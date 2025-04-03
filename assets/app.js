import { registerVueControllerComponents } from '@symfony/ux-vue';
import './bootstrap.js';
import './styles/app.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

registerVueControllerComponents(require.context('./vue/controllers', true, /\.vue$/));
document.addEventListener('vue:before-mount', (event) => {
    const {
        componentName,
        component,
        props,
        app,
    } = event.detail;

    console.log(`Komponent ${componentName} będzie zamontowany`, component, props);
});

document.addEventListener('vue:mount', (event) => {
    const {
        componentName,
        component,
        props,
    } = event.detail;

    console.log(`Komponent ${componentName} został zamontowany`, component, props);
});

document.addEventListener('vue:unmount', (event) => {
    const {
        componentName,
        props,
    } = event.detail;

    console.log(`Komponent ${componentName} został odmontowany`, props);
});