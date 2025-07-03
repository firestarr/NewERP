<!-- Full Updated RFQSend.vue -->
<template>
  <div class="rfq-send-container">
    <div class="page-header">
      <h1>Send RFQ to Vendors</h1>
      <div class="header-actions">
        <router-link :to="`/purchasing/rfqs/${rfqId}`" class="btn btn-outline">
          <i class="fas fa-arrow-left"></i> Back to RFQ
        </router-link>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-indicator">
      <i class="fas fa-spinner fa-spin"></i> Loading RFQ details...
    </div>

    <!-- Error States -->
    <div v-else-if="!rfq" class="error-state">
      <div class="error-icon">
        <i class="fas fa-exclamation-circle"></i>
      </div>
      <h3>Request for Quotation Not Found</h3>
      <p>The RFQ you are trying to send does not exist or has been deleted.</p>
      <router-link to="/purchasing/rfqs" class="btn btn-primary">
        Go Back to RFQ List
      </router-link>
    </div>

    <div v-else-if="rfq.status !== 'draft'" class="error-state">
      <div class="error-icon">
        <i class="fas fa-ban"></i>
      </div>
      <h3>RFQ Cannot Be Sent</h3>
      <p>This RFQ has already been sent or is in a state that cannot be modified.</p>
      <p><strong>Current Status:</strong> {{ capitalizeFirstLetter(rfq.status) }}</p>
      <router-link :to="`/purchasing/rfqs/${rfqId}`" class="btn btn-primary">
        View RFQ Details
      </router-link>
    </div>

    <!-- Main Content -->
    <div v-else class="rfq-send-content">
      <!-- RFQ Information Card -->
      <div class="detail-card">
        <div class="card-header">
          <h2 class="card-title">RFQ Information</h2>
          <div class="status-badge status-draft">
            {{ capitalizeFirstLetter(rfq.status) }}
          </div>
        </div>

        <div class="card-body">
          <div class="detail-row">
            <div class="detail-group">
              <label>RFQ Number</label>
              <div class="detail-value">{{ rfq.rfq_number }}</div>
            </div>
            
            <div class="detail-group">
              <label>RFQ Date</label>
              <div class="detail-value">{{ formatDate(rfq.rfq_date) }}</div>
            </div>
            
            <div class="detail-group">
              <label>Validity Date</label>
              <div class="detail-value">{{ formatDate(rfq.validity_date) || 'Not specified' }}</div>
            </div>

            <div class="detail-group">
              <label>Items Count</label>
              <div class="detail-value">{{ rfq.lines ? rfq.lines.length : 0 }} items</div>
            </div>
          </div>

          <div class="detail-group" v-if="rfq.notes">
            <label>Notes</label>
            <div class="detail-value">{{ rfq.notes }}</div>
          </div>

          <div class="detail-group" v-if="rfq.reference_document">
            <label>Reference Document</label>
            <div class="detail-value">{{ rfq.reference_document }}</div>
          </div>
        </div>
      </div>

      <!-- Pre-selected Vendors Info Card -->
      <div class="detail-card" v-if="preSelectedVendors.length > 0">
        <div class="card-header">
          <h3 class="card-title">
            <i class="fas fa-info-circle text-info"></i>
            Pre-selected Vendors
          </h3>
          <div class="vendor-count-badge">
            {{ preSelectedVendors.length }} vendor{{ preSelectedVendors.length > 1 ? 's' : '' }}
          </div>
        </div>

        <div class="card-body">
          <p class="info-text">
            <i class="fas fa-lightbulb"></i>
            These vendors were selected during PR to RFQ conversion. You can modify the selection below if needed.
          </p>
          
          <div class="pre-selected-vendors-list">
            <div 
              v-for="vendor in preSelectedVendors" 
              :key="vendor.vendor_id"
              class="pre-selected-vendor-item"
            >
              <div class="vendor-info">
                <div class="vendor-name">{{ vendor.name }}</div>
                <div class="vendor-details">
                  <span class="vendor-code">{{ vendor.vendor_code }}</span>
                  <span v-if="vendor.contact_person" class="vendor-contact">
                    <i class="fas fa-user"></i> {{ vendor.contact_person }}
                  </span>
                  <span v-if="vendor.email" class="vendor-email">
                    <i class="fas fa-envelope"></i> {{ vendor.email }}
                  </span>
                </div>
              </div>
              <div class="selection-status">
                <i class="fas fa-check-circle text-success"></i>
                Pre-selected
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Vendor Selection Card -->
      <div class="detail-card">
        <div class="card-header">
          <h3 class="card-title">Select Vendors to Send RFQ</h3>
          <div class="selection-summary">
            {{ vendorSelectionSummary }}
            <span v-if="isSelectionChanged" class="changed-indicator">
              <i class="fas fa-edit text-warning"></i> Modified from original
            </span>
          </div>
        </div>

        <div class="card-body">
          <!-- Loading Vendors -->
          <div v-if="loadingVendors" class="loading-vendors">
            <i class="fas fa-spinner fa-spin"></i> Loading vendors...
          </div>

          <!-- Error Loading Vendors -->
          <div v-else-if="vendorsError" class="error-loading-vendors">
            <i class="fas fa-exclamation-triangle text-danger"></i>
            <p>{{ vendorsError }}</p>
            <button class="btn btn-outline-primary btn-sm" @click="loadVendors">
              <i class="fas fa-refresh"></i> Retry
            </button>
          </div>

          <!-- Vendor Controls -->
          <div v-else class="vendor-controls">
            <div class="control-row">
              <div class="search-input">
                <i class="fas fa-search search-icon"></i>
                <input 
                  type="text" 
                  v-model="vendorSearchQuery"
                  placeholder="Search vendors by name, code, or email..."
                  class="form-control"
                >
              </div>
              
              <div class="bulk-actions">
                <button 
                  type="button" 
                  class="btn btn-outline-primary btn-sm"
                  @click="selectAllFilteredVendors"
                  :disabled="filteredVendors.length === 0 || areAllFilteredSelected"
                >
                  Select All {{ filteredVendors.length > 0 ? `(${filteredVendors.length})` : '' }}
                </button>
                <button 
                  type="button" 
                  class="btn btn-outline-secondary btn-sm"
                  @click="deselectAllVendors"
                  :disabled="selectedVendors.length === 0"
                >
                  Deselect All
                </button>
                <button 
                  type="button" 
                  class="btn btn-outline-info btn-sm"
                  @click="resetToPreSelected"
                  :disabled="preSelectedVendors.length === 0 || !isSelectionChanged"
                >
                  Reset to Pre-selected
                </button>
              </div>
            </div>

            <!-- Vendor Selection Summary -->
            <div class="selection-stats" v-if="vendors.length > 0">
              <div class="stat-item">
                <span class="stat-label">Total Vendors:</span>
                <span class="stat-value">{{ vendors.length }}</span>
              </div>
              <div class="stat-item">
                <span class="stat-label">Selected:</span>
                <span class="stat-value selected">{{ selectedVendors.length }}</span>
              </div>
              <div class="stat-item" v-if="preSelectedVendors.length > 0">
                <span class="stat-label">Pre-selected:</span>
                <span class="stat-value pre-selected">{{ preSelectedVendors.length }}</span>
              </div>
              <div class="stat-item" v-if="filteredVendors.length !== vendors.length">
                <span class="stat-label">Showing:</span>
                <span class="stat-value">{{ filteredVendors.length }}</span>
              </div>
            </div>

            <!-- Vendor List -->
            <div class="vendors-list" v-if="filteredVendors.length > 0">
              <div 
                v-for="vendor in filteredVendors" 
                :key="vendor.vendor_id"
                class="vendor-item"
                :class="{ 
                  'selected': isVendorSelected(vendor.vendor_id),
                  'pre-selected': isPreSelected(vendor.vendor_id),
                  'newly-added': isNewlyAdded(vendor.vendor_id)
                }"
                @click="toggleVendorSelection(vendor.vendor_id)"
              >
                <div class="vendor-checkbox">
                  <input 
                    type="checkbox" 
                    :checked="isVendorSelected(vendor.vendor_id)"
                    @change="toggleVendorSelection(vendor.vendor_id)"
                    @click.stop
                  >
                </div>
                
                <div class="vendor-info">
                  <div class="vendor-name">
                    {{ vendor.name }}
                    <span v-if="isPreSelected(vendor.vendor_id)" class="pre-selected-badge">
                      Pre-selected
                    </span>
                    <span v-else-if="isNewlyAdded(vendor.vendor_id)" class="newly-added-badge">
                      Newly Added
                    </span>
                  </div>
                  <div class="vendor-details">
                    <span class="vendor-code">{{ vendor.vendor_code }}</span>
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

                <div class="vendor-status">
                  <i 
                    v-if="isVendorSelected(vendor.vendor_id)" 
                    class="fas fa-check-circle text-success"
                  ></i>
                  <i 
                    v-else 
                    class="far fa-circle text-muted"
                  ></i>
                </div>
              </div>
            </div>

            <!-- No Vendors Found -->
            <div v-else-if="vendors.length > 0" class="no-vendors">
              <i class="fas fa-search"></i>
              <p>No vendors found matching your search criteria.</p>
              <button class="btn btn-outline-secondary btn-sm" @click="vendorSearchQuery = ''">
                Clear Search
              </button>
            </div>

            <!-- No Vendors Available -->
            <div v-else class="no-vendors">
              <i class="fas fa-exclamation-circle"></i>
              <p>No active vendors are available in the system.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="action-buttons">
        <router-link 
          :to="`/purchasing/rfqs/${rfqId}`" 
          class="btn btn-secondary"
          :class="{ 'disabled': isSending }"
        >
          Cancel
        </router-link>
        
        <button 
          type="button" 
          class="btn btn-primary"
          @click="confirmSendRFQ"
          :disabled="selectedVendors.length === 0 || isSending"
        >
          <i v-if="isSending" class="fas fa-spinner fa-spin"></i>
          <i v-else class="fas fa-paper-plane"></i>
          {{ isSending ? 'Sending...' : `Send RFQ to ${selectedVendors.length} Vendor${selectedVendors.length > 1 ? 's' : ''}` }}
        </button>
      </div>
    </div>

    <!-- Confirmation Modal -->
    <div v-if="showConfirmationModal" class="modal">
      <div class="modal-backdrop" @click="showConfirmationModal = false"></div>
      <div class="modal-content">
        <div class="modal-header">
          <h2>
            <i class="fas fa-paper-plane text-primary"></i>
            Confirm Send RFQ
          </h2>
          <button class="close-btn" @click="showConfirmationModal = false">
            <i class="fas fa-times"></i>
          </button>
        </div>
        
        <div class="modal-body">
          <p>Are you sure you want to send this RFQ to the following vendors?</p>
          
          <div class="selected-vendors-summary">
            <div 
              v-for="vendor in getSelectedVendorObjects()" 
              :key="vendor.vendor_id"
              class="vendor-summary-item"
            >
              <div class="vendor-info">
                <div class="vendor-name">{{ vendor.name }}</div>
                <div class="vendor-code">{{ vendor.vendor_code }}</div>
              </div>
              <div class="vendor-type">
                <span v-if="isPreSelected(vendor.vendor_id)" class="pre-selected-badge">
                  Pre-selected
                </span>
                <span v-else class="newly-added-badge">
                  Newly Added
                </span>
              </div>
            </div>
          </div>

          <div class="confirmation-stats">
            <p><strong>Total Vendors:</strong> {{ selectedVendors.length }}</p>
            <p><strong>RFQ Items:</strong> {{ rfq.lines ? rfq.lines.length : 0 }}</p>
            <p v-if="isSelectionChanged" class="warning-text">
              <i class="fas fa-info-circle"></i>
              Note: You have modified the original vendor selection.
            </p>
          </div>
        </div>
        
        <div class="modal-actions">
          <button 
            type="button" 
            class="btn btn-secondary" 
            @click="showConfirmationModal = false"
            :disabled="isSending"
          >
            Cancel
          </button>
          <button
            type="button"
            class="btn btn-primary"
            @click="sendRFQ"
            :disabled="isSending"
          >
            <i v-if="isSending" class="fas fa-spinner fa-spin"></i>
            Confirm Send
          </button>
        </div>
      </div>
    </div>

    <!-- Success Modal -->
    <div v-if="showSuccessModal" class="modal">
      <div class="modal-backdrop" @click="showSuccessModal = false"></div>
      <div class="modal-content success-modal">
        <div class="modal-header">
          <h2>
            <i class="fas fa-check-circle text-success"></i>
            RFQ Sent Successfully
          </h2>
        </div>
        
        <div class="modal-body">
          <p>RFQ <strong>{{ rfq.rfq_number }}</strong> has been successfully sent to {{ selectedVendors.length }} vendor{{ selectedVendors.length > 1 ? 's' : '' }}.</p>
          <p>Vendors can now submit their quotations for this RFQ. You will be notified when quotations are received.</p>
          
          <div class="success-actions">
            <h4>What's next?</h4>
            <ul>
              <li>Vendors will receive RFQ notifications</li>
              <li>Monitor quotation submissions in RFQ details</li>
              <li>Compare quotations when available</li>
              <li>Select winning quotation(s)</li>
            </ul>
          </div>
        </div>
        
        <div class="modal-actions">
          <router-link :to="`/purchasing/rfqs/${rfqId}`" class="btn btn-primary">
            View RFQ Details
          </router-link>
          <router-link to="/purchasing/rfqs" class="btn btn-secondary">
            Back to RFQ List
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'RFQSend',
  props: {
    rfqId: {
      type: [Number, String],
      required: true
    }
  },
  data() {
    return {
      // RFQ Data
      rfq: null,
      loading: true,
      error: null,

      // Vendor Data
      vendors: [],
      selectedVendors: [],
      preSelectedVendors: [],
      loadingVendors: false,
      vendorsError: null,
      vendorSearchQuery: '',

      // UI State
      isSending: false,
      showConfirmationModal: false,
      showSuccessModal: false,

      // Toast notifications
      toast: null
    }
  },

  computed: {
    // Filter vendors based on search query
    filteredVendors() {
      if (!this.vendorSearchQuery.trim()) {
        return this.vendors;
      }
      
      const searchTerm = this.vendorSearchQuery.toLowerCase();
      return this.vendors.filter(vendor => 
        vendor.name.toLowerCase().includes(searchTerm) || 
        vendor.vendor_code.toLowerCase().includes(searchTerm) ||
        (vendor.email && vendor.email.toLowerCase().includes(searchTerm)) ||
        (vendor.contact_person && vendor.contact_person.toLowerCase().includes(searchTerm))
      );
    },

    // Vendor selection summary text
    vendorSelectionSummary() {
      const total = this.vendors.length;
      const selected = this.selectedVendors.length;
      const preSelected = this.preSelectedVendors.length;
      
      let summary = `${selected} of ${total} vendors selected`;
      
      if (preSelected > 0) {
        summary += ` (${preSelected} pre-selected from conversion)`;
      }
      
      return summary;
    },
    
    // Check if current selection differs from pre-selected
    isSelectionChanged() {
      if (this.preSelectedVendors.length === 0) return false;
      
      const preSelectedIds = this.preSelectedVendors.map(v => v.vendor_id).sort();
      const currentIds = [...this.selectedVendors].sort();
      
      return JSON.stringify(preSelectedIds) !== JSON.stringify(currentIds);
    },

    // Check if all filtered vendors are selected
    areAllFilteredSelected() {
      if (this.filteredVendors.length === 0) return false;
      return this.filteredVendors.every(vendor => 
        this.selectedVendors.includes(vendor.vendor_id)
      );
    }
  },

  mounted() {
    this.loadRFQ();
    
    // Initialize toast if available
    if (this.$toast) {
      this.toast = this.$toast;
    }
  },

  methods: {
    // Load RFQ data with selected vendors
    async loadRFQ() {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get(`/request-for-quotations/${this.rfqId}`);
        
        if (response.data.status === 'success' && response.data.data) {
          this.rfq = response.data.data;
          
          // Load pre-selected vendors from RFQ data
          if (this.rfq.selected_vendors && Array.isArray(this.rfq.selected_vendors)) {
            this.preSelectedVendors = this.rfq.selected_vendors;
            this.selectedVendors = this.rfq.selected_vendors.map(vendor => vendor.vendor_id);
            
            console.log('Pre-selected vendors loaded:', this.preSelectedVendors.length);
          }
          
          // Load all available vendors
          await this.loadVendors();
          
        } else {
          this.rfq = null;
          throw new Error(response.data.message || 'Failed to load RFQ');
        }
      } catch (error) {
        console.error('Error loading RFQ:', error);
        this.rfq = null;
        
        if (error.response && error.response.status === 404) {
          this.error = 'Request for Quotation not found';
        } else {
          this.error = 'Failed to load RFQ details. Please try again.';
        }
        
        if (this.toast) {
          this.toast.error(this.error);
        }
      } finally {
        this.loading = false;
      }
    },

    // Load vendors data with selection info
    async loadVendors() {
      this.loadingVendors = true;
      this.vendorsError = null;
      
      try {
        // Use the new endpoint to get vendor data with pre-selected info
        const response = await axios.get(`/request-for-quotations/${this.rfqId}/vendors`);
        
        if (response.data.status === 'success') {
          this.vendors = response.data.data.all_vendors || [];
          
          // If no vendors were pre-selected from RFQ load, use the API response
          if (this.selectedVendors.length === 0 && response.data.data.selected_vendor_ids) {
            this.selectedVendors = [...response.data.data.selected_vendor_ids];
            this.preSelectedVendors = response.data.data.selected_vendors || [];
          }
          
          console.log('Vendors loaded:', this.vendors.length);
          console.log('Final selected vendors:', this.selectedVendors.length);
        } else {
          throw new Error(response.data.message || 'Failed to load vendors');
        }
      } catch (error) {
        console.error('Error loading vendors:', error);
        this.vendorsError = 'Failed to load vendors. Please try again.';
        
        if (this.toast) {
          this.toast.error(this.vendorsError);
        }
      } finally {
        this.loadingVendors = false;
      }
    },

    // Check if vendor is selected
    isVendorSelected(vendorId) {
      return this.selectedVendors.includes(vendorId);
    },

    // Check if vendor was pre-selected during conversion
    isPreSelected(vendorId) {
      return this.preSelectedVendors.some(vendor => vendor.vendor_id === vendorId);
    },

    // Check if vendor was newly added (not in pre-selected)
    isNewlyAdded(vendorId) {
      return this.isVendorSelected(vendorId) && !this.isPreSelected(vendorId);
    },

    // Toggle vendor selection
    toggleVendorSelection(vendorId) {
      const index = this.selectedVendors.indexOf(vendorId);
      
      if (index === -1) {
        this.selectedVendors.push(vendorId);
      } else {
        this.selectedVendors.splice(index, 1);
      }
    },

    // Select all filtered vendors
    selectAllFilteredVendors() {
      this.filteredVendors.forEach(vendor => {
        if (!this.selectedVendors.includes(vendor.vendor_id)) {
          this.selectedVendors.push(vendor.vendor_id);
        }
      });
    },

    // Deselect all vendors
    deselectAllVendors() {
      this.selectedVendors = [];
    },

    // Reset to pre-selected vendors
    resetToPreSelected() {
      this.selectedVendors = this.preSelectedVendors.map(vendor => vendor.vendor_id);
    },

    // Get selected vendor objects
    getSelectedVendorObjects() {
      return this.vendors.filter(vendor => 
        this.selectedVendors.includes(vendor.vendor_id)
      );
    },

    // Show confirmation modal
    confirmSendRFQ() {
      if (this.selectedVendors.length === 0) {
        if (this.toast) {
          this.toast.warning('Please select at least one vendor');
        }
        return;
      }
      
      this.showConfirmationModal = true;
    },

    // Replace the sendRFQ method in RFQSend.vue with this fixed version

