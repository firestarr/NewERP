<!-- src/views/purchasing/PurchaseOrderDetail.vue -->
<template>
  <div class="po-detail-container">
    <div class="page-header">
      <h1>Purchase Order Details</h1>
      <div class="action-buttons">
        <router-link to="/purchasing/orders" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i> Back to List
        </router-link>
        <div class="btn-group">
          <button
            v-if="purchaseOrder.status === 'draft'"
            @click="openCurrencyModal"
            class="btn btn-info"
          >
            <i class="fas fa-exchange-alt"></i> Convert Currency
          </button>
        </div>
        <div class="btn-group">
          <button
            v-if="purchaseOrder.status === 'draft'"
            @click="updateStatus('submitted')"
            class="btn btn-primary"
          >
            <i class="fas fa-paper-plane"></i> Submit
          </button>
          <button
            v-if="purchaseOrder.status === 'submitted'"
            @click="updateStatus('approved')"
            class="btn btn-success"
          >
            <i class="fas fa-check"></i> Approve
          </button>
          <button
            v-if="purchaseOrder.status === 'approved'"
            @click="updateStatus('sent')"
            class="btn btn-info"
          >
            <i class="fas fa-envelope"></i> Mark as Sent
          </button>
          <button
            v-if="['sent', 'partial'].includes(purchaseOrder.status)"
            @click="createGoodsReceipt()"
            class="btn btn-primary"
          >
            <i class="fas fa-truck-loading"></i> Create Receipt
          </button>
        </div>
        <div class="btn-group">
          <router-link
            v-if="purchaseOrder.status === 'draft'"
            :to="`/purchasing/orders/${purchaseOrder.po_id}/edit`"
            class="btn btn-warning"
          >
            <i class="fas fa-edit"></i> Edit
          </router-link>
          <button
            @click="printPurchaseOrder()"
            class="btn btn-secondary"
          >
            <i class="fas fa-print"></i> Print
          </button>
        </div>
      </div>
    </div>

    <div v-if="isLoading" class="loading-container">
      <div class="spinner-border text-primary" role="status">
        <span class="sr-only">Loading...</span>
      </div>
      <p class="loading-text">Loading purchase order data...</p>
    </div>

    <div v-else class="po-detail-content">
      <div class="po-layout">
        <!-- Left section: Purchase Order Information and Items -->
        <div class="po-main-content">
          <div class="card po-info-card">
            <div class="card-header">
              <h2 class="card-title">Purchase Order Information</h2>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-6">
                  <table class="table table-sm table-borderless detail-table">
                    <tbody>
                      <tr>
                        <th>PO Number:</th>
                        <td>{{ purchaseOrder.po_number }}</td>
                      </tr>
                      <tr>
                        <th>PO Date:</th>
                        <td>{{ formatDate(purchaseOrder.po_date) }}</td>
                      </tr>
                      <tr>
                        <th>Expected Delivery:</th>
                        <td>{{ formatDate(purchaseOrder.expected_delivery) || 'Not specified' }}</td>
                      </tr>
                      <tr>
                        <th>Payment Terms:</th>
                        <td>{{ purchaseOrder.payment_terms || 'Not specified' }}</td>
                      </tr>
                      <tr>
                        <th>Delivery Terms:</th>
                        <td>{{ purchaseOrder.delivery_terms || 'Not specified' }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div class="col-md-6">
                  <table class="table table-sm table-borderless detail-table">
                    <tbody>
                      <tr>
                        <th>Vendor:</th>
                        <td>{{ purchaseOrder.vendor ? purchaseOrder.vendor.name : 'Unknown' }}</td>
                      </tr>
                      <tr>
                        <th>Vendor Contact:</th>
                        <td>{{ purchaseOrder.vendor ? purchaseOrder.vendor.contact_person : 'Unknown' }}</td>
                      </tr>
                      <tr>
                        <th>Vendor Email:</th>
                        <td>{{ purchaseOrder.vendor ? purchaseOrder.vendor.email : 'Unknown' }}</td>
                      </tr>
                      <tr>
                        <th>Vendor Phone:</th>
                        <td>{{ purchaseOrder.vendor ? purchaseOrder.vendor.phone : 'Unknown' }}</td>
                      </tr>
                      <tr>
                        <th>Currency:</th>
                        <td>{{ purchaseOrder.currency_code || 'USD' }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <div class="card po-items-card">
            <div class="card-header">
              <div class="card-header-content">
                <h2 class="card-title">Purchase Order Items</h2>
                <span class="status-badge" :class="getStatusBadgeClass(purchaseOrder.status)">
                  {{ purchaseOrder.status }}
                </span>
              </div>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered">
                  <thead>
                    <tr>
                      <th class="item-number-col">#</th>
                      <th class="item-name-col">Item</th>
                      <th class="item-quantity-col">Quantity</th>
                      <th class="item-price-col">Unit Price</th>
                      <th class="item-subtotal-col">Subtotal</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(line, index) in purchaseOrder.lines" :key="line.line_id">
                      <td>{{ index + 1 }}</td>
                      <td>
                        {{ line.item ? line.item.name : 'Unknown Item' }}
                        <small class="text-muted item-code">
                          {{ line.item ? line.item.item_code : '' }}
                        </small>
                      </td>
                      <td>
                        {{ formatNumber(line.quantity) }}
                        {{ line.unitOfMeasure ? line.unitOfMeasure.name : '' }}
                      </td>
                      <td>{{ formatCurrency(line.unit_price) }}</td>
                      <td>{{ formatCurrency(line.subtotal) }}</td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr>
                      <td colspan="4" class="text-right font-weight-bold">Subtotal:</td>
                      <td>{{ formatCurrency(calculateSubtotal()) }}</td>
                    </tr>
                    <tr>
                      <td colspan="4" class="text-right font-weight-bold">Tax:</td>
                      <td>{{ formatCurrency(purchaseOrder.tax_amount) }}</td>
                    </tr>
                    <tr>
                      <td colspan="4" class="text-right font-weight-bold">Total:</td>
                      <td class="font-weight-bold">{{ formatCurrency(purchaseOrder.total_amount) }}</td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Right sidebar: Status, Receipt and Outstanding Info -->
        <div class="po-sidebar">
          <div class="card status-card">
            <div class="card-header">
              <h2 class="card-title">Status Information</h2>
            </div>
            <div class="card-body">
              <div class="status-timeline">
                <div
                  v-for="status in statusTimeline"
                  :key="status.name"
                  class="timeline-item"
                  :class="{ 'active': isStatusActive(status.name), 'completed': isStatusCompleted(status.name) }"
                >
                  <div class="timeline-marker"></div>
                  <div class="timeline-content">
                    <h3 class="timeline-title">{{ status.label }}</h3>
                    <p v-if="isStatusCompleted(status.name) && status.timestamp" class="timeline-date">
                      {{ formatDate(status.timestamp) }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- FIXED RECEIPT CARD -->
          <div class="card receipt-card">
            <div class="card-header">
              <div class="card-header-content">
                <h2 class="card-title">Receipt Information</h2>
                <div class="header-actions">
                  <button
                    @click="refreshReceipts"
                    class="btn btn-sm btn-outline-secondary"
                    :disabled="receiptsLoading"
                    title="Refresh receipts"
                  >
                    <i class="fas fa-sync-alt" :class="{ 'fa-spin': receiptsLoading }"></i>
                  </button>
                  <!-- Debug button (remove in production) -->
                  <button
                    @click="debugReceiptsDetailed"
                    class="btn btn-sm btn-outline-info ml-1"
                    title="Debug receipts data"
                  >
                    <i class="fas fa-bug"></i>
                  </button>
                </div>
              </div>
            </div>
            <div class="card-body">
              <!-- Loading state untuk receipts -->
              <div v-if="receiptsLoading" class="text-center py-3">
                <div class="spinner-border spinner-border-sm" role="status">
                  <span class="sr-only">Loading receipts...</span>
                </div>
                <p class="text-muted mt-2 mb-0">Refreshing receipts...</p>
              </div>

              <!-- Receipt data exists -->
              <div v-else-if="hasReceipts">
                <div class="table-responsive">
                  <table class="table table-sm receipt-table">
                    <thead>
                      <tr>
                        <th>Receipt #</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(receipt, index) in uniqueReceipts" :key="`receipt-${receipt.receipt_id}-${index}`">
                        <td>
                          <strong>{{ receipt.receipt_number }}</strong>
                          <br>
                          <!-- <small class="text-muted">ID: {{ receipt.receipt_id }}</small> -->
                        </td>
                        <td>{{ formatDate(receipt.receipt_date) }}</td>
                        <td>
                          <span class="status-badge" :class="getStatusBadgeClass(receipt.status)">
                            {{ receipt.status }}
                          </span>
                        </td>
                        <td>
                          <div class="btn-group">
                            <router-link
                              :to="`/purchasing/goods-receipts/${receipt.receipt_id}`"
                              class="btn btn-sm btn-info"
                              title="View Receipt Details"
                            >
                              <i class="fas fa-eye"></i>
                            </router-link>
                            <button
                              v-if="receipt.status === 'draft'"
                              @click="editReceipt(receipt.receipt_id)"
                              class="btn btn-sm btn-warning"
                              title="Edit Receipt"
                            >
                              <i class="fas fa-edit"></i>
                            </button>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- Summary info -->
                <div class="receipt-summary mt-3">
                  <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                      <i class="fas fa-info-circle"></i>
                      <!-- {{ uniqueReceipts.length }} receipt(s) found -->
                    </small>
                  </div>

                  <!-- Show warning if duplicates detected -->
                  <div v-if="totalReceipts > uniqueReceipts.length" class="alert alert-warning mt-2 py-2">
                    <i class="fas fa-exclamation-triangle"></i>
                    <small>
                      Warning: {{ totalReceipts - uniqueReceipts.length }} duplicate receipt(s) detected and filtered out.
                    </small>
                  </div>
                </div>
              </div>

              <!-- No receipts state -->
              <div v-else class="empty-state">
                <i class="fas fa-inbox empty-icon"></i>
                <p class="empty-text">No receipts created yet</p>

                <!-- Action button untuk create receipt -->
                <div class="mt-3">
                  <button
                    v-if="['sent', 'partial'].includes(purchaseOrder.status)"
                    @click="createGoodsReceipt()"
                    class="btn btn-primary"
                  >
                    <i class="fas fa-plus"></i> Create Receipt
                  </button>
                  <p v-else class="text-muted small mt-2 mb-0">
                    Purchase order must be in 'sent' or 'partial' status to create receipts.
                  </p>
                </div>
              </div>
            </div>
          </div>

          <div class="card outstanding-card">
            <div class="card-header">
              <h2 class="card-title">Outstanding Items</h2>
            </div>
            <div class="card-body">
              <div v-if="outstandingItems.length > 0">
                <div class="table-responsive">
                  <table class="table table-sm">
                    <thead>
                      <tr>
                        <th>Item</th>
                        <th class="text-center">Ordered</th>
                        <th class="text-center">Received</th>
                        <th class="text-center">Outstanding</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="item in outstandingItems" :key="item.line_id">
                        <td class="item-name">{{ item.item_name }}</td>
                        <td class="text-center">{{ formatNumber(item.ordered_quantity) }}</td>
                        <td class="text-center">{{ formatNumber(item.received_quantity) }}</td>
                        <td class="text-center font-weight-bold">{{ formatNumber(item.outstanding_quantity) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <div v-else class="empty-state">
                <i class="fas fa-check-circle completed-icon"></i>
                <p class="empty-text">All items have been received</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Status Update Confirmation Modal -->
    <div v-if="showStatusModal" class="modal-backdrop">
      <div class="modal-container">
        <div class="modal-header">
          <h5 class="modal-title">Update Purchase Order Status</h5>
          <button type="button" class="modal-close" @click="showStatusModal = false">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          Are you sure you want to change the status of this purchase order to <strong>{{ newStatus }}</strong>?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="showStatusModal = false">Cancel</button>
          <button type="button" class="btn btn-primary" @click="confirmStatusUpdate()">Update Status</button>
        </div>
      </div>
    </div>

    <!-- Currency Conversion Modal -->
    <div v-if="showCurrencyModal" class="modal-backdrop">
      <div class="modal-container currency-modal">
        <div class="modal-header">
          <h5 class="modal-title">Convert Currency</h5>
          <button type="button" class="modal-close" @click="showCurrencyModal = false">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <p class="current-currency">
            Current currency: <strong>{{ purchaseOrder.currency_code || 'USD' }}</strong>
          </p>
          <div class="form-group">
            <label>New Currency</label>
            <select v-model="newCurrency" class="form-control">
              <option value="USD">USD - US Dollar</option>
              <option value="EUR">EUR - Euro</option>
              <option value="GBP">GBP - British Pound</option>
              <option value="IDR">IDR - Indonesian Rupiah</option>
              <option value="JPY">JPY - Japanese Yen</option>
              <option value="CNY">CNY - Chinese Yuan</option>
              <option value="SGD">SGD - Singapore Dollar</option>
            </select>
          </div>
          <div class="form-check">
            <input type="checkbox" class="form-check-input" id="useExchangeRateDate" v-model="useExchangeRateDate">
            <label class="form-check-label" for="useExchangeRateDate">
              Use exchange rate from PO date ({{ formatDate(purchaseOrder.po_date) }})
            </label>
          </div>
          <div class="alert alert-info currency-info">
            <i class="fas fa-info-circle"></i>
            Converting currency will recalculate all amounts based on the current exchange rate. This cannot be undone.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="showCurrencyModal = false">Cancel</button>
          <button type="button" class="btn btn-primary" @click="convertCurrency()">Convert Currency</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'PurchaseOrderDetail',
  data() {
    return {
      isLoading: true,
      receiptsLoading: false,
      purchaseOrder: {},
      outstandingItems: [],
      showStatusModal: false,
      showCurrencyModal: false,
      newStatus: '',
      newCurrency: 'USD',
      useExchangeRateDate: true,
      statusTimeline: [
        { name: 'draft', label: 'Draft', timestamp: null },
        { name: 'submitted', label: 'Submitted', timestamp: null },
        { name: 'approved', label: 'Approved', timestamp: null },
        { name: 'sent', label: 'Sent to Vendor', timestamp: null },
        { name: 'partial', label: 'Partially Received', timestamp: null },
        { name: 'received', label: 'Fully Received', timestamp: null },
        { name: 'completed', label: 'Completed', timestamp: null }
      ]
    };
  },
  computed: {
    // Get receipts data dengan fallback ke berbagai property name
    receiptsData() {
      return this.purchaseOrder.goods_receipts ||
             this.purchaseOrder.goodsReceipts ||
             this.purchaseOrder.receipts ||
             [];
    },

    // Remove duplicate receipts berdasarkan receipt_id
    uniqueReceipts() {
      const receipts = this.receiptsData;
      if (!Array.isArray(receipts)) return [];

      // Create Map untuk remove duplicates berdasarkan receipt_id
      const uniqueMap = new Map();

      receipts.forEach(receipt => {
        const key = receipt.receipt_id;
        if (!uniqueMap.has(key)) {
          uniqueMap.set(key, receipt);
        }
      });

      // Convert Map back to array dan sort by date (newest first)
      return Array.from(uniqueMap.values()).sort((a, b) => {
        const dateA = new Date(a.receipt_date);
        const dateB = new Date(b.receipt_date);
        return dateB - dateA; // Descending order
      });
    },

    // Total receipts including duplicates untuk debugging
    totalReceipts() {
      return this.receiptsData.length;
    },

    // Check if receipts exist
    hasReceipts() {
      return this.uniqueReceipts.length > 0;
    }
  },
  created() {
    const poId = this.$route.params.id;
    if (poId) {
      this.loadPurchaseOrder(poId);
    } else {
      this.$router.push('/purchasing/orders');
    }
  },
  methods: {
    async loadPurchaseOrder(poId) {
      this.isLoading = true;
      try {
        const response = await axios.get(`/purchase-orders/${poId}`);

        if (response.data.status === 'success') {
          this.purchaseOrder = response.data.data;

          // Normalisasi property names - convert snake_case to camelCase jika diperlukan
          if (this.purchaseOrder.goods_receipts && !this.purchaseOrder.goodsReceipts) {
            this.purchaseOrder.goodsReceipts = this.purchaseOrder.goods_receipts;
          }

        //   // Debug logging
        //   console.log('Purchase Order loaded:', this.purchaseOrder);
        //   console.log('Raw goods_receipts:', this.purchaseOrder.goods_receipts);
        //   console.log('Processed receipts:', this.receiptsData);
        //   console.log('Unique receipts:', this.uniqueReceipts);

          // Set default currency for conversion
          this.newCurrency = this.purchaseOrder.currency_code || 'USD';

          // Load outstanding items
          this.loadOutstandingItems(poId);
        }
      } catch (error) {
        console.error('Error loading purchase order:', error);
        this.showNotification('Failed to load purchase order data', 'error');
      } finally {
        this.isLoading = false;
      }
    },

    async loadOutstandingItems(poId) {
      try {
        const response = await axios.get(`/purchase-orders/${poId}/outstanding`);

        if (response.data.status === 'success') {
          this.outstandingItems = response.data.data.outstanding_lines || [];
        }
      } catch (error) {
        console.error('Error loading outstanding items:', error);
      }
    },

    // Method untuk refresh receipts
    async refreshReceipts() {
      if (!this.purchaseOrder.po_id) return;

      this.receiptsLoading = true;
      try {
        await this.loadGoodsReceipts(this.purchaseOrder.po_id);
        this.showNotification('Receipts refreshed successfully', 'success');
      } catch (error) {
        this.showNotification('Failed to refresh receipts', 'error');
      } finally {
        this.receiptsLoading = false;
      }
    },

    // Method untuk load receipts secara terpisah
    async loadGoodsReceipts(poId) {
      try {
        const response = await axios.get(`/purchase-orders/${poId}/goods-receipts`);

        if (response.data.status === 'success') {
          // Update receipts data
          this.$set(this.purchaseOrder, 'goods_receipts', response.data.data);
        //   console.log('Receipts loaded separately:', this.purchaseOrder.goods_receipts);
        }
      } catch (error) {
        console.error('Error loading goods receipts:', error);

        // Jika endpoint tidak ada, reload full PO data
        if (error.response?.status === 404) {
        //   console.log('Goods receipts endpoint not found, reloading full PO data');
          await this.loadPurchaseOrder(poId);
        }
      }
    },

    // Method untuk debugging receipts
    debugReceiptsDetailed() {
      const debug = {
        purchaseOrder: this.purchaseOrder,
        receiptsData: this.receiptsData,
        uniqueReceipts: this.uniqueReceipts,
        totalReceipts: this.totalReceipts,
        hasReceipts: this.hasReceipts,
        propertyNames: {
          goods_receipts: !!this.purchaseOrder.goods_receipts,
          goodsReceipts: !!this.purchaseOrder.goodsReceipts,
          receipts: !!this.purchaseOrder.receipts
        }
      };

      // console.group('Receipt Debug Info');
    //   console.log('Debug Data:', debug);
    //   console.log('Raw receipts data:', this.receiptsData);
    //   console.log('Unique receipts:', this.uniqueReceipts);
      // console.groupEnd();

      // Show in alert for quick check
      this.showNotification(`
Debug Info:
- Total receipts (raw): ${this.totalReceipts}
- Unique receipts: ${this.uniqueReceipts.length}
- Has receipts: ${this.hasReceipts}
- Properties available: ${Object.keys(debug.propertyNames).filter(k => debug.propertyNames[k]).join(', ')}

Check console for detailed info.
      `, 'info');
    },

    calculateSubtotal() {
      if (!this.purchaseOrder.lines) return 0;
      return this.purchaseOrder.lines.reduce((sum, line) => sum + (line.subtotal || 0), 0);
    },

    formatDate(dateString) {
      if (!dateString) return '-';
      const date = new Date(dateString);
      return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    },

    formatCurrency(amount) {
      if (amount === null || amount === undefined) return '-';
      return new Intl.NumberFormat('id-ID', {
        style: 'decimal',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(amount);
    },

    formatNumber(number) {
      if (number === null || number === undefined) return '-';
      return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
      }).format(number);
    },

    getStatusBadgeClass(status) {
      const statusClasses = {
        'draft': 'badge-secondary',
        'submitted': 'badge-info',
        'approved': 'badge-primary',
        'sent': 'badge-warning',
        'partial': 'badge-info',
        'received': 'badge-success',
        'completed': 'badge-success',
        'canceled': 'badge-danger',
        'confirmed': 'badge-success'
      };

      return `badge ${statusClasses[status] || 'badge-secondary'}`;
    },

    isStatusActive(status) {
      return this.purchaseOrder.status === status;
    },

    isStatusCompleted(status) {
      const statusOrder = ['draft', 'submitted', 'approved', 'sent', 'partial', 'received', 'completed'];
      const currentIndex = statusOrder.indexOf(this.purchaseOrder.status);
      const statusIndex = statusOrder.indexOf(status);

      return statusIndex <= currentIndex;
    },

    updateStatus(status) {
      this.newStatus = status;
      this.showStatusModal = true;
    },

    async confirmStatusUpdate() {
      try {
        const response = await axios.patch(
          `/purchase-orders/${this.purchaseOrder.po_id}/status`,
          { status: this.newStatus }
        );

        if (response.data.status === 'success') {
          // Update local data
          this.purchaseOrder.status = this.newStatus;

          // Show success message
          this.showNotification(`Purchase order status updated to ${this.newStatus}`, 'success');

          // Reload the purchase order to get the latest data
          this.loadPurchaseOrder(this.purchaseOrder.po_id);
        } else {
          this.showNotification('Failed to update purchase order status', 'error');
        }
      } catch (error) {
        console.error('Error updating status:', error);

        // Show error message
        const message = error.response?.data?.message || 'An error occurred while updating the purchase order status';
        this.showNotification(`Error: ${message}`, 'error');
      } finally {
        this.showStatusModal = false;
      }
    },

    openCurrencyModal() {
      this.newCurrency = this.purchaseOrder.currency_code || 'USD';
      this.useExchangeRateDate = true;
      this.showCurrencyModal = true;
    },

    async convertCurrency() {
      // Don't convert if selected currency is the same as current
      if (this.newCurrency === this.purchaseOrder.currency_code) {
        this.showNotification('Selected currency is the same as current currency', 'warning');
        this.showCurrencyModal = false;
        return;
      }

      try {
        const response = await axios.post(
          `/purchase-orders/${this.purchaseOrder.po_id}/convert-currency`,
          {
            currency_code: this.newCurrency,
            use_exchange_rate_date: this.useExchangeRateDate
          }
        );

        if (response.data.status === 'success') {
          // Update local data
          this.purchaseOrder = response.data.data;

          // Show success message
          this.showNotification(`Purchase order currency converted to ${this.newCurrency}`, 'success');
        }
      } catch (error) {
        console.error('Error converting currency:', error);

        // Show error message
        const message = error.response?.data?.message || 'An error occurred while converting the currency';
        this.showNotification(`Error: ${message}`, 'error');
      } finally {
        this.showCurrencyModal = false;
      }
    },

    createGoodsReceipt() {
      this.$router.push({
        path: `/purchasing/goods-receipts/create`,
        query: {
          po_id: this.purchaseOrder.po_id,
          return_to: this.$route.fullPath
        }
      });
    },

    editReceipt(receiptId) {
      this.$router.push(`/purchasing/goods-receipts/${receiptId}/edit`);
    },

    printPurchaseOrder() {
      this.$router.push({
        name: 'PrintPurchaseOrder',
        params: { id: this.purchaseOrder.po_id },
        query: { autoprint: 'false' }
      });
    },

    // Utility method untuk menampilkan notifikasi
    showNotification(message, type = 'info') {
      // Implementasi bisa disesuaikan dengan notification system yang digunakan
      // Untuk sementara menggunakan alert
      if (type === 'error') {
        alert(`Error: ${message}`);
      } else if (type === 'success') {
        alert(`Success: ${message}`);
      } else if (type === 'warning') {
        alert(`Warning: ${message}`);
      } else {
        alert(message);
      }
    }
  }
};
</script>

<style scoped>
/* ============================================
   CORE LAYOUT & CONTAINER STYLES
   ============================================ */
.po-detail-container {
  margin-bottom: 2rem;
  color: var(--gray-800);
}

/* Page header with title and actions */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--gray-200);
}

.page-header h1 {
  margin: 0;
  color: var(--gray-900);
  font-weight: 600;
}

/* Main content area with side-by-side layout */
.po-detail-content {
  margin-top: 1.5rem;
}

.po-layout {
  display: flex;
  gap: 1.5rem;
}

.po-main-content {
  flex: 1;
  min-width: 0; /* Prevents flex item from overflowing */
}

.po-sidebar {
  width: 350px;
  flex-shrink: 0;
}

/* Loading state */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 0;
  text-align: center;
}

.loading-text {
  margin-top: 1rem;
  color: var(--gray-600);
}

/* Row and column grid for internal layouts */
.row {
  display: flex;
  flex-wrap: wrap;
  margin-right: -0.75rem;
  margin-left: -0.75rem;
}

.col-md-6 {
  padding-right: 0.75rem;
  padding-left: 0.75rem;
  width: 100%;
}

@media (min-width: 768px) {
  .col-md-6 {
    flex: 0 0 50%;
    max-width: 50%;
  }
}

.mt-2 {
  margin-top: 0.5rem;
}

.mt-3 {
  margin-top: 1rem;
}

.mt-4 {
  margin-top: 1.5rem;
}

.py-2 {
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
}

.py-3 {
  padding-top: 1rem;
  padding-bottom: 1rem;
}

.mb-0 {
  margin-bottom: 0;
}

.ml-1 {
  margin-left: 0.25rem;
}

.text-right {
  text-align: right;
}

.font-weight-bold {
  font-weight: 600;
}

.text-muted {
  color: var(--gray-500);
}

.text-success {
  color: #28a745;
}

.text-center {
  text-align: center;
}

.d-flex {
  display: flex;
}

.justify-content-between {
  justify-content: space-between;
}

.align-items-center {
  align-items: center;
}

.small {
  font-size: 0.875rem;
}

/* ============================================
   ACTION BUTTONS & CONTROLS
   ============================================ */
.action-buttons {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

/* Add extra margin-left to the btn-group containing the Print button to increase spacing */
.action-buttons > .btn-group:last-child {
  margin-left: 2rem;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.375rem 0.75rem;
  border-radius: 0.25rem;
  font-weight: 500;
  font-size: 0.875rem;
  line-height: 1.5;
  text-align: center;
  vertical-align: middle;
  cursor: pointer;
  user-select: none;
  border: 1px solid transparent;
  text-decoration: none;
  transition:
    color 0.15s ease-in-out,
    background-color 0.15s ease-in-out,
    border-color 0.15s ease-in-out,
    box-shadow 0.15s ease-in-out;
}

.btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.btn i {
  margin-right: 0.5rem;
}

.btn-primary {
  color: #fff;
  background-color: #0d6efd;
  border: 1px solid #0d6efd;
}

.btn-primary:hover:not(:disabled) {
  background-color: #0b5ed7;
  border-color: #0a58ca;
  color: #fff;
  text-decoration: none;
}

.btn-secondary {
  color: #fff;
  background-color: #6c757d;
  border: 1px solid #6c757d;
}

.btn-secondary:hover:not(:disabled) {
  background-color: #5c636a;
  border-color: #565e64;
  color: #fff;
  text-decoration: none;
}

.btn-info {
  color: #fff;
  background-color: #0dcaf0;
  border: 1px solid #0dcaf0;
}

.btn-info:hover:not(:disabled) {
  background-color: #31d2f2;
  border-color: #25cff2;
  color: #fff;
  text-decoration: none;
}

.btn-success {
  color: #fff;
  background-color: #198754;
  border: 1px solid #198754;
}

.btn-success:hover:not(:disabled) {
  background-color: #157347;
  border-color: #146c43;
  color: #fff;
  text-decoration: none;
}

.btn-warning {
  color: #000;
  background-color: #ffc107;
  border: 1px solid #ffc107;
}

.btn-warning:hover:not(:disabled) {
  background-color: #ffca2c;
  border-color: #ffc720;
  color: #000;
  text-decoration: none;
}

.btn-outline-secondary {
  color: #6c757d;
  background-color: transparent;
  border: 1px solid #6c757d;
}

.btn-outline-secondary:hover:not(:disabled) {
  background-color: #6c757d;
  border-color: #6c757d;
  color: #fff;
}

.btn-outline-info {
  color: #0dcaf0;
  background-color: transparent;
  border: 1px solid #0dcaf0;
}

.btn-outline-info:hover:not(:disabled) {
  background-color: #0dcaf0;
  border-color: #0dcaf0;
  color: #fff;
}

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
}

.btn-group {
  display: inline-flex;
  margin-right: 1rem;
}

.btn-group .btn:not(:last-child) {
  margin-right: 0.25rem;
}

/* Spinner for loading states */
.spinner-border {
  display: inline-block;
  width: 2rem;
  height: 2rem;
  border: 0.25rem solid currentColor;
  border-right-color: transparent;
  border-radius: 50%;
  animation: spinner-border 0.75s linear infinite;
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
  border-width: 0.125rem;
}

@keyframes spinner-border {
  to { transform: rotate(360deg); }
}

.fa-spin {
  animation: fa-spin 2s infinite linear;
}

@keyframes fa-spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* ============================================
   CARD STYLES
   ============================================ */
.card {
  position: relative;
  display: flex;
  flex-direction: column;
  min-width: 0;
  word-wrap: break-word;
  background-color: #fff;
  background-clip: border-box;
  border: 1px solid var(--gray-200);
  border-radius: 0.5rem;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
  margin-bottom: 1.5rem;
  overflow: hidden;
}

.card-header {
  padding: 1rem 1.25rem;
  background-color: rgba(0, 0, 0, 0.02);
  border-bottom: 1px solid var(--gray-200);
}

.card-title {
  margin-bottom: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--gray-900);
}

