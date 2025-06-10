<!-- src/views/manufacturing/ProductionOrderList.vue -->
<template>
    <div class="production-order-list">
      <div class="page-header">
        <h1>Job Process</h1>
        <div class="actions">
          <router-link to="/manufacturing/production-orders/create" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create Job Process
          </router-link>
        </div>
      </div>

      <!-- Improved Filters Section -->
      <div class="filters-section">
        <div class="filters-card">
          <!-- Search Bar -->
          <div class="search-container">
            <div class="search-input-wrapper">
              <i class="fas fa-search search-icon"></i>
              <input
                type="text"
                class="search-input"
                placeholder="Search Job Process by number, Job order, or product..."
                v-model="searchQuery"
                @input="debounceSearch"
              >
              <button
                v-if="searchQuery"
                @click="clearSearch"
                class="clear-search-btn"
                type="button"
              >
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>

          <!-- Filters Row -->
          <div class="filters-row">
            <div class="filter-item">
              <label for="status-filter" class="filter-label">
                <i class="fas fa-flag"></i>
                Status
              </label>
              <select
                id="status-filter"
                v-model="statusFilter"
                @change="fetchProductionOrders"
                class="filter-select"
              >
                <option value="">All Statuses</option>
                <option value="Draft">Draft</option>
                <option value="In Progress">In Progress</option>
                <option value="Completed">Completed</option>
                <option value="Cancelled">Cancelled</option>
              </select>
            </div>

            <div class="filter-item date-filter">
              <label class="filter-label">
                <i class="fas fa-calendar-alt"></i>
                Date Range
              </label>
              <div class="date-range-wrapper">
                <div class="date-input-group">
                  <input
                    type="date"
                    v-model="startDate"
                    @change="fetchProductionOrders"
                    class="date-input"
                    placeholder="Start Date"
                  >
                  <span class="date-separator">to</span>
                  <input
                    type="date"
                    v-model="endDate"
                    @change="fetchProductionOrders"
                    class="date-input"
                    placeholder="End Date"
                  >
                </div>
              </div>
            </div>

            <!-- Filter Actions -->
            <div class="filter-actions">
              <button
                @click="resetFilters"
                class="btn btn-outline-secondary btn-sm"
                :disabled="!hasActiveFilters"
              >
                <i class="fas fa-undo"></i>
                Reset
              </button>
              <button
                @click="fetchProductionOrders"
                class="btn btn-primary btn-sm"
              >
                <i class="fas fa-search"></i>
                Apply
              </button>
            </div>
          </div>

          <!-- Active Filters Display -->
          <div v-if="hasActiveFilters" class="active-filters">
            <span class="active-filters-label">Active Filters:</span>
            <div class="filter-tags">
              <span v-if="searchQuery" class="filter-tag">
                Search: "{{ searchQuery }}"
                <button @click="searchQuery = ''; fetchProductionOrders()" class="remove-filter">
                  <i class="fas fa-times"></i>
                </button>
              </span>
              <span v-if="statusFilter" class="filter-tag">
                Status: {{ statusFilter }}
                <button @click="statusFilter = ''; fetchProductionOrders()" class="remove-filter">
                  <i class="fas fa-times"></i>
                </button>
              </span>
              <span v-if="startDate || endDate" class="filter-tag">
                Date: {{ formatDateRange() }}
                <button @click="clearDateRange()" class="remove-filter">
                  <i class="fas fa-times"></i>
                </button>
              </span>
            </div>
          </div>
        </div>
      </div>

      <div v-if="loading" class="loading-container">
        <i class="fas fa-spinner fa-spin"></i>
        <span>Loading Job Process...</span>
      </div>

      <div v-else-if="productionOrders.length === 0" class="empty-state">
        <i class="fas fa-clipboard-list"></i>
        <h3>No Job Process Found</h3>
        <p>No Job Process match your search criteria or no Job Process have been created yet.</p>
        <router-link to="/manufacturing/production-orders/create" class="btn btn-primary">
          Create Job Process
        </router-link>
      </div>

      <div v-else class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th @click="sortBy('production_number')" class="sortable">
                Production #
                <i v-if="sortKey === 'production_number'"
                  :class="sortOrder === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'"></i>
              </th>
              <th @click="sortBy('production_date')" class="sortable">
                Date
                <i v-if="sortKey === 'production_date'"
                  :class="sortOrder === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'"></i>
              </th>
              <th>Work Order</th>
              <th>Product</th>
              <th @click="sortBy('planned_quantity')" class="sortable">
                Planned Qty
                <i v-if="sortKey === 'planned_quantity'"
                  :class="sortOrder === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'"></i>
              </th>
              <th @click="sortBy('actual_quantity')" class="sortable">
                Actual Qty
                <i v-if="sortKey === 'actual_quantity'"
                  :class="sortOrder === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'"></i>
              </th>
              <th @click="sortBy('status')" class="sortable">
                Status
                <i v-if="sortKey === 'status'"
                  :class="sortOrder === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'"></i>
              </th>
              <th>Actions</th>
            </tr>
          </thead>
          <!-- Ubah bagian kolom Product di tabel (sekitar line 180-190) -->
            <tbody>
            <tr v-for="order in sortedProductionOrders" :key="order.production_id">
                <td>{{ order.production_number }}</td>
                <td>{{ formatDate(order.production_date) }}</td>
                <td>{{ order.work_order?.wo_number || 'N/A' }}</td>
                <!-- KOLOM PRODUCT YANG DIUBAH -->
                <td>
                <router-link
                    v-if="order.work_order?.item && order.work_order.item.item_id"
                    :to="`/items/${order.work_order.item.item_id}`"
                    class="item-link"
                    :title="`View details for ${order.work_order.item.name || order.work_order.item.item_code}`"
                >
                    {{ order.work_order.item.name || order.work_order.item.item_code || 'Unknown Product' }}
                </router-link>
                <span v-else class="text-muted">Unknown Product</span>
                <small v-if="order.work_order?.item?.item_code" class="text-muted item-code d-block">
                    {{ order.work_order.item.item_code }}
                </small>
                </td>
                <td>{{ order.planned_quantity }}</td>
                <td>{{ order.actual_quantity }}</td>
                <td>
                <span class="status-badge" :class="getStatusClass(order.status)">
                    {{ order.status }}
                </span>
                </td>
                <td class="actions-cell">
                <router-link :to="`/manufacturing/production-orders/${order.production_id}`"
                            class="btn btn-sm btn-info" title="View">
                    <i class="fas fa-eye"></i>
                </router-link>
                <router-link v-if="order.status === 'Draft'"
                            :to="`/manufacturing/production-orders/${order.production_id}/edit`"
                            class="btn btn-sm btn-primary" title="Edit">
                    <i class="fas fa-edit"></i>
                </router-link>
                <router-link v-if="order.status === 'In Progress'"
                            :to="`/manufacturing/production-orders/${order.production_id}/complete`"
                            class="btn btn-sm btn-success" title="Complete">
                    <i class="fas fa-check"></i>
                </router-link>
                <button v-if="order.status === 'Draft'"
                        @click="confirmDelete(order)"
                        class="btn btn-sm btn-danger" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
                </td>
            </tr>
            </tbody>
        </table>
      </div>

      <div class="pagination-container" v-if="productionOrders.length > 0">
        <PaginationComponent
          :current-page="currentPage"
          :total-pages="totalPages"
          :from="from"
          :to="to"
          :total="total"
          @page-changed="changePage"
        />
      </div>

      <!-- Confirmation Modal -->
      <ConfirmationModal
        v-if="showDeleteModal"
        title="Delete Job Process"
        :message="`Are you sure you want to delete Job Process <strong>${selectedOrder?.production_number}</strong>? This action cannot be undone.`"
        confirm-button-text="Delete"
        confirm-button-class="btn btn-danger"
        @confirm="deleteProductionOrder"
        @close="cancelDelete"
      />
    </div>
  </template>

  <script>
  import axios from 'axios';

  export default {
    name: 'ProductionOrderList',
    data() {
      return {
        productionOrders: [],
        searchQuery: '',
        statusFilter: '',
        startDate: '',
        endDate: '',
        loading: true,
        currentPage: 1,
        totalPages: 1,
        from: 1,
        to: 10,
        total: 0,
        sortKey: 'production_date',
        sortOrder: 'desc',
        showDeleteModal: false,
        selectedOrder: null,
        searchTimeout: null
      };
    },
    computed: {
      sortedProductionOrders() {
        return [...this.productionOrders].sort((a, b) => {
          let modifier = this.sortOrder === 'asc' ? 1 : -1;

          if (this.sortKey === 'production_date') {
            return new Date(a.production_date) > new Date(b.production_date) ? modifier : -modifier;
          } else {
            if (a[this.sortKey] < b[this.sortKey]) return -1 * modifier;
            if (a[this.sortKey] > b[this.sortKey]) return 1 * modifier;
            return 0;
          }
        });
      },
      hasActiveFilters() {
        return this.searchQuery || this.statusFilter || this.startDate || this.endDate;
      }
    },
    created() {
      this.fetchProductionOrders();
    },
    methods: {
      async fetchProductionOrders() {
        this.loading = true;
        try {
          // Build query parameters
          const params = {
            page: this.currentPage,
            search: this.searchQuery,
            status: this.statusFilter,
            start_date: this.startDate,
            end_date: this.endDate,
            sort_by: this.sortKey,
            sort_order: this.sortOrder
          };

          const response = await axios.get('/production-orders', { params });

          // Check if API returns paginated data or just an array
          if (response.data.data && response.data.meta) {
            // Paginated response
            this.productionOrders = response.data.data;
            this.currentPage = response.data.meta.current_page;
            this.totalPages = response.data.meta.last_page;
            this.from = response.data.meta.from || 1;
            this.to = response.data.meta.to || this.productionOrders.length;
            this.total = response.data.meta.total;
          } else if (response.data.data) {
            // Non-paginated response with data property
            this.productionOrders = response.data.data;
            this.calculatePagination();
          } else {
            // Direct array response
            this.productionOrders = response.data;
            this.calculatePagination();
          }
        } catch (error) {
          console.error('Error fetching Job Process:', error);
          this.$toast.error('Failed to load Job Process');
        } finally {
          this.loading = false;
        }
      },

      debounceSearch() {
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
          this.fetchProductionOrders();
        }, 500);
      },

      clearSearch() {
        this.searchQuery = '';
        this.fetchProductionOrders();
      },

      resetFilters() {
        this.searchQuery = '';
        this.statusFilter = '';
        this.startDate = '';
        this.endDate = '';
        this.fetchProductionOrders();
      },

      clearDateRange() {
        this.startDate = '';
        this.endDate = '';
        this.fetchProductionOrders();
      },

      formatDateRange() {
        if (this.startDate && this.endDate) {
          return `${this.formatDate(this.startDate)} - ${this.formatDate(this.endDate)}`;
        } else if (this.startDate) {
          return `From ${this.formatDate(this.startDate)}`;
        } else if (this.endDate) {
          return `Until ${this.formatDate(this.endDate)}`;
        }
        return '';
      },

      calculatePagination() {
        // Simple pagination calculation for non-paginated API
        this.total = this.productionOrders.length;
        this.totalPages = Math.ceil(this.total / 10);
        this.from = (this.currentPage - 1) * 10 + 1;
        this.to = Math.min(this.from + 9, this.total);
      },

      changePage(page) {
        this.currentPage = page;
        this.fetchProductionOrders();
      },

      sortBy(key) {
        if (this.sortKey === key) {
          this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
          this.sortKey = key;
          this.sortOrder = 'asc';
        }
        this.fetchProductionOrders();
      },

      formatDate(date) {
        if (!date) return 'N/A';
        return new Date(date).toLocaleDateString();
      },

      getStatusClass(status) {
        switch (status) {
          case 'Draft': return 'status-draft';
          case 'In Progress': return 'status-in-progress';
          case 'Completed': return 'status-completed';
          case 'Cancelled': return 'status-cancelled';
          default: return '';
        }
      },

      confirmDelete(order) {
        this.selectedOrder = order;
        this.showDeleteModal = true;
      },

      async deleteProductionOrder() {
        try {
          await axios.delete(`/production-orders/${this.selectedOrder.production_id}`);
          this.$toast.success('Production order deleted successfully');
          this.fetchProductionOrders();
        } catch (error) {
          console.error('Error deleting production order:', error);
          this.$toast.error('Failed to delete Job Process');
        } finally {
          this.showDeleteModal = false;
          this.selectedOrder = null;
        }
      },

      cancelDelete() {
        this.showDeleteModal = false;
        this.selectedOrder = null;
      }
    }
  };
  </script>

  <style scoped>
  .production-order-list {
    padding: 1rem;
  }

  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }

  /* Enhanced Filters Section */
  .filters-section {
    margin-bottom: 1.5rem;
  }

  .filters-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  }

  /* Search Container */
  .search-container {
    margin-bottom: 1.25rem;
  }

  .search-input-wrapper {
    position: relative;
    max-width: 500px;
  }

  .search-input {
    width: 100%;
    padding: 0.75rem 2.5rem 0.75rem 2.5rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.9rem;
    transition: all 0.2s ease;
  }

  .search-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  .search-icon {
    position: absolute;
    left: 0.875rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
    font-size: 0.875rem;
  }

  .clear-search-btn {
    position: absolute;
    right: 0.5rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 3px;
    transition: color 0.2s ease;
  }

  .clear-search-btn:hover {
    color: #374151;
    background-color: #f3f4f6;
  }

  /* Filters Row */
  .filters-row {
    display: grid;
    grid-template-columns: 200px 1fr auto;
    gap: 1.5rem;
    align-items: end;
  }

  .filter-item {
    display: flex;
    flex-direction: column;
  }

  .filter-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
  }

  .filter-label i {
    color: #6b7280;
  }

  .filter-select {
    padding: 0.625rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.9rem;
    background-color: white;
    transition: border-color 0.2s ease;
  }

  .filter-select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  /* Date Filter */
  .date-filter {
    min-width: 280px;
  }

  .date-range-wrapper {
    display: flex;
    flex-direction: column;
  }

  .date-input-group {
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  .date-input {
    flex: 1;
    padding: 0.625rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.9rem;
    transition: border-color 0.2s ease;
  }

  .date-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  .date-separator {
    color: #6b7280;
    font-size: 0.875rem;
    font-weight: 500;
  }

  /* Filter Actions */
  .filter-actions {
    display: flex;
    gap: 0.75rem;
  }

  .btn {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid transparent;
  }

  .btn-primary {
    background-color: #3b82f6;
    color: white;
    border-color: #3b82f6;
  }

  .btn-primary:hover {
    background-color: #2563eb;
    border-color: #2563eb;
  }

  .btn-outline-secondary {
    background-color: transparent;
    color: #6b7280;
    border-color: #d1d5db;
  }

  .btn-outline-secondary:hover:not(:disabled) {
    background-color: #f9fafb;
    color: #374151;
  }

  .btn-outline-secondary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.8rem;
  }

  /* Active Filters */
  .active-filters {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
  }

  .active-filters-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin-right: 0.75rem;
  }

  .filter-tags {
    display: inline-flex;
    flex-wrap: wrap;
    gap: 0.5rem;
  }

  .filter-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background-color: #eff6ff;
    color: #1e40af;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.8rem;
    border: 1px solid #bfdbfe;
  }

  .remove-filter {
    background: none;
    border: none;
    color: inherit;
    cursor: pointer;
    padding: 0;
    margin-left: 0.25rem;
    font-size: 0.7rem;
    opacity: 0.7;
    transition: opacity 0.2s ease;
  }

  .remove-filter:hover {
    opacity: 1;
  }

  /* Table Styles */
  .table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1rem;
  }

  .table th,
  .table td {
    padding: 0.75rem;
    border-bottom: 1px solid var(--gray-200);
    text-align: left;
  }

  .table th {
    background-color: var(--gray-50);
    font-weight: 500;
  }

  .table tbody tr:hover {
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

  .actions-cell {
    display: flex;
    gap: 0.5rem;
  }

  .loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    color: var(--gray-500);
  }

  .loading-container i {
    font-size: 2rem;
    margin-bottom: 1rem;
  }

  .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    text-align: center;
  }

  .empty-state i {
    font-size: 3rem;
    color: var(--gray-300);
    margin-bottom: 1rem;
  }

  .pagination-container {
    margin-top: 1.5rem;
  }

  /* Responsive Design */
  @media (max-width: 1024px) {
    .filters-row {
      grid-template-columns: 1fr;
      gap: 1rem;
    }

    .filter-actions {
      justify-content: flex-end;
    }
  }

  @media (max-width: 768px) {
    .page-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 1rem;
    }

    .actions {
      width: 100%;
    }

    .filters-card {
      padding: 1rem;
    }

    .search-input-wrapper {
      max-width: none;
    }

    .date-input-group {
      flex-direction: column;
      align-items: stretch;
    }

    .date-separator {
      align-self: center;
      margin: 0.25rem 0;
    }

    .filter-actions {
      flex-direction: column;
    }

    .filter-tags {
      margin-top: 0.5rem;
    }

    .table-responsive {
      overflow-x: auto;
    }
  }

  @media (max-width: 480px) {
    .filters-card {
      padding: 0.75rem;
    }

    .filter-actions {
      gap: 0.5rem;
    }

    .btn {
      justify-content: center;
    }
  }
  </style>
