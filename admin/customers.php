<?php
// SolusiPaymentManagement Admin Customers Page

require_once __DIR__ . '/../includes/bootstrap.php';

// Check authentication and permissions
$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.customers');

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - SolusiPaymentManagement</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

    <style>
        .sidebar { min-block-size: 100vh; background: #343a40; }
        .sidebar .nav-link { color: rgba(255,255,255,.75); }
        .sidebar .nav-link:hover { color: #fff; }
        .sidebar .nav-link.active { color: #fff; background: #0d6efd; }
        .main-content { margin-inline-start: 0; }
        @media (min-inline-size: 768px) { .main-content { margin-inline-start: 250px; } }
        .table-responsive { max-block-size: 600px; overflow-y: auto; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar position-fixed" style="inline-size: 250px;">
        <div class="p-3">
            <h5 class="text-white mb-4">
                <i class="fas fa-cogs me-2"></i>
                SolusiPaymentManagement
            </h5>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="customers.php">
                        <i class="fas fa-users me-2"></i>Customers
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="invoices.php">
                        <i class="fas fa-file-invoice me-2"></i>Invoices
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="transactions.php">
                        <i class="fas fa-credit-card me-2"></i>Transactions
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="payment_gateways.php">
                        <i class="fas fa-money-check me-2"></i>Payment Gateways
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="customers_map.php">
                        <i class="fas fa-map-marked-alt me-2"></i>Customer Map
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="settings.php">
                        <i class="fas fa-cog me-2"></i>Settings
                    </a>
                </li>
                <li class="nav-item mt-3">
                    <a class="nav-link text-danger" href="#" onclick="logout()">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid p-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Customers</h2>
                <button class="btn btn-primary" onclick="showCreateModal()">
                    <i class="fas fa-plus me-2"></i>Add Customer
                </button>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <input type="text" class="form-control" id="search-input" placeholder="Search customers...">
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" id="status-filter">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="isolir">Isolir</option>
                                <option value="suspended">Suspended</option>
                                <option value="terminated">Terminated</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-outline-secondary" onclick="clearFilters()">
                                <i class="fas fa-times me-2"></i>Clear
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customers Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="customers-table" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Package</th>
                                    <th>Status</th>
                                    <th>PPPoE User</th>
                                    <th>Router</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create/Edit Customer Modal -->
    <div class="modal fade" id="customerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-title">Add Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="customer-form">
                    <div class="modal-body">
                        <input type="hidden" id="customer-id" name="id">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Name *</label>
                                <input type="text" class="form-control" id="nama" name="nama" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="telp" name="telp">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Package *</label>
                                <input type="text" class="form-control" id="paket" name="paket" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" id="alamat" name="alamat" rows="3"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">PPPoE Username</label>
                                <input type="text" class="form-control" id="pppoe_user" name="pppoe_user">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">PPPoE Password</label>
                                <input type="password" class="form-control" id="pppoe_pass" name="pppoe_pass">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Router</label>
                                <select class="form-select" id="router_id" name="router_id">
                                    <option value="">Select Router</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active">Active</option>
                                    <option value="isolir">Isolir</option>
                                    <option value="suspended">Suspended</option>
                                    <option value="terminated">Terminated</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Profile Aktif</label>
                                <input type="text" class="form-control" id="profile_aktif" name="profile_aktif" value="default">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Profile Isolir</label>
                                <input type="text" class="form-control" id="profile_isolir" name="profile_isolir" value="ISOLIR">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="save-btn">
                            <i class="fas fa-save me-2"></i>Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <script>
        // CSRF token
        const csrfToken = '<?php echo getCsrfToken(); ?>';

        $.ajaxSetup({
            headers: { 'X-CSRF-Token': csrfToken }
        });

        let customersTable;
        let customerModal;

        $(document).ready(function() {
            customerModal = new bootstrap.Modal(document.getElementById('customerModal'));
            initializeTable();
            loadRouters();
            setupEventListeners();
        });

        function initializeTable() {
            customersTable = $('#customers-table').DataTable({
                ajax: {
                    url: '/api/admin/customers?action=list',
                    data: function(d) {
                        d.search = $('#search-input').val();
                        d.status = $('#status-filter').val();
                    }
                },
                columns: [
                    { data: 'kode_pelanggan' },
                    { data: 'nama' },
                    { data: 'email' },
                    { data: 'telp' },
                    { data: 'paket' },
                    {
                        data: 'status',
                        render: function(data) {
                            const badges = {
                                'active': '<span class="badge bg-success">Active</span>',
                                'isolir': '<span class="badge bg-danger">Isolir</span>',
                                'suspended': '<span class="badge bg-warning">Suspended</span>',
                                'terminated': '<span class="badge bg-secondary">Terminated</span>'
                            };
                            return badges[data] || data;
                        }
                    },
                    { data: 'pppoe_user' },
                    { data: 'router_name', defaultContent: '-' },
                    {
                        data: 'created_at',
                        render: function(data) {
                            return new Date(data).toLocaleDateString('id-ID');
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        render: function(data) {
                            return `
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" onclick="editCustomer(${data.id})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="deleteCustomer(${data.id})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <button class="btn btn-outline-warning" onclick="toggleCustomerStatus(${data.id}, '${data.status}')">
                                        <i class="fas fa-${data.status === 'active' ? 'ban' : 'check'}"></i>
                                    </button>
                                </div>
                            `;
                        }
                    }
                ],
                pageLength: 25,
                responsive: true,
                language: {
                    emptyTable: "No customers found"
                }
            });
        }

        function loadRouters() {
            $.get('/api/admin/mikrotik?action=routers')
                .done(function(response) {
                    if (response.success) {
                        let options = '<option value="">Select Router</option>';
                        response.routers.forEach(function(router) {
                            options += `<option value="${router.id}">${router.name}</option>`;
                        });
                        $('#router_id').html(options);
                    }
                });
        }

        function setupEventListeners() {
            $('#search-input, #status-filter').on('input change', function() {
                customersTable.ajax.reload();
            });

            $('#customer-form').on('submit', function(e) {
                e.preventDefault();
                saveCustomer();
            });
        }

        function showCreateModal() {
            $('#modal-title').text('Add Customer');
            $('#customer-form')[0].reset();
            $('#customer-id').val('');
            customerModal.show();
        }

        function editCustomer(id) {
            $.get(`/api/admin/customers?id=${id}`)
                .done(function(response) {
                    if (response.success && response.customer) {
                        const customer = response.customer;
                        $('#modal-title').text('Edit Customer');
                        $('#customer-id').val(customer.id);
                        $('#nama').val(customer.nama);
                        $('#email').val(customer.email);
                        $('#telp').val(customer.telp);
                        $('#paket').val(customer.paket);
                        $('#alamat').val(customer.alamat);
                        $('#pppoe_user').val(customer.pppoe_user);
                        $('#router_id').val(customer.router_id);
                        $('#status').val(customer.status);
                        $('#profile_aktif').val(customer.profile_aktif);
                        $('#profile_isolir').val(customer.profile_isolir);
                        customerModal.show();
                    }
                });
        }

        function saveCustomer() {
            const formData = new FormData(document.getElementById('customer-form'));
            const isEdit = formData.get('id') !== '';

            $('#save-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...');

            $.ajax({
                url: '/api/admin/customers?action=' + (isEdit ? 'update' : 'create'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false
            })
            .done(function(response) {
                if (response.success) {
                    customerModal.hide();
                    customersTable.ajax.reload();
                    showAlert('Customer ' + (isEdit ? 'updated' : 'created') + ' successfully', 'success');
                } else {
                    showAlert(response.message || 'Error saving customer', 'danger');
                }
            })
            .fail(function(xhr) {
                const response = xhr.responseJSON;
                showAlert(response?.message || 'Error saving customer', 'danger');
            })
            .always(function() {
                $('#save-btn').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Save');
            });
        }

        function deleteCustomer(id) {
            if (!confirm('Are you sure you want to delete this customer?')) return;

            $.ajax({
                url: `/api/admin/customers?action=delete&id=${id}`,
                method: 'POST'
            })
            .done(function(response) {
                if (response.success) {
                    customersTable.ajax.reload();
                    showAlert('Customer deleted successfully', 'success');
                } else {
                    showAlert(response.message || 'Error deleting customer', 'danger');
                }
            });
        }

        function toggleCustomerStatus(id, currentStatus) {
            const action = currentStatus === 'active' ? 'isolir' : 'activate';
            const confirmMsg = `Are you sure you want to ${action} this customer?`;

            if (!confirm(confirmMsg)) return;

            $.ajax({
                url: `/api/admin/customers?action=${action}&id=${id}`,
                method: 'POST'
            })
            .done(function(response) {
                if (response.success) {
                    customersTable.ajax.reload();
                    showAlert(`Customer ${action}d successfully`, 'success');
                } else {
                    showAlert(response.message || `Error ${action}ing customer`, 'danger');
                }
            });
        }

        function clearFilters() {
            $('#search-input').val('');
            $('#status-filter').val('');
            customersTable.ajax.reload();
        }

        function showAlert(message, type) {
            // Simple alert - you can enhance this with a proper notification system
            alert(message);
        }

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                $.post('/api/public/logout')
                    .done(function() {
                        window.location.href = '/';
                    });
            }
        }
    </script>
</body>
</html>
