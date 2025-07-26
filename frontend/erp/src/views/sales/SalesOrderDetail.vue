<!-- src/views/sales/SalesOrderDetail.vue -->
<template>
    <div class="order-detail">
        <div class="page-header">
            <h1>Order Details</h1>
            <div class="page-actions">
                <button class="btn btn-secondary" @click="goBack">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <div class="btn-group" v-if="order">
                    <button
                        class="btn btn-primary"
                        @click="editOrder"
                        v-if="canEdit"
                    >
                        <i class="fas fa-edit"></i> Edit
                    </button>

                    <button
                        v-if="order.status === 'Draft'"
                        class="btn btn-info"
                        @click="confirmOrder"
                    >
                        <i class="fas fa-check-circle"></i> Confirm
                    </button>

                    <button
                        v-if="
                            order.status === 'Confirmed' ||
                            order.status === 'Processing'
                        "
                        class="btn btn-info"
                        @click="createDelivery"
                    >
                        <i class="fas fa-truck"></i> Create Delivery
                    </button>

                    <button
                        v-if="order.status === 'Delivered'"
                        class="btn btn-success"
                        @click="createInvoice"
                    >
                        <i class="fas fa-file-invoice-dollar"></i> Create Invoice
                    </button>

                    <button class="btn btn-secondary" @click="printOrder">
                        <i class="fas fa-print"></i> Preview Print
                    </button>
                </div>
            </div>
        </div>

        <div v-if="isLoading" class="loading-indicator">
            <i class="fas fa-spinner fa-spin"></i> Loading order data...
        </div>

        <div v-else-if="!order" class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h3>Order not found</h3>
            <p>The order you are looking for may have been deleted or does not exist.</p>
            <button class="btn btn-primary" @click="goBack">
                Back to order list
            </button>
        </div>

        <div v-else class="order-container">
            <!-- Order Header -->
            <div class="detail-card">
                <div class="card-header">
                    <h2>Order Information</h2>
                    <div
                        class="order-status"
                        :class="getStatusClass(order.status)"
                    >
                        {{ getStatusLabel(order.status) }}
                    </div>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-group">
                            <label>Order Number</label>
                            <div class="info-value">{{ order.soNumber }}</div>
                        </div>

                        <div class="info-group">
                            <label>Customer PO Number</label>
                            <div class="info-value">
                                {{ order.poNumberCustomer || '-' }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Order Date</label>
                            <div class="info-value">
                                {{ formatDate(order.soDate) }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Customer</label>
                            <div class="info-value">
                                {{ order.customer.name }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Quotation Reference</label>
                            <div class="info-value">
                                <template v-if="order.quotation_id">
                                    <a
                                        @click.prevent="viewQuotation"
                                        href="#"
                                        class="link"
                                    >
                                        {{
                                            order.salesQuotation
                                                ?.quotation_number ||
                                            order.quotation_id
                                        }}
                                    </a>
                                </template>
                                <template v-else>-</template>
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Expected Delivery</label>
                            <div class="info-value">
                                {{ formatDate(order.expectedDelivery) || "-" }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Payment Terms</label>
                            <div class="info-value">
                                {{ order.paymentTerms || "-" }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Delivery Terms</label>
                            <div class="info-value">
                                {{ order.deliveryTerms || "-" }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="detail-card">
                <div class="card-header">
                    <h2>Customer Information</h2>
                </div>
                <div class="card-body">
                    <div class="customer-info">
                        <div class="info-group">
                            <label>Customer Name</label>
                            <div class="info-value">
                                {{ order.customer.name }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Customer Code</label>
                            <div class="info-value">
                                {{ order.customer.customerCode }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Address</label>
                            <div class="info-value">
                                {{ order.customer.address || "-" }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Tax ID</label>
                            <div class="info-value">
                                {{ order.customer.taxId || "-" }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Contact Person</label>
                            <div class="info-value">
                                {{ order.customer.contactPerson || "-" }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Phone</label>
                            <div class="info-value">
                                {{ order.customer.phone || "-" }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Email</label>
                            <div class="info-value">
                                {{ order.customer.email || "-" }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items with Outstanding Quantities -->
            <div class="detail-card">
                <div class="card-header">
                    <h2>Order Items and Delivery Status</h2>
                </div>
                <div class="card-body">
                    <div class="order-items">
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Unit Price</th>
                                    <th>Order Quantity</th>
                                    <th>Delivered Quantity</th>
                                    <th>Outstanding</th>
                                    <th>UOM</th>
                                    <th>Status</th>
                                    <th>Subtotal</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="line in order.salesOrderLines"
                                    :key="line.lineId"
                                    :class="{'has-outstanding': getOutstandingQuantity(line) > 0}"
                                >
                                    <td>
                                        <div class="item-info">
                                            <div class="item-code">
                                                {{ line.item.itemCode }}
                                            </div>
                                            <div class="item-name">
                                                <router-link
                                                    v-if="line.item && line.item.itemId"
                                                    :to="`/items/${line.item.itemId}`"
                                                    class="item-link"
                                                    :title="`View details for ${line.item.name}`"
                                                >
                                                    {{ line.item.name }}
                                                </router-link>
                                                <span v-else class="text-muted">Unknown Item</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="right">
                                        {{ formatCurrency(line.unitPrice) }}
                                        <div style="font-size: 0.75rem; color: #6b7280;">
                                            Base: {{ formatCurrency(line.baseCurrencyUnitPrice, order.baseCurrency) }}
                                        </div>
                                    </td>
                                    <td class="right">{{ safeParseFloat(line.quantity) }}</td>
                                    <td class="right">{{ getDeliveredQuantity(line) }}</td>
                                    <td class="right outstanding-qty">
                                        <span :class="{'text-danger': getOutstandingQuantity(line) > 0, 'text-success': getOutstandingQuantity(line) <= 0}">
                                            {{ getOutstandingQuantity(line) }}
                                        </span>
                                    </td>
                                    <td class="center">
                                        {{ getUomSymbol(line.uomId) }}
                                    </td>
                                    <td class="center">
                                        <span class="delivery-status" :class="getDeliveryStatusClass(line)">
                                            {{ getDeliveryStatusLabel(line) }}
                                        </span>
                                    </td>
                                    <td class="right">
                                        {{ formatCurrency(line.subtotal) }}
                                        <div style="font-size: 0.75rem; color: #6b7280;">
                                            Base: {{ formatCurrency(line.baseCurrencySubtotal, order.baseCurrency) }}
                                        </div>
                                    </td>
                                    <td class="right">
                                        {{ formatCurrency(line.total) }}
                                        <div style="font-size: 0.75rem; color: #6b7280;">
                                            Base: {{ formatCurrency(line.baseCurrencyTotal, order.baseCurrency) }}
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="7" class="totals-label">
                                        Subtotal
                                    </td>
                                    <td colspan="2" class="totals-value">
                                        {{ formatCurrency(calculateSubtotal()) }}
                                        <div style="font-size: 0.75rem; color: #6b7280;">
                                            Base: {{ formatCurrency(calculateBaseSubtotal(), order.baseCurrency) }}
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="7" class="totals-label">
                                        Total Discount
                                    </td>
                                    <td colspan="2" class="totals-value">
                                        {{ formatCurrency(calculateTotalDiscount()) }}
                                        <div style="font-size: 0.75rem; color: #6b7280;">
                                            Base: {{ formatCurrency(calculateBaseTotalDiscount(), order.baseCurrency) }}
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="7" class="totals-label">
                                        Total Tax
                                    </td>
                                    <td colspan="2" class="totals-value">
                                        {{ formatCurrency(calculateTotalTax()) }}
                                        <div style="font-size: 0.75rem; color: #6b7280;">
                                            Base: {{ formatCurrency(calculateBaseTotalTax(), order.baseCurrency) }}
                                        </div>
                                    </td>
                                </tr>
                                <tr class="grand-total">
                                    <td colspan="7" class="totals-label">
                                        Total
                                    </td>
                                    <td colspan="2" class="totals-value">
                                        {{ formatCurrency(order.totalAmount) }}
                                        <div style="font-size: 0.75rem; color: #6b7280;">
                                            Base: {{ formatCurrency(order.baseCurrencyTotal, order.baseCurrency) }}
                                        </div>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Order Fulfillment Summary -->
            <div class="detail-card" v-if="showFulfillmentSummary">
                <div class="card-header">
                    <h2>Delivery Summary</h2>
                </div>
                <div class="card-body">
                    <div class="fulfillment-summary">
                        <div class="summary-card">
                            <div class="summary-title">Total Order Items</div>
                            <div class="summary-value">{{ getTotalOrderedItems() }}</div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-title">Total Delivered Items</div>
                            <div class="summary-value">{{ getTotalDeliveredQuantity() }}</div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-title">Total Outstanding</div>
                            <div class="summary-value" :class="{'text-danger': getTotalOutstandingQuantity() > 0}">
                                {{ getTotalOutstandingQuantity() }}
                            </div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-title">Delivery Percentage</div>
                            <div class="summary-value">{{ getDeliveryPercentage() }}%</div>
                            <div class="progress-bar">
                                <div class="progress" :style="{ width: getDeliveryPercentage() + '%' }"></div>
                            </div>
                        </div>
                    </div>
                    <div class="delivery-actions" v-if="getTotalOutstandingQuantity() > 0 && (order.status === 'Confirmed' || order.status === 'Processing')">
                        <button class="btn btn-primary" @click="createDelivery">
                            <i class="fas fa-truck"></i> Process Delivery for Outstanding Items
                        </button>
                    </div>
                </div>
            </div>

            <!-- Related Deliveries (if any) -->
            <div v-if="hasDeliveries" class="detail-card">
                <div class="card-header">
                    <h2>Related Deliveries</h2>
                </div>
                <div class="card-body">
                    <div class="related-items">
                        <table class="related-table">
                            <thead>
                                <tr>
                                    <th>Delivery No.</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Shipping</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="delivery in order.deliveries"
                                    :key="delivery.deliveryId"
                                >
                                    <td>{{ delivery.deliveryNumber }}</td>
                                    <td>
                                        {{ formatDate(delivery.deliveryDate) }}
                                    </td>
                                    <td>
                                        <span
                                            class="status-badge"
                                            :class="
                                                'status-' +
                                                delivery.status.toLowerCase()
                                            "
                                        >
                                            {{ delivery.status }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ delivery.shippingMethod || "-" }}
                                    </td>
                                    <td>
                                        <button
                                            class="btn btn-sm btn-secondary"
                                            @click="viewDelivery(delivery)"
                                        >
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Related Invoices (if any) -->
            <div v-if="hasInvoices" class="detail-card">
                <div class="card-header">
                    <h2>Related Invoices</h2>
                </div>
                <div class="card-body">
                    <div class="related-items">
                        <table class="related-table">
                            <thead>
                                <tr>
                                    <th>Invoice No.</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Amount</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="invoice in order.salesInvoices"
                                    :key="invoice.invoiceId"
                                >
                                    <td>{{ invoice.invoiceNumber }}</td>
                                    <td>
                                        {{ formatDate(invoice.invoiceDate) }}
                                    </td>
                                    <td>
                                        <span
                                            class="status-badge"
                                            :class="
                                                'status-' +
                                                invoice.status.toLowerCase()
                                            "
                                        >
                                            {{ invoice.status }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ formatCurrency(invoice.totalAmount) }}
                                    </td>
                                    <td>
                                        <button
                                            class="btn btn-sm btn-secondary"
                                            @click="viewInvoice(invoice)"
                                        >
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <ConfirmationModal
            v-if="showConfirmModal"
            title="Confirm Order"
            message="Are you sure you want to confirm this order? Status will change to 'Confirmed'."
            confirm-button-text="Confirm"
            confirm-button-class="btn btn-primary"
            @confirm="confirmOrderAction"
            @close="closeConfirmModal"
        />
    </div>
</template>

<script>
import { ref, computed, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import axios from "axios";
import ConfirmationModal from "@/components/common/ConfirmationModal.vue";

export default {
    name: "SalesOrderDetail",
    components: {
        ConfirmationModal
    },
    setup() {
        const router = useRouter();
        const route = useRoute();

        // Helper function for safe number parsing
        const safeParseFloat = (value) => {
            if (value === null || value === undefined || value === '') return 0;
            const parsed = parseFloat(value);
            return isNaN(parsed) ? 0 : parsed;
        };

        // Data
        const order = ref(null);
        const unitOfMeasures = ref([]);
        const isLoading = ref(true);
        const showConfirmModal = ref(false);

        // Calculate base currency subtotal of all lines
        const calculateBaseSubtotal = () => {
            if (!order.value || !order.value.salesOrderLines) return 0;

            return order.value.salesOrderLines.reduce((sum, line) => {
                return sum + (parseFloat(line.baseCurrencySubtotal) || 0);
            }, 0);
        };

        // Calculate base currency total discount of all lines
        const calculateBaseTotalDiscount = () => {
            if (!order.value || !order.value.salesOrderLines) return 0;

            return order.value.salesOrderLines.reduce((sum, line) => {
                return sum + (parseFloat(line.baseCurrencyDiscount) || 0);
            }, 0);
        };

        // Calculate base currency total tax of all lines
        const calculateBaseTotalTax = () => {
            if (!order.value || !order.value.salesOrderLines) return 0;

            return order.value.salesOrderLines.reduce((sum, line) => {
                return sum + (parseFloat(line.baseCurrencyTax) || 0);
            }, 0);
        };
        // Computed properties
        const canEdit = computed(() => {
            if (!order.value) return false;
            return !["Delivered", "Invoiced", "Closed"].includes(
                order.value.status
            );
        });

        const hasDeliveries = computed(() => {
            if (!order.value || !order.value.deliveries) return false;
            return order.value.deliveries.length > 0;
        });

        const hasInvoices = computed(() => {
            if (!order.value || !order.value.salesInvoices) return false;
            return order.value.salesInvoices.length > 0;
        });

        const showFulfillmentSummary = computed(() => {
            return order.value && order.value.salesOrderLines && order.value.salesOrderLines.length > 0;
        });

        // FIXED: Load order and reference data with enhanced logging
        const loadData = async () => {
            isLoading.value = true;

            // Helper function to convert snake_case keys to camelCase recursively
            const toCamelCase = (obj) => {
                if (Array.isArray(obj)) {
                    return obj.map(v => toCamelCase(v));
                } else if (obj !== null && obj.constructor === Object) {
                    return Object.keys(obj).reduce((result, key) => {
                        const camelKey = key.replace(/_([a-z])/g, g => g[1].toUpperCase());
                        result[camelKey] = toCamelCase(obj[key]);
                        return result;
                    }, {});
                }
                return obj;
            };

            try {
                console.log('🔄 Loading order data...');

                // Load unit of measures for reference
                const uomResponse = await axios.get("/unit-of-measures");
                unitOfMeasures.value = uomResponse.data.data || [];
                console.log('✅ UOMs loaded:', unitOfMeasures.value.length);

                // Load order details
                const orderResponse = await axios.get(`orders/${route.params.id}`);
                order.value = toCamelCase(orderResponse.data.data);

                console.log('✅ Order loaded:', order.value.soNumber);
                console.log('💰 Currency code:', order.value.currencyCode);
                console.log('📊 Order lines:', order.value.salesOrderLines?.length || 0);

                // Debug: log sample line data
                if (order.value.salesOrderLines && order.value.salesOrderLines.length > 0) {
                    const sampleLine = order.value.salesOrderLines[0];
                    console.log('📦 Sample line data:', {
                        itemCode: sampleLine.item?.itemCode,
                        unitPrice: sampleLine.unitPrice,
                        quantity: sampleLine.quantity,
                        subtotal: sampleLine.subtotal,
                        discount: sampleLine.discount,
                        tax: sampleLine.tax,
                        total: sampleLine.total
                    });
                }

            } catch (error) {
                console.error("❌ Error loading order:", error);
                order.value = null;
            } finally {
                isLoading.value = false;
            }
        };

        // Format date
        const formatDate = (dateString) => {
            if (!dateString) return "-";
            const date = new Date(dateString);
            return date.toLocaleDateString("en-US", {
                day: "2-digit",
                month: "long",
                year: "numeric",
            });
        };

        // FIXED: Format currency with proper error handling and optional currency code
        const formatCurrency = (value, currencyCode = null) => {
            const safeValue = safeParseFloat(value);

            // Use provided currencyCode or fallback to order currency or USD
            const currCode = currencyCode || order.value?.currencyCode || "USD";

            try {
                return new Intl.NumberFormat("en-US", {
                    style: "currency",
                    currency: currCode,
                    minimumFractionDigits: 4,
                    maximumFractionDigits: 4,
                }).format(safeValue);
            } catch (error) {
                console.error('Currency formatting error:', error);
                return `${currCode} ${safeValue.toFixed(2)}`;
            }
        };

        // Get UOM symbol
        const getUomSymbol = (uomId) => {
            const uom = unitOfMeasures.value.find((u) => u.uom_id === uomId);
            return uom ? uom.symbol : "-";
        };

        // Get status label
        const getStatusLabel = (status) => {
            switch (status) {
                case "Draft":
                    return "Draft";
                case "Confirmed":
                    return "Confirmed";
                case "Processing":
                    return "Processing";
                case "Shipped":
                    return "Shipped";
                case "Delivered":
                    return "Delivered";
                case "Invoiced":
                    return "Invoiced";
                case "Closed":
                    return "Completed";
                default:
                    return status;
            }
        };

        // Get status class
        const getStatusClass = (status) => {
            switch (status) {
                case "Draft":
                    return "status-draft";
                case "Confirmed":
                    return "status-confirmed";
                case "Processing":
                    return "status-processing";
                case "Shipped":
                    return "status-shipped";
                case "Delivered":
                    return "status-delivered";
                case "Invoiced":
                    return "status-invoiced";
                case "Closed":
                    return "status-closed";
                default:
                    return "";
            }
        };

        // FIXED: Calculate delivered quantity for a line with safe parsing
        const getDeliveredQuantity = (line) => {
            if (!line.deliveryLines || line.deliveryLines.length === 0) {
                return 0;
            }

            // Sum up quantities from completed deliveries
            return line.deliveryLines.reduce((total, deliveryLine) => {
                // Check if the delivery is completed
                const delivery = order.value.deliveries?.find(d =>
                    d.deliveryId === deliveryLine.deliveryId);
                if (delivery && ['Completed', 'Delivered', 'Invoiced'].includes(delivery.status)) {
                    return total + safeParseFloat(deliveryLine.deliveredQuantity);
                }
                return total;
            }, 0);
        };

        // FIXED: Calculate outstanding quantity for a line with safe parsing
        const getOutstandingQuantity = (line) => {
            const ordered = safeParseFloat(line.quantity);
            const delivered = getDeliveredQuantity(line);
            return Math.max(0, ordered - delivered);
        };

        // Get delivery status for a line
        const getDeliveryStatusLabel = (line) => {
            const outstanding = getOutstandingQuantity(line);
            const ordered = safeParseFloat(line.quantity);

            if (outstanding <= 0) {
                return "Fully Delivered";
            } else if (outstanding < ordered) {
                return "Partially Delivered";
            } else {
                return "Not Delivered";
            }
        };

        // Get delivery status class
        const getDeliveryStatusClass = (line) => {
            const status = getDeliveryStatusLabel(line);

            switch (status) {
                case "Fully Delivered":
                    return "status-delivered";
                case "Partially Delivered":
                    return "status-partial";
                case "Not Delivered":
                    return "status-pending";
                default:
                    return "";
            }
        };

        // Get total ordered quantity across all items
        const getTotalOrderedItems = () => {
            if (!order.value || !order.value.salesOrderLines) return 0;
            return order.value.salesOrderLines.length;
        };

        // FIXED: Get total ordered quantity across all lines with safe parsing
        const getTotalOrderedQuantity = () => {
            if (!order.value || !order.value.salesOrderLines) return 0;
            return order.value.salesOrderLines.reduce((total, line) => {
                return total + safeParseFloat(line.quantity);
            }, 0);
        };

        // FIXED: Get total delivered quantity across all lines with safe parsing
        const getTotalDeliveredQuantity = () => {
            if (!order.value || !order.value.salesOrderLines) return 0;
            return order.value.salesOrderLines.reduce((total, line) => {
                return total + getDeliveredQuantity(line);
            }, 0);
        };

        // FIXED: Get total outstanding quantity across all lines with safe parsing
        const getTotalOutstandingQuantity = () => {
            if (!order.value || !order.value.salesOrderLines) return 0;
            return order.value.salesOrderLines.reduce((total, line) => {
                return total + getOutstandingQuantity(line);
            }, 0);
        };

        // FIXED: Calculate delivery percentage with safe parsing
        const getDeliveryPercentage = () => {
            const total = getTotalOrderedQuantity();
            if (total === 0) return 0;

            const delivered = getTotalDeliveredQuantity();
            return Math.round((delivered / total) * 100);
        };

        // FIXED: Calculate subtotal of all lines with safe parsing and logging
        const calculateSubtotal = () => {
            if (!order.value || !order.value.salesOrderLines) return 0;

            const subtotal = order.value.salesOrderLines.reduce((sum, line) => {
                const lineSubtotal = safeParseFloat(line.subtotal);
                console.log('Line subtotal:', line.item?.itemCode, lineSubtotal);
                return sum + lineSubtotal;
            }, 0);

            console.log('Total calculated subtotal:', subtotal);
            return subtotal;
        };

        // FIXED: Calculate total discount of all lines with safe parsing and logging
        const calculateTotalDiscount = () => {
            if (!order.value || !order.value.salesOrderLines) return 0;

            const totalDiscount = order.value.salesOrderLines.reduce((sum, line) => {
                const lineDiscount = safeParseFloat(line.discount);
                console.log('Line discount:', line.item?.itemCode, lineDiscount);
                return sum + lineDiscount;
            }, 0);

            console.log('Total calculated discount:', totalDiscount);
            return totalDiscount;
        };

        // FIXED: Calculate total tax of all lines with safe parsing and logging
        const calculateTotalTax = () => {
            if (!order.value || !order.value.salesOrderLines) return 0;

            const totalTax = order.value.salesOrderLines.reduce((sum, line) => {
                const lineTax = safeParseFloat(line.tax);
                console.log('Line tax:', line.item?.itemCode, lineTax);
                return sum + lineTax;
            }, 0);

            console.log('Total calculated tax:', totalTax);
            return totalTax;
        };

        // Navigation methods
        const goBack = () => {
            router.push("/sales/orders");
        };

        const editOrder = () => {
            router.push(`/sales/orders/${order.value.soId}/edit`);
        };

        const viewQuotation = () => {
            if (order.value && order.value.quotationId) {
                router.push(`/sales/quotations/${order.value.quotationId}`);
            }
        };

        const viewDelivery = (delivery) => {
            if (delivery && delivery.deliveryId !== undefined && delivery.deliveryId !== null) {
                router.push(`/sales/deliveries/${delivery.deliveryId}`);
            } else {
                alert("Invalid delivery ID. Cannot open delivery details.");
            }
        };

        const viewInvoice = (invoice) => {
            router.push(`/sales/invoices/${invoice.invoiceId}`);
        };

        // Print order
        const printOrder = () => {
            const printUrl = `/sales/orders/${order.value.soId}/print`;
            router.push(printUrl);
        };

        // Confirm order
        const confirmOrder = () => {
            showConfirmModal.value = true;
        };

        const closeConfirmModal = () => {
            showConfirmModal.value = false;
        };

        const confirmOrderAction = async () => {
            try {
                // Mapping data ke format backend (snake_case dan nama field sesuai)
                const payload = {
                    so_number: order.value.soNumber,
                    po_number_customer: order.value.poNumberCustomer || null,
                    so_date: order.value.soDate,
                    customer_id: order.value.customer.customerId,
                    quotation_id: order.value.quotationId || null,
                    payment_terms: order.value.paymentTerms || null,
                    delivery_terms: order.value.deliveryTerms || null,
                    expected_delivery: order.value.expectedDelivery || null,
                    status: "Confirmed",
                    lines: order.value.salesOrderLines.map(line => ({
                        line_id: line.lineId || null,
                        item_id: line.item.itemId,
                        unit_price: safeParseFloat(line.unitPrice),
                        quantity: safeParseFloat(line.quantity),
                        uom_id: line.uomId,
                        discount: safeParseFloat(line.discount),
                        tax: safeParseFloat(line.tax)
                    }))
                };

                await axios.put(`orders/${order.value.soId}`, payload);

                // Reload the order to get the updated data
                loadData();

                closeConfirmModal();
                alert("Order successfully confirmed");
            } catch (error) {
                console.error("Error confirming order:", error);
                alert("An error occurred while confirming the order");
            }
        };

        // Create a delivery
        const createDelivery = () => {
            router.push(
                `/sales/deliveries/create?order_id=${order.value.soId}`
            );
        };

        // Create an invoice
        const createInvoice = () => {
            router.push(`/sales/invoices/create?order_id=${order.value.soId}`);
        };

        onMounted(() => {
            loadData();
        });

        return {
            order,
            isLoading,
            canEdit,
            hasDeliveries,
            hasInvoices,
            showConfirmModal,
            showFulfillmentSummary,
            safeParseFloat,
            formatDate,
            formatCurrency,
            getUomSymbol,
            getStatusLabel,
            getStatusClass,
            calculateSubtotal,
            calculateTotalDiscount,
            calculateTotalTax,
            calculateBaseSubtotal,
            calculateBaseTotalDiscount,
            calculateBaseTotalTax,
            getDeliveredQuantity,
            getOutstandingQuantity,
            getDeliveryStatusLabel,
            getDeliveryStatusClass,
            getTotalOrderedItems,
            getTotalOrderedQuantity,
            getTotalDeliveredQuantity,
            getTotalOutstandingQuantity,
            getDeliveryPercentage,
            goBack,
            editOrder,
            viewQuotation,
            viewDelivery,
            viewInvoice,
            printOrder,
            confirmOrder,
            closeConfirmModal,
            confirmOrderAction,
            createDelivery,
            createInvoice
        };
        

    },
};
</script>

<style scoped>
.order-detail {
    padding: 1rem 0;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.page-header h1 {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
    color: #1e293b;
}

.page-actions {
    display: flex;
    gap: 0.75rem;
}

.btn-group {
    display: flex;
    gap: 0.5rem;
}

.order-container {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.detail-card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.card-header {
    background-color: #f8fafc;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h2 {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0;
    color: #1e293b;
}

.card-body {
    padding: 1.5rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

.customer-info {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

.info-group {
    margin-bottom: 0.75rem;
}

.info-group label {
    display: block;
    font-size: 0.75rem;
    color: #64748b;
    margin-bottom: 0.25rem;
}

.info-value {
    font-size: 0.875rem;
    color: #1e293b;
    font-weight: 500;
}

.order-status {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

.status-draft {
    background-color: #e2e8f0;
    color: #475569;
}

.status-confirmed {
    background-color: #dbeafe;
    color: #2563eb;
}

.status-processing {
    background-color: #ede9fe;
    color: #7c3aed;
}

.status-shipped {
    background-color: #fef3c7;
    color: #d97706;
}

.status-delivered {
    background-color: #d1fae5;
    color: #059669;
}

.status-invoiced {
    background-color: #e0f2fe;
    color: #0284c7;
}

.status-closed {
    background-color: #cbd5e1;
    color: #1e293b;
}

.status-partial {
    background-color: #fef3c7;
    color: #d97706;
}

.status-pending {
    background-color: #fee2e2;
    color: #dc2626;
}

.items-table,
.related-table {
    width: 100%;
    border-collapse: collapse;
}

.items-table th,
.related-table th {
    text-align: left;
    padding: 0.75rem;
    background-color: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    font-weight: 500;
    color: #64748b;
    font-size: 0.75rem;
}

.items-table td,
.related-table td {
    padding: 0.75rem;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.875rem;
}

.center {
    text-align: center;
}

.right {
    text-align: right;
}

.item-info {
    display: flex;
    flex-direction: column;
}

.item-code {
    font-size: 0.75rem;
    color: #64748b;
}

.item-name {
    font-weight: 500;
}

.totals-label {
    text-align: right;
    font-weight: 500;
    color: #475569;
}

.totals-value {
    text-align: right;
    font-weight: 500;
}

.grand-total td {
    font-weight: 600;
    color: #1e293b;
    font-size: 1rem;
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
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

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
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

.btn-info {
    background-color: #0ea5e9;
    color: white;
}

.btn-info:hover {
    background-color: #0284c7;
}

.btn-success {
    background-color: #059669;
    color: white;
}

.btn-success:hover {
    background-color: #047857;
}

.link {
    color: #2563eb;
    cursor: pointer;
}

.link:hover {
    text-decoration: underline;
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
    margin: 0 0 1.5rem 0;
    font-size: 0.875rem;
}

/* New styles for outstanding item tracking */
.has-outstanding {
    background-color: #fff7ed;
}

.outstanding-qty {
    font-weight: 600;
}

.text-danger {
    color: #dc2626;
}

.text-success {
    color: #059669;
}

.text-muted {
    color: #64748b;
}

.delivery-status {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.fulfillment-summary {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.summary-card {
    background-color: #f8fafc;
    border-radius: 0.375rem;
    padding: 1.25rem;
    border: 1px solid #e2e8f0;
    text-align: center;
}

.summary-title {
    font-size: 0.75rem;
    color: #64748b;
    margin-bottom: 0.5rem;
}

.summary-value {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.5rem;
}

.progress-bar {
    width: 100%;
    height: 0.5rem;
    background-color: #e2e8f0;
    border-radius: 0.25rem;
    overflow: hidden;
}

.progress {
    height: 100%;
    background-color: #059669;
    border-radius: 0.25rem;
}

.delivery-actions {
    text-align: center;
    margin-top: 1.5rem;
}

.item-link {
    color: #2563eb;
    text-decoration: none;
}

.item-link:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .page-actions {
        width: 100%;
        justify-content: space-between;
    }

    .btn-group {
        flex-wrap: wrap;
    }

    .info-grid,
    .customer-info {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }

    .items-table,
    .related-table {
        display: block;
        overflow-x: auto;
    }

    .fulfillment-summary {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
}
</style>
