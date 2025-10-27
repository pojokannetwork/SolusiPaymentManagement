import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import dotenv from 'dotenv';
import mysql from 'mysql2/promise';
import qrcodeTerminal from 'qrcode-terminal';
import { Client, LocalAuth } from 'whatsapp-web.js';

dotenv.config();

const __dirname = path.dirname(fileURLToPath(import.meta.url));

function loadConfig() {
    const fallbackPath = path.join(__dirname, 'config.example.json');
    const configPath = process.env.WHATSAPP_CONFIG
        ? path.resolve(process.env.WHATSAPP_CONFIG)
        : path.join(__dirname, 'config.json');

    let baseConfig = {};
    if (fs.existsSync(fallbackPath)) {
        baseConfig = JSON.parse(fs.readFileSync(fallbackPath, 'utf-8'));
    }

    if (fs.existsSync(configPath)) {
        const override = JSON.parse(fs.readFileSync(configPath, 'utf-8'));
        return { ...baseConfig, ...override };
    }

    console.warn(`[WhatsappWorker] config.json tidak ditemukan, menggunakan config.example.json`);
    return baseConfig;
}

const config = loadConfig();
const sessionName = config.sessionName || 'default';
const pollIntervalMs = config.pollIntervalMs || 5000;
const maxBatch = config.maxBatch || 5;

let pool;

async function initPool() {
    pool = mysql.createPool({
        host: config.database?.host || '127.0.0.1',
        port: config.database?.port || 3306,
        user: config.database?.user || 'root',
        password: config.database?.password || '',
        database: config.database?.database || 'solusipaymentmanagement',
        waitForConnections: true,
        connectionLimit: 5,
        timezone: 'Z'
    });
    await pool.query('SELECT 1');
    console.log('[WhatsappWorker] Koneksi database siap.');
}

async function updateSession(payload) {
    const conn = await pool.getConnection();
    try {
        const now = new Date();
        const rows = await conn.query(
            "SELECT id FROM whatsapp_sessions WHERE session_name = ?",
            [sessionName]
        );

        const data = {
            status: payload.status,
            qr_base64: payload.qr_base64 ?? null,
            info_json: payload.info ? JSON.stringify(payload.info) : null,
            error_message: payload.error_message ?? null,
            last_seen: payload.last_seen ?? now,
            updated_at: now,
        };

        if (rows[0].length > 0) {
            await conn.query(
                "UPDATE whatsapp_sessions SET status = ?, qr_base64 = ?, info_json = ?, error_message = ?, last_seen = ?, updated_at = ? WHERE session_name = ?",
                [
                    data.status,
                    data.qr_base64,
                    data.info_json,
                    data.error_message,
                    data.last_seen,
                    data.updated_at,
                    sessionName,
                ]
            );
        } else {
            await conn.query(
                "INSERT INTO whatsapp_sessions (session_name, status, qr_base64, info_json, error_message, last_seen, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    sessionName,
                    data.status,
                    data.qr_base64,
                    data.info_json,
                    data.error_message,
                    data.last_seen,
                    now,
                    now,
                ]
            );
        }
    } finally {
        conn.release();
    }
}

async function logQueue(queueId, level, message, context = null) {
    await pool.query(
        "INSERT INTO whatsapp_logs (queue_id, level, message, context_json, created_at) VALUES (?, ?, ?, ?, ?)",
        [
            queueId,
            level,
            message,
            context ? JSON.stringify(context) : null,
            new Date(),
        ]
    );
}

async function fetchPendingMessages() {
    const conn = await pool.getConnection();
    try {
        const now = new Date();
        const [rows] = await conn.query(
            "SELECT * FROM whatsapp_queue WHERE session_name = ? AND status = 'pending' AND scheduled_at <= ? ORDER BY scheduled_at ASC LIMIT ?",
            [sessionName, now, maxBatch]
        );

        const selected = [];
        for (const row of rows) {
            const [result] = await conn.query(
                "UPDATE whatsapp_queue SET status = 'processing', attempts = attempts + 1, updated_at = ? WHERE id = ? AND status = 'pending'",
                [now, row.id]
            );
            if (result.affectedRows === 1) {
                selected.push(row);
            }
        }
        return selected;
    } finally {
        conn.release();
    }
}

async function markQueueSuccess(id) {
    await pool.query(
        "UPDATE whatsapp_queue SET status = 'sent', sent_at = ?, updated_at = ? WHERE id = ?",
        [new Date(), new Date(), id]
    );
}

