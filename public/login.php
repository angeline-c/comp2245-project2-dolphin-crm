<?php echo "This is login.php"; ?>

<?php
session_start();
require_once "../includes/db.php";
?>

<?php include "../includes/header.php"; ?>

<h1>Login</h1>

<form method="POST" action="login.php">
    <label>Email</label><br>
    <input type="email" name="email" required><br><br>

    <label>Password</label><br>
    <input type="password" name="password" required><br><br>

    <button type="submit">Login</button>
</form>

<?php include "../includes/footer.php"; ?>
