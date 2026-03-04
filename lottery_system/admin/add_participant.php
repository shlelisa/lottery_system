<?php

session_start();

include 'includes/layout.php'; 

if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit;
}
include '../config/db.php';

if(!isset($_GET['round_id'])){
    die("Round ID is required.");
}

$round_id = intval($_GET['round_id']);
$admin_id = $_SESSION['admin_id'];
$message = '';

// Handle participant addition
if(isset($_POST['add_participant'])){
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $amount_paid = floatval($_POST['amount_paid']);
    
    if($name && $amount_paid > 0){
        $stmt = $pdo->prepare("INSERT INTO participants 
            (round_id, name, phone, amount_paid, payment_verified, added_by) 
            VALUES (?, ?, ?, ?, 0, ?)");
        $stmt->execute([$round_id, $name, $phone, $amount_paid, $admin_id]);
        $message = "Participant '$name' added successfully! ✅";
    } else {
        $message = "Please enter valid name and amount.";
    }
}

// Handle payment verification
if(isset($_GET['verify'])){
    $participant_id = intval($_GET['verify']);
    $stmt = $pdo->prepare("UPDATE participants SET payment_verified = 1 WHERE id = ?");
    $stmt->execute([$participant_id]);
    $message = "Payment verified successfully ✅";
}

// Fetch round info
$stmt = $pdo->prepare("SELECT * FROM rounds WHERE id = ?");
$stmt->execute([$round_id]);
$round = $stmt->fetch();

// Fetch participants
$stmt = $pdo->prepare("SELECT * FROM participants WHERE round_id = ? ORDER BY created_at DESC");
$stmt->execute([$round_id]);
$participants = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Participant - <?php echo $round['round_code']; ?></title>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<style>
body { font-family: Arial; background: linear-gradient(135deg,#141e30,#243b55); color:white; padding:20px; }
.container { max-width:900px; margin:auto; background: rgba(255,255,255,0.05); padding:20px; border-radius:15px; }
input, button { padding:10px; margin:5px 0; border-radius:5px; border:none; }
button { cursor:pointer; background: gold; font-weight:bold; }
table.dataTable thead th { background-color: rgba(255,255,255,0.1); }
a { color: #2ecc71; text-decoration:none; }
.message { color: lightgreen; margin:10px 0; }
</style>
</head>

<div class="container">
<h2>➕ Add Participant - <?php echo $round['round_code']; ?></h2>
<a href="dashboard.php">← Back to Dashboard</a>

<?php if($message) echo "<div class='message'>$message</div>"; ?>

<form method="POST">
    <input type="text" name="name" placeholder="Participant Name" required>
    <input type="text" name="phone" placeholder="Phone (optional)">
    <input type="number" name="amount_paid" placeholder="Amount Paid (ETB)" required>
    <button type="submit" name="add_participant">Add Participant</button>
</form>

<h3>Participants List</h3>
<table id="participantsTable" class="display" style="width:100%; color:white;">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Phone</th>
            <th>Amount Paid</th>
            <th>Verified</th>
            <th>Action</th>
            <th>Added At</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($participants as $p){ ?>
        <tr>
            <td><?php echo $p['id']; ?></td>
            <td><?php echo htmlspecialchars($p['name']); ?></td>
            <td><?php echo htmlspecialchars($p['phone']); ?></td>
            <td><?php echo $p['amount_paid']; ?> ETB</td>
            <td><?php echo $p['payment_verified'] ? '✅' : '❌'; ?></td>
            <td>
                <?php if(!$p['payment_verified']){ ?>
                <a href="?round_id=<?php echo $round_id; ?>&verify=<?php echo $p['id']; ?>">Verify Payment</a>
                <?php } else { echo '-'; } ?>
            </td>
            <td><?php echo $p['created_at']; ?></td>
        </tr>
        <?php } ?>
    </tbody>
</table>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    $('#participantsTable').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 10
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