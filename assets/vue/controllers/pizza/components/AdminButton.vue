<template>
  <div class="flex justify-center items-center mb-4 mt-4" v-if="isAdmin">
    <div class="flex space-x-3">
      <a :href="createPizzaPath" class="admin-btn px-4 py-2 bg-green-500 text-white rounded-lg">
        <span>{{ $t('pizza.create') }}</span>
      </a>
      <a :href="createAdditionPath" class="admin-btn px-4 py-2 bg-blue-500 text-white rounded-lg">
        <span>{{ $t('addition.create') }}</span>
      </a>
      <a :href="settingsPath" class="admin-btn px-4 py-2 bg-red-400 text-white rounded-lg">
        <span>{{ $t('settings.title') }}</span>
      </a>
      <a :href="paymentHistoryPath" class="admin-btn px-4 py-2 bg-red-700 text-white rounded-lg">
        <span>{{ $t('paymentHistory') }}</span>
      </a>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";

const props = defineProps({
  isAdmin: {
    type: Boolean,
    required: true
  }
});

const createPizzaPath = ref('/pizza/create');
const createAdditionPath = ref('/addition/create');
const settingsPath = ref('/admin/settings');
const paymentHistoryPath = ref('/admin/payment-history');
</script>