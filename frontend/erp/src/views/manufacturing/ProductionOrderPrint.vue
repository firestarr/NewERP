<template>
  <div class="report-container" ref="printArea">
    <!-- Header -->
    <header class="report-header">
      <img src="/logo.png" alt="Company Logo" class="logo" />
      <h1 class="title">Laporan Penjualan Bulanan</h1>
      <p class="date">Periode: Juni 2024</p>
    </header>

    <!-- Content Table -->
    <table class="report-table" cellspacing="0" cellpadding="0">
      <thead>
        <tr>
          <th>No</th>
          <th>Produk</th>
          <th>Qty Terjual</th>
          <th>Harga Satuan</th>
          <th>Total Pendapatan</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item, index) in data" :key="index">
          <td>{{ index + 1 }}</td>
          <td>{{ item.produk }}</td>
          <td>{{ item.qty }}</td>
          <td>{{ formatCurrency(item.harga) }}</td>
          <td>{{ formatCurrency(item.qty * item.harga) }}</td>
        </tr>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="4" class="total-label">Total Pendapatan</td>
          <td class="total-value">{{ formatCurrency(totalPendapatan) }}</td>
        </tr>
      </tfoot>
    </table>

    <!-- Footer -->
    <footer class="report-footer">
      <p>Dicetak pada: {{ tanggalCetak }}</p>
      <p>PT. Contoh Perusahaan</p>
    </footer>
  </div>
  <button @click="printReport">Print Laporan</button>
</template>

<script>
export default {
  data() {
    return {
      data: [
        { produk: "Produk A", qty: 10, harga: 15000 },
        { produk: "Produk B", qty: 5, harga: 23000 },
        { produk: "Produk C", qty: 12, harga: 12000 },
        // Tambah data sesuai laporan pdf asli
      ],
    };
  },
  computed: {
    totalPendapatan() {
      return this.data.reduce((sum, item) => sum + item.qty * item.harga, 0);
    },
    tanggalCetak() {
      const now = new Date();
      return now.toLocaleDateString("id-ID", {
        day: "numeric", month: "long", year: "numeric"
      });
    }
  },
  methods: {
    formatCurrency(value) {
      return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR" }).format(value);
    },
    printReport() {
      const printContents = this.$refs.printArea.innerHTML;
      const originalContents = document.body.innerHTML;

      document.body.innerHTML = printContents;
      window.print();
      document.body.innerHTML = originalContents;
      window.location.reload();  // Agar kembali ke Vue SPA
    }
  }
};
</script>

<style scoped>
.report-container {
  width: 210mm; /* A4 width */
  padding: 20mm;
  font-family: 'Times New Roman', serif;
  font-size: 12pt;
  color: #000;
  background: #fff;
}

.report-header {
  text-align: center;
  margin-bottom: 15px;
}

.logo {
  width: 100px;
  margin-bottom: 10px;
}

.title {
  margin: 0;
  font-size: 18pt;
  font-weight: bold;
}

.date {
  margin: 0;
  font-style: italic;
  font-size: 11pt;
  color: #444;
}

/* Table Styling mirip PDF */
.report-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 20px;
}

.report-table th, .report-table td {
  border: 1px solid #000;
  padding: 8px 10px;
  text-align: center;
}

.report-table thead th {
  background-color: #eee;
  font-weight: bold;
}

.total-label {
  text-align: right;
  font-weight: bold;
  padding-right: 10px;
}

.total-value {
  font-weight: bold;
  text-align: center;
}

/* Footer mirip PDF */
.report-footer {
  text-align: center;
  font-size: 10pt;
  color: #555;
  border-top: 1px solid #ccc;
  padding-top: 8px;
}

/* Media print supaya pas dengan A4 dan rapih */
@media print {
  body {
    margin: 0;
    background: #fff;
  }

  .report-container {
    width: auto;
    padding: 0;
    margin: 0 auto;
    box-shadow: none;
    page-break-after: always;
  }

  button {
    display: none;
  }
}
</style>
