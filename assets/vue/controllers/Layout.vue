<template>
  <div>
    <!-- Navigation Bar -->
    <nav class="bg-blue-600 p-4 fixed top-0 left-0 right-0 z-50 transition-transform duration-300"
         :class="{ '-translate-y-full': isNavHidden }">
      <div class="flex justify-between items-center">
        <a :href="`/pizza`" class="text-white font-bold text-lg">Pizza</a>

        <div class="flex items-center">
          <template v-if="isAdmin">
            <a :href="`/logout`"
               class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-700">{{ $t('action.logout') }}</a>
          </template>

          <!-- Language Switcher -->
          <div class="language-switcher mr-4">
            <button
                @click="changeLocale('en')"
                :class="{'font-bold': currentLocale === 'en'}"
                class="text-white mx-1 px-2 py-1 rounded hover:bg-blue-700 bg-blue-600"
            >EN
            </button>
            <button
                @click="changeLocale('pl')"
                :class="{'font-bold': currentLocale === 'pl'}"
                class="text-white mx-1 px-2 py-1 rounded hover:bg-blue-700 bg-blue-600"
            >PL
            </button>
          </div>
        </div>
      </div>
    </nav>

    <!-- Spacer to prevent content from hiding under the fixed navbar -->
    <div class="h-16"></div>

    <!-- FlashMessage Component -->
    <transition name="flash-message">
      <FlashMessage v-if="flashVisible" :message="flashMessage" :type="flashType"/>
    </transition>

    <slot></slot>
  </div>
</template>

<script setup>
import FlashMessage from "./components/FlashMessage.vue";
import {ref, onMounted, getCurrentInstance, onUnmounted} from 'vue';

const app = getCurrentInstance();
const currentLocale = ref('');
const isAdmin = ref(false);
const isNavHidden = ref(false);
const flashVisible = ref(false);
const flashMessage = ref('');
const flashType = ref('success');
const translator = app?.appContext.config.globalProperties.$translator;
let lastScrollPosition = 0;

function changeLocale(locale) {
  const translator = app?.appContext.config.globalProperties.$translator;
  if (translator) {
    translator.locale = locale;
  }

  currentLocale.value = locale;

  const url = new URL(window.location.href);
  url.searchParams.set('_locale', locale);
  window.location.href = url.toString();
}

function handleScroll() {
  const currentScrollPosition = window.scrollY;

  isNavHidden.value = currentScrollPosition > lastScrollPosition && currentScrollPosition > 50;

  lastScrollPosition = currentScrollPosition;
}

if (flashMessages.success && flashMessages.success.length > 0) {
  const raw = flashMessages.success[0];
  flashMessage.value = translator.trans(raw);
  flashType.value = 'success';
  flashVisible.value = true;
  setTimeout(() => {
    flashVisible.value = false;
  }, 3000);
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll);

  const flashMessages = window.flashMessages || {};

  if (flashMessages.success && flashMessages.success.length > 0) {
    const raw = flashMessages.success[0];
    flashMessage.value = translator.trans(raw);
    flashType.value = 'success';
    flashVisible.value = true;
    setTimeout(() => {
      flashVisible.value = false;
    }, 3000);
  } else if (flashMessages.error && flashMessages.error.length > 0) {
    const raw = flashMessages.error[0];
    flashMessage.value = translator.trans(raw);
    flashType.value = 'error';
    flashVisible.value = true;
    setTimeout(() => {
      flashVisible.value = false;
    }, 3000);
  }
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
});
</script>
