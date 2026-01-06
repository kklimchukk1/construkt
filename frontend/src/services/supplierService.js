import axios from 'axios';

const API_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

// Helper function to get auth token
const getAuthToken = () => {
  return localStorage.getItem('token');
};

// Create axios instance with auth headers
const createAuthAxios = () => {
  const token = getAuthToken();
  return axios.create({
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    }
  });
};

// Get supplier profile
const getSupplierProfile = async () => {
  try {
    const authAxios = createAuthAxios();
    const response = await authAxios.get(`${API_URL}/supplier/profile`);
    return response.data;
  } catch (error) {
    console.error('Error fetching supplier profile:', error);
    throw error;
  }
};

// Create supplier profile
const createSupplierProfile = async (profileData) => {
  try {
    const authAxios = createAuthAxios();
    const response = await authAxios.post(`${API_URL}/supplier/profile`, profileData);
    return response.data;
  } catch (error) {
    console.error('Error creating supplier profile:', error);
    throw error;
  }
};

// Get supplier products
const getSupplierProducts = async (page = 1, limit = 10) => {
  try {
    const authAxios = createAuthAxios();
    const response = await authAxios.get(`${API_URL}/supplier/products`, {
      params: { page, limit }
    });
    return response.data;
  } catch (error) {
    console.error('Error fetching supplier products:', error);
    throw error;
  }
};

// Create a new product
const createProduct = async (productData) => {
  try {
    const authAxios = createAuthAxios();
    const response = await authAxios.post(`${API_URL}/supplier/products`, productData);
    return response.data;
  } catch (error) {
    console.error('Error creating product:', error);
    throw error;
  }
};

// Update an existing product
const updateProduct = async (productId, productData) => {
  try {
    const authAxios = createAuthAxios();
    const response = await authAxios.put(`${API_URL}/supplier/products/${productId}`, productData);
    return response.data;
  } catch (error) {
    console.error('Error updating product:', error);
    throw error;
  }
};

// Delete a product
const deleteProduct = async (productId) => {
  try {
    const authAxios = createAuthAxios();
    const response = await authAxios.delete(`${API_URL}/supplier/products/${productId}`);
    return response.data;
  } catch (error) {
    console.error('Error deleting product:', error);
    throw error;
  }
};

// Get product by ID
const getProductById = async (productId) => {
  try {
    const authAxios = createAuthAxios();
    const response = await authAxios.get(`${API_URL}/supplier/products/${productId}`);
    return response.data;
  } catch (error) {
    console.error('Error fetching product details:', error);
    throw error;
  }
};

// Get supplier stats
const getSupplierStats = async () => {
  try {
    const authAxios = createAuthAxios();
    const response = await authAxios.get(`${API_URL}/supplier/stats`);
    return response.data;
  } catch (error) {
    console.error('Error fetching supplier stats:', error);
    throw error;
  }
};

// Get product categories
const getCategories = async () => {
  try {
    const authAxios = createAuthAxios();
    const response = await authAxios.get(`${API_URL}/categories`);
    return response.data;
  } catch (error) {
    console.error('Error fetching categories:', error);
    throw error;
  }
};

const supplierService = {
  getSupplierProfile,
  createSupplierProfile,
  getSupplierProducts,
  createProduct,
  updateProduct,
  deleteProduct,
  getProductById,
  getSupplierStats,
  getCategories
};

export default supplierService;
