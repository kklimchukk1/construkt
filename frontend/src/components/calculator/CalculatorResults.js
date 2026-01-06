import React from 'react';

/**
 * Calculator Results Component
 * 
 * This component displays the calculation results based on the type
 * of calculation performed (area, volume, linear).
 */
const CalculatorResults = ({ type, result }) => {
  if (!result) return null;

  // Format the result rows based on calculation type
  const getResultRows = () => {
    switch (type) {
      case 'area':
        return [
          { label: 'Total Area', value: `${result.area} m²` },
          { label: 'Required Quantity', value: `${result.requiredQuantity} units` },
          { label: 'Wastage', value: `${result.wastagePercentage}% (${result.wastageAmount} units)` }
        ];
      case 'volume':
        return [
          { label: 'Total Volume', value: `${result.volume} m³` },
          { label: 'Required Volume', value: `${result.requiredVolume} m³` },
          { label: 'Wastage', value: `${result.wastagePercentage}% (${result.wastageAmount} m³)` }
        ];
      case 'linear':
        const rows = [
          { label: 'Total Length', value: `${result.length} m` },
          { label: 'Required Length', value: `${result.requiredLength} m` },
          { label: 'Wastage', value: `${result.wastagePercentage}% (${result.wastageAmount} m)` }
        ];
        
        // Add pieces information if available
        if (result.pieceLength && result.piecesNeeded) {
          rows.push({ 
            label: 'Pieces Needed', 
            value: `${result.piecesNeeded} pieces (${result.pieceLength} m each)` 
          });
        }
        
        return rows;
      default:
        return [];
    }
  };

  return (
    <div className="calculator-results">
      <h3>Calculation Results</h3>
      
      {getResultRows().map((row, index) => (
        <div key={index} className="result-row">
          <span className="result-label">{row.label}:</span>
          <span className="result-value">{row.value}</span>
        </div>
      ))}
      
      <div className="result-note">
        <p>
          <strong>Note:</strong> These calculations are estimates. Actual material needs may vary 
          based on specific project conditions and material properties.
        </p>
      </div>
    </div>
  );
};

export default CalculatorResults;
