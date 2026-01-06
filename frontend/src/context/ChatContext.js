import React, { createContext, useState, useContext, useEffect, useCallback, useRef } from 'react';
import chatbotService from '../services/chatbotService';
import authService from '../services/authService';

// Create the chat context
const ChatContext = createContext();

/**
 * Get user-specific localStorage key
 */
const getUserStorageKey = (userId, key) => {
  return `chatbot_${userId}_${key}`;
};

/**
 * ChatProvider component for managing chat state
 * Each authenticated user has their own individual chat history
 *
 * @param {Object} props Component props
 * @param {React.ReactNode} props.children Child components
 */
export const ChatProvider = ({ children }) => {
  // Chat window state
  const [isOpen, setIsOpen] = useState(false);
  const [isMinimized, setIsMinimized] = useState(false);

  // Messages state
  const [messages, setMessages] = useState([]);
  const [isLoading, setIsLoading] = useState(false);

  // User ID for conversation tracking (must be authenticated)
  const [userId, setUserId] = useState(null);

  // Track previous user to detect user changes
  const prevUserIdRef = useRef(null);

  // Command-based UI state
  const [currentView, setCurrentView] = useState('commands'); // 'commands', 'search', 'categories', 'calculator'
  const [popularSearches, setPopularSearches] = useState([
    'Nails', 'Cement', 'Bricks', 'Paint', 'Tiles', 'Lumber'
  ]);

  // Initialize and track authenticated user
  useEffect(() => {
    chatbotService.resetOfflineStatus();

    const currentUser = authService.getCurrentUser();

    if (currentUser && currentUser.id) {
      const newUserId = String(currentUser.id);

      // Check if user changed (different user logged in)
      if (prevUserIdRef.current && prevUserIdRef.current !== newUserId) {
        // User changed - clear current messages
        setMessages([]);
        setIsOpen(false);
        console.log('User changed, clearing chat');
      }

      setUserId(newUserId);
      prevUserIdRef.current = newUserId;

      // Load this user's chat history from localStorage
      const storedMessages = localStorage.getItem(getUserStorageKey(newUserId, 'messages'));
      if (storedMessages) {
        try {
          setMessages(JSON.parse(storedMessages));
        } catch (error) {
          console.error('Error parsing stored messages:', error);
        }
      }

      console.log('Chat initialized for user:', newUserId);
    } else {
      // No authenticated user - clear chat state
      setUserId(null);
      setMessages([]);
      setIsOpen(false);
    }
  }, []);

  // Save messages to user-specific localStorage when they change
  useEffect(() => {
    if (userId && messages.length > 0) {
      localStorage.setItem(getUserStorageKey(userId, 'messages'), JSON.stringify(messages));
    }
  }, [messages, userId]);

  // Listen for authentication changes (login/logout)
  useEffect(() => {
    const handleAuthChange = () => {
      const currentUser = authService.getCurrentUser();

      if (currentUser && currentUser.id) {
        const newUserId = String(currentUser.id);

        // User changed - load their chat history
        if (prevUserIdRef.current !== newUserId) {
          setMessages([]);
          setIsOpen(false);

          // Load new user's messages
          const storedMessages = localStorage.getItem(getUserStorageKey(newUserId, 'messages'));
          if (storedMessages) {
            try {
              setMessages(JSON.parse(storedMessages));
            } catch (error) {
              console.error('Error parsing stored messages:', error);
            }
          }

          setUserId(newUserId);
          prevUserIdRef.current = newUserId;
          console.log('Switched to user:', newUserId);
        }
      } else {
        // User logged out
        setUserId(null);
        setMessages([]);
        setIsOpen(false);
        prevUserIdRef.current = null;
        console.log('User logged out, chat cleared');
      }
    };

    // Listen for storage events (login/logout from other tabs)
    const handleStorageChange = (e) => {
      if (e.key === 'user' || e.key === 'token') {
        handleAuthChange();
      }
    };

    window.addEventListener('storage', handleStorageChange);

    // Also poll for changes (for same-tab auth changes)
    const pollInterval = setInterval(handleAuthChange, 1000);

    return () => {
      window.removeEventListener('storage', handleStorageChange);
      clearInterval(pollInterval);
    };
  }, []);
  
  /**
   * Send a message to the chatbot
   * 
   * @param {string} message The message to send
   */
  const sendMessage = useCallback(async (message) => {
    if (!message.trim()) return;
    
    // Add user message to the chat
    const userMessage = {
      message,
      isUser: true,
      timestamp: new Date().toISOString()
    };
    
    setMessages(prevMessages => [...prevMessages, userMessage]);
    setIsLoading(true);
    
    try {
      // Send message to the chatbot API
      const response = await chatbotService.sendMessage(message, userId);
      
      // Add bot response to the chat
      const botMessage = {
        message: response.message,
        isUser: false,
        data: response.data,
        intent: response.intent,
        timestamp: new Date().toISOString()
      };
      
      setMessages(prevMessages => [...prevMessages, botMessage]);
    } catch (error) {
      console.error('Error sending message to chatbot:', error);
      
      // Add error message to the chat
      const errorMessage = {
        message: 'Sorry, I encountered an error. Please try again later.',
        isUser: false,
        timestamp: new Date().toISOString()
      };
      
      setMessages(prevMessages => [...prevMessages, errorMessage]);
    } finally {
      setIsLoading(false);
    }
  }, [userId]);

  /**
   * Send a command to the chatbot
   *
   * @param {string} command The command to execute (SEARCH, CATEGORIES, etc.)
   * @param {Object} params Command parameters
   */
  const sendCommand = useCallback(async (command, params = {}) => {
    setIsLoading(true);

    try {
      // Execute command
      const response = await chatbotService.sendCommand(command, params, userId);

      // Add bot response to the chat
      const botMessage = {
        message: response.message,
        isUser: false,
        data: {
          type: response.type,
          items: response.items,
          actions: response.actions,
          commands: response.commands,
          popular_searches: response.popular_searches,
          product: response.product,
          related: response.related,
          result: response.result,
          options: response.options,
          searches: response.searches,
          suggestions: response.suggestions
        },
        intent: `command_${command.toLowerCase()}`,
        timestamp: new Date().toISOString()
      };

      setMessages(prevMessages => [...prevMessages, botMessage]);

      // Update popular searches if provided
      if (response.popular_searches) {
        setPopularSearches(response.popular_searches);
      }

      // Reset view to commands after getting response
      setCurrentView('commands');

      return response;
    } catch (error) {
      console.error('Error executing command:', error);

      // Add error message to the chat
      const errorMessage = {
        message: 'Sorry, I encountered an error. Please try again.',
        isUser: false,
        data: {
          type: 'error',
          actions: [{ type: 'HELP', label: 'Get Help' }]
        },
        timestamp: new Date().toISOString()
      };

      setMessages(prevMessages => [...prevMessages, errorMessage]);
    } finally {
      setIsLoading(false);
    }
  }, [userId]);

  /**
   * Handle action button click
   *
   * @param {string} actionType The action type (command)
   * @param {Object} params Action parameters
   */
  const handleAction = useCallback((actionType, params = {}) => {
    // If it's a navigation action, change view
    if (actionType === 'SEARCH') {
      setCurrentView('search');
    } else if (actionType === 'CALCULATOR') {
      setCurrentView('calculator');
    } else {
      // Execute command
      sendCommand(actionType, params);
    }
  }, [sendCommand]);

  /**
   * Show search panel
   */
  const showSearch = useCallback(() => {
    setCurrentView('search');
  }, []);

  /**
   * Show main commands
   */
  const showCommands = useCallback(() => {
    setCurrentView('commands');
  }, []);

  /**
   * Handle search
   *
   * @param {string} keyword Search keyword
   */
  const handleSearch = useCallback((keyword) => {
    sendCommand('SEARCH', { keyword });
  }, [sendCommand]);

  /**
   * Handle calculator calculation
   *
   * @param {Object} calcData Calculator data with material_type and dimensions
   */
  const handleCalculate = useCallback((calcData) => {
    sendCommand('CALCULATOR', {
      material_type: calcData.material_type,
      dimensions: calcData.dimensions
    });
  }, [sendCommand]);

  /**
   * Show calculator panel
   */
  const showCalculator = useCallback(() => {
    setCurrentView('calculator');
  }, []);

  // Check for calculator data in localStorage
  useEffect(() => {
    const checkForCalculatorData = () => {
      // Check if there's calculator data available
      const calculatorDataStr = localStorage.getItem('chatbot_calculator_result');
      if (calculatorDataStr) {
        try {
          // Parse calculator data
          const calculatorData = JSON.parse(calculatorDataStr);
          
          // Open chat window
          setIsOpen(true);
          setIsMinimized(false);
          
          // Add a system message about the calculation
          const message = {
            id: Date.now(),
            sender: 'system',
            text: 'New calculation result available. You can ask about quantities, costs, or dimensions.',
            timestamp: new Date().toISOString(),
            data: {
              type: 'calculator_notification',
              calculatorData
            }
          };
          
          // Add the message to the chat
          setMessages(prevMessages => [...prevMessages, message]);
          
          // Don't clear the data yet - it will be used by the chatbot service
          // when the user asks about the calculation
        } catch (error) {
          console.error('Error parsing calculator data:', error);
        }
      }
    };
    
    // Check on mount
    checkForCalculatorData();
    
    // Set up event listener for storage changes
    const handleStorageChange = (e) => {
      // Listen for storage events to detect new calculator data
      if (e.key === 'chatbot_calculator_result' || e.key === null) {
        checkForCalculatorData();
      }
    };
    
    window.addEventListener('storage', handleStorageChange);
    return () => window.removeEventListener('storage', handleStorageChange);
  }, [sendMessage, userId]);
  
  /**
   * Clear the chat history and all related localStorage items
   */
  const clearChat = () => {
    setMessages([]);

    // Clear user-specific localStorage items
    if (userId) {
      localStorage.removeItem(getUserStorageKey(userId, 'messages'));
      localStorage.removeItem(getUserStorageKey(userId, 'context'));

      // Also clear the conversation context on the server
      chatbotService.clearContext(userId).catch(error => {
        console.error('Error clearing context:', error);
      });
    }

    // Clear legacy keys
    localStorage.removeItem('chatbot_messages');
    localStorage.removeItem('last_product_inquiry');
    localStorage.removeItem('chatbot_calculator_result');
    localStorage.removeItem('chatbot_context');
  };
  
  /**
   * Open the chat window
   */
  const openChat = () => {
    // Reset offline status when opening the chat
    chatbotService.resetOfflineStatus();
    setIsOpen(true);
    setIsMinimized(false);
  };
  
  /**
   * Close the chat window
   */
  const closeChat = () => {
    setIsOpen(false);
  };
  
  /**
   * Toggle the minimized state of the chat window
   */
  const toggleMinimize = () => {
    setIsMinimized(prevState => !prevState);
  };
  
  // Context value
  const contextValue = {
    isOpen,
    isMinimized,
    messages,
    isLoading,
    userId,
    sendMessage,
    clearChat,
    openChat,
    closeChat,
    toggleMinimize,
    // Command-based UI
    currentView,
    setCurrentView,
    popularSearches,
    sendCommand,
    handleAction,
    showSearch,
    showCommands,
    showCalculator,
    handleSearch,
    handleCalculate
  };
  
  return (
    <ChatContext.Provider value={contextValue}>
      {children}
    </ChatContext.Provider>
  );
};

/**
 * Custom hook for using the chat context
 * 
 * @returns {Object} Chat context value
 */
export const useChat = () => {
  const context = useContext(ChatContext);
  
  if (!context) {
    throw new Error('useChat must be used within a ChatProvider');
  }
  
  return context;
};

export default ChatContext;
