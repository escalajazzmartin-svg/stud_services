<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$user = function_exists('current_user') ? current_user() : ($_SESSION['user'] ?? []);
$role = strtolower(trim((string)($user['role'] ?? '')));

if (!in_array($role, ['admin', 'registrar'], true)) {
    http_response_code(403);
    exit('Access denied.');
}

$db = $pdo ?? $conn ?? null;

if (!$db instanceof PDO) {
    http_response_code(500);
    exit('Database connection is unavailable.');
}

if (empty($_SESSION['registration_csrf_token'])) {
    $_SESSION['registration_csrf_token'] = bin2hex(random_bytes(32));
}

$courses = $db->query(
    'SELECT course_id, course_code, course_name FROM courses ORDER BY course_code'
)->fetchAll(PDO::FETCH_ASSOC);

$fields = [
    'student_number' => '',
    'first_name' => '',
    'middle_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
    'course_id' => '',
    'year_level' => ''
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($fields as $key => $_) {
        $fields[$key] = trim((string)($_POST[$key] ?? ''));
    }

    if (
        !hash_equals(
            (string)$_SESSION['registration_csrf_token'],
            (string)($_POST['csrf_token'] ?? '')
        )
    ) {
        $errors[] = 'Session token expired. Refresh and try again.';
    } elseif (
        $fields['student_number'] === '' ||
        $fields['first_name'] === '' ||
        $fields['last_name'] === '' ||
        $fields['year_level'] === ''
    ) {
        $errors[] = 'Student number, first name, last name, and year level are required.';
    } elseif (!ctype_digit($fields['student_number'])) {
        $errors[] = 'Student Number must contain digits only.';
    } elseif (
        $fields['phone'] !== '' &&
        !ctype_digit($fields['phone'])
    ) {
        $errors[] = 'Phone Number must contain digits only.';
    } elseif (
        $fields['email'] !== '' &&
        !filter_var($fields['email'], FILTER_VALIDATE_EMAIL)
    ) {
        $errors[] = 'Enter a valid email address.';
    } else {
        $year = filter_var($fields['year_level'], FILTER_VALIDATE_INT);
        $course = $fields['course_id'] === ''
            ? null
            : filter_var($fields['course_id'], FILTER_VALIDATE_INT);

        if ($year === false || $year < 1 || $year > 6) {
            $errors[] = 'Select a valid year level.';
        } elseif (
            $fields['course_id'] !== '' &&
            ($course === false || $course < 1)
        ) {
            $errors[] = 'Select a valid course.';
        } else {
            try {
                $q = $db->prepare(
                    'SELECT student_id FROM students WHERE student_number = :v'
                );
                $q->execute(['v' => $fields['student_number']]);

                if ($q->fetchColumn()) {
                    $errors[] = 'That student number is already in use.';
                }

                if (!$errors && $fields['email'] !== '') {
                    $q = $db->prepare(
                        'SELECT student_id FROM students WHERE email = :v'
                    );
                    $q->execute(['v' => $fields['email']]);

                    if ($q->fetchColumn()) {
                        $errors[] = 'That email address is already in use.';
                    }
                }

                if (!$errors && $course !== null) {
                    $q = $db->prepare(
                        'SELECT course_id FROM courses WHERE course_id = :v'
                    );
                    $q->execute(['v' => $course]);

                    if (!$q->fetchColumn()) {
                        $errors[] = 'Selected course does not exist.';
                    }
                }

                if (!$errors) {
                    $q = $db->prepare(
                        'INSERT INTO students
                        (
                            student_number,
                            first_name,
                            middle_name,
                            last_name,
                            email,
                            phone,
                            address,
                            course_id,
                            year_level
                        )
                        VALUES
                        (
                            :student_number,
                            :first_name,
                            :middle_name,
                            :last_name,
                            :email,
                            :phone,
                            :address,
                            :course_id,
                            :year_level
                        )'
                    );

                    $q->execute([
                        'student_number' => $fields['student_number'],
                        'first_name' => $fields['first_name'],
                        'middle_name' => $fields['middle_name'] !== ''
                            ? $fields['middle_name']
                            : null,
                        'last_name' => $fields['last_name'],
                        'email' => $fields['email'] !== ''
                            ? $fields['email']
                            : null,
                        'phone' => $fields['phone'] !== ''
                            ? $fields['phone']
                            : null,
                        'address' => $fields['address'] !== ''
                            ? $fields['address']
                            : null,
                        'course_id' => $course,
                        'year_level' => $year
                    ]);

                    header('Location: index.php?status=added');
                    exit;
                }
            } catch (PDOException $e) {
                error_log('Registration add error: ' . $e->getMessage());

                $errors[] = $e->getCode() === '23000'
                    ? 'A student number or email already exists.'
                    : 'Database error while adding student.';
            }
        }
    }
}

function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Add Student</title>
    <link rel="stylesheet" href="assets/css/registration.css">
</head>

<body>
    <main class="registration-page">
        <section class="registration-card">
            <h1>Add Student</h1>
            <p>Enter student information.</p>

            <?php foreach ($errors as $error): ?>
                <p class="registration-error"><?= e($error) ?></p>
            <?php endforeach; ?>

            <form method="post" action="add.php">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($_SESSION['registration_csrf_token']) ?>">

                <div class="registration-form-grid">

                    <label>
                        Student Number *
                        <input
                            type="text"
                            name="student_number"
                            maxlength="30"
                            inputmode="numeric"
                            pattern="[0-9]+"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                            required
                            value="<?= e($fields['student_number']) ?>">
                    </label>

                    <label>
                        First Name *
                        <input
                            type="text"
                            name="first_name"
                            maxlength="100"
                            required
                            value="<?= e($fields['first_name']) ?>">
                    </label>

                    <label>
                        Middle Name
                        <input
                            type="text"
                            name="middle_name"
                            maxlength="100"
                            value="<?= e($fields['middle_name']) ?>">
                    </label>

                    <label>
                        Last Name *
                        <input
                            type="text"
                            name="last_name"
                            maxlength="100"
                            required
                            value="<?= e($fields['last_name']) ?>">
                    </label>

                    <label>
                        Email
                        <input
                            type="email"
                            name="email"
                            maxlength="150"
                            value="<?= e($fields['email']) ?>">
                    </label>

                    <label>
                        Phone
                        <input
                            type="text"
                            name="phone"
                            maxlength="30"
                            inputmode="numeric"
                            pattern="[0-9]+"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                            value="<?= e($fields['phone']) ?>">
                    </label>

                    <label>
                        Year Level *
                        <select name="year_level" required>
                            <option value="">Select year</option>
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option
                                    value="<?= $i ?>"
                                    <?= $fields['year_level'] === (string)$i ? 'selected' : '' ?>>
                                    Year <?= $i ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </label>

                    <label>
                        Course
                        <select name="course_id">
                            <option value="">No course selected</option>
                            <?php foreach ($courses as $c): ?>
                                <option
                                    value="<?= (int)$c['course_id'] ?>"
                                    <?= $fields['course_id'] === (string)$c['course_id'] ? 'selected' : '' ?>>
                                    <?= e($c['course_code'] . ' - ' . $c['course_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="registration-full-width">
                        Address
                        <textarea name="address" rows="3"><?= e($fields['address']) ?></textarea>
                    </label>

                </div>

                <div class="registration-actions">
                    <button type="submit" class="registration-button">
                        Save Student
                    </button>

                    <a href="index.php" class="registration-button-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </section>
    </main>
</body>

</html>