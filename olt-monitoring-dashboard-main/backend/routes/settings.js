const express = require('express');
const router = express.Router();
const database = require('../database/connection');
const logger = require('../utils/logger');
const telegramService = require('../services/telegramService');

// Get all settings
router.get('/', async (req, res) => {
  try {
    const settings = await database.query('SELECT * FROM settings ORDER BY key');
    const thresholds = await database.query('SELECT * FROM thresholds ORDER BY threshold_type');
    
    res.json({
      settings: settings.reduce((acc, setting) => {
        acc[setting.key] = {
          value: setting.value,
          description: setting.description
        };
        return acc;
      }, {}),
      thresholds: thresholds.reduce((acc, threshold) => {
        acc[threshold.threshold_type] = {
          safe_min: threshold.safe_min,
          safe_max: threshold.safe_max,
          warning_min: threshold.warning_min,
          warning_max: threshold.warning_max,
          danger_min: threshold.danger_min,
          danger_max: threshold.danger_max
        };
        return acc;
      }, {})
    });
  } catch (error) {
    logger.error('Error fetching settings:', error);
    res.status(500).json({ error: 'Failed to fetch settings' });
  }
});

// Update settings
router.put('/', async (req, res) => {
  try {
    const { settings, thresholds } = req.body;
    
    // Update settings
    if (settings) {
      for (const [key, value] of Object.entries(settings)) {
        await database.query(`
          INSERT OR REPLACE INTO settings (key, value) 
          VALUES (?, ?)
        `, [key, value]);
      }
    }
    
    // Update thresholds
    if (thresholds) {
      for (const [type, values] of Object.entries(thresholds)) {
        await database.query(`
          UPDATE thresholds 
          SET safe_min = ?, safe_max = ?, warning_min = ?, warning_max = ?, danger_min = ?, danger_max = ?
          WHERE threshold_type = ?
        `, [
          values.safe_min, values.safe_max,
          values.warning_min, values.warning_max,
          values.danger_min, values.danger_max,
          type
        ]);
      }
    }
    
    // If Telegram bot token was updated, reinitialize the bot
    if (settings && settings.telegram_bot_token) {
      await telegramService.updateBotToken(settings.telegram_bot_token);
    }
    
    logger.info('Settings updated successfully');
    res.json({ message: 'Settings updated successfully' });
  } catch (error) {
    logger.error('Error updating settings:', error);
    res.status(500).json({ error: 'Failed to update settings' });
  }
});

// Test Telegram bot
router.post('/test-telegram', async (req, res) => {
  try {
    const { token, chat_id } = req.body;
    
    if (!token) {
      return res.status(400).json({ error: 'Bot token is required' });
    }
    
    const result = await telegramService.testBot(token, chat_id);
    
    if (result.success) {
      res.json({ message: 'Telegram bot test successful', data: result });
    } else {
      res.status(400).json({ error: result.error });
    }
  } catch (error) {
    logger.error('Error testing Telegram bot:', error);
    res.status(500).json({ error: 'Failed to test Telegram bot' });
  }
});

// Get Telegram bot info
router.get('/telegram-info', async (req, res) => {
  try {
    const info = await telegramService.getBotInfo();
    res.json(info);
  } catch (error) {
    logger.error('Error getting Telegram bot info:', error);
    res.status(500).json({ error: 'Failed to get bot info' });
  }
});

// Get system statistics
router.get('/stats', async (req, res) => {
  try {
    const stats = {
      uptime: process.uptime(),
      memory: process.memoryUsage(),
      node_version: process.version,
      platform: process.platform,
      timestamp: new Date().toISOString()
    };
    
    // Database stats
    const [oltCount] = await database.query('SELECT COUNT(*) as count FROM olts');
    const [ontCount] = await database.query('SELECT COUNT(*) as count FROM onts');
    const [eventCount] = await database.query('SELECT COUNT(*) as count FROM events WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)');
    
    stats.database = {
      olts: oltCount?.count || 0,
      onts: ontCount?.count || 0,
      events_24h: eventCount?.count || 0
    };
    
    res.json(stats);
  } catch (error) {
    logger.error('Error fetching system stats:', error);
    res.status(500).json({ error: 'Failed to fetch system stats' });
  }
});

module.exports = router;