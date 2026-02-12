{{-- PDF questions export: exact format matching exam paper header --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Questions – {{ $quiz->title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #000; margin: 20px 24px; line-height: 1.4; }
        .header { display: table; width: 100%; margin-bottom: 20px; }
        .header-logo { display: table-cell; width: 80px; vertical-align: middle; padding-right: 15px; }
        .header-logo img { max-height: 70px; max-width: 80px; }
        .header-text { display: table-cell; vertical-align: middle; text-align: center; }
        .header-text p { font-weight: bold; text-transform: uppercase; margin-bottom: 5px; font-size: 12pt; color: #000; }
        .exam-info { margin-top: 20px; margin-bottom: 20px; }
        .exam-info-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-weight: bold; text-transform: uppercase; font-size: 11pt; }
        .exam-info-row.centered { justify-content: center; text-align: center; }
        .exam-info-row .left { text-align: left; }
        .exam-info-row .right { text-align: right; }
        .instructions-title { margin-top: 20px; margin-bottom: 10px; font-weight: bold; text-transform: uppercase; font-size: 11pt; }
        .questions-section { margin-top: 20px; }
        .question { margin-bottom: 20px; page-break-inside: avoid; padding-bottom: 12px; }
        .question-number { font-weight: bold; font-size: 11pt; margin-bottom: 6px; }
        .question-text { font-size: 10pt; color: #000; margin-bottom: 10px; line-height: 1.5; }
        .question-options { margin-left: 20px; margin-top: 8px; }
        .question-option { font-size: 10pt; color: #000; margin-bottom: 4px; line-height: 1.4; }
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
            <p>{{ $institutionName }}</p>
            <p>FACULTY OF APPLIED ARTS AND TECHNOLOGY</p>
            <p>DEPARTMENT OF COMPUTER SCIENCE</p>
            <p>END OF FIRST SEMESTER EXAMINATIONS, {{ $examYear }}</p>
            <p>PROGRAMME: {{ $programme }}</p>
        </div>
    </div>

    <div class="exam-info">
        <div class="exam-info-row">
            <span class="left">COURSE TITLE: {{ strtoupper($courseName) }}</span>
            <span class="right">COURSE CODE: {{ strtoupper($courseCode) }}</span>
        </div>
        <div class="exam-info-row">
            <span class="left">DATE: {{ strtoupper($examDate) }}</span>
            <span class="right">DURATION: {{ $duration }}</span>
        </div>
    </div>

    <div class="instructions-title">INSTRUCTIONS:</div>

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
