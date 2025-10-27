const database = require('./connection');
const logger = require('../utils/logger');

async function addTelegramUsersTable() {
  try {
    await database.connect();
    
    // Create telegram_users table for whitelist
    const createTableQuery = `
      CREATE TABLE IF NOT EXISTS telegram_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        chat_id TEXT NOT NULL UNIQUE,
        username TEXT,
        first_name TEXT,
        last_name TEXT,
        chat_type TEXT CHECK(chat_type IN ('private', 'group', 'supergroup', 'channel')) DEFAULT 'private',
        is_active BOOLEAN DEFAULT TRUE,
        added_by TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )
    `;
    
    await database.query(createTableQuery);
    logger.info('✅ Telegram users table created successfully');
    
    // Add some default settings for telegram user management
    const settings = [
      ['telegram_whitelist_enabled', 'false', 'Enable telegram user whitelist'],
      ['telegram_auto_register', 'false', 'Auto register new users when they first interact with bot']
    ];
    
    for (const [key, value, description] of settings) {
      await database.query(`
        INSERT OR IGNORE INTO settings (key, value, description)
        VALUES (?, ?, ?)
      `, [key, value, description]);
    }
    
    logger.info('✅ Telegram user management settings added');
    console.log('Telegram users table and settings have been created successfully!');
    
  } catch (error) {
    logger.error('Error creating telegram users table:', error);
    console.error('Failed to create telegram users table:', error);
  } finally {
    if (database.db) {
      database.db.close();
    }
    process.exit(0);
  }
}

// Run the migration
addTelegramUsersTable();