<template>
  <div class="job-ticket-detail">
    <!-- Header Section -->
    <header class="page-header">
      <div class="header-content">
        <div class="title-section">
          <h2 class="page-title">Job Ticket Details</h2>
          <nav class="breadcrumb-nav" aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item">
                <router-link to="/manufacturing/job-tickets" class="breadcrumb-link">
                  Job Tickets
                </router-link>
              </li>
              <li class="breadcrumb-item active">
                {{ jobTicket?.ticket_id ? `#${jobTicket.ticket_id}` : 'Loading...' }}
              </li>
            </ol>
          </nav>
        </div>

        <div class="header-actions">
          <router-link
            to="/manufacturing/job-tickets"
            class="btn btn-outline btn-back"
          >
            <i class="fas fa-arrow-left"></i>
            <span>Back to List</span>
          </router-link>

          <router-link
            v-if="jobTicket"
            :to="`/manufacturing/job-tickets/${jobTicket.ticket_id}/print`"
            class="btn btn-outline btn-print"
          >
            <i class="fas fa-print"></i>
            <span>Print</span>
          </router-link>

          <button
            v-if="jobTicket"
            class="btn btn-primary btn-edit"
            @click="editMode = !editMode"
          >
            <i class="fas fa-edit"></i>
            <span>{{ editMode ? 'Cancel Edit' : 'Edit' }}</span>
          </button>
        </div>
      </div>
    </header>

    <!-- Content Section -->
    <main class="content-section">
      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="loading-spinner">
          <i class="fas fa-spinner fa-spin"></i>
        </div>
        <p class="loading-text">Loading job ticket details...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="error-state">
        <div class="error-icon">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <p class="error-message">{{ error }}</p>
      </div>

      <!-- Job Ticket Content -->
      <div v-else-if="jobTicket" class="ticket-content">

        <!-- Basic Information Card -->
        <section class="info-card">
          <div class="card-header">
            <div class="header-icon">
              <i class="fas fa-clipboard-list"></i>
            </div>
            <h3 class="card-title">Basic Information</h3>
          </div>

          <div class="card-body">
            <!-- Edit Form -->
            <form v-if="editMode" @submit.prevent="updateJobTicket" class="edit-form">
              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label required">Item</label>
                  <input
                    type="text"
                    class="form-control"
                    v-model="editForm.item"
                    required
                    placeholder="Enter item name"
                  />
                </div>

                <div class="form-group">
                  <label class="form-label required">UOM</label>
                  <input
                    type="text"
                    class="form-control"
                    v-model="editForm.uom"
                    required
                    placeholder="Unit of measure"
                  />
                </div>

                <div class="form-group">
                  <label class="form-label required">Qty Completed</label>
                  <input
                    type="number"
                    step="0.01"
                    class="form-control"
                    v-model="editForm.qty_completed"
                    required
                    placeholder="0.00"
                  />
                </div>

                <div class="form-group">
                  <label class="form-label">Customer</label>
                  <input
                    type="text"
                    class="form-control"
                    v-model="editForm.customer"
                    placeholder="Customer name"
                  />
                </div>

                <div class="form-group">
                  <label class="form-label">FGRN No</label>
                  <input
                    type="text"
                    class="form-control"
                    v-model="editForm.fgrn_no"
                    placeholder="FGRN number"
                  />
                </div>

                <div class="form-group">
                  <label class="form-label">Date</label>
                  <input
                    type="date"
                    class="form-control"
                    v-model="editForm.date"
                  />
                </div>

                <div class="form-group">
                  <label class="form-label">Reference JO No</label>
                  <input
                    type="text"
                    class="form-control"
                    v-model="editForm.ref_jo_no"
                    placeholder="Job order reference"
                  />
                </div>

                <div class="form-group">
                  <label class="form-label">JO Issue Date</label>
                  <input
                    type="date"
                    class="form-control"
                    v-model="editForm.issue_date_jo"
                  />
                </div>

                <div class="form-group">
                  <label class="form-label">JO Quantity</label>
                  <input
                    type="number"
                    step="0.01"
                    class="form-control"
                    v-model="editForm.qty_jo"
                    placeholder="0.00"
                  />
                </div>
              </div>

              <div class="form-actions">
                <button type="submit" class="btn btn-success" :disabled="updating">
                  <i v-if="updating" class="fas fa-spinner fa-spin"></i>
                  <span>{{ updating ? 'Updating...' : 'Update Job Ticket' }}</span>
                </button>
                <button type="button" class="btn btn-outline" @click="editMode = false">
                  Cancel
                </button>
              </div>
            </form>

            <!-- Read-only View -->
            <div v-else class="info-grid">
              <div class="info-item">
                <label class="info-label">Job Ticket ID</label>
                <div class="info-value">
                  <span class="ticket-badge">{{ jobTicket.ticket_id }}</span>
                </div>
              </div>

              <div class="info-item">
                <label class="info-label">FGRN No</label>
                <div class="info-value">
                  {{ jobTicket.fgrn_no || 'Not assigned' }}
                </div>
              </div>

              <div class="info-item info-item-wide">
                <label class="info-label">Item</label>
                <div class="info-value item-name">{{ jobTicket.item }}</div>
              </div>

              <div class="info-item">
                <label class="info-label">UOM</label>
                <div class="info-value">{{ jobTicket.uom }}</div>
              </div>

              <div class="info-item">
                <label class="info-label">Qty Completed</label>
                <div class="info-value quantity-value">
                  {{ formatNumber(jobTicket.qty_completed) }}
                </div>
              </div>

              <div class="info-item">
                <label class="info-label">Customer</label>
                <div class="info-value">{{ jobTicket.customer || 'Not specified' }}</div>
              </div>

              <div class="info-item">
                <label class="info-label">Date</label>
                <div class="info-value">
                  {{ jobTicket.date ? formatDate(jobTicket.date) : 'Not set' }}
                </div>
              </div>

              <div class="info-item">
                <label class="info-label">Created</label>
                <div class="info-value">
                  {{ formatDateTime(jobTicket.created_at) }}
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Job Order Information -->
        <section class="info-card">
          <div class="card-header">
            <div class="header-icon icon-info">
              <i class="fas fa-clipboard"></i>
            </div>
            <h3 class="card-title">Job Order Information</h3>
          </div>

          <div class="card-body">
            <div class="info-grid">
              <div class="info-item">
                <label class="info-label">Reference JO No</label>
                <div class="info-value">
                  {{ jobTicket.ref_jo_no || 'Not assigned' }}
                </div>
              </div>

              <div class="info-item">
                <label class="info-label">JO Issue Date</label>
                <div class="info-value">
                  {{ jobTicket.issue_date_jo ? formatDate(jobTicket.issue_date_jo) : 'Not set' }}
                </div>
              </div>

              <div class="info-item">
                <label class="info-label">JO Quantity</label>
                <div class="info-value">
                  {{ jobTicket.qty_jo ? formatNumber(jobTicket.qty_jo) : 'Not specified' }}
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Production Order Information -->
        <section v-if="jobTicket.production_order" class="info-card">
          <div class="card-header">
            <div class="header-icon icon-warning">
              <i class="fas fa-cogs"></i>
            </div>
            <h3 class="card-title">Production Order Information</h3>
          </div>

          <div class="card-body">
            <div class="info-grid">
              <div class="info-item">
                <label class="info-label">Production Number</label>
                <div class="info-value">
                  <router-link
                    :to="`/manufacturing/production-orders/${jobTicket.production_order.production_id}`"
                    class="link-primary"
                  >
                    {{ jobTicket.production_order.production_number }}
                  </router-link>
                </div>
              </div>

              <div class="info-item">
                <label class="info-label">Production Date</label>
                <div class="info-value">
                  {{ formatDate(jobTicket.production_order.production_date) }}
                </div>
              </div>

              <div class="info-item">
                <label class="info-label">Status</label>
                <div class="info-value">
                  <span :class="getStatusBadgeClass(jobTicket.production_order.status)">
                    {{ jobTicket.production_order.status }}
                  </span>
                </div>
              </div>

              <div class="info-item">
                <label class="info-label">Planned Quantity</label>
                <div class="info-value">
                  {{ formatNumber(jobTicket.production_order.planned_quantity) }}
                </div>
              </div>

              <div class="info-item">
                <label class="info-label">Actual Quantity</label>
                <div class="info-value">
                  {{ formatNumber(jobTicket.production_order.actual_quantity) || 'Not completed' }}
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Work Order Information -->
        <section v-if="jobTicket.production_order?.work_order" class="info-card">
          <div class="card-header">
            <div class="header-icon icon-success">
              <i class="fas fa-tasks"></i>
            </div>
            <h3 class="card-title">Work Order Information</h3>
          </div>

          <div class="card-body">
            <div class="info-grid">
              <div class="info-item">
                <label class="info-label">Work Order Number</label>
                <div class="info-value">
                  <router-link
                    :to="`/manufacturing/work-orders/${jobTicket.production_order.work_order.wo_id}`"
                    class="link-primary"
                  >
                    {{ jobTicket.production_order.work_order.wo_number }}
                  </router-link>
                </div>
              </div>

              <div class="info-item">
                <label class="info-label">WO Status</label>
                <div class="info-value">
                  <span :class="getStatusBadgeClass(jobTicket.production_order.work_order.status)">
                    {{ jobTicket.production_order.work_order.status }}
                  </span>
                </div>
              </div>

              <div class="info-item">
                <label class="info-label">WO Quantity</label>
                <div class="info-value">
                  {{ formatNumber(jobTicket.production_order.work_order.quantity) }}
                </div>
              </div>

              <div class="info-item">
                <label class="info-label">Start Date</label>
                <div class="info-value">
                  {{ formatDate(jobTicket.production_order.work_order.start_date) }}
                </div>
              </div>

              <div class="info-item">
                <label class="info-label">End Date</label>
                <div class="info-value">
                  {{ formatDate(jobTicket.production_order.work_order.end_date) }}
                </div>
              </div>
            </div>
          </div>
        </section>


      </div>
    </main>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'JobTicketDetail',
  data() {
    return {
      jobTicket: null,
      loading: false,
      error: null,
      editMode: false,
      updating: false,
      editForm: {
        item: '',
        uom: '',
        qty_completed: 0,
        ref_jo_no: '',
        issue_date_jo: '',
        qty_jo: 0,
        customer: '',
        fgrn_no: '',
        date: ''
      }
    };
  },
  mounted() {
    this.loadJobTicket();
  },
  watch: {
    '$route.params.id': {
      immediate: true,
      handler() {
        if (this.$route.params.id) {
          this.loadJobTicket();
        }
      }
    },
    editMode(newVal) {
      if (newVal && this.jobTicket) {
        this.initEditForm();
      }
    }
  },
  methods: {
    async loadJobTicket() {
      this.loading = true;
      this.error = null;

      try {
        const response = await axios.get(`/manufacturing/job-tickets/${this.$route.params.id}`);

        if (response.data.success) {
          this.jobTicket = response.data.data;
        } else {
          this.error = response.data.message || 'Failed to load job ticket';
        }
      } catch (error) {
        console.error('Error loading job ticket:', error);
        this.error = error.response?.data?.message || 'Failed to load job ticket';
      } finally {
        this.loading = false;
      }
    },

    initEditForm() {
      if (this.jobTicket) {
        this.editForm = {
          item: this.jobTicket.item || '',
          uom: this.jobTicket.uom || '',
          qty_completed: this.jobTicket.qty_completed || 0,
          ref_jo_no: this.jobTicket.ref_jo_no || '',
          issue_date_jo: this.jobTicket.issue_date_jo || '',
          qty_jo: this.jobTicket.qty_jo || 0,
          customer: this.jobTicket.customer || '',
          fgrn_no: this.jobTicket.fgrn_no || '',
          date: this.jobTicket.date || ''
        };
      }
    },

    async updateJobTicket() {
      this.updating = true;

      try {
        const response = await axios.put(`/manufacturing/job-tickets/${this.jobTicket.ticket_id}`, this.editForm);

        if (response.data.success) {
          this.jobTicket = response.data.data;
          this.editMode = false;
          this.$toast?.success('Job ticket updated successfully');
        } else {
          throw new Error(response.data.message || 'Failed to update job ticket');
        }
      } catch (error) {
        console.error('Error updating job ticket:', error);
        this.error = error.response?.data?.message || 'Failed to update job ticket';
      } finally {
        this.updating = false;
      }
    },

    refreshData() {
      this.loadJobTicket();
    },

    formatDate(date) {
      if (!date) return '';
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    },

    formatDateTime(datetime) {
      if (!datetime) return '';
      return new Date(datetime).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    },

    formatNumber(number) {
      if (number === null || number === undefined) return '0';
      return parseFloat(number).toLocaleString();
    },

    getStatusBadgeClass(status) {
      const statusClasses = {
        'draft': 'status-badge status-draft',
        'active': 'status-badge status-active',
        'in_progress': 'status-badge status-progress',
        'completed': 'status-badge status-completed',
        'cancelled': 'status-badge status-cancelled',
        'on_hold': 'status-badge status-hold'
      };
      return statusClasses[status?.toLowerCase()] || 'status-badge status-default';
    }
  }
};
</script>

