import React, { useState } from 'react';

const CommandPanel = ({ onCommand }) => {
    const [activePanel, setActivePanel] = useState(null);
    const [searchQuery, setSearchQuery] = useState('');

    const commands = [
        { id: 'SEARCH', label: 'Search', icon: 'search' },
        { id: 'CATEGORIES', label: 'Categories', icon: 'grid' },
        { id: 'FEATURED', label: 'Featured', icon: 'star' },
        { id: 'CHEAPEST', label: 'Cheapest', icon: 'dollar' },
        { id: 'CALCULATOR', label: 'Calculator', icon: 'calc' },
        { id: 'HELP', label: 'Help', icon: 'help' },
    ];

    const getIcon = (iconName) => {
        const icons = {
            search: <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>,
            grid: <path d="M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z"/>,
            star: <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>,
            dollar: <path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>,
            calc: <path d="M4 4h16v16H4zM4 9h16M9 4v16"/>,
            help: <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10zm0-6v-4m0-4h.01"/>,
        };
        return (
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                {icons[iconName]}
            </svg>
        );
    };

    const handleCommand = (command) => {
        if (command === 'SEARCH') {
            setActivePanel(activePanel === 'search' ? null : 'search');
        } else if (command === 'CALCULATOR') {
            setActivePanel(activePanel === 'calculator' ? null : 'calculator');
        } else {
            setActivePanel(null);
            onCommand(command);
        }
    };

    const handleSearch = (e) => {
        e.preventDefault();
        if (searchQuery.trim()) {
            onCommand('SEARCH', { keyword: searchQuery.trim() });
            setSearchQuery('');
            setActivePanel(null);
        }
    };

    const handleCalculator = (calcType, values) => {
        onCommand('CALCULATOR', { type: calcType, ...values });
        setActivePanel(null);
    };

    return (
        <div className="chatbot-commands">
            <div className="chatbot-commands__buttons">
                {commands.map((cmd) => (
                    <button
                        key={cmd.id}
                        className={`chatbot-commands__button ${
                            (cmd.id === 'SEARCH' && activePanel === 'search') ||
                            (cmd.id === 'CALCULATOR' && activePanel === 'calculator')
                                ? 'active'
                                : ''
                        }`}
                        onClick={() => handleCommand(cmd.id)}
                    >
                        {getIcon(cmd.icon)}
                        <span>{cmd.label}</span>
                    </button>
                ))}
            </div>

            {/* Search Panel */}
            {activePanel === 'search' && (
                <div className="chatbot-commands__panel">
                    <form onSubmit={handleSearch} className="chatbot-search-form">
                        <input
                            type="text"
                            placeholder="Search products..."
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            autoFocus
                        />
                        <button type="submit">Search</button>
                    </form>
                </div>
            )}

            {/* Calculator Panel */}
            {activePanel === 'calculator' && (
                <div className="chatbot-commands__panel">
                    <div className="chatbot-calculator-panel">
                        <div className="chatbot-calculator-panel__tabs">
                            <button
                                className="active"
                                onClick={() => {
                                    const length = prompt('Enter length (m):');
                                    const width = prompt('Enter width (m):');
                                    if (length && width) {
                                        handleCalculator('area', {
                                            length: parseFloat(length),
                                            width: parseFloat(width)
                                        });
                                    }
                                }}
                            >
                                Area (m²)
                            </button>
                            <button
                                onClick={() => {
                                    const length = prompt('Enter length (m):');
                                    const width = prompt('Enter width (m):');
                                    const depth = prompt('Enter depth (m):');
                                    if (length && width && depth) {
                                        handleCalculator('volume', {
                                            length: parseFloat(length),
                                            width: parseFloat(width),
                                            depth: parseFloat(depth)
                                        });
                                    }
                                }}
                            >
                                Volume (m³)
                            </button>
                        </div>
                        <p className="chatbot-calculator-panel__hint">
                            Click a button to calculate
                        </p>
                    </div>
                </div>
            )}
        </div>
    );
};

export default CommandPanel;
