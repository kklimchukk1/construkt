"""
Unified Conversation State Manager for the Construkt chatbot.
Single source of truth for all conversation context and state.
"""
import json
import time
import os
from datetime import datetime
from pathlib import Path
from typing import Optional, Dict, Any, List
from threading import Lock


class ConversationState:
    """
    Represents the complete state of a conversation with a user.
    Tracks current product, conversation history, calculator state, and more.
    """

    def __init__(self, user_id: str):
        self.user_id = user_id
        self.created_at = time.time()
        self.last_access = time.time()

        # Current product context
        self.current_product_id: Optional[int] = None
        self.current_product: Optional[Dict] = None

        # Conversation history (last N messages)
        self.conversation_history: List[Dict] = []
        self.max_history_size = 50

        # Intent tracking
        self.last_intent: Optional[str] = None
        self.last_confidence: float = 0.0
        self.intent_history: List[str] = []

        # Calculator state
        self.calculator_dimensions: Dict = {}
        self.calculator_material_type: Optional[str] = None
        self.calculator_state: Optional[str] = None
        self.calculator_last_result: Optional[Dict] = None

        # Topic tracking - what we're currently discussing
        self.current_topic: Optional[str] = None  # 'product', 'calculator', 'general'
        self.topic_product_name: Optional[str] = None  # e.g., "nails", "bricks"

        # Follow-up state
        self.awaiting_response: bool = False
        self.awaiting_response_type: Optional[str] = None  # 'more_info', 'dimensions', 'confirmation'

    def to_dict(self) -> Dict:
        """Convert state to dictionary for serialization"""
        return {
            'user_id': self.user_id,
            'created_at': self.created_at,
            'last_access': self.last_access,
            'current_product_id': self.current_product_id,
            'current_product': self.current_product,
            'conversation_history': self.conversation_history,
            'last_intent': self.last_intent,
            'last_confidence': self.last_confidence,
            'intent_history': self.intent_history[-10:],  # Keep last 10
            'calculator_dimensions': self.calculator_dimensions,
            'calculator_material_type': self.calculator_material_type,
            'calculator_state': self.calculator_state,
            'calculator_last_result': self.calculator_last_result,
            'current_topic': self.current_topic,
            'topic_product_name': self.topic_product_name,
            'awaiting_response': self.awaiting_response,
            'awaiting_response_type': self.awaiting_response_type
        }

    @classmethod
    def from_dict(cls, data: Dict) -> 'ConversationState':
        """Create state from dictionary"""
        state = cls(data.get('user_id', ''))
        state.created_at = data.get('created_at', time.time())
        state.last_access = data.get('last_access', time.time())
        state.current_product_id = data.get('current_product_id')
        state.current_product = data.get('current_product')
        state.conversation_history = data.get('conversation_history', [])
        state.last_intent = data.get('last_intent')
        state.last_confidence = data.get('last_confidence', 0.0)
        state.intent_history = data.get('intent_history', [])
        state.calculator_dimensions = data.get('calculator_dimensions', {})
        state.calculator_material_type = data.get('calculator_material_type')
        state.calculator_state = data.get('calculator_state')
        state.calculator_last_result = data.get('calculator_last_result')
        state.current_topic = data.get('current_topic')
        state.topic_product_name = data.get('topic_product_name')
        state.awaiting_response = data.get('awaiting_response', False)
        state.awaiting_response_type = data.get('awaiting_response_type')
        return state

    def add_message(self, text: str, is_user: bool, intent: str = None, data: Dict = None):
        """Add a message to conversation history"""
        message = {
            'text': text,
            'is_user': is_user,
            'timestamp': datetime.now().isoformat(),
            'intent': intent,
            'data': data
        }
        self.conversation_history.append(message)

        # Trim history if too long
        if len(self.conversation_history) > self.max_history_size:
            self.conversation_history = self.conversation_history[-self.max_history_size:]

        self.last_access = time.time()

    def set_current_product(self, product: Dict):
        """Set the current product context"""
        self.current_product = product
        self.current_product_id = product.get('id')
        self.current_topic = 'product'
        self.topic_product_name = product.get('name', '').lower()
        self.awaiting_response = True
        self.awaiting_response_type = 'more_info'
        self.last_access = time.time()

    def set_intent(self, intent: str, confidence: float):
        """Update intent tracking"""
        self.last_intent = intent
        self.last_confidence = confidence
        self.intent_history.append(intent)
        if len(self.intent_history) > 20:
            self.intent_history = self.intent_history[-20:]
        self.last_access = time.time()

    def get_context_summary(self) -> str:
        """Get a summary of current context for debugging/logging"""
        parts = []
        if self.current_product:
            parts.append(f"Product: {self.current_product.get('name')}")
        if self.current_topic:
            parts.append(f"Topic: {self.current_topic}")
        if self.last_intent:
            parts.append(f"Last intent: {self.last_intent}")
        if self.calculator_state:
            parts.append(f"Calculator: {self.calculator_state}")
        return " | ".join(parts) if parts else "Empty context"

    def get_recent_messages(self, count: int = 5) -> List[Dict]:
        """Get the most recent messages"""
        return self.conversation_history[-count:] if self.conversation_history else []

    def is_discussing_product(self, product_keywords: List[str] = None) -> bool:
        """Check if we're currently discussing a product"""
        if self.current_topic != 'product':
            return False
        if not product_keywords:
            return self.current_product is not None

        # Check if any keyword matches current product
        if self.topic_product_name:
            for keyword in product_keywords:
                if keyword.lower() in self.topic_product_name:
                    return True
        return False

    def clear_product_context(self):
        """Clear the current product context"""
        self.current_product = None
        self.current_product_id = None
        self.topic_product_name = None
        if self.current_topic == 'product':
            self.current_topic = None
        self.awaiting_response = False
        self.awaiting_response_type = None

    def clear_calculator_context(self):
        """Clear calculator state"""
        self.calculator_dimensions = {}
        self.calculator_material_type = None
        self.calculator_state = None
        self.calculator_last_result = None
        if self.current_topic == 'calculator':
            self.current_topic = None


