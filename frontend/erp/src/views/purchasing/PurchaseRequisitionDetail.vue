<!-- src/views/purchasing/PurchaseRequisitionDetail.vue -->
<template>
  <div class="purchase-requisition-detail">
    <!-- Enhanced Page Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="title-section">
          <h1 class="page-title">
            <i class="fas fa-file-alt"></i>
            Purchase Requisition Detail
          </h1>
          <p class="page-subtitle">Manage and track your purchase requisition</p>
        </div>
        <div class="action-buttons">
          <router-link
            v-if="pr.status === 'draft'"
            :to="`/purchasing/requisitions/${pr.pr_id}/edit`"
            class="btn btn-primary"
            v-tooltip="'Edit this requisition'"
          >
            <i class="fas fa-edit"></i>
            <span>Edit</span>
          </router-link>

          <button
            v-if="pr.status === 'draft'"
            @click="submitPR"
            class="btn btn-success"
            :disabled="loading"
            v-tooltip="'Submit for approval'"
          >
            <i class="fas fa-paper-plane"></i>
            <span>Submit for Approval</span>
          </button>

          <router-link
            v-if="pr.status === 'pending'"
            :to="`/purchasing/requisitions/${pr.pr_id}/approve`"
            class="btn btn-warning"
            v-tooltip="'Review and approve'"
          >
            <i class="fas fa-check-circle"></i>
            <span>Review & Approve</span>
          </router-link>

          <router-link
            v-if="pr.status === 'approved'"
            :to="`/purchasing/requisitions/${pr.pr_id}/convert`"
            class="btn btn-info"
            v-tooltip="'Convert to RFQ'"
          >
            <i class="fas fa-exchange-alt"></i>
            <span>Convert to RFQ</span>
          </router-link>

          <router-link v-if="pr.status === 'approved'"
            :to="{ name: 'PRVendorRecommendations', params: { id: pr.pr_id } }"
            class="btn btn-primary">
            📊 Vendor Recommendations
          </router-link>

          <router-link v-if="pr.status === 'approved'"
            :to="{ name: 'ProcurementPathAnalysis', params: { id: pr.pr_id } }"
            class="btn btn-outline">
            🔍 Procurement Analysis
          </router-link>

          <router-link v-if="pr.status === 'approved'"
            :to="{ name: 'CreateMultiVendorPO', params: { id: pr.pr_id } }"
            class="btn btn-secondary">
            🏢 Multi-Vendor PO
          </router-link>

          <button
            v-if="['draft', 'pending'].includes(pr.status)"
            @click="showCancelConfirmation = true"
            class="btn btn-danger"
            v-tooltip="'Cancel this requisition'"
          >
            <i class="fas fa-times"></i>
            <span>Cancel PR</span>
          </button>

          <div class="dropdown">
            <button
              class="btn btn-secondary dropdown-toggle"
              @click="showDropdown = !showDropdown"
              v-tooltip="'More actions'"
            >
              <i class="fas fa-ellipsis-v"></i>
              <span>More</span>
            </button>
            <div class="dropdown-menu" :class="{ 'show': showDropdown }">
              <a href="#" @click.prevent="printPR" class="dropdown-item">
                <i class="fas fa-print"></i> Print
              </a>
              <a href="#" @click.prevent="exportPDF" class="dropdown-item">
                <i class="fas fa-file-pdf"></i> Export PDF
              </a>
              <a href="#" @click.prevent="shareDocument" class="dropdown-item">
                <i class="fas fa-share"></i> Share
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="loading-spinner">
        <div class="spinner"></div>
        <p>Loading purchase requisition...</p>
      </div>
    </div>

    <!-- Main Content -->
    <div v-else class="main-content">
      <div class="content-grid">
        <!-- Left Column -->
        <div class="left-column">
          <!-- Requisition Information Card -->
          <div class="info-card card-animate" @mouseenter="cardHover = 'info'" @mouseleave="cardHover = null">
            <div class="card-header">
              <div class="header-content">
                <h5 class="card-title">
                  <i class="fas fa-info-circle"></i>
                  Requisition Information
                </h5>
                <button class="expand-btn" @click="expandedCards.info = !expandedCards.info">
                  <i class="fas" :class="expandedCards.info ? 'fa-compress' : 'fa-expand'"></i>
                </button>
              </div>
            </div>
            <div class="card-body" :class="{ 'expanded': expandedCards.info }">
              <div class="info-grid">
                <div class="info-item" v-for="(item, index) in infoItems" :key="index">
                  <div class="info-label">{{ item.label }}</div>
                  <div class="info-value" :class="item.class">
                    <component :is="item.component" v-if="item.component" v-bind="item.props">
                      {{ item.value }}
                    </component>
                    <template v-else>{{ item.value }}</template>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Requisition Lines Card -->
          <div class="lines-card card-animate" @mouseenter="cardHover = 'lines'" @mouseleave="cardHover = null">
            <div class="card-header">
              <div class="header-content">
                <h5 class="card-title">
                  <i class="fas fa-list"></i>
                  Requisition Lines
                  <span class="item-count">{{ pr.lines.length }} items</span>
                </h5>
                <div class="header-actions">
                  <button class="filter-btn" @click="showFilters = !showFilters">
                    <i class="fas fa-filter"></i>
                  </button>
                  <button class="expand-btn" @click="expandedCards.lines = !expandedCards.lines">
                    <i class="fas" :class="expandedCards.lines ? 'fa-compress' : 'fa-expand'"></i>
                  </button>
                </div>
              </div>
            </div>

            <!-- Filters -->
            <div v-if="showFilters" class="filters-section">
              <div class="filter-input">
                <input
                  type="text"
                  v-model="searchQuery"
                  placeholder="Search items..."
                  class="form-control"
                >
                <i class="fas fa-search"></i>
              </div>
            </div>

            <div class="card-body p-0" :class="{ 'expanded': expandedCards.lines }">
              <div class="table-container">
                <table class="enhanced-table">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th @click="sortBy('item')" class="sortable">
                        Item
                        <i class="fas fa-sort"></i>
                      </th>
                      <th @click="sortBy('quantity')" class="sortable">
                        Quantity
                        <i class="fas fa-sort"></i>
                      </th>
                      <th>Unit</th>
                      <th @click="sortBy('required_date')" class="sortable">
                        Required Date
                        <i class="fas fa-sort"></i>
                      </th>
                      <th>Notes</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="(line, index) in filteredLines"
                      :key="index"
                      class="table-row-animate"
                      @click="selectLine(index)"
                      :class="{ 'selected': selectedLine === index }"
                    >
                      <td>{{ index + 1 }}</td>
                      <td>
                        <div class="item-info">
                          <div class="item-header">
                            <span class="item-code">{{ line.item ? line.item.item_code : 'N/A' }}</span>
                            <span class="item-status" :class="getItemStatusClass(line)">
                              {{ getItemStatus(line) }}
                            </span>
                          </div>
                          <span class="item-name">{{ line.item ? line.item.name : 'Unknown Item' }}</span>
                        </div>
                      </td>
                      <td>
                        <div class="quantity-info">
                          <span class="quantity-value">{{ formatNumber(line.quantity) }}</span>
                          <span class="quantity-progress">
                            <div class="progress-bar" :style="{ width: getQuantityProgress(line) + '%' }"></div>
                          </span>
                        </div>
                      </td>
                      <td>
                        <span class="unit-badge">{{ line.unitOfMeasure ? line.unitOfMeasure.name : 'N/A' }}</span>
                      </td>
                      <td>
                        <div class="date-info">
                          <span class="date-value">{{ formatDate(line.required_date) }}</span>
                          <span class="date-indicator" :class="getDateIndicatorClass(line.required_date)">
                            <i class="fas" :class="getDateIcon(line.required_date)"></i>
                          </span>
                        </div>
                      </td>
                      <td>
                        <div class="notes-cell">
                          <span v-if="line.notes" class="notes-text">{{ line.notes }}</span>
                          <span v-else class="no-notes">-</span>
                        </div>
                      </td>
                      <td>
                        <div class="action-buttons-inline">
                          <button class="btn-icon" @click.stop="viewLineDetail(line)" v-tooltip="'View details'">
                            <i class="fas fa-eye"></i>
                          </button>
                          <button
                            v-if="pr.status === 'draft'"
                            class="btn-icon"
                            @click.stop="editLine(line)"
                            v-tooltip="'Edit line'"
                          >
                            <i class="fas fa-edit"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column -->
        <div class="right-column">
          <!-- Status Timeline Card -->
          <div class="timeline-card card-animate" @mouseenter="cardHover = 'timeline'" @mouseleave="cardHover = null">
            <div class="card-header">
              <h5 class="card-title">
                <i class="fas fa-clock"></i>
                Status Timeline
              </h5>
            </div>
            <div class="card-body">
              <ul class="enhanced-timeline">
                <li class="timeline-item" v-for="(step, index) in timelineSteps" :key="index" :class="step.status">
                  <div class="timeline-marker" :class="step.status">
                    <i :class="step.icon"></i>
                  </div>
                  <div class="timeline-content">
                    <h3 class="timeline-title">{{ step.title }}</h3>
                    <p class="timeline-date" v-if="step.date">{{ formatDateTime(step.date) }}</p>
                    <p v-if="step.info" class="timeline-info">{{ step.info }}</p>
                    <div v-if="step.rfq_link" class="timeline-link">
                      <router-link :to="step.rfq_link" class="rfq-link">
                        {{ step.rfq_number }}
                      </router-link>
                    </div>
                  </div>
                </li>
              </ul>
            </div>
          </div>

          <!-- Statistics Card -->
          <div class="stats-card card-animate">
            <div class="card-header">
              <h5 class="card-title">
                <i class="fas fa-chart-bar"></i>
                Statistics
              </h5>
            </div>
            <div class="card-body">
              <div class="stats-grid">
                <div class="stat-item" v-for="(stat, index) in statistics" :key="index">
                  <div class="stat-icon" :class="stat.color">
                    <i :class="stat.icon"></i>
                  </div>
                  <div class="stat-content">
                    <div class="stat-value">{{ stat.value }}</div>
                    <div class="stat-label">{{ stat.label }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Related Documents Card -->
          <div class="documents-card card-animate" @mouseenter="cardHover = 'documents'" @mouseleave="cardHover = null">
            <div class="card-header">
              <h5 class="card-title">
                <i class="fas fa-folder"></i>
                Related Documents
                <span class="doc-count">{{ relatedDocuments.length }}</span>
              </h5>
            </div>
            <div class="card-body">
              <div v-if="relatedDocuments.length === 0" class="empty-state">
                <i class="fas fa-folder-open"></i>
                <p>No related documents found.</p>
              </div>
              <div v-else class="documents-list">
                <div
                  v-for="(doc, index) in relatedDocuments"
                  :key="index"
                  class="document-item"
                  @click="openDocument(doc)"
                >
                  <div class="doc-icon" :class="getDocumentTypeClass(doc.type)">
                    <i :class="getDocumentIcon(doc.type)"></i>
                  </div>
                  <div class="doc-info">
                    <div class="doc-number">{{ doc.number }}</div>
                    <div class="doc-date">{{ formatDate(doc.date) }}</div>
                  </div>
                  <div class="doc-actions">
                    <button class="btn-icon" v-tooltip="'View document'">
                      <i class="fas fa-external-link-alt"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Enhanced Cancel Confirmation Modal -->
    <transition name="modal">
      <div v-if="showCancelConfirmation" class="modal-overlay" @click="showCancelConfirmation = false">
        <div class="modal-container" @click.stop>
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-exclamation-triangle text-warning"></i>
              Cancel Purchase Requisition
            </h5>
            <button type="button" class="close-btn" @click="showCancelConfirmation = false">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <div class="warning-content">
              <p>Are you sure you want to cancel this purchase requisition?</p>
              <div class="warning-details">
                <ul>
                  <li>This action cannot be undone</li>
                  <li>All related workflows will be stopped</li>
                  <li>You will need to create a new requisition</li>
                </ul>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showCancelConfirmation = false">
              <i class="fas fa-arrow-left"></i>
              Keep Requisition
            </button>
            <button type="button" class="btn btn-danger" @click="cancelPR" :disabled="processing">
              <span v-if="processing" class="spinner-sm"></span>
              <i v-else class="fas fa-trash"></i>
              Confirm Cancel
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Enhanced Alert System -->
    <transition name="alert">
      <div v-if="alert.show" class="alert-container">
        <div class="alert-content" :class="`alert-${alert.type}`">
          <div class="alert-icon">
            <i :class="getAlertIcon(alert.type)"></i>
          </div>
          <div class="alert-message">{{ alert.message }}</div>
          <button type="button" class="alert-close" @click="alert.show = false">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>
    </transition>

    <!-- Line Detail Modal -->
    <transition name="modal">
      <div v-if="showLineDetail" class="modal-overlay" @click="showLineDetail = false">
        <div class="modal-container large" @click.stop>
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-info-circle"></i>
              Line Item Details
            </h5>
            <button type="button" class="close-btn" @click="showLineDetail = false">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <div v-if="selectedLineData" class="line-detail-content">
              <div class="detail-grid">
                <div class="detail-item">
                  <label>Item Code</label>
                  <span>{{ selectedLineData.item?.item_code || 'N/A' }}</span>
                </div>
                <div class="detail-item">
                  <label>Item Name</label>
                  <span>{{ selectedLineData.item?.name || 'Unknown Item' }}</span>
                </div>
                <div class="detail-item">
                  <label>Quantity</label>
                  <span>{{ formatNumber(selectedLineData.quantity) }}</span>
                </div>
                <div class="detail-item">
                  <label>Unit of Measure</label>
                  <span>{{ selectedLineData.unitOfMeasure?.name || 'N/A' }}</span>
                </div>
                <div class="detail-item">
                  <label>Required Date</label>
                  <span>{{ formatDate(selectedLineData.required_date) }}</span>
                </div>
                <div class="detail-item full-width">
                  <label>Notes</label>
                  <span>{{ selectedLineData.notes || 'No notes provided' }}</span>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showLineDetail = false">
              Close
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'PurchaseRequisitionDetail',
  props: {
    id: {
      type: [String, Number],
      required: true
    }
  },
  data() {
    return {
      pr: {
        pr_id: null,
        pr_number: '',
        pr_date: '',
        requester_id: null,
        requester: null,
        status: '',
        notes: '',
        lines: [],
        created_at: null,
        submitted_at: null,
        approved_at: null,
        converted_at: null,
        converted_to_rfq: false,
        rfq_id: null,
        rfq_number: null
      },
      relatedDocuments: [],
      loading: true,
      processing: false,
      showCancelConfirmation: false,
      showDropdown: false,
      showFilters: false,
      showLineDetail: false,
      searchQuery: '',
      selectedLine: null,
      selectedLineData: null,
      cardHover: null,
      expandedCards: {
        info: false,
        lines: false
      },
      alert: {
        show: false,
        type: 'success',
        message: ''
      }
    };
  },
  computed: {
    infoItems() {
      return [
        {
          label: 'PR Number',
          value: this.pr.pr_number,
          class: 'pr-number'
        },
        {
          label: 'Status',
          value: this.capitalizeStatus(this.pr.status),
          component: 'span',
          props: { class: this.getStatusBadgeClass(this.pr.status) }
        },
        {
          label: 'PR Date',
          value: this.formatDate(this.pr.pr_date)
        },
        {
          label: 'Requester',
          value: this.pr.requester ? this.pr.requester.name : 'N/A'
        },
        {
          label: 'Notes',
          value: this.pr.notes || 'No notes provided.',
          class: 'notes-value'
        }
      ];
    },
    filteredLines() {
      if (!this.searchQuery) return this.pr.lines;

      return this.pr.lines.filter(line => {
        const itemCode = line.item?.item_code?.toLowerCase() || '';
        const itemName = line.item?.name?.toLowerCase() || '';
        const query = this.searchQuery.toLowerCase();

        return itemCode.includes(query) || itemName.includes(query);
      });
    },
    timelineSteps() {
      return [
        {
          title: 'Created',
          date: this.pr.created_at,
          icon: 'fas fa-file-alt',
          status: 'done'
        },
        {
          title: 'Submitted',
          date: this.pr.submitted_at,
          icon: 'fas fa-paper-plane',
          status: this.pr.status !== 'draft' ? 'done' : 'pending'
        },
        {
          title: this.pr.status === 'approved' ? 'Approved' : this.pr.status === 'rejected' ? 'Rejected' : 'Approval',
          date: this.pr.approved_at,
          icon: 'fas fa-check-circle',
          status: ['approved', 'rejected'].includes(this.pr.status) ? 'done' : 'pending'
        },
        {
          title: 'Converted to RFQ',
          date: this.pr.converted_at,
          icon: 'fas fa-exchange-alt',
          status: this.pr.converted_to_rfq ? 'done' : 'pending',
          rfq_number: this.pr.rfq_number,
          rfq_link: this.pr.rfq_id ? `/purchasing/rfqs/${this.pr.rfq_id}` : null
        }
      ];
    },
    statistics() {
      return [
        {
          label: 'Total Lines',
          value: this.pr.lines.length,
          icon: 'fas fa-list',
          color: 'blue'
        },
        {
          label: 'Days Active',
          value: this.getDaysActive(),
          icon: 'fas fa-calendar',
          color: 'green'
        },
        {
          label: 'Priority',
          value: this.getPriorityLevel(),
          icon: 'fas fa-flag',
          color: 'orange'
        },
        {
          label: 'Progress',
          value: this.getProgress() + '%',
          icon: 'fas fa-chart-line',
          color: 'purple'
        }
      ];
    }
  },
  async created() {
    await this.fetchPRData();
  },
  mounted() {
    // Add click outside listener for dropdown
    document.addEventListener('click', this.handleClickOutside);
  },
  beforeUnmount() {
    document.removeEventListener('click', this.handleClickOutside);
  },
  methods: {
    async fetchPRData() {
      try {
        this.loading = true;
        const response = await axios.get(`/purchase-requisitions/${this.id}`);

        this.pr = response.data.data;

        // Demo data enhancement
        this.pr.submitted_at = this.pr.status !== 'draft' ? new Date(new Date(this.pr.created_at).getTime() + 3600000) : null;
        this.pr.approved_at = ['approved', 'rejected'].includes(this.pr.status) ? new Date(new Date(this.pr.created_at).getTime() + 7200000) : null;

        this.checkRFQConversion();
        this.fetchRelatedDocuments();
      } catch (error) {
        console.error('Error fetching PR data:', error);
        this.showAlert('danger', 'Failed to load purchase requisition data. Please try again.');
      } finally {
        this.loading = false;
      }
    },

    async checkRFQConversion() {
      if (this.pr.status === 'approved') {
        const hasRFQ = Math.random() > 0.5;

        if (hasRFQ) {
          this.pr.converted_to_rfq = true;
          this.pr.converted_at = new Date(new Date(this.pr.approved_at).getTime() + 3600000);
          this.pr.rfq_id = 123;
          this.pr.rfq_number = 'RFQ20250101001';
        }
      }
    },

    async fetchRelatedDocuments() {
      this.relatedDocuments = [];

      if (this.pr.converted_to_rfq) {
        this.relatedDocuments.push({
          type: 'rfq',
          number: this.pr.rfq_number,
          date: this.pr.converted_at,
          link: `/purchasing/rfqs/${this.pr.rfq_id}`
        });

        this.relatedDocuments.push({
          type: 'po',
          number: 'PO20250115001',
          date: new Date(new Date(this.pr.converted_at).getTime() + 86400000 * 2),
          link: `/purchasing/orders/456`
        });
      }
    },

    async submitPR() {
      if (this.processing) return;

      this.processing = true;
      try {
        const response = await axios.patch(`/purchase-requisitions/${this.id}/status`, {
          status: 'pending'
        });

        this.pr.status = response.data.data.status;
        this.pr.submitted_at = new Date();

        this.showAlert('success', 'Purchase Requisition submitted for approval successfully.');
      } catch (error) {
        console.error('Error submitting PR:', error);
        this.showAlert('danger', 'Failed to submit purchase requisition. Please try again.');
      } finally {
        this.processing = false;
      }
    },

    async cancelPR() {
      if (this.processing) return;

      this.processing = true;
      try {
        const response = await axios.patch(`/purchase-requisitions/${this.id}/status`, {
          status: 'canceled'
        });

        this.pr.status = response.data.data.status;
        this.showCancelConfirmation = false;

        this.showAlert('success', 'Purchase Requisition canceled successfully.');
      } catch (error) {
        console.error('Error canceling PR:', error);
        this.showAlert('danger', 'Failed to cancel purchase requisition. Please try again.');
      } finally {
        this.processing = false;
      }
    },

    selectLine(index) {
      this.selectedLine = index;
    },

    viewLineDetail(line) {
      this.selectedLineData = line;
      this.showLineDetail = true;
    },

    editLine(line) {
      // Implement edit functionality
      console.log('Edit line:', line);
    },

    sortBy(field) {
      // Implement sorting functionality
      console.log('Sort by:', field);
    },

    printPR() {
      window.print();
    },

    exportPDF() {
      // Implement PDF export
      console.log('Export PDF');
    },

    shareDocument() {
      // Implement share functionality
      console.log('Share document');
    },

    openDocument(doc) {
      this.$router.push(doc.link);
    },

    handleClickOutside(event) {
      if (!event.target.closest('.dropdown')) {
        this.showDropdown = false;
      }
    },

    // Utility Methods
    formatDate(dateString) {
      if (!dateString) return 'N/A';

      const date = new Date(dateString);
      return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    },

    formatDateTime(dateString) {
      if (!dateString) return 'N/A';

      const date = new Date(dateString);
      return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    },

    formatNumber(value) {
      if (value === null || value === undefined) return 'N/A';

      return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
      }).format(value);
    },

    getStatusBadgeClass(status) {
      const statusClasses = {
        draft: 'badge badge-secondary',
        pending: 'badge badge-warning',
        approved: 'badge badge-success',
        rejected: 'badge badge-danger',
        canceled: 'badge badge-dark'
      };

      return statusClasses[status] || 'badge badge-secondary';
    },

    capitalizeStatus(status) {
      if (!status) return '';
      return status.charAt(0).toUpperCase() + status.slice(1);
    },

    getDocumentIcon(docType) {
      const iconClasses = {
        rfq: 'fas fa-file-invoice-dollar',
        po: 'fas fa-clipboard-list',
        gr: 'fas fa-truck-loading',
        invoice: 'fas fa-file-invoice'
      };

      return iconClasses[docType] || 'fas fa-file';
    },

    getDocumentTypeClass(docType) {
      const typeClasses = {
        rfq: 'doc-rfq',
        po: 'doc-po',
        gr: 'doc-gr',
        invoice: 'doc-invoice'
      };

      return typeClasses[docType] || 'doc-default';
    },

    getAlertIcon(type) {
      const icons = {
        success: 'fas fa-check-circle',
        danger: 'fas fa-exclamation-circle',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-info-circle'
      };

      return icons[type] || 'fas fa-info-circle';
    },

    getItemStatusClass(line) {
      // Mock status based on quantity or other criteria
      if (line.quantity > 100) return 'status-high';
      if (line.quantity > 50) return 'status-medium';
      return 'status-normal';
    },

    getItemStatus(line) {
      // Mock status logic
      if (line.quantity > 100) return 'High Volume';
      if (line.quantity > 50) return 'Medium Volume';
      return 'Normal';
    },

    getQuantityProgress(line) {
      // Mock progress calculation
      return Math.min(100, (line.quantity / 100) * 100);
    },

    getDateIndicatorClass(date) {
      if (!date) return 'date-normal';

      const requiredDate = new Date(date);
      const today = new Date();
      const diffDays = Math.ceil((requiredDate - today) / (1000 * 60 * 60 * 24));

      if (diffDays < 0) return 'date-overdue';
      if (diffDays <= 7) return 'date-urgent';
      if (diffDays <= 30) return 'date-normal';
      return 'date-future';
    },

    getDateIcon(date) {
      const indicatorClass = this.getDateIndicatorClass(date);

      switch (indicatorClass) {
        case 'date-overdue': return 'fa-exclamation-triangle';
        case 'date-urgent': return 'fa-clock';
        case 'date-normal': return 'fa-calendar';
        default: return 'fa-calendar-alt';
      }
    },

    getDaysActive() {
      if (!this.pr.created_at) return 0;

      const created = new Date(this.pr.created_at);
      const today = new Date();
      return Math.ceil((today - created) / (1000 * 60 * 60 * 24));
    },

    getPriorityLevel() {
      // Mock priority calculation
      const urgentLines = this.pr.lines.filter(line => {
        const date = new Date(line.required_date);
        const today = new Date();
        const diffDays = Math.ceil((date - today) / (1000 * 60 * 60 * 24));
        return diffDays <= 7;
      });

      if (urgentLines.length > this.pr.lines.length / 2) return 'High';
      if (urgentLines.length > 0) return 'Medium';
      return 'Low';
    },

    getProgress() {
      const statusWeight = {
        draft: 25,
        pending: 50,
        approved: 75,
        rejected: 100,
        canceled: 100
      };

      return statusWeight[this.pr.status] || 0;
    },

    showAlert(type, message) {
      this.alert = {
        show: true,
        type,
        message
      };

      setTimeout(() => {
        this.alert.show = false;
      }, 5000);
    }
  }
};
</script>

