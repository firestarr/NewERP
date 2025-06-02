<!-- frontend/erp/src/views/sales/SalesOrderExcelImport.vue -->
<template>
  <div class="sales-order-import">
  <div class="page-header" style="position: relative;">
    <router-link to="/sales/orders" class="btn btn-outline-secondary" style="position: absolute; right: 0; top: 0;">
      <i class="fas fa-arrow-left"></i> Back
    </router-link>
    <h1>Import Sales Orders from Excel</h1>
    <p>Upload an Excel file to import multiple sales orders and their lines</p>
  </div>

    <div class="import-container">
      <!-- Step 1: Download Template -->
      <div class="step-card">
        <div class="step-header">
          <div class="step-number">1</div>
          <h3>Download Template</h3>
        </div>
        <div class="step-content">
          <p>Download the Excel template with proper format and sample data.</p>
          <button
            @click="downloadTemplate"
            :disabled="downloadingTemplate"
            class="btn btn-outline-primary"
          >
            <i class="fas fa-download"></i>
            {{ downloadingTemplate ? 'Downloading...' : 'Download Template' }}
          </button>
        </div>
      </div>

      <!-- Step 2: Upload File -->
      <div class="step-card">
        <div class="step-header">
          <div class="step-number">2</div>
          <h3>Upload Excel File</h3>
        </div>
        <div class="step-content">
          <div class="upload-area"
               :class="{ 'dragover': isDragover, 'has-file': selectedFile }"
               @dragover.prevent="isDragover = true"
               @dragleave.prevent="isDragover = false"
               @drop.prevent="handleFileDrop">

            <div v-if="!selectedFile" class="upload-placeholder">
              <i class="fas fa-cloud-upload-alt"></i>
              <p><strong>Drop your Excel file here</strong> or click to browse</p>
              <p class="text-muted">Supported formats: .xlsx, .xls (Max: 10MB)</p>
              <input
                type="file"
                ref="fileInput"
                @change="handleFileSelect"
                accept=".xlsx,.xls"
                class="file-input"
              >
<button @click="$refs.fileInput.click()" class="btn btn-outline-primary">
  Choose File
</button>
            </div>

            <div v-else class="file-selected">
              <div class="file-info">
                <i class="fas fa-file-excel"></i>
                <div class="file-details">
                  <strong>{{ selectedFile.name }}</strong>
                  <span class="file-size">{{ formatFileSize(selectedFile.size) }}</span>
                </div>
                <button @click="removeFile" class="btn-remove">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Options -->
          <div v-if="selectedFile" class="import-options">
            <label class="checkbox-label">
              <input type="checkbox" v-model="updateExisting">
              <span class="checkmark"></span>
              Update existing sales orders if duplicate SO numbers are found
            </label>
          </div>

          <!-- Import Button -->
          <div v-if="selectedFile" class="import-actions">
<button
  @click="importFile"
  :disabled="isImporting"
  class="btn btn-success"
  style="color: white;"
>
  <i class="fas fa-upload"></i>
  {{ isImporting ? 'Importing...' : 'Import Sales Orders' }}
