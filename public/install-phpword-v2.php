<?php
/**
 * PhpWord Installation Script v2
 * 
 * SECURITY: Delete this file after use!
 * Access: https://yourdomain.com/install-phpword-v2.php?key=YOUR_SECRET_KEY
 */

// SECURITY: Set a secret key
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

// Determine composer command
$composerCmd = 'composer';
if (file_exists($composerPath)) {
    $composerCmd = 'php ' . escapeshellarg($composerPath);
} else {
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

// Determine HOME directory
$username = get_current_user();
$homeDir = getenv('HOME');
if (empty($homeDir)) {
    $possibleHomes = [
        '/home2/' . $username,
        '/home/' . $username,
        $baseDir . '/.composer',
        sys_get_temp_dir() . '/composer-home-' . $username,
    ];
    foreach ($possibleHomes as $possibleHome) {
        $parentDir = dirname($possibleHome);
        if (is_dir($parentDir) || @mkdir($parentDir, 0755, true)) {
            if (!is_dir($possibleHome)) {
                @mkdir($possibleHome, 0755, true);
            }
            if (is_dir($possibleHome)) {
                $homeDir = $possibleHome;
                break;
            }
        }
    }
    if (empty($homeDir)) {
        $homeDir = sys_get_temp_dir();
    }
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Install PhpWord v2</title>
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
    <h1>Installing PhpWord Library (v2)</h1>
    <p class='info'>Base directory: <strong>{$baseDir}</strong></p>
    <p class='info'>Composer command: <strong>{$composerCmd}</strong></p>
    <p class='info'>HOME directory: <strong>{$homeDir}</strong></p>
    <hr>
    <pre>";

// Change to base directory
chdir($baseDir);

// Create a wrapper shell script that sets environment variables
$wrapperScript = $baseDir . '/.composer-wrapper.sh';
$wrapperContent = "#!/bin/bash
export HOME=" . escapeshellarg($homeDir) . "
export COMPOSER_HOME=" . escapeshellarg($homeDir) . "
cd " . escapeshellarg($baseDir) . "
{$composerCmd} \"\$@\"
";
file_put_contents($wrapperScript, $wrapperContent);
chmod($wrapperScript, 0755);

echo "Created wrapper script: {$wrapperScript}\n";
echo str_repeat('=', 70) . "\n\n";

// First, try composer install
echo "Step 1: Running composer install...\n";
echo str_repeat('=', 70) . "\n\n";

$output1 = [];
$returnVar1 = 0;
$fullCommand = escapeshellarg($wrapperScript) . " install --no-dev --optimize-autoloader 2>&1";
exec($fullCommand, $output1, $returnVar1);

foreach ($output1 as $line) {
    echo htmlspecialchars($line) . "\n";
}

echo "\n" . str_repeat('=', 70) . "\n\n";

// Check if PhpWord is installed
$vendorPath = $baseDir . '/vendor/phpoffice/phpword';
if (file_exists($vendorPath)) {
    echo "✓ PhpWord already installed!\n";
    $finalReturnVar = 0;
} elseif ($returnVar1 === 0) {
    // Composer install succeeded but PhpWord not found, try require
    echo "Step 2: PhpWord not found, running composer require phpoffice/phpword...\n";
    echo str_repeat('=', 70) . "\n\n";
    
    $output = [];
    $returnVar = 0;
    $fullCommand = escapeshellarg($wrapperScript) . " require phpoffice/phpword --no-interaction --no-scripts 2>&1";
    exec($fullCommand, $output, $returnVar);
    
    foreach ($output as $line) {
        echo htmlspecialchars($line) . "\n";
    }
    
    echo "\n" . str_repeat('=', 70) . "\n";
    $finalReturnVar = $returnVar;
} else {
    // Composer install failed, try require anyway
    echo "\n<p class='error'>Composer install failed. Trying composer require directly...</p>\n";
    echo str_repeat('=', 70) . "\n\n";
    
    $output = [];
    $returnVar = 0;
    $fullCommand = escapeshellarg($wrapperScript) . " require phpoffice/phpword --no-interaction --no-scripts 2>&1";
    exec($fullCommand, $output, $returnVar);
    
    foreach ($output as $line) {
        echo htmlspecialchars($line) . "\n";
    }
    
    echo "\n" . str_repeat('=', 70) . "\n";
    $finalReturnVar = $returnVar;
}

if ($finalReturnVar === 0) {
    echo "\n<p class='success'><strong>✓ SUCCESS!</strong> PhpWord installed successfully.</p>\n";
    
    // Verify installation
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
    
    echo "<p class='info'><strong>IMPORTANT:</strong> Delete this file (install-phpword-v2.php) and the wrapper script ({$wrapperScript}) now for security!</p>\n";
} else {
    echo "\n<p class='error'><strong>✗ ERROR:</strong> Composer command failed with exit code {$finalReturnVar}</p>\n";
    echo "<p class='info'>Check the output above for details. The wrapper script is at: {$wrapperScript}</p>\n";
}

// Clean up wrapper script
if (file_exists($wrapperScript)) {
    // Don't delete yet - user might want to check it
    echo "\n<p class='info'>Wrapper script created at: {$wrapperScript}</p>\n";
}

echo "</pre></body></html>";
