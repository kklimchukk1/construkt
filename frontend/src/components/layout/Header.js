import React from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faShoppingCart } from '@fortawesome/free-solid-svg-icons';
import { useAuth } from '../../context/AuthContext';
import './Header.css';

function Header() {
  const { currentUser, logout, isAuthenticated, hasRole } = useAuth();
  const navigate = useNavigate();
  
  const handleLogout = () => {
    logout();
    navigate('/');
  };
  return (
    <header className="header">
      <div className="header-container">
        <div className="logo">
          <Link to="/">
            <h1>Construkt</h1>
          </Link>
        </div>
        <nav className="main-nav">
          <ul>
            <li>
              <Link to="/products">Products</Link>
            </li>
            <li>
              <Link to="/calculator">Calculator</Link>
            </li>
            <li>
              <Link to="/about">About</Link>
            </li>
            {isAuthenticated() && hasRole('supplier') && (
              <li>
                <Link to="/supplier">Supplier Dashboard</Link>
              </li>
            )}
            {isAuthenticated() && hasRole('manager') && (
              <li>
                <Link to="/manager">Manager Panel</Link>
              </li>
            )}
            {isAuthenticated() && hasRole('admin') && (
              <li>
                <Link to="/admin">Admin Panel</Link>
              </li>
            )}
          </ul>
        </nav>

        {isAuthenticated() && (
          <Link to="/cart" className="cart-link" title="Shopping Cart">
            <FontAwesomeIcon icon={faShoppingCart} />
            <span>Cart</span>
          </Link>
        )}

        <div className="auth-nav">
          {isAuthenticated() ? (
            <div className="user-menu">
              <Link to="/profile" className="profile-link">{currentUser.firstName && currentUser.lastName ? `${currentUser.firstName} ${currentUser.lastName}` : currentUser.email}</Link>
              <button onClick={handleLogout} className="logout-button">Logout</button>
            </div>
          ) : (
            <div className="auth-links">
              <Link to="/login" className="auth-link-login">Sign in</Link>
            </div>
          )}
        </div>
      </div>
    </header>
  );
}

export default Header;
