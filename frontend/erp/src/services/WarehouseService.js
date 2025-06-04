// src/services/WarehouseService.js
import axios from "axios";

/**
 * Service for warehouse management operations
 */
const WarehouseService = {
    /**
     * Get all warehouses with optional filters
     * @param {Object} params - Query parameters for filtering and pagination
     * @returns {Promise} Promise with warehouses response
     */
    getWarehouses: async (params = {}) => {
        try {
            const response = await axios.get("/warehouses", { params });
            return response.data;
        } catch (error) {
            console.error("Error fetching warehouses:", error);
            throw error;
        }
    },

    /**
     * Get warehouse by ID
     * @param {Number} id - Warehouse ID
     * @returns {Promise} Promise with warehouse response
     */
    getWarehouseById: async (id) => {
        try {
            const response = await axios.get(`/warehouses/${id}`);
            return response.data;
        } catch (error) {
            console.error(`Error fetching warehouse ${id}:`, error);
            throw error;
        }
    },

    /**
     * Create a new warehouse
     * @param {Object} warehouseData - Warehouse data
     * @returns {Promise} Promise with created warehouse response
     */
    createWarehouse: async (warehouseData) => {
        try {
            const response = await axios.post("/warehouses", warehouseData);
            return response.data;
        } catch (error) {
            console.error("Error creating warehouse:", error);
            throw error;
        }
    },

    /**
     * Update warehouse
     * @param {Number} id - Warehouse ID
     * @param {Object} warehouseData - Warehouse data to update
     * @returns {Promise} Promise with updated warehouse response
     */
    updateWarehouse: async (id, warehouseData) => {
        try {
            const response = await axios.put(
                `/warehouses/${id}`,
                warehouseData
            );
            return response.data;
        } catch (error) {
            console.error(`Error updating warehouse ${id}:`, error);
            throw error;
        }
    },

    /**
     * Delete warehouse
     * @param {Number} id - Warehouse ID
     * @returns {Promise} Promise with delete response
     */
    deleteWarehouse: async (id) => {
        try {
            const response = await axios.delete(`/warehouses/${id}`);
            return response.data;
        } catch (error) {
            console.error(`Error deleting warehouse ${id}:`, error);
            throw error;
        }
    },

    /**
     * Get warehouse zones
     * @param {Number} warehouseId - Warehouse ID
     * @returns {Promise} Promise with zones response
     */
    getWarehouseZones: async (warehouseId) => {
        try {
            const response = await axios.get(
                `/warehouses/${warehouseId}/zones`
            );
            return response.data;
        } catch (error) {
            console.error(
                `Error fetching zones for warehouse ${warehouseId}:`,
                error
            );
            throw error;
        }
    },

    /**
     * Get warehouse item count
     * @param {Number} warehouseId - Warehouse ID
     * @returns {Promise} Promise with item count response
     */
    getWarehouseItemCount: async (warehouseId) => {
        try {
            const response = await axios.get(
                `/stock-transactions/warehouse/${warehouseId}`
            );
            // Group by item_id to get unique items
            const uniqueItems = new Set(
                response.data.data.map((t) => t.item_id)
            );
            return uniqueItems.size;
        } catch (error) {
            console.error(
                `Error fetching item count for warehouse ${warehouseId}:`,
                error
            );
            return 0; // Return 0 in case of error
        }
    },
};

export default WarehouseService;
