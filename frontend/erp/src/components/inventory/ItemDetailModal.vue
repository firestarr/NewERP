<!-- src/components/inventory/ItemDetailModal.vue -->
<template>
  <div class="modal-backdrop" @click="$emit('close')">
    <div class="modal-dialog modal-xl" @click.stop>
      <div class="modal-content">
        <!-- Modal Header -->
        <div class="modal-header">
          <h4 class="modal-title">
            <i class="fas fa-cube me-2"></i>
            Item Details
          </h4>
          <button type="button" class="btn-close" @click="$emit('close')">
            <span>&times;</span>
          </button>
        </div>

        <!-- Modal Body - Loading State -->
        <div class="modal-body" v-if="isLoading">
          <div class="text-center py-4">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Loading item details...</p>
          </div>
        </div>

        <!-- Modal Body - Error State -->
        <div class="modal-body" v-else-if="errorMessage">
          <div class="text-center py-4">
            <div class="alert alert-danger">
              <i class="fas fa-exclamation-triangle me-2"></i>
              {{ errorMessage }}
            </div>
            <button class="btn btn-primary" @click="retryFetch" v-if="retryCount < maxRetries">
              <i class="fas fa-redo"></i> Retry ({{ retryCount }}/{{ maxRetries }})
            </button>
          </div>
        </div>

        <!-- Modal Body - Content -->
        <div class="modal-body" v-else-if="itemDetail">
          <!-- Action Buttons -->
          <div class="action-bar mb-4">
            <div class="d-flex justify-content-between align-items-center">
              <div class="item-title">
                <h5 class="mb-0">{{ itemDetail.item_code }} - {{ itemDetail.name }}</h5>
                <small class="text-muted">{{ itemDetail.category?.name || 'No Category' }}</small>
              </div>
              <div class="action-buttons">
                <button class="btn btn-secondary btn-sm me-2" @click="$emit('edit', itemDetail)">
                  <i class="fas fa-edit"></i> Edit
                </button>
                <button class="btn btn-info btn-sm me-2" @click="fetchPricesInCurrencies" :disabled="isLoadingCurrencies">
                  <i class="fas fa-dollar-sign"></i>
                  <span v-if="isLoadingCurrencies">Loading...</span>
                  <span v-else>Multi-Currency</span>
                </button>
                <button class="btn btn-outline-primary btn-sm" @click="downloadDocument" v-if="itemDetail.document_path">
                  <i class="fas fa-file-download"></i> Document
                </button>
              </div>
            </div>
          </div>

          <!-- Tab Navigation -->
          <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
              <button class="nav-link" :class="{ active: activeTab === 'basic' }" @click="activeTab = 'basic'">
                <i class="fas fa-info-circle me-1"></i> Basic Info
              </button>
            </li>
            <li class="nav-item">
              <button class="nav-link" :class="{ active: activeTab === 'stock' }" @click="activeTab = 'stock'">
                <i class="fas fa-boxes me-1"></i> Stock & Pricing
              </button>
            </li>
            <li class="nav-item" v-if="bomComponents && bomComponents.length > 0">
              <button class="nav-link" :class="{ active: activeTab === 'bom' }" @click="activeTab = 'bom'">
                <i class="fas fa-list me-1"></i> BOM Components ({{ bomComponents.length }})
              </button>
            </li>
            <li class="nav-item">
              <button class="nav-link position-relative" :class="{ active: activeTab === 'usage' }" @click="loadUsageTab">
                <i class="fas fa-arrow-up me-1"></i> Where Used
                <span v-if="usedInFinishedGoods.length > 0" class="badge badge-primary position-absolute top-0 start-100 translate-middle">
                  {{ usedInFinishedGoods.length }}
                </span>
              </button>
            </li>
            <li class="nav-item">
              <button class="nav-link" :class="{ active: activeTab === 'transactions' }" @click="loadTransactionsTab">
                <i class="fas fa-history me-1"></i> Transactions
              </button>
            </li>
          </ul>

          <!-- Tab Content -->
          <div class="tab-content">
            <!-- Basic Information Tab -->
            <div v-show="activeTab === 'basic'" class="tab-pane">
              <!-- Basic Information -->
              <div class="section-group">
                <h6 class="section-title">Basic Information</h6>
                <div class="row g-3">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Item Code</label>
                      <div class="form-value">{{ itemDetail.item_code }}</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Name</label>
                      <div class="form-value">{{ itemDetail.name }}</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Category</label>
                      <div class="form-value">{{ itemDetail.category?.name || '-' }}</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Unit of Measure</label>
                      <div class="form-value">{{ itemDetail.unitOfMeasure?.name || '-' }}</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">HS Code</label>
                      <div class="form-value">{{ itemDetail.hs_code || '-' }}</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Type</label>
                      <div class="form-value">
                        <span class="badge badge-secondary me-1" v-if="itemDetail.is_purchasable">Purchasable</span>
                        <span class="badge badge-secondary" v-if="itemDetail.is_sellable">Sellable</span>
                        <span v-if="!itemDetail.is_purchasable && !itemDetail.is_sellable">-</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Description -->
              <div class="section-group" v-if="itemDetail.description">
                <h6 class="section-title">Description</h6>
                <div class="description-box">{{ itemDetail.description }}</div>
              </div>

              <!-- Physical Properties -->
              <div class="section-group">
                <h6 class="section-title">Physical Properties</h6>
                <div class="row g-3">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Length</label>
                      <div class="form-value">{{ itemDetail.length || '-' }}</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Width</label>
                      <div class="form-value">{{ itemDetail.width || '-' }}</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Thickness</label>
                      <div class="form-value">{{ itemDetail.thickness || '-' }}</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Weight</label>
                      <div class="form-value">{{ itemDetail.weight || '-' }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Stock & Pricing Tab -->
            <div v-show="activeTab === 'stock'" class="tab-pane">
              <!-- Stock Information -->
              <div class="section-group">
                <h6 class="section-title">Stock Information</h6>
                <div class="row g-3">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Current Stock</label>
                      <div class="form-value">
                        {{ itemDetail.current_stock || 0 }}
                        <span class="badge ms-2" :class="getStockStatusClass(itemDetail)">
                          {{ getStockStatus(itemDetail) }}
                        </span>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Available Stock</label>
                      <div class="form-value">{{ itemDetail.available_stock || 0 }}</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Minimum Stock</label>
                      <div class="form-value">{{ itemDetail.minimum_stock || 0 }}</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Maximum Stock</label>
                      <div class="form-value">{{ itemDetail.maximum_stock || 0 }}</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Pricing Information -->
              <div class="section-group">
                <h6 class="section-title">Pricing Information</h6>
                <div class="row g-3">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Cost Price</label>
                      <div class="form-value">
                        {{ itemDetail.cost_price_currency || 'USD' }} {{ formatCurrency(itemDetail.cost_price) }}
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Sale Price</label>
                      <div class="form-value">
                        {{ itemDetail.sale_price_currency || 'USD' }} {{ formatCurrency(itemDetail.sale_price) }}
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Multi-Currency Prices -->
                <div v-if="showMultiCurrencyPrices" class="mt-3">
                  <h6 class="section-title">Multi-Currency Prices</h6>
                  <div v-if="isLoadingCurrencies" class="text-center">
                    <i class="fas fa-spinner fa-spin"></i> Loading prices...
                  </div>
                  <div v-else-if="multiCurrencyPrices" class="row g-2">
                    <div v-for="(price, currency) in multiCurrencyPrices.prices" :key="currency" class="col-md-4">
                      <div class="currency-card">
                        <div class="currency-code">{{ currency }}</div>
                        <div class="currency-prices">
                          <small>Purchase: {{ formatCurrency(price.purchase_price) }}</small><br>
                          <small>Sale: {{ formatCurrency(price.sale_price) }}</small>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- BOM Components Tab -->
            <div v-show="activeTab === 'bom'" class="tab-pane">
              <div class="section-group">
                <h6 class="section-title">BOM Components ({{ bomComponents.length }})</h6>
                <div class="table-responsive">
                  <table class="table table-sm table-hover">
                    <thead>
                      <tr>
                        <th>Component Code</th>
                        <th>Component Name</th>
                        <th>Qty</th>
                        <th>UOM</th>
                        <th>Critical</th>
                        <th>Yield Based</th>
                        <th>Yield Details</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="component in bomComponents" :key="component.component_id">
                        <td>
                          <strong>{{ component.component_code }}</strong>
                        </td>
                        <td>{{ component.component_name }}</td>
                        <td>{{ component.quantity }}</td>
                        <td>{{ component.uom || '-' }}</td>
                        <td>
                          <span class="badge" :class="component.is_critical ? 'badge-warning' : 'badge-secondary'">
                            {{ component.is_critical ? 'Yes' : 'No' }}
                          </span>
                        </td>
                        <td>
                          <span class="badge" :class="component.yield_based ? 'badge-info' : 'badge-secondary'">
                            {{ component.yield_based ? 'Yes' : 'No' }}
                          </span>
                        </td>
                        <td>
                          <div v-if="component.yield_based">
                            <small>
                              <strong>Ratio:</strong> {{ component.yield_ratio || '-' }}<br>
                              <strong>Shrinkage:</strong> {{ component.shrinkage_factor || '-' }}
                            </small>
                          </div>
                          <span v-else class="text-muted">-</span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- Where Used Tab -->
            <div v-show="activeTab === 'usage'" class="tab-pane">
              <div class="section-group">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h6 class="section-title mb-0">
                    <i class="fas fa-arrow-up me-2"></i>
                    Used in Finished Goods
                    <span v-if="usedInFinishedGoods.length > 0" class="badge badge-primary ms-2">
                      {{ usedInFinishedGoods.length }}
                    </span>
                  </h6>
                  <div class="d-flex gap-2">
                    <input
                      type="text"
                      class="form-control form-control-sm"
                      placeholder="Search finished goods..."
                      v-model="searchUsedIn"
                      style="width: 200px;"
                    >
                    <button
                      class="btn btn-sm btn-outline-primary"
                      @click="exportUsedInData()"
                      title="Export to Excel"
                      :disabled="usedInFinishedGoods.length === 0"
                    >
                      <i class="fas fa-download"></i>
                    </button>
                  </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-3" v-if="usedInFinishedGoods.length > 0">
                  <div class="col-md-3">
                    <div class="card border-primary">
                      <div class="card-body text-center py-2">
                        <small class="text-muted">Total Usage</small>
                        <div class="h6 mb-0">{{ usedInFinishedGoods.length }}</div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="card border-warning">
                      <div class="card-body text-center py-2">
                        <small class="text-muted">Critical Usage</small>
                        <div class="h6 mb-0">{{ usedInFinishedGoods.filter(u => u.usage_details.is_critical).length }}</div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="card border-info">
                      <div class="card-body text-center py-2">
                        <small class="text-muted">Yield Based</small>
                        <div class="h6 mb-0">{{ usedInFinishedGoods.filter(u => u.usage_details.is_yield_based).length }}</div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="card border-success">
                      <div class="card-body text-center py-2">
                        <small class="text-muted">Active BOMs</small>
                        <div class="h6 mb-0">{{ usedInFinishedGoods.filter(u => u.bom_details.status === 'active').length }}</div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Loading State -->
                <div v-if="isLoadingUsedIn" class="text-center py-4">
                  <i class="fas fa-spinner fa-spin"></i> Loading usage information...
                </div>

                <!-- Usage Table -->
                <div v-else-if="usedInFinishedGoods.length > 0" class="table-responsive">
                  <table class="table table-sm table-hover">
                    <thead>
                      <tr>
                        <th @click="sortUsedIn('item_code')" style="cursor: pointer;">
                          Finished Good Code <i class="fas fa-sort"></i>
                        </th>
                        <th>Finished Good Name</th>
                        <th @click="sortUsedIn('bom_code')" style="cursor: pointer;">
                          BOM Code <i class="fas fa-sort"></i>
                        </th>
                        <th @click="sortUsedIn('quantity')" style="cursor: pointer;">
                          Usage Qty <i class="fas fa-sort"></i>
                        </th>
                        <th>UOM</th>
                        <th>Per Unit</th>
                        <th>Critical</th>
                        <th>Yield Details</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="usage in filteredUsedIn" :key="`${usage.bom_id}-${usage.finished_good.item_id}`">
                        <td>
                          <strong>{{ usage.finished_good.item_code }}</strong>
                        </td>
                        <td>
                          {{ usage.finished_good.item_name }}
                          <br>
                          <small class="text-muted" v-if="usage.finished_good.category">
                            {{ usage.finished_good.category }}
                          </small>
                        </td>
                        <td>
                          {{ usage.bom_code }}
                          <br>
                          <small class="text-muted">Rev: {{ usage.bom_revision }}</small>
                        </td>
                        <td>
                          <strong>{{ usage.usage_details.quantity }}</strong>
                        </td>
                        <td>{{ usage.usage_details.uom || '-' }}</td>
                        <td>
                          <span v-if="usage.usage_details.quantity_per_unit" class="badge badge-info">
                            {{ formatQuantity(usage.usage_details.quantity_per_unit) }}
                          </span>
                          <span v-else>-</span>
                        </td>
                        <td>
                          <span class="badge" :class="usage.usage_details.is_critical ? 'badge-warning' : 'badge-secondary'">
                            {{ usage.usage_details.is_critical ? 'Yes' : 'No' }}
                          </span>
                        </td>
                        <td>
                          <div v-if="usage.usage_details.is_yield_based">
                            <small>
                              <strong>Yield:</strong> {{ usage.usage_details.yield_ratio || '-' }}<br>
                              <strong>Shrinkage:</strong> {{ usage.usage_details.shrinkage_factor || '-' }}
                            </small>
                          </div>
                          <span v-else class="text-muted">Standard</span>
                        </td>
                        <td>
                          <div class="btn-group btn-group-sm">
                            <button
                              class="btn btn-outline-primary btn-sm"
                              @click="viewFinishedGood(usage.finished_good)"
                              title="View Finished Good"
                            >
                              <i class="fas fa-eye"></i>
                            </button>
                            <button
                              class="btn btn-outline-secondary btn-sm"
                              @click="viewBOM(usage.bom_id)"
                              title="View BOM"
                            >
                              <i class="fas fa-list"></i>
                            </button>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-4">
                  <div class="empty-state">
                    <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                    <h6>No Usage Found</h6>
                    <p class="text-muted">This material is not used in any finished goods BOM.</p>
                  </div>
                </div>

                <!-- Summary Info -->
                <div class="row mt-3" v-if="usedInFinishedGoods.length > 0">
                  <div class="col-12">
                    <div class="alert alert-info">
                      <small>
                        <i class="fas fa-info-circle me-1"></i>
                        This material is used in <strong>{{ usedInFinishedGoods.length }}</strong> finished good(s).
                        <span v-if="usedInFinishedGoods.some(u => u.usage_details.is_critical)">
                          Some usages are marked as <span class="badge badge-warning">Critical</span>.
                        </span>
                      </small>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Transactions Tab -->
            <div v-show="activeTab === 'transactions'" class="tab-pane">
              <div class="section-group">
                <h6 class="section-title">
                  Recent Transactions
                  <button class="btn btn-outline-primary btn-sm float-end" @click="fetchTransactions">
                    <i class="fas fa-sync-alt"></i> Refresh
                  </button>
                </h6>

                <div v-if="isLoadingTransactions" class="text-center py-4">
                  <i class="fas fa-spinner fa-spin"></i> Loading transactions...
                </div>

                <div v-else-if="transactions && transactions.length > 0" class="table-responsive">
                  <table class="table table-sm table-hover">
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Reference</th>
                        <th>Warehouse</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="transaction in transactions.slice(0, 10)" :key="transaction.transaction_id || transaction.id">
                        <td>{{ formatDate(transaction.transaction_date || transaction.created_at) }}</td>
                        <td>
                          <span class="badge" :class="getTransactionTypeClass(transaction.transaction_type)">
                            {{ transaction.transaction_type }}
                          </span>
                        </td>
                        <td :class="getQuantityClass(transaction.quantity)">
                          {{ transaction.quantity > 0 ? '+' : '' }}{{ transaction.quantity }}
                        </td>
                        <td>{{ transaction.reference_number || transaction.reference || '-' }}</td>
                        <td>{{ transaction.warehouse?.name || transaction.warehouse_name || '-' }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <div v-else class="text-center py-4">
                  <div class="empty-state">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h6>No Transactions</h6>
                    <p class="text-muted">No recent transactions found for this item.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="$emit('close')">
            <i class="fas fa-times"></i> Close
          </button>
          <button type="button" class="btn btn-primary" @click="$emit('edit', itemDetail)">
            <i class="fas fa-edit"></i> Edit Item
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import ItemService from '@/services/ItemService.js';

export default {
  name: 'ItemDetailModal',
  emits: ['close', 'edit'],
  props: {
    item: {
      type: Object,
      required: true
    }
  },
  setup(props, { emit }) {
    // Core data
    const itemDetail = ref(null);
    const transactions = ref([]);
    const bomComponents = ref([]);

    // Where Used data
    const usedInFinishedGoods = ref([]);
    const searchUsedIn = ref('');

    // Loading states
    const isLoading = ref(true);
    const isLoadingTransactions = ref(false);
    const isLoadingUsedIn = ref(false);
    const isLoadingCurrencies = ref(false);

    // UI state
    const activeTab = ref('basic');
    const showMultiCurrencyPrices = ref(false);
    const multiCurrencyPrices = ref(null);

    // Error handling
    const errorMessage = ref('');
    const retryCount = ref(0);
    const maxRetries = ref(3);

    // Computed properties
    const filteredUsedIn = computed(() => {
      if (!searchUsedIn.value) return usedInFinishedGoods.value;

      return usedInFinishedGoods.value.filter(usage =>
        usage.finished_good.item_code.toLowerCase().includes(searchUsedIn.value.toLowerCase()) ||
        usage.finished_good.item_name.toLowerCase().includes(searchUsedIn.value.toLowerCase()) ||
        usage.bom_code.toLowerCase().includes(searchUsedIn.value.toLowerCase())
      );
    });

    // Utility methods
    const formatDate = (dateString) => {
      if (!dateString) return '-';
      return new Date(dateString).toLocaleDateString();
    };

    const formatQuantity = (quantity) => {
      if (quantity === null || quantity === undefined) return '-';
      return parseFloat(quantity).toFixed(4);
    };

    const formatCurrency = (amount) => {
      if (amount === null || amount === undefined) return '0.00';
      return parseFloat(amount).toFixed(2);
    };

    const getStockStatus = (item) => {
      if (!item) return 'Unknown';
      const current = item.current_stock || 0;
      const min = item.minimum_stock || 0;
      const max = item.maximum_stock || 0;

      if (current <= min) return 'Low';
      if (max > 0 && current >= max) return 'Over';
      return 'Normal';
    };

    const getStockStatusClass = (item) => {
      const status = getStockStatus(item);
      return {
        'badge-danger': status === 'Low',
        'badge-warning': status === 'Over',
        'badge-success': status === 'Normal'
      };
    };

    const getTransactionTypeClass = (type) => {
      const typeMap = {
        'IN': 'badge-success',
        'OUT': 'badge-danger',
        'TRANSFER': 'badge-info',
        'ADJUSTMENT': 'badge-warning'
      };
      return typeMap[type] || 'badge-secondary';
    };

    const getQuantityClass = (quantity) => {
      if (quantity > 0) return 'text-success';
      if (quantity < 0) return 'text-danger';
      return '';
    };

    // Core fetch methods
    const fetchItemDetail = async () => {
      if (!props.item?.item_id) {
        errorMessage.value = 'Invalid item data';
        return;
      }

      isLoading.value = true;
      try {
        const response = await ItemService.getItemById(props.item.item_id);

        let itemData = null;
        if (response.data && response.data.data) {
          itemData = response.data.data;
        } else if (response.data) {
          itemData = response.data;
        } else {
          itemData = response;
        }

        itemDetail.value = itemData;

        // Set BOM components
        if (response.data && response.data.bom_components) {
          bomComponents.value = response.data.bom_components;
        } else if (response.bom_components) {
          bomComponents.value = response.bom_components;
        } else if (itemData.bom_components) {
          bomComponents.value = itemData.bom_components;
        } else {
          bomComponents.value = [];
        }

        errorMessage.value = '';
      } catch (error) {
        console.error('Error fetching item detail:', error);
        errorMessage.value = 'Failed to load item details';
      } finally {
        isLoading.value = false;
      }
    };

const fetchTransactions = async () => {
      if (!props.item?.item_id) return;

      isLoadingTransactions.value = true;
      try {
        const response = await ItemService.getItemTransactions(props.item.item_id, { limit: 10 });

        let rawTransactions = [];
        if (response && response.data && response.data.transactions && Array.isArray(response.data.transactions.data)) {
          rawTransactions = response.data.transactions.data;
        } else if (response && response.data && Array.isArray(response.data)) {
          rawTransactions = response.data;
        } else if (response && Array.isArray(response)) {
          rawTransactions = response;
        }

        transactions.value = rawTransactions;
      } catch (error) {
        console.error('Error fetching transactions:', error);
        transactions.value = [];
      } finally {
        isLoadingTransactions.value = false;
      }
    };

    const fetchUsedInFinishedGoods = async () => {
      if (!props.item?.item_id) return;

      isLoadingUsedIn.value = true;
      try {
        const response = await axios.get(`/items/${props.item.item_id}/used-in-finished-goods`);
        usedInFinishedGoods.value = response.data.data.used_in_finished_goods || [];
        console.log('📊 Used in finished goods loaded:', usedInFinishedGoods.value.length);
      } catch (error) {
        console.error('❌ Error fetching used in finished goods:', error);
        usedInFinishedGoods.value = [];
      } finally {
        isLoadingUsedIn.value = false;
      }
    };

    const fetchPricesInCurrencies = async () => {
      if (!props.item?.item_id || isLoadingCurrencies.value) return;

      isLoadingCurrencies.value = true;
      try {
        const response = await axios.get(`/items/${props.item.item_id}/prices-in-currencies`);
        multiCurrencyPrices.value = response.data;
        showMultiCurrencyPrices.value = true;
      } catch (error) {
        console.error('Error fetching multi-currency prices:', error);
        alert('Failed to load multi-currency prices');
      } finally {
        isLoadingCurrencies.value = false;
      }
    };

    // Tab methods
    const loadUsageTab = () => {
      activeTab.value = 'usage';
      if (usedInFinishedGoods.value.length === 0) {
        fetchUsedInFinishedGoods();
      }
    };

    const loadTransactionsTab = () => {
      activeTab.value = 'transactions';
      if (transactions.value.length === 0) {
        fetchTransactions();
      }
    };

    // Where Used methods
    const sortUsedIn = (column) => {
      usedInFinishedGoods.value.sort((a, b) => {
        switch(column) {
          case 'item_code':
            return a.finished_good.item_code.localeCompare(b.finished_good.item_code);
          case 'quantity':
            return a.usage_details.quantity - b.usage_details.quantity;
          case 'bom_code':
            return a.bom_code.localeCompare(b.bom_code);
          default:
            return 0;
        }
      });
    };

    const exportUsedInData = () => {
      const exportData = usedInFinishedGoods.value.map(usage => ({
        'Finished Good Code': usage.finished_good.item_code,
        'Finished Good Name': usage.finished_good.item_name,
        'BOM Code': usage.bom_code,
        'BOM Revision': usage.bom_revision,
        'Usage Quantity': usage.usage_details.quantity,
        'UOM': usage.usage_details.uom,
        'Quantity Per Unit': usage.usage_details.quantity_per_unit,
        'Is Critical': usage.usage_details.is_critical ? 'Yes' : 'No',
        'Is Yield Based': usage.usage_details.is_yield_based ? 'Yes' : 'No',
        'Yield Ratio': usage.usage_details.yield_ratio || '',
        'Shrinkage Factor': usage.usage_details.shrinkage_factor || '',
        'Notes': usage.usage_details.notes || ''
      }));

      console.log('Export data:', exportData);
      alert('Export functionality would be implemented here');
    };

    const viewFinishedGood = (finishedGood) => {
      // Close current modal and emit event to show the finished good
      emit('close');
      // You might want to emit a specific event for navigation
      setTimeout(() => {
        window.open(`/items/${finishedGood.item_id}`, '_blank');
      }, 100);
    };

    const viewBOM = (bomId) => {
      window.open(`/manufacturing/bom/${bomId}`, '_blank');
    };

    const downloadDocument = () => {
      if (itemDetail.value?.document_path) {
        window.open(itemDetail.value.document_path, '_blank');
      }
    };

    const retryFetch = () => {
      if (retryCount.value < maxRetries.value) {
        retryCount.value++;
        errorMessage.value = '';
        fetchItemDetail();
      }
    };

    // Watch for prop changes
    watch(() => props.item, (newItem) => {
      if (newItem?.item_id) {
        // Reset state
        itemDetail.value = null;
        transactions.value = [];
        bomComponents.value = [];
        usedInFinishedGoods.value = [];
        errorMessage.value = '';
        retryCount.value = 0;
        activeTab.value = 'basic';

        // Fetch fresh data
        fetchItemDetail();
      }
    }, { immediate: true });

    return {
      // Core data
      itemDetail,
      transactions,
      bomComponents,

      // Where Used
      usedInFinishedGoods,
      searchUsedIn,
      filteredUsedIn,

      // Loading states
      isLoading,
      isLoadingTransactions,
      isLoadingUsedIn,
      isLoadingCurrencies,

      // UI state
      activeTab,
      showMultiCurrencyPrices,
      multiCurrencyPrices,

      // Error handling
      errorMessage,
      retryCount,
      maxRetries,

      // Utility methods
      formatDate,
      formatQuantity,
      formatCurrency,
      getStockStatus,
      getStockStatusClass,
      getTransactionTypeClass,
      getQuantityClass,

      // Core methods
      fetchTransactions,
      fetchPricesInCurrencies,
      retryFetch,
      downloadDocument,

      // Tab methods
      loadUsageTab,
      loadTransactionsTab,

      // Where Used methods
      sortUsedIn,
      exportUsedInData,
      viewFinishedGood,
      viewBOM
    };
  }
};
</script>

<style scoped>
/* Modal Styles */
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
}

.modal-dialog {
  width: 95%;
  max-width: 1200px;
  max-height: 95vh;
  margin: auto;
}

.modal-xl {
  max-width: 1200px;
}

.modal-content {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.175);
  display: flex;
  flex-direction: column;
  max-height: 95vh;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #dee2e6;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 0.5rem 0.5rem 0 0;
}

