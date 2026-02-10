<?php

use App\Http\Controllers\Student\QuizRulesController;
use App\Http\Controllers\Student\StudentLoginController;
use App\Http\Controllers\Student\TokenValidationController;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Http\Controllers\Student\ProctoringCaptureController;
use App\Http\Controllers\Student\StudentQuizController;
use App\Http\Controllers\Student\PostQuizCaptureController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\ClassGroupController;
use App\Http\Controllers\Admin\QuizManagementController;
use App\Http\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

// Public landing: single Start Quiz entry; no quiz list. If direct link has token (?t= or ?token=), go straight to rules.
Route::get('/', function (\Illuminate\Http\Request $request) {
    $token = $request->query('t') ?? $request->query('token');
    if ($token && is_string($token)) {
        $token = trim($token);
        if (preg_match('#^[a-zA-Z0-9_-]{8,64}$#', $token)) {
            $quiz = Quiz::where('link_token', $token)->first();
            if ($quiz && ($quiz->is_published || $quiz->is_active) && $quiz->hasEnoughApprovedQuestions()) {
                if ($quiz->ends_at && $quiz->ends_at->isPast()) {
                    return redirect()->route('student.link-expired');
                }
                if ($quiz->starts_at && $quiz->starts_at->isFuture()) {
                    return redirect()->route('student.quiz-will-start', ['token' => $token]);
                }
                return redirect()->route('student.rules.show.quiz', ['token' => $token]);
            }
            return redirect()->route('student.link-expired');
        }
    }
    
    // If lecturer/examiner is logged in, redirect to dashboard
    if (session('admin_authenticated', false) && session('admin_user_id')) {
        $user = \App\Models\User::find(session('admin_user_id'));
        if ($user && $user->isStaff()) {
            return redirect()->route('dashboard');
        }
    }
    
    $studentId = session('student_id');
    $student = $studentId ? \App\Models\Student::find($studentId) : null;
    
    return view('student.landing', compact('student'));
})->name('student.landing');

Route::get('/about-system', function () {
    $studentId = session('student_id');
    $student = $studentId ? \App\Models\Student::find($studentId) : null;
    return view('student.about-system', compact('student'));
})->name('about-system');

Route::post('/student/validate-token', [TokenValidationController::class, 'validateToken'])->name('student.validate-token');
Route::post('/student/start-quiz', function (\Illuminate\Http\Request $request) {
    $request->validate(['link' => 'required|string|max:2048']);
    $input = trim($request->input('link', ''));
    $token = null;
    if (preg_match('#/t/([a-zA-Z0-9_-]+)#', $input, $m)) {
        $token = $m[1];
    } elseif (preg_match('#^([a-zA-Z0-9_-]{8,64})$#', $input, $m)) {
        $token = $m[1];
    }
    if (!$token) {
        return redirect()->route('student.link-expired');
    }
    $quiz = Quiz::where('link_token', $token)->first();
    if (!$quiz || (!$quiz->is_published && !$quiz->is_active) || !$quiz->hasEnoughApprovedQuestions()) {
        return redirect()->route('student.link-expired');
    }
    if ($quiz->ends_at && $quiz->ends_at->isPast()) {
        return redirect()->route('student.link-expired');
    }
    if ($quiz->starts_at && $quiz->starts_at->isFuture()) {
        return redirect()->route('student.quiz-will-start', ['token' => $token]);
    }
    return redirect()->route('student.rules.show.quiz', ['token' => $token]);
})->name('student.start-quiz');

Route::get('/student/link-expired', fn () => view('student.link-expired'))->name('student.link-expired');

Route::get('/quiz/rules', [QuizRulesController::class, 'show'])->name('student.rules.show');
Route::get('/t/{token}', [QuizRulesController::class, 'show'])->name('student.rules.show.quiz');
Route::get('/t/{token}/wait', [QuizRulesController::class, 'quizWillStart'])->name('student.quiz-will-start');
Route::get('/take/quiz/{token}/rules', fn ($token) => redirect()->route('student.rules.show.quiz', ['token' => $token], 301))->name('student.rules.show.quiz.legacy');
Route::post('/quiz/accept-rules', [QuizRulesController::class, 'accept'])->name('student.rules.accept');

