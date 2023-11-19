<template>
  <div class="mail-preview">
    <!-- headers -->
    <mail-preview-attributes :mail="mail"/>

    <!-- HTML preview -->
    <iframe
      v-if="mail.html"
      class="mail-preview-html"
      :style="{height: `${iframeHeight}px`}"
      :srcdoc="mail.html"
      @load="setIframeHeight"
      ref="iframe"
    ></iframe>
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
