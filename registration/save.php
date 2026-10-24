<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

// Authentication and authorization
$user = function_exists('current_user')
    ? current_user()
    : ($_SESSION['user'] ?? []);

$role = strtolower(trim((string) ($user['role'] ?? '')));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response([
        'success' => false,
        'message' => 'Method not allowed.'
    ], 405);
}

if (!in_array($role, ['admin', 'registrar'], true)) {
    json_response([
        'success' => false,
        'message' => 'Access denied.'
    ], 403);
}

// CSRF validation
$token = (string) ($_SESSION['registration_csrf_token'] ?? '');
$submittedToken = (string) ($_POST['csrf_token'] ?? '');

if ($token === '' || !hash_equals($token, $submittedToken)) {
    json_response([
        'success' => false,
        'message' => 'Your session token expired. Refresh the page and try again.'
    ], 419);
}

// Database connection
$db = $pdo ?? $conn ?? null;

if (!$db instanceof PDO) {
    json_response([
        'success' => false,
        'message' => 'Database connection is unavailable. Check config/database.php.'
    ], 500);
}

// Collect submitted values
$id = filter_var(
    $_POST['student_id'] ?? '',
    FILTER_VALIDATE_INT
);

$id = ($id !== false && $id !== null && $id > 0)
    ? $id
    : null;

$number = trim((string) ($_POST['student_number'] ?? ''));
$first = trim((string) ($_POST['first_name'] ?? ''));
$middle = trim((string) ($_POST['middle_name'] ?? ''));
$last = trim((string) ($_POST['last_name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
$address = trim((string) ($_POST['address'] ?? ''));
$courseRaw = trim((string) ($_POST['course_id'] ?? ''));
$yearRaw = trim((string) ($_POST['year_level'] ?? ''));

// Required fields
if (
    $number === '' ||
    $first === '' ||
    $last === '' ||
    $yearRaw === ''
) {
    json_response([
        'success' => false,
        'message' => 'Student number, first name, last name, and year level are required.'
    ], 422);
}

// Student number: digits only
if (!ctype_digit($number)) {
    json_response([
        'success' => false,
        'message' => 'Student number must contain digits only.'
    ], 422);
}

// Phone number: optional, but digits only when supplied
if ($phone !== '' && !ctype_digit($phone)) {
    json_response([
        'success' => false,
        'message' => 'Phone number must contain digits only.'
    ], 422);
}

// Field length validation
if (
    mb_strlen($number) > 30 ||
    mb_strlen($first) > 100 ||
    mb_strlen($middle) > 100 ||
    mb_strlen($last) > 100 ||
    mb_strlen($email) > 150 ||
    mb_strlen($phone) > 30
) {
    json_response([
        'success' => false,
        'message' => 'One or more fields exceed the allowed length.'
    ], 422);
}

// Email validation
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response([
        'success' => false,
        'message' => 'Enter a valid email address.'
    ], 422);
}

// Year-level validation
$year = filter_var($yearRaw, FILTER_VALIDATE_INT);

if ($year === false || $year < 1 || $year > 6) {
    json_response([
        'success' => false,
        'message' => 'Select a valid year level.'
    ], 422);
}

// Course validation
$courseId = null;

if ($courseRaw !== '') {
    $courseId = filter_var($courseRaw, FILTER_VALIDATE_INT);

    if ($courseId === false || $courseId < 1) {
        json_response([
            'success' => false,
            'message' => 'Select a valid course.'
        ], 422);
    }
}

try {
    if ($courseId !== null) {
        $checkCourse = $db->prepare(
            'SELECT course_id
             FROM courses
             WHERE course_id = :id'
        );

        $checkCourse->execute([
            'id' => $courseId
        ]);

        if (!$checkCourse->fetchColumn()) {
            json_response([
                'success' => false,
                'message' => 'Selected course does not exist.'
            ], 422);
        }
    }

    // Confirm the student exists when updating
    if ($id !== null) {
        $check = $db->prepare(
            'SELECT student_id
             FROM students
             WHERE student_id = :id'
        );

        $check->execute([
            'id' => $id
        ]);

        if (!$check->fetchColumn()) {
            json_response([
                'success' => false,
                'message' => 'Student record not found.'
            ], 404);
        }
    }

    // Check duplicate student number
    if ($id !== null) {
        $check = $db->prepare(
            'SELECT student_id
             FROM students
             WHERE student_number = :number
               AND student_id <> :exclude_id'
        );

        $check->execute([
            'number' => $number,
            'exclude_id' => $id
        ]);
    } else {
        $check = $db->prepare(
            'SELECT student_id
             FROM students
             WHERE student_number = :number'
        );

        $check->execute([
            'number' => $number
        ]);
    }

    if ($check->fetchColumn()) {
        json_response([
            'success' => false,
            'message' => 'That student number is already in use.'
        ], 409);
    }

    // Check duplicate email when an email was provided
    if ($email !== '') {
        if ($id !== null) {
            $check = $db->prepare(
                'SELECT student_id
                 FROM students
                 WHERE email = :email
                   AND student_id <> :exclude_id'
            );

            $check->execute([
                'email' => $email,
                'exclude_id' => $id
            ]);
        } else {
            $check = $db->prepare(
                'SELECT student_id
                 FROM students
                 WHERE email = :email'
            );

            $check->execute([
                'email' => $email
            ]);
        }

        if ($check->fetchColumn()) {
            json_response([
                'success' => false,
                'message' => 'That email address is already in use.'
            ], 409);
        }
    }

    // Prepare database values
    $values = [
        'student_number' => $number,
        'first_name' => $first,
        'middle_name' => $middle !== '' ? $middle : null,
        'last_name' => $last,
        'email' => $email !== '' ? $email : null,
        'phone' => $phone !== '' ? $phone : null,
        'address' => $address !== '' ? $address : null,
        'course_id' => $courseId,
        'year_level' => $year
    ];

    // Update existing student
    if ($id !== null) {
        $values['student_id'] = $id;

        $stmt = $db->prepare(
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

        $stmt->execute($values);

        json_response([
            'success' => true,
            'message' => 'Student information updated successfully.',
            'student_id' => $id
        ]);
    }

    // Register new student
    $stmt = $db->prepare(
        'INSERT INTO students (
            student_number,
            first_name,
            middle_name,
            last_name,
            email,
            phone,
            address,
            course_id,
            year_level
        ) VALUES (
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

    $stmt->execute($values);

    json_response([
        'success' => true,
        'message' => 'Student registered successfully.',
        'student_id' => (int) $db->lastInsertId()
    ]);
} catch (PDOException $e) {
    error_log('Registration save PDO error: ' . $e->getMessage());

    if ($e->getCode() === '23000') {
        json_response([
            'success' => false,
            'message' => 'A student number or email already exists, or the data violates a database constraint.'
        ], 409);
    }

    json_response([
        'success' => false,
        'message' => 'A database error occurred while saving the student. Check the PHP error log.'
    ], 500);
} catch (Throwable $e) {
    error_log('Registration save error: ' . $e->getMessage());

    json_response([
        'success' => false,
        'message' => 'An unexpected error occurred while saving the student.'
    ], 500);
}
