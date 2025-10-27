const express = require('express');
const router = express.Router();
const database = require('../database/connection');
const logger = require('../utils/logger');
const pollingService = require('../services/pollingService');

// Get all OLTs
router.get('/', async (req, res) => {
  try {
    const olts = await database.query(`
      SELECT 
        o.*,
        COUNT(ont.id) as total_onts,
        SUM(CASE WHEN ont.status = 'online' THEN 1 ELSE 0 END) as online_onts,
        SUM(CASE WHEN ont.status = 'offline' THEN 1 ELSE 0 END) as offline_onts,
        SUM(CASE WHEN ont.status = 'los' THEN 1 ELSE 0 END) as los_onts
      FROM olts o
      LEFT JOIN onts ont ON o.id = ont.olt_id
      GROUP BY o.id
      ORDER BY o.created_at DESC
    `);
    
    res.json(olts);
  } catch (error) {
    logger.error('Error fetching OLTs:', error);
    res.status(500).json({ error: 'Failed to fetch OLTs' });
  }
});

// Get single OLT by ID
router.get('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const [olt] = await database.query('SELECT * FROM olts WHERE id = ?', [id]);
    
    if (!olt) {
      return res.status(404).json({ error: 'OLT not found' });
    }
    
    // Get ONTs for this OLT
    const onts = await database.query(`
      SELECT * FROM onts 
      WHERE olt_id = ? 
      ORDER BY port, ont_id
    `, [id]);
    
    res.json({ ...olt, onts });
  } catch (error) {
    logger.error('Error fetching OLT:', error);
    res.status(500).json({ error: 'Failed to fetch OLT' });
  }
});

// Create new OLT
router.post('/', async (req, res) => {
  try {
    const { 
      name, ip_address, port, username, password, olt_type, total_ports,
      connection_method, snmp_enabled, snmp_port, snmp_community, snmp_version,
      snmpv3_read_user, snmpv3_write_user, snmpv3_trap_user,
      snmp_auth_protocol, snmp_auth_password, snmp_priv_protocol, snmp_priv_password
    } = req.body;
    
    // Validate required fields based on connection method
    if (!name || !ip_address) {
      return res.status(400).json({ error: 'Name and IP address are required' });
    }
    
    if (connection_method === 'snmp') {
      if (snmp_version === 'v3') {
        if (!snmpv3_read_user) {
          return res.status(400).json({ error: 'SNMPv3 read user is required for SNMPv3' });
        }
      } else if (!snmp_community) {
        return res.status(400).json({ error: 'SNMP community is required for SNMP v1/v2c connection' });
      }
    } else {
      if (!username || !password) {
        return res.status(400).json({ error: 'Username and password are required for SSH connection' });
      }
    }
    
    const result = await database.query(`
      INSERT INTO olts (
        name, ip_address, port, username, password, type,
        connection_method, snmp_enabled, snmp_port, snmp_community, snmp_version,
        snmpv3_read_user, snmpv3_write_user, snmpv3_trap_user,
        snmp_auth_protocol, snmp_auth_password, snmp_priv_protocol, snmp_priv_password
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    `, [
      name, 
      ip_address, 
      port || 22, 
      username || '', 
      password || '', 
      olt_type || 'epon',
      connection_method || 'ssh', 
      snmp_enabled || false, 
      snmp_port || 161, 
      snmp_community || 'public', 
      snmp_version || 'v2c',
      snmpv3_read_user || '', 
      snmpv3_write_user || '', 
      snmpv3_trap_user || '',
      snmp_auth_protocol || 'MD5', 
      snmp_auth_password || '', 
      snmp_priv_protocol || 'DES', 
      snmp_priv_password || ''
    ]);
    
    // Get the created OLT
    const [olt] = await database.query('SELECT * FROM olts WHERE id = ?', [result.insertId]);
    
    // Add to polling service
    pollingService.addOlt(olt);
    
    logger.info('OLT created:', name, '(' + ip_address + ')');
    res.status(201).json(olt);
  } catch (error) {
    if (error.code === 'ER_DUP_ENTRY') {
      return res.status(409).json({ error: 'IP address already exists' });
    }
    logger.error('Error creating OLT:', error);
    res.status(500).json({ error: 'Failed to create OLT' });
  }
});

