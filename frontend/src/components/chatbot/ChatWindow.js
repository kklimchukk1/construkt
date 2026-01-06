import React, { useEffect, useRef } from 'react';
import { Card, Button } from 'react-bootstrap';
import { FaTimes, FaChevronDown } from 'react-icons/fa';
import ChatMessage from './ChatMessage';
import CommandPanel from './CommandPanel';
import SearchPanel from './SearchPanel';
import CalculatorPanel from './CalculatorPanel';
import './ChatWindow.css';

/**
 * ChatWindow component for displaying the chat interface
 * Uses command-based UI instead of free-form text input
 *
 * @param {Object} props Component props
 * @param {Array} props.messages Array of chat messages
 * @param {boolean} props.isLoading Whether the chatbot is processing a message
 * @param {Function} props.onClose Callback function when chat window is closed
 * @param {boolean} props.isMinimized Whether the chat window is minimized
 * @param {Function} props.onToggleMinimize Callback function to toggle minimize state
 * @param {Function} props.onClearChat Callback function to clear chat history
 * @param {string} props.currentView Current view state ('commands', 'search', etc.)
 * @param {Function} props.onCommand Callback for command execution
 * @param {Function} props.onSearch Callback for search
 * @param {Function} props.onShowCommands Callback to show commands panel
 * @param {Function} props.onAction Callback for action button clicks
 * @param {Array} props.popularSearches Popular search terms
 */
const ChatWindow = ({
  messages,
  isLoading,
  onClose,
  isMinimized,
  onToggleMinimize,
  onClearChat,
  currentView = 'commands',
  onCommand,
  onSearch,
  onShowCommands,
  onAction,
  onCalculate,
  popularSearches
}) => {
  // Reference to the messages container for auto-scrolling
  const messagesEndRef = useRef(null);
  
  // Scroll to bottom when messages change
  useEffect(() => {
    if (messagesEndRef.current && !isMinimized) {
      messagesEndRef.current.scrollIntoView({ behavior: 'smooth' });
    }
  }, [messages, isMinimized]);
  
  return (
    <div className="chat-window-container">
      <Card className="chat-window">
        {/* Chat Header */}
        <Card.Header 
          className="d-flex justify-content-between align-items-center py-2 px-3"
          onClick={onToggleMinimize}
        >
          <div className="d-flex align-items-center">
            <span className="me-2">Construkt Assistant</span>
          </div>
          <div>
            <Button 
              variant="link" 
              size="sm" 
              className="p-0 me-2 minimize-button" 
              onClick={(e) => {
                e.stopPropagation();
                onToggleMinimize();
              }}
            >
              <FaChevronDown 
                className={`chevron-icon ${isMinimized ? 'minimized' : ''}`}
              />
            </Button>
            <Button 
              variant="link" 
              size={24}
              className="close-button" 
              onClick={(e) => {
                e.stopPropagation();
                onClose();
              }}
            >
              <FaTimes />
            </Button>
          </div>
        </Card.Header>
        
        {/* Chat Body - Hidden when minimized */}
        {!isMinimized && (
          <>
            <Card.Body 
              className="p-3 chat-messages"
            >
              {messages.length === 0 ? (
                <div className="text-center text-muted my-5">
                  <p>Welcome to Construkt Assistant!</p>
                  <p className="small">How can I help you with construction materials today?</p>
                </div>
              ) : (
                messages.map((msg, index) => (
                  <ChatMessage
                    key={index}
                    message={msg.message}
                    isUser={msg.isUser}
                    timestamp={msg.timestamp}
                    data={msg.data}
                    onAction={onAction}
                  />
                ))
              )}
              <div ref={messagesEndRef} />
            </Card.Body>
            
            {/* Command Panel / Search Panel */}
            <Card.Footer className="p-0 border-top-0">
              {isLoading && (
                <div className="text-center py-2 bg-light">
                  <small className="text-muted">Processing...</small>
                </div>
              )}

              {/* Show appropriate panel based on current view */}
              {currentView === 'search' ? (
                <SearchPanel
                  onSearch={onSearch}
                  onCancel={onShowCommands}
                  popularSearches={popularSearches}
                  isLoading={isLoading}
                />
              ) : currentView === 'calculator' ? (
                <CalculatorPanel
                  onCancel={onShowCommands}
                />
              ) : (
                /* Show Command Panel in default mode */
                <CommandPanel
                  onCommand={onCommand}
                  isLoading={isLoading}
                />
              )}

              {messages.length > 0 && (
                <div className="reset-button text-center pb-2">
                  <Button
                    variant="link"
                    size="sm"
                    className="text-muted"
                    onClick={onClearChat}
                  >
                    Reset Chat
                  </Button>
                </div>
              )}
            </Card.Footer>
          </>
        )}
      </Card>
    </div>
  );
};

export default ChatWindow;
