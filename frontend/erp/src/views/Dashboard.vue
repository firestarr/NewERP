<!-- src/views/Dashboard.vue -->
<template>
    <div class="dashboard">
        <h1>Welcome to AOS</h1>
      <!-- Statistics Cards -->
      <!-- <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-box"></i>
          </div>
          <div class="stat-details">
            <h3 class="stat-title">Total Items</h3>
            <p class="stat-value">{{ stats.totalItems }}</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-warehouse"></i>
          </div>
          <div class="stat-details">
            <h3 class="stat-title">Warehouses</h3>
            <p class="stat-value">{{ stats.totalWarehouses }}</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon warning">
            <i class="fas fa-exclamation-triangle"></i>
          </div>
          <div class="stat-details">
            <h3 class="stat-title">Low Stock Items</h3>
            <p class="stat-value">{{ stats.lowStockItems }}</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon success">
            <i class="fas fa-exchange-alt"></i>
          </div>
          <div class="stat-details">
            <h3 class="stat-title">Transactions Today</h3>
            <p class="stat-value">{{ stats.transactionsToday }}</p>
          </div>
        </div>
      </div> -->

      <div class="dashboard-widgets">
        <!-- Recent Transactions Widget -->
        <!-- <div class="widget">
          <div class="widget-header">
            <h2 class="widget-title">Recent Transactions</h2>
            <router-link to="/stock-transactions" class="view-all">View All</router-link>
          </div>
          <div class="widget-content">
            <div v-if="isLoadingTransactions" class="loading-indicator">
              <i class="fas fa-spinner fa-spin"></i> Loading...
            </div>
            <div v-else-if="recentTransactions.length === 0" class="empty-state">
              No recent transactions
            </div>
            <table v-else class="data-table">
              <thead>
                <tr>
                  <th>Item</th>
                  <th>Type</th>
                  <th>Quantity</th>
                  <th>Warehouse</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="transaction in recentTransactions" :key="transaction.transaction_id">
                  <td>{{ transaction.item.name }}</td>
                  <td>
                    <span class="transaction-type" :class="getTransactionTypeClass(transaction.transaction_type)">
                      {{ transaction.transaction_type }}
                    </span>
                  </td>
                  <td>{{ transaction.quantity }}</td>
                  <td>{{ transaction.warehouse.name }}</td>
                  <td>{{ formatDate(transaction.transaction_date) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div> -->

        <!-- Low Stock Items Widget -->
        <!-- <div class="widget">
          <div class="widget-header">
            <h2 class="widget-title">Low Stock Items</h2>
            <router-link to="/items?filter=low_stock" class="view-all">View All</router-link>
          </div>
          <div class="widget-content">
            <div v-if="isLoadingLowStock" class="loading-indicator">
              <i class="fas fa-spinner fa-spin"></i> Loading...
            </div>
            <div v-else-if="lowStockItems.length === 0" class="empty-state">
              No low stock items
            </div>
            <table v-else class="data-table">
              <thead>
                <tr>
                  <th>Item Code</th>
                  <th>Name</th>
                  <th>Current Stock</th>
                  <th>Min. Stock</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in lowStockItems" :key="item.item_id">
                  <td>{{ item.item_code }}</td>
                  <td>{{ item.name }}</td>
                  <td>{{ item.current_stock }}</td>
                  <td>{{ item.minimum_stock }}</td>
                  <td>
                    <span class="stock-status low">Low Stock</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div> -->
      </div>
    </div>
  </template>

  <script>
  import { ref, onMounted } from 'vue';
  //import axios from 'axios';

  export default {
    name: 'DashboardView',
    setup() {
      const stats = ref({
        totalItems: 0,
        totalWarehouses: 0,
        lowStockItems: 0,
        transactionsToday: 0
      });

      const recentTransactions = ref([]);
      const lowStockItems = ref([]);
      const isLoadingTransactions = ref(true);
      const isLoadingLowStock = ref(true);

      //const fetchDashboardData = async () => {
        //try {
          // Fetch dashboard statistics
          //const statsResponse = await axios.get('/api/dashboard/stats');
          //stats.value = statsResponse.data;

          // Fetch recent transactions
          //const transactionsResponse = await axios.get('/api/stock-transactions?limit=5');
          //recentTransactions.value = transactionsResponse.data.data;
          //isLoadingTransactions.value = false;

          // Fetch low stock items
          //const lowStockResponse = await axios.get('/api/items/stock-status?status=low_stock&limit=5');
          //lowStockItems.value = lowStockResponse.data.data;
          //isLoadingLowStock.value = false;
        //} catch (error) {
          //console.error('Error fetching dashboard data:', error);
          //isLoadingTransactions.value = false;
          //isLoadingLowStock.value = false;
        //}
      //};

      const getTransactionTypeClass = (type) => {
        switch (type.toUpperCase()) {
          case 'IN':
          case 'RECEIPT':
          case 'RETURN':
          case 'ADJUSTMENT_IN':
            return 'type-in';
          case 'OUT':
          case 'ISSUE':
          case 'SALE':
          case 'ADJUSTMENT_OUT':
            return 'type-out';
          default:
            return '';
        }
      };

      const formatDate = (dateString) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        });
      };

      // For demo purposes, we'll use dummy data
      const loadDummyData = () => {
        stats.value = {
          totalItems: 156,
          totalWarehouses: 3,
          lowStockItems: 12,
          transactionsToday: 24
        };

        recentTransactions.value = [
          {
            transaction_id: 1,
            item: { name: 'Laptop Model X' },
            transaction_type: 'IN',
            quantity: 50,
            warehouse: { name: 'Main Warehouse' },
            transaction_date: '2025-03-22'
          },
          {
            transaction_id: 2,
            item: { name: 'Smartphone Y Pro' },
            transaction_type: 'OUT',
            quantity: 10,
            warehouse: { name: 'Main Warehouse' },
            transaction_date: '2025-03-22'
          },
          {
            transaction_id: 3,
            item: { name: 'USB Cable Type-C' },
            transaction_type: 'SALE',
            quantity: 100,
            warehouse: { name: 'Retail Store' },
            transaction_date: '2025-03-21'
          },
          {
            transaction_id: 4,
            item: { name: 'Office Chair' },
            transaction_type: 'ADJUSTMENT_IN',
            quantity: 5,
            warehouse: { name: 'Office Supplies' },
            transaction_date: '2025-03-20'
          },
          {
            transaction_id: 5,
            item: { name: 'Wireless Mouse' },
            transaction_type: 'RECEIPT',
            quantity: 25,
            warehouse: { name: 'Main Warehouse' },
            transaction_date: '2025-03-20'
          }
        ];

        lowStockItems.value = [
          {
            item_id: 1,
            item_code: 'MOUSE-001',
            name: 'Wireless Mouse',
            current_stock: 5,
            minimum_stock: 10
          },
          {
            item_id: 2,
            item_code: 'KB-001',
            name: 'Mechanical Keyboard',
            current_stock: 3,
            minimum_stock: 8
          },
          {
            item_id: 3,
            item_code: 'HDMI-001',
            name: 'HDMI Cable 2m',
            current_stock: 12,
            minimum_stock: 20
          },
          {
            item_id: 4,
            item_code: 'PAPER-A4',
            name: 'A4 Paper Ream',
            current_stock: 2,
            minimum_stock: 5
          }
        ];

        isLoadingTransactions.value = false;
        isLoadingLowStock.value = false;
      };

      onMounted(() => {
        // fetchDashboardData();
        loadDummyData();

        // For demo purposes, we're using dummy data
        loadDummyData();
      });

      return {
        stats,
        recentTransactions,
        lowStockItems,
        isLoadingTransactions,
        isLoadingLowStock,
        getTransactionTypeClass,
        formatDate
      };
    }
  };
  </script>

  <style scoped>
  .dashboard {
    display: flex;
    flex-direction: column;
    gap: 2rem;
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
  }

  .stat-card {
    background-color: white;
    border-radius: 0.5rem;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .stat-icon {
    font-size: 1.5rem;
    width: 3rem;
    height: 3rem;
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 0.5rem;
    background-color: #dbeafe;
    color: #2563eb;
    margin-right: 1rem;
  }

  .stat-icon.warning {
    background-color: #fef3c7;
    color: #d97706;
  }

  .stat-icon.success {
    background-color: #d1fae5;
    color: #059669;
  }

  .stat-title {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0 0 0.5rem 0;
  }

  .stat-value {
    font-size: 1.5rem;
    font-weight: 600;
    color: #0f172a;
    margin: 0;
  }

  .dashboard-widgets {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
  }

  .widget {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
  }

  .widget-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
  }

  .widget-title {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0;
    color: #1e293b;
  }

  .view-all {
    font-size: 0.875rem;
    color: #2563eb;
    text-decoration: none;
  }

  .view-all:hover {
    text-decoration: underline;
  }

  .widget-content {
    padding: 1.5rem;
  }

  .data-table {
    width: 100%;
    border-collapse: collapse;
  }

  .data-table th {
    text-align: left;
    font-size: 0.875rem;
    color: #64748b;
    font-weight: 500;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e2e8f0;
  }

  .data-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
  }

  .transaction-type {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
  }

  .type-in {
    background-color: #d1fae5;
    color: #059669;
  }

  .type-out {
    background-color: #fee2e2;
    color: #dc2626;
  }

  .stock-status {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
  }

  .stock-status.low {
    background-color: #fee2e2;
    color: #dc2626;
  }

  .loading-indicator {
    display: flex;
    justify-content: center;
    align-items: center;
    color: #64748b;
    padding: 2rem 0;
  }

  .empty-state {
    display: flex;
    justify-content: center;
    align-items: center;
    color: #64748b;
    padding: 3rem 0;
    font-style: italic;
  }

    @media (max-width: 1024px) {
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      .dashboard-widgets {
        grid-template-columns: 1fr;
      }
    }
  </style>
