<?php
// SolusiPaymentManagement Admin Payment Gateways Page

require_once __DIR__ . '/../includes/bootstrap.php';

// Check authentication and permissions
$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.payment_gateways');

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Gateways - SolusiPaymentManagement</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .sidebar { min-height: 100vh; background: #343a40; }
        .sidebar .nav-link { color: rgba(255,255,255,.75); }
        .sidebar .nav-link:hover { color: #fff; }
        .sidebar .nav-link.active { color: #fff; background: #0d6efd; }
        .main-content { margin-left: 0; }
        @media (min-width: 768px) { .main-content { margin-left: 250px; } }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar position-fixed" style="width: 250px;">
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
                    <a class="nav-link" href="customers.php">
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
                    <a class="nav-link active" href="payment_gateways.php">
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
                <h2>Payment Gateways</h2>
                <button class="btn btn-primary" onclick="showCreateModal()">
                    <i class="fas fa-plus me-2"></i>Add Gateway
                </button>
            </div>

            <!-- Gateways Grid -->
            <div class="row" id="gateways-container">
                <!-- Gateways will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Gateway Modal -->
    <div class="modal fade" id="gatewayModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-title">Configure Payment Gateway</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="gateway-form">
                    <div class="modal-body">
                        <input type="hidden" id="gateway-id" name="id">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Gateway Name *</label>
                                <input type="text" class="form-control" id="nama" name="nama" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Provider *</label>
                                <select class="form-select" id="provider" name="provider" required>
                                    <option value="">Select Provider</option>
                                    <option value="midtrans">Midtrans</option>
                                    <option value="xendit">Xendit</option>
                                    <option value="tripay">Tripay</option>
                                    <option value="duitku">Duitku</option>
                                    <option value="doku">DOKU</option>
                                    <option value="ovo">OVO</option>
                                    <option value="gopay">GoPay</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                    <label class="form-check-label" for="is_active">
                                        Active
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Provider-specific configuration -->
                        <div id="config-section" class="mt-4">
                            <h6>Configuration</h6>
                            <div id="midtrans-config" class="provider-config" style="display: none;">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Server Key</label>
                                        <input type="password" class="form-control" name="config[server_key]">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Client Key</label>
                                        <input type="text" class="form-control" name="config[client_key]">
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="config[sandbox]" checked>
                                            <label class="form-check-label">Sandbox Mode</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="xendit-config" class="provider-config" style="display: none;">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Secret Key</label>
                                        <input type="password" class="form-control" name="config[secret_key]">
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="config[sandbox]" checked>
                                            <label class="form-check-label">Sandbox Mode</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="tripay-config" class="provider-config" style="display: none;">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">API Key</label>
                                        <input type="password" class="form-control" name="config[api_key]">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Private Key</label>
                                        <input type="password" class="form-control" name="config[private_key]">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Merchant Code</label>
                                        <input type="text" class="form-control" name="config[merchant_code]">
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="config[sandbox]" checked>
                                            <label class="form-check-label">Sandbox Mode</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Other providers can be added similarly -->
                            <div id="other-config" class="provider-config">
                                <div class="alert alert-info">
                                    Configuration for this provider is not yet implemented.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="save-btn">
                            <i class="fas fa-save me-2"></i>Save
                        </button>
                        <button type="button" class="btn btn-info" id="test-btn" style="display: none;">
                            <i class="fas fa-vial me-2"></i>Test
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

    <script>
        const csrfToken = '<?php echo getCsrfToken(); ?>';

        $.ajaxSetup({
            headers: { 'X-CSRF-Token': csrfToken }
        });

        let gatewayModal;

        $(document).ready(function() {
            gatewayModal = new bootstrap.Modal(document.getElementById('gatewayModal'));
            loadGateways();
            setupEventListeners();
        });

        function loadGateways() {
            $.get('/api/admin/gateways?action=list')
                .done(function(response) {
                    if (response.success) {
                        renderGateways(response.gateways);
                    }
                });
        }

        function renderGateways(gateways) {
            let html = '';

            if (gateways.length === 0) {
                html = `
                    <div class="col-12">
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle fa-2x mb-3"></i>
                            <h5>No Payment Gateways Configured</h5>
                            <p>Click "Add Gateway" to configure your first payment gateway.</p>
                        </div>
                    </div>
                `;
            } else {
                gateways.forEach(function(gateway) {
                    const statusBadge = gateway.is_active ?
                        '<span class="badge bg-success">Active</span>' :
                        '<span class="badge bg-secondary">Inactive</span>';

                    html += `
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h5 class="card-title">${gateway.nama}</h5>
                                        ${statusBadge}
                                    </div>
                                    <p class="card-text">
                                        <strong>Provider:</strong> ${gateway.provider.toUpperCase()}<br>
                                        <strong>Status:</strong> ${gateway.is_active ? 'Active' : 'Inactive'}
                                    </p>
                                </div>
                                <div class="card-footer">
                                    <div class="btn-group btn-group-sm w-100">
                                        <button class="btn btn-outline-primary" onclick="editGateway(${gateway.id})">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-outline-info" onclick="testGateway(${gateway.id})">
                                            <i class="fas fa-vial"></i> Test
                                        </button>
                                        <button class="btn btn-outline-danger" onclick="deleteGateway(${gateway.id})">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
            }

            $('#gateways-container').html(html);
        }

        function setupEventListeners() {
            $('#provider').on('change', function() {
                showProviderConfig($(this).val());
            });

            $('#gateway-form').on('submit', function(e) {
                e.preventDefault();
                saveGateway();
            });
        }

        function showCreateModal() {
            $('#modal-title').text('Add Payment Gateway');
            $('#gateway-form')[0].reset();
            $('#gateway-id').val('');
            $('.provider-config').hide();
            $('#test-btn').hide();
            gatewayModal.show();
        }

        function editGateway(id) {
            $.get(`/api/admin/gateways?id=${id}`)
                .done(function(response) {
                    if (response.success && response.gateway) {
                        const gateway = response.gateway;
                        $('#modal-title').text('Edit Payment Gateway');
                        $('#gateway-id').val(gateway.id);
                        $('#nama').val(gateway.nama);
                        $('#provider').val(gateway.provider);
                        $('#is_active').prop('checked', gateway.is_active == 1);

                        // Load configuration
                        if (gateway.config_json) {
                            const config = JSON.parse(gateway.config_json);
                            loadGatewayConfig(gateway.provider, config);
                        }

                        showProviderConfig(gateway.provider);
                        $('#test-btn').show();
                        gatewayModal.show();
                    }
                });
        }

        function loadGatewayConfig(provider, config) {
            switch (provider) {
                case 'midtrans':
                    $('input[name="config[server_key]"]').val(config.server_key || '');
                    $('input[name="config[client_key]"]').val(config.client_key || '');
                    $('input[name="config[sandbox]"]').prop('checked', config.sandbox !== false);
                    break;
                case 'xendit':
                    $('input[name="config[secret_key]"]').val(config.secret_key || '');
                    $('input[name="config[sandbox]"]').prop('checked', config.sandbox !== false);
                    break;
                case 'tripay':
                    $('input[name="config[api_key]"]').val(config.api_key || '');
                    $('input[name="config[private_key]"]').val(config.private_key || '');
                    $('input[name="config[merchant_code]"]').val(config.merchant_code || '');
                    $('input[name="config[sandbox]"]').prop('checked', config.sandbox !== false);
                    break;
            }
        }

        function showProviderConfig(provider) {
            $('.provider-config').hide();
            $(`#${provider}-config`).show();
            if (!$(`#${provider}-config`).length) {
                $('#other-config').show();
            }
        }

        function saveGateway() {
            const formData = new FormData(document.getElementById('gateway-form'));

            // Convert config fields to JSON
            const config = {};
            for (let [key, value] of formData.entries()) {
                if (key.startsWith('config[')) {
                    const configKey = key.match(/config\[(.+)\]/)[1];
                    config[configKey] = value;
                }
            }
            formData.set('config_json', JSON.stringify(config));

            $('#save-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...');

            $.ajax({
                url: '/api/admin/gateways?action=save',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false
            })
            .done(function(response) {
                if (response.success) {
                    gatewayModal.hide();
                    loadGateways();
                    showAlert('Gateway saved successfully', 'success');
                } else {
                    showAlert(response.message || 'Error saving gateway', 'danger');
                }
            })
            .fail(function(xhr) {
                const response = xhr.responseJSON;
                showAlert(response?.message || 'Error saving gateway', 'danger');
            })
            .always(function() {
                $('#save-btn').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Save');
            });
        }

        function testGateway(id) {
            $('#test-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Testing...');

            $.ajax({
                url: `/api/admin/gateways?action=test&id=${id}`,
                method: 'POST'
            })
            .done(function(response) {
                if (response.success) {
                    showAlert('Gateway test successful!', 'success');
                } else {
                    showAlert('Gateway test failed: ' + response.error, 'danger');
                }
            })
            .fail(function(xhr) {
                const response = xhr.responseJSON;
                showAlert('Gateway test failed: ' + (response?.message || 'Unknown error'), 'danger');
            })
            .always(function() {
                $('#test-btn').prop('disabled', false).html('<i class="fas fa-vial me-2"></i>Test');
            });
        }

        function deleteGateway(id) {
            if (!confirm('Are you sure you want to delete this gateway? This action cannot be undone.')) return;

            $.ajax({
                url: `/api/admin/gateways?action=delete&id=${id}`,
                method: 'POST'
            })
            .done(function(response) {
                if (response.success) {
                    loadGateways();
                    showAlert('Gateway deleted successfully', 'success');
                } else {
                    showAlert(response.message || 'Error deleting gateway', 'danger');
                }
            });
        }

        function showAlert(message, type) {
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
