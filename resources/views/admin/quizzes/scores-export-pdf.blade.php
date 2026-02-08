{{-- Compact PDF for score report. Inline styles only (DomPDF). --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Score Report – {{ $quiz->title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #1f2937; margin: 12px 16px; line-height: 1.3; }
        .header { display: table; width: 100%; margin-bottom: 10px; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px; }
        .header-logo { display: table-cell; width: 60px; vertical-align: middle; }
        .header-logo img { max-height: 48px; max-width: 56px; }
        .header-text { display: table-cell; vertical-align: middle; padding-left: 10px; }
        .institution { font-size: 11pt; font-weight: bold; color: #111827; margin: 0 0 2px 0; }
        .meta { margin: 8px 0 10px 0; font-size: 8pt; color: #4b5563; }
        .meta-row { margin: 2px 0; }
        .meta-label { font-weight: bold; color: #374151; }
        table.scores { width: 100%; border-collapse: collapse; margin-top: 6px; font-size: 8pt; }
        table.scores th, table.scores td { border: 1px solid #d1d5db; padding: 4px 6px; text-align: left; }
        table.scores th { background: #f3f4f6; font-weight: 600; color: #374151; }
        table.scores tr:nth-child(even) { background: #f9fafb; }
        .num { text-align: right; }
        .footer { margin-top: 12px; font-size: 7pt; color: #6b7280; }
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
            <p class="meta"><span class="meta-label">Score report</span> — {{ $quiz->title }}</p>
        </div>
    </div>

    <div class="meta">
        <div class="meta-row"><span class="meta-label">Lecturer:</span> {{ $lecturerName }}</div>
        <div class="meta-row"><span class="meta-label">Course:</span> {{ $courseName }}</div>
        <div class="meta-row"><span class="meta-label">Exam:</span> {{ $examTypeLabel }}</div>
        <div class="meta-row"><span class="meta-label">Date:</span> {{ $reportDate }}</div>
        <div class="meta-row"><span class="meta-label">Number of students:</span> {{ $sessions->count() }}</div>
    </div>

    <table class="scores">
        <thead>
            <tr>
                <th style="width:28px">No.</th>
                <th>Student Index</th>
                <th class="num" style="width:56px">Score %</th>
                <th class="num" style="width:50px">Correct</th>
                <th class="num" style="width:44px">Total</th>
                <th class="num" style="width:52px">Violations</th>
                <th style="width:90px">Submitted</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sessions as $idx => $session)
            <tr>
                <td class="num">{{ $idx + 1 }}</td>
                <td>{{ $session->student_index }}</td>
                <td class="num">{{ $session->result ? $session->result->score . '%' : '—' }}</td>
                <td class="num">{{ $session->result ? $session->result->correct_count : '—' }}</td>
                <td class="num">{{ $session->result ? $session->result->total_questions : '—' }}</td>
                <td class="num">{{ $session->result ? $session->result->violations_count : $session->violations->count() }}</td>
                <td>{{ $session->result && $session->result->submitted_at ? $session->result->submitted_at->format('M d, Y H:i') : '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generated {{ now()->format('M d, Y H:i') }} — QuizSnap
    </div>
</body>
</html>
