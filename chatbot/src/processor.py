"""
Context-aware NLP processor for the construction materials chatbot.
Understands conversation context and handles contextual questions.
"""
import json
import re
import time
from pathlib import Path
from typing import Dict, List, Optional, Tuple, Any

from src.conversation_state import get_state_manager, ConversationState
from src.intents.store_info import handle_store_info, is_store_info_query
from utils.database import DatabaseConnector


class ProductCache:
    """Simple cache for products to avoid repeated DB queries"""

    def __init__(self, ttl: int = 300):  # 5 minutes TTL
        self.ttl = ttl
        self._cache: Dict = {}
        self._last_update: float = 0

    def get_products(self, db: DatabaseConnector, force_refresh: bool = False) -> List[Dict]:
        """Get products from cache or database"""
        current_time = time.time()

        if force_refresh or not self._cache or (current_time - self._last_update > self.ttl):
            products = db.get_products(limit=200)
            if products:
                self._cache = {
                    'products': products,
                    'by_id': {p['id']: p for p in products},
                    'by_name_lower': {p['name'].lower(): p for p in products}
                }
                self._last_update = current_time
                print(f"Product cache updated: {len(products)} products")

        return self._cache.get('products', [])

    def get_product_by_id(self, product_id: int) -> Optional[Dict]:
        """Get a product by ID from cache"""
        return self._cache.get('by_id', {}).get(product_id)

    def get_product_by_name(self, name: str) -> Optional[Dict]:
        """Get a product by name from cache"""
        return self._cache.get('by_name_lower', {}).get(name.lower())

    def find_products_by_keyword(self, keyword: str) -> List[Dict]:
        """Find products matching a keyword"""
        keyword_lower = keyword.lower()
        results = []
        for product in self._cache.get('products', []):
            name = product.get('name', '').lower()
            desc = product.get('description', '').lower()
            if keyword_lower in name or keyword_lower in desc:
                results.append(product)
        return results


class ContextualQuestionDetector:
    """Detects contextual questions that refer to previous conversation"""

    # Words that indicate the question is about something already discussed
    CONTEXTUAL_INDICATORS = [
        'it', 'this', 'that', 'them', 'these', 'those',
        'its', 'their', 'the same', 'same one',
        'the product', 'the item', 'that one', 'this one'
    ]

    # Question words for product attributes
    ATTRIBUTE_QUESTIONS = {
        'material': ['what material', 'made of', 'what is it made', 'composition'],
        'quantity': ['how many', 'quantity', 'per pack', 'in package', 'in box', 'per unit'],
        'size': ['size', 'dimensions', 'how big', 'how long', 'how wide', 'length', 'width', 'height'],
        'price': ['price', 'cost', 'how much'],
        'weight': ['weight', 'how heavy'],
        'color': ['color', 'colour', 'what color'],
        'stock': ['in stock', 'available', 'availability', 'how many left'],
        'supplier': ['supplier', 'manufacturer', 'who makes', 'brand']
    }

    # Affirmative responses
    AFFIRMATIVE = [
        'yes', 'yeah', 'yep', 'sure', 'ok', 'okay', 'please', 'go ahead',
        'tell me more', 'more info', 'more information', 'yes please'
    ]

    @classmethod
    def is_contextual_question(cls, message: str) -> bool:
        """Check if message is a contextual question about previous topic"""
        message_lower = message.lower().strip()

        # Check for contextual indicators
        for indicator in cls.CONTEXTUAL_INDICATORS:
            if indicator in message_lower:
                return True

        # Check for attribute questions without product name
        for attr_type, patterns in cls.ATTRIBUTE_QUESTIONS.items():
            for pattern in patterns:
                if pattern in message_lower:
                    # It's an attribute question - check if product is mentioned
                    # If no product mentioned, it's contextual
                    return True

        return False

    @classmethod
    def get_attribute_type(cls, message: str) -> Optional[str]:
        """Determine what attribute the user is asking about"""
        message_lower = message.lower()

        for attr_type, patterns in cls.ATTRIBUTE_QUESTIONS.items():
            for pattern in patterns:
                if pattern in message_lower:
                    return attr_type

        return None

    @classmethod
    def is_affirmative(cls, message: str) -> bool:
        """Check if message is an affirmative response"""
        message_lower = message.lower().strip()

        # Exact match
        if message_lower in cls.AFFIRMATIVE:
            return True

        # Partial match for longer phrases
        for phrase in cls.AFFIRMATIVE:
            if len(phrase) > 3 and phrase in message_lower:
                return True

        return False


