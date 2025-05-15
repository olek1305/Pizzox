<template>
  <transition name="modal-fade">
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="absolute inset-0 bg-black/60" @click="closeModal"></div>
      <transition name="modal-slide">
        <div v-if="show" class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4 z-10">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold">
              {{ itemType === 'pizza' ? $t('pizza.size_selector') : $t('addition.quantity_selector') }}
            </h3>
            <button @click="closeModal" class="text-gray-500 hover:text-gray-700">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

          <div class="mb-4">
            <h4 class="text-lg font-medium mb-2">{{ item.name }}</h4>
            <p v-if="itemType === 'pizza'" class="text-sm text-gray-600 mb-3">{{ formatToppings(item.toppings) }}</p>
          </div>

          <!-- Pizza size options -->
          <div v-if="itemType === 'pizza'" class="space-y-4">
            <!-- Small size option -->
            <div v-if="item.priceSmall"
                 class="flex flex-col p-3 border rounded-lg hover:bg-gray-50"
                 :class="{'bg-blue-50 border-blue-400': selectedSize === 'small'}"
                 @click="selectedSize = 'small'">
              <div class="flex justify-between items-center">
                <span class="font-medium">{{ $t('pizza.size_small') }}</span>
                <div class="text-right">
                  <div v-if="item.coupon">
                    <span class="text-gray-500 line-through mr-2">{{ item.priceSmall }} {{ $currency }}</span>
                    <span v-if="item.coupon.type === 'fixed'" class="text-red-600 font-semibold">
                  {{ calculatePrice(item.priceSmall, item.coupon, 'fixed') }} {{ $currency }}
                </span>
                    <span v-else class="text-red-600 font-semibold">
                  {{ calculatePrice(item.priceSmall, item.coupon, 'percent') }} {{ $currency }}
                </span>
                  </div>
                  <span v-else>{{ item.priceSmall }} {{ $currency }}</span>
                </div>
              </div>

              <div v-if="selectedSize === 'small'" class="flex justify-between items-center mt-3 pt-3 border-t">
                <div class="flex items-center">
                  <button @click.stop="decreaseQuantity"
                          class="w-8 h-8 bg-gray-200 rounded-md flex items-center justify-center">
                    <span class="text-xl font-bold">-</span>
                  </button>
                  <input type="number" v-model="quantity" min="1"
                         class="w-12 border rounded-md px-2 py-1 text-center mx-2"/>
                  <button @click.stop="increaseQuantity"
                          class="w-8 h-8 bg-gray-200 rounded-md flex items-center justify-center">
                    <span class="text-xl font-bold">+</span>
                  </button>
                </div>
                <div class="font-bold">
                  {{ getTotalPrice('small') }} {{ $currency }}
                </div>
              </div>
            </div>

            <!-- Medium size option -->
            <div class="flex flex-col p-3 border rounded-lg hover:bg-gray-50"
                 :class="{'bg-blue-50 border-blue-400': selectedSize === 'medium'}"
                 @click="selectedSize = 'medium'">
              <div class="flex justify-between items-center">
                <span class="font-medium">{{ $t('pizza.size_medium') }}</span>
                <div class="text-right">
                  <div v-if="item.coupon">
                    <span class="text-gray-500 line-through mr-2">{{ item.price }} {{ $currency }}</span>
                    <span v-if="item.coupon.type === 'fixed'" class="text-red-600 font-semibold">
                  {{ calculatePrice(item.price, item.coupon, 'fixed') }} {{ $currency }}
                </span>
                    <span v-else class="text-red-600 font-semibold">
                  {{ calculatePrice(item.price, item.coupon, 'percent') }} {{ $currency }}
                </span>
                  </div>
                  <span v-else>{{ item.price }} {{ $currency }}</span>
                </div>
              </div>

              <div v-if="selectedSize === 'medium'" class="flex justify-between items-center mt-3 pt-3 border-t">
                <div class="flex items-center">
                  <button @click.stop="decreaseQuantity"
                          class="w-8 h-8 bg-gray-200 rounded-md flex items-center justify-center">
                    <span class="text-xl font-bold">-</span>
                  </button>
                  <input type="number" v-model="quantity" min="1"
                         class="w-12 border rounded-md px-2 py-1 text-center mx-2"/>
                  <button @click.stop="increaseQuantity"
                          class="w-8 h-8 bg-gray-200 rounded-md flex items-center justify-center">
                    <span class="text-xl font-bold">+</span>
                  </button>
                </div>
                <div class="font-bold">
                  {{ getTotalPrice('medium') }} {{ $currency }}
                </div>
              </div>
            </div>

            <!-- Large size option -->
            <div v-if="item.priceLarge"
                 class="flex flex-col p-3 border rounded-lg hover:bg-gray-50"
                 :class="{'bg-blue-50 border-blue-400': selectedSize === 'large'}"
                 @click="selectedSize = 'large'">
              <div class="flex justify-between items-center">
                <span class="font-medium">{{ $t('pizza.size_large') }}</span>
                <div class="text-right">
                  <div v-if="item.coupon">
                    <span class="text-gray-500 line-through mr-2">{{ item.priceLarge }} {{ $currency }}</span>
                    <span v-if="item.coupon.type === 'fixed'" class="text-red-600 font-semibold">
                  {{ calculatePrice(item.priceLarge, item.coupon, 'fixed') }} {{ $currency }}
                </span>
                    <span v-else class="text-red-600 font-semibold">
                  {{ calculatePrice(item.priceLarge, item.coupon, 'percent') }} {{ $currency }}
                </span>
                  </div>
                  <span v-else>{{ item.priceLarge }} {{ $currency }}</span>
                </div>
              </div>

              <div v-if="selectedSize === 'large'" class="flex justify-between items-center mt-3 pt-3 border-t">
                <div class="flex items-center">
                  <button @click.stop="decreaseQuantity"
                          class="w-8 h-8 bg-gray-200 rounded-md flex items-center justify-center">
                    <span class="text-xl font-bold">-</span>
                  </button>
                  <input type="number" v-model="quantity" min="1"
                         class="w-12 border rounded-md px-2 py-1 text-center mx-2"/>
                  <button @click.stop="increaseQuantity"
                          class="w-8 h-8 bg-gray-200 rounded-md flex items-center justify-center">
                    <span class="text-xl font-bold">+</span>
                  </button>
                </div>
                <div class="font-bold">
                  {{ getTotalPrice('large') }} {{ $currency }}
                </div>
              </div>
            </div>
          </div>

          <!-- Addition quantity selector -->
          <div v-if="itemType === 'addition'" class="mt-4 p-3 border rounded-lg">
            <div class="flex justify-between items-center mb-3">
              <label class="font-medium">{{ $t('order.quantity') }}:</label>
              <div>
            <span v-if="item.coupon">
              <span class="text-gray-500 line-through mr-2">{{ item.price }} {{ $currency }}</span>
              <span v-if="item.coupon.type === 'fixed'" class="text-red-600 font-semibold">
                {{ calculatePrice(item.price, item.coupon, 'fixed') }} {{ $currency }}
              </span>
              <span v-else class="text-red-600 font-semibold">
                {{ calculatePrice(item.price, item.coupon, 'percent') }} {{ $currency }}
              </span>
            </span>
                <span v-else>{{ item.price }} {{ $currency }}</span>
              </div>
            </div>
            <div class="flex justify-between items-center">
              <div class="flex items-center">
                <button @click.stop="decreaseQuantity"
                        class="w-8 h-8 bg-gray-200 rounded-md flex items-center justify-center">
                  <span class="text-xl font-bold">-</span>
                </button>
                <input type="number" v-model="quantity" min="1"
                       class="w-12 border rounded-md px-2 py-1 text-center mx-2"/>
                <button @click.stop="increaseQuantity"
                        class="w-8 h-8 bg-gray-200 rounded-md flex items-center justify-center">
                  <span class="text-xl font-bold">+</span>
                </button>
              </div>
              <div class="font-bold">
                {{ getTotalPriceAddition() }} {{ $currency }}
              </div>
            </div>
          </div>

          <div class="mt-6 flex justify-end space-x-3">
            <button @click="closeModal" class="px-4 py-2 border rounded-md hover:bg-gray-100">
              {{ $t('action.cancel') }}
            </button>
            <button @click="addToCartWithQuantity"
                    class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-700">
              {{ $t('action.add') }}
            </button>
          </div>
        </div>
      </transition>
    </div>
  </transition>
