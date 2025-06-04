// src/services/api.js
import axios from "axios";

// Use the global axios instance from main.js instead of creating a new one
const api = axios.create({
    baseURL: axios.defaults.baseURL,
    headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
    },
});

// Request interceptor for API calls
api.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem("token");
        if (token) {
            config.headers["Authorization"] = `Bearer ${token}`;
        }
        return config;
    },
    (error) => {
        console.error("Request error:", error);
        return Promise.reject(error);
    }
);

// Response interceptor for API calls
api.interceptors.response.use(
    (response) => {
        return response;
    },
    async (error) => {
        const originalRequest = error.config;

        // Handle 401 Unauthorized
        if (
            error.response &&
            error.response.status === 401 &&
            !originalRequest._retry
        ) {
            console.log("Unauthorized access, redirecting to login");
            // If not a login request - logout user and redirect to login page
            if (originalRequest.url !== "/auth/login") {
                localStorage.removeItem("token");
                localStorage.removeItem("user");
                window.location.href = "/login";
            }
        }

        // Handle 403 Forbidden
        if (error.response && error.response.status === 403) {
            console.error(
                "Access denied:",
                error.response.data.message ||
                    "You do not have permission for this action"
            );
        }

        // Handle 422 Validation Errors
        if (error.response && error.response.status === 422) {
            const errorMsg = error.response.data.message || "Validation failed";
            const errors = error.response.data.errors || {};

            console.error("Validation error:", errorMsg, errors);

            // Simplify validation errors for easier handling in components
            const simplifiedErrors = {};
            Object.keys(errors).forEach((key) => {
                simplifiedErrors[key] = errors[key][0]; // Just take the first error for each field
            });

            error.validationErrors = simplifiedErrors;
        }

        // Handle 500 Server Error
        if (error.response && error.response.status >= 500) {
            console.error(
                "Server error:",
                error.response.data.message ||
                    "Something went wrong on the server"
            );
        }

        return Promise.reject(error);
    }
);

export default api;
