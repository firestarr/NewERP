<!-- src/views/inventory/StockTransactionsList.vue -->
<template>
    <div class="stock-transactions-list">
      <div class="page-header">
        <h1>Stock Transactions</h1>
        <div class="page-actions">
          <router-link to="/stock-transactions/create" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Transaction
          </router-link>
          <router-link to="/stock-transactions/transfer" class="btn btn-secondary">
            <i class="fas fa-exchange-alt"></i> Stock Transfer
          </router-link>
          <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" @click="showBulkActions = !showBulkActions">
              <i class="fas fa-cog"></i> Bulk Actions
            </button>
            <div v-if="showBulkActions" class="dropdown-menu show">
              <button
                class="dropdown-item"
                @click="bulkConfirmTransactions"
                :disabled="selectedTransactions.length === 0 || !hasSelectedDrafts"
              >
                <i class="fas fa-check"></i> Confirm Selected
              </button>
              <button
                class="dropdown-item"
                @click="bulkCancelTransactions"
                :disabled="selectedTransactions.length === 0 || !hasSelectedDrafts"
              >
                <i class="fas fa-times"></i> Cancel Selected
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters Panel -->
      <div class="filters-panel card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title">Filters</h3>
          <button class="btn-text" @click="toggleFiltersPanel">
            {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
            <i :class="showFilters ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" class="ml-1"></i>
          </button>
        </div>
        <div v-if="showFilters" class="card-body">
          <form @submit.prevent="applyFilters">
            <div class="filters-grid">
              <!-- State Filter -->
              <div class="form-group">
                <label for="state-filter">State</label>
                <select
                  id="state-filter"
                  v-model="filters.state"
                  class="form-control"
                >
                  <option value="">All States</option>
                  <option value="draft">Draft</option>
                  <option value="confirmed">Confirmed</option>
                  <option value="done">Done</option>
                  <option value="cancelled">Cancelled</option>
                </select>
              </div>

              <!-- Transaction Type Filter -->
              <div class="form-group">
                <label for="type-filter">Transaction Type</label>
                <select
                  id="type-filter"
                  v-model="filters.transaction_type"
                  class="form-control"
                >
                  <option value="">All Types</option>
                  <option value="receive">Receive</option>
                  <option value="issue">Issue</option>
                  <option value="transfer">Transfer</option>
                  <option value="adjustment">Adjustment</option>
                  <option value="return">Return</option>
                  <option value="manufacturing">Manufacturing</option>
                </select>
              </div>

              <!-- Move Type Filter -->
              <div class="form-group">
                <label for="move-type-filter">Move Type</label>
                <select
                  id="move-type-filter"
                  v-model="filters.move_type"
                  class="form-control"
                >
                  <option value="">All Move Types</option>
                  <option value="in">Incoming</option>
                  <option value="out">Outgoing</option>
                  <option value="internal">Internal</option>
                </select>
              </div>

              <!-- Item Filter -->
              <div class="form-group">
                <label for="item-filter">Item</label>
                <div class="select-with-status">
                  <select
                    id="item-filter"
                    v-model="filters.item_id"
                    class="form-control"
                  >
                    <option value="">All Items</option>
                    <option v-for="item in items" :key="item.item_id" :value="item.item_id">
                      {{ item.name }} ({{ item.item_code }})
                    </option>
                  </select>
                  <div v-if="itemsLoading" class="status-indicator">
                    <i class="fas fa-spinner fa-spin"></i>
                  </div>
                </div>
              </div>

              <!-- Warehouse Filter -->
              <div class="form-group">
                <label for="warehouse-filter">Warehouse</label>
                <div class="select-with-status">
                  <select
                    id="warehouse-filter"
                    v-model="filters.warehouse_id"
                    class="form-control"
                  >
                    <option value="">All Warehouses</option>
                    <option v-for="warehouse in warehouses" :key="warehouse.warehouse_id" :value="warehouse.warehouse_id">
                      {{ warehouse.name }}
                    </option>
                  </select>
                  <div v-if="warehousesLoading" class="status-indicator">
                    <i class="fas fa-spinner fa-spin"></i>
                  </div>
                </div>
              </div>

              <!-- Date Range Filter -->
              <div class="form-group">
                <label for="date-from">Date From</label>
                <input
                  id="date-from"
                  type="date"
                  v-model="filters.date_from"
                  class="form-control"
                />
              </div>

              <div class="form-group">
                <label for="date-to">Date To</label>
                <input
                  id="date-to"
                  type="date"
                  v-model="filters.date_to"
                  class="form-control"
                />
              </div>

              <!-- Reference Filter -->
              <div class="form-group">
                <label for="reference-filter">Reference</label>
                <input
                  id="reference-filter"
                  type="text"
                  v-model="filters.reference"
                  placeholder="Document or Number"
                  class="form-control"
                />
              </div>
            </div>

            <div class="filters-actions">
              <button type="button" class="btn btn-outline-secondary" @click="resetFilters">
                <i class="fas fa-eraser"></i> Clear Filters
              </button>
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Apply Filters
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Quick Search -->
      <div class="quick-search mb-3">
        <div class="input-group">
          <span class="input-group-text">
            <i class="fas fa-search"></i>
          </span>
          <input
            type="text"
            v-model="quickSearch"
            placeholder="Quick search: transaction ID, reference, item code..."
            class="form-control"
            @input="handleQuickSearch"
          />
          <button
            v-if="quickSearch"
            class="btn btn-outline-secondary"
            type="button"
            @click="clearQuickSearch"
          >
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>

      <!-- Summary Cards -->
      <div class="summary-cards mb-4" v-if="summary">
        <div class="row">
          <div class="col-md-3">
            <div class="summary-card">
              <div class="summary-icon bg-primary">
                <i class="fas fa-clipboard-list"></i>
              </div>
              <div class="summary-content">
                <div class="summary-title">Total Transactions</div>
                <div class="summary-value">{{ summary.total }}</div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="summary-card">
              <div class="summary-icon bg-warning">
                <i class="fas fa-clock"></i>
              </div>
              <div class="summary-content">
                <div class="summary-title">Draft</div>
                <div class="summary-value">{{ summary.draft }}</div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="summary-card">
              <div class="summary-icon bg-success">
                <i class="fas fa-check-circle"></i>
              </div>
              <div class="summary-content">
                <div class="summary-title">Completed</div>
                <div class="summary-value">{{ summary.done }}</div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="summary-card">
              <div class="summary-icon bg-danger">
                <i class="fas fa-times-circle"></i>
              </div>
              <div class="summary-content">
                <div class="summary-title">Cancelled</div>
                <div class="summary-value">{{ summary.cancelled }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Transactions Table -->
      <div class="card">
        <div class="card-body p-0">
          <!-- Loading State -->
          <div v-if="loading" class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
            <p>Loading transactions...</p>
          </div>

          <!-- Error State -->
          <div v-else-if="error" class="alert alert-danger m-3">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ error }}
            <button class="btn btn-sm btn-outline-danger ml-2" @click="fetchTransactions">
              Try Again
            </button>
          </div>

          <!-- Empty State -->
          <div v-else-if="transactions.length === 0" class="text-center py-5">
            <i class="fas fa-box-open fa-3x mb-3" style="color: var(--gray-400)"></i>
            <h3 class="h5">No Transactions Found</h3>
            <p class="text-muted">
              {{ isFiltered
                ? 'No transactions match your filters. Try adjusting your search criteria.'
                : 'There are no stock transactions recorded yet.'
              }}
            </p>
            <div v-if="isFiltered" class="mt-3">
              <button class="btn btn-outline-secondary" @click="resetFilters">
                Clear Filters
              </button>
            </div>
            <div v-else class="mt-3">
              <router-link to="/stock-transactions/create" class="btn btn-primary">
                Create First Transaction
              </router-link>
            </div>
          </div>

          <!-- Transactions Table -->
          <div v-else class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th width="40">
                    <input
                      type="checkbox"
                      :checked="allSelected"
                      @change="toggleSelectAll"
                      :indeterminate="someSelected"
                    />
                  </th>
                  <th>ID</th>
                  <th>Date</th>
                  <th>State</th>
                  <th>Type</th>
                  <th>Move</th>
                  <th>Item</th>
                  <th>Source</th>
                  <th>Destination</th>
                  <th class="text-right">Quantity</th>
                  <th>Reference</th>
                  <th class="text-right" width="120">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="transaction in transactions" :key="transaction.transaction_id">
                  <td>
                    <input
                      type="checkbox"
                      :value="transaction.transaction_id"
                      v-model="selectedTransactions"
                    />
                  </td>
                  <td>
                    <router-link :to="`/stock-transactions/${transaction.transaction_id}`" class="font-weight-bold">
                      #{{ transaction.transaction_id }}
                    </router-link>
                  </td>
                  <td>{{ formatDate(transaction.transaction_date) }}</td>
                  <td>
                    <span class="badge" :class="getStateClass(transaction.state)">
                      {{ formatState(transaction.state) }}
                    </span>
                  </td>
                  <td>
                    <span class="badge" :class="getTransactionTypeClass(transaction.transaction_type)">
                      {{ formatTransactionType(transaction.transaction_type) }}
                    </span>
                  </td>
                  <td>
                    <span class="badge" :class="getMoveTypeClass(transaction.move_type)">
                      {{ formatMoveType(transaction.move_type) }}
                    </span>
                  </td>
                  <td>
                    <div v-if="transaction.item">
                      <div class="font-weight-bold">{{ transaction.item.item_code }}</div>
                      <div class="small text-muted">{{ transaction.item.name }}</div>
                    </div>
                    <div v-else class="text-muted">--</div>
                  </td>
                  <td>
                    <div v-if="transaction.warehouse">
                      {{ transaction.warehouse.name }}
                      <div v-if="transaction.location" class="small text-muted">
                        Location: {{ transaction.location.code }}
                      </div>
                    </div>
                    <div v-else class="text-muted">--</div>
                  </td>
                  <td>
                    <!-- Destination warehouse for transfers -->
                    <div v-if="transaction.dest_warehouse_id && transaction.dest_warehouse">
                      {{ transaction.dest_warehouse.name }}
                      <div v-if="transaction.destLocation" class="small text-muted">
                        Location: {{ transaction.destLocation.code }}
                      </div>
                    </div>
                    <div v-else-if="transaction.transaction_type === 'transfer'" class="text-muted">
                      <i class="fas fa-exclamation-triangle" title="Destination not specified"></i>
                    </div>
                    <div v-else class="text-muted">--</div>
                  </td>
                  <td class="text-right">
                    <span :class="getQuantityClass(transaction.quantity)">
                      {{ transaction.quantity }}
                      <span v-if="transaction.item && transaction.item.unitOfMeasure" class="text-muted">
                        {{ transaction.item.unitOfMeasure.symbol }}
                      </span>
                    </span>
                  </td>
                  <td>
                    <div v-if="transaction.reference_document" class="font-weight-bold">
                      {{ transaction.reference_document }}
                    </div>
                    <div v-if="transaction.reference_number" class="small text-muted">
                      {{ transaction.reference_number }}
                    </div>
                    <div v-if="!transaction.reference_document && !transaction.reference_number" class="text-muted">
                      --
                    </div>
                  </td>
                  <td class="actions-cell">
                    <div class="actions-dropdown">
                      <button class="btn-icon dropdown-toggle" @click="toggleActionMenu(transaction.transaction_id)">
                        <i class="fas fa-ellipsis-v"></i>
                      </button>
                      <div
                        v-if="activeActionMenu === transaction.transaction_id"
                        class="dropdown-menu show"
                        @click.stop
                      >
                        <router-link :to="`/stock-transactions/${transaction.transaction_id}`" class="dropdown-item">
                          <i class="fas fa-eye"></i> View Details
                        </router-link>

                        <!-- Draft specific actions -->
                        <template v-if="transaction.state === 'draft'">
                          <button
                            class="dropdown-item"
                            @click="confirmSingleTransaction(transaction.transaction_id)"
                          >
                            <i class="fas fa-check"></i> Confirm
                          </button>
                          <button
                            class="dropdown-item text-danger"
                            @click="cancelSingleTransaction(transaction.transaction_id)"
                          >
                            <i class="fas fa-times"></i> Cancel
                          </button>
                        </template>

                        <!-- Done specific actions -->
                        <template v-if="transaction.state === 'done'">
                          <button
                            class="dropdown-item"
                            @click="reverseTransaction(transaction)"
                          >
                            <i class="fas fa-undo"></i> Reverse
                          </button>
                        </template>

                        <div class="dropdown-divider"></div>
                        <button class="dropdown-item" @click="printTransaction(transaction)">
                          <i class="fas fa-print"></i> Print
                        </button>
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="pagination && pagination.total > pagination.per_page" class="card-footer">
          <div class="pagination-container">
            <div class="pagination-info">
              Showing {{ pagination.from }} to {{ pagination.to }}
              of {{ pagination.total }} transactions
            </div>
            <nav class="pagination-nav">
              <button
                class="btn btn-sm btn-outline-secondary"
                :disabled="pagination.current_page === 1"
                @click="changePage(pagination.current_page - 1)"
              >
                <i class="fas fa-chevron-left"></i> Previous
              </button>

              <template v-for="page in paginationPages" :key="page">
                <button
                  v-if="page !== '...'"
                  class="btn btn-sm"
                  :class="page === pagination.current_page ? 'btn-primary' : 'btn-outline-secondary'"
                  @click="changePage(page)"
                >
                  {{ page }}
                </button>
                <span v-else class="pagination-ellipsis">...</span>
              </template>

              <button
                class="btn btn-sm btn-outline-secondary"
                :disabled="pagination.current_page === pagination.last_page"
                @click="changePage(pagination.current_page + 1)"
              >
                Next <i class="fas fa-chevron-right"></i>
              </button>
            </nav>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script>
  import { ref, computed, onMounted, onUnmounted } from 'vue';
  import { useRouter } from 'vue-router';
  import axios from 'axios';

  export default {
    name: 'StockTransactionsList',
    setup() {
      const router = useRouter();

      // Data
      const transactions = ref([]);
      const items = ref([]);
      const warehouses = ref([]);
      const loading = ref(true);
      const itemsLoading = ref(false);
      const warehousesLoading = ref(false);
      const error = ref(null);
      const summary = ref(null);

      // UI State
      const showFilters = ref(false);
      const showBulkActions = ref(false);
      const activeActionMenu = ref(null);
      const quickSearch = ref('');
      const selectedTransactions = ref([]);

      // Filters
      const filters = ref({
        state: '',
        transaction_type: '',
        move_type: '',
        item_id: '',
        warehouse_id: '',
        date_from: '',
        date_to: '',
        reference: ''
      });

      // Pagination
      const pagination = ref(null);
      const currentPage = ref(1);
      const perPage = ref(25);

      // Computed properties
      const isFiltered = computed(() => {
        return Object.values(filters.value).some(value => value !== '') || quickSearch.value !== '';
      });

      const allSelected = computed(() => {
        return transactions.value.length > 0 && selectedTransactions.value.length === transactions.value.length;
      });

      const someSelected = computed(() => {
        return selectedTransactions.value.length > 0 && selectedTransactions.value.length < transactions.value.length;
      });

      const hasSelectedDrafts = computed(() => {
        return selectedTransactions.value.some(id => {
          const transaction = transactions.value.find(t => t.transaction_id === id);
          return transaction && transaction.state === 'draft';
        });
      });

      const paginationPages = computed(() => {
        if (!pagination.value) return [];

        const current = pagination.value.current_page;
        const last = pagination.value.last_page;
        const pages = [];

        // Always show first page
        if (current > 3) {
          pages.push(1);
          if (current > 4) pages.push('...');
        }

        // Show pages around current
        for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
          pages.push(i);
        }

        // Always show last page
        if (current < last - 2) {
          if (current < last - 3) pages.push('...');
          pages.push(last);
        }

        return pages;
      });

      // Methods
      const fetchTransactions = async (page = 1) => {
        loading.value = true;
        error.value = null;

        try {
          const params = {
            page,
            per_page: perPage.value,
            ...filters.value
          };

          // Add quick search
          if (quickSearch.value) {
            params.search = quickSearch.value;
          }

          // Remove empty filters
          Object.keys(params).forEach(key => {
            if (params[key] === '' || params[key] === null) {
              delete params[key];
            }
          });

          const response = await axios.get('/transactions', { params });

          transactions.value = response.data.data.data;
          pagination.value = response.data.data;
          currentPage.value = page;

          // Clear selected transactions when data changes
          selectedTransactions.value = [];

        } catch (err) {
          console.error('Error fetching transactions:', err);
          error.value = 'Failed to load transactions. Please try again.';
        } finally {
          loading.value = false;
        }
      };

      const fetchSummary = async () => {
        try {
          const response = await axios.get('/transactions/summary');
          summary.value = response.data.data;
        } catch (err) {
          console.error('Error fetching summary:', err);
        }
      };

      const fetchItems = async () => {
        itemsLoading.value = true;
        try {
          const response = await axios.get('/items', {
            params: { per_page: 100, active: true }
          });
          items.value = response.data.data.data || response.data.data;
        } catch (err) {
          console.error('Error fetching items:', err);
        } finally {
          itemsLoading.value = false;
        }
      };

      const fetchWarehouses = async () => {
        warehousesLoading.value = true;
        try {
          const response = await axios.get('/warehouses', {
            params: { per_page: 100, active: true }
          });
          warehouses.value = response.data.data.data || response.data.data;
        } catch (err) {
          console.error('Error fetching warehouses:', err);
        } finally {
          warehousesLoading.value = false;
        }
      };

      const toggleFiltersPanel = () => {
        showFilters.value = !showFilters.value;
      };

      const applyFilters = () => {
        currentPage.value = 1;
        fetchTransactions(1);
        showFilters.value = false;
      };

      const resetFilters = () => {
        filters.value = {
          state: '',
          transaction_type: '',
          move_type: '',
          item_id: '',
          warehouse_id: '',
          date_from: '',
          date_to: '',
          reference: ''
        };
        quickSearch.value = '';
        currentPage.value = 1;
        fetchTransactions(1);
      };

      const handleQuickSearch = () => {
        // Debounce the search
        clearTimeout(handleQuickSearch.timeoutId);
        handleQuickSearch.timeoutId = setTimeout(() => {
          currentPage.value = 1;
          fetchTransactions(1);
        }, 500);
      };

      const clearQuickSearch = () => {
        quickSearch.value = '';
        currentPage.value = 1;
        fetchTransactions(1);
      };

      const changePage = (page) => {
        if (page >= 1 && page <= pagination.value.last_page) {
          fetchTransactions(page);
        }
      };

      const toggleSelectAll = () => {
        if (allSelected.value) {
          selectedTransactions.value = [];
        } else {
          selectedTransactions.value = transactions.value.map(t => t.transaction_id);
        }
      };

      const toggleActionMenu = (transactionId) => {
        if (activeActionMenu.value === transactionId) {
          activeActionMenu.value = null;
        } else {
          activeActionMenu.value = transactionId;
        }
      };

      const confirmSingleTransaction = async (transactionId) => {
        try {
          await axios.post(`/stock-transactions/${transactionId}/confirm`);
          await fetchTransactions(currentPage.value);
          await fetchSummary();
          activeActionMenu.value = null;
        } catch (err) {
          console.error('Error confirming transaction:', err);
          alert('Failed to confirm transaction. Please try again.');
        }
      };

      const cancelSingleTransaction = async (transactionId) => {
        if (!confirm('Are you sure you want to cancel this transaction?')) {
          return;
        }

        try {
          await axios.post(`/stock-transactions/${transactionId}/cancel`);
          await fetchTransactions(currentPage.value);
          await fetchSummary();
          activeActionMenu.value = null;
        } catch (err) {
          console.error('Error cancelling transaction:', err);
          alert('Failed to cancel transaction. Please try again.');
        }
      };

      const bulkConfirmTransactions = async () => {
        if (!confirm(`Are you sure you want to confirm ${selectedTransactions.value.length} transactions?`)) {
          return;
        }

        try {
          await axios.post('/stock-transactions/bulk-confirm', {
            transaction_ids: selectedTransactions.value
          });

          await fetchTransactions(currentPage.value);
          await fetchSummary();
          selectedTransactions.value = [];
          showBulkActions.value = false;
        } catch (err) {
          console.error('Error confirming transactions:', err);
          alert('Failed to confirm transactions. Please try again.');
        }
      };

      const bulkCancelTransactions = async () => {
        if (!confirm(`Are you sure you want to cancel ${selectedTransactions.value.length} transactions?`)) {
          return;
        }

        try {
          await axios.post('/stock-transactions/bulk-cancel', {
            transaction_ids: selectedTransactions.value
          });

          await fetchTransactions(currentPage.value);
          await fetchSummary();
          selectedTransactions.value = [];
          showBulkActions.value = false;
        } catch (err) {
          console.error('Error cancelling transactions:', err);
          alert('Failed to cancel transactions. Please try again.');
        }
      };

      const reverseTransaction = (transaction) => {
        router.push({
          path: '/stock-transactions/create',
          query: {
            reverse: transaction.transaction_id,
            item_id: transaction.item_id,
            warehouse_id: transaction.warehouse_id,
            quantity: -transaction.quantity,
            type: 'adjustment'
          }
        });
      };

      const printTransaction = (transaction) => {
        // Open transaction detail in new window for printing
        const url = `/stock-transactions/${transaction.transaction_id}`;
        window.open(url, '_blank');
      };

      // Format functions
      const formatDate = (dateString) => {
        if (!dateString) return '--';

        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        });
      };

      const formatTransactionType = (type) => {
        if (!type) return '--';

        const types = {
          'receive': 'Receive',
          'issue': 'Issue',
          'transfer': 'Transfer',
          'adjustment': 'Adjustment',
          'return': 'Return',
          'manufacturing': 'Manufacturing'
        };

        return types[type.toLowerCase()] || type;
      };

      const formatState = (state) => {
        if (!state) return '--';

        const states = {
          'draft': 'Draft',
          'confirmed': 'Confirmed',
          'done': 'Done',
          'cancelled': 'Cancelled'
        };

        return states[state.toLowerCase()] || state;
      };

      const formatMoveType = (moveType) => {
        if (!moveType) return '--';

        const types = {
          'in': 'In',
          'out': 'Out',
          'internal': 'Internal'
        };

        return types[moveType.toLowerCase()] || moveType;
      };

      const getTransactionTypeClass = (type) => {
        if (!type) return '';

        const typeClasses = {
          'receive': 'badge-success',
          'issue': 'badge-danger',
          'transfer': 'badge-warning',
          'adjustment': 'badge-secondary',
          'return': 'badge-info',
          'manufacturing': 'badge-primary'
        };

        return typeClasses[type.toLowerCase()] || 'badge-secondary';
      };

      const getStateClass = (state) => {
        if (!state) return '';

        const stateClasses = {
          'draft': 'badge-warning',
          'confirmed': 'badge-info',
          'done': 'badge-success',
          'cancelled': 'badge-danger'
        };

        return stateClasses[state.toLowerCase()] || 'badge-secondary';
      };

      const getMoveTypeClass = (moveType) => {
        if (!moveType) return '';

        const typeClasses = {
          'in': 'badge-success',
          'out': 'badge-danger',
          'internal': 'badge-info'
        };

        return typeClasses[moveType.toLowerCase()] || 'badge-secondary';
      };

      const getQuantityClass = (quantity) => {
        if (!quantity) return '';

        if (quantity > 0) {
          return 'text-success';
        } else if (quantity < 0) {
          return 'text-danger';
        }

        return '';
      };

      // Event listeners
      const handleClickOutside = (event) => {
        if (!event.target.closest('.actions-dropdown')) {
          activeActionMenu.value = null;
        }
        if (!event.target.closest('.dropdown')) {
          showBulkActions.value = false;
        }
      };

      // Lifecycle hooks
      onMounted(() => {
        fetchTransactions();
        fetchSummary();
        fetchItems();
        fetchWarehouses();
        document.addEventListener('click', handleClickOutside);
      });

      onUnmounted(() => {
        document.removeEventListener('click', handleClickOutside);
      });

      return {
        transactions,
        items,
        warehouses,
        loading,
        itemsLoading,
        warehousesLoading,
        error,
        summary,
        showFilters,
        showBulkActions,
        activeActionMenu,
        quickSearch,
        selectedTransactions,
        filters,
        pagination,
        currentPage,
        isFiltered,
        allSelected,
        someSelected,
        hasSelectedDrafts,
        paginationPages,
        fetchTransactions,
        toggleFiltersPanel,
        applyFilters,
        resetFilters,
        handleQuickSearch,
        clearQuickSearch,
        changePage,
        toggleSelectAll,
        toggleActionMenu,
        confirmSingleTransaction,
        cancelSingleTransaction,
        bulkConfirmTransactions,
        bulkCancelTransactions,
        reverseTransaction,
        printTransaction,
        formatDate,
        formatTransactionType,
        formatState,
        formatMoveType,
        getTransactionTypeClass,
        getStateClass,
        getMoveTypeClass,
        getQuantityClass
      };
    }
  };
  </script>

  <style scoped>
  .stock-transactions-list {
    padding: 1rem;
  }

  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }

  .page-header h1 {
    margin: 0;
    color: var(--gray-800);
  }

  .page-actions {
    display: flex;
    gap: 0.75rem;
    position: relative;
  }

  .dropdown {
    position: relative;
  }

  .dropdown-toggle {
    position: relative;
  }

  .dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    z-index: 1000;
    min-width: 200px;
    background-color: white;
    border: 1px solid var(--gray-200);
    border-radius: 0.375rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    padding: 0.5rem 0;
    margin-top: 0.25rem;
  }

  .dropdown-menu.show {
    display: block;
  }

  .dropdown-item {
    display: block;
    width: 100%;
    padding: 0.5rem 1rem;
    background: none;
    border: none;
    text-align: left;
    text-decoration: none;
    color: var(--gray-700);
    cursor: pointer;
  }

  .dropdown-item:hover {
    background-color: var(--gray-100);
  }

  .dropdown-item:disabled {
    color: var(--gray-400);
    cursor: not-allowed;
  }

  .dropdown-item:disabled:hover {
    background-color: transparent;
  }

  .dropdown-divider {
    height: 0;
    margin: 0.5rem 0;
    overflow: hidden;
    border-top: 1px solid var(--gray-200);
  }

  .filters-panel .card-header {
    background-color: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
  }

  .filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
  }

  .filters-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
    padding-top: 1rem;
    border-top: 1px solid var(--gray-200);
  }

  .select-with-status {
    position: relative;
  }

  .status-indicator {
    position: absolute;
    right: 2rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray-500);
  }

  .quick-search .input-group {
    display: flex;
    align-items: stretch;
  }

  .input-group-text {
    background-color: var(--gray-100);
    border: 1px solid var(--gray-300);
    border-right: none;
    padding: 0.5rem 0.75rem;
    border-radius: 0.375rem 0 0 0.375rem;
  }

  .quick-search .form-control {
    border-left: none;
    border-radius: 0;
  }

  .quick-search .btn {
    border-radius: 0 0.375rem 0.375rem 0;
  }

  .summary-cards .row {
    display: flex;
    flex-wrap: wrap;
    margin: -0.5rem;
  }

  .summary-cards .col-md-3 {
    flex: 0 0 25%;
    max-width: 25%;
    padding: 0.5rem;
  }

  .summary-card {
    display: flex;
    align-items: center;
    padding: 1rem;
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .summary-icon {
    width: 3rem;
    height: 3rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    margin-right: 1rem;
  }

  .bg-primary {
    background-color: var(--primary-color);
  }

  .bg-warning {
    background-color: var(--warning-color);
  }

  .bg-success {
    background-color: var(--success-color);
  }

  .bg-danger {
    background-color: var(--danger-color);
  }

  .summary-title {
    font-size: 0.875rem;
    color: var(--gray-600);
    margin-bottom: 0.25rem;
  }

  .summary-value {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--gray-800);
  }

  .card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
  }

  .card-header {
    padding: 1rem;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .card-title {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--gray-800);
  }

  .card-body {
    padding: 1rem;
  }

  .card-body.p-0 {
    padding: 0;
  }

  .card-footer {
    padding: 1rem;
    background-color: var(--gray-50);
    border-top: 1px solid var(--gray-200);
  }

  .form-group {
    margin-bottom: 1rem;
  }

  .form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--gray-700);
  }

  .form-control {
    width: 100%;
    padding: 0.5rem 0.75rem;
    font-size: 1rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
    background-color: white;
  }

  .form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
  }

  .table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  .data-table {
    width: 100%;
    margin-bottom: 1rem;
    color: var(--gray-800);
    border-collapse: collapse;
  }

  .data-table th,
  .data-table td {
    padding: 0.75rem;
    vertical-align: top;
    border-top: 1px solid var(--gray-200);
  }

  .data-table thead th {
    vertical-align: bottom;
    border-bottom: 2px solid var(--gray-200);
    background-color: var(--gray-50);
    color: var(--gray-600);
    font-weight: 500;
    font-size: 0.875rem;
  }

  .data-table tbody tr:hover {
    background-color: var(--gray-50);
  }

  .actions-cell {
    position: relative;
  }

  .actions-dropdown {
    position: relative;
  }

  .btn-icon {
    background: none;
    border: none;
    padding: 0.25rem 0.5rem;
    cursor: pointer;
    color: var(--gray-600);
    border-radius: 0.25rem;
  }

  .btn-icon:hover {
    background-color: var(--gray-100);
  }

  .pagination-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .pagination-info {
    color: var(--gray-600);
    font-size: 0.875rem;
  }

  .pagination-nav {
    display: flex;
    gap: 0.25rem;
    align-items: center;
  }

  .pagination-ellipsis {
    padding: 0.25rem 0.5rem;
    color: var(--gray-500);
  }

  .badge {
    display: inline-block;
    padding: 0.35em 0.65em;
    font-size: 0.75em;
    font-weight: 700;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 0.375rem;
  }

  .badge-success {
    background-color: var(--success-light);
    color: var(--success-color);
  }

  .badge-danger {
    background-color: var(--danger-light);
    color: var(--danger-color);
  }

  .badge-warning {
    background-color: var(--warning-light);
    color: var(--warning-color);
  }

  .badge-info {
    background-color: var(--primary-bg);
    color: var(--primary-color);
  }

  .badge-secondary {
    background-color: var(--gray-200);
    color: var(--gray-700);
  }

  .badge-primary {
    background-color: var(--primary-color);
    color: white;
  }

  .btn-text {
    background: none;
    border: none;
    color: var(--primary-color);
    cursor: pointer;
    padding: 0.25rem 0.5rem;
    display: inline-flex;
    align-items: center;
    font-size: 0.875rem;
  }

  .btn-text:hover {
    text-decoration: underline;
  }

  .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    border-radius: 0.25rem;
  }

  .btn-outline-secondary {
    color: var(--gray-600);
    border: 1px solid var(--gray-300);
    background-color: transparent;
  }

  .btn-outline-secondary:hover {
    color: white;
    background-color: var(--gray-600);
  }

  .btn-outline-danger {
    color: var(--danger-color);
    border: 1px solid var(--danger-color);
    background-color: transparent;
  }

  .btn-outline-danger:hover {
    color: white;
    background-color: var(--danger-color);
  }

  .text-muted {
    color: var(--gray-600);
  }

  .text-success {
    color: var(--success-color);
  }

  .text-danger {
    color: var(--danger-color);
  }

  .text-warning {
    color: var(--warning-color);
  }

  .font-weight-bold {
    font-weight: 700;
  }

  .small {
    font-size: 0.875rem;
  }

  .text-right {
    text-align: right;
  }

  .mb-4 {
    margin-bottom: 1.5rem;
  }

  .mb-3 {
    margin-bottom: 1rem;
  }

  .mt-3 {
    margin-top: 1rem;
  }

  .ml-1 {
    margin-left: 0.25rem;
  }

  .ml-2 {
    margin-left: 0.5rem;
  }

  .mr-2 {
    margin-right: 0.5rem;
  }

  .py-5 {
    padding-top: 3rem;
    padding-bottom: 3rem;
  }

  .fa-3x {
    font-size: 3rem;
  }

  .fa-2x {
    font-size: 2rem;
  }

  .d-flex {
    display: flex;
  }

  .justify-content-between {
    justify-content: space-between;
  }

  .align-items-center {
    align-items: center;
  }

  .text-center {
    text-align: center;
  }

  .alert {
    position: relative;
    padding: 0.75rem 1.25rem;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    border-radius: 0.25rem;
  }

  .alert-danger {
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
  }

  /* Responsive styles */
  @media (max-width: 992px) {
    .summary-cards .col-md-3 {
      flex: 0 0 50%;
      max-width: 50%;
    }

    .filters-grid {
      grid-template-columns: 1fr;
    }

    .pagination-container {
      flex-direction: column;
      gap: 1rem;
    }

    .page-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 1rem;
    }

    .page-actions {
      width: 100%;
      justify-content: flex-start;
    }
  }

  @media (max-width: 576px) {
    .summary-cards .col-md-3 {
      flex: 0 0 100%;
      max-width: 100%;
    }

    .data-table {
      font-size: 0.875rem;
    }

    .data-table th,
    .data-table td {
      padding: 0.5rem;
    }

    /* Hide some columns on mobile */
    .data-table th:nth-child(6),
    .data-table td:nth-child(6),
    .data-table th:nth-child(8),
    .data-table td:nth-child(8),
    .data-table th:nth-child(9),
    .data-table td:nth-child(9) {
      display: none;
    }
  }
  </style>
