import axios from 'axios';

// Create axios instance for chatbot API
const chatbotClient = axios.create({
  baseURL: 'http://localhost:5000',
  headers: {
    'Content-Type': 'application/json'
  },
  timeout: 10000
});

// Add response interceptor for error logging
chatbotClient.interceptors.response.use(
  response => response,
  error => {
    console.error('Chatbot API Error:', {
      url: error.config?.url,
      method: error.config?.method,
      status: error.response?.status,
      message: error.message
    });
    return Promise.reject(error);
  }
);

/**
 * Chatbot service for communicating with the backend chatbot API.
 * All conversation logic is handled by the backend.
 */
const chatbotService = {
  /**
   * Send a message to the chatbot
   *
   * @param {string} message The message to send
   * @param {string} userId User identifier for conversation tracking
   * @returns {Promise} Promise with chatbot response
   */
  sendMessage: async (message, userId) => {
    try {
      console.log('Sending message to chatbot:', { message, userId });

      const response = await chatbotClient.post('/api/chatbot/message', {
        message,
        user_id: userId
      });

      if (response.data && response.data.message) {
        return {
          message: response.data.message,
          data: response.data.data || {},
          intent: response.data.intent || 'unknown',
          confidence: response.data.confidence || 0,
          user_id: userId
        };
      } else {
        throw new Error('Invalid response format');
      }
    } catch (error) {
      console.error('Error sending message to chatbot:', error);

      // Return a user-friendly error message
      return {
        message: 'Sorry, I encountered an error. Please try again.',
        intent: 'error',
        confidence: 0,
        data: { error: true },
        user_id: userId
      };
    }
  },

  /**
   * Get conversation history for a user
   *
   * @param {string} userId User identifier
   * @param {number} limit Maximum number of messages to return
   * @returns {Promise} Promise with conversation history
   */
  getHistory: async (userId, limit = null) => {
    try {
      const params = { user_id: userId };
      if (limit) {
        params.limit = limit;
      }

      const response = await chatbotClient.get('/api/chatbot/history', { params });

      if (response.data && Array.isArray(response.data.history)) {
        return response.data.history;
      }
      return [];
    } catch (error) {
      console.error('Error fetching chat history:', error);
      return [];
    }
  },

  /**
   * Clear conversation context for a user
   *
   * @param {string} userId User identifier
   * @returns {Promise} Promise with clear context result
   */
  clearContext: async (userId) => {
    try {
      const response = await chatbotClient.post('/api/chatbot/context/clear', {
        user_id: userId
      });

      return response.data && response.data.success === true;
    } catch (error) {
      console.error('Error clearing context:', error);
      return false;
    }
  },

  /**
   * Get current conversation context (for debugging)
   *
   * @param {string} userId User identifier
   * @returns {Promise} Promise with context data
   */
  getContext: async (userId) => {
    try {
      const response = await chatbotClient.get('/api/chatbot/context', {
        params: { user_id: userId }
      });

      return response.data || {};
    } catch (error) {
      console.error('Error getting context:', error);
      return {};
    }
  },

  /**
   * Check if the chatbot API is available
   *
   * @returns {Promise<boolean>} Promise resolving to true if API is available
   */
  checkHealth: async () => {
    try {
      const response = await chatbotClient.get('/api/chatbot/health');
      return response.data && response.data.status === 'ok';
    } catch (error) {
      console.error('Chatbot API health check failed:', error);
      return false;
    }
  },

  /**
   * Reset offline status (no-op, kept for backward compatibility)
   */
  resetOfflineStatus() {
    // No longer needed - all logic is on backend
    console.log('Chatbot service ready');
  },

  // ============================================
  // COMMAND-BASED API METHODS
  // ============================================

  /**
   * Send a structured command to the chatbot
   *
   * @param {string} command Command name (SEARCH, CATEGORIES, PRODUCT, etc.)
   * @param {Object} params Command parameters
   * @param {string} userId User identifier
   * @returns {Promise} Promise with command response
   */
  sendCommand: async (command, params = {}, userId) => {
    try {
      console.log('Sending command to chatbot:', { command, params, userId });

      const response = await chatbotClient.post('/api/chatbot/command', {
        command,
        params,
        user_id: userId
      });

      return response.data;
    } catch (error) {
      console.error('Error sending command to chatbot:', error);

      return {
        type: 'error',
        message: 'Sorry, I encountered an error. Please try again.',
        actions: [{ type: 'HELP', label: 'Get Help' }]
      };
    }
  },

  /**
   * Get initial chatbot state with available commands
   *
   * @param {string} userId Optional user identifier
   * @returns {Promise} Promise with initial state
   */
  getInitialState: async (userId = null) => {
    try {
      const response = await chatbotClient.post('/api/chatbot/init', {
        user_id: userId
      });

      return response.data;
    } catch (error) {
      console.error('Error getting initial state:', error);

      // Return default state on error
      return {
        type: 'welcome',
        message: 'Welcome to Construkt! How can I help you today?',
        commands: [
          { command: 'SEARCH', icon: '🔍', label: 'Search' },
          { command: 'CATEGORIES', icon: '📦', label: 'Categories' },
          { command: 'FEATURED', icon: '⭐', label: 'Popular' },
          { command: 'CHEAPEST', icon: '💰', label: 'Budget' },
          { command: 'CALCULATOR', icon: '📐', label: 'Calculator' },
          { command: 'HELP', icon: '❓', label: 'Help' }
        ],
        popular_searches: ['Nails', 'Cement', 'Bricks', 'Paint', 'Tiles', 'Lumber'],
        actions: []
      };
    }
  },

  /**
   * Get all product categories
   *
   * @returns {Promise} Promise with categories list
   */
  getCategories: async () => {
    try {
      const response = await chatbotClient.get('/api/chatbot/categories');
      return response.data.categories || [];
    } catch (error) {
      console.error('Error getting categories:', error);
      return [];
    }
  },

  /**
   * Search products
   *
   * @param {string} keyword Search keyword
   * @param {string} userId User identifier
   * @returns {Promise} Promise with search results
   */
  searchProducts: async (keyword, userId) => {
    return chatbotService.sendCommand('SEARCH', { keyword }, userId);
  },

  /**
   * Get products by category
   *
   * @param {number} categoryId Category ID
   * @param {string} userId User identifier
   * @returns {Promise} Promise with category products
   */
  getProductsByCategory: async (categoryId, userId) => {
    return chatbotService.sendCommand('CATEGORY', { category_id: categoryId }, userId);
  },

  /**
   * Get product details
   *
   * @param {number} productId Product ID
   * @param {string} userId User identifier
   * @returns {Promise} Promise with product details
   */
  getProductDetails: async (productId, userId) => {
    return chatbotService.sendCommand('PRODUCT', { product_id: productId }, userId);
  },

  /**
   * Get featured products
   *
   * @param {string} userId User identifier
   * @returns {Promise} Promise with featured products
   */
  getFeaturedProducts: async (userId) => {
    return chatbotService.sendCommand('FEATURED', {}, userId);
  },

  /**
   * Get cheapest products
   *
   * @param {number} categoryId Optional category ID
   * @param {string} userId User identifier
   * @returns {Promise} Promise with cheapest products
   */
  getCheapestProducts: async (categoryId = null, userId) => {
    const params = categoryId ? { category_id: categoryId } : {};
    return chatbotService.sendCommand('CHEAPEST', params, userId);
  }
};

export default chatbotService;
