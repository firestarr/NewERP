<!-- src/views/purchasing/PurchaseOrderPrint.vue -->
<template>
  <div class="print-container">
    <div class="print-actions no-print">
      <button class="btn btn-primary" @click="printDocument">
        <i class="fas fa-print"></i> Print Document
      </button>
      <button class="btn btn-danger" @click="printPdf" style="margin-left: 0.5rem;">
        <i class="fas fa-file-pdf"></i> Save as PDF
      </button>
      <router-link :to="`/purchasing/orders/${purchaseOrder.po_id}`" class="btn btn-secondary" style="margin-left: 0.5rem;">
        <i class="fas fa-arrow-left"></i> Back
      </router-link>
    </div>

    <!-- Print Content - A4 Document with Original Layout -->
    <div class="document-wrapper">
      <div id="printDocument" class="purchase-order-document">
        <!-- Main Content Wrapper -->
        <div class="main-content">
          <!-- Header Section -->
          <div class="header">
            <div class="company-info">
              <h2>ARMSTRONG MIRATECH INDIA PRIVATE LIMITED</h2>
              <p>Plot No 1705, Portia Road, SRI CITY</p>
              <p>PIN:517646</p>
              <!-- <p>Telp. 62-21. 8971171 - 73 &nbsp;&nbsp;&nbsp; Fax. 62-21. 8971168</p> -->
            </div>
            <div class="document-title">
              <h1>PURCHASE ORDER</h1>
            </div>
          </div>

          <p class="important-note">The following number must appear on all related correspondence, shipping papers and invoices:</p>

          <div class="po-number-section">
            <strong>P.O. Number : {{ purchaseOrder.po_number }}</strong>
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
                  {{ purchaseOrder.vendor.city }}
                </p>
                <p v-if="purchaseOrder.vendor.state">{{ purchaseOrder.vendor.state }}</p>
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
                  <td>{{ purchaseOrder.vendor && purchaseOrder.vendor.contact_person ? purchaseOrder.vendor.contact_person : 'MS. SAGA SHIZUE' }}</td>
                </tr>
                <tr>
                  <td>Fax</td>
                  <td>:</td>
                  <td>{{ purchaseOrder.vendor && purchaseOrder.vendor.fax ? purchaseOrder.vendor.fax : '84-2413734028' }}</td>
                </tr>
                <tr>
                  <td>Tel</td>
                  <td>:</td>
                  <td>{{ purchaseOrder.vendor && purchaseOrder.vendor.phone ? purchaseOrder.vendor.phone : '84-2413734024' }}</td>
                </tr>
                <tr>
                  <td>Vendor</td>
                  <td>:</td>
                  <td>{{ purchaseOrder.vendor && purchaseOrder.vendor.vendor_code ? purchaseOrder.vendor.vendor_code : 'AWV-001S' }}</td>
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
                <td>{{ purchaseOrder.requisitioner || 'Yasutaka Kajioka' }}</td>
                <td>{{ purchaseOrder.shipping_method || 'BY SEA' }}</td>
                <td>{{ purchaseOrder.fob_point || 'FOB' }}</td>
                <td>{{ purchaseOrder.payment_terms || 'Net 60 days' }}</td>
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
                <th class="delivery-col">Delivery Date</th>
                <th class="qty-col">Qty</th>
                <th class="unit-col">Unit</th>
                <th class="price-col">U/ Price</th>
                <th class="total-col">Total</th>
              </tr>
              <tr v-for="(line, index) in purchaseOrder.lines" :key="index">
                <td class="text-center">{{ index + 1 }}</td>
                <td>{{ line.item ? line.item.item_code : 'WBJ1-Z-FR/D110/3' }}</td>
                <td>
                  {{ line.item ? line.item.name : 'POLYURETHANE' }}<br>
                  <small>{{ line.item ? line.item.specification : '3MMX1000MMX2000MM' }}</small>
                </td>
                <td class="text-center">{{ formatDate(line.delivery_date || '2025-01-06', 'DD/MM/YY') }}</td>
                <td class="text-center">{{ formatNumber(line.quantity || 300) }}</td>
                <td class="text-center">{{ line.unitOfMeasure ? line.unitOfMeasure.name : 'SHT' }}</td>
                <td class="text-right">{{ formatCurrency(line.unit_price || 5.0400, true) }}</td>
                <td class="text-right">{{ formatCurrency(line.subtotal || 1512.0000, true) }}</td>
              </tr>
            </table>
          </div>

          <!-- Footer Section -->
          <div class="footer-content">
            <div class="remarks-section">
              <div class="remarks-title">
                <strong>Remarks :</strong>
              </div>
              <div class="notes-content">
                <p><strong>Note :</strong> Please Include certificate of analysis or inspection data in your delivery documents</p>
                <p></p>
                <p>Please Confirm your acceptance by signing this PO and return to us.</p>
                <p>If we have not receive your reply within 5 days, it means you accepted.</p>
                <p>1. Please send two copies of your invoice.</p>
                <p>2. Enter this order in accordance with the prices, terms, delivery method and specification listed above.</p>
                <p>3. Please notify us immediately if you are unable to ship as specified.</p>
                <p>4. send all correspondence to : {{ purchaseOrder.contact_person || 'EDWIN INDRADJAJA' }}</p>
                <p>5. Please include certificate of analysis/inspection data in your delivery documents.</p>
                <p>6. The material should not contain any substance banned on ROHS and Level 1 Prohibited Chemical Substance</p>
                <p>7. Any change to this raw material must be informed and approved by Armstrong in advance.</p>
              </div>
            </div>

            <div class="totals-section">
              <table class="totals-summary">
                <tr>
                  <td class="label">Subtotal</td>
                  <td class="colon">:</td>
                  <td class="amount">{{ formatCurrency(calculateSubtotal(), true) }}</td>
                </tr>
                <tr>
                  <td class="label">Discount</td>
                  <td class="colon">:</td>
                  <td class="amount">{{ formatCurrency(purchaseOrder.discount_amount || -0.0100, true, true) }}</td>
                </tr>
                <tr>
                  <td class="label">Sales Tax</td>
                  <td class="colon">:</td>
                  <td class="amount">{{ formatCurrency(purchaseOrder.tax_amount || 0, true) }}</td>
                </tr>
                <tr>
                  <td class="label">Shipping Cost</td>
                  <td class="colon">:</td>
                  <td class="amount">{{ formatCurrency(purchaseOrder.shipping_cost || 0, true) }}</td>
                </tr>
                <tr class="grand-total-row">
                  <td class="label"><strong>Grand Total</strong></td>
                  <td class="colon">:</td>
                  <td class="amount"><strong>{{ formatCurrency(purchaseOrder.total_amount || 2786.1350, true) }}</strong></td>
                </tr>
                <tr>
                  <td class="label">Currency</td>
                  <td class="colon">:</td>
                  <td class="amount">{{ purchaseOrder.currency_code || 'USD' }}</td>
                </tr>
              </table>
            </div>
          </div>
        </div>

        <!-- Signatures Section - Positioned at Bottom -->
        <div class="signatures-section">
          <div class="acceptance-box">
            <p><strong>Accepted by :</strong></p>
            <div class="signature-area"></div>
            <div class="signature-line"></div>
            <p>Pre acknowledge this PO</p>
          </div>

          <div class="signed-box">
            <p><strong>Signed by</strong></p>
            <div class="signature-area"></div>
            <div class="signature-line"></div>
            <p>{{ purchaseOrder.contact_person || 'EDWIN INDRADJAJA' }}<br>
            <small>INDUSTRI INDONESIA</small></p>
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
            }, 1000);
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
      if (!this.purchaseOrder.lines) return 2786.1250;
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

      if (format === 'DD/MM/YY') {
        const day = date.getDate().toString().padStart(2, '0');
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const year = date.getFullYear().toString().slice(-2);
        return `${day}/${month}/${year}`;
      }

      return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    },
    formatCurrency(amount, indonesianFormat = false, isNegative = false) {
      if (amount === null || amount === undefined) return '-';

      const absAmount = Math.abs(amount);

      if (indonesianFormat) {
        const formatted = new Intl.NumberFormat('en-US', {
          minimumFractionDigits: 4,
          maximumFractionDigits: 4
        }).format(absAmount);

        return (amount < 0 || isNegative) ? `-${formatted}` : formatted;
      }

      return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(amount);
    },
    formatNumber(number) {
      if (number === null || number === undefined) return '-';
      return new Intl.NumberFormat('en-US').format(number);
    },
    // Updated printDocument method - following SalesOrderPrint pattern
    printDocument() {
      if (!this.purchaseOrder || !this.purchaseOrder.lines) return;

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
          <title>Purchase Order - ${this.purchaseOrder?.po_number || 'Document'}</title>
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

            .purchase-order-document {
              width: 100%;
              min-height: 297mm;
              padding: 20mm;
              background-color: white;
              font-family: Arial, sans-serif;
              font-size: 11px;
              box-sizing: border-box;
              display: flex;
              flex-direction: column;
            }

            .main-content {
              flex: 1;
            }

            /* Header styles */
            .header {
              display: flex;
              justify-content: space-between;
              margin-bottom: 15px;
              align-items: flex-start;
            }

            .company-info {
              width: 60%;
            }

            .company-info h2 {
              margin: 0 0 5px 0;
              font-size: 12px;
              font-weight: bold;
              color: #000;
            }

            .company-info p {
              margin: 0 0 2px 0;
              font-size: 11px;
              color: #000;
            }

            .document-title {
              width: 40%;
              text-align: right;
            }

            .document-title h1 {
              font-size: 20px;
              font-weight: bold;
              margin: 0;
              color: #000;
            }

            .important-note {
              font-size: 11px;
              margin: 10px 0 8px 0;
              color: #000;
            }

            .po-number-section {
              margin-bottom: 15px;
              font-size: 12px;
              font-weight: bold;
            }

            /* Address section */
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
              font-size: 12px;
            }

            .company-name {
              font-weight: bold;
              margin: 0 0 2px 0;
              font-size: 11px;
            }

            .vendor-address p {
              margin: 0 0 2px 0;
              font-size: 11px;
            }

            .contact-info {
              width: 50%;
            }

            .contact-table {
              width: 100%;
              border: none;
            }

            .contact-table td {
              border: none;
              padding: 2px 5px;
              font-size: 11px;
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
              border: none;
              text-align: center;
              padding: 6px;
              font-size: 11px;
            }

            .po-details-table th {
              font-weight: bold;
              background-color: white;
              border-bottom: 1px dashed #000;
            }

            /* Items table */
            .items-section {
              margin-bottom: 20px;
            }

            .items-table {
              width: 100%;
              border-collapse: collapse;
            }

            .items-table th, .items-table td {
              border: none;
              padding: 4px;
              font-size: 10px;
            }

            .items-table th {
              text-align: center;
              font-weight: bold;
              background-color: white;
              border-bottom: 1px dashed #000;
            }

            /* Column widths */
            .no-col { width: 5%; }
            .item-col { width: 15%; }
            .desc-col { width: 25%; }
            .delivery-col { width: 10%; }
            .qty-col { width: 8%; }
            .unit-col { width: 7%; }
            .price-col { width: 12%; }
            .total-col { width: 13%; }

            .text-center { text-align: center; }
            .text-right { text-align: right; }

            .items-table td small {
              font-size: 9px;
              color: #666;
            }

            /* Footer content */
            .footer-content {
              display: flex;
              margin-top: 20px;
              gap: 30px;
              align-items: flex-start;
            }

            .remarks-section {
              flex: 2;
              font-size: 11px;
            }

            .remarks-title {
              font-weight: bold;
              margin-bottom: 8px;
              font-size: 12px;
            }

            .notes-content p {
              margin: 3px 0;
              line-height: 1.4;
              font-size: 10px;
            }

            .totals-section {
              flex: 1;
              margin-left: 20px;
            }

            .totals-summary {
              width: 100%;
              border: none;
            }

            .totals-summary td {
              border: none;
              padding: 3px 5px;
              font-size: 11px;
            }

            .totals-summary .label {
              text-align: right;
              width: 60%;
              font-weight: normal;
            }

            .totals-summary .colon {
              text-align: center;
              width: 5%;
            }

            .totals-summary .amount {
              text-align: right;
              width: 35%;
            }

            .grand-total-row {
              border-top: 1px solid #000;
              border-bottom: 1px solid #000;
            }

            .grand-total-row td {
              font-weight: bold;
              padding-top: 5px;
              padding-bottom: 5px;
            }

            /* Signatures section - positioned at bottom */
            .signatures-section {
              display: flex;
              justify-content: space-between;
              gap: 30px;
              page-break-inside: avoid;
              margin-top: auto;
              padding-top: 30px;
            }

            .acceptance-box, .signed-box {
              flex: 1;
              text-align: center;
              max-width: 250px;
            }

            .acceptance-box p, .signed-box p {
              margin: 2px 0;
              font-size: 11px;
            }

            .acceptance-box p:first-child, .signed-box p:first-child {
              margin-bottom: 8px;
              font-weight: bold;
            }

            .acceptance-box p:last-child, .signed-box p:last-child {
              margin-top: 5px;
            }

            .signature-area {
              height: 40px;
              margin: 15px 0;
              position: relative;
            }

            .signature-line {
              border-bottom: 1px solid #000;
              margin: 2px 0;
              width: 100%;
            }

            .signed-box small {
              font-size: 9px;
              color: #666;
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
    },
    // Updated printPdf method - following SalesOrderPrint pattern
    async printPdf() {
      if (this.isLoading) {
        console.warn("Data is still loading. Please wait before printing PDF.");
        return;
      }

      const container = document.createElement('div');
      container.style.position = 'static';
      container.style.width = '210mm';
      container.style.backgroundColor = 'white';
      container.style.padding = '0';
      container.style.margin = '0';

      // Clone the document element
      const element = document.getElementById('printDocument');
      const clonedElement = element.cloneNode(true);

      container.appendChild(clonedElement);
      document.body.appendChild(container);

      const opt = {
        margin: 0,
        filename: `PurchaseOrder_${this.purchaseOrder?.po_number || 'document'}.pdf`,
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
    }
  }
};
</script>

