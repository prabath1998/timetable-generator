<!doctype html>
<html class="h-full">

<head>
    <meta charset="utf-8" />
    <title>Workload – Timetable #{{ $id }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>

<body class="h-full bg-gradient-to-br from-slate-50 via-blue-50/30 to-slate-50 text-gray-900 antialiased">
    @php
        $rows = collect($barCategories ?? [])->zip($barData ?? [])->map(function ($pair) {
            [$name, $count] = $pair;
            return ['name' => (string) $name, 'periods' => (int) $count];
        });

        $weeklySlots = $weeklySlots ?? 40;
        $totalTeachers = $rows->count();
        $totalAssigned = (int) $rows->sum('periods');
        $totalCapacity = max($totalTeachers * $weeklySlots, 1);
        $utilPercent = round($totalAssigned / $totalCapacity * 100, 1);
        $avgLoad = $totalTeachers ? round($totalAssigned / $totalTeachers, 1) : 0;
    @endphp

    <main class="min-h-screen p-4 md:p-8">
        <div class="max-w-7xl mx-auto space-y-6">

            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <a href="{{ route('tt.show', $id) }}"
                        class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Back to Timetable
                    </a>
                </div>

                <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6">
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold text-slate-900">Teacher Workload</h1>
                        <p class="text-slate-600 mt-2">Timetable #{{ $id }} · {{ $totalTeachers }} teachers ·
                            {{ $weeklySlots }} periods/week capacity</p>
                    </div>

                    <button id="btnExport"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-300 text-slate-700 rounded-xl hover:bg-slate-50 hover:border-slate-400 transition-all shadow-sm font-medium text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export CSV
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/60">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-600">Overall Utilization</p>
                            <p class="text-3xl font-bold text-slate-900 mt-2">{{ $utilPercent }}%</p>
                            <p class="text-xs text-slate-500 mt-1">{{ $totalAssigned }} / {{ $totalCapacity }} total
                                periods</p>
                        </div>
                        <div class="p-3 bg-blue-50 rounded-xl">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/60">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-600">Average Load</p>
                            <p class="text-3xl font-bold text-slate-900 mt-2">{{ $avgLoad }}</p>
                            <p class="text-xs text-slate-500 mt-1">out of {{ $weeklySlots }} periods per teacher</p>
                        </div>
                        <div class="p-3 bg-emerald-50 rounded-xl">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/60">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-600">Total Teachers</p>
                            <p class="text-3xl font-bold text-slate-900 mt-2">{{ $totalTeachers }}</p>
                            <p class="text-xs text-slate-500 mt-1">in this timetable</p>
                        </div>
                        <div class="p-3 bg-purple-50 rounded-xl">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/60">
                <h3 class="text-base font-semibold text-slate-900 mb-4">Filters & Options</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Search Teacher</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input id="q" type="text" placeholder="Type a name…"
                                class="w-full pl-10 pr-4 py-2.5 rounded-xl border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all text-sm" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Sort By</label>
                        <select id="sortBy"
                            class="w-full px-4 py-2.5 rounded-xl border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all text-sm">
                            <option value="load_desc" selected>Load: High → Low</option>
                            <option value="load_asc">Load: Low → High</option>
                            <option value="name_asc">Name: A → Z</option>
                            <option value="name_desc">Name: Z → A</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Display Limit</label>
                        <input id="topN" type="number" min="5" max="{{ max(5, $totalTeachers) }}" value="20"
                            class="w-full px-4 py-2.5 rounded-xl border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all text-sm" />
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/60">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-slate-900">Workload Distribution</h3>
                        <span class="text-xs font-medium text-slate-500 bg-slate-100 px-3 py-1 rounded-full">Periods /
                            {{ $weeklySlots }}</span>
                    </div>
                    <div id="chartBars"></div>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/60">
                    <h3 class="text-lg font-semibold text-slate-900 mb-4">Detailed Breakdown</h3>
                    <div class="overflow-x-auto -mx-6 px-6">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-slate-200">
                                    <th
                                        class="text-left py-3 px-3 text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                        #</th>
                                    <th
                                        class="text-left py-3 px-3 text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                        Teacher</th>
                                    <th
                                        class="text-left py-3 px-3 text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                        Assigned</th>
                                    <th
                                        class="text-left py-3 px-3 text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                        Capacity</th>
                                    <th
                                        class="text-left py-3 px-3 text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                        Rate</th>
                                </tr>
                            </thead>
                            <tbody id="tbody" class="divide-y divide-slate-100"></tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <script>

        const WEEKLY = {{ (int) $weeklySlots }};
        const RAW = @json($rows->values()->all());

        function pct(v) { return (Math.round(v * 10) / 10).toFixed(1) + '%'; }
        function sortData(kind, arr) {
            const a = [...arr];
            switch (kind) {
                case 'name_asc': a.sort((x, y) => x.name.localeCompare(y.name)); break;
                case 'name_desc': a.sort((x, y) => y.name.localeCompare(x.name)); break;
                case 'load_asc': a.sort((x, y) => (x.periods || 0) - (y.periods || 0)); break;
                case 'load_desc': a.sort((x, y) => (y.periods || 0) - (x.periods || 0)); break;
            }
            return a;
        }

        const q = document.getElementById('q');
        const sortBy = document.getElementById('sortBy');
        const topN = document.getElementById('topN');

        function renderTable(list) {
            const body = document.getElementById('tbody');
            body.innerHTML = list.map((r, i) => {
                const util = (r.periods || 0) / WEEKLY * 100;
                const utilColor = util >= 90 ? 'text-emerald-600 bg-emerald-50' : util >= 70 ? 'text-blue-600 bg-blue-50' : 'text-slate-600 bg-slate-50';
                return `
      <tr class="hover:bg-slate-50/50 transition-colors">
        <td class="py-3 px-3 text-sm text-slate-500">${i + 1}</td>
        <td class="py-3 px-3 text-sm font-medium text-slate-900">${r.name}</td>
        <td class="py-3 px-3 text-sm font-semibold text-slate-900">${r.periods}</td>
        <td class="py-3 px-3 text-sm text-slate-600">${WEEKLY}</td>
        <td class="py-3 px-3">
          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${utilColor}">
            ${pct(util)}
          </span>
        </td>
      </tr>
    `}).join('');
        }

        let bars;
        function renderBars(list) {
            const names = list.map(x => x.name);
            const loads = list.map(x => x.periods);

            const opts = {
                series: [{ name: 'Periods', data: loads }],
                chart: { type: 'bar', height: Math.max(320, 20 + list.length * 26), toolbar: { show: false }, fontFamily: 'inherit' },
                plotOptions: { bar: { horizontal: true, borderRadius: 0, barHeight: '80%' } },
                dataLabels: { enabled: false },
                xaxis: { categories: names, max: WEEKLY, tickAmount: 8, labels: { style: { colors: '#64748b', fontSize: '12px' } } },
                yaxis: { labels: { style: { colors: '#64748b', fontSize: '12px' } } },
                grid: { borderColor: '#e2e8f0', strokeDashArray: 3 },
                colors: ['#3b82f6'],
                tooltip: { y: { formatter: (v) => `${v} / ${WEEKLY}` } }
            };
            if (bars) bars.destroy();
            bars = new ApexCharts(document.querySelector("#chartBars"), opts);
            bars.render();
        }

        function currentList() {
            const query = (q.value || '').toLowerCase();
            let data = RAW.filter(r => r.name.toLowerCase().includes(query));
            data = sortData(sortBy.value, data);
            const n = Math.max(5, Number(topN.value || 20));
            return data.slice(0, n);
        }
        function refresh() {
            const list = currentList();
            renderBars(list);
            renderTable(list);
        }

        q.addEventListener('input', refresh);
        sortBy.addEventListener('change', refresh);
        topN.addEventListener('input', refresh);

        document.getElementById('btnExport').addEventListener('click', () => {
            const header = ['Teacher', 'Assigned', 'Capacity', 'Utilization%'];
            const rows = RAW.map(r => [
                `"${r.name.replace(/"/g, '""')}"`,
                r.periods,
                WEEKLY,
                ((r.periods / WEEKLY * 100).toFixed(1))
            ]);
            const csv = [header.join(','), ...rows.map(r => r.join(','))].join('\n');
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = Object.assign(document.createElement('a'), { href: url, download: `workload_{{ $id }}.csv` });
            document.body.appendChild(a); a.click(); a.remove(); URL.revokeObjectURL(url);
        });

        refresh();
    </script>
</body>

</html>
