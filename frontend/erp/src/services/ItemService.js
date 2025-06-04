// src/services/item.service.js
import axios from "axios";

/**
 * Service for item management operations
 */
const ItemService = {
    /**
     * Get all items
     * @param {Object} params - Query parameters
     * @returns {Promise} Promise with items response
     */
    getItems: async (params = {}) => {
        try {
            const response = await axios.get("/items", { params });
            return response.data;
        } catch (error) {
            console.error("Error fetching items:", error);
            throw error;
        }
    },

    /**
     * Get item by ID
     * @param {Number} id - Item ID
     * @returns {Promise} Promise with item response
     */
    getItemById: async (id) => {
        try {
            const response = await axios.get(`/items/${id}`);
            return response.data;
        } catch (error) {
            console.error(`Error fetching item ${id}:`, error);
            throw error;
        }
    },

    /**
     * Create a new item
     * @param {Object|FormData} itemData - Item data or FormData for file uploads
     * @param {Boolean} hasFile - Whether itemData includes file uploads
     * @returns {Promise} Promise with create item response
     */
    createItem: async (itemData, hasFile = false) => {
        try {
            const config = hasFile
                ? {
                      headers: {
                          "Content-Type": "multipart/form-data",
                      },
                  }
                : {};

            const response = await axios.post("/items", itemData, config);
            return response.data;
        } catch (error) {
            console.error("Error creating item:", error);
            throw error;
        }
    },

    /**
     * Update item
     * @param {Number} id - Item ID
     * @param {Object|FormData} itemData - Item data to update
     * @param {Boolean} hasFile - Whether itemData includes file uploads
     * @returns {Promise} Promise with update item response
     */
    updateItem: async (id, itemData, hasFile = false) => {
        try {
            if (hasFile) {
                // Use post with _method=PUT for FormData
                const response = await axios.post(
                    `/items/${id}?_method=PUT`,
                    itemData,
                    {
                        headers: {
                            "Content-Type": "multipart/form-data",
                        },
                    }
                );
                return response.data;
            } else {
                const response = await axios.put(`/items/${id}`, itemData);
                return response.data;
            }
        } catch (error) {
            console.error(`Error updating item ${id}:`, error);
            throw error;
        }
    },

    /**
     * Delete item
     * @param {Number} id - Item ID
     * @returns {Promise} Promise with delete item response
     */
    deleteItem: async (id) => {
        try {
            const response = await axios.delete(`/items/${id}`);
            return response.data;
        } catch (error) {
            console.error(`Error deleting item ${id}:`, error);
            throw error;
        }
    },

    /**
     * Get stock status for all items
     * @returns {Promise} Promise with stock status response
     */
    getStockStatus: async () => {
        try {
            const response = await axios.get("/items/stock-status");
            return response.data;
        } catch (error) {
            console.error("Error fetching stock status:", error);
            throw error;
        }
    },

    /**
     * Get item categories
     * @returns {Promise} Promise with categories response
     */
    getCategories: async () => {
        try {
            const response = await axios.get("/item-categories");
            return response.data;
        } catch (error) {
            console.error("Error fetching categories:", error);
            throw error;
        }
    },

    /**
     * Get units of measure
     * @returns {Promise} Promise with UOM response
     */
    getUnitsOfMeasure: async () => {
        try {
            const response = await axios.get("/unit-of-measures");
            return response.data;
        } catch (error) {
            console.error("Error fetching units of measure:", error);
            throw error;
        }
    },

    /**
     * Get transactions for a specific item
     * @param {Number} itemId - Item ID
     * @param {Object} params - Query parameters
     * @returns {Promise} Promise with transactions response
     */
    getItemTransactions: async (itemId, params = {}) => {
        try {
            const response = await axios.get(
                `/stock-transactions/item/${itemId}`,
                {
                    params,
                }
            );
            return response.data;
        } catch (error) {
            console.error(
                `Error fetching transactions for item ${itemId}:`,
                error
            );
            throw error;
        }
    },

    /**
     * Get prices in multiple currencies
     * @param {Number} id - Item ID
     * @param {Array} currencies - List of currency codes
     * @param {String} date - Optional date for exchange rates (YYYY-MM-DD)
     * @returns {Promise} Promise with prices in currencies response
     */
    getPricesInCurrencies: async (id, currencies, date = null) => {
        try {
            const params = { currencies };
            if (date) params.date = date;

            const response = await axios.get(
                `/items/${id}/prices-in-currencies`,
                {
                    params,
                }
            );
            return response.data;
        } catch (error) {
            console.error(`Error fetching prices for item ${id}:`, error);
            throw error;
        }
    },

    /**
     * Get purchasable items
     * @param {Object} params - Query parameters
     * @returns {Promise} Promise with purchasable items response
     */
    getPurchasableItems: async (params = {}) => {
        try {
            const response = await axios.get("/items/purchasable", { params });
            return response.data;
        } catch (error) {
            console.error("Error fetching purchasable items:", error);
            throw error;
        }
    },

    /**
     * Get sellable items
     * @param {Object} params - Query parameters
     * @returns {Promise} Promise with sellable items response
     */
    getSellableItems: async (params = {}) => {
        try {
            const response = await axios.get("/items/sellable", { params });
            return response.data;
        } catch (error) {
            console.error("Error fetching sellable items:", error);
            throw error;
        }
    },

    /**
     * Download item document
     * @param {Number} id - Item ID
     * @returns {Promise} Promise with document blob
     */
    downloadDocument: async (id) => {
        try {
            const response = await axios.get(`/items/${id}/document`, {
                responseType: "blob",
            });
            return response;
        } catch (error) {
            console.error(`Error downloading document for item ${id}:`, error);
            throw error;
        }
    },

    /**
     * Get stock level report
     * @param {Object} params - Optional query parameters
     * @returns {Promise} Promise with stock levels response
     */
    getStockLevelReport: async (params = {}) => {
        try {
            const response = await axios.get("/items/stock-levels", { params });
            return response.data;
        } catch (error) {
            console.error("Error fetching stock level report:", error);
            throw error;
        }
    },

    /**
     * Update item stock
     * @param {Number} id - Item ID
     * @param {Object} data - Adjustment data
     * @returns {Promise} Promise with update stock response
     */
    updateStock: async (id, data) => {
        try {
            const response = await axios.post(
                `/items/${id}/update-stock`,
                data
            );
            return response.data;
        } catch (error) {
            console.error(`Error updating stock for item ${id}:`, error);
            throw error;
        }
    },
};

export default ItemService;
