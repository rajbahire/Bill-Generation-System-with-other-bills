<?php
// ============================================================
//  teacher/generate-bill.php — Generate & Submit Bill
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireTeacher();
$user = currentUser();
$uid  = $user['id'];

// Fetch teacher's rate
$teacherRow = $pdo->prepare("SELECT * FROM users WHERE id=?");
$teacherRow->execute([$uid]);
$teacher = $teacherRow->fetch();

// ---- Handle SUBMIT ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = $_POST['action'] ?? '';
    $month      = (int)($_POST['bill_month'] ?? 0);
    $year       = (int)($_POST['bill_year']  ?? 0);

    if ($action === 'submit' && $month && $year) {
        // Check duplicate bill for same month/year
        $dup = $pdo->prepare("SELECT id FROM bills WHERE teacher_id=? AND MONTH(period_from)=? AND YEAR(period_from)=? AND status IN ('pending','approved')");
        $dup->execute([$uid, $month, $year]);
        if ($dup->fetch()) {
            setFlash('error', 'A bill for '.date('F',mktime(0,0,0,$month,1))." $year already exists and is pending/approved.");
            header('Location: generate-bill.php'); exit;
        }

        // Fetch lectures for that month
        $lec = $pdo->prepare("SELECT * FROM lectures WHERE teacher_id=? AND MONTH(lecture_date)=? AND YEAR(lecture_date)=? AND id NOT IN (SELECT lecture_id FROM bill_lectures)");
        $lec->execute([$uid, $month, $year]);
        $lectures = $lec->fetchAll();
        $total_lec = array_sum(array_column($lectures, 'lecture_count'));
        $rate      = (float)$teacher['rate_per_lecture'];
        $total_amt = $total_lec * $rate;

        if ($total_lec === 0) {
            setFlash('error', 'No unbilled lecture entries found for the selected month.');
            header('Location: generate-bill.php'); exit;
        }

        $from = date("$year-$month-01");
        $to   = date("$year-$month-t");
        $monthYear = date('F Y', mktime(0,0,0,$month,1,$year));

        // Insert bill
        $ins = $pdo->prepare("INSERT INTO bills (teacher_id, month_year, period_from, period_to, total_lectures, rate_per_lecture, total_amount, status) VALUES (?,?,?,?,?,?,?,'pending')");
        $ins->execute([$uid, $monthYear, $from, $to, $total_lec, $rate, $total_amt]);
        $billId = $pdo->lastInsertId();

        // Link lectures
        $link = $pdo->prepare("INSERT INTO bill_lectures (bill_id, lecture_id) VALUES (?,?)");
        foreach ($lectures as $l) $link->execute([$billId, $l['id']]);

        logActivity($pdo, $uid, 'submit_bill', "Submitted bill #$billId for $monthYear — ₹$total_amt");
        setFlash('success', "Bill for $monthYear submitted successfully! HOD will review it.");
        header('Location: my-bills.php'); exit;
    }
}

// ---- Preview: fetch month data ----
$previewMonth = (int)($_GET['month'] ?? 0);
$previewYear  = (int)($_GET['year']  ?? date('Y'));
$previewLectures = [];
$previewTotal = 0;

if ($previewMonth) {
    $lec = $pdo->prepare("SELECT * FROM lectures WHERE teacher_id=? AND MONTH(lecture_date)=? AND YEAR(lecture_date)=? AND id NOT IN (SELECT lecture_id FROM bill_lectures) ORDER BY lecture_date ASC");
    $lec->execute([$uid, $previewMonth, $previewYear]);
    $previewLectures = $lec->fetchAll();
    $previewTotal    = array_sum(array_column($previewLectures, 'lecture_count'));
}

// Available months that have unbilled lectures
$avail = $pdo->prepare("SELECT DISTINCT MONTH(lecture_date) as m, YEAR(lecture_date) as y, SUM(lecture_count) as total FROM lectures WHERE teacher_id=? AND id NOT IN (SELECT lecture_id FROM bill_lectures) GROUP BY YEAR(lecture_date), MONTH(lecture_date) ORDER BY y DESC, m DESC");
$avail->execute([$uid]);
$availMonths = $avail->fetchAll();