<style scoped>
/* Container and base styles */
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
  text-decoration: none;
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

.purchase-order-document {
  width: 100%;
  min-height: 297mm;
  padding: 20mm;
  background-color: white;
  font-family: Arial, sans-serif;
  font-size: 11px;
  box-sizing: border-box;
  position: relative;
  display: flex;
  flex-direction: column;
}

.main-content {
  flex: 1;
}

/* Header styles */
.header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 15px;
  align-items: flex-start;
}

.company-info {
  width: 60%;
}

.company-info h2 {
  margin: 0 0 5px 0;
  font-size: 12px;
  font-weight: bold;
  color: #000;
}

.company-info p {
  margin: 0 0 2px 0;
  font-size: 11px;
  color: #000;
}

.document-title {
  width: 40%;
  text-align: right;
}

.document-title h1 {
  font-size: 20px;
  font-weight: bold;
  margin: 0;
  color: #000;
}

.important-note {
  font-size: 11px;
  margin: 10px 0 8px 0;
  color: #000;
}

.po-number-section {
  margin-bottom: 15px;
  font-size: 12px;
  font-weight: bold;
}

/* Address section */
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
  font-size: 12px;
}

.company-name {
  font-weight: bold;
  margin: 0 0 2px 0;
  font-size: 11px;
}

