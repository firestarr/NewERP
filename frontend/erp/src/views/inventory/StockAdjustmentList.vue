<template>
  <div class="stock-adjustment-list">
    <div class="page-header">
      <div class="header-left">
        <h1>Stock Adjustments</h1>
        <p class="page-description">Manage inventory stock adjustments</p>
      </div>
      <div class="header-actions">
        <router-link to="/stock-adjustments/create" class="btn btn-primary">
          <i class="fas fa-plus-circle mr-1"></i> Create Adjustment
        </router-link>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-container">
      <div class="filters-card">
        <div class="filters-grid">
          <div class="filter-group">
            <label for="statusFilter">Status</label>
            <select 
              id="statusFilter" 
              v-model="filters.status" 
              @change="fetchAdjustments"
              class="form-control"
            >
              <option value="">All Statuses</option>
              <option value="draft">Draft</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="completed">Completed</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>
          
          <div class="filter-group">
            <label for="dateFrom">Date From</label>
            <input 
              id="dateFrom"
              type="date" 
              v-model="filters.date_from" 
              @change="fetchAdjustments"
              class="form-control"
            >
          </div>
          
          <div class="filter-group">
            <label for="dateTo">Date To</label>
            <input 
              id="dateTo"
              type="date" 
              v-model="filters.date_to" 
              @change="fetchAdjustments"
              class="form-control"
            >
          </div>
          
          <div class="filter-group">
            <label for="searchInput">Search</label>
            <input 
              id="searchInput"
              type="text" 
              v-model="filters.search" 
              @input="debounceSearch"
              placeholder="Search reference or reason..."
              class="form-control"
            >
          </div>
        </div>
        <div class="filters-actions">
          <button class="btn btn-secondary" @click="resetFilters">
            <i class="fas fa-times mr-1"></i> Clear Filters
          </button>
        </div>
      </div>
    </div>

    <div v-if="loading" class="loading-container">
      <div class="loading-spinner">
        <i class="fas fa-spinner fa-spin"></i>
      </div>
      <p>Loading adjustments...</p>
    </div>

    <div v-else-if="error" class="error-container">
      <div class="error-icon">
        <i class="fas fa-exclamation-triangle"></i>
      </div>
      <p>{{ error }}</p>
      <button @click="fetchAdjustments" class="btn btn-secondary">
        <i class="fas fa-sync"></i> Try Again
      </button>
    </div>

    <div v-else-if="adjustments.length === 0" class="empty-state">
      <div class="empty-icon">
        <i class="fas fa-sliders-h"></i>
      </div>
      <h3>No stock adjustments found</h3>
      <p v-if="hasActiveFilters">
        No adjustments match your current filters. Try adjusting your search criteria or
        <button @click="resetFilters" class="btn-link">clear all filters</button>
      </p>
      <p v-else>
        Get started by creating your first stock adjustment.
      </p>
      <button class="btn btn-primary mt-3" @click="createNewAdjustment">
        <i class="fas fa-plus-circle mr-2"></i> Create Adjustment
      </button>
    </div>

    <div v-else class="adjustments-table-container">
      <table class="adjustments-table">
        <thead>
          <tr>
            <th @click="sortBy('adjustment_id')" class="sortable-header">
              ID
              <i v-if="sortField === 'adjustment_id'" 
                :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
            </th>
            <th @click="sortBy('adjustment_date')" class="sortable-header">
              Date
              <i v-if="sortField === 'adjustment_date'" 
                :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
            </th>
            <th>Reference</th>
            <th>Items</th>
            <th>Total Variance</th>
            <th @click="sortBy('status')" class="sortable-header">
              Status
              <i v-if="sortField === 'status'" 
                :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
            </th>
            <th class="actions-column">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="adjustment in adjustments" :key="adjustment.adjustment_id">
            <td class="id-column">
              <span class="adjustment-id">#{{ adjustment.adjustment_id }}</span>
            </td>
            <td>
              <span class="adjustment-date">{{ formatDate(adjustment.adjustment_date) }}</span>
            </td>
            <td>
              <span class="reference-text">{{ adjustment.reference_document || 'N/A' }}</span>
            </td>
            <td>
              <span class="items-count">{{ adjustment.adjustment_lines?.length || 0 }} items</span>
            </td>
            <td>
              <span 
                class="variance-value" 
                :class="getVarianceClass(adjustment.total_variance)"
              >
                {{ formatVariance(adjustment.total_variance) }}
              </span>
            </td>
            <td>
              <span class="status-badge" :class="getStatusClass(adjustment.status)">
                {{ getStatusText(adjustment.status) }}
              </span>
            </td>
            <td class="actions-column">
              <div class="action-buttons">
                <router-link 
                  :to="`/stock-adjustments/${adjustment.adjustment_id}`"
                  class="btn btn-sm btn-outline-primary"
                  title="View Details"
                >
                  <i class="fas fa-eye"></i>
                </router-link>
                
                <router-link 
                  v-if="adjustment.status === 'draft'"
                  :to="`/stock-adjustments/${adjustment.adjustment_id}/edit`"
                  class="btn btn-sm btn-outline-secondary"
                  title="Edit"
                >
                  <i class="fas fa-edit"></i>
                </router-link>
                
                <button 
                  v-if="adjustment.status === 'draft'"
                  @click="submitAdjustment(adjustment)"
                  class="btn btn-sm btn-outline-info"
                  title="Submit for Approval"
                >
                  <i class="fas fa-paper-plane"></i>
                </button>
                
                <router-link 
                  v-if="adjustment.status === 'pending'"
                  :to="`/stock-adjustments/${adjustment.adjustment_id}/approve`"
                  class="btn btn-sm btn-outline-success"
                  title="Approve"
                >
                  <i class="fas fa-check"></i>
                </router-link>
                
                <button 
                  v-if="adjustment.status === 'approved'"
                  @click="processAdjustment(adjustment)"
                  class="btn btn-sm btn-outline-warning"
                  title="Process"
                >
                  <i class="fas fa-cogs"></i>
                </button>
                
                <button 
                  v-if="canDelete(adjustment)"
                  @click="confirmDelete(adjustment)"
                  class="btn btn-sm btn-outline-danger"
                  title="Delete"
                >
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      
      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="pagination-container">
        <div class="pagination-info">
          Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results
        </div>
        <nav class="pagination-nav">
          <button 
            @click="goToPage(1)" 
            :disabled="pagination.current_page === 1"
            class="btn btn-sm btn-outline-secondary"
          >
            First
          </button>
          <button 
            @click="goToPage(pagination.current_page - 1)" 
            :disabled="pagination.current_page === 1"
            class="btn btn-sm btn-outline-secondary"
          >
            Previous
          </button>
          <span class="pagination-current">
            Page {{ pagination.current_page }} of {{ pagination.last_page }}
          </span>
          <button 
            @click="goToPage(pagination.current_page + 1)" 
            :disabled="pagination.current_page === pagination.last_page"
            class="btn btn-sm btn-outline-secondary"
          >
            Next
          </button>
          <button 
            @click="goToPage(pagination.last_page)" 
            :disabled="pagination.current_page === pagination.last_page"
            class="btn btn-sm btn-outline-secondary"
          >
            Last
          </button>
        </nav>
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
            <strong>ID #{{ adjustmentToDelete?.adjustment_id }}</strong>?
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
            :disabled="isDeleting"
          >
            <i v-if="isDeleting" class="fas fa-spinner fa-spin mr-2"></i>
            Delete
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { debounce } from 'lodash';

