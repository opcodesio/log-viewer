<template>
  <div class="absolute z-20 top-0 bottom-10 bg-gray-100 dark:bg-gray-900 md:left-0 md:flex md:w-88 md:flex-col md:fixed md:inset-y-0"
       :class="[fileStore.sidebarOpen ? 'left-0 right-0 md:left-auto md:right-auto' : '-left-[100%] right-[100%] md:left-auto md:right-auto']"

  >
    <file-list></file-list>
  </div>

  <div class="md:pl-88 flex flex-col flex-1 min-h-screen max-h-screen max-w-full">
    <log-list class="pb-16 md:pb-12"></log-list>
  </div>
</template>

<script setup>
import FileList from '../components/FileList.vue';
import LogList from '../components/LogList.vue';
import { useLogViewerStore } from '../stores/logViewer.js';
import { useFileStore } from '../stores/files.js';
import { useSearchStore } from '../stores/search.js';
import { usePaginationStore } from '../stores/pagination.js';
import { useRoute } from 'vue-router';
import { onBeforeMount, onMounted, watch } from 'vue';

const logViewerStore = useLogViewerStore();
const fileStore = useFileStore();
const searchStore = useSearchStore();
const paginationStore = usePaginationStore();
const route = useRoute();

onBeforeMount(() => logViewerStore.syncTheme());

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
</script>
