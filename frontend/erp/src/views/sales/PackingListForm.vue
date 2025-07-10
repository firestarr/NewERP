<template>
  <div class="packing-list-form">
    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <button class="btn-back" @click="goBack">
          <i class="fas fa-arrow-left"></i>
        </button>
        <div>
          <h1 class="page-title">
            <i class="fas fa-boxes"></i>
            {{ isEditMode ? 'Edit Packing List' : 'Create Packing List' }}
          </h1>
          <p class="page-subtitle" v-if="packingList.packing_list_number">
            {{ packingList.packing_list_number }}
          </p>
        </div>
      </div>
      <div class="header-actions">
        <button class="btn btn-secondary" @click="goBack">Cancel</button>
        <button class="btn btn-primary" @click="savePackingList" :disabled="saving">
          <i class="fas fa-save"></i>
          {{ saving ? 'Saving...' : 'Save' }}
        </button>
      </div>
    </div>

    <div class="form-container">
      <!-- Main Form -->
      <div class="form-section">
        <h2 class="section-title">
          <i class="fas fa-info-circle"></i>
          Basic Information
        </h2>

        <div class="form-grid">
          <div class="form-group" v-if="!isEditMode">
            <label class="required">Delivery Order</label>
            <select v-model="packingList.delivery_id" @change="loadDeliveryDetails" class="form-control" :disabled="isEditMode">
              <option value="">Select delivery order...</option>
              <option v-for="delivery in availableDeliveries" :key="delivery.delivery_id" :value="delivery.delivery_id">
                {{ delivery.delivery_number }} - {{ delivery.customer?.name }}
              </option>
            </select>
            <div class="field-error" v-if="errors.delivery_id">{{ errors.delivery_id }}</div>
          </div>

          <div class="form-group">
            <label class="required">Packing Date</label>
            <input type="date" v-model="packingList.packing_date" class="form-control">
            <div class="field-error" v-if="errors.packing_date">{{ errors.packing_date }}</div>
          </div>

          <div class="form-group">
            <label>Packed By</label>
            <input type="text" v-model="packingList.packed_by" class="form-control" placeholder="Enter packer name">
          </div>

          <div class="form-group">
            <label>Checked By</label>
            <input type="text" v-model="packingList.checked_by" class="form-control" placeholder="Enter checker name">
          </div>
        </div>

        <div class="form-group">
          <label>Notes</label>
          <textarea v-model="packingList.notes" class="form-control" rows="3" placeholder="Additional notes..."></textarea>
        </div>
      </div>

      <!-- Delivery Information (if selected) -->
      <div class="info-section" v-if="deliveryInfo">
        <h2 class="section-title">
          <i class="fas fa-truck"></i>
          Delivery Information
        </h2>

        <div class="info-grid">
          <div class="info-card">
            <div class="info-label">Delivery Number</div>
            <div class="info-value">{{ deliveryInfo.delivery_number }}</div>
          </div>
          <div class="info-card">
            <div class="info-label">Customer</div>
            <div class="info-value">{{ deliveryInfo.customer?.name }}</div>
          </div>
          <div class="info-card">
            <div class="info-label">SO Number</div>
            <div class="info-value">{{ deliveryInfo.sales_order?.so_number }}</div>
          </div>
          <div class="info-card">
            <div class="info-label">Delivery Date</div>
            <div class="info-value">{{ formatDate(deliveryInfo.delivery_date) }}</div>
          </div>
        </div>
      </div>

      <!-- Packing Items -->
      <div class="items-section" v-if="packingItems.length > 0">
        <div class="section-header">
          <h2 class="section-title">
            <i class="fas fa-boxes"></i>
            Items to Pack
          </h2>
          <div class="section-summary">
            <span class="summary-item">
              <strong>{{ packingItems.length }}</strong> items
            </span>
            <span class="summary-item">
              <strong>{{ totalPackages }}</strong> packages
            </span>
            <span class="summary-item">
              <strong>{{ totalWeight.toFixed(2)}}</strong> kg
            </span>
          </div>
        </div>

        <div class="items-list">
          <div v-for="(item, index) in packingItems" :key="item.line_id || index" class="item-card">
            <div class="item-header">
              <div class="item-info">
                <h4>{{ item.item?.name || item.item_name }}</h4>
                <p class="item-code">{{ item.item?.item_code || item.item_code }}</p>
              </div>
              <div class="item-status">
                <span class="badge" :class="getPackingStatusClass(item)">
                  {{ getPackingStatus(item) }}
                </span>
              </div>
            </div>

            <div class="item-body">
              <div class="quantity-info">
                <div class="quantity-item">
                  <label>Delivered Qty</label>
                  <span class="quantity-value">{{ item.delivered_quantity || item.quantity }} {{ item.uom_symbol }}</span>
                </div>
                <div class="quantity-item">
                  <label>Packed Qty</label>
                  <input
                    type="number"
                    v-model.number="item.packed_quantity"
                    @input="updateCalculations"
                    :max="item.delivered_quantity || item.quantity"
                    min="0"
                    step="0.01"
                    class="form-control quantity-input"
                  >
                </div>
                <div class="quantity-item">
                  <label>Remaining</label>
                  <span class="quantity-value remaining">
                    {{ (item.delivered_quantity || item.quantity) - (item.packed_quantity || 0) }}
                  </span>
                </div>
              </div>

              <div class="packing-details">
                <div class="detail-row">
                  <div class="detail-group">
                    <label>Package Number</label>
                    <input type="number" v-model.number="item.package_number" min="1" class="form-control">
                  </div>
                  <div class="detail-group">
                    <label>Package Type</label>
                    <select v-model="item.package_type" class="form-control">
                      <option value="Box">Box</option>
                      <option value="Carton">Carton</option>
                      <option value="Pallet">Pallet</option>
                      <option value="Bag">Bag</option>
                      <option value="Bundle">Bundle</option>
                      <option value="Other">Other</option>
                    </select>
                  </div>
                </div>

                <div class="detail-row">
                  <div class="detail-group">
                    <label>Weight per Unit (kg)</label>
                    <input
                      type="number"
                      v-model.number="item.weight_per_unit"
                      @input="updateCalculations"
                      min="0"
                      step="0.001"
                      class="form-control"
                    >
                  </div>
                  <div class="detail-group">
                    <label>Volume per Unit (m³)</label>
                    <input
                      type="number"
                      v-model.number="item.volume_per_unit"
                      @input="updateCalculations"
                      min="0"
                      step="0.001"
                      class="form-control"
                    >
                  </div>
                </div>

                <div class="detail-row">
                  <div class="detail-group full-width">
                    <label>Notes</label>
                    <input type="text" v-model="item.notes" class="form-control" placeholder="Additional notes for this item">
                  </div>
                </div>

                <div class="calculations">
                  <div class="calc-item">
                    <span class="calc-label">Total Weight:</span>
                    <span class="calc-value">{{ ((item.packed_quantity || 0) * (item.weight_per_unit || 0)).toFixed(3) }} kg</span>
                  </div>
                  <div class="calc-item">
                    <span class="calc-label">Total Volume:</span>
                    <span class="calc-value">{{ ((item.packed_quantity || 0) * (item.volume_per_unit || 0)).toFixed(3) }} m³</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Summary -->
      <div class="summary-section" v-if="packingItems.length > 0">
        <h2 class="section-title">
          <i class="fas fa-calculator"></i>
          Summary
        </h2>

        <div class="summary-grid">
          <div class="summary-card">
            <div class="summary-icon packages">
              <i class="fas fa-boxes"></i>
            </div>
            <div class="summary-content">
              <div class="summary-value">{{ totalPackages }}</div>
              <div class="summary-label">Total Packages</div>
            </div>
          </div>

          <div class="summary-card">
            <div class="summary-icon weight">
              <i class="fas fa-weight-hanging"></i>
            </div>
            <div class="summary-content">
              <div class="summary-value">{{ totalWeight.toFixed(2) }} kg</div>
              <div class="summary-label">Total Weight</div>
            </div>
          </div>

          <div class="summary-card">
            <div class="summary-icon volume">
              <i class="fas fa-cube"></i>
            </div>
            <div class="summary-content">
              <div class="summary-value">{{ totalVolume.toFixed(3) }} m³</div>
              <div class="summary-label">Total Volume</div>
            </div>
          </div>

          <div class="summary-card">
            <div class="summary-icon progress">
              <i class="fas fa-chart-pie"></i>
            </div>
            <div class="summary-content">
              <div class="summary-value">{{ packingProgress.toFixed(1) }}%</div>
              <div class="summary-label">Packing Progress</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-content">
        <div class="spinner"></div>
        <p>Loading...</p>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'
