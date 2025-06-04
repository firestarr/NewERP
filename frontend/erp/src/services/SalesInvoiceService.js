// src/services/SalesInvoiceService.js
import axios from "axios";

/**
 * Service for Sales Invoice operations
 */
const SalesInvoiceService = {
    /**
     * Get all sales invoices
     * @param {Object} params - Query parameters
     * @returns {Promise} Promise with sales invoices
     */
    getInvoices: async (params = {}) => {
        try {
            const response = await axios.get("/sales/invoices", { params });
            return response.data;
        } catch (error) {
            console.error("Error fetching sales invoices:", error);
            throw error;
        }
    },

    /**
     * Get a sales invoice by ID
     * @param {Number} id - Sales invoice ID
     * @returns {Promise} Promise with sales invoice
     */
    getInvoiceById: async (id) => {
        try {
            const response = await axios.get(`/sales/invoices/${id}`);
            return response.data;
        } catch (error) {
            console.error(`Error fetching sales invoice ${id}:`, error);
            throw error;
        }
    },

    /**
     * Create a new sales invoice
     * @param {Object} invoiceData - Sales invoice data
     * @returns {Promise} Promise with created sales invoice
     */
    createInvoice: async (invoiceData) => {
        try {
            const response = await axios.post("/sales/invoices", invoiceData);
            return response.data;
        } catch (error) {
            console.error("Error creating sales invoice:", error);
            throw error;
        }
    },

    /**
     * Update a sales invoice
     * @param {Number} id - Sales invoice ID
     * @param {Object} invoiceData - Sales invoice data to update
     * @returns {Promise} Promise with updated sales invoice
     */
    updateInvoice: async (id, invoiceData) => {
        try {
            const response = await axios.put(
                `/sales/invoices/${id}`,
                invoiceData
            );
            return response.data;
        } catch (error) {
            console.error(`Error updating sales invoice ${id}:`, error);
            throw error;
        }
    },

    /**
     * Delete a sales invoice
     * @param {Number} id - Sales invoice ID
     * @returns {Promise} Promise with delete response
     */
    deleteInvoice: async (id) => {
        try {
            const response = await axios.delete(`/sales/invoices/${id}`);
            return response.data;
        } catch (error) {
            console.error(`Error deleting sales invoice ${id}:`, error);
            throw error;
        }
    },

    /**
     * Update a sales invoice status
     * @param {Number} id - Sales invoice ID
     * @param {String} status - New status
     * @returns {Promise} Promise with updated sales invoice
     */
    updateStatus: async (id, status) => {
        try {
            const response = await axios.put(`/sales/invoices/${id}/status`, {
                status,
            });
            return response.data;
        } catch (error) {
            console.error(`Error updating sales invoice ${id} status:`, error);
            throw error;
        }
    },

    /**
     * Record a payment for a sales invoice
     * @param {Number} id - Sales invoice ID
     * @param {Object} paymentData - Payment data
     * @returns {Promise} Promise with payment response
     */
    recordPayment: async (id, paymentData) => {
        try {
            const response = await axios.post(
                `/sales/invoices/${id}/payments`,
                paymentData
            );
            return response.data;
        } catch (error) {
            console.error(
                `Error recording payment for sales invoice ${id}:`,
                error
            );
            throw error;
        }
    },

    /**
     * Get payment history for a sales invoice
     * @param {Number} id - Sales invoice ID
     * @returns {Promise} Promise with payment history
     */
    getPaymentHistory: async (id) => {
        try {
            const response = await axios.get(`/sales/invoices/${id}/payments`);
            return response.data;
        } catch (error) {
            console.error(
                `Error fetching payment history for sales invoice ${id}:`,
                error
            );
            throw error;
        }
    },

    /**
     * Create a sales invoice from a sales order
     * @param {Number} orderId - Sales order ID
     * @param {Object} invoiceData - Additional invoice data
     * @returns {Promise} Promise with created sales invoice
     */
    createFromOrder: async (orderId, invoiceData) => {
        try {
            const response = await axios.post(
                `/sales/orders/${orderId}/invoices`,
                invoiceData
            );
            return response.data;
        } catch (error) {
            console.error(
                `Error creating sales invoice from order ${orderId}:`,
                error
            );
            throw error;
        }
    },
};

export default SalesInvoiceService;
