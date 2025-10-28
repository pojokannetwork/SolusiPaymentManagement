<?php
// SolusiPaymentManagement Admin Invoices Page (Responsive Template)
$page_title = 'Invoices';
require_once __DIR__ . '/../includes/admin_header.php';
// Permission check after header for consistency
$guard->requirePermission('admin.invoices');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-file-invoice me-2"></i>Invoices Management</h2>
    <button class="btn btn-primary" onclick="showCreateInvoiceModal()">
        <i class="fas fa-plus me-2"></i>Create New Invoice
    </button>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-secondary">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="card-title">Draft</h6>
                        <h3 id="draft-count">0</h3>
                        <p class="mb-0 small" id="draft-amount">Rp 0</p>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-file-alt fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="card-title">Sent</h6>
                        <h3 id="sent-count">0</h3>
                        <p class="mb-0 small" id="sent-amount">Rp 0</p>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-paper-plane fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="card-title">Paid</h6>
                        <h3 id="paid-count">0</h3>
                        <p class="mb-0 small" id="paid-amount">Rp 0</p>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="card-title">Overdue</h6>
                        <h3 id="overdue-count">0</h3>
                        <p class="mb-0 small" id="overdue-amount">Rp 0</p>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Filter Status</label>
                <select class="form-select" id="status-filter">
                    <option value="">All Status</option>
                    <option value="draft">Draft</option>
                    <option value="sent">Sent</option>
                    <option value="paid">Paid</option>
                    <option value="overdue">Overdue</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" class="form-control" id="search-filter" placeholder="Invoice no, customer name...">
            </div>
            <div class="col-md-2">
                <label class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start-date-filter">
            </div>
            <div class="col-md-2">
                <label class="form-label">End Date</label>
                <input type="date" class="form-control" id="end-date-filter">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-secondary w-100" onclick="clearFilters()">
                    <i class="fas fa-undo me-2"></i>Clear
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Invoices Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="invoices-table">
                <thead>
                    <tr>
                        <th>Invoice No</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Due Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="invoices-tbody">
                    <tr>
                        <td colspan="7" class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create/Edit Invoice Modal -->
<div class="modal fade" id="invoiceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="invoiceModalTitle">Create New Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="invoiceForm">
                    <input type="hidden" id="invoice-id" name="id">

                    <div class="mb-3">
                        <label class="form-label">Customer *</label>
                        <select class="form-select" id="customer-select" name="pelanggan_id" required>
                            <option value="">Select Customer</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Invoice Date</label>
                            <input type="date" class="form-control" id="invoice-date" name="tanggal" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="due-date" name="jatuh_tempo" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Invoice Items</label>
                        <div id="invoice-items">
                            <!-- Items will be added here dynamically -->
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addInvoiceItem()">
                            <i class="fas fa-plus me-1"></i>Add Item
                        </button>
                    </div>

                    <div class="row">
                        <div class="col-md-8"></div>
                        <div class="col-md-4">
                            <div class="mb-2">
                                <label>Subtotal:</label>
                                <span class="float-end fw-bold" id="subtotal-display">Rp 0</span>
                            </div>
                            <div class="mb-2">
                                <label>Tax:</label>
                                <input type="number" class="form-control form-control-sm d-inline w-50 float-end"
                                       id="tax-input" name="pajak" value="0" step="0.01" onchange="calculateTotal()">
                            </div>
                            <hr>
                            <div class="mb-2">
                                <label class="fw-bold">Total:</label>
                                <span class="float-end fw-bold fs-5" id="total-display">Rp 0</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="invoice-status" name="status">
                            <option value="draft">Draft</option>
                            <option value="sent">Sent</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveInvoice()">Save Invoice</button>
            </div>
        </div>
    </div>
</div>

<!-- View Invoice Modal -->
<div class="modal fade" id="viewInvoiceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Invoice Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="invoice-view-content">
                <!-- Invoice details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printInvoice()">
                    <i class="fas fa-print me-2"></i>Print
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let invoiceModal, viewInvoiceModal;
let currentInvoiceId = null;

