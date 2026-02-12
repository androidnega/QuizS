<?php
/**
 * TTU Faculties and Departments Seeder Script
 * 
 * This script seeds faculties and departments for Takoradi Technical University (TTU)
 * Auto-deletes after successful execution.
 * 
 * Access via: https://quizsnap.ausweblabs.com/seed-ttu-faculties-depts.php?key=QuizSnapMigrations2026&run=yes
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

// Store script path for deletion
$scriptPath = __FILE__;

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/plain; charset=utf-8');
echo "TTU Faculties and Departments Seeder\n";
echo "=====================================\n\n";

try {
    // Run the seeder directly (bypasses production mode restrictions)
    echo "Seeding TTU faculties and departments...\n\n";
    
    // Check if institution exists first
    $institution = \App\Models\Institution::where('name', 'Takoradi Technical University')->first();
    if (!$institution) {
        throw new \Exception('Takoradi Technical University not found. Please ensure the institution exists in the database.');
    }
    
    echo "Found institution: {$institution->name} (ID: {$institution->id})\n\n";
    
    $seeder = new \Database\Seeders\TtuFacultiesDepartmentsSeeder();
    
    // Create a simple command output handler
    $outputHandler = new class {
        public function info($message) {
            echo $message . "\n";
        }
        
        public function line($message) {
            echo $message . "\n";
        }
        
        public function error($message) {
            echo "ERROR: " . $message . "\n";
        }
    };
    
    // Use reflection to set the command property if it exists
    $reflection = new ReflectionClass($seeder);
    if ($reflection->hasProperty('command')) {
        $property = $reflection->getProperty('command');
        $property->setAccessible(true);
        $property->setValue($seeder, $outputHandler);
    }
    
    $seeder->run();
    
    // Verify the data was created
    $facultyCount = \App\Models\Faculty::where('institution_id', $institution->id)->count();
    $deptCount = \App\Models\Department::whereHas('faculty', function($q) use ($institution) {
        $q->where('institution_id', $institution->id);
    })->count();
    
    echo "\n📊 Verification:\n";
    echo "   Faculties created: {$facultyCount}\n";
    echo "   Departments created: {$deptCount}\n";
    echo "\n✅ Seeding completed successfully!\n";
    
    // Flush output buffer
    ob_end_flush();
    
    // Auto-delete this script after successful execution
    if (file_exists($scriptPath)) {
        @unlink($scriptPath);
        echo "\n🗑️  Script auto-deleted.\n";
    }
    
} catch (Exception $e) {
    // Clean any buffered output
    ob_end_clean();
    
    // Set error response code
    http_response_code(500);
    
    // Output plain text error
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    
    // Don't delete on error - let user see the error and retry
}
