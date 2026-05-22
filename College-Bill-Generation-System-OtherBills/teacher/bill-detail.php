<?php
// ============================================================
//  teacher/bill-detail.php — Bill Detail View
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireTeacher();
$user = currentUser();
$uid  = $user['id'];

$billId = (int)($_GET['id'] ?? 0);
$bill   = $pdo->prepare("SELECT b.*, u.name AS teacher_name, u.email, u.department, u.teacher_type FROM bills b JOIN users u ON u.id=b.teacher_id WHERE b.id=? AND b.teacher_id=?");
$bill->execute([$billId, $uid]);
$bill = $bill->fetch();

if (!$bill) {
    setFlash('error','Bill not found.'); header('Location: my-bills.php'); exit;
}

// Get linked lectures
$lec = $pdo->prepare("SELECT l.* FROM lectures l JOIN bill_lectures bl ON bl.lecture_id=l.id WHERE bl.bill_id=? ORDER BY l.lecture_date ASC");
$lec->execute([$billId]);
$lectures = $lec->fetchAll();

renderHead('Bill Detail');
?>
<div class="app-layout">
<?php renderSidebar('my-bills','teacher',$user); ?>
<div class="main-content">
<?php renderTopbar('Bill Detail'); ?>
<div class="page-body">
    <?= showFlash() ?>

    <div class="breadcrumb">
        <a href="my-bills.php">My Bills</a>
        <span class="sep">›</span>
        <span>Bill #<?= $billId ?></span>
    </div>

    <!-- Header row -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem">
        <div class="page-header" style="margin:0">
            <h1><?= e($bill['month_year']) ?> Bill</h1>
            <p>Submitted on <?= fmtDate($bill['submitted_at'],'d F Y, h:i A') ?></p>
        </div>
        <div style="display:flex;gap:8px">
            <?php if ($bill['status'] === 'approved'): ?>
            <a href="../pdf/generate.php?id=<?= $billId ?>" class="btn btn-success" target="_blank">⬇ Download PDF</a>
            <?php endif; ?>
            <?php if ($bill['status'] === 'rejected'): ?>
            <a href="generate-bill.php" class="btn btn-gold">🔄 Generate New Bill</a>
            <?php endif; ?>
            <a href="my-bills.php" class="btn btn-outline">← Back</a>
        </div>
    </div>

    <?php if ($bill['status'] === 'rejected' && $bill['rejection_reason']): ?>
    <div class="alert alert-error" style="margin-bottom:1.5rem">
        ❌ <strong>Rejected:</strong> <?= e($bill['rejection_reason']) ?>
    </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">
        <!-- Bill Summary Card -->
        <div class="card">
            <div class="card-header"><h3>📋 Bill Summary</h3></div>
            <div class="card-body">
                <table style="font-size:0.88rem">
                    <tr><td style="color:var(--muted);padding:6px 0;width:140px">Bill ID</td><td><strong>#<?= $billId ?></strong></td></tr>
                    <tr><td style="color:var(--muted);padding:6px 0">Month</td><td><?= e($bill['month_year']) ?></td></tr>
                    <tr><td style="color:var(--muted);padding:6px 0">Period</td><td><?= fmtDate($bill['period_from']) ?> – <?= fmtDate($bill['period_to']) ?></td></tr>
                    <tr><td style="color:var(--muted);padding:6px 0">Total Lectures</td><td><strong><?= (int)$bill['total_lectures'] ?></strong></td></tr>
                    <tr><td style="color:var(--muted);padding:6px 0">Rate / Lecture</td><td><?= formatINR($bill['rate_per_lecture']) ?></td></tr>
                    <tr><td style="color:var(--muted);padding:6px 0">Total Amount</td><td><strong style="font-size:1.1rem;color:var(--navy)"><?= formatINR($bill['total_amount']) ?></strong></td></tr>
                    <tr><td style="color:var(--muted);padding:6px 0">Status</td><td><?= statusBadge($bill['status']) ?></td></tr>
                    <?php if ($bill['reviewed_at']): ?>
                    <tr><td style="color:var(--muted);padding:6px 0">Reviewed On</td><td><?= fmtDate($bill['reviewed_at'],'d M Y') ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <!-- Teacher Info -->
        <div class="card">
            <div class="card-header"><h3>👨‍🏫 Teacher Info</h3></div>
            <div class="card-body">
                <table style="font-size:0.88rem">
                    <tr><td style="color:var(--muted);padding:6px 0;width:140px">Name</td><td><?= e($bill['teacher_name']) ?></td></tr>
                    <tr><td style="color:var(--muted);padding:6px 0">Email</td><td><?= e($bill['email']) ?></td></tr>
                    <tr><td style="color:var(--muted);padding:6px 0">Department</td><td><?= e($bill['department'] ?: '—') ?></td></tr>
                    <tr><td style="color:var(--muted);padding:6px 0">Type</td><td><?= teacherTypeBadge($bill['teacher_type'] ?: 'visiting') ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Lecture Breakdown -->
    <div class="card" style="margin-top:1.5rem">
        <div class="card-header"><h3>📅 Lecture Breakdown</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th>Date</th><th>Subject</th><th>Lectures</th><th>Amount</th></tr></thead>
                <tbody>
                <?php $running = 0; foreach ($lectures as $i => $l):
                    $amt = $l['lecture_count'] * (float)$bill['rate_per_lecture'];
                    $running += $amt; ?>
                <tr>
                    <td style="color:var(--muted)"><?= $i+1 ?></td>
                    <td><?= fmtDate($l['lecture_date']) ?></td>
                    <td><?= e($l['subject']) ?></td>
                    <td><?= (int)$l['lecture_count'] ?></td>
                    <td><?= formatINR($amt) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr style="background:var(--bg-light)">
                    <td colspan="3" style="text-align:right;font-weight:600;padding:12px 14px">Total</td>
                    <td><strong><?= (int)$bill['total_lectures'] ?></strong></td>
                    <td><strong><?= formatINR($bill['total_amount']) ?></strong></td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
</div>
<?php renderFooter(); ?>
