"""
Advanced NLP Engine for Construction Materials Chatbot.
Features:
- Fuzzy matching for typos
- Smart product recommendations within categories
- Product comparison (same category only)
- Price calculations
- Stock checking with alternatives
- Unlimited conversation context
"""
import re
import json
from difflib import SequenceMatcher
from typing import Dict, List, Optional, Tuple, Any
from dataclasses import dataclass, field


@dataclass
class ProductMatch:
    """Represents a matched product with confidence score"""
    product: Dict
    score: float
    match_type: str  # 'exact', 'fuzzy', 'keyword'


@dataclass
class ConversationMemory:
    """Stores full conversation history and context"""
    messages: List[Dict] = field(default_factory=list)
    mentioned_products: List[Dict] = field(default_factory=list)  # All products mentioned
    current_product: Optional[Dict] = None  # Most recent product focus
    comparison_products: List[Dict] = field(default_factory=list)  # Products being compared
    last_intent: Optional[str] = None
    user_preferences: Dict = field(default_factory=dict)  # Size, material preferences

    def add_message(self, content: str, is_user: bool, intent: str = None, products: List[Dict] = None):
        """Add a message to history"""
        self.messages.append({
            'content': content,
            'is_user': is_user,
            'intent': intent,
            'products': products or [],
            'index': len(self.messages)
        })

        if products:
            for p in products:
                if p not in self.mentioned_products:
                    self.mentioned_products.append(p)

    def get_products_by_category(self, category_id: int) -> List[Dict]:
        """Get all mentioned products in a specific category"""
        return [p for p in self.mentioned_products if p.get('category_id') == category_id]

    def get_conversation_summary(self) -> str:
        """Get a summary of the conversation"""
        product_names = [p.get('name', 'Unknown') for p in self.mentioned_products[-5:]]
        return f"Products discussed: {', '.join(product_names) if product_names else 'None'}"


class FuzzyMatcher:
    """Handles fuzzy matching for typos and variations"""

    # Common typos and variations mapping
    WORD_CORRECTIONS = {
        'nailes': 'nails', 'nial': 'nail', 'nals': 'nails',
        'scews': 'screws', 'screw': 'screws', 'srews': 'screws',
        'bolt': 'bolts', 'bols': 'bolts',
        'ciment': 'cement', 'sement': 'cement', 'cemant': 'cement',
        'brik': 'brick', 'bricks': 'brick', 'brich': 'brick',
        'hammar': 'hammer', 'hamer': 'hammer',
        'woood': 'wood', 'wod': 'wood',
        'plywod': 'plywood', 'plyood': 'plywood',
        'drywll': 'drywall', 'drywal': 'drywall',
        'insulaton': 'insulation', 'insualtion': 'insulation',
        'roofin': 'roofing', 'roofign': 'roofing',
        'pant': 'paint', 'paont': 'paint',
        'til': 'tile', 'tiles': 'tile', 'tiel': 'tile',
        'concret': 'concrete', 'conrete': 'concrete',
        'galvanised': 'galvanized', 'galvenized': 'galvanized',
        'stainles': 'stainless', 'stainlees': 'stainless',
    }

    @classmethod
    def correct_word(cls, word: str) -> str:
        """Correct a potentially misspelled word"""
        word_lower = word.lower()

        # Direct correction lookup
        if word_lower in cls.WORD_CORRECTIONS:
            return cls.WORD_CORRECTIONS[word_lower]

        return word

    @classmethod
    def similarity(cls, s1: str, s2: str) -> float:
        """Calculate similarity between two strings"""
        return SequenceMatcher(None, s1.lower(), s2.lower()).ratio()

    @classmethod
    def fuzzy_match(cls, query: str, target: str, threshold: float = 0.7) -> Tuple[bool, float]:
        """Check if query fuzzy-matches target"""
        query_lower = query.lower()
        target_lower = target.lower()

        # Exact match
        if query_lower == target_lower:
            return True, 1.0

        # Substring match
        if query_lower in target_lower or target_lower in query_lower:
            return True, 0.9

        # Fuzzy match
        score = cls.similarity(query_lower, target_lower)
        if score >= threshold:
            return True, score

        # Word-by-word matching
        query_words = set(query_lower.split())
        target_words = set(target_lower.split())
        common = query_words & target_words
        if common:
            word_score = len(common) / max(len(query_words), len(target_words))
            if word_score >= 0.5:
                return True, word_score

        return False, score


