@echo off
echo ===================================================
echo 🌾 Kisan Smart Assistant - Startup Script
echo ===================================================
echo.

echo Starting Python AI Service...
start "AI Service" cmd /c "cd ai-service && pip install -r requirements.txt && uvicorn main:app --host 0.0.0.0 --port 8001"

echo Starting Laravel Backend...
set PATH=%PATH%;C:\bin
start "Laravel Backend" cmd /c "cd backend && php artisan serve --port 8000"

echo Starting React Frontend...
start "React Frontend" cmd /c "cd frontend && npm run dev"

echo.
echo ===================================================
echo ✅ All services are starting in the background!
echo 🌐 Your unified application link is: http://localhost:5173
echo ===================================================
echo.
echo Opening browser...
timeout /t 5 /nobreak > nul
start http://localhost:5173
