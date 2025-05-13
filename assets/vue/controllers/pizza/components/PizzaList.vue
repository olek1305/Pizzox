<template>
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
             @click="$emit('openModal', pizza)">

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
                    @submit.prevent="$emit('delete', $event, pizza.name)">
                <button type="submit" class="text-red-500 hover:underline">{{ $t('action.delete') }}</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
const props = defineProps({
  pizzas: Array,
  pizzaCategories: Array,
  visiblePizzaCategories: Array,
  isAdmin: Boolean,
  getPizzasByCategory: Function,
  hasVisiblePizzas: Boolean,
  formatPrice: Function,
  getLowestPrice: Function
})

defineEmits(['openModal', 'delete']);
</script>