$(document).ready(function() {
    invoiceModal = new bootstrap.Modal(document.getElementById('invoiceModal'));
    viewInvoiceModal = new bootstrap.Modal(document.getElementById('viewInvoiceModal'));

    loadSummary();
    loadInvoices();
    loadCustomers();

    // Set up filter listeners
    $('#status-filter, #search-filter, #start-date-filter, #end-date-filter').on('change keyup', function() {
        loadInvoices();
    });
});

function loadSummary() {
    $.get('/api/admin/invoices.php?action=summary')
    .done(function(response) {
        if (response.success && response.data) {
            const data = response.data;
            $('#draft-count').text(data.draft || 0);
            $('#draft-amount').text(formatRupiah(data.total_draft || 0));
            $('#sent-count').text(data.sent || 0);
            $('#sent-amount').text(formatRupiah(data.total_sent || 0));
            $('#paid-count').text(data.paid || 0);
            $('#paid-amount').text(formatRupiah(data.total_paid || 0));
            $('#overdue-count').text(data.overdue || 0);
            $('#overdue-amount').text(formatRupiah(data.total_overdue || 0));
        }
    });
}

function loadInvoices() {
    const status = $('#status-filter').val();
    const search = $('#search-filter').val();
    const startDate = $('#start-date-filter').val();
    const endDate = $('#end-date-filter').val();

    const params = new URLSearchParams({
        action: 'list',
        status: status,
        search: search,
        start_date: startDate,
        end_date: endDate
    });

    $.get('/api/admin/invoices.php?' + params.toString())
    .done(function(response) {
        if (response.success) {
            displayInvoices(response.data);
        }
    });
}