.card-body {
  flex: 1 1 auto;
  padding: 1.25rem;
}

/* Header with status badge and actions */
.card-header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-actions {
  display: flex;
  align-items: center;
}

/* ============================================
   TABLE STYLES
   ============================================ */
.table-responsive {
  display: block;
  width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.table {
  width: 100%;
  max-width: 100%;
  margin-bottom: 1rem;
  color: var(--gray-900);
  border-collapse: collapse;
}

.table th,
.table td {
  padding: 0.75rem;
  vertical-align: top;
  border-top: 1px solid var(--gray-200);
}

.table thead th {
  vertical-align: bottom;
  border-bottom: 2px solid var(--gray-200);
  font-weight: 600;
  color: var(--gray-700);
  background-color: var(--gray-50);
}

.table-sm th,
.table-sm td {
  padding: 0.3rem;
}

.table-bordered {
  border: 1px solid var(--gray-200);
}

.table-bordered th,
.table-bordered td {
  border: 1px solid var(--gray-200);
}

.detail-table {
  margin-bottom: 0;
}

.detail-table th {
  width: 40%;
  font-weight: 600;
  color: var(--gray-700);
  border-top: none;
}

.detail-table td {
  color: var(--gray-900);
  border-top: none;
}

.table-sm.detail-table th,
.table-sm.detail-table td {
  padding: 0.4rem 0;
}

.table-borderless th,
.table-borderless td {
  border: 0;
}

.item-number-col {
  width: 5%;
}

.item-name-col {
  width: 40%;
}

.item-quantity-col,
.item-price-col {
  width: 15%;
}

.item-subtotal-col {
  width: 25%;
}

.item-code {
  display: block;
  font-size: 0.85rem;
  color: var(--gray-500);
}

/* Sidebar tables */
.po-sidebar .table th {
  font-size: 0.8125rem;
}

.po-sidebar .table td {
  font-size: 0.8125rem;
}

.receipt-table th,
.outstanding-table th {
  white-space: nowrap;
}

/* Outstanding items table improvements */
.outstanding-card .table-responsive {
  margin: 0;
  overflow-x: auto;
}

.outstanding-card .table {
  margin-bottom: 0;
  white-space: nowrap;
}

.outstanding-card th,
.outstanding-card td {
  padding: 0.5rem;
  vertical-align: middle;
}

.item-name {
  white-space: normal;
  word-break: break-word;
  min-width: 120px;
}

/* ============================================
   STATUS BADGES & INDICATORS
   ============================================ */
.status-badge {
  display: inline-block;
  padding: 0.35em 0.65em;
  font-size: 0.75em;
  font-weight: 600;
  line-height: 1;
  text-align: center;
  white-space: nowrap;
  vertical-align: baseline;
  border-radius: 0.375rem;
  text-transform: capitalize;
  letter-spacing: 0.025em;
}

.badge-secondary {
  color: #fff;
  background-color: #6c757d;
}

.badge-info {
  color: #fff;
  background-color: #0dcaf0;
}

.badge-primary {
  color: #fff;
  background-color: #0d6efd;
}

.badge-warning {
  color: #000;
  background-color: #ffc107;
}

.badge-success {
  color: #fff;
  background-color: #198754;
}

.badge-danger {
  color: #fff;
  background-color: #dc3545;
}

/* ============================================
   TIMELINE STYLES
   ============================================ */
.status-timeline {
  position: relative;
  padding-left: 1.5rem;
  padding-top: 0.25rem;
  padding-bottom: 0.25rem;
}

.status-timeline:before {
  content: '';
  position: absolute;
  top: 0;
  bottom: 0;
  left: 0.6rem;
  width: 2px;
  background-color: var(--gray-200);
}

.timeline-item {
  position: relative;
  padding-bottom: 1.5rem;
}

.timeline-item:last-child {
  padding-bottom: 0;
}

.timeline-marker {
  position: absolute;
  top: 0.25rem;
  left: -1.5rem;
  width: 1rem;
  height: 1rem;
  border-radius: 50%;
  background-color: var(--gray-200);
  border: 2px solid #fff;
  z-index: 5;
  transition: background-color 0.3s;
}

.timeline-item.active .timeline-marker {
  background-color: #0d6efd;
  border-color: #fff;
  box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.25);
}

