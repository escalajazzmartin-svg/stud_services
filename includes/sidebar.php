<?php
require_once __DIR__ . '/auth.php';

$u = current_user();
$role = $u['role'] ?? '';

$currentFile = basename($_SERVER['SCRIPT_NAME'] ?? '');

// Menu items are filtered according to the authenticated user's role.
$menu = [
  [
    'key' => 'dashboard',
    'href' => '../dashboard/index.php',
    'label' => 'Dashboard',
    'roles' => ['Admin', 'Registrar', 'Instructor', 'Student'],
    'icon' => 'fa-house'
  ],
  [
    'key' => 'registration',
    'href' => '../registration/index.php',
    'label' => 'Student Registration',
    'roles' => ['Admin', 'Registrar', 'Instructor'],
    'icon' => 'fa-user'
  ],
  [
    'key' => 'student-registration',
    'href' => '../registration/student-registration.php',
    'label' => 'My Registration',
    'roles' => ['Student'],
    'icon' => 'fa-id-card'
  ],
  [
    'key' => 'subjects',
    'href' => '../subjects/index.php',
    'label' => 'Subjects',
    'roles' => ['Admin', 'Registrar'],
    'icon' => 'fa-book'
  ],
  [
    'key' => 'enrollment',
    'href' => '../enrollment/index.php',
    'label' => 'Enrollment',
    'roles' => ['Admin', 'Registrar'],
    'icon' => 'fa-clipboard-list'
  ],
  [
    'key' => 'grades',
    'href' => '../grades/index.php',
    'label' => 'Grades',
    'roles' => ['Admin', 'Instructor'],
    'icon' => 'fa-graduation-cap'
  ],
];

// Identify the current page for active menu highlighting.
$registrationPages = [
  'index.php',
  'student-registration.php'
];

$isRegistrationPage = in_array($currentFile, $registrationPages, true);

function sidebarIsActive(string $key, string $file, bool $isRegistrationPage): bool
{
  if ($key === 'registration' || $key === 'student-registration') {
    return $isRegistrationPage &&
      (($key === 'student-registration' && $file === 'student-registration.php') ||
        ($key === 'registration' && $file === 'index.php'));
  }

  return $file === 'index.php' && $key === 'dashboard'
    ? str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/dashboard/')
    : basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')) === $key;
}
?>

<aside class="student-sidebar">
  <div class="sidebar-brand">
    <img
      src="../assets/images/wisnoname.png"
      alt="Westmead International School logo">

    <div>
      <h2>WSS</h2>
      <span>WIS Student Services</span>
    </div>
  </div>

  <nav class="student-navigation" aria-label="Main menu">
    <?php foreach ($menu as $item): ?>
      <?php
      // Show only menu items authorized for the current role.
      if (!in_array($role, $item['roles'], true)) {
        continue;
      }

      $href = $item['href'];

      // Resolve the destination relative to the project root.
      $projectRoot = dirname(__DIR__);
      $relativePath = str_replace('../', '', $href);
      $targetFile = $projectRoot . DIRECTORY_SEPARATOR .
        str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

      if (!is_file($targetFile)) {
        continue;
      }

      $isActive = sidebarIsActive(
        $item['key'],
        $currentFile,
        $isRegistrationPage
      );
      ?>

      <a
        href="<?= e($href) ?>"
        class="<?= $isActive ? 'active' : '' ?>"
        <?= $isActive ? 'aria-current="page"' : '' ?>>

        <i
          class="fa-solid <?= e($item['icon']) ?>"
          aria-hidden="true"></i>

        <span><?= e($item['label']) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-bottom">
    <a class="logout-link" href="../auth/logout.php">
      <i
        class="fa-solid fa-right-from-bracket"
        aria-hidden="true"></i>
      <span>Sign out</span>
    </a>
  </div>
</aside>