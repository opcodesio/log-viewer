import { defineStore } from 'pinia';
import { useLocalStorage } from '@vueuse/core';

const DefaultSeverities = [
  'debug',
  'info',
  'notice',
  'warning',
  'error',
  'critical',
  'alert',
  'emergency',
  'processing',
  'processed',
  'failed',
  '',
];

export const useSeverityStore = defineStore({
  id: 'severity',

  state: () => ({
    selectedLevels: useLocalStorage('selectedLevels', DefaultSeverities),
    levelCounts: [],
  }),

  getters: {
    levelsFound: (state) => (state.levelCounts || []).filter(level => level.count > 0),

    totalResults() {
      return this.levelsFound.reduce((total, level) => total + level.count, 0);
    },

    levelsSelected() {
      return this.levelsFound.filter(levelCount => levelCount.selected);
    },

    totalResultsSelected() {
      return this.levelsSelected.reduce((total, level) => total + level.count, 0);
    },
  },

  actions: {
    setLevelCounts(levelCounts) {
      if (levelCounts.hasOwnProperty('length')) {
        this.levelCounts = levelCounts;
      } else {
        this.levelCounts = Object.values(levelCounts);
      }
    },

    selectAllLevels() {
      this.selectedLevels = DefaultSeverities;
      this.levelCounts.forEach(levelCount => levelCount.selected = true);
    },

    deselectAllLevels() {
      this.selectedLevels = [];
      this.levelCounts.forEach(levelCount => levelCount.selected = false);
    },

    toggleLevel(level) {
      const levelCount = this.levelCounts.find(levelCount => levelCount.level === level) || {};

      if (this.selectedLevels.includes(level)) {
        this.selectedLevels = this.selectedLevels.filter(selectedLevel => selectedLevel !== level);
        levelCount.selected = false;
      } else {
        this.selectedLevels.push(level);
        levelCount.selected = true;
      }
    },
  },
})
