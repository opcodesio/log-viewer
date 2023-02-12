<template>
  <nav class="pagination">
    <div class="previous">
      <button v-if="paginationStore.page !== 1" @click="paginationStore.previousPage" :disabled="loading" rel="prev">
        <ArrowLeftIcon class="h-5 w-5" />
      </button>
    </div>
    <div class="pages">
      <template v-for="link in paginationStore.links">
        <button v-if="link.active" class="border-emerald-500 text-emerald-600 dark:border-emerald-600 dark:text-emerald-500"
                aria-current="page">
          {{ Number(link.label).toLocaleString() }}
        </button>
        <span v-else-if="link.label === '...'">{{ link.label }}</span>
        <button v-else @click="paginationStore.gotoPage(Number(link.label))"
                class="border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 hover:border-gray-300 dark:hover:text-gray-300 dark:hover:border-gray-400">
          {{ Number(link.label).toLocaleString() }}
        </button>
      </template>
    </div>
    <div class="next">
      <button v-if="paginationStore.hasMorePages" @click="paginationStore.nextPage" :disabled="loading" rel="next">
        <ArrowRightIcon class="h-5 w-5" />
      </button>
    </div>
  </nav>
</template>

<script setup>
import { ArrowLeftIcon, ArrowRightIcon } from '@heroicons/vue/24/outline';
import { usePaginationStore } from '../stores/pagination.js';
import { useRoute, useRouter } from 'vue-router';
import { watch } from 'vue';

const props = defineProps({
  loading: {
    type: Boolean,
    required: true,
  },
})

const paginationStore = usePaginationStore();
const router = useRouter();
const route = useRoute();

watch(
  () => paginationStore.currentPage,
  (newPage) => {
    const query = { ... route.query };

    if (newPage === 1) {
      delete query.page;
    } else {
      query.page = newPage;
    }

    router.push({ name: 'home', query });
  }
)
</script>
