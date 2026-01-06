import React, { useState } from 'react';
import AreaCalculator from './AreaCalculator';
import VolumeCalculator from './VolumeCalculator';
import LinearCalculator from './LinearCalculator';
import CalculatorResults from './CalculatorResults';
import LoadingIndicator from '../layout/LoadingIndicator';
import ErrorMessage from '../layout/ErrorMessage';
import { useChat } from '../../context/ChatContext';
import './Calculator.css';

/**
 * Material Calculator Component
 * 
 * This component provides a UI for calculating material quantities
 * based on different measurement types (area, volume, linear).
 */
const Calculator = () => {
  const [calculationType, setCalculationType] = useState('area');
  const [calculationResult, setCalculationResult] = useState(null);
  const [isCalculating, setIsCalculating] = useState(false);
  const [error, setError] = useState(null);
  const [calculationData, setCalculationData] = useState(null);
  const { openChat } = useChat();

  // Handle calculation type change
  const handleTypeChange = (e) => {
    setCalculationType(e.target.value);
    setCalculationResult(null);
    setError(null);
  };

  // Handle calculation result
  const handleCalculationResult = (result, data) => {
    setCalculationResult(result);
    setCalculationData(data);
    setIsCalculating(false);
  };

  // Handle calculation error
  const handleCalculationError = (error) => {
    setError(error.message || 'An error occurred during calculation');
    setIsCalculating(false);
    setCalculationResult(null);
  };

  // Share calculation with chatbot
  const shareWithChatbot = () => {
    if (!calculationResult || !calculationData) return;
    
    let message = '';
    
    if (calculationType === 'area') {
      message = `I calculated materials for an area of ${calculationData.length}m x ${calculationData.width}m`;
      if (calculationData.materialType && calculationData.materialType !== 'custom') {
        message += ` for ${calculationData.materialType}`;
      }
    } else if (calculationType === 'volume') {
      message = `I calculated materials for a volume of ${calculationData.length}m x ${calculationData.width}m x ${calculationData.depth}m`;
    } else if (calculationType === 'linear') {
      message = `I calculated materials for a length of ${calculationData.length}m`;
    }
    
    // Store calculation in localStorage to be accessed by the chatbot
    localStorage.setItem('calculatorResult', JSON.stringify({
      type: calculationType,
      data: calculationData,
      result: calculationResult.result
    }));
    
    // Store the message to be picked up by the chat context
    localStorage.setItem('calculator_pending_message', message);
    
    // Open the chat window
    openChat();
    
    // Trigger a storage event for other tabs
    const storageEvent = new Event('storage');
    storageEvent.key = 'calculator_pending_message';
    window.dispatchEvent(storageEvent);
  };
  
  // Render the appropriate calculator based on type
  const renderCalculator = () => {
    switch (calculationType) {
      case 'area':
        return (
          <AreaCalculator 
            onCalculationStart={() => {
              setIsCalculating(true);
              setError(null);
            }}
            onCalculationResult={(result, data) => handleCalculationResult(result, data)}
            onCalculationError={handleCalculationError}
          />
        );
      case 'volume':
        return (
          <VolumeCalculator 
            onCalculationStart={() => {
              setIsCalculating(true);
              setError(null);
            }}
            onCalculationResult={handleCalculationResult}
            onCalculationError={handleCalculationError}
          />
        );
      case 'linear':
        return (
          <LinearCalculator 
            onCalculationStart={() => {
              setIsCalculating(true);
              setError(null);
            }}
            onCalculationResult={handleCalculationResult}
            onCalculationError={handleCalculationError}
          />
        );
      default:
        return <p>Please select a calculation type</p>;
    }
  };

  return (
    <div className="calculator-container">
      <h2>Construction Material Calculator</h2>
      <p className="calculator-description">
        Calculate the amount of materials needed for your construction project.
      </p>
      
      <div className="calculator-type-selector">
        <label htmlFor="calculationType">Calculation Type:</label>
        <select 
          id="calculationType" 
          value={calculationType} 
          onChange={handleTypeChange}
          disabled={isCalculating}
        >
          <option value="area">Area (Flooring, Paint, etc.)</option>
          <option value="volume">Volume (Concrete, Gravel, etc.)</option>
          <option value="linear">Linear (Pipes, Cables, etc.)</option>
        </select>
      </div>
      
      {renderCalculator()}
      
      {isCalculating && (
        <div className="calculator-loading">
          <LoadingIndicator 
            type="pulse" 
            size="medium" 
            text="Calculating..." 
          />
        </div>
      )}
      
      {error && (
        <div className="calculator-error">
          <ErrorMessage 
            message={error}
            type="warning"
            dismissible={true}
            onDismiss={() => setError(null)}
            retry={true}
            onRetry={() => {
              setError(null);
              setCalculationResult(null);
            }}
          />
        </div>
      )}
      
      {calculationResult && calculationResult.success && (
        <>
          <CalculatorResults 
            type={calculationType} 
            result={calculationResult.result} 
          />
          
          <div className="calculator-actions">
            <button 
              className="share-button" 
              onClick={shareWithChatbot}
              title="Share this calculation with the chatbot"
            >
              <i className="fas fa-comment-alt"></i> Share with Chatbot
            </button>
            <button 
              className="reset-button" 
              onClick={() => {
                setCalculationResult(null);
                setCalculationData(null);
              }}
              title="Clear results and start a new calculation"
            >
              <i className="fas fa-redo"></i> New Calculation
            </button>
          </div>
        </>
      )}
    </div>
  );
};

export default Calculator;
