<!-- src/views/purchasing/PurchaseRequisitionList.vue -->
<template>
  <div class="purchase-requisition-list">
    <!-- Page Header -->
    <div class="page-header">
      <h1>
        <i class="fas fa-file-alt me-2"></i>
        Purchase Requisitions
      </h1>
      <router-link to="/purchasing/requisitions/create" class="btn-create">
        <i class="fas fa-plus me-2"></i>Create New PR
      </router-link>
    </div>

    <!-- Filters Card -->
    <div class="filters-card-full">
      <div class="filters-header-full">
        <div class="filters-title">
          <i class="fas fa-filter me-2"></i>
          <span>Filters & Search</span>
        </div>
        <button class="clear-filters-btn" @click="clearFilters">
          <i class="fas fa-times me-1"></i>Clear All
        </button>
      </div>
      <div class="filters-body-full">
        <div class="container-fluid px-0">
          <div class="row g-4 mx-0">
            <div class="col-12 col-lg-3 col-md-6">
              <div class="filter-group">
                <label class="filter-label">Status</label>
                <select
                  v-model="filters.status"
                  class="form-control-custom"
                  @change="fetchPurchaseRequisitions()"
                >
                  <option value="">All Statuses</option>
                  <option value="draft">Draft</option>
                  <option value="pending">Pending</option>
                  <option value="approved">Approved</option>
                  <option value="rejected">Rejected</option>
                  <option value="canceled">Canceled</option>
                </select>
              </div>
            </div>
            <div class="col-12 col-lg-3 col-md-6">
              <div class="filter-group">
                <label class="filter-label">Date From</label>
                <input
                  type="date"
                  v-model="filters.dateFrom"
                  class="form-control-custom"
                  @change="fetchPurchaseRequisitions()"
                />
              </div>
            </div>
            <div class="col-12 col-lg-3 col-md-6">
              <div class="filter-group">
                <label class="filter-label">Date To</label>
                <input
                  type="date"
                  v-model="filters.dateTo"
                  class="form-control-custom"
                  @change="fetchPurchaseRequisitions()"
                />
              </div>
            </div>
            <div class="col-12 col-lg-3 col-md-6">
              <div class="filter-group">
                <label class="filter-label">Search</label>
                <div class="search-group-full">
                  <input
                    type="text"
                    v-model="filters.search"
                    class="form-control-custom search-input-full"
                    placeholder="PR Number or Requester..."
                    @keyup.enter="fetchPurchaseRequisitions()"
                  />
                  <button class="search-btn-full" @click="fetchPurchaseRequisitions()">
                    <i class="fas fa-search"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-spinner">
      <div class="spinner"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="purchaseRequisitions.length === 0" class="empty-state">
      <div class="empty-state-container">
        <i class="fas fa-file-alt empty-state-icon"></i>
        <h3>No Purchase Requisitions Found</h3>
        <p>No purchase requisitions match your current filters. Try adjusting your filters or create a new requisition.</p>
        <router-link to="/purchasing/requisitions/create" class="btn btn-primary btn-lg">
          <i class="fas fa-plus me-2"></i>Create New PR
        </router-link>
      </div>
    </div>

    <!-- Table Container -->
    <div v-else class="table-container">
      <div class="table-header">
        <div>
          <i class="fas fa-table me-2"></i>
          <span>Purchase Requisitions</span>
          <span class="badge bg-primary ms-2">{{ pagination.total }} items</span>
        </div>
        <div>
          <button class="btn btn-sm btn-outline-secondary me-2" @click="exportData">
            <i class="fas fa-download me-1"></i>Export
          </button>
          <button class="btn btn-sm btn-outline-primary" @click="fetchPurchaseRequisitions">
            <i class="fas fa-sync-alt me-1"></i>Refresh
          </button>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table-custom">
          <thead>
            <tr>
              <th
                class="sortable-column col-pr-number"
                @click="sortBy('pr_number')"
              >
                PR Number
                <i v-if="sortField === 'pr_number'"
                   :class="[sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down']">
                </i>
                <i v-else class="fas fa-sort"></i>
              </th>
              <th
                class="sortable-column col-date"
                @click="sortBy('pr_date')"
              >
                Date
                <i v-if="sortField === 'pr_date'"
                   :class="[sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down']">
                </i>
                <i v-else class="fas fa-sort"></i>
              </th>
              <th class="col-requester">Requester</th>
              <th
                class="sortable-column col-status"
                @click="sortBy('status')"
              >
                Status
                <i v-if="sortField === 'status'"
                   :class="[sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down']">
                </i>
                <i v-else class="fas fa-sort"></i>
              </th>
              <th class="col-items text-center">Items</th>
              <th class="col-actions text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="pr in purchaseRequisitions" :key="pr.pr_id" class="table-row">
              <td>
                <span class="pr-number-text">
                  {{ pr.pr_number }}
                </span>
              </td>
              <td>{{ formatDate(pr.pr_date) }}</td>
              <td>
                <div class="requester-info">
                  <div class="requester-avatar">
                    {{ getInitials(pr.requester ? pr.requester.name : 'N/A') }}
                  </div>
                  <span>{{ pr.requester ? pr.requester.name : 'N/A' }}</span>
                </div>
              </td>
              <td>
                <span :class="getStatusBadgeClass(pr.status)">
                  {{ capitalizeStatus(pr.status) }}
                </span>
              </td>
              <td class="text-center">
                <span class="items-badge">
                  {{ pr.lines ? pr.lines.length : 0 }}
                </span>
              </td>
              <td>
                <div class="actions-container">
                  <router-link
                    :to="`/purchasing/requisitions/${pr.pr_id}`"
                    class="action-btn btn-view"
                    title="View Details"
                  >
                    <i class="fas fa-eye"></i>
                  </router-link>

                  <router-link
                    v-if="pr.status === 'draft'"
                    :to="`/purchasing/requisitions/${pr.pr_id}/edit`"
                    class="action-btn btn-edit"
                    title="Edit"
                  >
                    <i class="fas fa-edit"></i>
                  </router-link>

                  <router-link
                    v-if="pr.status === 'pending'"
                    :to="`/purchasing/requisitions/${pr.pr_id}/approve`"
                    class="action-btn btn-approve"
                    title="Approve/Reject"
                  >
                    <i class="fas fa-check-circle"></i>
                  </router-link>

                  <router-link
                    v-if="pr.status === 'approved'"
                    :to="`/purchasing/requisitions/${pr.pr_id}/convert`"
                    class="action-btn btn-convert"
                    title="Convert to RFQ"
                  >
                    <i class="fas fa-exchange-alt"></i>
                  </router-link>

                  <button
                    v-if="['draft', 'pending'].includes(pr.status)"
                    @click="confirmCancelPR(pr)"
                    class="action-btn btn-cancel"
                    title="Cancel"
                  >
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div class="pagination-container" v-if="purchaseRequisitions.length > 0">
      <div class="pagination-info">
        Showing <strong>{{ pagination.from }}</strong> to <strong>{{ pagination.to }}</strong>
        of <strong>{{ pagination.total }}</strong> entries
      </div>
      <div class="pagination-controls">
        <button
          class="pagination-btn"
          :disabled="pagination.current_page === 1"
          @click="goToPage(pagination.current_page - 1)"
        >
          <i class="fas fa-chevron-left"></i>
        </button>

        <template v-for="page in displayedPages" :key="page">
          <button
            v-if="page !== '...'"
            class="pagination-btn"
            :class="{ active: page === pagination.current_page }"
            @click="goToPage(page)"
          >
            {{ page }}
          </button>
          <span v-else class="pagination-ellipsis">...</span>
        </template>

        <button
          class="pagination-btn"
          :disabled="pagination.current_page === pagination.last_page"
          @click="goToPage(pagination.current_page + 1)"
        >
          <i class="fas fa-chevron-right"></i>
        </button>
      </div>
    </div>

    <!-- Confirmation Modal -->
    <div v-if="showCancelModal" class="modal-backdrop" @click="showCancelModal = false"></div>
    <div v-if="showCancelModal" class="modal cancel-modal">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
            Cancel Purchase Requisition
          </h5>
          <button type="button" class="close" @click="showCancelModal = false">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="alert alert-warning">
            <i class="fas fa-info-circle me-2"></i>
            This action cannot be undone.
          </div>
          <p>Are you sure you want to cancel purchase requisition <strong>{{ selectedPR ? selectedPR.pr_number : '' }}</strong>?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary modal-btn" @click="showCancelModal = false">
            <i class="fas fa-times me-1"></i>Cancel
          </button>
          <button type="button" class="btn btn-danger modal-btn" @click="cancelPR">
            <i class="fas fa-check me-1"></i>Confirm Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'PurchaseRequisitionList',
  data() {
    return {
      purchaseRequisitions: [],
      loading: true,
      filters: {
        status: '',
        search: '',
        dateFrom: '',
        dateTo: '',
      },
      sortField: 'pr_date',
      sortDirection: 'desc',
      pagination: {
        current_page: 1,
        from: 1,
        to: 10,
        total: 0,
        per_page: 10,
        last_page: 1
      },
      showCancelModal: false,
      selectedPR: null
    };
  },
  computed: {
    displayedPages() {
      const total = this.pagination.last_page;
      const current = this.pagination.current_page;
      const pages = [];

      if (total <= 7) {
        for (let i = 1; i <= total; i++) {
          pages.push(i);
        }
      } else {
        pages.push(1);

        if (current > 3) {
          pages.push('...');
        }

        const startPage = Math.max(2, current - 1);
        const endPage = Math.min(total - 1, current + 1);

        for (let i = startPage; i <= endPage; i++) {
          pages.push(i);
        }

        if (current < total - 2) {
          pages.push('...');
        }

        if (total > 1) {
          pages.push(total);
        }
      }

      return pages;
    }
  },
  created() {
    this.fetchPurchaseRequisitions();
  },
  methods: {
    async fetchPurchaseRequisitions() {
      this.loading = true;
      try {
        const params = {
          page: this.pagination.current_page,
          per_page: this.pagination.per_page,
          sort_field: this.sortField,
          sort_direction: this.sortDirection
        };

        if (this.filters.status) params.status = this.filters.status;
        if (this.filters.search) params.search = this.filters.search;
        if (this.filters.dateFrom) params.date_from = this.filters.dateFrom;
        if (this.filters.dateTo) params.date_to = this.filters.dateTo;

        const response = await axios.get('/purchase-requisitions', { params });

        this.purchaseRequisitions = response.data.data.data;

        this.pagination = {
          current_page: response.data.data.current_page,
          from: response.data.data.from,
          to: response.data.data.to,
          total: response.data.data.total,
          per_page: response.data.data.per_page,
          last_page: response.data.data.last_page
        };
      } catch (error) {
        console.error('Error fetching purchase requisitions:', error);
        this.showAlert('danger', 'Failed to load purchase requisitions. Please try again.');
      } finally {
        this.loading = false;
      }
    },

    sortBy(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortField = field;
        this.sortDirection = 'asc';
      }

      this.pagination.current_page = 1;
      this.fetchPurchaseRequisitions();
    },

    goToPage(page) {
      if (page < 1 || page > this.pagination.last_page) return;

      this.pagination.current_page = page;
      this.fetchPurchaseRequisitions();
    },

    clearFilters() {
      this.filters = {
        status: '',
        search: '',
        dateFrom: '',
        dateTo: '',
      };
      this.pagination.current_page = 1;
      this.fetchPurchaseRequisitions();
    },

    formatDate(dateString) {
      if (!dateString) return 'N/A';

      const date = new Date(dateString);
      return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    },

    getStatusBadgeClass(status) {
      const statusClasses = {
        draft: 'status-badge status-draft',
        pending: 'status-badge status-pending',
        approved: 'status-badge status-approved',
        rejected: 'status-badge status-rejected',
        canceled: 'status-badge status-canceled'
      };

      return statusClasses[status] || 'status-badge status-draft';
    },

    capitalizeStatus(status) {
      if (!status) return '';
      return status.charAt(0).toUpperCase() + status.slice(1);
    },

    truncateText(text, maxLength) {
      if (!text) return '';
      if (text.length <= maxLength) return text;

      return text.substring(0, maxLength) + '...';
    },

    getInitials(name) {
      if (!name || name === 'N/A') return 'NA';
      return name.split(' ').map(n => n[0]).join('').toUpperCase();
    },

    confirmCancelPR(pr) {
      this.selectedPR = pr;
      this.showCancelModal = true;
    },

    async cancelPR() {
      if (!this.selectedPR) return;

      try {
        await axios.patch(`/purchase-requisitions/${this.selectedPR.pr_id}/status`, {
          status: 'canceled'
        });

        this.showCancelModal = false;

        this.showAlert('success', `Purchase Requisition ${this.selectedPR.pr_number} has been canceled successfully.`);

        this.fetchPurchaseRequisitions();
      } catch (error) {
        console.error('Error canceling purchase requisition:', error);

        this.showAlert('danger', 'Failed to cancel the purchase requisition. Please try again.');
      }
    },

    exportData() {
      this.showAlert('info', 'Exporting data...');
      // Add export logic here
    },

    showAlert(type, message) {
      this.$root.$emit('showAlert', { type, message });
    }
  }
};
</script>

