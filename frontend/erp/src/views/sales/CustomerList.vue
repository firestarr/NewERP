<!-- src/views/sales/CustomersList.vue -->
<template>
    <div class="customers-list">
        <!-- Search and Filter Section -->
        <div class="page-actions">
            <div class="search-box">
                <i class="fas fa-search search-icon"></i>
                <input
                    type="text"
                    v-model="searchQuery"
                    placeholder="Search customers..."
                    @input="applyFilters"
                />
                <button
                    v-if="searchQuery"
                    @click="clearSearch"
                    class="clear-search"
                >
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="filters">
                <select v-model="statusFilter" @change="applyFilters">
                    <option value="">All Status</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>

            <button class="btn btn-primary" @click="openAddCustomerModal">
                <i class="fas fa-plus"></i> Add Customer
            </button>
        </div>

        <!-- Customers Grid -->
        <div class="customers-container">
            <div v-if="isLoading" class="loading-indicator">
                <i class="fas fa-spinner fa-spin"></i> Loading customers...
            </div>

            <div v-else-if="filteredCustomers.length === 0" class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>No customers found</h3>
                <p>Try adjusting your search or add a new customer.</p>
            </div>

            <div v-else class="customers-grid">
                <div
                    v-for="customer in filteredCustomers"
                    :key="customer.customer_id"
                    class="customer-card"
                    @click="viewCustomerDetails(customer)"
                >
                    <div class="customer-header">
                        <div
                            class="customer-status"
                            :class="{ inactive: customer.status !== 'Active' }"
                        >
                            {{ customer.status }}
                        </div>
                        <div class="customer-actions">
                            <button
                                class="action-btn"
                                title="Edit Customer"
                                @click.stop="editCustomer(customer)"
                            >
                                <i class="fas fa-edit"></i>
                            </button>
                            <button
                                class="action-btn"
                                title="Delete Customer"
                                @click.stop="confirmDelete(customer)"
                            >
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    <div class="customer-content">
                        <div class="customer-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="customer-info">
                            <h3 class="customer-name">{{ customer.name }}</h3>
                            <div class="customer-code">
                                {{ customer.customer_code }}
                            </div>
                            <p v-if="customer.email" class="customer-email">
                                <i class="fas fa-envelope"></i>
                                {{ customer.email }}
                            </p>
                            <p v-if="customer.phone" class="customer-phone">
                                <i class="fas fa-phone"></i>
                                {{ customer.phone }}
                            </p>
                        </div>
                    </div>

                    <div class="customer-footer">
                        <div class="customer-contact">
                            <span v-if="customer.contact_person">{{
                                customer.contact_person
                            }}</span>
                            <span v-else class="no-data"
                                >No contact person</span
                            >
                        </div>
                        <div class="view-details">
                            <i class="fas fa-eye"></i> View Details
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add/Edit Customer Modal -->
        <div v-if="showCustomerModal" class="modal">
            <div class="modal-backdrop" @click="closeCustomerModal"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h2>
                        {{ isEditMode ? "Edit Customer" : "Add New Customer" }}
                    </h2>
                    <button class="close-btn" @click="closeCustomerModal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="saveCustomer">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="customer_code"
                                    >Customer Code*</label
                                >
                                <input
                                    type="text"
                                    id="customer_code"
                                    v-model="customerForm.customer_code"
                                    required
                                    :disabled="isEditMode"
                                />
                            </div>
                            <div class="form-group">
                                <label for="name">Name*</label>
                                <input
                                    type="text"
                                    id="name"
                                    v-model="customerForm.name"
                                    required
                                />
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea
                                id="address"
                                v-model="customerForm.address"
                                rows="3"
                            ></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="contact_person"
                                    >Contact Person</label
                                >
                                <input
                                    type="text"
                                    id="contact_person"
                                    v-model="customerForm.contact_person"
                                />
                            </div>
                            <div class="form-group">
                                <label for="tax_id">Tax ID</label>
                                <input
                                    type="text"
                                    id="tax_id"
                                    v-model="customerForm.tax_id"
                                />
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input
                                    type="text"
                                    id="phone"
                                    v-model="customerForm.phone"
                                />
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input
                                    type="email"
                                    id="email"
                                    v-model="customerForm.email"
                                />
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="checkbox-group">
                                <input
                                    type="checkbox"
                                    id="is_active"
                                    v-model="isActive"
                                />
                                <label for="is_active">Active</label>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button
                                type="button"
                                class="btn btn-secondary"
                                @click="closeCustomerModal"
                            >
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                {{
                                    isEditMode
                                        ? "Update Customer"
                                        : "Add Customer"
                                }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
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
                    <p>
                        Are you sure you want to delete
                        <strong>{{ customerToDelete.name }}</strong
                        >?
                    </p>
                    <p class="text-danger">This action cannot be undone.</p>

                    <div class="form-actions">
                        <button
                            type="button"
                            class="btn btn-secondary"
                            @click="closeDeleteModal"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            class="btn btn-danger"
                            @click="deleteCustomer"
                        >
                            Delete Customer
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Details Modal -->
        <div v-if="showDetailsModal" class="modal">
            <div class="modal-backdrop" @click="closeDetailsModal"></div>
            <div class="modal-content modal-lg">
                <div class="modal-header">
                    <h2>Customer Details</h2>
                    <button class="close-btn" @click="closeDetailsModal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div v-if="selectedCustomer" class="customer-details">
                        <div class="details-section">
                            <h3 class="section-title">Basic Information</h3>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <div class="detail-label">
                                        Customer Code
                                    </div>
                                    <div class="detail-value">
                                        {{ selectedCustomer.customer_code }}
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Name</div>
                                    <div class="detail-value">
                                        {{ selectedCustomer.name }}
                                    </div>
                                </div>
                                <div class="detail-item full-width">
                                    <div class="detail-label">Address</div>
                                    <div class="detail-value">
                                        {{
                                            selectedCustomer.address ||
                                            "Not provided"
                                        }}
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">
                                        Contact Person
                                    </div>
                                    <div class="detail-value">
                                        {{
                                            selectedCustomer.contact_person ||
                                            "Not provided"
                                        }}
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Tax ID</div>
                                    <div class="detail-value">
                                        {{
                                            selectedCustomer.tax_id ||
                                            "Not provided"
                                        }}
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Phone</div>
                                    <div class="detail-value">
                                        {{
                                            selectedCustomer.phone ||
                                            "Not provided"
                                        }}
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Email</div>
                                    <div class="detail-value">
                                        {{
                                            selectedCustomer.email ||
                                            "Not provided"
                                        }}
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Status</div>
                                    <div class="detail-value">
                                        <span
                                            class="customer-status-badge"
                                            :class="
                                                selectedCustomer.status.toLowerCase()
                                            "
                                        >
                                            {{ selectedCustomer.status }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Debug Panel -->
                        <!-- <div class="debug-panel" style="background: #f3f4f6; padding: 10px; margin: 10px 0; border-radius: 5px;">
                            <strong>Debug Info:</strong><br>
                            Active Tab: {{ activeTab }}<br>
                            Selected Customer: {{ selectedCustomer ? selectedCustomer.name : 'None' }}<br>
                            Loading States - Orders: {{ isLoadingOrders }}, Invoices: {{ isLoadingInvoices }}, Quotations: {{ isLoadingQuotations }}, Interactions: {{ isLoadingInteractions }}<br>
                            Data Counts - Orders: {{ customerOrders?.length || 0 }}, Invoices: {{ customerInvoices?.length || 0 }}, Quotations: {{ customerQuotations?.length || 0 }}, Interactions: {{ customerInteractions?.length || 0 }}
                        </div> -->

                        <!-- Fixed Tabs -->
                        <div class="tabs">
                            <div
                                class="tab"
                                :class="{ active: activeTab === 'orders' }"
                                @click="setActiveTab('orders')"
                            >
                                Orders
                            </div>
                            <div
                                class="tab"
                                :class="{ active: activeTab === 'invoices' }"
                                @click="setActiveTab('invoices')"
                            >
                                Invoices
                            </div>
                            <div
                                class="tab"
                                :class="{ active: activeTab === 'quotations' }"
                                @click="setActiveTab('quotations')"
                            >
                                Quotations
                            </div>
                            <div
                                class="tab"
                                :class="{
                                    active: activeTab === 'interactions',
                                }"
                                @click="setActiveTab('interactions')"
                            >
                                Interactions
                            </div>
                        </div>

                        <div class="tab-content">
                            <!-- Orders Tab -->
                            <div
                                v-if="activeTab === 'orders'"
                                class="orders-tab"
                            >
                                <div
                                    v-if="isLoadingOrders"
                                    class="loading-indicator"
                                >
                                    <i class="fas fa-spinner fa-spin"></i>
                                    Loading orders...
                                </div>
                                <div
                                    v-else-if="
                                        !customerOrders ||
                                        customerOrders.length === 0
                                    "
                                    class="empty-tab-state"
                                >
                                    <i class="fas fa-shopping-cart"></i>
                                    <p>No orders found for this customer</p>
                                </div>
                                <div v-else class="data-table-wrapper">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Order #</th>
                                                <th>Date</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="order in customerOrders"
                                                :key="order.so_id"
                                            >
                                                <td>{{ order.so_number }}</td>
                                                <td>
                                                    {{
                                                        formatDate(
                                                            order.so_date
                                                        )
                                                    }}
                                                </td>
                                                <td>
                                                    {{
                                                        formatCurrency(
                                                            order.total_amount
                                                        )
                                                    }}
                                                </td>
                                                <td>
                                                    <span
                                                        class="status-badge"
                                                        :class="
                                                            getOrderStatusClass(
                                                                order.status
                                                            )
                                                        "
                                                    >
                                                        {{ order.status }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <button
                                                        class="action-btn"
                                                        @click="
                                                            viewOrder(order)
                                                        "
                                                    >
                                                        <i
                                                            class="fas fa-eye"
                                                        ></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Invoices Tab -->
                            <div
                                v-if="activeTab === 'invoices'"
                                class="invoices-tab"
                            >
                                <div
                                    v-if="isLoadingInvoices"
                                    class="loading-indicator"
                                >
                                    <i class="fas fa-spinner fa-spin"></i>
                                    Loading invoices...
                                </div>
                                <div
                                    v-else-if="
                                        !customerInvoices ||
                                        customerInvoices.length === 0
                                    "
                                    class="empty-tab-state"
                                >
                                    <i class="fas fa-file-invoice-dollar"></i>
                                    <p>No invoices found for this customer</p>
                                    <p style="color: #666; font-size: 12px;">
                                        Debug: customerInvoices = {{ customerInvoices }}, length = {{ customerInvoices?.length }}
                                    </p>
                                </div>
                                <div v-else class="data-table-wrapper">
                                    <p style="color: #666; margin-bottom: 10px;">
                                        Showing {{ customerInvoices.length }} invoice(s)
                                    </p>
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
                                            <tr
                                                v-for="invoice in customerInvoices"
                                                :key="invoice.invoice_id"
                                            >
                                                <td>
                                                    {{ invoice.invoice_number }}
                                                </td>
                                                <td>
                                                    {{
                                                        formatDate(
                                                            invoice.invoice_date
                                                        )
                                                    }}
                                                </td>
                                                <td>
                                                    {{
                                                        formatDate(
                                                            invoice.due_date
                                                        )
                                                    }}
                                                </td>
                                                <td>
                                                    {{
                                                        formatCurrency(
                                                            invoice.total_amount
                                                        )
                                                    }}
                                                </td>
                                                <td>
                                                    <span
                                                        class="status-badge"
                                                        :class="
                                                            getInvoiceStatusClass(
                                                                invoice.status
                                                            )
                                                        "
                                                    >
                                                        {{ invoice.status }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <button
                                                        class="action-btn"
                                                        @click="
                                                            viewInvoice(invoice)
                                                        "
                                                    >
                                                        <i
                                                            class="fas fa-eye"
                                                        ></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Quotations Tab -->
                            <div
                                v-if="activeTab === 'quotations'"
                                class="quotations-tab"
                            >
                                <div
                                    v-if="isLoadingQuotations"
                                    class="loading-indicator"
                                >
                                    <i class="fas fa-spinner fa-spin"></i>
                                    Loading quotations...
                                </div>
                                <div
                                    v-else-if="
                                        !customerQuotations ||
                                        customerQuotations.length === 0
                                    "
                                    class="empty-tab-state"
                                >
                                    <i class="fas fa-quote-right"></i>
                                    <p>No quotations found for this customer</p>
                                </div>
                                <div v-else class="data-table-wrapper">
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
                                            <tr
                                                v-for="quotation in customerQuotations"
                                                :key="quotation.quotation_id"
                                            >
                                                <td>
                                                    {{
                                                        quotation.quotation_number
                                                    }}
                                                </td>
                                                <td>
                                                    {{
                                                        formatDate(
                                                            quotation.quotation_date
                                                        )
                                                    }}
                                                </td>
                                                <td>
                                                    {{
                                                        formatDate(
                                                            quotation.validity_date
                                                        )
                                                    }}
                                                </td>
                                                <td>
                                                    <span
                                                        class="status-badge"
                                                        :class="
                                                            getQuotationStatusClass(
                                                                quotation.status
                                                            )
                                                        "
                                                    >
                                                        {{ quotation.status }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <button
                                                        class="action-btn"
                                                        @click="
                                                            viewQuotation(
                                                                quotation
                                                            )
                                                        "
                                                    >
                                                        <i
                                                            class="fas fa-eye"
                                                        ></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Interactions Tab -->
                            <div
                                v-if="activeTab === 'interactions'"
                                class="interactions-tab"
                            >
                                <div
                                    v-if="isLoadingInteractions"
                                    class="loading-indicator"
                                >
                                    <i class="fas fa-spinner fa-spin"></i>
                                    Loading interactions...
                                </div>
                                <div
                                    v-else-if="
                                        !customerInteractions ||
                                        customerInteractions.length === 0
                                    "
                                    class="empty-tab-state"
                                >
                                    <i class="fas fa-comments"></i>
                                    <p>
                                        No interactions recorded for this
                                        customer
                                    </p>
                                </div>
                                <div v-else class="interaction-list">
                                    <div
                                        v-for="interaction in customerInteractions"
                                        :key="interaction.interaction_id"
                                        class="interaction-item"
                                    >
                                        <div class="interaction-header">
                                            <div class="interaction-type">
                                                {{
                                                    interaction.interaction_type
                                                }}
                                            </div>
                                            <div class="interaction-date">
                                                {{
                                                    formatDate(
                                                        interaction.interaction_date
                                                    )
                                                }}
                                            </div>
                                        </div>
                                        <div class="interaction-notes">
                                            {{ interaction.notes }}
                                        </div>
                                        <div class="interaction-footer">
                                            <div class="interaction-user">
                                                By:
                                                {{
                                                    interaction.user
                                                        ? interaction.user.name
                                                        : "Unknown"
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <div class="footer-actions">
                                <button
                                    class="btn btn-secondary"
                                    @click="closeDetailsModal"
                                >
                                    Close
                                </button>
                                <button
                                    class="btn btn-primary"
                                    @click="editCustomer(selectedCustomer)"
                                >
                                    <i class="fas fa-edit"></i> Edit Customer
                                </button>
                                <button
                                    class="btn btn-secondary"
                                    @click="forceRefreshInvoices"
                                >
                                    🔄 Force Refresh Invoices
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";

export default {
    name: "CustomersList",
    setup() {
        // console.log('CustomersList component initialized');

        const router = useRouter();
        const customers = ref([]);
        const isLoading = ref(true);
        const searchQuery = ref("");
        const statusFilter = ref("");

        // Modals
        const showCustomerModal = ref(false);
        const showDeleteModal = ref(false);
        const showDetailsModal = ref(false);
        const isEditMode = ref(false);
        const customerForm = ref({
            customer_code: "",
            name: "",
            address: "",
            tax_id: "",
            contact_person: "",
            phone: "",
            email: "",
            status: "Active",
        });
        const isActive = ref(true);
        const customerToDelete = ref({});
        const selectedCustomer = ref(null);

        // Customer details tabs
        const activeTab = ref("orders");
        const customerOrders = ref([]);
        const customerInvoices = ref([]);
        const customerQuotations = ref([]);
        const customerInteractions = ref([]);
        const isLoadingOrders = ref(false);
        const isLoadingInvoices = ref(false);
        const isLoadingQuotations = ref(false);
        const isLoadingInteractions = ref(false);

        // Computed properties
        const filteredCustomers = computed(() => {
            let result = [...customers.value];

            // Apply search filter
            if (searchQuery.value) {
                const query = searchQuery.value.toLowerCase();
                result = result.filter(
                    (customer) =>
                        customer.name.toLowerCase().includes(query) ||
                        customer.customer_code.toLowerCase().includes(query) ||
                        (customer.email &&
                            customer.email.toLowerCase().includes(query)) ||
                        (customer.phone &&
                            customer.phone.toLowerCase().includes(query)) ||
                        (customer.contact_person &&
                            customer.contact_person
                                .toLowerCase()
                                .includes(query))
                );
            }

            // Apply status filter
            if (statusFilter.value) {
                result = result.filter(
                    (customer) => customer.status === statusFilter.value
                );
            }

            return result;
        });

        // FIXED: setActiveTab function
        const setActiveTab = async (tab) => {
            // console.log('=== CustomersList - Setting active tab to:', tab);
            // console.log('Selected customer:', selectedCustomer.value);

            activeTab.value = tab;

            if (!selectedCustomer.value) {
                // console.log('No selected customer, skipping fetch');
                return;
            }

            const customerId = selectedCustomer.value.customer_id;
            // console.log('Customer ID:', customerId);

            try {
                switch (tab) {
                    case 'orders':
                        // console.log('Loading orders...');
                        if (customerOrders.value.length === 0) {
                            await fetchCustomerOrders(customerId);
                        } else {
                            // console.log('Orders already loaded:', customerOrders.value.length);
                        }
                        break;

                    case 'invoices':
                        // console.log('Loading invoices...');
                        if (customerInvoices.value.length === 0) {
                            await fetchCustomerInvoices(customerId);
                        } else {
                            // console.log('Invoices already loaded:', customerInvoices.value.length);
                        }
                        break;

                    case 'quotations':
                        // console.log('Loading quotations...');
                        if (customerQuotations.value.length === 0) {
                            await fetchCustomerQuotations(customerId);
                        } else {
                            // console.log('Quotations already loaded:', customerQuotations.value.length);
                        }
                        break;

                    case 'interactions':
                        // console.log('Loading interactions...');
                        if (customerInteractions.value.length === 0) {
                            await fetchCustomerInteractions(customerId);
                        } else {
                            // console.log('Interactions already loaded:', customerInteractions.value.length);
                        }
                        break;
                }
            } catch (error) {
                console.error(`Error loading ${tab} data:`, error);
            }
        };

        // Force refresh function untuk debugging
        const forceRefreshInvoices = async () => {
            // console.log('Force refreshing invoices...');
            if (!selectedCustomer.value) {
                // console.log('No selected customer');
                return;
            }

            customerInvoices.value = [];
            await fetchCustomerInvoices(selectedCustomer.value.customer_id);
        };

        // Methods
        const fetchCustomers = async () => {
            isLoading.value = true;

            try {
                const response = await axios.get("/customers");
                customers.value = response.data.data;
            } catch (error) {
                console.error("Error fetching customers:", error);
                // Use dummy data if API call fails
                customers.value = [
                    {
                        customer_id: 1,
                        customer_code: "CUST001",
                        name: "Acme Corporation",
                        address: "123 Main St, City",
                        tax_id: "TAX123456",
                        contact_person: "John Doe",
                        phone: "555-1234",
                        email: "contact@acme.com",
                        status: "Active",
                    },
                    {
                        customer_id: 2,
                        customer_code: "CUST002",
                        name: "XYZ Industries",
                        address: "456 Business Ave, Town",
                        tax_id: "TAX789012",
                        contact_person: "Jane Smith",
                        phone: "555-5678",
                        email: "info@xyz.com",
                        status: "Active",
                    },
                    {
                        customer_id: 3,
                        customer_code: "CUST003",
                        name: "Global Enterprises",
                        address: "789 Commerce Blvd, City",
                        tax_id: "TAX345678",
                        contact_person: "Bob Johnson",
                        phone: "555-9012",
                        email: "sales@global.com",
                        status: "Inactive",
                    },
                ];
            } finally {
                isLoading.value = false;
            }
        };

        const applyFilters = () => {
            // Nothing specific to do for filtering customers
            // The computed property handles the filtering
        };

        const clearSearch = () => {
            searchQuery.value = "";
        };

        const openAddCustomerModal = () => {
            isEditMode.value = false;
            customerForm.value = {
                customer_code: "",
                name: "",
                address: "",
                tax_id: "",
                contact_person: "",
                phone: "",
                email: "",
                status: "Active",
            };
            isActive.value = true;
            showCustomerModal.value = true;
        };

        const editCustomer = (customer) => {
            isEditMode.value = true;
            customerForm.value = {
                customer_id: customer.customer_id,
                customer_code: customer.customer_code,
                name: customer.name,
                address: customer.address || "",
                tax_id: customer.tax_id || "",
                contact_person: customer.contact_person || "",
                phone: customer.phone || "",
                email: customer.email || "",
                status: customer.status,
            };
            isActive.value = customer.status === "Active";

            // Close details modal if open
            if (showDetailsModal.value) {
                showDetailsModal.value = false;
            }

            showCustomerModal.value = true;
        };

        const closeCustomerModal = () => {
            showCustomerModal.value = false;
        };

        const saveCustomer = async () => {
            try {
                // Set status based on isActive checkbox
                customerForm.value.status = isActive.value
                    ? "Active"
                    : "Inactive";

                // console.log(
                //     "Saving customer with payload:",
                //     customerForm.value
                // );

                if (isEditMode.value) {
                    // Update existing customer
                     await axios.put(
                        `/customers/${customerForm.value.customer_id}`,
                        customerForm.value
                    );
                    // console.log(
                    //     "Customer update successful. Response:",
                    //     response.data
                    // );

                    // Update local state
                    const index = customers.value.findIndex(
                        (c) => c.customer_id === customerForm.value.customer_id
                    );
                    if (index !== -1) {
                        customers.value[index] = { ...customerForm.value };
                    }

                    // Update selected customer if being viewed
                    if (
                        selectedCustomer.value &&
                        selectedCustomer.value.customer_id ===
                            customerForm.value.customer_id
                    ) {
                        selectedCustomer.value = { ...customerForm.value };
                    }

                    alert("Customer updated successfully!");
                } else {
                    // Create new customer
                    const response = await axios.post(
                        "/customers",
                        customerForm.value
                    );
                    // console.log(
                    //     "Customer creation successful. Response:",
                    //     response.data
                    // );

                    // Add to local state
                    customers.value.push(response.data.data);

                    alert("Customer added successfully!");
                }

                // Close the modal
                closeCustomerModal();
            } catch (error) {
                console.error("Error saving customer:", {
                    message: error.message,
                    response: error.response?.data,
                    status: error.response?.status,
                });

                if (
                    error.response &&
                    error.response.data &&
                    error.response.data.errors
                ) {
                    // Display validation errors
                    const errors = error.response.data.errors;
                    const errorMessage = Object.values(errors)
                        .flat()
                        .join("\n");
                    alert(`Validation errors:\n${errorMessage}`);
                } else {
                    alert(
                        `An error occurred while saving the customer: ${error.message}`
                    );
                }
            }
        };

        const viewCustomerDetails = async (customer) => {
            // console.log('Opening customer details for:', customer.name);
            selectedCustomer.value = customer;
            activeTab.value = "orders";
            showDetailsModal.value = true;

            // Load customer orders
            await fetchCustomerOrders(customer.customer_id);
        };

        const closeDetailsModal = () => {
            showDetailsModal.value = false;
            selectedCustomer.value = null;

            // Reset tab data
            customerOrders.value = [];
            customerInvoices.value = [];
            customerQuotations.value = [];
            customerInteractions.value = [];
        };

        const confirmDelete = (customer) => {
            customerToDelete.value = customer;
            showDeleteModal.value = true;
        };

        const closeDeleteModal = () => {
            showDeleteModal.value = false;
        };

        const deleteCustomer = async () => {
            try {
                await axios.delete(
                    `/customers/${customerToDelete.value.customer_id}`
                );

                // Remove from local state
                customers.value = customers.value.filter(
                    (c) => c.customer_id !== customerToDelete.value.customer_id
                );

                // Close modal
                closeDeleteModal();

                // If viewing the deleted customer, close details modal
                if (
                    selectedCustomer.value &&
                    selectedCustomer.value.customer_id ===
                        customerToDelete.value.customer_id
                ) {
                    closeDetailsModal();
                }

                alert("Customer deleted successfully!");
            } catch (error) {
                console.error("Error deleting customer:", error);

                if (error.response && error.response.status === 422) {
                    alert(
                        "Cannot delete this customer as it has related records."
                    );
                } else {
                    alert(
                        "An error occurred while deleting the customer. Please try again."
                    );
                }
            }
        };

        // Tab-related methods
        const fetchCustomerOrders = async (customerId) => {
            // console.log('fetchCustomerOrders called for customer:', customerId);
            isLoadingOrders.value = true;
            try {
                const response = await axios.get(
                    `/customers/${customerId}/orders`
                );
                customerOrders.value = response.data.data;
                // console.log('Customer orders loaded:', customerOrders.value);
            } catch (error) {
                console.error("Error fetching customer orders:", error);
                // Use dummy data if API call fails
                customerOrders.value = [
                    {
                        so_id: 1,
                        so_number: "SO-2024-001",
                        so_date: "2024-03-15",
                        total_amount: 15000,
                        status: "Confirmed",
                    },
                    {
                        so_id: 2,
                        so_number: "SO-2024-002",
                        so_date: "2024-03-22",
                        total_amount: 8500,
                        status: "Delivered",
                    },
                ];
                // console.log('Using dummy orders:', customerOrders.value);
            } finally {
                isLoadingOrders.value = false;
            }
        };

        // FIXED: fetchCustomerInvoices with better debugging
        const fetchCustomerInvoices = async (customerId) => {
            // console.log('fetchCustomerInvoices called for customer:', customerId);

            isLoadingInvoices.value = true;
            try {
                const url = `/customers/${customerId}/invoices`;
                // console.log('Fetching from URL:', url);

                const response = await axios.get(url);
                // console.log('API Response:', response);
                // console.log('Response data:', response.data);

                if (response.data && response.data.data) {
                    customerInvoices.value = response.data.data;
                    // console.log('Customer invoices set to:', customerInvoices.value);
                } else if (response.data && Array.isArray(response.data)) {
                    customerInvoices.value = response.data;
                    // console.log('Customer invoices set to (direct array):', customerInvoices.value);
                } else {
                    console.warn('Unexpected response structure:', response.data);
                    customerInvoices.value = [];
                }
            } catch (error) {
                console.error('Error fetching customer invoices:', error);
                console.error('Error details:', {
                    message: error.message,
                    status: error.response?.status,
                    statusText: error.response?.statusText,
                    data: error.response?.data
                });

                // Use dummy data if API call fails
                customerInvoices.value = [
                    {
                        invoice_id: 1,
                        invoice_number: "INV-2024-001",
                        invoice_date: "2024-03-16",
                        due_date: "2024-04-15",
                        total_amount: 15000,
                        status: "Paid",
                    },
                    {
                        invoice_id: 2,
                        invoice_number: "INV-2024-002",
                        invoice_date: "2024-03-23",
                        due_date: "2024-04-22",
                        total_amount: 8500,
                        status: "Open",
                    },
                ];
                // console.log('Using dummy invoices:', customerInvoices.value);
            } finally {
                isLoadingInvoices.value = false;
                // console.log('fetchCustomerInvoices completed. Final invoices:', customerInvoices.value);
            }
        };

        const fetchCustomerQuotations = async (customerId) => {
            // console.log('fetchCustomerQuotations called for customer:', customerId);
            isLoadingQuotations.value = true;
            try {
                const response = await axios.get(
                    `/customers/${customerId}/quotations`
                );
                customerQuotations.value = response.data.data;
                // console.log('Customer quotations loaded:', customerQuotations.value);
            } catch (error) {
                console.error("Error fetching customer quotations:", error);
                // Use dummy data if API call fails
                customerQuotations.value = [
                    {
                        quotation_id: 1,
                        quotation_number: "QUO-2024-001",
                        quotation_date: "2024-03-10",
                        validity_date: "2024-04-10",
                        status: "Converted",
                    },
                    {
                        quotation_id: 2,
                        quotation_number: "QUO-2024-002",
                        quotation_date: "2024-03-20",
                        validity_date: "2024-04-20",
                        status: "Open",
                    },
                ];
                // console.log('Using dummy quotations:', customerQuotations.value);
            } finally {
                isLoadingQuotations.value = false;
            }
        };

        const fetchCustomerInteractions = async (customerId) => {
            // console.log('fetchCustomerInteractions called for customer:', customerId);
            isLoadingInteractions.value = true;
            try {
                const response = await axios.get(
                    `/interactions/customer/${customerId}`
                );
                customerInteractions.value = response.data.data;
                // console.log('Customer interactions loaded:', customerInteractions.value);
            } catch (error) {
                console.error("Error fetching customer interactions:", error);
                // Use dummy data if API call fails
                customerInteractions.value = [
                    {
                        interaction_id: 1,
                        interaction_date: "2024-03-15",
                        interaction_type: "Phone Call",
                        notes: "Discussed new product offerings and pricing options",
                        user: { name: "Sales Rep 1" },
                    },
                    {
                        interaction_id: 2,
                        interaction_date: "2024-03-22",
                        interaction_type: "Email",
                        notes: "Sent follow-up information about delivery options",
                        user: { name: "Sales Rep 2" },
                    },
                ];
                // console.log('Using dummy interactions:', customerInteractions.value);
            } finally {
                isLoadingInteractions.value = false;
            }
        };

        // Helper methods
        const formatDate = (dateString) => {
            if (!dateString) return "-";

            const date = new Date(dateString);
            return new Intl.DateTimeFormat("en-US", {
                year: "numeric",
                month: "short",
                day: "numeric",
            }).format(date);
        };

        const formatCurrency = (amount) => {
            if (amount === undefined || amount === null) return "-";

            return new Intl.NumberFormat("en-US", {
                style: "currency",
                currency: "USD",
            }).format(amount);
        };

        const getOrderStatusClass = (status) => {
            switch (status) {
                case "Pending":
                    return "pending";
                case "Confirmed":
                    return "confirmed";
                case "Delivering":
                    return "delivering";
                case "Delivered":
                    return "delivered";
                case "Invoiced":
                    return "invoiced";
                case "Closed":
                    return "closed";
                case "Cancelled":
                    return "cancelled";
                default:
                    return "";
            }
        };

        const getInvoiceStatusClass = (status) => {
            switch (status) {
                case "Open":
                    return "open";
                case "Paid":
                    return "paid";
                case "Overdue":
                    return "overdue";
                case "Closed":
                    return "closed";
                default:
                    return "";
            }
        };

        const getQuotationStatusClass = (status) => {
            switch (status) {
                case "Open":
                    return "open";
                case "Converted":
                    return "converted";
                case "Expired":
                    return "expired";
                case "Rejected":
                    return "rejected";
                default:
                    return "";
            }
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

        // Initialize
        onMounted(() => {
            // console.log('CustomersList mounted');
            fetchCustomers();
        });

        return {
            customers,
            isLoading,
            searchQuery,
            statusFilter,
            filteredCustomers,
            showCustomerModal,
            showDeleteModal,
            showDetailsModal,
            isEditMode,
            customerForm,
            isActive,
            customerToDelete,
            selectedCustomer,
            activeTab,
            customerOrders,
            customerInvoices,
            customerQuotations,
            customerInteractions,
            isLoadingOrders,
            isLoadingInvoices,
            isLoadingQuotations,
            isLoadingInteractions,
            setActiveTab,  // FIXED: Added this
            forceRefreshInvoices,  // Added for debugging
            applyFilters,
            clearSearch,
            openAddCustomerModal,
            editCustomer,
            closeCustomerModal,
            saveCustomer,
            viewCustomerDetails,
            closeDetailsModal,
            confirmDelete,
            closeDeleteModal,
            deleteCustomer,
            formatDate,
            formatCurrency,
            getOrderStatusClass,
            getInvoiceStatusClass,
            getQuotationStatusClass,
            viewOrder,
            viewInvoice,
            viewQuotation,
        };
    },
};
</script>

<style scoped>
.customers-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.page-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.search-box {
    position: relative;
    width: 100%;
    max-width: 400px;
}

.search-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
}

.search-box input {
    width: 100%;
    padding: 0.625rem 2.5rem 0.625rem 2.25rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.clear-search {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.25rem;
}

.filters select {
    padding: 0.625rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    background-color: white;
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

.customers-container {
    width: 100%;
}

.customers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.customer-card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    cursor: pointer;
    transition: box-shadow 0.3s;
}

.customer-card:hover {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.customer-header {
    padding: 0.75rem 1rem;
    background-color: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.customer-status {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    background-color: #d1fae5;
    color: #059669;
}

.customer-status.inactive {
    background-color: #f3f4f6;
    color: #6b7280;
}

.customer-actions {
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

.action-btn:hover {
    background-color: #f1f5f9;
    color: #0f172a;
}

.customer-content {
    padding: 1.5rem;
    display: flex;
    gap: 1rem;
    flex: 1;
}

.customer-icon {
    font-size: 1.5rem;
    color: #2563eb;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 3rem;
    height: 3rem;
    background-color: #eff6ff;
    border-radius: 50%;
}

.customer-info {
    flex: 1;
}

.customer-name {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
    color: #1e293b;
}

.customer-code {
    font-size: 0.875rem;
    color: #64748b;
    margin-bottom: 0.75rem;
}

.customer-email,
.customer-phone {
    font-size: 0.875rem;
    color: #334155;
    margin: 0.25rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.customer-footer {
    padding: 0.75rem 1rem;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.875rem;
}

.customer-contact {
    color: #64748b;
}

.no-data {
    font-style: italic;
    color: #94a3b8;
}

.view-details {
    color: #2563eb;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 0;
    text-align: center;
    color: #64748b;
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
    margin: 0;
    font-size: 0.875rem;
}

.loading-indicator {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 4rem 0;
    color: #64748b;
    font-size: 1rem;
}

.loading-indicator i {
    margin-right: 0.5rem;
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
    max-height: 90vh;
    display: flex;
    flex-direction: column;
}

.modal-lg {
    max-width: 800px;
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
    overflow-y: auto;
    flex: 1;
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

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.checkbox-group input[type="checkbox"] {
    width: auto;
}

.checkbox-group label {
    margin-bottom: 0;
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

/* Customer details styles */
.customer-details {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.details-section {
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    overflow: hidden;
}

.section-title {
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
    padding: 0.75rem 1rem;
    background-color: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    color: #1e293b;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    padding: 1rem;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.detail-item.full-width {
    grid-column: 1 / -1;
}

.detail-label {
    font-size: 0.75rem;
    color: #64748b;
    font-weight: 500;
}

.detail-value {
    font-size: 0.875rem;
    color: #334155;
}

.customer-status-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.customer-status-badge.active {
    background-color: #d1fae5;
    color: #059669;
}

.customer-status-badge.inactive {
    background-color: #f3f4f6;
    color: #6b7280;
}

/* Debug panel */
.debug-panel {
    font-size: 0.75rem;
    line-height: 1.4;
}

/* Tab styles */
.tabs {
    display: flex;
    border-bottom: 1px solid #e2e8f0;
    margin-bottom: 1rem;
}

.tab {
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #64748b;
    cursor: pointer;
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

.tab-content {
    min-height: 200px;
}

.empty-tab-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 0;
    color: #64748b;
}

.empty-tab-state i {
    font-size: 2rem;
    margin-bottom: 1rem;
    color: #cbd5e1;
}

/* Table styles */
.data-table-wrapper {
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
}

.data-table tr:hover td {
    background-color: #f8fafc;
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

/* Interaction styles */
.interaction-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.interaction-item {
    background-color: #f8fafc;
    border-radius: 0.5rem;
    padding: 1rem;
    border: 1px solid #e2e8f0;
}

.interaction-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.interaction-type {
    font-weight: 500;
    font-size: 0.875rem;
    color: #1e293b;
}

.interaction-date {
    font-size: 0.75rem;
    color: #64748b;
}

.interaction-notes {
    font-size: 0.875rem;
    color: #334155;
    margin-bottom: 0.5rem;
}

.interaction-footer {
    font-size: 0.75rem;
    color: #64748b;
    display: flex;
    justify-content: flex-end;
}

.modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #e2e8f0;
}

.footer-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
}

@media (max-width: 768px) {
    .page-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .customers-grid {
        grid-template-columns: 1fr;
    }

    .form-row {
        grid-template-columns: 1fr;
    }

    .detail-grid {
        grid-template-columns: 1fr;
    }

    .tabs {
        flex-wrap: wrap;
    }
}
</style>
