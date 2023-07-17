import { defineStore } from 'pinia';
import { useLocalStorage } from '@vueuse/core';

export const useSeverityStore = defineStore({
  id: 'severity',

  state: () => ({
    allLevels: [],  // should be updated by the backend
    excludedLevels: useLocalStorage('excludedLevels', []),
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

      this.allLevels = levelCounts.map(levelCount => levelCount.level);
    },

    selectAllLevels() {
      this.excludedLevels = [];
      this.levelCounts.forEach(levelCount => levelCount.selected = true);
    },

    deselectAllLevels() {
      this.excludedLevels = this.allLevels;
      this.levelCounts.forEach(levelCount => levelCount.selected = false);
    },

    toggleLevel(level) {
      const levelCount = this.levelCounts.find(levelCount => levelCount.level === level) || {};

      if (this.excludedLevels.includes(level)) {
        this.excludedLevels = this.excludedLevels.filter(excludedLevel => excludedLevel !== level);
        levelCount.selected = true;
      } else {
        this.excludedLevels.push(level);
        levelCount.selected = false;
      }
    },
  },
})
