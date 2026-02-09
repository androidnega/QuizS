@extends('layouts.app')

@section('title', 'QuizSnap')
@section('body_class', '')

@push('styles')
<style>
    body,
    .home-page-wrap { background: #fafaf9 !important; }
    .home-input { 
        outline: none; 
        border: 2px solid #e5e7eb;
        background: #fff; 
        transition: all 0.2s ease;
    }
    .home-input:focus { 
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }
    .home-input.token-valid { background-color: #f0fdf4 !important; border-color: #22c55e !important; color: #15803d; }
    .home-input.token-invalid { background-color: #fef2f2 !important; border-color: #ef4444 !important; color: #dc2626; }
    .home-input.token-loading { background-color: #fffbeb !important; border-color: #f59e0b !important; color: #d97706; }
    .btn-home-cta.btn-cta-disabled, .btn-home-cta:disabled { 
        background: #d1d5db !important; 
        color: #fff !important; 
        cursor: not-allowed; 
        pointer-events: none; 
        box-shadow: none !important;
    }
    .btn-home-cta:not(.btn-cta-disabled):not(:disabled) { 
        background: #2563eb; 
        color: #fff !important; 
        transition: all 0.2s ease;
        font-weight: 700;
        box-shadow: none;
        border: none;
    }
    .btn-home-cta:not(.btn-cta-disabled):not(:disabled):hover {
        background: #1d4ed8;
        transform: translateY(-1px);
        box-shadow: none;
    }
    .btn-home-cta:not(.btn-cta-disabled):not(:disabled):focus {
        box-shadow: none;
    }
    .logo-text { font-size: 1.75rem; font-weight: 700; letter-spacing: -0.02em; display: inline-flex; align-items: center; gap: 0.5rem; }
    .logo-mark { width: 2.25rem; height: 2.25rem; flex-shrink: 0; }
    .home-input { -webkit-user-select: text !important; -moz-user-select: text !important; user-select: text !important; }
    @media (max-width: 639px) { .home-form-container { gap: 0.5rem; } }
</style>
@endpush

@section('content')
<div class="home-page-wrap min-h-screen flex flex-col font-sans antialiased">
    <header class="shrink-0 bg-white border-b border-gray-200">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-6">
            <a href="{{ route('student.landing') }}" class="logo-text no-underline">
                <span class="logo-mark" aria-hidden="true">
                    <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
                        <rect width="40" height="40" rx="10" fill="#2563eb"/>
                        <circle cx="20" cy="18" r="7" fill="#fbbf24"/>
                        <circle cx="20" cy="18" r="3" fill="#2563eb"/>
                        <rect x="18" y="26" width="4" height="6" rx="1" fill="#fbbf24"/>
                    </svg>
                </span>
                <span style="color: #2563eb;">Quiz</span><span style="color: #eab308;">Snap</span>
            </a>
            <a href="{{ route('login') }}" class="text-sm font-semibold text-gray-700 px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 transition-all no-underline">Staff Login</a>
        </div>
    </header>

    <main class="flex flex-1 flex-col items-center justify-center px-6 py-16 sm:py-20">
        <div class="w-full max-w-2xl text-center">
            <h1 class="text-4xl sm:text-5xl font-bold text-gray-900 mb-3 tracking-tight">
                Start Your Quiz
            </h1>
            <p class="text-gray-600 text-lg mb-10">
                Enter your quiz token from your lecturer or examiner
            </p>

            <form action="{{ route('student.start-quiz') }}" method="post" class="mb-12" id="start-quiz-form">
                @csrf
                <div class="home-form-container flex flex-col sm:flex-row gap-3 mb-3">
                    <label for="quiz-token" class="sr-only">Quiz token</label>
                    <input type="text" id="quiz-token" name="link" placeholder="Enter quiz token (e.g. KTdie54-3Sx9)" required autocomplete="off"
                        class="home-input flex-1 rounded-lg px-5 py-4 text-base min-h-[56px] sm:rounded-r-none">
                    <button type="submit" id="start-quiz-btn" disabled class="btn-home-cta btn-cta-disabled rounded-lg px-8 py-4 font-bold text-base min-h-[56px] sm:rounded-l-none transition-all">
                        Start Quiz →
                    </button>
                </div>
                <div id="token-message" class="text-sm min-h-[1.5rem] text-center font-medium"></div>
                @error('link')
                    <div class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-4 py-3 font-medium">{{ $message }}</div>
                @enderror
            </form>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 max-w-3xl mx-auto">
                <div class="bg-white rounded-lg px-4 py-3 border border-gray-200 text-center">
                    <div class="w-9 h-9 bg-blue-600 rounded-lg flex items-center justify-center text-white text-lg font-bold mx-auto mb-2">🔒</div>
                    <p class="text-sm font-bold text-gray-900 mb-0.5">Secure</p>
                    <p class="text-xs text-gray-600">Proctored environment</p>
                </div>
                <div class="bg-white rounded-lg px-4 py-3 border border-gray-200 text-center">
                    <div class="w-9 h-9 bg-yellow-400 rounded-lg flex items-center justify-center text-gray-900 text-lg font-bold mx-auto mb-2">⚡</div>
                    <p class="text-sm font-bold text-gray-900 mb-0.5">Fast</p>
                    <p class="text-xs text-gray-600">Instant access</p>
                </div>
                <div class="bg-white rounded-lg px-4 py-3 border border-gray-200 text-center">
                    <div class="w-9 h-9 bg-blue-600 rounded-lg flex items-center justify-center text-white text-lg font-bold mx-auto mb-2">✓</div>
                    <p class="text-sm font-bold text-gray-900 mb-0.5">Reliable</p>
                    <p class="text-xs text-gray-600">Desktop optimized</p>
                </div>
            </div>
        </div>
    </main>

    <footer class="shrink-0 border-t border-gray-200 bg-white py-6">
        <div class="mx-auto max-w-6xl px-6 text-center text-sm text-gray-500">&copy; {{ date('Y') }} QuizSnap</div>
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
</script>
@endpush
