<template>
  <div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">{{ $t('order.history') }}</h1>

    <div v-if="orders.length === 0">
      <p class="text-gray-600">{{ $t('order.no_orders') }}</p>
    </div>
    <div v-else class="overflow-x-auto">
      <table class="min-w-full bg-white rounded-lg overflow-hidden shadow-lg">
        <thead class="bg-gray-100">
        <tr>
          <th class="px-4 py-3 text-left">{{ $t('order.id') }}</th>
          <th class="px-4 py-3 text-left">{{ $t('order.client') }}</th>
          <th class="px-4 py-3 text-left">{{ $t('order.phone') }}</th>
          <th class="px-4 py-3 text-left">{{ $t('order.price') }}</th>
          <th class="px-4 py-3 text-left">{{ $t('order.status_label') }}</th>
          <th class="px-4 py-3 text-left">{{ $t('order.date') }}</th>
          <th class="px-4 py-3 text-left">{{ $t('order.action') }}</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="order in orders" :key="order.id" class="border-t hover:bg-gray-50">
          <td class="px-4 py-3 text-gray-600">{{ order.id }}</td>
          <td class="px-4 py-3 font-medium">{{ order.fullName }}</td>
          <td class="px-4 py-3">{{ order.phone }}</td>
          <td class="px-4 py-3">{{ formatPrice(order.totalPrice) }} zł</td>
          <td class="px-4 py-3">
              <span class="px-2 py-1 rounded-full text-xs font-medium" :class="statusClass(order.status)">
                {{ order.statusLabel }}
              </span>
          </td>
          <td class="px-4 py-3">{{ formatDate(order.createdAt) }}</td>
          <td class="px-4 py-3">
            <a :href="`/admin/payment-history/${order.id}`" class="text-blue-600 hover:text-blue-800 underline">
              {{ $t('order.details_link') }}
            </a>
          </td>
        </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const orders = ref([])
const page = ref(1)
const limit = 20

const fetchOrders = async () => {
  try {
    const response = await fetch(`/admin/api/payment-history?page=${page.value}&limit=${limit}`)
    if (!response.ok) throw new Error('Błąd pobierania danych')
    orders.value = await response.json()
  } catch (error) {
    console.error(error)
  }
}

onMounted(() => {
  fetchOrders()
  const interval = setInterval(fetchOrders, 10000)

  // Clear on unmount
  onUnmounted(() => clearInterval(interval))
})

const formatPrice = (price) =>
    new Intl.NumberFormat('pl-PL', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(price)

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

const statusClass = (status) => {
  switch (status) {
    case 'pending':
      return 'bg-yellow-100 text-yellow-800'
    case 'completed':
      return 'bg-green-100 text-green-800'
    case 'cancelled':
      return 'bg-red-100 text-red-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}
</script>
