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
    <div v-else class="document-wrapper">
      <div id="invoiceDocument" ref="invoiceDoc" class="invoice-document">
        <!-- Invoice Header -->
        <div class="header">
          <div class="company-info">
            <h1 class="company-name">ARMSTRONG MIRATECH INDIA PRIVATE LIMITED</h1>
            <p>Plot No 1705, Portia Road, SRI CITY</p>
            <p>PIN:517646</p>
            <p>Phone: (021) 8974-7566</p>
          </div>
          <div class="invoice-info">
            <div class="info-row">
              <span>Page : <span class="page-number">1</span> of <span class="total-pages">1</span></span>
            </div>
            <div class="info-row">
              <span>No. Inv. : <strong>{{ invoice.invoice_number }}</strong></span>
            </div>
            <div class="info-row">
              <span>Date. Inv. : {{ formatDateSimple(invoice.invoice_date) }}</span>
            </div>
          </div>
        </div>

        <!-- Sales Invoice Title -->
        <div class="sales-invoice-title">
          <h2>INVOICE</h2>
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
              <strong>ARMSTRONG MIRATECH INDIA PRIVATE LIMITED</strong>
              <br>Plot No 1705, Portia Road, SRI CITY<br>
              PIN:517646<br>
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
                <td>{{ (invoice.po_numbers && invoice.po_numbers.length > 0) ? invoice.po_numbers[0] : 'N/A' }}</td>
                <td>{{ formatDateSimple(invoice.invoice_date) }}</td>
                <td>SALES</td>
                <td>{{ invoice.delivery ? invoice.delivery.delivery_number : 'N/A' }}</td>
                <td>{{ invoice.delivery ? invoice.delivery.shipping_method : '' }}</td>
                <td>{{ invoice.payment_terms || 'Net 30 days' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

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
            <tr v-for="(line, index) in invoiceLines" :key="line.line_id || index" class="item-row">
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
        <div class="invoice-summary page-break-avoid">
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

        <!-- Payment Terms and Signature -->
        <div class="payment-signature-section page-break-avoid">
          <div class="payment-terms">
            <h3>Terms of Payment :</h3>
            <ol>
              <li>Payment through bank is treated as settled only after clearing</li>
              <li>Interest for late payment rate 5% per month</li>
              <li>Bank DBS Indonesia<br>
                  DBS Tower, 35 th floor Ciputra Word I Jakarta,<br>
                  Jl. Prof Dr. Satrio Kav. 3-5 Karet Kuningan<br>
                  Account No. : 030-16069-48 (IDR)<br>
                  ARMSTRONG MIRATECH INDIA PRIVATE LIMITED<br>
                  Account No. : 030-16068-47 (USD)<br>
                  Jakarta Selatan (12940)
              </li>
            </ol>
          </div>
          <div class="signature-area">
            <div class="company-signature">
              <strong>PT. ARMSTRONG INDUSTRI INDONESIA</strong>
            </div>
            <div class="signature-space">
              <!-- Space for signature -->
            </div>
            <div class="signatory-name">
              <strong>Welly Wibisono</strong>
            </div>
          </div>
        </div>
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

    // Updated Print Function - Same approach as SalesOrderPrint.vue
    printInvoice() {
      if (!this.invoice || !this.invoiceLines.length) {
        console.warn("Invoice data is not ready for printing.");
        return;
      }

      // Get only the invoice document content
      const invoiceElement = this.$refs.invoiceDoc;
      if (!invoiceElement) {
        console.error("Invoice document not found");
        return;
      }

      // Create a new window for printing
      const printWindow = window.open('', '_blank');

      // Get the invoice content HTML
      const invoiceContent = invoiceElement.outerHTML;

      // Create the complete print HTML with styles - same approach as SalesOrderPrint
      const printHTML = `
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset="utf-8">
          <title>Invoice - ${this.invoice.invoice_number || 'Document'}</title>
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

            .invoice-document {
              width: 100%;
              min-height: 297mm;
              padding: 15mm;
              background-color: white;
              font-family: Arial, sans-serif;
              font-size: 12px;
              box-sizing: border-box;
              position: relative;
              padding-bottom: 120px;
            }

            .header {
              display: flex;
              justify-content: space-between;
              margin-bottom: 0.5rem;
              padding-bottom: 0.25rem;
            }

            .company-name {
              font-size: 12px !important;
              font-weight: bold;
              margin-bottom: 0.5rem;
              color: #000000 !important;
            }

            .company-info .company-name {
              font-size: 12px !important;
              font-weight: bold;
              margin-bottom: 0.5rem;
              color: #000000 !important;
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

            .sales-invoice-title {
              text-align: center;
              margin: 1rem 0;
            }

            .sales-invoice-title h2 {
              font-size: 16px;
              font-weight: bold;
              margin: 0;
              color: #000000;
            }

            .customer-section {
              display: flex;
              border: none;
              margin-bottom: 1rem;
              margin-top: 0.5rem;
              flex-wrap: nowrap;
              width: 100%;
            }

            .customer-block {
              flex: 1;
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

            .order-info {
              margin-bottom: 0.5rem;
            }

            .order-table {
              width: 100%;
              border-collapse: collapse;
              border: 1px solid #000000;
            }

            .order-table th, .order-table td {
              border: 1px solid #000000;
              padding: 0.5rem;
              font-size: 11px;
              text-align: center;
              color: #000000 !important;
            }

            .order-table th {
              font-weight: bold;
              background-color: #ffffff;
              color: #000000 !important;
            }

            .items-table {
              width: 100%;
              border-collapse: collapse;
              border: none;
              margin-top: 1rem;
              page-break-inside: auto;
            }

            .items-table thead {
              display: table-header-group;
            }

            .items-table th {
              border-top: 1px solid #000000;
              border-bottom: 1px solid #000000;
              border-left: none;
              border-right: none;
              padding: 0.5rem;
              font-size: 9px;
              background-color: #ffffff;
              font-weight: bold;
            }

            .items-table td {
              border: none;
              border-bottom: 1px solid #f1f5f9;
              padding: 0.5rem;
              font-size: 11px;
            }

            .item-row {
              page-break-inside: avoid;
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

            .invoice-summary {
              display: flex;
              flex-direction: column;
              align-items: flex-end;
              margin-top: 1rem;
              margin-bottom: 3rem;
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

            .payment-signature-section {
              display: flex;
              gap: 2rem;
              align-items: flex-start;
              position: absolute;
              bottom: 0;
              left: 0;
              right: 0;
              padding: 1rem 0;
              background: white;
            }

            .payment-terms {
              flex: 2;
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

            .signature-area {
              flex: 1;
              text-align: center;
              padding-top: 0.5rem;
            }

            .company-signature {
              font-size: 12px;
              margin-bottom: 3rem;
            }

            .signature-space {
              height: 3rem;
              border-bottom: 1px solid #000;
              margin-bottom: 0.5rem;
            }

            .signatory-name {
              font-size: 12px;
            }

            .page-break-avoid {
              page-break-inside: avoid;
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
            ${invoiceContent}
          </div>
        </body>
        </html>
      `;

      // Write HTML to new window and print - same as SalesOrderPrint
      printWindow.document.write(printHTML);
      printWindow.document.close();

      // Wait for content to load, then print
      printWindow.onload = () => {
        setTimeout(() => {
          printWindow.print();
          printWindow.close();
        }, 500);
      };
    },

    // Updated PDF Function - Same approach as SalesOrderPrint.vue
    async downloadPDF() {
      if (this.loading) {
        console.warn("Data is still loading. Please wait before generating PDF.");
        return;
      }

      if (!this.invoice || !this.invoiceLines.length) {
        console.warn("Invoice data is not ready for PDF generation.");
        return;
      }

      // Same approach as SalesOrderPrint - create temporary container
      const container = document.createElement('div');
      container.style.position = 'static';
      container.style.width = '210mm';
      container.style.backgroundColor = 'white';
      container.style.padding = '0';
      container.style.margin = '0';

      // Get the invoice document element
      const element = document.getElementById('invoiceDocument');
      const clonedElement = element.cloneNode(true);

      // Append to container
      container.appendChild(clonedElement);

      // Add container to body temporarily
      document.body.appendChild(container);

      // Same PDF options as SalesOrderPrint
      const opt = {
        margin: 0,
        filename: `Invoice_${this.invoice.invoice_number || 'document'}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
      };

      try {
        await html2pdf().set(opt).from(container).save();
        console.log("PDF generated successfully");
      } catch (error) {
        console.error("Error generating PDF:", error);
        if (this.$toast && typeof this.$toast.error === 'function') {
          this.$toast.error('Failed to generate PDF. Please try again.');
        } else {
          alert('Failed to generate PDF. Please try again.');
        }
      } finally {
        // Remove temporary container
        if (document.body.contains(container)) {
          document.body.removeChild(container);
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
  min-height: 297mm; /* A4 height */
  margin: 0 auto;
  padding: 1rem;
  background: #f1f5f9;
  display: flex;
  flex-direction: column;
  align-items: center;
}

/* Print controls */
.print-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  width: 100%;
  max-width: 210mm;
}

.print-actions {
  display: flex;
  gap: 0.5rem;
}

.btn {
  padding: 0.625rem 1rem;
  border-radius: 4px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  font-weight: 500;
  border: none;
  transition: background-color 0.2s, color 0.2s;
  font-size: 0.875rem;
}

.btn i {
  margin-right: 0.5rem;
}

.btn-primary {
  background: #059669;
  color: white;
}

.btn-primary:hover {
  background: #047857;
}

.btn-secondary {
  background: #e2e8f0;
  color: #1e293b;
}

.btn-secondary:hover {
  background: #cbd5e1;
}

.btn-danger {
  background: #ef4444;
  color: white;
}

.btn-danger:hover {
  background: #dc2626;
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

/* Document wrapper for A4 sizing */
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

/* Invoice document */
.invoice-document {
  background-color: white;
  padding: 15mm; /* A4 margin */
  min-height: calc(297mm - 2rem); /* A4 height minus container padding */
  width: 100%;
  position: relative;
  padding-bottom: 120px; /* Space for footer section */
  box-sizing: border-box;
}

/* Header section */
.header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
  padding-bottom: 0.25rem;
}

/* Company name */
.company-name {
  font-size: 12px !important;
  font-weight: bold;
  margin-bottom: 0.5rem;
  color: #000000 !important;
}

.company-info .company-name {
  font-size: 12px !important;
  font-weight: bold;
  margin-bottom: 0.5rem;
  color: #000000 !important;
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

/* Sales Invoice Title */
.sales-invoice-title {
  text-align: center;
  margin: 1rem 0;
}

.sales-invoice-title h2 {
  font-size: 14px;
  font-weight: bold;
  margin: 0;
  color: #000000;
  letter-spacing: 1px;
}

/* Customer section */
.customer-section {
  display: flex;
  border: none;
  margin-bottom: 1rem;
  margin-top: 0.5rem;
  flex-wrap: nowrap;
  width: 100%;
}

.customer-block {
  flex: 1;
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
  border: 1px solid #000000;
}

.order-table th, .order-table td {
  border: 1px solid #000000;
  padding: 0.5rem;
  font-size: 11px;
  text-align: center;
  color: #000000 !important; /* Force black color */
}

.order-table th {
  font-weight: bold;
  background-color: #ffffff;
  color: #000000 !important; /* Force black color for headers */
}

/* Items table */
.items-table {
  width: 100%;
  border-collapse: collapse;
  border: none;
  margin-top: 1rem;
  page-break-inside: auto;
}

.items-table thead {
  display: table-header-group; /* Repeat header on each page */
}

.items-table th {
  border-top: 1px solid #000000;
  border-bottom: 1px solid #000000;
  border-left: none;
  border-right: none;
  padding: 0.5rem;
  font-size: 9px;
  background-color: #ffffff;
  font-weight: bold;
}

.items-table td {
  border: none;
  border-bottom: 1px solid #f1f5f9;
  padding: 0.5rem;
  font-size: 11px;
}

.item-row {
  page-break-inside: avoid; /* Avoid breaking rows */
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
  margin-bottom: 3rem;
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

/* Payment terms and signature section */
.payment-signature-section {
  display: flex;
  gap: 2rem;
  align-items: flex-start;
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 1rem 0;
  background: white;
}

.payment-terms {
  flex: 2;
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

.signature-area {
  flex: 1;
  text-align: center;
  padding-top: 0.5rem;
}

.company-signature {
  font-size: 12px;
  margin-bottom: 3rem;
}

.signature-space {
  height: 3rem;
  border-bottom: 1px solid #000;
  margin-bottom: 0.5rem;
}

.signatory-name {
  font-size: 12px;
}

/* Page break utilities */
.page-break-before {
  page-break-before: always;
}

.page-break-after {
  page-break-after: always;
}

.page-break-avoid {
  page-break-inside: avoid;
}

/* Print specific styles */
@media print {
  .no-print {
    display: none !important;
  }

  @page {
    size: A4;
    margin: 15mm;
  }

  body {
    background: white;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  .print-container {
    width: 100%;
    max-width: none;
    margin: 0;
    padding: 0;
    box-shadow: none;
    background: white;
    min-height: auto;
  }

  .document-wrapper {
    box-shadow: none;
    border: none;
  }

  .invoice-document {
    border: none;
    padding: 0;
    width: 100%;
    min-height: auto;
  }

  .items-table {
    page-break-inside: auto;
  }

  .items-table thead {
    display: table-header-group;
  }

  .item-row {
    page-break-inside: avoid;
  }

  .invoice-summary {
    page-break-inside: avoid;
    margin-bottom: 2rem;
  }

  .payment-signature-section {
    position: fixed;
    bottom: 15mm;
    left: 15mm;
    right: 15mm;
    page-break-inside: avoid;
    padding-top: 1rem;
    background: white;
  }

  .payment-terms,
  .signature-area {
    page-break-inside: avoid;
  }

  /* Remove forced page break */
  .payment-signature-section {
    page-break-before: avoid;
  }

  /* Ensure black text and borders in print */
  .order-table {
    border: 1px solid #000000 !important;
  }

  .order-table th, .order-table td {
    border: 1px solid #000000 !important;
    color: #000000 !important;
  }

  .order-table th {
    background-color: #ffffff !important;
  }
}

/* Screen view adjustments */
@media screen {
  .invoice-document {
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
  }

  .payment-signature-section {
    position: absolute;
    bottom: 15mm;
    left: 15mm;
    right: 15mm;
  }
}

/* Responsive design for smaller screens */
@media (max-width: 768px) {
  .print-container {
    padding: 1rem;
  }

  .document-wrapper {
    width: 100%;
  }

  .invoice-document {
    width: 100%;
    padding: 1.5rem;
  }

  .header,
  .customer-section {
    flex-direction: column;
  }

  .invoice-info {
    text-align: left;
    margin-top: 1rem;
  }

  .customer-block {
    width: 100%;
    padding: 0.5rem 0;
  }

  .items-table {
    font-size: 10px;
  }

  .items-table th,
  .items-table td {
    padding: 0.25rem;
  }

  .payment-signature-section {
    flex-direction: column;
    gap: 1rem;
  }

  .payment-terms,
  .signature-area {
    width: 100%;
  }
}
</style>
