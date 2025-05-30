<!-- src/views/sales/ImportForecastForm.vue -->
<template>
  <div class="page-container">
    <div class="page-header">
      <h2>Import Sales Forecast</h2>
      <p class="text-muted">Upload customer forecasts or generate system forecasts</p>
    </div>

    <div class="forms-container">
      <div class="forms-row">
        <!-- CSV Import Section -->
        <div class="form-column">
          <div class="card h-100">
            <div class="card-header">
              <h5 class="mb-0">Import Customer Forecasts</h5>
            </div>
            <div class="card-body d-flex flex-column">
            <form @submit.prevent="importForecast" class="mb-4">
              <div class="form-group">
                <label class="form-label">Customer</label>
                <select
                  class="form-select"
                  v-model="importForm.customer_id"
                  required
                >
                  <option value="">Select Customer</option>
                  <option
                    v-for="customer in customers"
                    :key="customer.customer_id"
                    :value="customer.customer_id"
                  >
                    {{ customer.name }}
                  </option>
                </select>
                <div class="form-text">
                  Select the customer who provided this forecast
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Forecast Issue Date</label>
                <input
                  type="date"
                  class="form-control"
                  v-model="importForm.forecast_issue_date"
                  required
                />
                <div class="form-text">
                  Date when this forecast was issued by the customer
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">CSV File</label>
                <input
                  type="file"
                  class="form-control"
                  accept=".csv,.txt"
                  @change="handleFileUpload"
                  required
                />
                <div class="form-text">
                  CSV must include an "item_code" column and monthly forecast columns in YYYY-MM format
                </div>
              </div>

              <div class="form-group checkbox-group">
                <div class="form-check">
                  <input
                    type="checkbox"
                    class="form-check-input"
                    id="fillMissingPeriods"
                    v-model="importForm.fill_missing_periods"
                  />
                  <label class="form-check-label" for="fillMissingPeriods">
                    Fill missing periods with system forecasts
                  </label>
                </div>
              </div>

              <div v-if="importError" class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ importError }}
              </div>

              <div class="button-group">
                <button
                  type="submit"
                  class="btn btn-primary"
                  :disabled="importing"
                >
                  <i v-if="importing" class="fas fa-spinner fa-spin me-2"></i>
                  <i v-else class="fas fa-upload me-2"></i>
                  Import Forecast
                </button>
                <button
                  type="button"
                  class="btn btn-outline-secondary"
                  @click="resetImportForm"
                >
                  Reset
                </button>
              </div>
            </form>

            <div v-if="importSuccess" class="alert alert-success" role="alert">
              <i class="fas fa-check-circle me-2"></i>
              {{ importSuccess }}
            </div>

            <div v-if="importResponse && importResponse.errors && importResponse.errors.length > 0" class="error-section">
              <h6 class="error-title">Import Errors</h6>
              <div class="table-wrapper">
                <table class="table table-sm table-bordered">
                  <thead>
                    <tr>
                      <th>Row</th>
                      <th>Item Code</th>
                      <th>Month</th>
                      <th>Error</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(error, index) in importResponse.errors" :key="index">
                      <td>{{ error.row }}</td>
                      <td>{{ error.item_code }}</td>
                      <td>{{ error.month || 'N/A' }}</td>
                      <td>{{ error.error }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            </div>
          </div>
        </div>

        <!-- System Generate Section -->
        <div class="form-column">
          <div class="card h-100">
            <div class="card-header">
              <h5 class="mb-0">Generate System Forecasts</h5>
            </div>
            <div class="card-body d-flex flex-column">
            <form @submit.prevent="generateForecast" class="mb-4">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label">Start Period</label>
                    <input
                      type="month"
                      class="form-control"
                      v-model="generateForm.start_period"
                      required
                    />
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label">End Period</label>
                    <input
                      type="month"
                      class="form-control"
                      v-model="generateForm.end_period"
                      required
                    />
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Customer (Optional)</label>
                <select
                  class="form-select"
                  v-model="generateForm.customer_id"
                >
                  <option value="">All Customers</option>
                  <option
                    v-for="customer in customers"
                    :key="customer.customer_id"
                    :value="customer.customer_id"
                  >
                    {{ customer.name }}
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">Item (Optional)</label>
                <select
                  class="form-select"
                  v-model="generateForm.item_id"
                >
                  <option value="">All Items</option>
                  <option
                    v-for="item in items"
                    :key="item.item_id"
                    :value="item.item_id"
                  >
                    {{ item.item_code }} - {{ item.name }}
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">Forecast Method</label>
                <select
                  class="form-select"
                  v-model="generateForm.method"
                  required
                >
                  <option value="trend">Trend (Linear Regression)</option>
                  <option value="weighted">Weighted Average</option>
                  <option value="average">Simple Average</option>
                </select>
                <div class="form-text method-description">
                  <strong>Trend:</strong> Uses linear regression based on historical data<br>
                  <strong>Weighted Average:</strong> Gives more weight to recent periods<br>
                  <strong>Simple Average:</strong> Calculates the average of historical quantities
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Forecast Issue Date (Optional)</label>
                <input
                  type="date"
                  class="form-control"
                  v-model="generateForm.forecast_issue_date"
                />
                <div class="form-text">
                  Defaults to today's date if not specified
                </div>
              </div>

              <div v-if="generateError" class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ generateError }}
              </div>

              <div class="button-group">
                <button
                  type="submit"
                  class="btn btn-primary"
                  :disabled="generating"
                >
                  <i v-if="generating" class="fas fa-spinner fa-spin me-2"></i>
                  <i v-else class="fas fa-magic me-2"></i>
                  Generate Forecasts
                </button>
                <button
                  type="button"
                  class="btn btn-outline-secondary"
                  @click="resetGenerateForm"
                >
                  Reset
                </button>
              </div>
            </form>

            <div v-if="generateSuccess" class="alert alert-success" role="alert">
              <i class="fas fa-check-circle me-2"></i>
              {{ generateSuccess }}
            </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Documentation Section -->
    <div class="section-container">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">CSV Import Format</h5>
        </div>
        <div class="card-body">
          <div class="format-section">
            <h6>Required Format</h6>
            <p class="format-description">
              The CSV file must contain an <code>item_code</code> column and at least one month column in <code>YYYY-MM</code> format (e.g., <code>2025-05</code>).
            </p>
          </div>

          <div class="example-section">
            <h6>Example</h6>
            <div class="table-wrapper">
              <table class="table table-sm table-bordered">
                <thead>
                  <tr>
                    <th>item_code</th>
                    <th>2025-05</th>
                    <th>2025-06</th>
                    <th>2025-07</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>ITEM001</td>
                    <td>100</td>
                    <td>120</td>
                    <td>140</td>
                  </tr>
                  <tr>
                    <td>ITEM002</td>
                    <td>50</td>
                    <td>60</td>
                    <td>70</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="notes-section">
            <h6>Notes</h6>
            <ul class="notes-list">
              <li>Each row represents one item's forecast</li>
              <li>Month columns (YYYY-MM) represent forecast quantities for that period</li>
              <li>Empty cells will be ignored</li>
              <li>Negative values are not allowed</li>
              <li>Commas in numbers (e.g., 1,000) will be automatically handled</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'ImportForecastForm',
  data() {
    return {
      // Import form data
      importForm: {
        customer_id: '',
        forecast_issue_date: this.getTodayFormatted(),
        csv_file: null,
        fill_missing_periods: false
      },
      importing: false,
      importError: null,
      importSuccess: null,
      importResponse: null,

      // Generate form data
      generateForm: {
        start_period: this.getCurrentMonth(),
        end_period: this.getMonthsLater(6),
        customer_id: '',
        item_id: '',
        method: 'trend',
        forecast_issue_date: this.getTodayFormatted()
      },
      generating: false,
      generateError: null,
      generateSuccess: null,

      // Lists for dropdowns
      customers: [],
      items: []
    };
  },
  created() {
    this.fetchCustomers();
    this.fetchItems();
  },
  methods: {
    // Common utility methods
    getTodayFormatted() {
      const today = new Date();
      const year = today.getFullYear();
      const month = (today.getMonth() + 1).toString().padStart(2, '0');
      const day = today.getDate().toString().padStart(2, '0');
      return `${year}-${month}-${day}`;
    },
    getCurrentMonth() {
      const today = new Date();
      const year = today.getFullYear();
      const month = (today.getMonth() + 1).toString().padStart(2, '0');
      return `${year}-${month}`;
    },
    getMonthsLater(months) {
      const date = new Date();
      date.setMonth(date.getMonth() + months);
      const year = date.getFullYear();
      const month = (date.getMonth() + 1).toString().padStart(2, '0');
      return `${year}-${month}`;
    },

    // Data fetching methods
    async fetchCustomers() {
      try {
        const response = await axios.get('/customers');
        this.customers = response.data.data || [];
      } catch (error) {
        console.error('Error fetching customers:', error);
      }
    },
    async fetchItems() {
      try {
        const response = await axios.get('/items', {
            params: { sellable: true }
          });
        this.items = response.data.data || [];
      } catch (error) {
        console.error('Error fetching items:', error);
      }
    },

    // Import methods
    handleFileUpload(event) {
      this.importForm.csv_file = event.target.files[0];
    },
    async importForecast() {
      if (!this.importForm.customer_id || !this.importForm.forecast_issue_date || !this.importForm.csv_file) {
        this.importError = 'Please fill in all required fields and select a CSV file';
        return;
      }

      this.importing = true;
      this.importError = null;
      this.importSuccess = null;
      this.importResponse = null;

      try {
        const formData = new FormData();
        formData.append('customer_id', this.importForm.customer_id);
        formData.append('forecast_issue_date', this.importForm.forecast_issue_date);
        formData.append('csv_file', this.importForm.csv_file);
        formData.append('fill_missing_periods', this.importForm.fill_missing_periods ? 1 : 0);

        const response = await axios.post('/forecasts/import', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        });

        this.importResponse = response.data;

        if (response.data.message) {
          this.importSuccess = response.data.message;
        }
      } catch (error) {
        console.error('Error importing forecast:', error);
        this.importError = error.response?.data?.message || 'An error occurred while importing the forecast';
      } finally {
        this.importing = false;
      }
    },
    resetImportForm() {
      this.importForm = {
        customer_id: '',
        forecast_issue_date: this.getTodayFormatted(),
        csv_file: null,
        fill_missing_periods: false
      };
      this.importError = null;
      this.importSuccess = null;
      this.importResponse = null;

      // Reset file input
      const fileInput = document.querySelector('input[type="file"]');
      if (fileInput) {
        fileInput.value = '';
      }
    },

    // Generate methods
    async generateForecast() {
      if (!this.generateForm.start_period || !this.generateForm.end_period || !this.generateForm.method) {
        this.generateError = 'Please fill in all required fields';
        return;
      }

      // Validate end period is after start period
      if (this.generateForm.end_period <= this.generateForm.start_period) {
        this.generateError = 'End period must be after start period';
        return;
      }

      this.generating = true;
      this.generateError = null;
      this.generateSuccess = null;

      try {
        const payload = {
          start_period: `${this.generateForm.start_period}-01`,
          end_period: `${this.generateForm.end_period}-01`,
          method: this.generateForm.method
        };

        // Add optional parameters if they exist
        if (this.generateForm.customer_id) {
          payload.customer_id = this.generateForm.customer_id;
        }

        if (this.generateForm.item_id) {
          payload.item_id = this.generateForm.item_id;
        }

        if (this.generateForm.forecast_issue_date) {
          payload.forecast_issue_date = this.generateForm.forecast_issue_date;
        }

        const response = await axios.post('/forecasts/generate', payload);

        if (response.data.message) {
          this.generateSuccess = response.data.message;
        }
      } catch (error) {
        console.error('Error generating forecast:', error);
        this.generateError = error.response?.data?.message || 'An error occurred while generating the forecast';
      } finally {
        this.generating = false;
      }
    },
    resetGenerateForm() {
      this.generateForm = {
        start_period: this.getCurrentMonth(),
        end_period: this.getMonthsLater(6),
        customer_id: '',
        item_id: '',
        method: 'trend',
        forecast_issue_date: this.getTodayFormatted()
      };
      this.generateError = null;
      this.generateSuccess = null;
    }
  }
};
</script>

