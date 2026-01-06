# Construkt - System Architecture

Technical overview of the e-commerce platform architecture.

---

## System Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                            BROWSER                                   │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│   ┌─────────────────────────┐    ┌─────────────────────────────┐   │
│   │      PHP PAGES          │    │      REACT WIDGET           │   │
│   │                         │    │                             │   │
│   │   index.php             │    │   ChatbotWidget             │   │
│   │   products.php          │    │   ├── ChatWindow            │   │
│   │   cart.php              │    │   ├── ChatMessage           │   │
│   │   checkout.php          │    │   └── ChatButton            │   │
│   │   admin.php             │    │                             │   │
│   └───────────┬─────────────┘    └───────────┬─────────────────┘   │
│               │                              │                      │
└───────────────┼──────────────────────────────┼──────────────────────┘
                │                              │
                │ HTTP :8000                   │ HTTP :5000
                ▼                              ▼
┌───────────────────────────┐    ┌────────────────────────────────────┐
│     PHP SERVER            │    │         PYTHON FLASK               │
│     (php-site/)           │    │         (chatbot/)                 │
│                           │    │                                    │
│   ┌───────────────────┐   │    │   ┌────────────────────────────┐  │
│   │ config/           │   │    │   │ app.py                     │  │
│   │ ├── database.php  │   │    │   │ POST /api/chatbot/message  │  │
│   │ └── auth.php      │   │    │   │ POST /api/chatbot/command  │  │
│   └───────────────────┘   │    │   └────────────────────────────┘  │
│                           │    │               │                    │
│   ┌───────────────────┐   │    │   ┌───────────▼───────────────┐   │
│   │ includes/         │   │    │   │ src/                      │   │
│   │ ├── header.php    │   │    │   │ ├── command_handler.py    │   │
│   │ └── footer.php    │   │    │   │ ├── nlp_engine.py         │   │
│   └───────────────────┘   │    │   │ └── intents/              │   │
│                           │    │   └───────────────────────────┘   │
└────────────┬──────────────┘    └──────────────┬─────────────────────┘
             │                                  │
             └──────────────┬───────────────────┘
                            │
                            ▼
             ┌──────────────────────────────┐
             │       MySQL DATABASE         │
             │       construkt              │
             │                              │
             │   users, products,           │
             │   categories, orders,        │
             │   cart_items, suppliers      │
             └──────────────────────────────┘
```

---

## Technology Stack

| Layer | Technology | Version | Purpose |
|-------|------------|---------|---------|
| **Frontend** | PHP | 8.1+ | Server-side rendering |
| **Frontend** | CSS3 | - | Flexbox, Grid, Variables |
| **Frontend** | JavaScript | ES6+ | Vanilla JS |
| **Widget** | React | 18.2 | Component UI |
| **Widget** | Webpack | 5.x | UMD bundle build |
| **API** | Python | 3.10+ | Interpreter |
| **API** | Flask | 2.0 | Microframework |
| **Database** | MySQL | 8.0 | Relational DB |

---

## Components

### 1. PHP Website

**Location:** `php-site/`

**Structure:**
```
php-site/
├── config/
│   ├── database.php       # MySQL PDO + auto-init
│   └── auth.php           # Sessions, bcrypt, roles
├── includes/
│   ├── header.php         # DOCTYPE, <head>, navigation
│   └── footer.php         # Footer, widget include
├── css/
│   └── style.css          # CSS Variables, responsive
├── js/
│   └── chatbot-widget.js  # React bundle
├── images/
│   └── products/          # Product images
└── *.php                  # Page files
```

**Features:**
- Pure PHP (no framework) for simplicity
- Auto-creates DB tables on first run
- Session-based authentication
- Prepared statements (SQL injection protection)
- htmlspecialchars() (XSS protection)

### 2. React Widget

**Location:** `chatbot-widget/`

**Structure:**
```
chatbot-widget/
├── src/
│   ├── index.js              # ChatbotWidgetManager
│   ├── ChatbotWidget.jsx     # Root component
│   ├── components/
│   │   ├── ChatWindow.jsx    # Chat window
│   │   ├── ChatMessage.jsx   # Messages + cards
│   │   ├── ChatButton.jsx    # FAB button
│   │   └── CommandPanel.jsx  # Quick commands
│   ├── context/
│   │   └── ChatContext.jsx   # State management
│   ├── services/
│   │   └── chatbotService.js # fetch() wrapper
│   └── styles/
│       └── chatbot.css
├── dist/
│   └── chatbot-widget.js     # Production build
└── webpack.config.js
```

**Initialization:**
```javascript
if (window.CHATBOT_CONFIG) {
    window.ChatbotWidget.init(window.CHATBOT_CONFIG);
}
```

### 3. Python API

**Location:** `chatbot/`

**Structure:**
```
chatbot/
├── app.py                    # Flask app
├── src/
│   ├── command_handler.py    # Command processing
│   ├── nlp_engine.py         # NLP
│   ├── gemini_ai.py          # Gemini API
│   └── intents/
│       ├── calculator.py     # Material calculator
│       └── store_info.py     # Store info
├── utils/
│   └── database.py           # MySQL connector
├── data/
│   ├── intents.json
│   └── store_info.json
└── .env
```

**API Commands:**
| Command | Description | Response |
|---------|-------------|----------|
| SEARCH | Search products | products |
| CATEGORIES | List categories | categories |
| FEATURED | Featured products | products |
| CHEAPEST | Cheapest products | products |
| PRODUCT | Product details | product_detail |
| CALCULATOR | Material calc | calculator |
| HELP | Help text | help |

---

## Database Schema

### Tables

```sql
users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE,
    password VARCHAR(255),
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(50),
    role ENUM('customer','manager','supplier','admin'),
    is_active TINYINT(1),
    created_at TIMESTAMP
)

categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    description TEXT,
    image_url VARCHAR(500)
)

suppliers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT REFERENCES users(id),
    company_name VARCHAR(255),
    description TEXT,
    phone VARCHAR(50)
)

products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    description TEXT,
    price DECIMAL(10,2),
    stock INT,
    unit VARCHAR(50),
    category_id INT REFERENCES categories(id),
    supplier_id INT REFERENCES suppliers(id),
    is_active TINYINT(1),
    created_at TIMESTAMP
)

cart_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT REFERENCES users(id),
    product_id INT REFERENCES products(id),
    quantity INT,
    UNIQUE(user_id, product_id)
)

orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT REFERENCES users(id),
    total_amount DECIMAL(10,2),
    status ENUM('pending','processing','shipped','delivered','cancelled'),
    shipping_address TEXT,
    created_at TIMESTAMP
)

order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT REFERENCES orders(id),
    product_id INT REFERENCES products(id),
    quantity INT,
    price DECIMAL(10,2)
)

support_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT REFERENCES users(id),
    manager_id INT REFERENCES users(id),
    message TEXT,
    is_from_customer TINYINT(1),
    is_read TINYINT(1),
    created_at TIMESTAMP
)

conversation_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id VARCHAR(100),
    user_message TEXT,
    bot_response TEXT,
    intent VARCHAR(100),
    created_at TIMESTAMP
)
```

### ERD

```
┌─────────────┐       ┌──────────────┐       ┌─────────────┐
│   users     │       │   products   │       │ categories  │
├─────────────┤       ├──────────────┤       ├─────────────┤
│ id (PK)     │       │ id (PK)      │       │ id (PK)     │
│ email (U)   │       │ name         │       │ name        │
│ password    │       │ price        │       │ description │
│ first_name  │       │ stock        │       │ image_url   │
│ last_name   │       │ category_id ─┼───────┤             │
│ role        │       │ supplier_id  │       └─────────────┘
│ is_active   │       │ is_active    │
└──────┬──────┘       └──────┬───────┘
       │                     │
       ▼                     ▼
┌─────────────┐       ┌──────────────┐
│ cart_items  │       │   orders     │
├─────────────┤       ├──────────────┤
│ user_id(FK) │       │ user_id (FK) │
│ product_id  │       │ total_amount │
│ quantity    │       │ status       │
└─────────────┘       └──────┬───────┘
                             │
                      ┌──────▼───────┐
                      │ order_items  │
                      ├──────────────┤
                      │ order_id(FK) │
                      │ product_id   │
                      │ quantity     │
                      │ price        │
                      └──────────────┘
```

---

## Data Flow

### Page Load

```
Browser          PHP Server           MySQL
   │                 │                   │
   │ GET /products   │                   │
   ├────────────────►│                   │
   │                 │ SELECT products   │
   │                 ├──────────────────►│
   │                 │◄──────────────────┤
   │                 │ [data]            │
   │ HTML + CSS + JS │                   │
   │◄────────────────┤                   │
```

### Chatbot Request

```
Widget          Flask API          MySQL
   │                │                 │
   │ POST /command  │                 │
   │ {SEARCH: "x"}  │                 │
   ├───────────────►│                 │
   │                │ SELECT WHERE    │
   │                │ name LIKE '%x%' │
   │                ├────────────────►│
   │                │◄────────────────┤
   │ {type:products}│                 │
   │◄───────────────┤                 │
```

---

## Security

### Authentication

```php
// Hashing
$hash = password_hash($password, PASSWORD_DEFAULT);

// Verification
if (password_verify($input, $hash)) {
    $_SESSION['user_id'] = $user['id'];
}
```

### SQL Injection Protection

```php
$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
```

```python
cursor.execute("SELECT * FROM products WHERE id = %s", (id,))
```

### XSS Protection

```php
<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>
```

### CORS (Flask)

```python
from flask_cors import CORS
CORS(app, resources={r"/api/*": {"origins": "http://localhost:8000"}})
```

---

## Deployment

### Development

```bash
# Terminal 1 - Python API
cd chatbot && python app.py

# Terminal 2 - PHP Server
cd php-site && php -S localhost:8000
```

### Production (nginx)

```nginx
server {
    listen 80;
    root /var/www/construkt/php-site;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        include fastcgi_params;
    }

    location /api/ {
        proxy_pass http://127.0.0.1:5000;
    }
}
```

---

## Configuration Files

| File | Purpose |
|------|---------|
| `chatbot/.env` | MySQL creds, API keys |
| `php-site/config/database.php` | PHP database config |
| `database/requirements.txt` | DB script dependencies |
| `chatbot/requirements.txt` | Python dependencies |

---

**Construkt Architecture © 2025**
