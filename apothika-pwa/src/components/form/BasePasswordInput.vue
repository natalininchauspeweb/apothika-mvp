<script setup>
import { ref } from 'vue';
import { Eye, EyeOff } from 'lucide-vue-next';

defineOptions({
  inheritAttrs: false,
});

const model = defineModel();

defineProps({
  hasError: {
    type: Boolean,
    default: false,
  },
});

const showPassword = ref(false);
</script>

<template>
  <div class="relative">
    <input
      v-bind="$attrs"
      v-model="model"
      :type="showPassword ? 'text' : 'password'"
      :aria-invalid="hasError ? 'true' : undefined"
      class="w-full rounded-xl border-2 bg-white pl-4 pr-11 py-2.5 text-sm text-brand-text placeholder:text-gray-400 transition focus:outline-none disabled:bg-gray-100 disabled:cursor-not-allowed"
      :class="hasError ? 'border-game-error focus:border-game-error' : 'border-gray-200 focus:border-brand-secondary'"
    />

    <button
      type="button"
      :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
      :aria-pressed="showPassword"
      class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-brand-primary transition cursor-pointer rounded-r-xl"
      @click="showPassword = !showPassword"
    >
      <EyeOff v-if="showPassword" class="w-4 h-4" aria-hidden="true" />
      <Eye v-else class="w-4 h-4" aria-hidden="true" />
    </button>
  </div>
</template>