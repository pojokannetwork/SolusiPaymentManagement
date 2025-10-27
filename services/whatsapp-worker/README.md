# WhatsApp Worker

Worker Node.js sederhana untuk mengirim pesan WhatsApp secara otomatis dengan memanfaatkan WhatsApp Web (tanpa API berbayar). Worker membaca antrean dari tabel `whatsapp_queue` pada database aplikasi.

## Fitur

- Menjaga sesi WhatsApp Web (menggunakan `whatsapp-web.js`).
- Menampilkan QR login di UI (disimpan dalam tabel `whatsapp_sessions`, dibaca oleh halaman admin).
- Mengambil pesan berstatus `pending` dari `whatsapp_queue`, menandai `processing`, lalu mengirim via WhatsApp.
- Menandai hasil (sent/failed) dan mencatat log di `whatsapp_logs`.

## Persyaratan

- Node.js 18+ dan npm/pnpm/yarn (pilih salah satu).
- Chrome/Chromium terpasang (atau set `CHROME_BIN` ke path Chrome). Pada server Linux tanpa display, gunakan paket `chromium` + `xvfb` jika diperlukan.
- Database MySQL (koneksi menyesuaikan `config.json`).

## Instalasi

```bash
cd services/whatsapp-worker
npm install
cp config.example.json config.json
# edit config.json sesuai kredensial database
```

Struktur `config.json`:

```json
{
  "sessionName": "default",
  "pollIntervalMs": 5000,
  "maxBatch": 5,
  "database": {
    "host": "127.0.0.1",
    "port": 3306,
    "user": "root",
    "password": "",
    "database": "solusipaymentmanagement"
  }
}
```

## Menjalankan

```bash
npm start
```

Pertama kali dijalankan, worker akan menampilkan QR di terminal dan juga menulis QR ke tabel `whatsapp_sessions`. Scan QR tersebut menggunakan aplikasi WhatsApp admin. Setelah tersambung, status akan berubah menjadi `ready`.

Direkomendasikan menjalankan worker sebagai service menggunakan PM2/systemd:

```bash
# contoh dengan PM2
pm2 start worker.js --name whatsapp-worker
pm2 save
```

Pastikan direktori `.wwebjs_auth` (yang berisi sesi login) memiliki hak akses yang benar agar sesi tidak hilang setelah restart.

## Antrean Pesan

Antrean diisi otomatis oleh aplikasi (misalnya saat registrasi pelanggan, pengingat jatuh tempo, dsb). Struktur tabel:

- `whatsapp_queue`: menyimpan pesan yang belum terkirim.
- `whatsapp_sessions`: status koneksi & QR.
- `whatsapp_logs`: riwayat pengiriman.

Worker otomatis memproses pesan `pending`, mengirim, lalu memperbarui status menjadi `sent` atau `failed`.

## Catatan Keamanan

- Pastikan hanya nomor resmi perusahaan yang login dan server terlindungi.
- WhatsApp dapat memblokir nomor jika mendeteksi aktivitas otomatis berlebihan. Gunakan fitur ini secara bijak.

