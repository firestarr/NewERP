<!-- src/views/inventory/StockTransactionDetail.vue -->
<template>
    <div class="stock-transaction-detail">
      <div class="page-header">
        <div class="header-content">
          <h1>
            Transaction #{{ transactionId }}
            <span
              v-if="transaction"
              class="badge"
              :class="getTransactionTypeClass(transaction.transaction_type)"
            >
              {{ formatTransactionType(transaction.transaction_type) }}
            </span>
            <span
              v-if="transaction"
              class="badge"
              :class="getStateClass(transaction.state)"
            >
              {{ formatState(transaction.state) }}
            </span>
          </h1>
          <div class="transaction-date" v-if="transaction">
            <i class="fas fa-calendar-alt mr-2"></i>
            {{ formatDate(transaction.transaction_date) }}
          </div>
        </div>
        <div class="page-actions">
          <router-link to="/stock-transactions" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Transactions
          </router-link>
          <div v-if="transaction && transaction.item">
            <router-link
              :to="`/stock-transactions/items/${transaction.item.item_id}/movement`"
              class="btn btn-primary"
            >
              <i class="fas fa-chart-line"></i> Item Movement
            </router-link>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-container">
        <div class="loading-spinner">
          <i class="fas fa-spinner fa-spin"></i>
        </div>
        <p>Loading transaction details...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="error-container">
        <div class="error-icon">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <p>{{ error }}</p>
        <button @click="fetchTransaction" class="btn btn-primary">
          <i class="fas fa-sync"></i> Try Again
        </button>
      </div>

      <!-- Transaction Details -->
      <div v-else-if="transaction" class="transaction-details-container">
        <!-- Action Buttons for Draft State -->
        <div v-if="transaction.state === 'draft'" class="action-bar mb-4">
          <div class="alert alert-info">
            <i class="fas fa-info-circle mr-2"></i>
            This transaction is in draft state. You can confirm or cancel it.
          </div>
          <div class="action-buttons">
            <button
              @click="confirmTransaction"
              class="btn btn-success"
              :disabled="isConfirming"
            >
              <i v-if="isConfirming" class="fas fa-spinner fa-spin mr-2"></i>
              <i v-else class="fas fa-check mr-2"></i>
              Confirm Transaction
            </button>
            <button
              @click="cancelTransaction"
              class="btn btn-danger"
              :disabled="isCancelling"
            >
              <i v-if="isCancelling" class="fas fa-spinner fa-spin mr-2"></i>
              <i v-else class="fas fa-times mr-2"></i>
              Cancel Transaction
            </button>
          </div>
        </div>

        <div class="row">
          <!-- Left Column - Primary Info -->
          <div class="col-lg-8">
            <div class="card mb-4">
              <div class="card-header">
                <h3 class="card-title">Transaction Details</h3>
              </div>
              <div class="card-body">
                <div class="detail-grid">
                  <div class="detail-item">
                    <div class="detail-label">Transaction ID</div>
                    <div class="detail-value">#{{ transaction.transaction_id }}</div>
                  </div>
                  <div class="detail-item">
                    <div class="detail-label">State</div>
                    <div class="detail-value">
                      <span class="badge" :class="getStateClass(transaction.state)">
                        {{ formatState(transaction.state) }}
                      </span>
                    </div>
                  </div>
                  <div class="detail-item">
                    <div class="detail-label">Type</div>
                    <div class="detail-value">
                      <span
                        class="badge"
                        :class="getTransactionTypeClass(transaction.transaction_type)"
                      >
                        {{ formatTransactionType(transaction.transaction_type) }}
                      </span>
                    </div>
                  </div>
                  <div class="detail-item">
                    <div class="detail-label">Move Type</div>
                    <div class="detail-value">
                      <span class="badge" :class="getMoveTypeClass(transaction.move_type)">
                        {{ formatMoveType(transaction.move_type) }}
                      </span>
                    </div>
                  </div>
                  <div class="detail-item">
                    <div class="detail-label">Date</div>
                    <div class="detail-value">{{ formatDate(transaction.transaction_date) }}</div>
                  </div>
                  <div class="detail-item">
                    <div class="detail-label">Quantity</div>
                    <div class="detail-value" :class="getQuantityClass(transaction.quantity)">
                      {{ transaction.quantity }}
                      <span v-if="transaction.item && transaction.item.unitOfMeasure" class="unit-symbol">
                        {{ transaction.item.unitOfMeasure.symbol }}
                      </span>
                    </div>
                  </div>
                  <div class="detail-item" v-if="transaction.origin">
                    <div class="detail-label">Origin</div>
                    <div class="detail-value">{{ transaction.origin }}</div>
                  </div>
                  <div class="detail-item" v-if="transaction.reference_document">
                    <div class="detail-label">Reference Document</div>
                    <div class="detail-value">{{ transaction.reference_document }}</div>
                  </div>
                  <div class="detail-item" v-if="transaction.reference_number">
                    <div class="detail-label">Reference Number</div>
                    <div class="detail-value">{{ transaction.reference_number }}</div>
                  </div>
                  <div class="detail-item" v-if="transaction.notes">
                    <div class="detail-label">Notes</div>
                    <div class="detail-value">{{ transaction.notes }}</div>
                  </div>
                  <div class="detail-item" v-if="transaction.batch">
                    <div class="detail-label">Batch</div>
                    <div class="detail-value">
                      {{ transaction.batch.batch_number }}
                      <span v-if="transaction.batch.expiry_date" class="text-muted">
                        (Expires: {{ formatDate(transaction.batch.expiry_date) }})
                      </span>
                    </div>
                  </div>
                  <div class="detail-item" v-if="transaction.created_at">
                    <div class="detail-label">Created</div>
                    <div class="detail-value">{{ formatDateTime(transaction.created_at) }}</div>
                  </div>
                  <div class="detail-item" v-if="transaction.updated_at">
                    <div class="detail-label">Last Updated</div>
                    <div class="detail-value">{{ formatDateTime(transaction.updated_at) }}</div>
                  </div>
                </div>

                <!-- Transaction Description -->
                <div v-if="transaction.description" class="transaction-description mt-3">
                  <div class="detail-label">Description</div>
                  <div class="detail-value description-text">
                    {{ transaction.description }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Item Information -->
            <div class="card mb-4" v-if="transaction.item">
              <div class="card-header">
                <h3 class="card-title">Item Information</h3>
                <router-link :to="`/items/${transaction.item.item_id}`" class="btn-text">
                  <i class="fas fa-external-link-alt"></i> View Item
                </router-link>
              </div>
              <div class="card-body">
                <div class="item-details">
                  <div class="item-header">
                    <div class="item-code">{{ transaction.item.item_code }}</div>
                    <div class="item-name">{{ transaction.item.name }}</div>
                  </div>
                  <div class="item-meta">
                    <div class="meta-item" v-if="transaction.item.category">
                      <div class="meta-label">Category</div>
                      <div class="meta-value">{{ transaction.item.category.name }}</div>
                    </div>
                    <div class="meta-item" v-if="transaction.item.unitOfMeasure">
                      <div class="meta-label">Unit of Measure</div>
                      <div class="meta-value">
                        {{ transaction.item.unitOfMeasure.name }}
                        ({{ transaction.item.unitOfMeasure.symbol }})
                      </div>
                    </div>
                  </div>
                  <div class="item-stock">
                    <div class="stock-label">Current Stock</div>
                    <div class="stock-value" :class="getStockStatusClass(transaction.item)">
                      {{ transaction.item.current_stock }}
                      <span v-if="transaction.item.unitOfMeasure" class="unit-symbol">
                        {{ transaction.item.unitOfMeasure.symbol }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Related Transactions -->
            <div class="card" v-if="relatedTransactions.length > 0">
              <div class="card-header">
                <h3 class="card-title">Related Transactions</h3>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>State</th>
                        <th>Type</th>
                        <th class="text-right">Quantity</th>
                        <th>Reference</th>
                        <th class="text-right">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="relatedTx in relatedTransactions" :key="relatedTx.transaction_id">
                        <td>
                          <router-link :to="`/stock-transactions/${relatedTx.transaction_id}`" class="font-weight-bold">
                            #{{ relatedTx.transaction_id }}
                          </router-link>
                        </td>
                        <td>{{ formatDate(relatedTx.transaction_date) }}</td>
                        <td>
                          <span class="badge" :class="getStateClass(relatedTx.state)">
                            {{ formatState(relatedTx.state) }}
                          </span>
                        </td>
                        <td>
                          <span class="badge" :class="getTransactionTypeClass(relatedTx.transaction_type)">
                            {{ formatTransactionType(relatedTx.transaction_type) }}
                          </span>
                        </td>
                        <td class="text-right">
                          <span :class="getQuantityClass(relatedTx.quantity)">
                            {{ relatedTx.quantity }}
                            <span v-if="relatedTx.item && relatedTx.item.unitOfMeasure" class="text-muted">
                              {{ relatedTx.item.unitOfMeasure.symbol }}
                            </span>
                          </span>
                        </td>
                        <td>
                          <div v-if="relatedTx.reference_document">{{ relatedTx.reference_document }}</div>
                          <div v-if="relatedTx.reference_number" class="text-muted">{{ relatedTx.reference_number }}</div>
                          <div v-if="!relatedTx.reference_document && !relatedTx.reference_number" class="text-muted">
                            --
                          </div>
                        </td>
                        <td class="text-right">
                          <router-link :to="`/stock-transactions/${relatedTx.transaction_id}`" class="btn-sm btn-outline-primary">
                            <i class="fas fa-eye"></i> View
                          </router-link>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Column - Secondary Info -->
          <div class="col-lg-4">
            <!-- Location Information -->
            <div class="card mb-4">
              <div class="card-header">
                <h3 class="card-title">Warehouse Information</h3>
              </div>
              <div class="card-body">
                <!-- Source Warehouse -->
                <div v-if="transaction.warehouse" class="warehouse-info">
                  <div class="warehouse-label">{{ getWarehouseLabel() }}</div>
                  <div class="warehouse-name">
                    <i class="fas fa-warehouse mr-2"></i> {{ transaction.warehouse.name }}
                  </div>
                  <div class="warehouse-code text-muted">{{ transaction.warehouse.code }}</div>
                </div>

                <!-- Transfer Arrow -->
                <div v-if="transaction.dest_warehouse_id" class="transfer-arrow mt-2 mb-2">
                  <i class="fas fa-arrow-down text-primary"></i>
                </div>

                <!-- Destination Warehouse (for transfers) -->
                <div v-if="transaction.dest_warehouse_id && transaction.dest_warehouse" class="warehouse-info mt-3">
                  <div class="warehouse-label">Destination Warehouse</div>
                  <div class="warehouse-name">
                    <i class="fas fa-warehouse mr-2"></i> {{ transaction.dest_warehouse.name }}
                  </div>
                  <div class="warehouse-code text-muted">{{ transaction.dest_warehouse.code }}</div>
                </div>



                <div v-if="!transaction.warehouse" class="alert alert-warning">
                  <i class="fas fa-exclamation-triangle mr-2"></i>
                  Warehouse information not available
                </div>
              </div>
            </div>

            <!-- Actions Card -->
            <div class="card mb-4">
              <div class="card-header">
                <h3 class="card-title">Actions</h3>
              </div>
              <div class="card-body">
                <div class="actions-list">
                  <button
                    class="btn btn-outline-primary btn-block mb-2"
                    @click="printTransaction"
                  >
                    <i class="fas fa-print mr-2"></i> Print Transaction
                  </button>

                  <!-- Confirm/Cancel actions for draft transactions -->
                  <div v-if="transaction.state === 'draft'" class="draft-actions">
                    <button
                      class="btn btn-success btn-block mb-2"
                      @click="confirmTransaction"
                      :disabled="isConfirming"
                    >
                      <i v-if="isConfirming" class="fas fa-spinner fa-spin mr-2"></i>
                      <i v-else class="fas fa-check mr-2"></i>
                      Confirm
                    </button>
                    <button
                      class="btn btn-danger btn-block mb-2"
                      @click="cancelTransaction"
                      :disabled="isCancelling"
                    >
                      <i v-if="isCancelling" class="fas fa-spinner fa-spin mr-2"></i>
                      <i v-else class="fas fa-times mr-2"></i>
                      Cancel
                    </button>
                  </div>

                  <button
                    v-if="canReverseTransaction"
                    class="btn btn-outline-danger btn-block"
                    @click="showReverseModal"
                  >
                    <i class="fas fa-undo mr-2"></i> Reverse Transaction
                  </button>
                </div>
              </div>
            </div>

            <!-- Transaction History Card -->
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Transaction History</h3>
              </div>
              <div class="card-body p-0">
                <ul class="transaction-timeline">
                  <li class="timeline-item">
                    <div class="timeline-marker bg-success"></div>
                    <div class="timeline-content">
                      <div class="timeline-title">Transaction Created</div>
                      <div class="timeline-date">{{ formatDateTime(transaction.created_at) }}</div>
                    </div>
                  </li>
                  <li v-if="transaction.state === 'done'" class="timeline-item">
                    <div class="timeline-marker bg-success"></div>
                    <div class="timeline-content">
                      <div class="timeline-title">Transaction Completed</div>
                      <div class="timeline-date">{{ formatDateTime(transaction.updated_at) }}</div>
                    </div>
                  </li>
                  <li v-else-if="transaction.state === 'cancelled'" class="timeline-item">
                    <div class="timeline-marker bg-danger"></div>
                    <div class="timeline-content">
                      <div class="timeline-title">Transaction Cancelled</div>
                      <div class="timeline-date">{{ formatDateTime(transaction.updated_at) }}</div>
                    </div>
                  </li>
                  <li v-else-if="transaction.created_at !== transaction.updated_at" class="timeline-item">
                    <div class="timeline-marker bg-info"></div>
                    <div class="timeline-content">
                      <div class="timeline-title">Transaction Updated</div>
                      <div class="timeline-date">{{ formatDateTime(transaction.updated_at) }}</div>
                    </div>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Reverse Transaction Modal -->
      <div v-if="showReversalModal" class="modal-backdrop">
        <div class="modal-content">
          <div class="modal-header">
            <h3>Reverse Transaction</h3>
            <button class="btn-close" @click="closeReverseModal">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <div class="alert alert-warning mb-4">
              <i class="fas fa-exclamation-triangle mr-2"></i>
              You are about to create a reverse transaction for transaction #{{ transaction.transaction_id }}.
              This will create a new transaction with an opposite quantity.
            </div>

            <div class="transaction-details mb-4">
              <p><strong>Transaction Type:</strong> {{ formatTransactionType(transaction.transaction_type) }}</p>
              <p><strong>Item:</strong> {{ transaction.item?.item_code }} - {{ transaction.item?.name }}</p>
              <p><strong>Warehouse:</strong> {{ transaction.warehouse?.name }}</p>
              <p><strong>Quantity:</strong> <span :class="getQuantityClass(transaction.quantity)">{{ transaction.quantity }}</span></p>
              <p><strong>New Quantity:</strong> <span :class="getQuantityClass(-transaction.quantity)">{{ -transaction.quantity }}</span></p>
            </div>

            <div class="form-group">
              <label for="reversal-reason">Reason for Reversal*</label>
              <textarea
                id="reversal-reason"
                v-model="reversalReason"
                rows="3"
                class="form-control"
                :class="{ 'is-invalid': reversalReasonError }"
                required
              ></textarea>
              <div v-if="reversalReasonError" class="invalid-feedback">
                {{ reversalReasonError }}
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeReverseModal">Cancel</button>
            <button
              type="button"
              class="btn btn-danger"
              @click="reverseTransaction"
              :disabled="isReversing"
            >
              <i v-if="isReversing" class="fas fa-spinner fa-spin mr-2"></i>
              Confirm Reversal
            </button>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script>
  import { ref, computed, onMounted } from 'vue';
  import { useRoute, useRouter } from 'vue-router';
  import axios from 'axios';

  export default {
    name: 'StockTransactionDetail',
    setup() {
      const route = useRoute();
      const router = useRouter();

      // Route params
      const transactionId = computed(() => route.params.id);

      // Data
      const transaction = ref(null);
      const relatedTransactions = ref([]);
      const loading = ref(true);
      const error = ref(null);

      // Action states
      const isConfirming = ref(false);
      const isCancelling = ref(false);

      // Reversal modal
      const showReversalModal = ref(false);
      const reversalReason = ref('');
      const reversalReasonError = ref(null);
      const isReversing = ref(false);

      // Computed properties
      const canReverseTransaction = computed(() => {
        if (!transaction.value) return false;

        // Only allow reversal for completed transactions
        return transaction.value.state === 'done';
      });

      // Methods
      const fetchTransaction = async () => {
        loading.value = true;
        error.value = null;

        try {
          const response = await axios.get(`/transactions/${transactionId.value}`);
          transaction.value = response.data.data;

          // After fetching the transaction, fetch related transactions
          fetchRelatedTransactions();
        } catch (err) {
          console.error('Error fetching transaction:', err);
          error.value = 'Failed to load transaction details. Please try again.';
        } finally {
          loading.value = false;
        }
      };

      const fetchRelatedTransactions = async () => {
        if (!transaction.value || !transaction.value.item) return;

        try {
          // Fetch other transactions for the same item
          const params = {
            item_id: transaction.value.item.item_id,
            limit: 5 // Limit to 5 related transactions
          };

          const response = await axios.get('/transactions', { params });

          // Filter out the current transaction and sort by date (newest first)
          relatedTransactions.value = response.data.data.data
            .filter(t => t.transaction_id !== parseInt(transactionId.value))
            .sort((a, b) => new Date(b.transaction_date) - new Date(a.transaction_date))
            .slice(0, 5); // Take only the first 5
        } catch (err) {
          console.error('Error fetching related transactions:', err);
        }
      };

      const confirmTransaction = async () => {
        isConfirming.value = true;

        try {
          await axios.post(`/stock-transactions/${transactionId.value}/confirm`);

          // Refresh transaction data
          await fetchTransaction();

          // Show success message
          alert('Transaction confirmed successfully');
        } catch (err) {
          console.error('Error confirming transaction:', err);
          alert('Failed to confirm transaction. Please try again.');
        } finally {
          isConfirming.value = false;
        }
      };

      const cancelTransaction = async () => {
        if (!confirm('Are you sure you want to cancel this transaction?')) {
          return;
        }

        isCancelling.value = true;

        try {
          await axios.post(`/stock-transactions/${transactionId.value}/cancel`);

          // Refresh transaction data
          await fetchTransaction();

          // Show success message
          alert('Transaction cancelled successfully');
        } catch (err) {
          console.error('Error cancelling transaction:', err);
          alert('Failed to cancel transaction. Please try again.');
        } finally {
          isCancelling.value = false;
        }
      };

      const formatDate = (dateString) => {
        if (!dateString) return '--';

        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        });
      };

      const formatDateTime = (dateTimeString) => {
        if (!dateTimeString) return '--';

        const date = new Date(dateTimeString);
        return date.toLocaleString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        });
      };

      const formatTransactionType = (type) => {
        if (!type) return '--';

        const types = {
          'receive': 'Receive',
          'issue': 'Issue',
          'transfer': 'Transfer',
          'adjustment': 'Adjustment',
          'return': 'Return',
          'manufacturing': 'Manufacturing'
        };

        return types[type.toLowerCase()] || type;
      };

      const formatState = (state) => {
        if (!state) return '--';

        const states = {
          'draft': 'Draft',
          'confirmed': 'Confirmed',
          'done': 'Done',
          'cancelled': 'Cancelled'
        };

        return states[state.toLowerCase()] || state;
      };

      const formatMoveType = (moveType) => {
        if (!moveType) return '--';

        const types = {
          'in': 'Incoming',
          'out': 'Outgoing',
          'internal': 'Internal'
        };

        return types[moveType.toLowerCase()] || moveType;
      };

      const getTransactionTypeClass = (type) => {
        if (!type) return '';

        const typeClasses = {
          'receive': 'badge-success',
          'issue': 'badge-danger',
          'transfer': 'badge-warning',
          'adjustment': 'badge-secondary',
          'return': 'badge-info',
          'manufacturing': 'badge-primary'
        };

        return typeClasses[type.toLowerCase()] || 'badge-secondary';
      };

      const getStateClass = (state) => {
        if (!state) return '';

        const stateClasses = {
          'draft': 'badge-warning',
          'confirmed': 'badge-info',
          'done': 'badge-success',
          'cancelled': 'badge-danger'
        };

        return stateClasses[state.toLowerCase()] || 'badge-secondary';
      };

      const getMoveTypeClass = (moveType) => {
        if (!moveType) return '';

        const typeClasses = {
          'in': 'badge-success',
          'out': 'badge-danger',
          'internal': 'badge-info'
        };

        return typeClasses[moveType.toLowerCase()] || 'badge-secondary';
      };

      const getQuantityClass = (quantity) => {
        if (!quantity) return '';

        if (quantity > 0) {
          return 'text-success';
        } else if (quantity < 0) {
          return 'text-danger';
        }

        return '';
      };

      const getStockStatusClass = (item) => {
        if (!item) return '';

        if (item.current_stock <= 0) {
          return 'text-danger';
        } else if (item.current_stock <= item.minimum_stock) {
          return 'text-warning';
        } else {
          return 'text-success';
        }
      };

      const getWarehouseLabel = () => {
        if (!transaction.value) return 'Warehouse';

        if (transaction.value.move_type === 'in') {
          return 'Destination Warehouse';
        } else if (transaction.value.move_type === 'out') {
          return 'Source Warehouse';
        } else if (transaction.value.dest_warehouse_id) {
          return 'Source Warehouse';
        } else {
          return 'Warehouse';
        }
      };

      const printTransaction = () => {
        window.print();
      };

      const showReverseModal = () => {
        showReversalModal.value = true;
        reversalReason.value = '';
        reversalReasonError.value = null;
      };

      const closeReverseModal = () => {
        showReversalModal.value = false;
        reversalReason.value = '';
        reversalReasonError.value = null;
      };

      const reverseTransaction = async () => {
        if (!reversalReason.value.trim()) {
          reversalReasonError.value = 'Please provide a reason for reversal';
          return;
        }

        isReversing.value = true;

        try {
          // Create the reversal transaction
          const response = await axios.post('/transactions', {
            item_id: transaction.value.item_id,
            warehouse_id: transaction.value.warehouse_id,
            dest_warehouse_id: transaction.value.dest_warehouse_id,
            transaction_type: 'adjustment',
            move_type: transaction.value.move_type === 'in' ? 'out' : 'in',
            quantity: transaction.value.quantity,
            transaction_date: new Date().toISOString().split('T')[0],
            reference_document: 'Reversal',
            reference_number: `REV-${transaction.value.transaction_id}`,
            origin: `Reversal for transaction #${transaction.value.transaction_id}`,
            batch_id: transaction.value.batch_id,
            notes: `Reversal for transaction #${transaction.value.transaction_id}. Reason: ${reversalReason.value}`,
            auto_confirm: true
          });

          // Close modal
          closeReverseModal();

          // Show success and redirect to the new transaction
          alert('Transaction successfully reversed');
          router.push(`/stock-transactions/${response.data.data.transaction_id}`);
        } catch (err) {
          console.error('Error reversing transaction:', err);
          reversalReasonError.value = 'Failed to reverse transaction. Please try again.';
        } finally {
          isReversing.value = false;
        }
      };

      // Lifecycle hooks
      onMounted(() => {
        fetchTransaction();
      });

      return {
        transactionId,
        transaction,
        relatedTransactions,
        loading,
        error,
        isConfirming,
        isCancelling,
        showReversalModal,
        reversalReason,
        reversalReasonError,
        isReversing,
        canReverseTransaction,
        fetchTransaction,
        confirmTransaction,
        cancelTransaction,
        formatDate,
        formatDateTime,
        formatTransactionType,
        formatState,
        formatMoveType,
        getTransactionTypeClass,
        getStateClass,
        getMoveTypeClass,
        getQuantityClass,
        getStockStatusClass,
        getWarehouseLabel,
        printTransaction,
        showReverseModal,
        closeReverseModal,
        reverseTransaction
      };
    }
  };
  </script>

  <style scoped>
  .stock-transaction-detail {
    padding: 1rem;
  }

  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
  }

  .header-content h1 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
  }

  .page-actions {
    display: flex;
    gap: 0.75rem;
  }

  .transaction-date {
    margin-top: 0.5rem;
    color: var(--gray-600);
  }

  .action-bar {
    background: white;
    border-radius: 0.5rem;
    padding: 1rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .action-buttons {
    display: flex;
    gap: 0.75rem;
    margin-top: 1rem;
  }

  .draft-actions {
    border-top: 1px solid var(--gray-200);
    padding-top: 1rem;
    margin-top: 1rem;
  }

  .transfer-arrow {
    text-align: center;
    font-size: 1.25rem;
  }

  .warehouse-label {
    font-size: 0.75rem;
    color: var(--gray-600);
    margin-bottom: 0.25rem;
    font-weight: 500;
  }

  .description-text {
    background-color: var(--gray-50);
    padding: 0.75rem;
    border-radius: 0.375rem;
    border-left: 4px solid var(--primary-color);
    font-style: italic;
  }

  .loading-container, .error-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 1rem;
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    margin-bottom: 1.5rem;
  }

  .loading-spinner, .error-icon {
    font-size: 2rem;
    margin-bottom: 1rem;
  }

  .error-icon {
    color: var(--danger-color);
  }

  .transaction-details-container {
    margin-bottom: 1.5rem;
  }

  .row {
    display: flex;
    flex-wrap: wrap;
    margin-right: -0.75rem;
    margin-left: -0.75rem;
  }

  .col-lg-8 {
    flex: 0 0 66.666667%;
    max-width: 66.666667%;
    padding-right: 0.75rem;
    padding-left: 0.75rem;
  }

  .col-lg-4 {
    flex: 0 0 33.333333%;
    max-width: 33.333333%;
    padding-right: 0.75rem;
    padding-left: 0.75rem;
  }

  .card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    margin-bottom: 1.5rem;
  }

  .card-header {
    padding: 1rem;
    border-bottom: 1px solid var(--gray-200);
    background-color: var(--gray-50);
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .card-title {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--gray-800);
  }

  .card-body {
    padding: 1rem;
  }

  .card-body.p-0 {
    padding: 0;
  }

  .detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
  }

  .detail-item {
    margin-bottom: 0.75rem;
  }

  .detail-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--gray-600);
    margin-bottom: 0.25rem;
  }

  .detail-value {
    color: var(--gray-800);
  }

  .unit-symbol {
    font-size: 0.875rem;
    color: var(--gray-600);
    margin-left: 0.25rem;
  }

  .item-details {
    padding: 1rem;
    border: 1px solid var(--gray-200);
    border-radius: 0.375rem;
  }

  .item-header {
    margin-bottom: 1rem;
  }

  .item-code {
    font-size: 0.875rem;
    color: var(--gray-600);
    margin-bottom: 0.25rem;
  }

  .item-name {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--gray-800);
  }

  .item-meta {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
  }

  .meta-label {
    font-size: 0.75rem;
    color: var(--gray-600);
    margin-bottom: 0.25rem;
  }

  .meta-value {
    font-size: 0.875rem;
    color: var(--gray-800);
  }

  .item-stock {
    padding-top: 1rem;
    border-top: 1px solid var(--gray-200);
  }

  .stock-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--gray-600);
    margin-bottom: 0.25rem;
  }

  .stock-value {
    font-size: 1.25rem;
    font-weight: 600;
  }

  .warehouse-info {
    margin-bottom: 1rem;
  }

  .warehouse-name {
    font-size: 1rem;
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: 0.25rem;
  }

  .warehouse-code {
    font-size: 0.875rem;
  }

  .location-info {
    padding-top: 1rem;
    border-top: 1px solid var(--gray-200);
  }

  .location-label {
    font-size: 0.75rem;
    margin-bottom: 0.25rem;
  }

  .location-code {
    font-size: 1rem;
    font-weight: 500;
    color: var(--gray-800);
    margin-bottom: 0.25rem;
  }

  .location-description {
    font-size: 0.875rem;
  }

  .transaction-timeline {
    list-style-type: none;
    padding: 0;
    margin: 0;
  }

  .timeline-item {
    position: relative;
    padding: 1rem 1rem 1rem 2.5rem;
    border-bottom: 1px solid var(--gray-200);
  }

  .timeline-item:last-child {
    border-bottom: none;
  }

  .timeline-marker {
    position: absolute;
    left: 1rem;
    top: 1.25rem;
    width: 0.75rem;
    height: 0.75rem;
    border-radius: 50%;
  }

  .bg-success {
    background-color: var(--success-color);
  }

  .bg-info {
    background-color: var(--primary-color);
  }

  .bg-danger {
    background-color: var(--danger-color);
  }

  .timeline-title {
    font-weight: 500;
    color: var(--gray-800);
    margin-bottom: 0.25rem;
  }

  .timeline-date {
    font-size: 0.75rem;
    color: var(--gray-600);
  }

  .modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
  }

  .modal-content {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
  }

  .modal-header {
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--gray-200);
  }

  .modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
  }

  .modal-body {
    padding: 1rem;
  }

  .modal-footer {
    padding: 1rem;
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    border-top: 1px solid var(--gray-200);
  }

  .btn-close {
    background: none;
    border: none;
    font-size: 1.25rem;
    color: var(--gray-600);
    cursor: pointer;
  }

  .form-group {
    margin-bottom: 1rem;
  }

  .form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
  }

  .form-control {
    width: 100%;
    padding: 0.5rem 0.75rem;
    font-size: 1rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
  }

  .form-control.is-invalid {
    border-color: var(--danger-color);
  }

  .invalid-feedback {
    color: var(--danger-color);
    font-size: 0.875rem;
    margin-top: 0.25rem;
  }

  .badge {
    display: inline-block;
    padding: 0.35em 0.65em;
    font-size: 0.75em;
    font-weight: 700;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 0.375rem;
  }

  .badge-success {
    background-color: var(--success-light);
    color: var(--success-color);
  }

  .badge-danger {
    background-color: var(--danger-light);
    color: var(--danger-color);
  }

  .badge-warning {
    background-color: var(--warning-light);
    color: var(--warning-color);
  }

  .badge-info {
    background-color: var(--primary-bg);
    color: var(--primary-color);
  }

  .badge-secondary {
    background-color: var(--gray-200);
    color: var(--gray-700);
  }

  .badge-primary {
    background-color: var(--primary-color);
    color: white;
  }

  .btn-text {
    background: none;
    border: none;
    color: var(--primary-color);
    cursor: pointer;
    padding: 0.25rem 0.5rem;
    display: inline-flex;
    align-items: center;
  }

  .btn-text:hover {
    text-decoration: underline;
  }

  .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    border-radius: 0.25rem;
  }

  .btn-block {
    display: block;
    width: 100%;
  }

  .btn-outline-primary {
    color: var(--primary-color);
    border: 1px solid var(--primary-color);
    background-color: transparent;
  }

  .btn-outline-primary:hover {
    color: white;
    background-color: var(--primary-color);
  }

  .btn-outline-danger {
    color: var(--danger-color);
    border: 1px solid var(--danger-color);
    background-color: transparent;
  }

  .btn-outline-danger:hover {
    color: white;
    background-color: var(--danger-color);
  }

  .alert {
    position: relative;
    padding: 0.75rem 1.25rem;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    border-radius: 0.25rem;
  }

  .alert-warning {
    color: #856404;
    background-color: #fff3cd;
    border-color: #ffeeba;
  }

  .alert-info {
    color: #0c5460;
    background-color: #d1ecf1;
    border-color: #bee5eb;
  }

  .text-muted {
    color: var(--gray-600);
  }

  .text-success {
    color: var(--success-color);
  }

  .text-danger {
    color: var(--danger-color);
  }

  .text-warning {
    color: var(--warning-color);
  }

  .text-primary {
    color: var(--primary-color);
  }

  .font-weight-bold {
    font-weight: 700;
  }

  .mb-2 {
    margin-bottom: 0.5rem;
  }

  .mb-4 {
    margin-bottom: 1.5rem;
  }

  .mt-3 {
    margin-top: 1rem;
  }

  .mr-2 {
    margin-right: 0.5rem;
  }

  .text-right {
    text-align: right;
  }

  /* Table styles */
  .table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  .data-table {
    width: 100%;
    margin-bottom: 1rem;
    color: var(--gray-800);
    border-collapse: collapse;
  }

  .data-table th,
  .data-table td {
    padding: 0.75rem;
    vertical-align: top;
    border-top: 1px solid var(--gray-200);
  }

  .data-table thead th {
    vertical-align: bottom;
    border-bottom: 2px solid var(--gray-200);
    background-color: var(--gray-50);
    color: var(--gray-600);
    font-weight: 500;
  }

  .data-table tbody tr:hover {
    background-color: var(--gray-50);
  }

  /* Responsive styles */
  @media (max-width: 992px) {
    .col-lg-8, .col-lg-4 {
      flex: 0 0 100%;
      max-width: 100%;
    }

    .detail-grid {
      grid-template-columns: 1fr;
    }

    .action-buttons {
      flex-direction: column;
    }

    .page-header {
      flex-direction: column;
      gap: 1rem;
    }

    .page-actions {
      flex-direction: column;
      width: 100%;
    }
  }

  /* Print styles */
  @media print {
    .page-actions, .btn-text, .btn, .actions-list, .action-bar {
      display: none !important;
    }

    .stock-transaction-detail {
      padding: 0;
    }

    .card {
      box-shadow: none;
      border: 1px solid #ddd;
      break-inside: avoid;
    }
  }
  </style>
