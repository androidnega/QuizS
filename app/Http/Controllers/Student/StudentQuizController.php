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
            return redirect()->to($this->resultUrlWithToken($token));
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
            return redirect()->to($this->resultUrlWithToken($token));
        }
        if ($session->start_time === null) {
            $session->update(['start_time' => now()]);
        }
        if ($session->ip_address !== $request->ip()) {
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
        if ($session->ip_address !== $request->ip()) {
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
        if ($session->ip_address !== $request->ip()) {
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
            'type' => 'required|string|in:blur,tab_switch,copy_paste,right_click,window_resize,other',
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
        // Critical (copy_paste, multiple_ip): auto-submit on first
        if ($severity === QuizViolation::SEVERITY_CRITICAL) {
            $criticalCount = $session->violations()->where('severity', QuizViolation::SEVERITY_CRITICAL)->count();
            if ($criticalCount >= 1) {
                $session->update([
                    'post_face_skipped_at' => now(),
                    'post_face_skipped_reason' => 'auto_submit',
                    'auto_submit_after' => null,
                ]);
                $this->finalizeQuiz($session);
                $autoSubmitted = true;
            }
        }
        // Tab switch / blur: first time = schedule 20s; second time = immediate auto-submit
        if (!$autoSubmitted && in_array($type, ['blur', 'tab_switch'], true)) {
            $tabBlurCount = $session->violations()->whereIn('type', ['blur', 'tab_switch'])->count();
            if ($tabBlurCount >= 2) {
                $session->update([
                    'post_face_skipped_at' => now(),
                    'post_face_skipped_reason' => 'auto_submit',
                    'auto_submit_after' => null,
                ]);
                $this->finalizeQuiz($session);
                $autoSubmitted = true;
            } else {
                $session->update(['auto_submit_after' => now()->addSeconds(20)]);
            }
        }
        // Window resize / exit fullscreen: no auto-submit on first; auto-submit after limit (e.g. 3)
        $windowResizeLimit = 3;
        if (!$autoSubmitted && $type === 'window_resize') {
            $resizeCount = $session->violations()->where('type', 'window_resize')->count();
            if ($resizeCount >= $windowResizeLimit) {
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
        if ($type === 'window_resize' && !$autoSubmitted) {
            $resizeCount = $session->violations()->where('type', 'window_resize')->count();
            $response['window_resize_count'] = $resizeCount;
            $response['window_resize_limit'] = $windowResizeLimit;
            $response['next_violation_auto_submits'] = ($resizeCount + 1) >= $windowResizeLimit;
        }
        if ($autoSubmitted) {
            $response['redirect'] = $this->resultUrlWithToken($session->session_token);
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
            return response()->json(['success' => true, 'redirect' => $this->resultUrlWithToken($session->session_token)]);
        }
        if ($session->ip_address !== $request->ip()) {
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
            'redirect' => $this->resultUrlWithToken($session->session_token),
        ]);
    }

    /**
     * Result page URL with session token so student can open in new tab or return later and still see results.
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
                $correctAnswer = $correctAnswersSnapshot[$qid] ?? $correctAnswersSnapshot[(string) $qid] ?? null;
                if ($correctAnswer === null) {
                    continue;
                }
                $studentAnswer = $answersByQuestion[$qid] ?? '';
                if (trim((string) $studentAnswer) === trim((string) $correctAnswer)) {
                    $correct++;
                }
            }
        }
        $violationsCount = $session->violations()->count();
        Result::create([
            'quiz_session_id' => $session->id,
            'score' => $total > 0 ? round(100 * $correct / $total, 2) : 0,
            'total_questions' => $total,
            'correct_count' => $correct,
            'violations_count' => $violationsCount,
            'submitted_at' => now(),
        ]);
        broadcast(new DataUpdated('dashboard'))->toOthers();
        SendQuizResultReadyNotification::dispatch($session->id);
    }

    /**
     * Result page. Session token from session or from query (?token=) so link works in new tab or after session expiry.
     * For wrong answers without explanation_wrong, generates a short AI reason and saves to answer.
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
        // Persist token in session when loaded from URL so reload and in-page links work without query
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
