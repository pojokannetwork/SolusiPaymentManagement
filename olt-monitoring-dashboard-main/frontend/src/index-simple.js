import React from 'react';
import ReactDOM from 'react-dom/client';

function SimpleApp() {
  return (
    <div style={{ padding: '20px', textAlign: 'center', fontFamily: 'Arial, sans-serif' }}>
      <h1 style={{ color: '#1976d2' }}>🚀 OLT Monitoring Dashboard</h1>
      <p style={{ fontSize: '18px', margin: '20px 0' }}>✅ Aplikasi frontend berhasil jalan!</p>
      <div style={{ background: '#f5f5f5', padding: '20px', borderRadius: '8px', margin: '20px 0' }}>
        <h3>Status Koneksi:</h3>
        <p>Backend API: <a href="http://localhost:3001/api/health" target="_blank" rel="noreferrer" style={{ color: '#1976d2' }}>http://localhost:3001/api/health</a></p>
        <p>Frontend Dashboard: <strong>http://localhost:3000</strong> ✅</p>
      </div>
      <div style={{ background: '#e3f2fd', padding: '15px', borderRadius: '8px', marginTop: '20px' }}>
        <p><strong>Langkah selanjutnya:</strong></p>
        <p>1. ✅ Backend sudah running</p>
        <p>2. ✅ Frontend sudah running</p>
        <p>3. 🔧 Setup Telegram bot di Settings</p>
        <p>4. 🌐 Tambah OLT device untuk monitoring</p>
      </div>
    </div>
  );
}

const root = ReactDOM.createRoot(document.getElementById('root'));
root.render(<SimpleApp />);