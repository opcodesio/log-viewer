<template>
  <div class="file-item-container flex" :class="[isSelected ? 'active' : '']">
    <Menu>
      <div class="file-item grow">
        <div v-if="logFile.can_delete" class="my-auto mr-2" v-show="showSelectToggle">
          <input type="checkbox"
                 @click.stop="emit('selectForDeletion', logFile)"
                 :value="logFile.selected_for_deletion" />
        </div>
        <p class="file-name">{{ logFile.name }}</p>
        <span class="file-size">{{ logFile.size_formatted }}</span>

        <MenuButton @click.stop>
          <button type="button" class="file-dropdown-toggle">
            <EllipsisVerticalIcon class="w-5 h-5" />
          </button>
        </MenuButton>
      </div>

      <MenuItems as="div" class="dropdown down w-48">
        <div class="py-2">
          <MenuItem @click.stop.prevent="clearCacheForFile">
            <button>
              <CircleStackIcon v-show="!loading" class="h-4 w-4 mr-2" />
              <SpinnerIcon v-show="loading" class="spin" />
              <span v-show="!cacheRecentlyCleared && !loading">Clear index</span>
              <span v-show="!cacheRecentlyCleared && loading">Clearing...</span>
              <span v-show="cacheRecentlyCleared" class="text-emerald-500">Index cleared</span>
            </button>
          </MenuItem>

          <MenuItem v-if="logFile.can_download" @click.stop.prevent>
            <a :href="logFile.download_url">
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

            <MenuItem @click.stop.prevent="deleteMultiple">
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
import { useFileViewerStore } from '../stores/fileViewer.js';
import SpinnerIcon from './SpinnerIcon.vue';

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
const fileViewerStore = useFileViewerStore();

// data
const loading = ref(false);
const cacheRecentlyCleared = ref(false);
const isSelected = computed(() => {
  return fileViewerStore.selectedFile && fileViewerStore.selectedFile.identifier === props.logFile.identifier;
})

const confirmDeletion = () => {
  if (confirm(`Are you sure you want to delete the log file '${props.logFile.name}'? THIS ACTION CANNOT BE UNDONE.`)) {
    // $wire.call('deleteFile', '{{ $logFile->identifier }}')
  }
}

const deleteMultiple = () => {
  fileViewerStore.toggleCheckboxVisibility();
  fileViewerStore.checkBoxToggle(props.logFile.identifier);
}

const clearCacheForFile = () => {
  loading.value = true;

  // clear the cache

  loading.value = false;
  cacheRecentlyCleared.value = true;
  setTimeout(() => cacheRecentlyCleared.value = false, 2000);
}
</script>
