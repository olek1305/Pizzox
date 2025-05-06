<template>
  <div class="container mx-auto px-4 py-8" v-if="order">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold">{{ $t('order.details') }}</h1>
      <a :href="paymentHistoryPath" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
        {{ $t('order.back_to_list') }}
      </a>
    </div>

    <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
      <!-- Customer Info -->
      <div class="p-6 border-b">
        <h2 class="text-xl font-semibold mb-4">{{ $t('order.customer_information') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="mb-4">
            <p class="text-gray-600">{{ $t('order.full_name') }}:</p>
            <p class="font-medium">{{ order.fullName }}</p>
          </div>
          <div class="mb-4">
            <p class="text-gray-600">{{ $t('order.email') }}:</p>
            <p class="font-medium">{{ order.email }}</p>
          </div>
          <div class="mb-4">
            <p class="text-gray-600">{{ $t('order.phone') }}:</p>
            <p class="font-medium">{{ order.phone }}</p>
          </div>
          <div class="mb-4">
            <p class="text-gray-600">{{ $t('order.address') }}:</p>
            <p class="font-medium">{{ order.address }}</p>
          </div>
        </div>
      </div>

      <!-- Order Info -->
      <div class="p-6 border-b">
        <h2 class="text-xl font-semibold mb-4">{{ $t('order.order_details') }}</h2>
        <div class="mb-4">
          <p class="text-gray-600">{{ $t('order.order_id') }}:</p>
          <p class="font-medium">{{ order.id }}</p>
        </div>
        <div class="mb-4">
          <p class="text-gray-600">{{ $t('order.order_date') }}:</p>
          <p class="font-medium">{{ formatDate(order.createdAt) }}</p>
        </div>
        <div class="mb-4">
          <p class="text-gray-600">{{ $t('order.status_label') }}:</p>
          <span class="px-3 py-1 rounded-full text-sm font-medium" :class="statusClass(order.status)">
            {{ order.statusLabel || order.status }}
          </span>
        </div>
      </div>

      <!-- Pizzas -->
      <div class="p-6 border-b">
        <h2 class="text-xl font-semibold mb-4">{{ $t('order.ordered_pizzas') }}</h2>
        <div v-if="order.pizzas?.length === 0" class="text-gray-600">
          {{ $t('order.no_pizzas_found') }}
        </div>
        <table v-else class="min-w-full">
          <thead>
          <tr class="bg-gray-100">
            <th class="px-4 py-2 text-left">{{ $t('order.name') }}</th>
            <th class="px-4 py-2 text-left">{{ $t('order.size') }}</th>
            <th class="px-4 py-2 text-left">{{ $t('order.toppings') }}</th>
            <th class="px-4 py-2 text-right">{{ $t('order.price') }}</th>
            <th class="px-4 py-2 text-center">{{ $t('order.quantity') }}</th>
            <th class="px-4 py-2 text-right">{{ $t('order.total') }}</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="(pizza, i) in order.pizzas" :key="i" class="border-t">
            <td class="px-4 py-2 font-medium">{{ pizza.name }}</td>
            <td class="px-4 py-2">{{ pizza.size }}</td>
            <td class="px-4 py-2">{{ pizza.toppings?.join(', ') }}</td>
            <td class="px-4 py-2 text-right">{{ formatPrice(pizza.price) }}</td>
            <td class="px-4 py-2 text-center">{{ pizza.quantity }}</td>
            <td class="px-4 py-2 text-right">{{ formatPrice(pizza.price * pizza.quantity) }}</td>
          </tr>
          </tbody>
        </table>
      </div>

      <!-- Additions -->
      <div class="p-6 border-b">
        <h2 class="text-xl font-semibold mb-4">{{ $t('order.extras') }}</h2>
        <div v-if="order.additions?.length === 0" class="text-gray-600">
          {{ $t('order.no_extras_found') }}
        </div>
        <table v-else class="min-w-full">
          <thead>
          <tr class="bg-gray-100">
            <th class="px-4 py-2 text-left">{{ $t('order.name') }}</th>
            <th class="px-4 py-2 text-right">{{ $t('order.price') }}</th>
            <th class="px-4 py-2 text-center">{{ $t('order.quantity') }}</th>
            <th class="px-4 py-2 text-right">{{ $t('order.total') }}</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="(addition, i) in order.additions" :key="i" class="border-t">
            <td class="px-4 py-2 font-medium">{{ addition.name }}</td>
            <td class="px-4 py-2 text-right">{{ formatPrice(addition.price) }}</td>
            <td class="px-4 py-2 text-center">{{ addition.quantity }}</td>
            <td class="px-4 py-2 text-right">{{ formatPrice(addition.price * addition.quantity) }}</td>
          </tr>
          </tbody>
        </table>
      </div>

      <!-- Total -->
      <div class="p-6">
        <div class="text-xl font-bold text-right">
          <span>{{ $t('order.total_price') }}: {{ formatPrice(order.totalPrice) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>


<script setup>
const props = defineProps({
  order: {
    type: Object,
    required: true
  }
})

const paymentHistoryPath = '/admin/payment-history'

const formatDate = (isoDate) => {
  const date = new Date(isoDate)
  return date.toLocaleString('pl-PL', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatPrice = (price) =>
    new Intl.NumberFormat('pl-PL', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(price) + ' zł'

const statusClass = (status) => {
  switch (status) {
    case 'pending': return 'bg-yellow-100 text-yellow-800'
    case 'completed': return 'bg-green-100 text-green-800'
    case 'cancelled': return 'bg-red-100 text-red-800'
    default: return 'bg-gray-100 text-gray-800'
  }
}
</script>
