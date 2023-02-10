import { createApp } from 'vue';
import Base from './base';
import axios from 'axios';
import { createRouter, createWebHashHistory } from 'vue-router';
import VueJsonPretty from 'vue-json-pretty';

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

const app = createApp({
    router,

    data() {
        return {
            alert: {
                type: null,
                autoClose: 0,
                message: '',
                confirmationProceed: null,
                confirmationCancel: null,
            },

            autoLoadsNewEntries: localStorage.autoLoadsNewEntries === '1',
        };
    },
});

app.use(router);
app.mixin(Base);
app.component('vue-json-pretty', VueJsonPretty);
app.provide('$http', axios.create());

app.mount('#log-viewer');
