import React, { useState } from 'react';
import { Form, Button, InputGroup } from 'react-bootstrap';
import { FaPaperPlane } from 'react-icons/fa';
import './ChatInput.css';

/**
 * ChatInput component for sending messages to the chatbot
 * 
 * @param {Object} props Component props
 * @param {Function} props.onSendMessage Callback function when message is sent
 * @param {boolean} props.isLoading Whether the chatbot is processing a message
 */
const ChatInput = ({ onSendMessage, isLoading }) => {
  const [message, setMessage] = useState('');
  
  /**
   * Handle form submission
   * 
   * @param {Event} e Form submit event
   */
  const handleSubmit = (e) => {
    e.preventDefault();
    
    // Trim message and check if it's not empty
    const trimmedMessage = message.trim();
    if (!trimmedMessage) return;
    
    // Call the onSendMessage callback with the message
    onSendMessage(trimmedMessage);
    
    // Clear the input field
    setMessage('');
  };
  
  return (
    <Form onSubmit={handleSubmit} className="chat-input-form">
      <InputGroup className="chat-input">
        <Form.Control
          type="text"
          placeholder="Type your message here..."
          value={message}
          onChange={(e) => setMessage(e.target.value)}
          disabled={isLoading}
          aria-label="Chat message"
        />
        <Button 
          variant="primary" 
          type="submit" 
          disabled={isLoading || !message.trim()}
          className="chat-input-button"
        >
          <FaPaperPlane />
        </Button>
      </InputGroup>
    </Form>
  );
};

export default ChatInput;
