<!-- src/views/manufacturing/ProductionOrderDetail.vue -->
<template>
    <div class="production-order-detail">
      <!-- Toast Notifications -->
      <div class="toast-container">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          :class="['toast', `toast-${toast.type}`]"
          @click="removeToast(toast.id)"
        >
          <i :class="getToastIcon(toast.type)"></i>
          <span>{{ toast.message }}</span>
          <button class="toast-close" @click.stop="removeToast(toast.id)">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>

      <div class="page-header">
        <h1>Job Order Process Details</h1>
        <div class="actions">
          <router-link to="/manufacturing/production-orders" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
          </router-link>

          <!-- Status-based Action Buttons -->
          <template v-if="productionOrder">
            <!-- Draft Status Actions -->
            <template v-if="productionOrder.status === 'Draft'">
              <router-link
                :to="`/manufacturing/production-orders/${productionId}/edit`"
                class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit
              </router-link>
              <button
                @click="printProductionOrder"
                class="btn btn-info">
                <i class="fas fa-print"></i> Print
              </button>
              <button
                @click="confirmIssueMaterials"
                class="btn btn-warning"
                :disabled="!allMaterialsAvailable">
                <i class="fas fa-boxes"></i> Issue Materials
              </button>
              <button
                @click="confirmDelete"
                class="btn btn-danger">
                <i class="fas fa-trash"></i> Delete
              </button>
            </template>

            <!-- Materials Issued Status Actions -->
            <template v-if="productionOrder.status === 'Materials Issued'">
              <button
                @click="confirmStartProduction"
                class="btn btn-success">
                <i class="fas fa-play"></i> Start Production
              </button>
              <button
                @click="confirmCancelProduction"
                class="btn btn-warning">
                <i class="fas fa-times"></i> Cancel Production
              </button>
            </template>

            <!-- In Progress Status Actions -->
            <template v-if="productionOrder.status === 'In Progress'">
              <button
                @click="confirmCompleteProduction"
                class="btn btn-success">
                <i class="fas fa-check"></i> Complete Production
              </button>
              <button
                @click="confirmCancelProduction"
                class="btn btn-warning">
                <i class="fas fa-times"></i> Cancel Production
              </button>
            </template>

            <!-- Completed Status Actions -->
            <template v-if="productionOrder.status === 'Completed'">
              <button
                @click="viewProductionSummary"
                class="btn btn-primary">
                <i class="fas fa-chart-line"></i> View Summary
              </button>
            </template>

            <!-- Cancelled Status Actions -->
            <template v-if="productionOrder.status === 'Cancelled'">
              <button
                @click="confirmReactivate"
                class="btn btn-primary">
                <i class="fas fa-undo"></i> Reactivate
              </button>
            </template>
          </template>
        </div>
      </div>

      <!-- Progress Steps -->
      <div class="progress-section" v-if="productionOrder">
        <div class="progress-bar">
          <div class="progress-step" :class="{ active: isStepActive(1), completed: isStepCompleted(1) }">
            <div class="step-icon">1</div>
            <div class="step-label">Draft</div>
          </div>
          <div class="progress-line" :class="{ completed: isStepCompleted(1) }"></div>

          <div class="progress-step" :class="{ active: isStepActive(2), completed: isStepCompleted(2) }">
            <div class="step-icon">2</div>
            <div class="step-label">Materials Issued</div>
          </div>
          <div class="progress-line" :class="{ completed: isStepCompleted(2) }"></div>

          <div class="progress-step" :class="{ active: isStepActive(3), completed: isStepCompleted(3) }">
            <div class="step-icon">3</div>
            <div class="step-label">In Progress</div>
          </div>
          <div class="progress-line" :class="{ completed: isStepCompleted(3) }"></div>

          <div class="progress-step" :class="{ active: isStepActive(4), completed: isStepCompleted(4) }">
            <div class="step-icon">4</div>
            <div class="step-label">Completed</div>
          </div>
        </div>
      </div>

      <div v-if="loading" class="loading-container">
        <i class="fas fa-spinner fa-spin"></i>
        <span>Loading Job Order Process details...</span>
      </div>

      <div v-else-if="!productionOrder" class="error-container">
        <i class="fas fa-exclamation-triangle"></i>
        <h3>Job Order Process Not Found</h3>
        <p>The requested Job Order Process could not be found.</p>
        <router-link to="/manufacturing/production-orders" class="btn btn-primary">
          Back to Job Order Process
        </router-link>
      </div>

      <div v-else class="detail-content">
        <div class="card detail-card">
          <div class="card-header">
            <h2>Job Order Process Information</h2>
            <div class="status-badge" :class="getStatusClass(productionOrder.status)">
              {{ productionOrder.status }}
            </div>
          </div>
          <div class="card-body">
            <div class="detail-grid">
              <div class="detail-item">
                <div class="detail-label">Job Order Process #</div>
                <div class="detail-value">{{ productionOrder.production_number }}</div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Job Order Process Date</div>
                <div class="detail-value">{{ formatDate(productionOrder.production_date) }}</div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Job Order</div>
                <div class="detail-value">
                  <router-link :to="`/manufacturing/work-orders/${productionOrder.wo_id}`">
                    {{ workOrder?.wo_number || 'N/A' }}
                  </router-link>
                </div>
              </div>
            <div class="detail-item">
                <div class="detail-label">Product</div>
                    <div class="detail-value">
                        <router-link
                        v-if="workOrder?.item && workOrder.item.item_id"
                        :to="`/items/${workOrder.item.item_id}`"
                        class="item-link"
                        :title="`View details for ${workOrder.item.name || workOrder.item.item_code}`"
                        >
                        {{ workOrder.item.name || workOrder.item.item_code || 'Unknown Product' }}
                        </router-link>
                        <span v-else class="text-muted">{{ workOrder?.item?.item_code || 'N/A' }}</span>
                        <small v-if="workOrder?.item?.item_code" class="text-muted item-code d-block">
                        {{ workOrder.item.item_code }}
                        </small>
                    </div>
                </div>
            </div>
          </div>
        </div>

        <!-- Material Status Card -->
        <div class="card detail-card" v-if="materialStatus">
          <div class="card-header">
            <h2>Material Status</h2>
            <div class="header-actions">
              <button
                @click="refreshMaterialStatus"
                class="btn btn-sm btn-secondary">
                <i class="fas fa-refresh"></i> Refresh
              </button>
            </div>
          </div>
          <div class="card-body">
            <div class="status-summary">
              <div class="status-item">
                <div class="status-label">All Materials Available</div>
                <div class="status-value" :class="allMaterialsAvailable ? 'text-success' : 'text-danger'">
                  {{ allMaterialsAvailable ? 'Yes' : 'No' }}
                </div>
              </div>
              <div class="status-item">
                <div class="status-label">Total Planned Value</div>
                <div class="status-value">${{ materialStatus.total_planned_value?.toFixed(2) || '0.00' }}</div>
              </div>
              <div class="status-item">
                <div class="status-label">Total Actual Value</div>
                <div class="status-value">${{ materialStatus.total_actual_value?.toFixed(2) || '0.00' }}</div>
              </div>
            </div>
          </div>
        </div>

        <div class="card detail-card" v-if="consumptions.length > 0">
          <div class="card-header">
            <h2>Material Consumption</h2>
            <div v-if="productionOrder.status === 'Draft'" class="header-actions">
              <button
                @click="saveAllConsumptions"
                :disabled="!hasUnsavedChanges"
                class="btn btn-sm btn-primary">
                <i class="fas fa-save"></i> Save Changes
              </button>
            </div>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <th>Item</th>
                    <th>Warehouse</th>
                    <th>Planned Quantity</th>
                    <th>Available Stock</th>
                    <th>Actual Quantity</th>
                    <th>Variance</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                    <tr v-for="consumption in consumptions" :key="consumption.consumption_id">
                        <!-- Kolom Item yang diupdate -->
                        <td>
                        <div class="item-info">
                            <router-link
                            v-if="consumption.item && consumption.item.item_id"
                            :to="`/items/${consumption.item.item_id}`"
                            class="item-link"
                            :title="`View details for ${consumption.item.name || consumption.item.item_code}`"
                            >
                            <div class="item-name">{{ consumption.item.name || consumption.item.item_code || 'Unknown Item' }}</div>
                            </router-link>
                            <div v-else class="item-name text-muted">{{ consumption.item?.name || 'Unknown Item' }}</div>

                            <div v-if="consumption.item?.item_code" class="item-code">{{ consumption.item.item_code }}</div>

                            <!-- Optional: Tambahan info item seperti category, unit, dll -->
                            <div v-if="consumption.item?.category" class="item-category">
                            <small class="text-muted">{{ consumption.item.category }}</small>
                            </div>
                        </div>
                        </td>

                        <td>{{ consumption.warehouse?.name || 'N/A' }}</td>
                        <td>{{ consumption.planned_quantity }}</td>
                        <td class="stock-cell">
                        <span :class="getStockClass(consumption)">
                            {{ getAvailableStock(consumption) }}
                        </span>
                        <div v-if="getShortage(consumption) > 0" class="shortage-info">
                            Short: {{ getShortage(consumption) }}
                        </div>
                        </td>

                        <!-- Editable Actual Quantity Column -->
                        <td class="actual-quantity-cell">
                          <div v-if="productionOrder.status === 'Draft'" class="editable-quantity">
                            <input
                              v-model.number="editableConsumptions[consumption.consumption_id]"
                              @input="onConsumptionChange(consumption.consumption_id)"
                              type="number"
                              min="0"
                              step="0.01"
                              class="form-control form-control-sm"
                              :class="{ 'has-changes': hasChanges(consumption.consumption_id) }"
                            />
                            <div v-if="hasChanges(consumption.consumption_id)" class="change-indicator">
                              <i class="fas fa-asterisk text-warning"></i>
                            </div>
                          </div>
                          <div v-else class="readonly-quantity">
                            {{ consumption.actual_quantity || '0' }}
                          </div>
                        </td>

                        <td>
                        <div class="variance" :class="getVarianceClass(consumption)">
                            {{ getVarianceForConsumption(consumption) }}
                        </div>
                        </td>
                        <td>
                        <span class="status-badge" :class="getMaterialStatusClass(consumption)">
                            {{ getMaterialStatus(consumption) }}
                        </span>
                        </td>
                    </tr>
                </tbody>
              </table>
            </div>

            <!-- Show unsaved changes warning -->
            <div v-if="hasUnsavedChanges && productionOrder.status === 'Draft'" class="unsaved-changes-warning">
              <i class="fas fa-exclamation-triangle"></i>
              <span>You have unsaved changes. Click "Save Changes" to apply them.</span>
            </div>
          </div>
        </div>

        <div class="card detail-card" v-else>
          <div class="card-header">
            <h2>Material Consumption</h2>
          </div>
          <div class="card-body">
            <div class="empty-state">
              <i class="fas fa-box-open"></i>
              <p>No material consumption records found.</p>
              <p v-if="productionOrder.status === 'Draft'">
                Materials will be auto-generated from BOM when production starts.
              </p>
            </div>
          </div>
        </div>

        <div v-if="productionOrder.status === 'Completed'" class="card detail-card">
          <div class="card-header">
            <h2>Job Order Process Summary</h2>
          </div>
          <div class="card-body">
            <div class="summary-stats">
              <div class="stat-item">
                <div class="stat-label">Efficiency</div>
                <div class="stat-value">{{ calculateEfficiency() }}%</div>
              </div>
              <div class="stat-item">
                <div class="stat-label">Material Utilization</div>
                <div class="stat-value">{{ calculateMaterialUtilization() }}%</div>
              </div>
              <div class="stat-item">
              <div class="stat-label">Completion Date</div>
              <div class="stat-value">{{ formatDate(productionOrder.job_tickets && productionOrder.job_tickets.length > 0 ? productionOrder.job_tickets[0].date : productionOrder.updated_at) }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Issue Materials Modal -->
      <ConfirmationModal
        v-if="showIssueMaterialsModal"
        title="Issue Materials"
        :message="`Are you sure you want to issue materials for Job Order Process <strong>${productionOrder?.production_number}</strong>?<br><br>This will move materials from Raw Materials warehouse to WIP warehouse and change status to 'Materials Issued'.`"
        confirm-button-text="Issue Materials"
        confirm-button-class="btn btn-warning"
        @confirm="issueMaterials"
        @close="cancelIssueMaterials"
      />

      <!-- Start Production Confirmation Modal -->
      <ConfirmationModal
        v-if="showStartModal"
        title="Start Job Order Process"
        :message="`Are you sure you want to start production for <strong>${productionOrder?.production_number}</strong>?<br><br>This will change the status to 'In Progress' and production activities can begin.`"
        confirm-button-text="Start Job Order Process"
        confirm-button-class="btn btn-success"
        @confirm="startProduction"
        @close="cancelStart"
      />

      <!-- Complete Production Modal -->
      <div v-if="showCompleteModal" class="modal-overlay" @click="closeCompleteModal">
        <div class="modal" @click.stop>
          <div class="modal-header">
            <h3>Complete Job Order Process</h3>
            <button @click="closeCompleteModal" class="btn-close">&times;</button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label>Actual Quantity Produced</label>
              <input
                v-model="completionForm.actual_quantity"
                type="number"
                min="0.01"
                step="0.01"
                class="form-control"
                placeholder="Enter actual quantity produced"
              />
              <small class="form-text">Planned: {{ productionOrder?.planned_quantity }}</small>
            </div>
            <div class="form-group">
              <label>Quality Notes (Optional)</label>
              <textarea
                v-model="completionForm.quality_notes"
                class="form-control"
                rows="3"
                placeholder="Enter any quality observations"
              ></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button @click="closeCompleteModal" class="btn btn-secondary">Cancel</button>
            <button
              @click="completeProduction"
              class="btn btn-success"
              :disabled="!completionForm.actual_quantity || completionForm.actual_quantity <= 0"
            >
              Complete Job Order Process
            </button>
          </div>
        </div>
      </div>

      <!-- Cancel Production Confirmation Modal -->
      <ConfirmationModal
        v-if="showCancelModal"
        title="Cancel Job Order Process"
        :message="`Are you sure you want to cancel Job Order Process for <strong>${productionOrder?.production_number}</strong>?<br><br>This will change the status to 'Cancelled'.`"
        confirm-button-text="Cancel Job Order Process"
        confirm-button-class="btn btn-warning"
        @confirm="cancelProduction"
        @close="cancelCancelAction"
      />

      <!-- Delete Confirmation Modal -->
      <ConfirmationModal
        v-if="showDeleteModal"
        title="Delete Job Order Process"
        :message="`Are you sure you want to delete Job Order Process order <strong>${productionOrder?.production_number}</strong>? This action cannot be undone.`"
        confirm-button-text="Delete"
        confirm-button-class="btn btn-danger"
        @confirm="deleteProductionOrder"
        @close="cancelDelete"
      />

      <!-- Reactivate Confirmation Modal -->
      <ConfirmationModal
        v-if="showReactivateModal"
        title="Reactivate Job Order Process"
        :message="`Are you sure you want to reactivate Job Order Process <strong>${productionOrder?.production_number}</strong>?<br><br>This will change the status back to 'Draft'.`"
        confirm-button-text="Reactivate"
        confirm-button-class="btn btn-primary"
        @confirm="reactivateProduction"
        @close="cancelReactivate"
      />
    </div>
  </template>

  <script>
  import axios from 'axios';

  export default {
    name: 'ProductionOrderDetail',
    props: {
      productionId: {
        type: [Number, String],
        required: true
      }
    },
    data() {
      return {
        productionOrder: null,
        workOrder: null,
        consumptions: [],
        materialStatus: null,
        loading: true,
        showIssueMaterialsModal: false,
        showStartModal: false,
        showCompleteModal: false,
        showCancelModal: false,
        showDeleteModal: false,
        showReactivateModal: false,
        completionForm: {
          actual_quantity: 0,
          quality_notes: ''
        },
        // Toast system
        toasts: [],
        toastIdCounter: 0,

        // Editable consumption system
        editableConsumptions: {}, // Track editable values
        originalConsumptions: {}, // Track original values
        hasUnsavedChanges: false
      };
    },
    computed: {
      allMaterialsAvailable() {
        return this.materialStatus?.all_materials_available || false;
      }
    },
    created() {
      this.fetchProductionOrder();
    },
    methods: {
      // Toast Methods
      showToast(message, type = 'info', duration = 5000) {
        const toast = {
          id: this.toastIdCounter++,
          message,
          type,
          duration
        };

        this.toasts.push(toast);

        setTimeout(() => {
          this.removeToast(toast.id);
        }, duration);
      },

      removeToast(id) {
        this.toasts = this.toasts.filter(toast => toast.id !== id);
      },

      getToastIcon(type) {
        switch (type) {
          case 'success':
            return 'fas fa-check-circle';
          case 'error':
            return 'fas fa-exclamation-circle';
          case 'warning':
            return 'fas fa-exclamation-triangle';
          default:
            return 'fas fa-info-circle';
        }
      },

      showError(msg) { this.showToast(msg, 'error'); },
      showSuccess(msg) { this.showToast(msg, 'success'); },
      showWarning(msg) { this.showToast(msg, 'warning'); },
      showInfo(msg) { this.showToast(msg, 'info'); },

      // Progress Steps
      isStepActive(step) {
        const currentStep = this.getStepNumber(this.productionOrder?.status);
        return currentStep === step;
      },

      isStepCompleted(step) {
        const currentStep = this.getStepNumber(this.productionOrder?.status);
        return currentStep > step;
      },

      getStepNumber(status) {
        switch (status) {
          case 'Draft': return 1;
          case 'Materials Issued': return 2;
          case 'In Progress': return 3;
          case 'Completed': return 4;
          default: return 1;
        }
      },

      async fetchProductionOrder() {
        this.loading = true;
        try {
          const response = await axios.get(`/production-orders/${this.productionId}`);
          this.productionOrder = response.data.data || response.data;

          // Get consumptions
          if (this.productionOrder.production_consumptions) {
            this.consumptions = this.productionOrder.production_consumptions;

            // Initialize editable consumptions
            this.initializeEditableConsumptions();
          }

          // Initialize completion form
          this.completionForm.actual_quantity = this.productionOrder.planned_quantity;

          // Fetch work order details
          if (this.productionOrder.wo_id) {
            await this.fetchWorkOrder(this.productionOrder.wo_id);
          }

          // Fetch material status
          await this.fetchMaterialStatus();
        } catch (error) {
          console.error('Error fetching Job Order Process:', error);
          this.showError('Failed to load Job Order Process');
        } finally {
          this.loading = false;
        }
      },

      async fetchWorkOrder(workOrderId) {
        try {
          const response = await axios.get(`/work-orders/${workOrderId}`);
          this.workOrder = response.data.data || response.data;
        } catch (error) {
          console.error('Error fetching work order:', error);
          this.showError('Failed to load work order details');
        }
      },

      async fetchMaterialStatus() {
        try {
          const response = await axios.get(`/production-orders/${this.productionId}/material-status`);
          this.materialStatus = response.data.data || response.data;
        } catch (error) {
          console.error('Error fetching material status:', error);
          // Don't show error for material status as it's not critical
        }
      },

      async refreshMaterialStatus() {
        await this.fetchMaterialStatus();
        this.showInfo('Material status refreshed');
      },

      // === NEW: Editable Consumption Methods ===
      initializeEditableConsumptions() {
        this.editableConsumptions = {};
        this.originalConsumptions = {};

        this.consumptions.forEach(consumption => {
          const consumptionId = consumption.consumption_id;
          const actualQty = consumption.actual_quantity || 0;

          this.editableConsumptions[consumptionId] = actualQty;
          this.originalConsumptions[consumptionId] = actualQty;
        });

        this.hasUnsavedChanges = false;
      },

      onConsumptionChange() {
        this.checkForUnsavedChanges();
      },

      checkForUnsavedChanges() {
        this.hasUnsavedChanges = Object.keys(this.editableConsumptions).some(
          consumptionId => this.hasChanges(consumptionId)
        );
      },

      hasChanges(consumptionId) {
        const original = this.originalConsumptions[consumptionId] || 0;
        const current = this.editableConsumptions[consumptionId] || 0;
        return original !== current;
      },

      async saveAllConsumptions() {
        if (!this.hasUnsavedChanges) {
          this.showInfo('No changes to save');
          return;
        }

        const changedConsumptions = Object.keys(this.editableConsumptions)
          .filter(consumptionId => this.hasChanges(consumptionId))
          .map(consumptionId => ({
            consumption_id: consumptionId,
            actual_quantity: this.editableConsumptions[consumptionId] || 0
          }));

        if (changedConsumptions.length === 0) {
          this.showInfo('No changes to save');
          return;
        }

        try {
          // Save each changed consumption
          for (const change of changedConsumptions) {
            await axios.put(
              `/production-orders/${this.productionId}/consumptions/${change.consumption_id}`,
              {
                actual_quantity: change.actual_quantity
              }
            );
          }

          this.showSuccess('Consumption quantities updated successfully');

          // Refresh data and reset tracking
          await this.fetchProductionOrder();

        } catch (error) {
          console.error('Error saving consumptions:', error);
          this.showError(error.response?.data?.message || 'Failed to save consumption changes');
        }
      },

      getVarianceForConsumption(consumption) {
        const consumptionId = consumption.consumption_id;
        const planned = parseFloat(consumption.planned_quantity) || 0;

        // Use editable value if available, otherwise use actual quantity
        const actual = this.editableConsumptions[consumptionId] !== undefined
          ? parseFloat(this.editableConsumptions[consumptionId]) || 0
          : parseFloat(consumption.actual_quantity) || 0;

        const variance = planned - actual;

        if (variance === 0) return '0';

        return variance > 0
          ? `+${variance.toFixed(2)}`
          : variance.toFixed(2);
      },
      // === END: Editable Consumption Methods ===

      // Status Transition Methods
      confirmIssueMaterials() {
        if (!this.allMaterialsAvailable) {
          this.showError('Cannot issue materials - insufficient stock for some items');
          return;
        }
        this.showIssueMaterialsModal = true;
      },

      async issueMaterials() {
        try {
          // Prepare consumption data from editable quantities
          const consumptions = this.consumptions.map(c => ({
            consumption_id: c.consumption_id,
            actual_quantity: this.editableConsumptions[c.consumption_id] || c.planned_quantity
          }));

          await axios.post(`/production-orders/${this.productionId}/issue-materials`, {
            consumptions
          });

          this.showSuccess('Materials issued successfully');
          this.fetchProductionOrder();
        } catch (error) {
          console.error('Error issuing materials:', error);
          this.showError(error.response?.data?.message || 'Failed to issue materials');
        } finally {
          this.showIssueMaterialsModal = false;
        }
      },

      cancelIssueMaterials() {
        this.showIssueMaterialsModal = false;
      },

      confirmStartProduction() {
        this.showStartModal = true;
      },

      async startProduction() {
        try {
          await axios.post(`/production-orders/${this.productionId}/start-production`);

          this.showSuccess('Production started successfully');
          this.fetchProductionOrder();
        } catch (error) {
          console.error('Error starting production:', error);
          this.showError(error.response?.data?.message || 'Failed to start production');
        } finally {
          this.showStartModal = false;
        }
      },

      cancelStart() {
        this.showStartModal = false;
      },

      confirmCompleteProduction() {
        this.showCompleteModal = true;
      },

      async completeProduction() {
        try {
          await axios.post(`/production-orders/${this.productionId}/complete`, this.completionForm);

          this.showSuccess('Job Order Process completed successfully');
          this.fetchProductionOrder();
        } catch (error) {
          console.error('Error completing Job Order Process:', error);
          this.showError(error.response?.data?.message || 'Failed to complete Job Order Process');
        } finally {
          this.showCompleteModal = false;
        }
      },

      closeCompleteModal() {
        this.showCompleteModal = false;
      },

      confirmCancelProduction() {
        this.showCancelModal = true;
      },

      async cancelProduction() {
        try {
          await axios.patch(`/production-orders/${this.productionId}/status`, {
            status: 'Cancelled'
          });

          this.showSuccess('Job Order Process cancelled successfully');
          this.fetchProductionOrder();
        } catch (error) {
          console.error('Error cancelling Job Order Process:', error);
          this.showError(error.response?.data?.message || 'Failed to cancel Job Order Process');
        } finally {
          this.showCancelModal = false;
        }
      },

      cancelCancelAction() {
        this.showCancelModal = false;
      },

      confirmReactivate() {
        this.showReactivateModal = true;
      },

      async reactivateProduction() {
        try {
          await axios.patch(`/production-orders/${this.productionId}/status`, {
            status: 'Draft'
          });

          this.showSuccess('Job Order Process reactivated successfully');
          this.fetchProductionOrder();
        } catch (error) {
          console.error('Error reactivating Job Order Process:', error);
          this.showError(error.response?.data?.message || 'Failed to reactivate Job Order Process');
        } finally {
          this.showReactivateModal = false;
        }
      },

      cancelReactivate() {
        this.showReactivateModal = false;
      },

      // Utility Methods
      formatDate(date) {
        if (!date) return 'N/A';
        return new Date(date).toLocaleDateString();
      },

      getStatusClass(status) {
        switch (status) {
          case 'Draft': return 'status-draft';
          case 'Materials Issued': return 'status-materials-issued';
          case 'In Progress': return 'status-in-progress';
          case 'Completed': return 'status-completed';
          case 'Cancelled': return 'status-cancelled';
          default: return '';
        }
      },

      getAvailableStock(consumption) {
        const material = this.materialStatus?.material_details?.find(
          m => m.consumption_id === consumption.consumption_id
        );
        return material?.available_stock || 0;
      },

      getShortage(consumption) {
        const material = this.materialStatus?.material_details?.find(
          m => m.consumption_id === consumption.consumption_id
        );
        return material?.shortage || 0;
      },

      getStockClass(consumption) {
        const material = this.materialStatus?.material_details?.find(
          m => m.consumption_id === consumption.consumption_id
        );
        return material?.is_available ? 'text-success' : 'text-danger';
      },

      getMaterialStatus(consumption) {
        const actual = this.editableConsumptions[consumption.consumption_id] !== undefined
          ? this.editableConsumptions[consumption.consumption_id]
          : consumption.actual_quantity;
        return actual > 0 ? 'Issued' : 'Pending';
      },

      getMaterialStatusClass(consumption) {
        const actual = this.editableConsumptions[consumption.consumption_id] !== undefined
          ? this.editableConsumptions[consumption.consumption_id]
          : consumption.actual_quantity;
        return actual > 0 ? 'status-issued' : 'status-pending';
      },

      getVariance(consumption) {
        const planned = parseFloat(consumption.planned_quantity) || 0;
        const actual = parseFloat(consumption.actual_quantity) || 0;
        const variance = planned - actual;

        if (variance === 0) return '0';

        return variance > 0
          ? `+${variance.toFixed(2)}`
          : variance.toFixed(2);
      },

      getVarianceClass(consumption) {
        const planned = parseFloat(consumption.planned_quantity) || 0;
        const actual = parseFloat(consumption.actual_quantity) || 0;
        const variance = planned - actual;

        if (variance === 0) return 'no-variance';
        if (Math.abs(variance) / planned <= 0.05) return 'low-variance';
        if (variance > 0) return 'positive-variance';
        return 'negative-variance';
      },

      calculateEfficiency() {
        if (!this.productionOrder || !this.productionOrder.planned_quantity || this.productionOrder.planned_quantity <= 0) {
          return '0';
        }

        const efficiency = (this.productionOrder.actual_quantity / this.productionOrder.planned_quantity) * 100;
        return efficiency.toFixed(2);
      },

      calculateMaterialUtilization() {
        if (!this.consumptions || this.consumptions.length === 0) {
          return '0';
        }

        let plannedTotal = 0;
        let actualTotal = 0;

        this.consumptions.forEach(consumption => {
          plannedTotal += parseFloat(consumption.planned_quantity) || 0;
          actualTotal += parseFloat(consumption.actual_quantity) || 0;
        });

        if (plannedTotal <= 0) {
          return '0';
        }

        const utilization = (actualTotal / plannedTotal) * 100;
        return utilization.toFixed(2);
      },

      printProductionOrder() {
        this.$router.push({
        name: 'PrintProductionOrder',
        params: { productionId: this.productionId }
        });
      },

      async viewProductionSummary() {
        try {
          const response = await axios.get(`/production-orders/${this.productionId}/production-summary`);
          // Could navigate to a detailed summary page or show in modal
          console.log('Job Order Process Summary:', response.data);
          this.showInfo('Job Order Process summary loaded - check console for details');
        } catch (error) {
          this.showError('Failed to load Job Order Process summary');
        }
      },

      confirmDelete() {
        this.showDeleteModal = true;
      },

      async deleteProductionOrder() {
        try {
          await axios.delete(`/production-orders/${this.productionId}`);
          this.showSuccess('Production order deleted successfully');
          this.$router.push('/manufacturing/production-orders');
        } catch (error) {
          console.error('Error deleting production order:', error);
          this.showError('Failed to delete production order');
        } finally {
          this.showDeleteModal = false;
        }
      },

      cancelDelete() {
        this.showDeleteModal = false;
      }
    }
  };
  </script>

  <style scoped>
  /* Existing styles plus new ones for editable consumptions */

  /* === NEW: Editable Consumption Styles === */
  .actual-quantity-cell {
    min-width: 120px;
  }

  .editable-quantity {
    position: relative;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .editable-quantity .form-control-sm {
    width: 80px;
    padding: 4px 8px;
    font-size: 13px;
    border: 1px solid #ddd;
    border-radius: 4px;
    transition: all 0.2s;
  }

  .editable-quantity .form-control-sm:focus {
    border-color: #2196f3;
    box-shadow: 0 0 0 2px rgba(33, 150, 243, 0.2);
    outline: none;
  }

  .editable-quantity .form-control-sm.has-changes {
    border-color: #f59e0b;
    background-color: #fffbeb;
  }

  .change-indicator {
    color: #f59e0b;
    font-size: 10px;
  }

  .readonly-quantity {
    font-weight: 500;
    color: var(--gray-700);
  }

  .unsaved-changes-warning {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px;
    background-color: #fffbeb;
    border: 1px solid #fbbf24;
    border-radius: 6px;
    color: #92400e;
    margin-top: 16px;
    font-size: 14px;
  }

  .unsaved-changes-warning i {
    color: #f59e0b;
  }

  .header-actions .btn {
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
  /* === END: Editable Consumption Styles === */

  /* Existing styles plus new ones for progress bar and material status */

  .progress-section {
    margin: 2rem 0;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
  }

  .progress-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 20px 0;
  }

  .progress-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
  }

  .step-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e0e0e0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-bottom: 8px;
    color: #666;
  }

  .progress-step.active .step-icon {
    background: #2196f3;
    color: white;
  }

  .progress-step.completed .step-icon {
    background: #4caf50;
    color: white;
  }

  .step-label {
    font-size: 12px;
    font-weight: 500;
    text-align: center;
    min-width: 80px;
  }

  .progress-line {
    width: 60px;
    height: 2px;
    background: #e0e0e0;
    margin: 0 10px;
    margin-bottom: 20px;
  }

  .progress-line.completed {
    background: #4caf50;
  }

  .status-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
  }

  .status-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
  }

  .status-label {
    font-weight: 500;
    color: var(--gray-600);
  }

  .status-value {
    font-weight: 600;
  }

  .stock-cell {
    position: relative;
  }

  .shortage-info {
    font-size: 11px;
    color: #f44336;
    margin-top: 2px;
  }

  .status-materials-issued {
    background-color: #fff3e0;
    color: #f57c00;
  }

  .status-issued {
    background-color: #e8f5e8;
    color: #2e7d32;
  }

  .status-pending {
    background-color: #fff3e0;
    color: #f57c00;
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

  .modal {
    background: white;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
  }

  .modal-header {
    padding: 1rem;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .modal-body {
    padding: 1rem;
  }

  .modal-footer {
    padding: 1rem;
    border-top: 1px solid #e0e0e0;
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
  }

  .btn-close {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #666;
  }

  .form-group {
    margin-bottom: 1rem;
  }

  .form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
  }

  .form-control {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
  }

  .form-text {
    font-size: 12px;
    color: #666;
    margin-top: 0.25rem;
  }

  /* All existing styles from the original file... */
  /* Toast Styles */
  .toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-width: 400px;
  }

  .toast {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    cursor: pointer;
    animation: toastSlideIn 0.3s ease-out;
    position: relative;
    word-wrap: break-word;
  }

  .toast i {
    margin-right: 12px;
    font-size: 18px;
    flex-shrink: 0;
  }

  .toast span {
    flex-grow: 1;
    font-weight: 500;
  }

  .toast-close {
    background: none;
    border: none;
    margin-left: 12px;
    cursor: pointer;
    opacity: 0.7;
    padding: 4px;
    border-radius: 4px;
    transition: opacity 0.2s;
  }

  .toast-close:hover {
    opacity: 1;
    background-color: rgba(0, 0, 0, 0.1);
  }

  .toast-success {
    background-color: #10b981;
    color: white;
  }

  .toast-error {
    background-color: #ef4444;
    color: white;
  }

  .toast-warning {
    background-color: #f59e0b;
    color: white;
  }

  .toast-info {
    background-color: #3b82f6;
    color: white;
  }

  @keyframes toastSlideIn {
    from {
      transform: translateX(100%);
      opacity: 0;
    }
    to {
      transform: translateX(0);
      opacity: 1;
    }
  }

  /* Rest of existing styles... */
  .production-order-detail {
    padding: 1rem;
  }

  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }

  .actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
  }

  .loading-container,
  .error-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    text-align: center;
  }

  .loading-container i,
  .error-container i {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: var(--gray-300);
  }

  .error-container i {
    color: var(--danger-color);
  }

  .detail-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }

  .detail-card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
  }

  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background-color: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
  }

  .card-header h2 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
  }

  .card-body {
    padding: 1.5rem;
  }

  .detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
  }

  .detail-item {
    margin-bottom: 0.5rem;
  }

  .detail-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--gray-500);
    margin-bottom: 0.25rem;
  }

  .detail-value {
    font-size: 1rem;
    color: var(--gray-800);
  }

  .status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
  }

  .status-draft {
    background-color: var(--gray-200);
    color: var(--gray-700);
  }

  .status-in-progress {
    background-color: #bfdbfe;
    color: #1e40af;
  }

  .status-completed {
    background-color: #bbf7d0;
    color: #166534;
  }

  .status-cancelled {
    background-color: #fecaca;
    color: #b91c1c;
  }

  .table {
    width: 100%;
    border-collapse: collapse;
  }

  .table th,
  .table td {
    padding: 0.75rem;
    border-bottom: 1px solid var(--gray-200);
    text-align: left;
  }

  .table th {
    font-weight: 500;
    color: var(--gray-600);
  }

  .item-name {
    font-weight: 500;
  }

  .item-code {
    font-size: 0.75rem;
    color: var(--gray-500);
  }

  .variance {
    font-weight: 500;
  }

  .no-variance {
    color: var(--gray-600);
  }

  .low-variance {
    color: var(--warning-color);
  }

  .positive-variance {
    color: var(--success-color);
  }

  .negative-variance {
    color: var(--danger-color);
  }

  .header-actions {
    display: flex;
    gap: 0.5rem;
  }

  .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 2rem;
    color: var(--gray-500);
    text-align: center;
  }

  .empty-state i {
    font-size: 2rem;
    margin-bottom: 1rem;
  }

  .summary-stats {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1.5rem;
  }

  .stat-item {
    text-align: center;
    padding: 1rem;
    background-color: var(--gray-50);
    border-radius: 0.5rem;
  }

  .stat-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--gray-600);
    margin-bottom: 0.5rem;
  }

  .stat-value {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--primary-color);
  }

  .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    font-weight: 500;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
  }

  .btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
  }

  .btn-primary {
    background-color: var(--primary-color);
    color: white;
  }

  .btn-primary:hover {
    background-color: var(--primary-dark);
  }

  .btn-secondary {
    background-color: var(--gray-200);
    color: var(--gray-800);
  }

  .btn-secondary:hover {
    background-color: var(--gray-300);
  }

  .btn-success {
    background-color: var(--success-color);
    color: white;
  }

  .btn-success:hover {
    background-color: #047857;
  }

  .btn-warning {
    background-color: var(--warning-color);
    color: white;
  }

  .btn-warning:hover {
    background-color: #b45309;
  }

  .btn-danger {
    background-color: var(--danger-color);
    color: white;
  }

  .btn-danger:hover {
    background-color: #b91c1c;
  }

  .btn-info {
    background-color: #3b82f6;
    color: white;
  }

  .btn-info:hover {
    background-color: #2563eb;
  }

  .text-success {
    color: #16a085;
  }

  .text-danger {
    color: #e74c3c;
  }

  @media (max-width: 768px) {
    .toast-container {
      top: 10px;
      right: 10px;
      left: 10px;
      max-width: none;
    }

    .page-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 1rem;
    }

    .actions {
      width: 100%;
      justify-content: flex-start;
    }

    .detail-grid {
      grid-template-columns: 1fr;
    }

    .table-responsive {
      overflow-x: auto;
    }

    .summary-stats {
      grid-template-columns: 1fr;
    }

    .progress-bar {
      flex-direction: column;
      gap: 1rem;
    }

    .progress-line {
      width: 2px;
      height: 30px;
      margin: 5px 0;
    }

    .actual-quantity-cell {
      min-width: 100px;
    }

    .editable-quantity .form-control-sm {
      width: 70px;
    }
  }
  </style>
