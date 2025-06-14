<template>
  <div class="container">
    <!-- Header atas (JO No dan Prod Date, Delivery Date) -->
    <div class="header-top">
      <div class="jo-no">
        <span>JO No :</span>
        <b>J2-25-00004</b>
      </div>

      <div class="right-dates">
        <div class="prod-date">
          Prod Date : <b>17 Dec 2024</b>
        </div>
        <div class="delivery-date">
          Delivery Date : <b>02 Jan 2025</b>
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
              <dd>PT. YAMAHA MUSIC MANUFACT</dd>
          </div>
          <div class="row">
              <dt>Intern Code</dt>
              <dd>YMM0001</dd>
          </div>
          <div class="row">
              <dt>Cust Code</dt>
              <dd>V630000</dd>
          </div>
          <div class="row">
              <dt>Part Name</dt>
              <dd class="bold-dd">CUSHION - TORAY PEF</dd>
          </div>
          <div class="row">
              <dt>Size</dt>
              <dd>2MMX5MMX480MM</dd>
          </div>
          <div class="row">
              <dt>Process</dt>
              <dd>SINGLE KNIFE</dd>
          </div>

          <div class="row cavity-punch-knife">
            <div class="item">
              <dt>Cavity</dt>
              <dd>1</dd>
            </div>
            <div class="item">
              <dt>Total Punch</dt>
              <dd></dd>
            </div>
            <div class="item single-knife-inline">
              SINGLE KNIFE
            </div>
          </div>

          <div class="row">
            <dt>ECN#</dt>
            <dd></dd>
          </div>
          <div class="row">
            <dt>Set Jump</dt>
            <dd>0.000</dd>
            <dd></dd>
          </div>
        </dl>

        <div class="mat-codes">
          <div>
            <div><b>MatCode 1</b> : <span>□□□□□□</span></div>
            <div><b>MatNm 1</b> : TORAY PEF/2MM</div>
            <div><b>Size :</b> 2MMX1000MMX200M</div>
            <div><b>Yield :</b> 82,784</div>
            <div><b>Issue Qty :</b> 0.02885 /</div>
            <div><b>Qty/Mtr :</b> □□□□□□</div>
          </div>
          <div>
            <div><b>MatCode 2</b> : <span>□□□□□□</span></div>
            <div><b>MatNm2 :</b></div>
            <div><b>Size :</b></div>
            <div><b>Yield :</b></div>
            <div><b>Issue Qty :</b> 0.00000 /</div>
            <div><b>Qty/Mtr :</b> □□□□□□</div>
          </div>
          <div>
            <div><b>MatCode 3</b> : <span>□□□□□□</span></div>
            <div><b>Mat Nm 3 :</b></div>
            <div><b>Size :</b></div>
            <div><b>Yield :</b></div>
            <div><b>Issue Qty :</b> 0.00000 /</div>
            <div><b>Qty/Mtr :</b> □□□□□□</div>
          </div>
        </div>

        <div class="tape-codes">
          <div>
            <div><b>TapeCode 1</b>: N500A/1210</div>
            <div><b>TapeNm1 :</b></div>
            <div><b>Size :</b></div>
            <div><b>Yield :</b> 20,696</div>
            <div><b>Issue Qty :</b> 0.11538 / RL</div>
          </div>
          <div>
            <div><b>TapeCode 2</b>:</div>
            <div><b>TapeNm 2 :</b></div>
            <div><b>Size :</b></div>
            <div><b>Yield :</b></div>
            <div><b>Issue Qty :</b> 0.00000 /</div>
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
            <tr><td>Issue WH :</td><td></td></tr>
            <tr><td>Issue Date</td><td>17 Dec 2024</td></tr>
            <tr><td>Print Date</td><td>17 May 2025</td></tr>
            <tr><td>Order Qty</td><td>2,396 PCS</td></tr>
            <tr><td>Prod. Qty</td><td>2,388 PCS</td></tr>
            <tr><td>Important</td><td></td></tr>
            <tr><td>Yield</td><td></td></tr>
            <tr><td>Size</td><td></td></tr>
            <tr><td>Qty</td><td></td></tr>
          </tbody>
          <!-- <div class="yield-block">
          <div><b>Yield</b> 199 PCS</div>
          <div><b>Size</b> 2MMX1000MMX480MM</div>
          <div><b>Qty</b> 12.00 SHEET</div>
          </div> -->
        </table>
      </div>

      <!-- Kanan: Bagian Yield dan Flow Chart History -->
      <div class="right-section">
        <!-- <div class="yield-block">
          <div><b>Yield</b> 199 PCS</div>
          <div><b>Size</b> 2MMX1000MMX480MM</div>
          <div><b>Qty</b> 12.00 SHEET</div>
        </div> -->

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
            <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Workflow Table (unchanged) -->
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
        <tr>
          <td>C</td>
          <td>CEK MATERIAL<br />SEBELUM PROSES</td>
          <td>-1.0 +1.0</td>
          <td></td>
          <td></td>
          <td>MANUAL</td>
          <td>2</td>
          <td>0.4</td>
          <td>2.4</td>
          <td></td>
        </tr>
        <tr>
          <td>L</td>
          <td>LAMIN SESUAIKAN MATERIAL<br />LAM.BAG.YG MENGKILAP</td>
          <td></td>
          <td></td>
          <td></td>
          <td>L-1</td>
          <td>15</td>
          <td>1.6</td>
          <td>16.6</td>
          <td></td>
        </tr>
        <tr>
          <td>P</td>
          <td>2MM X 1000MM X 480MM F/C<br />POTONG YANG SIKU</td>
          <td>-1.0 +1.0</td>
          <td></td>
          <td></td>
          <td>P-5</td>
          <td>15</td>
          <td>23.9</td>
          <td>38.9</td>
          <td></td>
        </tr>
        <tr>
          <td>P</td>
          <td>2MM X 5MM X 480 K/C<br />TIAP 50 BARIS F/C</td>
          <td>-0.3 +0.3</td>
          <td></td>
          <td></td>
          <td>P-20</td>
          <td>10</td>
          <td>23.9</td>
          <td>33.9</td>
          <td></td>
        </tr>
        <tr>
          <td>PK</td>
          <td>SESUAIKAN DENGAN ORDER<br />PACKING YANG RAPI</td>
          <td></td>
          <td></td>
          <td></td>
          <td>MANUAL</td>
          <td>5</td>
          <td>8.0</td>
          <td>13.0</td>
          <td></td>
        </tr>
      </tbody>
    </table>

    <!-- Catatan Penting (unchanged) -->
    <div class="catatan-penting">
      <em><b>Catatan Penting :</b></em>
      <span class="date-note">02/01/2025</span>
    </div>

    <!-- Footer (unchanged) -->
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
            <td>02/01/2025</td>
            <td></td>
          </tr>
        </tbody>
      </table>

      <div class="footer-note">
        *) Just streak (coret) if part partial or no partial
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "DetailReport",
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Libre+Barcode+39&display=swap');

