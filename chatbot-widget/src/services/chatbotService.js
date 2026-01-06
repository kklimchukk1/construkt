const chatbotService = {
    async sendMessage(message, userId, apiUrl = 'http://localhost:5000') {
        try {
            const response = await fetch(`${apiUrl}/api/chatbot/message`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    message: message,
                    user_id: userId || 'guest'
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Chatbot service error:', error);
            throw error;
        }
    },

    async sendCommand(command, params = {}, userId, apiUrl = 'http://localhost:5000') {
        try {
            const response = await fetch(`${apiUrl}/api/chatbot/command`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    command: command,
                    params: params,
                    user_id: userId || 'guest'
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Chatbot command error:', error);
            throw error;
        }
    },

    async getCategories(apiUrl = 'http://localhost:5000') {
        try {
            const response = await fetch(`${apiUrl}/api/categories`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error('Get categories error:', error);
            throw error;
        }
    },

    async searchProducts(keyword, apiUrl = 'http://localhost:5000') {
        try {
            const response = await fetch(`${apiUrl}/api/products/search?q=${encodeURIComponent(keyword)}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error('Search products error:', error);
            throw error;
        }
    }
};

export default chatbotService;
