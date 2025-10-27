@echo off
echo ========================================
echo   OLT Monitoring Setup Script
echo ========================================
echo.

echo 1. Setting up Database...
echo Please enter your MySQL root password when prompted.
echo.

mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS olt_monitoring; GRANT ALL PRIVILEGES ON olt_monitoring.* TO 'root'@'localhost'; FLUSH PRIVILEGES; SHOW DATABASES;"

if %errorlevel% neq 0 (
    echo.
    echo ERROR: Failed to setup database. Please check:
    echo - MySQL service is running
    echo - Root password is correct
    echo - MySQL is accessible
    echo.
    pause
    exit /b 1
)

echo.
echo 2. Running database migrations...
node database/migrate.js

if %errorlevel% neq 0 (
    echo.
    echo ERROR: Database migration failed.
    echo Please check the .env file configuration.
    echo.
    pause
    exit /b 1
)

echo.
echo ========================================
echo   Database setup completed successfully!
echo ========================================
echo.
echo Next steps:
echo 1. Run: npm run dev (to start backend)
echo 2. In another terminal: cd ../frontend && npm start
echo 3. Open http://localhost:3000 in browser
echo.
pause