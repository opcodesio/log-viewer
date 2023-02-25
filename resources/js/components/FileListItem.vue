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
          <MenuItem @click.stop.prevent="fileStore.clearCacheForFile(logFile)">
            <button>
              <CircleStackIcon v-show="!fileStore.clearingCache[logFile.identifier]" class="h-4 w-4 mr-2" />
              <SpinnerIcon v-show="fileStore.clearingCache[logFile.identifier]" />
              <span v-show="!fileStore.cacheRecentlyCleared[logFile.identifier] && !fileStore.clearingCache[logFile.identifier]">Clear index</span>
              <span v-show="!fileStore.cacheRecentlyCleared[logFile.identifier] && fileStore.clearingCache[logFile.identifier]">Clearing...</span>
              <span v-show="fileStore.cacheRecentlyCleared[logFile.identifier]" class="text-brand-500">Index cleared</span>
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
import { computed } from 'vue';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { CircleStackIcon, CloudArrowDownIcon, EllipsisVerticalIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { useFileStore } from '../stores/files.js';
import SpinnerIcon from './SpinnerIcon.vue';
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
const router = useRouter();
const { dropdownDirections, calculateDropdownDirection } = useDropdownDirection();

// data
const isSelected = computed(() => {
  return fileStore.selectedFile && fileStore.selectedFile.identifier === props.logFile.identifier;
})

const confirmDeletion = async () => {
  if (confirm(`Are you sure you want to delete the log file '${props.logFile.name}'? THIS ACTION CANNOT BE UNDONE.`)) {
    await fileStore.deleteFile(props.logFile);

    if (props.logFile.identifier === fileStore.selectedFileIdentifier) {
      replaceQuery(router, 'file', null);
    }

    await fileStore.loadFolders();
  }
}

const toggleCheckbox = () => {
  fileStore.checkBoxToggle(props.logFile.identifier);
}

const deleteMultiple = () => {
  fileStore.toggleCheckboxVisibility();
  toggleCheckbox();
}
</script>
