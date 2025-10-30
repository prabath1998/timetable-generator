<!doctype html>
<html class="h-full">
<head>
  <meta charset="utf-8">
  <title>Timetable – Generate</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="h-full bg-gray-50 text-gray-900 antialiased">
  <main class="max-w-6xl mx-auto p-6">
    <h1 class="text-2xl font-semibold mb-4">Timetable – Generate</h1>

    @if(session('success'))
      <div class="mb-4 rounded-lg border border-emerald-300 bg-emerald-50 text-emerald-900 p-3">
        {{ session('success') }}
      </div>
    @endif
    @if(session('error'))
      <div class="mb-4 rounded-lg border border-red-300 bg-red-50 text-red-900 p-3">
        {{ session('error') }}
      </div>
    @endif

    <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
      <h2 class="text-lg font-medium mb-3">Run a new generation</h2>
      <form id="genForm" class="flex flex-wrap items-end gap-4"
            action="{{ route('tt.generate') }}" method="POST"
            data-status-url-template="{{ route('tt.status', ['id' => '__ID__']) }}"
            data-show-url-template="{{ route('tt.show', ['id' => '__ID__']) }}">
        @csrf
        <label class="text-sm">
          <span class="block text-gray-700 mb-1">Avoid last period (weight)</span>
          <input type="number" name="avoid_last_period_weight" min="0" value="1"
                 class="block w-36 rounded-lg border-gray-300 focus:border-gray-900 focus:ring-gray-900" />
        </label>
        <button type="submit"
          class="inline-flex items-center gap-2 rounded-lg border border-gray-900 bg-gray-900 text-white px-4 py-2 hover:bg-black">
          Generate & Solve
        </button>
      </form>
      <p class="text-sm text-gray-600 mt-2">
        Tip: This may take a minute on large datasets. You can leave this tab open; we’ll redirect when it’s ready.
      </p>
    </section>

    <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-lg font-medium">Recent runs</h2>
        @php
          $lastSolved = optional(($requests ?? collect())->where('status','solved')->first())->id;
        @endphp
        @if($lastSolved)
          <a href="{{ route('tt.workload',$lastSolved) }}"
             class="text-sm inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 border-gray-300 hover:border-gray-400">
            View teacher workload
          </a>
        @endif
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="bg-gray-100 text-gray-700">
              <th class="text-left py-2 px-3">ID</th>
              <th class="text-left py-2 px-3">Status</th>
              <th class="text-left py-2 px-3">Created</th>
              <th class="text-left py-2 px-3">Error</th>
              <th class="py-2 px-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            @forelse($requests as $r)
              <tr class="bg-white">
                <td class="py-2 px-3">#{{ $r->id }}</td>
                <td class="py-2 px-3">
                  @if($r->status==='solved')
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs border border-emerald-300 bg-emerald-50 text-emerald-800">solved</span>
                  @elseif($r->status==='failed')
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs border border-red-300 bg-red-50 text-red-800">failed</span>
                  @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs border border-amber-300 bg-amber-50 text-amber-800">{{ $r->status }}</span>
                  @endif
                </td>
                <td class="py-2 px-3">{{ \Illuminate\Support\Carbon::parse($r->created_at)->format('Y-m-d H:i') }}</td>
                <td class="py-2 px-3 max-w-[28rem] truncate" title="{{ $r->error }}">{{ $r->error }}</td>
                <td class="py-2 px-3 text-right">
                  @if($r->status==='solved')
                    <a class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 border-gray-300 hover:border-gray-400"
                       href="{{ route('tt.show',$r->id) }}">Open</a>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="5" class="py-6 text-gray-500">No runs yet. Generate one above.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>
  </main>

  <!-- Fullscreen loader overlay -->
  <div id="genOverlay" class="fixed inset-0 z-[100] hidden">
    <div class="absolute inset-0 bg-white/70 backdrop-blur-sm"></div>
    <div class="relative h-full flex items-center justify-center p-6">
      <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-xl p-6">
        <div class="flex items-center gap-3">
          <!-- spinner -->
          <svg class="animate-spin h-6 w-6 text-gray-900" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
            <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4A4 4 0 008 12H4z"></path>
          </svg>
          <h3 class="text-base font-semibold">Generating timetable…</h3>
        </div>

        <!-- progress line -->
        <div class="mt-5">
          <div class="h-2 w-full rounded-full bg-gray-100 overflow-hidden">
            <div id="genBar" class="h-2 w-1/6 bg-gray-900 rounded-full transition-all"></div>
          </div>
          <p id="genStatus" class="mt-3 text-sm text-gray-600">Submitting job to solver…</p>
          <p id="genHint" class="mt-1 text-xs text-gray-500">You can keep this tab open; we’ll redirect when it’s ready.</p>
        </div>

        <!-- small log area -->
        <div id="genLog" class="mt-4 text-xs text-gray-500 space-y-1 max-h-36 overflow-auto hidden"></div>

        <!-- error -->
        <div id="genError" class="mt-4 hidden rounded-lg border border-red-300 bg-red-50 text-red-900 p-3 text-sm"></div>
      </div>
    </div>
  </div>

  <script>
    const csrf = () => document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const overlay   = document.getElementById('genOverlay');
    const genBar    = document.getElementById('genBar');
    const genStatus = document.getElementById('genStatus');
    const genError  = document.getElementById('genError');
    const genLog    = document.getElementById('genLog');

    const steps = [
      { msg: 'Submitting job to solver…', w: 'w-1/6'  },
      { msg: 'Packing data (groups, teachers, subjects)…', w: 'w-2/6' },
      { msg: 'Contacting FastAPI microservice…', w: 'w-3/6' },
      { msg: 'Solver running (CP-SAT)…', w: 'w-4/6' },
      { msg: 'Post-processing timetable…', w: 'w-5/6' },
      { msg: 'Finalizing…', w: 'w-full' },
    ];

    function showOverlay(stepIdx=0){
      overlay.classList.remove('hidden');
      const s = steps[Math.min(stepIdx, steps.length-1)];
      genBar.className = 'h-2 ' + s.w + ' bg-gray-900 rounded-full transition-all';
      genStatus.textContent = s.msg;
      genError.classList.add('hidden');
    }

    function hideOverlay(){ overlay.classList.add('hidden'); }

    // Poll the status endpoint until solved/failed
    async function pollStatus(reqId, statusUrlTpl, showUrlTpl){
      let i = 2; // we already set first two steps
      const statusUrl = statusUrlTpl.replace('__ID__', reqId);
      const showUrl   = showUrlTpl.replace('__ID__', reqId);

      const tick = async () => {
        try{
          const res = await fetch(statusUrl, { headers: { 'Accept':'application/json' }});
          if (!res.ok) throw new Error('Status HTTP '+res.status);
          const data = await res.json(); // {status: 'pending'|'solved'|'failed', error?:string}
          // animate bar & message
          const s = steps[Math.min(i, steps.length-1)];
          genBar.className = 'h-2 ' + s.w + ' bg-gray-900 rounded-full transition-all';
          genStatus.textContent = data.status === 'pending' ? s.msg : (data.status === 'solved' ? 'Completed!' : 'Failed');

          if (data.status === 'solved') {
            setTimeout(()=>{ window.location.href = showUrl; }, 350);
            return;
          }
          if (data.status === 'failed') {
            genError.textContent = data.error || 'Generation failed.';
            genError.classList.remove('hidden');
            return;
          }
          i = (i+1) % steps.length;
          setTimeout(tick, 1600);
        } catch (e){
          genError.textContent = 'Lost connection while checking status. Retrying…';
          genError.classList.remove('hidden');
          setTimeout(()=>{ genError.classList.add('hidden'); tick(); }, 1800);
        }
      };
      tick();
    }

    // Intercept the Generate form to run async + loader
    document.getElementById('genForm')?.addEventListener('submit', async (ev)=>{
      ev.preventDefault();

      const form     = ev.currentTarget;
      const action   = form.getAttribute('action');
      const statusT  = form.dataset.statusUrlTemplate;
      const showT    = form.dataset.showUrlTemplate;

      showOverlay(0);

      try{
        // step 1 visual
        setTimeout(()=>showOverlay(1), 400);

        // submit via fetch (expects JSON: { ok:true, id, message? } OR { id } )
        const fd = new FormData(form);
        const res = await fetch(action, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrf(), 'Accept':'application/json' },
          body: fd
        });

        // if backend redirects (HTML), just keep loader until the new page loads:
        const contentType = res.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
          // Not JSON: likely redirecting to a waiting page. Show loader and let browser follow.
          hideOverlay(); // prevent double overlay if new page also has one
          window.location.href = res.url;
          return;
        }

        const data = await res.json();
        const reqId = data.id || data.request_id;
        if (!reqId) throw new Error(data.message || 'No request id returned.');

        // start polling
        showOverlay(2);
        pollStatus(reqId, statusT, showT);

      } catch (e){
        genError.textContent = e.message || 'Failed to start generation.';
        genError.classList.remove('hidden');
      }
    });
  </script>
</body>
</html>
