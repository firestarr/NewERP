<!-- src/views/purchasing/PurchaseOrderFormView.vue -->
<template>
  <div class="purchase-order-form">
    <div class="page-header">
      <div class="header-left">
        <router-link to="/purchasing/orders" class="back-link">
          <i class="fas fa-arrow-left"></i> Back to Purchase Orders
        </router-link>
        <h1>{{ isEditMode ? 'Edit Purchase Order' : 'Create Purchase Order' }}</h1>
      </div>
    </div>

    <div class="alert-card" v-if="isEditMode && purchaseOrder.status !== 'draft'">
      <div class="alert-icon">
        <i class="fas fa-info-circle"></i>
      </div>
      <div class="alert-content">
        <p>This purchase order is in "{{ purchaseOrder.status }}" status and some fields may not be editable.</p>
      </div>
    </div>

    <div v-if="isLoading" class="loading">
      <i class="fas fa-spinner fa-spin"></i> Loading purchase order data...
    </div>

    <form v-else @submit.prevent="savePurchaseOrder" class="po-form">
      <div class="card">
        <div class="card-header">
          <h2>Purchase Order Information</h2>
        </div>
        <div class="card-body">
          <div class="form-row">
            <div class="form-group">
              <label for="vendor_id">Vendor <span class="required">*</span></label>
              <div class="dropdown-container">
                <input
                  type="text"
                  id="vendor_search"
                  v-model="vendorSearch"
                  class="form-control"
                  placeholder="Search for a vendor..."
                  @focus="showVendorDropdown = true"
                  @blur="hideVendorDropdown"
                  :disabled="isEditMode && purchaseOrder.status !== 'draft'"
                  required
                />
                <div v-if="showVendorDropdown" class="dropdown-menu">
                  <div
                    v-for="vendor in filteredVendors"
                    :key="vendor.vendor_id"
                    @click="selectVendor(vendor)"
                    class="dropdown-item"
                  >
                    {{ vendor.name }} ({{ vendor.vendor_code || 'No Code' }})
                  </div>
                  <div v-if="filteredVendors.length === 0" class="dropdown-item text-muted">
                    No vendors found
                  </div>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label for="po_date">PO Date <span class="required">*</span></label>
              <input
                type="date"
                id="po_date"
                v-model="purchaseOrder.po_date"
                :disabled="isEditMode && purchaseOrder.status !== 'draft'"
                required
              >
            </div>

            <div class="form-group">
              <label for="expected_delivery">Expected Delivery</label>
              <input
                type="date"
                id="expected_delivery"
                v-model="purchaseOrder.expected_delivery"
              >
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="payment_terms">Payment Terms</label>
              <select id="payment_terms" v-model="purchaseOrder.payment_terms">
                <option value="">Select Payment Terms</option>
                <option value="Net 30">Net 30</option>
                <option value="Net 45">Net 45</option>
                <option value="Net 60">Net 60</option>
                <option value="Immediate">Immediate</option>
              </select>
            </div>

            <div class="form-group">
              <label for="delivery_terms">Delivery Terms</label>
              <select id="delivery_terms" v-model="purchaseOrder.delivery_terms">
                <option value="">Select Delivery Terms</option>
                <option value="FOB">FOB (Free On Board)</option>
                <option value="CIF">CIF (Cost, Insurance, Freight)</option>
                <option value="EXW">EXW (Ex Works)</option>
                <option value="DDP">DDP (Delivered Duty Paid)</option>
              </select>
            </div>

            <div class="form-group">
              <label for="currency_code">Currency</label>
              <select
                id="currency_code"
                v-model="purchaseOrder.currency_code"
                :disabled="isEditMode && purchaseOrder.status !== 'draft'"
              >
                <option
                  v-for="currency in availableCurrencies"
                  :key="currency.code"
                  :value="currency.code"
                >
                  {{ currency.code }} - {{ currency.name }} ({{ currency.symbol }})
                </option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="card mt-4">
        <div class="card-header">
          <h2>Purchase Order Lines</h2>
          <button
            type="button"
            class="btn btn-primary"
            @click="addLine"
            :disabled="isEditMode && purchaseOrder.status !== 'draft'"
          >
            <i class="fas fa-plus"></i> Add Line
          </button>
        </div>

        <div class="card-body">
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th style="width: 40%">Item</th>
                  <th style="width: 15%">Quantity</th>
                  <th style="width: 15%">UOM</th>
                  <th style="width: 15%">Unit Price</th>
                  <th style="width: 15%">Total</th>
                  <th style="width: 10%">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(line, lineIndex) in purchaseOrder.lines" :key="lineIndex">
                  <td>
                    <div class="dropdown-container">
                      <input
                        type="text"
                        v-model="itemSearches[lineIndex]"
                        class="form-control item-search"
                        placeholder="Search for an item..."
                        @focus="showItemDropdown[lineIndex] = true"
                        @blur="hideItemDropdown(lineIndex)"
                        :disabled="isEditMode && purchaseOrder.status !== 'draft'"
                      />
                      <div v-if="showItemDropdown[lineIndex]" class="dropdown-menu">
                        <div
                          v-for="item in getFilteredItems(lineIndex)"
                          :key="item.item_id"
                          @click="selectItem(item, lineIndex)"
                          class="dropdown-item"
                        >
                          {{ item.item_code }} - {{ item.name }}
                        </div>
                        <div v-if="getFilteredItems(lineIndex).length === 0" class="dropdown-item text-muted">
                          No items found
                        </div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <input
                      type="number"
                      v-model.number="line.quantity"
                      min="0.01"
                      step="0.01"
                      @input="updateLineTotal(line)"
                      :disabled="isEditMode && purchaseOrder.status !== 'draft'"
                      required
                    >
                  </td>
                  <td>
                    <select
                      v-model="line.uom_id"
                      :disabled="isEditMode && purchaseOrder.status !== 'draft'"
                      required
                    >
                      <option value="" disabled>Select UOM</option>
                      <option v-for="uom in uoms" :key="uom.uom_id" :value="uom.uom_id">
                        {{ uom.name }}
                      </option>
                    </select>
                  </td>
                  <td>
                    <input
                      type="number"
                      v-model.number="line.unit_price"
                      min="0"
                      step="0.01"
                      @input="updateLineTotal(line)"
                      :disabled="isEditMode && purchaseOrder.status !== 'draft'"
                      required
                    >
                  </td>
                  <td>
                    <input
                      type="number"
                      v-model.number="line.subtotal"
                      readonly
                      class="read-only"
                    >
                  </td>
                  <td>
                    <button
                      type="button"
                      class="btn-icon btn-danger"
                      @click="removeLine(lineIndex)"
                      :disabled="isEditMode && purchaseOrder.status !== 'draft'"
                    >
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>
                <tr v-if="purchaseOrder.lines.length === 0">
                  <td colspan="6" class="empty-message">
                    No items added. Click "Add Line" to add items to this purchase order.
                  </td>
                </tr>
              </tbody>
              <tfoot>
                <tr>
                  <td colspan="4" class="text-right"><strong>Subtotal:</strong></td>
                  <td colspan="2">{{ formatCurrency(calculateSubtotal()) }}</td>
                </tr>
                <tr>
                  <td colspan="4" class="text-right"><strong>Tax:</strong></td>
                  <td colspan="2">
                    <input
                      type="number"
                      v-model.number="purchaseOrder.tax_amount"
                      min="0"
                      step="0.01"
                      @input="updateTotals"
                      :disabled="isEditMode && purchaseOrder.status !== 'draft'"
                    >
                  </td>
                </tr>
                <tr>
                  <td colspan="4" class="text-right"><strong>Total:</strong></td>
                  <td colspan="2" class="total-value">{{ formatCurrency(calculateTotal()) }}</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>

      <div class="form-actions mt-4">
        <button type="button" class="btn btn-secondary" @click="$router.go(-1)">Cancel</button>
        <button
          type="submit"
          class="btn btn-primary"
          :disabled="isSaving || (isEditMode && purchaseOrder.status !== 'draft')"
        >
          <span v-if="isSaving">
            <i class="fas fa-spinner fa-spin"></i> Saving...
          </span>
          <span v-else>
            <i class="fas fa-save"></i> {{ isEditMode ? 'Update' : 'Create' }} Purchase Order
          </span>
        </button>
      </div>
    </form>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'PurchaseOrderFormView',
  data() {
    return {
      isEditMode: false,
      isLoading: false,
      isSaving: false,
      vendors: [],
      items: [],
      uoms: [],
      availableCurrencies: [],
      baseCurrency: 'USD',
      purchaseOrder: {
        po_date: new Date().toISOString().split('T')[0],
        vendor_id: '',
        payment_terms: '',
        delivery_terms: '',
        expected_delivery: '',
        currency_code: '', // Will be set to base currency after loading
        tax_amount: 0,
        lines: []
      },
      // Properties for searchable vendor select
      vendorSearch: '',
      showVendorDropdown: false,
      // Properties for searchable item select
      itemSearches: [],
      showItemDropdown: []
    };
  },
  computed: {
    // Computed property to filter vendors based on search input
    filteredVendors() {
      if (!this.vendorSearch) {
        return this.vendors.filter(vendor => vendor && vendor.vendor_id);
      }
      return this.vendors.filter(vendor =>
        vendor &&
        vendor.vendor_id &&
        (vendor.name.toLowerCase().includes(this.vendorSearch.toLowerCase()) ||
         (vendor.vendor_code && vendor.vendor_code.toLowerCase().includes(this.vendorSearch.toLowerCase())))
      );
    }
  },
  created() {
    // Check if we're in edit mode
    const poId = this.$route.params.id;
    this.isEditMode = !!poId;

    // Initialize search arrays
    this.itemSearches = [];
    this.showItemDropdown = [];

    // Load necessary data
    this.loadCurrencySettings();
    this.loadVendors();
    this.loadItems();
    this.loadUoms();

    if (this.isEditMode) {
      this.loadPurchaseOrder(poId);
    } else {
      // Add an empty line for new POs
      this.addLine();
    }
  },
  methods: {
    // Load currency settings from system
    async loadCurrencySettings() {
      try {
        const response = await axios.get('/admin/currency/settings');
        if (response.data.status === 'success') {
          const data = response.data.data;
          this.baseCurrency = data.base_currency;
          
          // Convert available currencies object to array for dropdown
          this.availableCurrencies = Object.keys(data.available_currencies).map(code => ({
            code: code,
            name: data.available_currencies[code].name,
            symbol: data.available_currencies[code].symbol,
            decimal_places: data.available_currencies[code].decimal_places
          })).sort((a, b) => {
            // Put base currency first, then sort alphabetically
            if (a.code === this.baseCurrency) return -1;
            if (b.code === this.baseCurrency) return 1;
            return a.name.localeCompare(b.name);
          });
          
          // Set default currency for new purchase orders
          if (!this.isEditMode) {
            this.purchaseOrder.currency_code = this.baseCurrency;
          }
        }
      } catch (error) {
        console.error('Error loading currency settings:', error);
        // Fallback to hardcoded currencies if API fails
        this.baseCurrency = 'USD';
        this.availableCurrencies = [
          { code: 'USD', name: 'US Dollar', symbol: '$', decimal_places: 2 },
          { code: 'EUR', name: 'Euro', symbol: '€', decimal_places: 2 },
          { code: 'GBP', name: 'British Pound', symbol: '£', decimal_places: 2 },
          { code: 'IDR', name: 'Indonesian Rupiah', symbol: 'Rp', decimal_places: 0 },
          { code: 'JPY', name: 'Japanese Yen', symbol: '¥', decimal_places: 0 },
          { code: 'CNY', name: 'Chinese Yuan', symbol: '¥', decimal_places: 2 },
          { code: 'SGD', name: 'Singapore Dollar', symbol: 'S$', decimal_places: 2 }
        ];
        if (!this.isEditMode) {
          this.purchaseOrder.currency_code = this.baseCurrency;
        }
      }
    },

    // Method to select a vendor from the dropdown
    selectVendor(vendor) {
      this.purchaseOrder.vendor_id = vendor.vendor_id;
      this.vendorSearch = `${vendor.name} (${vendor.vendor_code || 'No Code'})`;
      this.showVendorDropdown = false;
      // If vendor has preferred currency, set it as the order currency
      if (vendor.preferred_currency) {
        this.purchaseOrder.currency_code = vendor.preferred_currency;
      }
    },

    // Method to hide vendor dropdown
    hideVendorDropdown() {
      setTimeout(() => {
        this.showVendorDropdown = false;
      }, 200);
    },

    async loadVendors() {
      try {
        const response = await axios.get('/vendors');

        // Handle paginated response structure
        if (response.data && response.data.data && response.data.data.data) {
          // The vendors are in response.data.data.data (paginated response)
          this.vendors = response.data.data.data.filter(vendor => vendor && vendor.vendor_id);
        } else if (response.data && response.data.data && Array.isArray(response.data.data)) {
          // Regular response structure: response.data.data
          this.vendors = response.data.data.filter(vendor => vendor && vendor.vendor_id);
        } else if (response.data && Array.isArray(response.data)) {
          // Direct array structure
          this.vendors = response.data.filter(vendor => vendor && vendor.vendor_id);
        } else {
          console.warn('Unexpected vendors response format:', response.data);
          this.vendors = [];
        }
      } catch (error) {
        console.error('Error loading vendors:', error);
        this.vendors = [];
      }
    },

    async loadItems() {
      try {
        const response = await axios.get('/items?is_purchasable=1');
        this.items = response.data.data;
      } catch (error) {
        console.error('Error loading items:', error);
      }
    },

    async loadUoms() {
      try {
        const response = await axios.get('/uoms');
        this.uoms = response.data.data;
      } catch (error) {
        console.error('Error loading UOMs:', error);
      }
    },

    async loadPurchaseOrder(poId) {
      this.isLoading = true;
      try {
        const response = await axios.get(`/purchase-orders/${poId}`);

        if (response.data.status === 'success') {
          this.purchaseOrder = response.data.data;

          // Set vendor search text if vendor is selected
          if (this.purchaseOrder.vendor_id) {
            const vendor = this.vendors.find(v => v.vendor_id === this.purchaseOrder.vendor_id);
            if (vendor) {
              this.vendorSearch = `${vendor.name} (${vendor.vendor_code || 'No Code'})`;
            }
          }

          // Ensure po_date is in the right format for input[type=date]
          if (this.purchaseOrder.po_date) {
            this.purchaseOrder.po_date = this.formatDateForInput(this.purchaseOrder.po_date);
          }

          // Ensure expected_delivery is in the right format for input[type=date]
          if (this.purchaseOrder.expected_delivery) {
            this.purchaseOrder.expected_delivery = this.formatDateForInput(this.purchaseOrder.expected_delivery);
          }

          // Ensure lines array exists
          if (!this.purchaseOrder.lines) {
            this.purchaseOrder.lines = [];
          }

          // Initialize search states for existing lines
          this.initializeLineSearchStates();

          // If no lines, add one empty line
          if (this.purchaseOrder.lines.length === 0) {
            this.addLine();
          }
        }
      } catch (error) {
        console.error('Error loading purchase order:', error);
        alert('Failed to load purchase order data');
      } finally {
        this.isLoading = false;
      }
    },

    // Initialize search states for lines
    initializeLineSearchStates() {
      this.itemSearches = new Array(this.purchaseOrder.lines.length);
      this.showItemDropdown = new Array(this.purchaseOrder.lines.length);

      this.purchaseOrder.lines.forEach((line, index) => {
        if (line.item_id) {
          const item = this.items.find(i => i.item_id === line.item_id);
          if (item) {
            this.itemSearches[index] = `${item.item_code} - ${item.name}`;
          } else {
            this.itemSearches[index] = '';
          }
        } else {
          this.itemSearches[index] = '';
        }
        this.showItemDropdown[index] = false;
      });
    },

    // Get filtered items for a specific line
    getFilteredItems(lineIndex) {
      const searchText = this.itemSearches[lineIndex] || '';
      if (!searchText) {
        return this.items;
      }
      return this.items.filter(item =>
        item.name.toLowerCase().includes(searchText.toLowerCase()) ||
        item.item_code.toLowerCase().includes(searchText.toLowerCase())
      );
    },

    // Select an item from dropdown
    selectItem(item, lineIndex) {
      const line = this.purchaseOrder.lines[lineIndex];
      line.item_id = item.item_id;
      this.itemSearches[lineIndex] = `${item.item_code} - ${item.name}`;
      this.showItemDropdown[lineIndex] = false;
      this.updateLineItem(line);
    },

    // Hide item dropdown with delay
    hideItemDropdown(lineIndex) {
      setTimeout(() => {
        this.showItemDropdown[lineIndex] = false;
      }, 200);
    },

    addLine() {
      const newIndex = this.purchaseOrder.lines.length;

      this.purchaseOrder.lines.push({
        item_id: '',
        quantity: 1,
        uom_id: '',
        unit_price: 0,
        subtotal: 0,
        tax: 0,
        total: 0
      });

      // Initialize search states for new line
      this.itemSearches[newIndex] = '';
      this.showItemDropdown[newIndex] = false;
    },

    removeLine(lineIndex) {
      if (confirm('Are you sure you want to remove this line?')) {
        this.purchaseOrder.lines.splice(lineIndex, 1);
        this.itemSearches.splice(lineIndex, 1);
        this.showItemDropdown.splice(lineIndex, 1);
        this.updateTotals();
      }
    },

    updateLineItem(line) {
      const item = this.items.find(i => i.item_id === line.item_id);
      if (item) {
        if (!line.uom_id && item.uom_id) {
          line.uom_id = item.uom_id;
        }
        if (item.cost_price && line.unit_price === 0) {
          line.unit_price = item.cost_price;
          this.updateLineTotal(line);
        }
      }
    },

    updateLineTotal(line) {
      if (line.quantity && line.unit_price) {
        line.subtotal = parseFloat((line.quantity * line.unit_price).toFixed(2));
      } else {
        line.subtotal = 0;
      }
      this.updateTotals();
    },

    calculateSubtotal() {
      return this.purchaseOrder.lines.reduce((sum, line) => sum + (line.subtotal || 0), 0);
    },

    calculateTotal() {
      const subtotal = this.calculateSubtotal();
      const tax = parseFloat(this.purchaseOrder.tax_amount || 0);
      return subtotal + tax;
    },

    updateTotals() {
      const subtotal = this.calculateSubtotal();
      const tax = parseFloat(this.purchaseOrder.tax_amount || 0);
      this.purchaseOrder.total_amount = subtotal + tax;
    },

    formatDateForInput(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toISOString().split('T')[0];
    },

    formatCurrency(amount) {
      if (amount === null || amount === undefined) return '-';
      
      // Get current currency info
      const currentCurrency = this.availableCurrencies.find(c => c.code === this.purchaseOrder.currency_code);
      const decimalPlaces = currentCurrency ? currentCurrency.decimal_places : 2;
      
      return new Intl.NumberFormat('id-ID', {
        style: 'decimal',
        minimumFractionDigits: decimalPlaces,
        maximumFractionDigits: decimalPlaces
      }).format(amount);
    },

    async savePurchaseOrder() {
      // Validate the form
      if (!this.purchaseOrder.vendor_id || !this.purchaseOrder.po_date) {
        alert('Please fill all required fields');
        return;
      }

      if (this.purchaseOrder.lines.length === 0) {
        alert('Please add at least one item to the purchase order');
        return;
      }

      for (const line of this.purchaseOrder.lines) {
        if (!line.item_id || !line.quantity || !line.uom_id) {
          alert('Please fill all required fields for each line item');
          return;
        }
      }

      this.isSaving = true;
      try {
        let response;

        if (this.isEditMode) {
          response = await axios.put(
            `/purchase-orders/${this.purchaseOrder.po_id}`,
            this.purchaseOrder
          );
        } else {
          response = await axios.post('/purchase-orders', this.purchaseOrder);
        }

        if (response.data.status === 'success') {
          alert(this.isEditMode ? 'Purchase order updated successfully' : 'Purchase order created successfully');
          this.$router.push(`/purchasing/orders/${response.data.data.po_id}`);
        }
      } catch (error) {
        console.error('Error saving purchase order:', error);
        if (error.response && error.response.data && error.response.data.message) {
          alert(`Error: ${error.response.data.message}`);
        } else {
          alert('An error occurred while saving the purchase order');
        }
      } finally {
        this.isSaving = false;
      }
    }
  }
};
</script>

