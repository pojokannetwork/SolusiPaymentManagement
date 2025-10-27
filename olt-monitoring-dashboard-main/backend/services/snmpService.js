const snmp = require('net-snmp');
const logger = require('../utils/logger');

class SNMPService {
  constructor() {
    this.sessions = new Map();
  }

  createSession(olt) {
    const key = `${olt.ip_address}:${olt.snmp_port}`;
    
    if (this.sessions.has(key)) {
      return this.sessions.get(key);
    }

    const options = {
      port: olt.snmp_port || 161,
      retries: 1,
      timeout: 5000,
      transport: 'udp4',
      trapPort: 162,
      version: this.getSnmpVersion(olt.snmp_version || 'v2c'),
      sourceAddress: undefined,
      sourcePort: undefined
    };

    const session = snmp.createSession(olt.ip_address, olt.snmp_community || 'public', options);
    
    session.on('error', (error) => {
      logger.error(`SNMP session error for ${olt.ip_address}:`, error);
      this.sessions.delete(key);
    });

    this.sessions.set(key, session);
    return session;
  }

  getSnmpVersion(version) {
    switch (version.toLowerCase()) {
      case 'v1': return snmp.Version1;
      case 'v2c': return snmp.Version2c;
      case 'v3': return snmp.Version3;
      default: return snmp.Version2c;
    }
  }

  async testConnection(olt) {
    try {
      const session = this.createSession(olt);
      
      return new Promise((resolve) => {
        // Test with system uptime OID (1.3.6.1.2.1.1.3.0)
        const oids = ['1.3.6.1.2.1.1.3.0'];
        
        session.get(oids, (error, varbinds) => {
          if (error) {
            resolve({ 
              success: false, 
              error: error.message,
              method: 'snmp'
            });
          } else {
            const uptime = varbinds[0];
            if (snmp.isVarbindError(uptime)) {
              resolve({ 
                success: false, 
                error: snmp.varbindError(uptime),
                method: 'snmp'
              });
            } else {
              resolve({ 
                success: true, 
                uptime: uptime.value,
                method: 'snmp'
              });
            }
          }
        });
      });
    } catch (error) {
      return { 
        success: false, 
        error: error.message,
        method: 'snmp'
      };
    }
  }

  async getSystemInfo(olt) {
    try {
      const session = this.createSession(olt);
      
      return new Promise((resolve) => {
        const oids = [
          '1.3.6.1.2.1.1.1.0', // sysDescr
          '1.3.6.1.2.1.1.3.0', // sysUpTime
          '1.3.6.1.2.1.1.4.0', // sysContact
          '1.3.6.1.2.1.1.5.0', // sysName
          '1.3.6.1.2.1.1.6.0'  // sysLocation
        ];
        
        session.get(oids, (error, varbinds) => {
          if (error) {
            resolve({ error: error.message });
          } else {
            const info = {};
            varbinds.forEach((vb, index) => {
              if (!snmp.isVarbindError(vb)) {
                switch (index) {
                  case 0: info.description = vb.value.toString(); break;
                  case 1: info.uptime = vb.value; break;
                  case 2: info.contact = vb.value.toString(); break;
                  case 3: info.name = vb.value.toString(); break;
                  case 4: info.location = vb.value.toString(); break;
                }
              }
            });
            resolve(info);
          }
        });
      });
    } catch (error) {
      return { error: error.message };
    }
  }

  async getInterfaceInfo(olt) {
    try {
      const session = this.createSession(olt);
      
      return new Promise((resolve) => {
        // Walk interface table
        const baseOid = '1.3.6.1.2.1.2.2.1';
        
        session.subtree(baseOid, (varbinds) => {
          const interfaces = {};
          
          varbinds.forEach((vb) => {
            if (snmp.isVarbindError(vb)) return;
            
            const oid = vb.oid;
            const parts = oid.split('.');
            const column = parts[parts.length - 2];
            const ifIndex = parts[parts.length - 1];
            
            if (!interfaces[ifIndex]) {
              interfaces[ifIndex] = {};
            }
            
            switch (column) {
              case '1': interfaces[ifIndex].index = vb.value; break;
              case '2': interfaces[ifIndex].name = vb.value.toString(); break;
              case '3': interfaces[ifIndex].type = vb.value; break;
              case '5': interfaces[ifIndex].speed = vb.value; break;
              case '7': interfaces[ifIndex].adminStatus = vb.value; break;
              case '8': interfaces[ifIndex].operStatus = vb.value; break;
              case '10': interfaces[ifIndex].inOctets = vb.value; break;
              case '16': interfaces[ifIndex].outOctets = vb.value; break;
            }
          });
          
          resolve(Object.values(interfaces));
        }, (error) => {
          resolve({ error: error.message });
        });
      });
    } catch (error) {
      return { error: error.message };
    }
  }

