<script setup>
import { computed } from 'vue';
import { CheckCircle2, AlertCircle, AlertTriangle, Info, X } from 'lucide-vue-next';

const props = defineProps({
  content: {
    type: Object,
    required: true,
  },
});

defineEmits(['close']);

// Clases completas de Tailwind (sin concatenación dinámica)
const VARIANT_STYLES = {
  success: 'bg-game-success/12 border-game-success text-green-900',
  error: 'bg-game-error/12 border-game-error text-red-900',
  warning: 'bg-game-warning/20 border-game-warning text-amber-950',
  info: 'bg-brand-secondary/12 border-brand-secondary text-brand-primary',
};

const ICON_MAP = {
  success: CheckCircle2,
  error: AlertCircle,
  warning: AlertTriangle,
  info: Info,
};

const boxClasses = computed(() => VARIANT_STYLES[props.content.type] || VARIANT_STYLES.info);
const IconComponent = computed(() => ICON_MAP[props.content.type] || Info);
</script>

<template>
  <div
    role="alert"
    aria-live="polite"
    class="flex items-start gap-3 p-4 mb-4 rounded-2xl border-2 font-medium text-sm"
    :class="boxClasses"
  >
    <component :is="IconComponent" class="w-5 h-5 shrink-0 mt-0.5" aria-hidden="true" />
    <p class="flex-1 leading-snug">{{ content.message }}</p>
    <button
      v-if="content.closable"
      type="button"
      aria-label="Cerrar notificación"
      class="p-1 rounded-lg hover:bg-black/10 transition cursor-pointer shrink-0"
      @click="$emit('close')"
    >
      <X class="w-4 h-4" aria-hidden="true" />
    </button>
  </div>
</template>