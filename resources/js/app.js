import { createApp } from 'vue';
import { createPinia } from 'pinia';
import axios from 'axios';
import { createRouter, createWebHistory } from 'vue-router';
import VueJsonPretty from 'vue-json-pretty';
import Home from './pages/Home.vue';

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
    path: `/${LogViewer.path}`,
    name: 'home',
    component: Home,
  }],
  history: createWebHistory(),
  base: routerBasePath,
});
const pinia = createPinia();

const app = createApp({
  router,
});

app.use(router);
app.use(pinia);
app.mixin({
  computed: {
    LogViewer: () => window.LogViewer,
  },
});
app.component('vue-json-pretty', VueJsonPretty);
app.provide('$http', axios.create());

app.mount('#log-viewer');
