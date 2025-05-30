<!-- src/views/purchasing/VendorInvoicePrint.vue -->
<template>
  <div class="print-container">
  <div class="print-actions non-printable">
    <button @click="goBack" class="btn"><i class="fas fa-arrow-left"></i> Back</button>
    <div class="right-buttons">
      <button @click="printInvoice" class="btn btn-primary"><i class="fas fa-print"></i> Print</button>
      <button @click="saveAsPDF" class="btn btn-primary"><i class="fas fa-file-pdf"></i> Save as PDF</button>
    </div>
  </div>

    <div class="invoice-page">
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
              <td class="center">{{ line.item ? line.item.unitOfMeasure?.code : 'N/A' }}</td>
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
    printInvoice() {
      const printContents = this.$el.querySelector('.invoice-page').innerHTML;
      const printWindow = window.open('', '', 'width=800,height=600');
      printWindow.document.write(`
        <html>
          <head>
            <title>Print Invoice</title>
            <style>
              @media print {
                @page {
                  size: A4;
                  margin: 10mm;
                }
                body {
                  margin: 0;
                  padding: 0;
                  font-family: Arial, sans-serif;
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
                .line-items-table thead {
                  position: relative;
                }
                .line-items-table thead:before,
                .line-items-table thead:after {
                  content: "";
                  position: absolute;
                  left: 0;
                  right: 0;
                  height: 1px;
                  background-color: black;
                }
                .line-items-table thead:before {
                  top: 0;
                }
                .line-items-table thead:after {
                  bottom: 0;
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
              }
            </style>
          </head>
          <body>
            <div class="invoice-page">${printContents}</div>
          </body>
        </html>
      `);
      printWindow.document.close();
      printWindow.focus();
      printWindow.print();
      printWindow.close();
    },
    saveAsPDF() {
      const element = this.$el.querySelector('.invoice-page');
      const opt = {
        margin:       0.3,
        filename:     `${this.invoice.invoice_number || 'invoice'}.pdf`,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2 },
        jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
      };
      html2pdf().set(opt).from(element).save();
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
@media print {
  @page {
    size: A4;
    margin: 10mm;
  }

  .non-printable {
    display: none !important;
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
}

.print-container {
  padding: 20px;
  background-color: #f1f5f9;
}

.print-actions {
  display: flex;
  justify-content: space-between;
  margin-bottom: 20px;
}

.right-buttons {
  margin-left: auto;
  display: flex;
  gap: 10px;
}

.btn-danger {
  background-color: #dc2626;
  color: white;
  border-color: #dc2626;
}

.btn {
  padding: 8px 16px;
  border-radius: 4px;
  border: 1px solid #e2e8f0;
  background-color: white;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn-primary {
  background-color: #2563eb;
  color: white;
  border-color: #2563eb;
}

.invoice-page {
  width: 210mm;
  min-height: 297mm;
  margin: 0 auto;
  background-color: white;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
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
  /* border: 1px solid black; */
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

.line-items-table thead {
  position: relative;
}

.line-items-table thead:before,
.line-items-table thead:after {
  content: "";
  position: absolute;
  left: 0;
  right: 0;
  height: 1px;
  background-color: black;
}

.line-items-table thead:before {
  top: 0;
}

.line-items-table thead:after {
  bottom: 0;
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
</style>
