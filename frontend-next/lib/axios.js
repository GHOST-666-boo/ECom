import axios from 'axios';
import useAuthStore from '../stores/authStore';

/**
 * Axios Instance with Interceptors (Next.js adapted)
 */
const axiosInstance = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Request interceptor: Add Authorization header with Bearer token
axiosInstance.interceptors.request.use(
  (config) => {
    const token = useAuthStore.getState().token;
    
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor: Handle HTTP 401 (clear token, redirect to login)
axiosInstance.interceptors.response.use(
  (response) => {
    return response;
  },
  (error) => {
    if (error.response && error.response.status === 401) {
      // Clear authentication state
      useAuthStore.getState().logout();
      
      // Redirect to home page with login required state
      if (typeof window !== 'undefined') {
        window.location.href = '/?loginRequired=true';
      }
    }
    
    return Promise.reject(error);
  }
);

export default axiosInstance;
