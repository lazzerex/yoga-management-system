import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import { i18nVue, loadLanguageAsync } from 'laravel-vue-i18n';

// Sidebar links prefetch on hover and Inertia keeps that copy for 30s, so a page
// hovered before a write would be replayed without the row the write just created.
router.on('finish', (event) => {
    if ((event.detail.visit?.method ?? 'get').toLowerCase() !== 'get') {
        router.flushAll();
    }
});

createInertiaApp({
    title: (title) => (title ? `${title} - Yoga Management` : 'Yoga Management'),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    async setup({ el, App, props, plugin }) {
        const langs = import.meta.glob('../../lang/*.json');

        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(i18nVue, {
                resolve: (lang) => langs[`../../lang/${lang}.json`]?.() ?? Promise.resolve({ default: {} }),
            });

        await loadLanguageAsync(document.documentElement.lang.replace('-', '_'));
        return app.mount(el);
    },
    progress: {
        color: '#4b6694',
    },
});
