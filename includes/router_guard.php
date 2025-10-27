<?php
// SolusiPaymentManagement Router Guard
// Handles authentication and authorization

class RouterGuard {
    private static $instance = null;
    private $currentUser = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Check if user is authenticated
    public function isAuthenticated() {
        return isset($_SESSION['user_id']) && isset($_SESSION['login_time']);
    }

    // Check if session is expired
    public function isSessionExpired() {
        if (!isset($_SESSION['login_time'])) {
            return true;
        }

        $sessionAge = time() - $_SESSION['login_time'];
        $defaultMinutes = max(1, intdiv(SESSION_LIFETIME, 60));
        $lifetimeMinutes = (int) getSetting('session_lifetime_minutes', $defaultMinutes);
        if ($lifetimeMinutes < 5 || $lifetimeMinutes > 480) {
            $lifetimeMinutes = $defaultMinutes;
        }
        $sessionLifetimeSeconds = $lifetimeMinutes * 60;
        return $sessionAge > $sessionLifetimeSeconds;
    }

    // Get current user with roles
    public function getCurrentUser() {
        if ($this->currentUser !== null) {
            return $this->currentUser;
        }

        if (!$this->isAuthenticated() || $this->isSessionExpired()) {
            return null;
        }

        global $db;
        $this->currentUser = $db->fetchOne(
            "SELECT p.*, GROUP_CONCAT(r.nama) as roles, GROUP_CONCAT(rp.izin_id) as permission_ids
             FROM pengguna p
             LEFT JOIN user_roles ur ON p.id = ur.user_id
             LEFT JOIN peran r ON ur.role_id = r.id
             LEFT JOIN role_permissions rp ON r.id = rp.role_id
             WHERE p.id = ? AND p.is_active = 1
             GROUP BY p.id",
            [$_SESSION['user_id']]
        );

        return $this->currentUser;
    }

    // Check if user has specific permission
    public function hasPermission($permissionCode) {
        $user = $this->getCurrentUser();
        if (!$user) return false;

        // Admin has all permissions
        if ($user['role'] === 'admin') return true;

        global $db;
        $result = $db->fetchOne(
            "SELECT COUNT(*) as count
             FROM role_permissions rp
             JOIN izin i ON rp.izin_id = i.id
             WHERE rp.role_id IN (
                 SELECT role_id FROM user_roles WHERE user_id = ?
             ) AND i.kode = ?",
            [$user['id'], $permissionCode]
        );

        return $result['count'] > 0;
    }

