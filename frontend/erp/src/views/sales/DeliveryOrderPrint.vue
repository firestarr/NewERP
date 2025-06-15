<!-- src/views/sales/DeliveryOrderPrint.vue -->
<template>
  <div class="delivery-print-container" id="delivery-print-content">
    <div class="page-content">
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
              <span>Page</span>
              <span>:</span>
              <span>{{ currentPage }} of {{ totalPages }}</span>
            </div>
          </div>
          <div class="detail-row">
            <div class="detail-item">
              <span>DO No</span>
              <span>:</span>
              <span>{{ delivery?.delivery_number }}</span>
            </div>
          </div>
          <div class="detail-row">
            <div class="detail-item">
              <span>DO Date</span>
              <span>:</span>
              <span>{{ formatDate(delivery?.delivery_date) }}</span>
            </div>
          </div>
          <div class="detail-row">
            <div class="detail-item">
              <span>BC No.</span>
              <span>:</span>
              <span>{{ delivery?.bc_number || '-' }}</span>
            </div>
          </div>
          <div class="detail-row">
            <div class="detail-item">
              <span>No Aju</span>
              <span>:</span>
              <span>{{ delivery?.aju_number || '-' }}</span>
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
              <th>SO NO.</th>
              <th>L</th>
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
              <td>{{ delivery?.sales_order?.so_number }}</td>
              <td>{{ line.item?.width }}</td>
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
    </div>

    <!-- Signature Section - Footer -->
    <div class="signature-section">
      <div class="signature-container">
        <div class="horizontal-line"></div>
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

  <!-- Print Actions - Only visible on screen -->
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

  <!-- Loading State -->
  <div v-if="isLoading" class="loading-state">
    <div class="loading-spinner">
      <i class="fas fa-spinner fa-spin"></i>
      <p>Loading delivery data...</p>
    </div>
  </div>

  <!-- Error State -->
  <div v-if="error" class="error-state">
    <div class="error-message">
      <i class="fas fa-exclamation-triangle"></i>
      <p>{{ error }}</p>
      <button class="btn btn-primary" @click="loadDelivery">Retry</button>
    </div>
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
    const currentPage = ref(1);
    const totalPages = ref(1);

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

        // Ensure deliveryLines exists
        if (!delivery.value.deliveryLines) {
          delivery.value.deliveryLines = [];
        }

        // Calculate total pages
        totalPages.value = calculateTotalPages(delivery.value.deliveryLines.length);

        // Set the page title
        document.title = `Delivery Order - ${delivery.value.delivery_number}`;
      } catch (err) {
        console.error('Error loading delivery data:', err);
        error.value = 'Terjadi kesalahan saat memuat data pengiriman. Silakan coba lagi.';
      } finally {
        isLoading.value = false;
      }
    };

    // Format date to DD/MM/YYYY
    const formatDate = (dateString) => {
      if (!dateString) return '-';
      try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '-';
        return `${date.getDate().toString().padStart(2, '0')}/${(date.getMonth() + 1).toString().padStart(2, '0')}/${date.getFullYear()}`;
      } catch (err) {
        console.error('Error formatting date:', err);
        return '-';
      }
    };

    // Calculate total pages based on items
    const calculateTotalPages = (itemCount) => {
      const itemsPerPage = 10; // Minimum rows per page
      return Math.max(1, Math.ceil(itemCount / itemsPerPage));
    };

    // Calculate empty rows to add to the table
    const getEmptyRows = (itemCount) => {
      // Add empty rows based on the number of parts
      const minRows = 10;
      return Math.max(0, minRows - itemCount);
    };

    // Print the delivery order
    const printDeliveryOrder = () => {
      try {
        const printContents = document.getElementById('delivery-print-content').innerHTML;
        const originalContents = document.body.innerHTML;

        // Create print styles
        const printStyles = `
          <style>
            @media print {
              * { margin: 0; padding: 0; box-sizing: border-box; }
              body { font-family: Arial, sans-serif; font-size: 12px; }
              .delivery-print-container {
                width: 210mm;
                min-height: 297mm;
                margin: 0;
                padding: 20mm;
                box-sizing: border-box;
              }
              .signature-section {
                position: absolute;
                bottom: 20mm;
                left: 20mm;
                right: 20mm;
              }
              @page { margin: 0; size: A4 portrait; }
            }
          </style>
        `;

        document.body.innerHTML = printStyles + printContents;
        window.print();
        document.body.innerHTML = originalContents;
        window.location.reload();
      } catch (err) {
        console.error('Error printing:', err);
        alert('Terjadi kesalahan saat mencetak. Silakan coba lagi.');
      }
    };

    // Generate PDF of the delivery order
    const printPDF = async () => {
      try {
        const element = document.getElementById('delivery-print-content');
        if (!element) {
          alert('Konten tidak ditemukan!');
          return;
        }

        const opt = {
          margin: [20, 20, 20, 20], // mm: top, left, bottom, right
          filename: `DeliveryOrder_${delivery.value?.delivery_number || 'unknown'}.pdf`,
          image: { type: 'jpeg', quality: 0.98 },
          html2canvas: {
            scale: 2,
            useCORS: true,
            allowTaint: true,
            height: window.innerHeight,
            width: window.innerWidth
          },
          jsPDF: {
            unit: 'mm',
            format: 'a4',
            orientation: 'portrait'
          },
          pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
        };

        await html2pdf().set(opt).from(element).save();
      } catch (err) {
        console.error('Error generating PDF:', err);
        alert('Terjadi kesalahan saat membuat PDF. Silakan coba lagi.');
      }
    };

    // Navigate back to delivery detail
    const goBack = () => {
      router.push(`/sales/deliveries/${route.params.id}`);
    };

    // Initialize component
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
      currentPage,
      totalPages,
      formatDate,
      getEmptyRows,
      calculateTotalPages,
      printDeliveryOrder,
      printPDF,
      goBack,
      loadDelivery
    };
  }
};
</script>

