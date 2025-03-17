<script setup>
import {useLogViewerStore} from "../stores/logViewer";

const logViewerStore = useLogViewerStore();
</script>

<template>
<div class="text-sm text-gray-500 dark:text-gray-400">
  <label for="log-sort-direction" class="sr-only">Sort direction</label>
  <select id="log-sort-direction" v-model="logViewerStore.direction" class="select mr-4">
    <option value="desc">Newest first</option>
    <option value="asc">Oldest first</option>
  </select>
  <label for="items-per-page" class="sr-only">Items per page</label>
  <select id="items-per-page" v-model="logViewerStore.resultsPerPage" class="select">
    <option v-for="option in logViewerStore.perPageOptions" :key="option" :value="option">{{ option }} items per page</option>
  </select>
</div>
</template>
