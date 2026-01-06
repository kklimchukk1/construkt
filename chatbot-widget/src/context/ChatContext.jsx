import React, { createContext, useContext, useState, useCallback, useEffect } from 'react';
import chatbotService from '../services/chatbotService';

const ChatContext = createContext(null);

export const useChat = () => {
    const context = useContext(ChatContext);
    if (!context) {
        throw new Error('useChat must be used within a ChatProvider');
    }
    return context;
};

export const ChatProvider = ({ children, config }) => {
    const [messages, setMessages] = useState([]);
    const [isLoading, setIsLoading] = useState(false);
    const [currentView, setCurrentView] = useState('chat');

    // Load messages from localStorage on mount
    useEffect(() => {
        const storageKey = `chatbot_messages_${config.userId || 'guest'}`;
        const saved = localStorage.getItem(storageKey);
        if (saved) {
            try {
                setMessages(JSON.parse(saved));
            } catch (e) {
                console.error('Failed to load chat history', e);
            }
        }
    }, [config.userId]);

    // Save messages to localStorage when they change
    useEffect(() => {
        const storageKey = `chatbot_messages_${config.userId || 'guest'}`;
        if (messages.length > 0) {
            localStorage.setItem(storageKey, JSON.stringify(messages.slice(-50))); // Keep last 50 messages
        }
    }, [messages, config.userId]);

    const addMessage = useCallback((type, content, data = null) => {
        const message = {
            type,
            content,
            data,
            timestamp: new Date().toISOString()
        };
        setMessages(prev => [...prev, message]);
        return message;
    }, []);

    const sendMessage = useCallback(async (text) => {
        if (!text.trim()) return;

        // Add user message
        addMessage('user', text);
        setIsLoading(true);

        try {
            const response = await chatbotService.sendMessage(text, config.userId, config.apiUrl);

            // Process response and structure data properly
            let data = null;
            let message = response.message || response.response || 'I received your message.';

            // Check if response has properly structured data
            if (response.data) {
                if (response.data.type && response.data.items) {
                    // Already properly formatted
                    data = response.data;
                } else if (response.data.products) {
                    // Convert legacy format to new format
                    data = { type: 'products', items: response.data.products };
                } else if (response.data.categories) {
                    data = { type: 'categories', items: response.data.categories };
                } else {
                    data = response.data;
                }
            }

            addMessage('bot', message, data);
        } catch (error) {
            console.error('Failed to send message:', error);
            addMessage('bot', 'Sorry, I encountered an error. Please try again.');
        } finally {
            setIsLoading(false);
        }
    }, [config.userId, config.apiUrl, addMessage]);

    const sendCommand = useCallback(async (command, params = {}) => {
        setIsLoading(true);

        // Add user action message
        const commandLabels = {
            'SEARCH': `Searching for "${params.keyword}"...`,
            'CATEGORIES': 'Show me all categories',
            'FEATURED': 'Show featured products',
            'CHEAPEST': 'Show cheapest products',
            'CALCULATOR': `Calculate ${params.type}`,
            'HELP': 'I need help'
        };
        addMessage('user', commandLabels[command] || command);

        try {
            const response = await chatbotService.sendCommand(command, params, config.userId, config.apiUrl);

            // Process response based on command type
            let data = null;
            let message = response.message || response.response;

            // Handle different response formats from API
            if (response.type === 'products' && response.items) {
                data = { type: 'products', items: response.items };
                message = message || `Found ${response.items.length} products`;
            } else if (response.products) {
                data = { type: 'products', items: response.products };
                message = message || `Found ${response.products.length} products`;
            } else if (response.type === 'categories' && response.items) {
                data = { type: 'categories', items: response.items };
                message = message || 'Here are our categories:';
            } else if (response.categories) {
                data = { type: 'categories', items: response.categories };
                message = message || 'Here are our categories:';
            } else if (response.result) {
                data = { type: 'calculator', result: response.result };
                message = message || 'Calculation complete:';
            }

            addMessage('bot', message, data);
        } catch (error) {
            console.error('Failed to execute command:', error);
            addMessage('bot', 'Sorry, I could not process that command. Please try again.');
        } finally {
            setIsLoading(false);
        }
    }, [config.userId, config.apiUrl, addMessage]);

    const clearChat = useCallback(() => {
        setMessages([]);
        const storageKey = `chatbot_messages_${config.userId || 'guest'}`;
        localStorage.removeItem(storageKey);
    }, [config.userId]);

    const value = {
        messages,
        isLoading,
        currentView,
        setCurrentView,
        sendMessage,
        sendCommand,
        clearChat,
        addMessage
    };

    return (
        <ChatContext.Provider value={value}>
            {children}
        </ChatContext.Provider>
    );
};

export default ChatContext;
