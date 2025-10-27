const TelegramBot = require('node-telegram-bot-api');
const database = require('../database/connection');
const logger = require('../utils/logger');

class TelegramService {
  constructor() {
    this.bot = null;
    this.token = null;
    this.defaultChatId = null;
  }

  async initialize() {
    try {
      // Get bot token from settings
      const tokenSettings = await database.query(
        'SELECT value FROM settings WHERE key = ?',
        ['bot_token']
      );

      const chatIdSettings = await database.query(
        'SELECT value FROM settings WHERE key = ?',
        ['chat_id']
      );

      const tokenSetting = Array.isArray(tokenSettings) ? tokenSettings[0] : tokenSettings;
      const chatIdSetting = Array.isArray(chatIdSettings) ? chatIdSettings[0] : chatIdSettings;

      if (tokenSetting?.value) {
        await this.createBot(tokenSetting.value);
        this.defaultChatId = chatIdSetting?.value || null;
        logger.info('Telegram service initialized successfully');
      } else {
        logger.info('No Telegram bot token found, bot service disabled');
      }
    } catch (error) {
      logger.error('Error initializing Telegram service:', error);
    }
  }

  async createBot(token) {
    try {
      if (this.bot) {
        await this.bot.stopPolling();
      }

      this.token = token;
      this.bot = new TelegramBot(token, { polling: true });

      // Set up command handlers
      this.setupCommands();

      logger.info('Telegram bot created successfully');
    } catch (error) {
      logger.error('Error creating Telegram bot:', error);
      throw error;
    }
  }

  async isUserAllowed(chatId) {
    try {
      // Check if whitelist is enabled
      const whitelistSettings = await database.query(
        'SELECT value FROM settings WHERE key = ?',
        ['telegram_whitelist_enabled']
      );
      
      const whitelistSetting = Array.isArray(whitelistSettings) ? whitelistSettings[0] : whitelistSettings;
      const whitelistEnabled = whitelistSetting?.value === 'true';
      
      if (!whitelistEnabled) {
        return true; // Whitelist disabled, allow all users
      }
      
      // Check if user is in whitelist and active
      const users = await database.query(
        'SELECT * FROM telegram_users WHERE chat_id = ? AND is_active = TRUE',
        [chatId.toString()]
      );
      
      const user = Array.isArray(users) ? users[0] : users;
      return !!user;
    } catch (error) {
      logger.error('Error checking user whitelist:', error);
      return false; // Deny access on error
    }
  }

  // Save chat interaction to database
  async saveChatHistory(msg, command = null, botResponse = null, status = 'success') {
    try {
      const chatId = msg.chat?.id || msg.from?.id;
      const username = msg.from?.username;
      const firstName = msg.from?.first_name;
      const lastName = msg.from?.last_name;
      const messageType = command ? 'command' : 'text';
      const message = msg.text || msg.caption || '[Media]';
      const responseTime = botResponse ? new Date() : null;

      await database.query(`
        INSERT INTO telegram_chat_history (
          chat_id, username, first_name, last_name, message_type, 
          message, command, bot_response, response_time, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
      `, [
        chatId?.toString(),
        username,
        firstName,
        lastName,
        messageType,
        message,
        command,
        botResponse,
        responseTime,
        status
      ]);
    } catch (error) {
      logger.error('Error saving chat history:', error);
    }
  }

