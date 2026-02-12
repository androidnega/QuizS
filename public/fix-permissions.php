<?php
/**
 * Fix System-Wide 403 Errors
 * 
 * This script checks and fixes common issues that cause 403 errors:
 * - File permissions
 * - Directory permissions
 * - .htaccess file
 * - Laravel bootstrap
 * 
 * SECURITY: Delete this file after use!
 * Access: https://yourdomain.com/fix-permissions.php?key=YOUR_SECRET_KEY
 */

// SECURITY: Set a secret key
$SECRET_KEY = 'QuizSnapFixPerms2026' . date('Ymd');

// Check if key matches
$providedKey = $_GET['key'] ?? '';
if ($providedKey !== $SECRET_KEY) {
    http_response_code(403);
    die('Access denied. Provide correct ?key= parameter.');
}

$baseDir = dirname(__DIR__);
$publicDir = __DIR__;

echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix System Permissions</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #0f0; }
        .success { color: #0f0; }
        .error { color: #f00; }
        .info { color: #0ff; }
        .warning { color: #ff0; }
        pre { background: #000; padding: 10px; border: 1px solid #333; overflow-x: auto; }
        h1 { color: #fff; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #333; }
    </style>
</head>
<body>
    <h1>System Permission Diagnostic & Fix</h1>";

// Check .htaccess
echo "<div class='section'>";
echo "<h2>1. Checking .htaccess file</h2>";
$htaccessPath = $publicDir . '/.htaccess';
if (file_exists($htaccessPath)) {
    echo "<p class='success'>✓ .htaccess exists</p>";
    $htaccessContent = file_get_contents($htaccessPath);
    if (strpos($htaccessContent, 'RewriteEngine On') !== false) {
        echo "<p class='success'>✓ RewriteEngine is enabled</p>";
    } else {
        echo "<p class='error'>✗ RewriteEngine not found in .htaccess</p>";
    }
    echo "<pre>" . htmlspecialchars(substr($htaccessContent, 0, 500)) . "</pre>";
} else {
    echo "<p class='error'>✗ .htaccess file missing!</p>";
    // Create basic .htaccess
    $basicHtaccess = "<IfModule mod_rewrite.c>
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
    
    if (isset($_GET['create_htaccess']) && $_GET['create_htaccess'] === 'yes') {
        file_put_contents($htaccessPath, $basicHtaccess);
        chmod($htaccessPath, 0644);
        echo "<p class='success'>✓ Created .htaccess file</p>";
    } else {
        echo "<p class='info'>To create .htaccess, add: &create_htaccess=yes</p>";
    }
}
echo "</div>";

// Check file permissions
echo "<div class='section'>";
echo "<h2>2. Checking File Permissions</h2>";

$criticalPaths = [
    'storage' => $baseDir . '/storage',
    'bootstrap/cache' => $baseDir . '/bootstrap/cache',
    'public' => $publicDir,
    '.htaccess' => $htaccessPath,
];

foreach ($criticalPaths as $name => $path) {
    if (file_exists($path)) {
        $perms = fileperms($path);
        $readable = is_readable($path);
        $writable = is_writable($path);
        
        echo "<p>";
        echo "<strong>{$name}:</strong> ";
        echo "perms: " . substr(sprintf('%o', $perms), -4) . " ";
        echo $readable ? '<span class="success">readable</span>' : '<span class="error">NOT readable</span>';
        echo " ";
        echo $writable ? '<span class="success">writable</span>' : '<span class="error">NOT writable</span>';
        echo "</p>";
        
        // Fix permissions if needed
        if (isset($_GET['fix_perms']) && $_GET['fix_perms'] === 'yes') {
            if (is_dir($path)) {
                chmod($path, 0755);
                echo "<p class='success'>✓ Fixed directory permissions for {$name}</p>";
            } else {
                chmod($path, 0644);
                echo "<p class='success'>✓ Fixed file permissions for {$name}</p>";
            }
        }
    } else {
        echo "<p class='error'>✗ {$name} does not exist at: {$path}</p>";
    }
}
echo "<p class='info'>To fix permissions, add: &fix_perms=yes</p>";
echo "</div>";

// Check storage directories
echo "<div class='section'>";
echo "<h2>3. Checking Storage Directories</h2>";
$storageDirs = [
    'storage/app',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    'bootstrap/cache',
];

foreach ($storageDirs as $dir) {
    $fullPath = $baseDir . '/' . $dir;
    if (!is_dir($fullPath)) {
        echo "<p class='error'>✗ {$dir} does not exist</p>";
        if (isset($_GET['create_dirs']) && $_GET['create_dirs'] === 'yes') {
            @mkdir($fullPath, 0755, true);
            echo "<p class='success'>✓ Created {$dir}</p>";
        }
    } else {
        $writable = is_writable($fullPath);
        echo "<p>";
        echo $writable ? '<span class="success">✓</span>' : '<span class="error">✗</span>';
        echo " {$dir} - " . ($writable ? 'writable' : 'NOT writable');
        echo "</p>";
        
        if (!$writable && isset($_GET['fix_perms']) && $_GET['fix_perms'] === 'yes') {
            chmod($fullPath, 0755);
            echo "<p class='success'>✓ Fixed permissions for {$dir}</p>";
        }
    }
}
echo "<p class='info'>To create missing directories, add: &create_dirs=yes</p>";
echo "</div>";

// Check Laravel bootstrap
echo "<div class='section'>";
echo "<h2>4. Checking Laravel Bootstrap</h2>";
$bootstrapFile = $baseDir . '/bootstrap/app.php';
if (file_exists($bootstrapFile)) {
    echo "<p class='success'>✓ bootstrap/app.php exists</p>";
    if (is_readable($bootstrapFile)) {
        echo "<p class='success'>✓ bootstrap/app.php is readable</p>";
    } else {
        echo "<p class='error'>✗ bootstrap/app.php is NOT readable</p>";
    }
} else {
    echo "<p class='error'>✗ bootstrap/app.php missing!</p>";
}

$envFile = $baseDir . '/.env';
if (file_exists($envFile)) {
    echo "<p class='success'>✓ .env file exists</p>";
} else {
    echo "<p class='warning'>⚠ .env file missing (may be normal if using .env.example)</p>";
}
echo "</div>";

// Check PHP configuration
echo "<div class='section'>";
echo "<h2>5. PHP Configuration</h2>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Server API: " . php_sapi_name() . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "</p>";
echo "<p>Current User: " . get_current_user() . "</p>";
echo "</div>";

// Quick fix all
if (isset($_GET['fix_all']) && $_GET['fix_all'] === 'yes') {
    echo "<div class='section'>";
    echo "<h2>6. Applying All Fixes</h2>";
    
    // Fix storage permissions
    foreach ($storageDirs as $dir) {
        $fullPath = $baseDir . '/' . $dir;
        if (!is_dir($fullPath)) {
            @mkdir($fullPath, 0755, true);
            echo "<p class='success'>✓ Created {$dir}</p>";
        }
        @chmod($fullPath, 0755);
    }
    
    // Fix critical paths
    foreach ($criticalPaths as $name => $path) {
        if (file_exists($path)) {
            if (is_dir($path)) {
                @chmod($path, 0755);
            } else {
                @chmod($path, 0644);
            }
        }
    }
    
    // Ensure .htaccess exists
    if (!file_exists($htaccessPath)) {
        $basicHtaccess = "<IfModule mod_rewrite.c>
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
        file_put_contents($htaccessPath, $basicHtaccess);
        chmod($htaccessPath, 0644);
        echo "<p class='success'>✓ Created .htaccess</p>";
    }
    
    echo "<p class='success'><strong>✓ All fixes applied!</strong></p>";
    echo "<p class='info'>Try accessing your site now. If issues persist, check with your hosting provider.</p>";
    echo "</div>";
} else {
    echo "<div class='section'>";
    echo "<h2>6. Quick Fix All</h2>";
    echo "<p class='info'>To apply all fixes at once, add: &fix_all=yes</p>";
    echo "<p class='warning'>This will attempt to fix permissions and create missing directories.</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p class='info'><strong>Usage:</strong></p>";
echo "<pre>";
echo "?key={$SECRET_KEY}&fix_all=yes\n";
echo "?key={$SECRET_KEY}&fix_perms=yes\n";
echo "?key={$SECRET_KEY}&create_dirs=yes\n";
echo "?key={$SECRET_KEY}&create_htaccess=yes\n";
echo "</pre>";

echo "</body></html>";
