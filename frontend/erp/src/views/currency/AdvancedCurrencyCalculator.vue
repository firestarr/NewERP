<!-- Advanced Currency Calculator with Multi-functionality -->
<template>
  <div class="advanced-calculator">
    <!-- Header -->
    <div class="calculator-header">
      <div class="header-title">
        <h1>
          <i class="fas fa-calculator"></i>
          Advanced Currency Calculator
        </h1>
        <p class="subtitle">
          Comprehensive currency calculations with advanced analytics and multi-currency support
        </p>
      </div>
      
      <div class="header-actions">
        <button @click="saveCalculation" class="btn btn-primary" :disabled="!hasActiveCalculation">
          <i class="fas fa-save"></i>
          Save Calculation
        </button>
        
        <button @click="exportResults" class="btn btn-secondary" :disabled="!hasResults">
          <i class="fas fa-download"></i>
          Export Results
        </button>
        
        <button @click="clearAll" class="btn btn-outline">
          <i class="fas fa-trash"></i>
          Clear All
        </button>
      </div>
    </div>

    <!-- Main Content -->
    <div class="calculator-content">
      <!-- Tab Navigation -->
      <div class="tab-navigation">
        <button 
          v-for="tab in tabs" 
          :key="tab.id"
          @click="activeTab = tab.id"
          class="tab-btn"
          :class="{ active: activeTab === tab.id }"
        >
          <i :class="tab.icon"></i>
          <span>{{ tab.label }}</span>
          <span v-if="tab.badge" class="tab-badge">{{ tab.badge }}</span>
        </button>
      </div>

      <!-- Tab Content -->
      <div class="tab-content">
        <!-- Simple Converter Tab -->
        <div v-if="activeTab === 'converter'" class="tab-pane active">
          <div class="converter-section">
            <div class="converter-panel">
              <!-- Enhanced Simple Converter -->
              <div class="currency-conversion">
                <div class="conversion-row">
                  <div class="amount-input-group">
                    <label>Amount</label>
                    <input
                      v-model.number="simpleCalc.amount"
                      type="number"
                      step="0.01"
                      min="0"
                      placeholder="Enter amount"
                      class="amount-input"
                      @input="performSimpleConversion"
                    />
                  </div>
                  
                  <div class="currency-select-group">
                    <label>From Currency</label>
                    <select v-model="simpleCalc.fromCurrency" @change="performSimpleConversion" class="currency-select">
                      <option value="">Select Currency</option>
                      <option v-for="currency in currencies" :key="currency.code" :value="currency.code">
                        {{ currency.code }} - {{ currency.name }}
                      </option>
                    </select>
                  </div>
                  
                  <div class="swap-button">
                    <button @click="swapSimpleCurrencies" class="swap-btn">
                      <i class="fas fa-exchange-alt"></i>
                    </button>
                  </div>
                  
                  <div class="currency-select-group">
                    <label>To Currency</label>
                    <select v-model="simpleCalc.toCurrency" @change="performSimpleConversion" class="currency-select">
                      <option value="">Select Currency</option>
                      <option v-for="currency in currencies" :key="currency.code" :value="currency.code">
                        {{ currency.code }} - {{ currency.name }}
                      </option>
                    </select>
                  </div>
                  
                  <div class="result-display-group">
                    <label>Converted Amount</label>
                    <input
                      :value="formatAmount(simpleCalc.result, simpleCalc.toCurrency)"
                      type="text"
                      readonly
                      class="result-input"
                    />
                  </div>
                </div>
                
                <!-- Rate Information -->
                <div v-if="simpleCalc.rateInfo" class="rate-information">
                  <div class="rate-display">
                    <span class="rate-equation">
                      1 {{ simpleCalc.fromCurrency }} = {{ formatRate(simpleCalc.rateInfo.rate) }} {{ simpleCalc.toCurrency }}
                    </span>
                    <span class="rate-direction" :class="`direction-${simpleCalc.rateInfo.direction}`">
                      {{ simpleCalc.rateInfo.direction.toUpperCase() }}
                    </span>
                    <span class="rate-confidence" :class="`confidence-${simpleCalc.rateInfo.confidence}`">
                      {{ simpleCalc.rateInfo.confidence.toUpperCase() }}
                    </span>
                  </div>
                  
                  <div class="rate-metadata">
                    <span class="rate-date">Rate from {{ formatDate(simpleCalc.rateInfo.date) }}</span>
                    <span v-if="simpleCalc.rateInfo.calculation_path" class="calculation-path">
                      Path: {{ simpleCalc.rateInfo.calculation_path }}
                    </span>
                  </div>
                </div>
              </div>
              
              <!-- Quick Amount Buttons -->
              <div class="quick-amounts">
                <h4>Quick Amounts</h4>
                <div class="amount-buttons">
                  <button 
                    v-for="amount in quickAmounts" 
                    :key="amount"
                    @click="setQuickAmount(amount)"
                    class="amount-btn"
                  >
                    {{ formatNumber(amount) }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Multi-Currency Calculator Tab -->
        <div v-if="activeTab === 'multi'" class="tab-pane active">
          <div class="multi-currency-section">
            <div class="section-header">
              <h3>Multi-Currency Calculator</h3>
              <p>Perform calculations across multiple currencies simultaneously</p>
            </div>
            
            <div class="multi-calc-controls">
              <div class="base-currency-selector">
                <label>Base Currency for Display</label>
                <select v-model="multiCalc.baseCurrency" @change="updateMultiCalculations" class="currency-select">
                  <option v-for="currency in currencies" :key="currency.code" :value="currency.code">
                    {{ currency.code }} - {{ currency.name }}
                  </option>
                </select>
              </div>
              
              <div class="operation-selector">
                <label>Operation</label>
                <select v-model="multiCalc.operation" @change="updateMultiCalculations" class="operation-select">
                  <option value="sum">Sum All</option>
                  <option value="average">Average</option>
                  <option value="max">Maximum</option>
                  <option value="min">Minimum</option>
                  <option value="multiply">Multiply</option>
                  <option value="divide">Divide</option>
                </select>
              </div>
              
              <button @click="addCurrencyLine" class="btn btn-secondary">
                <i class="fas fa-plus"></i>
                Add Currency
              </button>
            </div>
            
            <!-- Currency Lines -->
            <div class="currency-lines">
              <div 
                v-for="(line, index) in multiCalc.lines" 
                :key="line.id"
                class="currency-line"
              >
                <div class="line-number">{{ index + 1 }}</div>
                
                <div class="line-amount">
                  <input
                    v-model.number="line.amount"
                    type="number"
                    step="0.01"
                    placeholder="Amount"
                    class="amount-input"
                    @input="updateMultiCalculations"
                  />
                </div>
                
                <div class="line-currency">
                  <select v-model="line.currency" @change="updateMultiCalculations" class="currency-select">
                    <option value="">Select Currency</option>
                    <option v-for="currency in currencies" :key="currency.code" :value="currency.code">
                      {{ currency.code }}
                    </option>
                  </select>
                </div>
                
                <div class="line-converted">
                  <span class="converted-amount">
                    {{ formatAmount(line.convertedAmount, multiCalc.baseCurrency) }}
                  </span>
                  <span class="conversion-rate" v-if="line.rate">
                    Rate: {{ formatRate(line.rate) }}
                  </span>
                </div>
                
                <div class="line-actions">
                  <button @click="removeCurrencyLine(index)" class="remove-btn" :disabled="multiCalc.lines.length <= 1">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </div>
            </div>
            
            <!-- Multi-Currency Result -->
            <div v-if="multiCalc.result" class="multi-result">
              <div class="result-header">
                <h4>Calculation Result</h4>
                <span class="operation-label">{{ operationLabels[multiCalc.operation] }}</span>
              </div>
              
              <div class="result-display">
                <div class="primary-result">
                  <span class="result-amount">{{ formatAmount(multiCalc.result.amount, multiCalc.baseCurrency) }}</span>
                  <span class="result-currency">{{ multiCalc.baseCurrency }}</span>
                </div>
                
                <div class="result-breakdown">
                  <div class="breakdown-item" v-for="line in multiCalc.result.breakdown" :key="line.currency">
                    <span class="breakdown-currency">{{ line.currency }}:</span>
                    <span class="breakdown-amount">{{ formatAmount(line.originalAmount, line.currency) }}</span>
                    <span class="breakdown-converted">= {{ formatAmount(line.convertedAmount, multiCalc.baseCurrency) }} {{ multiCalc.baseCurrency }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Historical Analysis Tab -->
        <div v-if="activeTab === 'historical'" class="tab-pane active">
          <div class="historical-section">
            <div class="section-header">
              <h3>Historical Rate Analysis</h3>
              <p>Analyze currency rate trends and historical performance</p>
            </div>
            
            <div class="historical-controls">
              <div class="currency-pair-selector">
                <div class="currency-group">
                  <label>From Currency</label>
                  <select v-model="historical.fromCurrency" @change="loadHistoricalData" class="currency-select">
                    <option v-for="currency in currencies" :key="currency.code" :value="currency.code">
                      {{ currency.code }}
                    </option>
                  </select>
                </div>
                
                <div class="currency-group">
                  <label>To Currency</label>
                  <select v-model="historical.toCurrency" @change="loadHistoricalData" class="currency-select">
                    <option v-for="currency in currencies" :key="currency.code" :value="currency.code">
                      {{ currency.code }}
                    </option>
                  </select>
                </div>
              </div>
              
              <div class="date-range-selector">
                <div class="date-group">
                  <label>Start Date</label>
                  <input
                    v-model="historical.startDate"
                    type="date"
                    @change="loadHistoricalData"
                    class="date-input"
                  />
                </div>
                
                <div class="date-group">
                  <label>End Date</label>
                  <input
                    v-model="historical.endDate"
                    type="date"
                    @change="loadHistoricalData"
                    class="date-input"
                  />
                </div>
              </div>
              
              <div class="period-quick-select">
                <button 
                  v-for="period in historicalPeriods" 
                  :key="period.key"
                  @click="setHistoricalPeriod(period)"
                  class="period-btn"
                  :class="{ active: historical.selectedPeriod === period.key }"
                >
                  {{ period.label }}
                </button>
              </div>
            </div>
            
            <!-- Historical Results -->
            <div v-if="historical.data.length > 0" class="historical-results">
              <!-- Chart Placeholder -->
              <div class="chart-container">
                <canvas ref="historicalChart" width="800" height="400"></canvas>
              </div>
              
              <!-- Statistics -->
              <div class="historical-stats">
                <div class="stat-grid">
                  <div class="stat-item">
                    <div class="stat-label">Current Rate</div>
                    <div class="stat-value">{{ formatRate(historical.stats.current) }}</div>
                  </div>
                  
                  <div class="stat-item">
                    <div class="stat-label">Average Rate</div>
                    <div class="stat-value">{{ formatRate(historical.stats.average) }}</div>
                  </div>
                  
                  <div class="stat-item">
                    <div class="stat-label">Highest Rate</div>
                    <div class="stat-value">{{ formatRate(historical.stats.highest) }}</div>
                    <div class="stat-date">{{ formatDate(historical.stats.highestDate) }}</div>
                  </div>
                  
                  <div class="stat-item">
                    <div class="stat-label">Lowest Rate</div>
                    <div class="stat-value">{{ formatRate(historical.stats.lowest) }}</div>
                    <div class="stat-date">{{ formatDate(historical.stats.lowestDate) }}</div>
                  </div>
                  
                  <div class="stat-item">
                    <div class="stat-label">Volatility</div>
                    <div class="stat-value">{{ formatPercent(historical.stats.volatility) }}</div>
                  </div>
                  
                  <div class="stat-item">
                    <div class="stat-label">Period Change</div>
                    <div class="stat-value" :class="{ 
                      'positive': historical.stats.periodChange > 0,
                      'negative': historical.stats.periodChange < 0 
                    }">
                      {{ formatPercent(historical.stats.periodChange) }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div v-else-if="historical.loading" class="loading-state">
              <i class="fas fa-spinner fa-spin"></i>
              <p>Loading historical data...</p>
            </div>
            
            <div v-else class="empty-state">
              <i class="fas fa-chart-line"></i>
              <p>Select currencies and date range to view historical analysis</p>
            </div>
          </div>
        </div>

        <!-- Bulk Calculator Tab -->
        <div v-if="activeTab === 'bulk'" class="tab-pane active">
          <div class="bulk-section">
            <div class="section-header">
              <h3>Bulk Currency Calculator</h3>
              <p>Perform batch currency conversions for multiple amounts and currency pairs</p>
            </div>
            
            <div class="bulk-controls">
              <div class="bulk-actions">
                <button @click="addBulkRow" class="btn btn-primary">
                  <i class="fas fa-plus"></i>
                  Add Row
                </button>
                
                <button @click="importBulkData" class="btn btn-secondary">
                  <i class="fas fa-upload"></i>
                  Import CSV
                </button>
                
                <button @click="exportBulkResults" class="btn btn-secondary" :disabled="!hasBulkResults">
                  <i class="fas fa-download"></i>
                  Export Results
                </button>
                
                <button @click="clearBulkData" class="btn btn-outline">
                  <i class="fas fa-trash"></i>
                  Clear All
                </button>
              </div>
              
              <div class="bulk-settings">
                <div class="setting-group">
                  <label>
                    <input type="checkbox" v-model="bulk.useFixedDate">
                    Use Fixed Date
                  </label>
                  <input
                    v-if="bulk.useFixedDate"
                    v-model="bulk.fixedDate"
                    type="date"
                    class="date-input"
                    @change="recalculateBulk"
                  />
                </div>
                
                <div class="setting-group">
                  <label>
                    <input type="checkbox" v-model="bulk.autoCalculate">
                    Auto Calculate
                  </label>
                </div>
              </div>
            </div>
            
            <!-- Bulk Table -->
            <div class="bulk-table-container">
              <table class="bulk-table">
                <thead>
                  <tr>
                    <th>Row</th>
                    <th>Amount</th>
                    <th>From Currency</th>
                    <th>To Currency</th>
                    <th>Exchange Rate</th>
                    <th>Converted Amount</th>
                    <th>Rate Type</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr 
                    v-for="(row, index) in bulk.rows" 
                    :key="row.id"
                    class="bulk-row"
                    :class="{ 
                      'success': row.status === 'success',
                      'error': row.status === 'error',
                      'loading': row.status === 'loading' 
                    }"
                  >
                    <td>{{ index + 1 }}</td>
                    
                    <td>
                      <input
                        v-model.number="row.amount"
                        type="number"
                        step="0.01"
                        placeholder="Amount"
                        class="table-input"
                        @input="calculateBulkRow(row)"
                      />
                    </td>
                    
                    <td>
                      <select 
                        v-model="row.fromCurrency" 
                        @change="calculateBulkRow(row)"
                        class="table-select"
                      >
                        <option value="">Select</option>
                        <option v-for="currency in currencies" :key="currency.code" :value="currency.code">
                          {{ currency.code }}
                        </option>
                      </select>
                    </td>
                    
                    <td>
                      <select 
                        v-model="row.toCurrency" 
                        @change="calculateBulkRow(row)"
                        class="table-select"
                      >
                        <option value="">Select</option>
                        <option v-for="currency in currencies" :key="currency.code" :value="currency.code">
                          {{ currency.code }}
                        </option>
                      </select>
                    </td>
                    
                    <td class="rate-cell">
                      <span v-if="row.rate">{{ formatRate(row.rate) }}</span>
                      <span v-else-if="row.status === 'loading'">
                        <i class="fas fa-spinner fa-spin"></i>
                      </span>
                      <span v-else class="no-rate">-</span>
                    </td>
                    
                    <td class="result-cell">
                      <span v-if="row.convertedAmount">{{ formatAmount(row.convertedAmount, row.toCurrency) }}</span>
                      <span v-else class="no-result">-</span>
                    </td>
                    
                    <td class="type-cell">
                      <span v-if="row.rateType" class="rate-type-badge" :class="`type-${row.rateType}`">
                        {{ row.rateType.toUpperCase() }}
                      </span>
                    </td>
                    
                    <td class="status-cell">
                      <span class="status-indicator" :class="`status-${row.status}`">
                        <i v-if="row.status === 'success'" class="fas fa-check"></i>
                        <i v-else-if="row.status === 'error'" class="fas fa-times"></i>
                        <i v-else-if="row.status === 'loading'" class="fas fa-spinner fa-spin"></i>
                        <i v-else class="fas fa-minus"></i>
                      </span>
                    </td>
                    
                    <td class="actions-cell">
                      <button @click="duplicateBulkRow(row)" class="action-btn" title="Duplicate">
                        <i class="fas fa-copy"></i>
                      </button>
                      <button @click="removeBulkRow(index)" class="action-btn remove" title="Remove">
                        <i class="fas fa-trash"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            
            <!-- Bulk Summary -->
            <div v-if="hasBulkResults" class="bulk-summary">
              <div class="summary-stats">
                <div class="summary-item">
                  <div class="summary-label">Total Rows</div>
                  <div class="summary-value">{{ bulk.rows.length }}</div>
                </div>
                
                <div class="summary-item">
                  <div class="summary-label">Successful</div>
                  <div class="summary-value success">{{ bulkStats.successful }}</div>
                </div>
                
                <div class="summary-item">
                  <div class="summary-label">Failed</div>
                  <div class="summary-value error">{{ bulkStats.failed }}</div>
                </div>
                
                <div class="summary-item">
                  <div class="summary-label">Total Amount (Base)</div>
                  <div class="summary-value">{{ formatAmount(bulkStats.totalAmount, bulkStats.baseCurrency) }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Scenario Planning Tab -->
        <div v-if="activeTab === 'scenario'" class="tab-pane active">
          <div class="scenario-section">
            <div class="section-header">
              <h3>Scenario Planning</h3>
              <p>Analyze different currency scenarios and their financial impact</p>
            </div>
            
            <div class="scenario-controls">
              <div class="scenario-setup">
                <div class="base-scenario">
                  <h4>Base Scenario</h4>
                  <div class="scenario-inputs">
                    <div class="input-group">
                      <label>Amount</label>
                      <input v-model.number="scenario.baseAmount" type="number" step="0.01" class="amount-input">
                    </div>
                    
                    <div class="input-group">
                      <label>From Currency</label>
                      <select v-model="scenario.fromCurrency" class="currency-select">
                        <option v-for="currency in currencies" :key="currency.code" :value="currency.code">
                          {{ currency.code }}
                        </option>
                      </select>
                    </div>
                    
                    <div class="input-group">
                      <label>To Currency</label>
                      <select v-model="scenario.toCurrency" class="currency-select">
                        <option v-for="currency in currencies" :key="currency.code" :value="currency.code">
                          {{ currency.code }}
                        </option>
                      </select>
                    </div>
                  </div>
                </div>
                
                <div class="scenario-variations">
                  <h4>Rate Variations</h4>
                  <div class="variation-controls">
                    <div class="variation-input">
                      <label>Rate Change Range (%)</label>
                      <div class="range-inputs">
                        <input v-model.number="scenario.minChange" type="number" step="0.1" placeholder="Min" class="range-input">
                        <span>to</span>
                        <input v-model.number="scenario.maxChange" type="number" step="0.1" placeholder="Max" class="range-input">
                      </div>
                    </div>
                    
                    <div class="variation-input">
                      <label>Number of Scenarios</label>
                      <input v-model.number="scenario.scenarioCount" type="number" min="3" max="20" class="count-input">
                    </div>
                    
                    <button @click="generateScenarios" class="btn btn-primary">
                      <i class="fas fa-calculator"></i>
                      Generate Scenarios
                    </button>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Scenario Results -->
            <div v-if="scenario.results.length > 0" class="scenario-results">
              <div class="results-table-container">
                <table class="scenario-table">
                  <thead>
                    <tr>
                      <th>Scenario</th>
                      <th>Rate Change (%)</th>
                      <th>Exchange Rate</th>
                      <th>Converted Amount</th>
                      <th>Difference from Base</th>
                      <th>Impact</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr 
                      v-for="(result, index) in scenario.results" 
                      :key="index"
                      class="scenario-row"
                      :class="{ 
                        'base-scenario': result.isBase,
                        'positive-impact': result.impact > 0,
                        'negative-impact': result.impact < 0 
                      }"
                    >
                      <td>
                        <span v-if="result.isBase" class="scenario-label base">Base</span>
                        <span v-else class="scenario-label">{{ index }}</span>
                      </td>
                      
                      <td class="change-cell">
                        <span v-if="!result.isBase" :class="{ 'positive': result.rateChange > 0, 'negative': result.rateChange < 0 }">
                          {{ formatPercent(result.rateChange / 100) }}
                        </span>
                        <span v-else>-</span>
                      </td>
                      
                      <td>{{ formatRate(result.rate) }}</td>
                      
                      <td class="amount-cell">{{ formatAmount(result.convertedAmount, scenario.toCurrency) }}</td>
                      
                      <td class="difference-cell" :class="{ 'positive': result.difference > 0, 'negative': result.difference < 0 }">
                        <span v-if="!result.isBase">{{ formatAmount(result.difference, scenario.toCurrency) }}</span>
                        <span v-else>-</span>
                      </td>
                      
                      <td class="impact-cell">
                        <div class="impact-bar" :style="{ width: `${Math.abs(result.impactPercent)}%` }" :class="{ 'positive': result.impact > 0, 'negative': result.impact < 0 }"></div>
                        <span class="impact-text" :class="{ 'positive': result.impact > 0, 'negative': result.impact < 0 }">
                          {{ formatPercent(result.impactPercent / 100) }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              
              <!-- Scenario Summary -->
              <div class="scenario-summary">
                <div class="summary-cards">
                  <div class="summary-card best">
                    <div class="card-header">
                      <i class="fas fa-arrow-up"></i>
                      Best Case
                    </div>
                    <div class="card-content">
                      <div class="card-amount">{{ formatAmount(scenario.summary.bestCase.amount, scenario.toCurrency) }}</div>
                      <div class="card-change">{{ formatPercent(scenario.summary.bestCase.change / 100) }}</div>
                    </div>
                  </div>
                  
                  <div class="summary-card worst">
                    <div class="card-header">
                      <i class="fas fa-arrow-down"></i>
                      Worst Case
                    </div>
                    <div class="card-content">
                      <div class="card-amount">{{ formatAmount(scenario.summary.worstCase.amount, scenario.toCurrency) }}</div>
                      <div class="card-change">{{ formatPercent(scenario.summary.worstCase.change / 100) }}</div>
                    </div>
                  </div>
                  
                  <div class="summary-card average">
                    <div class="card-header">
                      <i class="fas fa-chart-line"></i>
                      Average
                    </div>
                    <div class="card-content">
                      <div class="card-amount">{{ formatAmount(scenario.summary.average.amount, scenario.toCurrency) }}</div>
                      <div class="card-change">{{ formatPercent(scenario.summary.average.change / 100) }}</div>
                    </div>
                  </div>
                  
                  <div class="summary-card volatility">
                    <div class="card-header">
                      <i class="fas fa-chart-area"></i>
                      Volatility Range
                    </div>
                    <div class="card-content">
                      <div class="card-amount">{{ formatAmount(scenario.summary.volatility.range, scenario.toCurrency) }}</div>
                      <div class="card-change">{{ formatPercent(scenario.summary.volatility.percent / 100) }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Calculation History Sidebar -->
    <div class="history-sidebar" :class="{ 'open': showHistory }">
      <div class="sidebar-header">
        <h3>Calculation History</h3>
        <button @click="toggleHistory" class="close-sidebar">
          <i class="fas fa-times"></i>
        </button>
      </div>
      
      <div class="sidebar-content">
        <div v-if="calculationHistory.length === 0" class="empty-history">
          <i class="fas fa-history"></i>
          <p>No calculations yet</p>
        </div>
        
        <div v-else class="history-list">
          <div 
            v-for="calc in calculationHistory" 
            :key="calc.id"
            class="history-item"
            @click="loadCalculation(calc)"
          >
            <div class="history-header">
              <span class="history-type">{{ calc.type }}</span>
              <span class="history-date">{{ formatDateTime(calc.timestamp) }}</span>
            </div>
            <div class="history-content">
              <div class="history-details">{{ calc.description }}</div>
              <div class="history-result">{{ calc.result }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- History Toggle Button -->
    <button @click="toggleHistory" class="history-toggle" :class="{ 'active': showHistory }">
      <i class="fas fa-history"></i>
      <span>History</span>
      <span v-if="calculationHistory.length > 0" class="history-count">{{ calculationHistory.length }}</span>
    </button>
  </div>
</template>

<script>
/* eslint-disable */
import { CurrencyService, CurrencyUtils } from '@/services/CurrencyService';
import Chart from 'chart.js/auto';

export default {
  name: 'AdvancedCurrencyCalculator',
  data() {
    return {
      activeTab: 'converter',
      showHistory: false,
      currencies: [],
      
      // Tab configuration
      tabs: [
        { id: 'converter', label: 'Simple Converter', icon: 'fas fa-exchange-alt' },
        { id: 'multi', label: 'Multi-Currency', icon: 'fas fa-coins' },
        { id: 'historical', label: 'Historical Analysis', icon: 'fas fa-chart-line' },
        { id: 'bulk', label: 'Bulk Calculator', icon: 'fas fa-table' },
        { id: 'scenario', label: 'Scenario Planning', icon: 'fas fa-project-diagram' }
      ],
      
      // Simple converter
      simpleCalc: {
        amount: 1000,
        fromCurrency: 'USD',
        toCurrency: 'EUR',
        result: 0,
        rateInfo: null
      },
      
      // Multi-currency calculator
      multiCalc: {
        baseCurrency: 'USD',
        operation: 'sum',
        lines: [
          { id: 1, amount: 1000, currency: 'USD', convertedAmount: 0, rate: null },
          { id: 2, amount: 850, currency: 'EUR', convertedAmount: 0, rate: null }
        ],
        result: null
      },
      
      // Historical analysis
      historical: {
        fromCurrency: 'USD',
        toCurrency: 'EUR',
        startDate: '',
        endDate: '',
        selectedPeriod: '',
        data: [],
        stats: {},
        loading: false
      },
      
      // Bulk calculator
      bulk: {
        rows: [
          { id: 1, amount: null, fromCurrency: '', toCurrency: '', rate: null, convertedAmount: null, rateType: null, status: 'pending' }
        ],
        useFixedDate: false,
        fixedDate: '',
        autoCalculate: true
      },
      
      // Scenario planning
      scenario: {
        baseAmount: 10000,
        fromCurrency: 'USD',
        toCurrency: 'EUR',
        minChange: -10,
        maxChange: 10,
        scenarioCount: 10,
        results: [],
        summary: {}
      },
      
      // Configuration
      quickAmounts: [100, 500, 1000, 5000, 10000, 50000],
      operationLabels: {
        sum: 'Sum of All Amounts',
        average: 'Average Amount',
        max: 'Maximum Amount',
        min: 'Minimum Amount',
        multiply: 'Product of All Amounts',
        divide: 'Division Result'
      },
      historicalPeriods: [
        { key: '7d', label: '7 Days', days: 7 },
        { key: '30d', label: '30 Days', days: 30 },
        { key: '90d', label: '3 Months', days: 90 },
        { key: '1y', label: '1 Year', days: 365 }
      ],
      
      // History and state
      calculationHistory: [],
      nextId: 1,
      historicalChart: null
    };
  },
  
  computed: {
    hasActiveCalculation() {
      return this.simpleCalc.result > 0 || 
             this.multiCalc.result !== null || 
             this.historical.data.length > 0 ||
             this.hasBulkResults ||
             this.scenario.results.length > 0;
    },
    
    hasResults() {
      return this.hasActiveCalculation;
    },
    
    hasBulkResults() {
      return this.bulk.rows.some(row => row.convertedAmount !== null);
    },
    
    bulkStats() {
      const successful = this.bulk.rows.filter(row => row.status === 'success').length;
      const failed = this.bulk.rows.filter(row => row.status === 'error').length;
      const totalAmount = this.bulk.rows
        .filter(row => row.status === 'success' && row.convertedAmount)
        .reduce((sum, row) => sum + row.convertedAmount, 0);
      
      return {
        successful,
        failed,
        totalAmount,
        baseCurrency: this.multiCalc.baseCurrency
      };
    }
  },
  
  mounted() {
    this.initializeCalculator();
  },
  
  beforeUnmount() {
    if (this.historicalChart) {
      this.historicalChart.destroy();
    }
  },
  
  methods: {
    async initializeCalculator() {
      await this.loadCurrencies();
      this.setDefaultDates();
      this.loadSavedHistory();
      await this.performSimpleConversion();
    },
    
    async loadCurrencies() {
      try {
        const response = await CurrencyService.getAllCurrencies();
        if (response.data.status === 'success') {
          this.currencies = response.data.data;
        }
      } catch (error) {
        console.error('Failed to load currencies:', error);
      }
    },
    
    setDefaultDates() {
      const today = new Date();
      const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
      
      this.historical.endDate = today.toISOString().split('T')[0];
      this.historical.startDate = thirtyDaysAgo.toISOString().split('T')[0];
    },
    
    // Simple Converter Methods
    async performSimpleConversion() {
      if (!this.simpleCalc.fromCurrency || !this.simpleCalc.toCurrency || !this.simpleCalc.amount) {
        this.simpleCalc.result = 0;
        this.simpleCalc.rateInfo = null;
        return;
      }
      
      try {
        const response = await CurrencyService.convertAmount(
          this.simpleCalc.amount,
          this.simpleCalc.fromCurrency,
          this.simpleCalc.toCurrency
        );
        
        if (response.data.status === 'success') {
          const data = response.data.data;
          this.simpleCalc.result = data.converted_amount;
          this.simpleCalc.rateInfo = {
            rate: data.rate,
            direction: data.direction,
            confidence: data.confidence,
            date: data.date,
            calculation_path: data.calculation_path
          };
          
          this.addToHistory('simple', 
            `${this.formatAmount(this.simpleCalc.amount, this.simpleCalc.fromCurrency)} → ${this.formatAmount(this.simpleCalc.result, this.simpleCalc.toCurrency)}`,
            `${this.formatAmount(this.simpleCalc.result, this.simpleCalc.toCurrency)}`
          );
        }
      } catch (error) {
        console.error('Conversion failed:', error);
        this.simpleCalc.result = 0;
        this.simpleCalc.rateInfo = null;
      }
    },
    
    swapSimpleCurrencies() {
      const temp = this.simpleCalc.fromCurrency;
      this.simpleCalc.fromCurrency = this.simpleCalc.toCurrency;
      this.simpleCalc.toCurrency = temp;
      
      if (this.simpleCalc.result > 0) {
        const tempAmount = this.simpleCalc.amount;
        this.simpleCalc.amount = this.simpleCalc.result;
        this.simpleCalc.result = tempAmount;
      }
      
      this.performSimpleConversion();
    },
    
    setQuickAmount(amount) {
      this.simpleCalc.amount = amount;
      this.performSimpleConversion();
    },
    
    // Multi-Currency Methods
    addCurrencyLine() {
      this.multiCalc.lines.push({
        id: ++this.nextId,
        amount: 0,
        currency: '',
        convertedAmount: 0,
        rate: null
      });
    },
    
    removeCurrencyLine(index) {
      if (this.multiCalc.lines.length > 1) {
        this.multiCalc.lines.splice(index, 1);
        this.updateMultiCalculations();
      }
    },
    
    async updateMultiCalculations() {
      // Convert each line to base currency
      for (const line of this.multiCalc.lines) {
        if (line.amount && line.currency && line.currency !== this.multiCalc.baseCurrency) {
          try {
            const response = await CurrencyService.convertAmount(
              line.amount,
              line.currency,
              this.multiCalc.baseCurrency
            );
            
            if (response.data.status === 'success') {
              line.convertedAmount = response.data.data.converted_amount;
              line.rate = response.data.data.rate;
            }
          } catch (error) {
            line.convertedAmount = 0;
            line.rate = null;
          }
        } else if (line.currency === this.multiCalc.baseCurrency) {
          line.convertedAmount = line.amount;
          line.rate = 1;
        }
      }
      
      // Perform operation
      this.calculateMultiResult();
    },
    
    calculateMultiResult() {
      const validLines = this.multiCalc.lines.filter(line => 
        line.amount && line.convertedAmount
      );
      
      if (validLines.length === 0) {
        this.multiCalc.result = null;
        return;
      }
      
      const amounts = validLines.map(line => line.convertedAmount);
      let result = 0;
      
      switch (this.multiCalc.operation) {
        case 'sum':
          result = amounts.reduce((sum, amount) => sum + amount, 0);
          break;
        case 'average':
          result = amounts.reduce((sum, amount) => sum + amount, 0) / amounts.length;
          break;
        case 'max':
          result = Math.max(...amounts);
          break;
        case 'min':
          result = Math.min(...amounts);
          break;
        case 'multiply':
          result = amounts.reduce((product, amount) => product * amount, 1);
          break;
        case 'divide':
          result = amounts.reduce((quotient, amount) => quotient / amount);
          break;
      }
      
      this.multiCalc.result = {
        amount: result,
        breakdown: validLines.map(line => ({
          currency: line.currency,
          originalAmount: line.amount,
          convertedAmount: line.convertedAmount
        }))
      };
      
      this.addToHistory('multi', 
        `${this.operationLabels[this.multiCalc.operation]} (${validLines.length} currencies)`,
        `${this.formatAmount(result, this.multiCalc.baseCurrency)}`
      );
    },
    
    // Historical Analysis Methods
    async loadHistoricalData() {
      if (!this.historical.fromCurrency || !this.historical.toCurrency || 
          !this.historical.startDate || !this.historical.endDate) {
        return;
      }
      
      this.historical.loading = true;
      
      try {
        const response = await CurrencyService.getHistoricalRates(
          this.historical.fromCurrency,
          this.historical.toCurrency,
          this.historical.startDate,
          this.historical.endDate
        );
        
        if (response.data.status === 'success') {
          this.historical.data = response.data.data;
          this.calculateHistoricalStats();
          this.renderHistoricalChart();
        }
      } catch (error) {
        console.error('Failed to load historical data:', error);
        this.historical.data = [];
      } finally {
        this.historical.loading = false;
      }
    },
    
    setHistoricalPeriod(period) {
      const endDate = new Date();
      const startDate = new Date(endDate.getTime() - (period.days * 24 * 60 * 60 * 1000));
      
      this.historical.endDate = endDate.toISOString().split('T')[0];
      this.historical.startDate = startDate.toISOString().split('T')[0];
      this.historical.selectedPeriod = period.key;
      
      this.loadHistoricalData();
    },
    
    calculateHistoricalStats() {
      if (this.historical.data.length === 0) return;
      
      const rates = this.historical.data.map(d => d.rate);
      const firstRate = rates[0];
      const lastRate = rates[rates.length - 1];
      
      this.historical.stats = {
        current: lastRate,
        average: rates.reduce((sum, rate) => sum + rate, 0) / rates.length,
        highest: Math.max(...rates),
        lowest: Math.min(...rates),
        highestDate: this.historical.data.find(d => d.rate === Math.max(...rates)).date,
        lowestDate: this.historical.data.find(d => d.rate === Math.min(...rates)).date,
        volatility: this.calculateVolatility(rates),
        periodChange: ((lastRate - firstRate) / firstRate) * 100
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
      
      return Math.sqrt(variance) * Math.sqrt(252) * 100; // Annualized volatility
    },
    
    renderHistoricalChart() {
      if (!this.$refs.historicalChart) return;
      
      if (this.historicalChart) {
        this.historicalChart.destroy();
      }
      
      const ctx = this.$refs.historicalChart.getContext('2d');
      
      this.historicalChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: this.historical.data.map(d => this.formatDate(d.date)),
          datasets: [{
            label: `${this.historical.fromCurrency}/${this.historical.toCurrency}`,
            data: this.historical.data.map(d => d.rate),
            borderColor: '#3498db',
            backgroundColor: 'rgba(52, 152, 219, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.1
          }]
        },
        options: {
          responsive: true,
          plugins: {
            title: {
              display: true,
              text: `${this.historical.fromCurrency} to ${this.historical.toCurrency} Exchange Rate`
            },
            legend: {
              display: false
            }
          },
          scales: {
            y: {
              beginAtZero: false,
              title: {
                display: true,
                text: 'Exchange Rate'
              }
            },
            x: {
              title: {
                display: true,
                text: 'Date'
              }
            }
          }
        }
      });
    },
    
    // Bulk Calculator Methods
    addBulkRow() {
      this.bulk.rows.push({
        id: ++this.nextId,
        amount: null,
        fromCurrency: '',
        toCurrency: '',
        rate: null,
        convertedAmount: null,
        rateType: null,
        status: 'pending'
      });
    },
    
    removeBulkRow(index) {
      this.bulk.rows.splice(index, 1);
    },
    
    duplicateBulkRow(row) {
      const newRow = {
        ...row,
        id: ++this.nextId,
        status: 'pending'
      };
      this.bulk.rows.push(newRow);
      
      if (this.bulk.autoCalculate) {
        this.calculateBulkRow(newRow);
      }
    },
    
    async calculateBulkRow(row) {
      if (!row.amount || !row.fromCurrency || !row.toCurrency) {
        row.status = 'pending';
        return;
      }
      
      row.status = 'loading';
      
      try {
        const date = this.bulk.useFixedDate ? this.bulk.fixedDate : null;
        const response = await CurrencyService.convertAmount(
          row.amount,
          row.fromCurrency,
          row.toCurrency,
          date
        );
        
        if (response.data.status === 'success') {
          const data = response.data.data;
          row.rate = data.rate;
          row.convertedAmount = data.converted_amount;
          row.rateType = data.direction;
          row.status = 'success';
        } else {
          row.status = 'error';
        }
      } catch (error) {
        row.status = 'error';
        row.rate = null;
        row.convertedAmount = null;
        row.rateType = null;
      }
    },
    
    async recalculateBulk() {
      for (const row of this.bulk.rows) {
        await this.calculateBulkRow(row);
      }
    },
    
    clearBulkData() {
      this.bulk.rows = [{
        id: ++this.nextId,
        amount: null,
        fromCurrency: '',
        toCurrency: '',
        rate: null,
        convertedAmount: null,
        rateType: null,
        status: 'pending'
      }];
    },
    
    importBulkData() {
      // Implementation for CSV import
      const input = document.createElement('input');
      input.type = 'file';
      input.accept = '.csv';
      input.onchange = this.handleBulkImport;
      input.click();
    },
    
    handleBulkImport(event) {
      const file = event.target.files[0];
      if (!file) return;
      
      const reader = new FileReader();
      reader.onload = (e) => {
        const csv = e.target.result;
        const lines = csv.split('\n').slice(1); // Skip header
        
        this.bulk.rows = lines.filter(line => line.trim()).map((line, index) => {
          const [amount, fromCurrency, toCurrency] = line.split(',');
          return {
            id: ++this.nextId,
            amount: parseFloat(amount),
            fromCurrency: fromCurrency?.trim(),
            toCurrency: toCurrency?.trim(),
            rate: null,
            convertedAmount: null,
            rateType: null,
            status: 'pending'
          };
        });
        
        if (this.bulk.autoCalculate) {
          this.recalculateBulk();
        }
      };
      reader.readAsText(file);
    },
    
    exportBulkResults() {
      const csvContent = 'Amount,From Currency,To Currency,Exchange Rate,Converted Amount,Rate Type,Status\n' +
        this.bulk.rows.map(row => 
          `${row.amount || ''},${row.fromCurrency},${row.toCurrency},${row.rate || ''},${row.convertedAmount || ''},${row.rateType || ''},${row.status}`
        ).join('\n');
      
      const blob = new Blob([csvContent], { type: 'text/csv' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `bulk_currency_calculations_${new Date().toISOString().split('T')[0]}.csv`;
      a.click();
      URL.revokeObjectURL(url);
    },
    
    // Scenario Planning Methods
    async generateScenarios() {
      if (!this.scenario.baseAmount || !this.scenario.fromCurrency || !this.scenario.toCurrency) {
        return;
      }
      
      try {
        // Get current rate
        const response = await CurrencyService.getBidirectionalRate(
          this.scenario.fromCurrency,
          this.scenario.toCurrency
        );
        
        if (response.data.status !== 'success') return;
        
        const baseRate = response.data.data.rate;
        const baseResult = this.scenario.baseAmount * baseRate;
        
        this.scenario.results = [];
        
        // Add base scenario
        this.scenario.results.push({
          isBase: true,
          rateChange: 0,
          rate: baseRate,
          convertedAmount: baseResult,
          difference: 0,
          impact: 0,
          impactPercent: 0
        });
        
        // Generate variations
        const step = (this.scenario.maxChange - this.scenario.minChange) / (this.scenario.scenarioCount - 1);
        
        for (let i = 0; i < this.scenario.scenarioCount; i++) {
          const changePercent = this.scenario.minChange + (i * step);
          if (changePercent === 0) continue; // Skip base scenario
          
          const newRate = baseRate * (1 + changePercent / 100);
          const convertedAmount = this.scenario.baseAmount * newRate;
          const difference = convertedAmount - baseResult;
          const impact = difference;
          const impactPercent = (difference / baseResult) * 100;
          
          this.scenario.results.push({
            isBase: false,
            rateChange: changePercent,
            rate: newRate,
            convertedAmount,
            difference,
            impact,
            impactPercent
          });
        }
        
        // Sort by rate change
        this.scenario.results.sort((a, b) => {
          if (a.isBase) return -1;
          if (b.isBase) return 1;
          return a.rateChange - b.rateChange;
        });
        
        this.calculateScenarioSummary();
        
        this.addToHistory('scenario', 
          `${this.scenario.scenarioCount} scenarios for ${this.formatAmount(this.scenario.baseAmount, this.scenario.fromCurrency)}`,
          `Range: ${this.formatAmount(this.scenario.summary.worstCase.amount, this.scenario.toCurrency)} - ${this.formatAmount(this.scenario.summary.bestCase.amount, this.scenario.toCurrency)}`
        );
        
      } catch (error) {
        console.error('Failed to generate scenarios:', error);
      }
    },
    
    calculateScenarioSummary() {
      const nonBaseResults = this.scenario.results.filter(r => !r.isBase);
      
      if (nonBaseResults.length === 0) return;
      
      const amounts = nonBaseResults.map(r => r.convertedAmount);
      const changes = nonBaseResults.map(r => r.impactPercent);
      
      const bestCase = nonBaseResults.reduce((best, current) => 
        current.convertedAmount > best.convertedAmount ? current : best
      );
      
      const worstCase = nonBaseResults.reduce((worst, current) => 
        current.convertedAmount < worst.convertedAmount ? current : worst
      );
      
      this.scenario.summary = {
        bestCase: {
          amount: bestCase.convertedAmount,
          change: bestCase.impactPercent
        },
        worstCase: {
          amount: worstCase.convertedAmount,
          change: worstCase.impactPercent
        },
        average: {
          amount: amounts.reduce((sum, amount) => sum + amount, 0) / amounts.length,
          change: changes.reduce((sum, change) => sum + change, 0) / changes.length
        },
        volatility: {
          range: Math.max(...amounts) - Math.min(...amounts),
          percent: Math.max(...changes) - Math.min(...changes)
        }
      };
    },
    
    // History Management
    addToHistory(type, description, result) {
      this.calculationHistory.unshift({
        id: ++this.nextId,
        type,
        description,
        result,
        timestamp: new Date().toISOString(),
        data: this.getCurrentCalculationData(type)
      });
      
      // Keep only last 50 calculations
      if (this.calculationHistory.length > 50) {
        this.calculationHistory = this.calculationHistory.slice(0, 50);
      }
      
      this.saveHistory();
    },
    
    getCurrentCalculationData(type) {
      switch (type) {
        case 'simple':
          return { ...this.simpleCalc };
        case 'multi':
          return { ...this.multiCalc };
        case 'historical':
          return { ...this.historical };
        case 'bulk':
          return { ...this.bulk };
        case 'scenario':
          return { ...this.scenario };
        default:
          return {};
      }
    },
    
    loadCalculation(calc) {
      this.activeTab = calc.type;
      
      switch (calc.type) {
        case 'simple':
          this.simpleCalc = { ...calc.data };
          break;
        case 'multi':
          this.multiCalc = { ...calc.data };
          break;
        case 'historical':
          this.historical = { ...calc.data };
          break;
        case 'bulk':
          this.bulk = { ...calc.data };
          break;
        case 'scenario':
          this.scenario = { ...calc.data };
          break;
      }
      
      this.showHistory = false;
    },
    
    toggleHistory() {
      this.showHistory = !this.showHistory;
    },
    
    saveHistory() {
      localStorage.setItem('currency_calculator_history', JSON.stringify(this.calculationHistory));
    },
    
    loadSavedHistory() {
      const saved = localStorage.getItem('currency_calculator_history');
      if (saved) {
        this.calculationHistory = JSON.parse(saved);
      }
    },
    
    // Action Methods
    saveCalculation() {
      // Implementation for saving current calculation
      const calculation = {
        id: ++this.nextId,
        name: `Calculation ${new Date().toLocaleString()}`,
        type: this.activeTab,
        data: this.getCurrentCalculationData(this.activeTab),
        timestamp: new Date().toISOString()
      };
      
      const saved = JSON.parse(localStorage.getItem('saved_calculations') || '[]');
      saved.unshift(calculation);
      localStorage.setItem('saved_calculations', JSON.stringify(saved));
      
      // Show success message
      this.$emit('show-toast', 'Calculation saved successfully', 'success');
    },
    
    exportResults() {
      const data = this.getCurrentCalculationData(this.activeTab);
      const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `currency_calculation_${this.activeTab}_${new Date().toISOString().split('T')[0]}.json`;
      a.click();
      URL.revokeObjectURL(url);
    },
    
    clearAll() {
      if (confirm('Are you sure you want to clear all calculations?')) {
        // Reset all calculations
        this.simpleCalc = {
          amount: 1000,
          fromCurrency: 'USD',
          toCurrency: 'EUR',
          result: 0,
          rateInfo: null
        };
        
        this.multiCalc = {
          baseCurrency: 'USD',
          operation: 'sum',
          lines: [
            { id: 1, amount: 1000, currency: 'USD', convertedAmount: 0, rate: null },
            { id: 2, amount: 850, currency: 'EUR', convertedAmount: 0, rate: null }
          ],
          result: null
        };
        
        this.historical.data = [];
        this.historical.stats = {};
        
        this.clearBulkData();
        
        this.scenario.results = [];
        this.scenario.summary = {};
        
        this.calculationHistory = [];
        this.saveHistory();
      }
    },
    
    // Utility Methods
    formatAmount(amount, currency) {
      return CurrencyUtils.formatCurrency(amount, currency);
    },
    
    formatRate(rate) {
      return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 4,
        maximumFractionDigits: 6
      }).format(rate);
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
    
    formatDateTime(dateString) {
      if (!dateString) return '';
      return new Date(dateString).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    }
  }
};
</script>

<style scoped>
/* Base Styles */
.advanced-calculator {
  max-width: 1400px;
  margin: 0 auto;
  padding: 24px;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  position: relative;
}

/* Header */
.calculator-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 32px;
  padding-bottom: 24px;
  border-bottom: 2px solid #ecf0f1;
}

.header-title h1 {
  color: #2c3e50;
  font-size: 32px;
  font-weight: 600;
  margin-bottom: 8px;
}

.header-title h1 i {
  color: #3498db;
  margin-right: 12px;
}

.subtitle {
  color: #7f8c8d;
  font-size: 16px;
  margin: 0;
  max-width: 600px;
}

.header-actions {
  display: flex;
  gap: 12px;
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

/* Tab Navigation */
.tab-navigation {
  display: flex;
  background: #f8f9fa;
  border-radius: 8px;
  padding: 4px;
  margin-bottom: 24px;
  overflow-x: auto;
}

.tab-btn {
  flex: 1;
  min-width: 160px;
  padding: 12px 16px;
  background: transparent;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 500;
  color: #7f8c8d;
  position: relative;
}

.tab-btn:hover {
  background: rgba(52, 152, 219, 0.1);
  color: #3498db;
}

.tab-btn.active {
  background: #3498db;
  color: white;
  box-shadow: 0 2px 4px rgba(52, 152, 219, 0.3);
}

.tab-badge {
  background: rgba(255, 255, 255, 0.3);
  padding: 2px 6px;
  border-radius: 10px;
  font-size: 10px;
  margin-left: 4px;
}

/* Tab Content */
.tab-content {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  overflow: hidden;
}

.tab-pane {
  padding: 24px;
}

/* Simple Converter Styles */
.converter-section {
  max-width: 800px;
  margin: 0 auto;
}

.conversion-row {
  display: grid;
  grid-template-columns: 2fr auto 2fr auto 2fr;
  gap: 16px;
  align-items: end;
  margin-bottom: 24px;
}

.amount-input-group,
.currency-select-group,
.result-display-group {
  display: flex;
  flex-direction: column;
}

.amount-input-group label,
.currency-select-group label,
.result-display-group label {
  font-weight: 500;
  color: #2c3e50;
  margin-bottom: 6px;
  font-size: 14px;
}

.amount-input,
.currency-select,
.result-input {
  padding: 12px 16px;
  border: 2px solid #ecf0f1;
  border-radius: 6px;
  font-size: 16px;
  transition: all 0.3s ease;
}

.amount-input:focus,
.currency-select:focus {
  outline: none;
  border-color: #3498db;
  box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.result-input {
  background: #f8f9fa;
  font-weight: 600;
  color: #2ecc71;
}

.swap-button {
  display: flex;
  justify-content: center;
  align-items: center;
}

.swap-btn {
  width: 48px;
  height: 48px;
  border: 2px solid #3498db;
  background: white;
  border-radius: 50%;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  color: #3498db;
}

.swap-btn:hover {
  background: #3498db;
  color: white;
  transform: rotate(180deg);
}

/* Rate Information */
.rate-information {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 24px;
}

.rate-display {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 8px;
  flex-wrap: wrap;
}

.rate-equation {
  font-size: 16px;
  font-weight: 600;
  color: #2c3e50;
}

.rate-direction,
.rate-confidence {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.direction-direct { background: #d5f4e6; color: #27ae60; }
.direction-inverse { background: #fef9e7; color: #f39c12; }
.direction-cross { background: #f4ecf7; color: #9b59b6; }

.confidence-high { background: #d5f4e6; color: #27ae60; }
.confidence-medium { background: #fef9e7; color: #f39c12; }
.confidence-low { background: #ffeaea; color: #e74c3c; }

.rate-metadata {
  display: flex;
  gap: 16px;
  font-size: 13px;
  color: #7f8c8d;
}

/* Quick Amounts */
.quick-amounts h4 {
  color: #2c3e50;
  margin-bottom: 12px;
}

.amount-buttons {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.amount-btn {
  padding: 8px 12px;
  background: #ecf0f1;
  border: 1px solid #bdc3c7;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 13px;
}

.amount-btn:hover {
  background: #3498db;
  color: white;
  border-color: #3498db;
}

/* Multi-Currency Styles */
.multi-currency-section {
  max-width: 1000px;
  margin: 0 auto;
}

.section-header {
  text-align: center;
  margin-bottom: 24px;
}

.section-header h3 {
  color: #2c3e50;
  margin-bottom: 8px;
}

.section-header p {
  color: #7f8c8d;
  margin: 0;
}

.multi-calc-controls {
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  gap: 16px;
  align-items: end;
  margin-bottom: 24px;
  padding: 20px;
  background: #f8f9fa;
  border-radius: 8px;
}

.base-currency-selector label,
.operation-selector label {
  display: block;
  font-weight: 500;
  color: #2c3e50;
  margin-bottom: 6px;
  font-size: 14px;
}

.currency-lines {
  margin-bottom: 24px;
}

.currency-line {
  display: grid;
  grid-template-columns: auto 1fr 1fr 2fr auto;
  gap: 16px;
  align-items: center;
  padding: 16px;
  background: white;
  border: 1px solid #ecf0f1;
  border-radius: 6px;
  margin-bottom: 8px;
}

.line-number {
  width: 32px;
  height: 32px;
  background: #3498db;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 14px;
}

.line-converted {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.converted-amount {
  font-weight: 600;
  color: #2ecc71;
  font-size: 16px;
}

.conversion-rate {
  font-size: 12px;
  color: #7f8c8d;
}

.remove-btn {
  padding: 8px;
  background: #e74c3c;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.remove-btn:hover:not(:disabled) {
  background: #c0392b;
}

.remove-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Multi-Currency Result */
.multi-result {
  background: linear-gradient(135deg, #f8f9fa 0%, #e8f5e8 100%);
  border-radius: 8px;
  padding: 24px;
  border: 2px solid #2ecc71;
}

.result-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.result-header h4 {
  color: #2c3e50;
  margin: 0;
}

.operation-label {
  background: #2ecc71;
  color: white;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
}

.primary-result {
  text-align: center;
  margin-bottom: 20px;
}

.result-amount {
  font-size: 36px;
  font-weight: 700;
  color: #2ecc71;
  margin-right: 8px;
}

.result-currency {
  font-size: 18px;
  color: #2c3e50;
  font-weight: 600;
}

.result-breakdown {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 8px;
}

.breakdown-item {
  display: flex;
  justify-content: space-between;
  padding: 8px 12px;
  background: white;
  border-radius: 4px;
  font-size: 13px;
}

.breakdown-currency {
  font-weight: 600;
  color: #2c3e50;
}

.breakdown-converted {
  color: #7f8c8d;
}

/* Historical Styles */
.historical-controls {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  margin-bottom: 24px;
  padding: 20px;
  background: #f8f9fa;
  border-radius: 8px;
}

.currency-pair-selector,
.date-range-selector {
  display: flex;
  gap: 12px;
}

.currency-group,
.date-group {
  flex: 1;
}

.currency-group label,
.date-group label {
  display: block;
  font-weight: 500;
  color: #2c3e50;
  margin-bottom: 6px;
  font-size: 14px;
}

.date-input {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.period-quick-select {
  grid-column: 1 / -1;
  display: flex;
  gap: 8px;
  justify-content: center;
  margin-top: 16px;
}

.period-btn {
  padding: 8px 16px;
  background: white;
  border: 1px solid #ddd;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 13px;
}

.period-btn:hover {
  background: #f8f9fa;
}

.period-btn.active {
  background: #3498db;
  color: white;
  border-color: #3498db;
}

.chart-container {
  margin-bottom: 24px;
  padding: 20px;
  background: white;
  border-radius: 8px;
  border: 1px solid #ecf0f1;
}

.historical-stats {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 20px;
}

.stat-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}

.stat-item {
  background: white;
  padding: 16px;
  border-radius: 6px;
  text-align: center;
}

.stat-label {
  font-size: 12px;
  color: #7f8c8d;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
}

.stat-value {
  font-size: 20px;
  font-weight: 700;
  color: #2c3e50;
  margin-bottom: 4px;
}

.stat-value.positive {
  color: #2ecc71;
}

.stat-value.negative {
  color: #e74c3c;
}

.stat-date {
  font-size: 11px;
  color: #95a5a6;
}

/* Bulk Calculator Styles */
.bulk-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  padding: 20px;
  background: #f8f9fa;
  border-radius: 8px;
}

.bulk-actions {
  display: flex;
  gap: 8px;
}

.bulk-settings {
  display: flex;
  gap: 16px;
  align-items: center;
}

.setting-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.setting-group label {
  font-size: 14px;
  color: #2c3e50;
  cursor: pointer;
}

.bulk-table-container {
  overflow-x: auto;
  border-radius: 8px;
  border: 1px solid #ecf0f1;
  margin-bottom: 24px;
}

.bulk-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
}

.bulk-table th {
  background: #f8f9fa;
  padding: 12px 8px;
  text-align: left;
  font-weight: 600;
  color: #2c3e50;
  border-bottom: 2px solid #ecf0f1;
  font-size: 13px;
  white-space: nowrap;
}

.bulk-table td {
  padding: 8px;
  border-bottom: 1px solid #f1f3f4;
  vertical-align: middle;
}

.bulk-row.success {
  background: rgba(46, 204, 113, 0.05);
}

.bulk-row.error {
  background: rgba(231, 76, 60, 0.05);
}

.bulk-row.loading {
  background: rgba(52, 152, 219, 0.05);
}

.table-input,
.table-select {
  width: 100%;
  padding: 6px 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 13px;
}

.rate-cell,
.result-cell {
  text-align: right;
  font-weight: 500;
  min-width: 120px;
}

.no-rate,
.no-result {
  color: #bdc3c7;
}

.rate-type-badge {
  padding: 2px 6px;
  border-radius: 8px;
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
}

.type-direct { background: #d5f4e6; color: #27ae60; }
.type-inverse { background: #fef9e7; color: #f39c12; }
.type-cross { background: #f4ecf7; color: #9b59b6; }

.status-indicator {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 50%;
}

.status-success { background: #d5f4e6; color: #27ae60; }
.status-error { background: #ffeaea; color: #e74c3c; }
.status-loading { background: #e3f2fd; color: #3498db; }
.status-pending { background: #f8f9fa; color: #bdc3c7; }

.actions-cell {
  display: flex;
  gap: 4px;
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

.action-btn.remove:hover {
  background: #e74c3c;
  color: white;
  border-color: #e74c3c;
}

/* Bulk Summary */
.bulk-summary {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 20px;
}

.summary-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 16px;
}

.summary-item {
  background: white;
  padding: 16px;
  border-radius: 6px;
  text-align: center;
}

.summary-label {
  font-size: 12px;
  color: #7f8c8d;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
}

.summary-value {
  font-size: 18px;
  font-weight: 700;
  color: #2c3e50;
}

.summary-value.success {
  color: #2ecc71;
}

.summary-value.error {
  color: #e74c3c;
}

/* Scenario Planning Styles */
.scenario-setup {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  margin-bottom: 24px;
  padding: 20px;
  background: #f8f9fa;
  border-radius: 8px;
}

.base-scenario h4,
.scenario-variations h4 {
  color: #2c3e50;
  margin-bottom: 16px;
}

.scenario-inputs {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

.input-group {
  display: flex;
  flex-direction: column;
}

.input-group label {
  font-weight: 500;
  color: #2c3e50;
  margin-bottom: 6px;
  font-size: 14px;
}

.variation-controls {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.variation-input {
  display: flex;
  flex-direction: column;
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

.count-input {
  width: 100px;
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

/* Scenario Results */
.results-table-container {
  overflow-x: auto;
  border-radius: 8px;
  border: 1px solid #ecf0f1;
  margin-bottom: 24px;
}

.scenario-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
}

.scenario-table th {
  background: #f8f9fa;
  padding: 12px 16px;
  text-align: left;
  font-weight: 600;
  color: #2c3e50;
  border-bottom: 2px solid #ecf0f1;
  font-size: 14px;
}

.scenario-table td {
  padding: 12px 16px;
  border-bottom: 1px solid #f1f3f4;
}

.scenario-row.base-scenario {
  background: rgba(52, 152, 219, 0.1);
  font-weight: 600;
}

.scenario-row.positive-impact {
  background: rgba(46, 204, 113, 0.05);
}

.scenario-row.negative-impact {
  background: rgba(231, 76, 60, 0.05);
}

.scenario-label {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.scenario-label.base {
  background: #3498db;
  color: white;
}

.change-cell .positive {
  color: #2ecc71;
  font-weight: 600;
}

.change-cell .negative {
  color: #e74c3c;
  font-weight: 600;
}

.difference-cell.positive {
  color: #2ecc71;
  font-weight: 600;
}

.difference-cell.negative {
  color: #e74c3c;
  font-weight: 600;
}

.impact-cell {
  position: relative;
  min-width: 120px;
}

.impact-bar {
  position: absolute;
  top: 50%;
  left: 0;
  height: 4px;
  transform: translateY(-50%);
  border-radius: 2px;
  max-width: 60px;
}

.impact-bar.positive {
  background: #2ecc71;
}

.impact-bar.negative {
  background: #e74c3c;
}

.impact-text {
  position: relative;
  z-index: 1;
  font-weight: 600;
  font-size: 13px;
}

.impact-text.positive {
  color: #2ecc71;
}

.impact-text.negative {
  color: #e74c3c;
}

/* Scenario Summary Cards */
.scenario-summary {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 20px;
}

.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}

.summary-card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  text-align: center;
  border: 2px solid transparent;
}

.summary-card.best {
  border-color: #2ecc71;
}

.summary-card.worst {
  border-color: #e74c3c;
}

.summary-card.average {
  border-color: #3498db;
}

.summary-card.volatility {
  border-color: #f39c12;
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-bottom: 12px;
  font-weight: 600;
  color: #2c3e50;
}

.card-amount {
  font-size: 20px;
  font-weight: 700;
  color: #2c3e50;
  margin-bottom: 4px;
}

.card-change {
  font-size: 14px;
  font-weight: 500;
}

.summary-card.best .card-change {
  color: #2ecc71;
}

.summary-card.worst .card-change {
  color: #e74c3c;
}

.summary-card.average .card-change {
  color: #3498db;
}

.summary-card.volatility .card-change {
  color: #f39c12;
}

/* Loading and Empty States */
.loading-state,
.empty-state {
  text-align: center;
  padding: 48px 24px;
  color: #7f8c8d;
}

.loading-state i,
.empty-state i {
  font-size: 48px;
  margin-bottom: 16px;
  color: #bdc3c7;
}

.loading-state i {
  color: #3498db;
}

/* History Sidebar */
.history-sidebar {
  position: fixed;
  top: 0;
  right: -400px;
  width: 400px;
  height: 100vh;
  background: white;
  box-shadow: -4px 0 20px rgba(0, 0, 0, 0.1);
  transition: right 0.3s ease;
  z-index: 1000;
  overflow-y: auto;
}

.history-sidebar.open {
  right: 0;
}

.sidebar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #ecf0f1;
  background: #f8f9fa;
}

.sidebar-header h3 {
  margin: 0;
  color: #2c3e50;
}

.close-sidebar {
  background: none;
  border: none;
  font-size: 16px;
  color: #7f8c8d;
  cursor: pointer;
  padding: 8px;
  border-radius: 4px;
}

.close-sidebar:hover {
  background: #ecf0f1;
  color: #2c3e50;
}

.sidebar-content {
  padding: 16px;
}

.empty-history {
  text-align: center;
  padding: 48px 16px;
  color: #7f8c8d;
}

.empty-history i {
  font-size: 48px;
  margin-bottom: 16px;
  color: #bdc3c7;
}

.history-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.history-item {
  padding: 12px;
  background: #f8f9fa;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s ease;
  border: 1px solid transparent;
}

.history-item:hover {
  background: #e3f2fd;
  border-color: #3498db;
}

.history-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 6px;
}

.history-type {
  background: #3498db;
  color: white;
  padding: 2px 6px;
  border-radius: 8px;
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
}

.history-date {
  font-size: 11px;
  color: #7f8c8d;
}

.history-details {
  font-size: 13px;
  color: #2c3e50;
  margin-bottom: 4px;
}

.history-result {
  font-size: 12px;
  color: #2ecc71;
  font-weight: 600;
}

/* History Toggle Button */
.history-toggle {
  position: fixed;
  bottom: 24px;
  right: 24px;
  background: #3498db;
  color: white;
  border: none;
  border-radius: 50px;
  padding: 12px 20px;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 500;
  z-index: 999;
}

.history-toggle:hover {
  background: #2980b9;
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(52, 152, 219, 0.4);
}

.history-toggle.active {
  background: #2ecc71;
}

.history-count {
  background: rgba(255, 255, 255, 0.3);
  padding: 2px 6px;
  border-radius: 10px;
  font-size: 10px;
  margin-left: 4px;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .conversion-row {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  
  .swap-button {
    order: 3;
  }
  
  .multi-calc-controls {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  
  .historical-controls {
    grid-template-columns: 1fr;
    gap: 16px;
  }
  
  .scenario-setup {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .advanced-calculator {
    padding: 16px;
  }
  
  .calculator-header {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
  }
  
  .header-actions {
    justify-content: stretch;
  }
  
  .tab-navigation {
    flex-wrap: wrap;
  }
  
  .tab-btn {
    min-width: 120px;
  }
  
  .currency-pair-selector,
  .date-range-selector {
    flex-direction: column;
    gap: 8px;
  }
  
  .period-quick-select {
    flex-wrap: wrap;
  }
  
  .bulk-controls {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
  }
  
  .bulk-actions {
    justify-content: stretch;
  }
  
  .scenario-inputs {
    grid-template-columns: 1fr;
  }
  
  .variation-controls {
    gap: 12px;
  }
  
  .range-inputs {
    flex-direction: column;
    gap: 8px;
  }
  
  .history-sidebar {
    width: 100%;
    right: -100%;
  }
  
  .summary-cards {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 480px) {
  .calculator-header h1 {
    font-size: 24px;
  }
  
  .tab-btn {
    padding: 8px 12px;
    font-size: 12px;
    min-width: 100px;
  }
  
  .tab-pane {
    padding: 16px;
  }
  
  .currency-line {
    grid-template-columns: 1fr;
    gap: 8px;
    text-align: center;
  }
  
  .line-number {
    justify-self: center;
  }
  
  .bulk-table th,
  .bulk-table td {
    padding: 4px 6px;
    font-size: 12px;
  }
  
  .scenario-table th,
  .scenario-table td {
    padding: 8px 6px;
    font-size: 12px;
  }
  
  .summary-cards {
    grid-template-columns: 1fr;
  }
  
  .history-toggle {
    bottom: 16px;
    right: 16px;
    padding: 10px 16px;
  }
}
</style>