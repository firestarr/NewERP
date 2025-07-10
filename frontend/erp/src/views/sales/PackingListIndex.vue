<template>
  <div class="packing-list-index">
    <!-- Header Section -->
    <div class="page-header">
      <div class="header-left">
        <h1 class="page-title">
          <i class="fas fa-boxes"></i>
          Packing Lists
        </h1>
        <p class="page-subtitle">Manage your packing lists and shipping preparations</p>
      </div>
      <div class="header-right">
        <button class="btn btn-primary" @click="showCreateModal = true">
          <i class="fas fa-plus"></i>
          New Packing List
        </button>
      </div>
    </div>

    <!-- Filters Section -->
    <div class="filters-section">
      <div class="filter-grid">
        <div class="filter-group">
          <label>Status</label>
          <select v-model="filters.status" @change="loadPackingLists" class="form-control">
            <option value="">All Status</option>
            <option value="Draft">Draft</option>
            <option value="In Progress">In Progress</option>
            <option value="Completed">Completed</option>
            <option value="Shipped">Shipped</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Customer</label>
          <select v-model="filters.customer_id" @change="loadPackingLists" class="form-control">
            <option value="">All Customers</option>
            <option v-for="customer in customers" :key="customer.customer_id" :value="customer.customer_id">
              {{ customer.name }}
            </option>
          </select>
        </div>
        <div class="filter-group">
          <label>Date From</label>
          <input type="date" v-model="filters.date_from" @change="loadPackingLists" class="form-control">
        </div>
        <div class="filter-group">
          <label>Date To</label>
          <input type="date" v-model="filters.date_to" @change="loadPackingLists" class="form-control">
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid" v-if="stats">
      <div class="stat-card">
        <div class="stat-icon draft">
          <i class="fas fa-edit"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.draft || 0 }}</h3>
          <p>Draft</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon progress">
          <i class="fas fa-tasks"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.in_progress || 0 }}</h3>
          <p>In Progress</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon completed">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.completed || 0 }}</h3>
          <p>Completed</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon shipped">
          <i class="fas fa-shipping-fast"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.shipped || 0 }}</h3>
          <p>Shipped</p>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="spinner"></div>
      <p>Loading packing lists...</p>
    </div>

    <!-- Packing Lists Grid -->
    <div v-else class="packing-lists-grid">
      <div v-for="packingList in packingLists" :key="packingList.packing_list_id" class="packing-list-card">
        <div class="card-header">
          <div class="card-title">
            <h3>{{ packingList.packing_list_number }}</h3>
            <span class="badge" :class="getStatusClass(packingList.status)">
              {{ packingList.status }}
            </span>
          </div>
          <div class="card-actions">
            <button class="btn-icon" @click="viewPackingList(packingList.packing_list_id)" title="View Details">
              <i class="fas fa-eye"></i>
            </button>
            <button class="btn-icon" @click="editPackingList(packingList.packing_list_id)" title="Edit"
                    v-if="packingList.status !== 'Shipped'">
              <i class="fas fa-edit"></i>
            </button>
            <div class="dropdown">
              <button class="btn-icon dropdown-toggle" @click="toggleDropdown(packingList.packing_list_id)">
                <i class="fas fa-ellipsis-v"></i>
              </button>
              <div class="dropdown-menu" v-show="activeDropdown === packingList.packing_list_id">
                <button @click="completePacking(packingList)" v-if="packingList.status === 'Draft' || packingList.status === 'In Progress'">
                  <i class="fas fa-check"></i> Complete Packing
                </button>
                <button @click="markAsShipped(packingList)" v-if="packingList.status === 'Completed'">
                  <i class="fas fa-shipping-fast"></i> Mark as Shipped
                </button>
                <button @click="printPackingList(packingList)" class="text-blue">
                  <i class="fas fa-print"></i> Print
                </button>
                <button @click="deletePackingList(packingList)" v-if="packingList.status !== 'Shipped'" class="text-red">
                  <i class="fas fa-trash"></i> Delete
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="card-body">
          <div class="info-grid">
            <div class="info-item">
              <i class="fas fa-calendar"></i>
              <span>{{ formatDate(packingList.packing_date) }}</span>
            </div>
            <div class="info-item">
              <i class="fas fa-user"></i>
              <span>{{ packingList.customer?.name || 'N/A' }}</span>
            </div>
            <div class="info-item">
              <i class="fas fa-truck"></i>
              <span>{{ packingList.delivery?.delivery_number || 'N/A' }}</span>
            </div>
            <div class="info-item">
              <i class="fas fa-receipt"></i>
              <span>{{ packingList.delivery?.sales_order?.so_number || 'N/A' }}</span>
            </div>
          </div>

          <div class="metrics-grid">
            <div class="metric">
              <span class="metric-value">{{ packingList.number_of_packages || 0 }}</span>
              <span class="metric-label">Packages</span>
            </div>
            <div class="metric">
              <span class="metric-value">{{ formatWeight(packingList.total_weight) }}</span>
              <span class="metric-label">Weight</span>
            </div>
            <div class="metric">
              <span class="metric-value">{{ formatVolume(packingList.total_volume) }}</span>
              <span class="metric-label">Volume</span>
            </div>
          </div>

          <div class="packed-by" v-if="packingList.packed_by">
            <small>Packed by: {{ packingList.packed_by }}</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!loading && packingLists.length === 0" class="empty-state">
      <i class="fas fa-boxes"></i>
      <h3>No Packing Lists Found</h3>
      <p>Start by creating your first packing list from a delivery order.</p>
      <button class="btn btn-primary" @click="showCreateModal = true">
        Create Packing List
      </button>
    </div>

    <!-- Create Modal -->
    <div v-if="showCreateModal" class="modal-overlay" @click="showCreateModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h2>Create Packing List</h2>
          <button class="btn-close" @click="showCreateModal = false">×</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Select Delivery Order</label>
            <select v-model="newPackingList.delivery_id" class="form-control">
              <option value="">Select delivery...</option>
              <option v-for="delivery in availableDeliveries" :key="delivery.delivery_id" :value="delivery.delivery_id">
                {{ delivery.delivery_number }} - {{ delivery.customer?.name }}
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Packing Date</label>
            <input type="date" v-model="newPackingList.packing_date" class="form-control">
          </div>
          <div class="form-group">
            <label>Packed By</label>
            <input type="text" v-model="newPackingList.packed_by" class="form-control" placeholder="Enter packer name">
          </div>
          <div class="form-group">
            <label>Notes</label>
            <textarea v-model="newPackingList.notes" class="form-control" rows="3" placeholder="Additional notes..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="showCreateModal = false">Cancel</button>
          <button class="btn btn-primary" @click="createPackingList" :disabled="createLoading">
            <span v-if="createLoading">Creating...</span>
            <span v-else>Create Packing List</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

