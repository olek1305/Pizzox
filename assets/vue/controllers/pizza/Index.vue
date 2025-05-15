<template>
<div class="flex flex-wrap">
  <div class="w-full lg:w-[80%]">
    <AdminButton :is-admin="isAdmin"/>

    <FiltersPanel
        :pizza-categories="pizzaCategories"
        :filter="filter"
    />

    <PizzaList
        :pizzas="pizzas"
        :pizza-categories="pizzaCategories"
        :visible-pizza-categories="visiblePizzaCategories"
        :is-admin="isAdmin"
        :get-pizzas-by-category="getPizzasByCategory"
        :has-visible-pizzas="hasVisiblePizzas"
        :format-price="formatPrice"
        :get-lowest-price="getLowestPrice"
        @openModal="openPizzaModal"
        @delete="onDelete"
    />

    <AdditionList
        :additions="additions"
        :additions-categories="additionsCategories"
        :visible-additions-categories="visibleAdditionsCategories"
        :is-admin="isAdmin"
        :get-additions-by-category="getAdditionsByCategory"
        :has-visible-additions="hasVisibleAdditions"
        :format-price="formatPrice"
        @openModal="openAdditionModal"
        @delete="onDeleteAddition"
    />

    <Cart ref="cartComponent"/>

    <ItemModal
        :show="showModal"
        :item="selectedItem"
        :item-type="selectedItemType"
        @close="closeModal"
        @item-added="refreshCart"
    />

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
import {ref, computed } from 'vue';

import Cart from "./components/Cart.vue";
import ItemModal from "./components/ItemModal.vue";
import Footer from "../components/Footer.vue";
import PizzaList from "./components/PizzaList.vue";
import AdditionList from "./components/AdditionList.vue";
import FiltersPanel from "./components/FiltersPanel.vue";
import AdminButton from "./components/AdminButton.vue";

const props = defineProps({
  initialPizzas: {
    type: Array,
    required: true
  },
  initialAdditions: {
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
const isAdmin = computed(() => window.isAdmin === true);

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

const cartComponent = ref(null);

// Function to refresh the cart when an item is added
const refreshCart = () => {
  if (cartComponent.value) {
    cartComponent.value.loadCartData();
  }
};

// Function to handle cart interactions - no longer needed since we're using
// addToCartWithQuantity directly in ItemModal.vue

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
const visiblePizzaCategories = computed(() =>
    pizzaCategories.value.filter(cat => getPizzasByCategory(cat.id).length > 0)
);

const visibleAdditionsCategories = computed(() =>
    additionsCategories.value.filter(cat => getAdditionsByCategory(cat.id).length > 0)
);


function useFiltered(items) {
  return computed(() =>
      items.value.filter(item => {
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

const filteredPizzas = useFiltered(pizzas);
const filteredAdditions = useFiltered(additions);
/* End filter section */
</script>