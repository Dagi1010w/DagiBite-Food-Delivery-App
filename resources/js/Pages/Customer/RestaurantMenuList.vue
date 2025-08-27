<script setup>

import RestaurantCard from '@/Pages/Customer/Components/RestaurantCard.vue'
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'

const props = defineProps({
  auth: Object,
})

// -------------------- Data from database --------------------
const restaurants = ref([])
const menus = ref([])
const loading = ref(true)
const error = ref(null)

// -------------------- Restaurants controls --------------------
const searchQueryRestaurants = ref('')
const selectedCategory = ref('All')
const minRating = ref(0)

// -------------------- Menus controls --------------------
const queryMenus = ref('')
const selectedSort = ref('name_asc')

// Fetch data from database
onMounted(async () => {
  try {
    const [restaurantsResponse, menusResponse] = await Promise.all([
      axios.get('/api/restaurants'),
      axios.get('/api/menus')
    ])
    
    restaurants.value = restaurantsResponse.data.restaurants
    menus.value = menusResponse.data.menus
  } catch (err) {
    error.value = 'Failed to load data'
    console.error('Error loading data:', err)
  } finally {
    loading.value = false
  }
})

// Order functionality
const orderModalOpen = ref(false)
const selectedMenu = ref(null)
const orderQuantity = ref(1)
const orderNotes = ref('')

const openOrderModal = (menu) => {
  selectedMenu.value = menu
  orderQuantity.value = 1
  orderNotes.value = ''
  orderModalOpen.value = true
}

const closeOrderModal = () => {
  orderModalOpen.value = false
  selectedMenu.value = null
  orderQuantity.value = 1
  orderNotes.value = ''
}

const placeOrder = () => {
  if (!selectedMenu.value) return
  
  // Create order object
  const order = {
    menuId: selectedMenu.value.id,
    menuName: selectedMenu.value.name,
    quantity: orderQuantity.value,
    price: Number(selectedMenu.value.price) || 0,
    total: (Number(selectedMenu.value.price) || 0) * orderQuantity.value,
    notes: orderNotes.value,
    timestamp: new Date().toISOString()
  }
  
  // Store order in localStorage (for demo purposes)
  const existingOrders = JSON.parse(localStorage.getItem('customerOrders') || '[]')
  existingOrders.push(order)
  localStorage.setItem('customerOrders', JSON.stringify(existingOrders))
  
  // Show success message
  alert(`Order placed successfully!\n\n${order.quantity}x ${order.menuName}\nTotal: ${new Intl.NumberFormat('en-ET', {
    style: 'currency',
    currency: 'ETB',
    minimumFractionDigits: 2,
  }).format(order.total)}`)
  
  closeOrderModal()
}

const totalOrderPrice = computed(() => {
  if (!selectedMenu.value) return 0
  return (Number(selectedMenu.value.price) || 0) * orderQuantity.value
})

// Missing reactive properties
const totalCount = computed(() => filteredMenus.value.length)
const displayedCount = ref(12)
const itemsPerPage = 12

// Price formatter
const priceFormatter = new Intl.NumberFormat('en-ET', {
  style: 'currency',
  currency: 'ETB',
  minimumFractionDigits: 2,
})

// Image source function
const imgSrc = (menu) => {
  if (!menu || !menu.picture) {
    return 'https://via.placeholder.com/400x300/FEE2E2/EA580C?text=No+Image'
  }
  // Handle both relative and absolute paths
  if (menu.picture.startsWith('http')) {
    return menu.picture
  }
  return menu.picture.startsWith('/') ? menu.picture : `/storage/${menu.picture}`
}

// Cart navigation with menu item
const goToCart = (menu) => {
  if (!menu) return
  
  const cartItem = {
    menuId: menu.id,
    name: menu.name,
    price: Number(menu.price) || 0,
    quantity: 1,
    picture: menu.picture
  }
  
  // Check if item already exists in cart
  const existingItem = cartItems.value.find(item => item.menuId === menu.id)
  
  if (existingItem) {
    existingItem.quantity += 1
  } else {
    cartItems.value.push(cartItem)
  }
  
  saveCart()
  window.location.href = '/cart'
}

// Restaurant categories
const categories = computed(() => {
  const cats = (restaurants.value || []).map(r => r.category).filter(Boolean)
  return ['All', ...Array.from(new Set(cats))]
})

// Filtered restaurants
const filteredRestaurants = computed(() => {
  const list = restaurants.value || []
  return list.filter(r => {
    const name = (r.name || '').toLowerCase()
    const matchesSearch = name.includes(searchQueryRestaurants.value.toLowerCase())
    const matchesCategory = selectedCategory.value === 'All' || r.category === selectedCategory.value
    const rating = parseFloat(r.rating || 0)
    const matchesRating = rating >= minRating.value
    return matchesSearch && matchesCategory && matchesRating
  })
})