<style scoped>
/* Main Container - A4 Size */
.delivery-print-container {
  padding: 20px;
  width: 210mm; /* A4 width */
  min-height: 297mm; /* A4 height */
  margin: 0 auto;
  background-color: white;
  font-family: Arial, sans-serif;
  color: #000;
  font-size: 12px;
  position: relative;
  display: flex;
  flex-direction: column;
  box-sizing: border-box;
}

/* Page Content Area */
.page-content {
  flex: 1;
}

/* Header Section */
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

/* Document Details */
.document-details {
  margin-top: 5px;
  text-align: right;
}

.detail-row {
  margin-bottom: 2px;
  display: flex;
  justify-content: flex-end;
}

.detail-item {
  display: flex;
  align-items: center;
  font-size: 11px;
  min-width: 180px;
}

.detail-item span:first-child {
  font-weight: bold;
  width: 70px;
  text-align: left;
}

.detail-item span:nth-child(2) {
  margin: 0 8px;
  width: 10px;
  text-align: center;
}

.detail-item span:nth-child(3) {
  flex: 1;
  text-align: left;
}

/* Document Title */
.document-title-section {
  text-align: center;
  margin: 15px 0;
}

.document-title-section h1 {
  font-size: 18px;
  margin: 0;
  font-weight: bold;
}

/* Customer Information */
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

/* Items Table */
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

/* Signature Section Styles - Fixed at bottom */
.signature-section {
  margin-top: auto;
  position: absolute;
  bottom: 20px;
  left: 20px;
  right: 20px;
}

.horizontal-line {
  width: calc(100% + 40px);
  border-top: 1px solid #000;
  margin-bottom: 15px;
  margin-left: -20px;
  clear: both;
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

/* Print Actions */
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

.btn-danger {
  background-color: #dc2626;
  color: white;
}

.btn-danger:hover {
  background-color: #b91c1c;
}

/* Loading State */
.loading-state {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(255, 255, 255, 0.9);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.loading-spinner {
  text-align: center;
  padding: 20px;
}

.loading-spinner i {
  font-size: 32px;
  color: #2563eb;
  margin-bottom: 10px;
}

.loading-spinner p {
  font-size: 16px;
  color: #64748b;
}

/* Error State */
.error-state {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(255, 255, 255, 0.9);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.error-message {
  text-align: center;
  padding: 30px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.1);
  max-width: 400px;
}

.error-message i {
  font-size: 48px;
  color: #dc2626;
  margin-bottom: 15px;
}

.error-message p {
  font-size: 16px;
  color: #374151;
  margin-bottom: 20px;
}

/* Print Media Styles */
@media print {
  body {
    margin: 0;
    padding: 0;
    background: white;
  }

  .delivery-print-container {
    width: 210mm;
    min-height: 297mm;
    margin: 0;
    padding: 20mm;
    box-sizing: border-box;
    border: none;
    position: relative;
  }

  .page-content {
    padding-bottom: 100px;
  }

  .signature-section {
    position: absolute;
    bottom: 20mm;
    left: 20mm;
    right: 20mm;
  }

  .print-actions,
  .loading-state,
  .error-state {
    display: none !important;
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
    margin: 0;
    size: A4 portrait;
  }

  /* Ensure no page breaks in signature area */
  .signature-container {
    page-break-inside: avoid;
  }

  /* Hide browser print headers */
  html, body {
    margin: 0 !important;
    padding: 0 !important;
  }
}

/* Responsive Design for Screen View */
@media screen and (max-width: 768px) {
  .delivery-print-container {
    width: 100%;
    min-width: 300px;
    padding: 10px;
  }

  .top-header {
    flex-direction: column;
    gap: 15px;
  }

  .info-row {
    flex-direction: column;
  }

  .left-column,
  .right-column {
    padding: 5px 0;
  }

  .tables-container {
    flex-direction: column;
    gap: 15px;
  }

  .left-table,
  .right-table {
    width: 100%;
  }

  .signature-section {
    position: relative;
    bottom: auto;
    left: auto;
    right: auto;
    margin-top: 30px;
  }

  .page-content {
    padding-bottom: 20px;
  }

  .detail-row {
    margin-bottom: 2px;
    justify-content: flex-start;
  }

  .detail-item {
    min-width: 150px;
    font-size: 10px;
  }

  .detail-item span:first-child {
    width: 60px;
  }
}
</style>
