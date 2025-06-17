<!-- src/views/sales/SalesInvoiceList.vue -->
<template>
  <div class="page-container">
    <!-- Search and Filter Section -->
    <div class="filters-row">
      <!-- Search Box -->
      <div class="filter-item search-box">
        <div class="search-icon-wrapper">
          <i class="fas fa-search search-icon"></i>
        </div>
        <input
          type="text"
          v-model="searchTerm"
          placeholder="Search invoice number or customer..."
          @input="handleSearch"
          class="search-input"
        />
        <button v-if="searchTerm" @click="clearSearch" class="clear-search">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <!-- Status Filter -->
      <div class="filter-item">
        <select v-model="filters.status" @change="fetchInvoices" placeholder="Status">
          <option value="">All Status</option>
          <option value="Draft">Draft</option>
          <option value="Sent">Sent</option>
          <option value="Partially Paid">Partially Paid</option>
          <option value="Paid">Paid</option>
          <option value="Overdue">Overdue</option>
          <option value="Cancelled">Cancelled</option>
        </select>
      </div>

      <!-- Date Range Filter -->
      <div class="filter-item">
        <select v-model="filters.dateRange" @change="handleDateRangeChange">
          <option value="">All Time</option>
          <option value="today">Today</option>
          <option value="week">This Week</option>
          <option value="month">This Month</option>
          <option value="custom">Custom Range</option>
        </select>
      </div>

      <!-- Custom Date Range -->
      <div class="filter-item" v-if="filters.dateRange === 'custom'">
        <input type="date" v-model="filters.startDate" @change="fetchInvoices" placeholder="From" />
      </div>

      <div class="filter-item" v-if="filters.dateRange === 'custom'">
        <input type="date" v-model="filters.endDate" @change="fetchInvoices" placeholder="To" />
      </div>

      <!-- Create New Invoice button moved here -->
      <div class="filter-item create-invoice-btn">
        <router-link to="/sales/invoices/create" class="btn btn-primary">
          <i class="fas fa-plus"></i> Create New Invoice
        </router-link>
      </div>
    </div>

    <!-- Loading Indicator -->
    <div v-if="loading" class="loading-indicator">
      <i class="fas fa-spinner fa-spin"></i> Loading invoices...
    </div>

    <!-- Empty State -->
    <div v-else-if="invoices.length === 0" class="empty-state">
      <div class="empty-icon">
        <i class="fas fa-file-invoice"></i>
      </div>
      <h3>No Invoices Found</h3>
      <p>There are no invoices matching your criteria. Try changing your filters or create a new invoice.</p>
    </div>

    <!-- Invoices Table -->
    <div v-else class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th @click="sortBy('invoice_number')" class="sortable">
              Invoice Number
              <i v-if="sortKey === 'invoice_number'"
                 :class="sortOrder === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'"></i>
            </th>
            <th @click="sortBy('invoice_date')" class="sortable">
              Invoice Date
              <i v-if="sortKey === 'invoice_date'"
                 :class="sortOrder === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'"></i>
            </th>
            <th @click="sortBy('due_date')" class="sortable">
              Due Date
              <i v-if="sortKey === 'due_date'"
                 :class="sortOrder === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'"></i>
            </th>
            <th>Customer</th>
            <th>Amount</th>
            <th @click="sortBy('status')" class="sortable">
              Status
              <i v-if="sortKey === 'status'"
                 :class="sortOrder === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'"></i>
            </th>
            <th class="actions-column">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="invoice in invoices" :key="invoice.invoice_id">
            <td>{{ invoice.invoice_number }}</td>
            <td>{{ formatDate(invoice.invoice_date) }}</td>
            <td>
              {{ formatDate(invoice.due_date) }}
              <span v-if="isOverdue(invoice.due_date, invoice.status)" class="overdue-badge">Overdue</span>
            </td>
            <td>{{ invoice.customer ? invoice.customer.name : 'N/A' }}</td>
            <td>{{ formatCurrency(invoice.total_amount, invoice.currency_code) }}</td>
            <td>
              <span class="status-badge" :class="getStatusClass(invoice.status)">
                {{ invoice.status }}
              </span>
            </td>
            <td class="actions-cell">
              <div class="action-buttons">
                <router-link :to="`/sales/invoices/${invoice.invoice_id}`" class="btn btn-sm btn-info" title="View">
                  <i class="fas fa-eye"></i>
                </router-link>
                <router-link :to="`/sales/invoices/${invoice.invoice_id}/edit`" class="btn btn-sm btn-warning"
                             v-if="canEdit(invoice.status)" title="Edit">
                  <i class="fas fa-edit"></i>
                </router-link>
                <router-link :to="`/sales/invoices/${invoice.invoice_id}/print`" class="btn btn-sm btn-secondary" title="Print">
                  <i class="fas fa-print"></i>
                </router-link>
                <button @click="confirmDelete(invoice)" class="btn btn-sm btn-danger"
                        v-if="canDelete(invoice.status)" title="Delete">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="pagination-container" v-if="totalPages > 1">
      <div class="pagination">
        <div class="pagination-info">
          Showing {{ (currentPage - 1) * perPage + 1 }} to {{ Math.min(currentPage * perPage, totalItems) }} of {{ totalItems }} invoices
        </div>
        <div class="pagination-controls">
          <button
            class="pagination-btn"
            :disabled="currentPage === 1"
            @click="goToPage(currentPage - 1)"
          >
            <i class="fas fa-chevron-left"></i>
          </button>

          <template v-for="page in displayedPages" :key="page">
            <button
              v-if="page !== '...'"
              class="pagination-btn"
              :class="{ active: page === currentPage }"
              @click="goToPage(page)"
            >
              {{ page }}
            </button>
            <span v-else class="pagination-ellipsis">...</span>
          </template>

          <button
            class="pagination-btn"
            :disabled="currentPage === totalPages"
            @click="goToPage(currentPage + 1)"
          >
            <i class="fas fa-chevron-right"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal" v-if="showDeleteModal">
      <div class="modal-backdrop" @click="showDeleteModal = false"></div>
      <div class="modal-content modal-sm">
        <div class="modal-header">
          <h2>Confirm Delete</h2>
          <button class="close-btn" @click="showDeleteModal = false">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete invoice <strong>{{ selectedInvoice?.invoice_number }}</strong>?</p>
          <p>This action cannot be undone.</p>

          <div class="form-actions">
            <button type="button" class="btn btn-secondary" @click="showDeleteModal = false">
              Cancel
            </button>
            <button
              type="button"
              class="btn btn-danger"
              @click="deleteInvoice"
            >
              Delete
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'SalesInvoiceList',
  data() {
    return {
      invoices: [],
      loading: true,
      currentPage: 1,
      totalPages: 0,
      totalItems: 0,
      perPage: 10,
      searchTerm: '',
      searchDebounce: null,
      sortKey: 'invoice_id',
      sortOrder: 'desc',
      filters: {
        status: '',
        dateRange: '',
        startDate: '',
        endDate: ''
      },
      showDeleteModal: false,
      selectedInvoice: null
    };
  },
  computed: {
    displayedPages() {
      const pages = [];
      const total = this.totalPages;
      const current = this.currentPage;

      if (total <= 7) {
        // Show all pages if 7 or fewer
        for (let i = 1; i <= total; i++) {
          pages.push(i);
        }
      } else {
        // Always include first page
        pages.push(1);

        // Show ellipsis if current page is more than 3
        if (current > 3) {
          pages.push('...');
        }

        // Add pages around current page
        const startPage = Math.max(2, current - 1);
        const endPage = Math.min(total - 1, current + 1);

        for (let i = startPage; i <= endPage; i++) {
          pages.push(i);
        }

        // Show ellipsis if current page is less than total - 2
        if (current < total - 2) {
          pages.push('...');
        }

        // Always include last page
        if (total > 1) {
          pages.push(total);
        }
      }

      return pages;
    }
  },
  mounted() {
    this.fetchInvoices();
  },
  methods: {
    async fetchInvoices() {
      this.loading = true;
      try {
        // Build query parameters
        const params = {
          page: this.currentPage,
          per_page: this.perPage,
          sort_field: this.sortKey,
          sort_direction: this.sortOrder
        };

        // Add filters if set
        if (this.searchTerm) {
          params.search = this.searchTerm;
        }

        if (this.filters.status) {
          params.status = this.filters.status;
        }

        if (this.filters.dateRange && this.filters.dateRange !== 'custom') {
          params.date_range = this.filters.dateRange;
        } else if (this.filters.startDate && this.filters.endDate) {
          params.start_date = this.filters.startDate;
          params.end_date = this.filters.endDate;
        }

        const response = await axios.get('/invoices', { params });

        this.invoices = response.data.data;
        this.currentPage = response.data.current_page;
        this.totalPages = response.data.last_page;
        this.totalItems = response.data.total;
      } catch (error) {
        console.error('Error fetching invoices:', error);
        this.$toast.error('Failed to load invoices. Please try again later.');
      } finally {
        this.loading = false;
      }
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
    formatCurrency(amount, currencyCode = 'IDR') {
      if (amount === undefined || amount === null) return 'N/A';
      return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: currencyCode || 'IDR',
        minimumFractionDigits: 4,
        maximumFractionDigits: 4
      }).format(amount);
    },
    getStatusClass(status) {
      switch (status) {
        case 'Draft':
          return 'status-draft';
        case 'Sent':
          return 'status-sent';
        case 'Partially Paid':
          return 'status-partial';
        case 'Paid':
          return 'status-paid';
        case 'Overdue':
          return 'status-overdue';
        case 'Cancelled':
          return 'status-cancelled';
        default:
          return '';
      }
    },
    isOverdue(dueDate, status) {
      if (!dueDate || status === 'Paid' || status === 'Cancelled') return false;
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const due = new Date(dueDate);
      return due < today;
    },
    canEdit(status) {
      // Can edit if not Paid or Cancelled
      return !['Paid', 'Cancelled'].includes(status);
    },
    canDelete(status) {
      // Can delete if not Paid and doesn't have related returns
      return !['Paid'].includes(status);
    },
    sortBy(key) {
      if (this.sortKey === key) {
        // Toggle sort order if clicking on the same column
        this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
      } else {
        // Set new sort key and default to ascending
        this.sortKey = key;
        this.sortOrder = 'asc';
      }
      this.fetchInvoices();
    },
    handleSearch() {
      // Debounce search to avoid too many API calls
      clearTimeout(this.searchDebounce);
      this.searchDebounce = setTimeout(() => {
        this.currentPage = 1; // Reset to first page
        this.fetchInvoices();
      }, 500);
    },
    clearSearch() {
      this.searchTerm = '';
      this.fetchInvoices();
    },
    handleDateRangeChange() {
      // Reset custom date range if a preset is selected
      if (this.filters.dateRange !== 'custom') {
        this.filters.startDate = '';
        this.filters.endDate = '';
      } else {
        // Set default custom range to current month if not already set
        if (!this.filters.startDate) {
          const today = new Date();
          this.filters.startDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
          this.filters.endDate = new Date().toISOString().split('T')[0];
        }
      }
      this.fetchInvoices();
    },
    goToPage(page) {
      if (page !== this.currentPage && page >= 1 && page <= this.totalPages) {
        this.currentPage = page;
        this.fetchInvoices();
      }
    },
    confirmDelete(invoice) {
      this.selectedInvoice = invoice;
      this.showDeleteModal = true;
    },
    async deleteInvoice() {
      if (!this.selectedInvoice) return;

      try {
        await axios.delete(`/invoices/${this.selectedInvoice.invoice_id}`);
        this.$toast.success(`Invoice ${this.selectedInvoice.invoice_number} deleted successfully`);
        this.showDeleteModal = false;
        this.fetchInvoices();
      } catch (error) {
        console.error('Error deleting invoice:', error);
        if (error.response && error.response.data.message) {
          this.$toast.error(error.response.data.message);
        } else {
          this.$toast.error('Failed to delete invoice. Please try again later.');
        }
      }
    }
  }
};
</script>

