<template>
  <div class="flex items-center">
    <Menu as="div" class="mr-5 relative log-levels-selector">

      <MenuButton as="button" id="severity-dropdown-toggle" class="dropdown-toggle badge none" :class="severityStore.levelsSelected.length > 0 ? 'active' : ''">
        <template v-if="severityStore.levelsSelected.length > 2">
          <span class="opacity-90 mr-1">{{ severityStore.totalResultsSelected.toLocaleString() + (logViewerStore.hasMoreResults ? '+' : '') }} entries in</span>
          <strong class="font-semibold">{{ severityStore.levelsSelected[0].level_name }} + {{ severityStore.levelsSelected.length - 1 }} more</strong>
        </template>
        <template v-else-if="severityStore.levelsSelected.length > 0">
          <span class="opacity-90 mr-1">{{ severityStore.totalResultsSelected.toLocaleString() + (logViewerStore.hasMoreResults ? '+' : '') }} entries in</span>
          <strong class="font-semibold">{{ severityStore.levelsSelected.map(levelCount => levelCount.level_name).join(', ') }}</strong>
        </template>
        <span v-else-if="severityStore.levelsFound.length > 0" class="opacity-90">{{ severityStore.totalResults.toLocaleString() + (logViewerStore.hasMoreResults ? '+' : '') }} entries found. None selected</span>
        <span v-else class="opacity-90">No entries found</span>

        <ChevronDownIcon class="w-4 h-4" />
      </MenuButton>

      <transition
        leave-active-class="transition ease-in duration-100"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-90"
        enter-active-class="transition ease-out duration-100"
        enter-from-class="opacity-0 scale-90"
        enter-to-class="opacity-100 scale-100"
      >
        <MenuItems as="div" class="dropdown down left min-w-[240px]">
          <div class="py-2">
            <div class="label flex justify-between">
              Severity
              <template v-if="severityStore.levelsFound.length > 0">
                <MenuItem v-if="severityStore.levelsSelected.length === severityStore.levelsFound.length" @click.stop="severityStore.deselectAllLevels" v-slot="{ active }">
                  <a class="inline-link px-2 -mr-2 py-1 -my-1 rounded-md cursor-pointer text-brand-700 dark:text-brand-500 font-normal" :class="[active ? 'active' : '']">
                    Deselect all
                  </a>
                </MenuItem>
                <MenuItem v-else @click.stop="severityStore.selectAllLevels" v-slot="{ active }">
                  <a class="inline-link px-2 -mr-2 py-1 -my-1 rounded-md cursor-pointer text-brand-700 dark:text-brand-500 font-normal" :class="[active ? 'active' : '']">
                    Select all
                  </a>
                </MenuItem>
              </template>
            </div>

            <template v-if="severityStore.levelsFound.length === 0">
              <div class="no-results">There are no severity filters to display because no entries have been found.</div>
            </template>

            <template v-else>
              <MenuItem v-for="levelCount in severityStore.levelsFound"
                        @click.stop.prevent="severityStore.toggleLevel(levelCount.level)"
                        v-slot="{ active }"
              >
                <button :class="[active ? 'active' : '']">
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
      </transition>
    </Menu>
  </div>
</template>

<script setup>
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { ChevronDownIcon } from '@heroicons/vue/24/outline';
import Checkmark from './Checkmark.vue';
import { useLogViewerStore } from '../stores/logViewer.js';
import { useSeverityStore } from '../stores/severity.js';
import { watch } from 'vue';

const logViewerStore = useLogViewerStore();
const severityStore = useSeverityStore();

watch(
  () => severityStore.selectedLevels,
  () => logViewerStore.loadLogs()
);
</script>