// Replace the sendRFQ method in RFQSend.vue with this fixed version

async sendRFQ() {
  if (this.selectedVendors.length === 0) {
    if (this.toast) {
      this.toast.warning('Please select at least one vendor');
    }
    return;
  }
  
  this.isSending = true;
  
  try {
    // Update selected vendors if changed from original selection
    if (this.isSelectionChanged) {
      await this.updateSelectedVendors();
    }

    // STEP 1: Update RFQ status to 'sent' FIRST (required before creating quotations)
    try {
      await axios.patch(`/request-for-quotations/${this.rfqId}/status`, {
        status: 'sent'
      });
    } catch (error) {
      throw new Error('Failed to update RFQ status: ' + (error.response?.data?.message || error.message));
    }

    // STEP 2: Create vendor quotations for each selected vendor individually
    const quotationDate = new Date().toISOString().substr(0, 10); // Current date in YYYY-MM-DD format
    const failedVendors = [];
    const successfulVendors = [];

    for (const vendorId of this.selectedVendors) {
      try {
        const response = await axios.post('/vendor-quotations/create-from-rfq', {
          rfq_id: this.rfqId,
          vendor_id: vendorId,  // Send individual vendor_id
          quotation_date: quotationDate,  // Add required quotation_date
          validity_date: this.rfq.validity_date || null, // Optional validity date from RFQ
          notes: `Quotation request from RFQ ${this.rfq.rfq_number}`,
          // ADD LINES FROM RFQ - this is the missing piece!
          lines: this.rfq.lines ? this.rfq.lines.map(line => ({
            item_id: line.item_id,
            quantity: line.quantity,
            uom_id: line.uom_id,
            unit_price: 0, // Default price - vendor will update this
            delivery_date: line.required_date || null
          })) : []
        });

        if (response.data.status === 'success') {
          successfulVendors.push(vendorId);
        } else {
          throw new Error(response.data.message || 'Failed to create quotation');
        }
      } catch (error) {
        console.error(`Error creating quotation for vendor ${vendorId}:`, error);
        failedVendors.push({
          vendorId,
          error: error.response?.data?.message || error.message
        });
      }
    }

    // If at least one vendor was successful, mark vendors as sent
    if (successfulVendors.length > 0) {
      try {
        // Mark successful vendors as sent in RFQ
        if (successfulVendors.length === this.selectedVendors.length) {
          await this.markVendorsAsSent();
        }
      } catch (error) {
        console.error('Error marking vendors as sent:', error);
        // Don't fail the entire operation for this
      }
    }

    // Close confirmation modal
    this.showConfirmationModal = false;
    
    // Show appropriate success/error messages
    if (successfulVendors.length === this.selectedVendors.length) {
      // All vendors successful
      this.showSuccessModal = true;
      if (this.toast) {
        this.toast.success(`RFQ sent successfully to ${successfulVendors.length} vendor${successfulVendors.length > 1 ? 's' : ''}`);
      }
    } else if (successfulVendors.length > 0) {
      // Partial success
      if (this.toast) {
        this.toast.warning(`RFQ sent to ${successfulVendors.length} out of ${this.selectedVendors.length} vendors. ${failedVendors.length} failed.`);
      }
      this.showSuccessModal = true; // Still show success modal for partial success
    } else {
      // All failed - rollback RFQ status to draft
      try {
        await axios.patch(`/request-for-quotations/${this.rfqId}/status`, {
          status: 'draft'
        });
      } catch (rollbackError) {
        console.error('Failed to rollback RFQ status:', rollbackError);
      }
      
      const firstError = failedVendors[0]?.error || 'Unknown error';
      if (this.toast) {
        this.toast.error(`Failed to send RFQ to all vendors: ${firstError}`);
      }
    }

    // Log failed vendors for debugging
    if (failedVendors.length > 0) {
      console.error('Failed vendors:', failedVendors);
    }

  } catch (error) {
    console.error('Error in sendRFQ:', error);
    
    this.showConfirmationModal = false;
    
    const errorMessage = error.response?.data?.message || 'Failed to send RFQ to vendors. Please try again.';
    
    if (this.toast) {
      this.toast.error('Failed to send RFQ: ' + errorMessage);
    }
  } finally {
    this.isSending = false;
  }
},

    // Update selected vendors if selection changed
    async updateSelectedVendors() {
      try {
        await axios.patch(`/request-for-quotations/${this.rfqId}/vendors`, {
          vendor_ids: this.selectedVendors
        });
        
        console.log('Vendor selection updated');
      } catch (error) {
        console.error('Error updating vendor selection:', error);
        // Don't throw error here, just log it as it's not critical
      }
    },

    // Mark vendors as sent
    async markVendorsAsSent() {
      try {
        await axios.patch(`/request-for-quotations/${this.rfqId}/vendors/mark-sent`, {
          vendor_ids: this.selectedVendors
        });
        
        console.log('Vendors marked as sent');
      } catch (error) {
        console.error('Error marking vendors as sent:', error);
        // Don't throw error here, just log it as it's not critical
      }
    },

    // Format date for display
    formatDate(dateString) {
      if (!dateString) return null;
      
      const date = new Date(dateString);
      return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    },

    // Capitalize first letter
    capitalizeFirstLetter(string) {
      if (!string) return '';
      return string.charAt(0).toUpperCase() + string.slice(1);
    }
  }
}
</script>

