const sqlite3 = require('sqlite3').verbose();
const path = require('path');
const fs = require('fs').promises;

async function setupSQLite() {
    console.log('🔧 Setting up SQLite Database (Alternative)');
    console.log('==========================================\n');

    const dbPath = path.join(__dirname, 'database', 'olt_monitoring.db');
    
    // Create database directory if not exists
    const dbDir = path.dirname(dbPath);
    try {
        await fs.access(dbDir);
    } catch {
        await fs.mkdir(dbDir, { recursive: true });
    }

    return new Promise((resolve, reject) => {
        const db = new sqlite3.Database(dbPath, (err) => {
            if (err) {
                console.error('❌ Error creating SQLite database:', err.message);
                reject(err);
                return;
            }
            console.log('✅ Connected to SQLite database');
        });

        // Create tables
        const createTables = `
            -- OLTs table
            CREATE TABLE IF NOT EXISTS olts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                ip_address TEXT NOT NULL UNIQUE,
                port INTEGER DEFAULT 22,
                username TEXT NOT NULL,
                password TEXT NOT NULL,
                type TEXT DEFAULT 'epon',
                status TEXT DEFAULT 'offline',
                last_poll DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            -- ONTs table
            CREATE TABLE IF NOT EXISTS onts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                olt_id INTEGER NOT NULL,
                ont_id TEXT NOT NULL,
                pon_port TEXT NOT NULL,
                serial_number TEXT,
                mac_address TEXT,
                status TEXT DEFAULT 'offline',
                rx_power REAL,
                tx_power REAL,
                distance REAL,
                temperature REAL,
                voltage REAL,
                last_seen DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (olt_id) REFERENCES olts (id) ON DELETE CASCADE,
                UNIQUE(olt_id, ont_id, pon_port)
            );

            -- Events table
            CREATE TABLE IF NOT EXISTS events (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                olt_id INTEGER,
                ont_id INTEGER,
                event_type TEXT NOT NULL,
                severity TEXT DEFAULT 'info',
                title TEXT NOT NULL,
                description TEXT,
                metadata TEXT,
                acknowledged BOOLEAN DEFAULT FALSE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (olt_id) REFERENCES olts (id) ON DELETE CASCADE,
                FOREIGN KEY (ont_id) REFERENCES onts (id) ON DELETE CASCADE
            );

            -- Settings table
            CREATE TABLE IF NOT EXISTS settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                category TEXT NOT NULL,
                key TEXT NOT NULL,
                value TEXT,
                description TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(category, key)
            );

            -- Thresholds table
            CREATE TABLE IF NOT EXISTS thresholds (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                metric_type TEXT NOT NULL,
                threshold_type TEXT NOT NULL,
                min_value REAL,
                max_value REAL,
                unit TEXT,
                severity TEXT DEFAULT 'warning',
                enabled BOOLEAN DEFAULT TRUE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(metric_type, threshold_type)
            );
        `;

        db.exec(createTables, (err) => {
            if (err) {
                console.error('❌ Error creating tables:', err.message);
                reject(err);
                return;
            }
            console.log('✅ Database tables created');

            // Insert default data
            const insertDefaults = `
                -- Default thresholds
                INSERT OR REPLACE INTO thresholds (metric_type, threshold_type, min_value, max_value, unit, severity) VALUES
                ('rx_power', 'safe', -25, -8, 'dBm', 'info'),
                ('rx_power', 'warning', -27, -25, 'dBm', 'warning'),
                ('rx_power', 'danger', NULL, -27, 'dBm', 'critical'),
                ('tx_power', 'safe', 0, 5, 'dBm', 'info'),
                ('distance', 'warning', 20, NULL, 'km', 'warning'),
                ('distance', 'danger', 25, NULL, 'km', 'critical');

                -- Default settings
                INSERT OR REPLACE INTO settings (category, key, value, description) VALUES
                ('telegram', 'bot_token', '', 'Telegram Bot Token'),
                ('telegram', 'chat_id', '', 'Default Chat ID for notifications'),
                ('telegram', 'notifications_enabled', 'true', 'Enable/disable notifications'),
                ('polling', 'interval', '300', 'Polling interval in seconds'),
                ('polling', 'enabled', 'true', 'Enable/disable polling'),
                ('alerts', 'rx_power_enabled', 'true', 'Enable RX power alerts'),
                ('alerts', 'tx_power_enabled', 'true', 'Enable TX power alerts'),
                ('alerts', 'distance_enabled', 'true', 'Enable distance alerts');
            `;

            db.exec(insertDefaults, (err) => {
                if (err) {
                    console.error('❌ Error inserting default data:', err.message);
                    reject(err);
                    return;
                }
                console.log('✅ Default data inserted');

                db.close((err) => {
                    if (err) {
                        console.error('❌ Error closing database:', err.message);
                        reject(err);
                        return;
                    }
                    console.log('✅ Database setup complete');
                    resolve();
                });
            });
        });
    });
}

async function updateConfig() {
    console.log('📝 Updating configuration for SQLite...');
    
    const envContent = `# Database Configuration (SQLite)
DB_TYPE=sqlite
DB_PATH=database/olt_monitoring.db

# Server Configuration
PORT=3001
NODE_ENV=development

# JWT Secret (change in production)
JWT_SECRET=your-super-secret-jwt-key-change-in-production

# Telegram Bot Configuration (will be set via web interface)
TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=

# Logging
LOG_LEVEL=info
LOG_FILE=logs/app.log
`;

    const envPath = path.join(__dirname, '.env');
    await fs.writeFile(envPath, envContent);
    console.log('✅ Configuration saved');
}

async function main() {
    try {
        await setupSQLite();
        await updateConfig();
        
        console.log('\n🎉 SQLite Setup Complete!');
        console.log('==========================');
        console.log('✅ Database: SQLite (olt_monitoring.db)');
        console.log('✅ Tables: Created');
        console.log('✅ Default data: Inserted');
        console.log('✅ Configuration: Updated');
        
        console.log('\n🚀 Ready to start!');
        console.log('1. Run: npm run dev');
        console.log('2. Open: http://localhost:3001/api/health');
        console.log('3. Start frontend: cd ../frontend && npm start');
        
    } catch (error) {
        console.error('❌ Setup failed:', error.message);
        process.exit(1);
    }
}

main();