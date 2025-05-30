<!-- src/views/purchasing/PurchaseOrderList.vue -->
<template>
  <div class="po-container">
    <!-- Header Section -->
    <div class="po-header">
      <h1 class="po-title">Purchase Orders</h1>
      <div class="po-actions">
        <router-link to="/purchasing/orders/create" class="btn-create">
          <i class="fas fa-plus"></i> Create Purchase Order
        </router-link>
      </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-panel">
      <div class="filter-body">
        <div class="filter-row">
          <!-- Status Filter -->
          <div class="filter-col">
            <label class="filter-label">Status</label>
            <select v-model="filters.status" class="filter-input" @change="loadPurchaseOrders()">
              <option value="">All Statuses</option>
              <option value="draft">Draft</option>
              <option value="submitted">Submitted</option>
              <option value="approved">Approved</option>
              <option value="sent">Sent</option>
              <option value="partial">Partial</option>
              <option value="received">Received</option>
              <option value="completed">Completed</option>
              <option value="canceled">Canceled</option>
            </select>
          </div>

          <!-- Vendor Filter -->
          <div class="filter-col">
            <label class="filter-label">Vendor</label>
            <select v-model="filters.vendor_id" class="filter-input" @change="loadPurchaseOrders()">
              <option value="">All Vendors</option>
              <option v-for="vendor in filteredVendors" :key="vendor.vendor_id" :value="vendor.vendor_id">
                {{ vendor.name }}
              </option>
            </select>
          </div>

          <!-- Date From Filter -->
          <div class="filter-col">
            <label class="filter-label">Date From</label>
            <input
              type="date"
              v-model="filters.date_from"
              class="filter-input"
              @change="loadPurchaseOrders()"
            >
          </div>

          <!-- Date To Filter -->
          <div class="filter-col">
            <label class="filter-label">Date To</label>
            <input
              type="date"
              v-model="filters.date_to"
              class="filter-input"
              @change="loadPurchaseOrders()"
            >
          </div>

          <!-- Search Filter -->
          <div class="filter-col filter-search">
            <label class="filter-label">Search</label>
            <div class="search-wrapper">
              <input
                type="text"
                v-model="filters.search"
                class="filter-input"
                placeholder="Search by PO number"
                @keyup.enter="loadPurchaseOrders()"
              >
              <button class="search-btn" @click="loadPurchaseOrders()">
                <i class="fas fa-search"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Second Row for Outstanding Filter and Reset -->
        <div class="filter-options">
          <div class="outstanding-filter">
            <input
              type="checkbox"
              id="outstandingOnly"
              v-model="filters.has_outstanding"
              @change="loadPurchaseOrders()"
            >
            <label for="outstandingOnly">Outstanding Only</label>
          </div>

          <button class="reset-btn" @click="resetFilters()">
            <i class="fas fa-redo"></i> Reset Filters
          </button>
        </div>
      </div>
    </div>

    <!-- Data Table Section -->
    <div class="po-data">
      <!-- Loading State -->
      <div v-if="isLoading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading purchase orders...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="purchaseOrders.length === 0" class="empty-state">
        <i class="fas fa-clipboard-list"></i>
        <h3>No Purchase Orders Found</h3>
        <p>Try adjusting your filters or create a new purchase order.</p>
      </div>

      <!-- Data Table -->
      <div v-else class="data-table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th @click="updateSort('po_number')" class="sortable-header">
                PO Number
                <i v-if="sortField === 'po_number'"
                  :class="sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'"></i>
              </th>
              <th @click="updateSort('po_date')" class="sortable-header">
                Date
                <i v-if="sortField === 'po_date'"
                  :class="sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'"></i>
              </th>
              <th>Vendor</th>
              <th @click="updateSort('expected_delivery')" class="sortable-header">
                Expected Delivery
                <i v-if="sortField === 'expected_delivery'"
                  :class="sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'"></i>
              </th>
              <th @click="updateSort('total_amount')" class="sortable-header">
                Amount
                <i v-if="sortField === 'total_amount'"
                  :class="sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'"></i>
              </th>
              <th>Currency</th>
              <th @click="updateSort('status')" class="sortable-header">
                Status
                <i v-if="sortField === 'status'"
                  :class="sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'"></i>
              </th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="po in purchaseOrders" :key="po.po_id" class="data-row">
              <td>{{ po.po_number }}</td>
              <td>{{ formatDate(po.po_date) }}</td>
              <td>{{ po.vendor ? po.vendor.name : '-' }}</td>
              <td>{{ formatDate(po.expected_delivery) }}</td>
              <td>{{ formatCurrency(po.total_amount) }}</td>
              <td>{{ po.currency_code || 'USD' }}</td>
              <td>
                <span :class="['status-badge', getStatusClass(po.status)]">
                  {{ po.status }}
                </span>
              </td>
              <td>
                <div class="action-buttons">
                  <router-link :to="`/purchasing/orders/${po.po_id}`" class="action-btn view-btn" title="View">
                    <i class="fas fa-eye"></i>
                  </router-link>
                  <router-link v-if="po.status === 'draft'"
                    :to="`/purchasing/orders/${po.po_id}/edit`"
                    class="action-btn edit-btn" title="Edit">
                    <i class="fas fa-edit"></i>
                  </router-link>
                  <router-link :to="`/purchasing/orders/${po.po_id}/track`"
                    class="action-btn track-btn" title="Track">
                    <i class="fas fa-chart-line"></i>
                  </router-link>
                  <button v-if="po.status === 'draft'"
                    @click="confirmDelete(po)"
                    class="action-btn delete-btn" title="Delete">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination-container">
          <div class="pagination-info">
            Showing {{ paginationInfo.from }} to {{ paginationInfo.to }} of {{ paginationInfo.total }} items
          </div>
          <div class="pagination-controls">
            <button
              class="page-btn prev-btn"
              :class="{ disabled: currentPage === 1 }"
              @click="goToPage(currentPage - 1)">
              <i class="fas fa-chevron-left"></i> Previous
            </button>

            <button
              v-for="page in paginationPages"
              :key="page"
              class="page-btn number-btn"
              :class="{ active: currentPage === page }"
              @click="goToPage(page)">
              {{ page }}
            </button>

            <button
              class="page-btn next-btn"
              :class="{ disabled: currentPage === paginationInfo.last_page }"
              @click="goToPage(currentPage + 1)">
              Next <i class="fas fa-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" v-if="showDeleteModal">
      <div class="modal-container">
        <div class="modal-header">
          <h5 class="modal-title">Confirm Delete</h5>
          <button class="modal-close" @click="showDeleteModal = false">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete purchase order <strong>{{ poToDelete?.po_number }}</strong>?</p>
          <p class="warning-text">This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
          <button class="btn-cancel" @click="showDeleteModal = false">Cancel</button>
          <button class="btn-delete" @click="deletePurchaseOrder()">Delete</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'PurchaseOrderList',
  data() {
    return {
      purchaseOrders: [],
      vendors: [],
      isLoading: false,
      filters: {
        status: '',
        vendor_id: '',
        date_from: '',
        date_to: '',
        search: '',
        has_outstanding: false
      },
      sortField: 'po_date',
      sortDirection: 'desc',
      currentPage: 1,
      paginationInfo: {
        total: 0,
        per_page: 15,
        current_page: 1,
        last_page: 1,
        from: 0,
        to: 0
      },
      showDeleteModal: false,
      poToDelete: null
    };
  },
  computed: {
    paginationPages() {
      const pages = [];
      let startPage = Math.max(1, this.currentPage - 2);
      let endPage = Math.min(this.paginationInfo.last_page, startPage + 4);

      if (endPage - startPage < 4) {
        startPage = Math.max(1, endPage - 4);
      }

      for (let i = startPage; i <= endPage; i++) {
        pages.push(i);
      }

      return pages;
    },
    filteredVendors() {
      return this.vendors.filter(vendor => vendor);
    }
  },
  created() {
    this.loadVendors();
    this.loadPurchaseOrders();
  },
  methods: {
    async loadVendors() {
      try {
        const response = await axios.get('/vendors');
        // Adjusted to handle paginated response data structure correctly
        if (Array.isArray(response.data)) {
          this.vendors = response.data.filter(vendor => vendor !== null && vendor !== undefined);
        } else if (response.data && response.data.data) {
          if (Array.isArray(response.data.data)) {
            this.vendors = response.data.data.filter(vendor => vendor !== null && vendor !== undefined);
          } else if (Array.isArray(response.data.data.data)) {
            this.vendors = response.data.data.data.filter(vendor => vendor !== null && vendor !== undefined);
          } else {
            this.vendors = [];
            console.error('Unexpected vendors data format:', response.data);
          }
        } else {
          this.vendors = [];
          console.error('Unexpected vendors data format:', response.data);
        }
      } catch (error) {
        console.error('Error loading vendors:', error);
      }
    },
    async loadPurchaseOrders() {
      this.isLoading = true;
      try {
        const params = {
          page: this.currentPage,
          per_page: this.paginationInfo.per_page,
          sort_field: this.sortField,
          sort_direction: this.sortDirection,
          ...this.filters
        };

        const response = await axios.get('/purchase-orders', { params });

        if (response.data.status === 'success') {
          this.purchaseOrders = response.data.data.data;
          this.paginationInfo = {
            total: response.data.data.total,
            per_page: response.data.data.per_page,
            current_page: response.data.data.current_page,
            last_page: response.data.data.last_page,
            from: response.data.data.from,
            to: response.data.data.to
          };
        }
      } catch (error) {
        console.error('Error loading purchase orders:', error);
      } finally {
        this.isLoading = false;
      }
    },
    resetFilters() {
      this.filters = {
        status: '',
        vendor_id: '',
        date_from: '',
        date_to: '',
        search: '',
        has_outstanding: false
      };
      this.loadPurchaseOrders();
    },
    updateSort(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortField = field;
        this.sortDirection = 'asc';
      }
      this.loadPurchaseOrders();
    },
    goToPage(page) {
      if (page < 1 || page > this.paginationInfo.last_page) {
        return;
      }
      this.currentPage = page;
      this.loadPurchaseOrders();
    },
    confirmDelete(po) {
      this.poToDelete = po;
      this.showDeleteModal = true;
    },
    async deletePurchaseOrder() {
      if (!this.poToDelete) return;

      try {
        const response = await axios.delete(`/purchase-orders/${this.poToDelete.po_id}`);

        if (response.data.status === 'success') {
          // Show success notification
          alert('Purchase order deleted successfully');

          // Refresh the list
          this.loadPurchaseOrders();
        }
      } catch (error) {
        console.error('Error deleting purchase order:', error);

        // Show error notification
        if (error.response && error.response.data && error.response.data.message) {
          alert(`Error: ${error.response.data.message}`);
        } else {
          alert('An error occurred while deleting the purchase order');
        }
      } finally {
        this.showDeleteModal = false;
        this.poToDelete = null;
      }
    },
    formatDate(dateString) {
      if (!dateString) return '-';
      const date = new Date(dateString);
      return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    },
    formatCurrency(amount) {
      if (amount === null || amount === undefined) return '-';
      return new Intl.NumberFormat('id-ID', {
        style: 'decimal',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(amount);
    },
    getStatusClass(status) {
      const statusClasses = {
        'draft': 'status-draft',
        'submitted': 'status-submitted',
        'approved': 'status-approved',
        'sent': 'status-sent',
        'partial': 'status-partial',
        'received': 'status-received',
        'completed': 'status-completed',
        'canceled': 'status-canceled'
      };

      return statusClasses[status] || 'status-default';
    }
  }
};
</script>

