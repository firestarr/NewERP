<!-- Enhanced Vendor Quotation Compare with Multi-Currency Support -->
<template>
  <div class="quotation-compare-container">
    <div class="page-header">
      <div class="header-left">
        <h1>Compare Vendor Quotations</h1>
        <p class="page-description">Compare vendor quotations side by side to make informed decisions</p>
      </div>
      <div class="header-actions">
        <router-link to="/purchasing/quotations" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i> Back to List
        </router-link>
      </div>
    </div>

    <!-- RFQ Selection -->
    <div class="filter-section">
      <div class="filter-card">
        <div class="filter-header">
          <h2><i class="fas fa-clipboard-list"></i> Select RFQ to Compare Quotations</h2>
        </div>
        <div class="filter-content">
          <div class="filter-row">
            <div class="filter-select-group">
              <label for="rfq-select">Request for Quotation:</label>
              <select 
                id="rfq-select" 
                v-model="selectedRFQ"
                @change="loadQuotations"
                class="form-control"
              >
                <option value="">Select RFQ</option>
                <option v-for="rfq in rfqOptions" :key="rfq.rfq_id" :value="rfq.rfq_id">
                  {{ rfq.rfq_number }} - {{ formatDate(rfq.rfq_date) }}
                </option>
              </select>
            </div>
            
            <div class="currency-controls" v-if="selectedRFQ">
              <div class="currency-select-group">
                <label for="display-currency">Display Currency:</label>
                <select 
                  id="display-currency"
                  v-model="displayCurrency"
                  @change="updateDisplayCurrency"
                  class="form-control"
                >
                  <option value="">Original Currencies</option>
                  <option v-for="currency in currencyOptions" :key="currency.code" :value="currency.code">
                    {{ currency.code }} - {{ currency.name }}
                  </option>
                </select>
              </div>
              
              <div class="comparison-mode">
                <label>
                  <input type="checkbox" v-model="showOnlyReceived" @change="filterQuotations" />
                  Show only received quotations
                </label>
              </div>
            </div>
          </div>
          
          <div class="filter-help">
            <i class="fas fa-info-circle"></i>
            Select a Request for Quotation to view and compare all vendor responses.
          </div>
        </div>
      </div>
    </div>

    <!-- Currency Display Notice -->
    <div v-if="displayCurrency" class="currency-notice">
      <i class="fas fa-coins"></i>
      All amounts are displayed in <strong>{{ displayCurrency }}</strong> 
      (converted from original currencies using rates from quotation dates)
    </div>

    <div v-if="loading" class="loading-container">
      <i class="fas fa-spinner fa-spin"></i> Loading quotations...
    </div>

    <div v-else>
      <div v-if="filteredQuotations.length === 0 && selectedRFQ" class="empty-state">
        <i class="fas fa-file-invoice-dollar empty-icon"></i>
        <h3>No quotations found</h3>
        <p>No vendor quotations are available for the selected Request for Quotation.</p>
      </div>

      <div v-else-if="filteredQuotations.length > 0" class="comparison-section">
        <!-- RFQ Information -->
        <div class="rfq-info-section">
          <div class="rfq-header">
            <div class="rfq-badge">
              <span class="rfq-number">RFQ: {{ rfqDetails.rfq_number }}</span>
              <span class="status-badge" :class="rfqStatusClass">
                {{ capitalizeFirstLetter(rfqDetails.status) }}
              </span>
            </div>
            <div class="rfq-stats">
              <div class="stat-item">
                <span class="stat-label">Total Quotations</span>
                <span class="stat-value">{{ quotations.length }}</span>
              </div>
              <div class="stat-item">
                <span class="stat-label">Received</span>
                <span class="stat-value">{{ receivedQuotations.length }}</span>
              </div>
              <div class="stat-item" v-if="bestPriceQuotation">
                <span class="stat-label">Best Total Price</span>
                <span class="stat-value">{{ formatDisplayAmount(bestPriceQuotation) }}</span>
              </div>
            </div>
          </div>
          
          <div class="rfq-details">
            <div class="rfq-detail-item">
              <span class="detail-label">RFQ Date:</span>
              <span class="detail-value">{{ formatDate(rfqDetails.rfq_date) }}</span>
            </div>
            <div class="rfq-detail-item">
              <span class="detail-label">Valid Until:</span>
              <span class="detail-value">{{ formatDate(rfqDetails.validity_date) }}</span>
            </div>
            <div class="rfq-detail-item">
              <span class="detail-label">Required Items:</span>
              <span class="detail-value">{{ rfqDetails.lines ? rfqDetails.lines.length : 0 }} items</span>
            </div>
          </div>
        </div>
        
        <!-- Quotation Summary Cards -->
        <div class="summary-cards">
          <div 
            v-for="(quotation, index) in filteredQuotations" 
            :key="quotation.quotation_id"
            class="summary-card"
            :class="getQuotationCardClass(quotation, index)"
          >
            <div class="summary-header">
              <div class="vendor-info">
                <h3>{{ quotation.vendor ? quotation.vendor.name : 'Unknown Vendor' }}</h3>
                <small v-if="quotation.vendor">{{ quotation.vendor.vendor_code }}</small>
              </div>
              <div class="card-badges">
                <span v-if="isBestTotalPrice(quotation.quotation_id)" class="best-badge">
                  <i class="fas fa-crown"></i> Best Price
                </span>
                <span class="status-badge" :class="getStatusClass(quotation.status)">
                  {{ capitalizeFirstLetter(quotation.status) }}
                </span>
              </div>
            </div>
            
            <div class="summary-content">
              <!-- Currency Information -->
              <div class="currency-info">
                <div class="currency-row">
                  <span class="currency-label">Currency:</span>
                  <span class="currency-value">{{ quotation.currency_code || 'USD' }}</span>
                </div>
                <div v-if="quotation.currency_code !== baseCurrency" class="currency-row">
                  <span class="currency-label">Exchange Rate:</span>
                  <span class="currency-value">1:{{ formatNumber(quotation.exchange_rate) }}</span>
                </div>
              </div>

              <!-- Price Information -->
              <div class="price-summary">
                <div class="price-row main-price">
                  <span class="price-label">Total Amount:</span>
                  <span class="price-value">{{ formatDisplayAmount(quotation) }}</span>
                </div>
                <div v-if="displayCurrency && quotation.currency_code !== displayCurrency" class="price-row original-price">
                  <span class="price-label">Original ({{ quotation.currency_code }}):</span>
                  <span class="price-value">{{ formatCurrency(quotation.total_amount, quotation.currency_code) }}</span>
                </div>
              </div>

              <!-- Additional Details -->
              <div class="summary-details">
                <div class="detail-item">
                  <i class="fas fa-calendar"></i>
                  <span>{{ formatDate(quotation.quotation_date) }}</span>
                </div>
                <div class="detail-item">
                  <i class="fas fa-clock"></i>
                  <span>Valid until {{ formatDate(quotation.validity_date) }}</span>
                </div>
                <div class="detail-item">
                  <i class="fas fa-truck"></i>
                  <span>{{ getEarliestDelivery(quotation) }}</span>
                </div>
              </div>
            </div>
            
            <div class="summary-actions">
              <router-link 
                :to="`/purchasing/quotations/${quotation.quotation_id}`" 
                class="btn btn-sm btn-info"
              >
                <i class="fas fa-eye"></i> View Details
              </router-link>
              <div class="action-dropdown">
                <button 
                  v-if="quotation.status === 'received'" 
                  @click="changeStatus(quotation.quotation_id, 'accepted')"
                  class="btn btn-sm btn-success"
                  :disabled="processingAction"
                >
                  <i class="fas fa-check"></i> Accept
                </button>
                <button 
                  v-if="quotation.status === 'received'" 
                  @click="changeStatus(quotation.quotation_id, 'rejected')"
                  class="btn btn-sm btn-danger"
                  :disabled="processingAction"
                >
                  <i class="fas fa-times"></i> Reject
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Detailed Item Comparison -->
        <div class="comparison-table-section">
          <div class="section-header">
            <h2><i class="fas fa-table"></i> Item-by-Item Comparison</h2>
            <div class="table-controls">
              <button @click="exportComparison" class="btn btn-outline">
                <i class="fas fa-file-excel"></i> Export Comparison
              </button>
            </div>
          </div>
          
          <div class="table-responsive">
            <table class="comparison-table">
              <thead>
                <tr>
                  <th rowspan="2" class="item-header">Item</th>
                  <th rowspan="2" class="qty-header">Qty</th>
                  <th rowspan="2" class="uom-header">UOM</th>
                  <th 
                    v-for="quotation in filteredQuotations" 
                    :key="`vendor-${quotation.quotation_id}`"
                    :class="['vendor-header', getQuotationHeaderClass(quotation)]"
                  >
                    {{ quotation.vendor ? quotation.vendor.name : 'Unknown' }}
                    <small>{{ quotation.currency_code || 'USD' }}</small>
                  </th>
                </tr>
                <tr>
                  <th 
                    v-for="quotation in filteredQuotations" 
                    :key="`price-${quotation.quotation_id}`"
                    class="price-header"
                  >
                    Unit Price ({{ displayCurrency || quotation.currency_code || 'USD' }})
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in comparisonData" :key="item.item_id" class="comparison-row">
                  <td class="item-cell">
                    <div class="item-info">
                      <strong>{{ item.item_code }}</strong>
                      <div class="item-name">{{ item.item_name }}</div>
                    </div>
                  </td>
                  <td class="qty-cell">{{ formatNumber(item.quantity) }}</td>
                  <td class="uom-cell">{{ item.uom_symbol }}</td>
                  <td 
                    v-for="quotation in filteredQuotations" 
                    :key="`price-${quotation.quotation_id}-${item.item_id}`"
                    :class="['price-cell', getPriceCellClass(quotation.quotation_id, item)]"
                  >
                    <div class="price-info">
                      <span class="unit-price">{{ getItemPrice(quotation, item.item_id) }}</span>
                      <span class="line-total">{{ getItemTotal(quotation, item.item_id) }}</span>
                    </div>
                  </td>
                </tr>
                <!-- Total Row -->
                <tr class="total-row">
                  <td colspan="3" class="total-label">
                    <strong>Total Amount</strong>
                  </td>
                  <td 
                    v-for="quotation in filteredQuotations" 
                    :key="`total-${quotation.quotation_id}`"
                    :class="['total-cell', getTotalCellClass(quotation)]"
                  >
                    <strong>{{ formatDisplayAmount(quotation) }}</strong>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Recommendation Section -->
        <div class="recommendation-section">
          <div class="section-header">
            <h2><i class="fas fa-lightbulb"></i> Recommendations</h2>
          </div>
          
          <div class="recommendations-grid">
            <div class="recommendation-card best-price">
              <div class="rec-header">
                <i class="fas fa-dollar-sign"></i>
                <h4>Best Overall Price</h4>
              </div>
              <div class="rec-content" v-if="bestPriceQuotation">
                <div class="vendor-name">{{ bestPriceQuotation.vendor.name }}</div>
                <div class="price-amount">{{ formatDisplayAmount(bestPriceQuotation) }}</div>
                <div class="savings" v-if="priceSavings > 0">
                  Saves {{ formatCurrency(priceSavings, displayCurrency || baseCurrency) }} vs highest
                </div>
              </div>
            </div>

            <div class="recommendation-card best-delivery">
              <div class="rec-header">
                <i class="fas fa-shipping-fast"></i>
                <h4>Fastest Delivery</h4>
              </div>
              <div class="rec-content" v-if="fastestDeliveryQuotation">
                <div class="vendor-name">{{ fastestDeliveryQuotation.vendor.name }}</div>
                <div class="delivery-date">{{ getEarliestDelivery(fastestDeliveryQuotation) }}</div>
              </div>
            </div>

            <div class="recommendation-card balanced">
              <div class="rec-header">
                <i class="fas fa-balance-scale"></i>
                <h4>Balanced Choice</h4>
              </div>
              <div class="rec-content" v-if="balancedChoiceQuotation">
                <div class="vendor-name">{{ balancedChoiceQuotation.vendor.name }}</div>
                <div class="balance-score">Good price + delivery combination</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'VendorQuotationCompare',
  data() {
    return {
      selectedRFQ: '',
      displayCurrency: '',
      showOnlyReceived: true,
      loading: false,
      processingAction: false,
      baseCurrency: 'USD',
      
      rfqOptions: [],
      quotations: [],
      filteredQuotations: [],
      rfqDetails: {},
      comparisonData: [],
      
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
      return this.getStatusClass(this.rfqDetails.status);
    },
    
    receivedQuotations() {
      return this.quotations.filter(q => q.status === 'received');
    },

    bestPriceQuotation() {
      if (this.filteredQuotations.length === 0) return null;
      
      return this.filteredQuotations.reduce((best, current) => {
        const bestAmount = this.getDisplayAmount(best);
        const currentAmount = this.getDisplayAmount(current);
        return currentAmount < bestAmount ? current : best;
      });
    },

    fastestDeliveryQuotation() {
      return this.filteredQuotations.reduce((fastest, current) => {
        const fastestDate = this.getEarliestDeliveryDate(fastest);
        const currentDate = this.getEarliestDeliveryDate(current);
        return currentDate < fastestDate ? current : fastest;
      }, this.filteredQuotations[0]);
    },

    balancedChoiceQuotation() {
      // Simple balanced choice logic - can be enhanced
      return this.bestPriceQuotation;
    },

    priceSavings() {
      if (this.filteredQuotations.length < 2) return 0;
      
      const amounts = this.filteredQuotations.map(q => this.getDisplayAmount(q));
      const highest = Math.max(...amounts);
      const lowest = Math.min(...amounts);
      
      return highest - lowest;
    }
  },
  methods: {
    // Fetch RFQ options
    fetchRFQOptions() {
      axios.get('/request-for-quotations?status=sent')
        .then(response => {
          this.rfqOptions = response.data.data.data || response.data.data;
        })
        .catch(error => {
          console.error('Error fetching RFQ options:', error);
          this.$toasted.error('Failed to load RFQ options');
        });
    },
    
    // Load quotations for selected RFQ
    loadQuotations() {
      if (!this.selectedRFQ) {
        this.quotations = [];
        this.filteredQuotations = [];
        this.comparisonData = [];
        return;
      }
      
      this.loading = true;
      
      const params = {
        rfq_id: this.selectedRFQ
      };

      if (this.displayCurrency) {
        params.display_currency = this.displayCurrency;
        params.exchange_date = new Date().toISOString().split('T')[0];
      }
      
      Promise.all([
        axios.get('/vendor-quotations', { params }),
        axios.get(`/request-for-quotations/${this.selectedRFQ}`)
      ])
      .then(([quotationsResponse, rfqResponse]) => {
        this.quotations = quotationsResponse.data.data.data || quotationsResponse.data.data;
        this.rfqDetails = rfqResponse.data.data;
        
        this.filterQuotations();
        this.buildComparisonData();
      })
      .catch(error => {
        console.error('Error loading quotations:', error);
        this.$toasted.error('Failed to load quotations');
      })
      .finally(() => {
        this.loading = false;
      });
    },

    // Update display currency
    updateDisplayCurrency() {
      if (this.selectedRFQ) {
        this.loadQuotations();
      }
    },

    // Filter quotations based on settings
    filterQuotations() {
      this.filteredQuotations = this.quotations.filter(quotation => {
        if (this.showOnlyReceived && quotation.status !== 'received') {
          return false;
        }
        return true;
      });
    },

    // Build comparison data
    buildComparisonData() {
      if (!this.rfqDetails.lines || this.quotations.length === 0) {
        this.comparisonData = [];
        return;
      }

      this.comparisonData = this.rfqDetails.lines.map(rfqLine => ({
        item_id: rfqLine.item_id,
        item_code: rfqLine.item ? rfqLine.item.item_code : 'N/A',
        item_name: rfqLine.item ? rfqLine.item.name : 'N/A',
        quantity: rfqLine.quantity,
        uom_symbol: rfqLine.unitOfMeasure ? rfqLine.unitOfMeasure.symbol : 'N/A'
      }));
    },

    // Get display amount considering currency conversion
    getDisplayAmount(quotation) {
      if (this.displayCurrency && quotation.display_amounts) {
        return quotation.display_amounts.total_amount;
      }
      return quotation.total_amount || 0;
    },

    // Format display amount
    formatDisplayAmount(quotation) {
      const amount = this.getDisplayAmount(quotation);
      const currency = this.displayCurrency || quotation.currency_code || 'USD';
      return this.formatCurrency(amount, currency);
    },

    // Get item price from quotation
    getItemPrice(quotation, itemId) {
      const line = quotation.lines ? quotation.lines.find(l => l.item_id === itemId) : null;
      if (!line) return 'N/A';
      
      let unitPrice = line.unit_price;
      let currency = quotation.currency_code || 'USD';

      // Convert to display currency if needed
      if (this.displayCurrency && quotation.currency_code !== this.displayCurrency) {
        unitPrice = line.base_currency_unit_price / this.getExchangeRateToDisplay(quotation);
        currency = this.displayCurrency;
      }

      return this.formatCurrency(unitPrice, currency);
    },

    // Get item total from quotation
    getItemTotal(quotation, itemId) {
      const line = quotation.lines ? quotation.lines.find(l => l.item_id === itemId) : null;
      if (!line) return 'N/A';
      
      let subtotal = line.subtotal;
      let currency = quotation.currency_code || 'USD';

      // Convert to display currency if needed
      if (this.displayCurrency && quotation.currency_code !== this.displayCurrency) {
        subtotal = line.base_currency_subtotal / this.getExchangeRateToDisplay(quotation);
        currency = this.displayCurrency;
      }

      return this.formatCurrency(subtotal, currency);
    },

    // Get exchange rate for display currency conversion
    getExchangeRateToDisplay(quotation) {
      if (quotation && typeof quotation.exchange_rate === 'number' && quotation.exchange_rate > 0) {
        return quotation.exchange_rate;
      }
      return 1.0;
    },

    // Get earliest delivery date
    getEarliestDeliveryDate(quotation) {
      if (!quotation.lines || quotation.lines.length === 0) return new Date('2099-12-31');
      
      const dates = quotation.lines
        .map(line => line.delivery_date)
        .filter(date => date)
        .map(date => new Date(date));
      
      return dates.length > 0 ? new Date(Math.min(...dates)) : new Date('2099-12-31');
    },

    // Get earliest delivery formatted
    getEarliestDelivery(quotation) {
      const date = this.getEarliestDeliveryDate(quotation);
      if (date.getFullYear() === 2099) return 'N/A';
      return this.formatDate(date);
    },

    // Check if quotation has best total price
    isBestTotalPrice(quotationId) {
      return this.bestPriceQuotation && this.bestPriceQuotation.quotation_id === quotationId;
    },

    // Get quotation card class
    getQuotationCardClass(quotation, index) {
      const classes = ['vendor-' + (index % 5 + 1)];
      
      if (this.isBestTotalPrice(quotation.quotation_id)) {
        classes.push('best-overall');
      }
      
      return classes;
    },

    // Get quotation header class
    getQuotationHeaderClass(quotation) {
      if (this.isBestTotalPrice(quotation.quotation_id)) {
        return 'best-price-header';
      }
      return '';
    },

    // Get price cell class
    getPriceCellClass(quotationId, item) {
      // Find best price for this item
      const prices = this.filteredQuotations
        .map(q => {
          const line = q.lines ? q.lines.find(l => l.item_id === item.item_id) : null;
          return line ? this.getDisplayAmount({ ...q, total_amount: line.subtotal }) : Infinity;
        });
      
      const minPrice = Math.min(...prices);
      const quotationIndex = this.filteredQuotations.findIndex(q => q.quotation_id === quotationId);
      const currentPrice = prices[quotationIndex];
      
      if (currentPrice === minPrice && currentPrice !== Infinity) {
        return 'best-item-price';
      }
      
      return '';
    },

    // Get total cell class
    getTotalCellClass(quotation) {
      if (this.isBestTotalPrice(quotation.quotation_id)) {
        return 'best-total-price';
      }
      return '';
    },

    // Change quotation status
    changeStatus(id, newStatus) {
      this.processingAction = true;
      
      axios.patch(`/vendor-quotations/${id}/status`, { status: newStatus })
        .then(response => {
          if (response.data.status === 'success') {
            const index = this.quotations.findIndex(q => q.quotation_id === id);
            if (index !== -1) {
              this.quotations[index].status = newStatus;
            }
            
            this.filterQuotations();
            this.$toasted.success(`Quotation ${newStatus} successfully.`);
          } else {
            this.$toasted.error(`Failed to update status: ${response.data.message}`);
          }
        })
        .catch(error => {
          console.error('API Error:', error);
          this.$toasted.error('An error occurred while updating status');
        })
        .finally(() => {
          this.processingAction = false;
        });
    },

    // Export comparison to Excel
    exportComparison() {
      if (!this.selectedRFQ) {
        this.$toasted.warning('Please select an RFQ first');
        return;
      }

      const params = {
        rfq_id: this.selectedRFQ,
        display_currency: this.displayCurrency,
        format: 'comparison'
      };

      const queryString = new URLSearchParams(params).toString();
      window.open(`/api/vendor-quotations/export?${queryString}`, '_blank');
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
    this.fetchRFQOptions();
    
    // If RFQ ID is provided in query params, select it automatically
    if (this.$route.query.rfq_id) {
      this.selectedRFQ = this.$route.query.rfq_id;
      this.loadQuotations();
    }
  }
};
</script>

