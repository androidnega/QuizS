@extends('layouts.student-dashboard')

@section('title', 'Dashboard')
@php $dashboardTitle = 'Dashboard'; @endphp

@section('dashboard_content')
<div class="space-y-4">
    <div>
        <h1 class="text-xl font-bold text-gray-900">{{ $greeting ?? 'Hello' }}, {{ $student->first_name }}</h1>
        <p class="text-sm text-gray-600 mt-1">Your quiz history and scheduled quizzes.</p>
    </div>

    <div class="flex flex-row flex-wrap gap-3">
        <a href="{{ route('dashboard.my-quizzes') }}" class="flex-1 min-w-[180px] rounded-lg border border-yellow-300 bg-yellow-100 p-4 block">
            <p class="text-xs font-medium text-yellow-800">Quizzes taken</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-gray-900">{{ $sessionsCount }}</p>
            <span class="mt-2 inline-block text-xs font-medium text-yellow-700">View all →</span>
        </a>
        <a href="{{ route('dashboard.my-profile') }}" class="flex-1 min-w-[180px] rounded-lg border border-blue-300 bg-blue-100 p-4 block">
            <p class="text-xs font-medium text-blue-800">Index number</p>
            <p class="mt-1 text-base font-mono font-semibold text-gray-900">{{ $student->index_number }}</p>
            <span class="mt-2 inline-block text-xs font-medium text-blue-700">Edit profile →</span>
        </a>
        @if(isset($scheduledQuiz) && $scheduledQuiz)
        <div class="flex-1 min-w-[180px] rounded-lg border-2 border-blue-400 bg-blue-50 p-4">
            <p class="text-xs font-medium text-blue-800">Scheduled quiz</p>
            <p class="mt-1 text-base font-semibold text-gray-900">{{ $scheduledQuiz->title }}</p>
            @if($scheduledQuiz->course)
            <p class="text-xs text-gray-600 mt-0.5">{{ $scheduledQuiz->course->name }}</p>
            @endif
            <p class="text-xs text-gray-500 mt-1">{{ $scheduledQuiz->duration_minutes }} min · {{ $scheduledQuiz->getQuestionsPerStudent() }} questions</p>
            @if($scheduledQuiz->starts_at && $scheduledQuiz->starts_at->isFuture())
            <p class="text-xs text-gray-600 mt-2">Starts {{ $scheduledQuiz->starts_at->format('M j, g:i A') }}</p>
            <div class="mt-2 flex items-center gap-2">
                <span id="quiz-countdown-{{ $scheduledQuiz->id }}" class="text-base font-bold tabular-nums text-blue-700">--:--:--</span>
                <a href="{{ route('student.quiz-will-start', ['token' => $scheduledQuiz->link_token]) }}" class="text-xs font-medium text-blue-600">View countdown</a>
            </div>
            @else
            <a href="{{ route('student.rules.show.quiz', ['token' => $scheduledQuiz->link_token]) }}" class="mt-3 inline-flex items-center justify-center w-full rounded-lg bg-blue-600 text-white py-2 px-4 text-sm font-semibold">
                Start quiz →
            </a>
            @endif
        </div>
        @else
        <a href="{{ route('student.landing') }}" class="flex-1 min-w-[180px] rounded-lg border border-gray-300 bg-gray-100 p-4 block">
            <p class="text-xs font-medium text-gray-700">Start a quiz</p>
            <p class="mt-1 text-base font-semibold text-gray-900">Enter token</p>
            <span class="mt-2 inline-block text-xs font-medium text-gray-600">Go →</span>
        </a>
        @endif
    </div>

    @if(isset($classGroups) && $classGroups->isNotEmpty())
    <section class="rounded-lg border border-gray-200 bg-white p-3">
        <h2 class="text-xs font-semibold text-gray-800 mb-2">My groups</h2>
        <ul class="flex flex-wrap gap-2">
            @foreach($classGroups as $group)
            <li class="inline-flex items-center rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-800">{{ $group->name }}</li>
            @endforeach
        </ul>
    </section>
    @endif
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
