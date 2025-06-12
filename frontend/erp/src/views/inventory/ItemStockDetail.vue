<!-- src/views/inventory/ItemStockDetail.vue -->
<template>
  <div class="item-detail">
    <div class="page-header">
      <div class="header-left">
        <router-link to="/item-stocks" class="back-link">
          <i class="fas fa-arrow-left"></i> Back to Stock List
        </router-link>
        <h1>Item Stock Details</h1>
      </div>
      <div class="actions">
        <router-link :to="`/item-stocks/transfer?item=${itemId}`" class="btn btn-primary">
          <i class="fas fa-exchange-alt"></i> Transfer Stock
        </router-link>
        <router-link :to="`/item-stocks/adjust?item=${itemId}`" class="btn btn-secondary">
          <i class="fas fa-sliders-h"></i> Adjust Stock
        </router-link>
      </div>
    </div>

    <div v-if="loading" class="loading">
      <i class="fas fa-spinner fa-spin"></i> Loading item data...
    </div>

    <div v-else-if="error" class="error-message">
      <i class="fas fa-exclamation-circle"></i>
      {{ error }}
    </div>

    <div v-else-if="!item" class="empty-state">
      <i class="fas fa-box-open"></i>
      <h3>Item Not Found</h3>
      <p>The requested item could not be found. Please check the item ID and try again.</p>
    </div>

    <div v-else class="content-container">
      <div class="item-info-card">
        <div class="card-header">
          <h2>Item Information</h2>
        </div>
        <div class="card-body">
          <div class="info-grid">
            <div class="info-item">
              <div class="info-label">Item Code</div>
              <div class="info-value">{{ item.item_code }}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Item Name</div>
              <div class="info-value">{{ item.item_name || item.name }}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Category</div>
              <div class="info-value">{{ item.category_name || 'N/A' }}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Unit of Measure</div>
              <div class="info-value">{{ item.uom_name || 'N/A' }}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Total Stock</div>
              <div class="info-value highlighted">{{ formatNumber(item.total_stock) }}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Stock Status</div>
              <div class="info-value">
                <span class="status-badge" :class="getStockStatusClass(item)">
                  {{ getStockStatus(item) }}
                </span>
              </div>
            </div>
            <div class="info-item">
              <div class="info-label">Min Stock Level</div>
              <div class="info-value">{{ formatNumber(item.minimum_stock) || 'Not set' }}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Max Stock Level</div>
              <div class="info-value">{{ formatNumber(item.maximum_stock) || 'Not set' }}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="warehouse-stocks-card">
        <div class="card-header">
          <h2>Warehouse Stock Distribution</h2>
        </div>
        <div class="card-body">
          <div v-if="!item.warehouse_stocks || item.warehouse_stocks.length === 0" class="empty-state small">
            <i class="fas fa-warehouse"></i>
            <p>No stock found in any warehouse</p>
          </div>
          <table v-else class="stock-table">
            <thead>
              <tr>
                <th>Warehouse</th>
                <th>Quantity</th>
                <th>Reserved</th>
                <th>Available</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="stock in item.warehouse_stocks" :key="stock.warehouse_id">
                <td>{{ stock.warehouse_name }}</td>
                <td>{{ formatNumber(stock.quantity) }}</td>
                <td>{{ formatNumber(stock.reserved_quantity) }}</td>
                <td>{{ formatNumber(stock.available_quantity) }}</td>
                <td class="actions-cell">
                  <router-link
                    :to="`/item-stocks/transfer?item=${itemId}&warehouse=${stock.warehouse_id}`"
                    class="btn-icon"
                    title="Transfer"
                  >
                    <i class="fas fa-exchange-alt"></i>
                  </router-link>
                  <router-link
                    :to="`/item-stocks/adjust?item=${itemId}&warehouse=${stock.warehouse_id}`"
                    class="btn-icon"
                    title="Adjust"
                  >
                    <i class="fas fa-sliders-h"></i>
                  </router-link>
                  <router-link
                    :to="`/item-stocks/reserve?item=${itemId}&warehouse=${stock.warehouse_id}`"
                    class="btn-icon"
                    title="Reserve"
                  >
                    <i class="fas fa-lock"></i>
                  </router-link>
                  <router-link
                    :to="`/stock-transactions/items/${itemId}/movement?warehouse=${stock.warehouse_id}`"
                    class="btn-icon"
                    title="Movement History"
                  >
                    <i class="fas fa-history"></i>
                  </router-link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="transactions-card">
        <div class="card-header">
          <h2>Recent Transactions</h2>
          <router-link :to="`/stock-transactions/items/${itemId}/movement`" class="view-all-link">
            View All <i class="fas fa-chevron-right"></i>
          </router-link>
        </div>
        <div class="card-body">
          <div v-if="loadingTransactions" class="loading-small">
            <i class="fas fa-spinner fa-spin"></i> Loading transactions...
          </div>
          <div v-else-if="transactions.length === 0" class="empty-state small">
            <i class="fas fa-exchange-alt"></i>
            <p>No recent transactions found</p>
          </div>
          <table v-else class="transactions-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Type</th>
                <th>From Warehouse</th>
                <th>To Warehouse</th>
                <th>Quantity</th>
                <th>Reference</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(tx, index) in transactions" :key="tx.transaction_id || index">
                <td>{{ formatDate(tx.transaction_date) }}</td>
                <td>
                  <span class="transaction-type" :class="getTransactionTypeClass(tx)">
                    {{ formatTransactionType(tx.transaction_type) }}
                  </span>
                </td>
                <td>
                  <span class="warehouse-name">
                    {{ getWarehouseName(tx.warehouse) }}
                  </span>
                </td>
                <td>
                  <span class="warehouse-name" v-if="tx.dest_warehouse">
                    {{ getWarehouseName(tx.dest_warehouse) }}
                  </span>
                  <span v-else class="text-muted">-</span>
                </td>
                <td :class="getQuantityClass(tx.quantity)">
                  {{ formatQuantity(tx.quantity) }}
                </td>
                <td class="reference-cell">
                  <div v-if="tx.reference_document">
                    <strong>{{ tx.reference_document }}</strong>
                    <br v-if="tx.reference_number">
                    <small v-if="tx.reference_number">#{{ tx.reference_number }}</small>
                  </div>
                  <span v-else class="text-muted">N/A</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'ItemStockDetail',
  props: {
    itemId: {
      type: [String, Number],
      required: true
    }
  },
  data() {
    return {
      item: null,
      transactions: [],
      loading: true,
      loadingTransactions: false,
      error: null
    };
  },
  created() {
    this.fetchItemStockData();
    this.fetchRecentTransactions();
  },
  methods: {
    async fetchItemStockData() {
      this.loading = true;
      this.error = null;

      try {
        const response = await axios.get(`/item-stocks/item/${this.itemId}`);

        if (response.data && response.data.data) {
          this.item = response.data.data;

          // Ensure all warehouse stocks have available_quantity
          if (this.item.warehouse_stocks) {
            this.item.warehouse_stocks = this.item.warehouse_stocks.map(stock => ({
              ...stock,
              quantity: Number(stock.quantity) || 0,
              reserved_quantity: Number(stock.reserved_quantity) || 0,
              available_quantity: (Number(stock.quantity) || 0) - (Number(stock.reserved_quantity) || 0)
            }));
          }

          // Ensure total_stock is a number
          this.item.total_stock = Number(this.item.total_stock) || 0;
          this.item.minimum_stock = Number(this.item.minimum_stock) || 0;
          this.item.maximum_stock = Number(this.item.maximum_stock) || 0;
        } else {
          this.item = null;
        }
      } catch (err) {
        console.error('Error fetching item stock data:', err);
        this.error = 'Failed to load item stock data. Please try again later.';
      } finally {
        this.loading = false;
      }
    },

    async fetchRecentTransactions() {
      this.loadingTransactions = true;

      try {
        const response = await axios.get(`/transactions/items/${this.itemId}/movement?limit=5`);

        console.log('Raw transaction response:', response.data);

        // Handle different response structures
        let transactionData = [];

        if (response.data && response.data.success && response.data.data) {
          // Structure: { success: true, data: { item: {...}, transactions: { data: [...] } } }
          if (response.data.data.transactions && response.data.data.transactions.data) {
            transactionData = response.data.data.transactions.data;
          }
        } else if (response.data && response.data.data) {
          // Structure: { data: [...] }
          transactionData = Array.isArray(response.data.data) ? response.data.data : [];
        } else if (response.data && Array.isArray(response.data)) {
          // Structure: [...]
          transactionData = response.data;
        }

        // Process transactions
        this.transactions = transactionData.slice(0, 5).map(tx => ({
          ...tx,
          // Convert quantity to number
          quantity: Number(tx.quantity) || 0,
          // Add warehouse_name for backward compatibility if needed
          warehouse_name: this.getWarehouseName(tx.warehouse)
        }));

        console.log('Processed transactions:', this.transactions);

      } catch (err) {
        console.error('Error fetching transactions:', err);
        this.transactions = [];
      } finally {
        this.loadingTransactions = false;
      }
    },

    // Helper method to safely get warehouse name
    getWarehouseName(warehouse) {
      if (typeof warehouse === 'string') {
        return warehouse;
      }
      if (warehouse && warehouse.name) {
        return warehouse.name;
      }
      return 'Unknown Warehouse';
    },

    // Helper method to format numbers consistently
    formatNumber(value) {
      const num = Number(value);
      if (isNaN(num)) return '0.00';
      return num.toFixed(2);
    },

    // Helper method to format quantity with +/- prefix
    formatQuantity(quantity) {
      const num = Number(quantity);
      if (isNaN(num)) return '0.00';
      const prefix = num > 0 ? '+' : '';
      return `${prefix}${num.toFixed(2)}`;
    },

    // Helper method to get quantity CSS class
    getQuantityClass(quantity) {
      const num = Number(quantity);
      return {
        'quantity-positive': num > 0,
        'quantity-negative': num < 0,
        'quantity-zero': num === 0
      };
    },

    getStockStatus(item) {
      const totalStock = Number(item.total_stock) || 0;
      const minStock = Number(item.minimum_stock) || 0;
      const maxStock = Number(item.maximum_stock) || Infinity;

      if (totalStock <= 0) {
        return 'Out of Stock';
      } else if (totalStock < minStock && minStock > 0) {
        return 'Low Stock';
      } else if (totalStock > maxStock && maxStock !== Infinity && maxStock > 0) {
        return 'High Stock';
      } else {
        return 'Normal';
      }
    },

    getStockStatusClass(item) {
      const status = this.getStockStatus(item);

      switch(status) {
        case 'Out of Stock': return 'status-danger';
        case 'Low Stock': return 'status-warning';
        case 'High Stock': return 'status-info';
        default: return 'status-success';
      }
    },

    formatTransactionType(type) {
      const typeMap = {
        'manufacturing': 'Manufacturing',
        'receive': 'Received',
        'issue': 'Issued',
        'transfer': 'Transfer',
        'adjustment': 'Adjustment',
        'return': 'Return'
      };
      return typeMap[type] || (type ? type.charAt(0).toUpperCase() + type.slice(1) : 'Unknown');
    },

    getTransactionTypeClass(tx) {
      const type = tx.transaction_type;
      const quantity = Number(tx.quantity) || 0;

      // Base class on transaction type
      let baseClass = '';
      switch(type) {
        case 'manufacturing':
          baseClass = 'type-manufacturing';
          break;
        case 'receive':
          baseClass = 'type-receive';
          break;
        case 'issue':
          baseClass = 'type-issue';
          break;
        case 'transfer':
          baseClass = 'type-transfer';
          break;
        case 'adjustment':
          baseClass = 'type-adjustment';
          break;
        default:
          baseClass = 'type-default';
      }

      // Add quantity-based class
      if (quantity > 0) {
        return `${baseClass} type-positive`;
      } else if (quantity < 0) {
        return `${baseClass} type-negative`;
      } else {
        return baseClass;
      }
    },

    formatDate(dateString) {
      if (!dateString) return 'N/A';

      try {
        const date = new Date(dateString);
        return date.toLocaleDateString(undefined, {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        });
      } catch (error) {
        return dateString;
      }
    }
  }
};
</script>