export default {
  name: 'PackingListIndex',
  setup() {
    const packingLists = ref([])
    const customers = ref([])
    const availableDeliveries = ref([])
    const loading = ref(false)
    const createLoading = ref(false)
    const showCreateModal = ref(false)
    const activeDropdown = ref(null)
    const stats = ref(null)

    const filters = reactive({
      status: '',
      customer_id: '',
      date_from: '',
      date_to: ''
    })

    const newPackingList = reactive({
      delivery_id: '',
      packing_date: new Date().toISOString().split('T')[0],
      packed_by: '',
      notes: ''
    })

    // Load data on mount
    onMounted(() => {
      loadPackingLists()
      loadCustomers()
      loadAvailableDeliveries()
      loadStats()
    })

    // API Methods
    const loadPackingLists = async () => {
      loading.value = true
      try {
        const response = await axios.get('/sales/packing-lists', { params: filters })
        packingLists.value = response.data.data
      } catch (error) {
        console.error('Error loading packing lists:', error)
        alert('Error loading packing lists')
      } finally {
        loading.value = false
      }
    }

    const loadCustomers = async () => {
      try {
        const response = await axios.get('/customers')
        customers.value = response.data.data
      } catch (error) {
        console.error('Error loading customers:', error)
      }
    }

    const loadAvailableDeliveries = async () => {
      try {
        const response = await axios.get('/sales/packing-lists-available-deliveries')
        availableDeliveries.value = response.data.data
      } catch (error) {
        console.error('Error loading available deliveries:', error)
      }
    }

    const loadStats = async () => {
      try {
        const response = await axios.get('/sales/packing-lists-progress')
        stats.value = response.data.summary
      } catch (error) {
        console.error('Error loading stats:', error)
      }
    }

    const createPackingList = async () => {
      if (!newPackingList.delivery_id) {
        alert('Please select a delivery order')
        return
      }

      createLoading.value = true
      try {
        await axios.post('/sales/packing-lists/from-delivery', newPackingList)
        alert('Packing list created successfully!')
        showCreateModal.value = false
        loadPackingLists()
        loadAvailableDeliveries()
        resetForm()
      } catch (error) {
        console.error('Error creating packing list:', error)
        alert('Error creating packing list: ' + (error.response?.data?.message || error.message))
      } finally {
        createLoading.value = false
      }
    }

    const completePacking = async (packingList) => {
      const checkedBy = prompt('Enter checker name:')
      if (!checkedBy) return

      try {
        await axios.put(`/sales/packing-lists/${packingList.packing_list_id}/complete`, {
          checked_by: checkedBy
        })
        alert('Packing completed successfully!')
        loadPackingLists()
      } catch (error) {
        console.error('Error completing packing:', error)
        alert('Error completing packing: ' + (error.response?.data?.message || error.message))
      }
    }

    const markAsShipped = async (packingList) => {
      if (!confirm('Mark this packing list as shipped?')) return

      try {
        await axios.put(`/sales/packing-lists/${packingList.packing_list_id}/ship`)
        alert('Packing list marked as shipped!')
        loadPackingLists()
      } catch (error) {
        console.error('Error marking as shipped:', error)
        alert('Error marking as shipped: ' + (error.response?.data?.message || error.message))
      }
    }

    const deletePackingList = async (packingList) => {
      if (!confirm(`Delete packing list ${packingList.packing_list_number}?`)) return

      try {
        await axios.delete(`/sales/packing-lists/${packingList.packing_list_id}`)
        alert('Packing list deleted successfully!')
        loadPackingLists()
      } catch (error) {
        console.error('Error deleting packing list:', error)
        alert('Error deleting packing list: ' + (error.response?.data?.message || error.message))
      }
    }

    // Navigation methods
    const router = useRouter()

    const viewPackingList = (id) => {
      router.push({ name: 'PackingListDetail', params: { id } })
    }

    const editPackingList = (id) => {
      router.push({ name: 'PackingListForm', params: { id } })
    }

    const printPackingList = (packingList) => {
      window.open(`/sales/packing-lists/${packingList.packing_list_id}/print`, '_blank')
    }

    // Utility methods
    const resetForm = () => {
      Object.assign(newPackingList, {
        delivery_id: '',
        packing_date: new Date().toISOString().split('T')[0],
        packed_by: '',
        notes: ''
      })
    }

    const toggleDropdown = (id) => {
      activeDropdown.value = activeDropdown.value === id ? null : id
    }

    const getStatusClass = (status) => {
      const classes = {
        'Draft': 'badge-secondary',
        'In Progress': 'badge-warning',
        'Completed': 'badge-success',
        'Shipped': 'badge-primary'
      }
      return classes[status] || 'badge-secondary'
    }

    const formatDate = (date) => {
      return new Date(date).toLocaleDateString()
    }

    const formatWeight = (weight) => {
      return weight ? `${weight} kg` : '0 kg'
    }

    const formatVolume = (volume) => {
      return volume ? `${volume} m³` : '0 m³'
    }

    return {
      packingLists,
      customers,
      availableDeliveries,
      loading,
      createLoading,
      showCreateModal,
      activeDropdown,
      stats,
      filters,
      newPackingList,
      loadPackingLists,
      createPackingList,
      completePacking,
      markAsShipped,
      deletePackingList,
      viewPackingList,
      editPackingList,
      printPackingList,
      toggleDropdown,
      getStatusClass,
      formatDate,
      formatWeight,
      formatVolume
    }
  }
}
</script>

