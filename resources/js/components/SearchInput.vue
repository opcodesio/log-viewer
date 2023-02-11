<template>
  <div class="flex-1">
    <div class="search" :class="{'has-error': searchStore.error}">
      <div class="prefix-icon">
        <label for="query" class="sr-only">Search</label>
        <MagnifyingGlassIcon v-show="!searchStore.searching" class="h-4 w-4" />
        <!-- TODO: replace with a spinner component -->
        <svg v-show="searchStore.searching" xmlns="http://www.w3.org/2000/svg"
             class="spin w-5 h-5 -mr-1" viewBox="0 0 24 24" fill="currentColor">
          <use href="#icon-spinner" />
        </svg>
      </div>
      <div class="relative flex-1 m-1">
        <input v-model.lazy="searchStore.query" name="query" id="query" type="text" />
        <div v-show="String(searchStore.query).trim() !== ''" class="clear-search">
          <button @click="clearQuery">
            <XMarkIcon class="h-4 w-4" />
          </button>
        </div>
      </div>
      <div class="submit-search">
        <button v-if="logViewerStore.hasMoreResults" disabled="disabled">
          <span>Searching {{ selectedFile ? selectedFile.name : 'all files' }}...</span>
        </button>
        <button v-else @click="submitSearch">
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
import { useFileViewerStore } from '../stores/fileViewer.js';
import { computed } from 'vue';

const searchStore = useSearchStore();
const logViewerStore = useLogViewerStore();
const fileViewerStore = useFileViewerStore();

const selectedFile = computed(() => fileViewerStore.selectedFile);

const clearQuery = () => {
  searchStore.query = '';
}

const submitSearch = () => {
  //
}
</script>
