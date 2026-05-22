<?php
// ============================================================
//  logout.php — Logout Handler
//  Teacher Bill Management System
// ============================================================

session_start();

// Log the logout action before destroying session
if (isset($_SESSION['user_id'])) {
    require_once 'includes/db.php';

    $stmt = $pdo->prepare(
        "INSERT INTO activity_log (user_id, action, description, ip_address)
         VALUES (?, 'logout', 'User logged out', ?)"
    );
    $stmt->execute([
        $_SESSION['user_id'],
        $_SERVER['REMOTE_ADDR'] ?? ''
    ]);
}

// Destroy session completely
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

// Redirect to login with a goodbye message
header('Location: index.php?logout=1');
exit;
