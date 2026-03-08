<?php

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/_layout.php';

start_app_session();
$pdo = db();
$subjects = $pdo->query("SELECT id, name FROM subjects WHERE is_active = 1 ORDER BY name")->fetchAll();
$faculty = $pdo->query("SELECT id, name, department FROM faculty WHERE is_active = 1 ORDER BY name")->fetchAll();

$errors = [];
$ok = false;
$currentStudent = current_student();

function post(string $k): string
{
    return isset($_POST[$k]) ? trim((string)$_POST[$k]) : '';
}

function student_prefill(?array $student, string $field, string $postKey): string
{
    $fromPost = post($postKey);
    if ($fromPost !== '') {
        return $fromPost;
    }

    if (!$student) {
        return '';
    }

    if ($field === 'name') {
        return (string)($student['name'] ?? '');
    }
    if ($field === 'student_id') {
        return (string)($student['student_id'] ?? '');
    }
    if ($field === 'email') {
        return (string)($student['email'] ?? '');
    }
    if ($field === 'department') {
        return (string)($student['department'] ?? '');
    }
    if ($field === 'year_of_study') {
        return (string)($student['year_of_study'] ?? '');
    }

    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_student_logged_in()) {
        $errors[] = 'Please log in as a student before submitting feedback.';
    } else {
        $student_name = post('student_name') !== '' ? post('student_name') : student_prefill($currentStudent, 'name', 'student_name');
        $student_id = post('student_id') !== '' ? post('student_id') : student_prefill($currentStudent, 'student_id', 'student_id');
        $student_email = post('student_email') !== '' ? post('student_email') : student_prefill($currentStudent, 'email', 'student_email');
        $department = post('department') !== '' ? post('department') : student_prefill($currentStudent, 'department', 'department');
        $year_of_study = post('year_of_study') !== '' ? post('year_of_study') : student_prefill($currentStudent, 'year_of_study', 'year_of_study');
        $target_type = post('target_type'); // subject|faculty
        $subject_id = post('subject_id');
        $faculty_id = post('faculty_id');
        $rating = (int)post('rating');
        $comments = post('comments');

        if ($student_name === '' || mb_strlen($student_name) > 120) {
            $errors[] = 'Please enter your name (max 120 characters).';
        }
        if ($student_id === '' || mb_strlen($student_id) > 40) {
            $errors[] = 'Please enter your student ID (max 40 characters).';
        }
        if ($student_email !== '' && (!filter_var($student_email, FILTER_VALIDATE_EMAIL) || mb_strlen($student_email) > 190)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (!in_array($target_type, ['subject', 'faculty'], true)) {
            $errors[] = 'Please choose whether you are reviewing a subject or a faculty member.';
        }
        if ($rating < 1 || $rating > 5) {
            $errors[] = 'Please give a rating between 1 and 5.';
        }

        $subject_id_db = null;
        $faculty_id_db = null;
        if ($target_type === 'subject') {
            if ($subject_id === '' || !ctype_digit($subject_id)) {
                $errors[] = 'Please select a subject.';
            } else {
                $subject_id_db = (int)$subject_id;
            }
        } elseif ($target_type === 'faculty') {
            if ($faculty_id === '' || !ctype_digit($faculty_id)) {
                $errors[] = 'Please select a faculty member.';
            } else {
                $faculty_id_db = (int)$faculty_id;
            }
        }

        if (!$errors) {
            $stmt = $pdo->prepare(
                'INSERT INTO feedback
                  (student_name, student_id, student_email, department, year_of_study, target_type, subject_id, faculty_id, rating, comments)
                 VALUES
                  (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $student_name,
                $student_id,
                $student_email !== '' ? $student_email : null,
                $department !== '' ? $department : null,
                $year_of_study !== '' ? $year_of_study : null,
                $target_type,
                $subject_id_db,
                $faculty_id_db,
                $rating,
                $comments !== '' ? $comments : null,
            ]);
            $ok = true;

            // Clear POST so refresh doesn't re-submit
            $_POST = [];
        }
    }
}

$rightLinks = [];
if (is_student_logged_in()) {
    $student = current_student();
    $label = $student['name'] ?? $student['student_id'] ?? 'Student';
    $rightLinks[] = '<span class="muted small">Hi, ' . h($label) . '</span>';
    $rightLinks[] = '<a class="pill" href="/student_logout.php">Student Logout</a>';
} else {
    $rightLinks[] = '<a class="pill" href="/student_login.php">Student Login</a>';
}
$rightLinks[] = '<a class="pill" href="/admin/login.php">Admin Login</a>';

