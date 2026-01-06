"""
Database connector for the chatbot using MySQL (same DB as main site)
"""
import os
import time
import mysql.connector
from mysql.connector import Error


# MySQL connection settings
DB_CONFIG = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'user': os.getenv('DB_USER', 'root'),
    'password': os.getenv('DB_PASSWORD', ''),
    'database': os.getenv('DB_NAME', 'construkt'),
    'port': int(os.getenv('DB_PORT', 3306)),
    'charset': 'utf8mb4',
    'autocommit': True
}


class DatabaseConnector:
    """
    Handles MySQL database connections for the chatbot
    """

    def __init__(self):
        self.connection = None
        print(f"MySQL Config: {DB_CONFIG['host']}:{DB_CONFIG['port']}/{DB_CONFIG['database']}")

    def connect(self):
        """Connect to MySQL database"""
        try:
            if self.connection and self.connection.is_connected():
                return True

            self.connection = mysql.connector.connect(**DB_CONFIG)
            print("MySQL database connection successful!")
            return True
        except Error as e:
            print(f"Database connection error: {e}")
            return False

    def disconnect(self):
        """Close the database connection"""
        if self.connection and self.connection.is_connected():
            self.connection.close()
            self.connection = None

    def ensure_connection(self):
        """Ensure database connection is alive"""
        try:
            if self.connection is None or not self.connection.is_connected():
                return self.connect()
            # Test connection with ping
            self.connection.ping(reconnect=True)
            return True
        except Error as e:
            print(f"Connection lost, reconnecting: {e}")
            self.connection = None
            return self.connect()

    def _dict_from_row(self, cursor, row):
        """Convert row to dict using cursor description"""
        if row is None:
            return None
        columns = [col[0] for col in cursor.description]
        return dict(zip(columns, row))

    def _dicts_from_rows(self, cursor, rows):
        """Convert list of rows to list of dicts"""
        columns = [col[0] for col in cursor.description]
        return [dict(zip(columns, row)) for row in rows]

    def get_products(self, limit=10, category_id=None, search=None, product_id=None):
        """Get products from the database"""
        try:
            if not self.ensure_connection():
                print("Failed to connect to database")
                return []

            query = """
                SELECT p.*, c.name as category_name, s.company_name as supplier_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                WHERE p.is_active = 1
            """
            params = []

            if product_id is not None:
                query += " AND p.id = %s"
                params.append(product_id)

            if category_id is not None:
                query += " AND p.category_id = %s"
                params.append(category_id)

            if search:
                query += " AND (p.name LIKE %s OR p.description LIKE %s)"
                search_term = f"%{search}%"
                params.append(search_term)
                params.append(search_term)

            query += f" ORDER BY p.name ASC LIMIT {int(limit)}"

            cursor = self.connection.cursor()
            cursor.execute(query, params)
            rows = cursor.fetchall()
            products = self._dicts_from_rows(cursor, rows)
            cursor.close()

            print(f"Database returned {len(products)} products")
            return products
        except Error as e:
            print(f"Error getting products: {e}")
            return []

    def get_categories(self, with_product_counts=False):
        """Get all product categories"""
        try:
            if not self.ensure_connection():
                return []

            if with_product_counts:
                query = """
                    SELECT c.*, COUNT(p.id) as product_count
                    FROM categories c
                    LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
                    GROUP BY c.id
                    ORDER BY c.name
                """
            else:
                query = "SELECT * FROM categories ORDER BY name"

            cursor = self.connection.cursor()
            cursor.execute(query)
            rows = cursor.fetchall()
            result = self._dicts_from_rows(cursor, rows)
            cursor.close()
            return result
        except Error as e:
            print(f"Error getting categories: {e}")
            return []

    def get_suppliers(self, limit=10):
        """Get suppliers from the database"""
        try:
            if not self.ensure_connection():
                return []

            query = f"SELECT * FROM suppliers ORDER BY company_name LIMIT {int(limit)}"
            cursor = self.connection.cursor()
            cursor.execute(query)
            rows = cursor.fetchall()
            result = self._dicts_from_rows(cursor, rows)
            cursor.close()
            return result
        except Error as e:
            print(f"Error getting suppliers: {e}")
            return []

    def log_conversation(self, user_id, user_message, bot_response, intent=None):
        """Log a conversation to database"""
        try:
            if not self.ensure_connection():
                return self._log_to_file(user_id, user_message, bot_response, intent)

            cursor = self.connection.cursor()
            cursor.execute("""
                INSERT INTO conversation_logs (user_id, user_message, bot_response, intent)
                VALUES (%s, %s, %s, %s)
            """, (str(user_id), user_message, bot_response, intent))
            cursor.close()
            return True
        except Error as e:
            print(f"Error logging to DB: {e}")
            return self._log_to_file(user_id, user_message, bot_response, intent)

    def _log_to_file(self, user_id, user_message, bot_response, intent=None):
        """Log conversation to a file (fallback)"""
        try:
            log_dir = os.path.join(os.path.dirname(os.path.dirname(__file__)), 'logs')
            os.makedirs(log_dir, exist_ok=True)

            log_file = os.path.join(log_dir, f"chatbot_{time.strftime('%Y-%m-%d')}.log")

            timestamp = time.strftime('%Y-%m-%d %H:%M:%S')
            log_entry = f"[{timestamp}] User ID: {user_id}, Intent: {intent or 'unknown'}\n"
            log_entry += f"User: {user_message}\n"
            log_entry += f"Bot: {bot_response}\n\n"

            with open(log_file, 'a', encoding='utf-8') as f:
                f.write(log_entry)

            return True
        except Exception as e:
            print(f"Error logging to file: {e}")
            return False

    def get_product_by_id(self, product_id):
        """Get a single product by ID"""
        try:
            if not self.ensure_connection():
                return None

            query = """
                SELECT p.*, c.name as category_name, s.company_name as supplier_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                WHERE p.id = %s AND p.is_active = 1
            """

            cursor = self.connection.cursor()
            cursor.execute(query, (product_id,))
            row = cursor.fetchone()
            result = self._dict_from_row(cursor, row)
            cursor.close()
            return result
        except Error as e:
            print(f"Error getting product by ID: {e}")
            return None

    def get_user_by_email(self, email):
        """Get user by email"""
        try:
            if not self.ensure_connection():
                return None

            cursor = self.connection.cursor()
            cursor.execute("SELECT * FROM users WHERE email = %s", (email,))
            row = cursor.fetchone()
            result = self._dict_from_row(cursor, row)
            cursor.close()
            return result
        except Error as e:
            print(f"Error getting user: {e}")
            return None

    def create_user(self, email, password, name=''):
        """Create new user"""
        try:
            if not self.ensure_connection():
                return None

            cursor = self.connection.cursor()
            cursor.execute(
                "INSERT INTO users (email, password, first_name, last_name, role, is_active) VALUES (%s, %s, %s, '', 'customer', 1)",
                (email, password, name)
            )
            user_id = cursor.lastrowid
            cursor.close()
            return user_id
        except Error as e:
            print(f"Error creating user: {e}")
            return None

    def get_cart(self, user_id):
        """Get user's cart items"""
        try:
            if not self.ensure_connection():
                return []

            query = """
                SELECT ci.*, p.name as product_name, p.price, p.image_url
                FROM cart_items ci
                JOIN products p ON ci.product_id = p.id
                WHERE ci.user_id = %s
            """
            cursor = self.connection.cursor()
            cursor.execute(query, (user_id,))
            rows = cursor.fetchall()
            result = self._dicts_from_rows(cursor, rows)
            cursor.close()
            return result
        except Error as e:
            print(f"Error getting cart: {e}")
            return []

    def add_to_cart(self, user_id, product_id, quantity=1):
        """Add item to cart"""
        try:
            if not self.ensure_connection():
                return None

            cursor = self.connection.cursor()

            # Check if item already in cart
            cursor.execute(
                "SELECT id, quantity FROM cart_items WHERE user_id = %s AND product_id = %s",
                (user_id, product_id)
            )
            existing = cursor.fetchone()

            if existing:
                new_qty = existing[1] + quantity
                cursor.execute(
                    "UPDATE cart_items SET quantity = %s WHERE id = %s",
                    (new_qty, existing[0])
                )
                result = existing[0]
            else:
                cursor.execute(
                    "INSERT INTO cart_items (user_id, product_id, quantity) VALUES (%s, %s, %s)",
                    (user_id, product_id, quantity)
                )
                result = cursor.lastrowid

            cursor.close()
            return result
        except Error as e:
            print(f"Error adding to cart: {e}")
            return None

    def update_cart_item(self, item_id, quantity):
        """Update cart item quantity"""
        try:
            if not self.ensure_connection():
                return False

            cursor = self.connection.cursor()
            cursor.execute("UPDATE cart_items SET quantity = %s WHERE id = %s", (quantity, item_id))
            cursor.close()
            return True
        except Error as e:
            print(f"Error updating cart: {e}")
            return False

    def remove_from_cart(self, item_id):
        """Remove item from cart"""
        try:
            if not self.ensure_connection():
                return False

            cursor = self.connection.cursor()
            cursor.execute("DELETE FROM cart_items WHERE id = %s", (item_id,))
            cursor.close()
            return True
        except Error as e:
            print(f"Error removing from cart: {e}")
            return False

    def clear_cart(self, user_id):
        """Clear user's cart"""
        try:
            if not self.ensure_connection():
                return False

            cursor = self.connection.cursor()
            cursor.execute("DELETE FROM cart_items WHERE user_id = %s", (user_id,))
            cursor.close()
            return True
        except Error as e:
            print(f"Error clearing cart: {e}")
            return False

    def get_user_orders(self, user_id):
        """Get user's orders"""
        try:
            if not self.ensure_connection():
                return []

            cursor = self.connection.cursor()
            cursor.execute(
                "SELECT * FROM orders WHERE user_id = %s ORDER BY created_at DESC",
                (user_id,)
            )
            rows = cursor.fetchall()
            orders = self._dicts_from_rows(cursor, rows)

            for order in orders:
                cursor.execute("""
                    SELECT oi.*, p.name as product_name
                    FROM order_items oi
                    JOIN products p ON oi.product_id = p.id
                    WHERE oi.order_id = %s
                """, (order['id'],))
                item_rows = cursor.fetchall()
                order['items'] = self._dicts_from_rows(cursor, item_rows)

            cursor.close()
            return orders
        except Error as e:
            print(f"Error getting user orders: {e}")
            return []

    def get_all_orders(self):
        """Get all orders (for managers)"""
        try:
            if not self.ensure_connection():
                return []

            cursor = self.connection.cursor()
            cursor.execute("""
                SELECT o.*, u.email as user_email, u.first_name, u.last_name
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                ORDER BY o.created_at DESC
            """)
            rows = cursor.fetchall()
            result = self._dicts_from_rows(cursor, rows)
            cursor.close()
            return result
        except Error as e:
            print(f"Error getting orders: {e}")
            return []

    def get_order(self, order_id):
        """Get order details"""
        try:
            if not self.ensure_connection():
                return None

            cursor = self.connection.cursor()
            cursor.execute("SELECT * FROM orders WHERE id = %s", (order_id,))
            row = cursor.fetchone()
            order = self._dict_from_row(cursor, row)

            if order:
                cursor.execute("""
                    SELECT oi.*, p.name as product_name
                    FROM order_items oi
                    JOIN products p ON oi.product_id = p.id
                    WHERE oi.order_id = %s
                """, (order_id,))
                item_rows = cursor.fetchall()
                order['items'] = self._dicts_from_rows(cursor, item_rows)

            cursor.close()
            return order
        except Error as e:
            print(f"Error getting order: {e}")
            return None

    def create_order(self, user_id, shipping_address='', notes=''):
        """Create order from cart"""
        try:
            if not self.ensure_connection():
                return None

            cart_items = self.get_cart(user_id)
            if not cart_items:
                return None

            total = sum(float(item['price']) * item['quantity'] for item in cart_items)

            cursor = self.connection.cursor()
            cursor.execute("""
                INSERT INTO orders (user_id, total_amount, status, shipping_address, notes)
                VALUES (%s, %s, 'pending', %s, %s)
            """, (user_id, total, shipping_address, notes))
            order_id = cursor.lastrowid

            for item in cart_items:
                cursor.execute("""
                    INSERT INTO order_items (order_id, product_id, quantity, price)
                    VALUES (%s, %s, %s, %s)
                """, (order_id, item['product_id'], item['quantity'], item['price']))

            cursor.close()
            return order_id
        except Error as e:
            print(f"Error creating order: {e}")
            return None

    def update_order_status(self, order_id, status):
        """Update order status"""
        try:
            if not self.ensure_connection():
                return False

            cursor = self.connection.cursor()
            cursor.execute("UPDATE orders SET status = %s WHERE id = %s", (status, order_id))
            cursor.close()
            return True
        except Error as e:
            print(f"Error updating order: {e}")
            return False

    # ============================================
    # SUPPORT MESSAGES
    # ============================================

    def get_support_messages(self, customer_id):
        """Get support messages for a customer"""
        try:
            if not self.ensure_connection():
                return []

            cursor = self.connection.cursor()
            cursor.execute("""
                SELECT sm.*, u.first_name as manager_name
                FROM support_messages sm
                LEFT JOIN users u ON sm.manager_id = u.id
                WHERE sm.customer_id = %s
                ORDER BY sm.created_at ASC
            """, (customer_id,))
            rows = cursor.fetchall()
            result = self._dicts_from_rows(cursor, rows)
            cursor.close()
            return result
        except Error as e:
            print(f"Error getting support messages: {e}")
            return []

    def send_support_message(self, customer_id, message, is_from_customer=True, manager_id=None):
        """Send a support message"""
        try:
            if not self.ensure_connection():
                return False

            cursor = self.connection.cursor()
            cursor.execute("""
                INSERT INTO support_messages (customer_id, manager_id, message, is_from_customer)
                VALUES (%s, %s, %s, %s)
            """, (customer_id, manager_id, message, 1 if is_from_customer else 0))
            cursor.close()
            return True
        except Error as e:
            print(f"Error sending support message: {e}")
            return False

    def get_support_chats(self):
        """Get all support chats (for managers)"""
        try:
            if not self.ensure_connection():
                return []

            cursor = self.connection.cursor()
            cursor.execute("""
                SELECT u.id, u.email, u.first_name, u.last_name,
                       COUNT(sm.id) as message_count,
                       MAX(sm.created_at) as last_message_at,
                       SUM(CASE WHEN sm.is_read = 0 AND sm.is_from_customer = 1 THEN 1 ELSE 0 END) as unread_count
                FROM users u
                JOIN support_messages sm ON u.id = sm.customer_id
                GROUP BY u.id
                ORDER BY last_message_at DESC
            """)
            rows = cursor.fetchall()
            result = self._dicts_from_rows(cursor, rows)
            cursor.close()
            return result
        except Error as e:
            print(f"Error getting support chats: {e}")
            return []

    # ============================================
    # CRUD OPERATIONS
    # ============================================

    def create_product(self, data):
        """Create new product"""
        try:
            if not self.ensure_connection():
                return None

            cursor = self.connection.cursor()
            cursor.execute("""
                INSERT INTO products (name, description, price, unit, stock_quantity, category_id, supplier_id, image_url, is_active)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, 1)
            """, (
                data.get('name'),
                data.get('description', ''),
                data.get('price', 0),
                data.get('unit', 'pc'),
                data.get('stock_quantity', 0),
                data.get('category_id'),
                data.get('supplier_id'),
                data.get('image_url', '')
            ))
            product_id = cursor.lastrowid
            cursor.close()
            return product_id
        except Error as e:
            print(f"Error creating product: {e}")
            return None

    def update_product(self, product_id, data):
        """Update product"""
        try:
            if not self.ensure_connection():
                return False

            fields = []
            values = []
            for key in ['name', 'description', 'price', 'unit', 'stock', 'category_id', 'supplier_id', 'image_url', 'is_active']:
                if key in data:
                    fields.append(f"{key} = %s")
                    values.append(data[key])

            if not fields:
                return False

            values.append(product_id)
            query = f"UPDATE products SET {', '.join(fields)} WHERE id = %s"

            cursor = self.connection.cursor()
            cursor.execute(query, values)
            cursor.close()
            return True
        except Error as e:
            print(f"Error updating product: {e}")
            return False

    def delete_product(self, product_id):
        """Delete product (soft delete)"""
        try:
            if not self.ensure_connection():
                return False

            cursor = self.connection.cursor()
            cursor.execute("UPDATE products SET is_active = 0 WHERE id = %s", (product_id,))
            cursor.close()
            return True
        except Error as e:
            print(f"Error deleting product: {e}")
            return False

    def create_category(self, data):
        """Create new category"""
        try:
            if not self.ensure_connection():
                return None

            cursor = self.connection.cursor()
            cursor.execute(
                "INSERT INTO categories (name, description, image_url) VALUES (%s, %s, %s)",
                (data.get('name'), data.get('description', ''), data.get('image_url', ''))
            )
            category_id = cursor.lastrowid
            cursor.close()
            return category_id
        except Error as e:
            print(f"Error creating category: {e}")
            return None

    def update_category(self, category_id, data):
        """Update category"""
        try:
            if not self.ensure_connection():
                return False

            cursor = self.connection.cursor()
            cursor.execute(
                "UPDATE categories SET name = %s, description = %s, image_url = %s WHERE id = %s",
                (data.get('name'), data.get('description', ''), data.get('image_url', ''), category_id)
            )
            cursor.close()
            return True
        except Error as e:
            print(f"Error updating category: {e}")
            return False

    def delete_category(self, category_id):
        """Delete category"""
        try:
            if not self.ensure_connection():
                return False

            cursor = self.connection.cursor()
            cursor.execute("DELETE FROM categories WHERE id = %s", (category_id,))
            cursor.close()
            return True
        except Error as e:
            print(f"Error deleting category: {e}")
            return False

    # ============================================
    # USER MANAGEMENT
    # ============================================

    def get_all_users(self):
        """Get all users"""
        try:
            if not self.ensure_connection():
                return []

            cursor = self.connection.cursor()
            cursor.execute(
                "SELECT id, email, first_name, last_name, role, phone, is_active, created_at FROM users ORDER BY id"
            )
            rows = cursor.fetchall()
            result = self._dicts_from_rows(cursor, rows)
            cursor.close()
            return result
        except Error as e:
            print(f"Error getting users: {e}")
            return []

    def update_user_role(self, user_id, role):
        """Update user role"""
        try:
            if not self.ensure_connection():
                return False

            cursor = self.connection.cursor()
            cursor.execute("UPDATE users SET role = %s WHERE id = %s", (role, user_id))
            cursor.close()
            return True
        except Error as e:
            print(f"Error updating user role: {e}")
            return False

    def update_user_status(self, user_id, is_active):
        """Update user status"""
        try:
            if not self.ensure_connection():
                return False

            cursor = self.connection.cursor()
            cursor.execute("UPDATE users SET is_active = %s WHERE id = %s", (1 if is_active else 0, user_id))
            cursor.close()
            return True
        except Error as e:
            print(f"Error updating user status: {e}")
            return False

    def delete_user(self, user_id):
        """Delete user"""
        try:
            if not self.ensure_connection():
                return False

            cursor = self.connection.cursor()
            cursor.execute("DELETE FROM users WHERE id = %s", (user_id,))
            cursor.close()
            return True
        except Error as e:
            print(f"Error deleting user: {e}")
            return False
