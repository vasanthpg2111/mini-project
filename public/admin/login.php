<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../_layout.php';

start_app_session();

if (is_admin_logged_in()) {
    header('Location: /admin/');
    exit;
}

$error = '';

function post(string $k): string
{
    return isset($_POST[$k]) ? trim((string)$_POST[$k]) : '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = post('username');
    $password = post('password');

    if ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } elseif (!admin_login($username, $password)) {
        $error = 'Invalid username or password.';
    } else {
        header('Location: /admin/');
        exit;
    }
}

render_header('Admin Login', [
    'subtitle' => 'Administrator portal',
    'right' => '<a class="pill" href="/">Student Portal</a>',
]);
?>

<div class="grid">
  <div class="card">
    <h1>Admin Login</h1>
    <div class="muted">Sign in to view feedback records.</div>

    <?php if ($error): ?>
      <div class="alert danger"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" action="">
      <label for="username">Username</label>
      <input id="username" name="username" value="<?= h(post('username')) ?>" autocomplete="username" required />

      <label for="password">Password</label>
      <input id="password" name="password" type="password" autocomplete="current-password" required />

      <div class="actions">
        <button class="btn" type="submit">Login</button>
        <a class="btn secondary" href="/">Back</a>
      </div>
    </form>

    <div class="alert" style="margin-top:14px">
      <div class="badge">Default admin</div>
      <div class="muted" style="margin-top:8px">
        Username: <strong>admin</strong><br />
        Password: <strong>admin123</strong>
      </div>
      <div class="muted small" style="margin-top:8px">
        Change this in the <code>admins</code> table for real deployments.
      </div>
    </div>
  </div>

  <div class="card">
    <h2>Security note</h2>
    <div class="muted">
      This login uses password hashing (<code>password_verify</code>) and PHP sessions.
      For production, use HTTPS and a strong admin password.
    </div>
  </div>
</div>

<?php render_footer(); ?>