<style scoped>
.page-container {
  padding: 1.5rem;
}

/* Filters row styling - updated to align all items */
.filters-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
  align-items: center;
}

.filter-item {
  flex: 0 0 auto;
}

/* Make the create-invoice-btn push to the right */
.create-invoice-btn {
  margin-left: auto;
}

/* Search box styling */
.search-box {
  position: relative;
  flex: 1;
  min-width: 200px;
  display: flex;
  align-items: center;
  border: 1px solid var(--gray-200);
  border-radius: 0.375rem;
  background-color: white;
}

.search-icon-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  padding-left: 0.75rem;
  padding-right: 0.5rem;
}

.search-icon {
  color: var(--gray-400);
  width: 1rem;
  height: 1rem;
}

.search-input {
  flex: 1;
  padding: 0.625rem 0.5rem;
  border: none;
  outline: none;
  font-size: 0.875rem;
  background: transparent;
}

.clear-search {
  padding: 0 0.75rem;
  background: none;
  border: none;
  color: var(--gray-400);
  cursor: pointer;
  display: flex;
  align-items: center;
  height: 100%;
}

/* Select and input styling */
.filter-item select,
.filter-item input {
  padding: 0.625rem 1rem;
  border: 1px solid var(--gray-200);
  border-radius: 0.375rem;
  font-size: 0.875rem;
  background-color: white;
  min-width: 150px;
}

