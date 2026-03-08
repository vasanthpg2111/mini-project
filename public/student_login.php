<?php

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/_layout.php';

start_app_session();

if (is_student_logged_in()) {
    header('Location: /');
    exit;
}

$error = '';

function post(string $k): string
{
    return isset($_POST[$k]) ? trim((string)$_POST[$k]) : '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = post('student_id');
    $password = post('password');

    if ($student_id === '' || $password === '') {
        $error = 'Please enter your student ID and password.';
    } elseif (!student_login($student_id, $password)) {
        $error = 'Invalid student ID or password.';
    } else {
        header('Location: /');
        exit;
    }
}

$rightLinks = [];
$rightLinks[] = '<a class="pill" href="/admin/login.php">Admin Login</a>';

render_header('Student Login', [
    'subtitle' => 'Student portal',
    'right' => implode(' ', $rightLinks),
]);
?>

<div class="grid">
  <div class="card">
    <h1>Student Login</h1>
    <div class="muted">Sign in to auto-fill your details when submitting feedback.</div>

    <?php if ($error): ?>
      <div class="alert danger"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" action="">
      <label for="student_id">Student ID</label>
      <input id="student_id" name="student_id" value="<?= h(post('student_id')) ?>" autocomplete="username" required />

      <label for="password">Password</label>
      <input id="password" name="password" type="password" autocomplete="current-password" required />

      <div class="actions">
        <button class="btn" type="submit">Login</button>
        <a class="btn secondary" href="/">Back</a>
      </div>
    </form>

    <div class="alert" style="margin-top:14px">
      <div class="badge">Note</div>
      <div class="muted" style="margin-top:8px">
        Student accounts are managed by the administrator in the database.
      </div>
    </div>
  </div>

  <div class="card">
    <h2>Why login?</h2>
    <div class="muted">
      - Faster feedback submission<br />
      - Fewer typos in name / ID<br />
      - Consistent data for reports
    </div>
  </div>
</div>

<?php render_footer(); ?>


