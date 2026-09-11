<template>
  <button class="log-text-copy group/copy"
          @click.stop="copy"
          :title="copied ? 'Copied!' : 'Copy the full log entry to clipboard'"
  >
    <span class="sr-only">Copy the full log entry, including stack trace, to the clipboard.</span>
    <CheckIcon v-if="copied" class="text-green-600 dark:text-green-500" />
    <Square2StackIcon v-else class="md:opacity-75 group-hover/copy:opacity-100" />
  </button>
</template>

<script setup>
import { ref } from 'vue';
import { copyToClipboard, logToText } from '../helpers.js';
import { Square2StackIcon } from '@heroicons/vue/24/outline';
import { CheckIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
  log: {
    type: Object,
    required: true,
  },
})

const copied = ref(false);

const copy = () => {
  copyToClipboard(logToText(props.log));
  copied.value = true;
  setTimeout(() => copied.value = false, 1000);
}
</script>
