@extends('layouts.student-dashboard')

@section('title', $session->quiz->title ?? 'Quiz result')
@php $dashboardTitle = 'Past quiz'; @endphp

@section('dashboard_content')
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('dashboard.my-quizzes') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">← My quizzes</a>
    </div>

    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $session->quiz->title ?? 'Quiz' }}</h1>
        <p class="text-gray-600 mt-1">Taken {{ $session->created_at->format('M j, Y g:i A') }}@if(isset($showFullReview) && $showFullReview) · Question review available for 21 days@endif</p>
    </div>

    @if($session->result && $session->quiz->canShowScore())
    <div class="rounded-xl border-2 border-primary-200 bg-primary-50 p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-2">Your result</h2>
        <div class="flex items-center gap-4 flex-wrap">
            <div class="w-14 h-14 rounded-full flex items-center justify-center
                @if($session->result->score >= 70) bg-success-100
                @elseif($session->result->score >= 50) bg-warning-100
                @else bg-danger-100 @endif">
                <span class="text-2xl font-bold tabular-nums
                    @if($session->result->score >= 70) text-success-700
                    @elseif($session->result->score >= 50) text-warning-700
                    @else text-danger-700 @endif">{{ round($session->result->score, 0) }}%</span>
            </div>
            <div>
                <p class="font-semibold text-gray-900">{{ $session->result->correct_count }} / {{ $session->result->total_questions }} correct</p>
                <p class="text-sm text-gray-500">{{ $session->result->total_questions }} questions</p>
            </div>
        </div>
    </div>
    @endif

    @if(isset($showFullReview) && $showFullReview && $session->quiz->canShowFullReview() && $session->answers->isNotEmpty())
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 sm:p-8">
        <h2 class="text-sm font-semibold text-gray-900 mb-3">Questions & answers</h2>
        <p class="text-xs text-gray-500 mb-4">What you answered and the correct answers. This review is available for 21 days.</p>
        <div class="space-y-4">
            @foreach($session->answers as $idx => $answer)
                @if($answer->question)
                    @php
                        $sessionCorrect = $session->assigned_correct_answers[$answer->question_id] ?? $session->assigned_correct_answers[(string)$answer->question_id] ?? $answer->question->correct_answer;
                        $correct = trim((string)$answer->student_answer) === trim((string)$sessionCorrect);
                        $shuffledOpts = $session->shuffled_question_options[$answer->question_id] ?? $session->shuffled_question_options[(string)$answer->question_id] ?? null;
                        $yourText = null;
                        $correctText = null;
                        if (is_array($shuffledOpts)) {
                            foreach ($shuffledOpts as $o) {
                                $k = $o['key'] ?? $o;
                                $t = $o['text'] ?? $o;
                                if ((string)$k === trim((string)$answer->student_answer)) $yourText = $t;
                                if ((string)$k === trim((string)$sessionCorrect)) $correctText = $t;
                            }
                        }
                    @endphp
                    <div class="border rounded-lg p-3 {{ $correct ? 'bg-success-50/50 border-success-200' : 'bg-danger-50/50 border-danger-200' }}">
                        <p class="text-sm font-medium text-gray-900 mb-1">{{ $idx + 1 }}. {{ $answer->question->text }}</p>
                        <div class="flex flex-wrap gap-2 text-xs mt-2">
                            <span class="text-gray-600">Your answer: <strong>{{ $yourText !== null ? $answer->student_answer . '. ' . $yourText : ($answer->student_answer ?: '—') }}</strong></span>
                            <span class="text-success-700">Correct: <strong>{{ $correctText !== null ? $sessionCorrect . '. ' . $correctText : $sessionCorrect }}</strong></span>
                        </div>
                        @if(!$correct)
                            @php $whyWrong = $answer->question->explanation_wrong ?? $answer->explanation_wrong ?? null; @endphp
                            @if(!empty($whyWrong))
                                <div class="mt-3 pt-3 border-t border-gray-200 text-xs">
                                    <p class="text-danger-700"><strong>Reason:</strong> {{ $whyWrong }}</p>
                                </div>
                            @endif
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
    </div>
    @elseif(isset($showFullReview) && !$showFullReview)
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
        <p class="text-sm text-gray-600">Detailed review (questions and answers) is no longer available. It is kept for 21 days to keep the system clean. Your score above is kept forever.</p>
    </div>
    @elseif($session->quiz->canShowScore() && !$session->quiz->canShowFullReview())
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
        <p class="text-sm text-gray-600">Answer review is not shown for this quiz.</p>
    </div>
    @endif
</div>
@endsection
