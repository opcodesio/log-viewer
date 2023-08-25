<template>
  <div class="mail-preview">
    <!-- headers -->
    <div class="mail-preview-attributes">
      <table>
        <tr v-if="mail.from">
          <td class="font-semibold">From</td>
          <td>{{ mail.from }}</td>
        </tr>
        <tr v-if="mail.to">
          <td class="font-semibold">To</td>
          <td>{{ mail.to }}</td>
        </tr>
        <tr v-if="mail.id">
          <td class="font-semibold">Message ID</td>
          <td>{{ mail.id }}</td>
        </tr>
        <tr v-if="mail.subject">
          <td class="font-semibold">Subject</td>
          <td>{{ mail.subject }}</td>
        </tr>
        <tr v-if="mail.attachments && mail.attachments.length > 0">
          <td class="font-semibold">Attachments</td>
          <td>
            <div v-for="(attachment, index) in mail.attachments" :key="`mail-${mail.id}-attachment-${index}`"
                 class="mail-attachment-button"
            >
              <div class="flex items-center">
                <PaperClipIcon class="h-4 w-4 text-gray-500 dark:text-gray-400 mr-1" />
                <span>{{ attachment.filename }} <span class="opacity-60">({{ attachment.size_formatted }})</span></span>
              </div>
              <div>
                <a href="#" @click.prevent="downloadAttachment(attachment)"
                   class="text-blue-600 hover:text-blue-700 dark:text-blue-500 dark:hover:text-blue-400"
                >Download</a>
              </div>
            </div>
          </td>
        </tr>
      </table>
    </div>

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
import { PaperClipIcon } from '@heroicons/vue/24/outline';
import {computed, ref} from "vue";

const props = defineProps({
  mail: {
    type: Object,
  },
})

const iframe = ref(null);

const iframeHeight = ref(600);

const setIframeHeight = () => {
  iframeHeight.value = (iframe.value?.contentWindow?.document?.body?.clientHeight || 580) + 20;
}

const downloadAttachment = (attachment) => {
  const blob = new Blob([attachment.content], { type: attachment.content_type || 'application/octet-stream' });
  const blobUrl = URL.createObjectURL(blob);

  const downloadLink = document.createElement('a');
  downloadLink.href = blobUrl;
  downloadLink.download = attachment.filename;
  downloadLink.click();

  // Clean up the temporary URL after the download
  URL.revokeObjectURL(blobUrl);
}
</script>
