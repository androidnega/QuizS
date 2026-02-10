@extends('layouts.app')

@section('title', 'QuizSnap')
@section('body_class', '')

@push('styles')
<style>
    body,
    .home-page-wrap { background: #f8fafc !important; }
    .home-input { 
        outline: none; 
        border: 2px solid #e2e8f0;
        background: #fff; 
        transition: all 0.2s ease;
    }
    .home-input:focus { 
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    .home-input.token-valid { background-color: #f0fdf4 !important; border-color: #22c55e !important; color: #15803d; }
    .home-input.token-invalid { background-color: #fef2f2 !important; border-color: #ef4444 !important; color: #dc2626; }
    .home-input.token-loading { background-color: #fffbeb !important; border-color: #f59e0b !important; color: #d97706; }
    .btn-home-cta.btn-cta-disabled, .btn-home-cta:disabled { 
        background: #cbd5e1 !important; 
        color: #fff !important; 
        cursor: not-allowed; 
        pointer-events: none; 
        box-shadow: none !important;
    }
    .btn-home-cta:not(.btn-cta-disabled):not(:disabled) { 
        background: #3b82f6; 
        color: #fff !important; 
        transition: all 0.2s ease;
        font-weight: 600;
        box-shadow: none;
        border: none;
    }
    .btn-home-cta:not(.btn-cta-disabled):not(:disabled):hover {
        background: #2563eb;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    .logo-text { font-size: 1.75rem; font-weight: 700; letter-spacing: -0.02em; display: inline-flex; align-items: center; gap: 0.5rem; }
    .logo-mark { width: 2.25rem; height: 2.25rem; flex-shrink: 0; }
    .home-input { -webkit-user-select: text !important; -moz-user-select: text !important; user-select: text !important; }
    @media (max-width: 639px) { .home-form-container { gap: 0.5rem; } }
</style>
@endpush

@section('content')
<div class="home-page-wrap min-h-screen flex flex-col font-sans antialiased">
    <header class="shrink-0 bg-white border-b border-slate-200">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-6">
            <a href="{{ route('student.landing') }}" class="logo-text no-underline">
                <span class="logo-mark" aria-hidden="true">
                    <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
                        <rect width="40" height="40" rx="10" fill="#3b82f6"/>
                        <circle cx="20" cy="18" r="7" fill="#fbbf24"/>
                        <circle cx="20" cy="18" r="3" fill="#3b82f6"/>
                        <rect x="18" y="26" width="4" height="6" rx="1" fill="#fbbf24"/>
                    </svg>
                </span>
                <span style="color: #3b82f6;">Quiz</span><span style="color: #fbbf24;">Snap</span>
            </a>
            <div class="flex items-center gap-2">
                <button type="button" id="about-system-btn" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 transition-all focus:outline-none focus:ring-2 focus:ring-blue-400" title="About the system" aria-label="About the system">
                    <span class="text-lg font-bold leading-none">!</span>
                </button>
                @if(isset($student) && $student)
                    <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-blue-600 px-4 py-2 rounded-lg border border-blue-200 hover:bg-blue-50 transition-all no-underline">Dashboard</a>
                @else
                    <a href="{{ route('student.account.login.form') }}" class="text-sm font-semibold text-blue-600 px-4 py-2 rounded-lg border border-blue-200 hover:bg-blue-50 transition-all no-underline">Student Login</a>
                @endif
            </div>
        </div>
    </header>

    {{-- About the system modal: clean and simple --}}
    <div id="about-system-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-6 bg-black/50" aria-modal="true" aria-labelledby="about-system-title" role="dialog">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full max-h-[90vh] overflow-hidden flex flex-col">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100">
                <h2 id="about-system-title" class="text-xl font-bold text-slate-900">How QuizSnap works</h2>
                <button type="button" id="about-system-close" class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 focus:outline-none text-xl" aria-label="Close">×</button>
            </div>
            <div class="px-6 py-6 overflow-y-auto text-base text-slate-700">
                <p class="mb-6">Your lecturer or examiner gives you a <strong>token</strong>. Enter it here to start.</p>
                <ol class="list-decimal list-inside space-y-3 text-slate-700">
                    <li><strong>Enter token</strong> — Type the token and click Start Quiz.</li>
                    <li><strong>Verify index</strong> — Enter your index number.</li>
                    <li><strong>Start photo</strong> — Allow camera and capture your face.</li>
                    <li><strong>Take the quiz</strong> — Answer the questions before time runs out.</li>
                    <li><strong>End photo</strong> — Capture a final photo, then submit.</li>
                    <li><strong>Result</strong> — View your score and feedback.</li>
                </ol>
                <p class="mt-6 pt-6 border-t border-slate-100 text-slate-600">QuizSnap runs on desktop only for a clear, fair quiz. Start and end photos help verify your session. Please stay on the quiz tab and use a stable connection.</p>
            </div>
        </div>
    </div>

    <main class="flex flex-1 flex-col items-center justify-center px-6 py-16 sm:py-20">
        <div class="w-full max-w-3xl text-center">
            <h1 class="text-4xl sm:text-5xl font-bold text-slate-900 mb-4 tracking-tight">
                Start Your Quiz
            </h1>
            <p class="text-slate-600 text-lg mb-12">
                Enter the <span style="color: #ef4444; font-weight: 600;">token</span> from your lecturer or examiner
            </p>

            @if(isset($activeQuiz) && $activeQuiz)
                {{-- Active quiz available --}}
                <div class="mb-12 rounded-xl border-2 border-blue-200 bg-blue-50 p-6 max-w-xl mx-auto">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="flex-1 text-left">
                            <h3 class="text-lg font-semibold text-slate-900 mb-1">Quiz Available</h3>
                            <p class="text-sm text-slate-700 mb-3">{{ $activeQuiz->title }}</p>
                            @if($activeQuiz->course)
                            <p class="text-xs text-slate-600 mb-2">{{ $activeQuiz->course->name }}</p>
                            @endif
                            <p class="text-xs text-slate-600 mb-4">{{ $activeQuiz->duration_minutes }} min · {{ $activeQuiz->getQuestionsPerStudent() }} questions</p>
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                                Go to Dashboard
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            @else
                {{-- No quiz available --}}
                <div class="mb-12 rounded-xl border-2 border-slate-200 bg-slate-50 p-8 max-w-xl mx-auto">
                    <div class="flex flex-col items-center">
                        <div class="w-16 h-16 bg-slate-300 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-700 mb-2">No quiz available at the moment</h3>
                        <p class="text-sm text-slate-600">When a quiz is available for your class, it will automatically appear on your dashboard.</p>
                    </div>
                </div>
            @endif

            <form action="{{ route('student.start-quiz') }}" method="post" class="mb-12" id="start-quiz-form">
                @csrf
                <div class="home-form-container flex flex-col sm:flex-row gap-3 mb-3">
                    <label for="quiz-token" class="sr-only">Quiz token</label>
                    <input type="text" id="quiz-token" name="link" placeholder="Enter quiz token (e.g. KTdie54-3Sx9)" required autocomplete="off"
                        class="home-input flex-1 rounded-lg px-5 py-4 text-base min-h-[56px] sm:rounded-r-none">
                    <button type="submit" id="start-quiz-btn" disabled class="btn-home-cta btn-cta-disabled rounded-lg px-8 py-4 font-semibold text-base min-h-[56px] sm:rounded-l-none transition-all">
                        Start Quiz →
                    </button>
                </div>
                <div id="token-message" class="text-sm min-h-[1.5rem] text-center font-medium"></div>
                @error('link')
                    <div class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-4 py-3 font-medium">{{ $message }}</div>
                @enderror
            </form>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 max-w-3xl mx-auto">
                <div class="bg-indigo-50 rounded-xl px-6 py-5 border border-indigo-100 text-center">
                    <div class="w-12 h-12 bg-indigo-500 rounded-xl flex items-center justify-center text-white text-xl font-bold mx-auto mb-3">🔒</div>
                    <p class="text-sm font-semibold text-slate-900 mb-1">Secure</p>
                    <p class="text-xs text-slate-600">Proctored environment</p>
                </div>
                <div class="bg-emerald-50 rounded-xl px-6 py-5 border border-emerald-100 text-center">
                    <div class="w-12 h-12 bg-emerald-500 rounded-xl flex items-center justify-center text-white text-xl font-bold mx-auto mb-3">⚡</div>
                    <p class="text-sm font-semibold text-slate-900 mb-1">Fast</p>
                    <p class="text-xs text-slate-600">Instant access</p>
                </div>
                <div class="bg-cyan-50 rounded-xl px-6 py-5 border border-cyan-100 text-center">
                    <div class="w-12 h-12 bg-cyan-500 rounded-xl flex items-center justify-center text-white text-xl font-bold mx-auto mb-3">✓</div>
                    <p class="text-sm font-semibold text-slate-900 mb-1">Reliable</p>
                    <p class="text-xs text-slate-600">Desktop optimized</p>
                </div>
            </div>
        </div>
    </main>

    <footer class="shrink-0 border-t border-slate-200 bg-white py-6">
        <div class="mx-auto max-w-6xl px-6 text-center text-sm text-slate-500">&copy; {{ date('Y') }} QuizSnap</div>
    </footer>
</div>
@endsection

@push('scripts')
<script>
(function() {
    var DEBOUNCE_MS = 350;
    var input = document.getElementById('quiz-token');
    var messageEl = document.getElementById('token-message');
    var form = document.getElementById('start-quiz-form');
    var validateUrl = '{{ route("student.validate-token") }}';
    var csrf = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content;
    var debounceTimer = null;
    var lastToken = '';
    var btn = document.getElementById('start-quiz-btn');

    function setButtonState(enable) {
        if (!btn) return;
        btn.disabled = !enable;
        btn.classList.toggle('btn-cta-disabled', !enable);
    }

    function setState(klass, text) {
        input.classList.remove('token-valid', 'token-invalid', 'token-loading');
        if (klass) input.classList.add(klass);
        if (messageEl) {
            messageEl.textContent = text || '';
            messageEl.className = 'text-sm min-h-[1.5rem] text-left';
            if (text) {
                if (klass === 'token-valid') messageEl.classList.add('text-green-600', 'font-medium');
                else if (klass === 'token-invalid') messageEl.classList.add('text-red-600');
                else messageEl.classList.add('text-amber-600');
            }
        }
        setButtonState(klass === 'token-valid');
    }

    function runValidation(tokenValue) {
        if (!tokenValue || tokenValue.length < 8) {
            setState('token-invalid', 'Please enter a valid quiz token.');
            setButtonState(false);
            return;
        }
        setState('token-loading', 'Checking…');
        setButtonState(false);
        var fd = new FormData();
        fd.append('_token', csrf);
        fd.append('token', tokenValue);
        fetch(validateUrl, { method: 'POST', body: fd, headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.valid) {
                    setState('token-valid', 'Valid token, proceed.');
                } else {
                    setState('token-invalid', data.message || 'Invalid token.');
                }
            })
            .catch(function() {
                setState('token-invalid', 'Could not validate. Try again.');
            });
    }

    function onTokenInput() {
        var raw = (input && input.value) ? input.value.trim() : '';
        if (debounceTimer) clearTimeout(debounceTimer);
        if (!raw || raw.length < 8) {
            setState('', '');
            setButtonState(false);
            lastToken = '';
            return;
        }
        lastToken = raw;
        debounceTimer = setTimeout(function() {
            debounceTimer = null;
            runValidation(raw);
        }, DEBOUNCE_MS);
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
                } else {
                    onTokenInput();
                }
            }, 50);
        });
        input.addEventListener('blur', function() {
            if (lastToken && !input.value.trim()) {
                if (debounceTimer) clearTimeout(debounceTimer);
                debounceTimer = null;
                setState('', '');
                setButtonState(false);
                lastToken = '';
            }
        });
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            if (!input.classList.contains('token-valid')) {
                e.preventDefault();
                var raw = (input && input.value) ? input.value.trim() : '';
                if (raw.length >= 8) {
                    if (debounceTimer) clearTimeout(debounceTimer);
                    debounceTimer = null;
                    runValidation(raw);
                } else {
                    setState('token-invalid', 'Please enter a valid quiz token (e.g. KTdie54-3Sx9).');
                    setButtonState(false);
                }
                return false;
            }
        });
    }
})();
(function() {
    var btn = document.getElementById('about-system-btn');
    var modal = document.getElementById('about-system-modal');
    var closeBtn = document.getElementById('about-system-close');
    if (!btn || !modal) return;
    function openModal() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }
    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }
    btn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function(e) {
        if (e.target === modal) closeModal();
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
    });
})();
</script>
@endpush
