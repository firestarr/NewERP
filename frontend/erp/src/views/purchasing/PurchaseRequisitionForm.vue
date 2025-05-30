<!-- src/views/purchasing/PurchaseRequisitionForm.vue -->
<template>
  <div class="purchase-requisition-form">
    <!-- Header Section -->
    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title">
          <i class="fas fa-file-invoice"></i>
          {{ isEditMode ? 'Edit Purchase Requisition' : 'Create Purchase Requisition' }}
        </h1>
        <p class="page-subtitle">{{ isEditMode ? 'Modify existing purchase requisition' : 'Create a new purchase requisition for approval' }}</p>
      </div>
      <div class="header-actions">
        <!-- <button
          v-if="isEditMode && form.status === 'draft'"
          @click="submitForm"
          class="btn btn-success"
          :disabled="saving"
        >
          <i class="fas fa-paper-plane"></i>
          <span>Submit for Approval</span>
        </button> -->
        <!-- <button
          @click="saveForm"
          class="btn btn-primary"
          :disabled="loading || saving"
        >
          <i class="fas fa-save"></i>
          <span>{{ isEditMode ? 'Update' : 'Save' }}</span>
        </button> -->
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="loading-spinner">
        <div class="spinner"></div>
        <p>Loading form data...</p>
      </div>
    </div>

    <!-- Main Form -->
    <div v-else class="form-container">
      <form @submit.prevent="saveForm" class="requisition-form">

        <!-- Basic Information Card -->
        <div class="form-card">
          <div class="card-header">
            <h3 class="card-title">
              <i class="fas fa-info-circle"></i>
              Basic Information
            </h3>
          </div>
          <div class="card-body">
            <div class="form-grid">
              <div class="form-group">
                <label for="pr_number" class="form-label">
                  <i class="fas fa-hashtag"></i>
                  PR Number
                </label>
                <input
                  type="text"
                  id="pr_number"
                  class="form-control disabled-input"
                  v-model="form.pr_number"
                  readonly
                  placeholder="Will be generated automatically"
                />
              </div>

              <div class="form-group">
                <label for="pr_date" class="form-label required">
                  <i class="fas fa-calendar-alt"></i>
                  PR Date
                </label>
                <input
                  type="date"
                  id="pr_date"
                  class="form-control"
                  v-model="form.pr_date"
                  required
                  :class="{ 'error': errors.pr_date }"
                />
                <div v-if="errors.pr_date" class="error-message">
                  <i class="fas fa-exclamation-circle"></i>
                  {{ errors.pr_date[0] }}
                </div>
              </div>

              <div class="form-group">
                <label for="requester_id" class="form-label required">
                  <i class="fas fa-user"></i>
                  Requester
                </label>
                <select
                  id="requester_id"
                  class="form-control"
                  v-model="form.requester_id"
                  required
                  :class="{ 'error': errors.requester_id }"
                >
                  <option value="" disabled>Select Requester</option>
                  <option v-for="user in users" :key="user.id" :value="user.id">
                    {{ user.name }}
                  </option>
                </select>
                <div v-if="errors.requester_id" class="error-message">
                  <i class="fas fa-exclamation-circle"></i>
                  {{ errors.requester_id[0] }}
                </div>
              </div>

              <div class="form-group">
                <label for="status" class="form-label">
                  <i class="fas fa-flag"></i>
                  Status
                </label>
                <div class="status-display">
                  <span class="status-badge" :class="`status-${form.status}`">
                    {{ form.status.charAt(0).toUpperCase() + form.status.slice(1) }}
                  </span>
                </div>
              </div>
            </div>

            <div class="form-group full-width">
              <label for="notes" class="form-label">
                <i class="fas fa-sticky-note"></i>
                Notes
              </label>
              <textarea
                id="notes"
                class="form-control"
                v-model="form.notes"
                rows="3"
                placeholder="Add any additional notes or comments..."
                :class="{ 'error': errors.notes }"
              ></textarea>
              <div v-if="errors.notes" class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                {{ errors.notes[0] }}
              </div>
            </div>
          </div>
        </div>

        <!-- Requisition Lines Card -->
        <div class="form-card">
          <div class="card-header">
            <h3 class="card-title">
              <i class="fas fa-list"></i>
              Requisition Items
              <span class="item-count">({{ form.lines.length }} {{ form.lines.length === 1 ? 'item' : 'items' }})</span>
            </h3>
            <button
              type="button"
              class="btn btn-outline-primary btn-sm add-line-btn"
              @click="addLine"
            >
              <i class="fas fa-plus"></i>
              Add Item
            </button>
          </div>

          <div class="card-body">
            <!-- Empty State -->
            <div v-if="form.lines.length === 0" class="empty-state">
              <i class="fas fa-box-open"></i>
              <h4>No items added yet</h4>
              <p>Click "Add Item" to start adding items to this requisition</p>
              <button type="button" class="btn btn-primary" @click="addLine">
                <i class="fas fa-plus"></i>
                Add Your First Item
              </button>
            </div>

            <!-- Items Table -->
            <div v-else class="items-table-container">
              <div class="table-responsive">
                <table class="items-table">
                  <thead>
                    <tr>
                      <th class="col-item">
                        <i class="fas fa-cube"></i>
                        Item
                      </th>
                      <th class="col-quantity">
                        <i class="fas fa-sort-numeric-up"></i>
                        Quantity
                      </th>
                      <th class="col-unit">
                        <i class="fas fa-ruler"></i>
                        Unit
                      </th>
                      <th class="col-date">
                        <i class="fas fa-calendar"></i>
                        Required Date
                      </th>
                      <th class="col-actions">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="(line, index) in form.lines"
                      :key="index"
                      class="item-row"
                      :class="{ 'error-row': lineErrors[index] }"
                    >
                      <!-- Item Selection -->
                      <td class="item-cell">
                        <div class="item-search-container">
                          <div class="search-input-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input
                              type="text"
                              class="form-control search-input"
                              v-model="line.itemSearch"
                              :class="{ 'error': lineErrors[index]?.item_id }"
                              placeholder="Search for an item..."
                              @focus="showDropdown(index)"
                              @input="showDropdown(index)"
                              @blur="hideDropdown(index)"
                              required
                            />
                          </div>

                          <div
                            v-show="line.showDropdown"
                            class="dropdown-menu"
                          >
                            <div
                              v-for="item in filteredItems(line.itemSearch)"
                              :key="item.item_id"
                              @mousedown="selectItem(line, item)"
                              class="dropdown-item"
                            >
                              <div class="item-info">
                                <span class="item-code">{{ item.item_code }}</span>
                                <span class="item-name">{{ item.name }}</span>
                              </div>
                            </div>
                            <div
                              v-if="filteredItems(line.itemSearch).length === 0"
                              class="dropdown-item no-results"
                            >
                              <i class="fas fa-search"></i>
                              No items found
                            </div>
                          </div>
                        </div>
                        <div v-if="lineErrors[index]?.item_id" class="error-message">
                          <i class="fas fa-exclamation-circle"></i>
                          {{ lineErrors[index].item_id[0] }}
                        </div>
                      </td>

                      <!-- Quantity -->
                      <td class="quantity-cell">
                        <input
                          type="number"
                          class="form-control quantity-input"
                          v-model.number="line.quantity"
                          min="0.01"
                          step="0.01"
                          required
                          :class="{ 'error': lineErrors[index]?.quantity }"
                        />
                        <div v-if="lineErrors[index]?.quantity" class="error-message">
                          <i class="fas fa-exclamation-circle"></i>
                          {{ lineErrors[index].quantity[0] }}
                        </div>
                      </td>

                      <!-- Unit of Measure -->
                      <td class="unit-cell">
                        <select
                          class="form-control unit-select"
                          v-model="line.uom_id"
                          required
                          :class="{ 'error': lineErrors[index]?.uom_id }"
                        >
                          <option value="" disabled>Select Unit</option>
                          <option v-for="uom in uoms" :key="uom.uom_id" :value="uom.uom_id">
                            {{ uom.name }}
                          </option>
                        </select>
                        <div v-if="lineErrors[index]?.uom_id" class="error-message">
                          <i class="fas fa-exclamation-circle"></i>
                          {{ lineErrors[index].uom_id[0] }}
                        </div>
                      </td>

                      <!-- Required Date -->
                      <td class="date-cell">
                        <input
                          type="date"
                          class="form-control date-input"
                          v-model="line.required_date"
                          :class="{ 'error': lineErrors[index]?.required_date }"
                        />
                        <div v-if="lineErrors[index]?.required_date" class="error-message">
                          <i class="fas fa-exclamation-circle"></i>
                          {{ lineErrors[index].required_date[0] }}
                        </div>
                      </td>

                      <!-- Actions -->
                      <td class="actions-cell">
                        <button
                          type="button"
                          class="btn btn-danger btn-sm delete-btn"
                          @click="removeLine(index)"
                          title="Remove Item"
                          :disabled="form.lines.length === 1"
                        >
                          <i class="fas fa-trash"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
          <div class="actions-left">
            <router-link :to="'/purchasing/requisitions'" class="btn btn-secondary">
              <i class="fas fa-arrow-left"></i>
              <span>Cancel</span>
            </router-link>
          </div>

          <div class="actions-right">
            <button
              v-if="isEditMode && form.status === 'draft'"
              @click="submitForm"
              type="button"
              class="btn btn-success"
              :disabled="saving"
            >
              <i class="fas fa-paper-plane"></i>
              <span>Submit for Approval</span>
            </button>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="saving"
            >
              <i class="fas fa-save"></i>
              <span>{{ isEditMode ? 'Update' : 'Save' }}</span>
              <div v-if="saving" class="btn-spinner"></div>
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- Alert Notification -->
    <transition name="alert-slide">
      <div v-if="alert.show" class="alert-notification">
        <div class="alert" :class="`alert-${alert.type}`">
          <div class="alert-content">
            <i
              class="alert-icon"
              :class="{
                'fas fa-check-circle': alert.type === 'success',
                'fas fa-exclamation-triangle': alert.type === 'warning',
                'fas fa-times-circle': alert.type === 'danger',
                'fas fa-info-circle': alert.type === 'info'
              }"
            ></i>
            <span class="alert-message">{{ alert.message }}</span>
          </div>
          <button type="button" class="alert-close" @click="alert.show = false">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>
    </transition>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'PurchaseRequisitionForm',
  props: {
    id: {
      type: [String, Number],
      default: null
    }
  },
  data() {
    return {
      form: {
        pr_number: '',
        pr_date: this.formatDate(new Date()),
        requester_id: '',
        status: 'draft',
        notes: '',
        lines: []
      },
      originalForm: null,
      users: [],
      items: [],
      uoms: [],
      loading: true,
      saving: false,
      errors: {},
      lineErrors: [],
      alert: {
        show: false,
        type: 'success',
        message: ''
      }
    };
  },
  computed: {
    isEditMode() {
      return !!this.id;
    }
  },
  async created() {
    try {
      await this.fetchDropdownData();

      if (this.isEditMode) {
        await this.fetchPRData();
      } else {
        this.addLine();
      }

      if (!this.isEditMode && this.users.length > 0) {
        this.form.requester_id = this.users[0].id;
      }
    } catch (error) {
      this.showAlert('danger', 'Failed to load data. Please try again.');
      console.error('Error loading form data:', error);
    } finally {
      this.loading = false;
    }
  },
  methods: {
    // Methods for searchable dropdown
    filteredItems(search) {
      if (!search) {
        return this.items;
      }
      return this.items.filter(item =>
        item.name.toLowerCase().includes(search.toLowerCase()) ||
        item.item_code.toLowerCase().includes(search.toLowerCase())
      );
    },

    showDropdown(index) {
      if (!this.form.lines[index].showDropdown) {
        this.form.lines[index].showDropdown = true;
      }
    },

    hideDropdown(index) {
      setTimeout(() => {
        this.form.lines[index].showDropdown = false;
      }, 200);
    },

    selectItem(line, item) {
      line.item_id = item.item_id;
      line.itemSearch = `${item.item_code} - ${item.name}`;
      line.showDropdown = false;

      // Auto-fill UOM based on item's default UOM
      if (item.unitOfMeasure && item.unitOfMeasure.uom_id) {
        line.uom_id = item.unitOfMeasure.uom_id;
      } else if (item.uom_id) {
        line.uom_id = item.uom_id;
      } else if (item.default_uom_id) {
        line.uom_id = item.default_uom_id;
      }

      console.log('Selected item:', item);
      console.log('UOM set to:', line.uom_id);
    },

    async fetchDropdownData() {
      try {
        const usersResponse = await axios.get('/user');

        if (Array.isArray(usersResponse.data)) {
          this.users = usersResponse.data;
        } else if (usersResponse.data && usersResponse.data.id) {
          this.users = [usersResponse.data];
        } else if (usersResponse.data && Array.isArray(usersResponse.data.data)) {
          this.users = usersResponse.data.data;
        } else if (usersResponse.data && usersResponse.data.data && usersResponse.data.data.id) {
          this.users = [usersResponse.data.data];
        } else {
          this.users = [];
        }

        const itemsResponse = await axios.get('/items/purchasable');
        this.items = itemsResponse.data.data || [];

        if (this.items.length > 0) {
          console.log('Item structure example:', this.items[0]);
        }

        const uomsResponse = await axios.get('/uoms');
        this.uoms = uomsResponse.data.data || [];
      } catch (error) {
        console.error('Error fetching dropdown data:', error);
        throw error;
      }
    },

    onItemChange(line) {
      const selectedItem = this.items.find(item => item.item_id === line.item_id);
      if (selectedItem && selectedItem.unitOfMeasure) {
        line.uom_id = selectedItem.unitOfMeasure.uom_id;
      } else {
        line.uom_id = '';
      }
    },

    async fetchPRData() {
      try {
        const response = await axios.get(`/purchase-requisitions/${this.id}`);
        const prData = response.data.data;

        this.form = {
          pr_number: prData.pr_number,
          pr_date: this.formatDate(prData.pr_date),
          requester_id: prData.requester_id,
          status: prData.status,
          notes: prData.notes,
          lines: prData.lines.map(line => {
            const matchedItem = this.items.find(item => item.item_id === line.item_id);
            const itemDisplay = matchedItem ?
              `${matchedItem.item_code} - ${matchedItem.name}` : '';

            return {
              item_id: line.item_id,
              itemSearch: itemDisplay,
              quantity: parseFloat(line.quantity),
              uom_id: line.uom_id,
              required_date: line.required_date ? this.formatDate(line.required_date) : null,
              notes: line.notes,
              showDropdown: false
            };
          })
        };

        this.originalForm = JSON.parse(JSON.stringify(this.form));
      } catch (error) {
        console.error('Error fetching PR data:', error);
        throw error;
      }
    },

    addLine() {
      this.form.lines.push({
        item_id: '',
        itemSearch: '',
        quantity: 1,
        uom_id: '',
        required_date: '',
        notes: '',
        showDropdown: false
      });
    },

    removeLine(index) {
      if (this.form.lines.length > 1) {
        this.form.lines.splice(index, 1);
      } else {
        this.showAlert('warning', 'At least one line item is required.');
      }
    },

    async saveForm() {
      if (!this.validateForm()) {
        return;
      }

      this.saving = true;

      try {
        let response;
        const formData = this.prepareFormData();

        if (this.isEditMode) {
          response = await axios.put(`/purchase-requisitions/${this.id}`, formData);
          this.showAlert('success', 'Purchase Requisition updated successfully.');
        } else {
          response = await axios.post('/purchase-requisitions', formData);
          this.showAlert('success', 'Purchase Requisition created successfully.');

          setTimeout(() => {
            this.$router.push(`/purchasing/requisitions/${response.data.data.pr_id}`);
          }, 1500);
        }

        const prData = response.data.data;
        this.form.pr_number = prData.pr_number;
        this.form.status = prData.status;

        this.originalForm = JSON.parse(JSON.stringify(this.form));
      } catch (error) {
        this.handleApiError(error);
      } finally {
        this.saving = false;
      }
    },

    async submitForm() {
      if (!this.validateForm()) {
        return;
      }

      this.saving = true;

      try {
        await this.saveForm();

        const response = await axios.patch(`/purchase-requisitions/${this.id}/status`, {
          status: 'pending'
        });

        this.form.status = response.data.data.status;
        this.showAlert('success', 'Purchase Requisition submitted for approval.');

        setTimeout(() => {
          this.$router.push(`/purchasing/requisitions/${this.id}`);
        }, 1500);
      } catch (error) {
        this.handleApiError(error);
      } finally {
        this.saving = false;
      }
    },

    validateForm() {
      this.errors = {};
      this.lineErrors = [];

      if (!this.form.pr_date) {
        this.errors.pr_date = ['PR Date is required.'];
      }

      if (!this.form.requester_id) {
        this.errors.requester_id = ['Requester is required.'];
      }

      let hasLineErrors = false;

      this.form.lines.forEach((line, index) => {
        const lineError = {};

        if (!line.item_id) {
          lineError.item_id = ['Item is required.'];
          hasLineErrors = true;
        }

        if (!line.quantity || line.quantity <= 0) {
          lineError.quantity = ['Quantity must be greater than 0.'];
          hasLineErrors = true;
        }

        if (!line.uom_id) {
          lineError.uom_id = ['Unit of Measure is required.'];
          hasLineErrors = true;
        }

        this.lineErrors[index] = Object.keys(lineError).length ? lineError : null;
      });

      return !Object.keys(this.errors).length && !hasLineErrors;
    },

    prepareFormData() {
      const formData = {
        pr_date: this.form.pr_date,
        requester_id: this.form.requester_id,
        notes: this.form.notes,
        lines: this.form.lines.map(line => ({
          item_id: line.item_id,
          quantity: line.quantity,
          uom_id: line.uom_id,
          required_date: line.required_date,
          notes: line.notes
        }))
      };

      return formData;
    },

    handleApiError(error) {
      if (error.response && error.response.data && error.response.data.errors) {
        this.errors = error.response.data.errors;

        if (this.errors.lines) {
          this.errors.lines.forEach(lineError => {
            const match = lineError.match(/^lines\.(\d+)\.(.+)/);
            if (match) {
              const lineIndex = parseInt(match[1]);
              const field = match[2];
              const message = lineError.split(':')[1].trim();

              if (!this.lineErrors[lineIndex]) {
                this.lineErrors[lineIndex] = {};
              }

              this.lineErrors[lineIndex][field] = [message];
            }
          });

          delete this.errors.lines;
        }

        this.showAlert('danger', 'Please correct the errors in the form.');
      } else {
        this.showAlert('danger', 'An error occurred. Please try again.');
        console.error('API Error:', error);
      }
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
    },

    formatDate(dateString) {
      if (!dateString) return '';

      const date = new Date(dateString);
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');

      return `${year}-${month}-${day}`;
    },

    getCurrentUser() {
      return {
        id: 1,
        name: 'Current User'
      };
    }
  }
};
</script>

