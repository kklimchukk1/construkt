import React, { useState, useEffect, useMemo } from 'react';
import { useLocation } from 'react-router-dom';
import ProductCard from '../components/products/ProductCard';
import productService from '../services/productService';
import './Products.css';

const findProductsArray = (obj) => {
  const productKeys = ['products', 'data', 'items', 'results'];
  for (const key of productKeys) {
    if (obj[key] && Array.isArray(obj[key])) {
      return obj[key];
    }
  }
  for (const key in obj) {
    if (obj[key] && typeof obj[key] === 'object') {
      for (const nestedKey of productKeys) {
        if (obj[key][nestedKey] && Array.isArray(obj[key][nestedKey])) {
          return obj[key][nestedKey];
        }
      }
    }
  }
  for (const key in obj) {
    if (obj[key] && Array.isArray(obj[key]) && obj[key].length > 0) {
      const firstItem = obj[key][0];
      if (firstItem && typeof firstItem === 'object' &&
        (firstItem.id !== undefined || firstItem.name !== undefined || firstItem.price !== undefined)) {
        return obj[key];
      }
    }
  }
  return null;
};

const categories = [
  { id: 0, name: 'All Products', icon: '📦' },
  { id: 1, name: 'Building Materials', icon: '🧱' },
  { id: 2, name: 'Hardware', icon: '🔩' },
  { id: 3, name: 'Flooring', icon: '🪵' },
  { id: 4, name: 'Plumbing', icon: '🚿' },
  { id: 5, name: 'Electrical', icon: '⚡' },
  { id: 6, name: 'HVAC', icon: '❄️' },
  { id: 7, name: 'Painting & Supplies', icon: '🎨' },
  { id: 8, name: 'Landscaping', icon: '🌿' },
  { id: 9, name: 'Concrete & Cement', icon: '🏗️' },
  { id: 10, name: 'Bricks & Blocks', icon: '🧱' },
  { id: 11, name: 'Lumber & Composites', icon: '🪵' },
  { id: 12, name: 'Drywall', icon: '📋' },
  { id: 13, name: 'Insulation', icon: '🧤' },
  { id: 14, name: 'Roofing', icon: '🏠' },
  { id: 15, name: 'Siding', icon: '🏢' },
  { id: 16, name: 'Windows & Doors', icon: '🚪' },
  { id: 17, name: 'Hand Tools', icon: '🔧' },
  { id: 18, name: 'Power Tools', icon: '🔌' },
  { id: 19, name: 'Fasteners', icon: '🔩' },
  { id: 20, name: 'Safety Equipment', icon: '🦺' },
  { id: 21, name: 'Tile', icon: '🔲' },
  { id: 22, name: 'Hardwood', icon: '🪵' },
  { id: 23, name: 'Laminate', icon: '📐' },
  { id: 24, name: 'Vinyl', icon: '🎞️' },
  { id: 25, name: 'Carpet', icon: '🧶' }
];

