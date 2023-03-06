<template>
  <div class="absolute z-20 top-0 bottom-10 bg-gray-100 dark:bg-gray-900 md:left-0 md:flex md:w-88 md:flex-col md:fixed md:inset-y-0"
       :class="[fileStore.sidebarOpen ? 'left-0 right-0 md:left-auto md:right-auto' : '-left-[200%] right-[200%] md:left-auto md:right-auto']"
  >
    <file-list></file-list>
  </div>

  <div class="md:pl-88 flex flex-col flex-1 min-h-screen max-h-screen max-w-full">
    <log-list class="pb-16 md:pb-12"></log-list>
  </div>

  <div class="absolute bottom-4 right-4 flex items-center">
    <p class="text-xs text-gray-500 dark:text-gray-400 mr-5 -mb-0.5">
      <template v-if="logViewerStore.performance?.requestTime">
        <span><span class="hidden md:inline">Memory: </span><span class="font-semibold">{{ logViewerStore.performance.memoryUsage }}</span></span>
        <span class="mx-1.5">&middot;</span>
        <span><span class="hidden md:inline">Duration: </span><span class="font-semibold">{{ logViewerStore.performance.requestTime }}</span></span>
        <span class="mx-1.5">&middot;</span>
      </template>
      <span><span class="hidden md:inline">Version: </span><span class="font-semibold">{{ LogViewer.version }}</span></span>
    </p>
    <a href="https://www.buymeacoffee.com/arunas" target="_blank" v-if="LogViewer.show_support_link">
      <bmc-logo class="h-6 w-auto" title="Support me by buying me a cup of coffee ❤️" />
    </a>
  </div>

  <keyboard-shortcuts-overlay />
</template>

<script setup>
import FileList from '../components/FileList.vue';
import LogList from '../components/LogList.vue';
import { useHostStore } from '../stores/hosts.js';
import { useLogViewerStore } from '../stores/logViewer.js';
import { useFileStore } from '../stores/files.js';
import { useSearchStore } from '../stores/search.js';
import { usePaginationStore } from '../stores/pagination.js';
import { useRoute, useRouter } from 'vue-router';
import { onBeforeMount, onBeforeUnmount, onMounted, watch } from 'vue';
import BmcLogo from '../components/BmcLogo.vue';
import { replaceQuery } from '../helpers.js';
import { registerGlobalShortcuts, unregisterGlobalShortcuts } from '../keyboardNavigation';
import KeyboardShortcutsOverlay from '../components/KeyboardShortcutsOverlay.vue';

const hostStore = useHostStore();
const logViewerStore = useLogViewerStore();
const fileStore = useFileStore();
const searchStore = useSearchStore();
const paginationStore = usePaginationStore();
const route = useRoute();
const router = useRouter();

onBeforeMount(() => {
  logViewerStore.syncTheme();
  registerGlobalShortcuts();
});

onBeforeUnmount(() => {
  unregisterGlobalShortcuts();
})

onMounted(() => {
  // This makes sure we react to device's dark mode changes
  setInterval(logViewerStore.syncTheme, 1000);
})

// watch for URL query changes and update the store values
watch(
  () => route.query,
  (query) => {
    fileStore.selectFile(query.file || null);
    paginationStore.setPage(query.page || 1);
    searchStore.setQuery(query.query || '');

    logViewerStore.loadLogs();
  },
  { immediate: true },
)

watch(
  () => route.query.host,
  async (newHost) => {
    hostStore.selectHost(newHost || null);

    if (newHost && !hostStore.selectedHostIdentifier) {
      // the host no longer exists, remove it from the URL
      replaceQuery(router, 'host', null);
    }

    fileStore.reset();
    await fileStore.loadFolders();
    logViewerStore.loadLogs();
  },
  { immediate: true },
)

onMounted(() => {
  window.onresize = function () {
    logViewerStore.setViewportDimensions(window.innerWidth, window.innerHeight);
  };
})
</script>
