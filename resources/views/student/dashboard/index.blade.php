@extends('layouts.student-dashboard')

@section('title', 'Dashboard')
@php $dashboardTitle = 'Dashboard'; @endphp

@section('dashboard_content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Welcome, {{ $student->display_name }}</h1>
        <p class="text-gray-600 mt-1">View and manage your quiz history here.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Quizzes taken</p>
            <p class="mt-1 text-3xl font-bold tabular-nums text-gray-900">{{ $sessionsCount }}</p>
            <a href="{{ route('student.dashboard.quizzes') }}" class="mt-2 inline-block text-sm font-medium text-primary-600 hover:text-primary-700">View all →</a>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Index number</p>
            <p class="mt-1 text-lg font-mono font-semibold text-gray-900">{{ $student->index_number }}</p>
            <a href="{{ route('student.dashboard.profile') }}" class="mt-2 inline-block text-sm font-medium text-primary-600 hover:text-primary-700">Edit profile →</a>
        </div>
    </div>

    @if(isset($classGroups) && $classGroups->isNotEmpty())
    <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <h2 class="text-sm font-semibold text-gray-800 mb-2">My groups</h2>
        <p class="text-xs text-gray-500 mb-3">Class groups you belong to (as added by your examiner).</p>
        <ul class="flex flex-wrap gap-2">
            @foreach($classGroups as $group)
            <li class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-800">{{ $group->name }}</li>
            @endforeach
        </ul>
    </section>
    @endif

    @if($recentSessions->isNotEmpty())
    <section class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-800">Recent quizzes</h2>
            <a href="{{ route('student.dashboard.quizzes') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">See all</a>
        </div>
        <ul class="divide-y divide-gray-100">
            @foreach($recentSessions as $session)
            <li class="px-4 py-3 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p class="font-medium text-gray-900">{{ $session->quiz->title ?? 'Quiz' }}</p>
                    <p class="text-xs text-gray-500">{{ $session->created_at->format('M j, Y g:i A') }}</p>
                </div>
                @if($session->result)
                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-sm font-medium text-gray-800">
                    Score: {{ number_format($session->result->score, 1) }}%
                </span>
                @else
                <span class="text-sm text-gray-500">—</span>
                @endif
            </li>
            @endforeach
        </ul>
    </section>
    @else
    <div class="rounded-lg border border-gray-200 bg-white p-8 text-center">
        <p class="text-gray-600">You haven't taken any quizzes yet.</p>
        <a href="{{ route('student.landing') }}" class="mt-3 inline-block text-primary-600 font-medium hover:underline">Start a quiz →</a>
    </div>
    @endif
</div>
@endsection
