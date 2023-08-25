<template>
  <div>
    <div class="tabs-container" v-if="tabs && tabs.length > 1">
      <div class="border-b border-gray-200 dark:border-gray-800">
        <nav class="-mb-px flex space-x-6" aria-label="Tabs">
          <a v-for="tab in tabs" :key="tab.name" href="#" @click.prevent="currentTab = tab"
             :class="[isCurrent(tab) ? 'border-brand-500 dark:border-brand-400 text-brand-600 dark:text-brand-500' : 'border-transparent text-gray-500 dark:text-gray-400 hover:border-gray-300 hover:text-gray-700 dark:hover:text-gray-200', 'whitespace-nowrap border-b-2 py-2 px-1 text-sm font-medium focus:outline-brand-500']"
             :aria-current="isCurrent(tab) ? 'page' : undefined">{{ tab.name }}</a>
        </nav>
      </div>
    </div>

    <slot></slot>
  </div>
</template>

<script setup>
import {provide, ref} from "vue";

const props = defineProps({
  tabs: {
    type: Array,
    required: true,
  },
})

const currentTab = ref(props.tabs[0]);
provide('currentTab', currentTab);

const isCurrent = (tab) => {
  return currentTab.value && currentTab.value.value === tab.value;
}
</script>
