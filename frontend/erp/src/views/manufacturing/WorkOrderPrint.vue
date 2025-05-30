<!-- src/views/manufacturing/WorkOrderPrint.vue -->
<template>
  <div class="work-order-print-page">
    <!-- Print Controls (Hidden in print) -->
    <div class="print-controls no-print">
      <div class="controls-bar">
        <button @click="goBack" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i> Back
        </button>
        <button @click="printDocument" class="btn btn-primary">
          <i class="fas fa-print"></i> Print
        </button>
        <button @click="downloadPDF" class="btn btn-success">
          <i class="fas fa-download"></i> Download PDF
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="loading-state">
      <div class="spinner">
        <i class="fas fa-spinner fa-spin"></i>
      </div>
      <p>Loading work order data...</p>
    </div>

    <!-- Print Document -->
    <div v-else class="print-document">
      <!-- Header Section -->
      <div class="document-header">
        <div class="header-top">
          <div class="company-section">
            <!-- <h1>WORK ORDER</h1> -->
            <!-- <div class="standard-label">Standard</div> -->
          </div>
          <div class="dates-section">
            <!-- <div class="date-row">
              <span class="date-label">Issue Date</span>
              <span class="date-value">{{ formatDate(workOrder.wo_date) }}</span>
            </div>
            <div class="date-row">
              <span class="date-label">Prod Date:</span>
              <span class="date-value">{{ formatDate(workOrder.planned_start_date) }}</span>
            </div>
            <div class="date-row">
              <span class="date-label">Print Date</span>
              <span class="date-value">{{ formatDate(new Date()) }}</span>
            </div> -->
          </div>
        </div>

        <div class="header-main">
          <div class="left-info">
            <div class="info-row">
              <span class="label">Customer</span>
              <span class="value">{{ workOrder.customer_name || 'Internal Production' }}</span>
            </div>
            <div class="info-row">
              <span class="label">JO No:</span>
              <span class="value wo-number">{{ workOrder.wo_number }}</span>
            </div>
            <div class="info-row">
              <span class="label">Cust Code</span>
              <span class="value">{{ workOrder.customer_code || workOrder.item?.item_code }}</span>
            </div>
          </div>

          <div class="center-info">
            <div class="info-row">
              <span class="label">Part Name</span>
              <span class="value part-name">{{ workOrder.item?.name || '-' }}</span>
            </div>
            <div class="info-row">
              <span class="label">Intern Code</span>
              <span class="value">{{ workOrder.item?.item_code || '-' }}</span>
            </div>
            <div class="info-row">
              <span class="label">Size</span>
              <span class="value">{{ getItemSize() }}</span>
            </div>
          </div>

          <div class="right-info">
            <div class="info-row">
              <span class="label">Order Qty</span>
              <span class="value qty-value">{{ workOrder.planned_quantity }}</span>
              <span class="uom">{{ workOrder.item?.unitOfMeasure?.name || 'PCS' }}</span>
            </div>
            <div class="info-row">
              <span class="label">Prod. Qty</span>
              <span class="value qty-value">{{ workOrder.completed_quantity || 0 }}</span>
              <span class="uom">{{ workOrder.item?.unitOfMeasure?.name || 'PCS' }}</span>
            </div>
            <div class="info-row">
              <span class="label">Delivery Date:</span>
              <span class="value">{{ formatDate(workOrder.planned_end_date) }}</span>
            </div>
            <div class="date-row">
              <span class="date-label">Prod Date:</span>
              <span class="date-value">{{ formatDate(workOrder.planned_start_date) }}</span>
            </div>
            <div class="date-row">
              <span class="date-label">Print Date</span>
              <span class="date-value">{{ formatDate(new Date()) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Process Information -->
      <div class="process-section">
        <div class="process-header">
          <div class="process-info">
            <div class="info-item">
              <span class="label">Process</span>
              <span class="value">{{ getMainProcess() }}</span>
            </div>
            <div class="info-item">
              <span class="label">Machine</span>
              <span class="value">{{ getMainMachine() }}</span>
            </div>
            <div class="info-item">
              <span class="label">Setup (mnt)</span>
              <span class="value">{{ getTotalSetupTime() }}</span>
            </div>
            <div class="info-item">
              <span class="label">Process (mnt)</span>
              <span class="value">{{ getTotalProcessTime() }}</span>
            </div>
          </div>
          <div class="process-details">
            <div class="info-item">
              <span class="label">ARM NO</span>
              <span class="value">{{ workOrder.arm_no || '-' }}</span>
            </div>
            <div class="info-item">
              <span class="label">Cavity</span>
              <span class="value">{{ workOrder.cavity || '1' }}</span>
            </div>
            <div class="info-item">
              <span class="label">Total Punch</span>
              <span class="value">{{ getTotalPunch() }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Materials Section -->
      <div class="materials-section">
        <h3>Materials</h3>
        <div class="materials-grid">
          <div v-for="(material, index) in materials" :key="index" class="material-block">
            <div class="material-header">
              <span class="material-label">Material {{ index + 1 }}</span>
            </div>
            <div class="material-details">
              <div class="detail-row">
                <span class="label">MatCode{{ index + 1 }}:</span>
                <span class="value">{{ material.item_code }}</span>
              </div>
              <div class="detail-row">
                <span class="label">MatNm {{ index + 1 }}:</span>
                <span class="value">{{ material.item_name }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Size:</span>
                <span class="value">{{ getMaterialSize(material) }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Issue Qty:</span>
                <span class="value">{{ material.required_quantity }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Yield:</span>
                <span class="value">{{ material.yield || getCalculatedYield(material) }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Qty/Mtr:</span>
                <span class="value">{{ material.qty_per_meter || calculateQtyPerMeter(material) }}</span>
              </div>
            </div>
            <div class="lot-tracking">
              <div class="lot-row">
                <span class="label">Lot Material</span>
                <input type="text" class="lot-input" placeholder="Lot No." />
              </div>
              <div class="lot-row">
                <span class="label">Operator</span>
                <input type="text" class="lot-input" placeholder="Operator" />
              </div>
            </div>
          </div>

          <!-- Adhesive/Tape Materials -->
          <div v-for="(tape, index) in tapeAdhesives" :key="`tape-${index}`" class="material-block">
            <div class="material-header">
              <span class="material-label">Tape{{ index + 1 }}</span>
            </div>
            <div class="material-details">
              <div class="detail-row">
                <span class="label">TapeCode{{ index + 1 }}:</span>
                <span class="value">{{ tape.item_code }}</span>
              </div>
              <div class="detail-row">
                <span class="label">TapeNm{{ index + 1 }}:</span>
                <span class="value">{{ tape.item_name }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Size:</span>
                <span class="value">{{ getMaterialSize(tape) }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Issue Qty:</span>
                <span class="value">{{ tape.required_quantity }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Yield:</span>
                <span class="value">{{ tape.yield || getCalculatedYield(tape) }}</span>
              </div>
            </div>
            <div class="lot-tracking">
              <div class="lot-row">
                <span class="label">Lot adhesive</span>
                <input type="text" class="lot-input" placeholder="Lot No." />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quality Control Section -->
      <div class="quality-section">
        <div class="quality-grid">
          <div class="quality-item">
            <span class="label">No. of Plastic</span>
            <input type="number" class="qty-input" />
          </div>
          <div class="quality-item">
            <span class="label">Reject Qty</span>
            <input type="number" class="qty-input" value="0" />
          </div>
          <div class="quality-item">
            <span class="label">No. of Carton</span>
            <input type="number" class="qty-input" />
          </div>
          <div class="quality-item">
            <span class="label">Tolerance Qty</span>
            <input type="text" class="qty-input" />
          </div>
          <div class="quality-item">
            <span class="label">Set Jump</span>
            <input type="text" class="qty-input" value="0.000" />
          </div>
          <div class="quality-item">
            <span class="label">Total</span>
            <input type="number" class="qty-input" />
          </div>
        </div>

        <div class="partial-section">
          <div class="partial-row">
            <span class="label">* Partial / No Partial</span>
            <div class="checkbox-group">
              <label><input type="checkbox" /> Partial</label>
              <label><input type="checkbox" /> No Partial</label>
            </div>
          </div>
          <div class="partial-row">
            <span class="label">Partial GIN No:</span>
            <input type="text" class="gin-input" placeholder="/" />
          </div>
          <div class="important-note">
            <strong>PASTIKAN LOT NO. HARUS DI INPUT !!!</strong>
          </div>
        </div>
      </div>

      <!-- Process Flow Chart -->
      <div class="flow-chart-section">
        <h3>Work Flow</h3>
        <table class="flow-chart-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Process</th>
              <th>Qty</th>
              <th>Name</th>
              <th>Notes</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(operation, index) in operations" :key="index">
              <td>{{ formatDate(operation.actual_start || operation.scheduled_start) }}</td>
              <td class="process-cell">
                <div class="process-code">{{ operation.operation_code || getOperationCode(operation, index) }}</div>
                <div class="process-name">{{ operation.operation_name }}</div>
              </td>
              <td>{{ getOperationQuantity(operation) }}</td>
              <td>{{ operation.operator_name || '-' }}</td>
              <td class="notes-cell">{{ operation.notes || getOperationNotes(operation) }}</td>
            </tr>
            <!-- Add empty rows for manual entry -->
            <tr v-for="n in 3" :key="`empty-${n}`" class="empty-row">
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Important Notes -->
      <div class="notes-section">
        <div class="notes-header">
          <strong>Catatan Penting:</strong>
        </div>
        <div class="notes-content">
          {{ workOrder.notes || 'Pastikan semua proses sesuai dengan spesifikasi. Lakukan quality check di setiap tahap.' }}
        </div>
        <div class="notes-date">
          {{ formatDate(workOrder.wo_date) }}
        </div>
      </div>

      <!-- Additional Info -->
      <div class="additional-info">
        <div class="info-grid">
          <div class="info-item">
            <span class="label">Issue WH:</span>
            <span class="value">{{ workOrder.issue_warehouse || formatDate(workOrder.wo_date) }}</span>
          </div>
          <div class="info-item">
            <span class="label">ECN#</span>
            <span class="value">{{ workOrder.ecn_number || '-' }}</span>
          </div>
          <div class="info-item">
            <span class="label">Check</span>
            <div class="checkbox-group">
              <label><input type="checkbox" /> OK</label>
              <label><input type="checkbox" /> NG</label>
            </div>
          </div>
        </div>
      </div>

      <!-- Sub Materials -->
      <div class="sub-materials">
        <div class="sub-row">
          <span class="label">Sub Material:</span>
          <input type="text" class="sub-input" />
          <input type="text" class="sub-input" />
          <input type="text" class="sub-input" />
          <input type="text" class="sub-input" />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

export default {
  name: 'WorkOrderPrint',
  setup() {
    const route = useRoute();
    const router = useRouter();

    const isLoading = ref(true);
    const workOrder = ref({});
    const materials = ref([]);
    const tapeAdhesives = ref([]);
    const operations = ref([]);

    const loadWorkOrderData = async () => {
      try {
        isLoading.value = true;

        // Load work order details
        const response = await axios.get(`/work-orders/${route.params.id}`);
        workOrder.value = response.data.data;

        // Load materials from BOM
        if (workOrder.value.bom_id) {
          const bomResponse = await axios.get(`/boms/${workOrder.value.bom_id}/lines`);
          const allMaterials = bomResponse.data.data.map(line => {
            let requiredQty = line.quantity * workOrder.value.planned_quantity;
            if (line.is_yield_based && line.yield_ratio > 0) {
              requiredQty = requiredQty / line.yield_ratio;
            }
            return {
              ...line,
              item_name: line.item?.name || 'Unknown',
              item_code: line.item?.item_code || 'Unknown',
              required_quantity: Math.ceil(requiredQty),
              issued_quantity: line.issued_quantity || 0
            };
          });

          // Separate materials and adhesives/tapes
          materials.value = allMaterials.filter(m =>
            !m.item_name.toLowerCase().includes('tape') &&
            !m.item_name.toLowerCase().includes('adhesive')
          );

          tapeAdhesives.value = allMaterials.filter(m =>
            m.item_name.toLowerCase().includes('tape') ||
            m.item_name.toLowerCase().includes('adhesive')
          );
        }

        // Load operations
        const operationsResponse = await axios.get(`/work-orders/${route.params.id}/operations`);
        operations.value = operationsResponse.data.data.map(op => ({
          ...op,
          operation_name: op.routingOperation?.operation_name || 'Unknown',
          work_center_name: op.routingOperation?.workCenter?.name || 'Unknown',
          setup_time: op.routingOperation?.setup_time || 0,
          run_time: op.routingOperation?.run_time || 0,
          sequence: op.routingOperation?.sequence || 0
        }));

      } catch (error) {
        console.error('Error loading work order data:', error);
      } finally {
        isLoading.value = false;
      }
    };

    const formatDate = (dateString) => {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
      });
    };

    const getItemSize = () => {
      const item = workOrder.value.item;
      if (!item) return '';

      const dimensions = [];
      if (item.length) dimensions.push(`${item.length}MM`);
      if (item.width) dimensions.push(`${item.width}MM`);
      if (item.thickness) dimensions.push(`${item.thickness}MM`);

      return dimensions.join('X') || '';
    };

    const getMaterialSize = (material) => {
      const item = material.item;
      if (!item) return '';

      const dimensions = [];
      if (item.thickness) dimensions.push(`${item.thickness}MM`);
      if (item.length) dimensions.push(`${item.length}MM`);
      if (item.width) dimensions.push(`${item.width}MM`);

      return dimensions.join('X') || '';
    };

    const getMainProcess = () => {
      return operations.value.length > 0 ? operations.value[0].operation_name : 'SINGLE KNIFE';
    };

    const getMainMachine = () => {
      return operations.value.length > 0 ? operations.value[0].work_center_name : '';
    };

    const getTotalSetupTime = () => {
      return operations.value.reduce((total, op) => total + (op.setup_time || 0), 0);
    };

    const getTotalProcessTime = () => {
      return operations.value.reduce((total, op) => total + (op.run_time || 0), 0);
    };

    const getTotalPunch = () => {
      return workOrder.value.planned_quantity || 0;
    };

    const getCalculatedYield = (material) => {
      if (material.yield_ratio) return material.yield_ratio;
      return Math.floor(workOrder.value.planned_quantity / (material.required_quantity || 1));
    };

    const calculateQtyPerMeter = (material) => {
      const item = material.item;
      if (item && item.length) {
        return (1000 / item.length).toFixed(5);
      }
      return '0.00000';
    };

    const getOperationCode = (operation, index) => {
      const codes = ['C', 'L', 'P', 'PK'];
      return codes[index] || `OP${index + 1}`;
    };

    const getOperationQuantity = (operation) => {
      return operation.completed_quantity || workOrder.value.planned_quantity || 0;
    };

    const getOperationNotes = (operation) => {
      const defaultNotes = [
        'CEK MATERIAL SEBELUM PROSES',
        'LAMINATING SESUAIKAN MATERIAL',
        'POTONG YANG SIKU',
        'PACKING YANG RAPI'
      ];
      return operation.notes || defaultNotes[operation.sequence - 1] || 'SESUAIKAN DENGAN ORDER';
    };

    const goBack = () => {
      router.go(-1);
    };

    const printDocument = () => {
      window.print();
    };

    const downloadPDF = () => {
      // This would require a PDF generation library like jsPDF or html2pdf
      // For now, just trigger print dialog
      window.print();
    };

    onMounted(() => {
      loadWorkOrderData();
    });

    return {
      isLoading,
      workOrder,
      materials,
      tapeAdhesives,
      operations,
      formatDate,
      getItemSize,
      getMaterialSize,
      getMainProcess,
      getMainMachine,
      getTotalSetupTime,
      getTotalProcessTime,
      getTotalPunch,
      getCalculatedYield,
      calculateQtyPerMeter,
      getOperationCode,
      getOperationQuantity,
      getOperationNotes,
      goBack,
      printDocument,
      downloadPDF
    };
  }
};
</script>

<style scoped>
.work-order-print-page {
  background: #f5f5f5;
  min-height: 100vh;
  padding: 20px;
}

.print-controls {
  background: white;
  padding: 15px;
  border-radius: 8px;
  margin-bottom: 20px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.controls-bar {
  display: flex;
  gap: 10px;
}

.btn {
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-primary {
  background: #007bff;
  color: white;
}

.btn-success {
  background: #28a745;
  color: white;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 400px;
  background: white;
  border-radius: 8px;
}

.spinner {
  font-size: 2rem;
  color: #007bff;
  margin-bottom: 10px;
}

.print-document {
  background: white;
  width: 210mm;
  margin: 0 auto;
  padding: 10mm;
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
  font-family: Arial, sans-serif;
  font-size: 12px;
  line-height: 1.3;
}

/* Header Styles */
.document-header {
  border: 2px solid #000;
  margin-bottom: 15px;
}

.header-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 15px;
  border-bottom: 1px solid #000;
  background: #f8f9fa;
}

.company-section h1 {
  margin: 0;
  font-size: 20px;
  font-weight: bold;
}

.standard-label {
  font-size: 10px;
  margin-top: 2px;
}

.dates-section {
  text-align: right;
}

.date-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 2px;
  min-width: 150px;
}

.date-label {
  font-weight: bold;
  margin-right: 10px;
}

.header-main {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  padding: 15px;
  gap: 20px;
}

.info-row {
  display: flex;
  margin-bottom: 8px;
  align-items: center;
}

.label {
  font-weight: bold;
  min-width: 80px;
  margin-right: 10px;
}

.value {
  flex: 1;
}

.wo-number {
  font-size: 16px;
  font-weight: bold;
  color: #007bff;
}

.part-name {
  font-weight: bold;
  color: #333;
}

.qty-value {
  font-weight: bold;
  margin-right: 5px;
}

.uom {
  font-size: 10px;
  color: #666;
}

/* Process Section */
.process-section {
  border: 1px solid #000;
  margin-bottom: 15px;
  padding: 10px;
}

.process-header {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 20px;
}

.process-info, .process-details {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
}

.info-item {
  display: flex;
  justify-content: space-between;
}

/* Materials Section */
.materials-section {
  margin-bottom: 20px;
}

.materials-section h3 {
  margin: 0 0 10px 0;
  padding: 8px;
  background: #e9ecef;
  border: 1px solid #000;
  font-size: 14px;
}

.materials-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 15px;
}

.material-block {
  border: 1px solid #333;
  padding: 8px;
}

.material-header {
  background: #f8f9fa;
  padding: 4px 8px;
  margin: -8px -8px 8px -8px;
  border-bottom: 1px solid #333;
  font-weight: bold;
}

.material-details {
  margin-bottom: 10px;
}

.detail-row {
  display: flex;
  margin-bottom: 4px;
  font-size: 10px;
}

.lot-tracking {
  border-top: 1px solid #ddd;
  padding-top: 8px;
}

.lot-row {
  display: flex;
  align-items: center;
  margin-bottom: 4px;
}

.lot-input {
  width: 80px;
  height: 20px;
  border: 1px solid #ccc;
  padding: 2px;
  font-size: 10px;
  margin-left: 5px;
}

/* Quality Section */
.quality-section {
  margin-bottom: 20px;
  border: 1px solid #000;
  padding: 10px;
}

.quality-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  margin-bottom: 15px;
}

.quality-item {
  display: flex;
  align-items: center;
  font-size: 10px;
}

.qty-input {
  width: 60px;
  height: 20px;
  border: 1px solid #ccc;
  padding: 2px;
  font-size: 10px;
  margin-left: 5px;
}

.partial-section {
  border-top: 1px solid #ddd;
  padding-top: 10px;
}

.partial-row {
  display: flex;
  align-items: center;
  margin-bottom: 8px;
}

.checkbox-group {
  display: flex;
  gap: 10px;
  margin-left: 10px;
}

.checkbox-group label {
  display: flex;
  align-items: center;
  gap: 3px;
  font-size: 10px;
}

.gin-input {
  width: 100px;
  height: 20px;
  border: 1px solid #ccc;
  padding: 2px;
  margin-left: 10px;
}

.important-note {
  margin-top: 10px;
  color: #dc3545;
  font-size: 11px;
  text-align: center;
}

/* Flow Chart Section */
.flow-chart-section {
  margin-bottom: 20px;
}

/* .flow-chart-section h3 {
  margin: 0 0 10px 0;
  padding: 8px;
  background: #e9ecef;
  border: 1px solid #000;
  font-size: 14px;
} */

.flow-chart-table {
  width: 100%;
  border-collapse: collapse;
  border: 1px solid #000;
}

.flow-chart-table th,
.flow-chart-table td {
  border: 1px solid #000;
  padding: 6px;
  text-align: left;
  font-size: 10px;
}

.flow-chart-table th {
  background: #f8f9fa;
  font-weight: bold;
  text-align: center;
}

.process-cell {
  min-width: 120px;
}

.process-code {
  font-weight: bold;
  margin-bottom: 2px;
}

.process-name {
  font-size: 9px;
  color: #666;
}

.notes-cell {
  font-size: 9px;
  max-width: 150px;
}

.empty-row {
  height: 25px;
}

/* Notes Section */
.notes-section {
  border: 1px solid #000;
  padding: 10px;
  margin-bottom: 15px;
}

.notes-header {
  margin-bottom: 8px;
  font-size: 12px;
}

.notes-content {
  min-height: 40px;
  font-size: 10px;
  line-height: 1.4;
  margin-bottom: 8px;
}

.notes-date {
  text-align: right;
  font-size: 10px;
  font-weight: bold;
}

/* Additional Info */
.additional-info {
  margin-bottom: 15px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
}

.sub-materials {
  margin-bottom: 20px;
}

.sub-row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.sub-input {
  width: 100px;
  height: 20px;
  border: 1px solid #ccc;
  padding: 2px;
  font-size: 10px;
}

/* Print Styles */
@media print {
  .work-order-print-page {
    background: white;
    padding: 0;
  }

  .no-print {
    display: none !important;
  }

  .print-document {
    width: 100%;
    margin: 0;
    padding: 5mm;
    box-shadow: none;
    font-size: 11px;
  }

  .materials-grid {
    grid-template-columns: 1fr 1fr;
  }

  .quality-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .flow-chart-table {
    page-break-inside: avoid;
  }

  .material-block {
    page-break-inside: avoid;
  }

  .document-header,
  .process-section,
  .materials-section,
  .quality-section,
  .flow-chart-section,
  .notes-section {
    page-break-inside: avoid;
  }
}

@page {
  size: A4;
  margin: 8mm;
}
</style>