<style scoped>
/* CSS Custom Properties */
:root {
  --primary-color: #2563eb;
  --primary-hover: #1d4ed8;
  --success-color: #059669;
  --warning-color: #d97706;
  --danger-color: #dc2626;
  --info-color: #0891b2;

  --gray-50: #f8fafc;
  --gray-100: #f1f5f9;
  --gray-200: #e2e8f0;
  --gray-300: #cbd5e1;
  --gray-400: #94a3b8;
  --gray-500: #64748b;
  --gray-600: #475569;
  --gray-700: #334155;
  --gray-800: #1e293b;
  --gray-900: #0f172a;

  --border-radius: 0.75rem;
  --border-radius-sm: 0.5rem;
  --border-radius-lg: 1rem;
  --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
  --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Main Container */
.job-ticket-detail {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
  min-height: 100vh;
  background: linear-gradient(135deg, var(--gray-50) 0%, white 100%);
}

/* Header Section */
.page-header {
  margin-bottom: 2rem;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 2rem;
  padding: 1.5rem 2rem;
  background: white;
  border-radius: var(--border-radius-lg);
  box-shadow: var(--shadow);
  border: 1px solid var(--gray-200);
}

.title-section {
  flex: 1;
}

.page-title {
  font-size: 1.875rem;
  font-weight: 700;
  color: var(--gray-900);
  margin-bottom: 0.5rem;
  line-height: 1.2;
}

.breadcrumb-nav {
  margin: 0;
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0;
  padding: 0;
  list-style: none;
  font-size: 0.875rem;
  color: var(--gray-600);
}

.breadcrumb-item {
  display: flex;
  align-items: center;
}

.breadcrumb-item:not(:last-child)::after {
  content: '/';
  margin-left: 0.5rem;
  color: var(--gray-400);
}

.breadcrumb-link {
  color: var(--primary-color);
  text-decoration: none;
  transition: var(--transition);
}

.breadcrumb-link:hover {
  color: var(--primary-hover);
  text-decoration: underline;
}

.breadcrumb-item.active {
  color: var(--gray-500);
}

/* Header Actions */
.header-actions {
  display: flex;
  gap: 0.75rem;
  flex-shrink: 0;
}

/* Button Styles */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  font-size: 0.875rem;
  font-weight: 500;
  line-height: 1;
  border-radius: var(--border-radius-sm);
  border: 1px solid transparent;
  text-decoration: none;
  transition: var(--transition);
  cursor: pointer;
  white-space: nowrap;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
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
  box-shadow: var(--shadow);
}

