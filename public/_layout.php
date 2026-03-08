<?php
function h(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function render_header(string $title, array $meta = []): void
{
    $rightHtml = $meta['right'] ?? '';
    $subtitle = $meta['subtitle'] ?? '';
    ?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= h($title) ?></title>
    <link rel="stylesheet" href="/assets/styles.css" />
  </head>
  <body>
    <div class="container">
      <div class="topbar">
        <div>
          <div class="brand">College Feedback System</div>
          <?php if ($subtitle): ?>
            <div class="muted small"><?= h($subtitle) ?></div>
          <?php endif; ?>
        </div>
        <div class="right">
          <?= $rightHtml ?>
        </div>
      </div>
<?php
}

function render_footer(): void
{
    ?>
      <div class="muted small" style="margin-top:16px">
        <span class="badge">PHP + MySQL</span>
        <span class="badge">Online feedback</span>
      </div>
    </div>
  </body>
</html>
<?php
}

function render_fatal(string $title, string $message): void
{
    // Minimal standalone error page (avoid dependency on DB, etc.)
    ?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= h($title) ?></title>
    <link rel="stylesheet" href="/assets/styles.css" />
  </head>
  <body>
    <div class="container">
      <div class="topbar">
        <div>
          <div class="brand">College Feedback System</div>
          <div class="muted small">Troubleshooting</div>
        </div>
        <div class="right">
          <a class="pill" href="/">Home</a>
        </div>
      </div>
      <div class="card" style="margin-top:18px">
        <h1><?= h($title) ?></h1>
        <div class="alert danger" style="margin-top:10px">
          <div class="muted small" style="white-space:pre-wrap"><?= h($message) ?></div>
        </div>
        <div class="muted small">
          If this is a database error, check <code>config.php</code> and confirm the database is running and initialized.
        </div>
      </div>
    </div>
  </body>
</html>
<?php
}

// Friendly global error page (prevents raw stack traces in the browser).
set_exception_handler(function (Throwable $e): void {
    if (!headers_sent()) {
        http_response_code(500);
    }
    $msg = $e->getMessage();
    render_fatal('Application Error', $msg !== '' ? $msg : 'Unexpected error.');
    exit;
});