Route::get('/student/login', [StudentLoginController::class, 'showLoginForm'])->name('student.login.form')->middleware('rules.accepted');
Route::post('/student/verify-index', [StudentLoginController::class, 'verifyIndex'])->name('student.verify.index')->middleware('rules.accepted');

Route::get('/student/proctoring/capture', [ProctoringCaptureController::class, 'show'])->name('student.proctoring.capture')->middleware('rules.accepted');
Route::post('/student/proctoring/capture', [ProctoringCaptureController::class, 'store'])->name('student.proctoring.store');

Route::get('/quiz/ready', [StudentQuizController::class, 'ready'])->name('student.quiz.ready')->middleware('rules.accepted');
Route::get('/quiz/take', [StudentQuizController::class, 'show'])->name('student.quiz.show')->middleware('rules.accepted');
Route::get('/quiz/time-sync', [StudentQuizController::class, 'timeSync'])->name('student.quiz.time-sync')->middleware('rules.accepted');
Route::post('/quiz/save-answer', [StudentQuizController::class, 'saveAnswer'])->name('student.quiz.save');
Route::post('/quiz/save-answers', [StudentQuizController::class, 'saveAnswersBatch'])->name('student.quiz.save.batch');
Route::post('/quiz/violation', [StudentQuizController::class, 'recordViolation'])->name('student.quiz.violation');
Route::post('/quiz/heartbeat', [StudentQuizController::class, 'heartbeat'])->name('student.quiz.heartbeat');
Route::post('/quiz/finalize', [StudentQuizController::class, 'finalize'])->name('student.quiz.finalize');
Route::get('/quiz/complete', [StudentQuizController::class, 'quizComplete'])->name('student.quiz.complete');
Route::get('/quiz/result', [StudentQuizController::class, 'result'])->name('student.result');

Route::get('/quiz/final-photo', [PostQuizCaptureController::class, 'show'])->name('student.final-photo.capture')->middleware('rules.accepted');
Route::post('/quiz/post-face', [PostQuizCaptureController::class, 'store'])->name('student.post-face.store');

// Student account login (index → phone → OTP); no quiz link required
Route::get('/student/account/login', [\App\Http\Controllers\Student\StudentAccountController::class, 'showLoginForm'])->name('student.account.login.form');
Route::post('/student/account/verify-index', [\App\Http\Controllers\Student\StudentAccountController::class, 'verifyIndex'])->name('student.account.verify-index');
Route::post('/student/account/send-otp', [\App\Http\Controllers\Student\StudentAccountController::class, 'sendOtp'])->name('student.account.send-otp');
Route::post('/student/account/verify-otp', [\App\Http\Controllers\Student\StudentAccountController::class, 'verifyOtp'])->name('student.account.verify-otp');
Route::post('/student/account/logout', [\App\Http\Controllers\Student\StudentAccountController::class, 'logout'])->name('student.account.logout');

// Legacy redirects: old student dashboard URLs → unified /dashboard
Route::get('/student/dashboard', fn () => redirect()->route('dashboard', [], 301))->name('student.dashboard.index.legacy');
Route::get('/student/dashboard/quizzes', fn () => redirect()->route('dashboard.my-quizzes', [], 301));
Route::get('/student/dashboard/profile', fn () => redirect()->route('dashboard.my-profile', [], 301));

