# SolusiPaymentManagement

Full-Stack Payment Gateway & ISP Operations Management System

## Overview

SolusiPaymentManagement is a comprehensive web-based application designed for ISP (Internet Service Provider) operations management. It combines payment gateway integration, customer management, billing, RADIUS/FreeRADIUS provisioning, MikroTik router management, and AI-powered assistance.

## Features

### Core Features
- **Multi-role Authentication**: Admin, Employee, and Customer portals
- **Customer Management**: CRUD operations with PPPoE credentials and geographic mapping
- **Billing & Invoices**: Create, send, and track invoices with payment integration
- **Payment Gateways**: Support for Midtrans, Xendit, Tripay, Duitku, DOKU, OVO, and GoPay
- **Transaction Management**: Track all payment transactions with callback handling
- **Asset Management**: Track company assets with depreciation calculations
- **Payroll System**: Employee salary management with overtime and deductions
- **Attendance Tracking**: Clock in/out with leave request management
- **Revenue & Tax Management**: Track income and calculate taxes

### ISP-Specific Features
- **RADIUS Integration**: FreeRADIUS MySQL backend with CoA (Change of Authorization)
- **MikroTik API**: Router management and PPPoE provisioning
- **Automatic Provisioning**: Activate/isolate customers based on payment status
- **Customer Mapping**: Geographic visualization using OpenStreetMap and Leaflet
- **Source of Truth**: Configurable between RADIUS and MikroTik

### AI & Automation
- **Ollama Integration**: Local AI assistant for NOC, call center, and billing support
- **Automated Provisioning**: Real-time service activation/deactivation
- **Smart Routing**: Intelligent customer support routing

### Security & Compliance
- **RBAC**: Granular role-based access control
- **CSRF Protection**: Token-based request validation
- **Rate Limiting**: Login attempt protection
- **Audit Logging**: Comprehensive activity tracking
- **Data Encryption**: Sensitive data protection

## Technology Stack

### Backend
- **PHP 7.4+**: Pure PHP without frameworks
- **MySQL/MariaDB**: Relational database
- **PDO**: Database abstraction layer
- **PSR-12**: Coding standards

### Frontend
- **Bootstrap 5**: Responsive UI framework
- **jQuery**: DOM manipulation and AJAX
- **DataTables**: Advanced table management
- **Chart.js**: Data visualization
- **Leaflet**: Interactive maps

### Integrations
- **MikroTik RouterOS API**: Router management
- **FreeRADIUS**: AAA server integration
- **Ollama**: Local AI model serving
- **OpenStreetMap/Nominatim**: Geocoding and mapping

## Installation

### Prerequisites
- Apache 2.x with mod_rewrite
- PHP 7.4+ with extensions:
  - pdo_mysql
  - curl
  - mbstring
  - zip
  - gd
- MySQL 5.7+ or MariaDB 10.3+
- Composer (optional, for dependencies)

### Step 1: Clone and Setup
```bash
git clone <repository-url>
cd SolusiPaymentManagement
```

### Step 2: Database Setup
```bash
mysql -u root -p
CREATE DATABASE solusipaymentmanagement CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit

# Import schema
mysql -u root -p solusipaymentmanagement < database/schema.sql
```

### Step 3: Configuration
Edit configuration files:
```bash
# Database configuration
cp config/database.php.example config/database.php
# Edit database credentials

# Application configuration
cp config/app.php.example config/app.php
# Edit application settings
```

### Step 4: Web Server Configuration
Configure Apache virtual host:
```apache
<VirtualHost *:80>
    ServerName solusipayment.local
    DocumentRoot /path/to/SolusiPaymentManagement

    <Directory /path/to/SolusiPaymentManagement>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/solusipayment_error.log
    CustomLog ${APACHE_LOG_DIR}/solusipayment_access.log combined
</VirtualHost>
```

Create `.htaccess` file:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . index.php [L]
```

### Step 5: Permissions
```bash
chmod 755 .
chmod 777 assets/uploads/
chmod 777 logs/
```

### Step 6: Seed Admin User
The schema includes a default admin user:
- Email: admin@solusipayment.local
- Password: Admin123!
- **Important**: Change this password after first login!

## FreeRADIUS Setup

### 1. Install FreeRADIUS
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install freeradius freeradius-mysql

# CentOS/RHEL
sudo yum install freeradius freeradius-mysql
```

