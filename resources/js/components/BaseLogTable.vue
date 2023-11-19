<template>
  <table class="table-fixed min-w-full max-w-full border-separate" style="border-spacing: 0">
    <thead class="bg-gray-50">
    <tr>
      <th class="hidden lg:table-cell"><span class="sr-only">Expand/Collapse</span></th>
      <th v-for="(column) in logViewerStore.columns" scope="col">
        <div>{{ column.label }}</div>
      </th>
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
        <td class="log-level hidden lg:table-cell">
          <div class="flex items-center lg:pl-2">
            <button :aria-expanded="logViewerStore.isOpen(index)"
                    @keydown="handleLogToggleKeyboardNavigation"
                    class="log-level-icon opacity-75 w-5 h-5 hidden lg:block group focus:opacity-100 focus:outline-none focus:ring-2 focus:ring-brand-500 rounded-md"
            >
              <span class="sr-only" v-if="!logViewerStore.isOpen(index)">Expand log entry</span>
              <span class="sr-only" v-if="logViewerStore.isOpen(index)">Collapse log entry</span>
              <span class="w-full h-full group-hover:hidden group-focus:hidden">
                <ExclamationCircleIcon v-if="log.level_class === 'danger'" />
                <ExclamationTriangleIcon v-else-if="log.level_class === 'warning'" />
                <CheckCircleIcon v-else-if="log.level_class === 'success'" />
                <InformationCircleIcon v-else />
              </span>
              <span class="w-full h-full hidden group-hover:inline-block group-focus:inline-block">
                <ChevronRightIcon :class="[logViewerStore.isOpen(index) ? 'rotate-90' : '', 'transition duration-100']" />
              </span>
            </button>
          </div>
        </td>

        <template v-for="(column, colIndex) in logViewerStore.columns">
          <!-- Severity -->
          <td :key="`${log.index}-column-${colIndex}`" v-if="column.data_path === 'level'" class="log-level truncate">
            <span>{{ log.level_name }}</span>
          </td>
          <!-- /Severity -->

          <!-- Datetime -->
          <td :key="`${log.index}-column-${colIndex}`" v-else-if="column.data_path === 'datetime'" class="whitespace-nowrap text-gray-900 dark:text-gray-200">
            <span class="hidden lg:inline" v-html="highlightSearchResult(log.datetime, searchStore.query)"></span>
            <span class="lg:hidden">{{ log.time }}</span>
          </td>
          <!-- /Datetime -->

          <!-- Message -->
          <td :key="`${log.index}-column-${colIndex}`" v-else-if="column.data_path === 'message'" class="max-w-[1px] w-full truncate text-gray-500 dark:text-gray-300 dark:opacity-90">
            <span v-html="highlightSearchResult(`${log.message}`, searchStore.query)"></span>
          </td>
          <!-- /Message -->

          <td :key="`${log.index}-column-${colIndex}`" v-else class="text-gray-500 dark:text-gray-300 dark:opacity-90" :class="column.class || ''">
            <span v-html="highlightSearchResult(getDataAtPath(log, column.data_path), searchStore.query)"></span>
          </td>
        </template>

        <td class="whitespace-nowrap text-gray-500 dark:text-gray-300 dark:opacity-90 text-xs hidden lg:table-cell">
          <LogCopyButton :log="log" class="pr-2 large-screen" />
        </td>
      </tr>
      <tr v-show="logViewerStore.isOpen(index)">
        <td colspan="6">
          <div class="lg:hidden flex justify-between px-2 pt-2 pb-1 text-xs">
            <div class="flex-1"><span class="font-semibold">Datetime:</span> {{ log.datetime }}</div>
            <div>
              <LogCopyButton :log="log" />
            </div>
          </div>

          <tab-container v-if="logViewerStore.isOpen(index)" :tabs="getTabsForLog(log)">
            <tab-content v-if="log.extra && log.extra.mail_preview && log.extra.mail_preview.html" tab-value="mail_html_preview">
              <mail-html-preview :mail="log.extra.mail_preview" />
            </tab-content>

            <tab-content v-if="log.extra && log.extra.mail_preview && log.extra.mail_preview.text" tab-value="mail_text_preview">
              <mail-text-preview :mail="log.extra.mail_preview" />
            </tab-content>

            <tab-content tab-value="raw">
              <pre class="log-stack" v-html="highlightSearchResult(log.full_text, searchStore.query)"></pre>
              <template v-if="hasContext(log)">
                <p class="mx-2 lg:mx-8 pt-2 border-t font-semibold text-gray-700 dark:text-gray-400 text-xs lg:text-sm">Context:</p>
                <pre class="log-stack" v-html="highlightSearchResult(prepareContextForOutput(log.context), searchStore.query)"></pre>
              </template>

              <div v-if="log.extra && log.extra.log_text_incomplete" class="py-4 px-8 text-gray-500 italic">
                The contents of this log have been cut short to the first {{ LogViewer.max_log_size_formatted }}.
                The full size of this log entry is <strong>{{ log.extra.log_size_formatted }}</strong>
              </div>
            </tab-content>
          </tab-container>
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
</template>

<script setup>
import {
  ChevronRightIcon,
  ExclamationCircleIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  InformationCircleIcon,
} from '@heroicons/vue/24/solid';
import { highlightSearchResult } from '../helpers.js';
import { useLogViewerStore } from '../stores/logViewer.js';
import { useSearchStore } from '../stores/search.js';
import { useFileStore } from '../stores/files.js';
import LogCopyButton from './LogCopyButton.vue';
import { handleLogToggleKeyboardNavigation } from '../keyboardNavigation';
import { useSeverityStore } from '../stores/severity.js';
import TabContainer from "./TabContainer.vue";
import TabContent from "./TabContent.vue";
import MailHtmlPreview from "./MailHtmlPreview.vue";
import MailTextPreview from "./MailTextPreview.vue";

const fileStore = useFileStore();
const logViewerStore = useLogViewerStore();
const searchStore = useSearchStore();
const severityStore = useSeverityStore();
const emit = defineEmits(['clearSelectedFile', 'clearQuery']);

const clearSelectedFile = () => {
  emit('clearSelectedFile');
}

const clearQuery = () => {
  emit('clearQuery');
}

const getDataAtPath = (obj, path) => {
  const value = path.split('.').reduce((acc, part) => acc && acc[part], obj);
  return typeof value === 'undefined' ? '' : String(value);
}

const hasContext = (log) => {
  return log.context && Object.keys(log.context).length > 0;
}

const getExtraTabsForLog = (log) => {
  let tabs = [];

  if (! log.extra || ! log.extra.mail_preview) {
    return tabs;
  }

  if (log.extra.mail_preview.html) {
    tabs.push({ name: 'HTML preview', value: 'mail_html_preview' });
  }

  if (log.extra.mail_preview.text) {
    tabs.push({ name: 'Text preview', value: 'mail_text_preview' });
  }

  return tabs;
}

const getTabsForLog = (log) => {
  return [
    ...getExtraTabsForLog(log),
    { name: 'Raw', value: 'raw' },
  ].filter(Boolean);
}

const prepareContextForOutput = (context) => {
  return JSON.stringify(context, function (key, value) {
    if (typeof value === 'string') {
      return value.replaceAll('\n', '<br/>');
    }

    return value;
  }, 2);
}
</script>
