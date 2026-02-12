<?php
/**
 * One-time script to install PhpWord library on live server
 * 
 * SECURITY: Delete this file after use!
 * Access: https://yourdomain.com/install-phpword.php?key=YOUR_SECRET_KEY
 */

// SECURITY: Set a secret key - change this to something random!
// IMPORTANT: Change this to a strong random string before using!
$SECRET_KEY = 'QuizSnapPhpWord2026' . date('Ymd');

// Check if key matches
$providedKey = $_GET['key'] ?? '';
if ($providedKey !== $SECRET_KEY) {
    http_response_code(403);
    die('Access denied. Provide correct ?key= parameter.');
}

// Get the base directory (parent of public)
$baseDir = dirname(__DIR__);
$composerPath = $baseDir . '/composer.phar';
$composerJson = $baseDir . '/composer.json';

// Check if composer.json exists
if (!file_exists($composerJson)) {
    die('Error: composer.json not found at: ' . $composerJson);
}

// Check if composer.phar exists, if not try to find composer command
$composerCmd = 'composer';
if (file_exists($composerPath)) {
    $composerCmd = 'php ' . escapeshellarg($composerPath);
} else {
    // Try to find composer in common locations
    $possiblePaths = [
        '/usr/local/bin/composer',
        '/usr/bin/composer',
        '/opt/cpanel/composer/bin/composer',
    ];
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $composerCmd = escapeshellarg($path);
            break;
        }
    }
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Install PhpWord</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #0f0; }
        .success { color: #0f0; }
        .error { color: #f00; }
        .info { color: #0ff; }
        pre { background: #000; padding: 10px; border: 1px solid #333; overflow-x: auto; }
        h1 { color: #fff; }
    </style>
</head>
<body>
    <h1>Installing PhpWord Library</h1>
    <p class='info'>Base directory: <strong>{$baseDir}</strong></p>
    <p class='info'>Composer command: <strong>{$composerCmd}</strong></p>
    <hr>
    <pre>";

// Change to base directory
chdir($baseDir);

// First, try composer install to ensure all dependencies are installed
echo "Step 1: Running composer install to ensure all dependencies are up to date...\n";
echo str_repeat('=', 70) . "\n\n";

$output1 = [];
$returnVar1 = 0;
exec("{$composerCmd} install --no-dev --optimize-autoloader 2>&1", $output1, $returnVar1);

foreach ($output1 as $line) {
    echo htmlspecialchars($line) . "\n";
}

echo "\n" . str_repeat('=', 70) . "\n\n";

// If composer install worked or phpoffice/phpword is already in composer.json, we're done
// Otherwise, run composer require
if ($returnVar1 === 0) {
    // Check if phpoffice/phpword is already installed
    $vendorPath = $baseDir . '/vendor/phpoffice/phpword';
    if (!file_exists($vendorPath)) {
        echo "Step 2: PhpWord not found, running composer require phpoffice/phpword...\n";
        echo str_repeat('=', 70) . "\n\n";
        
        $output = [];
        $returnVar = 0;
        exec("{$composerCmd} require phpoffice/phpword --no-interaction --no-scripts 2>&1", $output, $returnVar);
        
        foreach ($output as $line) {
            echo htmlspecialchars($line) . "\n";
        }
        
        echo "\n" . str_repeat('=', 70) . "\n";
        $finalReturnVar = $returnVar;
    } else {
        echo "✓ PhpWord already installed!\n";
        $finalReturnVar = 0;
    }
} else {
    echo "\n<p class='error'>Composer install failed. Trying composer require directly...</p>\n";
    echo str_repeat('=', 70) . "\n\n";
    
    $output = [];
    $returnVar = 0;
    exec("{$composerCmd} require phpoffice/phpword --no-interaction --no-scripts 2>&1", $output, $returnVar);
    
    foreach ($output as $line) {
        echo htmlspecialchars($line) . "\n";
    }
    
    echo "\n" . str_repeat('=', 70) . "\n";
    $finalReturnVar = $returnVar;
}

foreach ($output as $line) {
    echo htmlspecialchars($line) . "\n";
}

echo "\n" . str_repeat('=', 70) . "\n";

if ($finalReturnVar === 0) {
    echo "\n<p class='success'><strong>✓ SUCCESS!</strong> PhpWord installed successfully.</p>\n";
    
    // Verify installation
    $vendorPath = $baseDir . '/vendor/phpoffice/phpword';
    if (file_exists($vendorPath)) {
        echo "<p class='success'>✓ Verified: PhpWord directory exists at: {$vendorPath}</p>\n";
    } else {
        echo "<p class='error'>⚠ Warning: PhpWord directory not found at expected location.</p>\n";
    }
    
    // Check if class can be loaded
    require_once $baseDir . '/vendor/autoload.php';
    if (class_exists('PhpOffice\PhpWord\PhpWord')) {
        echo "<p class='success'>✓ Verified: PhpWord class can be loaded!</p>\n";
    } else {
        echo "<p class='error'>⚠ Warning: PhpWord class cannot be loaded. Check autoload.</p>\n";
    }
    
    echo "<p class='info'><strong>IMPORTANT:</strong> Delete this file (install-phpword.php) now for security!</p>\n";
} else {
    echo "\n<p class='error'><strong>✗ ERROR:</strong> Composer command failed with exit code {$returnVar}</p>\n";
    echo "<p class='info'>Try running composer manually or check server logs.</p>\n";
}

echo "</pre></body></html>";
