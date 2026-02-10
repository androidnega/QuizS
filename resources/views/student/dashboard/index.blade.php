@extends('layouts.student-dashboard')

@section('title', 'Dashboard')
@php $dashboardTitle = 'Dashboard'; @endphp

@section('dashboard_content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $greeting ?? 'Hello' }}, {{ $student->first_name }}</h1>
        <p class="text-gray-600 mt-1">Your quiz history. Marks are kept forever; open a quiz to see your score and, for the last 21 days, questions and answers.</p>
    </div>

    <div class="flex flex-row flex-wrap gap-4">
        <a href="{{ route('dashboard.my-quizzes') }}" class="flex-1 min-w-[200px] rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:shadow-md hover:border-primary-200 transition-all block">
            <p class="text-sm font-medium text-gray-500">Quizzes taken</p>
            <p class="mt-1 text-3xl font-bold tabular-nums text-gray-900">{{ $sessionsCount }}</p>
            <span class="mt-2 inline-block text-sm font-medium text-primary-600">View all →</span>
        </a>
        <a href="{{ route('dashboard.my-profile') }}" class="flex-1 min-w-[200px] rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:shadow-md hover:border-primary-200 transition-all block">
            <p class="text-sm font-medium text-gray-500">Index number</p>
            <p class="mt-1 text-lg font-mono font-semibold text-gray-900">{{ $student->index_number }}</p>
            <span class="mt-2 inline-block text-sm font-medium text-primary-600">Edit profile →</span>
        </a>
        @if(isset($scheduledQuiz) && $scheduledQuiz)
        <div class="flex-1 min-w-[200px] rounded-xl border-2 border-primary-200 bg-primary-50/50 p-5 shadow-sm">
            <p class="text-sm font-medium text-primary-700">Quiz</p>
            <p class="mt-1 text-lg font-semibold text-gray-900">{{ $scheduledQuiz->title }}</p>
            @if($scheduledQuiz->course)
            <p class="text-xs text-gray-600 mt-0.5">{{ $scheduledQuiz->course->name }}</p>
            @endif
            <p class="text-xs text-gray-500 mt-1">{{ $scheduledQuiz->duration_minutes }} min · {{ $scheduledQuiz->getQuestionsPerStudent() }} questions</p>
            @if($scheduledQuiz->starts_at && $scheduledQuiz->starts_at->isFuture())
            <p class="text-xs text-gray-600 mt-2">Starts {{ $scheduledQuiz->starts_at->format('M j, g:i A') }}</p>
            <div class="mt-2 flex items-center gap-2">
                <span id="quiz-countdown-{{ $scheduledQuiz->id }}" class="text-lg font-bold tabular-nums text-primary-700">--:--:--</span>
                <a href="{{ route('student.quiz-will-start', ['token' => $scheduledQuiz->link_token]) }}" class="text-sm font-medium text-primary-600 hover:underline">Countdown →</a>
            </div>
            @else
            <a href="{{ route('student.rules.show.quiz', ['token' => $scheduledQuiz->link_token]) }}" class="mt-3 inline-flex items-center justify-center w-full rounded-lg bg-primary-600 text-white py-2.5 px-4 text-sm font-semibold hover:bg-primary-700 transition-colors">
                Start quiz →
            </a>
            @endif
        </div>
        @else
        <a href="{{ route('student.landing') }}" class="flex-1 min-w-[200px] rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:shadow-md hover:border-primary-200 transition-all block">
            <p class="text-sm font-medium text-gray-500">Start a quiz</p>
            <p class="mt-1 text-lg font-semibold text-gray-900">Enter token</p>
            <span class="mt-2 inline-block text-sm font-medium text-primary-600">Go →</span>
        </a>
        @endif
    </div>

    @if(isset($classGroups) && $classGroups->isNotEmpty())
    <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <h2 class="text-sm font-semibold text-gray-800 mb-2">My groups</h2>
        <p class="text-xs text-gray-500 mb-3">Class groups you belong to.</p>
        <ul class="flex flex-wrap gap-2">
            @foreach($classGroups as $group)
            <li class="inline-flex items-center rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-800">{{ $group->name }}</li>
            @endforeach
        </ul>
    </section>
    @endif

    <section>
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-gray-800">Recent quizzes</h2>
            <a href="{{ route('dashboard.my-quizzes') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">See all</a>
        </div>
    @if($recentSessions->isNotEmpty())
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach($recentSessions as $s)
            <a href="{{ route('dashboard.my-quizzes.show', $s) }}" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm hover:border-primary-200 hover:shadow transition-all block">
                <p class="font-medium text-gray-900 truncate">{{ $s->quiz->title ?? 'Quiz' }}</p>
                <p class="text-xs text-gray-500 mt-0.5">{{ $s->created_at->format('M j, Y g:i A') }}</p>
                @if($s->result)
                <p class="mt-2 text-sm font-semibold text-gray-800">{{ number_format($s->result->score, 1) }}%</p>
                @else
                <p class="mt-2 text-sm text-gray-500">—</p>
                @endif
            </a>
            @endforeach
        </div>
    @else
        <div class="rounded-xl border border-gray-200 bg-white p-8 text-center">
            <p class="text-gray-600">You haven't taken any quizzes yet.</p>
            <a href="{{ route('student.landing') }}" class="mt-3 inline-block text-primary-600 font-medium hover:underline">Start a quiz →</a>
        </div>
    @endif
    </section>
</div>

@if(isset($scheduledQuiz) && $scheduledQuiz && $scheduledQuiz->starts_at && $scheduledQuiz->starts_at->isFuture())
@push('scripts')
<script>
(function() {
    var startsAt = @json($scheduledQuiz->starts_at->toIso8601String());
    var startMs = new Date(startsAt).getTime();
    var el = document.getElementById('quiz-countdown-{{ $scheduledQuiz->id }}');
    if (!el) return;
    function update() {
        var now = Date.now();
        var left = Math.max(0, Math.floor((startMs - now) / 1000));
        if (left <= 0) {
            el.textContent = 'Started';
            el.nextElementSibling && (el.nextElementSibling.innerHTML = '<a href="{{ route('student.rules.show.quiz', ['token' => $scheduledQuiz->link_token]) }}" class="text-sm font-medium text-primary-600 hover:underline">Start quiz →</a>');
            return;
        }
        var h = Math.floor(left / 3600);
        var m = Math.floor((left % 3600) / 60);
        var s = left % 60;
        el.textContent = (h > 0 ? h + ':' : '') + (m < 10 && h > 0 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
    }
    update();
    setInterval(update, 1000);
})();
</script>
@endpush
@endif
@endsection
