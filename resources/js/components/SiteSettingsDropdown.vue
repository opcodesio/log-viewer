<template>
  <Menu as="div" class="relative">
    <MenuButton>
      <button type="button" class="menu-button">
        <Cog8ToothIcon class="w-5 h-5" />
      </button>
    </MenuButton>

    <MenuItems as="div" style="min-width: 250px;" class="dropdown">
      <div class="py-2">
        <div class="label">Settings</div>

        <MenuItem @click.stop.prevent="logViewerStore.shorterStackTraces = !logViewerStore.shorterStackTraces">
          <button>
            <Checkmark :checked="logViewerStore.shorterStackTraces" />
            <span class="ml-3">Shorter stack traces</span>
          </button>
        </MenuItem>

        <div class="divider"></div>
        <div class="label">Actions</div>

        <MenuItem @click.stop.prevent="fileStore.clearCacheForAllFiles">
          <button>
            <CircleStackIcon v-show="!fileStore.clearingCache['*']" class="w-4 h-4 mr-1.5" />
            <SpinnerIcon v-show="fileStore.clearingCache['*']" class="w-4 h-4 mr-1.5" />
            <span v-show="!fileStore.cacheRecentlyCleared['*'] && !fileStore.clearingCache['*']">Clear indices for all files</span>
            <span v-show="!fileStore.cacheRecentlyCleared['*'] && fileStore.clearingCache['*']">Please wait...</span>
            <span v-show="fileStore.cacheRecentlyCleared['*']" class="text-brand-500">File indices cleared</span>
          </button>
        </MenuItem>

        <MenuItem @click.stop.prevent="copyUrlToClipboard">
          <button>
            <ShareIcon class="w-4 h-4" />
            <span v-show="!copied">Share this page</span>
            <span v-show="copied" class="text-brand-500">Link copied!</span>
          </button>
        </MenuItem>

        <div class="divider"></div>

        <MenuItem @click.stop.prevent="logViewerStore.toggleTheme()">
          <button>
            <ComputerDesktopIcon v-show="logViewerStore.theme === Theme.System" class="w-4 h-4" />
            <SunIcon v-show="logViewerStore.theme === Theme.Light" class="w-4 h-4" />
            <MoonIcon v-show="logViewerStore.theme === Theme.Dark" class="w-4 h-4" />
            <span>Theme: <span v-html="logViewerStore.theme" class="font-semibold"></span></span>
          </button>
        </MenuItem>

        <MenuItem>
          <a href="https://log-viewer.opcodes.io/docs" target="_blank">
            <QuestionMarkCircleIcon class="w-4 h-4" />
            Documentation
          </a>
        </MenuItem>

        <MenuItem>
          <a href="https://www.github.com/opcodesio/log-viewer" target="_blank">
            <QuestionMarkCircleIcon class="w-4 h-4" />
            Help
          </a>
        </MenuItem>

        <div class="divider"></div>

        <MenuItem>
          <a href="https://www.buymeacoffee.com/arunas" target="_blank">
            <div class="w-4 h-4 mr-3 flex flex-col items-center">
              <bmc-icon class="h-4 w-auto" />
            </div>
            <strong class="text-brand-500">Show your support</strong>
            <ArrowTopRightOnSquareIcon class="ml-2 w-4 h-4 opacity-75" />
          </a>
        </MenuItem>
      </div>
    </MenuItems>
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
