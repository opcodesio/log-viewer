<template>
  <div class="checkmark w-[18px] h-[18px] bg-gray-50 dark:bg-gray-800 rounded border dark:border-gray-600 inline-flex items-center justify-center">
    <CheckIcon v-if="checked" width="18" height="18" class="w-full h-full" />
  </div>
</template>

<script setup>
import { CheckIcon } from '@heroicons/vue/20/solid'

defineProps({
  checked: {
    type: Boolean,
    required: true,
  },
})
</script>
