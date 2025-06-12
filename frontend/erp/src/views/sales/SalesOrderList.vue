<!-- src/views/sales/SalesOrderList.vue -->
<template>
    <div class="sales-orders">
        <!-- Search and Filter Section -->
        <div class="page-actions">
            <SearchFilter
                v-model:value="searchQuery"
                placeholder="Search orders (SO number, PO number, customer)..."
                @search="handleSearch"
                @clear="clearSearch"
            >
                <template #filters>
                    <div class="filter-group">
                        <!-- <label>Status</label> -->
                        <select v-model="statusFilter" @change="applyFilters">
                            <option value="">All Statuses</option>
                            <option value="Draft">Draft</option>
                            <option value="Confirmed">Confirmed</option>
                            <option value="Processing">Processed</option>
                            <option value="Shipped">Shipped</option>
                            <option value="Delivered">Delivered</option>
                            <option value="Invoiced">Invoiced</option>
                            <option value="Closed">Closed</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <!-- <label>Periode</label> -->
                        <select
                            v-model="dateRangeFilter"
                            @change="applyFilters"
                        >
                            <option value="all">All Time</option>
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>
                </template>

                <template #actions>
                    <button class="btn btn-outline-primary" @click="createPocapture">
                        <i class="fa-solid fa-brain"></i> PO Capture By AI
                    </button>
                    <button class="btn btn-primary" @click="createOrder">
                        <i class="fas fa-plus"></i> Create Order
                    </button>
                    <!-- <button
                        @click="exportToExcel"
                        :disabled="isExporting"
                        class="btn btn-outline-success"
                        title="Export to Excel"
                    >
                        <i class="fas fa-file-excel"></i>
                        {{ isExporting ? 'Exporting...' : 'Export' }}
                    </button> -->

                    <router-link
                        to="/sales/orders/excel/import"
                        class="btn btn-outline-primary"
                        title="Import from Excel"
                    >
                        <i class="fas fa-file-import"></i>
                        Import Excel
                    </router-link>
                </template>
            </SearchFilter>

            <!-- Custom Date Range (when Custom is selected) -->
            <div v-if="dateRangeFilter === 'custom'" class="custom-date-range">
                <div class="date-range-inputs">
                    <div class="filter-group">
                        <label for="startDate">Start Date</label>
                        <input
                            type="date"
                            id="startDate"
                            v-model="customDateRange.startDate"
                            @change="applyFilters"
                        />
                    </div>

                    <div class="filter-group">
                        <label for="endDate">End Date</label>
                        <input
                            type="date"
                            id="endDate"
                            v-model="customDateRange.endDate"
                            @change="applyFilters"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- DataTable Component -->
        <DataTable
            :columns="columns"
            :items="orders"
            :is-loading="isLoading"
            keyField="so_id"
            emptyIcon="fas fa-file-invoice"
            emptyTitle="No orders"
            emptyMessage="No orders available to display."
            @sort="handleSort"
        >
            <!-- Custom cell templates -->
            <template #so_number="{ value }">
                <div class="order-number">{{ value }}</div>
            </template>

            <template #po_number_customer="{ value }">
                <div class="po-number">{{ value || '-' }}</div>
            </template>

            <template #customer="{ item }">
                <div class="customer-info">
                    <div class="customer-name">
                        {{ item.customer?.name || "-" }}
                    </div>
                </div>
            </template>

            <template #total="{ value }">
                <div class="order-total">{{ formatCurrency(value) }}</div>
            </template>

            <template #status="{ value }">
                <span class="status-badge" :class="getStatusClass(value)">
                    {{ getStatusLabel(value) }}
                </span>
            </template>

            <template #date="{ value }">
                {{ formatDate(value) }}
            </template>

            <template #actions="{ item }">
                <div class="actions-cell">
                    <button
                        class="action-btn view"
                        title="Lihat Detail"
                        @click="viewOrder(item)"
                    >
                        <i class="fas fa-eye"></i>
                    </button>
                    <button
                        v-if="canEditOrder(item)"
                        class="action-btn edit"
                        title="Edit Order"
                        @click="editOrder(item)"
                    >
                        <i class="fas fa-edit"></i>
                    </button>

                    <button
                        v-if="canDeleteOrder(item)"
                        class="action-btn delete"
                        title="Hapus Order"
                        @click="confirmDelete(item)"
                    >
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </template>
        </DataTable>

        <!-- Pagination -->
        <PaginationComponent
            v-if="totalItems > 0"
            :current-page="currentPage"
            :total-pages="totalPages"
            :from="(currentPage - 1) * itemsPerPage + 1"
            :to="Math.min(currentPage * itemsPerPage, totalItems)"
            :total="totalItems"
            @page-changed="goToPage"
        />

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal
            v-if="showDeleteModal"
            title="Confirm Delete"
            :message="`Are you sure you want to delete order <strong>${orderToDelete.so_number}</strong>?<br>This action cannot be undone.`"
            confirm-button-text="Delete"
            confirm-button-class="btn btn-danger"
            @confirm="deleteOrder"
            @close="closeDeleteModal"
        />
    </div>
