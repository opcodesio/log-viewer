import { createApp } from 'vue';
import { createPinia } from 'pinia';
import axios from 'axios';
import { createRouter, createWebHistory } from 'vue-router';
import Home from './pages/Home.vue';
import Root from './Root.vue'; // ✅ new root component

let token = document.head.querySelector('meta[name="csrf-token"]');
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

if (token) {
  axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

for (const [key, value] of Object.entries(window.LogViewer.headers || {})) {
  axios.defaults.headers.common[key] = value;
}

window.LogViewer.basePath = '/' + window.LogViewer.path;

if (! window.location.pathname.startsWith(window.LogViewer.basePath)) {
  window.LogViewer.basePath = window.location.pathname;
}

let routerBasePath = window.LogViewer.basePath + '/';

if (window.LogViewer.path === '' || window.LogViewer.path === '/') {
  routerBasePath = '/';
  window.LogViewer.basePath = '';
}

const router = createRouter({
  routes: [{
    path: LogViewer.basePath,
    name: 'home',
    component: Home,
  }],
  history: createWebHistory(),
  base: routerBasePath,
});
const pinia = createPinia();

const app = createApp(Root);

app.use(router);
app.use(pinia);
app.mixin({
  computed: {
    LogViewer: () => window.LogViewer,
  },
});

app.mount('#log-viewer');
