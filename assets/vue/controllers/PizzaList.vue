<template>
  <div class="container mx-auto p-12">
    <!-- Admin buttons section -->
    <div class="flex justify-center items-center mb-4" v-if="isAdmin">
      <div class="flex space-x-3">
        <a :href="createPizzaPath" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-700">
          {{ $t('pizza.create') }}
        </a>
        <a :href="createAdditionPath" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-700">
          {{ $t('addition.create') }}
        </a>
        <a :href="settingsPath" class="px-4 py-2 bg-red-400 text-white rounded-lg hover:bg-blue-700">
          {{ $t('settings.title') }}
        </a>
        <a :href="paymentHistoryPath" class="px-4 py-2 bg-red-700 text-white rounded-lg hover:bg-blue-700">
          {{ $t('paymentHistory') }}
        </a>
      </div>
    </div>

    <!-- Pizza list section -->
    <div v-if="pizzas.length === 0" class="bg-white shadow-md rounded-lg p-6 text-center text-gray-600 mb-3">
      <h1 class="text-3xl font-bold">{{ $t('pizza.list_title') }}</h1>
      {{ $t('pizza.no_pizzas') }}
    </div>
    <div v-else class="bg-white shadow-md rounded-lg overflow-hidden mb-3">
      <h1 class="text-3xl font-bold ml-3 mt-1">{{ $t('pizza.list_title') }}</h1>
      <table class="w-full text-left border-collapse">
        <thead>
        <tr>
          <th class="px-6 py-3">{{ $t('pizza.name') }}</th>
          <th class="px-6 py-3">{{ $t('pizza.price') }}</th>
          <th class="px-6 py-3">{{ $t('pizza.toppings') }}</th>
          <th class="px-6 py-3">{{ $t('pizza.category') }}</th>
          <th class="px-6 py-3">{{ $t('action.label') }}</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="pizza in pizzas" :key="pizza.id" class="border-b hover:bg-gray-100">
          <td class="px-6 py-4">{{ pizza.name }}</td>
          <td class="px-6 py-4">
            <div v-if="pizza.coupon" class="flex flex-col">
              <span class="text-gray-500 line-through">{{ pizza.price }} {{ currency }}</span>
              <span v-if="pizza.coupon.type === 'fixed'" class="text-red-600 font-semibold">
                {{ pizza.price - pizza.coupon.discount }} {{ currency }}
              </span>
              <span v-else class="text-red-600 font-semibold">
                {{ pizza.price * (1 - pizza.coupon.discount / 100) }} {{ currency }}
              </span>
            </div>
            <template v-else>
              {{ formatPrice(pizza.price) }}
            </template>
          </td>
          <td class="px-6 py-4">{{ formatToppings(pizza.toppings) }}</td>
          <td class="px-6 py-4">{{ pizza.category ? pizza.category.name : $t('pizza.no_category') }}</td>
          <td class="px-6 py-4 space-x-2">
            <form :action="`/cart/add/pizza/${pizza.id}`" method="POST" class="inline">
            <div class="flex space-x-2">
                <div class="flex space-x-1">
                  <button v-if="pizza.priceSmall" type="submit" name="size" value="small"
                          class="bg-blue-500 text-white px-3 py-1 rounded-md hover:bg-blue-700">
                    S
                  </button>
                  <button type="submit" name="size" value="medium"
                          class="bg-blue-500 text-white px-3 py-1 rounded-md hover:bg-blue-700">
                    M
                  </button>
                  <button v-if="pizza.priceLarge" type="submit" name="size" value="large"
                          class="bg-blue-500 text-white px-3 py-1 rounded-md hover:bg-blue-700">
                    L
                  </button>
                </div>
              </div>
            </form>

            <template v-if="isAdmin">
              |
              <a :href="`/pizza/${pizza.id}`" class="text-blue-500 hover:underline">{{ $t('show') }}</a>
              |
              <a :href="`/pizza/${pizza.id}/edit`" class="text-green-500 hover:underline">{{ $t('edit') }}</a>
              |
              <form :action="'/pizza/' + pizza.id" method="POST" class="inline" @submit.prevent="confirmDelete">
                <input type="hidden" name="_token" :value="csrfToken('delete' + pizza.id)">
                <button type="submit" class="text-red-500 hover:underline">{{ $t('delete') }}</button>
              </form>
            </template>
          </td>
        </tr>
        </tbody>
      </table>
    </div>

    <!-- Additions section -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
      <h2 class="text-3xl font-bold ml-3 mt-1">{{ $t('Additions') }}</h2>
      <table class="w-full text-left border-collapse">
        <thead>
        <tr>
          <th class="px-6 py-3">{{ $t('addition.name') }}</th>
          <th class="px-6 py-3">{{ $t('addition.price') }}</th>
          <th class="px-6 py-3">{{ $t('addition.action') }}</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="addition in additions" :key="addition.id" class="border-b hover:bg-gray-100">
          <td class="px-6 py-4">{{ addition.name }}</td>
          <td class="px-6 py-4">
            <div v-if="addition.coupon" class="flex flex-col">
              <span class="text-gray-500 line-through">{{ addition.price }} {{ currency }}</span>
              <span v-if="addition.coupon.type === 'fixed'" class="text-red-600 font-semibold">
                {{ addition.price - addition.coupon.discount }} {{ currency }}
              </span>
              <span v-else class="text-red-600 font-semibold">
                {{ addition.price * (1 - addition.coupon.discount / 100) }} {{ currency }}
              </span>
            </div>
            <template v-else>
              {{ formatPrice(addition.price) }}
            </template>
          </td>
          <td class="px-6 py-4 space-x-2">
            <form :action="`/cart/add-addition/${addition.id}`" method="POST" class="inline">
              <label>
                <input type="number" name="quantity" value="1" min="1" class="w-12 border rounded-md px-2 text-center" />
              </label>
              <button type="submit" class="bg-green-500 text-white px-2 py-1 rounded-md hover:bg-green-700">
                {{ $t('add') }}
              </button>
            </form>

            <template v-if="isAdmin">
              |
              <a :href="`/addition/${addition.id}`" class="text-blue-500 hover:underline">{{ $t('show') }}</a>
              |
              <a :href="`/addition/${addition.id}/edit`" class="text-green-500 hover:underline">{{ $t('edit') }}</a>
              |
              <form :action="`/addition/${addition.id}/delete`" method="POST" class="inline">
                <input type="hidden" name="_method" value="DELETE">
                <input type="hidden" name="_token" :value="csrf_token">
                <button type="submit" class="text-red-700 hover:underline">{{ $t('delete') }}</button>
              </form>
            </template>
          </td>
        </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
  initialPizzas: {
    type: Array,
    required: true
  },
  initialAdditions: {
    type: Array,
    required: true
  },
  userRoles: {
    type: Array,
    default: () => []
  },
  currencySymbol: {
    type: String,
    default: ''
  },
  csrfToken: {
    type: String,
    default: ''
  }
});

const pizzas = ref(props.initialPizzas);
const additions = ref(props.initialAdditions);
const createPizzaPath = ref('/pizza/create');
const createAdditionPath = ref('/addition/create');
const settingsPath = ref('/admin/settings');
const paymentHistoryPath = ref('/payment/history');
const csrf_token = ref(props.csrfToken);

const isAdmin = computed(() => {
  return props.userRoles.includes('ROLE_ADMIN');
});

function formatPrice(price) {
  return `${price} ${props.currencySymbol}`;
}

function formatToppings(toppings) {
  if (!toppings) {
    return $t('pizza.no_toppings');
  }

  if (Array.isArray(toppings)) {
    return toppings.join(', ');
  }

  if (typeof toppings === 'string') {
    return toppings;
  }

  return $t('pizza.no_toppings');
}

function confirmDelete(event) {
  if (confirm($t('pizza.confirm_delete'))) {
    event.target.submit();
  }
}

function csrfToken(id) {
  // This should be replaced with how you're actually getting CSRF tokens
  // Typically they're passed from the server to your Vue component
  return csrf_tokens?.[id] || '';
}

onMounted(() => {
  console.log('Pizza data:', pizzas.value);
});
</script>