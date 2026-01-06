import React from 'react';
import { Button } from 'react-bootstrap';
import { FaComments } from 'react-icons/fa';
import { useChat } from '../../context/ChatContext';
import './ChatButton.css';

/**
 * ChatButton component for opening the chat window
 */
const ChatButton = () => {
  const { isOpen, openChat } = useChat();
  
  return (
    <Button
      variant="primary"
      className="chat-button"
      onClick={openChat}
      style={{
        display: isOpen ? 'none' : 'flex'
      }}
      aria-label="Open chat"
    >
      <FaComments size={24} />
    </Button>
  );
};

export default ChatButton;
