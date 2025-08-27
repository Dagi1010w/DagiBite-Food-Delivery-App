<template>
  <div v-if="restaurant" class="bg-white rounded-lg shadow-lg overflow-hidden transform transition-transform duration-300 hover:scale-105 cursor-pointer">
    <img :src="imageSrc" :alt="restaurant.name" class="w-full h-48 object-cover" />
    <div class="p-4">
      <h3 class="text-xl font-semibold text-gray-800 mb-2">{{ restaurant.name }}</h3>
      <p class="text-gray-600 text-sm">{{ restaurant.description }}</p>
    </div>
  </div>
  <div v-else class="p-4 text-red-600 font-bold">
    ⚠️ No restaurant data
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  restaurant: {
    type: Object,
    required: true,
  },
});

const imageSrc = computed(() => {
  if (props.restaurant.logo_path) {
    return props.restaurant.logo_path.startsWith('http') 
      ? props.restaurant.logo_path 
      : `/storage/${props.restaurant.logo_path}`;
  }
  if (props.restaurant.image) {
    return props.restaurant.image.startsWith('http') 
      ? props.restaurant.image 
      : `/storage/${props.restaurant.image}`;
  }
  return 'https://via.placeholder.com/400x300/FEE2E2/EA580C?text=Restaurant';
});
</script>
