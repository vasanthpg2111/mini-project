<?php

require_once __DIR__ . '/../lib/auth.php';

start_app_session();
student_logout();

header('Location: /');
exit;


