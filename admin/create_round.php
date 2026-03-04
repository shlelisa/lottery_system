<?php
session_start();

if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit;
}

include '../config/db.php';
include 'includes/layout.php'; 

$message = '';

if(isset($_POST['create_round'])){

    $admin_id = $_SESSION['admin_id'];

    // Sanitize inputs
    $entry_fee = filter_input(INPUT_POST,'entry_fee',FILTER_VALIDATE_FLOAT);
    $max_participants = filter_input(INPUT_POST,'max_participants',FILTER_VALIDATE_INT);

    // RULE 1 & 3
    if(!$entry_fee || !$max_participants || $entry_fee <= 0 || $max_participants <= 0){
        $message = "❌ Invalid entry fee or participants.";
    } elseif($entry_fee > 100000){
        $message = "❌ Entry fee too high.";
    } elseif($max_participants > 10000){
        $message = "❌ Participants limit too high.";
    } else {

        try{
            $pdo->beginTransaction();

            // RULE 2: Only one open round
            $check = $pdo->query("SELECT id FROM rounds WHERE status='open' LIMIT 1");
            if($check->fetch()){
                throw new Exception("An open round already exists.");
            }

            // RULE 4: Unique round code
            do{
                $round_code = 'ROUND-' . strtoupper(bin2hex(random_bytes(4)));
                $stmt = $pdo->prepare("SELECT id FROM rounds WHERE round_code=?");
                $stmt->execute([$round_code]);
            }while($stmt->fetch());

            // RULE 6: Prevent identical active round
            $duplicate = $pdo->prepare("
                SELECT id FROM rounds 
                WHERE entry_fee=? AND max_participants=? AND status IN ('open','locked')
            ");
            $duplicate->execute([$entry_fee,$max_participants]);
            if($duplicate->fetch()){
                throw new Exception("Similar active round already exists.");
            }

            // Insert new round
            $stmt = $pdo->prepare("
                INSERT INTO rounds 
                (round_code, created_by, entry_fee, max_participants, 
                 current_participants, total_amount, admin_profit, status)
                VALUES (?, ?, ?, ?, 0, 0, 0, 'open')
            ");
            $stmt->execute([$round_code, $admin_id, $entry_fee, $max_participants]);

            $pdo->commit();
            $message = "✅ Round created successfully: <strong>$round_code</strong>";

        } catch(Exception $e){
            $pdo->rollBack();
            $message = "❌ ".$e->getMessage();
        }
    }
}

// Fetch all rounds
$stmt = $pdo->query("SELECT * FROM rounds ORDER BY created_at DESC");
$allRounds = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Lottery Round</title>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

<style>
body {
    font-family: 'Arial', sans-serif;
    background: linear-gradient(135deg,#141e30,#243b55);
    color: #fff;
    padding: 20px;
}

.container {
    max-width: 1200px;
    margin: auto;
    background: rgba(255,255,255,0.05);
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
}

h2 {
    text-align: center;
    color: gold;
    margin-bottom: 20px;
}

a {
    color: gold;
    text-decoration: none;
    font-weight: bold;
}

form {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 20px;
}

form label {
    flex: 1 1 100%;
    font-weight: bold;
}

form input {
    flex: 1 1 45%;
    padding: 10px;
    border-radius: 8px;
    border: none;
    outline: none;
    font-size: 16px;
}

form button {
    flex: 1 1 100%;
    padding: 12px;
    font-size: 18px;
    background: gold;
    color: #141e30;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: 0.3s;
}

form button:hover {
    background: #ffd700cc;
}

.message {
    background: rgba(255,255,255,0.1);
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    text-align: center;
}

.card {
    background: rgba(255,255,255,0.1);
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 15px;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

table.dataTable {
    width: 100% !important;
    color: white;
}

table.dataTable thead th {
    background-color: rgba(255,255,255,0.1);
}

table.dataTable tbody tr:hover {
    background-color: rgba(255,255,255,0.05);
}
</style>
</head>
<body>
<div class="container">

<h2>🆕 Create New Lottery Round</h2>
<a href="dashboard.php">← Back to Dashboard</a>

<?php if($message) echo "<div class='message'>$message</div>"; ?>

<form method="POST">
    <label>Entry Fee (ETB)</label>
    <input type="number" name="entry_fee" id="entry_fee" min="1" step="0.01" required>

    <label>Maximum Participants</label>
    <input type="number" name="max_participants" id="max_participants" min="2" required>

    <div class="card">
        💰 Potential Maximum Pot: <strong><span id="total_preview">0</span> ETB</strong>
    </div>

    <button type="submit" name="create_round">Create Round</button>
</form>

<hr style="border-color: rgba(255,255,255,0.2); margin: 30px 0;">

<h3>All Rounds</h3>
<table id="roundsTable" class="display" style="width:100%;">
<thead>
<tr>
    <th>Code</th>
    <th>Entry</th>
    <th>Participants</th>
    <th>Total</th>
    <th>Admin Profit</th>
    <th>Status</th>
    <th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach($allRounds as $row): ?>
<tr>
    <td><?= htmlspecialchars($row['round_code']) ?></td>
    <td><?= number_format($row['entry_fee'],2) ?> ETB</td>
    <td><?= $row['current_participants'] ?> / <?= $row['max_participants'] ?></td>
    <td><?= number_format($row['total_amount'],2) ?> ETB</td>
    <td><?= number_format($row['admin_profit'],2) ?> ETB</td>
    <td><?= strtoupper($row['status']) ?></td>
    <td>
        <?php if($row['status']=='open'): ?>
            <a href="add_participant.php?round_id=<?= $row['id'] ?>">Manage</a>
        <?php elseif($row['status']=='locked'): ?>
            <a href="spin_round.php?round_id=<?= $row['id'] ?>">Spin</a>
        <?php else: ?>
            Completed
        <?php endif; ?>
    </td>
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
    $('#roundsTable').DataTable({
        order: [[0, "desc"]],
        pageLength: 10,
        dom: 'Bfrtip',
        buttons: ['copy','csv','excel','pdf','print']
    });
});

// Live potential total calculation
document.querySelectorAll('#entry_fee,#max_participants').forEach(input=>{
    input.addEventListener('input',()=>{
        let fee = parseFloat(document.getElementById('entry_fee').value) || 0;
        let max = parseInt(document.getElementById('max_participants').value) || 0;
        document.getElementById('total_preview').innerText = (fee*max).toFixed(2);
    });
});
</script>
</body>
</html>