<?php
// auth/student-login.php
session_start();

require_once __DIR__ . '/../config/database.php';

// Ensure the database connection is available.
if (!isset($pdo) || !$pdo instanceof PDO) {
    http_response_code(500);
    exit('Database connection is unavailable.');
}

$error = '';
$username = '';

// Redirect authenticated students to their registration page.
$isStudent = strtolower((string) (
    $_SESSION['role_name'] ?? $_SESSION['role'] ?? ''
)) === 'student';

if (
    isset($_SESSION['user_id']) &&
    $isStudent
) {
    header('Location: ../registration/student-registration.php');
    exit;
}

// Generate CSRF token.
if (empty($_SESSION['student_login_csrf'])) {
    $_SESSION['student_login_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($csrfToken) ||
        !hash_equals($_SESSION['student_login_csrf'], $csrfToken)
    ) {
        $error = 'Invalid request. Please refresh and try again.';
    } elseif ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {
        try {
            // Authenticate student accounts only.
            $sql = "
                SELECT
                    u.user_id,
                    u.username,
                    u.password_hash,
                    u.role,
                    u.student_id,
                    s.email
                FROM users AS u
                INNER JOIN students AS s
                    ON s.student_id = u.student_id
                WHERE u.username = :username
                  AND LOWER(u.role) = 'student'
                LIMIT 1
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'username' => $username
            ]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (
                !$user ||
                empty($user['password_hash']) ||
                !password_verify($password, $user['password_hash'])
            ) {
                $error = 'Invalid student username or password.';
            } else {
                // Prevent session fixation.
                session_regenerate_id(true);

                // Keep session keys consistent with the application.
                $_SESSION['user_id'] = (int) $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['role_name'] = 'student';
                $_SESSION['student_id'] = (int) $user['student_id'];
                $_SESSION['email'] = $user['email'] ?? '';

                unset($_SESSION['student_login_csrf']);

                header('Location: ../registration/student-registration.php');
                exit;
            }
        } catch (PDOException $e) {
            error_log('Student login failed: ' . $e->getMessage());
            $error = 'Unable to log in right now. Please try again later.';
        }
    }
}

// Refresh the token if it was removed during processing.
if (empty($_SESSION['student_login_csrf'])) {
    $_SESSION['student_login_csrf'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['student_login_csrf'];

function h($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Student Login | WSS</title>

    <link
        rel="icon"
        type="image/png"
        href="../assets/images/wisnoname.png">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/login.css">
</head>

<body>
    <main class="container">
        <header class="branding">
            <div class="school-logo">
                <img
                    src="../assets/images/wisnoname.png"
                    alt="Westmead International School Logo">
            </div>

            <div class="branding-text">
                <h1>WSS</h1>
            </div>
        </header>

        <p class="subtitle">WIS Student Services</p>
        <h2>Student Login</h2>

        <?php if ($error !== ''): ?>
            <p class="error" role="alert">
                <?= h($error) ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= h($csrfToken) ?>">

            <div class="select-field">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Enter your username"
                    value="<?= h($username) ?>"
                    autocomplete="username"
                    required
                    autofocus>
            </div>

            <div class="select-field password-field">
                <label for="password">Password</label>

                <input
                    type="password"
                    name="password"
                    id="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required>

                <button
                    type="button"
                    class="toggle-password"
                    id="togglePassword"
                    aria-label="Show password"
                    aria-pressed="false">
                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                </button>
            </div>

            <button type="submit">Log In</button>
        </form>

        <footer class="login-footer">
            &copy; <?= date('Y') ?> Westmead International School
        </footer>
    </main>

    <script src="../assets/js/login.js"></script>
</body>

</html>