class SmartProductMatcher:
    """Intelligent product matching with context awareness"""

    # Category keywords for classification
    CATEGORY_KEYWORDS = {
        'fasteners': ['nail', 'nails', 'screw', 'screws', 'bolt', 'bolts', 'fastener', 'tapcon'],
        'concrete-cement': ['cement', 'concrete', 'mortar', 'grout'],
        'bricks-blocks': ['brick', 'bricks', 'block', 'blocks', 'masonry'],
        'lumber': ['lumber', 'wood', 'plywood', 'osb', 'board', 'stud', '2x4', '2x6'],
        'drywall': ['drywall', 'sheetrock', 'gypsum'],
        'insulation': ['insulation', 'fiberglass', 'foam'],
        'roofing': ['roofing', 'shingle', 'shingles', 'felt'],
        'painting': ['paint', 'primer', 'latex', 'stain'],
        'tile': ['tile', 'tiles', 'ceramic', 'porcelain'],
        'tools': ['hammer', 'tape', 'level', 'saw', 'drill', 'tool'],
    }

    def __init__(self, products: List[Dict]):
        self.products = products
        self.products_by_category = self._group_by_category()

    def _group_by_category(self) -> Dict[int, List[Dict]]:
        """Group products by category ID"""
        grouped = {}
        for p in self.products:
            cat_id = p.get('category_id')
            if cat_id not in grouped:
                grouped[cat_id] = []
            grouped[cat_id].append(p)
        return grouped

    def find_products(self, query: str, limit: int = 5) -> List[ProductMatch]:
        """Find products matching a query with fuzzy matching"""
        query_lower = query.lower()
        matches = []

        # Correct potential typos in query
        corrected_words = []
        for word in query_lower.split():
            corrected = FuzzyMatcher.correct_word(word)
            corrected_words.append(corrected)
        corrected_query = ' '.join(corrected_words)

        for product in self.products:
            name = product.get('name', '').lower()
            description = product.get('description', '').lower()

            # Check exact match in name
            if corrected_query in name:
                matches.append(ProductMatch(product, 1.0, 'exact'))
                continue

            # Check fuzzy match on name
            is_match, score = FuzzyMatcher.fuzzy_match(corrected_query, name)
            if is_match and score >= 0.6:
                matches.append(ProductMatch(product, score, 'fuzzy'))
                continue

            # Check keyword match
            for word in corrected_words:
                if len(word) >= 3 and word in name:
                    matches.append(ProductMatch(product, 0.7, 'keyword'))
                    break
                elif len(word) >= 3 and word in description:
                    matches.append(ProductMatch(product, 0.5, 'keyword'))
                    break

        # Sort by score and remove duplicates
        seen = set()
        unique_matches = []
        for m in sorted(matches, key=lambda x: x.score, reverse=True):
            if m.product['id'] not in seen:
                seen.add(m.product['id'])
                unique_matches.append(m)

        return unique_matches[:limit]

    def find_alternatives(self, product: Dict, exclude_ids: List[int] = None) -> List[Dict]:
        """Find alternative products in the same category"""
        category_id = product.get('category_id')
        if not category_id:
            return []

        exclude_ids = exclude_ids or []
        exclude_ids.append(product.get('id'))

        category_products = self.products_by_category.get(category_id, [])

        # Filter and sort by relevance
        alternatives = []
        for p in category_products:
            if p.get('id') in exclude_ids:
                continue
            if p.get('stock_quantity', 0) <= 0:
                continue

            # Calculate relevance score based on similar attributes
            score = self._calculate_similarity(product, p)
            alternatives.append((p, score))

        # Sort by similarity and return
        alternatives.sort(key=lambda x: x[1], reverse=True)
        return [a[0] for a in alternatives[:5]]

    def _calculate_similarity(self, p1: Dict, p2: Dict) -> float:
        """Calculate similarity between two products"""
        score = 0.0

        # Same supplier bonus
        if p1.get('supplier_id') == p2.get('supplier_id'):
            score += 0.2

        # Price similarity (within 30%)
        price1 = float(p1.get('price', 0))
        price2 = float(p2.get('price', 0))
        if price1 > 0 and price2 > 0:
            price_diff = abs(price1 - price2) / max(price1, price2)
            if price_diff <= 0.3:
                score += 0.3 * (1 - price_diff)

        # Name similarity
        name_score = FuzzyMatcher.similarity(
            p1.get('name', ''),
            p2.get('name', '')
        )
        score += name_score * 0.5

        return score

    def can_compare(self, product1: Dict, product2: Dict) -> Tuple[bool, str]:
        """Check if two products can be meaningfully compared"""
        cat1 = product1.get('category_id')
        cat2 = product2.get('category_id')

        if cat1 == cat2:
            return True, "Same category - comparison valid"

        # Check if categories are related
        cat1_name = product1.get('category_name', '').lower()
        cat2_name = product2.get('category_name', '').lower()

        # Related categories that can be compared
        related_groups = [
            {'fasteners', 'hardware'},
            {'lumber', 'lumber-composites', 'plywood'},
            {'tile', 'flooring'},
            {'concrete-cement', 'bricks-blocks', 'masonry'},
        ]

        for group in related_groups:
            if any(g in cat1_name for g in group) and any(g in cat2_name for g in group):
                return True, "Related categories - comparison possible"

        return False, f"Cannot compare {cat1_name} with {cat2_name} - different product types"


class PriceCalculator:
    """Handles price calculations"""

    @staticmethod
    def calculate_total(product: Dict, quantity: int) -> Dict:
        """Calculate total price for a quantity"""
        price = float(product.get('price', 0))
        unit = product.get('unit', 'piece')
        stock = product.get('stock_quantity', 0)

        total = price * quantity
        in_stock = stock >= quantity

        return {
            'product_name': product.get('name'),
            'unit_price': price,
            'quantity': quantity,
            'unit': unit,
            'total': round(total, 2),
            'in_stock': in_stock,
            'available_stock': stock,
            'message': f"{quantity} {unit}(s) of {product.get('name')} = ${total:.2f}"
        }

    @staticmethod
    def compare_prices(products: List[Dict], quantity: int = 1) -> str:
        """Generate a price comparison table"""
        if not products:
            return "No products to compare"

        lines = ["Price Comparison:"]
        lines.append("-" * 50)

        for p in products:
            price = float(p.get('price', 0))
            unit = p.get('unit', 'piece')
            total = price * quantity
            stock = p.get('stock_quantity', 0)
            stock_status = "In Stock" if stock >= quantity else f"Only {stock} left"

            lines.append(f"- {p.get('name')}")
            lines.append(f"  ${price:.2f}/{unit} x {quantity} = ${total:.2f} ({stock_status})")

        return "\n".join(lines)