.vendor-address p {
  margin: 0 0 2px 0;
  font-size: 11px;
}

.contact-info {
  width: 50%;
}

.contact-table {
  width: 100%;
  border: none;
}

.contact-table td {
  border: none;
  padding: 2px 5px;
  font-size: 11px;
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
  border: none;
  text-align: center;
  padding: 6px;
  font-size: 11px;
}

.po-details-table th {
  font-weight: bold;
  background-color: white;
  border-bottom: 1px dashed #000;
}

/* Items table */
.items-section {
  margin-bottom: 20px;
}

.items-table {
  width: 100%;
  border-collapse: collapse;
}

.items-table th, .items-table td {
  border: none;
  padding: 4px;
  font-size: 10px;
}

.items-table th {
  text-align: center;
  font-weight: bold;
  background-color: white;
  border-bottom: 1px dashed #000;
}

/* Column widths */
.no-col { width: 5%; }
.item-col { width: 15%; }
.desc-col { width: 25%; }
.delivery-col { width: 10%; }
.qty-col { width: 8%; }
.unit-col { width: 7%; }
.price-col { width: 12%; }
.total-col { width: 13%; }

.text-center { text-align: center; }
.text-right { text-align: right; }

.items-table td small {
  font-size: 9px;
  color: #666;
}

