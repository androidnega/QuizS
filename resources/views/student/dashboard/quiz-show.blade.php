@extends('layouts.student-dashboard')

@section('title', isset($session->quiz->title) ? $session->quiz->title : 'Quiz result')
@php $dashboardTitle = 'Past quiz'; @endphp

@section('dashboard_content')
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('dashboard.my-quizzes') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">← My quizzes</a>
    </div>

    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ isset($session->quiz->title) ? $session->quiz->title : 'Quiz' }}</h1>
        <p class="text-gray-600 mt-1">Taken {{ $session->created_at ? $session->created_at->format('M j, Y g:i A') : 'Date not available' }}@if(isset($showFullReview) && $showFullReview) · Question review available for 21 days@endif</p>
    </div>

    @if(isset($session->result) && $session->result && isset($session->quiz) && $session->quiz->canShowScore())
    @php
        $score = round($session->result->score, 0);
        $correctCount = $session->result->correct_count;
        $totalQuestions = $session->result->total_questions;
        if ($score >= 80) { $scoreBg = 'bg-success-100 border-success-300'; $scoreText = 'text-success-800'; $label = 'Excellent'; }
        elseif ($score >= 60) { $scoreBg = 'bg-primary-100 border-primary-300'; $scoreText = 'text-primary-800'; $label = 'Good'; }
        elseif ($score >= 40) { $scoreBg = 'bg-warning-100 border-warning-300'; $scoreText = 'text-warning-800'; $label = 'Average'; }
        else { $scoreBg = 'bg-danger-100 border-danger-300'; $scoreText = 'text-danger-800'; $label = 'Keep trying'; }
    @endphp
    <div class="rounded-xl border-2 border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Your result</h2>
        <div class="flex flex-wrap items-center gap-4">
            <div class="rounded-xl border-2 min-w-[6rem] px-6 py-4 flex flex-col items-center justify-center {{ $scoreBg }}">
                <span class="text-3xl font-bold tabular-nums {{ $scoreText }}">{{ $score }}%</span>
                <span class="text-xs font-medium {{ $scoreText }} mt-0.5">{{ $label }}</span>
            </div>
            <div class="rounded-xl border-2 min-w-[6rem] px-6 py-4 flex flex-col items-center justify-center {{ $scoreBg }}">
                <span class="text-2xl font-bold tabular-nums {{ $scoreText }}">{{ $correctCount }} / {{ $totalQuestions }}</span>
                <span class="text-xs font-medium {{ $scoreText }} mt-0.5">correct</span>
            </div>
            <p class="text-sm text-gray-500 self-center">{{ $totalQuestions }} questions</p>
        </div>
    </div>
    @endif

    @if(!empty($session->pre_face_image) || !empty($session->post_face_image))
    <div class="rounded-xl border-2 border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Face verification photos</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @if(!empty($session->pre_face_image))
            <div class="space-y-2">
                <p class="text-sm font-medium text-gray-700">Before quiz</p>
                @php
                    $preFaceUrl = (str_starts_with($session->pre_face_image, 'http') || str_starts_with($session->pre_face_image, 'https'))
                        ? $session->pre_face_image 
                        : asset('storage/' . $session->pre_face_image);
                @endphp
                <img src="{{ $preFaceUrl }}" alt="Before quiz photo" class="w-full h-auto rounded-lg border border-gray-200">
                <p class="text-xs text-gray-500">Captured before starting</p>
            </div>
            @endif

            @if(!empty($session->post_face_image))
            <div class="space-y-2">
                <p class="text-sm font-medium text-gray-700">After quiz</p>
                @php
                    $postFaceUrl = (str_starts_with($session->post_face_image, 'http') || str_starts_with($session->post_face_image, 'https'))
                        ? $session->post_face_image 
                        : asset('storage/' . $session->post_face_image);
                @endphp
                <img src="{{ $postFaceUrl }}" alt="After quiz photo" class="w-full h-auto rounded-lg border border-gray-200">
                <p class="text-xs text-gray-500">Captured {{ !empty($session->post_face_captured_at) ? $session->post_face_captured_at->format('M j, Y g:i A') : 'after submission' }}</p>
            </div>
            @endif
        </div>
    </div>
    @endif

    @if(isset($showFullReview) && $showFullReview && isset($session->quiz) && $session->quiz->canShowFullReview() && isset($session->answers) && $session->answers->isNotEmpty())
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 sm:p-8">
        <h2 class="text-sm font-semibold text-gray-900 mb-3">Questions & answers</h2>
        <p class="text-xs text-gray-500 mb-4">What you answered and the correct answers. This review is available for 21 days.</p>
        <div class="space-y-4">
            @foreach($session->answers as $idx => $answer)
                @if(isset($answer->question) && $answer->question)
                    @php
                        $assignedCorrect = is_array($session->assigned_correct_answers) ? $session->assigned_correct_answers : [];
                        $sessionCorrect = $assignedCorrect[$answer->question_id] ?? $assignedCorrect[(string)$answer->question_id] ?? ($answer->question->correct_answer ?? '');
                        $studentAnswerValue = $answer->student_answer ?? '';
                        $correct = trim((string)$studentAnswerValue) === trim((string)$sessionCorrect);
                        $shuffledOpts = null;
                        if (is_array($session->shuffled_question_options)) {
                            $shuffledOpts = $session->shuffled_question_options[$answer->question_id] ?? $session->shuffled_question_options[(string)$answer->question_id] ?? null;
                        }
                        $yourText = null;
                        $correctText = null;
                        
                        // Try shuffled options first
                        if (is_array($shuffledOpts) && !empty($shuffledOpts)) {
                            foreach ($shuffledOpts as $o) {
                                $k = $o['key'] ?? $o;
                                $t = $o['text'] ?? $o;
                                if ((string)$k === trim((string)$studentAnswerValue)) $yourText = $t;
                                if ((string)$k === trim((string)$sessionCorrect)) $correctText = $t;
                            }
                        }
                        
                        // Fallback to question's original options if shuffled not available
                        if (($yourText === null || $correctText === null) && isset($answer->question->options) && is_array($answer->question->options)) {
                            foreach ($answer->question->options as $opt) {
                                if (!is_array($opt)) continue;
                                $optKey = $opt['key'] ?? '';
                                $optText = $opt['text'] ?? '';
                                if ($yourText === null && (string)$optKey === trim((string)$studentAnswerValue)) {
                                    $yourText = $optText;
                                }
                                if ($correctText === null && (string)$optKey === trim((string)$sessionCorrect)) {
                                    $correctText = $optText;
                                }
                            }
                        }
                        
                        $questionText = $answer->question->text ?? 'Question not available';
                    @endphp
                    <div class="border rounded-lg p-3 {{ $correct ? 'bg-success-50/50 border-success-200' : 'bg-danger-50/50 border-danger-200' }}">
                        <p class="text-sm font-medium text-gray-900 mb-1">{{ $idx + 1 }}. {{ $questionText }}</p>
                        <div class="flex flex-wrap gap-2 text-xs mt-2">
                            <span class="text-gray-600">Your answer: <strong>{{ $yourText !== null ? $studentAnswerValue . '. ' . $yourText : ($studentAnswerValue ?: '—') }}</strong></span>
                            <span class="text-success-700">Correct: <strong>{{ $correctText !== null ? $sessionCorrect . '. ' . $correctText : ($sessionCorrect ?: '—') }}</strong></span>
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
    @endif

    @if(isset($showFullReview) && !$showFullReview)
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
        <p class="text-sm text-gray-600">Detailed review (questions and answers) is no longer available. It is kept for 21 days to keep the system clean. Your score above is kept forever.</p>
    </div>
    @endif

    @if(isset($session->quiz) && $session->quiz->canShowScore() && !$session->quiz->canShowFullReview())
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
        <p class="text-sm text-gray-600">Answer review is not shown for this quiz.</p>
    </div>
    @endif

    @if(isset($session->quiz) && $session->quiz->canShowFullReview() && isset($showFullReview) && $showFullReview && isset($session->answers) && $session->answers->isEmpty())
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
        <p class="text-sm text-gray-600">No answers recorded for this quiz session.</p>
    </div>
    @endif

</div>
@endsection
