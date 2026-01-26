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
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}


