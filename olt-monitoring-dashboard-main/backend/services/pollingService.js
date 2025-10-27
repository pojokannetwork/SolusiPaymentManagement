const cron = require('node-cron');
const { Client } = require('ssh2');
const database = require('../database/connection');
const telegramService = require('./telegramService');
const snmpService = require('./snmpService');
const logger = require('../utils/logger');

class PollingService {
  constructor() {
    this.isRunning = false;
    this.oltConnections = new Map();
    this.pollingJob = null;
    this.pollingInterval = 30000; // 30 seconds default
  }

  async start() {
    try {
      // Get polling interval from settings
      const intervalSettings = await database.query(
        'SELECT value FROM settings WHERE key = ?',
        ['interval']
      );

      const intervalSetting = Array.isArray(intervalSettings) ? intervalSettings[0] : intervalSettings;

      if (intervalSetting?.value) {
        this.pollingInterval = parseInt(intervalSetting.value) * 1000; // Convert to milliseconds
      }

      // Load all OLTs
      const olts = await database.query('SELECT * FROM olts WHERE status != ?', ['error']);
      
      for (const olt of olts) {
        this.addOlt(olt);
      }

      // Start polling cron job (every 30 seconds)
      this.pollingJob = cron.schedule('*/30 * * * * *', async () => {
        if (!this.isRunning) {
          this.isRunning = true;
          await this.pollAllOlts();
          this.isRunning = false;
        }
      });

      logger.info(`Polling service started with ${olts.length} OLTs`);
    } catch (error) {
      logger.error('Error starting polling service:', error);
    }
  }

  stop() {
    if (this.pollingJob) {
      this.pollingJob.destroy();
    }
    
    // Close all SSH connections
    for (const [oltId, connection] of this.oltConnections) {
      if (connection.conn) {
        connection.conn.end();
      }
    }
    
    this.oltConnections.clear();
    logger.info('Polling service stopped');
  }

  addOlt(olt) {
    this.oltConnections.set(olt.id, {
      olt: olt,
      conn: null,
      lastPoll: null,
      retryCount: 0
    });
    
    logger.info(`Added OLT to polling: ${olt.name} (${olt.ip_address})`);
  }

  updateOlt(olt) {
    if (this.oltConnections.has(olt.id)) {
      const connection = this.oltConnections.get(olt.id);
      connection.olt = olt;
      logger.info(`Updated OLT in polling: ${olt.name}`);
    } else {
      this.addOlt(olt);
    }
  }

  removeOlt(oltId) {
    const connection = this.oltConnections.get(oltId);
    if (connection && connection.conn) {
      connection.conn.end();
    }
    
    this.oltConnections.delete(oltId);
    logger.info(`Removed OLT from polling: ${oltId}`);
  }

  async pollAllOlts() {
    const promises = [];
    
    for (const [oltId, connection] of this.oltConnections) {
      promises.push(this.pollOlt(connection));
    }
    
    await Promise.allSettled(promises);
  }

  async pollOlt(connectionData) {
    const { olt } = connectionData;
    
    try {
      // Test connection first
      const isConnected = await this.testOltConnection(olt);
      
      if (!isConnected.success) {
        await this.handleOltOffline(olt);
        return;
      }

      // Update OLT status to online
      await database.query('UPDATE olts SET status = ?, last_poll = CURRENT_TIMESTAMP WHERE id = ?', ['online', olt.id]);

      // Poll ONT data based on connection method
      let ontData = [];
      const connectionMethod = olt.connection_method || 'ssh';
      
      if (connectionMethod === 'snmp' && olt.snmp_enabled) {
        ontData = await this.pollOntDataSNMP(olt);
      } else {
        ontData = await this.pollOntDataSSH(olt);
      }
      
      if (ontData.length > 0) {
        await this.updateOntData(olt.id, ontData);
      }

      connectionData.lastPoll = new Date();
      connectionData.retryCount = 0;
      
    } catch (error) {
      connectionData.retryCount++;
      logger.error(`Error polling OLT ${olt.name}:`, error);
      
      if (connectionData.retryCount >= 3) {
        await this.handleOltOffline(olt);
      }
    }
  }

  async testOltConnection(olt) {
    const connectionMethod = olt.connection_method || 'ssh';
    
    if (connectionMethod === 'snmp' && olt.snmp_enabled) {
      // Test SNMP connection
      return await snmpService.testConnection(olt);
    } else {
      // Test SSH connection
      return new Promise((resolve) => {
        const conn = new Client();
        const timeout = setTimeout(() => {
          conn.end();
          resolve({ success: false, error: 'SSH connection timeout', method: 'ssh' });
        }, parseInt(process.env.SSH_TIMEOUT) || 10000);

        conn.on('ready', () => {
          clearTimeout(timeout);
          conn.end();
          resolve({ success: true, method: 'ssh' });
        });

        conn.on('error', (err) => {
          clearTimeout(timeout);
          resolve({ success: false, error: err.message, method: 'ssh' });
        });

        conn.connect({
          host: olt.ip_address,
          port: olt.port || 22,
          username: olt.username,
          password: olt.password,
          readyTimeout: 10000
        });
      });
    }
  }

