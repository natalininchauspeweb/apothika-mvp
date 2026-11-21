<script setup>
import { computed, inject, onMounted, ref } from 'vue';
import {
  Check,
  ChevronLeft,
  ChevronRight,
  Coins,
  Copy,
  Gift,
  History,
  Info,
  QrCode,
  RefreshCw,
  ShoppingBag,
  Sparkles,
} from 'lucide-vue-next';
import { useAuth } from '../../composables/useAuth';
import { useTheme } from '../../composables/useTheme';
import { fetchPatientPoints } from '../../services/patient';
import { updateLocalPoints } from '../../services/auth';
import { globalFeedbackKey } from '../../symbols/inject';
import QrCodeCard from '../../components/QrCodeCard.vue';
import BaseLoader from '../../components/BaseLoader.vue';

const { loggedUser } = useAuth();
const { pharmacyBranding } = useTheme();
const feedback = inject(globalFeedbackKey);

const pointsPer1000 = ref(10);
const transactions = ref([]);
const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
});

const activeFilter = ref('');
const isLoading = ref(true);
const isRefreshing = ref(false);
const copiedCode = ref(false);

const FILTER_OPTIONS = [
  { value: '', label: 'Todos' },
  { value: 'purchase', label: 'Compras OTC' },
  { value: 'trivia', label: 'Trivias' },
  { value: 'redemption', label: 'Canjes' },
];

const TYPE_META = {
  purchase: {
    label: 'Compra venta libre',
    icon: ShoppingBag,
    badgeClass: 'bg-brand-secondary/15 text-brand-primary',
  },
  trivia: {
    label: 'Trivia de salud',
    icon: Sparkles,
    badgeClass: 'bg-game-gold/30 text-brand-primary',
  },
  redemption: {
    label: 'Canje de cupón',
    icon: Gift,
    badgeClass: 'bg-gray-100 text-brand-muted',
  },
};

const formattedBalance = computed(() =>
  new Intl.NumberFormat('es-AR').format(loggedUser.value.points_balance || 0)
);

async function loadPointsData({ page = 1, silent = false } = {}) {
  if (silent) {
    isRefreshing.value = true;
  } else {
    isLoading.value = true;
  }

  try {
    const data = await fetchPatientPoints({
      type: activeFilter.value,
      page,
    });

    pointsPer1000.value = data.points_per_1000_ars ?? 10;
    updateLocalPoints(data.points_balance);

    transactions.value = data.transactions?.data || [];
    pagination.value = {
      current_page: data.transactions?.current_page || 1,
      last_page: data.transactions?.last_page || 1,
      total: data.transactions?.total || 0,
    };
  } catch (error) {
    feedback.show({
      type: 'error',
      message: error.message || 'No pudimos cargar tus puntos y movimientos.',
    });
  } finally {
    isLoading.value = false;
    isRefreshing.value = false;
  }
}

function handleFilterChange(type) {
  if (activeFilter.value === type) return;
  activeFilter.value = type;
  loadPointsData({ page: 1 });
}

function handlePageChange(nextPage) {
  if (nextPage < 1 || nextPage > pagination.value.last_page) return;
  loadPointsData({ page: nextPage });
}

async function copyQrCode() {
  if (!loggedUser.value.qr_code || !navigator.clipboard) return;
  try {
    await navigator.clipboard.writeText(loggedUser.value.qr_code);
    copiedCode.value = true;
    setTimeout(() => {
      copiedCode.value = false;
    }, 2000);
  } catch {
    feedback.show({
      type: 'info',
      message: 'Podés dictar tu DNI o mostrar el código QR directamente en caja.',
    });
  }
}

