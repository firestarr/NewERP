<!-- src/views/manufacturing/BOMForm.vue -->
<template>
  <div class="bom-form-container">
    <div class="card">
      <div class="card-header">
        <h2 class="card-title">{{ isEditMode ? 'Edit BOM' : 'Create New BOM' }}</h2>
      </div>

      <div class="card-body">
        <form @submit.prevent="submitForm">
          <!-- BOM Header Information -->
          <div class="form-section">
            <h3 class="section-title">Basic Information</h3>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="item_id">Item <span class="required">*</span></label>
                  <div class="dropdown">
                    <input
                      type="text"
                      id="item_id"
                      v-model="itemSearch"
                      class="form-control"
                      :class="{ 'is-invalid': errors.item_id }"
                      placeholder="Search for an item..."
                      @focus="showDropdown = true"
                      @blur="hideDropdown"
                    />
                    <div v-if="showDropdown" class="dropdown-menu">
                      <div v-for="item in filteredItems" :key="item.item_id" @mousedown="selectItem(item)" class="dropdown-item">
                        <div class="item-info">
                          <strong>{{ item.name }}</strong>
                          <span class="item-code">({{ item.item_code }})</span>
                        </div>
                        <div class="item-details">
                          <span class="category">{{ item.category ? item.category.name : 'No Category' }}</span>
                          <span class="stock">Stock: {{ item.current_stock || 0 }}</span>
                        </div>
                      </div>
                      <div v-if="filteredItems.length === 0" class="dropdown-item text-muted">No items found</div>
                    </div>
                  </div>
                  <div v-if="errors.item_id" class="invalid-feedback">{{ errors.item_id }}</div>
                </div>

                <div class="form-group">
                  <label for="bom_code">BOM Code <span class="required">*</span></label>
                  <input
                    type="text"
                    id="bom_code"
                    v-model="form.bom_code"
                    class="form-control"
                    :class="{ 'is-invalid': errors.bom_code }"
                    required
                    maxlength="50"
                    :disabled="isEditMode"
                    placeholder="Enter unique BOM code"
                  />
                  <div v-if="errors.bom_code" class="invalid-feedback">{{ errors.bom_code }}</div>
                </div>

                <div class="form-group">
                  <label for="revision">Revision <span class="required">*</span></label>
                  <input
                    type="text"
                    id="revision"
                    v-model="form.revision"
                    class="form-control"
                    :class="{ 'is-invalid': errors.revision }"
                    required
                    maxlength="10"
                    placeholder="e.g. 1.0"
                  />
                  <div v-if="errors.revision" class="invalid-feedback">{{ errors.revision }}</div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label for="effective_date">Effective Date <span class="required">*</span></label>
                  <input
                    type="date"
                    id="effective_date"
                    v-model="form.effective_date"
                    class="form-control"
                    :class="{ 'is-invalid': errors.effective_date }"
                    required
                  />
                  <div v-if="errors.effective_date" class="invalid-feedback">{{ errors.effective_date }}</div>
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="standard_quantity">Standard Quantity <span class="required">*</span></label>
                      <input
                        type="number"
                        id="standard_quantity"
                        v-model="form.standard_quantity"
                        class="form-control"
                        :class="{ 'is-invalid': errors.standard_quantity }"
                        required
                        step="0.01"
                        min="0.01"
                        placeholder="e.g. 1.00"
                      />
                      <div v-if="errors.standard_quantity" class="invalid-feedback">{{ errors.standard_quantity }}</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="uom_id">Unit of Measure <span class="required">*</span></label>
                      <select
                        id="uom_id"
                        v-model="form.uom_id"
                        class="form-control"
                        :class="{ 'is-invalid': errors.uom_id }"
                        required
                      >
                        <option value="">Select UOM</option>
                        <option v-if="selectedItemUOM" :value="selectedItemUOM.uom_id">
                          {{ selectedItemUOM.name }} ({{ selectedItemUOM.symbol }})
                        </option>
                      </select>
                      <div v-if="errors.uom_id" class="invalid-feedback">{{ errors.uom_id }}</div>
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label for="status">Status <span class="required">*</span></label>
                  <select
                    id="status"
                    v-model="form.status"
                    class="form-control"
                    :class="{ 'is-invalid': errors.status }"
                    required
                  >
                    <option value="Draft">Draft</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                    <option value="Obsolete">Obsolete</option>
                  </select>
                  <div v-if="errors.status" class="invalid-feedback">{{ errors.status }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- BOM Components Section -->
          <div class="form-section">
            <div class="section-header">
              <h3 class="section-title">Components</h3>
              <button
                type="button"
                class="btn btn-primary btn-sm"
                @click="openAddComponentModal"
              >
                <i class="fas fa-plus mr-1"></i> Add Component
              </button>
            </div>

            <div class="table-container">
              <table class="table table-bordered" v-if="form.bom_lines.length > 0">
                <thead>
                  <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>UOM</th>
                    <th>Critical</th>
                    <th>Yield-Based</th>
                    <th>Yield Ratio</th>
                    <th>Shrinkage</th>
                    <th>Notes</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(line, index) in form.bom_lines" :key="index">
                    <td>
                      <span v-if="getItemName(line.item_id)">
                        {{ getItemName(line.item_id) }}
                      </span>
                      <span v-else class="text-muted">Item not found</span>
                    </td>
                    <td>{{ formatNumber(line.quantity) }}</td>
                    <td>{{ getUomSymbol(line.uom_id) }}</td>
                    <td class="text-center">
                      <i
                        v-if="line.is_critical"
                        class="fas fa-check-circle text-success"
                        title="Critical component"
                      ></i>
                      <i
                        v-else
                        class="fas fa-times-circle text-muted"
                        title="Optional component"
                      ></i>
                    </td>
                    <td class="text-center">
                      <i
                        v-if="line.is_yield_based"
                        class="fas fa-flask text-info"
                        title="Yield-based component"
                      ></i>
                      <i
                        v-else
                        class="fas fa-minus text-muted"
                        title="Standard component"
                      ></i>
                    </td>
                    <td>
                      {{ line.is_yield_based ? formatNumber(line.yield_ratio) : '-' }}
                    </td>
                    <td>
                      {{ line.is_yield_based ? formatPercentage(line.shrinkage_factor) : '-' }}
                    </td>
                    <td>
                      <span
                        v-if="line.notes"
                        class="notes-preview"
                        :title="line.notes"
                      >
                        {{ truncateText(line.notes, 30) }}
                      </span>
                      <span v-else class="text-muted">-</span>
                    </td>
                    <td class="text-center">
                      <div class="action-group">
                        <button
                          type="button"
                          class="btn btn-sm btn-info"
                          @click="editComponent(index)"
                          title="Edit component"
                        >
                          <i class="fas fa-edit"></i>
                        </button>
                        <button
                          type="button"
                          class="btn btn-sm btn-danger"
                          @click="removeComponent(index)"
                          title="Remove component"
                        >
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>

              <div v-else class="empty-components">
                <p>No components added yet</p>
                <button
                  type="button"
                  class="btn btn-primary"
                  @click="openAddComponentModal"
                >
                  <i class="fas fa-plus mr-1"></i> Add First Component
                </button>
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="form-actions">
            <button type="button" class="btn btn-secondary" @click="cancelForm">
              Cancel
            </button>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="isSubmitting || form.bom_lines.length === 0"
            >
              <i v-if="isSubmitting" class="fas fa-spinner fa-spin mr-1"></i>
              {{ isEditMode ? 'Update BOM' : 'Create BOM' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Add/Edit Component Modal -->
    <div v-if="showAddComponentModal || showEditComponentModal" class="modal">
      <div class="modal-backdrop" @click="cancelComponentModal"></div>
      <div class="modal-content">
        <div class="modal-header">
          <h3>{{ showEditComponentModal ? 'Edit Component' : 'Add Component' }}</h3>
          <button type="button" class="close-btn" @click="cancelComponentModal">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveComponent">
            <!-- Item Selection with Search -->
            <div class="form-group">
              <label for="component_item_id">Item <span class="required">*</span></label>
              <div class="dropdown">
                <input
                  type="text"
                  id="component_item_id"
                  v-model="componentItemSearch"
                  class="form-control"
                  :class="{ 'is-invalid': componentErrors.item_id }"
                  placeholder="Search for an item..."
                  @focus="showComponentDropdown = true"
                  @blur="hideComponentDropdown"
                  autocomplete="off"
                />
                <div v-if="showComponentDropdown" class="dropdown-menu">
                  <div
                    v-for="item in filteredComponentItems"
                    :key="item.item_id"
                    @mousedown="selectComponentItem(item)"
                    class="dropdown-item"
                  >
                    <div class="item-info">
                      <strong>{{ item.name }}</strong>
                      <span class="item-code">({{ item.item_code }})</span>
                    </div>
                    <div class="item-details">
                      <span class="category">{{ item.category ? item.category.name : 'No Category' }}</span>
                      <span class="stock">Stock: {{ item.current_stock || 0 }}</span>
                    </div>
                  </div>
                  <div v-if="filteredComponentItems.length === 0" class="dropdown-item text-muted">
                    No items found
                  </div>
                </div>
              </div>
              <div v-if="componentErrors.item_id" class="invalid-feedback">{{ componentErrors.item_id }}</div>
            </div>

            <!-- Quantity and UOM -->
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="component_quantity">Quantity <span class="required">*</span></label>
                  <input
                    type="number"
                    id="component_quantity"
                    v-model="componentForm.quantity"
                    class="form-control"
                    :class="{ 'is-invalid': componentErrors.quantity }"
                    step="0.01"
                    min="0.01"
                    required
                  />
                  <div v-if="componentErrors.quantity" class="invalid-feedback">{{ componentErrors.quantity }}</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="component_uom_id">Unit of Measure <span class="required">*</span></label>
                  <select
                    id="component_uom_id"
                    v-model="componentForm.uom_id"
                    class="form-control"
                    :class="{ 'is-invalid': componentErrors.uom_id }"
                    required
                  >
                    <option value="">Select UOM</option>
                    <option v-if="selectedComponentItemUOM" :value="selectedComponentItemUOM.uom_id">
                      {{ selectedComponentItemUOM.name }} ({{ selectedComponentItemUOM.symbol }})
                    </option>
                    <option v-for="uom in uoms" :key="uom.uom_id" :value="uom.uom_id">
                      {{ uom.name }} ({{ uom.symbol }})
                    </option>
                  </select>
                  <div v-if="componentErrors.uom_id" class="invalid-feedback">{{ componentErrors.uom_id }}</div>
                </div>
              </div>
            </div>

            <!-- Critical and Yield-Based Checkboxes -->
            <div class="form-group form-check">
              <input
                type="checkbox"
                id="component_is_critical"
                v-model="componentForm.is_critical"
                class="form-check-input"
              />
              <label for="component_is_critical" class="form-check-label">
                Critical Component
              </label>
              <small class="form-text text-muted">
                Mark as critical if this component is essential for the BOM
              </small>
            </div>

            <div class="form-group form-check">
              <input
                type="checkbox"
                id="component_is_yield_based"
                v-model="componentForm.is_yield_based"
                class="form-check-input"
              />
              <label for="component_is_yield_based" class="form-check-label">
                Yield-Based Component
              </label>
              <small class="form-text text-muted">
                Enable if this component's quantity affects the final yield of the product
              </small>
            </div>

            <!-- Yield-Based Fields -->
            <div v-if="componentForm.is_yield_based" class="yield-based-fields">
              <div class="form-group">
                <label for="component_yield_ratio">Yield Ratio <span class="required">*</span></label>
                <input
                  type="number"
                  id="component_yield_ratio"
                  v-model="componentForm.yield_ratio"
                  class="form-control"
                  step="0.0001"
                  min="0.0001"
                  required
                  placeholder="Number of finished products per unit of this material"
                />
                <small class="form-text text-muted">
                  How many units of the finished product can be produced per unit of this material
                </small>
              </div>

              <div class="form-group">
                <label for="component_shrinkage_factor">Shrinkage Factor</label>
                <div class="input-group">
                  <input
                    type="number"
                    id="component_shrinkage_factor"
                    v-model="componentForm.shrinkage_factor"
                    class="form-control"
                    step="0.01"
                    min="0"
                    max="0.99"
                    placeholder="e.g. 0.05 for 5% shrinkage"
                  />
                  <div class="input-group-append">
                    <span class="input-group-text">%</span>
                  </div>
                </div>
                <small class="form-text text-muted">
                  Factor to account for material waste/shrinkage (0-99%)
                </small>
              </div>
            </div>

            <!-- Notes -->
            <div class="form-group">
              <label for="component_notes">Notes</label>
              <textarea
                id="component_notes"
                v-model="componentForm.notes"
                class="form-control"
                rows="3"
                placeholder="Enter any special instructions or notes about this component"
              ></textarea>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="cancelComponentModal">
                Cancel
              </button>
              <button type="submit" class="btn btn-primary">
                {{ showEditComponentModal ? 'Update Component' : 'Add Component' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

export default {
  name: 'BOMForm',
  setup() {
    const route = useRoute();
    const router = useRouter();
    const bomId = route.params.id;
    const isEditMode = computed(() => !!bomId);

    // Form state
    const isLoading = ref(false);
    const isSubmitting = ref(false);
    const errors = ref({});

    // Reference data
    const items = ref([]);
    const uoms = ref([]);

    // Modal state
    const showAddComponentModal = ref(false);
    const showEditComponentModal = ref(false);
    const editingComponentIndex = ref(-1);

    // Main item search state
    const itemSearch = ref('');
    const showDropdown = ref(false);

    // Component search state
    const componentItemSearch = ref('');
    const showComponentDropdown = ref(false);
    const componentErrors = ref({});

    // Main form data
    const form = reactive({
      item_id: '',
      bom_code: '',
      revision: '1.0',
      effective_date: new Date().toISOString().substr(0, 10),
      standard_quantity: 1,
      uom_id: '',
      status: 'Draft',
      bom_lines: []
    });

    // Component form data
    const componentForm = reactive({
      item_id: '',
      quantity: 1,
      uom_id: '',
      is_critical: false,
      is_yield_based: false,
      yield_ratio: 1,
      shrinkage_factor: 0,
      notes: ''
    });

    // Computed property untuk filter main items berdasarkan search input
    const filteredItems = computed(() => {
      if (!itemSearch.value) {
        return items.value.slice(0, 10);
      }
      return items.value.filter(item =>
        item.name.toLowerCase().includes(itemSearch.value.toLowerCase()) ||
        item.item_code.toLowerCase().includes(itemSearch.value.toLowerCase())
      ).slice(0, 10);
    });

    // Computed property untuk filter component items berdasarkan search
    const filteredComponentItems = computed(() => {
      if (!componentItemSearch.value) {
        return items.value.slice(0, 10);
      }
      return items.value.filter(item =>
        item.name.toLowerCase().includes(componentItemSearch.value.toLowerCase()) ||
        item.item_code.toLowerCase().includes(componentItemSearch.value.toLowerCase())
      ).slice(0, 10);
    });

    // Computed property untuk mendapatkan UOM dari main item yang dipilih
    const selectedItemUOM = computed(() => {
      const selectedItem = items.value.find(item => item.item_id === form.item_id);
      return selectedItem ? selectedItem.unit_of_measure : null;
    });

    // Computed property untuk mendapatkan UOM dari component item yang dipilih
    const selectedComponentItemUOM = computed(() => {
      const selectedItem = items.value.find(item => item.item_id === componentForm.item_id);
      return selectedItem ? selectedItem.unit_of_measure : null;
    });

    // Watch perubahan main item_id untuk auto-set UOM
    watch(() => form.item_id, (newItemId) => {
      const selectedItem = items.value.find(item => item.item_id === newItemId);
      if (selectedItem && selectedItem.unit_of_measure) {
        form.uom_id = selectedItem.unit_of_measure.uom_id;
      } else {
        form.uom_id = '';
      }
    });

    // Watch perubahan component item_id untuk auto-set UOM
    watch(() => componentForm.item_id, (newItemId) => {
      const selectedItem = items.value.find(item => item.item_id === newItemId);
      if (selectedItem && selectedItem.unit_of_measure) {
        componentForm.uom_id = selectedItem.unit_of_measure.uom_id;
      } else {
        componentForm.uom_id = '';
      }
    });

    // Method untuk select main item dari dropdown
    const selectItem = (item) => {
      form.item_id = item.item_id;
      itemSearch.value = `${item.name} (${item.item_code})`;
      showDropdown.value = false;
    };

    // Method untuk hide main dropdown
    const hideDropdown = () => {
      setTimeout(() => {
        showDropdown.value = false;
      }, 200);
    };

    // Method untuk select component item dari dropdown
    const selectComponentItem = (item) => {
      componentForm.item_id = item.item_id;
      componentItemSearch.value = `${item.name} (${item.item_code})`;
      showComponentDropdown.value = false;
    };

    // Method untuk hide component dropdown
    const hideComponentDropdown = () => {
      setTimeout(() => {
        showComponentDropdown.value = false;
      }, 200);
    };

    // Fetch reference data
    const fetchReferenceData = async () => {
      try {
        // Fetch items
        const itemsResponse = await axios.get('/items');
        items.value = itemsResponse.data.data || itemsResponse.data;

        // Fetch UOMs
        const uomsResponse = await axios.get('/unit-of-measures');
        uoms.value = uomsResponse.data.data || uomsResponse.data;
      } catch (error) {
        console.error('Error fetching reference data:', error);
      }
    };

    // Fetch BOM for editing
    const fetchBOM = async () => {
      if (!isEditMode.value) return;

      isLoading.value = true;

      try {
        const response = await axios.get(`/boms/${bomId}`);
        const bomData = response.data.data;

        // Update form with BOM data
        form.item_id = bomData.item_id;
        form.bom_code = bomData.bom_code;
        form.revision = bomData.revision;
        form.effective_date = bomData.effective_date ? new Date(bomData.effective_date).toISOString().substr(0, 10) : '';
        form.standard_quantity = bomData.standard_quantity;
        form.uom_id = bomData.uom_id;
        form.status = bomData.status;

        // Set itemSearch untuk display
        if (bomData.item) {
          itemSearch.value = `${bomData.item.name} (${bomData.item.item_code})`;
        }

        // Handle BOM lines
        if (bomData.bomLines && Array.isArray(bomData.bomLines)) {
          form.bom_lines = bomData.bomLines.map(line => ({
            line_id: line.line_id,
            item_id: line.item_id,
            quantity: line.quantity,
            uom_id: line.uom_id,
            is_critical: line.is_critical || false,
            is_yield_based: line.is_yield_based || false,
            yield_ratio: line.yield_ratio || 1,
            shrinkage_factor: line.shrinkage_factor || 0,
            notes: line.notes || ''
          }));
        } else {
          await fetchBOMLines();
        }
      } catch (error) {
        console.error('Error fetching BOM:', error);
      } finally {
        isLoading.value = false;
      }
    };

    // Fetch BOM lines separately
    const fetchBOMLines = async () => {
      try {
        const response = await axios.get(`/boms/${bomId}/lines`);
        form.bom_lines = response.data.data.map(line => ({
          line_id: line.line_id,
          item_id: line.item_id,
          quantity: line.quantity,
          uom_id: line.uom_id,
          is_critical: line.is_critical || false,
          is_yield_based: line.is_yield_based || false,
          yield_ratio: line.yield_ratio || 1,
          shrinkage_factor: line.shrinkage_factor || 0,
          notes: line.notes || ''
        }));
      } catch (error) {
        console.error('Error fetching BOM lines:', error);
      }
    };

    // Component modal handlers
    const openAddComponentModal = () => {
      // Reset component form
      Object.keys(componentForm).forEach(key => {
        if (key === 'quantity' || key === 'yield_ratio') {
          componentForm[key] = 1;
        } else if (key === 'is_critical' || key === 'is_yield_based') {
          componentForm[key] = false;
        } else if (key === 'shrinkage_factor') {
          componentForm[key] = 0;
        } else {
          componentForm[key] = '';
        }
      });

      // Reset search and errors
      componentItemSearch.value = '';
      componentErrors.value = {};
      showAddComponentModal.value = true;
    };

    const editComponent = (index) => {
      const line = form.bom_lines[index];
      const item = items.value.find(i => i.item_id === line.item_id);

      // Populate component form with line data
      componentForm.item_id = line.item_id;
      componentForm.quantity = line.quantity;
      componentForm.uom_id = line.uom_id;
      componentForm.is_critical = line.is_critical;
      componentForm.is_yield_based = line.is_yield_based;
      componentForm.yield_ratio = line.yield_ratio || 1;
      componentForm.shrinkage_factor = line.shrinkage_factor || 0;
      componentForm.notes = line.notes || '';

      // Set search text
      if (item) {
        componentItemSearch.value = `${item.name} (${item.item_code})`;
      }

      editingComponentIndex.value = index;
      componentErrors.value = {};
      showEditComponentModal.value = true;
    };

    const cancelComponentModal = () => {
      showAddComponentModal.value = false;
      showEditComponentModal.value = false;
      editingComponentIndex.value = -1;
      componentItemSearch.value = '';
      componentErrors.value = {};
    };

    const saveComponent = () => {
      // Reset errors
      componentErrors.value = {};

      // Basic validation
      if (!componentForm.item_id) {
        componentErrors.value.item_id = 'Item is required';
      }
      if (!componentForm.quantity || componentForm.quantity <= 0) {
        componentErrors.value.quantity = 'Quantity must be greater than zero';
      }
      if (!componentForm.uom_id) {
        componentErrors.value.uom_id = 'Unit of measure is required';
      }

      if (componentForm.is_yield_based && (!componentForm.yield_ratio || componentForm.yield_ratio <= 0)) {
        componentErrors.value.yield_ratio = 'Yield ratio must be greater than zero for yield-based components';
      }

      // Return if validation fails
      if (Object.keys(componentErrors.value).length > 0) {
        return;
      }

      if (showEditComponentModal.value) {
        // Update existing component
        if (editingComponentIndex.value >= 0 && editingComponentIndex.value < form.bom_lines.length) {
          const line = form.bom_lines[editingComponentIndex.value];
          line.item_id = componentForm.item_id;
          line.quantity = componentForm.quantity;
          line.uom_id = componentForm.uom_id;
          line.is_critical = componentForm.is_critical;
          line.is_yield_based = componentForm.is_yield_based;
          line.yield_ratio = componentForm.is_yield_based ? componentForm.yield_ratio : 1;
          line.shrinkage_factor = componentForm.is_yield_based ? componentForm.shrinkage_factor : 0;
          line.notes = componentForm.notes;
        }
      } else {
        // Add new component
        form.bom_lines.push({
          item_id: componentForm.item_id,
          quantity: componentForm.quantity,
          uom_id: componentForm.uom_id,
          is_critical: componentForm.is_critical,
          is_yield_based: componentForm.is_yield_based,
          yield_ratio: componentForm.is_yield_based ? componentForm.yield_ratio : 1,
          shrinkage_factor: componentForm.is_yield_based ? componentForm.shrinkage_factor : 0,
          notes: componentForm.notes
        });
      }

      cancelComponentModal();
    };

    const removeComponent = (index) => {
      if (confirm(`Are you sure you want to remove this component?`)) {
        form.bom_lines.splice(index, 1);
      }
    };

    // Form submission
    const submitForm = async () => {
      errors.value = {};

      // Validate form
      if (!form.item_id) errors.value.item_id = 'Item is required';
      if (!form.bom_code) errors.value.bom_code = 'BOM Code is required';
      if (!form.revision) errors.value.revision = 'Revision is required';
      if (!form.effective_date) errors.value.effective_date = 'Effective Date is required';
      if (!form.standard_quantity || form.standard_quantity <= 0) {
        errors.value.standard_quantity = 'Standard Quantity must be greater than zero';
      }
      if (!form.uom_id) errors.value.uom_id = 'Unit of Measure is required';
      if (!form.status) errors.value.status = 'Status is required';

      if (form.bom_lines.length === 0) {
        alert('You must add at least one component to the BOM');
        return;
      }

      if (Object.keys(errors.value).length > 0) return;

      isSubmitting.value = true;

      try {
        if (isEditMode.value) {
          await updateBOM();
        } else {
          await createBOM();
        }

        router.push(isEditMode.value ? `/manufacturing/boms/${bomId}` : '/manufacturing/boms');
      } catch (error) {
        console.error('Error saving BOM:', error);

        if (error.response && error.response.data && error.response.data.errors) {
          const serverErrors = error.response.data.errors;
          Object.keys(serverErrors).forEach(key => {
            errors.value[key] = serverErrors[key][0];
          });
        } else {
          alert('Failed to save BOM. Please try again.');
        }
      } finally {
        isSubmitting.value = false;
      }
    };

    // Create a new BOM
    const createBOM = async () => {
      const payload = {
        item_id: form.item_id,
        bom_code: form.bom_code,
        revision: form.revision,
        effective_date: form.effective_date,
        standard_quantity: form.standard_quantity,
        uom_id: form.uom_id,
        status: form.status,
        bom_lines: form.bom_lines
      };

      await axios.post('/boms', payload);
    };

    // Update an existing BOM
    const updateBOM = async () => {
      const bomPayload = {
        item_id: form.item_id,
        bom_code: form.bom_code,
        revision: form.revision,
        effective_date: form.effective_date,
        standard_quantity: form.standard_quantity,
        uom_id: form.uom_id,
        status: form.status
      };

      await axios.put(`/boms/${bomId}`, bomPayload);

      const updatePromises = [];

      for (const line of form.bom_lines) {
        const linePayload = {
          item_id: line.item_id,
          quantity: line.quantity,
          uom_id: line.uom_id,
          is_critical: line.is_critical,
          is_yield_based: line.is_yield_based,
          yield_ratio: line.is_yield_based ? line.yield_ratio : null,
          shrinkage_factor: line.is_yield_based ? line.shrinkage_factor : null,
          notes: line.notes
        };

        if (line.line_id) {
          updatePromises.push(axios.put(`/boms/${bomId}/lines/${line.line_id}`, linePayload));
        } else {
          updatePromises.push(axios.post(`/boms/${bomId}/lines`, linePayload));
        }
      }

      await Promise.all(updatePromises);
    };

    // Cancel form submission
    const cancelForm = () => {
      router.push(isEditMode.value ? `/manufacturing/boms/${bomId}` : '/manufacturing/boms');
    };

    // Helper functions
    const getItemName = (itemId) => {
      const item = items.value.find(i => i.item_id === itemId);
      return item ? `${item.name} (${item.item_code})` : '';
    };

    const getUomSymbol = (uomId) => {
      const uom = uoms.value.find(u => u.uom_id === uomId);
      return uom ? uom.symbol : '';
    };

    const formatNumber = (value) => {
      if (value === null || value === undefined) return '-';
      return parseFloat(value).toLocaleString(undefined, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 4
      });
    };

    const formatPercentage = (value) => {
      if (value === null || value === undefined) return '-';
      return (value * 100).toLocaleString(undefined, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
      }) + '%';
    };

    const truncateText = (text, maxLength) => {
      if (!text) return '';
      return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
    };

    onMounted(() => {
      fetchReferenceData();
      fetchBOM();
    });

    return {
      isEditMode,
      isLoading,
      isSubmitting,
      form,
      componentForm,
      errors,
      componentErrors,
      items,
      uoms,
      showAddComponentModal,
      showEditComponentModal,
      editingComponentIndex,
      itemSearch,
      showDropdown,
      componentItemSearch,
      showComponentDropdown,
      filteredItems,
      filteredComponentItems,
      selectedItemUOM,
      selectedComponentItemUOM,
      selectItem,
      hideDropdown,
      selectComponentItem,
      hideComponentDropdown,
      openAddComponentModal,
      editComponent,
      cancelComponentModal,
      saveComponent,
      removeComponent,
      submitForm,
      cancelForm,
      getItemName,
      getUomSymbol,
      formatNumber,
      formatPercentage,
      truncateText
    };
  }
};
</script>

<style scoped>
.bom-form-container {
  padding: 1rem;
}

.card {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  margin-bottom: 1.5rem;
}

.card-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--gray-200);
}

.card-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--gray-800);
  margin: 0;
}

