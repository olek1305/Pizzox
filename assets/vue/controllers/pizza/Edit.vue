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
      <h1 class="text-3xl font-bold mb-6">{{ $t('pizza.edit') }}: {{ pizza.name }}</h1>

      <!-- Pizza Edit Form -->
      <div class="mb-8">
        <!-- Warning about medium pizza price -->
        <div class="mb-4 bg-yellow-50 border-l-4 border-yellow-400 p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm text-yellow-700">
                <strong>{{ $t('pizza.price_warning.title') }}</strong> {{ $t('pizza.price_warning.message') }}
              </p>
            </div>
          </div>
        </div>
        
        <div class="mb-4">
          <label for="pizza_name" class="block text-sm font-medium text-gray-700 mb-1">{{ $t('pizza.name') }}</label>
          <input type="text" id="pizza_name" v-model="pizzaName" required
                 class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
          <div v-if="formErrors.name" class="text-red-500 text-sm mt-1">
            {{ formErrors.name }}
          </div>
        </div>
      
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
          <div>
            <label for="pizza_price" class="block text-sm font-medium text-gray-700 mb-1">
              {{ $t('pizza.price_medium') }} <strong class="text-blue-600">({{ $t('as_the_default') }})</strong>
            </label>
            <input type="number" id="pizza_price" v-model="pizzaPrice" step="0.01" min="0.01" required
                   class="mt-1 block w-full rounded-md border-blue-400 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                   @input="validatePizzaPrice">
            <div v-if="priceError" class="text-red-500 text-sm mt-1">
              {{ priceError }}
            </div>
            <small class="text-gray-700">{{ $t('pizza.topping_input.must_be_greater_than_zero') }}</small>
          </div>
      
          <div>
            <label for="pizza_price_small" class="block text-sm font-medium text-gray-700 mb-1">{{ $t('pizza.price_small') }}</label>
            <input type="number" id="pizza_price_small" v-model="smallPrice" step="0.01" min="0"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <small class="text-gray-500">{{ $t('pizza.small_price_help') }}</small>
          </div>
          
          <div>
            <label for="pizza_price_large" class="block text-sm font-medium text-gray-700 mb-1">{{ $t('pizza.price_large') }}</label>
            <input type="number" id="pizza_price_large" v-model="largePrice" step="0.01" min="0"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <small class="text-gray-500">{{ $t('pizza.large_price_help') }}</small>
          </div>
        </div>
      
        <div class="mb-4">
          <label for="pizza_category" class="block text-sm font-medium text-gray-700 mb-1">{{ $t('pizza.category') }}</label>
          <select id="pizza_category" v-model="selectedCategoryId"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">{{ $t('pizza.no_category') }}</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </select>
        </div>
      
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('pizza.toppings') }}</label>
          <div class="flex flex-wrap gap-2 mb-3">
            <template v-if="toppings.length > 0">
              <div v-for="(topping, index) in toppings" :key="index" 
                   class="flex items-center bg-gray-100 px-2 py-1 rounded-md">
                <span class="text-gray-800">{{ topping }}</span>
                <button type="button" @click="removeToppingAt(index)"
                        class="ml-1 p-1 text-red-500 hover:text-red-700">
                  <span>&times;</span>
                </button>
              </div>
            </template>
          </div>
          <div class="flex items-center">
            <input type="text" v-model="newTopping" :placeholder="$t('pizza.topping_input.placeholder')"
                   class="inline-block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                   @keydown.space.prevent="handleToppingSpace"
                   ref="toppingInput">
            <button type="button" @click="addTopping"
                    class="ml-2 px-3 py-1 bg-green-500 text-white rounded-lg hover:bg-green-700 whitespace-nowrap">
              {{ $t('action.add') }}
            </button>
          </div>
          <small class="text-gray-500 mt-1 block">{{ $t('pizza.topping_input.help_text') }}</small>
        </div>
      
        <div class="flex justify-end mt-6">
          <button type="button" @click="submitForm" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-700">
            {{ $t('action.save') }}
          </button>
        </div>
      </div>

      <!-- Promotion Creation Form - This remains the same -->
      <div v-if="isAdmin" class="mt-8 pt-8 border-t border-gray-200">
        <h3 class="text-2xl font-normal mb-4">{{ $t('promotion.create_title') }}</h3>
        <form :action="`/promotion/create/pizza/${pizza.id}`" method="POST" class="space-y-6">
          <input type="hidden" name="_csrf_token" data-controller="csrf-protection">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <div class="font-normal mb-2">
                <label for="usage_limit">{{ $t('promotion.usage_limit') }}</label>
              </div>
              <input type="number" id="usage_limit" name="usage_limit" required min="1"
                     class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
              <div class="font-normal mb-2">
                <label for="discount_value">{{ $t('promotion.discount_value') }}</label>
              </div>
              <input type="number" id="discount_value" name="discount_value" required step="0.01" min="0"
                     class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
              <div class="font-normal mb-2">
                <label for="discount_type">{{ $t('promotion.discount_type') }}</label>
              </div>
              <select id="discount_type" name="discount_type" required
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="percentage">{{ $t('promotion.percentage') }}</option>
                <option value="fixed">{{ $t('promotion.fixed_amount') }}</option>
              </select>
            </div>

            <div>
              <div class="font-normal mb-2">
                <label>{{ $t('promotion.calendar_to_expire') }}</label>
              </div>
              <input type="date" id="expiry_date"
                     onfocus="this.showPicker()"
                     class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                     @change="calculateDays">
              <input type="hidden" id="expiry_days" name="expiry_days" :value="daysCount">
            </div>
          </div>

          <div class="flex justify-end">
            <button type="submit"
                    class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-700">
              {{ $t('promotion.create') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, nextTick } from 'vue';

const props = defineProps({
  pizza: {
    type: Object,
    required: true
  },
  userRoles: {
    type: Array,
    default: () => []
  },
  categories: {
    type: Array,
    default: () => []
  }
});

const isAdmin = computed(() => window.isAdmin === true);
const newTopping = ref('');
const toppings = ref([...(props.pizza.toppings || [])]);

// Form validation
const priceError = ref('');
const formErrors = ref({});

// Initialize form fields with data from the pizza object
const pizzaName = ref(props.pizza.name);
const pizzaPrice = ref(props.pizza.price);
const smallPrice = ref(props.pizza.priceSmall !== null ? props.pizza.priceSmall : '');
const largePrice = ref(props.pizza.priceLarge !== null ? props.pizza.priceLarge : '');
const selectedCategoryId = ref(props.pizza.category ? props.pizza.category.id : '');

// Validate medium pizza price
const validatePizzaPrice = () => {
  if (!pizzaPrice.value || parseFloat(pizzaPrice.value) <= 0) {
    priceError.value = $t('pizza.price_warning.message');
    return false;
  }
  priceError.value = '';
  return true;
};

// Add a new topping
const addTopping = () => {
  if (newTopping.value.trim()) {
    toppings.value.push(newTopping.value.trim());
    newTopping.value = '';
    // Focus the input field again
    nextTick(() => {
      if (toppingInput.value) {
        toppingInput.value.focus();
      }
    });
  }
};

// Handle space key press in topping input
const handleToppingSpace = (event) => {
  // Prevent the default space behavior
  event.preventDefault();
  
  // If there is text before the space, add it as a topping
  if (newTopping.value.trim()) {
    addTopping();
  } else {
    // If it's just a space, ignore it
    newTopping.value = '';
  }
};

// Remove a topping at the specified index
const removeToppingAt = (index) => {
  toppings.value.splice(index, 1);
};

// Reference to the topping input field
const toppingInput = ref(null);

// Form submission via fetch API
const submitForm = async () => {
  try {
    // Validate required fields before submission
    if (!validatePizzaPrice()) {
      return;
    }

    if (!pizzaName.value || pizzaName.value.trim() === '') {
      formErrors.value = { ...formErrors.value, name: 'Pizza name is required' };
      return;
    }

    const formData = {
      name: pizzaName.value,
      price: parseFloat(pizzaPrice.value),
      priceSmall: smallPrice.value === '' ? null : parseFloat(smallPrice.value),
      priceLarge: largePrice.value === '' ? null : parseFloat(largePrice.value),
      category: selectedCategoryId.value,
      toppings: toppings.value.filter(t => t.trim() !== '')
    };

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const response = await fetch(`/pizza/${props.pizza.id}/edit`, {
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
        alert(result?.error || 'An error occurred while saving the pizza.');
      }
    }
  } catch (error) {
    console.error('Error submitting form:', error);
    alert('An error occurred while saving the pizza.');
  }
};

// Calendar for promotions
const daysCount = ref(7);
const selectedDate = ref(null);

const selectedDateFormatted = computed(() => {
  if (!selectedDate.value) return 'No selected date';
  return selectedDate.value.toISOString().split('T')[0];
});

const calculateDays = (event) => {
  const date = new Date(event.target.value);
  selectedDate.value = date;

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const diffTime = Math.abs(date - today);
  daysCount.value = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
};
</script>