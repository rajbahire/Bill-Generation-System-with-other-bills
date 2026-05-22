<?php
// ============================================================
//  includes/db.php — Database Connection (PDO)
//  Teacher Bill Management System
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'teacher_bill_system');
define('DB_USER', 'root');
define('DB_PASS', '');          // XAMPP default: empty password
define('DB_CHARSET', 'utf8mb4');

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // throw exceptions on error
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // return arrays by default
    PDO::ATTR_EMULATE_PREPARES   => false,                    // use real prepared statements
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Show friendly error — in production, log this instead of displaying
    die('<div style="font-family:sans-serif;padding:2rem;color:#b91c1c;background:#fff5f5;border:1px solid #fecaca;border-radius:8px;max-width:500px;margin:3rem auto;">
        <strong>Database connection failed.</strong><br><br>
        Please make sure:<br>
        &bull; XAMPP Apache &amp; MySQL are running<br>
        &bull; You have imported <code>database.sql</code> into phpMyAdmin<br>
        &bull; DB credentials in <code>includes/db.php</code> are correct<br><br>
        <small style="color:#6b7280;">Error: ' . htmlspecialchars($e->getMessage()) . '</small>
    </div>');
}
