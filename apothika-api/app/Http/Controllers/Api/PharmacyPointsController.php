<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RedeemedCoupon;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PointsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PharmacyPointsController extends Controller
{
    /**
     * Inyección de Dependencias mediante el Service Container de Laravel.
     */
    public function __construct(
        private PointsService $pointsService
    ) {}

    /**
     * Métricas rápidas y actividad reciente para el panel principal de la Farmacia (B2B).
     */
    public function dashboard(Request $request): JsonResponse
    {
        $pharmacy = $request->user()->pharmacy;

        // Puntos totales emitidos por la farmacia (solo movimientos positivos: compras OTC y trivias)
        $pointsIssued = (int) Transaction::where('pharmacy_id', $pharmacy->id)
            ->where('points', '>', 0)
            ->sum('points');

        $couponsAvailable = RedeemedCoupon::where('pharmacy_id', $pharmacy->id)
            ->where('status', 'available')
            ->count();

        $couponsUsed = RedeemedCoupon::where('pharmacy_id', $pharmacy->id)
            ->where('status', 'used')
            ->count();

        $totalPatients = User::where('pharmacy_id', $pharmacy->id)
            ->where('role', 'patient')
            ->count();

        // Eager loading de patient para evitar el problema de N+1 queries
        $recentTransactions = Transaction::with('patient:id,name,dni,email')
            ->where('pharmacy_id', $pharmacy->id)
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'pharmacy' => $pharmacy,
            'metrics' => [
                'points_issued' => $pointsIssued,
                'coupons_used' => $couponsUsed,
                'coupons_available' => $couponsAvailable,
                'total_patients' => $totalPatients,
            ],
            'recent_transactions' => $recentTransactions,
        ], 200);
    }

    /**
     * Identifica a un paciente en mostrador por código QR escaneado, DNI o email.
     * Ejemplo: GET /api/pharmacy/patients/lookup?q=38456123
     */
    public function lookupPatient(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if ($query === '') {
            return response()->json([
                'message' => 'Ingresá o escaneá un código QR, DNI o email para buscar al paciente.',
            ], 422);
        }

        $pharmacy = $request->user()->pharmacy;
        $patient = $this->pointsService->findPatientForPharmacy($pharmacy, $query);

        if (!$patient) {
            return response()->json([
                'message' => 'No encontramos ningún paciente registrado con ese QR, DNI o correo.',
            ], 404);
        }

        return response()->json([
            'patient' => [
                'id' => $patient->id,
                'name' => $patient->name,
                'email' => $patient->email,
                'dni' => $patient->dni,
                'qr_code' => $patient->qr_code,
                'points_balance' => $patient->points_balance,
            ],
            'conversion_rate' => [
                'points_per_1000_ars' => $pharmacy->points_per_1000_ars,
            ],
        ], 200);
    }

    /**
     * Acredita puntos a un paciente por compra de productos de venta libre (OTC) en mostrador.
     * Permite enviar el monto en ARS (cálculo automático) o una cantidad manual de puntos.
     */
    public function awardPurchasePoints(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:120'],
            'amount_ars' => ['required_without:points', 'nullable', 'integer', 'min:100', 'max:10000000'],
            'points' => ['required_without:amount_ars', 'nullable', 'integer', 'min:1', 'max:100000'],
            'otc_confirmed' => ['required', 'accepted'],
            'notes' => ['nullable', 'string', 'max:120'],
        ], [
            'identifier.required' => 'Debés escanear el QR o indicar el DNI/email del paciente.',
            'amount_ars.required_without' => 'Ingresá el monto de la compra de venta libre en pesos (ARS) o los puntos a acreditar.',
            'amount_ars.integer' => 'El monto en ARS debe ser un número entero sin centavos.',
            'amount_ars.min' => 'El monto mínimo para sumar puntos es de $:min ARS.',
            'points.required_without' => 'Ingresá los puntos a acreditar o el monto de compra en ARS.',
            'points.min' => 'Debés acreditar al menos :min punto.',
            'otc_confirmed.required' => 'Debés confirmar que la compra corresponde a productos de venta libre (OTC).',
            'otc_confirmed.accepted' => 'Por normativa sanitaria, solo las compras de productos de venta libre (OTC) y dermocosmética suman puntos.',
        ]);

        $pharmacy = $request->user()->pharmacy;
        $patient = $this->pointsService->findPatientForPharmacy($pharmacy, $validated['identifier']);

        if (!$patient) {
            return response()->json([
                'message' => 'No se encontró un paciente válido para el identificador ingresado.',
            ], 404);
        }

        // Si se envió monto en ARS, calculamos con la tasa de la farmacia; si no, usamos los puntos manuales
        $amountArs = isset($validated['amount_ars']) ? (int) $validated['amount_ars'] : null;
        $pointsToAward = $amountArs !== null
            ? $this->pointsService->calculatePointsFromOtcAmount($pharmacy, $amountArs)
            : (int) $validated['points'];

        if ($pointsToAward < 1) {
            return response()->json([
                'message' => 'El monto ingresado no alcanza para acreditar al menos 1 punto con la tasa actual.',
            ], 422);
        }

        $result = $this->pointsService->creditOtcPurchase(
            pharmacy: $pharmacy,
            patient: $patient,
            points: $pointsToAward,
            amountArs: $amountArs,
            notes: $validated['notes'] ?? null
        );

        return response()->json([
            'message' => "Se acreditaron +{$pointsToAward} puntos a {$result['patient']->name} por compra de venta libre.",
            'patient' => [
                'id' => $result['patient']->id,
                'name' => $result['patient']->name,
                'dni' => $result['patient']->dni,
                'qr_code' => $result['patient']->qr_code,
                'points_balance' => $result['patient']->points_balance,
            ],
            'transaction' => $result['transaction'],
        ], 201);
    }
}