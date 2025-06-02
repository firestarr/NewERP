<!-- src/views/manufacturing/ProductionOrderForm.vue -->
<template>
  <div class="production-order-form">
    <div class="page-header">
      <h1>{{ isEditing ? 'Edit Production Order' : 'Create Production Order' }}</h1>
      <div class="actions">
        <router-link to="/manufacturing/production-orders" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i> Back to List
        </router-link>
      </div>
    </div>

    <div v-if="loading" class="loading-container">
      <i class="fas fa-spinner fa-spin"></i>
      <span>Loading...</span>
    </div>

    <form v-else @submit.prevent="saveProductionOrder" class="card">
      <div class="card-body">
        <div class="form-section">
          <h2>Production Order Details</h2>

          <div class="form-row">
            <div class="form-group">
              <label for="production_number">Production Number</label>
              <input
                type="text"
                id="production_number"
                v-model="form.production_number"
                :readonly="isEditing"
                :class="{ 'error': errors && errors.production_number }"
                placeholder="Will be auto-generated if left empty"
              >
              <div v-if="errors && errors.production_number" class="error-message">
                {{ errors.production_number }}
              </div>
            </div>

            <div class="form-group">
              <label for="production_date">Production Date</label>
              <input
                type="date"
                id="production_date"
                v-model="form.production_date"
                :class="{ 'error': errors && errors.production_date }"
                required
              >
              <div v-if="errors && errors.production_date" class="error-message">
                {{ errors.production_date }}
              </div>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="work_order">Work Order <span class="required">*</span></label>
              <div class="dropdown">
                <input
                  type="text"
                  id="work_order"
                  v-model="workOrderSearch"
                  class="form-control"
                  :class="{ 'error': errors && errors.wo_id }"
                  placeholder="Search for a work order..."
                  @focus="showWorkOrderDropdown = true"
                  @blur="hideWorkOrderDropdown"
                  autocomplete="off"
                />
                <div v-if="showWorkOrderDropdown" class="dropdown-menu">
                  <div
                    v-for="wo in filteredWorkOrders"
                    :key="wo.wo_id"
                    @mousedown="selectWorkOrder(wo)"
                    class="dropdown-item"
                  >
                    <div class="work-order-info">
                      <strong>{{ wo.wo_number }}</strong>
                      <span class="work-order-status" :class="getStatusClass(wo.status)">{{ wo.status }}</span>
                    </div>
                    <div class="work-order-details">
                      <span class="item-name">{{ wo.item?.name || 'Unknown Item' }}</span>
                      <span class="quantity">Qty: {{ wo.planned_quantity }}</span>
                    </div>
                    <div class="work-order-dates">
                      <span class="date-info">Start: {{ formatDate(wo.planned_start_date) }}</span>
                      <span class="date-info">End: {{ formatDate(wo.planned_end_date) }}</span>
                    </div>
                  </div>
                  <div v-if="filteredWorkOrders.length === 0" class="dropdown-item text-muted">
                    No work orders found
                  </div>
                </div>
              </div>
              <div v-if="errors && errors.wo_id" class="error-message">
                {{ errors.wo_id }}
              </div>
            </div>

            <div class="form-group">
              <label for="status">Status</label>
              <select
                id="status"
                v-model="form.status"
                :class="{ 'error': errors && errors.status }"
                required
              >
                <option value="Draft">Draft</option>
                <option value="In Progress">In Progress</option>
                <option value="Completed">Completed</option>
                <option value="Cancelled">Cancelled</option>
              </select>
              <div v-if="errors && errors.status" class="error-message">
                {{ errors.status }}
              </div>
            </div>
          </div>

          <div v-if="workOrderDetails" class="info-panel">
            <div class="info-panel-title">Work Order Information</div>
            <div class="info-panel-content">
              <div class="info-row">
                <div class="info-label">Item:</div>
                <div class="info-value">{{ workOrderDetails.item?.name || 'N/A' }}</div>
              </div>
              <div class="info-row">
                <div class="info-label">BOM:</div>
                <div class="info-value">{{ workOrderDetails.bom?.bom_code || 'N/A' }}</div>
              </div>
              <div class="info-row">
                <div class="info-label">Routing:</div>
                <div class="info-value">{{ workOrderDetails.routing?.routing_code || 'N/A' }}</div>
              </div>
              <div class="info-row">
                <div class="info-label">Total Planned Quantity:</div>
                <div class="info-value">{{ workOrderDetails.planned_quantity }}</div>
              </div>
              <div class="info-row">
                <div class="info-label">Remaining Quantity:</div>
                <div class="info-value" :class="getRemainingQuantityClass()">
                  {{ getRemainingQuantity() }}
                </div>
              </div>
              <div class="info-row">
                <div class="info-label">Planned Start:</div>
                <div class="info-value">{{ formatDate(workOrderDetails.planned_start_date) }}</div>
              </div>
              <div class="info-row">
                <div class="info-label">Planned End:</div>
                <div class="info-value">{{ formatDate(workOrderDetails.planned_end_date) }}</div>
              </div>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="planned_quantity">Planned Quantity</label>
              <input
                type="number"
                id="planned_quantity"
                v-model="form.planned_quantity"
                :class="{ 'error': errors && errors.planned_quantity }"
                :max="getRemainingQuantity()"
                min="0"
                step="0.01"
                required
              >
              <div v-if="errors && errors.planned_quantity" class="error-message">
                {{ errors.planned_quantity }}
              </div>
              <div v-if="workOrderDetails" class="input-hint">
                Maximum available: {{ getRemainingQuantity() }}
              </div>
            </div>

            <div class="form-group">
              <label for="actual_quantity">Actual Quantity</label>
              <input
                type="number"
                id="actual_quantity"
                v-model="form.actual_quantity"
                :class="{ 'error': errors && errors.actual_quantity }"
                min="0"
                step="0.01"
              >
              <div v-if="errors && errors.actual_quantity" class="error-message">
                {{ errors.actual_quantity }}
              </div>
            </div>
          </div>
        </div>

        <div v-if="form.wo_id && bomComponents.length > 0" class="form-section material-section">
          <h2>Material Consumption</h2>
          <p class="section-description">
            The following materials will be consumed from the BOM.
            You can adjust the quantities as needed.
          </p>

          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>Item</th>
                  <th>Warehouse</th>
                  <th>Planned Quantity</th>
                  <th>Actual Quantity</th>
                  <th>UoM</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(component, index) in form.consumptions" :key="index">
                  <td>
                    <div class="item-name">{{ getItemName(component.item_id) }}</div>
                    <div v-if="component.item_id" class="item-code">{{ getItemCode(component.item_id) }}</div>
                  </td>
                  <td>
                    <select v-model="component.warehouse_id" required>
                      <option value="">-- Select Warehouse --</option>
                      <option v-for="warehouse in warehouses" :key="warehouse.warehouse_id" :value="warehouse.warehouse_id">
                        {{ warehouse.name }}
                      </option>
                    </select>
                    <div v-if="errors && errors[`consumptions.${index}.warehouse_id`]" class="error-message">
                      {{ errors[`consumptions.${index}.warehouse_id`] }}
                    </div>
                  </td>
                  <td>
                    <input
                      type="number"
                      v-model="component.planned_quantity"
                      min="0"
                      step="0.01"
                      required
                    >
                    <div v-if="errors && errors[`consumptions.${index}.planned_quantity`]" class="error-message">
                      {{ errors[`consumptions.${index}.planned_quantity`] }}
                    </div>
                  </td>
                  <td>
                    <input
                      type="number"
                      v-model="component.actual_quantity"
                      min="0"
                      step="0.01"
                    >
                  </td>
                  <td>{{ getItemUom(component.item_id) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="card-footer">
        <button type="button" class="btn btn-secondary" @click="cancel">Cancel</button>
        <button type="submit" class="btn btn-primary" :disabled="saving">
          <i v-if="saving" class="fas fa-spinner fa-spin"></i>
          {{ saving ? 'Saving...' : 'Save Production Order' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'ProductionOrderForm',
  props: {
    id: {
      type: [Number, String],
      required: false
    }
  },
  data() {
    return {
      form: {
        production_number: '',
        production_date: new Date().toISOString().substr(0, 10),
        wo_id: '',
        planned_quantity: 0,
        actual_quantity: 0,
        status: 'Draft',
        consumptions: []
      },
      workOrders: [],
      workOrderDetails: null,
      existingProductionOrders: [],
      warehouses: [],
      items: {},
      bomComponents: [],
      loading: true,
      saving: false,
      errors: {},
      // Work Order Search State
      workOrderSearch: '',
      showWorkOrderDropdown: false
    };
  },
  computed: {
    isEditing() {
      return !!this.id;
    },
    // Filtered work orders based on search
    filteredWorkOrders() {
      if (!this.workOrderSearch) {
        return this.workOrders.slice(0, 10);
      }
      return this.workOrders.filter(wo =>
        wo.wo_number.toLowerCase().includes(this.workOrderSearch.toLowerCase()) ||
        (wo.item && wo.item.name.toLowerCase().includes(this.workOrderSearch.toLowerCase())) ||
        wo.status.toLowerCase().includes(this.workOrderSearch.toLowerCase())
      ).slice(0, 10);
    }
  },
  created() {
    this.fetchInitialData();
  },
  methods: {
    async fetchInitialData() {
      this.loading = true;
      try {
        // Fetch work orders excluding completed
        const [workOrdersRes, warehousesRes] = await Promise.all([
          axios.get('/work-orders', { params: { exclude_status: 'Completed' } }),
          axios.get('/warehouses')
        ]);

        this.workOrders = workOrdersRes.data.data || workOrdersRes.data;
        this.warehouses = warehousesRes.data.data || warehousesRes.data;

        // If editing, fetch production order details
        if (this.isEditing) {
          await this.fetchProductionOrder();
        }
      } catch (error) {
        console.error('Error fetching initial data:', error);
        if (this.$toast) this.$toast.error('Failed to load required data');
      } finally {
        this.loading = false;
      }
    },

    async fetchProductionOrder() {
      try {
        const response = await axios.get(`/production-orders/${this.id}`);
        const productionOrder = response.data.data || response.data;

        // Map production order data to form
        this.form = {
          production_number: productionOrder.production_number,
          production_date: productionOrder.production_date,
          wo_id: productionOrder.wo_id,
          planned_quantity: productionOrder.planned_quantity,
          actual_quantity: productionOrder.actual_quantity || 0,
          status: productionOrder.status,
          consumptions: []
        };

        // Set work order search text
        if (productionOrder.workOrder) {
          this.workOrderSearch = `${productionOrder.workOrder.wo_number} - ${productionOrder.workOrder.item?.name || 'Unknown Item'}`;
        }

        // Load work order details
        await this.loadWorkOrderDetails();

        // Load consumption data
        if (productionOrder.productionConsumptions) {
          this.form.consumptions = productionOrder.productionConsumptions.map(consumption => ({
            consumption_id: consumption.consumption_id,
            item_id: consumption.item_id,
            planned_quantity: consumption.planned_quantity,
            actual_quantity: consumption.actual_quantity || 0,
            warehouse_id: consumption.warehouse_id
          }));
        }
      } catch (error) {
        console.error('Error fetching production order:', error);
        if (this.$toast) this.$toast.error('Failed to load production order data');
      }
    },

    // Work Order Selection Methods
    selectWorkOrder(workOrder) {
      this.form.wo_id = workOrder.wo_id;
      this.workOrderSearch = `${workOrder.wo_number} - ${workOrder.item?.name || 'Unknown Item'}`;
      this.showWorkOrderDropdown = false;
      this.loadWorkOrderDetails();
    },

    hideWorkOrderDropdown() {
      setTimeout(() => {
        this.showWorkOrderDropdown = false;
      }, 200);
    },

    getStatusClass(status) {
      const statusClasses = {
        'Draft': 'status-draft',
        'In Progress': 'status-in-progress',
        'Completed': 'status-completed',
        'Cancelled': 'status-cancelled'
      };
      return statusClasses[status] || '';
    },

    async loadWorkOrderDetails() {
      if (!this.form.wo_id) {
        this.workOrderDetails = null;
        this.bomComponents = [];
        this.form.consumptions = [];
        this.existingProductionOrders = [];
        return;
      }

      try {
        // Fetch work order details and existing production orders
        const [workOrderResponse, productionOrdersResponse] = await Promise.all([
          axios.get(`/work-orders/${this.form.wo_id}`),
          axios.get('/production-orders', {
            params: { wo_id: this.form.wo_id }
          })
        ]);

        this.workOrderDetails = workOrderResponse.data.data || workOrderResponse.data;

        // Filter out current production order if editing
        this.existingProductionOrders = (productionOrdersResponse.data.data || productionOrdersResponse.data)
          .filter(po => this.isEditing ? po.production_id !== parseInt(this.id) : true);

        // Set default production quantity from remaining quantity
        const remainingQuantity = this.getRemainingQuantity();
        if (!this.isEditing || this.form.planned_quantity === 0) {
          this.form.planned_quantity = Math.min(remainingQuantity, this.workOrderDetails.planned_quantity);
        }

        // Fetch BOM components
        if (this.workOrderDetails.bom_id) {
          const bomResponse = await axios.get(`/boms/${this.workOrderDetails.bom_id}`);
          const bom = bomResponse.data.data || bomResponse.data;

          if (bom && bom.bomLines) {
            this.bomComponents = bom.bomLines;

            // Fetch items data
            const itemIds = this.bomComponents.map(line => line.item_id);
            const itemsResponse = await axios.get('/items', { params: { ids: itemIds.join(',') } });
            const items = itemsResponse.data.data || itemsResponse.data;

            // Create a lookup object for items
            items.forEach(item => {
              this.items[item.item_id] = item;
            });

            // Only create new consumptions if not editing or if no consumptions exist
            if (!this.isEditing || this.form.consumptions.length === 0) {
              // Calculate component quantities based on production quantity ratio
              const ratio = this.form.planned_quantity / bom.standard_quantity;

              this.form.consumptions = this.bomComponents.map(component => {
                return {
                  item_id: component.item_id,
                  planned_quantity: parseFloat((component.quantity * ratio).toFixed(4)),
                  actual_quantity: 0,
                  warehouse_id: this.getDefaultWarehouse(),
                  variance: 0
                };
              });
            }
          }
        }
      } catch (error) {
        console.error('Error loading work order details:', error);
        if (this.$toast) this.$toast.error('Failed to load work order details');
      }
    },

    getRemainingQuantity() {
      if (!this.workOrderDetails) return 0;

      const existingPlannedQtySum = this.existingProductionOrders
        .reduce((sum, po) => sum + parseFloat(po.planned_quantity || 0), 0);

      return this.workOrderDetails.planned_quantity - existingPlannedQtySum;
    },

    getRemainingQuantityClass() {
      const remaining = this.getRemainingQuantity();
      if (remaining <= 0) return 'text-danger';
      if (remaining < this.workOrderDetails.planned_quantity * 0.2) return 'text-warning';
      return 'text-success';
    },

    getDefaultWarehouse() {
      // Return first warehouse or empty if none available
      return this.warehouses.length > 0 ? this.warehouses[0].warehouse_id : '';
    },

    getItemName(itemId) {
      return this.items[itemId]?.name || 'Unknown Item';
    },

    getItemCode(itemId) {
      return this.items[itemId]?.item_code || '';
    },

    getItemUom(itemId) {
      return this.items[itemId]?.unitOfMeasure?.symbol || '';
    },

    formatDate(date) {
      if (!date) return 'N/A';
      return new Date(date).toLocaleDateString();
    },

    async saveProductionOrder() {
      this.errors = {};
      this.saving = true;

      try {
        // Calculate variances for consumptions
        this.form.consumptions.forEach(consumption => {
          consumption.variance = consumption.planned_quantity - (consumption.actual_quantity || 0);
        });

        let response;
        if (this.isEditing) {
          response = await axios.put(`/production-orders/${this.id}`, this.form);
        } else {
          response = await axios.post('/production-orders', this.form);
        }

        if (this.$toast) this.$toast.success(
          this.isEditing
            ? 'Production order updated successfully'
            : 'Production order created successfully'
        );

        // Redirect to production order detail view
        const productionId = this.isEditing
          ? this.id
          : (response.data.data?.production_id || response.data.production_id);

        this.$router.push(`/manufacturing/production-orders/${productionId}`);
      } catch (error) {
        console.error('Error saving production order:', error);

        // Reset errors object
        this.errors = {};

        if (error && error.response && error.response.data) {
          // Handle validation errors
          if (error.response.data.errors) {
            this.errors = error.response.data.errors;
            if (this.$toast) this.$toast.error('Please correct the errors before submitting');
          }
          // Handle single error message
          else {
            // Safely extract the error message, avoiding undefined properties
            const errorMessage = error.response.data.message ||
                                (error.response.data.error !== undefined ? error.response.data.error : null) ||
                                'Failed to save production order';
            if (this.$toast) this.$toast.error(errorMessage);
          }
        } else {
          if (this.$toast) this.$toast.error('Failed to save production order');
        }
      } finally {
        this.saving = false;
      }
    },

    cancel() {
      // Go back to previous page or to list
      if (this.$router.options.history.state.back) {
        this.$router.back();
      } else {
        this.$router.push('/manufacturing/production-orders');
      }
    }
  },

  watch: {
    'form.planned_quantity'() {
      // Recalculate consumption quantities when planned quantity changes
      if (this.bomComponents.length > 0 && this.workOrderDetails?.bom) {
        const ratio = this.form.planned_quantity / this.workOrderDetails.bom.standard_quantity;

        this.form.consumptions.forEach((consumption, index) => {
          const bomLine = this.bomComponents[index];
          if (bomLine) {
            consumption.planned_quantity = parseFloat((bomLine.quantity * ratio).toFixed(4));
          }
        });
      }
    }
  }
};
</script>

<style scoped>
/* Existing styles remain the same - adding new classes for dropdown and work order styling */

.text-success {
  color: #059669 !important;
  font-weight: 600;
}

.text-warning {
  color: #d97706 !important;
  font-weight: 600;
}

.text-danger {
  color: #dc2626 !important;
  font-weight: 600;
}

.input-hint {
  font-size: 0.75rem;
  color: #64748b;
  margin-top: 0.25rem;
  font-style: italic;
}

.required {
  color: #dc2626;
}

/* Dropdown Styles */
.dropdown {
  position: relative;
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #d1d5db;
  border-top: none;
  border-radius: 0 0 0.375rem 0.375rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  max-height: 300px;
  overflow-y: auto;
  z-index: 100;
}

.dropdown-item {
  padding: 0.75rem 1rem;
  cursor: pointer;
  border-bottom: 1px solid #f3f4f6;
  transition: background-color 0.2s;
}

.dropdown-item:last-child {
  border-bottom: none;
}

.dropdown-item:hover {
  background-color: #f9fafb;
}

.dropdown-item.text-muted {
  color: #6b7280;
  cursor: default;
}

.dropdown-item.text-muted:hover {
  background-color: transparent;
}

.work-order-info {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.25rem;
}

.work-order-info strong {
  font-weight: 600;
  color: #111827;
}

.work-order-status {
  font-size: 0.75rem;
  padding: 0.125rem 0.5rem;
  border-radius: 9999px;
  font-weight: 500;
}

.status-draft {
  background-color: #f3f4f6;
  color: #374151;
}

.status-in-progress {
  background-color: #dbeafe;
  color: #1d4ed8;
}

.status-completed {
  background-color: #d1fae5;
  color: #059669;
}

.status-cancelled {
  background-color: #fee2e2;
  color: #dc2626;
}

.work-order-details {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.25rem;
  font-size: 0.875rem;
}

.item-name {
  color: #2563eb;
  font-weight: 500;
}

.quantity {
  color: #6b7280;
}

.work-order-dates {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.8rem;
  color: #6b7280;
}

.date-info {
  font-weight: 400;
}

/* Base container styling */
.production-order-form {
  max-width: 1200px;
  margin: 0 auto;
  padding: 1.25rem;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  color: #334155;
}

/* Page header styling */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e2e8f0;
}

.page-header h1 {
  font-size: 1.75rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
}

.actions {
  display: flex;
  gap: 0.75rem;
}

/* Card styling */
.card {
  background: #ffffff;
  border-radius: 8px;
  box-shadow: 0 1px 8px rgba(0, 0, 0, 0.08);
  margin-bottom: 2rem;
  border: none;
  overflow: hidden;
}

.card-body {
  padding: 1.75rem;
}

.card-footer {
  background: #f8fafc;
  padding: 1.25rem 1.75rem;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

/* Form section styling */
.form-section {
  margin-bottom: 2.5rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}

.form-section:last-child {
  margin-bottom: 0;
  padding-bottom: 0;
  border-bottom: none;
}

.form-section h2 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 1.25rem 0;
}

.section-description {
  font-size: 0.95rem;
  color: #64748b;
  margin-bottom: 1.25rem;
}

/* Form layout */
.form-row {
  display: flex;
  flex-wrap: wrap;
  margin: 0 -0.75rem 1.25rem -0.75rem;
  gap: 0;
}

.form-row:last-child {
  margin-bottom: 0;
}

.form-group {
  flex: 1 1 calc(50% - 1.5rem);
  margin: 0 0.75rem 1.25rem 0.75rem;
  min-width: 250px;
}

/* Form controls */
label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  font-size: 0.95rem;
  color: #475569;
}

input[type="text"],
input[type="date"],
input[type="number"],
select,
.form-control {
  width: 100%;
  padding: 0.65rem 0.85rem;
  font-size: 0.95rem;
  line-height: 1.5;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  color: #334155;
  background-color: #ffffff;
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

select {
  appearance: none;
  background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23475569' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
  background-repeat: no-repeat;
  background-position: right 0.75rem center;
  background-size: 1em;
  padding-right: 2.5rem;
}

input:focus,
select:focus,
.form-control:focus {
  border-color: #3b82f6;
  outline: 0;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
}

input:read-only {
  background-color: #f1f5f9;
  cursor: not-allowed;
}

input.error,
select.error,
.form-control.error {
  border-color: #ef4444;
}

.error-message {
  color: #ef4444;
  font-size: 0.8rem;
  margin-top: 0.35rem;
}

/* Info panel styling */
.info-panel {
  background-color: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  margin: 1.25rem 0 1.75rem 0;
  overflow: hidden;
}

.info-panel-title {
  background-color: #f1f5f9;
  padding: 0.75rem 1.25rem;
  font-weight: 600;
  font-size: 0.95rem;
  color: #334155;
  border-bottom: 1px solid #e2e8f0;
}

.info-panel-content {
  padding: 1rem 1.25rem;
}

.info-row {
  display: flex;
  padding: 0.5rem 0;
  border-bottom: 1px solid #e2e8f0;
}

.info-row:last-child {
  border-bottom: none;
}

.info-label {
  flex: 0 0 150px;
  font-weight: 500;
  color: #64748b;
}

.info-value {
  flex: 1;
  color: #334155;
}

/* Table styling */
.table-responsive {
  overflow-x: auto;
  margin-bottom: 1.5rem;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
}

.table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.95rem;
}

.table thead th {
  background-color: #f1f5f9;
  color: #475569;
  font-weight: 600;
  text-align: left;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #e2e8f0;
}

.table tbody td {
  padding: 0.85rem 1rem;
  border-bottom: 1px solid #e2e8f0;
  vertical-align: middle;
}

.table tbody tr:last-child td {
  border-bottom: none;
}

.table tbody tr:hover {
  background-color: #f8fafc;
}

.table input,
.table select {
  margin: 0;
  width: 100%;
}

.table .error-message {
  white-space: nowrap;
}

/* Item info in table cells */
.item-name {
  font-weight: 500;
  margin-bottom: 0.25rem;
}

.item-code {
  font-size: 0.85rem;
  color: #64748b;
}

/* Button styling */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.6rem 1.25rem;
  font-size: 0.95rem;
  font-weight: 500;
  line-height: 1.5;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
}

