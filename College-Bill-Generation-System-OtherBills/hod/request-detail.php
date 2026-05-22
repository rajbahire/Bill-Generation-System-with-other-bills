<?php
// ============================================================
//  hod/request-detail.php — Review, Approve or Reject Bill
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireHOD();
$user = currentUser();
$hodId = $user['id'];

$billId = (int)($_GET['id'] ?? 0);
$bill   = $pdo->prepare(
    "SELECT b.*, u.name AS teacher_name, u.email, u.department, u.teacher_type, u.phone
     FROM bills b JOIN users u ON u.id=b.teacher_id WHERE b.id=?"
);
$bill->execute([$billId]);
$bill = $bill->fetch();
if (!$bill) { setFlash('error','Bill not found.'); header('Location: requests.php'); exit; }

// Handle Approve / Reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'approve') {
        $pdo->prepare("UPDATE bills SET status='approved', reviewed_at=NOW(), reviewed_by=? WHERE id=?")->execute([$hodId, $billId]);
        logActivity($pdo, $hodId, 'approve_bill', "Approved bill #$billId for {$bill['teacher_name']}");
        setFlash('success', "Bill #$billId approved successfully.");
        header('Location: requests.php'); exit;
    }

    if ($action === 'reject') {
        $reason = trim($_POST['reason'] ?? '');
        if (!$reason) { setFlash('error','Please provide a rejection reason.'); header("Location: request-detail.php?id=$billId"); exit; }
        $pdo->prepare("UPDATE bills SET status='rejected', rejection_reason=?, reviewed_at=NOW(), reviewed_by=? WHERE id=?")->execute([$reason, $hodId, $billId]);
        logActivity($pdo, $hodId, 'reject_bill', "Rejected bill #$billId: $reason");
        setFlash('success', "Bill #$billId rejected. Teacher will be notified.");
        header('Location: requests.php'); exit;
    }
}

// Get linked lectures
$lec = $pdo->prepare("SELECT l.* FROM lectures l JOIN bill_lectures bl ON bl.lecture_id=l.id WHERE bl.bill_id=? ORDER BY l.lecture_date ASC");
$lec->execute([$billId]);
$lectures = $lec->fetchAll();

