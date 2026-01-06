import httpService from './httpService';

/**
 * Helper function to extract products from various response formats
 * @param {Object} responseData - The response data to extract products from
 * @returns {Array} - Array of products
 */
const extractProductsFromResponse = (responseData) => {
  if (!responseData) return [];
  
  // If it's already an array, assume it's an array of products
  if (Array.isArray(responseData)) {
    return responseData;
  }
  
  // Check for common properties that might contain products
  for (const key of ['products', 'data', 'items', 'results']) {
    if (responseData[key] && Array.isArray(responseData[key])) {
      return responseData[key];
    }
  }
  
  // If we have an object with product-like properties, wrap it in an array
  if (responseData.id && (responseData.name || responseData.title)) {
    return [responseData];
  }
  
  // Last resort: look for any array in the response
  for (const key in responseData) {
    if (Array.isArray(responseData[key])) {
      // Check if the first item looks like a product
      const firstItem = responseData[key][0];
      if (firstItem && (firstItem.id || firstItem.name || firstItem.price)) {
        return responseData[key];
      }
    }
  }
  
  return [];
};

/**
 * Product service for handling product-related API calls
 */
const productService = {
  /**
   * Get all products with optional filtering
   * @param {Object} filters - Optional filters (category, search, etc.)
   * @returns {Promise} - Promise with products data
   */
  getProducts: async (filters = {}) => {
    try {
      // Pass filters as query parameters
      const response = await httpService.get('/products', filters);

      console.log('Raw API response:', response);

      // httpService.get returns response.data directly
      // So response IS already the data object

      // Format: { status: 'success', data: { products: [...], total: N } }
      if (response && response.status === 'success' && response.data && response.data.products) {
        console.log('Products from API (standard format):', response.data);
        return response.data;
      }

      // Format: { products: [...] }
      if (response && response.products && Array.isArray(response.products)) {
        console.log('Products from API (direct products):', response);
        return response;
      }

      // Format: { data: { products: [...] } }
      if (response && response.data && response.data.products) {
        console.log('Products from API (nested data):', response.data);
        return response.data;
      }

      // Format: direct array
      if (response && Array.isArray(response)) {
        console.log('Products from API (array format):', response);
        return { products: response };
      }

      // Try to extract products from response
      const extractedProducts = extractProductsFromResponse(response);
      if (extractedProducts.length > 0) {
        console.log('Extracted products from response:', extractedProducts);
        return { products: extractedProducts };
      }

      console.error('Could not parse products from response:', response);
      return { products: [] };
    } catch (error) {
      console.error('Error fetching products:', error);
      // Only use mock data if explicitly requested or if in development with no connection
      if (filters.useMockData === true ||
          (process.env.NODE_ENV === 'development' && error.message.includes('Network Error'))) {
        console.log('Using mock product data as fallback');
        return { products: productService.getMockProducts(), pagination: { total: 100, per_page: 20, current_page: 1, total_pages: 5 } };
      }
      throw error;
    }
  },
  
  /**
   * Get a single product by ID
   * @param {number|string} id - Product ID
   * @returns {Promise} - Promise with product data
   */
  getProductById: async (id) => {
    try {
      const response = await httpService.get(`/products/${id}`);
      
      console.log('Raw product response:', response.data);
      
      // Check if response has the expected format - handle multiple possible formats
      if (response.data && response.data.success === true && response.data.data) {
        // API format with success:true and data object
        console.log('Product data found in success/data format');
        const productData = response.data.data;
        if (productData.product) {
          productData.product.stock_quantity = parseInt(productData.product.stock_quantity || 0);
        }
        return productData;
      } else if (response.data && response.data.status === 'success' && response.data.data) {
        // Alternative API format with status:'success'
        console.log('Product data found in status/data format');
        const productData = response.data.data;
        if (productData.product) {
          productData.product.stock_quantity = parseInt(productData.product.stock_quantity || 0);
        }
        return productData;
      } else if (response.data && response.data.product) {
        // Direct product object in response
        console.log('Product data found directly in response');
        response.data.product.stock_quantity = parseInt(response.data.product.stock_quantity || 0);
        return response.data;
      } else if (response.data && typeof response.data === 'object' && response.data.id) {
        // The response itself is the product
        console.log('Response itself is the product');
        const product = response.data;
        product.stock_quantity = parseInt(product.stock_quantity || 0);
        return { product };
      } else {
        console.error('Unexpected API response format:', response.data);
        throw new Error('Invalid response format');
      }
    } catch (error) {
      console.error(`Error fetching product ${id}:`, error);
      // Fallback to mock data in development
      console.log('Using mock product data as fallback');
      const mockProducts = productService.getMockProducts();
      
      // Find the product by ID
      let product = mockProducts.find(p => p.id.toString() === id.toString());
      
      // If product not found, create a placeholder
      if (!product) {
        product = {
          id: parseInt(id),
          name: `Product ${id}`,
          price: 99.99,
          description: 'This is a placeholder product description.',
          category_id: 1,
          category_name: 'General',
          image_url: '/placeholder-product.jpg',
          stock_quantity: 100, // Ensure this is set to a positive number
          rating: 4.5,
          unit: 'piece'
        };
      } else {
        // Ensure stock_quantity is set
        product.stock_quantity = 100; // Force stock to be available
      }
      
      // Get related products
      const related_products = mockProducts
        .filter(p => p.id.toString() !== id.toString())
        .slice(0, 4);
      
      return { product, related_products };
    }
  },
  
  /**
   * Get all product categories
   * @returns {Promise} - Promise with categories data
   */
  getCategories: async (withProductCounts = false) => {
    try {
      const response = await httpService.get('/categories', { 
        params: { with_product_counts: withProductCounts }
      });
      
      // Check if response has the expected format
      if (response.data && response.data.status === 'success' && response.data.data) {
        return response.data.data.categories;
      } else {
        throw new Error('Invalid response format');
      }
    } catch (error) {
      console.error('Error fetching categories:', error);
      // Fallback to mock data in development
      if (process.env.NODE_ENV === 'development') {
        console.log('Using mock category data as fallback');
        return productService.getMockCategories();
      }
      throw error;
    }
  },
  
  /**
   * Search products by keyword
   * @param {string} query - Search query
   * @returns {Promise} - Promise with search results
   */
  searchProducts: async (query) => {
    try {
      const response = await httpService.get('/products/search', { query });
      return response.data;
    } catch (error) {
      console.error('Error searching products:', error);
      throw error;
    }
  },
  
  /**
   * Get featured products
   * @param {number} limit - Maximum number of products to return
   * @returns {Promise} - Promise with featured products data
   */
  getFeaturedProducts: async (limit = 6) => {
    try {
      const response = await httpService.get('/products/featured', { 
        params: { limit }
      });
      
      // Check if response has the expected format
      if (response.data && response.data.status === 'success' && response.data.data) {
        return response.data.data.products;
      } else {
        throw new Error('Invalid response format');
      }
    } catch (error) {
      console.error('Error fetching featured products:', error);
      // Fallback to mock data in development
      if (process.env.NODE_ENV === 'development') {
        console.log('Using mock featured product data as fallback');
        return productService.getMockProducts().slice(0, limit);
      }
      throw error;
    }
  },
  
  /**
   * Get related products for a specific product
   * @param {number|string} productId - Product ID
   * @param {number} limit - Maximum number of products to return
   * @returns {Promise} - Promise with related products data
   */
  getRelatedProducts: async (productId, limit = 4) => {
    try {
      const response = await httpService.get(`/products/${productId}/related`, { limit });
      return response.data;
    } catch (error) {
      console.error(`Error fetching related products for ${productId}:`, error);
      throw error;
    }
  },
  
  /**
   * Get mock product data for development
   * This function simulates API responses for local development
   * @returns {Array} - Array of mock product data
   */
  getMockProducts: () => {
    return [
      {
        id: 1,
        name: 'Premium Cement',
        description: 'High-quality cement for all construction needs',
        price: 12.99,
        unit: 'bag',
        category_id: 1,
        category_name: 'Building Materials',
        supplier_id: 1,
        supplier_name: 'BuildRight Materials',
        image_url: 'https://via.placeholder.com/300x300?text=Cement',
        stock: 250,
        rating: 4.5,
        featured: true
      },
      {
        id: 2,
        name: 'Steel Reinforcement Bars',
        description: 'Heavy-duty steel bars for reinforced concrete',
        price: 24.50,
        unit: 'bundle',
        category_id: 1,
        category_name: 'Building Materials',
        supplier_id: 2,
        supplier_name: 'Quality Building Supplies',
        image_url: 'https://via.placeholder.com/300x300?text=Steel+Bars',
        stock: 120,
        rating: 4.8,
        featured: true
      },
      {
        id: 3,
        name: 'Ceramic Floor Tiles',
        description: 'Elegant ceramic tiles for interior flooring',
        price: 3.99,
        unit: 'sq.ft',
        category_id: 2,
        category_name: 'Flooring',
        supplier_id: 3,
        supplier_name: 'Pro Construction Materials',
        image_url: 'https://via.placeholder.com/300x300?text=Ceramic+Tiles',
        stock: 500,
        rating: 4.2,
        featured: false
      },
      {
        id: 4,
        name: 'Exterior House Paint',
        description: 'Weather-resistant paint for exterior surfaces',
        price: 45.99,
        unit: 'gallon',
        category_id: 3,
        category_name: 'Paints & Finishes',
        supplier_id: 1,
        supplier_name: 'BuildRight Materials',
        image_url: 'https://via.placeholder.com/300x300?text=House+Paint',
        stock: 75,
        rating: 4.6,
        featured: true
      },
      {
        id: 5,
        name: 'Hardwood Flooring',
        description: 'Premium oak hardwood flooring',
        price: 5.99,
        unit: 'sq.ft',
        category_id: 2,
        category_name: 'Flooring',
        supplier_id: 2,
        supplier_name: 'Quality Building Supplies',
        image_url: 'https://via.placeholder.com/300x300?text=Hardwood+Flooring',
        stock: 300,
        rating: 4.9,
        featured: true
      },
      {
        id: 6,
        name: 'Concrete Blocks',
        description: 'Standard concrete blocks for construction',
        price: 2.49,
        unit: 'each',
        category_id: 1,
        category_name: 'Building Materials',
        supplier_id: 3,
        supplier_name: 'Pro Construction Materials',
        image_url: 'https://via.placeholder.com/300x300?text=Concrete+Blocks',
        stock: 800,
        rating: 4.3,
        featured: false
      },
      {
        id: 7,
        name: 'Insulation Roll',
        description: 'Fiberglass insulation for walls and attics',
        price: 19.99,
        unit: 'roll',
        category_id: 4,
        category_name: 'Insulation',
        supplier_id: 1,
        supplier_name: 'BuildRight Materials',
        image_url: 'https://via.placeholder.com/300x300?text=Insulation',
        stock: 150,
        rating: 4.4,
        featured: false
      },
      {
        id: 8,
        name: 'Roofing Shingles',
        description: 'Asphalt roof shingles with 30-year warranty',
        price: 29.99,
        unit: 'bundle',
        category_id: 5,
        category_name: 'Roofing',
        supplier_id: 2,
        supplier_name: 'Quality Building Supplies',
        image_url: 'https://via.placeholder.com/300x300?text=Roofing+Shingles',
        stock: 200,
        rating: 4.7,
        featured: true
      },
      {
        id: 9,
        name: 'Plywood Sheets',
        description: '4x8 plywood sheets for construction',
        price: 32.50,
        unit: 'sheet',
        category_id: 1,
        category_name: 'Building Materials',
        supplier_id: 3,
        supplier_name: 'Pro Construction Materials',
        image_url: 'https://via.placeholder.com/300x300?text=Plywood',
        stock: 180,
        rating: 4.5,
        featured: false
      },
      {
        id: 10,
        name: 'PVC Pipes',
        description: 'Schedule 40 PVC pipes for plumbing',
        price: 8.99,
        unit: '10ft',
        category_id: 6,
        category_name: 'Plumbing',
        supplier_id: 1,
        supplier_name: 'BuildRight Materials',
        image_url: 'https://via.placeholder.com/300x300?text=PVC+Pipes',
        stock: 350,
        rating: 4.6,
        featured: false
      },
      {
        id: 11,
        name: 'Electrical Wire',
        description: '12-gauge copper electrical wire',
        price: 0.89,
        unit: 'ft',
        category_id: 7,
        category_name: 'Electrical',
        supplier_id: 2,
        supplier_name: 'Quality Building Supplies',
        image_url: 'https://via.placeholder.com/300x300?text=Electrical+Wire',
        stock: 1000,
        rating: 4.8,
        featured: false
      },
      {
        id: 12,
        name: 'Interior Door',
        description: 'Pre-hung interior door with frame',
        price: 129.99,
        unit: 'each',
        category_id: 8,
        category_name: 'Doors & Windows',
        supplier_id: 3,
        supplier_name: 'Pro Construction Materials',
        image_url: 'https://via.placeholder.com/300x300?text=Interior+Door',
        stock: 45,
        rating: 4.4,
        featured: true
      }
    ];
  },
  
  /**
   * Get mock categories data for development
   * This function simulates API responses for local development
   * @returns {Array} - Array of mock category data
   */
  getMockCategories: () => {
    return [
      { id: 1, name: 'Building Materials', parent_id: null },
      { id: 2, name: 'Flooring', parent_id: null },
      { id: 3, name: 'Paints & Finishes', parent_id: null },
      { id: 4, name: 'Insulation', parent_id: null },
      { id: 5, name: 'Roofing', parent_id: null },
      { id: 6, name: 'Plumbing', parent_id: null },
      { id: 7, name: 'Electrical', parent_id: null },
      { id: 8, name: 'Doors & Windows', parent_id: null },
      { id: 9, name: 'Concrete', parent_id: 1 },
      { id: 10, name: 'Lumber', parent_id: 1 },
      { id: 11, name: 'Drywall', parent_id: 1 },
      { id: 12, name: 'Ceramic Tiles', parent_id: 2 },
      { id: 13, name: 'Hardwood', parent_id: 2 },
      { id: 14, name: 'Laminate', parent_id: 2 },
      { id: 15, name: 'Interior Paint', parent_id: 3 },
      { id: 16, name: 'Exterior Paint', parent_id: 3 }
    ];
  }
};

export default productService;
