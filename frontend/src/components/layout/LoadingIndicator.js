import React from 'react';
import './LoadingIndicator.css';

/**
 * A reusable loading indicator component with different sizes and styles
 * @param {Object} props
 * @param {string} props.size - Size of the loader ('small', 'medium', 'large')
 * @param {string} props.type - Type of loader ('spinner', 'dots', 'pulse')
 * @param {string} props.text - Optional text to display with the loader
 * @param {boolean} props.fullPage - Whether the loader should take up the full page
 * @param {string} props.className - Additional CSS classes
 */
function LoadingIndicator({ 
  size = 'medium', 
  type = 'spinner',
  text = 'Loading...',
  fullPage = false,
  className = ''
}) {
  const loaderClass = `loading-indicator ${size} ${type} ${fullPage ? 'full-page' : ''} ${className}`;
  
  return (
    <div className={loaderClass}>
      <div className="loading-animation">
        {type === 'spinner' && (
          <div className="spinner"></div>
        )}
        {type === 'dots' && (
          <div className="dots">
            <div className="dot"></div>
            <div className="dot"></div>
            <div className="dot"></div>
          </div>
        )}
        {type === 'pulse' && (
          <div className="pulse"></div>
        )}
      </div>
      {text && <div className="loading-text">{text}</div>}
    </div>
  );
}

export default LoadingIndicator;