<style scoped>
/* Base Styles */
.purchase-requisition-detail {
  min-height: 100vh;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  padding: 2rem;
}

/* Enhanced Page Header */
.page-header {
  background: white;
  border-radius: 16px;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  backdrop-filter: blur(10px);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
}

.title-section {
  flex: 1;
}

.page-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 0 0 0.5rem 0;
  font-size: 2rem;
  font-weight: 700;
  color: #2d3748;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.page-subtitle {
  color: #718096;
  margin: 0;
  font-size: 1.1rem;
}

.action-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  align-items: center;
}

/* Enhanced Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
  position: relative;
  overflow: hidden;
  font-size: 0.875rem;
}

.btn::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  transition: left 0.5s;
}

.btn:hover::before {
  left: 100%;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
}

.btn-success {
  background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
  color: white;
  box-shadow: 0 4px 15px rgba(72, 187, 120, 0.4);
}

.btn-success:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(72, 187, 120, 0.6);
}

.btn-warning {
  background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
  color: white;
  box-shadow: 0 4px 15px rgba(237, 137, 54, 0.4);
}

.btn-info {
  background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
  color: white;
  box-shadow: 0 4px 15px rgba(66, 153, 225, 0.4);
}

.btn-danger {
  background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
  color: white;
  box-shadow: 0 4px 15px rgba(245, 101, 101, 0.4);
}

.btn-secondary {
  background: linear-gradient(135deg, #a0aec0 0%, #718096 100%);
  color: white;
  box-shadow: 0 4px 15px rgba(160, 174, 192, 0.4);
}

/* Dropdown */
.dropdown {
  position: relative;
}