.btn-outline {
  background: white;
  color: var(--gray-700);
  border-color: var(--gray-300);
}

.btn-outline:hover:not(:disabled) {
  background: var(--gray-50);
  border-color: var(--gray-400);
  transform: translateY(-1px);
  box-shadow: var(--shadow-sm);
}

.btn-success {
  background: var(--success-color);
  color: white;
  border-color: var(--success-color);
}

.btn-success:hover:not(:disabled) {
  background: #047857;
  border-color: #047857;
  transform: translateY(-1px);
  box-shadow: var(--shadow);
}

/* Content Section */
.content-section {
  min-height: 60vh;
}

/* Loading & Error States */
.loading-state,
.error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  background: white;
  border-radius: var(--border-radius-lg);
  box-shadow: var(--shadow);
  border: 1px solid var(--gray-200);
}

.loading-spinner {
  font-size: 3rem;
  color: var(--primary-color);
  margin-bottom: 1rem;
}

.loading-text {
  color: var(--gray-600);
  font-size: 1.125rem;
  margin: 0;
}

.error-state {
  border-left: 4px solid var(--danger-color);
}

.error-icon {
  font-size: 3rem;
  color: var(--danger-color);
  margin-bottom: 1rem;
}

.error-message {
  color: var(--danger-color);
  font-size: 1.125rem;
  font-weight: 500;
  margin: 0;
  text-align: center;
}

