<template>
  <button class="log-link group" @click.stop.prevent="copy" title="Copy link to this log entry">
    <span v-show="!copied" class="group-hover:underline">{{ Number(log.index).toLocaleString() }}</span>
    <LinkIcon v-show="!copied" class="opacity-0 group-hover:opacity-75" />
    <span v-show="copied" class="text-green-600 dark:text-green-500">Copied!</span>
  </button>
</template>

<script setup>
import { ref } from 'vue';
import { copyToClipboard } from '../helpers.js';
import { LinkIcon } from '@heroicons/vue/24/outline';

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
