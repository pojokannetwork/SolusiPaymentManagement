-- SQLite compatible schema for SolusiPaymentManagement

-- Users table (pengguna)
CREATE TABLE pengguna (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    nama TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'customer',
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

-- Employees table (karyawan)
CREATE TABLE karyawan (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    nip TEXT UNIQUE,
    departemen TEXT,
    posisi TEXT,
    gaji_pokok REAL,
    foto_path TEXT,
    tgl_masuk TEXT,
    status TEXT DEFAULT 'active',
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (user_id) REFERENCES pengguna(id) ON DELETE CASCADE
);

-- Assets table (aset)
CREATE TABLE aset (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    kode TEXT UNIQUE NOT NULL,
    nama TEXT NOT NULL,
    kategori TEXT,
    nilai_perolehan REAL,
    tgl_beli TEXT,
    kondisi TEXT DEFAULT 'baik',
    status TEXT DEFAULT 'tersedia',
    lokasi TEXT,
    assigned_to INTEGER,
    umur_ekonomis_bulan INTEGER DEFAULT 60,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (assigned_to) REFERENCES karyawan(id) ON DELETE SET NULL
);

-- Revenue table (pendapatan)
CREATE TABLE pendapatan (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tanggal TEXT NOT NULL,
    kategori TEXT,
    deskripsi TEXT,
    jumlah REAL NOT NULL,
    sumber TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

-- Taxes table (pajak)
CREATE TABLE pajak (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    periode_bulan INTEGER NOT NULL,
    periode_tahun INTEGER NOT NULL,
    jenis TEXT NOT NULL,
    dasar_pengenaan REAL,
    tarif REAL,
    nilai REAL,
    status TEXT DEFAULT 'draft',
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

-- Payroll table (penggajian)
CREATE TABLE payroll (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    karyawan_id INTEGER NOT NULL,
    periode_bulan INTEGER NOT NULL,
    periode_tahun INTEGER NOT NULL,
    gaji_pokok REAL,
    lembur REAL DEFAULT 0,
    bonus REAL DEFAULT 0,
    potongan REAL DEFAULT 0,
    pajak REAL DEFAULT 0,
    total_bayar REAL,
    status TEXT DEFAULT 'draft',
    paid_at TEXT,
    metode TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (karyawan_id) REFERENCES karyawan(id) ON DELETE CASCADE
);

-- Attendance table (kehadiran)
CREATE TABLE kehadiran (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    karyawan_id INTEGER NOT NULL,
    tanggal TEXT NOT NULL,
    clock_in TEXT,
    clock_out TEXT,
    lembur_jam REAL DEFAULT 0,
    catatan TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (karyawan_id) REFERENCES karyawan(id) ON DELETE CASCADE
);

-- Leave requests table (cuti_permintaan)
CREATE TABLE cuti_permintaan (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    karyawan_id INTEGER NOT NULL,
    tgl_mulai TEXT NOT NULL,
    tgl_selesai TEXT NOT NULL,
    alasan TEXT,
    status TEXT DEFAULT 'pending',
    approved_by INTEGER,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (karyawan_id) REFERENCES karyawan(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES karyawan(id) ON DELETE SET NULL
);

-- Customers table (pelanggan)
CREATE TABLE pelanggan (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    kode_pelanggan TEXT UNIQUE NOT NULL,
    nama TEXT NOT NULL,
    email TEXT,
    telp TEXT,
    alamat TEXT,
    lat REAL,
    lon REAL,
    paket TEXT,
    status TEXT DEFAULT 'active',
    router_id INTEGER,
    pppoe_user TEXT UNIQUE,
    pppoe_pass_enc TEXT,
    profile_aktif TEXT,
    profile_isolir TEXT,
    ip_static TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

-- Roles table (peran)
CREATE TABLE peran (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nama TEXT NOT NULL UNIQUE,
    deskripsi TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

-- Permissions table (izin)
CREATE TABLE izin (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    kode TEXT NOT NULL UNIQUE,
    deskripsi TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

-- Role permissions table
CREATE TABLE role_permissions (
    role_id INTEGER NOT NULL,
    izin_id INTEGER NOT NULL,
    PRIMARY KEY (role_id, izin_id),
    FOREIGN KEY (role_id) REFERENCES peran(id) ON DELETE CASCADE,
    FOREIGN KEY (izin_id) REFERENCES izin(id) ON DELETE CASCADE
);

-- User roles table
CREATE TABLE user_roles (
    user_id INTEGER NOT NULL,
    role_id INTEGER NOT NULL,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES pengguna(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES peran(id) ON DELETE CASCADE
);

-- Payment gateways table
CREATE TABLE payment_gateways (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nama TEXT NOT NULL,
    provider TEXT NOT NULL,
    is_active INTEGER DEFAULT 1,
    config_json TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

-- Invoices table (faktur)
CREATE TABLE faktur (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nomor TEXT UNIQUE NOT NULL,
    pelanggan_id INTEGER NOT NULL,
    tanggal TEXT NOT NULL,
    jatuh_tempo TEXT NOT NULL,
    subtotal REAL,
    pajak REAL DEFAULT 0,
    total REAL,
    status TEXT DEFAULT 'draft',
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id) ON DELETE CASCADE
);

-- Invoice items table
CREATE TABLE invoice_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    faktur_id INTEGER NOT NULL,
    deskripsi TEXT NOT NULL,
    qty REAL DEFAULT 1,
    harga REAL,
    subtotal REAL,
    FOREIGN KEY (faktur_id) REFERENCES faktur(id) ON DELETE CASCADE
);

-- Transactions table (transaksi)
CREATE TABLE transaksi (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    faktur_id INTEGER NOT NULL,
    gateway_id INTEGER NOT NULL,
    amount REAL,
    currency TEXT DEFAULT 'IDR',
    external_ref TEXT UNIQUE,
    status TEXT DEFAULT 'pending',
    paid_at TEXT,
    raw_callback_json TEXT,
    signature_valid INTEGER DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (faktur_id) REFERENCES faktur(id) ON DELETE CASCADE,
    FOREIGN KEY (gateway_id) REFERENCES payment_gateways(id) ON DELETE CASCADE
);

-- Activity logs table
CREATE TABLE activity_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    aksi TEXT NOT NULL,
    endpoint TEXT,
    ip TEXT,
    user_agent TEXT,
    payload_singkat TEXT,
    created_at TEXT DEFAULT (datetime('now'))
);

-- Sessions table
CREATE TABLE sesi (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    session_token TEXT UNIQUE NOT NULL,
    ip TEXT,
    user_agent TEXT,
    last_activity TEXT DEFAULT (datetime('now')),
    expired_at TEXT,
    FOREIGN KEY (user_id) REFERENCES pengguna(id) ON DELETE CASCADE
);

-- Notifications table
CREATE TABLE notifikasi (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    judul TEXT NOT NULL,
    isi TEXT,
    tipe TEXT DEFAULT 'info',
    is_read INTEGER DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (user_id) REFERENCES pengguna(id) ON DELETE CASCADE
);

-- MikroTik routers table
CREATE TABLE mikrotik_routers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    host TEXT NOT NULL,
    port INTEGER DEFAULT 8728,
    username TEXT NOT NULL,
    password_enc TEXT NOT NULL,
    use_tls INTEGER DEFAULT 0,
    comment TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

-- Settings table
CREATE TABLE settings (
    key TEXT PRIMARY KEY,
    value TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

-- Insert default settings
INSERT INTO settings (key, value) VALUES
('app_name', 'SolusiPaymentManagement'),
('app_version', '1.0.0'),
('timezone', 'Asia/Jakarta'),
('currency', 'IDR'),
('ollama_host', 'http://localhost:11434'),
('ollama_model', 'llama3'),
('radius_db_host', 'localhost'),
('radius_db_name', 'radius'),
('radius_db_user', 'radius'),
('radius_db_pass', ''),
('nas_ip', '192.168.1.1'),
('nas_secret', 'testing123'),
('profile_default', 'default'),
('profile_isolir', 'ISOLIR'),
('source_of_truth', 'radius');

-- Insert default roles

-- Warehouse / Inventory tables (SQLite)
CREATE TABLE IF NOT EXISTS inventory_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    description TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS inventory_locations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    description TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS inventory_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    category_id INTEGER,
    unit TEXT DEFAULT 'pcs',
    stock_qty REAL DEFAULT 0,
    location_id INTEGER,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (category_id) REFERENCES inventory_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (location_id) REFERENCES inventory_locations(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS inventory_receipts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    doc_no TEXT NOT NULL UNIQUE,
    date TEXT NOT NULL,
    supplier TEXT,
    status TEXT DEFAULT 'Draft',
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS inventory_receipt_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    receipt_id INTEGER NOT NULL,
    item_id INTEGER NOT NULL,
    qty REAL NOT NULL,
    unit TEXT DEFAULT 'pcs',
    note TEXT,
    FOREIGN KEY (receipt_id) REFERENCES inventory_receipts(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS inventory_issues (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    doc_no TEXT NOT NULL UNIQUE,
    date TEXT NOT NULL,
    target TEXT,
    status TEXT DEFAULT 'Draft',
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS inventory_issue_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    issue_id INTEGER NOT NULL,
    item_id INTEGER NOT NULL,
    qty REAL NOT NULL,
    unit TEXT DEFAULT 'pcs',
    note TEXT,
    FOREIGN KEY (issue_id) REFERENCES inventory_issues(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS inventory_adjustments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    doc_no TEXT NOT NULL UNIQUE,
    date TEXT NOT NULL,
    item_id INTEGER NOT NULL,
    change_qty REAL NOT NULL,
    note TEXT,
    status TEXT DEFAULT 'Draft',
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE RESTRICT
);
INSERT INTO peran (nama, deskripsi) VALUES
('admin', 'Administrator with full access'),
('employee', 'Employee with limited access'),
('customer', 'Customer portal access');

-- Insert default permissions
INSERT INTO izin (kode, deskripsi) VALUES
('admin.dashboard', 'Access admin dashboard'),
('admin.employees', 'Manage employees'),
('admin.assets', 'Manage assets'),
('admin.revenue', 'Manage revenue'),
('admin.taxes', 'Manage taxes'),
('admin.payroll', 'Manage payroll'),
('admin.attendance', 'Manage attendance'),
('admin.customers', 'Manage customers'),
('admin.invoices', 'Manage invoices'),
('admin.transactions', 'View transactions'),
('admin.payment_gateways', 'Configure payment gateways'),
('admin.roles', 'Manage roles and permissions'),
('admin.activity_logs', 'View activity logs'),
('admin.reports', 'View reports'),
('admin.noc_assistant', 'Access NOC assistant'),
('admin.call_center', 'Access call center'),
('admin.customers_map', 'View customers map'),
('admin.settings', 'System settings'),
('employee.dashboard', 'Access employee dashboard'),
('employee.attendance', 'Manage attendance'),
('employee.leave', 'Request leave'),
('customer.dashboard', 'Access customer dashboard'),
('customer.invoices', 'View invoices'),
('customer.pay', 'Make payments');

-- Assign permissions to admin role
INSERT INTO role_permissions (role_id, izin_id)
SELECT r.id, p.id FROM peran r, izin p WHERE r.nama = 'admin';

-- Assign permissions to employee role
INSERT INTO role_permissions (role_id, izin_id)
SELECT r.id, p.id FROM peran r, izin p WHERE r.nama = 'employee' AND p.kode LIKE 'employee.%';

-- Assign permissions to customer role
INSERT INTO role_permissions (role_id, izin_id)
SELECT r.id, p.id FROM peran r, izin p WHERE r.nama = 'customer' AND p.kode LIKE 'customer.%';

-- Create default admin user
INSERT INTO pengguna (email, password_hash, nama, role) VALUES
('admin@solusipayment.local', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewdBPjYfY8Z6Q1K2', 'Administrator', 'admin'); -- Password: Admin123!

-- Create admin employee record
INSERT INTO karyawan (user_id, nip, departemen, posisi, gaji_pokok) VALUES
(1, 'ADM001', 'IT', 'System Administrator', 5000000.00);
