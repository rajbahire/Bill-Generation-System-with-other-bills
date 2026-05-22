<?php
// ============================================================
//  teacher/lectures.php — Manage Lecture Entries
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireTeacher();
$user = currentUser();
$uid  = $user['id'];

// ---- Handle POST actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ADD lecture
    if ($action === 'add') {
        $date    = $_POST['lecture_date']  ?? '';
        $subject = trim($_POST['subject']  ?? '');
        $count   = (int)($_POST['lecture_count'] ?? 1);
        $notes   = trim($_POST['notes']    ?? '');

        if ($date && $subject && $count > 0) {
            $stmt = $pdo->prepare(
                "INSERT INTO lectures (teacher_id, lecture_date, subject, lecture_count, notes)
                 VALUES (?,?,?,?,?)"
            );
            $stmt->execute([$uid, $date, $subject, $count, $notes]);
            logActivity($pdo, $uid, 'add_lecture', "Added $count lecture(s) for $subject on $date");
            setFlash('success', 'Lecture entry added successfully.');
        } else {
            setFlash('error', 'Please fill all required fields.');
        }
        header('Location: lectures.php'); exit;
    }

    // DELETE lecture
    if ($action === 'delete') {
        $lid = (int)($_POST['lecture_id'] ?? 0);
        // Ensure lecture belongs to this teacher and is NOT linked to a submitted bill
        $check = $pdo->prepare(
            "SELECT l.id FROM lectures l
             LEFT JOIN bill_lectures bl ON bl.lecture_id = l.id
             LEFT JOIN bills b ON b.id = bl.bill_id AND b.status IN ('pending','approved')
             WHERE l.id=? AND l.teacher_id=? AND b.id IS NULL"
        );
        $check->execute([$lid, $uid]);
        if ($check->fetch()) {
            $pdo->prepare("DELETE FROM lectures WHERE id=? AND teacher_id=?")->execute([$lid, $uid]);
            setFlash('success', 'Lecture entry deleted.');
        } else {
            setFlash('error', 'Cannot delete: lecture is linked to a submitted bill.');
        }
        header('Location: lectures.php'); exit;
    }
}

// ---- Filters ----
$filterMonth = $_GET['month'] ?? '';
$filterYear  = $_GET['year']  ?? date('Y');

$sql    = "SELECT * FROM lectures WHERE teacher_id=?";
$params = [$uid];
if ($filterMonth) { $sql .= " AND MONTH(lecture_date)=?"; $params[] = $filterMonth; }
if ($filterYear)  { $sql .= " AND YEAR(lecture_date)=?";  $params[] = $filterYear;  }
$sql .= " ORDER BY lecture_date DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$lectures = $stmt->fetchAll();

// Month total
$monthTotal = 0;
foreach ($lectures as $l) $monthTotal += $l['lecture_count'];

renderHead('My Lectures');
?>
<div class="app-layout">
<?php renderSidebar('lectures','teacher',$user); ?>
<div class="main-content">
<?php renderTopbar('Manage Lectures'); ?>
<div class="page-body">
    <?= showFlash() ?>

    <div class="page-header">
        <h1>My Lectures</h1>
        <p>Record and manage your lecture sessions</p>
    </div>

    <div style="display:grid;grid-template-columns:1fr 340px;gap:1.5rem;align-items:start">

        <!-- Left: Table + Filters -->
        <div>
            <!-- Filter bar -->
            <div class="card" style="margin-bottom:1rem">
                <div class="card-body" style="padding:1rem">
                    <form method="GET" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
                        <div class="form-group" style="margin:0">
                            <label>Month</label>
                            <select name="month" class="form-control" style="width:130px">
                                <option value="">All Months</option>
                                <?php for($m=1;$m<=12;$m++): ?>
                                <option value="<?= $m ?>" <?= $filterMonth==$m?'selected':'' ?>><?= date('F',mktime(0,0,0,$m,1)) ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin:0">
                            <label>Year</label>
                            <select name="year" class="form-control" style="width:100px">
                                <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?>
                                <option value="<?= $y ?>" <?= $filterYear==$y?'selected':'' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="lectures.php" class="btn btn-outline">Clear</a>
                    </form>
                </div>
            </div>

            <?php if ($filterMonth): ?>
            <div class="alert alert-info" style="margin-bottom:1rem">
                📊 Total lectures for <?= date('F',mktime(0,0,0,$filterMonth,1)) ?> <?= $filterYear ?>: <strong><?= $monthTotal ?></strong>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3>Lecture Entries (<?= count($lectures) ?>)</h3>
                </div>
                <?php if ($lectures): ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Subject</th>
                                <th>Lectures</th>
                                <th>Notes</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($lectures as $i => $l): ?>
                        <tr>
                            <td style="color:var(--muted)"><?= $i+1 ?></td>
                            <td><?= fmtDate($l['lecture_date']) ?></td>
                            <td><?= e($l['subject']) ?></td>
                            <td><strong><?= (int)$l['lecture_count'] ?></strong></td>
                            <td style="color:var(--muted)"><?= e($l['notes'] ?: '—') ?></td>
                            <td>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="lecture_id" value="<?= $l['id'] ?>">
                                    <button type="submit" class="btn btn-outline btn-sm"
                                        onclick="return confirmAction('Delete this lecture entry?')"
                                        style="color:var(--rejected);border-color:#fecaca">🗑</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <div class="icon">📅</div>
                    <h3>No lecture entries found</h3>
                    <p>Add your lecture sessions using the form on the right.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: Add Form -->
        <div class="card" style="position:sticky;top:80px">
            <div class="card-header"><h3>➕ Add Lecture Entry</h3></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">

                    <div class="form-group">
                        <label>Date <span style="color:red">*</span></label>
                        <input type="date" name="lecture_date" class="form-control"
                            data-default-today required
                            max="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="form-group">
                        <label>Subject <span style="color:red">*</span></label>
                        <input type="text" name="subject" class="form-control"
                            placeholder="e.g. Data Structures" required>
                    </div>

                    <div class="form-group">
                        <label>Number of Lectures <span style="color:red">*</span></label>
                        <input type="number" name="lecture_count" class="form-control"
                            value="1" min="1" max="10" required>
                    </div>

                    <div class="form-group">
                        <label>Notes (optional)</label>
                        <textarea name="notes" class="form-control" rows="2"
                            placeholder="Any remarks..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%">
                        ➕ Add Entry
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>
</div>
</div>
<?php renderFooter(); ?>
