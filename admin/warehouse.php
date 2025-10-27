<?php
$page_title = 'Stok Gudang';
require_once __DIR__ . '/../includes/admin_header.php';
// Permission check: only roles with admin.warehouse (admin role passes) can access
$guard->requirePermission('admin.warehouse');
?>

<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Gudang / Stok Gudang</h2>
            <p class="text-muted mb-0">Kelola persediaan barang, lokasi penyimpanan, dan pergerakan stok.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" id="inv-export" disabled>
                <i class="fas fa-file-export me-2"></i>Export
            </button>
            <button class="btn btn-primary" id="btn-add-item">
                <i class="fas fa-plus me-2"></i>Tambah Barang
            </button>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" id="inv-search" placeholder="Cari nama/kode barang">
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="inv-category">
                        <option value="">Semua Kategori</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="inv-location">
                        <option value="">Semua Lokasi</option>
                    </select>
                </div>
                <div class="col-md-3 text-md-end">
                    <button class="btn btn-outline-secondary" id="inv-clear">
                        <i class="fas fa-times me-2"></i>Bersihkan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-boxes me-2"></i>Daftar Stok</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle" id="inventory-table">
                    <thead class="table-light">
                        <tr>
                            <th>Kode</th>
                            <th>Nama Barang</th>
                            <th>Kategori</th>
                            <th>Stok</th>
                            <th>Satuan</th>
                            <th>Lokasi</th>
                            <th>Diperbarui</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">Modul gudang belum aktif. Konten akan ditambahkan kemudian.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Item -->
<div class="modal fade" id="itemModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="itemModalTitle">Tambah Barang</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="itemForm">
      <div class="modal-body">
        <input type="hidden" id="item-id">
        <div class="mb-3">
            <label class="form-label">Kode</label>
            <input type="text" class="form-control" id="item-code" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Nama</label>
            <input type="text" class="form-control" id="item-name" required>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Kategori</label>
                <select class="form-select" id="item-category"></select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Lokasi</label>
                <select class="form-select" id="item-location"></select>
            </div>
        </div>
        <div class="row g-3 mt-1">
            <div class="col-md-6">
                <label class="form-label">Satuan</label>
                <input type="text" class="form-control" id="item-unit" value="pcs">
            </div>
            <div class="col-md-6">
                <label class="form-label">Stok</label>
                <input type="number" step="0.001" class="form-control" id="item-stock" value="0">
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary" id="item-save-btn">
            <i class="fas fa-save me-2"></i>Simpan
        </button>
      </div>
      </form>
    </div>
  </div>
 </div>

<?php $page_specific_scripts = <<<'EOT'
<script>
// Load DataTables assets dynamically if missing
function ensureDataTables(cb){
    const cssId = 'dt-css';
    const jsId = 'dt-js';
    if(!document.getElementById(cssId)){
        const l = document.createElement('link');
        l.id = cssId; l.rel='stylesheet';
        l.href='https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css';
        document.head.appendChild(l);
    }
    function loadJS(src,id,done){
        if(document.getElementById(id)) return done();
        const s=document.createElement('script'); s.id=id; s.src=src; s.onload=done; document.body.appendChild(s);
    }
    loadJS('https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js','dt-js-core',function(){
        loadJS('https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js','dt-js-bs',cb);
    });
}

function fetchJSON(url){
    return fetch(url, { headers: { 'X-CSRF-Token': '<?php echo getCsrfToken(); ?>' } }).then(r=>r.json());
}