<style scoped>
.purchase-order-form {
  padding: 1rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.header-left {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.header-left h1 {
  margin: 0;
  font-size: 1.5rem;
  color: var(--gray-800);
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--gray-600);
  text-decoration: none;
  font-size: 0.875rem;
}

.back-link:hover {
  color: var(--primary-color);
}

.alert-card {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  background-color: #e0f2fe;
  border-left: 4px solid #0ea5e9;
  border-radius: 0.375rem;
  margin-bottom: 1.5rem;
}

.alert-icon {
  color: #0ea5e9;
  font-size: 1.25rem;
}

.alert-content p {
  margin: 0;
  color: var(--gray-700);
}

.card {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  margin-bottom: 1.5rem;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--gray-200);
}

.card-header h2 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--gray-800);
}

.card-body {
  padding: 1.5rem;
}

.form-row {
  display: flex;
  flex-wrap: wrap;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.form-group {
  flex: 1;
  min-width: 200px;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.form-group label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--gray-700);
}

.form-group input,
.form-group select {
  padding: 0.625rem;
  border: 1px solid var(--gray-300);
  border-radius: 0.375rem;
  font-size: 0.875rem;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
}

.form-group input:disabled,
.form-group select:disabled {
  background-color: var(--gray-100);
  color: var(--gray-500);
  cursor: not-allowed;
}

