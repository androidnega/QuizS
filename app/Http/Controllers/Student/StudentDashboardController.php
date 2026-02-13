<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassGroup;
use App\Models\ClassGroupStudent;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
        // Use case-insensitive index match (class_group_students may differ from students.index_number after migration)
        $classGroupIds = ClassGroupStudent::whereRaw('LOWER(TRIM(index_number)) = ?', [strtolower(trim($student->index_number ?? ''))])
            ->pluck('class_group_id')
            ->unique()
            ->values()
            ->all();
        $classGroups = $student->classGroupStudents->map(fn ($s) => $s->classGroup)->filter()->unique('id')->values();
        if (empty($classGroupIds) && $classGroups->isNotEmpty()) {
            $classGroupIds = $classGroups->pluck('id')->filter()->values()->all();
        }
        if ($classGroups->isEmpty() && !empty($classGroupIds)) {
            $classGroups = ClassGroup::whereIn('id', $classGroupIds)->get();
        }

        $sessionsCount = QuizSession::where('student_index', $student->index_number)->count();
        $recentSessions = QuizSession::where('student_index', $student->index_number)
            ->with(['quiz', 'result'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
        
        // Get the last completed quiz with result
        $lastQuiz = QuizSession::where('student_index', $student->index_number)
            ->whereHas('result')
            ->with(['quiz.course', 'result'])
            ->orderByDesc('created_at')
            ->first();

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
            'lastQuiz' => $lastQuiz,
            'greeting' => $greeting,
        ]);
    }

    /**
     * List all quizzes (sessions) this student has taken. Marks are kept forever.
     * Active quizzes (available to take) are shown first.
     */
    public function quizzes(): View
    {
        $student = $this->student();
        $student->load(['classGroupStudents.classGroup']);
        // Use case-insensitive index match (class_group_students may differ from students.index_number after migration)
        $classGroupIds = ClassGroupStudent::whereRaw('LOWER(TRIM(index_number)) = ?', [strtolower(trim($student->index_number ?? ''))])
            ->pluck('class_group_id')
            ->unique()
            ->values()
            ->all();
        $classGroups = $student->classGroupStudents->map(fn ($s) => $s->classGroup)->filter()->unique('id')->values();
        if (empty($classGroupIds) && $classGroups->isNotEmpty()) {
            $classGroupIds = $classGroups->pluck('id')->filter()->values()->all();
        }
        
        // Get active quizzes the student can take
        $activeQuizzes = collect();
        if (!empty($classGroupIds)) {
            $activeQuizzes = Quiz::whereIn('class_group_id', $classGroupIds)
                ->where('is_published', true)
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
                })
                ->where(function ($q) {
                    $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                })
                ->with('course')
                ->get()
                ->filter(fn (Quiz $q) => $q->hasEnoughApprovedQuestions() && $q->isActive())
                ->filter(function (Quiz $quiz) use ($student) {
                    // Only show if student hasn't completed it yet
                    $hasSession = QuizSession::where('quiz_id', $quiz->id)
                        ->where('student_index', $student->index_number)
                        ->whereNotNull('ended_at')
                        ->exists();
                    return !$hasSession;
                });
        }
        
        // Get completed quiz sessions
        $sessions = QuizSession::where('student_index', $student->index_number)
            ->with(['quiz', 'result'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('student.dashboard.quizzes', [
            'student' => $student,
            'sessions' => $sessions,
            'activeQuizzes' => $activeQuizzes,
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
        return redirect()->route('dashboard.my-profile')->with('success', 'Saved');
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
                return redirect()->route('dashboard.my-quizzes')->with('error', 'Error');
            }
            
            $quizSession = QuizSession::where('id', $sessionId)
                ->where('student_index', $student->index_number)
                ->with(['quiz.course', 'result', 'answers.question'])
                ->first();
            
            if (!$quizSession) {
                return redirect()->route('dashboard.my-quizzes')->with('error', 'Not found');
            }

            if (!$quizSession->quiz) {
                return redirect()->route('dashboard.my-quizzes')->with('error', 'Not found');
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
            return redirect()->route('dashboard.my-quizzes')->with('error', 'Error');
        }
    }

    /**
     * Download quiz result as PDF.
     */
    public function downloadPdf(Request $request, $sessionId): Response|RedirectResponse
    {
        try {
            $student = $this->student();
            
            // Validate session ID is numeric
            if (!is_numeric($sessionId)) {
                return redirect()->route('dashboard.my-quizzes')->with('error', 'Error');
            }
            
            $quizSession = QuizSession::where('id', $sessionId)
                ->where('student_index', $student->index_number)
                ->with(['quiz.course', 'result', 'answers.question'])
                ->first();
            
            if (!$quizSession) {
                return redirect()->route('dashboard.my-quizzes')->with('error', 'Not found');
            }

            if (!$quizSession->quiz) {
                return redirect()->route('dashboard.my-quizzes')->with('error', 'Not found');
            }

            if (!$quizSession->quiz->canShowScore() || !$quizSession->result) {
                return redirect()->route('dashboard.my-quizzes')->with('error', 'Not found');
            }

            $reviewAvailableWithinDays = 21;
            $showFullReview = $quizSession->created_at && $quizSession->created_at->gte(now()->subDays($reviewAvailableWithinDays));

            $html = view('student.dashboard.quiz-pdf', [
                'student' => $student,
                'session' => $quizSession,
                'showFullReview' => $showFullReview,
            ])->render();

            $filename = 'Quiz_Result_' . ($quizSession->quiz->title ?? 'Quiz') . '_' . ($quizSession->created_at ? $quizSession->created_at->format('Y-m-d') : date('Y-m-d')) . '.pdf';

            return response($html)
                ->header('Content-Type', 'text/html')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
        } catch (\Exception $e) {
            \Log::error('Error generating PDF: ' . $e->getMessage(), [
                'session_id' => $sessionId ?? null,
                'student_id' => $student->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('dashboard.my-quizzes')->with('error', 'Error');
        }
    }

    /**
     * Show course materials page with weekly content.
     */
    public function courseMaterials(): View
    {
        $student = $this->student();
        return view('student.dashboard.course-materials', [
            'student' => $student,
        ]);
    }
}
