<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <!-- Toast Notifications -->
    <div class="toast-container">
      <div v-for="toast in toasts" :key="toast.id" class="toast show">
        <div class="toast-header">
          <i :class="getToastIcon(toast.type)" :style="{ color: getToastColor(toast.type) }"></i>
          <strong class="me-auto">{{ getToastTitle(toast.type) }}</strong>
          <button type="button" class="btn btn-sm" @click="removeToast(toast.id)">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="toast-body">{{ toast.message }}</div>
      </div>
    </div>

    <!-- Page Header -->
    <div class="page-header">
      <h1 class="h2 mb-2">Item Price Management</h1>
      <p class="text-muted mb-0">Manage item pricing and compare rates across vendors and customers</p>
    </div>

    <!-- Tab Navigation -->
    <div class="nav-tabs-enhanced mb-4">
      <button
        :class="['nav-link', { active: activeTab === 'list' }]"
        @click="activeTab = 'list'"
      >
        <i class="fas fa-search me-2"></i>
        Price Management
      </button>
      <button
        :class="['nav-link', { active: activeTab === 'comparison' }]"
        @click="activeTab = 'comparison'"
      >
        <i class="fas fa-balance-scale me-2"></i>
        Price Comparison
      </button>
    </div>

    <!-- Item Price List Tab -->
    <div v-if="activeTab === 'list'" class="tab-content">
      <!-- Item Selection -->
      <div class="card mb-4">
        <div class="card-body">
          <div class="d-flex align-items-center gap-3">
            <label class="fw-semibold mb-0" style="min-width: fit-content;">Select Item</label>
            <div class="item-search-container flex-1" style="position: relative; flex: 1;">
              <div class="input-group">
                <span class="input-group-text">
                  <i class="fas fa-search"></i>
                </span>
                <input
                  type="text"
                  class="form-control"
                  v-model="itemSearch"
                  @focus="showItemDropdown = true"
                  @blur="hideItemDropdown"
                  @input="showItemDropdown = true"
                  placeholder="Search by item code or name"
                />
                <button
                  v-if="itemSearch"
                  type="button"
                  class="search-clear-btn"
                  @click="clearItemSearch"
                >
                  <i class="fas fa-times"></i>
                </button>
              </div>
              <div v-if="showItemDropdown" class="dropdown-menu show">
                <div v-if="!items.length" class="dropdown-item text-muted">
                  <i class="fas fa-spinner fa-spin me-2"></i>Loading items...
                </div>
                <div v-else-if="filteredItems.length === 0 && itemSearch.length > 0" class="dropdown-item text-muted">
                  <i class="fas fa-search me-2"></i>No items found for "{{ itemSearch }}"
                </div>
                <div v-else-if="filteredItems.length === 0" class="dropdown-item text-muted">
                  <i class="fas fa-info-circle me-2"></i>Start typing to search for items...
                </div>
                <div
                  v-for="item in filteredItems"
                  :key="item.item_id"
                  @mousedown="selectItem(item)"
                  class="dropdown-item"
                >
                  <div class="item-info">
                    <strong>{{ item.name }}</strong>
                    <span class="item-code">{{ item.item_code }}</span>
                  </div>
                  <div class="item-details">
                    <span class="category">{{ item.category?.name || 'No Category' }}</span>
                    <span class="stock">Stock: {{ formatNumber(item.current_stock || 0) }}</span>
                  </div>
                </div>
              </div>
            </div>
            <button
              class="btn btn-primary"
              @click="openAddPriceModal"
              :disabled="!selectedItemId || isLoading"
            >
              <i class="fas fa-plus me-2"></i>
              Add New Price
            </button>
          </div>
        </div>
      </div>

      <!-- Price Filters -->
      <div v-if="selectedItemId" class="card mb-4">
        <div class="card-header">
          <h6 class="mb-0 d-flex align-items-center">
            <i class="fas fa-filter me-2"></i>
            Filter Options
          </h6>
        </div>
        <div class="card-body">
          <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="d-flex align-items-center gap-2">
              <label class="fw-semibold mb-0">Type</label>
              <select
                class="form-select form-select-sm"
                v-model="priceTypeFilter"
                @change="applyFilters"
                style="min-width: 120px;"
              >
                <option value="">All Types</option>
                <option value="purchase">Purchase</option>
                <option value="sale">Sale</option>
              </select>
            </div>

            <div class="d-flex align-items-center gap-2">
              <label class="fw-semibold mb-0">Status</label>
              <select
                class="form-select form-select-sm"
                v-model="activeFilter"
                @change="applyFilters"
                style="min-width: 120px;"
              >
                <option value="">All Status</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>

            <div class="d-flex align-items-center gap-2">
              <label class="fw-semibold mb-0">Currency</label>
              <select
                class="form-select form-select-sm"
                v-model="currencyFilter"
                @change="applyFilters"
                style="min-width: 120px;"
              >
                <option value="">All Currencies</option>
                <option v-for="currency in currencies" :key="currency" :value="currency">
                  {{ currency }}
                </option>
              </select>
            </div>

            <div class="d-flex align-items-center gap-2">
              <input
                type="checkbox"
                v-model="currentOnlyFilter"
                @change="applyFilters"
                id="currentOnlySwitch"
              />
              <label class="fw-semibold mb-0" for="currentOnlySwitch">
                Show only current prices
              </label>
            </div>

            <button class="btn btn-outline-secondary btn-sm ms-auto" @click="clearFilters">
              <i class="fas fa-times me-1"></i>
              Clear Filters
            </button>
          </div>
        </div>
      </div>

      <!-- Price List Table -->
      <div v-if="selectedItemId" class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0 d-flex align-items-center">
            <i class="fas fa-dollar-sign me-2"></i>
            Price List
            <span v-if="filteredPrices.length > 0" class="badge bg-primary ms-2">
              {{ filteredPrices.length }} {{ filteredPrices.length === 1 ? 'price' : 'prices' }}
            </span>
          </h6>
          <div class="text-muted small">
            <i class="fas fa-info-circle me-1"></i>
            Click column headers to sort
          </div>
        </div>
        <div class="card-body p-0">
          <!-- Loading State -->
          <div v-if="isLoading" class="loading-container">
            <div class="text-center py-5">
              <div class="spinner-border text-primary mb-3"></div>
              <p class="text-muted">Loading price data...</p>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else-if="!hasAnyPrices" class="empty-state">
            <div class="text-center py-5">
              <i class="fas fa-dollar-sign fa-4x text-muted mb-3"></i>
              <h4 class="text-muted">No Prices Set</h4>
              <p class="text-muted mb-4">This item doesn't have any prices configured yet.</p>
              <button class="btn btn-primary" @click="openAddPriceModal">
                <i class="fas fa-plus me-2"></i>Add First Price
              </button>
            </div>
          </div>

          <!-- No Results State -->
          <div v-else-if="filteredPrices.length === 0" class="empty-state">
            <div class="text-center py-5">
              <i class="fas fa-search fa-4x text-muted mb-3"></i>
              <h4 class="text-muted">No Results Found</h4>
              <p class="text-muted mb-4">No prices match your current filters.</p>
              <button class="btn btn-outline-secondary" @click="clearFilters">
                <i class="fas fa-times me-2"></i>Clear Filters
              </button>
            </div>
          </div>

          <!-- Price Table -->
          <div v-else class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th class="sortable" @click="sortTable('price_type')">
                    <span class="d-flex align-items-center">
                      Type
                      <i v-if="sortKey === 'price_type'"
                         :class="['fas ms-1', sortOrder === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
                      <i v-else class="fas fa-sort ms-1 text-muted"></i>
                    </span>
                  </th>
                  <th class="sortable" @click="sortTable('price')">
                    <span class="d-flex align-items-center">
                      Price
                      <i v-if="sortKey === 'price'"
                         :class="['fas ms-1', sortOrder === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
                      <i v-else class="fas fa-sort ms-1 text-muted"></i>
                    </span>
                  </th>
                  <th>Currency</th>
                  <th>Min Qty</th>
                  <th>Status</th>
                  <th>Valid Period</th>
                  <th>Vendor/Customer</th>
                  <th width="120">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(price, index) in sortedPrices" :key="price.price_id || index">
                  <td>
                    <span :class="['badge', price.price_type === 'purchase' ? 'bg-info' : 'bg-success']">
                      {{ price.price_type === 'purchase' ? 'Purchase' : 'Sale' }}
                    </span>
                  </td>
                  <td class="fw-semibold">{{ formatPrice(price.price) }}</td>
                  <td>
                    <span class="badge bg-light text-dark">{{ price.currency_code }}</span>
                  </td>
                  <td>{{ formatNumber(price.min_quantity) }}</td>
                  <td>
                    <span :class="['badge', price.is_active ? 'bg-success' : 'bg-secondary']">
                      {{ price.is_active ? 'Active' : 'Inactive' }}
                    </span>
                  </td>
                  <td class="small">
                    <div>From: {{ formatDate(price.start_date) }}</div>
                    <div>To: {{ formatDate(price.end_date) }}</div>
                  </td>
                  <td>
                    <div class="text-truncate" style="max-width: 150px;">
                      {{ getPartnerInfo(price) }}
                    </div>
                  </td>
                  <td>
                    <div class="btn-group btn-group-sm">
                      <button class="btn btn-outline-primary" @click="editPrice(price)">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn btn-outline-danger" @click="confirmDelete(price)">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Price Comparison Tab -->
    <div v-if="activeTab === 'comparison'" class="tab-content">
      <div class="card mb-4">
        <div class="card-header">
          <h6 class="mb-0 d-flex align-items-center">
            <i class="fas fa-balance-scale me-2"></i>
            Price Comparison Settings
          </h6>
        </div>
        <div class="card-body">
          <div class="d-flex align-items-center gap-3 flex-wrap">
            <label class="fw-semibold mb-0">Select Item</label>
            <div class="item-search-container" style="position: relative; min-width: 300px;">
              <div class="input-group">
                <span class="input-group-text">
                  <i class="fas fa-search"></i>
                </span>
                <input
                  type="text"
                  class="form-control"
                  v-model="comparisonItemSearch"
                  @focus="showComparisonDropdown = true"
                  @blur="hideComparisonDropdown"
                  @input="showComparisonDropdown = true"
                  placeholder="Search by item code or name"
                />
                <button
                  v-if="comparisonItemSearch"
                  type="button"
                  class="search-clear-btn"
                  @click="clearComparisonSearch"
                >
                  <i class="fas fa-times"></i>
                </button>
              </div>
              <div v-if="showComparisonDropdown" class="dropdown-menu show">
                <div
                  v-for="item in filteredComparisonItems"
                  :key="item.item_id"
                  @mousedown="selectComparisonItem(item)"
                  class="dropdown-item"
                >
                  <div class="item-info">
                    <strong>{{ item.name }}</strong>
                    <span class="item-code">{{ item.item_code }}</span>
                  </div>
                  <div class="item-details">
                    <span class="category">{{ item.category?.name || 'No Category' }}</span>
                    <span class="stock">Stock: {{ formatNumber(item.current_stock || 0) }}</span>
                  </div>
                </div>
              </div>
            </div>
            <div>
              <label class="fw-semibold">Quantity</label>
              <input
                type="number"
                class="form-control"
                v-model.number="comparisonQuantity"
                @change="loadPriceComparison"
                min="1"
                style="width: 120px;"
              />
            </div>
            <div>
              <label class="fw-semibold">Base Currency</label>
              <select
                class="form-select"
                v-model="comparisonCurrency"
                @change="loadPriceComparison"
                style="width: 120px;"
              >
                <option v-for="currency in currencies" :key="currency" :value="currency">
                  {{ currency }}
                </option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Comparison Results -->
      <div v-if="comparisonItemId">
        <!-- Best Prices Cards -->
        <div class="d-flex gap-3 mb-4">
          <div class="card flex-1" style="border-color: #0ea5e9;">
            <div class="card-header" style="background-color: #0ea5e9; color: white;">
              <h6 class="mb-0 d-flex align-items-center">
                <i class="fas fa-shopping-cart me-2"></i>
                Best Purchase Price
              </h6>
            </div>
            <div class="card-body">
              <div v-if="comparisonLoading" class="text-center py-4">
                <div class="spinner-border" style="color: #0ea5e9;"></div>
                <p class="text-muted mt-3">Finding best purchase price...</p>
              </div>
              <div v-else-if="purchasePrice" class="text-center">
                <div class="display-4 fw-bold mb-2" style="color: #0ea5e9;">
                  {{ formatPrice(purchasePrice.price) }}
                </div>
                <div class="text-muted mb-3">{{ purchasePrice.currency }}</div>
                <div class="row text-start small">
                  <div class="col-6"><strong>Quantity:</strong></div>
                  <div class="col-6">{{ formatNumber(purchasePrice.quantity) }}</div>
                  <div class="col-6"><strong>Vendor:</strong></div>
                  <div class="col-6">{{ getVendorName(purchasePrice.vendor_id) || 'General' }}</div>
                </div>
                <button class="btn btn-outline-primary btn-sm mt-3">
                  <i class="fas fa-eye me-1"></i>View Details
                </button>
              </div>
              <div v-else class="text-center py-4">
                <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                <p class="text-muted">No purchase price available</p>
              </div>
            </div>
          </div>

          <div class="card flex-1" style="border-color: #059669;">
            <div class="card-header" style="background-color: #059669; color: white;">
              <h6 class="mb-0 d-flex align-items-center">
                <i class="fas fa-dollar-sign me-2"></i>
                Best Sale Price
              </h6>
            </div>
            <div class="card-body">
              <div v-if="comparisonLoading" class="text-center py-4">
                <div class="spinner-border" style="color: #059669;"></div>
                <p class="text-muted mt-3">Finding best sale price...</p>
              </div>
              <div v-else-if="salePrice" class="text-center">
                <div class="display-4 fw-bold mb-2" style="color: #059669;">
                  {{ formatPrice(salePrice.price) }}
                </div>
                <div class="text-muted mb-3">{{ salePrice.currency }}</div>
                <div class="row text-start small">
                  <div class="col-6"><strong>Quantity:</strong></div>
                  <div class="col-6">{{ formatNumber(salePrice.quantity) }}</div>
                  <div class="col-6"><strong>Customer:</strong></div>
                  <div class="col-6">{{ getCustomerName(salePrice.customer_id) || 'General' }}</div>
                </div>
                <button class="btn btn-outline-success btn-sm mt-3">
                  <i class="fas fa-eye me-1"></i>View Details
                </button>
              </div>
              <div v-else class="text-center py-4">
                <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                <p class="text-muted">No sale price available</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Multi-currency Comparison -->
        <div v-if="pricesInCurrencies" class="card">
          <div class="card-header">
            <h6 class="mb-0 d-flex align-items-center">
              <i class="fas fa-globe me-2"></i>
              Multi-Currency Price Analysis
            </h6>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-striped mb-0">
                <thead style="background-color: #1f2937; color: white;">
                  <tr>
                    <th>Currency</th>
                    <th class="text-end">Purchase Price</th>
                    <th class="text-end">Sale Price</th>
                    <th class="text-center">Profit Margin</th>
                    <th class="text-center">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(prices, currency) in pricesInCurrencies.prices" :key="currency">
                    <td>
                      <span class="fw-semibold">{{ currency }}</span>
                    </td>
                    <td class="text-end">
                      <span v-if="prices?.purchase_price" class="fw-semibold" style="color: #0ea5e9;">
                        {{ formatPrice(prices.purchase_price) }}
                      </span>
                      <span v-else class="text-muted">-</span>
                    </td>
                    <td class="text-end">
                      <span v-if="prices?.sale_price" class="fw-semibold" style="color: #059669;">
                        {{ formatPrice(prices.sale_price) }}
                      </span>
                      <span v-else class="text-muted">-</span>
                    </td>
                    <td class="text-center">
                      <span v-if="calculateMargin(prices?.purchase_price, prices?.sale_price) !== '-'"
                            :class="['badge', getMarginClass(calculateMargin(prices?.purchase_price, prices?.sale_price))]">
                        {{ calculateMargin(prices?.purchase_price, prices?.sale_price) }}
                      </span>
                      <span v-else class="text-muted">-</span>
                    </td>
                    <td class="text-center">
                      <span v-if="prices?.is_base_currency" class="badge bg-primary">Base Currency</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty state for comparison -->
      <div v-else class="empty-state">
        <div class="text-center py-5">
          <i class="fas fa-balance-scale fa-4x text-muted mb-3"></i>
          <h4 class="text-muted">Price Comparison</h4>
          <p class="text-muted mb-4">Select an item to compare prices across different vendors and customers.</p>
        </div>
      </div>
    </div>

    <!-- Add/Edit Price Modal -->
    <div v-if="showPriceModal" class="modal-overlay" @click.self="closePriceModal">
      <div class="modal-dialog modal-lg" @click.stop>
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i :class="isEditing ? 'fas fa-edit' : 'fas fa-plus'"></i>
              {{ isEditing ? 'Edit Price' : 'Add New Price' }}
            </h5>
            <button type="button" class="modal-close" @click="closePriceModal">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <div class="modal-form-row">
              <div>
                <label class="modal-form-label">
                  Price Type <span class="required-asterisk">*</span>
                </label>
                <select
                  :class="['modal-form-select', { 'is-invalid': formErrors.price_type }]"
                  v-model="priceForm.price_type"
                >
                  <option value="purchase">Purchase Price</option>
                  <option value="sale">Sale Price</option>
                </select>
                <div v-if="formErrors.price_type" class="invalid-feedback">
                  {{ formErrors.price_type }}
                </div>
              </div>

              <div>
                <label class="modal-form-label">
                  Minimum Quantity <span class="required-asterisk">*</span>
                </label>
                <input
                  type="number"
                  :class="['modal-form-control', { 'is-invalid': formErrors.min_quantity }]"
                  v-model.number="priceForm.min_quantity"
                  min="1"
                  placeholder="Enter minimum quantity"
                />
                <div v-if="formErrors.min_quantity" class="invalid-feedback">
                  {{ formErrors.min_quantity }}
                </div>
              </div>
            </div>

            <div class="modal-form-row three">
              <div>
                <label class="modal-form-label">
                  Price <span class="required-asterisk">*</span>
                </label>
                <input
                  type="number"
                  :class="['modal-form-control', { 'is-invalid': formErrors.price }]"
                  v-model.number="priceForm.price"
                  step="0.01"
                  min="0"
                  placeholder="Enter price amount"
                />
                <div v-if="formErrors.price" class="invalid-feedback">
                  {{ formErrors.price }}
                </div>
              </div>

              <div>
                <label class="modal-form-label">
                  Currency <span class="required-asterisk">*</span>
                </label>
                <select
                  :class="['modal-form-select', { 'is-invalid': formErrors.currency_code }]"
                  v-model="priceForm.currency_code"
                >
                  <option v-for="currency in currencies" :key="currency" :value="currency">
                    {{ currency }}
                  </option>
                </select>
                <div v-if="formErrors.currency_code" class="invalid-feedback">
                  {{ formErrors.currency_code }}
                </div>
              </div>
            </div>

            <!-- Vendor Section (only for purchase) -->
            <div v-if="priceForm.price_type === 'purchase'" class="modal-form-row single">
              <div>
                <label class="modal-form-label">Vendor</label>
                <div class="modal-search-container">
                  <input
                    type="text"
                    class="modal-form-control"
                    v-model="vendorSearch"
                    @focus="showVendorDropdown = true"
                    @blur="hideVendorDropdown"
                    @input="showVendorDropdown = true"
                    placeholder="Search for a vendor or leave empty for general price..."
                  />
                  <button
                    v-if="vendorSearch"
                    type="button"
                    class="modal-search-clear"
                    @click="clearVendorSearch"
                  >
                    <i class="fas fa-times"></i>
                  </button>
                  <div v-if="showVendorDropdown" class="modal-dropdown show">
                    <div
                      class="modal-dropdown-item"
                      @mousedown="clearVendorSearch"
                    >
                      <div class="item-info">
                        <strong><i class="fas fa-globe me-2"></i>General Price (All Vendors)</strong>
                      </div>
                      <div class="item-details">
                        <span class="category">Applies to all vendors</span>
                      </div>
                    </div>
                    <div
                      v-for="vendor in filteredVendors"
                      :key="vendor.vendor_id"
                      @mousedown="selectVendor(vendor)"
                      class="modal-dropdown-item"
                    >
                      <div class="item-info">
                        <strong>{{ vendor.name }}</strong>
                        <span class="item-code">{{ vendor.vendor_code }}</span>
                      </div>
                      <div class="item-details">
                        <span class="category">Vendor-specific price</span>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="form-help-text">
                  <i class="fas fa-info-circle"></i>
                  Leave empty for general pricing that applies to all vendors
                </div>
              </div>
            </div>

            <!-- Customer Section (only for sale) -->
            <div v-if="priceForm.price_type === 'sale'" class="modal-form-row single">
              <div>
                <label class="modal-form-label">Customer</label>
                <div class="modal-search-container">
                  <input
                    type="text"
                    class="modal-form-control"
                    v-model="customerSearch"
                    @focus="showCustomerDropdown = true"
                    @blur="hideCustomerDropdown"
                    @input="showCustomerDropdown = true"
                    placeholder="Search for a customer or leave empty for general price..."
                  />
                  <button
                    v-if="customerSearch"
                    type="button"
                    class="modal-search-clear"
                    @click="clearCustomerSearch"
                  >
                    <i class="fas fa-times"></i>
                  </button>
                  <div v-if="showCustomerDropdown" class="modal-dropdown show">
                    <div
                      class="modal-dropdown-item"
                      @mousedown="clearCustomerSearch"
                    >
                      <div class="item-info">
                        <strong><i class="fas fa-globe me-2"></i>General Price (All Customers)</strong>
                      </div>
                      <div class="item-details">
                        <span class="category">Applies to all customers</span>
                      </div>
                    </div>
                    <div
                      v-for="customer in filteredCustomers"
                      :key="customer.customer_id"
                      @mousedown="selectCustomer(customer)"
                      class="modal-dropdown-item"
                    >
                      <div class="item-info">
                        <strong>{{ customer.name }}</strong>
                        <span class="item-code">{{ customer.customer_code }}</span>
                      </div>
                      <div class="item-details">
                        <span class="category">Customer-specific price</span>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="form-help-text">
                  <i class="fas fa-info-circle"></i>
                  Leave empty for general pricing that applies to all customers
                </div>
              </div>
            </div>

            <div class="modal-form-row">
              <div>
                <label class="modal-form-label">Valid From</label>
                <input
                  type="date"
                  class="modal-form-control"
                  v-model="priceForm.start_date"
                />
                <div class="form-help-text">
                  <i class="fas fa-calendar"></i>
                  Leave empty for immediate effect
                </div>
              </div>

              <div>
                <label class="modal-form-label">Valid Until</label>
                <input
                  type="date"
                  class="modal-form-control"
                  v-model="priceForm.end_date"
                />
                <div class="form-help-text">
                  <i class="fas fa-calendar"></i>
                  Leave empty for indefinite validity
                </div>
              </div>
            </div>

            <div class="modal-form-switch">
              <input
                type="checkbox"
                class="switch-input"
                v-model="priceForm.is_active"
                id="priceActive"
              />
              <label class="switch-label" for="priceActive">
                <i class="fas fa-check"></i>
                Price is Active
              </label>
            </div>
            <div class="form-help-text mt-2">
              <i class="fas fa-info-circle"></i>
              Only active prices will be used in price calculations and comparisons
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" @click="closePriceModal">
              <i class="fas fa-times"></i>
              Cancel
            </button>
            <button type="button" class="btn btn-primary" :disabled="saveLoading" @click="savePrice">
              <div v-if="saveLoading" class="spinner-border spinner-border-sm"></div>
              <i v-else :class="isEditing ? 'fas fa-save' : 'fas fa-plus'"></i>
              {{ isEditing ? 'Update Price' : 'Save Price' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
      <div class="modal-dialog modal-sm" @click.stop>
        <div class="modal-content">
          <div class="modal-header bg-danger">
            <h5 class="modal-title">
              <i class="fas fa-exclamation-triangle"></i>
              Confirm Delete
            </h5>
            <button type="button" class="modal-close" @click="showDeleteModal = false">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <p class="mb-3">Are you sure you want to delete this price? This action cannot be undone.</p>
            <div v-if="priceToDelete" class="alert alert-warning">
              <div class="row small">
                <div class="col-5"><strong>Type:</strong></div>
                <div class="col-7">{{ priceToDelete.price_type === 'purchase' ? 'Purchase' : 'Sale' }}</div>
                <div class="col-5"><strong>Price:</strong></div>
                <div class="col-7">{{ formatPrice(priceToDelete.price) }} {{ priceToDelete.currency_code }}</div>
                <div class="col-5"><strong>Min Qty:</strong></div>
                <div class="col-7">{{ formatNumber(priceToDelete.min_quantity) }}</div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" @click="showDeleteModal = false">
              Cancel
            </button>
            <button type="button" class="btn btn-danger" @click="deletePrice" :disabled="deleteLoading">
              <div v-if="deleteLoading" class="spinner-border spinner-border-sm"></div>
              <i v-else class="fas fa-trash"></i>
              Delete Price
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import axios from 'axios';

export default {
  name: 'EnhancedItemPriceManagement',
  data() {
    return {
      // General
      activeTab: 'list',
      items: [],
      vendors: [],
      customers: [],
      currencies: ['IDR', 'USD', 'EUR', 'SGD', 'JPY', 'CNY'],

      // Toast notifications
      toasts: [],
      toastId: 0,

      // Item search state
      itemSearch: '',
      showItemDropdown: false,
      selectedItemId: '',

      // Vendor/Customer search state
      vendorSearch: '',
      showVendorDropdown: false,
      customerSearch: '',
      showCustomerDropdown: false,

      // Comparison search state
      comparisonItemSearch: '',
      showComparisonDropdown: false,

      // Price List Tab
      prices: [],
      filteredPrices: [],
      isLoading: false,
      priceTypeFilter: '',
      activeFilter: '',
      currencyFilter: '',
      currentOnlyFilter: false,
      sortKey: 'price_type',
      sortOrder: 'asc',

      // Price Form Modal
      showPriceModal: false,
      isEditing: false,
      selectedPriceId: null,
      saveLoading: false,
      formErrors: {},
      priceForm: {
        price_type: 'purchase',
        price: null,
        currency_code: 'IDR',
        min_quantity: 1,
        vendor_id: null,
        customer_id: '',
        start_date: '',
        end_date: '',
        is_active: true
      },

      // Delete Modal
      showDeleteModal: false,
      priceToDelete: null,
      deleteLoading: false,

      // Price Comparison Tab
      comparisonItemId: '',
      comparisonQuantity: 1,
      comparisonCurrency: 'IDR',
      comparisonLoading: false,
      purchasePrice: null,
      salePrice: null,
      pricesInCurrencies: null
    };
  },

  computed: {
    sortedPrices() {
      return [...this.filteredPrices].sort((a, b) => {
        let aValue = this.getSortValue(a, this.sortKey);
        let bValue = this.getSortValue(b, this.sortKey);

        if (typeof aValue === 'number' && typeof bValue === 'number') {
          return this.sortOrder === 'asc' ? aValue - bValue : bValue - aValue;
        }

        const aStr = String(aValue).toLowerCase();
        const bStr = String(bValue).toLowerCase();
        return this.sortOrder === 'asc' ? aStr.localeCompare(bStr) : bStr.localeCompare(aStr);
      });
    },

    hasAnyPrices() {
      return this.prices && this.prices.length > 0;
    },

    filteredItems() {
      if (!this.showItemDropdown) return [];
      if (!this.itemSearch || this.itemSearch.trim() === '') {
        return this.items.slice(0, 15);
      }
      const searchTerm = this.itemSearch.toLowerCase().trim();
      return this.items.filter(item =>
        item.name.toLowerCase().includes(searchTerm) ||
        item.item_code.toLowerCase().includes(searchTerm)
      ).slice(0, 15);
    },

    filteredComparisonItems() {
      if (!this.showComparisonDropdown) return [];
      if (!this.comparisonItemSearch || this.comparisonItemSearch.trim() === '') {
        return this.items.slice(0, 15);
      }
      const searchTerm = this.comparisonItemSearch.toLowerCase().trim();
      return this.items.filter(item =>
        item.name.toLowerCase().includes(searchTerm) ||
        item.item_code.toLowerCase().includes(searchTerm)
      ).slice(0, 15);
    },

    filteredVendors() {
      if (!this.showVendorDropdown) return [];
      if (!this.vendorSearch || this.vendorSearch.trim() === '') {
        return this.vendors.slice(0, 15);
      }
      const searchTerm = this.vendorSearch.toLowerCase().trim();
      return this.vendors.filter(vendor =>
        vendor.name.toLowerCase().includes(searchTerm) ||
        vendor.vendor_code.toLowerCase().includes(searchTerm)
      ).slice(0, 15);
    },

    filteredCustomers() {
      if (!this.showCustomerDropdown) return [];
      if (!this.customerSearch || this.customerSearch.trim() === '') {
        return this.customers.slice(0, 15);
      }
      const searchTerm = this.customerSearch.toLowerCase().trim();
      return this.customers.filter(customer =>
        customer.name.toLowerCase().includes(searchTerm) ||
        customer.customer_code.toLowerCase().includes(searchTerm)
      ).slice(0, 15);
    }
  },

  mounted() {
    this.initializeComponent();
  },

  methods: {
    // Initialization
    async initializeComponent() {
      try {
        await Promise.all([
          this.loadItems(),
          this.loadVendors(),
          this.loadCustomers()
        ]);
      } catch (error) {
        this.showToast('error', 'Failed to initialize the application. Please refresh the page.');
      }
    },

    // Data Loading Functions
    async loadItems() {
      try {
        const response = await axios.get('items');
        this.items = response.data.data || [];
      } catch (error) {
        console.error('Failed to load items:', error);
        this.showToast('error', 'Could not load items');
        this.items = [];
      }
    },

    async loadVendors() {
      try {
        const response = await axios.get('/vendors');
        const vendorsData = response.data.data.data;
        this.vendors = Array.isArray(vendorsData) ? vendorsData.filter(v => v) : [];
      } catch (error) {
        console.error('Failed to load vendors:', error);
        this.vendors = [];
      }
    },

    async loadCustomers() {
      try {
        const response = await axios.get('/customers');
        const customersData = response.data.data;
        this.customers = Array.isArray(customersData) ? customersData.filter(c => c) : [];
      } catch (error) {
        console.error('Failed to load customers:', error);
        this.customers = [];
      }
    },

    // Item Search Methods
    selectItem(item) {
      this.selectedItemId = item.item_id;
      this.itemSearch = `${item.name} (${item.item_code})`;
      this.showItemDropdown = false;
      this.handleItemSelection();
    },

    clearItemSearch() {
      this.itemSearch = '';
      this.selectedItemId = '';
      this.showItemDropdown = false;
      this.prices = [];
      this.filteredPrices = [];
    },

    hideItemDropdown() {
      setTimeout(() => {
        this.showItemDropdown = false;
      }, 200);
    },

    selectComparisonItem(item) {
      this.comparisonItemId = item.item_id;
      this.comparisonItemSearch = `${item.name} (${item.item_code})`;
      this.showComparisonDropdown = false;
      this.loadPriceComparison();
    },

    clearComparisonSearch() {
      this.comparisonItemSearch = '';
      this.comparisonItemId = '';
      this.showComparisonDropdown = false;
      this.purchasePrice = null;
      this.salePrice = null;
      this.pricesInCurrencies = null;
    },

    hideComparisonDropdown() {
      setTimeout(() => {
        this.showComparisonDropdown = false;
      }, 200);
    },

    // Vendor/Customer Search Methods
    selectVendor(vendor) {
      this.priceForm.vendor_id = vendor.vendor_id;
      this.vendorSearch = `${vendor.name} (${vendor.vendor_code})`;
      this.showVendorDropdown = false;
    },

    clearVendorSearch() {
      this.vendorSearch = '';
      this.priceForm.vendor_id = null;
      this.showVendorDropdown = false;
    },

    hideVendorDropdown() {
      setTimeout(() => {
        this.showVendorDropdown = false;
      }, 200);
    },

    selectCustomer(customer) {
      this.priceForm.customer_id = customer.customer_id;
      this.customerSearch = `${customer.name} (${customer.customer_code})`;
      this.showCustomerDropdown = false;
    },

    clearCustomerSearch() {
      this.customerSearch = '';
      this.priceForm.customer_id = '';
      this.showCustomerDropdown = false;
    },

    hideCustomerDropdown() {
      setTimeout(() => {
        this.showCustomerDropdown = false;
      }, 200);
    },

    // Toast notification system
    showToast(type, message) {
      const toast = {
        id: ++this.toastId,
        type,
        message
      };
      this.toasts.push(toast);

      setTimeout(() => {
        this.removeToast(toast.id);
      }, 5000);
    },

    removeToast(id) {
      const index = this.toasts.findIndex(toast => toast.id === id);
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
    },

    getToastColor(type) {
      const colors = {
        success: '#10b981',
        error: '#ef4444',
        warning: '#f59e0b',
        info: '#3b82f6'
      };
      return colors[type] || colors.info;
    },

    getToastTitle(type) {
      const titles = {
        success: 'Success',
        error: 'Error',
        warning: 'Warning',
        info: 'Information'
      };
      return titles[type] || titles.info;
    },

    // Helper methods
    formatPrice(value) {
      if (value === null || value === undefined) return '-';
      return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(value);
    },

    formatNumber(value) {
      if (value === null || value === undefined) return '-';
      return new Intl.NumberFormat('id-ID').format(value);
    },

    formatDate(value) {
      if (!value) return '-';
      return new Date(value).toLocaleDateString('id-ID');
    },

    getSortValue(item, key) {
      if (key === 'customer_name') {
        return this.getCustomerName(item.customer_id) || '';
      }
      if (key === 'vendor_name') {
        return this.getVendorName(item.vendor_id) || '';
      }
      return item[key] ?? '';
    },

    getVendorName(vendorId) {
      if (!vendorId) return null;
      const vendor = this.vendors.find(v => v.vendor_id === vendorId);
      return vendor ? vendor.name : null;
    },

    getCustomerName(customerId) {
      if (!customerId) return null;
      const customer = this.customers.find(c => c.customer_id === customerId);
      return customer ? customer.name : null;
    },

    getPartnerInfo(price) {
      if (price.customer && price.customer.customer_code) {
        return `${price.customer.customer_code} - ${price.customer.name}`;
      }
      if (price.customer_id) {
        return this.getCustomerName(price.customer_id) || `Customer #${price.customer_id}`;
      }
      if (price.vendor && price.vendor.vendor_code) {
        return `${price.vendor.vendor_code} - ${price.vendor.name}`;
      }
      if (price.vendor_id) {
        return this.getVendorName(price.vendor_id) || `Vendor #${price.vendor_id}`;
      }
      return 'General Price';
    },

    calculateMargin(purchasePrice, salePrice) {
      if (!purchasePrice || !salePrice || purchasePrice === 0) {
        return '-';
      }

      try {
        const margin = ((salePrice - purchasePrice) / purchasePrice) * 100;
        return isFinite(margin) ? `${margin.toFixed(2)}%` : '-';
      } catch (error) {
        return '-';
      }
    },

    getMarginClass(marginStr) {
      if (marginStr === '-') return 'bg-secondary';

      const margin = parseFloat(marginStr);
      if (margin >= 30) return 'bg-success';
      if (margin >= 15) return 'bg-warning';
      if (margin >= 0) return 'bg-info';
      return 'bg-danger';
    },

    // Sorting
    sortTable(key) {
      if (this.sortKey === key) {
        this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortKey = key;
        this.sortOrder = 'asc';
      }
    },

    async handleItemSelection() {
      this.clearFilters();
      await this.loadItemPrices();
    },

    async loadItemPrices() {
      if (!this.selectedItemId) {
        this.prices = [];
        this.filteredPrices = [];
        return;
      }

      try {
        this.isLoading = true;
        const response = await axios.get(`items/${this.selectedItemId}/prices`);
        this.prices = response.data.data || [];
        this.applyFilters();
      } catch (error) {
        console.error('Failed to load item prices:', error);
        this.showToast('error', 'Could not load price data. Please try again.');
        this.prices = [];
        this.filteredPrices = [];
      } finally {
        this.isLoading = false;
      }
    },

    // Filtering
    applyFilters() {
      let filtered = [...this.prices];

      if (this.priceTypeFilter) {
        filtered = filtered.filter(price => price.price_type === this.priceTypeFilter);
      }

      if (this.activeFilter !== '') {
        const isActive = this.activeFilter === '1';
        filtered = filtered.filter(price => price.is_active === isActive);
      }

      if (this.currencyFilter) {
        filtered = filtered.filter(price => price.currency_code === this.currencyFilter);
      }

      if (this.currentOnlyFilter) {
        const today = new Date();
        filtered = filtered.filter(price => {
          const startDate = price.start_date ? new Date(price.start_date) : null;
          const endDate = price.end_date ? new Date(price.end_date) : null;
          return (!startDate || startDate <= today) && (!endDate || endDate >= today);
        });
      }

      this.filteredPrices = filtered;
    },

    clearFilters() {
      this.priceTypeFilter = '';
      this.activeFilter = '';
      this.currencyFilter = '';
      this.currentOnlyFilter = false;
      this.applyFilters();
    },

    // Price Comparison
    async loadPriceComparison() {
      if (!this.comparisonItemId) {
        this.purchasePrice = null;
        this.salePrice = null;
        this.pricesInCurrencies = null;
        return;
      }

      try {
        this.comparisonLoading = true;

        const [purchaseResponse, saleResponse, currenciesResponse] = await Promise.all([
          axios.get(`items/${this.comparisonItemId}/best-purchase-price`, {
            params: { quantity: this.comparisonQuantity, currency_code: this.comparisonCurrency }
          }),
          axios.get(`items/${this.comparisonItemId}/best-sale-price`, {
            params: { quantity: this.comparisonQuantity, currency_code: this.comparisonCurrency }
          }),
          axios.get(`items/${this.comparisonItemId}/prices-in-currencies`, {
            params: { currencies: this.currencies }
          })
        ]);

        this.purchasePrice = purchaseResponse.data.data;
        this.salePrice = saleResponse.data.data;
        this.pricesInCurrencies = currenciesResponse.data.data;

      } catch (error) {
        console.error('Failed to load price comparison:', error);
        this.showToast('error', 'Could not load price comparison data. Please try again.');
      } finally {
        this.comparisonLoading = false;
      }
    },

    // Price Management
    openAddPriceModal() {
      this.isEditing = false;
      this.selectedPriceId = null;
      this.formErrors = {};

      this.priceForm = {
        price_type: 'purchase',
        price: null,
        currency_code: 'IDR',
        min_quantity: 1,
        vendor_id: null,
        customer_id: '',
        start_date: '',
        end_date: '',
        is_active: true
      };

      this.vendorSearch = '';
      this.customerSearch = '';
      this.showVendorDropdown = false;
      this.showCustomerDropdown = false;
      this.showPriceModal = true;
    },

    editPrice(price) {
      if (!price?.price_id) {
        this.showToast('error', 'Invalid price data. Cannot edit.');
        return;
      }

      this.isEditing = true;
      this.selectedPriceId = price.price_id;
      this.formErrors = {};

      this.priceForm = {
        price_type: price.price_type || 'purchase',
        price: price.price || null,
        currency_code: price.currency_code || 'IDR',
        min_quantity: price.min_quantity || 1,
        vendor_id: price.vendor_id || (price.vendor?.vendor_id) || null,
        customer_id: price.customer_id || (price.customer?.customer_id) || '',
        start_date: price.start_date || '',
        end_date: price.end_date || '',
        is_active: price.is_active !== undefined ? price.is_active : true
      };

      // Set search fields based on selected vendor/customer
      if (this.priceForm.vendor_id) {
        const vendor = this.vendors.find(v => v.vendor_id === this.priceForm.vendor_id);
        if (vendor) {
          this.vendorSearch = `${vendor.name} (${vendor.vendor_code})`;
        }
      } else {
        this.vendorSearch = '';
      }

      if (this.priceForm.customer_id) {
        const customer = this.customers.find(c => c.customer_id === this.priceForm.customer_id);
        if (customer) {
          this.customerSearch = `${customer.name} (${customer.customer_code})`;
        }
      } else {
        this.customerSearch = '';
      }

      this.showVendorDropdown = false;
      this.showCustomerDropdown = false;
      this.showPriceModal = true;
    },

    closePriceModal() {
      this.showPriceModal = false;
      this.formErrors = {};
      this.vendorSearch = '';
      this.customerSearch = '';
      this.showVendorDropdown = false;
      this.showCustomerDropdown = false;
    },

    validateForm() {
      this.formErrors = {};

      if (!this.priceForm.price_type) {
        this.formErrors.price_type = 'Price type is required';
      }

      if (!this.priceForm.price || this.priceForm.price <= 0) {
        this.formErrors.price = 'Price must be greater than 0';
      }

      if (!this.priceForm.currency_code) {
        this.formErrors.currency_code = 'Currency is required';
      }

      if (!this.priceForm.min_quantity || this.priceForm.min_quantity < 1) {
        this.formErrors.min_quantity = 'Minimum quantity must be at least 1';
      }

      // Validate date range
      if (this.priceForm.start_date && this.priceForm.end_date) {
        if (new Date(this.priceForm.start_date) > new Date(this.priceForm.end_date)) {
          this.formErrors.end_date = 'End date must be after start date';
        }
      }

      return Object.keys(this.formErrors).length === 0;
    },

    async savePrice() {
      if (!this.validateForm()) {
        this.showToast('error', 'Please correct the form errors before saving.');
        return;
      }

      try {
        this.saveLoading = true;

        const url = this.isEditing
          ? `items/${this.selectedItemId}/prices/${this.selectedPriceId}`
          : `items/${this.selectedItemId}/prices`;

        const method = this.isEditing ? 'put' : 'post';

        await axios[method](url, this.priceForm);

        this.showPriceModal = false;
        this.showToast('success', this.isEditing ? 'Price updated successfully!' : 'Price added successfully!');

        await this.loadItemPrices();

        if (this.comparisonItemId === this.selectedItemId) {
          await this.loadPriceComparison();
        }

      } catch (error) {
        console.error('Failed to save price:', error);
        this.showToast('error', 'Could not save price. Please try again.');
      } finally {
        this.saveLoading = false;
      }
    },

    // Delete operations
    confirmDelete(price) {
      this.priceToDelete = price;
      this.showDeleteModal = true;
    },

    async deletePrice() {
      if (!this.priceToDelete) return;

      try {
        this.deleteLoading = true;

        await axios.delete(`items/${this.selectedItemId}/prices/${this.priceToDelete.price_id}`);

        this.showDeleteModal = false;
        this.priceToDelete = null;
        this.showToast('success', 'Price deleted successfully!');

        await this.loadItemPrices();

        if (this.comparisonItemId === this.selectedItemId) {
          await this.loadPriceComparison();
        }

      } catch (error) {
        console.error('Failed to delete price:', error);
        this.showToast('error', 'Could not delete price. Please try again.');
      } finally {
        this.deleteLoading = false;
      }
    }
  }
};
</script>
<style scoped>
/* Enhanced Item Price Management CSS */
:root {
  --primary-color: #2563eb;
  --secondary-color: #64748b;
  --success-color: #059669;
  --info-color: #0ea5e9;
  --warning-color: #d97706;
  --danger-color: #dc2626;
  --light-color: #f8fafc;
  --dark-color: #1e293b;
  --gray-50: #f9fafb;
  --gray-100: #f3f4f6;
  --gray-200: #e5e7eb;
  --gray-300: #d1d5db;
  --gray-400: #9ca3af;
  --gray-500: #6b7280;
  --gray-600: #4b5563;
  --gray-700: #374151;
  --gray-800: #1f2937;
  --border-radius: 0.5rem;
  --border-radius-sm: 0.375rem;
  --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
  --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

/* Enhanced page header styling */
.page-header {
  background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
  color: white;
  padding: 2rem;
  border-radius: var(--border-radius);
  margin-bottom: 2rem;
  box-shadow: var(--shadow-md);
}

/* Enhanced navigation tabs */
.nav-tabs-enhanced {
  border: none;
  background: var(--gray-100);
  border-radius: var(--border-radius);
  padding: 0.25rem;
  box-shadow: var(--shadow-sm);
  display: flex;
  gap: 0.25rem;
}

.nav-link {
  border: none;
  border-radius: var(--border-radius-sm);
  color: var(--gray-600);
  font-weight: 500;
  transition: all 0.2s ease;
  padding: 0.75rem 1.25rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
  background: transparent;
}

.nav-link:hover {
  background: rgba(255, 255, 255, 0.6);
  color: var(--gray-700);
  transform: translateY(-1px);
}

.nav-link.active {
  background: white;
  color: var(--primary-color);
  box-shadow: var(--shadow-sm);
  font-weight: 600;
}

/* Enhanced card styling */
.card {
  border: none;
  box-shadow: var(--shadow);
  border-radius: var(--border-radius);
  margin-bottom: 1.5rem;
  overflow: hidden;
  transition: box-shadow 0.2s ease;
  background: white;
}

.card:hover {
  box-shadow: var(--shadow-md);
}

.card-header {
  background: var(--gray-50);
  border-bottom: 1px solid var(--gray-200);
  font-weight: 600;
  color: var(--gray-700);
  padding: 1.25rem 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.card-body {
  padding: 1.5rem;
}

/* Item search container and dropdown */
.item-search-container,
.vendor-search-container,
.customer-search-container {
  position: relative;
  z-index: 200;
}

.dropdown-menu {
  position: fixed; /* Changed from absolute to fixed to escape parent overflow */
  background: white;
  border: 1px solid var(--gray-300);
  border-radius: var(--border-radius-sm);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
  max-height: 320px;
  overflow-y: auto;
  z-index: 99999; /* Very high z-index */
  margin: 0;
  min-width: 400px;
  display: none;
  /* Ensure dropdown can extend beyond parent boundaries */
  transform: translateZ(0);
  will-change: transform;
  /* Add backdrop filter for better visibility */
  backdrop-filter: blur(2px);
}
.dropdown-menu.show {
  display: block;
}

.dropdown-item {
  padding: 0.875rem 1rem;
  cursor: pointer;
  border-bottom: 1px solid var(--gray-100);
  transition: all 0.15s ease;
  display: block;
  width: 100%;
  clear: both;
  background-color: transparent;
  border: none;
  text-align: left;
}

.dropdown-item:last-child {
  border-bottom: none;
}

.dropdown-item:hover {
  background-color: var(--primary-color);
  color: white;
}

.dropdown-item.text-muted {
  color: var(--gray-500);
  cursor: default;
  font-style: italic;
  background-color: var(--gray-50);
}

.dropdown-item.text-muted:hover {
  background-color: var(--gray-50);
  color: var(--gray-500);
}

.item-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.5rem;
  flex-wrap: wrap;
}

.item-info strong {
  font-weight: 600;
  color: var(--gray-800);
  flex: 1;
  min-width: 0;
}

.item-code {
  color: var(--primary-color);
  font-size: 0.8rem;
  font-weight: 600;
  background-color: var(--gray-100);
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  white-space: nowrap;
}

.item-details {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.8rem;
  gap: 1rem;
}

.category {
  color: var(--info-color);
  font-weight: 500;
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.stock {
  color: var(--gray-600);
  font-weight: 500;
  white-space: nowrap;
}

/* Enhanced input group styling */
.input-group {
  position: relative;
  display: flex;
  flex-wrap: wrap;
  align-items: stretch;
  width: 100%;
  border-radius: var(--border-radius-sm);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.input-group-text {
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  background-color: white;
  border: 1px solid var(--gray-300);
  color: var(--gray-500);
  border-right: none;
  margin: 0;
}

.form-control {
  border: 1px solid var(--gray-300);
  border-radius: var(--border-radius-sm);
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  transition: all 0.2s ease;
  background-color: white;
  width: 100%;
}

.form-control:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
  outline: none;
}

.input-group .form-control {
  border-left: none;
  flex: 1;
  min-width: 0;
  padding-right: 2.5rem;
}

/* Clear button positioning */
.search-clear-btn {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  background: var(--gray-200);
  border: 1px solid var(--gray-300);
  color: var(--gray-600);
  cursor: pointer;
  z-index: 10;
  padding: 0.25rem;
  border-radius: 50%;
  transition: all 0.2s ease;
  width: 1.5rem;
  height: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
}

.search-clear-btn:hover {
  color: var(--gray-700);
  background-color: var(--gray-300);
  border-color: var(--gray-400);
}

/* Enhanced button styling */
.btn {
  font-weight: 500;
  border-radius: var(--border-radius-sm);
  padding: 0.625rem 1.25rem;
  transition: all 0.2s ease;
  border: 1px solid transparent;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  white-space: nowrap;
  cursor: pointer;
  text-decoration: none;
}

.btn:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-sm);
}

.btn:active {
  transform: translateY(0);
}

.btn-primary {
  background-color: var(--primary-color);
  border-color: var(--primary-color);
  color: white;
}

.btn-primary:hover {
  background-color: #1d4ed8;
  border-color: #1d4ed8;
  color: white;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.btn-outline-primary {
  color: var(--primary-color);
  border-color: var(--primary-color);
  background-color: transparent;
}

.btn-outline-primary:hover {
  background-color: var(--primary-color);
  border-color: var(--primary-color);
  color: white;
}

.btn-outline-secondary {
  color: var(--gray-600);
  border-color: var(--gray-300);
  background-color: transparent;
}

.btn-outline-secondary:hover {
  background-color: var(--gray-100);
  border-color: var(--gray-400);
  color: var(--gray-700);
}

.btn-outline-danger {
  color: var(--danger-color);
  border-color: var(--danger-color);
  background-color: transparent;
}

.btn-outline-danger:hover {
  background-color: var(--danger-color);
  border-color: var(--danger-color);
  color: white;
}

.btn-outline-success {
  color: var(--success-color);
  border-color: var(--success-color);
  background-color: transparent;
}

.btn-outline-success:hover {
  background-color: var(--success-color);
  border-color: var(--success-color);
  color: white;
}

.btn-danger {
  background-color: var(--danger-color);
  border-color: var(--danger-color);
  color: white;
}

.btn-danger:hover {
  background-color: #b91c1c;
  border-color: #b91c1c;
  color: white;
}

.btn-sm {
  padding: 0.375rem 0.75rem;
  font-size: 0.8rem;
}

.btn-group {
  display: flex;
  gap: 0.25rem;
}

/* Enhanced badges */
.badge {
  font-weight: 500;
  padding: 0.375rem 0.625rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  display: inline-flex;
  align-items: center;
}

.bg-info {
  background-color: var(--info-color) !important;
  color: white;
}

.bg-success {
  background-color: var(--success-color) !important;
  color: white;
}

.bg-secondary {
  background-color: var(--gray-500) !important;
  color: white;
}

.bg-light {
  background-color: var(--gray-100) !important;
  color: var(--gray-700) !important;
}

.bg-primary {
  background-color: var(--primary-color) !important;
  color: white;
}

.bg-warning {
  background-color: var(--warning-color) !important;
  color: white;
}

.bg-danger {
  background-color: var(--danger-color) !important;
  color: white;
}

/* Table styling */
.table {
  width: 100%;
  margin-bottom: 0;
  color: var(--gray-700);
}

.table th {
  border-bottom: 2px solid var(--gray-200);
  font-weight: 600;
  color: var(--gray-700);
  padding: 1rem;
  background-color: var(--gray-50);
}

.table td {
  border-bottom: 1px solid var(--gray-200);
  padding: 1rem;
}

.table th.sortable {
  cursor: pointer;
  user-select: none;
  transition: background-color 0.2s ease;
  position: relative;
}

.table th.sortable:hover {
  background-color: var(--gray-100);
}

.table-hover tbody tr:hover {
  background-color: var(--gray-50);
}

.table-responsive {
  overflow-x: auto;
}

/* Loading and empty states */
.loading-container,
.empty-state {
  padding: 3rem 2rem;
  text-align: center;
}

.empty-state {
  background-color: var(--gray-50);
  border-radius: var(--border-radius);
  border: 2px dashed var(--gray-300);
}

.spinner-border {
  width: 2rem;
  height: 2rem;
  border: 0.25em solid currentcolor;
  border-right-color: transparent;
  border-radius: 50%;
  animation: spinner-border 0.75s linear infinite;
}

@keyframes spinner-border {
  to {
    transform: rotate(360deg);
  }
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
  border-width: 0.125rem;
}

/* Modal Overlay & Container - Enhanced */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.65);
  z-index: 1055;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(4px);
  animation: modalFadeIn 0.25s ease-out;
  padding: 1rem;
}

.modal-dialog {
  width: 100%;
  max-width: 700px;
  max-height: 95vh;
  overflow: hidden;
  animation: modalSlideIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
  margin: 0;
  border-radius: 12px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.modal-dialog.modal-lg {
  max-width: 900px;
}

.modal-dialog.modal-sm {
  max-width: 400px;
}

.modal-content {
  border: none;
  border-radius: 12px;
  background: white;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  max-height: 95vh;
}

/* Modal Header - Enhanced */
.modal-header {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 50%, #1e40af 100%);
  color: white;
  padding: 1.75rem 2rem;
  border-bottom: none;
  position: relative;
  overflow: hidden;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="30" r="1.5" fill="white" opacity="0.08"/><circle cx="80" cy="60" r="1" fill="white" opacity="0.1"/><circle cx="20" cy="80" r="1.2" fill="white" opacity="0.09"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
  opacity: 0.6;
}

.modal-header.bg-danger {
  background: linear-gradient(135deg, var(--danger-color) 0%, #b91c1c 100%);
}

.modal-title {
  color: white;
  font-weight: 700;
  font-size: 1.375rem;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  position: relative;
  z-index: 1;
  letter-spacing: -0.025em;
}

.modal-close {
  background: none;
  border: none;
  color: rgba(255, 255, 255, 0.8);
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 50%;
  transition: all 0.2s ease;
  width: 2.75rem;
  height: 2.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  z-index: 1;
}

.modal-close:hover {
  color: white;
  background: rgba(255, 255, 255, 0.2);
  transform: scale(1.1) rotate(90deg);
}

/* Modal Body - Enhanced */
.modal-body {
  padding: 2.5rem;
  background: #fafbfc;
  flex: 1;
  overflow-y: auto;
  position: relative;
}

.modal-body::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #2563eb, #7c3aed, #dc2626, #059669);
  opacity: 0.1;
}

/* Form Layout */
.modal-form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  margin-bottom: 2rem;
}

.modal-form-row.single {
  grid-template-columns: 1fr;
}

.modal-form-row.three {
  grid-template-columns: 2fr 1fr;
}

/* Form Labels - Enhanced */
.modal-form-label {
  font-weight: 700;
  color: #374151;
  margin-bottom: 0.75rem;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.required-asterisk {
  color: #dc2626;
  margin-left: 0.25rem;
  font-size: 1rem;
  font-weight: 400;
}

/* Form Controls - Enhanced */
.modal-form-control,
.modal-form-select {
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  padding: 1rem 1.25rem;
  font-size: 0.95rem;
  font-weight: 500;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  background-color: white;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  width: 100%;
}

.modal-form-control:focus,
.modal-form-select:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1), 0 4px 6px rgba(0, 0, 0, 0.07);
  outline: none;
  transform: translateY(-1px);
}

