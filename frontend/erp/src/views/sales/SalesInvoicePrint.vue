<!-- src/views/sales/SalesInvoicePrint.vue -->
<template>
  <div class="print-container">
    <!-- Print Controls (visible on screen only) -->
    <div class="print-controls no-print">
      <button @click="goBack" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back
      </button>
      <div class="print-actions">
        <button @click="printInvoice" class="btn btn-primary">
          <i class="fas fa-print"></i> Print Invoice
        </button>
        <button @click="downloadPDF" class="btn btn-danger">
          <i class="fas fa-file-pdf"></i> Save as PDF
        </button>
      </div>
    </div>

    <!-- Loading Indicator -->
    <div v-if="loading" class="loading-indicator no-print">
      <i class="fas fa-spinner fa-spin"></i> Loading invoice data...
    </div>

    <!-- Invoice Content (for printing) -->
    <div v-else ref="invoiceDoc" class="invoice-document">
      <!-- Invoice Header -->
      <div class="header">
        <div class="company-info">
          <h1 class="company-name">PT. ARMSTRONG INDUSTRI INDONESIA</h1>
          <p>EJIP INDUSTRIAL PARK PLOT 1 A-3 LIPPO CIKARANG</p>
          <p>SUKARESMI, CIKARANG SELATAN, BEKASI 17857</p>
          <p>Phone: (021) 8974-7566</p>
        </div>
        <div class="invoice-info">
          <div class="info-row">
            <span>Page : 1 of 1</span>
          </div>
          <div class="info-row">
            <span>No. Inv. : <strong>{{ invoice.invoice_number }}</strong></span>
          </div>
          <div class="info-row">
            <span>Date. Inv. : {{ formatDateSimple(invoice.invoice_date) }}</span>
          </div>
        </div>
      </div>

      <!-- Customer Information - Modified to be side by side -->
      <div class="customer-section">
        <div class="customer-block">
          <div class="customer-title">SOLD TO :</div>
          <div v-if="invoice.customer" class="customer-details">
            <strong>{{ invoice.customer.name }}</strong><br>
            {{ invoice.customer.address }}<br>
            {{ invoice.customer.city }} {{ invoice.customer.country }} {{ invoice.customer.postal_code }}
          </div>
          <div v-else class="customer-details">
            <strong>PT. INDONESIA EPSON INDUSTRY</strong><br>
            EJIP INDUSTRIAL PARK PLOT 4E<br>
            CIKARANG SELATAN<br>
            BEKASI INDONESIA 17550
          </div>
        </div>
        <div class="customer-block">
          <div class="customer-title">GOOD DELIVERY TO :</div>
          <div v-if="invoice.customer" class="customer-details">
            <strong>{{ invoice.customer.name }}</strong><br>
            {{ invoice.customer.address }}<br>
            {{ invoice.customer.city }} {{ invoice.customer.country }} {{ invoice.customer.postal_code }}
          </div>
          <div v-else class="customer-details">
            <strong>PT. INDONESIA EPSON INDUSTRY</strong><br>
            EJIP INDUSTRIAL PARK PLOT 4E<br>
            CIKARANG SELATAN<br>
            BEKASI INDONESIA 17550
          </div>
        </div>
      </div>

      <!-- Order Information -->
      <div class="order-info">
        <table class="order-table">
          <tbody>
            <tr>
              <th>PO No.</th>
              <th>PO Date</th>
              <th>SALES PERSON</th>
              <th>DO No.</th>
              <th>SHIP VIA</th>
              <th>TERMS</th>
            </tr>
            <tr>
              <td>{{ invoice.reference || 'N/A' }}</td>
              <td>{{ formatDateSimple(invoice.invoice_date) }}</td>
              <td>SALES</td>
              <td>{{ invoice.delivery ? invoice.delivery.delivery_number : 'N/A' }}</td>
              <td>{{ invoice.delivery ? invoice.delivery.shipping_method : '' }}</td>
              <td>{{ invoice.payment_terms || 'Net 30 days' }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- CO Number -->
      <!-- <div class="co-number">
        <span>CO-NUM : {{ invoice.delivery ? invoice.delivery.sales_order_id : 'N/A' }}</span>
        <div class="part-number-col">PART NUMBER</div>
        <div class="do-remarks-col">DO REMARKS</div>
      </div> -->

      <!-- Invoice Items -->
      <table class="items-table">
        <thead>
          <tr>
            <th class="no-col">NO.</th>
            <th class="part-col">PART NUMBER</th>
            <th class="do-col">DO No.</th>
            <th class="qty-col right">QTY SHIPPED</th>
            <th class="uom-col">UOM</th>
            <th class="desc-col">DESCRIPTION</th>
            <th class="unit-price-col right">UNIT PRICE</th>
            <th class="amount-col right">AMOUNT</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(line, index) in invoiceLines" :key="line.line_id || index">
            <td class="no-col center">{{ index + 1 }}</td>
            <td class="part-col">{{ line.item ? line.item.item_code : 'N/A' }}</td>
            <td class="do-col">{{ invoice.delivery ? invoice.delivery.delivery_number : 'N/A' }}</td>
            <td class="qty-col right">{{ line.quantity }}</td>
            <td class="uom-col">{{ getUomSymbol(line) }}</td>
            <td class="desc-col">{{ line.item ? line.item.name : 'Unknown Item' }}</td>
            <td class="unit-price-col right">{{ formatNumberWithCommas(line.unit_price) }}</td>
            <td class="amount-col right">{{ formatNumberTwoDecimals(line.total) }}</td>
          </tr>
        </tbody>
      </table>

      <!-- Invoice Summary -->
      <div class="invoice-summary">
        <div class="summary-row">
          <div class="summary-label">Harga jual</div>
          <div class="summary-colon">:</div>
          <div class="summary-value">{{ formatNumberTwoDecimals(calculateSubtotal()) }}</div>
        </div>
        <div v-if="calculateTotalDiscount() > 0" class="summary-row">
          <div class="summary-label">Diskon</div>
          <div class="summary-colon">:</div>
          <div class="summary-value">{{ formatNumberTwoDecimals(calculateTotalDiscount()) }}</div>
        </div>
        <div class="summary-row">
          <div class="summary-label">Tax</div>
          <div class="summary-colon">:</div>
          <div class="summary-value">{{ invoice.tax_amount > 0 ? (getTaxRate() + '%') : '0 %' }} {{ formatNumberTwoDecimals(invoice.tax_amount || 0) }}</div>
        </div>
        <div class="summary-row">
          <div class="summary-label">TOTAL</div>
          <div class="summary-colon">:</div>
          <div class="summary-value">{{ formatNumberTwoDecimals(invoice.total_amount) }}</div>
        </div>
      </div>

      <!-- Payment Terms -->
      <div class="payment-terms">
        <h3>Terms of Payment :</h3>
        <ol>
          <li>Payment through bank is treated as settled only after clearing</li>
          <li>Interest for late payment rate 5% per month</li>
          <li>Bank DBS Indonesia<br>
              DBS Tower, 35 th floor Ciputra Word I Jakarta,<br>
              Jl. Prof Dr. Satrio Kav. 3-5 Karet Kuningan<br>
              Account No. : 030-16069-48 (IDR)<br>
              PT. ARMSTRONG INDUSTRI INDONESIA<br>
              Account No. : 030-16068-47 (USD)<br>
              Jakarta Selatan (12940)
          </li>
        </ol>
      </div>

      <!-- Signature Area -->
      <div class="signature-area">
        <div class="signature-box">
          <p>Issued By</p>
          <div class="signature-line"></div>
          <p>PT. ARMSTRONG INDUSTRI INDONESIA</p>
        </div>
        <div class="signature-box">
          <p>Received By</p>
          <div class="signature-line"></div>
          <p>{{ invoice.customer ? invoice.customer.name : 'Customer' }}</p>
        </div>
      </div>

      <!-- Invoice Footer -->
      <div class="invoice-footer">
        <p>Thank you for your business!</p>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import html2pdf from 'html2pdf.js';

export default {
  name: 'SalesInvoicePrint',
  props: {
    id: {
      type: [Number, String],
      required: true
    }
  },
  data() {
    return {
      invoice: {},
      loading: true
    };
  },
  computed: {
    invoiceLines() {
      return this.invoice.sales_invoice_lines || [];
    }
  },
  mounted() {
    this.fetchInvoice();
  },
  methods: {
    async fetchInvoice() {
      this.loading = true;
      try {
        const response = await axios.get(`/invoices/${this.id}`);
        this.invoice = response.data.data;
      } catch (error) {
        console.error('Error fetching invoice:', error);
        this.$toast.error('Failed to load invoice details');
      } finally {
        this.loading = false;
      }
    },
    formatDateSimple(dateString) {
      if (!dateString) return 'DD/MM/YYYY';
      const date = new Date(dateString);
      const day = String(date.getDate()).padStart(2, '0');
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const year = date.getFullYear();
      return `${day}/${month}/${year}`;
    },
    formatNumberWithCommas(value) {
      if (value === undefined || value === null) return '0';
      const number = parseFloat(value);
      return number.toLocaleString('en-US', { minimumFractionDigits: 5, maximumFractionDigits: 5 });
    },
    formatNumberTwoDecimals(value) {
      if (value === undefined || value === null) return '0.00';
      const number = parseFloat(value);
      return number.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    },
    calculateSubtotal() {
      return this.invoiceLines.reduce((total, line) => total + (line.subtotal || 0), 0);
    },
    calculateTotalDiscount() {
      return this.invoiceLines.reduce((total, line) => total + (line.discount || 0), 0);
    },
    getTaxRate() {
      // Calculate the tax rate as a percentage of the subtotal
      const subtotal = this.calculateSubtotal();
      if (subtotal === 0 || !this.invoice.tax_amount) return 0;
      return Math.round((this.invoice.tax_amount / subtotal) * 100);
    },
    getUomSymbol(line) {
      if (line.item && line.item.unitOfMeasure) {
        return line.item.unitOfMeasure.symbol || 'PCS';
      }
      return line.uom_id || 'PCS';
    },
    printInvoice() {
      const printContents = this.$refs.invoiceDoc.innerHTML;
      const printWindow = window.open('', '', 'width=800,height=600');
      printWindow.document.write(`
        <html>
          <head>
            <title>Print Invoice</title>
            <style>
              body {
                font-family: Arial, sans-serif;
                font-size: 12px;
                line-height: 1.4;
                background: white;
                margin: 10mm;
              }
              .header, .company-info, .invoice-info, .customer-section, .order-info, .items-table, .invoice-summary, .payment-terms, .signature-area, .invoice-footer {
                width: 100%;
              }
              table {
                width: 100%;
                border-collapse: collapse;
              }
              th, td {
                border: 1px solid #94a3b8;
                padding: 0.5rem;
                font-size: 11px;
                text-align: left;
              }
              th {
                background-color: #f1f5f9;
                font-weight: bold;
              }
              .right {
                text-align: right;
              }
              .center {
                text-align: center;
              }
            </style>
          </head>
          <body>
            ${printContents}
          </body>
        </html>
      `);
      printWindow.document.close();
      printWindow.focus();
      printWindow.print();
      printWindow.close();
    },
    downloadPDF() {
      if (html2pdf) {
        const element = this.$refs.invoiceDoc;
        const opt = {
          margin: 10,
          filename: `Invoice-${this.invoice.invoice_number}.pdf`,
          image: { type: 'jpeg', quality: 0.98 },
          html2canvas: { scale: 2 },
          jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        html2pdf().from(element).set(opt).save();
      } else {
        if (this.$toast && typeof this.$toast.error === 'function') {
          this.$toast.error('PDF generation library not available. Please include html2pdf.js');
        } else {
          alert('PDF generation library not available. Please include html2pdf.js');
        }
      }
    },
    goBack() {
      this.$router.go(-1);
    }
  }
};
</script>

<style>
/* Reset and general styles */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: Arial, sans-serif;
  font-size: 12px;
  line-height: 1.4;
  background: #f5f5f5;
}

.print-container {
  max-width: 210mm; /* A4 width */
  margin: 0 auto;
  padding: 1rem;
  background: white;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

/* Print controls */
.print-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.print-actions {
  display: flex;
  gap: 0.5rem;
}

.btn {
  padding: 0.5rem 1rem;
  border-radius: 4px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  font-weight: bold;
  border: none;
  transition: background-color 0.2s, color 0.2s;
}

.btn i {
  margin-right: 0.5rem;
}

.btn-primary {
  background: #2563eb;
  color: white;
}

.btn-primary:hover {
  background: #1d4ed8;
}

.btn-secondary {
  background: #e5e7eb;
  color: #1f2937;
}

.btn-secondary:hover {
  background: #d1d5db;
}

.btn-info {
  background: #0ea5e9;
  color: white;
}

.btn-info:hover {
  background: #0284c7;
}

.loading-indicator {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.loading-indicator i {
  margin-right: 0.5rem;
}

/* Invoice document */
.invoice-document {
  background-color: white;
  padding: 2rem;
  border: 1px solid #e5e7eb;
  border-radius: 4px;
}

/* Header section */
.header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  padding-bottom: 1rem;
}

.company-name {
  font-size: 16px;
  font-weight: bold;
  margin-bottom: 0.5rem;
  color: #1e40af;
}

.company-info p {
  font-size: 11px;
  margin: 0;
  line-height: 1.3;
}

.invoice-info {
  text-align: right;
  font-size: 11px;
}

.info-row {
  margin-bottom: 0.25rem;
}

/* Customer section - Updated to remove all borders */
.customer-section {
  display: flex;
  border: none;
  margin-bottom: 1rem;
  flex-wrap: nowrap;
  width: 100%;
}

.customer-block {
  flex: 1; /* Each block takes equal width */
  padding: 0.5rem 0.75rem;
  font-size: 11px;
}

.customer-title {
  font-weight: bold;
  margin-bottom: 0.5rem;
  font-size: 12px;
}

.customer-details {
  font-size: 11px;
  line-height: 1.4;
}

/* Order info section */
.order-info {
  margin-bottom: 0.5rem;
}

.order-table {
  width: 100%;
  border-collapse: collapse;
  border: 1px solid #94a3b8;
}

.order-table th, .order-table td {
  border: 1px solid #94a3b8;
  padding: 0.5rem;
  font-size: 11px;
  text-align: center;
}

.order-table th {
  font-weight: bold;
  background-color: #f1f5f9;
}

/* Horizontal info line with items side by side */
.info-horizontal-row {
  display: flex;
  flex-direction: row;
  align-items: center;
  margin-bottom: 1rem;
  padding: 0.5rem 0;
  font-size: 11px;
  font-weight: bold;
}

.info-item {
  flex: 1;
  margin: 0 0.5rem;
}

.info-separator {
  color: #94a3b8;
  margin: 0 0.5rem;
  font-weight: normal;
}

/* Items table - Modified to have only top border for headers */
.items-table {
  width: 100%;
  border-collapse: collapse;
  border: none;
  margin-top: 1rem;
}

.items-table th {
  border-top: 1px solid #94a3b8;
  border-bottom: 1px solid #94a3b8;
  border-left: none;
  border-right: none;
  padding: 0.5rem;
  font-size: 11px;
  background-color: #f1f5f9;
  font-weight: bold;
}

.items-table td {
  border: none;
  border-bottom: 1px solid #f1f5f9;
  padding: 0.5rem;
  font-size: 11px;
}

.no-col { width: 5%; }
.part-col { width: 12%; }
.do-col { width: 12%; }
.qty-col { width: 10%; }
.uom-col { width: 8%; }
.desc-col { width: 25%; }
.unit-price-col { width: 14%; }
.amount-col { width: 14%; }

.center { text-align: center; }
.right { text-align: right; }

/* Invoice summary */
.invoice-summary {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  margin-top: 1rem;
  width: 100%;
}

.summary-row {
  display: flex;
  align-items: center;
  margin-bottom: 0.5rem;
  width: 300px;
}

.summary-label {
  width: 100px;
  font-weight: bold;
  font-size: 12px;
}

.summary-colon {
  width: 20px;
  text-align: center;
}

.summary-value {
  flex: 1;
  text-align: right;
  font-size: 12px;
}

/* Payment terms */
.payment-terms {
  margin-top: 1.5rem;
}

.payment-terms h3 {
  font-size: 13px;
  font-weight: bold;
  margin-bottom: 0.75rem;
}

.payment-terms ol {
  padding-left: 1.5rem;
}

.payment-terms li {
  margin-bottom: 0.5rem;
  font-size: 11px;
}

/* Signature area */
.signature-area {
  display: flex;
  justify-content: space-between;
  margin-top: 3rem;
}

.signature-box {
  width: 200px;
  text-align: center;
}

.signature-line {
  height: 50px;
  border-bottom: 1px solid #94a3b8;
  margin-bottom: 0.5rem;
}

.signature-box p {
  font-size: 11px;
}

/* Invoice footer */
.invoice-footer {
  margin-top: 3rem;
  text-align: center;
  font-size: 11px;
  font-style: italic;
  color: #64748b;
}

/* Print specific styles */
@media print {
  .no-print {
    display: none !important;
  }

  .print-container {
    width: 100%;
    max-width: none;
    margin: 0;
    padding: 0;
    box-shadow: none;
    background: white;
  }

  .invoice-document {
    border: none;
    padding: 10mm;
  }

  body {
    background: white;
  }

  @page {
    size: A4;
    margin: 0;
  }
}
</style>