class ConversationStateManager:
    """
    Singleton manager for all conversation states.
    Provides thread-safe access to conversation contexts.
    """

    _instance = None
    _lock = Lock()

    def __new__(cls):
        if cls._instance is None:
            with cls._lock:
                if cls._instance is None:
                    cls._instance = super().__new__(cls)
                    cls._instance._initialized = False
        return cls._instance

    def __init__(self, context_timeout: int = 3600, storage_dir: str = None):
        if self._initialized:
            return

        self._states: Dict[str, ConversationState] = {}
        self._state_locks: Dict[str, Lock] = {}
        self._global_lock = Lock()
        self.context_timeout = context_timeout  # 1 hour default

        # Storage directory for persistent contexts
        if storage_dir:
            self.storage_dir = Path(storage_dir)
        else:
            self.storage_dir = Path(__file__).parent.parent / 'data' / 'contexts'

        os.makedirs(self.storage_dir, exist_ok=True)
        self._initialized = True

        print(f"ConversationStateManager initialized. Storage: {self.storage_dir}")

    def _get_state_lock(self, user_id: str) -> Lock:
        """Get or create a lock for a specific user's state"""
        with self._global_lock:
            if user_id not in self._state_locks:
                self._state_locks[user_id] = Lock()
            return self._state_locks[user_id]

    def get_state(self, user_id: str) -> ConversationState:
        """Get or create conversation state for a user"""
        lock = self._get_state_lock(user_id)

        with lock:
            # Check memory first
            if user_id in self._states:
                state = self._states[user_id]
                state.last_access = time.time()
                return state

            # Try to load from file
            state = self._load_from_file(user_id)
            if state:
                self._states[user_id] = state
                return state

            # Create new state
            state = ConversationState(user_id)
            self._states[user_id] = state
            return state

    def save_state(self, user_id: str, state: ConversationState = None):
        """Save state to memory and file"""
        lock = self._get_state_lock(user_id)

        with lock:
            if state is None:
                state = self._states.get(user_id)

            if state:
                state.last_access = time.time()
                self._states[user_id] = state
                self._save_to_file(user_id, state)

    def clear_state(self, user_id: str):
        """Clear state for a user"""
        lock = self._get_state_lock(user_id)

        with lock:
            if user_id in self._states:
                del self._states[user_id]

            # Remove file
            file_path = self.storage_dir / f"{user_id}.json"
            if file_path.exists():
                try:
                    os.remove(file_path)
                except Exception as e:
                    print(f"Error removing state file: {e}")

    def cleanup_expired(self):
        """Remove expired states"""
        current_time = time.time()
        expired = []

        with self._global_lock:
            for user_id, state in self._states.items():
                if current_time - state.last_access > self.context_timeout:
                    expired.append(user_id)

        for user_id in expired:
            self.clear_state(user_id)

        if expired:
            print(f"Cleaned up {len(expired)} expired conversation states")

    def _save_to_file(self, user_id: str, state: ConversationState):
        """Save state to JSON file"""
        try:
            file_path = self.storage_dir / f"{user_id}.json"

            # Custom encoder for Decimal and other types
            class StateEncoder(json.JSONEncoder):
                def default(self, obj):
                    from decimal import Decimal
                    if isinstance(obj, Decimal):
                        return float(obj)
                    return super().default(obj)

            with open(file_path, 'w', encoding='utf-8') as f:
                json.dump(state.to_dict(), f, cls=StateEncoder, ensure_ascii=False, indent=2)
        except Exception as e:
            print(f"Error saving state to file: {e}")

    def _load_from_file(self, user_id: str) -> Optional[ConversationState]:
        """Load state from JSON file"""
        try:
            file_path = self.storage_dir / f"{user_id}.json"
            if file_path.exists():
                with open(file_path, 'r', encoding='utf-8') as f:
                    data = json.load(f)
                    return ConversationState.from_dict(data)
        except Exception as e:
            print(f"Error loading state from file: {e}")
        return None

    def get_conversation_history(self, user_id: str, limit: int = None) -> List[Dict]:
        """Get conversation history for a user"""
        state = self.get_state(user_id)
        history = state.conversation_history

        if limit and limit > 0:
            return history[-limit:]
        return history

    # Convenience methods that mirror the old ContextManager API
    def get_context(self, user_id: str) -> Dict:
        """Get context as dictionary (backward compatibility)"""
        state = self.get_state(user_id)
        return state.to_dict()

    def set_context(self, user_id: str, key: str, value: Any):
        """Set a context value (backward compatibility)"""
        state = self.get_state(user_id)

        if hasattr(state, key):
            setattr(state, key, value)
        else:
            # For unknown keys, store in a generic way
            if not hasattr(state, '_extra'):
                state._extra = {}
            state._extra[key] = value

        self.save_state(user_id, state)

    def add_message_to_history(self, user_id: str, message: str, is_user: bool = True,
                                intent: str = None, data: Dict = None):
        """Add message to history (backward compatibility)"""
        state = self.get_state(user_id)
        state.add_message(message, is_user, intent, data)
        self.save_state(user_id, state)


# Global instance
_state_manager: Optional[ConversationStateManager] = None


def get_state_manager() -> ConversationStateManager:
    """Get the global ConversationStateManager instance"""
    global _state_manager
    if _state_manager is None:
        _state_manager = ConversationStateManager()
    return _state_manager
