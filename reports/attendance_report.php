<?php
define('REQUIRED_ROLE','admin');
require_once '../config/auth.php';
require_once '../config/db.php';

$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m'); // default current month

$stmt = $pdo->prepare("
    SELECT u.name, a.date, a.punch_in, a.punch_out
    FROM attendance a
    JOIN users u ON a.user_id = u.id
    WHERE DATE_FORMAT(a.date,'%Y-%m') = ?
    ORDER BY a.date, u.name
");
$stmt->execute([$month]);
$records = $stmt->fetchAll();
?>

<h2>Attendance Report (<?= $month ?>)</h2>

<form method="GET">
    <input type="month" name="month" value="<?= $month ?>">
    <button type="submit">Filter</button>
</form>

<table border="1" cellpadding="10">
<tr>
    <th>Employee</th>
    <th>Date</th>
    <th>Punch In</th>
    <th>Punch Out</th>
</tr>
<?php foreach($records as $r): ?>
<tr>
    <td><?= $r['name'] ?></td>
    <td><?= $r['date'] ?></td>
    <td><?= $r['punch_in'] ?></td>
    <td><?= $r['punch_out'] ?></td>
</tr>
<?php endforeach; ?>
</table>
