<?php
// ============================================================
//  pdf/generate.php — Bill PDF Generator
//  Uses browser print / DOMPDF if available.
//  Works out-of-the-box with XAMPP (no library needed).
//  If DOMPDF is installed in /vendor, it will use that instead.
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();
$user = currentUser();
$uid  = $user['id'];

$billId = (int)($_GET['id'] ?? 0);

// Teachers can only download their own approved bills
// HOD can download any bill
if ($user['role'] === 'teacher') {
    $bill = $pdo->prepare("SELECT b.*, u.name AS teacher_name, u.email, u.department, u.teacher_type, u.phone FROM bills b JOIN users u ON u.id=b.teacher_id WHERE b.id=? AND b.teacher_id=? AND b.status='approved'");
    $bill->execute([$billId, $uid]);
} else {
    $bill = $pdo->prepare("SELECT b.*, u.name AS teacher_name, u.email, u.department, u.teacher_type, u.phone FROM bills b JOIN users u ON u.id=b.teacher_id WHERE b.id=?");
    $bill->execute([$billId]);
}
$bill = $bill->fetch();
if (!$bill) { die('Bill not found or not accessible.'); }

// Lectures
$lec = $pdo->prepare("SELECT l.* FROM lectures l JOIN bill_lectures bl ON bl.lecture_id=l.id WHERE bl.bill_id=? ORDER BY l.lecture_date ASC");
$lec->execute([$billId]);
$lectures = $lec->fetchAll();

// HOD info
$hod = $pdo->query("SELECT name, department FROM users WHERE role='hod' LIMIT 1")->fetch();

