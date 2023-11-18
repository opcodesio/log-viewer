<script setup>
import { CloudArrowDownIcon } from '@heroicons/vue/24/outline';
import streamSaver from 'streamsaver';
import axios from 'axios';

const props = defineProps(['url']);

const downloadFile = async () => {
  const response = await axios.get(props.url, {
    responseType: 'blob'
  });

  if (response.status !== 200) {
    throw new Error(`HTTP error! status: ${response.status}`);
  }

  const disposition = response.headers['content-disposition'];
  const filename = disposition ? disposition.split('filename=')[1].replace(/"/g, '') : 'download.txt';

  const fileStream = streamSaver.createWriteStream(filename);
  const readableStream = response.data.stream();

  if (window.WritableStream && readableStream.pipeTo) {
    return readableStream.pipeTo(fileStream)
      .then(() => console.log('done writing'));
  }

  window.writer = fileStream.getWriter();
  const reader = readableStream.getReader();
  const pump = () => reader.read()
    .then(res => res.done
      ? writer.close()
      : writer.write(res.value).then(pump));

  pump();
};</script>

<template>
  <button @click="downloadFile">
    <slot>
      <CloudArrowDownIcon class="w-4 h-4 mr-2" />
      Download
    </slot>
  </button>
</template>
