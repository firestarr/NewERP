<template>
  <div class="stock-adjustment-form">
    <div class="page-header">
      <div class="header-left">
        <router-link to="/stock-adjustments" class="back-link">
          <i class="fas fa-arrow-left"></i> Back to Adjustments
        </router-link>
        <h1>{{ isEditMode ? 'Edit' : 'Create' }} Stock Adjustment</h1>
      </div>
    </div>

    <div v-if="loading" class="loading-container">
      <div class="loading-spinner">
        <i class="fas fa-spinner fa-spin"></i>
      </div>
      <p>Loading form data...</p>
    </div>

    <div v-else-if="error" class="error-container">
      <div class="error-icon">
        <i class="fas fa-exclamation-triangle"></i>
      </div>
      <p>{{ error }}</p>
      <button @click="loadFormData" class="btn btn-secondary">
        <i class="fas fa-sync"></i> Try Again
      </button>
    </div>

    <form v-else @submit.prevent="submitForm" class="adjustment-form">
      <!-- Header Information -->
      <div class="form-card">
        <div class="card-header">
          <h3>Adjustment Information</h3>
        </div>
        <div class="card-body">
          <div class="form-grid">
            <div class="form-group">
              <label for="adjustmentDate">Adjustment Date <span class="required">*</span></label>
              <input
                id="adjustmentDate"
                type="date"
                v-model="form.adjustment_date"
                :disabled="isSubmitting"
                required
                class="form-control"
                :class="{ 'is-invalid': validationErrors.adjustment_date }"
              >
              <div v-if="validationErrors.adjustment_date" class="invalid-feedback">
                {{ validationErrors.adjustment_date[0] }}
              </div>
            </div>

            <div class="form-group">
              <label for="referenceDocument">Reference Document</label>
              <input
                id="referenceDocument"
                type="text"
                v-model="form.reference_document"
                :disabled="isSubmitting"
                class="form-control"
                placeholder="Enter reference document"
                :class="{ 'is-invalid': validationErrors.reference_document }"
              >
              <div v-if="validationErrors.reference_document" class="invalid-feedback">
                {{ validationErrors.reference_document[0] }}
              </div>
            </div>

            <div class="form-group span-full">
              <label for="adjustmentReason">Adjustment Reason <span class="required">*</span></label>
              <textarea
                id="adjustmentReason"
                v-model="form.adjustment_reason"
                :disabled="isSubmitting"
                required
                class="form-control"
                rows="3"
                placeholder="Explain the reason for this adjustment"
                :class="{ 'is-invalid': validationErrors.adjustment_reason }"
              ></textarea>
              <div v-if="validationErrors.adjustment_reason" class="invalid-feedback">
                {{ validationErrors.adjustment_reason[0] }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Adjustment Lines -->
      <div class="form-card">
        <div class="card-header">
          <h3>Adjustment Lines</h3>
          <button 
            type="button" 
            @click="addNewLine" 
            :disabled="isSubmitting"
            class="btn btn-primary btn-sm"
          >
            <i class="fas fa-plus"></i> Add Line
          </button>
        </div>
        <div class="card-body">
          <div v-if="form.lines.length === 0" class="empty-lines">
            <i class="fas fa-inbox"></i>
            <p>No adjustment lines added yet.</p>
            <p>Click "Add Line" to start adding items to adjust.</p>
          </div>

          <div v-else class="lines-container">
            <div
              v-for="(line, index) in form.lines"
              :key="index"
              class="adjustment-line"
              :class="{ 'is-invalid': lineErrors[index] }"
            >
              <div class="line-header">
                <h4>Line {{ index + 1 }}</h4>
                <button 
                  type="button" 
                  @click="removeLine(index)"
                  :disabled="isSubmitting"
                  class="btn btn-danger btn-sm"
                >
                  <i class="fas fa-trash"></i>
                </button>
              </div>

              <div class="line-form">
                <div class="form-row">
                  <div class="form-group">
                    <label>Item <span class="required">*</span></label>
                    <select 
                      v-model="line.item_id" 
                      @change="onItemChange(index)"
                      :disabled="isSubmitting"
                      required
                      class="form-control"
                      :class="{ 'is-invalid': lineErrors[index]?.item_id }"
                    >
                      <option value="">Select an item</option>
                      <option 
                        v-for="item in items" 
                        :key="item.item_id" 
                        :value="item.item_id"
                      >
                        {{ item.item_code }} - {{ item.name }}
                      </option>
                    </select>
                    <div v-if="lineErrors[index]?.item_id" class="invalid-feedback">
                      {{ lineErrors[index].item_id[0] }}
                    </div>
                  </div>

                  <div class="form-group">
                    <label>Warehouse <span class="required">*</span></label>
                    <select 
                      v-model="line.warehouse_id" 
                      @change="onWarehouseChange(index)"
                      :disabled="isSubmitting || !line.item_id"
                      required
                      class="form-control"
                      :class="{ 'is-invalid': lineErrors[index]?.warehouse_id }"
                    >
                      <option value="">Select warehouse</option>
                      <option 
                        v-for="warehouse in warehouses" 
                        :key="warehouse.warehouse_id" 
                        :value="warehouse.warehouse_id"
                      >
                        {{ warehouse.name }}
                      </option>
                    </select>
                    <div v-if="lineErrors[index]?.warehouse_id" class="invalid-feedback">
                      {{ lineErrors[index].warehouse_id[0] }}
                    </div>
                  </div>
                </div>

                <div class="form-row" v-if="line.item_id && line.warehouse_id">
                  <div class="form-group">
                    <label>Current Stock</label>
                    <div class="stock-display">
                      <span class="stock-value">{{ formatNumber(line.book_quantity || 0) }}</span>
                      <span class="stock-unit">{{ getItemUnit(line.item_id) }}</span>
                    </div>
                  </div>

                  <div class="form-group">
                    <label>Adjusted Quantity <span class="required">*</span></label>
                    <div class="input-group">
                      <input 
                        type="number" 
                        v-model.number="line.adjusted_quantity"
                        @input="calculateVariance(index)"
                        step="0.01"
                        min="0"
                        :disabled="isSubmitting"
                        required
                        class="form-control"
                        :class="{ 'is-invalid': lineErrors[index]?.adjusted_quantity }"
                      >
                      <div class="input-group-append">
                        <span class="input-group-text">{{ getItemUnit(line.item_id) }}</span>
                      </div>
                    </div>
                    <div v-if="lineErrors[index]?.adjusted_quantity" class="invalid-feedback">
                      {{ lineErrors[index].adjusted_quantity[0] }}
                    </div>
                  </div>

                  <div class="form-group">
                    <label>Variance</label>
                    <div class="variance-display">
                      <span 
                        class="variance-value"
                        :class="getVarianceClass(line.variance)"
                      >
                        {{ formatVariance(line.variance) }}
                      </span>
                      <span class="variance-unit">{{ getItemUnit(line.item_id) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Summary -->
          <div v-if="form.lines.length > 0" class="adjustment-summary">
            <div class="summary-header">
              <h4>Adjustment Summary</h4>
            </div>
            <div class="summary-content">
              <div class="summary-row">
                <span class="label">Total Lines:</span>
                <span class="value">{{ form.lines.length }}</span>
              </div>
              <div class="summary-row">
                <span class="label">Total Variance:</span>
                <span 
                  class="value"
                  :class="getVarianceClass(totalVariance)"
                >
                  {{ formatVariance(totalVariance) }}
                </span>
              </div>
              <div class="summary-row">
                <span class="label">Lines with Increase:</span>
                <span class="value text-success">{{ positiveLines }}</span>
              </div>
              <div class="summary-row">
                <span class="label">Lines with Decrease:</span>
                <span class="value text-danger">{{ negativeLines }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="form-actions">
        <button 
          type="button" 
          @click="showCancelModal = true"
          :disabled="isSubmitting"
          class="btn btn-secondary"
        >
          Cancel
        </button>
        <button 
          type="button" 
          @click="saveDraft"
          :disabled="isSubmitting || !canSave"
          class="btn btn-outline-primary"
        >
          <i v-if="isSubmitting" class="fas fa-spinner fa-spin mr-2"></i>
          Save as Draft
        </button>
        <button 
          type="submit" 
          :disabled="isSubmitting || !isFormValid"
          class="btn btn-primary"
        >
          <i v-if="isSubmitting" class="fas fa-spinner fa-spin mr-2"></i>
          {{ isEditMode ? 'Update' : 'Create' }} Adjustment
        </button>
      </div>
    </form>

    <!-- Cancel Confirmation Modal -->
    <div v-if="showCancelModal" class="modal-backdrop">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Confirm Cancel</h3>
          <button class="btn-close" @click="showCancelModal = false">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to cancel? Any unsaved changes will be lost.</p>
        </div>
        <div class="modal-footer">
          <button 
            class="btn btn-secondary" 
            @click="showCancelModal = false"
          >
            No, Continue Editing
          </button>
          <button 
            class="btn btn-danger" 
            @click="cancelForm"
          >
            Yes, Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'StockAdjustmentForm',
  data() {
    return {
      form: {
        adjustment_date: new Date().toISOString().split('T')[0],
        adjustment_reason: '',
        reference_document: '',
        lines: []
      },
      items: [],
      warehouses: [],
      loading: false,
      error: null,
      isSubmitting: false,
      showCancelModal: false,
      validationErrors: {},
      lineErrors: []
    };
  },
  computed: {
    isEditMode() {
      return !!this.$route.params.id;
    },
    isFormValid() {
      return this.form.adjustment_date && 
             this.form.adjustment_reason &&
             this.form.lines.length > 0 &&
             !this.form.lines.some(line => 
               !line.item_id || 
               !line.warehouse_id || 
               line.adjusted_quantity === null || 
               line.adjusted_quantity === undefined ||
               isNaN(line.adjusted_quantity)
             );
    },
    canSave() {
      return this.form.adjustment_date && 
             this.form.adjustment_reason &&
             this.form.lines.length > 0;
    },
    totalVariance() {
      return this.form.lines.reduce((total, line) => {
        return total + (line.variance || 0);
      }, 0);
    },
    positiveLines() {
      return this.form.lines.filter(line => (line.variance || 0) > 0).length;
    },
    negativeLines() {
      return this.form.lines.filter(line => (line.variance || 0) < 0).length;
    }
  },
  mounted() {
    this.loadFormData();
  },
  methods: {
    async loadFormData() {
      this.loading = true;
      this.error = null;
      
      try {
        // Load items and warehouses
        const [itemsResponse, warehousesResponse] = await Promise.all([
          axios.get('/items'),
          axios.get('/warehouses')
        ]);
        
        this.items = itemsResponse.data.data || itemsResponse.data;
        this.warehouses = warehousesResponse.data.data || warehousesResponse.data;
        
        // If in edit mode, load the adjustment data
        if (this.isEditMode) {
          const adjustmentResponse = await axios.get(`/stock-adjustments/${this.$route.params.id}`);
          const adjustment = adjustmentResponse.data.data;
          
          this.form.adjustment_date = adjustment.adjustment_date.split('T')[0];
          this.form.adjustment_reason = adjustment.adjustment_reason;
          this.form.reference_document = adjustment.reference_document;
          this.form.lines = adjustment.adjustment_lines.map(line => ({
            line_id: line.line_id,
            item_id: line.item_id,
            warehouse_id: line.warehouse_id,
            book_quantity: line.book_quantity,
            adjusted_quantity: line.adjusted_quantity,
            variance: line.variance
          }));
        }
      } catch (err) {
        console.error('Error loading form data:', err);
        this.error = 'Failed to load form data. Please try again.';
      } finally {
        this.loading = false;
      }
    },

    addNewLine() {
      this.form.lines.push({
        item_id: '',
        warehouse_id: '',
        book_quantity: 0,
        adjusted_quantity: null,
        variance: 0
      });
      this.lineErrors.push({});
    },

    removeLine(index) {
      this.form.lines.splice(index, 1);
      this.lineErrors.splice(index, 1);
    },

    async onItemChange(lineIndex) {
      const line = this.form.lines[lineIndex];
      
      // Reset warehouse selection when item changes
      line.warehouse_id = '';
      line.book_quantity = 0;
      line.adjusted_quantity = null;
      line.variance = 0;
    },

    async onWarehouseChange(lineIndex) {
      const line = this.form.lines[lineIndex];
      
      if (line.item_id && line.warehouse_id) {
        try {
          // Load current stock for this item in this warehouse
          const response = await axios.get(`/item-stocks/${line.item_id}/warehouse/${line.warehouse_id}`);
          line.book_quantity = response.data.data ? response.data.data.quantity : 0;
          this.calculateVariance(lineIndex);
        } catch (err) {
          console.error('Error loading current stock:', err);
          line.book_quantity = 0;
        }
      }
    },

    calculateVariance(lineIndex) {
      const line = this.form.lines[lineIndex];
      if (line.adjusted_quantity !== null && line.adjusted_quantity !== undefined) {
        line.variance = line.adjusted_quantity - (line.book_quantity || 0);
      } else {
        line.variance = 0;
      }
    },

    getItemUnit(itemId) {
      const item = this.items.find(i => i.item_id == itemId);
      return item?.unit_of_measure?.symbol || 'units';
    },

    formatNumber(value) {
      if (value === null || value === undefined) return '0';
      return Number(value).toLocaleString();
    },

    formatVariance(variance) {
      if (variance === null || variance === undefined) return '0';
      const sign = variance >= 0 ? '+' : '';
      return `${sign}${Number(variance).toLocaleString()}`;
    },

    getVarianceClass(variance) {
      if (variance > 0) return 'text-success';
      if (variance < 0) return 'text-danger';
      return 'text-muted';
    },

    async saveDraft() {
      await this.saveForm('draft');
    },

    async submitForm() {
      await this.saveForm('submit');
    },

    async saveForm(action = 'submit') {
      this.isSubmitting = true;
      this.validationErrors = {};
      this.lineErrors = [];
      
      try {
        const url = this.isEditMode 
          ? `/stock-adjustments/${this.$route.params.id}`
          : '/stock-adjustments';
          
        const method = this.isEditMode ? 'put' : 'post';
        
        const response = await axios[method](url, this.form);
        
        this.$toast.success(
          this.isEditMode 
            ? 'Stock adjustment updated successfully' 
            : 'Stock adjustment created successfully'
        );
        
        // Redirect to the adjustment detail or list
        if (action === 'draft') {
          this.$router.push('/stock-adjustments');
        } else {
          const adjustmentId = response.data.data.adjustment_id;
          this.$router.push(`/stock-adjustments/${adjustmentId}`);
        }
      } catch (err) {
        console.error('Error saving adjustment:', err);
        
        if (err.response && err.response.status === 422) {
          const errors = err.response.data.errors;
          this.validationErrors = errors;
          
          // Handle line-specific errors
          if (errors.lines) {
            this.lineErrors = errors.lines;
          }
          
          this.$toast.error('Please fix the validation errors and try again.');
        } else {
          this.$toast.error('Failed to save adjustment. Please try again.');
        }
      } finally {
        this.isSubmitting = false;
      }
    },

    cancelForm() {
      this.$router.push('/stock-adjustments');
    }
  }
};
</script>

<style scoped>
.stock-adjustment-form {
  padding: 1.5rem;
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 2rem;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.back-link {
  color: #6c757d;
  text-decoration: none;
  transition: color 0.2s;
}

.back-link:hover {
  color: #007bff;
}

.form-card {
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 6px;
  margin-bottom: 1.5rem;
  overflow: hidden;
}

.card-header {
  background: #f8f9fa;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #dee2e6;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h3 {
  margin: 0;
  font-size: 1.125rem;
  color: #495057;
}

.card-body {
  padding: 1.5rem;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-group.span-full {
  grid-column: 1 / -1;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  font-weight: 500;
  margin-bottom: 0.5rem;
  color: #495057;
  font-size: 0.875rem;
}

.required {
  color: #dc3545;
}

.form-control {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ced4da;
  border-radius: 4px;
  font-size: 0.875rem;
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus {
  outline: 0;
  border-color: #80bdff;
  box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

.form-control.is-invalid {
  border-color: #dc3545;
}

.invalid-feedback {
  color: #dc3545;
  font-size: 0.75rem;
  margin-top: 0.25rem;
}

/* Add vertical spacing between adjustment lines */
.lines-container > .adjustment-line:not(:last-child) {
  margin-bottom: 1rem;
}

.adjustment-line {
  border: 1px solid #dee2e6;
  border-radius: 6px;
  padding: 1rem;
  margin-bottom: 1rem;
  background: #f8f9fa;
}

.adjustment-line.is-invalid {
  border-color: #dc3545;
}

.line-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #dee2e6;
}

.line-header h4 {
  margin: 0;
  font-size: 1rem;
  color: #495057;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.input-group {
  display: flex;
}

.input-group .form-control {
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
}

.input-group-append {
  display: flex;
}

.input-group-text {
  padding: 0.75rem;
  background-color: #e9ecef;
  border: 1px solid #ced4da;
  border-left: 0;
  border-top-right-radius: 4px;
  border-bottom-right-radius: 4px;
  font-size: 0.875rem;
  color: #495057;
}

.stock-display,
.variance-display {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem;
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 4px;
}

.stock-value,
.variance-value {
  font-weight: 600;
  font-size: 1rem;
}

.stock-unit,
.variance-unit {
  color: #6c757d;
  font-size: 0.875rem;
}

.adjustment-summary {
  background: #e9ecef;
  border: 1px solid #dee2e6;
  border-radius: 6px;
  margin-top: 1.5rem;
  overflow: hidden;
}

.summary-header {
  background: #dee2e6;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #ced4da;
}

.summary-header h4 {
  margin: 0;
  font-size: 1rem;
  color: #495057;
}

.summary-content {
  padding: 1rem;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.25rem 0;
}

.summary-row .label {
  color: #6c757d;
  font-weight: 500;
}

.summary-row .value {
  font-weight: 600;
}

.empty-lines {
  text-align: center;
  padding: 3rem;
  color: #6c757d;
}

.empty-lines i {
  font-size: 3rem;
  margin-bottom: 1rem;
  display: block;
}

.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  padding: 1.5rem;
  background: #f8f9fa;
  border-top: 1px solid #dee2e6;
  margin: 0 -1.5rem -1.5rem -1.5rem;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: 1px solid;
  border-radius: 4px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s;
}

.btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.btn-sm {
  padding: 0.375rem 0.75rem;
  font-size: 0.75rem;
}

.btn-primary { background: #007bff; color: white; border-color: #007bff; }
.btn-secondary { background: #6c757d; color: white; border-color: #6c757d; }
.btn-danger { background: #dc3545; color: white; border-color: #dc3545; }
.btn-outline-primary { background: transparent; color: #007bff; border-color: #007bff; }

.btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.loading-container,
.error-container {
  text-align: center;
  padding: 3rem;
  color: #6c757d;
}

.loading-spinner i,
.error-icon i {
  font-size: 3rem;
  margin-bottom: 1rem;
  display: block;
}

.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 6px;
  max-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  border-bottom: 1px solid #dee2e6;
}

.modal-header h3 {
  margin: 0;
  color: #495057;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.25rem;
  cursor: pointer;
  color: #6c757d;
}

.modal-body {
  padding: 1rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  padding: 1rem;
  border-top: 1px solid #dee2e6;
}

.text-success { color: #28a745 !important; }
.text-danger { color: #dc3545 !important; }
.text-muted { color: #6c757d !important; }

@media (max-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .form-actions {
    flex-direction: column-reverse;
  }
  
  .btn {
    width: 100%;
    justify-content: center;
  }
}
</style>