</button>
            <button @click="removeFile" class="btn btn-secondary">
              Cancel
            </button>
          </div>
        </div>
      </div>

      <!-- Step 3: Results -->
      <div v-if="importResult" class="step-card">
        <div class="step-header">
          <div class="step-number">3</div>
          <h3>Import Results</h3>
        </div>
        <div class="step-content">
          <div class="result-summary">
            <div class="summary-cards">
              <div class="summary-card success">
                <div class="card-icon">
                  <i class="fas fa-check-circle"></i>
                </div>
                <div class="card-content">
                  <div class="card-number">{{ importResult.summary.successful }}</div>
                  <div class="card-label">Successful</div>
                </div>
              </div>

              <div class="summary-card error">
                <div class="card-icon">
                  <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="card-content">
                  <div class="card-number">{{ importResult.summary.failed }}</div>
                  <div class="card-label">Failed</div>
                </div>
              </div>

              <div class="summary-card info">
                <div class="card-icon">
                  <i class="fas fa-info-circle"></i>
                </div>
                <div class="card-content">
                  <div class="card-number">{{ importResult.summary.total_processed }}</div>
                  <div class="card-label">Total Processed</div>
                </div>
              </div>
            </div>

            <!-- Error Details -->
            <div v-if="importResult.summary.errors.length > 0" class="error-details">
              <h4>
                <i class="fas fa-exclamation-triangle"></i>
                Import Errors ({{ importResult.summary.errors.length }})
              </h4>
              <div class="error-list">
                <div
                  v-for="(error, index) in importResult.summary.errors"
                  :key="index"
                  class="error-item"
                >
                  <i class="fas fa-times-circle"></i>
                  {{ error }}
                </div>
              </div>
              <button @click="downloadErrorReport" class="btn btn-outline-danger">
                <i class="fas fa-download"></i>
                Download Error Report
              </button>
            </div>
          </div>

          <div class="result-actions">
            <router-link to="/sales/orders" class="btn btn-primary">
              <i class="fas fa-eye"></i>
              View Sales Orders
            </router-link>
            <button @click="resetImport" class="btn btn-outline-secondary">
              Import Another File
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading Overlay -->
    <div v-if="isImporting" class="loading-overlay">
      <div class="loading-content">
        <div class="spinner"></div>
        <h3>Importing Sales Orders</h3>
        <p>Please wait while we process your file...</p>
        <div class="progress-bar">
          <div class="progress-fill" :style="{ width: importProgress + '%' }"></div>
        </div>
        <span class="progress-text">{{ importProgress }}%</span>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'SalesOrderExcelImport',
  data() {
    return {
      selectedFile: null,
      isDragover: false,
      isImporting: false,
      downloadingTemplate: false,
      updateExisting: false,
      importResult: null,
      importProgress: 0
    };
  },
  methods: {
    async downloadTemplate() {
  this.downloadingTemplate = true;
  try {
    const response = await axios.get('/orders/excel/template', {
      responseType: 'blob'
    });

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `sales_order_template_${new Date().toISOString().split('T')[0]}.xlsx`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);

    // Safe toast notification - check if toast exists
    if (this.$toast) {
      this.$toast.success('Template downloaded successfully');
    } else {
      // Fallback notification method
      alert('Template downloaded successfully');
      // or use console.log for debugging
      console.log('Template downloaded successfully');
    }
  } catch (error) {
    console.error('Download template error:', error);

    // Safe error notification
    if (this.$toast) {
      this.$toast.error('Failed to download template');
    } else {
      // Fallback error notification
      alert('Failed to download template');
      console.error('Failed to download template');
    }
  } finally {
    this.downloadingTemplate = false;
  }
},

    handleFileDrop(e) {
      this.isDragover = false;
      const files = e.dataTransfer.files;
      if (files.length > 0) {
        this.handleFile(files[0]);
      }
    },

    handleFileSelect(e) {
      const file = e.target.files[0];
      if (file) {
        this.handleFile(file);
      }
    },

    handleFile(file) {
      // Validate file type
      const allowedTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'];
      if (!allowedTypes.includes(file.type)) {
        this.$toast.error('Please select a valid Excel file (.xlsx or .xls)');
        return;
      }

      // Validate file size (10MB)
      if (file.size > 10 * 1024 * 1024) {
        this.$toast.error('File size must be less than 10MB');
        return;
      }

      this.selectedFile = file;
      this.importResult = null;
    },

    removeFile() {
      this.selectedFile = null;
      this.$refs.fileInput.value = '';
      this.importResult = null;
    },

    async importFile() {
      if (!this.selectedFile) return;

      this.isImporting = true;
      this.importProgress = 0;

      // Simulate progress for user experience
      const progressInterval = setInterval(() => {
        if (this.importProgress < 90) {
          this.importProgress += Math.random() * 20;
        }
      }, 500);

      try {
        const formData = new FormData();
        formData.append('file', this.selectedFile);
        formData.append('update_existing', this.updateExisting ? '1' : '0');

        const response = await axios.post('/orders/excel/import', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        });

        this.importProgress = 100;
        this.importResult = response.data;

        if (response.data.summary.successful > 0) {
          this.$toast.success(`Successfully imported ${response.data.summary.successful} sales orders`);
        }

        if (response.data.summary.failed > 0) {
          this.$toast.warning(`${response.data.summary.failed} records failed to import`);
        }

      } catch (error) {
        console.error('Import error:', error);
        this.importProgress = 100;
        this.importResult = {
          summary: {
            successful: 0,
            failed: 1,
            total_processed: 1,
            errors: [error.response?.data?.message || 'Import failed']
          }
        };
        this.$toast.error('Import failed: ' + (error.response?.data?.message || 'Unknown error'));
      } finally {
        clearInterval(progressInterval);
        this.isImporting = false;
      }
    },

    downloadErrorReport() {
      if (!this.importResult?.summary?.errors) return;

      const errorText = this.importResult.summary.errors.join('\n');
      const blob = new Blob([errorText], { type: 'text/plain' });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `sales_order_import_errors_${new Date().toISOString().split('T')[0]}.txt`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
    },

    resetImport() {
      this.selectedFile = null;
      this.importResult = null;
      this.updateExisting = false;
      this.importProgress = 0;
      this.$refs.fileInput.value = '';
    },

    formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes';
      const k = 1024;
      const sizes = ['Bytes', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
  }
};
</script>

<style scoped>
.sales-order-import {
  max-width: 900px;
  margin: 0 auto;
  padding: 2rem;
}

.page-header {
  text-align: center;
  margin-bottom: 3rem;
}

.page-header h1 {
  color: var(--text-primary);
  margin-bottom: 0.5rem;
}

.page-header p {
  color: var(--text-muted);
  font-size: 1.1rem;
}

.step-card {
  background: var(--card-bg);
  border-radius: 12px;
  border: 1px solid var(--border-color);
  margin-bottom: 2rem;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.step-header {
  display: flex;
  align-items: center;
  padding: 1.5rem;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  border-bottom: 1px solid var(--border-color);
}

.step-number {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--primary-gradient);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  margin-right: 1rem;
}

