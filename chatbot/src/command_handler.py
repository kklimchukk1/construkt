"""
Command Handler for Construkt Chatbot
Handles structured commands instead of free-form NLP.
All responses are based on actual database data - no AI-generated products.
"""
from typing import Dict, List, Optional, Any
from decimal import Decimal
import json


class CommandHandler:
    """
    Processes structured commands and returns data from the database.
    No free-form text processing - only predefined commands.
    """

    # Available commands
    COMMANDS = {
        'SEARCH': 'Search products by keyword',
        'CATEGORIES': 'Get all product categories',
        'CATEGORY': 'Get products in a specific category',
        'PRODUCT': 'Get product details by ID',
        'CHEAPEST': 'Get cheapest products',
        'EXPENSIVE': 'Get most expensive products',
        'FEATURED': 'Get featured/popular products',
        'CALCULATOR': 'Calculate materials for a project',
        'HELP': 'Get help and available commands',
        'POPULAR_SEARCHES': 'Get popular search terms'
    }

    # Popular search terms for quick access
    POPULAR_SEARCHES = [
        'Nails', 'Cement', 'Bricks', 'Paint', 'Tiles',
        'Lumber', 'Tools', 'Drywall', 'Insulation', 'Roofing'
    ]

    def __init__(self, database):
        """
        Initialize command handler with database connection.

        Args:
            database: DatabaseConnector instance
        """
        self.db = database
        self._categories_cache = None

    def execute(self, command: str, params: Optional[Dict] = None, user_id: Optional[str] = None) -> Dict[str, Any]:
        """
        Execute a command and return structured response.

        Args:
            command: Command name (SEARCH, CATEGORIES, etc.)
            params: Command parameters
            user_id: User identifier for context

        Returns:
            Dict with type, message, items, and actions
        """
        command = command.upper()
        params = params or {}

        handlers = {
            'SEARCH': self._handle_search,
            'CATEGORIES': self._handle_categories,
            'CATEGORY': self._handle_category,
            'PRODUCT': self._handle_product,
            'CHEAPEST': self._handle_cheapest,
            'EXPENSIVE': self._handle_expensive,
            'FEATURED': self._handle_featured,
            'CALCULATOR': self._handle_calculator,
            'HELP': self._handle_help,
            'POPULAR_SEARCHES': self._handle_popular_searches
        }

        handler = handlers.get(command)
        if handler:
            try:
                return handler(params, user_id)
            except Exception as e:
                print(f"Error executing command {command}: {e}")
                return self._error_response(f"Error processing command: {str(e)}")
        else:
            return self._error_response(f"Unknown command: {command}")

    def _handle_search(self, params: Dict, user_id: str) -> Dict:
        """Search products by keyword"""
        keyword = params.get('keyword', '').strip()
        limit = params.get('limit', 10)

        if not keyword:
            return {
                'type': 'search_prompt',
                'message': 'What are you looking for?',
                'popular_searches': self.POPULAR_SEARCHES,
                'actions': [
                    {'type': 'CATEGORIES', 'label': 'Browse Categories'},
                    {'type': 'FEATURED', 'label': 'Popular Products'}
                ]
            }

        # Search in database
        products = self.db.get_products(search=keyword, limit=limit)

        if not products:
            return {
                'type': 'no_results',
                'message': f'No products found for "{keyword}".',
                'keyword': keyword,
                'suggestions': self._get_search_suggestions(keyword),
                'actions': [
                    {'type': 'SEARCH', 'label': 'Try Another Search'},
                    {'type': 'CATEGORIES', 'label': 'Browse Categories'}
                ]
            }

        return {
            'type': 'products',
            'message': f'Found {len(products)} products for "{keyword}":',
            'keyword': keyword,
            'items': self._format_products(products),
            'actions': [
                {'type': 'SEARCH', 'label': 'Search Again'},
                {'type': 'CATEGORIES', 'label': 'Browse Categories'}
            ]
        }

    def _handle_categories(self, params: Dict, user_id: str) -> Dict:
        """Get all product categories"""
        categories = self.db.get_categories(with_product_counts=True)

        if not categories:
            return self._error_response("No categories found")

        # Filter to show only parent categories or categories with products
        main_categories = [
            c for c in categories
            if c.get('parent_id') is None or c.get('product_count', 0) > 0
        ]

        return {
            'type': 'categories',
            'message': 'Browse our product categories:',
            'items': [
                {
                    'id': c['id'],
                    'name': c['name'],
                    'slug': c.get('slug', ''),
                    'description': c.get('description', ''),
                    'product_count': c.get('product_count', 0),
                    'link': f"/products.php?category={c['id']}"
                }
                for c in main_categories
            ],
            'actions': [
                {'type': 'SEARCH', 'label': 'Search Products'},
                {'type': 'FEATURED', 'label': 'Popular Products'}
            ]
        }

    def _handle_category(self, params: Dict, user_id: str) -> Dict:
        """Get products in a specific category"""
        category_id = params.get('category_id')
        limit = params.get('limit', 10)

        if not category_id:
            return self._handle_categories(params, user_id)

        products = self.db.get_products(category_id=category_id, limit=limit)

        # Get category name
        categories = self.db.get_categories()
        category = next((c for c in categories if c['id'] == category_id), None)
        category_name = category['name'] if category else 'Category'

        if not products:
            return {
                'type': 'no_results',
                'message': f'No products found in {category_name}.',
                'actions': [
                    {'type': 'CATEGORIES', 'label': 'Other Categories'},
                    {'type': 'SEARCH', 'label': 'Search Products'}
                ]
            }

        return {
            'type': 'products',
            'message': f'{category_name} ({len(products)} products):',
            'category': category_name,
            'items': self._format_products(products),
            'actions': [
                {'type': 'CATEGORIES', 'label': 'Other Categories'},
                {'type': 'SEARCH', 'label': 'Search Products'}
            ]
        }

    def _handle_product(self, params: Dict, user_id: str) -> Dict:
        """Get detailed product information"""
        product_id = params.get('product_id')

        if not product_id:
            return self._error_response("Product ID is required")

        products = self.db.get_products(product_id=product_id, limit=1)

        if not products:
            return {
                'type': 'not_found',
                'message': 'Product not found.',
                'actions': [
                    {'type': 'SEARCH', 'label': 'Search Products'},
                    {'type': 'CATEGORIES', 'label': 'Browse Categories'}
                ]
            }

        product = products[0]
        formatted = self._format_product_detail(product)

        # Get related products from same category
        related = self.db.get_products(category_id=product.get('category_id'), limit=4)
        related = [p for p in related if p['id'] != product_id][:3]

        return {
            'type': 'product_detail',
            'message': f'{product["name"]}',
            'product': formatted,
            'related': self._format_products(related) if related else [],
            'actions': [
                {'type': 'CATEGORY', 'label': 'More in Category', 'params': {'category_id': product.get('category_id')}},
                {'type': 'SEARCH', 'label': 'Search Products'}
            ]
        }

    def _handle_cheapest(self, params: Dict, user_id: str) -> Dict:
        """Get cheapest products"""
        category_id = params.get('category_id')
        limit = params.get('limit', 5)

        # Get products and sort by price
        products = self.db.get_products(category_id=category_id, limit=100)

        if not products:
            return {
                'type': 'no_results',
                'message': 'No products found.',
                'actions': [
                    {'type': 'CATEGORIES', 'label': 'Browse Categories'}
                ]
            }

        # Sort by price ascending
        sorted_products = sorted(products, key=lambda p: float(p.get('price', 0)))[:limit]

        message = 'Cheapest products'
        if category_id:
            categories = self.db.get_categories()
            category = next((c for c in categories if c['id'] == category_id), None)
            if category:
                message = f'Cheapest in {category["name"]}'

        return {
            'type': 'products',
            'message': f'{message}:',
            'items': self._format_products(sorted_products),
            'actions': [
                {'type': 'EXPENSIVE', 'label': 'Most Expensive'},
                {'type': 'CATEGORIES', 'label': 'Browse Categories'}
            ]
        }

    def _handle_expensive(self, params: Dict, user_id: str) -> Dict:
        """Get most expensive products"""
        category_id = params.get('category_id')
        limit = params.get('limit', 5)

        products = self.db.get_products(category_id=category_id, limit=100)

        if not products:
            return {
                'type': 'no_results',
                'message': 'No products found.',
                'actions': [
                    {'type': 'CATEGORIES', 'label': 'Browse Categories'}
                ]
            }

        # Sort by price descending
        sorted_products = sorted(products, key=lambda p: float(p.get('price', 0)), reverse=True)[:limit]

        message = 'Most expensive products'
        if category_id:
            categories = self.db.get_categories()
            category = next((c for c in categories if c['id'] == category_id), None)
            if category:
                message = f'Premium in {category["name"]}'

        return {
            'type': 'products',
            'message': f'{message}:',
            'items': self._format_products(sorted_products),
            'actions': [
                {'type': 'CHEAPEST', 'label': 'Cheapest'},
                {'type': 'CATEGORIES', 'label': 'Browse Categories'}
            ]
        }

    def _handle_featured(self, params: Dict, user_id: str) -> Dict:
        """Get featured/popular products"""
        limit = params.get('limit', 6)

        # Get all products and filter featured
        products = self.db.get_products(limit=100)
        featured = [p for p in products if p.get('is_featured')]

        # If not enough featured, add some regular products
        if len(featured) < limit:
            regular = [p for p in products if not p.get('is_featured')][:limit - len(featured)]
            featured.extend(regular)

        return {
            'type': 'products',
            'message': 'Popular products:',
            'items': self._format_products(featured[:limit]),
            'actions': [
                {'type': 'SEARCH', 'label': 'Search Products'},
                {'type': 'CATEGORIES', 'label': 'Browse Categories'}
            ]
        }

    def _handle_calculator(self, params: Dict, user_id: str) -> Dict:
        """Handle material calculator"""
        material_type = params.get('material_type')  # area, volume, linear
        dimensions = params.get('dimensions', {})
        product_id = params.get('product_id')

        if not material_type:
            # Show calculator options
            return {
                'type': 'calculator_options',
                'message': 'Choose calculation type:',
                'options': [
                    {'type': 'area', 'label': 'Area (m²)', 'description': 'Paint, tiles, flooring'},
                    {'type': 'volume', 'label': 'Volume (m³)', 'description': 'Concrete, fill materials'},
                    {'type': 'linear', 'label': 'Linear (m)', 'description': 'Lumber, pipes, wires'}
                ],
                'actions': [
                    {'type': 'SEARCH', 'label': 'Search Products'},
                    {'type': 'HELP', 'label': 'Help'}
                ]
            }

        # Check if we have dimensions
        if material_type == 'area' and ('length' not in dimensions or 'width' not in dimensions):
            return {
                'type': 'calculator_input',
                'message': 'Enter dimensions for area calculation:',
                'material_type': 'area',
                'required_fields': ['length', 'width'],
                'actions': [{'type': 'CALCULATOR', 'label': 'Cancel'}]
            }

        if material_type == 'volume' and ('length' not in dimensions or 'width' not in dimensions or 'depth' not in dimensions):
            return {
                'type': 'calculator_input',
                'message': 'Enter dimensions for volume calculation:',
                'material_type': 'volume',
                'required_fields': ['length', 'width', 'depth'],
                'actions': [{'type': 'CALCULATOR', 'label': 'Cancel'}]
            }

        if material_type == 'linear' and 'length' not in dimensions:
            return {
                'type': 'calculator_input',
                'message': 'Enter length:',
                'material_type': 'linear',
                'required_fields': ['length'],
                'actions': [{'type': 'CALCULATOR', 'label': 'Cancel'}]
            }

        # Calculate based on type
        if material_type == 'area':
            result = float(dimensions['length']) * float(dimensions['width'])
            unit = 'm²'
        elif material_type == 'volume':
            result = float(dimensions['length']) * float(dimensions['width']) * float(dimensions['depth'])
            unit = 'm³'
        else:  # linear
            result = float(dimensions['length'])
            unit = 'm'

        response = {
            'type': 'calculator_result',
            'message': f'Calculation result: {result:.2f} {unit}',
            'result': {
                'value': round(result, 2),
                'unit': unit,
                'dimensions': dimensions,
                'material_type': material_type
            },
            'actions': [
                {'type': 'SEARCH', 'label': 'Find Materials'},
                {'type': 'CALCULATOR', 'label': 'New Calculation'}
            ]
        }

        # If product specified, calculate cost
        if product_id:
            products = self.db.get_products(product_id=product_id, limit=1)
            if products:
                product = products[0]
                price = float(product.get('price', 0))
                total_cost = result * price
                response['result']['product'] = self._format_product_detail(product)
                response['result']['total_cost'] = round(total_cost, 2)
                response['message'] = f'You need {result:.2f} {unit} = ${total_cost:.2f}'

        return response

    def _handle_help(self, params: Dict, user_id: str) -> Dict:
        """Return help information"""
        return {
            'type': 'help',
            'message': 'How can I help you?',
            'commands': [
                {'command': 'SEARCH', 'icon': '🔍', 'label': 'Search', 'description': 'Find products by name'},
                {'command': 'CATEGORIES', 'icon': '📦', 'label': 'Categories', 'description': 'Browse by category'},
                {'command': 'FEATURED', 'icon': '⭐', 'label': 'Popular', 'description': 'Top products'},
                {'command': 'CHEAPEST', 'icon': '💰', 'label': 'Budget', 'description': 'Lowest prices'},
                {'command': 'CALCULATOR', 'icon': '📐', 'label': 'Calculator', 'description': 'Calculate materials'}
            ],
            'actions': [
                {'type': 'SEARCH', 'label': 'Start Searching'},
                {'type': 'CATEGORIES', 'label': 'Browse Categories'}
            ]
        }

    def _handle_popular_searches(self, params: Dict, user_id: str) -> Dict:
        """Return popular search terms"""
        return {
            'type': 'popular_searches',
            'message': 'Popular searches:',
            'searches': self.POPULAR_SEARCHES,
            'actions': [
                {'type': 'SEARCH', 'label': 'Custom Search'},
                {'type': 'CATEGORIES', 'label': 'Browse Categories'}
            ]
        }

    def _format_products(self, products: List[Dict]) -> List[Dict]:
        """Format products for response"""
        formatted = []
        for p in products:
            stock = p.get('stock_quantity', 0) or 0
            print(f"DEBUG _format_products: {p.get('name')} stock_quantity={p.get('stock_quantity')} -> stock={stock}")
            formatted.append({
                'id': p['id'],
                'name': p['name'],
                'description': p.get('description', '')[:100] + '...' if p.get('description') and len(p.get('description', '')) > 100 else p.get('description', ''),
                'price': f"${float(p.get('price', 0)):.2f}",
                'price_raw': float(p.get('price', 0)),
                'unit': p.get('unit', 'pc'),
                'category': p.get('category_name', ''),
                'supplier': p.get('supplier_name', ''),
                'in_stock': stock > 0,
                'stock_quantity': stock,
                'thumbnail': p.get('image_url', ''),
                'link': f"/product.php?id={p['id']}"
            })
        return formatted

    def _format_product_detail(self, product: Dict) -> Dict:
        """Format single product with full details"""
        dimensions = product.get('dimensions')
        if isinstance(dimensions, str):
            try:
                dimensions = json.loads(dimensions)
            except:
                dimensions = {}

        stock = product.get('stock_quantity', 0) or 0

        return {
            'id': product['id'],
            'name': product['name'],
            'description': product.get('description', ''),
            'price': f"${float(product.get('price', 0)):.2f}",
            'price_raw': float(product.get('price', 0)),
            'unit': product.get('unit', 'pc'),
            'category': product.get('category_name', ''),
            'category_id': product.get('category_id'),
            'supplier': product.get('supplier_name', ''),
            'in_stock': stock > 0,
            'stock_quantity': stock,
            'thumbnail': product.get('image_url', ''),
            'dimensions': dimensions,
            'is_featured': product.get('is_featured', False),
            'link': f"/product.php?id={product['id']}"
        }

    def _get_search_suggestions(self, keyword: str) -> List[str]:
        """Get search suggestions when no results found"""
        # Simple similarity check with popular searches
        keyword_lower = keyword.lower()
        suggestions = []

        for term in self.POPULAR_SEARCHES:
            if keyword_lower in term.lower() or term.lower() in keyword_lower:
                suggestions.append(term)

        # If no matches, return some popular searches
        if not suggestions:
            suggestions = self.POPULAR_SEARCHES[:3]

        return suggestions[:5]

    def _error_response(self, message: str) -> Dict:
        """Generate error response"""
        return {
            'type': 'error',
            'message': message,
            'actions': [
                {'type': 'HELP', 'label': 'Get Help'},
                {'type': 'SEARCH', 'label': 'Search Products'}
            ]
        }

    def get_initial_state(self) -> Dict:
        """Get initial chatbot state for new users"""
        return {
            'type': 'welcome',
            'message': 'Welcome to Construkt! How can I help you today?',
            'commands': [
                {'command': 'SEARCH', 'icon': '🔍', 'label': 'Search'},
                {'command': 'CATEGORIES', 'icon': '📦', 'label': 'Categories'},
                {'command': 'FEATURED', 'icon': '⭐', 'label': 'Popular'},
                {'command': 'CHEAPEST', 'icon': '💰', 'label': 'Budget'},
                {'command': 'CALCULATOR', 'icon': '📐', 'label': 'Calculator'},
                {'command': 'HELP', 'icon': '❓', 'label': 'Help'}
            ],
            'popular_searches': self.POPULAR_SEARCHES[:6],
            'actions': []
        }
