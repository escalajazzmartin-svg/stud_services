<?php
session_start();

// Get the user's role before clearing the session.
$role = $_SESSION['role'] ?? '';

// Determine the login page based on role.
switch (strtolower($role)) {
    case 'student':
        $loginPage = 'student-login.php';
        break;

    case 'admin':
        $loginPage = 'admin-login.php';
        break;

    case 'registrar':
        $loginPage = 'registrar-login.php';
        break;

    case 'instructor':
        $loginPage = 'instructor-login.php';
        break;

    default:
        $loginPage = 'admin-login.php';
        break;
}

// Clear the session.
$_SESSION = [];

// Remove the session cookie.
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $p['path'],
        $p['domain'],
        $p['secure'],
        $p['httponly']
    );
}

// Destroy the session.
session_destroy();

// Redirect to the appropriate login page.
header('Location: ' . $loginPage);
exit;
