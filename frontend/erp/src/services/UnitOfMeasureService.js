// src/services/UnitOfMeasureService.js
import axios from "axios";

/**
 * Service for Unit of Measure operations
 */
const UnitOfMeasureService = {
    /**
     * Get all units of measure
     * @param {Object} params - Query parameters
     * @returns {Promise} Promise with units of measure
     */
    getAll: async (params = {}) => {
        const response = await axios.get("/unit-of-measures", { params });
        return response.data;
    },

    /**
     * Get unit of measure by ID
     * @param {Number} id - Unit of measure ID
     * @returns {Promise} Promise with unit of measure
     */
    getById: async (id) => {
        const response = await axios.get(`/unit-of-measures/${id}`);
        return response.data;
    },

    /**
     * Create a new unit of measure
     * @param {Object} data - Unit of measure data
     * @returns {Promise} Promise with created unit of measure
     */
    create: async (data) => {
        const response = await axios.post("/unit-of-measures", data);
        return response.data;
    },

    /**
     * Update an existing unit of measure
     * @param {Number} id - Unit of measure ID
     * @param {Object} data - Unit of measure data to update
     * @returns {Promise} Promise with updated unit of measure
     */
    update: async (id, data) => {
        const response = await axios.put(`/unit-of-measures/${id}`, data);
        return response.data;
    },

    /**
     * Delete a unit of measure
     * @param {Number} id - Unit of measure ID
     * @returns {Promise} Promise with delete response
     */
    delete: async (id) => {
        const response = await axios.delete(`/unit-of-measures/${id}`);
        return response.data;
    },

    /**
     * Get unit of measure usage data
     * This will check what items are using a specific UOM
     * @param {Number} id - Unit of measure ID
     * @returns {Promise} Promise with usage data
     */
    getUsage: async (id) => {
        const response = await axios.get(`/unit-of-measures/${id}/usage`);
        return response.data;
    },
};

export default UnitOfMeasureService;
