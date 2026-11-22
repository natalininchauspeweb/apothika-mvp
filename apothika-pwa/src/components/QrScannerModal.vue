<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { Html5Qrcode } from 'html5-qrcode';
import { AlertCircle, Camera, X } from 'lucide-vue-next';

const props = defineProps({
  title: {
    type: String,
    default: 'Escanear código QR',
  },
  hint: {
    type: String,
    default: 'Apuntá la cámara hacia el código QR en la pantalla del paciente.',
  },
});

const emit = defineEmits(['scanned', 'close']);

const scannerElementId = `apothika-qr-reader-${Math.random().toString(36).slice(2, 8)}`;
const isStarting = ref(true);
const cameraError = ref('');

let html5QrCode = null;
let isRunning = false;
let hasDecoded = false;

async function stopScanner() {
  if (!html5QrCode) return;

  try {
    if (isRunning) {
      await html5QrCode.stop();
      isRunning = false;
    }
    html5QrCode.clear();
  } catch {
    // Evitamos errores silenciosos si la cámara ya había sido liberada por el navegador
  } finally {
    html5QrCode = null;
  }
}

async function handleClose() {
  await stopScanner();
  emit('close');
}

function handleKeydown(event) {
  if (event.key === 'Escape') {
    handleClose();
  }
}

onMounted(async () => {
  window.addEventListener('keydown', handleKeydown);

  try {
    html5QrCode = new Html5Qrcode(scannerElementId);
    await html5QrCode.start(
      { facingMode: 'environment' },
      {
        fps: 10,
        qrbox: { width: 220, height: 220 },
      },
      async (decodedText) => {
        if (hasDecoded) return;
        hasDecoded = true;
        await stopScanner();
        emit('scanned', decodedText.trim());
      },
      () => {
        // Ignoramos los frames intermedios sin QR detectado
      }
    );
    isRunning = true;
  } catch {
    cameraError.value =
      'No pudimos acceder a la cámara. Verificá los permisos del navegador o ingresá el código/DNI de forma manual.';
  } finally {
    isStarting.value = false;
  }
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown);
  stopScanner();
});
</script>

<template>
  <div
    class="fixed inset-0 z-50 bg-black/70 backdrop-blur-xs flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="qr-scanner-title"
    @click.self="handleClose"
  >
    <div class="card-duo max-w-md w-full p-5 sm:p-6 space-y-4">
      <div class="flex items-start justify-between gap-3">
        <div class="space-y-1">
          <div class="inline-flex items-center gap-2 text-apothika-emerald font-bold text-xs uppercase tracking-wider">
            <Camera class="w-4 h-4" aria-hidden="true" />
            <span>Cámara activa</span>
          </div>
          <h2 id="qr-scanner-title" class="text-lg font-extrabold text-apothika-charcoal">
            {{ title }}
          </h2>
        </div>

        <button
          type="button"
          aria-label="Cerrar escáner de cámara"
          class="p-2 rounded-xl border-2 border-gray-200 text-gray-600 hover:bg-gray-100 transition cursor-pointer"
          @click="handleClose"
        >
          <X class="w-4 h-4" aria-hidden="true" />
        </button>
      </div>

      <p class="text-xs text-brand-muted leading-relaxed">
        {{ hint }}
      </p>

      <!-- Contenedor de video de html5-qrcode -->
      <div class="rounded-2xl overflow-hidden bg-apothika-charcoal min-h-64 flex items-center justify-center relative">
        <div :id="scannerElementId" class="w-full" />

        <p
          v-if="isStarting && !cameraError"
          class="absolute inset-0 flex items-center justify-center text-xs font-semibold text-white/90 bg-apothika-charcoal/85 px-4 text-center"
        >
          Iniciando cámara...
        </p>
      </div>

      <div
        v-if="cameraError"
        role="alert"
        class="rounded-xl border-2 border-game-error/40 bg-game-error/10 p-3 flex items-start gap-2.5 text-xs text-red-900"
      >
        <AlertCircle class="w-4 h-4 shrink-0 mt-0.5 text-game-error" aria-hidden="true" />
        <p class="leading-relaxed">{{ cameraError }}</p>
      </div>

      <button
        type="button"
        class="w-full py-2.5 px-4 rounded-xl border-2 border-gray-300 bg-white hover:bg-gray-50 text-xs font-bold text-apothika-charcoal transition cursor-pointer"
        @click="handleClose"
      >
        Cancelar y escribir manualmente
      </button>
    </div>
  </div>
</template>