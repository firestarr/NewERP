// src/services/auth.service.js
import axios from "axios";

/**
 * Service for authentication related operations
 */
const AuthService = {
    /**
     * Login user with email and password
     * @param {string} email User email
     * @param {string} password User password
     * @param {boolean} rememberMe Remember me flag
     * @returns {Promise} Promise with login response
     */
    login: async (email, password, rememberMe = false) => {
        try {
            const response = await axios.post("/auth/login", {
                email,
                password,
                remember_me: rememberMe,
            });

            if (response.data.token) {
                localStorage.setItem("token", response.data.token);
                localStorage.setItem(
                    "user",
                    JSON.stringify(response.data.user)
                );
            }

            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Register a new user
     * @param {object} userData User registration data
     * @returns {Promise} Promise with registration response
     */
    register: async (userData) => {
        try {
            const response = await axios.post("/auth/register", userData);
            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Log out current user
     * @returns {Promise} Promise with logout response
     */
    logout: async () => {
        try {
            // Call logout endpoint to invalidate token on server
            await axios.post("/auth/logout");

            // Remove user data from local storage
            localStorage.removeItem("token");
            localStorage.removeItem("user");

            return { success: true };
        } catch (error) {
            // Even if server-side logout fails, clear local storage
            localStorage.removeItem("token");
            localStorage.removeItem("user");

            throw error;
        }
    },

    /**
     * Get current authenticated user
     * @returns {object|null} Current user or null if not logged in
     */
    getCurrentUser: () => {
        const userStr = localStorage.getItem("user");
        if (!userStr) return null;

        try {
            return JSON.parse(userStr);
        } catch (e) {
            localStorage.removeItem("user");
            return null;
        }
    },

    /**
     * Check if user is authenticated
     * @returns {boolean} True if user is authenticated
     */
    isAuthenticated: () => {
        return !!localStorage.getItem("token");
    },

    /**
     * Check if user is admin
     * @returns {boolean} True if user is admin
     */
    isAdmin: () => {
        const user = AuthService.getCurrentUser();
        return user && user.is_admin === true;
    },

    /**
     * Request password reset email
     * @param {string} email User email
     * @returns {Promise} Promise with password reset response
     */
    forgotPassword: async (email) => {
        try {
            const response = await axios.post("/auth/forgot-password", {
                email,
            });
            return response.data;
        } catch (error) {
            throw error;
        }
    },

    /**
     * Reset password with token
     * @param {object} resetData Password reset data
     * @returns {Promise} Promise with password reset response
     */
    resetPassword: async (resetData) => {
        try {
            const response = await axios.post(
                "/auth/reset-password",
                resetData
            );
            return response.data;
        } catch (error) {
            throw error;
        }
    },
};

export default AuthService;
