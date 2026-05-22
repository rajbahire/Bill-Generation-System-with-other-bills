<?php
// ============================================================
//  hod/other-bills.php - HOD Other Bills
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireHOD();
$user = currentUser();

$billType = $_GET['type'] ?? 'practical';
$allowedTypes = ['practical', 'earn_learn', 'seminar'];
if (!in_array($billType, $allowedTypes, true)) {
    $billType = 'practical';
}

$typeLabels = [
    'practical'  => 'Practical Examination Bill',
    'earn_learn' => 'Earn and Learn Student Bill',
    'seminar'    => 'Seminar Bill',
];

function selectedType(string $current, string $type): string {
    return $current === $type ? 'active' : '';
}

renderHead('Other Bills');
?>
<style>
.bill-type-tabs{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:1.2rem}
.bill-type-tabs .btn.active{background:var(--primary);color:#fff;border-color:var(--primary)}
.hint-box{background:var(--primary-lt);border:1px solid #BFCFEA;color:var(--primary);border-radius:var(--radius-sm);padding:10px 12px;font-size:.82rem;margin-bottom:1.2rem}
.line-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:1rem}
</style>
<div class="app-layout">
<?php renderSidebar('other-bills','hod',$user); ?>
<div class="main-content">
<?php renderTopbar('Other Bills'); ?>
<div class="page-body">
    <?= showFlash() ?>

    <div class="page-header">
        <h1>Other Bills</h1>
        <p>Create printable bills for practical examination, Earn and Learn students, and seminar payments.</p>
    </div>

    <div class="bill-type-tabs">
        <?php foreach ($typeLabels as $type => $label): ?>
        <a class="btn btn-outline <?= selectedType($billType, $type) ?>" href="?type=<?= e($type) ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>

    <div class="hint-box">
        These forms open in a print-ready page. Existing teacher bill generation and approved bill PDFs are not changed.
    </div>

    <div class="card">
        <div class="card-header"><h3><?= e($typeLabels[$billType]) ?></h3></div>
        <div class="card-body">
            <form method="POST" action="../pdf/other-bill.php" target="_blank">
                <input type="hidden" name="bill_type" value="<?= e($billType) ?>">

                <?php if ($billType === 'practical'): ?>
                <div class="form-grid">
                    <div class="form-group"><label>Name of Examiner / Faculty</label><input class="form-control" name="faculty_name" required></div>
                    <div class="form-group"><label>Department</label><input class="form-control" name="department" value="Computer Science"></div>
                    <div class="form-group"><label>Programme / Class</label><input class="form-control" name="program" placeholder="e.g. B.Tech CSE"></div>
                    <div class="form-group"><label>Semester</label><input class="form-control" name="semester"></div>
                    <div class="form-group"><label>Subject / Course</label><input class="form-control" name="subject" required></div>
                    <div class="form-group"><label>Examination</label><input class="form-control" name="exam_name" placeholder="Winter / Summer Examination"></div>
                    <div class="form-group"><label>Date of Practical Exam</label><input type="date" class="form-control" name="exam_date"></div>
                    <div class="form-group"><label>No. of Students</label><input type="number" min="0" step="1" class="form-control" name="students" value="0"></div>
                    <div class="form-group"><label>Rate per Student</label><input type="number" min="0" step="0.01" class="form-control" name="rate" value="0"></div>
                    <div class="form-group"><label>Other Amount</label><input type="number" min="0" step="0.01" class="form-control" name="other_amount" value="0"></div>
                    <div class="form-group"><label>Bill Date</label><input type="date" class="form-control" name="bill_date" value="<?= date('Y-m-d') ?>"></div>
                    <div class="form-group"><label>Academic Year</label><input class="form-control" name="academic_year" value="<?= date('Y') . '-' . (date('y') + 1) ?>"></div>
                </div>
                <hr class="divider">
                <div class="form-grid">
                    <div class="form-group"><label>Bank Name</label><input class="form-control" name="bank_name"></div>
                    <div class="form-group"><label>Account No.</label><input class="form-control" name="account_no"></div>
                    <div class="form-group"><label>IFSC</label><input class="form-control" name="ifsc"></div>
                    <div class="form-group"><label>PAN</label><input class="form-control" name="pan"></div>
                </div>

                <?php elseif ($billType === 'earn_learn'): ?>
                <div class="form-grid">
                    <div class="form-group"><label>Student Name</label><input class="form-control" name="student_name" required></div>
                    <div class="form-group"><label>Department</label><input class="form-control" name="department" value="Computer Science"></div>
                    <div class="form-group"><label>Class / Year</label><input class="form-control" name="class_year"></div>
                    <div class="form-group"><label>Month</label><select class="form-control" name="month"><?php for($m=1;$m<=12;$m++): ?><option value="<?= $m ?>" <?= $m==(int)date('n')?'selected':'' ?>><?= date('F',mktime(0,0,0,$m,1)) ?></option><?php endfor; ?></select></div>
                    <div class="form-group"><label>Year</label><input type="number" class="form-control" name="year" value="<?= date('Y') ?>"></div>
                    <div class="form-group"><label>Work Assigned</label><input class="form-control" name="work_assigned" placeholder="Department / office work"></div>
                    <div class="form-group"><label>Working Days</label><input type="number" min="0" step="1" class="form-control" name="working_days" value="0"></div>
                    <div class="form-group"><label>Hours per Day</label><input type="number" min="0" step="0.5" class="form-control" name="hours_per_day" value="2"></div>
                    <div class="form-group"><label>Rate per Hour</label><input type="number" min="0" step="0.01" class="form-control" name="rate" value="0"></div>
                    <div class="form-group"><label>Bill Date</label><input type="date" class="form-control" name="bill_date" value="<?= date('Y-m-d') ?>"></div>
                </div>
                <hr class="divider">
                <div class="form-grid">
                    <div class="form-group"><label>Bank Name</label><input class="form-control" name="bank_name"></div>
                    <div class="form-group"><label>Account No.</label><input class="form-control" name="account_no"></div>
                    <div class="form-group"><label>IFSC</label><input class="form-control" name="ifsc"></div>
                    <div class="form-group"><label>Mobile No.</label><input class="form-control" name="mobile"></div>
                </div>

                <?php else: ?>
                <div class="form-grid">
                    <div class="form-group"><label>Name of Speaker / Faculty</label><input class="form-control" name="speaker_name" required></div>
                    <div class="form-group"><label>Department</label><input class="form-control" name="department" value="Computer Science"></div>
                    <div class="form-group"><label>Seminar / Event Title</label><input class="form-control" name="seminar_title" required></div>
                    <div class="form-group"><label>Topic</label><input class="form-control" name="topic"></div>
                    <div class="form-group"><label>Date</label><input type="date" class="form-control" name="seminar_date"></div>
                    <div class="form-group"><label>Duration</label><input class="form-control" name="duration" placeholder="e.g. 2 hours"></div>
                    <div class="form-group"><label>Honorarium</label><input type="number" min="0" step="0.01" class="form-control" name="honorarium" value="0"></div>
                    <div class="form-group"><label>TA / DA</label><input type="number" min="0" step="0.01" class="form-control" name="ta_da" value="0"></div>
                    <div class="form-group"><label>Other Amount</label><input type="number" min="0" step="0.01" class="form-control" name="other_amount" value="0"></div>
                    <div class="form-group"><label>Bill Date</label><input type="date" class="form-control" name="bill_date" value="<?= date('Y-m-d') ?>"></div>
                </div>
                <hr class="divider">
                <div class="form-grid">
                    <div class="form-group"><label>Bank Name</label><input class="form-control" name="bank_name"></div>
                    <div class="form-group"><label>Account No.</label><input class="form-control" name="account_no"></div>
                    <div class="form-group"><label>IFSC</label><input class="form-control" name="ifsc"></div>
                    <div class="form-group"><label>PAN</label><input class="form-control" name="pan"></div>
                </div>
                <?php endif; ?>

                <hr class="divider">
                <button type="submit" class="btn btn-primary">Generate Printable Bill</button>
                <a href="dashboard.php" class="btn btn-outline">Back</a>
            </form>
        </div>
    </div>
</div>
</div>
</div>
<?php renderFooter(); ?>