class SmartRecommendations:
    """Generates intelligent product recommendations"""

    def __init__(self, matcher: SmartProductMatcher):
        self.matcher = matcher

    def get_recommendations(self, context: ConversationMemory, query: str = None) -> List[Dict]:
        """Get recommendations based on conversation context"""
        recommendations = []

        # If there's a current product, recommend similar ones
        if context.current_product:
            current = context.current_product
            alternatives = self.matcher.find_alternatives(
                current,
                exclude_ids=[p.get('id') for p in context.mentioned_products]
            )
            recommendations.extend(alternatives[:3])

        # If query mentions specific attributes, filter by them
        if query:
            size_match = re.search(r'(\d+)\s*(inch|in|cm|mm|ft|foot|feet)', query.lower())
            if size_match:
                size_value = size_match.group(1)
                size_unit = size_match.group(2)

                # Look for products with matching size in dimensions
                for alt in recommendations[:]:
                    dims = alt.get('dimensions', '{}')
                    if isinstance(dims, str):
                        if size_value not in dims:
                            # Lower priority but don't remove
                            pass

        return recommendations[:5]

    def suggest_alternatives_for_out_of_stock(self, product: Dict) -> Tuple[List[Dict], str]:
        """Suggest alternatives when product is out of stock"""
        stock = product.get('stock_quantity', 0)

        if stock > 0:
            return [], f"{product.get('name')} is in stock ({stock} available)"

        alternatives = self.matcher.find_alternatives(product)

        if not alternatives:
            return [], f"Sorry, {product.get('name')} is out of stock and no alternatives available"

        message = f"{product.get('name')} is currently out of stock. Here are similar alternatives:\n"
        for i, alt in enumerate(alternatives[:3], 1):
            alt_stock = alt.get('stock_quantity', 0)
            message += f"\n{i}. {alt.get('name')} - ${alt.get('price')}/{alt.get('unit')} ({alt_stock} in stock)"

        return alternatives, message


