<!doctype html>
<html class="h-full">
<head>
  <meta charset="utf-8">
  <title>Subject Usage – Timetable #{{ $id }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  @vite(['resources/css/app.css','resources/js/app.js'])
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body class="h-full bg-gradient-to-br from-slate-50 via-purple-50/30 to-slate-50 text-gray-900 antialiased">
<main class="min-h-screen p-4 md:p-8">
  <div class="max-w-7xl mx-auto space-y-6">

    <div class="space-y-4">
      <div class="flex items-center gap-3">
        <a href="{{ route('tt.show', $id) }}"
           class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
          Back to Timetable
        </a>
      </div>

      <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6">
        <div>
          <h1 class="text-3xl md:text-4xl font-bold text-slate-900">Subject Usage Analysis</h1>
          <p class="text-slate-600 mt-2">Timetable #{{ $id }} · Weekly period distribution by subject and group</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200/60 px-6 py-4">
          <div class="flex items-start gap-4">
            <div class="p-3 bg-blue-50 rounded-xl">
              <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div>
              <p class="text-sm font-medium text-slate-600">This Week</p>
              <p class="text-2xl font-bold text-slate-900 mt-1">{{ $total }} period{{ $total === 1 ? '' : 's' }}</p>
              @if(!is_null($required))
                <p class="text-xs text-slate-500 mt-1">Target: {{ $required }}/week</p>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>

    <form method="GET" class="bg-white rounded-2xl shadow-sm border border-slate-200/60 p-6">
      <h3 class="text-base font-semibold text-slate-900 mb-4">Filters</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">Group</label>
          <select name="group_id"
                  class="w-full px-4 py-2.5 rounded-xl border-slate-300 bg-white text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
            @foreach($groups as $g)
              <option value="{{ $g->id }}" @selected($g->id == $groupId)>{{ $g->name }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">Subject</label>
          <select name="subject_id"
                  class="w-full px-4 py-2.5 rounded-xl border-slate-300 bg-white text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
            @foreach($subjects as $s)
              <option value="{{ $s->id }}" @selected($s->id == $subjectId)>{{ $s->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="flex items-end">
          <button class="w-full px-6 py-2.5 bg-blue-500 text-white rounded-xl hover:bg-blue-700 transition-all shadow-lg shadow-indigo-200 font-medium text-sm">
            Apply Filters
          </button>
        </div>
      </div>
    </form>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

      <div class="bg-white rounded-2xl shadow-sm border border-slate-200/60 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-slate-900">Total Periods</h3>
          <span class="p-2 bg-purple-50 rounded-lg">
            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
          </span>
        </div>
        <div id="radial"></div>
        <div class="mt-4 p-3 bg-slate-50 rounded-xl">
          <p class="text-xs text-slate-600 leading-relaxed">
            <span class="font-semibold text-slate-900">{{ $subjectName }}</span> in <span class="font-semibold text-slate-900">{{ $groupName }}</span>
            <br>
            <span class="text-slate-500">{{ $total }} scheduled this week</span>
            @if(!is_null($required))
              <span class="text-slate-500"> · Target: {{ $required }}</span>
            @endif
          </p>
        </div>
      </div>

      <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200/60 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-slate-900">Daily Distribution</h3>
          <span class="text-xs font-medium text-slate-500 bg-slate-100 px-3 py-1 rounded-full">Periods per day</span>
        </div>
        <div id="bars"></div>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/60 p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-slate-900">Weekly Schedule</h3>
        <span class="text-xs font-medium text-slate-500 bg-slate-100 px-3 py-1 rounded-full">{{ count($rows) }} lesson{{ count($rows) === 1 ? '' : 's' }}</span>
      </div>

      <div class="overflow-x-auto -mx-6 px-6">
        <table class="min-w-full">
          <thead>
            <tr class="border-b border-slate-200">
              <th class="text-left py-3 px-4 text-xs font-semibold text-slate-600 uppercase tracking-wider">Day</th>
              <th class="text-left py-3 px-4 text-xs font-semibold text-slate-600 uppercase tracking-wider">Period</th>
              <th class="text-left py-3 px-4 text-xs font-semibold text-slate-600 uppercase tracking-wider">Time</th>
              <th class="text-left py-3 px-4 text-xs font-semibold text-slate-600 uppercase tracking-wider">Teacher</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @php $dayNames=[1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',7=>'Sun']; @endphp
            @forelse($rows as $r)
              <tr class="hover:bg-slate-50/50 transition-colors">
                <td class="py-3 px-4">
                  <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-blue-50 text-indigo-700 ring-1 ring-indigo-200">
                    {{ $dayNames[$r->day_of_week] ?? 'Day '.$r->day_of_week }}
                  </span>
                </td>
                <td class="py-3 px-4 text-sm font-semibold text-slate-900">P{{ $r->slot_index }}</td>
                <td class="py-3 px-4 text-sm text-slate-600">
                  <span class="font-mono">{{ substr($r->start_time,0,5) }}</span>
                  <span class="text-slate-400">–</span>
                  <span class="font-mono">{{ substr($r->end_time,0,5) }}</span>
                </td>
                <td class="py-3 px-4 text-sm text-slate-700">{{ $r->teacher_name ?? '—' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="py-12 text-center">
                  <svg class="w-12 h-12 mx-auto text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                  <p class="mt-3 text-slate-500">No lessons scheduled for this combination.</p>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<script>

  const TOTAL    = {{ (int) $total }};
  const REQUIRED = @json($required);
  const DAYS     = @json($perDaySeries['categories'] ?? []);
  const COUNTS   = @json($perDaySeries['data'] ?? []);
  const WEEKLY   = {{ (int) $weeklySlots }};

  const maxRing = (REQUIRED ?? TOTAL) || 1;
  const radialOptions = {
    chart: { type:'radialBar', height: 280, fontFamily: 'inherit' },
    series: [ Math.min(100, Math.round((TOTAL / (REQUIRED ?? WEEKLY)) * 100)) ],
    labels: ['Weekly Progress'],
    colors: ['#3b82f6'],
    plotOptions: {
      radialBar: {
        hollow: { size:'65%' },
        track: { background:'#e2e8f0', strokeWidth: '100%' },
        dataLabels: {
          name: { offsetY: 20, color:'#64748b', fontSize:'13px', fontWeight: 500 },
          value: {
            offsetY: -15,
            fontSize:'32px',
            fontWeight: 700,
            color: '#3b82f6',
            formatter:(v)=>`${v}%`
          }
        }
      }
    },
    stroke: {
      lineCap: 'round'
    }
  };
  new ApexCharts(document.querySelector('#radial'), radialOptions).render();

  const barOptions = {
    chart: { type:'bar', height: 320, toolbar:{show:false}, fontFamily: 'inherit' },
    series: [{ name:'Periods', data: COUNTS }],
    xaxis: {
      categories: DAYS,
      labels: { style: { colors: '#64748b', fontSize: '12px', fontWeight: 500 } }
    },
    yaxis: {
      labels: { style: { colors: '#64748b', fontSize: '12px' } }
    },
    plotOptions: {
      bar: {
        columnWidth:'55%',
        borderRadius: 8,
        dataLabels: { position: 'top' }
      }
    },
    dataLabels: {
      enabled: true,
      formatter: (val) => val,
      offsetY: -20,
      style: {
        fontSize: '11px',
        colors: ['#64748b'],
        fontWeight: 600
      }
    },
    grid: {
      borderColor:'#e2e8f0',
      strokeDashArray: 3,
      padding: {
        top: 0,
        bottom: 0
      }
    },
    colors: ['#3b82f6'],
    tooltip: {
      y: { formatter:(v)=> `${v} period${v===1?'':'s'}` },
      style: { fontSize: '12px' }
    }
  };
  new ApexCharts(document.querySelector('#bars'), barOptions).render();
</script>
</body>
</html>
