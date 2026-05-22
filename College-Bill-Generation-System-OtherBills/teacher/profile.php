<?php
// ============================================================
//  teacher/profile.php — Teacher Profile
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireTeacher();
$user = currentUser();
$uid  = $user['id'];

$row  = $pdo->prepare("SELECT * FROM users WHERE id=?"); $row->execute([$uid]); $row = $row->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name  = trim($_POST['name']       ?? '');
        $phone = trim($_POST['phone']      ?? '');
        $dept  = trim($_POST['department'] ?? '');
        if ($name) {
            $pdo->prepare("UPDATE users SET name=?,phone=?,department=? WHERE id=?")->execute([$name,$phone,$dept,$uid]);
            $_SESSION['user_name'] = $name;
            setFlash('success','Profile updated successfully.');
        } else { setFlash('error','Name cannot be empty.'); }
    }

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (!password_verify($current, $row['password'])) {
            setFlash('error','Current password is incorrect.');
        } elseif (strlen($new) < 6) {
            setFlash('error','New password must be at least 6 characters.');
        } elseif ($new !== $confirm) {
            setFlash('error','New passwords do not match.');
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash,$uid]);
            setFlash('success','Password changed successfully.');
        }
    }

    header('Location: profile.php'); exit;
}

renderHead('Profile');
?>
<div class="app-layout">
<?php renderSidebar('profile','teacher',$user); ?>
<div class="main-content">
<?php renderTopbar('My Profile'); ?>
<div class="page-body">
    <?= showFlash() ?>
    <div class="page-header"><h1>My Profile</h1><p>Update your personal info and password</p></div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start">

        <!-- Profile Form -->
        <div class="card">
            <div class="card-header"><h3>👤 Personal Information</h3></div>
            <div class="card-body">

                <!-- Avatar display -->
                <div style="display:flex;align-items:center;gap:16px;margin-bottom:1.5rem;padding:1rem;background:var(--bg-light);border-radius:10px">
                    <div style="width:60px;height:60px;background:rgba(26,37,64,0.1);border:2px solid var(--border);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.4rem;font-weight:700;color:var(--navy)">
                        <?= e(getInitials($row['name'])) ?>
                    </div>
                    <div>
                        <div style="font-weight:600;font-size:1rem"><?= e($row['name']) ?></div>
                        <div style="font-size:0.8rem;color:var(--muted)"><?= e($row['email']) ?></div>
                        <div style="margin-top:4px"><?= teacherTypeBadge($row['teacher_type'] ?: 'visiting') ?></div>
                    </div>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?= e($row['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email (read only)</label>
                        <input type="email" class="form-control" value="<?= e($row['email']) ?>" disabled style="background:var(--bg-light)">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= e($row['phone'] ?? '') ?>" placeholder="10-digit mobile number">
                    </div>
                    <div class="form-group">
                        <label>Department</label>
                        <input type="text" name="department" class="form-control" value="<?= e($row['department'] ?? '') ?>" placeholder="e.g. Computer Science">
                    </div>
                    <div class="form-group">
                        <label>Rate per Lecture (set by HOD)</label>
                        <input type="text" class="form-control" value="<?= formatINR($row['rate_per_lecture']) ?>" disabled style="background:var(--bg-light)">
                    </div>
                    <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="card">
            <div class="card-header"><h3>🔒 Change Password</h3></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" class="form-control" placeholder="Enter current password" required>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" class="form-control" placeholder="Min. 6 characters" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">🔑 Change Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
</div>
<?php renderFooter(); ?>
