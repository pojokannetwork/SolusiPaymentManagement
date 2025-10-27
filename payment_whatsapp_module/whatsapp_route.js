
const express = require('express');
const router = express.Router();
const WhatsAppController = require('../controllers/WhatsAppController');
const authMiddleware = require('../middlewares/auth');

router.post('/send', authMiddleware.verifyToken, WhatsAppController.sendMessage);

module.exports = router;
