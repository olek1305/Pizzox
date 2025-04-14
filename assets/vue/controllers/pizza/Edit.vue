<template>
  <div>
    <div class="flex justify-center items-center mb-4">
      <div class="flex space-x-3">
        <a :href="`/pizza`" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-700">
          {{ $t('action.back_to_list') }}
        </a>
      </div>
    </div>

    <div class="bg-white shadow-md rounded-lg p-6">
      <h1 class="text-3xl font-bold mb-6">{{ pizza.name }}</h1>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="p-3 bg-gray-50 rounded-lg">
          <p class="font-normal mb-2">{{ $t('pizza.price_medium') }} <strong>({{ $t('as_the_default') }})</strong></p>
          <p>{{ pizza.price }} {{ $currency }}</p>
        </div>
        
        <div class="p-3 bg-gray-50 rounded-lg">
          <p class="font-normal mb-2">{{ $t('pizza.price_small') }}</p>
          <p>{{ pizza.priceSmall ? `${pizza.priceSmall} ${$currency}` : $t('pizza.calculated_from_medium') }}</p>
        </div>
        
        <div class="p-3 bg-gray-50 rounded-lg">
          <p class="font-normal mb-2">{{ $t('pizza.price_large') }}</p>
          <p>{{ pizza.priceLarge ? `${pizza.priceLarge} ${$currency}` : $t('pizza.calculated_from_medium') }}</p>
        </div>

        <div class="p-3 bg-gray-50 rounded-lg">
          <p class="font-normal mb-2">{{ $t('pizza.category') }}</p>
          <p>{{ pizza.category ? pizza.category.name : $t('pizza.no_category') }}</p>
        </div>

        <div class="p-3 bg-gray-50 rounded-lg">
          <p class="font-normal mb-2">{{ $t('pizza.toppings') }}</p>
          <template v-if="pizza.toppings && pizza.toppings.length > 0">
            <ul class="list-disc pl-6">
              <li v-for="(topping, index) in pizza.toppings" :key="index">{{ topping }}</li>
            </ul>
          </template>
          <p v-else>{{ $t('pizza.no_toppings') }}</p>
        </div>
      </div>

      <div v-if="isAdmin" class="mt-8">
        <h3 class="text-2xl font-normal mb-4">{{ $t('promotion.create_title') }}</h3>
        <form :action="`/promotion/create/pizza/${pizza.id}`" method="POST" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <div class="font-normal mb-2">
                <label for="usage_limit">{{ $t('promotion.usage_limit') }}</label>
              </div>
              <input type="number" id="usage_limit" name="usage_limit" required min="1"
                     class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
              <div class="font-normal mb-2">
                <label for="discount_value">{{ $t('promotion.discount_value') }}</label>
              </div>
              <input type="number" id="discount_value" name="discount_value" required step="0.01" min="0"
                     class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
              <div class="font-normal mb-2">
                <label for="discount_type">{{ $t('promotion.discount_type') }}</label>
              </div>
              <select id="discount_type" name="discount_type" required
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="percentage">{{ $t('promotion.percentage') }}</option>
                <option value="fixed">{{ $t('promotion.fixed_amount') }}</option>
              </select>
            </div>

            <div>
              <div class="font-normal mb-2">
                <label>{{ $t('promotion.calendar_to_expire') }}</label>
              </div>
              <input type="date" id="expiry_date"
                     onfocus="this.showPicker()"
                     class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                     @change="calculateDays">
              <input type="hidden" id="expiry_days" name="expiry_days" :value="daysCount">
            </div>

            <!-- Debug info -->
            <div class="mt-2 text-sm text-gray-600">
              <p>{{ $t('promotion.selected_date') }}: {{ selectedDateFormatted }}</p>
              <p>{{ $t('promotion.days_count') }}: {{ daysCount }}</p>
            </div>

          </div>

          <div class="flex justify-end">
            <button type="submit"
                    class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-700">
              {{ $t('promotion.create') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  pizza: {
    type: Object,
    required: true
  },
  userRoles: {
    type: Array,
    default: () => []
  }
});

const isAdmin = computed(() => props.userRoles.includes('ROLE_ADMIN'));

// Calendar
const daysCount = ref(7);
const selectedDate = ref(null);

const selectedDateFormatted = computed(() => {
  if (!selectedDate.value) return 'No selected date';
  return selectedDate.value.toISOString().split('T')[0];
});

const calculateDays = (event) => {
  const date = new Date(event.target.value);
  selectedDate.value = date;

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const diffTime = Math.abs(date - today);
  daysCount.value = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
};
</script>