<!doctype html>
<html class="h-full">

<head>
    <meta charset="utf-8">
    <title>Timetable – Generate</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full bg-gradient-to-br from-slate-50 via-violet-50/30 to-slate-50 text-gray-900 antialiased">
    <main class="min-h-screen p-4 md:p-8">
        <div class="max-w-6xl mx-auto space-y-6">

            <div class="space-y-2">
                <h1 class="text-3xl md:text-4xl font-bold text-slate-900">Generate Timetable</h1>
                <p class="text-slate-600">Create a new optimized schedule using constraint programming</p>
            </div>

            @if(session('success'))
                <div
                    class="rounded-2xl border border-emerald-300 bg-emerald-50 text-emerald-900 p-4 flex items-start gap-3 shadow-sm">
                    <svg class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="flex-1">{{ session('success') }}</div>
                </div>
            @endif
            @if(session('error'))
                <div class="rounded-2xl border border-red-300 bg-red-50 text-red-900 p-4 flex items-start gap-3 shadow-sm">
                    <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="flex-1">{{ session('error') }}</div>
                </div>
            @endif

            <section class="bg-white rounded-2xl shadow-sm border border-slate-200/60 p-6">
                <div class="flex items-start gap-4 mb-5">
                    <div class="p-3 bg-gradient-to-br from-violet-50 to-indigo-50 rounded-xl">
                        <svg class="w-6 h-6 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-xl font-semibold text-slate-900">New Generation</h2>
                        <p class="text-sm text-slate-600 mt-1">Configure and run the CP-SAT solver</p>
                    </div>
                </div>

                <form id="genForm" class="space-y-4" action="{{ route('tt.generate') }}" method="POST"
                    data-status-url-template="{{ route('tt.status', ['id' => '__ID__']) }}"
                    data-show-url-template="{{ route('tt.show', ['id' => '__ID__']) }}">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">
                                Avoid Last Period Weight
                                <span class="text-slate-500 font-normal">(constraint strength)</span>
                            </label>
                            <input type="number" name="avoid_last_period_weight" min="0" value="1"
                                class="w-full px-4 py-2.5 rounded-xl border-slate-300 bg-white text-sm focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 transition-all" />
                            <p class="text-xs text-slate-500 mt-1.5">Higher values = stronger preference to avoid
                                scheduling in the last period</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-violet-600 to-indigo-600 text-white rounded-xl hover:from-violet-700 hover:to-indigo-700 transition-all shadow-lg shadow-violet-200 font-medium text-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Generate & Solve
                        </button>
                        <div class="flex items-start gap-2 text-xs text-slate-500">
                            <svg class="w-4 h-4 text-slate-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>This may take 30-60 seconds on large datasets. Keep this tab open for automatic
                                redirect.</span>
                        </div>
                    </div>
                </form>
            </section>

            <section class="bg-white rounded-2xl shadow-sm border border-slate-200/60 p-6">
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-slate-100 rounded-lg">
                            <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h2 class="text-xl font-semibold text-slate-900">Recent Runs</h2>
                    </div>
                    @php
                        $lastSolved = optional(($requests ?? collect())->where('status', 'solved')->first())->id;
                      @endphp
                    @if($lastSolved)
                        <a href="{{ route('tt.workload', $lastSolved) }}"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-400 transition-all shadow-sm text-sm font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            View Workload
                        </a>
                    @endif
                </div>

                <div class="overflow-x-auto -mx-6 px-6">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th
                                    class="text-left py-3 px-4 text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                    ID</th>
                                <th
                                    class="text-left py-3 px-4 text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="text-left py-3 px-4 text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                    Created</th>
                                <th
                                    class="text-left py-3 px-4 text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                    Error</th>
                                <th class="py-3 px-4"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($requests as $r)
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="py-3 px-4 text-sm font-semibold text-slate-900">#{{ $r->id }}</td>
                                    <td class="py-3 px-4">
                                        @if($r->status === 'solved')
                                            <span
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Solved
                                            </span>
                                        @elseif($r->status === 'failed')
                                            <span
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-red-100 text-red-700 ring-1 ring-red-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                                Failed
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-amber-100 text-amber-700 ring-1 ring-amber-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                {{ ucfirst($r->status) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-sm text-slate-600">
                                        {{ \Illuminate\Support\Carbon::parse($r->created_at)->format('M d, Y H:i') }}
                                    </td>
                                    <td class="py-3 px-4 max-w-md">
                                        @if($r->error)
                                            <span class="text-xs text-red-600 truncate block"
                                                title="{{ $r->error }}">{{ $r->error }}</span>
                                        @else
                                            <span class="text-xs text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        @if($r->status === 'solved')
                                            <a class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-400 transition-all text-sm font-medium"
                                                href="{{ route('tt.show', $r->id) }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                Open
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center">
                                        <svg class="w-12 h-12 mx-auto text-slate-300" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <p class="mt-3 text-slate-500">No generation runs yet. Create one above to get
                                            started.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>

    <div id="genOverlay" class="fixed inset-0 z-[100] hidden">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-md"></div>
        <div class="relative h-full flex items-center justify-center p-6">
            <div class="w-full max-w-lg rounded-3xl border border-slate-200/60 bg-white shadow-2xl p-8">
                <div class="flex items-center gap-4">

                    <div class="relative">
                        <svg class="animate-spin h-8 w-8 text-violet-600" viewBox="0 0 24 24">
                            <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"
                                fill="none"></circle>
                            <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4A4 4 0 008 12H4z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Generating Timetable</h3>
                        <p class="text-sm text-slate-600 mt-0.5">Please wait while we optimize your schedule</p>
                    </div>
                </div>

                <div class="mt-6">
                    <div class="h-2.5 w-full rounded-full bg-slate-100 overflow-hidden">
                        <div id="genBar"
                            class="h-2.5 w-1/6 bg-gradient-to-r from-violet-600 to-indigo-600 rounded-full transition-all duration-500">
                        </div>
                    </div>
                    <div class="mt-4 flex items-start gap-2">
                        <svg class="w-4 h-4 text-slate-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <p id="genStatus" class="text-sm font-medium text-slate-700">Submitting job to solver…</p>
                            <p id="genHint" class="text-xs text-slate-500 mt-1">You can keep this tab open; we'll
                                redirect when it's ready.</p>
                        </div>
                    </div>
                </div>

                <div id="genError" class="mt-5 hidden rounded-xl border border-red-300 bg-red-50 text-red-900 p-4">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="flex-1 text-sm"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const csrf = () => document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const overlay = document.getElementById('genOverlay');
        const genBar = document.getElementById('genBar');
        const genStatus = document.getElementById('genStatus');
        const genError = document.getElementById('genError');

        const steps = [
            { msg: 'Submitting job to solver…', w: 'w-1/6' },
            { msg: 'Packing data (groups, teachers, subjects)…', w: 'w-2/6' },
            { msg: 'Contacting FastAPI microservice…', w: 'w-3/6' },
            { msg: 'Solver running (CP-SAT)…', w: 'w-4/6' },
            { msg: 'Post-processing timetable…', w: 'w-5/6' },
            { msg: 'Finalizing…', w: 'w-full' },
        ];

        function showOverlay(stepIdx = 0) {
            overlay.classList.remove('hidden');
            const s = steps[Math.min(stepIdx, steps.length - 1)];
            genBar.className = 'h-2.5 ' + s.w + ' bg-gradient-to-r from-violet-600 to-indigo-600 rounded-full transition-all duration-500';
            genStatus.textContent = s.msg;
            genError.classList.add('hidden');
        }

        function hideOverlay() { overlay.classList.add('hidden'); }

        async function pollStatus(reqId, statusUrlTpl, showUrlTpl) {
            let i = 2;
            const statusUrl = statusUrlTpl.replace('__ID__', reqId);
            const showUrl = showUrlTpl.replace('__ID__', reqId);

            const tick = async () => {
                try {
                    const res = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) throw new Error('Status HTTP ' + res.status);
                    const data = await res.json();

                    const s = steps[Math.min(i, steps.length - 1)];
                    genBar.className = 'h-2.5 ' + s.w + ' bg-gradient-to-r from-violet-600 to-indigo-600 rounded-full transition-all duration-500';
                    genStatus.textContent = data.status === 'pending' ? s.msg : (data.status === 'solved' ? 'Completed successfully!' : 'Generation failed');

                    if (data.status === 'solved') {
                        setTimeout(() => { window.location.href = showUrl; }, 400);
                        return;
                    }
                    if (data.status === 'failed') {
                        const errorDiv = genError.querySelector('div');
                        errorDiv.textContent = data.error || 'Generation failed. Please try again.';
                        genError.classList.remove('hidden');
                        return;
                    }
                    i = (i + 1) % steps.length;
                    setTimeout(tick, 1600);
                } catch (e) {
                    const errorDiv = genError.querySelector('div');
                    errorDiv.textContent = 'Lost connection while checking status. Retrying…';
                    genError.classList.remove('hidden');
                    setTimeout(() => { genError.classList.add('hidden'); tick(); }, 1800);
                }
            };
            tick();
        }

        document.getElementById('genForm')?.addEventListener('submit', async (ev) => {
            ev.preventDefault();

            const form = ev.currentTarget;
            const action = form.getAttribute('action');
            const statusT = form.dataset.statusUrlTemplate;
            const showT = form.dataset.showUrlTemplate;

            showOverlay(0);

            try {
                setTimeout(() => showOverlay(1), 400);

                const fd = new FormData(form);
                const res = await fetch(action, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
                    body: fd
                });

                const contentType = res.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    hideOverlay();
                    window.location.href = res.url;
                    return;
                }

                const data = await res.json();
                const reqId = data.id || data.request_id;
                if (!reqId) throw new Error(data.message || 'No request id returned.');

                showOverlay(2);
                pollStatus(reqId, statusT, showT);

            } catch (e) {
                const errorDiv = genError.querySelector('div');
                errorDiv.textContent = e.message || 'Failed to start generation.';
                genError.classList.remove('hidden');
            }
        });
    </script>
</body>

</html>
