import { createI18n } from 'vue-i18n';
import en from './locales/en';
import vi from './locales/vi';

const savedLocale = localStorage.getItem('locale') || 'en';

document.cookie = `locale=${savedLocale}; path=/; SameSite=Lax`;

export const i18n = createI18n({
    legacy: false,
    locale: savedLocale,
    fallbackLocale: 'en',
    messages: { en, vi },
});
