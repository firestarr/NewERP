<!-- src/views/purchasing/GoodsReceiptPrint.vue -->
<template>
  <div class="print-page" ref="printContent">
    <!-- Print Controls (hidden when printing) -->
    <div class="print-controls no-print">
      <button @click="goBack()" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back
      </button>
      <div class="print-buttons-right">
        <button @click="print()" class="btn btn-primary">
          <i class="fas fa-print"></i> Print
        </button>
        <button @click="printPDF()" class="btn btn-success">
          <i class="fas fa-file-pdf"></i> Print PDF
        </button>
      </div>
    </div>

    <!-- Receipt Content (visible when printing) -->
    <div v-if="receipt" ref="reportContent">
      <!-- Generate a page for each chunk of items -->
      <div v-for="(pageItems, pageIndex) in paginatedItems"
           :key="pageIndex"
           class="receipt-document"
           :class="{'page-break': pageIndex > 0}">
        <div class="document-content">
          <!-- Company Name -->
          <div class="company-name">PT. ARMSTRONG INDUSTRI INDONESIA</div>

          <!-- Document Title and Page Info -->
          <div class="document-header">
            <div class="header-left">
              <div class="document-title">GOODS RECEIVED NOTE No. {{ receipt.receipt_number }}</div>
            </div>
            <div class="header-right">
              <div class="page-info">Page : {{ pageIndex + 1 }} of {{ totalPages }}</div>
            </div>
          </div>

          <!-- Supplier Info Section - Only show on first page -->
          <div v-if="pageIndex === 0" class="info-container">
            <div class="supplier-section">
              <div class="supplier-label">Supplier :</div>
              <div class="supplier-info">
                <div class="supplier-name">{{ receipt.vendor?.name || 'PT BESQ SARANA ABADI' }}</div>
                <div class="supplier-address">{{ receipt.vendor?.address_line1 || 'RAWA BOGO RT 002 RW 007,' }}</div>
                <div class="supplier-address">{{ receipt.vendor?.address_line2 || 'PADURENAN, MUSTIKA JAYA' }}</div>
                <div class="supplier-city">{{ receipt.vendor?.city || 'KOTA BEKASI' }}</div>
              </div>
            </div>

            <!-- PO and Delivery Info -->
            <div class="details-section">
              <table class="details-table">
                <tr>
                  <td>Our PO No</td>
                  <td>:</td>
                  <td>{{ poNumbers || 'LC3-25-0348' }}</td>
                </tr>
                <tr>
                  <td>Our PO Date</td>
                  <td>:</td>
                  <td>{{ formatDate(poDate) || '06/05/2025' }}</td>
                </tr>
                <tr>
                  <td>Attn</td>
                  <td>:</td>
                  <td>{{ receipt.vendor?.contact_person || 'RUDINAL HABIBI' }}</td>
                </tr>
                <tr>
                  <td>TEL</td>
                  <td>:</td>
                  <td>{{ receipt.vendor?.phone || '021-29456442' }}</td>
                </tr>
                <tr>
                  <td>FAX</td>
                  <td>:</td>
                  <td>{{ receipt.vendor?.fax || '' }}</td>
                </tr>
                <tr>
                  <td>Date</td>
                  <td>:</td>
                  <td>{{ formatDate(receipt.receipt_date) || '19/05/2025' }}</td>
                </tr>
                <tr>
                  <td>Terms</td>
                  <td>:</td>
                  <td>{{ poTerms || 'Net 30 days' }}</td>
                </tr>
              </table>
            </div>
          </div>

          <!-- Repeating header for additional pages -->
          <div v-if="pageIndex > 0" class="continuation-header">
            <div class="continuation-text">Continued from previous page</div>
          </div>

          <!-- Items Table -->
          <table class="items-table">
            <thead>
              <tr class="header-row">
                <th class="column-no">No.</th>
                <th class="column-item-code">Item Code</th>
                <th class="column-description">Description</th>
                <th class="column-size">Size</th>
                <th class="column-uom">UOM</th>
                <th class="column-qty">Qty</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(line, index) in pageItems" :key="index" class="data-row">
                <td>{{ getItemNumber(pageIndex, index) }}</td>
                <td>{{ line.item_code }}</td>
                <td>{{ line.item_name }}</td>
                <td>{{ line.size || '' }}</td>
                <td>{{ line.uom_name || '' }}</td>
                <td class="text-right">{{ formatQty(line.received_quantity) }}</td>
              </tr>
              <!-- Add empty rows to fill the page if needed -->
              <tr v-for="i in getEmptyRows(pageItems.length, pageIndex)"
                  :key="`empty-${i}`"
                  class="empty-row">
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
              </tr>
            </tbody>
          </table>

          <!-- Signatures - only show on last page -->
          <div v-if="pageIndex === paginatedItems.length - 1" class="signature-row">
            <div class="signature-column">
              <div class="signer">Vediantoro</div>
              <div class="signature-role">Approved</div>
            </div>
            <div class="signature-column">
              <div class="signer">Indra Zakaria</div>
              <div class="signature-role">Checked</div>
            </div>
            <div class="signature-column">
              <div class="signer">Ayu Retno</div>
              <div class="signature-role">Created</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-else-if="loading" class="loading-state">
      <i class="fas fa-spinner fa-spin"></i>
      <p>Loading receipt details...</p>
    </div>

    <!-- Error State -->
    <div v-else class="error-state">
      <i class="fas fa-exclamation-triangle"></i>
      <p>Failed to load receipt details. Please try again.</p>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import html2pdf from 'html2pdf.js';

