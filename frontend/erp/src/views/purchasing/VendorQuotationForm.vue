<!-- Enhanced Vendor Quotation Form with Multi-Currency Support -->
<template>
  <div class="quotation-form-container">
    <div class="page-header">
      <h1>{{ isEditMode ? 'Edit Vendor Quotation' : 'Create Vendor Quotation' }}</h1>
      <router-link to="/purchasing/quotations" class="btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to List
      </router-link>
    </div>

    <div v-if="loading" class="loading-container">
      <i class="fas fa-spinner fa-spin"></i> Loading...
    </div>

    <form v-else @submit.prevent="saveQuotation" class="quotation-form">
      <!-- Basic Information Card -->
      <div class="form-card">
        <h2 class="card-title">
          <i class="fas fa-file-alt"></i> Quotation Information
        </h2>
        
        <div class="form-row">
          <div class="form-group">
            <label for="rfq_id">
              <i class="fas fa-clipboard-list"></i> Request for Quotation
            </label>
            <select 
              id="rfq_id" 
              v-model="form.rfq_id" 
              required 
              :disabled="isEditMode"
              @change="loadRfqDetails"
              class="form-control"
            >
              <option value="" disabled>Select a Request for Quotation</option>
              <option v-for="rfq in rfqOptions" :key="rfq.rfq_id" :value="rfq.rfq_id">
                {{ rfq.rfq_number }} - {{ rfq.title }}
              </option>
            </select>
            <div v-if="errors.rfq_id" class="error-message">{{ errors.rfq_id[0] }}</div>
          </div>
          
          <div class="form-group">
            <label for="vendor_id">
              <i class="fas fa-building"></i> Vendor
            </label>
            <select 
              id="vendor_id" 
              v-model="form.vendor_id" 
              required
              :disabled="isEditMode"
              @change="handleVendorChange"
              class="form-control"
            >
              <option value="" disabled>Select a Vendor</option>
              <option v-for="vendor in vendorOptions" :key="vendor.vendor_id" :value="vendor.vendor_id">
                {{ vendor.name }} - {{ vendor.vendor_code }}
              </option>
            </select>
            <div v-if="errors.vendor_id" class="error-message">{{ errors.vendor_id[0] }}</div>
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label for="quotation_date">
              <i class="fas fa-calendar"></i> Quotation Date
            </label>
            <input 
              type="date" 
              id="quotation_date" 
              v-model="form.quotation_date" 
              required
              class="form-control"
            />
            <div v-if="errors.quotation_date" class="error-message">{{ errors.quotation_date[0] }}</div>
          </div>
          
          <div class="form-group">
            <label for="validity_date">
              <i class="fas fa-clock"></i> Validity Date
            </label>
            <input 
              type="date" 
              id="validity_date" 
              v-model="form.validity_date" 
              required
              :min="form.quotation_date"
              class="form-control"
            />
            <div v-if="errors.validity_date" class="error-message">{{ errors.validity_date[0] }}</div>
          </div>
        </div>
      </div>

      <!-- Multi-Currency Information Card -->
      <div class="form-card currency-card">
        <h2 class="card-title">
          <i class="fas fa-coins"></i> Currency & Exchange Rate Information
        </h2>
        
        <div class="form-row">
          <div class="form-group">
            <label for="currency_code">
              <i class="fas fa-money-bill-wave"></i> Quotation Currency
            </label>
            <select 
              id="currency_code" 
              v-model="form.currency_code" 
              required
              @change="handleCurrencyChange"
              class="form-control"
            >
              <option value="" disabled>Select Currency</option>
              <option v-for="currency in currencyOptions" :key="currency.code" :value="currency.code">
                {{ currency.code }} - {{ currency.name }}
              </option>
            </select>
            <div v-if="errors.currency_code" class="error-message">{{ errors.currency_code[0] }}</div>
          </div>
          
          <div class="form-group">
            <label for="exchange_rate">
              <i class="fas fa-exchange-alt"></i> Exchange Rate to {{ baseCurrency }}
            </label>
            <div class="exchange-rate-input">
              <input 
                type="number" 
                id="exchange_rate" 
                v-model.number="form.exchange_rate" 
                required
                min="0.000001"
                step="0.000001"
                class="form-control"
                @input="calculateBaseCurrencyAmounts"
              />
              <button 
                type="button" 
                @click="fetchCurrentExchangeRate" 
                class="btn-fetch-rate"
                :disabled="!form.currency_code || form.currency_code === baseCurrency"
                title="Fetch current exchange rate"
              >
                <i class="fas fa-sync-alt" :class="{ 'fa-spin': fetchingRate }"></i>
              </button>
            </div>
            <small class="exchange-rate-info">
              1 {{ form.currency_code || 'XXX' }} = {{ form.exchange_rate || 0 }} {{ baseCurrency }}
            </small>
            <div v-if="errors.exchange_rate" class="error-message">{{ errors.exchange_rate[0] }}</div>
          </div>
        </div>

        <!-- Currency Summary -->
        <div v-if="form.currency_code && form.currency_code !== baseCurrency" class="currency-summary">
          <div class="summary-item">
            <span class="label">Quotation Currency:</span>
            <span class="value">{{ form.currency_code }}</span>
          </div>
          <div class="summary-item">
            <span class="label">Base Currency:</span>
            <span class="value">{{ baseCurrency }}</span>
          </div>
          <div class="summary-item">
            <span class="label">Exchange Rate Date:</span>
            <span class="value">{{ formatDate(form.quotation_date) }}</span>
          </div>
        </div>
      </div>

      <!-- Line Items Card -->
      <div class="form-card">
        <h2 class="card-title">
          <i class="fas fa-list"></i> Quotation Items
        </h2>
        
        <div v-if="form.lines && form.lines.length > 0" class="table-wrapper">
          <table class="line-items-table">
            <thead>
              <tr>
                <th>Item Code</th>
                <th>Item Name</th>
                <th>Quantity</th>
                <th>UOM</th>
                <th>Unit Price ({{ form.currency_code || 'XXX' }})</th>
                <th v-if="showBaseCurrency">Unit Price ({{ baseCurrency }})</th>
                <th>Delivery Date</th>
                <th>Subtotal ({{ form.currency_code || 'XXX' }})</th>
                <th v-if="showBaseCurrency">Subtotal ({{ baseCurrency }})</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(line, index) in form.lines" :key="index" class="line-item-row">
                <td class="item-code">
                  {{ getItemCode(line.item_id) }}
                </td>
                <td class="item-name">
                  <div class="item-info">
                    <strong>{{ getItemName(line.item_id) }}</strong>
                    <small class="item-description">{{ getItemDescription(line.item_id) }}</small>
                  </div>
                </td>
                <td>
                  <input 
                    type="number" 
                    v-model.number="line.quantity" 
                    required 
                    min="0.01"
                    step="0.01"
                    :disabled="true"
                    class="form-control quantity-input"
                  />
                </td>
                <td>
                  <select 
                    v-model="line.uom_id" 
                    required
                    :disabled="true"
                    class="form-control uom-select"
                  >
                    <option v-for="uom in uomOptions" :key="uom.uom_id" :value="uom.uom_id">
                      {{ uom.symbol }} - {{ uom.name }}
                    </option>
                  </select>
                </td>
                <td>
                  <input 
                    type="number" 
                    v-model.number="line.unit_price" 
                    required 
                    min="0"
                    step="0.01"
                    @input="calculateLineAmounts(index)"
                    class="form-control price-input"
                  />
                </td>
                <td v-if="showBaseCurrency" class="base-currency-col">
                  <span class="base-currency-amount">
                    {{ formatCurrency(calculateBaseCurrencyUnitPrice(line.unit_price), baseCurrency) }}
                  </span>
                </td>
                <td>
                  <input 
                    type="date" 
                    v-model="line.delivery_date"
                    :min="form.quotation_date"
                    class="form-control date-input"
                  />
                </td>
                <td class="subtotal-cell">
                  <strong>{{ formatCurrency(calculateLineSubtotal(line), form.currency_code) }}</strong>
                </td>
                <td v-if="showBaseCurrency" class="base-currency-col">
                  <strong class="base-currency-amount">
                    {{ formatCurrency(calculateBaseCurrencySubtotal(line), baseCurrency) }}
                  </strong>
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="total-row">
                <td :colspan="showBaseCurrency ? 8 : 7" class="text-right">
                  <strong>Total Amount</strong>
                </td>
                <td class="total-cell">
                  <strong>{{ formatCurrency(calculateTotal(), form.currency_code) }}</strong>
                </td>
                <td v-if="showBaseCurrency" class="base-currency-col">
                  <strong class="base-currency-amount">
                    {{ formatCurrency(calculateBaseCurrencyTotal(), baseCurrency) }}
                  </strong>
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
        <div v-else class="no-items">
          <i class="fas fa-inbox"></i>
          <p>No items available. Please select an RFQ to load items.</p>
        </div>
      </div>

      <!-- Additional Information Card -->
      <div class="form-card">
        <h2 class="card-title">
          <i class="fas fa-info-circle"></i> Additional Information
        </h2>
        
        <div class="form-group">
          <label for="notes">
            <i class="fas fa-sticky-note"></i> Notes / Comments
          </label>
          <textarea 
            id="notes"
            v-model="form.notes"
            rows="3"
            class="form-control"
            placeholder="Enter any additional notes or comments..."
          ></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="payment_terms">
              <i class="fas fa-credit-card"></i> Payment Terms
            </label>
            <input 
              type="text" 
              id="payment_terms" 
              v-model="form.payment_terms"
              class="form-control"
              placeholder="e.g., NET 30, COD, etc."
            />
          </div>
          
          <div class="form-group">
            <label for="delivery_terms">
              <i class="fas fa-truck"></i> Delivery Terms
            </label>
            <input 
              type="text" 
              id="delivery_terms" 
              v-model="form.delivery_terms"
              class="form-control"
              placeholder="e.g., FOB, CIF, EXW, etc."
            />
          </div>
        </div>
      </div>
      
      <!-- Form Actions -->
      <div class="form-actions">
        <router-link to="/purchasing/quotations" class="btn btn-secondary">
          <i class="fas fa-times"></i> Cancel
        </router-link>
        <button type="button" @click="saveDraft" class="btn btn-warning" :disabled="submitting">
          <i class="fas fa-save"></i> Save as Draft
        </button>
        <button type="submit" class="btn btn-primary" :disabled="submitting">
          <i class="fas fa-spinner fa-spin" v-if="submitting"></i>
          <i class="fas fa-check" v-else></i>
          {{ isEditMode ? 'Update Quotation' : 'Create Quotation' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'VendorQuotationForm',
  props: {
    id: {
      type: [Number, String],
      required: false
    }
  },
  data() {
    return {
      localId: this.id || null,
      isEditMode: false,
      loading: true,
      submitting: false,
      fetchingRate: false,
      baseCurrency: 'USD', // Base currency dari config
      form: {
        rfq_id: '',
        vendor_id: '',
        quotation_date: '',
        validity_date: '',
        currency_code: 'USD',
        exchange_rate: 1,
        status: 'draft',
        notes: '',
        payment_terms: '',
        delivery_terms: '',
        lines: []
      },
      rfqOptions: [],
      vendorOptions: [],
      itemOptions: [],
      uomOptions: [],
      currencyOptions: [
        { code: 'USD', name: 'US Dollar' },
        { code: 'IDR', name: 'Indonesian Rupiah' },
        { code: 'EUR', name: 'Euro' },
        { code: 'SGD', name: 'Singapore Dollar' },
        { code: 'JPY', name: 'Japanese Yen' },
        { code: 'CNY', name: 'Chinese Yuan' },
        { code: 'GBP', name: 'British Pound' },
        { code: 'AUD', name: 'Australian Dollar' },
        { code: 'CAD', name: 'Canadian Dollar' },
      ],
      rfqDetails: null,
      errors: {}
    };
  },
  computed: {
    formMode() {
      return this.isEditMode ? 'edit' : 'create';
    },
    showBaseCurrency() {
      return this.form.currency_code && this.form.currency_code !== this.baseCurrency;
    }
  },
  methods: {
    // Initialize form based on mode
    initializeForm() {
      this.loading = true;
      this.fetchDropdownOptions();
      
      if (this.localId) {
        this.isEditMode = true;
        this.fetchQuotationDetails();
      } else {
        this.isEditMode = false;
        // Set default dates
        const today = new Date().toISOString().split('T')[0];
        this.form.quotation_date = today;
        
        // Set default validity date (30 days from now)
        const validityDate = new Date();
        validityDate.setDate(validityDate.getDate() + 30);
        this.form.validity_date = validityDate.toISOString().split('T')[0];
        
        this.loading = false;
      }
    },
    
    // Fetch options for dropdowns
    fetchDropdownOptions() {
      Promise.all([
        axios.get('/request-for-quotations?status=sent'),
        axios.get('/vendors?is_active=1'),
        axios.get('/items'),
        axios.get('/uoms')
      ])
      .then(([rfqResponse, vendorResponse, itemsResponse, uomResponse]) => {
        this.rfqOptions = rfqResponse.data.data.data || rfqResponse.data.data;
        this.vendorOptions = vendorResponse.data.data.data || vendorResponse.data.data;
        this.itemOptions = itemsResponse.data.data.data || itemsResponse.data.data;
        this.uomOptions = uomResponse.data.data;
      })
      .catch(error => {
        console.error('Error fetching options:', error);
        this.$toasted.error('Failed to load form options');
      });
    },
    
    // Fetch quotation details for edit mode
fetchQuotationDetails() {
  axios.get(`/vendor-quotations/${this.localId}`)
    .then(response => {
      if (response.data.status === 'success') {
        const quotation = response.data.data;
        
        // Format dates for the form inputs
        this.form = {
          ...quotation,
          quotation_date: quotation.quotation_date ? quotation.quotation_date.split('T')[0] : '',
          validity_date: quotation.validity_date ? quotation.validity_date.split('T')[0] : '',
          currency_code: quotation.currency_code || 'USD',
          exchange_rate: quotation.exchange_rate || 1,
          notes: quotation.notes || '',
          payment_terms: quotation.payment_terms || '',
          delivery_terms: quotation.delivery_terms || '',
          lines: quotation.lines || []
        };
        
        // Ensure date fields are in yyyy-MM-dd format
        if (this.form.quotation_date && this.form.quotation_date.length > 10) {
          this.form.quotation_date = this.form.quotation_date.substring(0, 10);
        }
        if (this.form.validity_date && this.form.validity_date.length > 10) {
          this.form.validity_date = this.form.validity_date.substring(0, 10);
        }

        // Format delivery_date for each line item
        if (this.form.lines && this.form.lines.length > 0) {
          this.form.lines = this.form.lines.map(line => {
            if (line.delivery_date && line.delivery_date.length > 10) {
              return {
                ...line,
                delivery_date: line.delivery_date.substring(0, 10)
              };
            }
            return line;
          });
        }
        
        this.loading = false;
      }
    })
    .catch(error => {
      console.error('Error fetching quotation:', error);
      this.$toasted.error('Failed to load quotation details');
      this.loading = false;
    });
},
    
    // Load RFQ details when RFQ is selected
    loadRfqDetails() {
      if (!this.form.rfq_id) return;
      
      axios.get(`/request-for-quotations/${this.form.rfq_id}`)
        .then(response => {
          this.rfqDetails = response.data.data;
          
          // Copy line items from RFQ
          this.form.lines = this.rfqDetails.lines.map(line => ({
            item_id: line.item_id,
            quantity: line.quantity,
            uom_id: line.uom_id,
            unit_price: 0,
            delivery_date: '',
          }));
          
          this.calculateBaseCurrencyAmounts();
        })
        .catch(error => {
          console.error('Error loading RFQ details:', error);
          this.$toasted.error('Failed to load RFQ details');
        });
    },

    // Handle vendor change
    handleVendorChange() {
      const selectedVendor = this.vendorOptions.find(v => v.vendor_id === this.form.vendor_id);
      if (selectedVendor && selectedVendor.preferred_currency) {
        this.form.currency_code = selectedVendor.preferred_currency;
        this.handleCurrencyChange();
      }
    },

    // Handle currency change
    handleCurrencyChange() {
      if (this.form.currency_code === this.baseCurrency) {
        this.form.exchange_rate = 1;
      } else {
        this.fetchCurrentExchangeRate();
      }
      this.calculateBaseCurrencyAmounts();
    },

// Fetch current exchange rate - FIXED API ENDPOINT
async fetchCurrentExchangeRate() {
  if (!this.form.currency_code || this.form.currency_code === this.baseCurrency) {
    this.form.exchange_rate = 1;
    return;
  }

  this.fetchingRate = true;

  

  try {
    const response = await axios.get(`/accounting/currency-rates/current-rate`, {
      params: {
        from: this.form.currency_code,
        to: this.baseCurrency,
        date: this.form.quotation_date
      }
    });
    this.handleExchangeRateResponse(response);
  } catch (error) {
    console.warn('Could not fetch exchange rate:', error);
    if (this.$toasted) {
      this.$toasted.warning('Could not fetch current exchange rate. Please enter manually.');
    }
  } finally {
    this.fetchingRate = false;
  }
},

handleExchangeRateResponse(response) {
  if (response && response.data && response.data.status === 'success' && response.data.data) {
    this.form.exchange_rate = response.data.data.rate;
    this.calculateBaseCurrencyAmounts();
    if (this.$toasted) {
      this.$toasted.success(`Exchange rate updated: 1 ${this.form.currency_code} = ${this.form.exchange_rate} ${this.baseCurrency}`);
    }
  } else {
    console.warn('Unexpected response format or status:', response);
    if (this.$toasted) {
      this.$toasted.warning('Failed to fetch current exchange rate. Please enter manually.');
    }
  }
},

    // Calculate line amounts
    calculateLineAmounts() {
      // Trigger reactivity for base currency calculations
      this.$forceUpdate();
    },

    // Calculate base currency amounts
    calculateBaseCurrencyAmounts() {
      // This method triggers reactivity for base currency calculations
      this.$forceUpdate();
    },

    // Calculate line subtotal
    calculateLineSubtotal(line) {
      return (parseFloat(line.quantity) || 0) * (parseFloat(line.unit_price) || 0);
    },

    // Calculate base currency unit price
    calculateBaseCurrencyUnitPrice(unitPrice) {
      return (parseFloat(unitPrice) || 0) * (parseFloat(this.form.exchange_rate) || 1);
    },

    // Calculate base currency subtotal
    calculateBaseCurrencySubtotal(line) {
      const subtotal = this.calculateLineSubtotal(line);
      return subtotal * (parseFloat(this.form.exchange_rate) || 1);
    },

    // Calculate total
    calculateTotal() {
      return this.form.lines.reduce((total, line) => {
        return total + this.calculateLineSubtotal(line);
      }, 0);
    },

    // Calculate base currency total
    calculateBaseCurrencyTotal() {
      return this.calculateTotal() * (parseFloat(this.form.exchange_rate) || 1);
    },
    
    // Get item details
    getItemCode(itemId) {
      const item = this.itemOptions.find(i => i.item_id === itemId);
      return item ? item.item_code : '';
    },
    
    getItemName(itemId) {
      const item = this.itemOptions.find(i => i.item_id === itemId);
      return item ? item.name : '';
    },

    getItemDescription(itemId) {
      const item = this.itemOptions.find(i => i.item_id === itemId);
      return item ? item.description : '';
    },
    
    // Format currency for display
    formatCurrency(value, currency) {
      if (value === undefined || value === null || value === 'N/A') return 'N/A';
      
      const formatter = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency || this.form.currency_code || 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
      
      return formatter.format(value);
    },

    // Format date for display
    formatDate(dateString) {
      if (!dateString) return 'N/A';
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
      });
    },
    
    // Save as draft
    saveDraft() {
      this.form.status = 'draft';
      this.saveQuotation();
    },

    // Submit the form - FIXED API ENDPOINTS
saveQuotation() {
  this.submitting = true;
  this.errors = {};
  
  // Prepare form data
  const formData = {
    ...this.form,
    // Ensure numeric values
    exchange_rate: parseFloat(this.form.exchange_rate) || 1,
    lines: this.form.lines.map(line => ({
      ...line,
      quantity: parseFloat(line.quantity) || 0,
      unit_price: parseFloat(line.unit_price) || 0,
      subtotal: this.calculateLineSubtotal(line),
      base_currency_unit_price: this.calculateBaseCurrencyUnitPrice(line.unit_price),
      base_currency_subtotal: this.calculateBaseCurrencySubtotal(line)
    })),
    // Add totals
    total_amount: this.calculateTotal(),
    base_currency_total: this.calculateBaseCurrencyTotal()
  };
  
  // Use appropriate HTTP method based on mode - FIXED API ENDPOINTS
  const method = this.isEditMode ? 'put' : 'post';
  const url = this.isEditMode ? `/vendor-quotations/${this.localId}` : '/vendor-quotations';
  
  axios[method](url, formData)
    .then(response => {
      if (response && response.data && response.data.status === 'success' && response.data.data && response.data.data.quotation_id) {
        this.$toasted.success(this.isEditMode ? 'Quotation updated successfully' : 'Quotation created successfully');
        this.$router.push(`/purchasing/quotations/${response.data.data.quotation_id}`);
      } else if (response && response.data && response.data.message) {
        this.$toasted.error(response.data.message);
      } else {
        this.$toasted.error('Failed to save quotation due to unexpected response format');
      }
    })
    .catch(error => {
      console.error('API Error:', error);
      
      if (error.response && error.response.data && error.response.data.errors) {
        this.errors = error.response.data.errors;
        if (this.$toasted) {
          this.$toasted.error('Please correct the errors in the form');
        }
      } else {
        if (this.$toasted) {
          this.$toasted.error('An error occurred while saving');
        }
      }
    })
    .finally(() => {
      this.submitting = false;
    });
}
  },
  mounted() {
    this.initializeForm();
  },
  watch: {
    // Watch for route changes
    '$route'(to, from) {
      if (to.params.id !== from.params.id) {
        this.localId = to.params.id;
        this.initializeForm();
      }
    },
    
    // Watch exchange rate changes
    'form.exchange_rate'() {
      this.calculateBaseCurrencyAmounts();
    }
  }
};
</script>

