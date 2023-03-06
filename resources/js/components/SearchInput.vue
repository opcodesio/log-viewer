<template>
  <div class="flex-1">
    <div class="search" :class="{'has-error': logViewerStore.error}">
      <div class="prefix-icon">
        <label for="query" class="sr-only">Search</label>
        <MagnifyingGlassIcon v-show="!logViewerStore.hasMoreResults" class="h-4 w-4" />
        <SpinnerIcon v-show="logViewerStore.hasMoreResults" class="w-4 h-4" />
      </div>
      <div class="relative flex-1 m-1">
        <input v-model="tempQuery" name="query" id="query" type="text"
               @keydown.enter="submitQuery"
               @keydown.esc="(event) => event.target.blur()"
        />
        <div v-show="searchStore.hasQuery" class="clear-search">
          <button @click="clearQuery">
            <XMarkIcon class="h-4 w-4" />
          </button>
        </div>
      </div>
      <div class="submit-search">
        <button v-if="logViewerStore.hasMoreResults" disabled="disabled">
          <span>Searching<span class="hidden xl:inline ml-1"> {{ selectedFile ? selectedFile.name : 'all files' }}</span>...</span>
        </button>
        <button v-else @click="submitQuery" id="query-submit">
          <span>Search<span class="hidden xl:inline ml-1"> {{ selectedFile ? 'in "' + selectedFile.name + '"' : 'all files' }}</span></span>
          <ArrowRightIcon class="h-4 w-4" />
        </button>
      </div>
    </div>
    <div class="relative h-0 w-full overflow-visible">
      <div class="search-progress-bar" v-show="logViewerStore.hasMoreResults"
           :style="{ width: logViewerStore.percentScanned + '%' }"></div>
    </div>
    <p class="mt-1 text-red-600 text-xs" v-show="logViewerStore.error" v-html="logViewerStore.error"></p>
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
const submitQuery = () => {
  replaceQuery(router, 'query', tempQuery.value === '' ? null : tempQuery.value);
  document.getElementById('query-submit')?.focus();
}
const clearQuery = () => {
  tempQuery.value = '';
  submitQuery();
}

watch(
  () => route.query.query,
  (query) => tempQuery.value = query || '',
)
</script>
