import React from 'react';

const ChatMessage = ({ message }) => {
    const { type, content, data, timestamp } = message;
    const isBot = type === 'bot';

    const formatTime = (ts) => {
        if (!ts) return '';
        const date = new Date(ts);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    };

    const renderContent = () => {
        // If message has structured data (products, categories, etc.)
        if (data) {
            return renderStructuredContent(data);
        }
        // Plain text message
        return <p className="chatbot-message__text">{content}</p>;
    };

    const renderStructuredContent = (data) => {
        switch (data.type) {
            case 'products':
                return renderProducts(data.items);
            case 'categories':
                return renderCategories(data.items);
            case 'calculator':
                return renderCalculatorResult(data.result);
            case 'product_detail':
                return renderProductDetail(data.product);
            default:
                return <p className="chatbot-message__text">{content}</p>;
        }
    };

    const renderProducts = (products) => (
        <div className="chatbot-products">
            {content && <p className="chatbot-message__text">{content}</p>}
            <div className="chatbot-products__grid">
                {products.slice(0, 6).map((product, index) => (
                    <div key={index} className="chatbot-product-card">
                        <div className="chatbot-product-card__image">
                            {product.thumbnail ? (
                                <img src={product.thumbnail} alt={product.name} />
                            ) : (
                                <div className="chatbot-product-card__placeholder">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                                    </svg>
                                </div>
                            )}
                        </div>
                        <div className="chatbot-product-card__info">
                            <h4 className="chatbot-product-card__name">{product.name}</h4>
                            <p className="chatbot-product-card__price">
                                ${Number(product.price).toFixed(2)}
                                <span className="chatbot-product-card__unit">/{product.unit}</span>
                            </p>
                            <span className={`chatbot-product-card__stock ${product.stock_quantity > 0 ? 'in-stock' : 'out-stock'}`}>
                                {product.stock_quantity > 0 ? 'In Stock' : 'Out of Stock'}
                            </span>
                        </div>
                        <a
                            href={product.link || `/product.php?id=${product.id}`}
                            className="chatbot-product-card__button"
                            target="_self"
                        >
                            View Details
                        </a>
                    </div>
                ))}
            </div>
            {products.length > 6 && (
                <p className="chatbot-products__more">
                    And {products.length - 6} more products...
                    <a href="/products.php">View all</a>
                </p>
            )}
        </div>
    );

    const renderCategories = (categories) => (
        <div className="chatbot-categories">
            {content && <p className="chatbot-message__text">{content}</p>}
            <div className="chatbot-categories__list">
                {categories.map((category, index) => (
                    <a
                        key={index}
                        href={category.link || `/products.php?category=${category.id}`}
                        className="chatbot-category-item"
                    >
                        <span className="chatbot-category-item__name">{category.name}</span>
                        <span className="chatbot-category-item__count">{category.product_count || 0} products</span>
                    </a>
                ))}
            </div>
        </div>
    );

    const renderCalculatorResult = (result) => (
        <div className="chatbot-calculator-result">
            {content && <p className="chatbot-message__text">{content}</p>}
            <div className="chatbot-calculator-result__card">
                <div className="chatbot-calculator-result__row">
                    <span>Result:</span>
                    <strong>{result.value} {result.unit}</strong>
                </div>
                {result.with_wastage && (
                    <div className="chatbot-calculator-result__row">
                        <span>With wastage ({result.wastage}%):</span>
                        <strong>{result.with_wastage} {result.unit}</strong>
                    </div>
                )}
            </div>
        </div>
    );

    const renderProductDetail = (product) => (
        <div className="chatbot-product-detail">
            <div className="chatbot-product-detail__card">
                <div className="chatbot-product-detail__image">
                    {product.thumbnail ? (
                        <img src={product.thumbnail} alt={product.name} />
                    ) : (
                        <div className="chatbot-product-detail__placeholder">No Image</div>
                    )}
                </div>
                <div className="chatbot-product-detail__info">
                    <h4>{product.name}</h4>
                    <p className="chatbot-product-detail__category">{product.category_name}</p>
                    <p className="chatbot-product-detail__price">
                        ${Number(product.price).toFixed(2)} / {product.unit}
                    </p>
                    {product.description && (
                        <p className="chatbot-product-detail__desc">{product.description}</p>
                    )}
                    <a href={product.link || `/product.php?id=${product.id}`} className="chatbot-product-detail__button">
                        View Full Details
                    </a>
                </div>
            </div>
        </div>
    );

    return (
        <div className={`chatbot-message chatbot-message--${isBot ? 'bot' : 'user'}`}>
            {isBot && (
                <div className="chatbot-message__avatar">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                </div>
            )}
            <div className="chatbot-message__content">
                {renderContent()}
                <span className="chatbot-message__time">{formatTime(timestamp)}</span>
            </div>
        </div>
    );
};

export default ChatMessage;
