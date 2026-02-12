<?php
/**
 * Cleanup Install Script Changes
 * 
 * This script removes files created by install-phpword-v2.php and restores system
 * 
 * SECURITY: Delete this file after use!
 * Access: https://yourdomain.com/cleanup-install-script.php?key=YOUR_SECRET_KEY&run=yes
 */

// SECURITY: Set a secret key
$SECRET_KEY = 'QuizSnapCleanup2026' . date('Ymd');

// Check if key matches
$providedKey = $_GET['key'] ?? '';
$run = $_GET['run'] ?? '';

if ($providedKey !== $SECRET_KEY) {
    http_response_code(403);
    die('Access denied. Provide correct ?key= parameter.');
}

if ($run !== 'yes') {
    die('Add &run=yes to confirm cleanup');
}

$baseDir = dirname(__DIR__);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Cleanup Install Script</title>
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
    <h1>Cleaning Up Install Script Changes</h1>
    <pre>";

// Remove wrapper script created by install-phpword-v2.php
$wrapperScript = $baseDir . '/.composer-wrapper.sh';
if (file_exists($wrapperScript)) {
    if (@unlink($wrapperScript)) {
        echo "✓ Removed wrapper script: .composer-wrapper.sh\n";
    } else {
        echo "✗ Failed to remove wrapper script: .composer-wrapper.sh\n";
        echo "  Manual removal needed: {$wrapperScript}\n";
    }
} else {
    echo "✓ Wrapper script not found (already removed or never created)\n";
}

// Fix file permissions
echo "\n=== Fixing File Permissions ===\n";

$pathsToFix = [
    'storage' => $baseDir . '/storage',
    'bootstrap/cache' => $baseDir . '/bootstrap/cache',
    'public' => __DIR__,
];

foreach ($pathsToFix as $name => $path) {
    if (file_exists($path)) {
        if (is_dir($path)) {
            @chmod($path, 0755);
            echo "✓ Set permissions for {$name} directory\n";
        } else {
            @chmod($path, 0644);
            echo "✓ Set permissions for {$name} file\n";
        }
    }
}

// Fix storage subdirectories
$storageDirs = [
    'storage/app',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    'bootstrap/cache',
];

echo "\n=== Fixing Storage Directories ===\n";
foreach ($storageDirs as $dir) {
    $fullPath = $baseDir . '/' . $dir;
    if (is_dir($fullPath)) {
        @chmod($fullPath, 0755);
        echo "✓ Fixed permissions for {$dir}\n";
    } else {
        @mkdir($fullPath, 0755, true);
        echo "✓ Created {$dir}\n";
    }
}

// Ensure .htaccess exists and is correct
echo "\n=== Checking .htaccess ===\n";
$htaccessPath = __DIR__ . '/.htaccess';
if (!file_exists($htaccessPath)) {
    $htaccessContent = "<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>";
    file_put_contents($htaccessPath, $htaccessContent);
    chmod($htaccessPath, 0644);
    echo "✓ Created .htaccess file\n";
} else {
    echo "✓ .htaccess file exists\n";
    chmod($htaccessPath, 0644);
}

// Clear Laravel cache
echo "\n=== Clearing Laravel Cache ===\n";
$cachePaths = [
    $baseDir . '/bootstrap/cache/config.php',
    $baseDir . '/bootstrap/cache/routes-v7.php',
    $baseDir . '/bootstrap/cache/services.php',
    $baseDir . '/storage/framework/cache',
    $baseDir . '/storage/framework/views',
];

foreach ($cachePaths as $cachePath) {
    if (file_exists($cachePath)) {
        if (is_dir($cachePath)) {
            // Clear directory contents
            $files = glob($cachePath . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
            echo "✓ Cleared cache directory: " . basename($cachePath) . "\n";
        } else {
            @unlink($cachePath);
            echo "✓ Removed cache file: " . basename($cachePath) . "\n";
        }
    }
}

echo "\n=== Cleanup Complete ===\n";
echo "\n<p class='success'><strong>✓ Cleanup finished!</strong></p>";
echo "<p class='info'>Try accessing your site now. If issues persist:</p>";
echo "<ol>";
echo "<li>Check file permissions via cPanel File Manager</li>";
echo "<li>Ensure storage/ and bootstrap/cache/ are writable (755)</li>";
echo "<li>Check .htaccess file exists in public/ directory</li>";
echo "<li>Contact hosting provider if 403 errors continue</li>";
echo "</ol>";

echo "</pre></body></html>";
