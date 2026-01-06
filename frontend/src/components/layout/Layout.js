import React from 'react';
import Header from './Header';
import './Layout.css';

function Layout({ children }) {
  return (
    <div className="layout">
      <Header />
      <main className="layout-content">
        {children}
      </main>
    </div>
  );
}

export default Layout;