</template>

<script>
import { ref, onMounted, reactive } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";

import DataTable from "@/components/common/DataTable.vue";
import SearchFilter from "@/components/common/SearchFilter.vue";
import PaginationComponent from "@/components/common/Pagination.vue";
import ConfirmationModal from "@/components/common/ConfirmationModal.vue";

export default {
    name: "SalesOrderList",
    components: {
        DataTable,
        SearchFilter,
        PaginationComponent,
        ConfirmationModal,
    },
    setup() {
        const router = useRouter();
        const orders = ref([]);
        const isLoading = ref(true);

        // Pagination
        const currentPage = ref(1);
        const itemsPerPage = ref(10);
        const totalItems = ref(0);
        const totalPages = ref(1);

        // Filters
        const searchQuery = ref("");
        const statusFilter = ref("");
        const dateRangeFilter = ref("all");
        const customDateRange = reactive({
            startDate: new Date().toISOString().substr(0, 10),
            endDate: new Date().toISOString().substr(0, 10),
        });

        // Sort
        const sortKey = ref("so_id");
        const sortOrder = ref("desc");

        // Delete modal
        const showDeleteModal = ref(false);
        const orderToDelete = ref({});

        // Table columns - Add po_number_customer column
        const columns = [
            {
                key: "so_number",
                label: "SO Number",
                sortable: true,
                template: "so_number",
            },
            {
                key: "po_number_customer",
                label: "Customer PO",
                sortable: true,
                template: "po_number_customer",
            },
            {
                key: "customer",
                label: "Customer",
                sortable: false,
                template: "customer",
            },
            {
                key: "so_date",
                label: "Order Date",
                sortable: true,
                template: "date",
            },
            {
                key: "expected_delivery",
                label: "Delivery Date",
                sortable: true,
                template: "date",
            },
            {
                key: "status",
                label: "Status",
                sortable: true,
                template: "status",
            },
            {
                key: "total_amount",
                label: "Total",
                sortable: true,
                template: "total",
            },
        ];

        // Fetch orders
        const fetchOrders = async () => {
            isLoading.value = true;

            try {
                // Prepare query parameters
                const params = {
                    page: currentPage.value,
                    search: searchQuery.value,
                    sort_field: sortKey.value,
                    sort_direction: sortOrder.value,
                };

                if (statusFilter.value !== "") {
                    params.status = statusFilter.value;
                }

                // Add date range filters if applicable
                if (dateRangeFilter.value === "custom") {
                    params.start_date = customDateRange.startDate;
                    params.end_date = customDateRange.endDate;
                } else if (dateRangeFilter.value !== "all") {
                    params.date_range = dateRangeFilter.value;
                }

                const response = await axios.get("/orders", {
                    params,
                });

                orders.value = response.data.data;
                totalItems.value =
                    response.data.meta?.total || orders.value.length;
                totalPages.value =
                    response.data.meta?.last_page ||
                    Math.ceil(totalItems.value / itemsPerPage.value);
            } catch (error) {
                console.error("Error fetching sales orders:", error);
                alert("An error occurred while loading data. Please try again.");
            } finally {
                isLoading.value = false;
            }
        };

        // Filters and pagination methods
        const handleSearch = () => {
            currentPage.value = 1;
            fetchOrders();
        };

        const clearSearch = () => {
            searchQuery.value = "";
            handleSearch();
        };

        const applyFilters = () => {
            currentPage.value = 1;
            fetchOrders();
        };

        const handleSort = (sortData) => {
            sortKey.value = sortData.key;
            sortOrder.value = sortData.order;
            fetchOrders();
        };

        const goToPage = (page) => {
            currentPage.value = page;
            fetchOrders();
        };

        // CRUD operations
        const createOrder = () => {
            router.push("/sales/orders/create");
        };

        const createPocapture = () => {
            router.push("/pdf-order-capture");
        };

        const viewOrder = (order) => {
            router.push(`/sales/orders/${order.so_id}`);
        };

        const editOrder = (order) => {
            router.push(`/sales/orders/${order.so_id}/edit`);
        };

        const confirmDelete = (order) => {
            orderToDelete.value = order;
            showDeleteModal.value = true;
        };

        const closeDeleteModal = () => {
            showDeleteModal.value = false;
        };

        const deleteOrder = async () => {
            try {
                await axios.delete(`/orders/${orderToDelete.value.so_id}`);
                fetchOrders(); // Refresh list after delete
                showDeleteModal.value = false;
                alert("Order successfully deleted");
            } catch (error) {
                console.error("Error deleting order:", error);
                if (error.response?.data?.message) {
                    alert(error.response.data.message);
                } else {
                    alert("An error occurred while deleting the order");
                }
            }
        };

        // Helper methods
        const formatDate = (dateString) => {
            if (!dateString) return "-";
            const date = new Date(dateString);
            return date.toLocaleDateString("id-ID", {
                day: "2-digit",
                month: "short",
                year: "numeric",
            });
        };

        const formatCurrency = (value) => {
            return new Intl.NumberFormat("id-ID", {
                style: "decimal",
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(value ?? 0);
        };

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
                    return "Closed";
                default:
                    return status;
            }
        };

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

        const canEditOrder = (order) => {
            // Only allow edit for orders that are not delivered, invoiced or closed
            return !["Delivered", "Invoiced", "Closed"].includes(order.status);
        };

        const canDeleteOrder = (order) => {
            // Only allow delete if there are no deliveries or invoices
            return (
                order.deliveries?.length === 0
            );
        };

        onMounted(() => {
            fetchOrders();
        });

        return {
            orders,
            columns,
            isLoading,
            currentPage,
            itemsPerPage,
            totalItems,
            totalPages,
            searchQuery,
            statusFilter,
            dateRangeFilter,
            customDateRange,
            showDeleteModal,
            orderToDelete,
            handleSearch,
            clearSearch,
            applyFilters,
            handleSort,
            goToPage,
            createOrder,
            createPocapture,
            viewOrder,
            editOrder,
            confirmDelete,
            closeDeleteModal,
            deleteOrder,
            formatDate,
            formatCurrency,
            getStatusLabel,
            getStatusClass,
            canEditOrder,
            canDeleteOrder,
        };
    },
};
</script>

<style scoped>
.sales-orders {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.custom-date-range {
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.date-range-inputs {
    display: flex;
    gap: 1rem;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.filter-group label {
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--gray-500);
}

.filter-group select,
.filter-group input {
    padding: 0.5rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.order-number {
    font-weight: 500;
    color: var(--primary-color);
}

.po-number {
    font-weight: 400;
    color: #64748b;
    font-style: italic;
}

.customer-info {
    display: flex;
    flex-direction: column;
}

.customer-name {
    font-weight: 500;
}

.order-total {
    font-weight: 500;
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
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

.actions-cell {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.action-btn {
    background: none;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 0.25rem;
    transition: background-color 0.2s;
}

.action-btn.view {
    color: var(--primary-color);
}

.action-btn.edit {
    color: var(--warning-color);
}

.action-btn.delete {
    color: var(--danger-color);
}

.action-btn:hover {
    background-color: #f8fafc;
}

@media (max-width: 768px) {
    .date-range-inputs {
        flex-direction: column;
    }
}
</style>
