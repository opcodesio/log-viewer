<template>
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
              <PaperClipIcon class="h-4 w-4 text-gray-500 dark:text-gray-400 mr-1"/>
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
</template>

<script setup>
import { PaperClipIcon } from '@heroicons/vue/24/outline';

defineProps(['mail']);

const downloadAttachment = (attachment) => {
  // Decode the base64 encoded string
  const decodedContent = atob(attachment.content);

  // Convert decoded base64 string to a Uint8Array
  const byteNumbers = new Array(decodedContent.length);
  for (let i = 0; i < decodedContent.length; i++) {
    byteNumbers[i] = decodedContent.charCodeAt(i);
  }
  const byteArray = new Uint8Array(byteNumbers);

  const blob = new Blob([byteArray], { type: attachment.content_type || 'application/octet-stream' });
  const blobUrl = URL.createObjectURL(blob);

  const downloadLink = document.createElement('a');
  downloadLink.href = blobUrl;
  downloadLink.download = attachment.filename;
  downloadLink.click();

  // Clean up the temporary URL after the download
  URL.revokeObjectURL(blobUrl);
}
</script>