.timeline-item.completed .timeline-marker {
  background-color: #198754;
  border-color: #fff;
}

.timeline-content {
  padding-right: 0.5rem;
}

.timeline-title {
  font-size: 1rem;
  margin-bottom: 0.25rem;
  color: var(--gray-600);
  transition: color 0.3s, font-weight 0.3s;
}

.timeline-item.active .timeline-title,
.timeline-item.completed .timeline-title {
  color: var(--gray-900);
  font-weight: 600;
}

.timeline-date {
  font-size: 0.875rem;
  color: var(--gray-500);
  margin: 0;
}

/* ============================================
   EMPTY STATE STYLES
   ============================================ */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem 0;
  text-align: center;
}

.empty-icon,
.completed-icon {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.empty-icon {
  color: var(--gray-400);
}

.completed-icon {
  color: #28a745;
}

.empty-text {
  color: var(--gray-500);
  margin: 0;
}

/* ============================================
   ALERT STYLES
   ============================================ */
.alert {
  position: relative;
  padding: 1rem;
  margin-bottom: 1rem;
  border: 1px solid transparent;
  border-radius: 0.25rem;
}

.alert-info {
  color: #055160;
  background-color: #cff4fc;
  border-color: #b6effb;
}

.alert-warning {
  color: #664d03;
  background-color: #fff3cd;
  border-color: #ffecb5;
}

.alert-info i,
.alert-warning i {
  margin-right: 0.5rem;
}

.currency-info {
  margin-top: 1.25rem;
  font-size: 0.875rem;
}

/* ============================================
   MODAL STYLES
   ============================================ */
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1050;
  backdrop-filter: blur(2px);
}

