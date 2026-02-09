@extends('layouts.student')

@section('title', 'Ready to start')
@section('body_class', 'bg-offwhite')

@section('content')
{{-- Fullscreen gate: block until full screen or maximized --}}
<div id="fullscreen-gate" class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-900/90 px-4" aria-live="polite">
    <div class="bg-white border border-gray-200 rounded-xl p-6 max-w-md w-full shadow-lg text-center">
        <h2 class="text-lg font-bold text-gray-800 mb-2">Full screen required</h2>
        <p class="text-sm text-gray-600 mb-4">Enter full screen or maximize this window to continue. Do not minimize or resize during the quiz.</p>
        <p class="text-xs text-gray-500 mb-4">Press <kbd class="px-1.5 py-0.5 bg-gray-100 rounded">F11</kbd> for full screen, or maximize the window.</p>
        <p id="fullscreen-gate-status" class="text-sm font-medium text-primary-600 hidden">Window OK. You can start the quiz below.</p>
    </div>
</div>

<div class="min-h-[100dvh] min-h-screen flex items-center justify-center px-4 py-6">
    <div class="max-w-md w-full">
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <h1 class="text-xl font-bold text-gray-800 mb-1">System ready</h1>
            <p class="text-gray-600 text-sm mb-4">Review below. When you click Start Quiz, the timer begins immediately.</p>

            <div class="space-y-3 mb-4">
                <div class="border border-gray-200 rounded-lg p-3">
                    <p class="text-xs text-gray-500 uppercase tracking-wide mb-0.5">Course</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $courseName }}</p>
                </div>
                <div class="border border-gray-200 rounded-lg p-3">
                    <p class="text-xs text-gray-500 uppercase tracking-wide mb-0.5">Duration</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $durationMinutes }} min</p>
                </div>
                <div class="border border-danger-200 bg-danger-50 rounded-lg p-3">
                    <p class="text-xs font-semibold text-danger-800 uppercase tracking-wide mb-0.5">Warning</p>
                    <p class="text-xs text-danger-700">Do not switch tabs or leave this window. Tab switching is monitored; violations are logged. Multiple violations may auto-submit your quiz.</p>
                </div>
            </div>

            <a href="{{ route('student.quiz.show') }}" id="start-quiz-link" class="btn btn-action w-full py-2.5 text-sm font-semibold pointer-events-none opacity-60" aria-disabled="true">
                Start Quiz
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    function isFullscreenOrMaximized() {
        if (document.fullscreenElement) return true;
        if (document.webkitFullscreenElement) return true;
        var margin = 50;
        var w = window.outerWidth || window.innerWidth;
        var h = window.outerHeight || window.innerHeight;
        var availW = window.screen?.availWidth ?? window.innerWidth;
        var availH = window.screen?.availHeight ?? window.innerHeight;
        return (w >= availW - margin && h >= availH - margin);
    }
    function checkGate() {
        var gate = document.getElementById('fullscreen-gate');
        var link = document.getElementById('start-quiz-link');
        var status = document.getElementById('fullscreen-gate-status');
        if (!gate || !link) return;
        if (isFullscreenOrMaximized()) {
            gate.classList.add('hidden');
            link.classList.remove('pointer-events-none', 'opacity-60');
            link.setAttribute('aria-disabled', 'false');
            if (status) { status.classList.remove('hidden'); }
        } else {
            gate.classList.remove('hidden');
            link.classList.add('pointer-events-none', 'opacity-60');
            link.setAttribute('aria-disabled', 'true');
            if (status) { status.classList.add('hidden'); }
        }
    }
    checkGate();
    window.addEventListener('resize', checkGate);
    document.addEventListener('fullscreenchange', checkGate);
    document.addEventListener('webkitfullscreenchange', checkGate);
    setInterval(checkGate, 1000);
})();
</script>
@endpush
@endsection
