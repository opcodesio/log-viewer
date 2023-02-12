<template>
  <div class="hidden md:flex md:w-88 md:flex-col md:fixed md:inset-y-0">
      <file-list></file-list>
  </div>

  <div class="md:pl-88 flex flex-col flex-1 min-h-screen max-h-screen max-w-full">
      <log-list></log-list>
  </div>
</template>

<script setup>
import FileList from '../components/FileList.vue';
import LogList from '../components/LogList.vue';
import { useLogViewerStore } from '../stores/logViewer.js';
import { useFileStore } from '../stores/files.js';
import { useSearchStore } from '../stores/search.js';
import { usePaginationStore } from '../stores/pagination.js';
import { useRoute, useRouter } from 'vue-router';
import { onMounted, watch } from 'vue';
import { useSeverityStore } from '../stores/severity.js';
import { replaceQuery } from '../helpers.js';

const logViewerStore = useLogViewerStore();
const fileStore = useFileStore();
const searchStore = useSearchStore();
const paginationStore = usePaginationStore();
const severityStore = useSeverityStore();
const router = useRouter();
const route = useRoute();

onMounted(() => {
  const query = { ...route.query };

  // First, let's set the default values from the query string
  if (query.query) {
    searchStore.query = query.query;
    searchStore.tempQuery = query.query;
  }

  if (query.page) {
    paginationStore.page = parseInt(query.page);
  }

  // This makes sure we react to device's dark mode changes
  setInterval(logViewerStore.syncTheme, 1000);
})

watch(
  () => searchStore.query,
  (query) => replaceQuery(router, 'query', query)
);

watch([
  () => route.query,
  () => severityStore.selectedLevels,
], () => {
  logViewerStore.loadLogs();
});
</script>
