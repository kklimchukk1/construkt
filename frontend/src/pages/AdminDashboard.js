import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import authService from '../services/authService';
import httpService from '../services/httpService';
import './AdminDashboard.css';

function AdminDashboard() {
  const navigate = useNavigate();
  const [user, setUser] = useState(null);
  const [activeTab, setActiveTab] = useState('users');
  const [users, setUsers] = useState([]);
  const [products, setProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [stats, setStats] = useState({ users: 0, products: 0 });
  const [loading, setLoading] = useState(true);

  // Modals
  const [showProductModal, setShowProductModal] = useState(false);
  const [showCategoryModal, setShowCategoryModal] = useState(false);
  const [showUserModal, setShowUserModal] = useState(false);
  const [editingProduct, setEditingProduct] = useState(null);
  const [editingCategory, setEditingCategory] = useState(null);
  const [editingUser, setEditingUser] = useState(null);

  // Forms
  const [productForm, setProductForm] = useState({
    name: '', description: '', price: '', unit: 'piece',
    stock_quantity: '', category_id: '', is_featured: false, is_active: true,
    calculation_type: 'unit'
  });
  const [categoryForm, setCategoryForm] = useState({
    name: '', description: '', parent_id: '', is_active: true
  });
  const [userForm, setUserForm] = useState({
    role: 'customer', is_active: true
  });

  useEffect(() => {
    const currentUser = authService.getCurrentUser();
    if (!currentUser || currentUser.role !== 'admin') {
      navigate('/login');
      return;
    }
    setUser(currentUser);
    loadData();
  }, [navigate]);

  const loadData = async () => {
    setLoading(true);
    try {
      const [usersRes, productsRes, categoriesRes] = await Promise.all([
        httpService.get('/users').catch(() => ({ data: [] })),
        httpService.get('/products').catch(() => ({ data: { products: [] } })),
        httpService.get('/categories').catch(() => ({ data: [] }))
      ]);

      const usersData = usersRes.data || [];
      const productsData = productsRes.data?.products || productsRes.data || [];

      setUsers(usersData);
      setProducts(productsData);
      setCategories(categoriesRes.data?.categories || categoriesRes.data || []);

      setStats({
        users: usersData.length,
        products: productsData.length
      });
    } catch (error) {
      console.error('Error loading data:', error);
    }
    setLoading(false);
  };

  // User Management
  const updateUserRole = async (userId, role) => {
    try {
      await httpService.put(`/users/${userId}/role`, { role });
      loadData();
    } catch (error) {
      alert('Error updating user role');
    }
  };

  const toggleUserStatus = async (userId, isActive) => {
    try {
      await httpService.put(`/users/${userId}/status`, { is_active: isActive });
      loadData();
    } catch (error) {
      alert('Error updating user status');
    }
  };

  const deleteUser = async (userId) => {
    if (userId === user.id) {
      alert("You cannot delete your own account!");
      return;
    }
    if (window.confirm('Are you sure you want to delete this user?')) {
      try {
        await httpService.delete(`/users/${userId}`);
        loadData();
      } catch (error) {
        alert('Error deleting user');
      }
    }
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
      calculation_type: product.calculation_type || 'unit'
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
      calculation_type: 'unit'
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

  const getRoleBadgeClass = (role) => {
    return `role-badge role-${role}`;
  };

  if (!user) return null;

  return (
    <div className="admin-dashboard">
      {/* Sidebar */}
      <div className="dashboard-sidebar">
        <div className="panel-title">
          <span className="panel-icon">👑</span>
          <h2>Admin Panel</h2>
        </div>

        <nav className="dashboard-nav">
          <button
            className={`nav-item ${activeTab === 'overview' ? 'active' : ''}`}
            onClick={() => setActiveTab('overview')}
          >
            <span className="nav-icon">📊</span>
            Overview
          </button>
          <button
            className={`nav-item ${activeTab === 'users' ? 'active' : ''}`}
            onClick={() => setActiveTab('users')}
          >
            <span className="nav-icon">👥</span>
            Users
            <span className="count">{users.length}</span>
          </button>
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
        </nav>
      </div>

      {/* Main Content */}
      <div className="dashboard-main">
        {/* Overview Tab */}
        {activeTab === 'overview' && (
          <div className="dashboard-section">
            <div className="section-header">
              <h2>Dashboard Overview</h2>
            </div>

            <div className="stats-grid">
              <div className="stat-card">
                <div className="stat-icon users">👥</div>
                <div className="stat-info">
                  <span className="stat-value">{stats.users}</span>
                  <span className="stat-label">Total Users</span>
                </div>
              </div>
              <div className="stat-card">
                <div className="stat-icon products">📦</div>
                <div className="stat-info">
                  <span className="stat-value">{stats.products}</span>
                  <span className="stat-label">Products</span>
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Users Tab */}
        {activeTab === 'users' && (
          <div className="dashboard-section">
            <div className="section-header">
              <h2>Users Management</h2>
            </div>

            <div className="data-table">
              <table>
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {users.map(u => (
                    <tr key={u.id}>
                      <td>{u.id}</td>
                      <td>{u.first_name} {u.last_name}</td>
                      <td>{u.email}</td>
                      <td>
                        <select
                          value={u.role}
                          onChange={(e) => updateUserRole(u.id, e.target.value)}
                          className="role-select"
                          disabled={u.id === user.id}
                        >
                          <option value="customer">Customer</option>
                          <option value="supplier">Supplier/Manager</option>
                          <option value="admin">Admin</option>
                        </select>
                      </td>
                      <td>
                        <span className={`status ${u.is_active ? 'active' : 'inactive'}`}>
                          {u.is_active ? 'Active' : 'Inactive'}
                        </span>
                      </td>
                      <td>
                        <div className="actions">
                          <button
                            className={u.is_active ? 'deactivate-btn' : 'activate-btn'}
                            onClick={() => toggleUserStatus(u.id, !u.is_active)}
                            disabled={u.id === user.id}
                          >
                            {u.is_active ? 'Deactivate' : 'Activate'}
                          </button>
                          <button
                            className="delete-btn"
                            onClick={() => deleteUser(u.id)}
                            disabled={u.id === user.id}
                          >
                            Delete
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}

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
                <button type="button" className="cancel-btn" onClick={() => setShowProductModal(false)}>Cancel</button>
                <button type="submit" className="submit-btn">{editingProduct ? 'Update' : 'Add'} Product</button>
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
                <button type="button" className="cancel-btn" onClick={() => setShowCategoryModal(false)}>Cancel</button>
                <button type="submit" className="submit-btn">{editingCategory ? 'Update' : 'Add'} Category</button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}

export default AdminDashboard;