<style scoped>
/* CSS Variables for theming */
:root {
  --primary-color: #3b82f6;
  --primary-dark: #2563eb;
  --success-color: #059669;
  --warning-color: #d97706;
  --danger-color: #dc2626;
  --gray-50: #f9fafb;
  --gray-100: #f3f4f6;
  --gray-200: #e5e7eb;
  --gray-500: #6b7280;
  --gray-600: #4b5563;
  --gray-700: #374151;
  --gray-800: #1f2937;
}

.item-detail {
  margin-bottom: 2rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
}

.header-left {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--primary-color);
  text-decoration: none;
  font-size: 0.875rem;
}

.back-link:hover {
  text-decoration: underline;
}

.page-header h1 {
  margin: 0;
  font-size: 1.75rem;
}

.actions {
  display: flex;
  gap: 0.75rem;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1rem;
  border-radius: 0.375rem;
  font-weight: 500;
  font-size: 0.875rem;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
  text-decoration: none;
}

.btn-primary {
  background-color: var(--primary-color);
  color: white;
}

.btn-primary:hover {
  background-color: var(--primary-dark);
}

.btn-secondary {
  background-color: var(--gray-100);
  color: var(--gray-800);
  border: 1px solid var(--gray-200);
}

.btn-secondary:hover {
  background-color: var(--gray-200);
}