<style scoped>
/* Main Container */
.po-container {
  background-color: #f8f9fa;
  border-radius: 8px;
  padding: 24px;
  max-width: 100%;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  margin-bottom: 30px;
}

/* Header Section */
.po-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 1px solid #e9ecef;
}

.po-title {
  font-size: 24px;
  font-weight: 600;
  color: #212529;
  margin: 0;
}

.btn-create {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background-color: #007bff;
  color: white;
  border: none;
  border-radius: 6px;
  padding: 12px 20px;
  font-size: 16px;
  font-weight: 500;
  text-decoration: none;
  transition: background-color 0.2s;
}

.btn-create:hover {
  background-color: #0069d9;
  text-decoration: none;
  color: white;
}

/* Filter Panel */
.filter-panel {
  background-color: white;
  border-radius: 8px;
  margin-bottom: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.filter-body {
  padding: 20px;
}

.filter-row {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 20px;
}

.filter-col {
  flex: 1;
  min-width: 180px;
}

.filter-search {
  flex: 1.5;
  min-width: 250px;
}

.filter-label {
  display: block;
  font-weight: 500;
  margin-bottom: 8px;
  color: #495057;
  font-size: 14px;
}

.filter-input {
  width: 100%;
  height: 48px;
  padding: 8px 16px;
  border: 1px solid #ced4da;
  border-radius: 6px;
  font-size: 16px;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.filter-input:focus {
  border-color: #80bdff;
  outline: 0;
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
}

.search-wrapper {
  position: relative;
  display: flex;
}

.search-wrapper .filter-input {
  flex: 1;
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
  border-right: none;
}

.search-btn {
  width: 48px;
  background-color: #007bff;
  border: none;
  border-top-right-radius: 6px;
  border-bottom-right-radius: 6px;
  color: white;
  cursor: pointer;
  transition: background-color 0.2s;
}

.search-btn:hover {
  background-color: #0069d9;
}

.filter-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.outstanding-filter {
  display: flex;
  align-items: center;
  gap: 8px;
}

.outstanding-filter input[type="checkbox"] {
  width: 18px;
  height: 18px;
}

.outstanding-filter label {
  font-size: 16px;
  color: #495057;
  margin: 0;
  cursor: pointer;
}

.reset-btn {
  background-color: #6c757d;
  color: white;
  border: none;
  border-radius: 6px;
  padding: 10px 20px;
  font-size: 16px;
  cursor: pointer;
  transition: background-color 0.2s;
  display: flex;
  align-items: center;
  gap: 8px;
}

.reset-btn:hover {
  background-color: #5a6268;
}

/* Loading State */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 0;
}

.spinner {
  border: 4px solid rgba(0, 0, 0, 0.1);
  border-radius: 50%;
  border-top: 4px solid #007bff;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin-bottom: 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.loading-state p {
  color: #6c757d;
  font-size: 16px;
}

/* Empty State */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 0;
  color: #6c757d;
  text-align: center;
}

