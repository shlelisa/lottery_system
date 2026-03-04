<?php
session_start();
include 'includes/layout.php'; 
if(!isset($_SESSION['admin_id'])) header("Location: login.php");

include '../config/db.php';
if(!isset($_GET['round_id'])) die("Round ID is required.");

$round_id = intval($_GET['round_id']);
$admin_id = $_SESSION['admin_id'];

// Fetch round
$stmt = $pdo->prepare("SELECT * FROM rounds WHERE id=?");
$stmt->execute([$round_id]);
$round = $stmt->fetch();
if(!$round) die("Round not found.");

// RULE: Only locked rounds
if($round['status']!=='locked') die("❌ Round not locked.");
if($round['status']==='completed') die("❌ Round already completed.");

// Fetch VERIFIED participants
$stmt = $pdo->prepare("SELECT * FROM participants WHERE round_id=? AND payment_verified=1");
$stmt->execute([$round_id]);
$participants = $stmt->fetchAll();
if(count($participants)<2) die("❌ Not enough verified participants.");

// Secure prize calculation
$total_amount = $round['total_amount'];
function calculatePrizes($total){
    $admin = $total*0.1;
    $distributable = $total-$admin;
    if($total<2000){
        return [['position'=>1,'amount'=>$distributable],'admin'=>$admin];
    }
    return [['position'=>1,'amount'=>$distributable*0.7],['position'=>2,'amount'=>$distributable*0.3],'admin'=>$admin];
}
$prizes = calculatePrizes($total_amount);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Spin Round <?= $round['round_code'] ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body{font-family:Arial,sans-serif;background:linear-gradient(135deg,#141e30,#243b55);color:white;text-align:center;padding:20px;}
canvas{margin:20px auto; display:block; background:#1c2a44; border-radius:50%;}
button{padding:12px 25px;background:gold;border:none;border-radius:12px;font-weight:bold;cursor:pointer;transition:0.3s;}
button:hover{background:#e6c200;}
#winnerModal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.85);color:white;align-items:center;justify-content:center;flex-direction:column;}
#winnerModal div{background:rgba(255,255,255,0.05);padding:40px;border-radius:20px;text-align:center;box-shadow:0 0 20px rgba(255,215,0,0.5);}
#winnerModal button{margin-top:20px;background:gold;color:black;}
</style>
</head>
<body>
<h2>🎡 Spin Round: <?= $round['round_code'] ?></h2>
<p>Verified Participants: <?= count($participants) ?><br>Total Amount: <?= number_format($total_amount,2) ?> ETB</p>
<canvas id="wheel" width="500" height="500"></canvas>
<button id="spinBtn">Spin Wheel</button>

<div id="winnerModal">
    <div>
        <h2>🎉 Winners!</h2>
        <p id="winnerAmount"></p>
        <button onclick="closeModal()">Close</button>
    </div>
</div>

<script>
const canvas = document.getElementById("wheel");
const ctx = canvas.getContext("2d");
const radius = canvas.width/2;
let participants = <?= json_encode($participants) ?>;
let prizes = <?= json_encode($prizes) ?>;
let startAngle = 0;
let arc = 2*Math.PI/participants.length;

// Draw wheel
function drawWheel(highlight=-1){
    ctx.clearRect(0,0,canvas.width,canvas.height);
    for(let i=0;i<participants.length;i++){
        let angle = startAngle + i*arc;
        ctx.beginPath();
        ctx.fillStyle = i===highlight?'gold':`hsl(${i*360/participants.length},70%,50%)`;
        ctx.moveTo(radius,radius);
        ctx.arc(radius,radius,radius,angle,angle+arc);
        ctx.fill();

        ctx.save();
        ctx.fillStyle="white";
        ctx.translate(radius+Math.cos(angle+arc/2)*radius*0.6,
                      radius+Math.sin(angle+arc/2)*radius*0.6);
        ctx.rotate(angle+arc/2+Math.PI/2);
        ctx.fillText(participants[i].name,-ctx.measureText(participants[i].name).width/2,0);
        ctx.restore();
    }
    // Arrow
    ctx.fillStyle="black";
    ctx.beginPath();
    ctx.moveTo(radius-10,5); ctx.lineTo(radius+10,5); ctx.lineTo(radius,30); ctx.fill();
}
drawWheel();

// Secure random selection
function secureRandom(max){
    let array = new Uint32Array(1);
    window.crypto.getRandomValues(array);
    return array[0]%max;
}

// Spin wheel
document.getElementById("spinBtn").onclick = async function(){
    this.disabled=true;
    let winnerIndex = secureRandom(participants.length);
    let winner1 = participants[winnerIndex];

    let winner2 = null;
    if(prizes.length>2){
        let remaining = participants.filter((_,i)=>i!==winnerIndex);
        let winner2Index = secureRandom(remaining.length);
        winner2 = remaining[winner2Index];
    }

    // Animate spin
    let spins=60;
    let current=0;
    let highlight=-1;
    let spinInterval = setInterval(()=>{
        highlight=(highlight+1)%participants.length;
        drawWheel(highlight);
        current++;
        if(current>=spins){ clearInterval(spinInterval); showWinner(); }
    },50);

    function showWinner(){
        drawWheel(winnerIndex);
        let resultHTML = `🏆 1st: ${winner1.name} → ${prizes[0].amount.toFixed(2)} ETB<br>`;
        if(winner2) resultHTML += `🏆 2nd: ${winner2.name} → ${prizes[1].amount.toFixed(2)} ETB<br>`;
        resultHTML += `💰 Admin Profit: ${prizes.admin.toFixed(2)} ETB`;
        document.getElementById("winnerAmount").innerHTML = resultHTML;
        document.getElementById("winnerModal").style.display="flex";

        // Save winners & complete round
        fetch('save_winners.php',{
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify({
                round_id: <?= $round_id ?>,
                winners: winner2?[winner1,winner2]:[winner1],
                prizes: prizes
            })
        }).then(()=> fetch('complete_round.php?round_id=<?= $round_id ?>'));
    }
};

function closeModal(){
    document.getElementById("winnerModal").style.display="none";
    window.location.href="dashboard.php";
}
</script>
</body>
</html>