<style scoped>
/* Main Container */
.rfq-send-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px;
}

/* Page Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 1px solid #e9ecef;
}

.page-header h1 {
  margin: 0;
  color: #212529;
  font-size: 1.75rem;
  font-weight: 600;
}

.header-actions {
  display: flex;
  gap: 12px;
}

/* Loading and Error States */
.loading-indicator, .error-state {
  text-align: center;
  padding: 48px 24px;
}

.loading-indicator i {
  font-size: 2rem;
  color: #6c757d;
  margin-bottom: 16px;
}

.error-state {
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e9ecef;
}

.error-icon i {
  font-size: 3rem;
  color: #dc3545;
  margin-bottom: 16px;
}

.error-state h3 {
  color: #212529;
  margin-bottom: 8px;
}

.error-state p {
  color: #6c757d;
  margin-bottom: 24px;
}

/* Detail Cards */
.detail-card {
  background: white;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  margin-bottom: 24px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #e9ecef;
  background: #f8f9fa;
  border-radius: 8px 8px 0 0;
}

.card-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #212529;
}

.card-body {
  padding: 24px;
}

/* Status Badge */
.status-badge {
  padding: 6px 12px;
  border-radius: 16px;
  font-size: 0.875rem;
  font-weight: 500;
  text-transform: uppercase;
}

