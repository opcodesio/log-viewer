<template>
  <div class="flex-1">
    <div class="search" :class="{'has-error': searchStore.error}">
      <div class="prefix-icon">
        <label for="query" class="sr-only">Search</label>
        <MagnifyingGlassIcon v-show="!searchStore.searching" class="h-4 w-4" />
        <SpinnerIcon v-show="searchStore.searching" class="w-5 h-5 -mr-1" />
      </div>
      <div class="relative flex-1 m-1">
        <input v-model="tempQuery" name="query" id="query" type="text"
               @keydown.enter="submitQuery"
        />
        <div v-show="searchStore.hasQuery" class="clear-search">
          <button @click="clearQuery">
            <XMarkIcon class="h-4 w-4" />
          </button>
        </div>
      </div>
      <div class="submit-search">
        <button v-if="logViewerStore.hasMoreResults" disabled="disabled">
          <span>Searching {{ selectedFile ? selectedFile.name : 'all files' }}...</span>
        </button>
        <button v-else @click="submitQuery">
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
import { ArrowRightIcon, MagnifyingGlassIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { useLogViewerStore } from '../stores/logViewer.js';
import { computed, ref, watch } from 'vue';
import SpinnerIcon from './SpinnerIcon.vue';
import { replaceQuery } from '../helpers.js';
import { useRoute, useRouter } from 'vue-router';

const searchStore = useSearchStore();
const logViewerStore = useLogViewerStore();
const router = useRouter();
const route = useRoute();

const selectedFile = computed(() => logViewerStore.selectedFile);

const tempQuery = ref(route.query.query || '');
const submitQuery = () => replaceQuery(router, 'query', tempQuery.value === '' ? null : tempQuery.value);
const clearQuery = () => {
  tempQuery.value = '';
  submitQuery();
}

watch(
  () => route.query.query,
  (query) => tempQuery.value = query || '',
)
</script>
