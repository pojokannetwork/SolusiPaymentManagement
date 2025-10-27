import React from 'react';
import ReactDOM from 'react-dom/client';

function SimpleApp() {
  return (
    <div style={{ padding: '20px', textAlign: 'center' }}>
      <h1>🚀 OLT Monitoring Dashboard</h1>
      <p>Test aplikasi berhasil!</p>
      <p>Backend API: <a href="http://localhost:3001/api/health" target="_blank" rel="noreferrer">http://localhost:3001/api/health</a></p>
    </div>
  );
}

const root = ReactDOM.createRoot(document.getElementById('root'));
root.render(<SimpleApp />);