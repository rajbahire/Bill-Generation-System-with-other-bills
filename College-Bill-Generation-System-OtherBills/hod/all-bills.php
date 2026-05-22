<?php
// ============================================================
//  hod/all-bills.php — All Bills History
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireHOD();
$user = currentUser();

$filterStatus  = $_GET['status']  ?? '';
$filterTeacher = $_GET['teacher'] ?? '';
$filterMonth   = $_GET['month']   ?? '';
$filterYear    = $_GET['year']    ?? '';

$sql    = "SELECT b.*, u.name AS teacher_name, u.teacher_type FROM bills b JOIN users u ON u.id=b.teacher_id WHERE 1=1";
$params = [];
if ($filterStatus)  { $sql .= " AND b.status=?";         $params[] = $filterStatus; }
if ($filterTeacher) { $sql .= " AND b.teacher_id=?";     $params[] = $filterTeacher; }
if ($filterMonth)   { $sql .= " AND MONTH(b.period_from)=?"; $params[] = $filterMonth; }
if ($filterYear)    { $sql .= " AND YEAR(b.period_from)=?";  $params[] = $filterYear; }
$sql .= " ORDER BY b.submitted_at DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$bills = $stmt->fetchAll();

$teachers = $pdo->query("SELECT id,name FROM users WHERE role='teacher' AND is_active=1 ORDER BY name")->fetchAll();
$totalAmt  = array_sum(array_column($bills,'total_amount'));

renderHead('All Bills');
?>
<div class="app-layout">
<?php renderSidebar('all-bills','hod',$user); ?>
<div class="main-content">
<?php renderTopbar('All Bills History'); ?>
<div class="page-body">
    <?= showFlash() ?>
    <div class="page-header"><h1>All Bills</h1><p>Complete bill history across all teachers</p></div>

    <!-- Filter bar -->
    <div class="card" style="margin-bottom:1.5rem">
        <div class="card-body" style="padding:1rem">
            <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
                <div class="form-group" style="margin:0">
                    <label>Status</label>
                    <select name="status" class="form-control" style="width:130px">
                        <option value="">All</option>
                        <option value="pending"  <?= $filterStatus==='pending' ?'selected':''?>>Pending</option>
                        <option value="approved" <?= $filterStatus==='approved'?'selected':''?>>Approved</option>
                        <option value="rejected" <?= $filterStatus==='rejected'?'selected':''?>>Rejected</option>
                    </select>
                </div>
                <div class="form-group" style="margin:0">
                    <label>Teacher</label>
                    <select name="teacher" class="form-control" style="width:180px">
                        <option value="">All Teachers</option>
                        <?php foreach ($teachers as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $filterTeacher==$t['id']?'selected':''?>><?= e($t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0">
                    <label>Month</label>
                    <select name="month" class="form-control" style="width:130px">
                        <option value="">All Months</option>
                        <?php for($m=1;$m<=12;$m++): ?>
                        <option value="<?= $m ?>" <?= $filterMonth==$m?'selected':''?>><?= date('F',mktime(0,0,0,$m,1)) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0">
                    <label>Year</label>
                    <select name="year" class="form-control" style="width:100px">
                        <option value="">All</option>
                        <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?>
                        <option value="<?= $y ?>" <?= $filterYear==$y?'selected':''?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="all-bills.php" class="btn btn-outline">Clear</a>
            </form>
        </div>
    </div>

    <?php if ($bills): ?>
    <div style="display:flex;gap:1rem;margin-bottom:1rem;font-size:0.85rem;color:var(--muted)">
        <span>Showing <strong style="color:var(--navy)"><?= count($bills) ?></strong> bill<?= count($bills)!=1?'s':'' ?></span>
        <span>·</span>
        <span>Total: <strong style="color:var(--navy)"><?= formatINR($totalAmt) ?></strong></span>
    </div>
    <?php endif; ?>

    <div class="card">
        <?php if ($bills): ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th>Teacher</th><th>Month</th><th>Lectures</th><th>Amount</th><th>Status</th><th>Submitted</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($bills as $b): ?>
                <tr>
                    <td style="color:var(--muted)"><?= $b['id'] ?></td>
                    <td><strong><?= e($b['teacher_name']) ?></strong><br><small><?= teacherTypeBadge($b['teacher_type']?:'visiting') ?></small></td>
                    <td><?= e($b['month_year']) ?></td>
                    <td><?= (int)$b['total_lectures'] ?></td>
                    <td><strong><?= formatINR($b['total_amount']) ?></strong></td>
                    <td><?= statusBadge($b['status']) ?></td>
                    <td style="font-size:0.8rem;color:var(--muted)"><?= fmtDate($b['submitted_at'],'d M Y') ?></td>
                    <td style="display:flex;gap:6px">
                        <a href="request-detail.php?id=<?= $b['id'] ?>" class="btn btn-outline btn-sm">View</a>
                        <?php if ($b['status']==='approved'): ?>
                        <a href="../pdf/generate.php?id=<?= $b['id'] ?>" class="btn btn-success btn-sm" target="_blank">PDF</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state"><div class="icon">📋</div><h3>No bills found</h3><p>Try adjusting your filters.</p></div>
        <?php endif; ?>
    </div>
</div>
</div>
</div>
<?php renderFooter(); ?>
