import React, { useState, useEffect } from 'react';
import calculatorService from '../../services/calculatorService';

/**
 * Linear Calculator Component
 * 
 * This component provides a form for calculating linear materials
 * such as pipes, cables, moldings, etc.
 */
const LinearCalculator = ({ productDimensions, productName, onCalculationStart, onCalculationResult, onCalculationError }) => {
  const [formData, setFormData] = useState({
    projectLength: '',
    pieceLength: '',
    wastage: '5',
    materialType: 'pipe'
  });
  
  // Initialize piece length and material type based on product dimensions and name
  useEffect(() => {
    const newFormData = { ...formData };
    
    // Set piece length if available in product dimensions
    if (productDimensions) {
      let dims = productDimensions;
      if (typeof dims === 'string') {
        try {
          dims = JSON.parse(dims);
        } catch (e) {
          dims = null;
        }
      }
      
      if (dims && dims.length) {
        newFormData.pieceLength = dims.length.toString();
      }
    }
    
    // Set material type based on product name
    if (productName) {
      const lowerName = productName.toLowerCase();
      if (lowerName.includes('pipe')) {
        newFormData.materialType = 'pipe';
      } else if (lowerName.includes('cable') || lowerName.includes('wire')) {
        newFormData.materialType = 'cable';
      } else if (lowerName.includes('molding') || lowerName.includes('trim')) {
        newFormData.materialType = 'molding';
      } else if (lowerName.includes('rebar')) {
        newFormData.materialType = 'rebar';
      } else if (lowerName.includes('lumber') || lowerName.includes('wood')) {
        newFormData.materialType = 'lumber';
      }
    }
    
    setFormData(newFormData);
  }, [productDimensions, productName]);
  
  // Material options for linear calculations
  const materialOptions = [
    { value: 'pipe', label: 'Pipes' },
    { value: 'cable', label: 'Cables/Wires' },
    { value: 'molding', label: 'Moldings/Trim' },
    { value: 'rebar', label: 'Rebar' },
    { value: 'lumber', label: 'Lumber' },
    { value: 'custom', label: 'Custom Material' }
  ];

  // Handle form input changes
  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData({
      ...formData,
      [name]: value
    });
  };

  // Handle form submission
  const handleSubmit = async (e) => {
    e.preventDefault();
    
    // Validate inputs
    if (!formData.projectLength) {
      onCalculationError(new Error('Please enter the required project length'));
      return;
    }
    
    // Convert to numbers
    const calculationData = {
      length: parseFloat(formData.projectLength),
      wastage: parseFloat(formData.wastage)
    };
    
    // Add piece length if provided
    if (formData.pieceLength) {
      calculationData.pieceLength = parseFloat(formData.pieceLength);
    }
    
    // Validate numeric values
    if (isNaN(calculationData.length) || isNaN(calculationData.wastage) || 
        (formData.pieceLength && isNaN(calculationData.pieceLength))) {
      onCalculationError(new Error('Please enter valid numeric values'));
      return;
    }
    
    // Start calculation
    onCalculationStart();
    
    try {
      // Call the calculator service
      const result = await calculatorService.calculateLinear(calculationData);
      console.log('Linear calculator raw result:', result);
      
      // Make sure we have a valid result object
      if (result && typeof result === 'object') {
        onCalculationResult(result, calculationData);
      } else {
        // If the result is not in the expected format, create a fallback result
        const fallbackResult = {
          success: true,
          result: {
            length: calculationData.length,
            requiredLength: calculationData.length * (1 + (calculationData.wastage / 100)),
            wastagePercentage: calculationData.wastage,
            wastageAmount: calculationData.length * (calculationData.wastage / 100)
          }
        };
        console.log('Using fallback calculation result:', fallbackResult);
        onCalculationResult(fallbackResult, calculationData);
      }
    } catch (error) {
      console.error('Error in linear calculation:', error);
      onCalculationError(error);
    }
  };

  return (
    <div className="calculator-form">
      <h3>Linear Calculator</h3>
      
      <form onSubmit={handleSubmit}>
        
        {productDimensions && (
          <div className="product-dimensions-info">
            <h4>Product Dimensions:</h4>
            <div className="dimensions-display">
              {typeof productDimensions === 'string' ? (
                <div>
                  {(() => {
                    try {
                      const dims = JSON.parse(productDimensions);
                      return (
                        <>
                          {dims.length && <div>Length: {dims.length}m</div>}
                        </>
                      );
                    } catch (e) {
                      return <div>{productDimensions}</div>;
                    }
                  })()}
                </div>
              ) : (
                <>
                  {productDimensions.length && <div>Length: {productDimensions.length}m</div>}
                </>
              )}
            </div>
          </div>
        )}

        <h4>Project Dimensions:</h4>
        <div className="form-group">
          <label htmlFor="projectLength">Project Length (meters):</label>
          <input
            type="number"
            id="projectLength"
            name="projectLength"
            value={formData.projectLength}
            onChange={handleChange}
            min="0.01"
            step="0.01"
            placeholder="Enter total project length"
            required
          />
        </div>
        
        <div className="form-group">
          <label htmlFor="pieceLength">Piece Length (meters, optional):</label>
          <input
            type="number"
            id="pieceLength"
            name="pieceLength"
            value={formData.pieceLength}
            onChange={handleChange}
            min="0.01"
            step="0.01"
            placeholder="Enter piece length"
          />
          <small>Length of each piece (e.g., standard pipe length). Leave blank if not applicable.</small>
        </div>
        
        <div className="form-group">
          <label htmlFor="wastage">Wastage (%):</label>
          <input
            type="number"
            id="wastage"
            name="wastage"
            value={formData.wastage}
            onChange={handleChange}
            min="0"
            max="100"
            step="1"
            placeholder="Enter wastage percentage"
          />
          <small>Additional material to account for wastage (default: 5%)</small>
        </div>
        
        <button type="submit" className="calculator-button">Calculate</button>
        
        <div className="calculator-tips">
          <h4>Tips:</h4>
          <ul>
            <li>For pipes or cables, measure the total run length needed</li>
            <li>If using standard length pieces, enter the piece length to calculate how many pieces you need</li>
            <li>Add extra length for connections and bends</li>
          </ul>
        </div>
      </form>
    </div>
  );
};

export default LinearCalculator;
