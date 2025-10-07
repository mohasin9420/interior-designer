<?php
declare(strict_types=1);

// Bootstrap configuration, sessions, timezone
$settings = require __DIR__ . '/../config/settings.php';

if (!headers_sent()) {
    session_name($settings['app']['session_name']);
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

date_default_timezone_set($settings['app']['timezone']);

require_once __DIR__ . '/../config/database.php';

function base_url(string $path = ''): string {
    $settings = require __DIR__ . '/../config/settings.php';
    $base = rtrim($settings['app']['base_url'], '/');
    $path = ltrim($path, '/');
    return $path ? $base . '/' . $path : $base;
}