function Products() {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCategory, setSelectedCategory] = useState(0);
  const [sortBy, setSortBy] = useState('name');
  const [priceRange, setPriceRange] = useState({ min: '', max: '' });

  const location = useLocation();

  useEffect(() => {
    const searchParams = new URLSearchParams(location.search);
    const categoryParam = searchParams.get('category');
    const searchParam = searchParams.get('search');

    if (categoryParam) {
      setSelectedCategory(parseInt(categoryParam) || 0);
    }
    if (searchParam) {
      setSearchTerm(searchParam);
    }
  }, [location.search]);

  useEffect(() => {
    const fetchData = async () => {
      setLoading(true);
      setError(null);
      try {
        const productsResponse = await productService.getProducts({});

        if (productsResponse && Array.isArray(productsResponse.products)) {
          setProducts(productsResponse.products);
        } else if (productsResponse && Array.isArray(productsResponse)) {
          setProducts(productsResponse);
        } else if (productsResponse && typeof productsResponse === 'object') {
          const productsArray = findProductsArray(productsResponse);
          if (productsArray && productsArray.length > 0) {
            setProducts(productsArray);
          } else {
            setError('Failed to load products. Invalid data format.');
          }
        } else {
          setError('Failed to load products. Invalid data format.');
        }
        setLoading(false);
      } catch (err) {
        console.error('Error fetching data:', err);
        setError('Failed to load products. ' + (err.message || 'Please try again.'));
        setLoading(false);
        setProducts([]);
      }
    };

    fetchData();
  }, []);

  const filteredProducts = useMemo(() => {
    let result = [...products];

    // Filter by search term
    if (searchTerm) {
      const term = searchTerm.toLowerCase();
      result = result.filter(product =>
        product.name?.toLowerCase().includes(term) ||
        product.description?.toLowerCase().includes(term)
      );
    }

    // Filter by category
    if (selectedCategory !== 0) {
      result = result.filter(product =>
        product.category_id?.toString() === selectedCategory.toString()
      );
    }

    // Filter by price range
    if (priceRange.min) {
      result = result.filter(product => product.price >= parseFloat(priceRange.min));
    }
    if (priceRange.max) {
      result = result.filter(product => product.price <= parseFloat(priceRange.max));
    }

    // Sort
    switch (sortBy) {
      case 'name':
        result.sort((a, b) => (a.name || '').localeCompare(b.name || ''));
        break;
      case 'name-desc':
        result.sort((a, b) => (b.name || '').localeCompare(a.name || ''));
        break;
      case 'price-asc':
        result.sort((a, b) => (a.price || 0) - (b.price || 0));
        break;
      case 'price-desc':
        result.sort((a, b) => (b.price || 0) - (a.price || 0));
        break;
      default:
        break;
    }

    return result;
  }, [products, searchTerm, selectedCategory, sortBy, priceRange]);

  // Group products by category
  const productsByCategory = useMemo(() => {
    if (selectedCategory !== 0) {
      return null; // Don't group when a specific category is selected
    }

    const grouped = {};
    filteredProducts.forEach(product => {
      const catId = product.category_id || 0;
      if (!grouped[catId]) {
        grouped[catId] = [];
      }
      grouped[catId].push(product);
    });
    return grouped;
  }, [filteredProducts, selectedCategory]);

  const handleReset = () => {
    setSearchTerm('');
    setSelectedCategory(0);
    setSortBy('name');
    setPriceRange({ min: '', max: '' });
  };

  const getCategoryInfo = (categoryId) => {
    return categories.find(c => c.id === parseInt(categoryId)) || { name: 'Other', icon: '📦' };
  };

  return (
    <div className="products-page">
      {/* Parallax Hero */}
      <div className="products-hero">
        <div className="parallax-particles">
          {[...Array(8)].map((_, i) => (
            <div key={i} className="particle" />
          ))}
        </div>
        <div className="products-header">
          <h1>Product Catalog</h1>
          <p>Premium construction materials for your projects</p>
        </div>
      </div>

      {/* Main Content */}
      <div className="products-content-wrapper">
        {/* Filter Bar */}
        <div className="products-filter-bar">
          {/* Category Tabs */}
          <div className="category-tabs">
            {categories.map(category => (
              <button
                key={category.id}
                className={`category-tab ${selectedCategory === category.id ? 'active' : ''}`}
                onClick={() => setSelectedCategory(category.id)}
              >
                <span>{category.icon}</span> {category.name}
              </button>
            ))}
          </div>

          {/* Filter Controls */}
          <div className="filter-controls">
            <div className="filter-group">
              <label>Search</label>
              <input
                type="text"
                placeholder="Search products..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
              />
            </div>

            <div className="filter-group">
              <label>Price Range</label>
              <div className="price-inputs">
                <input
                  type="number"
                  placeholder="Min"
                  value={priceRange.min}
                  onChange={(e) => setPriceRange({ ...priceRange, min: e.target.value })}
                />
                <span>-</span>
                <input
                  type="number"
                  placeholder="Max"
                  value={priceRange.max}
                  onChange={(e) => setPriceRange({ ...priceRange, max: e.target.value })}
                />
              </div>
            </div>

            <div className="filter-group">
              <label>Sort By</label>
              <select value={sortBy} onChange={(e) => setSortBy(e.target.value)}>
                <option value="name">Name (A-Z)</option>
                <option value="name-desc">Name (Z-A)</option>
                <option value="price-asc">Price (Low to High)</option>
                <option value="price-desc">Price (High to Low)</option>
              </select>
            </div>

            <button className="filter-reset-btn" onClick={handleReset}>
              Reset Filters
            </button>
          </div>
        </div>

        {/* Results Info */}
        <div className="products-results-info">
          <p>
            Showing <strong>{filteredProducts.length}</strong> products
            {searchTerm && ` for "${searchTerm}"`}
            {selectedCategory !== 0 && ` in ${getCategoryInfo(selectedCategory).name}`}
          </p>
        </div>

        {/* Content */}
        {loading ? (
          <div className="products-loading">
            <div className="loading-spinner"></div>
            <p>Loading products...</p>
          </div>
        ) : error ? (
          <div className="products-error">
            <p>{error}</p>
          </div>
        ) : filteredProducts.length === 0 ? (
          <div className="products-empty">
            <div className="products-empty-icon">🔍</div>
            <h3>No products found</h3>
            <p>Try adjusting your filters or search terms</p>
          </div>
        ) : selectedCategory !== 0 ? (
          /* Single Category View */
          <div className="products-all-section">
            <div className="products-grid">
              {filteredProducts.map(product => (
                <ProductCard key={product.id} product={product} />
              ))}
            </div>
          </div>
        ) : (
          /* Grouped by Categories */
          Object.entries(productsByCategory || {}).map(([categoryId, categoryProducts]) => {
            const catInfo = getCategoryInfo(categoryId);
            return (
              <div key={categoryId} className="category-section">
                <div className="category-section-header">
                  <div className="category-icon">{catInfo.icon}</div>
                  <h2>{catInfo.name}</h2>
                  <span className="category-count">{categoryProducts.length} items</span>
                </div>
                <div className="products-grid">
                  {categoryProducts.map(product => (
                    <ProductCard key={product.id} product={product} />
                  ))}
                </div>
              </div>
            );
          })
        )}
      </div>
    </div>
  );
}

export default Products;
