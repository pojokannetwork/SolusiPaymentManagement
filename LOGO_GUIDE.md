# 📷 LOGO SYSTEM - SolusiPaymentManagement

## 🎯 Fitur Logo System

### ✅ **Yang Sudah Tersedia:**
- **Logo Settings Page** - Panel untuk mengelola logo
- **Multi-Format Support** - PNG, JPG, GIF, ICO, SVG
- **Responsive Logo** - Otomatis menyesuaikan ukuran layar
- **Theme Support** - Logo berbeda untuk dark/light theme
- **Default Logo** - SVG logo jika tidak ada upload
- **Easy Upload** - Drag & drop atau klik untuk upload

## 🖼️ Jenis Logo

| Jenis | Kegunaan | Format | Ukuran |
|-------|----------|--------|--------|
| **Logo Utama** | Background terang, sidebar desktop | PNG/JPG | 180px width |
| **Logo Putih** | Background gelap, sidebar | PNG (transparan) | 180px width |
| **Logo Kecil** | Mobile, compact view | PNG/JPG | 120px width |
| **Favicon** | Icon browser tab | ICO/PNG | 32x32px |

## 📍 Lokasi Logo

### 🔧 **Dimana Logo Muncul:**
- ✅ **Sidebar kiri** (semua halaman admin)
- ✅ **Mobile header** (saat sidebar tersembunyi) 
- ✅ **Browser tab** (favicon)
- ✅ **Login page** (jika ada)
- ✅ **Print layout** (dokumen)

### 🎨 **Responsive Behavior:**
- **Desktop**: Logo besar + teks company
- **Tablet**: Logo medium + teks
- **Mobile**: Logo kecil + teks ringkas
- **Print**: Logo sederhana hitam putih

## 🚀 Cara Menggunakan

### 1. **Akses Logo Settings**
```
URL: http://10.0.0.2:8888/admin/logo_settings.php
Menu: Admin → Settings → Logo Settings
```

### 2. **Upload Logo Baru**
1. Klik area upload atau drag & drop file
2. Pilih file gambar (max 2MB)
3. Logo otomatis tersimpan dan muncul di semua halaman
4. Refresh halaman untuk melihat perubahan

### 3. **Ganti Logo Existing**
1. Klik tombol "Ganti" pada logo yang ada
2. Pilih file baru
3. Logo lama otomatis terganti

### 4. **Hapus Logo**
1. Klik tombol "Hapus" 
2. Konfirmasi penghapusan
3. Sistem akan kembali ke default logo atau teks

## ⚙️ Konfigurasi Logo

### 📝 **Edit Pengaturan:**
File: `/includes/logo_config.php`

```php
$logo_config = [
    'company_name' => 'SolusiPaymentManagement',
    'company_tagline' => 'Smart Payment Solution',
    'show_logo' => true,        // Tampilkan gambar logo
    'show_text' => true,        // Tampilkan teks company
    'logo_width' => '180px',    // Lebar logo desktop
    'logo_width_mobile' => '120px', // Lebar logo mobile
    'use_text_only' => true,    // Fallback ke teks jika tidak ada logo
];
```

### 🎛️ **Panel Settings:**
- **Nama Perusahaan** - Teks yang muncul
- **Tagline** - Subtitle di bawah nama
- **Lebar Logo** - Ukuran desktop & mobile
- **Checkbox Options** - Show logo/text, fallback settings

## 📁 File Structure

```
/assets/images/logos/
├── logo.png              # Logo utama (upload user)
├── logo-white.png         # Logo putih (upload user) 
├── logo-small.png         # Logo kecil (upload user)
├── favicon.ico            # Favicon (upload user)
├── logo-default.svg       # Default logo (built-in)
├── logo-white-default.svg # Default white logo (built-in)
└── .gitkeep              # Keep directory

/includes/
├── logo_config.php        # Konfigurasi logo sistem
└── admin_header.php       # Template dengan logo

/assets/css/
├── logo.css              # Styling khusus logo
├── style.css             # Main styles
└── responsive.css        # Responsive styles

/api/admin/
└── logo_management.php   # API upload/delete logo

/admin/
└── logo_settings.php     # Panel kelola logo
```

