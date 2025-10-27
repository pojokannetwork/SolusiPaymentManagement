<?php
session_start();
require_once 'config/app.php';
require_once 'config/database.php';

if ($_POST) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        global $db;
        $db = Database::getInstance();
        
        $user = $db->fetchOne(
            "SELECT id, email, password_hash, nama, role FROM pengguna WHERE email = ? AND is_active = 1",
            [$email]
        );
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['nama'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['login_time'] = time();
            $_SESSION['last_regeneration'] = time();
            
            echo "<div class='alert alert-success'>✅ Login berhasil! Redirect ke dashboard...</div>";
            echo "<div class='mt-3'>";
            echo "<a href='admin/' class='btn btn-primary me-2'>🔗 Admin Dashboard</a>";
            echo "<a href='admin_dashboard.html' class='btn btn-secondary'>📊 Static Dashboard</a>";
            echo "</div>";
            echo "<script>setTimeout(function(){ window.location.href = 'admin/'; }, 3000);</script>";
        } else {
            echo "<div class='alert alert-danger'>❌ Email atau password salah!</div>";
        }
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>❌ Error: " . $e->getMessage() . "</div>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Login - SolusiPaymentManagement</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>🔐 Simple Login Test</h4>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label>Email:</label>
                            <input type="email" name="email" class="form-control" value="admin@solusipayment.local" required>
                        </div>
                        <div class="mb-3">
                            <label>Password:</label>
                            <input type="password" name="password" class="form-control" value="Admin123!" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Login</button>
                    </form>
                    
                    <hr>
                    <small class="text-muted">
                        Kredensial default:<br>
                        Email: admin@solusipayment.local<br>
                        Password: Admin123!
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>