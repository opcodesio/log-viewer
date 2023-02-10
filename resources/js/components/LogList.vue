<template>
  <div class="h-full w-full py-5 log-list">
    <div class="flex flex-col h-full w-full mx-3 mb-4">
      <div class="px-4 mb-4 flex items-start">
        <div class="flex items-center mr-6">
          <div v-if="logViewerStore.showLevelsDropdown">
            @include('log-viewer::partials.log-list-level-buttons')
          </div>
        </div>
        <div class="flex-1 flex justify-end min-h-[38px]">
          <div class="flex-1">@include('log-viewer::partials.search-input')</div>
          <div class="ml-5">@include('log-viewer::partials.log-list-share-page-button')</div>
          <div class="ml-2">@include('log-viewer::partials.site-settings-dropdown')</div>
        </div>
      </div>

      @if(isset($logs) && ($logs->isNotEmpty() || !$hasMoreResults))
      <div class="relative overflow-hidden text-sm h-full">
        <div id="log-item-container" class="log-item-container h-full overflow-y-auto px-4"
             @scroll="(event) => logViewerStore.onScroll(event)">
          <div class="inline-block min-w-full max-w-full align-middle">
            <table class="table-fixed min-w-full max-w-full border-separate" style="border-spacing: 0">
              <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="w-[60px] pl-4 pr-2 sm:pl-6 lg:pl-8"><span class="sr-only">Level icon</span></th>
                <th scope="col" class="w-[90px] hidden lg:table-cell">Level</th>
                <th scope="col" class="w-[180px] hidden sm:table-cell">Time</th>
                <th scope="col" class="w-[110px] hidden lg:table-cell">Env</th>
                <th scope="col">
                  <div class="flex justify-between">
                    <span>Description</span>
                    <div>
                      <label for="log-sort-direction" class="sr-only">Sort direction</label>
                      <select id="log-sort-direction" v-model="direction"
                              class="bg-gray-100 dark:bg-gray-900 px-2 font-normal mr-3 outline-none rounded focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-700">
                        <option value="desc">Newest first</option>
                        <option value="asc">Oldest first</option>
                      </select>
                      <label for="items-per-page" class="sr-only">Items per page</label>
                      <select id="items-per-page" v-model="perPage"
                              class="bg-gray-100 dark:bg-gray-900 px-2 font-normal outline-none rounded focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-700">
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
                <th scope="col"><span class="sr-only">Log index</span></th>
              </tr>
              </thead>

              <template v-if="logs && logs.length > 0">
                <tbody v-for="(log, index) in logs" :key="index"
                       :class="[index === 0 ? 'first' : '', 'log-group']"
                       :id="`tbody-${index}`" :data-index="index"
                >
                <tr @click="logViewerStore.toggle(index)"
                    :class="['log-item', log.level_class, logViewerStore.isOpen(index) ? 'active' : '', logViewerStore.shouldBeSticky(index) ? 'sticky z-2' : '']"
                    :style="{ top: logViewerStore.stackTops[index] || 0 }"
                >
                  <td class="log-level log-level-icon">
                    <ExclamationCircleIcon v-if="log.level_class === 'danger'" class="w-4 h-4" />
                    <ExclamationTriangleIcon v-if="log.level_class === 'warning'" class="w-4 h-4" />
                    <InformationCircleIcon v-else class="w-4 h-4" />
                  </td>
                  <td class="log-level truncate hidden lg:table-cell">{{ log.level_name }}</td>
                  <td class="whitespace-nowrap text-gray-900 dark:text-gray-200"
                      v-html="highlightSearchResult(log.time, searchStore.query)"></td>
                  <td class="whitespace-nowrap text-gray-500 dark:text-gray-300 dark:opacity-90 hidden lg:table-cell"
                      v-html="highlightSearchResult(log.environment, searchStore.query)"></td>
                  <td class="max-w-[1px] w-full truncate text-gray-500 dark:text-gray-300 dark:opacity-90"
                      v-html="highlightSearchResult(log.text, searchStore.query)"></td>
                  <td class="whitespace-nowrap text-gray-500 dark:text-gray-300 dark:opacity-90 text-xs">
                    @include('log-viewer::partials.log-list-link-button')
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
                        class="px-3 py-2 border dark:border-gray-700 text-gray-800 dark:text-gray-200 hover:border-emerald-600 dark:hover:border-emerald-700 rounded-md"
                        @click="searchStore.clearQuery">Clear search query
                      </button>
                      <button v-if="searchStore.query?.length > 0 && fileStore.selectedFile"
                        class="px-3 ml-3 py-2 border dark:border-gray-700 text-gray-800 dark:text-gray-200 hover:border-emerald-600 dark:hover:border-emerald-700 rounded-md"
                        @click.prevent="fileStore.selectFile(null)">Search all files
                      </button>
                      @if(isset($levels) && count(array_filter($levels, fn ($level) => $level->count > 0 &&
                      $level->selected)) === 0 && count(array_filter($levels, fn ($level) => $level->count > 0)) > 0)
                      <button
                        class="px-3 ml-3 py-2 border dark:border-gray-700 text-gray-800 dark:text-gray-200 hover:border-emerald-600 dark:hover:border-emerald-700 rounded-md"
                        @click="selectAllLevels">Select all severities
                      </button>
                      @endif
                    </div>
                  </div>
                </td>
              </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="absolute inset-0 top-9 px-4 z-20" v-show="loading">
          <div
            class="rounded-md bg-white text-gray-800 dark:bg-gray-700 dark:text-gray-200 opacity-90 w-full h-full flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14 spin" fill="currentColor">
              <use href="#icon-spinner" />
            </svg>
          </div>
        </div>
      </div>
      @else
      <div class="flex h-full items-center justify-center text-gray-500 dark:text-gray-400">
        <span v-if="has_more_results">Searching...</span>
        <span v-else>Select a file or start searching...</span>
      </div>
      @endisset

      <div v-if="paginator" class="px-4">
        <Pagination :paginator="paginator" :loading="false" />
      </div>

      <div class="grow flex flex-col justify-end text-right px-4 mt-3">
        <p class="text-xs text-gray-400 dark:text-gray-500">
          <span>Version: <span class="font-semibold">{{ LogViewer.version }}</span></span>
        </p>
      </div>
    </div>
  </div>

</template>

<script setup>
import { useLogViewerStore } from '../stores/logViewer.js';
import { onMounted, ref } from 'vue';
import { ExclamationCircleIcon, ExclamationTriangleIcon, InformationCircleIcon } from '@heroicons/vue/24/solid';
import { highlightSearchResult } from '../helpers.js';
import { useSearchStore } from '../stores/search.js';
import Pagination from './Pagination.vue';

const props = defineProps({
  expandAutomatically: {
    type: Boolean,
    default: false,
  },
})
const logViewerStore = useLogViewerStore();
const searchStore = useSearchStore();

const loading = ref(false);
const perPage = ref(25);
const direction = ref('desc');

onMounted(() => {
  logViewerStore.reset();

  if (props.expandAutomatically) {
    logViewerStore.stacksOpen.push(0);
  }
})
</script>