.dropdown-toggle {
  background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e0 100%);
  color: #4a5568;
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  background: white;
  border-radius: 12px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  padding: 0.5rem;
  min-width: 200px;
  opacity: 0;
  visibility: hidden;
  transform: translateY(-10px);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  z-index: 1000;
}

.dropdown-menu.show {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  color: #4a5568;
  text-decoration: none;
  transition: all 0.2s;
  font-weight: 500;
}

.dropdown-item:hover {
  background: #f7fafc;
  color: #2d3748;
  transform: translateX(4px);
}

/* Loading Animation */
.loading-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 400px;
}

.loading-spinner {
  text-align: center;
}

.spinner {
  width: 60px;
  height: 60px;
  border: 4px solid #e2e8f0;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Content Grid */
.main-content {
  animation: slideInUp 0.6s ease-out;
}

.content-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 2rem;
}

/* Cards */
.card-animate {
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  margin-bottom: 2rem;
  overflow: hidden;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.card-animate:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
}

.card-header {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  padding: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
  color: #2d3748;
}

.item-count, .doc-count {
  background: #667eea;
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  margin-left: 0.5rem;
}

.expand-btn, .filter-btn {
  background: none;
  border: none;
  color: #718096;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 6px;
  transition: all 0.2s;
}

.expand-btn:hover, .filter-btn:hover {
  background: #edf2f7;
  color: #4a5568;
}