.status-draft {
  background: #fff3cd;
  color: #856404;
  border: 1px solid #ffeaa7;
}

/* Detail Display */
.detail-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 24px;
  margin-bottom: 20px;
}

.detail-group {
  margin-bottom: 16px;
}

.detail-group label {
  display: block;
  font-weight: 600;
  color: #495057;
  margin-bottom: 4px;
  font-size: 0.875rem;
}

.detail-value {
  color: #212529;
  font-size: 1rem;
}

/* Pre-selected Vendors */
.vendor-count-badge {
  background: #e3f2fd;
  color: #1976d2;
  padding: 4px 12px;
  border-radius: 16px;
  font-size: 0.85rem;
  font-weight: 500;
}

.info-text {
  background: #e8f4f8;
  padding: 12px;
  border-radius: 6px;
  margin-bottom: 20px;
  color: #0277bd;
  font-size: 0.9rem;
}

.pre-selected-vendors-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.pre-selected-vendor-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 8px;
}

.vendor-info {
  flex: 1;
}

.vendor-name {
  font-weight: 600;
  color: #212529;
  margin-bottom: 4px;
}

.vendor-details {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  font-size: 0.875rem;
  color: #6c757d;
}

.vendor-details span {
  display: flex;
  align-items: center;
  gap: 4px;
}

