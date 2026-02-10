@extends('layouts.app')

@section('title', 'QuizSnap')
@section('body_class', '')

@push('styles')
<style>
    body,
    .home-page-wrap { 
        background: #f8fafc !important; 
    }
    .home-main {
        display: flex;
        flex: 1 1 auto;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 4rem 1.25rem;
    }
    .home-container {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        background: white;
        border-radius: 1rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        padding: 3rem 2rem;
    }
    .home-hero {
        width: 100%;
        text-align: center;
    }
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
    }
    .btn-home-cta:not(.btn-cta-disabled):not(:disabled) { 
        background: #3b82f6; 
        color: #fff !important; 
        font-weight: 600;
        border: none;
    }
    .btn-home-cta:not(.btn-cta-disabled):not(:disabled):hover {
        background: #2563eb;
    }
    .feature-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
        max-width: 58rem;
        margin: 0 auto;
    }
    .feature-card {
        border: 1px solid transparent;
        border-radius: 1rem;
        padding: 1rem 0.9rem;
        min-height: 9.5rem;
    }
    .logo-text { 
        font-size: 1.75rem; 
        font-weight: 700; 
        letter-spacing: -0.02em; 
        display: inline-flex; 
        align-items: center; 
        gap: 0.5rem; 
    }
    .logo-mark { 
        width: 2.25rem; 
        height: 2.25rem; 
        flex-shrink: 0; 
    }
    .home-input { -webkit-user-select: text !important; -moz-user-select: text !important; user-select: text !important; }
    @media (max-width: 1023px) {
        .feature-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 640px) {
        .feature-grid {
            grid-template-columns: 1fr;
        }
        .feature-card {
            padding: 1rem;
            min-height: auto;
        }
        .home-form-container { gap: 0.5rem; }
        .home-container {
            padding: 2rem 1.5rem;
        }
    }
</style>
@endpush

@section('content')
<div class="home-page-wrap min-h-screen flex flex-col font-sans antialiased">
    <header class="shrink-0 bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm">
        <div class="mx-auto max-w-7xl px-6">
            <div class="flex h-20 items-center justify-between">
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
                <nav class="flex items-center gap-6">
                    <a href="{{ route('about-system') }}" class="text-sm font-medium text-slate-700 hover:text-blue-600 transition-colors no-underline" style="text-decoration: none; color: #475569;">
                        About System
                    </a>
                    @if(isset($student) && $student)
                        <a href="{{ route('dashboard') }}" style="display: inline-block; padding: 0.625rem 1.5rem; background-color: #2563eb; color: #ffffff !important; font-weight: 600; font-size: 0.875rem; border-radius: 0.5rem; text-decoration: none; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#1d4ed8'" onmouseout="this.style.backgroundColor='#2563eb'">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('student.account.login.form') }}" style="display: inline-block; padding: 0.625rem 1.5rem; background-color: #2563eb; color: #ffffff !important; font-weight: 600; font-size: 0.875rem; border-radius: 0.5rem; text-decoration: none; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#1d4ed8'" onmouseout="this.style.backgroundColor='#2563eb'">
                            Student Login
                        </a>
                    @endif
                </nav>
            </div>
        </div>
    </header>

    <main class="home-main">
        <div class="home-container">
            <div class="home-hero">
                <div class="mb-8">
                    <h1 class="text-4xl sm:text-5xl font-bold text-slate-900 mb-4 tracking-tight">
                        Welcome to QuizSnap
                    </h1>
                    <p class="text-lg text-slate-600 max-w-2xl mx-auto leading-relaxed">
                        A modern platform for secure and efficient online assessments
                    </p>
                </div>

                <form action="{{ route('student.start-quiz') }}" method="post" class="mb-10 max-w-2xl mx-auto" id="start-quiz-form">
                    @csrf
                    <div class="home-form-container flex flex-col sm:flex-row gap-3 mb-2">
                        <label for="quiz-token" class="sr-only">Quiz token</label>
                        <input type="text" id="quiz-token" name="link" placeholder="Enter quiz token (e.g. KTdie54-3Sx9)" required autocomplete="off"
                            class="home-input flex-1 rounded-lg px-5 py-3 text-base min-h-[52px] sm:rounded-r-none">
                        <button type="submit" id="start-quiz-btn" disabled class="btn-home-cta btn-cta-disabled rounded-lg px-6 py-3 font-semibold text-base min-h-[52px] sm:rounded-l-none">
                            Start Quiz →
                        </button>
                    </div>
                    <div id="token-message" class="text-sm min-h-[1.25rem] text-center font-medium"></div>
                    @error('link')
                        <div class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-4 py-3 font-medium mt-2">{{ $message }}</div>
                    @enderror
                </form>

                <div class="feature-grid">
                    <div class="feature-card" style="background-color: #dbeafe; border-color: #bfdbfe;">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center mx-auto mb-2" style="background-color: #3b82f6;">
                            <svg class="w-6 h-6" style="color: #ffffff;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold text-slate-900 mb-1">Secure</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">Proctored environment with advanced security measures</p>
                    </div>
                    
                    <div class="feature-card" style="background-color: #f3e8ff; border-color: #e9d5ff;">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center mx-auto mb-2" style="background-color: #a855f7;">
                            <svg class="w-6 h-6" style="color: #ffffff;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold text-slate-900 mb-1">Fast</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">Instant access and seamless experience</p>
                    </div>
                    
                    <div class="feature-card" style="background-color: #ccfbf1; border-color: #99f6e4;">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center mx-auto mb-2" style="background-color: #14b8a6;">
                            <svg class="w-6 h-6" style="color: #ffffff;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold text-slate-900 mb-1">Reliable</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">Desktop optimized for consistent performance</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="shrink-0 border-t border-slate-200 bg-white py-8">
        <div class="mx-auto max-w-7xl px-6 text-center">
            <p class="text-sm text-slate-500">&copy; {{ date('Y') }} QuizSnap. All rights reserved.</p>
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
            messageEl.className = 'text-sm min-h-[1.25rem] text-center font-medium';
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
