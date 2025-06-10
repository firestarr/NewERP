<template>
  <div class="pdf-order-capture">
    <!-- Header Section -->
    <div class="page-header">
      <div class="header-content">
        <div class="title-section">
          <h1 class="page-title">
            <i class="fas fa-file-pdf"></i>
            PDF Order Capture
          </h1>
          <p class="page-subtitle">Upload PDF files and automatically create sales orders using AI</p>
        </div>
        <div class="header-actions">
          <button @click="refreshData" class="btn btn-secondary" :disabled="isLoading">
            <i class="fas fa-sync-alt" :class="{ 'fa-spin': isLoading }"></i>
            Refresh
          </button>
          <button @click="showUploadModal = true" class="btn btn-primary">
            <i class="fas fa-upload"></i>
            Upload PDF
          </button>
        </div>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon success">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
          <h3>{{ statistics.completed || 0 }}</h3>
          <p>Completed</p>
          <span class="stat-change success">{{ statistics.success_rate || 0 }}% success rate</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon warning">
          <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
          <h3>{{ statistics.processing || 0 }}</h3>
          <p>Processing</p>
          <span class="stat-change neutral">In progress</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon danger">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-content">
          <h3>{{ statistics.failed || 0 }}</h3>
          <p>Failed</p>
          <span class="stat-change danger">Need attention</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon info">
          <i class="fas fa-brain"></i>
        </div>
        <div class="stat-content">
          <h3>{{ statistics.average_confidence || 0 }}%</h3>
          <p>Avg. Confidence</p>
          <span class="stat-change info">AI accuracy</span>
        </div>
      </div>
    </div>

    <!-- Filters and Search -->
    <div class="filters-section">
      <div class="search-filters">
        <div class="filter-group">
          <label>Status</label>
          <select v-model="filters.status" @change="loadCaptureHistory">
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="processing">Processing</option>
            <option value="data_extracted">Data Extracted</option>
            <option value="completed">Completed</option>
            <option value="failed">Failed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>

        <div class="filter-group">
          <label>Date Range</label>
          <select v-model="filters.days" @change="loadCaptureHistory">
            <option value="7">Last 7 days</option>
            <option value="30">Last 30 days</option>
            <option value="90">Last 90 days</option>
            <option value="">All time</option>
          </select>
        </div>

        <div class="filter-group">
          <label>Per Page</label>
          <select v-model="filters.per_page" @change="loadCaptureHistory">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
          </select>
        </div>

        <button @click="clearFilters" class="btn btn-outline">
          <i class="fas fa-times"></i>
          Clear Filters
        </button>
      </div>
    </div>

    <!-- Processing History Table -->
    <div class="table-container">
      <div class="table-header">
        <h3>Processing History</h3>
        <div class="table-actions">
          <button @click="toggleSelectAll" class="btn btn-sm btn-outline">
            <i class="fas fa-check-square"></i>
            {{ selectedCaptures.length > 0 ? 'Deselect All' : 'Select All' }}
          </button>
          <button
            v-if="selectedCaptures.length > 0"
            @click="bulkRetry"
            class="btn btn-sm btn-warning"
            :disabled="bulkLoading"
          >
            <i class="fas fa-redo" :class="{ 'fa-spin': bulkLoading }"></i>
            Retry Selected ({{ selectedCaptures.length }})
          </button>
        </div>
      </div>

      <div v-if="isLoading" class="loading-state">
        <div class="loading-spinner">
          <i class="fas fa-spinner fa-spin"></i>
        </div>
        <p>Loading capture history...</p>
      </div>

      <div v-else-if="captures.length === 0" class="empty-state">
        <div class="empty-icon">
          <i class="fas fa-file-pdf"></i>
        </div>
        <h3>No PDF captures found</h3>
        <p>Upload your first PDF to get started with AI-powered order capture</p>
        <button @click="showUploadModal = true" class="btn btn-primary">
          <i class="fas fa-upload"></i>
          Upload PDF
        </button>
      </div>

      <div v-else class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th class="checkbox-col">
                <input
                  type="checkbox"
                  :checked="selectedCaptures.length === captures.length && captures.length > 0"
                  @change="toggleSelectAll"
                >
              </th>
              <th>File</th>
              <th>Status</th>
              <th>Customer</th>
              <th>Items</th>
              <th>Confidence</th>
              <th>Sales Order</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="capture in captures" :key="capture.id" class="table-row">
              <td class="checkbox-col">
                <input
                  type="checkbox"
                  :value="capture.id"
                  v-model="selectedCaptures"
                >
              </td>

              <td class="file-info">
                <div class="file-details">
                  <div class="file-name">
                    <i class="fas fa-file-pdf text-danger"></i>
                    {{ capture.filename }}
                  </div>
                  <div class="file-meta">
                    {{ capture.file_size_human }}
                  </div>
                </div>
              </td>

              <td>
                <span
                  class="status-badge"
                  :class="getStatusClass(capture.status)"
                >
                  {{ formatStatus(capture.status) }}
                </span>
              </td>

              <td class="customer-info">
                <div v-if="capture.extracted_customer">
                  <div class="customer-name">{{ capture.extracted_customer.name }}</div>
                  <div class="customer-meta">{{ capture.extracted_customer.email || 'No email' }}</div>
                </div>
                <span v-else class="text-muted">-</span>
              </td>

              <td class="items-info">
                <div v-if="capture.extracted_items && capture.extracted_items.length > 0">
                  <div class="items-count">{{ capture.extracted_items.length }} items</div>
                  <div class="items-preview">
                    {{ capture.extracted_items[0].name }}
                    <span v-if="capture.extracted_items.length > 1">
                      +{{ capture.extracted_items.length - 1 }} more
                    </span>
                  </div>
                </div>
                <span v-else class="text-muted">-</span>
              </td>

              <td class="confidence-score">
                <div v-if="capture.confidence_score" class="confidence-display">
                  <div class="confidence-bar">
                    <div
                      class="confidence-fill"
                      :style="{ width: capture.confidence_score + '%' }"
                      :class="getConfidenceClass(capture.confidence_score)"
                    ></div>
                  </div>
                  <span class="confidence-text">{{ capture.confidence_score }}%</span>
                </div>
                <span v-else class="text-muted">-</span>
              </td>

              <td class="sales-order">
                <div v-if="capture.sales_order">
                  <router-link
                    :to="`/sales/orders/${capture.sales_order.so_id}`"
                    class="order-link"
                  >
                    {{ capture.sales_order.so_number }}
                  </router-link>
                  <div class="order-amount">${{ formatCurrency(capture.sales_order.total_amount) }}</div>
                </div>
                <span v-else class="text-muted">-</span>
              </td>

              <td class="created-date">
                <div class="date-display">
                  <div class="date-main">{{ formatDate(capture.created_at) }}</div>
                  <div class="date-time">{{ formatTime(capture.created_at) }}</div>
                </div>
              </td>

              <td class="actions">
                <div class="action-buttons">
                  <button
                    @click="viewDetails(capture)"
                    class="btn-icon"
                    title="View Details"
                  >
                    <i class="fas fa-eye"></i>
                  </button>

                  <button
                    v-if="capture.status === 'failed'"
                    @click="retryCapture(capture.id)"
                    class="btn-icon btn-warning"
                    title="Retry Processing"
                    :disabled="retryLoading[capture.id]"
                  >
                    <i class="fas fa-redo" :class="{ 'fa-spin': retryLoading[capture.id] }"></i>
                  </button>

                  <button
                    @click="downloadFile(capture.id)"
                    class="btn-icon btn-secondary"
                    title="Download PDF"
                  >
                    <i class="fas fa-download"></i>
                  </button>

                  <button
                    @click="deleteCapture(capture.id)"
                    class="btn-icon btn-danger"
                    title="Delete"
                    :disabled="deleteLoading[capture.id]"
                  >
                    <i class="fas fa-trash" :class="{ 'fa-spin': deleteLoading[capture.id] }"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.total > 0" class="pagination-container">
        <div class="pagination-info">
          Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results
        </div>
        <div class="pagination-controls">
          <button
            @click="changePage(pagination.current_page - 1)"
            :disabled="pagination.current_page <= 1"
            class="btn btn-sm btn-outline"
          >
            <i class="fas fa-chevron-left"></i>
            Previous
          </button>

          <span class="page-numbers">
            <button
              v-for="page in visiblePages"
              :key="page"
              @click="changePage(page)"
              class="btn btn-sm"
              :class="{ 'btn-primary': page === pagination.current_page, 'btn-outline': page !== pagination.current_page }"
            >
              {{ page }}
            </button>
          </span>

          <button
            @click="changePage(pagination.current_page + 1)"
            :disabled="pagination.current_page >= pagination.last_page"
            class="btn btn-sm btn-outline"
          >
            Next
            <i class="fas fa-chevron-right"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Upload Modal -->
    <div v-if="showUploadModal" class="modal-overlay" @click="closeUploadModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Upload PDF Order</h3>
          <button @click="closeUploadModal" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="modal-body">
          <div class="upload-area" :class="{ 'dragover': isDragOver }"
               @drop="handleDrop" @dragover.prevent="isDragOver = true"
               @dragleave="isDragOver = false" @dragenter.prevent>
            <input
              ref="fileInput"
              type="file"
              accept=".pdf"
              @change="handleFileSelect"
              style="display: none"
            >

            <div v-if="!selectedFile" class="upload-placeholder">
              <div class="upload-icon">
                <i class="fas fa-cloud-upload-alt"></i>
              </div>
              <h4>Drag & Drop PDF File</h4>
              <p>or <button @click="$refs.fileInput.click()" class="link-btn">browse files</button></p>
              <div class="upload-info">
                <small>Supported: PDF files up to 10MB</small>
              </div>
            </div>

            <div v-else class="file-selected">
              <div class="file-preview">
                <i class="fas fa-file-pdf text-danger"></i>
                <div class="file-details">
                  <div class="file-name">{{ selectedFile.name }}</div>
                  <div class="file-size">{{ formatFileSize(selectedFile.size) }}</div>
                </div>
                <button @click="clearSelectedFile" class="remove-btn">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Processing Options -->
          <div class="processing-options">
            <h4>Processing Options</h4>

            <div class="option-group">
              <label class="checkbox-label">
                <input type="checkbox" v-model="uploadOptions.auto_create_missing_data">
                <span>Auto-create missing customers and items</span>
              </label>
            </div>

            <div class="option-row">
              <div class="option-field">
                <label>Preferred Currency</label>
                <select v-model="uploadOptions.preferred_currency">
                  <option value="USD">USD - US Dollar</option>
                  <option value="EUR">EUR - Euro</option>
                  <option value="GBP">GBP - British Pound</option>
                  <option value="IDR">IDR - Indonesian Rupiah</option>
                </select>
              </div>

              <div class="option-field">
                <label>Confidence Threshold</label>
                <select v-model="uploadOptions.processing_options.confidence_threshold">
                  <option value="60">60% - Low</option>
                  <option value="70">70% - Medium</option>
                  <option value="80">80% - High</option>
                  <option value="90">90% - Very High</option>
                </select>
              </div>
            </div>

            <div class="option-group">
              <label class="checkbox-label">
                <input type="checkbox" v-model="uploadOptions.processing_options.auto_approve">
                <span>Auto-approve if confidence is high enough</span>
              </label>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button @click="closeUploadModal" class="btn btn-secondary">
            Cancel
          </button>
          <button @click="previewExtraction" class="btn btn-outline" :disabled="!selectedFile || isUploading">
            <i class="fas fa-eye"></i>
            Preview
          </button>
          <button @click="uploadAndProcess" class="btn btn-primary" :disabled="!selectedFile || isUploading">
            <i class="fas fa-upload" :class="{ 'fa-spin': isUploading }"></i>
            {{ isUploading ? 'Processing...' : 'Upload & Process' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Preview Modal -->
    <div v-if="showPreviewModal" class="modal-overlay" @click="closePreviewModal">
      <div class="modal-content modal-large" @click.stop>
        <div class="modal-header">
          <h3>
            <i class="fas fa-eye text-primary"></i>
            AI Extraction Preview
          </h3>
          <button @click="closePreviewModal" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="modal-body">
          <div v-if="previewData" class="preview-content">
            <!-- Overall Confidence Score -->
            <div class="preview-section">
              <div class="confidence-header">
                <div class="confidence-info">
                  <h4>
                    <i class="fas fa-brain"></i>
                    AI Confidence Score
                  </h4>
                  <div class="confidence-display-large">
                    <div class="confidence-bar-large">
                      <div
                        class="confidence-fill"
                        :style="{ width: previewData.confidence_score + '%' }"
                        :class="getConfidenceClass(previewData.confidence_score)"
                      ></div>
                    </div>
                    <span class="confidence-text-large">{{ previewData.confidence_score }}%</span>
                  </div>
                  <p class="confidence-description">
                    {{ getConfidenceDescription(previewData.confidence_score) }}
                  </p>
                </div>
                <div class="extraction-status">
                  <span class="status-badge status-success">
                    <i class="fas fa-check-circle"></i>
                    Data Extracted
                  </span>
                </div>
              </div>
            </div>

            <!-- Customer Information -->
            <div v-if="previewData.extracted_data && previewData.extracted_data.customer" class="preview-section">
              <h4>
                <i class="fas fa-user"></i>
                Customer Information
              </h4>
              <div class="customer-preview-card">
                <div class="customer-header">
                  <div class="customer-name-section">
                    <h5>{{ previewData.extracted_data.customer.name || 'Unknown Customer' }}</h5>
                    <div v-if="previewData.extracted_data.customer.confidence" class="field-confidence">
                      <span :class="getConfidenceClass(previewData.extracted_data.customer.confidence)">
                        {{ previewData.extracted_data.customer.confidence }}% confidence
                      </span>
                    </div>
                  </div>
                </div>

                <div class="customer-details">
                  <div class="customer-field" v-if="previewData.extracted_data.customer.email">
                    <label>
                      <i class="fas fa-envelope"></i>
                      Email
                    </label>
                    <span>{{ previewData.extracted_data.customer.email }}</span>
                  </div>

                  <div class="customer-field" v-if="previewData.extracted_data.customer.phone">
                    <label>
                      <i class="fas fa-phone"></i>
                      Phone
                    </label>
                    <span>{{ previewData.extracted_data.customer.phone }}</span>
                  </div>

                  <div class="customer-field" v-if="previewData.extracted_data.customer.address">
                    <label>
                      <i class="fas fa-map-marker-alt"></i>
                      Address
                    </label>
                    <span>{{ previewData.extracted_data.customer.address }}</span>
                  </div>

                  <div class="customer-field" v-if="previewData.extracted_data.customer.code">
                    <label>
                      <i class="fas fa-tag"></i>
                      Customer Code
                    </label>
                    <span>{{ previewData.extracted_data.customer.code }}</span>
                  </div>

                  <div class="customer-field" v-if="previewData.extracted_data.customer.tax_id">
                    <label>
                      <i class="fas fa-file-invoice"></i>
                      Tax ID
                    </label>
                    <span>{{ previewData.extracted_data.customer.tax_id }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Items Information -->
            <div v-if="previewData.extracted_data && previewData.extracted_data.items && previewData.extracted_data.items.length > 0" class="preview-section">
              <h4>
                <i class="fas fa-list"></i>
                Order Items ({{ previewData.extracted_data.items.length }})
              </h4>

              <div class="items-summary">
                <div class="summary-stats">
                  <div class="summary-stat">
                    <span class="stat-label">Total Items:</span>
                    <span class="stat-value">{{ previewData.extracted_data.items.length }}</span>
                  </div>
                  <div class="summary-stat" v-if="getTotalQuantity() > 0">
                    <span class="stat-label">Total Quantity:</span>
                    <span class="stat-value">{{ getTotalQuantity() }}</span>
                  </div>
                  <div class="summary-stat" v-if="getTotalAmount() > 0">
                    <span class="stat-label">Total Amount:</span>
                    <span class="stat-value">${{ formatCurrency(getTotalAmount()) }}</span>
                  </div>
                </div>
              </div>

              <div class="items-list">
                <div v-for="(item, index) in previewData.extracted_data.items" :key="index" class="item-preview-card">
                  <div class="item-header">
                    <div class="item-name-section">
                      <h6>{{ item.name || 'Unknown Item' }}</h6>
                      <div v-if="item.item_code" class="item-code">
                        <span class="code-label">Code:</span> {{ item.item_code }}
                      </div>
                      <div v-if="item.confidence" class="field-confidence">
                        <span :class="getConfidenceClass(item.confidence)">
                          {{ item.confidence }}% confidence
                        </span>
                      </div>
                    </div>
                    <div class="item-amount">
                      <span v-if="item.unit_price && item.quantity" class="amount-value">
                        ${{ formatCurrency(item.unit_price * item.quantity) }}
                      </span>
                      <span v-else-if="item.total_value" class="amount-value">
                        ${{ formatCurrency(item.total_value) }}
                      </span>
                    </div>
                  </div>

                  <div class="item-details">
                    <div class="item-detail-row">
                      <div class="item-field" v-if="item.quantity">
                        <label>Quantity:</label>
                        <span>{{ item.quantity }}</span>
                      </div>

                      <div class="item-field" v-if="item.unit_price">
                        <label>Unit Price:</label>
                        <span>${{ formatCurrency(item.unit_price) }}</span>
                      </div>

                      <div class="item-field" v-if="item.uom">
                        <label>UOM:</label>
                        <span>{{ item.uom }}</span>
                      </div>

                      <div class="item-field" v-if="item.total_value">
                        <label>Total Value:</label>
                        <span>${{ formatCurrency(item.total_value) }}</span>
                      </div>
                    </div>

                    <div v-if="item.description" class="item-description">
                      <label>Description:</label>
                      <p>{{ item.description }}</p>
                    </div>

                    <div v-if="item.validation_check" class="item-validation">
                      <label>Validation:</label>
                      <p class="validation-text">{{ item.validation_check }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Order Information -->
            <div v-if="previewData.extracted_data && previewData.extracted_data.order_info" class="preview-section">
              <h4>
                <i class="fas fa-file-invoice"></i>
                Order Information
              </h4>
              <div class="order-info-card">
                <div class="order-info-grid">
                  <div class="order-field" v-if="previewData.extracted_data.order_info.order_number">
                    <label>Order Number:</label>
                    <span>{{ previewData.extracted_data.order_info.order_number }}</span>
                  </div>

                  <div class="order-field" v-if="previewData.extracted_data.order_info.order_date">
                    <label>Order Date:</label>
                    <span>{{ previewData.extracted_data.order_info.order_date }}</span>
                  </div>

                  <div class="order-field" v-if="previewData.extracted_data.order_info.currency">
                    <label>Currency:</label>
                    <span>{{ previewData.extracted_data.order_info.currency }}</span>
                  </div>

                  <div class="order-field" v-if="previewData.extracted_data.order_info.expected_delivery">
                    <label>Expected Delivery:</label>
                    <span>{{ previewData.extracted_data.order_info.expected_delivery }}</span>
                  </div>

                  <div class="order-field" v-if="previewData.extracted_data.order_info.payment_terms">
                    <label>Payment Terms:</label>
                    <span>{{ previewData.extracted_data.order_info.payment_terms }}</span>
                  </div>

                  <div class="order-field" v-if="previewData.extracted_data.order_info.delivery_terms">
                    <label>Delivery Terms:</label>
                    <span>{{ previewData.extracted_data.order_info.delivery_terms }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Vendor Information -->
            <div v-if="previewData.extracted_data && previewData.extracted_data.vendor_info" class="preview-section">
              <h4>
                <i class="fas fa-building"></i>
                Vendor Information
              </h4>
              <div class="vendor-info-card">
                <div class="vendor-details">
                  <div class="vendor-field" v-if="previewData.extracted_data.vendor_info.name">
                    <label>
                      <i class="fas fa-building"></i>
                      Company Name
                    </label>
                    <span>{{ previewData.extracted_data.vendor_info.name }}</span>
                  </div>

                  <div class="vendor-field" v-if="previewData.extracted_data.vendor_info.address">
                    <label>
                      <i class="fas fa-map-marker-alt"></i>
                      Address
                    </label>
                    <span>{{ previewData.extracted_data.vendor_info.address }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Processing Notes -->
            <div v-if="previewData.extracted_data && (previewData.extracted_data.number_format_notes || previewData.extracted_data.table_structure_notes)" class="preview-section">
              <h4>
                <i class="fas fa-sticky-note"></i>
                Processing Notes
              </h4>
              <div class="notes-list">
                <div v-if="previewData.extracted_data.number_format_notes" class="note-item">
                  <div class="note-type info">
                    <i class="fas fa-info-circle"></i>
                  </div>
                  <div class="note-content">
                    <p><strong>Number Format:</strong> {{ previewData.extracted_data.number_format_notes }}</p>
                  </div>
                </div>

                <div v-if="previewData.extracted_data.table_structure_notes" class="note-item">
                  <div class="note-type info">
                    <i class="fas fa-table"></i>
                  </div>
                  <div class="note-content">
                    <p><strong>Table Structure:</strong> {{ previewData.extracted_data.table_structure_notes }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="preview-loading">
            <div class="loading-spinner">
              <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p>Generating preview...</p>
          </div>
        </div>

        <div class="modal-footer">
          <button @click="closePreviewModal" class="btn btn-secondary">
            Close Preview
          </button>
          <button @click="editPreviewData" class="btn btn-outline">
            <i class="fas fa-edit"></i>
            Edit Data
          </button>
          <button @click="proceedWithPreview" class="btn btn-primary">
            <i class="fas fa-arrow-right"></i>
            Proceed to Create Order
          </button>
        </div>
      </div>
    </div>

    <!-- Details Modal -->
    <div v-if="showDetailsModal" class="modal-overlay" @click="closeDetailsModal">
      <div class="modal-content modal-large" @click.stop>
        <div class="modal-header">
          <h3>Capture Details</h3>
          <button @click="closeDetailsModal" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="modal-body">
          <div v-if="selectedCapture" class="details-content">
            <!-- Basic Info -->
            <div class="details-section">
              <h4>File Information</h4>
              <div class="info-grid">
                <div class="info-item">
                  <label>Filename</label>
                  <span>{{ selectedCapture.filename }}</span>
                </div>
                <div class="info-item">
                  <label>File Size</label>
                  <span>{{ selectedCapture.file_size_human }}</span>
                </div>
                <div class="info-item">
                  <label>Status</label>
                  <span class="status-badge" :class="getStatusClass(selectedCapture.status)">
                    {{ formatStatus(selectedCapture.status) }}
                  </span>
                </div>
                <div class="info-item">
                  <label>Confidence Score</label>
                  <span>{{ selectedCapture.confidence_score || 'N/A' }}%</span>
                </div>
              </div>
            </div>

            <!-- Extracted Data -->
            <div v-if="selectedCapture.extracted_data" class="details-section">
              <h4>Extracted Data</h4>

              <!-- Customer Info -->
              <div v-if="selectedCapture.extracted_customer" class="extracted-section">
                <h5>Customer Information</h5>
                <div class="info-grid">
                  <div class="info-item">
                    <label>Name</label>
                    <span>{{ selectedCapture.extracted_customer.name }}</span>
                  </div>
                  <div class="info-item">
                    <label>Email</label>
                    <span>{{ selectedCapture.extracted_customer.email || 'N/A' }}</span>
                  </div>
                  <div class="info-item">
                    <label>Phone</label>
                    <span>{{ selectedCapture.extracted_customer.phone || 'N/A' }}</span>
                  </div>
                  <div class="info-item">
                    <label>Address</label>
                    <span>{{ selectedCapture.extracted_customer.address || 'N/A' }}</span>
                  </div>
                </div>
              </div>

              <!-- Items -->
              <div v-if="selectedCapture.extracted_items" class="extracted-section">
                <h5>Items ({{ selectedCapture.extracted_items.length }})</h5>
                <div class="items-list">
                  <div v-for="(item, index) in selectedCapture.extracted_items" :key="index" class="item-card">
                    <div class="item-header">
                      <span class="item-name">{{ item.name }}</span>
                      <span class="item-qty">Qty: {{ item.quantity }}</span>
                    </div>
                    <div class="item-details">
                      <span v-if="item.unit_price">Price: ${{ item.unit_price }}</span>
                      <span v-if="item.uom">UOM: {{ item.uom }}</span>
                      <span v-if="item.description">{{ item.description }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Error Message -->
            <div v-if="selectedCapture.error_message" class="details-section">
              <h4>Error Details</h4>
              <div class="error-message">
                <i class="fas fa-exclamation-triangle text-danger"></i>
                {{ selectedCapture.error_message }}
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button @click="closeDetailsModal" class="btn btn-secondary">
            Close
          </button>
          <button
            v-if="selectedCapture && selectedCapture.file_path"
            @click="downloadFile(selectedCapture.id)"
            class="btn btn-outline"
          >
            <i class="fas fa-download"></i>
            Download PDF
          </button>
          <button
            v-if="selectedCapture && selectedCapture.status === 'failed'"
            @click="retryCapture(selectedCapture.id)"
            class="btn btn-warning"
          >
            <i class="fas fa-redo"></i>
            Retry Processing
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'PdfOrderCapture',
  data() {
    return {
      // State
      isLoading: false,
      isUploading: false,
      bulkLoading: false,
      retryLoading: {},
      deleteLoading: {},

      // Data
      captures: [],
      statistics: {},

      // Pagination
      pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 20,
        total: 0,
        from: 0,
        to: 0
      },

      // Filters
      filters: {
        status: '',
        days: '30',
        per_page: 20
      },

      // Selection
      selectedCaptures: [],

      // Modals
      showUploadModal: false,
      showDetailsModal: false,
      showPreviewModal: false,
      selectedCapture: null,

      // Upload
      selectedFile: null,
      isDragOver: false,
      uploadOptions: {
        auto_create_missing_data: true,
        preferred_currency: 'USD',
        processing_options: {
          confidence_threshold: 80,
          auto_approve: false,
          use_ocr: true
        }
      },

      // Preview Data
      previewData: null
    }
  },

  computed: {
    visiblePages() {
      const current = this.pagination.current_page
      const last = this.pagination.last_page
      const pages = []

      if (last <= 7) {
        for (let i = 1; i <= last; i++) {
          pages.push(i)
        }
      } else {
        if (current <= 4) {
          for (let i = 1; i <= 5; i++) {
            pages.push(i)
          }
          pages.push('...')
          pages.push(last)
        } else if (current >= last - 3) {
          pages.push(1)
          pages.push('...')
          for (let i = last - 4; i <= last; i++) {
            pages.push(i)
          }
        } else {
          pages.push(1)
          pages.push('...')
          for (let i = current - 1; i <= current + 1; i++) {
            pages.push(i)
          }
          pages.push('...')
          pages.push(last)
        }
      }

      return pages
    }
  },

  async mounted() {
    await this.loadData()
  },

  methods: {
    async loadData() {
      await Promise.all([
        this.loadStatistics(),
        this.loadCaptureHistory()
      ])
    },

    async loadStatistics() {
      try {
        const response = await axios.get('/pdf-order-capture/statistics/overview', {
          params: { days: this.filters.days }
        })
        this.statistics = response.data.data
      } catch (error) {
        console.error('Failed to load statistics:', error)
        this.$toast?.error('Failed to load statistics')
      }
    },

    async loadCaptureHistory() {
      this.isLoading = true
      try {
        const params = {
          page: this.pagination.current_page,
          per_page: this.filters.per_page
        }

        if (this.filters.status) params.status = this.filters.status
        if (this.filters.days) params.days = this.filters.days

        const response = await axios.get('/pdf-order-capture', { params })
        const data = response.data.data

        this.captures = data.data
        this.pagination = {
          current_page: data.current_page,
          last_page: data.last_page,
          per_page: data.per_page,
          total: data.total,
          from: data.from,
          to: data.to
        }
      } catch (error) {
        console.error('Failed to load capture history:', error)
        this.$toast?.error('Failed to load capture history')
      } finally {
        this.isLoading = false
      }
    },

    async refreshData() {
      await this.loadData()
      this.$toast?.success('Data refreshed successfully')
    },

    // Upload Functions
    handleFileSelect(event) {
      const file = event.target.files[0]
      if (file && file.type === 'application/pdf') {
        this.selectedFile = file
      } else {
        this.$toast?.error('Please select a valid PDF file')
      }
    },

    handleDrop(event) {
      event.preventDefault()
      this.isDragOver = false

      const files = event.dataTransfer.files
      if (files.length > 0) {
        const file = files[0]
        if (file.type === 'application/pdf') {
          this.selectedFile = file
        } else {
          this.$toast?.error('Please select a valid PDF file')
        }
      }
    },

    clearSelectedFile() {
      this.selectedFile = null
      if (this.$refs.fileInput) {
        this.$refs.fileInput.value = ''
      }
    },

    // Preview Functions
    async previewExtraction() {
      if (!this.selectedFile) return

      this.isUploading = true
      this.previewData = null
      this.showPreviewModal = true

      try {
        const formData = new FormData()
        formData.append('pdf_file', this.selectedFile)

        const response = await axios.post('/pdf-order-capture/preview', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })

        this.previewData = response.data.data
        this.$toast?.success('Preview generated successfully')

      } catch (error) {
        console.error('Preview failed:', error)
        this.$toast?.error(error.response?.data?.message || 'Preview failed')
        this.closePreviewModal()
      } finally {
        this.isUploading = false
      }
    },

    // Updated methods for calculating totals
    getTotalQuantity() {
      if (!this.previewData?.extracted_data?.items) return 0
      return this.previewData.extracted_data.items.reduce((sum, item) => sum + (item.quantity || 0), 0)
    },

    getTotalAmount() {
      if (!this.previewData?.extracted_data?.items) return 0
      return this.previewData.extracted_data.items.reduce((sum, item) => {
        // Prioritize total_value if available, otherwise calculate from unit_price * quantity
        if (item.total_value) {
          return sum + parseFloat(item.total_value)
        } else if (item.unit_price && item.quantity) {
          return sum + (parseFloat(item.unit_price) * parseFloat(item.quantity))
        }
        return sum
      }, 0)
    },

    closePreviewModal() {
      this.showPreviewModal = false
      this.previewData = null
    },

    editPreviewData() {
      // TODO: Implement edit functionality
      this.$toast?.info('Edit functionality coming soon')
    },

    async proceedWithPreview() {
      if (!this.previewData) return

      // Close preview modal and proceed with upload
      this.closePreviewModal()
      await this.uploadAndProcess()
    },

    async uploadAndProcess() {
      if (!this.selectedFile) return

      this.isUploading = true
      try {
        const formData = new FormData()
        formData.append('pdf_file', this.selectedFile)
        formData.append('auto_create_missing_data', this.uploadOptions.auto_create_missing_data ? '1' : '0')
        formData.append('preferred_currency', this.uploadOptions.preferred_currency)
        formData.append('processing_options', JSON.stringify(this.uploadOptions.processing_options))

        const response = await axios.post('/pdf-order-capture', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })

        this.$toast?.success('PDF uploaded and processing started')
        this.closeUploadModal()
        await this.loadData()

        if (response.data.data.sales_order) {
          this.$router.push(`/sales/orders/${response.data.data.sales_order.so_id}`)
        }

      } catch (error) {
        console.error('Upload failed:', error)
        this.$toast?.error(error.response?.data?.message || 'Upload failed')
      } finally {
        this.isUploading = false
      }
    },

    // Action Functions
    async retryCapture(captureId) {
      this.$set(this.retryLoading, captureId, true)
      try {
        await axios.post(`/pdf-order-capture/${captureId}/retry`)
        this.$toast?.success('Processing restarted')
        await this.loadData()
      } catch (error) {
        console.error('Retry failed:', error)
        this.$toast?.error(error.response?.data?.message || 'Retry failed')
      } finally {
        this.$set(this.retryLoading, captureId, false)
      }
    },

    async deleteCapture(captureId) {
      if (!confirm('Are you sure you want to delete this capture?')) return

      this.$set(this.deleteLoading, captureId, true)
      try {
        await axios.delete(`/pdf-order-capture/${captureId}`)
        this.$toast?.success('Capture deleted successfully')
        await this.loadData()
      } catch (error) {
        console.error('Delete failed:', error)
        this.$toast?.error(error.response?.data?.message || 'Delete failed')
      } finally {
        this.$set(this.deleteLoading, captureId, false)
      }
    },

    async downloadFile(captureId) {
      try {
        const response = await axios.get(`/pdf-order-capture/${captureId}/download`, {
          responseType: 'blob'
        })

        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `capture_${captureId}.pdf`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)

      } catch (error) {
        console.error('Download failed:', error)
        this.$toast?.error('Download failed')
      }
    },

    async viewDetails(capture) {
      try {
        const response = await axios.get(`/pdf-order-capture/${capture.id}`)
        this.selectedCapture = response.data.data
        this.showDetailsModal = true
      } catch (error) {
        console.error('Failed to load details:', error)
        this.$toast?.error('Failed to load capture details')
      }
    },

    // Bulk Actions
    toggleSelectAll() {
      if (this.selectedCaptures.length === this.captures.length) {
        this.selectedCaptures = []
      } else {
        this.selectedCaptures = this.captures.map(c => c.id)
      }
    },

    async bulkRetry() {
      if (this.selectedCaptures.length === 0) return

      this.bulkLoading = true
      try {
        await axios.post('/pdf-order-capture/bulk/retry', {
          capture_ids: this.selectedCaptures
        })
        this.$toast?.success(`Retrying ${this.selectedCaptures.length} captures`)
        this.selectedCaptures = []
        await this.loadData()
      } catch (error) {
        console.error('Bulk retry failed:', error)
        this.$toast?.error('Bulk retry failed')
      } finally {
        this.bulkLoading = false
      }
    },

    // Pagination
    changePage(page) {
      if (page >= 1 && page <= this.pagination.last_page && page !== this.pagination.current_page) {
        this.pagination.current_page = page
        this.loadCaptureHistory()
      }
    },

    // Filters
    clearFilters() {
      this.filters = {
        status: '',
        days: '30',
        per_page: 20
      }
      this.pagination.current_page = 1
      this.loadCaptureHistory()
    },

    // Modal Functions
    closeUploadModal() {
      this.showUploadModal = false
      this.clearSelectedFile()
      this.isDragOver = false
    },

    closeDetailsModal() {
      this.showDetailsModal = false
      this.selectedCapture = null
    },

    // Helper Functions
    getStatusClass(status) {
      const statusClasses = {
        pending: 'status-secondary',
        processing: 'status-warning',
        data_extracted: 'status-info',
        validating: 'status-info',
        creating_order: 'status-warning',
        completed: 'status-success',
        failed: 'status-danger',
        cancelled: 'status-secondary'
      }
      return statusClasses[status] || 'status-secondary'
    },

    formatStatus(status) {
      const statusLabels = {
        pending: 'Pending',
        processing: 'Processing',
        data_extracted: 'Data Extracted',
        validating: 'Validating',
        creating_order: 'Creating Order',
        completed: 'Completed',
        failed: 'Failed',
        cancelled: 'Cancelled'
      }
      return statusLabels[status] || status
    },

    getConfidenceClass(score) {
      if (score >= 80) return 'confidence-high'
      if (score >= 60) return 'confidence-medium'
      return 'confidence-low'
    },

    getConfidenceDescription(score) {
      if (score >= 90) return 'Excellent accuracy - Data is highly reliable'
      if (score >= 80) return 'Good accuracy - Data is reliable with minor verification needed'
      if (score >= 70) return 'Moderate accuracy - Please review extracted data carefully'
      if (score >= 60) return 'Fair accuracy - Manual verification strongly recommended'
      return 'Low accuracy - Extracted data requires thorough review'
    },

    getNoteIcon(type) {
      const icons = {
        warning: 'fas fa-exclamation-triangle',
        error: 'fas fa-times-circle',
        info: 'fas fa-info-circle',
        success: 'fas fa-check-circle'
      }
      return icons[type] || 'fas fa-info-circle'
    },

    formatDate(dateString) {
      return new Date(dateString).toLocaleDateString()
    },

    formatTime(dateString) {
      return new Date(dateString).toLocaleTimeString()
    },

    formatCurrency(amount) {
      return parseFloat(amount).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      })
    },

    formatFileSize(bytes) {
      const units = ['B', 'KB', 'MB', 'GB']
      let size = bytes
      let unitIndex = 0

      while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024
        unitIndex++
      }

      return `${size.toFixed(1)} ${units[unitIndex]}`
    }
  }
}
</script>