<style scoped>
.quotation-compare-container {
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

.page-description {
  margin: 0;
  color: #6b7280;
  font-size: 1rem;
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

.btn-secondary {
  background-color: #f8fafc;
  color: #64748b;
  border: 2px solid #e2e8f0;
}

.btn-secondary:hover {
  background-color: #e2e8f0;
  text-decoration: none;
}

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.75rem;
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

.btn-outline {
  background-color: white;
  color: #6b7280;
  border: 2px solid #e5e7eb;
}

.btn-outline:hover {
  background-color: #f9fafb;
  border-color: #d1d5db;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.filter-section {
  margin-bottom: 2rem;
}

.filter-card {
  background: white;
  border-radius: 1rem;
  padding: 2rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
}

.filter-header {
  margin-bottom: 1.5rem;
}

.filter-header h2 {
  margin: 0;
  font-size: 1.25rem;
  color: #1f2937;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.filter-row {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 2rem;
  margin-bottom: 1rem;
}

.filter-select-group,
.currency-select-group {
  display: flex;
  flex-direction: column;
}

.filter-select-group label,
.currency-select-group label {
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
}

.form-control {
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

.comparison-mode {
  display: flex;
  align-items: center;
  padding-top: 1.5rem;
}

.comparison-mode label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 500;
  color: #374151;
  cursor: pointer;
}

.filter-help {
  color: #6b7280;
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 0.5rem;
  border: 1px solid #e2e8f0;
}

.currency-notice {
  background: #eff6ff;
  border: 1px solid #3b82f6;
  border-radius: 0.5rem;
  padding: 1rem;
  margin-bottom: 1.5rem;
  color: #1e40af;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.loading-container {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 4rem;
  font-size: 1.25rem;
  color: #6b7280;
}

.empty-state {
  text-align: center;
  padding: 4rem;
  color: #6b7280;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
  color: #d1d5db;
}

.empty-state h3 {
  margin: 0 0 0.5rem 0;
  color: #374151;
  font-size: 1.5rem;
}

.empty-state p {
  margin: 0;
  color: #6b7280;
}

.rfq-info-section {
  background: white;
  border-radius: 1rem;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
}

.rfq-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f3f4f6;
}

.rfq-badge {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.rfq-number {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
}

.rfq-stats {
  display: flex;
  gap: 2rem;
}

.stat-item {
  text-align: center;
}

.stat-label {
  display: block;
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 0.25rem;
}

.stat-value {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
}

.rfq-details {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 2rem;
}

.rfq-detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.detail-label {
  font-size: 0.875rem;
  color: #6b7280;
  font-weight: 500;
}

.detail-value {
  font-weight: 600;
  color: #1f2937;
}

.status-badge {
  padding: 0.5rem 1rem;
  border-radius: 1rem;
  font-size: 0.75rem;
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

.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.summary-card {
  background: white;
  border-radius: 1rem;
  padding: 1.5rem;
  border: 2px solid #e5e7eb;
  transition: all 0.2s;
  position: relative;
}

.summary-card:hover {
  border-color: #3b82f6;
  box-shadow: 0 8px 25px rgba(59, 130, 246, 0.15);
  transform: translateY(-2px);
}

.summary-card.best-overall {
  border-color: #f59e0b;
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
}

.summary-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.vendor-info h3 {
  margin: 0 0 0.25rem 0;
  color: #1f2937;
  font-size: 1.125rem;
  font-weight: 600;
}

.vendor-info small {
  color: #6b7280;
  font-size: 0.875rem;
}

.card-badges {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  align-items: flex-end;
}

.best-badge {
  background: #f59e0b;
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
  font-size: 0.75rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.summary-content {
  margin-bottom: 1.5rem;
}

.currency-info {
  margin-bottom: 1rem;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 0.5rem;
}

.currency-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.currency-row:last-child {
  margin-bottom: 0;
}

.currency-label {
  font-size: 0.875rem;
  color: #6b7280;
}

.currency-value {
  font-weight: 600;
  color: #1f2937;
}

.price-summary {
  margin-bottom: 1rem;
}

.price-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.price-row:last-child {
  margin-bottom: 0;
}

.price-row.main-price {
  padding: 0.75rem;
  background: #f0f9ff;
  border-radius: 0.5rem;
  border: 1px solid #0284c7;
}

.price-row.main-price .price-label {
  font-weight: 600;
  color: #0369a1;
}

.price-row.main-price .price-value {
  font-size: 1.125rem;
  font-weight: 700;
  color: #0369a1;
}

.price-label {
  font-size: 0.875rem;
  color: #6b7280;
}

.price-value {
  font-weight: 600;
  color: #1f2937;
}

.original-price {
  font-style: italic;
  opacity: 0.8;
}

.summary-details {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: #6b7280;
}

.detail-item i {
  color: #9ca3af;
  width: 1rem;
}

.summary-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.comparison-table-section {
  background: white;
  border-radius: 1rem;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f3f4f6;
}

.section-header h2 {
  margin: 0;
  font-size: 1.25rem;
  color: #1f2937;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.table-responsive {
  overflow-x: auto;
  border-radius: 0.5rem;
  border: 1px solid #e5e7eb;
}

.comparison-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
  min-width: 800px;
}

.comparison-table th {
  background: #f8fafc;
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  border-bottom: 2px solid #e5e7eb;
  white-space: nowrap;
}

.vendor-header {
  text-align: center;
  min-width: 150px;
}

.vendor-header.best-price-header {
  background: #fef3c7;
  color: #92400e;
}

.vendor-header small {
  display: block;
  font-size: 0.75rem;
  font-weight: 400;
  color: #6b7280;
  margin-top: 0.25rem;
}

.price-header {
  text-align: center;
  font-size: 0.875rem;
  color: #6b7280;
}

.comparison-table td {
  padding: 1rem;
  border-bottom: 1px solid #f3f4f6;
  vertical-align: top;
}

.comparison-row:hover {
  background-color: #f8fafc;
}

.item-cell {
  min-width: 200px;
}

.item-info strong {
  display: block;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.item-name {
  font-size: 0.875rem;
  color: #6b7280;
}

.qty-cell,
.uom-cell {
  text-align: center;
  font-weight: 500;
  color: #374151;
}

.price-cell {
  text-align: center;
  min-width: 120px;
}

.price-cell.best-item-price {
  background: #d1fae5;
  border-left: 3px solid #10b981;
  border-right: 3px solid #10b981;
}

.price-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.unit-price {
  font-weight: 600;
  color: #1f2937;
}

.line-total {
  font-size: 0.875rem;
  color: #6b7280;
}

.total-row {
  background: #f8fafc;
  border-top: 2px solid #e5e7eb;
}

.total-row td {
  border-bottom: none;
  font-weight: 600;
  padding: 1.5rem 1rem;
}

.total-label {
  text-align: right;
  color: #374151;
}

.total-cell {
  text-align: center;
  font-size: 1.125rem;
  color: #1f2937;
}

.total-cell.best-total-price {
  background: #d1fae5;
  color: #065f46;
  border-left: 3px solid #10b981;
  border-right: 3px solid #10b981;
}

.recommendation-section {
  background: white;
  border-radius: 1rem;
  padding: 2rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
}

.recommendations-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.recommendation-card {
  padding: 1.5rem;
  border-radius: 0.75rem;
  border: 2px solid #e5e7eb;
}

.recommendation-card.best-price {
  background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
  border-color: #10b981;
}

.recommendation-card.best-delivery {
  background: linear-gradient(135deg, #dbeafe 0%, #93c5fd 100%);
  border-color: #3b82f6;
}

.recommendation-card.balanced {
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  border-color: #f59e0b;
}

.rec-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.5);
}

.rec-header i {
  font-size: 1.25rem;
}

.rec-header h4 {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
}

.rec-content {
  text-align: center;
}

.vendor-name {
  font-size: 1.125rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: #1f2937;
}

.price-amount,
.delivery-date {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: #374151;
}

.savings,
.balance-score {
  font-size: 0.875rem;
  color: #6b7280;
}

/* Responsive */
@media (max-width: 1024px) {
  .filter-row {
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
  }
  
  .currency-controls {
    grid-column: 1 / -1;
  }
  
  .rfq-stats {
    gap: 1rem;
  }
  
  .rfq-details {
    grid-template-columns: 1fr 1fr;
  }
  
  .summary-cards {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 768px) {
  .quotation-compare-container {
    padding: 0.5rem;
  }
  
  .page-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .filter-row {
    grid-template-columns: 1fr;
  }
  
  .rfq-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .rfq-stats {
    justify-content: space-around;
  }
  
  .rfq-details {
    grid-template-columns: 1fr;
  }
  
  .summary-cards {
    grid-template-columns: 1fr;
  }
  
  .comparison-table {
    font-size: 0.875rem;
  }
  
  .comparison-table th,
  .comparison-table td {
    padding: 0.5rem;
  }
  
  .recommendations-grid {
    grid-template-columns: 1fr;
  }
}
</style>