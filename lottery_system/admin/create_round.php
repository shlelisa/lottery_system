<?php
session_start();

include 'includes/layout.php'; 

if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit;
}
include '../config/db.php';

$message = '';

if(isset($_POST['create_round'])){
    $round_code = 'ROUND-' . strtoupper(uniqid());
    $admin_id = $_SESSION['admin_id'];

    // Insert new round
    $stmt = $pdo->prepare("INSERT INTO rounds (round_code, created_by, status) VALUES (?, ?, 'open')");
    $stmt->execute([$round_code, $admin_id]);

    $message = "✅ New round created successfully: <strong>$round_code</strong>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Round</title>
<style>
body { font-family: Arial; background: linear-gradient(135deg,#141e30,#243b55); color:white; padding:20px; }
.container { max-width:600px; margin:auto; background: rgba(255,255,255,0.05); padding:20px; border-radius:15px; }
input, button { padding:10px; width:100%; margin:10px 0; border:none; border-radius:5px; }
button { background: gold; cursor:pointer; font-weight:bold; }
.message { color: lightgreen; margin-top:10px; }
a { color: gold; text-decoration:none; display:inline-block; margin-bottom:10px; }
</style>
</head>
<div class="container">
<h2>🆕 Create New Lottery Round</h2>
<a href="dashboard.php">← Back to Dashboard</a>

<form method="POST">
    <p>Click the button to create a new round.</p>
    <button type="submit" name="create_round">Create Round</button>
</form>

<?php if($message) echo "<div class='message'>$message</div>"; ?>

<hr>
<h3>Open Rounds</h3>
<table border="1" cellpadding="10" style="width:100%; text-align:left; color:white;">
<tr>
<th>Round Code</th>
<th>Total Amount</th>
<th>Status</th>
<th>Action</th>
</tr>
<?php
$stmt = $pdo->query("SELECT * FROM rounds ORDER BY created_at DESC");
while($row = $stmt->fetch()){
    echo "<tr>";
    echo "<td>{$row['round_code']}</td>";
    echo "<td>{$row['total_amount']} ETB</td>";
    echo "<td>{$row['status']}</td>";
    if($row['status']=='open'){
        echo "<td><a href='add_participant.php?round_id={$row['id']}'>Add Participant</a></td>";
    } else {
        echo "<td>Locked</td>";
    }
    echo "</tr>";
}
?>
</table>
</div>
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