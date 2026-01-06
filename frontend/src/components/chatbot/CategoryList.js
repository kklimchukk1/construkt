import React from 'react';
import { Badge } from 'react-bootstrap';
import { FaChevronRight } from 'react-icons/fa';
import './CategoryList.css';

/**
 * CategoryList - Displays list of product categories
 * Each category is clickable and shows product count
 */
const CategoryList = ({ categories, onCategoryClick, isLoading }) => {
  if (!categories || categories.length === 0) {
    return (
      <div className="category-list-empty">
        No categories available
      </div>
    );
  }

  return (
    <div className="category-list">
      {categories.map((category) => (
        <div
          key={category.id}
          className="category-item"
          onClick={() => onCategoryClick(category)}
          role="button"
          tabIndex={0}
        >
          <div className="category-info">
            <span className="category-name">{category.name}</span>
            {category.description && (
              <span className="category-description">{category.description}</span>
            )}
          </div>
          <div className="category-meta">
            {category.product_count !== undefined && (
              <Badge bg="secondary" className="category-count">
                {category.product_count}
              </Badge>
            )}
            <FaChevronRight className="category-arrow" size={12} />
          </div>
        </div>
      ))}
    </div>
  );
};

export default CategoryList;
