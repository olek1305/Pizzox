<template>
  <div class="flex flex-wrap">
    <div class="w-full lg:w-[80%]">
      <!-- Admin buttons section -->
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

      <div class="mb-4 px-6 py-4 bg-white rounded shadow flex flex-wrap gap-4 justify-center items-center">
        <select v-model="filter.category" class="border bg-white p-2 rounded">
          <option :value="null">Wszystkie kategorie</option>
          <option
              v-for="cat in pizzaCategories"
              :key="cat.id"
              :value="cat.id"
          >
            {{ cat.name }}
          </option>
        </select>
        <input
            v-model.number="filter.priceMin"
            type="number"
            min="1"
            placeholder="Cena od"
            class="border p-2 rounded w-24 focus:ring-2 focus:ring-blue-400 transition-shadow duration-300"
        />
        <input
            v-model.number="filter.priceMax"
            type="number"
            min="1"
            placeholder="Cena do"
            class="border p-2 rounded w-24"
        />
        <label class="inline-flex items-center">
          <input
              v-model="filter.promotionOnly"
              type="checkbox"
              class="mr-2 accent-blue-500 h-5 w-5 transition-transform duration-200 scale-90 checked:scale-125"
          />
          <span class="font-medium text-gray-700 transition-colors duration-200 select-none">
            {{ $t('only_promotion') }}
          </span>
        </label>
      </div>

      <!-- Pizza list section -->

      <div v-if="pizzas.length === 0" class="bg-white shadow-md text-center text-gray-600 p-12">
        <h1 v-if="hasVisiblePizzas" class="text-3xl font-bold mb-6 text-center">{{ $t('pizza.label') }}</h1>
        {{ $t('pizza.no_pizzas') }}
      </div>
      <div v-else class="bg-white shadow-md overflow-hidden p-8">
        <h1 v-if="hasVisiblePizzas" class="text-3xl font-bold mb-6 text-center">{{ $t('pizza.label') }}</h1>
        <!-- Display by category for pizzas -->
        <div v-for="category in visiblePizzaCategories" :key="category.id" class="mb-8">
          <h2 class="text-xl font-semibold mb-4 pb-2 border-b-2 border-gray-200">{{ category.name }}</h2>

          <div class="grid grid-cols-1 gap-6">
            <!-- List of pizzas in this category -->
            <div v-for="pizza in getPizzasByCategory(category.id)" :key="pizza.id"
                 class="list-item-animate relative p-4 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer"
                 @click="openPizzaModal(pizza)">

              <div class="flex flex-col md:flex-row justify-between">
                <div class="flex-1">
                  <h3 class="text-lg font-semibold mb-1">{{ pizza.name }}</h3>
                  <p class="text-sm text-gray-600 mb-2">
                    {{ pizza.toppings && pizza.toppings.length > 0 ? pizza.toppings.join(', ') : $t('pizza.no_toppings') }}
                  </p>

                  <!-- Price display -->
                  <div class="font-medium mb-3">
                    <div v-if="pizza.coupon">
                      <span class="text-gray-500 line-through mr-2">{{ formatPrice(pizza.price) }}
                          {{ $currency }}
                      </span>
                      <span v-if="pizza.coupon.type === 'fixed'" class="text-red-600 font-semibold">
                        {{ formatPrice(pizza.price - pizza.coupon.discount) }} {{ $currency }}
                      </span>
                      <span v-else class="text-red-600 font-semibold">
                        {{ formatPrice(pizza.price * (1 - pizza.coupon.discount / 100)) }} {{ $currency }}
                      </span>
                    </div>
                    <span v-else>od {{ formatPrice(getLowestPrice(pizza)) }} {{ $currency }}</span>
                  </div>
                </div>

                <!-- Admin controls -->
                <div v-if="isAdmin" class="flex items-center mt-2 md:mt-0 md:ml-4" @click.stop>
                  <a :href="`/pizza/${pizza.id}/edit`" class="text-blue-500 hover:text-blue-700 hover:underline mr-3">
                    {{ $t('action.edit') }}
                  </a>
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
        <h1 v-if="hasVisibleAdditions" class="text-3xl font-bold mb-6 text-center">
          {{ $t('addition.label') }}
        </h1>
        <div v-if="!additions || additions.length === 0" class="text-center text-gray-600">
          {{ $t('addition.no_additions') }}
        </div>

        <div v-else>
          <!-- Display by category -->
          <div v-for="category in visibleAdditionsCategories" :key="category.id" class="mb-8">
            <h2 class="text-xl font-semibold mb-4 pb-2 border-b-2 border-gray-200">{{ category.name }}</h2>

            <div class="grid grid-cols-1 gap-6">
              <!-- List of additions in this category -->
              <div v-for="addition in getAdditionsByCategory(category.id)" :key="addition.id"
                   class="list-item-animate relative p-4 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer"
                   @click="openAdditionModal(addition)">
                <div class="flex flex-col md:flex-row justify-between">
                  <div class="flex-1">
                    <h3 class="text-lg font-semibold mb-1">{{ addition.name }}</h3>

                    <!-- Price display -->
                    <div class="font-medium mb-3">
                      <div v-if="addition.coupon">
                        <span class="text-gray-500 line-through mr-2">{{ formatPrice(addition.price) }} {{
                            $currency
                          }}</span>
                        <span v-if="addition.coupon.type === 'fixed'" class="text-red-600 font-semibold">
                          {{ formatPrice(addition.price - addition.coupon.discount) }} {{ $currency }}
                        </span>
                        <span v-else class="text-red-600 font-semibold">
                          {{ formatPrice(addition.price * (1 - addition.coupon.discount / 100)) }} {{ $currency }}
                        </span>
                      </div>
                      <span v-else>{{ formatPrice(addition.price) }} {{ $currency }}</span>
                    </div>
                  </div>

                  <!-- Admin controls -->
                  <div v-if="isAdmin" class="flex items-center mt-2 md:mt-0 md:ml-4" @click.stop>
                    <a :href="`/addition/${addition.id}/edit`" class="text-blue-500 hover:text-blue-700 mr-3">
                      {{ $t('action.edit') }}
                    </a>
                    <form :action="`/addition/${addition.id}/delete`" method="POST" class="inline"
                          @submit="onDeleteAddition($event, addition.name)">
                      <button type="submit" class="text-red-500 hover:underline">{{ $t('action.delete') }}</button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Cart section -->
    <Cart/>

    <!-- Size selection modal -->
    <SizeModal
        :show="showModal"
        :item="selectedItem"
        :item-type="selectedItemType"
        @close="closeModal"
        @add-to-cart="handleAddToCart"
    />

    <!-- Footer section -->
    <div class="w-full lg:w-[80%] text-center">
      <Footer
          :restaurant-name="footerSettings.restaurantName"
          :restaurant-address="footerSettings.restaurantAddress"
          :latitude="footerSettings.latitude"
          :longitude="footerSettings.longitude"
          :mapbox-token="footerSettings.mapboxToken"
      />
    </div>
  </div>
