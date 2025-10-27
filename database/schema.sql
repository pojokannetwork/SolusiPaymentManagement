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

-- ISP Packages
CREATE TABLE isp_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    speed VARCHAR(100) NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    tax_rate DECIMAL(5,2) DEFAULT 11.00,
    description TEXT,
    pppoe_profile VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    status ENUM('active','inactive') DEFAULT 'active',
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_is_active (is_active)
);

-- Voucher pricing and generation tables
CREATE TABLE isp_voucher_pricing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    package_id INT NOT NULL,
    duration_hours INT NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    agent_price DECIMAL(12,2) DEFAULT 0,
    commission DECIMAL(12,2) DEFAULT 0,
    hotspot_profile VARCHAR(100),
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_package_duration (package_id, duration_hours),
    FOREIGN KEY (package_id) REFERENCES isp_packages(id) ON DELETE CASCADE
);

CREATE TABLE isp_voucher_generation_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE isp_voucher_online_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    package_code VARCHAR(50) NOT NULL UNIQUE,
    profile VARCHAR(100) NOT NULL,
    enabled TINYINT(1) DEFAULT 1,
    display_name VARCHAR(150),
    description TEXT,
    price DECIMAL(12,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (package_code) REFERENCES isp_packages(code) ON DELETE CASCADE
);

CREATE TABLE isp_voucher_purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(150) NOT NULL,
    customer_phone VARCHAR(30) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    description TEXT,
    purchase_type ENUM('voucher','manual') DEFAULT 'voucher',
    voucher_package VARCHAR(50) NOT NULL,
    voucher_quantity INT DEFAULT 1,
    voucher_profile VARCHAR(100) NOT NULL,
    voucher_payload JSON,
    status ENUM('pending','completed','failed','cancelled') DEFAULT 'pending',
    payment_gateway VARCHAR(100),
    payment_transaction_id VARCHAR(100),
    payment_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_voucher_phone (customer_phone),
    INDEX idx_voucher_status (status),
    INDEX idx_voucher_package (voucher_package)
);

CREATE TABLE isp_voucher_delivery_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    voucher_purchase_id INT NOT NULL,
    delivery_channel ENUM('whatsapp','email','sms','manual') DEFAULT 'whatsapp',
    recipient VARCHAR(150) NOT NULL,
    status ENUM('pending','sent','failed') DEFAULT 'pending',
    response_message TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    FOREIGN KEY (voucher_purchase_id) REFERENCES isp_voucher_purchases(id) ON DELETE CASCADE,
    INDEX idx_voucher_delivery_status (status)
);

-- Extend customers with package metadata
ALTER TABLE pelanggan
    ADD COLUMN package_id INT NULL AFTER paket,
    ADD COLUMN billing_day TINYINT DEFAULT 15,
    ADD COLUMN auto_suspension TINYINT(1) DEFAULT 1,
    ADD COLUMN cable_type VARCHAR(100) NULL,
    ADD COLUMN cable_length DECIMAL(10,2) NULL,
    ADD COLUMN port_number INT NULL,
    ADD COLUMN cable_status ENUM('connected','disconnected','maintenance','damaged') DEFAULT 'connected',
    ADD COLUMN cable_notes TEXT NULL,
    ADD COLUMN mac_address VARCHAR(50) NULL,
  ADD COLUMN assigned_ip VARCHAR(45) NULL,
  ADD CONSTRAINT fk_pelanggan_package FOREIGN KEY (package_id) REFERENCES isp_packages(id);


-- Insert default roles

-- Warehouse / Inventory tables
CREATE TABLE IF NOT EXISTS inventory_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
);

CREATE TABLE IF NOT EXISTS inventory_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
);

CREATE TABLE IF NOT EXISTS inventory_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(200) NOT NULL,
    category_id INT NULL,
    unit VARCHAR(20) DEFAULT 'pcs',
    stock_qty DECIMAL(15,3) DEFAULT 0,
    location_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES inventory_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (location_id) REFERENCES inventory_locations(id) ON DELETE SET NULL,
    INDEX idx_name (name),
    INDEX idx_category (category_id),
    INDEX idx_location (location_id)
);

CREATE TABLE IF NOT EXISTS inventory_receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doc_no VARCHAR(50) NOT NULL UNIQUE,
    date DATE NOT NULL,
    supplier VARCHAR(200),
    status ENUM('Draft','Posted','Cancelled') DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date (date),
    INDEX idx_status (status)
);

