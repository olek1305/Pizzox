<template>
  <div class="locale-switcher">
    <a @click.prevent="switchLocale('en')" :class="{ active: currentLocale === 'en' }">EN</a> |
    <a @click.prevent="switchLocale('pl')" :class="{ active: currentLocale === 'pl' }">PL</a>
  </div>
</template>

<script>
import translator from "../../../js/utils/translator";

export default {
  data() {
    return {
      currentLocale: 'en' // Default
    }
  },

  mounted() {
    const urlParams = new URLSearchParams(window.location.search);
    this.currentLocale = urlParams.get('_locale') || translator.locale || 'en';
  },

  methods: {
    switchLocale(locale) {
      if (this.currentLocale !== locale) {
        // Update URL with new locale and reload page
        const url = new URL(window.location);
        url.searchParams.set('_locale', locale);
        window.location = url.toString();
      }
    }
  }
}
</script>