// src/axios.js
import axios from "axios";

const api = axios.create({
    baseURL: "http://192.168.1.10:8020/api", // ganti IP sesuai server Laravel Anda
});

export default api;
