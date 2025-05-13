<template>
<div class="flex flex-wrap">
  <div class="w-full lg:w-[80%]">
    <AdminButton :is-admin="isAdmin"/>

    <FiltersPanel
        :pizza-categories="pizzaCategories"
        :filter="filter"
        :is-loading="isLoading"
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
        :is-loading="isLoading"
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
        :is-loading="isLoading"
        @openModal="openAdditionModal"
        @delete="onDeleteAddition"
    />

    <Cart/>

    <SizeModal
        :show="showModal"
        :item="selectedItem"
        :item-type="selectedItemType"
        @close="closeModal"
        @add-to-cart="handleAddToCart"
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
import {ref, computed, watch} from 'vue';
import Cart from "../components/Cart.vue";
import SizeModal from "../components/SizeModal.vue";
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
const isLoading = ref(false);

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
/* End filter section */

let filterTimeout = null;
function handleFilterChange() {
  isLoading.value = true;

  if (filterTimeout) {
    clearTimeout(filterTimeout);
  }

  filterTimeout = setTimeout(() => {
    isLoading.value = false;
  }, 500);
}

watch(filter, () => {
  handleFilterChange();
}, { deep: true });


</script>