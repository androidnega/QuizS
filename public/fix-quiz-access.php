<?php
/**
 * Fix Quiz Access Issues
 * 
 * This script helps diagnose and fix quiz access issues after running installation scripts.
 * 
 * SECURITY: Delete this file after use!
 * Access: https://yourdomain.com/fix-quiz-access.php?key=YOUR_SECRET_KEY&quiz_id=12
 */

// SECURITY: Set a secret key
$SECRET_KEY = 'QuizSnapFix2026' . date('Ymd');

// Check if key matches
$providedKey = $_GET['key'] ?? '';
if ($providedKey !== $SECRET_KEY) {
    http_response_code(403);
    die('Access denied. Provide correct ?key= parameter.');
}

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Quiz;
use App\Models\ClassGroup;
use App\Models\User;

$quizId = $_GET['quiz_id'] ?? null;

echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix Quiz Access</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #0f0; }
        .success { color: #0f0; }
        .error { color: #f00; }
        .info { color: #0ff; }
        .warning { color: #ff0; }
        pre { background: #000; padding: 10px; border: 1px solid #333; overflow-x: auto; }
        h1 { color: #fff; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; }
        th { background: #333; color: #0f0; }
    </style>
</head>
<body>
    <h1>Quiz Access Diagnostic & Fix</h1>";

if ($quizId) {
    $quiz = Quiz::with('classGroup')->find($quizId);
    
    if (!$quiz) {
        echo "<p class='error'>Quiz ID {$quizId} not found.</p></body></html>";
        exit;
    }
    
    echo "<h2>Quiz #{$quiz->id}: {$quiz->title}</h2>";
    echo "<table>";
    echo "<tr><th>Property</th><th>Value</th></tr>";
    echo "<tr><td>ID</td><td>{$quiz->id}</td></tr>";
    echo "<tr><td>Title</td><td>{$quiz->title}</td></tr>";
    echo "<tr><td>Class Group ID</td><td>" . ($quiz->class_group_id ?? '<span class=\"error\">NULL</span>') . "</td></tr>";
    
    if ($quiz->classGroup) {
        echo "<tr><td>Class Group Name</td><td>{$quiz->classGroup->name}</td></tr>";
        echo "<tr><td>Class Group Examiner ID</td><td>{$quiz->classGroup->examiner_id}</td></tr>";
        
        if ($quiz->classGroup->examiner_id) {
            $examiner = User::find($quiz->classGroup->examiner_id);
            echo "<tr><td>Examiner Username</td><td>" . ($examiner ? $examiner->username : '<span class=\"error\">Not found</span>') . "</td></tr>";
            echo "<tr><td>Examiner Role</td><td>" . ($examiner ? $examiner->role : 'N/A') . "</td></tr>";
        }
    } else {
        echo "<tr><td>Class Group</td><td><span class=\"error\">NOT ASSIGNED</span></td></tr>";
    }
    
    echo "</table>";
    
    // Check current logged-in user (if any)
    $currentUserId = session('admin_user_id');
    if ($currentUserId) {
        $currentUser = User::find($currentUserId);
        echo "<h3>Current User</h3>";
        echo "<table>";
        echo "<tr><th>Property</th><th>Value</th></tr>";
        echo "<tr><td>ID</td><td>{$currentUser->id}</td></tr>";
        echo "<tr><td>Username</td><td>{$currentUser->username}</td></tr>";
        echo "<tr><td>Role</td><td>{$currentUser->role}</td></tr>";
        echo "<tr><td>Is Super Admin</td><td>" . ($currentUser->isSuperAdmin() ? 'Yes' : 'No') . "</td></tr>";
        echo "<tr><td>Is Examiner</td><td>" . ($currentUser->isExaminer() ? 'Yes' : 'No') . "</td></tr>";
        echo "</table>";
        
        // Check if user can access
        $canAccess = false;
        if ($currentUser->isSuperAdmin()) {
            $canAccess = true;
        } elseif ($quiz->classGroup && (int)$quiz->classGroup->examiner_id === (int)$currentUser->id) {
            $canAccess = true;
        }
        
        echo "<p class='" . ($canAccess ? 'success' : 'error') . "'>";
        echo $canAccess ? "✓ User CAN access this quiz" : "✗ User CANNOT access this quiz";
        echo "</p>";
    } else {
        echo "<p class='warning'>No user logged in. Please log in first.</p>";
    }
    
    // Show all class groups
    echo "<h3>Available Class Groups</h3>";
    $classGroups = ClassGroup::with('examiner')->get();
    echo "<table>";
    echo "<tr><th>ID</th><th>Name</th><th>Examiner ID</th><th>Examiner Username</th></tr>";
    foreach ($classGroups as $cg) {
        $selected = $quiz->class_group_id == $cg->id ? ' style="background: #0f0; color: #000;"' : '';
        echo "<tr{$selected}>";
        echo "<td>{$cg->id}</td>";
        echo "<td>{$cg->name}</td>";
        echo "<td>{$cg->examiner_id}</td>";
        echo "<td>" . ($cg->examiner ? $cg->examiner->username : 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Fix option
    if (isset($_GET['fix']) && $_GET['fix'] === 'yes' && isset($_GET['class_group_id'])) {
        $newClassGroupId = (int)$_GET['class_group_id'];
        $newClassGroup = ClassGroup::find($newClassGroupId);
        
        if ($newClassGroup) {
            $quiz->update(['class_group_id' => $newClassGroupId]);
            echo "<p class='success'>✓ Quiz updated! Class Group ID {$newClassGroupId} assigned.</p>";
            echo "<p class='info'><a href='/dashboard/quizzes/{$quiz->id}' style='color: #0ff;'>Try accessing the quiz now</a></p>";
        } else {
            echo "<p class='error'>✗ Class Group ID {$newClassGroupId} not found.</p>";
        }
    } else {
        echo "<h3>Fix Options</h3>";
        echo "<p class='info'>To assign this quiz to a class group, use:</p>";
        echo "<pre>?key={$SECRET_KEY}&quiz_id={$quizId}&fix=yes&class_group_id=CLASS_GROUP_ID</pre>";
    }
    
} else {
    echo "<h2>Usage</h2>";
    echo "<p class='info'>Add quiz_id parameter:</p>";
    echo "<pre>?key={$SECRET_KEY}&quiz_id=12</pre>";
    
    // List all quizzes
    echo "<h3>All Quizzes</h3>";
    $quizzes = Quiz::with('classGroup')->get();
    echo "<table>";
    echo "<tr><th>ID</th><th>Title</th><th>Class Group</th><th>Examiner ID</th></tr>";
    foreach ($quizzes as $q) {
        echo "<tr>";
        echo "<td><a href='?key={$SECRET_KEY}&quiz_id={$q->id}' style='color: #0ff;'>{$q->id}</a></td>";
        echo "<td>{$q->title}</td>";
        echo "<td>" . ($q->classGroup ? $q->classGroup->name : '<span class="error">None</span>') . "</td>";
        echo "<td>" . ($q->classGroup ? $q->classGroup->examiner_id : 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "</body></html>";
