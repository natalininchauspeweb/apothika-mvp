<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RedeemedCoupon;
use App\Models\Reward;
use App\Services\PointsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CouponController extends Controller
{
    public function __construct(
        private PointsService $pointsService
    ) {}

    /**
     * [B2C] Lista los cupones canjeados por el paciente autenticado (disponibles y usados).
     */
    public function indexForPatient(Request $request): JsonResponse
    {
        $coupons = RedeemedCoupon::with('reward:id,title,description,points_cost,image_path')
            ->where('patient_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'coupons' => $coupons,
        ], 200);
    }

    /**
     * [B2C] Canjea puntos del paciente por un cupón con código único/QR.
     */
    public function redeem(Request $request, Reward $reward): JsonResponse
    {
        $patient = $request->user()->load('pharmacy');

        if ($reward->pharmacy_id !== $patient->pharmacy_id || !$reward->is_active) {
            return response()->json([
                'message' => 'Este beneficio no se encuentra disponible para canje.',
            ], 404);
        }

        if ($patient->points_balance < $reward->points_cost) {
            return response()->json([
                'message' => 'No tenés puntos suficientes para canjear este beneficio.',
            ], 422);
        }

        try {
            $result = DB::transaction(function () use ($patient, $reward) {
                $coupon = RedeemedCoupon::create([
                    'patient_id' => $patient->id,
                    'pharmacy_id' => $patient->pharmacy_id,
                    'reward_id' => $reward->id,
                    'redeem_code' => $this->generateUniqueCode(),
                ]);

                $movement = $this->pointsService->debitCouponRedemption(
                    pharmacy: $patient->pharmacy,
                    patient: $patient,
                    pointsCost: $reward->points_cost,
                    couponId: $coupon->id,
                    rewardTitle: $reward->title
                );

                return [
                    'coupon' => $coupon->load('reward:id,title,description,points_cost,image_path'),
                    'points_balance' => $movement['patient']->points_balance,
                ];
            });
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => "¡Canje exitoso! Presentá el código {$result['coupon']->redeem_code} en el mostrador.",
            'coupon' => $result['coupon'],
            'points_balance' => $result['points_balance'],
        ], 201);
    }

    /**
     * [B2B] Valida y marca como "usado" un cupón presentado en caja.
     */
    public function validateInCounter(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'redeem_code' => ['required', 'string', 'max:32'],
        ], [
            'redeem_code.required' => 'Escaneá o ingresá el código del cupón.',
        ]);

        $code = Str::upper(trim($validated['redeem_code']));

        $coupon = RedeemedCoupon::with([
            'reward:id,title,description,points_cost',
            'patient:id,name,dni,email',
        ])
            ->where('pharmacy_id', $request->user()->pharmacy_id)
            ->where('redeem_code', $code)
            ->first();

        if (!$coupon) {
            return response()->json([
                'message' => 'No encontramos ningún cupón con ese código en tu farmacia.',
            ], 404);
        }

        if ($coupon->status === 'used') {
            return response()->json([
                'message' => 'Este cupón ya fue utilizado anteriormente.',
                'coupon' => $coupon,
            ], 422);
        }

        // Asignación explícita (fuera de $fillable por seguridad)
        $coupon->status = 'used';
        $coupon->used_at = now();
        $coupon->save();

        return response()->json([
            'message' => '¡Cupón validado correctamente! Beneficio aplicado en caja.',
            'coupon' => $coupon,
        ], 200);
    }

    /**
     * Genera un código corto y legible para QR o dictado en mostrador (ej: CUP-A7K9P2).
     */
    private function generateUniqueCode(): string
    {
        do {
            $code = 'CUP-' . Str::upper(Str::random(6));
        } while (RedeemedCoupon::where('redeem_code', $code)->exists());

        return $code;
    }
}