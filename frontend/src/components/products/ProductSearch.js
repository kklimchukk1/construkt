import React, { useState } from 'react';
import './ProductSearch.css';

const ProductSearch = ({ onSearch }) => {
  const [searchTerm, setSearchTerm] = useState('');

  const handleSubmit = (e) => {
    e.preventDefault();
    onSearch(searchTerm);
  };

  return (
    <div className="product-search">
      <form onSubmit={handleSubmit}>
        <input
          type="text"
          placeholder="Search for products..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
        />
        <button type="submit">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18">
            <path fill="currentColor" d="M23.707 22.293l-5.969-5.969a10.016 10.016 0 002.486-6.599c0-5.52-4.481-10-10-10s-10 4.481-10 10 4.481 10 10 10a9.983 9.983 0 006.599-2.486l5.969 5.969a1 1 0 001.414-1.414zM10.224 18c-4.418 0-8-3.582-8-8s3.582-8 8-8 8 3.582 8 8-3.582 8-8 8z" />
          </svg>
        </button>
      </form>
    </div>
  );
};

export default ProductSearch;
