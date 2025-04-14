import { registerVueControllerComponents } from '@symfony/ux-vue';
import { createApp } from 'vue';
import './bootstrap.js';
import './styles/app.css';
import translator from "./js/utils/translator";

const currency = window.cartCurrency || 'Null';

translator.init();

const vueControllers = require.context('./vue/controllers', true, /\.vue$/);
registerVueControllerComponents(vueControllers);

document.addEventListener('vue:before-mount', (event) => {
    const { app } = event.detail;

    app.config.globalProperties.$t = (key, params = {}) => translator.trans(key, params);
    app.config.globalProperties.$translator = translator;
    app.config.globalProperties.$currency = currency;
});

document.addEventListener('DOMContentLoaded', () => {
    const appElement = document.getElementById('app');
    if (appElement) {
        const app = createApp({
            template: '<Layout><slot></slot></Layout>'
        });

        const Layout = vueControllers('./Layout.vue').default;
        app.component('Layout', Layout);

        app.config.globalProperties.$t = (key, params = {}) => translator.trans(key, params);
        app.config.globalProperties.$translator = translator;
        app.config.globalProperties.$currency = currency;

        app.mount('#app');
    }
});