.modal-form-control:hover:not(:focus),
.modal-form-select:hover:not(:focus) {
  border-color: #9ca3af;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.modal-form-control.is-invalid {
  border-color: #dc2626;
  box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.1);
}

.modal-form-control::placeholder {
  color: #9ca3af;
  font-weight: 400;
  font-style: italic;
}

/* Invalid Feedback - Enhanced */
.invalid-feedback {
  display: block;
  margin-top: 0.5rem;
  font-size: 0.8rem;
  color: #dc2626;
  font-weight: 600;
  padding-left: 0.5rem;
  border-left: 3px solid #dc2626;
  background-color: rgba(220, 38, 38, 0.05);
  padding: 0.5rem 0.5rem 0.5rem 1rem;
  border-radius: 4px;
}

/* Form Help Text - Enhanced */
.form-help-text {
  color: #6b7280;
  font-size: 0.8rem;
  margin-top: 0.75rem;
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  padding: 0.75rem;
  background-color: #f3f4f6;
  border-radius: 6px;
  border-left: 4px solid #2563eb;
}

/* Search Containers in Modal */
.modal-search-container {
  position: relative;
  z-index: 100;
}

.modal-search-container .modal-form-control {
  padding-right: 3rem;
}

/* Search Clear Button in Modal */
.modal-search-clear {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  background: #f3f4f6;
  border: 1px solid #d1d5db;
  color: #6b7280;
  cursor: pointer;
  z-index: 10;
  padding: 0.375rem;
  border-radius: 50%;
  transition: all 0.2s ease;
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
}