.step-header h3 {
  margin: 0;
  color: var(--text-primary);
}

.step-content {
  padding: 2rem;
}

.upload-area {
  border: 2px dashed var(--border-color);
  border-radius: 12px;
  padding: 3rem 2rem;
  text-align: center;
  transition: all 0.3s ease;
  background: var(--bg-secondary);
  margin-bottom: 1.5rem;
}

.upload-area.dragover {
  border-color: var(--primary-color);
  background: rgba(99, 102, 241, 0.05);
}

.upload-area.has-file {
  border-color: var(--success-color);
  background: rgba(16, 185, 129, 0.05);
}

.upload-placeholder i {
  font-size: 3rem;
  color: var(--text-muted);
  margin-bottom: 1rem;
}

.upload-placeholder p {
  margin: 0.5rem 0;
  color: var(--text-secondary);
}

.file-input {
  display: none;
}

.file-selected {
  padding: 1rem;
}

.file-info {
  display: flex;
  align-items: center;
  background: white;
  border-radius: 8px;
  padding: 1rem;
  border: 1px solid var(--border-color);
}

.file-info i {
  font-size: 2rem;
  color: var(--success-color);
  margin-right: 1rem;
}

.file-details {
  flex: 1;
  text-align: left;
}

.file-details strong {
  display: block;
  color: var(--text-primary);
}

.file-size {
  color: var(--text-muted);
  font-size: 0.9rem;
}

.btn-remove {
  background: none;
  border: none;
  color: var(--text-muted);
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 4px;
  transition: all 0.2s ease;
}

.btn-remove:hover {
  color: var(--danger-color);
  background: rgba(220, 38, 38, 0.1);
}

.import-options {
  margin: 1.5rem 0;
}

.checkbox-label {
  display: flex;
  align-items: center;
  cursor: pointer;
  color: var(--text-secondary);
}

.checkbox-label input {
  margin-right: 0.75rem;
}

.import-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  margin-top: 1.5rem;
}

.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.summary-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  border: 1px solid var(--border-color);
  display: flex;
  align-items: center;
  gap: 1rem;
}

.summary-card.success {
  border-left: 4px solid var(--success-color);
}

.summary-card.error {
  border-left: 4px solid var(--danger-color);
}

.summary-card.info {
  border-left: 4px solid var(--primary-color);
}

.card-icon {
  font-size: 2rem;
}

.card-icon .fa-check-circle {
  color: var(--success-color);
}

.card-icon .fa-exclamation-circle {
  color: var(--danger-color);
}

.card-icon .fa-info-circle {
  color: var(--primary-color);
}

.card-number {
  font-size: 2rem;
  font-weight: 700;
  color: var(--text-primary);
}

.card-label {
  color: var(--text-muted);
  font-weight: 500;
}

.error-details {
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  padding: 1.5rem;
  margin-bottom: 2rem;
}

.error-details h4 {
  color: #dc2626;
  margin-bottom: 1rem;
}

.error-list {
  max-height: 200px;
  overflow-y: auto;
  margin-bottom: 1rem;
}

.error-item {
  display: flex;
  align-items: flex-start;
  padding: 0.5rem 0;
  border-bottom: 1px solid #fecaca;
  color: #991b1b;
}

.error-item:last-child {
  border-bottom: none;
}

.error-item i {
  margin-right: 0.5rem;
  margin-top: 0.2rem;
  flex-shrink: 0;
}

.result-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}

.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.loading-content {
  background: white;
  border-radius: 12px;
  padding: 3rem;
  text-align: center;
  max-width: 400px;
  width: 90%;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #f3f4f6;
  border-top: 4px solid var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

.progress-bar {
  width: 100%;
  height: 8px;
  background: #e5e7eb;
  border-radius: 4px;
  overflow: hidden;
  margin: 1rem 0 0.5rem;
}

.progress-fill {
  height: 100%;
  background: var(--primary-gradient);
  transition: width 0.3s ease;
}

.progress-text {
  color: var(--text-muted);
  font-size: 0.9rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.btn {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  border: none;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
}

.btn-primary {
  background: var(--primary-gradient);
  color: white;
}

.btn-success {
  background: var(--success-gradient);
  color: white;
}

.btn-secondary {
  background: var(--gray-500);
  color: white;
}

.btn-outline-primary {
  background: transparent;
  color: var(--primary-color);
  border: 1px solid var(--primary-color);
}

.btn-outline-secondary {
  background: transparent;
  color: var(--gray-600);
  border: 1px solid var(--gray-300);
}

.btn-outline-danger {
  background: transparent;
  color: var(--danger-color);
  border: 1px solid var(--danger-color);
}

.btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

@media (max-width: 768px) {
  .sales-order-import {
    padding: 1rem;
  }

  .step-content {
    padding: 1.5rem;
  }

  .upload-area {
    padding: 2rem 1rem;
  }

  .summary-cards {
    grid-template-columns: 1fr;
  }

  .result-actions {
    flex-direction: column;
  }
}
</style>
