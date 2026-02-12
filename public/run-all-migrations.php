<?php
/**
 * Migration Endpoint Script
 * 
 * This script runs all pending migrations including:
 * - Faculty and Department tables
 * - User faculty/department columns
 * - Course name uppercase migration
 * 
 * Access via: https://quizsnap.ausweblabs.com/run-all-migrations.php?key=QuizSnapMigrations2026&run=yes
 * 
 * SECURITY: Change the key below before deploying!
 */

// Start output buffering to prevent "headers already sent" errors
ob_start();

$secret = 'QuizSnapMigrations2026';
$key = $_GET['key'] ?? '';
$run = $_GET['run'] ?? '';

if ($key !== $secret || $run !== 'yes') {
    http_response_code(403);
    ob_end_flush();
    die('Access denied. Provide correct key and run=yes parameter.');
}

// Set execution time limit
set_time_limit(300);

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/plain; charset=utf-8');
echo "QuizSnap Migration Runner\n";
echo "==========================\n\n";

try {
    // Run migrations
    echo "Running migrations...\n";
    Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    $output = Illuminate\Support\Facades\Artisan::output();
    echo $output . "\n";
    
    // Clear caches
    echo "\nClearing caches...\n";
    Illuminate\Support\Facades\Artisan::call('config:clear');
    Illuminate\Support\Facades\Artisan::call('route:clear');
    Illuminate\Support\Facades\Artisan::call('cache:clear');
    Illuminate\Support\Facades\Artisan::call('view:clear');
    
    echo "\n✅ All migrations completed successfully!\n";
    echo "\nNext steps:\n";
    echo "1. Go to https://quizsnap.ausweblabs.com/dashboard/institutions → Edit an institution\n";
    echo "2. Add faculties and departments for each institution\n";
    echo "3. Examiners can then select their faculty/department from their profile\n";
    
    // Flush output buffer
    ob_end_flush();
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    ob_end_flush();
    http_response_code(500);
}
