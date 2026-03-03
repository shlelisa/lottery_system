<?php
session_start();

include 'includes/layout.php'; 

if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit;
}
include '../config/db.php';
$admin_id = $_SESSION['admin_id'];

// Summary Data
$totalRounds = $pdo->query("SELECT COUNT(*) FROM rounds")->fetchColumn();
$totalRevenue = $pdo->query("SELECT SUM(total_amount) FROM rounds")->fetchColumn();
$totalWinners = $pdo->query("SELECT COUNT(*) FROM winners")->fetchColumn();
$totalAdminProfit = $pdo->query("SELECT SUM(admin_profit) FROM rounds")->fetchColumn();

// Fetch rounds with winners
$stmt = $pdo->query("
SELECT r.id as round_id, r.round_code, r.total_amount, r.admin_profit, r.created_at, 
GROUP_CONCAT(CONCAT(w.position,'-',p.name) ORDER BY w.position SEPARATOR ', ') AS winners
FROM rounds r
LEFT JOIN winners w ON r.id = w.round_id
LEFT JOIN participants p ON w.participant_id = p.id
GROUP BY r.id
ORDER BY r.created_at DESC
");
$rounds = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<style>
body { font-family: Arial; background: linear-gradient(135deg,#141e30,#243b55); color:white; padding:20px;}
h1,h2 { text-align:center; }
.cards { display:flex; justify-content:space-around; flex-wrap:wrap; margin:20px 0;}
.card { background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); padding:20px; border-radius:15px; width:200px; margin:10px; text-align:center; box-shadow:0 0 10px rgba(0,0,0,0.5);}
.card h3 { margin:10px 0; font-size:2em; color: gold;}
table.dataTable thead th { background-color: rgba(255,255,255,0.1); }
a { color: gold; text-decoration:none; margin-right:10px;}
button { padding:10px 20px; border:none; border-radius:10px; background: gold; font-weight:bold; cursor:pointer;}
</style>
</head>


<h1>🎰 Lottery Admin Dashboard</h1>
<p style="text-align:center;"><a href="create_round.php">Create New Round</a> | <a href="logout.php">Logout</a></p>

<div class="cards">
    <div class="card">
        <h3><?php echo $totalRounds; ?></h3>
        <p>Total Rounds</p>
    </div>
    <div class="card">
        <h3><?php echo $totalRevenue; ?> ETB</h3>
        <p>Total Revenue</p>
    </div>
    <div class="card">
        <h3><?php echo $totalWinners; ?></h3>
        <p>Total Winners</p>
    </div>
    <div class="card">
        <h3><?php echo $totalAdminProfit; ?> ETB</h3>
        <p>Admin Profit</p>
    </div>
</div>

<h2>🔍 Rounds History</h2>
<table id="roundsTable" class="display" style="width:100%; color:white;">
<thead>
<tr>
<th>Round Code</th>
<th>Total Amount</th>
<th>Admin Profit</th>
<th>Winners</th>
<th>Status</th>
<th>Created At</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach($rounds as $r){ ?>
<tr>
<td><?php echo $r['round_code']; ?></td>
<td><?php echo $r['total_amount']; ?> ETB</td>
<td><?php echo $r['admin_profit']; ?> ETB</td>
<td><?php echo $r['winners'] ? $r['winners'] : '-'; ?></td>
<td><?php echo ucfirst($r['status']); ?></td>
<td><?php echo $r['created_at']; ?></td>
<td>
<?php if($r['status']=='open'){ ?>
<a href="add_participant.php?round_id=<?php echo $r['round_id']; ?>">Add Participant</a> |
<a href="spin_round.php?round_id=<?php echo $r['round_id']; ?>">Spin</a>
<?php } else { echo "-"; } ?>
</td>
</tr>
<?php } ?>
</tbody>
</table>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    $('#roundsTable').DataTable({
        "order": [[5, "desc"]],
        "pageLength": 10,
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
    });
});
</script>
</div>
</div>

<script>
// Persist Theme
document.addEventListener("DOMContentLoaded", function(){
    let theme = localStorage.getItem("theme");
    if(theme){
        document.body.classList.add(theme);
        updateIcon(theme);
    } else {
        document.body.classList.add("dark");
    }
});

function toggleSidebar(){
    document.getElementById("sidebar").classList.toggle("collapsed");
}

function toggleTheme(){
    if(document.body.classList.contains("dark")){
        document.body.classList.replace("dark","light");
        localStorage.setItem("theme","light");
        updateIcon("light");
    } else {
        document.body.classList.replace("light","dark");
        localStorage.setItem("theme","dark");
        updateIcon("dark");
    }
}

function updateIcon(theme){
    let icon = document.getElementById("themeIcon");
    if(theme==="dark"){
        icon.classList.remove("fa-sun");
        icon.classList.add("fa-moon");
    } else {
        icon.classList.remove("fa-moon");
        icon.classList.add("fa-sun");
    }
}
</script>

</body>
</html>