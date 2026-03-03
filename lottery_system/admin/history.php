<?php
session_start();

include 'includes/layout.php'; 

if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit;
}
include '../config/db.php';

// Fetch all rounds with participants, winners, transactions
$stmt = $pdo->query("
SELECT 
    r.round_code,
    r.status,
    r.total_amount,
    r.admin_profit,
    r.created_at as round_created,
    GROUP_CONCAT(DISTINCT CONCAT(p.name,' (',p.amount_paid,' ETB)') ORDER BY p.id SEPARATOR ', ') AS participants,
    GROUP_CONCAT(DISTINCT CONCAT(w.position,'-',wp.name,' (',w.prize_amount,' ETB)') ORDER BY w.position SEPARATOR ', ') AS winners
FROM rounds r
LEFT JOIN participants p ON r.id=p.round_id
LEFT JOIN winners w ON r.id=w.round_id
LEFT JOIN participants wp ON w.participant_id=wp.id
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
<title>Lottery History</title>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
<style>
body { font-family: Arial; background: linear-gradient(135deg,#141e30,#243b55); color:white; padding:20px; }
.container { max-width:1200px; margin:auto; background: rgba(255,255,255,0.05); padding:20px; border-radius:15px; }
h1 { text-align:center; margin-bottom:20px;}
table.dataTable thead th { background-color: rgba(255,255,255,0.1); }
a { color: gold; text-decoration:none; }
</style>
</head>
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
<?php foreach($rounds as $r){ ?>
<tr>
<td><?php echo $r['round_code']; ?></td>
<td><?php echo ucfirst($r['status']); ?></td>
<td><?php echo $r['total_amount']; ?> ETB</td>
<td><?php echo $r['admin_profit']; ?> ETB</td>
<td><?php echo $r['participants'] ? $r['participants'] : '-'; ?></td>
<td><?php echo $r['winners'] ? $r['winners'] : '-'; ?></td>
<td><?php echo $r['round_created']; ?></td>
</tr>
<?php } ?>
</tbody>
</table>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.flash.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    $('#historyTable').DataTable({
        "order": [[6, "desc"]],
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