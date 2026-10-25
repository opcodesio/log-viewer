<template>
  <Menu as="div" class="relative">
    <MenuButton as="button" id="auto-reload-button" class="menu-button flex items-center" :class="{ 'text-brand-500 dark:text-brand-400': isEnabled }"
                :title="isEnabled ? `Auto-reload every ${currentLabel}` : 'Auto-reload'">
      <span class="sr-only">Auto-reload dropdown</span>
      <span v-if="isEnabled" class="text-xs font-semibold mr-0.5">{{ currentLabel }}</span>
      <ChevronDownIcon class="w-4 h-4" />
    </MenuButton>

    <transition
      leave-active-class="transition ease-in duration-100"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-90"
      enter-active-class="transition ease-out duration-100"
      enter-from-class="opacity-0 scale-90"
      enter-to-class="opacity-100 scale-100"
    >
      <MenuItems as="div" style="min-width: 180px;" class="dropdown">
        <div class="py-2">
          <div class="label">Auto-reload</div>

          <MenuItem v-for="option in intervalOptions" :key="option.value" v-slot="{ active }">
            <button :class="[active ? 'active' : '']" @click="logViewerStore.autoReloadInterval = option.value">
              <Checkmark :checked="logViewerStore.autoReloadInterval === option.value" />
              <span class="ml-3">{{ option.label }}</span>
            </button>
          </MenuItem>
        </div>
      </MenuItems>
    </transition>
  </Menu>
</template>

<script setup>
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { ChevronDownIcon } from '@heroicons/vue/24/outline';
import { computed, onBeforeUnmount, watch } from 'vue';
import { useLogViewerStore } from '../stores/logViewer.js';
import Checkmark from './Checkmark.vue';

const logViewerStore = useLogViewerStore();

const intervalOptions = [
  { value: 0, label: 'Off' },
  { value: 5, label: '5s' },
  { value: 10, label: '10s' },
  { value: 30, label: '30s' },
  { value: 60, label: '1m' },
  { value: 300, label: '5m' },
  { value: 900, label: '15m' },
];

const isEnabled = computed(() => logViewerStore.autoReloadInterval > 0);

const currentLabel = computed(() => {
  return intervalOptions.find(option => option.value === logViewerStore.autoReloadInterval)?.label
    || `${logViewerStore.autoReloadInterval}s`;
});

const reload = () => {
  // skip this tick if the tab is in the background, a request is still running,
  // or the user has expanded a log entry (new logs would shift the open entry).
  if (document.hidden || logViewerStore.loading || logViewerStore.hasMoreResults || logViewerStore.stacksOpen.length > 0) {
    return;
  }

  logViewerStore.loadLogs({ silently: true });
};

let timer = null;

watch(
  () => logViewerStore.autoReloadInterval,
  (interval) => {
    clearInterval(timer);
    timer = interval > 0 ? setInterval(reload, interval * 1000) : null;
  },
  { immediate: true }
);

onBeforeUnmount(() => clearInterval(timer));
</script>
