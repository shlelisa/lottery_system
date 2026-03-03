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

// Fetch round
$stmt = $pdo->prepare("SELECT * FROM rounds WHERE id = ?");
$stmt->execute([$round_id]);
$round = $stmt->fetch();
if(!$round){
    die("Round not found.");
}

// Fetch verified participants
$stmt = $pdo->prepare("SELECT * FROM participants WHERE round_id=? AND payment_verified=1");
$stmt->execute([$round_id]);
$participants = $stmt->fetchAll();

// Calculate total verified money
$total_amount = 0;
foreach($participants as $p) $total_amount += $p['amount_paid'];

function calculatePrizes($total){
    if($total <= 1000){
        return [
            ["position"=>1,"amount"=>$total*0.9],
            "admin"=>$total*0.1
        ];
    }elseif($total < 2000){
        return [
            ["position"=>1,"amount"=>1000],
            ["position"=>2,"amount"=>300],
            "admin"=>$total-1300
        ];
    }else{
        return [
            ["position"=>1,"amount"=>1000],
            ["position"=>2,"amount"=>700],
            "admin"=>$total-1700
        ];
    }
}

// Determine number of winners based on prize rules
$prizes = calculatePrizes($total_amount);
$winner_count = count(array_filter($prizes, fn($k) => isset($k['position'])));

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Spin Round <?php echo $round['round_code']; ?></title>
<style>
body { font-family: Arial; background: linear-gradient(135deg,#141e30,#243b55); color:white; text-align:center; padding:20px;}
canvas { margin:20px auto; display:block; background:#1c2a44; border-radius:50%; }
button { padding:10px 20px; background: gold; border:none; border-radius:10px; font-weight:bold; cursor:pointer;}
#winnerModal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); color:white; align-items:center; justify-content:center; flex-direction:column;}
#winnerModal div { background:rgba(255,255,255,0.05); padding:30px; border-radius:20px; text-align:center; }
</style>
</head>
<h2>🎡 Spin Round: <?php echo $round['round_code']; ?></h2>
<p>Total Verified Participants: <?php echo count($participants); ?> | Total Amount: <?php echo $total_amount; ?> ETB</p>

<canvas id="wheel" width="500" height="500"></canvas>
<button id="spinBtn">Spin Wheel</button>

<div id="winnerModal">
    <div>
        <h2 id="winnerText"></h2>
        <p id="winnerAmount"></p>
        <button onclick="closeModal()">Close</button>
    </div>
</div>

<script>
let participants = <?php echo json_encode($participants); ?>;
let prizes = <?php echo json_encode($prizes); ?>;

const canvas = document.getElementById("wheel");
const ctx = canvas.getContext("2d");
const radius = canvas.width/2;
let startAngle = 0;
let arc = (2*Math.PI)/participants.length;

function drawWheel(){
    ctx.clearRect(0,0,canvas.width,canvas.height);
    for(let i=0;i<participants.length;i++){
        let angle = startAngle + i*arc;
        ctx.beginPath();
        ctx.fillStyle = `hsl(${i*360/participants.length},70%,50%)`;
        ctx.moveTo(radius,radius);
        ctx.arc(radius,radius,radius,angle,angle+arc);
        ctx.fill();

        ctx.save();
        ctx.fillStyle="white";
        ctx.translate(radius+Math.cos(angle+arc/2)*radius*0.6, radius+Math.sin(angle+arc/2)*radius*0.6);
        ctx.rotate(angle+arc/2+Math.PI/2);
        ctx.fillText(participants[i].name,-ctx.measureText(participants[i].name).width/2,0);
        ctx.restore();
    }

    // pointer
    ctx.fillStyle="black";
    ctx.beginPath();
    ctx.moveTo(radius-10,5);
    ctx.lineTo(radius+10,5);
    ctx.lineTo(radius,30);
    ctx.fill();
}
drawWheel();

document.getElementById("spinBtn").onclick = function(){
    this.disabled = true;
    let spinAngle = Math.random()*3000+4000;
    let rotation = 0;

    let interval = setInterval(()=>{
        rotation+=20;
        startAngle += 20*Math.PI/180;
        drawWheel();
        if(rotation>=spinAngle){
            clearInterval(interval);
            finishSpin();
        }
    },20);
}

function finishSpin(){
    let degrees = startAngle*180/Math.PI + 90;
    let index = Math.floor((360 - degrees%360)/ (2*Math.PI/participants.length*180/Math.PI));
    let winner1 = participants[index];
    
    let winner2 = null;
    if(prizes.length>2){
        // second winner
        let temp = [...participants];
        temp.splice(index,1); // remove first winner
        let randomIndex = Math.floor(Math.random()*temp.length);
        winner2 = temp[randomIndex];
    }

    let html = "";
    html += `🏆 Winner 1: ${winner1.name} → ${prizes[0].amount} ETB<br>`;
    if(winner2){
        html += `🏆 Winner 2: ${winner2.name} → ${prizes[1].amount} ETB<br>`;
    }
    html += `💰 Admin Profit: ${prizes.admin} ETB`;
    document.getElementById("winnerText").innerHTML = "🎉 Winners!";
    document.getElementById("winnerAmount").innerHTML = html;
    document.getElementById("winnerModal").style.display="flex";

    // Send winners to backend
    fetch('save_winners.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({round_id:<?php echo $round_id; ?>, winners:[winner1<?php echo ($winner_count>1)?',winner2':''; ?>], prizes:prizes})
    });
}

function closeModal(){
    document.getElementById("winnerModal").style.display="none";
}
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