.selection-status {
  color: #28a745;
  font-weight: 500;
  font-size: 0.875rem;
}

/* Selection Summary */
.selection-summary {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.9rem;
  color: #666;
}

.changed-indicator {
  color: #ff9800;
  font-weight: 500;
}

/* Vendor Controls */
.control-row {
  display: flex;
  gap: 16px;
  margin-bottom: 20px;
  align-items: center;
}

.search-input {
  flex: 1;
  position: relative;
}

.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #6c757d;
}

.search-input .form-control {
  padding-left: 40px;
}

.bulk-actions {
  display: flex;
  gap: 8px;
}

/* Selection Stats */
.selection-stats {
  display: flex;
  gap: 24px;
  margin-bottom: 20px;
  padding: 12px;
  background: #f8f9fa;
  border-radius: 6px;
  font-size: 0.875rem;
}

.stat-item {
  display: flex;
  gap: 4px;
}

.stat-label {
  color: #6c757d;
}

.stat-value {
  font-weight: 600;
  color: #212529;
}

.stat-value.selected {
  color: #28a745;
}

.stat-value.pre-selected {
  color: #17a2b8;
}

/* Vendor List */
.vendors-list {
  max-height: 400px;
  overflow-y: auto;
  border: 1px solid #e9ecef;
  border-radius: 6px;
}