.modal-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 500;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1;
  color: white;
  opacity: 0.8;
  cursor: pointer;
  padding: 0;
}

.btn-close:hover {
  opacity: 1;
}

.modal-body {
  flex: 1 1 auto;
  padding: 1.5rem;
  overflow-y: auto;
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding: 1rem 1.5rem;
  border-top: 1px solid #dee2e6;
  background-color: #f8f9fa;
  border-radius: 0 0 0.5rem 0.5rem;
}

/* Action Bar */
.action-bar {
  background-color: #f8f9fa;
  border-radius: 0.5rem;
  padding: 1rem;
  border: 1px solid #e9ecef;
}

.item-title h5 {
  color: #495057;
  font-weight: 600;
}

.action-buttons .btn {
  border-radius: 0.375rem;
  font-weight: 500;
}

/* Tabs */
.nav-tabs {
  border-bottom: 2px solid #dee2e6;
}

.nav-tabs .nav-link {
  border: none;
  background: none;
  color: #6c757d;
  padding: 0.75rem 1rem;
  cursor: pointer;
  border-radius: 0.5rem 0.5rem 0 0;
  margin-bottom: -2px;
  transition: all 0.3s ease;
}

.nav-tabs .nav-link:hover {
  background-color: #f8f9fa;
  color: #495057;
}

