<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PharmacyController extends Controller
{
    /**
     * [Público] Devuelve la configuración visual White-Label de una farmacia por su slug.
     */
    public function showBranding(string $slug): JsonResponse
    {
        $pharmacy = Pharmacy::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json([
            'pharmacy' => $this->formatBranding($pharmacy),
        ], 200);
    }

    /**
     * [B2B] Permite al dueño de la farmacia personalizar su marca blanca:
     * nombre, colores HEX, preset tipográfico curado, tasa de puntos y logo.
     */
    public function updateBranding(Request $request): JsonResponse
    {
        $pharmacy = $request->user()->pharmacy;

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:120'],
            'primary_color' => ['sometimes', 'required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['sometimes', 'required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'surface_color' => ['sometimes', 'required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_color' => ['sometimes', 'required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'font_preset' => ['sometimes', 'required', 'in:dalton,apothika,editorial,accessible'],
            'points_per_1000_ars' => ['sometimes', 'required', 'integer', 'min:1', 'max:500'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ], [
            'primary_color.regex' => 'El color primario debe ser un código HEX válido (ej: #002D3A).',
            'secondary_color.regex' => 'El color secundario debe ser un código HEX válido (ej: #28A2A5).',
            'font_preset.in' => 'Seleccioná una de las duplas tipográficas disponibles.',
            'logo.image' => 'El logo debe ser una imagen válida (PNG, JPG, WebP o SVG).',
        ]);

        $oldLogo = $pharmacy->logo_path;

        if ($request->hasFile('logo')) {
            $validated['logo_path'] = $request->file('logo')->store('logos', 'public');
        }

        unset($validated['logo']);
        $pharmacy->update($validated);

        if ($request->hasFile('logo') && $oldLogo && Storage::disk('public')->exists($oldLogo)) {
            Storage::disk('public')->delete($oldLogo);
        }

        return response()->json([
            'message' => 'Identidad visual actualizada correctamente.',
            'pharmacy' => $this->formatBranding($pharmacy->fresh()),
        ], 200);
    }

    private function formatBranding(Pharmacy $pharmacy): array
    {
        return [
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
        ];
    }
}