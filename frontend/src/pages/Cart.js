import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faTrash, faMinus, faPlus, faShoppingCart, faArrowLeft } from '@fortawesome/free-solid-svg-icons';
import orderService from '../services/orderService';
import authService from '../services/authService';
import './Cart.css';

function Cart() {
  const navigate = useNavigate();
  const [cartItems, setCartItems] = useState([]);
  const [loading, setLoading] = useState(true);
  const [updating, setUpdating] = useState(null);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (!authService.isAuthenticated()) {
      navigate('/login');
      return;
    }
    fetchCart();
  }, [navigate]);

  const fetchCart = async () => {
    try {
      setLoading(true);
      const data = await orderService.getCart();
      setCartItems(Array.isArray(data) ? data : []);
      setError(null);
    } catch (err) {
      console.error('Error fetching cart:', err);
      setError('Failed to load cart');
    } finally {
      setLoading(false);
    }
  };

  const handleUpdateQuantity = async (itemId, newQuantity) => {
    if (newQuantity < 1) return;

    setUpdating(itemId);
    try {
      await orderService.updateCartItem(itemId, newQuantity);
      setCartItems(items =>
        items.map(item =>
          item.id === itemId ? { ...item, quantity: newQuantity } : item
        )
      );
    } catch (err) {
      console.error('Error updating quantity:', err);
    } finally {
      setUpdating(null);
    }
  };

  const handleRemoveItem = async (itemId) => {
    setUpdating(itemId);
    try {
      await orderService.removeFromCart(itemId);
      setCartItems(items => items.filter(item => item.id !== itemId));
    } catch (err) {
      console.error('Error removing item:', err);
    } finally {
      setUpdating(null);
    }
  };

  const handleClearCart = async () => {
    if (!window.confirm('Are you sure you want to clear the cart?')) return;

    try {
      await orderService.clearCart();
      setCartItems([]);
    } catch (err) {
      console.error('Error clearing cart:', err);
    }
  };

  const calculateTotal = () => {
    return cartItems.reduce((total, item) => {
      const price = parseFloat(item.price) || 0;
      const quantity = parseInt(item.quantity) || 0;
      return total + (price * quantity);
    }, 0);
  };

  const handleCheckout = async () => {
    try {
      const total = calculateTotal();
      await orderService.createOrder({
        items: cartItems.map(item => ({
          product_id: item.product_id,
          quantity: item.quantity,
          price: item.price
        })),
        total_amount: total
      });
      alert('Order placed successfully!');
      setCartItems([]);
      navigate('/dashboard');
    } catch (err) {
      console.error('Error creating order:', err);
      alert('Failed to place order. Please try again.');
    }
  };

  if (loading) {
    return (
      <div className="cart-page">
        <div className="cart-loading">
          <div className="spinner"></div>
          <p>Loading cart...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="cart-page">
      <div className="cart-header">
        <h1>
          <FontAwesomeIcon icon={faShoppingCart} /> Shopping Cart
        </h1>
        <Link to="/products" className="continue-shopping">
          <FontAwesomeIcon icon={faArrowLeft} /> Continue Shopping
        </Link>
      </div>

      {error && (
        <div className="cart-error">
          {error}
          <button onClick={fetchCart}>Try Again</button>
        </div>
      )}

      {cartItems.length === 0 ? (
        <div className="cart-empty">
          <FontAwesomeIcon icon={faShoppingCart} className="empty-icon" />
          <h2>Your cart is empty</h2>
          <p>Add some products to your cart to see them here.</p>
          <Link to="/products" className="shop-now-btn">
            Browse Products
          </Link>
        </div>
      ) : (
        <div className="cart-content">
          <div className="cart-items">
            <table className="cart-table">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Price</th>
                  <th>Quantity</th>
                  <th>Subtotal</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {cartItems.map(item => (
                  <tr key={item.id} className={updating === item.id ? 'updating' : ''}>
                    <td className="product-cell">
                      <div className="product-info">
                        {item.thumbnail && (
                          <img src={item.thumbnail} alt={item.product_name} />
                        )}
                        <div>
                          <Link to={`/products/${item.product_id}`}>
                            {item.product_name || `Product #${item.product_id}`}
                          </Link>
                          {item.unit && <span className="unit">per {item.unit}</span>}
                        </div>
                      </div>
                    </td>
                    <td className="price-cell">
                      ${parseFloat(item.price || 0).toFixed(2)}
                    </td>
                    <td className="quantity-cell">
                      <div className="quantity-controls">
                        <button
                          onClick={() => handleUpdateQuantity(item.id, item.quantity - 1)}
                          disabled={updating === item.id || item.quantity <= 1}
                        >
                          <FontAwesomeIcon icon={faMinus} />
                        </button>
                        <span>{item.quantity}</span>
                        <button
                          onClick={() => handleUpdateQuantity(item.id, item.quantity + 1)}
                          disabled={updating === item.id}
                        >
                          <FontAwesomeIcon icon={faPlus} />
                        </button>
                      </div>
                    </td>
                    <td className="subtotal-cell">
                      ${(parseFloat(item.price || 0) * parseInt(item.quantity || 0)).toFixed(2)}
                    </td>
                    <td className="actions-cell">
                      <button
                        className="remove-btn"
                        onClick={() => handleRemoveItem(item.id)}
                        disabled={updating === item.id}
                        title="Remove from cart"
                      >
                        <FontAwesomeIcon icon={faTrash} />
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>

            <div className="cart-actions">
              <button className="clear-cart-btn" onClick={handleClearCart}>
                Clear Cart
              </button>
            </div>
          </div>

          <div className="cart-summary">
            <h2>Order Summary</h2>
            <div className="summary-row">
              <span>Items ({cartItems.reduce((sum, item) => sum + item.quantity, 0)})</span>
              <span>${calculateTotal().toFixed(2)}</span>
            </div>
            <div className="summary-row">
              <span>Shipping</span>
              <span>Free</span>
            </div>
            <div className="summary-divider"></div>
            <div className="summary-row total">
              <span>Total</span>
              <span>${calculateTotal().toFixed(2)}</span>
            </div>
            <button className="checkout-btn" onClick={handleCheckout}>
              Proceed to Checkout
            </button>
          </div>
        </div>
      )}
    </div>
  );
}

export default Cart;
