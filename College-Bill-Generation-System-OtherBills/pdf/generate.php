<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();
$user=$user=currentUser();
$uid=$user['id'];
$billId=(int)($_GET['id']??0);
if($user['role']==='teacher'){
    $q=$pdo->prepare("SELECT b.*,u.name AS tname,u.email,u.department,u.teacher_type,u.phone,u.rate_per_lecture FROM bills b JOIN users u ON u.id=b.teacher_id WHERE b.id=? AND b.teacher_id=? AND b.status='approved'");
    $q->execute([$billId,$uid]);
}else{
    $q=$pdo->prepare("SELECT b.*,u.name AS tname,u.email,u.department,u.teacher_type,u.phone,u.rate_per_lecture FROM bills b JOIN users u ON u.id=b.teacher_id WHERE b.id=?");
    $q->execute([$billId]);
}
$bill=$q->fetch();
if(!$bill){die('<p>Bill not found or not approved.</p>');}
$lq=$pdo->prepare("SELECT l.* FROM lectures l JOIN bill_lectures bl ON bl.lecture_id=l.id WHERE bl.bill_id=? ORDER BY l.lecture_date ASC");
$lq->execute([$billId]);
$lectures=$lq->fetchAll();
$hod=$pdo->query("SELECT name FROM users WHERE role='hod' LIMIT 1")->fetch();
$type=$bill['teacher_type']??'visiting';
$rate=(float)$bill['rate_per_lecture'];
$totalLec=(int)$bill['total_lectures'];
$totalAmt=(float)$bill['total_amount'];
$month=$bill['month_year'];
$theoryHrs=$totalLec;
$isVisiting=in_array($type,['visiting','guest']);
$isAdjunct=($type==='earn_and_learn');
$isPoP=false;
function h($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function n($v){return number_format((float)$v,2);}
function cross(){
    return '<div style="position:absolute;inset:0;pointer-events:none;z-index:8;overflow:hidden">'.
           '<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" preserveAspectRatio="none" style="position:absolute;top:0;left:0;width:100%;height:100%">'.
           '<line x1="0" y1="0" x2="100%" y2="100%" stroke="#777" stroke-width="1.5"/>'.
           '<line x1="100%" y1="0" x2="0" y2="100%" stroke="#777" stroke-width="1.5"/>'.
           '</svg></div>';
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bill</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
html{font-size:9.5pt}
body{font-family:'Times New Roman',Times,serif;color:#000;background:#ccc}
.pbar{position:fixed;top:0;left:0;right:0;z-index:999;background:#1a3a6e;color:#fff;display:flex;align-items:center;gap:12px;padding:9px 18px;font-family:Arial,sans-serif;font-size:12px}
.pbar button{background:#fff;color:#1a3a6e;border:none;border-radius:4px;padding:6px 16px;font-weight:700;font-size:12px;cursor:pointer}
.pbar a{color:rgba(255,255,255,.7);text-decoration:none;margin-left:auto}
.page{width:210mm;min-height:297mm;padding:13mm 14mm 12mm;margin:0 auto 14px;background:#fff;page-break-after:always;position:relative}
@media screen{body{padding-top:50px}.page{box-shadow:0 2px 10px rgba(0,0,0,.3)}}
@media print{.pbar{display:none!important}body{background:#fff;padding-top:0}.page{box-shadow:none;margin:0}}
.c{text-align:center}.b{font-weight:bold}.u{text-decoration:underline}.j{text-align:justify}
.hdr h1{font-size:11.5pt;font-weight:bold;text-align:center;line-height:1.4}
.hdr h2{font-size:10pt;font-weight:bold;text-align:center}
.hdr p{font-size:8.5pt;text-align:center}
hr.thick{border:none;border-top:2.5px solid #000;margin:2.5mm 0}
hr.thin{border:none;border-top:1.2px solid #000;margin:1.5mm 0}
.fl{display:inline-block;border-bottom:1px solid #000;min-width:40mm;padding:0 1mm;vertical-align:bottom}
.binfo{font-size:9.5pt;line-height:2.1;margin:3mm 0}
.sec{position:relative;margin-bottom:4mm;overflow:hidden}
table{width:100%;border-collapse:collapse;font-size:9pt}
th,td{border:1px solid #000;padding:1.5mm 2mm;vertical-align:top}
.sh{background:#d0d0d0;font-weight:bold;font-size:10pt}
.ac{width:22mm;text-align:right}
.lc{width:76%}
.tb{font-weight:bold}
.at th,.at td{border:1px solid #000;padding:1.5mm 2mm;text-align:center;vertical-align:middle}
.at th{font-weight:bold}
.at .tl{text-align:left}
.cpara{font-size:9.5pt;text-align:justify;line-height:1.7;margin-bottom:4mm}
.bbox{border:1.5px solid #000;padding:3mm 4mm;margin:4mm 0;font-size:9.5pt;line-height:2}
.bbox h3{font-size:10pt;font-weight:bold;text-align:center;margin-bottom:2mm}
.brow{display:flex;gap:16mm}
.sgg{display:grid;grid-template-columns:1fr 1fr;gap:8mm;font-size:9.5pt;line-height:1.9;margin-top:8mm}
.sr{text-align:right}
.prin{text-align:center;margin-top:16mm;font-size:9.5pt;line-height:1.9}
</style>
</head>
<body>

<div class="pbar">
  <button onclick="window.print()">Print / Save as PDF</button>
  <span>Official Bill</span>
  <a href="javascript:history.back()">Back</a>
</div>

<div class="page">
<div class="hdr c">
  <h1>GOVERNMENT COLLEGE OF ENGINEERING AURANGABAD<br>CHHATRAPATI SAMBHAJINAGAR</h1>
  <h2>(An Autonomous Institute of Government of Maharashtra)</h2>
</div>
<hr class="thick">
<p class="b" style="font-size:9.5pt">Details of bill for PoP/Adjunct Faculty/ Sessional Instructor/ Visiting Faculty/Expert Faculty</p>
<hr class="thin">
<p class="c b" style="font-size:11pt;margin:3mm 0">BILL FOR THE MONTH OF &nbsp;<span class="fl">&nbsp;<?= h($month) ?>&nbsp;</span></p>
<div class="binfo">
  <div><span class="b">Name of the Faculty:</span> <span class="fl" style="min-width:90mm">&nbsp;<?= h($bill['tname']) ?>&nbsp;</span></div>
  <div><span class="b">Department:</span> <span class="fl" style="min-width:70mm">&nbsp;<?= h($bill['department']??'Computer Science') ?>&nbsp;</span></div>
  <div><span class="b">Appointment order number:</span> <span class="fl" style="min-width:65mm">&nbsp;</span> &nbsp;<span class="b">dated</span> <span class="fl" style="min-width:22mm">&nbsp;</span></div>
</div>
<p class="b" style="font-size:9pt;margin-bottom:2mm">Note: Fill in the details applicable</p>

<div class="sec">
<?php if(!$isPoP) echo cross(); ?>
<table>
  <tr><td colspan="2" class="sh">For Full-time Professor of Practice/ Adjunct Faculty</td></tr>
  <tr><td class="lc">Fixed Emoluments per month (as per office order)</td><td class="ac">Rs.&nbsp;<?= $isPoP?n($totalAmt):'' ?></td></tr>
  <tr><td>a) Total no. of working days in the month (including holidays) in the month</td><td class="ac"></td></tr>
  <tr><td>b) No. of permissible casual leaves availed in the month</td><td class="ac"></td></tr>
  <tr><td>c) No. of additional leaves availed in the month</td><td class="ac"></td></tr>
  <tr><td>d) Total number of days permitted to claim the bill (a - c)</td><td class="ac"></td></tr>
  <tr><td class="tb">Bill claimed for <span class="fl" style="min-width:18mm">&nbsp;</span> days in the month @ Rs. <span class="fl" style="min-width:16mm">&nbsp;</span> per day</td><td class="ac tb">Rs.</td></tr>
</table>
</div>

<div class="sec">
<?php if(!$isVisiting) echo cross(); ?>
<table>
  <tr><td colspan="2" class="sh">For Hourly based Visiting faculty /Sessional Instructor /Expert Faculty</td></tr>
  <tr><td class="lc">Permissible rate /hour (as per office order) for-</td><td class="ac"></td></tr>
  <tr><td style="padding-left:8mm">1.&nbsp; Theory/ Tutorials</td><td class="ac">Rs.&nbsp;<?= $isVisiting?n($rate):'' ?></td></tr>
  <tr><td style="padding-left:8mm">2.&nbsp; Practical/ Project</td><td class="ac">Rs.</td></tr>
  <tr><td style="padding-left:8mm">3.&nbsp; Other works</td><td class="ac">Rs.</td></tr>
  <tr><td>Name of Program and course</td><td class="ac">1.<br>2.</td></tr>
  <tr><td>Total no. of hours in the month (details as per Annexure I attached)</td><td class="ac"></td></tr>
  <tr><td style="padding-left:8mm">1.&nbsp; Theory and Tutorials</td><td class="ac">1.&nbsp;<?= $isVisiting?$theoryHrs.' hrs':'' ?></td></tr>
  <tr><td style="padding-left:8mm">2.&nbsp; Practical / Project</td><td class="ac">2.&nbsp;<?= $isVisiting?'0 hrs':'' ?></td></tr>
  <tr><td style="padding-left:8mm">3.&nbsp; Other works</td><td class="ac">3.&nbsp;<?= $isVisiting?'0 hrs':'' ?></td></tr>
  <tr><td>Bill claimed for-</td><td class="ac"></td></tr>
  <tr><td style="padding-left:8mm">a.&nbsp; Theory and Tutorials (for <span class="fl" style="min-width:10mm">&nbsp;<?= $isVisiting?$theoryHrs:'' ?>&nbsp;</span> hrs @ Rs. <span class="fl" style="min-width:12mm">&nbsp;<?= $isVisiting?n($rate):'' ?>&nbsp;</span> per hour)</td><td class="ac">Rs.&nbsp;<?= $isVisiting?n($theoryHrs*$rate):'' ?></td></tr>
  <tr><td style="padding-left:8mm">b.&nbsp; Practical / Project (for <span class="fl" style="min-width:10mm">&nbsp;0&nbsp;</span> hrs @ Rs. <span class="fl" style="min-width:12mm">&nbsp;</span> per hour)</td><td class="ac">Rs.&nbsp;<?= $isVisiting?'0.00':'' ?></td></tr>
  <tr><td style="padding-left:8mm">c.&nbsp; Other works (for <span class="fl" style="min-width:10mm">&nbsp;0&nbsp;</span> hrs @ Rs. <span class="fl" style="min-width:14mm">&nbsp;</span> per hour)</td><td class="ac">Rs.&nbsp;<?= $isVisiting?'0.00':'' ?></td></tr>
  <tr><td class="tb">Total bill claimed (a+b+c)</td><td class="ac tb">Rs.&nbsp;<?= $isVisiting?n($totalAmt):'' ?></td></tr>
</table>
</div>

<div class="sec">
<?php if(!$isAdjunct) echo cross(); ?>
<table>
  <tr><td colspan="2" class="sh">For Adjunct Faculty (Credit Based)</td></tr>
  <tr><td class="lc">Permissible rate per credit (as per office order)</td><td class="ac">Rs.&nbsp;<?= $isAdjunct?n($rate):'' ?></td></tr>
  <tr><td>Name of Program and course</td><td class="ac">1.<br>2.</td></tr>
  <tr><td>Total no. of credits in the semester</td><td class="ac">1.&nbsp;<?= $isAdjunct?$totalLec:'' ?><br>2.</td></tr>
  <tr><td class="tb">Bill claimed for <span class="fl" style="min-width:16mm">&nbsp;<?= $isAdjunct?$totalLec:'' ?>&nbsp;</span> credits in the semester @ Rs. <span class="fl" style="min-width:16mm">&nbsp;<?= $isAdjunct?n($rate):'' ?>&nbsp;</span> per credit</td><td class="ac tb">Rs.&nbsp;<?= $isAdjunct?n($totalAmt):'' ?></td></tr>
</table>
</div>
</div>

<div class="page">
<p class="c b u" style="font-size:11pt;margin-bottom:5mm">CERTIFICATE</p>
<p class="cpara">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;I certify that the above bill claimed by me for said duration of academic load which is actually engaged by me and is in accordance with attendance register and record of department. The bill claimed herewith is correct according to the rates as prescribed in the order received from Govt. College of Engineering Aurangabad, Chhatrapati Sambhajinagar. I know that I will be responsible and accountable for any wrongful claim. I will return any excess amount disbursed, if found in future.</p>
<div class="bbox">
  <h3>Bank Details of Claimant</h3>
  <div>Name of Bank: <span class="fl" style="min-width:85mm">&nbsp;<?= h($bill['bank_name']??'') ?>&nbsp;</span></div>
  <div>IFSC : <span class="fl" style="min-width:85mm">&nbsp;<?= h($bill['ifsc']??'') ?>&nbsp;</span></div>
  <div class="brow"><div>A/C No. <span class="fl" style="min-width:55mm">&nbsp;<?= h($bill['account_no']??'') ?>&nbsp;</span></div><div>PAN: <span class="fl" style="min-width:38mm">&nbsp;<?= h($bill['pan']??'') ?>&nbsp;</span></div></div>
  <div>Mobile No. <span class="fl" style="min-width:55mm">&nbsp;<?= h($bill['phone']??'') ?>&nbsp;</span></div>
</div>
<div class="sgg" style="margin-top:10mm">
  <div>Date: <span class="fl" style="min-width:35mm">&nbsp;<?= date('d / m / Y') ?>&nbsp;</span><br>Place: Chhatrapati Sambhaji Nagar</div>
  <div class="sr">Signature of faculty:<br><span style="display:inline-block;margin-top:12mm">Name: <span class="fl" style="min-width:50mm">&nbsp;<?= h($bill['tname']) ?>&nbsp;</span></span></div>
</div>
<p class="cpara" style="margin-top:8mm">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;I certify that the work load as stated above has been checked and amount claimed is correct and hence submitted to sanction. Certified that the amount is not exceeding the ceiling of 20% earned during the academic session / year.</p>
<div style="margin-top:8mm;font-size:9.5pt;line-height:2">Signature of Dept. Faculty I/C<br>Date:</div>
<div class="sgg" style="margin-top:6mm"><div></div><div class="sr">Signature of HoD with Stamp<br>Date: <span class="fl" style="min-width:35mm">&nbsp;</span></div></div>
<div class="prin">Principal<br>Govt. College of Engineering Aurangabad<br>Chhatrapati Sambhajinagar</div>
</div>

<div class="page">
<p class="c b u" style="font-size:11pt;margin-bottom:1mm">ANNEXURE I</p>
<p class="c b" style="font-size:10pt;margin-bottom:3mm">(For Visiting Faculty/ Expert faculty / Sessional Instructor)</p>
<p class="b" style="margin-bottom:3mm">Details of academics and other works for the month of <span class="fl" style="min-width:40mm">&nbsp;<?= h($month) ?>&nbsp;</span></p>
<table class="at">
<thead>
  <tr>
    <th style="width:9mm">Sr.<br>No.</th>
    <th style="width:24mm">Class</th>
    <th style="width:30mm">Course</th>
    <th style="width:24mm">Date</th>
    <th style="width:18mm">Time</th>
    <th style="width:23mm">Theory<br>Lectures/<br>Tutorials<br>(Hrs)</th>
    <th style="width:22mm">Practicals/<br>Project<br>(Hrs)</th>
    <th style="width:28mm">Any other<br>work<br>assigned by<br>HoD /<br>Principal<br>(Hrs)</th>
  </tr>
</thead>
<tbody>
<?php $rowsUsed=0; foreach($lectures as $i=>$l): $rowsUsed++; ?>
<tr>
  <td><?= $i+1 ?></td>
  <td class="tl"></td>
  <td class="tl"><?= h($l['subject']) ?></td>
  <td><?= date('d/m/Y',strtotime($l['lecture_date'])) ?></td>
  <td></td>
  <td><?= (int)$l['lecture_count'] ?></td>
  <td>-</td>
  <td>-</td>
</tr>
<?php endforeach; for($r=$rowsUsed;$r<20;$r++): ?>
<tr style="height:7mm"><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<?php endfor; ?>
<tr><td colspan="4"></td><td class="b">Total Hrs.</td><td class="b"><?= $theoryHrs ?></td><td class="b">0</td><td class="b">0</td></tr>
</tbody>
</table>
<div class="sgg" style="margin-top:8mm">
  <div style="font-size:9.5pt;line-height:2">Signature of Dept. Faculty I/C<br>Date:</div>
  <div class="sr" style="font-size:9.5pt;line-height:2">Signature &amp; Name of Faculty:</div>
</div>
<div style="text-align:right;margin-top:10mm;font-size:9.5pt;line-height:2">Signature of HoD with Stamp<br>Date: <span class="fl" style="min-width:35mm">&nbsp;</span></div>
</div>

<div class="page">
<p class="c b u" style="font-size:11pt;margin-bottom:1mm">ANNEXURE II</p>
<p class="c b" style="font-size:10pt;margin-bottom:3mm">(For Full-Time PoP/ Adjunct Faculty)</p>
<p class="b" style="margin-bottom:3mm">Details of academics and other engagements for the month/semester <span class="fl" style="min-width:40mm">&nbsp;<?= h($month) ?>&nbsp;</span></p>
<table class="at">
<thead><tr><th style="width:14mm">Sr.No.</th><th>Activities</th><th style="width:60mm">Particulars of participation</th></tr></thead>
<tbody>
  <tr><td>1</td><td class="tl"><span class="b">Department Level</span><br>(e.g. Academic, Curriculum development,<br>T &amp; P activities, Faculty Development<br>Programme, Research &amp; Development etc.)</td><td style="height:20mm"></td></tr>
  <tr><td>2</td><td class="tl"><span class="b">Institute Level</span><br>(e.g. NBA, administrative work, Institute<br>level portfolio etc.)</td><td style="height:20mm"></td></tr>
  <tr><td>3</td><td class="tl"><span class="b">Any Other</span><br>(e.g. social events etc.)</td><td style="height:18mm"></td></tr>
</tbody>
</table>
<div class="sgg" style="margin-top:12mm">
  <div style="font-size:9.5pt;line-height:2">Signature of Dept. Faculty I/C<br>Date:</div>
  <div class="sr" style="font-size:9.5pt;line-height:2">Signature &amp; Name of Faculty:</div>
</div>
<div style="text-align:right;margin-top:10mm;font-size:9.5pt;line-height:2">Signature of HoD with Stamp<br>Date: <span class="fl" style="min-width:35mm">&nbsp;</span></div>
</div>

<script>
if(new URLSearchParams(window.location.search).get('print')==='1')
  window.addEventListener('load',()=>setTimeout(()=>window.print(),600));
</script>
</body>
</html>