import { useRoute, useRouter } from 'vue-router'

export default {
  name: 'PackingListForm',
  setup() {
    const route = useRoute()
    const router = useRouter()

    const loading = ref(false)
    const saving = ref(false)
    const isEditMode = computed(() => !!route.params.id)

    const packingList = reactive({
      packing_list_id: null,
      packing_list_number: '',
      delivery_id: '',
      customer_id: '',
      packing_date: new Date().toISOString().split('T')[0],
      packed_by: '',
      checked_by: '',
      status: 'Draft',
      notes: ''
    })

    const packingItems = ref([])
    const availableDeliveries = ref([])
    const deliveryInfo = ref(null)
    const errors = reactive({})

    // Computed properties
    const totalPackages = computed(() => {
      const packages = packingItems.value.map(item => item.package_number || 1)
      return Math.max(...packages, 0)
    })

    const totalWeight = computed(() => {
      return packingItems.value.reduce((total, item) => {
        return total + ((item.packed_quantity || 0) * (item.weight_per_unit || 0))
      }, 0)
    })

    const totalVolume = computed(() => {
      return packingItems.value.reduce((total, item) => {
        return total + ((item.packed_quantity || 0) * (item.volume_per_unit || 0))
      }, 0)
    })

    const packingProgress = computed(() => {
      if (packingItems.value.length === 0) return 0

      const totalExpected = packingItems.value.reduce((total, item) => {
        return total + (item.delivered_quantity || item.quantity || 0)
      }, 0)

      const totalPacked = packingItems.value.reduce((total, item) => {
        return total + (item.packed_quantity || 0)
      }, 0)

      return totalExpected > 0 ? (totalPacked / totalExpected) * 100 : 0
    })

    // Lifecycle
    onMounted(() => {
      if (isEditMode.value) {
        loadPackingList()
      } else {
        loadAvailableDeliveries()
      }
    })

    // Methods
    const loadPackingList = async () => {
      loading.value = true
      try {
        const response = await axios.get(`/sales/packing-lists/${route.params.id}`)
        const data = response.data.data

        // Format packing_date to yyyy-MM-dd
        if (data.packing_date) {
          data.packing_date = data.packing_date.split('T')[0]
        }

        Object.assign(packingList, data)
        packingItems.value = data.packing_list_lines || []
        deliveryInfo.value = data.delivery

      } catch (error) {
        console.error('Error loading packing list:', error)
        alert('Error loading packing list')
      } finally {
        loading.value = false
      }
    }

    const loadAvailableDeliveries = async () => {
      try {
        const response = await axios.get('/sales/packing-lists-available-deliveries')
        availableDeliveries.value = response.data.data
      } catch (error) {
        console.error('Error loading deliveries:', error)
      }
    }

    const loadDeliveryDetails = async () => {
      if (!packingList.delivery_id) return

      try {
        const response = await axios.get(`/sales/deliveries/${packingList.delivery_id}/items-for-packing`)
        deliveryInfo.value = response.data.delivery
        packingList.customer_id = deliveryInfo.value.customer_id

        // Initialize packing items
        packingItems.value = response.data.items.map(item => ({
          delivery_line_id: item.delivery_line_id,
          item_id: item.item_id,
          item_code: item.item_code,
          item_name: item.item_name,
          delivered_quantity: item.delivered_quantity,
          uom_symbol: item.uom_symbol,
          warehouse_name: item.warehouse_name,
          batch_number: item.batch_number,
          packed_quantity: 0,
          package_number: 1,
          package_type: 'Box',
          weight_per_unit: item.suggested_weight || 0,
          volume_per_unit: item.suggested_volume || 0,
          notes: ''
        }))

      } catch (error) {
        console.error('Error loading delivery details:', error)
        alert('Error loading delivery details')
      }
    }

    const savePackingList = async () => {
      if (!validateForm()) return

      saving.value = true
      try {
        const payload = {
          ...packingList,
          packing_lines: packingItems.value.map(item => ({
            line_id: item.line_id,
            delivery_line_id: item.delivery_line_id,
            item_id: item.item_id,
            packed_quantity: item.packed_quantity || 0,
            warehouse_id: item.warehouse_id,
            batch_number: item.batch_number,
            package_number: item.package_number || 1,
            package_type: item.package_type || 'Box',
            weight_per_unit: item.weight_per_unit || 0,
            volume_per_unit: item.volume_per_unit || 0,
            notes: item.notes
          }))
        }

        if (isEditMode.value) {
          await axios.put(`/sales/packing-lists/${route.params.id}`, payload)
          alert('Packing list updated successfully!')
        } else {
            await axios.post('/sales/packing-lists', payload)
          alert('Packing list created successfully!')
        }

        goBack()

      } catch (error) {
        console.error('Error saving packing list:', error)
        if (error.response?.data?.errors) {
          Object.assign(errors, error.response.data.errors)
        }
        alert('Error saving packing list: ' + (error.response?.data?.message || error.message))
      } finally {
        saving.value = false
      }
    }

    const validateForm = () => {
      Object.keys(errors).forEach(key => delete errors[key])

      if (!packingList.delivery_id && !isEditMode.value) {
        errors.delivery_id = 'Delivery order is required'
      }

      if (!packingList.packing_date) {
        errors.packing_date = 'Packing date is required'
      }

      return Object.keys(errors).length === 0
    }

    const updateCalculations = () => {
      // Trigger reactivity for computed properties
    }

    const getPackingStatus = (item) => {
      const delivered = item.delivered_quantity || item.quantity || 0
      const packed = item.packed_quantity || 0

      if (packed === 0) return 'Not Packed'
      if (packed >= delivered) return 'Fully Packed'
      return 'Partially Packed'
    }

    const getPackingStatusClass = (item) => {
      const status = getPackingStatus(item)
      const classes = {
        'Not Packed': 'badge-secondary',
        'Partially Packed': 'badge-warning',
        'Fully Packed': 'badge-success'
      }
      return classes[status] || 'badge-secondary'
    }

    const formatDate = (date) => {
      return new Date(date).toLocaleDateString()
    }

    const goBack = () => {
      router.push('/packing-lists')
    }

    return {
      loading,
      saving,
      isEditMode,
      packingList,
      packingItems,
      availableDeliveries,
      deliveryInfo,
      errors,
      totalPackages,
      totalWeight,
      totalVolume,
      packingProgress,
      loadDeliveryDetails,
      savePackingList,
      updateCalculations,
      getPackingStatus,
      getPackingStatusClass,
      formatDate,
      goBack
    }
  }
}
</script>

