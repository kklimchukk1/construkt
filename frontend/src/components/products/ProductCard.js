import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import './ProductCard.css';
import unknown_material from '../../assets/unknown_material.png';
import orderService from '../../services/orderService';
import authService from '../../services/authService';

const ProductCard = ({ product }) => {
  const navigate = useNavigate();
  const [adding, setAdding] = useState(false);
  const [added, setAdded] = useState(false);

  if (!product) {
    return <div className="product-card product-card-empty">Product information unavailable</div>;
  }

  const {
    id = 0,
    name = 'Unnamed Product',
    featured = false,
    stock_quantity = 0,
    category_name = 'Uncategorized',
    supplier_name = 'Unknown Supplier',
    rating = 0,
    price = 0,
    unit = 'unit'
  } = product;

  const handleAddToCart = async (e) => {
    e.preventDefault();
    e.stopPropagation();

    if (!authService.isAuthenticated()) {
      navigate('/login');
      return;
    }

    if (stock_quantity <= 0) return;

    setAdding(true);
    try {
      await orderService.addToCart(id, 1);
      setAdded(true);
      setTimeout(() => setAdded(false), 2000);
    } catch (error) {
      console.error('Error adding to cart:', error);
    }
    setAdding(false);
  };

  return (
    <div className="product-card">
      <div className="product-image">
        {product.thumbnail ? (
          <img
            src={product.thumbnail}
            alt={name}
            onError={(e) => {
              e.target.onerror = null;
              e.target.src = unknown_material;
            }}
          />
        ) : (
          <div className="product-image-placeholder">📦</div>
        )}
        {featured && <span className="featured-badge">Featured</span>}
        {stock_quantity <= 0 && <div className="out-of-stock">Out of Stock</div>}
      </div>

      <div className="product-info">
        <div className="product-category">{category_name}</div>

        <h3 className="product-name">
          <Link to={`/products/${id}`}>{name}</Link>
        </h3>

        {supplier_name && <div className="product-supplier">{supplier_name}</div>}

        {rating > 0 && (
          <div className="product-rating">
            {[...Array(5)].map((_, i) => (
              <span
                key={i}
                className={`star ${i < Math.floor(rating) ? 'filled' : ''}`}
              >
                ★
              </span>
            ))}
            <span className="rating-value">({rating.toFixed(1)})</span>
          </div>
        )}

        <div className="product-price">
          ${parseFloat(price).toFixed(2)} <span className="product-unit">/ {unit}</span>
        </div>
      </div>

      <div className="product-actions">
        <button
          className={`add-to-cart-btn ${added ? 'added' : ''}`}
          onClick={handleAddToCart}
          disabled={adding || stock_quantity <= 0}
        >
          {adding ? 'Adding...' : added ? 'Added ✓' : 'Add to Cart'}
        </button>
        <Link to={`/products/${id}`} className="view-details-btn">
          Details
        </Link>
      </div>
    </div>
  );
};

export default ProductCard;
