<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({
  auth: Object,
})

// Suggested categories
const categorySuggestions = [
  'Ethiopian',
  'Italian',
  'Indian',
  'Chinese',
  'Japanese',
  'American',
  'Vegan',
  'Vegetarian',
  'Desserts',
  'Bakery',
  'Cafe',
  'Fast Food',
  'Seafood',
  'BBQ',
  'Grill',
]

// Form state
const form = useForm({
  name: '',
  description: '',
  category: '',
  phone: '',
  address: '',
  logo: null,
  rating: 0,
  slug: '',
})

// Slug helpers
const userEditedSlug = ref(false)
function slugify(str) {
  return (str || '')
    .toString()
    .normalize('NFKD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9\s-]/g, '')
    .replace(/[\s_-]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 80)
}

watch(
  () => form.name,
  (val) => {
    if (!userEditedSlug.value) {
      form.slug = slugify(val)
    }
  }
)

function onSlugInput() {
  userEditedSlug.value = true
}

// File handling
const logoPreview = ref(null)
function onLogoChange(e) {
  const file = e.target.files?.[0]
  form.logo = file || null
  if (file) {
    const reader = new FileReader()
    reader.onload = (ev) => {
      logoPreview.value = ev.target?.result
    }
    reader.readAsDataURL(file)
  } else {
    logoPreview.value = null
  }
}

function regenerateSlugFromName() {
  form.slug = slugify(form.name)
  userEditedSlug.value = false
}

// Submit
function submit() {
  form.post('/restaurants', {
    forceFormData: true,
    onSuccess: () => {
      window.location.href = '/restaurant'
    },
  })
}
</script>

<template>
    <Head title="Add Restaurant" />

    <!-- Beautiful Background with Restaurant Theme -->
    <div
      class="min-h-screen bg-gradient-to-br from-orange-50 via-white to-amber-50 bg-fixed bg-center bg-cover"
      style="background-image: url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1770&q=80');"
    >
      <!-- Overlay for better text readability -->
      <div class="min-h-screen bg-black/30 backdrop-blur-sm">
        <div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
          <!-- Header -->
          <div class="mb-10 text-center">
            <h1
              class="text-4xl sm:text-5xl font-extrabold tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-orange-200 to-yellow-100 drop-shadow-lg"
            >
              Add Your Restaurant
            </h1>
            <p class="mt-3 text-lg text-orange-100/90">
              Fill in the details to get started with your culinary journey.
            </p>
          </div>

          <!-- Form Card with Glassmorphism -->
          <div
            class="rounded-3xl border border-orange-200/30 bg-white/80 shadow-2xl backdrop-blur-md transition-all duration-300 hover:shadow-3xl"
          >
            <!-- Accent Top Bar -->
            <div class="-mx-5 -mt-5 mb-6 h-1.5 bg-gradient-to-r from-orange-500 via-amber-400 to-orange-500 sm:-mx-6 lg:-mx-8" />

            <form @submit.prevent="submit" enctype="multipart/form-data" class="space-y-10 p-6 sm:p-8">
              <!-- Basic Info -->
              <div>
                <h2 class="text-xl font-semibold text-gray-800">Basic Information</h2>
                <p class="text-sm text-gray-500">Name and public slug for your restaurant.</p>
                <div class="mt-5 grid grid-cols-1 gap-6 sm:grid-cols-2">
                  <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Name <span class="text-orange-600">*</span></label>
                    <input
                      id="name"
                      v-model.trim="form.name"
                      type="text"
                      required
                      placeholder="e.g., Spice Garden"
                      class="mt-2 w-full rounded-xl border border-orange-200 bg-white/70 px-4 py-3 text-sm shadow-sm placeholder:text-gray-400 focus:border-orange-500 focus:ring-1 focus:ring-orange-300 transition-all"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                  </div>

                  <div>
                    <div class="flex items-center justify-between">
                      <label for="slug" class="block text-sm font-medium text-gray-700">Slug <span class="text-orange-600">*</span></label>
                      <button
                        type="button"
                        @click="regenerateSlugFromName"
                        class="text-xs font-medium text-orange-600 hover:text-orange-700 underline transition"
                      >
                        Regenerate
                      </button>
                    </div>
                    <input
                      id="slug"
                      v-model.trim="form.slug"
                      @input="onSlugInput"
                      type="text"
                      required
                      placeholder="e.g., spice-garden"
                      class="mt-2 w-full rounded-xl border border-orange-200 bg-white/70 px-4 py-3 text-sm shadow-sm placeholder:text-gray-400 focus:border-orange-500 focus:ring-1 focus:ring-orange-300 transition-all"
                    />
                    <p class="mt-1 text-xs text-gray-500">Used in your public URL.</p>
                    <p v-if="form.errors.slug" class="mt-1 text-sm text-red-600">{{ form.errors.slug }}</p>
                  </div>
                </div>
              </div>

              <!-- Classification -->
              <div>
                <h2 class="text-xl font-semibold text-gray-800">Classification</h2>
                <p class="text-sm text-gray-500">Category helps customers discover your cuisine.</p>
                <div class="mt-5 grid grid-cols-1 gap-6 sm:grid-cols-2">
                  <div>
                    <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                    <input
                      id="category"
                      v-model.trim="form.category"
                      type="text"
                      list="category-suggestions"
                      placeholder="e.g., Indian"
                      class="mt-2 w-full rounded-xl border border-orange-200 bg-white/70 px-4 py-3 text-sm shadow-sm placeholder:text-gray-400 focus:border-orange-500 focus:ring-1 focus:ring-orange-300 transition-all"
                    />
                    <datalist id="category-suggestions">
                      <option v-for="c in categorySuggestions" :key="c" :value="c">{{ c }}</option>
                    </datalist>
                    <p v-if="form.errors.category" class="mt-1 text-sm text-red-600">{{ form.errors.category }}</p>
                  </div>

                  <div>
                    <label for="rating" class="block text-sm font-medium text-gray-700">Rating</label>
                    <input
                      id="rating"
                      v-model.number="form.rating"
                      type="number"
                      step="0.01"
                      min="0"
                      max="9.99"
                      placeholder="0 - 9.99"
                      class="mt-2 w-full rounded-xl border border-orange-200 bg-white/70 px-4 py-3 text-sm shadow-sm placeholder:text-gray-400 focus:border-orange-500 focus:ring-1 focus:ring-orange-300 transition-all"
                    />
                    <p class="mt-1 text-xs text-gray-500">Optional — can be updated later.</p>
                    <p v-if="form.errors.rating" class="mt-1 text-sm text-red-600">{{ form.errors.rating }}</p>
                  </div>
                </div>
              </div>

              <!-- Contact & Address -->
              <div>
                <h2 class="text-xl font-semibold text-gray-800">Contact</h2>
                <p class="text-sm text-gray-500">Make it easy for customers to reach you.</p>
                <div class="mt-5 grid grid-cols-1 gap-6 sm:grid-cols-2">
                  <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                    <input
                      id="phone"
                      v-model.trim="form.phone"
                      type="tel"
                      inputmode="tel"
                      placeholder="e.g., +251 900 000000"
                      class="mt-2 w-full rounded-xl border border-orange-200 bg-white/70 px-4 py-3 text-sm shadow-sm placeholder:text-gray-400 focus:border-orange-500 focus:ring-1 focus:ring-orange-300 transition-all"
                    />
                    <p class="mt-1 text-xs text-gray-500">Include country code.</p>
                    <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
                  </div>

                  <div>
                    <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                    <input
                      id="address"
                      v-model.trim="form.address"
                      type="text"
                      placeholder="Street, City, Country"
                      class="mt-2 w-full rounded-xl border border-orange-200 bg-white/70 px-4 py-3 text-sm shadow-sm placeholder:text-gray-400 focus:border-orange-500 focus:ring-1 focus:ring-orange-300 transition-all"
                    />
                    <p v-if="form.errors.address" class="mt-1 text-sm text-red-600">{{ form.errors.address }}</p>
                  </div>
                </div>
              </div>

              <!-- Description -->
              <div>
                <h2 class="text-xl font-semibold text-gray-800">Description</h2>
                <p class="text-sm text-gray-500">Tell customers what makes your restaurant special.</p>
                <div class="mt-5">
                  <textarea
                    id="description"
                    v-model.trim="form.description"
                    rows="4"
                    placeholder="Write a short description..."
                    class="mt-2 w-full rounded-xl border border-orange-200 bg-white/70 px-4 py-3 text-sm shadow-sm placeholder:text-gray-400 focus:border-orange-500 focus:ring-1 focus:ring-orange-300 transition-all resize-none"
                  />
                  <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
                </div>
              </div>

              <!-- Logo Upload -->
              <div>
                <h2 class="text-xl font-semibold text-gray-800">Logo</h2>
                <p class="text-sm text-gray-500">Upload a logo (PNG/JPG/SVG). Max ~2MB.</p>
                <div class="mt-5 grid grid-cols-1 gap-6 sm:grid-cols-2">
                  <div>
                    <label for="logo" class="block text-sm font-medium text-gray-700">Logo File</label>
                    <input
                      id="logo"
                      type="file"
                      accept="image/*"
                      @change="onLogoChange"
                      class="mt-2 block w-full text-sm text-gray-700 file:mr-3 file:rounded-xl file:border-0 file:bg-orange-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-orange-700 hover:file:bg-orange-100 transition-all"
                    />
                    <p v-if="form.errors.logo" class="mt-1 text-sm text-red-600">{{ form.errors.logo }}</p>
                  </div>

                  <div v-if="logoPreview" class="flex justify-center sm:justify-end">
                    <div class="rounded-xl overflow-hidden shadow-lg ring-2 ring-orange-200/50">
                      <img :src="logoPreview" alt="Logo preview" class="h-20 w-20 object-cover" />
                    </div>
                  </div>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex flex-col gap-3 sm:flex-row sm:justify-end sm:items-center pt-4">
                <button
                  type="button"
                  @click="form.reset(); logoPreview = null; userEditedSlug = false;"
                  class="px-5 py-2.5 rounded-xl border border-orange-200 bg-white/70 text-orange-700 font-medium text-sm hover:bg-orange-50 transition-all focus:outline-none focus:ring-2 focus:ring-orange-200"
                >
                  Reset
                </button>

                <button
                  type="submit"
                  :disabled="form.processing"
                  class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 text-white font-semibold text-sm shadow-md hover:from-orange-600 hover:to-amber-600 focus:outline-none focus:ring-2 focus:ring-orange-300 disabled:opacity-60 disabled:cursor-not-allowed transition-all flex items-center justify-center"
                >
                  <svg
                    v-if="form.processing"
                    class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                  >
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path
                      class="opacity-75"
                      fill="currentColor"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                  </svg>
                  {{ form.processing ? 'Saving...' : 'Save Restaurant' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
</template>

<style scoped>
/* Subtle backdrop for accessibility */
.bg-fixed {
  background-attachment: fixed;
}
</style>