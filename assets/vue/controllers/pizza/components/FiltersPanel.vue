<template>
  <div class="mb-4 px-6 py-4 bg-white rounded shadow flex flex-wrap gap-4 justify-center items-center">
    <select v-model="filter.category" class="border bg-white p-2 rounded">
      <option :value="null">{{ $t('all_categories') }}</option>
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
        :placeholder="$t('price_from')"
        class="border p-2 rounded w-24 focus:ring-2 focus:ring-blue-400 transition-shadow duration-300"
    />
    <input
        v-model.number="filter.priceMax"
        type="number"
        min="1"
        :placeholder="$t('price_to')"
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
</template>

<script setup>
const props = defineProps({
  pizzaCategories: Array,
  filter: Object
});
</script>