// ---- DOMPDF support (optional) ----
// If you have DOMPDF installed: composer require dompdf/dompdf
// Uncomment the block below to generate a real PDF file download.
/*
$dompdfPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($dompdfPath)) {
    require_once $dompdfPath;
    use Dompdf\Dompdf;
    ob_start();
    // (render HTML below, then:)
    $html = ob_get_clean();
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("Bill_{$bill['month_year']}_{$bill['teacher_name']}.pdf", ['Attachment' => true]);
    exit;
}
*/
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bill — <?= e($bill['month_year']) ?> — <?= e($bill['teacher_name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            color: #1a2540;
            background: #f0ede7;
            padding: 2rem;
        }

        .bill-page {
            background: #ffffff;
            max-width: 780px;
            margin: 0 auto;
            padding: 3rem;
            border-radius: 4px;
        }

        /* Top header */
        .bill-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #1a2540;
            margin-bottom: 1.5rem;
        }
        .college-info h1 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1a2540;
        }
        .college-info p { font-size: 0.8rem; color: #6b7280; margin-top: 3px; }
        .bill-title {
            text-align: right;
        }
        .bill-title h2 {
            font-size: 1.6rem;
            font-weight: 300;
            color: #1a2540;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .bill-title .bill-num {
            font-size: 0.78rem;
            color: #6b7280;
            margin-top: 4px;
        }
        .bill-title .status-approved {
            display: inline-block;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #059669;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 600;
            margin-top: 6px;
        }

        /* Info grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.8rem;
        }
        .info-box {
            background: #f8f5ef;
            border-radius: 8px;
            padding: 1rem 1.2rem;
        }
        .info-box h3 {
            font-size: 0.68rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #9ca3af;
            margin-bottom: 0.7rem;
        }
        .info-row { display: flex; justify-content: space-between; padding: 3px 0; font-size: 0.82rem; }
        .info-row .label { color: #6b7280; }
        .info-row .val   { font-weight: 500; color: #1a2540; }

        /* Lecture table */
        table { width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; }
        thead th {
            background: #1a2540;
            color: #ffffff;
            padding: 9px 12px;
            text-align: left;
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        tbody td {
            padding: 9px 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 0.83rem;
        }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:nth-child(even) { background: #fafaf9; }
        tfoot td {
            padding: 10px 12px;
            background: #f3f4f6;
            font-weight: 600;
        }

        /* Totals box */
        .totals-box {
            float: right;
            width: 280px;
            margin-bottom: 2rem;
        }
        .totals-row {
            display: flex; justify-content: space-between;
            padding: 7px 0;
            font-size: 0.85rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .totals-row:last-child { border-bottom: none; }
        .totals-row.grand {
            background: #1a2540;
            color: #ffffff;
            margin: 0 -1px;
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 700;
        }
        .totals-row.grand .amount { color: #e2c97e; }

        .clearfix::after { content: ''; display: table; clear: both; }

        /* Signatures */
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 3rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
        }
        .sig-box { text-align: center; }
        .sig-line {
            border-bottom: 1px solid #6b7280;
            height: 40px;
            margin-bottom: 6px;
        }
        .sig-label { font-size: 0.78rem; color: #6b7280; }
        .sig-name  { font-size: 0.85rem; font-weight: 600; color: #1a2540; }

        /* Footer */
        .bill-footer {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 0.72rem;
            color: #9ca3af;
        }

        /* Print button (hidden on print) */
        .no-print {
            max-width: 780px;
            margin: 0 auto 1rem;
            display: flex; gap: 10px;
        }
        .btn-print {
            padding: 9px 20px;
            background: #1a2540;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.88rem;
            cursor: pointer;
        }
        .btn-back {
            padding: 9px 20px;
            background: transparent;
            border: 1px solid #d1d5db;
            color: #374151;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.88rem;
            cursor: pointer;
            text-decoration: none;
        }

        @media print {
            body { background: #fff; padding: 0; }
            .bill-page { padding: 1.5rem; max-width: 100%; box-shadow: none; border-radius: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button class="btn-print" onclick="window.print()">🖨 Print / Save as PDF</button>
    <a href="javascript:history.back()" class="btn-back">← Back</a>
</div>

<div class="bill-page">

    <!-- Header -->
    <div class="bill-header">
        <div class="college-info">
            <h1><?= e($bill['department'] ?: 'Department of Computer Science') ?></h1>
            <p>Internal Bill — Visiting / Guest Teacher Remuneration</p>
        </div>
        <div class="bill-title">
            <h2>Bill</h2>
            <div class="bill-num">Bill #<?= str_pad($bill['id'], 5, '0', STR_PAD_LEFT) ?></div>
            <div class="status-approved">✅ Approved</div>
        </div>
    </div>

    <!-- Info Grid -->
    <div class="info-grid">
        <div class="info-box">
            <h3>Teacher Details</h3>
            <div class="info-row"><span class="label">Name</span><span class="val"><?= e($bill['teacher_name']) ?></span></div>
            <div class="info-row"><span class="label">Email</span><span class="val"><?= e($bill['email']) ?></span></div>
            <div class="info-row"><span class="label">Phone</span><span class="val"><?= e($bill['phone'] ?: '—') ?></span></div>
            <div class="info-row"><span class="label">Type</span><span class="val"><?= ucfirst(str_replace('_',' ',$bill['teacher_type']?:'visiting')) ?></span></div>
        </div>
        <div class="info-box">
            <h3>Bill Details</h3>
            <div class="info-row"><span class="label">Month</span><span class="val"><?= e($bill['month_year']) ?></span></div>
            <div class="info-row"><span class="label">Period</span><span class="val"><?= fmtDate($bill['period_from'],'d M Y') ?> – <?= fmtDate($bill['period_to'],'d M Y') ?></span></div>
            <div class="info-row"><span class="label">Submitted</span><span class="val"><?= fmtDate($bill['submitted_at'],'d M Y') ?></span></div>
            <div class="info-row"><span class="label">Approved</span><span class="val"><?= fmtDate($bill['reviewed_at'],'d M Y') ?></span></div>
        </div>
    </div>

    <!-- Lecture Table -->
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Subject</th>
                <th style="text-align:center">No. of Lectures</th>
                <th style="text-align:right">Rate (₹)</th>
                <th style="text-align:right">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($lectures as $i => $l):
            $amt = $l['lecture_count'] * (float)$bill['rate_per_lecture']; ?>
        <tr>
            <td><?= $i+1 ?></td>
            <td><?= fmtDate($l['lecture_date'],'d M Y') ?></td>
            <td><?= e($l['subject']) ?></td>
            <td style="text-align:center"><?= (int)$l['lecture_count'] ?></td>
            <td style="text-align:right"><?= number_format($bill['rate_per_lecture'],2) ?></td>
            <td style="text-align:right"><?= number_format($amt,2) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Totals -->
    <div class="clearfix">
        <div class="totals-box">
            <div class="totals-row">
                <span>Total Lectures</span>
                <span><?= (int)$bill['total_lectures'] ?></span>
            </div>
            <div class="totals-row">
                <span>Rate per Lecture</span>
                <span>₹<?= number_format($bill['rate_per_lecture'],2) ?></span>
            </div>
            <div style="height:8px"></div>
            <div class="totals-row grand">
                <span>Total Payable</span>
                <span class="amount">₹<?= number_format($bill['total_amount'],2) ?></span>
            </div>
        </div>
    </div>

    <!-- Signatures -->
    <div class="signatures">
        <div class="sig-box">
            <div class="sig-line"></div>
            <div class="sig-name"><?= e($bill['teacher_name']) ?></div>
            <div class="sig-label">Teacher's Signature</div>
        </div>
        <div class="sig-box">
            <div class="sig-line"></div>
            <div class="sig-name"><?= e($hod['name'] ?? 'Head of Department') ?></div>
            <div class="sig-label">HOD Signature &amp; Stamp</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="bill-footer">
        This is a system-generated bill. Generated on <?= date('d F Y, h:i A') ?> &middot; Bill Management System
    </div>

</div>

</body>
</html>
