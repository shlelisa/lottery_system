<?php
session_start();
if(!isset($_SESSION['admin_id'])) exit;
include '../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$round_id = intval($data['round_id']);
$winners = $data['winners'];
$prizes = $data['prizes'];

// Fetch participants to match by name
$names = array_map(fn($w)=>$w['name'],$winners);
$placeholders = implode(',', array_fill(0, count($names), '?'));
$stmt = $pdo->prepare("SELECT * FROM participants WHERE round_id=? AND name IN ($placeholders)");
$stmt->execute(array_merge([$round_id], $names));
$participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Insert winners and transactions
foreach($participants as $i => $p){
    if(isset($prizes[$i]['amount'])){
        // Insert winner
        $stmt = $pdo->prepare("INSERT INTO winners (round_id, participant_id, position, prize_amount) VALUES (?,?,?,?)");
        $stmt->execute([$round_id, $p['id'], $prizes[$i]['position'], $prizes[$i]['amount']]);
        // Insert transaction
        $stmt = $pdo->prepare("INSERT INTO transactions (round_id, participant_id, type, amount) VALUES (?,?, 'prize', ?)");
        $stmt->execute([$round_id, $p['id'], $prizes[$i]['amount']]);
    }
}

// Admin profit transaction
if(isset($prizes['admin'])){
    $stmt = $pdo->prepare("INSERT INTO transactions (round_id, type, amount) VALUES (?,?,?)");
    $stmt->execute([$round_id,'admin_profit',$prizes['admin']]);
}

// Close round
$stmt = $pdo->prepare("UPDATE rounds SET status='completed', total_amount=(SELECT SUM(amount_paid) FROM participants WHERE round_id=?), admin_profit=? WHERE id=?");
$stmt->execute([$round_id,$prizes['admin'],$round_id]);

echo json_encode(['success'=>true]);