/* Card Styles */
.info-card {
  background: white;
  border-radius: var(--border-radius-lg);
  box-shadow: var(--shadow);
  border: 1px solid var(--gray-200);
  margin-bottom: 1.5rem;
  overflow: hidden;
}

.card-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.5rem 2rem;
  background: linear-gradient(135deg, var(--gray-50) 0%, white 100%);
  border-bottom: 1px solid var(--gray-200);
}

.header-icon {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: var(--border-radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  color: white;
  background: var(--primary-color);
}

.header-icon.icon-info {
  background: var(--info-color);
}

.header-icon.icon-warning {
  background: var(--warning-color);
}

.header-icon.icon-success {
  background: var(--success-color);
}

.card-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--gray-900);
  margin: 0;
}

.card-body {
  padding: 2rem;
}

/* Form Styles */
.edit-form {
  max-width: none;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--gray-700);
  margin-bottom: 0.5rem;
}

.form-label.required::after {
  content: ' *';
  color: var(--danger-color);
}

.form-control {
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  border: 1px solid var(--gray-300);
  border-radius: var(--border-radius-sm);
  background: white;
  transition: var(--transition);
}

.form-control:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

.form-control::placeholder {
  color: var(--gray-400);
}

.form-actions {
  display: flex;
  gap: 1rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--gray-200);
}