  setupCommands() {
    // Middleware to check user authorization and save chat history
    this.bot.use(async (msg, metadata) => {
      const chatId = msg.chat?.id || msg.from?.id;
      if (!chatId) return;
      
      const isAllowed = await this.isUserAllowed(chatId);
      if (!isAllowed) {
        const errorResponse = '🚫 Maaf, Anda tidak memiliki akses ke bot ini. Silakan hubungi administrator untuk didaftarkan.';
        await this.sendMessage(chatId, errorResponse);
        await this.saveChatHistory(msg, null, errorResponse, 'error');
        return;
      }
    });

    // Start command
    this.bot.onText(/\/start/, async (msg) => {
      const chatId = msg.chat.id;
      
      // Check if user is allowed
      const isAllowed = await this.isUserAllowed(chatId);
      if (!isAllowed) {
        const errorResponse = '🚫 Maaf, Anda tidak memiliki akses ke bot ini. Silakan hubungi administrator untuk didaftarkan.';
        await this.sendMessage(chatId, errorResponse);
        await this.saveChatHistory(msg, 'start', errorResponse, 'error');
        return;
      }
      
      const welcomeMessage = `
🤖 **OLT Monitoring Bot**

Selamat datang! Bot ini dapat membantu Anda monitoring OLT dan ONT.

**Commands tersedia:**
/status - Status ringkasan semua OLT
/power <olt_name> <port> <ont_id> - Cek power ONT
/rename <olt_name> <port> <ont_id> <nama_baru> - Rename customer
/help - Bantuan penggunaan

Kirim /status untuk melihat status sistem.
      `;
      
      await this.sendMessage(chatId, welcomeMessage);
      await this.saveChatHistory(msg, 'start', welcomeMessage);
      
      // Save chat ID if it's the first time
      if (!this.defaultChatId) {
        await database.query(
          'UPDATE settings SET setting_value = ? WHERE setting_key = ?',
          [chatId.toString(), 'telegram_chat_id']
        );
        this.defaultChatId = chatId.toString();
      }
    });

    // Status command
    this.bot.onText(/\/status/, async (msg) => {
      const chatId = msg.chat.id;
      
      // Check if user is allowed
      const isAllowed = await this.isUserAllowed(chatId);
      if (!isAllowed) {
        const errorResponse = '🚫 Maaf, Anda tidak memiliki akses ke bot ini. Silakan hubungi administrator untuk didaftarkan.';
        await this.sendMessage(chatId, errorResponse);
        await this.saveChatHistory(msg, 'status', errorResponse, 'error');
        return;
      }
      
      try {
        const summary = await this.getDashboardSummary();
        const message = this.formatStatusMessage(summary);
        await this.sendMessage(msg.chat.id, message);
        await this.saveChatHistory(msg, 'status', message);
      } catch (error) {
        logger.error('Error handling status command:', error);
        const errorMessage = '❌ Error getting status information';
        await this.sendMessage(msg.chat.id, errorMessage);
        await this.saveChatHistory(msg, 'status', errorMessage, 'error');
      }
    });

    // Power command: /power oltA epon0/1 1
    this.bot.onText(/\/power (.+) (.+) (.+)/, async (msg, match) => {
      const chatId = msg.chat.id;
      
      // Check if user is allowed
      const isAllowed = await this.isUserAllowed(chatId);
      if (!isAllowed) {
        await this.sendMessage(chatId, '🚫 Maaf, Anda tidak memiliki akses ke bot ini. Silakan hubungi administrator untuk didaftarkan.');
        return;
      }
      
      try {
        const [, oltName, port, ontId] = match;
        const ont = await this.getOntByDetails(oltName, port, parseInt(ontId));
        
        if (!ont) {
          await this.sendMessage(msg.chat.id, '❌ ONT tidak ditemukan');
          return;
        }

        const message = this.formatPowerMessage(ont);
        await this.sendMessage(msg.chat.id, message);
      } catch (error) {
        logger.error('Error handling power command:', error);
        await this.sendMessage(msg.chat.id, '❌ Error getting power information');
      }
    });

    // Rename command: /rename oltA epon0/1 1 customer-name
    this.bot.onText(/\/rename (.+) (.+) (.+) (.+)/, async (msg, match) => {
      const chatId = msg.chat.id;
      
      // Check if user is allowed
      const isAllowed = await this.isUserAllowed(chatId);
      if (!isAllowed) {
        await this.sendMessage(chatId, '🚫 Maaf, Anda tidak memiliki akses ke bot ini. Silakan hubungi administrator untuk didaftarkan.');
        return;
      }
      
      try {
        const [, oltName, port, ontId, newName] = match;
        const ont = await this.getOntByDetails(oltName, port, parseInt(ontId));
        
        if (!ont) {
          await this.sendMessage(msg.chat.id, '❌ ONT tidak ditemukan');
          return;
        }

        await database.query(
          'UPDATE onts SET customer_name = ? WHERE id = ?',
          [newName, ont.id]
        );

        await this.sendMessage(
          msg.chat.id, 
          `✅ Customer name updated: ${ont.port}/${ont.ont_id} -> ${newName}`
        );
      } catch (error) {
        logger.error('Error handling rename command:', error);
        await this.sendMessage(msg.chat.id, '❌ Error updating customer name');
      }
    });

    // Help command
    this.bot.onText(/\/help/, async (msg) => {
      const chatId = msg.chat.id;
      
      // Check if user is allowed
      const isAllowed = await this.isUserAllowed(chatId);
      if (!isAllowed) {
        const errorResponse = '🚫 Maaf, Anda tidak memiliki akses ke bot ini. Silakan hubungi administrator untuk didaftarkan.';
        await this.sendMessage(chatId, errorResponse);
        await this.saveChatHistory(msg, 'help', errorResponse, 'error');
        return;
      }
      
      const helpMessage = `
📖 **Help - OLT Monitoring Bot**

**Available Commands:**

🔹 /status
   Menampilkan ringkasan status semua OLT dan ONT

🔹 /power <olt_name> <port> <ont_id>
   Contoh: /power oltA epon0/1 1
   Menampilkan informasi power RX/TX dan jarak ONT

🔹 /rename <olt_name> <port> <ont_id> <nama_baru>
   Contoh: /rename oltA epon0/1 1 pelanggan-andi
   Mengubah nama customer ONT

**Format Parameter:**
- olt_name: Nama OLT (sesuai yang dikonfigurasi)
- port: Format port, contoh: epon0/1, epon0/2
- ont_id: ID ONT pada port (1, 2, 3, dst)

**Status Indikator:**
🟢 Safe | 🟡 Warning | 🔴 Danger | ⚫ Offline
      `;
      
      await this.sendMessage(msg.chat.id, helpMessage);
      await this.saveChatHistory(msg, 'help', helpMessage);
    });

    // Handle unknown commands and text messages
    this.bot.on('message', async (msg) => {
      const chatId = msg.chat.id;
      const isAllowed = await this.isUserAllowed(chatId);
      
      if (!isAllowed) {
        const errorResponse = '🚫 Maaf, Anda tidak memiliki akses ke bot ini. Silakan hubungi administrator untuk didaftarkan.';
        await this.sendMessage(chatId, errorResponse);
        await this.saveChatHistory(msg, null, errorResponse, 'error');
        return;
      }
      
      if (msg.text && msg.text.startsWith('/') && !msg.text.match(/\/(start|status|power|rename|help)/)) {
        const response = '❓ Command tidak dikenal. Kirim /help untuk melihat daftar command.';
        await this.sendMessage(msg.chat.id, response);
        await this.saveChatHistory(msg, 'unknown', response, 'error');
      } else if (msg.text && !msg.text.startsWith('/')) {
        // Handle regular text messages
        const response = '👋 Halo! Saya adalah bot monitoring OLT. Ketik /help untuk melihat command yang tersedia.';
        await this.sendMessage(msg.chat.id, response);
        await this.saveChatHistory(msg, null, response);
      }
    });

    // Error handling
    this.bot.on('error', (error) => {
      logger.error('Telegram bot error:', error);
    });
  }

