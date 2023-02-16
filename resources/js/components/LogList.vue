<template>
  <div class="h-full w-full py-5 log-list">
    <div class="flex flex-col h-full w-full md:mx-3 mb-4">
      <div class="md:px-4 mb-4 flex flex-col-reverse md:flex-row items-start">
        <div class="flex items-center mr-5 mt-3 md:mt-0" v-if="showLevelsDropdown">
          <LevelButtons />
        </div>
        <div class="w-full md:w-auto flex-1 flex justify-end min-h-[38px]">
          <SearchInput />
          <div class="hidden md:block ml-5">
            <button @click="logViewerStore.loadLogs()" title="Reload current results" class="menu-button">
              <ArrowPathIcon class="w-5 h-5" />
            </button>
          </div>
          <div class="hidden md:block">
            <SiteSettingsDropdown class="ml-2" />
          </div>
          <div class="md:hidden">
            <button type="button" class="menu-button">
              <Bars3Icon class="w-5 h-5 ml-2" @click="fileStore.toggleSidebar" />
            </button>
          </div>
        </div>
      </div>

      <!-- Dummy element to make fancy scrolling work -->
      <div id="log-item-container" class="relative"></div>

      <div v-if="logViewerStore.logs && (logViewerStore.logs.length > 0 || !logViewerStore.hasMoreResults) && (logViewerStore.selectedFile || searchStore.hasQuery)" class="relative overflow-hidden text-sm h-full">
        <div class="h-full overflow-y-auto md:px-4"
             @scroll="(event) => logViewerStore.onScroll(event)">
          <div class="inline-block min-w-full max-w-full align-middle">
            <table class="table-fixed min-w-full max-w-full border-separate" style="border-spacing: 0">
              <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="w-[60px] pl-4 pr-2 sm:pl-6 lg:pl-8 hidden sm:table-cell"><span class="sr-only">Level icon</span></th>
                <th scope="col" class="w-[90px] hidden lg:table-cell">Level</th>
                <th scope="col" class="w-[180px] hidden sm:table-cell">Time</th>
                <th scope="col" class="w-[110px] hidden lg:table-cell">Env</th>
                <th scope="col" :colspan="headerColspan">
                  <div class="flex justify-end sm:justify-between">
                    <span class="hidden sm:inline-block">Description</span>
                    <div>
                      <label for="log-sort-direction" class="sr-only">Sort direction</label>
                      <select id="log-sort-direction" v-model="logViewerStore.direction"
                              class="bg-gray-100 dark:bg-gray-900 px-2 font-normal mr-3 outline-none rounded focus:ring-2 focus:ring-brand-500 dark:focus:ring-brand-700">
                        <option value="desc">Newest first</option>
                        <option value="asc">Oldest first</option>
                      </select>
                      <label for="items-per-page" class="sr-only">Items per page</label>
                      <select id="items-per-page" v-model="logViewerStore.resultsPerPage"
                              class="bg-gray-100 dark:bg-gray-900 px-2 font-normal outline-none rounded focus:ring-2 focus:ring-brand-500 dark:focus:ring-brand-700">
                        <option value="10">10 items per page</option>
                        <option value="25">25 items per page</option>
                        <option value="50">50 items per page</option>
                        <option value="100">100 items per page</option>
                        <option value="250">250 items per page</option>
                        <option value="500">500 items per page</option>
                      </select>
                    </div>
                  </div>
                </th>
                <th scope="col" class="hidden sm:table-cell"><span class="sr-only">Log index</span></th>
              </tr>
              </thead>

              <template v-if="logViewerStore.logs && logViewerStore.logs.length > 0">
                <tbody v-for="(log, index) in logViewerStore.logs" :key="index"
                       :class="[index === 0 ? 'first' : '', 'log-group']"
                       :id="`tbody-${index}`" :data-index="index"
                >
                <tr @click="logViewerStore.toggle(index)"
                    :class="['log-item', log.level_class, logViewerStore.isOpen(index) ? 'active' : '', logViewerStore.shouldBeSticky(index) ? 'sticky z-2' : '']"
                    :style="{ top: logViewerStore.stackTops[index] || 0 }"
                >
                  <td class="log-level log-level-icon">
                    <ExclamationCircleIcon v-if="log.level_class === 'danger'" class="w-4 h-4" />
                    <ExclamationTriangleIcon v-else-if="log.level_class === 'warning'" class="w-4 h-4" />
                    <InformationCircleIcon v-else class="w-4 h-4" />
                  </td>
                  <td class="log-level truncate hidden lg:table-cell">{{ log.level_name }}</td>
                  <td class="whitespace-nowrap text-gray-900 dark:text-gray-200 hidden sm:table-cell"
                      v-html="highlightSearchResult(log.time, searchStore.query)"></td>
                  <td class="whitespace-nowrap text-gray-500 dark:text-gray-300 dark:opacity-90 hidden lg:table-cell"
                      v-html="highlightSearchResult(log.environment, searchStore.query)"></td>
                  <td class="max-w-[1px] w-full truncate text-gray-500 dark:text-gray-300 dark:opacity-90"
                      v-html="highlightSearchResult(log.text, searchStore.query)"></td>
                  <td class="whitespace-nowrap text-gray-500 dark:text-gray-300 dark:opacity-90 text-xs">
                    <LogCopyButton :log="log" />
                  </td>
                </tr>
                <tr v-show="logViewerStore.isOpen(index)">
                  <td colspan="6">
                    <pre class="log-stack" v-html="highlightSearchResult(log.full_text, searchStore.query)"></pre>
                    <div v-if="log.full_text_incomplete" class="py-4 px-8 text-gray-500 italic">
                      The contents of this log have been cut short to the first {{ LogViewer.max_log_size_formatted }}.
                      The full size of this log entry is <strong>{{ log.full_text_length_formatted }}</strong>
                    </div>
                  </td>
                </tr>
                </tbody>
              </template>

              <tbody v-else class="log-group">
              <tr>
                <td colspan="6">
                  <div class="bg-white text-gray-600 dark:bg-gray-800 dark:text-gray-200 p-12">
                    <div class="text-center font-semibold">No results</div>
                    <div class="text-center mt-6">
                      <button v-if="searchStore.query?.length > 0"
                        class="px-3 py-2 border dark:border-gray-700 text-gray-800 dark:text-gray-200 hover:border-brand-600 dark:hover:border-brand-700 rounded-md"
                        @click="clearQuery">Clear search query
                      </button>
                      <button v-if="searchStore.query?.length > 0 && fileStore.selectedFile"
                        class="px-3 ml-3 py-2 border dark:border-gray-700 text-gray-800 dark:text-gray-200 hover:border-brand-600 dark:hover:border-brand-700 rounded-md"
                        @click.prevent="clearSelectedFile">Search all files
                      </button>
                      <button
                        v-if="severityStore.levelsFound.length > 0 && severityStore.levelsSelected.length === 0"
                        class="px-3 ml-3 py-2 border dark:border-gray-700 text-gray-800 dark:text-gray-200 hover:border-brand-600 dark:hover:border-brand-700 rounded-md"
                        @click="severityStore.selectAllLevels">Select all severities
                      </button>
                    </div>
                  </div>
                </td>
              </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="absolute inset-0 top-9 md:px-4 z-20" v-show="logViewerStore.loading">
          <div
            class="rounded-md bg-white text-gray-800 dark:bg-gray-700 dark:text-gray-200 opacity-90 w-full h-full flex items-center justify-center">
            <SpinnerIcon class="w-14 h-14" />
          </div>
        </div>
      </div>
      <div v-else class="flex h-full items-center justify-center text-gray-500 dark:text-gray-400">
        <span v-if="logViewerStore.hasMoreResults">Searching...</span>
        <span v-else>Select a file or start searching...</span>
      </div>

      <div v-if="paginationStore.hasPages" class="md:px-4">
        <Pagination :loading="logViewerStore.loading" />
      </div>
    </div>
  </div>

