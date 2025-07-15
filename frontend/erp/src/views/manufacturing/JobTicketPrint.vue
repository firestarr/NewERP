<template>
  <div class="job-order-print">
    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="loading-spinner"></div>
      <p>Loading job ticket data...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-container">
      <div class="error-message">
        <i class="fas fa-exclamation-triangle"></i>
        <p>{{ error }}</p>
        <button class="btn btn-outline" @click="loadJobTicketData">
          <i class="fas fa-redo"></i>
          Retry
        </button>
      </div>
    </div>

    <!-- Print Content -->
    <div v-else>
      <!-- Control Buttons -->
      <div class="control-buttons no-print">
        <button class="btn btn-back" @click="goBack">
          <i class="fas fa-arrow-left"></i>
          <span>Back</span>
        </button>
        <button class="btn btn-print" @click="printDocument">
          <i class="fas fa-print"></i>
          <span>Print Document</span>
        </button>
        <button class="btn btn-pdf" @click="printPdf">
          <i class="fas fa-file-pdf"></i>
          <span>Save as PDF</span>
        </button>
      </div>

      <!-- Document Content -->
      <div class="document-wrapper">
        <div id="jobOrderDocument" class="job-order-report">
          <!-- Header Section -->
          <div class="header">
            <div class="left-section">
              <div class="title">JOB ORDER REPORT</div>
            </div>
            <div class="form-info">
<div class="fgrn-info">FGRN No: {{ jobTicket.fgrn_no || 'Not assigned' }}</div>
              <div class="date-info">Date: {{ formatPrintDateFullYear(printData.print_date) }}</div>
            </div>
          </div>

          <!-- Dotted Separator -->
          <div class="dotted-line"></div>

          <!-- Job Details Section -->
          <div class="job-details">
            <!-- First row: Ref JO No, Issue Date JO, Customer Code -->
            <div class="job-row">
              <div class="job-section">
                <span class="label">Ref. JO No:</span>
                <span class="value">{{ jobTicket.ref_jo_no || 'Not assigned' }}</span>
              </div>

              <div class="job-section">
                <span class="label">Issue Date JO:</span>
                <span class="value">{{ formatPrintDate(jobTicket.issue_date_jo) }}</span>
              </div>

              <div class="job-section ml-auto">
                <span class="label">Customer Code:</span>
                <span class="value">{{ jobTicket.customer_relation?.customer_code || 'N/A' }}</span>
              </div>
            </div>

            <!-- Second row: Qty JO positioned under Issue Date JO, Customer Name under Customer Code -->
            <div class="job-row">
              <div class="job-section-spacer"></div> <!-- Empty space for Ref JO No width -->

              <div class="job-section">
                <span class="label">Qty JO:</span>
                <span class="value">{{ formatNumber(jobTicket.qty_jo) }}</span>
              </div>

              <div class="job-section ml-auto">
                <span class="customer-name">{{ jobTicket.customer || 'Not specified' }}</span>
              </div>
            </div>
          </div>

          <!-- Dotted Separator before Parts Section -->
          <div class="dotted-line"></div>

          <!-- Parts Section -->
          <div class="parts-section">
            <div class="parts-table">
              <div class="parts-column">
                <div class="parts-label">Part No:</div>
                <div class="parts-value">{{ getPartNumber(jobTicket) }}</div>
              </div>
              <div class="parts-column">
                <div class="parts-label">Description</div>
                <div class="parts-value">{{ getItemDescription(jobTicket.production_order.work_order.item) }}</div>
              </div>
              <div class="parts-column">
                <div class="parts-label">Desc 2/ Size</div>
                <div class="parts-value">{{ getItemSize(jobTicket.production_order.work_order.item) }}</div>
              </div>
              <div class="parts-column">
                <div class="parts-label">UOM</div>
                <div class="parts-value">{{ jobTicket.uom || '' }}</div>
              </div>
              <div class="parts-column">
                <div class="parts-label">Qty Completed</div>
                <div class="parts-value">{{ formatNumber(jobTicket.qty_completed) }}</div>
              </div>
              <div class="parts-column">
                <div class="parts-label">Output</div>
                <div class="checkbox">☑</div>
              </div>
            </div>
          </div>

          <!-- Dotted Separator -->
          <div class="dotted-line"></div>

          <!-- Footer Section -->
          <div class="footer">
            <div class="footer-row">
              <span class="label">Lot Material :</span>
              <span class="value">{{ getLotMaterial(jobTicket) }}</span>

              <span class="label ml-auto">Issued By :</span>
              <span class="value">{{ printData.print_user || 'System' }}</span>
              <span class="value">{{ formatPrintDateTime(printData.print_date) }}</span>

              <span class="label ml-auto">Received By :</span>
              <span class="value">_________________</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import html2pdf from "html2pdf.js";

