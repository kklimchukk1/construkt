import React from 'react';
import { Card, Button, Badge } from 'react-bootstrap';
import { FaShoppingCart, FaExternalLinkAlt } from 'react-icons/fa';
import './ProductCard.css';

/**
 * ProductCard - Displays a product from the database
 * Shows real product data with link to product page
 */
const ProductCard = ({ product, compact = false, onProductClick }) => {
  if (!product) return null;

  const handleViewProduct = () => {
    if (onProductClick) {
      onProductClick(product);
    } else {
      // Navigate to product page
      window.location.href = product.link || `/products/${product.id}`;
    }
  };

  const handleAddToCart = (e) => {
    e.stopPropagation();
    // TODO: Implement add to cart functionality
    console.log('Add to cart:', product.id);
  };

  if (compact) {
    return (
      <div className="product-card-compact" onClick={handleViewProduct}>
        <div className="product-info-compact">
          <span className="product-name-compact">{product.name}</span>
          <span className="product-price-compact">{product.price}/{product.unit}</span>
        </div>
        <FaExternalLinkAlt className="product-link-icon" size={10} />
      </div>
    );
  }

  return (
    <Card className="product-card">
      {product.thumbnail && (
        <div className="product-thumbnail">
          <img
            src={product.thumbnail}
            alt={product.name}
            onError={(e) => {
              e.target.style.display = 'none';
            }}
          />
        </div>
      )}
      <Card.Body className="product-body">
        <div className="product-header">
          <Card.Title className="product-name">{product.name}</Card.Title>
          {product.in_stock ? (
            <Badge bg="success" className="stock-badge">In Stock</Badge>
          ) : (
            <Badge bg="secondary" className="stock-badge">Out of Stock</Badge>
          )}
        </div>

        {product.description && (
          <Card.Text className="product-description">
            {product.description}
          </Card.Text>
        )}

        <div className="product-meta">
          {product.category && (
            <span className="product-category">{product.category}</span>
          )}
          {product.supplier && (
            <span className="product-supplier">{product.supplier}</span>
          )}
        </div>

        <div className="product-footer">
          <div className="product-price">
            <span className="price-value">{product.price}</span>
            <span className="price-unit">/ {product.unit}</span>
          </div>
          <div className="product-actions">
            <Button
              variant="outline-primary"
              size="sm"
              onClick={handleViewProduct}
              className="view-button"
            >
              View <FaExternalLinkAlt size={10} />
            </Button>
            <Button
              variant="primary"
              size="sm"
              onClick={handleAddToCart}
              disabled={!product.in_stock}
              className="cart-button"
            >
              <FaShoppingCart size={12} />
            </Button>
          </div>
        </div>
      </Card.Body>
    </Card>
  );
};

export default ProductCard;
