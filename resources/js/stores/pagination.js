import { defineStore } from 'pinia';

export const usePaginationStore = defineStore({
  id: 'pagination',

  state: () => ({
    page: 1,
    pagination: {},
  }),

  getters: {
    currentPage: (state) => state.page !== 1 ? Number(state.page) : null,

    links: (state) => (state.pagination.links || []).slice(1, -1),

    hasPages: (state) => state.pagination?.links?.length > 2,

    hasMorePages: (state) => state.pagination?.next_page_url !== null,
  },

  actions: {
    setPagination(pagination) {
      this.pagination = pagination;
    },

    gotoPage(page) {
      this.page = page;
    },

    nextPage() {
      this.page += 1;
    },

    previousPage() {
      this.page -= 1;
    },

    reset() {
      this.page = 1;
    },
  },
})