<style scoped>
/* CSS Variables untuk konsistensi */
:root {
  --primary-color: #2563eb;
  --primary-hover: #1d4ed8;
  --success-color: #10b981;
  --warning-color: #f59e0b;
  --danger-color: #ef4444;
  --info-color: #3b82f6;

  --text-primary: #1f2937;
  --text-secondary: #6b7280;
  --text-muted: #9ca3af;

  --bg-primary: #ffffff;
  --bg-secondary: #f9fafb;
  --bg-light: #f3f4f6;

  --card-bg: #ffffff;
  --border-color: #e5e7eb;
  --border-light: #f3f4f6;

  --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);

  --radius-sm: 6px;
  --radius-md: 8px;
  --radius-lg: 12px;
  --radius-xl: 16px;
}

/* Reset dan Base Styles */
* {
  box-sizing: border-box;
}

.pdf-order-capture {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
  background: var(--bg-secondary);
  min-height: 100vh;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

/* Header Section */
.page-header {
  margin-bottom: 2rem;
  background: var(--card-bg);
  border-radius: var(--radius-lg);
  padding: 2rem;
  border: 1px solid var(--border-color);
  box-shadow: var(--shadow-sm);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 2rem;
}

.title-section {
  flex: 1;
}

.page-title {
  font-size: 2.25rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 0.75rem 0;
  display: flex;
  align-items: center;
  gap: 1rem;
  line-height: 1.2;
}

.page-title i {
  color: var(--danger-color);
  font-size: 2rem;
}

.page-subtitle {
  color: var(--text-secondary);
  font-size: 1.125rem;
  margin: 0;
  line-height: 1.5;
}

.header-actions {
  display: flex;
  gap: 1rem;
  flex-shrink: 0;
  align-items: center;
}

/* Statistics Cards */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-lg);
  padding: 1.75rem;
  display: flex;
  align-items: center;
  gap: 1.25rem;
  transition: all 0.2s ease;
  box-shadow: var(--shadow-sm);
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.stat-icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: white;
  flex-shrink: 0;
}