/* Footer content */
.footer-content {
  display: flex;
  margin-top: 20px;
  gap: 30px;
  align-items: flex-start;
}

.remarks-section {
  flex: 2;
  font-size: 11px;
}

.remarks-title {
  font-weight: bold;
  margin-bottom: 8px;
  font-size: 12px;
}

.notes-content p {
  margin: 3px 0;
  line-height: 1.4;
  font-size: 10px;
}

.totals-section {
  flex: 1;
  margin-left: 20px;
}

.totals-summary {
  width: 100%;
  border: none;
}

.totals-summary td {
  border: none;
  padding: 3px 5px;
  font-size: 11px;
}

.totals-summary .label {
  text-align: right;
  width: 60%;
  font-weight: normal;
}

.totals-summary .colon {
  text-align: center;
  width: 5%;
}

.totals-summary .amount {
  text-align: right;
  width: 35%;
}

.grand-total-row {
  border-top: 1px solid #000;
  border-bottom: 1px solid #000;
}

.grand-total-row td {
  font-weight: bold;
  padding-top: 5px;
  padding-bottom: 5px;
}

/* Signatures section - positioned at bottom */
.signatures-section {
  display: flex;
  justify-content: space-between;
  gap: 30px;
  page-break-inside: avoid;
  margin-top: auto;
  padding-top: 30px;
}

.acceptance-box, .signed-box {
  flex: 1;
  text-align: center;
  max-width: 250px;
}

.acceptance-box p, .signed-box p {
  margin: 2px 0;
  font-size: 11px;
}

.acceptance-box p:first-child, .signed-box p:first-child {
  margin-bottom: 8px;
  font-weight: bold;
}

.acceptance-box p:last-child, .signed-box p:last-child {
  margin-top: 5px;
}

.signature-area {
  height: 40px;
  margin: 15px 0;
  position: relative;
}

.signature-line {
  border-bottom: 1px solid #000;
  margin: 2px 0;
  width: 100%;
}

.signed-box small {
  font-size: 9px;
  color: #666;
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

  .purchase-order-document {
    width: 100%;
    padding: 1.5rem;
  }

  .header,
  .address-section {
    flex-direction: column;
  }

  .document-title {
    text-align: left;
    margin-top: 1rem;
  }

  .vendor-address,
  .contact-info {
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
