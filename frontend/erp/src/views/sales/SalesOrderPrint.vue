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

    <!-- Print Content - Only this will be printed -->
    <div id="printDocument" class="sales-order-document">
      <div class="document-header">
        <div class="company-info">
          <h1 class="company-name">PT. ARMSTRONG INDUSTRI INDONESIA</h1>
          <p>EJIP Industrial park Plot1 A-3, Desa Sukaresmi</p>
          <p>Cikarang Selatan, Bekasi 17857, Indonesia</p>
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
              <td>{{ order?.quotation_id ? order?.salesQuotation?.quotation_number : '-' }}</td>
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
            <tr v-for="(line, index) in paginatedSalesOrderLines" :key="index">
              <td class="no">{{ (currentPage - 1) * perPage + index + 1 }}</td>
              <td class="item-code">{{ line.item.itemCode }}</td>
              <td class="description">{{ line.item.name }}</td>
              <td class="qty">{{ formatNumber(line.quantity) }}</td>
              <td class="uom">{{ getUomSymbol(line.uomId) }}</td>
              <td class="price">{{ formatCurrency(line.unitPrice, order.currencyCode, true, 4) }}</td>
              <td class="disc">{{ line.discount ? formatCurrency(line.discount, order.currencyCode, true) : '-' }}</td>
              <td class="amount">{{ formatCurrency(line.total, order.currencyCode, true) }}</td>
            </tr>
            <!-- Empty rows to ensure consistent spacing -->
            <tr v-for="n in (perPage - paginatedSalesOrderLines.length)" :key="`empty-${n}`" class="empty-row">
              <td class="no"></td>
              <td class="item-code"></td>
              <td class="description"></td>
              <td class="qty"></td>
              <td class="uom"></td>
              <td class="price"></td>
              <td class="disc"></td>
              <td class="amount"></td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="7" class="total-label">Total</td>
              <td class="total-value"> {{ order?.currencyCode }} {{ formatCurrency(order?.totalAmount, order?.currencyCode, true) }}</td>
            </tr>
          </tfoot>
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

    <!-- Pagination Controls -->
    <div class="pagination-controls no-print" style="text-align: center; margin-top: 1rem;">
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
        const orderResponse = await axios.get(
          `orders/${route.params.id}`
        );
        console.log("Order API response data:", orderResponse.data.data);
        order.value = toCamelCase(orderResponse.data.data);
        console.log("Order description:", order.value.description);

        // Auto-print if requested in the URL
        if (route.query.autoprint === 'true') {
          // Wait for content to load before printing
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

    // Convert number to words
    const numberToWords = (num) => {
      const ones = ['', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE', 'TEN', 'ELEVEN', 'TWELVE', 'THIRTEEN', 'FOURTEEN', 'FIFTEEN', 'SIXTEEN', 'SEVENTEEN', 'EIGHTEEN', 'NINETEEN'];
      const tens = ['', '', 'TWENTY', 'THIRTY', 'FORTY', 'FIFTY', 'SIXTY', 'SEVENTY', 'EIGHTY', 'NINETY'];

      const numString = num.toString();

      if (num < 0) return 'MINUS ' + numberToWords(Math.abs(num));

      if (num === 0) return 'ZERO';

      // For decimals
      if (numString.includes('.')) {
        const parts = numString.split('.');
        const integerPart = parseInt(parts[0]);

        // Handle the decimal part specifically for currency
        let decimalPart = parts[1];
        if (decimalPart.length === 1) decimalPart = decimalPart + '0';
        if (decimalPart.length > 2) decimalPart = decimalPart.substring(0, 2);

        const decimalWords = parseInt(decimalPart) > 0
          ? ' AND CENTS ' + numberToWords(parseInt(decimalPart)) + ' ONLY'
          : ' ONLY';

        return numberToWords(integerPart) + decimalWords;
      }

      if (num < 20) return ones[num];

      if (num < 100) {
        return tens[Math.floor(num / 10)] + (num % 10 ? ' ' + ones[num % 10] : '');
      }

      if (num < 1000) {
        return ones[Math.floor(num / 100)] + ' HUNDRED' + (num % 100 ? ' ' + numberToWords(num % 100) : '');
      }

      if (num < 1000000) {
        return numberToWords(Math.floor(num / 1000)) + ' THOUSAND' + (num % 1000 ? ' ' + numberToWords(num % 1000) : '');
      }

      if (num < 1000000000) {
        return numberToWords(Math.floor(num / 1000000)) + ' MILLION' + (num % 1000000 ? ' ' + numberToWords(num % 1000000) : '');
      }

      return numberToWords(Math.floor(num / 1000000000)) + ' BILLION' + (num % 1000000000 ? ' ' + numberToWords(num % 1000000000) : '');
    };

    // Computed property for amount in words
    const amountInWords = computed(() => {
      if (!order.value || !order.value.totalAmount) return '';
      return numberToWords(parseFloat(order.value.totalAmount));
    });

    // Format currency
    const formatCurrency = (value, currencyCode = "IDR", noSymbol = false, decimalPlaces = 2) => {
      if (!value) return "0.00";

      // Format number with specified decimal places
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
      return date.toLocaleDateString('en-US', {
        day: "2-digit",
        month: "2-digit",
        year: "numeric"
      }).replace(/\//g, '/');
    };

    // Format number
    const formatNumber = (value) => {
      if (!value) return "0";
      return new Intl.NumberFormat('en-US').format(value);
    };

    // Get currency name
    const getCurrencyName = (code) => {
      const currencies = {
        "IDR": "INDONESIAN RUPIAH",
        "USD": "US DOLLAR",
        "EUR": "EURO",
        "SGD": "SINGAPORE DOLLAR",
        "JPY": "JAPANESE YEN"
      };

      return currencies[code] || code;
    };

    // Get UOM symbol
    const getUomSymbol = (uomId) => {
      const uom = unitOfMeasures.value.find((u) => u.uom_id === uomId);
      return uom ? uom.symbol : "-";
    };

    // Print document - Modified to print all pages (all sales order lines)
    const printDocument = () => {
      if (!order.value || !order.value.salesOrderLines) return;

      // Save current page and page info text
      const originalPage = currentPage.value;
      const originalPageInfo = document.querySelector('.page-info')?.textContent;

      // Temporarily set currentPage to 1 and perPage to total lines to show all lines
      currentPage.value = 1;
      perPage.value = order.value.salesOrderLines.length;

      // Wait for DOM update
      setTimeout(() => {
        // Create a new style element
        const printStyles = document.createElement('style');

        // Add print-specific styles
        printStyles.textContent = `
          @media print {
            body * {
              visibility: hidden;
            }
            #printDocument, #printDocument * {
              visibility: visible;
            }
            #printDocument {
              position: absolute;
              left: 0;
              top: 0;
              width: 100%;
              padding: 0 !important;
              margin: 0 !important;
              box-shadow: none !important;
            }
            .pagination-controls {
              display: none !important;
            }
            @page {
              size: A4;
              margin: 0.5cm;
            }
          }
        `;

        // Add the style element to the head
        document.head.appendChild(printStyles);

        // Update page info to show all pages as 1 of 1
        const pageInfo = document.querySelector('.page-info');
        if (pageInfo) {
          pageInfo.textContent = `Page: 1 of 1`;
        }

        // Trigger the print dialog
        window.print();

        // Remove the style element after printing
        setTimeout(() => {
          document.head.removeChild(printStyles);

          // Restore original page and perPage values
          currentPage.value = originalPage;
          perPage.value = 10; // reset to default or original perPage if stored

          // Restore original page info text
          if (pageInfo) {
            pageInfo.textContent = originalPageInfo || '';
          }
        }, 1000);
      }, 100);
    };

    // Print PDF document using html2pdf.js
    const printPdf = async () => {
      if (isLoading.value) {
        console.warn("Data is still loading. Please wait before printing PDF.");
        return;
      }

      // Create a temporary container for the full document with pagination
      const container = document.createElement('div');
      container.style.position = 'static';
      container.style.top = '0';
      container.style.left = '0';
      container.style.width = '210mm'; // A4 width
      container.style.backgroundColor = 'white';
      container.style.padding = '0';
      container.style.margin = '0';
      container.style.zIndex = 'auto';

      const lines = order.value?.salesOrderLines || [];
      const totalLines = lines.length;
      const linesPerPage = perPage.value;
      const totalPagesPdf = Math.ceil(totalLines / linesPerPage);

      for (let page = 1; page <= totalPagesPdf; page++) {
        // Clone the printDocument div for each page
        const element = document.getElementById('printDocument');
        const clonedElement = element.cloneNode(true);

        // Remove pagination controls from the cloned element if present
        const paginationControls = clonedElement.querySelector('.pagination-controls');
        if (paginationControls) {
          paginationControls.remove();
        }

        // Update page info for the current page
        const pageInfo = clonedElement.querySelector('.page-info');
        if (pageInfo) {
          pageInfo.textContent = `Page: ${page} of ${totalPagesPdf}`;
        }

        // Replace the tbody content with the sales order lines for the current page
        const tbody = clonedElement.querySelector('tbody');
        if (tbody) {
          // Clear existing rows
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
              <td class="description">${line.item.description}</td>
              <td class="qty">${formatNumber(line.quantity)}</td>
              <td class="uom">${getUomSymbol(line.uomId)}</td>
              <td class="price">${formatCurrency(line.unitPrice, order.value.currencyCode, true, 4)}</td>
              <td class="disc">${line.discount ? formatCurrency(line.discount, order.value.currencyCode, true) : '-'}</td>
              <td class="amount">${formatCurrency(line.total, order.value.currencyCode, true)}</td>
            `;
            tbody.appendChild(tr);
          });

          // Add empty rows to maintain consistent spacing if needed
          const emptyRowsCount = linesPerPage - pageLines.length;
          for (let n = 0; n < emptyRowsCount; n++) {
            const emptyTr = document.createElement('tr');
            emptyTr.classList.add('empty-row');
            emptyTr.innerHTML = `
              <td class="no"></td>
              <td class="item-code"></td>
              <td class="description"></td>
              <td class="qty"></td>
              <td class="uom"></td>
              <td class="price"></td>
              <td class="disc"></td>
              <td class="amount"></td>
            `;
            tbody.appendChild(emptyTr);
          }
        }

        // Append a page break div after each page except the last
        if (page < totalPagesPdf) {
          const pageBreak = document.createElement('div');
          pageBreak.style.pageBreakAfter = 'always';
          clonedElement.appendChild(pageBreak);
        }

        container.appendChild(clonedElement);
      }

      document.body.appendChild(container);

      const opt = {
        margin:       0,
        filename:     `SalesOrder_${order.value?.soNumber || 'document'}.pdf`,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2 },
        jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
      };

      try {
        await html2pdf().set(opt).from(container).save();
      } catch (error) {
        console.error("Error generating PDF:", error);
      } finally {
        // Clean up the temporary container after saving
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
      amountInWords,
      formatCurrency,
      formatDate,
      formatNumber,
      getCurrencyName,
      getUomSymbol,
      printDocument,
      printPdf,
      goBack,
      currentPage,
      perPage,
      paginatedSalesOrderLines,
      totalPages,
      nextPage,
      prevPage
    };
  }
};
</script>

<style>
/* Component styles */
.print-container {
  padding: 2rem;
  background-color: #f1f5f9;
  min-height: 100vh;
}

.print-actions {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
  justify-content: flex-end;
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

.sales-order-document {
  width: 210mm;
  margin: 0 auto;
  padding: 3rem;
  background-color: white;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  font-family: Arial, sans-serif;
  font-size: 11pt;
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
  font-size: 18pt;
  font-weight: bold;
  margin: 0 0 0.5rem 0;
}

.company-info p {
  margin: 0.1rem 0;
  font-size: 10pt;
}

.document-title {
  text-align: right;
}

.document-title h2 {
  font-size: 16pt;
  margin: 0 0 0.5rem 0;
  text-decoration: underline;
}

.document-number {
  font-weight: bold;
  margin-bottom: 0.25rem;
}

.page-info {
  font-size: 10pt;
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
}

.customer-address {
  font-size: 10pt;
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
  font-size: 10pt;
}

.info-table td:nth-child(2) {
  width: 10px;
  text-align: center;
}

/* Updated styles for items table without borders */
.items-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 2rem;
}

/* Remove borders from all cells */
.items-table th,
.items-table td {
  border: none;
  padding: 0.5rem;
  font-size: 10pt;
}

/* Add only top and bottom borders to header */
.items-table thead tr {
  border-top: 1px solid #000;
  border-bottom: 1px solid #000;
}

/* Style header */
.items-table th {
  background-color: transparent;
  font-weight: bold;
  text-align: center;
  padding-top: 0.75rem;
  padding-bottom: 0.75rem;
}

/* Adjust column widths and alignments */
.items-table td.no {
  text-align: center;
  width: 5%;
}

.items-table td.item-code {
  width: 15%;
}

.items-table td.description {
  width: 30%;
}

.items-table td.qty {
  text-align: right;
  width: 8%;
}

.items-table td.uom {
  text-align: center;
  width: 7%;
}

.items-table td.price {
  text-align: right;
  width: 12%;
}

.items-table td.disc {
  text-align: right;
  width: 8%;
}

.items-table td.amount {
  text-align: right;
  width: 15%;
}

/* Empty rows */
.empty-row td {
  height: 1.5rem;
}

.items-table tr {
  page-break-inside: avoid;
  page-break-after: auto;
  break-inside: avoid;
}

/* Add top border to footer */
.items-table tfoot tr {
  border-top: 1px solid #000;
}

/* Footer cell styles */
.items-table tfoot td {
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
  margin-top: 2rem;
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
  margin-top: 5rem;
}

.signature-box p {
  margin: 0;
  font-size: 10pt;
}

/* Print media query for proper printing */
@media print {
  body {
    margin: 0;
    padding: 0;
    background-color: #fff;
  }

  .no-print {
    display: none !important;
  }

  .print-container {
    padding: 0;
    background-color: #fff;
  }

.sales-order-document {
  width: 100%;
  margin: 0;
  padding: 5mm;
  box-shadow: none;
  overflow: visible;
  max-width: 210mm;
}

  .document-header, .document-info {
    margin-bottom: 10mm;
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
