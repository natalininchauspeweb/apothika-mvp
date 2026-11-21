<script setup>
import BaseButton from './BaseButton.vue';
import BaseLoader from '../BaseLoader.vue';

defineProps({
  loading: {
    type: Boolean,
    default: false,
  },
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
</script>

<template>
  <BaseButton
    :variant="variant"
    :type="type"
    :disabled="loading || disabled"
    :full-width="fullWidth"
  >
    <slot v-if="!loading" />
    <BaseLoader v-else color="#ffffff" size="sm" />
  </BaseButton>
</template>