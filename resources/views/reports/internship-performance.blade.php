<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Internship Performance Report — {{ $generated_at }}</title>
    <style>
        @page { margin: 0 10mm 10mm; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 9pt; color: #1e293b; line-height: 1.4; }
        .header { background: linear-gradient(135deg, #4f46e5, #6366f1); color: #fff; padding: 10px 18px; border-radius: 6px; margin-bottom: 10px; }
        .header h1 { margin: 0; font-size: 22pt; font-weight: 800; letter-spacing: -.3px; }
        .header .sub { font-size: 9pt; opacity: .8; margin-top: 4px; }
        .meta-bar { display: flex; justify-content: space-between; font-size: 8pt; color: #64748b; margin-bottom: 16px; padding: 8px 12px; background: #f8fafc; border-radius: 6px; border: 1px solid #e2e8f0; }
        .filter-tag { display: inline-block; background: #eef2ff; color: #4f46e5; padding: 2px 10px; border-radius: 10px; font-size: 7.5pt; font-weight: 600; margin: 1px 2px; }
        .section-title { font-size: 12pt; font-weight: 700; color: #1e293b; margin: 22px 0 10px; padding-bottom: 6px; border-bottom: 3px solid #4f46e5; display: flex; align-items: center; gap: 8px; }
        .section-title .count { font-size: 8pt; font-weight: 600; color: #64748b; background: #f1f5f9; padding: 1px 10px; border-radius: 10px; }

        .card-grid { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 18px; }
        .card { flex: 1 0 110px; border-radius: 8px; padding: 12px 14px; text-align: center; }
        .card .val { font-size: 20pt; font-weight: 800; line-height: 1.1; }
        .card .lbl { font-size: 6.5pt; text-transform: uppercase; letter-spacing: .8px; margin-top: 3px; opacity: .75; }
        .card-primary { background: linear-gradient(135deg, #eef2ff, #e0e7ff); }
        .card-primary .val { color: #4338ca; }
        .card-emerald { background: linear-gradient(135deg, #ecfdf5, #d1fae5); }
        .card-emerald .val { color: #059669; }
        .card-amber { background: linear-gradient(135deg, #fffbeb, #fde68a); }
        .card-amber .val { color: #d97706; }
        .card-blue { background: linear-gradient(135deg, #eff6ff, #bfdbfe); }
        .card-blue .val { color: #2563eb; }
        .card-rose { background: linear-gradient(135deg, #fff1f2, #fecdd3); }
        .card-rose .val { color: #e11d48; }
        .card-slate { background: linear-gradient(135deg, #f1f5f9, #e2e8f0); }
        .card-slate .val { color: #475569; }
        .card-purple { background: linear-gradient(135deg, #faf5ff, #e9d5ff); }
        .card-purple .val { color: #9333ea; }
        .card-cyan { background: linear-gradient(135deg, #ecfeff, #a5f3fc); }
        .card-cyan .val { color: #0891b2; }

        table { width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 14px; font-size: 8pt; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
        th { background: #4f46e5; color: #fff; font-weight: 700; padding: 8px 10px; text-align: left; font-size: 7pt; text-transform: uppercase; letter-spacing: .5px; }
        th:not(:last-child) { border-right: 1px solid rgba(255,255,255,.15); }
        td { padding: 7px 10px; border-bottom: 1px solid #e2e8f0; background: #fff; }
        tr:last-child td { border-bottom: none; }
        tr:nth-child(even) td { background: #f8fafc; }
        tr:hover td { background: #eef2ff; }
        .num { text-align: center; font-weight: 700; font-variant-numeric: tabular-nums; }
        .num-lg { text-align: center; font-weight: 800; font-size: 10pt; }
        .bar-bg { display: inline-block; width: 50px; height: 6px; background: #e2e8f0; border-radius: 3px; vertical-align: middle; margin-right: 6px; overflow: hidden; }
        .bar-fill { height: 100%; border-radius: 3px; }
        .bar-green .bar-fill { background: #10b981; }
        .bar-blue .bar-fill { background: #6366f1; }
        .bar-amber .bar-fill { background: #f59e0b; }

        .badge { display: inline-block; padding: 2px 10px; border-radius: 10px; font-size: 7pt; font-weight: 700; }
        .badge-blue { background: #eef2ff; color: #4338ca; }
        .badge-green { background: #ecfdf5; color: #059669; }
        .badge-amber { background: #fffbeb; color: #d97706; }
        .badge-red { background: #fef2f2; color: #dc2626; }
        .badge-slate { background: #f1f5f9; color: #64748b; }
        .footer { text-align: center; font-size: 7pt; color: #94a3b8; margin-top: 20px; padding-top: 10px; border-top: 2px solid #e2e8f0; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Internship Performance Report</h1>
        <div class="sub">
            @if(!empty($filters))
                Filtered report &mdash; {{ $report['summary']['total_assignments'] ?? 0 }} assignments
            @else
                Comprehensive overview of student internship assignments &amp; status distribution
            @endif
        </div>
    </div>

    <div class="meta-bar">
        <h1>Internship Performance Report</h1>
        <span><strong>Generated:</strong> {{ $generated_at }}</span>
        @if(!empty($filters))
            <span>
                @foreach($filters as $label => $value)
                    <span class="filter-tag">{{ $label }}: {{ $value }}</span>
                @endforeach
            </span>
        @endif
        <span><strong>Records:</strong> {{ $report['summary']['total_assignments'] ?? 0 }} assignments</span>
    </div>

    <div class="section-title">Summary Metrics</div>

    <table style="margin-top:-2px;margin-bottom:18px;">
        <thead>
            <tr>
                <th colspan="4" style="text-align:center;background:#4338ca;font-size:8pt;letter-spacing:1px;">KEY PERFORMANCE INDICATORS</th>
            </tr>
        </thead>
        <tbody>
            @php
                $colors = ['#4f46e5','#059669','#d97706','#2563eb','#e11d48','#475569','#9333ea','#0891b2'];
                $idx = 0;
            @endphp
            <tr>
                @foreach($report['summary'] as $key => $value)
                    <td style="width:25%;padding:10px 12px;border-bottom:1px solid #e2e8f0;border-left:3px solid {{ $colors[$idx % count($colors)] }};">
                        <div style="font-size:6pt;color:#64748b;text-transform:uppercase;letter-spacing:.6px;margin-bottom:2px;">{{ ucwords(str_replace('_', ' ', $key)) }}</div>
                        <div style="font-size:16pt;font-weight:800;color:#1e293b;line-height:1.1;">{{ $value }}</div>
                    </td>
                    @php
                        $idx++;
                        if ($idx % 4 === 0 && $idx < count($report['summary'])) echo '</tr><tr>';
                    @endphp
                @endforeach
                @php
                    $remainder = count($report['summary']) % 4;
                    if ($remainder > 0) {
                        for ($pad = 0; $pad < 4 - $remainder; $pad++) {
                            echo '<td style="width:25%;padding:10px 12px;border-bottom:1px solid #e2e8f0;"></td>';
                        }
                    }
                @endphp
            </tr>
        </tbody>
    </table>

    <div class="section-title">
        Students per Batch
        <span class="count">{{ count($report['students_per_batch']) }}</span>
    </div>
    <table>
        <thead>
            <tr>
                <th style="width:30%;">Batch</th>
                <th style="width:12%;">Year</th>
                <th style="width:16%;" class="num">Total</th>
                <th style="width:16%;" class="num">Active</th>
                <th style="width:16%;" class="num">Completed</th>
                <th style="width:10%;">Rate</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['students_per_batch'] as $batch)
                @php $rate = $batch['student_count'] > 0 ? round(($batch['completed_count'] / $batch['student_count']) * 100) : 0; @endphp
                <tr>
                    <td><strong>{{ $batch['batch'] }}</strong></td>
                    <td>{{ $batch['year'] ?? '—' }}</td>
                    <td class="num-lg">{{ $batch['student_count'] }}</td>
                    <td class="num">{{ $batch['active_count'] }}</td>
                    <td class="num">{{ $batch['completed_count'] }}</td>
                    <td>
                        <span class="bar-bg bar-{{ $rate >= 70 ? 'green' : ($rate >= 40 ? 'blue' : 'amber') }}">
                            <span class="bar-fill" style="width:{{ $rate }}%;"></span>
                        </span>
                        <span style="font-weight:700;font-size:7.5pt;">{{ $rate }}%</span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;padding:16px;color:#94a3b8;">No batch data available.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">
        Students per Company
        <span class="count">{{ count($report['students_per_company']) }}</span>
    </div>
    <table>
        <thead>
            <tr>
                <th style="width:26%;">Company</th>
                <th style="width:18%;">Industry</th>
                <th style="width:14%;" class="num">Total</th>
                <th style="width:14%;" class="num">Active</th>
                <th style="width:14%;" class="num">Completed</th>
                <th style="width:14%;" class="num">Terminated</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['students_per_company'] as $company)
                <tr>
                    <td><strong>{{ $company['company'] }}</strong></td>
                    <td style="color:#64748b;">{{ $company['industry'] ?? '—' }}</td>
                    <td class="num-lg">{{ $company['assigned_count'] }}</td>
                    <td class="num">{{ $company['active_count'] }}</td>
                    <td class="num">{{ $company['completed_count'] }}</td>
                    <td class="num">{{ $company['terminated_count'] }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;padding:16px;color:#94a3b8;">No company data available.</td></tr>
            @endforelse
        </tbody>
    </table>

    @php
        $chunks = array_chunk($report['assignments'], 10);
        $pageNum = 1;
    @endphp

    @foreach($chunks as $chunk)
        @if(!$loop->first)
            <div class="page-break"></div>
        @endif

        <div class="section-title">
            Assignments
            <span class="count">{{ count($report['assignments']) }} total &mdash; Page {{ $pageNum }} of {{ count($chunks) }}</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Code</th>
                    <th>Batch</th>
                    <th>Company</th>
                    <th>Tutor</th>
                    <th>Position</th>
                    <th>Status</th>
                    <th>Start</th>
                    <th>End</th>
                    <th style="width:8%;">Dur.</th>
                </tr>
            </thead>
            <tbody>
                @foreach($chunk as $a)
                    <tr>
                        <td><strong>{{ $a['student'] }}</strong></td>
                        <td style="font-family:monospace;font-size:7.5pt;">{{ $a['student_code'] }}</td>
                        <td>{{ $a['batch'] }}</td>
                        <td>{{ $a['company'] }}</td>
                        <td>{{ $a['tutor'] }}</td>
                        <td style="color:#64748b;">{{ $a['position'] }}</td>
                        <td>
                            <span class="badge
                                @switch($a['status'])
                                    @case('Assigned') badge-blue @break
                                    @case('In Progress') badge-amber @break
                                    @case('Completed') badge-green @break
                                    @case('Terminated') badge-red @break
                                    @default badge-slate
                                @endswitch
                            ">{{ $a['status'] }}</span>
                        </td>
                        <td style="font-size:7.5pt;">{{ $a['start_date'] }}</td>
                        <td style="font-size:7.5pt;">{{ $a['end_date'] }}</td>
                        <td class="num" style="font-size:7.5pt;">{{ $a['duration_days'] }}d</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @php $pageNum++; @endphp
    @endforeach

    @if(count($report['assignments']) === 0)
        <div class="section-title">Assignments <span class="count">0</span></div>
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Code</th>
                    <th>Batch</th>
                    <th>Company</th>
                    <th>Tutor</th>
                    <th>Position</th>
                    <th>Status</th>
                    <th>Start</th>
                    <th>End</th>
                    <th style="width:8%;">Dur.</th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="10" style="text-align:center;padding:16px;color:#94a3b8;">No assignments found.</td></tr>
            </tbody>
        </table>
    @endif

    <div class="footer">Internship Follow-up System &mdash; Generated {{ $generated_at }}</div>

</body>
</html>
