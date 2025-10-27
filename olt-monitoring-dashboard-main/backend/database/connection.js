const mysql = require('mysql2/promise');
const sqlite3 = require('sqlite3').verbose();
const path = require('path');
const logger = require('../utils/logger');

class Database {
  constructor() {
    this.pool = null;
    this.db = null;
    this.dbType = process.env.DB_TYPE || 'mysql';
  }

  async connect() {
    try {
      if (this.dbType === 'sqlite') {
        return this.connectSQLite();
      } else {
        return this.connectMySQL();
      }
    } catch (error) {
      logger.error('Database connection failed:', error);
      throw error;
    }
  }

  async connectSQLite() {
    return new Promise((resolve, reject) => {
      const dbPath = path.join(__dirname, '..', process.env.DB_PATH || 'database/olt_monitoring.db');
      
      this.db = new sqlite3.Database(dbPath, (err) => {
        if (err) {
          logger.error('SQLite connection error:', err.message);
          reject(err);
          return;
        }
        logger.info('✅ Connected to SQLite database');
        resolve();
      });
    });
  }

  async connectMySQL() {
    try {
      this.pool = mysql.createPool({
        host: process.env.DB_HOST || 'localhost',
        port: process.env.DB_PORT || 3306,
        user: process.env.DB_USER || 'root',
        password: process.env.DB_PASSWORD || '',
        database: process.env.DB_NAME || 'olt_monitoring',
        waitForConnections: true,
        connectionLimit: 10,
        queueLimit: 0,
        timezone: '+00:00'
      });

      logger.info('Database pool created');
    } catch (error) {
      logger.error('Database connection error:', error);
      throw error;
    }
  }

  async testConnection() {
    try {
      if (this.dbType === 'sqlite') {
        if (!this.db) await this.connect();
        return new Promise((resolve, reject) => {
          this.db.get('SELECT 1', (err) => {
            if (err) reject(err);
            else resolve();
          });
        });
      } else {
        if (!this.pool) await this.connect();
        const connection = await this.pool.getConnection();
        await connection.ping();
        connection.release();
      }
      logger.info('Database connection test successful');
    } catch (error) {
      logger.error('Database connection test failed:', error);
      throw error;
    }
  }

  async query(sql, params = []) {
    try {
      if (this.dbType === 'sqlite') {
        if (!this.db) await this.connect();
        return new Promise((resolve, reject) => {
          if (sql.trim().toUpperCase().startsWith('SELECT')) {
            this.db.all(sql, params, (err, rows) => {
              if (err) reject(err);
              else resolve(rows);
            });
          } else {
            this.db.run(sql, params, function(err) {
              if (err) reject(err);
              else resolve({ affectedRows: this.changes, insertId: this.lastID });
            });
          }
        });
      } else {
        if (!this.pool) await this.connect();
        const [rows] = await this.pool.execute(sql, params);
        return rows;
      }
    } catch (error) {
      logger.error('Database query error:', { sql, params, error: error.message });
      throw error;
    }
  }

  async closeConnection() {
    try {
      if (this.dbType === 'sqlite' && this.db) {
        await new Promise(resolve => this.db.close(resolve));
      } else if (this.pool) {
        await this.pool.end();
      }
      logger.info('Database connection closed');
    } catch (error) {
      logger.error('Error closing database connection:', error);
    }
  }
}

module.exports = new Database();