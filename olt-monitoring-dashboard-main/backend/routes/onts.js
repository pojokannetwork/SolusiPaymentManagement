const express = require('express');
const router = express.Router();
const database = require('../database/connection');
const logger = require('../utils/logger');

// Get all ONTs
router.get('/', async (req, res) => {
  try {
    const { olt_id, status, search } = req.query;
    
    let whereConditions = [];
    let params = [];
    
    if (olt_id) {
      whereConditions.push('ont.olt_id = ?');
      params.push(olt_id);
    }
    
    if (status) {
      whereConditions.push('ont.status = ?');
      params.push(status);
    }
    
    if (search) {
      whereConditions.push('(ont.customer_name LIKE ? OR ont.port LIKE ?)');
      params.push(`%${search}%`, `%${search}%`);
    }
    
    const whereClause = whereConditions.length > 0 ? `WHERE ${whereConditions.join(' AND ')}` : '';
    
    const onts = await database.query(`
      SELECT 
        ont.*,
        o.name as olt_name,
        o.location as olt_location
      FROM onts ont
      JOIN olts o ON ont.olt_id = o.id
      ${whereClause}
      ORDER BY ont.updated_at DESC
    `, params);
    
    res.json(onts);
  } catch (error) {
    logger.error('Error fetching ONTs:', error);
    res.status(500).json({ error: 'Failed to fetch ONTs' });
  }
});

// Get single ONT by ID
router.get('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const [ont] = await database.query(`
      SELECT 
        ont.*,
        o.name as olt_name,
        o.location as olt_location,
        o.ip_address as olt_ip
      FROM onts ont
      JOIN olts o ON ont.olt_id = o.id
      WHERE ont.id = ?
    `, [id]);
    
    if (!ont) {
      return res.status(404).json({ error: 'ONT not found' });
    }
    
    // Get recent events for this ONT
    const events = await database.query(`
      SELECT * FROM events 
      WHERE ont_id = ? 
      ORDER BY created_at DESC 
      LIMIT 10
    `, [id]);
    
    res.json({ ...ont, recent_events: events });
  } catch (error) {
    logger.error('Error fetching ONT:', error);
    res.status(500).json({ error: 'Failed to fetch ONT' });
  }
});

// Update ONT customer name
router.put('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const { customer_name } = req.body;
    
    if (!customer_name) {
      return res.status(400).json({ error: 'Customer name is required' });
    }
    
    const result = await database.query(
      'UPDATE onts SET customer_name = ? WHERE id = ?',
      [customer_name, id]
    );
    
    if (result.affectedRows === 0) {
      return res.status(404).json({ error: 'ONT not found' });
    }
    
    // Get updated ONT
    const [ont] = await database.query('SELECT * FROM onts WHERE id = ?', [id]);
    
    logger.info(`ONT customer name updated: ${ont.port} -> ${customer_name}`);
    res.json(ont);
  } catch (error) {
    logger.error('Error updating ONT:', error);
    res.status(500).json({ error: 'Failed to update ONT' });
  }
});

// Get ONT power history (for future implementation)
router.get('/:id/power-history', async (req, res) => {
  try {
    const { id } = req.params;
    const { hours = 24 } = req.query;
    
    // For now, return current power data
    // In future, implement power history table
    const [ont] = await database.query(`
      SELECT rx_power, tx_power, distance, updated_at
      FROM onts 
      WHERE id = ?
    `, [id]);
    
    if (!ont) {
      return res.status(404).json({ error: 'ONT not found' });
    }
    
    // Return single point for now
    res.json([{
      timestamp: ont.updated_at,
      rx_power: ont.rx_power,
      tx_power: ont.tx_power,
      distance: ont.distance
    }]);
  } catch (error) {
    logger.error('Error fetching ONT power history:', error);
    res.status(500).json({ error: 'Failed to fetch power history' });
  }
});

// Get ONTs by OLT with port information
router.get('/by-olt/:oltId', async (req, res) => {
  try {
    const { oltId } = req.params;
    
    // Get OLT info
    const [olt] = await database.query('SELECT * FROM olts WHERE id = ?', [oltId]);
    if (!olt) {
      return res.status(404).json({ error: 'OLT not found' });
    }
    
    // Get all ONTs for this OLT, organized by port
    const onts = await database.query(`
      SELECT * FROM onts 
      WHERE olt_id = ? 
      ORDER BY port, ont_id
    `, [oltId]);
    
    // Group by port
    const portGroups = {};
    onts.forEach(ont => {
      if (!portGroups[ont.port]) {
        portGroups[ont.port] = [];
      }
      portGroups[ont.port].push(ont);
    });
    
    res.json({
      olt: olt,
      ports: portGroups
    });
  } catch (error) {
    logger.error('Error fetching ONTs by OLT:', error);
    res.status(500).json({ error: 'Failed to fetch ONTs' });
  }
});

module.exports = router;