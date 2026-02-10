<?php
/**
 * Cache clearing utility - access via browser.
 * Visit: https://quizsnap.ausweblabs.com/clear-cache.php
 * 
 * IMPORTANT: Delete this file after use for security.
 */

define('LARAVEL_START', microtime(true));

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/plain; charset=utf-8');

echo "QuizSnap Cache Clearing\n";
echo "=======================\n\n";

try {
    echo "Clearing view cache...\n";
    Artisan::call('view:clear');
    echo "✓ View cache cleared\n\n";

    echo "Clearing application cache...\n";
    Artisan::call('cache:clear');
    echo "✓ Application cache cleared\n\n";

    echo "Clearing config cache...\n";
    Artisan::call('config:clear');
    echo "✓ Config cache cleared\n\n";

    echo "Clearing route cache...\n";
    Artisan::call('route:clear');
    echo "✓ Route cache cleared\n\n";

    echo "Clearing compiled views and cache...\n";
    Artisan::call('optimize:clear');
    echo "✓ All optimizations cleared\n\n";

    echo "=======================\n";
    echo "SUCCESS: All caches cleared!\n\n";
    echo "Refresh your dashboard page now.\n\n";
    echo "⚠️  IMPORTANT: Delete this file (public/clear-cache.php) for security.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString();
}
