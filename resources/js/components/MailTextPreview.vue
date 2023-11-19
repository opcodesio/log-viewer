<template>
  <div class="mail-preview">
    <!-- headers -->
    <mail-preview-attributes :mail="mail"/>

    <!-- Text preview -->
    <pre
      v-if="mail.text"
      class="mail-preview-text"
      v-text="mail.text"
    ></pre>
  </div>
</template>

<script setup>
import {ref} from "vue";
import MailPreviewAttributes from "./MailPreviewAttributes.vue";

defineProps({
  mail: {
    type: Object,
  },
})

const iframe = ref(null);
const iframeHeight = ref(600);

const setIframeHeight = () => {
  iframeHeight.value = (iframe.value?.contentWindow?.document?.body?.clientHeight || 580) + 20;
}
</script>
