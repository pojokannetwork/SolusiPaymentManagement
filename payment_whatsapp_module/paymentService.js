
const axios = require('axios');
const Setting = require('../models/Setting');

exports.createPayment = async (customerId, invoiceNumber, amount) => {
    const gateway = await Setting.findOne({ where: { key: 'payment_gateway' } });
    const apiKey = await Setting.findOne({ where: { key: 'payment_api_key' } });

    if (!gateway || !apiKey) throw new Error('Payment gateway not configured');

    // Contoh Midtrans style
    const response = await axios.post(gateway.value + '/charge', {
        order_id: invoiceNumber,
        gross_amount: amount
    }, {
        headers: { 'Authorization': `Bearer ${apiKey.value}` }
    });

    return response.data;
};
