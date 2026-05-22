<?php
// ============================================================
//  teacher/dashboard.php
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireTeacher();
$user = currentUser();

// Stats
$uid = $user['id'];
$totalBills    = $pdo->prepare("SELECT COUNT(*) FROM bills WHERE teacher_id=?"); $totalBills->execute([$uid]);
$pendingBills  = $pdo->prepare("SELECT COUNT(*) FROM bills WHERE teacher_id=? AND status='pending'"); $pendingBills->execute([$uid]);
$approvedBills = $pdo->prepare("SELECT COUNT(*) FROM bills WHERE teacher_id=? AND status='approved'"); $approvedBills->execute([$uid]);
$rejectedBills = $pdo->prepare("SELECT COUNT(*) FROM bills WHERE teacher_id=? AND status='rejected'"); $rejectedBills->execute([$uid]);
$totalEarned   = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM bills WHERE teacher_id=? AND status='approved'"); $totalEarned->execute([$uid]);

$thisMonth = $pdo->prepare("SELECT COALESCE(SUM(lecture_count),0) FROM lectures WHERE teacher_id=? AND MONTH(lecture_date)=MONTH(NOW()) AND YEAR(lecture_date)=YEAR(NOW())");
$thisMonth->execute([$uid]);

// Recent bills
$recent = $pdo->prepare("SELECT * FROM bills WHERE teacher_id=? ORDER BY submitted_at DESC LIMIT 5");
$recent->execute([$uid]);
$recentBills = $recent->fetchAll();

// Recent lectures
$recentLec = $pdo->prepare("SELECT * FROM lectures WHERE teacher_id=? ORDER BY lecture_date DESC LIMIT 5");
$recentLec->execute([$uid]);
$recentLectures = $recentLec->fetchAll();

renderHead('Dashboard');
?>
<div class="app-layout">
<?php renderSidebar('dashboard','teacher',$user); ?>
<div class="main-content">
<?php renderTopbar('Dashboard'); ?>
<div class="page-body">
    <?= showFlash() ?>

    <div class="page-header">
        <h1>Welcome back, <?= e(explode(' ',$user['name'])[0]) ?> 👋</h1>
        <p>Here's your activity overview for <?= date('F Y') ?></p>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon amber">🧾</div>
            <div class="stat-info">
                <div class="label">Total Bills</div>
                <div class="value"><?= (int)$totalBills->fetchColumn() ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">⏳</div>
            <div class="stat-info">
                <div class="label">Pending</div>
                <div class="value"><?= (int)$pendingBills->fetchColumn() ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">✅</div>
            <div class="stat-info">
                <div class="label">Approved</div>
                <div class="value"><?= (int)$approvedBills->fetchColumn() ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">📅</div>
            <div class="stat-info">
                <div class="label">Lectures This Month</div>
                <div class="value"><?= (int)$thisMonth->fetchColumn() ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">💰</div>
            <div class="stat-info">
                <div class="label">Total Earned</div>
                <div class="value" style="font-size:1.1rem"><?= formatINR((float)$totalEarned->fetchColumn()) ?></div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div style="display:flex;gap:10px;margin-bottom:2rem;flex-wrap:wrap">
        <a href="lectures.php" class="btn btn-primary">📅 Add Lecture Entry</a>
        <a href="generate-bill.php" class="btn btn-gold">🧾 Generate Bill</a>
        <a href="my-bills.php" class="btn btn-outline">📊 View My Bills</a>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">
        <!-- Recent Bills -->
        <div class="card">
            <div class="card-header">
                <h3>Recent Bills</h3>
                <a href="my-bills.php" class="btn btn-outline btn-sm">View All</a>
            </div>
            <?php if ($recentBills): ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Month</th><th>Amount</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentBills as $b): ?>
                    <tr>
                        <td><?= e($b['month_year']) ?></td>
                        <td><?= formatINR($b['total_amount']) ?></td>
                        <td><?= statusBadge($b['status']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state"><div class="icon">📄</div><h3>No bills yet</h3><p>Generate your first bill to get started.</p></div>
            <?php endif; ?>
        </div>

        <!-- Recent Lectures -->
        <div class="card">
            <div class="card-header">
                <h3>Recent Lectures</h3>
                <a href="lectures.php" class="btn btn-outline btn-sm">Manage</a>
            </div>
            <?php if ($recentLectures): ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Date</th><th>Subject</th><th>Count</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentLectures as $l): ?>
                    <tr>
                        <td><?= fmtDate($l['lecture_date'],'d M') ?></td>
                        <td><?= e($l['subject']) ?></td>
                        <td><?= (int)$l['lecture_count'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state"><div class="icon">📅</div><h3>No lectures recorded</h3><p>Start by adding your lecture entries.</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>
</div>
<?php renderFooter(); ?>
