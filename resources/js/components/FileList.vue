<template>
  <nav class="flex flex-col h-full py-5">
    <div class="mx-3 md:mx-0 mb-1">
      <div class="sm:flex sm:flex-col-reverse">
        <h1 class="font-semibold text-brand-700 dark:text-brand-600 text-2xl flex items-center">
          Log Viewer
          <a href="https://www.github.com/opcodesio/log-viewer" target="_blank"
             class="rounded ml-3 text-gray-400 hover:text-brand-800 dark:hover:text-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 dark:focus:ring-brand-700 p-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" title="">
              <path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"></path>
            </svg>
          </a>
          <span class="md:hidden flex-1 flex justify-end">
            <SiteSettingsDropdown class="ml-2" />
            <button type="button" class="menu-button">
              <XMarkIcon class="w-5 h-5 ml-2" @click="fileStore.toggleSidebar" />
            </button>
          </span>
        </h1>

        <div v-if="LogViewer.back_to_system_url">
          <a :href="LogViewer.back_to_system_url"
             class="rounded shrink inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-brand-800 dark:hover:text-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 dark:focus:ring-brand-700 mt-0">
            <ArrowLeftIcon class="h-3 w-3 mr-1.5" />
            {{ LogViewer.back_to_system_label || `Back to ${LogViewer.app_name}` }}
          </a>
        </div>
      </div>

      <div v-if="LogViewer.assets_outdated" class="bg-yellow-100 dark:bg-yellow-900 bg-opacity-75 dark:bg-opacity-40 border border-yellow-300 dark:border-yellow-800 rounded-md px-2 py-1 mt-2 text-xs leading-5 text-yellow-700 dark:text-yellow-400">
        <ExclamationTriangleIcon class="h-4 w-4 mr-1 inline" />
        Front-end assets are outdated. To update, please run <code class="font-mono px-2 py-1 bg-gray-100 dark:bg-gray-900 rounded">php artisan log-viewer:publish</code>
      </div>

      <template v-if="hostStore.supportsHosts && hostStore.hasRemoteHosts">
        <host-selector class="mb-8 mt-6" />
      </template>

      <div class="flex justify-between items-baseline mt-6" v-if="fileStore.folders?.length > 0">
        <div class="ml-1 block text-sm text-gray-500 dark:text-gray-400 truncate">Log files on {{ fileStore.selectedHost?.name }}</div>
        <div class="text-sm text-gray-500 dark:text-gray-400">
          <label for="file-sort-direction" class="sr-only">Sort direction</label>
          <select id="file-sort-direction" class="select" v-model="fileStore.direction">
            <option value="desc">Newest first</option>
            <option value="asc">Oldest first</option>
          </select>
        </div>
      </div>

      <p v-if="fileStore.error" class="mx-1 mt-1 text-red-600 text-xs">
        {{ fileStore.error }}
      </p>
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
            >
              <div class="file-item group">
                <button class="file-item-info group" @keydown="handleKeyboardFileNavigation">
                  <span class="sr-only" v-if="!fileStore.isOpen(folder)">Open folder</span>
                  <span class="sr-only" v-if="fileStore.isOpen(folder)">Close folder</span>
                  <span class="file-icon group-hover:hidden group-focus:hidden">
                    <FolderIcon v-show="!fileStore.isOpen(folder)" class="w-5 h-5" />
                    <FolderOpenIcon v-show="fileStore.isOpen(folder)" class="w-5 h-5" />
                  </span>
                  <span class="file-icon hidden group-hover:inline-block group-focus:inline-block">
                    <ChevronRightIcon :class="[fileStore.isOpen(folder) ? 'rotate-90' : '', 'transition duration-100']" />
                  </span>
                  <span class="file-name">
                    <span v-if="String(folder.clean_path || '').startsWith('root')">
                      <span class="text-gray-500 dark:text-gray-400">root</span>{{ String(folder.clean_path).substring(4) }}
                    </span>
                    <span v-else>{{ folder.clean_path }}</span>
                  </span>
                </button>

                <MenuButton as="button" class="file-dropdown-toggle group-hover:border-brand-600 group-hover:dark:border-brand-800"
                            :data-toggle-id="folder.identifier"
                            @keydown="handleKeyboardFileSettingsNavigation"
                            @click.stop="calculateDropdownDirection($event.target)">
                  <span class="sr-only">Open folder options</span>
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
                <MenuItems static v-show="open" as="div" class="dropdown w-48" :class="[dropdownDirections[folder.identifier]]">
                  <div class="py-2">
                    <MenuItem @click.stop.prevent="fileStore.clearCacheForFolder(folder)" v-slot="{ active }">
                      <button :class="[active ? 'active' : '']">
                        <CircleStackIcon v-show="!fileStore.clearingCache[folder.identifier]" class="w-4 h-4 mr-2"/>
                        <SpinnerIcon v-show="fileStore.clearingCache[folder.identifier]" class="w-4 h-4 mr-2" />
                        <span v-show="!fileStore.cacheRecentlyCleared[folder.identifier] && !fileStore.clearingCache[folder.identifier]">Clear indices</span>
                        <span v-show="!fileStore.cacheRecentlyCleared[folder.identifier] && fileStore.clearingCache[folder.identifier]">Clearing...</span>
                        <span v-show="fileStore.cacheRecentlyCleared[folder.identifier]" class="text-brand-500">Indices cleared</span>
                      </button>
                    </MenuItem>

                    <MenuItem v-if="folder.can_download" v-slot="{ active }">
                      <a :href="folder.download_url" download @click.stop :class="[active ? 'active' : '']">
                        <CloudArrowDownIcon class="w-4 h-4 mr-2"/>
                        Download
                      </a>
                    </MenuItem>

                    <template v-if="folder.can_delete">
                      <div class="divider"></div>
                      <MenuItem v-slot="{ active }">
                        <button @click.stop="confirmDeleteFolder(folder)" :disabled="fileStore.deleting[folder.identifier]" :class="[active ? 'active' : '']">
                          <TrashIcon v-show="!fileStore.deleting[folder.identifier]" class="w-4 h-4 mr-2" />
                          <SpinnerIcon v-show="fileStore.deleting[folder.identifier]" />
                          Delete
                        </button>
                      </MenuItem>
                    </template>
                  </div>
                </MenuItems>
              </transition>
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

        <div v-if="fileStore.folders.length === 0" class="text-center text-sm text-gray-600 dark:text-gray-400">
          <p class="mb-5">No log files were found.</p>
          <div class="flex items-center justify-center px-1">
            <button @click.prevent="fileStore.loadFolders()"
                    class="inline-flex items-center px-4 py-2 text-left text-sm bg-white hover:bg-gray-50 outline-brand-500 dark:outline-brand-800 text-gray-900 dark:text-gray-200 rounded-md dark:bg-gray-700 dark:hover:bg-gray-600"
            >
              <ArrowPathIcon class="w-4 h-4 mr-1.5" />
              Refresh file list
            </button>
          </div>
        </div>
      </div>

      <!-- gradient to hide the bottom of the file list -->
      <div class="pointer-events-none absolute z-10 bottom-0 h-4 w-full bg-gradient-to-t from-gray-100 dark:from-gray-900 to-transparent"></div>

      <!-- loading state overlay -->
      <div class="absolute inset-y-0 left-3 right-7 lg:left-0 lg:right-0 z-10" v-show="fileStore.loading">
        <div class="rounded-md bg-white text-gray-800 dark:bg-gray-700 dark:text-gray-200 opacity-90 w-full h-full flex items-center justify-center">
          <SpinnerIcon class="w-14 h-14" />
        </div>
        </div>
    </div>
  </nav>