<style scoped>
:root {
  --primary-color: #007bff;
  --success-color: #28a745;
  --warning-color: #ffc107;
  --danger-color: #dc3545;
  --info-color: #17a2b8;
  --secondary-color: #6c757d;
  --light-color: #f8f9fa;
  --dark-color: #343a40;
  --border-radius: 0.5rem;
  --box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.purchase-requisition-list {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
  background-color: #f5f7fa;
  min-height: 100vh;
}

.page-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 2rem;
  border-radius: var(--border-radius);
  margin-bottom: 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: var(--box-shadow);
}

.page-header h1 {
  margin: 0;
  font-size: 2rem;
  font-weight: 700;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.btn-create {
  background: rgba(255, 255, 255, 0.2);
  border: 2px solid rgba(255, 255, 255, 0.3);
  color: white;
  padding: 0.75rem 1.5rem;
  border-radius: var(--border-radius);
  font-weight: 600;
  transition: var(--transition);
  backdrop-filter: blur(10px);
  text-decoration: none;
  display: inline-flex;
  align-items: center;
}

.btn-create:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
  color: white;
  text-decoration: none;
}

.filters-card-full {
  background: white;
  border-radius: var(--border-radius);
  box-shadow: var(--box-shadow);
  margin-bottom: 2rem;
  overflow: hidden;
  width: 100%;
  border: 1px solid #e9ecef;
}

