<!-- src/views/sales/SalesOrderPrint.vue -->
<template>
  <div class="print-container">
    <!-- Print Actions - Hidden during print -->
    <div class="print-actions no-print">
      <button class="btn btn-primary" @click="printDocument">
        <i class="fas fa-print"></i> Print Document
      </button>
      <button class="btn btn-danger" @click="printPdf" style="margin-left: 0.5rem;">
        <i class="fas fa-file-pdf"></i> Save as PDF
      </button>
      <button class="btn btn-secondary" @click="goBack">
        <i class="fas fa-arrow-left"></i> Back
      </button>
    </div>

    <!-- Print Content - A4 Document with Original Layout -->
    <div class="document-wrapper">
      <div id="printDocument" class="sales-order-document">
        <div class="document-header">
          <div class="company-info">
            <h1 class="company-name">ARMSTRONG MIRATECH INDIA PRIVATE LIMITED</h1>
            <p>Plot No 1705, Portia Road, SRI CITY</p>
            <p>PIN:517646</p>
          </div>
          <div class="document-title">
            <h2>SALES ORDER</h2>
            <div class="document-number">No. {{ order?.soNumber }}</div>
            <div class="page-info">Page: {{ currentPage }} of {{ totalPages }}</div>
          </div>
        </div>

        <div class="document-info">
          <div class="customer-details">
            <div class="customer-name">{{ order?.customer?.name }}</div>
            <div class="customer-address">{{ order?.customer?.address }}</div>
          </div>
          <div class="order-details">
            <table class="info-table">
              <tr>
                <td>Date</td>
                <td>:</td>
                <td>{{ formatDate(order?.soDate) }}</td>
              </tr>
              <tr>
                <td>Terms</td>
                <td>:</td>
                <td>{{ order?.payment_terms || 'Net 30 days' }}</td>
              </tr>
              <tr>
                <td>Our Ref No.</td>
                <td>:</td>
                <td>{{ order?.poNumberCustomer }}</td>
              </tr>
              <tr>
                <td>Your Ref No.</td>
                <td>:</td>
                <td>-</td>
              </tr>
            </table>
          </div>
        </div>

        <div class="order-items">
          <table class="items-table">
            <thead>
              <tr>
                <th class="no">No.</th>
                <th class="item-code">Item Code</th>
                <th class="description">Description</th>
                <th class="qty">Qty</th>
                <th class="uom">UOM</th>
                <th class="price">U/Price</th>
                <th class="disc">Disc.</th>
                <th class="amount">Amount</th>
              </tr>
            </thead>
            <tbody>
              <!-- Regular item rows -->
              <tr v-for="(line, index) in paginatedSalesOrderLines" :key="index">
                <td class="no">{{ (currentPage - 1) * perPage + index + 1 }}</td>
                <td class="item-code">{{ line.item.itemCode }}</td>
                <td class="description">{{ line.item.name }}</td>
                <td class="qty">{{ formatNumber(line.quantity) }}</td>
                <td class="uom">{{ getUomSymbol(line.uomId) }}</td>
                <td class="price">{{ formatCurrency(line.unitPrice, order.currencyCode, true, 4) }}</td>
                <td class="disc">{{ line.discount ? formatCurrency(line.discount, order.currencyCode, true) : '0.00' }}</td>
                <td class="amount">{{ formatCurrency(line.total, order.currencyCode, true) }}</td>
              </tr>

              <!-- Total row - appears right after items -->
              <tr v-if="isLastPage" class="total-row">
                <td colspan="7" class="total-label">Total</td>
                <td class="total-value">{{ order?.currencyCode }} {{ formatCurrency(order?.totalAmount, order?.currencyCode, true) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="document-footer">
          <div class="signature-section">
            <div class="signature-box">
              <p>Authorised Signature</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination Controls -->
    <div class="pagination-controls no-print">
      <button class="btn btn-secondary" @click="prevPage" :disabled="currentPage === 1">
        &laquo; Previous
      </button>
      <span style="margin: 0 1rem;">Page {{ currentPage }} of {{ totalPages }}</span>
      <button class="btn btn-secondary" @click="nextPage" :disabled="currentPage === totalPages">
        Next &raquo;
      </button>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import axios from "axios";
import html2pdf from "html2pdf.js";

export default {
  name: "SalesOrderPrint",
  setup() {
    const router = useRouter();
    const route = useRoute();

    const order = ref(null);
    const isLoading = ref(true);
    const printSection = ref(null);
    const unitOfMeasures = ref([]);

    // Pagination state
    const currentPage = ref(1);
    const perPage = ref(10);

    // Load order data
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
        // Load unit of measures for reference
        const uomResponse = await axios.get("/unit-of-measures");
        unitOfMeasures.value = uomResponse.data.data;

        // Load order details
        const orderResponse = await axios.get(`orders/${route.params.id}`);
        console.log("Order API response data:", orderResponse.data.data);
        order.value = toCamelCase(orderResponse.data.data);
        console.log("Order description:", order.value.description);

        // Auto-print if requested in the URL
        if (route.query.autoprint === 'true') {
          setTimeout(() => {
            printDocument();
          }, 1000);
        }
      } catch (error) {
        console.error("Error loading order:", error);
        order.value = null;
      } finally {
        isLoading.value = false;
      }
    };

    // Computed paginated sales order lines
    const paginatedSalesOrderLines = computed(() => {
      if (!order.value || !order.value.salesOrderLines) return [];
      const start = (currentPage.value - 1) * perPage.value;
      return order.value.salesOrderLines.slice(start, start + perPage.value);
    });

    // Total pages
    const totalPages = computed(() => {
      if (!order.value || !order.value.salesOrderLines) return 1;
      return Math.ceil(order.value.salesOrderLines.length / perPage.value);
    });

    // Check if current page is the last page
    const isLastPage = computed(() => {
      return currentPage.value === totalPages.value;
    });

    // Format currency
    const formatCurrency = (value, currencyCode = "IDR", noSymbol = false, decimalPlaces = 2) => {
      if (!value) return "0.00";

      const formattedValue = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: decimalPlaces,
        maximumFractionDigits: decimalPlaces
      }).format(value);

      if (noSymbol) {
        return formattedValue;
      }

      return `${currencyCode || "IDR"} ${formattedValue}`;
    };

    // Format date
    const formatDate = (dateString) => {
      if (!dateString) return "-";
      const date = new Date(dateString);
      return date.toLocaleDateString('en-GB', {
        day: "2-digit",
        month: "2-digit",
        year: "numeric"
      });
    };

    // Format number
    const formatNumber = (value) => {
      if (!value) return "0";
      return new Intl.NumberFormat('en-US').format(value);
    };

    // Get UOM symbol
    const getUomSymbol = (uomId) => {
      const uom = unitOfMeasures.value.find((u) => u.uom_id === uomId);
      return uom ? uom.symbol : "-";
    };

    // Print document - Modified to maintain preview styling with smaller fonts
    const printDocument = () => {
      if (!order.value || !order.value.salesOrderLines) return;

      // Create a new window for printing
      const printWindow = window.open('', '_blank');

      // Get the current document content
      const documentElement = document.getElementById('printDocument');
      const documentHTML = documentElement.outerHTML;

      // Create the print HTML with same styling as preview
      const printHTML = `
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset="utf-8">
          <title>Sales Order - ${order.value?.soNumber || 'Document'}</title>
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

            .sales-order-document {
              width: 100%;
              min-height: 297mm;
              padding: 3rem;
              background-color: white;
              font-family: Arial, sans-serif;
              font-size: 11pt;
              box-sizing: border-box;
            }

            .document-header {
              display: flex;
              justify-content: space-between;
              margin-bottom: 2rem;
            }

            .company-info {
              flex-grow: 1;
            }

            .company-name {
              font-size: 10pt;
              font-weight: bold;
              margin: 0 0 0.5rem 0;
              letter-spacing: 0.3px;
            }

            .company-info p {
              margin: 0.1rem 0;
              font-size: 9pt;
            }

            .document-title {
              text-align: right;
            }

            .document-title h2 {
              font-size: 14pt;
              margin: 0 0 0.5rem 0;
              text-decoration: underline;
              font-weight: bold;
            }

            .document-number {
              font-weight: bold;
              margin-bottom: 0.25rem;
              font-size: 10pt;
            }

            .page-info {
              font-size: 9pt;
            }

            .document-info {
              display: flex;
              justify-content: space-between;
              margin-bottom: 2rem;
            }

            .customer-details {
              width: 60%;
            }

            .customer-name {
              font-weight: bold;
              margin-bottom: 0.5rem;
              text-transform: uppercase;
              font-size: 10pt;
            }

            .customer-address {
              font-size: 9pt;
              white-space: pre-line;
            }

            .order-details {
              width: 40%;
            }

            .info-table {
              width: 100%;
              border-collapse: collapse;
            }

            .info-table td {
              padding: 0.25rem;
              vertical-align: top;
              font-size: 9pt;
            }

            .info-table td:nth-child(2) {
              width: 10px;
              text-align: center;
            }

            .items-table {
              width: 100%;
              border-collapse: collapse;
              margin-bottom: 2rem;
            }

            .items-table th,
            .items-table td {
              border: none;
              padding: 0.5rem;
              font-size: 9pt;
            }

            .items-table thead tr {
              border-top: 1px solid #000;
              border-bottom: 1px solid #000;
            }

            .items-table th {
              background-color: transparent;
              font-weight: bold;
              text-align: center;
              padding-top: 0.75rem;
              padding-bottom: 0.75rem;
            }

            .items-table td.no,
            .items-table th.no {
              text-align: center;
              width: 5%;
            }

            .items-table td.item-code,
            .items-table th.item-code {
              width: 15%;
            }

            .items-table td.description,
            .items-table th.description {
              width: 30%;
            }

            .items-table td.qty,
            .items-table th.qty {
              text-align: right;
              width: 8%;
            }

            .items-table td.uom,
            .items-table th.uom {
              text-align: center;
              width: 7%;
            }

            .items-table td.price,
            .items-table th.price {
              text-align: right;
              width: 12%;
            }

            .items-table td.disc,
            .items-table th.disc {
              text-align: right;
              width: 8%;
            }

            .items-table td.amount,
            .items-table th.amount {
              text-align: right;
              width: 15%;
            }

            .total-row {
              border-top: 1px solid #000;
            }

            .total-row td {
              font-weight: bold;
              padding-top: 0.75rem;
            }

            .total-label {
              text-align: right;
            }

            .total-value {
              text-align: right;
            }

            .document-footer {
              position: absolute;
              bottom: 3rem;
              right: 3rem;
              width: 200px;
            }

            .signature-section {
              display: flex;
              justify-content: flex-end;
            }

            .signature-box {
              width: 200px;
              border-top: 1px solid #000;
              text-align: center;
              padding-top: 0.5rem;
              margin-top: 3rem;
            }

            .signature-box p {
              margin: 0;
              font-size: 9pt;
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
    };

    // Print PDF document
    const printPdf = async () => {
      if (isLoading.value) {
        console.warn("Data is still loading. Please wait before printing PDF.");
        return;
      }

      const container = document.createElement('div');
      container.style.position = 'static';
      container.style.width = '210mm';
      container.style.backgroundColor = 'white';
      container.style.padding = '0';
      container.style.margin = '0';

      const lines = order.value?.salesOrderLines || [];
      const totalLines = lines.length;
      const linesPerPage = perPage.value;
      const totalPagesPdf = Math.ceil(totalLines / linesPerPage);

      for (let page = 1; page <= totalPagesPdf; page++) {
        const element = document.getElementById('printDocument');
        const clonedElement = element.cloneNode(true);

        const paginationControls = clonedElement.querySelector('.pagination-controls');
        if (paginationControls) {
          paginationControls.remove();
        }

        const pageInfo = clonedElement.querySelector('.page-info');
        if (pageInfo) {
          pageInfo.textContent = `Page: ${page} of ${totalPagesPdf}`;
        }

        const tbody = clonedElement.querySelector('tbody');
        if (tbody) {
          while (tbody.firstChild) {
            tbody.removeChild(tbody.firstChild);
          }

          const startIndex = (page - 1) * linesPerPage;
          const pageLines = lines.slice(startIndex, startIndex + linesPerPage);

          pageLines.forEach((line, index) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
              <td class="no">${startIndex + index + 1}</td>
              <td class="item-code">${line.item.itemCode}</td>
              <td class="description">${line.item.name}</td>
              <td class="qty">${formatNumber(line.quantity)}</td>
              <td class="uom">${getUomSymbol(line.uomId)}</td>
              <td class="price">${formatCurrency(line.unitPrice, order.value.currencyCode, true, 4)}</td>
              <td class="disc">${line.discount ? formatCurrency(line.discount, order.value.currencyCode, true) : '0.00'}</td>
              <td class="amount">${formatCurrency(line.total, order.value.currencyCode, true)}</td>
            `;
            tbody.appendChild(tr);
          });

          // Add total row only on last page
          if (page === totalPagesPdf) {
            const totalTr = document.createElement('tr');
            totalTr.classList.add('total-row');
            totalTr.innerHTML = `
              <td colspan="7" class="total-label">Total</td>
              <td class="total-value">${order.value.currencyCode} ${formatCurrency(order.value.totalAmount, order.value.currencyCode, true)}</td>
            `;
            tbody.appendChild(totalTr);
          }
        }

        if (page < totalPagesPdf) {
          const pageBreak = document.createElement('div');
          pageBreak.style.pageBreakAfter = 'always';
          clonedElement.appendChild(pageBreak);
        }

        container.appendChild(clonedElement);
      }

      document.body.appendChild(container);

      const opt = {
        margin: 0,
        filename: `SalesOrder_${order.value?.soNumber || 'document'}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
      };

      try {
        await html2pdf().set(opt).from(container).save();
      } catch (error) {
        console.error("Error generating PDF:", error);
      } finally {
        document.body.removeChild(container);
      }
    };

    // Navigation
    const goBack = () => {
      router.push(`/sales/orders/${route.params.id}`);
    };

    // Pagination controls
    const nextPage = () => {
      if (currentPage.value < totalPages.value) {
        currentPage.value++;
      }
    };

    const prevPage = () => {
      if (currentPage.value > 1) {
        currentPage.value--;
      }
    };

    onMounted(() => {
      loadData();
    });

    return {
      order,
      isLoading,
      printSection,
      formatCurrency,
      formatDate,
      formatNumber,
      getUomSymbol,
      printDocument,
      printPdf,
      goBack,
      currentPage,
      perPage,
      paginatedSalesOrderLines,
      totalPages,
      nextPage,
      prevPage,
      isLastPage
    };
  }
};
</script>

<style>
/* Component styles */
.print-container {
  min-height: 100vh;
  background-color: #f1f5f9;
  padding: 2rem 1rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
}

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

.sales-order-document {
  width: 100%;
  min-height: 297mm;
  padding: 3rem;
  background-color: white;
  font-family: Arial, sans-serif;
  font-size: 11pt;
  box-sizing: border-box;
  position: relative;
}

.document-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 2rem;
}

.company-info {
  flex-grow: 1;
}

.company-name {
  font-size: 10pt;
  font-weight: bold;
  margin: 0 0 0.5rem 0;
  letter-spacing: 0.3px;
}

.company-info p {
  margin: 0.1rem 0;
  font-size: 9pt;
}

.document-title {
  text-align: right;
}

.document-title h2 {
  font-size: 14pt;
  margin: 0 0 0.5rem 0;
  text-decoration: underline;
  font-weight: bold;
}

.document-number {
  font-weight: bold;
  margin-bottom: 0.25rem;
  font-size: 10pt;
}

.page-info {
  font-size: 9pt;
}

.document-info {
  display: flex;
  justify-content: space-between;
  margin-bottom: 2rem;
}

.customer-details {
  width: 60%;
}

.customer-name {
  font-weight: bold;
  margin-bottom: 0.5rem;
  text-transform: uppercase;
  font-size: 10pt;
}

.customer-address {
  font-size: 9pt;
  white-space: pre-line;
}

.order-details {
  width: 40%;
}

.info-table {
  width: 100%;
  border-collapse: collapse;
}

.info-table td {
  padding: 0.25rem;
  vertical-align: top;
  font-size: 9pt;
}

.info-table td:nth-child(2) {
  width: 10px;
  text-align: center;
}

/* Items table styles - keeping original layout */
.items-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 2rem;
}

.items-table th,
.items-table td {
  border: none;
  padding: 0.5rem;
  font-size: 9pt;
}

.items-table thead tr {
  border-top: 1px solid #000;
  border-bottom: 1px solid #000;
}

.items-table th {
  background-color: transparent;
  font-weight: bold;
  text-align: center;
  padding-top: 0.75rem;
  padding-bottom: 0.75rem;
}

/* Column widths and alignments - matching original */
.items-table td.no,
.items-table th.no {
  text-align: center;
  width: 5%;
}

.items-table td.item-code,
.items-table th.item-code {
  width: 15%;
}

.items-table td.description,
.items-table th.description {
  width: 30%;
}

.items-table td.qty,
.items-table th.qty {
  text-align: right;
  width: 8%;
}

.items-table td.uom,
.items-table th.uom {
  text-align: center;
  width: 7%;
}

.items-table td.price,
.items-table th.price {
  text-align: right;
  width: 12%;
}

.items-table td.disc,
.items-table th.disc {
  text-align: right;
  width: 8%;
}

.items-table td.amount,
.items-table th.amount {
  text-align: right;
  width: 15%;
}

.items-table tr {
  page-break-inside: avoid;
  page-break-after: auto;
  break-inside: avoid;
}

/* Total row styling - appears right after items */
.total-row {
  border-top: 1px solid #000;
}

.total-row td {
  font-weight: bold;
  padding-top: 0.75rem;
}

.total-label {
  text-align: right;
}

.total-value {
  text-align: right;
}

.document-footer {
  position: absolute;
  bottom: 3rem;
  right: 3rem;
  width: 200px;
}

.signature-section {
  display: flex;
  justify-content: flex-end;
}

.signature-box {
  width: 200px;
  border-top: 1px solid #000;
  text-align: center;
  padding-top: 0.5rem;
  margin-top: 3rem;
}

.signature-box p {
  margin: 0;
  font-size: 9pt;
}

.pagination-controls {
  text-align: center;
  margin-top: 1rem;
}

/* Simplified Print media query */
@media print {
  .no-print {
    display: none !important;
  }

  @page {
    size: A4;
    margin: 0.5cm;
  }
}

@media (max-width: 768px) {
  .print-container {
    padding: 1rem;
  }

  .document-wrapper {
    width: 100%;
  }

  .sales-order-document {
    width: 100%;
    padding: 1.5rem;
  }

  .document-header,
  .document-info {
    flex-direction: column;
  }

  .document-title {
    text-align: left;
    margin-top: 1rem;
  }

  .customer-details,
  .order-details {
    width: 100%;
  }

  .items-table {
    font-size: 8pt;
  }

  .items-table th,
  .items-table td {
    padding: 0.25rem;
  }
}
</style>
