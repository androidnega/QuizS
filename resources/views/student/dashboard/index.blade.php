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
        <button type="button" id="enter-token-open-btn" class="flex-1 min-w-[180px] rounded-lg border border-gray-300 bg-gray-100 p-4 block text-left hover:bg-gray-200 transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
            <p class="text-xs font-medium text-gray-700">Start a quiz</p>
            <p class="mt-1 text-base font-semibold text-gray-900">Enter token</p>
            <span class="mt-2 inline-block text-xs font-medium text-gray-600">Go →</span>
        </button>
        @endif
    </div>

    @if(isset($lastQuiz) && $lastQuiz && $lastQuiz->quiz && $lastQuiz->result)
        @php
            $score = round($lastQuiz->result->score, 0);
            $correctCount = $lastQuiz->result->correct_count;
            $totalQuestions = $lastQuiz->result->total_questions;
            $label = 'Good';
            if ($score >= 80) {
                $label = 'Excellent';
            } elseif ($score >= 60) {
                $label = 'Good';
            } elseif ($score >= 40) {
                $label = 'Average';
            } else {
                $label = 'Keep trying';
            }
        @endphp
        <div class="rounded-lg border border-teal-400 p-4" style="background-color: #06b6d4;">
            <div class="flex items-start justify-between mb-3">
                <div class="flex-1">
                    <h2 class="text-sm font-semibold text-white mb-1">Last Quiz Result</h2>
                    <p class="text-sm font-semibold text-white">{{ $lastQuiz->quiz->title ?? 'Quiz' }}</p>
                    @if($lastQuiz->quiz->course)
                    <p class="text-xs text-white/90 mt-0.5">{{ $lastQuiz->quiz->course->name }}</p>
                    @endif
                    <p class="text-xs text-white/80 mt-1">Taken {{ $lastQuiz->created_at ? $lastQuiz->created_at->format('M j, Y g:i A') : 'Date not available' }}</p>
                </div>
                <div class="text-right ml-3 flex-shrink-0">
                    <div class="inline-flex flex-col items-center bg-white/20 rounded-lg px-3 py-2 border border-white/30">
                        <span class="text-2xl font-bold tabular-nums text-white">{{ $score }}%</span>
                        <span class="text-xs font-semibold text-white mt-0.5">{{ $label }}</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-between pt-2 border-t border-white/30">
                <span class="text-sm text-white"><span class="font-bold">{{ $correctCount }}</span> / <span class="font-bold">{{ $totalQuestions }}</span> correct</span>
                <a href="{{ route('dashboard.my-quizzes.show', ['sessionId' => $lastQuiz->id]) }}" class="text-sm font-semibold text-white hover:text-white/90 flex items-center gap-1 bg-white/20 hover:bg-white/30 px-3 py-1.5 rounded-lg transition-colors">
                    View Details
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        </div>
    @endif

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

    <a href="{{ route('dashboard.course-materials') }}" class="w-full rounded-lg border border-gray-200 bg-white p-4 text-left hover:bg-gray-50 transition-colors block">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-semibold text-gray-900">Course Materials</h2>
                <p class="text-xs text-gray-600 mt-1">Weekly course files and notes</p>
            </div>
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </div>
    </a>
</div>