export default {
  name: 'StockAdjustmentList',
  data() {
    return {
      adjustments: [],
      loading: true,
      error: null,
      showDeleteModal: false,
      adjustmentToDelete: null,
      isDeleting: false,
      filters: {
        status: '',
        date_from: '',
        date_to: '',
        search: '',
        page: 1
      },
      sortField: 'adjustment_date',
      sortDirection: 'desc',
      pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
        from: 0,
        to: 0
      }
    };
  },
  computed: {
    hasActiveFilters() {
      return this.filters.status || 
             this.filters.date_from || 
             this.filters.date_to || 
             this.filters.search;
    }
  },
  mounted() {
    this.fetchAdjustments();
  },
  methods: {
    async fetchAdjustments() {
      this.loading = true;
      this.error = null;
      
      try {
        const params = {
          ...this.filters,
          sort_field: this.sortField,
          sort_direction: this.sortDirection,
          per_page: this.pagination.per_page
        };
        
        // Remove empty filters
        Object.keys(params).forEach(key => {
          if (params[key] === '' || params[key] === null || params[key] === undefined) {
            delete params[key];
          }
        });
        
        const response = await axios.get('/stock-adjustments', { params });
        
        this.adjustments = response.data.data.data || response.data.data;
        this.pagination = {
          current_page: response.data.data.current_page || 1,
          last_page: response.data.data.last_page || 1,
          per_page: response.data.data.per_page || 15,
          total: response.data.data.total || 0,
          from: response.data.data.from || 0,
          to: response.data.data.to || 0
        };
      } catch (err) {
        console.error('Error fetching adjustments:', err);
        this.error = 'Failed to load stock adjustments. Please try again.';
      } finally {
        this.loading = false;
      }
    },

    debounceSearch: debounce(function() {
      this.filters.page = 1;
      this.fetchAdjustments();
    }, 500),

    resetFilters() {
      this.filters = {
        status: '',
        date_from: '',
        date_to: '',
        search: '',
        page: 1
      };
      this.fetchAdjustments();
    },

    sortBy(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortField = field;
        this.sortDirection = 'asc';
      }
      this.fetchAdjustments();
    },

    goToPage(page) {
      this.filters.page = page;
      this.fetchAdjustments();
    },

    createNewAdjustment() {
      this.$router.push('/stock-adjustments/create');
    },

    confirmDelete(adjustment) {
      this.adjustmentToDelete = adjustment;
      this.showDeleteModal = true;
    },

    async deleteAdjustment() {
      if (!this.adjustmentToDelete) return;
      
      this.isDeleting = true;
      
      try {
        await axios.delete(`/stock-adjustments/${this.adjustmentToDelete.adjustment_id}`);
        this.showDeleteModal = false;
        
        // Remove item from list
        this.adjustments = this.adjustments.filter(
          a => a.adjustment_id !== this.adjustmentToDelete.adjustment_id
        );
        
        // Show success message
        this.$toast.success('Stock adjustment deleted successfully');
      } catch (err) {
        console.error('Error deleting adjustment:', err);
        
        if (err.response && err.response.status === 422) {
          this.$toast.error(err.response.data.message || 'Cannot delete this adjustment.');
        } else {
          this.$toast.error('Failed to delete adjustment. Please try again.');
        }
      } finally {
        this.isDeleting = false;
        this.adjustmentToDelete = null;
      }
    },

    async submitAdjustment(adjustment) {
      try {
        await axios.post(`/stock-adjustments/${adjustment.adjustment_id}/submit`);
        
        // Update the status in the local list
        const index = this.adjustments.findIndex(a => a.adjustment_id === adjustment.adjustment_id);
        if (index !== -1) {
          this.adjustments[index].status = 'pending';
        }
        
        this.$toast.success('Adjustment submitted for approval');
      } catch (err) {
        console.error('Error submitting adjustment:', err);
        this.$toast.error('Failed to submit adjustment. Please try again.');
      }
    },

    async processAdjustment(adjustment) {
      if (!confirm('Are you sure you want to process this adjustment? This will update stock levels.')) {
        return;
      }

      try {
        await axios.post(`/stock-adjustments/${adjustment.adjustment_id}/process`);
        
        // Update the status in the local list
        const index = this.adjustments.findIndex(a => a.adjustment_id === adjustment.adjustment_id);
        if (index !== -1) {
          this.adjustments[index].status = 'completed';
        }
        
        this.$toast.success('Adjustment processed successfully');
      } catch (err) {
        console.error('Error processing adjustment:', err);
        this.$toast.error('Failed to process adjustment. Please try again.');
      }
    },

    canDelete(adjustment) {
      return ['draft', 'rejected'].includes(adjustment.status);
    },

    formatDate(dateString) {
      if (!dateString) return 'N/A';
      return new Date(dateString).toLocaleDateString();
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
    }
  }
};
</script>

