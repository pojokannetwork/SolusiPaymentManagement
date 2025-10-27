<?php
$page_title = 'Gudang - Penerimaan';
require_once __DIR__ . '/../includes/admin_header.php';
$guard->requirePermission('admin.warehouse');
?>

<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Penerimaan Barang</h2>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" id="btn-export" disabled>
                <i class="fas fa-file-export me-2"></i>Export
            </button>
            <button class="btn btn-primary" id="btn-add-receipt">
                <i class="fas fa-plus me-2"></i>Tambah Penerimaan
            </button>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <input type="text" id="search" class="form-control" placeholder="Cari no. dokumen / supplier">
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
        <div class="card-header"><h5 class="mb-0"><i class="fas fa-inbox me-2"></i>Daftar Penerimaan</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle" id="tbl-receipts">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>No. Dokumen</th>
                            <th>Supplier</th>
                            <th>Jumlah Item</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Belum ada data penerimaan.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Receipt -->
<div class="modal fade" id="receiptModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="rcpModalTitle">Tambah Penerimaan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="receiptForm">
      <div class="modal-body">
        <input type="hidden" id="rcp-id">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">Tanggal</label>
                <input type="date" class="form-control" id="rcp-date" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">No. Dokumen</label>
                <input type="text" class="form-control" id="rcp-docno" placeholder="Otomatis jika kosong">
            </div>
            <div class="col-md-4">
                <label class="form-label">Supplier</label>
                <input type="text" class="form-control" id="rcp-supplier">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table align-middle" id="tbl-lines">
                <thead class="table-light">
                    <tr>
                        <th style="width:28%">Item</th>
                        <th style="width:16%">Qty</th>
                        <th style="width:16%">Satuan</th>
                        <th>Catatan</th>
                        <th style="width:8%"></th>
                    </tr>
                </thead>
                <tbody id="lines-tbody"></tbody>
            </table>
        </div>
        <button type="button" class="btn btn-outline-secondary" id="btn-add-line"><i class="fas fa-plus me-2"></i>Tambah Baris</button>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary" id="rcp-save-btn"><i class="fas fa-save me-2"></i>Simpan</button>
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
    const tbody=document.querySelector('#tbl-receipts tbody');
    let modal, editingId=null;
    function render(rows){
        tbody.innerHTML='';
        if(!rows||rows.length===0){ tbody.innerHTML='<tr><td colspan="6" class="text-center py-4 text-muted">Belum ada data penerimaan.</td></tr>'; return; }
        rows.forEach((r,idx)=>{
            const tr=document.createElement('tr');
            tr.innerHTML=`<td>${r.date}</td><td>${r.doc_no}</td><td>${r.supplier||'-'}</td><td>${r.items||0}</td><td>${r.status||'-'}</td>
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
        fetchJSON('/api/admin/warehouse.php?action=list_receipts').then(res=>{
            render(res.receipts||[]);
            ensureDataTables(()=>{
                if(!$.fn.DataTable.isDataTable('#tbl-receipts')){$('#tbl-receipts').DataTable({responsive:true,pageLength:25,order:[]});}
                else{$('#tbl-receipts').DataTable().destroy();$('#tbl-receipts').DataTable({responsive:true,pageLength:25,order:[]});}
            });
        });
    }
    function lineRow(data){
        const tr=document.createElement('tr');
        tr.innerHTML=`
            <td><select class="form-select line-item"></select></td>
            <td><input type="number" step="0.001" class="form-control line-qty" value="${data?.qty||1}"></td>
            <td><input type="text" class="form-control line-unit" value="${data?.unit||'pcs'}"></td>
            <td><input type="text" class="form-control line-note" value="${data?.note||''}"></td>
            <td class="text-end"><button class="btn btn-sm btn-outline-danger btn-remove-line"><i class="fas fa-trash"></i></button></td>
        `;
        fetchJSON('/api/admin/warehouse.php?action=item_lookup').then(res=>{
            const sel=tr.querySelector('.line-item');
            sel.innerHTML = '<option value="">Pilih Item</option>' + (res.items||[]).map(it=>`<option value="${it.id}">${it.code} - ${it.name}</option>`).join('');
            if(data?.item_id){ sel.value = String(data.item_id); }
        });
        return tr;
    }
    function openModal(row){
        editingId = row ? row.id : null;
        document.getElementById('rcpModalTitle').textContent = row ? 'Edit Penerimaan' : 'Tambah Penerimaan';
        document.getElementById('rcp-id').value = row?.id || '';
        document.getElementById('rcp-date').value = row?.date || '<?php echo date('Y-m-d'); ?>';
        document.getElementById('rcp-docno').value = row?.doc_no || '';
        document.getElementById('rcp-supplier').value = row?.supplier || '';
        const tbodyLines = document.getElementById('lines-tbody');
        tbodyLines.innerHTML='';
        if(row){
            fetchJSON('/api/admin/warehouse.php?action=get_receipt&id='+encodeURIComponent(row.id)).then(r=>{
                const items = r.data?.receipt?.items || [];
                items.forEach(it=>{ tbodyLines.appendChild(lineRow({ item_id: it.item_id, qty: it.qty, unit: it.unit, note: it.note })); });
                if(items.length===0) tbodyLines.appendChild(lineRow({}));
            });
        } else { tbodyLines.appendChild(lineRow({})); }
        modal = new bootstrap.Modal(document.getElementById('receiptModal'));
        modal.show();
    }
    document.getElementById('btn-add-receipt').addEventListener('click', ()=> openModal(null));
    document.getElementById('btn-add-line').addEventListener('click', ()=>{
        document.getElementById('lines-tbody').appendChild(lineRow({}));
    });
    document.getElementById('lines-tbody').addEventListener('click', function(e){
        const btn=e.target.closest('button'); if(!btn) return; if(btn.classList.contains('btn-remove-line')){ btn.closest('tr').remove(); }
    });
    document.getElementById('receiptForm').addEventListener('submit', function(e){
        e.preventDefault();
        const payload={ id: document.getElementById('rcp-id').value, date: document.getElementById('rcp-date').value, doc_no: document.getElementById('rcp-docno').value.trim(), supplier: document.getElementById('rcp-supplier').value.trim(), items: [] };
        document.querySelectorAll('#lines-tbody tr').forEach(tr=>{
            const item_id = parseInt(tr.querySelector('.line-item').value||'0');
            const qty = parseFloat(tr.querySelector('.line-qty').value||'0');
            const unit = tr.querySelector('.line-unit').value||'pcs';
            const note = tr.querySelector('.line-note').value||'';
            if(item_id>0 && qty>0){ payload.items.push({ item_id, qty, unit, note }); }
        });
        const action = editingId ? 'update_receipt' : 'create_receipt';
        fetch('/api/admin/warehouse.php?action='+action,{ method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':'<?php echo getCsrfToken(); ?>'}, body: JSON.stringify(payload) })
          .then(r=>r.json()).then(res=>{ if(!res.success) throw new Error(res.message||'Gagal simpan'); modal.hide(); load(); })
          .catch(err=>alert(err.message));
    });
    tbody.addEventListener('click', function(e){
        const btn = e.target.closest('button'); if(!btn) return; const tr=btn.closest('tr'); const row=JSON.parse(tr.dataset.row||'{}');
        if(btn.classList.contains('btn-edit')) return openModal(row);
        if(btn.classList.contains('btn-del')){
            if(confirm('Hapus dokumen ini?')){
                fetch('/api/admin/warehouse.php?action=delete_receipt',{ method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':'<?php echo getCsrfToken(); ?>'}, body: JSON.stringify({ id: row.id }) })
                .then(r=>r.json()).then(res=>{ if(!res.success) throw new Error(res.message||'Gagal hapus'); load(); })
                .catch(err=>alert(err.message));
            }
            return;
        }
        if(btn.classList.contains('btn-post')){
            if(confirm('Posting dokumen? Stok akan bertambah.')){
                fetch('/api/admin/warehouse.php?action=post_receipt',{ method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':'<?php echo getCsrfToken(); ?>'}, body: JSON.stringify({ id: row.id }) })
                .then(r=>r.json()).then(res=>{ if(!res.success) throw new Error(res.message||'Gagal posting'); load(); })
                .catch(err=>alert(err.message));
            }
            return;
        }
        if(btn.classList.contains('btn-unpost')){
            if(confirm('Unpost dokumen? Stok akan berkurang.')){
                fetch('/api/admin/warehouse.php?action=unpost_receipt',{ method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':'<?php echo getCsrfToken(); ?>'}, body: JSON.stringify({ id: row.id }) })
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
