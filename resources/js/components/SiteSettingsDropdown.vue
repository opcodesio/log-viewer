<template>
  <Menu class="relative">
    <MenuButton>
      <button type="button" class="menu-button">
        <Cog8ToothIcon class="w-5 h-5" />
      </button>
    </MenuButton>

    <MenuItems as="div" style="min-width: 250px;" class="dropdown">
      <div class="py-2">
        <div class="label">Settings</div>

        <MenuItem>
          <button @click="logViewerStore.shorterStackTraces = !logViewerStore.shorterStackTraces">
            <Checkmark :checked="logViewerStore.shorterStackTraces" />
            <span class="ml-3">Shorter stack traces</span>
          </button>
        </MenuItem>

        <div class="divider"></div>
        <div class="label">Actions</div>

        <MenuItem>
          <button @click="clearCacheAll">
            <CircleStackIcon v-show="!clearingCache" class="w-4 h-4 mr-1.5" />
            <!-- TODO: replace with a spinner component -->
            <svg v-show="clearingCache" xmlns="http://www.w3.org/2000/svg" class="spin" fill="currentColor">
              <use href="#icon-spinner" />
            </svg>
            <span v-show="!cacheRecentlyCleared && !clearingCache">Clear indices for all files</span>
            <span v-show="!cacheRecentlyCleared && clearingCache">Please wait...</span>
            <span v-show="cacheRecentlyCleared" class="text-emerald-500">File indices cleared</span>
          </button>
        </MenuItem>

        <MenuItem>
          <button @click="copyUrlToClipboard">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <use href="#icon-share" />
            </svg>
            <span v-show="!copied">Share this page</span>
            <span v-show="copied" class="text-emerald-500">Link copied!</span>
          </button>
        </MenuItem>

        <div class="divider"></div>

        <MenuItem>
          <button @click="logViewerStore.toggleTheme()">
            <svg v-show="logViewerStore.theme === 'System'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                 fill="currentColor">
              <use href="#icon-theme-auto" />
            </svg>
            <svg v-show="logViewerStore.theme === 'Light'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                 fill="currentColor">
              <use href="#icon-theme-light" />
            </svg>
            <svg v-show="logViewerStore.theme === 'Dark'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                 fill="currentColor">
              <use href="#icon-theme-dark" />
            </svg>
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
import { QuestionMarkCircleIcon, CircleStackIcon, Cog8ToothIcon } from '@heroicons/vue/24/outline';
import { useLogViewerStore } from '../stores/logViewer.js';
import { ref } from 'vue';
import Checkmark from './Checkmark.vue';

const logViewerStore = useLogViewerStore();

const copyToClipboard = (str) => {
  const el = document.createElement('textarea');
  el.value = str;
  el.setAttribute('readonly', '');
  el.style.position = 'absolute';
  el.style.left = '-9999px';
  document.body.appendChild(el);
  const selected =
    document.getSelection().rangeCount > 0
      ? document.getSelection().getRangeAt(0)
      : false;
  el.select();
  document.execCommand('copy');
  document.body.removeChild(el);
  if (selected) {
    document.getSelection().removeAllRanges();
    document.getSelection().addRange(selected);
  }
};

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