<style scoped>
/* Base Container Styling */
.page-container {
  padding: 2.5rem;
  max-width: 1600px;
  margin: 0 auto;
  background-color: #f8fafc;
  min-height: 100vh;
}

/* Page Header */
.page-header {
  margin-bottom: 3rem;
  border-bottom: 1px solid #e2e8f0;
  padding-bottom: 2rem;
  position: relative;
  background-color: white;
  border-radius: 12px;
  padding: 2rem 2.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.page-header:after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 2.5rem;
  height: 4px;
  width: 120px;
  background: linear-gradient(to right, #4361ee, #7048e8);
  border-radius: 2px;
}

.page-header h2 {
  font-size: 2rem;
  margin-bottom: 0.75rem;
  color: #1a202c;
  font-weight: 700;
  letter-spacing: -0.025em;
}

.page-header .text-muted {
  font-size: 1.1rem;
  color: #64748b;
  margin: 0;
}

/* Forms Container Layout */
.forms-container {
  margin-bottom: 3rem;
}

.forms-row {
  display: flex;
  gap: 2rem;
  align-items: stretch;
}

.form-column {
  flex: 1;
  min-width: 0; /* Prevents flex items from overflowing */
}

/* Equal Height Cards */
.h-100 {
  height: 100% !important;
}

.card-body.d-flex {
  flex: 1;
  display: flex !important;
  flex-direction: column;
}

.card-body.d-flex form {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.card-body.d-flex .button-group {
  margin-top: auto;
  padding-top: 1rem;
}

/* Consistent Input Sizing */
.form-control,
.form-select {
  border-radius: 8px;
  padding: 0.75rem 1rem;
  border: 2px solid #e2e8f0;
  transition: all 0.3s ease;
  font-size: 1rem;
  background-color: white;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  width: 100%;
  max-width: 100%;
  min-height: 48px; /* Consistent minimum height */
}

/* Month input specific styling */
input[type="month"].form-control {
  min-height: 48px;
}

/* Date input specific styling */
input[type="date"].form-control {
  min-height: 48px;
}

/* File input specific styling */
input[type="file"].form-control {
  padding: 0.75rem;
  background-color: #f8fafc;
  border: 2px dashed #cbd5e0;
  cursor: pointer;
  min-height: 48px;
  display: flex;
  align-items: center;
}

/* Section Container */
.section-container {
  margin-bottom: 2rem;
}

/* Card Styling */
.card {
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05), 0 2px 4px rgba(0, 0, 0, 0.05);
  border: none;
  background-color: white;
  transition: all 0.3s ease;
  margin-bottom: 2rem;
}

.card:hover {
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08), 0 4px 8px rgba(0, 0, 0, 0.05);
  transform: translateY(-2px);
}

