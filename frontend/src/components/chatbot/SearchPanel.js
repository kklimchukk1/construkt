import React, { useState } from 'react';
import { Form, Button, InputGroup } from 'react-bootstrap';
import { FaSearch, FaTimes } from 'react-icons/fa';
import './SearchPanel.css';

/**
 * SearchPanel - Search interface with popular search terms
 * Shows quick search buttons and custom search input
 */
const SearchPanel = ({ onSearch, onCancel, popularSearches, isLoading }) => {
  const [customSearch, setCustomSearch] = useState('');

  const defaultPopularSearches = popularSearches || [
    'Nails', 'Cement', 'Bricks', 'Paint', 'Tiles', 'Lumber'
  ];

  const handleSubmit = (e) => {
    e.preventDefault();
    if (customSearch.trim()) {
      onSearch(customSearch.trim());
      setCustomSearch('');
    }
  };

  const handleQuickSearch = (term) => {
    onSearch(term);
  };

  return (
    <div className="search-panel">
      <div className="search-panel-header">
        <span className="search-title">What are you looking for?</span>
        <button className="cancel-button" onClick={onCancel} disabled={isLoading}>
          <FaTimes />
        </button>
      </div>

      {/* Popular search terms as buttons */}
      <div className="popular-searches">
        <span className="popular-label">Popular:</span>
        <div className="search-tags">
          {defaultPopularSearches.map((term, index) => (
            <button
              key={index}
              className="search-tag"
              onClick={() => handleQuickSearch(term)}
              disabled={isLoading}
            >
              {term}
            </button>
          ))}
        </div>
      </div>

      {/* Custom search input */}
      <Form onSubmit={handleSubmit} className="custom-search-form">
        <InputGroup>
          <Form.Control
            type="text"
            placeholder="Or type product name..."
            value={customSearch}
            onChange={(e) => setCustomSearch(e.target.value)}
            disabled={isLoading}
            maxLength={50}
            className="search-input"
          />
          <Button
            type="submit"
            variant="primary"
            disabled={isLoading || !customSearch.trim()}
            className="search-button"
          >
            <FaSearch />
          </Button>
        </InputGroup>
      </Form>
    </div>
  );
};

export default SearchPanel;
