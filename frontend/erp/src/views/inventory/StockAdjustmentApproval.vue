<template>
  <div class="stock-adjustment-approval">
    <div class="page-header">
      <div class="header-left">
        <router-link to="/stock-adjustments" class="back-link">
          <i class="fas fa-arrow-left"></i> Back to Adjustments
        </router-link>
        <h1>Approve Stock Adjustment #{{ adjustmentId }}</h1>
      </div>
    </div>

    <div v-if="loading" class="loading-container">
      <div class="loading-spinner">
        <i class="fas fa-spinner fa-spin"></i>
      </div>
      <p>Loading adjustment details...</p>
    </div>

    <div v-else-if="error" class="error-container">
      <div class="error-icon">
        <i class="fas fa-exclamation-triangle"></i>
      </div>
      <p>{{ error }}</p>
      <button @click="fetchAdjustment" class="btn btn-secondary">
        <i class="fas fa-sync"></i> Try Again
      </button>
    </div>

    <div v-else-if="adjustment && adjustment.status !== 'pending'" class="status-message">
      <div class="status-icon" :class="adjustment.status === 'approved' ? 'approved' : 'rejected'">
        <i :class="adjustment.status === 'approved' ? 'fas fa-check-circle' : 'fas fa-times-circle'"></i>
      </div>
      <h3>
        Adjustment Already {{ adjustment.status === 'approved' ? 'Approved' : 'Rejected' }}
      </h3>
      <p>
        This adjustment has already been {{ adjustment.status === 'approved' ? 'approved' : 'rejected' }}.
        No further action is required.
      </p>
      <router-link :to="`/stock-adjustments/${adjustmentId}`" class="btn btn-primary mt-3">
        <i class="fas fa-eye mr-1"></i> View Details
      </router-link>
    </div>

    <div v-else class="approval-container">
      <div class="approval-cards">
        <!-- Adjustment Summary Card -->
        <div class="approval-card">
          <div class="card-header">
            <h3 class="card-title">Adjustment Summary</h3>
          </div>
          <div class="card-body">
            <div class="info-grid">
              <div class="info-item">
                <div class="info-label">Adjustment Date</div>
                <div class="info-value">{{ formatDate(adjustment.adjustment_date) }}</div>
              </div>
              <div class="info-item">
                <div class="info-label">Reference Document</div>
                <div class="info-value">{{ adjustment.reference_document || 'N/A' }}</div>
              </div>
              <div class="info-item">
                <div class="info-label">Total Items</div>
                <div class="info-value">{{ adjustment.adjustment_lines?.length || 0 }}</div>
              </div>
              <div class="info-item">
                <div class="info-label">Total Variance</div>
                <div 
                  class="info-value" 
                  :class="getVarianceClass(adjustment.total_variance)"
                >
                  {{ formatVariance(adjustment.total_variance) }}
                </div>
              </div>
              <div class="info-item full-width">
                <div class="info-label">Adjustment Reason</div>
                <div class="info-value reason-box">
                  {{ adjustment.adjustment_reason || 'No reason provided' }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Approval Actions Card -->
        <div class="approval-card">
          <div class="card-header">
            <h3 class="card-title">Approval Actions</h3>
          </div>
          <div class="card-body">
            <div class="approval-form">
              <form @submit.prevent="approveAdjustment">
                <div class="form-group">
                  <div class="checkbox-group">
                    <input 
                      type="checkbox" 
                      id="createAdjustment" 
                      v-model="approvalForm.create_adjustment"
                    />
                    <label for="createAdjustment">Process adjustment and update stock levels</label>
                  </div>
                  <small class="form-text">
                    When checked, this will immediately process the adjustment and update inventory levels in each warehouse.
                  </small>
                </div>
                
                <div class="form-group" v-if="approvalForm.create_adjustment">
                  <label for="adjustmentReason">Processing Notes</label>
                  <textarea
                    id="adjustmentReason"
                    v-model="approvalForm.processing_notes"
                    class="form-control"
                    rows="3"
                    placeholder="Optional notes for this processing action"
                  ></textarea>
                </div>
                
                <div class="approval-buttons">
                  <button 
                    type="button"
                    @click="showRejectModal = true"
                    :disabled="isProcessing"
                    class="btn btn-danger"
                  >
                    <i class="fas fa-times mr-2"></i>
                    Reject
                  </button>
                  
                  <button 
                    type="submit"
                    :disabled="isProcessing"
                    class="btn btn-success"
                  >
                    <i v-if="isProcessing" class="fas fa-spinner fa-spin mr-2"></i>
                    <i v-else class="fas fa-check mr-2"></i>
                    Approve
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- Adjustment Lines Review -->
      <div class="approval-card">
        <div class="card-header">
          <h3 class="card-title">Items to Adjust</h3>
          <div class="search-controls">
            <input 
              type="text" 
              v-model="searchQuery"
              placeholder="Search items, warehouses..."
              class="search-input"
            >
          </div>
        </div>
        <div class="card-body">
          <div v-if="filteredLines.length === 0" class="empty-state">
            <i class="fas fa-inbox"></i>
            <p v-if="searchQuery">No lines match your search criteria.</p>
            <p v-else>No adjustment lines found.</p>
          </div>

          <div v-else class="lines-table-container">
            <table class="lines-table">
              <thead>
                <tr>
                  <th>Item</th>
                  <th>Warehouse</th>
                  <th class="text-right">Current Stock</th>
                  <th class="text-right">Adjusted Quantity</th>
                  <th class="text-right">Variance</th>
                  <th class="text-right">Impact</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="line in filteredLines" :key="line.line_id">
                  <td>
                    <div class="item-info">
                      <div class="item-code">{{ line.item?.item_code }}</div>
                      <div class="item-name">{{ line.item?.name }}</div>
                    </div>
                  </td>
                  <td>
                    <div class="warehouse-name">{{ line.warehouse?.name }}</div>
                  </td>
                  <td class="text-right">
                    <div class="quantity-display">
                      <span class="quantity-value">{{ formatNumber(line.book_quantity) }}</span>
                      <span class="quantity-unit">{{ getItemUnit(line.item) }}</span>
                    </div>
                  </td>
                  <td class="text-right">
                    <div class="quantity-display">
                      <span class="quantity-value">{{ formatNumber(line.adjusted_quantity) }}</span>
                      <span class="quantity-unit">{{ getItemUnit(line.item) }}</span>
                    </div>
                  </td>
                  <td class="text-right">
                    <div class="variance-display">
                      <span 
                        class="variance-value" 
                        :class="getVarianceClass(line.variance)"
                      >
                        {{ formatVariance(line.variance) }}
                      </span>
                      <span class="variance-unit">{{ getItemUnit(line.item) }}</span>
                    </div>
                  </td>
                  <td class="text-right">
                    <span 
                      class="impact-badge"
                      :class="getImpactClass(line.variance)"
                    >
                      {{ getImpactText(line.variance) }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Impact Summary -->
      <div class="approval-card">
        <div class="card-header">
          <h3 class="card-title">Impact Summary</h3>
        </div>
        <div class="card-body">
          <div class="impact-stats">
            <div class="impact-item">
              <div class="impact-value text-success">{{ positiveLines }}</div>
              <div class="impact-label">Stock Increases</div>
            </div>
            <div class="impact-item">
              <div class="impact-value text-danger">{{ negativeLines }}</div>
              <div class="impact-label">Stock Decreases</div>
            </div>
            <div class="impact-item">
              <div class="impact-value text-muted">{{ neutralLines }}</div>
              <div class="impact-label">No Change</div>
            </div>
            <div class="impact-item">
              <div class="impact-value" :class="getVarianceClass(totalVariance)">
                {{ formatVariance(totalVariance) }}
              </div>
              <div class="impact-label">Net Change</div>
            </div>
          </div>
          
          <div class="impact-warning" v-if="hasSignificantChanges">
            <div class="warning-icon">
              <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="warning-content">
              <strong>Significant Changes Detected</strong>
              <p>This adjustment contains significant stock changes. Please review carefully before approving.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Rejection Modal -->
    <div v-if="showRejectModal" class="modal-backdrop">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Reject Adjustment</h3>
          <button class="btn-close" @click="showRejectModal = false">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <p>
            You are about to reject stock adjustment 
            <strong>ID #{{ adjustmentId }}</strong>.
          </p>
          
          <div class="form-group">
            <label for="rejectionReason">Rejection Reason <span class="required">*</span></label>
            <textarea
              id="rejectionReason"
              v-model="rejectForm.rejection_reason"
              class="form-control"
              rows="3"
              placeholder="Provide a reason for rejection"
              required
            ></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="showRejectModal = false">
            Cancel
          </button>
          <button 
            class="btn btn-danger" 
            @click="rejectAdjustment" 
            :disabled="isProcessing || !rejectForm.rejection_reason"
          >
            <i v-if="isProcessing" class="fas fa-spinner fa-spin mr-2"></i>
            Reject
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'StockAdjustmentApproval',
  data() {
    return {
      adjustment: null,
      loading: true,
      error: null,
      searchQuery: '',
      showRejectModal: false,
      isProcessing: false,
      approvalForm: {
        create_adjustment: true,
        processing_notes: ''
      },
      rejectForm: {
        rejection_reason: ''
      }
    };
  },
  computed: {
    adjustmentId() {
      return this.$route.params.id;
    },
    filteredLines() {
      if (!this.adjustment || !this.adjustment.adjustment_lines) return [];
      
      if (!this.searchQuery) return this.adjustment.adjustment_lines;
      
      const query = this.searchQuery.toLowerCase();
      return this.adjustment.adjustment_lines.filter(line => 
        (line.item?.name && line.item.name.toLowerCase().includes(query)) ||
        (line.item?.item_code && line.item.item_code.toLowerCase().includes(query)) ||
        (line.warehouse?.name && line.warehouse.name.toLowerCase().includes(query))
      );
    },
    totalVariance() {
      if (!this.adjustment || !this.adjustment.adjustment_lines) return 0;
      return this.adjustment.adjustment_lines.reduce((total, line) => {
        return total + (line.variance || 0);
      }, 0);
    },
    positiveLines() {
      if (!this.adjustment || !this.adjustment.adjustment_lines) return 0;
      return this.adjustment.adjustment_lines.filter(line => (line.variance || 0) > 0).length;
    },
    negativeLines() {
      if (!this.adjustment || !this.adjustment.adjustment_lines) return 0;
      return this.adjustment.adjustment_lines.filter(line => (line.variance || 0) < 0).length;
    },
    neutralLines() {
      if (!this.adjustment || !this.adjustment.adjustment_lines) return 0;
      return this.adjustment.adjustment_lines.filter(line => (line.variance || 0) === 0).length;
    },
    hasSignificantChanges() {
      if (!this.adjustment || !this.adjustment.adjustment_lines) return false;
      return this.adjustment.adjustment_lines.some(line => {
        const variance = Math.abs(line.variance || 0);
        const bookQuantity = line.book_quantity || 1;
        const percentageChange = (variance / bookQuantity) * 100;
        return percentageChange > 20; // Consider >20% as significant
      });
    }
  },
  mounted() {
    this.fetchAdjustment();
  },
  methods: {
    async fetchAdjustment() {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get(`/stock-adjustments/${this.adjustmentId}`);
        this.adjustment = response.data.data;
        
        if (this.adjustment.status !== 'pending') {
          // Adjustment is not in pending status, no approval needed
          return;
        }
      } catch (err) {
        console.error('Error fetching adjustment:', err);
        if (err.response && err.response.status === 404) {
          this.error = 'Stock adjustment not found.';
        } else {
          this.error = 'Failed to load adjustment details. Please try again.';
        }
      } finally {
        this.loading = false;
      }
    },

    async approveAdjustment() {
      this.isProcessing = true;
      
      try {
        const payload = {
          process_immediately: this.approvalForm.create_adjustment,
          processing_notes: this.approvalForm.processing_notes
        };
        
        await axios.post(`/stock-adjustments/${this.adjustmentId}/approve`, payload);
        
        this.$toast.success('Adjustment approved successfully');
        
        // Redirect to the adjustment detail page
        this.$router.push(`/stock-adjustments/${this.adjustmentId}`);
      } catch (err) {
        console.error('Error approving adjustment:', err);
        if (err.response && err.response.data && err.response.data.message) {
          this.$toast.error(err.response.data.message);
        } else {
          this.$toast.error('Failed to approve adjustment. Please try again.');
        }
      } finally {
        this.isProcessing = false;
      }
    },

    async rejectAdjustment() {
      this.isProcessing = true;
      
      try {
        await axios.post(`/stock-adjustments/${this.adjustmentId}/reject`, {
          rejection_reason: this.rejectForm.rejection_reason
        });
        
        this.showRejectModal = false;
        this.$toast.success('Adjustment rejected successfully');
        
        // Redirect to the adjustment detail page
        this.$router.push(`/stock-adjustments/${this.adjustmentId}`);
      } catch (err) {
        console.error('Error rejecting adjustment:', err);
        if (err.response && err.response.data && err.response.data.message) {
          this.$toast.error(err.response.data.message);
        } else {
          this.$toast.error('Failed to reject adjustment. Please try again.');
        }
      } finally {
        this.isProcessing = false;
      }
    },

    formatDate(dateString) {
      if (!dateString) return 'N/A';
      return new Date(dateString).toLocaleDateString();
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

    getImpactClass(variance) {
      if (variance > 0) return 'impact-increase';
      if (variance < 0) return 'impact-decrease';
      return 'impact-neutral';
    },

    getImpactText(variance) {
      if (variance > 0) return 'Increase';
      if (variance < 0) return 'Decrease';
      return 'No Change';
    },

    getItemUnit(item) {
      return item?.unit_of_measure?.symbol || 'units';
    }
  }
};
</script>

<style scoped>
.stock-adjustment-approval {
  padding: 1.5rem;
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #dee2e6;
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

.header-left h1 {
  margin: 0;
  color: #495057;
  font-size: 1.75rem;
}

.status-message {
  text-align: center;
  padding: 3rem;
}

.status-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.status-icon.approved {
  color: #28a745;
}

.status-icon.rejected {
  color: #dc3545;
}

.approval-container {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.approval-cards {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.approval-card {
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 6px;
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

.card-title {
  margin: 0;
  font-size: 1.125rem;
  color: #495057;
}

.card-body {
  padding: 1.5rem;
}

.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.info-item.full-width {
  grid-column: 1 / -1;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.info-label {
  font-weight: 500;
  color: #6c757d;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.info-value {
  color: #495057;
  font-size: 1rem;
  font-weight: 500;
}

.reason-box {
  background: #f8f9fa;
  padding: 0.75rem;
  border-radius: 4px;
  border: 1px solid #dee2e6;
  white-space: pre-wrap;
  font-weight: normal;
}

.approval-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.checkbox-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.checkbox-group input[type="checkbox"] {
  width: 1.25rem;
  height: 1.25rem;
}

.checkbox-group label {
  font-weight: 500;
  color: #495057;
  cursor: pointer;
}

.form-text {
  color: #6c757d;
  font-size: 0.875rem;
  margin-top: 0.25rem;
}

.form-control {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ced4da;
  border-radius: 4px;
  font-size: 0.875rem;
}

.approval-buttons {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 1rem;
}

.search-controls {
  display: flex;
  gap: 1rem;
}

.search-input {
  padding: 0.5rem;
  border: 1px solid #ced4da;
  border-radius: 4px;
  font-size: 0.875rem;
  min-width: 250px;
}

.lines-table-container {
  overflow-x: auto;
}

.lines-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.lines-table th,
.lines-table td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid #dee2e6;
  vertical-align: top;
}

.lines-table th {
  background-color: #f8f9fa;
  font-weight: 600;
  color: #495057;
  white-space: nowrap;
}

.text-right {
  text-align: right;
}

.item-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.item-code {
  font-family: monospace;
  font-weight: 600;
  color: #495057;
  font-size: 0.8125rem;
}

.item-name {
  color: #6c757d;
  font-size: 0.8125rem;
}

.warehouse-name {
  color: #495057;
  font-weight: 500;
}

.quantity-display,
.variance-display {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.125rem;
}

.quantity-value,
.variance-value {
  font-weight: 600;
}

.quantity-unit,
.variance-unit {
  color: #6c757d;
  font-size: 0.75rem;
}

.impact-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.impact-increase {
  background-color: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.impact-decrease {
  background-color: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

.impact-neutral {
  background-color: #e2e3e5;
  color: #383d41;
  border: 1px solid #d6d8db;
}

.impact-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.impact-item {
  text-align: center;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 6px;
  border: 1px solid #dee2e6;
}

.impact-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #495057;
  margin-bottom: 0.25rem;
}

.impact-label {
  font-size: 0.875rem;
  color: #6c757d;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.impact-warning {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  background: #fff3cd;
  border: 1px solid #ffeaa7;
  border-radius: 6px;
  color: #856404;
}

.warning-icon {
  font-size: 1.25rem;
  flex-shrink: 0;
}

.warning-content strong {
  display: block;
  margin-bottom: 0.25rem;
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

.btn-primary { background: #007bff; color: white; border-color: #007bff; }
.btn-secondary { background: #6c757d; color: white; border-color: #6c757d; }
.btn-success { background: #28a745; color: white; border-color: #28a745; }
.btn-danger { background: #dc3545; color: white; border-color: #dc3545; }

.btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.loading-container,
.error-container,
.empty-state {
  text-align: center;
  padding: 3rem;
  color: #6c757d;
}

.loading-spinner i,
.error-icon i,
.empty-state i {
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

.required {
  color: #dc3545;
}

.text-success { color: #28a745 !important; }
.text-danger { color: #dc3545 !important; }
.text-muted { color: #6c757d !important; }

.mt-3 { margin-top: 1rem; }

@media (max-width: 768px) {
  .approval-cards {
    grid-template-columns: 1fr;
  }
  
  .info-grid {
    grid-template-columns: 1fr;
  }
  
  .impact-stats {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .lines-table-container {
    overflow-x: auto;
  }
  
  .lines-table {
    min-width: 800px;
  }
  
  .search-input {
    min-width: auto;
    width: 100%;
  }
  
  .approval-buttons {
    flex-direction: column-reverse;
  }
  
  .btn {
    width: 100%;
    justify-content: center;
  }
}
</style>