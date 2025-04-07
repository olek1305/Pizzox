<!-- assets/vue/controllers/Layout.vue -->
<template>
  <div class="layout-container">
    <!-- Przełącznik języka -->
    <div class="language-switcher absolute top-4 right-4 z-10">
      <button
          @click="changeLocale('en')"
          :class="{'font-bold': currentLocale === 'en'}"
          class="text-white mx-1 px-2 py-1 rounded hover:bg-blue-700 bg-blue-600"
      >EN</button>
      <button
          @click="changeLocale('pl')"
          :class="{'font-bold': currentLocale === 'pl'}"
          class="text-white mx-1 px-2 py-1 rounded hover:bg-blue-700 bg-blue-600"
      >PL</button>
    </div>

    <slot></slot>
  </div>
</template>

<script>
export default {
  data() {
    return {
      currentLocale: this.$translator.locale
    };
  },
  methods: {
    changeLocale(locale) {
      this.$translator.locale = locale;
      this.currentLocale = locale;

      // Dodaj parametr _locale do URL aby Symfony również wiedział o zmianie
      const url = new URL(window.location.href);
      url.searchParams.set('_locale', locale);
      window.location.href = url.toString();
    }
  },
  mounted() {
    this.currentLocale = this.$translator.locale;
  }
};
</script>