<style scoped>
.quotation-form-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 1rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 3px solid #e5e7eb;
}

.page-header h1 {
  margin: 0;
  font-size: 2rem;
  color: #1f2937;
  font-weight: 700;
}

.btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background-color: #f8fafc;
  color: #64748b;
  border: 2px solid #e2e8f0;
  border-radius: 0.75rem;
  font-size: 0.875rem;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.2s;
}

.btn-secondary:hover {
  background-color: #e2e8f0;
  border-color: #cbd5e1;
  transform: translateY(-1px);
  text-decoration: none;
}

.loading-container {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 4rem;
  font-size: 1.25rem;
  color: #6b7280;
}

.form-card {
  background: white;
  border-radius: 1rem;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
}

.card-title {
  margin: 0 0 1.5rem 0;
  font-size: 1.25rem;
  color: #1f2937;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f3f4f6;
}

.card-title i {
  color: #3b82f6;
}

.currency-card {
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
  border-color: #0284c7;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.form-group label i {
  color: #6b7280;
  width: 1rem;
}

.form-control {
  padding: 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  transition: all 0.2s;
  background-color: white;
}

.form-control:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.exchange-rate-input {
  display: flex;
  gap: 0.5rem;
}

.exchange-rate-input .form-control {
  flex: 1;
}