.empty-state i {
  font-size: 64px;
  color: #dee2e6;
  margin-bottom: 24px;
}

.empty-state h3 {
  font-size: 20px;
  margin-bottom: 12px;
  color: #343a40;
}

.empty-state p {
  font-size: 16px;
  max-width: 400px;
}

/* Data Table */
.data-table-container {
  background-color: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.data-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
}

.data-table thead {
  background-color: #f8f9fa;
}

.data-table th {
  padding: 16px;
  font-weight: 600;
  color: #495057;
  text-align: left;
  border-bottom: 2px solid #dee2e6;
}

.sortable-header {
  cursor: pointer;
  user-select: none;
  position: relative;
}

.sortable-header i {
  margin-left: 6px;
}

.data-row {
  transition: background-color 0.2s;
}

.data-row:hover {
  background-color: #f8f9fa;
}

.data-table td {
  padding: 16px;
  border-bottom: 1px solid #e9ecef;
  color: #212529;
}

/* Status Badges */
.status-badge {
  display: inline-block;
  padding: 6px 12px;
  border-radius: 50px;
  font-size: 14px;
  font-weight: 500;
  text-transform: capitalize;
  text-align: center;
}

.status-draft {
  background-color: #e9ecef;
  color: #495057;
}

.status-submitted {
  background-color: #cff4fc;
  color: #055160;
}

