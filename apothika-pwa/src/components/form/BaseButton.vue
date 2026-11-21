<script setup>
import { computed } from 'vue';

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
  },
  type: {
    type: String,
    default: 'submit',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  fullWidth: {
    type: Boolean,
    default: false,
  },
});

// Mapa estático con clases completas para que Tailwind compile todas las variantes
const VARIANT_CLASSES = {
  primary: 'btn-duo bg-brand-secondary text-white',
  brandDark: 'btn-duo bg-brand-primary text-white',
  apothika: 'btn-duo bg-apothika-emerald text-white',
  coral: 'btn-duo bg-apothika-coral text-white',
  success: 'btn-duo bg-game-success text-white',
  danger: 'btn-duo bg-game-error text-white',
  gold: 'btn-duo bg-game-gold text-brand-primary',
  outline:
    'border-2 border-gray-300 border-b-4 bg-white text-brand-text hover:bg-gray-50 active:translate-y-0.5',
};

const variantClasses = computed(() => VARIANT_CLASSES[props.variant] || VARIANT_CLASSES.primary);
</script>

<template>
  <button
    :type="type"
    :disabled="disabled"
    class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl font-bold text-sm tracking-wide cursor-pointer disabled:opacity-55 disabled:cursor-not-allowed transition"
    :class="[variantClasses, { 'w-full': fullWidth }]"
  >
    <slot>Enviar</slot>
  </button>
</template>