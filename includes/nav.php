<?php
// Top navigation (includes/nav.php in the folder structure).
// Include it from any module page:  include __DIR__ . '/../includes/nav.php';
// Requires includes/auth.php to be loaded first (for current_user() and e()).
// A link only shows if that module's index.php exists, so other groups' links
// appear automatically once they add their files.
$u       = current_user();
$current = basename(dirname($_SERVER['SCRIPT_NAME']));   // e.g. "dashboard", "grades"
$menu = [
  ['dashboard',    '../dashboard/index.php',    'Dashboard',    ['Admin','Registrar','Instructor','Student']],
  ['registration', '../registration/index.php', 'Registration', ['Admin','Registrar']],
  ['subjects',     '../subjects/index.php',     'Subjects',     ['Admin','Registrar']],
  ['enrollment',   '../enrollment/index.php',   'Enrollment',   ['Admin','Registrar']],
  ['grades',       '../grades/index.php',       'Grades',       ['Admin','Instructor']],
];
?>
<header class="topbar">
  <strong>WIS Student Services</strong>
  <nav>
    <?php foreach ($menu as [$key, $href, $label, $roles]):
      if (in_array($u['role'], $roles, true) && file_exists(__DIR__ . '/' . $href)): ?>
      <a href="<?= e($href) ?>"<?= $key === $current ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
    <?php endif; endforeach; ?>
  </nav>
  <span class="who"><?= e($u['username']) ?> (<?= e($u['role']) ?>) · <a href="../auth/logout.php">Sign out</a></span>
</header>
