# Billing System Enhancement Guide

## 🎉 Billing System Enhancements Successfully Added!

Sistem billing telah berhasil diperluas dengan fitur-fitur advanced untuk mendukung pra bayar, pasca bayar, dan auto-isolir.

## 📋 Fitur yang Ditambahkan

### 1. Sistem Pembayaran Dual ✅
- **Postpaid (Pasca Bayar)**: Bayar di akhir periode (tagihan bulanan)
- **Prepaid (Pra Bayar)**: Bayar di awal periode (voucher/token system)

### 2. Manajemen Tanggal Aktivasi ✅
- **Tanggal Aktif**: Tanggal mulai layanan customer
- **Siklus Tagihan**: Tanggal generate tagihan bulanan (1, 2, 3, 5, 10, 15, 20, 25)
- **Grace Period**: Masa tenggang sebelum isolir (0-30 hari)

### 3. Auto-Isolir System ✅
- **Tanggal Isolir**: Tanggal otomatis isolir customer
- **Auto-Calculate**: Hitung otomatis tanggal isolir berdasarkan siklus + grace period
- **Manual Override**: Admin bisa set tanggal isolir manual

### 4. Billing Monitor Dashboard ✅
- **Real-time monitoring** customer overdue
- **Auto-isolir execution** untuk customer yang terlambat bayar
- **Bulk bill generation** untuk semua customer postpaid
- **Summary cards** untuk overview billing

## 🗃️ Database Schema Updates

Kolom baru ditambahkan ke tabel `pelanggan`:

```sql
-- Tanggal mulai aktif layanan
tanggal_aktif DATE

-- Sistem pembayaran: 'postpaid' atau 'prepaid' 
sistem_bayar TEXT DEFAULT 'postpaid'

-- Tanggal isolir otomatis
tanggal_isolir DATE

-- Siklus tagihan bulanan (tanggal 1-31)
cycle_billing INTEGER DEFAULT 1

-- Enable/disable auto isolir
auto_isolir INTEGER DEFAULT 1

-- Masa tenggang dalam hari
grace_period INTEGER DEFAULT 7
```

## 🚀 Cara Menggunakan

### For Admin - Customer Registration:

1. **Buka Admin → Customers → Add Customer**

2. **Pilih Sistem Pembayaran**:
   - **Postpaid**: Untuk customer tagihan bulanan
   - **Prepaid**: Untuk customer voucher/token

3. **Set Tanggal Aktif**: 
   - Default: Hari ini
   - Bisa diubah sesuai kebutuhan

4. **Untuk Postpaid**:
   - **Siklus Tagihan**: Pilih tanggal generate tagihan (contoh: tanggal 1 setiap bulan)
   - **Grace Period**: Masa tenggang sebelum isolir (default: 7 hari)
   - **Auto Isolir**: Centang untuk enable auto-isolir

5. **Tanggal Isolir**: Auto-calculate atau set manual

### For Admin - Billing Monitor:

1. **Buka Admin → Financial → Billing Monitor**

2. **Dashboard Overview**:
   - **Overdue Today**: Customer yang sudah melewati tanggal isolir
   - **Due This Week**: Customer yang akan jatuh tempo minggu ini
   - **Prepaid Active**: Customer prepaid yang aktif
   - **Auto Isolir Enabled**: Customer dengan auto-isolir aktif

3. **Actions Available**:
   - **Run Auto Isolir**: Isolir semua customer overdue otomatis
   - **Generate Bills**: Generate tagihan bulanan untuk semua customer postpaid
   - **Manual Actions**: Isolir manual, generate tagihan individual

## 📊 Business Logic

### Postpaid Flow:
```
Customer Register → Set Active Date → Calculate Bill Date → Generate Invoice → 
Due Date Passed → Grace Period → Auto Isolir (if enabled)
```

**Contoh Postpaid**:
- Tanggal Aktif: 15 Januari 2025
- Siklus Tagihan: Tanggal 1
- Grace Period: 7 hari
- **Bill Generate**: 1 Februari 2025
- **Due Date**: 1 Februari 2025
- **Isolir Date**: 8 Februari 2025 (1 Feb + 7 hari)

### Prepaid Flow:
```
Customer Register → Buy Voucher/Token → Activate Service → 
Usage Until Expired → Buy New Voucher
```

**Contoh Prepaid**:
- Customer beli voucher 1 bulan
- Service aktif sampai voucher habis
- Tidak ada tagihan bulanan
- Isolir otomatis saat voucher expired

## 🔧 Advanced Features

