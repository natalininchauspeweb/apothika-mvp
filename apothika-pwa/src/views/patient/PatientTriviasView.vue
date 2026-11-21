<script setup>
import { computed, inject, onMounted, ref } from 'vue';
import {
  Award,
  CheckCircle2,
  Coins,
  Lightbulb,
  Play,
  Sparkles,
  XCircle,
} from 'lucide-vue-next';
import { answerPatientTrivia, fetchPatientTrivias } from '../../services/patient';
import { updateLocalPoints } from '../../services/auth';
import { globalFeedbackKey } from '../../symbols/inject';
import BaseLoader from '../../components/BaseLoader.vue';
import LoaderButton from '../../components/form/LoaderButton.vue';
import BaseButton from '../../components/form/BaseButton.vue';

const feedback = inject(globalFeedbackKey);

const trivias = ref([]);
const isLoading = ref(true);

// Estado de la trivia que el paciente está respondiendo actualmente
const activeTriviaId = ref(null);
const selectedOption = ref(null);
const isSubmitting = ref(false);
const lastResult = ref(null);

const completedCount = computed(() => trivias.value.filter((t) => t.completed).length);
const totalCount = computed(() => trivias.value.length);
const progressPercent = computed(() => {
  if (!totalCount.value) return 0;
  return Math.round((completedCount.value / totalCount.value) * 100);
});

const activeTrivia = computed(() =>
  trivias.value.find((t) => t.id === activeTriviaId.value) || null
);

const pendingTrivias = computed(() => trivias.value.filter((t) => !t.completed));
const completedTrivias = computed(() => trivias.value.filter((t) => t.completed));

async function loadTrivias() {
  isLoading.value = true;
  try {
    const data = await fetchPatientTrivias();
    trivias.value = data.trivias || [];

    // Abrimos automáticamente la primera trivia pendiente para reducir fricción
    const firstPending = trivias.value.find((t) => !t.completed);
    activeTriviaId.value = firstPending ? firstPending.id : null;
  } catch (error) {
    feedback.show({
      type: 'error',
      message: error.message || 'No pudimos cargar las trivias de bienestar.',
    });
  } finally {
    isLoading.value = false;
  }
}

function selectTrivia(triviaId) {
  activeTriviaId.value = triviaId;
  selectedOption.value = null;
  lastResult.value = null;
}

async function handleAnswer() {
  if (!activeTrivia.value || selectedOption.value === null || isSubmitting.value) return;

  isSubmitting.value = true;
  feedback.clear();

  try {
    const result = await answerPatientTrivia(activeTrivia.value.id, selectedOption.value);

    lastResult.value = {
      triviaId: activeTrivia.value.id,
      ...result,
    };

    // Actualizamos el estado local de esa trivia y el saldo global del paciente
    trivias.value = trivias.value.map((item) =>
      item.id === activeTrivia.value.id
        ? {
            ...item,
            completed: true,
            answered_correctly: result.correct,
            explanation: result.explanation,
          }
        : item
    );

    updateLocalPoints(result.points_balance);

    feedback.show({
      type: result.correct ? 'success' : 'info',
      message: result.message,
    });
  } catch (error) {
    feedback.show({
      type: 'error',
      message: error.message || 'No pudimos registrar tu respuesta.',
    });
  } finally {
    isSubmitting.value = false;
  }
}

function goToNextPending() {
  const next = trivias.value.find((t) => !t.completed);
  activeTriviaId.value = next ? next.id : null;
  selectedOption.value = null;
  lastResult.value = null;
}

onMounted(() => {
  loadTrivias();
});
</script>

