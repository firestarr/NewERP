<!-- Updated Simple CurrencyConverter.vue - Replace your existing component -->
<template>
  <div class="currency-converter">
    <!-- Header -->
    <div class="converter-header">
      <h2>
        <i class="fas fa-exchange-alt"></i>
        Currency Converter
      </h2>
      <p class="subtitle">Convert between currencies with real-time bidirectional rates</p>
    </div>

    <!-- Main Conversion Panel -->
    <div class="conversion-container">
      <div class="conversion-panel">
        <!-- From Currency Section -->
        <div class="currency-section from-section">
          <div class="section-header">
            <label class="currency-label">From</label>
            <div v-if="fromCurrencyInfo" class="currency-info">
              <span class="currency-symbol">{{ fromCurrencyInfo.symbol }}</span>
              <span class="currency-name">{{ fromCurrencyInfo.name }}</span>
            </div>
          </div>
          
          <div class="input-group">
            <select 
              v-model="fromCurrency" 
              @change="fetchRate"
              class="currency-select"
            >
              <option value="">Select Currency</option>
              <option 
                v-for="currency in currencies" 
                :key="`from-${currency.code}`" 
                :value="currency.code"
              >
                {{ currency.code }} - {{ currency.name }}
              </option>
            </select>
            
            <div class="amount-input-wrapper">
              <input
                v-model.number="amount"
                type="number"
                min="0"
                step="0.01"
                placeholder="Enter amount"
                class="amount-input"
                @input="handleAmountChange"
              />
            </div>
          </div>
        </div>

        <!-- Swap Button -->
        <div class="swap-section">
          <button 
            @click="swapCurrencies" 
            class="swap-btn"
            :disabled="!canSwap || loadingRate"
            :class="{ 'swapping': isSwapping }"
            title="Swap currencies (Ctrl+↑↓)"
          >
            <i class="fas fa-exchange-alt"></i>
          </button>
          <div class="swap-hint">Ctrl+↑↓</div>
        </div>

        <!-- To Currency Section -->
        <div class="currency-section to-section">
          <div class="section-header">
            <label class="currency-label">To</label>
            <div v-if="toCurrencyInfo" class="currency-info">
              <span class="currency-symbol">{{ toCurrencyInfo.symbol }}</span>
              <span class="currency-name">{{ toCurrencyInfo.name }}</span>
            </div>
          </div>
          
          <div class="input-group">
            <select 
              v-model="toCurrency" 
              @change="fetchRate"
              class="currency-select"
            >
              <option value="">Select Currency</option>
              <option 
                v-for="currency in currencies" 
                :key="`to-${currency.code}`" 
                :value="currency.code"
              >
                {{ currency.code }} - {{ currency.name }}
              </option>
            </select>
            
            <div class="amount-display-wrapper">
              <input
                :value="formatAmount(convertedAmount, toCurrency)"
                type="text"
                readonly
                placeholder="0.00"
                class="amount-display"
                :class="{ 'converted': convertedAmount > 0 }"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Enhanced Rate Information -->
      <div v-if="showRateInfo" class="rate-info-panel" :class="rateInfoClass">
        <div class="rate-main">
          <div class="rate-equation">
            <span class="rate-amount">1</span>
            <span class="rate-currency">{{ fromCurrency }}</span>
            <span class="rate-equals">=</span>
            <span class="rate-value">{{ formatNumber(exchangeRate, 6) }}</span>
            <span class="rate-currency">{{ toCurrency }}</span>
          </div>
          
          <div class="rate-metadata">
            <div class="rate-direction-badge" :class="directionBadgeClass">
              <i :class="directionIcon"></i>
              <span>{{ directionText }}</span>
            </div>
            
            <div class="rate-confidence" :class="confidenceClass">
              <i class="fas fa-signal"></i>
              <span>{{ rateConfidence }} confidence</span>
            </div>

            <div class="rate-date">
              <i class="fas fa-calendar-alt"></i>
              <span>{{ formatDate(rateDate) }}</span>
            </div>
          </div>
        </div>

        <div class="rate-details">
          <div v-if="calculationPath" class="calculation-path">
            <i class="fas fa-route"></i>
            <span>{{ calculationPath }}</span>
          </div>
          
          <div v-if="rateFromCache" class="cache-indicator">
            <i class="fas fa-clock"></i>
            <span>Cached rate</span>
          </div>
        </div>

        <!-- Reverse Rate Display -->
        <div class="reverse-rate">
          <span class="reverse-label">Reverse:</span>
          <span class="reverse-equation">
            1 {{ toCurrency }} = {{ formatNumber(1/exchangeRate, 6) }} {{ fromCurrency }}
          </span>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loadingRate" class="loading-panel">
        <div class="loading-spinner">
          <i class="fas fa-spinner fa-spin"></i>
        </div>
        <div class="loading-text">{{ loadingMessage }}</div>
      </div>

      <!-- Error State -->
      <div v-if="error" class="error-panel">
        <div class="error-icon">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="error-content">
          <div class="error-title">Rate Not Available</div>
          <div class="error-message">{{ error }}</div>
          <div class="error-actions">
            <button @click="retryFetchRate" class="retry-btn">
              <i class="fas fa-redo"></i>
              Retry
            </button>
            <button @click="analyzeRates" class="analyze-btn">
              <i class="fas fa-search"></i>
              Analyze
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
      <div class="quick-amounts">
        <span class="quick-label">Quick amounts:</span>
        <button 
          v-for="quickAmount in quickAmounts" 
          :key="quickAmount"
          @click="setQuickAmount(quickAmount)"
          class="quick-amount-btn"
        >
          {{ formatNumber(quickAmount) }}
        </button>
      </div>
      
      <div class="action-buttons">
        <button @click="copyResult" class="action-btn" :disabled="!convertedAmount">
          <i class="fas fa-copy"></i>
          Copy Result
        </button>
        
        <button @click="openAdvancedCalculator" class="action-btn primary">
          <i class="fas fa-calculator"></i>
          Advanced Calculator
        </button>
      </div>
    </div>

    <!-- Recent Rates (if available) -->
    <div v-if="recentRates.length > 0" class="recent-rates">
      <h3>
        <i class="fas fa-history"></i>
        Recent Rates
      </h3>
      <div class="rates-grid">
        <div 
          v-for="rate in recentRates" 
          :key="`${rate.from_currency}_${rate.to_currency}`"
          class="rate-card"
          @click="useRecentRate(rate)"
        >
          <div class="rate-header">
            <span class="currency-pair">{{ rate.from_currency }}/{{ rate.to_currency }}</span>
            <span class="rate-direction" :class="`direction-${rate.direction}`">
              {{ rate.direction.toUpperCase() }}
            </span>
          </div>
          <div class="rate-value">{{ formatNumber(rate.rate, 4) }}</div>
          <div class="rate-time">{{ formatRelativeTime(rate.timestamp) }}</div>
        </div>
      </div>
    </div>

    <!-- Rate Analysis Modal -->
    <div v-if="showAnalysis" class="modal-overlay" @click="closeAnalysis">
      <div class="analysis-modal" @click.stop>
        <div class="modal-header">
          <h3>Rate Analysis: {{ fromCurrency }} → {{ toCurrency }}</h3>
          <button @click="closeAnalysis" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>
        
        <div class="modal-content">
          <div v-if="analysisData" class="analysis-content">
            <div class="available-paths">
              <h4>Available Rate Paths</h4>
              <div 
                v-for="(path, method) in analysisData.available_paths" 
                :key="method"
                class="path-item"
                :class="{ 'recommended': method === analysisData.recommended_path?.direction }"
              >
                <div class="path-header">
                  <span class="path-method">{{ method.toUpperCase() }}</span>
                  <span class="path-rate">{{ formatNumber(path.rate, 6) }}</span>
                  <span class="path-confidence" :class="`confidence-${path.confidence}`">
                    {{ path.confidence }}
                  </span>
                </div>
                <div v-if="path.calculation_path" class="path-details">
                  {{ path.calculation_path }}
                </div>
              </div>
            </div>
            
            <div v-if="!Object.keys(analysisData.available_paths).length" class="no-paths">
              <i class="fas fa-exclamation-circle"></i>
              <p>No rate paths available for this currency pair.</p>
              <p>Consider adding direct rates or checking currency support.</p>
            </div>
          </div>
          
          <div v-else-if="loadingAnalysis" class="loading-analysis">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Analyzing rate paths...</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Toast Notifications -->
    <div class="toast-container">
      <div 
        v-for="toast in toasts" 
        :key="toast.id"
        class="toast"
        :class="toast.type"
      >
        <i :class="getToastIcon(toast.type)"></i>
        <span>{{ toast.message }}</span>
        <button @click="removeToast(toast.id)" class="toast-close">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
