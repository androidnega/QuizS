@extends('layouts.app')

@section('title', 'QuizSnap')
@section('body_class', '')

@push('styles')
<style>
    body { background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%); }
    .home-input { outline: none; border: 1px solid #e5e7eb; background: #fff; transition: border-color 0.15s, background 0.15s; }
    .home-input:focus { border-color: #9ca3af; box-shadow: 0 0 0 2px rgba(0, 85, 255, 0.12); }
    .home-input.token-valid { background-color: #ecfdf5 !important; border-color: #10b981 !important; color: #059669; }
    .home-input.token-invalid { background-color: #fef2f2 !important; border-color: #fecaca; }
    .home-input.token-loading { background-color: #fffbeb !important; border-color: #fde68a; }
    .btn-home-cta.btn-cta-disabled, .btn-home-cta:disabled { background-color: #9ca3af !important; color: #4b5563 !important; box-shadow: none !important; cursor: not-allowed; pointer-events: none; }
    .btn-home-cta:not(.btn-cta-disabled):not(:disabled) { background-color: #FFD500 !important; color: #0055FF !important; }
    .logo-text { font-size: 1.75rem; font-weight: 700; letter-spacing: -0.02em; }
    @media (max-width: 639px) {
        .home-form-container { gap: 0.75rem; }
    }
    .home-input {
        -webkit-user-select: text !important;
        -moz-user-select: text !important;
        user-select: text !important;
    }
</style>
@endpush

@section('content')
<div class="min-h-screen flex flex-col font-sans antialiased">
    {{-- Header --}}
    <header class="shrink-0 bg-white border-b border-gray-200" style="backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.95);">
        <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-6">
            <a href="{{ route('student.landing') }}" class="logo-text text-gray-900" style="text-decoration: none;">
                <span class="text-homeBlue">Quiz</span><span class="text-homeYellow">Snap</span>
            </a>
            <a href="{{ route('login') }}" class="text-sm font-semibold text-homeBlue px-4 py-2 rounded-lg hover:bg-blue-50" style="text-decoration: none; transition: all 150ms;">Staff Login</a>
        </div>
    </header>

    <main class="flex flex-1 flex-col items-center justify-center px-6 py-16">
        <div class="w-full max-w-2xl text-center">
            {{-- Hero Section --}}
            <div class="mb-10">
                <h1 class="text-4xl font-bold text-gray-900 mb-4 sm:text-5xl" style="line-height: 1.1;">
                    Start Your Quiz<br>
                    <span class="text-homeBlue">Instantly</span>
                </h1>
                <p class="text-lg text-gray-600 max-w-md mx-auto">
                    {{-- Do not change this text: "Enter your quiz token below (from your teacher — not a link)" --}}
                    Enter your <strong>quiz token</strong> below (from your teacher — not a link)
                </p>
            </div>

            {{-- Form (No Card) --}}
            <div class="mb-12 max-w-lg mx-auto">
                <form action="{{ route('student.start-quiz') }}" method="post" class="space-y-5" id="start-quiz-form">
                    @csrf
                    <div class="home-form-container flex flex-col gap-3 sm:flex-row sm:gap-0">
                        <label for="quiz-token" class="sr-only">Quiz token</label>
                        <input type="text"
                            id="quiz-token"
                            name="link"
                            placeholder="e.g. KTdie54-3Sx9"
                            required
                            autocomplete="off"
                            class="home-input flex-1 rounded-xl px-4 py-3 transition-all sm:rounded-r-none"
                            style="font-size: 16px; min-height: 52px;">
                        <button type="submit" id="start-quiz-btn" disabled class="btn-home-cta btn-cta-disabled rounded-xl px-6 py-3 font-bold shadow-lg sm:rounded-l-none" style="min-height: 52px; transition: all 150ms;">
                            Start Quiz →
                        </button>
                    </div>
                    <div id="token-message" class="text-sm min-h-[1.5rem] text-left"></div>
                    @error('link')
                        <div class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-4 py-3">
                            {{ $message }}
                        </div>
                    @enderror
                </form>
            </div>

            {{-- Info Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 max-w-2xl mx-auto">
                <div class="bg-white rounded-xl p-4 border border-gray-100">
                    <div class="text-2xl mb-2">🔒</div>
                    <p class="text-sm font-semibold text-gray-900 mb-1">Secure</p>
                    <p class="text-xs text-gray-500">Proctored environment</p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100">
                    <div class="text-2xl mb-2">⚡</div>
                    <p class="text-sm font-semibold text-gray-900 mb-1">Fast</p>
                    <p class="text-xs text-gray-500">Instant quiz access</p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100">
                    <div class="text-2xl mb-2">📱</div>
                    <p class="text-sm font-semibold text-gray-900 mb-1">Reliable</p>
                    <p class="text-xs text-gray-500">Desktop optimized</p>
                </div>
            </div>
        </div>
    </main>

    <footer class="shrink-0 border-t border-gray-200 bg-white py-6">
        <div class="mx-auto max-w-5xl px-6 text-center text-sm text-gray-500">
            &copy; {{ date('Y') }} QuizSnap. All rights reserved.
        </div>
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
