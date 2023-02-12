<template>
  <div class="flex-1">
    <div class="search" :class="{'has-error': searchStore.error}">
      <div class="prefix-icon">
        <label for="query" class="sr-only">Search</label>
        <MagnifyingGlassIcon v-show="!searchStore.searching" class="h-4 w-4" />
        <SpinnerIcon v-show="searchStore.searching" class="spin w-5 h-5 -mr-1" />
      </div>
      <div class="relative flex-1 m-1">
        <input v-model="searchStore.tempQuery" name="query" id="query" type="text"
               @keydown.enter="searchStore.submitQuery"
        />
        <div v-show="searchStore.hasQuery" class="clear-search">
          <button @click="searchStore.clearQuery">
            <XMarkIcon class="h-4 w-4" />
          </button>
        </div>
      </div>
      <div class="submit-search">
        <button v-if="logViewerStore.hasMoreResults" disabled="disabled">
          <span>Searching {{ selectedFile ? selectedFile.name : 'all files' }}...</span>
        </button>
        <button v-else @click="searchStore.submitQuery">
          <span>Search {{ selectedFile ? 'in "' + selectedFile.name + '"' : 'all files' }}</span>
          <ArrowRightIcon class="h-4 w-4" />
        </button>
      </div>
    </div>
    <div class="relative h-0 w-full overflow-visible">
      <div class="search-progress-bar" v-show="searchStore.searching"
           :style="{ width: searchStore.percentScanned + '%' }"></div>
    </div>
    <p class="mt-1 text-red-600 text-xs" v-show="searchStore.error" v-html="searchStore.error"></p>
  </div>
</template>

<script setup>
import { useSearchStore } from '../stores/search.js';
import { MagnifyingGlassIcon, XMarkIcon, ArrowRightIcon } from '@heroicons/vue/24/outline';
import { useLogViewerStore } from '../stores/logViewer.js';
import { useFileStore } from '../stores/files.js';
import { computed } from 'vue';
import SpinnerIcon from './SpinnerIcon.vue';

const searchStore = useSearchStore();
const logViewerStore = useLogViewerStore();
const fileStore = useFileStore();

const selectedFile = computed(() => fileStore.selectedFile);
</script>
