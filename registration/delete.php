<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$user = function_exists('current_user') ? current_user() : ($_SESSION['user'] ?? []);
$role = strtolower(trim((string)($user['role'] ?? '')));
if (!in_array($role, ['admin', 'registrar'], true)) {
    http_response_code(403);
    exit('Access denied. Only administrators and registrars can delete student records.');
}
$db = $pdo ?? $conn ?? null;
if (!$db instanceof PDO) {
    http_response_code(500);
    exit('Database connection is unavailable.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}
$token = (string)($_SESSION['registration_csrf_token'] ?? '');
if ($token === '' || !hash_equals($token, (string)($_POST['csrf_token'] ?? ''))) {
    http_response_code(419);
    exit('Session token expired. Refresh and try again.');
}
$id = filter_var($_POST['student_id'] ?? $_POST['id'] ?? '', FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1 || $id > PHP_INT_MAX) {
    http_response_code(400);
    exit('Invalid student ID.');
}
try {
    $db->beginTransaction();
    $q = $db->prepare('SELECT student_id FROM students WHERE student_id = :id LIMIT 1 FOR UPDATE');
    $q->execute(['id' => $id]);
    if (!$q->fetchColumn()) {
        $db->rollBack();
        header('Location: index.php?status=not-found');
        exit;
    }

    // Delete linked enrollment history before removing the student record.
    $q = $db->prepare('DELETE FROM enrollments WHERE student_id = :id');
    $q->execute(['id' => $id]);

    $q = $db->prepare('DELETE FROM students WHERE student_id = :id');
    $q->execute(['id' => $id]);

    $db->commit();
    header('Location: index.php?status=deleted');
    exit;
} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('Registration delete error: ' . $e->getMessage());
    http_response_code(500);
    exit('Database error while deleting the student.');
}
