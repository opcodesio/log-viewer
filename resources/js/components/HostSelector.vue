<template>
  <Listbox as="div" v-model="hostStore.selectedHostIdentifier">
    <ListboxLabel class="ml-1 block text-sm font-semibold text-brand-700">Select host</ListboxLabel>

    <div class="relative mt-1">
      <ListboxButton class="relative text-gray-800 dark:text-gray-200 w-full cursor-default rounded-md border border-gray-300 dark:border-gray-700 bg-white py-2 pl-4 pr-10 text-left focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 text-sm">
        <span class="block truncate">{{ hostStore.selectedHost.name }}</span>
        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
          <ChevronUpDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
        </span>
      </ListboxButton>

      <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <ListboxOptions class="absolute z-20 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none text-sm">
          <ListboxOption as="template" v-for="host in hostStore.hosts" :key="host.identifier" :value="host.identifier" v-slot="{ active, selected }">
            <li :class="[active ? 'text-white bg-brand-600' : 'text-gray-900', 'relative cursor-default select-none py-2 pl-3 pr-9']">
              <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ host.name }}</span>

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
import { watch } from 'vue'
import { Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions } from '@headlessui/vue'
import { CheckIcon, ChevronUpDownIcon } from '@heroicons/vue/20/solid'
import { useHostStore } from '../stores/hosts.js';
import { useRouter } from 'vue-router';
import { replaceQuery } from '../helpers.js';

const router = useRouter();
const hostStore = useHostStore();

watch(
  () => hostStore.selectedHostIdentifier,
  (value) => {
    replaceQuery(router, 'host', value);
  }
);
</script>
