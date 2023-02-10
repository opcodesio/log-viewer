import { defineStore } from 'pinia';

export const Theme = {
  System: 'System',
  Light: 'Light',
  Dark: 'Dark',
}

export const useLogViewerStore = defineStore({
  id: 'logViewer',

  state: () => ({
    theme: Theme.System,
    stacksOpen: [],
    stacksInView: [],
    stackTops: {},
    containerTop: 0,
  }),

  getters: {
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
          return this.theme = Theme.Light;
        case Theme.Light:
          return this.theme = Theme.Dark;
        default:
          return this.theme = Theme.System;
      }
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
      const container = document.getElementById('log-item-container');
      this.containerTop = container.getBoundingClientRect().top;
      container.scrollTo(0, 0);
    },
  },
})
