<?php

namespace App\Services;

use App\Models\Pharmacy;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Servicio centralizado de gestión de puntos y niveles de fidelización.
 * Garantiza consistencia ACID entre el saldo del paciente (users.points_balance)
 * y el historial inmutable de movimientos (transactions).
 */
class PointsService
{
    /**
     * Escala de niveles basada en puntos históricos ganados (compras OTC + trivias).
     * Los canjes nunca hacen perder el nivel alcanzado.
     */
    private const TIERS = [
        [
            'level' => 1,
            'slug' => 'initial',
            'name' => 'Inicial',
            'min_points' => 0,
            'multiplier_percent' => 100,
            'multiplier_label' => '1x',
            'bonus_percentage' => 0,
        ],
        [
            'level' => 2,
            'slug' => 'plus',
            'name' => 'Bienestar Plus',
            'min_points' => 500,
            'multiplier_percent' => 125,
            'multiplier_label' => '1,25x',
            'bonus_percentage' => 25,
        ],
        [
            'level' => 3,
            'slug' => 'ambassador',
            'name' => 'Embajador',
            'min_points' => 1500,
            'multiplier_percent' => 150,
            'multiplier_label' => '1,5x',
            'bonus_percentage' => 50,
        ],
    ];

    /**
     * Busca un paciente perteneciente a la farmacia indicada mediante
     * su código QR personal, DNI o correo electrónico.
     */
    public function findPatientForPharmacy(Pharmacy $pharmacy, string $query): ?User
    {
        $clean = trim($query);
        $cleanDni = preg_replace('/[^0-9]/', '', $clean);

        return User::query()
            ->where('pharmacy_id', $pharmacy->id)
            ->where('role', 'patient')
            ->where(function ($builder) use ($clean, $cleanDni) {
                $builder->where('qr_code', $clean)
                    ->orWhere('email', Str::lower($clean));

                if ($cleanDni !== '') {
                    $builder->orWhere('dni', $cleanDni);
                }
            })
            ->first();
    }

    /**
     * Devuelve la suma histórica de puntos ganados por el paciente (sin descontar canjes).
     */
    public function getLifetimePoints(User $patient): int
    {
        return (int) Transaction::where('patient_id', $patient->id)
            ->where('points', '>', 0)
            ->sum('points');
    }

    /**
     * Calcula el nivel actual del paciente, su multiplicador OTC y progreso al próximo nivel.
     */
    public function getPatientTier(User $patient): array
    {
        $lifetimePoints = $this->getLifetimePoints($patient);
        $currentIndex = 0;

        foreach (self::TIERS as $index => $tier) {
            if ($lifetimePoints >= $tier['min_points']) {
                $currentIndex = $index;
            }
        }

        $current = self::TIERS[$currentIndex];
        $next = self::TIERS[$currentIndex + 1] ?? null;

        if ($next) {
            $span = $next['min_points'] - $current['min_points'];
            $earnedInTier = $lifetimePoints - $current['min_points'];
            $progressPercent = $span > 0 ? min(100, intdiv($earnedInTier * 100, $span)) : 100;
            $pointsToNext = max(0, $next['min_points'] - $lifetimePoints);
        } else {
            $progressPercent = 100;
            $pointsToNext = 0;
        }

        return [
            'level' => $current['level'],
            'slug' => $current['slug'],
            'name' => $current['name'],
            'multiplier_percent' => $current['multiplier_percent'],
            'multiplier_label' => $current['multiplier_label'],
            'bonus_percentage' => $current['bonus_percentage'],
            'lifetime_points' => $lifetimePoints,
            'next_tier_name' => $next['name'] ?? null,
            'next_tier_min_points' => $next['min_points'] ?? null,
            'points_to_next_tier' => $pointsToNext,
            'progress_percent' => $progressPercent,
        ];
    }

    /**
     * Calcula los puntos enteros a otorgar según el monto en pesos argentinos (ARS)
     * de productos de venta libre (OTC), aplicando el multiplicador del nivel del paciente.
     */
    public function calculatePointsFromOtcAmount(Pharmacy $pharmacy, int $amountArs, ?User $patient = null): int
    {
        $multiplierPercent = $patient ? $this->getPatientTier($patient)['multiplier_percent'] : 100;

        // Aritmética entera exacta: (ARS * puntos_cada_1000 * multiplicador%) / 100.000
        return intdiv($amountArs * $pharmacy->points_per_1000_ars * $multiplierPercent, 100000);
    }