function formatDate(isoString) {
  if (!isoString) return '';
  return new Intl.DateTimeFormat('es-AR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(isoString));
}

onMounted(() => {
  loadPointsData({ page: 1 });
});
</script>

<template>
  <main class="max-w-3xl mx-auto w-full px-4 py-6 space-y-6">
    <!-- Hero: Saludo y Saldo Principal -->
    <section class="card-duo p-6 space-y-4">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="space-y-1">
          <p class="text-xs font-bold uppercase tracking-wider text-brand-secondary">
            {{ pharmacyBranding.name }} · Club de Beneficios
          </p>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-primary">
            Hola, {{ loggedUser.name }}
          </h1>
        </div>

        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border-2 border-gray-200 bg-white hover:bg-gray-50 text-xs font-bold text-brand-primary transition cursor-pointer disabled:opacity-50"
          :disabled="isRefreshing || isLoading"
          @click="loadPointsData({ page: pagination.current_page, silent: true })"
        >
          <RefreshCw
            class="w-3.5 h-3.5"
            :class="{ 'animate-spin': isRefreshing }"
            aria-hidden="true"
          />
          <span>Actualizar</span>
        </button>
      </div>

      <!-- Tarjeta destacada de puntos -->
      <div class="rounded-2xl bg-brand-primary text-white p-5 flex flex-wrap items-center justify-between gap-4">
        <div class="space-y-1">
          <p class="text-xs font-semibold uppercase tracking-wider text-white/75">
            Saldo disponible
          </p>
          <p class="text-3xl sm:text-4xl font-heading font-extrabold inline-flex items-center gap-2.5">
            <Coins class="w-8 h-8 text-game-gold shrink-0" aria-hidden="true" />
            <span>{{ formattedBalance }} pts</span>
          </p>
        </div>

        <div class="rounded-xl bg-white/12 px-4 py-2.5 text-xs leading-relaxed max-w-xs">
          <p class="font-bold text-game-gold">
            +{{ pointsPer1000 }} pts cada $1.000 ARS
          </p>
          <p class="text-white/85">
            En compras de productos de venta libre (OTC), dermocosmética y perfumería.
          </p>
        </div>
      </div>
    </section>

    <!-- Pase Digital QR para Mostrador -->
    <section class="card-duo p-6 space-y-5">
      <div class="flex items-start justify-between gap-3">
        <div class="space-y-1">
          <div class="inline-flex items-center gap-2 text-brand-secondary font-bold text-sm">
            <QrCode class="w-5 h-5" aria-hidden="true" />
            <h2 class="text-lg font-extrabold text-brand-primary">
              Tu pase digital de mostrador
            </h2>
          </div>
          <p class="text-sm text-brand-muted">
            Mostrá este código QR o indicá tu DNI al finalizar tu compra de venta libre para acreditar puntos al instante.
          </p>
        </div>
      </div>

      <div class="flex flex-col sm:flex-row items-center gap-6 bg-brand-surface/70 rounded-2xl p-5">
        <QrCodeCard
          v-if="loggedUser.qr_code"
          :value="loggedUser.qr_code"
          label="Pase digital de paciente"
          :size="180"
        />

        <div class="flex-1 w-full space-y-4 text-center sm:text-left">
          <div class="space-y-1">
            <p class="text-xs font-bold uppercase tracking-wider text-brand-muted">
              DNI vinculado
            </p>
            <p class="text-xl font-heading font-extrabold text-brand-primary">
              {{ loggedUser.dni || 'Sin DNI registrado' }}
            </p>
          </div>

          <button
            type="button"
            class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl border-2 border-gray-200 bg-white hover:border-brand-secondary text-xs font-bold text-brand-primary transition cursor-pointer"
            @click="copyQrCode"
          >
            <Check v-if="copiedCode" class="w-4 h-4 text-game-success" aria-hidden="true" />
            <Copy v-else class="w-4 h-4 text-brand-secondary" aria-hidden="true" />
            <span>{{ copiedCode ? 'Código copiado' : 'Copiar código identificador' }}</span>
          </button>

          <p class="text-xs text-brand-muted flex items-start justify-center sm:justify-start gap-1.5 leading-relaxed">
            <Info class="w-4 h-4 shrink-0 mt-0.5 text-brand-secondary" aria-hidden="true" />
            <span>
              Conforme a la normativa vigente, los medicamentos bajo receta quedan excluidos del programa de puntos.
            </span>
          </p>
        </div>
      </div>
    </section>

    <!-- Historial de Movimientos con Filtros y Paginación -->
    <section class="card-duo p-6 space-y-5">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="inline-flex items-center gap-2">
          <History class="w-5 h-5 text-brand-secondary" aria-hidden="true" />
          <h2 class="text-lg font-extrabold text-brand-primary">
            Historial de movimientos
          </h2>
        </div>

        <span class="text-xs font-semibold text-brand-muted">
          {{ pagination.total }} {{ pagination.total === 1 ? 'movimiento' : 'movimientos' }}
        </span>
      </div>

      <!-- Filtros por tipo -->
      <div role="group" aria-label="Filtrar movimientos por categoría" class="flex flex-wrap gap-2">
        <button
          v-for="option in FILTER_OPTIONS"
          :key="option.value || 'all'"
          type="button"
          :aria-pressed="activeFilter === option.value"
          class="px-3.5 py-1.5 rounded-full text-xs font-bold border-2 transition cursor-pointer"
          :class="
            activeFilter === option.value
              ? 'bg-brand-primary border-brand-primary text-white'
              : 'bg-white border-gray-200 text-brand-muted hover:border-brand-secondary hover:text-brand-primary'
          "
          @click="handleFilterChange(option.value)"
        >
          {{ option.label }}
        </button>
      </div>

      <!-- Estado de carga -->
      <div v-if="isLoading" class="py-10 flex flex-col items-center justify-center gap-3 text-sm text-brand-muted">
        <BaseLoader color="#28A2A5" size="md" />
        <p>Cargando tus movimientos...</p>
      </div>

      <!-- Estado vacío -->
      <div
        v-else-if="transactions.length === 0"
        class="py-10 px-4 text-center rounded-2xl bg-brand-surface/70 space-y-2"
      >
        <p class="font-bold text-brand-primary">
          Todavía no hay movimientos en esta categoría
        </p>
        <p class="text-xs text-brand-muted max-w-sm mx-auto">
          Presentá tu pase QR en el mostrador en tu próxima compra de venta libre o sumá puntos respondiendo trivias de bienestar.
        </p>
      </div>

      <!-- Lista de transacciones -->
      <ul v-else class="divide-y divide-gray-100">
        <li
          v-for="tx in transactions"
          :key="tx.id"
          class="py-3.5 flex items-start justify-between gap-4"
        >
          <div class="flex items-start gap-3 min-w-0">
            <span
              class="w-10 h-10 rounded-xl inline-flex items-center justify-center shrink-0"
              :class="TYPE_META[tx.type]?.badgeClass || 'bg-gray-100 text-brand-muted'"
            >
              <component
                :is="TYPE_META[tx.type]?.icon || Coins"
                class="w-5 h-5"
                aria-hidden="true"
              />
            </span>

            <div class="min-w-0 space-y-0.5">
              <p class="text-sm font-bold text-brand-primary leading-snug">
                {{ tx.description }}
              </p>
              <p class="text-xs text-brand-muted">
                {{ TYPE_META[tx.type]?.label || 'Movimiento' }} · {{ formatDate(tx.created_at) }}
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

      <!-- Paginación -->
      <nav
        v-if="!isLoading && pagination.last_page > 1"
        aria-label="Paginación del historial"
        class="pt-3 border-t border-gray-100 flex items-center justify-between gap-4"
      >
        <button
          type="button"
          class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl border-2 border-gray-200 text-xs font-bold text-brand-primary hover:bg-gray-50 disabled:opacity-45 disabled:cursor-not-allowed cursor-pointer"
          :disabled="pagination.current_page <= 1"
          @click="handlePageChange(pagination.current_page - 1)"
        >
          <ChevronLeft class="w-4 h-4" aria-hidden="true" />
          <span>Anterior</span>
        </button>

        <span class="text-xs font-semibold text-brand-muted">
          Página {{ pagination.current_page }} de {{ pagination.last_page }}
        </span>

        <button
          type="button"
          class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl border-2 border-gray-200 text-xs font-bold text-brand-primary hover:bg-gray-50 disabled:opacity-45 disabled:cursor-not-allowed cursor-pointer"
          :disabled="pagination.current_page >= pagination.last_page"
          @click="handlePageChange(pagination.current_page + 1)"
        >
          <span>Siguiente</span>
          <ChevronRight class="w-4 h-4" aria-hidden="true" />
        </button>
      </nav>
    </section>
  </main>
</template>