.container {
  font-family: Arial, sans-serif;
  font-size: 10pt;
  color: black;
  max-width: 960px;
  margin: 0 auto;
  padding: 10px;
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

/* Default row style */
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

/* Baris khusus untuk Cavity, Total Punch, dan SINGLE KNIFE di satu garis */
.cavity-punch-knife {
  display: flex;
  gap: 12px;
  margin-top: 6px;
  margin-bottom: 10px;
  align-items: center;
  flex-wrap: nowrap;
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

/* Mat Codes & Tape Codes Layout */
.mat-codes,
.tape-codes {
  display: flex;
  justify-content: space-between;
  font-size: 8pt;
  margin-top: 10px;
}

.mat-codes > div,
.tape-codes > div {
  width: 31%;
  line-height: 1.3;
}

.mat-codes > div b,
.tape-codes > div b {
  font-weight: bold;
}

.mat-codes > div span {
  display: inline-block;
  width: 70px;
}

/* Barcode Section */
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

.barcode {
  font-family: 'Libre Barcode 39', cursive;
  font-size: 32pt;
  letter-spacing: 2px;
  user-select: none;
  margin-top: 2px;
}

/* Middle Section for Issue WH & Important */
.middle-section {
  width: 20%;
  font-size: 9pt;
  text-align: left;
  align-self: flex-start;
}

/* Table Issue WH sampai Important */
.info-table.issue-important {
  width: 100%;
  border-collapse: collapse;
}

.info-table.issue-important td {
  padding: 0 8px 0 0;
  vertical-align: top;
  font-weight: normal;
  white-space: nowrap;
  border-bottom: 1px solid #ddd;
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

.yield-block {
  font-weight: normal;
  font-size: 9pt;
  line-height: 1.4;
  text-align: left;
  margin-bottom: 10px;
}

.yield-block div {
  margin: 6px 0 0;
}

/* Flow Chart History */
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
  margin-top: 25px;
  font-size: 9pt;
  border-top: 1px dotted black;
  padding-top: 8px;
}

/* Partial Info */
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

/* Warning text */
.warning-text {
  font-weight: bold;
  font-style: italic;
  font-size: 10pt;
}

/* Footer table */
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

/* Footer note */
.footer-note {
  font-size: 8pt;
  font-style: italic;
  font-weight: normal;
}

/* Clear floats */
.container::after {
  content: "";
  display: block;
  clear: both;
}
</style>