renderHead('Review Bill');
?>
<div class="app-layout">
<?php renderSidebar('requests','hod',$user); ?>
<div class="main-content">
<?php renderTopbar('Review Bill'); ?>
<div class="page-body">
    <?= showFlash() ?>

    <div class="breadcrumb">
        <a href="requests.php">Pending Requests</a>
        <span class="sep">›</span>
        <span>Review Bill #<?= $billId ?></span>
    </div>

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem">
        <div class="page-header" style="margin:0">
            <h1><?= e($bill['month_year']) ?> — <?= e($bill['teacher_name']) ?></h1>
            <p>Submitted <?= fmtDate($bill['submitted_at'],'d F Y') ?></p>
        </div>
        <div style="display:flex;gap:8px">
            <?php if ($bill['status'] === 'approved'): ?>
            <a href="../pdf/generate.php?id=<?= $billId ?>" class="btn btn-success" target="_blank">⬇ Download PDF</a>
            <?php endif; ?>
            <a href="requests.php" class="btn btn-outline">← Back</a>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 340px;gap:1.5rem;align-items:start">

        <!-- Left: Bill Details -->
        <div>
            <!-- Summary Cards -->
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem">
                <div style="text-align:center;padding:1.2rem;background:var(--white);border:1px solid var(--border);border-radius:12px">
                    <div style="font-size:0.72rem;color:var(--muted);text-transform:uppercase;letter-spacing:.05em">Total Lectures</div>
                    <div style="font-size:2rem;font-weight:700;color:var(--navy)"><?= (int)$bill['total_lectures'] ?></div>
                </div>
                <div style="text-align:center;padding:1.2rem;background:var(--white);border:1px solid var(--border);border-radius:12px">
                    <div style="font-size:0.72rem;color:var(--muted);text-transform:uppercase;letter-spacing:.05em">Rate / Lecture</div>
                    <div style="font-size:1.6rem;font-weight:700;color:var(--navy)"><?= formatINR($bill['rate_per_lecture']) ?></div>
                </div>
                <div style="text-align:center;padding:1.2rem;background:var(--navy);border-radius:12px">
                    <div style="font-size:0.72rem;color:rgba(255,255,255,0.6);text-transform:uppercase;letter-spacing:.05em">Total Amount</div>
                    <div style="font-size:1.5rem;font-weight:700;color:var(--gold-lt)"><?= formatINR($bill['total_amount']) ?></div>
                </div>
            </div>

            <!-- Teacher Info -->
            <div class="card" style="margin-bottom:1.5rem">
                <div class="card-header"><h3>👨‍🏫 Teacher Information</h3></div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;font-size:0.88rem">
                        <div><span style="color:var(--muted)">Name:</span> <strong><?= e($bill['teacher_name']) ?></strong></div>
                        <div><span style="color:var(--muted)">Email:</span> <?= e($bill['email']) ?></div>
                        <div><span style="color:var(--muted)">Department:</span> <?= e($bill['department'] ?: '—') ?></div>
                        <div><span style="color:var(--muted)">Type:</span> <?= teacherTypeBadge($bill['teacher_type'] ?: 'visiting') ?></div>
                        <div><span style="color:var(--muted)">Phone:</span> <?= e($bill['phone'] ?: '—') ?></div>
                        <div><span style="color:var(--muted)">Period:</span> <?= fmtDate($bill['period_from']) ?> – <?= fmtDate($bill['period_to']) ?></div>
                    </div>
                </div>
            </div>

            <!-- Lecture Breakdown -->
            <div class="card">
                <div class="card-header"><h3>📅 Lecture Entries</h3></div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>#</th><th>Date</th><th>Subject</th><th>Lectures</th><th>Amount</th></tr></thead>
                        <tbody>
                        <?php foreach ($lectures as $i => $l):
                            $amt = $l['lecture_count'] * (float)$bill['rate_per_lecture']; ?>
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

        <!-- Right: Action Panel -->
        <?php if ($bill['status'] === 'pending'): ?>
        <div>
            <!-- Approve -->
            <div class="card" style="margin-bottom:1rem;border:1px solid #bbf7d0">
                <div class="card-header" style="background:#f0fdf4"><h3 style="color:var(--approved)">✅ Approve Bill</h3></div>
                <div class="card-body">
                    <p style="font-size:0.85rem;color:var(--muted);margin-bottom:1rem">Approve this bill to finalize the payment of <strong><?= formatINR($bill['total_amount']) ?></strong>.</p>
                    <form method="POST">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn btn-success" style="width:100%"
                            onclick="return confirmAction('Approve this bill for <?= formatINR($bill['total_amount']) ?>?')">
                            ✅ Approve Bill
                        </button>
                    </form>
                </div>
            </div>

            <!-- Reject -->
            <div class="card" style="border:1px solid #fecaca">
                <div class="card-header" style="background:#fff5f5"><h3 style="color:var(--rejected)">❌ Reject Bill</h3></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="reject">
                        <div class="form-group">
                            <label>Reason for Rejection <span style="color:red">*</span></label>
                            <textarea name="reason" class="form-control" rows="4"
                                placeholder="Explain why this bill is being rejected..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger" style="width:100%"
                            onclick="return confirmAction('Reject this bill?')">
                            ❌ Reject Bill
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="card-body" style="text-align:center;padding:2rem">
                <div style="font-size:2.5rem;margin-bottom:1rem"><?= $bill['status']==='approved'?'✅':'❌' ?></div>
                <div style="font-weight:600;font-size:1rem;margin-bottom:0.5rem"><?= ucfirst($bill['status']) ?></div>
                <div style="font-size:0.82rem;color:var(--muted)">Reviewed on <?= fmtDate($bill['reviewed_at'],'d M Y') ?></div>
                <?php if ($bill['rejection_reason']): ?>
                <div class="alert alert-error" style="text-align:left;margin-top:1rem">
                    <?= e($bill['rejection_reason']) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>
</div>
<?php renderFooter(); ?>
