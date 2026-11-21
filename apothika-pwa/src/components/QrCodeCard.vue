<script setup>
import { ref, watch } from 'vue';
import QRCode from 'qrcode';

const props = defineProps({
  value: {
    type: String,
    required: true,
  },
  label: {
    type: String,
    default: 'Código QR identificador',
  },
  size: {
    type: Number,
    default: 200,
  },
});

const qrDataUrl = ref('');
const hasError = ref(false);

async function generateQr(text) {
  if (!text) {
    qrDataUrl.value = '';
    return;
  }

  hasError.value = false;

  try {
    // Alto contraste fijo (#111827 sobre #FFFFFF) para lectura confiable en cámara de mostrador
    qrDataUrl.value = await QRCode.toDataURL(text, {
      width: props.size,
      margin: 2,
      errorCorrectionLevel: 'M',
      color: {
        dark: '#111827',
        light: '#FFFFFF',
      },
    });
  } catch (error) {
    console.error('[QR] Error al generar código QR:', error);
    hasError.value = true;
  }
}

watch(
  () => [props.value, props.size],
  ([newValue]) => {
    generateQr(newValue);
  },
  { immediate: true }
);
</script>

<template>
  <div class="inline-flex flex-col items-center gap-2">
    <div class="bg-white p-3 rounded-2xl border-2 border-gray-200 shadow-xs">
      <img
        v-if="qrDataUrl"
        :src="qrDataUrl"
        :alt="`${label}: ${value}`"
        :width="size"
        :height="size"
        class="block rounded-lg"
      />
      <div
        v-else-if="hasError"
        class="flex items-center justify-center text-xs text-game-error text-center p-4"
        :style="{ width: `${size}px`, height: `${size}px` }"
      >
        No pudimos generar el QR. Mostrá el código de abajo en caja.
      </div>
      <div
        v-else
        class="bg-gray-100 animate-pulse rounded-lg"
        :style="{ width: `${size}px`, height: `${size}px` }"
        aria-hidden="true"
      />
    </div>

    <p class="font-mono text-xs sm:text-sm font-bold tracking-wider text-brand-primary bg-brand-surface px-3 py-1 rounded-lg select-all">
      {{ value }}
    </p>
  </div>
</template>