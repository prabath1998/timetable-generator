<!doctype html>
<html class="h-full">
<head>
  <meta charset="utf-8" />
  <title>Workload – Timetable #{{ $id }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  @vite(['resources/css/app.css','resources/js/app.js'])
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body class="h-full bg-gray-50 text-gray-900 antialiased">
@php
  // Normalize data from controller
  $rows = collect($barCategories ?? [])->zip($barData ?? [])->map(function($pair){
      [$name, $count] = $pair;
      return ['name' => (string)$name, 'periods' => (int)$count];
  });

  $weeklySlots   = $weeklySlots ?? 40;
  $totalTeachers = $rows->count();
  $totalAssigned = (int) $rows->sum('periods');
  $totalCapacity = max($totalTeachers * $weeklySlots, 1);
  $utilPercent   = round($totalAssigned / $totalCapacity * 100, 1);
  $avgLoad       = $totalTeachers ? round($totalAssigned / $totalTeachers, 1) : 0;
@endphp

<main class="max-w-6xl mx-auto p-6">
  <!-- Header -->
  <div class="flex flex-wrap items-end justify-between gap-4">
    <div>
      <a href="{{ route('tt.show', $id) }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back</a>
      <h1 class="text-2xl font-semibold mt-1">Teacher Workload <span class="text-gray-500">#{{ $id }}</span></h1>
      <p class="text-sm text-gray-600">Showing assigned periods out of {{ $weeklySlots }} per teacher.</p>
    </div>
    <div class="flex items-center gap-3">
      <div class="rounded-xl border bg-white px-3 py-2 text-sm">
        <div class="text-gray-500">Utilization</div>
        <div class="font-semibold">{{ $utilPercent }}%</div>
      </div>
      <div class="rounded-xl border bg-white px-3 py-2 text-sm">
        <div class="text-gray-500">Avg / Teacher</div>
        <div class="font-semibold">{{ $avgLoad }} / {{ $weeklySlots }}</div>
      </div>
      <button id="btnExport"
              class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm hover:border-gray-400">⬇️ CSV</button>
    </div>
  </div>

  <!-- Controls -->
  <section class="mt-5 rounded-2xl border bg-white p-4">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
      <label class="text-sm text-gray-700">
        <span class="block">Search teacher</span>
        <input id="q" type="text" placeholder="Type a name…"
               class="mt-1 w-full rounded-md border-gray-300 focus:border-gray-900 focus:ring-gray-900" />
      </label>
      <label class="text-sm text-gray-700">
        <span class="block">Sort by</span>
        <select id="sortBy"
                class="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-gray-900 focus:ring-gray-900">
          <option value="load_desc" selected>Load High → Low</option>
          <option value="load_asc">Load Low → High</option>
          <option value="name_asc">Name A → Z</option>
          <option value="name_desc">Name Z → A</option>
        </select>
      </label>
      <label class="text-sm text-gray-700">
        <span class="block">Show top N</span>
        <input id="topN" type="number" min="5" max="{{ max(5, $totalTeachers) }}" value="20"
               class="mt-1 w-full rounded-md border-gray-300 focus:border-gray-900 focus:ring-gray-900" />
      </label>
    </div>
  </section>

  <!-- Chart -->
  <section class="mt-5 rounded-2xl border bg-white p-4">
    <div class="flex items-center justify-between">
      <h3 class="text-base font-medium">Loads by teacher</h3>
      <div class="text-xs text-gray-500">bars show periods / {{ $weeklySlots }}</div>
    </div>
    <div id="chartBars" class="mt-3"></div>
  </section>

  <!-- Table -->
  <section class="mt-5 rounded-2xl border bg-white p-4">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="bg-gray-100 text-gray-700">
            <th class="text-left py-2 px-3">#</th>
            <th class="text-left py-2 px-3">Teacher</th>
            <th class="text-left py-2 px-3">Assigned</th>
            <th class="text-left py-2 px-3">Capacity</th>
            <th class="text-left py-2 px-3">Utilization</th>
          </tr>
        </thead>
        <tbody id="tbody" class="divide-y divide-gray-200"></tbody>
      </table>
    </div>
  </section>
