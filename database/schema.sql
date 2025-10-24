-- SolusiPaymentManagement Database Schema
-- MySQL 5.7+ / MariaDB 10.3+

CREATE DATABASE IF NOT EXISTS solusipaymentmanagement CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE solusipaymentmanagement;

-- Users table (pengguna)
CREATE TABLE pengguna (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nama VARCHAR(255) NOT NULL,
    role ENUM('admin', 'employee', 'customer') NOT NULL DEFAULT 'customer',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active)
);

-- Employees table (karyawan)
CREATE TABLE karyawan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nip VARCHAR(50) UNIQUE,
    departemen VARCHAR(100),
    posisi VARCHAR(100),
    gaji_pokok DECIMAL(15,2),
    foto_path VARCHAR(255),
    tgl_masuk DATE,
    status ENUM('active', 'inactive', 'terminated') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES pengguna(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_nip (nip),
    INDEX idx_departemen (departemen),
    INDEX idx_status (status)
);

-- Assets table (aset)
CREATE TABLE aset (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(50) UNIQUE NOT NULL,
    nama VARCHAR(255) NOT NULL,
    kategori VARCHAR(100),
    nilai_perolehan DECIMAL(15,2),
    tgl_beli DATE,
    kondisi ENUM('baik', 'rusak_ringan', 'rusak_berat') DEFAULT 'baik',
    status ENUM('tersedia', 'digunakan', 'dijual', 'rusak') DEFAULT 'tersedia',
    lokasi VARCHAR(255),
    assigned_to INT NULL,
    umur_ekonomis_bulan INT DEFAULT 60,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES karyawan(id) ON DELETE SET NULL,
    INDEX idx_kode (kode),
    INDEX idx_kategori (kategori),
    INDEX idx_status (status),
    INDEX idx_assigned_to (assigned_to)
);

-- Revenue table (pendapatan)
CREATE TABLE pendapatan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    kategori VARCHAR(100),
    deskripsi TEXT,
    jumlah DECIMAL(15,2) NOT NULL,
    sumber VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tanggal (tanggal),
    INDEX idx_kategori (kategori)
);

-- Taxes table (pajak)
CREATE TABLE pajak (
    id INT AUTO_INCREMENT PRIMARY KEY,
    periode_bulan TINYINT NOT NULL,
    periode_tahun YEAR NOT NULL,
    jenis VARCHAR(100) NOT NULL,
    dasar_pengenaan DECIMAL(15,2),
    tarif DECIMAL(5,4),
    nilai DECIMAL(15,2),
    status ENUM('draft', 'final') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_periode (periode_bulan, periode_tahun),
    INDEX idx_jenis (jenis),
    INDEX idx_status (status)
);

-- Payroll table (penggajian)
CREATE TABLE payroll (
    id INT AUTO_INCREMENT PRIMARY KEY,
    karyawan_id INT NOT NULL,
    periode_bulan TINYINT NOT NULL,
    periode_tahun YEAR NOT NULL,
    gaji_pokok DECIMAL(15,2),
    lembur DECIMAL(15,2) DEFAULT 0,
    bonus DECIMAL(15,2) DEFAULT 0,
    potongan DECIMAL(15,2) DEFAULT 0,
    pajak DECIMAL(15,2) DEFAULT 0,
    total_bayar DECIMAL(15,2),
    status ENUM('draft', 'approved', 'paid') DEFAULT 'draft',
    paid_at TIMESTAMP NULL,
    metode VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (karyawan_id) REFERENCES karyawan(id) ON DELETE CASCADE,
    INDEX idx_karyawan_id (karyawan_id),
    INDEX idx_periode (periode_bulan, periode_tahun),
    INDEX idx_status (status)
);

-- Attendance table (kehadiran)
CREATE TABLE kehadiran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    karyawan_id INT NOT NULL,
    tanggal DATE NOT NULL,
    clock_in TIME NULL,
    clock_out TIME NULL,
    lembur_jam DECIMAL(5,2) DEFAULT 0,
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (karyawan_id) REFERENCES karyawan(id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (karyawan_id, tanggal),
    INDEX idx_karyawan_id (karyawan_id),
    INDEX idx_tanggal (tanggal)
);

-- Leave requests table (cuti_permintaan)
CREATE TABLE cuti_permintaan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    karyawan_id INT NOT NULL,
    tgl_mulai DATE NOT NULL,
    tgl_selesai DATE NOT NULL,
    alasan TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (karyawan_id) REFERENCES karyawan(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES karyawan(id) ON DELETE SET NULL,
    INDEX idx_karyawan_id (karyawan_id),
    INDEX idx_status (status),
    INDEX idx_tgl_mulai (tgl_mulai),
    INDEX idx_tgl_selesai (tgl_selesai)
);

