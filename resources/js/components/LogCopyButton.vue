<template>
  <button class="log-link group/link"
          @click.stop="copy"
          @keydown="handleLogLinkKeyboardNavigation"
          :title="copied ? 'Copied!' : 'Copy link to this log entry'"
  >
    <span class="sr-only">Copy a link to log entry {{ log.index }} to the clipboard.</span>
    <CheckIcon v-if="copied" class="text-green-600 dark:text-green-500" />
    <LinkIcon v-else class="md:opacity-75 group-hover/link:opacity-100" />
  </button>
</template>

<script setup>
import { ref } from 'vue';
import { copyToClipboard } from '../helpers.js';
import { LinkIcon } from '@heroicons/vue/24/outline';
import { CheckIcon } from '@heroicons/vue/24/solid';
import { handleLogLinkKeyboardNavigation } from '../keyboardNavigation';

const props = defineProps({
  log: {
    type: Object,
    required: true,
  },
})

const copied = ref(false);

const copy = () => {
  copyToClipboard(props.log.url);
  copied.value = true;
  setTimeout(() => copied.value = false, 1000);
}
</script>