<style scoped>
/* Global Variables */
:root {
  --primary-color: #4f46e5;
  --primary-hover: #4338ca;
  --secondary-color: #6b7280;
  --success-color: #10b981;
  --danger-color: #ef4444;
  --warning-color: #f59e0b;
  --info-color: #3b82f6;
  --border-color: #e5e7eb;
  --gray-50: #f9fafb;
  --gray-100: #f3f4f6;
  --gray-200: #e5e7eb;
  --gray-300: #d1d5db;
  --gray-400: #9ca3af;
  --gray-500: #6b7280;
  --gray-600: #4b5563;
  --gray-700: #374151;
  --gray-800: #1f2937;
  --gray-900: #111827;
  --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  --border-radius: 8px;
  --border-radius-lg: 12px;
  --transition: all 0.2s ease-in-out;
}

/* Base Styles */
* {
  box-sizing: border-box;
}

.purchase-requisition-form {
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px;
  background-color: var(--gray-50);
  min-height: 100vh;
}

/* Page Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 32px;
  padding: 24px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: var(--border-radius-lg);
  color: white;
  box-shadow: var(--shadow-lg);
}

.header-content {
  flex: 1;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  margin: 0 0 8px 0;
  display: flex;
  align-items: center;
  gap: 12px;
}

.page-title i {
  color: rgba(255, 255, 255, 0.9);
}

.page-subtitle {
  font-size: 16px;
  margin: 0;
  opacity: 0.9;
  font-weight: 400;
}

.header-actions {
  display: flex;
  gap: 12px;
  align-items: center;
}

/* Loading State */
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
  width: 48px;
  height: 48px;
  border: 4px solid var(--gray-200);
  border-top: 4px solid var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.loading-spinner p {
  color: var(--gray-600);
  font-size: 16px;
  margin: 0;
}

