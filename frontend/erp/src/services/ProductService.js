// src/services/ProductService.js
import axios from "axios";

/**
 * Service for product operations
 */
const ProductService = {
    /**
     * Get all products
     * @param {object} params Query parameters
     * @returns {Promise} Promise with products response
     */
    getProducts: async (params = {}) => {
        try {
            const response = await axios.get("/products", { params });
            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Get a product by ID
     * @param {number} id Product ID
     * @returns {Promise} Promise with product response
     */
    getProductById: async (id) => {
        try {
            const response = await axios.get(`/products/${id}`);
            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Create a new product
     * @param {object} productData Product data
     * @returns {Promise} Promise with create product response
     */
    createProduct: async (productData) => {
        try {
            const response = await axios.post("/products", productData);
            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Update a product
     * @param {number} id Product ID
     * @param {object} productData Product data to update
     * @returns {Promise} Promise with update product response
     */
    updateProduct: async (id, productData) => {
        try {
            const response = await axios.put(`/products/${id}`, productData);
            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Delete a product
     * @param {number} id Product ID
     * @returns {Promise} Promise with delete product response
     */
    deleteProduct: async (id) => {
        try {
            const response = await axios.delete(`/products/${id}`);
            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Get BOMs for a specific product
     * @param {number} productId Product ID
     * @returns {Promise} Promise with BOMs response
     */
    getProductBOMs: async (productId) => {
        try {
            const response = await axios.get(`/products/${productId}/boms`);
            return response.data;
        } catch (error) {
            throw error;
        }
    },
};

export default ProductService;