// Filtered menus
const filteredMenus = computed(() => {
  const q = queryMenus.value.trim().toLowerCase().replace(/\s+/g, ' ')
  let list = (menus.value || []).filter((m) => {
    const name = (m?.name ?? '').toLowerCase()
    const desc = (m?.description ?? '').toLowerCase()
    return q === '' || name.includes(q) || desc.includes(q)
  })

  switch (selectedSort.value) {
    case 'price_asc':
      list.sort((a, b) => (Number(a.price) || 0) - (Number(b.price) || 0))
      break
    case 'price_desc':
      list.sort((a, b) => (Number(b.price) || 0) - (Number(a.price) || 0))
      break
    case 'name_desc':
      list.sort((a, b) => (b.name || '').localeCompare(a.name || '', undefined, { sensitivity: 'base' }))
      break
    case 'name_asc':
    default:
      list.sort((a, b) => (a.name || '').localeCompare(b.name || '', undefined, { sensitivity: 'base' }))
      break
  }

  return list
})

function viewRestaurant(restaurant) {
  if (restaurant?.slug) {
    window.location.href = `/restaurants/${encodeURIComponent(restaurant.slug)}`
  } else if (restaurant?.id) {
    window.location.href = `/restaurants/${restaurant.id}`
  }
}

// -------- Cart state --------
const cartItems = ref([])
const loadCart = () => {
  try {
    cartItems.value = JSON.parse(localStorage.getItem('cart') || '[]')
  } catch (e) {
    cartItems.value = []
  }
}
const cartCount = computed(() => cartItems.value.reduce((sum, i) => sum + Number(i.quantity || 0), 0))
const getCartQuantity = (id) => {
  const item = cartItems.value.find(i => i.menuId === id)
  return item ? Number(item.quantity || 0) : 0
}
const saveCart = () => {
  localStorage.setItem('cart', JSON.stringify(cartItems.value))
  window.dispatchEvent(new CustomEvent('cart:updated', { detail: { count: cartCount.value } }))
}
const addToCart = (menu) => {
  if (!menu) return
  const id = menu.id
  const price = Number(menu.price) || 0
  const name = menu.name || ''
  const picture = menu.picture || ''
  const idx = cartItems.value.findIndex(i => i.menuId === id)
  if (idx >= 0) {
    cartItems.value[idx].quantity = Number(cartItems.value[idx].quantity || 0) + 1
  } else {
    cartItems.value.push({ menuId: id, name, price, quantity: 1, picture })
  }
  saveCart()
}
const onStorage = (e) => { if (!e || e.key === 'cart') loadCart() }
const onCartUpdated = () => loadCart()

onMounted(() => {
  loadCart()
  window.addEventListener('storage', onStorage)
  window.addEventListener('cart:updated', onCartUpdated)
})
onBeforeUnmount(() => {
  window.removeEventListener('storage', onStorage)
  window.removeEventListener('cart:updated', onCartUpdated)
})
</script>