.nav-tabs .nav-link.active {
  background-color: #007bff;
  color: white;
  border-bottom: 2px solid #007bff;
}

.tab-content {
  min-height: 400px;
}

/* Section Groups */
.section-group {
  margin-bottom: 2rem;
  padding: 1.5rem;
  border: 1px solid #e9ecef;
  border-radius: 0.5rem;
  background-color: #ffffff;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.section-title {
  font-size: 1rem;
  font-weight: 600;
  color: #495057;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #e9ecef;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

/* Bootstrap Grid */
.row {
  display: flex;
  flex-wrap: wrap;
  margin-right: -0.75rem;
  margin-left: -0.75rem;
}

.col-md-6,
.col-md-4,
.col-md-3,
.col-12 {
  position: relative;
  width: 100%;
  padding-right: 0.75rem;
  padding-left: 0.75rem;
}

.col-md-6 {
  flex: 0 0 50%;
  max-width: 50%;
}

.col-md-4 {
  flex: 0 0 33.333333%;
  max-width: 33.333333%;
}

.col-md-3 {
  flex: 0 0 25%;
  max-width: 25%;
}

.col-12 {
  flex: 0 0 100%;
  max-width: 100%;
}

.g-3 > * {
  margin-bottom: 1rem;
}

.g-2 > * {
  margin-bottom: 0.5rem;
}

/* Form Elements */
.form-group {
  margin-bottom: 0;
}

.form-label {
  margin-bottom: 0.25rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #6c757d;
  display: block;
}

.form-value {
  font-size: 0.95rem;
  color: #495057;
  min-height: 1.5rem;
  line-height: 1.4;
  font-weight: 500;
}

.form-control {
  display: block;
  width: 100%;
  padding: 0.375rem 0.75rem;
  font-size: 1rem;
  font-weight: 400;
  line-height: 1.5;
  color: #212529;
  background-color: #fff;
  border: 1px solid #ced4da;
  border-radius: 0.375rem;
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control-sm {
  min-height: calc(1.5em + 0.5rem + 2px);
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
  border-radius: 0.25rem;
}

/* Description Box */
.description-box {
  background-color: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 0.375rem;
  padding: 1rem;
  font-size: 0.95rem;
  color: #495057;
  line-height: 1.5;
  min-height: 4rem;
}

/* Currency Cards */
.currency-card {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border: 1px solid #dee2e6;
  border-radius: 0.375rem;
  padding: 0.75rem;
  text-align: center;
  transition: transform 0.2s ease;
}

.currency-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
}

.currency-code {
  font-weight: 600;
  font-size: 0.875rem;
  color: #495057;
  margin-bottom: 0.5rem;
}

.currency-prices small {
  font-size: 0.75rem;
  color: #6c757d;
}

/* Statistics Cards */
.card {
  background-color: #fff;
  border: 1px solid rgba(0, 0, 0, 0.125);
  border-radius: 0.5rem;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
  transform: translateY(-2px);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.card-body {
  padding: 1rem;
}

.border-primary {
  border-color: #007bff !important;
}

.border-warning {
  border-color: #ffc107 !important;
}

.border-info {
  border-color: #17a2b8 !important;
}

.border-success {
  border-color: #28a745 !important;
}

/* Badges */
.badge {
  display: inline-block;
  padding: 0.375em 0.75em;
  font-size: 0.75em;
  font-weight: 700;
  line-height: 1;
  color: #fff;
  text-align: center;
  white-space: nowrap;
  vertical-align: baseline;
  border-radius: 0.375rem;
}

.badge-primary {
  background-color: #007bff;
}

.badge-secondary {
  background-color: #6c757d;
}

.badge-success {
  background-color: #28a745;
}

.badge-danger {
  background-color: #dc3545;
}

.badge-warning {
  background-color: #ffc107;
  color: #212529;
}

.badge-info {
  background-color: #17a2b8;
}

/* Buttons */
.btn {
  display: inline-block;
  font-weight: 400;
  line-height: 1.5;
  color: #212529;
  text-align: center;
  text-decoration: none;
  vertical-align: middle;
  cursor: pointer;
  user-select: none;
  background-color: transparent;
  border: 1px solid transparent;
  padding: 0.375rem 0.75rem;
  font-size: 0.875rem;
  border-radius: 0.375rem;
  transition: all 0.15s ease-in-out;
}

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.8125rem;
  border-radius: 0.25rem;
}

.btn-primary {
  color: #fff;
  background-color: #007bff;
  border-color: #007bff;
}

.btn-primary:hover {
  background-color: #0056b3;
  border-color: #004085;
}

.btn-secondary {
  color: #fff;
  background-color: #6c757d;
  border-color: #6c757d;
}

.btn-secondary:hover {
  background-color: #545b62;
  border-color: #4e555b;
}

.btn-info {
  color: #fff;
  background-color: #17a2b8;
  border-color: #17a2b8;
}

.btn-info:hover {
  background-color: #117a8b;
  border-color: #10707f;
}

.btn-outline-primary {
  color: #007bff;
  border-color: #007bff;
}

.btn-outline-primary:hover {
  color: #fff;
  background-color: #007bff;
  border-color: #007bff;
}

.btn-outline-secondary {
  color: #6c757d;
  border-color: #6c757d;
}

.btn-outline-secondary:hover {
  color: #fff;
  background-color: #6c757d;
  border-color: #6c757d;
}

.btn-group {
  position: relative;
  display: inline-flex;
  vertical-align: middle;
}

.btn-group-sm > .btn {
  padding: 0.25rem 0.5rem;
  font-size: 0.8125rem;
}

/* Tables */
.table {
  width: 100%;
  margin-bottom: 1rem;
  color: #212529;
  border-collapse: collapse;
}

.table th,
.table td {
  padding: 0.75rem;
  vertical-align: top;
  border-top: 1px solid #dee2e6;
}

.table thead th {
  vertical-align: bottom;
  border-bottom: 2px solid #dee2e6;
  font-weight: 600;
  color: #495057;
  background-color: #f8f9fa;
}

.table-sm th,
.table-sm td {
  padding: 0.5rem;
}

.table-hover tbody tr:hover {
  background-color: rgba(0, 0, 0, 0.075);
}

.table-responsive {
  display: block;
  width: 100%;
  overflow-x: auto;
}

/* Alerts */
.alert {
  position: relative;
  padding: 0.75rem 1.25rem;
  margin-bottom: 1rem;
  border: 1px solid transparent;
  border-radius: 0.375rem;
}

.alert-info {
  color: #0c5460;
  background-color: #d1ecf1;
  border-color: #bee5eb;
}

.alert-danger {
  color: #721c24;
  background-color: #f8d7da;
  border-color: #f5c6cb;
}

/* Empty State */
.empty-state {
  padding: 2rem;
  text-align: center;
  color: #6c757d;
}

.empty-state h6 {
  margin-bottom: 0.5rem;
  color: #495057;
}

.empty-state p {
  margin-bottom: 0;
  font-size: 0.875rem;
}

/* Utility Classes */
.text-center {
  text-align: center;
}

.text-muted {
  color: #6c757d;
}

.text-success {
  color: #28a745;
}

.text-danger {
  color: #dc3545;
}

.py-4 {
  padding-top: 1.5rem;
  padding-bottom: 1.5rem;
}

.py-2 {
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
}

.mb-3 {
  margin-bottom: 1rem;
}

.mb-0 {
  margin-bottom: 0;
}

.mt-2 {
  margin-top: 0.5rem;
}

.mt-3 {
  margin-top: 1rem;
}

.me-1 {
  margin-right: 0.25rem;
}

.me-2 {
  margin-right: 0.5rem;
}

.ms-2 {
  margin-left: 0.5rem;
}

.float-end {
  float: right;
}

.h6 {
  font-size: 1rem;
  font-weight: 500;
  line-height: 1.2;
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

.gap-2 > * {
  margin-right: 0.5rem;
}

.gap-2 > *:last-child {
  margin-right: 0;
}

.position-relative {
  position: relative;
}

.position-absolute {
  position: absolute;
}

.top-0 {
  top: 0;
}

.start-100 {
  left: 100%;
}

.translate-middle {
  transform: translate(-50%, -50%);
}

/* Animations */
.fa-spin {
  animation: fa-spin 2s infinite linear;
}

@keyframes fa-spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  .modal-dialog {
    width: 98%;
    margin: 1rem auto;
  }

  .col-md-6,
  .col-md-4,
  .col-md-3 {
    flex: 0 0 100%;
    max-width: 100%;
  }

  .modal-body {
    padding: 1rem;
  }

  .modal-header,
  .modal-footer {
    padding: 0.75rem 1rem;
  }

  .action-bar {
    padding: 0.75rem;
  }

  .action-buttons {
    flex-direction: column;
    gap: 0.5rem;
  }

  .nav-tabs .nav-link {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
  }

  .section-group {
    padding: 1rem;
  }

  .table-responsive {
    font-size: 0.875rem;
  }

  .btn-group {
    flex-direction: column;
  }

  .btn-group .btn {
    margin-bottom: 0.25rem;
  }
}

@media (max-width: 576px) {
  .modal-dialog {
    width: 100%;
    height: 100%;
    max-height: 100vh;
    margin: 0;
  }

  .modal-content {
    border-radius: 0;
    height: 100vh;
  }

  .currency-card {
    margin-bottom: 0.5rem;
  }

  .card {
    margin-bottom: 0.5rem;
  }
}
</style>
