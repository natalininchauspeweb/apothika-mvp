<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientPointsController extends Controller
{
    /**
     * Devuelve el saldo actual del paciente y su historial paginado de movimientos.
     * Permite filtrar opcionalmente por tipo (?type=purchase | trivia | redemption).
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

        // Paginación nativa de Laravel para no sobrecargar la PWA en celulares
        $transactions = $transactionsQuery->paginate(10);

        return response()->json([
            'points_balance' => $patient->points_balance,
            'qr_code' => $patient->qr_code,
            'dni' => $patient->dni,
            'points_per_1000_ars' => $patient->pharmacy?->points_per_1000_ars ?? 10,
            'transactions' => $transactions,
        ], 200);
    }
}