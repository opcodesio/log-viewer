<template>
  <TransitionRoot as="template" :show="logViewerStore.helpSlideOverOpen">
    <Dialog as="div" class="relative z-20" @close="logViewerStore.helpSlideOverOpen = false">
      <div class="fixed inset-0" />

      <div class="fixed inset-0 overflow-hidden">
        <div class="absolute inset-0 overflow-hidden">
          <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
            <TransitionChild
              as="template"
              enter="transform transition ease-in-out duration-200 sm:duration-300"
              enter-from="translate-x-full"
              enter-to="translate-x-0"
              leave="transform transition ease-in-out duration-200 sm:duration-300"
              leave-from="translate-x-0"
              leave-to="translate-x-full"
            >
              <DialogPanel class="pointer-events-auto w-screen max-w-md">
                <div class="flex h-full flex-col overflow-y-scroll bg-white py-6 shadow-xl dark:bg-gray-700">
                  <div class="px-4 sm:px-6">
                    <div class="flex items-start justify-between">
                      <DialogTitle class="text-base font-semibold leading-6 text-gray-900 dark:text-gray-100">Keyboard Shortcuts</DialogTitle>
                      <div class="ml-3 flex h-7 items-center">
                        <button type="button" class="rounded-md bg-white dark:bg-gray-700 text-gray-400 hover:text-gray-500 dark:text-gray-400 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-brand-500 dark:focus:ring-brand-300 focus:ring-offset-2" @click="logViewerStore.helpSlideOverOpen = false">
                          <span class="sr-only">Close panel</span>
                          <XMarkIcon class="h-6 w-6" aria-hidden="true" />
                        </button>
                      </div>
                    </div>
                  </div>
                  <div class="relative mt-6 flex-1 px-4 sm:px-6">
                    <div class="keyboard-shortcut">
                      <span class="shortcut">{{ KeyShortcuts.Hosts }}</span>
                      <span class="description">Select a host</span>
                    </div>
                    <div class="keyboard-shortcut">
                      <span class="shortcut">{{ KeyShortcuts.Files }}</span>
                      <span class="description">Jump to file selection</span>
                    </div>
                    <div class="keyboard-shortcut">
                      <span class="shortcut">{{ KeyShortcuts.Logs }}</span>
                      <span class="description">Jump to logs</span>
                    </div>
                    <div class="keyboard-shortcut">
                      <span class="shortcut">{{ KeyShortcuts.NextLog }}</span>
                      <span class="description">Open next log</span>
                    </div>
                    <div class="keyboard-shortcut">
                      <span class="shortcut">{{ KeyShortcuts.PreviousLog }}</span>
                      <span class="description">Open previous log</span>
                    </div>
                    <div class="keyboard-shortcut">
                      <span class="shortcut">{{ KeyShortcuts.Next }}</span>
                      <span class="description">Next (file or log)</span>
                    </div>
                    <div class="keyboard-shortcut">
                      <span class="shortcut">{{ KeyShortcuts.Previous }}</span>
                      <span class="description">Previous (file or log)</span>
                    </div>
                    <div class="keyboard-shortcut">
                      <span class="shortcut">{{ KeyShortcuts.Severity }}</span>
                      <span class="description">Severity selection</span>
                    </div>
                    <div class="keyboard-shortcut">
                      <span class="shortcut">{{ KeyShortcuts.Settings }}</span>
                      <span class="description">Settings</span>
                    </div>
                    <div class="keyboard-shortcut">
                      <span class="shortcut">{{ KeyShortcuts.Search }}</span>
                      <span class="description">Search</span>
                    </div>
                    <div class="keyboard-shortcut">
                      <span class="shortcut">{{ KeyShortcuts.Refresh }}</span>
                      <span class="description">Refresh logs</span>
                    </div>
                    <div class="keyboard-shortcut">
                      <span class="shortcut">{{ KeyShortcuts.ShortcutHelp }}</span>
                      <span class="description">Keyboard shortcuts help</span>
                    </div>
                  </div>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import { useLogViewerStore } from '../stores/logViewer.js';
import { KeyShortcuts } from '../keyboardNavigation/shared.js';

const logViewerStore = useLogViewerStore();
</script>
