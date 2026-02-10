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
    .home-hero {
        width: 100%;
        max-width: 72rem;
        text-align: center;
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
    }
</style>
@endpush

@section('content')
<div class="home-page-wrap min-h-screen flex flex-col font-sans antialiased">
    <header class="shrink-0 bg-white/80 backdrop-blur-sm border-b border-slate-200/50 sticky top-0 z-50">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
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
            <div class="flex items-center gap-3">
                @if(isset($student) && $student)
                    <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-slate-700 px-4 py-2 rounded-lg hover:bg-slate-100 transition-all no-underline">
                        Dashboard
                    </a>
                @endif
            </div>
        </div>
    </header>

    <main class="home-main">
        <div class="home-hero">
            <div class="mb-10">
                <h1 class="text-4xl sm:text-5xl font-bold text-slate-900 mb-4 tracking-tight">
                    Welcome to QuizSnap
                </h1>
                <p class="text-lg text-slate-600 max-w-2xl mx-auto leading-relaxed">
                    A modern platform for secure and efficient online assessments
                </p>
            </div>

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
    </main>

    <footer class="shrink-0 border-t border-slate-200 bg-white py-8">
        <div class="mx-auto max-w-7xl px-6 text-center">
            <p class="text-sm text-slate-500">&copy; {{ date('Y') }} QuizSnap. All rights reserved.</p>
        </div>
    </footer>
</div>
@endsection
