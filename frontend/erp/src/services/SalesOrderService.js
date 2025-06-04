// src/services/SalesOrderService.js
import axios from "axios";

/**
 * Service for sales order operations
 */
const SalesOrderService = {
    /**
     * Get all sales orders
     * @param {object} params Query parameters
     * @returns {Promise} Promise with sales orders response
     */
    getSalesOrders: async (params = {}) => {
        const response = await axios.get("/sales/orders", { params });
        return response.data;
        // try {
        // } catch (error) {
        //     throw error;
        // }
    },

    /**
     * Get a sales order by ID
     * @param {number} id Sales order ID
     * @returns {Promise} Promise with sales order response
     */
    getSalesOrderById: async (id) => {
        const response = await axios.get(`/sales/orders/${id}`);
        return response.data;
        // try {
        // } catch (error) {
        //     throw error;
        // }
    },

    /**
     * Create a new sales order
     * @param {object} orderData Sales order data
     * @returns {Promise} Promise with create sales order response
     */
    createSalesOrder: async (orderData) => {
        const response = await axios.post("/sales/orders", orderData);
        return response.data;
        // try {
        // } catch (error) {
        //     throw error;
        // }
    },

    /**
     * Create a sales order from quotation
     * @param {object} orderData Sales order data with quotation_id
     * @returns {Promise} Promise with create sales order response
     */
    createFromQuotation: async (orderData) => {
        const response = await axios.post(
            "/sales/orders/create-from-quotation",
            orderData
        );
        return response.data;
        // try {
        // } catch (error) {
        //     throw error;
        // }
    },

    /**
     * Update a sales order
     * @param {number} id Sales order ID
     * @param {object} orderData Sales order data to update
     * @returns {Promise} Promise with update sales order response
     */
    updateSalesOrder: async (id, orderData) => {
        const response = await axios.put(`/sales/orders/${id}`, orderData);
        return response.data;
        // try {
        // } catch (error) {
        //     throw error;
        // }
    },

    /**
     * Delete a sales order
     * @param {number} id Sales order ID
     * @returns {Promise} Promise with delete sales order response
     */
    deleteSalesOrder: async (id) => {
        const response = await axios.delete(`/sales/orders/${id}`);
        return response.data;
        // try {
        // } catch (error) {
        //     throw error;
        // }
    },

    /**
     * Add a line to sales order
     * @param {number} orderId Sales order ID
     * @param {object} lineData Line item data
     * @returns {Promise} Promise with add line response
     */
    addOrderLine: async (orderId, lineData) => {
        const response = await axios.post(
            `/sales/orders/${orderId}/lines`,
            lineData
        );
        return response.data;
        // try {
        // } catch (error) {
        //     throw error;
        // }
    },

    /**
     * Update a line in sales order
     * @param {number} orderId Sales order ID
     * @param {number} lineId Line ID
     * @param {object} lineData Line data to update
     * @returns {Promise} Promise with update line response
     */
    updateOrderLine: async (orderId, lineId, lineData) => {
        const response = await axios.put(
            `/sales/orders/${orderId}/lines/${lineId}`,
            lineData
        );
        return response.data;
        // try {
        // } catch (error) {
        //     throw error;
        // }
    },

    /**
     * Remove a line from sales order
     * @param {number} orderId Sales order ID
     * @param {number} lineId Line ID
     * @returns {Promise} Promise with remove line response
     */
    removeOrderLine: async (orderId, lineId) => {
        const response = await axios.delete(
            `/sales/orders/${orderId}/lines/${lineId}`
        );
        return response.data;
        // try {
        // } catch (error) {
        //     throw error;
        // }
    },
};

export default SalesOrderService;
