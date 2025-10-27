const database = require('./connection');
const logger = require('../utils/logger');

const migrations = [
  // Create OLTs table
  `CREATE TABLE IF NOT EXISTS olts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(255),
    ip_address VARCHAR(45) NOT NULL,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    olt_type ENUM('hioso', 'zte', 'huawei', 'other') DEFAULT 'hioso',
    status ENUM('online', 'offline', 'error') DEFAULT 'offline',
    total_ports INT DEFAULT 4,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_ip (ip_address)
  )`,

  // Create ONTs table
  `CREATE TABLE IF NOT EXISTS onts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    olt_id INT NOT NULL,
    port VARCHAR(20) NOT NULL,
    ont_id INT NOT NULL,
    customer_name VARCHAR(100),
    mac_address VARCHAR(17),
    status ENUM('online', 'offline', 'los', 'error') DEFAULT 'offline',
    rx_power DECIMAL(5,2),
    tx_power DECIMAL(5,2),
    distance DECIMAL(8,2),
    last_seen TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (olt_id) REFERENCES olts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_ont (olt_id, port, ont_id)
  )`,

  // Create Events table
  `CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    olt_id INT NOT NULL,
    ont_id INT,
    event_type ENUM('ont_online', 'ont_offline', 'ont_los', 'power_warning', 'distance_warning', 'olt_offline', 'olt_online') NOT NULL,
    message TEXT NOT NULL,
    severity ENUM('info', 'warning', 'error', 'critical') DEFAULT 'info',
    notified_telegram BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (olt_id) REFERENCES olts(id) ON DELETE CASCADE,
    FOREIGN KEY (ont_id) REFERENCES onts(id) ON DELETE SET NULL,
    INDEX idx_created_at (created_at),
    INDEX idx_event_type (event_type),
    INDEX idx_severity (severity)
  )`,

  // Create Settings table
  `CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  )`,

  // Create Thresholds table
  `CREATE TABLE IF NOT EXISTS thresholds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    threshold_type ENUM('rx_power', 'tx_power', 'distance') NOT NULL,
    safe_min DECIMAL(8,2),
    safe_max DECIMAL(8,2),
    warning_min DECIMAL(8,2),
    warning_max DECIMAL(8,2),
    danger_min DECIMAL(8,2),
    danger_max DECIMAL(8,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_threshold_type (threshold_type)
  )`,

  // Insert default thresholds
  `INSERT IGNORE INTO thresholds (threshold_type, safe_min, safe_max, warning_min, warning_max, danger_min, danger_max) VALUES
    ('rx_power', -25.0, -8.0, -27.0, -25.0, -999.0, -27.0),
    ('tx_power', -8.0, 8.0, -10.0, -8.0, -999.0, -10.0),
    ('distance', 0.0, 20.0, 20.0, 25.0, 25.0, 999.0)`,

  // Insert default settings
  `INSERT IGNORE INTO settings (setting_key, setting_value, description) VALUES
    ('telegram_bot_token', '', 'Telegram Bot Token from @BotFather'),
    ('telegram_chat_id', '', 'Default Telegram Chat ID for notifications'),
    ('polling_interval', '30000', 'OLT polling interval in milliseconds'),
    ('notification_enabled', 'true', 'Enable/disable Telegram notifications'),
    ('system_name', 'OLT Monitoring System', 'System name for notifications')`
];

async function runMigrations() {
  try {
    logger.info('Starting database migrations...');
    
    for (let i = 0; i < migrations.length; i++) {
      logger.info(`Running migration ${i + 1}/${migrations.length}`);
      await database.query(migrations[i]);
    }
    
    logger.info('Database migrations completed successfully');
  } catch (error) {
    logger.error('Migration failed:', error);
    throw error;
  }
}

// Run migrations if this file is executed directly
if (require.main === module) {
  runMigrations()
    .then(() => {
      logger.info('Migrations completed');
      process.exit(0);
    })
    .catch((error) => {
      logger.error('Migration failed:', error);
      process.exit(1);
    });
}

module.exports = { runMigrations };