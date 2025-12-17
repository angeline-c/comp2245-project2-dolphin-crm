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

<?php
// public/login.php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../includes/auth.php';
// Adjust this include to your actual DB file:
require_once __DIR__ . '/../includes/db.php';

redirect_if_logged_in('/dashboard.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } else {
        // Look up user by email
        $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify password
        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];

            header('Location: /dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Login</title>
</head>
<body>
  <h1>Login</h1>

  <?php if ($error !== ''): ?>
    <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
  <?php endif; ?>

  <form method="post" action="/login.php" autocomplete="on">
    <label>
      Email
      <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
    </label>
    <br>

    <label>
      Password
      <input type="password" name="password" required>
    </label>
    <br>

    <button type="submit">Login</button>
  </form>
</body>
</html>
