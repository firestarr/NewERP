<template>
  <div class="stock-adjustment-detail">
    <div class="page-header">
      <div class="header-left">
        <router-link to="/stock-adjustments" class="back-link">
          <i class="fas fa-arrow-left"></i> Back to Adjustments
        </router-link>
        <div class="header-info">
          <h1>Stock Adjustment #{{ adjustmentId }}</h1>
          <span 
            v-if="adjustment" 
            class="status-badge" 
            :class="getStatusClass(adjustment.status)"
          >
            {{ getStatusText(adjustment.status) }}
          </span>
        </div>
      </div>
      <div class="header-actions" v-if="adjustment">
        <router-link 
          v-if="adjustment.status === 'draft'"
          :to="`/stock-adjustments/${adjustmentId}/edit`"
          class="btn btn-secondary"
        >
          <i class="fas fa-edit"></i> Edit
        </router-link>
        
        <button 
          v-if="adjustment.status === 'draft'"
          @click="submitForApproval"
          :disabled="isProcessing"
          class="btn btn-info"
        >
          <i v-if="isProcessing" class="fas fa-spinner fa-spin mr-2"></i>
          <i v-else class="fas fa-paper-plane mr-2"></i>
          Submit for Approval
        </button>
        
        <router-link 
          v-if="adjustment.status === 'pending'"
          :to="`/stock-adjustments/${adjustmentId}/approve`"
          class="btn btn-success"
        >
          <i class="fas fa-check"></i> Approve
        </router-link>
        
        <button 
          v-if="adjustment.status === 'approved'"
          @click="processAdjustment"
          :disabled="isProcessing"
          class="btn btn-warning"
        >
          <i v-if="isProcessing" class="fas fa-spinner fa-spin mr-2"></i>
          <i v-else class="fas fa-cogs mr-2"></i>
          Process
        </button>
        
        <button 
          v-if="canDelete"
          @click="showDeleteModal = true"
          :disabled="isProcessing"
          class="btn btn-danger"
        >
          <i class="fas fa-trash"></i> Delete
        </button>
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

    <div v-else-if="adjustment" class="adjustment-details">
      <!-- Adjustment Information -->
      <div class="detail-card">
        <div class="card-header">
          <h3>Adjustment Information</h3>
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
              <div class="info-label">Status</div>
              <div class="info-value">
                <span class="status-badge" :class="getStatusClass(adjustment.status)">
                  {{ getStatusText(adjustment.status) }}
                </span>
              </div>
            </div>
            <div class="info-item">
              <div class="info-label">Created</div>
              <div class="info-value">{{ formatDateTime(adjustment.created_at) }}</div>
            </div>
            <div class="info-item full-width">
              <div class="info-label">Adjustment Reason</div>
              <div class="info-value reason-text">{{ adjustment.adjustment_reason }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Summary Statistics -->
      <div class="detail-card">
        <div class="card-header">
          <h3>Summary</h3>
        </div>
        <div class="card-body">
          <div class="summary-stats">
            <div class="stat-item">
              <div class="stat-value">{{ adjustment.adjustment_lines?.length || 0 }}</div>
              <div class="stat-label">Total Lines</div>
            </div>
            <div class="stat-item">
              <div class="stat-value" :class="getVarianceClass(totalVariance)">
                {{ formatVariance(totalVariance) }}
              </div>
              <div class="stat-label">Total Variance</div>
            </div>
            <div class="stat-item">
              <div class="stat-value text-success">{{ positiveLines }}</div>
              <div class="stat-label">Increases</div>
            </div>
            <div class="stat-item">
              <div class="stat-value text-danger">{{ negativeLines }}</div>
              <div class="stat-label">Decreases</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Adjustment Lines -->
      <div class="detail-card">
        <div class="card-header">
          <h3>Adjustment Lines</h3>
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
                  <th class="text-right">Variance %</th>
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
                      class="variance-percentage"
                      :class="getVarianceClass(line.variance)"
                    >
                      {{ formatVariancePercentage(line) }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Activity Log (if available) -->
      <div v-if="adjustment.activity_log" class="detail-card">
        <div class="card-header">
          <h3>Activity Log</h3>
        </div>
        <div class="card-body">
          <div class="activity-timeline">
            <div 
              v-for="activity in adjustment.activity_log" 
              :key="activity.id"
              class="activity-item"
            >
              <div class="activity-icon">
                <i :class="getActivityIcon(activity.action)"></i>
              </div>
              <div class="activity-content">
                <div class="activity-action">{{ activity.description }}</div>
                <div class="activity-meta">
                  <span class="activity-user">{{ activity.user?.name }}</span>
                  <span class="activity-time">{{ formatDateTime(activity.created_at) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal-backdrop">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Confirm Delete</h3>
          <button class="btn-close" @click="showDeleteModal = false">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <p>
            Are you sure you want to delete stock adjustment 
            <strong>ID #{{ adjustmentId }}</strong>?
          </p>
          <p class="text-danger">This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="showDeleteModal = false">
            Cancel
          </button>
          <button 
            class="btn btn-danger" 
            @click="deleteAdjustment" 
            :disabled="isProcessing"
          >
            <i v-if="isProcessing" class="fas fa-spinner fa-spin mr-2"></i>
            Delete
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'StockAdjustmentDetail',
  data() {
    return {
      adjustment: null,
      loading: true,
      error: null,
      searchQuery: '',
      showDeleteModal: false,
      isProcessing: false
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
    canDelete() {
      return this.adjustment && ['draft', 'rejected'].includes(this.adjustment.status);
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

    async submitForApproval() {
      if (!confirm('Are you sure you want to submit this adjustment for approval?')) {
        return;
      }

      this.isProcessing = true;
      
      try {
        await axios.post(`/stock-adjustments/${this.adjustmentId}/submit`);
        this.adjustment.status = 'pending';
        this.$toast.success('Adjustment submitted for approval');
      } catch (err) {
        console.error('Error submitting adjustment:', err);
        this.$toast.error('Failed to submit adjustment. Please try again.');
      } finally {
        this.isProcessing = false;
      }
    },

    async processAdjustment() {
      if (!confirm('Are you sure you want to process this adjustment? This will update stock levels.')) {
        return;
      }

      this.isProcessing = true;
      
      try {
        await axios.post(`/stock-adjustments/${this.adjustmentId}/process`);
        this.adjustment.status = 'completed';
        this.$toast.success('Adjustment processed successfully');
      } catch (err) {
        console.error('Error processing adjustment:', err);
        this.$toast.error('Failed to process adjustment. Please try again.');
      } finally {
        this.isProcessing = false;
      }
    },

    async deleteAdjustment() {
      this.isProcessing = true;
      
      try {
        await axios.delete(`/stock-adjustments/${this.adjustmentId}`);
        this.$toast.success('Stock adjustment deleted successfully');
        this.$router.push('/stock-adjustments');
      } catch (err) {
        console.error('Error deleting adjustment:', err);
        this.$toast.error('Failed to delete adjustment. Please try again.');
        this.showDeleteModal = false;
      } finally {
        this.isProcessing = false;
      }
    },

    formatDate(dateString) {
      if (!dateString) return 'N/A';
      return new Date(dateString).toLocaleDateString();
    },

    formatDateTime(dateString) {
      if (!dateString) return 'N/A';
      return new Date(dateString).toLocaleString();
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

    formatVariancePercentage(line) {
      if (!line.book_quantity || line.book_quantity === 0) return 'N/A';
      const percentage = ((line.variance || 0) / line.book_quantity) * 100;
      const sign = percentage >= 0 ? '+' : '';
      return `${sign}${percentage.toFixed(2)}%`;
    },

    getVarianceClass(variance) {
      if (variance > 0) return 'text-success';
      if (variance < 0) return 'text-danger';
      return 'text-muted';
    },

    getStatusClass(status) {
      const statusClasses = {
        'draft': 'badge-secondary',
        'pending': 'badge-warning',
        'approved': 'badge-info',
        'completed': 'badge-success',
        'rejected': 'badge-danger'
      };
      return `status-badge ${statusClasses[status] || 'badge-light'}`;
    },

    getStatusText(status) {
      const statusTexts = {
        'draft': 'Draft',
        'pending': 'Pending',
        'approved': 'Approved',
        'completed': 'Completed',
        'rejected': 'Rejected'
      };
      return statusTexts[status] || status;
    },

    getItemUnit(item) {
      return item?.unit_of_measure?.symbol || 'units';
    },

    getActivityIcon(action) {
      const icons = {
        'created': 'fas fa-plus-circle',
        'updated': 'fas fa-edit',
        'submitted': 'fas fa-paper-plane',
        'approved': 'fas fa-check-circle',
        'rejected': 'fas fa-times-circle',
        'processed': 'fas fa-cogs',
        'deleted': 'fas fa-trash'
      };
      return icons[action] || 'fas fa-info-circle';
    }
  }
};
</script>

<style scoped>
.stock-adjustment-detail {
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

.header-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.header-info h1 {
  margin: 0;
  color: #495057;
  font-size: 1.75rem;
}

.header-actions {
  display: flex;
  gap: 0.5rem;
}

.detail-card {
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

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
}

.reason-text {
  background: #f8f9fa;
  padding: 0.75rem;
  border-radius: 4px;
  border: 1px solid #dee2e6;
  white-space: pre-wrap;
}

.summary-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
}

.stat-item {
  text-align: center;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 6px;
  border: 1px solid #dee2e6;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #495057;
  margin-bottom: 0.25rem;
}

.stat-label {
  font-size: 0.875rem;
  color: #6c757d;
  text-transform: uppercase;
  letter-spacing: 0.025em;
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

.variance-percentage {
  font-weight: 600;
  font-size: 0.8125rem;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.badge-secondary { background-color: #6c757d; color: white; }
.badge-warning { background-color: #ffc107; color: #212529; }
.badge-info { background-color: #17a2b8; color: white; }
.badge-success { background-color: #28a745; color: white; }
.badge-danger { background-color: #dc3545; color: white; }
.badge-light { background-color: #f8f9fa; color: #495057; }

.activity-timeline {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.activity-item {
  display: flex;
  gap: 1rem;
  align-items: flex-start;
}

.activity-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  background: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 50%;
  color: #6c757d;
  flex-shrink: 0;
}

.activity-content {
  flex: 1;
}

.activity-action {
  font-weight: 500;
  color: #495057;
  margin-bottom: 0.25rem;
}

.activity-meta {
  display: flex;
  gap: 1rem;
  font-size: 0.875rem;
  color: #6c757d;
}

.btn {
  padding: 0.5rem 1rem;
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

.btn-secondary { background: #6c757d; color: white; border-color: #6c757d; }
.btn-info { background: #17a2b8; color: white; border-color: #17a2b8; }
.btn-success { background: #28a745; color: white; border-color: #28a745; }
.btn-warning { background: #ffc107; color: #212529; border-color: #ffc107; }
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

.text-success { color: #28a745 !important; }
.text-danger { color: #dc3545 !important; }
.text-muted { color: #6c757d !important; }

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .header-actions {
    flex-wrap: wrap;
  }
  
  .info-grid {
    grid-template-columns: 1fr;
  }
  
  .summary-stats {
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
}
</style>