.btn-fetch-rate {
  padding: 0.75rem;
  background-color: #3b82f6;
  color: white;
  border: none;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all 0.2s;
  min-width: 3rem;
}

.btn-fetch-rate:hover:not(:disabled) {
  background-color: #2563eb;
  transform: scale(1.05);
}

.btn-fetch-rate:disabled {
  background-color: #9ca3af;
  cursor: not-allowed;
}

.exchange-rate-info {
  color: #6b7280;
  font-size: 0.75rem;
  margin-top: 0.25rem;
  font-weight: 500;
}

.currency-summary {
  background: rgba(255, 255, 255, 0.7);
  padding: 1rem;
  border-radius: 0.5rem;
  margin-top: 1rem;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}

.summary-item {
  text-align: center;
}

.summary-item .label {
  display: block;
  font-size: 0.75rem;
  color: #6b7280;
  font-weight: 500;
  margin-bottom: 0.25rem;
}

.summary-item .value {
  font-weight: 600;
  color: #1f2937;
  font-size: 0.875rem;
}

.table-wrapper {
  overflow-x: auto;
  border-radius: 0.5rem;
  border: 1px solid #e5e7eb;
}

.line-items-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
}

.line-items-table th {
  background: #f8fafc;
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  border-bottom: 2px solid #e5e7eb;
  white-space: nowrap;
}

