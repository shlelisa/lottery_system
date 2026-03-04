<?php
session_start();
if(!isset($_SESSION['admin_id'])) header("Location: login.php");

include '../config/db.php';
include 'includes/layout.php';

if(!isset($_GET['round_id'])) die("Round ID is required.");

$round_id = intval($_GET['round_id']);
$admin_id = $_SESSION['admin_id'];
$message = '';

// Fetch round info
$stmt = $pdo->prepare("SELECT * FROM rounds WHERE id=?");
$stmt->execute([$round_id]);
$round = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$round) die("Round not found.");

$locked = ($round['status'] !== 'open');

// Add participant logic remains the same...
if(isset($_POST['add_participant']) && !$locked){
    try{
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT * FROM rounds WHERE id=? FOR UPDATE");
        $stmt->execute([$round_id]);
        $round = $stmt->fetch(PDO::FETCH_ASSOC);

        if($round['current_participants'] >= $round['max_participants'])
            throw new Exception("❌ Maximum participants reached.");

        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        if(!$name) throw new Exception("Please enter a valid name.");

        $stmt = $pdo->prepare("SELECT id FROM participants WHERE round_id=? AND name=?");
        $stmt->execute([$round_id,$name]);
        if($stmt->fetch()) throw new Exception("❌ Participant with this name already exists.");

        $amount_paid = $round['entry_fee'];
        $stmt = $pdo->prepare("INSERT INTO participants (round_id,name,phone,amount_paid,payment_verified,added_by) VALUES (?,?,?,?,0,?)");
        $stmt->execute([$round_id,$name,$phone,$amount_paid,$admin_id]);

        $pdo->commit();
        $message = "✅ Participant '$name' added. Please verify payment.";
    } catch(Exception $e){
        $pdo->rollBack();
        $message = $e->getMessage();
    }
}

// Verify payment logic remains the same...
if(isset($_GET['verify']) && !$locked){
    try{
        $participant_id = intval($_GET['verify']);
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT * FROM participants WHERE id=? AND payment_verified=0 FOR UPDATE");
        $stmt->execute([$participant_id]);
        $participant = $stmt->fetch(PDO::FETCH_ASSOC);
        if(!$participant) throw new Exception("❌ Participant not found or already verified.");

        $stmt = $pdo->prepare("UPDATE participants SET payment_verified=1 WHERE id=?");
        $stmt->execute([$participant_id]);

        $stmt = $pdo->prepare("UPDATE rounds SET current_participants=current_participants+1, total_amount=total_amount+? WHERE id=?");
        $stmt->execute([$round['entry_fee'],$round_id]);

        $stmt = $pdo->prepare("SELECT current_participants,max_participants FROM rounds WHERE id=?");
        $stmt->execute([$round_id]);
        $updatedRound = $stmt->fetch(PDO::FETCH_ASSOC);

        if($updatedRound['current_participants'] >= $updatedRound['max_participants']){
            $pdo->prepare("UPDATE rounds SET status='locked' WHERE id=?")->execute([$round_id]);
        }

        $pdo->commit();
        $message = "✅ Payment verified successfully.";
    } catch(Exception $e){
        $pdo->rollBack();
        $message = $e->getMessage();
    }
}

// Fetch participants
$stmt = $pdo->prepare("SELECT * FROM participants WHERE round_id=? ORDER BY created_at DESC");
$stmt->execute([$round_id]);
$participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <h2>➕ Add Participant - <?= htmlspecialchars($round['round_code']) ?></h2>
    <a href="dashboard.php" class="back-link">← Back to Dashboard</a>

    <!-- Round Summary Card -->
    <div class="round-summary">
        <p>💰 Entry Fee: <strong><?= $round['entry_fee'] ?> ETB</strong></p>
        <p>👥 Participants: <strong><?= $round['current_participants'] ?> / <?= $round['max_participants'] ?></strong></p>
        <p>📌 Status: <strong><?= strtoupper($round['status']) ?></strong></p>
        <p>💵 Total Verified: <strong><?= number_format($round['total_amount'],2) ?> ETB</strong></p>
    </div>

    <?php if($message): ?>
    <div class="alert"><?= $message ?></div>
    <?php endif; ?>

    <!-- Add Participant Form -->
    <?php if(!$locked): ?>
    <form method="POST" class="form-add-participant">
        <input type="text" name="name" placeholder="Participant Name" required>
        <input type="text" name="phone" placeholder="Phone (optional)">
        <input type="number" value="<?= $round['entry_fee'] ?>" readonly>
        <button type="submit" name="add_participant">Add Participant</button>
    </form>
    <?php else: ?>
    <div class="alert alert-locked">🔒 This round is locked. No more participants allowed.</div>
    <?php endif; ?>

    <h3>Participants List</h3>
    <table id="participantsTable" class="display stripe hover" style="width:100%">
        <thead>
            <tr>
                <th>ID</th><th>Name</th><th>Phone</th><th>Amount</th>
                <th>Verified</th><th>Winner</th><th>Prize</th><th>Action</th><th>Added At</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($participants as $p): ?>
            <tr>
                <td><?= $p['id'] ?></td>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td><?= htmlspecialchars($p['phone']) ?></td>
                <td><?= $p['amount_paid'] ?> ETB</td>
                <td><?= $p['payment_verified']?'✅':'❌' ?></td>
                <td><?= $p['is_winner']?'🏆':'-' ?></td>
                <td><?= $p['prize_amount']?$p['prize_amount'].' ETB':'-' ?></td>
                <td>
                <?php if(!$p['payment_verified'] && !$locked): ?>
                    <a href="?round_id=<?= $round_id ?>&verify=<?= $p['id'] ?>" class="verify-btn">Verify</a>
                <?php else: ?>-<?php endif; ?>
                </td>
                <td><?= $p['created_at'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modern Styling -->
<style>
.container{padding:20px;}
.back-link{display:inline-block;margin:10px 0;color:gold;text-decoration:none;font-weight:bold;}
.round-summary{background:linear-gradient(135deg,#1f2937,#111827);padding:20px;border-radius:12px;margin:15px 0;box-shadow:0 5px 15px rgba(0,0,0,0.4);color:white;font-weight:bold;}
.alert{margin:10px 0;padding:12px;background:rgba(255,215,0,0.2);border-radius:8px;color:gold;font-weight:bold;}
.alert-locked{background:rgba(255,0,0,0.3);color:#fff;}
.form-add-participant{display:flex;gap:10px;flex-wrap:wrap;margin:15px 0;}
.form-add-participant input{flex:1;padding:12px;border-radius:8px;border:none;background:#222;color:white;}
.form-add-participant input[readonly]{background:gold;color:black;text-align:center;}
.form-add-participant button{padding:12px 24px;background:gold;border:none;border-radius:8px;font-weight:bold;cursor:pointer;transition:0.3s;}
.form-add-participant button:hover{background:#e6c200;}
table.display{width:100%;margin-top:15px;color:white;border-collapse:collapse;}
table.display th, table.display td{padding:12px;text-align:center;border-bottom:1px solid #333;}
.verify-btn{color:gold;font-weight:bold;text-decoration:none;}
.verify-btn:hover{text-decoration:underline;}
</style>

<!-- DataTables Advanced -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script>
$(document).ready(function(){
    $('#participantsTable').DataTable({
        responsive:true,
        order:[[0,'desc']],
        pageLength:10
    });
});
</script>