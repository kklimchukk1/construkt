"""
Construkt Chatbot - Flask API Server
Smart NLP-powered chatbot with unlimited context memory.
"""
import os
import re
import threading
import uuid
from flask import Flask, request, jsonify
from flask_cors import CORS
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

# Initialize Flask app
app = Flask(__name__)
CORS(app)

# Import modules
from src.nlp_engine import NLPEngine
from src.intents.calculator import CalculatorIntentHandler
from src.intents.store_info import handle_store_info, is_store_info_query
from src.gemini_ai import get_gemini_assistant
from src.command_handler import CommandHandler
from utils.database import DatabaseConnector

# Initialize components
database = DatabaseConnector()
calculator_handler = CalculatorIntentHandler()
command_handler = CommandHandler(database)

# Initialize NLP Engine with products from database
nlp_engine = None


def get_nlp_engine():
    """Get or initialize NLP engine with fresh product data"""
    global nlp_engine
    if nlp_engine is None:
        products = database.get_products(limit=500)
        categories = database.get_categories(with_product_counts=True)
        nlp_engine = NLPEngine(products, categories)
        print(f"NLP Engine initialized with {len(products)} products and {len(categories)} categories")
    return nlp_engine


def refresh_nlp_engine():
    """Refresh NLP engine with updated product data"""
    global nlp_engine
    products = database.get_products(limit=500)
    categories = database.get_categories(with_product_counts=True)
    nlp_engine = NLPEngine(products, categories)
    print(f"NLP Engine refreshed with {len(products)} products and {len(categories)} categories")
    return nlp_engine


def get_user_id(data: dict) -> str:
    """Get or generate user ID from request data"""
    user_id = data.get('user_id')
    if not user_id:
        user_id = str(uuid.uuid4())[:8]
    return str(user_id)


def log_conversation_async(user_id: str, user_message: str, bot_response: str, intent: str):
    """Log conversation to database asynchronously"""
    def _log():
        try:
            database.log_conversation(user_id, user_message, bot_response, intent)
        except Exception as e:
            print(f"Error logging conversation: {e}")

    thread = threading.Thread(target=_log)
    thread.start()


