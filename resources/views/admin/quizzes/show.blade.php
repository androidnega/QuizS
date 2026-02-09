@extends('layouts.dashboard')

@section('title', $quiz->title)
@section('dashboard_heading', \Illuminate\Support\Str::limit($quiz->title, 40))

@section('dashboard_content')
@php $activeTab = request('tab', 'overview'); @endphp
<div class="w-full min-w-0 space-y-4">
    {{-- Compact header with tabs integrated --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                        <span>{{ $quiz->course->name ?? '-' }}</span>
                        <span>•</span>
                        <span>{{ $quiz->getQuestionsPerStudent() }} per student</span>
                        <span>•</span>
                        <span>{{ $quiz->duration_minutes }} min</span>
                    </div>
                    <div class="flex flex-wrap gap-1">
                        @if($quiz->is_active && $quiz->hasEnoughApprovedQuestions())
                            <span class="inline-flex px-1.5 py-0.5 text-xs font-medium rounded bg-success-100 text-success-700">Active</span>
                        @elseif($quiz->is_active && !$quiz->hasEnoughApprovedQuestions())
                            <span class="inline-flex px-1.5 py-0.5 text-xs font-medium rounded bg-warning-100 text-warning-700">Locked</span>
                        @else
                            <span class="inline-flex px-1.5 py-0.5 text-xs font-medium rounded bg-gray-100 text-gray-600">Inactive</span>
                        @endif
                        @if($quiz->is_published)
                            <span class="inline-flex px-1.5 py-0.5 text-xs font-medium rounded bg-primary-100 text-primary-700">Published</span>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    @if(!$quiz->hasStarted())
                        <a href="{{ route('dashboard.quizzes.edit', $quiz) }}" class="btn btn-primary text-xs px-3 py-1.5">Edit</a>
                        <form action="{{ route('dashboard.quizzes.destroy', $quiz) }}" method="post" class="inline" onsubmit="return confirm('Delete this quiz?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn bg-danger-100 text-danger-700 hover:bg-danger-200 text-xs px-3 py-1.5">Delete</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
        
        {{-- Tabs --}}
        <div class="border-b border-gray-100 bg-gray-50">
            <nav class="flex px-4 gap-1" aria-label="Quiz sections">
                <a href="{{ route('dashboard.quizzes.show', ['quiz' => $quiz, 'tab' => 'overview']) }}"
                   class="py-3 px-4 text-sm font-semibold whitespace-nowrap border-b-3 transition-all flex items-center gap-2 {{ $activeTab === 'overview' ? 'border-primary-500 text-primary-700 bg-white shadow-sm' : 'border-transparent text-gray-600 hover:text-gray-900 hover:bg-white/70' }}">
                    <i class="fas fa-info-circle"></i>
                    <span>Overview</span>
                </a>
                <a href="{{ route('dashboard.quizzes.show', ['quiz' => $quiz, 'tab' => 'sessions']) }}"
                   class="py-3 px-4 text-sm font-semibold whitespace-nowrap border-b-3 transition-all flex items-center gap-2 {{ $activeTab === 'sessions' ? 'border-primary-500 text-primary-700 bg-white shadow-sm' : 'border-transparent text-gray-600 hover:text-gray-900 hover:bg-white/70' }}">
                    <i class="fas fa-users"></i>
                    <span>Sessions</span>
                    @if($sessionsStats['total_students'] > 0)
                        <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-primary-100 text-primary-700">{{ $sessionsStats['total_students'] }}</span>
                    @endif
                </a>
                <a href="{{ route('dashboard.quizzes.show', ['quiz' => $quiz, 'tab' => 'scores']) }}"
                   class="py-3 px-4 text-sm font-semibold whitespace-nowrap border-b-3 transition-all flex items-center gap-2 {{ $activeTab === 'scores' ? 'border-primary-500 text-primary-700 bg-white shadow-sm' : 'border-transparent text-gray-600 hover:text-gray-900 hover:bg-white/70' }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Scores &amp; Export</span>
                </a>
            </nav>
        </div>
    </div>

    @if($activeTab === 'overview')
    @php
        $approvedCount = $quiz->questions()->count();
        $neededCount = $quiz->getQuestionsPerStudent();
        $shortBy = max(0, $neededCount - $approvedCount);
    @endphp
    {{-- Action Required: unapproved questions in pool --}}
    @if(!$quiz->is_published && !$quiz->hasEnoughApprovedQuestions() && $unapprovedPoolsTotal > 0)
        <div class="bg-primary-50 border-2 border-primary-300 rounded-lg p-4 flex items-start gap-3">
            <svg class="w-6 h-6 text-primary-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div class="flex-1">
                <p class="font-semibold text-primary-900 mb-1">Action Required: Approve Generated Questions</p>
                <p class="text-sm text-primary-800">
                    You have <strong>{{ $unapprovedPoolsTotal }} generated question(s)</strong> waiting for approval below. 
                    Click <strong>"Approve All ({{ $unapprovedPoolsTotal }})"</strong> to add them to your quiz.
                    You need at least <strong>{{ $neededCount }} approved</strong> (currently {{ $approvedCount }}).
                </p>
            </div>
        </div>
    @endif
    {{-- Short on questions and nothing in pool: show how to add more or use current count --}}
    @if(!$quiz->is_published && !$quiz->hasEnoughApprovedQuestions() && $unapprovedPoolsTotal === 0 && $shortBy > 0)
        <div class="bg-warning-50 border-2 border-warning-300 rounded-lg p-4 flex items-start gap-3">
            <svg class="w-6 h-6 text-warning-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92z" clip-rule="evenodd"/>
            </svg>
            <div class="flex-1">
                <p class="font-semibold text-warning-900 mb-1">Need {{ $shortBy }} more question(s) to publish</p>
                <p class="text-sm text-warning-800 mb-3">
                    This quiz is set to require <strong>{{ $neededCount }} questions</strong>. You have <strong>{{ $approvedCount }} approved</strong>. 
                    There are no questions waiting in the pool — the "other {{ $shortBy }}" were never added.
                </p>
                <p class="text-sm text-warning-800 mb-2">You can:</p>
                <ul class="text-sm text-warning-800 list-disc list-inside space-y-1 mb-3">
                    <li><strong>Add {{ $shortBy }} more:</strong> Edit the quiz and use AI to generate {{ $shortBy }} more questions (or add manually), then approve them.</li>
                    <li><strong>Use {{ $approvedCount }} questions:</strong> Edit the quiz and set "Questions per student" to {{ $approvedCount }} (or less) so you can publish with what you have.</li>
                </ul>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('dashboard.quizzes.edit', $quiz) }}" class="btn btn-primary text-sm">Edit quiz (add more or change required number)</a>
                </div>
            </div>
        </div>
    @endif
    
    {{-- Publish / End quiz or Unpublish — compact token + actions --}}
    @if($quiz->is_published)
        @php
            $quizWindowOpen = !$quiz->starts_at || $quiz->starts_at->isPast();
            $showEndQuiz = $quizWindowOpen && (!$quiz->ends_at || $quiz->ends_at->isFuture());
            $shareUrl = route('student.rules.show.quiz', ['token' => $quiz->link_token]);
        @endphp
        <div class="bg-primary-50 border border-primary-200 rounded-lg p-2 min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-medium text-primary-800 shrink-0">Token:</span>
                <input type="text" readonly value="{{ $quiz->link_token }}" id="quiz-token-{{ $quiz->id }}" class="w-36 text-xs font-mono font-semibold text-gray-800 bg-white border border-primary-300 rounded px-1.5 py-0.5" />
                <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('quiz-token-{{ $quiz->id }}').value); this.textContent='Copied!'; setTimeout(() => this.textContent='Copy', 1500)" class="btn btn-primary text-xs py-0.5 px-2">Copy</button>
                <details class="text-xs ml-1">
                    <summary class="cursor-pointer text-primary-600 hover:text-primary-800">link</summary>
                    <div class="flex items-center gap-1 mt-0.5 flex-wrap">
                        <input type="text" readonly value="{{ $shareUrl }}" id="quiz-share-url-{{ $quiz->id }}" class="flex-1 min-w-0 max-w-xs text-xs font-mono text-gray-600 bg-white border border-primary-200 rounded px-1.5 py-0.5" />
                        <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('quiz-share-url-{{ $quiz->id }}').value); this.textContent='Copied!'; setTimeout(() => this.textContent='Copy', 1500)" class="btn btn-secondary text-xs py-0.5 px-1.5">Copy</button>
                    </div>
                </details>
                <span class="flex-1"></span>
                @if($showEndQuiz)
                <form action="{{ route('dashboard.quizzes.end', $quiz) }}" method="post" class="inline" onsubmit="return confirm('End this quiz now? Students will no longer be able to start or submit.');">
                    @csrf
                    <button type="submit" class="btn bg-danger-100 text-danger-700 hover:bg-danger-200 text-xs py-0.5 px-2">End quiz</button>
                </form>
                @else
                <form action="{{ route('dashboard.quizzes.unpublish', $quiz) }}" method="post" class="inline">
                    @csrf
                    <button type="submit" class="btn btn-secondary text-xs py-0.5 px-2">Unpublish</button>
                </form>
                @endif
            </div>
        </div>
    @else
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                    <div>
                        <p class="font-medium text-gray-900">This quiz is not published</p>
                        <p class="text-sm text-gray-600">Students cannot see this quiz on the landing page until you publish it.</p>
                        @if(!$quiz->hasEnoughApprovedQuestions())
                            <p class="text-sm text-warning-600 font-medium mt-1">
                                ⚠️ Need {{ $quiz->getQuestionsPerStudent() }} approved questions (currently: {{ $quiz->questions->count() }})
                            </p>
                        @endif
                    </div>
                </div>
                <div class="flex flex-col items-end gap-2">
                    <form action="{{ route('dashboard.quizzes.publish', $quiz) }}" method="post" class="inline">
                        @csrf
                        <button type="submit" 
                                class="btn text-sm {{ $quiz->hasEnoughApprovedQuestions() ? 'btn-primary' : 'bg-gray-300 text-gray-500 cursor-not-allowed' }}"
                                @if(!$quiz->hasEnoughApprovedQuestions()) 
                                    disabled 
                                    onclick="event.preventDefault(); alert('Please approve at least {{ $quiz->getQuestionsPerStudent() }} questions first. Scroll down to see the \'Approve All\' button.');"
                                @endif>
                            Publish Quiz
                        </button>
                    </form>
                    @if(!$quiz->hasEnoughApprovedQuestions() && $unapprovedPoolsTotal > 0)
                        <p class="text-xs text-gray-600 text-right">
                            👇 Scroll down &amp; click "Approve All ({{ $unapprovedPoolsTotal }})"
                        </p>
                    @endif
                </div>
            </div>
        </div>
    @endif

        @if($quiz->is_active && !$quiz->hasEnoughApprovedQuestions())
        <div class="mb-6 bg-warning-50 border border-warning-200 rounded-lg p-4 flex gap-3">
            <svg class="w-6 h-6 text-warning-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <div class="text-sm text-warning-800">
                <p class="font-medium">Quiz locked until approval complete</p>
                <p>Students cannot see or take this quiz until at least {{ $quiz->getQuestionsPerStudent() }} question(s) are approved. Currently: {{ $quiz->questions->count() }} approved.</p>
            </div>
        </div>
        @endif

        <div class="grid gap-6">
            @if($unapprovedPools->isNotEmpty())
            <!-- Question Pool (Unapproved) - AI-generated awaiting approval -->
            <section class="card p-6 border-2 border-warning-200 bg-warning-50/30">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-warning-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-warning-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">Question Pool (Unapproved)</h2>
                            <p class="text-sm text-gray-600">{{ $unapprovedPoolsTotal }} AI-generated question(s) awaiting approval (showing {{ $unapprovedPools->count() }} on this page)</p>
                        </div>
                    </div>
                    @if(!$quiz->hasStarted())
                    <form action="{{ route('dashboard.quizzes.approve-all-pool', $quiz) }}" method="post" class="inline" onsubmit="return confirm('This will approve ALL {{ $unapprovedPoolsTotal }} pending questions. Continue?');">
                        @csrf
                        <button type="submit" class="btn btn-primary flex items-center gap-2 {{ $unapprovedPoolsTotal > 0 ? 'animate-pulse' : '' }}">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Approve All ({{ $unapprovedPoolsTotal }})</span>
                        </button>
                    </form>
                    @endif
                </div>
                <div class="space-y-4">
                    @foreach($unapprovedPools as $idx => $pool)
                        <div class="border border-warning-200 rounded-lg p-4 bg-white flex flex-wrap items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-gray-900 mb-2">{{ $pool->question_text }}</p>
                                @if($pool->options)
                                    <ul class="text-sm text-gray-600 space-y-1 mb-2">
                                        @foreach($pool->options as $opt)
                                            <li><span class="font-medium">{{ $opt['key'] ?? '' }}.</span> {{ $opt['text'] ?? '' }}
                                                @if(($opt['key'] ?? '') === $pool->correct_answer)<span class="text-success-600 font-medium"> (correct)</span>@endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                                @if($pool->topic)
                                    <span class="inline-flex px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700">{{ $pool->topic }}</span>
                                @endif
                            </div>
                            @if(!$quiz->hasStarted())
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <a href="{{ route('dashboard.quizzes.pool.edit', [$quiz, $pool]) }}" class="btn btn-secondary text-sm">Edit</a>
                                <form action="{{ route('dashboard.quizzes.pool.approve', [$quiz, $pool]) }}" method="post" class="inline">
                                    @csrf
                                    <button type="submit" class="btn btn-primary text-sm">Approve</button>
                                </form>
                                <form action="{{ route('dashboard.quizzes.pool.reject', [$quiz, $pool]) }}" method="post" class="inline" onsubmit="return confirm('Remove this question from the pool?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn bg-danger-100 text-danger-700 hover:bg-danger-200 text-sm">Reject</button>
                                </form>
                            </div>
                            @endif
                        </div>
                    @endforeach
                </div>
                
                {{-- Pagination --}}
                @if($unapprovedPools->hasPages())
                <div class="mt-6 flex justify-center">
                    {{ $unapprovedPools->links() }}
                </div>
                @endif
            </section>
            @endif

            <!-- Questions Section (Approved) - paginated -->
            <section class="card p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">Questions</h2>
                            <p class="text-sm text-gray-600">{{ $approvedQuestionsTotal }} question(s) in quiz (showing {{ $approvedQuestions->count() }} on this page)</p>
                        </div>
                    </div>
                </div>

                @if($approvedQuestions->isEmpty())
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        @if($unapprovedPoolsTotal > 0)
                            <p class="text-gray-700 mb-2">You have <strong>{{ $unapprovedPoolsTotal }}</strong> question(s) in the pool above.</p>
                            <p class="text-sm text-gray-600 mb-4">Click <strong>Approve All</strong> to add them to the quiz.</p>
                        @else
                            <p class="text-gray-500 mb-4">No questions added yet</p>
                            <p class="text-sm text-gray-600">Add questions manually or generate them with AI</p>
                        @endif
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($approvedQuestions as $idx => $q)
                            <div class="border border-gray-200 rounded-lg p-4 hover:border-primary-300 transition-colors flex flex-wrap items-start justify-between gap-3">
                                <div class="flex items-start gap-3 flex-1 min-w-0">
                                    <span class="flex-shrink-0 w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-gray-700 font-semibold text-sm">
                                        {{ ($approvedQuestions->currentPage() - 1) * $approvedQuestions->perPage() + $idx + 1 }}
                                    </span>
                                    <div class="flex-1">
                                        <p class="text-gray-900 mb-2">{{ $q->text }}</p>
                                        <div class="flex items-center gap-3 text-xs flex-wrap">
                                            <span class="inline-flex px-2 py-1 rounded-full bg-gray-100 text-gray-700">
                                                {{ ucfirst($q->type) }}
                                            </span>
                                            <span class="inline-flex px-2 py-1 rounded-full 
                                                @if($q->source === 'ai') bg-primary-100 text-primary-700 @else bg-gray-100 text-gray-700 @endif">
                                                {{ ucfirst($q->source) }}
                                            </span>
                                            @if($q->topic)
                                                <span class="text-gray-500">• {{ $q->topic }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    @if(!$quiz->hasStarted())
                                    <a href="{{ route('dashboard.quizzes.questions.edit', [$quiz, $q]) }}" class="btn btn-secondary text-sm">Edit</a>
                                    <form action="{{ route('dashboard.quizzes.questions.destroy', [$quiz, $q]) }}" method="post" class="inline" onsubmit="return confirm('Remove this question from the quiz?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn bg-danger-100 text-danger-700 hover:bg-danger-200 text-sm">Delete</button>
                                    </form>
                                    @else
                                    <span class="text-xs text-gray-500">Locked</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($approvedQuestions->hasPages())
                    <div class="mt-6 flex justify-center">
                        {{ $approvedQuestions->links() }}
                    </div>
                    @endif
                @endif
            </section>
        </div>
    @endif

    {{-- Sessions tab: compact stats and list --}}
    @if($activeTab === 'sessions')
        <div class="space-y-4">
            {{-- Compact summary stats --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-white rounded-lg border border-gray-200 p-3">
                    <p class="text-xs font-medium text-gray-500 mb-1">Students</p>
                    <p class="text-2xl font-bold text-gray-900 tabular-nums">{{ $sessionsStats['total_students'] }}</p>
                </div>
                <div class="bg-white rounded-lg border border-gray-200 p-3">
                    <p class="text-xs font-medium text-gray-500 mb-1">Average</p>
                    <p class="text-2xl font-bold text-gray-900 tabular-nums">{{ $sessionsStats['average_score'] }}<span class="text-base">%</span></p>
                </div>
                <div class="bg-white rounded-lg border border-gray-200 p-3">
                    <p class="text-xs font-medium text-gray-500 mb-1">Range</p>
                    <p class="text-2xl font-bold text-gray-900 tabular-nums">{{ $sessionsStats['lowest_score'] }}-{{ $sessionsStats['highest_score'] }}<span class="text-base">%</span></p>
                </div>
                <div class="bg-white rounded-lg border border-gray-200 p-3">
                    <p class="text-xs font-medium text-gray-500 mb-1">Violations</p>
                    <p class="text-2xl font-bold {{ $sessionsStats['total_violations'] > 0 ? 'text-danger-600' : 'text-gray-900' }} tabular-nums">{{ $sessionsStats['total_violations'] }}</p>
                    @if($sessionsStats['students_with_violations'] > 0)
                        <p class="text-xs text-danger-600">{{ $sessionsStats['students_with_violations'] }} student(s)</p>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-sm font-semibold text-gray-900">Student Results</h2>
                    <div class="flex flex-wrap items-center gap-2">
                        <label for="sessions-search-index" class="sr-only">Search by index number</label>
                        <input type="text" id="sessions-search-index" placeholder="Search by index…" class="input text-sm py-1.5 px-3 w-40 min-w-0 max-w-xs" autocomplete="off">
                        <a href="{{ route('dashboard.quizzes.show', ['quiz' => $quiz, 'tab' => 'scores']) }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-primary-600 hover:text-primary-800">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Export (PDF, Excel, CSV)
                        </a>
                    </div>
                </div>

                @if($sessionsPaginator->isEmpty())
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-10 w-10 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="text-sm text-gray-500 font-medium">No completed sessions yet</p>
                        <p class="text-xs text-gray-400 mt-1">Results will appear once students finish</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wide">Student</th>
                                    <th scope="col" class="px-4 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wide">Mark</th>
                                    <th scope="col" class="px-4 py-2.5 text-right text-xs font-semibold text-gray-700 uppercase tracking-wide">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100" id="sessions-table-body">
                                @foreach($sessionsPaginator as $session)
                                    <tr class="hover:bg-gray-50 transition-colors sessions-row" data-student-index="{{ strtoupper($session->student_index ?? '') }}">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="text-sm font-medium text-gray-900">{{ $session->student_index }}</span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if($session->result)
                                                @php
                                                    $score = $session->result->score;
                                                    $mark = $score >= 70 ? 5 : ($score >= 50 ? 4 : 3);
                                                @endphp
                                                <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-sm font-bold
                                                    @if($mark >= 5) bg-emerald-100 text-emerald-800
                                                    @elseif($mark >= 4) bg-amber-100 text-amber-800
                                                    @else bg-rose-100 text-rose-800
                                                    @endif">{{ $mark }}</span>
                                            @else
                                                <span class="text-xs text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right">
                                            <a href="{{ route('dashboard.quizzes.sessions.show', [$quiz, $session]) }}" class="inline-flex items-center gap-1 text-xs font-medium text-primary-600 hover:text-primary-800">
                                                View details
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                                </svg>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-2.5 border-t border-gray-200 bg-gray-50">
                        {{ $sessionsPaginator->links() }}
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Scores & Export tab: export only, no stats or table --}}
    @if($activeTab === 'scores')
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <h2 class="text-xs font-semibold text-gray-700 mb-1">Export results</h2>
            <p class="text-xs text-gray-500 mb-3">Preview or download scores as PDF, Excel, or CSV.</p>
            <div class="flex flex-wrap gap-1.5">
                <a href="{{ route('dashboard.quizzes.scores.export.pdf.preview', $quiz) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded hover:bg-gray-100 hover:border-gray-300">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    Preview PDF
                </a>
                <a href="{{ route('dashboard.quizzes.scores.export.pdf', $quiz) }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded hover:bg-gray-100 hover:border-gray-300" download>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    PDF
                </a>
                <a href="{{ route('dashboard.quizzes.scores.export.excel', $quiz) }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded hover:bg-gray-100 hover:border-gray-300" download>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Excel
                </a>
                <a href="{{ route('dashboard.quizzes.scores.export', $quiz) }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded hover:bg-gray-100 hover:border-gray-300" download>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    CSV
                </a>
            </div>
        </div>
    @endif
</div>

@if($activeTab === 'sessions' && !$sessionsPaginator->isEmpty())
@push('scripts')
<script>
(function() {
    var input = document.getElementById('sessions-search-index');
    var rows = document.querySelectorAll('.sessions-row');
    if (!input || !rows.length) return;
    function filter() {
        var q = (input.value || '').trim().toUpperCase();
        var visible = 0;
        for (var i = 0; i < rows.length; i++) {
            var index = (rows[i].getAttribute('data-student-index') || '').toUpperCase();
            var show = !q || index.indexOf(q) !== -1;
            rows[i].style.display = show ? '' : 'none';
            if (show) visible++;
        }
    }
    input.addEventListener('input', filter);
    input.addEventListener('keyup', filter);
})();
</script>
@endpush
@endif
@endsection
