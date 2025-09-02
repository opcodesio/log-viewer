<template>
  <div class="relative inline-block">

    <button
      @click="toggleDropdown"
      :disabled="loading"
      class="inline-flex items-center px-2 py-1 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700"
      :class="{ 'opacity-50 cursor-not-allowed': loading }"
      title="Ask AI for help with this error"
    >
      <SparklesIcon class="w-3 h-3 mr-1" />
      <span>Ask AI</span>
      <ChevronDownIcon class="w-3 h-3 ml-1" />
    </button>

    <transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="showDropdown"
        class="absolute right-0 mt-1 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 dark:bg-gray-800"
        style="min-width: 150px; z-index: 999999"
      >
        <div class="py-1">
          <button
            @click="copyAsMarkdown"
            class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700"
          >
            <ClipboardDocumentIcon class="w-4 h-4 mr-2" />
            Copy as Markdown
          </button>

          <div class="border-t border-gray-100 dark:border-gray-700"></div>

          <button
            v-for="provider in providers"
            :key="provider.key"
            @click="askAi(provider.key)"
            class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700"
          >
            <span v-html="provider.icon" class="mr-2"></span>
            {{ provider.name }}
          </button>
        </div>
      </div>
    </transition>

    <div v-if="loading" class="fixed inset-0 z-40 bg-black bg-opacity-25 flex items-center justify-center">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-xl">
        <SpinnerIcon class="w-8 h-8" />
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Preparing AI export...</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { SparklesIcon, ChevronDownIcon, ClipboardDocumentIcon } from '@heroicons/vue/24/solid';
import SpinnerIcon from './SpinnerIcon.vue';
import axios from 'axios';
import { useFileStore } from '../stores/files';
import { useAiProvidersStore } from '../stores/aiProviders';
import { useLogViewerStore } from '../stores/logViewer';

const props = defineProps({
  log: {
    type: Object,
    required: true
  },
  logIndex: {
    type: Number,
    required: true
  }
});

const fileStore = useFileStore();
const aiProvidersStore = useAiProvidersStore();
const logViewerStore = useLogViewerStore();
const showDropdown = ref(false);
const loading = ref(false);

// Usar computed para providers do store
const providers = computed(() => aiProvidersStore.providers);
const providersLoading = computed(() => aiProvidersStore.loading);

// Load providers only once (store takes care of caching)
onMounted(async () => {
  // Only tries to load providers if AI Export is enabled
  if (window.LogViewer?.ai_export_enabled) {
    // The store will only load the first time or return from the cache
    await aiProvidersStore.fetchProviders();
  }

  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});

const handleClickOutside = (event) => {
  if (!event.target.closest('.relative.inline-block')) {
    showDropdown.value = false;
  }
};

const toggleDropdown = (event) => {
  event.stopPropagation();

  const rowIndex = logViewerStore.logs.findIndex(l => l.index === props.logIndex);

  if (rowIndex >= 0 && !logViewerStore.isOpen(rowIndex)) {
    logViewerStore.toggle(rowIndex);

    setTimeout(() => {
      showDropdown.value = !showDropdown.value;
    }, 150);
  } else {
    showDropdown.value = !showDropdown.value;
  }
};

const copyAsMarkdown = async () => {
  loading.value = true;
  showDropdown.value = false;

  try {
    const response = await axios.post(`${LogViewer.basePath}/api/ai/copy-markdown`, {
      log_index: props.logIndex,
      file_identifier: fileStore.selectedFile?.identifier
    });

    await navigator.clipboard.writeText(response.data.markdown);

    showNotification('Markdown copied to clipboard!', 'success');
  } catch (error) {
    console.error('Failed to copy as markdown:', error);
    showNotification('Failed to copy markdown', 'error');
  } finally {
    loading.value = false;
  }
};

const askAi = async (providerKey) => {
  loading.value = true;
  showDropdown.value = false;

  try {
    const response = await axios.post(`${LogViewer.basePath}/api/ai/export`, {
      provider: providerKey,
      log_index: props.logIndex,
      file_identifier: fileStore.selectedFile?.identifier
    });

    window.open(response.data.url, '_blank');

    showNotification(`Opening ${response.data.provider.name}...`, 'success');
  } catch (error) {
    console.error('Failed to export to AI:', error);

    if (error.response?.status === 429) {
      showNotification('Too many requests. Please try again later.', 'error');
    } else {
      showNotification('Failed to export to AI', 'error');
    }
  } finally {
    loading.value = false;
  }
};

// Função auxiliar para mostrar notificações
const showNotification = (message, type = 'info') => {
  // Criar elemento de notificação
  const notification = document.createElement('div');
  notification.className = `fixed bottom-4 right-4 z-50 px-4 py-2 rounded-md shadow-lg text-white transition-opacity duration-300 ${
    type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500'
  }`;
  notification.textContent = message;

  document.body.appendChild(notification);

  // Remover após 3 segundos
  setTimeout(() => {
    notification.style.opacity = '0';
    setTimeout(() => {
      document.body.removeChild(notification);
    }, 300);
  }, 3000);
};
</script>
