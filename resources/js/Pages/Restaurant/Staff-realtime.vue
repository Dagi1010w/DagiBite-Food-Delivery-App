<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SideBar from '@/Pages/Restaurant/SideBar.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  auth: {
    type: Object,
    required: true
  },
  staff: {
    type: Array,
    default: () => []
  }
});

// Reactive staff data
const staffData = ref([...props.staff]);

const showAddStaffModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const roles = [
  'Chef', 'Sous Chef', 'Manager', 'Waiter', 'Waitress',
  'Bartender', 'Host', 'Dishwasher', 'Cashier',
  'Delivery Driver', 'Kitchen Assistant', 'Supervisor'
];

const form = useForm({
  name: '',
  email: '',
  role: '',
  phone: '',
  address: '',
  salary: '',
  start_date: new Date().toISOString().split('T')[0],
  emergency_contact: '',
  notes: '',
  active_today: false
});

// Computed properties using reactive data
const uniqueRoles = computed(() => {
  return [...new Set(staffData.value.map(s => s.role))];
});

const activeTodayCount = computed(() => {
  return staffData.value.filter(s => s.active_today).length;
});

const avgTenure = computed(() => {
  if (!staffData.value.length) return 0;
  const total = staffData.value.reduce((sum, s) => sum + calculateTenure(s.start_date), 0);
  return (total / staffData.value.length).toFixed(1);
});

const calculateTenure = (startDate) => {
  if (!startDate) return 0;
  const start = new Date(startDate);
  const today = new Date();
  const diffYears = today.getFullYear() - start.getFullYear();
  const monthDiff = today.getMonth() - start.getMonth();
  return diffYears + (monthDiff < 0 || (monthDiff === 0 && today.getDate() < start.getDate()) ? -1 : 0);
};

// Real-time updates using polling (fallback when Pusher not available)
let pollingInterval = null;

const updateStaffStatus = (updatedStaff) => {
  const index = staffData.value.findIndex(s => s.id === updatedStaff.id);
  if (index !== -1) {
    staffData.value[index] = { ...staffData.value[index], ...updatedStaff };
  }
};

const toggleActiveToday = async (staffMember) => {
  try {
    const response = await fetch(`/api/staff/${staffMember.id}/toggle-active`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      }
    });
    
    if (response.ok) {
      const data = await response.json();
      updateStaffStatus(data.staff);
    }
  } catch (error) {
    console.error('Error toggling active status:', error);
  }
};

const openAddStaffModal = (staffMember = null) => {
  isEditing.value = !!staffMember;
  editingId.value = staffMember?.id || null;

  if (staffMember) {
    form.name = staffMember.name || '';
    form.email = staffMember.email || '';
    form.role = staffMember.role || '';
    form.phone = staffMember.phone || '';
    form.address = staffMember.address || '';
    form.salary = staffMember.salary || '';
    form.start_date = staffMember.start_date || new Date().toISOString().split('T')[0];
    form.emergency_contact = staffMember.emergency_contact || '';
    form.notes = staffMember.notes || '';
    form.active_today = staffMember.active_today || false;
  } else {
    form.reset();
    form.start_date = new Date().toISOString().split('T')[0];
  }

  showAddStaffModal.value = true;
};

const closeAddStaffModal = () => {
  showAddStaffModal.value = false;
  isEditing.value = false;
  editingId.value = null;
  form.reset();
};

const submitStaff = () => {
  if (isEditing.value) {
    form.put(`/staff/${editingId.value}`, {
      onSuccess: () => {
        closeAddStaffModal();
      },
      onError: (errors) => {
        console.error('Update failed:', errors);
      }
    });
  } else {
    form.post('/staff', {
      onSuccess: () => {
        closeAddStaffModal();
      },
      onError: (errors) => {
        console.error('Create failed:', errors);
      }
    });
  }
};

const confirmDelete = (id) => {
  if (confirm('Are you sure you want to delete this staff member?')) {
    form.delete(`/staff/${id}`);
  }
};

// Poll for updates every 5 seconds
onMounted(() => {
  pollingInterval = setInterval(async () => {
    try {
      const response = await fetch('/api/staff');
      if (response.ok) {
        const data = await response.json();
        staffData.value = data.staff;
      }
    } catch (error) {
      console.error('Error polling staff data:', error);
    }
  }, 5000);
});