class MessageProcessor:
    """
    Context-aware message processor.
    Uses ConversationStateManager for unified context management.
    """

    def __init__(self):
        self.intents = self._load_intents()
        self.db = DatabaseConnector()
        self.product_cache = ProductCache()
        self.state_manager = get_state_manager()
        self.context_detector = ContextualQuestionDetector()

    def _load_intents(self) -> Dict:
        """Load intents from JSON file"""
        try:
            intents_path = Path(__file__).parent.parent / 'data' / 'intents.json'
            with open(intents_path, 'r', encoding='utf-8') as file:
                return json.load(file)
        except Exception as e:
            print(f"Error loading intents: {e}")
            return {"intents": []}

    def reload_intents(self):
        """Reload intents from file"""
        self.intents = self._load_intents()

    def _preprocess_text(self, text: str) -> str:
        """Preprocess text for matching"""
        text = text.lower()
        text = re.sub(r'[^\w\s\u0400-\u04FF]', '', text)  # Keep Cyrillic
        return text

    def process(self, message: str, user_id: str) -> Dict[str, Any]:
        """
        Process a message with full context awareness.
        Returns intent, confidence, and optional response.
        """
        # Get conversation state
        state = self.state_manager.get_state(user_id)

        print(f"\n=== Processing message for user {user_id} ===")
        print(f"Message: {message}")
        print(f"Current context: {state.get_context_summary()}")

        # Add user message to history
        state.add_message(message, is_user=True)

        # Refresh product cache
        self.product_cache.get_products(self.db)

        # 0. Check for store information queries (hours, location, delivery, contacts)
        if is_store_info_query(message):
            store_response = handle_store_info(message)
            if store_response:
                state.set_intent('store_info', 1.0)
                state.current_topic = 'store_info'
                state.add_message(store_response, is_user=False)
                self.state_manager.save_state(user_id, state)
                return {
                    'intent': 'store_info',
                    'confidence': 1.0,
                    'response': store_response,
                    'state': state
                }

        # 1. Check for affirmative response (yes, tell me more, etc.)
        if self.context_detector.is_affirmative(message):
            result = self._handle_affirmative(state, message)
            if result:
                self.state_manager.save_state(user_id, state)
                return result

        # 2. Check for contextual question about current product
        if state.current_product and self.context_detector.is_contextual_question(message):
            result = self._handle_contextual_question(state, message)
            if result:
                self.state_manager.save_state(user_id, state)
                return result

        # 3. Try to detect intent and find products
        intent, confidence = self._detect_intent(message, state)

        # 4. Check for product search
        product_match = self._find_product_in_message(message)
        if product_match:
            state.set_current_product(product_match)
            state.set_intent('product_inquiry', 1.0)

            response = self._generate_product_response(product_match)
            self.state_manager.save_state(user_id, state)

            return {
                'intent': 'product_inquiry',
                'confidence': 1.0,
                'response': response,
                'product': product_match
            }

        # 5. Handle calculator intent
        if intent == 'calculator_inquiry':
            state.current_topic = 'calculator'
            state.set_intent(intent, confidence)
            self.state_manager.save_state(user_id, state)

            return {
                'intent': intent,
                'confidence': confidence,
                'response': None,  # Let calculator handler generate response
                'state': state
            }

        # 6. Update state and return
        state.set_intent(intent, confidence)
        self.state_manager.save_state(user_id, state)

        return {
            'intent': intent,
            'confidence': confidence,
            'response': None,
            'state': state
        }

    def _handle_affirmative(self, state: ConversationState, message: str) -> Optional[Dict]:
        """Handle affirmative responses like 'yes', 'tell me more'"""
        if not state.current_product:
            return None

        print(f"Handling affirmative for product: {state.current_product.get('name')}")

        # User wants more info about current product
        product = state.current_product

        # Get full product details from DB
        product_details = self.db.get_products(product_id=product['id'])
        if product_details:
            product = product_details[0]
            state.current_product = product

        response = self._generate_detailed_product_response(product)
        state.awaiting_response = False
        state.set_intent('follow_up', 1.0)

        return {
            'intent': 'follow_up',
            'confidence': 1.0,
            'response': response,
            'product': product
        }

    def _handle_contextual_question(self, state: ConversationState, message: str) -> Optional[Dict]:
        """Handle questions about the current product context"""
        product = state.current_product
        if not product:
            return None

        attr_type = self.context_detector.get_attribute_type(message)
        print(f"Contextual question about {attr_type} for product: {product.get('name')}")

        # Get fresh product data
        product_details = self.db.get_products(product_id=product['id'])
        if product_details:
            product = product_details[0]
            state.current_product = product

        response = self._generate_attribute_response(product, attr_type, message)
        state.set_intent('product_attribute', 1.0)

        return {
            'intent': 'product_attribute',
            'confidence': 1.0,
            'response': response,
            'product': product,
            'attribute': attr_type
        }

    def _find_product_in_message(self, message: str) -> Optional[Dict]:
        """Find a product mentioned in the message"""
        message_lower = self._preprocess_text(message)
        message_words = set(message_lower.split())
        products = self.product_cache.get_products(self.db)

        best_match = None
        best_score = 0

        # Key product words - core terms that identify product type
        key_words = {'nails', 'screws', 'bolts', 'cement', 'bricks', 'blocks', 'hammer', 'tape',
                     'drill', 'paint', 'wood', 'lumber', 'tile', 'pipe', 'wire', 'drywall'}

        for product in products:
            product_name = self._preprocess_text(product.get('name', ''))
            product_desc = self._preprocess_text(product.get('description', ''))
            product_words = set(product_name.split())

            # Exact name match
            if product_name in message_lower:
                return product

            # Check if key product word matches
            for word in message_words:
                if len(word) >= 3:
                    # Direct word match in product name
                    if word in product_name:
                        # Boost score for key product words
                        score = 0.8 if word in key_words else 0.6
                        if score > best_score:
                            best_score = score
                            best_match = product
                            break
                    # Check description too
                    elif word in product_desc:
                        score = 0.5
                        if score > best_score:
                            best_score = score
                            best_match = product

            # Multiple word matching for more specific queries
            matching_words = [w for w in product_words if len(w) >= 3 and w in message_words]
            if len(matching_words) >= 2:
                # Multiple matches = higher confidence
                score = 0.9
                if score > best_score:
                    best_score = score
                    best_match = product

        # Return match if confidence is reasonable
        if best_score >= 0.5:
            print(f"Found product match: {best_match.get('name')} (score: {best_score})")
            return best_match

        return None

    def _detect_intent(self, message: str, state: ConversationState) -> Tuple[str, float]:
        """Detect intent from message and context"""
        processed = self._preprocess_text(message)
        words = processed.split()

        # Check for product-related keywords
        product_keywords = ['need', 'want', 'looking', 'search', 'find', 'have', 'sell', 'buy']
        if any(kw in words for kw in product_keywords):
            return 'product_inquiry', 0.8

        # Check for calculator keywords
        calc_keywords = ['calculate', 'calc', 'how much', 'how many', 'estimate']
        if any(kw in processed for kw in calc_keywords):
            return 'calculator_inquiry', 0.9

        # Check dimension patterns
        if re.search(r'\d+\s*(?:m|meter|ft|feet|inch)', processed):
            return 'calculator_inquiry', 0.85

        # Pattern matching from intents.json
        best_intent = 'unknown'
        best_score = 0

        for intent in self.intents.get('intents', []):
            score = 0
            for pattern in intent.get('patterns', []):
                pattern_lower = self._preprocess_text(pattern)
                if pattern_lower == processed:
                    score += 2
                elif pattern_lower in processed:
                    score += 1
                else:
                    pattern_words = set(pattern_lower.split())
                    message_words = set(processed.split())
                    common = pattern_words & message_words
                    if common:
                        score += 0.5 * len(common) / len(pattern_words)

            if score > best_score:
                best_score = score
                best_intent = intent.get('tag', 'unknown')

        confidence = min(best_score / 3, 1.0) if best_score > 0.3 else 0.0
        return best_intent, confidence

    def _generate_product_response(self, product: Dict) -> str:
        """Generate initial response for a product inquiry"""
        name = product.get('name', 'Product')
        price = product.get('price', 0)
        unit = product.get('unit', 'unit')
        description = product.get('description', '')

        response = f"We have {name} available at {price} per {unit}."
        if description:
            response += f" {description}"
        response += " Would you like more details about this product?"

        return response

    def _generate_detailed_product_response(self, product: Dict) -> str:
        """Generate detailed response with all product info"""
        name = product.get('name', 'Product')
        price = product.get('price', 0)
        unit = product.get('unit', 'unit')
        category = product.get('category_name', 'General')
        supplier = product.get('supplier_name', 'Unknown')
        stock = product.get('stock_quantity', 'N/A')
        description = product.get('description', '')

        response = f"Here's detailed information about {name}:\n\n"
        response += f"* Price: {price} per {unit}\n"
        response += f"* Category: {category}\n"
        response += f"* Supplier: {supplier}\n"
        response += f"* In stock: {stock} {unit}s\n"

        if description:
            response += f"* Description: {description}\n"

        # Try to parse dimensions if available
        if product.get('dimensions'):
            try:
                dims = json.loads(product['dimensions']) if isinstance(product['dimensions'], str) else product['dimensions']
                if dims:
                    response += f"* Specifications: {json.dumps(dims, ensure_ascii=False)}\n"
            except:
                pass

        response += "\nWould you like to calculate how much you need for your project, or ask about other products?"

        return response

    def _generate_attribute_response(self, product: Dict, attr_type: Optional[str], message: str) -> str:
        """Generate response for a specific product attribute question"""
        name = product.get('name', 'This product')

        if attr_type == 'price':
            price = product.get('price', 0)
            unit = product.get('unit', 'unit')
            return f"{name} costs {price} per {unit}."

        elif attr_type == 'size' or attr_type == 'dimensions':
            dims = product.get('dimensions')
            if dims:
                try:
                    dims_data = json.loads(dims) if isinstance(dims, str) else dims
                    return f"The dimensions of {name} are: {json.dumps(dims_data, ensure_ascii=False)}"
                except:
                    pass
            return f"I don't have specific size information for {name}. Please contact the supplier for details."

        elif attr_type == 'quantity':
            # This would typically come from product dimensions/specs
            dims = product.get('dimensions')
            if dims:
                try:
                    dims_data = json.loads(dims) if isinstance(dims, str) else dims
                    if 'quantity_per_pack' in dims_data:
                        return f"{name} comes with {dims_data['quantity_per_pack']} units per package."
                    if 'per_pack' in dims_data:
                        return f"{name} comes {dims_data['per_pack']} units per package."
                    if 'quantity' in dims_data:
                        return f"{name} comes {dims_data['quantity']} units per package."
                except:
                    pass
            unit = product.get('unit', 'unit')
            return f"{name} is sold by the {unit}. For bulk packaging information, please contact the supplier."

        elif attr_type == 'material':
            dims = product.get('dimensions')
            if dims:
                try:
                    dims_data = json.loads(dims) if isinstance(dims, str) else dims
                    if 'material' in dims_data:
                        return f"{name} is made of {dims_data['material']}."
                except:
                    pass
            description = product.get('description', '')
            return f"Here's the description of {name}: {description}. For detailed material composition, please check with the supplier."

        elif attr_type == 'stock':
            stock = product.get('stock_quantity', 'unknown')
            unit = product.get('unit', 'unit')
            if stock and stock != 'unknown':
                return f"We currently have {stock} {unit}s of {name} in stock."
            return f"Please contact us for current availability of {name}."

        elif attr_type == 'supplier':
            supplier = product.get('supplier_name', 'Unknown')
            return f"{name} is supplied by {supplier}."

        elif attr_type == 'weight':
            dims = product.get('dimensions')
            if dims:
                try:
                    dims_data = json.loads(dims) if isinstance(dims, str) else dims
                    if 'weight' in dims_data:
                        return f"{name} weighs {dims_data['weight']}."
                except:
                    pass
            return f"I don't have weight information for {name}. Please check the product specifications."

        elif attr_type == 'color':
            dims = product.get('dimensions')
            if dims:
                try:
                    dims_data = json.loads(dims) if isinstance(dims, str) else dims
                    if 'color' in dims_data:
                        return f"{name} is available in {dims_data['color']}."
                except:
                    pass
            description = product.get('description', '')
            return f"Color information for {name}: {description}"

        # Generic response
        return self._generate_detailed_product_response(product)

    def get_products(self, **kwargs) -> List[Dict]:
        """Get products (for backward compatibility)"""
        return self.db.get_products(**kwargs)