.card-body {
  padding: 1.5rem;
}

.form-section {
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid var(--gray-200);
}

.form-section:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.section-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--gray-800);
  margin-bottom: 1.25rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
}

.form-group {
  margin-bottom: 1.25rem;
}

.form-group label {
  display: block;
  font-weight: 600;
  color: var(--gray-700);
  margin-bottom: 0.5rem;
}

.required {
  color: var(--danger-color);
}

.form-control {
  width: 100%;
  padding: 0.625rem;
  border: 1px solid var(--gray-300);
  border-radius: 0.375rem;
  font-size: 0.875rem;
  transition: border-color 0.2s, box-shadow 0.2s;
  background-color: white;
}

.form-control:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
  outline: none;
}

.form-control.is-invalid {
  border-color: var(--danger-color);
}

.invalid-feedback {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.75rem;
  color: var(--danger-color);
}

.form-control:disabled {
  background-color: var(--gray-100);
  opacity: 0.75;
}

.form-check {
  display: flex;
  align-items: flex-start;
  margin-bottom: 0.5rem;
}

.form-check-input {
  margin-top: 0.25rem;
  margin-right: 0.5rem;
}

.form-check-label {
  font-weight: 600;
}

.form-text {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.75rem;
  color: var(--gray-500);
}

.yield-based-fields {
  background-color: rgba(14, 165, 233, 0.05);
  padding: 1rem;
  margin: 1rem 0;
  border-radius: 0.375rem;
  border-left: 4px solid #0ea5e9;
}