.vendor-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  border-bottom: 1px solid #e9ecef;
  cursor: pointer;
  transition: background-color 0.2s;
}

.vendor-item:last-child {
  border-bottom: none;
}

.vendor-item:hover {
  background: #f8f9fa;
}

.vendor-item.selected {
  background: #e8f5e8;
  border-color: #28a745;
}

.vendor-item.pre-selected {
  background: #f3f8ff;
  border-color: #4a90e2;
}

.vendor-item.newly-added {
  background: #fff8e1;
  border-color: #ffa726;
}

.vendor-checkbox {
  flex-shrink: 0;
}

.vendor-checkbox input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.vendor-info {
  flex: 1;
}

.vendor-info .vendor-name {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.vendor-status {
  flex-shrink: 0;
  font-size: 1.25rem;
}

/* Vendor Badges */
.pre-selected-badge {
  display: inline-block;
  background: #e3f2fd;
  color: #1976d2;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
}

.newly-added-badge {
  display: inline-block;
  background: #fff3e0;
  color: #f57c00;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
}

/* No Vendors */
.no-vendors {
  text-align: center;
  padding: 48px 24px;
  color: #6c757d;
}

.no-vendors i {
  font-size: 2rem;
  margin-bottom: 16px;
}

/* Loading Vendors */
.loading-vendors {
  text-align: center;
  padding: 48px 24px;
  color: #6c757d;
}

.error-loading-vendors {
  text-align: center;
  padding: 48px 24px;
  color: #dc3545;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 24px 0;
  border-top: 1px solid #e9ecef;
}

/* Modal Styles */
.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-backdrop {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
}

.modal-content {
  position: relative;
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
  max-width: 600px;
  width: 90%;
  max-height: 80vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #e9ecef;
}

.modal-header h2 {
  margin: 0;
  display: flex;
  align-items: center;
  gap: 8px;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.25rem;
  color: #6c757d;
  cursor: pointer;
  padding: 4px;
}

.close-btn:hover {
  color: #212529;
}

.modal-body {
  padding: 24px;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 20px 24px;
  border-top: 1px solid #e9ecef;
}

/* Selected Vendors Summary */
.selected-vendors-summary {
  max-height: 200px;
  overflow-y: auto;
  border: 1px solid #e9ecef;
  border-radius: 6px;
  margin: 16px 0;
}

.vendor-summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  border-bottom: 1px solid #e9ecef;
}