.loading, .error-message, .empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  text-align: center;
  color: var(--gray-600);
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.loading-small {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  color: var(--gray-600);
}

.loading i, .error-message i, .empty-state i, .loading-small i {
  font-size: 2.5rem;
  margin-bottom: 1rem;
}

.loading-small i {
  font-size: 1rem;
  margin-bottom: 0;
  margin-right: 0.5rem;
}

.error-message {
  color: var(--danger-color);
}

.empty-state h3 {
  margin: 0 0 0.5rem;
  color: var(--gray-700);
}

.empty-state p {
  color: var(--gray-500);
  max-width: 20rem;
}

.empty-state.small {
  padding: 2rem;
}

.empty-state.small i {
  font-size: 1.5rem;
  margin-bottom: 0.5rem;
}

.empty-state.small p {
  margin: 0;
}

.content-container {
  display: grid;
  grid-template-columns: repeat(1, 1fr);
  gap: 1.5rem;
}

.item-info-card, .warehouse-stocks-card, .transactions-card {
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
  font-size: 1.25rem;
  color: var(--gray-800);
}

.view-all-link {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  color: var(--primary-color);
  text-decoration: none;
  font-size: 0.875rem;
}

.view-all-link:hover {
  text-decoration: underline;
}

.card-body {
  padding: 1.5rem;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 1.5rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.info-label {
  font-size: 0.75rem;
  color: var(--gray-500);
  font-weight: 500;
}

.info-value {
  font-size: 0.875rem;
  color: var(--gray-800);
}

.info-value.highlighted {
  font-size: 1rem;
  font-weight: 600;
  color: var(--primary-color);
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  font-weight: 500;
}

.status-success {
  background-color: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
}

.status-warning {
  background-color: rgba(217, 119, 6, 0.1);
  color: var(--warning-color);
}

.status-danger {
  background-color: rgba(220, 38, 38, 0.1);
  color: var(--danger-color);
}

.status-info {
  background-color: rgba(37, 99, 235, 0.1);
  color: var(--primary-color);
}

.stock-table, .transactions-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.stock-table th, .transactions-table th {
  padding: 0.75rem 1rem;
  text-align: left;
  font-weight: 500;
  color: var(--gray-700);
  background-color: var(--gray-50);
  border-bottom: 1px solid var(--gray-200);
}

.stock-table td, .transactions-table td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--gray-100);
  color: var(--gray-800);
}

