<?php
// ============================================================
//  teacher/my-bills.php — All My Bills
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireTeacher();
$user = currentUser();
$uid  = $user['id'];

// Filters
$filterStatus = $_GET['status'] ?? '';
$sql = "SELECT * FROM bills WHERE teacher_id=?";
$params = [$uid];
if ($filterStatus) { $sql .= " AND status=?"; $params[] = $filterStatus; }
$sql .= " ORDER BY submitted_at DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$bills = $stmt->fetchAll();

renderHead('My Bills');
?>
<div class="app-layout">
<?php renderSidebar('my-bills','teacher',$user); ?>
<div class="main-content">
<?php renderTopbar('My Bills'); ?>
<div class="page-body">
    <?= showFlash() ?>

    <div class="page-header">
        <h1>My Bills</h1>
        <p>Track all your submitted bills and their status</p>
    </div>

    <!-- Filter -->
    <div style="display:flex;gap:8px;margin-bottom:1.5rem;flex-wrap:wrap">
        <a href="my-bills.php" class="btn <?= !$filterStatus ? 'btn-primary' : 'btn-outline' ?> btn-sm">All</a>
        <a href="?status=pending"  class="btn <?= $filterStatus==='pending'  ? 'btn-primary' : 'btn-outline' ?> btn-sm">⏳ Pending</a>
        <a href="?status=approved" class="btn <?= $filterStatus==='approved' ? 'btn-primary' : 'btn-outline' ?> btn-sm">✅ Approved</a>
        <a href="?status=rejected" class="btn <?= $filterStatus==='rejected' ? 'btn-primary' : 'btn-outline' ?> btn-sm">❌ Rejected</a>
        <a href="generate-bill.php" class="btn btn-gold btn-sm" style="margin-left:auto">➕ New Bill</a>
    </div>

    <div class="card">
        <?php if ($bills): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Month</th>
                        <th>Period</th>
                        <th>Lectures</th>
                        <th>Rate</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($bills as $b): ?>
                <tr>
                    <td style="color:var(--muted)"><?= $b['id'] ?></td>
                    <td><strong><?= e($b['month_year']) ?></strong></td>
                    <td style="font-size:0.8rem;color:var(--muted)"><?= fmtDate($b['period_from'],'d M') ?> – <?= fmtDate($b['period_to'],'d M') ?></td>
                    <td><?= (int)$b['total_lectures'] ?></td>
                    <td><?= formatINR($b['rate_per_lecture']) ?></td>
                    <td><strong><?= formatINR($b['total_amount']) ?></strong></td>
                    <td><?= statusBadge($b['status']) ?></td>
                    <td style="font-size:0.8rem;color:var(--muted)"><?= fmtDate($b['submitted_at'],'d M Y') ?></td>
                    <td>
                        <a href="bill-detail.php?id=<?= $b['id'] ?>" class="btn btn-outline btn-sm">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="icon">📄</div>
            <h3>No bills found</h3>
            <p><?= $filterStatus ? "No $filterStatus bills yet." : 'You have not submitted any bills yet.' ?></p>
            <a href="generate-bill.php" class="btn btn-primary" style="margin-top:1rem">Generate Your First Bill</a>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>
</div>
<?php renderFooter(); ?>