<style scoped>
.packing-list-form {
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
}

.form-container {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.form-section, .info-section, .items-section, .summary-section {
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

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.section-summary {
  display: flex;
  gap: 24px;
  font-size: 14px;
}

.summary-item {
  color: #6b7280;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  margin-bottom: 8px;
  font-weight: 600;
  color: #374151;
}

.required::after {
  content: ' *';
  color: #ef4444;
}

.form-control {
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

.field-error {
  margin-top: 4px;
  color: #ef4444;
  font-size: 12px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}

.info-card {
  padding: 16px;
  background: #f9fafb;
  border-radius: 8px;
}

.info-label {
  font-size: 12px;
  color: #6b7280;
  margin-bottom: 4px;
}

.info-value {
  font-weight: 600;
  color: #1f2937;
}

.items-list {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.item-card {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  overflow: hidden;
}

.item-header {
  padding: 16px;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.item-info h4 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
}

.item-code {
  margin: 4px 0 0 0;
  color: #6b7280;
  font-size: 14px;
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

.item-body {
  padding: 20px;
}

.quantity-info {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  margin-bottom: 20px;
  padding: 16px;
  background: #f9fafb;
  border-radius: 8px;
}

.quantity-item {
  text-align: center;
}

.quantity-item label {
  display: block;
  font-size: 12px;
  color: #6b7280;
  margin-bottom: 8px;
}

.quantity-value {
  font-size: 18px;
  font-weight: 600;
  color: #1f2937;
}

.quantity-value.remaining {
  color: #059669;
}

.quantity-input {
  text-align: center;
  font-weight: 600;
}

.packing-details {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.detail-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}

.detail-group {
  display: flex;
  flex-direction: column;
}

.detail-group.full-width {
  grid-column: 1 / -1;
}

.detail-group label {
  margin-bottom: 8px;
  font-size: 14px;
  font-weight: 600;
  color: #374151;
}

.calculations {
  display: flex;
  justify-content: space-around;
  padding: 16px;
  background: #f0f9ff;
  border-radius: 8px;
  margin-top: 16px;
}

.calc-item {
  text-align: center;
}

.calc-label {
  display: block;
  font-size: 12px;
  color: #0369a1;
  margin-bottom: 4px;
}

.calc-value {
  display: block;
  font-size: 16px;
  font-weight: 600;
  color: #1e40af;
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

.summary-icon.packages { background: #3b82f6; }
.summary-icon.weight { background: #10b981; }
.summary-icon.volume { background: #f59e0b; }
.summary-icon.progress { background: #8b5cf6; }

.summary-value {
  font-size: 20px;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 4px;
}

.summary-label {
  font-size: 12px;
  color: #6b7280;
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

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #2563eb;
}

.btn-secondary {
  background: #6b7280;
  color: white;
}

.btn-secondary:hover {
  background: #4b5563;
}

.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.loading-content {
  text-align: center;
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

  .form-grid {
    grid-template-columns: 1fr;
  }

  .quantity-info {
    grid-template-columns: 1fr;
  }

  .detail-row {
    grid-template-columns: 1fr;
  }

  .summary-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
