// src/services/SalesReturnService.js
import axios from "axios";

/**
 * Service for Sales Return operations
 */
const SalesReturnService = {
    /**
     * Get all sales returns
     * @param {Object} params - Query parameters
     * @returns {Promise} Promise with sales returns
     */
    getReturns: async (params = {}) => {
        try {
            const response = await axios.get("/sales/returns", { params });
            return response.data;
        } catch (error) {
            console.error("Error fetching sales returns:", error);
            throw error;
        }
    },

    /**
     * Get a sales return by ID
     * @param {Number} id - Sales return ID
     * @returns {Promise} Promise with sales return
     */
    getReturnById: async (id) => {
        try {
            const response = await axios.get(`/sales/returns/${id}`);
            return response.data;
        } catch (error) {
            console.error(`Error fetching sales return ${id}:`, error);
            throw error;
        }
    },

    /**
     * Create a new sales return
     * @param {Object} returnData - Sales return data
     * @returns {Promise} Promise with created sales return
     */
    createReturn: async (returnData) => {
        try {
            const response = await axios.post("/sales/returns", returnData);
            return response.data;
        } catch (error) {
            console.error("Error creating sales return:", error);
            throw error;
        }
    },

    /**
     * Update a sales return
     * @param {Number} id - Sales return ID
     * @param {Object} returnData - Sales return data to update
     * @returns {Promise} Promise with updated sales return
     */
    updateReturn: async (id, returnData) => {
        try {
            const response = await axios.put(
                `/sales/returns/${id}`,
                returnData
            );
            return response.data;
        } catch (error) {
            console.error(`Error updating sales return ${id}:`, error);
            throw error;
        }
    },

    /**
     * Delete a sales return
     * @param {Number} id - Sales return ID
     * @returns {Promise} Promise with delete response
     */
    deleteReturn: async (id) => {
        try {
            const response = await axios.delete(`/sales/returns/${id}`);
            return response.data;
        } catch (error) {
            console.error(`Error deleting sales return ${id}:`, error);
            throw error;
        }
    },

    /**
     * Process a sales return
     * @param {Number} id - Sales return ID
     * @returns {Promise} Promise with process response
     */
    processReturn: async (id) => {
        try {
            const response = await axios.post(`/sales/returns/${id}/process`);
            return response.data;
        } catch (error) {
            console.error(`Error processing sales return ${id}:`, error);
            throw error;
        }
    },

    /**
     * Get invoices for a customer
     * @param {Number} customerId - Customer ID
     * @returns {Promise} Promise with customer invoices
     */
    getCustomerInvoices: async (customerId) => {
        try {
            const response = await axios.get(`/sales/invoices`, {
                params: {
                    customer_id: customerId,
                    status: "Sent,Paid,Partially Paid",
                },
            });
            return response.data;
        } catch (error) {
            console.error(
                `Error fetching invoices for customer ${customerId}:`,
                error
            );
            throw error;
        }
    },

    /**
     * Get invoice details with its lines
     * @param {Number} invoiceId - Invoice ID
     * @returns {Promise} Promise with invoice details
     */
    getInvoiceDetails: async (invoiceId) => {
        try {
            const response = await axios.get(`/sales/invoices/${invoiceId}`);
            return response.data;
        } catch (error) {
            console.error(
                `Error fetching invoice details ${invoiceId}:`,
                error
            );
            throw error;
        }
    },
};

export default SalesReturnService;
