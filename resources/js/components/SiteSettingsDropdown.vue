<template>
  <Menu as="div" class="relative">
    <MenuButton as="button" class="menu-button">
      <span class="sr-only">Settings dropdown</span>
      <Cog8ToothIcon class="w-5 h-5" />
    </MenuButton>

    <transition
      leave-active-class="transition ease-in duration-100"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-90"
      enter-active-class="transition ease-out duration-100"
      enter-from-class="opacity-0 scale-90"
      enter-to-class="opacity-100 scale-100"
    >
      <MenuItems as="div" style="min-width: 250px;" class="dropdown">
        <div class="py-2">
          <div class="label">Settings</div>

          <MenuItem v-slot="{ active }">
            <button :class="[active ? 'active' : '']" @click.stop.prevent="logViewerStore.shorterStackTraces = !logViewerStore.shorterStackTraces">
              <Checkmark :checked="logViewerStore.shorterStackTraces" />
              <span class="ml-3">Shorter stack traces</span>
            </button>
          </MenuItem>

          <div class="divider"></div>
          <div class="label">Actions</div>

          <MenuItem @click.stop.prevent="fileStore.clearCacheForAllFiles" v-slot="{ active }">
            <button :class="[active ? 'active' : '']">
              <CircleStackIcon v-show="!fileStore.clearingCache['*']" class="w-4 h-4 mr-1.5" />
              <SpinnerIcon v-show="fileStore.clearingCache['*']" class="w-4 h-4 mr-1.5" />
              <span v-show="!fileStore.cacheRecentlyCleared['*'] && !fileStore.clearingCache['*']">Clear indices for all files</span>
              <span v-show="!fileStore.cacheRecentlyCleared['*'] && fileStore.clearingCache['*']">Please wait...</span>
              <span v-show="fileStore.cacheRecentlyCleared['*']" class="text-brand-500">File indices cleared</span>
            </button>
          </MenuItem>

          <MenuItem @click.stop.prevent="copyUrlToClipboard" v-slot="{ active }">
            <button :class="[active ? 'active' : '']">
              <ShareIcon class="w-4 h-4" />
              <span v-show="!copied">Share this page</span>
              <span v-show="copied" class="text-brand-500">Link copied!</span>
            </button>
          </MenuItem>

          <div class="divider"></div>

          <MenuItem @click.stop.prevent="logViewerStore.toggleTheme()" v-slot="{ active }">
            <button :class="[active ? 'active' : '']">
              <ComputerDesktopIcon v-show="logViewerStore.theme === Theme.System" class="w-4 h-4" />
              <SunIcon v-show="logViewerStore.theme === Theme.Light" class="w-4 h-4" />
              <MoonIcon v-show="logViewerStore.theme === Theme.Dark" class="w-4 h-4" />
              <span>Theme: <span v-html="logViewerStore.theme" class="font-semibold"></span></span>
            </button>
          </MenuItem>

          <MenuItem v-slot="{ active }">
            <button @click="logViewerStore.helpSlideOverOpen = true" :class="[active ? 'active' : '']">
              <QuestionMarkCircleIcon class="w-4 h-4" />
              Keyboard Shortcuts
            </button>
          </MenuItem>

          <MenuItem v-slot="{ active }">
            <a href="https://log-viewer.opcodes.io/docs" target="_blank" :class="[active ? 'active' : '']">
              <QuestionMarkCircleIcon class="w-4 h-4" />
              Documentation
            </a>
          </MenuItem>

          <MenuItem v-slot="{ active }">
            <a href="https://www.github.com/opcodesio/log-viewer" target="_blank" :class="[active ? 'active' : '']">
              <QuestionMarkCircleIcon class="w-4 h-4" />
              Help
            </a>
          </MenuItem>

          <div class="divider"></div>

          <MenuItem v-slot="{ active }">
            <a href="https://www.buymeacoffee.com/arunas" target="_blank" :class="[active ? 'active' : '']">
              <div class="w-4 h-4 mr-3 flex flex-col items-center">
                <bmc-icon class="h-4 w-auto" />
              </div>
              <strong :class="[active ? 'text-white' : 'text-brand-500']">Show your support</strong>
              <ArrowTopRightOnSquareIcon class="ml-2 w-4 h-4 opacity-75" />
            </a>
          </MenuItem>
        </div>
      </MenuItems>
    </transition>
  </Menu>

</template>

<script setup>
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import {
  ArrowTopRightOnSquareIcon,
  CircleStackIcon,
  Cog8ToothIcon,
  ComputerDesktopIcon,
  MoonIcon,
  QuestionMarkCircleIcon,
  ShareIcon,
  SunIcon,
} from '@heroicons/vue/24/outline';
import { Theme, useLogViewerStore } from '../stores/logViewer.js';
import { ref, watch } from 'vue';
import Checkmark from './Checkmark.vue';
import SpinnerIcon from './SpinnerIcon.vue';
import { copyToClipboard } from '../helpers.js';
import BmcIcon from './BmcIcon.vue';
import { useFileStore } from '../stores/files.js';

const logViewerStore = useLogViewerStore();
const fileStore = useFileStore();

const copied = ref(false);
const copyUrlToClipboard = () => {
  copyToClipboard(window.location.href);
  copied.value = true;
  setTimeout(() => copied.value = false, 2000);
};

watch(
  () => logViewerStore.shorterStackTraces,
  () => logViewerStore.loadLogs()
);
</script>
