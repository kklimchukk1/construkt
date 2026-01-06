import React, { useState } from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import './AuthForms.css';

const LoginForm = () => {
  const { login, error: authError } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const from = location.state?.from?.pathname || '/';
  const [formData, setFormData] = useState({
    email: '',
    password: '',
    rememberMe: true
  });
  
  const [errors, setErrors] = useState({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [loginError, setLoginError] = useState('');

  // Quick login credentials for testing (mock data - no backend needed)
  const quickLoginCredentials = [
    {
      role: 'Admin',
      email: 'admin@construkt.com',
      color: '#dc3545',
      user: { id: 1, email: 'admin@construkt.com', firstName: 'Admin', lastName: 'User', role: 'admin' }
    },
    {
      role: 'Supplier',
      email: 'supplier1@test.com',
      color: '#28a745',
      user: { id: 2, email: 'supplier1@test.com', firstName: 'John', lastName: 'Builder', role: 'supplier', companyName: 'Builder Supplies Co' }
    },
    {
      role: 'Customer',
      email: 'customer1@test.com',
      color: '#007bff',
      user: { id: 5, email: 'customer1@test.com', firstName: 'Bob', lastName: 'Smith', role: 'customer' }
    },
  ];

  const handleQuickLogin = async (credentials) => {
    setLoginError('');
    setIsSubmitting(true);

    try {
      // Mock login - directly set user in localStorage (no backend needed for demo)
      localStorage.setItem('user', JSON.stringify(credentials.user));
      localStorage.setItem('token', 'mock-token-' + credentials.role.toLowerCase());

      // Force page reload to update AuthContext
      window.location.href = from || '/';
    } catch (error) {
      setLoginError(`Failed to login as ${credentials.role}.`);
      setIsSubmitting(false);
    }
  };

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData({
      ...formData,
      [name]: type === 'checkbox' ? checked : value
    });
    
    // Clear errors when user types
    if (errors[name]) {
      setErrors({
        ...errors,
        [name]: ''
      });
    }
  };

  const validateForm = () => {
    const newErrors = {};
    
    // Email validation
    if (!formData.email) {
      newErrors.email = 'Email is required';
    } else if (!/\S+@\S+\.\S+/.test(formData.email)) {
      newErrors.email = 'Email address is invalid';
    }
    
    // Password validation
    if (!formData.password) {
      newErrors.password = 'Password is required';
    } else if (formData.password.length < 6) {
      newErrors.password = 'Password must be at least 6 characters';
    }
    
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoginError('');
    
    if (validateForm()) {
      setIsSubmitting(true);
      
      try {
        // Call the login function from AuthContext
        await login(formData.email, formData.password);
        
        // Redirect to the page the user was trying to access, or home
        navigate(from, { replace: true });
      } catch (error) {
        setLoginError('Invalid email or password. Please try again.');
      } finally {
        setIsSubmitting(false);
      }
    }
  };

  return (
    <div className="auth-form-container">
      <h2>Sign in</h2>
      
      {(loginError || authError) && (
        <div className="auth-error-message">
          {loginError || authError}
        </div>
      )}
      
      <form onSubmit={handleSubmit} className="auth-form">
        <div className="form-group">
          <label htmlFor="email">Email Address</label>
          <input
            type="email"
            id="email"
            name="email"
            value={formData.email}
            onChange={handleChange}
            className={errors.email ? 'error' : ''}
            placeholder="Enter your email"
            disabled={isSubmitting}
          />
          {errors.email && <div className="error-text">{errors.email}</div>}
        </div>
        
        <div className="form-group">
          <label htmlFor="password">Password</label>
          <input
            type="password"
            id="password"
            name="password"
            value={formData.password}
            onChange={handleChange}
            className={errors.password ? 'error' : ''}
            placeholder="Enter your password"
            disabled={isSubmitting}
          />
          {errors.password && <div className="error-text">{errors.password}</div>}
        </div>
        
        <button 
          type="submit" 
          className="auth-button"
          disabled={isSubmitting}
        >
          {isSubmitting ? 'Logging in...' : 'Login'}
        </button>
      </form>
      
      <div className="auth-links">
        <Link to="/register">Don't have an account?</Link>
      </div>

      {/* Quick Login Buttons for Testing */}
      <div className="quick-login-section">
        <div className="quick-login-divider">
          <span>Quick Login (Testing)</span>
        </div>
        <div className="quick-login-buttons">
          {quickLoginCredentials.map((cred) => (
            <button
              key={cred.role}
              type="button"
              className="quick-login-button"
              style={{ backgroundColor: cred.color }}
              onClick={() => handleQuickLogin(cred)}
              disabled={isSubmitting}
            >
              {cred.role}
            </button>
          ))}
        </div>
      </div>
    </div>
  );
};

export default LoginForm;
