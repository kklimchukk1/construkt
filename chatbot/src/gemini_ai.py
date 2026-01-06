"""
Gemini AI Integration for Construkt Chatbot.
Uses Gemini 2.5 Flash for intelligent, conversational responses.
"""
import os
import re
import json
from typing import Dict, List, Optional, Any
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

try:
    from google import genai
    GEMINI_AVAILABLE = True
except ImportError:
    GEMINI_AVAILABLE = False
    print("Warning: google-genai not installed. Run: pip install google-genai")


class GeminiAssistant:
    """Gemini AI assistant for enhanced chatbot responses"""

    SYSTEM_PROMPT = """You are a helpful assistant for Construkt, a construction materials marketplace in the USA.
Your role is to help customers find and understand construction materials, answer questions about products,
and provide helpful construction advice.

CRITICAL LANGUAGE REQUIREMENT:
- You MUST respond ONLY in English. This is MANDATORY and NON-NEGOTIABLE.
- Even if the user writes in Russian, Ukrainian, Spanish, or ANY other language, you MUST respond in English ONLY.
- NEVER use Cyrillic characters (Russian, Ukrainian, etc.) in your responses.
- NEVER translate your responses to any language other than English.
- If you receive a message in another language, respond in English anyway.

Guidelines:
- ALWAYS respond in English, no exceptions - this is a US-based English-only site
- Be concise and helpful (under 150 words unless more detail is truly needed)
- Focus on construction materials and building supplies
- Provide practical advice when asked
- Always use USD ($) for prices
- Be knowledgeable about construction terms and techniques
- Reference specific products from our inventory when relevant
- Keep responses conversational and friendly

Current product catalog data:
{product_context}

Recent conversation:
{conversation_history}
"""

    def __init__(self, api_key: str = None):
        self.api_key = api_key or os.getenv('GEMINI_API_KEY')
        self.client = None
        self.enabled = False

        if GEMINI_AVAILABLE and self.api_key:
            try:
                # Set API key in environment if not set
                if 'GEMINI_API_KEY' not in os.environ:
                    os.environ['GEMINI_API_KEY'] = self.api_key

                self.client = genai.Client(api_key=self.api_key)
                self.enabled = True
                print("Gemini AI (2.5 Flash) initialized successfully")
            except Exception as e:
                print(f"Error initializing Gemini: {e}")
                self.enabled = False

    def is_enabled(self) -> bool:
        """Check if Gemini is available"""
        return self.enabled

    def generate_response(
        self,
        user_message: str,
        products: List[Dict],
        current_product: Dict = None,
        conversation_history: List[Dict] = None,
        nlp_context: Dict = None
    ) -> str:
        """Generate a response using Gemini 2.5 Flash"""
        if not self.enabled:
            return None

        try:
            # Build product context
            product_context = self._build_product_context(products, current_product)

            # Build conversation context
            history_str = self._build_history_context(conversation_history or [])

            # Build system context
            system_context = self.SYSTEM_PROMPT.format(
                product_context=product_context,
                conversation_history=history_str
            )

            # Build the user prompt
            prompt_parts = [system_context]

            if nlp_context:
                intent = nlp_context.get('intent', 'unknown')
                nlp_response = nlp_context.get('response', '')
                prompt_parts.append(f"\nOur system detected intent: {intent}")
                if nlp_response:
                    prompt_parts.append(f"System found this information:\n{nlp_response[:500]}")

            prompt_parts.append(f"\nCustomer says: \"{user_message}\"")
            prompt_parts.append("\nProvide a helpful, natural response:")

            full_prompt = '\n'.join(prompt_parts)

            # Generate response using Gemini 2.5 Flash
            response = self.client.models.generate_content(
                model="gemini-2.5-flash",
                contents=full_prompt
            )

            if response and response.text:
                return self._sanitize_response(response.text.strip())
            return None

        except Exception as e:
            print(f"Gemini error: {e}")
            return None

    def _sanitize_response(self, text: str) -> str:
        """Remove any Cyrillic characters from response - English only"""
        if not text:
            return text
        # Remove Cyrillic characters (Russian, Ukrainian, etc.)
        sanitized = re.sub(r'[а-яА-ЯёЁіІїЇєЄґҐ]+', '', text)
        # Clean up any double spaces left behind
        sanitized = re.sub(r'\s{2,}', ' ', sanitized)
        return sanitized.strip()

    def answer_question(
        self,
        question: str,
        products: List[Dict],
        current_product: Dict = None
    ) -> str:
        """Answer a general construction question"""
        if not self.enabled:
            return None

        try:
            # Build minimal context
            context = "You are a construction materials expert helping a customer.\n"

            if current_product:
                context += f"\nCurrent product: {current_product.get('name')} - ${current_product.get('price')}/{current_product.get('unit')}\n"
                dims = current_product.get('dimensions')
                if dims:
                    context += f"Specifications: {dims}\n"

            # Add some available products
            categories = {}
            for p in products[:30]:
                cat = p.get('category_name', 'Other')
                if cat not in categories:
                    categories[cat] = []
                if len(categories[cat]) < 3:
                    categories[cat].append(f"{p.get('name')} (${p.get('price')})")

            context += "\nAvailable products by category:\n"
            for cat, items in categories.items():
                context += f"- {cat}: {', '.join(items)}\n"

            prompt = f"""{context}

Customer question: {question}

IMPORTANT: Respond ONLY in English. Never use Russian, Ukrainian, or any Cyrillic characters.
Provide a helpful, concise answer (under 100 words). If the question is about specific products, reference our inventory."""

            response = self.client.models.generate_content(
                model="gemini-2.5-flash",
                contents=prompt
            )

            if response and response.text:
                return self._sanitize_response(response.text.strip())
            return None

        except Exception as e:
            print(f"Gemini error: {e}")
            return None

    def _build_product_context(self, products: List[Dict], current_product: Dict = None) -> str:
        """Build product context for the prompt"""
        parts = []

        if current_product:
            parts.append(f"Currently discussing: {current_product.get('name')}")
            parts.append(f"  Price: ${current_product.get('price')}/{current_product.get('unit')}")
            parts.append(f"  Stock: {current_product.get('stock_quantity')} available")
            parts.append(f"  Category: {current_product.get('category_name')}")

        # Group products by category
        categories = {}
        for p in products:
            cat = p.get('category_name', 'Other')
            if cat not in categories:
                categories[cat] = []
            categories[cat].append(p)

        parts.append("\nOur inventory by category:")
        for cat, prods in list(categories.items())[:8]:
            parts.append(f"\n{cat}:")
            for p in prods[:4]:
                stock_status = "in stock" if p.get('stock_quantity', 0) > 0 else "out of stock"
                parts.append(f"  - {p.get('name')}: ${p.get('price')}/{p.get('unit')} ({stock_status})")

        return '\n'.join(parts)

    def _build_history_context(self, history: List[Dict]) -> str:
        """Build conversation history for context"""
        if not history:
            return "New conversation"

        lines = []
        for msg in history[-6:]:  # Last 6 messages
            role = "Customer" if msg.get('is_user') else "Assistant"
            content = msg.get('content', '')
            if len(content) > 150:
                content = content[:150] + "..."
            lines.append(f"{role}: {content}")

        return '\n'.join(lines)


# Singleton instance
_gemini_assistant = None


def get_gemini_assistant() -> GeminiAssistant:
    """Get or create Gemini assistant instance"""
    global _gemini_assistant
    if _gemini_assistant is None:
        _gemini_assistant = GeminiAssistant()
    return _gemini_assistant
