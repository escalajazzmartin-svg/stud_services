<?php
// Who may see which student? Used by dashboard.php and report.php.
//  - Admin / Registrar : every student
//  - Instructor        : only students enrolled in a subject assigned to him/her
//  - Student           : only himself/herself

// SQL that returns the student_ids of ONE instructor (bind: instructor user_id)
const MY_STUDENTS_SQL =
  'SELECT e.student_id FROM enrollments e
   JOIN enrollment_subjects es ON es.enrollment_id = e.enrollment_id
   JOIN course_subjects cs ON cs.course_subject_id = es.course_subject_id
   WHERE cs.instructor_id = ?';

function can_view_student(PDO $pdo, array $u, int $studentId): bool {
    if ($u['role'] === 'Student')    return (int)$u['student_id'] === $studentId;
    if ($u['role'] !== 'Instructor') return true;            // Admin, Registrar
    $st = $pdo->prepare('SELECT 1 FROM (' . MY_STUDENTS_SQL . ') m WHERE m.student_id = ? LIMIT 1');
    $st->execute([$u['user_id'], $studentId]);
    return (bool)$st->fetchColumn();
}

// A student's enrolled subjects + grades. An instructor only gets his/her own subjects.
function student_rows(PDO $pdo, array $u, int $studentId): array {
    $sql = 'SELECT e.academic_year, e.semester, e.status,
                   sub.subject_code, sub.subject_name, sub.units, g.grade, g.remarks
            FROM enrollments e
            JOIN enrollment_subjects es ON es.enrollment_id = e.enrollment_id
            JOIN course_subjects cs ON cs.course_subject_id = es.course_subject_id
            JOIN subjects sub ON sub.subject_id = cs.subject_id
            LEFT JOIN grades g ON g.enrollment_subject_id = es.enrollment_subject_id
            WHERE e.student_id = ?';
    $params = [$studentId];
    if ($u['role'] === 'Instructor') { $sql .= ' AND cs.instructor_id = ?'; $params[] = $u['user_id']; }
    $sql .= ' ORDER BY e.academic_year, e.semester, sub.subject_code';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}