  async pollOntDataSSH(olt) {
    // SSH implementation for getting ONT data
    // In a real implementation, you would SSH to the OLT and run commands
    // to get ONT information based on OLT type (Hioso, ZTE, Huawei, etc.)
    
    try {
      const mockOntData = await this.getMockOntData(olt);
      return mockOntData;
    } catch (error) {
      logger.error(`Error polling ONT data via SSH for ${olt.name}:`, error);
      return [];
    }
  }

  async pollOntDataSNMP(olt) {
    // SNMP implementation for getting ONT data
    try {
      logger.info(`Polling ONT data via SNMP for OLT: ${olt.name}`);
      
      // Get ONT list from SNMP
      const ontList = await snmpService.getONTList(olt);
      
      if (ontList.error) {
        logger.error(`SNMP ONT list error for ${olt.name}:`, ontList.error);
        return [];
      }
      
      const ontData = [];
      
      // Get detailed info for each ONT
      for (const ont of ontList) {
        try {
          // Get power information
          const powerInfo = await snmpService.getONTPower(olt, ont.port, ont.ont_id);
          
          if (!powerInfo.error) {
            ontData.push({
              port: ont.port,
              ont_id: ont.ont_id,
              mac_address: ont.mac_address,
              status: ont.status,
              rx_power: powerInfo.rx_power || null,
              tx_power: powerInfo.tx_power || null,
              distance: null, // Distance might need separate SNMP call
              customer_name: `Customer-${ont.port}-${ont.ont_id}` // Default name
            });
          }
        } catch (error) {
          logger.error(`Error getting SNMP data for ONT ${ont.port}/${ont.ont_id}:`, error);
        }
      }
      
      return ontData;
    } catch (error) {
      logger.error(`Error polling ONT data via SNMP for ${olt.name}:`, error);
      return [];
    }
  }

  async getMockOntData(olt) {
    // Generate mock data for demonstration
    // In real implementation, this would parse actual OLT responses
    
    const ports = ['epon0/1', 'epon0/2', 'epon0/3', 'epon0/4'];
    const ontData = [];
    
    for (const port of ports) {
      const ontCount = Math.floor(Math.random() * 8) + 1; // 1-8 ONTs per port
      
      for (let ontId = 1; ontId <= ontCount; ontId++) {
        const isOnline = Math.random() > 0.1; // 90% chance online
        const hasLos = !isOnline && Math.random() > 0.7; // 30% of offline ONTs have LOS
        
        ontData.push({
          port: port,
          ont_id: ontId,
          mac_address: this.generateMacAddress(),
          status: hasLos ? 'los' : (isOnline ? 'online' : 'offline'),
          rx_power: isOnline ? this.generateRxPower() : null,
          tx_power: isOnline ? this.generateTxPower() : null,
          distance: isOnline ? this.generateDistance() : null,
          last_seen: isOnline ? new Date() : null
        });
      }
    }
    
    return ontData;
  }

