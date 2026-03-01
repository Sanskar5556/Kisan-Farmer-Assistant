# 🌾 Kisan Smart Assistant — Startup Guide

## Prerequisites (Install These First)

| Tool | Download | Check if Installed |
|------|----------|--------------------|
| PHP 8.1+ | [php.net](https://www.php.net/downloads) | `php -v` in terminal |
| Composer | [getcomposer.org](https://getcomposer.org/download/) | `composer -v` |
| MySQL 8 | [mysql.com](https://dev.mysql.com/downloads/installer/) | Via MySQL Workbench |
| Node.js 18+ | [nodejs.org](https://nodejs.org/) | `node -v` |
| Python 3.10+ | [python.org](https://www.python.org/downloads/) | `python -v` |

---

## Step 1 — Set Up MySQL Database

Open **MySQL Workbench** (or any MySQL client) and run:

```sql
CREATE DATABASE kisan_smart_assistant CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

---

## Step 2 — Set Up Laravel Backend

Open **PowerShell / Terminal** in `D:\Web dev project\backend`:

```bash
# 1. Install PHP dependencies
composer install

# 2. Copy environment file
copy .env.example .env

# 3. Open .env and update these lines:
#    DB_DATABASE=kisan_smart_assistant
#    DB_USERNAME=root
#    DB_PASSWORD=your_mysql_password

# 4. Generate app key
php artisan key:generate

# 5. Generate JWT secret
php artisan jwt:secret

# 6. Run database migrations (creates all 8 tables)
php artisan migrate

# 7. Create symbolic link for file uploads
php artisan storage:link

# 8. Start the backend server
php artisan serve
```

✅ Backend will run at **http://localhost:8000**

### Seed Demo Data (Optional but recommended)
```bash
# Insert demo APMC prices so you can test immediately
php artisan apmc:fetch-prices --demo
```

---

## Step 3 — Set Up Python AI Microservice

Open a **second terminal** in `D:\Web dev project\ai-service`:

```bash
# 1. Install Python dependencies
pip install -r requirements.txt

# 2. Start the AI service
uvicorn main:app --reload --host 0.0.0.0 --port 8001
```

✅ AI service will run at **http://localhost:8001**

Test it works:
```
Visit: http://localhost:8001
You should see: { "service": "Kisan AI Microservice", "status": "running" }
```

---

## Step 4 — Set Up React Frontend

Open a **third terminal** in `D:\Web dev project\frontend`:

```bash
# 1. Install Node packages
npm install

# 2. Start the dev server
npm run dev
```

✅ Frontend will run at **http://localhost:5173**

---

## Step 5 — Use the App!

1. Open **http://localhost:5173** in your browser
2. Click **Create account** → register as a farmer
3. Login → you'll see the **Dashboard**

### Test each feature:
| Feature | How to test |
|---------|-------------|
| 📊 Market Prices | Go to "Market Prices" → select Wheat → click Search |
| 📔 Crop Diary | Go to "Crop Diary" → Add Entry → click "Advisory" |
| 🔬 AI Diagnosis | Go to "AI Diagnosis" → upload any plant photo |
| 👥 Community | Go to "Community" → create a post |

---

## File Structure Reference

```
D:\Web dev project\
├── backend/                    ← Laravel API (PHP)
│   ├── app/
│   │   ├── Http/Controllers/   ← All API logic here
│   │   ├── Models/             ← Database models
│   │   └── Console/Commands/   ← Cron jobs
│   ├── database/migrations/    ← Table definitions
│   ├── routes/api.php          ← All API routes
│   └── .env                    ← Your config (DB password etc.)
│
├── ai-service/                 ← Python FastAPI
│   ├── main.py                 ← API entry point
│   └── models/predictor.py     ← Disease detection logic
│
└── frontend/                   ← React app
    └── src/
        ├── pages/              ← One file per screen
        ├── components/         ← Shared UI components
        ├── api/axios.js        ← API client (auto-adds token)
        └── context/AuthContext.jsx ← Login state
```

---

## Common Issues & Fixes

| Problem | Fix |
|---------|-----|
| `composer: command not found` | Download Composer from getcomposer.org and restart terminal |
| `SQLSTATE[HY000]: No connection` | Check DB_PASSWORD in `.env` matches MySQL root password |
| `Could not connect to AI service` | Make sure Step 3 terminal is open and running |
| `jwt:secret not found` | Run `composer require tymon/jwt-auth` first |
| `npm: command not found` | Install Node.js from nodejs.org |
| White screen on frontend | Check browser console (F12) for errors |

---

## API Quick Reference (for testing with Postman)

**Register:**
```
POST http://localhost:8000/api/auth/register
Body (JSON): { "name": "Rajesh", "email": "raj@test.com", "password": "123456", "password_confirmation": "123456" }
```

**Login → copy the token:**
```
POST http://localhost:8000/api/auth/login
Body (JSON): { "email": "raj@test.com", "password": "123456" }
```

**All other requests need this header:**
```
Authorization: Bearer <paste_token_here>
```
