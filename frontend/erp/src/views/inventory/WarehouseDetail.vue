<!-- src/views/inventory/WarehouseDetail.vue -->
<template>
  <div class="warehouse-detail-page">
    <div class="page-header">
      <div class="header-content">
        <div class="header-top">
          <h2 class="page-title">
            Warehouse: {{ warehouse?.name }}
            <span v-if="warehouse?.code" class="warehouse-code">({{ warehouse.code }})</span>
          </h2>
          <div class="header-actions">
            <button class="btn-secondary" @click="editWarehouse" v-if="warehouse">
              <i class="fas fa-edit mr-2"></i> Edit Details
            </button>
            <router-link :to="`/warehouses/${warehouseId}/zones`" class="btn-primary">
              <i class="fas fa-map mr-2"></i> Manage Zones
            </router-link>
          </div>
        </div>

        <div class="warehouse-status" v-if="warehouse">
          <span
            class="status-badge"
            :class="warehouse.is_active ? 'status-active' : 'status-inactive'"
          >
            {{ warehouse.is_active ? 'Active' : 'Inactive' }}
          </span>
        </div>
      </div>

      <div class="breadcrumbs">
        <router-link to="/warehouses" class="breadcrumb-item">
          <i class="fas fa-warehouse mr-1"></i> Warehouses
        </router-link>
        <span class="breadcrumb-separator">/</span>
        <span class="breadcrumb-item active">Details</span>
      </div>
    </div>

    <div v-if="loading" class="loading-indicator">
      <i class="fas fa-spinner fa-spin mr-2"></i> Loading warehouse details...
    </div>

    <div v-else-if="error" class="error-message">
      <i class="fas fa-exclamation-triangle mr-2"></i>
      {{ error }}
    </div>

    <div v-else-if="!warehouse" class="not-found">
      <i class="fas fa-question-circle not-found-icon"></i>
      <h3>Warehouse Not Found</h3>
      <p>The warehouse you're looking for doesn't exist or has been deleted.</p>
      <router-link to="/warehouses" class="btn-primary mt-4">
        Return to Warehouses List
      </router-link>
    </div>

    <div v-else class="warehouse-details-container">
      <div class="details-card details-card-full">
        <div class="card-header">
          <h3 class="card-title">Warehouse Details</h3>
        </div>
        <div class="card-body">
          <div class="details-grid">
            <div class="detail-item">
              <span class="detail-label">Warehouse ID:</span>
              <span class="detail-value">{{ warehouse.warehouse_id }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Code:</span>
              <span class="detail-value">{{ warehouse.code }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Name:</span>
              <span class="detail-value">{{ warehouse.name }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Address:</span>
              <span class="detail-value">
                {{ warehouse.address || 'No address specified' }}
              </span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Status:</span>
              <span
                class="status-badge detail-value"
                :class="warehouse.is_active ? 'status-active' : 'status-inactive'"
              >
                {{ warehouse.is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Created At:</span>
              <span class="detail-value">{{ formatDate(warehouse.created_at) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Updated At:</span>
              <span class="detail-value">{{ formatDate(warehouse.updated_at) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Warehouse Modal -->
    <div v-if="showEditModal" class="modal-backdrop">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Edit Warehouse</h3>
          <button class="btn-close" @click="showEditModal = false">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveWarehouse">
            <div class="form-group">
              <label for="warehouseName">Warehouse Name</label>
              <input
                id="warehouseName"
                v-model="warehouseForm.name"
                type="text"
                class="form-control"
                required
              />
            </div>

            <div class="form-group">
              <label for="warehouseCode">Warehouse Code</label>
              <input
                id="warehouseCode"
                v-model="warehouseForm.code"
                type="text"
                class="form-control"
                required
              />
              <small class="form-text text-muted">
                A unique code to identify this warehouse
              </small>
            </div>

            <div class="form-group">
              <label for="warehouseAddress">Address</label>
              <textarea
                id="warehouseAddress"
                v-model="warehouseForm.address"
                class="form-control"
                rows="3"
              ></textarea>
            </div>

            <div class="form-group">
              <label class="checkbox-label">
                <input
                  type="checkbox"
                  v-model="warehouseForm.is_active"
                />
                Active
              </label>
              <small class="form-text text-muted">
                Inactive warehouses won't be available for new transactions
              </small>
            </div>

            <div class="form-group form-actions">
              <button type="button" class="btn-secondary" @click="showEditModal = false">
                Cancel
              </button>
              <button type="submit" class="btn-primary" :disabled="isSubmitting">
                <i v-if="isSubmitting" class="fas fa-spinner fa-spin mr-2"></i>
                Update Warehouse
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

export default {
  name: 'WarehouseDetail',
  setup() {
    const route = useRoute();
    const warehouseId = computed(() => route.params.id);

    const warehouse = ref(null);
    const loading = ref(true);
    const error = ref(null);

    // Edit warehouse modal state
    const showEditModal = ref(false);
    const isSubmitting = ref(false);
    const warehouseForm = reactive({
      name: '',
      code: '',
      address: '',
      is_active: true
    });

    const fetchWarehouseData = async () => {
      loading.value = true;
      error.value = null;

      try {
        const response = await axios.get(`/warehouses/${warehouseId.value}`);
        warehouse.value = response.data.data;
      } catch (err) {
        console.error('Error fetching warehouse:', err);
        error.value = 'Failed to load warehouse details. Please try again.';
      } finally {
        loading.value = false;
      }
    };

    const editWarehouse = () => {
      warehouseForm.name = warehouse.value.name;
      warehouseForm.code = warehouse.value.code;
      warehouseForm.address = warehouse.value.address || '';
      warehouseForm.is_active = warehouse.value.is_active;
      showEditModal.value = true;
    };

    const saveWarehouse = async () => {
      isSubmitting.value = true;

      try {
        const response = await axios.put(`/warehouses/${warehouseId.value}`, warehouseForm);
        warehouse.value = response.data.data;
        showEditModal.value = false;
      } catch (err) {
        console.error('Error updating warehouse:', err);
        error.value = err.response?.data?.message || 'Failed to update warehouse. Please try again.';
      } finally {
        isSubmitting.value = false;
      }
    };

    const formatDate = (dateString) => {
      if (!dateString) return 'N/A';

      const date = new Date(dateString);
      return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      }).format(date);
    };

    onMounted(async () => {
      await fetchWarehouseData();
    });

    return {
      warehouseId,
      warehouse,
      loading,
      error,
      showEditModal,
      isSubmitting,
      warehouseForm,
      fetchWarehouseData,
      editWarehouse,
      saveWarehouse,
      formatDate
    };
  }
};
</script>

<style scoped>
.warehouse-detail-page {
  padding: 1rem;
}

.page-header {
  margin-bottom: 2rem;
}

.header-content {
  margin-bottom: 1rem;
}

.header-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.page-title {
  margin: 0;
  font-size: 1.5rem;
  color: var(--gray-800);
}

.warehouse-code {
  font-size: 1rem;
  color: var(--gray-600);
  font-weight: normal;
}

.header-actions {
  display: flex;
  gap: 0.75rem;
}

.warehouse-status {
  margin-top: 0.5rem;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
  font-weight: 600;
  border-radius: 9999px;
}

.status-active {
  background-color: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
}

.status-inactive {
  background-color: rgba(220, 38, 38, 0.1);
  color: var(--danger-color);
}

.breadcrumbs {
  display: flex;
  align-items: center;
  font-size: 0.875rem;
}

.breadcrumb-item {
  color: var(--primary-color);
}

.breadcrumb-item.active {
  color: var(--gray-600);
}

.breadcrumb-separator {
  margin: 0 0.5rem;
  color: var(--gray-400);
}

.loading-indicator {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 2rem;
  color: var(--gray-500);
}

.error-message {
  padding: 1rem;
  background-color: rgba(220, 38, 38, 0.1);
  color: var(--danger-color);
  border-radius: 0.375rem;
  margin-bottom: 1rem;
}

.not-found {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  text-align: center;
}

.not-found-icon {
  font-size: 3rem;
  color: var(--gray-400);
  margin-bottom: 1rem;
}

.warehouse-details-container {
  display: flex;
  justify-content: center;
}

.details-card {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.details-card-full {
  width: 100%;
  max-width: 800px;
}

.card-header {
  padding: 1rem;
  border-bottom: 1px solid var(--gray-200);
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color: var(--gray-50);
}

.card-title {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--gray-800);
}

.card-body {
  padding: 2rem;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.detail-label {
  font-weight: 600;
  color: var(--gray-600);
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.detail-value {
  color: var(--gray-800);
  font-size: 1rem;
  padding: 0.75rem;
  background-color: var(--gray-50);
  border-radius: 0.375rem;
  border: 1px solid var(--gray-200);
}

.btn-primary {
  background-color: var(--primary-color);
  color: white;
  border: none;
  border-radius: 0.375rem;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  text-decoration: none;
}

.btn-primary:hover {
  background-color: var(--primary-dark);
  text-decoration: none;
}

.btn-secondary {
  background-color: var(--gray-200);
  color: var(--gray-800);
  border: none;
  border-radius: 0.375rem;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
}

.btn-secondary:hover {
  background-color: var(--gray-300);
}

.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  width: 95%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  padding: 1rem;
  border-bottom: 1px solid var(--gray-200);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--gray-800);
}

.btn-close {
  background: none;
  border: none;
  color: var(--gray-500);
  cursor: pointer;
  font-size: 1.25rem;
  line-height: 1;
  padding: 0.25rem;
}

.btn-close:hover {
  color: var(--gray-800);
}

.modal-body {
  padding: 1rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: var(--gray-700);
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}

.form-control {
  width: 100%;
  padding: 0.5rem 0.75rem;
  font-size: 1rem;
  line-height: 1.5;
  color: var(--gray-900);
  background-color: white;
  border: 1px solid var(--gray-300);
  border-radius: 0.375rem;
}

.form-control:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
}

.form-text {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.75rem;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 1.5rem;
}

.mr-1 {
  margin-right: 0.25rem;
}

.mr-2 {
  margin-right: 0.5rem;
}

.mt-4 {
  margin-top: 1rem;
}

@media (max-width: 768px) {
  .header-top {
    flex-direction: column;
  }

  .header-actions {
    margin-top: 1rem;
  }

  .details-grid {
    grid-template-columns: 1fr;
  }

  .card-body {
    padding: 1rem;
  }
}
</style>
