<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<?php
// includes/auth.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
}

function redirect_if_logged_in(string $to = '/dashboard.php'): void
{
    if (!empty($_SESSION['user_id'])) {
        header("Location: {$to}");
        exit;
    }
}
