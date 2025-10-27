<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SolusiPaymentManagement</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 25px 45px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-body {
            padding: 2rem;
        }
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #4facfe;
            box-shadow: 0 0 0 0.2rem rgba(79, 172, 254, 0.25);
            transform: translateY(-2px);
        }
        .btn-primary {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(79, 172, 254, 0.3);
        }
        .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
        }
        .form-select:focus {
            border-color: #4facfe;
            box-shadow: 0 0 0 0.2rem rgba(79, 172, 254, 0.25);
        }
        .demo-info {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 col-xl-5">
                <div class="login-container">
                    <div class="login-header">
                        <h2 class="mb-2">
                            <i class="fas fa-building me-3"></i>SolusiPaymentManagement
                        </h2>
                        <p class="mb-0 opacity-90">Sistem Manajemen Pembayaran & ISP</p>
                    </div>
                    <div class="login-body">

                        
<?php
session_start();
require_once 'includes/security.php';
$csrf_token = getCsrfToken();
?>
                        <form id="loginForm" method="POST" action="/login">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                            <div class="mb-4">
                                <label for="role" class="form-label fw-semibold">
                                    <i class="fas fa-user-tag me-2"></i>Role
                                </label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">Pilih Role</option>
                                    <option value="admin">Administrator</option>
                                    <option value="employee">Karyawan</option>
                                    <option value="customer">Pelanggan</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="email" class="form-label fw-semibold">
                                    <i class="fas fa-envelope me-2"></i>Email
                                </label>
                                <input type="email" class="form-control" id="email" name="email"
                                       placeholder="Masukkan alamat email" required>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label fw-semibold">
                                    <i class="fas fa-lock me-2"></i>Password
                                </label>
                                <input type="password" class="form-control" id="password" name="password"
                                       placeholder="Masukkan password" required>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg" id="loginBtn">
                                    <i class="fas fa-sign-in-alt me-2"></i>Masuk ke Sistem
                                </button>
                            </div>
                        </form>

                        <div class="demo-info text-center">
                            <small class="text-dark fw-semibold">
                                <i class="fas fa-info-circle me-2"></i>Demo Account<br>
                                Email: admin@solusipayment.local<br>
                                Password: Admin123!
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
    <script>
        $(document).ready(function() {
            // Form animation
            $('.form-control, .form-select').on('focus', function() {
                $(this).parent().addClass('focused');
            }).on('blur', function() {
                $(this).parent().removeClass('focused');
            });

            // Login form submission
            $('#loginForm').on('submit', function(e) {
                $('#loginBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Masuk...');
            });

            // Add floating particles effect
            createParticles();
        });

        function createParticles() {
            const container = $('body');
            for (let i = 0; i < 50; i++) {
                const particle = $('<div>').css({
                    position: 'fixed',
                    width: Math.random() * 4 + 2 + 'px',
                    height: Math.random() * 4 + 2 + 'px',
                    background: 'rgba(255,255,255,0.5)',
                    borderRadius: '50%',
                    left: Math.random() * 100 + '%',
                    top: Math.random() * 100 + '%',
                    zIndex: -1,
                    animation: `float ${Math.random() * 3 + 2}s ease-in-out infinite alternate`
                });
                container.append(particle);
            }
        }

        // CSS animation for particles
        $('<style>').text(`
            @keyframes float {
                0% { transform: translateY(0px); }
                100% { transform: translateY(-20px); }
            }
        `).appendTo('head');
    </script>
</body>
</html>
