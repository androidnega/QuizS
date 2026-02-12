{{-- PDF questions export: clean design matching results PDF format --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Questions – {{ $quiz->title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #1f2937; margin: 20px 24px; line-height: 1.4; }
        .header { display: table; width: 100%; margin-bottom: 16px; border-bottom: 2px solid #3b82f6; padding-bottom: 12px; }
        .header-logo { display: table-cell; width: 64px; vertical-align: middle; }
        .header-logo img { max-height: 52px; max-width: 60px; }
        .header-text { display: table-cell; vertical-align: middle; padding-left: 14px; }
        .institution { font-size: 12pt; font-weight: bold; color: #111827; margin: 0 0 4px 0; }
        .report-title { font-size: 11pt; font-weight: bold; color: #1d4ed8; margin: 0; }
        .meta { margin: 16px 0 18px 0; padding: 12px 14px; background: #f8fafc; border-radius: 6px; font-size: 9pt; color: #475569; }
        .meta-row { margin: 4px 0; }
        .meta-label { font-weight: bold; color: #334155; }
        .instructions-section { margin: 18px 0 16px 0; padding: 10px 14px; background: #eff6ff; border-left: 3px solid #3b82f6; font-size: 9pt; }
        .instructions-title { font-weight: bold; color: #1e40af; margin-bottom: 6px; }
        .questions-section { margin-top: 16px; }
        .question { margin-bottom: 20px; page-break-inside: avoid; padding-bottom: 12px; border-bottom: 1px solid #e5e7eb; }
        .question:last-child { border-bottom: none; }
        .question-number { font-weight: bold; font-size: 10pt; color: #1e40af; margin-bottom: 6px; }
        .question-text { font-size: 10pt; color: #374151; margin-bottom: 10px; line-height: 1.5; }
        .question-options { margin-left: 20px; margin-top: 8px; }
        .question-option { font-size: 9.5pt; color: #4b5563; margin-bottom: 4px; line-height: 1.4; }
        .footer { margin-top: 24px; padding-top: 12px; border-top: 1px solid #e2e8f0; font-size: 8pt; color: #64748b; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        @if(!empty($institutionLogoPath))
        <div class="header-logo">
            <img src="{{ $institutionLogoPath }}" alt="">
        </div>
        @endif
        <div class="header-text">
            @if(!empty($institutionName))
                <p class="institution">{{ $institutionName }}</p>
            @endif
            <p class="report-title">Examination Questions — {{ $quiz->title }}</p>
        </div>
    </div>

    <div class="meta">
        <div class="meta-row"><span class="meta-label">Lecturer:</span> {{ $lecturerName }}</div>
        <div class="meta-row"><span class="meta-label">Course:</span> {{ $courseName }} @if($courseCode)({{ $courseCode }})@endif</div>
        <div class="meta-row"><span class="meta-label">Programme:</span> {{ $programme }}</div>
        <div class="meta-row"><span class="meta-label">Date:</span> {{ $examDate }}</div>
        <div class="meta-row"><span class="meta-label">Duration:</span> {{ $duration }}</div>
        <div class="meta-row"><span class="meta-label">Number of questions:</span> {{ $questions->count() }}</div>
    </div>

    <div class="instructions-section">
        <div class="instructions-title">INSTRUCTIONS:</div>
        <div>Answer all questions. Each question carries equal marks. Write clearly and legibly.</div>
    </div>

    <div class="questions-section">
        @foreach($questions as $idx => $question)
            <div class="question">
                <div class="question-number">{{ $idx + 1 }}.</div>
                <div class="question-text">{{ $question->text }}</div>
                @if($question->options && is_array($question->options) && count($question->options) > 0)
                    <div class="question-options">
                        @foreach($question->options as $option)
                            @if(isset($option['key']) && isset($option['text']))
                                <div class="question-option">{{ $option['key'] }}. {{ $option['text'] }}</div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="footer">
        Generated {{ now()->format('M d, Y H:i') }} — QuizSnap
    </div>
</body>
</html>
