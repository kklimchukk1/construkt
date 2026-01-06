import React, { useState, useEffect } from 'react';
import httpService from '../../services/httpService';
import ErrorMessage from './ErrorMessage';
import './GlobalErrorHandler.css';

/**
 * A global error handler that shows error messages from HTTP requests
 * This component should be placed at the top level of the application
 */
function GlobalErrorHandler() {
  const [errors, setErrors] = useState([]);

  useEffect(() => {
    // Subscribe to error notifications
    const unsubscribe = httpService.addErrorListener((error) => {
      const errorId = Date.now().toString();
      const formattedMessage = httpService.formatErrorMessage(error);
      
      setErrors(prev => [...prev, { id: errorId, message: formattedMessage, error }]);
      
      // Auto-dismiss errors after 5 seconds
      setTimeout(() => {
        dismissError(errorId);
      }, 5000);
    });
    
    // Cleanup subscription on unmount
    return () => unsubscribe();
  }, []);

  const dismissError = (errorId) => {
    setErrors(prev => prev.filter(error => error.id !== errorId));
  };

  const retryRequest = (error) => {
    // This is a placeholder for retry functionality
    // In a real implementation, you would extract the request details from the error
    // and retry the request
    console.log('Retrying request:', error);
    dismissError(error.id);
  };

  if (errors.length === 0) return null;

  return (
    <div className="global-error-container">
      {errors.map(error => (
        <ErrorMessage
          key={error.id}
          message={error.message}
          type="error"
          dismissible={true}
          onDismiss={() => dismissError(error.id)}
          retry={false} // Set to true if you implement retry functionality
          onRetry={() => retryRequest(error)}
        />
      ))}
    </div>
  );
}

export default GlobalErrorHandler;
