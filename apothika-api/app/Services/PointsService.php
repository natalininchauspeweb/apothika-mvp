<?php

namespace App\Services;

use App\Models\Pharmacy;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Servicio centralizado de gestión de puntos.
 * Garantiza consistencia ACID entre el saldo del paciente (users.points_balance)
 * y el historial inmutable de movimientos (transactions).
 */
class PointsService
{
    /**
     * Busca un paciente perteneciente a la farmacia indicada mediante
     * su código QR personal, DNI o correo electrónico.
     */
    public function findPatientForPharmacy(Pharmacy $pharmacy, string $query): ?User
    {
        $clean = trim($query);
        // Normalizamos un posible DNI escrito con puntos o espacios (ej: 38.456.123 -> 38456123)
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
     * Calcula los puntos enteros a otorgar según el monto en pesos argentinos (ARS)
     * de productos de venta libre (OTC), usando aritmética entera exacta.
     */
    public function calculatePointsFromOtcAmount(Pharmacy $pharmacy, int $amountArs): int
    {
        return intdiv($amountArs * $pharmacy->points_per_1000_ars, 1000);
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
        ?string $notes = null
    ): array {
        if ($points <= 0) {
            throw new InvalidArgumentException('La cantidad de puntos a acreditar debe ser mayor a cero.');
        }

        $description = $this->buildOtcDescription($amountArs, $notes);

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
     * (Lo vamos a reutilizar en el módulo de Gamificación).
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
     * (Lo vamos a reutilizar en el módulo de Cupones).
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
     * Ejecuta el movimiento dentro de una transacción ACID con bloqueo pesimista
     * para evitar inconsistencias si llegan dos peticiones simultáneas.
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
            // Bloqueamos la fila del paciente hasta que finalice la transacción
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
    private function buildOtcDescription(?int $amountArs, ?string $notes): string
    {
        $base = 'Compra en mostrador — Venta libre (OTC)';

        if ($amountArs !== null) {
            $formatted = number_format($amountArs, 0, ',', '.');
            $base .= " (\${$formatted} ARS)";
        }

        if ($notes !== null && trim($notes) !== '') {
            $base .= ' · ' . trim($notes);
        }

        return $base;
    }
}