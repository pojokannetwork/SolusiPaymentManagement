# Panduan Instalasi Aplikasi Fiber Optic Management System

## Deskripsi Aplikasi

Aplikasi Fiber Optic Management System adalah sistem pencatatan sambungan kabel fiber optik 48 core 4 tube yang dilengkapi dengan fitur:

- **Sistem Login & Hak Akses**: Autentikasi pengguna dengan JWT token
- **Manajemen Joint Closures**: CRUD operations untuk joint closure dengan data lokasi
- **Pencatatan Core Connections**: Detail sambungan per core dengan informasi tube, warna, dan redaman
- **Integrasi Google Maps**: Peta lokasi joint closure dengan navigasi
- **Upload Foto**: Manajemen foto joint closure
- **GUI Responsif**: Tampilan yang optimal di desktop dan mobile
- **Easy Deployment**: Mudah diinstal di VPS atau hosting

## Arsitektur Sistem

- **Backend**: Flask (Python) dengan SQLite database
- **Frontend**: React dengan Vite dan Tailwind CSS
- **Authentication**: JWT (JSON Web Token)
- **File Upload**: Local file storage
- **Maps**: Google Maps Embed API

## Persyaratan Sistem

### Minimum Requirements
- **OS**: Ubuntu 20.04+ / CentOS 7+ / Windows 10+
- **RAM**: 1GB minimum, 2GB recommended
- **Storage**: 5GB free space
- **Python**: 3.8+
- **Node.js**: 18+
- **Internet**: Untuk Google Maps integration

### Recommended for Production
- **RAM**: 4GB+
- **CPU**: 2 cores+
- **Storage**: SSD 20GB+
- **Bandwidth**: 100Mbps+



## Instalasi Step-by-Step

### 1. Persiapan Server

#### Ubuntu/Debian
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install dependencies
sudo apt install -y python3 python3-pip python3-venv nodejs npm git curl

# Install pnpm (package manager untuk frontend)
npm install -g pnpm
```

#### CentOS/RHEL
```bash
# Update system
sudo yum update -y

# Install dependencies
sudo yum install -y python3 python3-pip nodejs npm git curl

# Install pnpm
npm install -g pnpm
```

### 2. Download Aplikasi

```bash
# Clone atau download aplikasi
# Jika menggunakan git:
git clone <repository-url> fiber-optic-app
cd fiber-optic-app

# Atau extract dari zip file:
unzip fiber-optic-app.zip
cd fiber-optic-app
```

### 3. Setup Backend (Flask)

```bash
# Masuk ke direktori backend
cd fiber-optic-app

# Buat virtual environment
python3 -m venv venv

# Aktifkan virtual environment
source venv/bin/activate  # Linux/Mac
# atau
venv\Scripts\activate     # Windows

# Install dependencies
pip install -r requirements.txt

# Buat direktori database dan uploads
mkdir -p src/database src/uploads

# Jalankan aplikasi untuk membuat database
python src/main.py
```

### 4. Setup Frontend (React)

```bash
# Buka terminal baru, masuk ke direktori frontend
cd fiber-optic-frontend

# Install dependencies
pnpm install

# Build aplikasi untuk production
pnpm run build
```

### 5. Konfigurasi Database

Database SQLite akan dibuat otomatis saat pertama kali menjalankan aplikasi. User default:
- **Username**: admin
- **Password**: admin123
- **Role**: admin

### 6. Konfigurasi Environment

Buat file `.env` di direktori backend:

```bash
# Backend configuration
SECRET_KEY=your-secret-key-here
DATABASE_URL=sqlite:///src/database/app.db
UPLOAD_FOLDER=src/uploads
FLASK_ENV=production
```

Buat file `.env` di direktori frontend:

```bash
# Frontend configuration
VITE_API_URL=http://your-domain.com/api
```


## Deployment Options

### Option 1: Deployment Manual di VPS

#### 1. Setup Nginx (Reverse Proxy)

```bash
# Install Nginx
sudo apt install nginx -y