CREATE TABLE IF NOT EXISTS inventory_receipt_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_id INT NOT NULL,
    item_id INT NOT NULL,
    qty DECIMAL(15,3) NOT NULL,
    unit VARCHAR(20) DEFAULT 'pcs',
    note VARCHAR(255),
    FOREIGN KEY (receipt_id) REFERENCES inventory_receipts(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE RESTRICT,
    INDEX idx_receipt (receipt_id),
    INDEX idx_item (item_id)
);

CREATE TABLE IF NOT EXISTS inventory_issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doc_no VARCHAR(50) NOT NULL UNIQUE,
    date DATE NOT NULL,
    target VARCHAR(200),
    status ENUM('Draft','Posted','Cancelled') DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date (date),
    INDEX idx_status (status)
);

CREATE TABLE IF NOT EXISTS inventory_issue_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    issue_id INT NOT NULL,
    item_id INT NOT NULL,
    qty DECIMAL(15,3) NOT NULL,
    unit VARCHAR(20) DEFAULT 'pcs',
    note VARCHAR(255),
    FOREIGN KEY (issue_id) REFERENCES inventory_issues(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE RESTRICT,
    INDEX idx_issue (issue_id),
    INDEX idx_item (item_id)
);

CREATE TABLE IF NOT EXISTS inventory_adjustments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doc_no VARCHAR(50) NOT NULL UNIQUE,
    date DATE NOT NULL,
    item_id INT NOT NULL,
    change_qty DECIMAL(15,3) NOT NULL,
    note VARCHAR(255),
    status ENUM('Draft','Posted','Cancelled') DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE RESTRICT,
    INDEX idx_date (date),
    INDEX idx_status (status)
);

-- Agent System
CREATE TABLE isp_agents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    parent_agent_id INT NULL,
    balance DECIMAL(12,2) DEFAULT 0,
    commission_rate DECIMAL(5,2) DEFAULT 0,
    status ENUM('active','inactive','suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_agent_id) REFERENCES isp_agents(id) ON DELETE SET NULL,
    INDEX idx_agent_status (status)
);

CREATE TABLE isp_agent_balance_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    notes TEXT,
    processed_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (agent_id) REFERENCES isp_agents(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ODP/ONU Network Infrastructure
CREATE TABLE isp_odp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    address TEXT,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    total_ports INT NOT NULL,
    available_ports INT NOT NULL,
    status ENUM('active','inactive','full','maintenance') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_odp_status (status)
);

CREATE TABLE isp_onu_devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    serial_number VARCHAR(100) NOT NULL UNIQUE,
    model VARCHAR(100),
    status ENUM('available','in_use','defective','maintenance') DEFAULT 'available',
    customer_id INT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES pelanggan(id) ON DELETE SET NULL,
    INDEX idx_onu_status (status)
);

CREATE TABLE isp_odp_connections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    odp_id INT NOT NULL,
    customer_id INT NOT NULL,
    port_number INT NOT NULL,
    onu_id INT NULL,
    cable_type VARCHAR(100),
    cable_length DECIMAL(10,2),
    status ENUM('connected','disconnected','maintenance') DEFAULT 'connected',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_odp_port (odp_id, port_number),
    FOREIGN KEY (odp_id) REFERENCES isp_odp(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES pelanggan(id) ON DELETE CASCADE,
    FOREIGN KEY (onu_id) REFERENCES isp_onu_devices(id) ON DELETE SET NULL
);

-- Financials
CREATE TABLE isp_expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    expense_date DATE NOT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE isp_monthly_summary (
    id INT AUTO_INCREMENT PRIMARY KEY,
    month INT NOT NULL,
    year INT NOT NULL,
    total_revenue DECIMAL(15,2) NOT NULL,
    total_expenses DECIMAL(15,2) NOT NULL,
    net_income DECIMAL(15,2) NOT NULL,
    active_customers INT NOT NULL,
    new_customers INT NOT NULL,
    churned_customers INT NOT NULL,
    total_invoices INT NOT NULL,
    paid_invoices INT NOT NULL,
    unpaid_invoices INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_month_year (month, year)
);


-- Extend customers with ODP/ONU details
ALTER TABLE pelanggan
    ADD COLUMN odp_id INT NULL AFTER assigned_ip,
    ADD COLUMN onu_id INT NULL AFTER odp_id,
    ADD CONSTRAINT fk_pelanggan_odp FOREIGN KEY (odp_id) REFERENCES isp_odp(id),
    ADD CONSTRAINT fk_pelanggan_onu FOREIGN KEY (onu_id) REFERENCES isp_onu_devices(id);

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
('admin.packages', 'Manage ISP Packages'),
('admin.vouchers', 'Manage ISP Vouchers'),
('admin.agents', 'Manage ISP Agents'),
('admin.odp', 'Manage ODP'),
('admin.onu', 'Manage ONU'),
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