  generateMacAddress() {
    const chars = '0123456789ABCDEF';
    let mac = '';
    for (let i = 0; i < 12; i++) {
      if (i > 0 && i % 2 === 0) mac += ':';
      mac += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return mac;
  }

  generateRxPower() {
    // Generate realistic RX power values
    const base = -15; // Base power
    const variance = 10; // ±10 dBm variance
    return parseFloat((base + (Math.random() * variance * 2 - variance)).toFixed(2));
  }

  generateTxPower() {
    // Generate realistic TX power values
    const base = 2; // Base power
    const variance = 3; // ±3 dBm variance
    return parseFloat((base + (Math.random() * variance * 2 - variance)).toFixed(2));
  }

  generateDistance() {
    // Generate realistic distances (0-30 km)
    return parseFloat((Math.random() * 30).toFixed(2));
  }

  async updateOntData(oltId, ontDataArray) {
    try {
      for (const ontData of ontDataArray) {
        // Check if ONT exists
        const [existingOnt] = await database.query(`
          SELECT * FROM onts 
          WHERE olt_id = ? AND port = ? AND ont_id = ?
        `, [oltId, ontData.port, ontData.ont_id]);

        if (existingOnt) {
          // Check for status changes
          if (existingOnt.status !== ontData.status) {
            await this.createStatusEvent(oltId, existingOnt.id, existingOnt.status, ontData.status);
          }

          // Check for power warnings
          if (ontData.rx_power !== null) {
            await this.checkPowerThresholds(oltId, existingOnt.id, ontData.rx_power, 'rx_power');
          }

          // Check for distance warnings
          if (ontData.distance !== null) {
            await this.checkDistanceThresholds(oltId, existingOnt.id, ontData.distance);
          }

          // Update ONT
          await database.query(`
            UPDATE onts 
            SET status = ?, rx_power = ?, tx_power = ?, distance = ?, 
                mac_address = ?, last_seen = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
          `, [
            ontData.status, ontData.rx_power, ontData.tx_power, 
            ontData.distance, ontData.mac_address, ontData.last_seen, 
            existingOnt.id
          ]);
        } else {
          // Create new ONT
          const result = await database.query(`
            INSERT INTO onts 
            (olt_id, port, ont_id, mac_address, status, rx_power, tx_power, distance, last_seen)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
          `, [
            oltId, ontData.port, ontData.ont_id, ontData.mac_address,
            ontData.status, ontData.rx_power, ontData.tx_power, 
            ontData.distance, ontData.last_seen
          ]);

          // Create discovery event
          await this.createEvent(oltId, result.insertId, 'ont_online', 
            `New ONT discovered: ${ontData.port}/${ontData.ont_id}`, 'info');
        }
      }
    } catch (error) {
      logger.error('Error updating ONT data:', error);
    }
  }

  async createStatusEvent(oltId, ontId, oldStatus, newStatus) {
    let eventType, message, severity;
    
    if (newStatus === 'online' && oldStatus !== 'online') {
      eventType = 'ont_online';
      message = 'ONT came online';
      severity = 'info';
    } else if (newStatus === 'offline' && oldStatus === 'online') {
      eventType = 'ont_offline';
      message = 'ONT went offline';
      severity = 'warning';
    } else if (newStatus === 'los') {
      eventType = 'ont_los';
      message = 'ONT has Loss of Signal (LOS)';
      severity = 'error';
    } else {
      return; // No significant status change
    }

    await this.createEvent(oltId, ontId, eventType, message, severity);
  }

  async checkPowerThresholds(oltId, ontId, power, type) {
    const [threshold] = await database.query(
      'SELECT * FROM thresholds WHERE threshold_type = ?',
      ['rx_power']
    );

    if (!threshold) return;

    let severity = null;
    let message = null;

    if (power < threshold.danger_max || power > threshold.safe_max) {
      severity = 'critical';
      message = `RX Power critical: ${power} dBm`;
    } else if (power < threshold.warning_max || power > threshold.warning_min) {
      severity = 'warning';
      message = `RX Power warning: ${power} dBm`;
    }

    if (severity) {
      await this.createEvent(oltId, ontId, 'power_warning', message, severity);
    }
  }

  async checkDistanceThresholds(oltId, ontId, distance) {
    const [threshold] = await database.query(
      'SELECT * FROM thresholds WHERE threshold_type = ?',
      ['distance']
    );

    if (!threshold) return;

    let severity = null;
    let message = null;

    if (distance > threshold.danger_min) {
      severity = 'critical';
      message = `Distance critical: ${distance} km`;
    } else if (distance > threshold.warning_min) {
      severity = 'warning';
      message = `Distance warning: ${distance} km`;
    }

    if (severity) {
      await this.createEvent(oltId, ontId, 'distance_warning', message, severity);
    }
  }

  async createEvent(oltId, ontId, eventType, message, severity) {
    try {
      await database.query(`
        INSERT INTO events (olt_id, ont_id, event_type, message, severity)
        VALUES (?, ?, ?, ?, ?)
      `, [oltId, ontId, eventType, message, severity]);

      // Send Telegram notification for important events
      if (severity === 'critical' || severity === 'error') {
        await this.sendTelegramNotification(oltId, ontId, eventType, message, severity);
      }
    } catch (error) {
      logger.error('Error creating event:', error);
    }
  }

  async sendTelegramNotification(oltId, ontId, eventType, message, severity) {
    try {
      // Check if notifications are enabled
      const [notificationSetting] = await database.query(
        'SELECT setting_value FROM settings WHERE setting_key = ?',
        ['notification_enabled']
      );

      if (notificationSetting?.setting_value !== 'true') {
        return;
      }

      // Get OLT and ONT details
      const [ontDetails] = await database.query(`
        SELECT 
          ont.customer_name, ont.port, ont.ont_id,
          o.name as olt_name, o.location
        FROM onts ont
        JOIN olts o ON ont.olt_id = o.id
        WHERE ont.id = ?
      `, [ontId]);

      if (ontDetails) {
        const severityIcon = severity === 'critical' ? '🚨' : '⚠️';
        const telegramMessage = `
${severityIcon} **${severity.toUpperCase()} Alert**

📍 **Location:** ${ontDetails.olt_name} ${ontDetails.location ? `(${ontDetails.location})` : ''}
👤 **Customer:** ${ontDetails.customer_name || 'Unknown'}
🔌 **Port:** ${ontDetails.port}/${ontDetails.ont_id}
📝 **Message:** ${message}

🕐 **Time:** ${new Date().toLocaleString('id-ID')}
        `.trim();

        await telegramService.sendNotification(telegramMessage);
      }
    } catch (error) {
      logger.error('Error sending Telegram notification:', error);
    }
  }

  async handleOltOffline(olt) {
    try {
      // Update OLT status
      await database.query('UPDATE olts SET status = ? WHERE id = ?', ['offline', olt.id]);

      // Create offline event
      await this.createEvent(olt.id, null, 'olt_offline', `OLT ${olt.name} is offline`, 'error');

      logger.warn(`OLT offline: ${olt.name} (${olt.ip_address})`);
    } catch (error) {
      logger.error('Error handling OLT offline:', error);
    }
  }
}

module.exports = new PollingService();