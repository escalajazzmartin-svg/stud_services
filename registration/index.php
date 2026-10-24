<?php
// registration/index.php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$u = current_user();
$role = trim((string) ($u['role'] ?? ''));
$roleKey = strtolower($role);

$isAdmin = $roleKey === 'admin';
$isRegistrar = $roleKey === 'registrar';
$isInstructor = $roleKey === 'instructor';
$isStudent = $roleKey === 'student';

if ($isStudent) {
    header('Location: student-registration.php');
    exit;
}

if (!$isAdmin && !$isRegistrar && !$isInstructor) {
    http_response_code(403);
    exit('Access denied.');
}

$canManageStudents = $isAdmin || $isRegistrar;
$canDeleteStudents = $isAdmin;

$pdo = $pdo ?? $conn ?? null;

if (!$pdo instanceof PDO) {
    http_response_code(500);
    exit('Database connection must use PDO.');
}

$rows = [];
$courses = [];
$error = '';

function h($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Use one session token for student save and delete requests.
if ($canManageStudents && empty($_SESSION['registration_csrf_token'])) {
    $_SESSION['registration_csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['registration_csrf_token'] ?? '';

try {
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
            s.course_id,
            c.course_code,
            c.course_name
        FROM students s
        LEFT JOIN courses c
            ON c.course_id = s.course_id
    ";

    $params = [];

    if ($isInstructor) {
        $sql .= "
            INNER JOIN enrollments en
                ON en.student_id = s.student_id
            INNER JOIN enrollment_subjects es
                ON es.enrollment_id = en.enrollment_id
            INNER JOIN course_subjects cs
                ON cs.course_subject_id = es.course_subject_id
            WHERE cs.instructor_id = ?
        ";

        $params[] = $u['user_id'] ?? 0;
    }

    if ($isInstructor) {
        $sql .= "
            GROUP BY
                s.student_id,
                s.student_number,
                s.first_name,
                s.middle_name,
                s.last_name,
                s.email,
                s.phone,
                s.year_level,
                s.course_id,
                c.course_code,
                c.course_name
        ";
    }

    $sql .= " ORDER BY s.last_name, s.first_name";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Courses are needed only for the Add/Edit modal.
    if ($canManageStudents) {
        $courseStmt = $pdo->query("
            SELECT course_id, course_code, course_name
            FROM courses
            ORDER BY course_name
        ");

        $courses = $courseStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log('Student registration error: ' . $e->getMessage());
    $error = 'Unable to load registration records. Please try again later.';
}

$pageTitle = $isInstructor
    ? 'My Students’ Registration'
    : 'Student Registration';

$columnCount = $canManageStudents ? 7 : 6;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>WSS | <?= h($pageTitle) ?></title>

    <link
        rel="icon"
        type="image/png"
        href="../assets/images/wisnoname.png">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <link rel="stylesheet" href="../assets/css/include.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/registration.css">
</head>

<body>
    <div class="layout">
        <?php require __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php require __DIR__ . '/../includes/header.php'; ?>

            <main class="registration-page">
                <div class="registration-heading">
                    <div>
                        <h1><?= h($pageTitle) ?></h1>
                        <p>
                            <?= $isInstructor
                                ? 'View students registered in your assigned subjects.'
                                : 'View and manage student registration records.' ?>
                        </p>
                    </div>

                    <?php if ($canManageStudents): ?>
                        <button
                            type="button"
                            class="registration-button"
                            id="openAddModal">
                            <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                            Register Student
                        </button>
                    <?php endif; ?>
                </div>

                <section class="registration-card">
                    <?php if ($error !== ''): ?>
                        <div class="registration-message" role="alert">
                            <?= h($error) ?>
                        </div>
                    <?php endif; ?>

                    <div class="registration-toolbar">
                        <span class="registration-count" aria-live="polite">
                            Records found: <?= count($rows) ?>
                        </span>

                        <form
                            method="GET"
                            class="registration-search-form"
                            id="registrationSearchForm"
                            role="search">

                            <input
                                type="search"
                                name="search"
                                id="registrationSearch"
                                class="registration-search"
                                placeholder="Search student registration..."
                                value=""
                                autocomplete="off"
                                aria-label="Search student registration">
                        </form>
                    </div>

                    <div class="registration-table-wrap">
                        <table class="registration-table">
                            <thead>
                                <tr>
                                    <th>Student No.</th>
                                    <th>Student Name</th>
                                    <th>Course</th>
                                    <th>Year Level</th>
                                    <th>Email</th>
                                    <th>Phone</th>

                                    <?php if ($canManageStudents): ?>
                                        <th>Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>

                            <tbody id="registrationTableBody">
                                <?php foreach ($rows as $student): ?>
                                    <tr>
                                        <td>C<?= h($student['student_number']) ?></td>

                                        <td>
                                            <?= h(trim(
                                                ($student['first_name'] ?? '') . ' ' .
                                                    ($student['middle_name'] ?? '') . ' ' .
                                                    ($student['last_name'] ?? '')
                                            )) ?>
                                        </td>

                                        <td>
                                            <?= h($student['course_code'] ?? 'Not assigned') ?>
                                        </td>

                                        <td>
                                            <?= !empty($student['year_level'])
                                                ? h('Year ' . $student['year_level'])
                                                : 'Not assigned' ?>
                                        </td>

                                        <td><?= h($student['email'] ?? '—') ?></td>
                                        <td><?= h($student['phone'] ?? '—') ?></td>

                                        <?php if ($canManageStudents): ?>
                                            <td class="registration-actions">
                                                <button
                                                    type="button"
                                                    class="registration-button open-edit-modal"
                                                    data-student-id="<?= (int) $student['student_id'] ?>"
                                                    data-student-number="<?= h($student['student_number']) ?>"
                                                    data-first-name="<?= h($student['first_name']) ?>"
                                                    data-middle-name="<?= h($student['middle_name']) ?>"
                                                    data-last-name="<?= h($student['last_name']) ?>"
                                                    data-email="<?= h($student['email']) ?>"
                                                    data-phone="<?= h($student['phone']) ?>"
                                                    data-year-level="<?= h($student['year_level']) ?>"
                                                    data-course-id="<?= (int) ($student['course_id'] ?? 0) ?>">
                                                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                                    Edit
                                                </button>

                                                <?php if ($canDeleteStudents): ?>
                                                    <form
                                                        method="POST"
                                                        action="delete.php"
                                                        class="registration-delete-form"
                                                        onsubmit="return confirm('Are you sure you want to delete this student?');">

                                                        <input
                                                            type="hidden"
                                                            name="student_id"
                                                            value="<?= (int) $student['student_id'] ?>">

                                                        <input
                                                            type="hidden"
                                                            name="csrf_token"
                                                            value="<?= h($csrfToken) ?>">

                                                        <button
                                                            type="submit"
                                                            class="registration-button registration-delete-button">
                                                            <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                                            Delete
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if (!$rows): ?>
                                    <tr class="registration-no-records">
                                        <td colspan="<?= $columnCount ?>" class="registration-empty">
                                            No registration records found.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <?php if ($canManageStudents): ?>
        <div
            class="registration-modal"
            id="studentModal"
            aria-hidden="true">

            <div class="registration-modal-backdrop" data-close-modal></div>

            <section
                class="registration-modal-content"
                role="dialog"
                aria-modal="true"
                aria-labelledby="studentModalTitle">

                <div class="registration-modal-header">
                    <div>
                        <h2 id="studentModalTitle">Register Student</h2>
                        <p id="studentModalDescription">
                            Enter the student's information below.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="registration-modal-close"
                        data-close-modal
                        aria-label="Close modal">
                        &times;
                    </button>
                </div>

                <form id="studentModalForm" action="save.php" method="POST">
                    <input type="hidden" name="student_id" id="modalStudentId" value="">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= h($csrfToken) ?>">

                    <div class="registration-modal-grid">
                        <div class="registration-field">
                            <label for="modalStudentNumber">Student Number *</label>
                            <input
                                type="text"
                                id="modalStudentNumber"
                                name="student_number"
                                maxlength="50"
                                required>
                        </div>

                        <div class="registration-field">
                            <label for="modalYearLevel">Year Level *</label>
                            <input
                                type="text"
                                id="modalYearLevel"
                                name="year_level"
                                maxlength="50"
                                placeholder="e.g. 1st Year"
                                required>
                        </div>

                        <div class="registration-field">
                            <label for="modalFirstName">First Name *</label>
                            <input
                                type="text"
                                id="modalFirstName"
                                name="first_name"
                                maxlength="100"
                                required>
                        </div>

                        <div class="registration-field">
                            <label for="modalMiddleName">Middle Name</label>
                            <input
                                type="text"
                                id="modalMiddleName"
                                name="middle_name"
                                maxlength="100">
                        </div>

                        <div class="registration-field">
                            <label for="modalLastName">Last Name *</label>
                            <input
                                type="text"
                                id="modalLastName"
                                name="last_name"
                                maxlength="100"
                                required>
                        </div>

                        <div class="registration-field">
                            <label for="modalCourse">Course *</label>
                            <select id="modalCourse" name="course_id" required>
                                <option value="">Select course</option>

                                <?php foreach ($courses as $course): ?>
                                    <option value="<?= (int) $course['course_id'] ?>">
                                        <?= h(
                                            $course['course_code'] . ' - ' .
                                                $course['course_name']
                                        ) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="registration-field">
                            <label for="modalEmail">Email *</label>
                            <input
                                type="email"
                                id="modalEmail"
                                name="email"
                                maxlength="255"
                                required>
                        </div>

                        <div class="registration-field">
                            <label for="modalPhone">Phone</label>
                            <input
                                type="text"
                                id="modalPhone"
                                name="phone"
                                maxlength="30">
                        </div>
                    </div>

                    <p
                        id="studentModalMessage"
                        class="registration-modal-message"
                        role="status"
                        aria-live="polite"></p>

                    <div class="registration-modal-footer">
                        <button
                            type="button"
                            class="registration-button registration-button-secondary"
                            data-close-modal>
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="registration-button"
                            id="saveStudentButton">
                            Save Student
                        </button>
                    </div>
                </form>
            </section>
        </div>
    <?php endif; ?>

    <script src="assets/js/registration.js" defer></script>
</body>

</html>