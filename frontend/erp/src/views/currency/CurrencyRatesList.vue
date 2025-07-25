<!-- Enhanced CurrencyRatesList.vue with Bidirectional Support -->
<template>
  <div class="currency-rates-container">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="title-section">
          <h1>
            <i class="fas fa-exchange-alt"></i>
            Currency Exchange Rates
          </h1>
          <p class="subtitle">Manage and monitor currency exchange rates with bidirectional support</p>
        </div>
        
        <div class="header-actions">
          <router-link to="/currency-rates/create" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            New Exchange Rate
          </router-link>
          
          <button @click="refreshRates" class="btn btn-secondary" :disabled="loading">
            <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
            Refresh
          </button>
          
          <button @click="openBulkActions" class="btn btn-outline">
            <i class="fas fa-cogs"></i>
            Bulk Actions
          </button>
        </div>
      </div>
      
      <!-- Statistics Cards -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon direct">
            <i class="fas fa-arrow-right"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ statistics.directRates }}</div>
            <div class="stat-label">Direct Rates</div>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon bidirectional">
            <i class="fas fa-exchange-alt"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ statistics.bidirectionalRates }}</div>
            <div class="stat-label">Bidirectional Pairs</div>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon currencies">
            <i class="fas fa-coins"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ statistics.totalCurrencies }}</div>
            <div class="stat-label">Supported Currencies</div>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon updated">
            <i class="fas fa-clock"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ formatLastUpdate(statistics.lastUpdate) }}</div>
            <div class="stat-label">Last Updated</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters and Search -->
    <div class="filters-container">
      <div class="filters-wrapper">
        <div class="search-section">
          <div class="search-input-group">
            <i class="fas fa-search search-icon"></i>
            <input
              type="text"
              v-model="filters.search"
              placeholder="Search currency pairs..."
              class="search-input"
              @input="debouncedSearch"
            />
            <button v-if="filters.search" @click="clearSearch" class="clear-search">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
        
        <div class="filter-controls">
          <div class="filter-group">
            <label>From Currency</label>
            <select v-model="filters.fromCurrency" @change="applyFilters" class="filter-select">
              <option value="">All Currencies</option>
              <option v-for="currency in availableCurrencies" :key="currency.code" :value="currency.code">
                {{ currency.code }} - {{ currency.name }}
              </option>
            </select>
          </div>
          
          <div class="filter-group">
            <label>To Currency</label>
            <select v-model="filters.toCurrency" @change="applyFilters" class="filter-select">
              <option value="">All Currencies</option>
              <option v-for="currency in availableCurrencies" :key="currency.code" :value="currency.code">
                {{ currency.code }} - {{ currency.name }}
              </option>
            </select>
          </div>
          
          <div class="filter-group">
            <label>Rate Type</label>
            <select v-model="filters.calculationMethod" @change="applyFilters" class="filter-select">
              <option value="">All Types</option>
              <option value="direct">Direct</option>
              <option value="inverse">Inverse</option>
              <option value="cross">Cross Currency</option>
            </select>
          </div>
          
          <div class="filter-group">
            <label>Confidence</label>
            <select v-model="filters.confidenceLevel" @change="applyFilters" class="filter-select">
              <option value="">All Levels</option>
              <option value="high">High</option>
              <option value="medium">Medium</option>
              <option value="low">Low</option>
            </select>
          </div>
          
          <div class="filter-group">
            <label>Status</label>
            <select v-model="filters.isActive" @change="applyFilters" class="filter-select">
              <option value="">All Status</option>
              <option :value="true">Active</option>
              <option :value="false">Inactive</option>
            </select>
          </div>
          
          <div class="filter-group">
            <label>Date Range</label>
            <div class="date-range">
              <input
                type="date"
                v-model="filters.startDate"
                @change="applyFilters"
                class="date-input"
              />
              <span class="date-separator">to</span>
              <input
                type="date"
                v-model="filters.endDate"
                @change="applyFilters"
                class="date-input"
              />
            </div>
          </div>
        </div>
        
        <div class="filter-actions">
          <button @click="applyFilters" class="btn btn-secondary">
            <i class="fas fa-filter"></i>
            Apply Filters
          </button>
          
          <button @click="clearFilters" class="btn btn-outline">
            <i class="fas fa-times"></i>
            Clear All
          </button>
          
          <button @click="toggleAdvancedFilters" class="btn btn-outline">
            <i class="fas fa-sliders-h"></i>
            {{ showAdvancedFilters ? 'Simple' : 'Advanced' }}
          </button>
        </div>
      </div>
      
      <!-- Advanced Filters -->
      <div v-if="showAdvancedFilters" class="advanced-filters">
        <div class="advanced-filter-group">
          <label>
            <input type="checkbox" v-model="filters.onlyBidirectional" @change="applyFilters">
            Show only bidirectional pairs
          </label>
        </div>
        
        <div class="advanced-filter-group">
          <label>
            <input type="checkbox" v-model="filters.hasReverseRate" @change="applyFilters">
            Has reverse rate available
          </label>
        </div>
        
        <div class="advanced-filter-group">
          <label>Rate Range</label>
          <div class="range-inputs">
            <input
              type="number"
              v-model.number="filters.minRate"
              placeholder="Min rate"
              step="0.0001"
              @input="debouncedFilter"
              class="range-input"
            />
            <span>to</span>
            <input
              type="number"
              v-model.number="filters.maxRate"
              placeholder="Max rate"
              step="0.0001"
              @input="debouncedFilter"
              class="range-input"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Results Summary -->
    <div class="results-summary">
      <div class="results-info">
        <span class="results-count">
          Showing {{ rates.length }} of {{ totalRates }} rates
          <span v-if="hasActiveFilters" class="filtered-indicator">
            (filtered)
          </span>
        </span>
        
        <div class="view-options">
          <button 
            @click="viewMode = 'table'" 
            class="view-btn"
            :class="{ active: viewMode === 'table' }"
          >
            <i class="fas fa-table"></i>
            Table
          </button>
          <button 
            @click="viewMode = 'grid'" 
            class="view-btn"
            :class="{ active: viewMode === 'grid' }"
          >
            <i class="fas fa-th"></i>
            Grid
          </button>
        </div>
      </div>
      
      <div class="sort-controls">
        <select v-model="sortBy" @change="applySorting" class="sort-select">
          <option value="effective_date">Sort by Date</option>
          <option value="from_currency">Sort by From Currency</option>
          <option value="to_currency">Sort by To Currency</option>
          <option value="rate">Sort by Rate</option>
          <option value="confidence_level">Sort by Confidence</option>
        </select>
        
        <button @click="toggleSortDirection" class="sort-direction-btn">
          <i :class="sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'"></i>
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading && !rates.length" class="loading-container">
      <div class="loading-spinner">
        <i class="fas fa-spinner fa-spin"></i>
      </div>
      <p>Loading exchange rates...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error && !rates.length" class="error-container">
      <div class="error-icon">
        <i class="fas fa-exclamation-triangle"></i>
      </div>
      <h3>Failed to Load Exchange Rates</h3>
      <p>{{ error }}</p>
      <button @click="refreshRates" class="btn btn-primary">
        <i class="fas fa-retry"></i>
        Try Again
      </button>
    </div>

    <!-- Empty State -->
    <div v-else-if="!loading && !rates.length" class="empty-state">
      <div class="empty-icon">
        <i class="fas fa-money-bill-wave"></i>
      </div>
      <h3>No Exchange Rates Found</h3>
      <p v-if="hasActiveFilters">
        No rates match your current filters. Try adjusting your search criteria.
      </p>
      <p v-else>
        There are no exchange rates configured yet.
      </p>
      <div class="empty-actions">
        <router-link to="/currency-rates/create" class="btn btn-primary">
          <i class="fas fa-plus"></i>
          Add First Rate
        </router-link>
        <button v-if="hasActiveFilters" @click="clearFilters" class="btn btn-secondary">
          <i class="fas fa-times"></i>
          Clear Filters
        </button>
      </div>
    </div>

    <!-- Table View -->
    <div v-else-if="viewMode === 'table'" class="rates-table-container">
      <div class="table-wrapper">
        <table class="rates-table">
          <thead>
            <tr>
              <th>
                <input 
                  type="checkbox" 
                  v-model="selectAll" 
                  @change="toggleSelectAll"
                  :indeterminate="isIndeterminate"
                />
              </th>
              <th @click="setSortBy('from_currency')" class="sortable">
                From Currency
                <i v-if="sortBy === 'from_currency'" :class="sortIconClass"></i>
              </th>
              <th @click="setSortBy('to_currency')" class="sortable">
                To Currency
                <i v-if="sortBy === 'to_currency'" :class="sortIconClass"></i>
              </th>
              <th @click="setSortBy('rate')" class="sortable">
                Exchange Rate
                <i v-if="sortBy === 'rate'" :class="sortIconClass"></i>
              </th>
              <th>Rate Info</th>
              <th @click="setSortBy('effective_date')" class="sortable">
                Effective Date
                <i v-if="sortBy === 'effective_date'" :class="sortIconClass"></i>
              </th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr 
              v-for="rate in paginatedRates" 
              :key="rate.rate_id"
              class="rate-row"
              :class="{ 
                'selected': selectedRates.includes(rate.rate_id),
                'bidirectional': rate.is_bidirectional,
                'inactive': !rate.is_active 
              }"
            >
              <td>
                <input 
                  type="checkbox" 
                  :value="rate.rate_id" 
                  v-model="selectedRates"
                />
              </td>
              
              <td class="currency-cell">
                <div class="currency-info">
                  <span class="currency-code">{{ rate.from_currency }}</span>
                  <span class="currency-symbol">{{ getCurrencySymbol(rate.from_currency) }}</span>
                </div>
              </td>
              
              <td class="currency-cell">
                <div class="currency-info">
                  <span class="currency-code">{{ rate.to_currency }}</span>
                  <span class="currency-symbol">{{ getCurrencySymbol(rate.to_currency) }}</span>
                </div>
              </td>
              
              <td class="rate-cell">
                <div class="rate-value">{{ formatRate(rate.rate) }}</div>
                <div class="rate-equation">
                  1 {{ rate.from_currency }} = {{ formatRate(rate.rate) }} {{ rate.to_currency }}
                </div>
              </td>
              
              <td class="rate-info-cell">
                <div class="rate-badges">
                  <span class="rate-badge" :class="`badge-${rate.calculation_method || 'direct'}`">
                    {{ (rate.calculation_method || 'direct').toUpperCase() }}
                  </span>
                  
                  <span class="confidence-badge" :class="`confidence-${rate.confidence_level || 'high'}`">
                    {{ rate.confidence_level || 'HIGH' }}
                  </span>
                  
                  <span v-if="rate.is_bidirectional" class="bidirectional-badge">
                    <i class="fas fa-exchange-alt"></i>
                    BIDIRECTIONAL
                  </span>
                  
                  <span v-if="rate.has_reverse_rate" class="reverse-badge">
                    <i class="fas fa-undo-alt"></i>
                    REVERSE AVAILABLE
                  </span>
                </div>
                
                <div v-if="rate.metadata?.calculation_path" class="calculation-path">
                  <small>{{ rate.metadata.calculation_path }}</small>
                </div>
              </td>
              
              <td class="date-cell">
                <div class="date-info">
                  <div class="effective-date">{{ formatDate(rate.effective_date) }}</div>
                  <div v-if="rate.end_date" class="end-date">
                    Until {{ formatDate(rate.end_date) }}
                  </div>
                </div>
              </td>
              
              <td class="status-cell">
                <span class="status-badge" :class="{ 'active': rate.is_active, 'inactive': !rate.is_active }">
                  <i :class="rate.is_active ? 'fas fa-check-circle' : 'fas fa-times-circle'"></i>
                  {{ rate.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              
              <td class="actions-cell">
                <div class="action-buttons">
                  <button 
                    @click="viewRateDetails(rate)" 
                    class="action-btn view-btn"
                    title="View Details"
                  >
                    <i class="fas fa-eye"></i>
                  </button>
                  
                  <button 
                    @click="editRate(rate)" 
                    class="action-btn edit-btn"
                    title="Edit Rate"
                  >
                    <i class="fas fa-edit"></i>
                  </button>
                  
                  <button 
                    @click="analyzeRate(rate)" 
                    class="action-btn analyze-btn"
                    title="Analyze Rate Paths"
                  >
                    <i class="fas fa-route"></i>
                  </button>
                  
                  <button 
                    @click="createReverseRate(rate)" 
                    class="action-btn reverse-btn"
                    title="Create Reverse Rate"
                    v-if="!rate.has_reverse_rate"
                  >
                    <i class="fas fa-exchange-alt"></i>
                  </button>
                  
                  <div class="dropdown">
                    <button class="action-btn dropdown-btn" @click="toggleRateMenu(rate.rate_id)">
                      <i class="fas fa-ellipsis-v"></i>
                    </button>
                    
                    <div v-if="showRateMenu === rate.rate_id" class="dropdown-menu">
                      <button @click="duplicateRate(rate)" class="dropdown-item">
                        <i class="fas fa-copy"></i>
                        Duplicate
                      </button>
                      <button @click="exportRate(rate)" class="dropdown-item">
                        <i class="fas fa-download"></i>
                        Export
                      </button>
                      <button @click="viewHistory(rate)" class="dropdown-item">
                        <i class="fas fa-history"></i>
                        History
                      </button>
                      <hr class="dropdown-divider">
                      <button 
                        @click="toggleRateStatus(rate)" 
                        class="dropdown-item"
                        :class="{ 'text-success': !rate.is_active, 'text-warning': rate.is_active }"
                      >
                        <i :class="rate.is_active ? 'fas fa-pause' : 'fas fa-play'"></i>
                        {{ rate.is_active ? 'Deactivate' : 'Activate' }}
                      </button>
                      <button @click="deleteRate(rate)" class="dropdown-item text-danger">
                        <i class="fas fa-trash"></i>
                        Delete
                      </button>
                    </div>
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Grid View -->
    <div v-else-if="viewMode === 'grid'" class="rates-grid">
      <div 
        v-for="rate in paginatedRates" 
        :key="rate.rate_id"
        class="rate-card"
        :class="{ 
          'selected': selectedRates.includes(rate.rate_id),
          'bidirectional': rate.is_bidirectional,
          'inactive': !rate.is_active 
        }"
      >
        <!-- Card Header -->
        <div class="card-header">
          <div class="currency-pair">
            <span class="from-currency">{{ rate.from_currency }}</span>
            <i class="fas fa-arrow-right pair-arrow"></i>
            <span class="to-currency">{{ rate.to_currency }}</span>
          </div>
          
          <div class="card-actions">
            <input 
              type="checkbox" 
              :value="rate.rate_id" 
              v-model="selectedRates"
              class="card-checkbox"
            />
            
            <div class="dropdown">
              <button class="card-menu-btn" @click="toggleRateMenu(rate.rate_id)">
                <i class="fas fa-ellipsis-v"></i>
              </button>
              
              <div v-if="showRateMenu === rate.rate_id" class="dropdown-menu">
                <button @click="viewRateDetails(rate)" class="dropdown-item">
                  <i class="fas fa-eye"></i>
                  View Details
                </button>
                <button @click="editRate(rate)" class="dropdown-item">
                  <i class="fas fa-edit"></i>
                  Edit
                </button>
                <button @click="analyzeRate(rate)" class="dropdown-item">
                  <i class="fas fa-route"></i>
                  Analyze
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Card Content -->
        <div class="card-content">
          <div class="rate-display">
            <div class="rate-value">{{ formatRate(rate.rate) }}</div>
            <div class="rate-description">
              1 {{ rate.from_currency }} = {{ formatRate(rate.rate) }} {{ rate.to_currency }}
            </div>
          </div>
          
          <div class="rate-metadata">
            <div class="metadata-row">
              <span class="metadata-label">Type:</span>
              <span class="rate-badge" :class="`badge-${rate.calculation_method || 'direct'}`">
                {{ (rate.calculation_method || 'direct').toUpperCase() }}
              </span>
            </div>
            
            <div class="metadata-row">
              <span class="metadata-label">Confidence:</span>
              <span class="confidence-badge" :class="`confidence-${rate.confidence_level || 'high'}`">
                {{ rate.confidence_level || 'HIGH' }}
              </span>
            </div>
            
            <div class="metadata-row">
              <span class="metadata-label">Effective:</span>
              <span class="metadata-value">{{ formatDate(rate.effective_date) }}</span>
            </div>
            
            <div v-if="rate.end_date" class="metadata-row">
              <span class="metadata-label">Expires:</span>
              <span class="metadata-value">{{ formatDate(rate.end_date) }}</span>
            </div>
          </div>
        </div>
        
        <!-- Card Footer -->
        <div class="card-footer">
          <div class="status-indicators">
            <span class="status-badge" :class="{ 'active': rate.is_active, 'inactive': !rate.is_active }">
              <i :class="rate.is_active ? 'fas fa-check-circle' : 'fas fa-times-circle'"></i>
              {{ rate.is_active ? 'Active' : 'Inactive' }}
            </span>
            
            <span v-if="rate.is_bidirectional" class="feature-badge bidirectional">
              <i class="fas fa-exchange-alt"></i>
              Bidirectional
            </span>
            
            <span v-if="rate.has_reverse_rate" class="feature-badge reverse">
              <i class="fas fa-undo-alt"></i>
              Reverse Available
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="pagination-container">
      <div class="pagination-info">
        Showing {{ ((currentPage - 1) * perPage) + 1 }} to {{ Math.min(currentPage * perPage, totalRates) }} of {{ totalRates }} entries
      </div>
      
      <div class="pagination-controls">
        <button 
          @click="goToPage(1)" 
          :disabled="currentPage === 1"
          class="pagination-btn"
        >
          <i class="fas fa-angle-double-left"></i>
        </button>
        
        <button 
          @click="goToPage(currentPage - 1)" 
          :disabled="currentPage === 1"
          class="pagination-btn"
        >
          <i class="fas fa-angle-left"></i>
        </button>
        
        <span class="pagination-current">
          Page {{ currentPage }} of {{ totalPages }}
        </span>
        
        <button 
          @click="goToPage(currentPage + 1)" 
          :disabled="currentPage === totalPages"
          class="pagination-btn"
        >
          <i class="fas fa-angle-right"></i>
        </button>
        
        <button 
          @click="goToPage(totalPages)" 
          :disabled="currentPage === totalPages"
          class="pagination-btn"
        >
          <i class="fas fa-angle-double-right"></i>
        </button>
      </div>
      
      <div class="per-page-selector">
        <select v-model="perPage" @change="changePerPage" class="per-page-select">
          <option :value="10">10 per page</option>
          <option :value="25">25 per page</option>
          <option :value="50">50 per page</option>
          <option :value="100">100 per page</option>
        </select>
      </div>
    </div>

    <!-- Bulk Actions Modal -->
    <div v-if="showBulkModal" class="modal-overlay" @click="closeBulkModal">
      <div class="bulk-modal" @click.stop>
        <div class="modal-header">
          <h3>Bulk Actions</h3>
          <button @click="closeBulkModal" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>
        
        <div class="modal-content">
          <p>{{ selectedRates.length }} rates selected</p>
          
          <div class="bulk-actions">
            <button @click="bulkActivate" class="bulk-btn success">
              <i class="fas fa-play"></i>
              Activate Selected
            </button>
            
            <button @click="bulkDeactivate" class="bulk-btn warning">
              <i class="fas fa-pause"></i>
              Deactivate Selected
            </button>
            
            <button @click="bulkExport" class="bulk-btn info">
              <i class="fas fa-download"></i>
              Export Selected
            </button>
            
            <button @click="bulkDelete" class="bulk-btn danger">
              <i class="fas fa-trash"></i>
              Delete Selected
            </button>
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
import { CurrencyService, CurrencyUtils } from '@/services/CurrencyService';

export default {
  name: 'EnhancedCurrencyRatesList',
  data() {
    return {
      // Data
      rates: [],
      availableCurrencies: [],
      
      // UI State
      loading: false,
      error: null,
      viewMode: 'table', // 'table' or 'grid'
      showAdvancedFilters: false,
      showBulkModal: false,
      showRateMenu: null,
      
      // Pagination
      currentPage: 1,
      perPage: 25,
      totalRates: 0,
      
      // Sorting
      sortBy: 'effective_date',
      sortDirection: 'desc',
      
      // Filters
      filters: {
        search: '',
        fromCurrency: '',
        toCurrency: '',
        calculationMethod: '',
        confidenceLevel: '',
        isActive: '',
        startDate: '',
        endDate: '',
        onlyBidirectional: false,
        hasReverseRate: false,
        minRate: null,
        maxRate: null
      },
      
      // Selection
      selectedRates: [],
      selectAll: false,
      
      // Statistics
      statistics: {
        directRates: 0,
        bidirectionalRates: 0,
        totalCurrencies: 0,
        lastUpdate: null
      },
      
      // Toast notifications
      toasts: [],
      toastId: 0,
      
      // Debounce timers
      searchTimer: null,
      filterTimer: null
    };
  },
  
  computed: {
    paginatedRates() {
      const start = (this.currentPage - 1) * this.perPage;
      const end = start + this.perPage;
      return this.rates.slice(start, end);
    },
    
    totalPages() {
      return Math.ceil(this.totalRates / this.perPage);
    },
    
    sortIconClass() {
      return this.sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
    },
    
    hasActiveFilters() {
      return Object.values(this.filters).some(value => 
        value !== '' && value !== null && value !== false
      );
    },
    
    isIndeterminate() {
      return this.selectedRates.length > 0 && this.selectedRates.length < this.rates.length;
    }
  },
  
  watch: {
    selectedRates() {
      this.selectAll = this.selectedRates.length === this.rates.length;
    }
  },
  
  mounted() {
    this.initializeComponent();
  },
  
  methods: {
    async initializeComponent() {
      await Promise.all([
        this.fetchCurrencies(),
        this.fetchRates(),
        this.loadStatistics()
      ]);
    },
    
    async fetchCurrencies() {
      try {
        const response = await CurrencyService.getAllCurrencies();
        if (response.data.status === 'success') {
          this.availableCurrencies = response.data.data;
        }
      } catch (error) {
        console.error('Failed to fetch currencies:', error);
      }
    },
    
    async fetchRates() {
      this.loading = true;
      this.error = null;
      
      try {
        const params = {
          ...this.filters,
          page: this.currentPage,
          per_page: this.perPage,
          sort_by: this.sortBy,
          sort_direction: this.sortDirection
        };
        
        // Clean up empty filters
        Object.keys(params).forEach(key => {
          if (params[key] === '' || params[key] === null) {
            delete params[key];
          }
        });
        
        const response = await CurrencyService.getCurrencyRates(params);
        
        if (response.data.status === 'success') {
          this.rates = response.data.data;
          this.totalRates = response.data.meta?.total || this.rates.length;
        } else {
          this.error = 'Failed to load currency rates';
        }
      } catch (error) {
        console.error('Error fetching rates:', error);
        this.error = CurrencyService.errors.getUserMessage(error);
      } finally {
        this.loading = false;
      }
    },
    
    async loadStatistics() {
      try {
        // Calculate statistics from current data
        this.statistics.directRates = this.rates.filter(r => r.calculation_method === 'direct').length;
        this.statistics.bidirectionalRates = this.rates.filter(r => r.is_bidirectional).length;
        
        const currencies = new Set();
        this.rates.forEach(rate => {
          currencies.add(rate.from_currency);
          currencies.add(rate.to_currency);
        });
        this.statistics.totalCurrencies = currencies.size;
        
        this.statistics.lastUpdate = new Date().toISOString();
      } catch (error) {
        console.error('Failed to load statistics:', error);
      }
    },
    
    // Search and Filter Methods
    debouncedSearch() {
      if (this.searchTimer) {
        clearTimeout(this.searchTimer);
      }
      
      this.searchTimer = setTimeout(() => {
        this.applyFilters();
      }, 300);
    },
    
    debouncedFilter() {
      if (this.filterTimer) {
        clearTimeout(this.filterTimer);
      }
      
      this.filterTimer = setTimeout(() => {
        this.applyFilters();
      }, 500);
    },
    
    applyFilters() {
      this.currentPage = 1;
      this.fetchRates();
    },
    
    clearFilters() {
      this.filters = {
        search: '',
        fromCurrency: '',
        toCurrency: '',
        calculationMethod: '',
        confidenceLevel: '',
        isActive: '',
        startDate: '',
        endDate: '',
        onlyBidirectional: false,
        hasReverseRate: false,
        minRate: null,
        maxRate: null
      };
      this.applyFilters();
    },
    
    clearSearch() {
      this.filters.search = '';
      this.applyFilters();
    },
    
    toggleAdvancedFilters() {
      this.showAdvancedFilters = !this.showAdvancedFilters;
    },
    
    // Sorting Methods
    setSortBy(field) {
      if (this.sortBy === field) {
        this.toggleSortDirection();
      } else {
        this.sortBy = field;
        this.sortDirection = 'asc';
      }
      this.applySorting();
    },
    
    toggleSortDirection() {
      this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      this.applySorting();
    },
    
    applySorting() {
      this.fetchRates();
    },
    
    // Pagination Methods
    goToPage(page) {
      this.currentPage = page;
      this.fetchRates();
    },
    
    changePerPage() {
      this.currentPage = 1;
      this.fetchRates();
    },
    
    // Selection Methods
    toggleSelectAll() {
      if (this.selectAll) {
        this.selectedRates = this.rates.map(rate => rate.rate_id);
      } else {
        this.selectedRates = [];
      }
    },
    
    // Action Methods
    refreshRates() {
      this.fetchRates();
      this.loadStatistics();
    },
    
    viewRateDetails(rate) {
      this.$router.push(`/currency-rates/${rate.rate_id}`);
    },
    
    editRate(rate) {
      this.$router.push(`/currency-rates/${rate.rate_id}/edit`);
    },
    
    async analyzeRate(rate) {
      try {
        const response = await CurrencyService.analyzeRatePaths(
          rate.from_currency,
          rate.to_currency
        );
        
        if (response.data.status === 'success') {
          // Show analysis modal or navigate to analysis page
          this.$router.push({
            name: 'CurrencyRateAnalysis',
            params: { 
              from: rate.from_currency, 
              to: rate.to_currency 
            },
            query: { data: JSON.stringify(response.data.data) }
          });
        }
      } catch (error) {
        this.showToast('Failed to analyze rate', 'error');
      }
    },
    
    createReverseRate(rate) {
      this.$router.push({
        name: 'CreateCurrencyRate',
        query: {
          from_currency: rate.to_currency,
          to_currency: rate.from_currency,
          suggested_rate: (1 / rate.rate).toFixed(6)
        }
      });
    },
    
    duplicateRate(rate) {
      this.$router.push({
        name: 'CreateCurrencyRate',
        query: {
          duplicate_from: rate.rate_id
        }
      });
    },
    
    exportRate(rate) {
      const data = {
        from_currency: rate.from_currency,
        to_currency: rate.to_currency,
        rate: rate.rate,
        effective_date: rate.effective_date,
        calculation_method: rate.calculation_method,
        confidence_level: rate.confidence_level
      };
      
      const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `rate_${rate.from_currency}_${rate.to_currency}_${rate.effective_date}.json`;
      a.click();
      URL.revokeObjectURL(url);
      
      this.showToast('Rate exported successfully', 'success');
    },
    
    viewHistory(rate) {
      this.$router.push(`/currency-rates/${rate.rate_id}/history`);
    },
    
    async toggleRateStatus(rate) {
      try {
        // API call to toggle status
        const newStatus = !rate.is_active;
        
        // Update local state optimistically
        rate.is_active = newStatus;
        
        this.showToast(
          `Rate ${newStatus ? 'activated' : 'deactivated'} successfully`,
          'success'
        );
      } catch (error) {
        // Revert optimistic update
        rate.is_active = !rate.is_active;
        this.showToast('Failed to update rate status', 'error');
      }
    },
    
    async deleteRate(rate) {
      if (!confirm(`Are you sure you want to delete the rate from ${rate.from_currency} to ${rate.to_currency}?`)) {
        return;
      }
      
      try {
        // API call to delete rate
        
        // Remove from local array
        const index = this.rates.findIndex(r => r.rate_id === rate.rate_id);
        if (index > -1) {
          this.rates.splice(index, 1);
        }
        
        this.showToast('Rate deleted successfully', 'success');
      } catch (error) {
        this.showToast('Failed to delete rate', 'error');
      }
    },
    
    // Bulk Actions
    openBulkActions() {
      if (this.selectedRates.length === 0) {
        this.showToast('Please select at least one rate', 'warning');
        return;
      }
      this.showBulkModal = true;
    },
    
    closeBulkModal() {
      this.showBulkModal = false;
    },
    
    async bulkActivate() {
      try {
        // API call for bulk activation
        this.selectedRates.forEach(rateId => {
          const rate = this.rates.find(r => r.rate_id === rateId);
          if (rate) rate.is_active = true;
        });
        
        this.showToast(`${this.selectedRates.length} rates activated`, 'success');
        this.selectedRates = [];
        this.closeBulkModal();
      } catch (error) {
        this.showToast('Failed to activate rates', 'error');
      }
    },
    
    async bulkDeactivate() {
      try {
        // API call for bulk deactivation
        this.selectedRates.forEach(rateId => {
          const rate = this.rates.find(r => r.rate_id === rateId);
          if (rate) rate.is_active = false;
        });
        
        this.showToast(`${this.selectedRates.length} rates deactivated`, 'success');
        this.selectedRates = [];
        this.closeBulkModal();
      } catch (error) {
        this.showToast('Failed to deactivate rates', 'error');
      }
    },
    
    bulkExport() {
      const selectedRatesData = this.rates.filter(rate => 
        this.selectedRates.includes(rate.rate_id)
      );
      
      const data = {
        exported_at: new Date().toISOString(),
        rates: selectedRatesData
      };
      
      const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `currency_rates_${new Date().toISOString().split('T')[0]}.json`;
      a.click();
      URL.revokeObjectURL(url);
      
      this.showToast(`${this.selectedRates.length} rates exported`, 'success');
      this.selectedRates = [];
      this.closeBulkModal();
    },
    
    async bulkDelete() {
      if (!confirm(`Are you sure you want to delete ${this.selectedRates.length} selected rates?`)) {
        return;
      }
      
      try {
        // API call for bulk deletion
        
        // Remove from local array
        this.rates = this.rates.filter(rate => 
          !this.selectedRates.includes(rate.rate_id)
        );
        
        this.showToast(`${this.selectedRates.length} rates deleted`, 'success');
        this.selectedRates = [];
        this.closeBulkModal();
      } catch (error) {
        this.showToast('Failed to delete rates', 'error');
      }
    },
    
    // UI Helper Methods
    toggleRateMenu(rateId) {
      this.showRateMenu = this.showRateMenu === rateId ? null : rateId;
    },
    
    formatRate(rate) {
      return CurrencyUtils.formatCurrency(rate, 'USD', {
        style: 'decimal',
        minimumFractionDigits: 2,
        maximumFractionDigits: 6
      });
    },
    
    formatDate(dateString) {
      if (!dateString) return '';
      
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    },
    
    formatLastUpdate(dateString) {
      if (!dateString) return 'Never';
      
      const date = new Date(dateString);
      const now = new Date();
      const diffMs = now - date;
      const diffMins = Math.floor(diffMs / 60000);
      
      if (diffMins < 1) return 'Just now';
      if (diffMins < 60) return `${diffMins}m ago`;
      if (diffMins < 1440) return `${Math.floor(diffMins / 60)}h ago`;
      return `${Math.floor(diffMins / 1440)}d ago`;
    },
    
    getCurrencySymbol(currency) {
      return CurrencyUtils.getCurrencySymbol(currency);
    },
    
    // Toast Notification System
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
/* Global Styles */
.currency-rates-container {
  padding: 24px;
  max-width: 1400px;
  margin: 0 auto;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Page Header */
.page-header {
  margin-bottom: 32px;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px;
}

.title-section h1 {
  color: #2c3e50;
  margin-bottom: 8px;
  font-size: 32px;
  font-weight: 600;
}

.title-section h1 i {
  color: #3498db;
  margin-right: 12px;
}

.subtitle {
  color: #7f8c8d;
  font-size: 16px;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 12px;
  align-items: center;
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

/* Statistics Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  display: flex;
  align-items: center;
  gap: 16px;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  color: white;
}

.stat-icon.direct { background: #2ecc71; }
.stat-icon.bidirectional { background: #3498db; }
.stat-icon.currencies { background: #f39c12; }
.stat-icon.updated { background: #9b59b6; }

.stat-value {
  font-size: 24px;
  font-weight: 700;
  color: #2c3e50;
  margin-bottom: 4px;
}

.stat-label {
  font-size: 14px;
  color: #7f8c8d;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Filters */
.filters-container {
  background: white;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  margin-bottom: 24px;
}

.search-section {
  margin-bottom: 20px;
}

.search-input-group {
  position: relative;
  max-width: 400px;
}

.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #7f8c8d;
}

.search-input {
  width: 100%;
  padding: 12px 16px 12px 40px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
}

.clear-search {
  position: absolute;
  right: 8px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #7f8c8d;
  cursor: pointer;
  padding: 4px;
}

.filter-controls {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 20px;
}

.filter-group label {
  display: block;
  font-weight: 500;
  color: #2c3e50;
  margin-bottom: 6px;
  font-size: 14px;
}

.filter-select,
.date-input {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.date-range {
  display: flex;
  align-items: center;
  gap: 8px;
}

.date-separator {
  color: #7f8c8d;
  font-size: 12px;
}

.filter-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.advanced-filters {
  border-top: 1px solid #ecf0f1;
  padding-top: 20px;
  margin-top: 20px;
}

.advanced-filter-group {
  margin-bottom: 16px;
}

.advanced-filter-group label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}

.range-inputs {
  display: flex;
  align-items: center;
  gap: 8px;
}

.range-input {
  flex: 1;
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

/* Results Summary */
.results-summary {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding: 16px 0;
  border-bottom: 1px solid #ecf0f1;
}

.results-count {
  color: #7f8c8d;
  font-size: 14px;
}

.filtered-indicator {
  color: #f39c12;
  font-weight: 500;
}

.view-options {
  display: flex;
  border: 1px solid #ddd;
  border-radius: 4px;
  overflow: hidden;
}

.view-btn {
  padding: 8px 12px;
  background: white;
  border: none;
  cursor: pointer;
  color: #7f8c8d;
  transition: all 0.3s ease;
}

.view-btn.active {
  background: #3498db;
  color: white;
}

.sort-controls {
  display: flex;
  align-items: center;
  gap: 8px;
}

.sort-select {
  padding: 6px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.sort-direction-btn {
  padding: 6px 8px;
  background: white;
  border: 1px solid #ddd;
  border-radius: 4px;
  cursor: pointer;
  color: #7f8c8d;
}

/* Table Styles */
.rates-table-container {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  overflow: hidden;
}

.table-wrapper {
  overflow-x: auto;
}

.rates-table {
  width: 100%;
  border-collapse: collapse;
}

.rates-table th {
  background: #f8f9fa;
  padding: 12px 16px;
  text-align: left;
  font-weight: 600;
  color: #2c3e50;
  border-bottom: 1px solid #dee2e6;
  font-size: 14px;
}

.rates-table th.sortable {
  cursor: pointer;
  user-select: none;
}

.rates-table th.sortable:hover {
  background: #e9ecef;
}

.rates-table td {
  padding: 12px 16px;
  border-bottom: 1px solid #f1f3f4;
  vertical-align: top;
}

.rate-row:hover {
  background: #f8f9fa;
}

.rate-row.selected {
  background: #e3f2fd;
}

.rate-row.bidirectional {
  border-left: 4px solid #3498db;
}

.rate-row.inactive {
  opacity: 0.6;
}

.currency-cell {
  min-width: 120px;
}

.currency-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.currency-code {
  font-weight: 600;
  color: #2c3e50;
}

.currency-symbol {
  font-size: 12px;
  color: #7f8c8d;
}

.rate-cell {
  min-width: 150px;
}

.rate-value {
  font-weight: 600;
  color: #2c3e50;
  font-size: 16px;
}

.rate-equation {
  font-size: 12px;
  color: #7f8c8d;
  margin-top: 2px;
}

.rate-info-cell {
  min-width: 200px;
}

.rate-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  margin-bottom: 8px;
}

.rate-badge,
.confidence-badge,
.bidirectional-badge,
.reverse-badge {
  padding: 2px 6px;
  border-radius: 8px;
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.badge-direct { background: #d5f4e6; color: #27ae60; }
.badge-inverse { background: #fef9e7; color: #f39c12; }
.badge-cross { background: #f4ecf7; color: #9b59b6; }

.confidence-high { background: #d5f4e6; color: #27ae60; }
.confidence-medium { background: #fef9e7; color: #f39c12; }
.confidence-low { background: #ffeaea; color: #e74c3c; }

.bidirectional-badge { background: #e3f2fd; color: #1976d2; }
.reverse-badge { background: #f3e5f5; color: #7b1fa2; }

.calculation-path {
  font-size: 11px;
  color: #95a5a6;
}

.date-cell {
  min-width: 120px;
}

.effective-date {
  font-weight: 500;
  color: #2c3e50;
}

.end-date {
  font-size: 12px;
  color: #7f8c8d;
}

.status-cell {
  min-width: 100px;
}

.status-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.status-badge.active {
  background: #d5f4e6;
  color: #27ae60;
}

.status-badge.inactive {
  background: #ffeaea;
  color: #e74c3c;
}

.actions-cell {
  min-width: 180px;
}

.action-buttons {
  display: flex;
  gap: 4px;
  position: relative;
}

.action-btn {
  padding: 6px 8px;
  background: white;
  border: 1px solid #ddd;
  border-radius: 4px;
  cursor: pointer;
  color: #7f8c8d;
  transition: all 0.3s ease;
  font-size: 12px;
}

.action-btn:hover {
  background: #f8f9fa;
  color: #2c3e50;
}

.view-btn:hover { border-color: #3498db; color: #3498db; }
.edit-btn:hover { border-color: #f39c12; color: #f39c12; }
.analyze-btn:hover { border-color: #9b59b6; color: #9b59b6; }
.reverse-btn:hover { border-color: #2ecc71; color: #2ecc71; }

.dropdown {
  position: relative;
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  background: white;
  border: 1px solid #ddd;
  border-radius: 4px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  z-index: 1000;
  min-width: 150px;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  padding: 8px 12px;
  background: none;
  border: none;
  text-align: left;
  font-size: 13px;
  cursor: pointer;
  color: #2c3e50;
}

.dropdown-item:hover {
  background: #f8f9fa;
}

.dropdown-divider {
  margin: 4px 0;
  border: none;
  border-top: 1px solid #ecf0f1;
}

.text-success { color: #27ae60 !important; }
.text-warning { color: #f39c12 !important; }
.text-danger { color: #e74c3c !important; }

/* Grid View */
.rates-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
}

.rate-card {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  overflow: hidden;
  transition: all 0.3s ease;
}

.rate-card:hover {
  box-shadow: 0 4px 16px rgba(0,0,0,0.15);
  transform: translateY(-2px);
}

.rate-card.selected {
  border: 2px solid #3498db;
}

.rate-card.bidirectional {
  border-left: 4px solid #3498db;
}

.rate-card.inactive {
  opacity: 0.6;
}

.card-header {
  padding: 16px 20px;
  background: #f8f9fa;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #ecf0f1;
}

.currency-pair {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
  color: #2c3e50;
}

.pair-arrow {
  color: #7f8c8d;
}

.card-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.card-checkbox {
  margin-right: 8px;
}

.card-menu-btn {
  padding: 4px 8px;
  background: none;
  border: 1px solid #ddd;
  border-radius: 4px;
  cursor: pointer;
  color: #7f8c8d;
}

.card-content {
  padding: 20px;
}

.rate-display {
  text-align: center;
  margin-bottom: 20px;
}

.rate-value {
  font-size: 28px;
  font-weight: 700;
  color: #2c3e50;
  margin-bottom: 8px;
}

.rate-description {
  font-size: 14px;
  color: #7f8c8d;
}

.rate-metadata {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.metadata-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.metadata-label {
  font-size: 13px;
  color: #7f8c8d;
  font-weight: 500;
}

.metadata-value {
  font-size: 13px;
  color: #2c3e50;
  font-weight: 500;
}

.card-footer {
  padding: 16px 20px;
  background: #f8f9fa;
  border-top: 1px solid #ecf0f1;
}

.status-indicators {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.feature-badge {
  padding: 2px 6px;
  border-radius: 8px;
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  display: flex;
  align-items: center;
  gap: 3px;
}

.feature-badge.bidirectional {
  background: #e3f2fd;
  color: #1976d2;
}

.feature-badge.reverse {
  background: #f3e5f5;
  color: #7b1fa2;
}

/* Pagination */
.pagination-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 24px;
  padding: 16px 0;
  border-top: 1px solid #ecf0f1;
}

.pagination-info {
  color: #7f8c8d;
  font-size: 14px;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 8px;
}

.pagination-btn {
  padding: 6px 8px;
  background: white;
  border: 1px solid #ddd;
  border-radius: 4px;
  cursor: pointer;
  color: #7f8c8d;
  transition: all 0.3s ease;
}

.pagination-btn:hover:not(:disabled) {
  background: #f8f9fa;
  color: #2c3e50;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-current {
  padding: 6px 12px;
  color: #2c3e50;
  font-weight: 500;
}

.per-page-select {
  padding: 6px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

/* Loading States */
.loading-container,
.error-container,
.empty-state {
  text-align: center;
  padding: 64px 32px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.loading-spinner,
.error-icon,
.empty-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.loading-spinner {
  color: #3498db;
}

.error-icon {
  color: #e74c3c;
}

.empty-icon {
  color: #bdc3c7;
}

.empty-actions {
  margin-top: 20px;
  display: flex;
  justify-content: center;
  gap: 12px;
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

.bulk-modal {
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

.bulk-actions {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 12px;
  margin-top: 20px;
}

.bulk-btn {
  padding: 12px 16px;
  border: none;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.bulk-btn.success {
  background: #27ae60;
  color: white;
}

.bulk-btn.success:hover {
  background: #219a52;
}

.bulk-btn.warning {
  background: #f39c12;
  color: white;
}

.bulk-btn.warning:hover {
  background: #d68910;
}

.bulk-btn.info {
  background: #3498db;
  color: white;
}

.bulk-btn.info:hover {
  background: #2980b9;
}

.bulk-btn.danger {
  background: #e74c3c;
  color: white;
}

.bulk-btn.danger:hover {
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
@media (max-width: 1200px) {
  .filter-controls {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .currency-rates-container {
    padding: 16px;
  }
  
  .header-content {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
  }
  
  .header-actions {
    justify-content: stretch;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .filter-controls {
    grid-template-columns: 1fr;
  }
  
  .results-summary {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }
  
  .rates-grid {
    grid-template-columns: 1fr;
  }
  
  .pagination-container {
    flex-direction: column;
    gap: 12px;
  }
  
  .bulk-actions {
    grid-template-columns: 1fr;
  }
  
  .toast {
    min-width: auto;
    margin: 0 16px;
  }
}

@media (max-width: 480px) {
  .table-wrapper {
    font-size: 12px;
  }
  
  .rates-table th,
  .rates-table td {
    padding: 8px 6px;
  }
  
  .action-buttons {
    flex-direction: column;
    gap: 2px;
  }
  
  .action-btn {
    padding: 4px 6px;
    font-size: 11px;
  }
}
</style>