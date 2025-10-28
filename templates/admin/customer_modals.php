<!-- Create/Edit Customer Modal -->
<div class="modal fade" id="customerModal" tabindex="-1" data-bs-backdrop="false">
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
                            <input type="tel" class="form-control" id="telp" name="telp" inputmode="tel" pattern="^\+?[0-9]{8,15}$" placeholder="08xxxxxxxxxx">
                            <div class="form-text">Gunakan angka saja, 8-15 digit (boleh awalan +)</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Package *</label>
                            <select class="form-select" id="paket" name="paket" required>
                                <option value="">Select Package</option>
                            </select>
                            <div class="form-text">Diisi dari menu ISP Packages</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" id="alamat" name="alamat" rows="3"></textarea>
                        </div>
                        <!-- Location (Map) -->
                        <div class="col-12">
                            <label class="form-label">Location</label>
                            <div class="row g-2">
                                <div class="col-md-8">
                                    <input type="text" class="form-control" id="address-search" placeholder="Cari alamat... (contoh: Jl. Sudirman, Jakarta)">
                                </div>
                                <div class="col-md-4">
                                    <div id="search-results" class="list-group" style="max-height: 140px; overflow:auto;"></div>
                                </div>
                            </div>
                            <div class="row g-2 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Latitude</label>
                                    <input type="number" step="any" class="form-control" id="lat" name="lat" placeholder="-6.2">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Longitude</label>
                                    <input type="number" step="any" class="form-control" id="lon" name="lon" placeholder="106.8">
                                </div>
                                <div class="col-md-4 d-grid">
                                    <button type="button" class="btn btn-outline-secondary" id="btn-current-location">
                                        <i class="fas fa-location-crosshairs me-2"></i>Use current location
                                    </button>
                                </div>
                            </div>
                            <div class="mt-2 border rounded" id="customer-map" style="height: 260px; background: #f8f9fa;"></div>
                            <div class="form-text">Klik pada peta untuk memilih lokasi pelanggan.</div>
                        </div>

                        <!-- Mitra & Bagi Hasil -->
                        <div class="col-12 mt-3">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Mitra</label>
                                    <select class="form-select" id="mitra_id" name="mitra_id">
                                        <option value="">Pilih Mitra</option>
                                    </select>
                                    <div class="form-text">Opsional. Pilih mitra yang bertanggung jawab di daerah pelanggan.</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Daerah</label>
                                    <input type="text" class="form-control" id="mitra_region" name="mitra_region" placeholder="Kecamatan/Area">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Bagi Hasil (%)</label>
                                    <input type="number" class="form-control" id="mitra_share" name="mitra_share" step="0.01" min="0" max="100" placeholder="0-100">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">PPPoE Username</label>
                            <input type="text" class="form-control" id="pppoe_user" name="pppoe_user">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">PPPoE Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="pppoe_pass" name="pppoe_pass" minlength="4" placeholder="Minimal 4 karakter">
                                <button class="btn btn-outline-secondary" type="button" id="pppoe_toggle" title="Show/Hide">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Router / Authentication Method</label>
                            <select class="form-select" id="router_id" name="router_id">
                                <option value="">Select Router/Method</option>
                                <option value="radius">RADIUS (Central Authentication)</option>
                            </select>
                            <div class="form-text">
                                <small>
                                    <strong>RADIUS:</strong> Central authentication server<br>
                                    <strong>Mikrotik Router:</strong> Direct router management
                                </small>
                            </div>
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
                        
                        <!-- Billing System Fields -->
                        <div class="col-12 mt-4">
                            <h6 class="text-primary"><i class="fas fa-credit-card me-2"></i>Billing System</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Sistem Pembayaran *</label>
                            <select class="form-select" id="sistem_bayar" name="sistem_bayar" required>
                                <option value="postpaid">Pasca Bayar (Postpaid)</option>
                                <option value="prepaid">Pra Bayar (Prepaid)</option>
                            </select>
                            <div class="form-text">
                                <small>
                                    <strong>Postpaid:</strong> Bayar di akhir periode (tagihan bulanan)<br>
                                    <strong>Prepaid:</strong> Bayar di awal periode (voucher/token)
                                </small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Aktif</label>
                            <input type="date" class="form-control" id="tanggal_aktif" name="tanggal_aktif">
                            <div class="form-text">Tanggal mulai aktif layanan. Kosongkan untuk hari ini.</div>
                        </div>
                        
                        <div class="col-md-6" id="postpaid-fields">
                            <label class="form-label">Siklus Tagihan</label>
                            <select class="form-select" id="cycle_billing" name="cycle_billing">
                                <option value="1">Tanggal 1 setiap bulan</option>
                                <option value="2">Tanggal 2 setiap bulan</option>
                                <option value="3">Tanggal 3 setiap bulan</option>
                                <option value="5">Tanggal 5 setiap bulan</option>
                                <option value="10">Tanggal 10 setiap bulan</option>
                                <option value="15">Tanggal 15 setiap bulan</option>
                                <option value="20">Tanggal 20 setiap bulan</option>
                                <option value="25">Tanggal 25 setiap bulan</option>
                            </select>
                            <div class="form-text">Tanggal generate tagihan bulanan</div>
                        </div>
                        
                        <div class="col-md-6" id="isolir-fields">
                            <label class="form-label">Tanggal Isolir</label>
                            <input type="date" class="form-control" id="tanggal_isolir" name="tanggal_isolir">
                            <div class="form-text">Tanggal isolir otomatis jika belum bayar. Kosongkan untuk auto-calculate.</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Grace Period (Hari)</label>
                            <input type="number" class="form-control" id="grace_period" name="grace_period" min="0" max="30" value="7">
                            <div class="form-text">Masa tenggang sebelum isolir (0-30 hari)</div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="auto_isolir" name="auto_isolir" checked>
                                <label class="form-check-label" for="auto_isolir">
                                    <strong>Auto Isolir</strong>
                                </label>
                                <div class="form-text">Isolir otomatis jika lewat tanggal isolir</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Profile Aktif</label>
                            <select class="form-select" id="profile_aktif" name="profile_aktif">
                                <option value="default">default</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Profile Isolir</label>
                            <select class="form-select" id="profile_isolir" name="profile_isolir">
                                <option value="ISOLIR">ISOLIR</option>
                            </select>
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

