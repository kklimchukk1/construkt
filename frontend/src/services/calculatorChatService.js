/**
 * Calculator Chat Service
 * 
 * This service handles the integration between the calculator components and the chatbot,
 * allowing calculation results to be shared with the chatbot.
 */

/**
 * Share calculation result with the chatbot
 * 
 * @param {Object} result - The calculation result
 * @param {Object} product - The product data
 * @param {Object} dimensions - The project dimensions
 * @param {string} calculationType - The type of calculation (area, volume, linear)
 * @returns {string} - The formatted message for the chatbot
 */
export const shareCalculationWithChatbot = (result, product, dimensions, calculationType) => {
  if (!result || !product) {
    console.error('Cannot share calculation: missing result or product data');
    return '';
  }

  // Format the calculation data for the chatbot
  const calculatorData = {
    productId: product.id,
    productName: product.name,
    productUnit: product.unit || 'unit',
    quantity: result.quantity,
    totalCost: parseFloat(result.totalCost).toFixed(2),
    calculationType,
    projectDimensions: dimensions
  };

  console.log('Sharing calculation with chatbot:', calculatorData);

  // Store the data in localStorage for the chatbot to access
  localStorage.setItem('chatbot_calculator_result', JSON.stringify(calculatorData));
  
  // Dispatch a storage event to notify the chatbot
  // This is needed because the chatbot might be in a different component
  setTimeout(() => {
    window.dispatchEvent(new Event('storage'));
  }, 100);
  
  // Format a message for display
  let dimensionDetails = '';
  if (calculationType === 'area' && dimensions) {
    dimensionDetails = `an area of ${dimensions.length}m × ${dimensions.width}m`;
  } else if (calculationType === 'volume' && dimensions) {
    dimensionDetails = `a volume of ${dimensions.length}m × ${dimensions.width}m × ${dimensions.depth}m`;
  } else if (calculationType === 'linear' && dimensions) {
    dimensionDetails = `a length of ${dimensions.length}m`;
  }
  
  return `You need ${result.quantity} ${product.unit || 'unit'}(s) of ${product.name} for ${dimensionDetails}. Total cost: $${parseFloat(result.totalCost).toFixed(2)}`;
};

/**
 * Format calculator data for the chatbot
 * 
 * @param {Object} calculatorData - The calculator data
 * @returns {string} - Formatted message
 */
export const formatCalculatorMessage = (calculatorData) => {
  if (!calculatorData) return '';
  
  const { productName, productUnit, quantity, totalCost, calculationType, projectDimensions } = calculatorData;
  
  let dimensionDetails = '';
  if (calculationType === 'area' && projectDimensions) {
    dimensionDetails = `an area of ${projectDimensions.length}m × ${projectDimensions.width}m`;
  } else if (calculationType === 'volume' && projectDimensions) {
    dimensionDetails = `a volume of ${projectDimensions.length}m × ${projectDimensions.width}m × ${projectDimensions.depth}m`;
  } else if (calculationType === 'linear' && projectDimensions) {
    dimensionDetails = `a length of ${projectDimensions.length}m`;
  }
  
  return `You need ${quantity} ${productUnit}(s) of ${productName} for ${dimensionDetails}. Total cost: $${totalCost}`;
};

// Export default object for backward compatibility
const calculatorChatService = {
  shareCalculationWithChatbot,
  formatCalculatorMessage
};

export default calculatorChatService;
