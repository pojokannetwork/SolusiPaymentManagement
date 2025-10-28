<?php
$page_title = 'WhatsApp Gateway (QR)';
require_once __DIR__ . '/../includes/admin_header.php';
$guard->requirePermission('admin.customers');
?>

<div class="p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="fab fa-whatsapp me-2 text-success"></i>WhatsApp Gateway (QR)</h2>
    <div>
      <button class="btn btn-outline-secondary btn-sm" onclick="refreshStatus()"><i class="fas fa-rotate me-1"></i>Refresh</button>
      <button class="btn btn-outline-danger btn-sm" onclick="logoutGateway()"><i class="fas fa-sign-out-alt me-1"></i>Logout</button>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header"><strong>Status</strong></div>
        <div class="card-body">
          <div class="d-flex align-items-center gap-3">
            <span id="statusBadge" class="badge bg-secondary">checking...</span>
            <div id="infoText" class="text-muted"></div>
          </div>
          <small class="text-muted">Scan QR dengan WhatsApp di ponsel Anda: Menu > Perangkat Tertaut > Tautkan perangkat.</small>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header"><strong>QR Pairing</strong></div>
        <div class="card-body text-center">
          <img id="qrImg" src="" alt="QR" style="max-width: 320px; display:none;" />
          <div id="qrHint" class="text-muted">QR akan muncul saat gateway siap dipindai.</div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const base = window.location.protocol + '//' + window.location.hostname + ':3001';
let poller = null;

function setStatus(status, info){
  const badge = document.getElementById('statusBadge');
  const infoEl = document.getElementById('infoText');
  const qrImg = document.getElementById('qrImg');
  const qrHint = document.getElementById('qrHint');
  let cls = 'bg-secondary';
  if (status === 'ready') cls = 'bg-success';
  else if (status === 'qr' || status === 'connecting') cls = 'bg-info';
  else if (status === 'error') cls = 'bg-danger';
  else if (status === 'disconnected') cls = 'bg-warning';
  badge.className = 'badge ' + cls;
  badge.innerText = status;

  infoEl.innerText = info || '';

  if (status === 'qr' && window._lastQR) {
    qrImg.src = window._lastQR; qrImg.style.display = 'inline-block'; qrHint.style.display = 'none';
  } else {
    qrImg.style.display = 'none'; qrHint.style.display = 'block';
  }
}

async function refreshStatus(){
  try {
    const res = await fetch(base + '/status');
    const data = await res.json();
    window._lastQR = data.qr || null;
    const info = data.info && (data.info.pushname || data.info.wid) ? (data.info.pushname + ' (' + data.info.wid + ')') : '';
    setStatus(data.status, info);
  } catch {
    setStatus('error', 'Tidak dapat terhubung ke gateway');
  }
}

async function logoutGateway(){
  if(!confirm('Logout dari WhatsApp di gateway ini?')) return;
  try { await fetch(base + '/logout', { method: 'POST' }); } catch {}
  setTimeout(refreshStatus, 800);
}

function startPolling(){
  if (poller) clearInterval(poller);
  poller = setInterval(refreshStatus, 2000);
  refreshStatus();
}

document.addEventListener('DOMContentLoaded', startPolling);
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
