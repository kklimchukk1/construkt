import React from 'react';
import { Button } from 'react-bootstrap';
import './ActionButtons.css';

/**
 * ActionButtons - Displays action buttons returned by the bot
 * Each action triggers a command
 */
const ActionButtons = ({ actions, onAction, isLoading }) => {
  if (!actions || actions.length === 0) {
    return null;
  }

  return (
    <div className="action-buttons">
      {actions.map((action, index) => (
        <Button
          key={index}
          variant="outline-secondary"
          size="sm"
          onClick={() => onAction(action.type, action.params)}
          disabled={isLoading}
          className="action-button"
        >
          {action.label}
        </Button>
      ))}
    </div>
  );
};

export default ActionButtons;
