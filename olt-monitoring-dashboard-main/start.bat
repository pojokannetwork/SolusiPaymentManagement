@echo off
echo ========================================
echo   OLT Monitoring - Quick Start
echo ========================================
echo.

REM Check if database exists
mysql -u root -p -e "USE olt_monitoring;" > nul 2>&1
if %errorlevel% neq 0 (
    echo Database not found. Running setup...
    call setup-database.bat
    if %errorlevel% neq 0 exit /b 1
)

echo Starting OLT Monitoring System...
echo.

REM Start backend in new window
echo Starting backend server...
start "OLT Backend" cmd /k "npm run dev"

REM Wait a bit for backend to start
timeout /t 3 > nul

REM Start frontend in new window  
echo Starting frontend server...
start "OLT Frontend" cmd /k "cd ../frontend && npm start"

echo.
echo ========================================
echo   System Started!
echo ========================================
echo.
echo Backend:  http://localhost:3001
echo Frontend: http://localhost:3000
echo.
echo Press any key to close this window...
pause > nul