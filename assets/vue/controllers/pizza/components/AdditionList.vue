<template>
  <div v-if="!additions || additions.length === 0" class="text-center text-gray-600">
    <h1 v-if="hasVisibleAdditions" class="text-3xl font-bold mb-6 text-center">{{ $t('addition.label') }}</h1>
    {{ $t('addition.no_additions') }}
  </div>

  <div v-else class="bg-white shadow-md overflow-hidden p-8 mt-2">
    <!-- Display by category -->
    <h1 v-if="hasVisibleAdditions" class="text-3xl font-bold mb-6 text-center">{{ $t('addition.label') }}</h1>
    <div v-for="category in visibleAdditionsCategories" :key="category.id" class="mb-8">
      <h2 class="text-xl font-semibold mb-4 pb-2 border-b-2 border-gray-200">{{ category.name }}</h2>

      <div class="grid grid-cols-1 gap-6">
        <!-- List of additions in this category -->
        <div v-for="addition in getAdditionsByCategory(category.id)" :key="addition.id"
             class="list-item-animate relative p-4 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer"
             @click="$emit('openModal', addition)">
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
                    @submit.prevent="$emit('delete', $event, addition.name)">
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
  additions: Array,
  additionsCategories: Array,
  visibleAdditionsCategories: Array,
  isAdmin: Boolean,
  getAdditionsByCategory: Function,
  hasVisibleAdditions: Boolean,
  formatPrice: Function
});

defineEmits(['openModal', 'delete']);
</script>