<template>
  
    <div class="min-h-screen bg-gradient-to-b from-orange-50/40 via-white to-white">
      <div class="max-w-7xl mx-auto px-6 py-12">
        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center items-center h-64">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-500"></div>
          <span class="ml-3 text-gray-600">Loading restaurants and menus...</span>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="text-center py-12">
          <div class="text-red-500 text-xl mb-4">⚠️ {{ error }}</div>
          <button 
            @click="window.location.reload()" 
            class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:bg-orange-600 transition"
          >
            Try Again
          </button>
        </div>

        <!-- Content -->
        <div v-else>
          <!-- Restaurants Section -->
          <h2 class="text-3xl font-extrabold text-gray-900 mb-6">Featured Restaurants</h2>

          <!-- Restaurants Controls -->
          <div class="mb-8 max-w-4xl flex flex-col sm:flex-row sm:items-center sm:space-x-6 space-y-4 sm:space-y-0">
            <input
              v-model="searchQueryRestaurants"
              type="text"
              placeholder="Search restaurants..."
              class="flex-grow px-4 py-3 rounded-xl border border-orange-300 focus:outline-none focus:ring-2 focus:ring-orange-500 transition"
            />
            <select
              v-model="selectedCategory"
              class="px-4 py-3 rounded-xl border border-orange-300 focus:outline-none focus:ring-2 focus:ring-orange-500 transition"
            >
              <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
            </select>
            <select
              v-model.number="minRating"
              class="px-4 py-3 rounded-xl border border-orange-300 focus:outline-none focus:ring-2 focus:ring-orange-500 transition"
            >
              <option :value="0">All Ratings</option>
              <option :value="4">4 stars & up</option>
              <option :value="4.5">4.5 stars & up</option>
              <option :value="5">5 stars</option>
            </select>
          </div>

          <!-- Restaurants Grid -->
          <div v-if="filteredRestaurants.length === 0" class="text-center py-8 text-gray-500">
            No restaurants found matching your criteria.
          </div>
          <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8 mb-16">
            <div
              v-for="restaurant in filteredRestaurants"
              :key="restaurant.id"
              class="cursor-pointer"
              @click="viewRestaurant(restaurant)"
            >
              <RestaurantCard :restaurant="restaurant" />
            </div>
          </div>

          <!-- Menus Section -->
          <h2 class="text-3xl font-extrabold text-gray-900 mb-6">Featured Menus</h2>

          <!-- Menus Controls -->
          <div class="mb-6 rounded-2xl border border-orange-100 bg-white/90 p-4 sm:p-5 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
              <!-- Search -->
              <div class="w-full sm:max-w-md">
                <label for="menu-search" class="sr-only">Search dishes</label>
                <div class="relative">
                  <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <circle cx="11" cy="11" r="8" />
                      <path d="m21 21-4.3-4.3" />
                    </svg>
                  </span>
                  <input
                    id="menu-search"
                    v-model="queryMenus"
                    type="search"
                    placeholder="Search dishes..."
                    class="w-full rounded-xl border border-orange-200/70 bg-white pl-10 pr-10 py-2.5 text-sm shadow-sm placeholder:text-gray-400 focus:border-orange-500 focus:ring-2 focus:ring-orange-200"
                  />
                  <button
                    v-if="queryMenus"
                    type="button"
                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
                    aria-label="Clear search"
                    @click="queryMenus = ''"
                  >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Sort -->
              <div class="w-full sm:w-60">
                <label for="menu-sort" class="sr-only">Sort menu</label>
                <div class="relative">
                  <select
                    id="menu-sort"
                    v-model="selectedSort"
                    class="block w-full appearance-none rounded-xl border border-orange-200/70 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-200"
                  >
                    <option value="name_asc">Name: A → Z</option>
                    <option value="name_desc">Name: Z → A</option>
                    <option value="price_asc">Price: Low → High</option>
                    <option value="price_desc">Price: High → Low</option>
                  </select>
                  <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                      <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.24a.75.75 0 0 1-1.06 0L5.25 8.27a.75.75 0 0 1-.02-1.06Z" clip-rule="evenodd" />
                    </svg>
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Results summary -->
        <p class="mb-6 text-sm text-gray-600" aria-live="polite">
          <span v-if="totalCount === 0">No items available.</span>
          <span v-else-if="filteredMenus.length === totalCount">Showing all {{ totalCount }} item{{ totalCount === 1 ? '' : 's' }}.</span>
          <span v-else>Showing {{ filteredMenus.length }} of {{ totalCount }} item{{ totalCount === 1 ? '' : 's' }}.</span>
        </p>

        <!-- Empty State -->
        <div
          v-if="totalCount === 0"
          class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-orange-200 bg-orange-50/60 p-10 text-center"
        >
          <div class="mb-3 rounded-full bg-white p-3 text-orange-500 shadow">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6"><path d="M6.32 2.577A49.255 49.255 0 0 1 12 2.25c1.92 0 3.8.11 5.68.327a.75.75 0 0 1 .66.743V7.5a.75.75 0 0 1-.75.75h-11A.75.75 0 0 1 6 7.5V3.647a.75.75 0 0 1 .32-.07ZM4.5 8.25A.75.75 0 0 0 3.75 9v9.75A2.25 2.25 0 0 0 6 21h12a2.25 2.25 0 0 0 2.25-2.25V9a.75.75 0 0 0-.75-.75h-15Z"/></svg>
          </div>
          <h2 class="text-lg font-semibold text-gray-900">No menu items yet</h2>
          <p class="mt-1 max-w-md text-sm text-gray-600">Please check back later.</p>
        </div>

        <!-- No Results -->
        <div
          v-else-if="filteredMenus.length === 0"
          class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-orange-200 bg-orange-50/40 p-8 text-center"
        >
          <div class="mb-3 rounded-full bg-white p-3 text-orange-500 shadow">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6"><path fill-rule="evenodd" d="M10.5 3.75a6.75 6.75 0 1 0 4.263 12.05l4.219 4.218a.75.75 0 1 0 1.06-1.06l-4.218-4.22A6.75 6.75 0 0 0 10.5 3.75ZM6 10.5a4.5 4.5 0 1 1 9.001.001A4.5 4.5 0 0 1 6 10.5Z" clip-rule="evenodd"/></svg>
          </div>
          <h2 class="text-base font-semibold text-gray-900">No results</h2>
          <p class="mt-1 max-w-md text-sm text-gray-600">
            No dishes match "{{ query }}". Try a different search.
          </p>
          <button
            type="button"
            class="mt-4 inline-flex items-center rounded-lg bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-2 text-sm font-medium text-white shadow hover:from-orange-600 hover:to-amber-600 focus:outline-none focus:ring-2 focus:ring-orange-300"
            @click="query = ''; selectedSort = 'name_asc'"
          >
            Clear filters
          </button>
        </div>

        <!-- Menu Grid -->
        <div
          v-else
          role="list"
          class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
          <article
            v-for="menu in filteredMenus"
            :key="menu.id"
            role="listitem"
            class="group relative overflow-hidden rounded-2xl border border-orange-100 bg-white shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-lg focus-within:ring-2 focus-within:ring-orange-300"
          >
            <!-- Subtle top gradient bar -->
            <div class="pointer-events-none absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-orange-500 via-amber-400 to-orange-500 opacity-70"></div>

            <!-- Image with maintained aspect ratio -->
            <div class="relative overflow-hidden">
              <div class="aspect-[4/3] w-full bg-gray-100"></div>
              <img
                :src="imgSrc(menu)"
                :alt="menu?.name ? `${menu.name} image` : 'Menu image'"
                loading="lazy"
                class="absolute inset-0 h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
              />
              <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/15 via-transparent to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
              
              <!-- Floating availability badge -->
              <div class="absolute top-3 right-3">
                <span class="inline-flex items-center rounded-full bg-green-500 px-2.5 py-1 text-xs font-medium text-white shadow-lg">
                  Available
                </span>
              </div>
            </div>

            <!-- Content -->
            <div class="p-4 sm:p-5">
              <!-- Restaurant Name Badge -->
              <div class="mb-3 inline-flex items-center">
                <div class="flex items-center space-x-2 rounded-full bg-gradient-to-r from-orange-50 to-amber-50 px-3 py-1.5 text-xs font-medium text-orange-700 border border-orange-200/50">
                  <svg class="h-3.5 w-3.5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                  </svg>
                  <span>{{ menu.restaurant?.name || 'Local Restaurant' }}</span>
                </div>
              </div>
              
              <h3 class="text-lg font-semibold text-gray-900">
                {{ menu.name }}
              </h3>
              <p class="mt-1 text-sm text-gray-600 clamp-2">{{ menu.description }}</p>

              <div class="mt-5 space-y-3">
  <span class="inline-flex items-center text-lg font-semibold text-orange-700">
    {{ priceFormatter.format(Number(menu.price) || 0) }}
  </span>
  
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 mt-4">
                <div class="flex gap-2 w-full sm:w-auto">
                  <button
                    @click="addToCart(menu)"
                    class="flex-1 sm:flex-none inline-flex items-center justify-center rounded-lg bg-gradient-to-r from-green-500 to-emerald-500 px-3 py-2 text-sm font-medium text-white shadow hover:from-green-600 hover:to-emerald-600 focus:outline-none focus:ring-2 focus:ring-green-300"
                  >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add to Cart
                  </button>
                  <button
                    @click="goToCart(menu)"
                    class="flex-1 sm:flex-none inline-flex items-center justify-center rounded-lg bg-gradient-to-r from-orange-500 to-amber-500 px-3 py-2 text-sm font-medium text-white shadow hover:from-orange-600 hover:to-amber-600 focus:outline-none focus:ring-2 focus:ring-orange-300"
                  >
                    Order Now
                  </button>
                </div>
            </div>
</div>

            </div>
          </article>
        </div>

        <!-- Load More Button -->
        <div v-if="filteredMenus.length > displayedCount" class="mt-8 text-center">
          <button
            @click="displayedCount = Math.min(displayedCount + itemsPerPage, filteredMenus.length)"
            class="inline-flex items-center rounded-lg bg-gradient-to-r from-orange-500 to-amber-500 px-6 py-3 text-sm font-medium text-white shadow hover:from-orange-600 hover:to-amber-600 focus:outline-none focus:ring-2 focus:ring-orange-300"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
            </svg>
            Load More ({{ filteredMenus.length - displayedCount }} more)
          </button>
        </div>
        </div>
      </div>
    </div>
</template>

<style scoped>
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}
</style>
