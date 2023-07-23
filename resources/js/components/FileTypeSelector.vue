<template>
  <Listbox as="div" v-model="fileStore.selectedFileTypes" multiple>
    <ListboxLabel class="ml-1 block text-sm text-gray-500 dark:text-gray-400">Selected file types</ListboxLabel>

    <div class="relative mt-1">
      <ListboxButton id="hosts-toggle-button" class="cursor-pointer relative text-gray-800 dark:text-gray-200 w-full cursor-default rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 py-2 pl-4 pr-10 text-left hover:border-brand-600 hover:dark:border-brand-800 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 text-sm">
        <span class="block truncate">{{ fileStore.selectedFileTypesString }}</span>
        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
          <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
        </span>
      </ListboxButton>

      <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <ListboxOptions class="absolute z-20 mt-1 max-h-60 w-full overflow-auto rounded-md shadow-md bg-white dark:bg-gray-800 py-1 border border-gray-200 dark:border-gray-700 ring-1 ring-brand ring-opacity-5 focus:outline-none text-sm">
          <ListboxOption as="template" v-for="fileType in fileStore.fileTypesAvailable" :key="fileType.identifier" :value="fileType.identifier" v-slot="{ active, selected }">
            <li :class="[active ? 'text-white bg-brand-600' : 'text-gray-900 dark:text-gray-300', 'relative cursor-default select-none py-2 pl-3 pr-9']">
              <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ fileType.name }}</span>

              <span v-if="selected" :class="[active ? 'text-white' : 'text-brand-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                <CheckIcon class="h-5 w-5" aria-hidden="true" />
              </span>
            </li>
          </ListboxOption>
        </ListboxOptions>
      </transition>
    </div>
  </Listbox>
</template>

<script setup>
import { Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions } from '@headlessui/vue'
import { CheckIcon, ChevronDownIcon } from '@heroicons/vue/20/solid'
import { useRouter } from 'vue-router';
import { useFileStore } from '../stores/files.js';

const router = useRouter();
const fileStore = useFileStore();
</script>