function displayInvoices(invoices) {
    const tbody = $('#invoices-tbody');
    tbody.empty();

    if (invoices.length === 0) {
        tbody.append('<tr><td colspan="7" class="text-center text-muted">No invoices found</td></tr>');
        return;
    }

    invoices.forEach(invoice => {
        const statusBadge = getStatusBadge(invoice.status);
        const row = `
            <tr>
                <td>${invoice.nomor}</td>
                <td>
                    <div>${invoice.customer_name || 'N/A'}</div>
                    <small class="text-muted">${invoice.kode_pelanggan || ''}</small>
                </td>
                <td>${formatDate(invoice.tanggal)}</td>
                <td>${formatDate(invoice.jatuh_tempo)}</td>
                <td class="fw-bold">${formatRupiah(invoice.total)}</td>
                <td>${statusBadge}</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-info" onclick="viewInvoice(${invoice.id})" title="View">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${invoice.status === 'draft' ? `
                            <button class="btn btn-outline-primary" onclick="editInvoice(${invoice.id})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                        ` : ''}
                        ${invoice.status === 'draft' ? `
                            <button class="btn btn-outline-success" onclick="sendInvoice(${invoice.id})" title="Send">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        ` : ''}
                        ${invoice.status !== 'paid' && invoice.status !== 'cancelled' ? `
                            <button class="btn btn-outline-success" onclick="markPaid(${invoice.id})" title="Mark as Paid">
                                <i class="fas fa-check"></i>
                            </button>
                        ` : ''}
                        ${invoice.status !== 'paid' ? `
                            <button class="btn btn-outline-danger" onclick="deleteInvoice(${invoice.id})" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function getStatusBadge(status) {
    const badges = {
        'draft': '<span class="badge bg-secondary">Draft</span>',
        'sent': '<span class="badge bg-info">Sent</span>',
        'paid': '<span class="badge bg-success">Paid</span>',
        'overdue': '<span class="badge bg-danger">Overdue</span>',
        'cancelled': '<span class="badge bg-dark">Cancelled</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">' + status + '</span>';
}

function loadCustomers() {
    $.get('/api/admin/customers.php?action=list')
    .done(function(response) {
        if (response.success && response.data) {
            const select = $('#customer-select');
            select.empty().append('<option value="">Select Customer</option>');
            response.data.forEach(customer => {
                select.append(`<option value="${customer.id}">${customer.nama} (${customer.kode_pelanggan})</option>`);
            });
        }
    });
}

function showCreateInvoiceModal() {
    $('#invoiceModalTitle').text('Create New Invoice');
    $('#invoiceForm')[0].reset();
    $('#invoice-id').val('');
    $('#invoice-items').empty();
    addInvoiceItem(); // Add one default item
    calculateTotal();
    invoiceModal.show();
}

function addInvoiceItem() {
    const itemHtml = `
        <div class="invoice-item mb-2 border p-3 rounded">
            <div class="row">
                <div class="col-md-5">
                    <input type="text" class="form-control form-control-sm" placeholder="Description"
                           name="item_desc[]" required>
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control form-control-sm" placeholder="Qty"
                           name="item_qty[]" value="1" min="0" step="0.01" onchange="calculateTotal()" required>
                </div>
                <div class="col-md-3">
                    <input type="number" class="form-control form-control-sm" placeholder="Price"
                           name="item_price[]" value="0" min="0" step="0.01" onchange="calculateTotal()" required>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeInvoiceItem(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    $('#invoice-items').append(itemHtml);
}

function removeInvoiceItem(button) {
    $(button).closest('.invoice-item').remove();
    calculateTotal();
}

function calculateTotal() {
    let subtotal = 0;

    $('.invoice-item').each(function() {
        const qty = parseFloat($(this).find('[name="item_qty[]"]').val()) || 0;
        const price = parseFloat($(this).find('[name="item_price[]"]').val()) || 0;
        subtotal += qty * price;
    });

    const tax = parseFloat($('#tax-input').val()) || 0;
    const total = subtotal + tax;

    $('#subtotal-display').text(formatRupiah(subtotal));
    $('#total-display').text(formatRupiah(total));
}

function saveInvoice() {
    const form = $('#invoiceForm');

    // Validate form
    if (!form[0].checkValidity()) {
        form[0].reportValidity();
        return;
    }

    // Collect invoice items
    const items = [];
    $('.invoice-item').each(function() {
        items.push({
            deskripsi: $(this).find('[name="item_desc[]"]').val(),
            qty: parseFloat($(this).find('[name="item_qty[]"]').val()),
            harga: parseFloat($(this).find('[name="item_price[]"]').val())
        });
    });

    const data = {
        id: $('#invoice-id').val(),
        pelanggan_id: $('#customer-select').val(),
        tanggal: $('#invoice-date').val(),
        jatuh_tempo: $('#due-date').val(),
        pajak: parseFloat($('#tax-input').val()) || 0,
        status: $('#invoice-status').val(),
        items: items
    };

    const url = data.id ? '/api/admin/invoices.php?action=update' : '/api/admin/invoices.php?action=create';

    $.ajax({
        url: url,
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            if (response.success) {
                alert(response.message);
                invoiceModal.hide();
                loadInvoices();
                loadSummary();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Failed to save invoice');
        }
    });
}

function viewInvoice(id) {
    $.get('/api/admin/invoices.php?action=get&id=' + id)
    .done(function(response) {
        if (response.success && response.data) {
            const invoice = response.data;
            let itemsHtml = '';

            if (invoice.items && invoice.items.length > 0) {
                invoice.items.forEach(item => {
                    itemsHtml += `
                        <tr>
                            <td>${item.deskripsi}</td>
                            <td class="text-end">${item.qty}</td>
                            <td class="text-end">${formatRupiah(item.harga)}</td>
                            <td class="text-end">${formatRupiah(item.subtotal)}</td>
                        </tr>
                    `;
                });
            }

            const content = `
                <div class="invoice-view">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Invoice: ${invoice.nomor}</h5>
                            <p class="mb-1"><strong>Date:</strong> ${formatDate(invoice.tanggal)}</p>
                            <p class="mb-1"><strong>Due Date:</strong> ${formatDate(invoice.jatuh_tempo)}</p>
                            <p class="mb-1"><strong>Status:</strong> ${getStatusBadge(invoice.status)}</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <h6>Bill To:</h6>
                            <p class="mb-1"><strong>${invoice.customer_name}</strong></p>
                            <p class="mb-1">${invoice.kode_pelanggan}</p>
                            <p class="mb-1">${invoice.alamat || ''}</p>
                            <p class="mb-1">${invoice.telepon || ''}</p>
                            <p class="mb-1">${invoice.email || ''}</p>
                        </div>
                    </div>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th class="text-end" width="10%">Qty</th>
                                <th class="text-end" width="20%">Price</th>
                                <th class="text-end" width="20%">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${itemsHtml}
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                <td class="text-end">${formatRupiah(invoice.subtotal)}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Tax:</strong></td>
                                <td class="text-end">${formatRupiah(invoice.pajak)}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                <td class="text-end"><strong>${formatRupiah(invoice.total)}</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            `;

            $('#invoice-view-content').html(content);
            currentInvoiceId = id;
            viewInvoiceModal.show();
        }
    });
}

function editInvoice(id) {
    $.get('/api/admin/invoices.php?action=get&id=' + id)
    .done(function(response) {
        if (response.success && response.data) {
            const invoice = response.data;

            $('#invoiceModalTitle').text('Edit Invoice');
            $('#invoice-id').val(invoice.id);
            $('#customer-select').val(invoice.pelanggan_id);
            $('#invoice-date').val(invoice.tanggal);
            $('#due-date').val(invoice.jatuh_tempo);
            $('#tax-input').val(invoice.pajak);
            $('#invoice-status').val(invoice.status);

            // Load items
            $('#invoice-items').empty();
            if (invoice.items && invoice.items.length > 0) {
                invoice.items.forEach(item => {
                    addInvoiceItem();
                    const lastItem = $('.invoice-item').last();
                    lastItem.find('[name="item_desc[]"]').val(item.deskripsi);
                    lastItem.find('[name="item_qty[]"]').val(item.qty);
                    lastItem.find('[name="item_price[]"]').val(item.harga);
                });
            } else {
                addInvoiceItem();
            }

            calculateTotal();
            invoiceModal.show();
        }
    });
}

function sendInvoice(id) {
    if (!confirm('Send this invoice to customer?')) return;

    $.post('/api/admin/invoices.php?action=send', { id: id })
    .done(function(response) {
        if (response.success) {
            alert(response.message);
            loadInvoices();
            loadSummary();
        } else {
            alert('Error: ' + response.message);
        }
    });
}

function markPaid(id) {
    if (!confirm('Mark this invoice as paid?')) return;

    $.post('/api/admin/invoices.php?action=mark_paid', { id: id })
    .done(function(response) {
        if (response.success) {
            alert(response.message);
            loadInvoices();
            loadSummary();
        } else {
            alert('Error: ' + response.message);
        }
    });
}

function deleteInvoice(id) {
    if (!confirm('Are you sure you want to delete this invoice?')) return;

    $.post('/api/admin/invoices.php?action=delete', { id: id })
    .done(function(response) {
        if (response.success) {
            alert(response.message);
            loadInvoices();
            loadSummary();
        } else {
            alert('Error: ' + response.message);
        }
    });
}

function clearFilters() {
    $('#status-filter').val('');
    $('#search-filter').val('');
    $('#start-date-filter').val('');
    $('#end-date-filter').val('');
    loadInvoices();
}

function printInvoice() {
    const content = $('#invoice-view-content').html();
    const printWindow = window.open('', '', 'height=600,width=800');
    printWindow.document.write('<html><head><title>Print Invoice</title>');
    printWindow.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">');
    printWindow.document.write('</head><body class="p-4">');
    printWindow.document.write(content);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}

function formatRupiah(amount) {
    return 'Rp ' + parseFloat(amount).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' });
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