.modal-search-clear:hover {
  color: #374151;
  background-color: #e5e7eb;
  border-color: #9ca3af;
  transform: translateY(-50%) scale(1.1);
}

/* Dropdown in Modal */
.modal-dropdown {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  max-height: 280px;
  overflow-y: auto;
  z-index: 1070;
  margin: 0;
  min-width: 100%;
  backdrop-filter: blur(8px);
}

.modal-dropdown.show {
  display: block;
  animation: dropdownSlideIn 0.2s ease-out;
}

.modal-dropdown-item {
  padding: 1rem 1.25rem;
  cursor: pointer;
  border-bottom: 1px solid #f3f4f6;
  transition: all 0.15s ease;
  background-color: white;
}

.modal-dropdown-item:last-child {
  border-bottom: none;
}

.modal-dropdown-item:hover {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  color: white;
  transform: translateX(4px);
}

/* Form Switch - Enhanced */
.modal-form-switch {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.5rem;
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
  border-radius: 12px;
  border: 2px solid #bae6fd;
  margin-top: 1rem;
  transition: all 0.2s ease;
}

.modal-form-switch:hover {
  background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
  border-color: #7dd3fc;
}

.switch-input {
  margin: 0;
  flex-shrink: 0;
  width: 3rem;
  height: 1.5rem;
  border-radius: 2rem;
  border: 2px solid #d1d5db;
  background-color: #f3f4f6;
  transition: all 0.2s ease;
  appearance: none;
  cursor: pointer;
  position: relative;
}

