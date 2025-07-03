<!-- src/views/sales/DeliveryOrderPrint.vue -->
<template>
  <div class="print-container">
    <!-- Print Actions - Hidden during print -->
    <div class="print-actions no-print">
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

    <!-- Print Content - A4 Document -->
    <div class="document-wrapper">
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

          <!-- PO and Invoice Information Section -->
          <div class="po-invoice-info">
            <table class="po-invoice-table">
              <thead>
                <tr>
                  <th>PO No.</th>
                  <th>PO Date</th>
                  <th>SALES PERSON</th>
                  <th>SHIP VIA</th>
                  <th>Inv No.</th>
                  <th>Inv Date</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>{{ getPONumber() }}</td>
                  <td>{{ formatDate(getPODate()) }}</td>
                  <td>{{ getSalesPerson() }}</td>
                  <td>{{ getShipVia() }}</td>
                  <td>{{ getInvoiceNumber() }}</td>
                  <td>{{ formatDate(getInvoiceDate()) }}</td>
                </tr>
              </tbody>
            </table>
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
                  <td>{{ line.item?.width || '-' }}</td>
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
                  <td class="armstrong-header">ARMSTRONG MIRATECH INDIA PRIVATE LIMITED</td>
                </tr>
                <tr>
                  <td class="armstrong-signature"></td>
                </tr>
              </table>
            </div>
          </div>
        </div>
      </div>
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
    const companyName = ref('ARMSTRONG MIRATECH INDIA PRIVATE LIMITED');
    const companyAddress1 = ref('Plot No 1705, Portia Road, SRI CITY');
    const companyAddress2 = ref('PIN:517646');

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

        // Auto-print if requested in the URL
        if (route.query.autoprint === 'true') {
          setTimeout(() => {
            printDeliveryOrder();
          }, 1000);
        }
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

    // Get PO Number from sales order
    const getPONumber = () => {
      return delivery.value?.sales_order?.po_number ||
             delivery.value?.salesOrder?.po_number ||
             delivery.value?.po_number || '-';
    };

    // Get PO Date from sales order
    const getPODate = () => {
      return delivery.value?.sales_order?.po_date ||
             delivery.value?.salesOrder?.po_date ||
             delivery.value?.po_date || null;
    };

    // Get Sales Person
    const getSalesPerson = () => {
      return delivery.value?.sales_person ||
             delivery.value?.sales_order?.sales_person ||
             delivery.value?.salesOrder?.sales_person ||
             delivery.value?.customer?.sales_person || '-';
    };

    // Get Ship Via (shipping method)
    const getShipVia = () => {
      return delivery.value?.shipping_method ||
             delivery.value?.ship_via ||
             delivery.value?.delivery_method || '-';
    };

    // Get Invoice Number
    const getInvoiceNumber = () => {
      // Check if there's an invoice associated with this delivery
      return delivery.value?.invoice?.invoice_number ||
             delivery.value?.sales_invoice?.invoice_number ||
             delivery.value?.invoice_number || '-';
    };

    // Get Invoice Date
    const getInvoiceDate = () => {
      // Check if there's an invoice associated with this delivery
      return delivery.value?.invoice?.invoice_date ||
             delivery.value?.sales_invoice?.invoice_date ||
             delivery.value?.invoice_date || null;
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

    // Print the delivery order - Updated to use new window approach
    const printDeliveryOrder = () => {
      if (!delivery.value) {
        console.warn("Delivery data not loaded");
        return;
      }

      try {
        // Create a new window for printing
        const printWindow = window.open('', '_blank');

        // Get the current document content
        const documentElement = document.getElementById('delivery-print-content');
        const documentHTML = documentElement.outerHTML;

        // Create the print HTML with comprehensive styling
        const printHTML = `
          <!DOCTYPE html>
          <html>
          <head>
            <meta charset="utf-8">
            <title>Delivery Order - ${delivery.value?.delivery_number || 'Document'}</title>
            <style>
              * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
              }

              body {
                font-family: Arial, sans-serif;
                background: white;
                margin: 0;
                padding: 20px;
              }

              .print-page {
                width: 210mm;
                min-height: 297mm;
                margin: 0 auto;
                background: white;
                padding: 0;
                box-shadow: none;
              }

              .delivery-print-container {
                padding: 20px;
                width: 210mm;
                min-height: 297mm;
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

              .page-content {
                flex: 1;
              }

              .top-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
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

              .info-box strong {
                display: block;
                margin-bottom: 5px;
              }

              .info-box p {
                margin: 3px 0;
              }

              .po-invoice-info {
                margin-bottom: 15px;
              }

              .po-invoice-table {
                width: 100%;
                border-collapse: collapse;
              }

              .po-invoice-table th,
              .po-invoice-table td {
                border: none;
                padding: 8px;
                text-align: left;
              }

              .po-invoice-table thead tr {
                border-bottom: 1px dashed #000;
              }

              .po-invoice-table th {
                font-weight: bold;
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

              .items-table thead tr {
                border-bottom: 1px dashed #000;
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

              .signature-section {
                margin-top: auto;
                position: absolute;
                bottom: 20px;
                left: 20px;
                right: 20px;
              }

              .horizontal-line {
                width: calc(100% + 40px);
                border-top: 1px dashed #000;
                margin-bottom: 15px;
                margin-left: -20px;
                clear: both;
              }

              .signature-container {
                width: 670px;
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
                font-size: 10px;
              }

              .armstrong-signature {
                border: 1px dashed #000;
                padding: 8px;
                height: 65px;
              }

              @media print {
                body {
                  margin: 0 !important;
                  padding: 0 !important;
                }

                .print-page {
                  margin: 0 !important;
                  padding: 0 !important;
                  box-shadow: none !important;
                }

                @page {
                  size: A4;
                  margin: 0.5cm;
                }
              }
            </style>
          </head>
          <body>
            <div class="print-page">
              ${documentHTML}
            </div>
          </body>
          </html>
        `;

        // Write the HTML to the new window
        printWindow.document.write(printHTML);
        printWindow.document.close();

        // Wait for content to load, then print
        printWindow.onload = () => {
          setTimeout(() => {
            printWindow.print();
            printWindow.close();
          }, 500);
        };
      } catch (err) {
        console.error('Error printing:', err);
        alert('Terjadi kesalahan saat mencetak. Silakan coba lagi.');
      }
    };

    // Generate PDF of the delivery order - Fixed to prevent blank PDF
    const printPDF = async () => {
      if (isLoading.value) {
        console.warn("Data is still loading. Please wait before printing PDF.");
        return;
      }

      if (!delivery.value) {
        alert('Konten tidak ditemukan!');
        return;
      }

      try {
        // Simple approach: directly use the existing element
        const element = document.getElementById('delivery-print-content');
        if (!element) {
          console.error('Document content element not found');
          return;
        }

        // Temporarily hide print actions and other non-printable elements
        const printActions = document.querySelector('.print-actions');
        const loadingState = document.querySelector('.loading-state');
        const errorState = document.querySelector('.error-state');

        if (printActions) printActions.style.display = 'none';
        if (loadingState) loadingState.style.display = 'none';
        if (errorState) errorState.style.display = 'none';

        // PDF options - simplified
        const opt = {
          margin: [10, 10, 10, 10], // Small margins in mm
          filename: `DeliveryOrder_${delivery.value?.delivery_number || 'unknown'}.pdf`,
          image: {
            type: 'jpeg',
            quality: 0.95
          },
          html2canvas: {
            scale: 1,
            useCORS: true,
            allowTaint: true,
            logging: true,
            height: element.scrollHeight,
            width: element.scrollWidth
          },
          jsPDF: {
            unit: 'mm',
            format: 'a4',
            orientation: 'portrait'
          }
        };

        // Generate PDF directly from the element
        console.log('Starting PDF generation...');
        await html2pdf().set(opt).from(element).save();
        console.log('PDF generated successfully');

        // Restore hidden elements
        if (printActions) printActions.style.display = '';
        if (loadingState) loadingState.style.display = '';
        if (errorState) errorState.style.display = '';

      } catch (err) {
        console.error('Error generating PDF:', err);

        // Restore hidden elements in case of error
        const printActions = document.querySelector('.print-actions');
        const loadingState = document.querySelector('.loading-state');
        const errorState = document.querySelector('.error-state');

        if (printActions) printActions.style.display = '';
        if (loadingState) loadingState.style.display = '';
        if (errorState) errorState.style.display = '';

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
      getPONumber,
      getPODate,
      getSalesPerson,
      getShipVia,
      getInvoiceNumber,
      getInvoiceDate,
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
/* Main Container - Following SalesOrderPrint pattern */
.print-container {
  min-height: 100vh;
  background-color: #f1f5f9;
  padding: 2rem 1rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
}

/* Print Actions - Following SalesOrderPrint styling */
.print-actions {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
  justify-content: center;
  width: 100%;
  max-width: 210mm;
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
  background-color: #059669;
  color: white;
}

.btn-primary:hover {
  background-color: #047857;
}

.btn-secondary {
  background-color: #e2e8f0;
  color: #1e293b;
}

.btn-secondary:hover {
  background-color: #cbd5e1;
}

.btn-danger {
  background-color: #ef4444;
  color: white;
}

.btn-danger:hover {
  background-color: #dc2626;
}

/* Document wrapper for A4 sizing - Perfectly centered */
.document-wrapper {
  width: 210mm;
  min-height: 297mm;
  margin: 0;
  background-color: white;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  border: 1px solid #e5e7eb;
  position: relative;
  display: block;
}

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

/* Standalone styling for PDF generation */
#delivery-print-content {
  width: 210mm;
  min-height: 297mm;
  background-color: white;
  font-family: Arial, sans-serif;
  color: #000;
  font-size: 12px;
  position: relative;
  display: flex;
  flex-direction: column;
  box-sizing: border-box;
  padding: 20px;
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

/* PO and Invoice Information Section */
.po-invoice-info {
  margin-bottom: 15px;
}

.po-invoice-table {
  width: 100%;
  border-collapse: collapse;
  border-top: none;
}

.po-invoice-table th,
.po-invoice-table td {
  border: none;
  padding: 8px;
  text-align: left;
}

/* Add a line under the header row */
.po-invoice-table thead tr {
  border-bottom: 1px dashed #000;
  border-top: none;
}

.po-invoice-table th {
  font-weight: bold;
  border-top: none;
}

/* Items Table */
.items-section {
  margin-bottom: 20px;
}

.items-table {
  width: 100%;
  border-collapse: collapse;
  border-top: none;
}

.items-table th,
.items-table td {
  border: none;
  padding: 8px;
  text-align: left;
}

/* Add a line under the header row */
.items-table thead tr {
  border-bottom: 1px dashed #000;
  border-top: none;
}

.items-table th {
  font-weight: bold;
  border-top: none;
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
  border-top: 1px dashed #000;
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
  font-size: 10px;
}

.armstrong-signature {
  border: 1px dashed #000;
  padding: 8px;
  height: 65px;
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
  .no-print {
    display: none !important;
  }

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

  .items-table,
  .po-invoice-table {
    page-break-inside: auto;
  }

  .items-table tr,
  .po-invoice-table tr {
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
  .print-container {
    padding: 1rem;
  }

  .print-actions {
    margin-bottom: 15px;
    padding: 5px 0;
    flex-wrap: wrap;
    gap: 8px;
  }

  .print-actions .btn {
    font-size: 12px;
    padding: 6px 12px;
  }

  .document-wrapper {
    width: 100%;
  }

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

  .po-invoice-table {
    font-size: 10px;
  }

  .po-invoice-table th,
  .po-invoice-table td {
    padding: 5px 3px;
    font-size: 10px;
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
