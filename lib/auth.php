<?php

require_once __DIR__ . '/db.php';

function start_app_session(): void
{
    $cfg = get_config();
    $sessionName = $cfg['app']['session_name'] ?? 'cfs_session';
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name($sessionName);
        session_start();
    }
}

function is_admin_logged_in(): bool
{
    start_app_session();
    return !empty($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!is_admin_logged_in()) {
        header('Location: /admin/login.php');
        exit;
    }
}

function admin_login(string $username, string $password): bool
{
    start_app_session();

    $stmt = db()->prepare('SELECT id, username, password_hash FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    if (!$admin) {
        return false;
    }

    if (!password_verify($password, $admin['password_hash'])) {
        return false;
    }

    $_SESSION['admin_id'] = (int)$admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    return true;
}

function admin_logout(): void
{
    start_app_session();
    unset(
        $_SESSION['admin_id'],
        $_SESSION['admin_username'],
        $_SESSION['pending_admin_id'],
        $_SESSION['pending_admin_username'],
        $_SESSION['pending_admin_email'],
        $_SESSION['admin_otp_attempts']
    );
}

function is_student_logged_in(): bool
{
    start_app_session();
    return !empty($_SESSION['student']) && !empty($_SESSION['student']['id']);
}

function current_student(): ?array
{
    start_app_session();
    return $_SESSION['student'] ?? null;
}

function require_student(): void
{
    if (!is_student_logged_in()) {
        header('Location: /student_login.php');
        exit;
    }
}

function student_login(string $studentId, string $password): bool
{
    start_app_session();

    $stmt = db()->prepare(
        'SELECT id, student_id, name, email, department, year_of_study, password_hash
         FROM students
         WHERE student_id = ?
         LIMIT 1'
    );
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();
    if (!$student) {
        return false;
    }

    if (!password_verify($password, $student['password_hash'])) {
        return false;
    }

    $_SESSION['student'] = [
        'id' => (int)$student['id'],
        'student_id' => $student['student_id'],
        'name' => $student['name'],
        'email' => $student['email'],
        'department' => $student['department'],
        'year_of_study' => $student['year_of_study'],
    ];

    return true;
}

function student_logout(): void
{
    start_app_session();
    unset($_SESSION['student']);
}


