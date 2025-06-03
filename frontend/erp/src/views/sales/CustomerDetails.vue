<!-- src/views/sales/CustomerDetails.vue -->
<template>
  <div class="customer-details-page">
    <div class="page-header">
      <div class="page-title">
        <h2 v-if="isLoading">Loading customer details...</h2>
        <h2 v-else-if="customer">{{ customer.name }}</h2>
        <h2 v-else>Customer Details</h2>
      </div>
      <div class="page-actions">
        <button class="btn btn-secondary" @click="goBack">
          <i class="fas fa-arrow-left"></i> Back
        </button>
        <button v-if="customer" class="btn btn-primary" @click="editCustomer">
          <i class="fas fa-edit"></i> Edit
        </button>
      </div>
    </div>

    <div v-if="isLoading" class="loading-indicator">
      <i class="fas fa-spinner fa-spin"></i> Loading customer details...
    </div>

    <div v-else-if="!customer" class="empty-state">
      <div class="empty-icon">
        <i class="fas fa-user-slash"></i>
      </div>
      <h3>Customer not found</h3>
      <p>The customer you're looking for doesn't exist or has been deleted.</p>
      <button class="btn btn-secondary" @click="goBack">
        Back to Customers
      </button>
    </div>

    <div v-else class="customer-content">
      <div class="customer-info-card">
        <div class="card-header">
          <h3>Customer Information</h3>
          <span class="customer-status" :class="{ inactive: customer.status !== 'Active' }">
            {{ customer.status }}
          </span>
        </div>

        <div class="card-body">
          <div class="info-grid">
            <div class="info-item">
              <div class="info-label">Customer Code</div>
              <div class="info-value">{{ customer.customer_code }}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Name</div>
              <div class="info-value">{{ customer.name }}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Tax ID</div>
              <div class="info-value">{{ customer.tax_id || 'Not provided' }}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Contact Person</div>
              <div class="info-value">{{ customer.contact_person || 'Not provided' }}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Phone</div>
              <div class="info-value">{{ customer.phone || 'Not provided' }}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Email</div>
              <div class="info-value">{{ customer.email || 'Not provided' }}</div>
            </div>
            <div class="info-item full-width">
              <div class="info-label">Address</div>
              <div class="info-value">{{ customer.address || 'Not provided' }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Customer Summary Cards -->
<div class="customer-summary-cards">
  <div class="summary-card">
    <div class="summary-icon orders-icon">
      <i class="fas fa-shopping-cart"></i>
    </div>
    <div class="summary-content">
      <div class="summary-value">{{ orderStats.count }}</div>
      <div class="summary-label">Orders</div>
    </div>
  </div>

  <div class="summary-card">
    <div class="summary-icon invoices-icon">
      <i class="fas fa-file-invoice-dollar"></i>
    </div>
    <div class="summary-content">
      <div class="summary-value">{{ invoiceStats.count }}</div>
      <div class="summary-label">Invoices</div>
    </div>
  </div>

  <div class="summary-card">
    <div class="summary-icon amount-icon">
      <i class="fas fa-dollar-sign"></i>
    </div>
    <div class="summary-content">
      <div class="summary-value">{{ formatCurrency(invoiceStats.total) }}</div>
      <div class="summary-label">Total Amount</div>
    </div>
  </div>

  <div class="summary-card">
    <div class="summary-icon last-order-icon">
      <i class="fas fa-calendar-check"></i>
    </div>
    <div class="summary-content">
      <div class="summary-value">{{ formatDate(orderStats.lastOrderDate) || 'Never' }}</div>
      <div class="summary-label">Last Order</div>
    </div>
  </div>
</div>

<!-- Tabs Container -->
<div class="tabs-container">
  <div class="tabs">
    <div
      class="tab"
      :class="{ active: activeTab === 'orders' }"
      @click="setActiveTab('orders')"
    >
      <i class="fas fa-shopping-cart"></i> Orders
    </div>
    <div
      class="tab"
      :class="{ active: activeTab === 'invoices' }"
      @click="setActiveTab('invoices')"
    >
      <i class="fas fa-file-invoice-dollar"></i> Invoices
    </div>
    <div
      class="tab"
      :class="{ active: activeTab === 'quotations' }"
      @click="setActiveTab('quotations')"
    >
      <i class="fas fa-quote-right"></i> Quotations
    </div>
    <div
      class="tab"
      :class="{ active: activeTab === 'interactions' }"
      @click="setActiveTab('interactions')"
    >
      <i class="fas fa-comments"></i> Interactions
    </div>
  </div>

        <div class="tab-content">
          <!-- Orders tab -->
          <div v-if="activeTab === 'orders'" class="orders-tab">
            <div class="tab-header">
              <h3>Orders</h3>
              <button class="btn btn-primary" @click="createNewOrder">
                <i class="fas fa-plus"></i> New Order
              </button>
            </div>

            <div v-if="isLoadingOrders" class="loading-indicator">
              <i class="fas fa-spinner fa-spin"></i> Loading orders...
            </div>

            <div v-else-if="!orders || orders.length === 0" class="empty-tab">
              <div class="empty-icon">
                <i class="fas fa-shopping-cart"></i>
              </div>
              <h4>No Orders Found</h4>
              <p>This customer doesn't have any orders yet.</p>
              <button class="btn btn-primary" @click="createNewOrder">
                Create First Order
              </button>
            </div>

            <div v-else class="data-table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Order #</th>
                    <th>Date</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="order in orders" :key="order.so_id">
                    <td>{{ order.so_number }}</td>
                    <td>{{ formatDate(order.so_date) }}</td>
                    <td>{{ formatCurrency(order.total_amount) }}</td>
                    <td>
                      <span class="status-badge" :class="getOrderStatusClass(order.status)">
                        {{ order.status }}
                      </span>
                    </td>
                    <td class="actions-cell">
                      <button class="action-btn" @click="viewOrder(order)" title="View Order">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button
                        class="action-btn"
                        @click="createDelivery(order)"
                        title="Create Delivery"
                        :disabled="!canCreateDelivery(order)"
                      >
                        <i class="fas fa-truck"></i>
                      </button>
                      <button
                        class="action-btn"
                        @click="createInvoice(order)"
                        title="Create Invoice"
                        :disabled="!canCreateInvoice(order)"
                      >
                        <i class="fas fa-file-invoice-dollar"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Invoices tab -->
          <div v-if="activeTab === 'invoices'" class="invoices-tab">
            <div class="tab-header">
              <h3>Invoices</h3>
            </div>

            <div v-if="isLoadingInvoices" class="loading-indicator">
              <i class="fas fa-spinner fa-spin"></i> Loading invoices...
            </div>

            <div v-else-if="!invoices || invoices.length === 0" class="empty-tab">
              <div class="empty-icon">
                <i class="fas fa-file-invoice-dollar"></i>
              </div>
              <h4>No Invoices Found</h4>
              <p>This customer doesn't have any invoices yet.</p>
            </div>

            <div v-else class="data-table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Invoice #</th>
                    <th>Date</th>
                    <th>Due Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="invoice in invoices" :key="invoice.invoice_id">
                    <td>{{ invoice.invoice_number }}</td>
                    <td>{{ formatDate(invoice.invoice_date) }}</td>
                    <td>{{ formatDate(invoice.due_date) }}</td>
                    <td>{{ formatCurrency(invoice.total_amount) }}</td>
                    <td>
                      <span class="status-badge" :class="getInvoiceStatusClass(invoice.status)">
                        {{ invoice.status }}
                      </span>
                    </td>
                    <td class="actions-cell">
                      <button class="action-btn" @click="viewInvoice(invoice)" title="View Invoice">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button class="action-btn" @click="printInvoice(invoice)" title="Print Invoice">
                        <i class="fas fa-print"></i>
                      </button>
                      <button
                        class="action-btn"
                        @click="registerPayment(invoice)"
                        title="Register Payment"
                        :disabled="invoice.status === 'Paid'"
                      >
                        <i class="fas fa-money-bill-wave"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Quotations tab -->
          <div v-if="activeTab === 'quotations'" class="quotations-tab">
            <div class="tab-header">
              <h3>Quotations</h3>
              <button class="btn btn-primary" @click="createNewQuotation">
                <i class="fas fa-plus"></i> New Quotation
              </button>
            </div>

            <div v-if="isLoadingQuotations" class="loading-indicator">
              <i class="fas fa-spinner fa-spin"></i> Loading quotations...
            </div>

            <div v-else-if="!quotations || quotations.length === 0" class="empty-tab">
              <div class="empty-icon">
                <i class="fas fa-quote-right"></i>
              </div>
              <h4>No Quotations Found</h4>
              <p>This customer doesn't have any quotations yet.</p>
              <button class="btn btn-primary" @click="createNewQuotation">
                Create First Quotation
              </button>
            </div>

            <div v-else class="data-table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Quotation #</th>
                    <th>Date</th>
                    <th>Valid Until</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="quotation in quotations" :key="quotation.quotation_id">
                    <td>{{ quotation.quotation_number }}</td>
                    <td>{{ formatDate(quotation.quotation_date) }}</td>
                    <td>{{ formatDate(quotation.validity_date) }}</td>
                    <td>
                      <span class="status-badge" :class="getQuotationStatusClass(quotation.status)">
                        {{ quotation.status }}
                      </span>
                    </td>
                    <td class="actions-cell">
                      <button class="action-btn" @click="viewQuotation(quotation)" title="View Quotation">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button class="action-btn" @click="printQuotation(quotation)" title="Print Quotation">
                        <i class="fas fa-print"></i>
                      </button>
                      <button
                        class="action-btn"
                        @click="convertToOrder(quotation)"
                        title="Convert to Order"
                        :disabled="quotation.status !== 'Open'"
                      >
                        <i class="fas fa-exchange-alt"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Interactions tab -->
          <div v-if="activeTab === 'interactions'" class="interactions-tab">
            <div class="tab-header">
              <h3>Interactions</h3>
              <button class="btn btn-primary" @click="addInteraction">
                <i class="fas fa-plus"></i> Add Interaction
              </button>
            </div>

            <div v-if="isLoadingInteractions" class="loading-indicator">
              <i class="fas fa-spinner fa-spin"></i> Loading interactions...
            </div>

            <div v-else-if="!interactions || interactions.length === 0" class="empty-tab">
              <div class="empty-icon">
                <i class="fas fa-comments"></i>
              </div>
              <h4>No Interactions Found</h4>
              <p>There are no recorded interactions with this customer yet.</p>
              <button class="btn btn-primary" @click="addInteraction">
                Add First Interaction
              </button>
            </div>

            <div v-else class="interactions-list">
              <div
                v-for="interaction in interactions"
                :key="interaction.interaction_id"
                class="interaction-card"
              >
                <div class="interaction-header">
                  <div class="interaction-type">{{ interaction.interaction_type }}</div>
                  <div class="interaction-date">{{ formatDate(interaction.interaction_date) }}</div>
                </div>
                <div class="interaction-body">
                  {{ interaction.notes }}
                </div>
                <div class="interaction-footer">
                  <div class="interaction-user">By: {{ interaction.user?.name || 'System' }}</div>
                  <div class="interaction-actions">
                    <button class="action-btn" @click="editInteraction(interaction)" title="Edit">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn" @click="deleteInteraction(interaction)" title="Delete">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Interaction Modal -->
    <div v-if="showInteractionModal" class="modal">
      <div class="modal-backdrop" @click="closeInteractionModal"></div>
      <div class="modal-content">
        <div class="modal-header">
          <h2>{{ editingInteraction ? 'Edit Interaction' : 'Add New Interaction' }}</h2>
          <button class="close-btn" @click="closeInteractionModal">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveInteraction">
            <div class="form-row">
              <div class="form-group">
                <label for="interaction_type">Interaction Type*</label>
                <select
                  id="interaction_type"
                  v-model="interactionForm.interaction_type"
                  required
                >
                  <option value="">-- Select Type --</option>
                  <option value="Phone Call">Phone Call</option>
                  <option value="Email">Email</option>
                  <option value="Meeting">Meeting</option>
                  <option value="Site Visit">Site Visit</option>
                  <option value="Other">Other</option>
                </select>
              </div>
              <div class="form-group">
                <label for="interaction_date">Date*</label>
                <input
                  type="date"
                  id="interaction_date"
                  v-model="interactionForm.interaction_date"
                  required
                />
              </div>
            </div>

            <div class="form-group">
              <label for="notes">Notes*</label>
              <textarea
                id="notes"
                v-model="interactionForm.notes"
                rows="5"
                required
              ></textarea>
            </div>

            <div class="form-actions">
              <button type="button" class="btn btn-secondary" @click="closeInteractionModal">
                Cancel
              </button>
              <button type="submit" class="btn btn-primary">
                {{ editingInteraction ? 'Update Interaction' : 'Add Interaction' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Delete Interaction Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal">
      <div class="modal-backdrop" @click="closeDeleteModal"></div>
      <div class="modal-content modal-sm">
        <div class="modal-header">
          <h2>Confirm Delete</h2>
          <button class="close-btn" @click="closeDeleteModal">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete this interaction?</p>
          <p class="text-danger">This action cannot be undone.</p>

          <div class="form-actions">
            <button type="button" class="btn btn-secondary" @click="closeDeleteModal">
              Cancel
            </button>
            <button type="button" class="btn btn-danger" @click="confirmDeleteInteraction">
              Delete Interaction
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

export default {
  name: 'CustomerDetails',
  setup() {
    const route = useRoute();
    const router = useRouter();
    const customerId = ref(parseInt(route.params.id));

    // Data
    const customer = ref(null);
    const orders = ref([]);
    const invoices = ref([]);
    const quotations = ref([]);
    const interactions = ref([]);

    // Loading states
    const isLoading = ref(true);
    const isLoadingOrders = ref(false);
    const isLoadingInvoices = ref(false);
    const isLoadingQuotations = ref(false);
    const isLoadingInteractions = ref(false);

    // UI state
    const activeTab = ref('orders');
    const showInteractionModal = ref(false);
    const editingInteraction = ref(false);
    const interactionForm = ref({
      interaction_type: '',
      interaction_date: new Date().toISOString().substring(0, 10),
      notes: ''
    });
    const interactionToEdit = ref(null);
    const interactionToDelete = ref(null);
    const showDeleteModal = ref(false);

    // Stats
    const orderStats = computed(() => {
      return {
        count: orders.value.length,
        lastOrderDate: orders.value.length > 0 ?
          [...orders.value].sort((a, b) => new Date(b.so_date) - new Date(a.so_date))[0].so_date :
          null
      };
    });


    const invoiceStats = computed(() => {
      return {
        count: invoices.value.length,
        total: invoices.value.reduce((sum, invoice) => sum + (invoice.total_amount || 0), 0)
      };
    });

    // Fetch data
    const fetchCustomer = async () => {
      isLoading.value = true;
      try {
        const response = await axios.get(`/customers/${customerId.value}`);
        customer.value = response.data.data;
      } catch (error) {
        console.error('Error fetching customer:', error);
        customer.value = null;
      } finally {
        isLoading.value = false;
      }
    };

    const fetchOrders = async () => {
      isLoadingOrders.value = true;
      try {
        const response = await axios.get(`/customers/${customerId.value}/orders`);
        orders.value = response.data.data;
      } catch (error) {
        console.error('Error fetching orders:', error);
        orders.value = [];
      } finally {
        isLoadingOrders.value = false;
      }
    };

    const fetchInvoices = async () => {
  console.log('fetchInvoices called for customer:', customerId.value);

  isLoadingInvoices.value = true;
  try {
    const url = `/api/customers/${customerId.value}/invoices`;
    console.log('Fetching from URL:', url);

    const response = await axios.get(url);
    console.log('API Response:', response);
    console.log('Response data:', response.data);

    // Pastikan struktur data sesuai
    if (response.data && response.data.data) {
      invoices.value = response.data.data;
      console.log('Invoices set to:', invoices.value);
    } else if (response.data && Array.isArray(response.data)) {
      // Jika response langsung array
      invoices.value = response.data;
      console.log('Invoices set to (direct array):', invoices.value);
    } else {
      console.warn('Unexpected response structure:', response.data);
      invoices.value = [];
    }
  } catch (error) {
    console.error('Error fetching invoices:', error);
    console.error('Error details:', {
      message: error.message,
      status: error.response?.status,
      statusText: error.response?.statusText,
      data: error.response?.data
    });
    invoices.value = [];
  } finally {
    isLoadingInvoices.value = false;
    console.log('fetchInvoices completed. Final invoices:', invoices.value);
  }
};

    const fetchQuotations = async () => {
      isLoadingQuotations.value = true;
      try {
        const response = await axios.get(`/customers/${customerId.value}/quotations`);
        quotations.value = response.data.data;
      } catch (error) {
        console.error('Error fetching quotations:', error);
        quotations.value = [];
      } finally {
        isLoadingQuotations.value = false;
      }
    };

    const fetchInteractions = async () => {
      isLoadingInteractions.value = true;
      try {
        const response = await axios.get(`/interactions/customer/${customerId.value}`);
        interactions.value = response.data.data;
      } catch (error) {
        console.error('Error fetching interactions:', error);
        interactions.value = [];
      } finally {
        isLoadingInteractions.value = false;
      }
    };

    // Actions
    const goBack = () => {
      router.push('/sales/customers');
    };

    const editCustomer = () => {
      router.push(`/sales/customers/edit/${customerId.value}`);
    };

    const createNewOrder = () => {
      router.push({
        path: '/sales/orders/create',
        query: { customer_id: customerId.value }
      });
    };

    const createNewQuotation = () => {
      router.push({
        path: '/sales/quotations/create',
        query: { customer_id: customerId.value }
      });
    };

    const viewOrder = (order) => {
      router.push(`/sales/orders/${order.so_id}`);
    };

    const viewInvoice = (invoice) => {
      router.push(`/sales/invoices/${invoice.invoice_id}`);
    };

    const viewQuotation = (quotation) => {
      router.push(`/sales/quotations/${quotation.quotation_id}`);
    };

    const printInvoice = (invoice) => {
      router.push(`/sales/invoices/${invoice.invoice_id}/print`);
    };

    const printQuotation = (quotation) => {
      router.push(`/sales/quotations/${quotation.quotation_id}/print`);
    };

    const createDelivery = (order) => {
      router.push({
        path: '/sales/deliveries/create',
        query: { order_id: order.so_id }
      });
    };

    const createInvoice = (order) => {
      router.push({
        path: '/sales/invoices/create',
        query: { order_id: order.so_id }
      });
    };

    const registerPayment = (invoice) => {
      router.push({
        path: `/sales/invoices/${invoice.invoice_id}/payment`
      });
    };

    const convertToOrder = (quotation) => {
      router.push({
        path: '/sales/orders/create-from-quotation',
        query: { quotation_id: quotation.quotation_id }
      });
    };

    const canCreateDelivery = (order) => {
      return ['Confirmed'].includes(order.status);
    };

    const canCreateInvoice = (order) => {
      return ['Confirmed', 'Delivered'].includes(order.status);
    };

    // Handle active tab
    const setActiveTab = async (tab) => {
  console.log('Setting active tab to:', tab);
  console.log('Current invoices length:', invoices.value?.length);

  activeTab.value = tab;

  // Load data untuk tab yang dipilih
  try {
    switch (tab) {
      case 'orders':
        if (!orders.value || orders.value.length === 0) {
          console.log('Fetching orders...');
          await fetchOrders();
        }
        break;
      case 'invoices':
        console.log('Invoice tab clicked, checking if need to fetch...');
        // Pastikan selalu fetch jika belum ada data atau array kosong
        if (!invoices.value || invoices.value.length === 0) {
          console.log('Fetching invoices...');
          await fetchInvoices();
        } else {
          console.log('Invoices already loaded:', invoices.value.length);
        }
        break;
      case 'quotations':
        if (!quotations.value || quotations.value.length === 0) {
          console.log('Fetching quotations...');
          await fetchQuotations();
        }
        break;
      case 'interactions':
        if (!interactions.value || interactions.value.length === 0) {
          console.log('Fetching interactions...');
          await fetchInteractions();
        }
        break;
    }
  } catch (error) {
    console.error(`Error loading ${tab} data:`, error);
  }
};

    // Interaction modal
    const addInteraction = () => {
      editingInteraction.value = false;
      interactionForm.value = {
        interaction_type: '',
        interaction_date: new Date().toISOString().substring(0, 10),
        notes: ''
      };
      showInteractionModal.value = true;
    };

    const editInteraction = (interaction) => {
      editingInteraction.value = true;
      interactionToEdit.value = interaction;
      interactionForm.value = {
        interaction_type: interaction.interaction_type,
        interaction_date: interaction.interaction_date.substring(0, 10),
        notes: interaction.notes
      };
      showInteractionModal.value = true;
    };

    const closeInteractionModal = () => {
      showInteractionModal.value = false;
      interactionToEdit.value = null;
    };

    const saveInteraction = async () => {
      try {
        if (editingInteraction.value) {
          // Update existing interaction
          await axios.put(`/interactions/${interactionToEdit.value.interaction_id}`, {
            ...interactionForm.value,
            customer_id: customerId.value
          });

          // Update in the local state
          const index = interactions.value.findIndex(i => i.interaction_id === interactionToEdit.value.interaction_id);
          if (index !== -1) {
            interactions.value[index] = {
              ...interactionToEdit.value,
              ...interactionForm.value
            };
          }

          alert('Interaction updated successfully!');
        } else {
          // Create new interaction
          const response = await axios.post('/interactions', {
            ...interactionForm.value,
            customer_id: customerId.value
          });

          // Add to local state
          interactions.value.unshift(response.data.data);

          alert('Interaction added successfully!');
        }

        closeInteractionModal();
      } catch (error) {
        console.error('Error saving interaction:', error);
        alert('An error occurred while saving the interaction. Please try again.');
      }
    };

    // Delete interaction
    const deleteInteraction = (interaction) => {
      interactionToDelete.value = interaction;
      showDeleteModal.value = true;
    };

    const closeDeleteModal = () => {
      showDeleteModal.value = false;
      interactionToDelete.value = null;
    };

    const confirmDeleteInteraction = async () => {
      try {
        await axios.delete(`/interactions/${interactionToDelete.value.interaction_id}`);

        // Remove from local state
        interactions.value = interactions.value.filter(
          i => i.interaction_id !== interactionToDelete.value.interaction_id
        );

        closeDeleteModal();
        alert('Interaction deleted successfully!');
      } catch (error) {
        console.error('Error deleting interaction:', error);
        alert('An error occurred while deleting the interaction. Please try again.');
      }
    };

    // Helper methods
    const formatDate = (dateString) => {
      if (!dateString) return '-';

      const date = new Date(dateString);
      return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      }).format(date);
    };

    const formatCurrency = (amount) => {
      if (amount === undefined || amount === null) return '-';

      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
      }).format(amount);
    };

    const getOrderStatusClass = (status) => {
      switch (status) {
        case 'Pending': return 'pending';
        case 'Confirmed': return 'confirmed';
        case 'Delivering': return 'delivering';
        case 'Delivered': return 'delivered';
        case 'Invoiced': return 'invoiced';
        case 'Closed': return 'closed';
        case 'Cancelled': return 'cancelled';
        default: return '';
      }
    };

    const getInvoiceStatusClass = (status) => {
      switch (status) {
        case 'Open': return 'open';
        case 'Paid': return 'paid';
        case 'Overdue': return 'overdue';
        case 'Closed': return 'closed';
        default: return '';
      }
    };

    const getQuotationStatusClass = (status) => {
      switch (status) {
        case 'Open': return 'open';
        case 'Converted': return 'converted';
        case 'Expired': return 'expired';
        case 'Rejected': return 'rejected';
        default: return '';
      }
    };

    // Initialize
    onMounted(async () => {
      await fetchCustomer();
      if (customer.value) {
        await fetchOrders();
      }
    });

    // Watch for route changes (if navigating between different customers)
    watch(() => route.params.id, async (newId) => {
      if (newId && parseInt(newId) !== customerId.value) {
        customerId.value = parseInt(newId);

        // Reset data
        customer.value = null;
        orders.value = [];
        invoices.value = [];
        quotations.value = [];
        interactions.value = [];

        // Load new customer data
        await fetchCustomer();
        if (customer.value) {
          await setActiveTab(activeTab.value);
        }
      }
    });

    return {
      customer,
      orders,
      invoices,
      quotations,
      interactions,
      isLoading,
      isLoadingOrders,
      isLoadingInvoices,
      isLoadingQuotations,
      isLoadingInteractions,
      activeTab,
      orderStats,
      invoiceStats,
      showInteractionModal,
      editingInteraction,
      interactionForm,
      showDeleteModal,
      goBack,
      editCustomer,
      createNewOrder,
      createNewQuotation,
      viewOrder,
      viewInvoice,
      viewQuotation,
      printInvoice,
      printQuotation,
      createDelivery,
      createInvoice,
      registerPayment,
      convertToOrder,
      canCreateDelivery,
      canCreateInvoice,
      setActiveTab,
      addInteraction,
      editInteraction,
      closeInteractionModal,
      saveInteraction,
      deleteInteraction,
      closeDeleteModal,
      confirmDeleteInteraction,
      formatDate,
      formatCurrency,
      getOrderStatusClass,
      getInvoiceStatusClass,
      getQuotationStatusClass
    };
  }
};
</script>

