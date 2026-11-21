<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trivia;
use App\Services\PointsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TriviaController extends Controller
{
    public function __construct(
        private PointsService $pointsService
    ) {}

    /**
     * [B2C] Lista las trivias activas indicando cuáles ya completó el paciente.
     * La respuesta correcta permanece oculta ($hidden en el modelo) hasta que responda.
     */
    public function index(Request $request): JsonResponse
    {
        $patient = $request->user();

        $completedMap = $patient->completedTrivias()
            ->get()
            ->keyBy('id');

        $trivias = Trivia::where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(function (Trivia $trivia) use ($completedMap) {
                $record = $completedMap->get($trivia->id);

                return [
                    'id' => $trivia->id,
                    'category' => $trivia->category,
                    'question' => $trivia->question,
                    'options' => $trivia->options,
                    'points_reward' => $trivia->points_reward,
                    'completed' => $record !== null,
                    'answered_correctly' => $record ? (bool) $record->pivot->answered_correctly : null,
                    'explanation' => $record ? $trivia->explanation : null,
                ];
            });

        return response()->json([
            'trivias' => $trivias,
            'completed_count' => $completedMap->count(),
            'total_count' => $trivias->count(),
        ], 200);
    }

    /**
     * [B2C] Valida la respuesta en el servidor, evita duplicados y acredita puntos al acertar.
     */
    public function answer(Request $request, Trivia $trivia): JsonResponse
    {
        $patient = $request->user()->load('pharmacy');

        if (!$trivia->is_active) {
            return response()->json(['message' => 'Esta trivia no está disponible.'], 404);
        }

        $maxIndex = count($trivia->options) - 1;
        $validated = $request->validate([
            'selected_option_index' => ['required', 'integer', 'min:0', "max:{$maxIndex}"],
        ], [
            'selected_option_index.required' => 'Seleccioná una opción antes de responder.',
        ]);

        // Verificamos en la tabla pivot si el paciente ya respondió esta trivia
        $alreadyCompleted = $patient->completedTrivias()
            ->where('trivia_id', $trivia->id)
            ->exists();

        if ($alreadyCompleted) {
            return response()->json([
                'message' => 'Ya completaste esta trivia anteriormente.',
            ], 422);
        }

        $isCorrect = (int) $validated['selected_option_index'] === $trivia->correct_option_index;
        $pointsAwarded = 0;

        $newBalance = DB::transaction(function () use ($patient, $trivia, $isCorrect, &$pointsAwarded) {
            // Registramos la participación en la tabla pivot trivia_user
            $patient->completedTrivias()->attach($trivia->id, [
                'answered_correctly' => $isCorrect,
                'completed_at' => now(),
            ]);

            if ($isCorrect) {
                $pointsAwarded = $trivia->points_reward;
                $movement = $this->pointsService->creditTriviaReward(
                    pharmacy: $patient->pharmacy,
                    patient: $patient,
                    points: $pointsAwarded,
                    triviaId: $trivia->id,
                    category: $trivia->category
                );

                return $movement['patient']->points_balance;
            }

            return $patient->points_balance;
        });

        return response()->json([
            'correct' => $isCorrect,
            'correct_option_index' => $trivia->correct_option_index,
            'explanation' => $trivia->explanation,
            'points_awarded' => $pointsAwarded,
            'points_balance' => $newBalance,
            'message' => $isCorrect
                ? "¡Excelente! Sumaste +{$pointsAwarded} puntos."
                : 'No pasa nada, ¡lo importante es aprender! Revisá el dato educativo.',
        ], 200);
    }
}