</template>

<script setup>
import {ref, watch} from 'vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  item: {
    type: Object,
    required: true,
  },
  itemType: {
    type: String,
    default: 'pizza',
    validator: (value) => ['pizza', 'addition'].includes(value),
  }
});

const emit = defineEmits(['close', 'add-to-cart']);

const quantity = ref(1);
const selectedSize = ref('medium');

watch(() => props.show, (newValue) => {
  if (newValue) {
    quantity.value = 1;
    selectedSize.value = 'medium';
  }
});

const increaseQuantity = (event) => {
  event.stopPropagation();
  const currentQuantity = parseInt(quantity.value) || 0;
  quantity.value = currentQuantity + 1;
};

const decreaseQuantity = (event) => {
  event.stopPropagation();
  const currentQuantity = parseInt(quantity.value) || 0;
  if (currentQuantity > 1) {
    quantity.value = currentQuantity - 1;
  } else {
    quantity.value = 1;
  }
};

// Add watcher to validate quantity when it changes
watch(quantity, (newValue) => {
  const numValue = parseInt(newValue);
  if (isNaN(numValue) || numValue < 1) {
    quantity.value = 1;
  }
});

const closeModal = () => {
  emit('close');
};

const getItemPrice = (size) => {
  if (props.itemType === 'pizza') {
    if (size === 'small' && props.item.priceSmall) {
      return props.item.coupon
          ? calculatePriceValue(props.item.priceSmall, props.item.coupon)
          : parseFloat(props.item.priceSmall);
    } else if (size === 'large' && props.item.priceLarge) {
      return props.item.coupon
          ? calculatePriceValue(props.item.priceLarge, props.item.coupon)
          : parseFloat(props.item.priceLarge);
    } else {
      return props.item.coupon
          ? calculatePriceValue(props.item.price, props.item.coupon)
          : parseFloat(props.item.price);
    }
  } else {
    return props.item.coupon
        ? calculatePriceValue(props.item.price, props.item.coupon)
        : parseFloat(props.item.price);
  }
};

