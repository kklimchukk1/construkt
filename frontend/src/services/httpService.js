import axios from 'axios';
import authService from './authService';

// Global loading and error state management
let loadingRequests = 0;
let loadingStateListeners = [];
let errorListeners = [];

// Create axios instance with default config
const httpClient = axios.create({
  baseURL: process.env.REACT_APP_API_URL || 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json'
  }
});

// Add request interceptor to add auth token and user ID to requests
httpClient.interceptors.request.use(
  config => {
    const token = authService.getToken();
    if (token) {
      config.headers['Authorization'] = `Bearer ${token}`;
    }
    // Add user ID header for API requests that need it
    const user = authService.getCurrentUser();
    if (user && user.id) {
      config.headers['X-User-Id'] = user.id;
    }
    return config;
  },
  error => {
    return Promise.reject(error);
  }
);

// Add request interceptor to track loading state
httpClient.interceptors.request.use(
  config => {
    // Increment loading counter and notify listeners
    loadingRequests++;
    notifyLoadingStateChange(true);
    return config;
  },
  error => {
    // Decrement loading counter and notify listeners
    loadingRequests--;
    if (loadingRequests <= 0) {
      loadingRequests = 0;
      notifyLoadingStateChange(false);
    }
    return Promise.reject(error);
  }
);

// Add response interceptor to handle common errors
httpClient.interceptors.response.use(
  response => {
    console.log('API Response:', response.config.url, response.status, response.data);
    
    // Decrement loading counter and notify listeners
    loadingRequests--;
    if (loadingRequests <= 0) {
      loadingRequests = 0;
      notifyLoadingStateChange(false);
    }
    
    return response;
  },
  error => {
    // Log detailed error information
    console.error('API Error:', {
      url: error.config?.url,
      method: error.config?.method,
      status: error.response?.status,
      data: error.response?.data,
      message: error.message
    });
    
    // Decrement loading counter and notify listeners
    loadingRequests--;
    if (loadingRequests <= 0) {
      loadingRequests = 0;
      notifyLoadingStateChange(false);
    }
    
    // Handle 401 Unauthorized errors (token expired, etc.)
    if (error.response && error.response.status === 401) {
      // Log the user out if their token is invalid/expired
      authService.logout();
      window.location.href = '/login';
    }
    
    // Notify error listeners
    notifyError(error);
    
    return Promise.reject(error);
  }
);

// Helper functions for loading state and error notifications
function notifyLoadingStateChange(isLoading) {
  loadingStateListeners.forEach(listener => listener(isLoading));
}

function notifyError(error) {
  const errorInfo = {
    message: error.response?.data?.message || error.message || 'An error occurred',
    status: error.response?.status,
    url: error.config?.url,
    method: error.config?.method,
    data: error.response?.data
  };
  
  errorListeners.forEach(listener => listener(errorInfo));
}

// HTTP service methods
const httpService = {
  // Loading state management
  isLoading: () => loadingRequests > 0,
  
  addLoadingListener: (listener) => {
    loadingStateListeners.push(listener);
    // Return function to remove listener
    return () => {
      loadingStateListeners = loadingStateListeners.filter(l => l !== listener);
    };
  },
  
  addErrorListener: (listener) => {
    errorListeners.push(listener);
    // Return function to remove listener
    return () => {
      errorListeners = errorListeners.filter(l => l !== listener);
    };
  },
  
  // Format error message for display
  formatErrorMessage: (error) => {
    if (!error) return 'An unknown error occurred';
    
    if (typeof error === 'string') return error;
    
    if (error.response) {
      // Server responded with a status code outside of 2xx range
      const data = error.response.data;
      if (data.message) return data.message;
      if (data.error) return data.error;
      
      // Handle different status codes
      switch (error.response.status) {
        case 400: return 'Bad request. Please check your input.';
        case 401: return 'Unauthorized. Please log in again.';
        case 403: return 'Forbidden. You don\'t have permission to access this resource.';
        case 404: return 'Resource not found.';
        case 500: return 'Server error. Please try again later.';
        default: return `Error ${error.response.status}: ${error.response.statusText}`;
      }
    } else if (error.request) {
      // Request was made but no response received
      return 'No response from server. Please check your internet connection.';
    } else {
      // Something else happened while setting up the request
      return error.message || 'An unexpected error occurred';
    }
  },
  /**
   * Make a GET request
   * @param {string} url - API endpoint
   * @param {Object} params - URL parameters
   * @returns {Promise} - Promise with response data
   */
  get: async (url, params = {}) => {
    try {
      const response = await httpClient.get(url, { params });
      return response.data;
    } catch (error) {
      console.error(`GET ${url} error:`, error.response?.data || error.message);
      throw error;
    }
  },
  
  /**
   * Make a POST request
   * @param {string} url - API endpoint
   * @param {Object} data - Request payload
   * @returns {Promise} - Promise with response data
   */
  post: async (url, data = {}) => {
    try {
      const response = await httpClient.post(url, data);
      return response.data;
    } catch (error) {
      console.error(`POST ${url} error:`, error.response?.data || error.message);
      throw error;
    }
  },
  
  /**
   * Make a PUT request
   * @param {string} url - API endpoint
   * @param {Object} data - Request payload
   * @returns {Promise} - Promise with response data
   */
  put: async (url, data = {}) => {
    try {
      const response = await httpClient.put(url, data);
      return response.data;
    } catch (error) {
      console.error(`PUT ${url} error:`, error.response?.data || error.message);
      throw error;
    }
  },
  
  /**
   * Make a DELETE request
   * @param {string} url - API endpoint
   * @param {Object} params - URL parameters
   * @returns {Promise} - Promise with response data
   */
  delete: async (url, params = {}) => {
    try {
      const response = await httpClient.delete(url, { params });
      return response.data;
    } catch (error) {
      console.error(`DELETE ${url} error:`, error.response?.data || error.message);
      throw error;
    }
  }
};

export default httpService;
