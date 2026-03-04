<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
/* Layout */
body {
    margin:0;
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg,#141e30,#243b55);
    color:white;
}

.wrapper {
    display:flex;
}

/* Sidebar */
.sidebar {
    width:250px;
    height:100vh;
    position:fixed;
    left:0;
    top:0;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(15px);
    padding:20px;
    box-shadow: 5px 0 20px rgba(0,0,0,0.4);
}

.sidebar h2 {
    text-align:center;
    margin-bottom:30px;
    color:gold;
}

.sidebar a {
    display:block;
    padding:12px 15px;
    margin:8px 0;
    color:white;
    text-decoration:none;
    border-radius:10px;
    transition:0.3s;
}

.sidebar a:hover {
    background:rgba(255,215,0,0.2);
    transform:translateX(5px);
}

.sidebar a.active {
    background:gold;
    color:black;
    font-weight:bold;
}

/* Content */
.content {
    margin-left:250px;
    padding:30px;
    width:100%;
}
</style>

<div class="wrapper">
    <div class="sidebar">
        <h2>🎰 Lottery</h2>

        <a href="dashboard.php" class="<?= $current_page=='dashboard.php'?'active':'' ?>">🏠 Dashboard</a>
        <a href="create_round.php" class="<?= $current_page=='create_round.php'?'active':'' ?>">➕ Create Round</a>
        <a href="history.php" class="<?= $current_page=='history.php'?'active':'' ?>">📜 History</a>
        <a href="logout.php">🚪 Logout</a>
    </div>

    <div class="content">