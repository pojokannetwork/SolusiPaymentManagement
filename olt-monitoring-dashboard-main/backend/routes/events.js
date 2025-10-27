const express = require('express');
const router = express.Router();
const database = require('../database/connection');
const logger = require('../utils/logger');

// Get events with pagination and filters
router.get('/', async (req, res) => {
  try {
    const { 
      page = 1, 
      limit = 50, 
      event_type = '', 
      severity = '', 
      olt_id = '',
      hours = 24 
    } = req.query;
    
    const offset = (page - 1) * limit;
    
    let whereConditions = [`e.created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)`];
    let params = [hours];
    
    if (event_type) {
      whereConditions.push('e.event_type = ?');
      params.push(event_type);
    }
    
    if (severity) {
      whereConditions.push('e.severity = ?');
      params.push(severity);
    }
    
    if (olt_id) {
      whereConditions.push('e.olt_id = ?');
      params.push(olt_id);
    }
    
    const whereClause = `WHERE ${whereConditions.join(' AND ')}`;
    
    // Get total count
    const [countResult] = await database.query(`
      SELECT COUNT(*) as total
      FROM events e
      ${whereClause}
    `, params);
    
    // Get events
    const events = await database.query(`
      SELECT 
        e.*,
        o.name as olt_name,
        o.location as olt_location,
        ont.customer_name,
        ont.port
      FROM events e
      JOIN olts o ON e.olt_id = o.id
      LEFT JOIN onts ont ON e.ont_id = ont.id
      ${whereClause}
      ORDER BY e.created_at DESC
      LIMIT ? OFFSET ?
    `, [...params, parseInt(limit), parseInt(offset)]);
    
    res.json({
      data: events,
      pagination: {
        current_page: parseInt(page),
        per_page: parseInt(limit),
        total: countResult?.total || 0,
        total_pages: Math.ceil((countResult?.total || 0) / limit)
      }
    });
  } catch (error) {
    logger.error('Error fetching events:', error);
    res.status(500).json({ error: 'Failed to fetch events' });
  }
});

// Get event statistics
router.get('/stats', async (req, res) => {
  try {
    const { hours = 24 } = req.query;
    
    const stats = await database.query(`
      SELECT 
        event_type,
        severity,
        COUNT(*) as count
      FROM events 
      WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
      GROUP BY event_type, severity
      ORDER BY count DESC
    `, [hours]);
    
    // Get events by hour for chart
    const hourlyStats = await database.query(`
      SELECT 
        DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as hour,
        COUNT(*) as count
      FROM events 
      WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
      GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')
      ORDER BY hour
    `, [hours]);
    
    res.json({
      event_types: stats,
      hourly: hourlyStats
    });
  } catch (error) {
    logger.error('Error fetching event statistics:', error);
    res.status(500).json({ error: 'Failed to fetch event statistics' });
  }
});

// Mark events as read/notified
router.put('/mark-notified', async (req, res) => {
  try {
    const { event_ids } = req.body;
    
    if (!event_ids || !Array.isArray(event_ids)) {
      return res.status(400).json({ error: 'Event IDs array is required' });
    }
    
    const placeholders = event_ids.map(() => '?').join(',');
    const result = await database.query(`
      UPDATE events 
      SET notified_telegram = TRUE 
      WHERE id IN (${placeholders})
    `, event_ids);
    
    logger.info(`Marked ${result.affectedRows} events as notified`);
    res.json({ 
      message: `Marked ${result.affectedRows} events as notified`,
      affected_rows: result.affectedRows 
    });
  } catch (error) {
    logger.error('Error marking events as notified:', error);
    res.status(500).json({ error: 'Failed to mark events as notified' });
  }
});

// Delete old events
router.delete('/cleanup', async (req, res) => {
  try {
    const { days = 30 } = req.query;
    
    const result = await database.query(`
      DELETE FROM events 
      WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
    `, [days]);
    
    logger.info(`Deleted ${result.affectedRows} old events (older than ${days} days)`);
    res.json({ 
      message: `Deleted ${result.affectedRows} old events`,
      deleted_count: result.affectedRows 
    });
  } catch (error) {
    logger.error('Error cleaning up events:', error);
    res.status(500).json({ error: 'Failed to cleanup events' });
  }
});

module.exports = router;