.card-body {
  padding: 1.5rem;
}

.card-body.expanded {
  max-height: none !important;
}

/* Info Grid */
.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.info-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #718096;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.info-value {
  font-size: 1.125rem;
  color: #2d3748;
  font-weight: 500;
}

.notes-value {
  white-space: pre-line;
  line-height: 1.6;
}

/* Badge Styles */
.badge {
  display: inline-flex;
  align-items: center;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 600;
  text-transform: capitalize;
}

.badge-secondary {
  background: linear-gradient(135deg, #a0aec0 0%, #718096 100%);
  color: white;
}

.badge-warning {
  background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
  color: white;
}

.badge-success {
  background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
  color: white;
}

.badge-danger {
  background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
  color: white;
}

.badge-dark {
  background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);
  color: white;
}

/* Filters */
.filters-section {
  padding: 1rem 1.5rem;
  background: #f8f9fa;
  border-bottom: 1px solid #e2e8f0;
}

.filter-input {
  position: relative;
  max-width: 300px;
}

.filter-input input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 2.5rem;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.875rem;
  transition: all 0.3s;
}

.filter-input input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.filter-input i {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #a0aec0;
}

/* Enhanced Table */
.table-container {
  overflow-x: auto;
}

.enhanced-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
}

.enhanced-table th {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  color: #4a5568;
  font-weight: 700;
  padding: 1rem;
  text-align: left;
  border: none;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.enhanced-table th.sortable {
  cursor: pointer;
  user-select: none;
  transition: all 0.2s;
}

.enhanced-table th.sortable:hover {
  background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e0 100%);
}

