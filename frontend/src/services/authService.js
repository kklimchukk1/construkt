import axios from 'axios';

// API base URL
const API_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

/**
 * Authentication service for handling user login, registration, and token management
 */
const authService = {
  /**
   * Login user with email and password
   * @param {string} email - User email
   * @param {string} password - User password
   * @returns {Promise} - Promise with user data on success
   */
  login: async (email, password) => {
    try {
      const response = await axios.post(`${API_URL}/auth/login`, {
        email,
        password
      });
      
      // Check if response has the expected format
      if (response.data && response.data.data && response.data.data.token) {
        // Store user details and token in localStorage
        localStorage.setItem('user', JSON.stringify(response.data.data.user));
        localStorage.setItem('token', response.data.data.token);
      }
      
      return response.data;
    } catch (error) {
      console.error('Login error:', error.response?.data?.message || error.message);
      throw error;
    }
  },
  
  /**
   * Register a new user
   * @param {Object} userData - User registration data
   * @returns {Promise} - Promise with user data on success
   */
  register: async (userData) => {
    try {
      const response = await axios.post(`${API_URL}/auth/register`, userData);
      
      // Check if response has the expected format
      if (response.data && response.data.data && response.data.data.token) {
        // Store user details and token in localStorage
        localStorage.setItem('user', JSON.stringify(response.data.data.user));
        localStorage.setItem('token', response.data.data.token);
      }
      
      return response.data;
    } catch (error) {
      console.error('Registration error:', error.response?.data?.message || error.message);
      throw error;
    }
  },
  
  /**
   * Logout current user
   */
  logout: () => {
    localStorage.removeItem('user');
    localStorage.removeItem('token');
  },
  
  /**
   * Get current user data from localStorage
   * @returns {Object|null} - User data or null if not logged in
   */
  getCurrentUser: () => {
    const userStr = localStorage.getItem('user');
    if (!userStr) return null;
    
    try {
      return JSON.parse(userStr);
    } catch (e) {
      localStorage.removeItem('user');
      return null;
    }
  },
  
  /**
   * Get authentication token from localStorage
   * @returns {string|null} - JWT token or null if not available
   */
  getToken: () => {
    return localStorage.getItem('token');
  },
  
  /**
   * Check if user is authenticated
   * @returns {boolean} - True if user is authenticated
   */
  isAuthenticated: () => {
    return !!localStorage.getItem('token') && !!localStorage.getItem('user');
  },
  
  /**
   * Update user profile
   * @param {Object} profileData - User profile data
   * @returns {Promise} - Promise with updated user data on success
   */
  updateProfile: async (profileData) => {
    try {
      const token = localStorage.getItem('token');
      
      if (!token) {
        throw new Error('Not authenticated');
      }
      
      const response = await axios.put(`${API_URL}/auth/profile`, profileData, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });
      
      return response.data;
    } catch (error) {
      console.error('Profile update error:', error.response?.data?.message || error.message);
      throw error;
    }
  }
};

export default authService;
