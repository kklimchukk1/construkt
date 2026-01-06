import React from 'react';
import ProductCard from './ProductCard';
import LoadingIndicator from '../layout/LoadingIndicator';
import ErrorMessage from '../layout/ErrorMessage';
import './ProductList.css';

const ProductList = ({ products, loading, error }) => {
  if (loading) {
    return (
      <div className="product-list-loading">
        <LoadingIndicator 
          type="spinner" 
          size="large" 
          text="Loading products..." 
        />
      </div>
    );
  }

  if (error) {
    return (
      <div className="product-list-error">
        <ErrorMessage 
          message={`Error loading products: ${error}`}
          type="error"
          retry={true}
          onRetry={() => window.location.reload()}
        />
      </div>
    );
  }

  if (!products || products.length === 0) {
    return (
      <div className="product-list-empty">
        <p>No products found matching your criteria.</p>
        <p>Try adjusting your filters or search terms.</p>
      </div>
    );
  }

  return (
    <div className="product-list">
      {products.map(product => (
        <div key={product.id} className="product-list-item">
          <ProductCard product={product} />
        </div>
      ))}
    </div>
  );
};

export default ProductList;
