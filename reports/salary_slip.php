<?php
define('REQUIRED_ROLE','admin');
require_once '../config/auth.php';
require_once '../config/db.php';

$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

$stmt = $pdo->prepare("
    SELECT p.*, u.name 
    FROM payroll p
    JOIN users u ON p.user_id = u.id
    WHERE DATE_FORMAT(p.month,'%Y-%m') = ?
    ORDER BY u.name
");
$stmt->execute([$month]);
$records = $stmt->fetchAll();
?>

<h2>Salary Slip Report (<?= $month ?>)</h2>

<form method="GET">
    <input type="month" name="month" value="<?= $month ?>">
    <button type="submit">Filter</button>
</form>

<table border="1" cellpadding="10">
<tr>
    <th>Employee</th>
    <th>Basic Salary</th>
    <th>Present Days</th>
    <th>Leave Days</th>
    <th>Deductions</th>
    <th>Net Salary</th>
</tr>
<?php foreach($records as $r): ?>
<tr>
    <td><?= $r['name'] ?></td>
    <td><?= $r['basic_salary'] ?></td>
    <td><?= $r['present_days'] ?></td>
    <td><?= $r['leave_days'] ?></td>
    <td><?= $r['deductions'] ?></td>
    <td><?= $r['net_salary'] ?></td>
</tr>
<?php endforeach; ?>
</table>