onUnmounted(() => {
  if (pollingInterval) {
    clearInterval(pollingInterval);
  }
});
</script>

<template>
  <AuthenticatedLayout :auth="auth">
    <Head title="Staff Management" />
    
    <div class="min-h-screen flex">
      <!-- Sidebar -->
      <SideBar />
      <!-- Main Content -->
      <div class="flex-1 ml-20 md:ml-64 p-4 sm:p-6 bg-white bg-opacity-90 min-h-screen transition-all duration-300">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
          <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Staff</h1>
            <p class="text-gray-600 text-sm sm:text-base">Manage your restaurant's staff members</p>
          </div>
          <div class="flex items-center space-x-4">
            <button @click="openAddStaffModal()" class="text-orange-600 hover:text-orange-700 flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
              </svg>
              Add Staff
            </button>
          </div>
        </div>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
          <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-500 text-sm">Total Staff</p>
                <p class="text-2xl sm:text-3xl font-bold mt-1">{{ staffData.length }}</p>
              </div>
              <div class="p-2 sm:p-3 rounded-lg bg-orange-50 text-orange-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87M17 8a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
              </div>
            </div>
            <p class="text-xs sm:text-sm text-green-500 mt-2 flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
              </svg>
              +2 this month
            </p>
          </div>
          
          <!-- Active Today Card - Now Interactive -->
          <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-100 cursor-pointer hover:shadow-md transition-all duration-200 group" 
               @click="() => {}">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-500 text-sm">Active Today</p>
                <p class="text-2xl sm:text-3xl font-bold mt-1 text-blue-600 group-hover:text-blue-700 transition-colors">
                  {{ activeTodayCount }}
                </p>
              </div>
              <div class="p-2 sm:p-3 rounded-lg bg-blue-50 text-blue-600 group-hover:bg-blue-100 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0-1.104-.896-2-2-2s-2 .896-2 2 .896 2 2 2 2-.896 2-2zm6 2a2 2 0 100-4 2 2 0 000 4zm-6 6a6 6 0 100-12 6 6 0 000 12z" />
                </svg>
              </div>
            </div>
            <p class="text-xs sm:text-sm text-green-500 mt-2 flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
              </svg>
              <span class="animate-pulse">Live Updates</span>
            </p>
          </div>
          
          <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-500 text-sm">Roles</p>
                <p class="text-2xl sm:text-3xl font-bold mt-1">{{ uniqueRoles.length }}</p>
              </div>
              <div class="p-2 sm:p-3 rounded-lg bg-green-50 text-green-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
            <p class="text-xs sm:text-sm text-green-500 mt-2 flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
              </svg>
              {{ uniqueRoles.join(', ') }}
            </p>
          </div>
          <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-500 text-sm">Avg. Tenure</p>
                <p class="text-2xl sm:text-3xl font-bold mt-1">{{ avgTenure }} yrs</p>
              </div>
              <div class="p-2 sm:p-3 rounded-lg bg-purple-50 text-purple-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
              </div>
            </div>
            <p class="text-xs sm:text-sm text-green-500 mt-2 flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
              </svg>
              +0.1 this year
            </p>
          </div>
        </div>

        <!-- Staff List -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="p-4 sm:p-6 border-b border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <h2 class="text-lg sm:text-xl font-semibold text-gray-800">Staff List</h2>
          </div>
          <div class="divide-y divide-gray-100 scrollbar-hide" style="max-height: 600px; overflow-y: auto;">
            <div v-for="member in staffData" :key="member.id" class="p-4 sm:p-6 hover:bg-gray-50 transition-colors duration-150 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
              <div class="flex items-center w-full sm:w-auto">
                <img :src="`https://placehold.co/60x60?text=${member.name.split(' ').map(n => n[0]).join('')}`" :alt="member.name" class="h-12 w-12 rounded-full object-cover">
                <div class="ml-4">
                  <h3 class="font-medium text-gray-800">{{ member.name }}</h3>
                  <p class="text-sm text-gray-500">{{ member.email }}</p>
                  <p class="text-xs text-gray-400 mt-1">Role: {{ member.role }}</p>
                  <p class="text-xs text-gray-400">Joined: {{ member.start_date }}</p>
                </div>
              </div>
              <div class="flex flex-wrap items-center space-x-0 sm:space-x-8 gap-2 sm:gap-0 w-full sm:w-auto">
                <div class="text-center w-1/2 sm:w-auto">
                  <p class="text-lg font-bold text-gray-800">{{ calculateTenure(member.start_date) }} yrs</p>
                  <p class="text-xs text-gray-500">Tenure</p>
                </div>
                <div class="text-center w-1/2 sm:w-auto">
                  <button
                    @click="toggleActiveToday(member)"
                    :class="[
                      member.active_today 
                        ? 'bg-green-100 text-green-700 hover:bg-green-200' 
                        : 'bg-gray-100 text-gray-500 hover:bg-gray-200',
                      'px-3 py-1 rounded-full text-xs font-semibold transition-colors duration-200 cursor-pointer'
                    ]"
                  >
                    {{ member.active_today ? 'Active Today' : 'Inactive' }}
                  </button>
                </div>
                <div class="flex gap-2">
                  <button @click="openAddStaffModal(member)" class="p-2 rounded-lg bg-blue-100 text-blue-700 hover:bg-blue-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button @click="confirmDelete(member.id)" class="p-2 rounded-lg bg-red-100 text-red-700 hover:bg-red-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add/Edit Staff Modal -->
    <div v-if="showAddStaffModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">{{ isEditing ? 'Edit Staff Member' : 'Add New Staff Member' }}</h2>
            <button @click="closeAddStaffModal" class="text-gray-400 hover:text-gray-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <div class="p-6">
          <form @submit.prevent="submitStaff" class="space-y-6">
            <!-- Basic Information -->
            <div>
              <h3 class="text-lg font-medium text-gray-800 mb-4">Basic Information</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                  <input v-model="form.name" type="text" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                  <input v-model="form.email" type="email" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                </div>
              </div>
            </div>

            <!-- Position & Role -->
            <div>
              <h3 class="text-lg font-medium text-gray-800 mb-4">Position Details</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Role/Position *</label>
                  <select v-model="form.role" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    <option value="">Select a role</option>
                    <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                  <input v-model="form.start_date" type="date"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                </div>
              </div>
            </div>

            <!-- Contact Information -->
            <div>
              <h3 class="text-lg font-medium text-gray-800 mb-4">Contact Information</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                  <input v-model="form.phone" type="tel"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                  <input v-model="form.address" type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                </div>
              </div>
            </div>

            <!-- Employment Details -->
            <div>
              <h3 class="text-lg font-medium text-gray-800 mb-4">Employment Details</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Salary</label>
                  <input v-model="form.salary" type="number" step="0.01" min="0"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Emergency Contact</label>
                  <input v-model="form.emergency_contact" type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                </div>
              </div>
            </div>

            <!-- Additional Information -->
            <div>
              <h3 class="text-lg font-medium text-gray-800 mb-4">Additional Information</h3>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea v-model="form.notes" rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                  placeholder="Any additional notes or special requirements..."></textarea>
              </div>
            </div>

            <!-- Active Today Toggle -->
            <div>
              <label class="flex items-center">
                <input v-model="form.active_today" type="checkbox" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                <span class="ml-2 text-sm text-gray-700">Active Today</span>
              </label>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-4 pt-4">
              <button type="button" @click="closeAddStaffModal"
                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                Cancel
              </button>
              <button type="submit"
                class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 focus:ring-2 focus:ring-orange-500">
                {{ isEditing ? 'Update Staff' : 'Add Staff Member' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
body {
  font-family: 'Poppins', sans-serif;
  background-image: url('https://placehold.co/1920x1080?text=');
  background-size: cover;
  background-position: center;
  background-attachment: fixed;
  background-color: #f8f8f8;
}
.bg-orange-transparent {
  background-color: rgba(234, 88, 12, 0.9);
}
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
.order-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
}
.chart-container {
  height: 200px;
}
.floating-notification {
  animation: float 3s ease-in-out infinite;
}
@keyframes float {
  0% { transform: translateY(0px); }
  50% { transform: translateY(-10px); }
  100% { transform: translateY(0px); }
}
.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: .5;
  }
}
</style>
