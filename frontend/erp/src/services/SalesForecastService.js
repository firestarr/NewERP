// src/services/SalesForecastService.js
import axios from "axios";

/**
 * Service for Sales Forecast operations
 */
const SalesForecastService = {
    /**
     * Get all sales forecasts
     * @param {object} params Query parameters
     * @returns {Promise} Promise with forecasts response
     */
    getForecasts: async (params = {}) => {
        try {
            const response = await axios.get("/sales-forecasts", { params });
            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Get a sales forecast by ID
     * @param {number} id Forecast ID
     * @returns {Promise} Promise with forecast response
     */
    getForecastById: async (id) => {
        try {
            const response = await axios.get(`/sales-forecasts/${id}`);
            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Create a new sales forecast
     * @param {object} forecastData Forecast data
     * @returns {Promise} Promise with create forecast response
     */
    createForecast: async (forecastData) => {
        try {
            const response = await axios.post("/sales-forecasts", forecastData);
            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Update a sales forecast
     * @param {number} id Forecast ID
     * @param {object} forecastData Forecast data to update
     * @returns {Promise} Promise with update forecast response
     */
    updateForecast: async (id, forecastData) => {
        try {
            const response = await axios.put(
                `/sales-forecasts/${id}`,
                forecastData
            );
            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Delete a sales forecast
     * @param {number} id Forecast ID
     * @returns {Promise} Promise with delete forecast response
     */
    deleteForecast: async (id) => {
        try {
            const response = await axios.delete(`/sales-forecasts/${id}`);
            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Generate sales forecasts based on historical data
     * @param {object} params Generation parameters
     * @returns {Promise} Promise with generation response
     */
    generateForecasts: async (params) => {
        try {
            const response = await axios.post(
                "/sales-forecasts/generate",
                params
            );
            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Update actual quantities in forecasts
     * @param {object} params Update parameters
     * @returns {Promise} Promise with update response
     */
    updateActuals: async (params) => {
        try {
            const response = await axios.post(
                "/sales-forecasts/update-actuals",
                params
            );
            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Get forecast accuracy metrics
     * @param {object} params Query parameters
     * @returns {Promise} Promise with accuracy metrics response
     */
    getForecastAccuracy: async (params) => {
        try {
            const response = await axios.get("/sales-forecasts/accuracy", {
                params,
            });
            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Get historical forecast data for specific item and customer
     * @param {object} params Query parameters
     * @returns {Promise} Promise with historical data response
     */
    getHistoricalData: async (params) => {
        try {
            const response = await axios.get("/sales-forecasts/historical", {
                params,
            });
            return response.data;
        } catch (error) {
            throw error;
        }
    },
};

export default SalesForecastService;
