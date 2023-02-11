<template>
  <nav class="pagination" :key="`pagination-next-${paginator.current_page}`">
    <div class="previous">
      <button v-if="paginator.current_page !== 1" @click="previousPage" :disabled="loading" rel="prev">
        <ArrowLeftIcon class="h-5 w-5" />
      </button>
    </div>
    <div class="pages">
      <template v-for="link in links">
        <button v-if="link.active" class="border-emerald-500 text-emerald-600 dark:border-emerald-600 dark:text-emerald-500"
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
      <button v-if="paginator.has_more_pages" @click="nextPage" :disabled="loading" rel="next">
        <ArrowRightIcon class="h-5 w-5" />
      </button>
    </div>
  </nav>

</template>

<script setup>
import { ArrowLeftIcon, ArrowRightIcon } from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({
  paginator: {
    type: Object,
    required: true,
  },
  loading: {
    type: Boolean,
    required: true,
  },
})

const links = computed(() => (props.paginator.links || []).slice(1, -1));
</script>
