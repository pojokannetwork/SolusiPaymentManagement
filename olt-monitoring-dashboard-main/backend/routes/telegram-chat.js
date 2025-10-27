const express = require('express');
const sqlite3 = require('sqlite3');
const path = require('path');

const router = express.Router();
const dbPath = path.join(__dirname, '../database/olt_monitoring.db');

// Get telegram chat history with pagination and filters
router.get('/', (req, res) => {
  const db = new sqlite3.Database(dbPath);
  
  const {
    page = 1,
    limit = 10,
    message_type,
    status,
    search,
    chat_id
  } = req.query;
  
  const offset = (page - 1) * limit;
  
  let whereConditions = [];
  let params = [];
  
  // Build WHERE conditions based on filters
  if (message_type && message_type !== 'all') {
    whereConditions.push('message_type = ?');
    params.push(message_type);
  }
  
  if (status && status !== 'all') {
    whereConditions.push('status = ?');
    params.push(status);
  }
  
  if (chat_id) {
    whereConditions.push('chat_id = ?');
    params.push(chat_id);
  }
  
  if (search) {
    whereConditions.push(`(
      message LIKE ? OR 
      username LIKE ? OR 
      first_name LIKE ? OR 
      last_name LIKE ? OR
      bot_response LIKE ?
    )`);
    const searchPattern = `%${search}%`;
    params.push(searchPattern, searchPattern, searchPattern, searchPattern, searchPattern);
  }
  
  const whereClause = whereConditions.length > 0 ? `WHERE ${whereConditions.join(' AND ')}` : '';
  
  // Get total count for pagination
  const countQuery = `
    SELECT COUNT(*) as total 
    FROM telegram_chat_history 
    ${whereClause}
  `;
  
  db.get(countQuery, params, (err, countResult) => {
    if (err) {
      console.error('Error counting chat history:', err);
      return res.status(500).json({
        success: false,
        error: 'Failed to count chat history'
      });
    }
    
    const total = countResult.total;
    const totalPages = Math.ceil(total / limit);
    
    // Get paginated data
    const dataQuery = `
      SELECT 
        id,
        chat_id,
        username,
        first_name,
        last_name,
        message_type,
        message,
        command,
        bot_response,
        response_time,
        status,
        created_at,
        updated_at
      FROM telegram_chat_history 
      ${whereClause}
      ORDER BY created_at DESC
      LIMIT ? OFFSET ?
    `;
    
    const dataParams = [...params, limit, offset];
    
    db.all(dataQuery, dataParams, (err, rows) => {
      if (err) {
        console.error('Error fetching chat history:', err);
        return res.status(500).json({
          success: false,
          error: 'Failed to fetch chat history'
        });
      }
      
      res.json({
        success: true,
        data: {
          chats: rows,
          total,
          page: parseInt(page),
          totalPages,
          limit: parseInt(limit)
        }
      });
    });
  });
  
  db.close();
});

// Get chat statistics
router.get('/stats', (req, res) => {
  const db = new sqlite3.Database(dbPath);
  
  const statsQuery = `
    SELECT 
      COUNT(*) as total_messages,
      COUNT(DISTINCT username) as unique_users,
      COUNT(CASE WHEN message_type = 'command' THEN 1 END) as commands_count,
      COUNT(CASE WHEN message_type = 'text' THEN 1 END) as text_messages_count,
      COUNT(CASE WHEN status = 'success' THEN 1 END) as successful_responses,
      COUNT(CASE WHEN status = 'error' THEN 1 END) as error_responses,
      AVG(CASE 
        WHEN response_time IS NOT NULL AND created_at IS NOT NULL 
        THEN (julianday(response_time) - julianday(created_at)) * 86400000 
      END) as avg_response_time_ms
    FROM telegram_chat_history
    WHERE created_at >= datetime('now', '-7 days')
  `;
  
  db.get(statsQuery, [], (err, stats) => {
    if (err) {
      console.error('Error fetching chat statistics:', err);
      return res.status(500).json({
        success: false,
        error: 'Failed to fetch statistics'
      });
    }
    
    res.json({
      success: true,
      data: {
        ...stats,
        avg_response_time_ms: stats.avg_response_time_ms ? Math.round(stats.avg_response_time_ms) : null
      }
    });
  });
  
  db.close();
});

// Add new chat history entry (untuk dipanggil dari telegram service)
router.post('/', (req, res) => {
  const db = new sqlite3.Database(dbPath);
  
  const {
    chat_id,
    username,
    first_name,
    last_name,
    message_type,
    message,
    command,
    bot_response,
    response_time,
    status = 'success'
  } = req.body;
  
  if (!chat_id || !message) {
    return res.status(400).json({
      success: false,
      error: 'chat_id and message are required'
    });
  }
  
  const insertQuery = `
    INSERT INTO telegram_chat_history (
      chat_id, username, first_name, last_name, message_type, 
      message, command, bot_response, response_time, status, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
  `;
  
  const params = [
    chat_id,
    username,
    first_name,
    last_name,
    message_type || 'text',
    message,
    command,
    bot_response,
    response_time,
    status
  ];
  
  db.run(insertQuery, params, function(err) {
    if (err) {
      console.error('Error inserting chat history:', err);
      return res.status(500).json({
        success: false,
        error: 'Failed to save chat history'
      });
    }
    
    res.json({
      success: true,
      data: {
        id: this.lastID,
        message: 'Chat history saved successfully'
      }
    });
  });
  
  db.close();
});

// Delete old chat history (cleanup endpoint)
router.delete('/cleanup', (req, res) => {
  const db = new sqlite3.Database(dbPath);
  const { days = 30 } = req.query;
  
  const deleteQuery = `
    DELETE FROM telegram_chat_history 
    WHERE created_at < datetime('now', '-${days} days')
  `;
  
  db.run(deleteQuery, [], function(err) {
    if (err) {
      console.error('Error cleaning up chat history:', err);
      return res.status(500).json({
        success: false,
        error: 'Failed to cleanup chat history'
      });
    }
    
    res.json({
      success: true,
      data: {
        deleted_count: this.changes,
        message: `Deleted chat history older than ${days} days`
      }
    });
  });
  
  db.close();
});

module.exports = router;