# Buat konfigurasi site
sudo nano /etc/nginx/sites-available/fiber-optic
```

Isi file konfigurasi:

```nginx
server {
    listen 80;
    server_name your-domain.com;

    # Frontend (React build)
    location / {
        root /path/to/fiber-optic-frontend/dist;
        try_files $uri $uri/ /index.html;
    }

    # Backend API
    location /api {
        proxy_pass http://127.0.0.1:5003;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # Static files (uploads)
    location /api/uploads {
        alias /path/to/fiber-optic-app/src/uploads;
    }
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/fiber-optic /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

#### 2. Setup Systemd Service

```bash
# Buat service file
sudo nano /etc/systemd/system/fiber-optic.service
```

Isi file service:

```ini
[Unit]
Description=Fiber Optic Management System
After=network.target

[Service]
Type=simple
User=ubuntu
WorkingDirectory=/path/to/fiber-optic-app
Environment=PATH=/path/to/fiber-optic-app/venv/bin
ExecStart=/path/to/fiber-optic-app/venv/bin/python src/main.py
Restart=always

[Install]
WantedBy=multi-user.target
```

```bash
# Enable dan start service
sudo systemctl daemon-reload
sudo systemctl enable fiber-optic
sudo systemctl start fiber-optic
sudo systemctl status fiber-optic
```

### Option 2: Deployment dengan Docker

#### 1. Dockerfile untuk Backend

```dockerfile
# Dockerfile.backend
FROM python:3.11-slim

WORKDIR /app

COPY requirements.txt .
RUN pip install -r requirements.txt

COPY src/ ./src/
RUN mkdir -p src/database src/uploads

EXPOSE 5003

CMD ["python", "src/main.py"]
```

#### 2. Dockerfile untuk Frontend

```dockerfile
# Dockerfile.frontend
FROM node:18-alpine as builder

WORKDIR /app
COPY package.json pnpm-lock.yaml ./
RUN npm install -g pnpm && pnpm install

COPY . .
RUN pnpm run build

FROM nginx:alpine
COPY --from=builder /app/dist /usr/share/nginx/html
COPY nginx.conf /etc/nginx/nginx.conf

EXPOSE 80
```

#### 3. Docker Compose

```yaml
# docker-compose.yml
version: '3.8'

services:
  backend:
    build:
      context: ./fiber-optic-app
      dockerfile: Dockerfile.backend
    ports:
      - "5003:5003"
    volumes:
      - ./data:/app/src/database
      - ./uploads:/app/src/uploads
    environment:
      - FLASK_ENV=production

  frontend:
    build:
      context: ./fiber-optic-frontend
      dockerfile: Dockerfile.frontend
    ports:
      - "80:80"
    depends_on:
      - backend

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
    depends_on:
      - backend
      - frontend
```

```bash
# Deploy dengan Docker Compose
docker-compose up -d
```

### Option 3: Deployment di Shared Hosting

Untuk shared hosting yang mendukung Python dan Node.js:

1. **Upload files** ke direktori public_html
2. **Setup Python environment** sesuai panduan hosting
3. **Build frontend** dan upload ke subdirectory
4. **Konfigurasi .htaccess** untuk routing
5. **Setup database** (gunakan MySQL jika SQLite tidak didukung)


## Konfigurasi Lanjutan

### SSL/HTTPS Setup

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx -y

# Generate SSL certificate
sudo certbot --nginx -d your-domain.com

# Auto-renewal
sudo crontab -e
# Tambahkan: 0 12 * * * /usr/bin/certbot renew --quiet
```

### Backup Database

```bash
# Backup SQLite database
cp src/database/app.db backup/app_$(date +%Y%m%d_%H%M%S).db

# Backup uploads
tar -czf backup/uploads_$(date +%Y%m%d_%H%M%S).tar.gz src/uploads/
```

### Monitoring

```bash
# Check application status
sudo systemctl status fiber-optic

# Check logs
sudo journalctl -u fiber-optic -f

# Check Nginx logs
sudo tail -f /var/log/nginx/access.log
sudo tail -f /var/log/nginx/error.log
```

## Troubleshooting

### Common Issues

#### 1. Backend tidak bisa diakses
```bash
# Check if service is running
sudo systemctl status fiber-optic

# Check port
sudo netstat -tlnp | grep 5003

# Restart service
sudo systemctl restart fiber-optic
```

#### 2. Frontend tidak load
```bash
# Check Nginx configuration
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx

# Check file permissions
ls -la /path/to/frontend/dist/
```

#### 3. Database error
```bash
# Check database file permissions
ls -la src/database/

# Recreate database
rm src/database/app.db
python src/main.py
```

#### 4. Upload foto tidak berfungsi
```bash
# Check upload directory permissions
chmod 755 src/uploads/
chown -R ubuntu:ubuntu src/uploads/

# Check disk space
df -h
```

### Performance Optimization

#### 1. Database Optimization
```sql
-- Buat index untuk query yang sering digunakan
CREATE INDEX idx_joint_closure_name ON joint_closures(name);
CREATE INDEX idx_core_connection_closure ON core_connections(closure_id);
```

#### 2. Nginx Optimization
```nginx
# Tambahkan ke konfigurasi Nginx
gzip on;
gzip_types text/plain text/css application/json application/javascript;

# Cache static files
location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

#### 3. Application Optimization
```python
# Gunakan connection pooling untuk database
# Implementasi caching untuk query yang sering digunakan
# Optimize image upload dengan compression
```

## Maintenance

### Regular Tasks

#### Daily
- Monitor disk space
- Check application logs
- Verify backup completion

#### Weekly
- Update system packages
- Review security logs
- Test backup restoration

#### Monthly
- Update application dependencies
- Review user access
- Performance analysis

### Update Procedure

```bash
# 1. Backup current version
cp -r fiber-optic-app fiber-optic-app-backup

# 2. Stop services
sudo systemctl stop fiber-optic
sudo systemctl stop nginx

# 3. Update code
git pull origin main
# atau extract new version

# 4. Update dependencies
source venv/bin/activate
pip install -r requirements.txt

cd fiber-optic-frontend
pnpm install
pnpm run build

# 5. Restart services
sudo systemctl start fiber-optic
sudo systemctl start nginx

# 6. Verify update
curl http://localhost/api/health
```

## Security Considerations

### 1. Firewall Configuration
```bash
# UFW setup
sudo ufw allow ssh
sudo ufw allow 80
sudo ufw allow 443
sudo ufw enable
```

### 2. Application Security
- Change default admin password
- Use strong SECRET_KEY
- Regular security updates
- Monitor access logs
- Implement rate limiting

### 3. File Upload Security
- Validate file types
- Limit file sizes
- Scan for malware
- Store outside web root

## Support & Documentation

### Log Files Location
- Application logs: `/var/log/fiber-optic/`
- Nginx logs: `/var/log/nginx/`
- System logs: `journalctl -u fiber-optic`

### Configuration Files
- Backend config: `src/main.py`
- Frontend config: `vite.config.js`
- Nginx config: `/etc/nginx/sites-available/fiber-optic`
- Service config: `/etc/systemd/system/fiber-optic.service`

### Default Credentials
- **Username**: admin
- **Password**: admin123
- **Role**: admin

**⚠️ PENTING: Ganti password default setelah instalasi!**