/* Form Container */
.form-container {
  max-width: 100%;
}

.requisition-form {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

/* Form Cards */
.form-card {
  background: white;
  border-radius: var(--border-radius-lg);
  box-shadow: var(--shadow-md);
  overflow: hidden;
  transition: var(--transition);
}

.form-card:hover {
  box-shadow: var(--shadow-lg);
}

.card-header {
  background: var(--gray-50);
  padding: 20px 24px;
  border-bottom: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-title {
  font-size: 18px;
  font-weight: 600;
  margin: 0;
  color: var(--gray-800);
  display: flex;
  align-items: center;
  gap: 8px;
}

.card-title i {
  color: var(--primary-color);
  font-size: 20px;
}

.item-count {
  background: var(--primary-color);
  color: white;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
  margin-left: 8px;
}

.card-body {
  padding: 24px;
}

/* Form Grid */
.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
  margin-bottom: 20px;
}

/* Form Groups */
.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.form-label {
  font-weight: 600;
  font-size: 14px;
  color: var(--gray-700);
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.form-label.required::after {
  content: '*';
  color: var(--danger-color);
  margin-left: 4px;
}

.form-label i {
  color: var(--primary-color);
  font-size: 14px;
}

/* Form Controls */
.form-control {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid var(--border-color);
  border-radius: var(--border-radius);
  font-size: 14px;
  line-height: 1.4;
  transition: var(--transition);
  background: white;
}

.form-control:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.form-control.error {
  border-color: var(--danger-color);
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.form-control:disabled,
.disabled-input {
  background-color: var(--gray-100);
  color: var(--gray-500);
  cursor: not-allowed;
}

.form-control::placeholder {
  color: var(--gray-400);
}

/* Textarea */
textarea.form-control {
  resize: vertical;
  min-height: 80px;
  font-family: inherit;
}

/* Status Display */
.status-display {
  padding: 12px 16px;
  border: 2px solid var(--border-color);
  border-radius: var(--border-radius);
  background: var(--gray-50);
}

.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-draft {
  background: #fef3c7;
  color: #92400e;
}

.status-pending {
  background: #dbeafe;
  color: #1e40af;
}

.status-approved {
  background: #d1fae5;
  color: #065f46;
}

.status-rejected {
  background: #fee2e2;
  color: #991b1b;
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  border: none;
  border-radius: var(--border-radius);
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
  transition: var(--transition);
  position: relative;
  overflow: hidden;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background: var(--primary-color);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: var(--primary-hover);
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.btn-secondary {
  background: var(--gray-500);
  color: white;
}

.btn-secondary:hover:not(:disabled) {
  background: var(--gray-600);
  transform: translateY(-1px);
}

.btn-success {
  background: var(--success-color);
  color: white;
}

.btn-success:hover:not(:disabled) {
  background: #059669;
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.btn-danger {
  background: var(--danger-color);
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background: #dc2626;
  transform: translateY(-1px);
}

.btn-outline-primary {
  background: transparent;
  color: var(--primary-color);
  border: 2px solid var(--primary-color);
}

.btn-outline-primary:hover:not(:disabled) {
  background: var(--primary-color);
  color: white;
}

.btn-sm {
  padding: 8px 16px;
  font-size: 13px;
}

/* Button Spinner */
.btn-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid transparent;
  border-top: 2px solid currentColor;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-left: 8px;
}

/* Add Line Button */
.add-line-btn {
  white-space: nowrap;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 48px 24px;
  color: var(--gray-500);
}

.empty-state i {
  font-size: 48px;
  color: var(--gray-300);
  margin-bottom: 16px;
}

.empty-state h4 {
  font-size: 18px;
  font-weight: 600;
  color: var(--gray-600);
  margin: 0 0 8px 0;
}

.empty-state p {
  font-size: 14px;
  margin: 0 0 24px 0;
}

/* Items Table */
.items-table-container {
  overflow: visible;
  border: 1px solid var(--border-color);
  border-radius: var(--border-radius);
}

.table-responsive {
  overflow: visible !important;
}

.items-table {
  width: 100%;
  border-collapse: collapse;
  border-spacing: 0;
  background: white;
  margin: 0;
}

.items-table thead th {
  background: var(--gray-50);
  color: var(--gray-700);
  font-weight: 600;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  padding: 16px;
  border-bottom: 2px solid var(--border-color);
  border-right: 1px solid var(--border-color);
  position: sticky;
  top: 0;
  z-index: 10;
}

.items-table thead th:last-child {
  border-right: none;
}

.items-table thead th i {
  color: var(--primary-color);
  margin-right: 6px;
}

.items-table tbody tr {
  transition: var(--transition);
  border-bottom: 1px solid var(--border-color);
}

.items-table tbody tr:last-child {
  border-bottom: none;
}

.items-table tbody tr:hover {
  background: var(--gray-50);
}

.items-table tbody tr.error-row {
  background: #fef2f2;
}

.items-table td {
  padding: 16px;
  border-right: 1px solid var(--border-color);
  vertical-align: top;
  position: relative;
}

.items-table td:last-child {
  border-right: none;
}

/* Column widths */
.col-item { width: 40%; }
.col-quantity { width: 15%; }
.col-unit { width: 15%; }
.col-date { width: 20%; }
.col-actions { width: 10%; }

/* Item Search */
.item-search-container {
  position: relative;
  width: 100%;
}

.search-input-wrapper {
  position: relative;
}

.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--gray-400);
  z-index: 5;
  pointer-events: none;
}

.search-input {
  padding-left: 40px !important;
}

/* Dropdown Menu */
.dropdown-menu {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  z-index: 1000;
  max-height: 200px;
  overflow-y: auto;
  background: white;
  border: 1px solid var(--border-color);
  border-radius: var(--border-radius);
  box-shadow: var(--shadow-lg);
  margin-top: 4px;
}

.dropdown-item {
  padding: 12px 16px;
  cursor: pointer;
  transition: var(--transition);
  border-bottom: 1px solid var(--gray-100);
}

.dropdown-item:last-child {
  border-bottom: none;
}

.dropdown-item:hover {
  background: var(--gray-50);
}

.dropdown-item.no-results {
  color: var(--gray-500);
  cursor: default;
  text-align: center;
  font-style: italic;
}

.dropdown-item.no-results:hover {
  background: transparent;
}

.item-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.item-code {
  font-weight: 600;
  color: var(--gray-800);
  font-size: 13px;
}

.item-name {
  color: var(--gray-600);
  font-size: 12px;
}

/* Table Inputs */
.quantity-input,
.unit-select,
.date-input {
  padding: 8px 12px;
  font-size: 13px;
}

.delete-btn {
  padding: 6px 8px;
  border-radius: 6px;
}

/* Error Messages */
.error-message {
  display: flex;
  align-items: center;
  gap: 6px;
  color: var(--danger-color);
  font-size: 12px;
  margin-top: 4px;
  font-weight: 500;
}

.error-message i {
  font-size: 12px;
}

/* Form Actions */
.form-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  background: white;
  border-radius: var(--border-radius-lg);
  box-shadow: var(--shadow-md);
  margin-top: 8px;
}

.actions-left,
.actions-right {
  display: flex;
  gap: 12px;
  align-items: center;
}

/* Alert Notifications */
.alert-notification {
  position: fixed;
  top: 24px;
  right: 24px;
  z-index: 1050;
  max-width: 400px;
}

.alert {
  display: flex;
  align-items: center;
  padding: 16px 20px;
  border-radius: var(--border-radius);
  box-shadow: var(--shadow-lg);
  border-left: 4px solid;
}

.alert-success {
  background: #f0fdf4;
  border-left-color: var(--success-color);
  color: #166534;
}

.alert-warning {
  background: #fffbeb;
  border-left-color: var(--warning-color);
  color: #92400e;
}

.alert-danger {
  background: #fef2f2;
  border-left-color: var(--danger-color);
  color: #991b1b;
}

.alert-info {
  background: #eff6ff;
  border-left-color: var(--info-color);
  color: #1e40af;
}

.alert-content {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
}

.alert-icon {
  font-size: 18px;
}

.alert-message {
  font-weight: 500;
  font-size: 14px;
}

.alert-close {
  background: none;
  border: none;
  color: currentColor;
  cursor: pointer;
  font-size: 16px;
  opacity: 0.7;
  padding: 0;
  margin-left: 12px;
  transition: var(--transition);
}

.alert-close:hover {
  opacity: 1;
}

/* Alert Transitions */
.alert-slide-enter-active,
.alert-slide-leave-active {
  transition: all 0.3s ease;
}

.alert-slide-enter-from {
  transform: translateX(100%);
  opacity: 0;
}

.alert-slide-leave-to {
  transform: translateX(100%);
  opacity: 0;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .purchase-requisition-form {
    padding: 16px;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
    text-align: center;
  }

  .header-actions {
    justify-content: center;
  }

  .page-title {
    font-size: 24px;
    justify-content: center;
  }

  .card-header {
    flex-direction: column;
    gap: 12px;
    align-items: stretch;
  }

  .card-title {
    justify-content: center;
  }

  .form-actions {
    flex-direction: column;
    gap: 12px;
  }

  .actions-left,
  .actions-right {
    width: 100%;
    justify-content: center;
  }

  .btn {
    width: 100%;
    justify-content: center;
  }

  .alert-notification {
    left: 16px;
    right: 16px;
    max-width: none;
  }

  /* Table responsiveness */
  .items-table-container {
    overflow-x: auto;
  }

  .items-table {
    min-width: 600px;
  }
}

@media (max-width: 640px) {
  .purchase-requisition-form {
    padding: 12px;
  }

  .card-body {
    padding: 16px;
  }

  .form-grid {
    gap: 16px;
  }

  .page-header {
    padding: 20px;
    margin-bottom: 20px;
  }

  .page-title {
    font-size: 20px;
  }

  .page-subtitle {
    font-size: 14px;
  }
}

/* Print Styles */
@media print {
  .purchase-requisition-form {
    background: white;
    padding: 0;
  }

  .page-header {
    background: white !important;
    color: black !important;
    box-shadow: none;
    border-bottom: 2px solid #000;
  }

  .header-actions,
  .form-actions,
  .delete-btn,
  .add-line-btn {
    display: none !important;
  }

  .form-card {
    box-shadow: none;
    border: 1px solid #000;
    break-inside: avoid;
  }

  .items-table {
    break-inside: avoid;
  }
}

/* Focus management for accessibility */
.form-control:focus-visible {
  outline: 2px solid var(--primary-color);
  outline-offset: 2px;
}

.btn:focus-visible {
  outline: 2px solid var(--primary-color);
  outline-offset: 2px;
}

/* Improved scrollbar for dropdown */
.dropdown-menu::-webkit-scrollbar {
  width: 6px;
}

.dropdown-menu::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.dropdown-menu::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.dropdown-menu::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>
