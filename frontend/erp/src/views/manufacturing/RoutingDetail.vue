<!-- src/views/manufacturing/RoutingDetail.vue -->
<template>
    <div class="routing-detail-container">
      <!-- Header with actions -->
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="page-title">Routing Detail</h1>
        <div class="action-buttons">
          <router-link to="/manufacturing/routings" class="btn btn-secondary mr-2">
            <i class="fas fa-list mr-1"></i> Routing List
          </router-link>
           <router-link :to="`/manufacturing/routings/${routingId}/print`" class="btn btn-info mr-2" target="_blank">
            <i class="fas fa-print mr-1"></i> Print
          </router-link>
          <router-link :to="`/manufacturing/routings/${routingId}/edit`" class="btn btn-primary mr-2">
            <i class="fas fa-edit mr-1"></i> Edit Routing
          </router-link>
          <button @click="confirmDelete" class="btn btn-danger">
            <i class="fas fa-trash-alt mr-1"></i> Delete
          </button>
        </div>
      </div>

      <!-- Loading indicator -->
      <div v-if="isLoading" class="text-center py-5">
        <i class="fas fa-spinner fa-spin fa-2x"></i>
        <p class="mt-2">Loading routing data...</p>
      </div>

      <div v-else>
        <!-- Routing Information Card -->
        <div class="card mb-4">
          <div class="card-header">
            <h2 class="card-title">Routing Information</h2>
          </div>
          <div class="card-body">
            <!-- Main Information Grid -->
            <div class="info-grid-simple">
              <div class="info-row">
                <div class="info-label">Routing Code:</div>
                <div class="info-value">{{ routing.routing_code }}</div>
                <div class="info-label">Product:</div>
                <div class="info-value">
                  {{ routing.item ? `${routing.item.name} (${routing.item.item_code})` : '-' }}
                </div>
              </div>

              <div class="info-row">
                <div class="info-label">Revised:</div>
                <div class="info-value">{{ routing.revision }}</div>
                <div class="info-label">Effective Date:</div>
                <div class="info-value">{{ formatDate(routing.effective_date) }}</div>
              </div>

              <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value">
                  <span
                    class="badge"
                    :class="{
                      'badge-success': routing.status === 'Active',
                      'badge-warning': routing.status === 'Draft',
                      'badge-secondary': routing.status === 'Obsolete'
                    }"
                  >
                    {{ routing.status }}
                  </span>
                </div>
                <div class="info-label">Total Operation:</div>
                <div class="info-value">{{ operations.length }}</div>
              </div>
            </div>

            <!-- Additional Information - Simple Version -->
            <div class="additional-info-simple mt-4">
              <div class="info-row">
                <div class="info-label">Cavity:</div>
                <div class="info-value">{{ routing.cavity || 'N/A' }}</div>
                <div class="info-label">Process:</div>
                <div class="info-value">{{ routing.process || 'N/A' }}</div>
              </div>

              <div class="info-row">
                <div class="info-label">Set Jump:</div>
                <div class="info-value">{{ routing.set_jump || 'N/A' }}</div>
                <div class="info-label">Yield:</div>
                <div class="info-value">
                  <span v-if="routing.yield" class="badge badge-info">
                    {{ routing.yield }}
                  </span>
                  <span v-else class="text-muted">N/A</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Operations Card -->
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="card-title">Routing Operation</h2>
            <button @click="showOperationModal = true" class="btn btn-primary">
              <i class="fas fa-plus mr-1"></i> Add Operation
            </button>
          </div>
          <div class="card-body p-0">
            <DataTable
              :columns="operationColumns"
              :items="sortedOperations"
              :is-loading="isLoadingOperations"
              empty-title="No operations yet"
              empty-message="Add operations for this routing using the 'Add Operation' button"
              initial-sort-key="sequence"
              initial-sort-order="asc"
            >
              <!-- Work Flow column -->
              <template #work_flow="{ value }">
                {{ value || '-' }}
              </template>

              <!-- Models column -->
              <template #models="{ value }">
                {{ value || '-' }}
              </template>

              <!-- Run Time column -->
              <template #run_time="{ value, item }">
                {{ value }} {{ getUnitName(value, item) }}
              </template>

              <!-- Setup Time column -->
              <template #setup_time="{ value, item }">
                {{ value }} {{ getUnitName(value, item) }}
              </template>

              <!-- Total Time column -->
              <template #total_time="{ value, item }">
                <strong class="text-primary">
                  {{ value || (item.setup_time + item.run_time) }} {{ getUnitName(value, item) }}
                </strong>
              </template>

              <!-- Cost columns -->
              <template #labor_cost="{ value }">
                {{ formatCurrency(value) }}
              </template>

              <template #overhead_cost="{ value }">
                {{ formatCurrency(value) }}
              </template>

              <!-- Actions column -->
              <template #actions="{ item }">
                <div class="btn-group btn-group-sm">
                  <button
                    @click="editOperation(item)"
                    class="btn btn-outline-primary"
                    title="Edit Operation"
                  >
                    <i class="fas fa-edit"></i>
                  </button>
                  <button
                    @click="confirmDeleteOperation(item)"
                    class="btn btn-outline-danger"
                    title="Delete Operation"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </template>
            </DataTable>
          </div>
        </div>
      </div>

      <!-- Operation Form Modal - Enhanced Version -->
      <div v-if="showOperationModal" class="modal">
        <div class="modal-backdrop" @click="cancelOperationForm"></div>
        <div class="modal-content modal-lg">
          <div class="modal-header">
            <h2>{{ selectedOperation ? 'Edit Operation' : 'Add New Operation' }}</h2>
            <button class="close-btn" @click="cancelOperationForm">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveOperation" class="operation-form">

              <!-- Basic Information Section -->
              <div class="form-section">
                <h3 class="section-title">Basic Information</h3>

                <div class="form-row">
                  <div class="form-group col-md-6">
                    <label for="operation_name">Operation Name <span class="required">*</span></label>
                    <input
                      id="operation_name"
                      v-model="operationForm.operation_name"
                      type="text"
                      class="form-control"
                      placeholder="Enter operation name"
                      required
                    />
                    <small v-if="operationErrors.operation_name" class="error-message">
                      {{ operationErrors.operation_name[0] }}
                    </small>
                  </div>

                  <div class="form-group col-md-6">
                    <label for="workcenter_id">Work Center <span class="required">*</span></label>
                    <select
                      id="workcenter_id"
                      v-model="operationForm.workcenter_id"
                      class="form-control"
                      required
                    >
                      <option value="" disabled>-- Select Work Center --</option>
                      <option
                        v-for="wc in workCenters"
                        :key="wc.workcenter_id"
                        :value="wc.workcenter_id"
                      >
                        {{ wc.name }} ({{ wc.code }})
                      </option>
                    </select>
                    <small v-if="operationErrors.workcenter_id" class="error-message">
                      {{ operationErrors.workcenter_id[0] }}
                    </small>
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group col-md-6">
                    <label for="work_flow">Work Flow</label>
                    <input
                      id="work_flow"
                      v-model="operationForm.work_flow"
                      type="text"
                      class="form-control"
                      placeholder="Enter work flow"
                    />
                    <small v-if="operationErrors.work_flow" class="error-message">
                      {{ operationErrors.work_flow[0] }}
                    </small>
                  </div>

                  <div class="form-group col-md-6">
                    <label for="models">Models</label>
                    <input
                      id="models"
                      v-model="operationForm.models"
                      type="text"
                      class="form-control"
                      placeholder="Enter models"
                    />
                    <small v-if="operationErrors.models" class="error-message">
                      {{ operationErrors.models[0] }}
                    </small>
                  </div>
                </div>
              </div>

              <!-- Time & Sequence Section -->
              <div class="form-section">
                <h3 class="section-title">Time & Sequence</h3>

                <div class="form-row">
                  <div class="form-group col-md-3">
                    <label for="sequence">Sequence <span class="required">*</span></label>
                    <input
                      id="sequence"
                      v-model.number="operationForm.sequence"
                      type="number"
                      min="1"
                      step="10"
                      class="form-control"
                      required
                    />
                    <small v-if="operationErrors.sequence" class="error-message">
                      {{ operationErrors.sequence[0] }}
                    </small>
                  </div>

                  <div class="form-group col-md-3">
                    <label for="uom_id">Unit of Measure <span class="required">*</span></label>
                    <select
                      id="uom_id"
                      v-model="operationForm.uom_id"
                      class="form-control"
                      required
                    >
                      <option value="" disabled>-- Select UOM --</option>
                      <option
                        v-for="uom in unitOfMeasures"
                        :key="uom.uom_id"
                        :value="uom.uom_id"
                      >
                        {{ uom.name }} ({{ uom.symbol }})
                      </option>
                    </select>
                    <small v-if="operationErrors.uom_id" class="error-message">
                      {{ operationErrors.uom_id[0] }}
                    </small>
                  </div>

                  <div class="form-group col-md-3">
                    <label for="setup_time">Setup Time <span class="required">*</span></label>
                    <input
                      id="setup_time"
                      v-model.number="operationForm.setup_time"
                      type="number"
                      min="0"
                      step="0.1"
                      class="form-control"
                      required
                    />
                    <small v-if="operationErrors.setup_time" class="error-message">
                      {{ operationErrors.setup_time[0] }}
                    </small>
                  </div>

                  <div class="form-group col-md-3">
                    <label for="run_time">Process Time <span class="required">*</span></label>
                    <input
                      id="run_time"
                      v-model.number="operationForm.run_time"
                      type="number"
                      min="0"
                      step="0.01"
                      class="form-control"
                      required
                    />
                    <small v-if="operationErrors.run_time" class="error-message">
                      {{ operationErrors.run_time[0] }}
                    </small>
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group col-md-4">
                    <label>Total Time (Calculated)</label>
                    <input
                      type="text"
                      class="form-control calculated-field"
                      :value="totalOperationTime !== undefined && totalOperationTime !== null ? totalOperationTime.toFixed(1) : ''"
                      readonly
                    />
                    <small class="help-text">Setup Time + Process Time</small>
                  </div>
                </div>
              </div>

              <!-- Cost Information Section -->
              <div class="form-section">
                <h3 class="section-title">Cost Information</h3>

                <div class="form-row">
                  <div class="form-group col-md-6">
                    <label for="labor_cost">Labor Cost <span class="required">*</span></label>
                    <input
                      id="labor_cost"
                      v-model.number="operationForm.labor_cost"
                      type="number"
                      min="0"
                      step="0.01"
                      class="form-control"
                      required
                    />
                    <small v-if="operationErrors.labor_cost" class="error-message">
                      {{ operationErrors.labor_cost[0] }}
                    </small>
                  </div>

                  <div class="form-group col-md-6">
                    <label for="overhead_cost">Overhead Cost <span class="required">*</span></label>
                    <input
                      id="overhead_cost"
                      v-model.number="operationForm.overhead_cost"
                      type="number"
                      min="0"
                      step="0.01"
                      class="form-control"
                      required
                    />
                    <small v-if="operationErrors.overhead_cost" class="error-message">
                      {{ operationErrors.overhead_cost[0] }}
                    </small>
                  </div>
                </div>
              </div>

              <!-- Form Actions -->
              <div class="form-actions">
                <button type="button" class="btn btn-secondary" @click="cancelOperationForm">
                  <i class="fas fa-times mr-1"></i>
                  Cancel
                </button>
                <button type="submit" class="btn btn-primary" :disabled="isSavingOperation">
                  <i class="fas fa-save mr-1"></i>
                  {{ isSavingOperation ? 'Saving...' : (selectedOperation ? 'Update Operation' : 'Save Operation') }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Confirmation Modal for Delete Routing -->
      <ConfirmationModal
        v-if="showDeleteModal"
        title="Delete Routing"
        :message="`Are you sure you want to delete routing <strong>${routing.routing_code}</strong>?<br>This action cannot be undone.`"
        confirm-button-text="Delete"
        confirm-button-class="btn btn-danger"
        @confirm="deleteRouting"
        @close="showDeleteModal = false"
      />

      <!-- Confirmation Modal for Delete Operation -->
      <ConfirmationModal
        v-if="showDeleteOperationModal"
        title="Delete Operation"
        :message="`Are you sure you want to delete operation <strong>${selectedOperation?.operation_name}</strong>?<br>This action cannot be undone.`"
        confirm-button-text="Delete"
        confirm-button-class="btn btn-danger"
        @confirm="deleteOperation"
        @close="showDeleteOperationModal = false"
      />
    </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';

export default {
  name: 'RoutingDetail',
  setup() {
    const router = useRouter();
    const route = useRoute();
    const routingId = computed(() => route.params.id);

    const isLoading = ref(true);
    const isLoadingOperations = ref(true);
    const routing = ref({});
    const operations = ref([]);
    const workCenters = ref([]);
    const unitOfMeasures = ref([]);

    const selectedOperation = ref(null);
    const showOperationModal = ref(false);
    const isSavingOperation = ref(false);
    const operationErrors = ref({});

    const showDeleteModal = ref(false);
    const showDeleteOperationModal = ref(false);

    // Initial operation form values
    const operationForm = reactive({
      workcenter_id: '',
      operation_name: '',
      work_flow: '',
      models: '',
      sequence: 10,
      setup_time: 0,
      run_time: 0,
      uom_id: '',
      labor_cost: 0,
      overhead_cost: 0,
    });

    // Computed property for total time safely calculated
    const totalOperationTime = computed(() => {
      const setup = Number(operationForm.setup_time) || 0;
      const run = Number(operationForm.run_time) || 0;
      return setup + run;
    });

    // Operation table columns
    const operationColumns = [
      { key: 'sequence', label: 'Sequence', sortable: true },
      { key: 'operation_name', label: 'Operation Name', sortable: true },
      { key: 'work_flow', label: 'Work Flow', sortable: true },
      { key: 'models', label: 'Models', sortable: true },
      { key: 'work_center_name', label: 'Work Center' },
      { key: 'setup_time', label: 'Setup Time' },
      { key: 'run_time', label: 'Process Time' },
      { key: 'total_time', label: 'Total Time' },
      { key: 'labor_cost', label: 'Labor Cost' },
      { key: 'overhead_cost', label: 'Overhead Cost' },
    //   { key: 'actions', label: 'Actions' },
    ];

    // Sort operations by sequence
    const sortedOperations = computed(() => {
      return [...operations.value].sort((a, b) => a.sequence - b.sequence);
    });

    // Format date
    const formatDate = (dateString) => {
      if (!dateString) return '-';
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
      });
    };

    // Format currency
    const formatCurrency = (value) => {
      if (value === null || value === undefined) return '-';
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
      }).format(value);
    };

    // Get unit of measure name based on ID
    const getUnitName = (value, item) => {
      if (!item || !item.unitOfMeasure) return '';
      return item.unitOfMeasure.symbol || '';
    };

    // Load routing data
    const loadRouting = async () => {
      isLoading.value = true;
      try {
        const response = await axios.get(`/routings/${routingId.value}`);
        routing.value = response.data.data;
      } catch (error) {
        console.error('Error loading routing:', error);
        alert('Failed to load routing data. Please try again.');
      } finally {
        isLoading.value = false;
      }
    };

    // Load operations
    const loadOperations = async () => {
      isLoadingOperations.value = true;
      try {
        const response = await axios.get(`/routings/${routingId.value}/operations`);
        console.log('Operations data:', response.data.data);
        // Map operations to add work_center_name property and format total_time
        operations.value = response.data.data.map(op => ({
          ...op,
          work_center_name: op.work_center ? op.work_center.name : '-',
          total_time: op.total_time || (op.setup_time + op.run_time)
        }));
      } catch (error) {
        console.error('Error loading operations:', error);
      } finally {
        isLoadingOperations.value = false;
      }
    };

    // Load work centers for dropdown
    const loadWorkCenters = async () => {
      try {
        const response = await axios.get('/work-centers');
        workCenters.value = response.data.data;
      } catch (error) {
        console.error('Error loading work centers:', error);
      }
    };

    // Load units of measure for dropdown
    const loadUnitOfMeasures = async () => {
      try {
        const response = await axios.get('/uoms');
        unitOfMeasures.value = response.data.data;
      } catch (error) {
        console.error('Error loading units of measure:', error);
      }
    };

    // Edit operation
    const editOperation = (operation) => {
      selectedOperation.value = operation;

      // Copy operation data to form
      operationForm.workcenter_id = operation.workcenter_id;
      operationForm.operation_name = operation.operation_name;
      operationForm.work_flow = operation.work_flow || '';
      operationForm.models = operation.models || '';
      operationForm.sequence = operation.sequence;
      operationForm.setup_time = operation.setup_time;
      operationForm.run_time = operation.run_time;
      operationForm.uom_id = operation.uom_id;
      operationForm.labor_cost = operation.labor_cost;
      operationForm.overhead_cost = operation.overhead_cost;

      showOperationModal.value = true;
    };

    // Reset operation form
    const resetOperationForm = () => {
      selectedOperation.value = null;
      operationForm.workcenter_id = '';
      operationForm.operation_name = '';
      operationForm.work_flow = '';
      operationForm.models = '';
      operationForm.sequence = operations.value.length > 0
        ? Math.max(...operations.value.map(op => op.sequence)) + 10
        : 10;
      operationForm.setup_time = 0;
      operationForm.run_time = 0;
      operationForm.uom_id = '';
      operationForm.labor_cost = 0;
      operationForm.overhead_cost = 0;
    };

    // Cancel operation form
    const cancelOperationForm = () => {
      showOperationModal.value = false;
      resetOperationForm();
    };

    // Save operation
    const saveOperation = async () => {
      isSavingOperation.value = true;
      operationErrors.value = {};

      try {
        if (selectedOperation.value) {
          // Update existing operation
          await axios.put(
            `/routings/${routingId.value}/operations/${selectedOperation.value.operation_id}`,
            operationForm
          );
        } else {
          // Create new operation
          await axios.post(
            `/routings/${routingId.value}/operations`,
            operationForm
          );
        }

        await loadOperations(); // Reload operations
        showOperationModal.value = false;
        resetOperationForm();
      } catch (error) {
        console.error('Error saving operation:', error);

        if (error.response && error.response.data && error.response.data.errors) {
          operationErrors.value = error.response.data.errors;
        } else {
          alert('Failed to save operation. Please try again.');
        }
      } finally {
        isSavingOperation.value = false;
      }
    };

    // Confirm delete routing
    const confirmDelete = () => {
      showDeleteModal.value = true;
    };

    // Delete routing
    const deleteRouting = async () => {
      try {
        await axios.delete(`/routings/${routingId.value}`);
        router.push('/manufacturing/routings');
      } catch (error) {
        console.error('Error deleting routing:', error);

        if (error.response && error.response.data && error.response.data.message) {
          alert(error.response.data.message);
        } else {
          alert('Failed to delete routing. Please try again.');
        }

        showDeleteModal.value = false;
      }
    };

    // Confirm delete operation
    const confirmDeleteOperation = (operation) => {
      selectedOperation.value = operation;
      showDeleteOperationModal.value = true;
    };

    // Delete operation
    const deleteOperation = async () => {
      try {
        await axios.delete(
          `/routings/${routingId.value}/operations/${selectedOperation.value.operation_id}`
        );
        await loadOperations(); // Reload operations
        showDeleteOperationModal.value = false;
      } catch (error) {
        console.error('Error deleting operation:', error);

        if (error.response && error.response.data && error.response.data.message) {
          alert(error.response.data.message);
        } else {
          alert('Failed to delete operation. Please try again.');
        }

        showDeleteOperationModal.value = false;
      }
    };

    // Load data on component mount
    onMounted(() => {
      loadRouting();
      loadOperations();
      loadWorkCenters();
      loadUnitOfMeasures();
    });

    return {
      routingId,
      isLoading,
      isLoadingOperations,
      routing,
      operations,
      sortedOperations,
      operationColumns,
      operationForm,
      operationErrors,
      selectedOperation,
      showOperationModal,
      isSavingOperation,
      workCenters,
      unitOfMeasures,
      showDeleteModal,
      showDeleteOperationModal,
      totalOperationTime,
      formatDate,
      formatCurrency,
      getUnitName,
      editOperation,
      cancelOperationForm,
      saveOperation,
      confirmDelete,
      deleteRouting,
      confirmDeleteOperation,
      deleteOperation,
    };
  },
};
</script>

