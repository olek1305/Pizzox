<template>
  <div class="w-full lg:w-[20%] min-h-screen fixed top-0 right-0 bg-white
    overflow-y-auto transition-transform duration-300 pt-14">
    <h1 class="text-3xl font-bold py-8 text-center">{{ $t('cart.title') }}</h1>

    <div v-if="cartItems && cartItems.length === 0" class="text-center py-2 bg-white shadow-md w-full">
      <p class="text-xl text-gray-500">{{ $t('cart.empty') }}</p>
    </div>

    <div v-else-if="cartItems && cartItems.length > 0" class="shadow-md w-full">
      <!-- Cart items -->
      <div v-for="(item, index) in cartItems" :key="index" class="p-4 text-sm">
        <div class="flex flex-wrap items-center">
          <!-- Item info -->
          <div class="w-full sm:w-1/2 mb-2 sm:mb-0">
            <h3 class="font-bold">{{ capitalize(item.type) }}: {{ item.item_name }}</h3>
            <span v-if="item.type === 'pizza'" class="text-xs text-gray-600">({{ item.size.toUpperCase() }})</span>
          </div>

          <!-- Quantity controls -->
          <div class="w-full sm:w-1/4 flex items-center justify-start sm:justify-center mb-2 sm:mb-0">
            <div class="flex items-center">
              <button @click="updateQuantity(item.type, item.item_id, 'decrease')"
                      class="bg-gray-200 text-gray-700 px-2 py-1 hover:bg-gray-300">
                -
              </button>
              <span class="px-3 py-1 bg-gray-100">{{ item.quantity }}</span>
              <button @click="updateQuantity(item.type, item.item_id, 'increase')"
                      class="bg-gray-200 text-gray-700 px-2 py-1 hover:bg-gray-300">
                +
              </button>
            </div>
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
                  <span v-if="item.coupon" class="text-xs text-gray-600">
                    {{ item.coupon.type === 'fixed' ? `-${item.coupon.discount} ${$currency}` : `-${item.coupon.discount}%` }}
                  </span>
              </div>
            </template>
            <template v-else>
                <span class="font-semibold">
                  {{ calculatePrice(item.price, item.quantity) }} {{ $currency }}
                </span>
            </template>
          </div>

          <!-- Remove button -->
          <div class="w-full sm:w-1/8 text-right">
            <button @click="removeItem(item.type, item.item_id)" class="text-red-500 hover:text-red-700">
              {{ $t('cart.remove') }}
            </button>
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
            <button 
              @click="showCheckoutModal = true" 
              class="block text-center bg-green-600 hover:bg-green-700 text-white py-3 px-4 font-bold flex-1"
              :disabled="cartItems.length === 0">
              {{ $t('cart.checkout') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-10 bg-white shadow-md w-full">
      <p class="text-xl text-gray-500">{{ $t('cart.loading') || 'Loading cart...' }}</p>
    </div>
    
    <!-- Checkout Modal -->
    <div v-if="showCheckoutModal" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="absolute inset-0 bg-black bg-opacity-50" @click="showCheckoutModal = false"></div>
      <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4 z-10 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-semibold">{{ $t('cart.checkout') }}</h3>
          <button @click="showCheckoutModal = false" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        
        <div class="mb-4">
          <h4 class="text-lg font-medium mb-4">{{ $t('order.customer_information') }}</h4>
          
          <form @submit.prevent="submitOrder" class="space-y-4">
            <div>
              <label for="fullName" class="block text-sm font-medium text-gray-700">{{ $t('order.full_name') }}</label>
              <input 
                type="text" 
                id="fullName" 
                v-model="customerInfo.fullName" 
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" 
                required
              />
            </div>
            
            <div>
              <label for="email" class="block text-sm font-medium text-gray-700">{{ $t('order.email') }}</label>
              <input 
                type="email" 
                id="email" 
                v-model="customerInfo.email" 
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500"
              />
              <p class="text-xs text-gray-500 mt-1">{{ $t('cart.email_optional') || 'Optional' }}</p>
            </div>
            
            <div>
              <label for="address" class="block text-sm font-medium text-gray-700">{{ $t('order.address') }}</label>
              <input 
                type="text" 
                id="address" 
                v-model="customerInfo.address" 
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500"
                required
              />
            </div>
            
            <div>
              <label for="phone" class="block text-sm font-medium text-gray-700">{{ $t('order.phone') }}</label>
              <input 
                type="tel" 
                id="phone" 
                v-model="customerInfo.phone" 
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500"
                required
              />
            </div>
            
            <div class="pt-4 border-t border-gray-200">
              <h5 class="font-medium mb-2">{{ $t('cart.summary') }}</h5>
              <div class="flex justify-between mb-1">
                <span>{{ $t('cart.items_count') }}:</span>
                <span>{{ cartItems.length }}</span>
              </div>
              <div class="flex justify-between font-bold">
                <span>{{ $t('cart.total') }}:</span>
                <span>{{ totalCost }} {{ $currency }}</span>
              </div>
            </div>
            
            <div v-if="orderError" class="text-center py-3 bg-red-100 rounded-md mb-4">
              <p class="text-red-700 font-medium">{{ orderError }}</p>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4">
              <button 
                type="button" 
                @click="showCheckoutModal = false" 
                class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-100"
                :disabled="orderSubmitting"
              >
                {{ $t('action.cancel') }}
              </button>
              <button 
                type="submit" 
                class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-700 disabled:bg-blue-300 disabled:cursor-not-allowed"
                :disabled="orderSubmitting || cartItems.length === 0"
              >
                <span v-if="orderSubmitting">
                  {{ $t('order.processing') || 'Processing...' }}
                </span>
                <span v-else>
                  {{ $t('cart.order_now') || 'Order Now' }}
                </span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, defineExpose } from 'vue';

const cartItems = ref([]);
const loading = ref(true);
const showCheckoutModal = ref(false);
const customerInfo = ref({
  fullName: '',
  email: '',
  address: '',
  phone: ''
});

// Function to load cart data from a server
const loadCartData = async () => {
  loading.value = true;
  try {
    const response = await fetch('/cart', {
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    if (response.ok) {
      const data = await response.json();
      cartItems.value = data.items || [];
    } else {
      console.error('Failed to load cart data');
    }
  } catch (error) {
    console.error('Error loading cart data:', error);
  } finally {
    loading.value = false;
  }
};

// Expose the loadCartData method to parent components
defineExpose({
  loadCartData
});

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
  return ((parseFloat(price) || 0) * quantity).toFixed(2);
};

// Calculate total cost
const totalCost = computed(() => {
  if (!cartItems.value || cartItems.value.length === 0) return '0.00';

  return cartItems.value.reduce((total, item) => {
    let itemPrice;
    // Simple parse the price directly - this is already the discounted price from the server
    itemPrice = parseFloat(item.price) || 0;
    return total + (itemPrice * item.quantity);
  }, 0).toFixed(2);
});

// Update item quantity asynchronously
const updateQuantity = async (type, itemId, action) => {
  try {
    const response = await fetch(`/cart/update/${type}/${itemId}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: `action=${action}`
    });

    if (response.ok) {
      // Reload cart data after successful update
      await loadCartData();
    } else {
      console.error('Failed to update item quantity');
    }
  } catch (error) {
    console.error('Error updating quantity:', error);
  }
};

// Function to remove item from the cart
const removeItem = async (type, itemId) => {
  try {
    const response = await fetch(`/cart/remove/${type}/${itemId}`, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    if (response.ok) {
      // Reload cart data after successful removal
      await loadCartData();
    } else {
      console.error('Failed to remove item from cart');
    }
  } catch (error) {
    console.error('Error removing item:', error);
  }
};

// Submit an order to the server
const orderSubmitting = ref(false);
const orderError = ref('');

const submitOrder = () => {
  orderSubmitting.value = true;
  orderError.value = '';
  
  // Validate form data
  if (!customerInfo.value.fullName || !customerInfo.value.address || !customerInfo.value.phone) {
    orderError.value = 'Please fill in all required fields';
    orderSubmitting.value = false;
    return;
  }
  
  // Create a form to submit to the checkout endpoint
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = '/checkout';
  
  // Add customer info to form
  for (const [key, value] of Object.entries(customerInfo.value)) {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = key;
    input.value = value;
    form.appendChild(input);
  }
  
  // Submit the form
  document.body.appendChild(form);
  form.submit();
};


onMounted(() => {
  // Load cart data when the component mounts
  loadCartData();
});
</script>