.vendor-summary-item:last-child {
  border-bottom: none;
}

.vendor-type {
  font-size: 0.875rem;
}

.confirmation-stats {
  background: #f8f9fa;
  padding: 16px;
  border-radius: 6px;
  margin-top: 16px;
}

.confirmation-stats p {
  margin: 4px 0;
}

.warning-text {
  color: #856404;
  background: #fff3cd;
  padding: 8px 12px;
  border-radius: 4px;
  border: 1px solid #ffeaa7;
}

/* Success Modal */
.success-modal .modal-header h2 {
  color: #28a745;
}

.success-actions {
  background: #f8f9fa;
  padding: 16px;
  border-radius: 6px;
  margin-top: 16px;
}

.success-actions h4 {
  margin: 0 0 8px 0;
  color: #212529;
}

.success-actions ul {
  margin: 8px 0 0 0;
  padding-left: 20px;
}

.success-actions li {
  margin-bottom: 4px;
  color: #6c757d;
}

/* Button Styles */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border: 1px solid transparent;
  border-radius: 4px;
  font-size: 0.875rem;
  font-weight: 500;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.2s;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background: #007bff;
  color: white;
  border-color: #007bff;
}

.btn-primary:hover:not(:disabled) {
  background: #0056b3;
  border-color: #0056b3;
}