.stat-icon.success { background: var(--success-color); }
.stat-icon.warning { background: var(--warning-color); }
.stat-icon.danger { background: var(--danger-color); }
.stat-icon.info { background: var(--info-color); }

.stat-content {
  flex: 1;
}

.stat-content h3 {
  font-size: 2.25rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 0.25rem 0;
  line-height: 1;
}

.stat-content p {
  color: var(--text-secondary);
  margin: 0 0 0.5rem 0;
  font-weight: 500;
  font-size: 1rem;
}

.stat-change {
  font-size: 0.875rem;
  font-weight: 500;
}

.stat-change.success { color: var(--success-color); }
.stat-change.danger { color: var(--danger-color); }
.stat-change.neutral { color: var(--text-muted); }
.stat-change.info { color: var(--info-color); }

/* Filters Section */
.filters-section {
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-lg);
  padding: 1.75rem;
  margin-bottom: 2rem;
  box-shadow: var(--shadow-sm);
}

.search-filters {
  display: flex;
  gap: 1.5rem;
  align-items: end;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  min-width: 160px;
}

.filter-group label {
  font-weight: 600;
  color: var(--text-secondary);
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.filter-group select {
  padding: 0.75rem 1rem;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  background: #ffffff;
  color: var(--text-primary);
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.2s ease;
}

.filter-group select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

/* Table Container */
.table-container {
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.75rem;
  border-bottom: 1px solid var(--border-color);
  background: #f1f5f9;
}

.table-header h3 {
  margin: 0;
  color: var(--text-primary);
  font-size: 1.25rem;
  font-weight: 700;
}

.table-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
}

