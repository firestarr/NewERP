<template>
  <div class="print-container">
    <!-- Print Actions - Hidden during print -->
    <div class="print-actions no-print">
      <button class="btn btn-primary" @click="printDocument" :disabled="isLoading">
        <i class="fas fa-print"></i>
        <span v-if="isLoading">⏳ Loading...</span>
        <span v-else>Print Document</span>
      </button>
      <button class="btn btn-danger" @click="printPdf" :disabled="isLoading" style="margin-left: 0.5rem;">
        <i class="fas fa-file-pdf"></i> Save as PDF
      </button>
      <button class="btn btn-secondary" @click="goBack">
        <i class="fas fa-arrow-left"></i> Back
      </button>
    </div>

    <!-- Loading spinner -->
    <div v-if="isLoading" class="loading-container">
      <div class="text-center">
        <div class="spinner-border" role="status">
          <span class="sr-only">Loading...</span>
        </div>
        <p>Loading Job order data...</p>
      </div>
    </div>

    <!-- Error message -->
    <div v-if="error" class="alert alert-danger">
      <strong>Error:</strong> {{ error }}
      <button @click="loadWorkOrderData" class="btn btn-sm btn-outline-danger ml-2">
        Retry
      </button>
    </div>

    <!-- Print Content - A4 Document -->
    <div class="document-wrapper" v-if="!isLoading && !error">
      <div id="printDocument" class="work-order-document">
        <div class="container">
          <!-- Header atas (JO No dan Prod Date, Delivery Date) -->
          <div class="header-top">
            <div class="jo-no">
              <span>JO No :</span>
              <b>{{ workOrderData.wo_number || 'Loading...' }}</b>
            </div>

            <div class="right-dates">
              <div class="prod-date">
                Prod Date : <b>{{ formatDate(workOrderData.planned_start_date) || formatDate(new Date()) }}</b>
              </div>
              <div class="delivery-date">
                Delivery Date : <b>{{ formatDate(workOrderData.planned_end_date) || formatDate(new Date()) }}</b>
              </div>
            </div>
          </div>

          <!-- Bagian utama kiri - left + issue wh di sebelah kiri, dan kanan yield + flow chart -->
          <div class="main-body">
            <!-- Kiri: Customer, Part Info, MatCode, TapeCode, Barcodes -->
            <div class="left-section">
              <dl>
                <div class="row">
                  <dt>Customer</dt>
                  <dd>{{ workOrderData.customer_name || (workOrderData.primary_customer?.customer_name || 'PT. YAMAHA MUSIC MANUFACTURING Dummy') }}</dd>
                </div>
                <div class="row">
                  <dt>Intern Code</dt>
                  <dd>{{ workOrderData.item?.item_code || 'Null' }}</dd>
                </div>
                <div class="row">
                  <dt>Cust Code</dt>
                  <dd>{{ workOrderData.primary_customer?.customer_code || 'YMI-0001 Dummy' }}</dd>
                </div>
                <div class="row">
                  <dt>Part Name</dt>
                  <dd class="bold-dd">{{ workOrderData.item?.name || 'CUSHION Dummy' }}</dd>
                </div>
                <div class="row">
                  <dt>Size</dt>
                  <dd>{{ workOrderData.item?.description || 'YMM0001 Dummy' }}</dd>
                </div>
                <div class="row">
                  <dt>Process</dt>
                  <dd>{{ workOrderData.routing?.process || 'SINGLE KNIFE Dummy' }}</dd>
                </div>

                <div class="row cavity-punch-knife">
                  <div class="cavity-punch-wrapper">
                    <div class="item">
                      <dt>Cavity</dt>
                      <dd>{{ workOrderData.routing?.cavity || '1 Dummy' }}</dd>
                    </div>
                    <div class="item">
                      <dt>Total Punch</dt>
                      <dd>{{ workOrderData.total_punch || '' }}</dd>
                    </div>
                  </div>
                  <div class="single-knife-inline">
                    {{ workOrderData.routing?.process || 'SINGLE KNIFE' }}
                  </div>
                </div>

                <div class="row">
                  <dt>ECN#</dt>
                  <dd>{{ workOrderData.ecn || '' }}</dd>
                </div>
                <div class="row">
                  <dt>Set Jump</dt>
                  <dd>{{ workOrderData.routing?.set_jump || '0.000 Dummy' }}</dd>
                  <dd></dd>
                </div>
              </dl>

              <!-- Updated mat-codes section with dynamic data from BOM -->
              <div class="mat-codes">
                <div v-for="(material, index) in materials" :key="index" class="mat-code-item">
                  <div><b>MatCode {{ index + 1 }}</b> : <span>{{ material.code || '□□□□□□' }}</span></div>
                  <div><b>MatNm {{ index + 1 }}</b> : {{ material.name || '' }}</div>
                  <div><b>Size :</b> {{ material.size || '' }}</div>
                  <div><b>Yield :</b> {{ material.yield || '' }}</div>
                  <div><b>Issue Qty :</b> {{ material.issue_qty || '0.00000' }} /</div>
                  <div><b>Qty/Mtr :</b> {{ material.qty_mtr }}</div>
                </div>
              </div>

              <div class="tape-codes">
                <div v-for="(tape, index) in tapes" :key="index">
                  <div><b>TapeCode {{ index + 1 }}</b>: {{ tape.code || '' }}</div>
                  <div><b>TapeNm{{ index + 1 }} :</b> {{ tape.name || '' }}</div>
                  <div><b>Size :</b> {{ tape.size || '' }}</div>
                  <div><b>Yield :</b> {{ tape.yield || '' }}</div>
                  <div><b>Issue Qty :</b> {{ tape.issue_qty || '0.00000' }} /</div>
                </div>
                <div>
                  <div><b>Sub Material :</b></div>
                  <div><b>Sub Material :</b></div>
                  <div><b>Sub Material :</b></div>
                  <div><b>Sub Material :</b></div>
                </div>
              </div>

              <div class="barcode-section">
                <!-- unused for now -->
              </div>
            </div>

            <!-- Middle Section: Issue WH sampai Important ada disini, sejajar dengan Customer -->
            <div class="middle-section">
              <table class="info-table issue-important">
                <tbody>
                  <tr><td>Issue WH :</td><td>{{ workOrderData.issue_wh || '' }}</td></tr>
                  <tr><td>Issue Date</td><td>{{ formatDate(workOrderData.wo_date) || formatDate(new Date()) }}</td></tr>
                  <tr><td>Print Date</td><td>{{ formatDate(new Date()) || '17 May 2025 Dum' }}</td></tr>
                  <tr><td>Order Qty</td><td>{{ formatNumber(workOrderData.actual_quantity) || ' ' }} PCS</td></tr>
                  <tr><td>Prod. Qty</td><td>{{ formatNumber(workOrderData.planned_quantity) || '2,388 Dummy' }} PCS</td></tr>
                  <tr><td>Important</td><td>{{ workOrderData.important || '' }}</td></tr>
                  <tr><td>Yield</td><td>{{ workOrderData.routing?.yield || '' }}</td></tr>
                  <tr><td>Size</td><td>{{ workOrderData.description || '' }}</td></tr>
                  <tr><td>Qty</td><td>{{ workOrderData.qty_info || '' }}</td></tr>
                </tbody>
              </table>
            </div>

            <!-- Kanan: Bagian Yield dan Flow Chart History -->
            <div class="right-section">
              <table class="flow-chart-history">
                <thead>
                  <tr>
                    <th colspan="4">Flow Chart History</th>
                  </tr>
                  <tr>
                    <th>Date</th>
                    <th>Process</th>
                    <th>Qty</th>
                    <th>Name</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(history, index) in flowChartHistory" :key="index">
                    <td>{{ formatDate(history.date) || '&nbsp;' }}</td>
                    <td>{{ history.process || '&nbsp;' }}</td>
                    <td>{{ history.qty || '&nbsp;' }}</td>
                    <td>{{ history.name || '&nbsp;' }}</td>
                  </tr>
                  <tr v-for="n in Math.max(0, 10 - flowChartHistory.length)" :key="'empty-' + n">
                    <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Workflow Table -->
          <table class="workflow-table">
            <thead>
              <tr>
                <th>Process</th>
                <th>Work Flow</th>
                <th>Tolerance</th>
                <th>Check Process</th>
                <th>Qty</th>
                <th>Machine</th>
                <th>Setup (mnt)</th>
                <th>Proces (mnt)</th>
                <th>Total (mnt)</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="workflow in workflows" :key="workflow.id">
                <td>{{ workflow.process }}</td>
                <td v-html="workflow.work_flow"></td>
                <td>{{ workflow.tolerance }}</td>
                <td>{{ workflow.check_process }}</td>
                <td>{{ workflow.qty }}</td>
                <td>{{ workflow.machine }}</td>
                <td>{{ workflow.setup }}</td>
                <td>{{ workflow.proces }}</td>
                <td>{{ workflow.total }}</td>
                <td>{{ workflow.status }}</td>
              </tr>
            </tbody>
          </table>

          <!-- Catatan Penting -->
          <div class="catatan-penting">
            <em><b>Catatan Penting :</b></em>
            <span class="date-note">{{ formatDate(workOrderData.planned_end_date) || '02/01/2025 Dummy' }}</span>
          </div>

          <!-- Footer -->
          <div class="footer-bottom">
            <div class="partial-info">
              <div>* Partial / No Partial</div>
              <div class="partial-gin-no">
                <label for="partial-gin-no-input">Partial GIN No :</label>
                <input id="partial-gin-no-input" type="text" />
                <span>/</span>
              </div>
              <div class="warning-text">
                <b>PASTIKAN LOT NO. HARUS DI INPUT !!!</b>
              </div>
            </div>

            <table class="footer-table">
              <thead>
                <tr>
                  <th>Standard</th>
                  <th>Lot Material</th>
                  <th>Lot Material</th>
                  <th>Lot Material</th>
                  <th>Reject Qty</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>No. of Plastic</td>
                  <td>Lot adhesive</td>
                  <td>Lot adhesive</td>
                  <td>Lot adhesive</td>
                  <td></td>
                </tr>
                <tr>
                  <td>No. of Carton</td>
                  <td>Machine</td>
                  <td>Operator</td>
                  <td>{{ formatDate(workOrderData.planned_end_date) || '02/01/2025' }}</td>
                  <td></td>
                </tr>
              </tbody>
            </table>

            <div class="footer-note">
              *) Just streak (coret) if part partial or no partial
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import html2pdf from 'html2pdf.js';

