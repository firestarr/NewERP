<!-- src/components/dashboard/StockStatusWidget.vue -->
<template>
    <div class="stock-status-widget">
      <div class="widget-header">
        <h2 class="widget-title">Inventory Status</h2>
        <router-link to="/items?filter=low_stock" class="widget-action">
          View All
        </router-link>
      </div>

      <div class="widget-body">
        <div v-if="isLoading" class="loading-indicator">
          <i class="fas fa-spinner fa-spin"></i> Loading...
        </div>

        <div v-else-if="stockItems.length === 0" class="empty-state">
          <p>No items found.</p>
        </div>

            <div v-else class="stock-summary">
            <div class="stock-stat">
                <div class="stat-icon normal">
                <i class="fas fa-box"></i>
                </div>
                <div class="stat-info">
                <div class="stat-label">Total Items</div>
                <div class="stat-value">{{ totalItems }}</div>
                </div>
            </div>

          <div class="stock-stat">
            <div class="stat-icon warning">
              <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-info">
              <div class="stat-label">Low Stock</div>
              <div class="stat-value">{{ lowStockCount }}</div>
            </div>
          </div>

          <div class="stock-stat">
            <div class="stat-icon danger">
              <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-info">
              <div class="stat-label">Out of Stock</div>
              <div class="stat-value">{{ outOfStockCount }}</div>
            </div>
          </div>

          <div class="stock-stat">
            <div class="stat-icon success">
              <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
              <div class="stat-label">Normal</div>
              <div class="stat-value">{{ normalStockCount }}</div>
            </div>
          </div>
        </div>

        <div class="low-stock-list" v-if="lowStockItems.length > 0">
          <h3 class="list-title">Low Stock Items</h3>
          <div class="list-container">
            <router-link
              v-for="item in lowStockItems"
              :key="item.item_id"
              :to="`/items/${item.item_id}`"
              class="low-stock-item"
            >
              <div class="item-info">
                <div class="item-name">{{ item.name }}</div>
                <div class="item-code">{{ item.item_code }}</div>
              </div>
              <div class="stock-info">
                <div class="current-stock">
                  {{ item.current_stock }}
                </div>
                <div class="minimum-stock">
                  Min: {{ item.minimum_stock }}
                </div>
              </div>
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script>
  import { ref, computed, onMounted } from 'vue';
  import ItemService from '@/services/item.service';

  export default {
    name: 'StockStatusWidget',
    props: {
      limitItems: {
        type: Number,
        default: 5
      }
    },
    setup(props) {
      const stockItems = ref([]);
      const isLoading = ref(true);

      const fetchStockStatus = async () => {
        isLoading.value = true;
        try {
          const response = await ItemService.getStockStatus();
          stockItems.value = response.data || [];
        } catch (error) {
          console.error('Error fetching stock status:', error);
          stockItems.value = [];
        } finally {
          isLoading.value = false;
        }
      };

      const totalItems = computed(() => stockItems.value.length);

      const lowStockItems = computed(() => {
        return stockItems.value
          .filter(item => item.current_stock <= item.minimum_stock && item.current_stock > 0)
          .slice(0, props.limitItems);
      });

      const lowStockCount = computed(() => {
        return stockItems.value.filter(item =>
          item.current_stock <= item.minimum_stock && item.current_stock > 0
        ).length;
      });

      const outOfStockCount = computed(() => {
        return stockItems.value.filter(item => item.current_stock <= 0).length;
      });

      const normalStockCount = computed(() => {
        return stockItems.value.filter(item =>
          item.current_stock > item.minimum_stock &&
          item.current_stock < item.maximum_stock
        ).length;
      });

      onMounted(() => {
        fetchStockStatus();
      });

      return {
        stockItems,
        isLoading,
        totalItems,
        lowStockItems,
        lowStockCount,
        outOfStockCount,
        normalStockCount
      };
    }
  };
  </script>

  <style scoped>
  .stock-status-widget {
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
    background-color: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
  }

  .widget-title {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0;
    color: #1e293b;
  }

  .widget-action {
    font-size: 0.875rem;
    color: #2563eb;
    text-decoration: none;
  }

  .widget-action:hover {
    text-decoration: underline;
  }

  .widget-body {
    padding: 1.5rem;
  }

  .stock-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
  }

  .stock-stat {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    border-radius: 0.375rem;
    background-color: #f8fafc;
  }

  .stat-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 9999px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
  }

  .stat-icon.normal {
    background-color: #e0f2fe;
    color: #0284c7;
  }

  .stat-icon.warning {
    background-color: #fef3c7;
    color: #d97706;
  }

  .stat-icon.danger {
    background-color: #fee2e2;
    color: #dc2626;
  }

  .stat-icon.success {
    background-color: #d1fae5;
    color: #059669;
  }

  .stat-label {
    font-size: 0.75rem;
    color: #64748b;
  }

  .stat-value {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
  }

  .low-stock-list {
    margin-top: 1.5rem;
  }

  .list-title {
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 1rem 0;
    color: #1e293b;
  }

  .list-container {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .low-stock-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    border-radius: 0.375rem;
    background-color: #f8fafc;
    text-decoration: none;
    transition: background-color 0.2s;
  }

  .low-stock-item:hover {
    background-color: #f1f5f9;
  }

  .item-info {
    display: flex;
    flex-direction: column;
  }

  .item-name {
    font-size: 0.875rem;
    font-weight: 500;
    color: #1e293b;
  }

  .item-code {
    font-size: 0.75rem;
    color: #64748b;
  }

  .stock-info {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
  }

  .current-stock {
    font-size: 1rem;
    font-weight: 600;
    color: #dc2626;
  }

  .minimum-stock {
    font-size: 0.75rem;
    color: #64748b;
  }

  .loading-indicator {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 2rem 0;
    color: #64748b;
  }

  .loading-indicator i {
    margin-right: 0.5rem;
  }

  .empty-state {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 2rem 0;
    color: #64748b;
    font-style: italic;
  }

  @media (max-width: 768px) {
    .stock-summary {
      grid-template-columns: repeat(2, 1fr);
    }
  }
  </style>