</template>

<script setup>
import { useLogViewerStore } from '../stores/logViewer.js';
import { computed, watch } from 'vue';
import {
  ArrowPathIcon,
  ExclamationCircleIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  Bars3Icon,
} from '@heroicons/vue/24/solid';
import { highlightSearchResult, replaceQuery } from '../helpers.js';
import { useSearchStore } from '../stores/search.js';
import Pagination from './Pagination.vue';
import LevelButtons from './LevelButtons.vue';
import { useFileStore } from '../stores/files.js';
import SearchInput from './SearchInput.vue';
import SiteSettingsDropdown from './SiteSettingsDropdown.vue';
import SpinnerIcon from './SpinnerIcon.vue';
import LogCopyButton from './LogCopyButton.vue';
import { usePaginationStore } from '../stores/pagination.js';
import { useSeverityStore } from '../stores/severity.js';
import { useRouter } from 'vue-router';

const router = useRouter();
const fileStore = useFileStore();
const logViewerStore = useLogViewerStore();
const searchStore = useSearchStore();
const paginationStore = usePaginationStore();
const severityStore = useSeverityStore();

const showLevelsDropdown = computed(() => {
  return fileStore.selectedFile || String(searchStore.query || '').trim().length > 0;
});

const clearSelectedFile = () => {
  replaceQuery(router, 'file', null);
}

const clearQuery = () => {
  replaceQuery(router, 'query', null);
}

const headerColspan = computed(() => {
  return window.matchMedia('(max-width: 640px)').matches ? 3 : 1;
});

watch(
  [
    () => logViewerStore.direction,
    () => logViewerStore.resultsPerPage,
  ],
  () => logViewerStore.loadLogs()
)
</script>