.btn-secondary {
  background: #6c757d;
  color: white;
  border-color: #6c757d;
}

.btn-secondary:hover:not(:disabled) {
  background: #545b62;
  border-color: #545b62;
}

.btn-outline {
  background: transparent;
  color: #6c757d;
  border-color: #6c757d;
}

.btn-outline:hover {
  background: #6c757d;
  color: white;
}

.btn-outline-primary {
  background: transparent;
  color: #007bff;
  border-color: #007bff;
}

.btn-outline-primary:hover:not(:disabled) {
  background: #007bff;
  color: white;
}

.btn-outline-secondary {
  background: transparent;
  color: #6c757d;
  border-color: #6c757d;
}

.btn-outline-secondary:hover:not(:disabled) {
  background: #6c757d;
  color: white;
}

.btn-outline-info {
  background: transparent;
  color: #17a2b8;
  border-color: #17a2b8;
}

.btn-outline-info:hover:not(:disabled) {
  background: #17a2b8;
  color: white;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 0.8rem;
}

/* Form Controls */
.form-control {
  display: block;
  width: 100%;
  padding: 8px 12px;
  font-size: 0.875rem;
  line-height: 1.5;
  color: #495057;
  background: white;
  border: 1px solid #ced4da;
  border-radius: 4px;
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus {
  color: #495057;
  background: white;
  border-color: #80bdff;
  outline: 0;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Utility Classes */
.text-success { color: #28a745 !important; }
.text-info { color: #17a2b8 !important; }
.text-warning { color: #ffc107 !important; }
.text-danger { color: #dc3545 !important; }
.text-muted { color: #6c757d !important; }
.text-primary { color: #007bff !important; }

/* Responsive */
@media (max-width: 768px) {
  .rfq-send-container {
    padding: 16px;
  }
  
  .page-header {
    flex-direction: column;
    gap: 16px;
    align-items: flex-start;
  }
  
  .control-row {
    flex-direction: column;
  }
  
  .bulk-actions {
    width: 100%;
    justify-content: space-between;
  }
  
  .detail-row {
    grid-template-columns: 1fr;
  }
  
  .selection-stats {
    flex-direction: column;
    gap: 8px;
  }
  
  .action-buttons {
    flex-direction: column;
  }
  
  .modal-content {
    width: 95%;
    margin: 16px;
  }
}
</style>