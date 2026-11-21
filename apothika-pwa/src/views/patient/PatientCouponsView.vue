<script setup>
import { computed, inject, onMounted, ref } from 'vue';
import { CheckCircle2, Clock, Gift, Ticket } from 'lucide-vue-next';
import { fetchPatientCoupons } from '../../services/patient';
import { globalFeedbackKey } from '../../symbols/inject';
import QrCodeCard from '../../components/QrCodeCard.vue';
import BaseLoader from '../../components/BaseLoader.vue';

const feedback = inject(globalFeedbackKey);

const coupons = ref([]);
const isLoading = ref(true);
const activeTab = ref('available'); // 'available' | 'used'

const availableCoupons = computed(() =>
  coupons.value.filter((c) => c.status === 'available')
);
const usedCoupons = computed(() =>
  coupons.value.filter((c) => c.status === 'used')
);

const visibleCoupons = computed(() =>
  activeTab.value === 'available' ? availableCoupons.value : usedCoupons.value
);

async function loadCoupons() {
  isLoading.value = true;
  try {
    const data = await fetchPatientCoupons();
    coupons.value = data.coupons || [];
  } catch (error) {
    feedback.show({
      type: 'error',
      message: error.message || 'No pudimos cargar tus cupones.',
    });
  } finally {
    isLoading.value = false;
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
  loadCoupons();
});
</script>

<template>
  <main class="max-w-3xl mx-auto w-full px-4 py-6 space-y-6">
    <!-- Cabecera y selector de estado -->
    <section class="card-duo p-6 space-y-4">
      <div class="space-y-1">
        <p class="text-xs font-bold uppercase tracking-wider text-brand-secondary inline-flex items-center gap-1.5">
          <Ticket class="w-4 h-4" aria-hidden="true" />
          <span>Billetera digital</span>
        </p>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-primary">
          Mis cupones
        </h1>
        <p class="text-sm text-brand-muted">
          Mostrá el código QR o dictá el código alfanumérico en el mostrador para aplicar tu beneficio.
        </p>
      </div>

      <div role="tablist" aria-label="Filtrar cupones por estado" class="grid grid-cols-2 gap-2 p-1 rounded-2xl bg-brand-surface">
        <button
          type="button"
          role="tab"
          :aria-selected="activeTab === 'available'"
          class="py-2.5 px-4 rounded-xl text-xs sm:text-sm font-bold transition cursor-pointer"
          :class="
            activeTab === 'available'
              ? 'bg-white text-brand-primary shadow-xs'
              : 'text-brand-muted hover:text-brand-primary'
          "
          @click="activeTab = 'available'"
        >
          Disponibles ({{ availableCoupons.length }})
        </button>

        <button
          type="button"
          role="tab"
          :aria-selected="activeTab === 'used'"
          class="py-2.5 px-4 rounded-xl text-xs sm:text-sm font-bold transition cursor-pointer"
          :class="
            activeTab === 'used'
              ? 'bg-white text-brand-primary shadow-xs'
              : 'text-brand-muted hover:text-brand-primary'
          "
          @click="activeTab = 'used'"
        >
          Usados ({{ usedCoupons.length }})
        </button>
      </div>
    </section>

    <div v-if="isLoading" class="card-duo p-10 flex flex-col items-center gap-3 text-sm text-brand-muted">
      <BaseLoader color="#28A2A5" size="md" />
      <p>Cargando tu billetera de cupones...</p>
    </div>

    <section v-else-if="visibleCoupons.length === 0" class="card-duo p-8 text-center space-y-4">
      <div class="w-14 h-14 mx-auto rounded-2xl bg-brand-surface text-brand-secondary inline-flex items-center justify-center">
        <Gift class="w-7 h-7" aria-hidden="true" />
      </div>
      <div class="space-y-1">
        <h2 class="text-lg font-extrabold text-brand-primary">
          {{
            activeTab === 'available'
              ? 'Todavía no tenés cupones disponibles'
              : 'Todavía no utilizaste ningún cupón'
          }}
        </h2>
        <p class="text-sm text-brand-muted max-w-sm mx-auto">
          {{
            activeTab === 'available'
              ? 'Explorá el catálogo de beneficios y canjeá tus puntos cuando quieras.'
              : 'Cuando el mostrador valide tus cupones, aparecerán acá como comprobante.'
          }}
        </p>
      </div>
      <RouterLink
        v-if="activeTab === 'available'"
        :to="{ name: 'patient.rewards' }"
        class="btn-duo inline-flex items-center gap-2 bg-brand-secondary text-white font-bold text-sm px-5 py-2.5 rounded-xl"
      >
        <Gift class="w-4 h-4" aria-hidden="true" />
        <span>Ir al catálogo</span>
      </RouterLink>
    </section>

    <section v-else aria-labelledby="coupons-list-heading" class="space-y-4">
      <h2 id="coupons-list-heading" class="sr-only">
        {{ activeTab === 'available' ? 'Cupones disponibles para presentar en caja' : 'Cupones ya utilizados' }}
      </h2>

      <article
        v-for="coupon in visibleCoupons"
        :key="coupon.id"
        class="card-duo p-5 sm:p-6 flex flex-col sm:flex-row items-center gap-6"
        :class="coupon.status === 'used' ? 'opacity-80' : ''"
      >
        <div v-if="coupon.status === 'available'" class="shrink-0">
          <QrCodeCard
            :value="coupon.redeem_code"
            :label="`Cupón ${coupon.reward?.title || 'de beneficio'}`"
            :size="150"
          />
        </div>

        <div class="flex-1 w-full space-y-3 text-center sm:text-left">
          <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
            <span
              class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold"
              :class="
                coupon.status === 'available'
                  ? 'bg-game-success/15 text-green-900'
                  : 'bg-gray-200 text-gray-700'
              "
            >
              <Clock v-if="coupon.status === 'available'" class="w-3.5 h-3.5" aria-hidden="true" />
              <CheckCircle2 v-else class="w-3.5 h-3.5" aria-hidden="true" />
              <span>{{ coupon.status === 'available' ? 'Listo para usar en caja' : 'Cupón utilizado' }}</span>
            </span>

            <span v-if="coupon.reward" class="text-xs font-bold text-brand-muted">
              Canjeado por {{ coupon.reward.points_cost }} pts
            </span>
          </div>

          <div class="space-y-1">
            <h3 class="text-lg font-extrabold text-brand-primary leading-snug">
              {{ coupon.reward?.title || 'Beneficio canjeado' }}
            </h3>
            <p v-if="coupon.reward?.description" class="text-xs text-brand-muted leading-relaxed">
              {{ coupon.reward.description }}
            </p>
          </div>

          <div class="pt-1 text-xs text-brand-muted space-y-0.5">
            <p v-if="coupon.status === 'used'">
              Código: <strong class="font-mono text-brand-primary">{{ coupon.redeem_code }}</strong> · Validado el {{ formatDate(coupon.used_at) }}
            </p>
            <p v-else>
              Generado el {{ formatDate(coupon.created_at) }}
            </p>
          </div>
        </div>
      </article>
    </section>
  </main>
</template>