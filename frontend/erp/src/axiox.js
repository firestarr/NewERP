// src/axios.js
import axios from "axios";

const api = axios.create({
    baseURL: "http://127.0.0.1:8020/api", // ganti IP sesuai server Laravel Anda
});

export default api;
