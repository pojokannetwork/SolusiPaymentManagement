<?php
$page_title = 'LUSI Assistant';
require_once __DIR__ . '/../includes/admin_header.php';
$guard->requirePermission('admin.customers');
?>

<div class="p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="fas fa-robot me-2"></i>LUSI Assistant</h2>
    <a href="/admin/settings.php#ai" class="btn btn-outline-primary"><i class="fas fa-cog me-2"></i>AI Settings</a>
  </div>

  <div class="card">
    <div class="card-body">
      <form id="lusiForm">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Customer ID (optional)</label>
            <input type="number" class="form-control" id="customer_id" placeholder="e.g. 123">
            <div class="form-text">Isi untuk otomatis ambil nomor WA pelanggan.</div>
          </div>
          <div class="col-md-4">
            <label class="form-label">WhatsApp Number (optional)</label>
            <input type="text" class="form-control" id="phone" placeholder="62xxxxxxxxxx">
          </div>
          <div class="col-md-4 d-flex align-items-end">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="send">
              <label class="form-check-label" for="send">Kirim via WhatsApp</label>
            </div>
          </div>
        </div>
        <div class="mt-3">
          <label class="form-label">Pertanyaan/Pesan</label>
          <textarea id="message" class="form-control" rows="4" placeholder="Tulis pesan untuk LUSI..."></textarea>
        </div>
        <div class="mt-3 d-flex gap-2">
          <button type="submit" class="btn btn-primary" id="sendBtn"><i class="fas fa-paper-plane me-2"></i>Tanya LUSI</button>
          <button type="button" class="btn btn-outline-secondary" id="statusBtn"><i class="fas fa-signal me-2"></i>Cek Status</button>
        </div>
      </form>
    </div>
  </div>

  <div class="row mt-4">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header"><strong>Jawaban LUSI</strong></div>
        <div class="card-body">
          <pre id="aiReply" class="mb-0" style="white-space: pre-wrap;"></pre>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header"><strong>Preview WhatsApp</strong></div>
        <div class="card-body text-center">
          <div id="waInfo" class="mb-2 text-muted"></div>
          <img id="waQR" alt="QR" style="max-width: 280px; display:none;" />
          <div class="mt-2">
            <a id="waLink" href="#" target="_blank" class="btn btn-success btn-sm" style="display:none;">
              <i class="fab fa-whatsapp me-1"></i>Buka WhatsApp
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
$(function(){
  $('#lusiForm').on('submit', function(e){ e.preventDefault(); runLusi(); });
  $('#statusBtn').on('click', fetchStatus);
});

function runLusi(){
  const payload = {
    customer_id: parseInt($('#customer_id').val() || '0') || null,
    phone: ($('#phone').val() || '').trim(),
    message: ($('#message').val() || '').trim(),
    send: $('#send').is(':checked')
  };
  if (!payload.message) { alert('Pesan tidak boleh kosong'); return; }

  const btn = $('#sendBtn');
  btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Memproses...');

  $.ajax({
    url: '/api/admin/lusi',
    method: 'POST',
    contentType: 'application/json',
    data: JSON.stringify(payload)
  })
  .done(function(res){
    $('#aiReply').text(res.ai_reply || '');
    const p = res.preview || {};
    $('#waInfo').text(p.phone ? ('+' + p.phone) : '');
    if (p.qr) { $('#waQR').attr('src', p.qr).show(); } else { $('#waQR').hide(); }
    if (p.link) { $('#waLink').attr('href', p.link).show(); } else { $('#waLink').hide(); }
  })
  .fail(function(xhr){
    alert(xhr.responseJSON?.message || 'Gagal memproses LUSI');
  })
  .always(function(){
    btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-2"></i>Tanya LUSI');
  });
}

function fetchStatus(){
  $.get('/api/admin/lusi')
    .done(function(res){
      const ok = res.ollama?.running ? 'ON' : 'OFF';
      alert('Ollama: ' + ok + '\nWA Pending: ' + (res.whatsapp?.summary?.pending || 0));
    })
    .fail(function(){ alert('Gagal cek status'); });
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
