<!-- Full Updated ConvertToRFQ.vue -->
<template>
  <div class="convert-rfq-page">
    <div v-if="loading" class="loading-indicator">
      <i class="fas fa-spinner fa-spin"></i> Memuat data...
    </div>

    <div v-else-if="error" class="error-message">
      <i class="fas fa-exclamation-circle"></i> {{ error }}
    </div>

    <div v-else>
      <div class="page-header">
        <h2 class="title">Konversi PR ke RFQ</h2>
        <div class="status-badge" :class="getStatusClass(purchaseRequisition.status)">
          {{ purchaseRequisition.status }}
        </div>
      </div>

      <!-- PR Information Card -->
      <div class="info-card">
        <div class="card-header">
          <h3>Informasi PR</h3>
        </div>
        <div class="card-body">
          <div class="info-grid">
            <div class="info-item">
              <div class="label">Nomor PR</div>
              <div class="value">{{ purchaseRequisition.pr_number }}</div>
            </div>
            <div class="info-item">
              <div class="label">Tanggal PR</div>
              <div class="value">{{ formatDate(purchaseRequisition.pr_date) }}</div>
            </div>
            <div class="info-item">
              <div class="label">Pemohon</div>
              <div class="value">{{ requesterName }}</div>
            </div>
            <div class="info-item">
              <div class="label">Status</div>
              <div class="value">{{ purchaseRequisition.status }}</div>
            </div>
            <div class="info-item">
              <div class="label">Total Items</div>
              <div class="value">{{ purchaseRequisition.lines ? purchaseRequisition.lines.length : 0 }} items</div>
            </div>
            <div class="info-item" v-if="purchaseRequisition.notes">
              <div class="label">Catatan PR</div>
              <div class="value">{{ purchaseRequisition.notes }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- RFQ Details Card -->
      <div class="info-card">
        <div class="card-header">
          <h3>Detail RFQ Baru</h3>
        </div>
        <div class="card-body">
          <div class="form-row">
            <div class="form-group">
              <label for="rfq-date">Tanggal RFQ <span class="required">*</span></label>
              <input
                type="date"
                id="rfq-date"
                v-model="rfqData.rfq_date"
                class="form-control"
                :class="{ 'is-invalid': errors.rfq_date }"
                required
              >
              <div class="error-text" v-if="errors.rfq_date">{{ errors.rfq_date }}</div>
            </div>

            <div class="form-group">
              <label for="validity-date">Tanggal Berlaku</label>
              <input
                type="date"
                id="validity-date"
                v-model="rfqData.validity_date"
                class="form-control"
                :class="{ 'is-invalid': errors.validity_date }"
                :min="rfqData.rfq_date"
              >
              <div class="help-text">RFQ akan berlaku hingga tanggal ini</div>
              <div class="error-text" v-if="errors.validity_date">{{ errors.validity_date }}</div>
            </div>
          </div>

          <div class="form-group">
            <label for="notes">Catatan RFQ</label>
            <textarea
              id="notes"
              v-model="rfqData.notes"
              rows="3"
              class="form-control"
              placeholder="Tambahkan catatan khusus untuk RFQ ini (opsional)">
            </textarea>
            <div class="help-text">Catatan ini akan terlihat oleh vendor saat menerima RFQ</div>
          </div>

          <!-- Enhanced Vendor Selection -->
          <div class="vendor-selection">
            <div class="vendor-selection-header">
              <h4>Pilihan Vendor <span class="required">*</span></h4>
              <div class="vendor-selection-info">
                <i class="fas fa-info-circle"></i>
                Vendor yang dipilih akan otomatis terpilih saat mengirim RFQ
              </div>
            </div>
            
            <div class="form-group">
              <div class="vendor-search">
                <div class="search-input">
                  <i class="fas fa-search search-icon"></i>
                  <input
                    type="text"
                    placeholder="Cari vendor berdasarkan nama, kode, atau email..."
                    v-model="vendorSearchQuery"
                    @input="searchVendors"
                    class="form-control"
                  >
                </div>
                <button
                  type="button"
                  class="btn btn-outline-primary add-vendor-btn"
                  @click="showVendorsList = true"
                  :disabled="loadingVendors"
                >
                  <i class="fas fa-plus"></i> 
                  {{ loadingVendors ? 'Memuat...' : 'Tambah Vendor' }}
                </button>
              </div>
            </div>

            <!-- Selected Vendors Display -->
            <div v-if="selectedVendors.length === 0" class="empty-vendors">
              <div class="empty-icon">
                <i class="fas fa-users"></i>
              </div>
              <p><strong>Belum ada vendor yang dipilih</strong></p>
              <p>Silakan pilih minimal satu vendor untuk RFQ ini</p>
              <button
                type="button"
                class="btn btn-primary"
                @click="showVendorsList = true"
                :disabled="loadingVendors"
              >
                <i class="fas fa-plus"></i> Pilih Vendor
              </button>
            </div>

            <div v-else class="selected-vendors">
              <div class="selected-vendors-header">
                <h5>Vendor Terpilih ({{ selectedVendors.length }})</h5>
                <button
                  type="button"
                  class="btn btn-outline-secondary btn-sm"
                  @click="clearAllVendors"
                >
                  <i class="fas fa-times"></i> Hapus Semua
                </button>
              </div>

              <div class="selected-vendors-list">
                <div
                  v-for="vendor in selectedVendors"
                  :key="vendor.vendor_id"
                  class="vendor-item"
                >
                  <div class="vendor-info">
                    <div class="vendor-name">{{ vendor.name }}</div>
                    <div class="vendor-details">
                      <span class="vendor-code">
                        <i class="fas fa-tag"></i> {{ vendor.vendor_code }}
                      </span>
                      <span v-if="vendor.contact_person" class="vendor-contact">
                        <i class="fas fa-user"></i> {{ vendor.contact_person }}
                      </span>
                      <span v-if="vendor.email" class="vendor-email">
                        <i class="fas fa-envelope"></i> {{ vendor.email }}
                      </span>
                      <span v-if="vendor.phone" class="vendor-phone">
                        <i class="fas fa-phone"></i> {{ vendor.phone }}
                      </span>
                    </div>
                  </div>
                  <button
                    type="button"
                    class="btn btn-icon remove-vendor-btn"
                    @click="removeVendor(vendor)"
                    :title="`Remove ${vendor.name}`"
                  >
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>

              <div class="vendor-selection-summary">
                <i class="fas fa-check-circle text-success"></i>
                {{ selectedVendors.length }} vendor{{ selectedVendors.length > 1 ? 's' : '' }} akan menerima RFQ ini
              </div>
            </div>

            <div class="error-text" v-if="errors.vendors">{{ errors.vendors }}</div>
          </div>
        </div>
      </div>

      <!-- Items Selection Card -->
      <div class="info-card">
        <div class="card-header">
          <div class="header-with-actions">
            <h3>Item untuk RFQ <span class="required">*</span></h3>
            <div class="header-actions">
              <div class="toggle-switch">
                <input
                  type="checkbox"
                  id="select-all"
                  v-model="selectAll"
                  @change="toggleSelectAll"
                >
                <label for="select-all">Pilih Semua Item</label>
              </div>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div class="items-summary" v-if="purchaseRequisition.lines">
            <div class="summary-item">
              <span class="summary-label">Total Items:</span>
              <span class="summary-value">{{ purchaseRequisition.lines.length }}</span>
            </div>
            <div class="summary-item">
              <span class="summary-label">Dipilih:</span>
              <span class="summary-value selected">{{ getSelectedItemsCount() }}</span>
            </div>
            <div class="summary-item" v-if="getSelectedItemsCount() > 0">
              <span class="summary-label">Akan dikirim:</span>
              <span class="summary-value">{{ getSelectedItemsCount() }} item ke {{ selectedVendors.length }} vendor</span>
            </div>
          </div>

          <div class="table-container">
            <table class="items-table">
              <thead>
                <tr>
                  <th style="width: 50px">Pilih</th>
                  <th>Kode Item</th>
                  <th>Nama Item</th>
                  <th>Jumlah</th>
                  <th>Satuan</th>
                  <th>Tanggal Dibutuhkan</th>
                  <th>Catatan</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(line, index) in purchaseRequisition.lines"
                  :key="index"
                  :class="{ 'selected-row': selectedLines[index] }"
                >
                  <td class="text-center">
                    <input
                      type="checkbox"
                      v-model="selectedLines[index]"
                      @change="updateSelectAllState"
                    >
                  </td>
                  <td>
                    <span class="item-code">{{ line.item.item_code }}</span>
                  </td>
                  <td>
                    <div class="item-info">
                      <div class="item-name">{{ line.item.name }}</div>
                      <div class="item-description" v-if="line.item.description">
                        {{ line.item.description }}
                      </div>
                    </div>
                  </td>
                  <td class="text-right">
                    <span class="quantity">{{ formatNumber(line.quantity) }}</span>
                  </td>
                  <td>{{ line.unitOfMeasure?.name || '-' }}</td>
                  <td>
                    <span class="required-date">{{ formatDate(line.required_date) }}</span>
                  </td>
                  <td>
                    <span class="item-notes">{{ line.notes || '-' }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-if="errors.lines" class="error-text mt-2">{{ errors.lines }}</div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="action-buttons">
        <button
          type="button"
          class="btn btn-secondary"
          @click="goBack"
          :disabled="isSubmitting"
        >
          <i class="fas fa-arrow-left"></i> Kembali
        </button>
        <button
          type="button"
          class="btn btn-primary"
          @click="submitConversion"
          :disabled="isSubmitting || !isValidForm"
        >
          <i class="fas fa-spinner fa-spin" v-if="isSubmitting"></i>
          <i class="fas fa-exchange-alt" v-else></i>
          {{ isSubmitting ? 'Membuat RFQ...' : 'Buat RFQ' }}
        </button>
      </div>
    </div>

    <!-- Vendor List Modal -->
    <div v-if="showVendorsList" class="modal">
      <div class="modal-backdrop" @click="showVendorsList = false"></div>
      <div class="modal-content">
        <div class="modal-header">
          <h2>Pilih Vendor untuk RFQ</h2>
          <button class="close-btn" @click="showVendorsList = false">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="vendor-search-modal">
            <i class="fas fa-search search-icon"></i>
            <input
              type="text"
              placeholder="Cari vendor berdasarkan nama, kode, email, atau contact person..."
              v-model="vendorSearchQuery"
              @input="searchVendors"
              class="form-control"
            >
          </div>

          <div class="modal-vendor-stats" v-if="vendors.length > 0">
            <div class="stat-item">
              <span class="stat-label">Total Vendor:</span>
              <span class="stat-value">{{ vendors.length }}</span>
            </div>
            <div class="stat-item">
              <span class="stat-label">Ditampilkan:</span>
              <span class="stat-value">{{ filteredVendors.length }}</span>
            </div>
            <div class="stat-item">
              <span class="stat-label">Terpilih:</span>
              <span class="stat-value selected">{{ tempSelectedVendors.length }}</span>
            </div>
          </div>

          <div v-if="loadingVendors" class="loading-vendors">
            <i class="fas fa-spinner fa-spin"></i> Memuat vendor...
          </div>

          <div v-else-if="vendors.length === 0" class="no-vendors">
            <div class="no-vendors-icon">
              <i class="fas fa-exclamation-circle"></i>
            </div>
            <p><strong>Tidak ada vendor aktif</strong></p>
            <p>Tidak ada vendor yang tersedia dalam sistem.</p>
          </div>

          <div v-else-if="filteredVendors.length === 0" class="no-vendors">
            <div class="no-vendors-icon">
              <i class="fas fa-search"></i>
            </div>
            <p><strong>Tidak ada vendor yang ditemukan</strong></p>
            <p>Coba ubah kata kunci pencarian Anda.</p>
            <button class="btn btn-outline-primary btn-sm" @click="vendorSearchQuery = ''">
              <i class="fas fa-times"></i> Hapus Pencarian
            </button>
          </div>

          <div v-else class="vendors-list">
            <div class="vendor-list-header">
              <button
                type="button"
                class="btn btn-outline-primary btn-sm"
                @click="selectAllFilteredVendors"
                :disabled="areAllFilteredSelected"
              >
                Pilih Semua ({{ filteredVendors.length }})
              </button>
              <button
                type="button"
                class="btn btn-outline-secondary btn-sm"
                @click="deselectAllVendors"
                :disabled="tempSelectedVendors.length === 0"
              >
                Hapus Semua
              </button>
            </div>

            <div class="vendor-list-container">
              <div
                v-for="vendor in filteredVendors"
                :key="vendor.vendor_id"
                class="vendor-list-item"
                :class="{ 'selected': isVendorSelected(vendor) }"
                @click="toggleVendor(vendor)"
              >
                <div class="vendor-list-info">
                  <div class="vendor-list-name">{{ vendor.name }}</div>
                  <div class="vendor-list-details">
                    <span class="vendor-list-code">
                      <i class="fas fa-tag"></i> {{ vendor.vendor_code }}
                    </span>
                    <span v-if="vendor.email" class="vendor-list-email">
                      <i class="fas fa-envelope"></i> {{ vendor.email }}
                    </span>
                    <span v-if="vendor.contact_person" class="vendor-list-contact">
                      <i class="fas fa-user"></i> {{ vendor.contact_person }}
                    </span>
                    <span v-if="vendor.phone" class="vendor-list-phone">
                      <i class="fas fa-phone"></i> {{ vendor.phone }}
                    </span>
                  </div>
                </div>
                <div class="vendor-list-checkbox">
                  <i
                    class="fas"
                    :class="isVendorSelected(vendor) ? 'fa-check-circle text-primary' : 'fa-circle text-muted'"
                  ></i>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button
            type="button"
            class="btn btn-secondary"
            @click="cancelVendorSelection"
          >
            Batal
          </button>
          <button
            type="button"
            class="btn btn-primary"
            @click="confirmVendors"
            :disabled="tempSelectedVendors.length === 0"
          >
            <i class="fas fa-check"></i>
            Pilih {{ tempSelectedVendors.length }} Vendor{{ tempSelectedVendors.length > 1 ? 's' : '' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Confirmation Modal -->
    <div v-if="showConfirmationModal" class="modal">
      <div class="modal-backdrop" @click="showConfirmationModal = false"></div>
      <div class="modal-content modal-sm">
        <div class="modal-header">
          <h2>Konfirmasi Konversi PR ke RFQ</h2>
          <button class="close-btn" @click="showConfirmationModal = false">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <p>
            Apakah Anda yakin ingin mengkonversi permintaan pembelian ini menjadi RFQ?
          </p>
          
          <div class="confirmation-summary">
            <div class="summary-section">
              <h4>Detail Konversi:</h4>
              <ul>
                <li><strong>PR Number:</strong> {{ purchaseRequisition.pr_number }}</li>
                <li><strong>Vendor terpilih:</strong> {{ selectedVendors.length }} vendor</li>
                <li><strong>Item terpilih:</strong> {{ getSelectedItemsCount() }} dari {{ purchaseRequisition.lines?.length || 0 }} item</li>
                <li><strong>Tanggal RFQ:</strong> {{ formatDate(rfqData.rfq_date) }}</li>
                <li v-if="rfqData.validity_date"><strong>Berlaku hingga:</strong> {{ formatDate(rfqData.validity_date) }}</li>
              </ul>
            </div>

            <div class="summary-section" v-if="selectedVendors.length > 0">
              <h4>Vendor yang akan menerima RFQ:</h4>
              <div class="vendor-summary-list">
                <div v-for="vendor in selectedVendors" :key="vendor.vendor_id" class="vendor-summary-item">
                  <span class="vendor-summary-name">{{ vendor.name }}</span>
                  <span class="vendor-summary-code">({{ vendor.vendor_code }})</span>
                </div>
              </div>
            </div>

            <div class="warning-note">
              <i class="fas fa-info-circle"></i>
              <p>Setelah RFQ dibuat, vendor yang dipilih akan otomatis terpilih di halaman pengiriman RFQ.</p>
            </div>
          </div>

          <div class="form-actions">
            <button
              type="button"
              class="btn btn-secondary"
              @click="showConfirmationModal = false"
              :disabled="isSubmitting"
            >
              Batal
            </button>
            <button
              type="button"
              class="btn btn-primary"
              @click="confirmConversion"
              :disabled="isSubmitting"
            >
              <i class="fas fa-spinner fa-spin" v-if="isSubmitting"></i>
              <i class="fas fa-check" v-else></i>
              {{ isSubmitting ? 'Membuat...' : 'Konfirmasi' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'ConvertToRFQ',
  props: {
    id: {
      type: [Number, String],
      required: true
    }
  },
  data() {
    return {
      purchaseRequisition: {},
      loading: true,
      error: null,
      rfqData: {
        rfq_date: new Date().toISOString().split('T')[0],
        validity_date: null,
        notes: ''
      },
      selectedLines: [],
      selectAll: false,
      vendors: [],
      loadingVendors: false,
      selectedVendors: [],
      tempSelectedVendors: [],
      vendorSearchQuery: '',
      isSubmitting: false,
      errors: {},
      showVendorsList: false,
      showConfirmationModal: false
    };
  },
  computed: {
    requesterName() {
      return this.purchaseRequisition.requester?.name || '-';
    },
    isValidForm() {
      // Check if at least one line is selected
      const hasSelectedLines = this.selectedLines.some(selected => selected);

      // Check if at least one vendor is selected
      const hasSelectedVendors = this.selectedVendors.length > 0;

      // Check if all required fields are filled
      const hasRequiredFields = !!this.rfqData.rfq_date;

      return hasSelectedLines && hasSelectedVendors && hasRequiredFields;
    },
    filteredVendors() {
      if (!this.vendorSearchQuery.trim()) {
        return this.vendors;
      }

      const searchTerm = this.vendorSearchQuery.toLowerCase();
      return this.vendors.filter(vendor =>
        vendor.name.toLowerCase().includes(searchTerm) ||
        vendor.vendor_code.toLowerCase().includes(searchTerm) ||
        (vendor.email && vendor.email.toLowerCase().includes(searchTerm)) ||
        (vendor.contact_person && vendor.contact_person.toLowerCase().includes(searchTerm)) ||
        (vendor.phone && vendor.phone.toLowerCase().includes(searchTerm))
      );
    },
    areAllFilteredSelected() {
      if (this.filteredVendors.length === 0) return false;
      return this.filteredVendors.every(vendor => 
        this.tempSelectedVendors.some(selected => selected.vendor_id === vendor.vendor_id)
      );
    }
  },
  created() {
    this.fetchPurchaseRequisition();
    this.fetchVendors();
  },
  methods: {
    async fetchPurchaseRequisition() {
      this.loading = true;
      this.error = null;

      try {
        const response = await axios.get(`/purchase-requisitions/${this.id}`);
        this.purchaseRequisition = response.data.data;

        // Initialize selectedLines array with false for each line
        this.selectedLines = Array(this.purchaseRequisition.lines.length).fill(false);

        // Check if PR is in a valid state for conversion
        if (this.purchaseRequisition.status !== 'approved') {
          this.error = `PR ini tidak dalam status 'approved'. Status saat ini: ${this.purchaseRequisition.status}`;
        }
      } catch (error) {
        console.error('Error fetching purchase requisition:', error);
        this.error = 'Gagal memuat data PR. Silakan coba lagi.';
      } finally {
        this.loading = false;
      }
    },

    async fetchVendors() {
      this.loadingVendors = true;

      try {
        const response = await axios.get('/vendors?status=active');
        this.vendors = response.data.data.data || response.data.data || [];
        console.log('Vendors loaded:', this.vendors.length);
      } catch (error) {
        console.error('Error fetching vendors:', error);
        this.vendors = [];
      } finally {
        this.loadingVendors = false;
      }
    },

    getStatusClass(status) {
      switch (status) {
        case 'draft': return 'status-draft';
        case 'pending': return 'status-pending';
        case 'approved': return 'status-approved';
        case 'rejected': return 'status-rejected';
        case 'canceled': return 'status-canceled';
        default: return '';
      }
    },

    formatDate(dateString) {
      if (!dateString) return '-';

      const options = { year: 'numeric', month: 'short', day: 'numeric' };
      return new Date(dateString).toLocaleDateString('id-ID', options);
    },

    formatNumber(value) {
      if (value === null || value === undefined) return '-';
      return new Intl.NumberFormat('id-ID').format(value);
    },

    goBack() {
      this.$router.push(`/purchasing/requisitions/${this.id}`);
    },

    toggleSelectAll() {
      for (let i = 0; i < this.selectedLines.length; i++) {
        this.selectedLines[i] = this.selectAll;
      }
    },

    updateSelectAllState() {
      this.selectAll = this.selectedLines.every(selected => selected);
    },

    searchVendors() {
      // This is handled by the computed property filteredVendors
    },

    isVendorSelected(vendor) {
      return this.tempSelectedVendors.some(v => v.vendor_id === vendor.vendor_id);
    },

    toggleVendor(vendor) {
      if (this.isVendorSelected(vendor)) {
        this.tempSelectedVendors = this.tempSelectedVendors.filter(v => v.vendor_id !== vendor.vendor_id);
      } else {
        this.tempSelectedVendors.push(vendor);
      }
    },

    selectAllFilteredVendors() {
      this.filteredVendors.forEach(vendor => {
        if (!this.isVendorSelected(vendor)) {
          this.tempSelectedVendors.push(vendor);
        }
      });
    },

    deselectAllVendors() {
      this.tempSelectedVendors = [];
    },

    confirmVendors() {
      this.selectedVendors = [...this.tempSelectedVendors];
      this.showVendorsList = false;
      this.vendorSearchQuery = ''; // Clear search when closing modal
      
      // Clear any vendor-related errors
      if (this.errors.vendors) {
        delete this.errors.vendors;
      }
    },

    cancelVendorSelection() {
      // Reset temp selection to current selection
      this.tempSelectedVendors = [...this.selectedVendors];
      this.showVendorsList = false;
      this.vendorSearchQuery = ''; // Clear search when closing modal
    },

    removeVendor(vendor) {
      this.selectedVendors = this.selectedVendors.filter(v => v.vendor_id !== vendor.vendor_id);
      this.tempSelectedVendors = this.tempSelectedVendors.filter(v => v.vendor_id !== vendor.vendor_id);
    },

    clearAllVendors() {
      this.selectedVendors = [];
      this.tempSelectedVendors = [];
    },

    getSelectedItemsCount() {
      return this.selectedLines.filter(selected => selected).length;
    },

    validateForm() {
      this.errors = {};

      // Validate RFQ date
      if (!this.rfqData.rfq_date) {
        this.errors.rfq_date = 'Tanggal RFQ wajib diisi';
      }

      // Validate validity date
      if (this.rfqData.validity_date && this.rfqData.validity_date < this.rfqData.rfq_date) {
        this.errors.validity_date = 'Tanggal berlaku harus setelah atau sama dengan tanggal RFQ';
      }

      // Validate vendor selection
      if (this.selectedVendors.length === 0) {
        this.errors.vendors = 'Pilih minimal satu vendor untuk RFQ';
      }

      // Validate item selection
      if (!this.selectedLines.some(selected => selected)) {
        this.errors.lines = 'Pilih minimal satu item untuk RFQ';
      }

      return Object.keys(this.errors).length === 0;
    },

    submitConversion() {
      if (!this.validateForm()) {
        return;
      }

      // Show confirmation modal
      this.showConfirmationModal = true;
    },

    async confirmConversion() {
      this.isSubmitting = true;
      this.showConfirmationModal = false;

      try {
        // Prepare the RFQ data
        const lines = [];
        this.purchaseRequisition.lines.forEach((line, index) => {
          if (this.selectedLines[index]) {
            lines.push({
              item_id: line.item_id,
              quantity: line.quantity,
              uom_id: line.uom_id,
              required_date: line.required_date
            });
          }
        });

        const requestData = {
          pr_id: this.purchaseRequisition.pr_id,
          rfq_date: this.rfqData.rfq_date,
          validity_date: this.rfqData.validity_date,
          notes: this.rfqData.notes,
          vendors: this.selectedVendors.map(v => v.vendor_id),
          lines: lines,
          reference_document: `PR-${this.purchaseRequisition.pr_number}`
        };

        console.log('Sending RFQ creation request:', requestData);

        const response = await axios.post('/request-for-quotations', requestData);

        console.log('RFQ creation response:', response.data);

        // Success! Redirect to RFQ detail or list
        this.$router.push({
          path: '/purchasing/rfqs',
          query: {
            message: `RFQ berhasil dibuat dari PR ${this.purchaseRequisition.pr_number}`,
            type: 'success'
          }
        });

      } catch (error) {
        console.error('Error creating RFQ:', error);

        if (error.response && error.response.data) {
          if (error.response.data.errors) {
            this.errors = error.response.data.errors;
          } else {
            this.error = error.response.data.message || 'Gagal membuat RFQ. Silakan coba lagi.';
          }
        } else {
          this.error = 'Gagal membuat RFQ. Silakan coba lagi.';
        }

        this.isSubmitting = false;
      }
    },

    // Open vendor selection modal with current selection
    openVendorModal() {
      this.tempSelectedVendors = [...this.selectedVendors];
      this.showVendorsList = true;
    }
  },

  watch: {
    // Clear search when modal opens
    showVendorsList(newVal) {
      if (newVal) {
        this.tempSelectedVendors = [...this.selectedVendors];
      } else {
        this.vendorSearchQuery = '';
      }
    }
  }
};
</script>

<style scoped>
/* Existing styles remain the same, plus additional enhancements */

.convert-rfq-page {
  padding: 20px;
  max-width: 100%;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.title {
  font-size: 1.5rem;
  font-weight: 600;
  margin: 0;
}

.status-badge {
  padding: 6px 12px;
  border-radius: 4px;
  font-weight: 600;
  font-size: 0.875rem;
  text-transform: uppercase;
}

.status-draft { background-color: var(--gray-200); color: var(--gray-700); }
.status-pending { background-color: #ffecb3; color: #8b6d00; }
.status-approved { background-color: #d0f0c0; color: #38761d; }
.status-rejected { background-color: #ffcdd2; color: #c62828; }
.status-canceled { background-color: var(--gray-300); color: var(--gray-600); }

.info-card {
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  margin-bottom: 24px;
  overflow: hidden;
}

.card-header {
  padding: 16px 20px;
  border-bottom: 1px solid var(--gray-200);
  background-color: var(--gray-50);
}

.header-with-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h3 {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 600;
}

.card-body {
  padding: 20px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 16px;
  margin-bottom: 16px;
}

.info-item {
  margin-bottom: 8px;
}

.label {
  font-size: 0.875rem;
  color: var(--gray-500);
  margin-bottom: 4px;
  font-weight: 500;
}

.value {
  font-size: 1rem;
  color: var(--gray-800);
  font-weight: 500;
}

.form-row {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 16px;
}

.form-group {
  flex: 1 1 300px;
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: var(--gray-700);
}

.required {
  color: #e53e3e;
}

.form-control {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid var(--gray-300);
  border-radius: 4px;
  font-size: 1rem;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.form-control:focus {
  border-color: var(--primary-color);
  outline: none;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.is-invalid {
  border-color: #e53e3e;
}

.error-text {
  color: #e53e3e;
  font-size: 0.875rem;
  margin-top: 4px;
}

.help-text {
  font-size: 0.8rem;
  color: var(--gray-500);
  margin-top: 4px;
}

.mt-2 {
  margin-top: 8px;
}

/* Enhanced Vendor Selection Styles */
.vendor-selection {
  margin-top: 20px;
  border: 2px dashed var(--gray-200);
  border-radius: 8px;
  padding: 20px;
  transition: border-color 0.3s;
}

.vendor-selection:focus-within {
  border-color: var(--primary-color);
}

.vendor-selection-header {
  margin-bottom: 16px;
}

.vendor-selection-header h4 {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 4px;
}

.vendor-selection-info {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.85rem;
  color: var(--primary-color);
  background: var(--primary-bg);
  padding: 8px 12px;
  border-radius: 4px;
}

.vendor-search {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
}

.search-input {
  position: relative;
  flex: 1;
}

.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--gray-500);
}

.search-input input {
  padding-left: 36px;
}

.add-vendor-btn {
  white-space: nowrap;
}

.empty-vendors {
  padding: 40px 20px;
  text-align: center;
  background-color: var(--gray-50);
  border-radius: 8px;
  color: var(--gray-600);
}

.empty-icon {
  font-size: 2.5rem;
  color: var(--gray-400);
  margin-bottom: 16px;
}

.empty-vendors p {
  margin: 8px 0;
}

.selected-vendors-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.selected-vendors-header h5 {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
  color: var(--gray-800);
}

.selected-vendors-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 16px;
}

.vendor-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  background-color: white;
  border: 1px solid var(--gray-200);
  border-radius: 8px;
  transition: all 0.2s;
}

.vendor-item:hover {
  border-color: var(--primary-color);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.vendor-info {
  flex: 1;
}

.vendor-name {
  font-weight: 600;
  color: var(--gray-800);
  margin-bottom: 6px;
}

.vendor-details {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  font-size: 0.875rem;
  color: var(--gray-600);
}

.vendor-details span {
  display: flex;
  align-items: center;
  gap: 4px;
}

.vendor-selection-summary {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px;
  background: var(--success-bg);
  color: var(--success-color);
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 500;
}

.remove-vendor-btn {
  color: var(--gray-500);
  background: none;
  border: none;
  cursor: pointer;
  padding: 8px;
  border-radius: 4px;
  transition: all 0.2s;
}

.remove-vendor-btn:hover {
  background-color: #fee;
  color: #dc2626;
}

/* Items Table Enhancements */
.items-summary {
  display: flex;
  gap: 24px;
  margin-bottom: 20px;
  padding: 12px;
  background: var(--gray-50);
  border-radius: 6px;
  font-size: 0.875rem;
}

.summary-item {
  display: flex;
  gap: 4px;
}

.summary-label {
  color: var(--gray-600);
  font-weight: 500;
}

.summary-value {
  font-weight: 600;
  color: var(--gray-800);
}

.summary-value.selected {
  color: var(--success-color);
}

.table-container {
  overflow-x: auto;
  border: 1px solid var(--gray-200);
  border-radius: 6px;
}

.items-table {
  width: 100%;
  border-collapse: collapse;
}

.items-table th,
.items-table td {
  padding: 12px 16px;
  text-align: left;
  border-bottom: 1px solid var(--gray-200);
}

.items-table th {
  background-color: var(--gray-50);
  font-weight: 600;
  color: var(--gray-700);
}

.items-table tbody tr:hover {
  background-color: var(--gray-50);
}

.selected-row {
  background-color: #e6f2ff !important;
}

.item-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.item-name {
  font-weight: 500;
  color: var(--gray-800);
}

.item-description {
  font-size: 0.85rem;
  color: var(--gray-600);
}

.item-code {
  font-family: monospace;
  font-weight: 500;
  color: var(--primary-color);
}

.quantity {
  font-weight: 600;
  color: var(--gray-800);
}

.required-date {
  font-size: 0.9rem;
  color: var(--gray-700);
}

.item-notes {
  font-size: 0.85rem;
  color: var(--gray-600);
}

.text-right {
  text-align: right;
}

.text-center {
  text-align: center;
}

.toggle-switch {
  display: flex;
  align-items: center;
}

.toggle-switch input[type="checkbox"] {
  margin-right: 8px;
}

/* Modal Enhancements */
.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 50;
  display: flex;
  justify-content: center;
  align-items: center;
}

.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 50;
}

.modal-content {
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 700px;
  z-index: 60;
  overflow: hidden;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}

.modal-sm {
  max-width: 500px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid var(--gray-200);
  background: var(--gray-50);
}

.modal-header h2 {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0;
  color: var(--gray-800);
}

.close-btn {
  background: none;
  border: none;
  color: var(--gray-500);
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  transition: background-color 0.2s;
}

.close-btn:hover {
  background: var(--gray-200);
  color: var(--gray-700);
}

.modal-body {
  padding: 20px;
  overflow-y: auto;
  flex: 1;
}

.modal-footer {
  padding: 16px 20px;
  border-top: 1px solid var(--gray-200);
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  background: var(--gray-50);
}

.vendor-search-modal {
  position: relative;
  margin-bottom: 16px;
}

.modal-vendor-stats {
  display: flex;
  gap: 20px;
  margin-bottom: 16px;
  padding: 12px;
  background: var(--gray-50);
  border-radius: 6px;
  font-size: 0.875rem;
}

.stat-item {
  display: flex;
  gap: 4px;
}

.stat-label {
  color: var(--gray-600);
}

.stat-value {
  font-weight: 600;
  color: var(--gray-800);
}

.stat-value.selected {
  color: var(--success-color);
}

.loading-vendors,
.no-vendors {
  padding: 40px 20px;
  text-align: center;
  color: var(--gray-600);
}

.no-vendors-icon {
  font-size: 2.5rem;
  color: var(--gray-400);
  margin-bottom: 16px;
}

.no-vendors p {
  margin: 8px 0;
}

.vendor-list-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 12px;
}

.vendors-list {
  border: 1px solid var(--gray-200);
  border-radius: 6px;
  overflow: hidden;
}

.vendor-list-container {
  max-height: 400px;
  overflow-y: auto;
}

.vendor-list-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  border-bottom: 1px solid var(--gray-200);
  cursor: pointer;
  transition: background-color 0.2s;
}

.vendor-list-item:last-child {
  border-bottom: none;
}

.vendor-list-item:hover {
  background-color: var(--gray-50);
}

.vendor-list-item.selected {
  background-color: var(--primary-bg);
  border-color: var(--primary-color);
}

.vendor-list-info {
  flex: 1;
}

.vendor-list-name {
  font-weight: 600;
  margin-bottom: 4px;
  color: var(--gray-800);
}

.vendor-list-details {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  font-size: 0.875rem;
  color: var(--gray-600);
}

.vendor-list-details span {
  display: flex;
  align-items: center;
  gap: 4px;
}

.vendor-list-checkbox {
  font-size: 1.25rem;
  color: var(--gray-400);
}

.vendor-list-item.selected .vendor-list-checkbox {
  color: var(--primary-color);
}

/* Confirmation Modal Enhancements */
.confirmation-summary {
  background: var(--gray-50);
  padding: 16px;
  border-radius: 6px;
  margin: 16px 0;
}

.summary-section {
  margin-bottom: 16px;
}

.summary-section:last-child {
  margin-bottom: 0;
}

.summary-section h4 {
  margin: 0 0 8px 0;
  font-size: 1rem;
  color: var(--gray-800);
}

.summary-section ul {
  margin: 8px 0 0 20px;
  color: var(--gray-700);
}

.summary-section li {
  margin-bottom: 4px;
}

.vendor-summary-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 8px;
}

.vendor-summary-item {
  display: flex;
  gap: 8px;
  padding: 8px 12px;
  background: white;
  border-radius: 4px;
  border: 1px solid var(--gray-200);
}

.vendor-summary-name {
  font-weight: 500;
  color: var(--gray-800);
}

.vendor-summary-code {
  color: var(--gray-600);
  font-size: 0.9rem;
}

.warning-note {
  display: flex;
  gap: 8px;
  padding: 12px;
  background: var(--primary-bg);
  border: 1px solid var(--primary-color);
  border-radius: 6px;
  font-size: 0.9rem;
  color: var(--primary-color);
}

.warning-note i {
  flex-shrink: 0;
  margin-top: 2px;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid var(--gray-200);
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 20px;
}

.btn {
  padding: 10px 16px;
  border-radius: 4px;
  font-weight: 500;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
}

.btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 0.875rem;
}

.btn-secondary {
  background-color: var(--gray-200);
  color: var(--gray-700);
}

.btn-secondary:hover:not(:disabled) {
  background-color: var(--gray-300);
}

.btn-primary {
  background-color: var(--primary-color);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: var(--primary-dark);
}

.btn-outline-primary {
  background-color: transparent;
  color: var(--primary-color);
  border: 1px solid var(--primary-color);
}

.btn-outline-primary:hover:not(:disabled) {
  background-color: var(--primary-color);
  color: white;
}

.btn-outline-secondary {
  background-color: transparent;
  color: var(--gray-600);
  border: 1px solid var(--gray-300);
}

.btn-outline-secondary:hover:not(:disabled) {
  background-color: var(--gray-100);
}

.btn-icon {
  padding: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Loading and Error States */
.loading-indicator,
.error-message {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 40px 0;
  font-size: 1rem;
}

.loading-indicator i,
.error-message i {
  margin-right: 8px;
}

.error-message {
  color: #c62828;
  background: #ffebee;
  border: 1px solid #ffcdd2;
  border-radius: 6px;
  padding: 20px;
  margin: 20px 0;
}

/* Utility Classes */
.text-success { color: var(--success-color) !important; }
.text-primary { color: var(--primary-color) !important; }
.text-muted { color: var(--gray-500) !important; }

/* Responsive Design */
@media (max-width: 768px) {
  .convert-rfq-page {
    padding: 16px;
  }
  
  .form-row {
    flex-direction: column;
    gap: 0;
  }

  .info-grid {
    grid-template-columns: 1fr;
  }

  .vendor-search {
    flex-direction: column;
  }

  .vendor-details,
  .vendor-list-details {
    flex-direction: column;
    gap: 4px;
  }

  .action-buttons,
  .form-actions {
    flex-direction: column;
    gap: 12px;
  }

  .btn {
    width: 100%;
    justify-content: center;
  }

  .modal-content {
    width: 95%;
    max-height: 80vh;
  }

  .modal-vendor-stats {
    flex-direction: column;
    gap: 8px;
  }

  .vendor-list-header {
    flex-direction: column;
    gap: 8px;
  }

  .selected-vendors-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }

  .items-summary {
    flex-direction: column;
    gap: 8px;
  }
}

@media (max-width: 480px) {
  .page-header {
    flex-direction: column;
    gap: 12px;
    align-items: flex-start;
  }
  
  .vendor-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  
  .remove-vendor-btn {
    align-self: flex-end;
  }
}
</style>