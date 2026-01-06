import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import authService from '../services/authService';
import httpService from '../services/httpService';
import './CustomerDashboard.css';

function CustomerDashboard() {
  const navigate = useNavigate();
  const [user, setUser] = useState(null);
  const [activeTab, setActiveTab] = useState('profile');
  const [orders, setOrders] = useState([]);
  const [cart, setCart] = useState([]);
  const [loading, setLoading] = useState(true);
  const [editMode, setEditMode] = useState(false);
  const [profileData, setProfileData] = useState({});
  const [supportMessages, setSupportMessages] = useState([]);
  const [newMessage, setNewMessage] = useState('');

  useEffect(() => {
    const currentUser = authService.getCurrentUser();
    if (!currentUser) {
      navigate('/login');
      return;
    }
    setUser(currentUser);
    setProfileData(currentUser);
    loadData();
  }, [navigate]);

  const loadData = async () => {
    setLoading(true);
    try {
      const [ordersRes, cartRes, messagesRes] = await Promise.all([
        httpService.get('/orders/my').catch(() => ({ data: [] })),
        httpService.get('/cart').catch(() => ({ data: [] })),
        httpService.get('/support/messages').catch(() => ({ data: [] }))
      ]);
      setOrders(ordersRes.data || []);
      setCart(cartRes.data || []);
      setSupportMessages(messagesRes.data || []);
    } catch (error) {
      console.error('Error loading data:', error);
    }
    setLoading(false);
  };

  const handleProfileUpdate = async (e) => {
    e.preventDefault();
    try {
      await authService.updateProfile(profileData);
      setUser(profileData);
      setEditMode(false);
      alert('Profile updated successfully!');
    } catch (error) {
      alert('Error updating profile');
    }
  };

  const handleSendMessage = async (e) => {
    e.preventDefault();
    if (!newMessage.trim()) return;

    try {
      await httpService.post('/support/messages', { message: newMessage });
      setNewMessage('');
      loadData();
    } catch (error) {
      console.error('Error sending message:', error);
    }
  };

  const removeFromCart = async (itemId) => {
    try {
      await httpService.delete(`/cart/${itemId}`);
      loadData();
    } catch (error) {
      console.error('Error removing from cart:', error);
    }
  };

  const checkout = async () => {
    try {
      await httpService.post('/orders', { items: cart });
      alert('Order placed successfully!');
      loadData();
    } catch (error) {
      alert('Error placing order');
    }
  };

  const getStatusColor = (status) => {
    const colors = {
      pending: '#f59e0b',
      processing: '#3b82f6',
      shipped: '#8b5cf6',
      delivered: '#22c55e',
      cancelled: '#ef4444'
    };
    return colors[status] || '#64748b';
  };

  if (!user) return null;

  return (
    <div className="customer-dashboard">
      {/* Sidebar */}
      <div className="dashboard-sidebar">
        <div className="user-info">
          <div className="user-avatar">
            {user.first_name?.[0]}{user.last_name?.[0]}
          </div>
          <h3>{user.first_name} {user.last_name}</h3>
          <p>{user.email}</p>
        </div>

        <nav className="dashboard-nav">
          <button
            className={`nav-item ${activeTab === 'profile' ? 'active' : ''}`}
            onClick={() => setActiveTab('profile')}
          >
            <span className="nav-icon">👤</span>
            Profile
          </button>
          <button
            className={`nav-item ${activeTab === 'orders' ? 'active' : ''}`}
            onClick={() => setActiveTab('orders')}
          >
            <span className="nav-icon">📦</span>
            My Orders
            {orders.length > 0 && <span className="badge">{orders.length}</span>}
          </button>
          <button
            className={`nav-item ${activeTab === 'cart' ? 'active' : ''}`}
            onClick={() => setActiveTab('cart')}
          >
            <span className="nav-icon">🛒</span>
            Cart
            {cart.length > 0 && <span className="badge">{cart.length}</span>}
          </button>
          <button
            className={`nav-item ${activeTab === 'support' ? 'active' : ''}`}
            onClick={() => setActiveTab('support')}
          >
            <span className="nav-icon">💬</span>
            Support Chat
          </button>
        </nav>
      </div>

      {/* Main Content */}
      <div className="dashboard-main">
        {/* Profile Tab */}
        {activeTab === 'profile' && (
          <div className="dashboard-section">
            <div className="section-header">
              <h2>My Profile</h2>
              <button
                className="edit-btn"
                onClick={() => setEditMode(!editMode)}
              >
                {editMode ? 'Cancel' : 'Edit Profile'}
              </button>
            </div>

            <form onSubmit={handleProfileUpdate} className="profile-form">
              <div className="form-row">
                <div className="form-group">
                  <label>First Name</label>
                  <input
                    type="text"
                    value={profileData.first_name || ''}
                    onChange={(e) => setProfileData({...profileData, first_name: e.target.value})}
                    disabled={!editMode}
                  />
                </div>
                <div className="form-group">
                  <label>Last Name</label>
                  <input
                    type="text"
                    value={profileData.last_name || ''}
                    onChange={(e) => setProfileData({...profileData, last_name: e.target.value})}
                    disabled={!editMode}
                  />
                </div>
              </div>

              <div className="form-group">
                <label>Email</label>
                <input
                  type="email"
                  value={profileData.email || ''}
                  disabled
                />
              </div>

              <div className="form-group">
                <label>Phone</label>
                <input
                  type="tel"
                  value={profileData.phone || ''}
                  onChange={(e) => setProfileData({...profileData, phone: e.target.value})}
                  disabled={!editMode}
                />
              </div>

              <div className="form-group">
                <label>Address</label>
                <textarea
                  value={profileData.address || ''}
                  onChange={(e) => setProfileData({...profileData, address: e.target.value})}
                  disabled={!editMode}
                />
              </div>

              <div className="form-row">
                <div className="form-group">
                  <label>City</label>
                  <input
                    type="text"
                    value={profileData.city || ''}
                    onChange={(e) => setProfileData({...profileData, city: e.target.value})}
                    disabled={!editMode}
                  />
                </div>
                <div className="form-group">
                  <label>Postal Code</label>
                  <input
                    type="text"
                    value={profileData.postal_code || ''}
                    onChange={(e) => setProfileData({...profileData, postal_code: e.target.value})}
                    disabled={!editMode}
                  />
                </div>
              </div>

              {editMode && (
                <button type="submit" className="save-btn">Save Changes</button>
              )}
            </form>
          </div>
        )}

        {/* Orders Tab */}
        {activeTab === 'orders' && (
          <div className="dashboard-section">
            <div className="section-header">
              <h2>My Orders</h2>
            </div>

            {loading ? (
              <div className="loading">Loading orders...</div>
            ) : orders.length === 0 ? (
              <div className="empty-state">
                <span className="empty-icon">📦</span>
                <h3>No orders yet</h3>
                <p>Start shopping to see your orders here</p>
                <button onClick={() => navigate('/products')} className="shop-btn">
                  Browse Products
                </button>
              </div>
            ) : (
              <div className="orders-list">
                {orders.map(order => (
                  <div key={order.id} className="order-card">
                    <div className="order-header">
                      <div>
                        <span className="order-id">Order #{order.id}</span>
                        <span className="order-date">
                          {new Date(order.created_at).toLocaleDateString()}
                        </span>
                      </div>
                      <span
                        className="order-status"
                        style={{ backgroundColor: getStatusColor(order.status) }}
                      >
                        {order.status}
                      </span>
                    </div>
                    <div className="order-items">
                      {order.items?.map(item => (
                        <div key={item.id} className="order-item">
                          <span>{item.product_name}</span>
                          <span>x{item.quantity}</span>
                          <span>${item.price}</span>
                        </div>
                      ))}
                    </div>
                    <div className="order-footer">
                      <span className="order-total">Total: ${order.total_amount}</span>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        )}

        {/* Cart Tab */}
        {activeTab === 'cart' && (
          <div className="dashboard-section">
            <div className="section-header">
              <h2>Shopping Cart</h2>
            </div>

            {cart.length === 0 ? (
              <div className="empty-state">
                <span className="empty-icon">🛒</span>
                <h3>Your cart is empty</h3>
                <p>Add products to your cart to see them here</p>
                <button onClick={() => navigate('/products')} className="shop-btn">
                  Browse Products
                </button>
              </div>
            ) : (
              <>
                <div className="cart-items">
                  {cart.map(item => (
                    <div key={item.id} className="cart-item">
                      <div className="item-image">
                        {item.thumbnail ? (
                          <img src={item.thumbnail} alt={item.product_name} />
                        ) : (
                          <span>📦</span>
                        )}
                      </div>
                      <div className="item-details">
                        <h4>{item.product_name}</h4>
                        <p>Qty: {item.quantity}</p>
                      </div>
                      <div className="item-price">${item.price * item.quantity}</div>
                      <button
                        className="remove-btn"
                        onClick={() => removeFromCart(item.id)}
                      >
                        ✕
                      </button>
                    </div>
                  ))}
                </div>
                <div className="cart-summary">
                  <div className="summary-row">
                    <span>Subtotal</span>
                    <span>${cart.reduce((sum, item) => sum + item.price * item.quantity, 0).toFixed(2)}</span>
                  </div>
                  <button className="checkout-btn" onClick={checkout}>
                    Proceed to Checkout
                  </button>
                </div>
              </>
            )}
          </div>
        )}

        {/* Support Chat Tab */}
        {activeTab === 'support' && (
          <div className="dashboard-section chat-section">
            <div className="section-header">
              <h2>Support Chat</h2>
            </div>

            <div className="chat-container">
              <div className="chat-messages">
                {supportMessages.length === 0 ? (
                  <div className="chat-empty">
                    <span>💬</span>
                    <p>Start a conversation with our support team</p>
                  </div>
                ) : (
                  supportMessages.map(msg => (
                    <div
                      key={msg.id}
                      className={`chat-message ${msg.is_from_customer ? 'sent' : 'received'}`}
                    >
                      <div className="message-content">{msg.message}</div>
                      <div className="message-time">
                        {new Date(msg.created_at).toLocaleTimeString()}
                      </div>
                    </div>
                  ))
                )}
              </div>

              <form onSubmit={handleSendMessage} className="chat-input">
                <input
                  type="text"
                  value={newMessage}
                  onChange={(e) => setNewMessage(e.target.value)}
                  placeholder="Type your message..."
                />
                <button type="submit">Send</button>
              </form>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

export default CustomerDashboard;
