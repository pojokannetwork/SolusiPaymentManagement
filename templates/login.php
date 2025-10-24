<?php
// SolusiPaymentManagement Login Template
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SolusiPaymentManagement - Sistem Manajemen Pembayaran & ISP Terdepan</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --accent-color: #3b82f6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        /* Hero Section */
        .hero-section {
            min-block-size: 100vh;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            position: relative;
            display: flex;
            align-items: center;
        }

        .hero-overlay {
            position: absolute;
            inset-block-start: 0;
            inset-inline-start: 0;
            inset-inline-end: 0;
            inset-block-end: 0;
            background: rgba(0, 0, 0, 0.3);
        }

        .hero-content {
            position: relative;
            z-index: 2;
            color: white;
        }

        /* Company Slider */
        .company-slider {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .slide-item {
            min-block-size: 400px;
            display: flex;
            align-items: center;
            padding: 3rem;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }

        .slide-content h3 {
            color: var(--primary-color);
            font-weight: 700;
            margin-block-end: 1rem;
        }

        .slide-content p {
            color: var(--dark-color);
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .slide-icon {
            font-size: 4rem;
            color: var(--accent-color);
            margin-block-end: 1rem;
        }

        /* Login Form */
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 2rem;
            text-align: center;
        }

        .login-header h2 {
            font-weight: 700;
            margin-block-end: 0.5rem;
        }

        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        }

        .btn-login {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            border: none;
            padding: 0.75rem 2rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
        }

        /* Features Section */
        .features-section {
            padding: 5rem 0;
            background: var(--light-color);
        }

        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: none;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .feature-icon {
            inline-size: 80px;
            block-size: 80px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 2rem;
        }

        /* Stats Section */
        .stats-section {
            background: var(--dark-color);
            color: white;
            padding: 3rem 0;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: var(--accent-color);
            display: block;
        }

        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Responsive */
        @media (max-inline-size: 768px) {
            .hero-section {
                padding: 2rem 0;
            }
            
            .slide-item {
                min-block-size: 300px;
                padding: 2rem 1rem;
                text-align: center;
            }
            
            .login-card {
                margin: 1rem;
            }
        }

        /* Custom Carousel */
        .carousel-control-prev,
        .carousel-control-next {
            inline-size: 5%;
            color: var(--primary-color);
        }

        .carousel-control-prev-icon,
        .carousel-control-next-icon {
            background-color: var(--primary-color);
            border-radius: 50%;
            padding: 20px;
        }

        .carousel-indicators button {
            background-color: var(--primary-color);
            inline-size: 12px;
            block-size: 12px;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark position-fixed w-100" style="z-index: 1000; background: rgba(37, 99, 235, 0.9); backdrop-filter: blur(10px);">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#home">
                <i class="fas fa-building me-2"></i>
                SolusiPaymentManagement
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Fitur</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">Tentang</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#login">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section d-flex align-items-center">
        <div class="hero-overlay"></div>
        <div class="container">
            <div class="row align-items-center gy-5">
                <!-- Company Presentation -->
                <div class="col-lg-7" data-aos="fade-right">
                    <div class="company-slider">
                        <div id="companyCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">
                            <div class="carousel-indicators">
                                <button type="button" data-bs-target="#companyCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                                <button type="button" data-bs-target="#companyCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                                <button type="button" data-bs-target="#companyCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
                                <button type="button" data-bs-target="#companyCarousel" data-bs-slide-to="3" aria-label="Slide 4"></button>
                            </div>
                            <div class="carousel-inner">
                                <div class="carousel-item active">
                                    <div class="slide-item">
                                        <div class="row align-items-center g-4">
                                            <div class="col-md-3 text-center">
                                                <i class="fas fa-building slide-icon"></i>
                                            </div>
                                            <div class="col-md-9">
                                                <div class="slide-content">
                                                    <h3>SolusiPaymentManagement</h3>
                                                    <p>Platform terintegrasi yang memudahkan pengelolaan pelanggan, pembayaran, dan operasional ISP dalam satu dashboard modern dan aman.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="carousel-item">
                                    <div class="slide-item">
                                        <div class="row align-items-center g-4">
                                            <div class="col-md-3 text-center">
                                                <i class="fas fa-credit-card slide-icon"></i>
                                            </div>
                                            <div class="col-md-9">
                                                <div class="slide-content">
                                                    <h3>Payment Gateway Terintegrasi</h3>
                                                    <p>Dukungan Midtrans, Xendit, transfer bank, dan e-wallet dengan verifikasi otomatis serta notifikasi real-time untuk mempercepat arus kas.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="carousel-item">
                                    <div class="slide-item">
                                        <div class="row align-items-center g-4">
                                            <div class="col-md-3 text-center">
                                                <i class="fas fa-wifi slide-icon"></i>
                                            </div>
                                            <div class="col-md-9">
                                                <div class="slide-content">
                                                    <h3>Manajemen ISP Lengkap</h3>
                                                    <p>Integrasi Mikrotik RouterOS, isolir otomatis, pemantauan bandwidth, dan dukungan NOC assistant untuk menjaga kualitas layanan pelanggan.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="carousel-item">
                                    <div class="slide-item">
                                        <div class="row align-items-center g-4">
                                            <div class="col-md-3 text-center">
                                                <i class="fas fa-chart-line slide-icon"></i>
                                            </div>
                                            <div class="col-md-9">
                                                <div class="slide-content">
                                                    <h3>Analytics & Business Intelligence</h3>
                                                    <p>Dashboard interaktif dengan insight pendapatan, retensi pelanggan, hingga forecasting untuk keputusan strategis yang lebih cepat dan akurat.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button class="carousel-control-prev" type="button" data-bs-target="#companyCarousel" data-bs-slide="prev" aria-label="Sebelumnya">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#companyCarousel" data-bs-slide="next" aria-label="Berikutnya">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Login Form -->
                <div class="col-lg-5" data-aos="fade-left">
                    <div class="login-card" id="login">
                        <div class="login-header">
                            <h2><i class="fas fa-sign-in-alt me-2"></i>Portal Login</h2>
                            <p class="mb-0">Masuk ke sistem manajemen</p>
                        </div>
                        <div class="p-4 p-lg-5">
                            <?php if (isset($_GET['message'])): ?>
                                <div class="alert alert-info alert-dismissible fade show">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <?php echo htmlspecialchars($_GET['message']); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <form id="loginForm" method="POST" action="/login">
                                <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">

                                <div class="mb-3">
                                    <label for="email" class="form-label fw-semibold">
                                        <i class="fas fa-envelope me-2"></i>Email Address
                                    </label>
                                    <input type="email" class="form-control form-control-lg" id="email" name="email" placeholder="masukkan@email.anda" required>
                                </div>

                                <div class="mb-4">
                                    <label for="password" class="form-label fw-semibold">
                                        <i class="fas fa-lock me-2"></i>Password
                                    </label>
                                    <input type="password" class="form-control form-control-lg" id="password" name="password" placeholder="Masukkan password" required>
                                </div>

                                <div class="d-grid mb-3">
                                    <button type="submit" class="btn btn-primary btn-login btn-lg" id="loginBtn">
                                        <i class="fas fa-sign-in-alt me-2"></i>Masuk ke Sistem
                                    </button>
                                </div>
                            </form>

                            <hr class="my-4">

                            <div class="text-center">
                                <h6 class="text-muted mb-3">Akun Demo Testing</h6>
                                <div class="bg-light p-3 rounded">
                                    <strong class="text-primary d-block">Administrator</strong>
                                    <small class="text-muted">
                                        Email: admin@solusipayment.local<br>
                                        Password: Admin123!
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="fw-bold">Solusi Lengkap Untuk Operasional Anda</h2>
                <p class="text-muted">Dibangun khusus untuk kebutuhan provider internet dan bisnis pembayaran digital modern.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card h-100 text-center">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4>Manajemen Pelanggan</h4>
                        <p>Pencatatan data pelanggan lengkap, histori pembayaran, dan status layanan dalam satu tampilan.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card h-100 text-center">
                        <div class="feature-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h4>Call Center & Ticketing</h4>
                        <p>Integrasi helpdesk dengan pencatatan aktivitas, SLA pengaduan, dan eskalasi otomatis.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-card h-100 text-center">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4>Keamanan Berlapis</h4>
                        <p>Proteksi CSRF, autentikasi berlapis, dan pencatatan aktivitas untuk mencegah akses tidak sah.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="400">
                    <div class="feature-card h-100 text-center">
                        <div class="feature-icon">
                            <i class="fas fa-network-wired"></i>
                        </div>
                        <h4>Integrasi Mikrotik</h4>
                        <p>Radius COA, isolir otomatis, dan monitor jaringan langsung dari dashboard.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="500">
                    <div class="feature-card h-100 text-center">
                        <div class="feature-icon">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <h4>Penagihan Otomatis</h4>
                        <p>Generasi invoice, pengingat otomatis via email/SMS, dan laporan aging piutang.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="600">
                    <div class="feature-card h-100 text-center">
                        <div class="feature-icon">
                            <i class="fas fa-cloud"></i>
                        </div>
                        <h4>Solusi Cloud</h4>
                        <p>Arsitektur fleksibel siap di-deploy on-premise ataupun cloud dengan skalabilitas tinggi.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-item">
                        <span class="stat-number" data-count="250">250+</span>
                        <span class="stat-label">Provider Terbantu</span>
                    </div>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-item">
                        <span class="stat-number" data-count="95">95%</span>
                        <span class="stat-label">Efisiensi Penagihan</span>
                    </div>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="300">
                    <div class="stat-item">
                        <span class="stat-number" data-count="12000">12K</span>
                        <span class="stat-label">Transaksi Per Hari</span>
                    </div>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="400">
                    <div class="stat-item">
                        <span class="stat-number" data-count="24">24/7</span>
                        <span class="stat-label">Monitoring Aktif</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-5">
        <div class="container">
            <div class="row align-items-center gy-4">
                <div class="col-lg-6" data-aos="fade-right">
                    <img src="https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=900&q=80" class="img-fluid rounded-4 shadow" alt="Tim SolusiPaymentManagement">
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <h2 class="fw-bold mb-3">Kenapa Memilih SolusiPaymentManagement?</h2>
                    <p class="text-muted">Kami hadir sebagai mitra teknologi yang membantu ISP dan bisnis pembayaran mengoptimalkan operasional. Dengan pengalaman panjang di industri broadband, kami memahami kebutuhan lapangan dan menghadirkannya dalam solusi siap pakai.</p>
                    <ul class="list-unstyled">
                        <li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i> Implementasi cepat dan dukungan tim profesional.</li>
                        <li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i> Roadmap fitur yang terus berkembang mengikuti kebutuhan industri.</li>
                        <li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i> Sistem keamanan dan pencatatan aktivitas tingkat enterprise.</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4" style="background: #0f172a; color: #e2e8f0;">
        <div class="container text-center">
            <p class="mb-1">&copy; <?php echo date('Y'); ?> SolusiPaymentManagement. All rights reserved.</p>
            <small class="text-muted">Menghubungkan layanan internet dan pembayaran dengan teknologi terbaik.</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS animations when page becomes interactive
        AOS.init({
            duration: 800,
            once: true
        });

        // Simple number animation for stats
        document.querySelectorAll('[data-count]').forEach(function (element) {
            var target = parseInt(element.getAttribute('data-count'), 10);
            var current = 0;
            var step = Math.max(1, Math.floor(target / 60));

            var interval = setInterval(function () {
                current += step;
                if (current >= target) {
                    element.textContent = target.toLocaleString();
                    clearInterval(interval);
                } else {
                    element.textContent = current.toLocaleString();
                }
            }, 20);
        });
    </script>
</body>
</html>
