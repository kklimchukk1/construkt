// This file contains the updated onCalculationResult handlers for the calculator components

// Area Calculator
const areaCalculatorHandler = `onCalculationResult={(result, data) => {
  console.log('Calculation result:', result, data);
  setCalculationResult(result);
  setIsCalculating(false);
  // Share calculation with chatbot
  calculatorChatService.shareCalculationWithChatbot(result, data, 'area', product);
}}`;

// Volume Calculator
const volumeCalculatorHandler = `onCalculationResult={(result, data) => {
  console.log('Calculation result:', result, data);
  setCalculationResult(result);
  setIsCalculating(false);
  // Share calculation with chatbot
  calculatorChatService.shareCalculationWithChatbot(result, data, 'volume', product);
}}`;

// Linear Calculator
const linearCalculatorHandler = `onCalculationResult={(result, data) => {
  console.log('Calculation result:', result, data);
  setCalculationResult(result);
  setIsCalculating(false);
  // Share calculation with chatbot
  calculatorChatService.shareCalculationWithChatbot(result, data, 'linear', product);
}}`;
