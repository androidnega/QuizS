@extends('layouts.student-dashboard')

@section('title', 'My Quizzes')
@php $dashboardTitle = 'My Quizzes'; @endphp

@section('dashboard_content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Quizzes I've taken</h1>
        <p class="text-gray-600 mt-1">History of quizzes you have attempted.</p>
    </div>

    @if($sessions->isNotEmpty())
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
        <ul class="divide-y divide-gray-100">
            @foreach($sessions as $session)
            <li class="px-4 py-4 flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-medium text-gray-900 truncate">{{ $session->quiz->title ?? 'Quiz' }}</p>
                    <p class="text-sm text-gray-500">{{ $session->created_at->format('M j, Y g:i A') }}</p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    @if($session->result)
                    <span class="inline-flex items-center rounded-full bg-primary-50 px-3 py-1 text-sm font-medium text-primary-700">
                        {{ number_format($session->result->score, 1) }}%
                    </span>
                    <span class="text-sm text-gray-500">
                        {{ $session->result->correct_count }}/{{ $session->result->total_questions }} correct
                    </span>
                    @else
                    <span class="text-sm text-gray-500">No result</span>
                    @endif
                </div>
            </li>
            @endforeach
        </ul>
        @if($sessions->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $sessions->links() }}
        </div>
        @endif
    </div>
    @else
    <div class="rounded-lg border border-gray-200 bg-white p-8 text-center">
        <p class="text-gray-600">You haven't taken any quizzes yet.</p>
        <a href="{{ route('student.landing') }}" class="mt-3 inline-block text-primary-600 font-medium hover:underline">Start a quiz →</a>
    </div>
    @endif
</div>
@endsection
