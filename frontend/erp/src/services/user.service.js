// src/services/user.service.js
import axios from "axios";

/**
 * Service for user management operations
 */
const UserService = {
    /**
     * Get all users (admin only)
     * @param {object} params Query parameters (pagination, filters, etc.)
     * @returns {Promise} Promise with users response
     */
    getUsers: async (params = {}) => {
        try {
            const response = await axios.get("/users", { params });
            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Get user by ID
     * @param {number} id User ID
     * @returns {Promise} Promise with user response
     */
    getUserById: async (id) => {
        try {
            const response = await axios.get(`/users/${id}`);
            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Create a new user (admin only)
     * @param {object} userData User data
     * @returns {Promise} Promise with create user response
     */
    createUser: async (userData) => {
        try {
            const response = await axios.post("/users", userData);
            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Update user
     * @param {number} id User ID
     * @param {object} userData User data to update
     * @returns {Promise} Promise with update user response
     */
    updateUser: async (id, userData) => {
        try {
            const response = await axios.put(`/users/${id}`, userData);
            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Delete user (admin only)
     * @param {number} id User ID
     * @returns {Promise} Promise with delete user response
     */
    deleteUser: async (id) => {
        try {
            const response = await axios.delete(`/users/${id}`);
            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Update current user profile
     * @param {object} profileData Profile data to update
     * @returns {Promise} Promise with update profile response
     */
    updateProfile: async (profileData) => {
        try {
            const response = await axios.put("/user/profile", profileData);

            // Update local storage with new user data
            if (response.data.user) {
                const userStr = localStorage.getItem("user");
                if (userStr) {
                    const user = JSON.parse(userStr);
                    const updatedUser = { ...user, ...response.data.user };
                    localStorage.setItem("user", JSON.stringify(updatedUser));
                }
            }

            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Change user password
     * @param {object} passwordData Password data
     * @returns {Promise} Promise with change password response
     */
    changePassword: async (passwordData) => {
        try {
            const response = await axios.put("/user/password", passwordData);
            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Get user permissions
     * @returns {Promise} Promise with user permissions
     */
    getUserPermissions: async () => {
        try {
            const response = await axios.get("/user/permissions");
            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Verify user email
     * @param {string} token Verification token
     * @returns {Promise} Promise with email verification response
     */
    verifyEmail: async (token) => {
        try {
            const response = await axios.get(`/auth/verify-email/${token}`);
            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Resend verification email
     * @returns {Promise} Promise with resend verification email response
     */
    resendVerificationEmail: async () => {
        try {
            const response = await axios.post("/auth/resend-verification");
            return response.data;
        } catch (error) {
            throw error;
        }
    },
};

export default UserService;