const getTotalPrice = (size) => {
  const itemPrice = getItemPrice(size);
  return (itemPrice * quantity.value).toFixed(2);
};

const getTotalPriceAddition = () => {
  const itemPrice = getItemPrice();
  return (itemPrice * quantity.value).toFixed(2);
};

const calculatePriceValue = (price, coupon) => {
  if (coupon.type === 'fixed') {
    return parseFloat(price) - coupon.discount;
  } else {
    return parseFloat(price) * (1 - coupon.discount / 100);
  }
};

const calculatePrice = (price, coupon, type) => {
  if (type === 'fixed') {
    return (parseFloat(price) - coupon.discount).toFixed(2);
  } else {
    return (parseFloat(price) * (1 - coupon.discount / 100)).toFixed(2);
  }
};

const addToCartWithQuantity = async () => {
  if (props.itemType === 'pizza' && !selectedSize.value) {
    return; // Size is required for pizzas
  }

  const formData = new FormData();
  formData.append('quantity', quantity.value);

  if (props.itemType === 'pizza') {
    formData.append('size', selectedSize.value);
  }

  try {
    const url = props.itemType === 'pizza'
        ? `/cart/add/pizza/${props.item.id}`
        : `/cart/add/addition/${props.item.id}`;

    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: formData
    });

    if (response.ok) {
      // Emit an event that will be caught by parent to refresh cart
      emit('item-added');
      closeModal();
    } else {
      console.error('Failed to add item to cart');
    }
  } catch (error) {
    console.error('Error adding item to cart:', error);
  }
};

const formatToppings = (toppings) => {
  if (!toppings || toppings.length === 0) {
    return "";
  }

  if (Array.isArray(toppings)) {
    return toppings.join(', ');
  }

  return toppings;
};
</script>

<style>
.modal-fade-enter-active, .modal-fade-leave-active {
  transition: opacity 0.4s ease, filter 0.3s ease;
}
.modal-fade-enter-from, .modal-fade-leave-to {
  opacity: 0;
  filter: blur(6px);
}
</style>
