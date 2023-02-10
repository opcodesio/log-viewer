import { createApp } from 'vue';
import { createPinia } from 'pinia';
import Base from './base';
import axios from 'axios';
import { createRouter, createWebHashHistory } from 'vue-router';
import VueJsonPretty from 'vue-json-pretty';
import FileList from './components/FileList.vue';
import { useLogViewerStore } from './stores/logViewer.js';

let token = document.head.querySelector('meta[name="csrf-token"]');
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

if (token) {
  axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

window.LogViewer.basePath = '/' + window.LogViewer.path;

let routerBasePath = window.LogViewer.basePath + '/';

if (window.LogViewer.path === '' || window.LogViewer.path === '/') {
  routerBasePath = '/';
  window.LogViewer.basePath = '';
}

const router = createRouter({
  routes: [{
    path: '/',
    name: 'home',
    component: require('./home').default,
  }],
  history: createWebHashHistory(),
  base: routerBasePath,
});
const pinia = createPinia();

const app = createApp({
  router,

  mounted() {
    const logViewerStore = useLogViewerStore();
    // This makes sure we react to device's dark mode changes
    setInterval(logViewerStore.syncTheme, 1000);
  },
});

app.use(router);
app.use(pinia);
app.mixin(Base);
app.component('vue-json-pretty', VueJsonPretty);
app.component('FileList', FileList);
app.provide('$http', axios.create());

app.mount('#log-viewer');
