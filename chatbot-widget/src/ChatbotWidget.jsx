import React, { useState, useEffect, useRef, useCallback } from 'react';
import ChatButton from './components/ChatButton';
import ChatWindow from './components/ChatWindow';
import { ChatProvider } from './context/ChatContext';

const ChatbotWidget = ({ config }) => {
    const [isOpen, setIsOpen] = useState(false);
    const [isMinimized, setIsMinimized] = useState(false);

    useEffect(() => {
        // Listen for external open/close commands
        const handleOpen = () => setIsOpen(true);
        const handleClose = () => setIsOpen(false);

        window.addEventListener('chatbot-open', handleOpen);
        window.addEventListener('chatbot-close', handleClose);

        return () => {
            window.removeEventListener('chatbot-open', handleOpen);
            window.removeEventListener('chatbot-close', handleClose);
        };
    }, []);

    const toggleChat = useCallback(() => {
        if (isMinimized) {
            setIsMinimized(false);
        } else {
            setIsOpen(!isOpen);
        }
    }, [isOpen, isMinimized]);

    const handleMinimize = useCallback(() => {
        setIsMinimized(true);
    }, []);

    const handleClose = useCallback(() => {
        setIsOpen(false);
        setIsMinimized(false);
    }, []);

    const positionClass = `chatbot-widget--${config.position || 'bottom-right'}`;
    const themeClass = `chatbot-widget--${config.theme || 'light'}`;

    return (
        <ChatProvider config={config}>
            <div className={`chatbot-widget ${positionClass} ${themeClass}`}>
                {(isOpen || isMinimized) && (
                    <ChatWindow
                        isMinimized={isMinimized}
                        onMinimize={handleMinimize}
                        onClose={handleClose}
                        onExpand={() => setIsMinimized(false)}
                        config={config}
                    />
                )}
                <ChatButton
                    isOpen={isOpen && !isMinimized}
                    onClick={toggleChat}
                />
            </div>
        </ChatProvider>
    );
};

export default ChatbotWidget;
