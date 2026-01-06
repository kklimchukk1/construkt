import httpService from './httpService';
import authService from './authService';

// Helper to get current user_id
const getUserId = () => {
  const user = authService.getCurrentUser();
  return user?.id || user?.user_id;
};

const orderService = {
  // Cart operations
  getCart: async () => {
    try {
      const userId = getUserId();
      const response = await httpService.get('/cart', { user_id: userId });
      return response.data || [];
    } catch (error) {
      console.error('Error getting cart:', error);
      // Return from localStorage as fallback
      return JSON.parse(localStorage.getItem('cart') || '[]');
    }
  },

  addToCart: async (productId, quantity = 1) => {
    try {
      const userId = getUserId();
      const response = await httpService.post('/cart', {
        user_id: userId,
        product_id: productId,
        quantity
      });
      return response.data;
    } catch (error) {
      // Fallback to localStorage
      const cart = JSON.parse(localStorage.getItem('cart') || '[]');
      const existingItem = cart.find(item => item.product_id === productId);
      if (existingItem) {
        existingItem.quantity += quantity;
      } else {
        cart.push({ product_id: productId, quantity });
      }
      localStorage.setItem('cart', JSON.stringify(cart));
      return cart;
    }
  },

  updateCartItem: async (itemId, quantity) => {
    try {
      const userId = getUserId();
      const response = await httpService.put(`/cart/${itemId}`, { user_id: userId, quantity });
      return response.data;
    } catch (error) {
      console.error('Error updating cart item:', error);
      throw error;
    }
  },

  removeFromCart: async (itemId) => {
    try {
      const userId = getUserId();
      const response = await httpService.delete(`/cart/${itemId}`, { user_id: userId });
      return response.data;
    } catch (error) {
      // Fallback to localStorage
      const cart = JSON.parse(localStorage.getItem('cart') || '[]');
      const updatedCart = cart.filter(item => item.id !== itemId);
      localStorage.setItem('cart', JSON.stringify(updatedCart));
      return updatedCart;
    }
  },

  clearCart: async () => {
    try {
      const userId = getUserId();
      await httpService.delete('/cart', { user_id: userId });
    } catch (error) {
      localStorage.setItem('cart', '[]');
    }
  },

  // Order operations
  createOrder: async (orderData) => {
    try {
      const userId = getUserId();
      const response = await httpService.post('/orders', {
        user_id: userId,
        ...orderData
      });
      return response.data;
    } catch (error) {
      console.error('Error creating order:', error);
      throw error;
    }
  },

  getMyOrders: async () => {
    try {
      const userId = getUserId();
      const response = await httpService.get('/orders/my', { user_id: userId });
      return response.data || [];
    } catch (error) {
      console.error('Error getting orders:', error);
      return [];
    }
  },

  getAllOrders: async () => {
    try {
      const response = await httpService.get('/orders');
      return response.data || [];
    } catch (error) {
      console.error('Error getting all orders:', error);
      return [];
    }
  },

  getOrder: async (orderId) => {
    try {
      const response = await httpService.get(`/orders/${orderId}`);
      return response.data;
    } catch (error) {
      console.error('Error getting order:', error);
      throw error;
    }
  },

  updateOrderStatus: async (orderId, status) => {
    try {
      const response = await httpService.put(`/orders/${orderId}/status`, { status });
      return response.data;
    } catch (error) {
      console.error('Error updating order status:', error);
      throw error;
    }
  },

  cancelOrder: async (orderId) => {
    try {
      const response = await httpService.put(`/orders/${orderId}/cancel`);
      return response.data;
    } catch (error) {
      console.error('Error canceling order:', error);
      throw error;
    }
  }
};

export default orderService;
