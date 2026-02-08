<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\Answer;
use App\Models\ClassGroup;
use App\Models\ClassGroupStudent;
use App\Models\Course;
use App\Models\Question;
use App\Models\QuestionPool;
use App\Models\Quiz;
use App\Models\QuizAcceptance;
use App\Models\QuizSession;
use App\Models\Result;
use App\Models\User;
use App\Models\ValidIndex;
use App\Models\QuizViolation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SystemResetController extends Controller
{
    use InteractsWithAdminSession;

    /**
     * Show reset system page (Super Admin only). Requires password + type (data_only / all_except_super_admin).
     */
    public function index(): View
    {
        return view('admin.system.reset');
    }

    /**
     * Reset the system. Requires current Super Admin password.
     * data_only = clear quizzes, courses, and all other data; keep all users.
     * all_except_super_admin = clear all data AND remove all users except Super Admin.
     */
    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'admin_password' => 'required|string',
            'reset_type' => 'required|in:data_only,all_except_super_admin',
            'confirm' => [
                'required',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (strtoupper(trim((string) $value)) !== 'RESET') {
                        $fail('You must type RESET to confirm.');
                    }
                },
            ],
        ], [
            'admin_password.required' => 'Your password is required.',
            'reset_type.required' => 'Please choose an option.',
            'confirm.required' => 'You must type RESET to confirm.',
        ]);

        $admin = $this->adminUser();
        if (!$admin || $admin->role !== User::ROLE_SUPER_ADMIN) {
            return redirect()->route('dashboard.system.reset.index')
                ->with('error', 'Only Super Admin can reset the system.');
        }

        if (!Hash::check($request->admin_password, $admin->password)) {
            return redirect()->back()
                ->withInput($request->only('reset_type', 'confirm'))
                ->withErrors(['admin_password' => 'Password is incorrect.']);
        }

        try {
            if ($request->reset_type === 'data_only') {
                $this->clearDataOnly();
                $message = 'System data cleared. All quizzes, courses, and related data have been removed. All user accounts (including examiners) are unchanged.';
            } else {
                $this->clearAllExceptSuperAdmin();
                $message = 'System reset complete. All data has been cleared and all users except Super Admin have been removed. You can add courses and examiners again.';
            }
        } catch (\Throwable $e) {
            return redirect()->route('dashboard.system.reset.index')
                ->withInput($request->only('reset_type'))
                ->with('error', 'Reset failed: ' . $e->getMessage());
        }

        return redirect()->route('dashboard')->with('success', $message);
    }

    /**
     * Clear all system data (quizzes, courses, sessions, etc.) but keep all users.
     * Courses are removed. No user accounts (including Super Admin) are modified; passwords stay unchanged.
     */
    private function clearDataOnly(): void
    {
        DB::transaction(function () {
            Result::query()->delete();
            QuizViolation::query()->delete();
            Answer::query()->delete();
            QuestionPool::query()->delete();
            Question::query()->delete();
            QuizSession::query()->delete();
            QuizAcceptance::query()->delete();
            Quiz::query()->delete();
            ValidIndex::query()->delete();
            DB::table('course_user')->delete();
            DB::table('class_group_course')->delete();
            Course::query()->delete();
        });
    }

    /**
     * Clear all data and delete all users except Super Admin.
     * Courses are removed. Super Admin user(s) are never modified or deleted; their password stays unchanged.
     */
    private function clearAllExceptSuperAdmin(): void
    {
        DB::transaction(function () {
            Result::query()->delete();
            QuizViolation::query()->delete();
            Answer::query()->delete();
            QuestionPool::query()->delete();
            Question::query()->delete();
            QuizSession::query()->delete();
            QuizAcceptance::query()->delete();
            Quiz::query()->delete();
            ClassGroupStudent::query()->delete();
            DB::table('class_group_course')->delete();
            ClassGroup::query()->delete();
            ValidIndex::query()->delete();
            DB::table('course_user')->delete();
            Course::query()->delete();
            User::where('role', '!=', User::ROLE_SUPER_ADMIN)->delete();
        });
    }
}
