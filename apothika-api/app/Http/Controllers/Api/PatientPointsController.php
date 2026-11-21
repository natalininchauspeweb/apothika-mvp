<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\PointsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientPointsController extends Controller
{
    public function __construct(
        private PointsService $pointsService
    ) {}

    /**
     * Devuelve el saldo actual, nivel de fidelización y el historial paginado del paciente.
     */
    public function summary(Request $request): JsonResponse
    {
        $patient = $request->user()->load('pharmacy');

        $transactionsQuery = Transaction::query()
            ->where('patient_id', $patient->id)
            ->latest();

        $type = $request->query('type');
        if (in_array($type, ['purchase', 'trivia', 'redemption'], true)) {
            $transactionsQuery->where('type', $type);
        }

        $transactions = $transactionsQuery->paginate(10);

        return response()->json([
            'points_balance' => $patient->points_balance,
            'qr_code' => $patient->qr_code,
            'dni' => $patient->dni,
            'points_per_1000_ars' => $patient->pharmacy?->points_per_1000_ars ?? 10,
            'tier' => $this->pointsService->getPatientTier($patient),
            'transactions' => $transactions,
        ], 200);
    }
}