<template>
  <main class="max-w-3xl mx-auto w-full px-4 py-6 space-y-6">
    <!-- Cabecera y barra de progreso -->
    <section class="card-duo p-6 space-y-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="space-y-1">
          <p class="text-xs font-bold uppercase tracking-wider text-brand-secondary inline-flex items-center gap-1.5">
            <Sparkles class="w-4 h-4" aria-hidden="true" />
            <span>Aprendé y sumá puntos</span>
          </p>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-primary">
            Trivias de bienestar
          </h1>
        </div>

        <div class="px-4 py-2 rounded-2xl bg-brand-surface text-brand-primary font-extrabold text-sm">
          {{ completedCount }} / {{ totalCount }} completadas
        </div>
      </div>

      <div class="space-y-1.5">
        <div
          role="progressbar"
          :aria-valuenow="progressPercent"
          aria-valuemin="0"
          aria-valuemax="100"
          aria-label="Progreso de trivias completadas"
          class="h-3.5 w-full rounded-full bg-gray-200 overflow-hidden"
        >
          <div
            class="h-full rounded-full bg-brand-secondary transition-all duration-300"
            :style="{ width: `${progressPercent}%` }"
          />
        </div>
        <p class="text-xs text-brand-muted">
          Cada pregunta acertada suma puntos directos a tu cuenta (una vez por trivia).
        </p>
      </div>
    </section>

    <!-- Estado de carga -->
    <div v-if="isLoading" class="card-duo p-10 flex flex-col items-center gap-3 text-sm text-brand-muted">
      <BaseLoader color="#28A2A5" size="md" />
      <p>Preparando tus desafíos de salud...</p>
    </div>

    <template v-else>
      <!-- Tarjeta interactiva de Trivia activa -->
      <section v-if="activeTrivia" class="card-duo p-6 space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <span class="px-3 py-1 rounded-full bg-brand-secondary/15 text-brand-primary font-bold text-xs uppercase tracking-wide">
            {{ activeTrivia.category }}
          </span>
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-game-gold/30 text-brand-primary font-extrabold text-xs">
            <Coins class="w-3.5 h-3.5" aria-hidden="true" />
            <span>+{{ activeTrivia.points_reward }} pts</span>
          </span>
        </div>

        <h2 class="text-lg sm:text-xl font-extrabold text-brand-primary leading-snug">
          {{ activeTrivia.question }}
        </h2>

        <!-- Opciones táctiles estilo Duolingo -->
        <div role="radiogroup" :aria-label="activeTrivia.question" class="space-y-3">
          <button
            v-for="(optionText, index) in activeTrivia.options"
            :key="index"
            type="button"
            role="radio"
            :aria-checked="selectedOption === index"
            :disabled="Boolean(lastResult) || isSubmitting"
            class="w-full text-left p-4 rounded-2xl border-2 border-b-4 transition flex items-start gap-3 cursor-pointer disabled:cursor-default"
            :class="[
              lastResult && index === lastResult.correct_option_index
                ? 'border-game-success bg-game-success/12 text-green-950 font-bold'
                : lastResult && selectedOption === index && !lastResult.correct
                  ? 'border-game-error bg-game-error/12 text-red-950 font-bold'
                  : selectedOption === index
                    ? 'border-brand-secondary bg-brand-secondary/10 text-brand-primary font-bold'
                    : 'border-gray-200 bg-white text-brand-text hover:border-brand-secondary/60',
            ]"
            @click="selectedOption = index"
          >
            <span
              class="w-7 h-7 rounded-lg border-2 text-xs font-extrabold inline-flex items-center justify-center shrink-0 mt-0.5"
              :class="
                selectedOption === index
                  ? 'border-brand-secondary bg-brand-secondary text-white'
                  : 'border-gray-300 text-brand-muted'
              "
            >
              {{ String.fromCharCode(65 + index) }}
            </span>
            <span class="text-sm leading-relaxed">{{ optionText }}</span>
          </button>
        </div>

        <!-- Botón de confirmar respuesta -->
        <div v-if="!lastResult" class="pt-1">
          <LoaderButton
            type="button"
            variant="primary"
            full-width
            :loading="isSubmitting"
            :disabled="selectedOption === null"
            @click="handleAnswer"
          >
            Comprobar respuesta
          </LoaderButton>
        </div>

        <!-- Panel de devolución educativa post-respuesta -->
        <div
          v-else
          role="status"
          aria-live="polite"
          class="rounded-2xl border-2 p-4 space-y-3"
          :class="
            lastResult.correct
              ? 'border-game-success bg-game-success/10 text-green-950'
              : 'border-game-warning bg-game-warning/15 text-amber-950'
          "
        >
          <div class="flex items-center gap-2 font-extrabold text-base">
            <CheckCircle2 v-if="lastResult.correct" class="w-5 h-5 text-game-success shrink-0" aria-hidden="true" />
            <XCircle v-else class="w-5 h-5 text-game-error shrink-0" aria-hidden="true" />
            <span>
              {{
                lastResult.correct
                  ? `¡Respuesta correcta! Sumaste +${lastResult.points_awarded} pts`
                  : 'Casi, pero hoy aprendiste algo clave'
              }}
            </span>
          </div>

          <p class="text-sm leading-relaxed flex items-start gap-2">
            <Lightbulb class="w-4 h-4 shrink-0 mt-0.5" aria-hidden="true" />
            <span>{{ lastResult.explanation }}</span>
          </p>

          <BaseButton
            type="button"
            :variant="lastResult.correct ? 'success' : 'brandDark'"
            full-width
            @click="goToNextPending"
          >
            {{ pendingTrivias.length > 0 ? 'Siguiente trivia' : 'Ver mi resumen' }}
          </BaseButton>
        </div>
      </section>

      <!-- Estado de todas las trivias completadas -->
      <section v-else class="card-duo p-8 text-center space-y-3">
        <div class="w-14 h-14 mx-auto rounded-2xl bg-game-gold/30 text-brand-primary inline-flex items-center justify-center">
          <Award class="w-8 h-8" aria-hidden="true" />
        </div>
        <h2 class="text-xl font-extrabold text-brand-primary">
          ¡Completaste todas las trivias disponibles!
        </h2>
        <p class="text-sm text-brand-muted max-w-md mx-auto">
          Excelente trabajo cuidando tu salud preventiva. Podés repasar las explicaciones abajo o canjear tus puntos en el catálogo.
        </p>
      </section>

      <!-- Lista de otras trivias pendientes -->
      <section v-if="pendingTrivias.length > 1" class="space-y-3">
        <h2 class="text-base font-extrabold text-brand-primary">
          Más desafíos disponibles
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <article
            v-for="trivia in pendingTrivias"
            :key="trivia.id"
            class="card-duo p-4 flex flex-col justify-between gap-3"
            :class="trivia.id === activeTriviaId ? 'border-brand-secondary ring-2 ring-brand-secondary/20' : ''"
          >
            <div class="space-y-1.5">
              <div class="flex items-center justify-between gap-2 text-xs">
                <span class="font-bold text-brand-secondary">{{ trivia.category }}</span>
                <span class="font-extrabold text-brand-primary">+{{ trivia.points_reward }} pts</span>
              </div>
              <h3 class="text-sm font-bold text-brand-primary leading-snug">
                {{ trivia.question }}
              </h3>
            </div>

            <button
              type="button"
              class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold border-2 transition cursor-pointer"
              :class="
                trivia.id === activeTriviaId
                  ? 'bg-brand-secondary border-brand-secondary text-white'
                  : 'bg-white border-gray-200 text-brand-primary hover:border-brand-secondary'
              "
              @click="selectTrivia(trivia.id)"
            >
              <Play class="w-3.5 h-3.5" aria-hidden="true" />
              <span>{{ trivia.id === activeTriviaId ? 'Jugando ahora' : 'Responder esta' }}</span>
            </button>
          </article>
        </div>
      </section>

      <!-- Repaso de trivias ya respondidas -->
      <section v-if="completedTrivias.length > 0" class="space-y-3">
        <h2 class="text-base font-extrabold text-brand-primary">
          Trivias completadas ({{ completedTrivias.length }})
        </h2>

        <div class="space-y-3">
          <article
            v-for="trivia in completedTrivias"
            :key="trivia.id"
            class="card-duo p-4 space-y-2 opacity-95"
          >
            <div class="flex flex-wrap items-center justify-between gap-2">
              <span class="text-xs font-bold text-brand-muted uppercase tracking-wide">
                {{ trivia.category }}
              </span>
              <span
                class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-0.5 rounded-full"
                :class="
                  trivia.answered_correctly
                    ? 'bg-game-success/15 text-green-900'
                    : 'bg-gray-100 text-brand-muted'
                "
              >
                <CheckCircle2 v-if="trivia.answered_correctly" class="w-3.5 h-3.5 text-game-success" aria-hidden="true" />
                <span>{{ trivia.answered_correctly ? `Acertada (+${trivia.points_reward} pts)` : 'Completada' }}</span>
              </span>
            </div>

            <h3 class="text-sm font-extrabold text-brand-primary">
              {{ trivia.question }}
            </h3>
            <p v-if="trivia.explanation" class="text-xs text-brand-muted leading-relaxed">
              {{ trivia.explanation }}
            </p>
          </article>
        </div>
      </section>
    </template>
  </main>
</template>