@app.route('/api/chatbot', methods=['POST', 'OPTIONS'])
@app.route('/api/chatbot/message', methods=['POST', 'OPTIONS'])
def process_message():
    """
    Main endpoint for processing chat messages.
    Uses smart NLP engine with unlimited context memory.
    """
    # Handle CORS preflight
    if request.method == 'OPTIONS':
        return '', 200

    try:
        data = request.get_json() or {}
        user_message = data.get('message', '').strip()
        user_id = get_user_id(data)

        if not user_message:
            return jsonify({
                'message': 'Please enter a message.',
                'intent': 'error',
                'confidence': 0,
                'user_id': user_id
            })

        print(f"\n{'='*50}")
        print(f"User [{user_id}]: {user_message}")
        print(f"{'='*50}")

        # Get NLP engine
        engine = get_nlp_engine()

        # Check for calculator-related intent first
        message_lower = user_message.lower()
        memory = engine.get_memory(user_id)

        # Check if we're in calculator mode or message contains calculator keywords
        in_calculator_mode = getattr(memory, 'calculator_state', None) == 'awaiting_dimensions'
        has_calculator_keywords = any(word in message_lower for word in ['calculate', 'how much', 'area', 'square meter', 'cubic', 'volume', 'm2', 'm3'])
        has_material_keywords = any(word in message_lower for word in ['paint', 'floor', 'concrete', 'wall', 'room', 'tile', 'brick'])
        has_dimensions = bool(re.search(r'\d+\s*[xх×*]\s*\d+|\d+\s*(?:m|м|meter)', message_lower))

        if in_calculator_mode or (has_calculator_keywords and has_material_keywords) or (in_calculator_mode and has_dimensions):
            # Use calculator handler for material calculations
            context = {
                'conversation_history': [m.get('content', '') for m in memory.messages[-10:]],
                'current_product': memory.current_product,
                'current_product_id': memory.current_product.get('id') if memory.current_product else None,
                'calculator_material_type': getattr(memory, 'calculator_material_type', None),
                'calculator_dimensions': getattr(memory, 'calculator_dimensions', {}),
                'calculator_state': getattr(memory, 'calculator_state', None)
            }

            calc_result = calculator_handler.handle_calculator_intent(user_message, context)
            response = calc_result.get('response', calc_result.get('message', ''))

            # Save calculator context update to memory
            if calc_result.get('data', {}).get('context_update'):
                ctx_update = calc_result['data']['context_update']
                memory.calculator_material_type = ctx_update.get('calculator_material_type')
                memory.calculator_dimensions = ctx_update.get('calculator_dimensions', {})
                memory.calculator_state = ctx_update.get('calculator_state')

            log_conversation_async(user_id, user_message, response, 'calculator')

            print(f"Bot (calculator): {response[:100]}...")
            return jsonify({
                'message': response,
                'intent': 'calculator',
                'confidence': 1.0,
                'data': calc_result.get('data', {}),
                'user_id': user_id
            })

        # Check for store information queries (hours, location, delivery, contacts)
        if is_store_info_query(user_message):
            store_response = handle_store_info(user_message)
            if store_response:
                log_conversation_async(user_id, user_message, store_response, 'store_info')
                print(f"Bot (store_info): {store_response[:100].encode('ascii', 'replace').decode()}...")
                return jsonify({
                    'message': store_response,
                    'intent': 'store_info',
                    'confidence': 1.0,
                    'user_id': user_id
                })

        # Process through NLP engine
        result = engine.process(user_message, user_id)

        response = result.get('response', '')
        intent = result.get('intent', 'unknown')
        products = result.get('products', [])

        # Use Gemini ONLY for truly unknown intents when NO products found
        # Do NOT use Gemini if products were found - use NLP response instead
        gemini = get_gemini_assistant()
        if gemini.is_enabled() and not products:
            memory = engine.get_memory(user_id)
            current_product = memory.current_product

            # Use Gemini ONLY when no products found AND (unknown intent OR error response)
            if intent == 'unknown' or 'Sorry' in response:
                gemini_response = gemini.generate_response(
                    user_message=user_message,
                    products=engine.products,
                    current_product=current_product,
                    conversation_history=memory.messages,
                    nlp_context=result
                )
                if gemini_response:
                    response = gemini_response
                    intent = 'gemini_ai'

        # Log conversation
        log_conversation_async(user_id, user_message, response, intent)

        # Build response
        response_data = {
            'message': response,
            'intent': intent,
            'confidence': 1.0,
            'user_id': user_id
        }

        # Include product data if available - format for widget rendering
        if products:
            # Debug: print product data
            for p in products[:2]:
                print(f"DEBUG product: {p.get('name')} - stock_quantity={p.get('stock_quantity')} - keys={list(p.keys())}")

            # Format products with links for clickable cards
            formatted_products = []
            for p in products[:6]:
                formatted_products.append({
                    'id': p.get('id'),
                    'name': p.get('name'),
                    'price': float(p.get('price', 0) or 0),
                    'unit': p.get('unit', 'piece'),
                    'stock_quantity': int(p.get('stock_quantity', 0) or 0),
                    'category_name': p.get('category_name', ''),
                    'thumbnail': p.get('thumbnail') or p.get('image_url') or '',
                    'link': f"/product.php?id={p.get('id')}"
                })
            response_data['data'] = {
                'type': 'products',
                'items': formatted_products,
                'product': formatted_products[0] if formatted_products else None
            }

        # Include categories if this was a fallback/unknown intent with category suggestions
        if result.get('type') == 'categories' or result.get('items'):
            items = result.get('items', [])
            if items:
                response_data['data'] = {
                    'type': 'categories',
                    'items': items
                }

        print(f"Bot: {response[:100]}...")
        return jsonify(response_data)

    except Exception as e:
        print(f"Error processing message: {e}")
        import traceback
        traceback.print_exc()
        return jsonify({
            'message': 'Sorry, I encountered an error processing your message. Please try again.',
            'intent': 'error',
            'confidence': 0,
            'error': str(e)
        }), 500