<style scoped>
/* Container styling */
.routing-detail-container {
  max-width: 1100px;
  margin: 0 auto;
  padding: 1.5rem;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  color: #334155;
}

/* Header styling */
.page-title {
  font-size: 1.75rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
}

.d-flex {
  display: flex;
}

.justify-content-between {
  justify-content: space-between;
}

.align-items-center {
  align-items: center;
}

.mb-3 {
  margin-bottom: 1.5rem;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

/* Button styling */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.65rem 1rem;
  font-size: 0.95rem;
  font-weight: 500;
  line-height: 1.5;
  border-radius: 0.5rem;
  border: 1px solid transparent;
  transition: all 0.2s ease;
  cursor: pointer;
  text-decoration: none;
  gap: 0.5rem;
}

.btn-primary {
  background-color: #3b82f6;
  border-color: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background-color: #2563eb;
  border-color: #2563eb;
  box-shadow: 0 2px 4px rgba(37, 99, 235, 0.25);
}

.btn-secondary {
  background-color: #f1f5f9;
  border-color: #cbd5e1;
  color: #475569;
}

.btn-secondary:hover {
  background-color: #e2e8f0;
  color: #334155;
}

.btn-danger {
  background-color: #ef4444;
  border-color: #ef4444;
  color: white;
}

