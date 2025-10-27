<?php
$page_title = 'Mitra';
require_once '../includes/admin_header.php';

// Security check: Ensure user is an admin and has permission
$guard->requirePermission('admin.agents');
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Mitra</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#agentModal">
            <i class="fas fa-plus"></i> Tambah Mitra
        </button>
    </div>
</div>

<!-- Daftar Mitra -->
<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Balance</th>
                        <th>Commission Rate</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="agents-table-body">
                    <!-- Agent rows will be inserted here by JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Agent Modal -->
<div class="modal fade" id="agentModal" tabindex="-1" aria-labelledby="agentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="agentModalLabel">Tambah/Ubah Mitra</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="agentForm">
                    <input type="hidden" id="agentId" name="id">
                    <div class="mb-3">
                        <label for="agentUser" class="form-label">User (Akun Mitra)</label>
                        <select class="form-select" id="agentUser" name="user_id" required></select>
                    </div>
                    <div class="mb-3">
                        <label for="agentBalance" class="form-label">Saldo</label>
                        <input type="number" class="form-control" id="agentBalance" name="balance" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="agentCommissionRate" class="form-label">Bagi Hasil Mitra (%)</label>
                        <input type="number" class="form-control" id="agentCommissionRate" name="commission_rate" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="agentStatus" class="form-label">Status</label>
                        <select class="form-select" id="agentStatus" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$page_specific_scripts = <<<'EOT'
<script>
$(document).ready(function() {
    function loadUsersDropdown() {
        $.get('/api/admin/users.php?action=list', function(data) {
            let options = '<option value="">Select User</option>';
            data.forEach(function(user) {
                options += `<option value="${user.id}">${user.nama} (${user.email})</option>`;
            });
            $('#agentUser').html(options);
        });
    }

    function loadAgents() {
        $.get('/api/admin/agents.php?action=list', function(data) {
            let tableBody = '';
            if(data.length === 0) {
                tableBody = '<tr><td colspan="5" class="text-center">No agents found.</td></tr>';
            }
            data.forEach(function(agent) {
                tableBody += `
                    <tr>
                        <td>${agent.user_name}</td>
                        <td>${agent.balance}</td>
                        <td>${agent.commission_rate}</td>
                        <td><span class="badge bg-${getBootstrapClassForStatus(agent.status)}">${agent.status}</span></td>
                        <td>
                            <button class="btn btn-sm btn-info btn-edit" data-id="${agent.id}"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-danger btn-delete" data-id="${agent.id}"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                `;
            });
            $('#agents-table-body').html(tableBody);
        });
    }

    function getBootstrapClassForStatus(status) {
        switch (status) {
            case 'active': return 'success';
            case 'inactive': return 'secondary';
            case 'suspended': return 'warning';
            default: return 'dark';
        }
    }

    loadUsersDropdown();
    loadAgents();

    $('#agentForm').on('submit', function(e) {
        e.preventDefault();
        let formData = $(this).serialize();
        let agentId = $('#agentId').val();
        let url = agentId ? '/api/admin/agents.php?action=update' : '/api/admin/agents.php?action=create';
        $.post(url, formData, function() {
            $('#agentModal').modal('hide');
            loadAgents();
        });
    });

    $('#agents-table-body').on('click', '.btn-edit', function() {
        let agentId = $(this).data('id');
        $.get('/api/admin/agents.php?action=get&id=' + agentId, function(agent) {
            $('#agentForm')[0].reset();
            $('#agentId').val(agent.id);
            $('#agentUser').val(agent.user_id);
            $('#agentBalance').val(agent.balance);
            $('#agentCommissionRate').val(agent.commission_rate);
            $('#agentStatus').val(agent.status);
            $('#agentModal').modal('show');
        });
    });

    $('#agents-table-body').on('click', '.btn-delete', function() {
        if (confirm('Are you sure you want to delete this agent?')) {
            let agentId = $(this).data('id');
            $.post('/api/admin/agents.php?action=delete&id=' + agentId, function() {
                loadAgents();
            });
        }
    });

    $('#agentModal').on('hidden.bs.modal', function () {
        $('#agentForm')[0].reset();
        $('#agentId').val('');
    });
});
</script>
EOT;

require_once '../includes/admin_footer.php';
?>
