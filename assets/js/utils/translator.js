import enTranslations from '../../translations/en.json';
import plTranslations from '../../translations/pl.json';

const translations = {
    en: enTranslations,
    pl: plTranslations
};

let currentLocale = document.querySelector('html').getAttribute('lang') || 'en';

export default {
    get locale() {
        return currentLocale;
    },

    set locale(newLocale) {
        if (translations[newLocale]) {
            currentLocale = newLocale;
            document.querySelector('html').setAttribute('lang', newLocale);
            localStorage.setItem('app_locale', newLocale);
            console.log(`Locale changed to: ${newLocale}`);
        } else {
            console.error(`Locale ${newLocale} is not supported`);
        }
    },

    trans(key, parameters = {}, domain = 'messages') {
        // We ignore the domain parameter as we use our own system
        const translation = this.getTranslation(key);
        if (!translation) {
            console.warn(`Translation key not found: ${key}`);
            return key;
        }

        return this.replaceParams(translation, parameters);
    },

    getTranslation(key) {
        const messages = translations[currentLocale] || translations.en;

        if (key.includes('.')) {
            const parts = key.split('.');
            let result = messages;

            for (const part of parts) {
                if (result && typeof result === 'object' && part in result) {
                    result = result[part];
                } else {
                    return null;
                }
            }

            return result;
        }

        return messages[key] || null;
    },

    replaceParams(translation, parameters) {
        let result = translation;
        for (const param in parameters) {
            result = result.replace(`{${param}}`, parameters[param]);
        }
        return result;
    },

    init() {
        const savedLocale = localStorage.getItem('app_locale');
        if (savedLocale && translations[savedLocale]) {
            this.locale = savedLocale;
        }

        return this;
    }
};