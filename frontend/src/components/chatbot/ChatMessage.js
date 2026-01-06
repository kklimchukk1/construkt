import React from 'react';
import { Card, Badge, Button } from 'react-bootstrap';
import { FaRobot, FaUser, FaExternalLinkAlt, FaChevronRight } from 'react-icons/fa';
import './ChatMessage.css';
import './CalculatorResult.css';
import './ProductCard.css';
import './CategoryList.css';
import './ActionButtons.css';

/**
 * ChatMessage component for displaying individual chat messages
 * Supports command-based responses with product cards, categories, and action buttons
 *
 * @param {Object} props Component props
 * @param {string} props.message Message text
 * @param {boolean} props.isUser Whether the message is from the user
 * @param {string} props.timestamp Message timestamp
 * @param {Object} props.data Additional message data
 * @param {Function} props.onAction Callback for action button clicks
 */
const ChatMessage = ({ message, isUser, timestamp, data, onAction }) => {
  // Format timestamp if provided
  const formattedTime = timestamp ? new Date(timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '';

  // Handle action button click
  const handleActionClick = (actionType, params) => {
    if (onAction) {
      onAction(actionType, params);
    }
  };

  // Render product card
  const renderProductCard = (product) => (
    <div key={product.id} className="product-card-compact" onClick={() => window.location.href = product.link}>
      <div className="product-info-compact">
        <span className="product-name-compact">{product.name}</span>
        <div className="d-flex align-items-center gap-2">
          <span className="product-price-compact">{product.price}/{product.unit}</span>
          {product.in_stock ? (
            <Badge bg="success" className="stock-badge-small">In Stock</Badge>
          ) : (
            <Badge bg="secondary" className="stock-badge-small">Out</Badge>
          )}
        </div>
      </div>
      <FaExternalLinkAlt className="product-link-icon" size={10} />
    </div>
  );

  // Render category item
  const renderCategoryItem = (category) => (
    <div
      key={category.id}
      className="category-item"
      onClick={() => window.location.href = category.link || `/products?category=${category.id}`}
    >
      <div className="category-info">
        <span className="category-name">{category.name}</span>
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
  );

  // Render action buttons
  const renderActions = (actions) => {
    if (!actions || actions.length === 0) return null;

    return (
      <div className="action-buttons">
        {actions.map((action, index) => (
          <Button
            key={index}
            variant="outline-secondary"
            size="sm"
            onClick={() => handleActionClick(action.type, action.params)}
            className="action-button"
          >
            {action.label}
          </Button>
        ))}
      </div>
    );
  };

  return (
    <div className={`d-flex mb-3 ${isUser ? 'justify-content-end' : 'justify-content-start'}`}>
      <Card
        className={`chat-message ${isUser ? 'user-message' : 'bot-message'}`}
      >
        <Card.Body className="py-2 px-3">
          <div className="card-upper">
            <div className='card-avatar'>
              <div
                className={`message-avatar me-2 ${isUser ? 'user-avatar' : 'bot-avatar'}`}
              >
                {isUser ? <FaUser size={12} color="white" /> : <FaRobot size={12} color="white" />}
              </div>
              <small className="text-muted">{isUser ? 'You' : 'Construkt Assistant'}</small>
            </div>
            {formattedTime && (
              <small className="text-muted ms-auto">{formattedTime}</small>
            )}
          </div>
          <div className="message-text">{message}</div>

          {/* Command-based response types */}

          {/* Products list */}
          {data && data.type === 'products' && data.items && data.items.length > 0 && (
            <div className="mt-2 border-top pt-2">
              <div className="chatbot-product-list">
                {data.items.map(renderProductCard)}
              </div>
              {renderActions(data.actions)}
            </div>
          )}

          {/* Product detail */}
          {data && data.type === 'product_detail' && data.product && (
            <div className="mt-2 border-top pt-2">
              <div className="product-detail-card p-2 border rounded">
                <div className="d-flex justify-content-between align-items-start mb-2">
                  <strong>{data.product.name}</strong>
                  {data.product.in_stock ? (
                    <Badge bg="success">In Stock</Badge>
                  ) : (
                    <Badge bg="secondary">Out of Stock</Badge>
                  )}
                </div>
                {data.product.description && (
                  <p className="small text-muted mb-2">{data.product.description}</p>
                )}
                <div className="d-flex justify-content-between align-items-center">
                  <span className="text-success fw-bold">{data.product.price}/{data.product.unit}</span>
                  <a href={data.product.link} className="btn btn-sm btn-primary">
                    View Product <FaExternalLinkAlt size={10} />
                  </a>
                </div>
              </div>
              {data.related && data.related.length > 0 && (
                <div className="mt-2">
                  <small className="text-muted d-block mb-1">Related products:</small>
                  <div className="chatbot-product-list">
                    {data.related.map(renderProductCard)}
                  </div>
                </div>
              )}
              {renderActions(data.actions)}
            </div>
          )}

          {/* Categories list */}
          {data && data.type === 'categories' && data.items && data.items.length > 0 && (
            <div className="mt-2 border-top pt-2">
              <div className="category-list">
                {data.items.map(renderCategoryItem)}
              </div>
              {renderActions(data.actions)}
            </div>
          )}

          {/* Help / Welcome with commands */}
          {data && (data.type === 'help' || data.type === 'welcome') && data.commands && (
            <div className="mt-2 border-top pt-2">
              <div className="help-commands">
                {data.commands.map((cmd, index) => (
                  <div key={index} className="help-command-item">
                    <span className="help-command-icon">{cmd.icon}</span>
                    <div className="help-command-info">
                      <span className="help-command-label">{cmd.label}</span>
                      {cmd.description && (
                        <span className="help-command-desc">{cmd.description}</span>
                      )}
                    </div>
                  </div>
                ))}
              </div>
              {renderActions(data.actions)}
            </div>
          )}

          {/* No results with suggestions */}
          {data && data.type === 'no_results' && (
            <div className="mt-2 border-top pt-2">
              {data.suggestions && data.suggestions.length > 0 && (
                <div className="suggestions mb-2">
                  <small className="text-muted d-block mb-1">Try searching for:</small>
                  <div className="d-flex flex-wrap gap-1">
                    {data.suggestions.map((term, index) => (
                      <Button
                        key={index}
                        variant="outline-secondary"
                        size="sm"
                        className="suggestion-tag"
                        onClick={() => handleActionClick('SEARCH', { keyword: term })}
                      >
                        {term}
                      </Button>
                    ))}
                  </div>
                </div>
              )}
              {renderActions(data.actions)}
            </div>
          )}

          {/* Error with actions */}
          {data && data.type === 'error' && (
            <div className="mt-2 border-top pt-2">
              {renderActions(data.actions)}
            </div>
          )}

          {/* Calculator result */}
          {data && data.type === 'calculator_result' && data.result && (
            <div className="mt-2 border-top pt-2 calculator-result">
              <div className="calculator-result-card p-2 border rounded">
                <div className="d-flex justify-content-between align-items-center mb-2">
                  <strong>Calculation Result</strong>
                  <Badge bg="primary">{data.result.material_type}</Badge>
                </div>

                <div className="calculator-details">
                  <div className="row mb-1">
                    <div className="col-6">Result:</div>
                    <div className="col-6 text-end fw-bold">{data.result.value} {data.result.unit}</div>
                  </div>

                  {data.result.total_cost !== undefined && (
                    <div className="row mb-1">
                      <div className="col-6">Estimated Cost:</div>
                      <div className="col-6 text-end text-success fw-bold">${data.result.total_cost}</div>
                    </div>
                  )}

                  {data.result.dimensions && (
                    <div className="project-dimensions mt-2 pt-1 border-top">
                      <small className="text-muted">Dimensions:</small>
                      {Object.entries(data.result.dimensions).map(([key, value]) => (
                        <div key={key} className="row mb-1">
                          <div className="col-6">{key}:</div>
                          <div className="col-6 text-end">{value}m</div>
                        </div>
                      ))}
                    </div>
                  )}
                </div>

                {data.result.product && (
                  <div className="mt-2 pt-1 border-top">
                    <a href={data.result.product.link} className="btn btn-sm btn-outline-primary w-100">
                      View {data.result.product.name}
                    </a>
                  </div>
                )}
              </div>
              {renderActions(data.actions)}
            </div>
          )}

          {/* Legacy calculator result format */}
          {data && data.type === 'calculator_result' && data.calculatorData && (
            <div className="mt-2 border-top pt-2 calculator-result">
              <div className="calculator-result-card p-2 border rounded">
                <div className="d-flex justify-content-between align-items-center mb-2">
                  <strong>{data.calculatorData.productName}</strong>
                  <Badge bg="primary">{data.calculatorData.calculationType}</Badge>
                </div>

                <div className="calculator-details">
                  <div className="row mb-1">
                    <div className="col-6">Quantity:</div>
                    <div className="col-6 text-end">{data.calculatorData.quantity} {data.calculatorData.productUnit}(s)</div>
                  </div>

                  <div className="row mb-1">
                    <div className="col-6">Total Cost:</div>
                    <div className="col-6 text-end">${data.calculatorData.totalCost}</div>
                  </div>

                  {data.calculatorData.projectDimensions && (
                    <div className="project-dimensions mt-2 pt-1 border-top">
                      <small className="text-muted">Project Dimensions:</small>
                      {data.calculatorData.projectDimensions.length && (
                        <div className="row mb-1">
                          <div className="col-6">Length:</div>
                          <div className="col-6 text-end">{data.calculatorData.projectDimensions.length}m</div>
                        </div>
                      )}
                      {data.calculatorData.projectDimensions.width && (
                        <div className="row mb-1">
                          <div className="col-6">Width:</div>
                          <div className="col-6 text-end">{data.calculatorData.projectDimensions.width}m</div>
                        </div>
                      )}
                      {data.calculatorData.projectDimensions.depth && (
                        <div className="row mb-1">
                          <div className="col-6">Depth:</div>
                          <div className="col-6 text-end">{data.calculatorData.projectDimensions.depth}m</div>
                        </div>
                      )}
                    </div>
                  )}
                </div>

                <div className="mt-2 pt-1 border-top">
                  <a href={`/products/${data.calculatorData.productId || 1}`} className="btn btn-sm btn-outline-primary w-100">
                    View Product
                  </a>
                </div>
              </div>
            </div>
          )}

          {/* Generic actions (if no specific type matched but actions exist) */}
          {data && data.actions && !['products', 'product_detail', 'categories', 'help', 'welcome', 'no_results', 'error', 'calculator_result'].includes(data.type) && (
            <div className="mt-2 border-top pt-2">
              {renderActions(data.actions)}
            </div>
          )}

          {/* Legacy: Quick links */}
          {data && data.links && data.links.length > 0 && (
            <div className="message-links mt-2 border-top pt-2">
              <small className="text-muted mb-1 d-block">Quick Links:</small>
              <div className="d-flex flex-wrap gap-2 mt-1">
                {data.links.map((link, index) => (
                  <a
                    key={index}
                    href={link.url}
                    className="btn btn-sm btn-outline-primary message-link-button"
                  >
                    {link.text}
                  </a>
                ))}
              </div>
            </div>
          )}
        </Card.Body>
      </Card>
    </div>
  );
};

export default ChatMessage;