  async getDashboardSummary() {
    const [oltSummary] = await database.query(`
      SELECT 
        COUNT(*) as total_olts,
        SUM(CASE WHEN status = 'online' THEN 1 ELSE 0 END) as online_olts
      FROM olts
    `);

    const [ontSummary] = await database.query(`
      SELECT 
        COUNT(*) as total_onts,
        SUM(CASE WHEN status = 'online' THEN 1 ELSE 0 END) as online_onts,
        SUM(CASE WHEN status = 'offline' THEN 1 ELSE 0 END) as offline_onts,
        SUM(CASE WHEN status = 'los' THEN 1 ELSE 0 END) as los_onts
      FROM onts
    `);

    return {
      olts: oltSummary || { total_olts: 0, online_olts: 0 },
      onts: ontSummary || { total_onts: 0, online_onts: 0, offline_onts: 0, los_onts: 0 }
    };
  }

  async getOntByDetails(oltName, port, ontId) {
    const [ont] = await database.query(`
      SELECT ont.*, o.name as olt_name
      FROM onts ont
      JOIN olts o ON ont.olt_id = o.id
      WHERE o.name = ? AND ont.port = ? AND ont.ont_id = ?
    `, [oltName, port, ontId]);

    return ont || null;
  }

  formatStatusMessage(summary) {
    const { olts, onts } = summary;
    
    return `
📊 **Status Monitoring OLT**

🏢 **OLT Status:**
• Total: ${olts.total_olts}
• Online: ${olts.online_olts} 🟢
• Offline: ${olts.total_olts - olts.online_olts} 🔴

👥 **ONT Status:**
• Total: ${onts.total_onts}
• Online: ${onts.online_onts} 🟢
• Offline: ${onts.offline_onts} ⚫
• LOS: ${onts.los_onts} 🟡

⏰ Update: ${new Date().toLocaleString('id-ID')}
    `.trim();
  }

