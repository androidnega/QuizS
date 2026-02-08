@extends('layouts.student')

@section('title', 'Ready to start')
@section('body_class', 'bg-offwhite')

@section('content')
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

            <a href="{{ route('student.quiz.show') }}" class="btn btn-action w-full py-2.5 text-sm font-semibold">
                Start Quiz
            </a>
        </div>
    </div>
</div>
@endsection
