<template>
  <div class="file-item-container" :class="[isSelected ? 'active' : '']">
    <Menu>
      <div class="file-item group">
        <button class="file-item-info" @keydown="handleKeyboardFileNavigation">
          <span class="sr-only" v-if="!isSelected">Select log file</span>
          <span class="sr-only" v-if="isSelected">Deselect log file</span>
          <span v-if="logFile.can_delete" class="my-auto mr-2" v-show="fileStore.checkBoxesVisibility">
            <input type="checkbox"
                   @click.stop="toggleCheckbox"
                   :checked="fileStore.isChecked(logFile)"
                   :value="fileStore.isChecked(logFile)"
            />
          </span>
          <span class="file-name"><span class="sr-only">Name:</span>{{ logFile.name }}</span>
          <span class="file-size"><span class="sr-only">Size:</span>{{ logFile.size_formatted }}</span>
        </button>

        <MenuButton as="button" class="file-dropdown-toggle group-hover:border-brand-600 group-hover:dark:border-brand-800"
                    :data-toggle-id="logFile.identifier"
                    @keydown="handleKeyboardFileSettingsNavigation"
                    @click.stop="calculateDropdownDirection($event.target)">
          <EllipsisVerticalIcon class="w-4 h-4 pointer-events-none" />
        </MenuButton>
      </div>

      <transition
        leave-active-class="transition ease-in duration-100"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-90"
        enter-active-class="transition ease-out duration-100"
        enter-from-class="opacity-0 scale-90"
        enter-to-class="opacity-100 scale-100"
      >
        <MenuItems as="div" class="dropdown w-48" :class="[dropdownDirections[logFile.identifier]]">
          <div class="py-2">
            <MenuItem @click.stop.prevent="fileStore.clearCacheForFile(logFile)" v-slot="{ active }">
              <button :class="[active ? 'active' : '']">
                <CircleStackIcon v-show="!fileStore.clearingCache[logFile.identifier]" class="h-4 w-4 mr-2" />
                <SpinnerIcon v-show="fileStore.clearingCache[logFile.identifier]" />
                <span v-show="!fileStore.cacheRecentlyCleared[logFile.identifier] && !fileStore.clearingCache[logFile.identifier]">Clear index</span>
                <span v-show="!fileStore.cacheRecentlyCleared[logFile.identifier] && fileStore.clearingCache[logFile.identifier]">Clearing...</span>
                <span v-show="fileStore.cacheRecentlyCleared[logFile.identifier]" class="text-brand-500">Index cleared</span>
              </button>
            </MenuItem>

            <MenuItem v-if="logFile.can_download" @click.stop v-slot="{ active }">
              <DownloadLink :url="logFile.download_url" :class="[active ? 'active' : '']" />
            </MenuItem>

            <template v-if="logFile.can_delete">
              <div class="divider"></div>

              <MenuItem @click.stop.prevent="confirmDeletion" v-slot="{ active }">
                <button :class="[active ? 'active' : '']">
                  <TrashIcon class="w-4 h-4 mr-2" />
                  Delete
                </button>
              </MenuItem>

              <MenuItem @click.stop="deleteMultiple" v-slot="{ active }">
                <button :class="[active ? 'active' : '']">
                  <TrashIcon class="w-4 h-4 mr-2" />
                  Delete Multiple
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
import { computed } from 'vue';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { CircleStackIcon, EllipsisVerticalIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { useFileStore } from '../stores/files.js';
import SpinnerIcon from './SpinnerIcon.vue';
import { replaceQuery, useDropdownDirection } from '../helpers.js';
import { useRouter } from 'vue-router';
import { handleKeyboardFileNavigation, handleKeyboardFileSettingsNavigation } from '../keyboardNavigation';
import DownloadLink from "./DownloadLink.vue";

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
