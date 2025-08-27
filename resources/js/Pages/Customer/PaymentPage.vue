<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { defineProps } from 'vue'
import axios from 'axios'
import { Head, router } from '@inertiajs/vue3'


const props = defineProps({
  auth: Object,
  order: Object,
})

const payWithChapa = () => {
  router.post(route('payments.initialize'), { order_id: props.order.id }, {
    onSuccess: (page) => {
      if (page.props.checkout_url) {
        window.location.href = page.props.checkout_url
      }
    },
    onError: (errors) => {
      console.error('Chapa init failed', errors)
      alert('Could not start payment. Please try again.')
    }
  })
}

</script>


<template>
  <AuthenticatedLayout :auth="auth">
    <Head title="Payment" />

    <div class="py-12 bg-gray-50 min-h-screen">
      <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded-xl p-6">
          <h1 class="text-2xl font-bold text-gray-800 mb-2">Complete Payment</h1>
          
          <div class="mb-6">
            <h2 class="text-lg font-semibold mb-3">Order Summary</h2>
            <div class="space-y-2">
              <div class="flex justify-between">
                <span>Order ID:</span>
                <span class="font-medium">#{{ order.id }}</span>
              </div>
              <div class="flex justify-between">
                <span>Total Amount:</span>
                <span class="font-medium text-orange-600">{{ order.total }} ETB</span>
              </div>
              <div class="flex justify-between">
                <span>Status:</span>
                <span class="font-medium text-yellow-600">{{ order.status }}</span>
              </div>
            </div>
          </div>

          <div class="mt-8">
            <button 
              @click="payWithChapa"
              class="w-full bg-gradient-to-r from-orange-500 to-amber-500 text-white font-semibold py-3 rounded-lg hover:from-orange-600 hover:to-amber-600 transition"
            >
              Pay with Chapa
            </button>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
