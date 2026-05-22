<?php
// ============================================================
//  hod/requests.php — Pending Bill Requests
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireHOD();
$user = currentUser();

$bills = $pdo->query(
    "SELECT b.*, u.name AS teacher_name, u.teacher_type, u.department
     FROM bills b JOIN users u ON u.id=b.teacher_id
     WHERE b.status='pending'
     ORDER BY b.submitted_at ASC"
)->fetchAll();

renderHead('Pending Requests');
?>
<div class="app-layout">
<?php renderSidebar('requests','hod',$user); ?>
<div class="main-content">
<?php renderTopbar('Pending Requests'); ?>
<div class="page-body">
    <?= showFlash() ?>
    <div class="page-header">
        <h1>Pending Requests</h1>
        <p><?= count($bills) ?> bill<?= count($bills)!=1?'s':'' ?> awaiting your review</p>
    </div>

    <div class="card">
        <?php if ($bills): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>#</th><th>Teacher</th><th>Type</th><th>Month</th><th>Lectures</th><th>Amount</th><th>Submitted</th><th>Action</th></tr>
                </thead>
                <tbody>
                <?php foreach ($bills as $b): ?>
                <tr>
                    <td style="color:var(--muted)"><?= $b['id'] ?></td>
                    <td>
                        <strong><?= e($b['teacher_name']) ?></strong><br>
                        <small style="color:var(--muted)"><?= e($b['department'] ?: '—') ?></small>
                    </td>
                    <td><?= teacherTypeBadge($b['teacher_type'] ?: 'visiting') ?></td>
                    <td><?= e($b['month_year']) ?></td>
                    <td><?= (int)$b['total_lectures'] ?></td>
                    <td><strong><?= formatINR($b['total_amount']) ?></strong></td>
                    <td style="font-size:0.8rem;color:var(--muted)"><?= fmtDate($b['submitted_at'],'d M Y') ?></td>
                    <td>
                        <a href="request-detail.php?id=<?= $b['id'] ?>" class="btn btn-primary btn-sm">Review →</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="icon">🎉</div>
            <h3>No pending requests</h3>
            <p>All submitted bills have been reviewed. Great job!</p>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>
</div>
<?php renderFooter(); ?>
