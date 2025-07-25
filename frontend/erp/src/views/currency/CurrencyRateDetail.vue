<!-- Enhanced CurrencyRateDetail.vue with Bidirectional Support -->
<template>
  <div class="currency-rate-detail">
    <!-- Header Section -->
    <div class="detail-header">
      <div class="header-content">
        <div class="breadcrumb">
          <router-link to="/currency/rates" class="breadcrumb-item">
            <i class="fas fa-chart-area"></i>
            Exchange Rates
          </router-link>
          <span class="breadcrumb-separator">/</span>
          <span class="breadcrumb-current">Rate Details</span>
        </div>
        
        <div class="rate-title">
          <h1>
            <span class="currency-pair">
              {{ rateData.from_currency }} <i class="fas fa-arrow-right"></i> {{ rateData.to_currency }}
            </span>
            <span class="rate-value">{{ formatRate(rateData.rate) }}</span>
          </h1>
          <div class="rate-meta">
            <span class="rate-date">
              <i class="fas fa-calendar-alt"></i>
              Effective from {{ formatDate(rateData.effective_date) }}
            </span>
            <span class="rate-status" :class="{ 'active': rateData.is_active, 'inactive': !rateData.is_active }">
              <i :class="rateData.is_active ? 'fas fa-check-circle' : 'fas fa-times-circle'"></i>
              {{ rateData.is_active ? 'Active' : 'Inactive' }}
            </span>
          </div>
        </div>
      </div>
      
      <div class="header-actions">
        <button @click="editRate" class="btn btn-primary">
          <i class="fas fa-edit"></i>
          Edit Rate
        </button>
        
        <button @click="duplicateRate" class="btn btn-secondary">
          <i class="fas fa-copy"></i>
          Duplicate
        </button>
        
        <button @click="createReverseRate" class="btn btn-secondary" v-if="!rateData.has_reverse_rate">
          <i class="fas fa-exchange-alt"></i>
          Create Reverse
        </button>
        
        <div class="dropdown">
          <button @click="toggleDropdown" class="btn btn-outline dropdown-toggle">
            <i class="fas fa-ellipsis-v"></i>
            More
          </button>
          
          <div v-if="showDropdown" class="dropdown-menu">
            <button @click="exportRate" class="dropdown-item">
              <i class="fas fa-download"></i>
              Export Rate
            </button>
            <button @click="viewHistory" class="dropdown-item">
              <i class="fas fa-history"></i>
              View History
            </button>
            <button @click="analyzeRate" class="dropdown-item">
              <i class="fas fa-chart-line"></i>
              Analyze Rate
            </button>
            <hr class="dropdown-divider">
            <button @click="toggleStatus" class="dropdown-item" :class="{ 'text-warning': rateData.is_active, 'text-success': !rateData.is_active }">
              <i :class="rateData.is_active ? 'fas fa-pause' : 'fas fa-play'"></i>
              {{ rateData.is_active ? 'Deactivate' : 'Activate' }}
            </button>
            <button @click="deleteRate" class="dropdown-item text-danger">
              <i class="fas fa-trash"></i>
              Delete Rate
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="loading-spinner">
        <i class="fas fa-spinner fa-spin"></i>
      </div>
      <p>Loading rate details...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-container">
      <div class="error-icon">
        <i class="fas fa-exclamation-triangle"></i>
      </div>
      <h3>Failed to Load Rate Details</h3>
      <p>{{ error }}</p>
      <button @click="loadRateData" class="btn btn-primary">
        <i class="fas fa-retry"></i>
        Try Again
      </button>
    </div>

    <!-- Main Content -->
    <div v-else class="detail-content">
      <!-- Rate Information Cards -->
      <div class="info-cards">
        <!-- Primary Rate Info -->
        <div class="info-card primary">
          <div class="card-header">
            <h3>
              <i class="fas fa-exchange-alt"></i>
              Exchange Rate Information
            </h3>
          </div>
          
          <div class="card-content">
            <div class="rate-display">
              <div class="conversion-equation">
                <span class="amount">1</span>
                <span class="currency from">{{ rateData.from_currency }}</span>
                <span class="equals">=</span>
                <span class="rate-value">{{ formatRate(rateData.rate) }}</span>
                <span class="currency to">{{ rateData.to_currency }}</span>
              </div>
              
              <div class="reverse-equation">
                <span class="amount">1</span>
                <span class="currency from">{{ rateData.to_currency }}</span>
                <span class="equals">=</span>
                <span class="rate-value">{{ formatRate(1 / rateData.rate) }}</span>
                <span class="currency to">{{ rateData.from_currency }}</span>
              </div>
            </div>
            
            <div class="rate-badges">
              <span class="badge calculation-method" :class="`method-${rateData.calculation_method || 'direct'}`">
                <i :class="getMethodIcon(rateData.calculation_method)"></i>
                {{ (rateData.calculation_method || 'direct').toUpperCase() }}
              </span>
              
              <span class="badge confidence-level" :class="`confidence-${rateData.confidence_level || 'high'}`">
                <i class="fas fa-signal"></i>
                {{ (rateData.confidence_level || 'high').toUpperCase() }} CONFIDENCE
              </span>
              
              <span v-if="rateData.is_bidirectional" class="badge bidirectional">
                <i class="fas fa-arrows-alt-h"></i>
                BIDIRECTIONAL
              </span>
              
              <span v-if="rateData.has_reverse_rate" class="badge reverse-available">
                <i class="fas fa-undo-alt"></i>
                REVERSE AVAILABLE
              </span>
            </div>
          </div>
        </div>

        <!-- Validity Period -->
        <div class="info-card">
          <div class="card-header">
            <h3>
              <i class="fas fa-calendar-check"></i>
              Validity Period
            </h3>
          </div>
          
          <div class="card-content">
            <div class="date-range">
              <div class="date-item">
                <label>Effective Date</label>
                <div class="date-value">
                  <i class="fas fa-play-circle"></i>
                  {{ formatDateLong(rateData.effective_date) }}
                </div>
              </div>
              
              <div class="date-item">
                <label>End Date</label>
                <div class="date-value">
                  <i class="fas fa-stop-circle"></i>
                  {{ rateData.end_date ? formatDateLong(rateData.end_date) : 'No expiration' }}
                </div>
              </div>
              
              <div class="date-item">
                <label>Duration</label>
                <div class="date-value">
                  <i class="fas fa-clock"></i>
                  {{ calculateDuration() }}
                </div>
              </div>
            </div>
            
            <div class="validity-status">
              <div class="status-indicator" :class="validityStatusClass">
                <i :class="validityStatusIcon"></i>
                <span>{{ validityStatusText }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Rate Statistics -->
        <div class="info-card">
          <div class="card-header">
            <h3>
              <i class="fas fa-chart-bar"></i>
              Rate Statistics
            </h3>
          </div>
          
          <div class="card-content">
            <div class="stats-grid">
              <div class="stat-item">
                <div class="stat-label">Created</div>
                <div class="stat-value">{{ formatDateLong(rateData.created_at) }}</div>
              </div>
              
              <div class="stat-item">
                <div class="stat-label">Last Updated</div>
                <div class="stat-value">{{ formatDateLong(rateData.updated_at) }}</div>
              </div>
              
              <div class="stat-item">
                <div class="stat-label">Usage Count</div>
                <div class="stat-value">{{ rateData.usage_count || 0 }} times</div>
              </div>
              
              <div class="stat-item">
                <div class="stat-label">Source</div>
                <div class="stat-value">{{ rateData.provider_code || 'Manual' }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Calculation Path (if applicable) -->
      <div v-if="rateData.metadata?.calculation_path" class="calculation-path-card">
        <div class="card-header">
          <h3>
            <i class="fas fa-route"></i>
            Calculation Path
          </h3>
        </div>
        
        <div class="card-content">
          <div class="path-visualization">
            <div class="path-description">
              <p>This rate is calculated using the following path:</p>
              <div class="path-steps">
                <span class="path-step">{{ rateData.metadata.calculation_path }}</span>
              </div>
            </div>
            
            <div v-if="rateData.source_rate_id" class="source-rate-info">
              <h4>Source Rate Information</h4>
              <button @click="viewSourceRate" class="btn btn-outline btn-sm">
                <i class="fas fa-external-link-alt"></i>
                View Source Rate
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Related Rates -->
      <div class="related-rates-section">
        <div class="section-header">
          <h3>
            <i class="fas fa-link"></i>
            Related Rates
          </h3>
        </div>
        
        <div class="related-rates-grid">
          <!-- Reverse Rate -->
          <div v-if="reverseRate" class="related-rate-card">
            <div class="rate-header">
              <span class="rate-type">Reverse Rate</span>
              <span class="rate-pair">{{ rateData.to_currency }} → {{ rateData.from_currency }}</span>
            </div>
            <div class="rate-content">
              <div class="rate-value">{{ formatRate(reverseRate.rate) }}</div>
              <button @click="viewRate(reverseRate.rate_id)" class="btn btn-sm btn-outline">View Details</button>
            </div>
          </div>
          
          <!-- Cross Rates -->
          <div v-for="crossRate in crossRates" :key="crossRate.rate_id" class="related-rate-card">
            <div class="rate-header">
              <span class="rate-type">Cross Rate</span>
              <span class="rate-pair">{{ crossRate.from_currency }} → {{ crossRate.to_currency }}</span>
            </div>
            <div class="rate-content">
              <div class="rate-value">{{ formatRate(crossRate.rate) }}</div>
              <button @click="viewRate(crossRate.rate_id)" class="btn btn-sm btn-outline">View Details</button>
            </div>
          </div>
          
          <!-- No Related Rates -->
          <div v-if="!reverseRate && crossRates.length === 0" class="no-related-rates">
            <i class="fas fa-info-circle"></i>
            <p>No related rates found</p>
            <button @click="createReverseRate" class="btn btn-primary btn-sm">
              <i class="fas fa-plus"></i>
              Create Reverse Rate
            </button>
          </div>
        </div>
      </div>

      <!-- Quick Calculator -->
      <div class="quick-calculator">
        <div class="section-header">
          <h3>
            <i class="fas fa-calculator"></i>
            Quick Calculator
          </h3>
        </div>
        
        <div class="calculator-content">
          <div class="calculator-row">
            <div class="amount-input-group">
              <label>Amount ({{ rateData.from_currency }})</label>
              <input
                v-model.number="calculatorAmount"
                type="number"
                step="0.01"
                min="0"
                placeholder="Enter amount"
                class="amount-input"
                @input="calculateConversion"
              />
            </div>
            
            <div class="conversion-arrow">
              <i class="fas fa-arrow-right"></i>
            </div>
            
            <div class="result-display-group">
              <label>Converted Amount ({{ rateData.to_currency }})</label>
              <div class="result-display">
                {{ formatAmount(convertedAmount, rateData.to_currency) }}
              </div>
            </div>
          </div>
          
          <div class="quick-amounts">
            <span class="label">Quick amounts:</span>
            <button 
              v-for="amount in quickAmounts" 
              :key="amount"
              @click="setQuickAmount(amount)"
              class="quick-amount-btn"
            >
              {{ formatNumber(amount) }}
            </button>
          </div>
        </div>
      </div>

      <!-- Historical Data -->
      <div v-if="historicalData.length > 0" class="historical-section">
        <div class="section-header">
          <h3>
            <i class="fas fa-chart-line"></i>
            Rate History (Last 30 Days)
          </h3>
          <button @click="viewFullHistory" class="btn btn-outline btn-sm">
            <i class="fas fa-external-link-alt"></i>
            View Full History
          </button>
        </div>
        
        <div class="historical-chart">
          <canvas ref="historyChart" width="800" height="300"></canvas>
        </div>
        
        <div class="historical-stats">
          <div class="stat-item">
            <label>Highest</label>
            <span class="value positive">{{ formatRate(historicalStats.highest) }}</span>
          </div>
          <div class="stat-item">
            <label>Lowest</label>
            <span class="value negative">{{ formatRate(historicalStats.lowest) }}</span>
          </div>
          <div class="stat-item">
            <label>Average</label>
            <span class="value">{{ formatRate(historicalStats.average) }}</span>
          </div>
          <div class="stat-item">
            <label>Volatility</label>
            <span class="value">{{ formatPercent(historicalStats.volatility / 100) }}</span>
          </div>
        </div>
      </div>

      <!-- Audit Trail -->
      <div class="audit-trail">
        <div class="section-header">
          <h3>
            <i class="fas fa-list-alt"></i>
            Audit Trail
          </h3>
        </div>
        
        <div class="audit-list">
          <div v-for="entry in auditTrail" :key="entry.id" class="audit-entry">
            <div class="audit-icon">
              <i :class="getAuditIcon(entry.action)"></i>
            </div>
            <div class="audit-content">
              <div class="audit-action">{{ entry.action }}</div>
              <div class="audit-details">{{ entry.description }}</div>
              <div class="audit-meta">
                <span class="audit-user">{{ entry.user_name }}</span>
                <span class="audit-date">{{ formatDateTime(entry.created_at) }}</span>
              </div>
            </div>
          </div>
          
          <div v-if="auditTrail.length === 0" class="no-audit-entries">
            <i class="fas fa-info-circle"></i>
            <p>No audit entries available</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Confirmation Modals -->
    <div v-if="showDeleteModal" class="modal-overlay" @click="closeDeleteModal">
      <div class="modal-dialog" @click.stop>
        <div class="modal-header">
          <h3>Confirm Deletion</h3>
          <button @click="closeDeleteModal" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-content">
          <div class="warning-message">
            <i class="fas fa-exclamation-triangle"></i>
            <p>Are you sure you want to delete this exchange rate?</p>
            <p><strong>{{ rateData.from_currency }} → {{ rateData.to_currency }}</strong></p>
            <p>This action cannot be undone.</p>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeDeleteModal" class="btn btn-secondary">Cancel</button>
          <button @click="confirmDelete" class="btn btn-danger">
            <i class="fas fa-trash"></i>
            Delete Rate
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
import Chart from 'chart.js/auto';

export default {
  name: 'CurrencyRateDetail',
  props: {
    id: {
      type: [String, Number],
      required: true
    }
  },
  
  data() {
    return {
      // Data
      rateData: {},
      reverseRate: null,
      crossRates: [],
      historicalData: [],
      historicalStats: {},
      auditTrail: [],
      
      // UI State
      loading: false,
      error: null,
      showDropdown: false,
      showDeleteModal: false,
      
      // Calculator
      calculatorAmount: 1000,
      convertedAmount: 0,
      quickAmounts: [100, 500, 1000, 5000, 10000, 50000],
      
      // Chart
      historyChart: null,
      
      // Toast notifications
      toasts: [],
      toastId: 0
    };
  },
  
  computed: {
    validityStatusClass() {
      const now = new Date();
      const effective = new Date(this.rateData.effective_date);
      const end = this.rateData.end_date ? new Date(this.rateData.end_date) : null;
      
      if (!this.rateData.is_active) return 'status-inactive';
      if (effective > now) return 'status-future';
      if (end && end < now) return 'status-expired';
      return 'status-active';
    },
    
    validityStatusIcon() {
      switch (this.validityStatusClass) {
        case 'status-active': return 'fas fa-check-circle';
        case 'status-inactive': return 'fas fa-times-circle';
        case 'status-future': return 'fas fa-clock';
        case 'status-expired': return 'fas fa-exclamation-triangle';
        default: return 'fas fa-question-circle';
      }
    },
    
    validityStatusText() {
      switch (this.validityStatusClass) {
        case 'status-active': return 'Currently Active';
        case 'status-inactive': return 'Inactive';
        case 'status-future': return 'Future Rate';
        case 'status-expired': return 'Expired';
        default: return 'Unknown Status';
      }
    }
  },
  
  mounted() {
    this.loadRateData();
  },
  
  beforeUnmount() {
    if (this.historyChart) {
      this.historyChart.destroy();
    }
  },
  
  methods: {
    async loadRateData() {
      this.loading = true;
      this.error = null;
      
      try {
        // Load main rate data
        const response = await CurrencyService.getCurrencyRates({ 
          rate_id: this.id,
          include_related: true 
        });
        
        if (response.data.status === 'success' && response.data.data.length > 0) {
          this.rateData = response.data.data[0];
          this.calculateConversion();
          
          // Load related data
          await Promise.all([
            this.loadRelatedRates(),
            this.loadHistoricalData(),
            this.loadAuditTrail()
          ]);
        } else {
          this.error = 'Exchange rate not found';
        }
      } catch (error) {
        console.error('Error loading rate data:', error);
        this.error = CurrencyService.errors.getUserMessage(error);
      } finally {
        this.loading = false;
      }
    },
    
    async loadRelatedRates() {
      try {
        // Load reverse rate
        const reverseResponse = await CurrencyService.getCurrencyRates({
          from_currency: this.rateData.to_currency,
          to_currency: this.rateData.from_currency,
          is_active: true
        });
        
        if (reverseResponse.data.data.length > 0) {
          this.reverseRate = reverseResponse.data.data[0];
        }
        
        // Load cross rates (rates involving either currency)
        const crossResponse = await CurrencyService.getCurrencyRates({
          currency_involved: [this.rateData.from_currency, this.rateData.to_currency],
          exclude_pair: `${this.rateData.from_currency}_${this.rateData.to_currency}`,
          limit: 10
        });
        
        if (crossResponse.data.data) {
          this.crossRates = crossResponse.data.data;
        }
      } catch (error) {
        console.error('Error loading related rates:', error);
      }
    },
    
    async loadHistoricalData() {
      try {
        const endDate = new Date();
        const startDate = new Date(endDate.getTime() - (30 * 24 * 60 * 60 * 1000));
        
        const response = await CurrencyService.getHistoricalRates(
          this.rateData.from_currency,
          this.rateData.to_currency,
          startDate.toISOString().split('T')[0],
          endDate.toISOString().split('T')[0]
        );
        
        if (response.data.status === 'success') {
          this.historicalData = response.data.data;
          this.calculateHistoricalStats();
          this.renderHistoryChart();
        }
      } catch (error) {
        console.error('Error loading historical data:', error);
      }
    },
    
    async loadAuditTrail() {
      try {
        // This would be a custom endpoint for audit trail
        // const response = await CurrencyService.getRateAuditTrail(this.id);
        
        // Mock data for now
        this.auditTrail = [
          {
            id: 1,
            action: 'Created',
            description: 'Exchange rate created',
            user_name: 'Admin User',
            created_at: this.rateData.created_at
          },
          {
            id: 2,
            action: 'Updated',
            description: 'Exchange rate updated',
            user_name: 'Admin User',
            created_at: this.rateData.updated_at
          }
        ];
      } catch (error) {
        console.error('Error loading audit trail:', error);
      }
    },
    
    calculateHistoricalStats() {
      if (this.historicalData.length === 0) return;
      
      const rates = this.historicalData.map(d => d.rate);
      
      this.historicalStats = {
        highest: Math.max(...rates),
        lowest: Math.min(...rates),
        average: rates.reduce((sum, rate) => sum + rate, 0) / rates.length,
        volatility: this.calculateVolatility(rates)
      };
    },
    
    calculateVolatility(rates) {
      if (rates.length < 2) return 0;
      
      const returns = [];
      for (let i = 1; i < rates.length; i++) {
        returns.push((rates[i] - rates[i-1]) / rates[i-1]);
      }
      
      const avgReturn = returns.reduce((sum, ret) => sum + ret, 0) / returns.length;
      const variance = returns.reduce((sum, ret) => sum + Math.pow(ret - avgReturn, 2), 0) / returns.length;
      
      return Math.sqrt(variance) * Math.sqrt(252) * 100;
    },
    
    renderHistoryChart() {
      if (!this.$refs.historyChart || this.historicalData.length === 0) return;
      
      if (this.historyChart) {
        this.historyChart.destroy();
      }
      
      const ctx = this.$refs.historyChart.getContext('2d');
      
      this.historyChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: this.historicalData.map(d => this.formatDate(d.date)),
          datasets: [{
            label: `${this.rateData.from_currency}/${this.rateData.to_currency}`,
            data: this.historicalData.map(d => d.rate),
            borderColor: '#3498db',
            backgroundColor: 'rgba(52, 152, 219, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.1,
            pointBackgroundColor: '#3498db',
            pointBorderColor: '#2980b9',
            pointRadius: 3
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            }
          },
          scales: {
            y: {
              beginAtZero: false,
              grid: {
                color: 'rgba(0,0,0,0.1)'
              }
            },
            x: {
              grid: {
                color: 'rgba(0,0,0,0.1)'
              }
            }
          }
        }
      });
    },
    
    calculateConversion() {
      this.convertedAmount = this.calculatorAmount * this.rateData.rate;
    },
    
    setQuickAmount(amount) {
      this.calculatorAmount = amount;
      this.calculateConversion();
    },
    
    calculateDuration() {
      const start = new Date(this.rateData.effective_date);
      const end = this.rateData.end_date ? new Date(this.rateData.end_date) : new Date();
      
      const diffTime = Math.abs(end - start);
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
      
      if (diffDays < 30) {
        return `${diffDays} days`;
      } else if (diffDays < 365) {
        const months = Math.floor(diffDays / 30);
        return `${months} months`;
      } else {
        const years = Math.floor(diffDays / 365);
        const remainingMonths = Math.floor((diffDays % 365) / 30);
        return `${years} years ${remainingMonths} months`;
      }
    },
    
    // Action Methods
    editRate() {
      this.$router.push(`/currency/rates/${this.id}/edit`);
    },
    
    duplicateRate() {
      this.$router.push({
        name: 'CreateCurrencyRate',
        query: {
          duplicate_from: this.id
        }
      });
    },
    
    createReverseRate() {
      this.$router.push({
        name: 'CreateCurrencyRate',
        query: {
          from_currency: this.rateData.to_currency,
          to_currency: this.rateData.from_currency,
          suggested_rate: (1 / this.rateData.rate).toFixed(6)
        }
      });
    },
    
    async toggleStatus() {
      try {
        // API call to toggle status
        const newStatus = !this.rateData.is_active;
        
        // Update local state
        this.rateData.is_active = newStatus;
        
        this.showToast(
          `Rate ${newStatus ? 'activated' : 'deactivated'} successfully`,
          'success'
        );
        
        this.showDropdown = false;
      } catch (error) {
        console.error('Error toggling status:', error);
        this.showToast('Failed to update rate status', 'error');
      }
    },
    
    deleteRate() {
      this.showDeleteModal = true;
      this.showDropdown = false;
    },
    
    async confirmDelete() {
      try {
        // API call to delete rate
        // await CurrencyService.deleteRate(this.id);
        
        this.showToast('Rate deleted successfully', 'success');
        this.closeDeleteModal();
        
        // Redirect to rates list
        setTimeout(() => {
          this.$router.push('/currency/rates');
        }, 1500);
      } catch (error) {
        console.error('Error deleting rate:', error);
        this.showToast('Failed to delete rate', 'error');
      }
    },
    
    closeDeleteModal() {
      this.showDeleteModal = false;
    },
    
    exportRate() {
      const data = {
        rate_data: this.rateData,
        historical_data: this.historicalData,
        audit_trail: this.auditTrail,
        exported_at: new Date().toISOString()
      };
      
      const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `rate_${this.rateData.from_currency}_${this.rateData.to_currency}_${this.rateData.effective_date}.json`;
      a.click();
      URL.revokeObjectURL(url);
      
      this.showToast('Rate data exported successfully', 'success');
      this.showDropdown = false;
    },
    
    viewHistory() {
      this.$router.push(`/currency/rates/${this.id}/history`);
    },
    
    analyzeRate() {
      this.$router.push({
        name: 'CurrencyRateAnalysis',
        params: { 
          from: this.rateData.from_currency, 
          to: this.rateData.to_currency 
        }
      });
    },
    
    viewSourceRate() {
      if (this.rateData.source_rate_id) {
        this.$router.push(`/currency/rates/${this.rateData.source_rate_id}`);
      }
    },
    
    viewRate(rateId) {
      this.$router.push(`/currency/rates/${rateId}`);
    },
    
    viewFullHistory() {
      this.$router.push(`/currency/rates/${this.id}/history`);
    },
    
    toggleDropdown() {
      this.showDropdown = !this.showDropdown;
    },
    
    // Utility Methods
    getMethodIcon(method) {
      const icons = {
        direct: 'fas fa-arrow-right',
        inverse: 'fas fa-undo-alt',
        cross: 'fas fa-route'
      };
      return icons[method] || 'fas fa-arrow-right';
    },
    
    getAuditIcon(action) {
      const icons = {
        'Created': 'fas fa-plus-circle',
        'Updated': 'fas fa-edit',
        'Activated': 'fas fa-play-circle',
        'Deactivated': 'fas fa-pause-circle',
        'Deleted': 'fas fa-trash'
      };
      return icons[action] || 'fas fa-info-circle';
    },
    
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
    
    formatNumber(value) {
      return new Intl.NumberFormat('en-US').format(value);
    },
    
    formatPercent(value) {
      return new Intl.NumberFormat('en-US', {
        style: 'percent',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(value);
    },
    
    formatDate(dateString) {
      if (!dateString) return '';
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    },
    
    formatDateLong(dateString) {
      if (!dateString) return '';
      return new Date(dateString).toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    },
    
    formatDateTime(dateString) {
      if (!dateString) return '';
      return new Date(dateString).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
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
.currency-rate-detail {
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Header */
.detail-header {
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

.rate-title h1 {
  color: #2c3e50;
  font-size: 28px;
  font-weight: 600;
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  gap: 16px;
}

.currency-pair {
  display: flex;
  align-items: center;
  gap: 12px;
}

.rate-value {
  color: #2ecc71;
  font-weight: 700;
}

.rate-meta {
  display: flex;
  align-items: center;
  gap: 20px;
  font-size: 14px;
}

.rate-date {
  color: #7f8c8d;
  display: flex;
  align-items: center;
  gap: 6px;
}

.rate-status {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 4px 8px;
  border-radius: 12px;
  font-weight: 500;
}

.rate-status.active {
  background: #d5f4e6;
  color: #27ae60;
}

.rate-status.inactive {
  background: #ffeaea;
  color: #e74c3c;
}

.header-actions {
  display: flex;
  gap: 8px;
  position: relative;
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
}

.btn-primary {
  background: #3498db;
  color: white;
}

.btn-primary:hover {
  background: #2980b9;
}

.btn-secondary {
  background: #95a5a6;
  color: white;
}

.btn-secondary:hover {
  background: #7f8c8d;
}

.btn-outline {
  background: transparent;
  color: #7f8c8d;
  border: 1px solid #bdc3c7;
}

.btn-outline:hover {
  background: #ecf0f1;
  color: #2c3e50;
}

.dropdown {
  position: relative;
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  background: white;
  border: 1px solid #ddd;
  border-radius: 6px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  z-index: 1000;
  min-width: 180px;
  padding: 4px 0;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  padding: 8px 16px;
  background: none;
  border: none;
  text-align: left;
  font-size: 14px;
  cursor: pointer;
  color: #2c3e50;
  transition: all 0.3s ease;
}

.dropdown-item:hover {
  background: #f8f9fa;
}

.dropdown-divider {
  margin: 4px 0;
  border: none;
  border-top: 1px solid #ecf0f1;
}

.text-warning { color: #f39c12 !important; }
.text-success { color: #27ae60 !important; }
.text-danger { color: #e74c3c !important; }

/* Loading and Error States */
.loading-container,
.error-container {
  text-align: center;
  padding: 64px 32px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.loading-spinner,
.error-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.loading-spinner {
  color: #3498db;
}

.error-icon {
  color: #e74c3c;
}

/* Content Cards */
.detail-content {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.info-cards {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr;
  gap: 20px;
}

.info-card {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  overflow: hidden;
}

.info-card.primary {
  border-top: 4px solid #3498db;
}

.card-header {
  background: #f8f9fa;
  padding: 16px 20px;
  border-bottom: 1px solid #ecf0f1;
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

.card-content {
  padding: 20px;
}

/* Rate Display */
.rate-display {
  text-align: center;
  margin-bottom: 20px;
}

.conversion-equation,
.reverse-equation {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-bottom: 8px;
  font-size: 18px;
}

.conversion-equation {
  font-weight: 600;
  color: #2c3e50;
}

.reverse-equation {
  font-size: 14px;
  color: #7f8c8d;
}

.amount {
  font-weight: 700;
}

.currency {
  padding: 4px 8px;
  background: #ecf0f1;
  border-radius: 4px;
  font-weight: 600;
  font-size: 14px;
}

.currency.from {
  background: #e3f2fd;
  color: #1976d2;
}

.currency.to {
  background: #e8f5e8;
  color: #2e7d32;
}

.equals {
  color: #7f8c8d;
  font-weight: normal;
}

.rate-value {
  color: #2ecc71;
  font-weight: 700;
}

.rate-badges {
  display: flex;
  justify-content: center;
  gap: 8px;
  flex-wrap: wrap;
}

.badge {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  display: flex;
  align-items: center;
  gap: 4px;
}

.method-direct { background: #d5f4e6; color: #27ae60; }
.method-inverse { background: #fef9e7; color: #f39c12; }
.method-cross { background: #f4ecf7; color: #9b59b6; }

.confidence-high { background: #d5f4e6; color: #27ae60; }
.confidence-medium { background: #fef9e7; color: #f39c12; }
.confidence-low { background: #ffeaea; color: #e74c3c; }

.bidirectional { background: #e3f2fd; color: #1976d2; }
.reverse-available { background: #f3e5f5; color: #7b1fa2; }

/* Date Range */
.date-range {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 16px;
}

.date-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.date-item label {
  font-size: 12px;
  color: #7f8c8d;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 600;
}

.date-value {
  display: flex;
  align-items: center;
  gap: 6px;
  font-weight: 500;
  color: #2c3e50;
}

.validity-status {
  text-align: center;
}

.status-indicator {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 12px;
  font-weight: 500;
  font-size: 13px;
}

.status-active { background: #d5f4e6; color: #27ae60; }
.status-inactive { background: #ffeaea; color: #e74c3c; }
.status-future { background: #fff3cd; color: #856404; }
.status-expired { background: #f8d7da; color: #721c24; }

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.stat-item {
  text-align: center;
}

.stat-item .stat-label {
  font-size: 11px;
  color: #7f8c8d;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 4px;
}

.stat-item .stat-value {
  font-weight: 600;
  color: #2c3e50;
  font-size: 14px;
}

/* Calculation Path */
.calculation-path-card {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  overflow: hidden;
  border-left: 4px solid #9b59b6;
}

.path-visualization {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.path-description p {
  color: #7f8c8d;
  margin-bottom: 8px;
}

.path-steps {
  background: #f8f9fa;
  padding: 12px;
  border-radius: 6px;
  border-left: 3px solid #9b59b6;
}

.path-step {
  font-family: monospace;
  font-weight: 600;
  color: #2c3e50;
}

.source-rate-info h4 {
  color: #2c3e50;
  margin-bottom: 8px;
  font-size: 14px;
}

/* Related Rates */
.related-rates-section {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  padding: 24px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.section-header h3 {
  margin: 0;
  color: #2c3e50;
  font-size: 18px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}

.related-rates-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 16px;
}

.related-rate-card {
  background: #f8f9fa;
  border-radius: 6px;
  padding: 16px;
  border: 1px solid #ecf0f1;
}

.rate-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.rate-type {
  background: #3498db;
  color: white;
  padding: 2px 6px;
  border-radius: 8px;
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
}

.rate-pair {
  font-weight: 500;
  color: #2c3e50;
  font-size: 13px;
}

.rate-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.rate-content .rate-value {
  font-weight: 700;
  color: #2ecc71;
  font-size: 16px;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 12px;
}

.no-related-rates {
  grid-column: 1 / -1;
  text-align: center;
  padding: 32px;
  color: #7f8c8d;
}

.no-related-rates i {
  font-size: 32px;
  margin-bottom: 12px;
  color: #bdc3c7;
}

/* Quick Calculator */
.quick-calculator {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  padding: 24px;
}

.calculator-row {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  gap: 20px;
  align-items: end;
  margin-bottom: 16px;
}

.amount-input-group,
.result-display-group {
  display: flex;
  flex-direction: column;
}

.amount-input-group label,
.result-display-group label {
  font-weight: 500;
  color: #2c3e50;
  margin-bottom: 6px;
  font-size: 14px;
}

.amount-input {
  padding: 12px 16px;
  border: 2px solid #ecf0f1;
  border-radius: 6px;
  font-size: 16px;
  font-weight: 600;
  transition: all 0.3s ease;
}

.amount-input:focus {
  outline: none;
  border-color: #3498db;
  box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.result-display {
  padding: 12px 16px;
  background: #f8f9fa;
  border: 2px solid #2ecc71;
  border-radius: 6px;
  font-size: 16px;
  font-weight: 700;
  color: #2ecc71;
  text-align: center;
}

.conversion-arrow {
  font-size: 20px;
  color: #3498db;
  display: flex;
  align-items: center;
  justify-content: center;
}

.quick-amounts {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.quick-amounts .label {
  font-size: 14px;
  color: #7f8c8d;
  font-weight: 500;
}

.quick-amount-btn {
  padding: 6px 12px;
  background: #ecf0f1;
  border: 1px solid #bdc3c7;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 13px;
  color: #2c3e50;
}

.quick-amount-btn:hover {
  background: #3498db;
  color: white;
  border-color: #3498db;
}

/* Historical Section */
.historical-section {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  padding: 24px;
}

.historical-chart {
  height: 300px;
  margin-bottom: 20px;
  position: relative;
}

.historical-stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  padding: 16px;
  background: #f8f9fa;
  border-radius: 6px;
}

.historical-stats .stat-item {
  text-align: center;
}

.historical-stats .stat-item label {
  display: block;
  font-size: 12px;
  color: #7f8c8d;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 4px;
  font-weight: 600;
}

.historical-stats .value {
  font-weight: 700;
  color: #2c3e50;
}

.historical-stats .value.positive {
  color: #2ecc71;
}

.historical-stats .value.negative {
  color: #e74c3c;
}

/* Audit Trail */
.audit-trail {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  padding: 24px;
}

.audit-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.audit-entry {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px;
  background: #f8f9fa;
  border-radius: 6px;
  border-left: 3px solid #3498db;
}

.audit-icon {
  width: 32px;
  height: 32px;
  background: #3498db;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  flex-shrink: 0;
}

.audit-content {
  flex: 1;
}

.audit-action {
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 4px;
}

.audit-details {
  color: #7f8c8d;
  font-size: 14px;
  margin-bottom: 8px;
}

.audit-meta {
  display: flex;
  gap: 12px;
  font-size: 12px;
  color: #95a5a6;
}

.audit-user {
  font-weight: 500;
}

.no-audit-entries {
  text-align: center;
  padding: 32px;
  color: #7f8c8d;
}

.no-audit-entries i {
  font-size: 32px;
  margin-bottom: 12px;
  color: #bdc3c7;
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

.modal-dialog {
  background: white;
  border-radius: 8px;
  max-width: 500px;
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
}

.warning-message {
  text-align: center;
}

.warning-message i {
  font-size: 48px;
  color: #f39c12;
  margin-bottom: 16px;
}

.warning-message p {
  margin-bottom: 8px;
  color: #2c3e50;
}

.modal-footer {
  padding: 16px 24px;
  border-top: 1px solid #ecf0f1;
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  background: #f8f9fa;
}

.btn-danger {
  background: #e74c3c;
  color: white;
}

.btn-danger:hover {
  background: #c0392b;
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
@media (max-width: 1024px) {
  .info-cards {
    grid-template-columns: 1fr;
  }
  
  .calculator-row {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  
  .conversion-arrow {
    transform: rotate(90deg);
  }
  
  .historical-stats {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .currency-rate-detail {
    padding: 16px;
  }
  
  .detail-header {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
  }
  
  .header-actions {
    justify-content: stretch;
  }
  
  .rate-title h1 {
    font-size: 24px;
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
  
  .rate-meta {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
  
  .related-rates-grid {
    grid-template-columns: 1fr;
  }
  
  .historical-stats {
    grid-template-columns: 1fr;
  }
  
  .quick-amounts {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .toast {
    min-width: auto;
    margin: 0 16px;
  }
}

@media (max-width: 480px) {
  .rate-title h1 {
    font-size: 20px;
  }
  
  .currency-pair {
    flex-direction: column;
    gap: 4px;
  }
  
  .btn {
    padding: 8px 12px;
    font-size: 12px;
  }
  
  .card-content {
    padding: 16px;
  }
  
  .conversion-equation,
  .reverse-equation {
    flex-direction: column;
    gap: 4px;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
}
</style>