export default {
  name: 'GoodsReceiptPrint',
  props: {
    id: {
      type: [Number, String],
      required: true
    }
  },
  data() {
    return {
      receipt: null,
      receiptLines: [],
      poSummary: [],
      loading: true,
      error: null,
      itemsPerPage: 15, // Default items per page (can be adjusted based on content size)
    };
  },
  computed: {
    poNumbers() {
      if (!this.poSummary || this.poSummary.length === 0) {
        return null;
      }
      return this.poSummary.map(po => po.po_number).join(', ');
    },
    poDate() {
      if (!this.poSummary || this.poSummary.length === 0) {
        return null;
      }
      return this.poSummary[0].po_date;
    },
    poTerms() {
      // Default value as shown in the sample
      return 'Net 30 days';
    },
    paginatedItems() {
      // If no receipt lines, return an empty array
      if (!this.receiptLines || this.receiptLines.length === 0) {
        return [[]];
      }

      // Calculate items per page - first page has fewer items due to header space
      const firstPageItems = this.itemsPerPage - 5; // Account for header space
      const otherPagesItems = this.itemsPerPage;

      // Create paginated array
      const result = [];
      let remainingItems = [...this.receiptLines];

      // First page
      result.push(remainingItems.slice(0, firstPageItems));
      remainingItems = remainingItems.slice(firstPageItems);

      // Other pages
      while (remainingItems.length > 0) {
        result.push(remainingItems.slice(0, otherPagesItems));
        remainingItems = remainingItems.slice(otherPagesItems);
      }

      return result;
    },
    totalPages() {
      return this.paginatedItems.length;
    }
  },
  created() {
    this.fetchReceipt();
  },
  methods: {
    fetchReceipt() {
      this.loading = true;

      axios.get(`/goods-receipts/${this.id}`)
        .then(response => {
          const data = response.data.data;
          this.receipt = data.receipt;
          this.receiptLines = data.lines;
          this.poSummary = data.po_summary;

          // If there are no lines, add a default sample line
          if (!this.receiptLines || this.receiptLines.length === 0) {
            this.receiptLines = [{
              item_code: '610475400',
              item_name: 'CAP BRTH/059',
              size: 'DIA15MMX16.5MM',
              uom_name: 'PCS',
              received_quantity: 50000
            }];
          }

          // Auto print after loading if URL has print=true parameter
          if (this.$route.query.print === 'true') {
            this.$nextTick(() => {
              this.print();
            });
          }
        })
        .catch(error => {
          console.error('Error fetching receipt details:', error);
          this.error = 'Failed to load receipt details';
        })
        .finally(() => {
          this.loading = false;
        });
    },
    formatDate(dateString) {
      if (!dateString) return null;
      const date = new Date(dateString);
      return date.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      }).replace(/\//g, '/');
    },
    formatQty(number) {
      if (number === undefined || number === null) return '-';
      return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(number);
    },
    print() {
      // Print only the receipt content
      const printContents = this.$refs.reportContent.innerHTML;

      const printWindow = window.open('', '', 'height=600,width=800');
      printWindow.document.write('<html><head><title>Print Receipt</title>');

      // Include styles for print
      const styles = Array.from(document.querySelectorAll('style, link[rel="stylesheet"]'))
        .map(style => style.outerHTML)
        .join('');
      printWindow.document.write(styles);

      printWindow.document.write('</head><body>');
      printWindow.document.write(printContents);
      printWindow.document.write('</body></html>');

      printWindow.document.close();
      printWindow.focus();

      printWindow.print();
      printWindow.close();
    },
    printPDF() {
      // Clone only the report content excluding print controls
      const originalElement = this.$refs.reportContent;
      const clone = originalElement.cloneNode(true);

      // Remove print controls from the clone if any (should not be present)
      const printControls = clone.querySelector('.print-controls');
      if (printControls) {
        printControls.remove();
      }

      // Create a temporary container for the cloned content
      const container = document.createElement('div');
      container.style.position = 'fixed';
      container.style.top = '-9999px';
      container.style.left = '-9999px';
      container.appendChild(clone);
      document.body.appendChild(container);

      const opt = {
        margin:       0.5,
        filename:     `GoodsReceipt_${this.receipt ? this.receipt.receipt_number : 'unknown'}.pdf`,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2 },
        jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
      };

      html2pdf().set(opt).from(clone).save().then(() => {
        // Remove the temporary container after saving the PDF
        document.body.removeChild(container);
      });
    },
    goBack() {
      this.$router.push(`/purchasing/goods-receipts/${this.id}`);
    },
    getItemNumber(pageIndex, indexOnPage) {
      // Calculate the correct item number across all pages
      if (pageIndex === 0) {
        return indexOnPage + 1;
      } else {
        // First page has different number of items
        const firstPageItems = this.itemsPerPage - 5;
        return firstPageItems + ((pageIndex - 1) * this.itemsPerPage) + indexOnPage + 1;
      }
    },
    getEmptyRows(itemCount, pageIndex) {
      // For the first page (has header information)
      if (pageIndex === 0) {
        const firstPageRows = this.itemsPerPage - 5;
        return itemCount < firstPageRows ? firstPageRows - itemCount : 0;
      }

      // For other pages (only table content)
      return itemCount < this.itemsPerPage ? this.itemsPerPage - itemCount : 0;
    }
  }
};
</script>

