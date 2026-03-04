<?php
if(session_status() === PHP_SESSION_NONE){
    session_start();
}
include '../config/db.php';

$current_page = basename($_SERVER['PHP_SELF']);
$openRounds = $pdo->query("SELECT COUNT(*) FROM rounds WHERE status='open'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lottery Admin</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
:root {
    --sidebar-width: 270px;
    --primary: #FFD700;
    --dark-bg: #0f172a;
    --dark-card: #1e293b;
    --dark-text: #fff;
    --light-bg: #f1f5f9;
    --light-card: #fff;
    --light-text: #111;
    --transition: 0.3s ease;
}

* {
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',sans-serif;
}

body {
    transition: all var(--transition);
}

/* ===== DARK MODE ===== */
body.dark {
    background: linear-gradient(135deg,#0f172a,#1e293b);
    color: var(--dark-text);
}

/* ===== LIGHT MODE ===== */
body.light {
    background: var(--light-bg);
    color: var(--light-text);
}

/* ===== SIDEBAR ===== */
.sidebar {
    position: fixed;
    top:0;
    left:0;
    width: var(--sidebar-width);
    height: 100vh;
    background: linear-gradient(180deg,#111827,#1f2937);
    padding: 25px 15px;
    transition: width var(--transition), background var(--transition);
    box-shadow: 5px 0 30px rgba(0,0,0,0.4);
    z-index: 1000;
}

.sidebar.collapsed {
    width: 80px;
}

.logo {
    font-size: 28px;
    font-weight: bold;
    text-align: center;
    margin-bottom: 40px;
    background: linear-gradient(90deg,#FFD700,#ff9f43,#FFD700);
    background-size: 200%;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    animation: shine 4s linear infinite;
}

@keyframes shine {
    0% { background-position:0% }
    100% { background-position:200% }
}

.sidebar a {
    display: flex;
    align-items: center;
    padding: 14px 10px;
    margin: 10px 0;
    color: #fff;
    text-decoration: none;
    border-radius: 12px;
    transition: all var(--transition);
    font-weight: 500;
}

.sidebar a i {
    width: 25px;
    font-size: 18px;
}

.sidebar a span {
    transition: var(--transition);
}

.sidebar.collapsed a span {
    display: none;
}

.sidebar a:hover {
    background: rgba(255,215,0,0.15);
    transform: translateX(5px);
}

.sidebar a.active {
    background: var(--primary);
    color: #111;
    font-weight: bold;
}

.badge {
    background: red;
    padding: 3px 8px;
    border-radius: 20px;
    font-size: 12px;
    margin-left: auto;
}

/* ===== TOPBAR ===== */
.topbar {
    margin-left: var(--sidebar-width);
    height: 65px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 25px;
    backdrop-filter: blur(10px);
    background: rgba(0,0,0,0.3);
    transition: all var(--transition);
    z-index: 900;
    position: sticky;
    top:0;
}

.sidebar.collapsed ~ .topbar {
    margin-left: 80px;
}

.toggle-btn {
    font-size: 22px;
    cursor: pointer;
    transition: var(--transition);
}

.toggle-btn:hover {
    color: var(--primary);
}

.profile {
    display: flex;
    align-items: center;
    gap: 15px;
}

.profile i {
    cursor: pointer;
    font-size: 20px;
    transition: var(--transition);
}

.profile i:hover {
    color: var(--primary);
}

/* ===== CONTENT ===== */
.content {
    margin-left: var(--sidebar-width);
    padding: 30px;
    transition: all var(--transition);
    min-height: 100vh;
}

.sidebar.collapsed ~ .content {
    margin-left: 80px;
}

/* ===== MOBILE ===== */
@media(max-width:768px){
    .sidebar {
        left:-270px;
    }
    .sidebar.active {
        left:0;
    }
    .topbar {
        margin-left:0;
    }
    .content {
        margin-left:0;
    }
}
</style>
</head>
<body class="dark">

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <div class="logo">🎰 LotteryPro</div>

    <a href="dashboard.php" class="<?= $current_page=='dashboard.php'?'active':'' ?>">
        <i class="fa fa-home"></i>
        <span>Dashboard</span>
    </a>

    <a href="create_round.php" class="<?= $current_page=='create_round.php'?'active':'' ?>">
        <i class="fa fa-plus-circle"></i>
        <span>Create Round</span>
        <?php if($openRounds>0): ?>
            <span class="badge"><?= $openRounds ?></span>
        <?php endif; ?>
    </a>

    <a href="history.php" class="<?= $current_page=='history.php'?'active':'' ?>">
        <i class="fa fa-clock"></i>
        <span>History</span>
    </a>

    <a href="logout.php">
        <i class="fa fa-sign-out-alt"></i>
        <span>Logout</span>
    </a>
</div>

<!-- TOPBAR -->
<div class="topbar">
    <i class="fa fa-bars toggle-btn" onclick="toggleSidebar()"></i>

    <div class="profile">
        <i class="fa fa-user-circle"></i>
        <span>Admin</span>
        <i class="fa fa-moon" id="themeIcon" onclick="toggleTheme()"></i>
    </div>
</div>

<!-- CONTENT START -->
<div class="content">

<script>
// ===== SIDEBAR TOGGLE =====
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('collapsed');
}

// ===== DARK / LIGHT MODE =====
function toggleTheme() {
    document.body.classList.toggle('dark');
    document.body.classList.toggle('light');
    const themeIcon = document.getElementById('themeIcon');
    if(document.body.classList.contains('dark')){
        themeIcon.classList.replace('fa-sun','fa-moon');
        localStorage.setItem('theme','dark');
    } else {
        themeIcon.classList.replace('fa-moon','fa-sun');
        localStorage.setItem('theme','light');
    }
}

// Persist theme across pages
document.addEventListener('DOMContentLoaded',()=>{
    const theme = localStorage.getItem('theme');
    if(theme){
        document.body.classList.remove('dark','light');
        document.body.classList.add(theme);
        document.getElementById('themeIcon').classList.toggle('fa-moon',theme==='dark');
        document.getElementById('themeIcon').classList.toggle('fa-sun',theme==='light');
    }
});
</script>