.stock-table tbody tr:hover, .transactions-table tbody tr:hover {
  background-color: var(--gray-50);
}

.actions-cell {
  display: flex;
  gap: 0.5rem;
}

.btn-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 2rem;
  width: 2rem;
  border-radius: 0.375rem;
  color: var(--gray-600);
  background-color: var(--gray-50);
  border: 1px solid var(--gray-200);
  transition: all 0.2s;
  text-decoration: none;
}

.btn-icon:hover {
  background-color: var(--gray-100);
  color: var(--gray-800);
}

/* Warehouse name styling */
.warehouse-name {
  font-weight: 500;
  color: var(--gray-700);
}

/* Reference cell styling */
.reference-cell {
  min-width: 150px;
}

.reference-cell strong {
  color: var(--gray-700);
}

.reference-cell small {
  color: var(--gray-500);
}

/* Transaction type styling */
.transaction-type {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: capitalize;
}

.type-manufacturing {
  background-color: #e3f2fd;
  color: #1976d2;
}

.type-receive {
  background-color: #e8f5e8;
  color: #388e3c;
}

.type-issue {
  background-color: #ffebee;
  color: #d32f2f;
}

.type-transfer {
  background-color: #fff3e0;
  color: #f57c00;
}

.type-adjustment {
  background-color: #f3e5f5;
  color: #7b1fa2;
}

.type-default {
  background-color: var(--gray-100);
  color: var(--gray-700);
}

/* Quantity styling */
.quantity-positive {
  color: var(--success-color);
  font-weight: 600;
}

.quantity-negative {
  color: var(--danger-color);
  font-weight: 600;
}

.quantity-zero {
  color: var(--gray-500);
}

/* Utility classes */
.text-muted {
  color: var(--gray-500);
}

@media (min-width: 1024px) {
  .content-container {
    grid-template-columns: repeat(2, 1fr);
  }

  .item-info-card {
    grid-column: 1 / -1;
  }
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    gap: 1rem;
  }

  .actions {
    width: 100%;
  }

  .btn {
    flex: 1;
    justify-content: center;
  }

  .info-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .stock-table, .transactions-table {
    display: block;
    overflow-x: auto;
    white-space: nowrap;
  }

  .actions-cell {
    flex-wrap: nowrap;
  }
}
</style>
