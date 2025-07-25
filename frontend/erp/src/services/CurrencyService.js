// Enhanced CurrencyService.js with Bidirectional Support
import axios from "axios";

export const CurrencyService = {
  /**
   * Get all available currencies
   * @returns {Promise} Currency list
   */
  async getAllCurrencies() {
    try {
      return await axios.get('/accounting/currencies');
    } catch (error) {
      console.error('Failed to fetch currencies:', error);
      throw error;
    }
  },

  /**
   * Get bidirectional exchange rate (main method)
   * @param {string} fromCurrency - Source currency code
   * @param {string} toCurrency - Target currency code  
   * @param {string|null} date - Optional date (YYYY-MM-DD)
   * @returns {Promise} Rate information with direction and confidence
   */
  async getBidirectionalRate(fromCurrency, toCurrency, date = null) {
    try {
      const params = {
        from_currency: fromCurrency.toUpperCase(),
        to_currency: toCurrency.toUpperCase()
      };
      
      if (date) {
        params.date = date;
      }
      
      return await axios.get('/accounting/currency-rates/current-rate', { params });
    } catch (error) {
      console.error('Failed to fetch bidirectional rate:', error);
      throw error;
    }
  },

  /**
   * Convert amount using bidirectional rates
   * @param {number} amount - Amount to convert
   * @param {string} fromCurrency - Source currency code
   * @param {string} toCurrency - Target currency code
   * @param {string|null} date - Optional date for historical conversion
   * @returns {Promise} Conversion result with detailed information
   */
  async convertAmount(amount, fromCurrency, toCurrency, date = null) {
    try {
      const data = {
        amount: parseFloat(amount),
        from_currency: fromCurrency.toUpperCase(),
        to_currency: toCurrency.toUpperCase()
      };
      
      if (date) {
        data.date = date;
      }
      
      return await axios.post('/accounting/currency-rates/convert', data);
    } catch (error) {
      console.error('Failed to convert amount:', error);
      throw error;
    }
  },

  /**
   * Get multiple rates for a base currency
   * @param {string} baseCurrency - Base currency code
   * @param {Array<string>} targetCurrencies - Array of target currency codes
   * @param {string|null} date - Optional date
   * @returns {Promise} Multiple rate results
   */
  async getMultipleRates(baseCurrency, targetCurrencies, date = null) {
    try {
      const params = {
        base_currency: baseCurrency.toUpperCase(),
        target_currencies: targetCurrencies.map(c => c.toUpperCase())
      };
      
      if (date) {
        params.date = date;
      }
      
      return await axios.get('/accounting/currency-rates/multiple', { params });
    } catch (error) {
      console.error('Failed to fetch multiple rates:', error);
      throw error;
    }
  },

  /**
   * Analyze available rate paths for debugging
   * @param {string} fromCurrency - Source currency code
   * @param {string} toCurrency - Target currency code
   * @param {string|null} date - Optional date
   * @returns {Promise} Analysis of all available rate paths
   */
  async analyzeRatePaths(fromCurrency, toCurrency, date = null) {
    try {
      const params = {
        from_currency: fromCurrency.toUpperCase(),
        to_currency: toCurrency.toUpperCase()
      };
      
      if (date) {
        params.date = date;
      }
      
      return await axios.get('/accounting/currency-rates/analyze', { params });
    } catch (error) {
      console.error('Failed to analyze rate paths:', error);
      throw error;
    }
  },

  /**
   * Get currency rates list with filtering
   * @param {Object} filters - Filter parameters
   * @returns {Promise} Paginated rates list
   */
  async getCurrencyRates(filters = {}) {
    try {
      const params = { ...filters };
      
      // Clean up undefined values
      Object.keys(params).forEach(key => {
        if (params[key] === undefined || params[key] === null || params[key] === '') {
          delete params[key];
        }
      });
      
      return await axios.get('/accounting/currency-rates', { params });
    } catch (error) {
      console.error('Failed to fetch currency rates:', error);
      throw error;
    }
  },

  /**
   * Bulk convert multiple amounts
   * @param {Array} conversions - Array of conversion objects
   * @param {string|null} date - Optional date
   * @returns {Promise} Bulk conversion results
   */
  async bulkConvert(conversions, date = null) {
    try {
      const data = {
        conversions: conversions.map(conv => ({
          amount: parseFloat(conv.amount),
          from_currency: conv.fromCurrency.toUpperCase(),
          to_currency: conv.toCurrency.toUpperCase()
        }))
      };
      
      if (date) {
        data.date = date;
      }
      
      return await axios.post('/accounting/currency-rates/bulk-convert', data);
    } catch (error) {
      console.error('Failed to perform bulk conversion:', error);
      throw error;
    }
  },

  /**
   * Get current exchange rates for dashboard
   * @param {string} baseCurrency - Base currency code
   * @returns {Promise} Dashboard-ready rates
   */
  async getDashboardRates(baseCurrency = 'USD') {
    try {
      const popularCurrencies = ['EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'CNY', 'SGD', 'IDR'];
      
      return await this.getMultipleRates(baseCurrency, popularCurrencies);
    } catch (error) {
      console.error('Failed to fetch dashboard rates:', error);
      throw error;
    }
  },

  /**
   * Get historical rates for charting
   * @param {string} fromCurrency - Source currency code
   * @param {string} toCurrency - Target currency code
   * @param {string} startDate - Start date (YYYY-MM-DD)
   * @param {string} endDate - End date (YYYY-MM-DD)
   * @returns {Promise} Historical rate data
   */
  async getHistoricalRates(fromCurrency, toCurrency, startDate, endDate) {
    try {
      const params = {
        from_currency: fromCurrency.toUpperCase(),
        to_currency: toCurrency.toUpperCase(),
        start_date: startDate,
        end_date: endDate
      };
      
      return await axios.get('/accounting/currency-rates/historical', { params });
    } catch (error) {
      console.error('Failed to fetch historical rates:', error);
      throw error;
    }
  },

  /**
   * Cache management methods
   */
  cache: {
    /**
     * Get cache statistics
     * @returns {Promise} Cache statistics
     */
    async getStats() {
      try {
        return await axios.get('/accounting/currency-rates/admin/cache/stats');
      } catch (error) {
        console.error('Failed to fetch cache stats:', error);
        throw error;
      }
    },

    /**
     * Clear expired cache entries
     * @returns {Promise} Clear operation result
     */
    async clearExpired() {
      try {
        return await axios.post('/accounting/currency-rates/admin/cache/clear');
      } catch (error) {
        console.error('Failed to clear cache:', error);
        throw error;
      }
    }
  },

  /**
   * System health check
   * @returns {Promise} Health check results
   */
  async healthCheck() {
    try {
      return await axios.get('/accounting/currency-rates/admin/health');
    } catch (error) {
      console.error('Health check failed:', error);
      throw error;
    }
  },

  /**
   * Legacy compatibility methods
   */
  legacy: {
    /**
     * Get exchange rates (legacy format)
     * @param {string} baseCurrency - Base currency
     * @returns {Promise} Legacy format rates
     */
    async getExchangeRates(baseCurrency = 'USD') {
      try {
        const params = { base_currency: baseCurrency };
        return await axios.get('/accounting/exchange-rates', { params });
      } catch (error) {
        console.error('Failed to fetch legacy exchange rates:', error);
        throw error;
      }
    },

    /**
     * Get historical rates (legacy format)
     * @param {string} date - Date for historical rates
     * @param {string} baseCurrency - Base currency
     * @returns {Promise} Legacy format historical rates
     */
    async getHistoricalRates(date, baseCurrency = 'USD') {
      try {
        const params = { 
          date: date,
          base_currency: baseCurrency 
        };
        return await axios.get('/accounting/exchange-rates/historical', { params });
      } catch (error) {
        console.error('Failed to fetch legacy historical rates:', error);
        throw error;
      }
    }
  },

  /**
   * Real-time updates via WebSocket (if enabled)
   */
  realtime: {
    /**
     * Subscribe to currency rate updates
     * @param {Array} currencyPairs - Array of currency pair objects
     * @param {Function} callback - Callback for rate updates
     * @returns {Promise} Subscription result
     */
    async subscribe(currencyPairs, callback) {
      try {
        const data = {
          currency_pairs: currencyPairs.map(pair => ({
            from: pair.from.toUpperCase(),
            to: pair.to.toUpperCase()
          }))
        };
        
        const response = await axios.post('/accounting/currency-rates/subscribe', data);
        
        // Set up WebSocket connection if supported
        if (window.Echo && response.data.channel) {
          window.Echo.channel(response.data.channel)
            .listen('CurrencyRateUpdated', callback);
        }
        
        return response;
      } catch (error) {
        console.error('Failed to subscribe to rate updates:', error);
        throw error;
      }
    }
  },

  /**
   * Utility methods
   */
  utils: {
    /**
     * Format currency amount
     * @param {number} amount - Amount to format
     * @param {string} currency - Currency code
     * @param {Object} options - Formatting options
     * @returns {string} Formatted amount
     */
    formatCurrency(amount, currency, options = {}) {
      const defaults = {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 6
      };
      
      const formatOptions = { ...defaults, ...options };
      
      try {
        return new Intl.NumberFormat('en-US', formatOptions).format(amount);
      } catch (error) {
        // Fallback formatting
        return `${amount.toFixed(2)} ${currency}`;
      }
    },

    /**
     * Calculate percentage change
     * @param {number} oldValue - Previous value
     * @param {number} newValue - Current value
     * @returns {number} Percentage change
     */
    calculatePercentageChange(oldValue, newValue) {
      if (oldValue === 0) return 0;
      return ((newValue - oldValue) / oldValue) * 100;
    },

    /**
     * Validate currency code
     * @param {string} code - Currency code to validate
     * @returns {boolean} Is valid currency code
     */
    isValidCurrencyCode(code) {
      return /^[A-Z]{3}$/.test(code);
    },

    /**
     * Get currency symbol
     * @param {string} currency - Currency code
     * @returns {string} Currency symbol
     */
    getCurrencySymbol(currency) {
      const symbols = {
        'USD': '$',
        'EUR': '€',
        'GBP': '£',
        'JPY': '¥',
        'CNY': '¥',
        'INR': '₹',
        'KRW': '₩',
        'SGD': 'S$',
        'AUD': 'A$',
        'CAD': 'C$',
        'CHF': 'Fr',
        'IDR': 'Rp',
        'MYR': 'RM',
        'THB': '฿',
        'VND': '₫'
      };
      
      return symbols[currency] || currency;
    },

    /**
     * Debounce function for API calls
     * @param {Function} func - Function to debounce
     * @param {number} wait - Wait time in milliseconds
     * @returns {Function} Debounced function
     */
    debounce(func, wait) {
      let timeout;
      return function executedFunction(...args) {
        const later = () => {
          clearTimeout(timeout);
          func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
      };
    },

    /**
     * Create rate comparison object
     * @param {Object} rate1 - First rate object
     * @param {Object} rate2 - Second rate object
     * @returns {Object} Comparison result
     */
    compareRates(rate1, rate2) {
      if (!rate1 || !rate2) return null;
      
      const change = rate2.rate - rate1.rate;
      const percentageChange = this.calculatePercentageChange(rate1.rate, rate2.rate);
      
      return {
        change,
        percentageChange,
        direction: change > 0 ? 'up' : change < 0 ? 'down' : 'stable',
        isSignificant: Math.abs(percentageChange) >= 1 // 1% threshold
      };
    },

    /**
     * Generate currency pair string
     * @param {string} from - From currency
     * @param {string} to - To currency
     * @returns {string} Currency pair string
     */
    createPairString(from, to) {
      return `${from.toUpperCase()}/${to.toUpperCase()}`;
    },

    /**
     * Parse currency pair string
     * @param {string} pairString - Currency pair string
     * @returns {Object} Parsed currency pair
     */
    parsePairString(pairString) {
      const [from, to] = pairString.split('/');
      return { from, to };
    }
  },

  /**
   * Error handling utilities
   */
  errors: {
    /**
     * Get user-friendly error message
     * @param {Error} error - Error object
     * @returns {string} User-friendly message
     */
    getUserMessage(error) {
      if (error.response?.status === 404) {
        return 'Exchange rate not found for the specified currency pair';
      }
      
      if (error.response?.status === 422) {
        return 'Invalid request parameters. Please check your input.';
      }
      
      if (error.response?.status === 429) {
        return 'Too many requests. Please wait a moment and try again.';
      }
      
      if (error.response?.status >= 500) {
        return 'Server error occurred. Please try again later.';
      }
      
      if (error.code === 'NETWORK_ERROR') {
        return 'Network connection error. Please check your internet connection.';
      }
      
      return error.message || 'An unexpected error occurred';
    },

    /**
     * Check if error is retryable
     * @param {Error} error - Error object
     * @returns {boolean} Is retryable
     */
    isRetryable(error) {
      const retryableStatuses = [429, 500, 502, 503, 504];
      return retryableStatuses.includes(error.response?.status) || 
             error.code === 'NETWORK_ERROR';
    }
  }
};

// Export individual methods for convenience
export const {
  getAllCurrencies,
  getBidirectionalRate,
  convertAmount,
  getMultipleRates,
  analyzeRatePaths,
  getCurrencyRates,
  bulkConvert,
  getDashboardRates,
  getHistoricalRates
} = CurrencyService;

// Export utilities
export const CurrencyUtils = CurrencyService.utils;
export const CurrencyErrors = CurrencyService.errors;

export default CurrencyService;