<?php
/**
 * ONE-TIME: Run migrations on the server by visiting this URL.
 *
 * 1. Change the secret below to a random string (e.g. "a8f3k2m9x") if desired.
 * 2. Deploy so public/run-migrate.php exists on the server (e.g. git pull).
 * 3. Visit: https://quizsnap.ausweblabs.com/run-migrate.php?key=YOUR_SECRET&run=yes
 * 4. The script runs migrations, then DELETES ITSELF. Visit only once.
 *
 * Keep this file in the repo until you have run it on the server; then remove or leave for future runs.
 */
$secret = 'QuizSnap2026Xk9m2p7';

if (($_GET['key'] ?? '') !== $secret) {
    header('HTTP/1.1 403 Forbidden');
    exit('Invalid or missing key.');
}

// Prevent running in production without explicit intent
if (($_GET['run'] ?? '') !== 'yes') {
    header('Content-Type: text/plain; charset=utf-8');
    exit('Add &run=yes to the URL to confirm: ' . parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) . '?key=YOUR_SECRET&run=yes');
}

define('LARAVEL_START', microtime(true));

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

header('Content-Type: text/plain; charset=utf-8');

try {
    Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    $output = Illuminate\Support\Facades\Artisan::output();
    echo "Migrations completed.\n\n";
    echo $output;

    // Delete this file after successful run (one-time use)
    if (@unlink(__FILE__)) {
        echo "\n[run-migrate.php has been deleted. Do not visit again.]\n";
    } else {
        echo "\n[Delete public/run-migrate.php manually for security.]\n";
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
