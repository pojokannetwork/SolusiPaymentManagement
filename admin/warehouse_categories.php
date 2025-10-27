<?php
$page_title = 'Gudang - Kategori';
require_once __DIR__ . '/../includes/admin_header.php';
$guard->requirePermission('admin.warehouse');
?>

<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Kategori Barang</h2>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" id="btn-add-category">
                <i class="fas fa-plus me-2"></i>Tambah Kategori
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0"><i class="fas fa-tags me-2"></i>Daftar Kategori</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle" id="tbl-categories">
                    <thead class="table-light">
                        <tr>
                            <th>Kode</th>
                            <th>Nama Kategori</th>
                            <th>Deskripsi</th>
                            <th>Diperbarui</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">Belum ada data kategori.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Category -->
<div class="modal fade" id="categoryModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Tambah Kategori</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="categoryForm">
      <div class="modal-body">
        <input type="hidden" id="cat-id">
        <div class="mb-3">
            <label class="form-label">Kode</label>
            <input type="text" class="form-control" id="cat-code" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Nama</label>
            <input type="text" class="form-control" id="cat-name" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Deskripsi</label>
            <textarea class="form-control" id="cat-desc" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary" id="cat-save-btn">
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
    const tbody=document.querySelector('#tbl-categories tbody');
    let modal, editingId = null;
    function showMsg(msg,type='info'){ alert(msg); }
    function render(rows){
        tbody.innerHTML='';
        if(!rows||rows.length===0){ tbody.innerHTML='<tr><td colspan="5" class="text-center py-4 text-muted">Belum ada data kategori.</td></tr>'; return; }
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
        fetchJSON('/api/admin/warehouse.php?action=list_categories').then(res=>{
            render(res.categories||[]);
            ensureDataTables(()=>{
                if(!$.fn.DataTable.isDataTable('#tbl-categories')){$('#tbl-categories').DataTable({responsive:true,pageLength:25,order:[]});}
                else{$('#tbl-categories').DataTable().destroy();$('#tbl-categories').DataTable({responsive:true,pageLength:25,order:[]});}
            });
        });
    }
    function openModal(row){
        editingId = row ? row.id : null;
        document.getElementById('modalTitle').textContent = row ? 'Edit Kategori' : 'Tambah Kategori';
        document.getElementById('cat-id').value = row?.id || '';
        document.getElementById('cat-code').value = row?.code || '';
        document.getElementById('cat-name').value = row?.name || '';
        document.getElementById('cat-desc').value = row?.description || '';
        modal = new bootstrap.Modal(document.getElementById('categoryModal'));
        modal.show();
    }
    document.getElementById('btn-add-category')?.addEventListener('click', ()=> openModal(null));
    tbody.addEventListener('click', function(e){
        const btn = e.target.closest('button'); if(!btn) return;
        const tr = btn.closest('tr'); const row = JSON.parse(tr.dataset.row||'{}');
        if(btn.classList.contains('btn-edit')){ openModal(row); }
        if(btn.classList.contains('btn-del')){
            if(confirm('Hapus kategori ini?')){
                fetch('/api/admin/warehouse.php?action=delete_category',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':'<?php echo getCsrfToken(); ?>'},body:JSON.stringify({id:row.id})})
                .then(r=>r.json()).then(res=>{ if(!res.success) throw new Error(res.message||'Gagal hapus'); load(); })
                .catch(err=>showMsg(err.message,'danger'));
            }
        }
    });
    document.getElementById('categoryForm').addEventListener('submit', function(e){
        e.preventDefault();
        const payload={ id: document.getElementById('cat-id').value, code: document.getElementById('cat-code').value.trim(), name: document.getElementById('cat-name').value.trim(), description: document.getElementById('cat-desc').value.trim() };
        const action = editingId ? 'update_category' : 'create_category';
        fetch('/api/admin/warehouse.php?action='+action,{ method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':'<?php echo getCsrfToken(); ?>'}, body:JSON.stringify(payload) })
            .then(r=>r.json()).then(res=>{ if(!res.success) throw new Error(res.message||'Gagal simpan'); modal.hide(); load(); })
            .catch(err=>showMsg(err.message,'danger'));
    });
    load();
});
</script>
EOT;
?>
<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
