<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RewardController extends Controller
{
    /**
     * [B2C] Lista únicamente los beneficios activos de la farmacia del paciente.
     */
    public function indexForPatient(Request $request): JsonResponse
    {
        $rewards = Reward::where('pharmacy_id', $request->user()->pharmacy_id)
            ->where('is_active', true)
            ->orderBy('points_cost')
            ->get();

        return response()->json([
            'rewards' => $rewards,
        ], 200);
    }

    /**
     * [B2B] Lista todos los beneficios de la farmacia (activos y pausados) para administración.
     */
    public function indexForPharmacy(Request $request): JsonResponse
    {
        $rewards = Reward::where('pharmacy_id', $request->user()->pharmacy_id)
            ->latest()
            ->get();

        return response()->json([
            'rewards' => $rewards,
        ], 200);
    }

    /**
     * [B2B] Crea un nuevo beneficio con imagen opcional almacenada vía File Storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:140'],
            'description' => ['required', 'string', 'min:5', 'max:1000'],
            'points_cost' => ['required', 'integer', 'min:10', 'max:100000'],
            'is_active' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'title.required' => 'Ingresá un título para el beneficio.',
            'description.required' => 'Ingresá una descripción clara de las condiciones.',
            'points_cost.required' => 'Indicá cuántos puntos cuesta canjear este beneficio.',
            'points_cost.min' => 'El costo mínimo es de :min puntos.',
            'image.image' => 'El archivo debe ser una imagen válida (JPG, PNG o WebP).',
            'image.max' => 'La imagen no puede superar los 2 MB.',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('rewards', 'public');
        }

        $reward = Reward::create([
            'pharmacy_id' => $request->user()->pharmacy_id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'points_cost' => (int) $validated['points_cost'],
            'image_path' => $imagePath,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Beneficio publicado correctamente.',
            'reward' => $reward,
        ], 201);
    }

    /**
     * [B2B] Actualiza un beneficio existente (Route Model Binding).
     */
    public function update(Request $request, Reward $reward): JsonResponse
    {
        if ($reward->pharmacy_id !== $request->user()->pharmacy_id) {
            return response()->json(['message' => 'No tenés permiso para editar este beneficio.'], 403);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'min:3', 'max:140'],
            'description' => ['sometimes', 'required', 'string', 'min:5', 'max:1000'],
            'points_cost' => ['sometimes', 'required', 'integer', 'min:10', 'max:100000'],
            'is_active' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $oldImage = $reward->image_path;

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('rewards', 'public');
        }

        unset($validated['image']);
        $reward->update($validated);

        // Si subió una imagen nueva y existía una anterior, eliminamos la vieja de Storage
        if ($request->hasFile('image') && $oldImage && Storage::disk('public')->exists($oldImage)) {
            Storage::disk('public')->delete($oldImage);
        }

        return response()->json([
            'message' => 'Beneficio actualizado correctamente.',
            'reward' => $reward->fresh(),
        ], 200);
    }

    /**
     * [B2B] Pausa o reactiva rápidamente un beneficio sin abrir el formulario completo.
     */
    public function toggleActive(Request $request, Reward $reward): JsonResponse
    {
        if ($reward->pharmacy_id !== $request->user()->pharmacy_id) {
            return response()->json(['message' => 'No tenés permiso para modificar este beneficio.'], 403);
        }

        $reward->is_active = !$reward->is_active;
        $reward->save();

        $estado = $reward->is_active ? 'activado' : 'pausado';

        return response()->json([
            'message' => "Beneficio {$estado} correctamente.",
            'reward' => $reward,
        ], 200);
    }

    /**
     * [B2B] Elimina un beneficio y su imagen asociada en File Storage.
     */
    public function destroy(Request $request, Reward $reward): JsonResponse
    {
        if ($reward->pharmacy_id !== $request->user()->pharmacy_id) {
            return response()->json(['message' => 'No tenés permiso para eliminar este beneficio.'], 403);
        }

        $oldImage = $reward->image_path;
        $reward->delete();

        if ($oldImage && Storage::disk('public')->exists($oldImage)) {
            Storage::disk('public')->delete($oldImage);
        }

        return response()->json([
            'message' => 'Beneficio eliminado correctamente.',
        ], 200);
    }
}