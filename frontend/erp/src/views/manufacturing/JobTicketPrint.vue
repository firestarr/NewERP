<!-- src/views/manufacturing/gin.vue -->
<template>
  <div>
    <!-- Control Buttons -->
    <div class="control-buttons">
      <button @click="goBack" class="btn btn-back">
        ← Kembali
      </button>
      <button @click="printReport" class="btn btn-print">
        🖨️ Print
      </button>
      <button @click="saveAsPDF" class="btn btn-pdf">
        📄 Save as PDF
      </button>
    </div>

    <!-- Job Order Report -->
    <div class="document-wrapper">
      <div id="jobOrderDocument" class="job-order-report">
        <!-- Header Section -->
        <div class="header">
          <div class="left-section">
            <div class="company-name">PT. ARMSTRONG INDUSTRI INDONESIA</div>
            <div class="title">JOB ORDER RESULT [FGRN / JT]</div>
          </div>
          <div class="form-info">
            <div class="fgrn-info">
              <span>FGRN NO. : <strong>{{ data.fgrnNo }}</strong></span>
            </div>
            <div class="date-info">
              <span>DATE : <strong>{{ data.date }}</strong></span>
            </div>
          </div>
        </div>

        <!-- Dotted Separator -->
        <div class="dotted-line"></div>

        <!-- Job Details Section -->
        <div class="job-details">
          <div class="job-row">
            <span class="label">Ref /JO No. :</span>
            <span class="value">{{ data.refJoNo }}</span>
            <span class="label ml-auto">Issue Date JO :</span>
            <span class="value">{{ data.issueDateJo }}</span>
            <span class="label ml-auto">Customer :</span>
            <span class="value">{{ data.customerCode }}</span>
          </div>
          <div class="job-row">
            <span class="label">QTY JO :</span>
            <span class="value">{{ data.qtyJo }}</span>
            <span class="customer-name">{{ data.customerName }}</span>
          </div>
        </div>

        <!-- Dotted Separator -->
        <div class="dotted-line"></div>

        <!-- Parts Table Section -->
        <div class="parts-section">
          <div class="parts-table">
            <div class="parts-column">
              <div class="parts-label">Part No. :</div>
              <div class="parts-value">{{ data.partNo }}</div>
            </div>
            <div class="parts-column">
              <div class="parts-label">Description</div>
              <div class="parts-value">{{ data.description }}</div>
            </div>
            <div class="parts-column">
              <div class="parts-label">Desc 2/ Size</div>
              <div class="parts-value">{{ data.desc2Size }}</div>
            </div>
            <div class="parts-column">
              <div class="parts-label">UOM</div>
              <div class="parts-value">{{ data.uom }}</div>
            </div>
            <div class="parts-column">
              <div class="parts-label">Qty Completed</div>
              <div class="parts-value">{{ data.qtyCompleted }}</div>
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
            <span class="value">{{ data.lotMaterial }}</span>
            <span class="label ml-auto">Issued By :</span>
            <span class="value">{{ data.issuedBy }}</span>
            <span class="value">{{ data.issuedDate }}</span>
            <span class="label ml-auto">Received By :</span>
            <span class="value">{{ data.receivedBy }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import html2pdf from "html2pdf.js";

export default {
  name: 'JobOrderReport',
  props: {
    reportData: {
      type: Object,
      default: () => ({})
    }
  },
  data() {
    return {
      data: {
        fgrnNo: 'JT2-25-30210',
        date: '12/06/2025',
        refJoNo: 'J2-25-15097',
        issueDateJo: '2/6/2025',
        qtyJo: '600',
        customerCode: 'YMM0001',
        customerName: 'PT. YAMAHA MUSIC MANUFACTURING ASIA',
        partNo: 'ZT05080',
        description: 'FELT',
        desc2Size: '1MMX15MMX30MM',
        uom: 'PCS',
        qtyCompleted: '744',
        output: '',
        lotMaterial: '',
        issuedBy: 'Yunita',
        issuedDate: '001 12/06/25 17.57',
        receivedBy: '',
        ...this.reportData
      }
    }
  },
  methods: {
    printReport() {
      // Create a new window for printing
      const printWindow = window.open('', '_blank');

      // Get the current document content
      const documentElement = document.getElementById('jobOrderDocument');
      const documentHTML = documentElement.outerHTML;

      // Create the print HTML with proper styling
      const printHTML = `
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset="utf-8">
          <title>Job Order Report - ${this.data.fgrnNo}</title>
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
              padding: 20px;
            }

            .print-page {
              width: 21cm;
              min-height: 9.9cm;
              margin: 0 auto;
              background: white;
              padding: 0;
              box-shadow: none;
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
              margin-left: auto;
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
              margin-right: 0.5cm;
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

              * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
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

    async saveAsPDF() {
      try {
        // Create a container for PDF generation
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

        // Add specific styling for PDF
        clonedElement.style.width = '21cm';
        clonedElement.style.height = '9.9cm';
        clonedElement.style.padding = '1cm';
        clonedElement.style.margin = '0';
        clonedElement.style.fontSize = '10px';
        clonedElement.style.fontFamily = '"Courier New", monospace';
        clonedElement.style.backgroundColor = 'white';
        clonedElement.style.color = 'black';

        container.appendChild(clonedElement);
        document.body.appendChild(container);

        // PDF generation options
        const opt = {
          margin: 0,
          filename: `JobOrder_${this.data.fgrnNo || 'report'}.pdf`,
          image: { type: 'jpeg', quality: 0.98 },
          html2canvas: {
            scale: 2,
            useCORS: true,
            letterRendering: true
          },
          jsPDF: {
            unit: 'cm',
            format: [21, 9.9],
            orientation: 'landscape'
          }
        };

        // Generate and save PDF
        await html2pdf().set(opt).from(container).save();

      } catch (error) {
        console.error("Error generating PDF:", error);
        alert("Error generating PDF. Please try again.");
      } finally {
        // Clean up - remove temporary container if it exists
        const container = document.querySelector('body > div:last-child');
        if (container && container.querySelector('#jobOrderDocument')) {
          document.body.removeChild(container);
        }
      }
    },

    goBack() {
      window.history.back();
    }
  }
}
</script>

<style scoped>
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
  background-color: #3b82f6;
  color: white;
}

.btn-print:hover {
  background-color: #2563eb;
  transform: translateY(-2px);
}

.btn-pdf {
  background-color: #dc2626;
  color: white;
}

.btn-pdf:hover {
  background-color: #b91c1c;
  transform: translateY(-2px);
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
  margin-left: auto;
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
  .control-buttons {
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

  .job-row .ml-auto {
    margin-left: 0;
    margin-top: 0.1cm;
  }
}
</style>
