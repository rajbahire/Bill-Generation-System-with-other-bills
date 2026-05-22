<?php
// ============================================================
//  hod/manage-teachers.php — Manage Teachers
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireHOD();
$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name  = trim($_POST['name']         ?? '');
        $email = trim($_POST['email']        ?? '');
        $type  = $_POST['teacher_type']      ?? 'visiting';
        $dept  = trim($_POST['department']   ?? '');
        $phone = trim($_POST['phone']        ?? '');
        $rate  = (float)($_POST['rate_per_lecture'] ?? 0);
        $pass  = $_POST['password']          ?? 'teacher@1234';

        if ($name && $email) {
            $dup = $pdo->prepare("SELECT id FROM users WHERE email=?"); $dup->execute([$email]);
            if ($dup->fetch()) { setFlash('error','Email already exists.'); }
            else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $pdo->prepare("INSERT INTO users (name,email,password,role,teacher_type,department,phone,rate_per_lecture) VALUES (?,?,?,'teacher',?,?,?,?)")
                    ->execute([$name,$email,$hash,$type,$dept,$phone,$rate]);
                logActivity($pdo,$user['id'],'add_teacher',"Added teacher: $name");
                setFlash('success',"Teacher $name added. Default password: $pass");
            }
        } else { setFlash('error','Name and Email are required.'); }
    }

    if ($action === 'edit') {
        $tid   = (int)($_POST['teacher_id']      ?? 0);
        $name  = trim($_POST['name']             ?? '');
        $type  = $_POST['teacher_type']          ?? 'visiting';
        $dept  = trim($_POST['department']       ?? '');
        $phone = trim($_POST['phone']            ?? '');
        $rate  = (float)($_POST['rate_per_lecture'] ?? 0);
        $active= (int)($_POST['is_active']       ?? 1);
        $pdo->prepare("UPDATE users SET name=?,teacher_type=?,department=?,phone=?,rate_per_lecture=?,is_active=? WHERE id=? AND role='teacher'")
            ->execute([$name,$type,$dept,$phone,$rate,$active,$tid]);
        logActivity($pdo,$user['id'],'edit_teacher',"Updated teacher ID $tid");
        setFlash('success','Teacher updated successfully.');
    }

    if ($action === 'reset_password') {
        $tid  = (int)($_POST['teacher_id'] ?? 0);
        $pass = 'teacher@1234';
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password=? WHERE id=? AND role='teacher'")->execute([$hash,$tid]);
        setFlash('success',"Password reset to: $pass");
    }

    header('Location: manage-teachers.php'); exit;
}

$teachers = $pdo->query("SELECT * FROM users WHERE role='teacher' ORDER BY name")->fetchAll();

// Edit target
$editId = (int)($_GET['edit'] ?? 0);
$editRow = null;
if ($editId) {
    $e = $pdo->prepare("SELECT * FROM users WHERE id=? AND role='teacher'"); $e->execute([$editId]); $editRow = $e->fetch();
}

renderHead('Manage Teachers');
?>
<div class="app-layout">
<?php renderSidebar('manage-teachers','hod',$user); ?>
<div class="main-content">
<?php renderTopbar('Manage Teachers'); ?>
<div class="page-body">
    <?= showFlash() ?>
    <div class="page-header"><h1>Manage Teachers</h1><p>Add teachers and set their lecture rates</p></div>

    <div style="display:grid;grid-template-columns:1fr 360px;gap:1.5rem;align-items:start">

        <!-- Teacher List -->
        <div class="card">
            <div class="card-header"><h3>All Teachers (<?= count($teachers) ?>)</h3></div>
            <?php if ($teachers): ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Name</th><th>Type</th><th>Rate/Lec</th><th>Department</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($teachers as $t): ?>
                    <tr>
                        <td>
                            <strong><?= e($t['name']) ?></strong><br>
                            <small style="color:var(--muted)"><?= e($t['email']) ?></small>
                        </td>
                        <td><?= teacherTypeBadge($t['teacher_type'] ?: 'visiting') ?></td>
                        <td><strong><?= formatINR($t['rate_per_lecture']) ?></strong></td>
                        <td style="color:var(--muted)"><?= e($t['department'] ?: '—') ?></td>
                        <td>
                            <?php if ($t['is_active']): ?>
                            <span class="badge badge-approved">Active</span>
                            <?php else: ?>
                            <span class="badge badge-rejected">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td style="display:flex;gap:5px;flex-wrap:wrap">
                            <a href="?edit=<?= $t['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action"     value="reset_password">
                                <input type="hidden" name="teacher_id" value="<?= $t['id'] ?>">
                                <button type="submit" class="btn btn-outline btn-sm"
                                    onclick="return confirmAction('Reset password to teacher@1234?')">
                                    🔑
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state"><div class="icon">👨‍🏫</div><h3>No teachers yet</h3><p>Add your first teacher using the form.</p></div>
            <?php endif; ?>
        </div>

        <!-- Add / Edit Form -->
        <div class="card" style="position:sticky;top:80px">
            <div class="card-header"><h3><?= $editRow ? '✏️ Edit Teacher' : '➕ Add Teacher' ?></h3></div>
            <div class="card-body">
                <form method="POST">
                    <?php if ($editRow): ?>
                    <input type="hidden" name="action"     value="edit">
                    <input type="hidden" name="teacher_id" value="<?= $editRow['id'] ?>">
                    <?php else: ?>
                    <input type="hidden" name="action" value="add">
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Full Name <span style="color:red">*</span></label>
                        <input type="text" name="name" class="form-control" required
                            value="<?= e($editRow['name'] ?? '') ?>" placeholder="Prof. Full Name">
                    </div>

                    <?php if (!$editRow): ?>
                    <div class="form-group">
                        <label>Email <span style="color:red">*</span></label>
                        <input type="email" name="email" class="form-control" required placeholder="teacher@college.edu">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="text" name="password" class="form-control" value="teacher@1234" placeholder="Default password">
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Teacher Type</label>
                        <select name="teacher_type" class="form-control">
                            <option value="visiting"       <?= ($editRow['teacher_type']??'')==='visiting'      ?'selected':''?>>Visiting</option>
                            <option value="guest"          <?= ($editRow['teacher_type']??'')==='guest'         ?'selected':''?>>Guest</option>
                            <option value="earn_and_learn" <?= ($editRow['teacher_type']??'')==='earn_and_learn'?'selected':''?>>Earn & Learn</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Rate per Lecture (₹) <span style="color:red">*</span></label>
                        <input type="number" name="rate_per_lecture" class="form-control" required
                            step="0.01" min="0"
                            value="<?= $editRow['rate_per_lecture'] ?? '' ?>" placeholder="500.00">
                    </div>

                    <div class="form-group">
                        <label>Department</label>
                        <input type="text" name="department" class="form-control"
                            value="<?= e($editRow['department'] ?? '') ?>" placeholder="e.g. Computer Science">
                    </div>

                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control"
                            value="<?= e($editRow['phone'] ?? '') ?>" placeholder="10-digit number">
                    </div>

                    <?php if ($editRow): ?>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="is_active" class="form-control">
                            <option value="1" <?= $editRow['is_active']?'selected':''?>>Active</option>
                            <option value="0" <?= !$editRow['is_active']?'selected':''?>>Inactive</option>
                        </select>
                    </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary" style="width:100%">
                        <?= $editRow ? '💾 Update Teacher' : '➕ Add Teacher' ?>
                    </button>
                    <?php if ($editRow): ?>
                    <a href="manage-teachers.php" class="btn btn-outline" style="width:100%;margin-top:8px;text-align:center">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
</div>
<?php renderFooter(); ?>
