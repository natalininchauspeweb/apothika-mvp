<script setup>
import { inject, onMounted, ref } from 'vue';
import {
  ArrowRight,
  CheckCircle2,
  Coins,
  Gift,
  History,
  QrCode,
  RefreshCw,
  ShoppingBag,
  Sparkles,
  Store,
  Ticket,
  Users,
} from 'lucide-vue-next';
import { useAuth } from '../../composables/useAuth';
import { fetchPharmacyDashboard } from '../../services/pharmacy';
import { globalFeedbackKey } from '../../symbols/inject';
import BaseLoader from '../../components/BaseLoader.vue';

const { loggedUser } = useAuth();
const feedback = inject(globalFeedbackKey);

const pharmacy = ref(null);
const metrics = ref({
  points_issued: 0,
  coupons_used: 0,
  coupons_available: 0,
  total_patients: 0,
});
const recentTransactions = ref([]);
const isLoading = ref(true);
const isRefreshing = ref(false);

const TX_TYPE_META = {
  purchase: {
    label: 'Compra OTC',
    icon: ShoppingBag,
    badgeClass: 'bg-apothika-mint/20 text-apothika-emerald',
  },
  trivia: {
    label: 'Trivia',
    icon: Sparkles,
    badgeClass: 'bg-game-gold/30 text-apothika-charcoal',
  },
  redemption: {
    label: 'Canje',
    icon: Gift,
    badgeClass: 'bg-gray-100 text-gray-700',
  },
};

function formatNumber(value) {
  return new Intl.NumberFormat('es-AR').format(value || 0);
}

function formatDate(isoString) {
  if (!isoString) return '';
  return new Intl.DateTimeFormat('es-AR', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(isoString));
}

async function loadDashboard({ silent = false } = {}) {
  if (silent) {
    isRefreshing.value = true;
  } else {
    isLoading.value = true;
  }

  try {
    const data = await fetchPharmacyDashboard();
    pharmacy.value = data.pharmacy;
    metrics.value = data.metrics || metrics.value;
    recentTransactions.value = data.recent_transactions || [];
  } catch (error) {
    feedback.show({
      type: 'error',
      message: error.message || 'No pudimos cargar las métricas de tu farmacia.',
    });
  } finally {
    isLoading.value = false;
    isRefreshing.value = false;
  }
}

onMounted(() => {
  loadDashboard();
});
</script>

