<template>
  <button class="log-link group"
          @click.stop="copy"
          @keydown="handleLogLinkKeyboardNavigation"
          title="Copy link to this log entry"
  >
    <span class="sr-only">Log index {{ log.index }}. Click the button to copy link to this log entry.</span>
    <span v-show="!copied" class="hidden md:inline group-hover:underline">{{ Number(log.index).toLocaleString() }}</span>
    <LinkIcon v-show="!copied" class="md:opacity-75 group-hover:opacity-100" />
    <HandThumbUpIcon v-show="copied" class="text-green-600 dark:text-green-500 md:hidden" />
    <span v-show="copied" class="text-green-600 dark:text-green-500 hidden md:inline">Copied!</span>
  </button>
</template>

<script setup>
import { ref } from 'vue';
import { copyToClipboard } from '../helpers.js';
import { LinkIcon } from '@heroicons/vue/24/outline';
import { HandThumbUpIcon } from '@heroicons/vue/24/solid';
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
