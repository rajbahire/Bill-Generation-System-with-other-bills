<?php
// ============================================================
//  includes/auth.php — Authentication Guard
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Require any logged-in user. Redirect to login if not authenticated.
 */
function requireLogin() {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . getBaseUrl() . 'index.php?msg=login_required');
        exit;
    }
}

/**
 * Require HOD role specifically.
 */
function requireHOD() {
    requireLogin();
    if ($_SESSION['role'] !== 'hod') {
        header('Location: ' . getBaseUrl() . 'teacher/dashboard.php');
        exit;
    }
}

/**
 * Require Teacher role specifically.
 */
function requireTeacher() {
    requireLogin();
    if ($_SESSION['role'] !== 'teacher') {
        header('Location: ' . getBaseUrl() . 'hod/dashboard.php');
        exit;
    }
}

/**
 * Returns the current logged-in user's data from session.
 */
function currentUser() {
    return [
        'id'   => $_SESSION['user_id']   ?? null,
        'name' => $_SESSION['user_name'] ?? '',
        'role' => $_SESSION['role']      ?? '',
        'email'=> $_SESSION['email']     ?? '',
    ];
}

/**
 * Returns base URL for redirects (works in subdirectory installs).
 */
function getBaseUrl() {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    // Walk up until we hit teacher-bill-system root
    $base = rtrim(str_replace(['teacher', 'hod', 'pdf'], '', dirname($script)), '/\\') . '/';
    return $base;
}

/**
 * Get initials from a name (for avatar display).
 */
function getInitials(string $name): string {
    $parts = explode(' ', trim($name));
    $init  = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $init .= strtoupper($p[0] ?? '');
    }
    return $init ?: '?';
}