<template>
  <main class="max-w-5xl mx-auto w-full px-4 py-6 space-y-6">
    <!-- Cabecera del panel B2B -->
    <section class="card-duo p-6 border-apothika-emerald/25 flex flex-wrap items-start justify-between gap-4">
      <div class="space-y-1.5">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-apothika-mint/20 text-apothika-emerald font-bold text-xs uppercase tracking-wide">
          <Store class="w-4 h-4" aria-hidden="true" />
          <span>Panel B2B APOTHIKA</span>
        </div>

        <h1 class="text-2xl sm:text-3xl font-extrabold text-apothika-emerald">
          {{ pharmacy?.name || loggedUser.pharmacy?.name || 'Panel de Farmacia' }}
        </h1>

        <p class="text-sm text-brand-muted">
          Tasa activa:
          <strong class="text-apothika-charcoal">
            {{ pharmacy?.points_per_1000_ars ?? loggedUser.pharmacy?.points_per_1000_ars ?? 10 }} pts cada $1.000 ARS
          </strong>
          en productos de venta libre (OTC).
        </p>
      </div>

      <button
        type="button"
        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border-2 border-gray-200 bg-white hover:bg-gray-50 text-xs font-bold text-apothika-emerald transition cursor-pointer disabled:opacity-50"
        :disabled="isLoading || isRefreshing"
        @click="loadDashboard({ silent: true })"
      >
        <RefreshCw
          class="w-3.5 h-3.5"
          :class="{ 'animate-spin': isRefreshing }"
          aria-hidden="true"
        />
        <span>Actualizar</span>
      </button>
    </section>

    <!-- Estado de carga inicial -->
    <div v-if="isLoading" class="card-duo p-10 flex flex-col items-center gap-3 text-sm text-brand-muted">
      <BaseLoader color="#165A4B" size="md" />
      <p>Cargando métricas del mostrador...</p>
    </div>

    <template v-else>
      <!-- KPIs principales -->
      <section aria-labelledby="kpi-heading" class="space-y-3">
        <h2 id="kpi-heading" class="text-base font-extrabold text-apothika-charcoal">
          Resumen del programa
        </h2>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
          <article class="card-duo p-4 sm:p-5 space-y-2">
            <div class="w-10 h-10 rounded-xl bg-apothika-mint/20 text-apothika-emerald inline-flex items-center justify-center">
              <Users class="w-5 h-5" aria-hidden="true" />
            </div>
            <p class="text-xs font-semibold text-brand-muted">Pacientes adheridos</p>
            <p class="text-2xl sm:text-3xl font-heading font-extrabold text-apothika-charcoal">
              {{ formatNumber(metrics.total_patients) }}
            </p>
          </article>

          <article class="card-duo p-4 sm:p-5 space-y-2">
            <div class="w-10 h-10 rounded-xl bg-game-gold/30 text-apothika-charcoal inline-flex items-center justify-center">
              <Coins class="w-5 h-5" aria-hidden="true" />
            </div>
            <p class="text-xs font-semibold text-brand-muted">Puntos emitidos</p>
            <p class="text-2xl sm:text-3xl font-heading font-extrabold text-apothika-charcoal">
              {{ formatNumber(metrics.points_issued) }}
            </p>
          </article>

          <article class="card-duo p-4 sm:p-5 space-y-2">
            <div class="w-10 h-10 rounded-xl bg-game-success/15 text-game-success inline-flex items-center justify-center">
              <CheckCircle2 class="w-5 h-5" aria-hidden="true" />
            </div>
            <p class="text-xs font-semibold text-brand-muted">Cupones validados</p>
            <p class="text-2xl sm:text-3xl font-heading font-extrabold text-apothika-charcoal">
              {{ formatNumber(metrics.coupons_used) }}
            </p>
          </article>

          <article class="card-duo p-4 sm:p-5 space-y-2">
            <div class="w-10 h-10 rounded-xl bg-apothika-coral/15 text-apothika-coral inline-flex items-center justify-center">
              <Ticket class="w-5 h-5" aria-hidden="true" />
            </div>
            <p class="text-xs font-semibold text-brand-muted">Cupones por canjear</p>
            <p class="text-2xl sm:text-3xl font-heading font-extrabold text-apothika-charcoal">
              {{ formatNumber(metrics.coupons_available) }}
            </p>
          </article>
        </div>
      </section>

      <!-- Accesos directos operativos -->
      <section aria-labelledby="quick-actions-heading" class="space-y-3">
        <h2 id="quick-actions-heading" class="text-base font-extrabold text-apothika-charcoal">
          Acciones rápidas de mostrador
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <RouterLink
            :to="{ name: 'pharmacy.counter', query: { tab: 'points' } }"
            class="card-duo p-5 hover:border-apothika-emerald transition flex items-center justify-between gap-4 group"
          >
            <div class="flex items-start gap-4">
              <span class="w-12 h-12 rounded-2xl bg-apothika-emerald text-white inline-flex items-center justify-center shrink-0">
                <QrCode class="w-6 h-6" aria-hidden="true" />
              </span>
              <div class="space-y-1">
                <h3 class="text-base font-extrabold text-apothika-charcoal group-hover:text-apothika-emerald transition">
                  Acreditar puntos OTC
                </h3>
                <p class="text-xs text-brand-muted leading-relaxed">
                  Escaneá el QR del paciente o buscá por DNI para sumar puntos con su multiplicador de nivel.
                </p>
              </div>
            </div>
            <ArrowRight class="w-5 h-5 text-apothika-emerald shrink-0" aria-hidden="true" />
          </RouterLink>

          <RouterLink
            :to="{ name: 'pharmacy.counter', query: { tab: 'coupon' } }"
            class="card-duo p-5 hover:border-apothika-emerald transition flex items-center justify-between gap-4 group"
          >
            <div class="flex items-start gap-4">
              <span class="w-12 h-12 rounded-2xl bg-apothika-mint text-apothika-emerald inline-flex items-center justify-center shrink-0">
                <Ticket class="w-6 h-6" aria-hidden="true" />
              </span>
              <div class="space-y-1">
                <h3 class="text-base font-extrabold text-apothika-charcoal group-hover:text-apothika-emerald transition">
                  Validar cupón en caja
                </h3>
                <p class="text-xs text-brand-muted leading-relaxed">
                  Escaneá o tipeá el código <span class="font-mono font-bold">CUP-XXXXXX</span> para aplicar el beneficio y marcarlo usado.
                </p>
              </div>
            </div>
            <ArrowRight class="w-5 h-5 text-apothika-emerald shrink-0" aria-hidden="true" />
          </RouterLink>
        </div>
      </section>

      <!-- Actividad reciente -->
      <section aria-labelledby="recent-activity-heading" class="card-duo p-6 space-y-4">
        <div class="flex items-center justify-between gap-3">
          <div class="inline-flex items-center gap-2">
            <History class="w-5 h-5 text-apothika-emerald" aria-hidden="true" />
            <h2 id="recent-activity-heading" class="text-lg font-extrabold text-apothika-charcoal">
              Actividad reciente
            </h2>
          </div>
          <span class="text-xs font-semibold text-brand-muted">Últimos 10 movimientos</span>
        </div>

        <div
          v-if="recentTransactions.length === 0"
          class="py-8 px-4 text-center rounded-2xl bg-apothika-offwhite text-sm text-brand-muted"
        >
          Todavía no se registraron movimientos en tu farmacia.
        </div>

        <ul v-else class="divide-y divide-gray-100">
          <li
            v-for="tx in recentTransactions"
            :key="tx.id"
            class="py-3.5 flex items-start justify-between gap-4"
          >
            <div class="flex items-start gap-3 min-w-0">
              <span
                class="w-10 h-10 rounded-xl inline-flex items-center justify-center shrink-0"
                :class="TX_TYPE_META[tx.type]?.badgeClass || 'bg-gray-100 text-gray-700'"
              >
                <component
                  :is="TX_TYPE_META[tx.type]?.icon || Coins"
                  class="w-5 h-5"
                  aria-hidden="true"
                />
              </span>

              <div class="min-w-0 space-y-0.5">
                <p class="text-sm font-bold text-apothika-charcoal leading-snug">
                  {{ tx.patient?.name || 'Paciente' }}
                  <span v-if="tx.patient?.dni" class="font-normal text-xs text-brand-muted">
                    · DNI {{ tx.patient.dni }}
                  </span>
                </p>
                <p class="text-xs text-brand-muted leading-snug">
                  {{ tx.description }}
                </p>
                <p class="text-[11px] font-medium text-brand-muted">
                  {{ TX_TYPE_META[tx.type]?.label || 'Movimiento' }} · {{ formatDate(tx.created_at) }}
                </p>
              </div>
            </div>

            <span
              class="font-heading font-extrabold text-sm sm:text-base whitespace-nowrap shrink-0"
              :class="tx.points >= 0 ? 'text-game-success' : 'text-game-error'"
            >
              {{ tx.points >= 0 ? `+${tx.points}` : tx.points }} pts
            </span>
          </li>
        </ul>
      </section>
    </template>
  </main>
</template>