.card-header {
  background-color: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  padding: 1.75rem 2rem;
  border-radius: 12px 12px 0 0;
}

.card-header h5 {
  font-weight: 700;
  color: #1a202c;
  margin: 0;
  font-size: 1.25rem;
  letter-spacing: -0.025em;
}

.card-body {
  padding: 2rem;
}

/* Form Styling */
.form-group {
  margin-bottom: 2rem;
}

.form-group:last-child {
  margin-bottom: 0;
}

.form-label {
  display: block;
  margin-bottom: 0.75rem;
  font-weight: 600;
  color: #1a202c;
  font-size: 0.95rem;
  letter-spacing: 0.025em;
}

/* Form Styling - Override previous styles */
.form-control:focus,
.form-select:focus {
  border-color: #4361ee;
  box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1);
  outline: none;
  background-color: #fafafb;
}

.form-text {
  margin-top: 0.5rem;
  font-size: 0.875rem;
  color: #64748b;
  line-height: 1.5;
}

/* Special styling for method description */
.method-description {
  margin-top: 0.75rem;
  padding: 1rem;
  background-color: #f1f5f9;
  border-radius: 6px;
  border-left: 4px solid #4361ee;
}

/* File Input Hover Effect */
input[type="file"].form-control:hover {
  border-color: #4361ee;
  background-color: #f1f5f9;
}

