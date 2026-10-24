<?php
// registration/student-registration.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$u = current_user();
$role = $u['role'] ?? '';

// This page is exclusively for students.
if ($role !== 'Student') {
    http_response_code(403);
    exit('Access denied. This page is for students only.');
}

$pdo = $pdo ?? $conn ?? null;

if (!$pdo instanceof PDO) {
    http_response_code(500);
    exit('Database connection must use PDO.');
}

$student = null;
$error = '';

function sr_h($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

try {
    // Prefer the student ID associated with the logged-in account.
    $studentId = $u['student_id'] ?? null;

    // Resolve the student ID through the users table if necessary.
    if (!$studentId) {
        $stmt = $pdo->prepare(
            'SELECT student_id FROM users WHERE user_id = ? LIMIT 1'
        );
        $stmt->execute([$u['user_id'] ?? 0]);
        $studentId = $stmt->fetchColumn();
    }

    if ($studentId) {
        $sql = "
            SELECT
                s.student_id,
                s.student_number,
                s.first_name,
                s.middle_name,
                s.last_name,
                s.email,
                s.phone,
                s.year_level,
                c.course_code,
                c.course_name
            FROM students s
            LEFT JOIN courses c ON c.course_id = s.course_id
            WHERE s.student_id = ?
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$studentId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $ex) {
    error_log($ex->getMessage());
    $error = 'Unable to load your registration information. Please try again later.';
}

$pageTitle = 'My Registration';

$fullName = $student
    ? trim(implode(' ', array_filter([
        $student['first_name'] ?? '',
        $student['middle_name'] ?? '',
        $student['last_name'] ?? ''
    ], static fn($part) => trim((string)$part) !== '')))
    : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>WSS | <?= sr_h($pageTitle) ?></title>

    <link rel="icon" type="image/png"
        href="../assets/images/wisnoname.png">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <link rel="stylesheet" href="../assets/css/include.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/student-registration.css">
</head>

<body>
    <div class="layout">

        <?php require __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">

            <?php require __DIR__ . '/../includes/header.php'; ?>

            <main class="student-registration-page">

                <header class="sr-heading">
                    <div>
                        <span class="sr-eyebrow">STUDENT SERVICES</span>
                        <h1>My Registration</h1>
                        <p>View your personal student registration information.</p>
                    </div>

                    <div class="sr-heading-icon" aria-hidden="true">
                        <i class="fa-solid fa-id-card"></i>
                    </div>
                </header>

                <?php if ($error !== ''): ?>

                    <div class="sr-message sr-error">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <?= sr_h($error) ?>
                    </div>

                <?php elseif (!$student): ?>

                    <section class="sr-card sr-empty">
                        <div class="sr-empty-icon">
                            <i class="fa-solid fa-user-graduate"></i>
                        </div>

                        <h2>Registration Information Unavailable</h2>
                        <p>
                            Your account is not currently linked to a student
                            registration record. Please contact the Registrar
                            for assistance.
                        </p>
                    </section>

                <?php else: ?>

                    <section class="sr-card">

                        <div class="sr-profile-header">
                            <div class="sr-avatar" aria-hidden="true">
                                <i class="fa-solid fa-user-graduate"></i>
                            </div>

                            <div class="sr-profile-heading">
                                <h2><?= sr_h($fullName) ?></h2>
                                <p>
                                    Student No.
                                    <strong><?= sr_h($student['student_number'] ?? '—') ?></strong>
                                </p>
                            </div>

                            <span class="sr-status">
                                <i class="fa-solid fa-circle-check"></i>
                                My Record
                            </span>
                        </div>

                        <div class="sr-divider"></div>

                        <div class="sr-section-heading">
                            <i class="fa-solid fa-address-card"></i>
                            <h3>Personal Information</h3>
                        </div>

                        <div class="sr-details-grid">

                            <div class="sr-detail">
                                <span class="sr-label">
                                    <i class="fa-regular fa-user"></i>
                                    Full Name
                                </span>
                                <p><?= sr_h($fullName ?: '—') ?></p>
                            </div>

                            <div class="sr-detail">
                                <span class="sr-label">
                                    <i class="fa-solid fa-hashtag"></i>
                                    Student Number
                                </span>
                                <p><?= sr_h($student['student_number'] ?? '—') ?></p>
                            </div>

                            <div class="sr-detail">
                                <span class="sr-label">
                                    <i class="fa-regular fa-envelope"></i>
                                    Email Address
                                </span>
                                <p><?= sr_h($student['email'] ?: '—') ?></p>
                            </div>

                            <div class="sr-detail">
                                <span class="sr-label">
                                    <i class="fa-solid fa-phone"></i>
                                    Phone Number
                                </span>
                                <p><?= sr_h($student['phone'] ?: '—') ?></p>
                            </div>

                        </div>

                        <div class="sr-divider"></div>

                        <div class="sr-section-heading">
                            <i class="fa-solid fa-graduation-cap"></i>
                            <h3>Academic Information</h3>
                        </div>

                        <div class="sr-details-grid">

                            <div class="sr-detail">
                                <span class="sr-label">
                                    <i class="fa-solid fa-book-open"></i>
                                    Course
                                </span>
                                <p>
                                    <?= sr_h($student['course_code'] ?? 'Not assigned') ?>
                                    <?php if (!empty($student['course_name'])): ?>
                                        <span class="sr-subtext">
                                            <?= sr_h($student['course_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                </p>
                            </div>

                            <div class="sr-detail">
                                <span class="sr-label">
                                    <i class="fa-solid fa-layer-group"></i>
                                    Year Level
                                </span>
                                <p>
                                    <?= !empty($student['year_level'])
                                        ? sr_h('Year ' . $student['year_level'])
                                        : 'Not assigned' ?>
                                </p>
                            </div>

                        </div>

                    </section>

                <?php endif; ?>

            </main>
        </div>
    </div>
</body>

</html>