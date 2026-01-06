import React, { useState, useEffect } from 'react';
import './ProductFilter.css';

const ProductFilter = ({ categories, onFilterChange, initialFilters = {} }) => {
  const [filters, setFilters] = useState({
    category: initialFilters.category || '',
    minPrice: initialFilters.minPrice || '',
    maxPrice: initialFilters.maxPrice || '',
    inStock: initialFilters.inStock || false,
    featured: initialFilters.featured || false,
    sortBy: initialFilters.sortBy || 'name'
  });

  const [isExpanded, setIsExpanded] = useState(false);

  // Update parent component when filters change
  useEffect(() => {
    onFilterChange(filters);
  }, [filters, onFilterChange]);

  const handleInputChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFilters({
      ...filters,
      [name]: type === 'checkbox' ? checked : value
    });
  };

  const handleReset = () => {
    setFilters({
      category: '',
      minPrice: '',
      maxPrice: '',
      inStock: false,
      featured: false,
      sortBy: 'name'
    });
  };

  const toggleExpand = () => {
    setIsExpanded(!isExpanded);
  };

  return (
    <div className="product-filter">
      <div className="filter-header">
        <h3>Filter Products</h3>
        <button 
          className="toggle-filter-btn"
          onClick={toggleExpand}
        >
          {isExpanded ? 'Hide Filters' : 'Show Filters'}
        </button>
      </div>

      <div className={`filter-body ${isExpanded ? 'expanded' : ''}`}>
        <div className="filter-section">
          <h4>Categories</h4>
          <select 
            name="category" 
            value={filters.category} 
            onChange={handleInputChange}
          >
            <option value="">All Categories</option>
            {categories.map(category => (
              <option key={category.id} value={category.id}>
                {category.name}
              </option>
            ))}
          </select>
        </div>

        <div className="filter-section">
          <h4>Price Range</h4>
          <div className="price-range">
            <input 
              type="number" 
              name="minPrice" 
              placeholder="Min" 
              value={filters.minPrice} 
              onChange={handleInputChange}
              min="0"
            />
            <span>to</span>
            <input 
              type="number" 
              name="maxPrice" 
              placeholder="Max" 
              value={filters.maxPrice} 
              onChange={handleInputChange}
              min="0"
            />
          </div>
        </div>

        <div className="filter-section">
          <h4>Sort By</h4>
          <select 
            name="sortBy" 
            value={filters.sortBy} 
            onChange={handleInputChange}
          >
            <option value="name">Name (A-Z)</option>
            <option value="name-desc">Name (Z-A)</option>
            <option value="price-asc">Price (Low to High)</option>
            <option value="price-desc">Price (High to Low)</option>
          </select>
        </div>

        <button 
          className="reset-filter-btn"
          onClick={handleReset}
        >
          Reset Filters
        </button>
      </div>
    </div>
  );
};

export default ProductFilter;
