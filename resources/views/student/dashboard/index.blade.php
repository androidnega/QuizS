@extends('layouts.student-dashboard')

@section('title', 'Dashboard')
@php $dashboardTitle = 'Dashboard'; @endphp

@section('dashboard_content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $greeting ?? 'Hello' }}, {{ $student->first_name }}</h1>
        <p class="text-gray-600 mt-1">Your quiz history. Marks are kept forever; open a quiz to see your score and, for the last 21 days, questions and answers.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <a href="{{ route('dashboard.my-quizzes') }}" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:shadow-md hover:border-primary-200 transition-all block">
            <p class="text-sm font-medium text-gray-500">Quizzes taken</p>
            <p class="mt-1 text-3xl font-bold tabular-nums text-gray-900">{{ $sessionsCount }}</p>
            <span class="mt-2 inline-block text-sm font-medium text-primary-600">View all →</span>
        </a>
        <a href="{{ route('dashboard.my-profile') }}" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:shadow-md hover:border-primary-200 transition-all block">
            <p class="text-sm font-medium text-gray-500">Index number</p>
            <p class="mt-1 text-lg font-mono font-semibold text-gray-900">{{ $student->index_number }}</p>
            <span class="mt-2 inline-block text-sm font-medium text-primary-600">Edit profile →</span>
        </a>
        <a href="{{ route('student.landing') }}" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:shadow-md hover:border-primary-200 transition-all block">
            <p class="text-sm font-medium text-gray-500">Start a quiz</p>
            <p class="mt-1 text-lg font-semibold text-gray-900">Enter token</p>
            <span class="mt-2 inline-block text-sm font-medium text-primary-600">Go →</span>
        </a>
    </div>

    @if(isset($classGroups) && $classGroups->isNotEmpty())
    <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <h2 class="text-sm font-semibold text-gray-800 mb-2">My groups</h2>
        <p class="text-xs text-gray-500 mb-3">Class groups you belong to.</p>
        <ul class="flex flex-wrap gap-2">
            @foreach($classGroups as $group)
            <li class="inline-flex items-center rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-800">{{ $group->name }}</li>
            @endforeach
        </ul>
    </section>
    @endif

    <section>
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-gray-800">Recent quizzes</h2>
            <a href="{{ route('dashboard.my-quizzes') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">See all</a>
        </div>
    @if($recentSessions->isNotEmpty())
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach($recentSessions as $s)
            <a href="{{ route('dashboard.my-quizzes.show', $s) }}" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm hover:border-primary-200 hover:shadow transition-all block">
                <p class="font-medium text-gray-900 truncate">{{ $s->quiz->title ?? 'Quiz' }}</p>
                <p class="text-xs text-gray-500 mt-0.5">{{ $s->created_at->format('M j, Y g:i A') }}</p>
                @if($s->result)
                <p class="mt-2 text-sm font-semibold text-gray-800">{{ number_format($s->result->score, 1) }}%</p>
                @else
                <p class="mt-2 text-sm text-gray-500">—</p>
                @endif
            </a>
            @endforeach
        </div>
    @else
        <div class="rounded-xl border border-gray-200 bg-white p-8 text-center">
            <p class="text-gray-600">You haven't taken any quizzes yet.</p>
            <a href="{{ route('student.landing') }}" class="mt-3 inline-block text-primary-600 font-medium hover:underline">Start a quiz →</a>
        </div>
    @endif
    </section>
</div>
@endsection
