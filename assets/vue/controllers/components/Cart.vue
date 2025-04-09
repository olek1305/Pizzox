<template>
  <div class="relative">
    <!-- Cart Icon -->
    <a :href="'/cart'" id="cart-trigger" class="relative block text-white hover:text-gray-300 font-medium px-3 py-1" @mouseenter="showDropdown = true" @mouseleave="showDropdown = false">
      <span class="flex items-center">
        {{ $t('cart.title') }}
        <span v-if="cartItems.length > 0" class="ml-1 bg-red-500 text-xs text-white font-bold rounded-full w-5 h-5 flex items-center justify-center">
          {{ cartItems.length }}
        </span>
      </span>
    </a>

    <!-- Dropdown Area -->
    <div
        id="cart-dropdown"
        class="absolute right-0 bg-white shadow-md rounded-lg p-4 w-72"
        :class="{ 'hidden': !showDropdown }"
        @mouseenter="showDropdown = true"
        @mouseleave="showDropdown = false"
    >
      <h4 class="text-lg font-bold mb-2">{{ $t('cart.title') }}</h4>
      <div v-if="cartItems.length === 0">
        <p class="text-gray-500 text-sm">{{ $t('cart.empty') }}</p>
      </div>
      <div v-else>
        <ul>
          <li v-for="(item, index) in cartItems" :key="index">
            <strong>{{ capitalize(item.type) }}:</strong> {{ item.item_name }} x {{ item.quantity }} -
            <template v-if="item.original_price !== undefined && item.original_price !== item.price">
              <span class="line-through text-gray-500">
                {{ calculatePrice(item.original_price, item.quantity) }} {{ currency }}
              </span>
              <span class="text-red-600 font-bold">
                {{ calculatePrice(item.price, item.quantity) }} {{ currency }}
              </span>
            </template>
            <template v-else>
              {{ calculatePrice(item.price, item.quantity) }} {{ currency }}
            </template>
          </li>
        </ul>
        <div class="mt-3 pt-2 border-t border-gray-200">
          <div class="flex justify-between font-bold">
            <span>{{ $t('cart.total') }}:</span>
            <span>{{ totalCost }} {{ currency }}</span>
          </div>
        </div>
        <div class="mt-3 text-center">
          <a :href="'/cart'" class="inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 w-full">
            {{ $t('cart.view') }}
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

const showDropdown = ref(false);
const cartItems = ref([]);
const currency = ref('Please setting your currency');

// Function to capitalize first letter
const capitalize = (str) => {
  if (!str) return '';
  return str.charAt(0).toUpperCase() + str.slice(1);
};

// Calculate price considering both simple numbers and objects with price property
const calculatePrice = (price, quantity) => {
  if (typeof price === 'object' && price !== null && 'price' in price) {
    return (price.price * quantity).toFixed(2);
  }
  return (price * quantity).toFixed(2);
};

// Calculate total cost
const totalCost = computed(() => {
  return cartItems.value.reduce((total, item) => {
    const itemPrice = typeof item.price === 'object' && item.price !== null && 'price' in item.price
        ? item.price.price
        : item.price;
    return total + (itemPrice * item.quantity);
  }, 0).toFixed(2);
});

onMounted(() => {
  try {
    // Use the data from window object instead of fetching
    if (window.cartData) {
      cartItems.value = window.cartData || [];
      currency.value = window.cartCurrency || 'zł';
    }
  } catch (error) {
    console.error('Failed to load cart data:', error);
  }
});

</script>