render_header('Submit Feedback', [
    'subtitle' => 'Student portal',
    'right' => implode(' ', $rightLinks),
]);
?>

<div class="grid">
  <div class="card">
    <h1>Submit Feedback</h1>
    <div class="muted">Share your ratings and comments to help improve teaching quality.</div>

    <?php if ($ok): ?>
      <div class="alert ok">Thank you! Your feedback has been submitted successfully.</div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="alert danger">
        <strong>Fix the following:</strong>
        <ul>
          <?php foreach ($errors as $e): ?>
            <li><?= h($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" action="">
      <div class="row">
        <div>
          <label for="student_name">Student Name *</label>
          <input id="student_name" name="student_name" value="<?= h(student_prefill($currentStudent, 'name', 'student_name')) ?>" required />
        </div>
        <div>
          <label for="student_id">Student ID *</label>
          <input id="student_id" name="student_id" value="<?= h(student_prefill($currentStudent, 'student_id', 'student_id')) ?>" required />
        </div>
      </div>

      <div class="row">
        <div>
          <label for="student_email">Email (optional)</label>
          <input id="student_email" name="student_email" value="<?= h(student_prefill($currentStudent, 'email', 'student_email')) ?>" />
        </div>
        <div>
          <label for="department">Department (optional)</label>
          <input id="department" name="department" value="<?= h(student_prefill($currentStudent, 'department', 'department')) ?>" />
        </div>
      </div>

      <div class="row">
        <div>
          <label for="year_of_study">Year of Study (optional)</label>
          <input id="year_of_study" name="year_of_study" value="<?= h(student_prefill($currentStudent, 'year_of_study', 'year_of_study')) ?>" placeholder="e.g., 2nd year" />
        </div>
        <div>
          <label for="target_type">Feedback For *</label>
          <select id="target_type" name="target_type" required>
            <option value="">Select…</option>
            <option value="subject" <?= post('target_type') === 'subject' ? 'selected' : '' ?>>Subject</option>
            <option value="faculty" <?= post('target_type') === 'faculty' ? 'selected' : '' ?>>Faculty</option>
          </select>
        </div>
      </div>

      <div class="row">
        <div>
          <label for="subject_id">Subject</label>
          <select id="subject_id" name="subject_id">
            <option value="">Select a subject…</option>
            <?php foreach ($subjects as $s): ?>
              <option value="<?= (int)$s['id'] ?>" <?= post('subject_id') === (string)$s['id'] ? 'selected' : '' ?>>
                <?= h($s['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="muted small">Use this if “Feedback For” = Subject.</div>
        </div>
        <div>
          <label for="faculty_id">Faculty</label>
          <select id="faculty_id" name="faculty_id">
            <option value="">Select a faculty member…</option>
            <?php foreach ($faculty as $f): ?>
              <option value="<?= (int)$f['id'] ?>" <?= post('faculty_id') === (string)$f['id'] ? 'selected' : '' ?>>
                <?= h($f['name']) ?><?= $f['department'] ? ' — ' . h($f['department']) : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="muted small">Use this if “Feedback For” = Faculty.</div>
        </div>
      </div>

      <label for="rating">Rating (1 = Poor, 5 = Excellent) *</label>
      <select id="rating" name="rating" required>
        <option value="">Select…</option>
        <?php for ($i=5; $i>=1; $i--): ?>
          <option value="<?= $i ?>" <?= (int)post('rating') === $i ? 'selected' : '' ?>><?= $i ?></option>
        <?php endfor; ?>
      </select>

      <label for="comments">Comments (optional)</label>
      <textarea id="comments" name="comments" placeholder="Write suggestions or observations..."><?= h(post('comments')) ?></textarea>

      <div class="actions">
        <button class="btn" type="submit">Submit Feedback</button>
        <a class="btn secondary" href="/">Reset</a>
      </div>
    </form>
  </div>

  <div class="card">
    <h2>How it helps</h2>
    <div class="muted">
      - Faster than paper forms<br />
      - Fewer manual errors<br />
      - Easy analysis for management<br />
      - Transparent, trackable records
    </div>
    <div style="margin-top:14px" class="alert">
      <div class="badge">Tip</div>
      <div class="muted" style="margin-top:8px">
        Choose “Subject” or “Faculty” first, then select the corresponding item.
      </div>
    </div>
  </div>
</div>

<?php render_footer(); ?>