document.addEventListener('DOMContentLoaded', function(){
    const tbl = document.querySelector('#inventory-table');
    const tbody = tbl.querySelector('tbody');
    const search = document.getElementById('inv-search');
    const category = document.getElementById('inv-category');
    const locationSel = document.getElementById('inv-location');

    function renderRows(items){
        tbody.innerHTML = '';
        if(!items || items.length===0){
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">Tidak ada data.</td></tr>';
            return;
        }
        items.forEach(it=>{
            const tr=document.createElement('tr');
            tr.innerHTML = `
                <td>${it.code}</td>
                <td>${it.name}</td>
                <td>${it.category||'-'}</td>
                <td>${it.qty}</td>
                <td>${it.unit||'-'}</td>
                <td>${it.location||'-'}</td>
                <td>${it.updated_at||'-'}</td>
                <td class="text-end">
                   <button class="btn btn-sm btn-outline-primary me-1 btn-edit"><i class="fas fa-edit"></i></button>
                   <button class="btn btn-sm btn-outline-danger btn-del"><i class="fas fa-trash"></i></button>
                </td>
            `;
            tr.dataset.row = JSON.stringify(it);
            tbody.appendChild(tr);
        });
    }

    function applyFilters(items){
        const q=(search.value||'').toLowerCase();
        const cat=category.value||'';
        const loc=locationSel.value||'';
        return items.filter(it=>{
            const okQ = !q || (it.code+it.name).toLowerCase().includes(q);
            const okC = !cat || (it.category===cat);
            const okL = !loc || (it.location===loc);
            return okQ && okC && okL;
        });
    }

    function loadFilters(){
        fetchJSON('/api/admin/warehouse.php?action=list_categories').then(res=>{
            const cats=(res.categories||[]).map(c=>c.name);
            category.innerHTML = '<option value="">Semua Kategori</option>' + cats.map(c=>`<option value="${c}">${c}</option>`).join('');
            const catOptions = (res.categories||[]).map(c=>`<option value="${c.id||''}">${c.name||c.code}</option>`).join('');
            document.getElementById('item-category').innerHTML = '<option value="">(Opsional)</option>' + catOptions;
        });
        fetchJSON('/api/admin/warehouse.php?action=list_locations').then(res=>{
            const locs=(res.locations||[]).map(l=>l.code);
            locationSel.innerHTML = '<option value="">Semua Lokasi</option>' + locs.map(c=>`<option value="${c}">${c}</option>`).join('');
            const locOptions=(res.locations||[]).map(l=>`<option value="${l.id||''}">${l.code}</option>`).join('');
            document.getElementById('item-location').innerHTML = '<option value="">(Opsional)</option>' + locOptions;
        });
    }

    let allItems=[];
    function loadData(){
        fetchJSON('/api/admin/warehouse.php?action=list_items').then(res=>{
            allItems = res.items||[];
            const filtered = applyFilters(allItems);
            renderRows(filtered);
            ensureDataTables(function(){
                if(!$.fn.DataTable.isDataTable('#inventory-table')){
                    $('#inventory-table').DataTable({
                        responsive:true,
                        pageLength:25,
                        order:[],
                    });
                } else {
                    $('#inventory-table').DataTable().destroy();
                    $('#inventory-table').DataTable({responsive:true,pageLength:25,order:[]});
                }
            });
        });
    }

    document.getElementById('inv-clear')?.addEventListener('click', function(){
        search.value=''; category.value=''; locationSel.value=''; loadData();
    });
    [search,category,locationSel].forEach(el=> el && el.addEventListener('input',()=>{
        const filtered = applyFilters(allItems);
        renderRows(filtered);
        if($.fn.DataTable && $.fn.DataTable.isDataTable('#inventory-table')){
            $('#inventory-table').DataTable().destroy();
            $('#inventory-table').DataTable({responsive:true,pageLength:25,order:[]});
        }
    }));

    // Add/Edit item
    let modal, editingId=null;
    function openModal(row){
        editingId = row ? row.id : null;
        document.getElementById('itemModalTitle').textContent = row ? 'Edit Barang' : 'Tambah Barang';
        document.getElementById('item-id').value = row?.id || '';
        document.getElementById('item-code').value = row?.code || '';
        document.getElementById('item-name').value = row?.name || '';
        document.getElementById('item-unit').value = row?.unit || 'pcs';
        document.getElementById('item-stock').value = row?.qty || 0;
        modal = new bootstrap.Modal(document.getElementById('itemModal'));
        modal.show();
    }
    document.getElementById('btn-add-item').addEventListener('click', ()=> openModal(null));
    tbody.addEventListener('click', function(e){
        const btn = e.target.closest('button'); if(!btn) return;
        const row = JSON.parse(btn.closest('tr').dataset.row||'{}');
        if(btn.classList.contains('btn-edit')) return openModal(row);
        if(btn.classList.contains('btn-del')){
            if(confirm('Hapus barang ini?')){
                fetch('/api/admin/warehouse.php?action=delete_item',{ method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':'<?php echo getCsrfToken(); ?>'}, body: JSON.stringify({ id: row.id }) })
                .then(r=>r.json()).then(res=>{ if(!res.success) throw new Error(res.message||'Gagal hapus'); loadData(); })
                .catch(err=>alert(err.message));
            }
        }
    });
    document.getElementById('itemForm').addEventListener('submit', function(e){
        e.preventDefault();
        const payload={
            id: document.getElementById('item-id').value,
            code: document.getElementById('item-code').value.trim(),
            name: document.getElementById('item-name').value.trim(),
            unit: document.getElementById('item-unit').value.trim()||'pcs',
            stock_qty: parseFloat(document.getElementById('item-stock').value||'0'),
            category_id: parseInt(document.getElementById('item-category').value||'0'),
            location_id: parseInt(document.getElementById('item-location').value||'0'),
        };
        const action = editingId ? 'update_item' : 'create_item';
        fetch('/api/admin/warehouse.php?action='+action,{ method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':'<?php echo getCsrfToken(); ?>'}, body: JSON.stringify(payload) })
        .then(r=>r.json()).then(res=>{ if(!res.success) throw new Error(res.message||'Gagal simpan'); modal.hide(); loadData(); })
        .catch(err=>alert(err.message));
    });

    loadFilters();
    loadData();
});
</script>
EOT;
?>
<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