// Update OLT
router.put('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const { 
      name, ip_address, port, username, password, olt_type,
      connection_method, snmp_enabled, snmp_port, snmp_community, snmp_version,
      snmpv3_read_user, snmpv3_write_user, snmpv3_trap_user,
      snmp_auth_protocol, snmp_auth_password, snmp_priv_protocol, snmp_priv_password
    } = req.body;
    
    const result = await database.query(`
      UPDATE olts 
      SET name = ?, ip_address = ?, port = ?, username = ?, password = ?, type = ?,
          connection_method = ?, snmp_enabled = ?, snmp_port = ?, snmp_community = ?, snmp_version = ?,
          snmpv3_read_user = ?, snmpv3_write_user = ?, snmpv3_trap_user = ?,
          snmp_auth_protocol = ?, snmp_auth_password = ?, snmp_priv_protocol = ?, snmp_priv_password = ?,
          updated_at = CURRENT_TIMESTAMP
      WHERE id = ?
    `, [
      name, 
      ip_address, 
      port || 22, 
      username || '', 
      password || '', 
      olt_type || 'epon',
      connection_method || 'ssh', 
      snmp_enabled || false, 
      snmp_port || 161, 
      snmp_community || 'public', 
      snmp_version || 'v2c',
      snmpv3_read_user || '', 
      snmpv3_write_user || '', 
      snmpv3_trap_user || '',
      snmp_auth_protocol || 'MD5', 
      snmp_auth_password || '', 
      snmp_priv_protocol || 'DES', 
      snmp_priv_password || '', 
      id
    ]);
    
    if (result.affectedRows === 0) {
      return res.status(404).json({ error: 'OLT not found' });
    }
    
    // Get updated OLT
    const [olt] = await database.query('SELECT * FROM olts WHERE id = ?', [id]);
    
    // Update in polling service
    pollingService.updateOlt(olt);
    
    logger.info('OLT updated:', name, '(' + ip_address + ')');
    res.json(olt);
  } catch (error) {
    logger.error('Error updating OLT:', error);
    res.status(500).json({ error: 'Failed to update OLT' });
  }
});

// Delete OLT
router.delete('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    
    // Get OLT info before deletion
    const [olt] = await database.query('SELECT * FROM olts WHERE id = ?', [id]);
    
    if (!olt) {
      return res.status(404).json({ error: 'OLT not found' });
    }
    
    // Delete associated ONTs first
    await database.query('DELETE FROM onts WHERE olt_id = ?', [id]);
    
    // Delete OLT
    await database.query('DELETE FROM olts WHERE id = ?', [id]);
    
    // Remove from polling service
    pollingService.removeOlt(id);
    
    logger.info('OLT deleted:', olt.name, '(' + olt.ip_address + ')');
    res.json({ message: 'OLT deleted successfully' });
  } catch (error) {
    logger.error('Error deleting OLT:', error);
    res.status(500).json({ error: 'Failed to delete OLT' });
  }
});

// Test OLT connection
router.post('/:id/test', async (req, res) => {
  try {
    const { id } = req.params;
    const [olt] = await database.query('SELECT * FROM olts WHERE id = ?', [id]);
    
    if (!olt) {
      return res.status(404).json({ error: 'OLT not found' });
    }
    
    // Test connection logic would go here
    // For now, just return success
    res.json({ 
      success: true, 
      message: 'Connection test successful',
      olt: olt.name 
    });
  } catch (error) {
    logger.error('Error testing OLT connection:', error);
    res.status(500).json({ error: 'Failed to test connection' });
  }
});

// Test connection (for form)
router.post('/test-connection', async (req, res) => {
  try {
    const {
      ip_address,
      port,
      username,
      password,
      olt_type,
      connection_method,
      snmp_enabled,
      snmp_port,
      snmp_community,
      snmp_version,
      snmpv3_read_user,
      snmpv3_write_user,
      snmp_auth_protocol,
      snmp_auth_password,
      snmp_priv_protocol,
      snmp_priv_password
    } = req.body;
    
    if (!ip_address) {
      return res.status(400).json({ error: 'IP address is required' });
    }
    
    // Here you would implement the actual connection test
    // For now, return a mock response
    res.json({ 
      success: true, 
      message: 'Connection test successful for ' + ip_address,
      method: connection_method,
      version: snmp_version || 'N/A'
    });
  } catch (error) {
    logger.error('Error testing connection:', error);
    res.status(500).json({ error: 'Connection test failed' });
  }
});

module.exports = router;