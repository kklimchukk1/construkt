import React, { useState, useRef, useEffect } from 'react';
import { useChat } from '../context/ChatContext';
import ChatMessage from './ChatMessage';
import CommandPanel from './CommandPanel';

const ChatWindow = ({ isMinimized, onMinimize, onClose, onExpand, config }) => {
    const { messages, sendMessage, sendCommand, isLoading, currentView, clearChat } = useChat();
    const [inputValue, setInputValue] = useState('');
    const messagesEndRef = useRef(null);
    const inputRef = useRef(null);

    // Scroll to bottom when messages change
    useEffect(() => {
        if (messagesEndRef.current) {
            messagesEndRef.current.scrollIntoView({ behavior: 'smooth' });
        }
    }, [messages]);

    // Focus input when window opens
    useEffect(() => {
        if (!isMinimized && inputRef.current) {
            inputRef.current.focus();
        }
    }, [isMinimized]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!inputValue.trim() || isLoading) return;

        await sendMessage(inputValue.trim());
        setInputValue('');
    };

    const handleKeyDown = (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSubmit(e);
        }
    };

    if (isMinimized) {
        return (
            <div className="chatbot-window chatbot-window--minimized" onClick={onExpand}>
                <div className="chatbot-window__header">
                    <span className="chatbot-window__title">Chat Assistant</span>
                </div>
            </div>
        );
    }

    return (
        <div className="chatbot-window">
            {/* Header */}
            <div className="chatbot-window__header">
                <div className="chatbot-window__header-info">
                    <div className="chatbot-window__avatar">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                    </div>
                    <div>
                        <div className="chatbot-window__title">Construkt Assistant</div>
                        <div className="chatbot-window__status">Online</div>
                    </div>
                </div>
                <div className="chatbot-window__actions">
                    <button className="chatbot-window__action" onClick={clearChat} aria-label="Clear history" title="Clear history">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </button>
                    <button className="chatbot-window__action" onClick={onMinimize} aria-label="Minimize">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </button>
                    <button className="chatbot-window__action" onClick={onClose} aria-label="Close">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
            </div>

            {/* Command Panel */}
            <CommandPanel onCommand={sendCommand} />

            {/* Messages */}
            <div className="chatbot-window__messages">
                {messages.length === 0 ? (
                    <div className="chatbot-window__welcome">
                        <div className="chatbot-window__welcome-icon">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                            </svg>
                        </div>
                        <h3>Welcome to Construkt!</h3>
                        <p>Hi {config.userName || 'there'}! How can I help you today?</p>
                        <p className="chatbot-window__welcome-hint">
                            Use the buttons above or type a message below.
                        </p>
                    </div>
                ) : (
                    messages.map((message, index) => (
                        <ChatMessage key={index} message={message} />
                    ))
                )}
                {isLoading && (
                    <div className="chatbot-message chatbot-message--bot">
                        <div className="chatbot-message__typing">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                )}
                <div ref={messagesEndRef} />
            </div>

            {/* Input */}
            <form className="chatbot-window__input-container" onSubmit={handleSubmit}>
                <input
                    ref={inputRef}
                    type="text"
                    className="chatbot-window__input"
                    placeholder="Type a message..."
                    value={inputValue}
                    onChange={(e) => setInputValue(e.target.value)}
                    onKeyDown={handleKeyDown}
                    disabled={isLoading}
                />
                <button
                    type="submit"
                    className="chatbot-window__send"
                    disabled={!inputValue.trim() || isLoading}
                >
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </form>
        </div>
    );
};

export default ChatWindow;
