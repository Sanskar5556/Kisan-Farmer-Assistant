import axios from 'axios';

/*
 * Axios API Client — configured for Kisan Smart Assistant
 *
 * All API calls go through this file.
 * It automatically:
 * 1. Points to the Laravel backend at /api
 * 2. Adds the JWT token to every request header
 * 3. Redirects to login if token expires (401)
 */
const api = axios.create({
    baseURL: '/api',   // Proxied to http://localhost:8000/api via vite.config.js
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

// Request interceptor — add JWT token to every request
api.interceptors.request.use((config) => {
    const token = localStorage.getItem('kisan_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// Response interceptor — handle auth errors globally
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            // Token expired or invalid — logout and redirect to login
            localStorage.removeItem('kisan_token');
            localStorage.removeItem('kisan_user');
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

export default api;