.filter-item select {
  padding-right: 2rem;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
  background-position: right 0.5rem center;
  background-repeat: no-repeat;
  background-size: 1.25rem;
  appearance: none;
}

/* Other styles remain the same */
.table-responsive {
  overflow-x: auto;
  margin-bottom: 1rem;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.data-table th {
  text-align: left;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--gray-200);
  background-color: var(--gray-50);
  font-weight: 500;
  color: var(--gray-600);
}

.data-table td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--gray-100);
  color: var(--gray-800);
  vertical-align: middle;
}

.data-table tr:hover td {
  background-color: var(--gray-50);
}

.sortable {
  cursor: pointer;
  user-select: none;
}

.sortable:hover {
  background-color: var(--gray-100);
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
  text-align: center;
  white-space: nowrap;
}

.status-draft {
  background-color: var(--gray-100);
  color: var(--gray-700);
}

.status-sent {
  background-color: #dbeafe;
  color: #1e40af;
}

.status-partial {
  background-color: #fef3c7;
  color: #92400e;
}

.status-paid {
  background-color: #d1fae5;
  color: #065f46;
}

.status-overdue {
  background-color: #fee2e2;
  color: #b91c1c;
}

.status-cancelled {
  background-color: var(--gray-200);
  color: var(--gray-700);
}

