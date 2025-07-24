<template>
  <div class="currency-settings">
    <div class="header">
      <h1>
        <Globe class="icon" />
        Currency Settings
      </h1>
      <p>Configure the base currency for your application</p>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="loading-state">
      <div class="spinner"></div>
      <p>Loading currency settings...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="hasError" class="error-state">
      <AlertCircle class="icon" />
      <p>{{ error }}</p>
      <button @click="fetchCurrencySettings" class="btn btn-secondary">
        Retry
      </button>
    </div>

    <!-- Main Content -->
    <div v-else class="content">
      <!-- Alert Messages -->
      <div v-if="message.text" :class="['alert', `alert-${message.type}`]">
        <CheckCircle v-if="message.type === 'success'" class="icon" />
        <AlertCircle v-else class="icon" />
        {{ message.text }}
      </div>

      <div class="grid">
        <!-- Settings Panel -->
        <div class="settings-panel">
          <div class="card">
            <div class="card-header">
              <DollarSign class="icon" />
              Base Currency Selection
            </div>
            
            <div class="card-body">
              <div class="form-group">
                <label for="base-currency">Choose Base Currency</label>
                <select 
                  id="base-currency"
                  v-model="selectedCurrency"
                  :disabled="loading.update"
                  class="form-control"
                >
                  <option 
                    v-for="(currency, code) in availableCurrencies"
                    :key="code"
                    :value="code"
                  >
                    {{ code }} - {{ currency.name }} ({{ currency.symbol }})
                  </option>
                </select>
              </div>

              <!-- Currency Information -->
              <div v-if="selectedCurrencyInfo" class="currency-info">
                <h3>Currency Information</h3>
                <div class="info-grid">
                  <div class="info-item">
                    <span class="label">Symbol:</span>
                    <span class="value">{{ selectedCurrencyInfo.symbol }}</span>
                  </div>
                  <div class="info-item">
                    <span class="label">Decimal Places:</span>
                    <span class="value">{{ selectedCurrencyInfo.decimal_places }}</span>
                  </div>
                  <div class="info-item">
                    <span class="label">Countries:</span>
                    <span class="value">{{ selectedCurrencyInfo.countries?.join(', ') }}</span>
                  </div>
                  <div v-if="selectedCurrencyInfo.special_formatting" class="info-item special">
                    <span class="label">Special Notes:</span>
                    <span class="value">{{ selectedCurrencyInfo.special_formatting.note }}</span>
                  </div>
                </div>
              </div>

              <button 
                @click="saveCurrency"
                :disabled="loading.update || selectedCurrency === baseCurrency"
                class="btn btn-primary"
              >
                <Save class="icon" />
                {{ loading.update ? 'Saving...' : 'Save Changes' }}
              </button>
            </div>
          </div>

          <!-- Current Settings Display -->
          <div class="current-settings">
            <h3>Current Settings</h3>
            <div class="settings-display">
              <div class="setting-item">
                <span class="label">Base Currency:</span>
                <span class="value">{{ baseCurrency }}</span>
              </div>
              <div class="setting-item">
                <span class="label">Currency Name:</span>
                <span class="value">{{ currentCurrencyInfo?.name }}</span>
              </div>
              <div class="setting-item">
                <span class="label">Symbol:</span>
                <span class="value symbol">{{ currentCurrencyInfo?.symbol }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Preview Panel -->
        <div class="preview-panel">
          <div class="card">
            <div class="card-header">
              Formatting Preview
            </div>
            
            <div class="card-body">
              <!-- Standard Preview -->
              <div class="preview-section">
                <h3>Standard Formatting</h3>
                <div class="preview-list">
                  <div 
                    v-for="{ amount, formatted } in standardPreviews" 
                    :key="amount"
                    class="preview-item"
                  >
                    <span class="amount">{{ amount.toLocaleString() }}</span>
                    <span class="formatted">{{ formatted }}</span>
                  </div>
                </div>
              </div>

              <!-- Special INR Preview -->
              <div v-if="showINRPreview" class="preview-section">
                <h3>Indian Rupee (Lakh/Crore Format)</h3>
                <div class="preview-list">
                  <div 
                    v-for="{ amount, formatted, special } in inrPreviews" 
                    :key="amount"
                    class="preview-item"
                  >
                    <span class="amount">{{ amount.toLocaleString() }}</span>
                    <span class="formatted">{{ formatted }}</span>
                    <span v-if="special" class="special-format">{{ special }}</span>
                  </div>
                </div>
              </div>

              <!-- VND Special Notes -->
              <div v-if="showVNDNotes" class="special-notes">
                <h3>Vietnamese Dong Notes</h3>
                <ul>
                  <li>No decimal places used</li>
                  <li>Amounts typically in thousands/millions</li>
                  <li>Uses dot (.) as thousand separator in Vietnam</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { Globe, DollarSign, Save, AlertCircle, CheckCircle } from 'lucide-vue-next'
import { useCurrency } from '@/composables/useCurrency'

// Use composable
const {
  baseCurrency,
  availableCurrencies,
  isLoading,
  hasError,
  error,
  fetchCurrencySettings,
  updateBaseCurrency,
  formatCurrency,
  formatINR
} = useCurrency()

// Local state
const selectedCurrency = ref('')
const loading = reactive({ update: false })
const message = reactive({ type: '', text: '' })

// Computed properties
const selectedCurrencyInfo = computed(() => {
  return selectedCurrency.value ? availableCurrencies.value[selectedCurrency.value] : null
})

const currentCurrencyInfo = computed(() => {
  return baseCurrency.value ? availableCurrencies.value[baseCurrency.value] : null
})

const showINRPreview = computed(() => {
  return selectedCurrency.value === 'INR' || baseCurrency.value === 'INR'
})

const showVNDNotes = computed(() => {
  return selectedCurrency.value === 'VND' || baseCurrency.value === 'VND'
})

const standardPreviews = computed(() => {
  const amounts = [1000, 100000, 1000000, 50000000]
  return amounts.map(amount => ({
    amount,
    formatted: formatCurrency(amount, selectedCurrency.value || baseCurrency.value)
  }))
})

const inrPreviews = computed(() => {
  const amounts = [500000, 2500000, 15000000, 45000000]
  return amounts.map(amount => ({
    amount,
    formatted: formatCurrency(amount, 'INR'),
    special: formatINR(amount, true)
  }))
})

// Methods
const saveCurrency = async () => {
  loading.update = true
  
  try {
    await updateBaseCurrency(selectedCurrency.value)
    showMessage('success', 'Base currency updated successfully!')
  } catch (err) {
    showMessage('error', err.message || 'Failed to update currency')
  } finally {
    loading.update = false
  }
}

const showMessage = (type, text) => {
  message.type = type
  message.text = text
  
  // Clear message after 3 seconds
  setTimeout(() => {
    message.type = ''
    message.text = ''
  }, 3000)
}

// Watchers
watch(baseCurrency, (newValue) => {
  if (newValue && !selectedCurrency.value) {
    selectedCurrency.value = newValue
  }
})

// Lifecycle
onMounted(async () => {
  try {
    await fetchCurrencySettings()
    selectedCurrency.value = baseCurrency.value
  } catch (error) {
    console.error('Failed to load currency settings:', error)
  }
})
</script>

<style scoped>
.currency-settings {
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.header {
  margin-bottom: 32px;
}

.header h1 {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 28px;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 8px 0;
}

.header p {
  color: #6b7280;
  margin: 0;
}

.loading-state, .error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 48px;
  text-align: center;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e5e7eb;
  border-left-color: #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 16px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.alert {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 24px;
}

.alert-success {
  background-color: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #15803d;
}

.alert-error {
  background-color: #fef2f2;
  border: 1px solid #fecaca;
  color: #dc2626;
}

.grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 32px;
}