    // Check if user has any of the permissions
    public function hasAnyPermission($permissions) {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    // Require authentication for route
    public function requireAuth() {
        if (!$this->isAuthenticated()) {
            $this->redirectToLogin();
        }

        if ($this->isSessionExpired()) {
            $this->logout();
            $this->redirectToLogin('Session expired. Please login again.');
        }

        // Regenerate session ID periodically for security
        if (!isset($_SESSION['last_regeneration']) ||
            time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }

    // Require specific permission for route
    public function requirePermission($permission) {
        $this->requireAuth();

        if (!$this->hasPermission($permission)) {
            if (isApiRequest()) {
                errorResponse('Access denied', 403);
            } else {
                $this->showAccessDenied();
            }
        }
    }

    // Require any of the permissions
    public function requireAnyPermission($permissions) {
        $this->requireAuth();

        if (!$this->hasAnyPermission($permissions)) {
            if (isApiRequest()) {
                errorResponse('Access denied', 403);
            } else {
                $this->showAccessDenied();
            }
        }
    }

    // Check route access based on user role
    public function checkRouteAccess($path) {
        $user = $this->getCurrentUser();
        if (!$user) return false;

        // Define route permissions
        $routePermissions = [
            '/admin' => ['admin.dashboard'],
            '/admin/employees' => ['admin.employees'],
            '/admin/assets' => ['admin.assets'],
            '/admin/revenue' => ['admin.revenue'],
            '/admin/taxes' => ['admin.taxes'],
            '/admin/payroll' => ['admin.payroll'],
            '/admin/attendance' => ['admin.attendance'],
            '/admin/customers' => ['admin.customers'],
            '/admin/invoices' => ['admin.invoices'],
            '/admin/transactions' => ['admin.transactions'],
            '/admin/payment_gateways' => ['admin.payment_gateways'],
            '/admin/whatsapp_notifications' => ['admin.customers'],
            '/admin/roles' => ['admin.roles'],
            '/admin/activity_logs' => ['admin.activity_logs'],
            '/admin/reports' => ['admin.reports'],
            '/admin/noc_assistant' => ['admin.noc_assistant'],
            '/admin/call_center' => ['admin.call_center'],
            '/admin/customers_map' => ['admin.customers_map'],
            '/admin/settings' => ['admin.settings'],
            '/employee' => ['employee.dashboard'],
            '/customer' => ['customer.dashboard'],
            '/api/admin' => ['admin.dashboard'],
            '/api/employee' => ['employee.dashboard'],
            '/api/customer' => ['customer.dashboard']
        ];

        // Check exact matches first
        if (isset($routePermissions[$path])) {
            return $this->hasPermission($routePermissions[$path]);
        }

        // Check prefix matches
        foreach ($routePermissions as $route => $permission) {
            if (strpos($path, $route) === 0) {
                return $this->hasPermission($permission);
            }
        }

        // Default deny for unknown routes
        return false;
    }

    // Login user
    public function login($email, $password) {
        global $db;

        // Rate limiting check
        if (!checkRateLimit("login_{$email}")) {
            logSecurityEvent('login_rate_limited', ['email' => $email]);
            return ['success' => false, 'message' => 'Too many login attempts. Please try again later.'];
        }

        // Get user
        $user = $db->fetchOne(
            "SELECT * FROM pengguna WHERE email = ? AND is_active = 1",
            [$email]
        );

        if (!$user || !verifyPassword($password, $user['password_hash'])) {
            incrementRateLimit("login_{$email}");
            logSecurityEvent('login_failed', ['email' => $email]);
            return ['success' => false, 'message' => 'Invalid email or password'];
        }

        // Reset rate limit on successful login
        resetRateLimit("login_{$email}");

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_regeneration'] = time();
        $_SESSION['user_role'] = $user['role'];

        // Update last activity
        $db->execute(
            "UPDATE pengguna SET updated_at = NOW() WHERE id = ?",
            [$user['id']]
        );

        logSecurityEvent('login_success', ['email' => $email, 'role' => $user['role']]);

        return ['success' => true, 'user' => $user];
    }

    // Logout user
    public function logout() {
        $userId = $_SESSION['user_id'] ?? null;

        if ($userId) {
            logSecurityEvent('logout', ['user_id' => $userId]);
        }

        // Clear session
        session_unset();
        session_destroy();

        // Start new session to prevent session fixation
        session_start();
        session_regenerate_id(true);
    }

    // Redirect to login page
    private function redirectToLogin($message = null) {
        if (isApiRequest()) {
            errorResponse('Authentication required', 401);
        } else {
            $redirect = urlencode($_SERVER['REQUEST_URI']);
            $url = "/?login&redirect={$redirect}";
            if ($message) {
                $url .= "&message=" . urlencode($message);
            }
            header("Location: {$url}");
            exit;
        }
    }

    // Show access denied page
    private function showAccessDenied() {
        http_response_code(403);
        include __DIR__ . '/../templates/403.php';
        exit;
    }

    // Validate CSRF token for POST/PUT/DELETE requests
    public function validateCsrf() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' ||
            (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) &&
             in_array($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'], ['PUT', 'DELETE']))) {

            $token = $_POST['csrf_token'] ??
                     $_SERVER['HTTP_X_CSRF_TOKEN'] ??
                     $_GET['csrf_token'] ?? null;

            if (!$token || !validateCsrfToken($token)) {
                if (isApiRequest()) {
                    errorResponse('Invalid CSRF token', 403);
                } else {
                    $this->showAccessDenied();
                }
            }
        }
    }
}

// Global router guard instance
$guard = RouterGuard::getInstance();