  // OID mappings for common OLT vendors
  getVendorOIDs(oltType) {
    const oids = {
      hioso: {
        ontList: '1.3.6.1.4.1.50058.102.1.1.1.1.1', // Example OID
        ontStatus: '1.3.6.1.4.1.50058.102.1.1.1.1.2',
        ontRxPower: '1.3.6.1.4.1.50058.102.1.1.1.1.3',
        ontTxPower: '1.3.6.1.4.1.50058.102.1.1.1.1.4'
      },
      zte: {
        ontList: '1.3.6.1.4.1.3902.1012.3.28.1.1.1',
        ontStatus: '1.3.6.1.4.1.3902.1012.3.28.1.1.2',
        ontRxPower: '1.3.6.1.4.1.3902.1012.3.28.1.1.6',
        ontTxPower: '1.3.6.1.4.1.3902.1012.3.28.1.1.7'
      },
      huawei: {
        ontList: '1.3.6.1.4.1.2011.6.128.1.1.2.43.1.2',
        ontStatus: '1.3.6.1.4.1.2011.6.128.1.1.2.46.1.15',
        ontRxPower: '1.3.6.1.4.1.2011.6.128.1.1.2.51.1.4',
        ontTxPower: '1.3.6.1.4.1.2011.6.128.1.1.2.51.1.6'
      },
      other: {
        ontList: '1.3.6.1.2.1.2.2.1.1',
        ontStatus: '1.3.6.1.2.1.2.2.1.8',
        ontRxPower: '1.3.6.1.2.1.2.2.1.10',
        ontTxPower: '1.3.6.1.2.1.2.2.1.16'
      }
    };
    
    return oids[oltType] || oids.other;
  }

  async getONTList(olt) {
    try {
      const session = this.createSession(olt);
      const vendorOids = this.getVendorOIDs(olt.type);
      
      return new Promise((resolve) => {
        session.subtree(vendorOids.ontList, (varbinds) => {
          const onts = [];
          
          varbinds.forEach((vb) => {
            if (snmp.isVarbindError(vb)) return;
            
            // Parse OID to extract port and ONT ID
            const oidParts = vb.oid.split('.');
            const port = oidParts[oidParts.length - 2];
            const ontId = oidParts[oidParts.length - 1];
            
            onts.push({
              port: `epon0/${port}`,
              ont_id: parseInt(ontId),
              mac_address: vb.value.toString(),
              status: 'online' // Will be updated by status check
            });
          });
          
          resolve(onts);
        }, (error) => {
          resolve({ error: error.message });
        });
      });
    } catch (error) {
      return { error: error.message };
    }
  }

  async getONTPower(olt, port, ontId) {
    try {
      const session = this.createSession(olt);
      const vendorOids = this.getVendorOIDs(olt.type);
      
      // Build specific OIDs for this ONT
      const portNum = port.split('/')[1];
      const rxPowerOid = `${vendorOids.ontRxPower}.${portNum}.${ontId}`;
      const txPowerOid = `${vendorOids.ontTxPower}.${portNum}.${ontId}`;
      
      return new Promise((resolve) => {
        session.get([rxPowerOid, txPowerOid], (error, varbinds) => {
          if (error) {
            resolve({ error: error.message });
          } else {
            const result = {};
            
            varbinds.forEach((vb, index) => {
              if (!snmp.isVarbindError(vb)) {
                if (index === 0) result.rx_power = vb.value / 100; // Convert to dBm
                if (index === 1) result.tx_power = vb.value / 100;
              }
            });
            
            resolve(result);
          }
        });
      });
    } catch (error) {
      return { error: error.message };
    }
  }

  closeSession(olt) {
    const key = `${olt.ip_address}:${olt.snmp_port}`;
    const session = this.sessions.get(key);
    
    if (session) {
      session.close();
      this.sessions.delete(key);
    }
  }

  closeAllSessions() {
    this.sessions.forEach((session) => {
      session.close();
    });
    this.sessions.clear();
  }
}

module.exports = new SNMPService();