@app.route('/api/chatbot/history', methods=['GET'])
def get_history():
    """Get full conversation history for a user"""
    user_id = request.args.get('user_id')
    limit = request.args.get('limit', type=int)

    if not user_id:
        return jsonify({'error': 'user_id is required', 'history': []}), 400

    engine = get_nlp_engine()
    history = engine.get_conversation_history(user_id)

    if limit:
        history = history[-limit:]

    return jsonify({
        'history': history,
        'count': len(history),
        'user_id': user_id
    })


@app.route('/api/chatbot/context/clear', methods=['POST'])
def clear_context():
    """Clear conversation context for a user"""
    try:
        data = request.get_json() or {}
        user_id = data.get('user_id')

        if not user_id:
            return jsonify({
                'success': False,
                'message': 'user_id is required'
            }), 400

        engine = get_nlp_engine()
        engine.clear_memory(user_id)

        return jsonify({
            'success': True,
            'message': 'Conversation cleared successfully',
            'user_id': user_id
        })

    except Exception as e:
        print(f"Error clearing context: {e}")
        return jsonify({
            'success': False,
            'message': str(e)
        }), 500


@app.route('/api/chatbot/context', methods=['GET'])
def get_context():
    """Get current conversation context (for debugging)"""
    user_id = request.args.get('user_id')

    if not user_id:
        return jsonify({'error': 'user_id is required'}), 400

    engine = get_nlp_engine()
    memory = engine.get_memory(user_id)

    return jsonify({
        'user_id': user_id,
        'summary': memory.get_conversation_summary(),
        'message_count': len(memory.messages),
        'products_discussed': len(memory.mentioned_products),
        'current_product': memory.current_product.get('name') if memory.current_product else None
    })


