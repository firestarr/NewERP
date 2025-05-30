<!-- src/views/sales/DeliveryOrderPrint.vue -->
<template>
  <div class="delivery-print-container" id="delivery-print-content">
    <!-- Company Header & Document Info Section -->
    <div class="top-header">
      <div class="company-info">
        <h1>{{ companyName }}</h1>
        <p>{{ companyAddress1 }}</p>
        <p>{{ companyAddress2 }}</p>
      </div>
      <div class="document-details">
        <div class="detail-row">
          <div class="detail-item">
            <span>DO No</span>
            <span>:</span>
            <span>{{ delivery?.delivery_number }}</span>
          </div>
          <div class="detail-item">
            <span>DO Date</span>
            <span>:</span>
            <span>{{ formatDate(delivery?.delivery_date) }}</span>
          </div>
        </div>
        <div class="detail-row">
          <div class="detail-item">
            <span>Page</span>
            <span>:</span>
            <span>1 of 1</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Document Title -->
    <div class="document-title-section">
      <h1>DELIVERY ORDER</h1>
    </div>

    <!-- Customer Information -->
    <div class="document-info">
      <div class="info-row">
        <div class="left-column">
          <div class="info-box no-border">
            <strong>SOLD TO:</strong>
            <p>{{ delivery?.customer?.name }}</p>
            <p>{{ delivery?.customer?.address }}</p>
            <p>{{ delivery?.customer?.city }}</p>
          </div>
        </div>
        <div class="right-column">
          <div class="info-box no-border">
            <strong>SHIP TO:</strong>
            <p>{{ delivery?.customer?.name }}</p>
            <p>{{ delivery?.customer?.address }}</p>
            <p>{{ delivery?.customer?.city }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Items Table -->
    <div class="items-section">
      <table class="items-table">
        <thead>
          <tr>
            <th>NO.</th>
            <th>PART NO.</th>
            <th>Description</th>
            <th>Qty</th>
            <th>UOM</th>
            <th>Remarks</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(line, index) in delivery?.deliveryLines" :key="line.line_id">
            <td>{{ index + 1 }}</td>
            <td>{{ line.item?.item_code }}</td>
            <td>{{ line.item?.name }}</td>
            <td class="text-right">{{ line.delivered_quantity }}</td>
            <td>{{ line.salesOrderLine?.unitOfMeasure?.symbol || 'PCS' }}</td>
            <td>{{ line.batch_number || '' }}</td>
          </tr>
          <!-- Add empty rows to fill space if needed -->
          <tr v-for="n in getEmptyRows(delivery?.deliveryLines?.length || 0)" :key="`empty-${n}`" class="empty-row">
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Signature Section -->
    <div class="signature-section">
      <div class="signature-container">
        <p class="condition-text">Received the Abovementioned in Good Condition</p>

        <div class="tables-container">
          <table class="signature-table left-table">
            <tr>
              <td class="left-cell">Received BY</td>
              <td class="right-cell">Delivered By</td>
            </tr>
            <tr>
              <td class="left-cell empty-cell"></td>
              <td class="right-cell empty-cell"></td>
            </tr>
          </table>

          <table class="signature-table right-table">
            <tr>
              <td class="armstrong-header">PT. ARMSTRONG INDUSTRI INDONESIA</td>
            </tr>
            <tr>
              <td class="armstrong-signature"></td>
            </tr>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="print-actions">
    <button class="btn btn-secondary" @click="goBack">
      <i class="fas fa-arrow-left"></i> Back
    </button>
    <button class="btn btn-primary" @click="printDeliveryOrder">
      <i class="fas fa-print"></i> Print
    </button>
    <button class="btn btn-danger" @click="printPDF">
      <i class="fas fa-file-pdf"></i> Save as PDF
    </button>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import html2pdf from 'html2pdf.js';

export default {
  name: 'DeliveryOrderPrint',
  setup() {
    const router = useRouter();
    const route = useRoute();
    const delivery = ref(null);
    const isLoading = ref(true);
    const error = ref('');

    // Company information
    const companyName = ref('PT. ARMSTRONG INDUSTRI INDONESIA');
    const companyAddress1 = ref('EJIP Industrial park Plot1 A-3, Desa Sukaresmi');
    const companyAddress2 = ref('Cikarang Selatan, Bekasi 17857, Indonesia');

    // Load delivery data
    const loadDelivery = async () => {
      isLoading.value = true;
      error.value = '';

      try {
        const response = await axios.get(`/deliveries/${route.params.id}`);
        delivery.value = response.data.data;

        // Convert any snake_case properties to camelCase if needed
        if (delivery.value.delivery_lines) {
          delivery.value.deliveryLines = delivery.value.delivery_lines;
          delete delivery.value.delivery_lines;
        }

        // Set the page title
        document.title = `Delivery Order - ${delivery.value.delivery_number}`;
      } catch (err) {
        console.error('Error loading delivery data:', err);
        error.value = 'Terjadi kesalahan saat memuat data pengiriman.';
      } finally {
        isLoading.value = false;
      }
    };

    // Format date to DD/MM/YYYY
    const formatDate = (dateString) => {
      if (!dateString) return '-';
      const date = new Date(dateString);
      return `${date.getDate().toString().padStart(2, '0')}/${(date.getMonth() + 1).toString().padStart(2, '0')}/${date.getFullYear()}`;
    };

    // Calculate empty rows to add to the table
    const getEmptyRows = (itemCount) => {
      // Add empty rows based on the number of parts
      const minRows = 10;
      return Math.max(0, minRows - itemCount);
    };

    // Print the delivery order
    const printDeliveryOrder = () => {
      const printContents = document.getElementById('delivery-print-content').innerHTML;
      const originalContents = document.body.innerHTML;

      document.body.innerHTML = printContents;
      window.print();
      document.body.innerHTML = originalContents;
      window.location.reload(); // Reload to restore event listeners and Vue bindings
    };

    // Print PDF of the delivery order
    const printPDF = () => {
      const element = document.getElementById('delivery-print-content');
      if (!element) {
        alert('Content to print not found!');
        return;
      }
      const opt = {
        margin: 0.5,
        filename: `DeliveryOrder_${delivery.value?.delivery_number || 'unknown'}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
      };
      html2pdf().set(opt).from(element).save();
    };

    // Go back to the delivery detail page
    const goBack = () => {
      router.push(`/sales/deliveries/${route.params.id}`);
    };

    onMounted(() => {
      loadDelivery();
    });

    return {
      delivery,
      isLoading,
      error,
      companyName,
      companyAddress1,
      companyAddress2,
      formatDate,
      getEmptyRows,
      printDeliveryOrder,
      printPDF,
      goBack
    };
  }
};
</script>

<style scoped>
.delivery-print-container {
  padding: 20px;
  max-width: 210mm; /* A4 width */
  margin: 0 auto;
  background-color: white;
  font-family: Arial, sans-serif;
  color: #000;
  font-size: 12px;
}

.top-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.company-info {
  text-align: left;
}

.company-info h1 {
  font-size: 16px;
  margin: 0 0 5px 0;
  font-weight: bold;
}

.company-info p {
  margin: 0;
  line-height: 1.3;
}

.document-details {
  margin-top: 5px;
}

.document-title-section {
  text-align: center;
  margin: 15px 0;
}

.document-title-section h1 {
  font-size: 18px;
  margin: 0;
  font-weight: bold;
}

.document-info {
  margin-bottom: 15px;
}

.info-row {
  display: flex;
  margin-bottom: 15px;
}

.left-column {
  flex: 1;
  padding-right: 15px;
}

.right-column {
  flex: 1;
  padding-left: 15px;
}

.info-box {
  padding: 8px;
}

.info-box.no-border {
  border: none;
}

.info-box strong {
  display: block;
  margin-bottom: 5px;
}

.info-box p {
  margin: 3px 0;
}

.details-table {
  width: 100%;
}

.details-table td:first-child {
  font-weight: bold;
  width: 25%;
}

.details-table td:nth-child(2) {
  width: 5%;
  text-align: center;
}

.items-section {
  margin-bottom: 20px;
}

.items-table {
  width: 100%;
  border-collapse: collapse;
}

.items-table th,
.items-table td {
  border: none;
  padding: 8px;
  text-align: left;
}

/* Add a line under the header row */
.items-table thead tr {
  border-bottom: 1px solid #000;
}

.items-table th {
  font-weight: bold;
}

.items-table .text-right {
  text-align: right;
}

.empty-row td {
  height: 24px;
}

/* Add horizontal line at the bottom of the table */
.items-table tbody tr:last-child {
  border-bottom: 1px solid #000;
}

/* Signature Section Styles */
.signature-section {
  margin-top: 40px;
}

.signature-container {
  width: 670px; /* Combined width of both tables + gap */
  margin: 0 auto;
}

.condition-text {
  font-size: 12px;
  margin: 0 0 10px 0;
  text-align: left;
}

.tables-container {
  display: flex;
  justify-content: space-between;
  gap: 20px;
}

.signature-table {
  border-collapse: collapse;
  border: 1px dashed #000;
}

.left-table {
  width: 325px;
}

.right-table {
  width: 325px;
}

.left-cell, .right-cell {
  border: 1px dashed #000;
  padding: 8px;
  text-align: center;
  vertical-align: top;
  width: 50%;
  height: 25px;
}

.empty-cell {
  height: 65px !important;
}

.armstrong-header {
  border: 1px dashed #000;
  padding: 8px;
  text-align: center;
  vertical-align: top;
  height: 25px;
}

.armstrong-signature {
  border: 1px dashed #000;
  padding: 8px;
  height: 65px;
}

/* Print actions - only visible in screen view */
.print-actions {
  display: flex;
  justify-content: center;
  gap: 15px;
  margin-top: 30px;
}

.btn {
  padding: 10px 15px;
  font-size: 14px;
  font-weight: bold;
  border-radius: 5px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
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

.btn-success {
  background-color: #16a34a;
  color: white;
}

.btn-success:hover {
  background-color: #15803d;
}

/* Print media styles */
@media print {
  .delivery-print-container {
    padding: 0;
  }

  .print-actions {
    display: none;
  }

  .items-table {
    page-break-inside: auto;
  }

  .items-table tr {
    page-break-inside: avoid;
  }

  .signature-section {
    page-break-inside: avoid;
  }

  @page {
    margin: 1cm;
    size: A4 portrait;
  }
}
</style>
