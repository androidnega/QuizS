<?php
/**
 * Migration Endpoint Script
 * 
 * This script runs all pending migrations including:
 * - Faculty and Department tables
 * - User faculty/department columns
 * - Course name uppercase migration
 * 
 * Access via: https://quizsnap.ausweblabs.com/migrate-all.php?key=QuizSnapMigrations2026&run=yes
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

// Disable Laravel's error handler to prevent Ignition from intercepting errors
app()->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    function () {
        return new class implements Illuminate\Contracts\Debug\ExceptionHandler {
            public function report(\Throwable $e) {}
            public function render($request, \Throwable $e) {
                throw $e; // Re-throw so our catch block can handle it
            }
            public function renderForConsole($output, \Throwable $e) {
                throw $e;
            }
            public function shouldReport(\Throwable $e) {
                return false;
            }
        };
    }
);

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
    // Clean any buffered output (including potential HTML from Ignition)
    ob_end_clean();
    
    // Set error response code before outputting anything
    http_response_code(500);
    
    // Output plain text error
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
