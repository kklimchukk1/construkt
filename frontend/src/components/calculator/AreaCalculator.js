import React, { useState, useEffect } from 'react';
import { shareCalculationWithChatbot } from '../../services/calculatorChatService';
import calculatorService from '../../services/calculatorService';

/**
 * Area Calculator Component
 * 
 * This component provides a form for calculating area-based materials
 * such as flooring, paint, etc.
 */
const AreaCalculator = ({ productDimensions, productName, onCalculationStart, onCalculationResult, onCalculationError }) => {
  const [formData, setFormData] = useState({
    projectLength: '',
    projectWidth: '',
    coverage: '',
    wastage: '10',
    materialType: 'paint'
  });
  
  // Initialize coverage and material type based on product dimensions and name
  useEffect(() => {
    const newFormData = { ...formData };
    
    // Set coverage if available in product dimensions
    if (productDimensions) {
      let dims = productDimensions;
      if (typeof dims === 'string') {
        try {
          dims = JSON.parse(dims);
        } catch (e) {
          dims = null;
        }
      }
      
      if (dims && dims.coverage) {
        newFormData.coverage = dims.coverage.toString();
      }
    }
    
    // Set material type based on product name
    if (productName) {
      const lowerName = productName.toLowerCase();
      if (lowerName.includes('paint') || lowerName.includes('coating')) {
        newFormData.materialType = 'paint';
      } else if (lowerName.includes('tile') || lowerName.includes('ceramic')) {
        newFormData.materialType = 'tiles';
      } else if (lowerName.includes('floor') || lowerName.includes('laminate')) {
        newFormData.materialType = 'flooring';
      } else if (lowerName.includes('wall') || lowerName.includes('paper')) {
        newFormData.materialType = 'wallpaper';
      } else if (lowerName.includes('insul')) {
        newFormData.materialType = 'insulation';
      }
    }
    
    setFormData(newFormData);
  }, [productDimensions, productName]);
  
  // Material options for area calculations
  const materialOptions = [
    { value: 'paint', label: 'Paint', defaultCoverage: 10 },
    { value: 'flooring', label: 'Flooring', defaultCoverage: 1 },
    { value: 'tiles', label: 'Tiles', defaultCoverage: 1 },
    { value: 'wallpaper', label: 'Wallpaper', defaultCoverage: 5 },
    { value: 'insulation', label: 'Insulation', defaultCoverage: 2 },
    { value: 'custom', label: 'Custom Material', defaultCoverage: '' }
  ];

  // Handle form input changes
  const handleChange = (e) => {
    const { name, value } = e.target;
    
    // If material type is changed, update the default coverage
    if (name === 'materialType' && value !== 'custom') {
      const selectedMaterial = materialOptions.find(option => option.value === value);
      setFormData({
        ...formData,
        [name]: value,
        coverage: selectedMaterial ? selectedMaterial.defaultCoverage.toString() : ''
      });
    } else {
      setFormData({
        ...formData,
        [name]: value
      });
    }
  };

  // Handle form submission
  const handleSubmit = async (e) => {
    e.preventDefault();
    
    // Validate inputs
    if (!formData.projectLength || !formData.projectWidth || !formData.coverage) {
      onCalculationError(new Error('Please fill in all required project dimensions'));
      return;
    }
    
    // Convert to numbers
    const calculationData = {
      length: parseFloat(formData.projectLength),
      width: parseFloat(formData.projectWidth),
      coverage: parseFloat(formData.coverage),
      wastage: parseFloat(formData.wastage)
    };
    
    // Validate numeric values
    if (isNaN(calculationData.length) || isNaN(calculationData.width) ||
        isNaN(calculationData.coverage) || isNaN(calculationData.wastage)) {
      onCalculationError(new Error('Please enter valid numeric values'));
      return;
    }

    // Validate positive values to prevent division by zero
    if (calculationData.length <= 0 || calculationData.width <= 0 || calculationData.coverage <= 0) {
      onCalculationError(new Error('Length, width, and coverage must be greater than zero'));
      return;
    }
    
    // Start calculation
    onCalculationStart();
    
    try {
      // Call the calculator service
      const result = await calculatorService.calculateArea(calculationData);
      console.log('Area calculator raw result:', result);
      
      // Make sure we have a valid result object
      if (result && typeof result === 'object') {
        onCalculationResult(result, calculationData);
      } else {
        // If the result is not in the expected format, create a fallback result
        const area = calculationData.length * calculationData.width;
        const wastageAmount = area * (calculationData.wastage / 100);
        const requiredArea = area + wastageAmount;
        
        const fallbackResult = {
          success: true,
          result: {
            area: area.toFixed(2),
            requiredArea: requiredArea.toFixed(2),
            wastagePercentage: calculationData.wastage,
            wastageAmount: wastageAmount.toFixed(2),
            coverage: calculationData.coverage,
            unitsNeeded: Math.ceil(requiredArea / calculationData.coverage),
            requiredQuantity: Math.ceil(requiredArea / calculationData.coverage)
          }
        };
        console.log('Using fallback calculation result:', fallbackResult);
        onCalculationResult(fallbackResult, calculationData);
      }
    } catch (error) {
      console.error('Error in area calculation:', error);
      onCalculationError(error);
    }
  };

  return (
    <div className="calculator-form">
      <h3>Area Calculator</h3>
      
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
                          {dims.width && <div>Width: {dims.width}m</div>}
                          {dims.coverage && <div>Coverage: {dims.coverage}m²</div>}
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
                  {productDimensions.width && <div>Width: {productDimensions.width}m</div>}
                  {productDimensions.coverage && <div>Coverage: {productDimensions.coverage}m²</div>}
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
            placeholder="Enter project length"
            required
          />
        </div>
        
        <div className="form-group">
          <label htmlFor="projectWidth">Project Width (meters):</label>
          <input
            type="number"
            id="projectWidth"
            name="projectWidth"
            value={formData.projectWidth}
            onChange={handleChange}
            min="0.01"
            step="0.01"
            placeholder="Enter project width"
            required
          />
        </div>
        
        <div className="form-group">
          <label htmlFor="coverage">Material Coverage (m² per unit):</label>
          <input
            type="number"
            id="coverage"
            name="coverage"
            value={formData.coverage}
            onChange={handleChange}
            min="0.01"
            step="0.01"
            placeholder="Enter coverage"
            required
          />
          <small>How many square meters one unit of material covers (e.g., 1 liter of paint covers 10m²)</small>
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
          <small>Additional material to account for wastage (default: 10%)</small>
        </div>
        
        <button type="submit" className="calculator-button">Calculate</button>
        
        <div className="calculator-tips">
          <h4>Tips:</h4>
          <ul>
            <li>For walls, use the length as the perimeter and width as the height</li>
            <li>For ceilings, simply enter the room dimensions</li>
            <li>Coverage refers to how many square meters one unit covers (e.g., 1L of paint)</li>
          </ul>
        </div>
      </form>
    </div>
  );
};

export default AreaCalculator;
