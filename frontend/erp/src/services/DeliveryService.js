// src/services/DeliveryService.js
import axios from "axios";

/**
 * Service for delivery management operations
 */
const DeliveryService = {
    /**
     * Get all deliveries with optional parameters
     * @param {Object} params - Query parameters (pagination, filters, etc.)
     * @returns {Promise} Promise with deliveries response
     */
    getDeliveries: async (params = {}) => {
        try {
            const response = await axios.get("/deliveries", { params });
            return response.data;
        } catch (error) {
            console.error("Error fetching deliveries:", error);
            throw error;
        }
    },

    /**
     * Get delivery by ID
     * @param {Number} id - Delivery ID
     * @returns {Promise} Promise with delivery response
     */
    getDeliveryById: async (id) => {
        try {
            const response = await axios.get(`/deliveries/${id}`);
            return response.data;
        } catch (error) {
            console.error(`Error fetching delivery ${id}:`, error);
            throw error;
        }
    },

    /**
     * Create a new delivery
     * @param {Object} deliveryData - Delivery data
     * @returns {Promise} Promise with created delivery response
     */
    createDelivery: async (deliveryData) => {
        try {
            const response = await axios.post("/deliveries", deliveryData);
            return response.data;
        } catch (error) {
            console.error("Error creating delivery:", error);
            throw error;
        }
    },

    /**
     * Update an existing delivery
     * @param {Number} id - Delivery ID
     * @param {Object} deliveryData - Delivery data to update
     * @returns {Promise} Promise with updated delivery response
     */
    updateDelivery: async (id, deliveryData) => {
        try {
            const response = await axios.put(`/deliveries/${id}`, deliveryData);
            return response.data;
        } catch (error) {
            console.error(`Error updating delivery ${id}:`, error);
            throw error;
        }
    },

    /**
     * Delete a delivery
     * @param {Number} id - Delivery ID
     * @returns {Promise} Promise with delete response
     */
    deleteDelivery: async (id) => {
        try {
            const response = await axios.delete(`/deliveries/${id}`);
            return response.data;
        } catch (error) {
            console.error(`Error deleting delivery ${id}:`, error);
            throw error;
        }
    },

    /**
     * Complete a delivery
     * @param {Number} id - Delivery ID
     * @returns {Promise} Promise with complete delivery response
     */
    completeDelivery: async (id) => {
        try {
            const response = await axios.post(`/deliveries/${id}/complete`);
            return response.data;
        } catch (error) {
            console.error(`Error completing delivery ${id}:`, error);
            throw error;
        }
    },

    /**
     * Get warehouses for delivery
     * @returns {Promise} Promise with warehouses response
     */
    getWarehouses: async () => {
        try {
            const response = await axios.get("/warehouses");
            return response.data;
        } catch (error) {
            console.error("Error fetching warehouses:", error);
            throw error;
        }
    },

    /**
     * Get all available sales orders for delivery
     * @returns {Promise} Promise with sales orders response
     */
    getAvailableSalesOrders: async () => {
        try {
            const response = await axios.get("/orders", {
                params: { status: "Confirmed" },
            });
            return response.data;
        } catch (error) {
            console.error("Error fetching available sales orders:", error);
            throw error;
        }
    },
};

export default DeliveryService;