.modal-container {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  width: 400px;
  max-width: 90%;
  display: flex;
  flex-direction: column;
  animation: modal-fade-in 0.3s ease forwards;
  overflow: hidden;
}

@keyframes modal-fade-in {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--gray-200);
}

.modal-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--gray-900);
  margin: 0;
}

.modal-close {
  background: transparent;
  border: none;
  color: var(--gray-500);
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0;
  line-height: 1;
  transition: color 0.15s ease-in-out;
}

.modal-close:hover {
  color: var(--gray-900);
}

.modal-body {
  padding: 1.5rem;
  color: var(--gray-700);
  line-height: 1.5;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 0.75rem;
  padding: 1.25rem 1.5rem;
  border-top: 1px solid var(--gray-200);
  background-color: var(--gray-50);
}

/* Currency conversion modal */
.currency-modal {
  width: 450px;
}

.current-currency {
  margin-bottom: 1.25rem;
}

.form-group {
  margin-bottom: 1.25rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: var(--gray-700);
}

.form-control {
  display: block;
  width: 100%;
  padding: 0.5rem 0.75rem;
  font-size: 1rem;
  font-weight: 400;
  line-height: 1.5;
  color: var(--gray-900);
  background-color: #fff;
  background-clip: padding-box;
  border: 1px solid var(--gray-300);
  border-radius: 0.25rem;
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus {
  color: var(--gray-900);
  background-color: #fff;
  border-color: #86b7fe;
  outline: 0;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.form-check {
  display: flex;
  align-items: center;
  margin-bottom: 1rem;
}

.form-check-input {
  margin-right: 0.5rem;
  width: 1rem;
  height: 1rem;
}

.form-check-label {
  font-size: 0.875rem;
  color: var(--gray-700);
}

/* ============================================
   RECEIPT SUMMARY STYLES
   ============================================ */
.receipt-summary {
  border-top: 1px solid var(--gray-200);
  padding-top: 1rem;
}

/* ============================================
   CSS CUSTOM PROPERTIES (VARIABLES)
   ============================================ */
:root {
  --gray-50: #f8f9fa;
  --gray-200: #e9ecef;
  --gray-300: #ced4da;
  --gray-400: #adb5bd;
  --gray-500: #6c757d;
  --gray-600: #495057;
  --gray-700: #343a40;
  --gray-800: #212529;
  --gray-900: #000;
}

/* ============================================
   RESPONSIVE DESIGN ADJUSTMENTS
   ============================================ */
@media (max-width: 1199.98px) {
  .po-layout {
    flex-direction: column;
  }

  .po-sidebar {
    width: 100%;
  }
}

@media (max-width: 991.98px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .action-buttons {
    margin-top: 1rem;
    width: 100%;
    justify-content: flex-start;
  }
}

@media (max-width: 767.98px) {
  .card-header-content {
    flex-direction: column;
    align-items: flex-start;
  }

  .status-badge {
    margin-top: 0.5rem;
  }

  .row {
    flex-direction: column;
  }

  .col-md-6 {
    max-width: 100%;
  }

  .modal-container {
    width: 95%;
  }

  .header-actions {
    margin-top: 0.5rem;
  }
}

@media (max-width: 575.98px) {
  .action-buttons {
    flex-direction: column;
    align-items: stretch;
    gap: 0.75rem;
  }

  .btn-group {
    display: flex;
    width: 100%;
    margin-right: 0;
    margin-bottom: 0;
  }

  .btn {
    flex: 1;
  }

  .table-responsive {
    margin: 0 -1.25rem;
    width: calc(100% + 2.5rem);
  }

  .outstanding-card .table-responsive {
    margin: 0 -1rem;
  }

  .outstanding-card .card-body {
    padding: 1rem;
  }
}

/* ============================================
   PRINT STYLES
   ============================================ */
@media print {
  .po-detail-container {
    font-size: 10pt;
  }

  .po-layout {
    display: block;
  }

  .action-buttons,
  .btn {
    display: none !important;
  }

  .page-header {
    display: none !important;
  }

  .card {
    border: 1px solid #dee2e6 !important;
    box-shadow: none !important;
    margin-bottom: 0.5cm;
    break-inside: avoid;
    page-break-inside: avoid;
  }

  .card-header {
    background-color: #f8f9fa !important;
    padding: 0.5cm 0.5cm 0.25cm 0.5cm;
  }

  .card-body {
    padding: 0.25cm 0.5cm 0.5cm 0.5cm;
  }

  .status-badge {
    border: 1px solid #000;
    padding: 0.2cm;
  }

  .table {
    width: 100% !important;
    border-collapse: collapse !important;
  }

  .table th,
  .table td {
    background-color: #fff !important;
    border: 1px solid #000 !important;
  }

  .status-timeline:before {
    background-color: #000 !important;
  }

  .timeline-marker {
    border: 2px solid #000 !important;
    box-shadow: none !important;
  }

  .table-sm th,
  .table-sm td {
    padding: 0.15cm !important;
  }

  .po-main-content,
  .po-sidebar {
    width: 100% !important;
    display: block !important;
  }

  .timeline-item.active .timeline-marker {
    background-color: #000 !important;
  }
}
</style>