// Unified dashboard: /dashboard (student or staff); student-only routes under /dashboard
Route::get('/dashboard', [\App\Http\Controllers\DashboardGatewayController::class, '__invoke'])->middleware('dashboard.auth')->name('dashboard');
Route::middleware(['dashboard.auth', 'student.auth'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/my-quizzes', [\App\Http\Controllers\Student\StudentDashboardController::class, 'quizzes'])->name('my-quizzes');
    Route::get('/my-quizzes/{sessionId}', [\App\Http\Controllers\Student\StudentDashboardController::class, 'showQuiz'])->name('my-quizzes.show');
    Route::get('/my-profile', [\App\Http\Controllers\Student\StudentDashboardController::class, 'profile'])->name('my-profile');
    Route::put('/my-profile', [\App\Http\Controllers\Student\StudentDashboardController::class, 'updateProfile'])->name('my-profile.update');
    Route::get('/course-materials', [\App\Http\Controllers\Student\StudentDashboardController::class, 'courseMaterials'])->name('course-materials');
});

// Staff login
Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AdminAuthController::class, 'login'])->name('login.post');
Route::get('/password/forgot', [\App\Http\Controllers\Admin\StaffPasswordResetController::class, 'showForgotForm'])->name('password.forgot');
Route::post('/password/forgot', [\App\Http\Controllers\Admin\StaffPasswordResetController::class, 'sendResetLink'])->name('password.forgot.send');
Route::get('/password/reset/{token}', [\App\Http\Controllers\Admin\StaffPasswordResetController::class, 'showResetForm'])->name('password.reset.form');
Route::post('/password/reset', [\App\Http\Controllers\Admin\StaffPasswordResetController::class, 'reset'])->name('password.reset');

