<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Questions - {{ $quiz->title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Times New Roman', serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #000;
            padding: 20px;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .header-logo {
            display: table-cell;
            width: 80px;
            vertical-align: middle;
            padding-right: 15px;
        }
        .header-logo img {
            max-height: 70px;
            max-width: 80px;
        }
        .header-text {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }
        .header-text p {
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
            font-size: 12pt;
        }
        .exam-info {
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .exam-info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .exam-info-row .left {
            text-align: left;
        }
        .exam-info-row .right {
            text-align: right;
        }
        .exam-info-row.centered {
            justify-content: center;
            text-align: center;
        }
        .instructions {
            margin-top: 20px;
            margin-bottom: 20px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .questions {
            margin-top: 30px;
        }
        .question {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        .question-number {
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 8px;
        }
        .question-text {
            margin-bottom: 10px;
            font-size: 11pt;
        }
        .question-options {
            margin-left: 20px;
            margin-bottom: 10px;
        }
        .question-option {
            margin-bottom: 5px;
            font-size: 11pt;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9pt;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        @if(!empty($institutionLogoPath))
        <div class="header-logo">
            <img src="{{ $institutionLogoPath }}" alt="Institution Logo">
        </div>
        @endif
        <div class="header-text">
            <p>{{ $institutionName }}</p>
            <p>FACULTY OF APPLIED ARTS AND TECHNOLOGY</p>
            <p>DEPARTMENT OF COMPUTER SCIENCE</p>
            <p>END OF FIRST SEMESTER EXAMINATIONS, {{ now()->format('Y') }}/{{ now()->addYear()->format('y') }}</p>
        </div>
    </div>

    <div class="exam-info">
        <div class="exam-info-row centered">
            <p>PROGRAMME: {{ $programme }}</p>
        </div>
        <div class="exam-info-row">
            <span class="left">COURSE TITLE: {{ strtoupper($courseName) }}</span>
            <span class="right">COURSE CODE: {{ strtoupper($courseCode) }}</span>
        </div>
        <div class="exam-info-row">
            <span class="left">DATE: {{ strtoupper($examDate) }}</span>
            <span class="right">DURATION: {{ $duration }}</span>
        </div>
    </div>

    <div class="instructions">
        <p>INSTRUCTIONS:</p>
    </div>

    <div class="questions">
        @foreach($questions as $idx => $question)
            <div class="question">
                <div class="question-number">{{ $idx + 1 }}. </div>
                <div class="question-text">{{ $question->text }}</div>
                @if($question->options && is_array($question->options))
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
