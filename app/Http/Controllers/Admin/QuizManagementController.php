<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\FaceImageViewLog;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\Question;
use App\Models\QuestionPool;
use App\Models\Setting;
use App\Exports\QuizScoresExport;
use App\Services\AiQuestionService;
use App\Services\CloudinaryService;
use App\Services\DocumentTextExtractor;
use App\Events\DataUpdated;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class QuizManagementController extends Controller
{
    use InteractsWithAdminSession;
    /** Course IDs the current examiner is assigned to (all for super_admin). */
    private function assignedCourseIds(): array
    {
        $user = $this->adminUser();
        return $user ? $user->assignedCourseIds() : [];
    }

    /** Class group IDs the current examiner owns (all for super_admin). */
    private function classGroupIds(): array
    {
        $user = $this->adminUser();
        return $user ? $user->classGroupIds() : [];
    }

    public function index(Request $request): View
    {
        $classGroupIds = $this->classGroupIds();
        $tab = $request->query('tab', 'active');
        $query = Quiz::with(['course', 'classGroup'])
            ->whereIn('class_group_id', $classGroupIds)
            ->orderByDesc('created_at');

        if ($tab === 'ended') {
            $query->ended();
        } else {
            $query->active();
        }

        $quizzes = $query->paginate(15)->withQueryString();
        return view('admin.quizzes.index', compact('quizzes', 'tab'));
    }

    public function create(): View
    {
        $this->authorize('create', Quiz::class);
        $classGroupIds = $this->classGroupIds();
        $classGroups = ClassGroup::with('courses')
            ->whereIn('id', $classGroupIds)
            ->withCount('students')
            ->orderBy('name')
            ->get()
            ->filter(fn (ClassGroup $g) => $g->students_count > 0);
        $aiApiAvailable = app(AiQuestionService::class)->hasApiKey();
        if ($classGroups->isEmpty()) {
            session()->flash('error', 'No class group with students available. Create a class group, attach courses, and add students first.');
        }
        return view('admin.quizzes.create', compact('classGroups', 'aiApiAvailable'));
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $user = $this->adminUser();
            if (!$user) {
                return redirect()->route('login')
                    ->with('error', 'Please log in to create a quiz.');
            }

            $request->validate([
                'title' => 'required|string|max:255',
                'exam_type' => 'nullable|in:quiz,midsem,end_of_semester',
                'class_group_id' => 'required|exists:class_groups,id',
                'course_id' => 'required|exists:courses,id',
                'number_of_questions' => 'required|integer|min:1|max:250',
                'questions_per_student' => 'required|integer|min:1|max:250',
                'duration_minutes' => 'required|integer|min:1|max:300',
                'topics' => 'nullable|string|max:1000',
                'is_active' => 'boolean',
                'starts_at' => 'nullable|date',
                'ends_at' => 'nullable|date|after_or_equal:starts_at',
                'result_visibility' => 'nullable|in:score_only,full_review_after_end,disabled',
            ]);

            $requestClassGroupId = (int) $request->class_group_id;
            $requestCourseId = (int) $request->course_id;
            $classGroup = ClassGroup::find($requestClassGroupId);
            if (! $classGroup) {
                return redirect()->route($this->staffRoutePrefix() . '.quizzes.create')
                    ->withInput()
                    ->with('error', 'Class group not found.');
            }
            if (! $user->isSuperAdmin() && (int) $classGroup->examiner_id !== (int) $user->id) {
                return redirect()->route($this->staffRoutePrefix() . '.quizzes.create')
                    ->withInput()
                    ->with('error', 'You can only create quizzes for your own class groups.');
            }
            if (! $classGroup || ! $classGroup->hasStudents()) {
                return redirect()->route($this->staffRoutePrefix() . '.quizzes.create')
                    ->withInput()
                    ->with('error', 'This class group has no students. Add students before creating a quiz.');
            }
            if (! $classGroup->courses()->where('courses.id', $requestCourseId)->exists()) {
                return redirect()->route($this->staffRoutePrefix() . '.quizzes.create')
                    ->withInput()
                    ->with('error', 'The selected course is not attached to this class group.');
            }

            $topics = $request->topics;
            if (is_string($topics) && $topics !== '') {
                $topics = array_map('trim', explode(',', $topics));
                $topics = array_filter($topics);
                $topics = array_map(fn ($t) => ['name' => $t], $topics);
            } else {
                $topics = [];
            }

            $createData = [
                'title' => $request->title,
                'exam_type' => $request->input('exam_type') ?: null,
                'class_group_id' => $requestClassGroupId,
                'course_id' => $requestCourseId,
                'number_of_questions' => (int) $request->number_of_questions,
                'duration_minutes' => (int) $request->duration_minutes,
                'topics' => !empty($topics) ? json_encode(array_values($topics)) : null,
                'is_active' => $request->boolean('is_active', true),
                'is_published' => false,
                'starts_at' => $request->filled('starts_at') ? $request->starts_at : null,
                'ends_at' => $request->filled('ends_at') ? $request->ends_at : null,
                'result_visibility' => $request->input('result_visibility', Quiz::RESULT_VISIBILITY_FULL_REVIEW_AFTER_END),
            ];
            if (Schema::hasColumn('quizzes', 'questions_per_student')) {
                $createData['questions_per_student'] = (int) $request->questions_per_student;
            }
            $quiz = Quiz::create($createData);

            if (!$quiz || !$quiz->id) {
                return redirect()->route($this->staffRoutePrefix() . '.quizzes.create')
                    ->withInput()
                    ->with('error', 'Failed to create quiz: the quiz was not saved. Please try again.');
            }

            // Generate AI questions only when API key is set; block and show clear message when missing
            $aiService = app(AiQuestionService::class);
            $poolCount = 0;
            if (!empty($topics)) {
                if (!$aiService->hasApiKey()) {
                    $message = 'Quiz created. AI generation was skipped: no API key is set. Add a Gemini or DeepSeek key in Dashboard → Settings, then generate from this quiz\'s edit page or add questions manually.';
                } else {
                    try {
                        $poolCount = count($aiService->generatePoolAndStore(
                            $quiz,
                            array_values($topics),
                            (int) $request->number_of_questions,
                            null
                        ));
                        if ($poolCount > 0) {
                            $message = "Quiz created successfully! {$poolCount} questions are in the pool — open this quiz and click \"Approve All\" to add them to the quiz.";
                            $flashKey = 'success';
                        } else {
                            $message = 'AI did not generate any questions. Check API keys in Dashboard → Settings (AI tab), try different topics, or add questions manually.';
                            $flashKey = 'error';
                        }
                    } catch (\Throwable $e) {
                        $message = 'Quiz created. AI generation failed: ' . $e->getMessage() . ' Add questions manually or try again.';
                        $flashKey = 'error';
                    }
                }
            } else {
                $message = 'Quiz created successfully! You can now add questions manually or use AI generation (set topics and ensure a Gemini or DeepSeek key in Settings).';
                $flashKey = 'success';
            }

            try {
                broadcast(new DataUpdated('quizzes'))->toOthers();
            } catch (\Exception $e) {
                // Ignore broadcast errors
            }

            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', ['quiz' => $quiz->id])->with($flashKey ?? 'success', $message);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.create')
                ->withInput()
                ->with('error', 'Quiz creation failed: ' . $e->getMessage());
        }
    }

    public function show(Request $request, string $quiz): View|Response|RedirectResponse
    {
        $quiz = Quiz::query()->find($quiz);
        if (! $quiz) {
            return redirect()->route('dashboard.quizzes.index')
                ->with('error', 'Quiz not found. It may have been removed or moved.');
        }

        $this->authorize('view', $quiz);
        $quiz->load(['course', 'classGroup', 'questions', 'questionPools']);

        // Unapproved pools for Overview tab: paginated (50 per page)
        $unapprovedPoolsQuery = $quiz->questionPools()->where('is_approved', false)->orderBy('id');
        $unapprovedPoolsTotal = $unapprovedPoolsQuery->count();
        $unapprovedPools = $unapprovedPoolsQuery->paginate(50, ['*'], 'pool_page')->withQueryString();

        // Approved questions for Overview tab: load all for live search
        $approvedQuestionsQuery = $quiz->questions()->orderBy('id');
        $approvedQuestionsTotal = $approvedQuestionsQuery->count();
        $approvedQuestions = $approvedQuestionsQuery->get();

        // Completed sessions for Sessions tab: load all for live search
        $sessionsQuery = $quiz->sessions()
            ->with('result')
            ->whereNotNull('ended_at')
            ->orderByDesc('ended_at');
        $sessionsPaginator = $sessionsQuery->get();

        // Stats for Sessions tab (from all completed sessions, not just current page)
        $completedSessions = $quiz->sessions()->whereNotNull('ended_at')->with(['result', 'violations'])->get();
        $scores = $completedSessions->pluck('result.score')->filter()->values();
        $sessionsStats = [
            'total_students' => $completedSessions->count(),
            'average_score' => $scores->isNotEmpty() ? round($scores->average(), 1) : 0,
            'highest_score' => $scores->isNotEmpty() ? $scores->max() : 0,
            'lowest_score' => $scores->isNotEmpty() ? $scores->min() : 0,
            'total_violations' => $completedSessions->sum(fn ($s) => $s->violations->count()),
            'students_with_violations' => $completedSessions->filter(fn ($s) => $s->violations->count() > 0)->count(),
        ];

        $data = compact('quiz', 'unapprovedPools', 'unapprovedPoolsTotal', 'approvedQuestions', 'approvedQuestionsTotal', 'sessionsPaginator', 'sessionsStats');

        // Live tab/pagination: return only the tab HTML fragment for AJAX requests
        if ($request->ajax()) {
            $tab = $request->get('tab');
            if (! in_array($tab, ['overview', 'sessions', 'scores'], true)) {
                $tab = 'overview'; // default so pagination (e.g. questions_page=2) returns overview partial, not full page
            }
            return response()->view('admin.quizzes.partials.' . $tab, $data);
        }

        return view('admin.quizzes.show', $data);
    }

    /**
     * Show session detail: result, faces, violation logs.
     * Logs admin view of face images for audit trail.
     * Route uses {quizSession} to avoid conflict with Laravel's session.
     */
    public function showSession(string $quizId, QuizSession $quizSession): View|RedirectResponse
    {
        $quiz = $quizSession->quiz;
        if (! $quiz) {
            abort(404);
        }
        $this->authorize('view', $quiz);
        // Handle stale/migrated links by redirecting to canonical quiz/session URL.
        if ((string) $quizId !== (string) $quiz->getRouteKey()) {
            return redirect()->route('dashboard.quizzes.sessions.show', [
                'quizId' => $quiz->getRouteKey(),
                'quizSession' => $quizSession->getRouteKey(),
            ]);
        }
        $session = $quizSession;
        $session->load(['quiz', 'result', 'violations' => fn ($q) => $q->orderBy('occurred_at')]);

        $admin = $this->adminUser();
        if ($admin) {
            $now = now();
            if ($session->pre_face_image) {
                FaceImageViewLog::create([
                    'admin_id' => $admin->id,
                    'quiz_session_id' => $session->id,
                    'image_type' => FaceImageViewLog::IMAGE_TYPE_PRE,
                    'viewed_at' => $now,
                ]);
            }
            if ($session->post_face_image) {
                FaceImageViewLog::create([
                    'admin_id' => $admin->id,
                    'quiz_session_id' => $session->id,
                    'image_type' => FaceImageViewLog::IMAGE_TYPE_POST,
                    'viewed_at' => $now,
                ]);
            }
        }

        return view('admin.sessions.show', compact('quiz', 'session'));
    }

    /**
     * Reset IP lock for a session (allow the IP to be used again).
     */
    public function resetSessionIp(string $quizId, QuizSession $quizSession): RedirectResponse
    {
        $quiz = $quizSession->quiz;
        if (! $quiz) {
            abort(404);
        }
        $this->authorize('update', $quiz);
        // If URL quiz is stale, move to canonical URL first.
        if ((string) $quizId !== (string) $quiz->getRouteKey()) {
            return redirect()->route('dashboard.quizzes.sessions.show', [
                'quizId' => $quiz->getRouteKey(),
                'quizSession' => $quizSession->getRouteKey(),
            ])->with('info', 'Session opened via updated quiz link.');
        }
        $session = $quizSession;

        $session->update([
            'ip_address' => 'reset-' . $session->id . '-' . now()->timestamp,
            'session_token' => null,
        ]);

        return redirect()->route($this->staffRoutePrefix() . '.quizzes.sessions.show', [$quiz, $session])
            ->with('success', 'IP lock reset. The student can now retry from a new IP/session.');
    }

    /**
     * Kill a session: delete the session and its result, allowing the student to retake the quiz.
     */
    public function killSession(string $quizId, QuizSession $quizSession): RedirectResponse
    {
        $quiz = $quizSession->quiz;
        if (! $quiz) {
            abort(404);
        }
        $this->authorize('update', $quiz);
        
        // If URL quiz is stale, move to canonical URL first.
        if ((string) $quizId !== (string) $quiz->getRouteKey()) {
            return redirect()->route('dashboard.quizzes.sessions.kill', [
                'quizId' => $quiz->getRouteKey(),
                'quizSession' => $quizSession->getRouteKey(),
            ]);
        }
        
        $studentIndex = $quizSession->student_index;
        
        // Delete the result first (if exists)
        $result = $quizSession->result;
        if ($result) {
            $result->delete();
        }
        
        // Delete the session (this will cascade to answers and violations via FK constraints)
        $quizSession->delete();
        
        broadcast(new DataUpdated('dashboard'))->toOthers();
        
        return redirect()
            ->route($this->staffRoutePrefix() . '.quizzes.show', ['quiz' => $quiz, 'tab' => 'sessions'])
            ->with('success', "Session killed for student {$studentIndex}. The student can now retake the quiz.");
    }

    /**
     * Delete completed quiz sessions within a date/time window so affected students can retake.
     * Cascades to answers/results/violations via FK constraints.
     */
    public function clearSessionsByRange(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);

        $validated = $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        $from = \Illuminate\Support\Carbon::parse($validated['from']);
        $to = \Illuminate\Support\Carbon::parse($validated['to']);

        $matching = $quiz->sessions()
            ->whereNotNull('ended_at')
            ->whereBetween('ended_at', [$from, $to])
            ->get(['id', 'student_index']);

        if ($matching->isEmpty()) {
            return redirect()
                ->route($this->staffRoutePrefix() . '.quizzes.show', ['quiz' => $quiz, 'tab' => 'sessions'])
                ->with('info', 'No completed sessions found in the selected date/time range.');
        }

        $ids = $matching->pluck('id')->all();
        $deletedSessions = count($ids);
        $affectedStudents = $matching->pluck('student_index')
            ->filter()
            ->map(fn ($idx) => strtoupper(trim((string) $idx)))
            ->unique()
            ->count();

        DB::transaction(function () use ($ids) {
            QuizSession::whereIn('id', $ids)->delete();
        });

        try {
            broadcast(new DataUpdated('quizzes'))->toOthers();
            broadcast(new DataUpdated('dashboard'))->toOthers();
        } catch (\Throwable $e) {
            // ignore broadcast failures
        }

        return redirect()
            ->route($this->staffRoutePrefix() . '.quizzes.show', ['quiz' => $quiz, 'tab' => 'sessions'])
            ->with('success', "Cleared {$deletedSessions} session(s) for {$affectedStudents} student(s). They can retake this quiz.");
    }

    /**
     * Approve a question pool item: create Question from it and mark pool as approved.
     */
    public function approvePool(Quiz $quiz, QuestionPool $pool): RedirectResponse
    {
        $this->authorize('update', $quiz);
        if ($quiz->hasStarted()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', 'This quiz has already been started. Editing is disabled.');
        }
        if ($pool->quiz_id !== $quiz->id) {
            abort(404);
        }
        if ($pool->is_approved) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('info', 'Already approved.');
        }
        Question::create([
            'quiz_id' => $quiz->id,
            'text' => $pool->question_text,
            'type' => 'mcq',
            'options' => $pool->options ?? [],
            'correct_answer' => $pool->correct_answer,
            'topic' => $pool->topic,
            'source' => 'ai',
            'points' => 1,
            'explanation_wrong' => $pool->explanation_wrong ?? null,
            'explanation_correct' => $pool->explanation_correct ?? null,
        ]);
        $pool->update(['is_approved' => true]);
        return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('success', 'Question approved and added to quiz.');
    }

    /**
     * Approve all unapproved question pool items for this quiz.
     */
    public function approveAllPool(Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);
        if ($quiz->hasStarted()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', 'This quiz has already been started. Editing is disabled.');
        }
        $pools = $quiz->questionPools()->where('is_approved', false)->get();
        $count = 0;
        foreach ($pools as $pool) {
            Question::create([
                'quiz_id' => $quiz->id,
                'text' => $pool->question_text,
                'type' => 'mcq',
                'options' => $pool->options ?? [],
                'correct_answer' => $pool->correct_answer,
                'topic' => $pool->topic,
                'source' => 'ai',
                'points' => 1,
                'explanation_wrong' => $pool->explanation_wrong ?? null,
                'explanation_correct' => $pool->explanation_correct ?? null,
            ]);
            $pool->update(['is_approved' => true]);
            $count++;
        }
        $message = $count > 0 ? "{$count} question(s) approved and added to quiz." : 'No unapproved questions in pool.';
        return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('success', $message);
    }

    /**
     * Publish quiz: make it visible on the student landing page.
     */
    public function publish(Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);
        if (!$quiz->hasEnoughApprovedQuestions()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', 'Cannot publish: quiz needs at least ' . $quiz->getQuestionsPerStudent() . ' approved questions.');
        }
        $quiz->update(['is_published' => true]);
        broadcast(new DataUpdated('quizzes'))->toOthers();
        return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('success', 'Quiz published successfully! Share the link with your students.');
    }

    /**
     * Unpublish quiz: remove it from the student landing page.
     * Only shown when quiz is published but not yet "open" (e.g. future starts_at).
     */
    public function unpublish(Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);
        $quiz->update(['is_published' => false]);
        broadcast(new DataUpdated('quizzes'))->toOthers();
        return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('success', 'Quiz unpublished successfully.');
    }

    /**
     * End quiz: set ends_at to now so students can no longer start or continue.
     * Shown when quiz is published and the quiz window is open (starts_at passed or null).
     */
    public function endQuiz(Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);
        $quiz->update(['ends_at' => now()]);
        broadcast(new DataUpdated('quizzes'))->toOthers();
        return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('success', 'Quiz ended. Students can no longer start or submit.');
    }

    /**
     * Extend quiz time while quiz is ongoing.
     * Adds additional minutes to the quiz duration.
     */
    public function extendTime(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);
        
        // Only allow extending time if quiz has started (has active sessions)
        if (!$quiz->hasStarted()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)
                ->with('error', 'Cannot extend time: quiz has not started yet.');
        }
        
        // Check if quiz has ended
        if ($quiz->hasEnded()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)
                ->with('error', 'Cannot extend time: quiz has already ended.');
        }
        
        $request->validate([
            'additional_minutes' => 'required|integer|min:1|max:120',
        ]);
        
        $additionalMinutes = (int) $request->input('additional_minutes');
        $newDuration = $quiz->duration_minutes + $additionalMinutes;
        
        // Cap at reasonable maximum (e.g., 600 minutes = 10 hours)
        if ($newDuration > 600) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)
                ->with('error', 'Total duration cannot exceed 600 minutes (10 hours).');
        }
        
        $quiz->update(['duration_minutes' => $newDuration]);
        
        // Broadcast update so students' timers refresh
        broadcast(new DataUpdated('quizzes'))->toOthers();
        
        return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)
            ->with('success', "Quiz time extended by {$additionalMinutes} minute(s). New duration: {$newDuration} minutes. Students' timers will update automatically.");
    }

    /**
     * Show edit form for an unapproved pool item.
     */
    public function editPool(Quiz $quiz, QuestionPool $pool): View|RedirectResponse
    {
        $this->authorize('view', $quiz);
        if ($quiz->hasStarted()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', 'This quiz has already been started. Editing is disabled.');
        }
        if ($pool->quiz_id !== $quiz->id) {
            abort(404);
        }
        if ($pool->is_approved) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('info', 'Already approved.');
        }
        return view('admin.quizzes.edit-pool', compact('quiz', 'pool'));
    }

    /**
     * Update an unapproved pool item.
     */
    public function updatePool(Request $request, Quiz $quiz, QuestionPool $pool): RedirectResponse
    {
        $this->authorize('update', $quiz);
        if ($quiz->hasStarted()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', 'This quiz has already been started. Editing is disabled.');
        }
        if ($pool->quiz_id !== $quiz->id || $pool->is_approved) {
            abort(404);
        }
        $request->validate([
            'question_text' => 'required|string|max:65535',
            'correct_answer' => 'required|string|in:A,B,C,D',
            'topic' => 'nullable|string|max:255',
            'option_a' => 'required|string|max:1000',
            'option_b' => 'required|string|max:1000',
            'option_c' => 'required|string|max:1000',
            'option_d' => 'required|string|max:1000',
        ]);
        $pool->update([
            'question_text' => $request->question_text,
            'options' => [
                ['key' => 'A', 'text' => $request->option_a],
                ['key' => 'B', 'text' => $request->option_b],
                ['key' => 'C', 'text' => $request->option_c],
                ['key' => 'D', 'text' => $request->option_d],
            ],
            'correct_answer' => $request->correct_answer,
            'topic' => $request->filled('topic') ? $request->topic : null,
        ]);
        return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('success', 'Question updated.');
    }

    /**
     * Reject (delete) an unapproved pool item.
     */
    public function rejectPool(Quiz $quiz, QuestionPool $pool): RedirectResponse
    {
        $this->authorize('update', $quiz);
        if ($quiz->hasStarted()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', 'This quiz has already been started. Editing is disabled.');
        }
        if ($pool->quiz_id !== $quiz->id) {
            abort(404);
        }
        if ($pool->is_approved) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('info', 'Already approved.');
        }
        $pool->delete();
        return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('success', 'Question rejected and removed from pool.');
    }

    /**
     * Show edit form for an approved question.
     */
    public function editQuestion(Quiz $quiz, Question $question): View|RedirectResponse
    {
        $this->authorize('view', $quiz);
        if ($quiz->hasStarted()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', 'This quiz has already been started. Editing is disabled.');
        }
        if ($question->quiz_id !== $quiz->id) {
            abort(404);
        }
        return view('admin.quizzes.edit-question', compact('quiz', 'question'));
    }

    /**
     * Update an approved question.
     */
    public function updateQuestion(Request $request, Quiz $quiz, Question $question): RedirectResponse
    {
        $this->authorize('update', $quiz);
        if ($quiz->hasStarted()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', 'This quiz has already been started. Editing is disabled.');
        }
        if ($question->quiz_id !== $quiz->id) {
            abort(404);
        }
        $request->validate([
            'text' => 'required|string|max:65535',
            'correct_answer' => 'required|string|in:A,B,C,D',
            'topic' => 'nullable|string|max:255',
            'option_a' => 'required|string|max:1000',
            'option_b' => 'required|string|max:1000',
            'option_c' => 'required|string|max:1000',
            'option_d' => 'required|string|max:1000',
        ]);
        $options = [
            ['key' => 'A', 'text' => $request->option_a],
            ['key' => 'B', 'text' => $request->option_b],
            ['key' => 'C', 'text' => $request->option_c],
            ['key' => 'D', 'text' => $request->option_d],
        ];
        $question->update([
            'text' => $request->text,
            'options' => $options,
            'correct_answer' => $request->correct_answer,
            'topic' => $request->filled('topic') ? $request->topic : null,
        ]);
        return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('success', 'Question updated.');
    }

    /**
     * Delete a quiz (and its sessions, questions, pools via cascade).
     * Only allowed when the quiz has not been started by any student.
     */
    public function destroy(Quiz $quiz): RedirectResponse
    {
        $this->authorize('delete', $quiz);
        if ($quiz->hasStarted()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', 'Cannot delete: this quiz has already been started by students.');
        }
        $title = $quiz->title;
        if ($quiz->script_public_id) {
            CloudinaryService::deleteRawByPublicId($quiz->script_public_id);
        }
        $quiz->delete();
        try {
            broadcast(new DataUpdated('quizzes'))->toOthers();
        } catch (\Exception $e) {
            // Ignore broadcast errors
        }
        return redirect()->route($this->staffRoutePrefix() . '.quizzes.index')->with('success', "Quiz \"{$title}\" has been deleted.");
    }

    /**
     * Delete an approved question. Blocked if any active (non-ended) session has this question in its snapshot.
     */
    public function destroyQuestion(Quiz $quiz, Question $question): RedirectResponse
    {
        $this->authorize('update', $quiz);
        if ($question->quiz_id !== $quiz->id) {
            abort(404);
        }
        $assignedToActiveSession = QuizSession::where('quiz_id', $quiz->id)
            ->whereNull('ended_at')
            ->whereJsonContains('assigned_question_ids', (int) $question->id)
            ->exists();
        if ($assignedToActiveSession) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', 'Cannot delete: this question is assigned to an active or in-progress session. Wait until all sessions are ended.');
        }
        if ($quiz->hasStarted()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', 'This quiz has already been started. Editing is disabled.');
        }
        $question->delete();
        return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('success', 'Question removed.');
    }

    public function edit(Quiz $quiz): View|RedirectResponse
    {
        $this->authorize('view', $quiz);
        if ($quiz->hasStarted()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', 'This quiz has already been started. Editing is disabled.');
        }
        $quiz->load('classGroup.courses');
        $courses = $quiz->classGroup ? $quiz->classGroup->courses()->orderBy('name')->get() : collect();
        $aiApiAvailable = app(AiQuestionService::class)->hasApiKey();
        return view('admin.quizzes.edit', compact('quiz', 'courses', 'aiApiAvailable'));
    }

    public function update(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);
        if ($quiz->hasStarted()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', 'This quiz has already been started. Editing is disabled.');
        }
        $request->validate([
            'title' => 'required|string|max:255',
            'exam_type' => 'nullable|in:quiz,midsem,end_of_semester',
            'course_id' => 'required|exists:courses,id',
            'number_of_questions' => 'required|integer|min:1|max:250',
            'questions_per_student' => 'required|integer|min:1|max:250',
            'duration_minutes' => 'required|integer|min:1|max:300',
            'topics' => 'nullable|string',
            'source_script' => 'nullable|string|max:100000',
            'source_file' => 'nullable|file|mimes:txt,pdf,docx|max:10240',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
            'result_visibility' => 'nullable|in:score_only,full_review_after_end,disabled',
        ]);
        $requestCourseId = (int) $request->course_id;
        $classGroup = $quiz->classGroup;
        if (! $classGroup || ! $classGroup->courses()->where('courses.id', $requestCourseId)->exists()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.edit', $quiz)
                ->withInput()
                ->with('error', 'The selected course must be attached to this quiz\'s class group.');
        }
        $topics = $request->topics;
        if (is_string($topics) && $topics !== '') {
            $topics = array_map('trim', explode(',', $topics));
            $topics = array_map(fn ($t) => ['name' => $t], $topics);
        } else {
            $topics = null;
        }
        $scriptUrl = $quiz->script_url;
        $scriptPublicId = $quiz->script_public_id;
        $scriptText = $quiz->script_text;
        if ($request->filled('source_script') && trim($request->source_script) !== '') {
            $scriptText = trim($request->source_script);
            $scriptUrl = null;
            $scriptPublicId = null;
        } elseif ($request->hasFile('source_file')) {
            $file = $request->file('source_file');
            $uploaded = CloudinaryService::uploadRawFromFile($file);
            if ($uploaded) {
                $scriptUrl = $uploaded['url'];
                $scriptPublicId = $uploaded['public_id'];
                $scriptText = app(DocumentTextExtractor::class)->extract($file);
            } else {
                return redirect()->route($this->staffRoutePrefix() . '.quizzes.edit', $quiz)
                    ->withInput()
                    ->with('error', 'Failed to upload script to Cloudinary. Check settings or try again.');
            }
        }
        $updateData = [
            'title' => $request->title,
            'exam_type' => $request->input('exam_type') ?: null,
            'course_id' => $request->course_id,
            'number_of_questions' => $request->number_of_questions,
            'duration_minutes' => $request->duration_minutes,
            'topics' => is_array($topics) ? json_encode($topics) : null,
            'script_url' => $scriptUrl,
            'script_public_id' => $scriptPublicId,
            'script_text' => $scriptText,
            'is_active' => $request->boolean('is_active', true),
            'starts_at' => $request->filled('starts_at') ? $request->starts_at : null,
            'ends_at' => $request->filled('ends_at') ? $request->ends_at : null,
            'result_visibility' => $request->input('result_visibility', $quiz->result_visibility ?? Quiz::RESULT_VISIBILITY_FULL_REVIEW_AFTER_END),
        ];
        if (Schema::hasColumn('quizzes', 'questions_per_student')) {
            $updateData['questions_per_student'] = (int) $request->questions_per_student;
        }
        $quiz->update($updateData);
        $sourceText = $scriptText;
        if ($sourceText === null) {
            $sourceText = '';
        }
        if ($sourceText !== null && $sourceText !== '') {
            $aiService = app(AiQuestionService::class);
            if (!$aiService->hasApiKey()) {
                return redirect()->route($this->staffRoutePrefix() . '.quizzes.edit', $quiz)
                    ->withInput()
                    ->with('error', 'AI generation requires a Gemini or DeepSeek API key. Add one in Dashboard → Settings, then try again.');
            }
            try {
                $poolCount = count($aiService->generatePoolAndStore(
                    $quiz,
                    is_array($topics) && !empty($topics) ? array_values($topics) : [['name' => 'General knowledge']],
                    (int) $quiz->number_of_questions,
                    $sourceText
                ));
                broadcast(new DataUpdated('quizzes'))->toOthers();
                if ($poolCount > 0) {
                    return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)
                        ->with('success', "Quiz updated. {$poolCount} question(s) generated from uploaded file and awaiting approval.");
                }
                return redirect()->route($this->staffRoutePrefix() . '.quizzes.edit', $quiz)
                    ->withInput()
                    ->with('error', 'AI did not generate any questions. Check API keys in Dashboard → Settings (AI tab), try different source/topics, or add questions manually.');
            } catch (\Throwable $e) {
                return redirect()->route($this->staffRoutePrefix() . '.quizzes.edit', $quiz)
                    ->withInput()
                    ->with('error', 'AI generation failed: ' . $e->getMessage());
            }
        }
        broadcast(new DataUpdated('quizzes'))->toOthers();
        return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('success', 'Quiz updated.');
    }

    /**
     * Show scores page: all students who took the quiz with their scores and violations.
     */
    public function scores(Quiz $quiz): View
    {
        $this->authorize('view', $quiz);
        
        // Load sessions with results and violations, only for sessions that have been completed
        $sessions = $quiz->sessions()
            ->with(['result', 'violations'])
            ->whereNotNull('ended_at')
            ->orderByDesc('ended_at')
            ->get();
        
        // Calculate statistics
        $totalStudents = $sessions->count();
        $completedWithResults = $sessions->filter(fn($s) => $s->result !== null)->count();
        
        $scores = $sessions->pluck('result.score')->filter()->values();
        $averageScore = $scores->isNotEmpty() ? round($scores->average(), 1) : 0;
        $highestScore = $scores->isNotEmpty() ? $scores->max() : 0;
        $lowestScore = $scores->isNotEmpty() ? $scores->min() : 0;
        
        $totalViolations = $sessions->sum(fn($s) => $s->violations->count());
        $studentsWithViolations = $sessions->filter(fn($s) => $s->violations->count() > 0)->count();
        
        $stats = [
            'total_students' => $totalStudents,
            'completed_with_results' => $completedWithResults,
            'average_score' => $averageScore,
            'highest_score' => $highestScore,
            'lowest_score' => $lowestScore,
            'total_violations' => $totalViolations,
            'students_with_violations' => $studentsWithViolations,
        ];
        
        return view('admin.quizzes.scores', compact('quiz', 'sessions', 'stats'));
    }

    /**
     * Export quiz results (scores) as PDF. Preview (inline) or download.
     */
    public function exportScoresPdf(Quiz $quiz, Request $request): Response
    {
        $this->authorize('view', $quiz);
        $quiz->load(['classGroup', 'course']);

        $sessions = $quiz->sessions()
            ->with(['result', 'violations'])
            ->whereNotNull('ended_at')
            ->orderBy('student_index')
            ->get();

        $lecturer = $this->adminUser();
        $lecturerName = $lecturer ? ($lecturer->name ?: $lecturer->username) : '—';
        $courseName = '—';
        if ($quiz->course) {
            $code = trim($quiz->course->code ?? '');
            $name = trim($quiz->course->name ?? '');
            $courseName = $code && $name ? $code . ' – ' . $name : ($name ?: $code ?: '—');
        }
        $examTypeLabel = $quiz->getExamTypeLabel();
        $reportDate = $quiz->ended_at ? $quiz->ended_at->format('F j, Y') : now()->format('F j, Y');
        $institutionName = Setting::getValue(Setting::KEY_INSTITUTION_NAME, '');
        $logoPath = Setting::getValue(Setting::KEY_INSTITUTION_LOGO, '');
        $institutionLogoPath = null;
        if ($logoPath) {
            if (str_starts_with($logoPath, 'http')) {
                try {
                    $response = \Illuminate\Support\Facades\Http::timeout(10)->get($logoPath);
                    if ($response->successful()) {
                        $body = $response->body();
                        $mime = $response->header('Content-Type') ?: 'image/png';
                        $institutionLogoPath = 'data:' . (explode(';', $mime)[0] ?: 'image/png') . ';base64,' . base64_encode($body);
                    }
                } catch (\Throwable $e) {
                    // omit logo on fetch failure
                }
            } else {
                $fullPath = storage_path('app/public/' . $logoPath);
                if (file_exists($fullPath)) {
                    $mime = @mime_content_type($fullPath) ?: 'image/png';
                    $institutionLogoPath = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
                }
            }
        }

        $classGroupName = $quiz->classGroup ? $quiz->classGroup->name : '—';
        $pdf = Pdf::loadView('admin.quizzes.scores-export-pdf', [
            'quiz' => $quiz,
            'sessions' => $sessions,
            'lecturerName' => $lecturerName,
            'courseName' => $courseName,
            'classGroupName' => $classGroupName,
            'examTypeLabel' => $examTypeLabel,
            'reportDate' => $reportDate,
            'institutionName' => $institutionName,
            'institutionLogoPath' => $institutionLogoPath,
        ])->setPaper('a4', 'portrait')->setWarnings(false);

        $groupSlug = \Illuminate\Support\Str::slug($classGroupName ?: 'group');
        $courseSlug = \Illuminate\Support\Str::slug($courseName ?: 'course');
        $dateStr = now()->format('Y-m-d');
        $filename = $groupSlug . '-' . $courseSlug . '-' . $dateStr . '.pdf';

        if (request()->routeIs('*scores.export.pdf.preview')) {
            return $pdf->stream($filename);
        }
        return $pdf->download($filename);
    }

    /**
     * Export quiz questions as TXT in exam format.
     */
    public function exportQuestionsTxt(Quiz $quiz): Response
    {
        $this->authorize('view', $quiz);
        $quiz->load(['course', 'classGroup', 'questions']);
        
        $questions = $quiz->questions()->orderBy('id')->get();
        
        $lecturer = $this->adminUser();
        $lecturerName = $lecturer ? ($lecturer->name ?: $lecturer->username) : '—';
        
        $courseName = '—';
        $courseCode = '—';
        if ($quiz->course) {
            $courseCode = trim($quiz->course->code ?? '');
            $courseName = trim($quiz->course->name ?? '');
        }
        
        // Format date properly - use ends_at if available, otherwise starts_at, otherwise current date
        if ($quiz->ends_at) {
            $examDate = $quiz->ends_at->format('F j, Y');
        } elseif ($quiz->starts_at) {
            $examDate = $quiz->starts_at->format('F j, Y');
        } else {
            $examDate = now()->format('F j, Y');
        }
        
        // Format duration properly - show as MINUTES if less than 60, otherwise HOURS
        $durationMinutes = $quiz->duration_minutes ?? 120;
        if ($durationMinutes < 60) {
            $duration = $durationMinutes . ' MINUTE' . ($durationMinutes > 1 ? 'S' : '');
        } else {
            $hours = floor($durationMinutes / 60);
            $minutes = $durationMinutes % 60;
            if ($hours > 0 && $minutes > 0) {
                $duration = $hours . ' HOUR' . ($hours > 1 ? 'S' : '') . ' ' . $minutes . ' MINUTE' . ($minutes > 1 ? 'S' : '');
            } elseif ($hours > 0) {
                $duration = $hours . ' HOUR' . ($hours > 1 ? 'S' : '');
            } else {
                $duration = $minutes . ' MINUTE' . ($minutes > 1 ? 'S' : '');
            }
        }
        
        $institutionName = Setting::getValue(Setting::KEY_INSTITUTION_NAME, 'TAKORADI TECHNICAL UNIVERSITY');
        
        $classGroupName = $quiz->classGroup ? $quiz->classGroup->name : '—';
        $programme = $classGroupName !== '—' ? strtoupper($classGroupName) : '—';
        
        // Get exam year (current year / next year format)
        $currentYear = now()->format('Y');
        $nextYear = now()->addYear()->format('y');
        $examYear = $currentYear . '/' . $nextYear;
        
        try {
            // Build text content
            $content = [];
            
            // Header
            $content[] = str_pad('', 80, ' ', STR_PAD_BOTH);
            $content[] = strtoupper($institutionName);
            $content[] = 'FACULTY OF APPLIED ARTS AND TECHNOLOGY';
            $content[] = 'DEPARTMENT OF COMPUTER SCIENCE';
            $content[] = 'END OF FIRST SEMESTER EXAMINATIONS, ' . $examYear;
            $content[] = 'PROGRAMME: ' . $programme;
            $content[] = '';
            
            // Course info
            $content[] = 'COURSE TITLE: ' . strtoupper($courseName) . str_pad('COURSE CODE: ' . strtoupper($courseCode), 80 - strlen('COURSE TITLE: ' . strtoupper($courseName)), ' ', STR_PAD_LEFT);
            $content[] = 'DATE: ' . strtoupper($examDate) . str_pad('DURATION: ' . strtoupper($duration), 80 - strlen('DATE: ' . strtoupper($examDate)), ' ', STR_PAD_LEFT);
            $content[] = '';
            
            // Instructions
            $content[] = 'INSTRUCTIONS:';
            $content[] = 'Answer all questions. Each question carries equal marks. Write clearly and legibly.';
            $content[] = '';
            
            // Questions
            $answerKey = [];
            foreach ($questions as $idx => $question) {
                $content[] = ($idx + 1) . '. ' . $question->text;
                
                if ($question->options && is_array($question->options) && count($question->options) > 0) {
                    foreach ($question->options as $option) {
                        if (isset($option['key']) && isset($option['text'])) {
                            $isCorrect = isset($question->correct_answer) && $option['key'] === $question->correct_answer;
                            $marker = $isCorrect ? ' ***' : '';
                            $content[] = '   ' . $option['key'] . '. ' . $option['text'] . $marker;
                        }
                    }
                } else {
                    // For non-MCQ questions, show the correct answer directly
                    if ($question->correct_answer) {
                        $content[] = '   Answer: ' . $question->correct_answer;
                    }
                }
                
                // Add to answer key
                if ($question->correct_answer) {
                    $answerKey[] = ($idx + 1) . '. ' . $question->correct_answer;
                } else {
                    $answerKey[] = ($idx + 1) . '. (No answer specified)';
                }
                
                $content[] = '';
            }
            
            // Answer Key Section
            $content[] = '';
            $content[] = str_repeat('=', 80);
            $content[] = 'ANSWER KEY';
            $content[] = str_repeat('=', 80);
            $content[] = '';
            foreach ($answerKey as $answer) {
                $content[] = $answer;
            }
            
            // Footer
            $content[] = '';
            $content[] = str_pad('Generated ' . now()->format('M d, Y H:i') . ' — QuizSnap', 80, ' ', STR_PAD_BOTH);
            
            // Join content with newlines
            $textContent = implode("\n", $content);
            
            // Create temporary file with .txt extension
            $tempDir = sys_get_temp_dir();
            $tempFile = $tempDir . DIRECTORY_SEPARATOR . 'questions_' . uniqid() . '.txt';
            
            // Write content to file
            file_put_contents($tempFile, $textContent);
            
            // Verify file was created
            if (!file_exists($tempFile)) {
                throw new \Exception('Failed to create TXT file');
            }
            
            // Generate filename with class name
            $classSlug = $classGroupName !== '—' ? \Illuminate\Support\Str::slug($classGroupName) : 'class';
            $courseSlug = \Illuminate\Support\Str::slug($courseName ?: 'course');
            $dateStr = now()->format('Y-m-d');
            $filename = $classSlug . '-' . $courseSlug . '-questions-' . $dateStr . '.txt';
            
            // Return download with proper headers to force .txt download
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'text/plain',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ])->deleteFileAfterSend(true);
            
        } catch (\Throwable $e) {
            // Clean up temp file if it exists
            if (isset($tempFile) && file_exists($tempFile)) {
                @unlink($tempFile);
            }
            abort(500, 'Failed to generate TXT file: ' . $e->getMessage());
        }
    }

    /**
     * Export quiz results (scores) as Excel.
     */
    public function exportScoresExcel(Quiz $quiz): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorize('view', $quiz);
        $filename = 'quiz-scores-' . \Illuminate\Support\Str::slug($quiz->title) . '-' . now()->format('Y-m-d-His') . '.xlsx';
        return Excel::download(new QuizScoresExport($quiz), $filename);
    }

    /**
     * Export quiz results (scores) as CSV. Restricted by course assignment via authorizeQuiz.
     * Buffered response with Content-Length to avoid ERR_INCOMPLETE_CHUNKED_ENCODING.
     */
    public function exportScores(Quiz $quiz): Response
    {
        $this->authorize('view', $quiz);

        $filename = 'quiz-scores-' . \Illuminate\Support\Str::slug($quiz->title) . '-' . now()->format('Y-m-d-His') . '.csv';

        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, [
            'Student Index',
            'Score %',
            'Total Questions',
            'Correct Count',
            'Violations Count',
            'Submitted At',
        ]);

        $sessions = $quiz->sessions()
            ->with('result')
            ->whereNotNull('ended_at')
            ->orderBy('student_index')
            ->get();

        foreach ($sessions as $session) {
            $result = $session->result;
            fputcsv($stream, [
                $session->student_index,
                $result ? (string) $result->score : '',
                $result ? (string) $result->total_questions : '',
                $result ? (string) $result->correct_count : '',
                $result ? (string) $result->violations_count : '',
                $result && $result->submitted_at ? $result->submitted_at->toIso8601String() : '',
            ]);
        }
        rewind($stream);
        $body = stream_get_contents($stream);
        fclose($stream);

        return new Response($body, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => (string) strlen($body),
        ]);
    }

    /**
     * Export quiz violations as CSV. Restricted by course assignment via authorizeQuiz.
     * Buffered response with Content-Length to avoid ERR_INCOMPLETE_CHUNKED_ENCODING.
     */
    public function exportViolations(Quiz $quiz): Response
    {
        $this->authorize('view', $quiz);

        $filename = 'quiz-violations-' . \Illuminate\Support\Str::slug($quiz->title) . '-' . now()->format('Y-m-d-His') . '.csv';

        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, [
            'Student Index',
            'Session ID',
            'Type',
            'Severity',
            'Occurred At',
            'Metadata',
        ]);

        $violations = \App\Models\QuizViolation::query()
            ->whereHas('quizSession', fn ($q) => $q->where('quiz_id', $quiz->id))
            ->with('quizSession:id,student_index')
            ->orderBy('occurred_at')
            ->get();

        foreach ($violations as $v) {
            $session = $v->quizSession;
            fputcsv($stream, [
                $session ? $session->student_index : '',
                (string) $v->quiz_session_id,
                $v->type ?? '',
                $v->severity ?? 'warning',
                $v->occurred_at ? $v->occurred_at->toIso8601String() : '',
                $v->metadata ? (is_string($v->metadata) ? $v->metadata : json_encode($v->metadata)) : '',
            ]);
        }
        rewind($stream);
        $body = stream_get_contents($stream);
        fclose($stream);

        return new Response($body, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => (string) strlen($body),
        ]);
    }
}