  formatPowerMessage(ont) {
    const rxStatus = this.getPowerStatus(ont.rx_power, 'rx');
    const distanceStatus = this.getDistanceStatus(ont.distance);
    
    return `
⚡ **Power Info - ${ont.customer_name || 'Unknown'}**

📍 **Location:** ${ont.olt_name} → ${ont.port}/${ont.ont_id}
📊 **Status:** ${ont.status.toUpperCase()}

🔽 **RX Power:** ${ont.rx_power || 'N/A'} dBm ${rxStatus}
🔼 **TX Power:** ${ont.tx_power || 'N/A'} dBm
📏 **Distance:** ${ont.distance || 'N/A'} km ${distanceStatus}

🕐 **Last Update:** ${ont.updated_at ? new Date(ont.updated_at).toLocaleString('id-ID') : 'N/A'}
    `.trim();
  }

  getPowerStatus(power, type) {
    if (power === null || power === undefined) return '❓';
    
    if (type === 'rx') {
      if (power >= -8 && power <= -25) return '🟢';
      if (power > -27 && power < -25) return '🟡';
      return '🔴';
    }
    
    return '🟢'; // Default for TX
  }

  getDistanceStatus(distance) {
    if (distance === null || distance === undefined) return '❓';
    
    if (distance <= 20) return '🟢';
    if (distance <= 25) return '🟡';
    return '🔴';
  }

  async sendMessage(chatId, message, options = {}) {
    try {
      if (!this.bot) {
        logger.warn('Telegram bot not initialized');
        return false;
      }

      await this.bot.sendMessage(chatId, message, {
        parse_mode: 'Markdown',
        ...options
      });
      
      return true;
    } catch (error) {
      logger.error('Error sending Telegram message:', error);
      return false;
    }
  }

  async sendNotification(message, chatId = null) {
    const targetChatId = chatId || this.defaultChatId;
    
    if (!targetChatId) {
      logger.warn('No chat ID configured for notifications');
      return false;
    }

    return await this.sendMessage(targetChatId, message);
  }

  async testBot(token, chatId = null) {
    try {
      const testBot = new TelegramBot(token, { polling: false });
      const botInfo = await testBot.getMe();
      
      if (chatId) {
        await testBot.sendMessage(chatId, '✅ Test successful! Bot is working correctly.');
      }
      
      return {
        success: true,
        bot_info: botInfo
      };
    } catch (error) {
      logger.error('Bot test failed:', error);
      return {
        success: false,
        error: error.message
      };
    }
  }

  async updateBotToken(newToken) {
    try {
      if (newToken !== this.token) {
        await this.createBot(newToken);
        logger.info('Bot token updated successfully');
      }
    } catch (error) {
      logger.error('Error updating bot token:', error);
      throw error;
    }
  }

  async getBotInfo() {
    try {
      if (!this.bot) {
        return { active: false, message: 'Bot not initialized' };
      }

      const botInfo = await this.bot.getMe();
      return {
        active: true,
        bot_info: botInfo,
        default_chat_id: this.defaultChatId
      };
    } catch (error) {
      logger.error('Error getting bot info:', error);
      return { active: false, error: error.message };
    }
  }

  isActive() {
    return this.bot !== null;
  }
}

module.exports = new TelegramService();