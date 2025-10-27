const express = require('express');
const router = express.Router();
const database = require('../database/connection');
const logger = require('../utils/logger');

// Get dashboard summary
router.get('/summary', async (req, res) => {
  try {
    // Get OLT summary
    const [oltSummary] = await database.query(`
      SELECT 
        COUNT(*) as total_olts,
        SUM(CASE WHEN status = 'online' THEN 1 ELSE 0 END) as online_olts,
        SUM(CASE WHEN status = 'offline' THEN 1 ELSE 0 END) as offline_olts
      FROM olts
    `);

    // Get ONT summary
    const [ontSummary] = await database.query(`
      SELECT 
        COUNT(*) as total_onts,
        SUM(CASE WHEN status = 'online' THEN 1 ELSE 0 END) as online_onts,
        SUM(CASE WHEN status = 'offline' THEN 1 ELSE 0 END) as offline_onts,
        SUM(CASE WHEN status = 'los' THEN 1 ELSE 0 END) as los_onts
      FROM onts
    `);

    // Get power warnings
    const powerWarnings = await database.query(`
      SELECT COUNT(*) as count FROM onts 
      WHERE rx_power IS NOT NULL AND (rx_power < -25 OR rx_power > -8)
    `);

    // Get distance warnings
    const distanceWarnings = await database.query(`
      SELECT COUNT(*) as count FROM onts 
      WHERE distance IS NOT NULL AND distance > 20
    `);

    // Get recent events
    const recentEvents = await database.query(`
      SELECT 
        e.*,
        o.name as olt_name,
        ont.serial_number,
        ont.pon_port
      FROM events e
      JOIN olts o ON e.olt_id = o.id
      LEFT JOIN onts ont ON e.ont_id = ont.id
      ORDER BY e.created_at DESC
      LIMIT 10
    `);

    res.json({
      olts: oltSummary || { total_olts: 0, online_olts: 0, offline_olts: 0 },
      onts: ontSummary || { total_onts: 0, online_onts: 0, offline_onts: 0, los_onts: 0 },
      warnings: {
        power: powerWarnings[0]?.count || 0,
        distance: distanceWarnings[0]?.count || 0
      },
      recent_events: recentEvents
    });
  } catch (error) {
    logger.error('Error fetching dashboard summary:', error);
    res.status(500).json({ error: 'Failed to fetch dashboard summary' });
  }
});

// Get ONT data for dashboard table
router.get('/onts', async (req, res) => {
  try {
    const { page = 1, limit = 50, search = '', olt_id = '', status = '' } = req.query;
    const offset = (page - 1) * limit;

    let whereConditions = [];
    let params = [];

    if (search) {
      whereConditions.push('(ont.customer_name LIKE ? OR ont.port LIKE ? OR o.name LIKE ?)');
      params.push(`%${search}%`, `%${search}%`, `%${search}%`);
    }

    if (olt_id) {
      whereConditions.push('ont.olt_id = ?');
      params.push(olt_id);
    }

    if (status) {
      whereConditions.push('ont.status = ?');
      params.push(status);
    }

    const whereClause = whereConditions.length > 0 ? `WHERE ${whereConditions.join(' AND ')}` : '';

    // Get total count
    const [countResult] = await database.query(`
      SELECT COUNT(*) as total
      FROM onts ont
      JOIN olts o ON ont.olt_id = o.id
      ${whereClause}
    `, params);

    // Get ONTs data
    const onts = await database.query(`
      SELECT 
        ont.*,
        o.name as olt_name,
        o.ip_address as olt_ip,
        CASE 
          WHEN ont.rx_power IS NULL THEN 'unknown'
          WHEN ont.rx_power >= -8 AND ont.rx_power <= -25 THEN 'safe'
          WHEN ont.rx_power > -27 AND ont.rx_power < -25 THEN 'warning'
          ELSE 'danger'
        END as rx_power_status,
        CASE 
          WHEN ont.distance IS NULL THEN 'unknown'
          WHEN ont.distance <= 20 THEN 'safe'
          WHEN ont.distance <= 25 THEN 'warning'
          ELSE 'danger'
        END as distance_status
      FROM onts ont
      JOIN olts o ON ont.olt_id = o.id
      ${whereClause}
      ORDER BY ont.updated_at DESC
      LIMIT ? OFFSET ?
    `, [...params, parseInt(limit), parseInt(offset)]);

    res.json({
      data: onts,
      pagination: {
        current_page: parseInt(page),
        per_page: parseInt(limit),
        total: countResult?.total || 0,
        total_pages: Math.ceil((countResult?.total || 0) / limit)
      }
    });
  } catch (error) {
    logger.error('Error fetching dashboard ONTs:', error);
    res.status(500).json({ error: 'Failed to fetch ONTs data' });
  }
});

// Get power statistics for charts
router.get('/power-stats', async (req, res) => {
  try {
    const stats = await database.query(`
      SELECT 
        o.name as olt_name,
        AVG(ont.rx_power) as avg_rx_power,
        MIN(ont.rx_power) as min_rx_power,
        MAX(ont.rx_power) as max_rx_power,
        COUNT(ont.id) as ont_count
      FROM olts o
      LEFT JOIN onts ont ON o.id = ont.olt_id AND ont.rx_power IS NOT NULL
      GROUP BY o.id, o.name
      ORDER BY o.name
    `);

    res.json(stats);
  } catch (error) {
    logger.error('Error fetching power statistics:', error);
    res.status(500).json({ error: 'Failed to fetch power statistics' });
  }
});

// Get events for timeline
router.get('/events', async (req, res) => {
  try {
    const { hours = 24 } = req.query;
    
    const events = await database.query(`
      SELECT 
        e.*,
        o.name as olt_name,
        ont.serial_number,
        ont.pon_port
      FROM events e
      JOIN olts o ON e.olt_id = o.id
      LEFT JOIN onts ont ON e.ont_id = ont.id
      WHERE e.created_at >= datetime('now', '-' || ? || ' hours')
      ORDER BY e.created_at DESC
      LIMIT 100
    `, [hours]);

    res.json(events);
  } catch (error) {
    logger.error('Error fetching events:', error);
    res.status(500).json({ error: 'Failed to fetch events' });
  }
});

module.exports = router;