<?php
require_once "../includes/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

//If already logged in, go dashboard
if (!empty($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";
$email = "";

if (!isset($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    if (!hash_equals($_SESSION["csrf_token"], $token)) {
        $error = "Invalid session token. Please refresh and try again.";
    } else {
        $email = trim($_POST["email"] ?? "");
        $password = $_POST["password"] ?? "";

        if ($email === "" || $password === "") {
            $error = "Please enter your email and password.";
        } else {
            // users table columns: id, email, password, role, firstname, lastname
            $stmt = $conn->prepare("SELECT id, firstname, lastname, email, password, role FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user["password"])) {
                session_regenerate_id(true);
                $_SESSION["user_id"] = (int)$user["id"];
                $_SESSION["user_role"] = $user["role"];
                $_SESSION["user_name"] = trim($user["firstname"] . " " . $user["lastname"]);

                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dolphin CRM - Login</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="login-body">

    <div class="login-topbar">
        <div class="login-brand">Dolphin CRM</div>
    </div>

    <div class="login-wrap">
        <div class="login-card">
            <h1 class="login-title">Login</h1>

            <?php if ($error !== ""): ?>
                <div class="alert alert-error"><?php echo h($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="login-form">
                <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION["csrf_token"]); ?>">

                <div class="form-group">
                    <label for="email">Email address</label>
                    <input id="email" type="email" name="email" required value="<?php echo h($email); ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required>
                </div>

                <button class="btn btn-primary login-btn" type="submit">Login</button>
            </form>
        </div>
    </div>

</body>
</html>
