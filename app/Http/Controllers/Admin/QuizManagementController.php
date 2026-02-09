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
use Illuminate\Http\StreamedResponse;
use Illuminate\Support\Facades\Schema;
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

    public function index(): View
    {
        $classGroupIds = $this->classGroupIds();
        $quizzes = Quiz::with(['course', 'classGroup'])
            ->when(!empty($classGroupIds), fn ($q) => $q->whereIn('class_group_id', $classGroupIds))
            ->orderByDesc('created_at')
            ->paginate(15);
        return view('admin.quizzes.index', compact('quizzes'));
    }

    public function create(): View
    {
        $this->authorize('create', Quiz::class);
        $classGroupIds = $this->classGroupIds();
        $classGroups = ClassGroup::with('courses')
            ->when(!empty($classGroupIds), fn ($q) => $q->whereIn('id', $classGroupIds))
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
            $this->authorize('update', $classGroup);
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

    public function show(Quiz $quiz): View
    {
        $this->authorize('view', $quiz);
        $quiz->load(['course', 'classGroup', 'questions', 'questionPools']);

        // Unapproved pools for Overview tab: paginated (50 per page)
        $unapprovedPoolsQuery = $quiz->questionPools()->where('is_approved', false)->orderBy('id');
        $unapprovedPoolsTotal = $unapprovedPoolsQuery->count();
        $unapprovedPools = $unapprovedPoolsQuery->paginate(50, ['*'], 'pool_page')->withQueryString();

        // Approved questions for Overview tab: paginated (50 per page)
        $approvedQuestionsQuery = $quiz->questions()->orderBy('id');
        $approvedQuestionsTotal = $approvedQuestionsQuery->count();
        $approvedQuestions = $approvedQuestionsQuery->paginate(50, ['*'], 'questions_page')->withQueryString();

        // Completed sessions for Sessions tab: paginated, with result and violations count
        $sessionsQuery = $quiz->sessions()
            ->with('result')
            ->whereNotNull('ended_at')
            ->orderByDesc('ended_at');
        $sessionsPaginator = $sessionsQuery->paginate(10)->withQueryString();

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

        return view('admin.quizzes.show', compact('quiz', 'unapprovedPools', 'unapprovedPoolsTotal', 'approvedQuestions', 'approvedQuestionsTotal', 'sessionsPaginator', 'sessionsStats'));
    }

    /**
     * Show session detail: result, faces, violation logs.
     * Logs admin view of face images for audit trail.
     */
    public function showSession(Quiz $quiz, QuizSession $session): View|RedirectResponse
    {
        $this->authorize('view', $quiz);
        if ($session->quiz_id !== $quiz->id) {
            abort(404);
        }
        $session->load(['quiz', 'result', 'violations']);

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
    public function resetSessionIp(Quiz $quiz, QuizSession $session): RedirectResponse
    {
        $this->authorize('update', $quiz);
        if ($session->quiz_id !== $quiz->id) {
            abort(404);
        }

        $session->update([
            'ip_address' => 'reset-' . $session->id . '-' . now()->timestamp,
            'session_token' => null,
        ]);

        return redirect()->route($this->staffRoutePrefix() . '.quizzes.sessions.show', [$quiz, $session])
            ->with('success', 'IP lock reset. The student can now retry from a new IP/session.');
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
        $courseName = $quiz->course ? $quiz->course->name : '—';
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

        $filename = 'score-report-' . \Illuminate\Support\Str::slug($quiz->title) . '-' . now()->format('Y-m-d') . '.pdf';

        if (request()->routeIs('*scores.export.pdf.preview')) {
            return $pdf->stream($filename);
        }
        return $pdf->download($filename);
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
     */
    public function exportScores(Quiz $quiz): StreamedResponse
    {
        $this->authorize('view', $quiz);

        $filename = 'quiz-scores-' . \Illuminate\Support\Str::slug($quiz->title) . '-' . now()->format('Y-m-d-His') . '.csv';

        return new StreamedResponse(function () use ($quiz) {
            $stream = fopen('php://output', 'w');
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
            fclose($stream);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export quiz violations as CSV. Restricted by course assignment via authorizeQuiz.
     */
    public function exportViolations(Quiz $quiz): StreamedResponse
    {
        $this->authorize('view', $quiz);

        $filename = 'quiz-violations-' . \Illuminate\Support\Str::slug($quiz->title) . '-' . now()->format('Y-m-d-His') . '.csv';

        return new StreamedResponse(function () use ($quiz) {
            $stream = fopen('php://output', 'w');
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
            fclose($stream);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