<style scoped>
.stock-adjustment-list {
  padding: 1.5rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #dee2e6;
}

.header-left h1 {
  margin: 0;
  color: #495057;
  font-size: 1.75rem;
}

.page-description {
  margin: 0.25rem 0 0 0;
  color: #6c757d;
  font-size: 0.875rem;
}

.filters-container {
  margin-bottom: 1.5rem;
}

.filters-card {
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 6px;
  padding: 1rem;
}

.filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.filter-group label {
  display: block;
  font-weight: 500;
  margin-bottom: 0.5rem;
  color: #495057;
  font-size: 0.875rem;
}

.form-control {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #ced4da;
  border-radius: 4px;
  font-size: 0.875rem;
}

.filters-actions {
  display: flex;
  justify-content: flex-end;
}

.adjustments-table-container {
  background: white;
  border-radius: 6px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.adjustments-table {
  width: 100%;
  border-collapse: collapse;
}

.adjustments-table th,
.adjustments-table td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid #dee2e6;
}

.adjustments-table th {
  background-color: #f8f9fa;
  font-weight: 600;
  color: #495057;
  font-size: 0.875rem;
}

.sortable-header {
  cursor: pointer;
  user-select: none;
  transition: background-color 0.2s;
}

.sortable-header:hover {
  background-color: #e9ecef;
}