.required {
  color: var(--danger-color);
}

.table-responsive {
  overflow-x: auto;
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
}

.data-table tbody tr:hover {
  background-color: var(--gray-50);
}

.data-table tfoot td {
  padding: 0.75rem 1rem;
  border-top: 1px solid var(--gray-200);
}

.data-table input,
.data-table select {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid var(--gray-200);
  border-radius: 0.375rem;
  font-size: 0.875rem;
}

.data-table input:focus,
.data-table select:focus {
  border-color: var(--primary-color);
  outline: none;
}

.data-table input:disabled,
.data-table select:disabled {
  background-color: var(--gray-100);
  color: var(--gray-500);
  cursor: not-allowed;
}

.data-table .read-only {
  background-color: var(--gray-50);
  cursor: not-allowed;
}

/* Styles for searchable dropdown */
.dropdown-container {
  position: relative;
  width: 100%;
}

.item-search {
  width: 100%;
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  z-index: 1000;
  background-color: white;
  border: 1px solid var(--gray-300);
  border-top: none;
  border-radius: 0 0 0.375rem 0.375rem;
  max-height: 200px;
  overflow-y: auto;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.dropdown-item {
  padding: 0.5rem 0.75rem;
  cursor: pointer;
  border-bottom: 1px solid var(--gray-100);
  font-size: 0.875rem;
}

.dropdown-item:hover {
  background-color: var(--gray-50);
}

.dropdown-item:last-child {
  border-bottom: none;
}

.dropdown-item.text-muted {
  color: var(--gray-500);
  cursor: default;
}

.dropdown-item.text-muted:hover {
  background-color: transparent;
}

.empty-message {
  text-align: center;
  color: var(--gray-500);
  padding: 2rem 0;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1rem;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
}

.btn-primary {
  background-color: var(--primary-color);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: var(--primary-dark);
}

.btn-secondary {
  background-color: var(--gray-200);
  color: var(--gray-700);
}

.btn-secondary:hover:not(:disabled) {
  background-color: var(--gray-300);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  border-radius: 0.375rem;
  border: none;
  cursor: pointer;
}

.btn-danger {
  background-color: #fee2e2;
  color: var(--danger-color);
}

.btn-danger:hover:not(:disabled) {
  background-color: #fecaca;
}

.text-right {
  text-align: right;
}

.total-value {
  font-weight: 600;
  color: var(--gray-800);
}

.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 0;
  text-align: center;
  color: var(--gray-500);
}

.loading i {
  font-size: 2rem;
  margin-bottom: 1rem;
  color: var(--primary-color);
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

.mt-4 {
  margin-top: 1.5rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .form-group {
    min-width: 100%;
  }

  .form-actions {
    flex-direction: column-reverse;
  }

  .form-actions .btn {
    width: 100%;
  }

  .card-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .card-header .btn {
    width: 100%;
  }

  .dropdown-menu {
    position: fixed;
    left: 1rem;
    right: 1rem;
    max-height: 150px;
  }
}
</style>