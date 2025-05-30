<!-- src/views/purchasing/PurchaseOrderPrint.vue -->
<template>
  <div class="print-container">
    <div class="print-actions no-print">
      <button class="btn btn-primary" @click="printDocument">
        <i class="fas fa-print"></i> Print
      </button>
      <button class="btn btn-primary ml-2" @click="printPdf">
        <i class="fas fa-file-pdf"></i> Export PDF
      </button>
      <router-link :to="`/purchasing/orders/${purchaseOrder.po_id}`" class="btn btn-secondary ml-2">
        <i class="fas fa-arrow-left"></i> Back to Details
      </router-link>
    </div>

    <div class="purchase-order-document">
      <!-- Header Section -->
      <div class="header">
        <div class="company-info">
          <h2>PT. ARMSTRONG INDUSTRI INDONESIA</h2>
          <p>EJIP Industrial Park Plot 1 A-3</p>
          <p>Desa Sukaresmi, Cikarang Selatan</p>
          <p>Bekasi 17550, Indonesia</p>
          <p>Telp. 62-21. 8971171 - 75</p>
          <p>Fax. 62-21. 8971168</p>
        </div>
        <div class="document-title">
          <h1>PURCHASE ORDER</h1>
        </div>
      </div>

      <p class="important-note">The following number must appear on all related correspondence, shipping papers and invoices:</p>

      <div class="po-number-box">
        <div class="po-label">P.O. Number</div>
        <div class="po-value">{{ purchaseOrder.po_number }}</div>
      </div>

      <!-- Vendor and Contact Information -->
      <div class="address-section">
        <div class="vendor-address">
          <div class="section-label">TO :</div>
          <div v-if="purchaseOrder.vendor">
            <p class="company-name">{{ purchaseOrder.vendor.name }}</p>
            <p v-if="purchaseOrder.vendor.address1">{{ purchaseOrder.vendor.address1 }}</p>
            <p v-if="purchaseOrder.vendor.address2">{{ purchaseOrder.vendor.address2 }}</p>
            <p v-if="purchaseOrder.vendor.city">
              {{ purchaseOrder.vendor.city }} {{ purchaseOrder.vendor.zip_code }}
            </p>
            <p v-if="purchaseOrder.vendor.country">{{ purchaseOrder.vendor.country }}</p>
          </div>
          <div v-else>
            <p class="company-name">[Vendor Name]</p>
          </div>
        </div>
        <div class="contact-info">
          <table class="contact-table">
            <tr>
              <td>Att</td>
              <td>:</td>
              <td>{{ purchaseOrder.vendor && purchaseOrder.vendor.contact_person ? purchaseOrder.vendor.contact_person : '[Contact Person]' }}</td>
            </tr>
            <tr>
              <td>Fax</td>
              <td>:</td>
              <td>{{ purchaseOrder.vendor && purchaseOrder.vendor.fax ? purchaseOrder.vendor.fax : '[Fax Number]' }}</td>
            </tr>
            <tr>
              <td>Tel</td>
              <td>:</td>
              <td>{{ purchaseOrder.vendor && purchaseOrder.vendor.phone ? purchaseOrder.vendor.phone : '[Phone Number]' }}</td>
            </tr>
            <tr>
              <td>Vendor</td>
              <td>:</td>
              <td>{{ purchaseOrder.vendor && purchaseOrder.vendor.vendor_code ? purchaseOrder.vendor.vendor_code : '[Vendor Code]' }}</td>
            </tr>
          </table>
        </div>
      </div>

      <!-- PO Info -->
      <div class="po-details">
        <table class="po-details-table">
          <tr>
            <th>P.O. DATE</th>
            <th>REQUISITIONER</th>
            <th>SHIP VIA</th>
            <th>F.O.B. POINT</th>
            <th>TERMS</th>
          </tr>
          <tr>
            <td>{{ formatDate(purchaseOrder.po_date, 'DD/MM/YYYY') }}</td>
            <td>{{ purchaseOrder.requisitioner || 'PURCHASING DEPT' }}</td>
            <td>{{ purchaseOrder.shipping_method || 'BY SEA' }}</td>
            <td>{{ purchaseOrder.fob_point || 'EXWORK' }}</td>
            <td>{{ purchaseOrder.payment_terms || 'TT' }}</td>
          </tr>
        </table>
      </div>

      <!-- Items Section -->
      <div class="items-section">
        <table class="items-table">
          <tr>
            <th class="no-col">No</th>
            <th class="item-col">Item</th>
            <th class="desc-col">Description</th>
            <th class="qty-col">Qty</th>
            <th class="unit-col">Unit</th>
            <th class="price-col">U/ Price</th>
            <th class="total-col">Total</th>
          </tr>
          <tr v-for="(line, index) in purchaseOrder.lines" :key="index">
            <td>{{ index + 1 }}</td>
            <td>{{ line.item ? line.item.item_code : '' }}</td>
            <td>{{ line.item ? line.item.name : 'Unknown Item' }}</td>
            <td>{{ formatNumber(line.quantity) }}</td>
            <td>{{ line.unitOfMeasure ? line.unitOfMeasure.name : 'LOT' }}</td>
            <td>{{ formatCurrency(line.unit_price, true) }}</td>
            <td>{{ formatCurrency(line.subtotal, true) }}</td>
          </tr>
        </table>
      </div>

      <!-- Totals and Footer -->
      <div class="footer-section">
        <div class="delivery-notes">
          <p>Delivery Date :</p>
          <p>&nbsp;</p>
          <p>Note : Please Include certificate of analysis or </p>
          <p>inspection data in your delivery documents</p>
        </div>
        <div class="totals">
          <table class="totals-table">
            <tr>
              <td>Subtotal</td>
              <td>:</td>
              <td>{{ formatCurrency(calculateSubtotal(), true) }}</td>
            </tr>
            <tr>
              <td>Discount</td>
              <td>:</td>
              <td>{{ formatCurrency(purchaseOrder.discount_amount || 0, true) }}</td>
            </tr>
            <tr>
              <td>Sales Tax</td>
              <td>:</td>
              <td>{{ formatCurrency(purchaseOrder.tax_amount || 0, true) }}</td>
            </tr>
            <tr>
              <td>Biaya kirim</td>
              <td>:</td>
              <td>{{ formatCurrency(purchaseOrder.shipping_cost || 0, true) }}</td>
            </tr>
            <tr class="grand-total">
              <td>Grand Total</td>
              <td>:</td>
              <td>{{ formatCurrency(purchaseOrder.total_amount, true) }}</td>
            </tr>
            <tr>
              <td>Currency</td>
              <td>:</td>
              <td>{{ purchaseOrder.currency_code || 'USD' }}</td>
            </tr>
          </table>
        </div>
      </div>

      <!-- Acceptance and Terms -->
      <div class="acceptance">
        <p>Please Confirm your acceptance by signing this PO and return to us.</p>
        <p>If we have not receive your reply within 5 days, it means you accepted.</p>
      </div>

      <div class="terms-and-signatures">
        <div class="terms">
          <ol class="terms-list">
            <li>Please send two copies of your invoice.</li>
            <li>Enter this order in accordance with the prices, terms, delivery method and specification listed above.</li>
            <li>Please notify us immediately if you are unable to ship as specified.</li>
            <li>send all correspondence to : {{ purchaseOrder.contact_person || 'PURCHASING DEPT' }}</li>
            <li>Please include certificate of analysis/inspection data in your delivery documents.</li>
          </ol>
        </div>
        <div class="signatures">
          <div class="signature-box">
            <p>Accepted by :</p>
            <div class="signature-space"></div>
            <p>Pre acknowledge this PO</p>
          </div>
          <div class="signature-box">
            <p>Signed by</p>
            <div class="signature-space with-signature">
              <!-- Placeholder for signature image -->
              <div class="signature-placeholder">SIGNATURE</div>
            </div>
            <p>{{ purchaseOrder.contact_person || 'PURCHASING MANAGER' }}</p>
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
  name: 'PurchaseOrderPrint',
  data() {
    return {
      purchaseOrder: {},
      isLoading: true
    };
  },
  created() {
    const poId = this.$route.params.id;
    if (poId) {
      this.loadPurchaseOrder(poId);
    } else {
      this.$router.push('/purchasing/orders');
    }
  },
  methods: {
    async loadPurchaseOrder(poId) {
      this.isLoading = true;
      try {
        const response = await axios.get(`/purchase-orders/${poId}`);

        if (response.data.status === 'success') {
          this.purchaseOrder = response.data.data;

          // Auto-print if in auto print mode
          if (this.$route.query.autoprint === 'true') {
            setTimeout(() => {
              this.printDocument();
            }, 1000); // Small delay to ensure document is loaded
          }
        }
      } catch (error) {
        console.error('Error loading purchase order:', error);
        alert('Failed to load purchase order data');
      } finally {
        this.isLoading = false;
      }
    },
    calculateSubtotal() {
      if (!this.purchaseOrder.lines) return 0;
      return this.purchaseOrder.lines.reduce((sum, line) => sum + (line.subtotal || 0), 0);
    },
    formatDate(dateString, format = 'long') {
      if (!dateString) return '-';
      const date = new Date(dateString);

      if (format === 'DD/MM/YYYY') {
        const day = date.getDate().toString().padStart(2, '0');
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const year = date.getFullYear();
        return `${day}/${month}/${year}`;
      }

      return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    },
    formatCurrency(amount, indonesianFormat = false) {
      if (amount === null || amount === undefined) return '-';

      if (indonesianFormat) {
        // Indonesian format uses periods for thousands and comma for decimal
        const formatted = new Intl.NumberFormat('id-ID', {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2
        }).format(amount);

        // Format to match the PDF example (e.g., 29.756,00)
        return formatted.replace(',', '.').replace(/\./g, ',').replace(/,/g, '.');
      }

      return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(amount);
    },
    formatNumber(number) {
      if (number === null || number === undefined) return '-';
      return new Intl.NumberFormat('id-ID').format(number);
    },
    printDocument() {
      const printContents = this.$el.querySelector('.purchase-order-document').innerHTML;
      const printWindow = window.open('', '', 'width=800,height=600');

    printWindow.document.write(`
        <html>
          <head>
            <title>Print Purchase Order</title>
            <style>
              @page {
                size: A4;
                margin: 1cm;
              }
              body {
                font-family: Arial, sans-serif;
                font-size: 12px;
                margin: 20px;
              }
              .purchase-order-document {
                background-color: white;
                padding: 20px;
                box-shadow: none;
              }
              table {
                width: 100%;
                border-collapse: collapse;
              }
              table, th, td {
                border: 1px solid black;
              }
              th, td {
                padding: 5px;
                text-align: left;
              }
              .header {
                display: flex;
                justify-content: space-between;
                margin-bottom: 15px;
              }
              .company-info h2 {
                margin: 0 0 5px 0;
                font-size: 16px;
                font-weight: bold;
              }
              .document-title h1 {
                font-size: 20px;
                font-weight: bold;
                margin: 0;
              }
              .po-number-box {
                display: flex;
                margin-bottom: 15px;
              }
              .po-label {
                font-weight: bold;
                padding-right: 10px;
              }
              .po-value {
                font-weight: bold;
              }
              .address-section {
                display: flex;
                margin-bottom: 15px;
              }
              .vendor-address {
                width: 50%;
              }
              .section-label {
                font-weight: bold;
                margin-bottom: 5px;
              }
              .company-name {
                font-weight: bold;
                margin: 0 0 2px 0;
              }
              .contact-info {
                width: 50%;
              }
              .contact-table td:first-child {
                width: 60px;
                font-weight: bold;
              }
              .contact-table td:nth-child(2) {
                width: 20px;
                text-align: center;
              }
              .items-table th, .items-table td {
                border: 1px solid #000;
                padding: 5px;
              }
              .items-table td:nth-child(1), .items-table td:nth-child(4), .items-table td:nth-child(5) {
                text-align: center;
              }
              .items-table td:nth-child(6), .items-table td:nth-child(7) {
                text-align: right;
              }
              .footer-section {
                display: flex;
                margin-top: 15px;
              }
              .delivery-notes {
                width: 50%;
              }
              .totals {
                width: 50%;
              }
              .totals-table td:first-child {
                text-align: right;
                font-weight: bold;
              }
              .totals-table td:nth-child(2) {
                text-align: center;
                width: 20px;
              }
              .totals-table td:nth-child(3) {
                text-align: right;
              }
              .acceptance {
                margin: 15px 0;
              }
              .terms-and-signatures {
                display: flex;
              }
              .terms {
                width: 60%;
              }
              .signatures {
                width: 40%;
                display: flex;
                justify-content: space-between;
              }
              .signature-box {
                text-align: center;
                width: 48%;
              }
              .signature-space {
                height: 60px;
                border-bottom: 1px solid #000;
                margin: 10px 0;
              }
            </style>
          </head>
          <body>
            <div class="purchase-order-document">${printContents}</div>
          </body>
        </html>
      `);

      printWindow.document.close();
      printWindow.focus();
      printWindow.print();
      printWindow.close();
    },
    async printPdf() {
      if (this.isLoading) {
        console.warn("Data is still loading. Please wait before printing PDF.");
        return;
      }

      const element = this.$el.querySelector('.purchase-order-document');

      const opt = {
        margin: 10,
        filename: `PurchaseOrder_${this.purchaseOrder.po_number || 'document'}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
      };

      try {
        await html2pdf().set(opt).from(element).save();
      } catch (error) {
        console.error("Error generating PDF:", error);
      }
    }
  }
};
</script>

<style scoped>
.print-container {
  padding: 20px;
  max-width: 210mm; /* A4 width */
  margin: 0 auto;
  font-family: Arial, sans-serif;
  font-size: 12px;
}

.print-actions {
  margin-bottom: 20px;
  text-align: right;
}

.purchase-order-document {
  background-color: white;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Header */
.header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 15px;
}

.company-info {
  width: 60%;
}

.company-info h2 {
  margin: 0 0 5px 0;
  font-size: 16px;
  font-weight: bold;
}

.company-info p {
  margin: 0 0 2px 0;
  font-size: 11px;
}

.document-title {
  width: 40%;
  text-align: right;
}

.document-title h1 {
  font-size: 20px;
  font-weight: bold;
  margin: 0;
}

.important-note {
  font-size: 11px;
  margin: 5px 0;
}

/* PO Number Box */
.po-number-box {
  display: flex;
  margin-bottom: 15px;
  /* border-bottom: 1px solid #000; */
}

.po-label {
  font-weight: bold;
  padding-right: 10px;
}

.po-value {
  font-weight: bold;
}

/* Address Section */
.address-section {
  display: flex;
  margin-bottom: 15px;
}

.vendor-address {
  width: 50%;
}

.section-label {
  font-weight: bold;
  margin-bottom: 5px;
}

.company-name {
  font-weight: bold;
  margin: 0 0 2px 0;
}

.vendor-address p {
  margin: 0 0 2px 0;
}

.contact-info {
  width: 50%;
}

.contact-table {
  width: 100%;
}

.contact-table td:first-child {
  width: 60px;
  font-weight: bold;
}

.contact-table td:nth-child(2) {
  width: 20px;
  text-align: center;
}

/* PO Details */
.po-details {
  margin-bottom: 15px;
}

.po-details-table {
  width: 100%;
  border-collapse: collapse;
}

.po-details-table th, .po-details-table td {
  border: 1px solid #000;
  text-align: center;
  padding: 5px;
}

.po-details-table th {
  font-size: 11px;
  font-weight: bold;
}

/* Items Table */
.items-section {
  margin-bottom: 15px;
}

.items-table {
  width: 100%;
  border-collapse: collapse;
}

.items-table th, .items-table td {
  border: 1px solid #000;
  padding: 5px;
}

.items-table th {
  text-align: center;
  font-size: 11px;
  font-weight: bold;
}

.no-col { width: 5%; text-align: center; }
.item-col { width: 15%; }
.desc-col { width: 35%; }
.qty-col { width: 8%; text-align: center; }
.unit-col { width: 7%; text-align: center; }
.price-col { width: 15%; text-align: right; }
.total-col { width: 15%; text-align: right; }

.items-table td:nth-child(1) { text-align: center; }
.items-table td:nth-child(4) { text-align: center; }
.items-table td:nth-child(5) { text-align: center; }
.items-table td:nth-child(6) { text-align: right; }
.items-table td:nth-child(7) { text-align: right; }

/* Footer Section */
.footer-section {
  display: flex;
  margin-top: 15px;
  border-top: 1px solid #000;
  padding-top: 15px;
}

.delivery-notes {
  width: 50%;
}

.delivery-notes p {
  margin: 0 0 5px 0;
}

.totals {
  width: 50%;
}

.totals-table {
  width: 100%;
}

.totals-table td:first-child {
  text-align: right;
  font-weight: bold;
  width: 60%;
}

.totals-table td:nth-child(2) {
  text-align: center;
  width: 5%;
}

.totals-table td:nth-child(3) {
  text-align: right;
  width: 35%;
}

.grand-total {
  font-weight: bold;
}

/* Acceptance */
.acceptance {
  margin: 15px 0;
  border-top: 1px solid #000;
  padding-top: 15px;
}

.acceptance p {
  margin: 0 0 5px 0;
}

/* Terms and Signatures */
.terms-and-signatures {
  display: flex;
  margin-top: 15px;
}

.terms {
  width: 60%;
}

.terms-list {
  margin: 0;
  padding-left: 20px;
}

.terms-list li {
  margin-bottom: 5px;
  font-size: 11px;
}

.signatures {
  width: 40%;
  display: flex;
  justify-content: space-between;
}

.signature-box {
  text-align: center;
  width: 48%;
}

.signature-space {
  height: 60px;
  border-bottom: 1px solid #000;
  margin: 10px 0;
}

.with-signature {
  position: relative;
}

.signature-placeholder {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  color: #0066cc;
  font-style: italic;
}

/* Print-specific styles */
  @media print {
    .print-actions {
      display: none;
    }

    .purchase-order-document {
      box-shadow: none;
      padding: 0;
      /* Ensure page breaks inside the document */
      page-break-after: auto;
      page-break-before: auto;
      page-break-inside: avoid;
    }

    /* Avoid breaking inside header and footer sections */
    .header, .footer-section, .acceptance, .terms-and-signatures {
      page-break-inside: avoid;
    }

    /* Avoid breaking inside table rows */
    .items-table tr, .po-details-table tr, .totals-table tr {
      page-break-inside: avoid;
      page-break-after: auto;
    }

    /* Add page break after the items section if needed */
    .items-section {
      page-break-after: auto;
    }

    body {
      margin: 0;
      padding: 0;
    }

    @page {
      size: A4;
      margin: 1cm;
    }
  }
</style>
