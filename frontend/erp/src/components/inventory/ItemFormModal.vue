<!-- src/components/inventory/ItemFormModal.vue -->
<template>
  <div class="modal">
    <div class="modal-backdrop" @click="$emit('close')"></div>
    <div class="modal-content">
      <div class="modal-header">
        <h2>{{ isEditMode ? 'Edit Item' : 'Add New Item' }}</h2>
        <button class="close-btn" @click="$emit('close')">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="modal-body">
        <form @submit.prevent="submitForm" enctype="multipart/form-data">
          <div class="form-row">
            <div class="form-group">
              <label for="item_code">Item Code*</label>
              <input
                type="text"
                id="item_code"
                v-model="form.item_code"
                required
                :disabled="isEditMode"
                :class="{ 'is-invalid': errors.item_code }"
              />
              <span v-if="errors.item_code" class="error-message">{{ errors.item_code }}</span>
            </div>
            <div class="form-group">
              <label for="name">Name*</label>
              <input
                type="text"
                id="name"
                v-model="form.name"
                required
                :class="{ 'is-invalid': errors.name }"
              />
              <span v-if="errors.name" class="error-message">{{ errors.name }}</span>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="category_id">Item Group</label>
              <select
                id="category_id"
                v-model="form.category_id"
                :class="{ 'is-invalid': errors.category_id }"
              >
                <option value="">-- Select Item Group --</option>
                <option v-for="category in categories" :key="category.category_id" :value="category.category_id">
                  {{ category.name }}
                </option>
              </select>
              <span v-if="errors.category_id" class="error-message">{{ errors.category_id }}</span>
            </div>
            <div class="form-group">
              <label for="uom_id">Unit of Measure</label>
              <select
                id="uom_id"
                v-model="form.uom_id"
                :class="{ 'is-invalid': errors.uom_id }"
              >
                <option value="">-- Select UOM --</option>
                <option v-for="uom in unitOfMeasures" :key="uom.uom_id" :value="uom.uom_id">
                  {{ uom.name }} ({{ uom.symbol }})
                </option>
              </select>
              <span v-if="errors.uom_id" class="error-message">{{ errors.uom_id }}</span>
            </div>
          </div>

          <div class="form-group">
            <label for="description">Description</label>
            <textarea
              id="description"
              v-model="form.description"
              rows="3"
              :class="{ 'is-invalid': errors.description }"
            ></textarea>
            <span v-if="errors.description" class="error-message">{{ errors.description }}</span>
          </div>

          <!-- Physical Properties Section -->
          <div class="form-section">
            <h3 class="section-title">Physical Properties</h3>
            <div class="form-row">
              <div class="form-group">
                <label for="length">Length</label>
                <input
                  type="number"
                  id="length"
                  v-model="form.length"
                  min="0"
                  step="0.01"
                  :class="{ 'is-invalid': errors.length }"
                />
                <span v-if="errors.length" class="error-message">{{ errors.length }}</span>
              </div>
              <div class="form-group">
                <label for="width">Width</label>
                <input
                  type="number"
                  id="width"
                  v-model="form.width"
                  min="0"
                  step="0.01"
                  :class="{ 'is-invalid': errors.width }"
                />
                <span v-if="errors.width" class="error-message">{{ errors.width }}</span>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="thickness">Thickness</label>
                <input
                  type="number"
                  id="thickness"
                  v-model="form.thickness"
                  min="0"
                  step="0.01"
                  :class="{ 'is-invalid': errors.thickness }"
                />
                <span v-if="errors.thickness" class="error-message">{{ errors.thickness }}</span>
              </div>
              <div class="form-group">
                <label for="weight">Weight</label>
                <input
                  type="number"
                  id="weight"
                  v-model="form.weight"
                  min="0"
                  step="0.01"
                  :class="{ 'is-invalid': errors.weight }"
                />
                <span v-if="errors.weight" class="error-message">{{ errors.weight }}</span>
              </div>
            </div>
            <div class="form-group">
              <label for="document">Technical Document</label>
              <input
                type="file"
                id="document"
                ref="documentInput"
                accept=".pdf"
                @change="handleFileUpload"
                :class="{ 'is-invalid': errors.document }"
              />
              <small class="form-text text-muted">Upload PDF file (max 10MB)</small>
              <span v-if="errors.document" class="error-message">{{ errors.document }}</span>
            </div>
          </div>

          <!-- Stock Levels Section -->
          <div class="form-section">
            <h3 class="section-title">Stock Levels</h3>
            <div class="form-row">
              <div class="form-group">
                <label for="minimum_stock">Minimum Stock</label>
                <input
                  type="number"
                  id="minimum_stock"
                  v-model="form.minimum_stock"
                  min="0"
                  step="0.01"
                  :class="{ 'is-invalid': errors.minimum_stock }"
                />
                <span v-if="errors.minimum_stock" class="error-message">{{ errors.minimum_stock }}</span>
              </div>
              <div class="form-group">
                <label for="maximum_stock">Maximum Stock</label>
                <input
                  type="number"
                  id="maximum_stock"
                  v-model="form.maximum_stock"
                  min="0"
                  step="0.01"
                  :class="{ 'is-invalid': errors.maximum_stock }"
                />
                <span v-if="errors.maximum_stock" class="error-message">{{ errors.maximum_stock }}</span>
              </div>
            </div>
          </div>

          <!-- Pricing Section -->
          <div class="form-section">
            <h3 class="section-title">Pricing</h3>
            <div class="form-row">
              <div class="form-group">
                <label for="cost_price">Cost Price</label>
              <input
                type="number"
                id="cost_price"
                v-model="form.cost_price"
                min="0"
                step="0.0001"
                :class="{ 'is-invalid': errors.cost_price }"
              />
                <span v-if="errors.cost_price" class="error-message">{{ errors.cost_price }}</span>
              </div>
              <div class="form-group">
                <label for="cost_price_currency">Currency</label>
                <select
                  id="cost_price_currency"
                  v-model="form.cost_price_currency"
                  :class="{ 'is-invalid': errors.cost_price_currency }"
                >
                  <option v-for="currency in currencies" :key="currency" :value="currency">
                    {{ currency }}
                  </option>
                </select>
                <span v-if="errors.cost_price_currency" class="error-message">{{ errors.cost_price_currency }}</span>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="sale_price">Sale Price</label>
                <!--Update 4 digit / step="0.0001"-->
              <input
                type="number"
                id="sale_price"
                v-model="form.sale_price"
                min="0"
                step="0.0001"
                :class="{ 'is-invalid': errors.sale_price }"
              />
                <span v-if="errors.sale_price" class="error-message">{{ errors.sale_price }}</span>
              </div>
              <div class="form-group">
                <label for="sale_price_currency">Currency</label>
                <select
                  id="sale_price_currency"
                  v-model="form.sale_price_currency"
                  :class="{ 'is-invalid': errors.sale_price_currency }"
                >
                  <option v-for="currency in currencies" :key="currency" :value="currency">
                    {{ currency }}
                  </option>
                </select>
                <span v-if="errors.sale_price_currency" class="error-message">{{ errors.sale_price_currency }}</span>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group checkbox-group">
                <input
                  type="checkbox"
                  id="is_purchasable"
                  v-model="form.is_purchasable"
                />
                <label for="is_purchasable">Purchasable</label>
              </div>
              <div class="form-group checkbox-group">
                <input
                  type="checkbox"
                  id="is_sellable"
                  v-model="form.is_sellable"
                />
                <label for="is_sellable">Sellable</label>
              </div>
            </div>
          </div>

          <div class="form-actions">
            <button type="button" class="btn btn-secondary" @click="$emit('close')">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary">
              {{ isEditMode ? 'Update Item' : 'Add Item' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, watch } from 'vue';

export default {
  name: 'ItemFormModal',
  props: {
    isEditMode: {
      type: Boolean,
      default: false
    },
    itemForm: {
      type: Object,
      required: true
    },
    categories: {
      type: Array,
      default: () => []
    },
    unitOfMeasures: {
      type: Array,
      default: () => []
    }
  },
  emits: ['save', 'close'],
  setup(props, { emit }) {
    // Available currencies
    const currencies = ref(['USD', 'IDR', 'EUR', 'SGD', 'JPY']);

    const form = reactive({
      item_id: props.itemForm.item_id,
      item_code: props.itemForm.item_code,
      name: props.itemForm.name,
      description: props.itemForm.description,
      category_id: props.itemForm.category_id,
      uom_id: props.itemForm.uom_id,
      minimum_stock: props.itemForm.minimum_stock,
      maximum_stock: props.itemForm.maximum_stock,
      length: props.itemForm.length || '',
      width: props.itemForm.width || '',
      thickness: props.itemForm.thickness || '',
      weight: props.itemForm.weight || '',
      is_purchasable: props.itemForm.is_purchasable === true || props.itemForm.is_purchasable === 'true',
      is_sellable: props.itemForm.is_sellable === true || props.itemForm.is_sellable === 'true',
      cost_price: props.itemForm.cost_price || 0,
      sale_price: props.itemForm.sale_price || 0,
      cost_price_currency: props.itemForm.cost_price_currency || 'USD',
      sale_price_currency: props.itemForm.sale_price_currency || 'USD',
      document: null
    });

    const errors = ref({});
    const documentInput = ref(null);

    // Watch for prop changes
    watch(() => props.itemForm, (newForm) => {
      Object.assign(form, {
        ...newForm,
        length: newForm.length || '',
        width: newForm.width || '',
        thickness: newForm.thickness || '',
        weight: newForm.weight || '',
        is_purchasable: newForm.is_purchasable === true || newForm.is_purchasable === 'true',
        is_sellable: newForm.is_sellable === true || newForm.is_sellable === 'true',
        cost_price: newForm.cost_price || 0,
        sale_price: newForm.sale_price || 0,
        cost_price_currency: newForm.cost_price_currency || 'USD',
        sale_price_currency: newForm.sale_price_currency || 'USD',
        document: null
      });
    }, { deep: true });

    const handleFileUpload = (event) => {
      form.document = event.target.files[0] || null;
    };

    // Validate form
    const validateForm = () => {
      errors.value = {};

      if (!form.item_code) {
        errors.value.item_code = 'Item code is required';
      }

      if (!form.name) {
        errors.value.name = 'Name is required';
      }

      if (form.minimum_stock < 0) {
        errors.value.minimum_stock = 'Minimum stock cannot be negative';
      }

      if (form.maximum_stock < 0) {
        errors.value.maximum_stock = 'Maximum stock cannot be negative';
      }

      if (form.maximum_stock < form.minimum_stock) {
        errors.value.maximum_stock = 'Maximum stock must be greater than minimum stock';
      }

      if (form.length && form.length < 0) {
        errors.value.length = 'Length cannot be negative';
      }

      if (form.width && form.width < 0) {
        errors.value.width = 'Width cannot be negative';
      }

      if (form.thickness && form.thickness < 0) {
        errors.value.thickness = 'Thickness cannot be negative';
      }

      if (form.weight && form.weight < 0) {
        errors.value.weight = 'Weight cannot be negative';
      }

      if (form.cost_price < 0) {
        errors.value.cost_price = 'Cost price cannot be negative';
      }

      if (form.sale_price < 0) {
        errors.value.sale_price = 'Sale price cannot be negative';
      }

      if (!form.cost_price_currency || form.cost_price_currency.length !== 3) {
        errors.value.cost_price_currency = 'Currency code must be 3 characters';
      }

      if (!form.sale_price_currency || form.sale_price_currency.length !== 3) {
        errors.value.sale_price_currency = 'Currency code must be 3 characters';
      }

      if (form.document && form.document.size > 10 * 1024 * 1024) {
        errors.value.document = 'Document file size must be less than 10MB';
      }

      return Object.keys(errors.value).length === 0;
    };

    const submitForm = () => {
      if (validateForm()) {
        // Round cost_price and sale_price to 4 decimal places before submission
        form.cost_price = Math.round(form.cost_price * 10000) / 10000; //Update 4 digit
        form.sale_price = Math.round(form.sale_price * 10000) / 10000; //Update 4 digit

        // Create a FormData object to handle file upload
        const formData = new FormData();

        // Add all form fields to the FormData except checkboxes
        Object.keys(form).forEach(key => {
          if (key === 'document' && form[key]) {
            formData.append(key, form[key]);
          } else if (key !== 'is_purchasable' && key !== 'is_sellable' && form[key] !== null && form[key] !== undefined) {
            formData.append(key, form[key]);
          }
        });

        // Explicitly append checkbox fields as string 'true' or 'false'
        formData.append('is_purchasable', form.is_purchasable ? 'true' : 'false');
        formData.append('is_sellable', form.is_sellable ? 'true' : 'false');

        emit('save', formData);
      }
    };

    return {
      form,
      errors,
      currencies,
      documentInput,
      handleFileUpload,
      submitForm
    };
  }
};
</script>

<style scoped>
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
  max-width: 600px;
  max-height: 90vh;
  z-index: 60;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e2e8f0;
  flex-shrink: 0;
}

.modal-header h2 {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0;
  color: #1e293b;
}

.close-btn {
  background: none;
  border: none;
  color: #64748b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.25rem;
  border-radius: 0.25rem;
}

.close-btn:hover {
  background-color: #f1f5f9;
  color: #0f172a;
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #1e293b;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 0.625rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.375rem;
  font-size: 0.875rem;
}

.form-group textarea {
  resize: vertical;
}

.form-group .is-invalid {
  border-color: #ef4444;
}

.error-message {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.75rem;
  color: #ef4444;
}

.form-section {
  margin-top: 1.5rem;
  margin-bottom: 1.5rem;
  border-top: 1px solid #e2e8f0;
  padding-top: 1.5rem;
}

.section-title {
  font-size: 1rem;
  font-weight: 600;
  margin: 0 0 1rem 0;
  color: #1e293b;
}

.checkbox-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.checkbox-group input[type="checkbox"] {
  width: auto;
}

.checkbox-group label {
  margin-bottom: 0;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 1.5rem;
}

@media (max-width: 640px) {
  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>