.table-container {
  overflow-x: auto;
  margin-top: 1rem;
}

.table {
  width: 100%;
  border-collapse: collapse;
  border: 1px solid var(--gray-200);
  margin-bottom: 1rem;
}

.table th {
  text-align: left;
  padding: 0.75rem 1rem;
  font-weight: 600;
  background-color: var(--gray-100);
  color: var(--gray-700);
  border-bottom: 2px solid var(--gray-200);
  white-space: nowrap;
}

.table td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--gray-200);
  vertical-align: middle;
}

.table tbody tr:hover {
  background-color: var(--gray-50);
}

.action-group {
  display: flex;
  gap: 0.25rem;
  justify-content: center;
}

.action-group .btn {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 2rem;
}

.input-group {
  display: flex;
  align-items: stretch;
  width: 100%;
}

.input-group .form-control {
  flex: 1;
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
}

.input-group-append {
  display: flex;
  align-items: center;
}

.input-group-text {
  padding: 0.625rem 0.75rem;
  background-color: var(--gray-100);
  border: 1px solid var(--gray-300);
  border-left: none;
  border-top-right-radius: 0.375rem;
  border-bottom-right-radius: 0.375rem;
  font-size: 0.875rem;
  color: var(--gray-700);
}

.empty-components {
  text-align: center;
  padding: 3rem 2rem;
  background-color: var(--gray-50);
  border-radius: 0.375rem;
  border: 1px dashed var(--gray-300);
}

