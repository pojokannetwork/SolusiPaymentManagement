
const axios = require('axios');
const Setting = require('../models/Setting');

exports.sendMessage = async (phone, message) => {
    const waEndpoint = await Setting.findOne({ where: { key: 'wa_gateway_url' } });
    const waToken = await Setting.findOne({ where: { key: 'wa_gateway_token' } });

    if (!waEndpoint || !waToken) throw new Error('WhatsApp gateway not configured');

    await axios.post(waEndpoint.value, {
        to: phone,
        message
    }, {
        headers: { 'Authorization': `Bearer ${waToken.value}` }
    });
};
