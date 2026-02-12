@extends('layouts.student-dashboard')

@section('title', isset($session->quiz->title) ? $session->quiz->title : 'Quiz result')
@php
    $dashboardTitle = 'Past quiz';
@endphp

@section('dashboard_content')
@php
    $quiz = $session->quiz ?? null;
    $hasScore = isset($session->result) && $session->result && $quiz && $quiz->canShowScore();
    $canShowFull = $quiz && $quiz->canShowFullReview();
    $reviewWindowOpen = isset($showFullReview) && $showFullReview;
    $hasAnswers = isset($session->answers) && $session->answers->isNotEmpty();
@endphp

<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('dashboard.my-quizzes') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">← My quizzes</a>
    </div>

    <div class="mb-2 flex items-start justify-between gap-4">
        <div class="flex-1">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ isset($quiz->title) ? $quiz->title : 'Quiz' }}</h1>
            <p class="text-base text-gray-600">
                Taken {{ $session->created_at ? $session->created_at->format('M j, Y g:i A') : 'Date not available' }}
                @if($reviewWindowOpen)
                    · Question review available for 21 days
                @endif
            </p>
        </div>
        @if($hasScore)
        <a href="{{ route('dashboard.my-quizzes.download-pdf', ['sessionId' => $session->id]) }}" target="_blank" style="background-color: #dc2626; color: #ffffff;" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-bold rounded-lg hover:opacity-90 transition-all shadow-sm border border-red-700" title="Download PDF">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #ffffff;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <span style="color: #ffffff;">Download PDF</span>
        </a>
        @endif
    </div>

    @if($hasScore)
        @php
            $score = round($session->result->score, 0);
            $correctCount = $session->result->correct_count;
            $totalQuestions = $session->result->total_questions;
            $scoreBg = 'bg-danger-100 border-danger-300';
            $scoreText = 'text-danger-800';
            $label = 'Keep trying';
            if ($score >= 80) {
                $scoreBg = 'bg-success-100 border-success-300';
                $scoreText = 'text-success-800';
                $label = 'Excellent';
            } elseif ($score >= 60) {
                $scoreBg = 'bg-primary-100 border-primary-300';
                $scoreText = 'text-primary-800';
                $label = 'Good';
            } elseif ($score >= 40) {
                $scoreBg = 'bg-warning-100 border-warning-300';
                $scoreText = 'text-warning-800';
                $label = 'Average';
            }
        @endphp
        <div class="rounded-lg border border-gray-200 bg-white p-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Your Result</h2>
            <div class="flex flex-wrap items-center gap-6">
                <div class="rounded-lg border-2 min-w-[7rem] px-8 py-6 flex flex-col items-center justify-center {{ $scoreBg }}">
                    <span class="text-4xl font-bold tabular-nums {{ $scoreText }}">{{ $score }}%</span>
                    <span class="text-sm font-medium {{ $scoreText }} mt-2">{{ $label }}</span>
                </div>
                <div class="rounded-lg border-2 min-w-[7rem] px-8 py-6 flex flex-col items-center justify-center {{ $scoreBg }}">
                    <span class="text-3xl font-bold tabular-nums {{ $scoreText }}">{{ $correctCount }} / {{ $totalQuestions }}</span>
                    <span class="text-sm font-medium {{ $scoreText }} mt-2">Correct</span>
                </div>
                <p class="text-base text-gray-600 self-center font-medium">{{ $totalQuestions }} Questions</p>
            </div>
        </div>
    @endif


    @if($canShowFull && $reviewWindowOpen && $hasAnswers)
        <div class="bg-white border border-gray-200 rounded-lg p-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Questions & Answers</h2>
            <p class="text-sm text-gray-600 mb-6">Review your answers and the correct answers. This review is available for 21 days.</p>
            <div class="space-y-4">
                @foreach($session->answers as $idx => $answer)
                    @php
                        $question = $answer->question ?? null;
                    @endphp
                    @if(!$question)
                        @continue
                    @endif

                    @php
                        $assignedCorrect = is_array($session->assigned_correct_answers) ? $session->assigned_correct_answers : [];
                        $sessionCorrect = $assignedCorrect[$answer->question_id] ?? $assignedCorrect[(string) $answer->question_id] ?? ($question->correct_answer ?? '');
                        $studentAnswerValue = $answer->student_answer ?? '';
                        $correct = trim((string) $studentAnswerValue) === trim((string) $sessionCorrect);

                        $shuffledOpts = null;
                        if (is_array($session->shuffled_question_options)) {
                            $shuffledOpts = $session->shuffled_question_options[$answer->question_id] ?? $session->shuffled_question_options[(string) $answer->question_id] ?? null;
                        }

                        $yourText = null;
                        $correctText = null;
                        if (is_array($shuffledOpts) && !empty($shuffledOpts)) {
                            foreach ($shuffledOpts as $o) {
                                $k = $o['key'] ?? $o;
                                $t = $o['text'] ?? $o;
                                if ((string) $k === trim((string) $studentAnswerValue)) {
                                    $yourText = $t;
                                }
                                if ((string) $k === trim((string) $sessionCorrect)) {
                                    $correctText = $t;
                                }
                            }
                        }

                        if (($yourText === null || $correctText === null) && isset($question->options) && is_array($question->options)) {
                            foreach ($question->options as $opt) {
                                if (!is_array($opt)) {
                                    continue;
                                }
                                $optKey = $opt['key'] ?? '';
                                $optText = $opt['text'] ?? '';
                                if ($yourText === null && (string) $optKey === trim((string) $studentAnswerValue)) {
                                    $yourText = $optText;
                                }
                                if ($correctText === null && (string) $optKey === trim((string) $sessionCorrect)) {
                                    $correctText = $optText;
                                }
                            }
                        }
                    @endphp

                    <div class="border rounded-lg p-5 {{ $correct ? 'bg-success-50/50 border-success-200' : 'bg-danger-50/50 border-danger-200' }}">
                        <p class="text-base font-medium text-gray-900 mb-3">{{ $idx + 1 }}. {{ $question->text ?? 'Question not available' }}</p>
                        <div class="flex flex-wrap gap-4 text-sm mt-3">
                            <span class="text-gray-700">Your answer: <strong class="font-semibold">{{ $yourText !== null ? $studentAnswerValue . '. ' . $yourText : ($studentAnswerValue ?: '—') }}</strong></span>
                            <span class="text-success-700">Correct: <strong class="font-semibold">{{ $correctText !== null ? $sessionCorrect . '. ' . $correctText : ($sessionCorrect ?: '—') }}</strong></span>
                        </div>

                        @if(!$correct)
                            @php
                                $whyWrong = $question->explanation_wrong ?? $answer->explanation_wrong ?? null;
                            @endphp
                            @if(!empty($whyWrong))
                                <div class="mt-4 pt-4 border-t border-gray-200 text-sm">
                                    <p class="text-danger-700"><strong>Reason:</strong> {{ $whyWrong }}</p>
                                </div>
                            @endif
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if(isset($showFullReview) && !$showFullReview)
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-6">
            <p class="text-base text-gray-700">Detailed review (questions and answers) is no longer available. It is kept for 21 days to keep the system clean. Your score above is kept forever.</p>
        </div>
    @endif

    @if($quiz && $quiz->canShowScore() && !$quiz->canShowFullReview())
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-6">
            <p class="text-base text-gray-700">Answer review is not shown for this quiz.</p>
        </div>
    @endif

    @if($canShowFull && $reviewWindowOpen && isset($session->answers) && $session->answers->isEmpty())
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-6">
            <p class="text-base text-gray-700">No answers recorded for this quiz session.</p>
        </div>
    @endif
</div>
@endsection