.status-approved {
  background-color: #cfe2ff;
  color: #084298;
}

.status-sent {
  background-color: #fff3cd;
  color: #664d03;
}

.status-partial {
  background-color: #d1e7dd;
  color: #0a3622;
}

.status-received, .status-completed {
  background-color: #d1e7dd;
  color: #0f5132;
}

.status-canceled {
  background-color: #f8d7da;
  color: #842029;
}

.status-default {
  background-color: #e9ecef;
  color: #212529;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 8px;
}

.action-btn {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  border: none;
  background-color: #f8f9fa;
  color: #495057;
  cursor: pointer;
  transition: all 0.2s;
}

.view-btn {
  background-color: #e9ecef;
  color: #0d6efd;
}

.view-btn:hover {
  background-color: #0d6efd;
  color: white;
}

.edit-btn {
  background-color: #e9ecef;
  color: #198754;
}

.edit-btn:hover {
  background-color: #198754;
  color: white;
}

.track-btn {
  background-color: #e9ecef;
  color: #6c757d;
}

.track-btn:hover {
  background-color: #6c757d;
  color: white;
}

.delete-btn {
  background-color: #e9ecef;
  color: #dc3545;
}

.delete-btn:hover {
  background-color: #dc3545;
  color: white;
}

/* Pagination */
.pagination-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  border-top: 1px solid #e9ecef;
}