class NLPEngine:
    """Main NLP Engine combining all features"""

    # Common construction material keywords for better matching
    PRODUCT_KEYWORDS = {
        'nail': ['nail', 'nails'],
        'screw': ['screw', 'screws'],
        'bolt': ['bolt', 'bolts'],
        'cement': ['cement', 'concrete'],
        'brick': ['brick', 'bricks'],
        'board': ['board', 'boards', 'lumber', 'plywood'],
        'pipe': ['pipe', 'pipes', 'tubing'],
        'wire': ['wire', 'wires', 'cable'],
        'outlet': ['outlet', 'outlets', 'socket'],
        'tile': ['tile', 'tiles', 'flooring'],
        'paint': ['paint', 'paints', 'primer'],
        'putty': ['putty', 'plaster', 'drywall'],
        'insulation': ['insulation', 'insulating'],
        'roofing': ['roofing', 'shingle', 'shingles'],
        'drywall': ['drywall', 'sheetrock'],
        'tool': ['tool', 'tools', 'hammer', 'drill'],
    }

    # Intent patterns
    INTENT_PATTERNS = {
        'product_search': [
            r'(?:show|find|get|need|want|looking for|search)\s+(?:me\s+)?(.+)',
            r'(?:do you have|got any)\s+(.+)',
            r'i need\s+(.+)',
        ],
        'price_inquiry': [
            r'(?:what(?:\'s| is) the price|cost of|price for)\s+(.+)',
            r'(?:total|calculate)\s+(?:for\s+)?(\d+)\s+(.+)',
        ],
        'comparison': [
            r'compare\s+(.+?)\s+(?:with|to|and|vs)\s+(.+)',
            r'(?:difference|differences)\s+between\s+(.+?)\s+and\s+(.+)',
            r'which is better[,:]?\s+(.+?)\s+or\s+(.+)',
        ],
        'stock_check': [
            r'(?:is|are)\s+(.+?)\s+(?:in stock|available)',
            r'(?:stock|availability)\s+(?:of\s+)?(.+)',
            r'do you have\s+(.+?)\s+in stock',
        ],
        'recommendation': [
            r'(?:recommend|suggest|similar to|alternatives? (?:to|for))\s+(.+)',
            r'(?:what else|anything else|other options)',
            r'(?:something like|similar)\s+(.+)',
        ],
        'alternatives': [
            r'^alternatives?$',
            r'^other options?$',
            r'^what else\??$',
            r'^similar products?$',
        ],
        'attribute_question': [
            r'(?:what|which)\s+(?:is\s+)?(?:the\s+)?(?:material|size|color|weight|dimensions?|length|height|width)',
            r'(?:what is the|what\'s the)\s+(?:length|size|weight|material|dimensions?|height|width)',
            r'(?:how\s+(?:long|big|tall|wide|heavy))\s+(?:is|are)',
            r'(?:made of|consists of)',
            r'^(?:length|size|dimensions?)\??$',
        ],
        'quantity_calculation': [
            r'(?:how much for|total for|price of)\s+(\d+)\s+(.+)',
            r'(\d+)\s+(?:packs?|pieces?|bags?|units?)\s+(?:of\s+)?(.+)',
            r'i(?:\'ll| will)?\s+take\s+(\d+)\s+(.+)',
        ],
        'how_much': [
            r'^how much\??$',
            r'^how much is (?:it|this|that)\??$',
            r'^price\??$',
            r'^cost\??$',
        ],
        'most_expensive': [
            r'most expensive',
            r'expensive',
            r'highest price',
            r'premium',
            r'top quality',
        ],
        'cheapest': [
            r'cheapest',
            r'lowest price',
            r'budget',
            r'affordable',
            r'least expensive',
        ],
        'category_list': [
            r'(?:how many|what|which)\s+(?:types?|kinds?)\s+(?:of\s+)?(.+?)(?:\s+(?:are there|do you have|available))?\??$',
            r'(?:list|show)\s+(?:all\s+)?(.+?)(?:\s+types?)?$',
            r'all\s+(.+)',
        ],
        'greeting': [
            r'^(?:hi|hello|hey|good (?:morning|afternoon|evening))$',
        ],
        'help': [
            r'^(?:help|what can you do|how do(?:es)? (?:this|it) work)$',
        ],
        'categories': [
            r'^(?:categories|all categories|show categories|browse categories)$',
            r'(?:show|list|get)\s+categories',
        ],
    }

    def __init__(self, products: List[Dict], categories: List[Dict] = None):
        self.products = products
        self.categories = categories or []
        self.matcher = SmartProductMatcher(products)
        self.recommender = SmartRecommendations(self.matcher)
        self.memories: Dict[str, ConversationMemory] = {}

    def set_categories(self, categories: List[Dict]):
        """Set categories list for fallback suggestions"""
        self.categories = categories

    def _handle_categories_list(self) -> Dict:
        """Handle request to show all categories"""
        if not self.categories:
            return {
                'intent': 'categories',
                'response': "Sorry, categories list is not available right now. Try searching for a product by name.",
                'products': [],
                'categories': []
            }

        response = "Our product categories:\n\n"

        for i, cat in enumerate(self.categories, 1):
            product_count = cat.get('product_count', 0)
            response += f"{i}. {cat.get('name')}"
            if product_count > 0:
                response += f" ({product_count} products)"
            response += "\n"

        response += "\nType a category or product name to search."

        return {
            'intent': 'categories',
            'response': response,
            'products': [],
            'categories': self.categories
        }

    def get_memory(self, user_id: str) -> ConversationMemory:
        """Get or create conversation memory for a user"""
        if user_id not in self.memories:
            self.memories[user_id] = ConversationMemory()
        return self.memories[user_id]

    def process(self, message: str, user_id: str) -> Dict[str, Any]:
        """Process a message and return response"""
        memory = self.get_memory(user_id)
        message_lower = message.lower().strip()

        # Add user message to memory
        memory.add_message(message, is_user=True)

        # Detect intent
        intent, params = self._detect_intent(message_lower)

        # Process based on intent
        result = self._handle_intent(intent, params, message, memory)

        # Update memory
        memory.last_intent = intent
        if result.get('products'):
            memory.add_message(
                result.get('response', ''),
                is_user=False,
                intent=intent,
                products=result.get('products', [])
            )
            if result['products']:
                memory.current_product = result['products'][0]

        return result

    def _detect_intent(self, message: str) -> Tuple[str, Dict]:
        """Detect intent from message"""
        for intent, patterns in self.INTENT_PATTERNS.items():
            for pattern in patterns:
                match = re.search(pattern, message, re.IGNORECASE)
                if match:
                    return intent, {'groups': match.groups(), 'match': match}

        # Default to product search if contains product-like words
        if any(word in message for word in ['nail', 'screw', 'cement', 'brick', 'wood', 'paint', 'tile']):
            return 'product_search', {'query': message}

        return 'unknown', {}

    def _handle_intent(self, intent: str, params: Dict, message: str, memory: ConversationMemory) -> Dict:
        """Handle detected intent"""

        if intent == 'greeting':
            return {
                'intent': 'greeting',
                'response': "Hello! Welcome to Construkt. I can help you find construction materials, compare products, calculate prices, and check stock availability. What are you looking for today?",
                'products': []
            }

        if intent == 'help':
            return {
                'intent': 'help',
                'response': """I can help you with:
- Finding products (e.g., "show me nails", "I need cement")
- Checking prices (e.g., "how much for 5 packs")
- Comparing products (e.g., "compare galvanized vs regular nails")
- Stock availability (e.g., "is cement in stock")
- Recommendations (e.g., "show alternatives")
- Sorting by price (e.g., "most expensive", "cheapest")
- Categories (type "categories" for a list)

What are you looking for?""",
                'products': []
            }

        if intent == 'categories':
            return self._handle_categories_list()

        if intent == 'product_search':
            query = params.get('groups', (message,))[0] if params.get('groups') else message
            return self._handle_product_search(query, memory)

        if intent == 'price_inquiry' or intent == 'quantity_calculation':
            return self._handle_price_calculation(params, message, memory)

        if intent == 'how_much':
            return self._handle_how_much(memory)

        if intent == 'comparison':
            return self._handle_comparison(params, memory)

        if intent == 'stock_check':
            return self._handle_stock_check(params, message, memory)

        if intent == 'recommendation' or intent == 'alternatives':
            return self._handle_alternatives(memory)

        if intent == 'most_expensive':
            return self._handle_price_sort(message, memory, expensive=True)

        if intent == 'cheapest':
            return self._handle_price_sort(message, memory, expensive=False)

        if intent == 'category_list':
            query = params.get('groups', (message,))[0] if params.get('groups') else message
            return self._handle_category_list(query, memory)

        if intent == 'attribute_question':
            return self._handle_attribute_question(message, memory)

        # Check if it's a contextual question
        if memory.current_product:
            return self._handle_contextual_question(message, memory)

        # For unknown intent, try product search first, then fallback with categories
        result = self._handle_product_search(message, memory)

        # If product search found nothing and returned fallback, enhance it
        if result.get('intent') == 'fallback' or not result.get('products'):
            return self._generate_smart_fallback(message, memory)

        return result

    def _generate_smart_fallback(self, message: str, memory: ConversationMemory) -> Dict:
        """Generate smart fallback response with clickable categories"""
        response = "I'm not sure what you're looking for. Browse our categories:"

        # Format categories as clickable items
        category_items = []
        if self.categories:
            for cat in self.categories[:8]:
                category_items.append({
                    'id': cat.get('id'),
                    'name': cat.get('name'),
                    'product_count': cat.get('product_count', 0),
                    'link': f"/products.php?category={cat.get('id')}"
                })

        return {
            'type': 'categories',
            'intent': 'unknown_with_suggestions',
            'response': response,
            'message': response,
            'items': category_items,
            'products': [],
            'actions': [
                {'type': 'SEARCH', 'label': 'Search Products'},
                {'type': 'CATEGORIES', 'label': 'All Categories'},
                {'type': 'HELP', 'label': 'Help'}
            ]
        }

    def _handle_product_search(self, query: str, memory: ConversationMemory) -> Dict:
        """Handle product search with improved matching"""
        # Normalize query and extract key terms
        query_lower = query.lower().strip()

        # Extract size/specification from query (e.g., "3 inch nail" -> size="3 inch")
        size_match = re.search(r'(\d+(?:[.,]\d+)?)\s*(inch|in|mm|cm|m|ft|feet)', query_lower)
        size_spec = size_match.group(0) if size_match else None

        # Find base keyword
        base_keyword = None
        for keyword, variations in self.PRODUCT_KEYWORDS.items():
            for var in variations:
                if var in query_lower:
                    base_keyword = keyword
                    break
            if base_keyword:
                break

        # Search with improved matching
        matches = self.matcher.find_products(query, limit=20)

        # If we have a base keyword, also search for variations
        if base_keyword and not matches:
            matches = self.matcher.find_products(base_keyword, limit=20)

        if not matches:
            # No products found - provide helpful fallback
            return self._generate_fallback_response(query, memory)

        products = [m.product for m in matches]

        # If we have size specification, try to find exact match first
        if size_spec:
            exact_matches = []
            partial_matches = []
            for p in products:
                name_lower = p.get('name', '').lower()
                desc_lower = p.get('description', '').lower()
                if size_spec in name_lower or size_spec in desc_lower:
                    exact_matches.append(p)
                else:
                    partial_matches.append(p)

            if exact_matches:
                # Found exact size match
                primary = exact_matches[0]
                response = f"Found exact match: {primary.get('name')}\n\n"
                response += f"Price: ${primary.get('price')}/{primary.get('unit')}\n"
                stock = primary.get('stock_quantity', 0)
                response += f"In stock: {stock}\n"

                if len(exact_matches) > 1:
                    response += f"\nAlso found {len(exact_matches)-1} similar products.\n"

                if partial_matches:
                    response += f"\nOther {base_keyword or query} options:\n"
                    for i, p in enumerate(partial_matches[:5], 1):
                        response += f"{i}. {p.get('name')} - ${p.get('price')}/{p.get('unit')}\n"

                return {
                    'intent': 'product_search',
                    'response': response,
                    'products': exact_matches + partial_matches[:5],
                    'primary_product': primary
                }

        # Multiple products found - show list
        if len(products) > 1:
            response = f"Found {len(products)} products for '{query}':\n\n"

            for i, p in enumerate(products[:10], 1):
                stock = p.get('stock_quantity', 0)
                stock_status = f"({stock} in stock)" if stock > 0 else "(out of stock)"
                response += f"{i}. {p.get('name')}\n"
                response += f"   ${p.get('price')}/{p.get('unit')} {stock_status}\n"

            if len(products) > 10:
                response += f"\n...and {len(products) - 10} more products.\n"

            response += "\nType a number or product name for details."

            return {
                'intent': 'product_search',
                'response': response,
                'products': products,
                'primary_product': products[0]
            }

        # Single product found
        primary = products[0]
        stock = primary.get('stock_quantity', 0)
        stock_msg = f" ({stock} in stock)" if stock > 0 else " (OUT OF STOCK)"

        response = f"Found: {primary.get('name')}!\n\n"
        response += f"Price: ${primary.get('price')}/{primary.get('unit')}{stock_msg}\n"
        response += f"Category: {primary.get('category_name', 'General')}\n"

        if primary.get('description'):
            response += f"\n{primary.get('description')}\n"

        # Parse dimensions
        dims = primary.get('dimensions')
        if dims:
            try:
                dims_data = json.loads(dims) if isinstance(dims, str) else dims
                if dims_data:
                    response += f"\nSpecifications: "
                    specs = [f"{k}: {v}" for k, v in dims_data.items()]
                    response += ", ".join(specs)
            except:
                pass

        # If out of stock, suggest alternatives
        if stock <= 0:
            alternatives, alt_msg = self.recommender.suggest_alternatives_for_out_of_stock(primary)
            if alternatives:
                response += f"\n\n{alt_msg}"
                products.extend(alternatives)

        response += "\n\nWould you like to know the price for a quantity or see alternatives?"

        return {
            'intent': 'product_search',
            'response': response,
            'products': products,
            'primary_product': primary
        }

    def _generate_fallback_response(self, query: str, memory: ConversationMemory) -> Dict:
        """Generate helpful fallback when no products found"""
        response = f"Sorry, I couldn't find any products matching '{query}'.\n\n"

        # Suggest categories
        if self.categories:
            response += "Browse our product categories:\n\n"
            for i, cat in enumerate(self.categories[:8], 1):
                product_count = cat.get('product_count', 0)
                response += f"{i}. {cat.get('name')}"
                if product_count > 0:
                    response += f" ({product_count} products)"
                response += "\n"

            response += "\nType a category name to see products."
        else:
            # No categories available, suggest popular products
            response += "Try searching for:\n"
            response += "- Cement, bricks, blocks\n"
            response += "- Nails, screws, bolts\n"
            response += "- Paint, putty\n"
            response += "- Pipes, wires\n"

        response += "\n\nOr type 'help' for available commands."

        return {
            'intent': 'fallback',
            'response': response,
            'products': [],
            'categories': self.categories[:8] if self.categories else []
        }

    def _handle_price_calculation(self, params: Dict, message: str, memory: ConversationMemory) -> Dict:
        """Handle price calculation"""
        # Extract quantity
        qty_match = re.search(r'(\d+)', message)
        quantity = int(qty_match.group(1)) if qty_match else 1

        # Find product
        product = memory.current_product
        if not product:
            # Try to find product in message
            matches = self.matcher.find_products(message)
            if matches:
                product = matches[0].product

        if not product:
            return {
                'intent': 'price_inquiry',
                'response': "Which product would you like to calculate the price for?",
                'products': []
            }

        calc = PriceCalculator.calculate_total(product, quantity)

        response = f"Price calculation for {product.get('name')}:\n\n"
        response += f"Unit price: ${calc['unit_price']:.2f}/{calc['unit']}\n"
        response += f"Quantity: {quantity}\n"
        response += f"Total: ${calc['total']:.2f}\n\n"

        if calc['in_stock']:
            response += f"Available in stock: {calc['available_stock']} {calc['unit']}(s)"
        else:
            response += f"Note: Only {calc['available_stock']} in stock (you requested {quantity})"

            # Suggest alternatives
            alternatives, _ = self.recommender.suggest_alternatives_for_out_of_stock(product)
            if alternatives:
                response += "\n\nAlternatives available:"
                for alt in alternatives[:2]:
                    response += f"\n- {alt.get('name')}: ${alt.get('price')}/{alt.get('unit')}"

        return {
            'intent': 'price_calculation',
            'response': response,
            'products': [product],
            'calculation': calc
        }

    def _handle_comparison(self, params: Dict, memory: ConversationMemory) -> Dict:
        """Handle product comparison"""
        groups = params.get('groups', ())

        if len(groups) >= 2:
            query1, query2 = groups[0], groups[1]
        elif memory.mentioned_products and len(memory.mentioned_products) >= 2:
            # Use last two mentioned products
            query1 = memory.mentioned_products[-2].get('name', '')
            query2 = memory.mentioned_products[-1].get('name', '')
        else:
            return {
                'intent': 'comparison',
                'response': "Please specify two products to compare. For example: 'compare galvanized nails with stainless steel nails'",
                'products': []
            }

        # Find both products
        matches1 = self.matcher.find_products(query1)
        matches2 = self.matcher.find_products(query2)

        if not matches1 or not matches2:
            missing = query1 if not matches1 else query2
            return {
                'intent': 'comparison',
                'response': f"Could not find product: {missing}",
                'products': []
            }

        product1 = matches1[0].product
        product2 = matches2[0].product

        # Check if comparison is valid
        can_compare, reason = self.matcher.can_compare(product1, product2)

        if not can_compare:
            return {
                'intent': 'comparison',
                'response': f"I can't meaningfully compare these products. {reason}\n\nTry comparing similar products, like different types of nails or different cement brands.",
                'products': [product1, product2]
            }

        # Generate comparison
        response = f"Comparison: {product1.get('name')} vs {product2.get('name')}\n"
        response += "=" * 50 + "\n\n"

        # Price comparison
        price1 = float(product1.get('price', 0))
        price2 = float(product2.get('price', 0))
        response += f"PRICE:\n"
        response += f"  {product1.get('name')}: ${price1:.2f}/{product1.get('unit')}\n"
        response += f"  {product2.get('name')}: ${price2:.2f}/{product2.get('unit')}\n"

        if price1 != price2:
            cheaper = product1 if price1 < price2 else product2
            savings = abs(price1 - price2)
            response += f"  -> {cheaper.get('name')} is ${savings:.2f} cheaper\n"

        # Stock comparison
        stock1 = product1.get('stock_quantity', 0)
        stock2 = product2.get('stock_quantity', 0)
        response += f"\nAVAILABILITY:\n"
        response += f"  {product1.get('name')}: {stock1} in stock\n"
        response += f"  {product2.get('name')}: {stock2} in stock\n"

        # Specifications comparison
        dims1 = product1.get('dimensions', '{}')
        dims2 = product2.get('dimensions', '{}')

        try:
            specs1 = json.loads(dims1) if isinstance(dims1, str) else dims1 or {}
            specs2 = json.loads(dims2) if isinstance(dims2, str) else dims2 or {}

            all_keys = set(specs1.keys()) | set(specs2.keys())
            if all_keys:
                response += f"\nSPECIFICATIONS:\n"
                for key in sorted(all_keys):
                    val1 = specs1.get(key, 'N/A')
                    val2 = specs2.get(key, 'N/A')
                    response += f"  {key}: {val1} vs {val2}\n"
        except:
            pass

        # Recommendation
        response += "\nRECOMMENDATION:\n"
        if stock1 <= 0 and stock2 > 0:
            response += f"  -> Go with {product2.get('name')} (other is out of stock)"
        elif stock2 <= 0 and stock1 > 0:
            response += f"  -> Go with {product1.get('name')} (other is out of stock)"
        elif price1 < price2:
            response += f"  -> {product1.get('name')} offers better value"
        elif price2 < price1:
            response += f"  -> {product2.get('name')} offers better value"
        else:
            response += "  -> Both are similar in price, choose based on specifications"

        memory.comparison_products = [product1, product2]

        return {
            'intent': 'comparison',
            'response': response,
            'products': [product1, product2]
        }

    def _handle_stock_check(self, params: Dict, message: str, memory: ConversationMemory) -> Dict:
        """Handle stock availability check"""
        query = params.get('groups', (message,))[0] if params.get('groups') else message

        # Try current product first
        product = memory.current_product
        if not product or query.lower() not in product.get('name', '').lower():
            matches = self.matcher.find_products(query)
            if matches:
                product = matches[0].product

        if not product:
            return {
                'intent': 'stock_check',
                'response': f"Which product would you like to check stock for?",
                'products': []
            }

        stock = product.get('stock_quantity', 0)
        name = product.get('name')

        if stock > 0:
            response = f"Yes! {name} is in stock.\n\n"
            response += f"Available: {stock} {product.get('unit')}(s)\n"
            response += f"Price: ${product.get('price')}/{product.get('unit')}"
        else:
            response = f"Sorry, {name} is currently out of stock.\n\n"
            alternatives, alt_msg = self.recommender.suggest_alternatives_for_out_of_stock(product)
            if alternatives:
                response += "Here are similar products that are available:\n"
                for alt in alternatives[:3]:
                    response += f"\n- {alt.get('name')}: ${alt.get('price')}/{alt.get('unit')} ({alt.get('stock_quantity')} in stock)"

        return {
            'intent': 'stock_check',
            'response': response,
            'products': [product]
        }

    def _handle_recommendation(self, params: Dict, message: str, memory: ConversationMemory) -> Dict:
        """Handle recommendation requests"""
        recommendations = self.recommender.get_recommendations(memory, message)

        if not recommendations and memory.current_product:
            recommendations = self.matcher.find_alternatives(memory.current_product)

        if not recommendations:
            return {
                'intent': 'recommendation',
                'response': "I'd be happy to recommend products! What type of materials are you looking for?",
                'products': []
            }

        response = "Here are my recommendations:\n\n"
        for i, rec in enumerate(recommendations[:5], 1):
            stock_status = f"{rec.get('stock_quantity')} in stock" if rec.get('stock_quantity', 0) > 0 else "Out of stock"
            response += f"{i}. {rec.get('name')}\n"
            response += f"   ${rec.get('price')}/{rec.get('unit')} - {stock_status}\n"

        response += "\nWould you like more details on any of these?"

        return {
            'intent': 'recommendation',
            'response': response,
            'products': recommendations
        }

    def _handle_attribute_question(self, message: str, memory: ConversationMemory) -> Dict:
        """Handle questions about product attributes"""
        product = memory.current_product

        if not product:
            return {
                'intent': 'attribute_question',
                'response': "Which product would you like to know about?",
                'products': []
            }

        # Parse dimensions
        dims = product.get('dimensions', '{}')
        try:
            specs = json.loads(dims) if isinstance(dims, str) else dims or {}
        except:
            specs = {}

        response = f"Here are the details for {product.get('name')}:\n\n"

        # Determine what attribute is being asked
        message_lower = message.lower()

        if 'material' in message_lower or 'made of' in message_lower:
            material = specs.get('material', 'Not specified')
            response = f"{product.get('name')} is made of {material}."

        elif 'length' in message_lower or 'long' in message_lower:
            if 'length' in specs:
                response = f"The length of {product.get('name')} is {specs['length']}."
            elif 'dimensions' in specs:
                response = f"{product.get('name')} dimensions: {specs['dimensions']}"
            else:
                response = f"Length not specified for {product.get('name')}. Available specs: {', '.join(specs.keys()) if specs else 'None'}"

        elif 'size' in message_lower or 'dimension' in message_lower:
            if 'dimensions' in specs:
                response = f"{product.get('name')} dimensions: {specs['dimensions']}"
            elif 'length' in specs:
                response = f"{product.get('name')} is {specs['length']} long."
            else:
                response = f"Size specifications for {product.get('name')}: {json.dumps(specs)}"

        elif 'weight' in message_lower or 'heavy' in message_lower:
            weight = specs.get('weight', 'Not specified')
            response = f"{product.get('name')} weight: {weight}"

        elif 'quantity' in message_lower or 'pack' in message_lower or 'many' in message_lower:
            qty = specs.get('quantity_per_pack', specs.get('per_pack', 'Not specified'))
            response = f"{product.get('name')} contains {qty} per package."

        else:
            # Show all specs
            response = f"Specifications for {product.get('name')}:\n"
            response += f"- Price: ${product.get('price')}/{product.get('unit')}\n"
            response += f"- In stock: {product.get('stock_quantity')}\n"
            for key, value in specs.items():
                response += f"- {key.replace('_', ' ').title()}: {value}\n"

        return {
            'intent': 'attribute_question',
            'response': response,
            'products': [product]
        }

    def _handle_contextual_question(self, message: str, memory: ConversationMemory) -> Dict:
        """Handle contextual questions about current product"""
        product = memory.current_product

        # Check for price/cost questions
        if any(word in message.lower() for word in ['price', 'cost', 'much', 'total']):
            qty_match = re.search(r'(\d+)', message)
            quantity = int(qty_match.group(1)) if qty_match else 1
            return self._handle_price_calculation({'quantity': quantity}, message, memory)

        # Check for comparison
        if any(word in message.lower() for word in ['compare', 'vs', 'versus', 'difference', 'better']):
            return self._handle_comparison({'groups': ()}, memory)

        # Check for stock
        if any(word in message.lower() for word in ['stock', 'available', 'have']):
            return self._handle_stock_check({'groups': ()}, message, memory)

        # Default to attribute question
        return self._handle_attribute_question(message, memory)

    def _handle_how_much(self, memory: ConversationMemory) -> Dict:
        """Handle 'how much?' questions about current product"""
        product = memory.current_product

        if not product:
            return {
                'intent': 'how_much',
                'response': "Which product would you like to know the price for?",
                'products': []
            }

        price = float(product.get('price', 0))
        unit = product.get('unit', 'piece')
        stock = product.get('stock_quantity', 0)
        name = product.get('name')

        response = f"Price for {name}:\n\n"
        response += f"Unit price: ${price:.2f} per {unit}\n"
        response += f"Available: {stock} {unit}(s) in stock\n\n"

        # Show bulk pricing
        response += "Quantity pricing:\n"
        for qty in [1, 5, 10, 25]:
            total = price * qty
            response += f"  {qty} {unit}(s) = ${total:.2f}\n"

        return {
            'intent': 'how_much',
            'response': response,
            'products': [product]
        }

    def _handle_alternatives(self, memory: ConversationMemory) -> Dict:
        """Handle 'alternative' or 'other options' requests"""
        product = memory.current_product

        if not product:
            return {
                'intent': 'alternatives',
                'response': "Which product would you like alternatives for? Tell me what you're looking for.",
                'products': []
            }

        alternatives = self.matcher.find_alternatives(product)

        if not alternatives:
            return {
                'intent': 'alternatives',
                'response': f"Sorry, I couldn't find alternatives for {product.get('name')} in the same category.",
                'products': [product]
            }

        response = f"Alternatives to {product.get('name')} ({product.get('category_name')}):\n\n"

        for i, alt in enumerate(alternatives[:5], 1):
            stock = alt.get('stock_quantity', 0)
            stock_status = f"{stock} in stock" if stock > 0 else "Out of stock"
            response += f"{i}. {alt.get('name')}\n"
            response += f"   ${alt.get('price')}/{alt.get('unit')} - {stock_status}\n"

            # Show key differences
            dims = alt.get('dimensions', '{}')
            try:
                specs = json.loads(dims) if isinstance(dims, str) else dims or {}
                if specs:
                    key_specs = list(specs.items())[:2]
                    spec_str = ", ".join([f"{k}: {v}" for k, v in key_specs])
                    response += f"   ({spec_str})\n"
            except:
                pass

        response += "\nWould you like more details on any of these?"

        return {
            'intent': 'alternatives',
            'response': response,
            'products': alternatives
        }

    def _handle_price_sort(self, message: str, memory: ConversationMemory, expensive: bool = True) -> Dict:
        """Handle 'most expensive' or 'cheapest' requests"""
        # Try to find category from message
        category_products = None
        message_lower = message.lower()

        # Check if current product has a category
        if memory.current_product:
            cat_id = memory.current_product.get('category_id')
            if cat_id:
                category_products = self.matcher.products_by_category.get(cat_id, [])

        # If no category from context, search for product type in message
        if not category_products:
            # Try to find products matching keywords
            for keyword in ['cement', 'nail', 'screw', 'brick', 'wood', 'paint', 'tile', 'lumber']:
                if keyword in message_lower:
                    matches = self.matcher.find_products(keyword, limit=20)
                    if matches:
                        # Get products from same category as first match
                        first_cat = matches[0].product.get('category_id')
                        category_products = self.matcher.products_by_category.get(first_cat, [])
                        break

        if not category_products:
            # Fall back to all products
            category_products = self.products

        # Sort by price
        sorted_products = sorted(
            [p for p in category_products if p.get('stock_quantity', 0) > 0],
            key=lambda x: float(x.get('price', 0)),
            reverse=expensive
        )

        if not sorted_products:
            return {
                'intent': 'price_sort',
                'response': "Sorry, I couldn't find products to sort by price.",
                'products': []
            }

        sort_type = "most expensive" if expensive else "cheapest"
        top = sorted_products[0]
        category_name = top.get('category_name', 'products')

        response = f"{'Most expensive' if expensive else 'Cheapest'} {category_name}:\n\n"

        for i, p in enumerate(sorted_products[:5], 1):
            stock = p.get('stock_quantity', 0)
            response += f"{i}. {p.get('name')}\n"
            response += f"   ${p.get('price')}/{p.get('unit')} - {stock} in stock\n"

        return {
            'intent': 'price_sort',
            'response': response,
            'products': sorted_products[:5],
            'primary_product': sorted_products[0]
        }

    def _handle_category_list(self, query: str, memory: ConversationMemory) -> Dict:
        """Handle 'how many types of X' or 'list all X' requests"""
        # Find products matching query
        matches = self.matcher.find_products(query, limit=50)

        if not matches:
            return {
                'intent': 'category_list',
                'response': f"I couldn't find any {query} products.",
                'products': []
            }

        # Get unique products in the category
        first_match = matches[0].product
        cat_id = first_match.get('category_id')
        cat_name = first_match.get('category_name', query)

        category_products = self.matcher.products_by_category.get(cat_id, [])

        if not category_products:
            category_products = [m.product for m in matches]

        response = f"We have {len(category_products)} types of {cat_name}:\n\n"

        for i, p in enumerate(category_products, 1):
            stock = p.get('stock_quantity', 0)
            stock_status = "In stock" if stock > 0 else "Out of stock"
            response += f"{i}. {p.get('name')} - ${p.get('price')}/{p.get('unit')} ({stock_status})\n"

        response += f"\nWould you like details on any of these?"

        return {
            'intent': 'category_list',
            'response': response,
            'products': category_products
        }

    def clear_memory(self, user_id: str):
        """Clear conversation memory for a user"""
        if user_id in self.memories:
            del self.memories[user_id]

    def get_conversation_history(self, user_id: str) -> List[Dict]:
        """Get full conversation history"""
        memory = self.get_memory(user_id)
        return memory.messages