<style scoped>
.customer-details-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.page-title h2 {
  margin: 0;
  font-size: 1.5rem;
  color: #1e293b;
}

.page-actions {
  display: flex;
  gap: 1rem;
}

.btn {
  padding: 0.625rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 0.375rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  border: none;
  transition: background-color 0.2s, color 0.2s;
}

.btn-primary {
  background-color: #2563eb;
  color: white;
}

.btn-primary:hover {
  background-color: #1d4ed8;
}

.btn-secondary {
  background-color: #e2e8f0;
  color: #1e293b;
}

.btn-secondary:hover {
  background-color: #cbd5e1;
}

.btn-danger {
  background-color: #dc2626;
  color: white;
}

.btn-danger:hover {
  background-color: #b91c1c;
}

.loading-indicator {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 2rem 0;
  color: #64748b;
}

.loading-indicator i {
  margin-right: 0.5rem;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 0;
  text-align: center;
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  color: #cbd5e1;
}

.empty-state h3 {
  font-size: 1.25rem;
  margin: 0 0 0.5rem 0;
  color: #1e293b;
}

.empty-state p {
  margin: 0 0 1.5rem 0;
  color: #64748b;
}

.customer-content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.customer-info-card {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  background-color: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.card-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
}

.card-body {
  padding: 1.5rem;
}

