
const Payment = require('../models/Payment');
const paymentService = require('../services/paymentService');

exports.createPayment = async (req, res) => {
    const { customerId, invoiceNumber, amount } = req.body;
    try {
        const payment = await Payment.create({ customerId, invoiceNumber, amount });
        const gatewayResponse = await paymentService.createPayment(customerId, invoiceNumber, amount);
        payment.paymentLink = gatewayResponse.redirect_url || gatewayResponse.payment_url;
        await payment.save();
        res.json(payment);
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
};
