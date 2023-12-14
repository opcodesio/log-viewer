<template>
  <div class="h-full w-full py-5 log-list">
    <div class="flex flex-col h-full w-full md:mx-3 mb-4">
      <div class="md:px-4 mb-4 flex flex-col-reverse lg:flex-row items-start">
        <div class="flex items-center mr-5 mt-3 md:mt-0" v-if="showLevelsDropdown">
          <LevelButtons />
        </div>
        <div class="w-full lg:w-auto flex-1 flex justify-end min-h-[38px]">
          <SearchInput />
          <div class="hidden md:block ml-5">
            <button @click="logViewerStore.loadLogs()" id="reload-logs-button" title="Reload current results" class="menu-button">
              <ArrowPathIcon class="w-5 h-5" />
            </button>
          </div>
          <div class="hidden md:block">
            <SiteSettingsDropdown class="ml-2" id="desktop-site-settings" />
          </div>
          <div class="md:hidden">
            <button type="button" class="menu-button">
              <Bars3Icon class="w-5 h-5 ml-2" @click="fileStore.toggleSidebar" />
            </button>
          </div>
        </div>
      </div>

      <div v-if="!inlinePaginationSettingsIntoTableHeader" class="flex justify-end md:px-4 my-1 mx-2">
        <pagination-options />
      </div>

      <div v-if="displayLogs" class="relative overflow-hidden h-full text-sm">
        <!-- pagination settings -->
        <pagination-options
          v-if="inlinePaginationSettingsIntoTableHeader"
          class="mx-2 mt-1 mb-2 text-right lg:mx-0 lg:mt-0 lg:mb-0 lg:absolute lg:top-2 lg:right-6 z-20"
        />

        <div class="log-item-container h-full overflow-y-auto md:px-4" @scroll="(event) => logViewerStore.onScroll(event)">
          <div class="inline-block min-w-full max-w-full align-middle">
            <base-log-table />
          </div>
        </div>

        <!-- loading state for logs -->
        <div class="absolute inset-0 top-9 md:px-4 z-20" v-show="logViewerStore.loading && (!logViewerStore.isMobile || !fileStore.sidebarOpen)">
          <div
            class="rounded-md bg-white text-gray-800 dark:bg-gray-700 dark:text-gray-200 opacity-90 w-full h-full flex items-center justify-center">
            <SpinnerIcon class="w-14 h-14" />
          </div>
        </div>
      </div>
      <div v-else class="flex h-full items-center justify-center text-gray-600 dark:text-gray-400">
        <span v-if="logViewerStore.hasMoreResults">Searching...</span>
        <span v-else>Select a file or start searching...</span>
      </div>

      <div v-if="displayLogs && paginationStore.hasPages" class="md:px-4">
        <div class="hidden lg:block">
          <Pagination :loading="logViewerStore.loading" />
        </div>
        <div class="lg:hidden">
          <Pagination :loading="logViewerStore.loading" :short="true" />
        </div>
      </div>
    </div>
  </div>

</template>

<script setup>
import {computed, ref, watch} from 'vue';
import { useRouter } from 'vue-router';
import { ArrowPathIcon, Bars3Icon } from '@heroicons/vue/24/solid';
import { useLogViewerStore } from '../stores/logViewer.js';
import { useSearchStore } from '../stores/search.js';
import { useFileStore } from '../stores/files.js';
import { usePaginationStore } from '../stores/pagination.js';
import Pagination from './Pagination.vue';
import LevelButtons from './LevelButtons.vue';
import SearchInput from './SearchInput.vue';
import SiteSettingsDropdown from './SiteSettingsDropdown.vue';
import SpinnerIcon from './SpinnerIcon.vue';
import BaseLogTable from './BaseLogTable.vue';
import PaginationOptions from './PaginationOptions.vue';

const router = useRouter();
const fileStore = useFileStore();
const logViewerStore = useLogViewerStore();
const searchStore = useSearchStore();
const paginationStore = usePaginationStore();

const showLevelsDropdown = computed(() => {
  return fileStore.selectedFile || String(searchStore.query || '').trim().length > 0;
});

const displayLogs = computed(() => {
  return logViewerStore.logs && (logViewerStore.logs.length > 0 || !logViewerStore.hasMoreResults) && (logViewerStore.selectedFile || searchStore.hasQuery);
});

watch(
  [
    () => logViewerStore.direction,
    () => logViewerStore.resultsPerPage,
  ],
  () => logViewerStore.loadLogs()
)

const inlinePaginationSettingsIntoTableHeader = ref(true);

watch(() => logViewerStore.columns, () => {
  // only if the last column is the message column, which is usually a wide column
  // and leaves space for the pagination settings to be displayed in the table's header.
  inlinePaginationSettingsIntoTableHeader.value =
    logViewerStore.columns[logViewerStore.columns.length - 1].data_path === 'message';
});
</script>
