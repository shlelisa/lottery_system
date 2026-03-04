<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login</title>
<style>
body { 
    font-family: Arial; 
    background: linear-gradient(135deg,#141e30,#243b55); 
    display:flex; align-items:center; justify-content:center; height:100vh; color:white;
}
.login-box { background: rgba(255,255,255,0.05); padding: 40px; border-radius: 15px; width: 300px; text-align:center; }
input { width:100%; padding:10px; margin:10px 0; border-radius:5px; border:none; }
button { width:100%; padding:10px; border:none; border-radius:5px; background: gold; cursor:pointer; font-weight:bold; }
.error { color:red; }
</style>
</head>
<body>

<div class="login-box">
<h2>Admin Login</h2>
<form method="POST" action="">
<input type="text" name="username" placeholder="Username" required>
<input type="password" name="password" placeholder="Password" required>
<button type="submit" name="login">Login</button>
</form>
<?php
if(isset($_POST['login'])){
    include '../config/db.php';

    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if($admin && password_verify($password, $admin['password'])){
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        header("Location: dashboard.php");
        exit;
    } else {
        echo "<p class='error'>Invalid username or password</p>";
    }
}
?>
</div>

</body>
</html>