// Staff dashboard and all staff pages under /dashboard (admin + examiner)
Route::middleware('admin.auth')->group(function () {
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
    // GET /dashboard is handled by DashboardGatewayController (unified)

    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        // Profile — both roles
        Route::get('/profile', [\App\Http\Controllers\Admin\StaffProfileController::class, 'show'])->name('profile.show');
        Route::put('/profile', [\App\Http\Controllers\Admin\StaffProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/avatar', [\App\Http\Controllers\Admin\StaffProfileController::class, 'updateAvatar'])->name('profile.avatar');
        Route::get('/profile/password', [\App\Http\Controllers\Admin\StaffProfileController::class, 'password'])->name('profile.password');
        Route::put('/profile/password', [\App\Http\Controllers\Admin\StaffProfileController::class, 'updatePassword'])->name('profile.password.update');

        // Institution / School — both roles (examiner-accessible; logo uploads to Cloudinary)
        Route::get('/institution', [\App\Http\Controllers\Admin\InstitutionController::class, 'index'])->name('institution.index');
        Route::put('/institution', [\App\Http\Controllers\Admin\InstitutionController::class, 'update'])->name('institution.update');

        // Class groups — both (policy controls create/edit/delete)
        Route::get('/class-groups', [ClassGroupController::class, 'index'])->name('class-groups.index');
        Route::get('/class-groups/create', [ClassGroupController::class, 'create'])->name('class-groups.create');
        Route::post('/class-groups', [ClassGroupController::class, 'store'])->name('class-groups.store');
        Route::get('/class-groups/{classGroup}', [ClassGroupController::class, 'show'])->name('class-groups.show');
        Route::get('/class-groups/{classGroup}/edit', [ClassGroupController::class, 'edit'])->name('class-groups.edit');
        Route::put('/class-groups/{classGroup}', [ClassGroupController::class, 'update'])->name('class-groups.update');
        Route::delete('/class-groups/{classGroup}', [ClassGroupController::class, 'destroy'])->name('class-groups.destroy');
        Route::get('/class-groups/{classGroup}/students', [ClassGroupController::class, 'studentsIndex'])->name('class-groups.students.index');
        Route::get('/class-groups/{classGroup}/students/{student}', [ClassGroupController::class, 'showStudent'])->name('class-groups.students.show');
        Route::get('/class-groups/{classGroup}/students/{student}/edit', [ClassGroupController::class, 'editStudent'])->name('class-groups.students.edit');
        Route::post('/class-groups/{classGroup}/students', [ClassGroupController::class, 'addStudent'])->name('class-groups.students.add');
        Route::post('/class-groups/{classGroup}/students/upload', [ClassGroupController::class, 'uploadStudents'])->name('class-groups.students.upload');
        Route::put('/class-groups/{classGroup}/students/{student}', [ClassGroupController::class, 'updateStudent'])->name('class-groups.students.update');
        Route::delete('/class-groups/{classGroup}/students/{student}', [ClassGroupController::class, 'destroyStudent'])->name('class-groups.students.destroy');
        Route::delete('/class-groups/{classGroup}/students/{student}/phone', [ClassGroupController::class, 'removeStudentPhone'])->name('class-groups.students.remove-phone');

        // Quizzes — examiner only
        Route::middleware('examiner.only')->group(function () {
            Route::get('/students', fn () => redirect()->route('dashboard.class-groups.index', [], 301))->name('students.index');
            Route::get('/attendance', fn () => redirect()->route('dashboard.class-groups.index', [], 301))->name('attendance.index');
            Route::get('/quizzes', [QuizManagementController::class, 'index'])->name('quizzes.index');
            Route::get('/quizzes/create', [QuizManagementController::class, 'create'])->name('quizzes.create');
            Route::post('/quizzes', [QuizManagementController::class, 'store'])->name('quizzes.store');
            Route::get('/quizzes/{quiz}', [QuizManagementController::class, 'show'])->name('quizzes.show');
            Route::get('/quizzes/{quiz}/edit', [QuizManagementController::class, 'edit'])->name('quizzes.edit');
            Route::put('/quizzes/{quiz}', [QuizManagementController::class, 'update'])->name('quizzes.update');
            Route::get('/quizzes/{quiz}/scores', [QuizManagementController::class, 'scores'])->name('quizzes.scores');
            Route::get('/quizzes/{quiz}/scores/export/pdf/preview', [QuizManagementController::class, 'exportScoresPdf'])->name('quizzes.scores.export.pdf.preview');
            Route::get('/quizzes/{quiz}/scores/export/pdf', [QuizManagementController::class, 'exportScoresPdf'])->name('quizzes.scores.export.pdf');
            Route::get('/quizzes/{quiz}/scores/export/excel', [QuizManagementController::class, 'exportScoresExcel'])->name('quizzes.scores.export.excel');
            Route::get('/quizzes/{quiz}/scores/export', [QuizManagementController::class, 'exportScores'])->name('quizzes.scores.export');
            Route::get('/quizzes/{quiz}/violations/export', [QuizManagementController::class, 'exportViolations'])->name('quizzes.violations.export');
            Route::get('/quizzes/{quiz}/sessions/{session}', [QuizManagementController::class, 'showSession'])->name('quizzes.sessions.show');
            Route::post('/quizzes/{quiz}/sessions/{session}/reset-ip', [QuizManagementController::class, 'resetSessionIp'])->name('quizzes.sessions.reset-ip');
            Route::post('/quizzes/{quiz}/question-pools/{pool}/approve', [QuizManagementController::class, 'approvePool'])->name('quizzes.pool.approve');
            Route::get('/quizzes/{quiz}/question-pools/{pool}/edit', [QuizManagementController::class, 'editPool'])->name('quizzes.pool.edit');
            Route::put('/quizzes/{quiz}/question-pools/{pool}', [QuizManagementController::class, 'updatePool'])->name('quizzes.pool.update');
            Route::delete('/quizzes/{quiz}/question-pools/{pool}', [QuizManagementController::class, 'rejectPool'])->name('quizzes.pool.reject');
            Route::post('/quizzes/{quiz}/approve-all-pool', [QuizManagementController::class, 'approveAllPool'])->name('quizzes.approve-all-pool');
            Route::post('/quizzes/{quiz}/publish', [QuizManagementController::class, 'publish'])->name('quizzes.publish');
            Route::post('/quizzes/{quiz}/unpublish', [QuizManagementController::class, 'unpublish'])->name('quizzes.unpublish');
            Route::post('/quizzes/{quiz}/end', [QuizManagementController::class, 'endQuiz'])->name('quizzes.end');
            Route::post('/quizzes/{quiz}/extend-time', [QuizManagementController::class, 'extendTime'])->name('quizzes.extend-time');
            Route::get('/quizzes/{quiz}/questions/{question}/edit', [QuizManagementController::class, 'editQuestion'])->name('quizzes.questions.edit');
            Route::put('/quizzes/{quiz}/questions/{question}', [QuizManagementController::class, 'updateQuestion'])->name('quizzes.questions.update');
            Route::delete('/quizzes/{quiz}/questions/{question}', [QuizManagementController::class, 'destroyQuestion'])->name('quizzes.questions.destroy');
            Route::delete('/quizzes/{quiz}', [QuizManagementController::class, 'destroy'])->name('quizzes.destroy');
        });

        // Courses: Super Admin always, Examiner when setting allows
        Route::middleware('course.creation')->group(function () {
            Route::get('/courses', [\App\Http\Controllers\Admin\CourseController::class, 'index'])->name('courses.index');
            Route::get('/courses/create', [\App\Http\Controllers\Admin\CourseController::class, 'create'])->name('courses.create');
            Route::post('/courses', [\App\Http\Controllers\Admin\CourseController::class, 'store'])->name('courses.store');
            Route::get('/courses/{course}/edit', [\App\Http\Controllers\Admin\CourseController::class, 'edit'])->name('courses.edit');
            Route::put('/courses/{course}', [\App\Http\Controllers\Admin\CourseController::class, 'update'])->name('courses.update');
            Route::post('/courses/{course}/archive', [\App\Http\Controllers\Admin\CourseController::class, 'archive'])->name('courses.archive');
            Route::post('/courses/{course}/unarchive', [\App\Http\Controllers\Admin\CourseController::class, 'unarchive'])->name('courses.unarchive');
            Route::delete('/courses/{course}', [\App\Http\Controllers\Admin\CourseController::class, 'destroy'])->name('courses.destroy');
        });

        // Super Admin only: users, settings, system reset
        Route::middleware('admin.role')->group(function () {
            Route::get('/system/reset', [\App\Http\Controllers\Admin\SystemResetController::class, 'index'])->name('system.reset.index');
            Route::post('/system/reset', [\App\Http\Controllers\Admin\SystemResetController::class, 'reset'])->name('system.reset');
            Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
            if (! app()->environment('production')) {
                Route::get('/settings/ai-test', [SettingsController::class, 'aiTest'])->name('settings.ai-test');
                Route::get('/settings/cloudinary-test', [SettingsController::class, 'cloudinaryTest'])->name('settings.cloudinary-test');
            }
            Route::post('/settings/otp-test', [SettingsController::class, 'otpTest'])->name('settings.otp-test');
            Route::get('/settings/otp-balance', [SettingsController::class, 'otpBalance'])->name('settings.otp-balance');
            Route::get('/users', [\App\Http\Controllers\Admin\UserManagementController::class, 'index'])->name('users.index');
            Route::get('/users/create', [\App\Http\Controllers\Admin\UserManagementController::class, 'create'])->name('users.create');
            Route::post('/users', [\App\Http\Controllers\Admin\UserManagementController::class, 'store'])->name('users.store');
            Route::get('/users/{user}/edit', [\App\Http\Controllers\Admin\UserManagementController::class, 'edit'])->name('users.edit');
            Route::put('/users/{user}', [\App\Http\Controllers\Admin\UserManagementController::class, 'update'])->name('users.update');
            Route::get('/users/{user}/view-password', [\App\Http\Controllers\Admin\UserManagementController::class, 'showPasswordForm'])->name('users.view-password-form');
            Route::post('/users/{user}/view-password', [\App\Http\Controllers\Admin\UserManagementController::class, 'viewPassword'])->name('users.view-password');
        });
    });
});