.btn-danger:hover {
  background-color: #dc2626;
  border-color: #dc2626;
  box-shadow: 0 2px 4px rgba(220, 38, 38, 0.25);
}

.btn-sm {
  padding: 0.4rem 0.7rem;
  font-size: 0.85rem;
}

.btn-outline-primary {
  background-color: transparent;
  border-color: #3b82f6;
  color: #3b82f6;
}

.btn-outline-primary:hover {
  background-color: #3b82f6;
  color: white;
}

.btn-outline-danger {
  background-color: transparent;
  border-color: #ef4444;
  color: #ef4444;
}

.btn-outline-danger:hover {
  background-color: #ef4444;
  color: white;
}

.btn-group {
  display: flex;
  gap: 0.25rem;
}

.mr-1 {
  margin-right: 0.25rem;
}

.mr-2 {
  margin-right: 0.5rem;
}

/* Card styling */
.card {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 1px 8px rgba(0, 0, 0, 0.08);
  margin-bottom: 1.5rem;
  overflow: hidden;
  border: none;
}

.card-header {
  padding: 1.25rem 1.5rem;
  background-color: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #1e293b;
}

.card-body {
  padding: 1.5rem;
}

.p-0 {
  padding: 0;
}

/* Badge styling */
.badge {
  display: inline-block;
  padding: 0.35em 0.65em;
  font-size: 0.75em;
  font-weight: 600;
  line-height: 1;
  text-align: center;
  white-space: nowrap;
  vertical-align: baseline;
  border-radius: 0.25rem;
}

