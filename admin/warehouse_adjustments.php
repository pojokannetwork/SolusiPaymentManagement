<?php
$page_title = 'Gudang - Penyesuaian';
require_once __DIR__ . '/../includes/admin_header.php';
$guard->requirePermission('admin.warehouse');
?>

<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Penyesuaian Stok</h2>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" id="btn-export" disabled>
                <i class="fas fa-file-export me-2"></i>Export
            </button>
            <button class="btn btn-primary" id="btn-add-adj">
                <i class="fas fa-plus me-2"></i>Tambah Penyesuaian
            </button>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <input type="text" id="search" class="form-control" placeholder="Cari no. dokumen / catatan">
                </div>
                <div class="col-md-3">
                    <input type="date" id="date_from" class="form-control">
                </div>
                <div class="col-md-3">
                    <input type="date" id="date_to" class="form-control">
                </div>
                <div class="col-md-3 text-md-end">
                    <button class="btn btn-outline-secondary" id="clear-filters">
                        <i class="fas fa-times me-2"></i> Bersihkan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0"><i class="fas fa-balance-scale me-2"></i>Daftar Penyesuaian</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle" id="tbl-adjustments">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>No. Dokumen</th>
                            <th>Item</th>
                            <th>Perubahan</th>
                            <th>Catatan</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Belum ada data penyesuaian.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Adjustment -->
<div class="modal fade" id="adjModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="adjModalTitle">Tambah Penyesuaian</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="adjForm">
      <div class="modal-body">
        <input type="hidden" id="adj-id">
        <div class="mb-3">
            <label class="form-label">Tanggal</label>
            <input type="date" class="form-control" id="adj-date" value="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">No. Dokumen</label>
            <input type="text" class="form-control" id="adj-docno" placeholder="Otomatis jika kosong">
        </div>
        <div class="mb-3">
            <label class="form-label">Item</label>
            <select class="form-select" id="adj-item"></select>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Perubahan (boleh negatif)</label>
                <input type="number" step="0.001" class="form-control" id="adj-change" value="0">
            </div>
            <div class="col-md-6">
                <label class="form-label">Catatan</label>
                <input type="text" class="form-control" id="adj-note">
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary" id="adj-save-btn"><i class="fas fa-save me-2"></i>Simpan</button>
      </div>
      </form>
    </div>
  </div>
 </div>

