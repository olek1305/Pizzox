<template>
  <div class="locale-switcher">
    <a @click.prevent="switchLocale('en')" :class="{ active: currentLocale === 'en' }">EN</a> |
    <a @click.prevent="switchLocale('pl')" :class="{ active: currentLocale === 'pl' }">PL</a>
  </div>
</template>

<script setup>
import translator from "../../../js/utils/translator";
import { ref, onMounted } from 'vue';

const currentLocale = ref('en');

onMounted(() => {
  const urlParams = new URLSearchParams(window.location.search);
  currentLocale.value = urlParams.get('_locale') || translator.locale || 'en';
});

const switchLocale = (locale) => {
  if (currentLocale.value !== locale) {
    // Update URL with new locale and reload page
    const url = new URL(window.location);
    url.searchParams.set('_locale', locale);
    window.location = url.toString();
  }
};
</script>
