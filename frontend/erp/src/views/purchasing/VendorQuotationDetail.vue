<!-- Enhanced Vendor Quotation Detail with Multi-Currency Support -->
<template>
  <div class="quotation-detail-container">
    <div class="page-header">
      <div class="header-left">
        <h1>Vendor Quotation Detail</h1>
        <div class="breadcrumb">
          <router-link to="/purchasing/quotations">Quotations</router-link>
          <i class="fas fa-chevron-right"></i>
          <span>{{ quotation.quotation_id || 'Loading...' }}</span>
        </div>
      </div>
      <div class="header-actions">
        <button 
          v-if="quotation.status === 'received'" 
          @click="showCurrencyConverter = true"
          class="btn btn-info"
          title="Convert Currency"
        >
          <i class="fas fa-exchange-alt"></i> Convert Currency
        </button>
        <router-link 
          v-if="quotation.status === 'received'" 
          :to="`/purchasing/quotations/${quotationId}/edit`" 
          class="btn btn-warning"
        >
          <i class="fas fa-edit"></i> Edit
        </router-link>
        <router-link to="/purchasing/quotations" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i> Back to List
        </router-link>
      </div>
    </div>

    <div v-if="loading" class="loading-container">
      <i class="fas fa-spinner fa-spin"></i> Loading quotation details...
    </div>

    <div v-else class="detail-content">
      <!-- Status and Basic Info Card -->
      <div class="detail-card status-card">
        <div class="card-header">
          <h2><i class="fas fa-info-circle"></i> Quotation Status</h2>
          <div class="status-actions">
            <span class="status-badge" :class="getStatusClass(quotation.status)">
              {{ capitalizeFirstLetter(quotation.status) }}
            </span>
            <div v-if="quotation.status === 'received'" class="action-buttons">
              <button 
                @click="changeStatus('accepted')" 
                class="btn btn-success btn-sm"
                :disabled="processingAction"
              >
                <i class="fas fa-check"></i> Accept
              </button>
              <button 
                @click="changeStatus('rejected')" 
                class="btn btn-danger btn-sm"
                :disabled="processingAction"
              >
                <i class="fas fa-times"></i> Reject
              </button>
            </div>
          </div>
        </div>
        
        <div class="status-details">
          <div class="detail-grid">
            <div class="detail-item">
              <div class="detail-label">Quotation ID</div>
              <div class="detail-value">#{{ quotation.quotation_id }}</div>
            </div>
            <div class="detail-item">
              <div class="detail-label">Quotation Date</div>
              <div class="detail-value">{{ formatDate(quotation.quotation_date) }}</div>
            </div>
            <div class="detail-item">
              <div class="detail-label">Valid Until</div>
              <div class="detail-value">{{ formatDate(quotation.validity_date) }}</div>
            </div>
            <div class="detail-item">
              <div class="detail-label">Days Remaining</div>
              <div class="detail-value" :class="getValidityClass()">
                {{ getDaysRemaining() }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="main-content">
        <!-- Currency Information Card -->
        <div class="detail-card currency-card">
          <h2 class="card-title">
            <i class="fas fa-coins"></i> Currency Information
          </h2>
          
          <div class="currency-info-grid">
            <div class="currency-primary">
              <div class="currency-label">Quotation Currency</div>
              <div class="currency-value">
                <span class="currency-code">{{ quotation.currency_code || 'USD' }}</span>
                <span class="currency-name">{{ getCurrencyName(quotation.currency_code) }}</span>
              </div>
            </div>
            
            <div class="exchange-rate-info" v-if="quotation.currency_code !== baseCurrency">
              <div class="currency-label">Exchange Rate</div>
              <div class="currency-value">
                1 {{ quotation.currency_code }} = {{ formatNumber(quotation.exchange_rate) }} {{ baseCurrency }}
              </div>
              <small class="rate-date">Rate Date: {{ formatDate(quotation.quotation_date) }}</small>
            </div>
            
            <div class="total-amounts">
              <div class="amount-row">
                <span class="amount-label">Total ({{ quotation.currency_code }}):</span>
                <span class="amount-value primary">
                  {{ formatCurrency(quotation.total_amount, quotation.currency_code) }}
                </span>
              </div>
              <div v-if="quotation.currency_code !== baseCurrency" class="amount-row">
                <span class="amount-label">Total ({{ baseCurrency }}):</span>
                <span class="amount-value secondary">
                  {{ formatCurrency(quotation.base_currency_total, baseCurrency) }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Vendor Information Card -->
        <div class="detail-card">
          <h2 class="card-title">
            <i class="fas fa-building"></i> Vendor Information
          </h2>
          <div class="detail-grid" v-if="quotation.vendor">
            <div class="detail-item">
              <div class="detail-label">Vendor Name</div>
              <div class="detail-value">{{ quotation.vendor.name }}</div>
            </div>
            <div class="detail-item">
              <div class="detail-label">Vendor Code</div>
              <div class="detail-value">{{ quotation.vendor.vendor_code || 'N/A' }}</div>
            </div>
            <div class="detail-item">
              <div class="detail-label">Contact Person</div>
              <div class="detail-value">{{ quotation.vendor.contact_person || 'N/A' }}</div>
            </div>
            <div class="detail-item">
              <div class="detail-label">Email</div>
              <div class="detail-value">
                <a v-if="quotation.vendor.email" :href="`mailto:${quotation.vendor.email}`">
                  {{ quotation.vendor.email }}
                </a>
                <span v-else>N/A</span>
              </div>
            </div>
            <div class="detail-item">
              <div class="detail-label">Phone</div>
              <div class="detail-value">{{ quotation.vendor.phone || 'N/A' }}</div>
            </div>
            <div class="detail-item">
              <div class="detail-label">Preferred Currency</div>
              <div class="detail-value">{{ quotation.vendor.preferred_currency || 'N/A' }}</div>
            </div>
          </div>
          <div v-else class="empty-vendor">
            Vendor information not available
          </div>
        </div>

        <!-- RFQ Information Card -->
        <div class="detail-card rfq-card">
          <h2 class="card-title">
            <i class="fas fa-clipboard-list"></i> Request for Quotation
          </h2>
          <div class="detail-grid" v-if="quotation.requestForQuotation">
            <div class="detail-item">
              <div class="detail-label">RFQ Number</div>
              <div class="detail-value">{{ quotation.requestForQuotation.rfq_number }}</div>
            </div>
            <div class="detail-item">
              <div class="detail-label">RFQ Date</div>
              <div class="detail-value">{{ formatDate(quotation.requestForQuotation.rfq_date) }}</div>
            </div>
            <div class="detail-item">
              <div class="detail-label">RFQ Status</div>
              <div class="detail-value">
                <span class="status-badge" :class="rfqStatusClass">
                  {{ capitalizeFirstLetter(quotation.requestForQuotation.status) }}
                </span>
              </div>
            </div>
            <div class="detail-item">
              <div class="detail-label">View RFQ</div>
              <div class="detail-value">
                <router-link :to="`/purchasing/rfqs/${quotation.rfq_id}`" class="link-button">
                  <i class="fas fa-external-link-alt"></i> Open RFQ
                </router-link>
              </div>
            </div>
          </div>
          <div v-else class="empty-rfq">
            RFQ information not available
          </div>
        </div>
      </div>

      <!-- Quotation Lines Card -->
      <div class="detail-card">
        <h2 class="card-title">
          <i class="fas fa-list"></i> Quotation Items
        </h2>
        <div class="table-responsive">
          <table class="lines-table">
            <thead>
              <tr>
                <th>Item Code</th>
                <th>Description</th>
                <th>Quantity</th>
                <th>UOM</th>
                <th>Unit Price ({{ quotation.currency_code }})</th>
                <th v-if="showBaseCurrency">Unit Price ({{ baseCurrency }})</th>
                <th>Delivery Date</th>
                <th>Subtotal ({{ quotation.currency_code }})</th>
                <th v-if="showBaseCurrency">Subtotal ({{ baseCurrency }})</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="line in quotation.lines" :key="line.line_id" class="line-row">
                <td class="item-code">{{ line.item ? line.item.item_code : 'N/A' }}</td>
                <td class="item-description">
                  <div class="item-info">
                    <strong>{{ line.item ? line.item.name : 'N/A' }}</strong>
                    <small v-if="line.item && line.item.description">
                      {{ line.item.description }}
                    </small>
                  </div>
                </td>
                <td class="quantity">{{ formatNumber(line.quantity) }}</td>
                <td class="uom">{{ line.unitOfMeasure ? line.unitOfMeasure.symbol : 'N/A' }}</td>
                <td class="unit-price">{{ formatCurrency(line.unit_price, quotation.currency_code) }}</td>
                <td v-if="showBaseCurrency" class="base-currency-col">
                  <span class="base-amount">
                    {{ formatCurrency(line.base_currency_unit_price, baseCurrency) }}
                  </span>
                </td>
                <td class="delivery-date">{{ formatDate(line.delivery_date) }}</td>
                <td class="subtotal">
                  <strong>{{ formatCurrency(line.subtotal, quotation.currency_code) }}</strong>
                </td>
                <td v-if="showBaseCurrency" class="base-currency-col">
                  <strong class="base-amount">
                    {{ formatCurrency(line.base_currency_subtotal, baseCurrency) }}
                  </strong>
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="total-row">
                <td :colspan="showBaseCurrency ? 8 : 7" class="text-right">
                  <strong>Total Amount</strong>
                </td>
                <td class="total-amount">
                  <strong>{{ formatCurrency(quotation.total_amount, quotation.currency_code) }}</strong>
                </td>
                <td v-if="showBaseCurrency" class="base-currency-col">
                  <strong class="base-amount">
                    {{ formatCurrency(quotation.base_currency_total, baseCurrency) }}
                  </strong>
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Additional Information Card -->
      <div v-if="quotation.notes || quotation.payment_terms || quotation.delivery_terms" class="detail-card">
        <h2 class="card-title">
          <i class="fas fa-info-circle"></i> Additional Information
        </h2>
        <div class="additional-info">
          <div v-if="quotation.notes" class="info-section">
            <h4>Notes</h4>
            <p>{{ quotation.notes }}</p>
          </div>
          <div class="info-row">
            <div v-if="quotation.payment_terms" class="info-item">
              <strong>Payment Terms:</strong>
              <span>{{ quotation.payment_terms }}</span>
            </div>
            <div v-if="quotation.delivery_terms" class="info-item">
              <strong>Delivery Terms:</strong>
              <span>{{ quotation.delivery_terms }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Currency Converter Modal -->
    <div v-if="showCurrencyConverter" class="modal-overlay" @click="showCurrencyConverter = false">
      <div class="modal-container" @click.stop>
        <div class="modal-header">
          <h3><i class="fas fa-exchange-alt"></i> Convert Currency</h3>
          <button @click="showCurrencyConverter = false" class="close-button">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Convert to Currency:</label>
            <select v-model="conversionCurrency" class="form-control">
              <option value="" disabled>Select Currency</option>
              <option v-for="currency in currencyOptions" :key="currency.code" :value="currency.code">
                {{ currency.code }} - {{ currency.name }}
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>
              <input type="checkbox" v-model="useQuotationDate" />
              Use quotation date for exchange rate
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="showCurrencyConverter = false" class="btn btn-secondary">Cancel</button>
          <button @click="convertCurrency" class="btn btn-primary" :disabled="!conversionCurrency || converting">
            <i class="fas fa-spinner fa-spin" v-if="converting"></i>
            Convert
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'VendorQuotationDetail',
  props: {
    id: {
      type: [Number, String],
      required: true
    }
  },
  data() {
    return {
      quotationId: this.id,
      quotation: {},
      loading: true,
      processingAction: false,
      showCurrencyConverter: false,
      converting: false,
      conversionCurrency: '',
      useQuotationDate: true,
      baseCurrency: 'USD',
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
      ]
    };
  },
  computed: {
    rfqStatusClass() {
      if (!this.quotation.requestForQuotation) return '';
      return this.getStatusClass(this.quotation.requestForQuotation.status);
    },
    showBaseCurrency() {
      return this.quotation.currency_code && this.quotation.currency_code !== this.baseCurrency;
    }
  },
  methods: {
    // Fetch quotation details
    fetchQuotation() {
      this.loading = true;
      
      axios.get(`/vendor-quotations/${this.quotationId}`)
        .then(response => {
          if (response.data.status === 'success') {
            this.quotation = response.data.data;
          } else {
            this.$toasted.error('Failed to load quotation details');
          }
        })
        .catch(error => {
          console.error('Error fetching quotation:', error);
          this.$toasted.error('An error occurred while loading quotation details');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    
    // Change quotation status
    changeStatus(newStatus) {
      this.processingAction = true;
      
      axios.patch(`/vendor-quotations/${this.quotationId}/status`, { status: newStatus })
        .then(response => {
          if (response.data.status === 'success') {
            this.quotation.status = newStatus;
            this.$toasted.success(`Quotation ${newStatus} successfully`);
          } else {
            this.$toasted.error(`Failed to ${newStatus} quotation`);
          }
        })
        .catch(error => {
          console.error('Error changing status:', error);
          this.$toasted.error('An error occurred while updating status');
        })
        .finally(() => {
          this.processingAction = false;
        });
    },

    // Convert currency
    convertCurrency() {
      if (!this.conversionCurrency) return;
      
      this.converting = true;
      
      axios.post(`/vendor-quotations/${this.quotationId}/convert-currency`, {
        currency_code: this.conversionCurrency,
        use_quotation_date: this.useQuotationDate
      })
      .then(response => {
        if (response.data.status === 'success') {
          this.quotation = response.data.data;
          this.showCurrencyConverter = false;
          this.$toasted.success('Currency converted successfully');
        } else {
          this.$toasted.error('Failed to convert currency');
        }
      })
      .catch(error => {
        console.error('Error converting currency:', error);
        this.$toasted.error('An error occurred during currency conversion');
      })
      .finally(() => {
        this.converting = false;
      });
    },
    
    // Get status class for badges
    getStatusClass(status) {
      switch (status) {
        case 'received': return 'status-info';
        case 'accepted': return 'status-success';
        case 'rejected': return 'status-danger';
        case 'sent': return 'status-warning';
        default: return 'status-secondary';
      }
    },

    // Get validity class based on days remaining
    getValidityClass() {
      const days = this.getDaysRemainingNumber();
      if (days < 0) return 'validity-expired';
      if (days <= 3) return 'validity-warning';
      return 'validity-ok';
    },

    // Get days remaining as number
    getDaysRemainingNumber() {
      if (!this.quotation.validity_date) return 0;
      const validityDate = new Date(this.quotation.validity_date);
      const today = new Date();
      const diffTime = validityDate - today;
      return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    },

    // Get days remaining as formatted string
    getDaysRemaining() {
      const days = this.getDaysRemainingNumber();
      if (days < 0) return `Expired ${Math.abs(days)} days ago`;
      if (days === 0) return 'Expires today';
      if (days === 1) return '1 day remaining';
      return `${days} days remaining`;
    },

    // Get currency name
    getCurrencyName(code) {
      const currency = this.currencyOptions.find(c => c.code === code);
      return currency ? currency.name : '';
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
    
    // Format number for display
    formatNumber(value) {
      if (value === undefined || value === null) return 'N/A';
      return new Intl.NumberFormat('en-US').format(value);
    },
    
    // Format currency for display
    formatCurrency(value, currency) {
      if (value === undefined || value === null || value === 'N/A') return 'N/A';
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency || 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(value);
    },
    
    // Capitalize first letter of a string
    capitalizeFirstLetter(string) {
      if (!string) return '';
      return string.charAt(0).toUpperCase() + string.slice(1);
    }
  },
  mounted() {
    this.fetchQuotation();
  },
  watch: {
    '$route'(to, from) {
      if (to.params.id !== from.params.id) {
        this.quotationId = to.params.id;
        this.fetchQuotation();
      }
    }
  }
};
</script>

<style scoped>
.quotation-detail-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 1rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 3px solid #e5e7eb;
}

.header-left h1 {
  margin: 0 0 0.5rem 0;
  font-size: 2rem;
  color: #1f2937;
  font-weight: 700;
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #6b7280;
  font-size: 0.875rem;
}

.breadcrumb a {
  color: #3b82f6;
  text-decoration: none;
}

.breadcrumb a:hover {
  text-decoration: underline;
}

.header-actions {
  display: flex;
  gap: 0.75rem;
}

.btn {
  padding: 0.75rem 1.5rem;
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

.btn-secondary {
  background-color: #f8fafc;
  color: #64748b;
  border: 2px solid #e2e8f0;
}

.btn-secondary:hover {
  background-color: #e2e8f0;
  text-decoration: none;
}

.btn-warning {
  background-color: #f59e0b;
  color: white;
}

.btn-warning:hover {
  background-color: #d97706;
  text-decoration: none;
}

.btn-info {
  background-color: #06b6d4;
  color: white;
}

.btn-info:hover {
  background-color: #0891b2;
}

.btn-success {
  background-color: #10b981;
  color: white;
}

.btn-success:hover:not(:disabled) {
  background-color: #059669;
}

.btn-danger {
  background-color: #ef4444;
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background-color: #dc2626;
}

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.75rem;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.loading-container {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 4rem;
  font-size: 1.25rem;
  color: #6b7280;
}

.detail-card {
  background: white;
  border-radius: 1rem;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
}

.status-card {
  border-left: 4px solid #3b82f6;
}

.currency-card {
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
  border-color: #0284c7;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f3f4f6;
}

.card-header h2 {
  margin: 0;
  font-size: 1.25rem;
  color: #1f2937;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
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

.status-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.status-badge {
  padding: 0.5rem 1rem;
  border-radius: 1rem;
  font-size: 0.875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.status-info {
  background: #dbeafe;
  color: #1e40af;
}

.status-success {
  background: #d1fae5;
  color: #065f46;
}

.status-danger {
  background: #fee2e2;
  color: #991b1b;
}

.status-warning {
  background: #fef3c7;
  color: #92400e;
}

.status-secondary {
  background: #f3f4f6;
  color: #374151;
}

.currency-info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 2rem;
  align-items: start;
}

.currency-primary .currency-label {
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.currency-value {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
}

.currency-code {
  color: #0369a1;
  margin-right: 0.5rem;
}

.currency-name {
  color: #6b7280;
  font-size: 0.875rem;
  font-weight: 400;
}

.exchange-rate-info .currency-label {
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.rate-date {
  display: block;
  color: #9ca3af;
  font-size: 0.75rem;
  margin-top: 0.25rem;
}

.total-amounts {
  text-align: right;
}

.amount-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.amount-label {
  color: #6b7280;
  font-size: 0.875rem;
}

.amount-value {
  font-weight: 600;
}

.amount-value.primary {
  color: #1f2937;
  font-size: 1.125rem;
}

.amount-value.secondary {
  color: #0369a1;
  font-size: 1rem;
}

.main-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  margin-bottom: 2rem;
}

.detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
}

.detail-label {
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 0.25rem;
  font-weight: 500;
}

.detail-value {
  font-weight: 600;
  color: #1f2937;
}

.validity-ok {
  color: #10b981;
}

.validity-warning {
  color: #f59e0b;
}

.validity-expired {
  color: #ef4444;
}

.link-button {
  color: #3b82f6;
  text-decoration: none;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
}

.link-button:hover {
  text-decoration: underline;
}

.table-responsive {
  overflow-x: auto;
  border-radius: 0.5rem;
  border: 1px solid #e5e7eb;
}

.lines-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
  min-width: 800px;
}

.lines-table th {
  background: #f8fafc;
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  border-bottom: 2px solid #e5e7eb;
  white-space: nowrap;
}

.lines-table td {
  padding: 1rem;
  border-bottom: 1px solid #f3f4f6;
  vertical-align: top;
}

.line-row:hover {
  background-color: #f8fafc;
}

.item-info strong {
  display: block;
  margin-bottom: 0.25rem;
}

.item-info small {
  color: #6b7280;
  font-size: 0.75rem;
}

.base-currency-col {
  background-color: #f0f9ff;
}

.base-amount {
  color: #0369a1;
  font-style: italic;
}

.quantity, .unit-price, .subtotal, .total-amount {
  text-align: right;
}

.total-row {
  background-color: #f8fafc;
  font-weight: 600;
}

.total-row td {
  border-top: 2px solid #e5e7eb;
  border-bottom: none;
  padding: 1.5rem 1rem;
}

.additional-info {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.info-section h4 {
  margin: 0 0 0.5rem 0;
  color: #374151;
  font-weight: 600;
}

.info-section p {
  margin: 0;
  color: #6b7280;
  line-height: 1.6;
}

.info-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.info-item strong {
  color: #374151;
  font-size: 0.875rem;
}

.info-item span {
  color: #6b7280;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-container {
  background: white;
  border-radius: 1rem;
  padding: 0;
  max-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.25rem;
  color: #1f2937;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.close-button {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #6b7280;
  cursor: pointer;
}

.close-button:hover {
  color: #374151;
}

.modal-body {
  padding: 2rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem 2rem;
  border-top: 1px solid #e5e7eb;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
}

.form-control {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  transition: all 0.2s;
}

.form-control:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Responsive */
@media (max-width: 1024px) {
  .currency-info-grid {
    grid-template-columns: 1fr 1fr;
  }
  
  .main-content {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .quotation-detail-container {
    padding: 0.5rem;
  }
  
  .page-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .header-actions {
    justify-content: center;
  }
  
  .currency-info-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .detail-grid {
    grid-template-columns: 1fr;
  }
  
  .info-row {
    grid-template-columns: 1fr;
  }
  
  .lines-table {
    font-size: 0.875rem;
  }
  
  .lines-table th,
  .lines-table td {
    padding: 0.5rem;
  }
}
</style>