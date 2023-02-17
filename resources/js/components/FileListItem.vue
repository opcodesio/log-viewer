<template>
  <div class="file-item-container flex" :class="[isSelected ? 'active' : '']">
    <Menu>
      <div class="file-item grow">
        <div v-if="logFile.can_delete" class="my-auto mr-2" v-show="fileStore.checkBoxesVisibility">
          <input type="checkbox"
                 @click.stop="toggleCheckbox"
                 :checked="fileStore.isChecked(logFile)"
                 :value="fileStore.isChecked(logFile)"
          />
        </div>
        <p class="file-name">{{ logFile.name }}</p>
        <span class="file-size">{{ logFile.size_formatted }}</span>

        <MenuButton @click.stop="calculateDropdownDirection($event.target)">
          <button type="button" class="file-dropdown-toggle" :data-toggle-id="logFile.identifier">
            <EllipsisVerticalIcon class="w-5 h-5 pointer-events-none" />
          </button>
        </MenuButton>
      </div>

      <MenuItems as="div" class="dropdown w-48" :class="[dropdownDirections[logFile.identifier]]">
        <div class="py-2">
          <MenuItem @click.stop.prevent="clearCacheForFile">
            <button>
              <CircleStackIcon v-show="!clearingCache" class="h-4 w-4 mr-2" />
              <SpinnerIcon v-show="clearingCache" />
              <span v-show="!cacheRecentlyCleared && !clearingCache">Clear index</span>
              <span v-show="!cacheRecentlyCleared && clearingCache">Clearing...</span>
              <span v-show="cacheRecentlyCleared" class="text-brand-500">Index cleared</span>
            </button>
          </MenuItem>

          <MenuItem v-if="logFile.can_download" @click.stop>
            <a :href="logFile.download_url" download>
              <CloudArrowDownIcon class="w-4 h-4 mr-2" />
              Download
            </a>
          </MenuItem>

          <template v-if="logFile.can_delete">
            <div class="divider"></div>

            <MenuItem @click.stop.prevent="confirmDeletion">
              <button>
                <TrashIcon class="w-4 h-4 mr-2" />
                Delete
              </button>
            </MenuItem>

            <MenuItem @click.stop="deleteMultiple">
              <button>
                <TrashIcon class="w-4 h-4 mr-2" />
                Delete Multiple
              </button>
            </MenuItem>
          </template>
        </div>
      </MenuItems>
    </Menu>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { CircleStackIcon, CloudArrowDownIcon, EllipsisVerticalIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { useFileStore } from '../stores/files.js';
import SpinnerIcon from './SpinnerIcon.vue';
import axios from 'axios';
import { useLogViewerStore } from '../stores/logViewer.js';
import { replaceQuery, useDropdownDirection } from '../helpers.js';
import { useRouter } from 'vue-router';

const props = defineProps({
  logFile: {
    type: Object,
    required: true,
  },
  showSelectToggle: {
    type: Boolean,
    default: false,
  },
})
const emit = defineEmits(['selectForDeletion']);
const fileStore = useFileStore();
const logViewerStore = useLogViewerStore();
const router = useRouter();
const { dropdownDirections, calculateDropdownDirection } = useDropdownDirection();

// data
const clearingCache = ref(false);
const cacheRecentlyCleared = ref(false);
const isSelected = computed(() => {
  return fileStore.selectedFile && fileStore.selectedFile.identifier === props.logFile.identifier;
})

const confirmDeletion = () => {
  if (confirm(`Are you sure you want to delete the log file '${props.logFile.name}'? THIS ACTION CANNOT BE UNDONE.`)) {
    axios.delete(`${LogViewer.basePath}/api/files/${props.logFile.identifier}`)
      .then(() => {
        if (props.logFile.identifier === fileStore.selectedFileIdentifier) {
          replaceQuery(router, 'file', null);
        }

        fileStore.loadFolders();
      })
  }
}

const toggleCheckbox = () => {
  fileStore.checkBoxToggle(props.logFile.identifier);
}

const deleteMultiple = () => {
  fileStore.toggleCheckboxVisibility();
  toggleCheckbox();
}

const clearCacheForFile = () => {
  clearingCache.value = true;

  axios.post(`${LogViewer.basePath}/api/files/${props.logFile.identifier}/clear-cache`)
    .then(() => {
      cacheRecentlyCleared.value = true;
      if (props.logFile.identifier === fileStore.selectedFileIdentifier) {
        logViewerStore.loadLogs();
      }
      setTimeout(() => cacheRecentlyCleared.value = false, 2000);
    })
    .catch((error) => {
      console.error(error);
    })
    .finally(() => clearingCache.value = false);
}
</script>
