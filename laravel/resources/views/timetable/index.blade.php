<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Timetable – Generate</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial; margin:24px; color:#111}
    .card{border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin-bottom:16px}
    .btn{display:inline-block; padding:10px 14px; border-radius:10px; border:1px solid #111; background:#111; color:#fff; text-decoration:none}
    .btn.secondary{background:#fff; color:#111}
    table{width:100%; border-collapse:collapse}
    th,td{padding:10px 8px; border-bottom:1px solid #eee; text-align:left}
    .badge{padding:3px 8px; border-radius:999px; font-size:12px; border:1px solid #e5e7eb}
    .ok{color:#065f46; border-color:#a7f3d0; background:#ecfdf5}
    .fail{color:#991b1b; border-color:#fecaca; background:#fef2f2}
    .pending{color:#92400e; border-color:#fde68a; background:#fffbeb}
    .flash{padding:10px 12px; border-radius:8px; margin-bottom:12px}
    .flash.ok{background:#ecfdf5; border:1px solid #a7f3d0}
    .flash.err{background:#fef2f2; border:1px solid #fecaca}
    input[type="number"]{padding:8px 10px; border:1px solid #d1d5db; border-radius:8px; width:120px}
  </style>
</head>
<body>
  <h1 style="margin-bottom:16px;">Timetable – Generate</h1>

  @if(session('success')) <div class="flash ok">{{ session('success') }}</div> @endif
  @if(session('error')) <div class="flash err">{{ session('error') }}</div> @endif

  <div class="card">
    <h2 style="margin-top:0;">Run a new generation</h2>
    <form method="POST" action="{{ route('tt.generate') }}">
      @csrf
      <div style="display:flex; gap:16px; align-items:center; flex-wrap:wrap;">
        <label>
          Avoid last period (weight)
          <br>
          <input type="number" name="avoid_last_period_weight" min="0" value="1" />
        </label>
        <button class="btn" type="submit">Generate & Solve</button>
      </div>
      <p style="margin-top:8px; color:#6b7280; font-size:14px;">
        Infeasible? Check required weekly slots vs available periods and teacher overlaps.
      </p>
    </form>
  </div>

  <div class="card">
    <h2 style="margin-top:0;">Recent runs</h2>
    <table>
      <thead><tr><th>ID</th><th>Status</th><th>Created</th><th>Error</th><th></th></tr></thead>
      <tbody>
        @forelse($requests as $r)
          <tr>
            <td>#{{ $r->id }}</td>
            <td>
              @if($r->status==='solved') <span class="badge ok">solved</span>
              @elseif($r->status==='failed') <span class="badge fail">failed</span>
              @else <span class="badge pending">{{ $r->status }}</span>
              @endif
            </td>
            <td>{{ \Illuminate\Support\Carbon::parse($r->created_at)->format('Y-m-d H:i') }}</td>
            <td style="max-width:420px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="{{ $r->error }}">{{ $r->error }}</td>
            <td>
              @if($r->status==='solved')
                <a class="btn secondary" href="{{ route('tt.show',$r->id) }}">Open</a>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="5" style="color:#6b7280;">No runs yet. Generate one above.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</body>
</html>