/* eslint-disable */
import { CurrencyService, CurrencyUtils } from '@/services/CurrencyService';

export default {
  name: 'CurrencyConverter',
  data() {
    return {
      // Currency data
      amount: 1,
      fromCurrency: 'USD',
      toCurrency: 'IDR',
      currencies: [],
      
      // Rate information
      exchangeRate: 0,
      convertedAmount: 0,
      rateDate: null,
      rateDirection: null,
      rateConfidence: null,
      calculationPath: null,
      sourceRateId: null,
      rateFromCache: false,
      
      // UI states
      loadingRate: false,
      loadingCurrencies: false,
      loadingAnalysis: false,
      isSwapping: false,
      
      // Error handling
      error: null,
      
      // Analysis modal
      showAnalysis: false,
      analysisData: null,
      
      // Recent rates
      recentRates: [],
      
      // Quick amounts
      quickAmounts: [100, 500, 1000, 5000, 10000],
      
      // Toast notifications
      toasts: [],
      toastId: 0,
      
      // Debounce timer
      fetchRateTimer: null,
      
      // Loading messages
      loadingMessages: [
        'Fetching exchange rate...',
        'Checking direct rates...',
        'Trying reverse calculation...',
        'Analyzing cross-currency paths...'
      ],
      currentLoadingIndex: 0
    };
  },
  
  computed: {
    canSwap() {
      return this.fromCurrency && this.toCurrency && !this.loadingRate;
    },
    
    showRateInfo() {
      return this.exchangeRate > 0 && this.fromCurrency && this.toCurrency && !this.error;
    },
    
    fromCurrencyInfo() {
      return this.currencies.find(c => c.code === this.fromCurrency);
    },
    
    toCurrencyInfo() {
      return this.currencies.find(c => c.code === this.toCurrency);
    },
    
    rateInfoClass() {
      return {
        'rate-info-direct': this.rateDirection === 'direct',
        'rate-info-inverse': this.rateDirection === 'inverse',
        'rate-info-cross': this.rateDirection === 'cross',
        'rate-info-cached': this.rateFromCache
      };
    },
    
    directionBadgeClass() {
      return {
        'badge-direct': this.rateDirection === 'direct',
        'badge-inverse': this.rateDirection === 'inverse',
        'badge-cross': this.rateDirection === 'cross',
        'badge-same': this.rateDirection === 'same'
      };
    },
    
    directionIcon() {
      const icons = {
        direct: 'fas fa-arrow-right',
        inverse: 'fas fa-undo-alt',
        cross: 'fas fa-route',
        same: 'fas fa-equals'
      };
      return icons[this.rateDirection] || 'fas fa-question';
    },
    
    directionText() {
      const texts = {
        direct: 'Direct Rate',
        inverse: 'Calculated (Inverse)',
        cross: 'Cross Currency',
        same: 'Same Currency'
      };
      return texts[this.rateDirection] || 'Unknown';
    },
    
    confidenceClass() {
      return {
        'confidence-high': this.rateConfidence === 'high',
        'confidence-medium': this.rateConfidence === 'medium',
        'confidence-low': this.rateConfidence === 'low'
      };
    },
    
    loadingMessage() {
      return this.loadingMessages[this.currentLoadingIndex] || 'Loading...';
    }
  },
  
  watch: {
    amount() {
      this.calculateConversion();
    },
    
    exchangeRate() {
      this.calculateConversion();
    }
  },
  
  mounted() {
    this.initializeConverter();
    this.setupKeyboardShortcuts();
    this.loadRecentRates();
  },
  
  beforeUnmount() {
    this.cleanupTimers();
    document.removeEventListener('keydown', this.handleKeyboard);
  },
  
  methods: {
    async initializeConverter() {
      await this.fetchCurrencies();
      await this.fetchRate();
    },
    
    setupKeyboardShortcuts() {
      document.addEventListener('keydown', this.handleKeyboard);
    },
    
    handleKeyboard(event) {
      // Ctrl + Up/Down arrows to swap currencies
      if (event.ctrlKey && (event.key === 'ArrowUp' || event.key === 'ArrowDown')) {
        event.preventDefault();
        this.swapCurrencies();
      }
    },
    
    cleanupTimers() {
      if (this.fetchRateTimer) {
        clearTimeout(this.fetchRateTimer);
      }
    },
    
    async fetchCurrencies() {
      this.loadingCurrencies = true;
      
      try {
        const response = await CurrencyService.getAllCurrencies();
        
        if (response.data.status === 'success') {
          this.currencies = response.data.data;
        } else {
          this.showToast('Failed to load currencies', 'error');
        }
      } catch (error) {
        console.error('Error fetching currencies:', error);
        this.showToast('Error loading currencies', 'error');
        
        // Fallback currencies
        this.currencies = [
          { code: 'USD', name: 'US Dollar', symbol: '$', decimal_places: 2 },
          { code: 'EUR', name: 'Euro', symbol: '€', decimal_places: 2 },
          { code: 'GBP', name: 'British Pound', symbol: '£', decimal_places: 2 },
          { code: 'JPY', name: 'Japanese Yen', symbol: '¥', decimal_places: 0 },
          { code: 'IDR', name: 'Indonesian Rupiah', symbol: 'Rp', decimal_places: 0 }
        ];
      } finally {
        this.loadingCurrencies = false;
      }
    },
    
    async fetchRate() {
      if (!this.fromCurrency || !this.toCurrency) {
        this.resetRateInfo();
        return;
      }
      
      if (this.fromCurrency === this.toCurrency) {
        this.handleSameCurrency();
        return;
      }
      
      this.loadingRate = true;
      this.error = null;
      this.currentLoadingIndex = 0;
      
      // Cycle through loading messages
      const loadingInterval = setInterval(() => {
        this.currentLoadingIndex = (this.currentLoadingIndex + 1) % this.loadingMessages.length;
      }, 1000);
      
      try {
        const response = await CurrencyService.getBidirectionalRate(
          this.fromCurrency,
          this.toCurrency,
          new Date().toISOString().split('T')[0]
        );
        
        if (response.data.status === 'success') {
          const data = response.data.data;
          this.updateRateInfo(data);
          this.calculateConversion();
          this.addToRecentRates(data);
        } else {
          this.handleRateError('Failed to fetch exchange rate');
        }
      } catch (error) {
        console.error('Error fetching exchange rate:', error);
        
        if (error.response?.status === 404) {
          this.handleRateError(`No exchange rate available for ${this.fromCurrency} ↔ ${this.toCurrency}`);
        } else {
          this.handleRateError(error.response?.data?.message || 'Network error occurred');
        }
      } finally {
        clearInterval(loadingInterval);
        this.loadingRate = false;
      }
    },
    
    updateRateInfo(data) {
      this.exchangeRate = data.rate;
      this.rateDate = data.date;
      this.rateDirection = data.direction;
      this.rateConfidence = data.confidence;
      this.calculationPath = data.calculation_path;
      this.sourceRateId = data.source_rate_id;
      this.rateFromCache = data.cached || false;
    },
    
    handleSameCurrency() {
      this.exchangeRate = 1;
      this.rateDate = new Date().toISOString().split('T')[0];
      this.rateDirection = 'same';
      this.rateConfidence = 'high';
      this.calculationPath = null;
      this.sourceRateId = null;
      this.rateFromCache = false;
      this.error = null;
      this.calculateConversion();
    },
    
    resetRateInfo() {
      this.exchangeRate = 0;
      this.convertedAmount = 0;
      this.rateDate = null;
      this.rateDirection = null;
      this.rateConfidence = null;
      this.calculationPath = null;
      this.sourceRateId = null;
      this.rateFromCache = false;
    },
    
    handleRateError(message) {
      this.error = message;
      this.resetRateInfo();
    },
    
    calculateConversion() {
      if (this.amount && this.exchangeRate > 0) {
        this.convertedAmount = this.amount * this.exchangeRate;
      } else {
        this.convertedAmount = 0;
      }
    },
    
    handleAmountChange() {
      this.calculateConversion();
    },
    
    async swapCurrencies() {
      if (!this.canSwap) return;
      
      this.isSwapping = true;
      
      // Swap currencies
      const temp = this.fromCurrency;
      this.fromCurrency = this.toCurrency;
      this.toCurrency = temp;
      
      // Swap amounts if there's a valid conversion
      if (this.convertedAmount > 0 && this.exchangeRate > 0) {
        const tempAmount = this.amount;
        this.amount = this.convertedAmount;
        this.convertedAmount = tempAmount;
      }
      
      // Add visual feedback
      setTimeout(() => {
        this.isSwapping = false;
      }, 300);
      
      // Fetch new rate
      await this.fetchRate();
      this.showToast('Currencies swapped', 'success');
    },
    
    setQuickAmount(amount) {
      this.amount = amount;
      this.calculateConversion();
    },
    
    async retryFetchRate() {
      await this.fetchRate();
    },
    
    async analyzeRates() {
      if (!this.fromCurrency || !this.toCurrency) return;
      
      this.showAnalysis = true;
      this.loadingAnalysis = true;
      this.analysisData = null;
      
      try {
        const response = await CurrencyService.analyzeRatePaths(
          this.fromCurrency,
          this.toCurrency,
          new Date().toISOString().split('T')[0]
        );
        
        if (response.data.status === 'success') {
          this.analysisData = response.data.data;
        } else {
          this.showToast('Failed to analyze rates', 'error');
        }
      } catch (error) {
        console.error('Error analyzing rates:', error);
        this.showToast('Error during rate analysis', 'error');
      } finally {
        this.loadingAnalysis = false;
      }
    },
    
    closeAnalysis() {
      this.showAnalysis = false;
      this.analysisData = null;
    },
    
    async copyResult() {
      if (!this.convertedAmount) return;
      
      const text = `${this.formatAmount(this.amount, this.fromCurrency)} = ${this.formatAmount(this.convertedAmount, this.toCurrency)}`;
      
      try {
        await navigator.clipboard.writeText(text);
        this.showToast('Result copied to clipboard', 'success');
      } catch (error) {
        console.error('Failed to copy:', error);
        this.showToast('Failed to copy result', 'error');
      }
    },
    
    openAdvancedCalculator() {
      this.$router.push('/currency/converter/advanced');
    },
    
    // Recent Rates Management
    addToRecentRates(rateData) {
      const rateKey = `${rateData.from_currency}_${rateData.to_currency}`;
      
      // Remove existing rate for this pair
      this.recentRates = this.recentRates.filter(rate => 
        `${rate.from_currency}_${rate.to_currency}` !== rateKey
      );
      
      // Add new rate to the beginning
      this.recentRates.unshift({
        ...rateData,
        timestamp: new Date().toISOString()
      });
      
      // Keep only last 5 rates
      if (this.recentRates.length > 5) {
        this.recentRates = this.recentRates.slice(0, 5);
      }
      
      this.saveRecentRates();
    },
    
    saveRecentRates() {
      localStorage.setItem('currency_converter_recent_rates', JSON.stringify(this.recentRates));
    },
    
    loadRecentRates() {
      const saved = localStorage.getItem('currency_converter_recent_rates');
      if (saved) {
        this.recentRates = JSON.parse(saved);
      }
    },
    
    useRecentRate(rate) {
      this.fromCurrency = rate.from_currency;
      this.toCurrency = rate.to_currency;
      this.fetchRate();
    },
    
    // Utility Methods
    formatNumber(value, decimals = 2) {
      if (typeof value !== 'number' || isNaN(value)) return '0.00';
      
      return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
      }).format(value);
    },
    
    formatAmount(amount, currency) {
      if (!amount || !currency) return '0.00';
      
      const currencyInfo = this.currencies.find(c => c.code === currency);
      const decimals = currencyInfo?.decimal_places ?? 2;
      
      return this.formatNumber(amount, decimals);
    },
    
    formatDate(dateString) {
      if (!dateString) return '';
      
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    },
    
    formatRelativeTime(timestamp) {
      if (!timestamp) return '';
      
      const now = new Date();
      const time = new Date(timestamp);
      const diffMinutes = Math.floor((now - time) / 60000);
      
      if (diffMinutes < 1) return 'Just now';
      if (diffMinutes < 60) return `${diffMinutes}m ago`;
      if (diffMinutes < 1440) return `${Math.floor(diffMinutes / 60)}h ago`;
      return `${Math.floor(diffMinutes / 1440)}d ago`;
    },
    
    // Toast notification system
    showToast(message, type = 'info') {
      const toast = {
        id: ++this.toastId,
        message,
        type
      };
      
      this.toasts.push(toast);
      
      // Auto remove after 3 seconds
      setTimeout(() => {
        this.removeToast(toast.id);
      }, 3000);
    },
    
    removeToast(id) {
      const index = this.toasts.findIndex(t => t.id === id);
      if (index > -1) {
        this.toasts.splice(index, 1);
      }
    },
    
    getToastIcon(type) {
      const icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-info-circle'
      };
      return icons[type] || icons.info;
    }
  }
};
</script>

