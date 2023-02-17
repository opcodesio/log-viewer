import { defineStore } from 'pinia';

export const usePaginationStore = defineStore({
  id: 'pagination',

  state: () => ({
    page: 1,
    pagination: {},
  }),

  getters: {
    currentPage: (state) => state.page !== 1 ? Number(state.page) : null,

    links: (state) => (state.pagination?.links || []).slice(1, -1),

    linksShort: (state) => (state.pagination?.links_short || []).slice(1, -1),

    hasPages: (state) => state.pagination?.last_page > 1,

    hasMorePages: (state) => state.pagination?.next_page_url !== null,
  },

  actions: {
    setPagination(pagination) {
      this.pagination = pagination;

      if (this.pagination?.last_page < this.page) {
        this.page = this.pagination?.last_page;
      }
    },

    setPage(page) {
      this.page = Number(page);
    },
  },
})