async function markQueueFailure(id, error) {
    await pool.query(
        "UPDATE whatsapp_queue SET status = 'failed', last_error = ?, updated_at = ? WHERE id = ?",
        [String(error).slice(0, 1000), new Date(), id]
    );
}

function normalizePhone(phone) {
    if (!phone) return '';
    let digits = String(phone).replace(/[^0-9+]/g, '');
    if (!digits) return '';
    if (digits.startsWith('+')) {
        digits = digits.substring(1);
    } else if (digits.startsWith('0')) {
        digits = '62' + digits.substring(1);
    }
    return digits;
}

function buildWaJid(phone) {
    const normalized = normalizePhone(phone);
    return normalized ? normalized + '@c.us' : '';
}

const client = new Client({
    authStrategy: new LocalAuth({
        clientId: sessionName,
        dataPath: path.join(__dirname, '.wwebjs_auth')
    }),
    puppeteer: {
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox'],
        executablePath: process.env.CHROME_BIN || undefined
    },
    takeoverOnConflict: true,
    takeoverTimeoutMs: 0
});

client.on('qr', async (qr) => {
    console.log('[WhatsappWorker] QR baru diterima. Silakan scan melalui aplikasi WhatsApp.');
    qrcodeTerminal.generate(qr, { small: true });
    await updateSession({
        status: 'qr',
        qr_base64: await generateQrDataUrl(qr),
        error_message: null,
        last_seen: new Date(),
    });
});

client.on('authenticated', () => {
    console.log('[WhatsappWorker] Authenticated.');
});

client.on('ready', async () => {
    console.log('[WhatsappWorker] WhatsApp siap.');
    await updateSession({
        status: 'ready',
        qr_base64: null,
        info: {
            pushname: client.info?.pushname ?? null,
            phone: client.info?.wid?.user ?? null,
        },
        error_message: null,
        last_seen: new Date(),
    });
});

client.on('disconnected', async (reason) => {
    console.log('[WhatsappWorker] Terputus:', reason);
    await updateSession({
        status: 'disconnected',
        qr_base64: null,
        error_message: reason,
        last_seen: new Date(),
    });
    setTimeout(() => client.initialize(), 5000);
});

client.on('auth_failure', async (message) => {
    console.error('[WhatsappWorker] Auth failure:', message);
    await updateSession({
        status: 'error',
        error_message: message,
        qr_base64: null,
        last_seen: new Date(),
    });
});

async function generateQrDataUrl(qr) {
    return new Promise((resolve, reject) => {
        import('qrcode').then(QR => {
            QR.default.toDataURL(qr, { margin: 1, scale: 8 }, (err, url) => {
                if (err) reject(err);
                else resolve(url);
            });
        }).catch(reject);
    });
}

async function processQueue() {
    try {
        const jobs = await fetchPendingMessages();
        if (!jobs.length) {
            return;
        }

        for (const job of jobs) {
            try {
                const jid = buildWaJid(job.phone);
                if (!jid) {
                    throw new Error('Nomor WhatsApp tidak valid: ' + job.phone);
                }
                await client.sendMessage(jid, job.message);
                await markQueueSuccess(job.id);
                await logQueue(job.id, 'info', 'Pesan terkirim.');
                console.log(`[WhatsappWorker] Pesan terkirim ke ${job.phone}`);
            } catch (err) {
                console.error('[WhatsappWorker] Gagal mengirim pesan:', err);
                await markQueueFailure(job.id, err);
                await logQueue(job.id, 'error', 'Gagal mengirim pesan', { error: String(err) });
            }
        }
    } catch (err) {
        console.error('[WhatsappWorker] Error saat memproses antrean:', err);
    }
}

async function start() {
    await initPool();
    await updateSession({
        status: 'connecting',
        qr_base64: null,
        error_message: null,
        last_seen: new Date(),
    });

    await client.initialize();

    setInterval(processQueue, pollIntervalMs);
}

process.on('SIGINT', async () => {
    console.log('[WhatsappWorker] SIGINT diterima, menutup sesi...');
    await updateSession({
        status: 'disconnected',
        qr_base64: null,
        error_message: 'Worker dimatikan.',
        last_seen: new Date(),
    });
    await client.destroy();
    process.exit(0);
});

start().catch((err) => {
    console.error('[WhatsappWorker] Gagal start worker:', err);
    process.exit(1);
});