/* Checkbox Styling */
.checkbox-group {
  margin-bottom: 2rem;
}

.form-check {
  padding: 1rem;
  background-color: #f8fafc;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  margin-bottom: 0;
}

.form-check-input {
  width: 1.25rem;
  height: 1.25rem;
  margin-top: 0;
  border: 2px solid #cbd5e0;
  border-radius: 4px;
  transition: all 0.3s ease;
}

.form-check-input:checked {
  background-color: #4361ee;
  border-color: #4361ee;
}

.form-check-label {
  color: #1a202c;
  font-size: 1rem;
  font-weight: 500;
  margin-left: 0.75rem;
  cursor: pointer;
}

/* Button Styling */
.button-group {
  display: flex;
  gap: 1rem;
  margin-top: 2rem;
  flex-wrap: wrap;
}

.btn {
  border-radius: 8px;
  font-weight: 600;
  padding: 0.75rem 1.5rem;
  transition: all 0.3s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.95rem;
  border: 2px solid transparent;
  min-width: 140px;
}

.btn i {
  margin-right: 0.5rem;
  font-size: 0.9rem;
}

.btn-primary {
  background: linear-gradient(135deg, #4361ee, #3a56d4);
  border-color: #4361ee;
  color: white;
}

.btn-primary:hover,
.btn-primary:active {
  background: linear-gradient(135deg, #3a56d4, #2f4bc4);
  border-color: #3a56d4;
  box-shadow: 0 4px 12px rgba(67, 97, 238, 0.25);
  transform: translateY(-1px);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.btn-outline-secondary {
  color: #64748b;
  border-color: #cbd5e0;
  background-color: white;
}

.btn-outline-secondary:hover {
  color: #1a202c;
  background-color: #f1f5f9;
  border-color: #94a3b8;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transform: translateY(-1px);
}

/* Alert Styling */
.alert {
  border: none;
  border-radius: 8px;
  padding: 1.25rem 1.5rem;
  margin-top: 1.5rem;
  font-size: 0.95rem;
  display: flex;
  align-items: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  border-left: 4px solid;
}

.alert i {
  font-size: 1.1rem;
  margin-right: 0.75rem;
}

.alert-success {
  background-color: rgba(34, 197, 94, 0.1);
  color: #166534;
  border-left-color: #22c55e;
}

.alert-danger {
  background-color: rgba(239, 68, 68, 0.1);
  color: #991b1b;
  border-left-color: #ef4444;
}

/* Table Styling */
.table-wrapper {
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  margin-top: 1rem;
  border: 1px solid #e2e8f0;
}

.table {
  margin-bottom: 0;
  font-size: 0.9rem;
}

.table th {
  background-color: #f1f5f9;
  font-weight: 700;
  color: #1a202c;
  border: none;
  padding: 1rem 1.25rem;
  text-transform: uppercase;
  font-size: 0.8rem;
  letter-spacing: 0.05em;
}

.table td {
  padding: 1rem 1.25rem;
  vertical-align: middle;
  border-color: #f1f5f9;
  color: #374151;
}

.table-bordered,
.table-bordered td,
.table-bordered th {
  border-color: #e2e8f0;
}

/* Error Section */
.error-section {
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 1px solid #e2e8f0;
}

.error-title {
  font-size: 1.1rem;
  font-weight: 700;
  margin-bottom: 1rem;
  color: #dc2626;
}

/* Format, Example, and Notes Sections */
.format-section,
.example-section,
.notes-section {
  margin-bottom: 2.5rem;
}

.format-section:last-child,
.example-section:last-child,
.notes-section:last-child {
  margin-bottom: 0;
}

.format-description {
  margin-bottom: 0;
  font-size: 1rem;
  line-height: 1.6;
  color: #374151;
}

.card h6 {
  font-size: 1.1rem;
  font-weight: 700;
  margin-bottom: 1rem;
  color: #1a202c;
  letter-spacing: -0.025em;
}

.notes-list {
  padding-left: 1.5rem;
  margin: 0;
}

.notes-list li {
  margin-bottom: 0.75rem;
  font-size: 0.95rem;
  color: #374151;
  line-height: 1.6;
}

.notes-list li:last-child {
  margin-bottom: 0;
}

/* Code Styling */
code {
  font-size: 0.875em;
  background-color: #f1f5f9;
  padding: 0.25em 0.5em;
  border-radius: 4px;
  color: #c7254e;
  font-family: 'SFMono-Regular', 'Monaco', 'Inconsolata', 'Roboto Mono', monospace;
  font-weight: 600;
}

/* Row Spacing */
.row {
  margin-left: -1rem;
  margin-right: -1rem;
}

.row > [class*="col-"] {
  padding-left: 1rem;
  padding-right: 1rem;
}

/* Column Spacing */
.col-md-6 .form-group {
  margin-bottom: 1.5rem;
}

/* Spinner Animation */
.fa-spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 991.98px) {
  .page-container {
    padding: 2rem;
  }

  .page-header {
    padding: 1.5rem 2rem;
    margin-bottom: 2rem;
  }

  .page-header h2 {
    font-size: 1.75rem;
  }

  .card-body {
    padding: 1.5rem;
  }

  .card-header {
    padding: 1.5rem;
  }

  .section-container {
    margin-bottom: 1.5rem;
  }

  .forms-row {
    flex-direction: column;
    gap: 1.5rem;
  }

  .form-column {
    width: 100%;
  }
}

@media (max-width: 767.98px) {
  .page-container {
    padding: 1.5rem;
  }

  .page-header {
    padding: 1.25rem 1.5rem;
    margin-bottom: 1.5rem;
  }

  .page-header h2 {
    font-size: 1.5rem;
  }

  .forms-row {
    flex-direction: column;
    gap: 1.5rem;
  }

  .form-column {
    width: 100%;
  }

  .col-md-6 {
    flex: 0 0 100%;
    max-width: 100%;
  }

  .button-group {
    flex-direction: column;
    gap: 0.75rem;
  }

  .btn {
    width: 100%;
    min-width: auto;
  }

  .card-header,
  .card-body {
    padding: 1.25rem;
  }

  .form-group {
    margin-bottom: 1.5rem;
  }

  .table td,
  .table th {
    padding: 0.75rem;
    font-size: 0.85rem;
  }

  .col-md-6 .form-group {
    margin-bottom: 1.25rem;
  }
}

@media (max-width: 575.98px) {
  .page-container {
    padding: 1rem;
  }

  .page-header {
    padding: 1rem;
  }

  .forms-row {
    gap: 1.25rem;
  }

  .card-header,
  .card-body {
    padding: 1rem;
  }

  .section-container {
    margin-bottom: 1.5rem;
  }

  .format-section,
  .example-section,
  .notes-section {
    margin-bottom: 2rem;
  }
}
</style>
