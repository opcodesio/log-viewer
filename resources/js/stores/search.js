import { defineStore } from 'pinia';
import axios from 'axios';

export const useSearchStore = defineStore({
  id: 'search',

  state: () => ({
    query: '',
    searchMoreRoute: null,
    searching: false,
    percentScanned: 0,
    error: null,
  }),

  getters: {
    hasQuery: (state) => String(state.query).trim() !== '',
  },

  actions: {
    init() {
      this.checkSearchProgress();
    },

    setQuery(query) {
      this.query = query;
    },

    update(query, error, searchMoreRoute, searching = false, percentScanned = 0) {
      this.query = query;
      this.error = (error && error !== '') ? error : null;
      this.searchMoreRoute = searchMoreRoute;
      this.searching = searching;
      this.percentScanned = percentScanned;

      if (this.searching) {
        this.checkSearchProgress();
      }
    },

    checkSearchProgress() {
      const queryChecked = this.query;
      if (queryChecked === '') return;
      const queryParams = '?' + new URLSearchParams({ query: queryChecked });
      axios.get(this.searchMoreRoute + queryParams)
        .then((response) => {
          const data = response.data;
          if (this.query !== queryChecked) return;
          const wasPreviouslySearching = this.searching;
          this.searching = data.hasMoreResults;
          this.percentScanned = data.percentScanned;

          if (this.searching) {
            this.checkSearchProgress();
          } else if (wasPreviouslySearching && !this.searching) {
            window.dispatchEvent(new CustomEvent('reload-results'));
          }
        });
    },
  }
})