.filters-header-full {
  background: linear-gradient(90deg, #f8f9fa 0%, #e9ecef 100%);
  padding: 1.25rem 1.5rem;
  border-bottom: 2px solid #dee2e6;
  font-weight: 600;
  color: var(--dark-color);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.filters-title {
  display: flex;
  align-items: center;
  font-size: 1.1rem;
  font-weight: 700;
}

.filters-body-full {
  padding: 1.5rem;
  background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
}

.filter-group {
  margin-bottom: 0;
  width: 100%;
}

.filter-label {
  font-weight: 700;
  color: var(--dark-color);
  margin-bottom: 0.75rem;
  display: block;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.form-control-custom {
  border: 2px solid #e9ecef;
  border-radius: var(--border-radius);
  padding: 0.875rem 1rem;
  transition: var(--transition);
  background: #fff;
  font-size: 0.95rem;
  width: 100%;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  min-height: 48px;
}

.form-control-custom:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
  transform: translateY(-1px);
  outline: none;
  background: #fff;
}

.search-group-full {
  position: relative;
  width: 100%;
}

.search-input-full {
  padding-right: 30.5rem;
  width: 100%;
}

.search-btn-full {
  position: absolute;
  right: 8px;
  top: 50%;
  transform: translateY(-50%);
  border: none;
  background: var(--primary-color);
  color: white;
  width: 2.75rem;
  height: 2.75rem;
  border-radius: calc(var(--border-radius) - 2px);
  transition: var(--transition);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.search-btn-full:hover {
  background: #0056b3;
  transform: translateY(-50%) scale(1.05);
  box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
}

.clear-filters-btn {
  background: linear-gradient(135deg, #6c757d, #495057);
  color: white;
  border: none;
  padding: 0.625rem 1.25rem;
  border-radius: var(--border-radius);
  font-size: 0.875rem;
  font-weight: 600;
  transition: var(--transition);
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.clear-filters-btn:hover {
  background: linear-gradient(135deg, #545b62, #343a40);
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

/* Ensure full width utilization */
.container-fluid {
  width: 100%;
  max-width: none;
  padding: 0;
}

.row.mx-0 {
  margin-left: 0;
  margin-right: 0;
}

.col-12,
.col-lg-3,
.col-md-6 {
  padding-left: 0.75rem;
  padding-right: 0.75rem;
}

.table-container {
  background: white;
  border-radius: var(--border-radius);
  box-shadow: var(--box-shadow);
  overflow: hidden;
  margin-bottom: 2rem;
}

.table-header {
  background: linear-gradient(90deg, #f8f9fa 0%, #e9ecef 100%);
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #dee2e6;
  font-weight: 600;
  color: var(--dark-color);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.table-custom {
  margin: 0;
  width: 100%;
  border-collapse: collapse;
}

.table-custom th {
  background: #f8f9fa;
  color: var(--dark-color);
  font-weight: 600;
  padding: 1.25rem 1rem;
  border: none;
  position: relative;
  white-space: nowrap;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.table-custom td {
  padding: 1.25rem 1rem;
  vertical-align: middle;
  border-bottom: 1px solid #f0f0f0;
  transition: var(--transition);
}

.table-row {
  transition: var(--transition);
}

.table-row:hover {
  background: linear-gradient(90deg, #f8f9ff 0%, #f0f8ff 100%);
  transform: scale(1.001);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.sortable-column {
  cursor: pointer;
  user-select: none;
  transition: var(--transition);
}

.sortable-column:hover {
  color: var(--primary-color);
  background: rgba(0, 123, 255, 0.05);
}

.sortable-column i {
  margin-left: 0.5rem;
  opacity: 0.6;
  transition: var(--transition);
}

.sortable-column:hover i {
  opacity: 1;
}

.pr-number-text {
  color: var(--dark-color);
  font-weight: 600;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  transition: var(--transition);
  display: inline-block;
  background: rgba(0, 123, 255, 0.05);
  border: 1px solid rgba(0, 123, 255, 0.1);
}

.requester-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.requester-avatar {
  width: 2rem;
  height: 2rem;
  background: linear-gradient(135deg, #667eea, #764ba2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
  font-size: 0.75rem;
}

.status-badge {
  padding: 0.5rem 1rem;
  border-radius: 2rem;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border: 2px solid transparent;
  transition: var(--transition);
}

.status-badge:hover {
  transform: scale(1.05);
}

.status-draft {
  background: linear-gradient(135deg, #6c757d, #495057);
  color: white;
}

.status-pending {
  background: linear-gradient(135deg, #ffc107, #fd7e14);
  color: #212529;
}

.status-approved {
  background: linear-gradient(135deg, #28a745, #20c997);
  color: white;
}

.status-rejected {
  background: linear-gradient(135deg, #dc3545, #c82333);
  color: white;
}

.status-canceled {
  background: linear-gradient(135deg, #6c757d, #495057);
  color: white;
}

.items-badge {
  background: #e9ecef;
  padding: 0.375rem 0.75rem;
  border-radius: 1rem;
  font-weight: 600;
  color: #495057;
  font-size: 0.875rem;
}

.actions-container {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
}

.action-btn {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: var(--transition);
  cursor: pointer;
  position: relative;
  overflow: hidden;
  text-decoration: none;
}

.action-btn::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0;
  height: 0;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.3);
  transition: var(--transition);
  transform: translate(-50%, -50%);
}

.action-btn:hover::before {
  width: 100%;
  height: 100%;
}

.action-btn:hover {
  transform: translateY(-2px) scale(1.1);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
  text-decoration: none;
}

.btn-view {
  background: linear-gradient(135deg, #17a2b8, #138496);
  color: white;
}

.btn-edit {
  background: linear-gradient(135deg, #007bff, #0056b3);
  color: white;
}

.btn-approve {
  background: linear-gradient(135deg, #28a745, #1e7e34);
  color: white;
}

.btn-convert {
  background: linear-gradient(135deg, #ffc107, #e0a800);
  color: #212529;
}

.btn-cancel {
  background: linear-gradient(135deg, #dc3545, #c82333);
  color: white;
}

.pagination-container {
  background: white;
  border-radius: var(--border-radius);
  box-shadow: var(--box-shadow);
  padding: 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.pagination-info {
  color: var(--secondary-color);
  font-weight: 500;
}

.pagination-controls {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.pagination-btn {
  width: 2.5rem;
  height: 2.5rem;
  border: 2px solid #e9ecef;
  background: white;
  color: var(--dark-color);
  border-radius: var(--border-radius);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: var(--transition);
  cursor: pointer;
  font-weight: 600;
}

.pagination-btn:hover:not(:disabled) {
  border-color: var(--primary-color);
  color: var(--primary-color);
  transform: translateY(-1px);
}

.pagination-btn.active {
  background: var(--primary-color);
  border-color: var(--primary-color);
  color: white;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-ellipsis {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2.5rem;
  height: 2.5rem;
  color: var(--secondary-color);
  font-weight: 500;
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  background: white;
  border-radius: var(--border-radius);
  box-shadow: var(--box-shadow);
}

.empty-state-container {
  max-width: 500px;
  margin: 0 auto;
}

.empty-state-icon {
  font-size: 4rem;
  color: #dee2e6;
  margin-bottom: 1.5rem;
  animation: float 3s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-10px); }
}

.loading-spinner {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 300px;
}

.spinner {
  width: 3rem;
  height: 3rem;
  border: 4px solid #f3f3f3;
  border-top: 4px solid var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Modal Styles */
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 1000;
  backdrop-filter: blur(2px);
}

.modal {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 1001;
  display: block;
  width: 100%;
  max-width: 500px;
  margin: 1rem;
}

.modal-content {
  background-color: white;
  border-radius: var(--border-radius);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
  overflow: hidden;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #dee2e6;
  background-color: #f8f9fa;
}

.modal-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--dark-color);
}

.close {
  background-color: transparent;
  border: none;
  font-size: 1.5rem;
  font-weight: 700;
  color: #000;
  opacity: 0.5;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 0.25rem;
  transition: var(--transition);
}

.close:hover {
  opacity: 0.75;
  background-color: #f8f9fa;
}

.modal-body {
  padding: 1.5rem;
  line-height: 1.6;
}

.modal-body p {
  margin-bottom: 1rem;
}

.modal-body p:last-child {
  margin-bottom: 0;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1.5rem;
  border-top: 1px solid #dee2e6;
  background-color: #f8f9fa;
}

.modal-btn {
  padding: 0.5rem 1.5rem;
  border-radius: var(--border-radius);
  font-weight: 500;
  transition: var(--transition);
  border: none;
  cursor: pointer;
}

.modal-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
}

/* Column widths for better spacing - 6 columns total */
.col-pr-number { width: 15%; }
.col-date { width: 15%; }
.col-requester { width: 25%; }
.col-status { width: 15%; }
.col-items { width: 10%; }
.col-actions { width: 20%; }

/* Responsive */
@media (max-width: 992px) {
  .purchase-requisition-list {
    padding: 1rem;
  }

  .page-header {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }

  .filters-header-full {
    padding: 1rem 1.25rem;
  }

  .filters-body-full {
    padding: 1.25rem;
  }

  .col-lg-3 {
    flex: 0 0 50%;
    max-width: 50%;
  }
}

@media (max-width: 768px) {
  .table-container {
    overflow-x: auto;
  }

  .pagination-container {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }

  .actions-container {
    justify-content: center;
    flex-wrap: wrap;
  }

  .filters-header-full {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
    padding: 1rem;
  }

  .filters-body-full {
    padding: 1rem;
  }

  .col-lg-3,
  .col-md-6 {
    flex: 0 0 100%;
    max-width: 100%;
    margin-bottom: 1rem;
  }

  .col-12,
  .col-lg-3,
  .col-md-6 {
    padding-left: 0;
    padding-right: 0;
  }

  .col-pr-number,
  .col-date,
  .col-requester,
  .col-status,
  .col-items,
  .col-actions {
    width: auto;
    min-width: 120px;
  }
}

@media (max-width: 576px) {
  .purchase-requisition-list {
    padding: 0.5rem;
  }

  .filters-header-full {
    padding: 0.75rem;
  }

  .filters-body-full {
    padding: 0.75rem;
  }

  .filter-group {
    margin-bottom: 1rem;
  }

  .form-control-custom {
    font-size: 16px; /* Prevent zoom on iOS */
  }
}
</style>
