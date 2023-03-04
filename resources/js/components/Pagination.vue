<template>
  <nav class="pagination">
    <div class="previous">
      <button v-if="paginationStore.page !== 1" @click="previousPage" :disabled="loading" rel="prev">
        <ArrowLeftIcon class="h-5 w-5" />
        <span class="sm:hidden">Previous page</span>
      </button>
    </div>
    <div class="sm:hidden border-transparent text-gray-500 dark:text-gray-400 border-t-2 pt-3 px-4 inline-flex items-center text-sm font-medium">
      <span>{{ paginationStore.page }}</span>
    </div>
    <div class="pages">
      <template v-for="link in (short ? paginationStore.linksShort : paginationStore.links)">
        <button v-if="link.active" class="border-brand-500 text-brand-600 dark:border-brand-600 dark:text-brand-500"
                aria-current="page">
          {{ Number(link.label).toLocaleString() }}
        </button>
        <span v-else-if="link.label === '...'">{{ link.label }}</span>
        <button v-else @click="gotoPage(Number(link.label))"
                class="border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 hover:border-gray-300 dark:hover:text-gray-300 dark:hover:border-gray-400">
          {{ Number(link.label).toLocaleString() }}
        </button>
      </template>
    </div>
    <div class="next">
      <button v-if="paginationStore.hasMorePages" @click="nextPage" :disabled="loading" rel="next">
        <span class="sm:hidden">Next page</span>
        <ArrowRightIcon class="h-5 w-5" />
      </button>
    </div>
  </nav>
</template>

<script setup>
import { ArrowLeftIcon, ArrowRightIcon } from '@heroicons/vue/24/outline';
import { usePaginationStore } from '../stores/pagination.js';
import { useRoute, useRouter } from 'vue-router';
import { computed } from 'vue';
import { replaceQuery } from '../helpers.js';

const props = defineProps({
  loading: {
    type: Boolean,
    required: true,
  },
  short: {
    type: Boolean,
    default: false,
  }
})

const paginationStore = usePaginationStore();
const router = useRouter();
const route = useRoute();

const currentPage = computed(() => Number(route.query.page) || 1);

const gotoPage = (page) => {
  if (page < 1) {
    page = 1;
  }

  if (paginationStore.pagination && page > paginationStore.pagination.last_page) {
    page = paginationStore.pagination.last_page;
  }

  replaceQuery(router, 'page', page > 1 ? Number(page) : null);
}

const nextPage = () => gotoPage(paginationStore.page + 1);
const previousPage = () => gotoPage(paginationStore.page - 1);
</script>