export default {
  name: 'JobTicketPrint',
  data() {
    return {
      jobTicket: null,
      printData: {},
      companyInfo: {},
      loading: true,
      error: null
    };
  },
  mounted() {
    this.loadJobTicketData();
  },
  watch: {
    '$route.params.id': {
      immediate: true,
      handler() {
        if (this.$route.params.id) {
          this.loadJobTicketData();
        }
      }
    }
  },
  methods: {
    async loadJobTicketData() {
      this.loading = true;
      this.error = null;

      try {
        // Try to fetch from print endpoint first (includes formatted data)
        let response;
        try {
          response = await axios.get(`/manufacturing/job-tickets/${this.$route.params.id}/print`);
        if (response.data.success) {
            this.jobTicket = response.data.data.job_ticket;
            // Assign production_number from nested production_order if exists
            if (this.jobTicket.production_order && this.jobTicket.production_order.production_number) {
              this.jobTicket.production_number = this.jobTicket.production_order.production_number;
            }
            // Assign fgrn_no if exists in response data
            if (response.data.data.job_ticket.fgrn_no) {
              this.jobTicket.fgrn_no = response.data.data.job_ticket.fgrn_no;
            }
            this.companyInfo = response.data.data.company_info;
            this.printData = {
              print_date: response.data.data.print_date,
              print_user: response.data.data.print_user
            };
          } else {
            throw new Error('Print endpoint failed');
          }
        } catch (printError) {
          // Fallback to regular detail endpoint
          console.warn('Print endpoint failed, using detail endpoint:', printError);
          response = await axios.get(`/manufacturing/job-tickets/${this.$route.params.id}`);

          if (response.data.success) {
            this.jobTicket = response.data.data;
            // Assign production_number from nested production_order if exists
            if (this.jobTicket.production_order && this.jobTicket.production_order.production_number) {
              this.jobTicket.production_number = this.jobTicket.production_order.production_number;
            }
            // Assign fgrn_no if exists in response data
            if (response.data.data.fgrn_no) {
              this.jobTicket.fgrn_no = response.data.data.fgrn_no;
            }
            this.companyInfo = {
              name: 'YAMAHA MUSIC MANUFACTURING ASIA',
              address: 'Company Address',
              phone: 'Company Phone',
              email: 'company@email.com'
            };
            this.printData = {
              print_date: new Date().toISOString(),
              print_user: 'System'
            };
          } else {
            throw new Error(response.data.message || 'Failed to load job ticket');
          }
        }
      } catch (error) {
        console.error('Error loading job ticket:', error);
        this.error = error.response?.data?.message || 'Failed to load job ticket data';
      } finally {
        this.loading = false;
      }
    },

    // Data formatting methods
    formatPrintDate(date) {
      if (!date) return '';

      try {
        const d = new Date(date);
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = String(d.getFullYear()).slice(-2);
        return `${day}/${month}/${year}`;
      } catch {
        return date;
      }
    },

    formatPrintDateFullYear(date) {
      if (!date) return '';

      try {
        const d = new Date(date);
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        return `${day}/${month}/${year}`;
      } catch {
        return date;
      }
    },

    formatPrintDateTime(datetime) {
      if (!datetime) return '';

      try {
        const d = new Date(datetime);
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = String(d.getFullYear()).slice(-2);
        const hours = String(d.getHours()).padStart(2, '0');
        const minutes = String(d.getMinutes()).padStart(2, '0');
        return `${day}/${month}/${year} ${hours}.${minutes}`;
      } catch {
        return datetime;
      }
    },

    formatNumber(value) {
      if (!value && value !== 0) return '';
      return Number(value).toLocaleString();
    },

    // Remove getCustomerCode method as customer_code will be fetched from backend directly

    getPartNumber(jobTicket) {
      // Try to get part number from various sources
      if (jobTicket.production_order?.work_order?.item?.item_code) {
        return jobTicket.production_order.work_order.item.item_code;
      }

      if (jobTicket.item && typeof jobTicket.item === 'object' && jobTicket.item.item_code) {
        return jobTicket.item.item_code;
      }

      // Generate from ticket ID or item name
      return jobTicket.ticket_id || jobTicket.item || 'N/A';
    },

    getItemDescription(item) {
      if (typeof item === 'object' && item) {
        return item.name || '';
      }

      if (typeof item === 'string') {
        return item;
      }

      return '';
    },

    getItemSize(item) {
      if (typeof item === 'object' && item) {
        return item.description || '';
      }

      return '';
    },

    getLotMaterial(jobTicket) {
      // Try to get lot/batch information
      if (jobTicket.production_order?.work_order?.batch_number) {
        return jobTicket.production_order.work_order.batch_number;
      }

      if (jobTicket.batch_number) {
        return jobTicket.batch_number;
      }

      return '';
    },

    // Print document - Create new window with styling like SalesOrderPrint
    printDocument() {
      if (!this.jobTicket) return;

      // Create a new window for printing
      const printWindow = window.open('', '_blank');

      // Get the current document content
      const documentElement = document.getElementById('jobOrderDocument');
      const documentHTML = documentElement.outerHTML;

      // Create the print HTML with same styling as preview
      const printHTML = `
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset="utf-8">
          <title>Job Order Report - ${this.jobTicket?.ticket_id || 'Document'}</title>
          <style>
            * {
              margin: 0;
              padding: 0;
              box-sizing: border-box;
            }

            body {
              font-family: 'Courier New', monospace;
              background: white;
              margin: 0;
              padding: 0;
            }

            .print-page {
              width: 21cm;
              height: 9.9cm;
              margin: 0 auto;
              background: white;
              padding: 0;
              box-shadow: none;
            }

            .job-order-report {
              width: 21cm;
              height: 9.9cm;
              margin: 0 auto;
              padding: 1cm;
              background: white;
              font-family: 'Courier New', monospace;
              font-size: 10px;
              line-height: 1.3;
              color: black;
              box-sizing: border-box;
            }

            .header {
              display: flex;
              justify-content: space-between;
              align-items: flex-start;
              margin-bottom: 0.1cm;
            }

            .left-section {
              display: flex;
              flex-direction: column;
            }

            .company-name {
              font-size: 14px;
              font-weight: bold;
              letter-spacing: 1px;
              margin-bottom: 0.2cm;
            }

            .title {
              font-size: 13px;
              font-weight: bold;
              letter-spacing: 1px;
            }

            .form-info {
              text-align: right;
            }

            .fgrn-info, .date-info {
              margin-bottom: 2px;
            }

            .dotted-line {
              border-top: 1px dotted #000;
              margin: 0.2cm 0;
              width: 100%;
            }

            .job-details {
              margin: 0.3cm 0;
            }

            .job-row {
              display: flex;
              align-items: center;
              margin-bottom: 0.1cm;
              flex-wrap: wrap;
            }

            .job-section {
              display: flex;
              align-items: center;
              margin-right: 1cm;
              min-width: fit-content;
            }

            .job-section .label {
              margin-right: 0.3cm;
              font-weight: normal;
              white-space: nowrap;
            }

            .job-section .value {
              font-weight: bold;
              white-space: nowrap;
            }

            .job-section-spacer {
              width: 5.3cm;
            }

            .job-row .label {
              margin-right: 0.3cm;
              font-weight: normal;
            }

            .job-row .value {
              margin-right: 0.5cm;
              font-weight: bold;
            }

            .job-row .ml-auto {
              margin-left: auto;
            }

            .customer-name {
              font-weight: bold;
            }

            .parts-section {
              margin: 0.3cm 0;
            }

            .parts-table {
              display: flex;
              justify-content: space-between;
              width: 100%;
            }

            .parts-column {
              display: flex;
              flex-direction: column;
              align-items: flex-start;
              min-width: 0;
              flex: 1;
              margin-right: 0.3cm;
            }

            .parts-column:last-child {
              margin-right: 0;
            }

            .parts-column:nth-child(1) { flex: 1.2; }
            .parts-column:nth-child(2) { flex: 1.5; }
            .parts-column:nth-child(3) { flex: 2.2; }
            .parts-column:nth-child(4) { flex: 0.8; }
            .parts-column:nth-child(5) { flex: 1.2; }
            .parts-column:nth-child(6) { flex: 1; }

            .parts-label {
              font-weight: normal;
              margin-bottom: 0.1cm;
              line-height: 1.2;
              white-space: nowrap;
            }

            .parts-value {
              font-weight: bold;
              line-height: 1.2;
              word-wrap: break-word;
            }

            .checkbox {
              font-size: 16px;
              margin-top: 0.1cm;
              align-self: flex-start;
            }

            .footer {
              margin: 0.3cm 0;
            }

            .footer-row {
              display: flex;
              align-items: center;
              flex-wrap: wrap;
            }

            .footer-row .label {
              margin-right: 0.3cm;
              font-weight: normal;
            }

            .footer-row .value {
              margin-right: 0.5rem;
            }

            .footer-row .ml-auto {
              margin-left: auto;
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
                size: 21cm 9.9cm landscape;
                margin: 0;
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

    // Print PDF document - Similar to SalesOrderPrint approach
    async printPdf() {
      if (this.loading) {
        console.warn("Data is still loading. Please wait before printing PDF.");
        return;
      }

      if (!this.jobTicket) {
        console.warn("No job ticket data available for PDF generation.");
        return;
      }

      // Create container for PDF generation
      const container = document.createElement('div');
      container.style.position = 'static';
      container.style.width = '21cm';
      container.style.height = '9.9cm';
      container.style.backgroundColor = 'white';
      container.style.padding = '0';
      container.style.margin = '0';

      // Clone the document element
      const element = document.getElementById('jobOrderDocument');
      const clonedElement = element.cloneNode(true);

      // Remove any pagination controls if they exist
      const paginationControls = clonedElement.querySelector('.pagination-controls');
      if (paginationControls) {
        paginationControls.remove();
      }

      container.appendChild(clonedElement);
      document.body.appendChild(container);

      const opt = {
        margin: 0,
        filename: `JobTicket_${this.jobTicket?.ticket_id || 'document'}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: {
          scale: 2,
          useCORS: true,
          letterRendering: true,
          allowTaint: false
        },
        jsPDF: {
          unit: 'cm',
          format: [21, 9.9],
          orientation: 'landscape',
          putOnlyUsedFonts: true,
          floatPrecision: 16
        }
      };

      try {
        await html2pdf().set(opt).from(container).save();
      } catch (error) {
        console.error("Error generating PDF:", error);
        alert("Error generating PDF. Please try again.");
      } finally {
        document.body.removeChild(container);
      }
    },

    goBack() {
      if (this.jobTicket?.ticket_id) {
        this.$router.push(`/manufacturing/job-tickets/${this.jobTicket.ticket_id}`);
      } else {
        this.$router.push('/manufacturing/job-tickets');
      }
    }
  }
}
</script>

