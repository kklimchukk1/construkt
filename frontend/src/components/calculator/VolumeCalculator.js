import React, { useState, useEffect } from 'react';
import calculatorService from '../../services/calculatorService';

/**
 * Volume Calculator Component
 * 
 * This component provides a form for calculating volume-based materials
 * such as concrete, gravel, etc.
 */
const VolumeCalculator = ({ productDimensions, productName, onCalculationStart, onCalculationResult, onCalculationError }) => {
  const [formData, setFormData] = useState({
    projectLength: '',
    projectWidth: '',
    projectDepth: '',
    wastage: '15',
    materialType: 'concrete'
  });
  
  // Initialize material type based on product name
  useEffect(() => {
    if (productName) {
      const newFormData = { ...formData };
      const lowerName = productName.toLowerCase();
      
      // Set material type based on product name
      if (lowerName.includes('concrete')) {
        newFormData.materialType = 'concrete';
      } else if (lowerName.includes('gravel')) {
        newFormData.materialType = 'gravel';
      } else if (lowerName.includes('sand')) {
        newFormData.materialType = 'sand';
      } else if (lowerName.includes('soil') || lowerName.includes('dirt')) {
        newFormData.materialType = 'soil';
      } else if (lowerName.includes('mulch')) {
        newFormData.materialType = 'mulch';
      }
      
      setFormData(newFormData);
    }
  }, [productName]);
  
  // Material options for volume calculations
  const materialOptions = [
    { value: 'concrete', label: 'Concrete' },
    { value: 'gravel', label: 'Gravel' },
    { value: 'sand', label: 'Sand' },
    { value: 'soil', label: 'Soil/Dirt' },
    { value: 'mulch', label: 'Mulch' },
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
    if (!formData.projectLength || !formData.projectWidth || !formData.projectDepth) {
      onCalculationError(new Error('Please fill in all required project dimensions'));
      return;
    }
    
    // Convert to numbers
    const calculationData = {
      length: parseFloat(formData.projectLength),
      width: parseFloat(formData.projectWidth),
      depth: parseFloat(formData.projectDepth),
      wastage: parseFloat(formData.wastage)
    };
    
    // Validate numeric values
    if (isNaN(calculationData.length) || isNaN(calculationData.width) || 
        isNaN(calculationData.depth) || isNaN(calculationData.wastage)) {
      onCalculationError(new Error('Please enter valid numeric values'));
      return;
    }
    
    // Start calculation
    onCalculationStart();
    
    try {
      // Call the calculator service
      const result = await calculatorService.calculateVolume(calculationData);
      console.log('Volume calculator raw result:', result);
      
      // Make sure we have a valid result object
      if (result && typeof result === 'object') {
        onCalculationResult(result, calculationData);
      } else {
        // If the result is not in the expected format, create a fallback result
        const volume = calculationData.length * calculationData.width * calculationData.depth;
        const wastageAmount = volume * (calculationData.wastage / 100);
        const requiredVolume = volume + wastageAmount;
        
        const fallbackResult = {
          success: true,
          result: {
            volume: volume,
            requiredVolume: requiredVolume,
            wastagePercentage: calculationData.wastage,
            wastageAmount: wastageAmount
          }
        };
        console.log('Using fallback calculation result:', fallbackResult);
        onCalculationResult(fallbackResult, calculationData);
      }
    } catch (error) {
      console.error('Error in volume calculation:', error);
      onCalculationError(error);
    }
  };

  return (
    <div className="calculator-form">
      <h3>Volume Calculator</h3>
      <p>Calculate materials needed for concrete, gravel, or other volume-based applications.</p>
      
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
                          {dims.height && <div>Height: {dims.height}m</div>}
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
                  {productDimensions.height && <div>Height: {productDimensions.height}m</div>}
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
          <label htmlFor="projectDepth">Project Depth (meters):</label>
          <input
            type="number"
            id="projectDepth"
            name="projectDepth"
            value={formData.projectDepth}
            onChange={handleChange}
            min="0.01"
            step="0.01"
            placeholder="Enter project depth"
            required
          />
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
          <small>Additional material to account for wastage (default: 15%)</small>
        </div>
        
        <button type="submit" className="calculator-button">Calculate</button>
        
        <div className="calculator-tips">
          <h4>Tips:</h4>
          <ul>
            <li>For slabs, enter the length, width, and thickness</li>
            <li>For footings, measure the total length of all footings</li>
            <li>Standard concrete wastage is 15% to account for spillage</li>
          </ul>
        </div>
      </form>
    </div>
  );
};

export default VolumeCalculator;
