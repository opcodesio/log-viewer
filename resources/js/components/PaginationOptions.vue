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
    <option value="10">10 items per page</option>
    <option value="25">25 items per page</option>
    <option value="50">50 items per page</option>
    <option value="100">100 items per page</option>
    <option value="250">250 items per page</option>
    <option value="500">500 items per page</option>
  </select>
</div>
</template>
