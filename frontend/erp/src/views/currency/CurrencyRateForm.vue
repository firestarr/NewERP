<!-- Enhanced CurrencyRateForm.vue with Bidirectional Support -->
<template>
  <div class="currency-rate-form">
    <!-- Header Section -->
    <div class="form-header">
      <div class="header-content">
        <div class="breadcrumb">
          <router-link to="/currency/rates" class="breadcrumb-item">
            <i class="fas fa-chart-area"></i>
            Exchange Rates
          </router-link>
          <span class="breadcrumb-separator">/</span>
          <span class="breadcrumb-current">{{ isEditing ? 'Edit Rate' : 'Create Rate' }}</span>
        </div>
        
        <div class="form-title">
          <h1>
            <i :class="isEditing ? 'fas fa-edit' : 'fas fa-plus'"></i>
            {{ isEditing ? 'Edit Exchange Rate' : 'Create Exchange Rate' }}
          </h1>
          <p class="subtitle">
            {{ isEditing ? 'Modify existing exchange rate information' : 'Add a new exchange rate to the system' }}
          </p>
        </div>
      </div>
      
      <div class="header-actions">
        <router-link to="/currency/rates" class="btn btn-outline">
          <i class="fas fa-times"></i>
          Cancel
        </router-link>
        
        <button @click="saveDraft" class="btn btn-secondary" v-if="!isEditing">
          <i class="fas fa-save"></i>
          Save Draft
        </button>
        
        <button @click="saveRate" class="btn btn-primary" :disabled="!isFormValid || saving">
          <i v-if="saving" class="fas fa-spinner fa-spin"></i>
          <i v-else :class="isEditing ? 'fas fa-save' : 'fas fa-plus'"></i>
          {{ isEditing ? 'Update Rate' : 'Create Rate' }}
        </button>
      </div>
    </div>

    <!-- Form Content -->
    <div class="form-content">
      <form @submit.prevent="saveRate">
        <!-- Basic Information Card -->
        <div class="form-card">
          <div class="card-header">
            <h3>
              <i class="fas fa-info-circle"></i>
              Basic Information
            </h3>
            <div class="card-actions">
              <button type="button" @click="swapCurrencies" class="swap-btn" title="Swap currencies">
                <i class="fas fa-exchange-alt"></i>
              </button>
            </div>
          </div>
          
          <div class="card-content">
            <div class="form-grid">
              <!-- Currency Pair -->
              <div class="form-group">
                <label class="required">From Currency</label>
                <select 
                  v-model="formData.from_currency" 
                  @change="handleCurrencyChange"
                  class="form-control"
                  :class="{ 'error': errors.from_currency }"
                  required
                >
                  <option value="">Select Currency</option>
                  <option 
                    v-for="currency in currencies" 
                    :key="currency.code" 
                    :value="currency.code"
                    :disabled="currency.code === formData.to_currency"
                  >
                    {{ currency.code }} - {{ currency.name }}
                  </option>
                </select>
                <div v-if="errors.from_currency" class="error-message">
                  {{ errors.from_currency }}
                </div>
              </div>
              
              <div class="form-group">
                <label class="required">To Currency</label>
                <select 
                  v-model="formData.to_currency" 
                  @change="handleCurrencyChange"
                  class="form-control"
                  :class="{ 'error': errors.to_currency }"
                  required
                >
                  <option value="">Select Currency</option>
                  <option 
                    v-for="currency in currencies" 
                    :key="currency.code" 
                    :value="currency.code"
                    :disabled="currency.code === formData.from_currency"
                  >
                    {{ currency.code }} - {{ currency.name }}
                  </option>
                </select>
                <div v-if="errors.to_currency" class="error-message">
                  {{ errors.to_currency }}
                </div>
              </div>
              
              <!-- Exchange Rate -->
              <div class="form-group">
                <label class="required">Exchange Rate</label>
                <div class="rate-input-container">
                  <input
                    v-model.number="formData.rate"
                    type="number"
                    step="0.000001"
                    min="0"
                    placeholder="0.000000"
                    class="form-control rate-input"
                    :class="{ 'error': errors.rate }"
                    @input="handleRateChange"
                    required
                  />
                  <div class="rate-helper">
                    <span v-if="formData.rate > 0" class="rate-preview">
                      1 {{ formData.from_currency || 'XXX' }} = {{ formatRate(formData.rate) }} {{ formData.to_currency || 'XXX' }}
                    </span>
                  </div>
                </div>
                <div v-if="errors.rate" class="error-message">
                  {{ errors.rate }}
                </div>
              </div>
              
              <!-- Reverse Rate Display -->
              <div class="form-group" v-if="formData.rate > 0">
                <label>Reverse Rate (Auto-calculated)</label>
                <div class="reverse-rate-display">
                  <span class="reverse-value">{{ formatRate(1 / formData.rate) }}</span>
                  <span class="reverse-equation">
                    1 {{ formData.to_currency || 'XXX' }} = {{ formatRate(1 / formData.rate) }} {{ formData.from_currency || 'XXX' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Validity Period Card -->
        <div class="form-card">
          <div class="card-header">
            <h3>
              <i class="fas fa-calendar-alt"></i>
              Validity Period
            </h3>
          </div>
          
          <div class="card-content">
            <div class="form-grid">
              <div class="form-group">
                <label class="required">Effective Date</label>
                <input
                  v-model="formData.effective_date"
                  type="date"
                  class="form-control"
                  :class="{ 'error': errors.effective_date }"
                  :min="minEffectiveDate"
                  required
                />
                <div v-if="errors.effective_date" class="error-message">
                  {{ errors.effective_date }}
                </div>
              </div>
              
              <div class="form-group">
                <label>End Date (Optional)</label>
                <input
                  v-model="formData.end_date"
                  type="date"
                  class="form-control"
                  :class="{ 'error': errors.end_date }"
                  :min="formData.effective_date"
                />
                <div class="form-helper">
                  Leave empty for indefinite validity
                </div>
                <div v-if="errors.end_date" class="error-message">
                  {{ errors.end_date }}
                </div>
              </div>
              
              <div class="form-group">
                <label>Status</label>
                <div class="status-controls">
                  <label class="checkbox-label">
                    <input 
                      v-model="formData.is_active" 
                      type="checkbox"
                      class="checkbox-input"
                    />
                    <span class="checkbox-custom"></span>
                    <span class="checkbox-text">Active Rate</span>
                  </label>
                </div>
                <div class="form-helper">
                  Only active rates are used for currency conversion
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Advanced Settings Card -->
        <div class="form-card">
          <div class="card-header">
            <h3>
              <i class="fas fa-cog"></i>
              Advanced Settings
            </h3>
            <button type="button" @click="toggleAdvanced" class="toggle-btn">
              <i :class="showAdvanced ? 'fas fa-chevron-up' : 'fas fa-chevron-down'"></i>
              {{ showAdvanced ? 'Hide' : 'Show' }} Advanced Options
            </button>
          </div>
          
          <div v-if="showAdvanced" class="card-content">
            <div class="form-grid">
              <!-- Calculation Method -->
              <div class="form-group">
                <label>Calculation Method</label>
                <select v-model="formData.calculation_method" class="form-control">
                  <option value="direct">Direct Rate</option>
                  <option value="inverse">Inverse Calculation</option>
                  <option value="cross">Cross Currency</option>
                </select>
                <div class="form-helper">
                  Specify how this rate was calculated
                </div>
              </div>
              
              <!-- Confidence Level -->
              <div class="form-group">
                <label>Confidence Level</label>
                <select v-model="formData.confidence_level" class="form-control">
                  <option value="high">High</option>
                  <option value="medium">Medium</option>
                  <option value="low">Low</option>
                </select>
                <div class="form-helper">
                  Indicates the reliability of this rate
                </div>
              </div>
              
              <!-- Provider -->
              <div class="form-group">
                <label>Rate Provider</label>
                <select v-model="formData.provider_code" class="form-control">
                  <option value="">Manual Entry</option>
                  <option v-for="provider in providers" :key="provider.code" :value="provider.code">
                    {{ provider.name }}
                  </option>
                </select>
                <div class="form-helper">
                  Source of this exchange rate
                </div>
              </div>
              
              <!-- Bidirectional Settings -->
              <div class="form-group">
                <label>Bidirectional Options</label>
                <div class="checkbox-group">
                  <label class="checkbox-label">
                    <input 
                      v-model="formData.is_bidirectional" 
                      type="checkbox"
                      class="checkbox-input"
                    />
                    <span class="checkbox-custom"></span>
                    <span class="checkbox-text">Enable Bidirectional Use</span>
                  </label>
                  
                  <label class="checkbox-label">
                    <input 
                      v-model="formData.auto_create_reverse" 
                      type="checkbox"
                      class="checkbox-input"
                    />
                    <span class="checkbox-custom"></span>
                    <span class="checkbox-text">Auto-create Reverse Rate</span>
                  </label>
                </div>
                <div class="form-helper">
                  Bidirectional rates can be used for both directions
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Rate Validation & Preview Card -->
        <div v-if="showValidation" class="form-card validation-card">
          <div class="card-header">
            <h3>
              <i class="fas fa-check-circle"></i>
              Rate Validation & Preview
            </h3>
          </div>
          
          <div class="card-content">
            <!-- Existing Rate Check -->
            <div v-if="existingRateCheck.loading" class="validation-item loading">
              <i class="fas fa-spinner fa-spin"></i>
              <span>Checking for existing rates...</span>
            </div>
            
            <div v-else-if="existingRateCheck.hasConflict" class="validation-item warning">
              <i class="fas fa-exclamation-triangle"></i>
              <div class="validation-content">
                <strong>Existing Rate Found</strong>
                <p>A rate for {{ formData.from_currency }} → {{ formData.to_currency }} already exists for this period:</p>
                <div class="existing-rate-info">
                  <span class="rate-value">{{ formatRate(existingRateCheck.existingRate.rate) }}</span>
                  <span class="rate-period">
                    {{ formatDate(existingRateCheck.existingRate.effective_date) }} - 
                    {{ existingRateCheck.existingRate.end_date ? formatDate(existingRateCheck.existingRate.end_date) : 'Ongoing' }}
                  </span>
                </div>
                <div class="conflict-actions">
                  <button type="button" @click="viewExistingRate" class="btn btn-sm btn-outline">
                    View Existing Rate
                  </button>
                  <button type="button" @click="replaceExistingRate" class="btn btn-sm btn-warning">
                    Replace Existing
                  </button>
                </div>
              </div>
            </div>
            
            <div v-else class="validation-item success">
              <i class="fas fa-check-circle"></i>
              <span>No conflicts detected</span>
            </div>
            
            <!-- Rate Reasonableness Check -->
            <div class="validation-item" :class="rateValidation.class">
              <i :class="rateValidation.icon"></i>
              <div class="validation-content">
                <strong>{{ rateValidation.title }}</strong>
                <p>{{ rateValidation.message }}</p>
                <div v-if="rateValidation.suggestions.length > 0" class="validation-suggestions">
                  <strong>Suggestions:</strong>
                  <ul>
                    <li v-for="suggestion in rateValidation.suggestions" :key="suggestion">
                      {{ suggestion }}
                    </li>
                  </ul>
                </div>
              </div>
            </div>
            
            <!-- Conversion Examples -->
            <div class="conversion-examples">
              <h4>Conversion Examples</h4>
              <div class="examples-grid">
                <div v-for="example in conversionExamples" :key="example.amount" class="example-item">
                  <span class="example-amount">{{ formatAmount(example.amount, formData.from_currency) }}</span>
                  <i class="fas fa-arrow-right"></i>
                  <span class="example-result">{{ formatAmount(example.result, formData.to_currency) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Additional Information Card -->
        <div class="form-card">
          <div class="card-header">
            <h3>
              <i class="fas fa-sticky-note"></i>
              Additional Information
            </h3>
          </div>
          
          <div class="card-content">
            <div class="form-group">
              <label>Notes</label>
              <textarea
                v-model="formData.notes"
                placeholder="Add any notes or comments about this exchange rate..."
                class="form-control textarea"
                rows="4"
              ></textarea>
              <div class="form-helper">
                Optional notes for future reference
              </div>
            </div>
            
            <div class="form-group" v-if="isEditing">
              <label>Reason for Change</label>
              <textarea
                v-model="formData.change_reason"
                placeholder="Describe the reason for this rate change..."
                class="form-control textarea"
                rows="3"
              ></textarea>
              <div class="form-helper">
                This will be recorded in the audit trail
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>

    <!-- Form Actions -->
    <div class="form-actions">
      <div class="actions-left">
        <button type="button" @click="resetForm" class="btn btn-outline">
          <i class="fas fa-undo"></i>
          Reset Form
        </button>
        
        <button type="button" @click="previewRate" class="btn btn-secondary">
          <i class="fas fa-eye"></i>
          Preview
        </button>
      </div>
      
      <div class="actions-right">
        <router-link to="/currency/rates" class="btn btn-outline">
          <i class="fas fa-times"></i>
          Cancel
        </router-link>
        
        <button type="button" @click="saveAndCreateAnother" class="btn btn-secondary" v-if="!isEditing">
          <i class="fas fa-plus"></i>
          Save & Create Another
        </button>
        
        <button @click="saveRate" class="btn btn-primary" :disabled="!isFormValid || saving">
          <i v-if="saving" class="fas fa-spinner fa-spin"></i>
          <i v-else :class="isEditing ? 'fas fa-save' : 'fas fa-plus'"></i>
          {{ isEditing ? 'Update Rate' : 'Create Rate' }}
        </button>
      </div>
    </div>

    <!-- Preview Modal -->
    <div v-if="showPreview" class="modal-overlay" @click="closePreview">
      <div class="preview-modal" @click.stop>
        <div class="modal-header">
          <h3>Rate Preview</h3>
          <button @click="closePreview" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>
        
        <div class="modal-content">
          <div class="preview-content">
            <div class="preview-rate">
              <div class="rate-display">
                <span class="currency-pair">
                  {{ formData.from_currency }} <i class="fas fa-arrow-right"></i> {{ formData.to_currency }}
                </span>
                <span class="rate-value">{{ formatRate(formData.rate) }}</span>
              </div>
              
              <div class="rate-details">
                <div class="detail-item">
                  <label>Effective Date:</label>
                  <span>{{ formatDate(formData.effective_date) }}</span>
                </div>
                
                <div class="detail-item">
                  <label>End Date:</label>
                  <span>{{ formData.end_date ? formatDate(formData.end_date) : 'No expiration' }}</span>
                </div>
                
                <div class="detail-item">
                  <label>Status:</label>
                  <span :class="{ 'status-active': formData.is_active, 'status-inactive': !formData.is_active }">
                    {{ formData.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </div>
                
                <div class="detail-item">
                  <label>Calculation Method:</label>
                  <span>{{ (formData.calculation_method || 'direct').toUpperCase() }}</span>
                </div>
                
                <div class="detail-item">
                  <label>Confidence Level:</label>
                  <span>{{ (formData.confidence_level || 'high').toUpperCase() }}</span>
                </div>
              </div>
            </div>
            
            <div class="preview-conversions">
              <h4>Sample Conversions</h4>
              <div class="conversion-list">
                <div v-for="amount in [100, 1000, 10000]" :key="amount" class="conversion-item">
                  <span>{{ formatAmount(amount, formData.from_currency) }}</span>
                  <i class="fas fa-arrow-right"></i>
                  <span>{{ formatAmount(amount * formData.rate, formData.to_currency) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="modal-footer">
          <button @click="closePreview" class="btn btn-secondary">Close</button>
          <button @click="saveRateFromPreview" class="btn btn-primary">
            <i class="fas fa-save"></i>
            {{ isEditing ? 'Update Rate' : 'Create Rate' }}
          </button>
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
import { CurrencyService, CurrencyUtils } from '@/services/CurrencyService';

export default {
  name: 'CurrencyRateForm',
  props: {
    id: {
      type: [String, Number],
      default: null
    }
  },
  
  data() {
    return {
      // Form state
      saving: false,
      showAdvanced: false,
      showPreview: false,
      
      // Form data
      formData: {
        from_currency: '',
        to_currency: '',
        rate: null,
        effective_date: new Date().toISOString().split('T')[0],
        end_date: '',
        is_active: true,
        calculation_method: 'direct',
        confidence_level: 'high',
        provider_code: '',
        is_bidirectional: false,
        auto_create_reverse: false,
        notes: '',
        change_reason: ''
      },
      
      // Original data for comparison
      originalData: {},
      
      // Validation
      errors: {},
      
      // Options
      currencies: [],
      providers: [],
      
      // Validation checks
      existingRateCheck: {
        loading: false,
        hasConflict: false,
        existingRate: null
      },
      
      // Toast notifications
      toasts: [],
      toastId: 0,
      
      // Validation debounce timer
      validationTimer: null
    };
  },
  
  computed: {
    isEditing() {
      return !!this.id;
    },
    
    isFormValid() {
      return this.formData.from_currency &&
             this.formData.to_currency &&
             this.formData.rate > 0 &&
             this.formData.effective_date &&
             Object.keys(this.errors).length === 0;
    },
    
    showValidation() {
      return this.formData.from_currency && 
             this.formData.to_currency && 
             this.formData.rate > 0;
    },
    
    minEffectiveDate() {
      return this.isEditing ? null : new Date().toISOString().split('T')[0];
    },
    
    rateValidation() {
      if (!this.formData.rate || this.formData.rate <= 0) {
        return {
          class: 'info',
          icon: 'fas fa-info-circle',
          title: 'Enter Rate',
          message: 'Please enter an exchange rate to validate',
          suggestions: []
        };
      }
      
      // Rate reasonableness checks
      const rate = this.formData.rate;
      let validation = {
        class: 'success',
        icon: 'fas fa-check-circle',
        title: 'Rate Looks Good',
        message: 'The exchange rate appears to be within reasonable bounds',
        suggestions: []
      };
      
      // Check for extremely high or low rates
      if (rate > 1000000) {
        validation = {
          class: 'warning',
          icon: 'fas fa-exclamation-triangle',
          title: 'Very High Rate',
          message: 'This exchange rate seems unusually high. Please verify.',
          suggestions: [
            'Double-check the decimal placement',
            'Verify against multiple sources',
            'Consider if you need to swap the currencies'
          ]
        };
      } else if (rate < 0.000001) {
        validation = {
          class: 'warning',
          icon: 'fas fa-exclamation-triangle',
          title: 'Very Low Rate',
          message: 'This exchange rate seems unusually low. Please verify.',
          suggestions: [
            'Check if more decimal places are needed',
            'Verify the currency pair is correct',
            'Consider using scientific notation if needed'
          ]
        };
      } else if (rate > 1000) {
        validation = {
          class: 'info',
          icon: 'fas fa-info-circle',
          title: 'High Rate Detected',
          message: 'This is a relatively high exchange rate. Common for currencies with large denominations.',
          suggestions: ['Common for currencies like IDR, VND, etc.']
        };
      }
      
      return validation;
    },
    
    conversionExamples() {
      if (!this.formData.rate || this.formData.rate <= 0) return [];
      
      const amounts = [100, 1000, 10000];
      return amounts.map(amount => ({
        amount,
        result: amount * this.formData.rate
      }));
    }
  },
  
  watch: {
    'formData.from_currency'() {
      this.debouncedValidation();
    },
    
    'formData.to_currency'() {
      this.debouncedValidation();
    },
    
    'formData.rate'() {
      this.debouncedValidation();
    },
    
    'formData.effective_date'() {
      this.debouncedValidation();
    },
    
    'formData.end_date'() {
      this.debouncedValidation();
    }
  },
  
  async mounted() {
    await this.initializeForm();
    
    // Load form data if editing
    if (this.isEditing) {
      await this.loadRateData();
    } else {
      // Pre-fill from query parameters
      this.prefillFromQuery();
    }
  },
  
  beforeUnmount() {
    if (this.validationTimer) {
      clearTimeout(this.validationTimer);
    }
  },
  
  methods: {
    async initializeForm() {
      try {
        // Load currencies and providers
        await Promise.all([
          this.loadCurrencies(),
          this.loadProviders()
        ]);
      } catch (error) {
        console.error('Error initializing form:', error);
        this.showToast('Failed to load form data', 'error');
      }
    },
    
    async loadCurrencies() {
      try {
        const response = await CurrencyService.getAllCurrencies();
        if (response.data.status === 'success') {
          this.currencies = response.data.data;
        }
      } catch (error) {
        console.error('Error loading currencies:', error);
      }
    },
    
    async loadProviders() {
      try {
        // Mock providers data - replace with actual API call
        this.providers = [
          { code: 'central_bank', name: 'Central Bank' },
          { code: 'xe', name: 'XE.com' },
          { code: 'fixer', name: 'Fixer.io' },
          { code: 'ecb', name: 'European Central Bank' }
        ];
      } catch (error) {
        console.error('Error loading providers:', error);
      }
    },
    
    async loadRateData() {
      try {
        const response = await CurrencyService.getCurrencyRates({ rate_id: this.id });
        
        if (response.data.status === 'success' && response.data.data.length > 0) {
          const rateData = response.data.data[0];
          
          // Populate form data
          this.formData = {
            from_currency: rateData.from_currency,
            to_currency: rateData.to_currency,
            rate: rateData.rate,
            effective_date: rateData.effective_date,
            end_date: rateData.end_date || '',
            is_active: rateData.is_active,
            calculation_method: rateData.calculation_method || 'direct',
            confidence_level: rateData.confidence_level || 'high',
            provider_code: rateData.provider_code || '',
            is_bidirectional: rateData.is_bidirectional || false,
            auto_create_reverse: false,
            notes: rateData.notes || '',
            change_reason: ''
          };
          
          // Store original data for comparison
          this.originalData = { ...this.formData };
        } else {
          this.showToast('Exchange rate not found', 'error');
          this.$router.push('/currency/rates');
        }
      } catch (error) {
        console.error('Error loading rate data:', error);
        this.showToast('Failed to load rate data', 'error');
      }
    },
    
    prefillFromQuery() {
      const query = this.$route.query;
      
      if (query.from_currency) {
        this.formData.from_currency = query.from_currency;
      }
      
      if (query.to_currency) {
        this.formData.to_currency = query.to_currency;
      }
      
      if (query.suggested_rate) {
        this.formData.rate = parseFloat(query.suggested_rate);
      }
      
      if (query.duplicate_from) {
        // Load data from the rate being duplicated
        this.loadDuplicateData(query.duplicate_from);
      }
    },
    
    async loadDuplicateData(sourceId) {
      try {
        const response = await CurrencyService.getCurrencyRates({ rate_id: sourceId });
        
        if (response.data.status === 'success' && response.data.data.length > 0) {
          const sourceData = response.data.data[0];
          
          // Copy relevant data but reset dates and status
          this.formData = {
            ...this.formData,
            from_currency: sourceData.from_currency,
            to_currency: sourceData.to_currency,
            calculation_method: sourceData.calculation_method,
            confidence_level: sourceData.confidence_level,
            provider_code: sourceData.provider_code,
            is_bidirectional: sourceData.is_bidirectional,
            notes: `Duplicated from rate ${sourceId}\n${sourceData.notes || ''}`
          };
        }
      } catch (error) {
        console.error('Error loading duplicate data:', error);
      }
    },
    
    // Form Methods
    handleCurrencyChange() {
      this.clearErrors();
      this.debouncedValidation();
    },
    
    handleRateChange() {
      this.clearErrors();
      this.debouncedValidation();
    },
    
    swapCurrencies() {
      const temp = this.formData.from_currency;
      this.formData.from_currency = this.formData.to_currency;
      this.formData.to_currency = temp;
      
      // Swap rate if it exists
      if (this.formData.rate > 0) {
        this.formData.rate = 1 / this.formData.rate;
      }
      
      this.debouncedValidation();
    },
    
    toggleAdvanced() {
      this.showAdvanced = !this.showAdvanced;
    },
    
    debouncedValidation() {
      if (this.validationTimer) {
        clearTimeout(this.validationTimer);
      }
      
      this.validationTimer = setTimeout(() => {
        this.validateForm();
        this.checkExistingRates();
      }, 500);
    },
    
    validateForm() {
      this.errors = {};
      
      // Currency validation
      if (!this.formData.from_currency) {
        this.errors.from_currency = 'From currency is required';
      }
      
      if (!this.formData.to_currency) {
        this.errors.to_currency = 'To currency is required';
      }
      
      if (this.formData.from_currency === this.formData.to_currency) {
        this.errors.from_currency = 'From and To currencies must be different';
        this.errors.to_currency = 'From and To currencies must be different';
      }
      
      // Rate validation
      if (!this.formData.rate || this.formData.rate <= 0) {
        this.errors.rate = 'Exchange rate must be greater than 0';
      }
      
      // Date validation
      if (!this.formData.effective_date) {
        this.errors.effective_date = 'Effective date is required';
      }
      
      if (this.formData.end_date && this.formData.effective_date) {
        if (new Date(this.formData.end_date) <= new Date(this.formData.effective_date)) {
          this.errors.end_date = 'End date must be after effective date';
        }
      }
    },
    
    async checkExistingRates() {
      if (!this.formData.from_currency || !this.formData.to_currency || !this.formData.effective_date) {
        return;
      }
      
      this.existingRateCheck.loading = true;
      this.existingRateCheck.hasConflict = false;
      
      try {
        const response = await CurrencyService.getCurrencyRates({
          from_currency: this.formData.from_currency,
          to_currency: this.formData.to_currency,
          effective_date: this.formData.effective_date,
          exclude_id: this.id
        });
        
        if (response.data.data && response.data.data.length > 0) {
          this.existingRateCheck.hasConflict = true;
          this.existingRateCheck.existingRate = response.data.data[0];
        }
      } catch (error) {
        console.error('Error checking existing rates:', error);
      } finally {
        this.existingRateCheck.loading = false;
      }
    },
    
    clearErrors() {
      this.errors = {};
    },
    
    resetForm() {
      if (this.isEditing) {
        this.formData = { ...this.originalData };
      } else {
        this.formData = {
          from_currency: '',
          to_currency: '',
          rate: null,
          effective_date: new Date().toISOString().split('T')[0],
          end_date: '',
          is_active: true,
          calculation_method: 'direct',
          confidence_level: 'high',
          provider_code: '',
          is_bidirectional: false,
          auto_create_reverse: false,
          notes: '',
          change_reason: ''
        };
      }
      
      this.clearErrors();
    },
    
    // Action Methods
    async saveRate() {
      this.validateForm();
      
      if (!this.isFormValid) {
        this.showToast('Please fix validation errors', 'error');
        return;
      }
      
      this.saving = true;
      
      try {
        let response;
        
        if (this.isEditing) {
          response = await CurrencyService.updateCurrencyRate(this.id, this.formData);
        } else {
          response = await CurrencyService.createCurrencyRate(this.formData);
        }
        
        if (response.data.status === 'success') {
          this.showToast(
            this.isEditing ? 'Rate updated successfully' : 'Rate created successfully',
            'success'
          );
          
          // Redirect to rates list or detail page
          setTimeout(() => {
            if (this.isEditing) {
              this.$router.push(`/currency/rates/${this.id}`);
            } else {
              this.$router.push('/currency/rates');
            }
          }, 1500);
        } else {
          this.showToast(response.data.message || 'Failed to save rate', 'error');
        }
      } catch (error) {
        console.error('Error saving rate:', error);
        this.showToast(CurrencyService.errors.getUserMessage(error), 'error');
      } finally {
        this.saving = false;
      }
    },
    
    async saveAndCreateAnother() {
      await this.saveRate();
      
      if (!this.saving) {
        // Reset form for new rate
        this.resetForm();
        this.showToast('Ready to create another rate', 'info');
      }
    },
    
    async saveDraft() {
      try {
        // Save as draft (inactive rate)
        const draftData = {
          ...this.formData,
          is_active: false,
          notes: `DRAFT: ${this.formData.notes || ''}`
        };
        
        const response = await CurrencyService.createCurrencyRate(draftData);
        
        if (response.data.status === 'success') {
          this.showToast('Draft saved successfully', 'success');
        } else {
          this.showToast('Failed to save draft', 'error');
        }
      } catch (error) {
        console.error('Error saving draft:', error);
        this.showToast('Failed to save draft', 'error');
      }
    },
    
    previewRate() {
      this.validateForm();
      
      if (this.isFormValid) {
        this.showPreview = true;
      } else {
        this.showToast('Please fix validation errors before previewing', 'error');
      }
    },
    
    closePreview() {
      this.showPreview = false;
    },
    
    async saveRateFromPreview() {
      this.closePreview();
      await this.saveRate();
    },
    
    viewExistingRate() {
      if (this.existingRateCheck.existingRate) {
        const url = this.$router.resolve(`/currency/rates/${this.existingRateCheck.existingRate.rate_id}`);
        window.open(url.href, '_blank');
      }
    },
    
    async replaceExistingRate() {
      if (confirm('Are you sure you want to replace the existing rate? This action cannot be undone.')) {
        // Set end date for existing rate to day before new effective date
        const newEndDate = new Date(this.formData.effective_date);
        newEndDate.setDate(newEndDate.getDate() - 1);
        
        try {
          await CurrencyService.updateCurrencyRate(this.existingRateCheck.existingRate.rate_id, {
            end_date: newEndDate.toISOString().split('T')[0]
          });
          
          this.existingRateCheck.hasConflict = false;
          this.showToast('Existing rate will be replaced', 'info');
        } catch (error) {
          console.error('Error updating existing rate:', error);
          this.showToast('Failed to update existing rate', 'error');
        }
      }
    },
    
    // Utility Methods
    formatRate(rate) {
      return CurrencyUtils.formatCurrency(rate, 'USD', {
        style: 'decimal',
        minimumFractionDigits: 2,
        maximumFractionDigits: 6
      });
    },
    
    formatAmount(amount, currency) {
      return CurrencyUtils.formatCurrency(amount, currency);
    },
    
    formatDate(dateString) {
      if (!dateString) return '';
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    },
    
    // Toast Methods
    showToast(message, type = 'info') {
      const toast = {
        id: ++this.toastId,
        message,
        type
      };
      
      this.toasts.push(toast);
      
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
/* Base Layout */
.currency-rate-form {
  max-width: 1000px;
  margin: 0 auto;
  padding: 24px;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Header */
.form-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 32px;
  padding-bottom: 24px;
  border-bottom: 2px solid #ecf0f1;
}

.breadcrumb {
  display: flex;
  align-items: center;
  margin-bottom: 16px;
  font-size: 14px;
}

.breadcrumb-item {
  color: #3498db;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 6px;
}

.breadcrumb-item:hover {
  color: #2980b9;
}

.breadcrumb-separator {
  margin: 0 8px;
  color: #bdc3c7;
}

.breadcrumb-current {
  color: #7f8c8d;
}

.form-title h1 {
  color: #2c3e50;
  font-size: 28px;
  font-weight: 600;
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.subtitle {
  color: #7f8c8d;
  font-size: 16px;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 8px;
}

.btn {
  padding: 10px 16px;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 6px;
  text-decoration: none;
}

.btn-primary {
  background: #3498db;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #2980b9;
}

.btn-secondary {
  background: #95a5a6;
  color: white;
}

.btn-secondary:hover:not(:disabled) {
  background: #7f8c8d;
}

.btn-outline {
  background: transparent;
  color: #7f8c8d;
  border: 1px solid #bdc3c7;
}

.btn-outline:hover:not(:disabled) {
  background: #ecf0f1;
  color: #2c3e50;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Form Content */
.form-content {
  display: flex;
  flex-direction: column;
  gap: 24px;
  margin-bottom: 32px;
}

.form-card {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  overflow: hidden;
}

.card-header {
  background: #f8f9fa;
  padding: 16px 20px;
  border-bottom: 1px solid #ecf0f1;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h3 {
  margin: 0;
  color: #2c3e50;
  font-size: 16px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}

.card-actions {
  display: flex;
  gap: 8px;
}

.swap-btn {
  width: 32px;
  height: 32px;
  background: #3498db;
  color: white;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.swap-btn:hover {
  background: #2980b9;
  transform: rotate(180deg);
}

.toggle-btn {
  background: none;
  border: none;
  color: #3498db;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 6px;
}

.toggle-btn:hover {
  color: #2980b9;
}

.card-content {
  padding: 20px;
}

/* Form Grid */
.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-weight: 500;
  color: #2c3e50;
  font-size: 14px;
}

.form-group label.required::after {
  content: ' *';
  color: #e74c3c;
}

.form-control {
  padding: 12px 16px;
  border: 2px solid #ecf0f1;
  border-radius: 6px;
  font-size: 14px;
  transition: all 0.3s ease;
  background: white;
}

.form-control:focus {
  outline: none;
  border-color: #3498db;
  box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.form-control.error {
  border-color: #e74c3c;
  box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
}

.textarea {
  resize: vertical;
  min-height: 80px;
  font-family: inherit;
}

/* Rate Input */
.rate-input-container {
  position: relative;
}

.rate-input {
  font-weight: 600;
  font-size: 16px;
}

.rate-helper {
  margin-top: 8px;
}

.rate-preview {
  font-size: 13px;
  color: #2ecc71;
  font-weight: 500;
}

/* Reverse Rate */
.reverse-rate-display {
  background: #f8f9fa;
  border: 2px solid #ecf0f1;
  border-radius: 6px;
  padding: 12px 16px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.reverse-value {
  font-weight: 700;
  font-size: 16px;
  color: #2ecc71;
}

.reverse-equation {
  font-size: 13px;
  color: #7f8c8d;
}

/* Checkbox Controls */
.status-controls,
.checkbox-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-size: 14px;
}

.checkbox-input {
  display: none;
}

.checkbox-custom {
  width: 18px;
  height: 18px;
  border: 2px solid #bdc3c7;
  border-radius: 3px;
  position: relative;
  transition: all 0.3s ease;
}

.checkbox-input:checked + .checkbox-custom {
  background: #3498db;
  border-color: #3498db;
}

.checkbox-input:checked + .checkbox-custom::after {
  content: '✓';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  color: white;
  font-size: 12px;
  font-weight: bold;
}

.checkbox-text {
  color: #2c3e50;
  font-weight: 500;
}

/* Form Helper */
.form-helper {
  font-size: 12px;
  color: #7f8c8d;
  margin-top: 4px;
}

/* Error Messages */
.error-message {
  color: #e74c3c;
  font-size: 12px;
  margin-top: 4px;
  display: flex;
  align-items: center;
  gap: 4px;
}

/* Validation Card */
.validation-card {
  border-left: 4px solid #3498db;
}

.validation-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  margin-bottom: 12px;
  border-radius: 6px;
}

.validation-item.success {
  background: #d5f4e6;
  color: #27ae60;
  border-left: 4px solid #27ae60;
}

.validation-item.warning {
  background: #fef9e7;
  color: #f39c12;
  border-left: 4px solid #f39c12;
}

.validation-item.info {
  background: #e3f2fd;
  color: #1976d2;
  border-left: 4px solid #1976d2;
}

.validation-item.loading {
  background: #f8f9fa;
  color: #7f8c8d;
}

.validation-content {
  flex: 1;
}

.validation-content strong {
  display: block;
  margin-bottom: 4px;
}

.validation-suggestions {
  margin-top: 8px;
}

.validation-suggestions ul {
  margin: 4px 0 0 16px;
  padding: 0;
}

.existing-rate-info {
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 8px 0;
  padding: 8px;
  background: rgba(255, 255, 255, 0.5);
  border-radius: 4px;
}

.rate-value {
  font-weight: 700;
  color: #2ecc71;
}

.rate-period {
  font-size: 12px;
  color: #7f8c8d;
}

.conflict-actions {
  display: flex;
  gap: 8px;
  margin-top: 8px;
}

.btn-sm {
  padding: 4px 8px;
  font-size: 12px;
}

.btn-warning {
  background: #f39c12;
  color: white;
}

.btn-warning:hover {
  background: #d68910;
}

/* Conversion Examples */
.conversion-examples {
  margin-top: 16px;
}

.conversion-examples h4 {
  color: #2c3e50;
  margin-bottom: 12px;
  font-size: 14px;
}

.examples-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 8px;
}

.example-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  background: #f8f9fa;
  border-radius: 4px;
  font-size: 13px;
}

.example-amount {
  font-weight: 600;
  color: #2c3e50;
}

.example-result {
  font-weight: 600;
  color: #2ecc71;
}

/* Form Actions */
.form-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 0;
  border-top: 1px solid #ecf0f1;
}

.actions-left,
.actions-right {
  display: flex;
  gap: 8px;
}

/* Preview Modal */
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

.preview-modal {
  background: white;
  border-radius: 8px;
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
}

.close-btn {
  background: none;
  border: none;
  font-size: 16px;
  color: #7f8c8d;
  cursor: pointer;
  padding: 8px;
  border-radius: 4px;
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

.preview-content {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.preview-rate {
  text-align: center;
}

.rate-display {
  margin-bottom: 20px;
}

.currency-pair {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  font-size: 20px;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 8px;
}

.rate-value {
  font-size: 28px;
  font-weight: 700;
  color: #2ecc71;
}

.rate-details {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 12px;
  text-align: left;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  padding: 8px 12px;
  background: #f8f9fa;
  border-radius: 4px;
}

.detail-item label {
  font-weight: 500;
  color: #7f8c8d;
}

.detail-item span {
  color: #2c3e50;
  font-weight: 500;
}

.status-active {
  color: #27ae60;
}

.status-inactive {
  color: #e74c3c;
}

.preview-conversions h4 {
  color: #2c3e50;
  margin-bottom: 12px;
}

.conversion-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.conversion-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 12px;
  background: #f8f9fa;
  border-radius: 4px;
  font-weight: 500;
}

.modal-footer {
  padding: 16px 24px;
  border-top: 1px solid #ecf0f1;
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  background: #f8f9fa;
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
  border-radius: 6px;
  color: white;
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 300px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  animation: slideIn 0.3s ease;
}

.toast.success { background: #27ae60; }
.toast.error { background: #e74c3c; }
.toast.warning { background: #f39c12; }
.toast.info { background: #3498db; }

.toast-close {
  background: none;
  border: none;
  color: white;
  cursor: pointer;
  padding: 4px;
  margin-left: auto;
  border-radius: 4px;
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
  .currency-rate-form {
    padding: 16px;
  }
  
  .form-header {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
  }
  
  .header-actions {
    justify-content: stretch;
  }
  
  .form-title h1 {
    font-size: 24px;
  }
  
  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .form-actions {
    flex-direction: column;
    gap: 16px;
  }
  
  .actions-left,
  .actions-right {
    justify-content: stretch;
  }
  
  .examples-grid {
    grid-template-columns: 1fr;
  }
  
  .rate-details {
    grid-template-columns: 1fr;
  }
  
  .toast {
    min-width: auto;
    margin: 0 16px;
  }
}

@media (max-width: 480px) {
  .form-title h1 {
    font-size: 20px;
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
  
  .btn {
    padding: 8px 12px;
    font-size: 12px;
  }
  
  .card-content {
    padding: 16px;
  }
  
  .currency-pair {
    flex-direction: column;
    gap: 4px;
  }
  
  .rate-value {
    font-size: 24px;
  }
  
  .conversion-item {
    flex-direction: column;
    text-align: center;
    gap: 4px;
  }
}
</style>