## 🎨 Customization Guide

### 1. **Ganti Default Logo**
Edit file: `/assets/images/logos/logo-default.svg`
```svg
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 60">
  <!-- Your custom SVG logo here -->
  <text x="50" y="30" fill="white">Your Company</text>
</svg>
```

### 2. **Ubah Styling Logo**
Edit file: `/assets/css/logo.css`
```css
.logo-container {
    padding: 1rem;
    /* Your custom styles */
}

.logo-image {
    border-radius: 8px;
    /* Your custom styles */
}
```

### 3. **Tambah Logo di Halaman Baru**
```php
<?php require_once 'includes/logo_config.php'; ?>

<!-- Dalam template HTML -->
<?= renderLogo('light', 'normal') ?> <!-- Theme light, size normal -->
<?= renderLogo('dark', 'small') ?>   <!-- Theme dark, size small -->
```

## 🔐 Security Features

### ✅ **Keamanan Upload:**
- **File type validation** - Hanya gambar yang diizinkan
- **File size limit** - Maksimal 2MB per file
- **CSRF protection** - Token untuk semua request
- **Permission check** - Hanya admin yang bisa upload
- **Path traversal protection** - Upload hanya ke directory yang aman

### ✅ **File Safety:**
- Filename sanitization
- Extension validation  
- MIME type checking
- Overwrite protection
- Proper file permissions (644)

## 📱 Mobile Optimization

### 🎯 **Responsive Features:**
- **Auto-scaling** - Logo menyesuaikan screen size
- **Touch-friendly** - Upload area besar untuk touch
- **Compact view** - Logo kecil untuk mobile header
- **Bandwidth-friendly** - SVG default untuk loading cepat

### 📏 **Breakpoints:**
```css
/* Desktop */
@media (min-width: 992px) {
    .logo-image { width: 180px; }
}

/* Tablet */  
@media (min-width: 768px) {
    .logo-image { width: 140px; }
}

/* Mobile */
@media (max-width: 767px) {
    .logo-image { width: 120px; }
}
```

## 🚨 Troubleshooting

### ❌ **Upload Gagal?**
1. **Cek file size** - Maksimal 2MB
2. **Cek format** - Hanya PNG, JPG, GIF, ICO
3. **Cek permission** - Directory harus writable (755)
4. **Cek PHP settings** - `upload_max_filesize` & `post_max_size`

### ❌ **Logo Tidak Muncul?**
1. **Clear browser cache** - Ctrl+F5
2. **Cek file path** - Logo ada di `/assets/images/logos/`
3. **Cek CSS** - File `logo.css` ter-load
4. **Cek config** - `show_logo = true` di config

### ❌ **Logo Blur/Pixelated?**
1. **Upload resolusi tinggi** - Min 300px width
2. **Gunakan PNG** - Untuk transparansi dan kualitas
3. **Optimize file** - Compress sebelum upload

## 🎉 Features Summary

**✅ LOGO SYSTEM LENGKAP:**
- 🖼️ **Multi-logo support** (main, white, small, favicon)
- 📱 **Responsive** di semua device  
- 🎨 **Theme-aware** (dark/light mode)
- 🔒 **Security-first** upload system
- 🎛️ **Easy management** panel
- 📂 **Default logos** jika tidak ada upload
- 🎯 **Touch-friendly** interface

**Test Logo System:**
- Logo Settings: http://10.0.0.2:8888/admin/logo_settings.php
- Dashboard: http://10.0.0.2:8888/admin/dashboard.php
- Demo: http://10.0.0.2:8888/responsive-demo.html

---

**🎨 Logo sistem sudah terintegrasi sempurna dengan responsive theme!**