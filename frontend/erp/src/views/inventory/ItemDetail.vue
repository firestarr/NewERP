<!-- src/views/inventory/ItemDetail.vue - Complete Implementation -->
<template>
  <div class="modal-backdrop" @click="$router.back()">
    <div class="modal-dialog" @click.stop>
      <div class="modal-content">
        <!-- Modal Header -->
        <div class="modal-header">
          <h4 class="modal-title">Item Details</h4>
          <button type="button" class="btn-close" @click="$router.back()">
            <span>&times;</span>
          </button>
        </div>

        <!-- Modal Body - Loading State -->
        <div class="modal-body" v-if="isLoading">
          <div class="text-center py-4">
            <i class="fas fa-spinner fa-spin"></i> Loading item details...
          </div>
        </div>

        <!-- Modal Body - Error State -->
        <div class="modal-body" v-else-if="!item || errorMessage">
          <div class="text-center py-4">
            <div class="alert alert-danger" v-if="errorMessage">
              <i class="fas fa-exclamation-triangle me-2"></i>
              {{ errorMessage }}
            </div>
            <p v-else>Item not found</p>
            <button class="btn btn-primary" @click="retryFetch" v-if="retryCount < maxRetries">
              <i class="fas fa-redo"></i> Retry ({{ retryCount }}/{{ maxRetries }})
            </button>
            <button class="btn btn-secondary" @click="$router.back()">
              <i class="fas fa-arrow-left"></i> Go Back
            </button>
          </div>
        </div>

        <!-- Modal Body - Content -->
        <div class="modal-body" v-else>
          <!-- Action Buttons -->
          <div class="mb-3">
            <button class="btn btn-secondary btn-sm me-2" @click="openEditModal">
              <i class="fas fa-edit"></i> Edit
            </button>
            <button class="btn btn-danger btn-sm me-2" @click="confirmDelete" v-if="canDelete">
              <i class="fas fa-trash"></i> Delete
            </button>
            <button class="btn btn-info btn-sm me-2" @click="fetchPricesInCurrencies" :disabled="isLoadingCurrencies">
              <i class="fas fa-dollar-sign"></i>
              <span v-if="isLoadingCurrencies">Loading...</span>
              <span v-else>Multi-Currency Prices</span>
            </button>
            <button class="btn btn-outline-primary btn-sm" @click="openDocument" v-if="item.document_path">
              <i class="fas fa-file-download"></i> Download Document
            </button>
          </div>

          <!-- Debug Mode Toggle -->
          <div class="mb-3" v-if="debugMode">
            <div class="alert alert-warning">
              <strong>Debug Mode:</strong>
              <pre>{{ JSON.stringify(item, null, 2) }}</pre>
            </div>
          </div>

          <!-- Basic Information -->
          <div class="section-group">
            <h6 class="section-title">Basic Information</h6>
            <div class="row g-3">
              <div class="col-6">
                <div class="form-group">
                  <label class="form-label">Item Code</label>
                  <div class="form-value">{{ item.item_code }}</div>
                </div>
              </div>
              <div class="col-6">
                <div class="form-group">
                  <label class="form-label">Name</label>
                  <div class="form-value">{{ item.name }}</div>
                </div>
              </div>
              <div class="col-6">
                <div class="form-group">
                  <label class="form-label">Category</label>
                  <div class="form-value">{{ item.category ? item.category.name : '-' }}</div>
                </div>
              </div>
              <div class="col-6">
                <div class="form-group">
                  <label class="form-label">Unit of Measure</label>
                  <div class="form-value">{{ item.unitOfMeasure ? item.unitOfMeasure.name : '-' }}</div>
                </div>
              </div>
              <div class="col-6">
                <div class="form-group">
                  <label class="form-label">HS Code</label>
                  <div class="form-value">{{ item.hs_code || '-' }}</div>
                </div>
              </div>
              <div class="col-6">
                <div class="form-group">
                  <label class="form-label">Type</label>
                  <div class="form-value">
                    <span class="badge badge-secondary me-1" v-if="item.is_purchasable">Purchasable</span>
                    <span class="badge badge-secondary" v-if="item.is_sellable">Sellable</span>
                    <span v-if="!item.is_purchasable && !item.is_sellable">-</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Description -->
          <div class="section-group" v-if="item.description">
            <h6 class="section-title">Description</h6>
            <div class="description-box">{{ item.description }}</div>
          </div>

          <!-- Physical Properties -->
          <div class="section-group">
            <h6 class="section-title">Physical Properties</h6>
            <div class="row g-3">
              <div class="col-6">
                <div class="form-group">
                  <label class="form-label">Length</label>
                  <div class="form-value">{{ item.length || '-' }}</div>
                </div>
              </div>
              <div class="col-6">
                <div class="form-group">
                  <label class="form-label">Width</label>
                  <div class="form-value">{{ item.width || '-' }}</div>
                </div>
              </div>
              <div class="col-6">
                <div class="form-group">
                  <label class="form-label">Thickness</label>
                  <div class="form-value">{{ item.thickness || '-' }}</div>
                </div>
              </div>
              <div class="col-6">
                <div class="form-group">
                  <label class="form-label">Weight</label>
                  <div class="form-value">{{ item.weight || '-' }}</div>
                </div>
              </div>
            <div class="col-6">
            <div class="form-group">
                <label class="form-label">Tape Mat PCC</label>
                <div class="form-value">
                <span v-if="item.tape_mat_pcc" class="badge" :class="getTapeMatPccBadgeClass(item.tape_mat_pcc)">
                    {{ getTapeMatPccDisplay(item.tape_mat_pcc) }}
                </span>
                <span v-else>-</span>
            </div>
      </div>
    </div>
            </div>
          </div>

          <!-- Stock Information -->
          <div class="section-group">
            <h6 class="section-title">Stock Information</h6>
            <div class="row g-3">
              <div class="col-6">
                <div class="form-group">
                  <label class="form-label">Current Stock</label>
                  <div class="form-value">
                    {{ item.current_stock || 0 }}
                    <span class="badge ms-2" :class="getStockStatusClass(item)">
                      {{ getStockStatus(item) }}
                    </span>
                  </div>
                </div>
              </div>
              <div class="col-6">
                <div class="form-group">
                  <label class="form-label">Available Stock</label>
                  <div class="form-value">{{ item.available_stock || 0 }}</div>
                </div>
              </div>
              <div class="col-6">
                <div class="form-group">
                  <label class="form-label">Minimum Stock</label>
                  <div class="form-value">{{ item.minimum_stock || 0 }}</div>
                </div>
              </div>
              <div class="col-6">
                <div class="form-group">
                  <label class="form-label">Maximum Stock</label>
                  <div class="form-value">{{ item.maximum_stock || 0 }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Pricing Information -->
          <div class="section-group">
            <h6 class="section-title">Pricing Information</h6>
            <div class="row g-3">
              <div class="col-6">
                <div class="form-group">
                  <label class="form-label">Cost Price</label>
                  <div class="form-value">
                    {{ item.cost_price_currency || 'USD' }} {{ item.cost_price || '0.00' }}
                  </div>
                </div>
              </div>
              <div class="col-6">
                <div class="form-group">
                  <label class="form-label">Sale Price</label>
                  <div class="form-value">
                    {{ item.sale_price_currency || 'USD' }} {{ item.sale_price || '0.00' }}
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
                <div v-for="(price, currency) in multiCurrencyPrices.prices" :key="currency" class="col-4">
                  <div class="currency-card">
                    <div class="currency-code">{{ currency }}</div>
                    <div class="currency-prices">
                      <small>Purchase: {{ price.purchase_price }}</small><br>
                      <small>Sale: {{ price.sale_price }}</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- BOM Components -->
          <div class="section-group" v-if="bomComponents && bomComponents.length > 0">
            <h6 class="section-title">BOM Components ({{ bomComponents.length }})</h6>
            <div class="table-responsive">
              <table class="table table-sm">
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
                    <td>{{ component.component_code }}</td>
                    <td>{{ component.component_name }}</td>
                    <td>{{ component.quantity }}</td>
                    <td>{{ component.uom || '-' }}</td>
                    <td>
                      <span class="badge" :class="component.is_critical ? 'badge-warning' : 'badge-secondary'">
                        {{ component.is_critical ? 'Yes' : 'No' }}
                      </span>
                    </td>
                    <td>{{ component.yield_based !== undefined ? component.yield_based : '-' }}</td>
                    <td>{{ component.yield_ratio !== undefined ? `Ratio: ${component.yield_ratio}` : '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Where Used - Finished Goods yang menggunakan material ini -->
          <div class="section-group" v-if="usedInFinishedGoods && usedInFinishedGoods.length > 0">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="section-title mb-0">
                <i class="fas fa-arrow-up me-2"></i>
                Used in Finished Goods ({{ usedInFinishedGoods.length }})
              </h6>
              <div class="d-flex gap-2">
                <!-- Search -->
                <input
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="Search finished goods..."
                  v-model="searchUsedIn"
                  style="width: 200px;"
                >
                <!-- Export Button -->
                <button
                  class="btn btn-sm btn-outline-primary"
                  @click="exportUsedInData()"
                  title="Export to Excel"
                >
                  <i class="fas fa-download"></i>
                </button>
              </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-3">
              <div class="col-3">
                <div class="card border-primary">
                  <div class="card-body text-center py-2">
                    <small class="text-muted">Total Usage</small>
                    <div class="h6 mb-0">{{ usedInFinishedGoods.length }}</div>
                  </div>
                </div>
              </div>
              <div class="col-3">
                <div class="card border-warning">
                  <div class="card-body text-center py-2">
                    <small class="text-muted">Critical Usage</small>
                    <div class="h6 mb-0">{{ usedInFinishedGoods.filter(u => u.usage_details.is_critical).length }}</div>
                  </div>
                </div>
              </div>
              <div class="col-3">
                <div class="card border-info">
                  <div class="card-body text-center py-2">
                    <small class="text-muted">Yield Based</small>
                    <div class="h6 mb-0">{{ usedInFinishedGoods.filter(u => u.usage_details.is_yield_based).length }}</div>
                  </div>
                </div>
              </div>
              <div class="col-3">
                <div class="card border-success">
                  <div class="card-body text-center py-2">
                    <small class="text-muted">Active BOMs</small>
                    <div class="h6 mb-0">{{ usedInFinishedGoods.filter(u => u.bom_details.status === 'active').length }}</div>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="isLoadingUsedIn" class="text-center">
              <i class="fas fa-spinner fa-spin"></i> Loading usage information...
            </div>
            <div v-else class="table-responsive">
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

            <!-- Summary Info -->
            <div class="row mt-3">
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

          <!-- Empty state untuk Where Used -->
          <div class="section-group" v-else-if="!isLoadingUsedIn && usedInFinishedGoods && usedInFinishedGoods.length === 0">
            <h6 class="section-title">
              <i class="fas fa-arrow-up me-2"></i>
              Used in Finished Goods
            </h6>
            <div class="alert alert-secondary">
              <i class="fas fa-info-circle me-2"></i>
              This material is not used in any finished goods BOM.
            </div>
          </div>

          <!-- Recent Transactions -->
          <div class="section-group">
            <h6 class="section-title">
              Recent Transactions
              <span class="float-end">
                <button class="btn btn-outline-primary btn-sm" @click="fetchTransactions">
                  <i class="fas fa-sync-alt"></i>
                </button>
              </span>
            </h6>
            <div v-if="isLoadingTransactions" class="text-center py-3">
              <i class="fas fa-spinner fa-spin"></i> Loading transactions...
            </div>
            <div v-else-if="transactions && transactions.length > 0" class="table-responsive">
              <table class="table table-sm">
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
            <div v-else class="text-center py-3 text-muted">
              <i class="fas fa-inbox"></i> No recent transactions
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Item Modal -->
    <ItemFormModal
      v-if="showEditModal"
      :is-edit-mode="true"
      :item-form="itemForm"
      :categories="categories"
      :unit-of-measures="unitOfMeasures"
      @save="saveItem"
      @close="closeEditModal"
    />

    <!-- Delete Confirmation Modal -->
    <ConfirmationModal
      v-if="showDeleteModal"
      title="Confirm Delete"
      :message="`Are you sure you want to delete <strong>${item?.name}</strong>?`"
      confirm-button-text="Delete Item"
      confirm-button-class="btn btn-danger"
      @confirm="deleteItem"
      @close="closeDeleteModal"
    />
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import ItemService from '@/services/ItemService.js';
import ItemFormModal from '@/components/inventory/ItemFormModal.vue';
import ConfirmationModal from '@/components/common/ConfirmationModal.vue';

export default {
  name: 'ItemDetail',
  components: {
    ItemFormModal,
    ConfirmationModal
  },
  props: {
    id: {
      type: [String, Number],
      required: true
    }
  },
  setup(props) {
    const router = useRouter();

    // Core data
    const item = ref(null);
    const transactions = ref([]);
    const categories = ref([]);
    const unitOfMeasures = ref([]);
    const bomComponents = ref([]);

    // Where Used feature
    const usedInFinishedGoods = ref([]);
    const isLoadingUsedIn = ref(false);
    const searchUsedIn = ref('');

    // Loading states
    const isLoading = ref(true);
    const isLoadingTransactions = ref(true);
    const isLoadingCurrencies = ref(false);

    // Modal states
    const showEditModal = ref(false);
    const showDeleteModal = ref(false);
    const showMultiCurrencyPrices = ref(false);

    // Misc
    const multiCurrencyPrices = ref(null);
    const debugMode = ref(false);
    const errorMessage = ref('');
    const retryCount = ref(0);
    const maxRetries = ref(3);

    // Form data
    const itemForm = ref({
      item_id: null,
      item_code: '',
      name: '',
      description: '',
      category_id: '',
      uom_id: '',
      minimum_stock: 0,
      maximum_stock: 0,
      is_purchasable: false,
      is_sellable: false,
      cost_price: 0,
      sale_price: 0,
      cost_price_currency: 'USD',
      sale_price_currency: 'USD',
      length: '',
      width: '',
      thickness: '',
      weight: '',
      hs_code: ''
    });

    // Helper function to convert snake_case keys to camelCase
    const toCamelCase = (obj) => {
      if (Array.isArray(obj)) {
        return obj.map(v => toCamelCase(v));
      } else if (obj !== null && obj.constructor === Object) {
        return Object.keys(obj).reduce((result, key) => {
          const camelKey = key.replace(/_([a-z])/g, g => g[1].toUpperCase());
          result[camelKey] = toCamelCase(obj[key]);
          return result;
        }, {});
      }
      return obj;
    };

    // Computed properties
    const canDelete = computed(() => {
      if (!item.value) return false;
      const hasTransactions = transactions.value.length > 0;
      const hasBatches = item.value.batches && item.value.batches.length > 0;
      return !hasTransactions && !hasBatches;
    });

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

    // Validation
    const validateRouteParams = () => {
      if (!props.id) {
        item.value = null;
        errorMessage.value = 'No item ID provided';
        return false;
      }

      const itemId = parseInt(props.id);
      if (isNaN(itemId) || itemId <= 0) {
        item.value = null;
        errorMessage.value = 'Invalid item ID provided';
        return false;
      }

      return true;
    };

    // Core fetch methods
    const fetchItem = async () => {
      isLoading.value = true;
      try {
        console.log('Fetching item with ID:', props.id);

        const itemId = parseInt(props.id);
        if (isNaN(itemId)) {
          throw new Error('Invalid item ID');
        }

        const response = await ItemService.getItemById(itemId);
        console.log('API Response:', response);

        let itemData = null;

        if (response.data && response.data.data) {
          itemData = response.data.data;
        } else if (response.data) {
          itemData = response.data;
        } else if (response) {
          itemData = response;
        }

        if (!itemData || !itemData.item_id) {
          throw new Error('Item data not found in response');
        }

        item.value = itemData;

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

        // Populate form for potential edit
        Object.assign(itemForm.value, {
          item_id: itemData.item_id,
          item_code: itemData.item_code,
          name: itemData.name,
          description: itemData.description || '',
          category_id: itemData.category_id || '',
          uom_id: itemData.uom_id || '',
          minimum_stock: itemData.minimum_stock || 0,
          maximum_stock: itemData.maximum_stock || 0,
          is_purchasable: itemData.is_purchasable || false,
          is_sellable: itemData.is_sellable || false,
          cost_price: itemData.cost_price || 0,
          sale_price: itemData.sale_price || 0,
          cost_price_currency: itemData.cost_price_currency || 'USD',
          sale_price_currency: itemData.sale_price_currency || 'USD',
          length: itemData.length || '',
          width: itemData.width || '',
          thickness: itemData.thickness || '',
          weight: itemData.weight || '',
          tape_mat_pcc: itemData.tape_mat_pcc || '',
          hs_code: itemData.hs_code || ''
        });

      } catch (error) {
        console.error('Error fetching item:', error);
        item.value = null;

        if (error.response && error.response.status === 404) {
          errorMessage.value = 'Item not found.';
        } else if (error.message === 'Invalid item ID') {
          errorMessage.value = 'Invalid item ID provided.';
        } else {
          errorMessage.value = 'An error occurred while loading the item.';
        }

        throw error;
      } finally {
        isLoading.value = false;
      }
    };

    const fetchItemWithRetry = async (attempt = 1) => {
      try {
        await fetchItem();
        errorMessage.value = '';
        retryCount.value = 0;

        // After successful item fetch, load used in data
        await fetchUsedInFinishedGoods(props.id);
      } catch (error) {
        if (attempt < maxRetries.value) {
          retryCount.value = attempt;
          await new Promise(resolve => setTimeout(resolve, 1000 * attempt));
          return fetchItemWithRetry(attempt + 1);
        } else {
          retryCount.value = maxRetries.value;
          if (!errorMessage.value) {
            errorMessage.value = 'Failed to load item after multiple attempts.';
          }
        }
      }
    };

const fetchTransactions = async () => {
      if (!props.id) return;

      isLoadingTransactions.value = true;
      try {
        const response = await ItemService.getItemTransactions(props.id, { limit: 10 });
        let rawTransactions = [];
        if (response && response.data && response.data.transactions && Array.isArray(response.data.transactions.data)) {
          rawTransactions = response.data.transactions.data;
        } else if (response && response.data && Array.isArray(response.data)) {
          rawTransactions = response.data;
        } else if (response && Array.isArray(response)) {
          rawTransactions = response;
        } else if (response && typeof response === 'object') {
          rawTransactions = [response];
        } else {
          rawTransactions = [];
        }
        transactions.value = toCamelCase(rawTransactions);
      } catch (error) {
        console.error('Error fetching transactions:', error);
        transactions.value = [];
      } finally {
        isLoadingTransactions.value = false;
      }
    };

    const fetchCategories = async () => {
      try {
        const response = await ItemService.getCategories();
        categories.value = response.data || response || [];
      } catch (error) {
        console.error('Error fetching categories:', error);
      }
    };

    const fetchUnitOfMeasures = async () => {
      try {
        const response = await ItemService.getUnitOfMeasures();
        unitOfMeasures.value = response.data || response || [];
      } catch (error) {
        console.error('Error fetching unit of measures:', error);
      }
    };

    // Where Used functionality
    const fetchUsedInFinishedGoods = async (itemId) => {
      if (!itemId) return;

      isLoadingUsedIn.value = true;
      try {
        const response = await axios.get(`/items/${itemId}/used-in-finished-goods`);
        usedInFinishedGoods.value = response.data.data.used_in_finished_goods || [];
        console.log('📊 Used in finished goods loaded:', usedInFinishedGoods.value.length);
      } catch (error) {
        console.error('❌ Error fetching used in finished goods:', error);
        usedInFinishedGoods.value = [];
      } finally {
        isLoadingUsedIn.value = false;
      }
    };

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

      // For now, just console log - you can implement actual export later
      console.log('Export data:', exportData);
      alert('Export functionality would be implemented here');
    };

    const viewFinishedGood = (finishedGood) => {
      router.push({
        name: 'ItemDetail',
        params: { id: finishedGood.item_id }
      });
    };

    const viewBOM = (bomId) => {
      // Navigate to BOM detail - adjust route as needed
      window.open(`/manufacturing/bom/${bomId}`, '_blank');
    };

    // Multi-currency pricing
    const fetchPricesInCurrencies = async () => {
      if (isLoadingCurrencies.value || !item.value) return;

      isLoadingCurrencies.value = true;
      try {
        const response = await axios.get(`/items/${item.value.item_id}/prices-in-currencies`);
        multiCurrencyPrices.value = response.data;
        showMultiCurrencyPrices.value = true;
      } catch (error) {
        console.error('Error fetching multi-currency prices:', error);
        alert('Failed to load multi-currency prices');
      } finally {
        isLoadingCurrencies.value = false;
      }
    };

    // Modal methods
    const openEditModal = () => {
      showEditModal.value = true;
    };

    const closeEditModal = () => {
      showEditModal.value = false;
    };

    const saveItem = async (formData) => {
      try {
        await ItemService.updateItem(item.value.item_id, formData);
        await fetchItemWithRetry();
        closeEditModal();
        alert('Item updated successfully');
      } catch (error) {
        console.error('Error updating item:', error);
        alert('Failed to update item');
      }
    };

    const confirmDelete = () => {
      showDeleteModal.value = true;
    };

    const closeDeleteModal = () => {
      showDeleteModal.value = false;
    };

    const deleteItem = async () => {
      try {
        await ItemService.deleteItem(item.value.item_id);
        closeDeleteModal();
        alert('Item deleted successfully');
        router.push('/items');
      } catch (error) {
        console.error('Error deleting item:', error);
        if (error.response && error.response.status === 422) {
          alert('This item cannot be deleted because it has related transactions or batches.');
        } else {
          alert('An error occurred while deleting the item. Please try again.');
        }
      }
    };

    const openDocument = () => {
      if (item.value?.document_url) {
        window.open(item.value.document_url, '_blank');
      }
    };

    const retryFetch = () => {
      errorMessage.value = '';
      retryCount.value = 0;
      fetchItemWithRetry();
    };

    // Watch for route changes
    watch(() => props.id, (newId, oldId) => {
      if (newId !== oldId && newId) {
        if (validateRouteParams()) {
          fetchItemWithRetry();
          fetchTransactions();
        }
      }
    });

    // Initialize
    onMounted(() => {
      if (validateRouteParams()) {
        fetchItemWithRetry();
      }
      fetchTransactions();
      fetchCategories();
      fetchUnitOfMeasures();
    });

    return {
      // Core data
      item,
      transactions,
      categories,
      unitOfMeasures,
      bomComponents,

      // Where Used
      usedInFinishedGoods,
      isLoadingUsedIn,
      searchUsedIn,
      filteredUsedIn,

      // Loading states
      isLoading,
      isLoadingTransactions,
      isLoadingCurrencies,

      // Modal states
      showEditModal,
      showDeleteModal,
      showMultiCurrencyPrices,

      // Form and misc
      itemForm,
      multiCurrencyPrices,
      debugMode,
      errorMessage,
      retryCount,
      maxRetries,
      canDelete,

      // Utility methods
      formatDate,
      formatQuantity,
      getStockStatus,
      getStockStatusClass,
      getTransactionTypeClass,
      getQuantityClass,

      // Where Used methods
      sortUsedIn,
      exportUsedInData,
      viewFinishedGood,
      viewBOM,

      // Core methods
      openEditModal,
      closeEditModal,
      saveItem,
      confirmDelete,
      closeDeleteModal,
      deleteItem,
      fetchPricesInCurrencies,
      retryFetch,
      openDocument,
      fetchTransactions
    };
  }
};
</script>

<style scoped>
/* Modal Backdrop */
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

/* Modal Dialog */
.modal-dialog {
  width: 90%;
  max-width: 900px;
  max-height: 90vh;
  margin: auto;
}

/* Modal Content */
.modal-content {
  background-color: white;
  border-radius: 0.375rem;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
  display: flex;
  flex-direction: column;
  max-height: 90vh;
}

/* Modal Header */
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #dee2e6;
  background-color: #f8f9fa;
}

.modal-title {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 500;
  color: #495057;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1;
  color: #000;
  opacity: 0.5;
  cursor: pointer;
  padding: 0;
}

.btn-close:hover {
  opacity: 0.75;
}

/* Modal Body */
.modal-body {
  flex: 1 1 auto;
  padding: 1.25rem;
  overflow-y: auto;
}

/* Section Group */
.section-group {
  margin-bottom: 2rem;
  padding: 1rem;
  border: 1px solid #e9ecef;
  border-radius: 0.375rem;
  background-color: #f8f9fa;
}

.section-title {
  font-size: 0.9rem;
  font-weight: 600;
  color: #495057;
  margin-bottom: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 2px solid #dee2e6;
  padding-bottom: 0.5rem;
}

/* Bootstrap-like Grid */
.row {
  display: flex;
  flex-wrap: wrap;
  margin-right: -0.75rem;
  margin-left: -0.75rem;
}

.col-6 {
  position: relative;
  width: 100%;
  padding-right: 0.75rem;
  padding-left: 0.75rem;
  flex: 0 0 50%;
  max-width: 50%;
}

.col-4 {
  position: relative;
  width: 100%;
  padding-right: 0.75rem;
  padding-left: 0.75rem;
  flex: 0 0 33.333333%;
  max-width: 33.333333%;
}

.col-3 {
  position: relative;
  width: 100%;
  padding-right: 0.75rem;
  padding-left: 0.75rem;
  flex: 0 0 25%;
  max-width: 25%;
}

.col-12 {
  position: relative;
  width: 100%;
  padding-right: 0.75rem;
  padding-left: 0.75rem;
  flex: 0 0 100%;
  max-width: 100%;
}

.g-3 > * {
  margin-bottom: 1rem;
}

.g-2 > * {
  margin-bottom: 0.5rem;
}

/* Form Groups */
.form-group {
  margin-bottom: 0;
}

.form-label {
  margin-bottom: 0.25rem;
  font-size: 0.8rem;
  font-weight: 500;
  color: #6c757d;
  display: block;
}

.form-value {
  font-size: 0.9rem;
  color: #495057;
  min-height: 1.2rem;
  line-height: 1.3;
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
  background-image: none;
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
  padding: 0.75rem;
  font-size: 0.9rem;
  color: #495057;
  line-height: 1.4;
  min-height: 3rem;
}

/* Currency Cards */
.currency-card {
  background-color: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 0.25rem;
  padding: 0.5rem;
  text-align: center;
}

.currency-code {
  font-weight: 600;
  font-size: 0.85rem;
  color: #495057;
  margin-bottom: 0.25rem;
}

.currency-prices small {
  font-size: 0.75rem;
  color: #6c757d;
}

/* Statistics Cards */
.card {
  position: relative;
  display: flex;
  flex-direction: column;
  min-width: 0;
  word-wrap: break-word;
  background-color: #fff;
  background-clip: border-box;
  border: 1px solid rgba(0, 0, 0, 0.125);
  border-radius: 0.375rem;
}

.card-body {
  flex: 1 1 auto;
  padding: 1rem;
}

.border-primary {
  border-color: #0d6efd !important;
}

.border-warning {
  border-color: #ffc107 !important;
}

.border-info {
  border-color: #0dcaf0 !important;
}

.border-success {
  border-color: #198754 !important;
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
  transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.8125rem;
  border-radius: 0.25rem;
}

.btn-primary {
  color: #fff;
  background-color: #0d6efd;
  border-color: #0d6efd;
}

.btn-primary:hover {
  color: #fff;
  background-color: #0b5ed7;
  border-color: #0a58ca;
}

.btn-secondary {
  color: #fff;
  background-color: #6c757d;
  border-color: #6c757d;
}

.btn-secondary:hover {
  color: #fff;
  background-color: #5c636a;
  border-color: #565e64;
}

.btn-danger {
  color: #fff;
  background-color: #dc3545;
  border-color: #dc3545;
}

.btn-danger:hover {
  color: #fff;
  background-color: #c82333;
  border-color: #bd2130;
}

.btn-info {
  color: #000;
  background-color: #0dcaf0;
  border-color: #0dcaf0;
}

.btn-info:hover {
  color: #000;
  background-color: #31d2f2;
  border-color: #25cff2;
}

.btn-outline-primary {
  color: #0d6efd;
  border-color: #0d6efd;
}

.btn-outline-primary:hover {
  color: #fff;
  background-color: #0d6efd;
  border-color: #0d6efd;
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
  border-radius: 0.25rem;
}

/* Badges */
.badge {
  display: inline-block;
  padding: 0.35em 0.65em;
  font-size: 0.75em;
  font-weight: 700;
  line-height: 1;
  color: #fff;
  text-align: center;
  white-space: nowrap;
  vertical-align: baseline;
  border-radius: 0.375rem;
}

.badge-success {
  background-color: #198754;
}

.badge-danger {
  background-color: #dc3545;
}

.badge-warning {
  background-color: #ffc107;
  color: #000;
}

.badge-secondary {
  background-color: #6c757d;
}

.badge-info {
  background-color: #0dcaf0;
  color: #000;
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
  padding: 0.5rem;
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
  padding: 0.3rem;
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

.alert-secondary {
  color: #383d41;
  background-color: #f8f9fa;
  border-color: #dee2e6;
}

.alert-danger {
  color: #721c24;
  background-color: #f8d7da;
  border-color: #f5c6cb;
}

/* Utility Classes */
.text-center {
  text-align: center;
}

.text-success {
  color: #198754;
}

.text-muted {
  color: #6c757d;
}

.text-danger {
  color: #dc3545;
}

.py-3 {
  padding-top: 1rem;
  padding-bottom: 1rem;
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

.mt-3 {
  margin-top: 1rem;
}

.me-2 {
  margin-right: 0.5rem;
}

.me-1 {
  margin-right: 0.25rem;
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
  color: inherit;
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

/* Spinner */
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

/* Responsive */
@media (max-width: 768px) {
  .modal-dialog {
    width: 95%;
    margin: 1rem auto;
  }

  .col-6 {
    flex: 0 0 100%;
    max-width: 100%;
  }

  .col-4 {
    flex: 0 0 50%;
    max-width: 50%;
  }

  .col-3 {
    flex: 0 0 50%;
    max-width: 50%;
  }

  .modal-body {
    padding: 1rem;
  }

  .modal-header {
    padding: 0.75rem 1rem;
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

  .col-4 {
    flex: 0 0 100%;
    max-width: 100%;
  }

  .col-3 {
    flex: 0 0 100%;
    max-width: 100%;
  }

  .section-group {
    padding: 0.75rem;
  }

  .table-responsive {
    font-size: 0.8rem;
  }
}
</style>