renderHead('Generate Bill');
?>
<div class="app-layout">
<?php renderSidebar('generate-bill','teacher',$user); ?>
<div class="main-content">
<?php renderTopbar('Generate Bill'); ?>
<div class="page-body">
    <?= showFlash() ?>

    <div class="page-header">
        <h1>Generate Bill</h1>
        <p>Select a month to preview and submit your bill</p>
    </div>

    <div style="display:grid;grid-template-columns:320px 1fr;gap:1.5rem;align-items:start">

        <!-- Left: Select Month -->
        <div class="card">
            <div class="card-header"><h3>📅 Select Month</h3></div>
            <div class="card-body">
                <form method="GET">
                    <div class="form-group">
                        <label>Month</label>
                        <select name="month" class="form-control" required>
                            <option value="">— Choose month —</option>
                            <?php for($m=1;$m<=12;$m++): ?>
                            <option value="<?= $m ?>" <?= $previewMonth==$m?'selected':'' ?>><?= date('F',mktime(0,0,0,$m,1)) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Year</label>
                        <select name="year" class="form-control">
                            <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?>
                            <option value="<?= $y ?>" <?= $previewYear==$y?'selected':'' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%">Preview Bill →</button>
                </form>

                <?php if ($availMonths): ?>
                <hr class="divider">
                <div style="font-size:0.78rem;color:var(--muted);margin-bottom:8px;font-weight:600;text-transform:uppercase;letter-spacing:.05em">Available Months</div>
                <?php foreach ($availMonths as $am): ?>
                <a href="?month=<?= $am['m'] ?>&year=<?= $am['y'] ?>" class="btn btn-outline btn-sm" style="display:block;margin-bottom:6px;text-align:left">
                    <?= date('F Y',mktime(0,0,0,$am['m'],1,$am['y'])) ?>
                    <span style="float:right;color:var(--muted)"><?= $am['total'] ?> lec</span>
                </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: Preview -->
        <div>
            <?php if ($previewMonth && $previewLectures): ?>
            <div class="card" style="margin-bottom:1.2rem">
                <div class="card-header">
                    <h3>🧾 Bill Preview — <?= date('F Y',mktime(0,0,0,$previewMonth,1,$previewYear)) ?></h3>
                </div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem">
                        <div style="text-align:center;padding:1rem;background:var(--bg-light);border-radius:10px">
                            <div style="font-size:0.72rem;color:var(--muted);text-transform:uppercase;letter-spacing:.05em">Total Lectures</div>
                            <div style="font-size:2rem;font-weight:700;color:var(--navy)"><?= $previewTotal ?></div>
                        </div>
                        <div style="text-align:center;padding:1rem;background:var(--bg-light);border-radius:10px">
                            <div style="font-size:0.72rem;color:var(--muted);text-transform:uppercase;letter-spacing:.05em">Rate / Lecture</div>
                            <div style="font-size:2rem;font-weight:700;color:var(--navy)"><?= formatINR($teacher['rate_per_lecture']) ?></div>
                        </div>
                        <div style="text-align:center;padding:1rem;background:var(--navy);border-radius:10px">
                            <div style="font-size:0.72rem;color:rgba(255,255,255,0.6);text-transform:uppercase;letter-spacing:.05em">Total Amount</div>
                            <div style="font-size:1.8rem;font-weight:700;color:var(--gold-lt)"><?= formatINR($previewTotal * (float)$teacher['rate_per_lecture']) ?></div>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Date</th><th>Subject</th><th>Lectures</th><th>Amount</th></tr></thead>
                            <tbody>
                            <?php foreach ($previewLectures as $l):
                                $amt = $l['lecture_count'] * (float)$teacher['rate_per_lecture']; ?>
                            <tr>
                                <td><?= fmtDate($l['lecture_date']) ?></td>
                                <td><?= e($l['subject']) ?></td>
                                <td><?= (int)$l['lecture_count'] ?></td>
                                <td><?= formatINR($amt) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <hr class="divider">

                    <div class="alert alert-warning">
                        ⚠️ Once submitted, you cannot edit this bill until the HOD reviews it.
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action"     value="submit">
                        <input type="hidden" name="bill_month" value="<?= $previewMonth ?>">
                        <input type="hidden" name="bill_year"  value="<?= $previewYear ?>">
                        <button type="submit" class="btn btn-gold"
                            onclick="return confirmAction('Submit this bill to HOD for approval?')">
                            📤 Submit Bill to HOD
                        </button>
                    </form>
                </div>
            </div>
            <?php elseif ($previewMonth && !$previewLectures): ?>
            <div class="card">
                <div class="empty-state">
                    <div class="icon">📭</div>
                    <h3>No unbilled lectures</h3>
                    <p>There are no lecture entries for <?= date('F Y',mktime(0,0,0,$previewMonth,1,$previewYear)) ?> that haven't been billed yet.</p>
                    <a href="lectures.php" class="btn btn-primary" style="margin-top:1rem">Add Lectures</a>
                </div>
            </div>
            <?php else: ?>
            <div class="card">
                <div class="empty-state">
                    <div class="icon">👈</div>
                    <h3>Select a month to preview</h3>
                    <p>Choose a month from the left panel to see your bill preview.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>
</div>
<?php renderFooter(); ?>
