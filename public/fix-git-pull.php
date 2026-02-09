<?php
/**
 * ONE-TIME: Fix "local changes would be overwritten by merge" so cPanel Git pull can succeed.
 *
 * Use when you cannot run terminal on the server. This script discards local changes to .env.example
 * and runs git pull, so the next cPanel "Update" will work.
 *
 * 1. Set the secret below (e.g. $secret = 'fixpull123';).
 * 2. Deploy this file to public/ (push to Git; pull on server if you can, or upload via File Manager).
 * 3. Visit: https://quizsnap.ausweblabs.com/fix-git-pull.php?key=YOUR_SECRET&run=yes
 * 4. The script deletes itself after running. Visit only once.
 */
$secret = 'CHANGE_ME_BEFORE_UPLOAD';

if (($_GET['key'] ?? '') !== $secret) {
    header('HTTP/1.1 403 Forbidden');
    exit('Invalid or missing key.');
}

if (($_GET['run'] ?? '') !== 'yes') {
    header('Content-Type: text/plain; charset=utf-8');
    exit('Add &run=yes to confirm: ' . parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) . '?key=YOUR_SECRET&run=yes');
}

header('Content-Type: text/plain; charset=utf-8');

$baseDir = dirname(__DIR__);
$git = '/usr/local/cpanel/3rdparty/bin/git';

if (!is_dir($baseDir . '/.git')) {
    exit('Not a Git repo: ' . $baseDir);
}

chdir($baseDir);

$out = [];
$code = 0;

// Discard local changes to .env.example so pull can merge
exec(sprintf('%s checkout -- .env.example 2>&1', escapeshellcmd($git)), $out, $code);
echo "git checkout -- .env.example (exit $code):\n" . implode("\n", $out) . "\n\n";

$out = [];
$code = 0;
exec(sprintf('%s pull 2>&1', escapeshellcmd($git)), $out, $code);
echo "git pull (exit $code):\n" . implode("\n", $out) . "\n";

if ($code === 0) {
    echo "\nPull succeeded. You can use cPanel Git Update normally from now on.\n";
} else {
    echo "\nIf pull still fails, run this script again or fix conflicts manually.\n";
}

if (@unlink(__FILE__)) {
    echo "\n[fix-git-pull.php has been deleted.]\n";
} else {
    echo "\n[Delete public/fix-git-pull.php manually.]\n";
}
