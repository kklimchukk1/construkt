import React, { useState, useEffect } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import { Tab, Tabs, Button, Badge } from 'react-bootstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faArrowLeft, faCalculator, faShoppingCart } from '@fortawesome/free-solid-svg-icons';
import { useAuth } from '../context/AuthContext';
import productService from '../services/productService';
import orderService from '../services/orderService';
import authService from '../services/authService';
import { shareCalculationWithChatbot } from '../services/calculatorChatService';
import AreaCalculator from '../components/calculator/AreaCalculator';
import VolumeCalculator from '../components/calculator/VolumeCalculator';
import LinearCalculator from '../components/calculator/LinearCalculator';
import CalculatorResults from '../components/calculator/CalculatorResults';
import LoadingIndicator from '../components/layout/LoadingIndicator';
import ErrorMessage from '../components/layout/ErrorMessage';
import './ProductDetail.css';
import unknown_material from '../assets/unknown_material.png';

function ProductDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { isAuthenticated } = useAuth();
  const [product, setProduct] = useState(null);
  const [relatedProducts, setRelatedProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [activeTab, setActiveTab] = useState('description');
  const [showCalculator, setShowCalculator] = useState(false);
  const [calculationType, setCalculationType] = useState('area');
  const [calculationResult, setCalculationResult] = useState(null);
  const [isCalculating, setIsCalculating] = useState(false);
  const [calculationError, setCalculationError] = useState(null);
  const [adding, setAdding] = useState(false);
  const [added, setAdded] = useState(false);
  const [quantity, setQuantity] = useState(1);

  const handleAddToCart = async () => {
    if (!authService.isAuthenticated()) {
      navigate('/login');
      return;
    }

    const stockQty = parseInt(product.stock_quantity || product.stock || 0);
    if (stockQty <= 0) return;

    setAdding(true);
    try {
      await orderService.addToCart(product.id, quantity);
      setAdded(true);
      setTimeout(() => setAdded(false), 2000);
    } catch (error) {
      console.error('Error adding to cart:', error);
    }
    setAdding(false);
  };

  // Utility function to handle calculation results
  const handleCalculationResult = (result, data, type) => {
    console.log('Calculation result:', result, data);
    setCalculationResult(result);
    setIsCalculating(false);
    
    // Create project dimensions object based on calculation type
    const projectDimensions = {};
    if (data.length) projectDimensions.length = data.length;
    if (data.width) projectDimensions.width = data.width;
    if (data.depth) projectDimensions.depth = data.depth;
    
    // Calculate quantity and total cost
    let quantity = 0;
    if (type === 'area' && result.result) {
      quantity = result.result.unitsNeeded || Math.ceil(result.result.requiredArea / (data.coverage || 1));
    } else if (type === 'volume' && result.result) {
      quantity = Math.ceil(result.result.requiredVolume || result.result.volume);
    } else if (type === 'linear' && result.result) {
      quantity = Math.ceil(result.result.requiredLength || result.result.length);
    }
    
    const totalCost = quantity * (product.price || 0);
    
    // Format the result for the chatbot
    const chatbotResult = {
      quantity,
      totalCost
    };
    
    // Share calculation with chatbot
    shareCalculationWithChatbot(chatbotResult, product, projectDimensions, type);
  };

  // Set calculation type based on product's calculation_type field (set by manager)
  useEffect(() => {
    if (product) {
      // First priority: use calculation_type from product (set by manager)
      if (product.calculation_type && ['area', 'volume', 'linear', 'length', 'weight'].includes(product.calculation_type)) {
        // Map calculation types to calculator types
        let calcType = product.calculation_type;
        if (calcType === 'length') calcType = 'linear'; // Map 'length' to 'linear' calculator
        if (calcType === 'weight') calcType = 'unit'; // Weight doesn't need special calculator

        if (['area', 'volume', 'linear'].includes(calcType)) {
          setCalculationType(calcType);
          console.log(`Setting calculation type to ${calcType} based on product.calculation_type`);
        }
      }
      // Fallback: check dimensions.material_type
      else if (product.dimensions) {
        let dims = product.dimensions;
        if (typeof dims === 'string') {
          try {
            dims = JSON.parse(dims);
          } catch (e) {
            dims = null;
          }
        }

        if (dims && dims.material_type) {
          if (['area', 'volume', 'linear'].includes(dims.material_type)) {
            setCalculationType(dims.material_type);
            console.log(`Setting calculation type to ${dims.material_type} based on product dimensions`);
          }
        }
      }
    }
  }, [product]);

  useEffect(() => {
    const fetchProductData = async () => {
      setLoading(true);
      setError(null); // Reset error state on new fetch

      try {
        console.log(`Fetching product with ID: ${id}`);
        const response = await productService.getProductById(id);
        console.log('Product response:', response);

        // Handle different response formats
        if (response && response.data && response.data.product) {
          // Standard format with product and related_products in data property
          console.log('Product data (standard format):', response.data.product);
          // Ensure stock_quantity is properly handled
          const productWithStock = {
            ...response.data.product,
            stock_quantity: response.data.product.stock_quantity || 0
          };
          setProduct(productWithStock);
          if (response.data.related_products && Array.isArray(response.data.related_products)) {
            setRelatedProducts(response.data.related_products);
          }
        } else if (response && response.product) {
          // Format with product directly in response
          console.log('Product data (direct product format):', response.product);
          const productWithStock = {
            ...response.product,
            stock_quantity: response.product.stock_quantity || 0
          };
          setProduct(productWithStock);
          if (response.related_products && Array.isArray(response.related_products)) {
            setRelatedProducts(response.related_products);
          }
        } else if (response && typeof response === 'object') {
          // Direct product object
          console.log('Product data (direct object):', response);
          // Ensure stock_quantity is properly handled
          const productWithStock = {
            ...response,
            stock_quantity: response.stock_quantity || 0
          };
          setProduct(productWithStock);

          // If we have a category_id, try to fetch related products
          if (response.category_id) {
            try {
              const productsResponse = await productService.getProducts({
                category: response.category_id,
                exclude: id
              });

              if (productsResponse && Array.isArray(productsResponse.products)) {
                setRelatedProducts(productsResponse.products.slice(0, 4));
              } else if (productsResponse && Array.isArray(productsResponse)) {
                setRelatedProducts(productsResponse.slice(0, 4));
              }
            } catch (relatedErr) {
              console.error('Error fetching related products:', relatedErr);
              // Non-critical error, don't set main error state
            }
          }
        } else {
          throw new Error('Invalid product data format');
        }

        setLoading(false);
      } catch (err) {
        console.error('Error fetching product:', err);
        setError('Product not found');
        setLoading(false);
      }
    };

    fetchProductData();
  }, [id]);

  if (loading) {
    return (
      <div className="product-detail-loading">
        <LoadingIndicator
          type="spinner"
          size="large"
          text="Loading product details..."
        />
      </div>
    );
  }

  if (error || !product) {
    return (
      <div className="product-detail-error">
        <ErrorMessage
          message={error || 'Product not found'}
          type="error"
          retry={true}
          onRetry={() => window.location.reload()}
        />
        <Link to="/products" className="back-to-products">
          Back to Products
        </Link>
      </div>
    );
  }

  return (
    <div className="product-detail-page">
      <div className="product-detail-breadcrumb">
        <Link to="/">Home</Link> &gt;
        <Link to="/products">Products</Link> &gt;
        <Link to={`/products?category=${product.category_id}`}>{product.category_name}</Link> &gt;
        <span>{product.name}</span>
      </div>

      <div className="product-detail-container">
        <div className="product-detail-left">
          <div className="product-detail-image">
            {product.thumbnail ? <>
              <img src={product.thumbnail} alt={product.name} />
            </> : <>
              <img src={unknown_material} alt={product.name} />
            </>}
            {product.featured && <span className="featured-badge">Featured</span>}
          </div>
        </div>

        <div className="product-detail-right">
          <h1 className="product-detail-name">{product.name || `Product ${id}`}</h1>

          <div className="product-detail-meta">
            <div className="product-detail-supplier">by {product.supplier_name}</div>

          </div>

          <div className="product-detail-price">
            ${parseFloat(product.price || 0).toFixed(2)} <span className="product-unit">/ {product.unit || 'unit'}</span>
          </div>

          <div className="product-detail-stock">
            {/* Debug stock information */}
            {console.log('Stock info:', {
              stock_quantity: product.stock_quantity,
              stock: product.stock,
              hasStock: (parseInt(product.stock_quantity) > 0 || parseInt(product.stock) > 0)
            })}

            {(parseInt(product.stock_quantity) > 0 || parseInt(product.stock) > 0) ? (
              <span className="in-stock">In Stock ({parseInt(product.stock_quantity || product.stock || 0)} available)</span>
            ) : (
              <span className="out-of-stock">Out of Stock</span>
            )}
          </div>

          <div className="product-detail-actions">
            {(parseInt(product.stock_quantity) > 0 || parseInt(product.stock) > 0) ? (
              <div className="product-cart-section">
                <div className="quantity-selector">
                  <button
                    className="qty-btn"
                    onClick={() => setQuantity(Math.max(1, quantity - 1))}
                    disabled={adding}
                  >
                    -
                  </button>
                  <input
                    type="number"
                    value={quantity}
                    onChange={(e) => setQuantity(Math.max(1, parseInt(e.target.value) || 1))}
                    min="1"
                    max={parseInt(product.stock_quantity || product.stock || 100)}
                    disabled={adding}
                  />
                  <button
                    className="qty-btn"
                    onClick={() => setQuantity(quantity + 1)}
                    disabled={adding}
                  >
                    +
                  </button>
                </div>
                <button
                  className={`add-to-cart-btn ${added ? 'added' : ''}`}
                  onClick={handleAddToCart}
                  disabled={adding}
                >
                  <FontAwesomeIcon icon={faShoppingCart} />
                  {adding ? ' Adding...' : added ? ' Added!' : ' Add to Cart'}
                </button>
              </div>
            ) : (
              <div className="out-of-stock-message">
                This product is currently out of stock
              </div>
            )}
            <Link to="/products" className="back-to-products-btn">
              Back to Products
            </Link>
          </div>

          <div className="product-detail-tabs">
            <div className="tab-buttons">
              <button
                className={activeTab === 'description' ? 'active' : ''}
                onClick={() => setActiveTab('description')}
              >
                Description
              </button>
              <button
                className={activeTab === 'specifications' ? 'active' : ''}
                onClick={() => setActiveTab('specifications')}
              >
                Specifications
              </button>

              <button
                className={activeTab === 'calculator' ? 'active' : ''}
                onClick={() => setActiveTab('calculator')}
              >
                Calculate Materials
              </button>
            </div>

            <div className="tab-content">
              {activeTab === 'description' && (
                <div className="tab-description">
                  <p>{product.description}</p>
                </div>
              )}

              {activeTab === 'specifications' && (
                <div className="tab-specifications">
                  <table>
                    <tbody>
                      <tr>
                        <th>Product Name</th>
                        <td>{product.name}</td>
                      </tr>
                      <tr>
                        <th>Category</th>
                        <td>{product.category_name}</td>
                      </tr>
                      <tr>
                        <th>Supplier</th>
                        <td>{product.supplier_name}</td>
                      </tr>
                      <tr>
                        <th>Unit</th>
                        <td>{product.unit}</td>
                      </tr>
                      {product.dimensions && (
                        <tr>
                          <th>Dimensions</th>
                          <td>
                            {typeof product.dimensions === 'string' ? (
                              <div className="product-dimensions">
                                {(() => {
                                  try {
                                    const dims = JSON.parse(product.dimensions);
                                    return (
                                      <>
                                        {dims.width && <div>Width: {dims.width}m</div>}
                                        {dims.height && <div>Height: {dims.height}m</div>}
                                        {dims.length && <div>Length: {dims.length}m</div>}
                                        {dims.coverage && <div>Coverage: {dims.coverage}m²</div>}
                                        {dims.material_type && <div>Type: {dims.material_type}</div>}
                                      </>
                                    );
                                  } catch (e) {
                                    return <div>{product.dimensions}</div>;
                                  }
                                })()}
                              </div>
                            ) : (
                              <div className="product-dimensions">
                                {product.dimensions.width && <div>Width: {product.dimensions.width}m</div>}
                                {product.dimensions.height && <div>Height: {product.dimensions.height}m</div>}
                                {product.dimensions.length && <div>Length: {product.dimensions.length}m</div>}
                                {product.dimensions.coverage && <div>Coverage: {product.dimensions.coverage}m²</div>}
                                {product.dimensions.material_type && <div>Type: {product.dimensions.material_type}</div>}
                              </div>
                            )}
                          </td>
                        </tr>
                      )}
                    </tbody>
                  </table>
                </div>
              )}

              {activeTab === 'calculator' && (
                <div className="tab-calculator">
                  <h3>Calculate Materials Needed</h3>
                  <p>Estimate how much {product.name} you'll need for your project.</p>

                  {/* For weight and unit products - show simple info, no dimensional calculator */}
                  {product.calculation_type && (product.calculation_type === 'weight' || product.calculation_type === 'unit') && (
                    <div className="calculator-type-info unit-weight-info">
                      <div className="unit-weight-message">
                        {product.calculation_type === 'weight' ? (
                          <>
                            <span className="calc-type-label">Weight-based Product (kg)</span>
                            <p>This product is sold by weight. Use the quantity selector above to add the desired amount to your cart.</p>
                            <p><strong>Price:</strong> ${parseFloat(product.price || 0).toFixed(2)} per {product.unit || 'kg'}</p>
                          </>
                        ) : (
                          <>
                            <span className="calc-type-label">Unit-based Product</span>
                            <p>This product is sold by unit. Use the quantity selector above to add the desired amount to your cart.</p>
                            <p><strong>Price:</strong> ${parseFloat(product.price || 0).toFixed(2)} per {product.unit || 'unit'}</p>
                          </>
                        )}
                      </div>
                    </div>
                  )}

                  {/* Show calculation type info for dimensional products - type is set by manager, not selectable by customer */}
                  {product.calculation_type && ['area', 'volume', 'linear', 'length'].includes(product.calculation_type) && (
                    <div className="calculator-type-info">
                      <span className="calc-type-label">
                        {calculationType === 'area' && 'Area Calculation (m²)'}
                        {calculationType === 'volume' && 'Volume Calculation (m³)'}
                        {calculationType === 'linear' && 'Linear Calculation (m)'}
                      </span>
                    </div>
                  )}

                  {/* Only show selector if no specific calculation_type is set for this product */}
                  {!product.calculation_type && (
                    <div className="calculator-type-selector">
                      <label htmlFor="calculationType">Calculation Type:</label>
                      <select
                        id="calculationType"
                        value={calculationType}
                        onChange={(e) => {
                          setCalculationType(e.target.value);
                          setCalculationResult(null);
                          setCalculationError(null);
                        }}
                        disabled={isCalculating}
                      >
                        <option value="area">Area (Flooring, Paint, etc.)</option>
                        <option value="volume">Volume (Concrete, Gravel, etc.)</option>
                        <option value="linear">Linear (Pipes, Cables, etc.)</option>
                      </select>
                    </div>
                  )}

                  {/* Only show dimensional calculators for non-weight/unit products */}
                  {(!product.calculation_type || !['weight', 'unit'].includes(product.calculation_type)) && calculationType === 'area' && (
                    <AreaCalculator
                      productDimensions={(() => {
                        let dims = product.dimensions;
                        if (typeof dims === 'string') {
                          try {
                            dims = JSON.parse(dims);
                          } catch (e) {
                            dims = null;
                          }
                        }
                        return dims;
                      })()}
                      productName={product.name}
                      onCalculationStart={() => {
                        setIsCalculating(true);
                        setCalculationError(null);
                      }}
                      onCalculationResult={(result, data) => {
                        console.log('Calculation result:', result, data);
                        setCalculationResult(result);
                        setIsCalculating(false);
                      }}
                      onCalculationError={(error) => {
                        setCalculationError(error.message || 'An error occurred during calculation');
                        setIsCalculating(false);
                        setCalculationResult(null);
                      }}
                    />
                  )}

                  {(!product.calculation_type || !['weight', 'unit'].includes(product.calculation_type)) && calculationType === 'volume' && (
                    <VolumeCalculator
                      productDimensions={(() => {
                        let dims = product.dimensions;
                        if (typeof dims === 'string') {
                          try {
                            dims = JSON.parse(dims);
                          } catch (e) {
                            dims = null;
                          }
                        }
                        return dims;
                      })()}
                      productName={product.name}
                      onCalculationStart={() => {
                        setIsCalculating(true);
                        setCalculationError(null);
                      }}
                      onCalculationResult={(result, data) => {
                        console.log('Calculation result:', result, data);
                        setCalculationResult(result);
                        setIsCalculating(false);
                      }}
                      onCalculationError={(error) => {
                        setCalculationError(error.message || 'An error occurred during calculation');
                        setIsCalculating(false);
                        setCalculationResult(null);
                      }}
                    />
                  )}

                  {(!product.calculation_type || !['weight', 'unit'].includes(product.calculation_type)) && calculationType === 'linear' && (
                    <LinearCalculator
                      productDimensions={(() => {
                        let dims = product.dimensions;
                        if (typeof dims === 'string') {
                          try {
                            dims = JSON.parse(dims);
                          } catch (e) {
                            dims = null;
                          }
                        }
                        return dims;
                      })()}
                      productName={product.name}
                      onCalculationStart={() => {
                        setIsCalculating(true);
                        setCalculationError(null);
                      }}
                      onCalculationResult={(result, data) => {
                        console.log('Linear calculation result:', result);
                        setIsCalculating(false);
                        
                        if (result && result.success) {
                          // Calculate quantity needed based on length
                          const quantity = result.result.unitsNeeded || Math.ceil(result.result.requiredLength / (data.length || 1));
                          const totalCost = quantity * (product.price || 0);
                          
                          const calculationResult = {
                            success: true,
                            quantity,
                            totalCost,
                            details: {
                              measuredLength: result.result.length,
                              requiredLength: result.result.requiredLength,
                              wastage: result.result.wastagePercentage,
                              projectLength: data.length
                            }
                          };
                          
                          setCalculationResult(calculationResult);
                          
                          // Share calculation with chatbot
                          const projectDimensions = {
                            length: data.length
                          };
                          
                          shareCalculationWithChatbot(
                            calculationResult,
                            product,
                            projectDimensions,
                            'linear'
                          );
                        } else {
                          setCalculationResult({
                            success: false,
                            error: 'Failed to calculate linear requirements'
                          });
                        }
                      }}
                      onCalculationError={(error) => {
                        setCalculationError(error.message || 'An error occurred during calculation');
                        setIsCalculating(false);
                        setCalculationResult(null);
                      }}
                    />
                  )}

                  {calculationError && (
                    <div className="calculator-error">
                      <ErrorMessage
                        message={calculationError}
                        type="warning"
                        dismissible={true}
                        onDismiss={() => setCalculationError(null)}
                      />
                    </div>
                  )}
                  


                  {console.log('Calculation result state:', calculationResult)}
                  {calculationResult && calculationResult.success && (
                    <>
                      <div className="calculator-results">
                        <h3>Calculation Results</h3>

                        {calculationType === 'area' && calculationResult.result && (
                          <>
                            <div className="result-row">
                              <span className="result-label">Total Area:</span>
                              <span className="result-value">{calculationResult.result.area} m²</span>
                            </div>
                            <div className="result-row">
                              <span className="result-label">Required Quantity:</span>
                              <span className="result-value">{Math.ceil(calculationResult.result.area / (calculationResult.result.coverage || 1))} units</span>
                            </div>
                            <div className="result-row">
                              <span className="result-label">Wastage:</span>
                              <span className="result-value">{calculationResult.result.wastagePercentage}% ({calculationResult.result.wastageAmount.toFixed(2)} m²)</span>
                            </div>
                          </>
                        )}

                        {calculationType === 'volume' && calculationResult.result && (
                          <>
                            <div className="result-row">
                              <span className="result-label">Total Volume:</span>
                              <span className="result-value">{calculationResult.result.volume} m³</span>
                            </div>
                            <div className="result-row">
                              <span className="result-label">Required Volume:</span>
                              <span className="result-value">{calculationResult.result.requiredVolume} m³</span>
                            </div>
                            <div className="result-row">
                              <span className="result-label">Wastage:</span>
                              <span className="result-value">{calculationResult.result.wastagePercentage}% ({calculationResult.result.wastageAmount.toFixed(2)} m³)</span>
                            </div>
                          </>
                        )}

                        {calculationType === 'linear' && calculationResult.result && (
                          <>
                            <div className="result-row">
                              <span className="result-label">Total Length:</span>
                              <span className="result-value">{calculationResult.result.length} m</span>
                            </div>
                            <div className="result-row">
                              <span className="result-label">Required Length:</span>
                              <span className="result-value">{calculationResult.result.requiredLength} m</span>
                            </div>
                            <div className="result-row">
                              <span className="result-label">Wastage:</span>
                              <span className="result-value">{calculationResult.result.wastagePercentage}% ({calculationResult.result.wastageAmount.toFixed(2)} m)</span>
                            </div>
                            {calculationResult.result.pieceLength && calculationResult.result.piecesNeeded && (
                              <div className="result-row">
                                <span className="result-label">Pieces Needed:</span>
                                <span className="result-value">{calculationResult.result.piecesNeeded} pieces ({calculationResult.result.pieceLength} m each)</span>
                              </div>
                            )}
                          </>
                        )}

                        <div className="result-note">
                          <p>
                            <strong>Note:</strong> These calculations are estimates. Actual material needs may vary
                            based on specific project conditions and material properties.
                          </p>
                        </div>
                      </div>

                      <div className="product-result-message">
                        <p>Based on your measurements, you'll need approximately:</p>
                        <h4>
                          {(() => {
                            let quantity = 0;
                            if (calculationResult.result) {
                              if (calculationType === 'area') {
                                // For area calculations, calculate how many units of the product are needed
                                // First check if we have coverage from the calculation result
                                if (calculationResult.result.coverage) {
                                  // If we have coverage, use it to calculate quantity
                                  quantity = Math.ceil(calculationResult.result.area / calculationResult.result.coverage);
                                } else {
                                  // If no coverage in result, try to calculate based on product dimensions
                                  let productArea = 0;
                                  let dims = product.dimensions;
                                  if (typeof dims === 'string') {
                                    try {
                                      dims = JSON.parse(dims);
                                      if (dims.length && dims.width) {
                                        productArea = dims.length * dims.width;
                                      }
                                    } catch (e) {
                                      console.error('Error parsing dimensions:', e);
                                    }
                                  } else if (dims && dims.length && dims.width) {
                                    productArea = dims.length * dims.width;
                                  }
                                  
                                  // If we have valid product area, divide the required area by it
                                  if (productArea > 0) {
                                    quantity = Math.ceil(calculationResult.result.area / productArea);
                                  } else {
                                    // Fallback to just using area with default coverage of 1
                                    quantity = Math.ceil(calculationResult.result.area);
                                  }
                                }
                              } else if (calculationType === 'volume') {
                                // For volume calculations, calculate how many units of the product are needed
                                // Get product dimensions
                                let productVolume = 0;
                                let dims = product.dimensions;
                                if (typeof dims === 'string') {
                                  try {
                                    dims = JSON.parse(dims);
                                    if (dims.length && dims.width && dims.height) {
                                      productVolume = dims.length * dims.width * dims.height;
                                    }
                                  } catch (e) {
                                    console.error('Error parsing dimensions:', e);
                                  }
                                } else if (dims && dims.length && dims.width && dims.height) {
                                  productVolume = dims.length * dims.width * dims.height;
                                }
                                
                                // If we have valid product volume, divide the required volume by it
                                if (productVolume > 0) {
                                  quantity = Math.ceil(calculationResult.result.requiredVolume / productVolume);
                                } else {
                                  // Fallback to just using the volume if we can't calculate product volume
                                  quantity = Math.ceil(calculationResult.result.volume);
                                }
                              } else if (calculationType === 'linear') {
                                // For linear calculations, use the length directly
                                quantity = Math.ceil(calculationResult.result.requiredLength);
                              }
                            }
                            return quantity;
                          })()}
                          {' '}{product.unit}(s) of {product.name}
                        </h4>
                        <p>Total cost estimate: ${((() => {
                          let quantity = 0;
                          if (calculationResult.result) {
                            if (calculationType === 'area') {
                              // For area calculations, calculate how many units of the product are needed
                              // First check if we have coverage from the calculation result
                              if (calculationResult.result.coverage) {
                                // If we have coverage, use it to calculate quantity
                                quantity = Math.ceil(calculationResult.result.area / calculationResult.result.coverage);
                              } else {
                                // If no coverage in result, try to calculate based on product dimensions
                                let productArea = 0;
                                let dims = product.dimensions;
                                if (typeof dims === 'string') {
                                  try {
                                    dims = JSON.parse(dims);
                                    if (dims.length && dims.width) {
                                      productArea = dims.length * dims.width;
                                    }
                                  } catch (e) {
                                    console.error('Error parsing dimensions:', e);
                                  }
                                } else if (dims && dims.length && dims.width) {
                                  productArea = dims.length * dims.width;
                                }
                                
                                // If we have valid product area, divide the required area by it
                                if (productArea > 0) {
                                  quantity = Math.ceil(calculationResult.result.area / productArea);
                                } else {
                                  // Fallback to just using area with default coverage of 1
                                  quantity = Math.ceil(calculationResult.result.area);
                                }
                              }
                            } else if (calculationType === 'volume') {
                              // For volume calculations, calculate how many units of the product are needed
                              // Get product dimensions
                              let productVolume = 0;
                              let dims = product.dimensions;
                              if (typeof dims === 'string') {
                                try {
                                  dims = JSON.parse(dims);
                                  if (dims.length && dims.width && dims.height) {
                                    productVolume = dims.length * dims.width * dims.height;
                                  }
                                } catch (e) {
                                  console.error('Error parsing dimensions:', e);
                                }
                              } else if (dims && dims.length && dims.width && dims.height) {
                                productVolume = dims.length * dims.width * dims.height;
                              }
                              
                              // If we have valid product volume, divide the required volume by it
                              if (productVolume > 0) {
                                quantity = Math.ceil(calculationResult.result.requiredVolume / productVolume);
                              } else {
                                // Fallback to just using the volume if we can't calculate product volume
                                quantity = Math.ceil(calculationResult.result.volume);
                              }
                            } else if (calculationType === 'linear') {
                              quantity = Math.ceil(calculationResult.result.requiredLength);
                            }
                            return quantity * product.price;
                          }
                          return 0;
                        })()).toFixed(2)}</p>
                      </div>
                      <div className="calculator-actions">
                        <button
                          className="reset-button"
                          onClick={() => {
                            setCalculationResult(null);
                          }}
                          title="Clear results and start a new calculation"
                        >
                          <i className="fas fa-redo"></i> New Calculation
                        </button>
                      </div>
                    </>
                  )}
                </div>
              )}
            </div>
          </div>
        </div>
      </div>

      {relatedProducts.length > 0 && (
        <div className="related-products">
          <h2>Related Products</h2>
          <div className="related-products-grid">
            {relatedProducts.map(relatedProduct => (
              <div key={relatedProduct.id} className="related-product-card">
                <Link to={`/products/${relatedProduct.id}`}>
                  <div className="related-product-image">
                    {relatedProduct.thumbnail ? <>
                      <img src={relatedProduct.thumbnail} alt={relatedProduct.name} />
                    </> : <>
                      <img src={unknown_material} alt={relatedProduct.name} />
                    </>}
                  </div>
                  <div className="related-product-info">
                    <h3>{relatedProduct.name}</h3>
                    <div className="related-product-price">${parseFloat(relatedProduct.price).toFixed(2)}</div>
                  </div>
                </Link>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}

export default ProductDetail;