    /**
     * Acredita puntos por compra de productos de venta libre (OTC) en mostrador.
     *
     * @return array{patient: User, transaction: Transaction}
     */
    public function creditOtcPurchase(
        Pharmacy $pharmacy,
        User $patient,
        int $points,
        ?int $amountArs = null,
        ?string $notes = null,
        ?array $appliedTier = null
    ): array {
        if ($points <= 0) {
            throw new InvalidArgumentException('La cantidad de puntos a acreditar debe ser mayor a cero.');
        }

        $description = $this->buildOtcDescription($amountArs, $notes, $appliedTier);

        return $this->applyMovement(
            pharmacy: $pharmacy,
            patient: $patient,
            type: 'purchase',
            pointsDelta: $points,
            description: $description
        );
    }

    /**
     * Acredita puntos por responder correctamente una trivia educativa.
     *
     * @return array{patient: User, transaction: Transaction}
     */
    public function creditTriviaReward(
        Pharmacy $pharmacy,
        User $patient,
        int $points,
        int $triviaId,
        string $category
    ): array {
        return $this->applyMovement(
            pharmacy: $pharmacy,
            patient: $patient,
            type: 'trivia',
            pointsDelta: $points,
            description: "Trivia completada — {$category}",
            referenceId: $triviaId
        );
    }

    /**
     * Descuenta puntos cuando el paciente canjea un beneficio del catálogo.
     *
     * @return array{patient: User, transaction: Transaction}
     */
    public function debitCouponRedemption(
        Pharmacy $pharmacy,
        User $patient,
        int $pointsCost,
        int $couponId,
        string $rewardTitle
    ): array {
        return $this->applyMovement(
            pharmacy: $pharmacy,
            patient: $patient,
            type: 'redemption',
            pointsDelta: -$pointsCost,
            description: "Canje de beneficio: {$rewardTitle}",
            referenceId: $couponId
        );
    }

    /**
     * Ejecuta el movimiento dentro de una transacción ACID con bloqueo pesimista.
     *
     * @return array{patient: User, transaction: Transaction}
     */
    private function applyMovement(
        Pharmacy $pharmacy,
        User $patient,
        string $type,
        int $pointsDelta,
        string $description,
        ?int $referenceId = null
    ): array {
        return DB::transaction(function () use ($pharmacy, $patient, $type, $pointsDelta, $description, $referenceId) {
            $lockedPatient = User::whereKey($patient->id)
                ->lockForUpdate()
                ->firstOrFail();

            $newBalance = $lockedPatient->points_balance + $pointsDelta;

            if ($newBalance < 0) {
                throw new InvalidArgumentException('Saldo de puntos insuficiente para realizar esta operación.');
            }

            $lockedPatient->points_balance = $newBalance;
            $lockedPatient->save();

            $transaction = Transaction::create([
                'patient_id' => $lockedPatient->id,
                'pharmacy_id' => $pharmacy->id,
                'type' => $type,
                'points' => $pointsDelta,
                'description' => $description,
                'reference_id' => $referenceId,
            ]);

            return [
                'patient' => $lockedPatient,
                'transaction' => $transaction,
            ];
        });
    }

    /**
     * Arma una descripción clara y auditable para el historial del paciente.
     */
    private function buildOtcDescription(?int $amountArs, ?string $notes, ?array $appliedTier = null): string
    {
        $base = 'Compra en mostrador — Venta libre (OTC)';

        if ($amountArs !== null) {
            $formatted = number_format($amountArs, 0, ',', '.');
            $base .= " (\${$formatted} ARS)";
        }

        if ($appliedTier && ($appliedTier['multiplier_percent'] ?? 100) > 100) {
            $base .= " · Nivel {$appliedTier['name']} {$appliedTier['multiplier_label']}";
        }

        if ($notes !== null && trim($notes) !== '') {
            $base .= ' · ' . trim($notes);
        }

        return $base;
    }
}