.customer-status {
  font-size: 0.75rem;
  font-weight: 500;
  padding: 0.25rem 0.75rem;
  border-radius: 0.25rem;
  background-color: #d1fae5;
  color: #059669;
}

.customer-status.inactive {
  background-color: #f3f4f6;
  color: #6b7280;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.25rem;
}

.info-item {
  display: flex;
  flex-direction: column;
}

.info-item.full-width {
  grid-column: 1 / -1;
}

.info-label {
  font-size: 0.75rem;
  color: #64748b;
  margin-bottom: 0.25rem;
}

.info-value {
  font-size: 0.875rem;
  color: #1e293b;
}

.customer-summary-cards {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
}

.summary-card {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  padding: 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.summary-icon {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
}

.orders-icon {
  background-color: #dbeafe;
  color: #2563eb;
}

.invoices-icon {
  background-color: #fef3c7;
  color: #d97706;
}

.amount-icon {
  background-color: #d1fae5;
  color: #059669;
}

.last-order-icon {
  background-color: #ede9fe;
  color: #7c3aed;
}

.summary-content {
  display: flex;
  flex-direction: column;
}

.summary-value {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e293b;
}

.summary-label {
  font-size: 0.75rem;
  color: #64748b;
}

.tabs-container {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.tabs {
  display: flex;
  border-bottom: 1px solid #e2e8f0;
}

.tab {
  padding: 1rem 1.25rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #64748b;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  border-bottom: 2px solid transparent;
  transition: all 0.2s;
}

.tab:hover {
  color: #2563eb;
}

.tab.active {
  color: #2563eb;
  border-bottom-color: #2563eb;
}

.tab i {
  font-size: 0.875rem;
}

.tab-content {
  padding: 1.5rem;
  min-height: 300px;
}

.tab-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.tab-header h3 {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 600;
}

.empty-tab {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 0;
  text-align: center;
}

.empty-tab .empty-icon {
  font-size: 2.5rem;
  margin-bottom: 1rem;
  color: #cbd5e1;
}

.empty-tab h4 {
  font-size: 1.125rem;
  margin: 0 0 0.5rem 0;
  color: #1e293b;
}

.empty-tab p {
  margin: 0 0 1.5rem 0;
  color: #64748b;
}

.data-table-container {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th {
  text-align: left;
  padding: 0.75rem 1rem;
  background-color: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  font-weight: 500;
  font-size: 0.75rem;
  color: #64748b;
}

.data-table td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #f1f5f9;
  font-size: 0.875rem;
  color: #334155;
}

.data-table tr:hover td {
  background-color: #f8fafc;
}

.actions-cell {
  display: flex;
  gap: 0.5rem;
}

.action-btn {
  background: none;
  border: none;
  color: #64748b;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 0.25rem;
  transition: background-color 0.2s, color 0.2s;
}

.action-btn:hover:not(:disabled) {
  background-color: #f1f5f9;
  color: #0f172a;
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  font-weight: 500;
}

/* Order status colors */
.status-badge.pending {
  background-color: #fef3c7;
  color: #d97706;
}

.status-badge.confirmed {
  background-color: #e0f2fe;
  color: #0284c7;
}

.status-badge.delivering {
  background-color: #ede9fe;
  color: #7c3aed;
}

.status-badge.delivered {
  background-color: #d1fae5;
  color: #059669;
}

.status-badge.invoiced {
  background-color: #dbeafe;
  color: #2563eb;
}

.status-badge.closed {
  background-color: #e5e7eb;
  color: #4b5563;
}

.status-badge.cancelled {
  background-color: #fee2e2;
  color: #dc2626;
}

/* Invoice status colors */
.status-badge.open {
  background-color: #e0f2fe;
  color: #0284c7;
}

.status-badge.paid {
  background-color: #d1fae5;
  color: #059669;
}

.status-badge.overdue {
  background-color: #fee2e2;
  color: #dc2626;
}

/* Quotation status colors */
.status-badge.converted {
  background-color: #d1fae5;
  color: #059669;
}

.status-badge.expired {
  background-color: #e5e7eb;
  color: #4b5563;
}

.status-badge.rejected {
  background-color: #fee2e2;
  color: #dc2626;
}

/* Interactions */
.interactions-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.interaction-card {
  background-color: #f8fafc;
  border-radius: 0.5rem;
  padding: 1rem;
  border: 1px solid #e2e8f0;
}

.interaction-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.75rem;
}

.interaction-type {
  font-weight: 500;
  color: #1e293b;
}

.interaction-date {
  font-size: 0.75rem;
  color: #64748b;
}

.interaction-body {
  font-size: 0.875rem;
  color: #334155;
  margin-bottom: 0.75rem;
  white-space: pre-line;
}

.interaction-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.75rem;
  color: #64748b;
}

.interaction-actions {
  display: flex;
  gap: 0.5rem;
}

/* Modal styles */
.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 50;
  display: flex;
  justify-content: center;
  align-items: center;
}

.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 50;
}

.modal-content {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 600px;
  z-index: 60;
  overflow: hidden;
}

.modal-sm {
  max-width: 400px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h2 {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0;
  color: #1e293b;
}

.close-btn {
  background: none;
  border: none;
  color: #64748b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.25rem;
  border-radius: 0.25rem;
}

.close-btn:hover {
  background-color: #f1f5f9;
  color: #0f172a;
}

.modal-body {
  padding: 1.5rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group:last-child {
  margin-bottom: 0;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #1e293b;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 0.625rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.375rem;
  font-size: 0.875rem;
}

.form-group textarea {
  resize: vertical;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 1rem;
}

.text-danger {
  color: #dc2626;
}

@media (max-width: 1024px) {
  .customer-summary-cards {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .page-actions {
    width: 100%;
    justify-content: flex-end;
  }

  .info-grid {
    grid-template-columns: 1fr;
  }

  .customer-summary-cards {
    grid-template-columns: 1fr;
  }

  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>