</template>

<script setup>
import { onMounted, watch } from 'vue';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import {
  ArrowLeftIcon,
  ArrowPathIcon,
  CircleStackIcon,
  CloudArrowDownIcon,
  EllipsisVerticalIcon,
  ExclamationTriangleIcon,
  FolderIcon,
  FolderOpenIcon,
  TrashIcon,
  XMarkIcon,
  ChevronRightIcon,
} from '@heroicons/vue/24/outline';
import { useHostStore } from '../stores/hosts.js';
import { useFileStore } from '../stores/files.js';
import { useRoute, useRouter } from 'vue-router';
import { replaceQuery, useDropdownDirection } from '../helpers.js';
import FileListItem from './FileListItem.vue';
import SpinnerIcon from './SpinnerIcon.vue';
import SiteSettingsDropdown from './SiteSettingsDropdown.vue';
import HostSelector from './HostSelector.vue';
import { handleKeyboardFileNavigation, handleKeyboardFileSettingsNavigation } from '../keyboardNavigation';

const router = useRouter();
const route = useRoute();
const hostStore = useHostStore();
const fileStore = useFileStore();
const { dropdownDirections, calculateDropdownDirection } = useDropdownDirection();

const confirmDeleteFolder = async (folder) => {
  if (confirm(`Are you sure you want to delete the log folder '${folder.path}'? THIS ACTION CANNOT BE UNDONE.`)) {
    await fileStore.deleteFolder(folder);

    if (folder.files.some(file => file.identifier === fileStore.selectedFileIdentifier)) {
      replaceQuery(router, 'file', null);
    }
  }
}

const confirmDeleteSelectedFiles = async () => {
  if (confirm('Are you sure you want to delete selected log files? THIS ACTION CANNOT BE UNDONE.')) {
    await fileStore.deleteSelectedFiles();

    if (fileStore.filesChecked.includes(fileStore.selectedFileIdentifier)) {
      replaceQuery(router, 'file', null);
    }

    fileStore.resetChecks();
    await fileStore.loadFolders();
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
  hostStore.selectHost(route.query.host || null);
});

watch(
  () => fileStore.direction,
  () => fileStore.loadFolders()
);
</script>
