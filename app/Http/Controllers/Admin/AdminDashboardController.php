<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\Result;
use App\Models\Setting;
use App\Models\User;
use App\Services\CloudinaryService;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    use InteractsWithAdminSession;

    /** Unified dashboard: show admin or examiner content based on role. */
    public function index(): View|\Illuminate\Http\RedirectResponse
    {
        if (session('admin_role') === 'super_admin') {
            return $this->adminDashboard();
        }
        return $this->examinerDashboard();
    }

    /** Admin (Super Admin) dashboard: stats, courses, users, class groups, quizzes. */
    public function adminDashboard(): View
    {
        $overview = [
            'users' => User::whereIn('role', [User::ROLE_SUPER_ADMIN, User::ROLE_EXAMINER])->count(),
            'courses' => Course::count(),
            'class_groups' => ClassGroup::count(),
            'quizzes' => Quiz::count(),
            'sessions' => QuizSession::count(),
        ];
        $cloudinary_configured = CloudinaryService::isConfigured();
        $update_mode = Setting::getValue(Setting::KEY_UPDATE_MODE, '0') === '1';
        $update_started_at = $update_mode ? Setting::getValue(Setting::KEY_UPDATE_STARTED_AT) : null;
        $update_estimated_end = $update_mode ? Setting::getValue(Setting::KEY_UPDATE_ESTIMATED_END) : null;
        return view('admin.dashboard-admin', compact('overview', 'cloudinary_configured', 'update_mode', 'update_started_at', 'update_estimated_end'));
    }

    /** Examiner dashboard: my class groups, my quizzes, recent sessions. */
    public function examinerDashboard(): View
    {
        $user = $this->adminUser();
        $classGroupIds = $user ? $user->classGroupIds() : [];
        $quizzes = Quiz::with(['course', 'classGroup'])
            ->whereIn('class_group_id', $classGroupIds)
            ->orderByDesc('created_at')
            ->paginate(10);
        $classGroups = !empty($classGroupIds)
            ? ClassGroup::withCount('students')->whereIn('id', $classGroupIds)->orderBy('name')->get()
            : collect();
        $quizIds = !empty($classGroupIds) ? Quiz::whereIn('class_group_id', $classGroupIds)->pluck('id') : collect();
        $stats = [
            'quizzes' => Quiz::whereIn('class_group_id', $classGroupIds)->count(),
            'sessions' => $quizIds->isEmpty() ? 0 : QuizSession::whereIn('quiz_id', $quizIds)->count(),
            'results' => $quizIds->isEmpty() ? 0 : Result::whereHas('quizSession', fn ($q) => $q->whereIn('quiz_id', $quizIds))->count(),
        ];
        $recentSessions = $quizIds->isEmpty() ? collect() : QuizSession::with(['quiz', 'result'])->whereIn('quiz_id', $quizIds)->orderByDesc('start_time')->limit(20)->get();
        
        // Check if examiner needs to set faculty/department
        $needsFacultyDepartment = $user && $user->isExaminer() && (!$user->faculty_id || !$user->department_id);
        
        return view('admin.dashboard-examiner', compact('quizzes', 'classGroups', 'recentSessions', 'stats', 'needsFacultyDepartment'));
    }
}
