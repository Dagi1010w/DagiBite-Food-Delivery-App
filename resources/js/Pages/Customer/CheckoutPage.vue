<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  auth: Object,
})

// Mock cart data (replace with real cart from store)
const cart = ref([
  { id: 1, name: 'Spicy Beef', price: 120, quantity: 1 },
  { id: 2, name: 'Injera', price: 40, quantity: 2 },
])

const total = cart.value.reduce((sum, item) => sum + item.price * item.quantity, 0)

const form = ref({
  address: '',
  phone: '',
  restaurant_id: 1, // Replace with real restaurant
})

const processing = ref(false)

const submit = () => {
  processing.value = true
  router.post('/customer/orders', {
    items: cart.value,
    total: total,
    ...form.value,
  }, {
    onError: (error) => {
      processing.value = false
      alert('Failed to create order. Try again.')
      console.error('Order creation error:', error)
    }
  })
}
</script>

<template>
  <AuthenticatedLayout :auth="auth">
    <Head title="Checkout" />

    <div class="py-12 bg-gray-50 min-h-screen">
      <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded-xl p-6">
          <h1 class="text-2xl font-bold text-gray-800 mb-6">Checkout</h1>

          <!-- Cart Summary -->
          <div class="mb-6">
            <h2 class="text-lg font-semibold mb-3">Your Order</h2>
            <ul class="space-y-2">
              <li v-for="item in cart" :key="item.id" class="flex justify-between text-sm">
                <span>{{ item.quantity }}x {{ item.name }}</span>
                <span>{{ (item.price * item.quantity).toFixed(2) }} ETB</span>
              </li>
            </ul>
            <div class="border-t pt-2 mt-4 font-bold">
              Total: <span class="text-orange-600">{{ total.toFixed(2) }} ETB</span>
            </div>
          </div>

          <!-- Delivery Info -->
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Delivery Address</label>
              <textarea
                v-model="form.address"
                required
                rows="3"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-200"
                placeholder="e.g., Bole, Addis Ababa, near X building"
              ></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Phone Number</label>
              <input
                v-model="form.phone"
                type="tel"
                required
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-200"
                placeholder="+251 9XX XXX XXX"
              />
            </div>
          </div>

          <!-- Submit -->
          <div class="mt-8">
            <button
              :disabled="processing"
              @click="submit"
              class="w-full bg-gradient-to-r from-orange-500 to-amber-500 text-white font-semibold py-3 rounded-lg hover:from-orange-600 hover:to-amber-600 transition disabled:opacity-70"
            >
              {{ processing ? 'Processing...' : 'Proceed to Payment' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>