{{-- Enter quiz token modal (stays on dashboard) --}}
<div id="enter-token-modal" class="fixed inset-0 z-50 hidden" aria-modal="true" aria-labelledby="enter-token-modal-title" role="dialog">
    <div class="fixed inset-0 bg-black/50" id="enter-token-modal-backdrop"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 pointer-events-auto" id="enter-token-modal-panel">
            <div class="flex items-center justify-between mb-4">
                <h2 id="enter-token-modal-title" class="text-lg font-semibold text-gray-900">Enter quiz token</h2>
                <button type="button" id="enter-token-close-btn" class="p-1 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-300" aria-label="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form action="{{ route('student.start-quiz') }}" method="post" id="enter-token-form">
                @csrf
                <label for="enter-token-input" class="sr-only">Quiz token</label>
                <input type="text" id="enter-token-input" name="link" placeholder="e.g. KTdie54-3Sx9" required autocomplete="off"
                    class="w-full rounded-lg border-2 border-gray-300 px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <p id="enter-token-message" class="mt-2 text-sm min-h-[1.25rem] font-medium"></p>
                <div class="mt-4 flex gap-2 justify-end">
                    <button type="button" id="enter-token-cancel-btn" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300">Cancel</button>
                    <button type="submit" id="enter-token-submit-btn" disabled class="px-4 py-2 rounded-lg text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-blue-600">Start Quiz →</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
{{-- Enter token modal: open/close and validation --}}
<script>
(function() {
    var modal = document.getElementById('enter-token-modal');
    var openBtn = document.getElementById('enter-token-open-btn');
    var closeBtn = document.getElementById('enter-token-close-btn');
    var cancelBtn = document.getElementById('enter-token-cancel-btn');
    var backdrop = document.getElementById('enter-token-modal-backdrop');
    var panel = document.getElementById('enter-token-modal-panel');
    var input = document.getElementById('enter-token-input');
    var messageEl = document.getElementById('enter-token-message');
    var form = document.getElementById('enter-token-form');
    var submitBtn = document.getElementById('enter-token-submit-btn');

    function openModal(prefillToken) {
        if (!modal) return;
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        if (input) {
            input.value = prefillToken || '';
            input.classList.remove('border-green-500', 'border-red-500', 'bg-amber-50');
            input.classList.add('border-gray-300');
        }
        if (messageEl) messageEl.textContent = '';
        if (submitBtn) { submitBtn.disabled = true; }
        setTimeout(function() {
            if (input) input.focus();
            if (prefillToken && prefillToken.length >= 8) {
                if (debounceTimer) clearTimeout(debounceTimer);
                debounceTimer = null;
                runValidation(prefillToken);
            }
        }, 100);
    }
    function closeModal() {
        if (!modal) return;
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }
    if (openBtn) openBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (backdrop) backdrop.addEventListener('click', closeModal);
    if (panel) panel.addEventListener('click', function(e) { e.stopPropagation(); });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) closeModal();
    });

    var DEBOUNCE_MS = 350;
    var debounceTimer = null;
    var lastToken = '';
    var validateUrl = '{{ route("student.validate-token") }}';
    var csrf = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content;

    function setSubmitState(enable) {
        if (submitBtn) submitBtn.disabled = !enable;
    }
    function setState(klass, text) {
        if (!input) return;
        input.classList.remove('border-green-500', 'border-red-500', 'bg-amber-50', 'bg-green-50', 'bg-red-50');
        if (klass === 'valid') {
            input.classList.add('border-green-500', 'bg-green-50');
        } else if (klass === 'invalid') {
            input.classList.add('border-red-500', 'bg-red-50');
        } else if (klass === 'loading') {
            input.classList.add('border-amber-400', 'bg-amber-50');
        } else {
            input.classList.add('border-gray-300');
        }
        if (messageEl) {
            messageEl.textContent = text || '';
            messageEl.className = 'mt-2 text-sm min-h-[1.25rem] font-medium';
            if (text) {
                if (klass === 'valid') messageEl.classList.add('text-green-600');
                else if (klass === 'invalid') messageEl.classList.add('text-red-600');
                else messageEl.classList.add('text-amber-600');
            }
        }
        setSubmitState(klass === 'valid');
    }
    function runValidation(tokenValue) {
        if (!tokenValue || tokenValue.length < 8) {
            setState('invalid', 'Please enter a valid quiz token.');
            return;
        }
        setState('loading', 'Checking…');
        setSubmitState(false);
        var fd = new FormData();
        fd.append('_token', csrf);
        fd.append('token', tokenValue);
        fetch(validateUrl, { method: 'POST', body: fd, headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.valid) setState('valid', 'Valid token, proceed.');
                else setState('invalid', data.message || 'Invalid token.');
            })
            .catch(function() { setState('invalid', 'Could not validate. Try again.'); });
    }
    function onTokenInput() {
        var raw = (input && input.value) ? input.value.trim() : '';
        if (debounceTimer) clearTimeout(debounceTimer);
        if (!raw || raw.length < 8) {
            setState('', '');
            setSubmitState(false);
            lastToken = '';
            return;
        }
        lastToken = raw;
        debounceTimer = setTimeout(function() { debounceTimer = null; runValidation(raw); }, DEBOUNCE_MS);
    }
    if (input) {
        input.addEventListener('input', onTokenInput);
        input.addEventListener('paste', function() {
            setTimeout(function() {
                var raw = (input && input.value) ? input.value.trim() : '';
                if (raw.length >= 8) {
                    if (debounceTimer) clearTimeout(debounceTimer);
                    debounceTimer = null;
                    runValidation(raw);
                } else onTokenInput();
            }, 50);
        });
    }
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!input || !input.classList.contains('border-green-500')) {
                e.preventDefault();
                var raw = (input && input.value) ? input.value.trim() : '';
                if (raw.length >= 8) {
                    if (debounceTimer) clearTimeout(debounceTimer);
                    debounceTimer = null;
                    runValidation(raw);
                } else setState('invalid', 'Please enter a valid quiz token (e.g. KTdie54-3Sx9).');
                return false;
            }
        });
    }

    // If token in URL on enter, open modal with token pre-filled and Start button when valid
    (function checkUrlToken() {
        var params = new URLSearchParams(window.location.search);
        var token = params.get('t') || params.get('token');
        if (token && typeof token === 'string') {
            token = token.trim();
            if (token.length >= 8) {
                openModal(token);
            }
        }
    })();
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
