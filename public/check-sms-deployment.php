<?php
/**
 * Check if SMS allocation system is properly deployed.
 * Visit: https://YOUR-DOMAIN.com/check-sms-deployment.php
 */
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

header('Content-Type: text/plain; charset=utf-8');

echo "SMS Allocation System - Deployment Check\n";
echo "========================================\n\n";

$checks = [];
$allPassed = true;

// Check 1: Database columns exist
try {
    $hasAllocation = \Illuminate\Support\Facades\Schema::hasColumn('users', 'sms_allocation');
    $hasUsed = \Illuminate\Support\Facades\Schema::hasColumn('users', 'sms_used');
    $checks['Database columns'] = $hasAllocation && $hasUsed;
    if (!$checks['Database columns']) {
        echo "❌ Database columns missing. Run migration: /run-migrate.php?key=QuizSnap2026Xk9m2p7&run=yes\n";
        $allPassed = false;
    } else {
        echo "✅ Database columns exist (sms_allocation, sms_used)\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking database: " . $e->getMessage() . "\n";
    $allPassed = false;
}

// Check 2: User model has SMS methods
try {
    $user = new \App\Models\User();
    $reflection = new ReflectionClass($user);
    $hasRemaining = $reflection->hasMethod('getSmsRemainingAttribute');
    $checks['User model methods'] = $hasRemaining;
    if (!$checks['User model methods']) {
        echo "❌ User model missing SMS methods. Pull latest code from Git.\n";
        $allPassed = false;
    } else {
        echo "✅ User model has SMS methods\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking User model: " . $e->getMessage() . "\n";
    $allPassed = false;
}

// Check 3: Config file has OTP TTL
try {
    $otpTtl = config('quizsnap.otp_ttl_seconds');
    $checks['Config OTP TTL'] = $otpTtl !== null && $otpTtl > 0;
    if (!$checks['Config OTP TTL']) {
        echo "❌ Config missing OTP TTL. Pull latest code from Git.\n";
        $allPassed = false;
    } else {
        echo "✅ Config has OTP TTL: " . ($otpTtl / 86400) . " days\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking config: " . $e->getMessage() . "\n";
    $allPassed = false;
}

// Check 4: View files exist
$viewFiles = [
    'resources/views/layouts/examiner.blade.php',
    'resources/views/layouts/dashboard.blade.php',
    'resources/views/admin/users/edit.blade.php',
];
$missingViews = [];
foreach ($viewFiles as $file) {
    if (!file_exists(__DIR__ . '/../' . $file)) {
        $missingViews[] = $file;
    }
}
$checks['View files'] = empty($missingViews);
if (!$checks['View files']) {
    echo "❌ Missing view files: " . implode(', ', $missingViews) . "\n";
    echo "   Pull latest code from Git.\n";
    $allPassed = false;
} else {
    echo "✅ All view files exist\n";
}

// Check 5: Migration file exists
$migrationFile = 'database/migrations/2026_02_12_000001_add_sms_allocation_to_users_table.php';
$checks['Migration file'] = file_exists(__DIR__ . '/../' . $migrationFile);
if (!$checks['Migration file']) {
    echo "❌ Migration file missing. Pull latest code from Git.\n";
    $allPassed = false;
} else {
    echo "✅ Migration file exists\n";
}

// Check 6: Sample examiner data
try {
    $examiner = \App\Models\User::where('role', 'examiner')->first();
    if ($examiner) {
        $allocation = $examiner->sms_allocation ?? 0;
        $used = $examiner->sms_used ?? 0;
        $remaining = $examiner->sms_remaining ?? 0;
        echo "\n📊 Sample Examiner Data:\n";
        echo "   Examiner: " . ($examiner->username ?? $examiner->name ?? 'N/A') . "\n";
        echo "   Allocation: $allocation\n";
        echo "   Used: $used\n";
        echo "   Remaining: $remaining\n";
        if ($allocation === 0) {
            echo "   ⚠️  No SMS allocation set. Set it in Dashboard → Users → Edit Examiner\n";
        }
    } else {
        echo "\n⚠️  No examiners found in database.\n";
    }
} catch (Exception $e) {
    echo "\n⚠️  Could not check examiner data: " . $e->getMessage() . "\n";
}

echo "\n========================================\n";
if ($allPassed) {
    echo "✅ All checks passed! SMS system is deployed.\n";
    echo "\nNext steps:\n";
    echo "1. Set SMS allocation for examiners: Dashboard → Users → Edit\n";
    echo "2. Log in as examiner to see SMS balance in header\n";
    echo "3. Upload index numbers to test SMS sending\n";
} else {
    echo "❌ Some checks failed. Follow the instructions above.\n";
    echo "\nQuick fix:\n";
    echo "1. Pull latest code: cPanel → Git → Pull\n";
    echo "2. Run migration: /run-migrate.php?key=QuizSnap2026Xk9m2p7&run=yes\n";
    echo "3. Clear caches: /run-migrate.php (it clears caches automatically)\n";
}