.switch-input:checked {
  background-color: #059669;
  border-color: #059669;
}

.switch-input:checked::before {
  transform: translateX(1.5rem);
}

.switch-input::before {
  content: '';
  position: absolute;
  top: 0.125rem;
  left: 0.125rem;
  width: 1rem;
  height: 1rem;
  background: white;
  border-radius: 50%;
  transition: transform 0.2s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.switch-label {
  margin: 0;
  font-weight: 700;
  color: #374151;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.95rem;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

/* Modal Footer - Enhanced */
.modal-footer {
  border-top: 1px solid #e5e7eb;
  background: linear-gradient(to bottom, #ffffff 0%, #f9fafb 100%);
  padding: 2rem 2.5rem;
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 1rem;
}

.modal-footer .btn {
  font-weight: 600;
  border-radius: 8px;
  padding: 0.875rem 2rem;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  border: 2px solid transparent;
  font-size: 0.95rem;
  min-width: 140px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.modal-footer .btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.modal-footer .btn:active {
  transform: translateY(0);
}

.modal-footer .btn-primary {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  border-color: #2563eb;
  color: white;
  box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);
}

.modal-footer .btn-primary:hover {
  background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
  border-color: #1d4ed8;
  box-shadow: 0 8px 25px rgba(37, 99, 235, 0.4);
}

.modal-footer .btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

/* Toast notifications */
.toast-container {
  position: fixed;
  top: 1rem;
  right: 1rem;
  z-index: 1060;
}

.toast {
  background: white;
  border: none;
  box-shadow: var(--shadow-lg);
  border-radius: var(--border-radius);
  animation: slideInRight 0.3s ease-out;
  margin-bottom: 0.5rem;
  min-width: 300px;
}

.toast-header {
  background: var(--gray-50);
  border-bottom: 1px solid var(--gray-200);
  padding: 0.75rem 1rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.toast-body {
  padding: 0.75rem 1rem;
}

/* Form select styling */
.form-select {
  border: 1px solid var(--gray-300);
  border-radius: var(--border-radius-sm);
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  transition: all 0.2s ease;
  background-color: white;
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m1 6 7 7 7-7'/%3e%3c/svg%3e");
  background-repeat: no-repeat;
  background-position: right 0.75rem center;
  background-size: 16px 12px;
  padding-right: 2.5rem;
}

.form-select:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
  outline: none;
}

.form-select-sm {
  padding: 0.375rem 0.75rem;
  font-size: 0.8rem;
  border-radius: var(--border-radius-sm);
  border: 1px solid var(--gray-300);
  background-color: white;
  min-width: 120px;
  transition: all 0.2s ease;
}

.form-select-sm:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
  outline: none;
}

.form-select-sm:hover {
  border-color: var(--gray-400);
}

/* Alert styling */
.alert {
  padding: 0.75rem 1.25rem;
  margin-bottom: 1rem;
  border: 1px solid transparent;
  border-radius: 0.375rem;
}

.alert-warning {
  color: #856404;
  background-color: #fff3cd;
  border-color: #ffeaa7;
}

/* Animations */
@keyframes modalFadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(-50px) scale(0.9);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@keyframes dropdownSlideIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(2rem);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

/* Utility classes */
.text-muted {
  color: var(--gray-500) !important;
}

.fw-semibold {
  font-weight: 600 !important;
}

.text-truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.d-flex {
  display: flex;
}

.align-items-center {
  align-items: center;
}

.justify-content-between {
  justify-content: space-between;
}

.gap-2 {
  gap: 0.5rem;
}

.gap-3 {
  gap: 1rem;
}

.flex-1 {
  flex: 1;
}

.flex-wrap {
  flex-wrap: wrap;
}

.mb-2 {
  margin-bottom: 0.5rem;
}

.mb-3 {
  margin-bottom: 1rem;
}

.mb-4 {
  margin-bottom: 1.5rem;
}

.mt-2 {
  margin-top: 0.5rem;
}

.mt-3 {
  margin-top: 1rem;
}

.ms-1 {
  margin-left: 0.25rem;
}

.ms-2 {
  margin-left: 0.5rem;
}

.ms-auto {
  margin-left: auto;
}

.me-1 {
  margin-right: 0.25rem;
}

.me-2 {
  margin-right: 0.5rem;
}

.me-3 {
  margin-right: 1rem;
}

.me-auto {
  margin-right: auto;
}

.p-0 {
  padding: 0;
}

.p-4 {
  padding: 1.5rem;
}

.py-4 {
  padding-top: 1.5rem;
  padding-bottom: 1.5rem;
}

.py-5 {
  padding-top: 3rem;
  padding-bottom: 3rem;
}

.text-center {
  text-align: center;
}

.text-end {
  text-align: end;
}

.text-start {
  text-align: start;
}

.w-100 {
  width: 100%;
}

.h-100 {
  height: 100%;
}

.min-h-screen {
  min-height: 100vh;
}

.bg-gray-50 {
  background-color: var(--gray-50);
}

.small {
  font-size: 0.875em;
}

.display-4 {
  font-size: 2.5rem;
  font-weight: 300;
  line-height: 1.2;
}

.row {
  display: flex;
  flex-wrap: wrap;
  margin-left: -0.75rem;
  margin-right: -0.75rem;
}

.col-5 {
  flex: 0 0 auto;
  width: 41.66666667%;
  padding-left: 0.75rem;
  padding-right: 0.75rem;
}

.col-6 {
  flex: 0 0 auto;
  width: 50%;
  padding-left: 0.75rem;
  padding-right: 0.75rem;
}

.col-7 {
  flex: 0 0 auto;
  width: 58.33333333%;
  padding-left: 0.75rem;
  padding-right: 0.75rem;
}

/* Responsive design */
@media (max-width: 768px) {
  .modal-dialog {
    margin: 0.5rem;
    max-height: 95vh;
  }

  .modal-header {
    padding: 1.5rem;
  }

  .modal-title {
    font-size: 1.25rem;
  }

  .modal-body {
    padding: 2rem 1.5rem;
  }

  .modal-form-row {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }

  .modal-footer {
    padding: 1.5rem;
    flex-direction: column-reverse;
    gap: 1rem;
  }

  .modal-footer .btn {
    width: 100%;
    min-width: auto;
  }

  .row {
    margin-left: -0.5rem;
    margin-right: -0.5rem;
  }

  .col-5,
  .col-6,
  .col-7 {
    padding-left: 0.5rem;
    padding-right: 0.5rem;
  }

  .d-flex.gap-3 {
    flex-direction: column;
    gap: 1rem;
  }

  .display-4 {
    font-size: 2rem;
  }

  .page-header {
    padding: 1.5rem;
    text-align: center;
  }

  .page-header h1 {
    font-size: 1.5rem;
  }

  .table-responsive {
    font-size: 0.875rem;
  }

  .btn-group .btn {
    padding: 0.25rem 0.5rem;
  }

  .card-body {
    padding: 1rem;
  }
}

@media (max-width: 480px) {
  .modal-dialog {
    margin: 0.25rem;
  }

  .modal-body {
    padding: 1.5rem 1rem;
  }

  .modal-form-control,
  .modal-form-select {
    padding: 0.875rem 1rem;
    font-size: 0.9rem;
  }

  .modal-footer {
    padding: 1.25rem 1rem;
  }

  .modal-footer .btn {
    padding: 0.75rem 1.5rem;
    font-size: 0.9rem;
  }

  .nav-tabs-enhanced .nav-link {
    padding: 0.5rem 0.75rem;
    font-size: 0.8rem;
  }

  .page-header {
    padding: 1rem;
  }

  .card-body {
    padding: 0.75rem;
  }

  .dropdown-item {
    padding: 0.75rem 1rem;
  }

  .item-info strong {
    font-size: 0.9rem;
  }

  .item-code {
    font-size: 0.75rem;
    padding: 0.125rem 0.375rem;
  }

  .item-details {
    font-size: 0.75rem;
  }

  .modal-form-switch {
    padding: 1.25rem;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
  }

  .switch-input {
    width: 2.5rem;
    height: 1.25rem;
  }
}

/* Focus management for accessibility */
.modal-form-control:focus,
.modal-form-select:focus,
.modal-footer .btn:focus {
  outline: 3px solid rgba(37, 99, 235, 0.3);
  outline-offset: 2px;
}

/* Scrollbar styling */
.modal-body::-webkit-scrollbar,
.dropdown-menu::-webkit-scrollbar {
  width: 8px;
}

.modal-body::-webkit-scrollbar-track,
.dropdown-menu::-webkit-scrollbar-track {
  background: #f3f4f6;
  border-radius: 4px;
}

.modal-body::-webkit-scrollbar-thumb,
.dropdown-menu::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 4px;
}

.modal-body::-webkit-scrollbar-thumb:hover,
.dropdown-menu::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .modal-form-control,
  .modal-form-select {
    border-width: 3px;
  }

  .modal-form-control:focus,
  .modal-form-select:focus {
    border-width: 3px;
    box-shadow: none;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .modal-overlay,
  .modal-dialog,
  .dropdown-menu,
  .form-control,
  .form-select,
  .btn {
    animation: none;
    transition: none;
  }
}

/* Print styles */
@media print {
  .page-header,
  .nav-tabs-enhanced,
  .btn,
  .modal-overlay,
  .dropdown-menu {
    display: none !important;
  }

  .card {
    box-shadow: none;
    border: 1px solid var(--gray-300);
  }
}
</style>
