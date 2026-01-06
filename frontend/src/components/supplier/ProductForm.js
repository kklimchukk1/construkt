import React, { useState, useEffect } from 'react';
import supplierService from '../../services/supplierService';
import './ProductForm.css';

// Default categories to use when API endpoint is not available
const getDefaultCategories = () => [
  { id: 1, name: 'Building Materials' },
  { id: 2, name: 'Tools & Equipment' },
  { id: 3, name: 'Electrical' },
  { id: 4, name: 'Plumbing' },
  { id: 5, name: 'HVAC' },
  { id: 6, name: 'Flooring' },
  { id: 7, name: 'Roofing' },
  { id: 8, name: 'Paint & Supplies' },
  { id: 9, name: 'Doors & Windows' },
  { id: 10, name: 'Hardware' }
];

const ProductForm = ({ product = null, onSave, onCancel }) => {
  const [formData, setFormData] = useState({
    name: '',
    description: '',
    price: '',
    stock_quantity: '',
    category_id: '',
    is_active: true,
    unit: '',
    thumbnail: '',
    dimensions: {
      material_type: 'area', // Default to area (options: area, volume, linear)
      coverage: '', // How much area/volume one unit covers
      length: '',
      width: '',
      height: ''
    }
  });
  
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  
  // Load categories and product data if editing
  useEffect(() => {
    console.log('ProductForm useEffect triggered');
    const loadData = async () => {
      setLoading(true);
      try {
        // Load categories
        console.log('Attempting to load categories...');
        try {
          const categoriesResponse = await supplierService.getCategories();
          console.log('Categories response:', categoriesResponse);
          if (categoriesResponse.success && categoriesResponse.data) {
            setCategories(categoriesResponse.data);
            console.log('Categories set:', categoriesResponse.data);
          } else {
            console.warn('Failed to load categories, using default categories');
            setCategories(getDefaultCategories());
          }
        } catch (categoryError) {
          console.warn('Categories endpoint not implemented yet, using default categories');
          setCategories(getDefaultCategories());
        }
        
        // If editing, populate form with product data
        if (product) {
          console.log('Editing product, populating form with:', product);
          // Parse dimensions from JSON if it exists
          let dimensionsData = {
            material_type: 'area',
            coverage: '',
            length: '',
            width: '',
            height: ''
          };
          
          if (product.dimensions) {
            try {
              // If dimensions is a string (JSON), parse it
              const parsedDimensions = typeof product.dimensions === 'string' 
                ? JSON.parse(product.dimensions) 
                : product.dimensions;
              
              dimensionsData = {
                ...dimensionsData,
                ...parsedDimensions
              };
            } catch (e) {
              console.error('Error parsing dimensions:', e);
            }
          }
          
          setFormData({
            name: product.name || '',
            description: product.description || '',
            price: product.price || '',
            stock_quantity: product.stock_quantity || '',
            category_id: product.category_id || '',
            is_active: product.is_active !== undefined ? product.is_active : true,
            unit: product.unit || '',
            thumbnail: product.thumbnail || '',
            dimensions: dimensionsData
          });
        } else {
          console.log('Adding new product, using default form values');
          // Reset form when adding a new product
          setFormData({
            name: '',
            description: '',
            price: '',
            stock_quantity: '',
            category_id: '',
            is_active: true,
            unit: '',
            thumbnail: '',
            dimensions: {
              material_type: 'area',
              coverage: '',
              length: '',
              width: '',
              height: ''
            }
          });
        }
      } catch (error) {
        console.error('Error loading data:', error);
      } finally {
        setLoading(false);
      }
    };
    
    loadData();
  }, [product]);
  
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
    
    if (!formData.name.trim()) {
      newErrors.name = 'Product name is required';
    }

    if (!formData.price) {
      newErrors.price = 'Price is required';
    } else if (isNaN(formData.price) || parseFloat(formData.price) <= 0) {
      newErrors.price = 'Price must be a positive number';
    }
    
    if (!formData.stock_quantity) {
      newErrors.stock_quantity = 'Stock quantity is required';
    } else if (isNaN(formData.stock_quantity) || parseInt(formData.stock_quantity) < 0) {
      newErrors.stock_quantity = 'Stock quantity must be a non-negative number';
    }
    
    if (!formData.category_id) {
      newErrors.category_id = 'Category is required';
    }
    
    // Validate dimensions based on material type
    const dims = formData.dimensions;
    if (dims.material_type === 'area' || dims.material_type === 'volume') {
      // For area and volume calculations, we need at least coverage value
      if (!dims.coverage) {
        newErrors['dimensions.coverage'] = 'Coverage value is required';
      } else if (isNaN(dims.coverage) || parseFloat(dims.coverage) <= 0) {
        newErrors['dimensions.coverage'] = 'Coverage must be a positive number';
      }
    }
    
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };
  
  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (validateForm()) {
      setIsSubmitting(true);
      
      try {
        // Format data for API
        // Validate dimensions data
        const dimensionsData = { ...formData.dimensions };
        
        // Convert numeric dimension values to numbers
        if (dimensionsData.coverage) dimensionsData.coverage = parseFloat(dimensionsData.coverage);
        if (dimensionsData.length) dimensionsData.length = parseFloat(dimensionsData.length);
        if (dimensionsData.width) dimensionsData.width = parseFloat(dimensionsData.width);
        if (dimensionsData.height) dimensionsData.height = parseFloat(dimensionsData.height);
        
        // Prepare product data for submission
        const productData = {
          ...formData,
          price: parseFloat(formData.price),
          stock_quantity: parseInt(formData.stock_quantity),
          category_id: parseInt(formData.category_id),
          dimensions: JSON.stringify(dimensionsData) // Convert dimensions to JSON string for database storage
        };
        
        console.log('Submitting product data:', productData);
        
        // Call the onSave callback with the product data
        await onSave(productData);
      } catch (error) {
        console.error('Error saving product:', error);
        alert(`Error saving product: ${error.message}`);
      } finally {
        setIsSubmitting(false);
      }
    }
  };
  
  if (loading) {
    return <div className="loading">Loading...</div>;
  }
  
  return (
    <div className="product-form-container">
      <h2>{product ? 'Edit Product' : 'Add New Product'}</h2>
      
      <form onSubmit={handleSubmit} className="product-form">
        <div className="form-group">
          <label htmlFor="name">Product Name *</label>
          <input
            type="text"
            id="name"
            name="name"
            value={formData.name}
            onChange={handleChange}
            disabled={isSubmitting}
            className={errors.name ? 'error' : ''}
          />
          {errors.name && <div className="error-text">{errors.name}</div>}
        </div>
        
        <div className="form-group">
          <label htmlFor="description">Description</label>
          <textarea
            id="description"
            name="description"
            value={formData.description}
            onChange={handleChange}
            disabled={isSubmitting}
            rows="4"
          />
        </div>
        
          <div className="form-group">
            <label htmlFor="price">Price ($) *</label>
            <input
              type="number"
              id="price"
              name="price"
              value={formData.price}
              onChange={handleChange}
              disabled={isSubmitting}
              className={errors.price ? 'error' : ''}
              step="0.01"
              min="0"
            />
            {errors.price && <div className="error-text">{errors.price}</div>}
          </div>
          
          <div className="form-group">
            <label htmlFor="stock_quantity">Stock Quantity *</label>
            <input
              type="number"
              id="stock_quantity"
              name="stock_quantity"
              value={formData.stock_quantity}
              onChange={handleChange}
              disabled={isSubmitting}
              className={errors.stock_quantity ? 'error' : ''}
              min="0"
            />
            {errors.stock_quantity && <div className="error-text">{errors.stock_quantity}</div>}
          </div>
        
        <div className="form-group">
          <label htmlFor="unit">Unit of Measure</label>
          <input
            type="text"
            id="unit"
            name="unit"
            value={formData.unit}
            onChange={handleChange}
            disabled={isSubmitting}
            placeholder="e.g., kg, pcs, m, ft"
          />
        </div>
        
        <div className="form-group">
          <label htmlFor="thumbnail">Thumbnail URL</label>
          <input
            type="url"
            id="thumbnail"
            name="thumbnail"
            value={formData.thumbnail}
            onChange={handleChange}
            disabled={isSubmitting}
            placeholder="https://example.com/image.jpg"
          />
        </div>
        
        <div className="form-group">
          <label htmlFor="category_id">Category *</label>
          <select
            id="category_id"
            name="category_id"
            value={formData.category_id}
            onChange={handleChange}
            disabled={isSubmitting}
            className={errors.category_id ? 'error' : ''}
          >
            <option value="">Select a category</option>
            {categories.map(category => (
              <option key={category.id} value={category.id}>
                {category.name}
              </option>
            ))}
          </select>
          {errors.category_id && <div className="error-text">{errors.category_id}</div>}
        </div>
        
        <div className="form-group checkbox-group">
          <input
            type="checkbox"
            id="is_active"
            name="is_active"
            checked={formData.is_active}
            onChange={handleChange}
            disabled={isSubmitting}
          />
          <label htmlFor="is_active">Product is active (visible to customers)</label>
        </div>
        
        <div className="form-section">
          <h3>Product Dimensions</h3>
          <p className="help-text">These dimensions help the chatbot calculate material quantities for customers.</p>
          
          <div className="form-group">
            <label htmlFor="material_type">Material Type *</label>
            <select
              id="material_type"
              value={formData.dimensions.material_type}
              onChange={(e) => {
                setFormData({
                  ...formData,
                  dimensions: {
                    ...formData.dimensions,
                    material_type: e.target.value
                  }
                });
              }}
              disabled={isSubmitting}
            >
              <option value="area">Area (e.g., paint, tiles)</option>
              <option value="volume">Volume (e.g., concrete, sand)</option>
              <option value="linear">Linear (e.g., pipes, cables)</option>
            </select>
          </div>
          
          <div className="form-group">
            <label htmlFor="coverage">Coverage (per unit) *</label>
            <div className="input-with-help">
              <input
                type="number"
                id="coverage"
                value={formData.dimensions.coverage}
                onChange={(e) => {
                  setFormData({
                    ...formData,
                    dimensions: {
                      ...formData.dimensions,
                      coverage: e.target.value
                    }
                  });
                }}
                disabled={isSubmitting}
                className={errors['dimensions.coverage'] ? 'error' : ''}
                step="0.01"
                min="0"
              />
              <span className="help-text">
                {formData.dimensions.material_type === 'area' && 'How many square meters one unit covers'}
                {formData.dimensions.material_type === 'volume' && 'How many cubic meters one unit provides'}
                {formData.dimensions.material_type === 'linear' && 'How many meters one unit covers'}
              </span>
            </div>
            {errors['dimensions.coverage'] && <div className="error-text">{errors['dimensions.coverage']}</div>}
          </div>
          
          <div className="dimensions-grid">
            <div className="form-group">
              <label htmlFor="length">Default Length (m)</label>
              <input
                type="number"
                id="length"
                value={formData.dimensions.length}
                onChange={(e) => {
                  setFormData({
                    ...formData,
                    dimensions: {
                      ...formData.dimensions,
                      length: e.target.value
                    }
                  });
                }}
                disabled={isSubmitting}
                step="0.01"
                min="0"
              />
            </div>
            
            <div className="form-group">
              <label htmlFor="width">Default Width (m)</label>
              <input
                type="number"
                id="width"
                value={formData.dimensions.width}
                onChange={(e) => {
                  setFormData({
                    ...formData,
                    dimensions: {
                      ...formData.dimensions,
                      width: e.target.value
                    }
                  });
                }}
                disabled={isSubmitting}
                step="0.01"
                min="0"
              />
            </div>
            
            <div className="form-group">
              <label htmlFor="height">Default Height (m)</label>
              <input
                type="number"
                id="height"
                value={formData.dimensions.height}
                onChange={(e) => {
                  setFormData({
                    ...formData,
                    dimensions: {
                      ...formData.dimensions,
                      height: e.target.value
                    }
                  });
                }}
                disabled={isSubmitting}
                step="0.01"
                min="0"
              />
            </div>
          </div>
        </div>
        
        <div className="form-actions">
          <button type="submit" disabled={isSubmitting} className="btn-primary">
            {isSubmitting ? 'Saving...' : 'Save Product'}
          </button>
          <button type="button" onClick={onCancel} disabled={isSubmitting} className="btn-secondary">
            Cancel
          </button>
        </div>
      </form>
    </div>
  );
};

export default ProductForm;
