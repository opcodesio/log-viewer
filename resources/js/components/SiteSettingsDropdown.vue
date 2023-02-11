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

        <MenuItem @click.stop.prevent="clearCacheAll">
          <button>
            <CircleStackIcon v-show="!clearingCache" class="w-4 h-4 mr-1.5" />
            <SpinnerIcon v-show="clearingCache" class="w-4 h-4 mr-1.5 spin" />
            <span v-show="!cacheRecentlyCleared && !clearingCache">Clear indices for all files</span>
            <span v-show="!cacheRecentlyCleared && clearingCache">Please wait...</span>
            <span v-show="cacheRecentlyCleared" class="text-emerald-500">File indices cleared</span>
          </button>
        </MenuItem>

        <MenuItem @click.stop.prevent="copyUrlToClipboard">
          <button>
            <ShareIcon class="w-4 h-4" />
            <span v-show="!copied">Share this page</span>
            <span v-show="copied" class="text-emerald-500">Link copied!</span>
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
          <a href="https://www.github.com/opcodesio/log-viewer" target="_blank">
            <QuestionMarkCircleIcon class="w-4 h-4" />
            Help
          </a>
        </MenuItem>
      </div>
    </MenuItems>
  </Menu>

</template>

<script setup>
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue';
import { QuestionMarkCircleIcon, CircleStackIcon, Cog8ToothIcon, ShareIcon, ComputerDesktopIcon, SunIcon, MoonIcon } from '@heroicons/vue/24/outline';
import { useLogViewerStore, Theme } from '../stores/logViewer.js';
import { ref } from 'vue';
import Checkmark from './Checkmark.vue';
import SpinnerIcon from './SpinnerIcon.vue';
import { copyToClipboard } from '../helpers.js';

const logViewerStore = useLogViewerStore();

const copied = ref(false);
const copyUrlToClipboard = () => {
  copyToClipboard(window.location.href);
  copied.value = true;
  setTimeout(() => copied.value = false, 2000);
};

const clearingCache = ref(false);
const cacheRecentlyCleared = ref(false);
const clearCacheAll = () => {
  //

  cacheRecentlyCleared.value = true;
  setTimeout(() => cacheRecentlyCleared.value = false, 2000);
}
</script>
