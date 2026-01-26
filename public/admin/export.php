<?php

require_once __DIR__ . '/../../lib/auth.php';

require_admin();

try {
    $pdo = db();
} catch (Throwable $e) {
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
    }
    echo "Export failed.\n\n";
    echo $e->getMessage();
    exit;
}

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
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$stmt = $pdo->prepare("
  SELECT
    f.id,
    f.created_at,
    f.student_name,
    f.student_id,
    f.student_email,
    f.department,
    f.year_of_study,
    f.target_type,
    s.name AS subject_name,
    fc.name AS faculty_name,
    fc.department AS faculty_department,
    f.rating,
    f.comments
  FROM feedback f
  LEFT JOIN subjects s ON s.id = f.subject_id
  LEFT JOIN faculty fc ON fc.id = f.faculty_id
  {$whereSql}
  ORDER BY f.created_at DESC, f.id DESC
");
$stmt->execute($params);

$filename = 'feedback_export_' . gmdate('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fputcsv($out, [
    'id',
    'created_at',
    'student_name',
    'student_id',
    'student_email',
    'department',
    'year_of_study',
    'target_type',
    'subject_name',
    'faculty_name',
    'faculty_department',
    'rating',
    'comments',
]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($out, $row);
}

fclose($out);
exit;


