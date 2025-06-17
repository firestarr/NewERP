<!-- src/views/purchasing/CreatePOFromQuotation.vue -->
<template>
  <div class="create-po-container">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-left">
          <h1 class="page-title">
            <i class="fas fa-file-invoice-dollar"></i>
            Create Purchase Order from Quotation
          </h1>
          <p class="page-subtitle">Convert vendor quotation into purchase order</p>
        </div>
        <div class="header-actions">
          <router-link to="/purchasing/rfqs" class="btn btn-back">
            <i class="fas fa-arrow-left"></i>
            <span>Back to RFQs</span>
          </router-link>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="loading-container">
      <div class="loading-content">
        <div class="loading-spinner">
          <div class="spinner"></div>
        </div>
        <h3 class="loading-title">Loading Quotation Data</h3>
        <p class="loading-subtitle">Please wait while we fetch the quotation information...</p>
      </div>
    </div>

    <!-- Main Content -->
    <div v-else class="main-content">
      <!-- Quotation Information Card -->
      <div class="info-card quotation-info">
        <div class="card-header">
          <div class="header-icon">
            <i class="fas fa-file-contract"></i>
          </div>
          <div class="header-text">
            <h2 class="card-title">Source Quotation Information</h2>
            <p class="card-subtitle">Review the quotation details before creating purchase order</p>
          </div>
        </div>
        
        <div class="card-body">
          <div class="info-grid">
            <div class="info-section">
              <div class="info-group">
                <label class="info-label">RFQ Number</label>
                <div class="info-value">
                  {{ quotation.request_for_quotation ? quotation.request_for_quotation.rfq_number : 'N/A' }}
                </div>
              </div>
              
              <div class="info-group">
                <label class="info-label">Quotation Date</label>
                <div class="info-value">{{ formatDate(quotation.quotation_date) }}</div>
              </div>
              
              <div class="info-group">
                <label class="info-label">Validity Date</label>
                <div class="info-value">{{ formatDate(quotation.validity_date) || 'Not specified' }}</div>
              </div>
              
              <div class="info-group">
                <label class="info-label">Status</label>
                <div class="info-value">
                  <span class="status-badge" :class="getStatusBadgeClass(quotation.status)">
                    {{ quotation.status }}
                  </span>
                </div>
              </div>
              
              <div v-if="quotation.currency_code" class="info-group">
                <label class="info-label">Currency</label>
                <div class="info-value">{{ quotation.currency_code }}</div>
              </div>
              
              <div v-if="quotation.exchange_rate && quotation.currency_code !== 'USD'" class="info-group">
                <label class="info-label">Exchange Rate</label>
                <div class="info-value">{{ formatNumber(quotation.exchange_rate) }}</div>
              </div>
            </div>
            
            <div class="info-section">
              <div class="info-group">
                <label class="info-label">Vendor</label>
                <div class="info-value vendor-info">
                  <div class="vendor-name">{{ quotation.vendor ? quotation.vendor.name : 'Unknown' }}</div>
                  <div class="vendor-details">
                    <div v-if="quotation.vendor?.contact_person" class="vendor-detail">
                      <i class="fas fa-user"></i>
                      {{ quotation.vendor.contact_person }}
                    </div>
                    <div v-if="quotation.vendor?.email" class="vendor-detail">
                      <i class="fas fa-envelope"></i>
                      {{ quotation.vendor.email }}
                    </div>
                    <div v-if="quotation.vendor?.phone" class="vendor-detail">
                      <i class="fas fa-phone"></i>
                      {{ quotation.vendor.phone }}
                    </div>
                  </div>
                </div>
              </div>
              
              <div v-if="quotation.payment_terms" class="info-group">
                <label class="info-label">Payment Terms</label>
                <div class="info-value">{{ quotation.payment_terms }}</div>
              </div>
              
              <div v-if="quotation.delivery_terms" class="info-group">
                <label class="info-label">Delivery Terms</label>
                <div class="info-value">{{ quotation.delivery_terms }}</div>
              </div>
            </div>
          </div>
          
          <div v-if="quotation.notes" class="notes-section">
            <label class="info-label">Quotation Notes</label>
            <div class="notes-content">{{ quotation.notes }}</div>
          </div>
        </div>
      </div>

      <!-- Quotation Items Card -->
      <div class="info-card items-card">
        <div class="card-header">
          <div class="header-icon">
            <i class="fas fa-list-alt"></i>
          </div>
          <div class="header-text">
            <h2 class="card-title">Quotation Items</h2>
            <p class="card-subtitle">{{ quotation.lines?.length || 0 }} items to be included in purchase order</p>
          </div>
          <div class="header-summary">
            <div class="total-amount">
              <span class="total-label">Total Amount</span>
              <span class="total-value">{{ formatCurrency(calculateTotal()) }}</span>
            </div>
          </div>
        </div>
        
        <div class="card-body">
          <div class="items-table-container">
            <table class="items-table">
              <thead>
                <tr>
                  <th class="col-no">#</th>
                  <th class="col-item">Item Details</th>
                  <th class="col-qty">Quantity</th>
                  <th class="col-price">Unit Price</th>
                  <th class="col-total">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(line, index) in quotation.lines" :key="line.line_id" class="item-row">
                  <td class="item-no">{{ index + 1 }}</td>
                  <td class="item-details">
                    <div class="item-name">{{ line.item ? line.item.name : 'Unknown Item' }}</div>
                    <div class="item-code">{{ line.item ? line.item.item_code : '' }}</div>
                  </td>
                  <td class="item-quantity">
                    <span class="qty-number">{{ formatNumber(line.quantity) }}</span>
                    <span class="qty-unit">{{ line.unitOfMeasure ? line.unitOfMeasure.name : '' }}</span>
                  </td>
                  <td class="item-price">{{ formatCurrency(line.unit_price) }}</td>
                  <td class="item-subtotal">{{ formatCurrency(line.unit_price * line.quantity) }}</td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="total-row">
                  <td colspan="4" class="total-label-cell">Total Amount</td>
                  <td class="total-amount-cell">{{ formatCurrency(calculateTotal()) }}</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>

      <div class="card mt-4">
        <div class="card-header">
          <h2 class="card-title">Purchase Order Details</h2>
        </div>
        <div class="card-body">
          <form @submit.prevent="createPurchaseOrder">
            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label>PO Date <span class="text-danger">*</span></label>
                  <input 
                    type="date" 
                    v-model="purchaseOrder.po_date" 
                    class="form-control"
                    required
                  >
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Expected Delivery</label>
                  <input 
                    type="date" 
                    v-model="purchaseOrder.expected_delivery" 
                    class="form-control"
                    :min="purchaseOrder.po_date"
                  >
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Currency <span class="text-danger">*</span></label>
                  <select v-model="purchaseOrder.currency_code" class="form-control" required>
                    <option value="USD">USD - US Dollar</option>
                    <option value="EUR">EUR - Euro</option>
                    <option value="GBP">GBP - British Pound</option>
                    <option value="IDR">IDR - Indonesian Rupiah</option>
                    <option value="JPY">JPY - Japanese Yen</option>
                    <option value="CNY">CNY - Chinese Yuan</option>
                    <option value="SGD">SGD - Singapore Dollar</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Payment Terms</label>
                  <select v-model="purchaseOrder.payment_terms" class="form-control">
                    <option value="">Select Payment Terms</option>
                    <option value="Net 30">Net 30</option>
                    <option value="Net 45">Net 45</option>
                    <option value="Net 60">Net 60</option>
                    <option value="Net 90">Net 90</option>
                    <option value="Cash on Delivery">Cash on Delivery</option>
                    <option value="Advance Payment">Advance Payment</option>
                    <option value="Letter of Credit">Letter of Credit</option>
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Delivery Terms</label>
                  <select v-model="purchaseOrder.delivery_terms" class="form-control">
                    <option value="">Select Delivery Terms</option>
                    <option value="FOB">FOB (Free On Board)</option>
                    <option value="CIF">CIF (Cost, Insurance, Freight)</option>
                    <option value="EXW">EXW (Ex Works)</option>
                    <option value="DDP">DDP (Delivered Duty Paid)</option>
                    <option value="FCA">FCA (Free Carrier)</option>
                    <option value="CPT">CPT (Carriage Paid To)</option>
                    <option value="CIP">CIP (Carriage and Insurance Paid To)</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Exchange Rate Information (if different currency) -->
            <div v-if="purchaseOrder.currency_code !== 'USD'" class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Exchange Rate</label>
                  <input 
                    type="number" 
                    v-model="purchaseOrder.exchange_rate" 
                    class="form-control"
                    step="0.000001"
                    min="0.000001"
                    placeholder="Auto-calculated if left empty"
                  >
                  <small class="form-text text-muted">
                    Exchange rate from {{ purchaseOrder.currency_code }} to USD. Leave empty for automatic calculation.
                  </small>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Estimated Total in USD</label>
                  <input 
                    type="text" 
                    :value="formatCurrency(estimatedUSDTotal)"
                    class="form-control"
                    readonly
                  >
                  <small class="form-text text-muted">
                    Estimated conversion based on current exchange rate
                  </small>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label>Notes</label>
              <textarea 
                v-model="purchaseOrder.notes" 
                class="form-control" 
                rows="3"
                placeholder="Additional notes for this purchase order..."
              ></textarea>
            </div>

            <!-- Reference Document Information -->
            <div class="form-group">
              <label>Reference Document</label>
              <input 
                type="text" 
                :value="`Quotation ${quotation.quotation_id}`"
                class="form-control"
                readonly
              >
              <small class="form-text text-muted">
                This PO will reference the quotation above
              </small>
            </div>

            <div class="alert alert-info">
              <i class="fas fa-info-circle"></i>
              <strong>Important Notes:</strong>
              <ul class="mb-0 mt-2">
                <li>Creating a purchase order will mark this quotation as accepted.</li>
                <li>All items and prices from the quotation will be transferred to the new purchase order.</li>
                <li>The currency and exchange rate will be locked at creation time.</li>
                <li v-if="!isQuotationValid" class="text-danger">
                  <strong>Warning:</strong> This quotation is not in accepted status.
                </li>
              </ul>
            </div>

            <div class="form-actions mt-4">
              <button type="button" class="btn btn-secondary" @click="$router.go(-1)">
                <i class="fas fa-times"></i> Cancel
              </button>
              <button 
                type="submit" 
                class="btn btn-primary ml-2" 
                :disabled="isCreating || !canCreatePO"
              >
                <span v-if="isCreating">
                  <i class="fas fa-spinner fa-spin"></i> Creating Purchase Order...
                </span>
                <span v-else>
                  <i class="fas fa-file-invoice"></i> Create Purchase Order
                </span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'CreatePOFromQuotation',
  data() {
    return {
      isLoading: true,
      isCreating: false,
      quotation: {},
      purchaseOrder: {
        po_date: new Date().toISOString().split('T')[0],
        expected_delivery: '',
        payment_terms: '',
        delivery_terms: '',
        currency_code: 'USD',
        exchange_rate: null,
        notes: ''
      }
    };
  },
  computed: {
    isQuotationValid() {
      return (
        this.quotation.vendor_id && 
        this.quotation.lines && 
        this.quotation.lines.length > 0
      );
    },
    canCreatePO() {
      return this.isQuotationValid && 
             this.purchaseOrder.po_date && 
             this.purchaseOrder.currency_code;
    },
    estimatedUSDTotal() {
      if (!this.quotation.lines || this.purchaseOrder.currency_code === 'USD') {
        return this.calculateTotal();
      }
      
      const rate = this.purchaseOrder.exchange_rate || 1;
      return this.calculateTotal() * rate;
    }
  },
  created() {
    const quotationId = this.$route.params.id;
    if (quotationId) {
      this.loadQuotation(quotationId);
    } else {
      this.$router.push('/purchasing/rfqs');
    }
  },
  methods: {
    async loadQuotation(quotationId) {
      this.isLoading = true;
      try {
        const response = await axios.get(`/vendor-quotations/${quotationId}`);
        
        if (response.data.status === 'success') {
          this.quotation = response.data.data;
          
          // Set default values from quotation
          this.setDefaultValuesFromQuotation();
          
          // Show warning if quotation is not accepted
          if (this.quotation.status !== 'accepted') {
            this.$toast.warning('This quotation is not in accepted status. Please accept the quotation first for best practices.');
          }
        }
      } catch (error) {
        console.error('Error loading quotation:', error);
        this.$toast.error('Failed to load quotation data');
        this.$router.go(-1);
      } finally {
        this.isLoading = false;
      }
    },
    
    setDefaultValuesFromQuotation() {
      // Use vendor's preferred currency if available
      if (this.quotation.vendor && this.quotation.vendor.preferred_currency) {
        this.purchaseOrder.currency_code = this.quotation.vendor.preferred_currency;
      } else if (this.quotation.currency_code) {
        this.purchaseOrder.currency_code = this.quotation.currency_code;
      }
      
      // Set payment terms from quotation or vendor default
      if (this.quotation.payment_terms) {
        this.purchaseOrder.payment_terms = this.quotation.payment_terms;
      } else if (this.quotation.vendor && this.quotation.vendor.payment_term) {
        this.purchaseOrder.payment_terms = `Net ${this.quotation.vendor.payment_term}`;
      }
      
      // Set delivery terms from quotation
      if (this.quotation.delivery_terms) {
        this.purchaseOrder.delivery_terms = this.quotation.delivery_terms;
      }
      
      // Set exchange rate from quotation
      if (this.quotation.exchange_rate) {
        this.purchaseOrder.exchange_rate = this.quotation.exchange_rate;
      }
      
      // Set notes from quotation
      if (this.quotation.notes) {
        this.purchaseOrder.notes = this.quotation.notes;
      }
      
      // Set expected delivery based on quotation validity or add some buffer days
      if (this.quotation.validity_date) {
        const validityDate = new Date(this.quotation.validity_date);
        validityDate.setDate(validityDate.getDate() + 14); // Add 14 days buffer
        this.purchaseOrder.expected_delivery = validityDate.toISOString().split('T')[0];
      }
    },
    
    calculateTotal() {
      if (!this.quotation.lines) return 0;
      return this.quotation.lines.reduce((sum, line) => sum + (line.unit_price * line.quantity), 0);
    },
    
    formatDate(dateString) {
      if (!dateString) return '-';
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    },
    
    formatCurrency(amount) {
      if (amount === null || amount === undefined) return '-';
      return new Intl.NumberFormat('en-US', {
        style: 'decimal',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(amount);
    },
    
    formatNumber(number) {
      if (number === null || number === undefined) return '-';
      return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 6
      }).format(number);
    },
    
    getStatusBadgeClass(status) {
      const statusClasses = {
        'draft': 'badge-secondary',
        'sent': 'badge-primary',
        'received': 'badge-info',
        'accepted': 'badge-success',
        'rejected': 'badge-danger',
        'expired': 'badge-warning'
      };
      
      return `badge ${statusClasses[status] || 'badge-secondary'}`;
    },
    
    async createPurchaseOrder() {
      if (!this.canCreatePO) {
        this.$toast.error('Please fill in all required fields');
        return;
      }
      
      this.isCreating = true;
      try {
        const payload = {
          quotation_id: this.quotation.quotation_id,
          po_date: this.purchaseOrder.po_date,
          expected_delivery: this.purchaseOrder.expected_delivery || null,
          payment_terms: this.purchaseOrder.payment_terms || null,
          delivery_terms: this.purchaseOrder.delivery_terms || null,
          currency_code: this.purchaseOrder.currency_code,
          exchange_rate: this.purchaseOrder.exchange_rate || null,
          notes: this.purchaseOrder.notes || null
        };
        
        const response = await axios.post('/purchase-orders/create-from-quotation', payload);
        
        if (response.data.status === 'success') {
          this.$toast.success('Purchase order created successfully');
          
          // Redirect to the new purchase order
          this.$router.push(`/purchasing/orders/${response.data.data.po_id}`);
        }
      } catch (error) {
        console.error('Error creating purchase order:', error);
        
        let errorMessage = 'An error occurred while creating the purchase order';
        if (error.response && error.response.data && error.response.data.message) {
          errorMessage = error.response.data.message;
        }
        
        this.$toast.error(errorMessage);
      } finally {
        this.isCreating = false;
      }
    }
  }
};
</script>

