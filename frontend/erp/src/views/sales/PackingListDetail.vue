<template>
  <div class="packing-list-detail">
    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <button class="btn-back" @click="goBack">
          <i class="fas fa-arrow-left"></i>
        </button>
        <div>
          <h1 class="page-title">
            <i class="fas fa-boxes"></i>
            Packing List Details
          </h1>
          <p class="page-subtitle" v-if="packingList.packing_list_number">
            {{ packingList.packing_list_number }}
          </p>
        </div>
      </div>
      <div class="header-actions">
        <button class="btn btn-secondary" @click="printPackingList">
          <i class="fas fa-print"></i>
          Print
        </button>
        <button
          class="btn btn-warning"
          @click="editPackingList"
          v-if="packingList.status !== 'Shipped'"
        >
          <i class="fas fa-edit"></i>
          Edit
        </button>
        <div class="dropdown" v-if="packingList.status !== 'Shipped'">
          <button class="btn btn-primary dropdown-toggle" @click="showActionsDropdown = !showActionsDropdown">
            <i class="fas fa-cog"></i>
            Actions
          </button>
          <div class="dropdown-menu" v-show="showActionsDropdown">
            <button
              @click="completePacking"
              v-if="packingList.status === 'Draft' || packingList.status === 'In Progress'"
            >
              <i class="fas fa-check"></i> Complete Packing
            </button>
            <button @click="markAsShipped" v-if="packingList.status === 'Completed'">
              <i class="fas fa-shipping-fast"></i> Mark as Shipped
            </button>
            <button @click="deletePackingList" class="text-red">
              <i class="fas fa-trash"></i> Delete
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="loading" class="loading-container">
      <div class="spinner"></div>
      <p>Loading packing list details...</p>
    </div>

    <div v-else class="detail-container">
      <!-- Status Banner -->
      <div class="status-banner" :class="getStatusBannerClass(packingList.status)">
        <div class="status-content">
          <i class="fas" :class="getStatusIcon(packingList.status)"></i>
          <div>
            <h3>{{ packingList.status }}</h3>
            <p>{{ getStatusDescription(packingList.status) }}</p>
          </div>
        </div>
        <div class="status-progress">
          <div class="progress-bar">
            <div class="progress-fill" :style="{ width: packingProgress + '%' }"></div>
          </div>
          <span class="progress-text">{{ packingProgress.toFixed(1) }}% Packed</span>
        </div>
      </div>

      <!-- Main Information -->
      <div class="info-section">
        <h2 class="section-title">
          <i class="fas fa-info-circle"></i>
          Basic Information
        </h2>

        <div class="info-grid">
          <div class="info-card">
            <div class="info-label">Packing List Number</div>
            <div class="info-value">{{ packingList.packing_list_number }}</div>
          </div>
          <div class="info-card">
            <div class="info-label">Packing Date</div>
            <div class="info-value">{{ formatDate(packingList.packing_date) }}</div>
          </div>
          <div class="info-card">
            <div class="info-label">Status</div>
            <div class="info-value">
              <span class="badge" :class="getStatusClass(packingList.status)">
                {{ packingList.status }}
              </span>
            </div>
          </div>
          <div class="info-card" v-if="packingList.packed_by">
            <div class="info-label">Packed By</div>
            <div class="info-value">{{ packingList.packed_by }}</div>
          </div>
          <div class="info-card" v-if="packingList.checked_by">
            <div class="info-label">Checked By</div>
            <div class="info-value">{{ packingList.checked_by }}</div>
          </div>
          <div class="info-card">
            <div class="info-label">Number of Packages</div>
            <div class="info-value">{{ packingList.number_of_packages || 0 }}</div>
          </div>
          <div class="info-card">
            <div class="info-label">Total Weight</div>
            <div class="info-value">{{ formatWeight(packingList.total_weight) }}</div>
          </div>
          <div class="info-card">
            <div class="info-label">Total Volume</div>
            <div class="info-value">{{ formatVolume(packingList.total_volume) }}</div>
          </div>
        </div>

        <div class="notes-section" v-if="packingList.notes">
          <div class="info-label">Notes</div>
          <div class="notes-content">{{ packingList.notes }}</div>
        </div>
      </div>

      <!-- Delivery Information -->
      <div class="info-section" v-if="packingList.delivery">
        <h2 class="section-title">
          <i class="fas fa-truck"></i>
          Delivery Information
        </h2>

        <div class="info-grid">
          <div class="info-card">
            <div class="info-label">Delivery Number</div>
            <div class="info-value">
              <a :href="`/deliveries/${packingList.delivery.delivery_id}`" class="link">
                {{ packingList.delivery.delivery_number }}
              </a>
            </div>
          </div>
          <div class="info-card">
            <div class="info-label">Delivery Date</div>
            <div class="info-value">{{ formatDate(packingList.delivery.delivery_date) }}</div>
          </div>
          <div class="info-card">
            <div class="info-label">Delivery Status</div>
            <div class="info-value">
              <span class="badge badge-info">{{ packingList.delivery.status }}</span>
            </div>
          </div>
          <div class="info-card" v-if="packingList.delivery.sales_order">
            <div class="info-label">Sales Order</div>
            <div class="info-value">
              <a :href="`/sales-orders/${packingList.delivery.sales_order.so_id}`" class="link">
                {{ packingList.delivery.sales_order.so_number }}
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Customer Information -->
      <div class="info-section" v-if="packingList.customer">
        <h2 class="section-title">
          <i class="fas fa-user"></i>
          Customer Information
        </h2>

        <div class="customer-card">
          <div class="customer-avatar">
            <i class="fas fa-building"></i>
          </div>
          <div class="customer-details">
            <h3>{{ packingList.customer.name }}</h3>
            <p class="customer-code">{{ packingList.customer.customer_code }}</p>
            <div class="customer-contact" v-if="packingList.customer.email">
              <i class="fas fa-envelope"></i>
              <span>{{ packingList.customer.email }}</span>
            </div>
            <div class="customer-contact" v-if="packingList.customer.phone">
              <i class="fas fa-phone"></i>
              <span>{{ packingList.customer.phone }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Summary Cards -->
      <div class="summary-section">
        <h2 class="section-title">
          <i class="fas fa-chart-bar"></i>
          Summary
        </h2>

        <div class="summary-grid">
          <div class="summary-card">
            <div class="summary-icon items">
              <i class="fas fa-boxes"></i>
            </div>
            <div class="summary-content">
              <div class="summary-value">{{ packingList.packing_list_lines?.length || 0 }}</div>
              <div class="summary-label">Items</div>
            </div>
          </div>

          <div class="summary-card">
            <div class="summary-icon packages">
              <i class="fas fa-archive"></i>
            </div>
            <div class="summary-content">
              <div class="summary-value">{{ packingList.number_of_packages || 0 }}</div>
              <div class="summary-label">Packages</div>
            </div>
          </div>

          <div class="summary-card">
            <div class="summary-icon weight">
              <i class="fas fa-weight-hanging"></i>
            </div>
            <div class="summary-content">
              <div class="summary-value">{{ formatWeight(packingList.total_weight) }}</div>
              <div class="summary-label">Total Weight</div>
            </div>
          </div>

          <div class="summary-card">
            <div class="summary-icon volume">
              <i class="fas fa-cube"></i>
            </div>
            <div class="summary-content">
              <div class="summary-value">{{ formatVolume(packingList.total_volume) }}</div>
              <div class="summary-label">Total Volume</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Packing Items -->
      <div class="items-section">
        <h2 class="section-title">
          <i class="fas fa-list"></i>
          Packed Items
        </h2>

        <div class="items-table">
          <div class="table-header">
            <div class="col-item">Item</div>
            <div class="col-delivered">Delivered</div>
            <div class="col-packed">Packed</div>
            <div class="col-package">Package</div>
            <div class="col-weight">Weight</div>
            <div class="col-volume">Volume</div>
            <div class="col-status">Status</div>
          </div>

          <div class="table-body">
            <div
              v-for="item in packingList.packing_list_lines"
              :key="item.line_id"
              class="table-row"
            >
              <div class="col-item">
                <div class="item-info">
                  <h4>{{ item.item?.name }}</h4>
                  <p class="item-code">{{ item.item?.item_code }}</p>
                  <p class="warehouse-info" v-if="item.warehouse">
                    <i class="fas fa-warehouse"></i>
                    {{ item.warehouse.name }}
                  </p>
                  <p class="batch-info" v-if="item.batch_number">
                    <i class="fas fa-barcode"></i>
                    Batch: {{ item.batch_number }}
                  </p>
                </div>
              </div>

              <div class="col-delivered">
                <div class="quantity-display">
                  <span class="quantity">{{ item.delivery_line?.delivered_quantity || 0 }}</span>
                  <span class="uom">{{ item.item?.unit_of_measure?.symbol || '' }}</span>
                </div>
              </div>

              <div class="col-packed">
                <div class="quantity-display packed">
                  <span class="quantity">{{ item.packed_quantity || 0 }}</span>
                  <span class="uom">{{ item.item?.unit_of_measure?.symbol || '' }}</span>
                </div>
              </div>

              <div class="col-package">
                <div class="package-info">
                  <div class="package-number">#{{ item.package_number || 1 }}</div>
                  <div class="package-type">{{ item.package_type || 'Box' }}</div>
                </div>
              </div>

              <div class="col-weight">
                <div class="metric-display">
                  <span class="value">{{ formatNumber((item.packed_quantity || 0) * (item.weight_per_unit || 0)) }}</span>
                  <span class="unit">kg</span>
                </div>
              </div>

              <div class="col-volume">
                <div class="metric-display">
                  <span class="value">{{ formatNumber((item.packed_quantity || 0) * (item.volume_per_unit || 0), 3) }}</span>
                  <span class="unit">m³</span>
                </div>
              </div>

              <div class="col-status">
                <span class="badge" :class="getItemStatusClass(item)">
                  {{ getItemStatus(item) }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Activity Timeline -->
      <div class="timeline-section">
        <h2 class="section-title">
          <i class="fas fa-history"></i>
          Activity Timeline
        </h2>

        <div class="timeline">
          <div class="timeline-item">
            <div class="timeline-marker created">
              <i class="fas fa-plus"></i>
            </div>
            <div class="timeline-content">
              <h4>Packing List Created</h4>
              <p>{{ formatDateTime(packingList.created_at) }}</p>
              <small v-if="packingList.packed_by">by {{ packingList.packed_by }}</small>
            </div>
          </div>

          <div class="timeline-item" v-if="packingList.status !== 'Draft'">
            <div class="timeline-marker progress">
              <i class="fas fa-tasks"></i>
            </div>
            <div class="timeline-content">
              <h4>Packing Started</h4>
              <p>Packing process initiated</p>
            </div>
          </div>

          <div class="timeline-item" v-if="packingList.status === 'Completed' || packingList.status === 'Shipped'">
            <div class="timeline-marker completed">
              <i class="fas fa-check"></i>
            </div>
            <div class="timeline-content">
              <h4>Packing Completed</h4>
              <p>All items packed and verified</p>
              <small v-if="packingList.checked_by">Checked by {{ packingList.checked_by }}</small>
            </div>
          </div>

          <div class="timeline-item" v-if="packingList.status === 'Shipped'">
            <div class="timeline-marker shipped">
              <i class="fas fa-shipping-fast"></i>
            </div>
            <div class="timeline-content">
              <h4>Shipped</h4>
              <p>Package sent to customer</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'
import { useRoute, useRouter } from 'vue-router'

export default {
  name: 'PackingListDetail',
  setup() {
    const route = useRoute()
    const router = useRouter()

    const loading = ref(false)
    const packingList = reactive({})
    const showActionsDropdown = ref(false)

    // Computed properties
    const packingProgress = computed(() => {
      if (!packingList.packing_list_lines?.length) return 0

      const totalExpected = packingList.packing_list_lines.reduce((total, item) => {
        return total + (item.delivery_line?.delivered_quantity || 0)
      }, 0)

      const totalPacked = packingList.packing_list_lines.reduce((total, item) => {
        return total + (item.packed_quantity || 0)
      }, 0)

      return totalExpected > 0 ? (totalPacked / totalExpected) * 100 : 0
    })

    // Lifecycle
    onMounted(() => {
      loadPackingList()
    })

    // Methods
    const loadPackingList = async () => {
      loading.value = true
      try {
        const response = await axios.get(`/sales/packing-lists/${route.params.id}`)
        Object.assign(packingList, response.data.data)
      } catch (error) {
        console.error('Error loading packing list:', error)
        alert('Error loading packing list')
      } finally {
        loading.value = false
      }
    }

    const completePacking = async () => {
      const checkedBy = prompt('Enter checker name:')
      if (!checkedBy) return

      try {
        await axios.put(`/sales/packing-lists/${packingList.packing_list_id}/complete`, {
          checked_by: checkedBy
        })
        alert('Packing completed successfully!')
        loadPackingList()
      } catch (error) {
        console.error('Error completing packing:', error)
        alert('Error completing packing: ' + (error.response?.data?.message || error.message))
      }
    }

    const markAsShipped = async () => {
      if (!confirm('Mark this packing list as shipped?')) return

      try {
        await axios.put(`/sales/packing-lists/${packingList.packing_list_id}/ship`)
        alert('Packing list marked as shipped!')
        loadPackingList()
      } catch (error) {
        console.error('Error marking as shipped:', error)
        alert('Error marking as shipped: ' + (error.response?.data?.message || error.message))
      }
    }

    const deletePackingList = async () => {
      if (!confirm(`Delete packing list ${packingList.packing_list_number}?`)) return

      try {
        await axios.delete(`/sales/packing-lists/${packingList.packing_list_id}`)
        alert('Packing list deleted successfully!')
        goBack()
      } catch (error) {
        console.error('Error deleting packing list:', error)
        alert('Error deleting packing list: ' + (error.response?.data?.message || error.message))
      }
    }

    const editPackingList = () => {
      router.push(`/sales/packinglist/${packingList.packing_list_id}/edit`)
    }

    const printPackingList = () => {
      window.open(`/sales/packing-lists/${packingList.packing_list_id}/print`, '_blank')
    }

    const goBack = () => {
      router.push('/packing-lists')
    }

    // Utility methods
    const getStatusClass = (status) => {
      const classes = {
        'Draft': 'badge-secondary',
        'In Progress': 'badge-warning',
        'Completed': 'badge-success',
        'Shipped': 'badge-primary'
      }
      return classes[status] || 'badge-secondary'
    }

    const getStatusBannerClass = (status) => {
      const classes = {
        'Draft': 'status-draft',
        'In Progress': 'status-progress',
        'Completed': 'status-completed',
        'Shipped': 'status-shipped'
      }
      return classes[status] || 'status-draft'
    }

    const getStatusIcon = (status) => {
      const icons = {
        'Draft': 'fa-edit',
        'In Progress': 'fa-tasks',
        'Completed': 'fa-check-circle',
        'Shipped': 'fa-shipping-fast'
      }
      return icons[status] || 'fa-edit'
    }

    const getStatusDescription = (status) => {
      const descriptions = {
        'Draft': 'Packing list is in draft mode and ready for packing',
        'In Progress': 'Packing process is currently underway',
        'Completed': 'All items have been packed and verified',
        'Shipped': 'Package has been shipped to customer'
      }
      return descriptions[status] || ''
    }

    const getItemStatus = (item) => {
      const delivered = item.delivery_line?.delivered_quantity || 0
      const packed = item.packed_quantity || 0

      if (packed === 0) return 'Not Packed'
      if (packed >= delivered) return 'Fully Packed'
      return 'Partially Packed'
    }

    const getItemStatusClass = (item) => {
      const status = getItemStatus(item)
      const classes = {
        'Not Packed': 'badge-secondary',
        'Partially Packed': 'badge-warning',
        'Fully Packed': 'badge-success'
      }
      return classes[status] || 'badge-secondary'
    }

    const formatDate = (date) => {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    }

    const formatDateTime = (datetime) => {
      if (!datetime) return 'N/A'
      return new Date(datetime).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    const formatWeight = (weight) => {
      return weight ? `${weight.toFixed(2)} kg` : '0 kg'
    }

    const formatVolume = (volume) => {
      return volume ? `${volume.toFixed(3)} m³` : '0 m³'
    }

    const formatNumber = (number, decimals = 2) => {
      return number ? number.toFixed(decimals) : '0'
    }

    return {
      loading,
      packingList,
      showActionsDropdown,
      packingProgress,
      completePacking,
      markAsShipped,
      deletePackingList,
      editPackingList,
      printPackingList,
      goBack,
      getStatusClass,
      getStatusBannerClass,
      getStatusIcon,
      getStatusDescription,
      getItemStatus,
      getItemStatusClass,
      formatDate,
      formatDateTime,
      formatWeight,
      formatVolume,
      formatNumber
    }
  }
}
</script>

<style scoped>
.packing-list-detail {
  padding: 24px;
  background: #f8fafc;
  min-height: 100vh;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
  background: white;
  padding: 24px;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.btn-back {
  width: 40px;
  height: 40px;
  border: none;
  background: #f3f4f6;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-back:hover {
  background: #e5e7eb;
}

.page-title {
  margin: 0;
  font-size: 28px;
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
  margin: 4px 0 0 0;
  color: #6b7280;
  font-size: 14px;
}

.header-actions {
  display: flex;
  gap: 12px;
  align-items: center;
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

.text-red { color: #ef4444; }

.detail-container {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.status-banner {
  padding: 24px;
  border-radius: 12px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: white;
}

.status-banner.status-draft { background: linear-gradient(135deg, #6b7280, #4b5563); }
.status-banner.status-progress { background: linear-gradient(135deg, #f59e0b, #d97706); }
.status-banner.status-completed { background: linear-gradient(135deg, #10b981, #059669); }
.status-banner.status-shipped { background: linear-gradient(135deg, #3b82f6, #2563eb); }

.status-content {
  display: flex;
  align-items: center;
  gap: 16px;
}

.status-content i {
  font-size: 32px;
}

.status-content h3 {
  margin: 0;
  font-size: 24px;
  font-weight: 700;
}

.status-content p {
  margin: 4px 0 0 0;
  opacity: 0.9;
}

.status-progress {
  text-align: right;
}

.progress-bar {
  width: 200px;
  height: 8px;
  background: rgba(255, 255, 255, 0.3);
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 8px;
}

.progress-fill {
  height: 100%;
  background: white;
  border-radius: 4px;
  transition: width 0.3s ease;
}

.progress-text {
  font-size: 14px;
  opacity: 0.9;
}

.info-section, .summary-section, .items-section, .timeline-section {
  background: white;
  padding: 24px;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.section-title {
  margin: 0 0 24px 0;
  font-size: 20px;
  font-weight: 600;
  color: #1f2937;
  display: flex;
  align-items: center;
  gap: 12px;
}

.section-title i {
  color: #3b82f6;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
}

.info-card {
  padding: 16px;
  background: #f9fafb;
  border-radius: 8px;
  border-left: 4px solid #3b82f6;
}

.info-label {
  font-size: 12px;
  color: #6b7280;
  margin-bottom: 8px;
  font-weight: 600;
  text-transform: uppercase;
}

.info-value {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
}

.link {
  color: #3b82f6;
  text-decoration: none;
}

.link:hover {
  text-decoration: underline;
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
.badge-info { background: #e0f2fe; color: #0277bd; }

.notes-section {
  margin-top: 20px;
}

.notes-content {
  padding: 16px;
  background: #f9fafb;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  font-style: italic;
  color: #6b7280;
}

.customer-card {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 20px;
  background: #f9fafb;
  border-radius: 12px;
}

.customer-avatar {
  width: 60px;
  height: 60px;
  background: #3b82f6;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 24px;
}

.customer-details h3 {
  margin: 0 0 4px 0;
  font-size: 20px;
  font-weight: 700;
  color: #1f2937;
}

.customer-code {
  margin: 0 0 12px 0;
  color: #6b7280;
  font-size: 14px;
}

.customer-contact {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
  color: #6b7280;
  font-size: 14px;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
}

.summary-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  background: #f9fafb;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
}

.summary-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  color: white;
}

.summary-icon.items { background: #8b5cf6; }
.summary-icon.packages { background: #3b82f6; }
.summary-icon.weight { background: #10b981; }
.summary-icon.volume { background: #f59e0b; }

.summary-value {
  font-size: 20px;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 4px;
}

.summary-label {
  font-size: 12px;
  color: #6b7280;
  font-weight: 600;
  text-transform: uppercase;
}

.items-table {
  overflow-x: auto;
}

.table-header {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr 1fr;
  gap: 16px;
  padding: 16px;
  background: #f9fafb;
  border-radius: 8px;
  font-weight: 600;
  color: #374151;
  font-size: 12px;
  text-transform: uppercase;
}

.table-body {
  display: flex;
  flex-direction: column;
  gap: 1px;
  margin-top: 1px;
}

.table-row {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr 1fr;
  gap: 16px;
  padding: 20px 16px;
  background: white;
  border: 1px solid #f3f4f6;
  align-items: center;
}

.table-row:hover {
  background: #f9fafb;
}

.item-info h4 {
  margin: 0 0 4px 0;
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
}

.item-code {
  margin: 0 0 8px 0;
  color: #6b7280;
  font-size: 12px;
}

.warehouse-info, .batch-info {
  margin: 0;
  color: #6b7280;
  font-size: 11px;
  display: flex;
  align-items: center;
  gap: 4px;
}

.quantity-display {
  text-align: center;
}

.quantity-display .quantity {
  display: block;
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
}

.quantity-display.packed .quantity {
  color: #059669;
}

.quantity-display .uom {
  display: block;
  font-size: 11px;
  color: #6b7280;
  margin-top: 2px;
}

.package-info {
  text-align: center;
}

.package-number {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 2px;
}

.package-type {
  font-size: 11px;
  color: #6b7280;
}

.metric-display {
  text-align: center;
}

.metric-display .value {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
}

.metric-display .unit {
  display: block;
  font-size: 11px;
  color: #6b7280;
  margin-top: 2px;
}

.timeline {
  position: relative;
}

.timeline::before {
  content: '';
  position: absolute;
  left: 20px;
  top: 0;
  bottom: 0;
  width: 2px;
  background: #e5e7eb;
}

.timeline-item {
  display: flex;
  align-items: flex-start;
  gap: 20px;
  margin-bottom: 24px;
  position: relative;
}

.timeline-marker {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 14px;
  z-index: 1;
}

.timeline-marker.created { background: #6b7280; }
.timeline-marker.progress { background: #f59e0b; }
.timeline-marker.completed { background: #10b981; }
.timeline-marker.shipped { background: #3b82f6; }

.timeline-content h4 {
  margin: 0 0 4px 0;
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
}

.timeline-content p {
  margin: 0 0 4px 0;
  color: #6b7280;
}

.timeline-content small {
  color: #9ca3af;
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

.btn-warning {
  background: #f59e0b;
  color: white;
}

.btn-warning:hover {
  background: #d97706;
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

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    gap: 16px;
    align-items: flex-start;
  }

  .header-actions {
    width: 100%;
    justify-content: stretch;
  }

  .header-actions .btn {
    flex: 1;
  }

  .status-banner {
    flex-direction: column;
    gap: 16px;
    text-align: center;
  }

  .progress-bar {
    width: 100%;
  }

  .info-grid {
    grid-template-columns: 1fr;
  }

  .summary-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .table-header, .table-row {
    grid-template-columns: 1fr;
    gap: 8px;
  }

  .table-header > div, .table-row > div {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .table-header > div::before {
    content: attr(data-label);
    font-weight: 600;
  }
}
</style>
