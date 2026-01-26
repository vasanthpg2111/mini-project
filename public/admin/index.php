<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../_layout.php';

require_admin();

$pdo = db();

function q(string $k): string
{
    return isset($_GET[$k]) ? trim((string)$_GET[$k]) : '';
}

$target = q('target'); // '', 'subject', 'faculty'
$rating = q('rating'); // '', '1'..'5'

$where = [];
$params = [];

if (in_array($target, ['subject', 'faculty'], true)) {
    $where[] = 'f.target_type = ?';
    $params[] = $target;
}
if ($rating !== '' && ctype_digit($rating) && (int)$rating >= 1 && (int)$rating <= 5) {
    $where[] = 'f.rating = ?';
    $params[] = (int)$rating;
} else {
    $rating = '';
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Summary
$summaryStmt = $pdo->prepare("
  SELECT
    COUNT(*) AS total,
    AVG(f.rating) AS avg_rating
  FROM feedback f
  {$whereSql}
");
$summaryStmt->execute($params);
$summary = $summaryStmt->fetch() ?: ['total' => 0, 'avg_rating' => null];

// Recent feedback (paginated)
$page = q('page');
$pageNum = (ctype_digit($page) && (int)$page >= 1) ? (int)$page : 1;
$pageSize = 25;
$offset = ($pageNum - 1) * $pageSize;

$listStmt = $pdo->prepare("
  SELECT
    f.*,
    s.name AS subject_name,
    fc.name AS faculty_name,
    fc.department AS faculty_department
  FROM feedback f
  LEFT JOIN subjects s ON s.id = f.subject_id
  LEFT JOIN faculty fc ON fc.id = f.faculty_id
  {$whereSql}
  ORDER BY f.created_at DESC, f.id DESC
  LIMIT {$pageSize} OFFSET {$offset}
");
$listStmt->execute($params);
$rows = $listStmt->fetchAll();

$countStmt = $pdo->prepare("SELECT COUNT(*) AS c FROM feedback f {$whereSql}");
$countStmt->execute($params);
$totalRows = (int)($countStmt->fetch()['c'] ?? 0);
$totalPages = max(1, (int)ceil($totalRows / $pageSize));

// Simple breakdowns (top 10)
$topSubjectsStmt = $pdo->query("
  SELECT s.name, COUNT(*) AS c, AVG(f.rating) AS avg_rating
  FROM feedback f
  INNER JOIN subjects s ON s.id = f.subject_id
  WHERE f.target_type = 'subject'
  GROUP BY s.id
  ORDER BY c DESC
  LIMIT 10
");
$topSubjects = $topSubjectsStmt->fetchAll();

$topFacultyStmt = $pdo->query("
  SELECT fc.name, fc.department, COUNT(*) AS c, AVG(f.rating) AS avg_rating
  FROM feedback f
  INNER JOIN faculty fc ON fc.id = f.faculty_id
  WHERE f.target_type = 'faculty'
  GROUP BY fc.id
  ORDER BY c DESC
  LIMIT 10
");
$topFaculty = $topFacultyStmt->fetchAll();

$right = '<a class="pill" href="/admin/export.php?' . h(http_build_query(['target' => $target, 'rating' => $rating])) . '">Export CSV</a>'
    . '<a class="pill" href="/admin/logout.php">Logout</a>';

render_header('Admin Dashboard', [
    'subtitle' => 'Feedback records',
    'right' => $right,
]);
?>

<div class="grid">
  <div class="card">
    <h1>Feedback Records</h1>
    <div class="muted small">
      Logged in as <strong><?= h((string)($_SESSION['admin_username'] ?? 'admin')) ?></strong>
    </div>

    <div class="row" style="margin-top:8px">
      <div class="alert">
        <div class="badge">Total</div>
        <div style="margin-top:8px"><strong><?= (int)$summary['total'] ?></strong> submissions</div>
      </div>
      <div class="alert">
        <div class="badge">Average rating</div>
        <div style="margin-top:8px"><strong><?= $summary['avg_rating'] !== null ? number_format((float)$summary['avg_rating'], 2) : '—' ?></strong> / 5</div>
      </div>
    </div>

    <form method="get" action="" style="margin-top:8px">
      <div class="row">
        <div>
          <label for="target">Filter: Target</label>
          <select id="target" name="target">
            <option value="" <?= $target === '' ? 'selected' : '' ?>>All</option>
            <option value="subject" <?= $target === 'subject' ? 'selected' : '' ?>>Subject</option>
            <option value="faculty" <?= $target === 'faculty' ? 'selected' : '' ?>>Faculty</option>
          </select>
        </div>
        <div>
          <label for="rating">Filter: Rating</label>
          <select id="rating" name="rating">
            <option value="" <?= $rating === '' ? 'selected' : '' ?>>All</option>
            <?php for ($i = 5; $i >= 1; $i--): ?>
              <option value="<?= $i ?>" <?= $rating === (string)$i ? 'selected' : '' ?>><?= $i ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>
      <div class="actions">
        <button class="btn" type="submit">Apply Filters</button>
        <a class="btn secondary" href="/admin/">Reset</a>
      </div>
    </form>

    <div style="overflow:auto; margin-top:12px">
      <table class="table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Student</th>
            <th>For</th>
            <th>Rating</th>
            <th>Comments</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="5" class="muted">No records found.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="small muted"><?= h((string)$r['created_at']) ?></td>
            <td>
              <div><strong><?= h((string)$r['student_name']) ?></strong></div>
              <div class="small muted">
                ID: <?= h((string)$r['student_id']) ?>
                <?php if (!empty($r['student_email'])): ?>
                  <br /><?= h((string)$r['student_email']) ?>
                <?php endif; ?>
              </div>
              <?php if (!empty($r['department']) || !empty($r['year_of_study'])): ?>
                <div class="small muted">
                  <?= !empty($r['department']) ? h((string)$r['department']) : '' ?>
                  <?= (!empty($r['department']) && !empty($r['year_of_study'])) ? ' • ' : '' ?>
                  <?= !empty($r['year_of_study']) ? h((string)$r['year_of_study']) : '' ?>
                </div>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($r['target_type'] === 'subject'): ?>
                <div class="badge">Subject</div>
                <div style="margin-top:6px"><strong><?= h((string)($r['subject_name'] ?? '—')) ?></strong></div>
              <?php else: ?>
                <div class="badge">Faculty</div>
                <div style="margin-top:6px"><strong><?= h((string)($r['faculty_name'] ?? '—')) ?></strong></div>
                <?php if (!empty($r['faculty_department'])): ?>
                  <div class="small muted"><?= h((string)$r['faculty_department']) ?></div>
                <?php endif; ?>
              <?php endif; ?>
            </td>
            <td><strong><?= (int)$r['rating'] ?></strong> / 5</td>
            <td class="muted"><?= $r['comments'] ? nl2br(h((string)$r['comments'])) : '—' ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
      <div class="actions" style="justify-content:space-between">
        <div class="muted small">Page <?= $pageNum ?> of <?= $totalPages ?></div>
        <div class="right">
          <?php
            $base = ['target' => $target, 'rating' => $rating];
            $prev = max(1, $pageNum - 1);
            $next = min($totalPages, $pageNum + 1);
          ?>
          <a class="pill" href="/admin/?<?= h(http_build_query($base + ['page' => $prev])) ?>">Prev</a>
          <a class="pill" href="/admin/?<?= h(http_build_query($base + ['page' => $next])) ?>">Next</a>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2>Quick insights</h2>

    <div class="alert">
      <div class="badge">Top subjects (by submissions)</div>
      <div style="margin-top:10px; overflow:auto">
        <table class="table">
          <thead><tr><th>Subject</th><th>Count</th><th>Avg</th></tr></thead>
          <tbody>
          <?php if (!$topSubjects): ?>
            <tr><td colspan="3" class="muted">No subject feedback yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($topSubjects as $s): ?>
            <tr>
              <td><?= h((string)$s['name']) ?></td>
              <td><?= (int)$s['c'] ?></td>
              <td><?= number_format((float)$s['avg_rating'], 2) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="alert">
      <div class="badge">Top faculty (by submissions)</div>
      <div style="margin-top:10px; overflow:auto">
        <table class="table">
          <thead><tr><th>Faculty</th><th>Count</th><th>Avg</th></tr></thead>
          <tbody>
          <?php if (!$topFaculty): ?>
            <tr><td colspan="3" class="muted">No faculty feedback yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($topFaculty as $f): ?>
            <tr>
              <td>
                <?= h((string)$f['name']) ?>
                <?php if (!empty($f['department'])): ?>
                  <div class="small muted"><?= h((string)$f['department']) ?></div>
                <?php endif; ?>
              </td>
              <td><?= (int)$f['c'] ?></td>
              <td><?= number_format((float)$f['avg_rating'], 2) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php render_footer(); ?>


