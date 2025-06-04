// src/services/VendorService.js
import axios from "axios";

/**
 * Service for vendor management operations
 */
const VendorService = {
    /**
     * Get all vendors with optional pagination and filters
     * @param {Object} params - Query parameters for filtering and pagination
     * @returns {Promise} Promise with vendors response
     */
    getAllVendors: async (params = {}) => {
        try {
            const response = await axios.get("/vendors", { params });

            // Return the response as is, we'll handle the structure in the component
            // This accommodates different axios response structures
            return response.data ? response.data : { data: [] };
        } catch (error) {
            console.error("Error fetching vendors:", error);
            // Return an empty data structure to avoid breaking the component
            return { data: [] };
        }
    },

    /**
     * Get a specific vendor by ID
     * @param {Number} id - Vendor ID
     * @returns {Promise} Promise with vendor response
     */
    getVendorById: async (id) => {
        try {
            const response = await axios.get(`/vendors/${id}`);
            return response.data;
        } catch (error) {
            console.error(`Error fetching vendor ${id}:`, error);
            throw error;
        }
    },

    /**
     * Create a new vendor
     * @param {Object} vendorData - Vendor data
     * @returns {Promise} Promise with create vendor response
     */
    createVendor: async (vendorData) => {
        try {
            const response = await axios.post("/vendors", vendorData);
            return response.data;
        } catch (error) {
            console.error("Error creating vendor:", error);
            throw error;
        }
    },

    /**
     * Update an existing vendor
     * @param {Number} id - Vendor ID
     * @param {Object} vendorData - Vendor data to update
     * @returns {Promise} Promise with update vendor response
     */
    updateVendor: async (id, vendorData) => {
        try {
            const response = await axios.put(`/vendors/${id}`, vendorData);
            return response.data;
        } catch (error) {
            console.error(`Error updating vendor ${id}:`, error);
            throw error;
        }
    },

    /**
     * Delete a vendor
     * @param {Number} id - Vendor ID
     * @returns {Promise} Promise with delete vendor response
     */
    deleteVendor: async (id) => {
        try {
            const response = await axios.delete(`/vendors/${id}`);
            return response.data;
        } catch (error) {
            console.error(`Error deleting vendor ${id}:`, error);
            throw error;
        }
    },

    /**
     * Get vendor evaluations
     * @param {Number} id - Vendor ID
     * @returns {Promise} Promise with vendor evaluations
     */
    getVendorEvaluations: async (id) => {
        try {
            const response = await axios.get(`/vendors/${id}/evaluations`);
            return response.data;
        } catch (error) {
            console.error(
                `Error fetching evaluations for vendor ${id}:`,
                error
            );
            throw error;
        }
    },

    /**
     * Get vendor purchase orders
     * @param {Number} id - Vendor ID
     * @returns {Promise} Promise with vendor purchase orders
     */
    getVendorPurchaseOrders: async (id) => {
        try {
            const response = await axios.get(`/vendors/${id}/purchase-orders`);
            return response.data;
        } catch (error) {
            console.error(
                `Error fetching purchase orders for vendor ${id}:`,
                error
            );
            throw error;
        }
    },
};

export default VendorService;
