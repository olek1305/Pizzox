<template>
  <div class="w-full lg:w-[20%] min-h-screen fixed top-0 right-0 bg-gray-100
    overflow-y-auto transition-transform duration-300 pt-14">
    <h1 class="text-3xl font-bold py-8 text-center">{{ $t('cart.title') }}</h1>

    <div v-if="cartItems && cartItems.length === 0" class="text-center py-2 bg-white shadow-md w-full">
      <p class="text-xl text-gray-500">{{ $t('cart.empty') }}</p>
    </div>

    <div v-else-if="cartItems && cartItems.length > 0" class="bg-white shadow-md w-full">
      <!-- Cart items -->
      <div v-for="(item, index) in cartItems" :key="index" class="p-4 text-sm">
        <div class="flex flex-wrap items-center">
          <!-- Item info -->
          <div class="w-full sm:w-1/2 mb-2 sm:mb-0">
            <h3 class="font-bold">{{ capitalize(item.type) }}: {{ item.item_name }}</h3>
          </div>

          <!-- Quantity controls TODO fix it to work -->
          <div class="w-full sm:w-1/4 flex items-center justify-start sm:justify-center mb-2 sm:mb-0">
            <form :action="`/cart/update/${item.type}/${item.id}`" method="POST" class="flex items-center">
              <button type="submit" name="action" value="decrease"
                      class="bg-gray-200 text-gray-700 px-2 py-1 hover:bg-gray-300">
                -
              </button>
              <span class="px-3 py-1 bg-gray-100">{{ item.quantity }}</span>
              <button type="submit" name="action" value="increase"
                      class="bg-gray-200 text-gray-700 px-2 py-1 hover:bg-gray-300">
                +
              </button>
            </form>
          </div>

          <!-- Price -->
          <div class="w-full sm:w-1/8 text-right sm:text-center mb-2 sm:mb-0">
            <template v-if="item.original_price !== undefined && item.original_price !== item.price">
              <div class="flex flex-col">
                  <span class="line-through text-gray-500">
                    {{ calculatePrice(item.original_price, item.quantity) }} {{ $currency }}
                  </span>
                                  <span class="text-red-600 font-bold">
                    {{ calculatePrice(item.price, item.quantity) }} {{ $currency }}
                  </span>
              </div>
            </template>
            <template v-else>
                <span class="font-semibold">
                  {{ calculatePrice(item.price, item.quantity) }} {{ $currency }}
                </span>
            </template>
          </div>

          <!-- Remove button TODO fix remove without reload page -->
          <div class="w-full sm:w-1/8 text-right">
            <form :action="`/cart/remove/${item.type}/${item.id}`" method="POST">
              <button type="submit" class="text-red-500 hover:text-red-700">
                {{ $t('cart.remove') }}
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Order summary (single column, at the bottom) -->
      <div class="border-t p-4">
        <div class="max-w-lg mx-auto">
          <h2 class="text-xl font-bold mb-4">{{ $t('cart.summary') }}</h2>

          <div class="space-y-2">
            <div class="flex justify-between">
              <span>{{ $t('cart.items_count') }}:</span>
              <span>{{ cartItems.length }}</span>
            </div>

            <div class="flex justify-between font-semibold">
              <span>{{ $t('cart.subtotal') }}:</span>
              <span>{{ totalCost }} {{ $currency }}</span>
            </div>

            <div class="pt-3 border-t mt-3">
              <div class="flex justify-between font-bold text-lg">
                <span>{{ $t('cart.total') }}:</span>
                <span>{{ totalCost }} {{ $currency }}</span>
              </div>
            </div>
          </div>

          <div class="mt-6 flex flex-col sm:flex-row gap-4">
            <a href="/checkout" class="block text-center bg-green-600 hover:bg-green-700 text-white py-3 px-4 font-bold flex-1">
              {{ $t('cart.checkout') }}
            </a>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-10 bg-white shadow-md w-full">
      <p class="text-xl text-gray-500">{{ $t('cart.loading') || 'Loading cart...' }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

const cartItems = ref([]);
const loading = ref(true);

// Function to capitalize the first letter
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
  if (!cartItems.value || cartItems.value.length === 0) return '0.00';

  return cartItems.value.reduce((total, item) => {
    const itemPrice = typeof item.price === 'object' && item.price !== null && 'price' in item.price
        ? item.price.price
        : item.price;
    return total + (itemPrice * item.quantity);
  }, 0).toFixed(2);
});

onMounted(() => {
  try {
    cartItems.value = window.cartData || [];
  } catch (error) {
    console.error('Failed to load cart data:', error);
    cartItems.value = [];
  } finally {
    loading.value = false;
  }
});
</script>