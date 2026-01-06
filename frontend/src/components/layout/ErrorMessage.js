import React from 'react';
import './ErrorMessage.css';

/**
 * A reusable error message component
 * @param {Object} props
 * @param {string} props.message - The error message to display
 * @param {string} props.type - Type of error ('error', 'warning', 'info')
 * @param {boolean} props.dismissible - Whether the error can be dismissed
 * @param {function} props.onDismiss - Function to call when error is dismissed
 * @param {boolean} props.retry - Whether to show retry button
 * @param {function} props.onRetry - Function to call when retry is clicked
 * @param {string} props.className - Additional CSS classes
 */
function ErrorMessage({ 
  message = 'An error occurred',
  type = 'error',
  dismissible = true,
  onDismiss = () => {},
  retry = false,
  onRetry = () => {},
  className = ''
}) {
  const errorClass = `error-message ${type} ${className}`;
  
  return (
    <div className={errorClass}>
      <div className="error-icon">
        {type === 'error' && <i className="fas fa-exclamation-circle"></i>}
        {type === 'warning' && <i className="fas fa-exclamation-triangle"></i>}
        {type === 'info' && <i className="fas fa-info-circle"></i>}
      </div>
      <div className="error-content">
        <p>{message}</p>
        <div className="error-actions">
          {retry && (
            <button className="retry-button" onClick={onRetry}>
              <i className="fas fa-redo"></i> Try Again
            </button>
          )}
        </div>
      </div>
      {dismissible && (
        <button className="dismiss-button" onClick={onDismiss}>
          <i className="fas fa-times"></i>
        </button>
      )}
    </div>
  );
}

export default ErrorMessage;
