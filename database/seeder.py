"""
Database Seeder for Construkt (MySQL)
Populates the database with test data.
Includes validation to prevent duplicate data.
"""
import os
import sys
from pathlib import Path
import mysql.connector
from mysql.connector import Error

sys.path.insert(0, str(Path(__file__).parent.parent))
from dotenv import load_dotenv

env_path = Path(__file__).parent.parent / 'chatbot' / '.env'
load_dotenv(env_path)


class DatabaseSeeder:
    """Seeds the database with test data (no duplicates)"""

    def __init__(self):
        self.conn = None
        self.cursor = None
        self.stats = {'users': 0, 'categories': 0, 'suppliers': 0, 'products': 0, 'skipped': 0}

    def connect(self):
        """Connect to MySQL database"""
        host = os.getenv('DB_HOST', 'localhost')
        port = int(os.getenv('DB_PORT', 3306))
        user = os.getenv('DB_USER', 'root')
        password = os.getenv('DB_PASSWORD', '')
        database = os.getenv('DB_NAME', 'construkt')

        # Create database if not exists
        conn_temp = mysql.connector.connect(
            host=host, port=port, user=user, password=password, charset='utf8mb4'
        )
        cursor_temp = conn_temp.cursor()
        cursor_temp.execute(f"CREATE DATABASE IF NOT EXISTS `{database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")
        conn_temp.commit()
        cursor_temp.close()
        conn_temp.close()

        # Connect to database
        self.conn = mysql.connector.connect(
            host=host, port=port, user=user, password=password,
            database=database, charset='utf8mb4', autocommit=True
        )
        self.cursor = self.conn.cursor(dictionary=True)
        print(f"Connected to MySQL: {host}:{port}/{database}")

    def close(self):
        """Close connection"""
        if self.cursor:
            self.cursor.close()
        if self.conn:
            self.conn.close()

    def record_exists(self, table, field, value):
        """Check if record exists"""
        self.cursor.execute(f"SELECT COUNT(*) as cnt FROM {table} WHERE {field} = %s", (value,))
        return self.cursor.fetchone()['cnt'] > 0

    def get_id(self, table, field, value):
        """Get ID of existing record"""
        self.cursor.execute(f"SELECT id FROM {table} WHERE {field} = %s", (value,))
        result = self.cursor.fetchone()
        return result['id'] if result else None

    def fix_schema(self):
        """Fix table schema if needed"""
        print("\n[Schema Check]")

        # Update users role ENUM
        try:
            self.cursor.execute("""
                ALTER TABLE users MODIFY COLUMN role
                ENUM('customer', 'manager', 'supplier', 'admin') DEFAULT 'customer'
            """)
            print("  [OK] Users role ENUM updated")
        except Error as e:
            if "1060" not in str(e):  # Ignore duplicate column errors
                print(f"  [INFO] Users: {e}")

    def seed_users(self):
        """Create users (update password if exists, insert if not)"""
        print("\n[Users]")
        # Hash for 'password' - works with PHP password_verify()
        password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'

        users = [
            ('admin@construkt.com', password_hash, 'Admin', 'User', 'admin', '+1-555-0001'),
            ('manager@construkt.com', password_hash, 'Manager', 'User', 'manager', '+1-555-0002'),
            ('supplier1@test.com', password_hash, 'John', 'Builder', 'supplier', '+1-555-0100'),
            ('supplier2@test.com', password_hash, 'Maria', 'Stone', 'supplier', '+1-555-0200'),
            ('customer1@test.com', password_hash, 'Bob', 'Smith', 'customer', '+1-555-1001'),
            ('customer2@test.com', password_hash, 'Alice', 'Johnson', 'customer', '+1-555-1002'),
        ]

        for email, pwd, first, last, role, phone in users:
            if self.record_exists('users', 'email', email):
                # Update password for existing user
                self.cursor.execute(
                    "UPDATE users SET password = %s, role = %s WHERE email = %s",
                    (pwd, role, email)
                )
                print(f"  [UPDATE] {email} - password reset")
                self.stats['skipped'] += 1
            else:
                self.cursor.execute("""
                    INSERT INTO users (email, password, first_name, last_name, role, phone, is_active)
                    VALUES (%s, %s, %s, %s, %s, %s, 1)
                """, (email, pwd, first, last, role, phone))
                print(f"  [ADD] {email}")
                self.stats['users'] += 1

    def seed_categories(self):
        """Create categories (skip existing)"""
        print("\n[Categories]")
        categories = [
            ('Bricks', 'Construction bricks of various types'),
            ('Cement', 'Cement and dry mixes'),
            ('Sand & Gravel', 'Aggregate materials'),
            ('Lumber', 'Wood and timber products'),
            ('Roofing', 'Roofing materials'),
            ('Paints', 'Paints and varnishes'),
            ('Tools', 'Construction tools'),
            ('Electrical', 'Electrical supplies'),
            ('Plumbing', 'Plumbing supplies'),
            ('Insulation', 'Insulation materials'),
        ]

        for name, desc in categories:
            if self.record_exists('categories', 'name', name):
                print(f"  [SKIP] {name} - already exists")
                self.stats['skipped'] += 1
            else:
                self.cursor.execute(
                    "INSERT INTO categories (name, description) VALUES (%s, %s)",
                    (name, desc)
                )
                print(f"  [ADD] {name}")
                self.stats['categories'] += 1

    def seed_suppliers(self):
        """Create suppliers (skip existing)"""
        print("\n[Suppliers]")
        # (company_name, phone, address, description)
        suppliers = [
            ('BuildMart', '+1-555-0101', '123 Industrial Ave', 'Major construction materials supplier'),
            ('BrickPro', '+1-555-0102', '456 Brick Lane', 'Brick manufacturer'),
            ('TimberTrade', '+1-555-0103', '789 Forest Rd', 'Wholesale lumber'),
            ('RoofMasters', '+1-555-0104', '321 Top St', 'Roofing specialists'),
            ('PaintWorld', '+1-555-0105', '654 Color Blvd', 'Paint and coatings'),
        ]

        for company, phone, addr, desc in suppliers:
            if self.record_exists('suppliers', 'company_name', company):
                print(f"  [SKIP] {company} - already exists")
                self.stats['skipped'] += 1
            else:
                self.cursor.execute("""
                    INSERT INTO suppliers (company_name, phone, address, description)
                    VALUES (%s, %s, %s, %s)
                """, (company, phone, addr, desc))
                print(f"  [ADD] {company}")
                self.stats['suppliers'] += 1

    def seed_products(self):
        """Create products (skip existing by name)"""
        print("\n[Products]")

        # Get category and supplier IDs
        self.cursor.execute("SELECT id, name FROM categories")
        categories = {r['name']: r['id'] for r in self.cursor.fetchall()}

        self.cursor.execute("SELECT id, company_name FROM suppliers")
        suppliers = {r['company_name']: r['id'] for r in self.cursor.fetchall()}

        products = [
            # Bricks
            ('Red Brick M-100', 'Ceramic construction brick', 0.85, 10000, 'pc', 'Bricks', 'BrickPro'),
            ('White Silicate Brick', 'Silicate brick M-150', 0.75, 8000, 'pc', 'Bricks', 'BrickPro'),
            ('Fire Brick', 'High-temperature resistant brick', 2.50, 3000, 'pc', 'Bricks', 'BrickPro'),
            # Cement
            ('Portland Cement 50kg', 'Portland cement PC-400', 12.50, 500, 'bag', 'Cement', 'BuildMart'),
            ('Premium Cement 50kg', 'High-strength portland cement PC-500', 14.00, 300, 'bag', 'Cement', 'BuildMart'),
            ('Quick-Set Cement 25kg', 'Fast-setting cement', 18.00, 200, 'bag', 'Cement', 'BuildMart'),
            # Sand & Gravel
            ('River Sand', 'Construction sand', 45.00, 100, 'ton', 'Sand & Gravel', 'BuildMart'),
            ('Granite Gravel 5-20mm', 'Crushed granite', 55.00, 80, 'ton', 'Sand & Gravel', 'BuildMart'),
            ('Fine Sand', 'Plastering sand', 50.00, 60, 'ton', 'Sand & Gravel', 'BuildMart'),
            # Lumber
            ('Pine Board 2x6', 'Pine lumber 6ft', 8.50, 200, 'pc', 'Lumber', 'TimberTrade'),
            ('Pine Beam 4x4', 'Pine beam 6ft', 15.00, 150, 'pc', 'Lumber', 'TimberTrade'),
            ('Plywood 4x8 3/4"', 'Construction plywood', 45.00, 100, 'sheet', 'Lumber', 'TimberTrade'),
            ('OSB Board 4x8', 'Oriented strand board', 28.00, 150, 'sheet', 'Lumber', 'TimberTrade'),
            # Roofing
            ('Metal Roofing Tile', 'Steel roofing tile', 12.00, 500, 'sqft', 'Roofing', 'RoofMasters'),
            ('Onduline Sheet', 'Bitumen roofing sheet', 18.00, 300, 'sheet', 'Roofing', 'RoofMasters'),
            ('Asphalt Shingles', '25-year warranty shingles', 35.00, 1000, 'bundle', 'Roofing', 'RoofMasters'),
            # Paints
            ('White Exterior Paint', 'Acrylic paint 2.5gal', 85.00, 100, 'bucket', 'Paints', 'PaintWorld'),
            ('Floor Varnish', 'Polyurethane varnish 1gal', 62.00, 50, 'can', 'Paints', 'PaintWorld'),
            ('Interior Latex White', 'Low VOC interior paint 5gal', 95.00, 80, 'bucket', 'Paints', 'PaintWorld'),
            ('Wood Stain Dark Oak', 'Oil-based wood stain 1gal', 45.00, 60, 'can', 'Paints', 'PaintWorld'),
            # Tools
            ('Bosch Hammer Drill', 'Professional hammer drill 800W', 249.00, 20, 'pc', 'Tools', 'BuildMart'),
            ('Makita Cordless Drill', 'Makita 18V cordless drill', 189.00, 30, 'pc', 'Tools', 'BuildMart'),
            ('Claw Hammer 16oz', 'Fiberglass handle hammer', 24.99, 50, 'pc', 'Tools', 'BuildMart'),
            ('Tape Measure 25ft', 'Heavy-duty tape measure', 12.99, 100, 'pc', 'Tools', 'BuildMart'),
            ('Level 48 inch', 'Professional aluminum level', 34.99, 30, 'pc', 'Tools', 'BuildMart'),
            # Electrical
            ('Copper Wire 12AWG', 'Copper electrical wire 100ft', 89.00, 50, 'roll', 'Electrical', 'BuildMart'),
            ('Circuit Breaker 15A', 'Automatic circuit breaker', 12.00, 200, 'pc', 'Electrical', 'BuildMart'),
            ('Outlet Box', 'Standard electrical box', 3.50, 500, 'pc', 'Electrical', 'BuildMart'),
            ('LED Bulb 10W', 'Energy-saving LED bulb', 8.00, 300, 'pc', 'Electrical', 'BuildMart'),
            # Plumbing
            ('PVC Pipe 2" 10ft', 'Schedule 40 PVC pipe', 8.50, 200, 'pc', 'Plumbing', 'BuildMart'),
            ('Copper Pipe 1/2" 10ft', 'Type L copper pipe', 25.00, 100, 'pc', 'Plumbing', 'BuildMart'),
            ('Ball Valve 1"', 'Brass ball valve', 18.00, 80, 'pc', 'Plumbing', 'BuildMart'),
            ('Faucet Single Handle', 'Chrome kitchen faucet', 85.00, 25, 'pc', 'Plumbing', 'BuildMart'),
            # Insulation
            ('Fiberglass Batt R-13', 'Wall insulation 15" wide', 0.89, 5000, 'sqft', 'Insulation', 'BuildMart'),
            ('Foam Board 2"', 'Rigid foam insulation', 32.00, 200, 'sheet', 'Insulation', 'BuildMart'),
            ('Spray Foam Kit', 'DIY spray foam insulation', 450.00, 15, 'kit', 'Insulation', 'BuildMart'),
        ]

        for name, desc, price, stock, unit, cat_name, sup_name in products:
            cat_id = categories.get(cat_name)
            sup_id = suppliers.get(sup_name)

            if not cat_id or not sup_id:
                print(f"  [ERROR] {name} - missing category or supplier")
                continue

            if self.record_exists('products', 'name', name):
                print(f"  [SKIP] {name} - already exists")
                self.stats['skipped'] += 1
            else:
                self.cursor.execute("""
                    INSERT INTO products (name, description, price, stock, unit, category_id, supplier_id, is_active)
                    VALUES (%s, %s, %s, %s, %s, %s, %s, 1)
                """, (name, desc, price, stock, unit, cat_id, sup_id))
                print(f"  [ADD] {name}")
                self.stats['products'] += 1

    def run(self, force_clear=False):
        """Run the seeder"""
        print("=" * 60)
        print("Construkt Database Seeder (MySQL)")
        print("=" * 60)

        try:
            self.connect()

            if force_clear:
                print("\n[!] Force clearing all data...")
                self.cursor.execute("SET FOREIGN_KEY_CHECKS = 0")
                for table in ['conversation_logs', 'support_messages', 'order_items', 'orders',
                              'cart_items', 'products', 'suppliers', 'categories', 'users']:
                    try:
                        self.cursor.execute(f"TRUNCATE TABLE {table}")
                        print(f"  Cleared: {table}")
                    except:
                        pass
                self.cursor.execute("SET FOREIGN_KEY_CHECKS = 1")

            self.fix_schema()
            self.seed_users()
            self.seed_categories()
            self.seed_suppliers()
            self.seed_products()

            print("\n" + "=" * 60)
            print("SUMMARY:")
            print(f"  Users added:      {self.stats['users']}")
            print(f"  Categories added: {self.stats['categories']}")
            print(f"  Suppliers added:  {self.stats['suppliers']}")
            print(f"  Products added:   {self.stats['products']}")
            print(f"  Skipped (exist):  {self.stats['skipped']}")
            print("=" * 60)

            if self.stats['skipped'] > 0 and sum([self.stats['users'], self.stats['categories'],
                                                   self.stats['suppliers'], self.stats['products']]) == 0:
                print("\nDatabase already populated. Use --force to clear and reseed.")

        except Error as e:
            print(f"\nERROR: {e}")
            import traceback
            traceback.print_exc()

        finally:
            self.close()


if __name__ == '__main__':
    import argparse
    parser = argparse.ArgumentParser(description='Seed the Construkt database')
    parser.add_argument('--force', action='store_true', help='Clear all data before seeding')
    args = parser.parse_args()

    seeder = DatabaseSeeder()
    seeder.run(force_clear=args.force)