<style scoped>
/* Loading and Error States */
.loading-container, .error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 400px;
  padding: 2rem;
}

.loading-spinner {
  border: 4px solid #f3f3f3;
  border-top: 4px solid #3498db;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-message {
  text-align: center;
  color: #dc3545;
}

.error-message i {
  font-size: 2rem;
  margin-bottom: 1rem;
  display: block;
}

/* Control Buttons */
.control-buttons {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
  justify-content: center;
  padding: 1rem;
  background: #f5f5f5;
  border-radius: 8px;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-back {
  background-color: #6b7280;
  color: white;
}

.btn-back:hover {
  background-color: #4b5563;
  transform: translateY(-2px);
}

.btn-print {
  background-color: #059669;
  color: white;
}

.btn-print:hover {
  background-color: #047857;
  transform: translateY(-2px);
}

.btn-pdf {
  background-color: #ef4444;
  color: white;
}

.btn-pdf:hover {
  background-color: #dc2626;
  transform: translateY(-2px);
}

.btn-outline {
  background-color: #f8f9fa;
  color: #6c757d;
  border: 1px solid #dee2e6;
}

.btn-outline:hover {
  background-color: #e2e6ea;
  border-color: #dae0e5;
}

/* Document wrapper for proper sizing and preview */
.document-wrapper {
  width: 21cm;
  min-height: 9.9cm;
  margin: 0 auto;
  background-color: white;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  border: 1px solid #e5e7eb;
  position: relative;
  display: block;
}

.job-order-report {
  width: 21cm;
  min-height: 9.9cm;
  margin: 0 auto;
  padding: 1cm;
  background: white;
  font-family: 'Courier New', monospace;
  font-size: 10px;
  line-height: 1.3;
  color: black;
  box-sizing: border-box;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.1cm;
}

.left-section {
  display: flex;
  flex-direction: column;
}

.company-name {
  font-size: 14px;
  font-weight: bold;
  letter-spacing: 1px;
  margin-bottom: 0.2cm;
}

.title {
  font-size: 13px;
  font-weight: bold;
  letter-spacing: 1px;
}

.form-info {
  text-align: right;
}

.fgrn-info, .date-info {
  margin-bottom: 2px;
}

.dotted-line {
  border-top: 1px dotted #000;
  margin: 0.2cm 0;
  width: 100%;
}

.job-details {
  margin: 0.3cm 0;
}

.job-row {
  display: flex;
  align-items: center;
  margin-bottom: 0.1cm;
  flex-wrap: wrap;
}

/* NEW: Updated job section styling for better spacing */
.job-section {
  display: flex;
  align-items: center;
  margin-right: 1cm; /* Controlled spacing between sections */
  min-width: fit-content;
}

.job-section .label {
  margin-right: 0.3cm;
  font-weight: normal;
  white-space: nowrap;
}

.job-section .value {
  font-weight: bold;
  white-space: nowrap;
}

/* Spacer to align Qty JO under Issue Date JO */
.job-section-spacer {
  width: 5.3cm; /* Adjusted width to align Qty JO exactly under Issue Date JO */
}

.job-row .label {
  margin-right: 0.3cm;
  font-weight: normal;
}

.job-row .value {
  margin-right: 0.5cm;
  font-weight: bold;
}

.job-row .ml-auto {
  margin-left: auto;
}

.customer-name {
  font-weight: bold;
}

.parts-section {
  margin: 0.3cm 0;
}

.parts-table {
  display: flex;
  justify-content: space-between;
  width: 100%;
}

.parts-column {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  min-width: 0;
  flex: 1;
  margin-right: 0.3cm;
}

.parts-column:last-child {
  margin-right: 0;
}

.parts-column:nth-child(1) { flex: 1.2; }
.parts-column:nth-child(2) { flex: 1.5; }
.parts-column:nth-child(3) { flex: 2.2; }
.parts-column:nth-child(4) { flex: 0.8; }
.parts-column:nth-child(5) { flex: 1.2; }
.parts-column:nth-child(6) { flex: 1; }

.parts-label {
  font-weight: normal;
  margin-bottom: 0.1cm;
  line-height: 1.2;
  white-space: nowrap;
}

.parts-value {
  font-weight: bold;
  line-height: 1.2;
  word-wrap: break-word;
}

.checkbox {
  font-size: 16px;
  margin-top: 0.1cm;
  align-self: flex-start;
}

.footer {
  margin: 0.3cm 0;
}

.footer-row {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
}

.footer-row .label {
  margin-right: 0.3cm;
  font-weight: normal;
}

.footer-row .value {
  margin-right: 0.5rem;
}

.footer-row .ml-auto {
  margin-left: auto;
}

/* Print Styles - Hidden during print */
@media print {
  .no-print {
    display: none !important;
  }

  .document-wrapper {
    box-shadow: none !important;
    border: none !important;
  }

  .job-order-report {
    width: 21cm;
    height: 9.9cm;
    margin: 0;
    padding: 0.5cm;
    box-shadow: none;
    page-break-inside: avoid;
  }

  @page {
    size: 21cm 9.9cm landscape;
    margin: 0;
  }

  body {
    margin: 0;
    padding: 0;
  }

  * {
    -webkit-print-color-adjust: exact !important;
    color-adjust: exact !important;
  }
}

/* Responsive adjustments */
@media screen and (max-width: 21cm) {
  .control-buttons {
    flex-direction: column;
    gap: 0.5rem;
  }

  .btn {
    font-size: 12px;
    padding: 0.5rem 1rem;
  }

  .document-wrapper {
    width: 100%;
  }

  .job-order-report {
    width: 100%;
    padding: 0.5cm;
  }

  .parts-table {
    flex-direction: column;
  }

  .parts-column {
    margin-right: 0;
    margin-bottom: 0.2cm;
  }

  .parts-label,
  .parts-value,
  .checkbox {
    font-size: 8px;
  }

  .job-row {
    flex-direction: column;
    align-items: flex-start;
  }

  .job-section {
    margin-right: 0;
    margin-bottom: 0.1cm;
  }

  .job-section-spacer {
    display: none; /* Hide spacer on mobile */
  }

  .job-row .ml-auto {
    margin-left: 0;
    margin-top: 0.1cm;
  }
}
</style>
