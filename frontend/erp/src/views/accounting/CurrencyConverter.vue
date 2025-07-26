<!-- frontend/erp/src/views/accounting/CurrencyConverter.vue -->
<!-- REPLACE COMPLETELY -->
<template>
  <div class="bidirectional-converter-container">
    <!-- Header -->
    <div class="page-header">
      <div class="header-content">
        <h1>Bidirectional Currency Converter</h1>
        <p class="subtitle">Convert currencies in both directions automatically</p>
      </div>
      <router-link to="/currency-rates" class="btn btn-secondary">
        <i class="fas fa-cog"></i> Manage Rates
      </router-link>
    </div>

    <!-- Converter Card -->
    <div class="converter-card">
      <div class="converter-header">
        <h2>Currency Conversion</h2>
        <div class="conversion-status" v-if="conversionData">
          <span class="status-badge">
            <i class="fas fa-check"></i> Live Rate
          </span>
        </div>
      </div>

      <div class="converter-form">
        <!-- Currency Exchange Container -->
        <div class="currency-exchange-container">
          <!-- From Currency -->
          <div class="currency-input-container">
            <label class="form-label">From</label>
            <div class="input-group">
              <input 
                v-model.number="amount" 
                type="number" 
                placeholder="Enter amount"
                min="0"
                step="0.01"
                class="form-control amount-input"
                @input="debouncedConvert"
              >
              <select v-model="fromCurrency" class="form-control currency-select" @change="fetchRateAndConvert">
                <option value="">Select Currency</option>
                <option v-for="currency in currencies" :key="currency" :value="currency">
                  {{ currency }}
                </option>
              </select>
            </div>
          </div>

          <!-- Swap Button -->
          <div class="swap-container">
            <button @click="swapCurrencies" class="swap-button" :disabled="!canSwap">
              <i class="fas fa-exchange-alt"></i>
            </button>
          </div>

          <!-- To Currency -->
          <div class="currency-input-container">
            <label class="form-label">To</label>
            <div class="input-group">
              <input 
                :value="formatNumber(convertedAmount)" 
                type="text" 
                readonly 
                class="form-control amount-input result-input"
                placeholder="Converted amount"
              >
              <select v-model="toCurrency" class="form-control currency-select" @change="fetchRateAndConvert">
                <option value="">Select Currency</option>
                <option v-for="currency in currencies" :key="currency" :value="currency">
                  {{ currency }}
                </option>
              </select>
            </div>
          </div>
        </div>

        <!-- Rate Information -->
        <div v-if="conversionData" class="rate-info-card">
          <div class="rate-header">
            <i class="fas fa-chart-line"></i>
            <span>Exchange Rate Information</span>
          </div>
          <div class="rate-content">
            <div class="rate-display">
              <div class="rate-main">
                1 {{ conversionData.from_currency }} = {{ formatNumber(conversionData.rate) }} {{ conversionData.to_currency }}
              </div>
              <div class="rate-direction">
                <span class="direction-badge" :class="conversionData.direction">
                  {{ conversionData.direction === 'direct' ? 'Direct Rate' : 'Calculated Rate' }}
                </span>
              </div>
            </div>
            <div class="calculation-display">
              <strong>Calculation:</strong> {{ conversionData.calculation }}
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <i class="fas fa-spinner fa-spin"></i>
          <span>Converting currencies...</span>
        </div>

        <!-- Error State -->
        <div v-if="error" class="error-state">
          <i class="fas fa-exclamation-triangle"></i>
          <span>{{ error }}</span>
        </div>
      </div>
    </div>

    <!-- Available Rates -->
    <div class="rates-list-card">
      <div class="card-header">
        <h2>Available Exchange Rates</h2>
        <div class="header-actions">
          <input 
            v-model="filterText" 
            placeholder="Filter currencies..." 
            class="filter-input"
          >
          <button @click="fetchAllRates" class="btn btn-secondary btn-sm">
            <i class="fas fa-sync-alt"></i> Refresh
          </button>
        </div>
      </div>

      <div v-if="loadingRates" class="loading-container">
        <i class="fas fa-spinner fa-spin"></i> Loading rates...
      </div>

      <div v-else-if="ratesError" class="error-container">
        <i class="fas fa-exclamation-triangle"></i> {{ ratesError }}
      </div>

      <div v-else-if="filteredRates.length === 0" class="empty-state">
        <i class="fas fa-exchange-alt"></i>
        <p>No exchange rates available</p>
        <small>Add rates to enable currency conversion</small>
      </div>

      <div v-else class="rates-table-container">
        <table class="rates-table">
          <thead>
            <tr>
              <th>Currency Pair</th>
              <th>Direct Rate</th>
              <th>Reverse Rate</th>
              <th>Effective Date</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="rate in filteredRates" :key="rate.rate_id" class="rate-row">
              <td>
                <div class="currency-pair">
                  <span class="currency-badge">{{ rate.from_currency }}</span>
                  <i class="fas fa-arrows-alt-h"></i>
                  <span class="currency-badge">{{ rate.to_currency }}</span>
                </div>
                <small class="pair-subtitle">Bidirectional conversion enabled</small>
              </td>
              <td class="rate-cell">
                <div class="rate-value">{{ formatNumber(rate.rate) }}</div>
                <small>{{ rate.from_currency }} → {{ rate.to_currency }}</small>
              </td>
              <td class="rate-cell">
                <div class="rate-value">{{ formatNumber(1 / rate.rate) }}</div>
                <small>{{ rate.to_currency }} → {{ rate.from_currency }}</small>
              </td>
              <td>
                <div class="date-info">
                  <div class="date-main">{{ formatDate(rate.effective_date) }}</div>
                  <small v-if="rate.end_date">Until {{ formatDate(rate.end_date) }}</small>
                  <small v-else class="unlimited">No expiry</small>
                </div>
              </td>
              <td>
                <span :class="['status-badge', rate.is_active ? 'status-active' : 'status-inactive']">
                  {{ rate.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="actions-cell">
                <button @click="useRate(rate)" class="btn btn-sm btn-success" title="Use this rate">
                  <i class="fas fa-check"></i>
                </button>
                <router-link :to="`/currency-rates/${rate.rate_id}`" class="btn btn-sm btn-primary" title="View details">
                  <i class="fas fa-eye"></i>
                </router-link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { debounce } from 'lodash';

export default {
  name: 'BidirectionalCurrencyConverter',
  data() {
    return {
      amount: 100,
      fromCurrency: '',
      toCurrency: '',
      convertedAmount: 0,
      conversionData: null,
      currencies: ['USD', 'IDR', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'CNY', 'SGD', 'MYR', 'THB'],
      rates: [],
      loading: false,
      loadingRates: true,
      error: null,
      ratesError: null,
      filterText: ''
    };
  },
  computed: {
    canConvert() {
      return this.amount > 0 && this.fromCurrency && this.toCurrency && this.fromCurrency !== this.toCurrency;
    },
    canSwap() {
      return this.fromCurrency && this.toCurrency && this.fromCurrency !== this.toCurrency;
    },
    filteredRates() {
      if (!this.filterText) return this.rates;
      
      const filter = this.filterText.toUpperCase();
      return this.rates.filter(rate => 
        rate.from_currency.includes(filter) || 
        rate.to_currency.includes(filter)
      );
    }
  },
  created() {
    this.debouncedConvert = debounce(this.performConversion, 300);
  },
  mounted() {
    this.fetchAllRates();
  },
  methods: {
    async fetchAllRates() {
      this.loadingRates = true;
      this.ratesError = null;
      try {
        const response = await axios.get('/accounting/currency-rates', {
          params: { is_active: true }
        });
        
        if (response.data.status === 'success') {
          this.rates = response.data.data;
        }
      } catch (error) {
        console.error('Error fetching rates:', error);
        this.ratesError = 'Failed to load exchange rates';
      } finally {
        this.loadingRates = false;
      }
    },

    async fetchRateAndConvert() {
      if (!this.canConvert) {
        this.resetConversion();
        return;
      }
      await this.performConversion();
    },

    async performConversion() {
      if (!this.canConvert) {
        this.resetConversion();
        return;
      }

      this.loading = true;
      this.error = null;

      try {
        const response = await axios.post('/accounting/currency-rates/convert', {
          from_currency: this.fromCurrency,
          to_currency: this.toCurrency,
          amount: this.amount
        });

        if (response.data.status === 'success') {
          this.conversionData = response.data.data;
          this.convertedAmount = this.conversionData.converted_amount;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Conversion failed';
        this.resetConversion();
      } finally {
        this.loading = false;
      }
    },

    resetConversion() {
      this.convertedAmount = 0;
      this.conversionData = null;
    },

    swapCurrencies() {
      if (!this.canSwap) return;
      
      const temp = this.fromCurrency;
      this.fromCurrency = this.toCurrency;
      this.toCurrency = temp;
      
      if (this.conversionData && this.convertedAmount > 0) {
        this.amount = this.convertedAmount;
      }
      
      this.performConversion();
    },

    useRate(rate) {
      this.fromCurrency = rate.from_currency;
      this.toCurrency = rate.to_currency;
      this.fetchRateAndConvert();
      
      this.$el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },

    formatNumber(value) {
      if (!value && value !== 0) return '';
      return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 6
      }).format(value);
    },

    formatDate(dateString) {
      if (!dateString) return '';
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    }
  }
};
</script>

<style scoped>
/* Add the CSS from the previous artifact - same styling */
.bidirectional-converter-container {
  padding: 1.5rem;
  max-width: 1200px;
  margin: 0 auto;
}
.currency-converter-container {
  padding: 1rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.page-header h1 {
  margin: 0;
  font-size: 1.5rem;
  color: var(--gray-800);
}

.converter-card,
.historical-rates-card {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  margin-bottom: 1.5rem;
  overflow: hidden;
}

.converter-header,
.card-header {
  padding: 1.5rem;
  border-bottom: 1px solid var(--gray-200);
}

.converter-header h2,
.card-header h2 {
  margin: 0 0 0.5rem 0;
  font-size: 1.25rem;
  color: var(--gray-700);
}

.converter-description {
  margin: 0;
  color: var(--gray-600);
}

.converter-form {
  padding: 1.5rem;
  border-bottom: 1px solid var(--gray-200);
}

.form-row {
  margin-bottom: 1.5rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: var(--gray-700);
}

.form-control {
  width: 100%;
  padding: 0.625rem;
  border: 1px solid var(--gray-300);
  border-radius: 0.375rem;
  font-size: 1rem;
}

.form-control:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
}

.currency-exchange-container {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.currency-input-container {
  flex: 1;
}

.swap-button {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 2.5rem;
  height: 2.5rem;
  background-color: var(--gray-100);
  border: 1px solid var(--gray-300);
  border-radius: 50%;
  color: var(--gray-600);
  cursor: pointer;
  transition: all 0.2s;
}

.swap-button:hover {
  background-color: var(--gray-200);
  color: var(--gray-800);
}

.rate-loading,
.rate-error,
.rate-info {
  margin-bottom: 1.5rem;
  padding: 1rem;
  border-radius: 0.375rem;
}

.rate-loading {
  background-color: var(--gray-100);
  color: var(--gray-600);
}

.rate-error {
  background-color: #fee2e2;
  color: #b91c1c;
}

.rate-info {
  background-color: #e0f2fe;
  color: #0369a1;
}

.rate-details {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.conversion-rate {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 500;
}

.equals {
  margin: 0 0.25rem;
  color: var(--gray-500);
}

.rate-value {
  font-weight: 600;
}

.rate-date {
  font-size: 0.875rem;
  opacity: 0.8;
}

.convert-btn {
  width: 100%;
  padding: 0.75rem;
  font-size: 1rem;
}

.conversion-result {
  padding: 1.5rem;
  background-color: #f0fdf4;
}

.result-header {
  margin-bottom: 1rem;
  font-size: 1.125rem;
  font-weight: 600;
  color: #15803d;
}

.result-container {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.result-amount {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.amount-from,
.amount-to {
  padding: 0.75rem 1.25rem;
  border-radius: 0.375rem;
  font-size: 1.25rem;
  font-weight: 600;
}

.amount-from {
  background-color: white;
  border: 1px solid #d1fae5;
  color: var(--gray-800);
}

.amount-equals {
  color: #059669;
}

.amount-to {
  background-color: #10b981;
  color: white;
}

.result-rate {
  font-size: 0.875rem;
  color: var(--gray-600);
}

.filter-container {
  margin-top: 1rem;
}

.loading-container {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 2rem;
}

.alert {
  margin: 1.5rem;
  padding: 1rem;
  border-radius: 0.375rem;
}

.alert-danger {
  background-color: #fee2e2;
  color: #b91c1c;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 3rem 1rem;
  text-align: center;
}

.empty-icon {
  font-size: 3rem;
  color: var(--gray-400);
  margin-bottom: 1rem;
}

.empty-state h3 {
  font-size: 1.25rem;
  margin-bottom: 0.5rem;
}

.empty-state p {
  max-width: 400px;
  margin-bottom: 1.5rem;
  color: var(--gray-600);
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table th,
.table td {
  padding: 0.75rem 1rem;
  text-align: left;
  border-bottom: 1px solid var(--gray-200);
}

.table th {
  font-weight: 600;
  background-color: var(--gray-50);
}

.sortable {
  cursor: pointer;
  user-select: none;
}

.sortable:hover {
  background-color: var(--gray-100);
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  font-weight: 600;
}

.status-active {
  background-color: #a7f3d0;
  color: #065f46;
}

.status-inactive {
  background-color: #fecaca;
  color: #7f1d1d;
}

.actions-cell {
  display: flex;
  gap: 0.5rem;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  font-weight: 500;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
  text-decoration: none;
}

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
}

.btn-primary {
  background-color: var(--primary-color);
  color: white;
}

.btn-primary:hover {
  background-color: var(--primary-dark);
}

.btn-primary:disabled {
  background-color: var(--gray-300);
  cursor: not-allowed;
}

.btn-secondary {
  background-color: var(--gray-600);
  color: white;
}

.btn-secondary:hover {
  background-color: var(--gray-700);
}

.btn-info {
  background-color: #0ea5e9;
  color: white;
}

.btn-info:hover {
  background-color: #0284c7;
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .currency-exchange-container {
    flex-direction: column;
    gap: 1.5rem;
  }
  
  .swap-button {
    align-self: center;
    transform: rotate(90deg);
  }
  
  .result-amount {
    flex-direction: column;
    align-items: stretch;
  }
  
  .amount-equals {
    align-self: center;
    transform: rotate(90deg);
    margin: 0.5rem 0;
  }
  
  .table-responsive {
    overflow-x: auto;
  }
}
</style>