.line-items-table td {
  padding: 1rem;
  border-bottom: 1px solid #f3f4f6;
  vertical-align: top;
}

.line-item-row:hover {
  background-color: #f8fafc;
}

.item-info {
  min-width: 200px;
}

.item-description {
  display: block;
  color: #6b7280;
  font-size: 0.75rem;
  margin-top: 0.25rem;
}

.quantity-input, .price-input {
  width: 100px;
}

.uom-select {
  width: 120px;
}

.date-input {
  width: 140px;
}

.base-currency-col {
  background-color: #f0f9ff;
  font-style: italic;
}

.base-currency-amount {
  color: #0369a1;
  font-weight: 500;
}

.subtotal-cell, .total-cell {
  text-align: right;
  font-weight: 600;
  color: #1f2937;
}

.total-row {
  background-color: #f8fafc;
}

.total-row td {
  border-top: 2px solid #e5e7eb;
  font-weight: 600;
}

.no-items {
  text-align: center;
  padding: 3rem;
  color: #6b7280;
}

.no-items i {
  font-size: 3rem;
  margin-bottom: 1rem;
  color: #d1d5db;
}

.error-message {
  color: #dc2626;
  font-size: 0.75rem;
  margin-top: 0.25rem;
  font-weight: 500;
}

.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  padding: 2rem;
  background: #f8fafc;
  border-radius: 0.75rem;
  border: 1px solid #e5e7eb;
}

.btn {
  padding: 0.75rem 2rem;
  border-radius: 0.5rem;
  font-weight: 600;
  font-size: 0.875rem;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
}

.btn-primary {
  background-color: #3b82f6;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: #2563eb;
  transform: translateY(-1px);
}

.btn-warning {
  background-color: #f59e0b;
  color: white;
}

.btn-warning:hover:not(:disabled) {
  background-color: #d97706;
  transform: translateY(-1px);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

/* Responsive */
@media (max-width: 768px) {
  .quotation-form-container {
    padding: 0.5rem;
  }
  
  .form-row {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .currency-summary {
    grid-template-columns: 1fr;
  }
  
  .form-actions {
    flex-direction: column;
  }
  
  .page-header {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }
}
</style>