export default {
  name: "WorkOrderPrint",
  setup() {
    const route = useRoute();
    const router = useRouter();
    const workOrderData = ref({});
    const isLoading = ref(false);
    const error = ref(null);

    const materials = ref([
      {
        code: '□□□□□□',
        name: 'TORAY PEF/2MM',
        size: '2MMX1000MMX200M',
        yield: '82,784',
        issue_qty: '0.02885',
        qty_mtr: '□□□□□□'
      },
      {
        code: '□□□□□□',
        name: '',
        size: '',
        yield: '',
        issue_qty: '0.00000',
        qty_mtr: '□□□□□□'
      },
      {
        code: '□□□□□□',
        name: '',
        size: '',
        yield: '',
        issue_qty: '0.00000',
        qty_mtr: '□□□□□□'
      }
    ]);

    const tapes = ref([
      {
        code: 'N500A/1210',
        name: '',
        size: '',
        yield: '20,696',
        issue_qty: '0.11538 / RL'
      },
      {
        code: '',
        name: '',
        size: '',
        yield: '',
        issue_qty: '0.00000'
      }
    ]);

    const flowChartHistory = ref([]);

    // Computed property untuk workflows berdasarkan data API
    const workflows = computed(() => {
      if (!workOrderData.value.work_order_operations || workOrderData.value.work_order_operations.length === 0) {
        // Return default data if no API data available
        return [
          {
            id: 1,
            process: 'C',
            work_flow: 'CEK MATERIAL<br />SEBELUM PROSES',
            tolerance: '-1.0 +1.0',
            check_process: '',
            qty: '',
            machine: 'MANUAL',
            setup: '2',
            proces: '0.4',
            total: '2.4',
            status: ''
          },
          {
            id: 2,
            process: 'L',
            work_flow: 'LAMIN SESUAIKAN MATERIAL<br />LAM.BAG.YG MENGKILAP',
            tolerance: '',
            check_process: '',
            qty: '',
            machine: 'L-1',
            setup: '15',
            proces: '1.6',
            total: '16.6',
            status: ''
          },
          {
            id: 3,
            process: 'P',
            work_flow: '2MM X 1000MM X 480MM F/C<br />POTONG YANG SIKU',
            tolerance: '-1.0 +1.0',
            check_process: '',
            qty: '',
            machine: 'P-5',
            setup: '15',
            proces: '23.9',
            total: '38.9',
            status: ''
          },
          {
            id: 4,
            process: 'P',
            work_flow: '2MM X 5MM X 480 K/C<br />TIAP 50 BARIS F/C',
            tolerance: '-0.3 +0.3',
            check_process: '',
            qty: '',
            machine: 'P-20',
            setup: '10',
            proces: '23.9',
            total: '33.9',
            status: ''
          },
          {
            id: 5,
            process: 'PK',
            work_flow: 'SESUAIKAN DENGAN ORDER<br />PACKING YANG RAPI',
            tolerance: '',
            check_process: '',
            qty: '',
            machine: 'MANUAL',
            setup: '5',
            proces: '8.0',
            total: '13.0',
            status: ''
          }
        ];
      }

      // Map API data to workflow format
      return workOrderData.value.work_order_operations.map((operation, index) => {
        const routingOp = operation.routing_operation || {};
        const workCenter = routingOp.work_center || {};

        const workFlowText = routingOp.work_flow || 'Operation ' + (index + 1);
        const formattedWorkFlow = workFlowText.replace(/\n/g, '<br />');

        const machineFromModels = routingOp.models || workCenter.name || 'MANUAL';

        let processCode = '';
        if (routingOp.operation_name) {
          const parts = routingOp.operation_name.split('-');
          processCode = parts.length > 1 ? parts[parts.length - 1] : (index + 1).toString();
        } else {
          processCode = (index + 1).toString();
        }

        return {
          id: operation.operation_id || index + 1,
          process: processCode,
          work_flow: formattedWorkFlow,
          tolerance: routingOp.tolerance || '',
          check_process: routingOp.check_process || '',
          qty: routingOp.planned_quantity || workOrderData.value.planned_quantity || '',
          machine: machineFromModels,
          setup: routingOp.setup_time || '0',
          proces: routingOp.run_time || '0',
          total: (parseFloat(routingOp.setup_time || 0) + parseFloat(routingOp.run_time || 0)).toString(),
          status: operation.status || 'Pending'
        };
      });
    });

    // Print document - Buat window baru dengan styling khusus
    const printDocument = () => {
      if (isLoading.value || error.value) {
        alert('Please wait for data to load before printing.');
        return;
      }

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
          <title>Work Order - ${workOrderData.value?.wo_number || 'Document'}</title>
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

            .work-order-document {
              width: 100%;
              min-height: 297mm;
              background-color: white;
              font-family: Arial, sans-serif;
              box-sizing: border-box;
            }

            .container {
              font-family: Arial, sans-serif;
              font-size: 10pt;
              color: black;
              padding: 0.5cm;
              background: white;
              position: relative;
              min-height: calc(297mm - 1cm);
              padding-bottom: 120px; /* Space for footer */
            }

            /* Header */
            .header-top {
              display: flex;
              justify-content: space-between;
              margin-bottom: 12px;
              font-size: 11pt;
            }

            .header-top .jo-no {
              font-weight: normal;
            }

            .header-top .jo-no b {
              font-weight: bold;
              font-size: 13pt;
            }

            .right-dates {
              text-align: right;
              font-weight: normal;
              font-size: 11pt;
            }

            .right-dates .prod-date b,
            .right-dates .delivery-date b {
              font-size: 16pt;
              font-weight: bold;
              letter-spacing: 1px;
            }

            /* Main body flex container */
            .main-body {
              display: flex;
              justify-content: space-between;
              margin-bottom: 12px;
              gap: 20px;
            }

            /* Left Section */
            .left-section {
              width: 53%;
              font-size: 9pt;
            }

            .left-section dl {
              margin-bottom: 12px;
            }

            .left-section .row {
              display: flex;
              width: 100%;
              gap: 5px 25px;
              align-items: center;
              margin-bottom: 3px;
            }

            .left-section dt,
            .left-section dd {
              margin: 0;
              padding: 0;
              line-height: 1.3;
              white-space: nowrap;
            }

            .left-section dt {
              font-weight: bold;
              flex: 0 0 90px;
            }

            .left-section dd {
              flex: 0 0 95px;
            }

            .left-section dd.bold-dd {
              font-weight: bold;
              white-space: normal;
            }

            .cavity-punch-knife {
              display: flex;
              justify-content: space-between;
              margin-top: 5px;
              margin-bottom: 5px;
              align-items: flex-start;
              flex-wrap: nowrap;
            }

            .cavity-punch-wrapper {
              display: flex;
              flex-direction: column;
              gap: 3px;
            }

            .cavity-punch-knife .item {
              display: flex;
              gap: 3px 8px;
              align-items: center;
              white-space: nowrap;
            }

            .cavity-punch-knife dt {
              font-weight: bold;
              flex: none;
            }

            .cavity-punch-knife dd {
              flex: none;
              min-width: 30px;
            }

            .single-knife-inline {
            font-weight: bold;
            font-size: 18pt;
            letter-spacing: 1px;
            color: black;
            border: 1.5px solid red;
            padding: 4px 12px;
            user-select: none;
            white-space: nowrap;
            }

            .mat-codes {
              display: flex;
              flex-wrap: nowrap;
              justify-content: space-between;
              gap: 10px;
              font-size: 8pt;
              margin-top: 10px;
            }

            .mat-code-item {
              flex: 1;
              min-width: 150px;
              max-width: 32%;
              line-height: 1.3;
            }

            .mat-code-item b {
              font-weight: bold;
            }

            .mat-code-item span {
              display: inline-block;
              width: 70px;
            }

            .tape-codes {
              display: flex;
              justify-content: space-between;
              font-size: 8pt;
              margin-top: 10px;
            }

            .tape-codes > div {
              width: 31%;
              line-height: 1.3;
            }

            .tape-codes > div b {
              font-weight: bold;
            }

            .barcode-section {
              display: flex;
              justify-content: space-between;
              margin-top: 10px;
              font-size: 9pt;
            }

            .barcode-section > div {
              width: 31%;
              text-align: center;
            }

            /* Middle Section */
            .middle-section {
              width: 20%;
              font-size: 9pt;
              text-align: left;
              align-self: flex-start;
            }

            .info-table.issue-important {
              width: 100%;
              border-collapse: collapse;
            }

            .info-table.issue-important td {
              padding: 0 8px 0 0;
              vertical-align: top;
              font-weight: normal;
              white-space: nowrap;
            }

            .info-table.issue-important td:first-child {
              font-weight: bold;
              width: 60px;
            }

            /* Right Section */
            .right-section {
              width: 25%;
              font-size: 9pt;
              text-align: right;
              line-height: 1.3;
              display: flex;
              flex-direction: column;
              gap: 5px;
            }

            .flow-chart-history {
              width: 100%;
              border-collapse: collapse;
              border: 1px solid black;
              font-size: 9pt;
            }

            .flow-chart-history thead th {
              border: 1px solid black;
              font-weight: bold;
              padding: 2px;
              text-align: center;
              background: none;
            }

            .flow-chart-history tbody td {
              border: 1px solid black;
              text-align: center;
              padding: 3px 2px;
              height: 20px;
              font-weight: normal;
            }

            /* Workflow Table */
            .workflow-table {
              width: 100%;
              border-collapse: collapse;
              font-size: 8pt;
              margin-top: 10px;
              text-align: left;
            }

            .workflow-table th,
            .workflow-table td {
              border: 1px solid black;
              padding: 2px 6px;
              vertical-align: top;
              font-weight: normal;
            }

            .workflow-table th {
              font-weight: bold;
              height: 24px;
            }

            /* Catatan Penting */
            .catatan-penting {
              margin-top: 10px;
              font-size: 10pt;
              font-style: italic;
              border-top: none;
              font-weight: normal;
              display: flex;
              justify-content: space-between;
            }

            .catatan-penting em b {
              text-decoration: underline;
              font-style: italic;
            }

            .catatan-penting .date-note {
              font-weight: bold;
              font-style: normal;
            }

            /* Footer Bottom */
            .footer-bottom {
              position: absolute;
              bottom: 0.5cm;
              left: 0.5cm;
              right: 0.5cm;
              font-size: 9pt;
              border-top: 1px solid black;
              padding-top: 8px;
            }

            .partial-info {
              display: flex;
              justify-content: space-between;
              align-items: center;
              margin-bottom: 6px;
              font-weight: normal;
            }

            .partial-info > div:first-child {
              white-space: nowrap;
            }

            .partial-gin-no {
              display: flex;
              align-items: center;
              gap: 6px;
            }

            .partial-gin-no label {
              white-space: nowrap;
            }

            .partial-gin-no input {
              width: 70px;
              text-align: center;
              font-size: 9pt;
              padding: 2px 4px;
              border: 1px solid #000;
            }

            .warning-text {
              font-weight: bold;
              font-style: italic;
              font-size: 10pt;
            }

            .footer-table {
              width: 100%;
              border-collapse: collapse;
              border: 1px solid black;
              margin-bottom: 5px;
            }

            .footer-table th,
            .footer-table td {
              border: 1px solid black;
              padding: 3px 5px;
              text-align: left;
              font-weight: normal;
            }

            .footer-note {
              font-size: 8pt;
              font-style: italic;
              font-weight: normal;
            }

            @media print {
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
    };

    // Print PDF document
    const printPdf = async () => {
      if (isLoading.value) {
        console.warn("Data is still loading. Please wait before printing PDF.");
        return;
      }

      const element = document.getElementById('printDocument');
      const clonedElement = element.cloneNode(true);

      // Remove any print controls from cloned element
      const printControls = clonedElement.querySelectorAll('.no-print');
      printControls.forEach(control => control.remove());

      const container = document.createElement('div');
      container.style.position = 'static';
      container.style.width = '210mm';
      container.style.backgroundColor = 'white';
      container.style.padding = '0';
      container.style.margin = '0';
      container.appendChild(clonedElement);

      document.body.appendChild(container);

      const opt = {
        margin: 0,
        filename: `WorkOrder_${workOrderData.value?.wo_number || 'document'}.pdf`,
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
    };

    const formatDate = (date) => {
      if (!date) return '';
      const d = new Date(date);
      return d.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
      });
    };

    const formatNumber = (number) => {
      if (!number) return '';
      return new Intl.NumberFormat('en-US').format(number);
    };

    const loadWorkOrderData = async () => {
      const woId = route.params.id;
      if (!woId) {
        error.value = 'Work Order ID is required';
        return;
      }

      isLoading.value = true;
      error.value = null;

      try {
        const response = await axios.get(`/work-orders/${woId}`);
        workOrderData.value = response.data.data;

        // Load BOM materials and tapes if available
        if (workOrderData.value.bom && workOrderData.value.bom.bom_lines) {
          const bomMaterials = [];
          const bomTapes = [];

          workOrderData.value.bom.bom_lines.forEach((bomLine) => {
            const tapeMatPcc = bomLine.item?.tape_mat_pcc || '';
            const itemData = {
              code: bomLine.item?.item_code || '□□□□□□',
              name: bomLine.item?.name || '',
              size: bomLine.item?.description || '',
              yield: bomLine.yield_ratio ? bomLine.yield_ratio.toFixed(2) : '',
              issue_qty: bomLine.quantity || '0.00000',
              qty_mtr: '□□□□□□'
            };

            if (tapeMatPcc.toLowerCase() === 'tape') {
              bomTapes.push(itemData);
            } else if (tapeMatPcc.toLowerCase() === 'material') {
              bomMaterials.push(itemData);
            }
          });

          materials.value = bomMaterials;
          tapes.value = bomTapes;
        } else {
          // Keep default fallback data if no BOM data available
          materials.value = [
            {
              code: '□□□□□□',
              name: 'TORAY PEF/2MM',
              size: '2MMX1000MMX200M',
              yield: '82,784',
              issue_qty: '0.02885',
              qty_mtr: '□□□□□□'
            },
            {
              code: '□□□□□□',
              name: '',
              size: '',
              yield: '',
              issue_qty: '0.00000',
              qty_mtr: '□□□□□□'
            },
            {
              code: '□□□□□□',
              name: '',
              size: '',
              yield: '',
              issue_qty: '0.00000',
              qty_mtr: '□□□□□□'
            }
          ];

          tapes.value = [
            {
              code: 'N500A/1210',
              name: '',
              size: '',
              yield: '20,696',
              issue_qty: '0.11538 / RL'
            },
            {
              code: '',
              name: '',
              size: '',
              yield: '',
              issue_qty: '0.00000'
            }
          ];
        }

        // Auto-print if requested in the URL
        if (route.query.autoprint === 'true') {
          setTimeout(() => {
            printDocument();
          }, 1000);
        }

      } catch (err) {
        console.error('Error loading work order data:', err);

        if (err.response && err.response.status === 404) {
          error.value = `Work Order with ID ${woId} not found`;
        } else if (err.response && err.response.status === 401) {
          error.value = 'You are not authorized to view this work order';
        } else {
          error.value = 'Failed to load work order data. Please try again.';
        }
      } finally {
        isLoading.value = false;
      }
    };

    // Navigation
    const goBack = () => {
      router.push(`/work-orders/${route.params.id}`);
    };

    onMounted(() => {
      loadWorkOrderData();
    });

    return {
      workOrderData,
      materials,
      tapes,
      workflows,
      flowChartHistory,
      isLoading,
      error,
      printDocument,
      printPdf,
      formatDate,
      formatNumber,
      loadWorkOrderData,
      goBack
    };
  }
};
</script>

