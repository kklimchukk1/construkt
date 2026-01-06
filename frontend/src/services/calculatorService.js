import httpService from './httpService';

/**
 * Material Calculator Service
 * 
 * This service handles API calls to the calculator endpoints
 */
const calculatorService = {
  /**
   * Calculate area-based material quantities
   * 
   * @param {Object} data - Calculation parameters
   * @param {number} data.length - Length in meters
   * @param {number} data.width - Width in meters
   * @param {number} data.coverage - Coverage per unit of material
   * @param {number} data.wastage - Wastage percentage (optional)
   * @returns {Promise} - Promise with calculation results
   */
  calculateArea: async (data) => {
    try {
      const response = await httpService.post('/calculator/area', data);
      console.log('Area calculation API response:', response);
      return response.data;
    } catch (error) {
      console.error('Error calculating area materials:', error);
      throw error;
    }
  },

  /**
   * Calculate volume-based material quantities
   * 
   * @param {Object} data - Calculation parameters
   * @param {number} data.length - Length in meters
   * @param {number} data.width - Width in meters
   * @param {number} data.depth - Depth in meters
   * @param {number} data.wastage - Wastage percentage (optional)
   * @returns {Promise} - Promise with calculation results
   */
  calculateVolume: async (data) => {
    try {
      const response = await httpService.post('/calculator/volume', data);
      console.log('Volume calculation API response:', response);
      return response.data;
    } catch (error) {
      console.error('Error calculating volume materials:', error);
      throw error;
    }
  },

  /**
   * Calculate linear material quantities
   * 
   * @param {Object} data - Calculation parameters
   * @param {number} data.length - Length in meters
   * @param {number} data.pieceLength - Length of each piece in meters (optional)
   * @param {number} data.wastage - Wastage percentage (optional)
   * @returns {Promise} - Promise with calculation results
   */
  calculateLinear: async (data) => {
    try {
      const response = await httpService.post('/calculator/linear', data);
      console.log('Linear calculation API response:', response);
      return response.data;
    } catch (error) {
      console.error('Error calculating linear materials:', error);
      throw error;
    }
  },

  /**
   * Generic calculate method that handles all calculation types
   * 
   * @param {string} type - Calculation type (area, volume, linear)
   * @param {Object} data - Calculation parameters
   * @returns {Promise} - Promise with calculation results
   */
  calculate: async (type, data) => {
    try {
      const payload = {
        type,
        ...data
      };
      const response = await httpService.post('/calculator/calculate', payload);
      return response.data;
    } catch (error) {
      console.error(`Error calculating ${type} materials:`, error);
      throw error;
    }
  }
};

export default calculatorService;
