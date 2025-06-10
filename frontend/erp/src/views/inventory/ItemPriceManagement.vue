<template>
  <div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h2 mb-1">Item Price Management</h1>
        <p class="text-muted mb-0">Manage item pricing and compare rates across vendors and customers</p>
      </div>
    </div>

    <!-- Tab Navigation -->
    <ul class="nav nav-tabs nav-tabs-enhanced mb-4">
      <li class="nav-item">
        <a class="nav-link" :class="{ active: activeTab === 'list' }" @click="switchTab('list')">
          <i class="fas fa-list me-2"></i>Price Management
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" :class="{ active: activeTab === 'comparison' }" @click="switchTab('comparison')">
          <i class="fas fa-balance-scale me-2"></i>Price Comparison
        </a>
      </li>
    </ul>

    <!-- Toast Notifications -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1060">
      <div v-for="toast in toasts" :key="toast.id" class="toast show" role="alert">
        <div class="toast-header">
          <i :class="['fas me-2', getToastIcon(toast.type)]" :style="{ color: getToastColor(toast.type) }"></i>
          <strong class="me-auto">{{ getToastTitle(toast.type) }}</strong>
          <button type="button" class="btn-close" @click="removeToast(toast.id)"></button>
        </div>
        <div class="toast-body">{{ toast.message }}</div>
      </div>
    </div>

    <!-- Item Price List Tab -->
    <div v-if="activeTab === 'list'" class="tab-content">
      <!-- Item Selection -->
      <div class="card mb-4">
        <div class="card-body">
          <div class="row align-items-end">
            <div class="col-md-8">
              <label class="form-label fw-semibold">Select Item</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <select class="form-select" v-model="selectedItemId" @change="handleItemSelection">
                  <option value="">-- Choose an item to manage prices --</option>
                  <option v-for="item in items" :key="item.item_id" :value="item.item_id">
                    {{ item.item_code }} - {{ item.name }}
                  </option>
                </select>
              </div>
            </div>
            <div class="col-md-4 text-end">
              <button
                class="btn btn-primary"
                @click="openAddPriceModal"
                :disabled="!selectedItemId || isLoading"
              >
                <i class="fas fa-plus me-2"></i>Add New Price
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Price Filters -->
      <div v-if="selectedItemId" class="card mb-4">
        <div class="card-header">
          <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Options</h6>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-2">
              <label class="form-label">Type</label>
              <select class="form-select form-select-sm" v-model="priceTypeFilter" @change="applyFilters">
                <option value="">All Types</option>
                <option value="purchase">Purchase</option>
                <option value="sale">Sale</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">Status</label>
              <select class="form-select form-select-sm" v-model="activeFilter" @change="applyFilters">
                <option value="">All Status</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">Currency</label>
              <select class="form-select form-select-sm" v-model="currencyFilter" @change="applyFilters">
                <option value="">All Currencies</option>
                <option v-for="currency in currencies" :key="currency" :value="currency">
                  {{ currency }}
                </option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Date Range</label>
              <div class="form-check form-switch">
                <input
                  class="form-check-input"
                  type="checkbox"
                  v-model="currentOnlyFilter"
                  @change="applyFilters"
                  id="currentOnlySwitch"
                >
                <label class="form-check-label" for="currentOnlySwitch">
                  Show only current prices
                </label>
              </div>
            </div>
            <div class="col-md-3 d-flex align-items-end">
              <button class="btn btn-outline-secondary btn-sm" @click="clearFilters">
                <i class="fas fa-times me-1"></i>Clear Filters
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Price List Table -->
      <div v-if="selectedItemId" class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0">
            <i class="fas fa-tags me-2"></i>Price List
            <span v-if="filteredPrices.length > 0" class="badge bg-primary ms-2">
              {{ filteredPrices.length }} {{ filteredPrices.length === 1 ? 'price' : 'prices' }}
            </span>
          </h6>
          <div class="text-muted small">
            <i class="fas fa-info-circle me-1"></i>Click column headers to sort
          </div>
        </div>
        <div class="card-body p-0">
          <!-- Loading State -->
          <div v-if="isLoading" class="loading-container">
            <div class="text-center py-5">
              <div class="spinner-border text-primary mb-3" role="status"></div>
              <p class="text-muted">Loading price data...</p>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else-if="!hasAnyPrices" class="empty-state">
            <div class="text-center py-5">
              <i class="fas fa-tags fa-4x text-muted mb-3"></i>
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
                <tr v-for="(price, index) in sortedPrices" :key="price.price_id || index" class="align-middle">
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
                    <div class="text-truncate" style="max-width: 150px;" :title="getPartnerInfo(price)">
                      {{ getPartnerInfo(price) }}
                    </div>
                  </td>
                  <td>
                    <div class="btn-group btn-group-sm">
                      <button class="btn btn-outline-primary" @click="editPrice(price)" title="Edit Price">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn btn-outline-danger" @click="confirmDelete(price)" title="Delete Price">
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
      <!-- Comparison Controls -->
      <div class="card mb-4">
        <div class="card-header">
          <h6 class="mb-0"><i class="fas fa-balance-scale me-2"></i>Price Comparison Settings</h6>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Select Item</label>
              <select class="form-select" v-model="comparisonItemId" @change="loadPriceComparison">
                <option value="">-- Choose item to compare prices --</option>
                <option v-for="item in items" :key="item.item_id" :value="item.item_id">
                  {{ item.item_code }} - {{ item.name }}
                </option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Quantity</label>
              <input
                type="number"
                class="form-control"
                v-model.number="comparisonQuantity"
                min="1"
                step="1"
                @change="loadPriceComparison"
                placeholder="Enter quantity"
              >
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Base Currency</label>
              <select class="form-select" v-model="comparisonCurrency" @change="loadPriceComparison">
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
        <div class="row mb-4">
          <div class="col-lg-6 mb-3">
            <div class="card h-100 border-info">
              <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Best Purchase Price</h6>
              </div>
              <div class="card-body">
                <div v-if="comparisonLoading" class="text-center py-4">
                  <div class="spinner-border text-info mb-3"></div>
                  <p class="text-muted">Finding best purchase price...</p>
                </div>
                <div v-else-if="purchasePrice" class="text-center">
                  <div class="display-4 text-info fw-bold mb-2">{{ formatPrice(purchasePrice.price) }}</div>
                  <div class="text-muted mb-3">{{ purchasePrice.currency }}</div>
                  <div class="row text-start small">
                    <div class="col-6"><strong>Quantity:</strong></div>
                    <div class="col-6">{{ formatNumber(purchasePrice.quantity) }}</div>
                    <div class="col-6"><strong>Vendor:</strong></div>
                    <div class="col-6">{{ getVendorName(purchasePrice.vendor_id) || 'General' }}</div>
                  </div>
                  <button class="btn btn-outline-info btn-sm mt-3" @click="viewPurchasePriceDetails">
                    <i class="fas fa-eye me-1"></i>View Details
                  </button>
                </div>
                <div v-else class="text-center py-4">
                  <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                  <p class="text-muted">No purchase price available</p>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-6 mb-3">
            <div class="card h-100 border-success">
              <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="fas fa-hand-holding-usd me-2"></i>Best Sale Price</h6>
              </div>
              <div class="card-body">
                <div v-if="comparisonLoading" class="text-center py-4">
                  <div class="spinner-border text-success mb-3"></div>
                  <p class="text-muted">Finding best sale price...</p>
                </div>
                <div v-else-if="salePrice" class="text-center">
                  <div class="display-4 text-success fw-bold mb-2">{{ formatPrice(salePrice.price) }}</div>
                  <div class="text-muted mb-3">{{ salePrice.currency }}</div>
                  <div class="row text-start small">
                    <div class="col-6"><strong>Quantity:</strong></div>
                    <div class="col-6">{{ formatNumber(salePrice.quantity) }}</div>
                    <div class="col-6"><strong>Customer:</strong></div>
                    <div class="col-6">{{ getCustomerName(salePrice.customer_id) || 'General' }}</div>
                  </div>
                  <button class="btn btn-outline-success btn-sm mt-3" @click="viewSalePriceDetails">
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
        </div>

        <!-- Multi-currency Comparison -->
        <div v-if="pricesInCurrencies" class="card">
          <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-globe me-2"></i>Multi-Currency Price Analysis</h6>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-striped mb-0">
                <thead class="table-dark">
                  <tr>
                    <th>Currency</th>
                    <th class="text-end">Purchase Price</th>
                    <th class="text-end">Sale Price</th>
                    <th class="text-center">Profit Margin</th>
                    <th class="text-center">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(prices, currency) in pricesInCurrencies?.prices || {}" :key="currency">
                    <td>
                      <span class="fw-semibold">{{ currency }}</span>
                    </td>
                    <td class="text-end">
                      <span v-if="prices?.purchase_price" class="text-info fw-semibold">
                        {{ formatPrice(prices.purchase_price) }}
                      </span>
                      <span v-else class="text-muted">-</span>
                    </td>
                    <td class="text-end">
                      <span v-if="prices?.sale_price" class="text-success fw-semibold">
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
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i :class="['fas me-2', isEditing ? 'fa-edit' : 'fa-plus']"></i>
              {{ isEditing ? 'Edit Price' : 'Add New Price' }}
            </h5>
            <button type="button" class="btn-close" @click="closePriceModal"></button>
          </div>
          <form @submit.prevent="savePrice" novalidate>
            <div class="modal-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Price Type *</label>
                  <select class="form-select" v-model="priceForm.price_type" required
                          :class="{ 'is-invalid': formErrors.price_type }">
                    <option value="purchase">Purchase Price</option>
                    <option value="sale">Sale Price</option>
                  </select>
                  <div v-if="formErrors.price_type" class="invalid-feedback">{{ formErrors.price_type }}</div>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold">Minimum Quantity *</label>
                  <input type="number" class="form-control" v-model.number="priceForm.min_quantity"
                         min="1" step="1" required :class="{ 'is-invalid': formErrors.min_quantity }"
                         placeholder="Enter minimum quantity">
                  <div v-if="formErrors.min_quantity" class="invalid-feedback">{{ formErrors.min_quantity }}</div>
                </div>

                <div class="col-md-8">
                  <label class="form-label fw-semibold">Price *</label>
                  <input type="number" class="form-control" v-model.number="priceForm.price"
                         step="0.01" min="0" required :class="{ 'is-invalid': formErrors.price }"
                         placeholder="Enter price amount">
                  <div v-if="formErrors.price" class="invalid-feedback">{{ formErrors.price }}</div>
                </div>

                <div class="col-md-4">
                  <label class="form-label fw-semibold">Currency *</label>
                  <select class="form-select" v-model="priceForm.currency_code" required
                          :class="{ 'is-invalid': formErrors.currency_code }">
                    <option v-for="currency in currencies" :key="currency" :value="currency">
                      {{ currency }}
                    </option>
                  </select>
                </div>

                <div v-if="priceForm.price_type === 'purchase'" class="col-12">
                  <label class="form-label fw-semibold">Vendor</label>
                  <select class="form-select" v-model="priceForm.vendor_id">
                    <option :value="null">General price (all vendors)</option>
                    <option v-for="vendor in vendors" :key="vendor.vendor_id" :value="vendor.vendor_id">
                      {{ vendor.vendor_code }} - {{ vendor.name }}
                    </option>
                  </select>
                  <div class="form-text">Leave as general if this price applies to all vendors</div>
                </div>

                <div v-if="priceForm.price_type === 'sale'" class="col-12">
                  <label class="form-label fw-semibold">Customer</label>
                  <select class="form-select" v-model="priceForm.customer_id">
                    <option value="">General price (all customers)</option>
                    <option v-for="customer in customers" :key="customer.customer_id" :value="customer.customer_id">
                      {{ customer.customer_code }} - {{ customer.name }}
                    </option>
                  </select>
                  <div class="form-text">Leave as general if this price applies to all customers</div>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold">Valid From</label>
                  <input type="date" class="form-control" v-model="priceForm.start_date">
                  <div class="form-text">Leave empty for immediate effect</div>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold">Valid Until</label>
                  <input type="date" class="form-control" v-model="priceForm.end_date">
                  <div class="form-text">Leave empty for indefinite validity</div>
                </div>

                <div class="col-12">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" v-model="priceForm.is_active" id="priceActive">
                    <label class="form-check-label fw-semibold" for="priceActive">
                      Price is Active
                    </label>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" @click="closePriceModal">
                Cancel
              </button>
              <button type="submit" class="btn btn-primary" :disabled="saveLoading">
                <span v-if="saveLoading" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else :class="['fas me-2', isEditing ? 'fa-save' : 'fa-plus']"></i>
                {{ isEditing ? 'Update Price' : 'Save Price' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
      <div class="modal-dialog modal-sm">
        <div class="modal-content">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete</h5>
            <button type="button" class="btn-close btn-close-white" @click="showDeleteModal = false"></button>
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
              <span v-if="deleteLoading" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="fas fa-trash me-2"></i>
              Delete Price
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Price Detail Modal -->
    <div v-if="showDetailModal" class="modal-overlay" @click.self="showDetailModal = false">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-info-circle me-2"></i>
              {{ detailModalType === 'purchase' ? 'Purchase' : 'Sale' }} Price Details
            </h5>
            <button type="button" class="btn-close" @click="showDetailModal = false"></button>
          </div>
          <div class="modal-body">
            <div v-if="detailsLoading" class="text-center py-5">
              <div class="spinner-border text-primary mb-3"></div>
              <p class="text-muted">Loading price details...</p>
            </div>
            <div v-else>
              <!-- Item Information -->
              <div class="card mb-4">
                <div class="card-header">
                  <h6 class="mb-0"><i class="fas fa-box me-2"></i>Item Information</h6>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-6">
                      <div class="mb-2"><strong>Item Code:</strong> {{ selectedItemInfo?.item_code || '-' }}</div>
                      <div class="mb-2"><strong>Item Name:</strong> {{ selectedItemInfo?.name || '-' }}</div>
                    </div>
                    <div class="col-md-6">
                      <div class="mb-2"><strong>Category:</strong> {{ selectedItemInfo?.category?.name || '-' }}</div>
                      <div class="mb-2"><strong>Unit:</strong> {{ selectedItemInfo?.unitOfMeasure?.symbol || '-' }}</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Price Analysis -->
              <div class="card">
                <div class="card-header">
                  <h6 class="mb-0"><i class="fas fa-calculator me-2"></i>Price Analysis</h6>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                      <thead class="table-light">
                        <tr>
                          <th>Selection</th>
                          <th>{{ detailModalType === 'purchase' ? 'Vendor' : 'Customer' }}</th>
                          <th class="text-end">Quantity</th>
                          <th class="text-end">Unit Price</th>
                          <th class="text-end">Total Price</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-if="selectedDetailPrice" class="table-success">
                          <td><span class="badge bg-success">Selected</span></td>
                          <td>{{ getPartnerNameForDetail(selectedDetailPrice) }}</td>
                          <td class="text-end">{{ formatNumber(comparisonQuantity) }}</td>
                          <td class="text-end fw-semibold">{{ formatPrice(selectedDetailPrice.price) }}</td>
                          <td class="text-end fw-bold">{{ formatPrice(selectedDetailPrice.price * comparisonQuantity) }}</td>
                        </tr>
                        <tr v-for="(price, index) in availablePrices" :key="index">
                          <td><span class="badge bg-secondary">Alternative</span></td>
                          <td>{{ getPartnerNameForPrice(price) }}</td>
                          <td class="text-end">{{ formatNumber(comparisonQuantity) }}</td>
                          <td class="text-end">{{ formatPrice(price.price) }}</td>
                          <td class="text-end">{{ formatPrice(price.price * comparisonQuantity) }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <div class="alert alert-info mt-3">
                    <h6><i class="fas fa-lightbulb me-2"></i>Price Selection Logic</h6>
                    <p class="mb-0">
                      {{ detailModalType === 'purchase'
                         ? 'Purchase prices are prioritized: vendor-specific prices → general prices. Prices matching the exact quantity take precedence.'
                         : 'Sale prices are prioritized: customer-specific prices → general prices. Prices matching the exact quantity take precedence.' }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showDetailModal = false">
              <i class="fas fa-times me-2"></i>Close
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

      // Price List Tab
      selectedItemId: '',
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
      pricesInCurrencies: null,

      // Price Details Modal
      showDetailModal: false,
      detailModalType: null,
      detailsLoading: false,
      selectedDetailPrice: null,
      availablePrices: [],
      selectedItemInfo: null
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

    // Toast notification system
    showToast(type, message) {
      const toast = {
        id: ++this.toastId,
        type,
        message
      };
      this.toasts.push(toast);

      // Auto remove after 5 seconds
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
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
      };
      return icons[type] || icons.info;
    },

    getToastColor(type) {
      const colors = {
        success: '#28a745',
        error: '#dc3545',
        warning: '#ffc107',
        info: '#17a2b8'
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

    // Navigation
    switchTab(tab) {
      this.activeTab = tab;
      this.clearFilters();
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

    getPartnerNameForDetail(price) {
      if (this.detailModalType === 'purchase') {
        return this.getVendorName(price.vendor_id) || 'No specific vendor';
      } else {
        return this.getCustomerName(price.customer_id) || 'No specific customer';
      }
    },

    getPartnerNameForPrice(price) {
      if (this.detailModalType === 'purchase') {
        return this.getVendorName(price.vendor?.vendor_id) || 'No specific vendor';
      } else {
        return this.getCustomerName(price.customer?.customer_id) || 'No specific customer';
      }
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

    // Data Loading Functions
    async loadItems() {
      try {
        const response = await axios.get('items');
        this.items = response.data.data || [];
      } catch (error) {
        console.error('Failed to load items:', error);
        throw new Error('Could not load items');
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

        const [itemResponse, purchaseResponse, saleResponse, currenciesResponse] = await Promise.all([
          axios.get(`items/${this.comparisonItemId}`),
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

        this.selectedItemInfo = itemResponse.data.data;
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

      this.showPriceModal = true;
    },

    closePriceModal() {
      this.showPriceModal = false;
      this.formErrors = {};
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
    },

    // Price Details
    viewPurchasePriceDetails() {
      if (!this.purchasePrice) return;
      this.detailModalType = 'purchase';
      this.selectedDetailPrice = { ...this.purchasePrice };
      this.loadPriceDetails();
    },

    viewSalePriceDetails() {
      if (!this.salePrice) return;
      this.detailModalType = 'sale';
      this.selectedDetailPrice = { ...this.salePrice };
      this.loadPriceDetails();
    },

    async loadPriceDetails() {
      this.showDetailModal = true;
      this.detailsLoading = true;

      try {
        if (!this.comparisonItemId || !this.selectedDetailPrice) {
          this.availablePrices = [];
          return;
        }

        const response = await axios.get(`items/${this.comparisonItemId}/prices`, {
          params: { price_type: this.detailModalType, is_active: 1 }
        });

        const allPrices = response.data.data || [];
        this.availablePrices = allPrices.filter(price => {
          const selectedVendorId = this.selectedDetailPrice.vendor_id || this.selectedDetailPrice.vendor?.vendor_id;
          const selectedCustomerId = this.selectedDetailPrice.customer_id || this.selectedDetailPrice.customer?.customer_id;
          const priceVendorId = price.vendor_id || price.vendor?.vendor_id;
          const priceCustomerId = price.customer_id || price.customer?.customer_id;

          if (selectedVendorId && priceVendorId) {
            return priceVendorId !== selectedVendorId;
          }
          if (selectedCustomerId && priceCustomerId) {
            return priceCustomerId !== selectedCustomerId;
          }
          if (!selectedVendorId && !selectedCustomerId) {
            return !(!priceVendorId && !priceCustomerId);
          }
          return true;
        });

      } catch (error) {
        console.error('Failed to load price details:', error);
        this.availablePrices = [];
        this.showToast('error', 'Could not load price details.');
      } finally {
        this.detailsLoading = false;
      }
    }
  }
};
</script>

<style scoped>
/* Enhanced styling for better UX */
.page-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 2rem;
  border-radius: 0.5rem;
  margin-bottom: 2rem;
}

.page-header h1 {
  color: white;
  margin-bottom: 0.5rem;
}

.nav-tabs-enhanced {
  border: none;
  background: #f8f9fa;
  border-radius: 0.5rem;
  padding: 0.25rem;
}

.nav-tabs-enhanced .nav-link {
  border: none;
  border-radius: 0.375rem;
  color: #6c757d;
  font-weight: 500;
  transition: all 0.3s ease;
}

.nav-tabs-enhanced .nav-link:hover {
  background: rgba(255, 255, 255, 0.5);
  color: #495057;
}

.nav-tabs-enhanced .nav-link.active {
  background: white;
  color: #495057;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.card {
  border: none;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  border-radius: 0.5rem;
  margin-bottom: 1.5rem;
}

.card-header {
  background: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
  font-weight: 600;
}

.table th.sortable {
  cursor: pointer;
  user-select: none;
  transition: background-color 0.2s ease;
}

.table th.sortable:hover {
  background-color: rgba(0, 0, 0, 0.05);
}

.empty-state, .loading-container {
  padding: 3rem 1rem;
}

.empty-state i {
  opacity: 0.5;
}

.badge {
  font-weight: 500;
  padding: 0.5em 0.75em;
}

.btn-group-sm .btn {
  padding: 0.25rem 0.5rem;
}

/* Toast notifications */
.toast-container {
  z-index: 1060;
}

.toast {
  background: white;
  border: none;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

/* Modal enhancements */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  z-index: 1050;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(2px);
}

.modal-dialog {
  margin: 1rem;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-content {
  border: none;
  border-radius: 0.5rem;
  box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.175);
}

.modal-header {
  border-bottom: 1px solid #dee2e6;
  background: #f8f9fa;
}

.modal-footer {
  border-top: 1px solid #dee2e6;
  background: #f8f9fa;
}

/* Form enhancements */
.form-label.fw-semibold {
  color: #495057;
  margin-bottom: 0.5rem;
}

.form-control:focus, .form-select:focus {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.is-invalid {
  border-color: #dc3545;
}

.invalid-feedback {
  display: block;
}

/* Responsive improvements */
@media (max-width: 768px) {
  .page-header {
    padding: 1rem;
    text-align: center;
  }

  .modal-dialog {
    margin: 0.5rem;
  }

  .table-responsive {
    font-size: 0.875rem;
  }

  .btn-group .btn {
    padding: 0.25rem 0.5rem;
  }
}

/* Animation improvements */
.modal-overlay {
  animation: fadeIn 0.3s ease-out;
}

.modal-content {
  animation: slideIn 0.3s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-50px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.toast {
  animation: slideInRight 0.3s ease-out;
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(100%);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

/* Loading states */
.spinner-border {
  width: 2rem;
  height: 2rem;
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
}

/* Enhanced table styling */
.table-hover tbody tr:hover {
  background-color: rgba(0, 0, 0, 0.025);
}

.table-striped tbody tr:nth-of-type(odd) {
  background-color: rgba(0, 0, 0, 0.02);
}

/* Button improvements */
.btn {
  font-weight: 500;
  transition: all 0.2s ease;
}

.btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
}

.btn:active {
  transform: translateY(0);
}

/* Price comparison cards */
.display-4 {
  font-size: 2.5rem;
  font-weight: 700;
}

.border-info {
  border-color: #b6effb !important;
}

.border-success {
  border-color: #badbcc !important;
}
</style>
