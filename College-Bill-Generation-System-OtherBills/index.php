<?php
// ============================================================
//  index.php — Login Page
//  PHP logic: 100% original (unchanged)
//  UI: Figma — Government College of Engineering, Aurangabad
// ============================================================

session_start();

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'hod') {
        header('Location: hod/dashboard.php');
    } else {
        header('Location: teacher/dashboard.php');
    }
    exit;
}

require_once __DIR__.'/includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['email']     = $user['email'];

            // Log activity
            $log = $pdo->prepare("INSERT INTO activity_log (user_id, action, description, ip_address) VALUES (?, 'login', 'User logged in', ?)");
            $log->execute([$user['id'], $_SERVER['REMOTE_ADDR'] ?? '']);

            // Redirect based on role
            if ($user['role'] === 'hod') {
                header('Location: hod/dashboard.php');
            } else {
                header('Location: teacher/dashboard.php');
            }
            exit;
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
}

// Show role selector first, then login form for chosen role
$showLogin = isset($_GET['role']) && in_array($_GET['role'], ['teacher', 'hod', 'earn_and_learn']);
$loginRole = $_GET['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — College Bill Generation System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- ===== TOP NAVBAR ===== -->
<div class="login-page-wrap">

    <nav class="login-navbar">
        <div class="navbar-brand">
            <div class="navbar-logo">
                <img src="assets/images/logo.png" alt="College Logo">
            </div>
            <span class="navbar-college">Government College of Engineering, Aurangabad</span>
        </div>
    </nav>

    <div class="login-center">

    <?php if (!$showLogin): ?>
    <!-- ===== ROLE SELECTION ===== -->
    <div style="width:100%">
        <div class="role-select-title">
            <h1>College Bill Generation System</h1>
            <p>Select your role to continue</p>
        </div>

        <div class="role-grid">
            <a href="?role=teacher" class="role-card">
                <div class="role-icon blue">🎓</div>
                <h3>Teacher</h3>
                <p>Manage lectures, generate bills, and track payment status</p>
            </a>
            <a href="?role=hod" class="role-card">
                <div class="role-icon purple">👥</div>
                <h3>HOD</h3>
                <p>Review bills, manage teachers, and approve payments</p>
            </a>
            <a href="?role=earn_and_learn" class="role-card">
                <div class="role-icon green">💼</div>
                <h3>Earn &amp; Learn</h3>
                <p>Submit work hours, generate bills, and track earnings</p>
            </a>
        </div>
    </div>

    <?php else: ?>
    <!-- ===== LOGIN FORM (role selected) ===== -->
    <div class="login-card">

        <a href="index.php" class="back-link">← Back to role selection</a>

        <?php
        $rolePills = [
            'teacher'       => ['🎓', 'Teacher'],
            'hod'           => ['👥', 'HOD'],
            'earn_and_learn'=> ['💼', 'Earn & Learn'],
        ];
        [$pillIcon, $pillLabel] = $rolePills[$loginRole] ?? ['🎓', 'Teacher'];
        ?>
        <div class="login-role-pill">
            <?= $pillIcon ?> <?= htmlspecialchars($pillLabel) ?>
        </div>

        <h2>Sign in</h2>
        <p class="subtitle">Enter your credentials to access the portal</p>

        <?php if ($error): ?>
        <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- ORIGINAL FORM — action posts to index.php with role param -->
        <form method="POST" action="index.php?role=<?= htmlspecialchars($loginRole) ?>">

            <div class="form-group">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control <?= $error ? 'is-invalid' : '' ?>"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    placeholder="your@college.edu"
                    required
                    autocomplete="email"
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="pw-wrap">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control <?= $error ? 'is-invalid' : '' ?>"
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password"
                    >
                    <button type="button" class="pw-toggle" onclick="togglePw()">
                        <span id="pw-eye">👁</span>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:10px 16px;font-size:0.9rem">
                Sign In →
            </button>

        </form>

        <!-- Demo credentials hint -->
        <div class="demo-box">
            <strong style="color:var(--text)">Demo credentials:</strong><br>
            <?php if ($loginRole === 'hod'): ?>
            HOD: <span class="demo-fill" onclick="fillDemo('hod@college.edu','hod@1234')">hod@college.edu / hod@1234</span>
            <?php else: ?>
            Teacher: <span class="demo-fill" onclick="fillDemo('anjali@college.edu','teacher@1234')">anjali@college.edu / teacher@1234</span><br>
            Teacher: <span class="demo-fill" onclick="fillDemo('ravi@college.edu','teacher@1234')">ravi@college.edu / teacher@1234</span>
            <?php endif; ?>
        </div>

    </div>
    <?php endif; ?>

    </div><!-- /login-center -->
</div><!-- /login-page-wrap -->

<script src="assets/js/app.js"></script>
<script>
function togglePw() {
    const inp = document.getElementById('password');
    const eye = document.getElementById('pw-eye');
    if (!inp) return;
    inp.type = inp.type === 'password' ? 'text' : 'password';
    eye.textContent = inp.type === 'password' ? '👁' : '🙈';
}
function fillDemo(email, pw) {
    const e = document.getElementById('email');
    const p = document.getElementById('password');
    if (e) e.value = email;
    if (p) p.value = pw;
}
</script>

</body>
</html>
