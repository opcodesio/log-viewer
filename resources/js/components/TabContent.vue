<template>
  <div v-if="isSelected">
    <slot></slot>
  </div>
</template>

<script setup>
import {computed, inject} from "vue";

const props = defineProps({
  tabValue: {
    type: String,
    required: true,
  },
})

const currentTab = inject('currentTab');

const isSelected = computed(() => {
  return currentTab.value && currentTab.value.value === props.tabValue;
})
</script>