/* Table Wrapper */
.table-wrapper {
  overflow-x: auto;
  background: #ffffff;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  background: #ffffff;
}

.data-table th {
  text-align: left;
  padding: 1.25rem 1rem;
  background: #f1f5f9;
  border-bottom: 2px solid var(--border-color);
  font-weight: 700;
  color: var(--text-secondary);
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  white-space: nowrap;
}

.data-table td {
  padding: 1.25rem 1rem;
  border-bottom: 1px solid var(--border-light);
  vertical-align: middle;
  background: #ffffff;
}

.table-row {
  transition: background-color 0.2s ease;
}

.table-row:hover {
  background: #f8fafc;
}

.table-row:hover td {
  background: #f8fafc;
}

/* Table Cell Specific Styles */
.checkbox-col {
  width: 50px;
  text-align: center;
}

.file-info {
  min-width: 220px;
}

.file-details {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.file-name {
  font-weight: 600;
  color: var(--text-primary);
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.875rem;
}

.file-meta {
  font-size: 0.75rem;
  color: var(--text-muted);
  font-weight: 500;
}

/* Status Badges */
.status-badge {
  padding: 0.5rem 0.875rem;
  border-radius: var(--radius-md);
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  white-space: nowrap;
  border: 1px solid transparent;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.status-success {
  background: #dcfce7;
  color: #15803d;
  border-color: #bbf7d0;
}

.status-warning {
  background: #fef3c7;
  color: #a16207;
  border-color: #fde68a;
}

.status-danger {
  background: #fee2e2;
  color: #dc2626;
  border-color: #fecaca;
}

.status-info {
  background: #dbeafe;
  color: #1d4ed8;
  border-color: #bfdbfe;
}

.status-secondary {
  background: var(--bg-light);
  color: var(--text-muted);
  border-color: var(--border-color);
}

/* Customer and Items Info */
.customer-info, .items-info {
  min-width: 180px;
}

.customer-name, .items-count {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 0.875rem;
}

.customer-meta, .items-preview {
  font-size: 0.75rem;
  color: var(--text-muted);
  margin-top: 0.25rem;
}

/* Confidence Display */
.confidence-display {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-width: 120px;
}

.confidence-bar {
  flex: 1;
  height: 10px;
  background: #f1f5f9;
  border-radius: var(--radius-sm);
  overflow: hidden;
  border: 1px solid var(--border-color);
}

.confidence-fill {
  height: 100%;
  border-radius: var(--radius-sm);
  transition: width 0.3s ease;
}

.confidence-high { background: var(--success-color); }
.confidence-medium { background: var(--warning-color); }
.confidence-low { background: var(--danger-color); }

.confidence-text {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--text-primary);
  min-width: 35px;
}

/* Order Links */
.order-link {
  color: var(--primary-color);
  text-decoration: none;
  font-weight: 600;
  font-size: 0.875rem;
  transition: color 0.2s ease;
}

.order-link:hover {
  color: var(--primary-hover);
  text-decoration: underline;
}

.order-amount {
  font-size: 0.75rem;
  color: var(--text-muted);
  margin-top: 0.25rem;
  font-weight: 500;
}

/* Date Display */
.date-display {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  min-width: 120px;
}

.date-main {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 0.875rem;
}

.date-time {
  font-size: 0.75rem;
  color: var(--text-muted);
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.btn-icon {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  border: 1px solid var(--border-color);
  background: #ffffff;
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.875rem;
}

.btn-icon:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.btn-icon:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

.btn-icon.btn-warning {
  border-color: var(--warning-color);
  color: var(--warning-color);
}

.btn-icon.btn-secondary {
  border-color: var(--border-color);
  color: var(--text-secondary);
}

.btn-icon.btn-danger {
  border-color: var(--danger-color);
  color: var(--danger-color);
}

.btn-icon.btn-warning:hover { background: #fef3c7; }
.btn-icon.btn-secondary:hover { background: #f1f5f9; }
.btn-icon.btn-danger:hover { background: #fee2e2; }

/* Loading and Empty States */
.loading-state, .empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  text-align: center;
  background: #ffffff;
}

.loading-spinner, .empty-icon {
  font-size: 3.5rem;
  color: var(--text-muted);
  margin-bottom: 1.5rem;
}

.empty-state h3 {
  margin: 0 0 0.75rem 0;
  color: var(--text-primary);
  font-size: 1.5rem;
  font-weight: 700;
}

.empty-state p {
  color: var(--text-muted);
  margin-bottom: 2rem;
  font-size: 1rem;
  line-height: 1.5;
}

/* Pagination */
.pagination-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.75rem;
  border-top: 1px solid var(--border-color);
  background: #f8fafc;
}

.pagination-info {
  color: var(--text-muted);
  font-size: 0.875rem;
  font-weight: 500;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.page-numbers {
  display: flex;
  gap: 0.25rem;
  margin: 0 1rem;
}

/* Button Styles */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1.5rem;
  border-radius: var(--radius-md);
  border: 1px solid transparent;
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
  line-height: 1;
  white-space: nowrap;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none !important;
}

.btn-primary {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.btn-primary:hover:not(:disabled) {
  background: var(--primary-hover);
  border-color: var(--primary-hover);
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.btn-secondary {
  background: #ffffff;
  color: var(--text-secondary);
  border-color: var(--border-color);
}

.btn-secondary:hover:not(:disabled) {
  background: #f1f5f9;
  border-color: var(--text-secondary);
  color: var(--text-primary);
}

.btn-outline {
  background: #ffffff;
  color: var(--text-secondary);
  border-color: var(--border-color);
}

.btn-outline:hover:not(:disabled) {
  background: #f1f5f9;
  border-color: var(--primary-color);
  color: var(--primary-color);
}

.btn-warning {
  background: var(--warning-color);
  color: white;
  border-color: var(--warning-color);
}

.btn-warning:hover:not(:disabled) {
  background: #d97706;
  border-color: #d97706;
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.btn-danger {
  background: var(--danger-color);
  color: white;
  border-color: var(--danger-color);
}

.btn-danger:hover:not(:disabled) {
  background: #dc2626;
  border-color: #dc2626;
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.btn-sm {
  padding: 0.625rem 1rem;
  font-size: 0.75rem;
  gap: 0.5rem;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: #000000;
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 2rem;
}

.modal-content {
  background: #ffffff;
  border-radius: var(--radius-xl);
  width: 100%;
  max-width: 650px;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
  border: 1px solid var(--border-color);
  position: relative;
}

.modal-large {
  max-width: 900px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 2rem;
  border-bottom: 1px solid var(--border-color);
  background: #f8fafc;
}

.modal-header h3 {
  margin: 0;
  color: var(--text-primary);
  font-size: 1.5rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.close-btn {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  border: 1px solid var(--border-color);
  background: #ffffff;
  color: var(--text-muted);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  font-size: 1.125rem;
}

.close-btn:hover {
  background: #f1f5f9;
  color: var(--text-primary);
  border-color: var(--text-secondary);
}

.modal-body {
  flex: 1;
  padding: 2rem;
  overflow-y: auto;
  background: #ffffff;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 2rem;
  border-top: 1px solid var(--border-color);
  background: #f8fafc;
}

/* Upload Area */
.upload-area {
  border: 2px dashed var(--border-color);
  border-radius: var(--radius-lg);
  padding: 3rem 2rem;
  text-align: center;
  transition: all 0.2s ease;
  margin-bottom: 2rem;
  background: #f8fafc;
}

.upload-area.dragover {
  border-color: var(--primary-color);
  background: #eff6ff;
}

.upload-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.25rem;
}

.upload-icon {
  font-size: 3.5rem;
  color: var(--text-muted);
}

.upload-placeholder h4 {
  margin: 0;
  color: var(--text-primary);
  font-size: 1.25rem;
  font-weight: 700;
}

.upload-placeholder p {
  margin: 0;
  color: var(--text-muted);
  font-size: 1rem;
}

.link-btn {
  background: none;
  border: none;
  color: var(--primary-color);
  cursor: pointer;
  text-decoration: underline;
  font-weight: 600;
  font-size: inherit;
}

.upload-info {
  margin-top: 1rem;
}

.upload-info small {
  color: var(--text-muted);
  font-size: 0.875rem;
}

.file-selected {
  display: flex;
  justify-content: center;
}

.file-preview {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  padding: 1.25rem 1.75rem;
  background: #ffffff;
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-color);
  max-width: 450px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.file-preview i {
  font-size: 2.25rem;
}

.remove-btn {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 1px solid var(--border-color);
  background: #f1f5f9;
  color: var(--text-muted);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.remove-btn:hover {
  background: var(--danger-color);
  color: white;
  border-color: var(--danger-color);
}

/* Processing Options */
.processing-options {
  border-top: 1px solid var(--border-color);
  padding-top: 2rem;
}

.processing-options h4 {
  margin: 0 0 1.5rem 0;
  color: var(--text-primary);
  font-size: 1.125rem;
  font-weight: 700;
}

.option-group {
  margin-bottom: 1.5rem;
}

.option-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.option-field {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.option-field label {
  font-weight: 600;
  color: var(--text-secondary);
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.option-field select {
  padding: 0.875rem 1rem;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  background: #ffffff;
  color: var(--text-primary);
  font-weight: 500;
  transition: all 0.2s ease;
}

.option-field select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 1rem;
  cursor: pointer;
  color: var(--text-primary);
  font-weight: 500;
  padding: 0.5rem 0;
}

.checkbox-label input[type="checkbox"] {
  width: 20px;
  height: 20px;
  accent-color: var(--primary-color);
}

/* Preview Modal Styles */
.preview-content {
  display: flex;
  flex-direction: column;
  gap: 2.5rem;
}

.preview-section {
  border-bottom: 1px solid var(--border-color);
  padding-bottom: 2rem;
}

.preview-section:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.preview-section h4 {
  margin: 0 0 1.5rem 0;
  color: var(--text-primary);
  font-size: 1.25rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

/* Confidence Header */
.confidence-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 2rem;
}

.confidence-info {
  flex: 1;
}

.confidence-display-large {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin: 1rem 0;
}

.confidence-bar-large {
  flex: 1;
  height: 16px;
  background: #f1f5f9;
  border-radius: var(--radius-md);
  overflow: hidden;
  border: 1px solid var(--border-color);
  max-width: 300px;
}

.confidence-text-large {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text-primary);
  min-width: 60px;
}

.confidence-description {
  color: var(--text-secondary);
  margin: 0;
  font-size: 0.875rem;
  line-height: 1.5;
}

.extraction-status {
  flex-shrink: 0;
}

/* Customer Preview Card */
.customer-preview-card {
  background: #f8fafc;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-lg);
  padding: 1.75rem;
}

.customer-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
}

.customer-name-section h5 {
  margin: 0 0 0.5rem 0;
  color: var(--text-primary);
  font-size: 1.25rem;
  font-weight: 700;
}

.field-confidence {
  font-size: 0.75rem;
  font-weight: 600;
}

.customer-details {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.25rem;
}

.customer-field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.customer-field label {
  font-size: 0.75rem;
  color: var(--text-muted);
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.customer-field span {
  color: var(--text-primary);
  font-weight: 600;
  font-size: 0.875rem;
}

/* Items Summary */
.items-summary {
  background: #f8fafc;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-lg);
  padding: 1.25rem;
  margin-bottom: 1.5rem;
}

.summary-stats {
  display: flex;
  gap: 2rem;
  flex-wrap: wrap;
}

.summary-stat {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.stat-label {
  font-size: 0.75rem;
  color: var(--text-muted);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.stat-value {
  font-size: 1.125rem;
  color: var(--text-primary);
  font-weight: 700;
}

/* Item Preview Cards */
.items-list {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.item-preview-card {
  background: #f8fafc;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-lg);
  padding: 1.5rem;
  transition: all 0.2s ease;
}

.item-preview-card:hover {
  background: #ffffff;
  box-shadow: var(--shadow-md);
}

.item-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.item-name-section h6 {
  margin: 0 0 0.5rem 0;
  color: var(--text-primary);
  font-size: 1rem;
  font-weight: 700;
}

.amount-value {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--success-color);
}

.item-details {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.item-detail-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.item-field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.item-field label {
  font-size: 0.75rem;
  color: var(--text-muted);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.item-field span {
  color: var(--text-primary);
  font-weight: 600;
  font-size: 0.875rem;
}

.item-description {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.item-description p {
  margin: 0;
  color: var(--text-primary);
  font-size: 0.875rem;
  line-height: 1.5;
}

/* Item Code Styling */
.item-code {
  font-size: 0.75rem;
  color: var(--text-muted);
  margin-top: 0.25rem;
}

.code-label {
  font-weight: 600;
  color: var(--text-secondary);
}

/* Vendor Info Card */
.vendor-info-card {
  background: #f8fafc;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-lg);
  padding: 1.75rem;
}

.vendor-details {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.25rem;
}

.vendor-field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.vendor-field label {
  font-size: 0.75rem;
  color: var(--text-muted);
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.vendor-field span {
  color: var(--text-primary);
  font-weight: 600;
  font-size: 0.875rem;
}

/* Item Validation Styling */
.item-validation {
  margin-top: 1rem;
  padding: 0.75rem;
  background: #f0f9ff;
  border: 1px solid #bae6fd;
  border-radius: var(--radius-md);
}

.validation-text {
  margin: 0;
  color: #0369a1;
  font-size: 0.875rem;
  font-family: 'Monaco', 'Menlo', monospace;
  font-weight: 500;
}

/* Order Info Card */
.order-info-card {
  background: #f8fafc;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-lg);
  padding: 1.75rem;
}

.order-info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.25rem;
}

.order-field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.order-field label {
  font-size: 0.75rem;
  color: var(--text-muted);
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.order-field span {
  color: var(--text-primary);
  font-weight: 600;
  font-size: 0.875rem;
}

/* Notes List */
.notes-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.note-item {
  display: flex;
  gap: 1rem;
  align-items: flex-start;
  padding: 1rem;
  background: #f8fafc;
  border-radius: var(--radius-md);
  border: 1px solid var(--border-color);
}

.note-type {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 0.875rem;
}

.note-type.info {
  background: #dbeafe;
  color: #1d4ed8;
}

.note-type.warning {
  background: #fef3c7;
  color: #a16207;
}

.note-type.error {
  background: #fee2e2;
  color: #dc2626;
}

.note-type.success {
  background: #dcfce7;
  color: #15803d;
}

.note-content p {
  margin: 0;
  color: var(--text-primary);
  font-size: 0.875rem;
  line-height: 1.5;
}

.note-content strong {
  color: var(--text-primary);
  font-weight: 700;
}

/* Preview Loading */
.preview-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  text-align: center;
}

/* Details Modal Content */
.details-content {
  display: flex;
  flex-direction: column;
  gap: 2.5rem;
}

.details-section {
  border-bottom: 1px solid var(--border-color);
  padding-bottom: 2rem;
}

.details-section:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.details-section h4 {
  margin: 0 0 1.5rem 0;
  color: var(--text-primary);
  font-size: 1.25rem;
  font-weight: 700;
}

.details-section h5 {
  margin: 0 0 1.25rem 0;
  color: var(--text-secondary);
  font-size: 1rem;
  font-weight: 600;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.5rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.info-item label {
  font-size: 0.75rem;
  color: var(--text-muted);
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.info-item span {
  color: var(--text-primary);
  font-weight: 600;
  font-size: 0.875rem;
}

.extracted-section {
  margin-bottom: 2rem;
}

.item-card {
  background: #f8fafc;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-lg);
  padding: 1.5rem;
}

.item-name {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 1rem;
}

.item-qty {
  font-size: 0.875rem;
  color: var(--text-muted);
  background: #ffffff;
  padding: 0.375rem 0.75rem;
  border-radius: var(--radius-md);
  font-weight: 600;
  border: 1px solid var(--border-color);
}

.error-message {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1.5rem;
  background: #fee2e2;
  border: 1px solid #fecaca;
  border-radius: var(--radius-lg);
  color: #991b1b;
  font-weight: 500;
}

.error-message i {
  font-size: 1.25rem;
  flex-shrink: 0;
  margin-top: 0.125rem;
}

/* Utility Classes */
.text-muted {
  color: var(--text-muted);
}

.text-danger {
  color: var(--danger-color);
}

.text-primary {
  color: var(--primary-color);
}

/* Responsive Design */
@media (max-width: 1200px) {
  .pdf-order-capture {
    padding: 1.5rem;
  }

  .stats-grid {
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  }
}

@media (max-width: 768px) {
  .pdf-order-capture {
    padding: 1rem;
  }

  .header-content {
    flex-direction: column;
    gap: 1.5rem;
  }

  .header-actions {
    align-self: stretch;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .search-filters {
    flex-direction: column;
    align-items: stretch;
  }

  .filter-group {
    min-width: auto;
  }

  .pagination-container {
    flex-direction: column;
    gap: 1.5rem;
    align-items: stretch;
    text-align: center;
  }

  .table-header {
    flex-direction: column;
    gap: 1.5rem;
    align-items: stretch;
  }

  .data-table {
    font-size: 0.8rem;
  }

  .data-table th,
  .data-table td {
    padding: 0.75rem 0.5rem;
  }

  .action-buttons {
    flex-direction: column;
    gap: 0.375rem;
  }

  .option-row {
    grid-template-columns: 1fr;
  }

  .modal-overlay {
    padding: 1rem;
  }

  .modal-content {
    max-height: 95vh;
  }

  .modal-header,
  .modal-body,
  .modal-footer {
    padding: 1.5rem;
  }

  .info-grid {
    grid-template-columns: 1fr;
  }

  .page-title {
    font-size: 1.875rem;
  }

  .upload-area {
    padding: 2rem 1rem;
  }

  .file-preview {
    max-width: 100%;
    flex-direction: column;
    text-align: center;
  }

  .confidence-header {
    flex-direction: column;
    gap: 1.5rem;
  }

  .customer-details {
    grid-template-columns: 1fr;
  }

  .summary-stats {
    flex-direction: column;
    gap: 1rem;
  }

  .item-detail-row {
    grid-template-columns: 1fr;
    gap: 0.75rem;
  }

  .order-info-grid {
    grid-template-columns: 1fr;
  }

  .vendor-details {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 480px) {
  .btn {
    padding: 0.75rem 1.25rem;
    font-size: 0.8rem;
  }

  .btn-sm {
    padding: 0.5rem 0.875rem;
    font-size: 0.7rem;
  }

  .stat-card {
    padding: 1.25rem;
  }

  .stat-icon {
    width: 56px;
    height: 56px;
    font-size: 1.25rem;
  }

  .stat-content h3 {
    font-size: 1.875rem;
  }

  .modal-footer {
    flex-direction: column;
    gap: 0.75rem;
  }

  .modal-footer .btn {
    width: 100%;
    justify-content: center;
  }

  .summary-stats {
    gap: 0.5rem;
  }

  .item-detail-row {
    gap: 0.5rem;
  }
}
</style>
