<?php
/**
 * TTU Faculties and Departments Seeder Script
 * 
 * This script seeds faculties and departments for Takoradi Technical University (TTU)
 * 
 * Access via: https://quizsnap.ausweblabs.com/seed-ttu-data.php?key=QuizSnapMigrations2026&run=yes
 * 
 * SECURITY: Uses the same key as migration script
 */

// Start output buffering
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
echo "TTU Faculties and Departments Seeder\n";
echo "=====================================\n\n";

try {
    // Run the seeder using Artisan
    echo "Seeding TTU faculties and departments...\n\n";
    
    Illuminate\Support\Facades\Artisan::call('db:seed', [
        '--class' => 'TtuFacultiesDepartmentsSeeder'
    ]);
    
    $output = Illuminate\Support\Facades\Artisan::output();
    echo $output . "\n";
    
    echo "\n✅ Seeding completed successfully!\n";
    
    // Flush output buffer
    ob_end_flush();
    
} catch (Exception $e) {
    // Clean any buffered output
    ob_end_clean();
    
    // Set error response code
    http_response_code(500);
    
    // Output plain text error
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
