<template>
  <div class="layout-container">
    <!-- Navigation Bar -->
    <nav class="bg-blue-600 p-4">
      <div class="container mx-auto flex justify-between items-center">
        <a :href="`/pizza`" class="text-white font-bold text-lg">Pizza</a>

        <div class="flex items-center">
          <Cart class="mr-4" />

          <template v-if="isAdmin">
            <a :href="`/logout`" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-700">{{ $t('action.logout') }}</a>
          </template>

          <!-- Language Switcher -->
          <div class="language-switcher mr-4">
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
        </div>
      </div>
    </nav>

    <slot></slot>
  </div>
</template>

<script setup>
import { ref, onMounted, getCurrentInstance } from 'vue';
import Cart from "../../components/Cart.vue";

const app = getCurrentInstance();
const currentLocale = ref('');
const isAdmin = ref(false);

function changeLocale(locale) {
  // Access the translator through the app instance
  const translator = app?.appContext.config.globalProperties.$translator;
  if (translator) {
    translator.locale = locale;
  }

  currentLocale.value = locale;

  // Add _locale parameter to URL so Symfony knows about the change
  const url = new URL(window.location.href);
  url.searchParams.set('_locale', locale);
  window.location.href = url.toString();
}

onMounted(() => {
  // Access the translator through the app instance
  const translator = app?.appContext.config.globalProperties.$translator;
  currentLocale.value = translator?.locale || 'en';

  // Get the admin status from window object or data attribute
  if (window.isAdmin !== undefined) {
    isAdmin.value = window.isAdmin;
  } else if (document.getElementById('app')?.dataset.isAdmin === 'true') {
    isAdmin.value = true;
  }
});
</script>
