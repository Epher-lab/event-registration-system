<?php
// includes/config.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'event_system');
define('SITE_NAME', 'EventSys');
define('SITE_URL', 'http://localhost/event_system');
define('ADMIN_URL', SITE_URL . '/admin');

// Session lifetime (2 hours)
define('SESSION_LIFETIME', 7200);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection (PDO)
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// Auth helpers
function isLoggedIn() {
    return isset($_SESSION['attendee_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isOrganizer() {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['organizer', 'admin']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/index.php');
        exit;
    }
}

// Generate booking reference
function generateBookingRef() {
    return 'EVT-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
}

// Format currency (KES)
function formatPrice($amount) {
    return 'KES ' . number_format($amount, 2);
}

// Cart helper
function getCart() {
    return $_SESSION['cart'] ?? [];
}

function addToCart($ticket_type_id, $event_id, $quantity) {
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    $key = $ticket_type_id;
    if (isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$key] = [
            'ticket_type_id' => $ticket_type_id,
            'event_id'       => $event_id,
            'quantity'       => $quantity,
        ];
    }
}

function clearCart() {
    $_SESSION['cart'] = [];
}

// CSRF token
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// CAPTCHA — simple checkbox + honeypot (replaces broken math captcha)
// Honeypot: a hidden field bots fill in but humans leave empty
// Checkbox: human must tick "I am not a robot"
function generateCaptcha() {
    return ''; // nothing to generate anymore
}

function verifyCaptcha($answer) {
    // Check honeypot is empty (bots fill this in)
    if (!empty($_POST['website'])) {
        return false;
    }
    // Checkbox must be ticked (value = '1')
    return $answer === '1';
}