### 2. Configure MySQL Backend
Edit `/etc/freeradius/3.0/mods-available/sql`:
```ini
sql {
    driver = "rlm_sql_mysql"
    dialect = "mysql"

    server = "localhost"
    port = 3306
    login = "radius"
    password = "your_radius_password"
    radius_db = "radius"

    # Other settings...
}
```

### 3. Enable SQL Module
```bash
sudo ln -s /etc/freeradius/3.0/mods-available/sql /etc/freeradius/3.0/mods-enabled/
```

### 4. Configure Clients
Edit `/etc/freeradius/3.0/clients.conf`:
```ini
client mikrotik-router {
    ipaddr = 192.168.1.1
    secret = your_nas_secret
    require_message_authenticator = no
}
```

### 5. Enable CoA
Edit `/etc/freeradius/3.0/sites-available/default`:
```ini
# Add to authorize section
coa {
    ok
}

# Add to post-auth section
Post-Auth-Type CoA {
    coa
}
```

### 6. Restart FreeRADIUS
```bash
sudo systemctl restart freeradius
```

## MikroTik Setup

### 1. Enable API
```
/system package enable api
/system reboot
```

### 2. Configure API User
```
/user group add name=api-group policy=api
/user add name=api-user group=api-group password=your_api_password
```

### 3. Enable API Service
```
/ip service set api disabled=no port=8728
/ip service set api-ssl disabled=no port=8729
```

### 4. Configure RADIUS Client
```
/radius add address=your_radius_server secret=your_nas_secret service=ppp
/radius incoming set accept=yes port=3799
```

### 5. Create PPP Profiles
```
/ppp profile add name=default local-address=192.168.1.1 remote-address=pool1
/ppp profile add name=ISOLIR rate-limit=1M/1M local-address=192.168.1.1 remote-address=pool1
```

## Ollama Setup

### 1. Install Ollama
```bash
curl -fsSL https://ollama.ai/install.sh | sh
```

### 2. Pull Model
```bash
ollama pull llama3
```

### 3. Configure Service
```bash
sudo systemctl enable ollama
sudo systemctl start ollama
```

### 4. Test Installation
```bash
curl http://localhost:11434/api/tags
```

## Usage

### Admin Portal
- Access: `/admin`
- Features: Full system management, customer operations, reporting

### Employee Portal
- Access: `/employee`
- Features: Attendance, leave requests, personal information

### Customer Portal
- Access: `/customer`
- Features: Invoice viewing, payment, service status

### API Endpoints
- Authentication: `/api/public/login`
- Customers: `/api/admin/customers`
- Invoices: `/api/admin/invoices`
- Payments: `/api/payment_callback.php`

## Security Considerations

### Production Deployment
1. **HTTPS Only**: Configure SSL certificates
2. **Firewall**: Restrict access to necessary ports only
3. **Database**: Use separate user with minimal privileges
4. **Backups**: Regular automated backups
5. **Updates**: Keep all components updated

### Password Policy
- Minimum 8 characters
- Mixed case, numbers, symbols
- Regular password changes

### Session Management
- Secure session cookies
- Session timeout after inactivity
- Regenerate session IDs

## Troubleshooting

### Common Issues

1. **Login Issues**
   - Check database connection
   - Verify user credentials
   - Check rate limiting

2. **Payment Gateway Issues**
   - Verify API credentials
   - Check webhook URLs
   - Review gateway logs

3. **RADIUS Issues**
   - Test database connectivity
   - Check CoA configuration
   - Verify NAS settings

4. **MikroTik Issues**
   - Test API connectivity
   - Verify user permissions
   - Check router configuration

### Logs
- Application logs: `logs/app.log`
- Apache error logs: `/var/log/apache2/`
- RADIUS logs: `/var/log/freeradius/`

## Contributing

1. Fork the repository
2. Create feature branch
3. Commit changes
4. Push to branch
5. Create Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support and questions:
- Documentation: This README
- Issues: GitHub Issues
- Email: support@solusipayment.local

## Changelog

### Version 1.0.0
- Initial release
- Core features implementation
- RADIUS and MikroTik integration
- Payment gateway support
- AI assistant integration
