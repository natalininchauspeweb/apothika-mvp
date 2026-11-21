<script setup>
const model = defineModel();

defineProps({
  hasError: {
    type: Boolean,
    default: false,
  },
});
</script>

<template>
  <input
    v-model="model"
    type="text"
    :aria-invalid="hasError ? 'true' : undefined"
    class="w-full rounded-xl border-2 bg-white px-4 py-2.5 text-sm text-brand-text placeholder:text-gray-400 transition focus:outline-none disabled:bg-gray-100 disabled:cursor-not-allowed"
    :class="hasError ? 'border-game-error focus:border-game-error' : 'border-gray-200 focus:border-brand-secondary'"
  />
</template>