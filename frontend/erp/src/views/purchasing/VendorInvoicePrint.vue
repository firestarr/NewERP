<!-- src/views/purchasing/VendorInvoicePrint.vue -->
<template>
  <div class="print-container">
    <!-- Print Actions - Hidden during print -->
    <div class="print-actions no-print">
      <button class="btn btn-primary" @click="printInvoice">
        <i class="fas fa-print"></i> Print Document
      </button>
      <button class="btn btn-danger" @click="saveAsPDF" style="margin-left: 0.5rem;">
        <i class="fas fa-file-pdf"></i> Save as PDF
      </button>
      <button class="btn btn-secondary" @click="goBack">
        <i class="fas fa-arrow-left"></i> Back
      </button>
    </div>

    <!-- Print Content - A4 Document (ONLY THIS PART WILL BE IN PDF) -->
    <div class="document-wrapper">
      <div id="printDocument" class="invoice-page pdf-content">
        <div class="invoice-header">
          <!-- Header Border -->
          <div class="header-border"></div>

          <div class="invoice-title">PURCHASE INVOICE</div>

          <div class="invoice-header-content">
            <!-- Vendor Information -->
            <div class="vendor-info">
              <div class="vendor-box">
                <div class="vendor-name">{{ vendor.name }}</div>
                <div>{{ vendor.address1 }}</div>
                <div v-if="vendor.address2">{{ vendor.address2 }}</div>
                <div>{{ vendor.city }}, {{ vendor.state }}</div>
                <div class="vendor-contact">
                  TEL : {{ vendor.phone }} FAX : {{ vendor.fax }}
                </div>
                <div class="vendor-contact">
                  ATTN: {{ vendor.contact_person }}
                </div>
              </div>
            </div>

            <!-- Invoice Details -->
            <div class="invoice-info">
              <table class="invoice-details">
                <tr>
                  <td class="label">No.</td>
                  <td class="colon">:</td>
                  <td class="value">{{ invoice.invoice_number }}</td>
                </tr>
                <tr>
                  <td class="label">Our GRN No.</td>
                  <td class="colon">:</td>
                  <td class="value">{{ receiptNumbers }}</td>
                </tr>
                <tr>
                  <td class="label">Terms</td>
                  <td class="colon">:</td>
                  <td class="value">Net {{ paymentTerms }} days</td>
                </tr>
                <tr>
                  <td class="label">Date</td>
                  <td class="colon">:</td>
                  <td class="value">{{ formatDate(invoice.invoice_date) }}</td>
                </tr>
                <tr>
                  <td class="label">Page</td>
                  <td class="colon">:</td>
                  <td class="value">1 of 1</td>
                </tr>
              </table>
            </div>
          </div>
        </div>

        <div class="invoice-body">
          <!-- Line Items Table -->
          <table class="line-items-table">
            <thead>
              <tr>
                <th class="no-column">No.</th>
                <th class="description-column">Description</th>
                <th class="uom-column">UOM</th>
                <th class="qty-column">Qty</th>
                <th class="price-column">Unit Price<br />{{ invoice.currency_code }}</th>
                <th class="amount-column">Amount<br />{{ invoice.currency_code }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(line, index) in invoice.lines" :key="line.line_id">
                <td class="center">{{ index + 1 }}</td>
                <td>{{ line.item ? line.item.name : 'N/A' }}</td>
                <td class="center">{{ line.item ? (line.item.unit_of_measure?.name) : 'N/A' }}</td>
                <td class="right">{{ formatNumber(line.quantity) }}</td>
                <td class="right">{{ formatNumber(line.unit_price) }}</td>
                <td class="right">{{ formatNumber(line.total) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="invoice-footer">
          <!-- Amount in Words -->
          <div class="amount-line"></div>

          <!-- Total Amount -->
          <div class="total-section">
            <div class="total-line">
              <span class="total-label">Total</span>
              <div class="total-amount-box">{{ formatCurrency(invoice.total_amount) }}</div>
            </div>
          </div>

          <!-- E & O.E -->
          <div class="disclaimer">
            E & O.E
          </div>

          <!-- Signature -->
          <div class="signature-line"></div>
          <div class="signature-text">Authorised Signature</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import html2pdf from 'html2pdf.js';

export default {
  name: 'VendorInvoicePrint',
  data() {
    return {
      invoice: {
        invoice_number: '',
        invoice_date: '',
        due_date: '',
        total_amount: 0,
        tax_amount: 0,
        status: '',
        currency_code: '',
        lines: []
      },
      vendor: {
        name: '',
        address1: '',
        address2: '',
        city: '',
        state: '',
        phone: '',
        fax: '',
        contact_person: '',
        payment_term: 30
      },
      receiptDetails: [],
      loading: true
    };
  },
  computed: {
    receiptNumbers() {
      if (!this.receiptDetails || this.receiptDetails.length === 0) {
        return 'N/A';
      }
      return this.receiptDetails.map(r => r.receipt_number).join(', ');
    },
    paymentTerms() {
      return this.vendor.payment_term || 30;
    },
    amountInWords() {
      if (!this.invoice.total_amount) return '';

      // Format currency to words - simplified version
      const currencyName = this.getCurrencyName(this.invoice.currency_code);
      const amountWords = this.numberToWords(this.invoice.total_amount);

      return `${currencyName} ${amountWords} ONLY`.toUpperCase();
    }
  },
  created() {
    this.loadInvoice();
  },
  methods: {
    async loadInvoice() {
      try {
        const invoiceId = this.$route.params.id;
        const response = await axios.get(`/vendor-invoices/${invoiceId}`);

        if (response.data.status === 'success') {
          this.invoice = response.data.data.invoice;
          this.vendor = this.invoice.vendor || {};
          this.receiptDetails = response.data.data.receipt_details || [];
        }
      } catch (error) {
        console.error('Error loading invoice:', error);
      } finally {
        this.loading = false;
      }
    },
    goBack() {
      this.$router.push(`/purchasing/vendor-invoices/${this.$route.params.id}`);
    },

    // Improved print function following SalesOrderPrint pattern
    printInvoice() {
      if (!this.invoice || !this.invoice.lines) return;

      // Create a new window for printing
      const printWindow = window.open('', '_blank');

      // Get the current document content
      const documentElement = document.getElementById('printDocument');
      const documentHTML = documentElement.outerHTML;

      // Create the print HTML with comprehensive styling
      const printHTML = `
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset="utf-8">
          <title>Purchase Invoice - ${this.invoice?.invoice_number || 'Document'}</title>
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

            .invoice-page {
              width: 100%;
              min-height: 297mm;
              margin: 0 auto;
              background-color: white;
              box-shadow: none;
              padding: 0;
              position: relative;
              display: flex;
              flex-direction: column;
              font-family: Arial, sans-serif;
              font-size: 12px;
            }

            .header-border {
              height: 1px;
              background-color: black;
              width: 100%;
              margin: 15px 0;
            }

            .invoice-header {
              padding: 20px;
            }

            .invoice-title {
              text-align: center;
              font-size: 22px;
              font-weight: bold;
              margin: 15px 0;
            }

            .invoice-header-content {
              display: flex;
              justify-content: space-between;
              margin-top: 20px;
            }

            .vendor-info {
              width: 45%;
            }

            .vendor-box {
              padding: 10px;
              font-size: 12px;
              line-height: 1.4;
            }

            .vendor-name {
              font-weight: bold;
            }

            .vendor-contact {
              margin-top: 5px;
            }

            .invoice-info {
              width: 50%;
            }

            .invoice-details {
              width: 100%;
              font-size: 12px;
            }

            .invoice-details .label {
              width: 30%;
              vertical-align: top;
              padding: 2px 0;
            }

            .invoice-details .colon {
              width: 5%;
              vertical-align: top;
              padding: 2px 0;
            }

            .invoice-details .value {
              width: 65%;
              vertical-align: top;
              padding: 2px 0;
            }

            .invoice-body {
              padding: 0 20px;
              flex-grow: 1;
            }

            .line-items-table {
              width: 100%;
              border-collapse: collapse;
              font-size: 12px;
            }

            .line-items-table th,
            .line-items-table td {
              padding: 8px;
              border: none;
            }

            .line-items-table thead tr {
              border-top: 2px solid black !important;
              border-bottom: 2px solid black !important;
            }

            .line-items-table th {
              border-top: 2px solid black !important;
              border-bottom: 2px solid black !important;
            }

            .line-items-table tbody tr {
              border-bottom: 1px solid #eee;
            }

            .line-items-table tbody tr:last-child {
              border-bottom: none;
            }

            .no-column {
              width: 5%;
            }

            .description-column {
              width: 40%;
            }

            .uom-column {
              width: 10%;
            }

            .qty-column {
              width: 10%;
            }

            .price-column {
              width: 15%;
            }

            .amount-column {
              width: 20%;
            }

            .center {
              text-align: center;
            }

            .right {
              text-align: right;
            }

            .invoice-footer {
              padding: 20px;
              margin-top: auto;
              position: relative;
            }

            .amount-line {
              width: 100%;
              height: 1px;
              background-color: black;
              margin-bottom: 5px;
            }

            .total-section {
              display: flex;
              justify-content: flex-end;
              margin-bottom: 5px;
              margin-top: 5px;
            }

            .total-line {
              display: flex;
              align-items: center;
            }

            .total-label {
              font-weight: normal;
              margin-right: 10px;
            }

            .total-amount-box {
              border: 1px solid black;
              padding: 2px 10px;
              min-width: 120px;
              text-align: right;
              font-weight: normal;
            }

            .disclaimer {
              font-size: 12px;
              margin: 0 0 40px 0;
              text-align: right;
            }

            .signature-line {
              height: 1px;
              background-color: rgb(0, 0, 0);
              width: 14%;
              margin-bottom: 5px;
            }

            .signature-text {
              font-size: 10px;
              font-weight: bold;
              text-align: start;
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

              /* Force borders to print */
              * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
                print-color-adjust: exact !important;
              }

              .line-items-table thead tr,
              .line-items-table th,
              .header-border,
              .amount-line,
              .signature-line {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
                print-color-adjust: exact !important;
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

      // Fallback in case onload doesn't fire
      setTimeout(() => {
        if (!printWindow.closed) {
          try {
            printWindow.print();
            printWindow.close();
          } catch (e) {
            console.warn('Print window may have been blocked by browser');
          }
        }
      }, 2000);
    },

    // Fixed PDF function
    async saveAsPDF() {
      if (this.loading) {
        console.warn("Data is still loading. Please wait before saving PDF.");
        return;
      }

      if (!this.invoice || !this.invoice.lines) {
        console.error("No invoice data available for PDF generation.");
        return;
      }

      // Get the print document element
      const sourceElement = document.getElementById('printDocument');
      if (!sourceElement) {
        console.error("Print document element not found.");
        return;
      }

      try {
        // Create a complete HTML document with all styles embedded
        const printHTML = this.createPrintHTML();

        // Create a temporary iframe to render the content
        const iframe = document.createElement('iframe');
        iframe.style.position = 'absolute';
        iframe.style.top = '-9999px';
        iframe.style.left = '-9999px';
        iframe.style.width = '210mm';
        iframe.style.height = '297mm';

        document.body.appendChild(iframe);

        // Write the HTML to iframe
        iframe.contentDocument.open();
        iframe.contentDocument.write(printHTML);
        iframe.contentDocument.close();

        // Wait for content to load
        await new Promise(resolve => {
          iframe.onload = resolve;
          setTimeout(resolve, 1000); // fallback timeout
        });

        // Get the content from iframe
        const iframeDocument = iframe.contentDocument.body;

        // PDF generation options
        const opt = {
          margin: [0.3, 0.3, 0.3, 0.3],
          filename: `Purchase_Invoice_${this.invoice?.invoice_number || 'document'}.pdf`,
          image: {
            type: 'jpeg',
            quality: 0.98
          },
          html2canvas: {
            scale: 2,
            useCORS: true,
            letterRendering: true,
            allowTaint: false,
            backgroundColor: '#ffffff',
            width: 794, // A4 width in pixels at 96 DPI
            height: 1123, // A4 height in pixels at 96 DPI
            scrollX: 0,
            scrollY: 0
          },
          jsPDF: {
            unit: 'mm',
            format: 'a4',
            orientation: 'portrait'
          }
        };

        // Generate PDF
        await html2pdf().set(opt).from(iframeDocument).save();
        console.log("PDF generated successfully");

        // Clean up
        document.body.removeChild(iframe);

      } catch (error) {
        console.error("Error generating PDF:", error);
        alert("Error generating PDF. Please try again.");
      }
    },

    // Create HTML with embedded styling for PDF
    createPrintHTML() {
      const sourceElement = document.getElementById('printDocument');
      const documentHTML = sourceElement.outerHTML;

      return `
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset="utf-8">
          <title>Purchase Invoice - ${this.invoice?.invoice_number || 'Document'}</title>
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
              color: black;
            }

            .invoice-page {
              width: 210mm;
              min-height: 297mm;
              margin: 0 auto;
              background-color: white;
              box-shadow: none;
              padding: 0;
              position: relative;
              display: flex;
              flex-direction: column;
              font-family: Arial, sans-serif;
              font-size: 12px;
              color: black;
            }

            .header-border {
              height: 2px !important;
              background-color: #000000 !important;
              width: 100%;
              margin: 15px 0;
              border: none;
            }

            .invoice-header {
              padding: 20px;
            }

            .invoice-title {
              text-align: center;
              font-size: 22px;
              font-weight: bold;
              margin: 15px 0;
              color: black;
            }

            .invoice-header-content {
              display: flex;
              justify-content: space-between;
              margin-top: 20px;
            }

            .vendor-info {
              width: 45%;
            }

            .vendor-box {
              padding: 10px;
              font-size: 12px;
              line-height: 1.4;
              color: black;
            }

            .vendor-name {
              font-weight: bold;
              color: black;
            }

            .vendor-contact {
              margin-top: 5px;
              color: black;
            }

            .invoice-info {
              width: 50%;
            }

            .invoice-details {
              width: 100%;
              font-size: 12px;
              border-collapse: collapse;
            }

            .invoice-details td {
              padding: 2px 0;
              color: black;
              vertical-align: top;
            }

            .invoice-details .label {
              width: 30%;
            }

            .invoice-details .colon {
              width: 5%;
            }

            .invoice-details .value {
              width: 65%;
            }

            .invoice-body {
              padding: 0 20px;
              flex-grow: 1;
            }

            .line-items-table {
              width: 100%;
              border-collapse: collapse;
              font-size: 12px;
              color: black;
            }

            .line-items-table th,
            .line-items-table td {
              padding: 8px;
              border: none;
              color: black;
            }

            .line-items-table thead tr {
              border-top: 2px solid #000000 !important;
              border-bottom: 2px solid #000000 !important;
            }

            .line-items-table th {
              border-top: 2px solid #000000 !important;
              border-bottom: 2px solid #000000 !important;
              font-weight: bold;
              color: black;
              background-color: white;
            }

            .line-items-table tbody tr {
              border-bottom: 1px solid #cccccc;
            }

            .line-items-table tbody tr:last-child {
              border-bottom: none;
            }

            .no-column {
              width: 5%;
            }

            .description-column {
              width: 40%;
            }

            .uom-column {
              width: 10%;
            }

            .qty-column {
              width: 10%;
            }

            .price-column {
              width: 15%;
            }

            .amount-column {
              width: 20%;
            }

            .center {
              text-align: center;
            }

            .right {
              text-align: right;
            }

            .invoice-footer {
              padding: 20px;
              margin-top: auto;
              position: relative;
            }

            .amount-line {
              width: 100%;
              height: 2px !important;
              background-color: #000000 !important;
              margin-bottom: 5px;
              border: none;
            }

            .total-section {
              display: flex;
              justify-content: flex-end;
              margin-bottom: 5px;
              margin-top: 5px;
            }

            .total-line {
              display: flex;
              align-items: center;
            }

            .total-label {
              font-weight: normal;
              margin-right: 10px;
              color: black;
            }

            .total-amount-box {
              border: 2px solid #000000 !important;
              padding: 2px 10px;
              min-width: 120px;
              text-align: right;
              font-weight: normal;
              color: black;
              background-color: white;
            }

            .disclaimer {
              font-size: 12px;
              margin: 0 0 40px 0;
              text-align: right;
              color: black;
            }

            .signature-line {
              height: 2px !important;
              background-color: #000000 !important;
              width: 14%;
              margin-bottom: 5px;
              border: none;
            }

            .signature-text {
              font-size: 10px;
              font-weight: bold;
              text-align: start;
              color: black;
            }

            /* Ensure all borders and lines are visible */
            .header-border,
            .amount-line,
            .signature-line,
            .line-items-table thead tr,
            .line-items-table th,
            .total-amount-box {
              -webkit-print-color-adjust: exact !important;
              color-adjust: exact !important;
              print-color-adjust: exact !important;
            }
          </style>
        </head>
        <body>
          ${documentHTML.replace(/class="invoice-page pdf-content"/, 'class="invoice-page"')}
        </body>
        </html>
      `;
    },

    formatDate(dateString) {
      if (!dateString) return '';

      const date = new Date(dateString);
      const day = date.getDate().toString().padStart(2, '0');
      const month = (date.getMonth() + 1).toString().padStart(2, '0');
      const year = date.getFullYear();

      return `${day}/${month}/${year}`;
    },
    formatNumber(value) {
      if (value === null || value === undefined) return '';

      return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(value);
    },
    formatCurrency(amount) {
      if (amount === null || amount === undefined) return '';

      return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(amount);
    },
    getCurrencyName(code) {
      const currencies = {
        'USD': 'UNITED STATES DOLLAR',
        'IDR': 'INDONESIAN RUPIAH',
        'EUR': 'EURO',
        'JPY': 'JAPANESE YEN'
      };

      return currencies[code] || code;
    },
    numberToWords(num) {
      const units = ['', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE', 'TEN', 'ELEVEN', 'TWELVE', 'THIRTEEN', 'FOURTEEN', 'FIFTEEN', 'SIXTEEN', 'SEVENTEEN', 'EIGHTEEN', 'NINETEEN'];
      const tens = ['', '', 'TWENTY', 'THIRTY', 'FORTY', 'FIFTY', 'SIXTY', 'SEVENTY', 'EIGHTY', 'NINETY'];

      function convertLessThanThousand(num) {
        if (num === 0) return '';

        if (num < 20) {
          return units[num];
        }

        if (num < 100) {
          return tens[Math.floor(num / 10)] + (num % 10 ? ' ' + units[num % 10] : '');
        }

        return units[Math.floor(num / 100)] + ' HUNDRED' + (num % 100 ? ' ' + convertLessThanThousand(num % 100) : '');
      }

      if (num === 0) return 'ZERO';

      let words = '';
      let chunk = 0;

      // Handle millions
      chunk = Math.floor(num / 1000000);
      if (chunk > 0) {
        words += convertLessThanThousand(chunk) + ' MILLION ';
        num %= 1000000;
      }

      // Handle thousands
      chunk = Math.floor(num / 1000);
      if (chunk > 0) {
        words += convertLessThanThousand(chunk) + ' THOUSAND ';
        num %= 1000;
      }

      // Handle hundreds and tens
      if (num > 0) {
        words += convertLessThanThousand(num);
      }

      // Add cents
      const cents = Math.round((num - Math.floor(num)) * 100);
      if (cents > 0) {
        words += ' AND ' + cents + '/100';
      }

      return words.trim();
    }
  }
};
</script>

<style scoped>
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

.invoice-page {
  width: 100%;
  min-height: 297mm;
  margin: 0 auto;
  background-color: white;
  box-shadow: none;
  padding: 0;
  position: relative;
  display: flex;
  flex-direction: column;
}

.header-border {
  height: 1px;
  background-color: black;
  width: 100%;
  margin: 15px 0;
}

.invoice-header {
  padding: 20px;
}

.invoice-title {
  text-align: center;
  font-size: 22px;
  font-weight: bold;
  margin: 15px 0;
}

.invoice-header-content {
  display: flex;
  justify-content: space-between;
  margin-top: 20px;
}

.vendor-info {
  width: 45%;
}

.vendor-box {
  padding: 10px;
  font-size: 12px;
  line-height: 1.4;
}

.vendor-name {
  font-weight: bold;
}

.vendor-contact {
  margin-top: 5px;
}

.invoice-info {
  width: 50%;
}

.invoice-details {
  width: 100%;
  font-size: 12px;
}

.invoice-details .label {
  width: 30%;
  vertical-align: top;
}

.invoice-details .colon {
  width: 5%;
  vertical-align: top;
}

.invoice-details .value {
  width: 65%;
  vertical-align: top;
}

.invoice-body {
  padding: 0 20px;
  flex-grow: 1;
}

.line-items-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
}

.line-items-table th,
.line-items-table td {
  padding: 8px;
  border: none;
}

.line-items-table thead tr {
  border-top: 2px solid black;
  border-bottom: 2px solid black;
}

.line-items-table th {
  border-top: 2px solid black;
  border-bottom: 2px solid black;
}

.line-items-table tbody tr {
  border-bottom: 1px solid #eee;
}

.line-items-table tbody tr:last-child {
  border-bottom: none;
}

.no-column {
  width: 5%;
}

.description-column {
  width: 40%;
}

.uom-column {
  width: 10%;
}

.qty-column {
  width: 10%;
}

.price-column {
  width: 15%;
}

.amount-column {
  width: 20%;
}

.center {
  text-align: center;
}

.right {
  text-align: right;
}

.invoice-footer {
  padding: 20px;
  margin-top: auto;
  position: relative;
}

.amount-line {
  width: 100%;
  height: 1px;
  background-color: black;
  margin-bottom: 5px;
}

.total-section {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 5px;
  margin-top: 5px;
}

.total-line {
  display: flex;
  align-items: center;
}

.total-label {
  font-weight: normal;
  margin-right: 10px;
}

.total-amount-box {
  border: 1px solid black;
  padding: 2px 10px;
  min-width: 120px;
  text-align: right;
  font-weight: normal;
}

.disclaimer {
  font-size: 12px;
  margin: 0 0 40px 0;
  text-align: right;
}

.signature-line {
  height: 1px;
  background-color: rgb(0, 0, 0);
  width: 14%;
  margin-bottom: 5px;
}

.signature-text {
  font-size: 10px;
  font-weight: bold;
  text-align: start;
}

/* PDF Content Specific Styles */
.pdf-content {
  background-color: white !important;
  color: black !important;
}

/* Ensure borders are visible in PDF */
.pdf-content .header-border,
.pdf-content .amount-line,
.pdf-content .signature-line,
.pdf-content .line-items-table thead tr,
.pdf-content .line-items-table th {
  -webkit-print-color-adjust: exact !important;
  color-adjust: exact !important;
  print-color-adjust: exact !important;
}

/* Print media query */
@media print {
  .no-print {
    display: none !important;
  }

  @page {
    size: A4;
    margin: 0.5cm;
  }

  body {
    margin: 0;
    padding: 0;
  }

  .print-container {
    padding: 0;
    background-color: white;
  }

  .invoice-page {
    box-shadow: none;
    margin: 0;
    padding: 0;
  }

  /* Force borders to print */
  * {
    -webkit-print-color-adjust: exact !important;
    color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  .line-items-table thead tr,
  .line-items-table th,
  .header-border,
  .amount-line,
  .signature-line {
    -webkit-print-color-adjust: exact !important;
    color-adjust: exact !important;
    print-color-adjust: exact !important;
  }
}

@media (max-width: 768px) {
  .print-container {
    padding: 1rem;
  }

  .document-wrapper {
    width: 100%;
  }

  .invoice-page {
    width: 100%;
    padding: 1.5rem;
  }

  .invoice-header-content {
    flex-direction: column;
  }

  .vendor-info,
  .invoice-info {
    width: 100%;
  }

  .line-items-table {
    font-size: 10px;
  }

  .line-items-table th,
  .line-items-table td {
    padding: 4px;
  }
}
</style>
