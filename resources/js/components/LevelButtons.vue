<template>
  <div class="flex items-center">
    <Menu as="div" class="mr-5 relative log-levels-selector">

      <MenuButton>
        <button type="button" class="dropdown-toggle badge none" :class="levelsSelected.length > 0 ? 'active' : ''">
          <template v-if="levelsSelected.length > 2">
            <span class="opacity-90 mr-1">{{ totalResultsSelected.toLocaleString() + (hasMoreResults ? '+' : '') }} entries in</span>
            <strong class="font-semibold">{{ levelsSelected[0].level_name }} + {{ levelsSelected.length - 1 }} more</strong>
          </template>
          <template v-else-if="levelsSelected.length > 0">
            <span class="opacity-90 mr-1">{{ totalResultsSelected.toLocaleString() + (hasMoreResults ? '+' : '') }} entries in</span>
            <strong class="font-semibold">{{ levelsSelected.map(levelCount => levelCount.level_name).join(', ') }}</strong>
          </template>
          <span v-else-if="levelsFound.length > 0" class="opacity-90">{{ totalResults.toLocaleString() + (hasMoreResults ? '+' : '') }} entries found. None selected</span>
          <span v-else class="opacity-90">No entries found</span>

          <ChevronDownIcon class="w-4 h-4" />
        </button>
      </MenuButton>

      <MenuItems as="div" class="dropdown down left min-w-[200px]">
        <div class="py-2">
          <div class="label flex justify-between">
            Severity
            <template v-if="levelsFound.length > 0">
              <span v-if="levelsSelected.length === levelsFound.length" @click.stop="deselectAllLevels"
                    class="cursor-pointer text-sky-700 dark:text-sky-500 font-normal hover:text-sky-800 dark:hover:text-sky-400">Deselect all</span>
              <span v-else @click.stop="selectAllLevels"
                    class="cursor-pointer text-sky-700 dark:text-sky-500 font-normal hover:text-sky-800 dark:hover:text-sky-400">Select all</span>
            </template>
          </div>

          <template v-if="levelsFound.length === 0">
            <div class="no-results">There are no severity filters to display because no entries have been found.</div>
          </template>

          <template v-else>
            <MenuItem v-for="levelCount in levelsFound">
              <button @click="toggleLevel(levelCount.level)">
                <Checkmark class="checkmark mr-2.5" :checked="levelCount.selected" />
                <span class="flex-1 inline-flex justify-between">
                  <span :class="['log-level', levelCount.level_class]">{{ levelCount.level_name }}</span>
                  <span class="log-count">{{ Number(levelCount.count).toLocaleString() }}</span>
                </span>
              </button>
            </MenuItem>
          </template>
        </div>
      </MenuItems>
    </Menu>
  </div>
</template>

<script setup>
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { ChevronDownIcon } from '@heroicons/vue/24/outline';
import Checkmark from './Checkmark.vue';
import { useLogViewerStore } from '../stores/logViewer.js';
import { mapState } from 'pinia';

const {
  totalResults,
  hasMoreResults,
  levelsFound,
  levelsSelected,
  totalResultsSelected,
} = mapState(useLogViewerStore, [
  'totalResults',
  'hasMoreResults',
  'levelsFound',
  'levelsSelected',
  'totalResultsSelected',
]);
</script>
