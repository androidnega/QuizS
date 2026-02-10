@extends('layouts.app')

@section('title', 'About QuizSnap')
@section('body_class', '')

@push('styles')
<style>
    body { background: #f8fafc !important; }
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
</style>
@endpush

@section('content')
<div class="min-h-screen flex flex-col font-sans antialiased">
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
                <a href="{{ route('about-system') }}" class="text-sm font-medium text-slate-900 hover:text-slate-900 transition-all no-underline">
                    About System
                </a>
                @if(isset($student) && $student)
                    <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-white bg-blue-600 px-4 py-2 rounded-lg hover:bg-blue-700 transition-all no-underline">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('student.account.login.form') }}" class="text-sm font-semibold text-white bg-blue-600 px-4 py-2 rounded-lg hover:bg-blue-700 transition-all no-underline">
                        Student Login
                    </a>
                @endif
            </div>
        </div>
    </header>

    <main class="flex-1 px-6 py-12">
        <div class="max-w-4xl mx-auto">
            <div class="mb-8">
                <a href="{{ route('student.landing') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium inline-flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Home
                </a>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-8 md:p-12">
                <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-6">How QuizSnap Works</h1>
                
                <p class="text-lg text-slate-700 mb-8 leading-relaxed">
                    QuizSnap is a secure online assessment platform designed for educational institutions. 
                    Here's everything you need to know about taking quizzes on QuizSnap.
                </p>

                <div class="space-y-10">
                    <!-- Getting Started -->
                    <section>
                        <h2 class="text-2xl font-semibold text-slate-900 mb-4 flex items-center gap-2">
                            <span class="flex items-center justify-center w-8 h-8 bg-blue-100 text-blue-600 rounded-lg font-bold text-sm">1</span>
                            Getting Started
                        </h2>
                        <div class="pl-10 space-y-3 text-slate-700">
                            <p><strong>Receive your token:</strong> Your lecturer or examiner will provide you with a unique quiz token (e.g., KTdie54-3Sx9).</p>
                            <p><strong>Enter the token:</strong> On the homepage, enter the token in the input field and click "Start Quiz".</p>
                            <p><strong>Login to your account:</strong> You can also log in to your student account to see all available quizzes on your dashboard.</p>
                        </div>
                    </section>

                    <!-- Verification Process -->
                    <section>
                        <h2 class="text-2xl font-semibold text-slate-900 mb-4 flex items-center gap-2">
                            <span class="flex items-center justify-center w-8 h-8 bg-purple-100 text-purple-600 rounded-lg font-bold text-sm">2</span>
                            Verification Process
                        </h2>
                        <div class="pl-10 space-y-3 text-slate-700">
                            <p><strong>Index number:</strong> Enter your student index number for verification.</p>
                            <p><strong>Phone verification:</strong> If it's your first time, you'll verify your phone number with an OTP.</p>
                            <p><strong>Pre-quiz photo:</strong> Take a clear photo of your face using your device camera. This helps verify your identity.</p>
                        </div>
                    </section>

                    <!-- Taking the Quiz -->
                    <section>
                        <h2 class="text-2xl font-semibold text-slate-900 mb-4 flex items-center gap-2">
                            <span class="flex items-center justify-center w-8 h-8 bg-teal-100 text-teal-600 rounded-lg font-bold text-sm">3</span>
                            Taking the Quiz
                        </h2>
                        <div class="pl-10 space-y-3 text-slate-700">
                            <p><strong>Timer:</strong> Once you start, a countdown timer begins. You must complete the quiz before time runs out.</p>
                            <p><strong>Answer questions:</strong> Questions are displayed one screen at a time. Select your answers carefully.</p>
                            <p><strong>Auto-save:</strong> Your answers are automatically saved as you progress.</p>
                            <p><strong>Stay focused:</strong> Remain on the quiz tab. Switching tabs may be logged as a violation.</p>
                            <p><strong>Desktop only:</strong> QuizSnap is optimized for desktop browsers. Mobile devices are not supported.</p>
                        </div>
                    </section>

                    <!-- Proctoring & Security -->
                    <section>
                        <h2 class="text-2xl font-semibold text-slate-900 mb-4 flex items-center gap-2">
                            <span class="flex items-center justify-center w-8 h-8 bg-orange-100 text-orange-600 rounded-lg font-bold text-sm">4</span>
                            Proctoring & Security
                        </h2>
                        <div class="pl-10 space-y-3 text-slate-700">
                            <p><strong>Face verification:</strong> Pre and post-quiz photos help verify that you completed the quiz.</p>
                            <p><strong>Tab monitoring:</strong> The system detects when you switch tabs or leave the quiz window.</p>
                            <p><strong>One device per session:</strong> You cannot take the same quiz from multiple devices simultaneously.</p>
                            <p><strong>Fair assessment:</strong> These measures ensure a fair testing environment for all students.</p>
                        </div>
                    </section>

                    <!-- Submitting & Results -->
                    <section>
                        <h2 class="text-2xl font-semibold text-slate-900 mb-4 flex items-center gap-2">
                            <span class="flex items-center justify-center w-8 h-8 bg-green-100 text-green-600 rounded-lg font-bold text-sm">5</span>
                            Submitting & Results
                        </h2>
                        <div class="pl-10 space-y-3 text-slate-700">
                            <p><strong>Final photo:</strong> After completing all questions, take a final photo to submit your quiz.</p>
                            <p><strong>Instant results:</strong> Your score is calculated immediately after submission.</p>
                            <p><strong>Review answers:</strong> You can review your answers and see correct answers (if enabled by your lecturer).</p>
                            <p><strong>Results history:</strong> Your scores are saved forever. Detailed question reviews are available for 21 days.</p>
                        </div>
                    </section>

                    <!-- Technical Requirements -->
                    <section>
                        <h2 class="text-2xl font-semibold text-slate-900 mb-4 flex items-center gap-2">
                            <span class="flex items-center justify-center w-8 h-8 bg-red-100 text-red-600 rounded-lg font-bold text-sm">6</span>
                            Technical Requirements
                        </h2>
                        <div class="pl-10 space-y-3 text-slate-700">
                            <p><strong>Device:</strong> Desktop or laptop computer (mobile devices not supported).</p>
                            <p><strong>Browser:</strong> Modern browser with JavaScript enabled (Chrome, Firefox, Safari, Edge).</p>
                            <p><strong>Camera:</strong> Working webcam for face verification photos.</p>
                            <p><strong>Internet:</strong> Stable internet connection throughout the quiz.</p>
                            <p><strong>Environment:</strong> Quiet space with minimal distractions.</p>
                        </div>
                    </section>

                    <!-- Tips for Success -->
                    <section class="bg-blue-50 border border-blue-100 rounded-xl p-6">
                        <h2 class="text-xl font-semibold text-blue-900 mb-4">Tips for Success</h2>
                        <ul class="space-y-2 text-slate-700">
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>Test your camera and internet connection before starting.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>Keep your face visible during photo capture for best results.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>Don't switch tabs during the quiz - it may be flagged as a violation.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>Read each question carefully and manage your time wisely.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>Ensure you're in a quiet environment with good lighting.</span>
                            </li>
                        </ul>
                    </section>

                    <!-- Need Help? -->
                    <section class="bg-slate-50 border border-slate-200 rounded-xl p-6">
                        <h2 class="text-xl font-semibold text-slate-900 mb-3">Need Help?</h2>
                        <p class="text-slate-700 mb-3">
                            If you encounter any issues during your quiz, contact your lecturer or examiner immediately.
                        </p>
                        <p class="text-sm text-slate-600">
                            Common issues: camera not working, token invalid, connection problems, or technical errors.
                        </p>
                    </section>
                </div>

                <div class="mt-10 pt-8 border-t border-slate-200 text-center">
                    <a href="{{ route('student.landing') }}" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                        Go to Homepage
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </a>
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