.sortable-header i {
  margin-left: 0.5rem;
  color: #6c757d;
}

.adjustment-id {
  font-family: monospace;
  font-weight: 600;
  color: #495057;
}

.adjustment-date {
  color: #495057;
}

.reference-text {
  color: #6c757d;
  font-style: italic;
}

.items-count {
  color: #6c757d;
  font-size: 0.875rem;
}

.variance-value {
  font-weight: 600;
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

.action-buttons {
  display: flex;
  gap: 0.25rem;
}

.btn {
  padding: 0.375rem 0.75rem;
  border: 1px solid;
  border-radius: 4px;
  font-size: 0.875rem;
  cursor: pointer;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  transition: all 0.2s;
}

.btn:hover {
  transform: translateY(-1px);
}

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
}

.btn-primary { background: #007bff; color: white; border-color: #007bff; }
.btn-secondary { background: #6c757d; color: white; border-color: #6c757d; }
.btn-outline-primary { background: transparent; color: #007bff; border-color: #007bff; }
.btn-outline-secondary { background: transparent; color: #6c757d; border-color: #6c757d; }
.btn-outline-info { background: transparent; color: #17a2b8; border-color: #17a2b8; }
.btn-outline-success { background: transparent; color: #28a745; border-color: #28a745; }
.btn-outline-warning { background: transparent; color: #ffc107; border-color: #ffc107; }
.btn-outline-danger { background: transparent; color: #dc3545; border-color: #dc3545; }

.btn-link {
  background: none;
  border: none;
  color: #007bff;
  text-decoration: underline;
  cursor: pointer;
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
.empty-icon i {
  font-size: 3rem;
  margin-bottom: 1rem;
  display: block;
}

.pagination-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background-color: #f8f9fa;
  border-top: 1px solid #dee2e6;
}

.pagination-nav {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.pagination-current {
  margin: 0 1rem;
  font-weight: 500;
  color: #495057;
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
  
  .filters-grid {
    grid-template-columns: 1fr;
  }
  
  .adjustments-table-container {
    overflow-x: auto;
  }
  
  .adjustments-table {
    min-width: 800px;
  }
  
  .pagination-container {
    flex-direction: column;
    gap: 1rem;
  }
}
</style>