.pagination-info {
  color: #6c757d;
  font-size: 14px;
}

.pagination-controls {
  display: flex;
  gap: 8px;
  align-items: center;
}

.page-btn {
  height: 38px;
  padding: 0 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: white;
  border: 1px solid #dee2e6;
  border-radius: 6px;
  color: #495057;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
}

.prev-btn, .next-btn {
  gap: 8px;
  min-width: 100px;
}

.page-btn:hover:not(.disabled):not(.active) {
  background-color: #e9ecef;
  border-color: #dee2e6;
  color: #495057;
}

.page-btn.active {
  background-color: #007bff;
  border-color: #007bff;
  color: white;
  font-weight: 600;
}

.page-btn.disabled {
  background-color: #f8f9fa;
  border-color: #dee2e6;
  color: #6c757d;
  cursor: not-allowed;
  opacity: 0.6;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-container {
  background-color: white;
  border-radius: 8px;
  width: 100%;
  max-width: 500px;
  overflow: hidden;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid #e9ecef;
}

.modal-title {
  font-size: 18px;
  font-weight: 600;
  color: #212529;
  margin: 0;
}

.modal-close {
  background: none;
  border: none;
  font-size: 16px;
  color: #6c757d;
  cursor: pointer;
}

.modal-body {
  padding: 20px;
}

.modal-body p {
  margin-top: 0;
  color: #212529;
}

.warning-text {
  color: #dc3545;
  font-weight: 500;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 16px 20px;
  border-top: 1px solid #e9ecef;
}

.btn-cancel {
  background-color: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 6px;
  padding: 10px 20px;
  color: #212529;
  font-size: 16px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-cancel:hover {
  background-color: #e9ecef;
}

.btn-delete {
  background-color: #dc3545;
  border: none;
  border-radius: 6px;
  padding: 10px 20px;
  color: white;
  font-size: 16px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-delete:hover {
  background-color: #bb2d3b;
}

/* Responsive Adjustments */
@media (max-width: 992px) {
  .po-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }

  .filter-col {
    min-width: 100%;
  }

  .filter-options {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }

  .reset-btn {
    width: 100%;
    justify-content: center;
  }

  .pagination-container {
    flex-direction: column;
    gap: 16px;
  }

  .pagination-controls {
    width: 100%;
    justify-content: center;
    flex-wrap: wrap;
  }
}
</style>
