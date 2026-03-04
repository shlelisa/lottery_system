<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once '../config/db.php';
require_once 'includes/layout.php';

$admin_id = (int) $_SESSION['admin_id'];

/* ===============================
   SUMMARY DATA (Safe + No NULL)
=================================*/
$totalRounds = (int) $pdo->query("SELECT COUNT(*) FROM rounds")->fetchColumn();
$totalRevenue = (float) ($pdo->query("SELECT IFNULL(SUM(total_amount),0) FROM rounds")->fetchColumn());
$totalWinners = (int) $pdo->query("SELECT COUNT(*) FROM participants WHERE is_winner=1")->fetchColumn();
$totalAdminProfit = (float) ($pdo->query("SELECT IFNULL(SUM(admin_profit),0) FROM rounds")->fetchColumn());

/* ===============================
   FETCH ROUNDS WITH PARTICIPANTS/WINNERS
=================================*/
$stmt = $pdo->query("
SELECT 
    r.id AS round_id,
    r.round_code,
    r.entry_fee,
    r.current_participants,
    r.max_participants,
    r.status,
    r.total_amount,
    r.admin_profit,
    r.created_at,
    GROUP_CONCAT(CONCAT(p.winner_position,' - ',p.name) ORDER BY p.winner_position SEPARATOR ', ') AS winners
FROM rounds r
LEFT JOIN participants p ON r.id = p.round_id AND p.is_winner=1
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
<title>Lottery Admin Dashboard</title>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
<style>
body { font-family: Arial, sans-serif; background: linear-gradient(135deg,#141e30,#243b55); color:white; padding:20px;}
h1,h2 { text-align:center; margin-bottom:20px; }
a { color: gold; text-decoration:none; margin-right:10px; }
.cards { display:flex; flex-wrap:wrap; justify-content:space-around; margin:20px 0; }
.card { background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); padding:25px; border-radius:15px; width:220px; margin:10px; text-align:center; box-shadow:0 0 20px rgba(0,0,0,0.5); transition: transform 0.2s; }
.card:hover { transform: translateY(-5px); box-shadow:0 10px 30px rgba(0,0,0,0.7);}
.card h3 { margin:10px 0; font-size:2.2em; color: gold; }
table.dataTable thead th { background-color: rgba(255,255,255,0.1); }
button, .btn { padding:8px 15px; border:none; border-radius:8px; background: gold; font-weight:bold; cursor:pointer; color:#000; text-decoration:none; }
</style>
</head>
<body>

<h1>🎰 Lottery Admin Dashboard</h1>
<p style="text-align:center;">
    <a href="create_round.php" class="btn">Create New Round</a>
    <a href="logout.php" class="btn">Logout</a>
</p>

<!-- ===============================
     SUMMARY CARDS
=================================-->
<div class="cards">
    <div class="card">
        <h3><?= $totalRounds ?></h3>
        <p>Total Rounds</p>
    </div>
    <div class="card">
        <h3><?= number_format($totalRevenue,2) ?> ETB</h3>
        <p>Total Revenue</p>
    </div>
    <div class="card">
        <h3><?= $totalWinners ?></h3>
        <p>Total Winners</p>
    </div>
    <div class="card">
        <h3><?= number_format($totalAdminProfit,2) ?> ETB</h3>
        <p>Admin Profit</p>
    </div>
</div>

<h2>🔍 Rounds History</h2>

<table id="roundsTable" class="display" style="width:100%; color:white;">
<thead>
<tr>
<th>Round Code</th>
<th>Entry Fee</th>
<th>Participants</th>
<th>Status</th>
<th>Total Amount</th>
<th>Admin Profit</th>
<th>Winners</th>
<th>Created At</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach($rounds as $r): ?>
<tr>
<td><?= htmlspecialchars($r['round_code']) ?></td>
<td><?= number_format((float)$r['entry_fee'],2) ?> ETB</td>
<td><?= (int)$r['current_participants'] ?> / <?= (int)$r['max_participants'] ?></td>
<td><?= ucfirst(htmlspecialchars($r['status'])) ?></td>
<td><?= number_format((float)$r['total_amount'],2) ?> ETB</td>
<td><?= number_format((float)$r['admin_profit'],2) ?> ETB</td>
<td><?= $r['winners'] ? htmlspecialchars($r['winners']) : '-' ?></td>
<td><?= htmlspecialchars($r['created_at']) ?></td>
<td>
<?php if($r['status'] === 'open'): ?>
    <a href="add_participant.php?round_id=<?= (int)$r['round_id'] ?>" class="btn">Add</a>
    <a href="spin_round.php?round_id=<?= (int)$r['round_id'] ?>" class="btn">Spin</a>
<?php else: ?>
    -
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<!-- ===============================
     DATATABLE SCRIPTS
=================================-->
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
    $('#roundsTable').DataTable({
        order: [[7, "desc"]],
        pageLength: 10,
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
    });
});
</script>
</body>
</html>