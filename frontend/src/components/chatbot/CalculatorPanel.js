import React, { useState } from 'react';
import { FaTimes, FaCalculator, FaRuler, FaCube, FaArrowLeft } from 'react-icons/fa';
import './CalculatorPanel.css';

/**
 * CalculatorPanel - Inline calculator for material calculations
 * All calculations happen locally, no messages sent to chat
 */
const CalculatorPanel = ({ onCancel }) => {
  const [calcType, setCalcType] = useState('');
  const [length, setLength] = useState('');
  const [width, setWidth] = useState('');
  const [depth, setDepth] = useState('');
  const [wastage, setWastage] = useState('10');
  const [result, setResult] = useState(null);

  const calculatorTypes = [
    { type: 'area', label: 'Area', unit: 'm²', icon: <FaRuler />, description: 'Length × Width', color: '#10b981' },
    { type: 'volume', label: 'Volume', unit: 'm³', icon: <FaCube />, description: 'L × W × Depth', color: '#6366f1' },
    { type: 'linear', label: 'Linear', unit: 'm', icon: <FaRuler style={{transform: 'rotate(45deg)'}} />, description: 'Total length', color: '#f59e0b' }
  ];

  const handleTypeSelect = (type) => {
    setCalcType(type);
    setLength('');
    setWidth('');
    setDepth('');
    setWastage('10');
    setResult(null);
  };

  const calculate = () => {
    const l = parseFloat(length) || 0;
    const w = parseFloat(width) || 0;
    const d = parseFloat(depth) || 0;
    const waste = parseFloat(wastage) || 0;

    let value = 0;
    let unit = '';

    if (calcType === 'area') {
      value = l * w;
      unit = 'm²';
    } else if (calcType === 'volume') {
      value = l * w * d;
      unit = 'm³';
    } else if (calcType === 'linear') {
      value = l;
      unit = 'm';
    }

    const wasteAmount = value * (waste / 100);
    const total = value + wasteAmount;

    setResult({
      base: value.toFixed(2),
      wastage: wasteAmount.toFixed(2),
      total: total.toFixed(2),
      unit
    });
  };

  const isValid = () => {
    if (!calcType || !length || parseFloat(length) <= 0) return false;
    if ((calcType === 'area' || calcType === 'volume') && (!width || parseFloat(width) <= 0)) return false;
    if (calcType === 'volume' && (!depth || parseFloat(depth) <= 0)) return false;
    return true;
  };

  const handleBack = () => {
    if (result) {
      setResult(null);
    } else if (calcType) {
      setCalcType('');
    } else {
      onCancel();
    }
  };

  const resetAll = () => {
    setCalcType('');
    setLength('');
    setWidth('');
    setDepth('');
    setWastage('10');
    setResult(null);
  };

  const currentType = calculatorTypes.find(c => c.type === calcType);

  return (
    <div className="calc-panel">
      {/* Header */}
      <div className="calc-header">
        <button className="calc-back-btn" onClick={handleBack}>
          <FaArrowLeft />
        </button>
        <span className="calc-title">
          <FaCalculator className="calc-icon" />
          {calcType ? currentType?.label : 'Calculator'}
        </span>
        <button className="calc-close-btn" onClick={onCancel}>
          <FaTimes />
        </button>
      </div>

      {/* Type Selection */}
      {!calcType && (
        <div className="calc-types">
          {calculatorTypes.map((calc) => (
            <button
              key={calc.type}
              className="calc-type-btn"
              style={{ '--type-color': calc.color }}
              onClick={() => handleTypeSelect(calc.type)}
            >
              <span className="calc-type-icon">{calc.icon}</span>
              <span className="calc-type-label">{calc.label}</span>
              <span className="calc-type-unit">{calc.unit}</span>
            </button>
          ))}
        </div>
      )}

      {/* Input Form */}
      {calcType && !result && (
        <div className="calc-form">
          <div className="calc-inputs">
            <div className="calc-input-group">
              <label>Length (m)</label>
              <input
                type="number"
                step="0.01"
                min="0"
                placeholder="0.00"
                value={length}
                onChange={(e) => setLength(e.target.value)}
                autoFocus
              />
            </div>

            {(calcType === 'area' || calcType === 'volume') && (
              <div className="calc-input-group">
                <label>Width (m)</label>
                <input
                  type="number"
                  step="0.01"
                  min="0"
                  placeholder="0.00"
                  value={width}
                  onChange={(e) => setWidth(e.target.value)}
                />
              </div>
            )}

            {calcType === 'volume' && (
              <div className="calc-input-group">
                <label>Depth (m)</label>
                <input
                  type="number"
                  step="0.01"
                  min="0"
                  placeholder="0.00"
                  value={depth}
                  onChange={(e) => setDepth(e.target.value)}
                />
              </div>
            )}

            <div className="calc-input-group wastage-group">
              <label>Wastage (%)</label>
              <input
                type="number"
                step="1"
                min="0"
                max="50"
                value={wastage}
                onChange={(e) => setWastage(e.target.value)}
              />
            </div>
          </div>

          <button
            className="calc-submit-btn"
            onClick={calculate}
            disabled={!isValid()}
            style={{ '--type-color': currentType?.color }}
          >
            <FaCalculator />
            Calculate
          </button>
        </div>
      )}

      {/* Result Display */}
      {result && (
        <div className="calc-result" style={{ '--type-color': currentType?.color }}>
          <div className="calc-result-main">
            <span className="calc-result-value">{result.total}</span>
            <span className="calc-result-unit">{result.unit}</span>
          </div>

          <div className="calc-result-details">
            <div className="calc-detail-row">
              <span>Base {currentType?.label}:</span>
              <span>{result.base} {result.unit}</span>
            </div>
            <div className="calc-detail-row">
              <span>+ Wastage ({wastage}%):</span>
              <span>{result.wastage} {result.unit}</span>
            </div>
            <div className="calc-detail-row total">
              <span>Total Required:</span>
              <span>{result.total} {result.unit}</span>
            </div>
          </div>

          <div className="calc-result-actions">
            <button className="calc-action-btn recalc" onClick={() => setResult(null)}>
              Edit Values
            </button>
            <button className="calc-action-btn new" onClick={resetAll}>
              New Calculation
            </button>
          </div>
        </div>
      )}
    </div>
  );
};

export default CalculatorPanel;
