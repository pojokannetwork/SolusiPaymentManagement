
const express = require('express');
const router = express.Router();
const PaymentController = require('../controllers/PaymentController');
const authMiddleware = require('../middlewares/auth');

router.post('/', authMiddleware.verifyToken, PaymentController.createPayment);

module.exports = router;
