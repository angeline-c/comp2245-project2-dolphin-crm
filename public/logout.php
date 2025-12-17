<?php
session_start();
session_destroy();
header("Location: login.php");
exit();
?>

<?php
// public/logout.php
declare(strict_types=1);

session_start();

// Unset all session variables
$_SESSION = [];

// If sessions use cookies, delete the cookie too
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy the session
session_destroy();

header('Location: /login.php');
exit;
?>

