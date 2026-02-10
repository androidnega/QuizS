<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
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

        $classGroupIds = $classGroups->pluck('id')->filter()->values()->all();
        $scheduledQuiz = null;
        $scheduledQuizSession = null;
        if (!empty($classGroupIds)) {
            $candidates = Quiz::whereIn('class_group_id', $classGroupIds)
                ->where('is_published', true)
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
                })
                ->with('course')
                ->get();
            $ready = $candidates->filter(fn (Quiz $q) => $q->hasEnoughApprovedQuestions() && (($q->starts_at && $q->starts_at->isFuture()) || $q->isActive()));
            $scheduledQuiz = $ready->sortBy(fn (Quiz $q) => $q->starts_at && $q->starts_at->isFuture() ? $q->starts_at->timestamp : PHP_INT_MAX)->first();
            
            // Check if student has already completed this quiz
            if ($scheduledQuiz) {
                $scheduledQuizSession = QuizSession::where('quiz_id', $scheduledQuiz->id)
                    ->where('student_index', $student->index_number)
                    ->whereNotNull('ended_at')
                    ->with('result')
                    ->first();
            }
        }

        $hour = (int) now()->format('G');
        $greeting = ($hour >= 5 && $hour < 12) ? 'Good morning' : (($hour >= 12 && $hour < 17) ? 'Good afternoon' : 'Good evening');

        return view('student.dashboard.index', [
            'student' => $student,
            'classGroups' => $classGroups,
            'sessionsCount' => $sessionsCount,
            'recentSessions' => $recentSessions,
            'scheduledQuiz' => $scheduledQuiz,
            'scheduledQuizSession' => $scheduledQuizSession,
            'greeting' => $greeting,
        ]);
    }

    /**
     * List all quizzes (sessions) this student has taken. Marks are kept forever.
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
        $student->student_name = $request->filled('student_name') ? ucwords(strtolower(trim($request->student_name))) : null;
        $student->save();
        return redirect()->route('dashboard.my-profile')->with('success', 'Profile updated.');
    }

    /**
     * Show one past quiz. Marks (score) always shown. Full Q&A review only for last 21 days.
     */
    public function showQuiz(Request $request, $sessionId): View|RedirectResponse
    {
        try {
            $student = $this->student();
            
            // Validate session ID is numeric
            if (!is_numeric($sessionId)) {
                return redirect()->route('dashboard.my-quizzes')->with('error', 'Invalid quiz session.');
            }
            
            $quizSession = QuizSession::where('id', $sessionId)
                ->where('student_index', $student->index_number)
                ->with(['quiz.course', 'result', 'answers.question'])
                ->first();
            
            if (!$quizSession) {
                return redirect()->route('dashboard.my-quizzes')->with('error', 'Quiz session not found or you do not have access to it.');
            }

            if (!$quizSession->quiz) {
                return redirect()->route('dashboard.my-quizzes')->with('error', 'Quiz not found.');
            }

            if (!$quizSession->quiz->canShowScore()) {
                return redirect()->route('dashboard.my-quizzes')->with('info', 'Results are not available for this quiz.');
            }

            $reviewAvailableWithinDays = 21;
            $showFullReview = $quizSession->created_at && $quizSession->created_at->gte(now()->subDays($reviewAvailableWithinDays));

            return view('student.dashboard.quiz-show', [
                'student' => $student,
                'session' => $quizSession,
                'showFullReview' => $showFullReview,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error showing quiz review: ' . $e->getMessage(), [
                'session_id' => $sessionId ?? null,
                'student_id' => $student->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('dashboard.my-quizzes')->with('error', 'An error occurred while loading the quiz review. Please try again.');
        }
    }
}
