<?php
// Copy this file to config.php and update values for your environment.
// Never commit real credentials.

return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'college_feedback',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        // Change this to a random long string in production.
        'session_name' => 'cfs_session',
    ],
];


