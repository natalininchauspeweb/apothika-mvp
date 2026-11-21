<script setup>
defineProps({
  color: {
    type: String,
    default: '#28A2A5',
  },
  size: {
    type: String,
    default: 'md', // 'sm' | 'md' | 'lg'
  },
});

const SIZE_CLASSES = {
  sm: 'w-5 h-5 border-2',
  md: 'w-8 h-8 border-3',
  lg: 'w-12 h-12 border-4',
};
</script>

<template>
  <span
    role="status"
    class="apothika-spinner inline-block rounded-full align-middle"
    :class="SIZE_CLASSES[size] || SIZE_CLASSES.md"
  >
    <span class="sr-only">Cargando...</span>
  </span>
</template>

<style scoped>
.apothika-spinner {
  border-color: color-mix(in srgb, v-bind(color) 25%, transparent);
  border-top-color: v-bind(color);
  animation: apothika-spin 0.75s linear infinite;
}

@keyframes apothika-spin {
  to {
    transform: rotate(360deg);
  }
}
</style>