.btn-primary {
  background-color: #3b82f6;
  border: 1px solid #3b82f6;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: #2563eb;
  border-color: #2563eb;
}

.btn-primary:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.btn-secondary {
  background-color: #f1f5f9;
  border: 1px solid #cbd5e1;
  color: #475569;
}

.btn-secondary:hover {
  background-color: #e2e8f0;
  color: #334155;
}

/* Loading state */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  background-color: #ffffff;
  border-radius: 8px;
  box-shadow: 0 1px 8px rgba(0, 0, 0, 0.08);
}

.loading-container i {
  font-size: 2.5rem;
  color: #3b82f6;
  margin-bottom: 1rem;
}

.loading-container span {
  font-size: 1rem;
  color: #64748b;
}

.fa-spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Material section specific */
.material-section {
  margin-top: 2rem;
}

/* Responsive styling */
@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .page-header h1 {
    margin-bottom: 1rem;
  }

  .form-row {
    flex-direction: column;
  }

  .form-group {
    flex: 0 0 100%;
    margin-bottom: 1rem;
  }

  .info-row {
    flex-direction: column;
  }

  .info-label {
    margin-bottom: 0.25rem;
  }

  .card-footer {
    flex-direction: column;
  }

  .btn {
    width: 100%;
  }
}

/* Touch device optimizations */
@media (max-width: 576px) {
  .card-body {
    padding: 1.25rem;
  }

  input,
  select,
  .btn {
    padding: 0.75rem 1rem;
  }

  .table-responsive {
    margin-left: -1.25rem;
    margin-right: -1.25rem;
    width: calc(100% + 2.5rem);
    border-left: none;
    border-right: none;
    border-radius: 0;
  }
}
</style>
