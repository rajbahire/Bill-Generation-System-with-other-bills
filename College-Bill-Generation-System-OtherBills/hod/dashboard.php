<?php
// ============================================================
//  hod/dashboard.php — HOD Dashboard
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireHOD();
$user = currentUser();

// Stats
$pending  = $pdo->query("SELECT COUNT(*) FROM bills WHERE status='pending'")->fetchColumn();
$approved = $pdo->query("SELECT COUNT(*) FROM bills WHERE status='approved'")->fetchColumn();
$rejected = $pdo->query("SELECT COUNT(*) FROM bills WHERE status='rejected'")->fetchColumn();
$teachers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='teacher' AND is_active=1")->fetchColumn();
$totalPaid = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM bills WHERE status='approved'")->fetchColumn();
$thisMonth = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM bills WHERE status='approved' AND MONTH(reviewed_at)=MONTH(NOW()) AND YEAR(reviewed_at)=YEAR(NOW())")->fetchColumn();

// Pending requests list
$pendingBills = $pdo->query("SELECT b.*, u.name AS teacher_name, u.teacher_type FROM bills b JOIN users u ON u.id=b.teacher_id WHERE b.status='pending' ORDER BY b.submitted_at DESC LIMIT 8")->fetchAll();

// Recent activity
$recentActivity = $pdo->query("SELECT a.*, u.name FROM activity_log a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.created_at DESC LIMIT 8")->fetchAll();

renderHead('HOD Dashboard');
?>
<div class="app-layout">
<?php renderSidebar('dashboard','hod',$user); ?>
<div class="main-content">
<?php renderTopbar('HOD Dashboard'); ?>
<div class="page-body">
    <?= showFlash() ?>

    <div class="page-header">
        <h1>Welcome, <?= e(explode(' ',$user['name'])[0]) ?> 👋</h1>
        <p>Department overview — <?= date('F Y') ?></p>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">⏳</div>
            <div class="stat-info"><div class="label">Pending Requests</div><div class="value"><?= (int)$pending ?></div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">✅</div>
            <div class="stat-info"><div class="label">Approved Bills</div><div class="value"><?= (int)$approved ?></div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">❌</div>
            <div class="stat-info"><div class="label">Rejected</div><div class="value"><?= (int)$rejected ?></div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon amber">👨‍🏫</div>
            <div class="stat-info"><div class="label">Active Teachers</div><div class="value"><?= (int)$teachers ?></div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">💰</div>
            <div class="stat-info"><div class="label">Total Disbursed</div><div class="value" style="font-size:1rem"><?= formatINR((float)$totalPaid) ?></div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">📅</div>
            <div class="stat-info"><div class="label">This Month Paid</div><div class="value" style="font-size:1rem"><?= formatINR((float)$thisMonth) ?></div></div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div style="display:flex;gap:10px;margin-bottom:2rem;flex-wrap:wrap">
        <a href="requests.php" class="btn btn-primary">📥 Review Requests <?php if($pending): ?><span style="background:#ef4444;color:#fff;padding:1px 7px;border-radius:20px;font-size:0.72rem;margin-left:4px"><?= (int)$pending ?></span><?php endif; ?></a>
        <a href="manage-teachers.php" class="btn btn-outline">👨‍🏫 Manage Teachers</a>
        <a href="all-bills.php" class="btn btn-outline">📋 All Bills</a>
        <a href="other-bills.php" class="btn btn-outline">🧾 Other Bills</a>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">
        <!-- Pending Requests -->
        <div class="card">
            <div class="card-header">
                <h3>📥 Pending Requests</h3>
                <a href="requests.php" class="btn btn-outline btn-sm">View All</a>
            </div>
            <?php if ($pendingBills): ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Teacher</th><th>Month</th><th>Amount</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($pendingBills as $b): ?>
                    <tr>
                        <td><?= e($b['teacher_name']) ?><br><small style="color:var(--muted)"><?= teacherTypeBadge($b['teacher_type'] ?: 'visiting') ?></small></td>
                        <td><?= e($b['month_year']) ?></td>
                        <td><strong><?= formatINR($b['total_amount']) ?></strong></td>
                        <td><a href="request-detail.php?id=<?= $b['id'] ?>" class="btn btn-outline btn-sm">Review</a></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state"><div class="icon">🎉</div><h3>No pending requests</h3><p>All bills have been reviewed.</p></div>
            <?php endif; ?>
        </div>

        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header"><h3>🕐 Recent Activity</h3></div>
            <?php if ($recentActivity): ?>
            <div style="padding:0.5rem 0">
                <?php foreach ($recentActivity as $a): ?>
                <div style="display:flex;gap:10px;align-items:flex-start;padding:10px 1.4rem;border-bottom:1px solid var(--border)">
                    <div style="width:32px;height:32px;background:var(--bg-light);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.85rem;flex-shrink:0">
                        <?= $a['action']==='login'?'🔑':($a['action']==='logout'?'🚪':($a['action']==='submit_bill'?'📤':($a['action']==='approve_bill'?'✅':($a['action']==='reject_bill'?'❌':'📋')))) ?>
                    </div>
                    <div>
                        <div style="font-size:0.82rem;font-weight:500"><?= e($a['name'] ?? 'System') ?></div>
                        <div style="font-size:0.78rem;color:var(--muted)"><?= e($a['description'] ?: $a['action']) ?></div>
                        <div style="font-size:0.72rem;color:var(--muted);margin-top:1px"><?= fmtDate($a['created_at'],'d M, h:i A') ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state"><div class="icon">📋</div><h3>No activity yet</h3></div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>
</div>
<?php renderFooter(); ?>