@app.route('/api/chatbot/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    try:
        # Check database connection
        db_status = 'connected' if database.connect() else 'disconnected'

        # Check NLP engine
        engine = get_nlp_engine()
        engine_status = f"loaded ({len(engine.products)} products)"

        return jsonify({
            'status': 'ok',
            'database': db_status,
            'nlp_engine': engine_status,
            'version': '3.0.0'
        })

    except Exception as e:
        return jsonify({
            'status': 'error',
            'error': str(e)
        }), 500


@app.route('/api/chatbot/refresh', methods=['POST'])
def refresh_products():
    """Refresh product data in NLP engine"""
    try:
        refresh_nlp_engine()

        return jsonify({
            'success': True,
            'message': 'NLP engine refreshed with latest products'
        })

    except Exception as e:
        print(f"Error refreshing products: {e}")
        return jsonify({
            'success': False,
            'message': str(e)
        }), 500


@app.route('/api/chatbot/products', methods=['GET'])
def get_products():
    """Get products for testing/debugging"""
    try:
        limit = request.args.get('limit', 20, type=int)
        search = request.args.get('search')
        category_id = request.args.get('category_id', type=int)

        products = database.get_products(
            limit=limit,
            search=search,
            category_id=category_id
        )

        return jsonify({
            'products': products,
            'count': len(products)
        })

    except Exception as e:
        return jsonify({
            'error': str(e),
            'products': []
        }), 500


# ============================================
# COMMAND-BASED API ENDPOINTS
# ============================================

@app.route('/api/chatbot/command', methods=['POST', 'OPTIONS'])
def process_command():
    """
    Process structured commands instead of free-form text.
    All responses are based on actual database data.

    Request body:
    {
        "command": "SEARCH" | "CATEGORIES" | "CATEGORY" | "PRODUCT" | "CHEAPEST" | "FEATURED" | "CALCULATOR" | "HELP",
        "params": { ... command-specific params ... },
        "user_id": "optional user identifier"
    }

    Response:
    {
        "type": "products" | "categories" | "help" | "error" | ...,
        "message": "Human-readable message",
        "items": [...],  // products or categories
        "actions": [{ "type": "COMMAND", "label": "Button Label" }]
    }
    """
    # Handle CORS preflight
    if request.method == 'OPTIONS':
        return '', 200

    try:
        data = request.get_json() or {}
        command = data.get('command', '').strip().upper()
        params = data.get('params', {})
        user_id = get_user_id(data)

        if not command:
            return jsonify({
                'type': 'error',
                'message': 'Command is required',
                'actions': [{'type': 'HELP', 'label': 'Get Help'}]
            }), 400

        print(f"\n{'='*50}")
        print(f"Command [{user_id}]: {command}")
        print(f"Params: {params}")
        print(f"{'='*50}")

        # Execute command
        result = command_handler.execute(command, params, user_id)

        # Log command execution
        log_conversation_async(
            user_id,
            f"[CMD] {command}: {params}",
            result.get('message', ''),
            f"command_{command.lower()}"
        )

        print(f"Result type: {result.get('type')}")
        return jsonify(result)

    except Exception as e:
        print(f"Error processing command: {e}")
        import traceback
        traceback.print_exc()
        return jsonify({
            'type': 'error',
            'message': 'Sorry, I encountered an error processing your command.',
            'error': str(e),
            'actions': [{'type': 'HELP', 'label': 'Get Help'}]
        }), 500


@app.route('/api/chatbot/init', methods=['GET', 'POST', 'OPTIONS'])
def get_initial_state():
    """
    Get initial chatbot state with available commands.
    Call this when user opens the chatbot.
    """
    if request.method == 'OPTIONS':
        return '', 200

    try:
        data = request.get_json() or {} if request.method == 'POST' else {}
        user_id = get_user_id(data) if data else str(uuid.uuid4())[:8]

        initial_state = command_handler.get_initial_state()
        initial_state['user_id'] = user_id

        return jsonify(initial_state)

    except Exception as e:
        print(f"Error getting initial state: {e}")
        return jsonify({
            'type': 'error',
            'message': 'Failed to initialize chatbot',
            'error': str(e)
        }), 500


@app.route('/api/chatbot/categories', methods=['GET'])
def get_categories():
    """Get all product categories"""
    try:
        categories = database.get_categories(with_product_counts=True)

        return jsonify({
            'categories': categories,
            'count': len(categories)
        })

    except Exception as e:
        return jsonify({
            'error': str(e),
            'categories': []
        }), 500


# ============================================
# AUTH ENDPOINTS (For development without PHP backend)
# ============================================

@app.route('/api/auth/login', methods=['POST', 'OPTIONS'])
def auth_login():
    """Simple login endpoint for development"""
    if request.method == 'OPTIONS':
        return '', 200

    try:
        data = request.get_json() or {}
        email = data.get('email', '')
        password = data.get('password', '')

        if not email or not password:
            return jsonify({
                'status': 'error',
                'message': 'Email and password are required'
            }), 400

        # Check user in database
        user = database.get_user_by_email(email)

        if user and user.get('password') == password:
            # Generate simple token
            token = str(uuid.uuid4())

            return jsonify({
                'status': 'success',
                'data': {
                    'user': {
                        'id': user['id'],
                        'name': user.get('name', email.split('@')[0]),
                        'email': user['email'],
                        'role': user.get('role', 'user')
                    },
                    'token': token
                }
            })
        else:
            return jsonify({
                'status': 'error',
                'message': 'Invalid email or password'
            }), 401

    except Exception as e:
        print(f"Login error: {e}")
        return jsonify({
            'status': 'error',
            'message': str(e)
        }), 500


@app.route('/api/auth/register', methods=['POST', 'OPTIONS'])
def auth_register():
    """Simple register endpoint for development"""
    if request.method == 'OPTIONS':
        return '', 200

    try:
        data = request.get_json() or {}
        email = data.get('email', '')
        password = data.get('password', '')
        name = data.get('name', '')

        if not email or not password:
            return jsonify({
                'status': 'error',
                'message': 'Email and password are required'
            }), 400

        # Check if user exists
        existing = database.get_user_by_email(email)
        if existing:
            return jsonify({
                'status': 'error',
                'message': 'User with this email already exists'
            }), 400

        # Create user
        user_id = database.create_user(email, password, name)

        if user_id:
            token = str(uuid.uuid4())
            return jsonify({
                'status': 'success',
                'data': {
                    'user': {
                        'id': user_id,
                        'name': name or email.split('@')[0],
                        'email': email,
                        'role': 'user'
                    },
                    'token': token
                }
            })
        else:
            return jsonify({
                'status': 'error',
                'message': 'Failed to create user'
            }), 500

    except Exception as e:
        print(f"Register error: {e}")
        return jsonify({
            'status': 'error',
            'message': str(e)
        }), 500


# ============================================
# PRODUCTS API (For development without PHP backend)
# ============================================

@app.route('/api/products', methods=['GET'])
def api_products():
    """Get products for the main site (replacing PHP endpoint)"""
    try:
        limit = request.args.get('limit', 50, type=int)
        category_id = request.args.get('category_id', type=int)
        search = request.args.get('search')

        products = database.get_products(
            limit=limit,
            search=search,
            category_id=category_id
        )

        # Format products for frontend (convert Decimal to float, etc.)
        formatted_products = []
        for p in products:
            formatted_products.append({
                'id': p['id'],
                'name': p['name'],
                'description': p.get('description', ''),
                'price': float(p['price']) if p.get('price') else 0,
                'unit': p.get('unit', 'piece'),
                'category_id': p.get('category_id'),
                'category_name': p.get('category_name', 'General'),
                'supplier_id': p.get('supplier_id'),
                'supplier_name': p.get('supplier_name', ''),
                'stock_quantity': int(p.get('stock_quantity', 0) or 0),
                'is_featured': p.get('is_featured', 0),
                'thumbnail': p.get('thumbnail') or p.get('image_url') or '',
                'dimensions': p.get('dimensions'),
                'calculation_type': p.get('calculation_type', 'unit')
            })

        return jsonify({
            'status': 'success',
            'data': {
                'products': formatted_products,
                'total': len(formatted_products)
            }
        })

    except Exception as e:
        return jsonify({
            'status': 'error',
            'message': str(e),
            'data': {'products': []}
        }), 500


@app.route('/api/products/<int:product_id>', methods=['GET'])
def api_product_detail(product_id):
    """Get single product details"""
    try:
        product = database.get_product_by_id(product_id)

        if product:
            return jsonify({
                'status': 'success',
                'data': {'product': product}
            })
        else:
            return jsonify({
                'status': 'error',
                'message': 'Product not found'
            }), 404

    except Exception as e:
        return jsonify({
            'status': 'error',
            'message': str(e)
        }), 500


@app.route('/api/categories', methods=['GET'])
def api_categories():
    """Get all categories for the main site"""
    try:
        categories = database.get_categories(with_product_counts=True)

        return jsonify({
            'status': 'success',
            'data': {'categories': categories}
        })

    except Exception as e:
        return jsonify({
            'status': 'error',
            'message': str(e),
            'data': {'categories': []}
        }), 500


# ============================================
# CART API
# ============================================

@app.route('/api/cart', methods=['GET'])
def get_cart():
    """Get user's cart"""
    try:
        user_id = request.args.get('user_id') or request.headers.get('X-User-Id')
        cart_items = database.get_cart(user_id) if user_id else []
        return jsonify({'data': cart_items})
    except Exception as e:
        return jsonify({'data': [], 'error': str(e)})


@app.route('/api/cart', methods=['POST'])
def add_to_cart():
    """Add item to cart"""
    try:
        data = request.get_json() or {}
        user_id = data.get('user_id') or request.headers.get('X-User-Id')
        product_id = data.get('product_id')
        quantity = data.get('quantity', 1)

        if not user_id or not product_id:
            return jsonify({'error': 'user_id and product_id required'}), 400

        result = database.add_to_cart(user_id, product_id, quantity)
        return jsonify({'success': True, 'data': result})
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@app.route('/api/cart/<int:item_id>', methods=['PUT'])
def update_cart_item(item_id):
    """Update cart item quantity"""
    try:
        data = request.get_json() or {}
        quantity = data.get('quantity', 1)
        database.update_cart_item(item_id, quantity)
        return jsonify({'success': True})
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@app.route('/api/cart/<int:item_id>', methods=['DELETE'])
def remove_from_cart(item_id):
    """Remove item from cart"""
    try:
        database.remove_from_cart(item_id)
        return jsonify({'success': True})
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@app.route('/api/cart', methods=['DELETE'])
def clear_cart():
    """Clear user's cart"""
    try:
        user_id = request.args.get('user_id') or request.headers.get('X-User-Id')
        if user_id:
            database.clear_cart(user_id)
        return jsonify({'success': True})
    except Exception as e:
        return jsonify({'error': str(e)}), 500


# ============================================
# ORDERS API
# ============================================

@app.route('/api/orders', methods=['GET'])
def get_orders():
    """Get all orders (for managers/admins)"""
    try:
        orders = database.get_all_orders()
        return jsonify({'data': orders})
    except Exception as e:
        return jsonify({'data': [], 'error': str(e)})


@app.route('/api/orders/my', methods=['GET'])
def get_my_orders():
    """Get current user's orders"""
    try:
        user_id = request.args.get('user_id') or request.headers.get('X-User-Id')
        orders = database.get_user_orders(user_id) if user_id else []
        return jsonify({'data': orders})
    except Exception as e:
        return jsonify({'data': [], 'error': str(e)})


@app.route('/api/orders', methods=['POST'])
def create_order():
    """Create new order from cart"""
    try:
        data = request.get_json() or {}
        user_id = data.get('user_id') or request.headers.get('X-User-Id')
        shipping_address = data.get('shipping_address', '')
        notes = data.get('notes', '')

        if not user_id:
            return jsonify({'error': 'user_id required'}), 400

        order_id = database.create_order(user_id, shipping_address, notes)
        if order_id:
            database.clear_cart(user_id)
            return jsonify({'success': True, 'order_id': order_id})
        return jsonify({'error': 'Failed to create order'}), 500
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@app.route('/api/orders/<int:order_id>', methods=['GET'])
def get_order(order_id):
    """Get order details"""
    try:
        order = database.get_order(order_id)
        if order:
            return jsonify({'data': order})
        return jsonify({'error': 'Order not found'}), 404
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@app.route('/api/orders/<int:order_id>/status', methods=['PUT'])
def update_order_status(order_id):
    """Update order status"""
    try:
        data = request.get_json() or {}
        status = data.get('status')
        if not status:
            return jsonify({'error': 'status required'}), 400

        database.update_order_status(order_id, status)
        return jsonify({'success': True})
    except Exception as e:
        return jsonify({'error': str(e)}), 500


# ============================================
# SUPPORT CHAT API
# ============================================

@app.route('/api/support/messages', methods=['GET'])
def get_support_messages():
    """Get user's support messages"""
    try:
        user_id = request.args.get('user_id') or request.headers.get('X-User-Id')
        messages = database.get_support_messages(user_id) if user_id else []
        return jsonify({'data': messages})
    except Exception as e:
        return jsonify({'data': [], 'error': str(e)})


@app.route('/api/support/messages/<int:customer_id>', methods=['GET'])
def get_customer_messages(customer_id):
    """Get messages for specific customer (for managers)"""
    try:
        messages = database.get_support_messages(customer_id)
        return jsonify({'data': messages})
    except Exception as e:
        return jsonify({'data': [], 'error': str(e)})


@app.route('/api/support/messages', methods=['POST'])
def send_support_message():
    """Send support message"""
    try:
        data = request.get_json() or {}
        customer_id = data.get('customer_id') or data.get('user_id') or request.headers.get('X-User-Id')
        message = data.get('message', '')
        is_from_customer = data.get('is_from_customer', True)
        manager_id = data.get('manager_id')

        if not customer_id or not message:
            return jsonify({'error': 'customer_id and message required'}), 400

        database.send_support_message(customer_id, message, is_from_customer, manager_id)
        return jsonify({'success': True})
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@app.route('/api/support/chats', methods=['GET'])
def get_support_chats():
    """Get all support chats (for managers)"""
    try:
        chats = database.get_support_chats()
        return jsonify({'data': chats})
    except Exception as e:
        return jsonify({'data': [], 'error': str(e)})


# ============================================
# CRUD API FOR PRODUCTS
# ============================================

@app.route('/api/products', methods=['POST'])
def create_product():
    """Create new product"""
    try:
        data = request.get_json() or {}
        print(f"Creating product with data: {data}")
        product_id = database.create_product(data)
        print(f"Product created with id: {product_id}")
        if product_id:
            refresh_nlp_engine()
            return jsonify({'success': True, 'id': product_id})
        return jsonify({'error': 'Failed to create product'}), 500
    except Exception as e:
        print(f"Error creating product: {e}")
        import traceback
        traceback.print_exc()
        return jsonify({'error': str(e)}), 500


@app.route('/api/products/<int:product_id>', methods=['PUT'])
def update_product(product_id):
    """Update product"""
    try:
        data = request.get_json() or {}
        database.update_product(product_id, data)
        refresh_nlp_engine()
        return jsonify({'success': True})
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@app.route('/api/products/<int:product_id>', methods=['DELETE'])
def delete_product(product_id):
    """Delete product"""
    try:
        database.delete_product(product_id)
        refresh_nlp_engine()
        return jsonify({'success': True})
    except Exception as e:
        return jsonify({'error': str(e)}), 500


# ============================================
# CRUD API FOR CATEGORIES
# ============================================

@app.route('/api/categories', methods=['POST'])
def create_category():
    """Create new category"""
    try:
        data = request.get_json() or {}
        category_id = database.create_category(data)
        if category_id:
            return jsonify({'success': True, 'id': category_id})
        return jsonify({'error': 'Failed to create category'}), 500
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@app.route('/api/categories/<int:category_id>', methods=['PUT'])
def update_category(category_id):
    """Update category"""
    try:
        data = request.get_json() or {}
        database.update_category(category_id, data)
        return jsonify({'success': True})
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@app.route('/api/categories/<int:category_id>', methods=['DELETE'])
def delete_category(category_id):
    """Delete category"""
    try:
        database.delete_category(category_id)
        return jsonify({'success': True})
    except Exception as e:
        return jsonify({'error': str(e)}), 500


# ============================================
# USER MANAGEMENT API (Admin)
# ============================================

@app.route('/api/users', methods=['GET'])
def get_users():
    """Get all users (admin only)"""
    try:
        users = database.get_all_users()
        return jsonify({'data': users})
    except Exception as e:
        return jsonify({'data': [], 'error': str(e)})


@app.route('/api/users/<int:user_id>/role', methods=['PUT'])
def update_user_role(user_id):
    """Update user role"""
    try:
        data = request.get_json() or {}
        role = data.get('role')
        if not role:
            return jsonify({'error': 'role required'}), 400

        database.update_user_role(user_id, role)
        return jsonify({'success': True})
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@app.route('/api/users/<int:user_id>/status', methods=['PUT'])
def update_user_status(user_id):
    """Update user active status"""
    try:
        data = request.get_json() or {}
        is_active = data.get('is_active', True)
        database.update_user_status(user_id, is_active)
        return jsonify({'success': True})
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@app.route('/api/users/<int:user_id>', methods=['DELETE'])
def delete_user(user_id):
    """Delete user"""
    try:
        database.delete_user(user_id)
        return jsonify({'success': True})
    except Exception as e:
        return jsonify({'error': str(e)}), 500


if __name__ == '__main__':
    # Run Flask app on port 5000 (chatbot API)
    port = int(os.environ.get('CHATBOT_PORT', 5000))
    print(f"\n{'='*50}")
    print(f"Construkt Smart Chatbot v3.0")
    print(f"Features: Fuzzy matching, Smart recommendations,")
    print(f"          Product comparison, Price calculation,")
    print(f"          Unlimited context memory")
    print(f"Starting on port {port}")
    print(f"{'='*50}\n")

    # Initialize engine on startup
    get_nlp_engine()

    app.run(host='0.0.0.0', port=port, debug=False)
