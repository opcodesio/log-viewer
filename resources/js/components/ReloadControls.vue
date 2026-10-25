<template>
  <div class="flex items-center gap-1">
    <button @click="logViewerStore.loadLogs()" id="reload-logs-button" :title="reloadButtonTitle" class="menu-button">
      <svg v-if="isEnabled" viewBox="0 0 36 36" class="auto-reload-ring" :class="{ 'paused': isPaused }" aria-hidden="true">
        <circle cx="18" cy="18" r="16" pathLength="100" class="track" />
        <circle v-if="!spinning" :key="cycle" cx="18" cy="18" r="16" pathLength="100" class="progress"
                :style="{ animationDuration: `${logViewerStore.autoReloadInterval}s` }" />
      </svg>
      <ArrowPathIcon class="w-5 h-5" :class="{ 'animate-spin': spinning }" />
    </button>

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
  </div>
</template>

<script setup>
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { ArrowPathIcon } from '@heroicons/vue/24/solid';
import { ChevronDownIcon } from '@heroicons/vue/24/outline';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
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

// an expanded log entry pauses auto-reload, because new logs would shift the open entry.
const isPaused = computed(() => logViewerStore.stacksOpen.length > 0);

const currentLabel = computed(() => {
  return intervalOptions.find(option => option.value === logViewerStore.autoReloadInterval)?.label
    || `${logViewerStore.autoReloadInterval}s`;
});

const reloadButtonTitle = computed(() => {
  if (!isEnabled.value) return 'Reload current results';
  if (isPaused.value) return 'Auto-reload paused while a log entry is expanded';
  return `Reload current results (auto-reloading every ${currentLabel.value})`;
});

// keep the icon spinning for at least one full rotation, so quick reloads are still noticeable.
const MIN_SPIN_MS = 1000;
const spinning = ref(false);
let spinStartedAt = 0;
let spinTimeout = null;

watch(() => logViewerStore.reloading, (reloading) => {
  clearTimeout(spinTimeout);

  if (reloading) {
    if (!spinning.value) spinStartedAt = Date.now();
    spinning.value = true;
  } else {
    spinTimeout = setTimeout(() => spinning.value = false, Math.max(0, MIN_SPIN_MS - (Date.now() - spinStartedAt)));
  }
});

// incremented on every new countdown, which re-mounts the progress ring and restarts its animation.
const cycle = ref(0);
let reloadTimeout = null;

const startCountdown = () => {
  clearTimeout(reloadTimeout);
  reloadTimeout = null;
  cycle.value++;

  if (isEnabled.value && !isPaused.value) {
    reloadTimeout = setTimeout(reload, logViewerStore.autoReloadInterval * 1000);
  }
};

const reload = () => {
  // skip reloading while the tab is in the background or a request is still running.
  if (!document.hidden && !logViewerStore.reloading && !logViewerStore.hasMoreResults) {
    logViewerStore.loadLogs({ silently: true, highlightNew: true });
  }

  startCountdown();
};

watch([() => logViewerStore.autoReloadInterval, isPaused], startCountdown, { immediate: true });

// any finished reload, including a manual one, starts a fresh countdown.
watch(spinning, (isSpinning) => {
  if (!isSpinning) startCountdown();
});

onBeforeUnmount(() => {
  clearTimeout(reloadTimeout);
  clearTimeout(spinTimeout);
});
</script>