</main>

<script>
  // Data
  const WEEKLY = {{ (int) $weeklySlots }};
  const RAW = @json($rows->values()->all()); // [{name, periods}]

  // Helpers
  function pct(v){ return (Math.round(v*10)/10).toFixed(1) + '%'; }
  function sortData(kind, arr){
    const a = [...arr];
    switch(kind){
      case 'name_asc':  a.sort((x,y)=>x.name.localeCompare(y.name)); break;
      case 'name_desc': a.sort((x,y)=>y.name.localeCompare(x.name)); break;
      case 'load_asc':  a.sort((x,y)=>(x.periods||0)-(y.periods||0)); break;
      case 'load_desc': a.sort((x,y)=>(y.periods||0)-(x.periods||0)); break;
    }
    return a;
  }

  // UI elements
  const q = document.getElementById('q');
  const sortBy = document.getElementById('sortBy');
  const topN = document.getElementById('topN');

  // Table render
  function renderTable(list){
    const body = document.getElementById('tbody');
    body.innerHTML = list.map((r,i)=>`
      <tr class="bg-white">
        <td class="py-2 px-3 text-gray-500">${i+1}</td>
        <td class="py-2 px-3">${r.name}</td>
        <td class="py-2 px-3 font-medium">${r.periods}</td>
        <td class="py-2 px-3">${WEEKLY}</td>
        <td class="py-2 px-3">${pct((r.periods||0)/WEEKLY*100)}</td>
      </tr>
    `).join('');
  }

  // Bar chart
  let bars;
  function renderBars(list){
    const names = list.map(x=>x.name);
    const loads = list.map(x=>x.periods);

    const opts = {
      series: [{ name: 'Periods', data: loads }],
      chart: { type:'bar', height: Math.max(320, 20 + list.length * 26), toolbar:{show:false} },
      plotOptions: { bar: { horizontal:true, borderRadius:6, barHeight:'70%' } },
      dataLabels: { enabled:false },
      xaxis: { categories:names, max: WEEKLY, tickAmount: 8 },
      grid: { borderColor:'#e5e7eb' },
      colors: ['#4169E1'],
      tooltip: { y: { formatter:(v)=>`${v} / ${WEEKLY}` } }
    };
    if (bars) bars.destroy();
    bars = new ApexCharts(document.querySelector("#chartBars"), opts);
    bars.render();
  }

  // Filtering + render
  function currentList(){
    const query = (q.value||'').toLowerCase();
    let data = RAW.filter(r => r.name.toLowerCase().includes(query));
    data = sortData(sortBy.value, data);
    const n = Math.max(5, Number(topN.value||20));
    return data.slice(0, n);
  }
  function refresh(){
    const list = currentList();
    renderBars(list);
    renderTable(list);
  }

  q.addEventListener('input', refresh);
  sortBy.addEventListener('change', refresh);
  topN.addEventListener('input', refresh);

  // CSV export (full list, not just filtered)
  document.getElementById('btnExport').addEventListener('click', ()=>{
    const header = ['Teacher','Assigned','Capacity','Utilization%'];
    const rows = RAW.map(r=>[
      `"${r.name.replace(/"/g,'""')}"`,
      r.periods,
      WEEKLY,
      ((r.periods/WEEKLY*100).toFixed(1))
    ]);
    const csv = [header.join(','), ...rows.map(r=>r.join(','))].join('\n');
    const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
    const url = URL.createObjectURL(blob);
    const a = Object.assign(document.createElement('a'), {href:url, download:`workload_{{ $id }}.csv`});
    document.body.appendChild(a); a.click(); a.remove(); URL.revokeObjectURL(url);
  });

  // Init
  refresh();
</script>
</body>
</html>
