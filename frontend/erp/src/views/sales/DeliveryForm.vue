<!-- src/views/sales/DeliveryForm.vue -->
<template>
  <div class="delivery-form">
    <div class="page-header">
      <h1>{{ isEditMode ? 'Edit Pengiriman' : 'Buat Pengiriman Baru' }}</h1>
      <div class="page-actions">
        <button class="btn btn-secondary" @click="goBack">
          <i class="fas fa-arrow-left"></i> Kembali
        </button>
        <button class="btn btn-primary" @click="saveDelivery" :disabled="isSubmitting">
          <i class="fas fa-save"></i> {{ isSubmitting ? 'Menyimpan...' : 'Simpan' }}
        </button>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger">
      {{ error }}
    </div>

    <div class="form-container">
      <div class="form-card">
        <div class="card-header">
          <h2>Informasi Pengiriman</h2>
        </div>
        <div class="card-body">
          <div class="form-row">
            <div class="form-group">
              <label for="delivery_number">Nomor Pengiriman*</label>
              <input
                type="text"
                id="delivery_number"
                v-model="form.delivery_number"
                required
                :readonly="isEditMode"
                :class="{ 'readonly': isEditMode }"
              />
              <small v-if="isEditMode" class="text-muted">Nomor pengiriman tidak dapat diubah</small>
            </div>

            <div class="form-group">
              <label for="delivery_date">Tanggal Pengiriman*</label>
              <input
                type="date"
                id="delivery_date"
                v-model="form.delivery_date"
                required
              />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="so_id">Sales Order*</label>
              <select id="so_id" v-model="form.so_id" required @change="loadSalesOrderDetails">
                <option value="">-- Pilih Sales Order --</option>
                <option v-for="so in salesOrders" :key="so.so_id" :value="so.so_id">
                  {{ so.so_number }} - {{ so.customer.name }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label for="shipping_method">Metode Pengiriman</label>
              <select id="shipping_method" v-model="form.shipping_method">
                <option value="">-- Pilih Metode --</option>
                <option value="Road">Darat</option>
                <option value="Sea">Laut</option>
                <option value="Air">Udara</option>
                <option value="Express">Express</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="tracking_number">Nomor Pelacakan</label>
              <input
                type="text"
                id="tracking_number"
                v-model="form.tracking_number"
                placeholder="Nomor pelacakan pengiriman"
              />
            </div>

            <div class="form-group" v-if="isEditMode">
              <label for="status">Status*</label>
              <select id="status" v-model="form.status" required>
                <option value="Pending">Menunggu</option>
                <option value="In Transit">Dalam Pengiriman</option>
                <option value="Completed">Selesai</option>
                <option value="Cancelled">Dibatalkan</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="form-card">
        <div class="card-header">
          <h2>Customer Information</h2>
        </div>
        <div class="card-body" v-if="selectedCustomer">
          <div class="customer-info">
            <div class="info-group">
              <label>Nama Pelanggan</label>
              <div class="info-value">{{ selectedCustomer.name }}</div>
            </div>

            <div class="info-group">
              <label>Kode Pelanggan</label>
              <div class="info-value">{{ selectedCustomer.customer_code }}</div>
            </div>

            <div class="info-group">
              <label>Alamat</label>
              <div class="info-value">{{ selectedCustomer.address || '-' }}</div>
            </div>

            <div class="info-group">
              <label>Contact Person</label>
              <div class="info-value">{{ selectedCustomer.contact_person || '-' }}</div>
            </div>

            <div class="info-group">
              <label>Telepon</label>
              <div class="info-value">{{ selectedCustomer.phone || '-' }}</div>
            </div>
          </div>
        </div>
        <div class="card-body empty-info" v-else>
          <p>Pilih Sales Order terlebih dahulu untuk melihat informasi pelanggan</p>
        </div>
      </div>

      <div class="form-card">
        <div class="card-header">
          <h2>Item Pengiriman</h2>
        </div>
        <div class="card-body">
          <div v-if="form.lines.length === 0" class="empty-lines">
            <p>Pilih Sales Order terlebih dahulu untuk menambahkan item pengiriman.</p>
          </div>

          <div v-else class="delivery-lines">
            <div class="line-headers">
              <div class="line-header">Item</div>
              <div class="line-header">Jumlah Dipesan</div>
              <div class="line-header">Jumlah Terkirim</div>
              <div class="line-header">Sisa Outstanding</div>
              <div class="line-header">Jumlah Dikirim</div>
              <div class="line-header">Gudang</div>
              <div class="line-header">Batch Number</div>
              <div class="line-header">Aksi</div>
            </div>

            <div
              v-for="(line, index) in form.lines"
              :key="index"
              class="delivery-line"
            >
              <div class="line-item">
                <div class="item-info" v-if="line.item">
                <div class="item-code">{{ line.item.itemCode }}</div>
                <div class="item-name">{{ line.item.name }}</div>
                </div>
                <div v-else>-</div>
              </div>

              <div class="line-item">
                {{ line.ordered_quantity || 0 }}
              </div>

              <div class="line-item">
                {{ line.previously_delivered_quantity || 0 }}
              </div>

              <div class="line-item">
                <strong>{{ getOutstandingQuantity(line) }}</strong>
              </div>

              <div class="line-item">
                <input
                  type="number"
                  v-model="line.delivered_quantity"
                  min="0"
                  :max="getOutstandingQuantity(line)"
                  step="0.01"
                  required
                  @input="validateDeliveredQuantity(line)"
                />
                <small v-if="line.quantityError" class="error-message">
                  {{ line.quantityError }}
                </small>
              </div>

              <div class="line-item">
                <select v-model="line.warehouse_id" required>
                  <option value="">-- Pilih Gudang --</option>
                  <option v-for="warehouse in warehouses" :key="warehouse.warehouse_id" :value="warehouse.warehouse_id">
                    {{ warehouse.name }}
                  </option>
                </select>
              </div>

              <div class="line-item">
                <input
                  type="text"
                  v-model="line.batch_number"
                  placeholder="Batch Number (opsional)"
                />
              </div>

              <div class="line-item actions">
                <button
                  type="button"
                  class="btn-icon delete-btn"
                  title="Hapus Item"
                  @click="removeLine(index)"
                >
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="form-actions">
        <button type="button" class="btn btn-secondary" @click="goBack">
          Batal
        </button>
        <button type="button" class="btn btn-primary" @click="saveDelivery" :disabled="isSubmitting">
          {{ isSubmitting ? 'Menyimpan...' : 'Simpan Pengiriman' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';

export default {
  name: 'DeliveryForm',
  setup() {
    const router = useRouter();
    const route = useRoute();

    // Form data
    const form = ref({
      delivery_number: '',
      delivery_date: new Date().toISOString().substr(0, 10),
      so_id: '',
      customer_id: '',
      status: 'Pending',
      shipping_method: '',
      tracking_number: '',
      lines: []
    });

    // Reference data
    const salesOrders = ref([]);
    const warehouses = ref([]);
    const selectedCustomer = ref(null);

    // UI state
    const isLoading = ref(false);
    const isSubmitting = ref(false);
    const error = ref('');

    // Check if we're in edit mode
    const isEditMode = computed(() => {
      return route.params.id !== undefined;
    });

    // Generate a unique delivery number for new deliveries
    const generateDeliveryNumber = () => {
      const today = new Date();
      const year = today.getFullYear().toString().slice(-2);
      const month = (today.getMonth() + 1).toString().padStart(2, '0');
      const day = today.getDate().toString().padStart(2, '0');
      const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');

      return `DO${year}${month}${day}-${random}`;
    };

    // Load reference data
    const loadReferenceData = async () => {
  try {
    console.log('🔄 Loading outstanding sales orders...');

    // Load sales orders that have outstanding items
    const soResponse = await axios.get('/deliveries/outstanding-so');

    // ⭐ TRANSFORM: Convert flat structure to nested structure
    const rawData = soResponse.data.data || [];
    console.log('📡 Raw response:', rawData);

    salesOrders.value = rawData.map(order => ({
      so_id: order.so_id,
      so_number: order.so_number,
      // Transform flat customer_name to nested customer.name
      customer: {
        name: order.customer_name || 'Unknown Customer',
        customer_code: order.customer_id || ''
      }
    }));

    console.log('✨ Transformed orders:', salesOrders.value);

    // Load warehouses
    const warehouseResponse = await axios.get('/warehouses');
    warehouses.value = warehouseResponse.data.data || [];

  } catch (err) {
    console.error('💥 Error loading reference data:', err);

    // Better error message based on error type
    if (err.response?.status === 404) {
      error.value = 'Endpoint tidak ditemukan. Pastikan route sudah diperbaiki.';
    } else if (err.response?.status === 400) {
      error.value = 'Invalid delivery ID error. Periksa urutan route.';
    } else {
      error.value = `Terjadi kesalahan: ${err.response?.data?.message || err.message}`;
    }

    // Set empty fallbacks
    salesOrders.value = [];
    warehouses.value = [];
  }
};

    // Load delivery data if in edit mode
    const loadDelivery = async () => {
      if (!isEditMode.value) {
        // Generate a delivery number for new deliveries
        form.value.delivery_number = generateDeliveryNumber();
        return;
      }

      isLoading.value = true;
      error.value = '';

      try {
        const response = await axios.get(`/deliveries/${route.params.id}`);
        let delivery = response.data.data;

        // Convert delivery object to camelCase
        delivery = toCamelCase(delivery);

        // Set form data
        form.value = {
          delivery_id: delivery.deliveryId,
          delivery_number: delivery.deliveryNumber,
          delivery_date: delivery.deliveryDate.substr(0, 10),
          so_id: delivery.soId,
          customer_id: delivery.customerId,
          status: delivery.status,
          shipping_method: delivery.shippingMethod || '',
          tracking_number: delivery.trackingNumber || '',
          lines: []
        };

        // Set selected customer
        selectedCustomer.value = delivery.customer;

        // Set line items
        if (delivery.deliveryLines && delivery.deliveryLines.length > 0) {
          console.log('Raw deliveryLines:', delivery.deliveryLines);
          const camelDeliveryLines = toCamelCase(delivery.deliveryLines);
          console.log('CamelCase deliveryLines:', camelDeliveryLines);
          form.value.lines = camelDeliveryLines.map(line => ({
            line_id: line.lineId,
            so_line_id: line.soLineId,
            item_id: line.itemId,
            item: line.item,
            delivered_quantity: line.deliveredQuantity,
            warehouse_id: line.warehouseId,
            batch_number: line.batchNumber || ''
          }));
          console.log('Mapped form lines:', form.value.lines);

          // Fetch item details for lines missing item info
          await Promise.all(form.value.lines.map(async (line) => {
            if (!line.item) {
              try {
                const response = await axios.get(`/items/${line.item_id}`);
                line.item = toCamelCase(response.data.data);
                console.log(`Fetched item for item_id ${line.item_id}:`, line.item);
              } catch (err) {
                console.error(`Error loading item details for item_id ${line.item_id}:`, err);
              }
            }
          }));
          console.log('Final form lines after fetching items:', form.value.lines);
        }

        // Load sales order details to get item information
        if (!isEditMode.value) {
          await loadSalesOrderDetails();
        } else {
          console.log('Edit mode: skipping loadSalesOrderDetails to avoid overwriting lines');
          console.log('Current form lines:', form.value.lines);

          // Fetch outstanding quantities for existing lines
          if (form.value.so_id) {
            await fetchOutstandingItems(form.value.so_id);
          }
        }
      } catch (err) {
        console.error('Error loading delivery:', err);
        error.value = 'Terjadi kesalahan saat memuat data pengiriman.';
      } finally {
        isLoading.value = false;
      }
    };

    // Load sales order details
    const toCamelCase = (obj) => {
      if (Array.isArray(obj)) {
        return obj.map(v => toCamelCase(v));
      } else if (obj !== null && obj.constructor === Object) {
        return Object.keys(obj).reduce((result, key) => {
          const camelKey = key.replace(/_([a-z])/g, g => g[1].toUpperCase());
          result[camelKey] = toCamelCase(obj[key]);
          return result;
        }, {});
      }
      return obj;
    };

    // Fetch outstanding items for a sales order
    const fetchOutstandingItems = async (soId) => {
      try {
        const response = await axios.get(`/deliveries/outstanding-items/${soId}`);
        const outstandingData = response.data.data;

        // Map outstanding data to existing lines or add new lines
        if (outstandingData && outstandingData.outstanding_items) {
          outstandingData.outstanding_items.forEach(outstandingItem => {
            const existingLine = form.value.lines.find(line =>
              line.so_line_id === outstandingItem.so_line_id
            );

            if (existingLine) {
              // Update existing line with outstanding data
              existingLine.ordered_quantity = outstandingItem.ordered_quantity;
              existingLine.previously_delivered_quantity = outstandingItem.delivered_quantity;
              existingLine.outstanding_quantity = outstandingItem.outstanding_quantity;
              existingLine.warehouse_stocks = outstandingItem.warehouse_stocks;
            }
          });
        }
      } catch (err) {
        console.error('Error fetching outstanding items:', err);
        error.value = 'Terjadi kesalahan saat memuat data outstanding items.';
      }
    };

    const loadSalesOrderDetails = async () => {
      if (!form.value.so_id) {
        selectedCustomer.value = null;
        form.value.lines = [];
        return;
      }

      try {
        // First load basic sales order details
        const response = await axios.get(`/orders/${form.value.so_id}`);
        let salesOrder = response.data.data;

        // Convert keys to camelCase
        salesOrder = toCamelCase(salesOrder);

        // Set customer
        selectedCustomer.value = salesOrder.customer;
        form.value.customer_id = salesOrder.customerId;

        // Now fetch outstanding items for delivery
        await fetchOutstandingItems(form.value.so_id);

        // Create lines based on outstanding items
        const outstandingResponse = await axios.get(`/deliveries/outstanding-items/${form.value.so_id}`);
        const outstandingData = outstandingResponse.data.data;

        if (outstandingData && outstandingData.outstanding_items) {
          // If this is a new delivery, set up line items from outstanding items
          if (!isEditMode.value) {
            form.value.lines = outstandingData.outstanding_items.map(item => ({
              so_line_id: item.so_line_id,
              item_id: item.item_id,
              item: {
                itemCode: item.item_code,
                name: item.item_name
              },
              ordered_quantity: item.ordered_quantity,
              previously_delivered_quantity: item.delivered_quantity,
              outstanding_quantity: item.outstanding_quantity,
              delivered_quantity: 0,
              warehouse_id: '',
              batch_number: '',
              warehouse_stocks: item.warehouse_stocks || []
            }));
          }
        }
      } catch (err) {
        console.error('Error loading sales order details:', err);
        error.value = 'Terjadi kesalahan saat memuat detail sales order.';
      }
    };

    // Get outstanding quantity for a line
    const getOutstandingQuantity = (line) => {
      if (line.outstanding_quantity !== undefined) {
        return line.outstanding_quantity;
      }

      // Calculate if not directly provided
      const ordered = parseFloat(line.ordered_quantity || 0);
      const delivered = parseFloat(line.previously_delivered_quantity || 0);
      return ordered - delivered;
    };

    // Validate delivered quantity against outstanding
    const validateDeliveredQuantity = (line) => {
      const delivered = parseFloat(line.delivered_quantity || 0);
      const outstanding = getOutstandingQuantity(line);

      if (delivered <= 0) {
        line.quantityError = 'Jumlah harus lebih dari 0';
      } else if (delivered > outstanding) {
        line.delivered_quantity = outstanding; // Cap at maximum outstanding
        line.quantityError = `Jumlah tidak boleh melebihi sisa outstanding (${outstanding})`;
      } else {
        line.quantityError = null;
      }
    };

    // Remove a line
    const removeLine = (index) => {
      if (confirm('Apakah Anda yakin ingin menghapus item ini?')) {
        form.value.lines.splice(index, 1);
      }
    };

    // Go back to the previous page
    const goBack = () => {
      router.push('/sales/deliveries');
    };

    // Save the delivery
    const saveDelivery = async () => {
      // Validate form
      if (!form.value.delivery_number || !form.value.delivery_date || !form.value.so_id) {
        error.value = 'Harap isi semua field yang wajib diisi.';
        return;
      }

      // Validate line items
      if (form.value.lines.length === 0) {
        error.value = 'Pengiriman harus memiliki setidaknya satu item.';
        return;
      }

      // Validate line items
      let hasError = false;
      form.value.lines.forEach((line, index) => {
        validateDeliveredQuantity(line);
        if (line.quantityError) {
          hasError = true;
        }

        if (line.delivered_quantity <= 0) {
          line.quantityError = `Jumlah pengiriman harus lebih dari 0`;
          hasError = true;
        }

        if (!line.warehouse_id) {
          error.value = `Gudang harus dipilih untuk item ke-${index + 1}.`;
          hasError = true;
        }
      });

      if (hasError) {
        return;
      }

      isSubmitting.value = true;
      error.value = '';

      try {
        if (isEditMode.value) {
          // Update existing delivery
          await axios.put(`/deliveries/${form.value.delivery_id}`, form.value);
          alert('Pengiriman berhasil diperbarui!');
        } else {
          // Create new delivery
          await axios.post('/deliveries', form.value);
          alert('Pengiriman berhasil dibuat!');
        }

        // Redirect to deliveries list
        router.push('/sales/deliveries');
      } catch (err) {
        console.error('Error saving delivery:', err);

        if (err.response && err.response.data && err.response.data.errors) {
          const errors = err.response.data.errors;
          const firstError = Object.values(errors)[0][0];
          error.value = firstError;
        } else if (err.response && err.response.data && err.response.data.message) {
          error.value = err.response.data.message;
        } else {
          error.value = 'Terjadi kesalahan saat menyimpan pengiriman.';
        }
      } finally {
        isSubmitting.value = false;
      }
    };

    onMounted(async () => {
      await loadReferenceData();
      await loadDelivery();
    });

    return {
      form,
      salesOrders,
      warehouses,
      selectedCustomer,
      isLoading,
      isSubmitting,
      error,
      isEditMode,
      getOutstandingQuantity,
      validateDeliveredQuantity,
      removeLine,
      loadSalesOrderDetails,
      goBack,
      saveDelivery
    };
  }
};
</script>

<style scoped>
.delivery-form {
  padding: 1rem 0;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.page-header h1 {
  font-size: 1.5rem;
  font-weight: 600;
  margin: 0;
  color: #1e293b;
}

.page-actions {
  display: flex;
  gap: 0.75rem;
}

.alert {
  padding: 1rem;
  border-radius: 0.375rem;
  margin-bottom: 1.5rem;
}

.alert-danger {
  background-color: #fee2e2;
  color: #b91c1c;
  border: 1px solid #fecaca;
}

.form-container {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-card {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.card-header {
  background-color: #f8fafc;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h2 {
  font-size: 1.125rem;
  font-weight: 600;
  margin: 0;
  color: #1e293b;
}

.card-body {
  padding: 1.5rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #334155;
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

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: 2px solid #2563eb;
  outline-offset: 1px;
}

.form-group input.readonly {
  background-color: #f8fafc;
  cursor: not-allowed;
}

.text-muted {
  color: #64748b;
  font-size: 0.75rem;
  margin-top: 0.25rem;
}

.customer-info {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
}

.info-group {
  margin-bottom: 0.75rem;
}

.info-group label {
  display: block;
  font-size: 0.75rem;
  color: #64748b;
  margin-bottom: 0.25rem;
}

.info-value {
  font-size: 0.875rem;
  color: #1e293b;
  font-weight: 500;
}

.empty-info {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 2rem 0;
  color: #64748b;
  text-align: center;
}

.empty-lines {
  background-color: #f8fafc;
  padding: 2rem;
  border-radius: 0.375rem;
  text-align: center;
  color: #64748b;
  border: 1px dashed #cbd5e1;
}

.delivery-lines {
  border: 1px solid #e2e8f0;
  border-radius: 0.375rem;
  overflow: hidden;
}

.line-headers {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr 1fr 0.5fr;
  gap: 0.5rem;
  background-color: #f8fafc;
  padding: 0.75rem 1rem;
  font-weight: 500;
  color: #475569;
  border-bottom: 1px solid #e2e8f0;
}

.line-header {
  font-size: 0.75rem;
}

.delivery-line {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr 1fr 0.5fr;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #e2e8f0;
  align-items: center;
}

.delivery-line:last-child {
  border-bottom: none;
}

.line-item input,
.line-item select {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.25rem;
  font-size: 0.875rem;
}

.item-info {
  display: flex;
  flex-direction: column;
}

.item-code {
  font-size: 0.75rem;
  color: #64748b;
}

.item-name {
  font-weight: 500;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 1rem;
}

.btn {
  padding: 0.625rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 0.375rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  border: none;
  transition: background-color 0.2s, color 0.2s;
}

.btn-primary {
  background-color: #2563eb;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: #1d4ed8;
}

.btn-primary:disabled {
  background-color: #93c5fd;
  cursor: not-allowed;
}

.btn-secondary {
  background-color: #e2e8f0;
  color: #1e293b;
}

.btn-secondary:hover {
  background-color: #cbd5e1;
}

.btn-icon {
  background: none;
  border: none;
  color: #64748b;
  cursor: pointer;
  padding: 0.375rem;
  border-radius: 0.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background-color 0.2s, color 0.2s;
}

.btn-icon:hover {
  background-color: #f1f5f9;
}

.delete-btn {
  color: #64748b;
}

.delete-btn:hover {
  color: #dc2626;
  background-color: #fee2e2;
}

.line-item.actions {
  display: flex;
  justify-content: center;
}

.error-message {
  color: #dc2626;
  font-size: 0.75rem;
  display: block;
  margin-top: 0.25rem;
}

@media (max-width: 1024px) {
  .form-row {
    grid-template-columns: 1fr;
    gap: 0;
  }

  .delivery-line,
  .line-headers {
    grid-template-columns: repeat(8, 1fr);
    font-size: 0.75rem;
    padding: 0.5rem;
  }
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .customer-info {
    grid-template-columns: 1fr;
  }

  .delivery-line,
  .line-headers {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding: 1rem;
  }

  .line-header {
    display: none;
  }

  .line-item {
    display: flex;
    align-items: center;
    width: 100%;
  }

  .line-item::before {
    content: attr(data-label);
    font-weight: 500;
    width: 8rem;
    text-align: left;
  }

  .line-item.actions {
    justify-content: flex-start;
    margin-top: 0.5rem;
  }

  .line-item.actions::before {
    content: "Aksi";
    font-weight: 500;
    width: 8rem;
    text-align: left;
  }
}
  </style>
