const express = require('express');
const router = express.Router();
const database = require('../database/connection');
const logger = require('../utils/logger');

// Get all registered telegram users
router.get('/', async (req, res) => {
  try {
    const users = await database.query(`
      SELECT id, chat_id, username, first_name, last_name, chat_type, is_active, added_by, created_at
      FROM telegram_users 
      ORDER BY created_at DESC
    `);
    
    res.json(users);
  } catch (error) {
    logger.error('Error fetching telegram users:', error);
    res.status(500).json({ error: 'Failed to fetch telegram users' });
  }
});

// Add new telegram user/group to whitelist
router.post('/', async (req, res) => {
  try {
    const { chat_id, username, first_name, last_name, chat_type, added_by } = req.body;
    
    if (!chat_id) {
      return res.status(400).json({ error: 'Chat ID is required' });
    }
    
    const result = await database.query(`
      INSERT INTO telegram_users (chat_id, username, first_name, last_name, chat_type, added_by)
      VALUES (?, ?, ?, ?, ?, ?)
    `, [chat_id, username || null, first_name || null, last_name || null, chat_type || 'private', added_by || 'admin']);
    
    const [user] = await database.query('SELECT * FROM telegram_users WHERE id = ?', [result.insertId]);
    
    logger.info(`Telegram user added: ${chat_id} (${username || 'unknown'})`);
    res.status(201).json(user);
  } catch (error) {
    if (error.code === 'SQLITE_CONSTRAINT_UNIQUE' || error.message.includes('UNIQUE constraint failed')) {
      return res.status(409).json({ error: 'User already exists' });
    }
    logger.error('Error adding telegram user:', error);
    res.status(500).json({ error: 'Failed to add telegram user' });
  }
});

// Update telegram user status
router.put('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const { username, first_name, last_name, is_active } = req.body;
    
    const result = await database.query(`
      UPDATE telegram_users 
      SET username = ?, first_name = ?, last_name = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP
      WHERE id = ?
    `, [username, first_name, last_name, is_active, id]);
    
    if (result.affectedRows === 0) {
      return res.status(404).json({ error: 'User not found' });
    }
    
    const [user] = await database.query('SELECT * FROM telegram_users WHERE id = ?', [id]);
    
    logger.info(`Telegram user updated: ${id}`);
    res.json(user);
  } catch (error) {
    logger.error('Error updating telegram user:', error);
    res.status(500).json({ error: 'Failed to update telegram user' });
  }
});

// Delete telegram user from whitelist
router.delete('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    
    const result = await database.query('DELETE FROM telegram_users WHERE id = ?', [id]);
    
    if (result.affectedRows === 0) {
      return res.status(404).json({ error: 'User not found' });
    }
    
    logger.info(`Telegram user deleted: ${id}`);
    res.json({ message: 'User deleted successfully' });
  } catch (error) {
    logger.error('Error deleting telegram user:', error);
    res.status(500).json({ error: 'Failed to delete telegram user' });
  }
});

// Check if chat_id is allowed
router.get('/check/:chatId', async (req, res) => {
  try {
    const { chatId } = req.params;
    
    // First check if whitelist is enabled
    const whitelistSetting = await database.query(
      'SELECT value FROM settings WHERE key = ?', 
      ['telegram_whitelist_enabled']
    );
    
    const whitelistEnabled = whitelistSetting[0]?.value === 'true';
    
    if (!whitelistEnabled) {
      return res.json({ allowed: true, reason: 'Whitelist disabled' });
    }
    
    // Check if user exists and is active
    const [user] = await database.query(
      'SELECT * FROM telegram_users WHERE chat_id = ? AND is_active = TRUE',
      [chatId]
    );
    
    if (user) {
      res.json({ allowed: true, user });
    } else {
      res.json({ allowed: false, reason: 'User not in whitelist or inactive' });
    }
  } catch (error) {
    logger.error('Error checking telegram user:', error);
    res.status(500).json({ error: 'Failed to check user' });
  }
});

module.exports = router;