<?php
// Shared session helpers for the NEW stud_services project.
// The login pages in /auth store FLAT session keys (user_id, username, role_name, student_id),
// so current_user() rebuilds a user array from them. Every protected page starts with:
//   require_once __DIR__ . '/../includes/auth.php'; require_login();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function current_user(): ?array {
    // NOTE: admin-login.php does not set $_SESSION['role'], so we map from role_name,
    // which ALL four login pages set.
    $map = ['admin' => 'Admin', 'registrar' => 'Registrar', 'instructor' => 'Instructor', 'student' => 'Student'];
    $roleName = $_SESSION['role_name'] ?? '';
    if (empty($_SESSION['user_id']) || !isset($map[$roleName])) {
        return null;
    }
    return [
        'user_id'    => (int)$_SESSION['user_id'],
        'username'   => (string)($_SESSION['username'] ?? ''),
        'role'       => $map[$roleName],
        'student_id' => isset($_SESSION['student_id']) ? (int)$_SESSION['student_id'] : null,
    ];
}

// $loginPath is relative to the page that calls it (pages in /dashboard use the default).
function require_login(string $loginPath = '../auth/student-login.php'): void {
    if (!current_user()) {
        header('Location: ' . $loginPath);
        exit;
    }
}

// Server-side role check (hiding a button is not enough).
function require_role(array $roles): void {
    require_login();
    if (!in_array(current_user()['role'], $roles, true)) {
        http_response_code(403);
        exit('Access denied for your role.');
    }
}
