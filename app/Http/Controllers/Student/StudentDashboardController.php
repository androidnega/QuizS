<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\QuizSession;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    protected function student(): Student
    {
        $user = auth()->user();
        if ($user instanceof Student) {
            return $user;
        }
        $student = Student::find(session('student_id'));
        if (!$student) {
            abort(401, 'Not authenticated as student.');
        }
        return $student;
    }

    public function index(): View
    {
        $student = $this->student();
        $student->load(['classGroupStudents.classGroup']);
        $classGroups = $student->classGroupStudents->map(fn ($s) => $s->classGroup)->filter()->unique('id')->values();

        $sessionsCount = QuizSession::where('student_index', $student->index_number)->count();
        $recentSessions = QuizSession::where('student_index', $student->index_number)
            ->with(['quiz', 'result'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('student.dashboard.index', [
            'student' => $student,
            'classGroups' => $classGroups,
            'sessionsCount' => $sessionsCount,
            'recentSessions' => $recentSessions,
        ]);
    }

    /**
     * List quizzes (sessions) this student has taken.
     */
    public function quizzes(): View
    {
        $student = $this->student();
        $sessions = QuizSession::where('student_index', $student->index_number)
            ->with(['quiz', 'result'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('student.dashboard.quizzes', [
            'student' => $student,
            'sessions' => $sessions,
        ]);
    }

    public function profile(): View
    {
        $student = $this->student();
        $student->load(['classGroupStudents.classGroup']);
        $classGroups = $student->classGroupStudents->map(fn ($s) => $s->classGroup)->filter()->unique('id')->values();
        return view('student.dashboard.profile', ['student' => $student, 'classGroups' => $classGroups]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $student = $this->student();
        $request->validate([
            'student_name' => 'nullable|string|max:255',
        ]);
        $student->student_name = $request->filled('student_name') ? trim($request->student_name) : null;
        $student->save();
        return redirect()->route('student.dashboard.profile')->with('success', 'Profile updated.');
    }
}
