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
          <!-- Company Name and Document Title on same line -->
          <div class="header-top">
            <div class="company-name">ARMSTRONG MIRATECH INDIA PRIVATE LIMITED</div>
            <div class="document-title">GRN No. {{ receipt.receipt_number }}</div>
          </div>

          <!-- Double horizontal lines -->
          <div class="header-lines">
            <div class="line-1"></div>
            <div class="line-2"></div>
          </div>

          <!-- Supplier Info Section - Only show on first page -->
          <div v-if="pageIndex === 0" class="info-container">
            <div class="supplier-section">
              <div class="supplier-label">Supplier No. / Name :</div>
              <div class="supplier-info">
                <div class="supplier-name">{{ receipt.vendor?.name || 'PT BESQ SARANA ABADI' }}</div>
                <!-- <div class="supplier-address">{{ receipt.vendor?.address_line1 || 'RAWA BOGO RT 002 RW 007,' }}</div>
                <div class="supplier-address">{{ receipt.vendor?.address_line2 || 'PADURENAN, MUSTIKA JAYA' }}</div>
                <div class="supplier-city">{{ receipt.vendor?.city || 'KOTA BEKASI' }}</div> -->
              </div>
            </div>

            <!-- Supplier DO No Section -->
            <div class="supplier-do-section">
              <div class="detail-label">Supplier DO No. :</div>
              <div class="detail-value">{{ receipt.supplier_do_no || '' }}</div>
            </div>

            <!-- Ref Section -->
            <div class="ref-section">
              <div class="detail-label">Ref :</div>
              <div class="detail-value">{{ receipt.ref || '' }}</div>
            </div>

            <!-- PO and Delivery Info -->
            <div class="details-section">
              <div class="detail-label">Ref Date :</div>
              <div class="detail-value">{{ formatDate(poDate) || '06/05/2025' }}</div>
            </div>
          </div>

          <!-- Repeating header for additional pages -->
          <div v-if="pageIndex > 0" class="continuation-header">
            <div class="continuation-text">Continued from previous page</div>
          </div>

          <!-- Items Table - Only shows rows with actual data -->
          <table class="items-table">
            <thead>
              <tr class="header-row">
                <th class="column-no">S/No.</th>
                <th class="column-item-code">Part No.</th>
                <th class="column-description">Description</th>
                <th class="column-po-no">PO No.</th>
                <th class="column-qty">Qty Completed</th>
                <th class="column-uom">UOM</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(line, index) in pageItems" :key="index" class="data-row">
                <td>{{ getItemNumber(pageIndex, index) }}</td>
                <td>{{ line.item_code }}</td>
                <td>{{ line.item_name }}</td>
                <td>{{ poNumbers || 'LC3-25-0348' }}</td>
                <td class="text-center">{{ formatQty(line.received_quantity) }}</td>
                <td>{{ line.uom_name || '' }}</td>
              </tr>
            </tbody>
          </table>

          <!-- Signature Section -->
          <div class="signature-section">
            <div class="signature-item">
              <div class="signature-label">
                Issued by <span class="signature-line"></span>
              </div>
              <div class="sign-text">Sign</div>
            </div>

            <div class="signature-item">
              <div class="signature-label">
                Received by <span class="signature-line"></span>
              </div>
              <div class="sign-text">Sign</div>
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
      itemsPerPage: 25, // Increased since no empty rows needed
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
      if (remainingItems.length > 0) {
        result.push(remainingItems.slice(0, firstPageItems));
        remainingItems = remainingItems.slice(firstPageItems);
      }

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
      // Langsung print halaman saat ini tanpa membuka tab baru
      // CSS @media print sudah mengatur agar hanya receipt content yang tercetak
      window.print();
    },
    printPDF() {
      // Pastikan hanya mengambil bagian reportContent untuk PDF
      const element = this.$refs.reportContent;

      if (!element) {
        console.error('Report content not found');
        return;
      }

      const opt = {
        margin: 0.5,
        filename: `GoodsReceipt_${this.receipt ? this.receipt.receipt_number : 'unknown'}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: {
          scale: 2,
          useCORS: true,
          allowTaint: true,
          scrollX: 0,
          scrollY: 0
        },
        jsPDF: {
          unit: 'in',
          format: 'a4',
          orientation: 'portrait'
        },
        pagebreak: {
          mode: ['avoid-all', 'css', 'legacy']
        }
      };

      // Generate PDF langsung dari elemen reportContent
      html2pdf()
        .set(opt)
        .from(element)
        .save()
        .catch(error => {
          console.error('Error generating PDF:', error);
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
    }
  }
};
</script>

<style>
/* Print-specific styles - ULTRA COMPACT untuk hasil seperti Image 2 */
@media print {
  /* Reset total semua elemen */
  * {
    margin: 0 !important;
    padding: 0 !important;
    box-sizing: border-box !important;
  }

  /* Sembunyikan semua elemen kecuali receipt content */
  body * {
    visibility: hidden !important;
  }

  /* Tampilkan hanya receipt content dan child elements */
  .print-page,
  .print-page *,
  .receipt-document,
  .receipt-document * {
    visibility: visible !important;
  }

  /* Pastikan print-controls dan loading/error tidak muncul */
  .print-controls,
  .no-print,
  .loading-state,
  .error-state {
    display: none !important;
    visibility: hidden !important;
  }

  /* Reset body untuk print optimal */
  body {
    margin: 0 !important;
    padding: 0 !important;
    font-family: Arial, sans-serif !important;
    font-size: 11pt !important;
    line-height: 1.1 !important;
    background: white !important;
    height: auto !important;
  }

  /* Reset print-page container */
  .print-page {
    position: static !important;
    width: 100% !important;
    max-width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    background: white !important;
    height: auto !important;
  }

  /* Optimize receipt document untuk ultra compact */
  .receipt-document {
    position: static !important;
    width: 100% !important;
    max-width: 100% !important;
    margin: 0 !important;
    padding: 10px 15px !important; /* Minimal padding */
    box-shadow: none !important;
    border: none !important;
    background: white !important;
    min-height: auto !important;
    height: auto !important;
    page-break-after: always !important;
  }

  .receipt-document:last-child {
    page-break-after: avoid !important;
  }

  /* Document content - ultra compact */
  .document-content {
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    height: auto !important;
  }

  /* Header optimization - compact */
  .header-top {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    margin-bottom: 5px !important; /* Reduced dari 8px */
    width: 100% !important;
  }

  .company-name {
    font-size: 13pt !important; /* Slightly smaller */
    font-weight: bold !important;
    margin: 0 !important;
    line-height: 1.1 !important;
  }

  .document-title {
    font-size: 11pt !important; /* Smaller */
    font-weight: bold !important;
    margin: 0 !important;
    line-height: 1.1 !important;
  }

  /* Header lines - tighter */
  .header-lines {
    margin: 3px 0 !important; /* Reduced dari 8px */
    width: 100% !important;
  }

  .line-1, .line-2 {
    width: 100% !important;
    height: 1px !important;
    border-top: 1px dashed #000 !important;
    margin-bottom: 1px !important; /* Reduced */
  }

  .line-2 {
    margin-bottom: 0 !important;
  }

  /* Info container - ultra compact */
  .info-container {
    display: flex !important;
    justify-content: space-between !important;
    margin: 6px 0 !important; /* Reduced dari 12px */
    gap: 15px !important; /* Reduced gap */
    width: 100% !important;
  }

  .supplier-section {
    flex: 2 !important;
  }

  .supplier-do-section,
  .ref-section,
  .details-section {
    flex: 1 !important;
    margin-bottom: 4px !important; /* Reduced */
  }

  .supplier-label,
  .detail-label {
    font-size: 10pt !important; /* Smaller */
    font-weight: bold !important;
    margin-bottom: 2px !important; /* Reduced */
    color: #000 !important;
    line-height: 1.1 !important;
  }

  .supplier-name,
  .detail-value {
    font-size: 10pt !important; /* Smaller */
    font-weight: normal !important;
    color: #000 !important;
    margin: 0 !important;
    padding-left: 2px !important; /* Reduced */
    line-height: 1.1 !important;
  }

  /* Table optimization - ultra compact */
  .items-table {
    width: 100% !important;
    border-collapse: collapse !important;
    margin: 6px 0 !important; /* Reduced dari 12px */
    table-layout: auto !important;
  }

  .items-table th,
  .items-table td {
    border: none !important;
    padding: 3px 4px !important; /* Reduced padding */
    font-size: 9pt !important; /* Smaller font */
    text-align: left !important;
    vertical-align: top !important;
    line-height: 1.1 !important;
  }

  /* Table header - compact */
  .items-table .header-row {
    border-top: 1px dashed #000 !important;
    border-bottom: none !important;
  }

  .items-table .header-row th {
    font-weight: bold !important;
    background: white !important;
    white-space: normal !important;
    word-wrap: break-word !important;
    font-size: 9pt !important; /* Smaller header */
  }

  /* Table body */
  .items-table tbody tr:last-child {
    border-bottom: 1px dashed #000 !important;
  }

  /* Remove alternating colors and hover effects */
  .data-row:nth-child(even),
  .data-row:hover {
    background-color: transparent !important;
  }

  /* Column alignments - compact */
  .items-table td:nth-child(1) { /* S/No */
    text-align: center !important;
    white-space: nowrap !important;
    font-size: 9pt !important;
  }

  .items-table td:nth-child(2) { /* Part No */
    text-align: left !important;
    white-space: nowrap !important;
    font-weight: 500 !important;
    font-size: 9pt !important;
  }

  .items-table td:nth-child(3) { /* Description */
    text-align: left !important;
    white-space: normal !important;
    word-wrap: break-word !important;
    font-size: 9pt !important;
  }

  .items-table td:nth-child(4) { /* PO No */
    text-align: left !important;
    white-space: nowrap !important;
    font-size: 9pt !important;
  }

  .items-table td:nth-child(5) { /* Qty */
    text-align: center !important;
    white-space: nowrap !important;
    font-weight: 500 !important;
    font-size: 9pt !important;
  }

  .items-table td:nth-child(6) { /* UOM */
    text-align: center !important;
    white-space: nowrap !important;
    font-size: 9pt !important;
  }

  /* Signature section - ultra compact */
  .signature-section {
    display: flex !important;
    justify-content: space-between !important;
    margin: 15px 0 5px 0 !important; /* Reduced margins */
    width: 100% !important;
    page-break-inside: avoid !important;
  }

  .signature-item {
    flex: 1 !important;
    max-width: 45% !important;
  }

  .signature-label {
    display: flex !important;
    align-items: center !important;
    margin-bottom: 5px !important; /* Reduced */
    font-size: 10pt !important; /* Smaller */
  }

  .signature-line {
    flex: 1 !important;
    border-bottom: 1px solid #000 !important;
    margin-left: 6px !important; /* Reduced */
    height: 1px !important;
    min-width: 120px !important; /* Reduced */
  }

  .sign-text {
    font-size: 9pt !important; /* Smaller */
    margin-top: 2px !important; /* Reduced */
    color: #000 !important;
  }

  /* Page settings - optimal */
  @page {
    size: A4 !important;
    margin: 0.3cm !important; /* Even smaller margin */
  }

  /* Page breaks */
  .page-break {
    page-break-before: always !important;
  }

  /* Prevent breaking inside important elements */
  .items-table tr,
  .signature-section {
    page-break-inside: avoid !important;
  }

  /* Continuation header for multi-page */
  .continuation-header {
    margin-bottom: 6px !important; /* Reduced */
    font-style: italic !important;
    color: #666 !important;
    font-size: 9pt !important; /* Smaller */
  }

  /* Force black color for all text */
  * {
    color: #000 !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  /* Remove any bottom margins that create whitespace */
  .receipt-document:last-child .signature-section {
    margin-bottom: 0 !important;
  }

  .receipt-document:last-child .document-content {
    padding-bottom: 0 !important;
  }

  /* Ensure no extra height */
  html, body {
    height: auto !important;
    overflow: visible !important;
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

.btn-success {
  background-color: #16a34a;
  color: white;
}

.btn-success:hover {
  background-color: #15803d;
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

/* Header Top - Company Name and Document Title on same line */
.header-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.company-name {
  font-size: 16px;
  font-weight: bold;
}

.document-title {
  font-size: 14px;
  font-weight: bold;
}

/* Double horizontal lines below header */
.header-lines {
  margin-bottom: 15px;
}

.line-1, .line-2 {
  width: 100%;
  height: 1px;
  border-top: 1px dashed #000; /* Ubah ke border dashed */
  margin-bottom: 3px;
}

.line-2 {
  margin-bottom: 0;
}

/* Continuation header for pages after first */
.continuation-header {
  margin-bottom: 15px;
  font-style: italic;
  color: #666;
  font-size: 12px;
}

/* Info Container with Supplier and Details - UPDATED */
.info-container {
  display: flex;
  margin-bottom: 20px;
  gap: 20px; /* Add consistent gap between sections */
  align-items: flex-start;
}

.supplier-section {
  flex: 2; /* Give more space to supplier section */
  min-width: 250px;
}

.supplier-label {
  font-weight: bold;
  font-size: 13px;
  margin-bottom: 5px;
  color: #333;
}

.supplier-info {
  margin-bottom: 0;
  padding-left: 5px;
}

.supplier-name {
  font-weight: bold;
  font-size: 13px;
  margin-bottom: 3px;
  color: #000;
  line-height: 1.3;
}

.supplier-address, .supplier-city {
  font-size: 12px;
  margin-bottom: 2px;
  color: #333;
  line-height: 1.2;
}

/* Right side details sections */
.supplier-do-section,
.ref-section,
.details-section {
  flex: 1;
  min-width: 150px;
  margin-bottom: 15px; /* Add consistent margin between sections */
}

/* New vertical format styles */
.detail-label {
  font-weight: bold;
  font-size: 13px;
  margin-bottom: 5px;
  color: #333;
}

.detail-value {
  font-size: 13px;
  font-weight: normal;
  color: #000;
  line-height: 1.3;
  padding-left: 5px; /* Consistent with supplier info padding */
  min-height: 18px; /* Ensure consistent height even when empty */
}

/* Keep details-table styles for any remaining table usage */
.details-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 8px;
}

.details-table td {
  padding: 3px 5px;
  font-size: 13px;
  vertical-align: top;
  line-height: 1.3;
}

.details-table td:first-child {
  width: auto;
  font-weight: 600;
  white-space: nowrap;
  min-width: fit-content;
  padding-right: 8px;
  color: #333;
}

.details-table td:nth-child(2) {
  width: 15px;
  text-align: center;
  padding: 3px 5px;
  font-weight: 600;
  color: #333;
}

.details-table td:nth-child(3) {
  padding-left: 8px;
  font-weight: normal;
  color: #000;
}

/* Items Table - Auto-sizing berdasarkan konten */
.items-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 30px; /* Reduced margin untuk signature section */
  table-layout: auto; /* Ubah dari fixed ke auto agar menyesuaikan konten */
  border-bottom: 1px dashed #000; /* Ubah ke dashed */
}

.items-table th, .items-table td {
  border: none;
  padding: 6px 8px;
  font-size: 12px;
  overflow: visible; /* Ubah dari hidden agar konten tidak terpotong */
  text-overflow: clip; /* Ubah dari ellipsis */
}

/* Header tetap bisa wrap */
.items-table th {
  white-space: normal;
  word-wrap: break-word;
}

/* Data cells dengan kontrol yang lebih fleksibel */
.items-table td {
  white-space: nowrap; /* Tetap nowrap untuk sebagian besar kolom */
}

/* Only add horizontal line at the top of the table header */
.items-table .header-row {
  border-top: 1px dashed #000; /* Ubah ke dashed */
  border-bottom: none; /* Hilangkan garis di bawah header */
}

.items-table tr:not(.header-row) {
  border: none;
}

/* Add bottom border after the table body */
.items-table tbody tr:last-child {
  border-bottom: 1px dashed #000; /* Ubah ke dashed */
}

.header-row th {
  background-color: #fff;
  font-weight: bold;
  text-align: left;
  padding: 8px 8px; /* Consistent padding with data cells */
  white-space: normal; /* Allow header text to wrap if needed */
  word-wrap: break-word;
  line-height: 1.2;
}

/* Optimized column constraints - menggunakan min/max width */
.column-no {
  min-width: 40px; /* Minimum untuk S/No */
  max-width: 60px;
  text-align: center;
  width: auto; /* Biarkan menyesuaikan konten */
}

.column-item-code {
  min-width: 80px; /* Minimum untuk Part No. */
  max-width: 120px;
  text-align: left;
  width: auto;
}

.column-description {
  min-width: 150px; /* Minimum untuk Description */
  max-width: 300px; /* Maksimum agar tidak terlalu lebar */
  text-align: left;
  white-space: normal; /* Tetap allow wrapping untuk deskripsi panjang */
  word-wrap: break-word;
  width: auto;
}

.column-po-no {
  min-width: 80px; /* Minimum untuk PO No. */
  max-width: 120px;
  text-align: left;
  width: auto;
}

.column-qty {
  min-width: 80px; /* Minimum untuk Qty */
  max-width: 120px;
  text-align: center; /* Ubah dari right ke center */
  white-space: nowrap; /* Tetap nowrap untuk angka */
  width: auto;
}

.column-uom {
  min-width: 50px; /* Minimum untuk UOM */
  max-width: 80px;
  text-align: center;
  width: auto;
}

/* Specific data cell adjustments */
.items-table td:nth-child(1) { /* S/No */
  text-align: center;
  white-space: nowrap;
}

.items-table td:nth-child(2) { /* Part No */
  text-align: left;
  font-weight: 500;
  white-space: nowrap;
}

.items-table td:nth-child(3) { /* Description */
  text-align: left;
  white-space: normal;
  word-wrap: break-word;
  line-height: 1.3;
  /* Biarkan kolom ini yang paling fleksibel */
}

.items-table td:nth-child(4) { /* PO No */
  text-align: left;
  white-space: nowrap;
}

.items-table td:nth-child(5) { /* Qty */
  text-align: center; /* Ubah dari right ke center */
  font-weight: 500;
  white-space: nowrap;
}

.items-table td:nth-child(6) { /* UOM */
  text-align: center;
  white-space: nowrap;
}

.text-right {
  text-align: right;
}

.text-center {
  text-align: center;
}

/* Signature Section */
.signature-section {
  display: flex;
  justify-content: space-between;
  margin-top: 40px;
  margin-bottom: 20px;
}

.signature-item {
  flex: 1;
  max-width: 45%;
}

.signature-label {
  display: flex;
  align-items: center;
  margin-bottom: 10px;
  font-size: 14px;
}

.signature-line {
  flex: 1;
  border-bottom: 1px solid #000;
  margin-left: 10px;
  height: 1px;
  min-width: 200px;
}

.sign-text {
  font-size: 12px;
  margin-top: 5px;
  color: #666;
}

/* Additional styling for better readability */
.data-row:nth-child(even) {
  background-color: #fafafa; /* Light gray for alternate rows */
}

.data-row:hover {
  background-color: #f0f9ff; /* Light blue on hover (won't show in print) */
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

/* Responsive styles untuk layar kecil - UPDATED */
@media screen and (max-width: 768px) {
  .print-page {
    width: 100%;
  }

  .header-top {
    flex-direction: column;
    align-items: flex-start;
    gap: 5px;
  }

  .info-container {
    flex-direction: column;
    gap: 15px;
  }

  .supplier-section,
  .supplier-do-section,
  .ref-section,
  .details-section {
    width: 100%;
    min-width: unset;
    margin-bottom: 12px; /* Consistent mobile spacing */
  }

  .supplier-label,
  .supplier-name,
  .detail-label,
  .detail-value,
  .details-table td {
    font-size: 12px; /* Slightly smaller on mobile */
  }

  .detail-value {
    padding-left: 3px; /* Tighter padding on mobile */
  }

  .details-table td:first-child {
    width: 120px;
    padding-right: 8px;
  }

  .details-table td:nth-child(3) {
    padding-left: 8px;
  }

  .items-table {
    font-size: 11px;
  }

  .items-table th, .items-table td {
    padding: 4px;
  }

  /* Adjust kolom untuk mobile */
  .column-description {
    max-width: 200px;
  }

  /* Signature section untuk mobile */
  .signature-section {
    flex-direction: column;
    gap: 20px;
  }

  .signature-item {
    max-width: 100%;
  }

  .signature-line {
    min-width: 150px;
  }
}
</style>
