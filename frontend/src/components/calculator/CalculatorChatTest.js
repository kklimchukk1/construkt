import React from 'react';
import calculatorChatService from '../../services/calculatorChatService';

/**
 * Test component for calculator-chatbot integration
 */
const CalculatorChatTest = ({ product }) => {
  // Sample calculation result for testing
  const testAreaCalculation = () => {
    const sampleResult = {
      success: true,
      result: {
        area: 25,
        requiredArea: 27.5,
        wastagePercentage: 10,
        wastageAmount: 2.5,
        coverage: 5,
        unitsNeeded: 6
      }
    };
    
    const sampleData = {
      length: 5,
      width: 5,
      coverage: 5,
      wastage: 10
    };
    
    calculatorChatService.shareCalculationWithChatbot(sampleResult, sampleData, 'area', product);
  };
  
  const testVolumeCalculation = () => {
    const sampleResult = {
      success: true,
      result: {
        volume: 30,
        requiredVolume: 33,
        wastagePercentage: 10,
        wastageAmount: 3
      }
    };
    
    const sampleData = {
      length: 5,
      width: 3,
      depth: 2,
      wastage: 10
    };
    
    calculatorChatService.shareCalculationWithChatbot(sampleResult, sampleData, 'volume', product);
  };
  
  const testLinearCalculation = () => {
    const sampleResult = {
      success: true,
      result: {
        requiredLength: 22,
        wastagePercentage: 10,
        wastageAmount: 2
      }
    };
    
    const sampleData = {
      length: 20,
      wastage: 10
    };
    
    calculatorChatService.shareCalculationWithChatbot(sampleResult, sampleData, 'linear', product);
  };
  
  return (
    <div className="calculator-chat-test">
      <h4>Test Calculator-Chatbot Integration</h4>
      <p>Click the buttons below to test the integration between the calculator and chatbot:</p>
      
      <div className="test-buttons">
        <button 
          className="btn btn-sm btn-outline-primary" 
          onClick={testAreaCalculation}
        >
          Test Area Calculation
        </button>
        
        <button 
          className="btn btn-sm btn-outline-primary ml-2" 
          onClick={testVolumeCalculation}
        >
          Test Volume Calculation
        </button>
        
        <button 
          className="btn btn-sm btn-outline-primary ml-2" 
          onClick={testLinearCalculation}
        >
          Test Linear Calculation
        </button>
      </div>
    </div>
  );
};

export default CalculatorChatTest;
