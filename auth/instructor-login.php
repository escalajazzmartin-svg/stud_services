<?php
session_start();

require_once __DIR__ . '/../config/database.php';

// Redirect authenticated instructors.
if (
    isset($_SESSION['user_id'], $_SESSION['role_name']) &&
    $_SESSION['role_name'] === 'instructor'
) {
    header('Location: ../registration/index.php');
    exit;
}

$error = '';
$login = '';

// Generate CSRF token.
if (empty($_SESSION['instructor_login_csrf'])) {
    $_SESSION['instructor_login_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($csrfToken) ||
        !hash_equals($_SESSION['instructor_login_csrf'], $csrfToken)
    ) {
        $error = 'Invalid request. Please refresh and try again.';
    } elseif ($login === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {
        try {
            $stmt = $pdo->prepare("
                SELECT
                    user_id,
                    username,
                    password_hash,
                    role
                FROM users
                WHERE username = :username
                  AND role = 'Instructor'
                LIMIT 1
            ");

            $stmt->execute([
                'username' => $login,
            ]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (
                $user &&
                password_verify($password, $user['password_hash'])
            ) {
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['role_name'] = 'instructor';

                unset($_SESSION['instructor_login_csrf']);

                header('Location: ../registration/index.php');
                exit;
            }

            $error = 'Invalid instructor username or password.';
        } catch (PDOException $e) {
            error_log('Instructor login failed: ' . $e->getMessage());
            $error = 'Unable to process your login. Please try again.';
        }
    }
}

$csrfToken = $_SESSION['instructor_login_csrf'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Instructor Login | WSS</title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <link
        rel="icon"
        type="image/png"
        href="../assets/images/wisnoname.png">

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
        <h2>Instructor Login</h2>

        <?php if ($error !== ''): ?>
            <p class="error" role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

            <div class="select-field">
                <label for="login">Username</label>
                <input
                    type="text"
                    id="login"
                    name="login"
                    placeholder="Enter your username"
                    value="<?= htmlspecialchars($login, ENT_QUOTES, 'UTF-8') ?>"
                    autocomplete="username"
                    required
                    autofocus>
            </div>

            <div class="select-field password-field">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
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