<?php $page_specific_scripts = <<<'EOT'
<script>
function ensureDataTables(cb){
    const cssId='dt-css'; if(!document.getElementById(cssId)){const l=document.createElement('link');l.id=cssId;l.rel='stylesheet';l.href='https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css';document.head.appendChild(l);}    
    function loadJS(src,id,done){ if(document.getElementById(id)) return done(); const s=document.createElement('script'); s.id=id; s.src=src; s.onload=done; document.body.appendChild(s); }
    loadJS('https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js','dt-js-core',()=>loadJS('https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js','dt-js-bs',cb));
}
function fetchJSON(u){ return fetch(u,{headers:{'X-CSRF-Token':'<?php echo getCsrfToken(); ?>'}}).then(r=>r.json()); }
document.addEventListener('DOMContentLoaded', ()=>{
    const tbody=document.querySelector('#tbl-adjustments tbody');
    let modal, editingId=null;
    function render(rows){
        tbody.innerHTML='';
        if(!rows||rows.length===0){ tbody.innerHTML='<tr><td colspan="6" class="text-center py-4 text-muted">Belum ada data penyesuaian.</td></tr>'; return; }
        rows.forEach((r,idx)=>{
            const tr=document.createElement('tr');
            tr.innerHTML=`<td>${r.date}</td><td>${r.doc_no}</td><td>${r.item||'-'}</td><td>${r.change||'-'}</td>
            <td>${r.note||'-'}</td>
            <td class=\"text-end\">
                <button class=\"btn btn-sm btn-outline-primary me-1 btn-edit\" data-index=\"${idx}\"><i class=\"fas fa-edit\"></i></button>
                ${String(r.status).toLowerCase()==='posted' ?
                  '<button class="btn btn-sm btn-outline-warning me-1 btn-unpost"><i class="fas fa-undo"></i></button>' :
                  '<button class="btn btn-sm btn-outline-success me-1 btn-post"><i class="fas fa-check"></i></button>'}
                <button class=\"btn btn-sm btn-outline-danger btn-del\" data-index=\"${idx}\"><i class=\"fas fa-trash\"></i></button>
            </td>`;
            tr.dataset.row = JSON.stringify(r);
            tbody.appendChild(tr);
        });
    }
    function load(){
        fetchJSON('/api/admin/warehouse.php?action=list_adjustments').then(res=>{
            render(res.adjustments||[]);
            ensureDataTables(()=>{
                if(!$.fn.DataTable.isDataTable('#tbl-adjustments')){$('#tbl-adjustments').DataTable({responsive:true,pageLength:25,order:[]});}
                else{$('#tbl-adjustments').DataTable().destroy();$('#tbl-adjustments').DataTable({responsive:true,pageLength:25,order:[]});}
            });
        });
    }
    function loadItemsIntoSelect(){
        fetchJSON('/api/admin/warehouse.php?action=item_lookup').then(res=>{
            const sel=document.getElementById('adj-item');
            sel.innerHTML = '<option value="">Pilih Item</option>' + (res.items||[]).map(it=>`<option value="${it.id}">${it.code} - ${it.name}</option>`).join('');
        });
    }
    function openModal(row){
        editingId = row ? row.id : null;
        document.getElementById('adjModalTitle').textContent = row ? 'Edit Penyesuaian' : 'Tambah Penyesuaian';
        document.getElementById('adj-id').value = row?.id || '';
        document.getElementById('adj-date').value = row?.date || '<?php echo date('Y-m-d'); ?>';
        document.getElementById('adj-docno').value = row?.doc_no || '';
        document.getElementById('adj-note').value = row?.note || '';
        loadItemsIntoSelect();
        modal = new bootstrap.Modal(document.getElementById('adjModal'));
        modal.show();
    }
    document.getElementById('btn-add-adj').addEventListener('click', ()=> openModal(null));
    document.getElementById('adjForm').addEventListener('submit', function(e){
        e.preventDefault();
        const payload={ id: document.getElementById('adj-id').value, date: document.getElementById('adj-date').value, doc_no: document.getElementById('adj-docno').value.trim(), item_id: parseInt(document.getElementById('adj-item').value||'0'), change_qty: parseFloat(document.getElementById('adj-change').value||'0'), note: document.getElementById('adj-note').value.trim() };
        const action = editingId ? 'update_adjustment' : 'create_adjustment';
        fetch('/api/admin/warehouse.php?action='+action,{ method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':'<?php echo getCsrfToken(); ?>'}, body: JSON.stringify(payload) })
          .then(r=>r.json()).then(res=>{ if(!res.success) throw new Error(res.message||'Gagal simpan'); modal.hide(); load(); })
          .catch(err=>alert(err.message));
    });
    tbody.addEventListener('click', function(e){
        const btn=e.target.closest('button'); if(!btn) return; const tr=btn.closest('tr'); const row=JSON.parse(tr.dataset.row||'{}');
        if(btn.classList.contains('btn-edit')) return openModal(row);
        if(btn.classList.contains('btn-del')){
            if(confirm('Hapus dokumen ini?')){
                fetch('/api/admin/warehouse.php?action=delete_adjustment',{ method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':'<?php echo getCsrfToken(); ?>'}, body: JSON.stringify({ id: row.id }) })
                .then(r=>r.json()).then(res=>{ if(!res.success) throw new Error(res.message||'Gagal hapus'); load(); })
                .catch(err=>alert(err.message));
            }
            return;
        }
        if(btn.classList.contains('btn-post')){
            if(confirm('Posting dokumen? Stok akan disesuaikan.')){
                fetch('/api/admin/warehouse.php?action=post_adjustment',{ method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':'<?php echo getCsrfToken(); ?>'}, body: JSON.stringify({ id: row.id }) })
                .then(r=>r.json()).then(res=>{ if(!res.success) throw new Error(res.message||'Gagal posting'); load(); })
                .catch(err=>alert(err.message));
            }
            return;
        }
        if(btn.classList.contains('btn-unpost')){
            if(confirm('Unpost dokumen? Stok akan dikembalikan.')){
                fetch('/api/admin/warehouse.php?action=unpost_adjustment',{ method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':'<?php echo getCsrfToken(); ?>'}, body: JSON.stringify({ id: row.id }) })
                .then(r=>r.json()).then(res=>{ if(!res.success) throw new Error(res.message||'Gagal unpost'); load(); })
                .catch(err=>alert(err.message));
            }
            return;
        }
    });
    document.getElementById('clear-filters')?.addEventListener('click', function(){ ['search','date_from','date_to'].forEach(id=>{const el=document.getElementById(id); if(el) el.value='';}); load(); });
    load();
});
</script>
EOT;
?>
<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
