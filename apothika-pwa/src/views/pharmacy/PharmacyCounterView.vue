<script setup>
import { computed, inject, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import {
  Award,
  Camera,
  CheckCircle2,
  Coins,
  QrCode,
  Search,
  ShieldAlert,
  Ticket,
  TrendingUp,
  UserCheck,
} from 'lucide-vue-next';
import {
  awardOtcPurchasePoints,
  lookupPharmacyPatient,
  validatePharmacyCoupon,
} from '../../services/pharmacy';
import { globalFeedbackKey } from '../../symbols/inject';
import QrScannerModal from '../../components/QrScannerModal.vue';
import BaseLabel from '../../components/form/BaseLabel.vue';
import BaseInput from '../../components/form/BaseInput.vue';
import LoaderButton from '../../components/form/LoaderButton.vue';

const route = useRoute();
const feedback = inject(globalFeedbackKey);

const activeTab = ref(route.query.tab === 'coupon' ? 'coupon' : 'points'); // 'points' | 'coupon'
const scannerTarget = ref(null); // null | 'patient' | 'coupon'

// --- Estado pestaña 1: Acreditación de puntos OTC ---
const searchQuery = ref('');
const isSearching = ref(false);
const foundPatient = ref(null);
const conversionRate = ref({
  points_per_1000_ars: 10,
  multiplier_percent: 100,
  multiplier_label: '1x',
});

const awardMode = ref('amount'); // 'amount' | 'manual'
const amountArs = ref('');
const manualPoints = ref('');
const otcConfirmed = ref(false);
const notes = ref('');
const isAwarding = ref(false);
const awardErrors = ref({});
const lastAwardReceipt = ref(null);

// --- Estado pestaña 2: Validación de cupón ---
const couponCode = ref('');
const isValidatingCoupon = ref(false);
const validatedCoupon = ref(null);

watch(
  () => route.query.tab,
  (tab) => {
    if (tab === 'coupon' || tab === 'points') {
      activeTab.value = tab;
    }
  }
);

// Réplica exacta de PointsService::calculatePointsFromOtcAmount (aritmética entera)
const previewPoints = computed(() => {
  if (awardMode.value === 'manual') {
    const parsed = parseInt(manualPoints.value, 10);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
  }

  const parsedAmount = parseInt(String(amountArs.value).replace(/[^0-9]/g, ''), 10);
  if (!Number.isFinite(parsedAmount) || parsedAmount <= 0) return 0;

  const rate = conversionRate.value.points_per_1000_ars || 10;
  const multiplier = conversionRate.value.multiplier_percent || 100;

  return Math.floor((parsedAmount * rate * multiplier) / 100000);
});

async function handleLookupPatient(explicitQuery = null) {
  const query = (explicitQuery ?? searchQuery.value).trim();
  if (!query || isSearching.value) return;

  searchQuery.value = query;
  isSearching.value = true;
  foundPatient.value = null;
  lastAwardReceipt.value = null;
  awardErrors.value = {};
  feedback.clear();

  try {
    const data = await lookupPharmacyPatient(query);
    foundPatient.value = data.patient;
    conversionRate.value = data.conversion_rate || conversionRate.value;
    amountArs.value = '';
    manualPoints.value = '';
    notes.value = '';
    otcConfirmed.value = false;
  } catch (error) {
    feedback.show({
      type: 'error',
      message: error.message || 'No encontramos ningún paciente con ese dato.',
    });
  } finally {
    isSearching.value = false;
  }
}

async function handleAwardPoints() {
  if (!foundPatient.value || isAwarding.value) return;

  isAwarding.value = true;
  awardErrors.value = {};
  feedback.clear();

  const payload = {
    identifier: foundPatient.value.qr_code || foundPatient.value.dni || foundPatient.value.email,
    otc_confirmed: otcConfirmed.value,
    notes: notes.value.trim() || null,
  };

  if (awardMode.value === 'amount') {
    const cleanAmount = parseInt(String(amountArs.value).replace(/[^0-9]/g, ''), 10);
    payload.amount_ars = Number.isFinite(cleanAmount) ? cleanAmount : null;
  } else {
    const cleanPoints = parseInt(manualPoints.value, 10);
    payload.points = Number.isFinite(cleanPoints) ? cleanPoints : null;
  }

  try {
    const result = await awardOtcPurchasePoints(payload);

    // Actualizamos los datos del paciente (incluyendo posible subida de nivel)
    foundPatient.value = result.patient;
    if (result.patient?.tier) {
      conversionRate.value = {
        ...conversionRate.value,
        multiplier_percent: result.patient.tier.multiplier_percent,
        multiplier_label: result.patient.tier.multiplier_label,
      };
    }

    lastAwardReceipt.value = {
      message: result.message,
      points: result.transaction?.points || previewPoints.value,
      newBalance: result.patient.points_balance,
      description: result.transaction?.description,
    };

    amountArs.value = '';
    manualPoints.value = '';
    notes.value = '';
    otcConfirmed.value = false;

    feedback.show({
      type: 'success',
      message: result.message,
    });
  } catch (error) {
    awardErrors.value = error.errors || {};
    feedback.show({
      type: 'error',
      message: error.message || 'No pudimos acreditar los puntos. Revisá los campos.',
    });
  } finally {
    isAwarding.value = false;
  }
}

async function handleValidateCoupon(explicitCode = null) {
  const code = (explicitCode ?? couponCode.value).trim().toUpperCase();
  if (!code || isValidatingCoupon.value) return;

  couponCode.value = code;
  isValidatingCoupon.value = true;
  validatedCoupon.value = null;
  feedback.clear();

  try {
    const result = await validatePharmacyCoupon(code);
    validatedCoupon.value = result.coupon;
    couponCode.value = '';

    feedback.show({
      type: 'success',
      message: result.message,
      duration: 7000,
    });
  } catch (error) {
    feedback.show({
      type: 'error',
      message: error.message || 'No pudimos validar ese código de cupón.',
    });
  } finally {
    isValidatingCoupon.value = false;
  }
}

function handleQrScanned(decodedText) {
  const target = scannerTarget.value;
  scannerTarget.value = null;

  if (target === 'patient') {
    handleLookupPatient(decodedText);
  } else if (target === 'coupon') {
    handleValidateCoupon(decodedText);
  }
}

function formatNumber(value) {
  return new Intl.NumberFormat('es-AR').format(value || 0);
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
</script>

<template>
  <main class="max-w-3xl mx-auto w-full px-4 py-6 space-y-6">
    <!-- Encabezado y selector de operación -->
    <section class="card-duo p-6 border-apothika-emerald/25 space-y-4">
      <div class="space-y-1">
        <p class="text-xs font-bold uppercase tracking-wider text-apothika-emerald inline-flex items-center gap-1.5">
          <QrCode class="w-4 h-4" aria-hidden="true" />
          <span>Operación en caja</span>
        </p>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-apothika-emerald">
          Mostrador
        </h1>
        <p class="text-sm text-brand-muted">
          Escaneá códigos QR con la cámara o ingresá DNI/código manualmente para operar en segundos.
        </p>
      </div>

      <div
        role="tablist"
        aria-label="Modo de operación en mostrador"
        class="grid grid-cols-2 gap-2 p-1 rounded-2xl bg-gray-100"
      >
        <button
          type="button"
          role="tab"
          :aria-selected="activeTab === 'points'"
          class="py-2.5 px-3 rounded-xl text-xs sm:text-sm font-bold inline-flex items-center justify-center gap-2 transition cursor-pointer"
          :class="
            activeTab === 'points'
              ? 'bg-apothika-emerald text-white shadow-xs'
              : 'text-apothika-charcoal hover:bg-white/70'
          "
          @click="activeTab = 'points'"
        >
          <Coins class="w-4 h-4" aria-hidden="true" />
          <span>Acreditar OTC</span>
        </button>

        <button
          type="button"
          role="tab"
          :aria-selected="activeTab === 'coupon'"
          class="py-2.5 px-3 rounded-xl text-xs sm:text-sm font-bold inline-flex items-center justify-center gap-2 transition cursor-pointer"
          :class="
            activeTab === 'coupon'
              ? 'bg-apothika-emerald text-white shadow-xs'
              : 'text-apothika-charcoal hover:bg-white/70'
          "
          @click="activeTab = 'coupon'"
        >
          <Ticket class="w-4 h-4" aria-hidden="true" />
          <span>Validar cupón</span>
        </button>
      </div>
    </section>

    <!-- ================================================================
         PESTAÑA 1: BUSCAR PACIENTE Y ACREDITAR PUNTOS POR COMPRA OTC
         ================================================================ -->
    <section v-if="activeTab === 'points'" aria-labelledby="counter-points-heading" class="space-y-5">
      <!-- Paso 1: Identificar paciente -->
      <div class="card-duo p-6 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <h2 id="counter-points-heading" class="text-lg font-extrabold text-apothika-charcoal">
            1. Identificar al paciente
          </h2>

          <button
            type="button"
            class="btn-duo bg-apothika-emerald text-white text-xs font-bold px-4 py-2 rounded-xl inline-flex items-center gap-2 cursor-pointer"
            @click="scannerTarget = 'patient'"
          >
            <Camera class="w-4 h-4" aria-hidden="true" />
            <span>Escanear QR</span>
          </button>
        </div>

        <form class="flex flex-col sm:flex-row gap-2.5" @submit.prevent="handleLookupPatient()">
          <div class="flex-1">
            <BaseLabel for="patient-lookup-input" class="sr-only">
              Código QR, DNI o correo electrónico del paciente
            </BaseLabel>
            <BaseInput
              id="patient-lookup-input"
              v-model="searchQuery"
              type="text"
              placeholder="Ej: 38456123, APO-DAL-38456123 o email"
              required
            />
          </div>

          <LoaderButton :loading="isSearching" variant="apothika">
            <Search class="w-4 h-4" aria-hidden="true" />
            <span>Buscar</span>
          </LoaderButton>
        </form>
      </div>

      <!-- Paso 2: Paciente identificado + Formulario OTC -->
      <div v-if="foundPatient" class="card-duo p-6 space-y-6 border-apothika-emerald/35">
        <!-- Ficha del paciente + Nivel -->
        <div class="rounded-2xl bg-apothika-offwhite border-2 border-apothika-emerald/15 p-4 space-y-3">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="space-y-0.5">
              <p class="text-xs font-bold uppercase tracking-wider text-apothika-emerald inline-flex items-center gap-1.5">
                <UserCheck class="w-4 h-4" aria-hidden="true" />
                <span>Paciente identificado</span>
              </p>
              <h3 class="text-lg font-extrabold text-apothika-charcoal">
                {{ foundPatient.name }}
              </h3>
              <p class="text-xs text-brand-muted">
                DNI {{ foundPatient.dni }} · {{ foundPatient.email }}
              </p>
            </div>

            <div class="text-right">
              <p class="text-[11px] font-bold uppercase tracking-wider text-brand-muted">
                Saldo actual
              </p>
              <p class="text-xl font-heading font-extrabold text-apothika-emerald">
                {{ formatNumber(foundPatient.points_balance) }} pts
              </p>
            </div>
          </div>

          <div
            v-if="foundPatient.tier"
            class="flex flex-wrap items-center justify-between gap-2 pt-2.5 border-t border-gray-200 text-xs"
          >
            <span class="inline-flex items-center gap-1.5 font-extrabold text-apothika-emerald">
              <Award class="w-4 h-4" aria-hidden="true" />
              <span>Nivel {{ foundPatient.tier.level }} · {{ foundPatient.tier.name }}</span>
            </span>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-apothika-mint/25 text-apothika-emerald font-extrabold">
              <TrendingUp class="w-3.5 h-3.5" aria-hidden="true" />
              <span>Multiplicador {{ foundPatient.tier.multiplier_label }} OTC</span>
            </span>
          </div>
        </div>

        <!-- Formulario de carga OTC -->
        <form class="space-y-4" novalidate @submit.prevent="handleAwardPoints">
          <div class="flex items-center justify-between gap-2">
            <h3 class="text-base font-extrabold text-apothika-charcoal">
              2. Acreditar compra de venta libre (OTC)
            </h3>

            <div class="inline-flex rounded-xl bg-gray-100 p-1 text-xs font-bold">
              <button
                type="button"
                class="px-3 py-1 rounded-lg transition cursor-pointer"
                :class="awardMode === 'amount' ? 'bg-white text-apothika-emerald shadow-xs' : 'text-brand-muted'"
                @click="awardMode = 'amount'"
              >
                Por monto ARS
              </button>
              <button
                type="button"
                class="px-3 py-1 rounded-lg transition cursor-pointer"
                :class="awardMode === 'manual' ? 'bg-white text-apothika-emerald shadow-xs' : 'text-brand-muted'"
                @click="awardMode = 'manual'"
              >
                Puntos manuales
              </button>
            </div>
          </div>

          <div v-if="awardMode === 'amount'">
            <BaseLabel for="otc-amount-ars">Monto de venta libre en pesos (ARS)</BaseLabel>
            <BaseInput
              id="otc-amount-ars"
              v-model="amountArs"
              type="number"
              inputmode="numeric"
              min="100"
              step="1"
              placeholder="Ej: 15500"
              :has-error="Boolean(awardErrors.amount_ars)"
              required
            />
            <p v-if="awardErrors.amount_ars" class="mt-1 text-xs font-medium text-game-error">
              {{ awardErrors.amount_ars[0] }}
            </p>
          </div>

          <div v-else>
            <BaseLabel for="otc-manual-points">Puntos a acreditar</BaseLabel>
            <BaseInput
              id="otc-manual-points"
              v-model="manualPoints"
              type="number"
              inputmode="numeric"
              min="1"
              step="1"
              placeholder="Ej: 150"
              :has-error="Boolean(awardErrors.points)"
              required
            />
            <p v-if="awardErrors.points" class="mt-1 text-xs font-medium text-game-error">
              {{ awardErrors.points[0] }}
            </p>
          </div>

          <!-- Previsualización en vivo de puntos calculados -->
          <div class="rounded-2xl bg-apothika-emerald text-white p-4 flex flex-wrap items-center justify-between gap-3">
            <div class="space-y-0.5">
              <p class="text-[11px] font-bold uppercase tracking-wider text-white/75">
                Puntos a acreditar
              </p>
              <p class="text-2xl font-heading font-extrabold inline-flex items-center gap-2">
                <Coins class="w-6 h-6 text-apothika-mint" aria-hidden="true" />
                <span>+{{ formatNumber(previewPoints) }} pts</span>
              </p>
            </div>
            <p v-if="awardMode === 'amount'" class="text-xs text-white/85">
              Base {{ conversionRate.points_per_1000_ars }} pts/$1.000 × {{ conversionRate.multiplier_label }}
            </p>
          </div>

          <div>
            <BaseLabel for="otc-notes">Detalle opcional (ej: Dermocosmética, Protector solar)</BaseLabel>
            <BaseInput
              id="otc-notes"
              v-model="notes"
              type="text"
              maxlength="120"
              placeholder="Opcional: breve referencia del ticket OTC"
            />
          </div>

          <!-- Declaración normativa obligatoria ANMAT -->
          <div
            class="rounded-2xl border-2 p-4 transition"
            :class="
              awardErrors.otc_confirmed
                ? 'border-game-error bg-game-error/8'
                : 'border-amber-300 bg-amber-50/70'
            "
          >
            <label for="otc-confirmed-check" class="flex items-start gap-3 cursor-pointer">
              <input
                id="otc-confirmed-check"
                v-model="otcConfirmed"
                type="checkbox"
                class="mt-1 w-4 h-4 accent-apothika-emerald rounded cursor-pointer shrink-0"
              />
              <span class="text-xs leading-relaxed text-apothika-charcoal">
                <strong class="inline-flex items-center gap-1 font-extrabold">
                  <ShieldAlert class="w-3.5 h-3.5 text-amber-700" aria-hidden="true" />
                  Confirmación normativa (ANMAT):
                </strong>
                declaro que el importe corresponde exclusivamente a productos de <strong>venta libre (OTC), dermocosmética, perfumería o cuidado personal</strong>, sin incluir medicamentos bajo receta.
              </span>
            </label>
            <p v-if="awardErrors.otc_confirmed" class="mt-2 text-xs font-bold text-game-error">
              {{ awardErrors.otc_confirmed[0] }}
            </p>
          </div>

          <LoaderButton
            :loading="isAwarding"
            :disabled="previewPoints < 1 || !otcConfirmed"
            variant="apothika"
            full-width
          >
            <CheckCircle2 class="w-4 h-4" aria-hidden="true" />
            <span>Acreditar +{{ formatNumber(previewPoints) }} puntos</span>
          </LoaderButton>
        </form>

        <!-- Comprobante inmediato tras acreditar -->
        <div
          v-if="lastAwardReceipt"
          role="status"
          aria-live="polite"
          class="rounded-2xl border-2 border-game-success bg-game-success/10 p-4 space-y-1 text-sm text-green-950"
        >
          <p class="font-extrabold inline-flex items-center gap-2">
            <CheckCircle2 class="w-5 h-5 text-game-success shrink-0" aria-hidden="true" />
            <span>Acreditación registrada: +{{ formatNumber(lastAwardReceipt.points) }} pts</span>
          </p>
          <p class="text-xs leading-relaxed">{{ lastAwardReceipt.description }}</p>
          <p class="text-xs font-bold">
            Nuevo saldo del paciente: {{ formatNumber(lastAwardReceipt.newBalance) }} pts
          </p>
        </div>
      </div>
    </section>

    <!-- ================================================================
         PESTAÑA 2: VALIDAR CUPÓN CANJEADO EN CAJA
         ================================================================ -->
    <section v-else aria-labelledby="counter-coupon-heading" class="space-y-5">
      <div class="card-duo p-6 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <h2 id="counter-coupon-heading" class="text-lg font-extrabold text-apothika-charcoal">
            Validar cupón de beneficio
          </h2>

          <button
            type="button"
            class="btn-duo bg-apothika-emerald text-white text-xs font-bold px-4 py-2 rounded-xl inline-flex items-center gap-2 cursor-pointer"
            @click="scannerTarget = 'coupon'"
          >
            <Camera class="w-4 h-4" aria-hidden="true" />
            <span>Escanear QR del cupón</span>
          </button>
        </div>

        <p class="text-sm text-brand-muted">
          Al validar el cupón, el sistema verifica que pertenezca a tu farmacia y lo marca automáticamente como <strong>utilizado</strong> para evitar usos duplicados.
        </p>

        <form class="flex flex-col sm:flex-row gap-2.5" @submit.prevent="handleValidateCoupon()">
          <div class="flex-1">
            <BaseLabel for="coupon-code-input" class="sr-only">
              Código alfanumérico del cupón
            </BaseLabel>
            <BaseInput
              id="coupon-code-input"
              v-model="couponCode"
              type="text"
              placeholder="Ej: CUP-A7K9P2"
              class="uppercase font-mono"
              required
            />
          </div>

          <LoaderButton :loading="isValidatingCoupon" variant="apothika">
            <CheckCircle2 class="w-4 h-4" aria-hidden="true" />
            <span>Validar en caja</span>
          </LoaderButton>
        </form>
      </div>

      <!-- Comprobante de cupón validado -->
      <article
        v-if="validatedCoupon"
        role="status"
        aria-live="polite"
        class="card-duo p-6 border-game-success bg-game-success/8 space-y-3"
      >
        <div class="flex items-center gap-2 text-game-success font-extrabold text-base">
          <CheckCircle2 class="w-6 h-6 shrink-0" aria-hidden="true" />
          <h3>Cupón aplicado correctamente en caja</h3>
        </div>

        <div class="rounded-2xl bg-white border-2 border-gray-200 p-4 space-y-2 text-sm">
          <p class="font-extrabold text-apothika-charcoal text-base">
            {{ validatedCoupon.reward?.title }}
          </p>
          <p v-if="validatedCoupon.reward?.description" class="text-xs text-brand-muted">
            {{ validatedCoupon.reward.description }}
          </p>
          <div class="pt-2 border-t border-gray-100 grid grid-cols-1 sm:grid-cols-2 gap-1.5 text-xs text-brand-muted">
            <p>
              Código: <strong class="font-mono text-apothika-charcoal">{{ validatedCoupon.redeem_code }}</strong>
            </p>
            <p>
              Paciente: <strong class="text-apothika-charcoal">{{ validatedCoupon.patient?.name }}</strong>
              <span v-if="validatedCoupon.patient?.dni"> (DNI {{ validatedCoupon.patient.dni }})</span>
            </p>
            <p>
              Validado: <strong class="text-apothika-charcoal">{{ formatDate(validatedCoupon.used_at) }}</strong>
            </p>
          </div>
        </div>
      </article>
    </section>

    <!-- Modal de cámara QR reutilizable -->
    <QrScannerModal
      v-if="scannerTarget"
      :title="scannerTarget === 'patient' ? 'Escanear pase del paciente' : 'Escanear cupón de beneficio'"
      :hint="
        scannerTarget === 'patient'
          ? 'Apuntá la cámara al código QR de la pantalla de Inicio del paciente.'
          : 'Apuntá la cámara al código QR dentro de Mis Cupones del paciente.'
      "
      @scanned="handleQrScanned"
      @close="scannerTarget = null"
    />
  </main>
</template>