<style>
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

.btn-primary:hover:not(:disabled) {
  background-color: #047857;
}

.btn-primary:disabled {
  background-color: #cccccc;
  cursor: not-allowed;
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

.btn-danger:hover:not(:disabled) {
  background-color: #dc2626;
}

.btn-danger:disabled {
  background-color: #cccccc;
  cursor: not-allowed;
}

/* Loading spinner styles */
.loading-container {
  text-align: center;
  margin: 50px 0;
  padding: 20px;
}

.spinner-border {
  width: 3rem;
  height: 3rem;
  border: 0.4em solid rgba(0,0,0,.1);
  border-right-color: transparent;
  border-radius: 50%;
  animation: spinner-border .75s linear infinite;
}

@keyframes spinner-border {
  to { transform: rotate(360deg); }
}

.alert {
  padding: 1rem;
  margin: 1rem 0;
  border-radius: 0.375rem;
}

.alert-danger {
  background-color: #fef2f2;
  color: #991b1b;
  border: 1px solid #fecaca;
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

.work-order-document {
  width: 100%;
  min-height: 297mm;
  background-color: white;
  font-family: Arial, sans-serif;
  box-sizing: border-box;
  position: relative;
}

/* Container Styles */
.container {
  font-family: Arial, sans-serif;
  font-size: 10pt;
  color: black;
  padding: 10px;
  background: white;
  position: relative;
  min-height: calc(297mm - 20px);
  padding-bottom: 120px; /* Space for footer */
}

/* Header */
.header-top {
  display: flex;
  justify-content: space-between;
  margin-bottom: 12px;
  font-size: 11pt;
}

.header-top .jo-no {
  font-weight: normal;
}

.header-top .jo-no b {
  font-weight: bold;
  font-size: 13pt;
}

.right-dates {
  text-align: right;
  font-weight: normal;
  font-size: 11pt;
}

.right-dates .prod-date b,
.right-dates .delivery-date b {
  font-size: 16pt;
  font-weight: bold;
  letter-spacing: 1px;
}

/* Main body flex container */
.main-body {
  display: flex;
  justify-content: space-between;
  margin-bottom: 12px;
  gap: 20px;
}

/* Left Section */
.left-section {
  width: 53%;
  font-size: 9pt;
}

.left-section dl {
  margin-bottom: 12px;
}

.left-section .row {
  display: flex;
  width: 100%;
  gap: 5px 25px;
  align-items: center;
  margin-bottom: 3px;
}

.left-section dt,
.left-section dd {
  margin: 0;
  padding: 0;
  line-height: 1.3;
  white-space: nowrap;
}

.left-section dt {
  font-weight: bold;
  flex: 0 0 90px;
}

.left-section dd {
  flex: 0 0 95px;
}

.left-section dd.bold-dd {
  font-weight: bold;
  white-space: normal;
}

.cavity-punch-knife {
  display: flex;
  justify-content: space-between;
  margin-top: 5px;
  margin-bottom: 5px;
  align-items: flex-start;
  flex-wrap: nowrap;
}

.cavity-punch-wrapper {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.cavity-punch-knife .item {
  display: flex;
  gap: 3px 8px;
  align-items: center;
  white-space: nowrap;
}

.cavity-punch-knife dt {
  font-weight: bold;
  flex: none;
}

.cavity-punch-knife dd {
  flex: none;
  min-width: 30px;
}

.single-knife-inline {
  font-weight: bold;
  font-size: 18pt;
  letter-spacing: 1px;
  color: black;
  border: 1.5px solid red;
  padding: 4px 12px;
  user-select: none;
  white-space: nowrap;
}

.mat-codes {
  display: flex;
  flex-wrap: nowrap;
  justify-content: space-between;
  gap: 10px;
  font-size: 8pt;
  margin-top: 10px;
}

.mat-code-item {
  flex: 1;
  min-width: 150px;
  max-width: 32%;
  line-height: 1.3;
}

.mat-code-item b {
  font-weight: bold;
}

.mat-code-item span {
  display: inline-block;
  width: 70px;
}

.tape-codes {
  display: flex;
  justify-content: space-between;
  font-size: 8pt;
  margin-top: 10px;
}

.tape-codes > div {
  width: 31%;
  line-height: 1.3;
}

.tape-codes > div b {
  font-weight: bold;
}

.barcode-section {
  display: flex;
  justify-content: space-between;
  margin-top: 10px;
  font-size: 9pt;
}

.barcode-section > div {
  width: 31%;
  text-align: center;
}

/* Middle Section */
.middle-section {
  width: 20%;
  font-size: 9pt;
  text-align: left;
  align-self: flex-start;
}

.info-table.issue-important {
  width: 100%;
  border-collapse: collapse;
}

.info-table.issue-important td {
  padding: 0 8px 0 0;
  vertical-align: top;
  font-weight: normal;
  white-space: nowrap;
}

.info-table.issue-important td:first-child {
  font-weight: bold;
  width: 60px;
}

/* Right Section */
.right-section {
  width: 25%;
  font-size: 9pt;
  text-align: right;
  line-height: 1.3;
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.flow-chart-history {
  width: 100%;
  border-collapse: collapse;
  border: 1px dotted black;
  font-size: 9pt;
}

.flow-chart-history thead th {
  border: 1px dotted black;
  font-weight: bold;
  padding: 2px;
  text-align: center;
  background: none;
}

.flow-chart-history tbody td {
  border: 1px dotted black;
  text-align: center;
  padding: 3px 2px;
  height: 20px;
  font-weight: normal;
}

/* Workflow Table */
.workflow-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 8pt;
  margin-top: 10px;
  text-align: left;
}

.workflow-table th,
.workflow-table td {
  border: 1px dotted black;
  padding: 2px 6px;
  vertical-align: top;
  font-weight: normal;
}

.workflow-table th {
  font-weight: bold;
  height: 24px;
}

/* Catatan Penting */
.catatan-penting {
  margin-top: 10px;
  font-size: 10pt;
  font-style: italic;
  border-top: none;
  font-weight: normal;
  display: flex;
  justify-content: space-between;
}

.catatan-penting em b {
  text-decoration: underline;
  font-style: italic;
}

.catatan-penting .date-note {
  font-weight: bold;
  font-style: normal;
}

/* Footer Bottom */
.footer-bottom {
  position: absolute;
  bottom: 10px;
  left: 10px;
  right: 10px;
  font-size: 9pt;
  border-top: 1px dotted black;
  padding-top: 8px;
}

.partial-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 6px;
  font-weight: normal;
}

.partial-info > div:first-child {
  white-space: nowrap;
}

.partial-gin-no {
  display: flex;
  align-items: center;
  gap: 6px;
}

.partial-gin-no label {
  white-space: nowrap;
}

.partial-gin-no input {
  width: 70px;
  text-align: center;
  font-size: 9pt;
  padding: 2px 4px;
  border: 1px solid #ccc;
}

.warning-text {
  font-weight: bold;
  font-style: italic;
  font-size: 10pt;
}

.footer-table {
  width: 100%;
  border-collapse: collapse;
  border: 1px dotted black;
  margin-bottom: 5px;
}

.footer-table th,
.footer-table td {
  border: 1px dotted black;
  padding: 3px 5px;
  text-align: left;
  font-weight: normal;
}

.footer-note {
  font-size: 8pt;
  font-style: italic;
  font-weight: normal;
}

.container::after {
  content: "";
  display: block;
  clear: both;
}

/* Print styles */
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

  .work-order-document {
    width: 100%;
  }

  .container {
    padding: 1rem;
    padding-bottom: 1rem; /* Reset bottom padding for mobile */
  }

  .main-body {
    flex-direction: column;
    gap: 1rem;
  }

  .left-section,
  .middle-section,
  .right-section {
    width: 100%;
  }

  .workflow-table {
    font-size: 7pt;
  }

  .workflow-table th,
  .workflow-table td {
    padding: 1px 3px;
  }

  .footer-bottom {
    position: relative;
    bottom: auto;
    left: auto;
    right: auto;
    margin-top: 2rem;
  }
}
</style>