### 1. Auto-Isolir Scheduling
Bisa dijadwalkan via cron job:
```bash
# Jalankan setiap hari jam 1 pagi
0 1 * * * curl -X POST "https://yourdomain.com/api/admin/billing_monitor.php?action=auto_isolir"
```

### 2. Monthly Bill Generation
Auto-generate tagihan bulanan:
```bash
# Jalankan setiap tanggal 1, 2, 3, dst sesuai cycle_billing
0 2 * * * curl -X POST "https://yourdomain.com/api/admin/billing_monitor.php?action=generate_bills"
```

### 3. Flexible Grace Period
- **0 hari**: Isolir langsung setelah due date
- **7 hari**: Standard grace period
- **30 hari**: Extended grace untuk VIP customer

### 4. Multiple Billing Cycles
Support berbagai cycle billing:
- **Tanggal 1**: Untuk mayoritas customer
- **Tanggal 15**: Untuk customer corporate
- **Custom dates**: Fleksibel sesuai kebutuhan

## 📱 Customer Experience

### Postpaid Customer:
1. **Registration**: Pilih paket, set tanggal aktif
2. **Monthly Billing**: Dapat tagihan setiap bulan sesuai cycle
3. **Payment**: Bayar tagihan via payment gateway
4. **Grace Period**: Ada masa tenggang jika terlambat bayar
5. **Auto Service**: Service otomatis aktif setelah bayar

### Prepaid Customer:
1. **Registration**: Pilih paket prepaid
2. **Buy Voucher**: Beli voucher/token sesuai kebutuhan
3. **Instant Activation**: Service langsung aktif setelah bayar
4. **Usage Monitoring**: Monitor penggunaan voucher
5. **Recharge**: Beli voucher baru sebelum habis

## 🔍 Monitoring & Reports

### Billing Monitor Dashboard:
- **Real-time overview** status billing semua customer
- **Filter & Search** berdasarkan status, tanggal, tipe billing
- **Bulk actions** untuk efficiency management
- **Export reports** untuk accounting

### Customer Status Tracking:
- **Active**: Customer aktif dan up-to-date
- **Overdue**: Customer terlambat bayar tapi belum isolir
- **Isolir**: Customer sudah diisolir karena terlambat bayar
- **Suspended**: Customer disuspend manual oleh admin

## 💡 Best Practices

### 1. Setting Up Billing Cycles:
- **Tanggal 1**: Untuk B2C customer (residential)
- **Tanggal 15**: Untuk B2B customer (corporate)
- **Multiple dates**: Spread load untuk payment processing

### 2. Grace Period Management:
- **Residential**: 7-14 hari grace period
- **Corporate**: 30 hari grace period
- **VIP Customer**: Extended grace period

### 3. Auto-Isolir Strategy:
- **Enable** untuk customer baru dan risky customers
- **Disable** untuk VIP dan corporate customers
- **Manual monitoring** untuk special cases

### 4. Prepaid vs Postpaid Decision:
- **Postpaid**: Untuk customer dengan credit history baik
- **Prepaid**: Untuk customer baru atau high-risk
- **Hybrid**: Offer both options untuk flexibility

## 🚨 Important Notes

### Security & Compliance:
- ✅ **Data Protection**: Billing data encrypted dan secure
- ✅ **Audit Trail**: Semua billing actions ter-log
- ✅ **Access Control**: Role-based access untuk billing features
- ✅ **Backup**: Regular backup data billing

### Performance Optimization:
- ✅ **Indexed Queries**: Database queries optimized untuk performance
- ✅ **Bulk Operations**: Efficient bulk processing untuk thousands customers
- ✅ **Caching**: Smart caching untuk dashboard data
- ✅ **Async Processing**: Background jobs untuk heavy operations

## 📞 Technical Support

### API Endpoints:
- `GET /api/admin/billing_monitor.php?action=list` - List customers dengan billing info
- `GET /api/admin/billing_monitor.php?action=summary` - Billing overview summary
- `POST /api/admin/billing_monitor.php?action=auto_isolir` - Execute auto-isolir
- `POST /api/admin/billing_monitor.php?action=generate_bills` - Generate monthly bills

### Database Queries Examples:
```sql
-- Customer overdue today
SELECT * FROM pelanggan 
WHERE tanggal_isolir <= date('now') 
AND status = 'active' 
AND sistem_bayar = 'postpaid'

-- Generate bills for cycle 1
SELECT * FROM pelanggan 
WHERE sistem_bayar = 'postpaid' 
AND cycle_billing = 1
AND status IN ('active', 'isolir')
```

---

**Status**: ✅ **FULLY IMPLEMENTED & READY FOR USE**

Sistem billing telah sepenuhnya terintegrasi dengan payment gateway dan customer management. Ready untuk production use!