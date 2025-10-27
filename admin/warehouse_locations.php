<?php
$page_title = 'Gudang - Lokasi';
require_once __DIR__ . '/../includes/admin_header.php';
$guard->requirePermission('admin.warehouse');
?>

<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Lokasi Penyimpanan</h2>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" id="btn-add-location">
                <i class="fas fa-plus me-2"></i>Tambah Lokasi
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Daftar Lokasi</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle" id="tbl-locations">
                    <thead class="table-light">
                        <tr>
                            <th>Kode</th>
                            <th>Nama Lokasi</th>
                            <th>Deskripsi</th>
                            <th>Diperbarui</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">Belum ada data lokasi.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Location -->
<div class="modal fade" id="locationModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="locModalTitle">Tambah Lokasi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="locationForm">
      <div class="modal-body">
        <input type="hidden" id="loc-id">
        <div class="mb-3">
            <label class="form-label">Kode</label>
            <input type="text" class="form-control" id="loc-code" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Nama</label>
            <input type="text" class="form-control" id="loc-name" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Deskripsi</label>
            <textarea class="form-control" id="loc-desc" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary" id="loc-save-btn">
            <i class="fas fa-save me-2"></i>Simpan
        </button>
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
    const tbody=document.querySelector('#tbl-locations tbody');
    let modal, editingId=null;
    function showMsg(m){ alert(m); }
    function render(rows){
        tbody.innerHTML='';
        if(!rows||rows.length===0){ tbody.innerHTML='<tr><td colspan="5" class="text-center py-4 text-muted">Belum ada data lokasi.</td></tr>'; return; }
        rows.forEach((r,idx)=>{
            const tr=document.createElement('tr');
            tr.innerHTML=`<td>${r.code}</td><td>${r.name}</td><td>${r.description||'-'}</td><td>${r.updated_at||'-'}</td>
            <td class=\"text-end\">
                <button class=\"btn btn-sm btn-outline-primary me-1 btn-edit\" data-index=\"${idx}\"><i class=\"fas fa-edit\"></i></button>
                <button class=\"btn btn-sm btn-outline-danger btn-del\" data-index=\"${idx}\"><i class=\"fas fa-trash\"></i></button>
            </td>`;
            tr.dataset.row = JSON.stringify(r);
            tbody.appendChild(tr);
        });
    }
    function load(){
        fetchJSON('/api/admin/warehouse.php?action=list_locations').then(res=>{
            render(res.locations||[]);
            ensureDataTables(()=>{
                if(!$.fn.DataTable.isDataTable('#tbl-locations')){$('#tbl-locations').DataTable({responsive:true,pageLength:25,order:[]});}
                else{$('#tbl-locations').DataTable().destroy();$('#tbl-locations').DataTable({responsive:true,pageLength:25,order:[]});}
            });
        });
    }
    function openModal(row){
        editingId = row ? row.id : null;
        document.getElementById('locModalTitle').textContent = row ? 'Edit Lokasi' : 'Tambah Lokasi';
        document.getElementById('loc-id').value = row?.id || '';
        document.getElementById('loc-code').value = row?.code || '';
        document.getElementById('loc-name').value = row?.name || '';
        document.getElementById('loc-desc').value = row?.description || '';
        modal = new bootstrap.Modal(document.getElementById('locationModal'));
        modal.show();
    }
    document.getElementById('btn-add-location')?.addEventListener('click', ()=> openModal(null));
    tbody.addEventListener('click', function(e){
        const btn=e.target.closest('button'); if(!btn) return; const tr=btn.closest('tr'); const row=JSON.parse(tr.dataset.row||'{}');
        if(btn.classList.contains('btn-edit')){ openModal(row); }
        if(btn.classList.contains('btn-del')){
            if(confirm('Hapus lokasi ini?')){
                fetch('/api/admin/warehouse.php?action=delete_location',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':'<?php echo getCsrfToken(); ?>'},body:JSON.stringify({id:row.id})})
                .then(r=>r.json()).then(res=>{ if(!res.success) throw new Error(res.message||'Gagal hapus'); load(); })
                .catch(err=>showMsg(err.message));
            }
        }
    });
    document.getElementById('locationForm').addEventListener('submit', function(e){
        e.preventDefault();
        const payload={ id: document.getElementById('loc-id').value, code: document.getElementById('loc-code').value.trim(), name: document.getElementById('loc-name').value.trim(), description: document.getElementById('loc-desc').value.trim() };
        const action = editingId ? 'update_location' : 'create_location';
        fetch('/api/admin/warehouse.php?action='+action,{ method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':'<?php echo getCsrfToken(); ?>'}, body:JSON.stringify(payload) })
            .then(r=>r.json()).then(res=>{ if(!res.success) throw new Error(res.message||'Gagal simpan'); modal.hide(); load(); })
            .catch(err=>showMsg(err.message));
    });
    load();
});
</script>
EOT;
?>
<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