</template>

<script setup>
import {ref, computed} from 'vue';
import Cart from "../components/Cart.vue";
import SizeModal from "../components/SizeModal.vue";
import Footer from "../components/Footer.vue";

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
  },
  footerSettings: {
    type: Object,
    required: true
  }
});

const pizzas = ref([...props.initialPizzas]);
const additions = ref([...props.initialAdditions]);
const createPizzaPath = ref('/pizza/create');
const createAdditionPath = ref('/addition/create');
const settingsPath = ref('/admin/settings');
const paymentHistoryPath = ref('/admin/payment-history');

const filter = ref({
  category: null,
  promotionOnly: false,
  priceMin: null,
  priceMax: null,
});

// Modal state
const showModal = ref(false);
const selectedItem = ref(null);
const selectedItemType = ref('pizza');

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

const formatPrice = (price) => {
  return Number(price).toFixed(2);
};

function getPizzasByCategory(categoryId) {
  return filteredPizzas.value.filter(pizza => pizza.category?.id === categoryId);
}

function getAdditionsByCategory(categoryId) {
  return filteredAdditions.value.filter(addition => addition.category?.id === categoryId);
}

const hasVisiblePizzas = computed(() =>
    visiblePizzaCategories.value.length > 0
);

const hasVisibleAdditions = computed(() =>
    visibleAdditionsCategories.value.length > 0
);


