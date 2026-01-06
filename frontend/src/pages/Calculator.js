import React from 'react';
import Calculator from '../components/calculator/Calculator';
import './Calculator.css';

/**
 * Calculator Page
 * 
 * This page displays the material calculator functionality.
 */
const CalculatorPage = () => {
  return (
    <div className="calculator-page">
      <div className="calculator-page-header">
        <h1>Material Calculator</h1>
        <p>
          Estimate the amount of materials needed for your construction project.
          Select the type of calculation and enter your dimensions.
        </p>
      </div>
      
      <Calculator />
      
    </div>
  );
};

export default CalculatorPage;