@media (max-width: 768px) {
  .grid {
    grid-template-columns: 1fr;
  }
}

.card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.card-header {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #f9fafb;
  padding: 16px;
  border-bottom: 1px solid #e5e7eb;
  font-weight: 600;
  color: #374151;
}

.card-body {
  padding: 24px;
}

.form-group {
  margin-bottom: 24px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #374151;
}

.form-control {
  width: 100%;
  padding: 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  transition: border-color 0.15s;
}

.form-control:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-control:disabled {
  background-color: #f3f4f6;
  cursor: not-allowed;
}

.currency-info {
  background: #f0f9ff;
  border: 1px solid #bae6fd;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 24px;
}

.currency-info h3 {
  margin: 0 0 12px 0;
  font-size: 16px;
  font-weight: 600;
  color: #0c4a6e;
}

.info-grid {
  display: grid;
  gap: 8px;
}

.info-item {
  display: flex;
  justify-content: space-between;
  font-size: 14px;
}

.info-item .label {
  font-weight: 500;
  color: #075985;
}

.info-item .value {
  color: #0c4a6e;
}

.btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 24px;
  border: none;
  border-radius: 6px;
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.15s;
  text-decoration: none;
}

.btn-primary {
  background: #3b82f6;
  color: white;
  width: 100%;
}

.btn-primary:hover:not(:disabled) {
  background: #2563eb;
}

.btn-primary:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
}

.current-settings {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 12px;
  padding: 20px;
  margin-top: 24px;
}

.current-settings h3 {
  margin: 0 0 16px 0;
  font-size: 16px;
  font-weight: 600;
  color: #15803d;
}

.settings-display {
  display: grid;
  gap: 8px;
}

.setting-item {
  display: flex;
  justify-content: space-between;
  font-size: 14px;
}

.preview-section {
  margin-bottom: 24px;
}

.preview-section h3 {
  margin: 0 0 12px 0;
  font-size: 16px;
  font-weight: 600;
  color: #374151;
}

.preview-list {
  display: grid;
  gap: 8px;
}

.preview-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
}

.preview-item .amount {
  color: #6b7280;
  font-size: 14px;
}

.preview-item .formatted {
  font-family: 'Courier New', monospace;
  font-weight: 600;
  font-size: 16px;
  color: #1f2937;
}

.preview-item .special-format {
  font-family: 'Courier New', monospace;
  font-size: 14px;
  color: #7c3aed;
  font-style: italic;
}

.special-notes {
  background: #fffbeb;
  border: 1px solid #fed7aa;
  border-radius: 8px;
  padding: 16px;
}

.special-notes h3 {
  margin: 0 0 12px 0;
  font-size: 16px;
  font-weight: 600;
  color: #92400e;
}

.special-notes ul {
  margin: 0;
  padding-left: 20px;
  color: #a16207;
}

.icon {
  width: 20px;
  height: 20px;
}
</style>