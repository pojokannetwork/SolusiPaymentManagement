const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');
require('dotenv').config();

const logger = require('./utils/logger');
const database = require('./database/connection');

// Routes
const oltRoutes = require('./routes/olts');
const ontRoutes = require('./routes/onts');
const settingsRoutes = require('./routes/settings');
const dashboardRoutes = require('./routes/dashboard');
const eventsRoutes = require('./routes/events');
const telegramUsersRoutes = require('./routes/telegram-users');
const telegramChatRoutes = require('./routes/telegram-chat');

// Services
const telegramService = require('./services/telegramService');
const pollingService = require('./services/pollingService');

const app = express();
const PORT = process.env.PORT || 3001;

// Security middleware
app.use(helmet());
app.use(cors({
  origin: process.env.NODE_ENV === 'production' 
    ? 'http://localhost:3000' 
    : true,
  credentials: true
}));

// Rate limiting
const limiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 minutes
  max: 100 // limit each IP to 100 requests per windowMs
});
app.use(limiter);

// Body parsing middleware
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true }));

// Logging middleware
app.use((req, res, next) => {
  logger.info(`${req.method} ${req.url} - ${req.ip}`);
  next();
});

// API Routes
app.use('/api/olts', oltRoutes);
app.use('/api/onts', ontRoutes);
app.use('/api/settings', settingsRoutes);
app.use('/api/dashboard', dashboardRoutes);
app.use('/api/events', eventsRoutes);
app.use('/api/telegram-users', telegramUsersRoutes);
app.use('/api/telegram-chat', telegramChatRoutes);

// Health check endpoint
app.get('/api/health', (req, res) => {
  res.json({ 
    status: 'OK', 
    timestamp: new Date().toISOString(),
    uptime: process.uptime()
  });
});

// Error handling middleware
app.use((err, req, res, next) => {
  logger.error('Unhandled error:', err);
  res.status(500).json({ 
    error: 'Internal server error',
    message: process.env.NODE_ENV === 'development' ? err.message : 'Something went wrong'
  });
});

// 404 handler
app.use('*', (req, res) => {
  res.status(404).json({ error: 'Endpoint not found' });
});

// Initialize services
async function initializeServices() {
  try {
    // Test database connection
    await database.testConnection();
    logger.info('Database connected successfully');

    // Initialize Telegram service
    await telegramService.initialize();
    
    // Start polling service
    pollingService.start();
    
    logger.info('All services initialized successfully');
  } catch (error) {
    logger.error('Failed to initialize services:', error);
    process.exit(1);
  }
}

// Start server
app.listen(PORT, async () => {
  logger.info(`Server running on port ${PORT}`);
  await initializeServices();
});

// Graceful shutdown
process.on('SIGINT', async () => {
  logger.info('Shutting down gracefully...');
  pollingService.stop();
  await database.closeConnection();
  process.exit(0);
});

module.exports = app;