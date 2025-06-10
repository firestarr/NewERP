<!-- src/views/inventory/ItemDetail.vue - Modal Style -->
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

        <!-- Modal Body -->
        <div class="modal-body" v-if="isLoading">
          <div class="text-center py-4">
            <i class="fas fa-spinner fa-spin"></i> Loading item details...
          </div>
        </div>

        <div class="modal-body" v-else-if="!item">
          <div class="text-center py-4">
            <p>Item not found</p>
            <button class="btn btn-primary" @click="retryFetch">Retry</button>
          </div>
        </div>

        <div class="modal-body" v-else>
          <!-- Action Buttons -->
          <div class="mb-3">
            <button class="btn btn-secondary btn-sm me-2" @click="openEditModal">
              <i class="fas fa-edit"></i> Edit
            </button>
            <button class="btn btn-danger btn-sm" @click="confirmDelete" v-if="canDelete">
              <i class="fas fa-trash"></i> Delete
            </button>
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
              <div class="col-12" v-if="item.document_path">
                <div class="form-group">
                  <label class="form-label">Technical Document</label>
                  <div class="form-value">
                    <button class="btn btn-primary btn-sm" @click="openDocument">
                      <i class="fas fa-eye"></i> View Document
                    </button>
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
                  <div class="form-value">{{ item.current_stock }}</div>
                </div>
              </div>
              <div class="col-6">
                <div class="form-group">
                  <label class="form-label">Minimum Stock</label>
                  <div class="form-value">{{ item.minimum_stock }}</div>
                </div>
              </div>
              <div class="col-6">
                <div class="form-group">
                  <label class="form-label">Maximum Stock</label>
                  <div class="form-value">{{ item.maximum_stock }}</div>
                </div>
              </div>
              <div class="col-6">
                <div class="form-group">
                  <label class="form-label">Stock Status</label>
                  <div class="form-value">
                    <span class="badge" :class="'badge-' + getStockStatusClass(item)">
                      {{ getStockStatus(item) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Pricing Information -->
          <div class="section-group">
            <h6 class="section-title">
              Pricing Information
              <button
                v-if="!showMultiCurrencyPrices && (item.cost_price > 0 || item.sale_price > 0)"
                class="btn btn-outline-primary btn-sm float-end"
                @click="fetchPricesInCurrencies"
              >
                <i class="fas fa-money-bill-wave"></i> Show in Currencies
              </button>
            </h6>
            <div class="row g-3">
              <div class="col-6">
                <div class="form-group">
                  <label class="form-label">Cost Price</label>
                  <div class="form-value">{{ item.cost_price || '-' }} {{ item.cost_price_currency || 'IDR' }}</div>
                </div>
              </div>
              <div class="col-6">
                <div class="form-group">
                  <label class="form-label">Sale Price</label>
                  <div class="form-value">{{ item.sale_price || '-' }} {{ item.sale_price_currency || 'IDR' }}</div>
                </div>
              </div>
              <div class="col-6">
                <div class="form-group">
                  <label class="form-label">Purchasable</label>
                  <div class="form-value">
                    <span class="text-success" v-if="item.is_purchasable">Yes</span>
                    <span class="text-muted" v-else>No</span>
                  </div>
                </div>
              </div>
              <div class="col-6">
                <div class="form-group">
                  <label class="form-label">Sellable</label>
                  <div class="form-value">
                    <span class="text-success" v-if="item.is_sellable">Yes</span>
                    <span class="text-muted" v-else>No</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Multi-Currency Prices -->
            <div v-if="showMultiCurrencyPrices" class="mt-3">
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
                    <td>{{ component.yield_ratio !== undefined ? component.yield_ratio : '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Recent Transactions -->
          <div class="section-group">
            <h6 class="section-title">Recent Transactions</h6>
            <div v-if="isLoadingTransactions" class="text-center py-3">
              <i class="fas fa-spinner fa-spin"></i> Loading transactions...
            </div>
            <div v-else-if="transactions.length === 0" class="text-center py-3 text-muted">
              No transactions found
            </div>
            <div v-else class="table-responsive">
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Quantity</th>
                    <th>Warehouse</th>
                  </tr>
                </thead>
                <tbody>
          <tr v-for="transaction in transactions.slice(0, 5)" :key="transaction.transactionId">
            <td>{{ formatDate(transaction.transactionDate) }}</td>
            <td>
              <span class="badge" :class="'badge-' + getTransactionTypeClass(transaction.transactionType)">
                {{ transaction.transactionType }}
              </span>
            </td>
            <td>{{ transaction.quantity }} {{ item.unitOfMeasure?.symbol || '' }}</td>
            <td>{{ transaction.warehouse?.name || '-' }}</td>
          </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
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
    const item = ref(null);
    const transactions = ref([]);
    const categories = ref([]);
    const unitOfMeasures = ref([]);
    const isLoading = ref(true);
    const isLoadingTransactions = ref(true);
    const isLoadingCurrencies = ref(false);
    const showEditModal = ref(false);
    const showDeleteModal = ref(false);
    const showMultiCurrencyPrices = ref(false);
    const multiCurrencyPrices = ref(null);
    const bomComponents = ref([]);
    const debugMode = ref(false);

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

    // Enhanced error handling
    const errorMessage = ref('');
    const retryCount = ref(0);
    const maxRetries = ref(3);

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


    const canDelete = computed(() => {
      if (!item.value) return false;
      const hasTransactions = transactions.value.length > 0;
      const hasBatches = item.value.batches && item.value.batches.length > 0;
      return !hasTransactions && !hasBatches;
    });

    // Enhanced fetch with robust response handling
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

    const retryFetch = () => {
      errorMessage.value = '';
      retryCount.value = 0;
      fetchItemWithRetry();
    };

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

const fetchTransactions = async () => {
      if (!props.id) return;

      isLoadingTransactions.value = true;
      try {
        const response = await ItemService.getItemTransactions(props.id, { limit: 10 });
        let rawTransactions = [];
        if (response && response.data && Array.isArray(response.data.data)) {
          rawTransactions = response.data.data;
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

    const fetchPricesInCurrencies = async () => {
      if (isLoadingCurrencies.value || !item.value?.item_id) return;

      showMultiCurrencyPrices.value = true;
      isLoadingCurrencies.value = true;

      try {
        const response = await ItemService.getPricesInCurrencies(
          item.value.item_id,
          ['USD', 'IDR', 'EUR', 'SGD', 'JPY']
        );

        if (response.success) {
          multiCurrencyPrices.value = response.data;
        }
      } catch (error) {
        console.error('Error fetching prices in currencies:', error);
      } finally {
        isLoadingCurrencies.value = false;
      }
    };

    const fetchCategories = async () => {
      try {
        const response = await ItemService.getCategories();
        categories.value = response.data || [];
      } catch (error) {
        console.error('Error fetching categories:', error);
        categories.value = [];
      }
    };

    const fetchUnitOfMeasures = async () => {
      try {
        const response = await ItemService.getUnitsOfMeasure();
        unitOfMeasures.value = response.data || [];
      } catch (error) {
        console.error('Error fetching units of measure:', error);
        unitOfMeasures.value = [];
      }
    };

    const formatDate = (dateString) => {
      if (!dateString) return '-';
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    };

    const getStockStatus = (item) => {
      if (item.current_stock <= item.minimum_stock) {
        return 'Low Stock';
      } else if (item.current_stock >= item.maximum_stock) {
        return 'Over Stock';
      } else {
        return 'Normal';
      }
    };

    const getStockStatusClass = (item) => {
      const status = getStockStatus(item);
      switch (status) {
        case 'Low Stock': return 'danger';
        case 'Over Stock': return 'warning';
        default: return 'success';
      }
    };

    const getTransactionTypeClass = (type) => {
      if (['IN', 'RECEIPT', 'RETURN', 'ADJUSTMENT_IN', 'receive', 'return', 'adjustment'].includes(type)) {
        return 'success';
      } else if (['OUT', 'ISSUE', 'SALE', 'ADJUSTMENT_OUT', 'issue', 'transfer', 'sale'].includes(type)) {
        return 'danger';
      }
      return 'secondary';
    };

    const getQuantityClass = (type) => {
      if (['IN', 'RECEIPT', 'RETURN', 'ADJUSTMENT_IN', 'receive', 'return'].includes(type)) {
        return 'text-success';
      } else if (['OUT', 'ISSUE', 'SALE', 'ADJUSTMENT_OUT', 'issue', 'transfer', 'sale'].includes(type)) {
        return 'text-danger';
      }
      return '';
    };

    const openEditModal = () => {
      showEditModal.value = true;
    };

    const closeEditModal = () => {
      showEditModal.value = false;
    };

    const saveItem = async (formData) => {
      try {
        await ItemService.updateItem(formData.get('item_id'), formData);
        await fetchItem();
        closeEditModal();
        alert('Item updated successfully!');
      } catch (error) {
        console.error('Error updating item:', error);
        if (error.validationErrors) {
          alert('Please check the form for errors: ' + Object.values(error.validationErrors).join(', '));
        } else {
          alert('An error occurred while updating the item. Please try again.');
        }
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
        await ItemService.deleteItem(props.id);
        closeDeleteModal();
        alert('Item deleted successfully!');
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

    // Watch for route changes
    watch(() => props.id, (newId, oldId) => {
      if (newId !== oldId && newId) {
        if (validateRouteParams()) {
          fetchItemWithRetry();
          fetchTransactions();
        }
      }
    });

    // Enhanced onMounted
    onMounted(() => {
      if (validateRouteParams()) {
        fetchItemWithRetry();
      }
      fetchTransactions();
      fetchCategories();
      fetchUnitOfMeasures();
    });

    return {
      item,
      transactions,
      categories,
      unitOfMeasures,
      isLoading,
      isLoadingTransactions,
      isLoadingCurrencies,
      showEditModal,
      showDeleteModal,
      showMultiCurrencyPrices,
      multiCurrencyPrices,
      itemForm,
      canDelete,
      bomComponents,
      debugMode,
      errorMessage,
      retryCount,
      maxRetries,
      formatDate,
      getStockStatus,
      getStockStatusClass,
      getTransactionTypeClass,
      getQuantityClass,
      openEditModal,
      closeEditModal,
      saveItem,
      confirmDelete,
      closeDeleteModal,
      deleteItem,
      fetchPricesInCurrencies,
      retryFetch,
      openDocument
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
  max-width: 700px;
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
  margin-bottom: 1.5rem;
}

.section-title {
  font-size: 0.9rem;
  font-weight: 600;
  color: #495057;
  margin-bottom: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
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

.btn-outline-primary {
  color: #0d6efd;
  border-color: #0d6efd;
}

.btn-outline-primary:hover {
  color: #fff;
  background-color: #0d6efd;
  border-color: #0d6efd;
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

.table-responsive {
  display: block;
  width: 100%;
  overflow-x: auto;
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

.mb-3 {
  margin-bottom: 1rem;
}

.mt-3 {
  margin-top: 1rem;
}

.me-2 {
  margin-right: 0.5rem;
}

.float-end {
  float: right;
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

  .modal-body {
    padding: 1rem;
  }

  .modal-header {
    padding: 0.75rem 1rem;
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
}
</style>
