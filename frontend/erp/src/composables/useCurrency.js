import { ref, reactive, computed } from 'vue'
import axios from 'axios';

// Global state for currency settings
const currencyState = reactive({
  base_currency: 'USD',
  available_currencies: {},
  lastUpdated: null
})

const loading = ref(false)
const error = ref('')

export function useCurrency() {
  
  /**
   * Fetch currency settings from the API
   */
  const fetchCurrencySettings = async () => {
    loading.value = true
    error.value = ''
    
    try {
      const response = await axios.get('/admin/currency/settings')
      
      if (response.data.status === 'success') {
        Object.assign(currencyState, {
          ...response.data.data,
          lastUpdated: new Date()
        })
      } else {
        throw new Error(response.data.message || 'Failed to fetch currency settings')
      }
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Currency settings fetch error:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Update the base currency
   */
  const updateBaseCurrency = async (currency) => {
    loading.value = true
    
    try {
      const response = await axios.post('/admin/currency/base-currency', { currency })
      
      if (response.data.status === 'success') {
        currencyState.base_currency = currency
        currencyState.lastUpdated = new Date()
        return response.data
      } else {
        throw new Error(response.data.message || 'Failed to update currency')
      }
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Currency update error:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Format currency amount with proper localization
   */
  const formatCurrency = (amount, currency = null, options = {}) => {
    const currentCurrency = currency || currencyState.base_currency
    const currencyInfo = currencyState.available_currencies[currentCurrency]
    
    if (!currencyInfo) {
      return amount.toString()
    }
    
    const formatOptions = {
      style: 'currency',
      currency: currentCurrency,
      ...options
    }
    
    // Handle currencies without decimals
    if (['VND', 'IDR', 'JPY', 'KRW'].includes(currentCurrency)) {
      formatOptions.minimumFractionDigits = 0
      formatOptions.maximumFractionDigits = 0
    } else {
      formatOptions.minimumFractionDigits = currencyInfo.decimal_places || 2
      formatOptions.maximumFractionDigits = currencyInfo.decimal_places || 2
    }
    
    try {
      const locale = currencyInfo.locale || 'en-US'
      return new Intl.NumberFormat(locale, formatOptions).format(amount)
    } catch (e) {
      console.warn('Currency formatting failed, using fallback:', e)
      return `${currencyInfo.symbol}${amount.toLocaleString()}`
    }
  }

  /**
   * Format INR with Indian numbering system
   */
  const formatINR = (amount, useShortForm = false) => {
    if (amount >= 10000000) { // 1 crore
      const crores = amount / 10000000
      return useShortForm 
        ? `₹${crores.toFixed(2)} Cr`
        : `₹${crores.toFixed(2)} Crore${crores !== 1 ? 's' : ''}`
    } else if (amount >= 100000) { // 1 lakh
      const lakhs = amount / 100000
      return useShortForm 
        ? `₹${lakhs.toFixed(2)} L`
        : `₹${lakhs.toFixed(2)} Lakh${lakhs !== 1 ? 's' : ''}`
    }
    return formatCurrency(amount, 'INR')
  }

  /**
   * Get currency symbol
   */
  const getCurrencySymbol = (currency = null) => {
    const currentCurrency = currency || currencyState.base_currency
    return currencyState.available_currencies[currentCurrency]?.symbol || '$'
  }

  /**
   * Check if currency uses decimals
   */
  const currencyUsesDecimals = (currency = null) => {
    const currentCurrency = currency || currencyState.base_currency
    const currencyInfo = currencyState.available_currencies[currentCurrency]
    return currencyInfo ? currencyInfo.decimal_places > 0 : true
  }

  // Computed properties
  const baseCurrency = computed(() => currencyState.base_currency)
  const availableCurrencies = computed(() => currencyState.available_currencies)
  const isLoading = computed(() => loading.value)
  const hasError = computed(() => !!error.value)

  return {
    // State
    currencyState,
    baseCurrency,
    availableCurrencies,
    isLoading,
    hasError,
    error,
    
    // Methods
    fetchCurrencySettings,
    updateBaseCurrency,
    formatCurrency,
    formatINR,
    getCurrencySymbol,
    currencyUsesDecimals
  }
}

// Vue plugin for global access
export default {
  install(app) {
    const currency = useCurrency()
    
    // Global properties
    app.config.globalProperties.$formatCurrency = currency.formatCurrency
    app.config.globalProperties.$baseCurrency = currency.baseCurrency
    app.config.globalProperties.$getCurrencySymbol = currency.getCurrencySymbol
    
    // Provide for inject
    app.provide('currency', currency)
  }
}