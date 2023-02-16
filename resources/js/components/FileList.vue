<template>
  <nav class="flex flex-col h-full py-5">
    <div class="mx-3 mb-2">
      <h1 class="font-semibold text-brand-700 dark:text-brand-600 text-2xl flex items-center">
        Log Viewer
        <a href="https://www.github.com/opcodesio/log-viewer" target="_blank"
           class="rounded ml-3 text-gray-400 hover:text-brand-800 dark:hover:text-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 dark:focus:ring-brand-700 p-1">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"></path>
          </svg>
        </a>
      </h1>

      <a v-if="LogViewer.back_to_system_url" :href="LogViewer.back_to_system_url"
         class="rounded inline-flex items-center text-sm text-gray-400 hover:text-brand-800 dark:hover:text-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 dark:focus:ring-brand-700 mt-3">
        <ArrowLeftIcon class="h-3 w-3 mr-1.5" />
        {{ LogViewer.back_to_system_label || `Back to ${LogViewer.app_name}` }}
      </a>

      <div class="flex justify-between mt-4 mr-1">
        <div class="relative">
          <div v-show="scanInProgress"
               class="flex items-center text-sm text-gray-500 dark:text-gray-400">
            <SpinnerIcon class="h-4 w-4 inline mr-1" />
            Indexing logs...
          </div>
        </div>
        <div class="text-sm text-gray-500 dark:text-gray-400">
          <label for="file-sort-direction" class="sr-only">Sort direction</label>
          <select id="file-sort-direction" v-model="fileStore.direction"
                  class="bg-gray-100 dark:bg-gray-900 px-2 font-normal outline-none rounded focus:ring-2 focus:ring-brand-500 dark:focus:ring-brand-700">
            <option value="desc">Newest first</option>
            <option value="asc">Oldest first</option>
          </select>
        </div>
      </div>
    </div>

    <div v-show="fileStore.checkBoxesVisibility">
      <p class="text-sm text-gray-600 dark:text-gray-400">Please select files to delete and confirm or cancel deletion.</p>
      <div class="grid grid-flow-col pr-4 mt-2"
           :class="[fileStore.hasFilesChecked ? 'justify-between' : 'justify-end']"
      >
        <button v-show="fileStore.hasFilesChecked"
                @click.stop="confirmDeleteSelectedFiles"
                class="button inline-flex">
          <TrashIcon class="w-5 mr-1" />
          Delete selected files
        </button>
        <button class="button inline-flex" @click.stop="fileStore.resetChecks()">
          Cancel
          <XMarkIcon class="w-5 ml-1" />
        </button>
      </div>
    </div>

    <div id="file-list-container" class="relative h-full overflow-hidden">
      <div class="pointer-events-none absolute z-10 top-0 h-4 w-full bg-gradient-to-b from-gray-100 dark:from-gray-900 to-transparent"></div>

      <div class="file-list" @scroll="(event) => fileStore.onScroll(event)">
        <div v-for="folder in fileStore.folders"
             :key="folder.identifier"
             :id="`folder-${folder.identifier}`"
             class="relative folder-container"
        >
          <Menu v-slot="{ open }">
            <div class="folder-item-container"
                 @click="fileStore.toggle(folder)"
                 :class="[fileStore.isOpen(folder) ? 'active-folder' : '', fileStore.shouldBeSticky(folder) ? 'sticky ' + (open ? 'z-20' : 'z-10') : '' ]"
                 :style="{ top: fileStore.isOpen(folder) ? (fileStore.folderTops[folder] || 0) : 0 }"
            >
              <div class="file-item">
                <div class="file-icon">
                  <FolderIcon v-show="!fileStore.isOpen(folder)" class="w-5 h-5" />
                  <FolderOpenIcon v-show="fileStore.isOpen(folder)" class="w-5 h-5" />
                </div>
                <div class="file-name">
                  <span v-if="String(folder.clean_path || '').startsWith('root')">
                    <span class="text-gray-500 dark:text-gray-400">root</span>{{ String(folder.clean_path).substring(4) }}
                  </span>
                  <span v-else>{{ folder.clean_path }}</span>
                </div>

                <MenuButton @click.stop="calculateDropdownDirection($event.target)">
                  <button type="button" class="file-dropdown-toggle" :data-toggle-id="folder.identifier">
                    <EllipsisVerticalIcon class="w-5 h-5 pointer-events-none" />
                  </button>
                </MenuButton>
              </div>

              <MenuItems static v-show="open" as="div" class="dropdown w-48" :class="[dropdownDirections[folder.identifier]]">
                <div class="py-2">
                  <MenuItem as="button" @click.stop.prevent="clearCacheForFolder(folder)">
                    <CircleStackIcon v-show="!clearingCache[folder.identifier]" class="w-4 h-4 mr-2"/>
                    <SpinnerIcon v-show="clearingCache[folder.identifier]" class="w-4 h-4 mr-2" />
                    <span v-show="!cacheRecentlyCleared[folder.identifier] && !clearingCache[folder.identifier]">Clear indices</span>
                    <span v-show="!cacheRecentlyCleared[folder.identifier] && clearingCache[folder.identifier]">Clearing...</span>
                    <span v-show="cacheRecentlyCleared[folder.identifier]" class="text-brand-500">Indices cleared</span>
                  </MenuItem>

                  <MenuItem v-if="folder.can_download">
                    <a :href="folder.download_url" download @click.stop>
                      <CloudArrowDownIcon class="w-4 h-4 mr-2"/>
                      Download
                    </a>
                  </MenuItem>

                  <template v-if="folder.can_delete">
                    <div class="divider"></div>
                    <MenuItem>
                      <button @click.stop="confirmDeleteFolder(folder)" :disabled="deleting[folder.identifier]">
                        <TrashIcon v-show="!deleting[folder.identifier]" class="w-4 h-4 mr-2" />
                        <SpinnerIcon v-show="deleting[folder.identifier]" />
                        Delete
                      </button>
                    </MenuItem>
                  </template>
                </div>
              </MenuItems>
            </div>
          </Menu>

          <div class="folder-files pl-3 ml-1 border-l border-gray-200 dark:border-gray-800"
               v-show="fileStore.isOpen(folder)">
            <file-list-item
              v-for="logFile in (folder.files || [])"
              :key="logFile.identifier"
              :log-file="logFile"
              @click="selectFile(logFile.identifier)"
            />
          </div>
        </div>
      </div>
      <div class="pointer-events-none absolute z-10 bottom-0 h-4 w-full bg-gradient-to-t from-gray-100 dark:from-gray-900 to-transparent"></div>
    </div>
  </nav>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import {
  ArrowLeftIcon,
  CircleStackIcon,
  CloudArrowDownIcon,
  EllipsisVerticalIcon,
  FolderIcon,
  FolderOpenIcon,
  TrashIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline';
import { useFileStore } from '../stores/files.js';
import FileListItem from './FileListItem.vue';
import SpinnerIcon from './SpinnerIcon.vue';
import { useRoute, useRouter } from 'vue-router';
import { useSearchStore } from '../stores/search.js';
import { useLogViewerStore } from '../stores/logViewer.js';
import { replaceQuery, useDropdownDirection } from '../helpers.js';
import axios from 'axios';

const router = useRouter();
const route = useRoute();
const fileStore = useFileStore();
const searchStore = useSearchStore();
const logViewerStore = useLogViewerStore();
const scanInProgress = ref(false);
const { dropdownDirections, calculateDropdownDirection } = useDropdownDirection();

const cacheRecentlyCleared = ref({});
const clearingCache = ref({});
const clearCacheForFolder = (folder) => {
  clearingCache.value[folder.identifier] = true;

  axios.post(`${LogViewer.basePath}/api/folders/${folder.identifier}/clear-cache`)
    .then(() => {
      if (folder.files.some(file => file.identifier === fileStore.selectedFileIdentifier)) {
        logViewerStore.loadLogs();
      }

      cacheRecentlyCleared.value[folder.identifier] = true;
      setTimeout(() => cacheRecentlyCleared.value[folder.identifier] = false, 2000);
    })
    .catch((error) => console.error(error))
    .finally(() => {
      clearingCache.value[folder.identifier] = false;
    })

}

const deleting = ref({});
const confirmDeleteFolder = (folder) => {
  if (confirm(`Are you sure you want to delete the log folder '${folder.path}'? THIS ACTION CANNOT BE UNDONE.`)) {
    deleting.value[folder.identifier] = true;

    axios.delete(`${LogViewer.basePath}/api/folders/${folder.identifier}`)
      .then(() => {
        if (folder.files.some(file => file.identifier === fileStore.selectedFileIdentifier)) {
          replaceQuery(router, 'file', null);
        }

        fileStore.loadFolders();
      })
      .catch((error) => console.error(error))
      .finally(() => {
        deleting.value[folder.identifier] = false;
      })
  }
}

const confirmDeleteSelectedFiles = () => {
  if (confirm('Are you sure you want to delete selected log files? THIS ACTION CANNOT BE UNDONE.')) {
    axios.post(`${LogViewer.basePath}/api/delete-multiple-files`, {
      files: fileStore.filesChecked
    }).then(() => {
      if (fileStore.filesChecked.includes(fileStore.selectedFileIdentifier)) {
        replaceQuery(router, 'file', null);
      }

      fileStore.resetChecks();
      fileStore.loadFolders();
    });
  }
}

const selectFile = (fileIdentifier) => {
  if (route.query.file && route.query.file === fileIdentifier) {
    replaceQuery(router, 'file', null);
  } else {
    replaceQuery(router, 'file', fileIdentifier);
  }
};

onMounted(async () => {
  await fileStore.loadFolders();

  if (fileStore.selectedFile || searchStore.hasQuery) {
    logViewerStore.loadLogs();
  }
});

watch(
  () => fileStore.direction,
  () => fileStore.loadFolders()
);
</script>