<!-- WhatsApp Modal -->
<div class="modal fade" id="whatsAppModal" tabindex="-1" data-bs-backdrop="false">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="whatsAppModalLabel"><i class="fab fa-whatsapp me-2 text-success"></i>WhatsApp Notification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="whatsapp-alert"></div>
                <div class="row g-3">
                    <div class="col-lg-5">
                        <div class="mb-3">
                            <div class="text-muted small">WhatsApp Number</div>
                            <div class="fw-semibold" id="wa-phone">-</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">WhatsApp Link</div>
                            <div class="input-group">
                                <input type="text" class="form-control" id="wa-link" readonly>
                                <button class="btn btn-outline-secondary" type="button" id="wa-open-link" disabled>
                                    <i class="fas fa-external-link-alt"></i>
                                </button>
                            </div>
                        </div>
                        <button class="btn btn-outline-secondary btn-sm" id="wa-copy-message">
                            <i class="fas fa-copy me-2"></i>Copy Message
                        </button>
                    </div>
                    <div class="col-lg-4">
                        <div class="border rounded-3 p-3 bg-light h-100">
                            <div class="text-muted small mb-2">Message Content</div>
                            <pre id="wa-message" class="mb-0" style="white-space: pre-wrap; word-break: break-word; font-family: inherit;"></pre>
                        </div>
                    </div>
                    <div class="col-lg-3 d-flex flex-column align-items-center">
                        <div class="border rounded-4 p-3 bg-white mb-3" style="width: 220px; height: 220px; display: grid; place-items: center;">
                            <img id="wa-qr" src="" alt="WhatsApp QR Code" style="max-width: 100%; display: none;">
                            <span class="text-muted small" id="wa-qr-placeholder">QR will appear here</span>
                        </div>
                        <button class="btn btn-outline-success btn-sm" id="wa-download-qr" disabled>
                            <i class="fas fa-download me-2"></i>Download QR
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-success" id="wa-queue">
                    <i class="fas fa-paper-plane me-2"></i>Queue Sending
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