const getLowestPrice = (pizza) => {
  const prices = [];
  if (pizza.priceSmall) prices.push(parseFloat(pizza.priceSmall));
  if (pizza.price) prices.push(parseFloat(pizza.price));
  if (pizza.priceLarge) prices.push(parseFloat(pizza.priceLarge));

  return prices.length > 0 ? Math.min(...prices) : 0;
};

// Modal functions
const openPizzaModal = (pizza) => {
  selectedItem.value = pizza;
  selectedItemType.value = 'pizza';
  showModal.value = true;
};

const openAdditionModal = (addition) => {
  selectedItem.value = addition;
  selectedItemType.value = 'addition';
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  selectedItem.value = null;
};

const handleAddToCart = (data) => {
  const {id, type, quantity, size, formData} = data;
  const url = type === 'pizza'
      ? `/cart/add/pizza/${id}`
      : `/cart/add/addition/${id}`;

  // Create a form element and submit it
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = url;

  // Always explicitly add quantity (default to 1 if missing)
  const quantityInput = document.createElement('input');
  quantityInput.type = 'hidden';
  quantityInput.name = 'quantity';
  quantityInput.value = String(quantity || 1);
  form.appendChild(quantityInput);

  // For pizza, add the size
  if (type === 'pizza' && size) {
    const sizeInput = document.createElement('input');
    sizeInput.type = 'hidden';
    sizeInput.name = 'size';
    sizeInput.value = String(size);
    form.appendChild(sizeInput);
  }

  // Append any additional form data
  for (const [key, value] of formData.entries()) {
    // Skip if we already added these inputs directly
    if ((key === 'quantity') || (key === 'size' && size)) {
      continue;
    }

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = key;
    input.value = value;
    form.appendChild(input);
  }

  document.body.appendChild(form);
  form.submit();

  // Close the modal
  closeModal();
};

const onDelete = (event, pizzaName) => {
  const confirmMessage = `Are you sure you want to delete ${pizzaName}?`;
  if (!confirm(confirmMessage)) {
    event.preventDefault();
  }
};

const onDeleteAddition = (event, additionName) => {
  const confirmMessage = `Are you sure you want to delete ${additionName}?`;
  if (!confirm(confirmMessage)) {
    event.preventDefault();
  }
};

/* Start filter section */
const filteredPizzas = useFiltered(pizzas);
const filteredAdditions = useFiltered(additions);

const visiblePizzaCategories = computed(() =>
    pizzaCategories.value.filter(cat => getPizzasByCategory(cat.id).length > 0)
);

const visibleAdditionsCategories = computed(() =>
    additionsCategories.value.filter(cat => getAdditionsByCategory(cat.id).length > 0)
);


function useFiltered(listRef) {
  return computed(() =>
      listRef.value.filter(item => {
        if (filter.value.category && item.category?.id !== filter.value.category)
          return false;
        if (filter.value.promotionOnly && !item.coupon)
          return false;
        // Price from
        if (filter.value.priceMin && getLowestPrice(item) < filter.value.priceMin)
          return false;
        // Price to
        return !(filter.value.priceMax && getLowestPrice(item) > filter.value.priceMax);

      })
  );
}
/* End filter section */
</script>