<style scoped>
/* Base Styles */
.currency-converter {
  max-width: 800px;
  margin: 0 auto;
  padding: 20px;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Header */
.converter-header {
  text-align: center;
  margin-bottom: 30px;
}

.converter-header h2 {
  color: #2c3e50;
  margin-bottom: 8px;
  font-size: 28px;
  font-weight: 600;
}

.converter-header h2 i {
  color: #3498db;
  margin-right: 12px;
}

.subtitle {
  color: #7f8c8d;
  font-size: 16px;
  margin: 0;
}

/* Main Container */
.conversion-container {
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.1);
  overflow: hidden;
  margin-bottom: 24px;
}

/* Conversion Panel */
.conversion-panel {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: stretch;
  min-height: 140px;
}

.currency-section {
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.from-section {
  border-right: 1px solid #ecf0f1;
  border-bottom: 3px solid #3498db;
}

.to-section {
  border-bottom: 3px solid #2ecc71;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.currency-label {
  font-weight: 600;
  color: #2c3e50;
  font-size: 14px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.currency-info {
  font-size: 12px;
  color: #7f8c8d;
  text-align: right;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 2px;
}

.currency-symbol {
  font-weight: bold;
  color: #2c3e50;
}

.currency-name {
  font-size: 11px;
}

.input-group {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.currency-select {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #ecf0f1;
  border-radius: 8px;
  font-size: 14px;
  background: #ffffff;
  transition: all 0.3s ease;
}

.currency-select:focus {
  outline: none;
  border-color: #3498db;
  box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.amount-input-wrapper,
.amount-display-wrapper {
  position: relative;
}

.amount-input,
.amount-display {
  width: 100%;
  padding: 16px;
  border: 2px solid #ecf0f1;
  border-radius: 8px;
  font-size: 20px;
  font-weight: 600;
  transition: all 0.3s ease;
  text-align: center;
}

.amount-input {
  background: #ffffff;
  color: #2c3e50;
}

.amount-input:focus {
  outline: none;
  border-color: #3498db;
  box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.amount-display {
  background: #f8f9fa;
  color: #7f8c8d;
  cursor: default;
  border-color: #ddd;
}

.amount-display.converted {
  background: #e8f5e8;
  border-color: #2ecc71;
  color: #27ae60;
  font-weight: 700;
}

/* Swap Section */
.swap-section {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding: 24px 16px;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-left: 1px solid #ecf0f1;
  border-right: 1px solid #ecf0f1;
  min-width: 80px;
  gap: 8px;
}

.swap-btn {
  width: 56px;
  height: 56px;
  border: 3px solid #3498db;
  background: #ffffff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 20px;
  color: #3498db;
}

.swap-btn:hover:not(:disabled) {
  background: #3498db;
  color: #ffffff;
  transform: rotate(180deg) scale(1.1);
  box-shadow: 0 6px 16px rgba(52, 152, 219, 0.4);
}

.swap-btn:disabled {
  border-color: #bdc3c7;
  color: #bdc3c7;
  cursor: not-allowed;
  transform: none;
}

.swap-btn.swapping {
  transform: rotate(180deg);
}

.swap-hint {
  font-size: 10px;
  color: #95a5a6;
  text-align: center;
  font-weight: 500;
}

/* Rate Info Panel */
.rate-info-panel {
  padding: 24px;
  background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
  border-top: 1px solid #ecf0f1;
}

.rate-info-direct {
  border-left: 4px solid #2ecc71;
}

.rate-info-inverse {
  border-left: 4px solid #f39c12;
}

.rate-info-cross {
  border-left: 4px solid #9b59b6;
}

.rate-info-cached {
  background: linear-gradient(135deg, #e8f5e8 0%, #ffffff 100%);
}

.rate-main {
  margin-bottom: 16px;
}

.rate-equation {
  text-align: center;
  font-size: 20px;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 12px;
}

.rate-amount,
.rate-currency {
  margin: 0 8px;
}

.rate-equals {
  margin: 0 12px;
  color: #7f8c8d;
  font-weight: normal;
}

.rate-value {
  color: #2ecc71;
  font-weight: 700;
}

.rate-metadata {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
  margin-bottom: 12px;
}

.rate-direction-badge {
  padding: 6px 12px;
  border-radius: 16px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  display: flex;
  align-items: center;
  gap: 4px;
}

.badge-direct {
  background: #d5f4e6;
  color: #27ae60;
}

.badge-inverse {
  background: #fef9e7;
  color: #f39c12;
}

.badge-cross {
  background: #f4ecf7;
  color: #9b59b6;
}

.badge-same {
  background: #e8f4f8;
  color: #3498db;
}

.rate-confidence {
  font-size: 12px;
  display: flex;
  align-items: center;
  gap: 4px;
  font-weight: 500;
}

.confidence-high {
  color: #27ae60;
}

.confidence-medium {
  color: #f39c12;
}

.confidence-low {
  color: #e74c3c;
}

.rate-date {
  font-size: 12px;
  color: #7f8c8d;
  display: flex;
  align-items: center;
  gap: 4px;
}

.rate-details {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  font-size: 13px;
  color: #7f8c8d;
  margin-bottom: 12px;
  flex-wrap: wrap;
}

.calculation-path,
.cache-indicator {
  display: flex;
  align-items: center;
  gap: 6px;
}

.reverse-rate {
  text-align: center;
  font-size: 14px;
  color: #7f8c8d;
  padding-top: 12px;
  border-top: 1px solid #ecf0f1;
}

.reverse-label {
  font-weight: 500;
  margin-right: 8px;
}

/* Loading Panel */
.loading-panel {
  padding: 32px 24px;
  text-align: center;
  background: linear-gradient(135deg, #e8f4f8 0%, #ffffff 100%);
  border-top: 1px solid #ecf0f1;
}

.loading-spinner {
  font-size: 32px;
  color: #3498db;
  margin-bottom: 16px;
}

.loading-text {
  color: #7f8c8d;
  font-size: 14px;
  font-weight: 500;
}

/* Error Panel */
.error-panel {
  padding: 24px;
  background: linear-gradient(135deg, #fdf2f2 0%, #ffffff 100%);
  border-top: 1px solid #ecf0f1;
  border-left: 4px solid #e74c3c;
  display: flex;
  align-items: center;
  gap: 16px;
}

.error-icon {
  font-size: 32px;
  color: #e74c3c;
  flex-shrink: 0;
}

.error-content {
  flex: 1;
}

.error-title {
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 4px;
  font-size: 16px;
}

.error-message {
  color: #7f8c8d;
  font-size: 14px;
  margin-bottom: 12px;
}

.error-actions {
  display: flex;
  gap: 8px;
}

.retry-btn,
.analyze-btn {
  padding: 8px 16px;
  border: 1px solid #e74c3c;
  background: transparent;
  color: #e74c3c;
  border-radius: 4px;
  font-size: 12px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-weight: 500;
}

.retry-btn:hover,
.analyze-btn:hover {
  background: #e74c3c;
  color: #ffffff;
}

/* Quick Actions */
.quick-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  padding: 20px;
  background: #f8f9fa;
  border-radius: 8px;
  flex-wrap: wrap;
  gap: 16px;
}

.quick-amounts {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.quick-label {
  font-size: 14px;
  color: #7f8c8d;
  font-weight: 500;
}

.quick-amount-btn {
  padding: 8px 12px;
  background: #ecf0f1;
  border: 1px solid #bdc3c7;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 13px;
  color: #2c3e50;
  font-weight: 500;
}

.quick-amount-btn:hover {
  background: #3498db;
  color: white;
  border-color: #3498db;
  transform: translateY(-1px);
}

.action-buttons {
  display: flex;
  gap: 8px;
}

.action-btn {
  padding: 10px 16px;
  border: 1px solid #bdc3c7;
  background: white;
  color: #7f8c8d;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 14px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 6px;
}

.action-btn:hover:not(:disabled) {
  background: #ecf0f1;
  color: #2c3e50;
  border-color: #95a5a6;
}

.action-btn.primary {
  background: #3498db;
  color: white;
  border-color: #3498db;
}

.action-btn.primary:hover {
  background: #2980b9;
  border-color: #2980b9;
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Recent Rates */
.recent-rates {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  padding: 20px;
  margin-bottom: 24px;
}

.recent-rates h3 {
  color: #2c3e50;
  margin-bottom: 16px;
  font-size: 18px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}

.rates-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 12px;
}

.rate-card {
  padding: 16px;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #ecf0f1;
  cursor: pointer;
  transition: all 0.3s ease;
}

.rate-card:hover {
  background: #e3f2fd;
  border-color: #3498db;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(52, 152, 219, 0.2);
}

.rate-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.currency-pair {
  font-weight: 600;
  color: #2c3e50;
  font-size: 14px;
}

.rate-direction {
  font-size: 10px;
  padding: 2px 6px;
  border-radius: 8px;
  font-weight: 600;
  text-transform: uppercase;
}

.direction-direct {
  background: #d5f4e6;
  color: #27ae60;
}

.direction-inverse {
  background: #fef9e7;
  color: #f39c12;
}

.direction-cross {
  background: #f4ecf7;
  color: #9b59b6;
}

.rate-card .rate-value {
  font-size: 16px;
  font-weight: 700;
  color: #2ecc71;
  margin-bottom: 4px;
}

.rate-time {
  font-size: 12px;
  color: #7f8c8d;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.analysis-modal {
  background: #ffffff;
  border-radius: 12px;
  max-width: 600px;
  width: 90%;
  max-height: 80vh;
  overflow: hidden;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}

.modal-header {
  padding: 20px 24px;
  border-bottom: 1px solid #ecf0f1;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f8f9fa;
}

.modal-header h3 {
  margin: 0;
  color: #2c3e50;
  font-size: 18px;
}

.close-btn {
  background: none;
  border: none;
  font-size: 16px;
  color: #7f8c8d;
  cursor: pointer;
  padding: 8px;
  border-radius: 4px;
  transition: all 0.3s ease;
}

.close-btn:hover {
  background: #ecf0f1;
  color: #2c3e50;
}

.modal-content {
  padding: 24px;
  max-height: 60vh;
  overflow-y: auto;
}

.available-paths h4 {
  margin-bottom: 16px;
  color: #2c3e50;
}

.path-item {
  padding: 16px;
  border: 1px solid #ecf0f1;
  border-radius: 8px;
  margin-bottom: 12px;
  transition: all 0.3s ease;
}

.path-item:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.path-item.recommended {
  border-color: #2ecc71;
  background: #f8fffe;
}

.path-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.path-method {
  font-weight: 600;
  text-transform: uppercase;
  font-size: 12px;
  letter-spacing: 0.5px;
}

.path-rate {
  font-weight: 700;
  color: #2ecc71;
}

.path-confidence {
  font-size: 12px;
  padding: 2px 6px;
  border-radius: 8px;
}

.path-details {
  font-size: 13px;
  color: #7f8c8d;
}

.no-paths {
  text-align: center;
  padding: 32px;
  color: #7f8c8d;
}

.no-paths i {
  font-size: 48px;
  margin-bottom: 16px;
  color: #bdc3c7;
}

.loading-analysis {
  text-align: center;
  padding: 32px;
  color: #7f8c8d;
}

.loading-analysis i {
  font-size: 24px;
  margin-bottom: 12px;
  color: #3498db;
}

/* Toast Notifications */
.toast-container {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 1100;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.toast {
  padding: 12px 16px;
  border-radius: 8px;
  color: #ffffff;
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 250px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  animation: slideIn 0.3s ease;
}

.toast.success {
  background: #27ae60;
}

.toast.error {
  background: #e74c3c;
}

.toast.warning {
  background: #f39c12;
}

.toast.info {
  background: #3498db;
}

.toast-close {
  background: none;
  border: none;
  color: #ffffff;
  cursor: pointer;
  padding: 4px;
  margin-left: auto;
  border-radius: 4px;
  transition: all 0.3s ease;
}

.toast-close:hover {
  background: rgba(255, 255, 255, 0.2);
}

@keyframes slideIn {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  .currency-converter {
    padding: 16px;
  }
  
  .conversion-panel {
    grid-template-columns: 1fr;
    gap: 0;
  }
  
  .currency-section {
    border-right: none;
    border-bottom: 1px solid #ecf0f1;
  }
  
  .swap-section {
    flex-direction: row;
    justify-content: center;
    padding: 16px;
    border-left: none;
    border-right: none;
    border-top: 1px solid #ecf0f1;
    border-bottom: 1px solid #ecf0f1;
  }
  
  .rate-main {
    text-align: center;
  }
  
  .rate-metadata {
    justify-content: center;
  }
  
  .rate-details {
    justify-content: center;
  }
  
  .quick-actions {
    flex-direction: column;
    align-items: stretch;
  }
  
  .quick-amounts {
    justify-content: center;
  }
  
  .action-buttons {
    justify-content: center;
  }
  
  .rates-grid {
    grid-template-columns: 1fr;
  }
  
  .analysis-modal {
    width: 95%;
  }
  
  .toast-container {
    left: 16px;
    right: 16px;
    top: 16px;
  }
  
  .toast {
    min-width: auto;
  }
}

@media (max-width: 480px) {
  .converter-header h2 {
    font-size: 24px;
  }
  
  .currency-section {
    padding: 20px 16px;
  }
  
  .amount-input,
  .amount-display {
    font-size: 18px;
    padding: 14px;
  }
  
  .rate-equation {
    font-size: 18px;
  }
  
  .swap-btn {
    width: 48px;
    height: 48px;
    font-size: 18px;
  }
  
  .quick-amounts {
    flex-wrap: wrap;
  }
  
  .path-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
  }
}
</style>
