import React from 'react';
import { createRoot } from 'react-dom/client';
import ChatbotWidget from './ChatbotWidget';
import './styles/chatbot.css';

class ChatbotWidgetManager {
    constructor() {
        this.root = null;
        this.container = null;
        this.config = {};
    }

    init(config = {}) {
        // Merge with default config
        this.config = {
            apiUrl: 'http://localhost:5000',
            userId: null,
            userName: 'Guest',
            theme: 'light',
            position: 'bottom-right',
            ...config
        };

        // Create container if it doesn't exist
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'chatbot-widget-root';
            document.body.appendChild(this.container);
        }

        // Create React root and render
        if (!this.root) {
            this.root = createRoot(this.container);
        }

        this.root.render(
            <ChatbotWidget config={this.config} />
        );

        console.log('Chatbot Widget initialized', this.config);
    }

    destroy() {
        if (this.root) {
            this.root.unmount();
            this.root = null;
        }
        if (this.container && this.container.parentNode) {
            this.container.parentNode.removeChild(this.container);
            this.container = null;
        }
    }

    open() {
        const event = new CustomEvent('chatbot-open');
        window.dispatchEvent(event);
    }

    close() {
        const event = new CustomEvent('chatbot-close');
        window.dispatchEvent(event);
    }
}

// Create global instance
const widgetInstance = new ChatbotWidgetManager();

// Auto-initialize if config is present
if (typeof window !== 'undefined') {
    window.ChatbotWidget = widgetInstance;

    // Auto-init when DOM is ready if config exists
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            if (window.CHATBOT_CONFIG) {
                widgetInstance.init(window.CHATBOT_CONFIG);
            }
        });
    } else {
        if (window.CHATBOT_CONFIG) {
            widgetInstance.init(window.CHATBOT_CONFIG);
        }
    }
}

export default widgetInstance;
