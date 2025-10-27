export const translations = {
  en: {
    // Navigation
    navigation: {
      dashboard: 'Dashboard',
      olts: 'OLT Management',
      settings: 'Settings',
      telegram: 'Telegram Chat'
    },
    
    // Dashboard
    dashboard: {
      totalOlts: 'Total OLTs',
      onlineOlts: 'Online OLTs',
      totalOnts: 'Total ONTs',
      onlineOnts: 'Online ONTs',
      offlineOnts: 'Offline ONTs',
      losOnts: 'LOS ONTs',
      recentEvents: 'Recent Events',
      powerDistribution: 'Power Distribution'
    },
    
    // OLT Management
    olt: {
      addNew: 'Add New OLT',
      edit: 'Edit OLT',
      name: 'OLT Name',
      ipAddress: 'IP Address',
      username: 'Username',
      password: 'Password',
      type: 'OLT Type',
      connectionMethod: 'Connection Method',
      testConnection: 'Test Connection',
      snmpConfiguration: 'SNMP Configuration',
      snmpCommunity: 'SNMP Community',
      snmpVersion: 'SNMP Version',
      snmpv3ReadUser: 'SNMPv3 Read User',
      snmpv3WriteUser: 'SNMPv3 Write User',
      snmpv3Trap: 'SNMPv3 Trap User',
      snmpAuthProtocol: 'Authentication Protocol',
      snmpAuthPassword: 'Authentication Password',
      snmpPrivProtocol: 'Privacy Protocol',
      snmpPrivPassword: 'Privacy Password',
      snmpPort: 'SNMP Port'
    },
    
    // Settings
    settings: {
      telegramBot: 'Telegram Bot',
      users: 'Users',
      thresholds: 'Thresholds',
      system: 'System',
      language: 'Language',
      mikrotikIntegration: 'Mikrotik Integration',
      enableMikrotik: 'Enable Mikrotik Bot',
      mikrotikDescription: 'Integrate with Mikrotik RouterOS for network monitoring',
      mikrotikHost: 'Mikrotik Host/IP',
      mikrotikHostHelper: 'IP address or hostname of Mikrotik router',
      mikrotikPort: 'API Port',
      mikrotikUsername: 'Username',
      mikrotikUsernameHelper: 'Mikrotik API user with read permissions',
      mikrotikPassword: 'Password',
      mikrotikPasswordHelper: 'Password for Mikrotik API user',
      systemName: 'System Name',
      systemNameHelper: 'System name for notifications',
      pollingInterval: 'Polling Interval (ms)',
      pollingIntervalHelper: 'OLT polling interval (minimum 10 seconds)',
      telegramInstructions: '1. Create a new bot with @BotFather on Telegram\\n2. Copy bot token and paste in Bot Token field\\n3. Start chat with your bot and send any message\\n4. Click Test Bot to verify connection',
      saved: 'Settings saved successfully',
      errorSaving: 'Error saving settings'
    },
    
    // Common
    common: {
      save: 'Save',
      cancel: 'Cancel',
      status: 'Status',
      online: 'Online',
      offline: 'Offline',
      loading: 'Loading...',
      error: 'Error',
      success: 'Success',
      delete: 'Delete',
      edit: 'Edit',
      add: 'Add',
      close: 'Close',
      confirm: 'Confirm'
    }
  },
  
  id: {
    // Navigation
    navigation: {
      dashboard: 'Dashboard',
      olts: 'Manajemen OLT',
      settings: 'Pengaturan',
      telegram: 'Chat Telegram'
    },
    
    // Dashboard
    dashboard: {
      totalOlts: 'Total OLT',
      onlineOlts: 'OLT Online',
      totalOnts: 'Total ONT',
      onlineOnts: 'ONT Online',
      offlineOnts: 'ONT Offline',
      losOnts: 'ONT LOS',
      recentEvents: 'Event Terbaru',
      powerDistribution: 'Distribusi Power'
    },
    
    // OLT Management
    olt: {
      addNew: 'Tambah OLT Baru',
      edit: 'Edit OLT',
      name: 'Nama OLT',
      ipAddress: 'Alamat IP',
      username: 'Username',
      password: 'Password',
      type: 'Tipe OLT',
      connectionMethod: 'Metode Koneksi',
      testConnection: 'Test Koneksi',
      snmpConfiguration: 'Konfigurasi SNMP',
      snmpCommunity: 'SNMP Community',
      snmpVersion: 'Versi SNMP',
      snmpv3ReadUser: 'SNMPv3 Read User',
      snmpv3WriteUser: 'SNMPv3 Write User',
      snmpv3Trap: 'SNMPv3 Trap User',
      snmpAuthProtocol: 'Protokol Autentikasi',
      snmpAuthPassword: 'Password Autentikasi',
      snmpPrivProtocol: 'Protokol Privasi',
      snmpPrivPassword: 'Password Privasi',
      snmpPort: 'Port SNMP'
    },
    
    // Settings
    settings: {
      telegramBot: 'Bot Telegram',
      users: 'Pengguna',
      thresholds: 'Threshold',
      system: 'Sistem',
      language: 'Bahasa',
      mikrotikIntegration: 'Integrasi Mikrotik',
      enableMikrotik: 'Aktifkan Bot Mikrotik',
      mikrotikDescription: 'Integrasi dengan Mikrotik RouterOS untuk monitoring jaringan',
      mikrotikHost: 'Host/IP Mikrotik',
      mikrotikHostHelper: 'Alamat IP atau hostname router Mikrotik',
      mikrotikPort: 'Port API',
      mikrotikUsername: 'Username',
      mikrotikUsernameHelper: 'User API Mikrotik dengan permission read',
      mikrotikPassword: 'Password',
      mikrotikPasswordHelper: 'Password untuk user API Mikrotik',
      systemName: 'Nama Sistem',
      systemNameHelper: 'Nama sistem untuk notifikasi',
      pollingInterval: 'Interval Polling (ms)',
      pollingIntervalHelper: 'Interval polling OLT (minimum 10 detik)',
      telegramInstructions: '1. Buat bot baru dengan @BotFather di Telegram\\n2. Copy token bot dan paste di field Bot Token\\n3. Start chat dengan bot Anda dan kirim pesan apa saja\\n4. Klik Test Bot untuk verifikasi koneksi',
      saved: 'Pengaturan berhasil disimpan',
      errorSaving: 'Error menyimpan pengaturan'
    },
    
    // Common
    common: {
      save: 'Simpan',
      cancel: 'Batal',
      status: 'Status',
      online: 'Online',
      offline: 'Offline',
      loading: 'Memuat...',
      error: 'Kesalahan',
      success: 'Berhasil',
      delete: 'Hapus',
      edit: 'Edit',
      add: 'Tambah',
      close: 'Tutup',
      confirm: 'Konfirmasi'
    }
  }
};

// Hook untuk menggunakan translations
import { useState, useEffect } from 'react';

export function useTranslation() {
  const [language, setLanguageState] = useState(() => {
    return localStorage.getItem('language') || 'id';
  });

  const setLanguage = (newLanguage) => {
    setLanguageState(newLanguage);
    localStorage.setItem('language', newLanguage);
  };

  const t = (key) => {
    const keys = key.split('.');
    let value = translations[language];
    
    for (const k of keys) {
      if (value && typeof value === 'object') {
        value = value[k];
      } else {
        return key; // Return key if translation not found
      }
    }
    
    return value || key;
  };

  return { t, language, setLanguage };
}