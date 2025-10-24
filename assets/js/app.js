// SolusiPaymentManagement JavaScript

$(document).ready(function() {
    // Initialize DataTables
    $('.data-table').DataTable({
        responsive: true,
        pageLength: 25,
        language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ entri per halaman",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 entri",
            infoFiltered: "(difilter dari _MAX_ total entri)",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            }
        }
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // CSRF token setup for AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Global AJAX error handler
    $(document).ajaxError(function(event, xhr, settings, thrownError) {
        if (xhr.status === 401) {
            // Unauthorized - redirect to login
            window.location.href = '/';
        } else if (xhr.status === 403) {
            // Forbidden
            showAlert('Akses ditolak', 'danger');
        } else if (xhr.status >= 500) {
            // Server error
            showAlert('Terjadi kesalahan server', 'danger');
        }
    });
});

// Show alert message
function showAlert(message, type = 'info', duration = 5000) {
    const alertClass = `alert-${type}`;
    const alertHtml = `<div class="alert ${alertClass} alert-dismissible fade show" role="alert">
        ${message}
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>`;

    // Remove existing alerts
    $('.alert').remove();

    // Add new alert
    $('.main-content').prepend(alertHtml);

    // Auto dismiss
    if (duration > 0) {
        setTimeout(() => {
            $('.alert').fadeOut();
        }, duration);
    }
}

// Confirm dialog
function confirmDialog(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Format currency
function formatCurrency(amount, currency = 'IDR') {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: currency
    }).format(amount);
}

// Format date
function formatDate(dateString, options = {}) {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        ...options
    });
}

// Format date and time
function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('id-ID', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Show loading spinner
function showLoading(element = '.main-content') {
    $(element).append('<div class="text-center mt-3"><div class="spinner"></div></div>');
}

// Hide loading spinner
function hideLoading(element = '.main-content') {
    $(element).find('.spinner').parent().remove();
}

// AJAX form submission
function submitForm(formSelector, successCallback = null, errorCallback = null) {
    const form = $(formSelector);
    const formData = new FormData(form[0]);

    showLoading(form.closest('.card-body'));

    $.ajax({
        url: form.attr('action'),
        type: form.attr('method') || 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            hideLoading(form.closest('.card-body'));

            if (response.success) {
                showAlert(response.message || 'Data berhasil disimpan', 'success');
                if (successCallback) successCallback(response);
            } else {
                showAlert(response.message || 'Terjadi kesalahan', 'danger');
                if (errorCallback) errorCallback(response);
            }
        },
        error: function(xhr) {
            hideLoading(form.closest('.card-body'));
            const response = xhr.responseJSON;
            if (response && response.errors) {
                // Show field errors
                Object.keys(response.errors).forEach(field => {
                    const input = $(`[name="${field}"]`);
                    input.addClass('is-invalid');
                    input.after(`<div class="invalid-feedback">${response.errors[field]}</div>`);
                });
            }
            if (errorCallback) errorCallback(xhr);
        }
    });
}

// Modal management
function openModal(modalId) {
    $(modalId).show();
}

function closeModal(modalId) {
    $(modalId).hide();
}

// Click outside modal to close
$(document).on('click', '.modal', function(e) {
    if (e.target === this) {
        $(this).hide();
    }
});

// Customer map functions
function initCustomerMap() {
    if (typeof L === 'undefined') return;

    const map = L.map('map').setView([-6.2, 106.816666], 10);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Load customers with coordinates
    $.get('/api/admin/customers/list?map=1')
        .done(function(response) {
            if (response.success && response.data) {
                const markers = L.markerClusterGroup();

                response.data.forEach(customer => {
                    if (customer.lat && customer.lon) {
                        const marker = L.marker([customer.lat, customer.lon]);
                        const popupContent = `
                            <strong>${customer.nama}</strong><br>
                            ${customer.alamat}<br>
                            Status: <span class="badge badge-${customer.status === 'active' ? 'success' : 'danger'}">${customer.status}</span><br>
                            <button class="btn btn-sm btn-primary mt-2" onclick="viewCustomer(${customer.id})">Lihat Detail</button>
                        `;
                        marker.bindPopup(popupContent);
                        markers.addLayer(marker);
                    }
                });

                map.addLayer(markers);
                map.fitBounds(markers.getBounds());
            }
        });

    return map;
}

