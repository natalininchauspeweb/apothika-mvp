<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy;
use Illuminate\Http\JsonResponse;

class PharmacyController extends Controller
{
    /**
     * Devuelve la configuración visual White-Label pública de una farmacia por su slug.
     */
    public function showBranding(string $slug): JsonResponse
    {
        $pharmacy = Pharmacy::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json([
            'pharmacy' => [
                'id' => $pharmacy->id,
                'name' => $pharmacy->name,
                'slug' => $pharmacy->slug,
                'logo_url' => $pharmacy->logo_url,
                'primary_color' => $pharmacy->primary_color,
                'secondary_color' => $pharmacy->secondary_color,
                'surface_color' => $pharmacy->surface_color,
                'text_color' => $pharmacy->text_color,
                'font_preset' => $pharmacy->font_preset,
                'points_per_1000_ars' => $pharmacy->points_per_1000_ars,
            ],
        ], 200);
    }
}