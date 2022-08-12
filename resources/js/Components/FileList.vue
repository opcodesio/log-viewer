<template>
    <div class="relative overflow-hidden">
        <div class="absolute top-0 h-6 w-full bg-gradient-to-b from-gray-100 to-transparent"></div>
        <div class="relative h-full overflow-y-scroll py-6 pr-4">
            <div v-for="file in files" :key="file.name"
                 @click.prevent="selectFile(file)"
                 class="mb-2 text-gray-800 rounded-md bg-white transition duration-100 border-2 border-transparent hover:border-emerald-600 cursor-pointer"
                 :class="{ 'border-emerald-500': selectedFile && selectedFile.name === file.name }"
            >
                <div
                    class="relative flex justify-between items-center pl-4 pr-10 py-2">
                    <p class="text-sm mr-3 whitespace-nowrap flex-1">{{ file.name }}</p>
                    <span class="text-xs text-gray-500 whitespace-nowrap">{{ file.size_formatted }}</span>

                    <Menu as="div" class="inline-block text-left">
                        <div>
                            <MenuButton @click.stop
                                class="absolute top-0 right-0 bottom-0 w-8 flex items-center justify-center rounded-r-md border-l-2 border-transparent text-gray-500 hover:border-emerald-600 hover:bg-emerald-50 transition duration-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20"
                                     fill="currentColor">
                                    <path
                                        d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                </svg>
                            </MenuButton>
                        </div>

                        <transition enter-active-class="transition ease-out duration-100"
                                    enter-from-class="transform opacity-0 scale-95"
                                    enter-to-class="transform opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="transform opacity-100 scale-100"
                                    leave-to-class="transform opacity-0 scale-95">
                            <MenuItems
                                class="origin-top-right absolute right-0 mt-2 z-20 w-56 rounded-md bg-white border-2 border-emerald-500 focus:outline-none">
                                <div class="py-1">
                                    <MenuItem v-slot="{ active }">
                                        <a :href="file.download_url" @click.stop="downloadFile(file)" download :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'block px-4 py-2 text-sm flex items-center']">
                                            <DownloadIcon class="w-4 h-4 inline mr-2 text-gray-500" />
                                            Download
                                        </a>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                        <a href="#" @click.prevent="deleteFile(file)" :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'block px-4 py-2 text-sm  flex items-center']">
                                            <TrashIcon class="w-4 h-4 inline mr-2 text-gray-500" />
                                            Delete
                                        </a>
                                    </MenuItem>
                                </div>
                            </MenuItems>
                        </transition>
                    </Menu>
                </div>
            </div>
        </div>
        <div class="absolute bottom-0 h-8 w-full bg-gradient-to-t from-gray-100 to-transparent"></div>
    </div>
</template>

<script>
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import { ChevronDownIcon, DownloadIcon, TrashIcon } from '@heroicons/vue/solid'
import axios from 'axios';
import EventBus from '../EventBus.js';

export default {
    name: 'FileList',

    data() {
        return {
            selectedFile: null,
            files: [],
        }
    },

    methods: {
        async getFiles() {
            this.files = (await axios.get(`${logViewerBackendUrl}/files`)).data;
            EventBus.emit('filesLoaded');
        },

        selectFile(file) {
            EventBus.emit('fileSelected', file);
        },

        downloadFile(file) {
            EventBus.emit('fileDownloaded', file);
        },

        async deleteFile(file) {
            if (confirm(`Are you sure you would like to delete the file '${file.name}'? This CANNOT be reversed!`)) {
                await axios.delete(`${logViewerBackendUrl}/file/${file.name}`);
                this.files = this.files.filter(f => f.name !== file.name);
                EventBus.emit('fileDeleted', file);
            }
        },
    },

    mounted() {
        this.getFiles();

        EventBus.on('fileSelected', file => {
            this.selectedFile = file;
        });
    },

    components: {
        Menu, MenuButton, MenuItem, MenuItems, DownloadIcon, TrashIcon
    },
}
</script>
