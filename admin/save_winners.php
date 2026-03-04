<?php
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['admin_id'])){
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit;
}

include '../config/db.php';

// Read JSON safely
$data = json_decode(file_get_contents('php://input'), true);
if(!$data || !isset($data['round_id']) || !isset($data['winners'])){
    echo json_encode(['success'=>false,'message'=>'Invalid request']);
    exit;
}

$round_id = intval($data['round_id']);
$winner_ids = array_map(fn($w)=>intval($w['id']), $data['winners']);

// Fetch round
$stmt = $pdo->prepare("SELECT * FROM rounds WHERE id=? FOR UPDATE");
$stmt->execute([$round_id]);
$round = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$round){
    echo json_encode(['success'=>false,'message'=>'Round not found']);
    exit;
}

// RULE: Must be locked before spinning
if($round['status'] !== 'locked'){
    echo json_encode(['success'=>false,'message'=>'Round is not locked']);
    exit;
}

// RULE: Prevent double execution
if($round['status'] === 'completed'){
    echo json_encode(['success'=>false,'message'=>'Round already completed']);
    exit;
}

// Fetch verified participants only
$placeholders = implode(',', array_fill(0,count($winner_ids),'?'));
$stmt = $pdo->prepare("
    SELECT * FROM participants 
    WHERE id IN ($placeholders)
    AND round_id=?
    AND payment_verified=1
");
$stmt->execute(array_merge($winner_ids,[$round_id]));
$verifiedWinners = $stmt->fetchAll(PDO::FETCH_ASSOC);

if(count($verifiedWinners) !== count($winner_ids)){
    echo json_encode(['success'=>false,'message'=>'Invalid winners']);
    exit;
}

// Secure total amount from DB
$total_amount = $round['total_amount'];

// Recalculate prizes server-side (never trust frontend)
function calculatePrizes($total, $winner_count){
    $admin_percentage = 0.1;
    $admin_profit = round($total * $admin_percentage,2);
    $distribute = $total - $admin_profit;

    $prizes = [];
    if($winner_count === 1){
        $prizes[] = ["position"=>1, "amount"=>$distribute];
    } elseif($winner_count === 2){
        $prizes[] = ["position"=>1, "amount"=>round($distribute*0.7,2)];
        $prizes[] = ["position"=>2, "amount"=>round($distribute*0.3,2)];
    } else {
        // For more than 2 winners, split evenly
        $each = round($distribute / $winner_count,2);
        for($i=0;$i<$winner_count;$i++){
            $prizes[] = ["position"=>$i+1,"amount"=>$each];
        }
    }

    return ['prizes'=>$prizes, 'admin'=>$admin_profit];
}

$calc = calculatePrizes($total_amount, count($verifiedWinners));
$prizes = $calc['prizes'];
$admin_profit = $calc['admin'];

// Start transaction
$pdo->beginTransaction();

try{
    // Reset previous winners for this round
    $pdo->prepare("UPDATE participants SET is_winner=0, winner_position=NULL, prize_amount=NULL WHERE round_id=?")->execute([$round_id]);

    // Assign winners
    foreach($verifiedWinners as $index=>$p){
        $stmt = $pdo->prepare("
            UPDATE participants
            SET is_winner=1, winner_position=?, prize_amount=?
            WHERE id=?
        ");
        $stmt->execute([
            $prizes[$index]['position'],
            $prizes[$index]['amount'],
            $p['id']
        ]);

        // Record prize transaction
        $stmt = $pdo->prepare("
            INSERT INTO transactions (round_id, participant_id, type, amount)
            VALUES (?,?, 'prize', ?)
        ");
        $stmt->execute([$round_id, $p['id'], $prizes[$index]['amount']]);
    }

    // Record admin profit
    $stmt = $pdo->prepare("
        INSERT INTO transactions (round_id, type, amount)
        VALUES (?,?,?)
    ");
    $stmt->execute([$round_id, 'admin_profit', $admin_profit]);

    // Update round status
    $stmt = $pdo->prepare("
        UPDATE rounds SET status='completed', admin_profit=? WHERE id=?
    ");
    $stmt->execute([$admin_profit, $round_id]);

    $pdo->commit();
    echo json_encode(['success'=>true]);

}catch(Exception $e){
    $pdo->rollBack();
    echo json_encode(['success'=>false,'message'=>'Transaction failed: '.$e->getMessage()]);
}