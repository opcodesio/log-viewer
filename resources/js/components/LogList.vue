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

      <div v-if="displayLogs" class="relative overflow-hidden h-full text-sm">
        <!-- pagination settings -->
        <div class="mx-2 mt-1 mb-2 text-right lg:mx-0 lg:mt-0 lg:mb-0 lg:absolute lg:top-2 lg:right-6 z-20 text-sm text-gray-500 dark:text-gray-400">
          <label for="log-sort-direction" class="sr-only">Sort direction</label>
          <select id="log-sort-direction" v-model="logViewerStore.direction" class="select mr-4">
            <option value="desc">Newest first</option>
            <option value="asc">Oldest first</option>
          </select>
          <label for="items-per-page" class="sr-only">Items per page</label>
          <select id="items-per-page" v-model="logViewerStore.resultsPerPage" class="select">
            <option value="10">10 items per page</option>
            <option value="25">25 items per page</option>
            <option value="50">50 items per page</option>
            <option value="100">100 items per page</option>
            <option value="250">250 items per page</option>
            <option value="500">500 items per page</option>
          </select>
        </div>

        <div class="log-item-container h-full overflow-y-auto md:px-4" @scroll="(event) => logViewerStore.onScroll(event)">
          <div class="inline-block min-w-full max-w-full align-middle">
            <table class="table-fixed min-w-full max-w-full border-separate" style="border-spacing: 0">
              <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="w-[120px] hidden lg:table-cell"><div class="pl-2">Level</div></th>
                <th scope="col" class="w-[180px] hidden lg:table-cell">Time</th>
                <th scope="col" class="w-[110px] hidden lg:table-cell">Env</th>
                <th scope="col" class="hidden lg:table-cell">Description</th>
                <th scope="col" class="hidden lg:table-cell"><span class="sr-only">Log index</span></th>
              </tr>
              </thead>

              <template v-if="logViewerStore.logs && logViewerStore.logs.length > 0">
                <tbody v-for="(log, index) in logViewerStore.logs" :key="index"
                       :class="[index === 0 ? 'first' : '', 'log-group']"
                       :id="`tbody-${index}`" :data-index="index"
                >
                <tr @click="logViewerStore.toggle(index)"
                    :class="['log-item group', log.level_class, logViewerStore.isOpen(index) ? 'active' : '', logViewerStore.shouldBeSticky(index) ? 'sticky z-2' : '']"
                    :style="{ top: logViewerStore.stackTops[index] || 0 }"
                >
                  <td class="log-level truncate">
                    <div class="flex items-center lg:pl-2">
                      <button :aria-expanded="logViewerStore.isOpen(index)"
                              @keydown="handleLogToggleKeyboardNavigation"
                              class="log-level-icon mr-2 opacity-75 w-5 h-5 hidden lg:block group focus:opacity-100 focus:outline-none focus:ring-2 focus:ring-brand-500 rounded-md"
                      >
                        <span class="sr-only" v-if="!logViewerStore.isOpen(index)">Expand log entry</span>
                        <span class="sr-only" v-if="logViewerStore.isOpen(index)">Collapse log entry</span>
                        <span class="w-full h-full group-hover:hidden group-focus:hidden">
                          <ExclamationCircleIcon v-if="log.level_class === 'danger'" />
                          <ExclamationTriangleIcon v-else-if="log.level_class === 'warning'" />
                          <InformationCircleIcon v-else />
                        </span>
                        <span class="w-full h-full hidden group-hover:inline-block group-focus:inline-block">
                          <ChevronRightIcon :class="[logViewerStore.isOpen(index) ? 'rotate-90' : '', 'transition duration-100']" />
                        </span>
                      </button>
                      <span>{{ log.level_name }}</span>
                    </div>
                  </td>
                  <td class="whitespace-nowrap text-gray-900 dark:text-gray-200">
                    <span class="hidden lg:inline" v-html="highlightSearchResult(log.datetime, searchStore.query)"></span>
                    <span class="lg:hidden">{{ log.time }}</span>
                  </td>
                  <td class="whitespace-nowrap text-gray-500 dark:text-gray-300 dark:opacity-90 hidden lg:table-cell"
                      v-html="highlightSearchResult(log.environment, searchStore.query)"></td>
                  <td class="max-w-[1px] w-full truncate text-gray-500 dark:text-gray-300 dark:opacity-90"
                      v-html="highlightSearchResult(log.text, searchStore.query)"></td>
                  <td class="whitespace-nowrap text-gray-500 dark:text-gray-300 dark:opacity-90 text-xs hidden lg:table-cell">
                    <LogCopyButton :log="log" class="pr-2 large-screen" />
                  </td>
                </tr>
                <tr v-show="logViewerStore.isOpen(index)">
                  <td colspan="6">
                    <div class="lg:hidden flex justify-between px-2 pt-2 pb-1 text-xs">
                      <div class="flex-1"><span class="font-semibold">Time:</span> {{ log.datetime }}</div>
                      <div class="flex-1"><span class="font-semibold">Env:</span> {{ log.environment }}</div>
                      <div>
                        <LogCopyButton :log="log" />
                      </div>
                    </div>
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
import { computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import { highlightSearchResult, replaceQuery } from '../helpers.js';
import {
  ArrowPathIcon,
  Bars3Icon,
  ChevronRightIcon,
  ExclamationCircleIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
} from '@heroicons/vue/24/solid';
import { useLogViewerStore } from '../stores/logViewer.js';
import { useSearchStore } from '../stores/search.js';
import { useFileStore } from '../stores/files.js';
import { usePaginationStore } from '../stores/pagination.js';
import { useSeverityStore } from '../stores/severity.js';
import Pagination from './Pagination.vue';
import LevelButtons from './LevelButtons.vue';
import SearchInput from './SearchInput.vue';
import SiteSettingsDropdown from './SiteSettingsDropdown.vue';
import SpinnerIcon from './SpinnerIcon.vue';
import LogCopyButton from './LogCopyButton.vue';
import { handleLogToggleKeyboardNavigation } from '../keyboardNavigation';

const router = useRouter();
const fileStore = useFileStore();
const logViewerStore = useLogViewerStore();
const searchStore = useSearchStore();
const paginationStore = usePaginationStore();
const severityStore = useSeverityStore();

const showLevelsDropdown = computed(() => {
  return fileStore.selectedFile || String(searchStore.query || '').trim().length > 0;
});

const displayLogs = computed(() => {
  return logViewerStore.logs && (logViewerStore.logs.length > 0 || !logViewerStore.hasMoreResults) && (logViewerStore.selectedFile || searchStore.hasQuery);
});

const clearSelectedFile = () => {
  replaceQuery(router, 'file', null);
}

const clearQuery = () => {
  replaceQuery(router, 'query', null);
}

watch(
  [
    () => logViewerStore.direction,
    () => logViewerStore.resultsPerPage,
  ],
  () => logViewerStore.loadLogs()
)
</script>
