<?php

// Prepare writable SQLite database in /tmp for serverless environment if sqlite is used
if (getenv('DB_CONNECTION') === 'sqlite' || !getenv('DB_CONNECTION')) {
    $tmpDb = '/tmp/database.sqlite';
    if (!file_exists($tmpDb) && file_exists(__DIR__ . '/../database/database.sqlite')) {
        copy(__DIR__ . '/../database/database.sqlite', $tmpDb);
    }
    putenv("DB_DATABASE={$tmpDb}");
    $_ENV['DB_DATABASE'] = $tmpDb;
    $_SERVER['DB_DATABASE'] = $tmpDb;
}

// Ensure necessary Laravel directories exist in writable /tmp
$dirs = ['/tmp/views', '/tmp/cache', '/tmp/sessions'];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Forward Vercel serverless requests to Laravel public/index.php
require __DIR__ . '/../public/index.php';