<style>
/* Print-specific styles */
@media print {
  @page {
    size: A4; /* Set A4 page size */
    margin: 1cm; /* Set 1cm margins */
  }

  .no-print {
    display: none !important;
  }

  body {
    margin: 0;
    padding: 0;
    font-size: 12pt;
  }

  .print-page {
    width: 100%;
    margin: 0;
    padding: 0;
  }

  .receipt-document {
    padding: 0;
    box-shadow: none;
    border: none;
    page-break-after: always;
  }

  .receipt-document:last-child {
    page-break-after: avoid;
  }

  /* Ensure page breaks don't happen in the middle of items */
  tr {
    page-break-inside: avoid;
  }

  /* Force background colors to print */
  * {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  .page-break {
    page-break-before: always;
  }
}

/* Regular styles */
body {
  font-family: Arial, sans-serif;
  line-height: 1.4;
}

.print-page {
  width: 210mm; /* A4 width */
  margin: 0 auto;
  padding: 10px;
}

.print-controls {
  margin-bottom: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0; /* Remove uniform gap */
}

.print-controls .btn-secondary {
  margin-right: 0; /* Remove margin since space-between handles spacing */
}

.print-buttons-right {
  display: flex;
  gap: 5px;
}

.print-controls .btn-primary {
  margin-right: 0; /* Remove margin since gap handles spacing */
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 8px 16px;
  border-radius: 4px;
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
  border: none;
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

.receipt-document {
  width: 100%;
  min-height: 277mm; /* A4 height - margins */
  padding: 10px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border: 1px solid #e2e8f0;
  background-color: white;
  position: relative;
  margin-bottom: 20px;
}

.document-content {
  position: relative;
}

/* Company Name */
.company-name {
  font-size: 16px;
  font-weight: bold;
  text-align: left;
  margin-bottom: 15px;
}

/* Document Header with Title and Page Info */
.document-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 15px;
  position: relative;
}

.header-title {
  flex: 1;
  text-align: center;
}

.document-title {
  font-size: 14px;
  font-weight: bold;
}

.header-right {
  text-align: right;
}

.gr-number {
  font-size: 14px;
  font-weight: bold;
  margin-bottom: 5px;
}

.page-info {
  font-size: 12px;
}

/* Continuation header for pages after first */
.continuation-header {
  margin-bottom: 15px;
  font-style: italic;
  color: #666;
  font-size: 12px;
}

/* Info Container with Supplier and Details */
.info-container {
  display: flex;
  margin-bottom: 15px;
}

.supplier-section {
  flex: 1;
}

.supplier-label {
  font-weight: bold;
  margin-bottom: 3px;
}

.supplier-info {
  margin-bottom: 15px;
}

.supplier-name {
  font-weight: bold;
  margin-bottom: 2px;
}

.supplier-address, .supplier-city {
  font-size: 12px;
  margin-bottom: 2px;
}

.details-section {
  flex: 1;
}

.details-table {
  width: 100%;
  border-collapse: collapse;
}

.details-table td {
  padding: 2px 5px;
  font-size: 12px;
  vertical-align: top;
}

.details-table td:first-child {
  width: 90px;
  font-weight: normal;
}

.details-table td:nth-child(2) {
  width: 10px;
  text-align: center;
}

/* Items Table */
.items-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 50px;
  table-layout: auto; /* Allow table to adjust to content */
}