-- Customers table (pelanggan) - Updated for ISP
CREATE TABLE pelanggan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_pelanggan VARCHAR(50) UNIQUE NOT NULL,
    nama VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    telp VARCHAR(20),
    alamat TEXT,
    lat DECIMAL(10,8) NULL,
    lon DECIMAL(11,8) NULL,
    paket VARCHAR(100),
    status ENUM('active', 'isolir', 'suspended', 'terminated') DEFAULT 'active',
    router_id INT NULL,
    pppoe_user VARCHAR(100) UNIQUE,
    pppoe_pass_enc VARCHAR(255),
    profile_aktif VARCHAR(100),
    profile_isolir VARCHAR(100),
    ip_static VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kode_pelanggan (kode_pelanggan),
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_router_id (router_id),
    INDEX idx_pppoe_user (pppoe_user)
);

-- Roles table (peran)
CREATE TABLE peran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL UNIQUE,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Permissions table (izin)
CREATE TABLE izin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(100) NOT NULL UNIQUE,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Role permissions table
CREATE TABLE role_permissions (
    role_id INT NOT NULL,
    izin_id INT NOT NULL,
    PRIMARY KEY (role_id, izin_id),
    FOREIGN KEY (role_id) REFERENCES peran(id) ON DELETE CASCADE,
    FOREIGN KEY (izin_id) REFERENCES izin(id) ON DELETE CASCADE
);

-- User roles table
CREATE TABLE user_roles (
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES pengguna(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES peran(id) ON DELETE CASCADE
);

-- Payment gateways table
CREATE TABLE payment_gateways (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    provider VARCHAR(50) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    config_json TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_provider (provider),
    INDEX idx_is_active (is_active)
);

-- Invoices table (faktur)
CREATE TABLE faktur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nomor VARCHAR(50) UNIQUE NOT NULL,
    pelanggan_id INT NOT NULL,
    tanggal DATE NOT NULL,
    jatuh_tempo DATE NOT NULL,
    subtotal DECIMAL(15,2),
    pajak DECIMAL(15,2) DEFAULT 0,
    total DECIMAL(15,2),
    status ENUM('draft', 'sent', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id) ON DELETE CASCADE,
    INDEX idx_nomor (nomor),
    INDEX idx_pelanggan_id (pelanggan_id),
    INDEX idx_status (status),
    INDEX idx_jatuh_tempo (jatuh_tempo)
);

-- Invoice items table
CREATE TABLE invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    faktur_id INT NOT NULL,
    deskripsi VARCHAR(255) NOT NULL,
    qty DECIMAL(10,2) DEFAULT 1,
    harga DECIMAL(15,2),
    subtotal DECIMAL(15,2),
    FOREIGN KEY (faktur_id) REFERENCES faktur(id) ON DELETE CASCADE,
    INDEX idx_faktur_id (faktur_id)
);

-- Transactions table (transaksi)
CREATE TABLE transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    faktur_id INT NOT NULL,
    gateway_id INT NOT NULL,
    amount DECIMAL(15,2),
    currency VARCHAR(3) DEFAULT 'IDR',
    external_ref VARCHAR(255) UNIQUE,
    status ENUM('pending', 'paid', 'failed', 'expired', 'refunded') DEFAULT 'pending',
    paid_at TIMESTAMP NULL,
    raw_callback_json TEXT,
    signature_valid TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (faktur_id) REFERENCES faktur(id) ON DELETE CASCADE,
    FOREIGN KEY (gateway_id) REFERENCES payment_gateways(id) ON DELETE CASCADE,
    INDEX idx_faktur_id (faktur_id),
    INDEX idx_gateway_id (gateway_id),
    INDEX idx_external_ref (external_ref),
    INDEX idx_status (status)
);

-- Activity logs table
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    aksi VARCHAR(255) NOT NULL,
    endpoint VARCHAR(255),
    ip VARCHAR(45),
    user_agent TEXT,
    payload_singkat TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_aksi (aksi),
    INDEX idx_created_at (created_at)
);

-- Sessions table
CREATE TABLE sesi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip VARCHAR(45),
    user_agent TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expired_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES pengguna(id) ON DELETE CASCADE,
    INDEX idx_session_token (session_token),
    INDEX idx_user_id (user_id),
    INDEX idx_expired_at (expired_at)
);

-- Notifications table
CREATE TABLE notifikasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    judul VARCHAR(255) NOT NULL,
    isi TEXT,
    tipe ENUM('info', 'warning', 'error', 'success') DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES pengguna(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
);

-- MikroTik routers table
CREATE TABLE mikrotik_routers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    host VARCHAR(255) NOT NULL,
    port INT DEFAULT 8728,
    username VARCHAR(100) NOT NULL,
    password_enc VARCHAR(255) NOT NULL,
    use_tls TINYINT(1) DEFAULT 0,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
);

-- Settings table
CREATE TABLE settings (
    `key` VARCHAR(100) PRIMARY KEY,
    value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO settings (`key`, value) VALUES
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
