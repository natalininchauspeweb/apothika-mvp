<script setup>
import { computed, inject, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { Check, Coins, Gift, Lock, Sparkles, Ticket } from 'lucide-vue-next';
import { useAuth } from '../../composables/useAuth';
import { fetchPatientRewards, redeemPatientReward } from '../../services/patient';
import { updateLocalPoints } from '../../services/auth';
import { globalFeedbackKey } from '../../symbols/inject';
import BaseLoader from '../../components/BaseLoader.vue';
import LoaderButton from '../../components/form/LoaderButton.vue';

const router = useRouter();
const { loggedUser } = useAuth();
const feedback = inject(globalFeedbackKey);

const rewards = ref([]);
const isLoading = ref(true);
const confirmingRewardId = ref(null);
const redeemingRewardId = ref(null);

const currentBalance = computed(() => loggedUser.value.points_balance || 0);

async function loadRewards() {
  isLoading.value = true;
  try {
    const data = await fetchPatientRewards();
    rewards.value = data.rewards || [];
  } catch (error) {
    feedback.show({
      type: 'error',
      message: error.message || 'No pudimos cargar el catálogo de beneficios.',
    });
  } finally {
    isLoading.value = false;
  }
}

function getProgressPercent(cost) {
  if (!cost) return 100;
  return Math.min(100, Math.round((currentBalance.value / cost) * 100));
}

async function handleConfirmRedeem(reward) {
  if (redeemingRewardId.value) return;

  redeemingRewardId.value = reward.id;
  feedback.clear();

  try {
    const result = await redeemPatientReward(reward.id);
    updateLocalPoints(result.points_balance);
    confirmingRewardId.value = null;

    feedback.show({
      type: 'success',
      message: result.message,
      duration: 7000,
    });

    // Llevamos al paciente directo a su billetera para ver el cupón QR recién generado
    await router.push({ name: 'patient.coupons' });
  } catch (error) {
    feedback.show({
      type: 'error',
      message: error.message || 'No pudimos completar el canje.',
    });
  } finally {
    redeemingRewardId.value = null;
  }
}

onMounted(() => {
  loadRewards();
});
</script>

<template>
  <main class="max-w-3xl mx-auto w-full px-4 py-6 space-y-6">
    <!-- Encabezado y saldo disponible -->
    <section class="card-duo p-6 flex flex-wrap items-center justify-between gap-4">
      <div class="space-y-1">
        <p class="text-xs font-bold uppercase tracking-wider text-brand-secondary inline-flex items-center gap-1.5">
          <Gift class="w-4 h-4" aria-hidden="true" />
          <span>Canjeá tus puntos</span>
        </p>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-primary">
          Catálogo de beneficios
        </h1>
      </div>

      <div class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-game-gold/30 border-2 border-game-gold text-brand-primary font-extrabold text-base">
        <Coins class="w-5 h-5" aria-hidden="true" />
        <span>{{ new Intl.NumberFormat('es-AR').format(currentBalance) }} pts</span>
      </div>
    </section>

    <div v-if="isLoading" class="card-duo p-10 flex flex-col items-center gap-3 text-sm text-brand-muted">
      <BaseLoader color="#28A2A5" size="md" />
      <p>Cargando beneficios activos...</p>
    </div>

    <section v-else-if="rewards.length === 0" class="card-duo p-8 text-center space-y-2">
      <h2 class="text-lg font-extrabold text-brand-primary">
        No hay beneficios publicados por el momento
      </h2>
      <p class="text-sm text-brand-muted">
        Tu farmacia está actualizando el catálogo. Seguí sumando puntos mientras tanto.
      </p>
    </section>

    <section v-else aria-labelledby="rewards-catalog-heading" class="space-y-4">
      <div class="flex items-center justify-between gap-2">
        <h2 id="rewards-catalog-heading" class="text-base font-extrabold text-brand-primary">
          Beneficios disponibles ({{ rewards.length }})
        </h2>
        <RouterLink
          :to="{ name: 'patient.coupons' }"
          class="inline-flex items-center gap-1.5 text-xs font-bold text-brand-secondary hover:underline"
        >
          <Ticket class="w-4 h-4" aria-hidden="true" />
          <span>Ver mis cupones</span>
        </RouterLink>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <article
          v-for="reward in rewards"
          :key="reward.id"
          class="card-duo overflow-hidden flex flex-col justify-between"
        >
          <div>
            <!-- Imagen del beneficio o banner limpio de respaldo -->
            <div class="h-36 bg-brand-surface flex items-center justify-center overflow-hidden relative">
              <img
                v-if="reward.image_url"
                :src="reward.image_url"
                :alt="reward.title"
                class="w-full h-full object-cover"
              />
              <div
                v-else
                class="flex flex-col items-center justify-center gap-1.5 text-brand-secondary/75"
              >
                <Sparkles class="w-9 h-9" aria-hidden="true" />
                <span class="text-xs font-bold uppercase tracking-wider">Beneficio del club</span>
              </div>

              <span class="absolute top-3 right-3 inline-flex items-center gap-1 px-3 py-1 rounded-full bg-brand-primary text-white font-extrabold text-xs shadow-xs">
                <Coins class="w-3.5 h-3.5 text-game-gold" aria-hidden="true" />
                <span>{{ reward.points_cost }} pts</span>
              </span>
            </div>

            <div class="p-5 space-y-2">
              <h3 class="text-base font-extrabold text-brand-primary leading-snug">
                {{ reward.title }}
              </h3>
              <p class="text-xs text-brand-muted leading-relaxed">
                {{ reward.description }}
              </p>
            </div>
          </div>

          <div class="px-5 pb-5 space-y-3">
            <!-- Si todavía no le alcanzan los puntos, mostramos cuánto falta -->
            <div v-if="currentBalance < reward.points_cost" class="space-y-1.5">
              <div class="h-2 w-full rounded-full bg-gray-200 overflow-hidden">
                <div
                  class="h-full rounded-full bg-brand-secondary/70"
                  :style="{ width: `${getProgressPercent(reward.points_cost)}%` }"
                />
              </div>
              <p class="text-xs font-semibold text-brand-muted inline-flex items-center gap-1.5">
                <Lock class="w-3.5 h-3.5 shrink-0" aria-hidden="true" />
                <span>Te faltan {{ reward.points_cost - currentBalance }} pts para canjear</span>
              </p>
            </div>

            <!-- Confirmación en dos pasos para evitar canjes accidentales -->
            <div v-else-if="confirmingRewardId === reward.id" class="space-y-2 rounded-xl bg-brand-surface p-3">
              <p class="text-xs font-bold text-brand-primary text-center">
                ¿Canjear por {{ reward.points_cost }} pts?
              </p>
              <div class="grid grid-cols-2 gap-2">
                <button
                  type="button"
                  class="px-3 py-2 rounded-xl border-2 border-gray-300 bg-white text-xs font-bold text-brand-muted hover:bg-gray-50 cursor-pointer"
                  :disabled="Boolean(redeemingRewardId)"
                  @click="confirmingRewardId = null"
                >
                  Cancelar
                </button>
                <LoaderButton
                  type="button"
                  variant="success"
                  :loading="redeemingRewardId === reward.id"
                  @click="handleConfirmRedeem(reward)"
                >
                  <Check class="w-4 h-4" aria-hidden="true" />
                  <span>Sí, canjear</span>
                </LoaderButton>
              </div>
            </div>

            <!-- Botón inicial de canje -->
            <button
              v-else
              type="button"
              class="btn-duo w-full bg-brand-secondary text-white font-bold text-sm px-4 py-2.5 rounded-xl inline-flex items-center justify-center gap-2 cursor-pointer"
              @click="confirmingRewardId = reward.id"
            >
              <Gift class="w-4 h-4" aria-hidden="true" />
              <span>Canjear beneficio</span>
            </button>
          </div>
        </article>
      </div>
    </section>
  </main>
</template>