<style scoped>
/* Main Container */
.create-po-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  padding: 1.5rem;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

/* Page Header */
.page-header {
  margin-bottom: 2rem;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  background: white;
  border-radius: 16px;
  padding: 2rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.header-left {
  flex: 1;
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  color: #1a202c;
  margin: 0 0 0.5rem 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.page-title i {
  color: #3182ce;
  font-size: 1.75rem;
}

.page-subtitle {
  color: #718096;
  font-size: 1rem;
  margin: 0;
  font-weight: 400;
}

.header-actions {
  display: flex;
  gap: 1rem;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border-radius: 12px;
  font-weight: 600;
  font-size: 0.875rem;
  text-decoration: none;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.btn-back {
  background: #e2e8f0;
  color: #4a5568;
}

.btn-back:hover {
  background: #cbd5e0;
  color: #2d3748;
  transform: translateY(-1px);
}

.btn-cancel {
  background: #fed7d7;
  color: #c53030;
}

.btn-cancel:hover {
  background: #feb2b2;
  color: #9b2c2c;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-weight: 600;
}

.btn-primary:hover:not(:disabled) {
  background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
  transform: translateY(-1px);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none !important;
}

/* Loading State */
.loading-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 60vh;
}

.loading-content {
  text-align: center;
  background: white;
  padding: 3rem;
  border-radius: 16px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.loading-spinner {
  margin-bottom: 1.5rem;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #e2e8f0;
  border-top: 4px solid #3182ce;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.loading-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #2d3748;
  margin: 0 0 0.5rem 0;
}

.loading-subtitle {
  color: #718096;
  margin: 0;
}

/* Main Content */
.main-content {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

/* Info Cards */
.info-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.2);
  overflow: hidden;
}

.card-header {
  background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.header-icon {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.25rem;
}

.header-text {
  flex: 1;
}

.card-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a202c;
  margin: 0 0 0.25rem 0;
}

.card-subtitle {
  color: #718096;
  font-size: 0.875rem;
  margin: 0;
}

.header-summary {
  text-align: right;
}

.total-amount {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.total-label {
  font-size: 0.75rem;
  color: #718096;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-weight: 600;
}

.total-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #2d3748;
}

.card-body {
  padding: 2rem;
}

/* Info Grid */
.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
}

.info-section {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.info-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.info-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #718096;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.info-value {
  font-size: 1rem;
  font-weight: 500;
  color: #2d3748;
}

.vendor-info {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.vendor-name {
  font-size: 1.125rem;
  font-weight: 600;
  color: #2d3748;
}

.vendor-details {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.vendor-detail {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: #718096;
}

.vendor-detail i {
  width: 16px;
  color: #a0aec0;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: capitalize;
}

.badge-secondary { background: #e2e8f0; color: #4a5568; }
.badge-primary { background: #bee3f8; color: #2b6cb0; }
.badge-info { background: #b3ecf2; color: #0987a0; }
.badge-success { background: #c6f6d5; color: #25855a; }
.badge-danger { background: #fed7d7; color: #c53030; }
.badge-warning { background: #faf089; color: #744210; }

.notes-section {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e2e8f0;
}

.notes-content {
  background: #f7fafc;
  padding: 1rem;
  border-radius: 8px;
  color: #4a5568;
  font-style: italic;
  border-left: 4px solid #3182ce;
}

/* Items Table */
.items-table-container {
  overflow-x: auto;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.items-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.items-table th {
  background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #4a5568;
  border-bottom: 1px solid #e2e8f0;
  white-space: nowrap;
}

.items-table td {
  padding: 1rem;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: top;
}

.col-no { width: 60px; }
.col-item { min-width: 300px; }
.col-qty { width: 120px; }
.col-price { width: 120px; text-align: right; }
.col-total { width: 120px; text-align: right; }

.item-row:hover {
  background: #f8fafc;
}

.item-no {
  font-weight: 600;
  color: #718096;
  text-align: center;
}

.item-details {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.item-name {
  font-weight: 600;
  color: #2d3748;
}

.item-code {
  font-size: 0.75rem;
  color: #718096;
  font-family: 'Monaco', 'Consolas', monospace;
}

.item-quantity {
  display: flex;
  align-items: baseline;
  gap: 0.25rem;
}

.qty-number {
  font-weight: 600;
  color: #2d3748;
}

.qty-unit {
  font-size: 0.75rem;
  color: #718096;
}

.item-price,
.item-subtotal {
  font-weight: 600;
  color: #2d3748;
  text-align: right;
}

.total-row {
  background: #f7fafc;
  font-weight: 700;
}

.total-label-cell {
  text-align: right;
  color: #4a5568;
}

.total-amount-cell {
  text-align: right;
  color: #2d3748;
  font-size: 1.125rem;
}

/* Form Styling */
.po-form {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.form-section {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.25rem;
  font-weight: 600;
  color: #2d3748;
  margin: 0;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #e2e8f0;
}

.section-title i {
  color: #3182ce;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.form-label.required::after {
  content: '*';
  color: #ef4444;
  font-weight: 700;
}

.form-input,
.form-select,
.form-textarea {
  padding: 0.75rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  background: white;
  transition: all 0.2s ease;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
  outline: none;
  border-color: #3182ce;
  box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
}

.form-textarea {
  resize: vertical;
  min-height: 100px;
  font-family: inherit;
}

.form-hint {
  font-size: 0.75rem;
  color: #6b7280;
  font-style: italic;
}

.currency-section {
  background: #f0f9ff;
  padding: 1.5rem;
  border-radius: 12px;
  border: 1px solid #bae6fd;
}

.currency-display {
  display: flex;
  align-items: center;
  background: white;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  border: 1px solid #d1d5db;
}

.currency-symbol {
  font-weight: 600;
  color: #374151;
  margin-right: 0.5rem;
}

.currency-amount {
  font-weight: 600;
  color: #059669;
  font-size: 1.125rem;
}

.reference-display {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: #f8fafc;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  color: #4a5568;
  font-weight: 500;
}

.reference-display i {
  color: #3182ce;
}

/* Notice Section */
.notice-section {
  margin-top: 1rem;
}

.notice-box {
  background: linear-gradient(135deg, #ebf4ff 0%, #dbeafe 100%);
  border: 1px solid #bfdbfe;
  border-radius: 12px;
  padding: 1.5rem;
  display: flex;
  gap: 1rem;
}

.notice-icon {
  flex-shrink: 0;
  width: 40px;
  height: 40px;
  background: #3182ce;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.notice-content {
  flex: 1;
}

.notice-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e40af;
  margin: 0 0 0.75rem 0;
}

.notice-list {
  margin: 0;
  padding-left: 1.25rem;
  color: #1e40af;
}

.notice-list li {
  margin-bottom: 0.5rem;
}

.warning-item {
  color: #dc2626 !important;
  font-weight: 600;
}

/* Form Actions */
.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding-top: 2rem;
  border-top: 1px solid #e2e8f0;
}

.btn-loading,
.btn-content {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Responsive Design */
@media (max-width: 768px) {
  .create-po-container {
    padding: 1rem;
  }
  
  .header-content {
    flex-direction: column;
    gap: 1rem;
    padding: 1.5rem;
  }
  
  .page-title {
    font-size: 1.5rem;
  }
  
  .info-grid {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
  
  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .card-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
    padding: 1.25rem;
  }
  
  .header-summary {
    align-self: stretch;
    text-align: left;
  }
  
  .form-actions {
    flex-direction: column;
  }
  
  .btn {
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .card-body {
    padding: 1.25rem;
  }
  
  .items-table th,
  .items-table td {
    padding: 0.75rem;
  }
  
  .col-item {
    min-width: 200px;
  }
}
</style>