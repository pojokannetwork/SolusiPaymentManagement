// WhatsApp Gateway (QR-based) using whatsapp-web.js
// No third-party API; pairs via WhatsApp Web QR and persists session

const express = require('express');
const cors = require('cors');
const qrcode = require('qrcode');
const path = require('path');
const { Client, LocalAuth } = require('whatsapp-web.js');

const app = express();
app.use(cors({ origin: true }));
app.use(express.json());

const SESSION_DIR = path.join(__dirname, 'sessions');
const PORT = process.env.PORT || 3001;

let state = {
  status: 'initializing', // initializing | qr | ready | connecting | disconnected | error
  qr: null,
  info: null,
  error: null,
  ts: Date.now()
};

const client = new Client({
  authStrategy: new LocalAuth({ clientId: 'default', dataPath: SESSION_DIR }),
  puppeteer: {
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox']
  }
});

client.on('qr', async (qr) => {
  try {
    state.status = 'qr';
    state.qr = await qrcode.toDataURL(qr);
    state.ts = Date.now();
  } catch (e) {
    state.status = 'error';
    state.error = String(e);
    state.ts = Date.now();
  }
});

client.on('ready', () => {
  try {
    state.status = 'ready';
    state.qr = null;
    state.error = null;
    const info = client.info || {};
    state.info = {
      pushname: info.pushname || null,
      wid: (info.wid && (info.wid._serialized || info.wid.user)) || null,
      platform: info.platform || null
    };
    state.ts = Date.now();
  } catch (e) {
    state.status = 'ready';
    state.ts = Date.now();
  }
});

client.on('loading_screen', () => { state.status = 'connecting'; state.ts = Date.now(); });
client.on('auth_failure', (m) => { state.status = 'error'; state.error = m; state.ts = Date.now(); });
client.on('disconnected', (reason) => { state.status = 'disconnected'; state.qr = null; state.error = reason; state.ts = Date.now(); });

(async () => {
  try {
    await client.initialize();
  } catch (e) {
    state.status = 'error';
    state.error = String(e);
    state.ts = Date.now();
  }
})();

// Helpers
function normalizePhone(raw) {
  if (!raw) return '';
  let digits = String(raw).replace(/[^0-9+]/g, '');
  if (digits.startsWith('+')) digits = digits.substring(1);
  if (digits.startsWith('0')) digits = '62' + digits.replace(/^0+/, '');
  return digits;
}

// Routes
app.get('/status', (_req, res) => {
  res.json({ success: true, ...state });
});

app.post('/send', async (req, res) => {
  try {
    const toRaw = req.body.to || req.body.phone || '';
    const message = (req.body.message || '').toString();
    if (!toRaw || !message) return res.status(400).json({ success: false, message: 'to and message required' });
    if (state.status !== 'ready') return res.status(409).json({ success: false, message: 'Gateway not ready' });

    const to = normalizePhone(toRaw) + '@c.us';
    await client.sendMessage(to, message);
    res.json({ success: true });
  } catch (e) {
    res.status(500).json({ success: false, message: String(e) });
  }
});

app.post('/logout', async (_req, res) => {
  try {
    await client.logout();
    state.status = 'disconnected';
    state.qr = null;
    state.info = null;
    state.error = null;
    state.ts = Date.now();
    res.json({ success: true });
  } catch (e) {
    res.status(500).json({ success: false, message: String(e) });
  }
});

app.listen(PORT, () => {
  console.log('WhatsApp Gateway listening on :' + PORT);
});