.badge-success {
  background-color: #22c55e;
  color: white;
}

.badge-warning {
  background-color: #f59e0b;
  color: white;
}

.badge-secondary {
  background-color: #94a3b8;
  color: white;
}

/* Detail table styling */
.detail-table {
  width: 100%;
  margin-bottom: 0;
}

.table {
  width: 100%;
  margin-bottom: 1rem;
  border-collapse: collapse;
}

.table-borderless th,
.table-borderless td {
  border: none;
}

.detail-table th {
  font-weight: 600;
  color: #64748b;
  padding: 0.75rem 0;
  vertical-align: top;
}

.detail-table td {
  color: #334155;
  padding: 0.75rem 0;
  vertical-align: top;
}

.col-md-6 {
  flex: 0 0 50%;
  max-width: 50%;
  padding-right: 0.75rem;
  padding-left: 0.75rem;
}

.col-md-3 {
  flex: 0 0 25%;
  max-width: 25%;
  padding-right: 0.75rem;
  padding-left: 0.75rem;
}

.col-md-4 {
  flex: 0 0 33.333333%;
  max-width: 33.333333%;
}

/* Simple Grid Layout */
.info-grid-simple {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.info-row {
  display: grid;
  grid-template-columns: 150px 1fr 150px 1fr;
  gap: 1rem;
  align-items: center;
  padding: 0.75rem 0;
  border-bottom: 1px solid #f1f5f9;
}

.info-row:last-child {
  border-bottom: none;
}

.info-label {
  font-weight: 500;
  color: #64748b;
  font-size: 0.9rem;
}

.info-value {
  color: #334155;
  font-size: 0.9rem;
  font-weight: 400;
}

/* Additional Information Simple */
.additional-info-simple {
  padding-top: 1rem;
  border-top: 2px solid #e2e8f0;
}

.badge-info {
  background-color: #3b82f6;
  color: white;
}

.text-muted {
  color: #9ca3af;
}

/* Loading indicator */
.text-center {
  text-align: center;
}

.py-5 {
  padding-top: 3rem;
  padding-bottom: 3rem;
}

.mt-2 {
  margin-top: 0.5rem;
}

.mt-4 {
  margin-top: 1.5rem;
}

.text-primary {
  color: #3b82f6;
}

.fa-spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Enhanced Modal Styling */
.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1050;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  padding: 2rem 1rem;
  overflow-y: auto;
}