/* Info Grid */
.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.info-item {
  display: flex;
  flex-direction: column;
}

.info-item-wide {
  grid-column: 1 / -1;
}

.info-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--gray-500);
  text-transform: uppercase;
  letter-spacing: 0.025em;
  margin-bottom: 0.5rem;
}

.info-value {
  font-size: 0.95rem;
  color: var(--gray-900);
  font-weight: 500;
  min-height: 1.5rem;
  line-height: 1.4;
}

.item-name {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--gray-900);
}

.quantity-value {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--success-color);
}

/* Badges */
.ticket-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: white;
  background: var(--primary-color);
  border-radius: var(--border-radius-sm);
  line-height: 1;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.375rem 0.75rem;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.025em;
  border-radius: var(--border-radius-sm);
  line-height: 1;
}

.status-draft {
  background: var(--gray-100);
  color: var(--gray-700);
}

.status-active {
  background: #dbeafe;
  color: #1e40af;
}

.status-progress {
  background: #fef3c7;
  color: #92400e;
}

.status-completed {
  background: #d1fae5;
  color: #065f46;
}

.status-cancelled {
  background: #fee2e2;
  color: #991b1b;
}

.status-hold {
  background: #e0f2fe;
  color: #0c4a6e;
}

.status-default {
  background: var(--gray-100);
  color: var(--gray-700);
}

/* Links */
.link-primary {
  color: var(--primary-color);
  text-decoration: none;
  font-weight: 500;
  transition: var(--transition);
}

.link-primary:hover {
  color: var(--primary-hover);
  text-decoration: underline;
}



/* Responsive Design */
@media (max-width: 1024px) {
  .job-ticket-detail {
    padding: 1rem;
  }

  .form-grid,
  .info-grid {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
  }

  .card-body {
    padding: 1.5rem;
  }
}

@media (max-width: 768px) {
  .header-content {
    flex-direction: column;
    align-items: stretch;
    gap: 1.5rem;
    padding: 1rem;
  }

  .header-actions {
    justify-content: flex-start;
    flex-wrap: wrap;
  }

  .page-title {
    font-size: 1.5rem;
  }

  .form-grid,
  .info-grid {
    grid-template-columns: 1fr;
  }

  .card-header {
    padding: 1rem;
  }

  .card-body {
    padding: 1rem;
  }

  .form-actions {
    flex-direction: column;
  }

  .btn {
    justify-content: center;
    padding: 0.875rem 1.5rem;
  }
}

@media (max-width: 480px) {
  .job-ticket-detail {
    padding: 0.5rem;
  }

  .page-title {
    font-size: 1.25rem;
  }

  .breadcrumb {
    font-size: 0.75rem;
  }

  .header-actions {
    gap: 0.5rem;
  }

  .btn {
    padding: 0.75rem 1rem;
    font-size: 0.8125rem;
  }

  .info-card {
    margin-bottom: 1rem;
  }
}

/* Print Styles */
@media print {
  .job-ticket-detail {
    padding: 0;
    background: white;
  }

  .header-actions,
  .form-actions {
    display: none !important;
  }

  .info-card {
    box-shadow: none;
    border: 1px solid #ccc;
    margin-bottom: 1rem;
    break-inside: avoid;
  }

  .page-title {
    color: black;
  }
}

/* Focus Styles for Accessibility */
.btn:focus-visible,
.form-control:focus-visible,
.breadcrumb-link:focus-visible,
.link-primary:focus-visible {
  outline: 2px solid var(--primary-color);
  outline-offset: 2px;
}

/* Smooth Animations */
@media (prefers-reduced-motion: no-preference) {
  .btn {
    transition: var(--transition);
  }

  .btn:hover {
    transform: translateY(-1px);
  }

  .info-card {
    transition: var(--transition);
  }

  .info-card:hover {
    box-shadow: var(--shadow-lg);
  }
}

/* Reduced Motion Support */
@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }

  .btn:hover {
    transform: none;
  }
}
</style>
