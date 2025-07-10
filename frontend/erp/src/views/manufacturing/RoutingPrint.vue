<!-- src/views/manufacturing/RoutingPrint.vue -->
<template>
  <div class="print-container">
    <!-- Print Actions - Hidden during print -->
    <div class="print-actions no-print">
      <button class="btn btn-primary" @click="printDocument">
        <i class="fas fa-print"></i> Print Document
      </button>
      <button class="btn btn-danger" @click="printPdf" style="margin-left: 0.5rem;">
        <i class="fas fa-file-pdf"></i> Save as PDF
      </button>
      <button class="btn btn-secondary" @click="goBack">
        <i class="fas fa-arrow-left"></i> Back
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="loading-indicator no-print">
      <i class="fas fa-spinner fa-spin"></i>
      <p>Loading routing data...</p>
    </div>

    <!-- Print Content - A4 Document -->
    <div v-else-if="routing" class="document-wrapper">
      <div id="printDocument" class="routing-print-document">
        <!-- Header Section -->
        <div class="document-header">
          <div class="company-info">
            <h1 class="company-name">PROCESS CONTROL CHART</h1>
          </div>
        </div>

        <!-- Main Information Grid -->
        <div class="main-info-grid">
          <div class="info-row">
            <div class="info-cell">
              <label>Code</label>
              <div class="value">{{ routing.routing_code }}</div>
            </div>
            <div class="info-cell">
              <label>Part Name</label>
              <div class="value">{{ routing.item?.name || '-' }}</div>
            </div>
            <div class="info-cell">
              <label>Overall Dim</label>
              <div class="value">{{ getOverallDimension() }}</div>
            </div>
            <div class="info-cell">
              <label>Cust Name</label>
              <div class="value">INTERNAL</div>
            </div>
          </div>

          <div class="info-row">
            <div class="info-cell">
              <label>Date</label>
              <div class="value">{{ formatDate(routing.effective_date) }}</div>
            </div>
            <div class="info-cell">
              <label>Rev.No</label>
              <div class="value">{{ routing.revision }}</div>
            </div>
            <div class="info-cell">
              <label>Rev.Date</label>
              <div class="value">{{ formatDate(routing.updated_at) }}</div>
            </div>
            <div class="info-cell">
              <label>Material Code</label>
              <div class="value">{{ routing.item?.item_code || '-' }}</div>
            </div>
          </div>

          <div class="info-row">
            <div class="info-cell">
              <label>Dimension</label>
              <div class="value">{{ getItemDimension() }}</div>
            </div>
            <div class="info-cell">
              <label>Remarks</label>
              <div class="value">{{ routing.item?.description || 'REV PACK STD' }}</div>
            </div>
            <div class="info-cell">
              <label>Cust Code</label>
              <div class="value">{{ routing.item?.item_code || '-' }}</div>
            </div>
            <div class="info-cell">
              <label>Material Name</label>
              <div class="value">{{ routing.item?.name || '-' }}</div>
            </div>
          </div>
        </div>

        <!-- Process Information Section -->
        <div class="process-info-section">
          <div class="process-grid">
            <div class="process-row">
              <div class="process-cell">
                <label>Cavity</label>
                <div class="value">{{ routing.cavity || '4' }}</div>
              </div>
              <div class="process-cell">
                <label>Mould 1</label>
                <div class="value">{{ routing.mould_1 || '8321R1-01' }}</div>
              </div>
              <div class="process-cell">
                <label>Mould 2</label>
                <div class="value">{{ routing.mould_2 || '-' }}</div>
              </div>
              <div class="process-cell">
                <label>UOM</label>
                <div class="value">{{ routing.item?.unitOfMeasure?.symbol || 'PCS' }}</div>
              </div>
            </div>

            <div class="process-row">
              <div class="process-cell">
                <label>Yield 1</label>
                <div class="value">{{ routing.yield_1 || '1776' }}</div>
              </div>
              <div class="process-cell">
                <label>Y.DIM1</label>
                <div class="value">{{ getYieldDimension() }}</div>
              </div>
              <div class="process-cell">
                <label>Y.REM1</label>
                <div class="value">{{ routing.yield_rem_1 || '-' }}</div>
              </div>
              <div class="process-cell">
                <label>Process</label>
                <div class="value">{{ routing.process || 'MANUFACTURING' }}</div>
              </div>
            </div>

            <div class="process-row">
              <div class="process-cell">
                <label>Yield 2</label>
                <div class="value">{{ routing.yield_2 || '0' }}</div>
              </div>
              <div class="process-cell">
                <label>Y.DIM2</label>
                <div class="value">{{ routing.yield_dim_2 || '-' }}</div>
              </div>
              <div class="process-cell">
                <label>Y.REM2</label>
                <div class="value">{{ routing.yield_rem_2 || '-' }}</div>
              </div>
              <div class="process-cell">
                <label>Yield %</label>
                <div class="value">{{ routing.yield ? routing.yield + '%' : '-' }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Operations Table -->
        <div class="operations-table-container">
          <table class="operations-table">
            <thead>
              <tr>
                <th>Process</th>
                <th>Dimensions</th>
                <th>Add.Instructions and Remarks</th>
                <th colspan="2">Tolerance</th>
                <th>Machine</th>
                <th>SetUp Time</th>
                <th>Proc.Time per Cycle</th>
                <th>Yld 1</th>
                <th>Yld 2</th>
                <th>No Pc</th>
              </tr>
              <tr class="sub-header">
                <th></th>
                <th></th>
                <th></th>
                <th>Min</th>
                <th>Max</th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="operation in operations" :key="operation.operation_id" class="operation-row">
                <td class="process-name">
                  <div class="process-code">{{ operation.operation_name }}</div>
                  <div class="process-description">{{ operation.work_flow || '' }}</div>
                </td>
                <td class="dimensions">{{ getDimensionText(operation) }}</td>
                <td class="instructions">
                  <div class="instruction-text">{{ getInstructionText(operation) }}</div>
                  <div class="additional-notes">{{ operation.models || '' }}</div>
                </td>
                <td class="tolerance-min">{{ getToleranceMin(operation) }}</td>
                <td class="tolerance-max">{{ getToleranceMax(operation) }}</td>
                <td class="machine">{{ getMachineCode(operation) }}</td>
                <td class="setup-time">{{ formatTime(operation.setup_time) }}</td>
                <td class="process-time">{{ formatTime(operation.run_time) }}</td>
                <td class="yield-1">{{ getYieldValue(operation) }}</td>
                <td class="yield-2">{{ operation.yield_2 || '0' }}</td>
                <td class="piece-count">{{ getPieceCount(operation) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- File Path Information (moved from header) -->
        <div class="file-path-section">
          <div class="file-path">\\192.178.0.132\10DRAWING\routing\{{ routing.routing_code }}.PDF</div>
        </div>

        <!-- Footer -->
        <div class="document-footer">
          <div class="signature-section">
            <div class="signature-box">
              <label>Prepared By</label>
              <div class="signature-line"></div>
            </div>
            <div class="signature-box">
              <label>Approved By</label>
              <div class="signature-line"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else class="error-state no-print">
      <i class="fas fa-exclamation-triangle"></i>
      <p>Routing data not found</p>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import html2pdf from 'html2pdf.js';

export default {
  name: 'RoutingPrint',
  setup() {
    const router = useRouter();
    const route = useRoute();
    const routingId = computed(() => route.params.id);

    const isLoading = ref(true);
    const routing = ref(null);
    const operations = ref([]);
    const currentUser = ref(null);

    // Get current user from localStorage or session
    const getCurrentUser = () => {
      try {
        const user = localStorage.getItem('user');
        return user ? JSON.parse(user) : null;
      } catch {
        return null;
      }
    };

    // Load routing data
    const loadRoutingData = async () => {
      isLoading.value = true;
      try {
        // Load routing basic info
        const routingResponse = await axios.get(`/routings/${routingId.value}`);
        routing.value = routingResponse.data.data;

        // Load operations
        const operationsResponse = await axios.get(`/routings/${routingId.value}/operations`);
        operations.value = operationsResponse.data.data || [];

        currentUser.value = getCurrentUser();

        // Auto-print if requested in the URL
        if (route.query.autoprint === 'true') {
          setTimeout(() => {
            printDocument();
          }, 1000);
        }
      } catch (error) {
        console.error('Error loading routing data:', error);
      } finally {
        isLoading.value = false;
      }
    };

    // Utility functions
    const formatDate = (dateString) => {
      if (!dateString) return '-';
      return new Date(dateString).toLocaleDateString('en-GB');
    };

    const formatDateTime = (date) => {
      return new Date(date).toLocaleString('en-GB');
    };

    const formatTime = (time) => {
      if (!time) return '0.00';
      return parseFloat(time).toFixed(2);
    };

    const formatNumber = (number) => {
      if (!number) return '0';
      return new Intl.NumberFormat('en-US').format(number);
    };

    const getOverallDimension = () => {
      const item = routing.value?.item;
      if (!item) return '-';

      const length = item.length || '';
      const width = item.width || '';
      const thickness = item.thickness || '';

      if (length && width && thickness) {
        return `${thickness}MMX${width}MMX${length}MM`;
      }
      return '-';
    };

    const getItemDimension = () => {
      const item = routing.value?.item;
      if (!item) return '-';

      const length = item.length || '';
      const width = item.width || '';
      const thickness = item.thickness || '';

      if (length && width && thickness) {
        return `${thickness}MMX${width}MMX${length}MM`;
      }
      return item.item_code || '-';
    };

    const getYieldDimension = () => {
      const item = routing.value?.item;
      if (!item) return '-';

      const length = item.length || '58';
      const thickness = item.thickness || '0.55';
      return `${thickness}MMX${length}MMX50M`;
    };

    // Operation-specific helper functions using existing data
    const getDimensionText = (operation) => {
      const item = routing.value?.item;
      const thickness = item?.thickness || '0.55';
      const width = item?.width || '58';

      if (operation.operation_name.includes('SLIT')) {
        return `${thickness}MMX${width}MMX50M`;
      }
      if (operation.operation_name.includes('PUNCH')) {
        return 'S/J 112.5MM';
      }
      return '-';
    };

    const getInstructionText = (operation) => {
      const opName = operation.operation_name?.toUpperCase() || '';

      if (opName.includes('SLIT')) {
        return `SLIT ${operation.work_flow || 'MATERIAL'} YANG RATA`;
      }
      if (opName.includes('LAMN')) {
        return `LAMN ${operation.work_flow || 'MATERIAL'} BAGIAN LUAR ROLL`;
      }
      if (opName.includes('PUNCH')) {
        return 'K/CUT LANGSUNG DEFLASING';
      }
      if (opName.includes('CUT')) {
        return 'QTY 1 SHEET = 20 PCS 2 PCS X 10 PCS= 20 PCS';
      }
      if (opName.includes('PACK')) {
        return operation.models || 'PAKAI LAYER 2 SISI';
      }

      return operation.work_flow || operation.models || '-';
    };

    const getToleranceMin = (operation) => {
      if (operation.operation_name?.includes('SLIT')) return '-1.0';
      return '-';
    };

    const getToleranceMax = (operation) => {
      if (operation.operation_name?.includes('SLIT')) return '+1.0';
      return '-';
    };

    const getMachineCode = (operation) => {
      if (operation.work_center?.code) {
        return operation.work_center.code;
      }
      if (operation.work_center?.name) {
        return operation.work_center.name;
      }

      const opName = operation.operation_name?.toUpperCase() || '';
      if (opName.includes('SLIT')) return 'S3/016/D/975';
      if (opName.includes('LAMN')) return 'P31/080/J2/02';
      if (opName.includes('PUNCH')) return 'P31/080/J2/02';
      if (opName.includes('PACK') || opName.includes('CUT')) return 'MANUAL';

      return '-';
    };

    const getYieldValue = (operation) => {
    //   const cavity = routing.value?.cavity || 4;
      const opName = operation.operation_name?.toUpperCase() || '';

      if (opName.includes('PUNCH')) {
        return formatNumber(1776);
      }
      if (opName.includes('SLIT')) {
        return formatNumber(30192);
      }

      return '0';
    };

    const getPieceCount = (operation) => {
      const opName = operation.operation_name?.toUpperCase() || '';

      if (opName.includes('PUNCH')) return '0.00';
      if (opName.includes('PACK')) return '0.00';

      return '0.00';
    };

    // Print document function - Enhanced like SalesOrderPrint
    const printDocument = () => {
      if (!routing.value) return;

      // Create a new window for printing
      const printWindow = window.open('', '_blank');

      // Get the current document content
      const documentElement = document.getElementById('printDocument');
      const documentHTML = documentElement.outerHTML;

      // Create the print HTML with optimized styling
      const printHTML = `
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset="utf-8">
          <title>Process Control Chart - ${routing.value?.routing_code || 'Document'}</title>
          <style>
            * {
              margin: 0;
              padding: 0;
              box-sizing: border-box;
            }

            body {
              font-family: Arial, sans-serif;
              font-size: 10pt;
              line-height: 1.2;
              color: #000;
              background: white;
              margin: 0;
              padding: 15px;
            }

            .routing-print-document {
              width: 100%;
              background: white;
              padding: 0;
            }

            /* Header Section */
            .document-header {
              text-align: center;
              margin-bottom: 10px;
              border-bottom: 2px solid #000;
              padding-bottom: 5px;
            }

            .company-name {
              font-size: 14pt;
              font-weight: bold;
              margin: 0;
            }

            .header-classification {
              font-size: 9pt;
              margin-top: 2px;
            }

            /* Main Information Grid */
            .main-info-grid {
              border: 1px solid #000;
              margin-bottom: 10px;
            }

            .info-row {
              display: flex;
              border-bottom: 1px solid #000;
            }

            .info-row:last-child {
              border-bottom: none;
            }

            .info-cell {
              flex: 1;
              border-right: 1px solid #000;
              padding: 4px 6px;
              min-height: 25px;
            }

            .info-cell:last-child {
              border-right: none;
            }

            .info-cell label {
              font-size: 8pt;
              font-weight: bold;
              display: block;
              margin-bottom: 2px;
            }

            .info-cell .value {
              font-size: 9pt;
              word-break: break-word;
            }

            /* Process Information Section */
            .process-info-section {
              margin-bottom: 10px;
            }

            .process-grid {
              border: 1px solid #000;
            }

            .process-row {
              display: flex;
              border-bottom: 1px solid #000;
            }

            .process-row:last-child {
              border-bottom: none;
            }

            .process-cell {
              flex: 1;
              border-right: 1px solid #000;
              padding: 4px 6px;
              min-height: 20px;
            }

            .process-cell:last-child {
              border-right: none;
            }

            .process-cell label {
              font-size: 8pt;
              font-weight: bold;
              display: block;
              margin-bottom: 1px;
            }

            .process-cell .value {
              font-size: 9pt;
            }

            /* Operations Table */
            .operations-table-container {
              margin-bottom: 15px;
            }

            .operations-table {
              width: 100%;
              border-collapse: collapse;
              border: 1px solid #000;
              font-size: 8pt;
            }

            .operations-table th,
            .operations-table td {
              border: 1px solid #000;
              padding: 2px 4px;
              text-align: center;
              vertical-align: middle;
            }

            .operations-table th {
              background-color: #f0f0f0;
              font-weight: bold;
              font-size: 7pt;
            }

            .operations-table .sub-header th {
              background-color: #e8e8e8;
              height: 15px;
            }

            .operation-row {
              min-height: 25px;
            }

            .process-name {
              text-align: left;
              width: 15%;
            }

            .process-code {
              font-weight: bold;
              font-size: 8pt;
            }

            .process-description {
              font-size: 7pt;
              color: #666;
            }

            .dimensions {
              width: 12%;
              text-align: left;
            }

            .instructions {
              width: 20%;
              text-align: left;
            }

            .instruction-text {
              font-size: 8pt;
            }

            .additional-notes {
              font-size: 7pt;
              color: #666;
              margin-top: 1px;
            }

            .tolerance-min,
            .tolerance-max {
              width: 6%;
            }

            .machine {
              width: 10%;
            }

            .setup-time,
            .process-time {
              width: 8%;
            }

            .yield-1,
            .yield-2 {
              width: 6%;
            }

            .piece-count {
              width: 6%;
            }

            .empty-row {
              height: 20px;
            }

            /* File Path Section */
            .file-path-section {
              margin-bottom: 15px;
              text-align: center;
              border: 1px solid #000;
              padding: 5px;
            }

            .file-path {
              font-size: 8pt;
              color: #666;
              font-family: monospace;
            }

            /* Footer */
            .document-footer {
              margin-top: 20px;
              border-top: 1px solid #000;
              padding-top: 10px;
            }

            .signature-section {
              display: flex;
              justify-content: space-between;
              margin-bottom: 20px;
            }

            .signature-box {
              width: 45%;
              text-align: center;
            }

            .signature-box label {
              font-weight: bold;
              font-size: 9pt;
              display: block;
              margin-bottom: 10px;
            }

            .signature-line {
              border-bottom: 1px solid #000;
              height: 30px;
              margin-bottom: 5px;
            }

            .signature-name,
            .signature-date {
              font-size: 8pt;
              margin-top: 2px;
            }

            @media print {
              body {
                margin: 0 !important;
                padding: 10px !important;
              }

              @page {
                size: A4;
                margin: 0.5cm;
              }
            }
          </style>
        </head>
        <body>
          ${documentHTML}
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
    };

    // Print PDF function - Using html2pdf like SalesOrderPrint
    const printPdf = async () => {
      if (isLoading.value) {
        console.warn("Data is still loading. Please wait before printing PDF.");
        return;
      }

      if (!routing.value) {
        console.warn("No routing data available for PDF export.");
        return;
      }

      const element = document.getElementById('printDocument');

      if (!element) {
        console.error("Print document element not found.");
        return;
      }

      const opt = {
        margin: [0.5, 0.5, 0.5, 0.5],
        filename: `RoutingChart_${routing.value?.routing_code || 'document'}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: {
          scale: 2,
          useCORS: true,
          letterRendering: true
        },
        jsPDF: {
          unit: 'in',
          format: 'a4',
          orientation: 'portrait'
        }
      };

      try {
        await html2pdf().set(opt).from(element).save();
      } catch (error) {
        console.error("Error generating PDF:", error);
        alert("Error generating PDF. Please try again.");
      }
    };

    // Navigation
    const goBack = () => {
      router.push(`/manufacturing/routings/${routingId.value}`);
    };

    onMounted(() => {
      loadRoutingData();
    });

    return {
      isLoading,
      routing,
      operations,
      currentUser,
      formatDate,
      formatDateTime,
      formatTime,
      formatNumber,
      getOverallDimension,
      getItemDimension,
      getYieldDimension,
      getDimensionText,
      getInstructionText,
      getToleranceMin,
      getToleranceMax,
      getMachineCode,
      getYieldValue,
      getPieceCount,
      printDocument,
      printPdf,
      goBack
    };
  }
};
</script>

<style scoped>
/* Component styles - enhanced like SalesOrderPrint */
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

.routing-print-document {
  width: 100%;
  min-height: 297mm;
  padding: 20px;
  background: white;
  font-family: Arial, sans-serif;
  font-size: 11pt;
  line-height: 1.3;
  color: #000;
  box-sizing: border-box;
}

/* Header Section */
.document-header {
  text-align: center;
  margin-bottom: 10px;
  border-bottom: 2px solid #000;
  padding-bottom: 5px;
}

.company-info .company-name {
  font-size: 14pt;
  font-weight: bold;
  margin: 0;
}

.header-classification {
  font-size: 9pt;
  margin-top: 2px;
}

/* Main Information Grid */
.main-info-grid {
  border: 1px solid #000;
  margin-bottom: 10px;
}

.info-row {
  display: flex;
  border-bottom: 1px solid #000;
}

.info-row:last-child {
  border-bottom: none;
}

.info-cell {
  flex: 1;
  border-right: 1px solid #000;
  padding: 4px 6px;
  min-height: 25px;
}

.info-cell:last-child {
  border-right: none;
}

.info-cell label {
  font-size: 8pt;
  font-weight: bold;
  display: block;
  margin-bottom: 2px;
}

.info-cell .value {
  font-size: 9pt;
  word-break: break-word;
}

/* Process Information Section */
.process-info-section {
  margin-bottom: 10px;
}

.process-grid {
  border: 1px solid #000;
}

.process-row {
  display: flex;
  border-bottom: 1px solid #000;
}

.process-row:last-child {
  border-bottom: none;
}

.process-cell {
  flex: 1;
  border-right: 1px solid #000;
  padding: 4px 6px;
  min-height: 20px;
}

.process-cell:last-child {
  border-right: none;
}

.process-cell label {
  font-size: 8pt;
  font-weight: bold;
  display: block;
  margin-bottom: 1px;
}

.process-cell .value {
  font-size: 9pt;
}

/* Operations Table */
.operations-table-container {
  margin-bottom: 15px;
}

.operations-table {
  width: 100%;
  border-collapse: collapse;
  border: 1px solid #000;
  font-size: 8pt;
}

.operations-table th,
.operations-table td {
  border: 1px solid #000;
  padding: 2px 4px;
  text-align: center;
  vertical-align: middle;
}

.operations-table th {
  background-color: #f0f0f0;
  font-weight: bold;
  font-size: 7pt;
}

.operations-table .sub-header th {
  background-color: #e8e8e8;
  height: 15px;
}

.operation-row {
  min-height: 25px;
}

.process-name {
  text-align: left;
  width: 15%;
}

.process-code {
  font-weight: bold;
  font-size: 8pt;
}

.process-description {
  font-size: 7pt;
  color: #666;
}

.dimensions {
  width: 12%;
  text-align: left;
}

.instructions {
  width: 20%;
  text-align: left;
}

.instruction-text {
  font-size: 8pt;
}

.additional-notes {
  font-size: 7pt;
  color: #666;
  margin-top: 1px;
}

.tolerance-min,
.tolerance-max {
  width: 6%;
}

.machine {
  width: 10%;
}

.setup-time,
.process-time {
  width: 8%;
}

.yield-1,
.yield-2 {
  width: 6%;
}

.piece-count {
  width: 6%;
}

/* File Path Section (moved from header) */
.file-path-section {
  margin-bottom: 15px;
  text-align: center;
  border: 1px solid #000;
  padding: 5px;
}

.file-path {
  font-size: 8pt;
  color: #666;
  font-family: monospace;
}

/* Footer */
.document-footer {
  margin-top: 20px;
  border-top: 1px solid #000;
  padding-top: 10px;
}

.signature-section {
  display: flex;
  justify-content: space-between;
  margin-bottom: 20px;
}

.signature-box {
  width: 45%;
  text-align: center;
}

.signature-box label {
  font-weight: bold;
  font-size: 9pt;
  display: block;
  margin-bottom: 10px;
}

.signature-line {
  border-bottom: 1px solid #000;
  height: 30px;
  margin-bottom: 5px;
}

.signature-name,
.signature-date {
  font-size: 8pt;
  margin-top: 2px;
}

/* Loading and Error States */
.loading-indicator,
.error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 200px;
  text-align: center;
}

.loading-indicator i {
  font-size: 2rem;
  margin-bottom: 10px;
  color: #007bff;
}

.error-state i {
  font-size: 2rem;
  margin-bottom: 10px;
  color: #dc3545;
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
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .print-container {
    padding: 1rem;
  }

  .document-wrapper {
    width: 100%;
  }

  .routing-print-document {
    padding: 10px;
  }

  .main-info-grid .info-row {
    flex-direction: column;
  }

  .info-cell {
    border-right: none;
    border-bottom: 1px solid #000;
  }

  .operations-table {
    font-size: 7pt;
  }

  .signature-section {
    flex-direction: column;
    gap: 20px;
  }

  .signature-box {
    width: 100%;
  }
}
</style>
