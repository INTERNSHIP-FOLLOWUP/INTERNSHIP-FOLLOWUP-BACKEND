<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $batch->batch_name }} — Student List — {{ $generated_at }}</title>
    <style>
        @page { margin: 8mm 10mm 10mm; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 9pt; color: #1e293b; line-height: 1.5; }
        .header { background: #4f46e5; color: #fff; padding: 12px 18px; border-radius: 6px; margin-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18pt; font-weight: 700; }
        .header .sub { font-size: 9pt; opacity: .85; margin-top: 3px; }
        .meta-bar { display: flex; justify-content: space-between; font-size: 8pt; color: #64748b; margin-bottom: 16px; padding-bottom: 6px; border-bottom: 2px solid #e2e8f0; }
        table { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
        th { background: #4f46e5; color: #fff; font-weight: 600; padding: 7px 10px; text-align: left; font-size: 7.5pt; text-transform: uppercase; letter-spacing: .3px; }
        td { padding: 6px 10px; border-bottom: 1px solid #e2e8f0; }
        tr:nth-child(even) td { background: #f8fafc; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 10px; font-size: 7.5pt; font-weight: 700; }
        .badge-green { background: #ecfdf5; color: #059669; }
        .badge-blue { background: #eef2ff; color: #4338ca; }
        .badge-amber { background: #fffbeb; color: #d97706; }
        .badge-red { background: #fef2f2; color: #dc2626; }
        .badge-slate { background: #f1f5f9; color: #64748b; }
        .footer { text-align: center; font-size: 7pt; color: #94a3b8; margin-top: 18px; padding-top: 6px; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $batch->batch_name }}</h1>
        <div class="sub">Year {{ $batch->year }} &middot; {{ $students->count() }} enrolled students</div>
    </div>

    <div class="meta-bar">
        <span>Generated: {{ $generated_at }}</span>
        <span>Status breakdown available on dashboard</span>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:28%;">Name</th>
                <th style="width:18%;">Code</th>
                <th style="width:12%;">Gender</th>
                <th style="width:28%;">Email</th>
                <th style="width:14%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $student)
                <tr>
                    <td><strong>{{ $student->name }}</strong></td>
                    <td style="font-family:monospace;">{{ $student->student_code ?? '—' }}</td>
                    <td>{{ $student->gender ?? '—' }}</td>
                    <td>{{ $student->email ?? '—' }}</td>
                    <td>
                        <span class="badge
                            @switch($student->status)
                                @case('active') badge-green @break
                                @case('graduated') badge-blue @break
                                @case('inactive') badge-amber @break
                                @case('suspended') badge-red @break
                                @default badge-slate
                            @endswitch
                        ">{{ $student->status ?? 'N/A' }}</span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center;padding:20px;color:#94a3b8;">No students enrolled in this batch.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Internship Follow-up System &mdash; {{ $batch->batch_name }} &mdash; {{ $generated_at }}</div>
</body>
</html>
