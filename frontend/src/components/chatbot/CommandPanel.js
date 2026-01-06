import React from 'react';
import { Button } from 'react-bootstrap';
import './CommandPanel.css';

/**
 * CommandPanel - Main command buttons for the chatbot
 * Replaces free-form text input with structured commands
 */
const CommandPanel = ({ onCommand, isLoading, showBackButton, onBack }) => {
  const mainCommands = [
    { id: 'SEARCH', icon: '🔍', label: 'Search' },
    { id: 'CATEGORIES', icon: '📦', label: 'Categories' },
    { id: 'FEATURED', icon: '⭐', label: 'Popular' },
    { id: 'CHEAPEST', icon: '💰', label: 'Budget' },
    { id: 'CALCULATOR', icon: '📐', label: 'Calculator' },
    { id: 'HELP', icon: '❓', label: 'Help' }
  ];

  return (
    <div className="command-panel">
      {showBackButton && (
        <Button
          variant="link"
          className="back-button"
          onClick={onBack}
          disabled={isLoading}
        >
          ← Back
        </Button>
      )}
      <div className="command-buttons">
        {mainCommands.map(cmd => (
          <button
            key={cmd.id}
            onClick={() => onCommand(cmd.id)}
            className="command-button"
            disabled={isLoading}
            title={cmd.label}
          >
            <span className="command-icon">{cmd.icon}</span>
            <span className="command-label">{cmd.label}</span>
          </button>
        ))}
      </div>
    </div>
  );
};

export default CommandPanel;
