<?php
/**
 * ONE-TIME: Fix "local changes would be overwritten by merge" so cPanel Git pull can succeed.
 *
 * Use when you cannot use SSH. This script discards ALL local changes and resets to origin/main,
 * so your working copy matches the remote and future cPanel "Pull" or "Update" will work.
 *
 * 1. Set the secret below (e.g. $secret = 'fixpull2026';).
 * 2. Upload this file to your site's public/ folder via cPanel File Manager
 *    (e.g. public_html/quizsnap/public/fix-git-pull.php if your app lives in public_html/quizsnap).
 * 3. Visit: https://YOUR-SITE.com/fix-git-pull.php?key=YOUR_SECRET&run=yes
 * 4. The script remains available for future use if conflicts occur again.
 */
$secret = 'QuizSnapFixPull2026';

if (($_GET['key'] ?? '') !== $secret) {
    header('HTTP/1.1 403 Forbidden');
    exit('Invalid or missing key.');
}

if (($_GET['run'] ?? '') !== 'yes') {
    header('Content-Type: text/plain; charset=utf-8');
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/fix-git-pull.php';
    exit('Add &run=yes to confirm: ' . $path . '?key=YOUR_SECRET&run=yes');
}

ob_start();

// Laravel app root = parent of public/
$baseDir = dirname(__DIR__);

// cPanel Git path (from your error message)
$git = '/usr/local/cpanel/3rdparty/bin/git';
if (!is_executable($git)) {
    $git = 'git'; // fallback
}

if (!is_dir($baseDir . '/.git')) {
    echo "Not a Git repo: $baseDir\n";
    echo "Make sure this script is in the public/ folder of your Laravel app.\n";
    $body = ob_get_clean();
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Length: ' . (string) strlen($body));
    echo $body;
    exit;
}

chdir($baseDir);

$run = function ($cmd) use ($git) {
    $full = $git . ' ' . $cmd;
    $out = [];
    $code = -1;
    exec($full . ' 2>&1', $out, $code);
    return [implode("\n", $out), $code];
};

echo "Repo root: $baseDir\n";
echo "Git: $git\n\n";

// Note: We'll restore the script after reset, but first discard local changes
// so Git reset can proceed without conflicts
$scriptPath = __FILE__;
$scriptBackup = $scriptPath . '.backup';
$scriptContent = file_get_contents($scriptPath);
file_put_contents($scriptBackup, $scriptContent);
echo "Backed up fix-git-pull.php for restoration after reset.\n\n";

// Fetch latest from remote
list($out, $code) = $run('fetch origin');
echo "git fetch origin (exit $code):\n$out\n\n";

// Discard ALL local changes to specific files that commonly conflict
// Include this script itself so it can be updated from remote
$conflictFiles = [
    'public/fix-git-pull.php',
    'resources/views/layouts/dashboard.blade.php',
    'resources/views/layouts/examiner.blade.php',
    '.env.example',
];
foreach ($conflictFiles as $file) {
    if (file_exists($baseDir . '/' . $file)) {
        list($out, $code) = $run('checkout -- ' . escapeshellarg($file));
        if ($code === 0) {
            echo "Discarded local changes to: $file\n";
        }
    }
}

// Discard ALL local changes and match remote main (fixes "would be overwritten by merge")
list($out, $code) = $run('reset --hard origin/main');
echo "git reset --hard origin/main (exit $code):\n$out\n\n";

if ($code !== 0) {
    // Try master if main doesn't exist
    list($out2, $code2) = $run('reset --hard origin/master');
    echo "git reset --hard origin/master (exit $code2):\n$out2\n\n";
    if ($code2 === 0) {
        $code = 0;
    }
}

if ($code === 0) {
    echo "Done. Local changes were discarded and your files now match the remote.\n";
    echo "You can use cPanel Git \"Pull\" or \"Update\" from now on.\n";
    
    // CRITICAL: Always restore THIS version (the protected one) after reset
    // This ensures the script stays available even if remote version was old/deleted itself
    if (file_exists($scriptBackup)) {
        // Restore the protected version we saved before reset
        file_put_contents($scriptPath, $scriptContent);
        @unlink($scriptBackup);
        echo "\n[fix-git-pull.php has been restored with protection enabled.]\n";
        echo "[The script will remain available for future use.]\n";
        echo "[You can run this script again anytime if conflicts occur.]\n";
    } else {
        echo "\n[Warning: Backup not found. Script may have been deleted by old version.]\n";
        echo "[Re-upload fix-git-pull.php from Git if needed.]\n";
    }
} else {
    echo "Reset failed. Check that the remote branch is 'main' or 'master' and that Git can run on the server.\n";
    // Restore backup if reset failed
    if (file_exists($scriptBackup)) {
        file_put_contents($scriptPath, $scriptContent);
        @unlink($scriptBackup);
        echo "[fix-git-pull.php has been restored.]\n";
    }
}

$body = ob_get_clean();
header('Content-Type: text/plain; charset=utf-8');
header('Content-Length: ' . (string) strlen($body));
echo $body;
