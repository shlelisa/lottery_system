<?php
session_start();

if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit;
}

include '../config/db.php';
include 'includes/layout.php';

// Fetch all rounds with participants/winners from single table
$stmt = $pdo->query("
    SELECT 
        r.id AS round_id,
        r.round_code,
        r.status,
        r.total_amount,
        r.admin_profit,
        r.created_at,
        GROUP_CONCAT(CONCAT(p.name,' (',p.amount_paid,' ETB)') ORDER BY p.id SEPARATOR ', ') AS participants,
        GROUP_CONCAT(CONCAT(p.winner_position,' - ',p.name,' (',p.prize_amount,' ETB)') ORDER BY p.winner_position SEPARATOR ', ') AS winners
    FROM rounds r
    LEFT JOIN participants p ON p.round_id = r.id
    GROUP BY r.id
    ORDER BY r.created_at DESC
");
$rounds = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lottery History</title>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

<style>
body { font-family: Arial, sans-serif; background: linear-gradient(135deg,#141e30,#243b55); color:white; padding:20px; }
.container { max-width:1400px; margin:auto; background: rgba(255,255,255,0.05); padding:20px; border-radius:15px; box-shadow:0 0 20px rgba(0,0,0,0.5); }
h1 { text-align:center; margin-bottom:20px; color: gold; }
a { color: gold; text-decoration:none; font-weight:bold; }
table.dataTable thead th { background-color: rgba(255,255,255,0.1); }
table.dataTable tbody tr:hover { background-color: rgba(255,255,255,0.05); }
</style>
</head>

<body>
<div class="container">
<h1>📜 Advanced Lottery History</h1>
<p style="text-align:center;"><a href="dashboard.php">← Back to Dashboard</a></p>

<table id="historyTable" class="display" style="width:100%; color:white;">
<thead>
<tr>
    <th>Round Code</th>
    <th>Status</th>
    <th>Total Amount</th>
    <th>Admin Profit</th>
    <th>Participants</th>
    <th>Winners</th>
    <th>Created At</th>
</tr>
</thead>
<tbody>
<?php foreach($rounds as $r): ?>
<tr>
    <td><?= htmlspecialchars($r['round_code']) ?></td>
    <td><?= ucfirst(htmlspecialchars($r['status'])) ?></td>
    <td><?= number_format((float)$r['total_amount'],2) ?> ETB</td>
    <td><?= number_format((float)$r['admin_profit'],2) ?> ETB</td>
    <td><?= $r['participants'] ?: '-' ?></td>
    <td><?= $r['winners'] ?: '-' ?></td>
    <td><?= htmlspecialchars($r['created_at']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<script>
$(document).ready(function() {
    $('#historyTable').DataTable({
        order: [[6, "desc"]],
        pageLength: 10,
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
    });
});
</script>
</body>
</html>