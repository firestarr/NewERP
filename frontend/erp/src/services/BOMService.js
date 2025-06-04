// src/services/BOMService.js
import axios from "axios";

/**
 * Service for Bill of Materials (BOM) operations
 */
const BOMService = {
    /**
     * Get all BOMs
     * @param {object} params Query parameters
     * @returns {Promise} Promise with BOMs response
     */
    getBOMs: async (params = {}) => {
        try {
            const response = await axios.get("/boms", { params });
            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Get a BOM by ID
     * @param {number} id BOM ID
     * @returns {Promise} Promise with BOM response
     */
    getBOMById: async (id) => {
        try {
            const response = await axios.get(`/boms/${id}`);
            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Create a new BOM
     * @param {object} bomData BOM data
     * @returns {Promise} Promise with create BOM response
     */
    createBOM: async (bomData) => {
        try {
            const response = await axios.post("/boms", bomData);
            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Update a BOM
     * @param {number} id BOM ID
     * @param {object} bomData BOM data to update
     * @returns {Promise} Promise with update BOM response
     */
    updateBOM: async (id, bomData) => {
        try {
            const response = await axios.put(`/boms/${id}`, bomData);
            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Delete a BOM
     * @param {number} id BOM ID
     * @returns {Promise} Promise with delete BOM response
     */
    deleteBOM: async (id) => {
        try {
            const response = await axios.delete(`/boms/${id}`);
            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Get BOM lines for a specific BOM
     * @param {number} bomId BOM ID
     * @returns {Promise} Promise with BOM lines response
     */
    getBOMLines: async (bomId) => {
        try {
            const response = await axios.get(`/boms/${bomId}/lines`);
            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Create a new BOM line
     * @param {number} bomId BOM ID
     * @param {object} lineData Line data
     * @returns {Promise} Promise with create BOM line response
     */
    createBOMLine: async (bomId, lineData) => {
        try {
            const response = await axios.post(`/boms/${bomId}/lines`, lineData);
            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Update a BOM line
     * @param {number} bomId BOM ID
     * @param {number} lineId Line ID
     * @param {object} lineData Line data to update
     * @returns {Promise} Promise with update BOM line response
     */
    updateBOMLine: async (bomId, lineId, lineData) => {
        try {
            const response = await axios.put(
                `/boms/${bomId}/lines/${lineId}`,
                lineData
            );
            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Delete a BOM line
     * @param {number} bomId BOM ID
     * @param {number} lineId Line ID
     * @returns {Promise} Promise with delete BOM line response
     */
    deleteBOMLine: async (bomId, lineId) => {
        try {
            const response = await axios.delete(
                `/boms/${bomId}/lines/${lineId}`
            );
            return response.data;
        } catch (error) {
            throw error;
        }
    },
};

export default BOMService;