// View customer details
function viewCustomer(id) {
    window.location.href = `/admin/customers.php?action=view&id=${id}`;
}

// Dashboard chart functions
function initRevenueChart() {
    if (typeof Chart === 'undefined') return;

    $.get('/api/admin/dashboard/revenue-chart')
        .done(function(response) {
            if (response.success) {
                const ctx = document.getElementById('revenueChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: response.data.labels,
                        datasets: [{
                            label: 'Pendapatan',
                            data: response.data.values,
                            borderColor: 'rgb(75, 192, 192)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return formatCurrency(value);
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
}

function initStatusChart() {
    if (typeof Chart === 'undefined') return;

    $.get('/api/admin/dashboard/status-chart')
        .done(function(response) {
            if (response.success) {
                const ctx = document.getElementById('statusChart').getContext('2d');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: response.data.labels,
                        datasets: [{
                            data: response.data.values,
                            backgroundColor: [
                                'rgb(40, 167, 69)',
                                'rgb(220, 53, 69)',
                                'rgb(255, 193, 7)',
                                'rgb(108, 117, 125)'
                            ]
                        }]
                    },
                    options: {
                        responsive: true
                    }
                });
            }
        });
}

// Payment gateway functions
function testPaymentGateway(provider) {
    showLoading();
    $.post(`/api/admin/payment_gateways/test/${provider}`)
        .done(function(response) {
            hideLoading();
            if (response.success) {
                showAlert('Koneksi berhasil', 'success');
            } else {
                showAlert(response.message || 'Koneksi gagal', 'danger');
            }
        })
        .fail(function() {
            hideLoading();
            showAlert('Koneksi gagal', 'danger');
        });
}

// MikroTik functions
function testMikroTikConnection(routerId) {
    showLoading();
    $.post('/api/admin/mikrotik/test', { router_id: routerId })
        .done(function(response) {
            hideLoading();
            if (response.success) {
                showAlert('Koneksi MikroTik berhasil', 'success');
            } else {
                showAlert(response.message || 'Koneksi gagal', 'danger');
            }
        })
        .fail(function() {
            hideLoading();
            showAlert('Koneksi gagal', 'danger');
        });
}

// Customer isolation/activation
function toggleCustomerStatus(customerId, action) {
    const actionText = action === 'isolate' ? 'isolir' : 'aktifkan';
    confirmDialog(`Apakah Anda yakin ingin ${actionText} pelanggan ini?`, function() {
        showLoading();
        $.post(`/api/admin/customers/${action}`, { customer_id: customerId })
            .done(function(response) {
                hideLoading();
                if (response.success) {
                    showAlert(`Pelanggan berhasil di${actionText}`, 'success');
                    location.reload();
                } else {
                    showAlert(response.message || 'Operasi gagal', 'danger');
                }
            })
            .fail(function() {
                hideLoading();
                showAlert('Operasi gagal', 'danger');
            });
    });
}

// File upload preview
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            $(`#${previewId}`).attr('src', e.target.result).show();
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Export functions
function exportData(type, format = 'csv') {
    const url = `/api/admin/export/${type}?format=${format}`;
    window.open(url, '_blank');
}

// Print functions
function printElement(elementId) {
    const printContent = document.getElementById(elementId).innerHTML;
    const originalContent = document.body.innerHTML;

    document.body.innerHTML = printContent;
    window.print();
    document.body.innerHTML = originalContent;
}

// Real-time updates (if needed)
function initRealTimeUpdates() {
    // WebSocket or polling for real-time updates
    // Implementation depends on requirements
}

// Initialize page-specific functions
$(document).ready(function() {
    // Dashboard
    if ($('#revenueChart').length) {
        initRevenueChart();
    }
    if ($('#statusChart').length) {
        initStatusChart();
    }

    // Customer map
    if ($('#map').length) {
        initCustomerMap();
    }

    // Real-time updates
    initRealTimeUpdates();
});
