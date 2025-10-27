# 🚀 Quick Setup Guide - Local Development

## Prerequisites
- ✅ Node.js v16+ 
- ✅ MySQL v8.0+
- ✅ NPM/Yarn

## 🔧 Setup Methods

### Method 1: Automated Setup (Recommended)

#### Windows:
```bash
# 1. Setup database (one time only)
cd backend
npm run setup

# 2. Start application  
cd ..
start.bat
```

#### Linux/Mac:
```bash
# 1. Setup database
cd backend
npm run setup

# 2. Start backend
npm run dev

# 3. Start frontend (new terminal)
cd ../frontend
npm start
```

### Method 2: Manual Setup

#### 1. Database Setup
```sql
-- Connect to MySQL
mysql -u root -p

-- Create database
CREATE DATABASE olt_monitoring;
-- Optional: Create dedicated user
-- CREATE USER 'olt_user'@'localhost' IDENTIFIED BY 'your_password';
-- GRANT ALL PRIVILEGES ON olt_monitoring.* TO 'olt_user'@'localhost';
-- FLUSH PRIVILEGES;
```

#### 2. Environment Configuration
```bash
cd backend
# Edit .env file with your MySQL credentials
DB_HOST=localhost
DB_PORT=3306  
DB_NAME=olt_monitoring
DB_USER=root
DB_PASSWORD=your_mysql_password
```

#### 3. Install & Run
```bash
# Backend
cd backend
npm install
npm run migrate  # Setup database schema
npm run dev     # Start backend server

# Frontend (new terminal)
cd frontend
npm install
npm start       # Start frontend server
```

## 🌐 Access Points

- **Frontend Dashboard**: http://localhost:3000
- **Backend API**: http://localhost:3001  
- **API Health**: http://localhost:3001/api/health

## 🎯 First Time Setup

1. **Access Dashboard**: Open http://localhost:3000
2. **Configure Telegram Bot**:
   - Go to Settings → Telegram Bot
   - Get bot token from @BotFather
   - Paste token and test connection
3. **Add OLT Device**:
   - Go to OLT Management  
   - Add your first OLT device
4. **Configure Thresholds**:
   - Go to Settings → Thresholds
   - Adjust power/distance limits

## 🔍 Troubleshooting

### Database Issues
```bash
# Check MySQL service
# Windows:
net start mysql

# Linux:
sudo systemctl start mysql
sudo systemctl status mysql

# Test connection
mysql -u root -p -e "SHOW DATABASES;"
```

### Port Issues
```bash
# Check if ports are in use
netstat -ano | findstr :3000
netstat -ano | findstr :3001

# Kill process if needed (Windows)
taskkill /PID <process_id> /F
```

### Permission Issues
```bash
# Fix MySQL access
mysql -u root -p -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'your_password'; FLUSH PRIVILEGES;"
```

### Clear Cache/Reset
```bash
# Backend
cd backend
rm -rf node_modules logs
npm install

# Frontend  
cd frontend
rm -rf node_modules build
npm install
```

## 📱 Production Deployment

For production deployment, see main README.md file.

## 🆘 Need Help?

- Check logs: `backend/logs/`
- Issues: GitHub Issues
- Documentation: Main README.md