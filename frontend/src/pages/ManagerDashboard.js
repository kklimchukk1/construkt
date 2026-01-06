import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import authService from '../services/authService';
import httpService from '../services/httpService';
import './ManagerDashboard.css';

function ManagerDashboard() {
  const navigate = useNavigate();
  const [user, setUser] = useState(null);
  const [activeTab, setActiveTab] = useState('products');
  const [products, setProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [orders, setOrders] = useState([]);
  const [chats, setChats] = useState([]);
  const [loading, setLoading] = useState(true);

  // Modals
  const [showProductModal, setShowProductModal] = useState(false);
  const [showCategoryModal, setShowCategoryModal] = useState(false);
  const [editingProduct, setEditingProduct] = useState(null);
  const [editingCategory, setEditingCategory] = useState(null);

  // Forms
  const [productForm, setProductForm] = useState({
    name: '', description: '', price: '', unit: 'piece',
    stock_quantity: '', category_id: '', is_featured: false, is_active: true,
    calculation_type: 'unit', thumbnail: ''
  });
  const [categoryForm, setCategoryForm] = useState({
    name: '', description: '', parent_id: '', is_active: true
  });

  // Chat
  const [selectedChat, setSelectedChat] = useState(null);
  const [chatMessages, setChatMessages] = useState([]);
  const [newMessage, setNewMessage] = useState('');

  useEffect(() => {
    const currentUser = authService.getCurrentUser();
    if (!currentUser || (currentUser.role !== 'supplier' && currentUser.role !== 'admin')) {
      navigate('/login');
      return;
    }
    setUser(currentUser);
    loadData();
  }, [navigate]);

  const loadData = async () => {
    setLoading(true);
    try {
      const [productsRes, categoriesRes, ordersRes, chatsRes] = await Promise.all([
        httpService.get('/products').catch(() => ({ data: { products: [] } })),
        httpService.get('/categories').catch(() => ({ data: [] })),
        httpService.get('/orders').catch(() => ({ data: [] })),
        httpService.get('/support/chats').catch(() => ({ data: [] }))
      ]);
      setProducts(productsRes.data?.products || productsRes.data || []);
      setCategories(categoriesRes.data?.categories || categoriesRes.data || []);
      setOrders(ordersRes.data || []);
      setChats(chatsRes.data || []);
    } catch (error) {
      console.error('Error loading data:', error);
    }
    setLoading(false);
  };

  // Product CRUD
  const handleProductSubmit = async (e) => {
    e.preventDefault();
    try {
      if (editingProduct) {
        await httpService.put(`/products/${editingProduct.id}`, productForm);
      } else {
        await httpService.post('/products', productForm);
      }
      setShowProductModal(false);
      resetProductForm();
      loadData();
    } catch (error) {
      alert('Error saving product');
    }
  };

  const editProduct = (product) => {
    setEditingProduct(product);
    setProductForm({
      name: product.name,
      description: product.description || '',
      price: product.price,
      unit: product.unit || 'piece',
      stock_quantity: product.stock_quantity,
      category_id: product.category_id,
      is_featured: product.is_featured,
      is_active: product.is_active,
      calculation_type: product.calculation_type || 'unit',
      thumbnail: product.thumbnail || ''
    });
    setShowProductModal(true);
  };

  const deleteProduct = async (id) => {
    if (window.confirm('Are you sure you want to delete this product?')) {
      try {
        await httpService.delete(`/products/${id}`);
        loadData();
      } catch (error) {
        alert('Error deleting product');
      }
    }
  };

  const resetProductForm = () => {
    setEditingProduct(null);
    setProductForm({
      name: '', description: '', price: '', unit: 'piece',
      stock_quantity: '', category_id: '', is_featured: false, is_active: true,
      calculation_type: 'unit', thumbnail: ''
    });
  };

  // Category CRUD
  const handleCategorySubmit = async (e) => {
    e.preventDefault();
    try {
      if (editingCategory) {
        await httpService.put(`/categories/${editingCategory.id}`, categoryForm);
      } else {
        await httpService.post('/categories', categoryForm);
      }
      setShowCategoryModal(false);
      resetCategoryForm();
      loadData();
    } catch (error) {
      alert('Error saving category');
    }
  };

  const editCategory = (category) => {
    setEditingCategory(category);
    setCategoryForm({
      name: category.name,
      description: category.description || '',
      parent_id: category.parent_id || '',
      is_active: category.is_active
    });
    setShowCategoryModal(true);
  };

  const deleteCategory = async (id) => {
    if (window.confirm('Are you sure you want to delete this category?')) {
      try {
        await httpService.delete(`/categories/${id}`);
        loadData();
      } catch (error) {
        alert('Error deleting category');
      }
    }
  };

  const resetCategoryForm = () => {
    setEditingCategory(null);
    setCategoryForm({ name: '', description: '', parent_id: '', is_active: true });
  };

  // Orders
  const updateOrderStatus = async (orderId, status) => {
    try {
      await httpService.put(`/orders/${orderId}/status`, { status });
      loadData();
    } catch (error) {
      alert('Error updating order status');
    }
  };

  // Chat
  const loadChatMessages = async (customerId) => {
    try {
      const res = await httpService.get(`/support/messages/${customerId}`);
      setChatMessages(res.data || []);
      setSelectedChat(customerId);
    } catch (error) {
      console.error('Error loading messages:', error);
    }
  };

  const sendMessage = async (e) => {
    e.preventDefault();
    if (!newMessage.trim() || !selectedChat) return;
    try {
      await httpService.post('/support/messages', {
        customer_id: selectedChat,
        message: newMessage,
        is_from_customer: false
      });
      setNewMessage('');
      loadChatMessages(selectedChat);
    } catch (error) {
      console.error('Error sending message:', error);
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
    <div className="manager-dashboard">
      {/* Sidebar */}
      <div className="dashboard-sidebar">
        <div className="panel-title">
          <span className="panel-icon">🏪</span>
          <h2>Manager Panel</h2>
        </div>

        <nav className="dashboard-nav">
          <button
            className={`nav-item ${activeTab === 'products' ? 'active' : ''}`}
            onClick={() => setActiveTab('products')}
          >
            <span className="nav-icon">📦</span>
            Products
            <span className="count">{products.length}</span>
          </button>
          <button
            className={`nav-item ${activeTab === 'categories' ? 'active' : ''}`}
            onClick={() => setActiveTab('categories')}
          >
            <span className="nav-icon">📂</span>
            Categories
            <span className="count">{categories.length}</span>
          </button>
          <button
            className={`nav-item ${activeTab === 'orders' ? 'active' : ''}`}
            onClick={() => setActiveTab('orders')}
          >
            <span className="nav-icon">🛒</span>
            Orders
            <span className="count">{orders.length}</span>
          </button>
          <button
            className={`nav-item ${activeTab === 'chat' ? 'active' : ''}`}
            onClick={() => setActiveTab('chat')}
          >
            <span className="nav-icon">💬</span>
            Customer Chat
            {chats.filter(c => c.unread > 0).length > 0 && (
              <span className="badge">{chats.filter(c => c.unread > 0).length}</span>
            )}
          </button>
        </nav>
      </div>

      {/* Main Content */}
      <div className="dashboard-main">
        {/* Products Tab */}
        {activeTab === 'products' && (
          <div className="dashboard-section">
            <div className="section-header">
              <h2>Products Management</h2>
              <button className="add-btn" onClick={() => { resetProductForm(); setShowProductModal(true); }}>
                + Add Product
              </button>
            </div>

            <div className="data-table">
              <table>
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Calc Type</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {products.map(product => (
                    <tr key={product.id}>
                      <td>{product.id}</td>
                      <td>
                        <div className="product-cell">
                          <span className="product-name">{product.name}</span>
                          {product.is_featured && <span className="featured-tag">Featured</span>}
                        </div>
                      </td>
                      <td>{categories.find(c => c.id === product.category_id)?.name || '-'}</td>
                      <td>${product.price}/{product.unit}</td>
                      <td>{product.stock_quantity}</td>
                      <td><span className="calc-type">{product.calculation_type || 'unit'}</span></td>
                      <td>
                        <span className={`status ${product.is_active ? 'active' : 'inactive'}`}>
                          {product.is_active ? 'Active' : 'Inactive'}
                        </span>
                      </td>
                      <td>
                        <div className="actions">
                          <button className="edit-btn" onClick={() => editProduct(product)}>Edit</button>
                          <button className="delete-btn" onClick={() => deleteProduct(product.id)}>Delete</button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}

        {/* Categories Tab */}
        {activeTab === 'categories' && (
          <div className="dashboard-section">
            <div className="section-header">
              <h2>Categories Management</h2>
              <button className="add-btn" onClick={() => { resetCategoryForm(); setShowCategoryModal(true); }}>
                + Add Category
              </button>
            </div>

            <div className="data-table">
              <table>
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Parent</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {categories.map(category => (
                    <tr key={category.id}>
                      <td>{category.id}</td>
                      <td>{category.name}</td>
                      <td>{categories.find(c => c.id === category.parent_id)?.name || '-'}</td>
                      <td className="desc-cell">{category.description || '-'}</td>
                      <td>
                        <span className={`status ${category.is_active ? 'active' : 'inactive'}`}>
                          {category.is_active ? 'Active' : 'Inactive'}
                        </span>
                      </td>
                      <td>
                        <div className="actions">
                          <button className="edit-btn" onClick={() => editCategory(category)}>Edit</button>
                          <button className="delete-btn" onClick={() => deleteCategory(category.id)}>Delete</button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}

        {/* Orders Tab */}
        {activeTab === 'orders' && (
          <div className="dashboard-section">
            <div className="section-header">
              <h2>Orders Management</h2>
            </div>

            <div className="data-table">
              <table>
                <thead>
                  <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {orders.map(order => (
                    <tr key={order.id}>
                      <td>#{order.id}</td>
                      <td>{order.customer_name || order.user_id}</td>
                      <td>{new Date(order.created_at).toLocaleDateString()}</td>
                      <td>${order.total_amount}</td>
                      <td>
                        <span
                          className="order-status"
                          style={{ backgroundColor: getStatusColor(order.status) }}
                        >
                          {order.status}
                        </span>
                      </td>
                      <td>
                        <select
                          value={order.status}
                          onChange={(e) => updateOrderStatus(order.id, e.target.value)}
                          className="status-select"
                        >
                          <option value="pending">Pending</option>
                          <option value="processing">Processing</option>
                          <option value="shipped">Shipped</option>
                          <option value="delivered">Delivered</option>
                          <option value="cancelled">Cancelled</option>
                        </select>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}

        {/* Chat Tab */}
        {activeTab === 'chat' && (
          <div className="dashboard-section chat-section">
            <div className="section-header">
              <h2>Customer Support Chat</h2>
            </div>

            <div className="chat-layout">
              <div className="chat-list">
                {chats.length === 0 ? (
                  <div className="no-chats">No conversations yet</div>
                ) : (
                  chats.map(chat => (
                    <div
                      key={chat.customer_id}
                      className={`chat-item ${selectedChat === chat.customer_id ? 'active' : ''}`}
                      onClick={() => loadChatMessages(chat.customer_id)}
                    >
                      <div className="chat-avatar">
                        {chat.customer_name?.[0] || 'U'}
                      </div>
                      <div className="chat-info">
                        <span className="chat-name">{chat.customer_name || 'Customer'}</span>
                        <span className="chat-preview">{chat.last_message}</span>
                      </div>
                      {chat.unread > 0 && <span className="unread-badge">{chat.unread}</span>}
                    </div>
                  ))
                )}
              </div>

              <div className="chat-window">
                {!selectedChat ? (
                  <div className="no-chat-selected">
                    <span>💬</span>
                    <p>Select a conversation to start chatting</p>
                  </div>
                ) : (
                  <>
                    <div className="chat-messages">
                      {chatMessages.map(msg => (
                        <div
                          key={msg.id}
                          className={`chat-message ${msg.is_from_customer ? 'received' : 'sent'}`}
                        >
                          <div className="message-content">{msg.message}</div>
                          <div className="message-time">
                            {new Date(msg.created_at).toLocaleTimeString()}
                          </div>
                        </div>
                      ))}
                    </div>
                    <form onSubmit={sendMessage} className="chat-input">
                      <input
                        type="text"
                        value={newMessage}
                        onChange={(e) => setNewMessage(e.target.value)}
                        placeholder="Type your message..."
                      />
                      <button type="submit">Send</button>
                    </form>
                  </>
                )}
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Product Modal */}
      {showProductModal && (
        <div className="modal-overlay" onClick={() => setShowProductModal(false)}>
          <div className="modal" onClick={e => e.stopPropagation()}>
            <div className="modal-header">
              <h3>{editingProduct ? 'Edit Product' : 'Add New Product'}</h3>
              <button className="close-btn" onClick={() => setShowProductModal(false)}>×</button>
            </div>
            <form onSubmit={handleProductSubmit} className="modal-form">
              <div className="form-group">
                <label>Name *</label>
                <input
                  type="text"
                  value={productForm.name}
                  onChange={(e) => setProductForm({...productForm, name: e.target.value})}
                  required
                />
              </div>

              <div className="form-group">
                <label>Description</label>
                <textarea
                  value={productForm.description}
                  onChange={(e) => setProductForm({...productForm, description: e.target.value})}
                />
              </div>

              <div className="form-group">
                <label>Image URL</label>
                <input
                  type="url"
                  value={productForm.thumbnail}
                  onChange={(e) => setProductForm({...productForm, thumbnail: e.target.value})}
                  placeholder="https://example.com/image.jpg"
                />
                {productForm.thumbnail && (
                  <div className="image-preview">
                    <img
                      src={productForm.thumbnail}
                      alt="Preview"
                      onError={(e) => e.target.style.display = 'none'}
                    />
                  </div>
                )}
              </div>

              <div className="form-row">
                <div className="form-group">
                  <label>Price *</label>
                  <input
                    type="number"
                    step="0.01"
                    value={productForm.price}
                    onChange={(e) => setProductForm({...productForm, price: e.target.value})}
                    required
                  />
                </div>
                <div className="form-group">
                  <label>Unit</label>
                  <select
                    value={productForm.unit}
                    onChange={(e) => setProductForm({...productForm, unit: e.target.value})}
                  >
                    <option value="piece">Piece</option>
                    <option value="kg">Kilogram</option>
                    <option value="m">Meter</option>
                    <option value="m2">Square Meter</option>
                    <option value="m3">Cubic Meter</option>
                    <option value="liter">Liter</option>
                    <option value="bag">Bag</option>
                    <option value="box">Box</option>
                  </select>
                </div>
              </div>

              <div className="form-row">
                <div className="form-group">
                  <label>Stock Quantity *</label>
                  <input
                    type="number"
                    value={productForm.stock_quantity}
                    onChange={(e) => setProductForm({...productForm, stock_quantity: e.target.value})}
                    required
                  />
                </div>
                <div className="form-group">
                  <label>Category *</label>
                  <select
                    value={productForm.category_id}
                    onChange={(e) => setProductForm({...productForm, category_id: e.target.value})}
                    required
                  >
                    <option value="">Select Category</option>
                    {categories.map(cat => (
                      <option key={cat.id} value={cat.id}>{cat.name}</option>
                    ))}
                  </select>
                </div>
              </div>

              <div className="form-group">
                <label>Calculation Type</label>
                <select
                  value={productForm.calculation_type}
                  onChange={(e) => setProductForm({...productForm, calculation_type: e.target.value})}
                >
                  <option value="unit">Unit (per piece)</option>
                  <option value="area">Area (m2)</option>
                  <option value="volume">Volume (m3)</option>
                  <option value="length">Length (m)</option>
                  <option value="weight">Weight (kg)</option>
                </select>
              </div>

              <div className="form-row checkboxes">
                <label className="checkbox-label">
                  <input
                    type="checkbox"
                    checked={productForm.is_featured}
                    onChange={(e) => setProductForm({...productForm, is_featured: e.target.checked})}
                  />
                  Featured Product
                </label>
                <label className="checkbox-label">
                  <input
                    type="checkbox"
                    checked={productForm.is_active}
                    onChange={(e) => setProductForm({...productForm, is_active: e.target.checked})}
                  />
                  Active
                </label>
              </div>

              <div className="modal-actions">
                <button type="button" className="cancel-btn" onClick={() => setShowProductModal(false)}>
                  Cancel
                </button>
                <button type="submit" className="submit-btn">
                  {editingProduct ? 'Update Product' : 'Add Product'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Category Modal */}
      {showCategoryModal && (
        <div className="modal-overlay" onClick={() => setShowCategoryModal(false)}>
          <div className="modal" onClick={e => e.stopPropagation()}>
            <div className="modal-header">
              <h3>{editingCategory ? 'Edit Category' : 'Add New Category'}</h3>
              <button className="close-btn" onClick={() => setShowCategoryModal(false)}>×</button>
            </div>
            <form onSubmit={handleCategorySubmit} className="modal-form">
              <div className="form-group">
                <label>Name *</label>
                <input
                  type="text"
                  value={categoryForm.name}
                  onChange={(e) => setCategoryForm({...categoryForm, name: e.target.value})}
                  required
                />
              </div>

              <div className="form-group">
                <label>Description</label>
                <textarea
                  value={categoryForm.description}
                  onChange={(e) => setCategoryForm({...categoryForm, description: e.target.value})}
                />
              </div>

              <div className="form-group">
                <label>Parent Category</label>
                <select
                  value={categoryForm.parent_id}
                  onChange={(e) => setCategoryForm({...categoryForm, parent_id: e.target.value})}
                >
                  <option value="">None (Root Category)</option>
                  {categories.filter(c => c.id !== editingCategory?.id).map(cat => (
                    <option key={cat.id} value={cat.id}>{cat.name}</option>
                  ))}
                </select>
              </div>

              <label className="checkbox-label">
                <input
                  type="checkbox"
                  checked={categoryForm.is_active}
                  onChange={(e) => setCategoryForm({...categoryForm, is_active: e.target.checked})}
                />
                Active
              </label>

              <div className="modal-actions">
                <button type="button" className="cancel-btn" onClick={() => setShowCategoryModal(false)}>
                  Cancel
                </button>
                <button type="submit" className="submit-btn">
                  {editingCategory ? 'Update Category' : 'Add Category'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}

export default ManagerDashboard;