.empty-components p {
  color: var(--gray-500);
  margin-bottom: 1rem;
}

.notes-preview {
  display: inline-block;
  max-width: 200px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.text-muted {
  color: var(--gray-500);
}

.text-success {
  color: var(--success-color);
}

.text-info {
  color: #0ea5e9;
}

/* Dropdown Styles */
.dropdown {
  position: relative;
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid var(--gray-300);
  border-top: none;
  border-radius: 0 0 0.375rem 0.375rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  max-height: 250px;
  overflow-y: auto;
  z-index: 100;
}

.dropdown-item {
  padding: 0.75rem 1rem;
  cursor: pointer;
  border-bottom: 1px solid var(--gray-100);
  transition: background-color 0.2s;
}

.dropdown-item:last-child {
  border-bottom: none;
}

.dropdown-item:hover {
  background-color: var(--gray-50);
}

.dropdown-item.text-muted {
  color: var(--gray-500);
  cursor: default;
}

.dropdown-item.text-muted:hover {
  background-color: transparent;
}

.item-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.25rem;
}

.item-info strong {
  font-weight: 600;
  color: var(--gray-800);
}

.item-code {
  color: var(--gray-500);
  font-size: 0.8rem;
}

.item-details {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.8rem;
}

.category {
  color: var(--primary-color);
  font-weight: 500;
}

