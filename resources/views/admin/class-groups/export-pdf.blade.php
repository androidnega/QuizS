<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class List - {{ $classGroup->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Times New Roman', serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #000;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .header p {
            font-size: 12pt;
            margin-bottom: 5px;
        }
        .meta {
            margin-bottom: 20px;
            font-size: 11pt;
        }
        .meta-row {
            margin-bottom: 5px;
        }
        .meta-label {
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        thead {
            background-color: #f0f0f0;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            font-weight: bold;
            font-size: 11pt;
        }
        td {
            font-size: 10pt;
        }
        .num {
            text-align: center;
            width: 50px;
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
        <h1>CLASS LIST</h1>
        <p><strong>{{ $classGroup->name }}</strong></p>
    </div>

    <div class="meta">
        <div class="meta-row"><span class="meta-label">Examiner:</span> {{ $examinerName }}</div>
        <div class="meta-row"><span class="meta-label">Total Students:</span> {{ $students->count() }}</div>
        <div class="meta-row"><span class="meta-label">Date:</span> {{ now()->format('F j, Y') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="num">No.</th>
                <th>Index Number</th>
                <th>Student Name</th>
                <th>Phone Number</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $idx => $student)
                @php
                    $phone = $student->studentAccount?->phone_contact ?? null;
                    $displayName = $student->studentAccount?->student_name ?? $student->student_name ?? '—';
                @endphp
                <tr>
                    <td class="num">{{ $idx + 1 }}</td>
                    <td>{{ $student->index_number }}</td>
                    <td>{{ $displayName }}</td>
                    <td>{{ $phone ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generated {{ now()->format('M d, Y H:i') }} — QuizSnap
    </div>
</body>
</html>
