<?php
// ============================================================
//  includes/functions.php — Shared Helper Functions
//  UI: Government College of Engineering, Aurangabad (Figma)
// ============================================================

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function formatINR(float $amount): string {
    return '₹' . number_format($amount, 2);
}

function fmtDate(string $date, string $format = 'd M Y'): string {
    if (!$date || $date === '0000-00-00') return '—';
    return date($format, strtotime($date));
}

function statusBadge(string $status): string {
    $map = [
        'pending'  => 'badge-pending',
        'approved' => 'badge-approved',
        'rejected' => 'badge-rejected',
    ];
    $cls  = $map[$status] ?? 'badge-pending';
    $icon = ['pending'=>'⏳','approved'=>'✅','rejected'=>'❌'][$status] ?? '';
    return '<span class="badge '.$cls.'">'.$icon.' '.ucfirst(e($status)).'</span>';
}

function teacherTypeBadge(string $type): string {
    $map = [
        'visiting'      => ['badge-visiting', 'Visiting'],
        'guest'         => ['badge-guest',    'Guest'],
        'earn_and_learn'=> ['badge-earn',     'Earn & Learn'],
    ];
    [$cls, $label] = $map[$type] ?? ['badge-visiting', ucfirst($type)];
    return '<span class="badge '.$cls.'">'.$label.'</span>';
}

function logActivity(PDO $pdo, int $userId, string $action, string $description = ''): void {
    $stmt = $pdo->prepare(
        "INSERT INTO activity_log (user_id, action, description, ip_address) VALUES (?,?,?,?)"
    );
    $stmt->execute([$userId, $action, $description, $_SERVER['REMOTE_ADDR'] ?? '']);
}

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function showFlash(): string {
    if (empty($_SESSION['flash'])) return '';
    $f   = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $cls = match($f['type']) {
        'success' => 'alert-success',
        'error'   => 'alert-error',
        'warning' => 'alert-warning',
        default   => 'alert-info',
    };
    $icon = match($f['type']) {
        'success' => '✅',
        'error'   => '❌',
        'warning' => '⚠️',
        default   => 'ℹ️',
    };
    return '<div class="alert '.$cls.' auto-dismiss">'.$icon.' '.e($f['message']).'</div>';
}

// function getInitials(string $name): string {
//     $parts = explode(' ', trim($name));
//     $init  = '';
//     foreach (array_slice($parts, 0, 2) as $p) {
//         $init .= strtoupper($p[0] ?? '');
//     }
//     return $init ?: '?';
// }

/**
 * Render sidebar — updated to Figma style (white sidebar, top navbar offset)
 */
function renderSidebar(string $active, string $role, array $user): void {
    $base = '../';

    $teacherNav = [
        ['key'=>'dashboard',     'href'=>'dashboard.php',     'icon'=>'🏠', 'label'=>'Dashboard'],
        ['key'=>'lectures',      'href'=>'lectures.php',      'icon'=>'📅', 'label'=>'My Lectures'],
        ['key'=>'generate-bill', 'href'=>'generate-bill.php', 'icon'=>'🧾', 'label'=>'Generate Bill'],
        ['key'=>'my-bills',      'href'=>'my-bills.php',      'icon'=>'📊', 'label'=>'My Bills'],
        ['key'=>'profile',       'href'=>'profile.php',       'icon'=>'👤', 'label'=>'Profile'],
    ];
    $hodNav = [
        ['key'=>'dashboard',       'href'=>'dashboard.php',       'icon'=>'🏠', 'label'=>'Dashboard'],
        ['key'=>'requests',        'href'=>'requests.php',        'icon'=>'📥', 'label'=>'Pending Requests'],
        ['key'=>'all-bills',       'href'=>'all-bills.php',       'icon'=>'📋', 'label'=>'All Bills'],
        ['key'=>'other-bills',     'href'=>'other-bills.php',     'icon'=>'🧾', 'label'=>'Other Bills'],
        ['key'=>'manage-teachers', 'href'=>'manage-teachers.php', 'icon'=>'👨‍🏫', 'label'=>'Manage Teachers'],
        ['key'=>'profile',         'href'=>'profile.php',         'icon'=>'👤', 'label'=>'Profile'],
    ];
    $navItems  = ($role === 'hod') ? $hodNav : $teacherNav;
    $initials  = getInitials($user['name']);
    $roleLabel = ($role === 'hod') ? 'HOD' : 'Teacher';
    ?>
    <aside class="sidebar">
        <div class="sidebar-user">
            <div class="user-avatar"><?= e($initials) ?></div>
            <div class="user-info">
                <div class="name"><?= e($user['name']) ?></div>
                <div class="role-badge"><?= $roleLabel ?></div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-label">Menu</div>
            <?php foreach ($navItems as $item): ?>
            <a href="<?= e($item['href']) ?>" class="nav-item <?= $active === $item['key'] ? 'active' : '' ?>">
                <span class="icon"><?= $item['icon'] ?></span>
                <?= e($item['label']) ?>
            </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <a href="<?= $base ?>logout.php" class="btn-logout"
               onclick="return confirmAction('Sign out of your account?')">
                🚪 Sign Out
            </a>
        </div>
    </aside>
    <?php
}

/**
 * Render top bar inside dashboard pages
 */
function renderTopbar(string $title): void {
    $date = date('l, d F Y');
    ?>
    <div class="topbar">
        <div class="topbar-title"><?= e($title) ?></div>
        <div class="topbar-right">
            <span class="topbar-date"><?= $date ?></span>
        </div>
    </div>
    <?php
}

/**
 * Render HTML <head> + open <body> + TOP NAVBAR (Figma)
 */
function renderHead(string $title, string $base = '../'): void {
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> — College Bill Generation System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
</head>
<body>
<!-- TOP NAVBAR: Government College of Engineering, Aurangabad -->
<nav class="navbar">
    <div class="navbar-brand">
        <div class="navbar-logo">
            <img src="<?= $base ?>assets/images/logo.png" alt="College Logo">
        </div>
        <span class="navbar-college">Government College of Engineering, Aurangabad</span>
    </div>
</nav>
    <?php
}

/**
 * Render closing scripts + </body></html>
 */
function renderFooter(string $base = '../'): void {
    ?>
    <script src="<?= $base ?>assets/js/app.js"></script>
</body>
</html>
    <?php
}
