import React from 'react';
import { useChat } from '../../context/ChatContext';
import { useAuth } from '../../context/AuthContext';
import ChatWindow from './ChatWindow';
import ChatButton from './ChatButton';
import './Chatbot.css';

/**
 * Main Chatbot component that integrates the chat button and window
 * Uses command-based UI instead of free-form text input
 * Only visible to authenticated users
 */
const Chatbot = () => {
  const { isAuthenticated } = useAuth();
  const {
    isOpen,
    isMinimized,
    messages,
    isLoading,
    closeChat,
    toggleMinimize,
    clearChat,
    // Command-based UI
    currentView,
    popularSearches,
    sendCommand,
    handleAction,
    showCommands,
    showCalculator,
    handleSearch,
    handleCalculate
  } = useChat();

  // Only show chatbot for authenticated users
  if (!isAuthenticated()) {
    return null;
  }

  // Handle command from CommandPanel
  const onCommand = (command) => {
    if (command === 'SEARCH') {
      // Show search panel instead of executing command
      handleAction('SEARCH');
    } else if (command === 'CALCULATOR') {
      // Show calculator panel
      showCalculator();
    } else {
      // Execute command directly
      sendCommand(command);
    }
  };

  return (
    <>
      {/* Chat Button - visible when chat is closed */}
      <ChatButton />

      {/* Chat Window - visible when chat is open */}
      {isOpen && (
        <ChatWindow
          messages={messages}
          isLoading={isLoading}
          onClose={closeChat}
          isMinimized={isMinimized}
          onToggleMinimize={toggleMinimize}
          onClearChat={clearChat}
          currentView={currentView}
          onCommand={onCommand}
          onSearch={handleSearch}
          onShowCommands={showCommands}
          onAction={handleAction}
          onCalculate={handleCalculate}
          popularSearches={popularSearches}
        />
      )}
    </>
  );
};

export default Chatbot;
