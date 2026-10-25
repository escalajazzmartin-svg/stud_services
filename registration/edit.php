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

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('Invalid student ID.');
}

$q = $db->prepare(
    'SELECT student_id, student_number, first_name, middle_name, last_name,
            email, phone, address, course_id, year_level
     FROM students
     WHERE student_id = :id'
);
$q->execute(['id' => $id]);

$student = $q->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    http_response_code(404);
    exit('Student not found.');
}

$courses = $db->query(
    'SELECT course_id, course_code, course_name
     FROM courses
     ORDER BY course_code'
)->fetchAll(PDO::FETCH_ASSOC);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        !hash_equals(
            (string)$_SESSION['registration_csrf_token'],
            (string)($_POST['csrf_token'] ?? '')
        )
    ) {
        $errors[] = 'Session token expired. Refresh and try again.';
    } else {
        foreach (
            [
                'student_number',
                'first_name',
                'middle_name',
                'last_name',
                'email',
                'phone',
                'address',
                'course_id',
                'year_level'
            ] as $key
        ) {
            $student[$key] = trim((string)($_POST[$key] ?? ''));
        }

        if (
            $student['student_number'] === '' ||
            $student['first_name'] === '' ||
            $student['last_name'] === '' ||
            $student['year_level'] === ''
        ) {
            $errors[] = 'Student number, first name, last name, and year level are required.';
        } elseif (
            !ctype_digit($student['student_number']) ||
            mb_strlen($student['student_number']) > 30
        ) {
            $errors[] = 'Student Number must contain digits only and be at most 30 characters.';
        } elseif (
            (
                $student['first_name'] !== '' &&
                !preg_match('/^[\pL\s\'\.-]+$/u', $student['first_name'])
            ) || (
                $student['middle_name'] !== '' &&
                !preg_match('/^[\pL\s\'\.-]+$/u', $student['middle_name'])
            ) || (
                $student['last_name'] !== '' &&
                !preg_match('/^[\pL\s\'\.-]+$/u', $student['last_name'])
            )
        ) {
            $errors[] = 'Name fields may only contain letters, spaces, apostrophes, periods, and hyphens.';
        } elseif (
            $student['phone'] !== '' &&
            (!ctype_digit($student['phone']) || mb_strlen($student['phone']) > 30)
        ) {
            $errors[] = 'Phone Number must contain digits only and be at most 30 characters.';
        } elseif (
            mb_strlen($student['first_name']) > 100 ||
            mb_strlen($student['middle_name']) > 100 ||
            mb_strlen($student['last_name']) > 100 ||
            mb_strlen($student['email']) > 150 ||
            mb_strlen($student['address']) > 255
        ) {
            $errors[] = 'One or more text fields exceed the allowed length.';
        } elseif (
            $student['email'] !== '' &&
            !filter_var($student['email'], FILTER_VALIDATE_EMAIL)
        ) {
            $errors[] = 'Enter a valid email address.';
        } else {
            $year = filter_var(
                $student['year_level'],
                FILTER_VALIDATE_INT
            );

            $course = $student['course_id'] === ''
                ? null
                : filter_var(
                    $student['course_id'],
                    FILTER_VALIDATE_INT
                );

            if ($year === false || $year < 1 || $year > 6) {
                $errors[] = 'Select a valid year level.';
            } elseif (
                $student['course_id'] !== '' &&
                ($course === false || $course < 1)
            ) {
                $errors[] = 'Select a valid course.';
            } else {
                try {
                    $q = $db->prepare(
                        'SELECT student_id
                         FROM students
                         WHERE student_number = :v
                         AND student_id <> :id
                         LIMIT 1'
                    );
                    $q->execute([
                        'v' => $student['student_number'],
                        'id' => $id
                    ]);

                    if ($q->fetchColumn()) {
                        $errors[] = 'That student number is already in use.';
                    }

                    if (!$errors && $student['email'] !== '') {
                        $q = $db->prepare(
                            'SELECT student_id
                             FROM students
                             WHERE email = :v
                             AND student_id <> :id
                             LIMIT 1'
                        );
                        $q->execute([
                            'v' => $student['email'],
                            'id' => $id
                        ]);

                        if ($q->fetchColumn()) {
                            $errors[] = 'That email address is already in use.';
                        }
                    }

                    if (!$errors && $course !== null) {
                        $q = $db->prepare(
                            'SELECT course_id
                             FROM courses
                             WHERE course_id = :v
                             LIMIT 1'
                        );
                        $q->execute(['v' => $course]);

                        if (!$q->fetchColumn()) {
                            $errors[] = 'Selected course does not exist.';
                        }
                    }

                    if (!$errors) {
                        $q = $db->prepare(
                            'UPDATE students
                             SET student_number = :student_number,
                                 first_name = :first_name,
                                 middle_name = :middle_name,
                                 last_name = :last_name,
                                 email = :email,
                                 phone = :phone,
                                 address = :address,
                                 course_id = :course_id,
                                 year_level = :year_level
                             WHERE student_id = :student_id'
                        );

                        $q->execute([
                            'student_number' => $student['student_number'],
                            'first_name' => $student['first_name'],
                            'middle_name' => $student['middle_name'] !== ''
                                ? $student['middle_name']
                                : null,
                            'last_name' => $student['last_name'],
                            'email' => $student['email'] !== ''
                                ? $student['email']
                                : null,
                            'phone' => $student['phone'] !== ''
                                ? $student['phone']
                                : null,
                            'address' => $student['address'] !== ''
                                ? $student['address']
                                : null,
                            'course_id' => $course,
                            'year_level' => $year,
                            'student_id' => $id
                        ]);

                        header('Location: index.php?status=updated');
                        exit;
                    }
                } catch (PDOException $e) {
                    error_log('Registration edit error: ' . $e->getMessage());

                    $errors[] = $e->getCode() === '23000'
                        ? 'A student number or email already exists.'
                        : 'Database error while updating student.';
                }
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
    <title>Edit Student</title>
    <link rel="stylesheet" href="assets/css/registration.css">
</head>

<body>
    <main class="registration-page">
        <section class="registration-card">
            <h1>Edit Student</h1>
            <p>Update student information.</p>

            <?php foreach ($errors as $error): ?>
                <p class="registration-error"><?= e($error) ?></p>
            <?php endforeach; ?>

            <form method="post" action="edit.php?id=<?= (int)$id ?>">
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
                            value="<?= e((string)$student['student_number']) ?>">
                    </label>

                    <label>
                        First Name *
                        <input
                            type="text"
                            name="first_name"
                            maxlength="100"
                            required
                            value="<?= e((string)$student['first_name']) ?>">
                    </label>

                    <label>
                        Middle Name
                        <input
                            type="text"
                            name="middle_name"
                            maxlength="100"
                            value="<?= e((string)($student['middle_name'] ?? '')) ?>">
                    </label>

                    <label>
                        Last Name *
                        <input
                            type="text"
                            name="last_name"
                            maxlength="100"
                            required
                            value="<?= e((string)$student['last_name']) ?>">
                    </label>

                    <label>
                        Email
                        <input
                            type="email"
                            name="email"
                            maxlength="150"
                            value="<?= e((string)($student['email'] ?? '')) ?>">
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
                            value="<?= e((string)($student['phone'] ?? '')) ?>">
                    </label>

                    <label>
                        Year Level *
                        <select name="year_level" required>
                            <option value="">Select year</option>

                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option
                                    value="<?= $i ?>"
                                    <?= (string)$student['year_level'] === (string)$i ? 'selected' : '' ?>>
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
                                    <?= (string)($student['course_id'] ?? '') === (string)$c['course_id'] ? 'selected' : '' ?>>
                                    <?= e($c['course_code'] . ' - ' . $c['course_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="registration-full-width">
                        Address
                        <textarea name="address" rows="3"><?= e((string)($student['address'] ?? '')) ?></textarea>
                    </label>

                </div>

                <div class="registration-actions">
                    <button type="submit" class="registration-button">
                        Update Student
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