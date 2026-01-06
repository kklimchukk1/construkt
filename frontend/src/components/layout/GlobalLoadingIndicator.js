import React, { useState, useEffect } from 'react';
import httpService from '../../services/httpService';
import LoadingIndicator from './LoadingIndicator';
import './GlobalLoadingIndicator.css';

/**
 * A global loading indicator that shows when any HTTP request is in progress
 * This component should be placed at the top level of the application
 */
function GlobalLoadingIndicator() {
  const [isLoading, setIsLoading] = useState(false);

  useEffect(() => {
    // Subscribe to loading state changes
    const unsubscribe = httpService.addLoadingListener(setIsLoading);
    
    // Cleanup subscription on unmount
    return () => unsubscribe();
  }, []);

  if (!isLoading) return null;

  return (
    <div className="global-loading-container">
      <LoadingIndicator 
        type="spinner" 
        size="small" 
        text="" 
        className="global-loading-indicator" 
      />
    </div>
  );
}

export default GlobalLoadingIndicator;
