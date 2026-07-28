<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Users List — {{ $generated_at }}</title>
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
        .badge-slate { background: #f1f5f9; color: #64748b; }
        .footer { text-align: center; font-size: 7pt; color: #94a3b8; margin-top: 18px; padding-top: 6px; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Users List</h1>
        <div class="sub">{{ $users->count() }} users</div>
    </div>

    <div class="meta-bar">
        <span>Generated: {{ $generated_at }}</span>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:18%;">First Name</th>
                <th style="width:18%;">Last Name</th>
                <th style="width:26%;">Email</th>
                <th style="width:14%;">Role</th>
                <th style="width:12%;">Status</th>
                <th style="width:12%;">Created At</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr>
                    <td><strong>{{ $user->first_name }}</strong></td>
                    <td>{{ $user->last_name }}</td>
                    <td>{{ $user->email ?? '—' }}</td>
                    <td>{{ $user->role?->name ? ucfirst($user->role->name) : '—' }}</td>
                    <td>
                        <span class="badge {{ $user->trashed() ? 'badge-slate' : 'badge-green' }}">
                            {{ $user->trashed() ? 'Deactivated' : 'Active' }}
                        </span>
                    </td>
                    <td>{{ $user->created_at?->format('Y-m-d') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;padding:20px;color:#94a3b8;">No users found.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Internship Follow-up System &mdash; Users List &mdash; {{ $generated_at }}</div>
</body>
</html>