.stock {
  color: var(--gray-500);
}

/* Modal Styles */
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
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 650px;
  max-height: 90vh;
  overflow-y: auto;
  z-index: 60;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--gray-200);
}

.modal-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--gray-800);
}

.close-btn {
  background: transparent;
  border: none;
  color: var(--gray-500);
  cursor: pointer;
  font-size: 1.25rem;
  transition: color 0.2s;
}

.close-btn:hover {
  color: var(--gray-700);
}

.modal-body {
  padding: 1.5rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--gray-200);
}

.row {
  display: flex;
  flex-wrap: wrap;
  margin: -0.5rem;
}

.col-md-6 {
  flex: 0 0 calc(50% - 1rem);
  padding: 0.5rem;
}

/* Button Styles */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.625rem 1.25rem;
  border: 1px solid transparent;
  border-radius: 0.375rem;
  font-weight: 500;
  font-size: 0.875rem;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary {
  background-color: #2563eb;
  border-color: #2563eb;
  color: white;
}

.btn-primary:hover {
  background-color: #1d4ed8;
  border-color: #1d4ed8;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background-color: #f1f5f9;
  border-color: #cbd5e1;
  color: #334155;
}

.btn-secondary:hover {
  background-color: #e2e8f0;
  border-color: #94a3b8;
}

.btn-info {
  background-color: #0ea5e9;
  border-color: #0ea5e9;
  color: white;
}

.btn-info:hover {
  background-color: #0284c7;
  border-color: #0284c7;
}

.btn-danger {
  background-color: #dc2626;
  border-color: #dc2626;
  color: white;
}

.btn-danger:hover {
  background-color: #b91c1c;
  border-color: #b91c1c;
}

.btn-sm {
  padding: 0.375rem 0.75rem;
  font-size: 0.8rem;
}

.mr-1 {
  margin-right: 0.25rem;
}

@media (max-width: 768px) {
  .row {
    flex-direction: column;
  }

  .col-md-6 {
    flex: 0 0 100%;
  }

  .form-actions {
    flex-direction: column-reverse;
    gap: 0.75rem;
  }

  .form-actions .btn {
    width: 100%;
  }

  .section-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
  }

  .section-header .btn {
    width: 100%;
  }

  .modal-content {
    width: 95%;
    max-height: 80vh;
  }

  .modal-footer {
    flex-direction: column-reverse;
    gap: 0.75rem;
  }

  .modal-footer .btn {
    width: 100%;
  }
}

@media (max-width: 480px) {
  .modal-content {
    width: 95%;
    max-height: 80vh;
  }
}
</style>
