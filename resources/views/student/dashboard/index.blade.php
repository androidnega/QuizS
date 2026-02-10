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
        <div class="flex-1 min-w-[180px] rounded-lg border-2 p-4" style="background-color: #1e3a5f; border-color: #2c5282;">
            <p class="text-xs font-medium" style="color: #fbbf24;">SCHEDULED QUIZ</p>
            <p class="mt-1 text-base font-semibold text-white">{{ $scheduledQuiz->title }}</p>
            @if($scheduledQuiz->course)
            <p class="text-xs mt-0.5" style="color: #e2e8f0;">{{ $scheduledQuiz->course->name }}</p>
            @endif
            <p class="text-xs mt-1" style="color: #cbd5e0;">{{ $scheduledQuiz->duration_minutes }} min · {{ $scheduledQuiz->getQuestionsPerStudent() }} questions</p>
            @if(isset($scheduledQuizSession) && $scheduledQuizSession)
                {{-- Student has already completed this quiz --}}
                @if($scheduledQuizSession->result)
                    <p class="text-xs mt-2" style="color: #e2e8f0;">Score: <span class="text-lg font-bold" style="color: #fbbf24;">{{ number_format($scheduledQuizSession->result->score, 1) }}%</span></p>
                @endif
                @if(isset($scheduledQuizSession->id))
                <a href="{{ route('dashboard.my-quizzes.show', ['sessionId' => $scheduledQuizSession->id]) }}" class="mt-3 inline-flex items-center justify-center w-full rounded-lg py-2 px-4 text-sm font-semibold" style="background-color: #fbbf24; color: #1e3a5f;">
                    View Results →
                </a>
                @endif
            @else
                @if($scheduledQuiz->starts_at && $scheduledQuiz->starts_at->isFuture())
                    <p class="text-xs mt-2" style="color: #e2e8f0;">Starts {{ $scheduledQuiz->starts_at->format('M j, g:i A') }}</p>
                    <div class="mt-2 flex items-center gap-2">
                        <span id="quiz-countdown-{{ $scheduledQuiz->id }}" class="text-base font-bold tabular-nums" style="color: #fbbf24;">--:--:--</span>
                        <a href="{{ route('student.quiz-will-start', ['token' => $scheduledQuiz->link_token]) }}" class="text-xs font-medium" style="color: #fbbf24;">View countdown</a>
                    </div>
                @else
                    <a href="{{ route('student.rules.show.quiz', ['token' => $scheduledQuiz->link_token]) }}" class="mt-3 inline-flex items-center justify-center w-full rounded-lg py-2 px-4 text-sm font-semibold" style="background-color: #fbbf24; color: #1e3a5f;">
                        Start quiz →
                    </a>
                @endif
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
    <section class="rounded-lg border border-gray-200 bg-white p-4">
        <h2 class="text-sm font-semibold text-gray-800 mb-3">My Groups</h2>
        <ul class="flex flex-wrap gap-2">
            @foreach($classGroups as $group)
            <li class="inline-flex items-center rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-800">{{ $group->name }}</li>
            @endforeach
        </ul>
    </section>
    @endif

    <button type="button" id="course-materials-btn" class="w-full rounded-lg border border-gray-200 bg-white p-4 text-left hover:bg-gray-50 transition-colors">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-semibold text-gray-900">Course Materials</h2>
                <p class="text-xs text-gray-600 mt-1">Weekly course files and notes</p>
            </div>
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </div>
    </button>

    {{-- Course Materials Modal --}}
    <div id="course-materials-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/50" aria-modal="true" aria-labelledby="course-materials-title" role="dialog">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                <h2 id="course-materials-title" class="text-lg font-semibold text-gray-900">Course Materials</h2>
                <button type="button" id="course-materials-close" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 focus:outline-none text-xl" aria-label="Close">×</button>
            </div>
            <div class="px-5 py-4 overflow-y-auto flex-1">
                <div class="grid grid-cols-3 gap-3">
                    @for($week = 1; $week <= 3; $week++)
                    <button type="button" class="week-btn rounded-lg border border-gray-200 bg-gray-50 p-3 text-center hover:bg-gray-100 transition-colors" data-week="{{ $week }}">
                        <h3 class="text-sm font-semibold text-gray-900">Week {{ $week }}</h3>
                        <p class="text-xs text-gray-500 mt-1">Click to view</p>
                    </button>
                    @endfor
                </div>
            </div>
        </div>
    </div>

    {{-- Coming Soon Modal --}}
    <div id="coming-soon-modal" class="fixed inset-0 z-[60] hidden items-center justify-center p-4 bg-black/50" aria-modal="true" aria-labelledby="coming-soon-title" role="dialog">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                <h2 id="coming-soon-title" class="text-lg font-semibold text-gray-900">Week <span id="coming-soon-week"></span></h2>
                <button type="button" id="coming-soon-close" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 focus:outline-none text-xl" aria-label="Close">×</button>
            </div>
            <div class="px-5 py-6 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-base font-medium text-gray-900 mb-2">Coming Soon</p>
                <p class="text-sm text-gray-600">Course materials for this week will be available soon.</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    // Course Materials Modal
    var materialsBtn = document.getElementById('course-materials-btn');
    var materialsModal = document.getElementById('course-materials-modal');
    var materialsClose = document.getElementById('course-materials-close');
    
    if (materialsBtn && materialsModal) {
        function openMaterialsModal() {
            materialsModal.classList.remove('hidden');
            materialsModal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }
        function closeMaterialsModal() {
            materialsModal.classList.add('hidden');
            materialsModal.classList.remove('flex');
            document.body.style.overflow = '';
        }
        materialsBtn.addEventListener('click', openMaterialsModal);
        if (materialsClose) materialsClose.addEventListener('click', closeMaterialsModal);
        materialsModal.addEventListener('click', function(e) {
            if (e.target === materialsModal) closeMaterialsModal();
        });
    }

    // Coming Soon Modal
    var comingSoonModal = document.getElementById('coming-soon-modal');
    var comingSoonClose = document.getElementById('coming-soon-close');
    var comingSoonWeek = document.getElementById('coming-soon-week');
    var weekButtons = document.querySelectorAll('.week-btn');
    
    if (comingSoonModal) {
        function openComingSoonModal(week) {
            if (comingSoonWeek) comingSoonWeek.textContent = week;
            comingSoonModal.classList.remove('hidden');
            comingSoonModal.classList.add('flex');
        }
        function closeComingSoonModal() {
            comingSoonModal.classList.add('hidden');
            comingSoonModal.classList.remove('flex');
        }
        weekButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                var week = this.getAttribute('data-week');
                if (materialsModal) {
                    materialsModal.classList.add('hidden');
                    materialsModal.classList.remove('flex');
                }
                openComingSoonModal(week);
            });
        });
        if (comingSoonClose) comingSoonClose.addEventListener('click', closeComingSoonModal);
        comingSoonModal.addEventListener('click', function(e) {
            if (e.target === comingSoonModal) closeComingSoonModal();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (!comingSoonModal.classList.contains('hidden')) closeComingSoonModal();
                if (materialsModal && !materialsModal.classList.contains('hidden')) closeMaterialsModal();
            }
        });
    }
})();
</script>
@if(isset($scheduledQuiz) && $scheduledQuiz && $scheduledQuiz->starts_at && $scheduledQuiz->starts_at->isFuture())
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
@endif
@endpush
@endsection