<style scoped>
.packing-list-index {
  padding: 24px;
  background: #f8fafc;
  min-height: 100vh;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 32px;
  background: white;
  padding: 24px;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.page-title {
  margin: 0;
  font-size: 32px;
  font-weight: 700;
  color: #1f2937;
  display: flex;
  align-items: center;
  gap: 12px;
}

.page-title i {
  color: #3b82f6;
}

.page-subtitle {
  margin: 8px 0 0 0;
  color: #6b7280;
  font-size: 16px;
}

.filters-section {
  background: white;
  padding: 24px;
  border-radius: 12px;
  margin-bottom: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.filter-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}

.filter-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: #374151;
}

.form-control {
  width: 100%;
  padding: 12px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.2s;
}

.form-control:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  background: white;
  padding: 24px;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 16px;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  color: white;
}

.stat-icon.draft { background: #6b7280; }
.stat-icon.progress { background: #f59e0b; }
.stat-icon.completed { background: #10b981; }
.stat-icon.shipped { background: #3b82f6; }

.stat-content h3 {
  margin: 0;
  font-size: 24px;
  font-weight: 700;
  color: #1f2937;
}

.stat-content p {
  margin: 4px 0 0 0;
  color: #6b7280;
  font-size: 14px;
}

.packing-lists-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 24px;
}

.packing-list-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  transition: transform 0.2s, box-shadow 0.2s;
}

.packing-list-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.card-header {
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-title {
  display: flex;
  align-items: center;
  gap: 12px;
}

.card-title h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #1f2937;
}

.badge {
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.badge-secondary { background: #f3f4f6; color: #374151; }
.badge-warning { background: #fef3c7; color: #92400e; }
.badge-success { background: #d1fae5; color: #065f46; }
.badge-primary { background: #dbeafe; color: #1e40af; }

.card-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-icon {
  width: 36px;
  height: 36px;
  border: none;
  background: #f3f4f6;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-icon:hover {
  background: #e5e7eb;
}

.dropdown {
  position: relative;
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  min-width: 180px;
}

.dropdown-menu button {
  width: 100%;
  padding: 12px 16px;
  border: none;
  background: none;
  text-align: left;
  cursor: pointer;
  transition: background-color 0.2s;
  display: flex;
  align-items: center;
  gap: 8px;
}

.dropdown-menu button:hover {
  background: #f3f4f6;
}

.text-blue { color: #3b82f6; }
.text-red { color: #ef4444; }

.card-body {
  padding: 20px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 16px;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #6b7280;
  font-size: 14px;
}

.info-item i {
  color: #9ca3af;
  width: 16px;
}

.metrics-grid {
  display: flex;
  justify-content: space-around;
  margin-bottom: 16px;
  padding: 16px;
  background: #f9fafb;
  border-radius: 8px;
}

.metric {
  text-align: center;
}

.metric-value {
  display: block;
  font-size: 18px;
  font-weight: 600;
  color: #1f2937;
}

.metric-label {
  display: block;
  font-size: 12px;
  color: #6b7280;
  margin-top: 4px;
}

.packed-by {
  color: #6b7280;
  font-style: italic;
}

.btn {
  padding: 12px 24px;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background: #2563eb;
}

.btn-secondary {
  background: #6b7280;
  color: white;
}

.btn-secondary:hover {
  background: #4b5563;
}

.loading-container {
  text-align: center;
  padding: 48px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f4f6;
  border-top: 4px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.empty-state {
  text-align: center;
  padding: 48px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.empty-state i {
  font-size: 48px;
  color: #d1d5db;
  margin-bottom: 16px;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  padding: 24px;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h2 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
}

.btn-close {
  width: 32px;
  height: 32px;
  border: none;
  background: #f3f4f6;
  border-radius: 8px;
  cursor: pointer;
  font-size: 18px;
}

.modal-body {
  padding: 24px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: #374151;
}

.modal-footer {
  padding: 24px;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    gap: 16px;
  }

  .filter-grid {
    grid-template-columns: 1fr;
  }

  .packing-lists-grid {
    grid-template-columns: 1fr;
  }

  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
