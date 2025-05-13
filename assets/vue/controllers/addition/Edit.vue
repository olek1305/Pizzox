<template>
  <div>
    <div class="flex justify-center items-center mb-4">
      <div class="flex space-x-3">
        <a :href="`/pizza`" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-700">
          {{ $t('action.back_to_list') }}
        </a>
      </div>
    </div>

    <div class="bg-white shadow-md rounded-lg p-6">
      <h1 class="text-3xl font-bold mb-6">{{ $t('addition.show') }}</h1>

      <!-- Addition Create Form -->
      <div class="mb-8">
        <!-- Warning about price -->
        <div class="mb-4 bg-yellow-50 border-l-4 border-yellow-400 p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm text-yellow-700">
                <strong>{{ $t('addition.price_warning.title') }}</strong> {{ $t('addition.price_warning.message') }}
              </p>
            </div>
          </div>
        </div>

        <div class="mb-4">
          <label for="addition_name" class="block text-sm font-medium text-gray-700 mb-1">{{ $t('addition.name') }}</label>
          <input type="text" id="addition_name" v-model="additionName" required
                 class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
          <div v-if="formErrors.name" class="text-red-500 text-sm mt-1">
            {{ formErrors.name }}
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
          <div class="mb-4">
            <label for="addition_price" class="block text-sm font-medium text-gray-700 mb-1">
              {{ $t('addition.price') }}
            </label>
            <input type="number" v-model="additionPrice" id="addition_price" step="0.01" min="0.01" required
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
            <div v-if="formErrors.price" class="text-red-500 text-sm mt-1">{{ formErrors.price }}</div>
          </div>
        </div>

        <div class="mb-4">
          <label for="addition_category" class="block text-sm font-medium text-gray-700 mb-1">{{ $t('addition.category') }}</label>
          <select id="addition_category" v-model="selectedCategoryId"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">{{ $t('addition.no_category') }}</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </select>
        </div>

        <div class="flex justify-end mt-6">
          <button
              type="button"
              @click="submitForm"
              :disabled="!isFormValid"
              class="px-4 py-2 text-white rounded-lg hover:bg-blue-700"
              :class="isFormValid ? 'bg-blue-500' : 'bg-gray-400 cursor-not-allowed'"
          >
            {{ $t('action.save') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  categories: {
    type: Array,
    default: () => []
  }
});

// Form fields
const additionName = ref('');
const additionPrice = ref('');
const selectedCategoryId = ref('');
const priceError = ref('');
const formErrors = ref({});

// Validate addition price
const validateAdditionPrice = () => {
  if (!additionPrice.value || parseFloat(additionPrice.value) <= 0) {
    priceError.value = $t('addition.price_warning.message');
    return false;
  }
  priceError.value = '';
  return true;
};

const isFormValid = computed(() => {
  return (
      additionName.value.trim() !== '' &&
      parseFloat(additionPrice.value) > 0 &&
      selectedCategoryId.value !== ''
  );
});

// Form submission via fetch API
const submitForm = async () => {
  try {
    // Validate required fields before submission
    if (!validateAdditionPrice()) {
      return;
    }

    if (!additionName.value || additionName.value.trim() === '') {
      formErrors.value = { ...formErrors.value, name: 'Addition name is required' };
      return;
    }

    const formData = {
      name: additionName.value,
      price: parseFloat(additionPrice.value),
      category: selectedCategoryId.value
    };

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const response = await fetch('/addition/create', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken || '',
      },
      body: JSON.stringify(formData)
    });

    const result = await response.json();

    if (response.ok) {
      if (result.flash) {
        window.flashMessages = result.flash;
      }

      window.location.href = result.redirect || '/pizza';
    } else {
      if (result.validationErrors) {
        formErrors.value = result.validationErrors;
        if (result.validationErrors.price) {
          priceError.value = result.validationErrors.price;
        }
      } else {
        alert(result?.error || 'An error occurred while saving the addition.');
      }
    }
  } catch (error) {
    console.error('Error submitting form:', error);
    alert('An error occurred while saving the addition.');
  }
};
</script>