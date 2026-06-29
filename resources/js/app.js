import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import { i18nVue, loadLanguageAsync } from 'laravel-vue-i18n';

createInertiaApp({
    title: (title) => `${title} - Yoga Management`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    async setup({ el, App, props, plugin }) {
        const langs = import.meta.glob('../../lang/*.json', { eager: true });

        const savedLocale = localStorage.getItem('locale') || 'en';

        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(i18nVue, {
                resolve: (lang) => {
                    const mod = langs[`../../lang/${lang}.json`];
                    return mod ? mod.default : {};
                },
            });

        await loadLanguageAsync(savedLocale);
        return app.mount(el);
    },
    progress: {
        color: '#4b6694',
    },
});
