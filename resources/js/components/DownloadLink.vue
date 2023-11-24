<script setup>
import { CloudArrowDownIcon } from '@heroicons/vue/24/outline';
import axios from 'axios';

const props = defineProps(['url']);

const requestFileDownload = () => {
  axios.get(`${props.url}/request`)
    .then((response) => {
      downloadFromUrl(response.data.url);
    }).catch((error) => {
      console.log(error);

      if (error.response && error.response.data) {
        alert(`${error.message}: ${error.response.data.message}. Check developer console for more info.`);
      }
    });
};

const downloadFromUrl = (url) => {
  const link = document.createElement('a');
  link.href = url;
  link.setAttribute('download', '');
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}
</script>

<template>
  <button @click="requestFileDownload">
    <slot>
      <CloudArrowDownIcon class="w-4 h-4 mr-2" />
      Download
    </slot>
  </button>
</template>
