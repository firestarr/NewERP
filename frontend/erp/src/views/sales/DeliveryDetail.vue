<!-- src/views/sales/DeliveryDetail.vue -->

<!-- ==================== TEMPLATE SECTION ==================== -->
<template>
    <div class="delivery-detail">
      <div class="page-header">
        <h1>Delivery Details</h1>
        <div class="page-actions">
          <button class="btn btn-secondary" @click="goBack">
            <i class="fas fa-arrow-left"></i> Back
          </button>
          <div class="btn-group" v-if="delivery">
            <button
              class="btn btn-primary"
              @click="editDelivery"
              v-if="canEdit"
            >
              <i class="fas fa-edit"></i> Edit
            </button>

            <button
              v-if="delivery.status === 'Pending'"
              class="btn btn-info"
              @click="markAsInTransit"
            >
              <i class="fas fa-truck"></i> Start Delivery
            </button>

            <button
              v-if="delivery.status === 'In Transit'"
              class="btn btn-success"
              @click="completeDelivery"
            >
              <i class="fas fa-check"></i> Complete
            </button>

            <button
              v-if="canCancel"
              class="btn btn-danger"
              @click="confirmCancel"
            >
              <i class="fas fa-times"></i> Cancel
            </button>

            <button
              class="btn btn-secondary"
              @click="printDeliveryNote"
            >
              <i class="fas fa-print"></i> Print
            </button>
          </div>
        </div>
      </div>

      <div v-if="isLoading" class="loading-indicator">
        <i class="fas fa-spinner fa-spin"></i> Loading delivery data...
      </div>

      <div v-else-if="!delivery" class="empty-state">
        <div class="empty-icon">
          <i class="fas fa-exclamation-circle"></i>
        </div>
        <h3>Delivery not found</h3>
        <p>The delivery you are looking for may have been deleted or does not exist.</p>
        <button class="btn btn-primary" @click="goBack">
          Back to delivery list
        </button>
      </div>

      <div v-else class="delivery-container">
        <!-- Delivery Header -->
        <div class="detail-card">
          <div class="card-header">
            <h2>Delivery Information</h2>
            <div class="delivery-status" :class="getStatusClass(delivery.status)">
              {{ getStatusLabel(delivery.status) }}
            </div>
          </div>
          <div class="card-body">
            <div class="info-grid">
              <div class="info-group">
                <label>Delivery Number</label>
                <div class="info-value">{{ delivery.delivery_number }}</div>
              </div>

              <div class="info-group">
                <label>Delivery Date</label>
                <div class="info-value">{{ formatDate(delivery.delivery_date) }}</div>
              </div>

              <div class="info-group">
                <label>Sales Order Number</label>
                <div class="info-value">
                  <router-link :to="`/sales/orders/${delivery.so_id}`">
                    {{ delivery.salesOrder?.so_number || '-' }}
                  </router-link>
                </div>
              </div>

              <div class="info-group">
                <label>Shipping Method</label>
                <div class="info-value">{{ delivery.shipping_method || '-' }}</div>
              </div>

              <div class="info-group">
                <label>Tracking Number</label>
                <div class="info-value">{{ delivery.tracking_number || '-' }}</div>
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
                <div class="info-value">{{ delivery.customer.name }}</div>
              </div>

              <div class="info-group">
                <label>Customer Code</label>
                <div class="info-value">{{ delivery.customer.customer_code }}</div>
              </div>

              <div class="info-group">
                <label>Address</label>
                <div class="info-value">{{ delivery.customer.address || '-' }}</div>
              </div>

              <div class="info-group">
                <label>Contact Person</label>
                <div class="info-value">{{ delivery.customer.contact_person || '-' }}</div>
              </div>

              <div class="info-group">
                <label>Phone</label>
                <div class="info-value">{{ delivery.customer.phone || '-' }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Delivery Items -->
        <div class="detail-card">
          <div class="card-header">
            <h2>Delivery Items</h2>
          </div>
          <div class="card-body">
            <div class="delivery-items">
              <table class="items-table">
                <thead>
                  <tr>
                    <th>Item</th>
                    <th>Quantity Delivered</th>
                    <th>Warehouse</th>
                    <th>Batch Number</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="line in delivery.deliveryLines" :key="line.line_id">
                    <td>
                      <div class="item-info">
                        <div class="item-code">{{ line.item.item_code }}</div>
                        <div class="item-name">{{ line.item.name }}</div>
                      </div>
                    </td>
                    <td>{{ line.delivered_quantity }} {{ line.salesOrderLine?.unitOfMeasure?.symbol || '' }}</td>
                    <td>{{ line.warehouse?.name || '-' }}</td>
                    <td>{{ line.batch_number || '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Delivery Timeline -->
        <div class="detail-card">
          <div class="card-header">
            <h2>Delivery Timeline</h2>
          </div>
          <div class="card-body">
            <div class="delivery-timeline">
              <div class="timeline-item" :class="{ 'active': delivery.status === 'Pending' || delivery.status === 'In Transit' || delivery.status === 'Completed' }">
                <div class="timeline-icon">
                  <i class="fas fa-clipboard-check"></i>
                </div>
                <div class="timeline-content">
                  <h3>Delivery Created</h3>
                  <p>{{ formatDate(delivery.delivery_date) }}</p>
                </div>
              </div>

              <div class="timeline-item" :class="{ 'active': delivery.status === 'In Transit' || delivery.status === 'Completed' }">
                <div class="timeline-icon">
                  <i class="fas fa-truck"></i>
                </div>
                <div class="timeline-content">
                  <h3>In Transit</h3>
                  <p>Items are in the delivery process</p>
                </div>
              </div>

              <div class="timeline-item" :class="{ 'active': delivery.status === 'Completed' }">
                <div class="timeline-icon">
                  <i class="fas fa-check-circle"></i>
                </div>
                <div class="timeline-content">
                  <h3>Delivery Completed</h3>
                  <p>Items have been received by the customer</p>
                </div>
              </div>

              <div class="timeline-item cancelled" :class="{ 'active': delivery.status === 'Cancelled' }">
                <div class="timeline-icon">
                  <i class="fas fa-times-circle"></i>
                </div>
                <div class="timeline-content">
                  <h3>Delivery Cancelled</h3>
                  <p>Delivery has been cancelled</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Cancel Confirmation Modal -->
      <ConfirmationModal
        v-if="showCancelModal"
        title="Cancel Confirmation"
        :message="`Are you sure you want to cancel delivery <strong>${delivery.delivery_number}</strong>?`"
        confirm-button-text="Cancel"
        confirm-button-class="btn btn-danger"
        @confirm="cancelDelivery"
        @close="showCancelModal = false"
      />

      <!-- Complete Confirmation Modal -->
      <ConfirmationModal
        v-if="showCompleteModal"
        title="Complete Confirmation"
        :message="`Are you sure you want to complete delivery <strong>${delivery.delivery_number}</strong>?`"
        confirm-button-text="Complete"
        confirm-button-class="btn btn-success"
        @confirm="confirmCompleteDelivery"
        @close="showCompleteModal = false"
      />
    </div>
  </template>
  <!-- ==================== SCRIPT SECTION ==================== -->
  <script>
  import { ref, computed, onMounted } from 'vue';
  import { useRouter, useRoute } from 'vue-router';
  import axios from 'axios';

  export default {
    name: 'DeliveryDetail',
    setup() {
      const router = useRouter();
      const route = useRoute();

      // Data
      const delivery = ref(null);
      const isLoading = ref(true);
      const showCancelModal = ref(false);
      const showCompleteModal = ref(false);

      // Computed properties
      const canEdit = computed(() => {
        if (!delivery.value) return false;
        return delivery.value.status !== 'Completed' && delivery.value.status !== 'Cancelled';
      });

      const canCancel = computed(() => {
        if (!delivery.value) return false;
        return delivery.value.status !== 'Completed' && delivery.value.status !== 'Cancelled';
      });

      // Load delivery data
      const loadDelivery = async () => {
        isLoading.value = true;

        try {
          const response = await axios.get(`/deliveries/${route.params.id}`);
          const data = response.data.data;
          // Convert snake_case delivery_lines to camelCase deliveryLines
          if (data.delivery_lines) {
            data.deliveryLines = data.delivery_lines;
            delete data.delivery_lines;
          }
          delivery.value = data;
        } catch (error) {
          console.error('Error loading delivery:', error);
          delivery.value = null;
        } finally {
          isLoading.value = false;
        }
      };

      // Format date
      const formatDate = (dateString) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
          day: '2-digit',
          month: 'long',
          year: 'numeric'
        });
      };

      // Get status label
      const getStatusLabel = (status) => {
        switch (status) {
          case 'Pending': return 'Pending';
          case 'In Transit': return 'In Transit';
          case 'Completed': return 'Completed';
          case 'Cancelled': return 'Cancelled';
          default: return status;
        }
      };

      // Get status class
      const getStatusClass = (status) => {
        switch (status) {
          case 'Pending': return 'status-pending';
          case 'In Transit': return 'status-transit';
          case 'Completed': return 'status-completed';
          case 'Cancelled': return 'status-cancelled';
          default: return '';
        }
      };

      // Navigation methods
      const goBack = () => {
        router.push('/sales/deliveries');
      };

      const editDelivery = () => {
        router.push(`/sales/deliveries/${delivery.value.delivery_id}/edit`);
      };

      // Actions
      const markAsInTransit = async () => {
        try {
          await axios.put(`/deliveries/${delivery.value.delivery_id}`, {
            ...delivery.value,
            status: 'In Transit'
          });

          delivery.value.status = 'In Transit';
          alert('Delivery status successfully changed to In Transit');
        } catch (error) {
          console.error('Error updating delivery status:', error);
          alert('Error occurred while updating delivery status');
        }
      };

      const completeDelivery = () => {
        showCompleteModal.value = true;
      };

      const confirmCompleteDelivery = async () => {
        try {
          await axios.post(`/deliveries/${delivery.value.delivery_id}/complete`);

          delivery.value.status = 'Completed';
          showCompleteModal.value = false;
          alert('Delivery successfully completed!');
        } catch (error) {
          console.error('Error completing delivery:', error);
          alert('Error occurred while completing delivery');
        }
      };

      const confirmCancel = () => {
        showCancelModal.value = true;
      };

      const cancelDelivery = async () => {
        try {
          await axios.put(`/deliveries/${delivery.value.delivery_id}`, {
            ...delivery.value,
            status: 'Cancelled'
          });

          delivery.value.status = 'Cancelled';
          showCancelModal.value = false;
          alert('Delivery successfully cancelled!');
        } catch (error) {
          console.error('Error cancelling delivery:', error);
          alert('Error occurred while cancelling delivery');
        }
      };

      const printDeliveryNote = () => {
        router.push(`/sales/deliveries/${delivery.value.delivery_id}/print`);
      };

      onMounted(() => {
        loadDelivery();
      });

      return {
        delivery,
        isLoading,
        canEdit,
        canCancel,
        showCancelModal,
        showCompleteModal,
        formatDate,
        getStatusLabel,
        getStatusClass,
        goBack,
        editDelivery,
        markAsInTransit,
        completeDelivery,
        confirmCompleteDelivery,
        confirmCancel,
        cancelDelivery,
        printDeliveryNote
      };
    }
  };
  </script>

  <!-- ==================== STYLE SECTION ==================== -->
  <style scoped>
  .delivery-detail {
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

  .delivery-container {
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

  .delivery-status {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
  }

  .status-pending {
    background-color: #e2e8f0;
    color: #475569;
  }

  .status-transit {
    background-color: #dbeafe;
    color: #2563eb;
  }

  .status-completed {
    background-color: #d1fae5;
    color: #059669;
  }

  .status-cancelled {
    background-color: #fee2e2;
    color: #dc2626;
  }

  .items-table {
    width: 100%;
    border-collapse: collapse;
  }

  .items-table th,
  .items-table td {
    padding: 0.75rem;
    text-align: left;
  }

  .items-table th {
    background-color: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    font-weight: 500;
    color: #64748b;
    font-size: 0.75rem;
  }

  .items-table td {
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.875rem;
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

  .delivery-timeline {
    position: relative;
    padding-left: 2rem;
  }

  .delivery-timeline::before {
    content: '';
    position: absolute;
    top: 0;
    left: 8px;
    height: 100%;
    width: 2px;
    background-color: #e2e8f0;
  }

  .timeline-item {
    position: relative;
    padding-bottom: 2rem;
    opacity: 0.5;
  }

  .timeline-item.active {
    opacity: 1;
  }

  .timeline-item:last-child {
    padding-bottom: 0;
  }

  .timeline-icon {
    position: absolute;
    left: -2rem;
    top: 0;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background-color: white;
    border: 2px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
  }

  .timeline-item.active .timeline-icon {
    background-color: #2563eb;
    border-color: #2563eb;
    color: white;
  }

  .timeline-item.cancelled.active .timeline-icon {
    background-color: #dc2626;
    border-color: #dc2626;
  }

  .timeline-content h3 {
    font-size: 0.875rem;
    font-weight: 600;
    margin: 0 0 0.25rem 0;
    color: #1e293b;
  }

  .timeline-content p {
    font-size: 0.75rem;
    color: #64748b;
    margin: 0;
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

  .btn-danger {
    background-color: #dc2626;
    color: white;
  }

  .btn-danger:hover {
    background-color: #b91c1c;
  }

  @media (max-width: 768px) {
    .page-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 1rem;
    }

    .btn-group {
      flex-wrap: wrap;
    }

    .info-grid,
    .customer-info {
      grid-template-columns: 1fr;
      gap: 0.75rem;
    }

    .items-table {
      display: block;
      overflow-x: auto;
    }
  }

  /* Print styles */
  @media print {
    .page-actions,
    .btn,
    .btn-group {
      display: none !important;
    }

    .page-header {
      text-align: center;
      margin-bottom: 2rem;
    }

    .detail-card {
      page-break-inside: avoid;
      margin-bottom: 2rem;
      box-shadow: none;
      border: 1px solid #e2e8f0;
    }

    .items-table th,
    .items-table td {
      padding: 0.5rem;
    }
  }
  </style>