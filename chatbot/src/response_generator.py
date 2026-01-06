"""
Response generator for the construction materials chatbot.
Generates responses based on detected intents using intents.json.
"""
import json
import random
from pathlib import Path
from typing import Dict, List, Optional, Any


class ResponseGenerator:
    """
    Generates responses based on detected intents.
    Uses intents.json for predefined responses.
    Product-specific responses are handled by the processor.
    """

    def __init__(self):
        """Initialize the response generator"""
        self.intents = self._load_intents()

    def _load_intents(self) -> Dict:
        """Load intents from JSON file"""
        try:
            intents_path = Path(__file__).parent.parent / 'data' / 'intents.json'
            with open(intents_path, 'r', encoding='utf-8') as file:
                return json.load(file)
        except Exception as e:
            print(f"Error loading intents: {e}")
            return self._get_fallback_intents()

    def _get_fallback_intents(self) -> Dict:
        """Return minimal fallback intents if file can't be loaded"""
        return {
            "intents": [
                {
                    "tag": "greeting",
                    "patterns": ["hello", "hi"],
                    "responses": ["Hello! Welcome to Construkt. How can I help you with construction materials today?"]
                },
                {
                    "tag": "farewell",
                    "patterns": ["bye", "goodbye"],
                    "responses": ["Thank you for visiting Construkt. Have a great day!"]
                },
                {
                    "tag": "help",
                    "patterns": ["help"],
                    "responses": ["I can help you find construction materials, calculate quantities, or answer questions about our products."]
                },
                {
                    "tag": "unknown",
                    "patterns": [],
                    "responses": ["I'm not sure I understand. Could you rephrase that?"]
                }
            ]
        }

    def _get_intent_responses(self, intent: str) -> List[str]:
        """
        Get responses for a specific intent.

        Args:
            intent: Intent tag

        Returns:
            List of possible responses
        """
        for intent_data in self.intents.get("intents", []):
            if intent_data.get("tag") == intent:
                return intent_data.get("responses", [])

        # Return unknown responses if intent not found
        return self._get_unknown_responses()

    def _get_unknown_responses(self) -> List[str]:
        """Get responses for unknown intent"""
        for intent_data in self.intents.get("intents", []):
            if intent_data.get("tag") == "unknown":
                return intent_data.get("responses", [])

        return ["I'm not sure I understand. Could you rephrase that?"]

    def generate_response(self, intent: str, message: str, context: Dict = None) -> str:
        """
        Generate a response based on intent and context.

        Args:
            intent: Detected intent
            message: User message
            context: Conversation context

        Returns:
            Response string
        """
        context = context or {}

        # Get possible responses for this intent
        responses = self._get_intent_responses(intent)

        # Choose a random response
        if responses:
            response = random.choice(responses)
        else:
            response = "I'm here to help with construction materials. What would you like to know?"

        return response

    def get_greeting_response(self) -> str:
        """Get a random greeting response"""
        responses = self._get_intent_responses("greeting")
        return random.choice(responses) if responses else "Hello! How can I help you?"

    def get_farewell_response(self) -> str:
        """Get a random farewell response"""
        responses = self._get_intent_responses("farewell")
        return random.choice(responses) if responses else "Goodbye!"

    def get_help_response(self) -> str:
        """Get a help response"""
        responses = self._get_intent_responses("help")
        return random.choice(responses) if responses else "I can help you find construction materials."

    def get_error_response(self) -> str:
        """Get an error response"""
        return "I'm sorry, something went wrong. Please try again."
