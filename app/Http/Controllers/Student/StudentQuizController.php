<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Events\DataUpdated;
use App\Jobs\SendQuizResultReadyNotification;
use App\Models\Answer;
use App\Models\Question;
use App\Models\QuizSession;
use App\Models\QuizViolation;
use App\Models\Result;
use App\Models\Setting;
use App\Models\Student;
use App\Services\AiQuestionService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class StudentQuizController extends Controller
{
    /**
     * System readiness screen: course name, duration, tab warning, Start Quiz button.
     */
    public function ready(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $token = session('quiz_session_token');
        if (!$token) {
            return redirect()->route('student.landing')->with('error', 'No active quiz session. Please start from the quiz rules.');
        }
        $session = QuizSession::with('quiz.course')->where('session_token', $token)->firstOrFail();
        if ($session->ended_at) {
            return redirect()->to($this->quizCompleteUrl());
        }
        if ($session->start_time !== null) {
            return redirect()->route('student.quiz.show');
        }
        return view('student.quiz-ready', [
            'session' => $session,
            'courseName' => $session->quiz->course->name ?? $session->quiz->title,
            'durationMinutes' => $session->quiz->duration_minutes,
        ]);
    }

    /**
     * Show quiz interface (StudentQuiz): timer, questions, auto-save.
     * Session token resolved from session (not URL). Timer starts on first load (start_time set here if null).
     */
    public function show(Request $request): View|JsonResponse|\Illuminate\Http\Response
    {
        $token = session('quiz_session_token');
        if (!$token) {
            return redirect()->route('student.landing')->with('error', 'No active quiz session. Please start from the quiz rules.');
        }
        $session = QuizSession::with(['quiz', 'quiz.questions'])->where('session_token', $token)->firstOrFail();
        if ($session->ended_at) {
            return redirect()->to($this->quizCompleteUrl());
        }
        if ($session->start_time === null) {
            $session->update(['start_time' => now()]);
        }
        if ($this->isIpDeviceRestrictionEnabled() && $session->ip_address !== $request->ip()) {
            QuizViolation::create([
                'quiz_session_id' => $session->id,
                'type' => 'multiple_ip',
                'severity' => QuizViolation::severityForType('multiple_ip'),
                'metadata' => json_encode(['expected' => $session->ip_address, 'got' => $request->ip()]),
                'occurred_at' => now(),
            ]);
            return redirect()->route('student.landing')->with('error', 'Session invalid.');
        }
        $questionIds = $session->assigned_question_ids ?? [];
        $questions = collect();
        $shuffledOptionsByQuestion = $session->shuffled_question_options ?? [];
        if (!empty($questionIds)) {
            $ids = array_map('intval', $questionIds);
            $questions = Question::whereIn('id', $ids)->get();
            $questions = $questions->sortBy(fn ($q) => array_search($q->id, $ids))->values();
        }
        $durationSeconds = $session->quiz->duration_minutes * 60;
        $elapsed = now()->diffInSeconds($session->start_time);
        $remaining = max(0, $durationSeconds - $elapsed);
        if ($session->quiz->ends_at && $session->quiz->ends_at->isPast()) {
            return redirect()->to($this->resultUrlWithToken($token));
        }
        if ($remaining <= 0) {
            return redirect()->to($this->resultUrlWithToken($token));
        }
        $savedAnswers = $session->answers()->pluck('student_answer', 'question_id')->toArray();
        $totalQuestions = $questions->count();
        $answeredCount = $questions->filter(function ($q) use ($savedAnswers) {
            $a = $savedAnswers[$q->id] ?? '';
            return trim((string) $a) !== '';
        })->count();
        $perPage = $totalQuestions <= 20 ? 10 : 20;
        $totalPages = $totalQuestions > 0 ? (int) ceil($totalQuestions / $perPage) : 1;
        return response()
            ->view('student.quiz', [
                'session' => $session,
                'questions' => $questions,
                'shuffledOptionsByQuestion' => $shuffledOptionsByQuestion,
                'savedAnswers' => $savedAnswers,
                'answeredCount' => $answeredCount,
                'durationSeconds' => $durationSeconds,
                'remainingSeconds' => $remaining,
                'perPage' => $perPage,
                'totalPages' => $totalPages,
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    /**
     * Server time sync for quiz timer: returns server time, session start, and duration so client can correct drift.
     */
    public function timeSync(Request $request): JsonResponse
    {
        $token = session('quiz_session_token');
        if (!$token) {
            return response()->json(['error' => 'No session'], 401);
        }
        $session = QuizSession::with('quiz')->where('session_token', $token)->first();
        if (!$session || $session->ended_at) {
            return response()->json(['error' => 'Session ended or invalid'], 404);
        }
        $start = $session->start_time ? $session->start_time->timestamp : now()->timestamp;
        $durationSeconds = $session->quiz->duration_minutes * 60;
        $serverTime = now()->timestamp;
        $remaining = max(0, $durationSeconds - ($serverTime - $start));
        if ($session->quiz->ends_at && $session->quiz->ends_at->timestamp < $serverTime) {
            $remaining = 0;
        }
        return response()->json([
            'server_time' => $serverTime,
            'start_time' => $start,
            'duration_seconds' => $durationSeconds,
            'remaining_seconds' => (int) $remaining,
        ]);
    }

    /**
     * Auto-save single answer. Session resolved from HttpOnly session only.
     */
    public function saveAnswer(Request $request): JsonResponse
    {
        $token = session('quiz_session_token');
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Session expired.'], 401);
        }
        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'answer' => 'nullable|string',
        ]);
        $session = QuizSession::where('session_token', $token)->firstOrFail();
        if ($session->ended_at) {
            return response()->json(['success' => false, 'message' => 'Quiz ended.'], 403);
        }
        if ($this->isIpDeviceRestrictionEnabled() && $session->ip_address !== $request->ip()) {
            return response()->json(['success' => false], 403);
        }
        Answer::updateOrCreate(
            [
                'quiz_session_id' => $session->id,
                'question_id' => $request->question_id,
            ],
            [
                'student_answer' => $request->answer,
                'answered_at' => now(),
            ]
        );
        return response()->json(['success' => true]);
    }

    /**
     * Save multiple answers in one request. Session resolved from HttpOnly session only.
     */
    public function saveAnswersBatch(Request $request): JsonResponse
    {
        $token = session('quiz_session_token');
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Session expired.'], 401);
        }
        $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.answer' => 'nullable|string',
        ]);
        $session = QuizSession::where('session_token', $token)->firstOrFail();
        if ($session->ended_at) {
            return response()->json(['success' => false, 'message' => 'Quiz ended.'], 403);
        }
        if ($this->isIpDeviceRestrictionEnabled() && $session->ip_address !== $request->ip()) {
            return response()->json(['success' => false], 403);
        }
        foreach ($request->answers as $item) {
            Answer::updateOrCreate(
                ['quiz_session_id' => $session->id, 'question_id' => $item['question_id']],
                ['student_answer' => $item['answer'] ?? '', 'answered_at' => now()]
            );
        }
        return response()->json(['success' => true]);
    }

    /**
     * Record violation. Right-click = warn only (no auto-submit).
     * Auto-submit: (1) critical (copy_paste, multiple_ip) on first, or (2) tab switch/blur: first time = 20s delay; second time = immediate.
     */
    public function recordViolation(Request $request): JsonResponse
    {
        $token = session('quiz_session_token');
        if (!$token) {
            return response()->json(['success' => false], 401);
        }
        $request->validate([
            'type' => 'required|string|in:blur,tab_switch,copy_paste,right_click,window_resize,screenshot_attempt,other',
        ]);
        $session = QuizSession::where('session_token', $token)->firstOrFail();
        if ($session->ended_at) {
            return response()->json(['success' => true]);
        }
        $type = $request->type;
        $severity = QuizViolation::severityForType($type);
        QuizViolation::create([
            'quiz_session_id' => $session->id,
            'type' => $type,
            'severity' => $severity,
            'metadata' => $request->input('metadata'),
            'occurred_at' => now(),
        ]);
        $autoSubmitted = false;
        // Zero-tolerance (copy_paste, screenshot_attempt, multiple_ip): auto-submit on first, no warning
        if ($severity === QuizViolation::SEVERITY_CRITICAL) {
            $session->update([
                'post_face_skipped_at' => now(),
                'post_face_skipped_reason' => 'auto_submit',
                'auto_submit_after' => null,
            ]);
            $this->finalizeQuiz($session);
            $autoSubmitted = true;
        }
        // Major violations (blur, tab_switch, window_resize): max 1 warning per session, then auto-submit
        $majorTypes = ['blur', 'tab_switch', 'window_resize'];
        if (!$autoSubmitted && in_array($type, $majorTypes, true)) {
            $majorCount = $session->violations()->whereIn('type', $majorTypes)->count();
            if ($majorCount >= 2) {
                $session->update([
                    'post_face_skipped_at' => now(),
                    'post_face_skipped_reason' => 'auto_submit',
                    'auto_submit_after' => null,
                ]);
                $this->finalizeQuiz($session);
                $autoSubmitted = true;
            }
        }
        $response = ['success' => true, 'auto_submitted' => $autoSubmitted];
        if (!$autoSubmitted && in_array($type, $majorTypes, true)) {
            $majorCount = $session->violations()->whereIn('type', $majorTypes)->count();
            $response['show_major_warning'] = $majorCount === 1;
        }
        if ($autoSubmitted) {
            $response['redirect'] = $this->quizCompleteUrl();
        }
        return response()->json($response);
    }

    /**
     * Heartbeat when user returns to the quiz tab. Clears the 20-second auto-submit countdown; returns flag to show "next time immediate" popup.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $token = session('quiz_session_token');
        if (!$token) {
            return response()->json(['success' => false], 401);
        }
        $session = QuizSession::where('session_token', $token)->first();
        if (!$session || $session->ended_at) {
            return response()->json(['success' => true]);
        }
        $hadScheduledSubmit = $session->auto_submit_after !== null;
        $session->update(['auto_submit_after' => null]);
        return response()->json([
            'success' => true,
            'show_tab_switch_warning' => $hadScheduledSubmit,
        ]);
    }

    /**
     * Finalize quiz: compute score, create result. Session resolved from HttpOnly session only.
     */
    public function finalize(Request $request): JsonResponse
    {
        $token = session('quiz_session_token');
        if (!$token) {
            return response()->json(['success' => false], 401);
        }
        $session = QuizSession::with('quiz')->where('session_token', $token)->firstOrFail();
        if ($session->ended_at) {
            return response()->json(['success' => true, 'redirect' => $this->quizCompleteUrl()]);
        }
        if ($this->isIpDeviceRestrictionEnabled() && $session->ip_address !== $request->ip()) {
            return response()->json(['success' => false], 403);
        }
        if (!$session->post_face_image && !$session->post_face_skipped_reason) {
            return response()->json([
                'success' => false,
                'message' => 'Post-quiz photo is required. Please capture your photo before submitting.',
            ], 403);
        }
        $this->finalizeQuiz($session);
        return response()->json([
            'success' => true,
            'redirect' => $this->quizCompleteUrl(),
        ]);
    }

    /**
     * Quiz complete page: "Log in to see your results". Shown after normal submit or auto-submit.
     */
    public function quizComplete(): View
    {
        return view('student.quiz-complete');
    }

    /**
     * URL to show after quiz submit/auto-submit (no marks or review; student must log in).
     */
    private function quizCompleteUrl(): string
    {
        return route('student.quiz.complete');
    }

    /**
     * Result page URL with session token (for logged-in owner only; otherwise redirect to login prompt).
     */
    private function resultUrlWithToken(string $sessionToken): string
    {
        return route('student.result') . '?token=' . urlencode($sessionToken);
    }

    /**
     * Public entry point for finalizing a session (e.g. from scheduler/command).
     */
    public function finalizeQuizSession(QuizSession $session): void
    {
        $this->finalizeQuiz($session);
    }

    /**
     * Finalize quiz: score using assigned snapshot only. Do not re-query live questions table.
     */
    protected function finalizeQuiz(QuizSession $session): void
    {
        $session->update(['ended_at' => now()]);
        $lockedIds = $session->assigned_question_ids ?? [];
        $correctAnswersSnapshot = $session->assigned_correct_answers ?? [];
        $total = count($lockedIds);
        $correct = 0;
        
        if ($total > 0) {
            $answersByQuestion = $session->answers()->whereIn('question_id', $lockedIds)->pluck('student_answer', 'question_id')->toArray();
            
            foreach ($lockedIds as $qid) {
                // Try both integer and string keys
                $correctAnswer = $correctAnswersSnapshot[$qid] ?? $correctAnswersSnapshot[(string) $qid] ?? null;
                
                if ($correctAnswer === null) {
                    // Skip if no correct answer found for this question
                    continue;
                }
                
                $studentAnswer = $answersByQuestion[$qid] ?? $answersByQuestion[(string) $qid] ?? '';
                
                // Normalize both answers: trim whitespace, convert to string, uppercase for comparison
                $normalizedStudent = strtoupper(trim((string) $studentAnswer));
                $normalizedCorrect = strtoupper(trim((string) $correctAnswer));
                
                // Only count as correct if they match exactly after normalization
                if ($normalizedStudent === $normalizedCorrect && $normalizedStudent !== '') {
                    $correct++;
                }
            }
        }
        
        $violationsCount = $session->violations()->count();
        
        // Ensure score doesn't exceed 100% and correct count doesn't exceed total
        $correct = min($correct, $total);
        $score = $total > 0 ? round(100 * $correct / $total, 2) : 0;
        $score = min($score, 100.00); // Cap at 100%
        
        Result::create([
            'quiz_session_id' => $session->id,
            'score' => $score,
            'total_questions' => $total,
            'correct_count' => $correct,
            'violations_count' => $violationsCount,
            'submitted_at' => now(),
        ]);
        broadcast(new DataUpdated('dashboard'))->toOthers();
        SendQuizResultReadyNotification::dispatch($session->id);
    }

    private function isIpDeviceRestrictionEnabled(): bool
    {
        return Setting::getValue(Setting::KEY_DISABLE_IP_DEVICE_RESTRICTIONS, '0') !== '1';
    }

    /**
     * Result page. Marks and review are shown only when the visitor is logged in as the student who took the quiz.
     * Otherwise show "log in to see results". Session token from session or query (?token=).
     */
    public function result(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $token = session('quiz_session_token') ?? $request->query('token');
        if (!$token || !is_string($token)) {
            return redirect()->route('student.landing')->with('error', 'No quiz result found. Please complete a quiz first.');
        }
        $session = QuizSession::with(['quiz', 'result', 'answers.question'])->where('session_token', $token)->first();
        if (!$session) {
            return redirect()->route('student.landing')->with('error', 'No quiz result found. Please complete a quiz first.');
        }

        $studentId = session('student_id');
        $student = $studentId ? Student::find($studentId) : null;
        $isOwner = $student && strtoupper(trim((string) $student->index_number)) === strtoupper(trim((string) $session->student_index));

        if (!$isOwner) {
            return view('student.quiz-complete');
        }

        if (!session('quiz_session_token')) {
            session(['quiz_session_token' => $token]);
        }

        $assignedCorrect = $session->assigned_correct_answers ?? [];
        $aiService = app(AiQuestionService::class);
        foreach ($session->answers as $answer) {
            $q = $answer->question;
            if (!$q) {
                continue;
            }
            $sessionCorrect = $assignedCorrect[$q->id] ?? $assignedCorrect[(string) $q->id] ?? $q->correct_answer;
            $correct = trim((string) $answer->student_answer) === trim((string) $sessionCorrect);
            if ($correct) {
                continue;
            }
            $hasExplanation = !empty($q->explanation_wrong) || !empty($answer->explanation_wrong);
            if ($hasExplanation) {
                continue;
            }
            $generated = $aiService->generateWrongAnswerExplanation($q, (string) $answer->student_answer);
            if ($generated !== null && $generated !== '') {
                $answer->update(['explanation_wrong' => $generated]);
            }
        }

        $session->load(['quiz', 'result', 'answers.question']);
        return view('student.result', [
            'session' => $session,
            'resultUrl' => $this->resultUrlWithToken($token),
        ]);
    }
}
