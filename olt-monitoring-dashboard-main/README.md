# 📊 OLT Monitoring Dashboard + Telegram Bot

Sistem monitoring terpusat untuk OLT (Optical Line Terminal) dengan integrasi bot Telegram untuk notifikasi real-time dan management ONT.

![Dashboard Preview](https://via.placeholder.com/800x400/1976d2/ffffff?text=OLT+Monitoring+Dashboard)

## ✨ Fitur Utama

- 📈 **Real-time Monitoring**: Monitor status ONT (online/offline/LOS) secara real-time
- ⚡ **Power Monitoring**: Monitor RX/TX power dengan indikator warna berbasis threshold
- 📏 **Distance Monitoring**: Monitor jarak kabel dari OLT ke ONT
- 🏢 **Multi-OLT Support**: Mendukung monitoring dari beberapa OLT dalam satu dashboard
- 🤖 **Telegram Bot**: Notifikasi otomatis dan command untuk management
- 🎛️ **Settings Management**: Konfigurasi bot, OLT, dan threshold via web interface
- 📊 **Event Logging**: Log lengkap semua aktivitas dan perubahan status
- 📱 **Responsive Design**: Interface yang optimal di desktop dan mobile

## 🏗️ Arsitektur Sistem

```
┌─────────────────┐    SSH/SNMP    ┌─────────────────┐
│   OLT Devices   │◄──────────────►│ Polling Service │
│  (Hioso/EPON)   │                │                 │
└─────────────────┘                └─────────────────┘
                                             │
                                             ▼
┌─────────────────┐                ┌─────────────────┐
│ Telegram Bot    │◄──────────────►│ MySQL Database  │
│                 │                │                 │
└─────────────────┘                └─────────────────┘
         ▲                                   ▲
         │                                   │
         ▼                                   ▼
┌─────────────────┐                ┌─────────────────┐
│   Dashboard     │◄──────────────►│   Backend API   │
│ (React + MUI)   │                │ (Node.js +      │
└─────────────────┘                │  Express)       │
                                   └─────────────────┘
```

## 🚀 Quick Start

### Prerequisites

- **Node.js** v16 atau lebih baru
- **MySQL** v8.0 atau lebih baru
- **NPM** atau **Yarn**
- **Git** (untuk clone repository)

### 1. Clone Repository

```bash
git clone https://github.com/yourusername/olt-monitoring.git
cd olt-monitoring
```

### 2. Setup Database

```bash
# Masuk ke MySQL
mysql -u root -p

# Buat database
CREATE DATABASE olt_monitoring;
CREATE USER 'olt_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON olt_monitoring.* TO 'olt_user'@'localhost';
FLUSH PRIVILEGES;
exit;
```

### 3. Setup Backend

```bash
cd backend
npm install

# Copy environment file
copy .env.example .env
# Edit .env dengan konfigurasi database Anda

# Jalankan migrasi database
npm run migrate

# Start backend server
npm run dev
```

### 4. Setup Frontend

```bash
cd ../frontend
npm install

# Start frontend development server
npm start
```

### 5. Akses Aplikasi

- **Dashboard**: http://localhost:3000
- **Backend API**: http://localhost:3001
- **API Health Check**: http://localhost:3001/api/health

## ⚙️ Konfigurasi

### Database Configuration

Edit file `backend/.env`:

```env
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=olt_monitoring
DB_USER=olt_user
DB_PASSWORD=your_secure_password
```

### Telegram Bot Setup

1. **Buat Bot Baru**:
   - Chat dengan @BotFather di Telegram
   - Kirim `/newbot` dan ikuti instruksi
   - Copy bot token yang diberikan

2. **Konfigurasi via Dashboard**:
   - Buka Settings → Telegram Bot
   - Paste bot token
   - Klik "Test Bot" untuk verifikasi
   - Save settings

3. **Setup Chat ID** (Opsional):
   - Start chat dengan bot Anda
   - Kirim pesan apa saja
   - Bot akan otomatis detect chat ID

### Threshold Configuration

Konfigurasi threshold power dan distance via Settings → Thresholds:

#### Default Thresholds:

**RX Power:**
- 🟢 **Safe**: -8 dBm hingga -25 dBm
- 🟡 **Warning**: -25 dBm hingga -27 dBm  
- 🔴 **Danger**: < -27 dBm atau > -8 dBm

**Distance:**
- 🟢 **Safe**: 0-20 km
- 🟡 **Warning**: 20-25 km
- 🔴 **Danger**: > 25 km

## 🤖 Telegram Bot Commands

### Command Dasar

```
/start          - Mulai menggunakan bot
/status         - Status ringkasan semua OLT
/help           - Bantuan penggunaan
```

### Command Management

```bash
# Cek power ONT
/power <olt_name> <port> <ont_id>
# Contoh: /power oltA epon0/1 1

# Rename customer
/rename <olt_name> <port> <ont_id> <nama_baru>  
# Contoh: /rename oltA epon0/1 1 pelanggan-andi

# Status semua OLT
/status all
```

### Format Parameter

- **olt_name**: Nama OLT sesuai konfigurasi (contoh: oltA, oltB)
- **port**: Format port EPON (contoh: epon0/1, epon0/2)
- **ont_id**: ID ONT pada port (1, 2, 3, dst.)

## 📊 Dashboard Features

### 1. Dashboard Overview
- Status summary OLT dan ONT
- Card dengan indikator warna real-time
- Tabel ONT dengan filtering dan search
- Recent events timeline
- Power statistics chart

### 2. OLT Management
- Tambah/edit/delete OLT
- Test koneksi ke OLT
- Monitor status dan statistik ONT
- Support multiple OLT types

### 3. Events & Logs
- Real-time event monitoring  
- Filter berdasarkan severity dan type
- Event statistics dan charts
- Event cleanup management

### 4. Settings
- **Telegram Bot**: Token, chat ID, notifications
- **Thresholds**: Power dan distance limits
- **System**: Polling interval, system name

## 🔧 Development

### Project Structure

```
olt-monitoring/
├── backend/                 # Node.js backend
│   ├── database/           # Database models & migrations
│   ├── routes/             # API route handlers  
│   ├── services/           # Business logic services
│   ├── utils/              # Utility functions
│   └── server.js           # Main server file
├── frontend/               # React frontend
│   ├── public/             # Static assets
│   ├── src/
│   │   ├── components/     # Reusable components
│   │   ├── pages/          # Page components  
│   │   └── services/       # API services
└── README.md              # This file
```

### Backend API Endpoints

```
GET    /api/health                    # Health check
GET    /api/dashboard/summary         # Dashboard overview
GET    /api/dashboard/onts            # ONT data table
GET    /api/olts                      # List all OLTs
POST   /api/olts                      # Create new OLT  
PUT    /api/olts/:id                  # Update OLT
DELETE /api/olts/:id                  # Delete OLT
GET    /api/events                    # Get events
GET    /api/settings                  # Get settings
PUT    /api/settings                  # Update settings
```

### Development Commands

```bash
# Backend development
cd backend
npm run dev          # Start with nodemon
npm test             # Run tests
npm run migrate      # Run database migrations

# Frontend development  
cd frontend
npm start            # Start development server
npm test             # Run tests
npm run build        # Build for production
```

### Database Schema

#### Main Tables:
- **olts**: OLT configuration dan status
- **onts**: ONT data dan measurements  
- **events**: Event logging
- **settings**: System settings
- **thresholds**: Power/distance thresholds

## 📱 Mobile Support

Dashboard fully responsive dan dioptimalkan untuk:
- 📱 **Mobile phones** (320px+)
- 📱 **Tablets** (768px+) 
- 💻 **Desktop** (1024px+)

## 🔐 Security

- **Environment Variables**: Sensitive data di .env files
- **Input Validation**: Joi schema validation
- **Rate Limiting**: API rate limiting
- **SQL Injection Prevention**: Parameterized queries
- **CORS Protection**: Configured CORS policies

## 🐛 Troubleshooting

### Common Issues

**1. Database Connection Error**
```bash
# Cek MySQL service
net start mysql
# atau
sudo systemctl start mysql

# Cek credentials di .env file
```

**2. Port Already in Use**
```bash
# Cek port usage
netstat -ano | findstr :3001
netstat -ano | findstr :3000

# Kill process jika perlu
taskkill /PID <process_id> /F
```

**3. Telegram Bot Not Working**
- Verifikasi bot token di Settings
- Pastikan bot tidak diblok
- Cek network connectivity

**4. Frontend Build Errors**
```bash
# Clear node_modules dan reinstall
rm -rf node_modules package-lock.json
npm install
```

### Log Files

Backend logs tersedia di:
- `backend/logs/error.log` - Error logs
- `backend/logs/combined.log` - All logs

## 📈 Performance Optimization

### Backend
- Connection pooling untuk database
- Caching untuk frequently accessed data
- Rate limiting untuk API protection
- Efficient SQL queries dengan indexes

### Frontend  
- Code splitting dengan React.lazy
- Memoization untuk expensive computations
- Virtualized lists untuk large datasets
- Optimized bundle dengan Webpack

## 🔮 Roadmap

### v1.1 (Coming Soon)
- [ ] Historical power data charts
- [ ] Email notifications
- [ ] Export data to Excel/CSV
- [ ] Dark mode theme

### v1.2 (Future)
- [ ] User authentication & roles
- [ ] WhatsApp bot integration  
- [ ] Mobile app (React Native)
- [ ] Advanced analytics

### v2.0 (Long Term)
- [ ] Multi-tenant support
- [ ] Real-time WebSocket updates
- [ ] Machine learning predictions
- [ ] Cloud deployment templates

## 🤝 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)  
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👥 Support

- 📧 **Email**: support@yourcompany.com
- 💬 **Telegram**: @yourusername  
- 🐛 **Issues**: [GitHub Issues](https://github.com/yourusername/olt-monitoring/issues)
- 📚 **Documentation**: [Wiki](https://github.com/yourusername/olt-monitoring/wiki)

---

<div align="center">
  <h3>🌟 Made with ❤️ for Network Engineers 🌟</h3>
  <p>
    <a href="https://github.com/yourusername/olt-monitoring">⭐ Star this repository</a> •
    <a href="https://github.com/yourusername/olt-monitoring/fork">🍴 Fork</a> •
    <a href="https://github.com/yourusername/olt-monitoring/issues">🐛 Report Bug</a>
  </p>
</div>