.overdue-badge {
  display: inline-block;
  margin-left: 0.5rem;
  padding: 0.125rem 0.375rem;
  background-color: #fee2e2;
  color: #b91c1c;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.pagination-container {
  margin-top: 1.5rem;
}

.pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.pagination-info {
  color: var(--gray-600);
  font-size: 0.875rem;
}

.pagination-controls {
  display: flex;
  gap: 0.25rem;
}

.pagination-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 2rem;
  height: 2rem;
  padding: 0 0.5rem;
  border: 1px solid var(--gray-200);
  background-color: white;
  color: var(--gray-700);
  border-radius: 0.375rem;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s;
}

.pagination-btn:hover:not(:disabled) {
  background-color: var(--gray-50);
  border-color: var(--gray-300);
}

.pagination-btn.active {
  background-color: var(--primary-color);
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
  min-width: 2rem;
  height: 2rem;
  color: var(--gray-400);
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 0;
  text-align: center;
}

.empty-icon {
  font-size: 2.5rem;
  color: var(--gray-300);
  margin-bottom: 1rem;
}

.empty-state h3 {
  font-size: 1.125rem;
  color: var(--gray-700);
  margin-bottom: 0.5rem;
}

.empty-state p {
  color: var(--gray-500);
  max-width: 24rem;
}

.loading-indicator {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 3rem 0;
  color: var(--gray-500);
  font-size: 0.875rem;
}

.loading-indicator i {
  margin-right: 0.5rem;
}

.actions-column {
  width: 1%;
  white-space: nowrap;
}

.actions-cell {
  text-align: right;
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 50;
  display: flex;
  justify-content: center;
  align-items: center;
}

.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 50;
}

.modal-content {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  width: 100%;
  z-index: 60;
  overflow: hidden;
}

.modal-sm {
  max-width: 400px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h2 {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0;
  color: #1e293b;
}

.close-btn {
  background: none;
  border: none;
  color: #64748b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.25rem;
  border-radius: 0.25rem;
}

.close-btn:hover {
  background-color: #f1f5f9;
  color: #0f172a;
}

.modal-body {
  padding: 1.5rem;
}

.modal-body p {
  margin-top: 0;
  margin-bottom: 1.5rem;
  color: #1e293b;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

/* Button styles */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 0.375rem;
  padding: 0.625rem 1rem;
  font-weight: 500;
  text-decoration: none;
  transition: background-color 0.2s, border-color 0.2s, color 0.2s;
  cursor: pointer;
  border: 1px solid transparent;
}

.btn-sm {
  padding: 0.375rem 0.625rem;
  font-size: 0.75rem;
}

.btn-primary {
  background-color: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.btn-primary:hover {
  background-color: var(--primary-dark);
  border-color: var(--primary-dark);
}

.btn-secondary {
  background-color: var(--gray-100);
  color: var(--gray-700);
  border-color: var(--gray-200);
}

.btn-secondary:hover {
  background-color: var(--gray-200);
  color: var(--gray-800);
}

.btn-info {
  background-color: #dbeafe;
  color: #1e40af;
  border-color: #bfdbfe;
}

.btn-info:hover {
  background-color: #bfdbfe;
  color: #1e3a8a;
}

.btn-warning {
  background-color: #fef3c7;
  color: #92400e;
  border-color: #fde68a;
}

.btn-warning:hover {
  background-color: #fde68a;
  color: #78350f;
}

.btn-danger {
  background-color: #fee2e2;
  color: #b91c1c;
  border-color: #fecaca;
}

.btn-danger:hover {
  background-color: #fecaca;
  color: #991b1b;
}

.btn i {
  margin-right: 0.5rem;
}

.btn-sm i {
  margin-right: 0.25rem;
}

/* For mobile responsiveness */
@media (max-width: 768px) {
  .filters-row {
    flex-direction: column;
    gap: 0.75rem;
  }

  .filter-item {
    width: 100%;
  }

  /* On mobile, the create button shouldn't have margin */
  .create-invoice-btn {
    margin-left: 0;
    width: 100%;
  }

  /* Make the button take full width on mobile */
  .create-invoice-btn .btn {
    width: 100%;
    justify-content: center;
  }

  .pagination {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
