# Construkt - Construction Materials Marketplace

Online marketplace for construction materials with AI-powered chatbot assistant.

## Technology Stack

- **Backend:** PHP 8.1+ (Website), Python 3.10+ (Chatbot API)
- **Database:** MySQL 8.0+
- **Frontend:** HTML/CSS/JS + React Widget

## Project Structure

```
construkt/
├── php-site/           # PHP Website (port 8000)
│   ├── config/         # Database & auth config
│   ├── includes/       # Header/footer templates
│   └── *.php           # Page files
├── chatbot/            # Python Flask API (port 5000)
│   ├── app.py          # Main Flask application
│   ├── src/            # NLP engine, processors
│   └── utils/          # Database connector (MySQL)
├── chatbot-widget/     # React chatbot widget
├── database/           # DB scripts
│   ├── create_tables.py
│   └── seeder.py
└── *.bat               # Windows scripts
```

## Database

**MySQL** - shared database for PHP site and Python chatbot:

| Parameter | Value |
|-----------|-------|
| Host | localhost |
| Port | 3306 |
| Database | construkt |
| User | root |
| Password | (empty) |

Database and tables are created automatically on first run.

## Requirements

- PHP 8.1+ with pdo_mysql extension
- Python 3.10+
- MySQL 8.0+ server running
- Node.js 18+ (optional, for widget rebuild)

## Quick Start

### 1. Install

```batch
install.bat
```

This will:
- Install Python dependencies
- Create MySQL database and tables
- Seed test data (with duplicate prevention)
- Build chatbot widget (if Node.js available)

### 2. Start

```batch
start.bat
```

### 3. Open

- Website: http://localhost:8000
- Chatbot API: http://localhost:5000

### 4. Stop

```batch
stop.bat
```

## Test Accounts

All accounts use password: `password`

| Email | Role |
|-------|------|
| admin@construkt.com | Admin |
| manager@construkt.com | Manager |
| supplier1@test.com | Supplier |
| customer1@test.com | Customer |

## Features

- Product catalog with 10 categories
- Shopping cart and checkout
- Order management
- User roles and permissions
- AI chatbot assistant
- Material calculator
- Support chat

## Database Management

### Seed Data (safe - no duplicates)
```batch
cd database
python seeder.py
```

### Force Reseed (clears all data)
```batch
cd database
python seeder.py --force
```

### Create Tables Only
```batch
cd database
python create_tables.py
```

## API Endpoints

### Chatbot API (port 5000)

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/chatbot` | POST | Send message |
| `/api/chatbot/health` | GET | Health check |
| `/api/products` | GET | List products |
| `/api/categories` | GET | List categories |

## Scripts

| Script | Description |
|--------|-------------|
| `install.bat` | Full installation |
| `start.bat` | Start servers |
| `stop.bat` | Stop servers |
| `build-widget.bat` | Rebuild React widget |

## Manual Setup

### Python Dependencies
```bash
cd chatbot
pip install -r requirements.txt
```

### Database Setup
```bash
cd database
pip install -r requirements.txt
python create_tables.py
python seeder.py
```

### Widget Build
```bash
cd chatbot-widget
npm install
npm run build
copy dist\chatbot-widget.js ..\php-site\js\
```

