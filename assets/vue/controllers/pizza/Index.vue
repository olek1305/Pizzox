<template>
  <div class="flex flex-wrap">
    <div class="w-full lg:w-[80%]">
      <!-- Admin buttons section -->
      <div class="flex justify-center items-center mb-4 mt-4" v-if="isAdmin">
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
      <div v-if="pizzas.length === 0" class="bg-white shadow-md text-center text-gray-600 p-12">
        <h1 class="text-3xl font-bold">{{ $t('pizza.list_title') }}</h1>
        {{ $t('pizza.no_pizzas') }}
      </div>
      <div v-else class="bg-white shadow-md overflow-hidden p-8">
        <h1 class="text-3xl font-bold mb-6 text-center">{{ $t('pizza.list_title') }}</h1>

        <!-- Display by category for pizzas -->
        <div v-for="category in pizzaCategories" :key="category.id" class="mb-8">
          <h2 class="text-xl font-semibold mb-4 pb-2 border-b-2 border-gray-200">{{ category.name }}</h2>

          <div class="grid grid-cols-1 gap-6">
            <!-- List of pizzas in this category -->
            <div v-for="pizza in getPizzasByCategory(category.id)" :key="pizza.id"
                 class="flex flex-col md:flex-row justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50">

              <div class="flex-1">
                <div class="flex justify-between mb-1">
                  <h3 class="text-lg font-semibold">{{ pizza.name }}</h3>
                  <div class="text-right font-medium">
                    <div v-if="pizza.coupon" class="flex flex-col">
                      <span class="text-gray-500">{{ pizza.price }} {{ $currency }}</span>
                      <span v-if="pizza.coupon.type === 'fixed'" class="text-red-600 font-semibold">
                        {{ pizza.price - pizza.coupon.discount }} {{ $currency }}
                      </span>
                      <span v-else class="text-red-600 font-semibold">
                        {{ pizza.price * (1 - pizza.coupon.discount / 100) }} {{ $currency }}
                      </span>
                    </div>
                    <template v-else>
                      <span>od {{ formatPrice(getLowestPrice(pizza)) }} {{ $currency }}</span>
                    </template>
                  </div>
                </div>

                <p class="text-sm text-gray-600 mb-3">{{ formatToppings(pizza.toppings) }}</p>
              </div>

              <div class="flex items-center mt-2 md:mt-0">
                <form :action="`/cart/add/pizza/${pizza.id}`" method="POST" class="flex space-x-2">
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
                </form>

                <div v-if="isAdmin" class="ml-4 flex space-x-2">
                  <a :href="`/pizza/${pizza.id}/edit`" class="text-green-500 hover:underline">{{ $t('action.edit') }}</a>
                  <form :action="`/pizza/${pizza.id}/delete`" method="POST" class="inline"
                        @submit="onDelete($event, pizza.name)">
                    <button type="submit" class="text-red-500 hover:underline">{{ $t('action.delete') }}</button>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Additions section -->
        <h1 class="text-3xl font-bold mb-6 text-center">{{ $t('Additions') }}</h1>

        <div v-if="!additions || additions.length === 0" class="text-center text-gray-600">
          {{ $t('addition.no_additions') }}
        </div>

        <div v-else>
          <!-- Display by category -->
          <div v-for="category in additionsCategories" :key="category.id" class="mb-8">
            <h2 class="text-xl font-semibold mb-4 pb-2 border-b-2 border-gray-200">{{ category.name }}</h2>

            <div class="grid grid-cols-1 gap-6">
              <!-- List of additions in this category -->
              <div v-for="addition in getAdditionsByCategory(category.id)" :key="addition.id"
                   class="flex flex-col md:flex-row justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50">
                <div class="flex-1">
                  <div class="flex justify-between mb-1">
                    <h3 class="text-lg font-semibold">{{ addition.name }}</h3>
                    <div class="text-right font-medium">
                      <div v-if="addition.coupon" class="flex flex-col">
                        <span class="text-gray-500">{{ addition.price }} {{ $currency }}</span>
                        <span v-if="addition.coupon.type === 'fixed'" class="text-red-600 font-semibold">
                            {{ addition.price - addition.coupon.discount }} {{ $currency }}
                          </span>
                        <span v-else class="text-red-600 font-semibold">
                            {{ addition.price * (1 - addition.coupon.discount / 100) }} {{ $currency }}
                          </span>
                      </div>
                      <template v-else>
                        <span>od {{ formatPrice(getLowestPrice(addition)) }} {{ $currency }}</span>
                      </template>
                    </div>
                  </div>
                </div>

                <div class="flex items-center mt-2 md:mt-0">
                  <form :action="`/cart/add/addition/${addition.id}`" method="POST" class="flex space-x-2">
                    <label class="mr-2">
                      <input type="number" name="quantity" value="1" min="1" class="w-12 border rounded-md px-2 text-center" />
                    </label>
                    <button type="submit" class="bg-green-500 text-white px-3 py-1 rounded-md hover:bg-green-700">
                      {{ $t('add') }}
                    </button>
                  </form>

                  <div v-if="isAdmin" class="ml-4 flex space-x-2">
                    <a :href="`/addition/${addition.id}/edit`" class="text-blue-500 hover:text-blue-700">
                      {{ $t('action.edit') }}
                    </a>
                    <a :href="`/addition/${addition.id}/delete`" class="text-red-500 hover:text-red-700">
                      {{ $t('action.delete') }}
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- Cart section -->
    <Cart />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import Cart from "../components/Cart.vue";

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
    required: true
  }
});

const pizzas = ref([...props.initialPizzas]);
const additions = ref([...props.initialAdditions]);
const createPizzaPath = ref('/pizza/create');
const createAdditionPath = ref('/addition/create');
const settingsPath = ref('/admin/settings');
const paymentHistoryPath = ref('/payment/history');

const isAdmin = computed(() => {
  return props.userRoles.includes('ROLE_ADMIN');
});

const pizzaCategories = computed(() => {
  const categories = [];
  const categoryIds = new Set();

  pizzas.value.forEach(pizza => {
    if (pizza.category && !categoryIds.has(pizza.category.id)) {
      categoryIds.add(pizza.category.id);
      categories.push(pizza.category);
    }
  });

  return categories;
});

const additionsCategories = computed(() => {
  const categories = [];
  const categoryIds = new Set();

  additions.value.forEach(addition => {
    if (addition.category && !categoryIds.has(addition.category.id)) {
      categoryIds.add(addition.category.id);
      categories.push(addition.category);
    }
  });

  return categories;
})

const formatToppings = (toppings) => {
  if (!toppings || toppings.length === 0) {
    return $t('pizza.no_toppings');
  }

  if (Array.isArray(toppings)) {
    return toppings.join(', ');
  }
};

const formatPrice = (price) => {
  return Number(price).toFixed(2);
};

const getPizzasByCategory = (categoryId) => {
  return pizzas.value.filter(pizza => pizza.category && pizza.category.id === categoryId);
};

const getAdditionsByCategory = (categoryId) => {
  return additions.value.filter(addition => addition.category && addition.category.id === categoryId);
}

const getLowestPrice = (pizza) => {
  const prices = [];
  if (pizza.priceSmall) prices.push(parseFloat(pizza.priceSmall));
  if (pizza.price) prices.push(parseFloat(pizza.price));
  if (pizza.priceLarge) prices.push(parseFloat(pizza.priceLarge));

  return prices.length > 0 ? Math.min(...prices) : 0;
};

const onDelete = (event, pizzaName) => {
  if (!confirm($t('pizza.confirm_delete', {name: pizzaName}))) {
    event.preventDefault();
  }
};
</script>