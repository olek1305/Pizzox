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
      <h1 class="text-3xl font-bold mb-6">{{ $t('pizza.create') }}</h1>

      <!-- Pizza Create Form -->
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
                   @input="handlePriceChange">
            <div v-if="priceError" class="text-red-500 text-sm mt-1">
              {{ priceError }}
            </div>
            <small class="text-gray-700">{{ $t('pizza.topping_input.must_be_greater_than_zero') }}</small>
          </div>
        </div>

        <!-- Size selection -->
        <div class="mb-4 mt-4">
          <label class="block text-gray-700 mb-2 font-medium">{{ $t('pizza.available_sizes') || 'Available sizes' }}:</label>
          <div class="flex space-x-4">
            <label class="inline-flex items-center">
              <input type="checkbox" v-model="hasSmallSize" class="text-blue-600">
              <span class="ml-2">Small</span>
            </label>
            <label class="inline-flex items-center">
              <input type="checkbox" v-model="hasLargeSize" class="text-blue-600">
              <span class="ml-2">Large</span>
            </label>
          </div>
        </div>
        
        <!-- Additional price fields that appear conditionally -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <div v-if="hasSmallSize">
            <label for="pizza_price_small" class="block text-sm font-medium text-gray-700 mb-1">{{ $t('pizza.price_small') }}</label>
            <input type="number" id="pizza_price_small" v-model="smallPrice" step="0.01" min="0"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <small class="text-gray-500">{{ $t('pizza.small_price_help') }}</small>
          </div>
          
          <div v-if="hasLargeSize">
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
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick, watch } from 'vue';

const props = defineProps({
  categories: {
    type: Array,
    default: () => []
  },
  priceSettings: {
    type: Object,
    default: () => ({
      calculationType: 'fixed',
      smallModifier: 8,
      largeModifier: 12
    })
  }
});

// Form fields
const pizzaName = ref('');
const pizzaPrice = ref('');
const smallPrice = ref('');
const largePrice = ref('');
const selectedCategoryId = ref('');
const toppings = ref([]);
const newTopping = ref('');
const toppingInput = ref(null);
const priceError = ref('');
const formErrors = ref({});

// Size checkboxes
const hasSmallSize = ref(false);
const hasLargeSize = ref(false);

// Watch for changes in the size selections
watch(hasSmallSize, (newValue) => {
  if (newValue) {
    // Calculate small price based on medium price
    if (pizzaPrice.value && parseFloat(pizzaPrice.value) > 0) {
      smallPrice.value = calculatePrice(parseFloat(pizzaPrice.value), 'small').toFixed(2);
    }
  } else {
    // Clear small price when checkbox is unchecked
    smallPrice.value = '';
  }
});

watch(hasLargeSize, (newValue) => {
  if (newValue) {
    // Calculate large price based on medium price
    if (pizzaPrice.value && parseFloat(pizzaPrice.value) > 0) {
      largePrice.value = calculatePrice(parseFloat(pizzaPrice.value), 'large').toFixed(2);
    }
  } else {
    // Clear large price when checkbox is unchecked
    largePrice.value = '';
  }
});

// Function to calculate price based on modifiers
const calculatePrice = (basePrice, size) => {
  const { calculationType, smallModifier, largeModifier } = props.priceSettings;
  
  if (calculationType === 'fixed') {
    if (size === 'small') return Math.max(0, basePrice - smallModifier);
    if (size === 'large') return basePrice + largeModifier;
  } else {
    // Percentage calculation
    if (size === 'small') return basePrice * (1 - (smallModifier / 100));
    if (size === 'large') return basePrice * (1 + (largeModifier / 100));
  }
  return basePrice; // Default to medium
};

// Handle price change and update dependent prices
const handlePriceChange = () => {
  validatePizzaPrice();
  
  if (!pizzaPrice.value || parseFloat(pizzaPrice.value) <= 0) {
    return;
  }
  
  const basePrice = parseFloat(pizzaPrice.value);
  
  // Update small price if that size is selected
  if (hasSmallSize.value) {
    smallPrice.value = calculatePrice(basePrice, 'small').toFixed(2);
  }
  
  // Update large price if that size is selected
  if (hasLargeSize.value) {
    largePrice.value = calculatePrice(basePrice, 'large').toFixed(2);
  }
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

// Validate medium pizza price
const validatePizzaPrice = () => {
  if (!pizzaPrice.value || parseFloat(pizzaPrice.value) <= 0) {
    priceError.value = $t('pizza.price_warning.message');
    return false;
  }
  priceError.value = '';
  return true;
};

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
      priceSmall: hasSmallSize.value && smallPrice.value ? parseFloat(smallPrice.value) : null,
      priceLarge: hasLargeSize.value && largePrice.value ? parseFloat(largePrice.value) : null,
      category: selectedCategoryId.value,
      toppings: toppings.value.filter(t => t.trim() !== '')
    };

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const response = await fetch('/pizza/create', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken || '',
      },
      body: JSON.stringify(formData)
    });
    
    const result = await response.json();
    
    if (response.ok) {
      window.location.href = '/pizza';
    } else {
      if (result.validationErrors) {
        // Display validation errors from the server
        formErrors.value = result.validationErrors;
        
        // Specifically, set price error if returned by server
        if (result.validationErrors.price) {
          priceError.value = result.validationErrors.price;
        }
      } else {
        alert(result.error || 'An error occurred while saving the pizza.');
      }
    }
  } catch (error) {
    console.error('Error submitting form:', error);
    alert('An error occurred while saving the pizza.');
  }
};
</script>