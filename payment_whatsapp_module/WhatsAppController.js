
const waService = require('../services/waService');

exports.sendMessage = async (req, res) => {
    const { phone, message } = req.body;
    try {
        await waService.sendMessage(phone, message);
        res.json({ success: true });
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
};