.items-table th, .items-table td {
  border: none; /* Remove all borders */
  padding: 5px;
  font-size: 12px;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Only add horizontal lines at the top and bottom of the table */
.items-table .header-row {
  border-top: 1px solid #000;
  border-bottom: 1px solid #000;
}

/* Remove borders between items */
.items-table tr:not(.header-row) {
  border: none;
}

/* Add a bottom border only to the last non-empty row */
.items-table tr:last-child {
  border-bottom: 1px solid #000;
}

.header-row th {
  background-color: #fff;
  font-weight: bold;
  text-align: left;
  padding-top: 8px;
  padding-bottom: 8px;
}

.column-no {
  width: 40px;
}

.column-item-code {
  width: 100px;
}

.column-description {
  width: auto; /* Allow description to take available space */
}

.column-size {
  width: 120px;
}

.column-uom {
  width: 50px;
}

.column-qty {
  width: 80px;
  text-align: right;
}

.text-right {
  text-align: right;
}

.empty-row td {
  height: 25px;
  border: none;
}

/* Signature Row */
.signature-row {
  display: flex;
  justify-content: space-between;
  margin-top: 50px;
  padding: 0 20px;
}

.signature-column {
  text-align: center;
  width: 120px;
}

.signer {
  margin-top: 40px;
  font-weight: bold;
  border-top: 1px solid #000;
  padding-top: 5px;
}

.signature-role {
  font-size: 12px;
  margin-top: 3px;
}

/* Loading and Error States */
.loading-state,
.error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 50px;
  text-align: center;
  color: #64748b;
}

.loading-state i,
.error-state i {
  font-size: 32px;
  margin-bottom: 10px;
}

.error-state i {
  color: #ef4444;
}

/* Responsive styles for smaller screens (when viewing in browser) */
@media screen and (max-width: 768px) {
  .print-page {
    width: 100%;
  }

  .info-container {
    flex-direction: column;
  }

  .supplier-section,
  .details-section {
    width: 100%;
  }

  .signature-row {
    flex-direction: column;
    align-items: center;
    gap: 30px;
  }
}
</style>