.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(15, 23, 42, 0.6);
  z-index: 1051;
  backdrop-filter: blur(3px);
}

.modal-content {
  background-color: white;
  border-radius: 0.75rem;
  box-shadow: 0 10px 50px rgba(0, 0, 0, 0.2);
  width: 100%;
  max-width: 900px;
  z-index: 1052;
  max-height: calc(100vh - 4rem);
  overflow: hidden;
  animation: modal-appear 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
  border: none;
  margin-top: auto;
  margin-bottom: auto;
}

@keyframes modal-appear {
  from {
    opacity: 0;
    transform: scale(0.9) translateY(-20px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.modal-lg {
  max-width: 900px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #e2e8f0;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.modal-header h2 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
  color: #1e293b;
}

.modal-body {
  padding: 0;
  overflow-y: auto;
  max-height: calc(100vh - 8rem);
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.25rem;
  cursor: pointer;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  transition: all 0.2s ease;
}

.close-btn:hover {
  background-color: #f1f5f9;
  color: #334155;
  transform: scale(1.1);
}

/* Enhanced Form Styling */
.operation-form {
  padding: 2rem;
}

.form-section {
  margin-bottom: 2.5rem;
  padding: 1.5rem;
  background-color: #fafbfc;
  border-radius: 0.75rem;
  border: 1px solid #e2e8f0;
}

.form-section:last-of-type {
  margin-bottom: 1rem;
}

.form-section .section-title {
  margin: 0 0 1.5rem 0;
  font-size: 1.125rem;
  font-weight: 600;
  color: #374151;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid #e5e7eb;
  display: flex;
  align-items: center;
}

.form-section .section-title::before {
  content: '';
  width: 4px;
  height: 1.25rem;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  border-radius: 2px;
  margin-right: 0.75rem;
}

.form-row {
  display: flex;
  flex-wrap: wrap;
  margin: 0 -0.75rem;
}

.form-group {
  margin-bottom: 1.5rem;
  padding: 0 0.75rem;
}

.col-md-4 {
  flex: 0 0 33.333333%;
  max-width: 33.333333%;
}

/* Enhanced Form Controls */
label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  font-size: 0.95rem;
  color: #374151;
  line-height: 1.4;
}

.required {
  color: #ef4444;
  font-weight: 600;
}

.form-control {
  display: block;
  width: 100%;
  padding: 0.75rem 1rem;
  font-size: 0.95rem;
  line-height: 1.5;
  color: #374151;
  background-color: #fff;
  background-clip: padding-box;
  border: 2px solid #d1d5db;
  border-radius: 0.5rem;
  transition: all 0.2s ease;
  font-family: inherit;
}

.form-control:focus {
  border-color: #3b82f6;
  outline: 0;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  background-color: #fefefe;
}

.form-control:hover:not(:focus) {
  border-color: #9ca3af;
}

.calculated-field {
  background-color: #f9fafb;
  border-color: #d1d5db;
  color: #6b7280;
  font-weight: 500;
}

.calculated-field:focus {
  border-color: #d1d5db;
  box-shadow: none;
}

/* Select Styling */
select.form-control {
  appearance: none;
  background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 1rem center;
  background-size: 1rem;
  padding-right: 3rem;
  cursor: pointer;
}

select.form-control:focus {
  background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%233b82f6' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
}

/* Helper Text & Error Messages */
.help-text {
  display: block;
  margin-top: 0.35rem;
  font-size: 0.8rem;
  color: #6b7280;
  font-style: italic;
}

.error-message {
  display: block;
  margin-top: 0.35rem;
  font-size: 0.8rem;
  color: #ef4444;
  font-weight: 500;
}

/* Enhanced Form Actions */
.form-actions {
  display: flex;
  justify-content: flex-end;
  padding: 1.5rem 2rem;
  background-color: #f8fafc;
  border-top: 1px solid #e2e8f0;
  gap: 1rem;
  margin: 0 -2rem -2rem -2rem;
}

.form-actions .btn {
  padding: 0.75rem 1.5rem;
  font-size: 0.95rem;
  font-weight: 500;
  border-radius: 0.5rem;
  border: 2px solid transparent;
  transition: all 0.2s ease;
  cursor: pointer;
  text-decoration: none;
  gap: 0.5rem;
  min-width: 120px;
}

.form-actions .btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.form-actions .btn-primary {
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  border-color: #3b82f6;
  color: white;
  box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
}

.form-actions .btn-primary:hover:not(:disabled) {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  border-color: #2563eb;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
  transform: translateY(-1px);
}

.form-actions .btn-secondary {
  background-color: #f8fafc;
  border-color: #d1d5db;
  color: #374151;
}

.form-actions .btn-secondary:hover {
  background-color: #f1f5f9;
  border-color: #9ca3af;
  color: #1f2937;
}

/* Responsive Design */
@media (max-width: 768px) {
  .d-flex.justify-content-between.align-items-center {
    flex-direction: column;
    align-items: flex-start;
  }

  .action-buttons {
    margin-top: 1rem;
    width: 100%;
  }

  .btn {
    flex: 1;
  }

  .info-row {
    grid-template-columns: 1fr;
    gap: 0.5rem;
    padding: 1rem 0;
  }

  .info-label {
    font-weight: 600;
    margin-bottom: 0.25rem;
  }

  .info-value {
    margin-bottom: 1rem;
    padding-left: 1rem;
  }

  .modal {
    padding: 1rem 0.5rem;
  }

  .modal-content {
    max-width: 100%;
  }

  .modal-header {
    padding: 1rem 1.5rem;
  }

  .modal-header h2 {
    font-size: 1.25rem;
  }

  .operation-form {
    padding: 1.5rem;
  }

  .form-section {
    padding: 1rem;
    margin-bottom: 1.5rem;
  }

  .form-row {
    margin: 0 -0.5rem;
  }

  .form-group {
    padding: 0 0.5rem;
  }

  .col-md-3,
  .col-md-4,
  .col-md-6 {
    flex: 0 0 100%;
    max-width: 100%;
  }

  .form-actions {
    flex-direction: column;
    padding: 1rem 1.5rem;
  }

  .form-actions .btn {
    width: 100%;
    justify-content: center;
  }
}

/* Touch optimizations for smaller screens */
@media (max-width: 576px) {
  .routing-detail-container {
    padding: 1rem;
  }

  .page-title {
    font-size: 1.5rem;
  }

  .btn {
    padding: 0.75rem 1rem;
  }

  .card-header {
    padding: 1rem;
  }

  .card-body {
    padding: 1rem;
  }

  .info-row {
    padding: 0.75rem 0;
  }

  .info-label {
    font-size: 0.85rem;
  }

  .info-value {
    font-size: 0.85rem;
  }

  .modal {
    padding: 0;
  }

  .modal-content {
    border-radius: 0;
    max-height: 100vh;
  }

  .operation-form {
    padding: 1rem;
  }

  .form-section {
    padding: 0.75rem;
    border-radius: 0.5rem;
  }

  .form-actions {
    padding: 1rem;
  }
}
</style>
