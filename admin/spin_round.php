<?php
session_start();
include 'includes/layout.php'; 
if(!isset($_SESSION['admin_id'])) header("Location: login.php");

include '../config/db.php';
if(!isset($_GET['round_id'])) die("Round ID is required.");

$round_id = intval($_GET['round_id']);

// Fetch round
$stmt = $pdo->prepare("SELECT * FROM rounds WHERE id=?");
$stmt->execute([$round_id]);
$round = $stmt->fetch();
if(!$round) die("Round not found.");

// RULE: Only locked rounds
if($round['status'] !== 'locked') die("❌ Round not locked.");
if($round['status'] === 'completed') die("❌ Round already completed.");

// Fetch VERIFIED participants
$stmt = $pdo->prepare("SELECT * FROM participants WHERE round_id=? AND payment_verified=1");
$stmt->execute([$round_id]);
$participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
if(count($participants) < 2) die("❌ Not enough verified participants.");

// FINAL BUSINESS RULE
$total_amount = $round['total_amount'];

function calculatePrizes($total){
    if($total <= 1000){
        return [
            ['position'=>1,'amount'=>$total * 0.85],
            'admin'=>$total * 0.15
        ];
    }

    return [
        ['position'=>1,'amount'=>$total * 0.50],
        ['position'=>2,'amount'=>$total * 0.35],
        'admin'=>$total * 0.15
    ];
}

$prizes = calculatePrizes($total_amount);
$twoWinners = ($total_amount > 1000);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Spin Round <?= $round['round_code'] ?></title>

<style>
body{
    font-family:Arial, sans-serif;
    background:linear-gradient(135deg,#141e30,#243b55);
    color:white;
    text-align:center;
    padding:20px;
}

canvas{
    margin:20px auto;
    display:block;
    background:#1c2a44;
    border-radius:50%;
    box-shadow:0 0 25px rgba(255,215,0,0.4);
}

button{
    padding:12px 30px;
    background:gold;
    border:none;
    border-radius:12px;
    font-weight:bold;
    cursor:pointer;
    font-size:16px;
    transition:0.3s;
}
button:hover{ background:#e6c200; }

#winnerModal{
    display:none;
    position:fixed;
    top:0; left:0;
    width:100%; height:100%;
    background:rgba(0,0,0,0.85);
    align-items:center;
    justify-content:center;
}

#winnerModal .box{
    background:#1c2a44;
    padding:40px;
    border-radius:20px;
    box-shadow:0 0 25px gold;
}
</style>
</head>
<body>

<h2>🎡 Spin Round: <?= $round['round_code'] ?></h2>
<p>
Participants: <?= count($participants) ?> <br>
Total Amount: <?= number_format($total_amount,2) ?> ETB
</p>

<canvas id="wheel" width="500" height="500"></canvas>
<button id="spinBtn">Spin Wheel</button>

<div id="winnerModal">
    <div class="box">
        <h2>🎉 Winners</h2>
        <div id="winnerResult"></div>
        <br>
        <button onclick="closeModal()">Close</button>
    </div>
</div>

<script>
const canvas = document.getElementById("wheel");
const ctx = canvas.getContext("2d");
const radius = canvas.width/2;

let participants = <?= json_encode($participants) ?>;
let prizes = <?= json_encode($prizes) ?>;
let twoWinners = <?= $twoWinners ? 'true' : 'false' ?>;

let remaining = [...participants];
let winners = [];
let spinStep = 0;
let totalSpinsRequired = twoWinners ? 2 : 1;

function drawWheel(rotation=0){
    ctx.clearRect(0,0,canvas.width,canvas.height);

    let arc = 2*Math.PI/remaining.length;

    for(let i=0;i<remaining.length;i++){
        let angle = rotation + i*arc;

        ctx.beginPath();
        ctx.fillStyle = `hsl(${i*360/remaining.length},70%,50%)`;
        ctx.moveTo(radius,radius);
        ctx.arc(radius,radius,radius,angle,angle+arc);
        ctx.fill();

        ctx.save();
        ctx.fillStyle="white";
        ctx.translate(
            radius + Math.cos(angle+arc/2)*radius*0.65,
            radius + Math.sin(angle+arc/2)*radius*0.65
        );
        ctx.rotate(angle+arc/2+Math.PI/2);
        ctx.fillText(
            remaining[i].name,
            -ctx.measureText(remaining[i].name).width/2,
            0
        );
        ctx.restore();
    }

    // Arrow
    ctx.fillStyle="black";
    ctx.beginPath();
    ctx.moveTo(radius-10,5);
    ctx.lineTo(radius+10,5);
    ctx.lineTo(radius,30);
    ctx.fill();
}

drawWheel();

function secureRandom(max){
    let array = new Uint32Array(1);
    window.crypto.getRandomValues(array);
    return array[0] % max;
}

document.getElementById("spinBtn").onclick = function(){

    if(spinStep >= totalSpinsRequired) return;

    this.disabled = true;

    let winnerIndex = secureRandom(remaining.length);
    let winner = remaining[winnerIndex];

    let arc = 2*Math.PI/remaining.length;
    let finalAngle = (Math.PI*3/2) - (winnerIndex * arc) - (arc/2);
    let currentAngle = 0;
    let frames = 0;

    let spinAnim = setInterval(()=>{
        frames++;
        currentAngle += 0.35;
        drawWheel(currentAngle);

        if(frames > 80){
            clearInterval(spinAnim);

            drawWheel(finalAngle);

            winners.push(winner);
            remaining.splice(winnerIndex,1);

            spinStep++;

            if(spinStep < totalSpinsRequired){
                this.disabled = false;
                alert("🥇 First winner selected. Click again for second winner.");
                drawWheel(); // redraw with remaining participants
            } else {
                finishRound();
            }
        }

    },20);
};

function finishRound(){

    let html = "";

    if(twoWinners){
        html += `🥇 1st: ${winners[0].name} → ${prizes[0].amount.toFixed(2)} ETB<br><br>`;
        html += `🥈 2nd: ${winners[1].name} → ${prizes[1].amount.toFixed(2)} ETB<br><br>`;
    } else {
        html += `🏆 Winner: ${winners[0].name} → ${prizes[0].amount.toFixed(2)} ETB<br><br>`;
    }

    html += `💰 Admin: ${prizes.admin.toFixed(2)} ETB`;

    document.getElementById("winnerResult").innerHTML = html;
    document.getElementById("winnerModal").style.display="flex";

    fetch('save_winners.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({
            round_id: <?= $round_id ?>,
            winners: winners,
            prizes: prizes
        })
    }).then(()=> fetch('complete_round.php?round_id=<?= $round_id ?>'));
}

function closeModal(){
    document.getElementById("winnerModal").style.display="none";
    window.location.href="dashboard.php";
}
</script>

</body>
</html>