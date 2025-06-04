// src/services/WarehouseZoneService.js
import axios from "axios";

/**
 * Service for warehouse zone and location management
 */
const WarehouseZoneService = {
    /**
     * Get zones for a warehouse
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
     * Get a specific zone
     * @param {Number} warehouseId - Warehouse ID
     * @param {Number} zoneId - Zone ID
     * @returns {Promise} Promise with zone response
     */
    getZoneById: async (warehouseId, zoneId) => {
        try {
            const response = await axios.get(
                `/warehouses/${warehouseId}/zones/${zoneId}`
            );
            return response.data;
        } catch (error) {
            console.error(`Error fetching zone ${zoneId}:`, error);
            throw error;
        }
    },

    /**
     * Create a new zone
     * @param {Number} warehouseId - Warehouse ID
     * @param {Object} zoneData - Zone data
     * @returns {Promise} Promise with created zone response
     */
    createZone: async (warehouseId, zoneData) => {
        try {
            const response = await axios.post(
                `/warehouses/${warehouseId}/zones`,
                zoneData
            );
            return response.data;
        } catch (error) {
            console.error("Error creating zone:", error);
            throw error;
        }
    },

    /**
     * Update a zone
     * @param {Number} warehouseId - Warehouse ID
     * @param {Number} zoneId - Zone ID
     * @param {Object} zoneData - Zone data to update
     * @returns {Promise} Promise with updated zone response
     */
    updateZone: async (warehouseId, zoneId, zoneData) => {
        try {
            const response = await axios.put(
                `/warehouses/${warehouseId}/zones/${zoneId}`,
                zoneData
            );
            return response.data;
        } catch (error) {
            console.error(`Error updating zone ${zoneId}:`, error);
            throw error;
        }
    },

    /**
     * Delete a zone
     * @param {Number} warehouseId - Warehouse ID
     * @param {Number} zoneId - Zone ID
     * @returns {Promise} Promise with delete response
     */
    deleteZone: async (warehouseId, zoneId) => {
        try {
            const response = await axios.delete(
                `/warehouses/${warehouseId}/zones/${zoneId}`
            );
            return response.data;
        } catch (error) {
            console.error(`Error deleting zone ${zoneId}:`, error);
            throw error;
        }
    },

    /**
     * Get locations for a zone
     * @param {Number} warehouseId - Warehouse ID
     * @param {Number} zoneId - Zone ID
     * @returns {Promise} Promise with locations response
     */
    getZoneLocations: async (warehouseId, zoneId) => {
        try {
            const response = await axios.get(
                `/warehouses/${warehouseId}/zones/${zoneId}/locations`
            );
            return response.data;
        } catch (error) {
            console.error(
                `Error fetching locations for zone ${zoneId}:`,
                error
            );
            throw error;
        }
    },

    /**
     * Create a new location
     * @param {Number} warehouseId - Warehouse ID
     * @param {Number} zoneId - Zone ID
     * @param {Object} locationData - Location data
     * @returns {Promise} Promise with created location response
     */
    createLocation: async (warehouseId, zoneId, locationData) => {
        try {
            const response = await axios.post(
                `/warehouses/${warehouseId}/zones/${zoneId}/locations`,
                locationData
            );
            return response.data;
        } catch (error) {
            console.error("Error creating location:", error);
            throw error;
        }
    },

    /**
     * Update a location
     * @param {Number} warehouseId - Warehouse ID
     * @param {Number} zoneId - Zone ID
     * @param {Number} locationId - Location ID
     * @param {Object} locationData - Location data to update
     * @returns {Promise} Promise with updated location response
     */
    updateLocation: async (warehouseId, zoneId, locationId, locationData) => {
        try {
            const response = await axios.put(
                `/warehouses/${warehouseId}/zones/${zoneId}/locations/${locationId}`,
                locationData
            );
            return response.data;
        } catch (error) {
            console.error(`Error updating location ${locationId}:`, error);
            throw error;
        }
    },

    /**
     * Delete a location
     * @param {Number} warehouseId - Warehouse ID
     * @param {Number} zoneId - Zone ID
     * @param {Number} locationId - Location ID
     * @returns {Promise} Promise with delete response
     */
    deleteLocation: async (warehouseId, zoneId, locationId) => {
        try {
            const response = await axios.delete(
                `/warehouses/${warehouseId}/zones/${zoneId}/locations/${locationId}`
            );
            return response.data;
        } catch (error) {
            console.error(`Error deleting location ${locationId}:`, error);
            throw error;
        }
    },
};

export default WarehouseZoneService;
