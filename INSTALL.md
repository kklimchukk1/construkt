# Construkt - Installation Guide

Step-by-step instructions for local deployment.

---

## System Requirements

| Component | Minimum | Recommended | Check |
|-----------|---------|-------------|-------|
| **PHP** | 8.1 | 8.3 | `php -v` |
| **MySQL** | 5.7 | 8.0 | `mysql --version` |
| **Python** | 3.10 | 3.12 | `python --version` |
| **Node.js** | 18.x | 20.x | `node -v` (optional) |

**Recommended:** Laragon (https://laragon.org/download/) - includes PHP + MySQL

---

## Quick Start (Windows)

```batch
:: 1. Start Laragon (MySQL must be running)

:: 2. Run installation
install.bat

:: 3. Start servers
start.bat

:: 4. Open http://localhost:8000
```

---

## Manual Installation

### Step 1: Start MySQL Server

Make sure MySQL is running on `localhost:3306`.

With Laragon: Start All

With standalone MySQL:
```bash
mysql.server start
```

### Step 2: Install Python Dependencies

```bash
cd chatbot
pip install -r requirements.txt
```

### Step 3: Create Database and Tables

```bash
cd database
pip install -r requirements.txt
python create_tables.py
```

### Step 4: Seed Test Data

```bash
python seeder.py
```

The seeder will:
- Check for existing records
- Skip duplicates automatically
- Report what was added/skipped

### Step 5: Build Chatbot Widget (Optional)

```bash
cd chatbot-widget
npm install
npm run build
copy dist\chatbot-widget.js ..\php-site\js\
```

### Step 6: Start Servers

**Terminal 1 - Chatbot API:**
```bash
cd chatbot
python app.py
```
Output: `Running on http://0.0.0.0:5000`

**Terminal 2 - PHP Website:**
```bash
cd php-site
php -S localhost:8000
```
Output: `Listening on http://localhost:8000`

---

## Configuration

### MySQL Settings

Edit `chatbot/.env`:

```ini
# MySQL Database
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=
DB_NAME=construkt
DB_PORT=3306

# Server
CHATBOT_PORT=5000

# Gemini AI (optional)
GEMINI_API_KEY=your_api_key_here
```

PHP uses the same settings in `php-site/config/database.php`.

---

## Verification

### 1. Website
Open: http://localhost:8000

### 2. Chatbot API
```bash
curl http://localhost:5000/api/chatbot/health
```

### 3. Test Login
1. Go to http://localhost:8000/login.php
2. Login: `customer1@test.com` / `password`
3. Click chat button (bottom right)
4. Type: "search cement"

---

## Test Accounts

| Email | Password | Role |
|-------|----------|------|
| `admin@construkt.com` | `password` | Admin |
| `manager@construkt.com` | `password` | Manager |
| `supplier1@test.com` | `password` | Supplier |
| `customer1@test.com` | `password` | Customer |

---

## Database Management

### View Database

With MySQL CLI:
```bash
mysql -u root construkt
SHOW TABLES;
SELECT * FROM products LIMIT 5;
```

With phpMyAdmin:
- Laragon > Database > phpMyAdmin
- Select `construkt` database

### Reseed Data

Safe mode (no duplicates):
```bash
cd database
python seeder.py
```

Force reseed (clears all):
```bash
python seeder.py --force
```

---

## Troubleshooting

### Widget Not Appearing

| Problem | Solution |
|---------|----------|
| Not logged in | Login via `/login.php` |
| File missing | Copy `chatbot-widget/dist/chatbot-widget.js` to `php-site/js/` |
| JS error | Press F12 > Console - check errors |

### Chatbot Not Responding

| Problem | Solution |
|---------|----------|
| Server not running | `cd chatbot && python app.py` |
| Port busy | `netstat -ano \| findstr :5000` then `taskkill /F /PID <pid>` |
| CORS error | Ensure flask-cors is installed |

### MySQL Errors

| Problem | Solution |
|---------|----------|
| Connection refused | Start MySQL via Laragon |
| Access denied | Check credentials in `.env` |
| Unknown database | Run `python create_tables.py` |
| Can't connect | Check port 3306 is not blocked |

### npm install fails

```bash
npm cache clean --force
rmdir /s /q node_modules
npm install
```

### pip install fails

```bash
python -m venv venv
venv\Scripts\activate
pip install -r requirements.txt
```

---

## Ports

| Service | Port |
|---------|------|
| PHP Website | 8000 |
| Chatbot API | 5000 |
| MySQL | 3306 |

---

## Production Notes

For production deployment:
1. Use proper MySQL credentials (not root without password)
2. Enable HTTPS
3. Use process manager (pm2, supervisor) for Python API
4. Use nginx/Apache instead of PHP built-in server

---

**Construkt Team © 2025**