.enhanced-table td {
  padding: 1rem;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}

.table-row-animate {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
}

.table-row-animate:hover {
  background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%);
  transform: scale(1.01);
}

.table-row-animate.selected {
  background: linear-gradient(135deg, #e6f3ff 0%, #cce7ff 100%);
  border-left: 4px solid #667eea;
}

/* Item Info in Table */
.item-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.item-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.item-code {
  font-size: 0.875rem;
  font-weight: 700;
  color: #4a5568;
}

.item-name {
  font-size: 0.8rem;
  color: #718096;
}

.item-status {
  padding: 0.2rem 0.5rem;
  border-radius: 12px;
  font-size: 0.7rem;
  font-weight: 600;
  background: #e6fffa;
  color: #065f46;
}

/* Quantity Info */
.quantity-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.quantity-value {
  font-weight: 700;
  color: #2d3748;
}

.quantity-progress {
  width: 60px;
  height: 4px;
  background: #e2e8f0;
  border-radius: 2px;
  overflow: hidden;
}

.progress-bar {
  height: 100%;
  background: linear-gradient(90deg, #48bb78, #68d391);
  transition: width 0.3s;
}

/* Unit Badge */
.unit-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.4rem 0.8rem;
  background: #edf2f7;
  color: #4a5568;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 600;
}

/* Date Info */
.date-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.date-value {
  font-weight: 500;
}

.date-indicator {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 1.5rem;
  height: 1.5rem;
  border-radius: 50%;
  font-size: 0.7rem;
}

.date-normal {
  background: #e6fffa;
  color: #065f46;
}

.date-urgent {
  background: #fed7d7;
  color: #742a2a;
}

.date-overdue {
  background: #fed7d7;
  color: #742a2a;
  animation: pulse 2s infinite;
}

.date-future {
  background: #e6f3ff;
  color: #1a365d;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

/* Notes Cell */
.notes-cell {
  max-width: 200px;
}

.notes-text {
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.no-notes {
  color: #a0aec0;
  font-style: italic;
}

/* Action Buttons Inline */
.action-buttons-inline {
  display: flex;
  gap: 0.5rem;
}

.btn-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  border: none;
  border-radius: 6px;
  background: #f7fafc;
  color: #718096;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-icon:hover {
  background: #edf2f7;
  color: #4a5568;
  transform: scale(1.1);
}

/* Enhanced Timeline */
.enhanced-timeline {
  position: relative;
  padding: 1rem 0;
  margin: 0;
  list-style: none;
}

.enhanced-timeline::before {
  content: '';
  position: absolute;
  top: 0;
  left: 1.5rem;
  height: 100%;
  width: 3px;
  background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
  border-radius: 2px;
}

.timeline-item {
  position: relative;
  padding: 0 0 2rem 4rem;
  transition: all 0.3s;
}

.timeline-item:last-child {
  padding-bottom: 0;
}

.timeline-item:hover {
  transform: translateX(4px);
}

.timeline-marker {
  position: absolute;
  left: 0;
  top: 0;
  width: 3rem;
  height: 3rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  transition: all 0.3s;
}

.timeline-marker.done {
  background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
  color: white;
}

.timeline-marker.pending {
  background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
  color: white;
}

.timeline-content {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  border-left: 4px solid #e2e8f0;
  transition: all 0.3s;
}

.timeline-item.done .timeline-content {
  border-left-color: #48bb78;
}

.timeline-item.pending .timeline-content {
  border-left-color: #ed8936;
}

.timeline-title {
  margin: 0 0 0.5rem 0;
  font-size: 1.125rem;
  font-weight: 700;
  color: #2d3748;
}

.timeline-date {
  margin: 0 0 0.5rem 0;
  font-size: 0.875rem;
  color: #718096;
}

.timeline-info {
  margin: 0;
  font-size: 0.875rem;
  color: #4a5568;
}

.rfq-link {
  color: #667eea;
  text-decoration: none;
  font-weight: 600;
  transition: all 0.2s;
}

.rfq-link:hover {
  color: #5a67d8;
  text-decoration: underline;
}

/* Statistics Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 12px;
  transition: all 0.3s;
}

.stat-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.stat-icon {
  width: 3rem;
  height: 3rem;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  color: white;
}

.stat-icon.blue {
  background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
}

.stat-icon.green {
  background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
}

.stat-icon.orange {
  background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
}

.stat-icon.purple {
  background: linear-gradient(135deg, #9f7aea 0%, #805ad5 100%);
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #2d3748;
  line-height: 1;
}

.stat-label {
  font-size: 0.875rem;
  color: #718096;
  margin-top: 0.25rem;
}

/* Documents List */
.empty-state {
  text-align: center;
  padding: 2rem;
  color: #a0aec0;
}

.empty-state i {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.documents-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.document-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.document-item:hover {
  background: #e2e8f0;
  transform: translateX(4px);
}

.doc-icon {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1rem;
}

.doc-rfq {
  background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
}

.doc-po {
  background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
}

.doc-gr {
  background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
}

.doc-invoice {
  background: linear-gradient(135deg, #9f7aea 0%, #805ad5 100%);
}

.doc-default {
  background: linear-gradient(135deg, #a0aec0 0%, #718096 100%);
}

.doc-info {
  flex: 1;
}

.doc-number {
  font-size: 1rem;
  font-weight: 600;
  color: #2d3748;
  text-decoration: none;
  transition: color 0.2s;
}

.doc-number:hover {
  color: #667eea;
}

.doc-date {
  font-size: 0.875rem;
  color: #718096;
  margin-top: 0.25rem;
}

/* Enhanced Modals */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.modal-container {
  background: white;
  border-radius: 20px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  max-width: 500px;
  width: 100%;
  max-height: 90vh;
  overflow: hidden;
  animation: modalSlideIn 0.3s ease-out;
}

.modal-container.large {
  max-width: 800px;
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: scale(0.9) translateY(-20px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 2rem;
  border-bottom: 1px solid #e2e8f0;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.modal-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
  color: #2d3748;
}

.close-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2.5rem;
  height: 2.5rem;
  border: none;
  border-radius: 50%;
  background: #f7fafc;
  color: #718096;
  cursor: pointer;
  transition: all 0.2s;
}

.close-btn:hover {
  background: #edf2f7;
  color: #4a5568;
  transform: scale(1.1);
}

.modal-body {
  padding: 2rem;
  max-height: 60vh;
  overflow-y: auto;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 2rem;
  border-top: 1px solid #e2e8f0;
  background: #f8f9fa;
}

/* Warning Content */
.warning-content {
  text-align: center;
}

.warning-details {
  background: #fef5e7;
  border: 1px solid #f6e05e;
  border-radius: 12px;
  padding: 1rem;
  margin-top: 1rem;
}

.warning-details ul {
  margin: 0;
  padding-left: 1.5rem;
  text-align: left;
}

.warning-details li {
  margin-bottom: 0.5rem;
  color: #744210;
}

/* Line Detail Content */
.line-detail-content {
  max-height: 400px;
  overflow-y: auto;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.detail-item.full-width {
  grid-column: span 2;
}

.detail-item label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #718096;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.detail-item span {
  font-size: 1rem;
  color: #2d3748;
  font-weight: 500;
}

/* Enhanced Alert System */
.alert-container {
  position: fixed;
  top: 2rem;
  right: 2rem;
  z-index: 1050;
  max-width: 400px;
}

.alert-content {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.5rem;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  backdrop-filter: blur(10px);
  animation: alertSlideIn 0.3s ease-out;
}

@keyframes alertSlideIn {
  from {
    opacity: 0;
    transform: translateX(100%);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.alert-success {
  background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
  color: white;
}

.alert-danger {
  background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
  color: white;
}

.alert-warning {
  background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
  color: white;
}

.alert-info {
  background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
  color: white;
}

.alert-icon {
  font-size: 1.25rem;
}

.alert-message {
  flex: 1;
  font-weight: 500;
}

.alert-close {
  background: none;
  border: none;
  color: white;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 4px;
  transition: all 0.2s;
}

.alert-close:hover {
  background: rgba(255, 255, 255, 0.1);
}

/* Transitions */
.modal-enter-active, .modal-leave-active {
  transition: all 0.3s ease;
}

.modal-enter-from, .modal-leave-to {
  opacity: 0;
  transform: scale(0.9);
}

.alert-enter-active, .alert-leave-active {
  transition: all 0.3s ease;
}

.alert-enter-from, .alert-leave-to {
  opacity: 0;
  transform: translateX(100%);
}

/* Animations */
@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Utility Classes */
.spinner-sm {
  width: 1rem;
  height: 1rem;
  border: 2px solid transparent;
  border-top: 2px solid currentColor;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  display: inline-block;
  margin-right: 0.5rem;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .content-grid {
    grid-template-columns: 1fr;
  }

  .right-column {
    order: -1;
  }
}

@media (max-width: 768px) {
  .purchase-requisition-detail {
    padding: 1rem;
  }

  .header-content {
    flex-direction: column;
    align-items: stretch;
    gap: 1.5rem;
  }

  .action-buttons {
    justify-content: center;
  }

  .btn span {
    display: none;
  }

  .info-grid {
    grid-template-columns: 1fr;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .detail-grid {
    grid-template-columns: 1fr;
  }

  .detail-item.full-width {
    grid-column: span 1;
  }

  .modal-container {
    margin: 1rem;
    max-width: calc(100% - 2rem);
  }

  .alert-container {
    top: 1rem;
    right: 1rem;
    left: 1rem;
    max-width: none;
  }
}

@media print {
  .action-buttons,
  .alert-container,
  .modal-overlay {
    display: none !important;
  }

  .card-animate {
    break-inside: avoid;
    box-shadow: none;
    border: 1px solid #dee2e6;
  }

  .purchase-requisition-detail {
    background: white;
  }
}

/* Tooltip Styles (if using a tooltip library) */
[v-tooltip] {
  position: relative;
}

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>
