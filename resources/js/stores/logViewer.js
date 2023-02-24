import { defineStore } from 'pinia';
import { useFileStore } from './files.js';
import axios from 'axios';
import { useSearchStore } from './search.js';
import { nextTick } from 'vue';
import { usePaginationStore } from './pagination.js';
import { useSeverityStore } from './severity.js';
import { useLocalStorage } from '@vueuse/core';
import { debounce } from 'lodash';

export const Theme = {
  System: 'System',
  Light: 'Light',
  Dark: 'Dark',
}

export const useLogViewerStore = defineStore({
  id: 'logViewer',

  state: () => ({
    theme: useLocalStorage('logViewerTheme', Theme.System),
    shorterStackTraces: useLocalStorage('logViewerShorterStackTraces', false),
    direction: useLocalStorage('logViewerDirection', 'desc'),
    resultsPerPage: useLocalStorage('logViewerResultsPerPage', 25),

    // Log data
    loading: false,
    error: null,
    logs: [],
    levelCounts: [],
    performance: {},
    hasMoreResults: false,
    percentScanned: 100,
    abortController: null,

    // Log scrolling behaviour data
    stacksOpen: [],
    stacksInView: [],
    stackTops: {},
    containerTop: 0,
    showLevelsDropdown: true,
  }),

  getters: {
    selectedFile() {
      const fileStore = useFileStore();
      return fileStore.selectedFile;
    },

    isOpen: (state) => (index) => state.stacksOpen.includes(index),

    shouldBeSticky(state) {
      return (index) => this.isOpen(index) && state.stacksInView.includes(index);
    },

    stickTopPosition() {
      return (index) => {
        let aboveFold = this.pixelsAboveFold(index);

        if (aboveFold < 0) {
          return Math.max(0, 36 + aboveFold) + 'px';
        }

        return '36px';
      }
    },

    pixelsAboveFold(state) {
      return (index) => {
        let tbody = document.getElementById('tbody-' + index);
        if (!tbody) return false;
        let row = tbody.getClientRects()[0];
        return (row.top + row.height - 73) - state.containerTop;
      }
    },

    isInViewport() {
      return (index) => this.pixelsAboveFold(index) > -36;
    },
  },

  actions: {
    toggleTheme() {
      switch (this.theme) {
        case Theme.System:
          this.theme = Theme.Light;
          break;
        case Theme.Light:
          this.theme = Theme.Dark;
          break;
        default:
          this.theme = Theme.System;
          break;
      }

      this.syncTheme();
    },

    syncTheme() {
      const theme = this.theme;

      if (theme === Theme.Dark || (theme === Theme.System && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark')
      } else {
        document.documentElement.classList.remove('dark')
      }
    },

    toggle(index) {
      if (this.isOpen(index)) {
        this.stacksOpen = this.stacksOpen.filter(idx => idx !== index)
      } else {
        this.stacksOpen.push(index)
      }
      this.onScroll();
    },

    onScroll() {
      let vm = this;
      this.stacksOpen.forEach(function (index) {
        if (vm.isInViewport(index)) {
          if (!vm.stacksInView.includes(index)) {
            vm.stacksInView.push(index);
          }
          vm.stackTops[index] = vm.stickTopPosition(index);
        } else {
          vm.stacksInView = vm.stacksInView.filter(idx => idx !== index);
          delete vm.stackTops[index];
        }
      })
    },

    reset() {
      this.stacksOpen = [];
      this.stacksInView = [];
      this.stackTops = {};
      const container = document.querySelector('.log-item-container');
      if (!container) return;
      this.containerTop = container.getBoundingClientRect().top;
      container.scrollTo(0, 0);
    },

    loadLogs: debounce(function ({ silently = false } = {}) {
      const fileStore = useFileStore();
      const searchStore = useSearchStore();
      const paginationStore = usePaginationStore();
      const severityStore = useSeverityStore();

      // abort if the files are not ready yet
      if (fileStore.folders.length === 0) return;

      // abort the previous request which might now be outdated
      if (this.abortController) {
        this.abortController.abort();
      }

      this.abortController = new AbortController();

      const params = {
        file: this.selectedFile?.identifier,
        direction: this.direction,
        query: searchStore.query,
        page: paginationStore.currentPage,
        per_page: this.resultsPerPage,
        levels: severityStore.selectedLevels,
        shorter_stack_traces: this.shorterStackTraces,
      };

      if (!silently) {
        this.loading = true;
      }

      axios.get(`${LogViewer.basePath}/api/logs`, { params, signal: this.abortController.signal })
        .then(({ data }) => {
          this.logs = data.logs;
          this.hasMoreResults = data.hasMoreResults;
          this.percentScanned = data.percentScanned;
          this.error = data.error || null;
          this.performance = data.performance || {};
          severityStore.setLevelCounts(data.levelCounts);
          paginationStore.setPagination(data.pagination);

          this.loading = false;

          if (!silently) {
            nextTick(() => {
              this.reset();
              if (data.expandAutomatically) {
                this.stacksOpen.push(0);
              }
            });
          }

          if (this.hasMoreResults) {
            this.loadLogs({ silently: true });
          }
        })
        .catch((error) => {
          this.loading = false;
          this.error = error.message;

          if (error.response?.data?.message) {
            this.error += ': ' + error.response.data.message;
          }

          console.error(error);
        });
    }, 10),
  },
})
