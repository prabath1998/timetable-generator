<!doctype html>
<html class="h-full">
<head>
  <meta charset="utf-8">
  <title>Teacher Workload – Timetable #{{ $id }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  @vite(['resources/css/app.css','resources/js/app.js'])
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body class="h-full bg-gray-50 text-gray-900 antialiased">
  <main class="max-w-7xl mx-auto p-6">
    <a href="{{ route('tt.show', $id) }}" class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 border-gray-300 hover:border-gray-400">&larr; Back to timetable</a>
    <h1 class="text-2xl font-semibold mt-3">Teacher Workload</h1>
    <p class="text-sm text-gray-600">Timetable #{{ $id }} • Created {{ \Illuminate\Support\Carbon::parse($req->created_at)->format('Y-m-d H:i') }}</p>

    <section class="grid grid-cols-1 lg:grid-cols-2 gap-5 mt-5">
      <div class="rounded-xl border bg-white p-5">
        <h3 class="text-lg font-medium mb-2">Weekly periods per teacher</h3>
        <div id="chart_total"></div>
        <p class="text-xs text-gray-600 mt-2">Shows total number of scheduled periods in the selected timetable.</p>
      </div>

      <div class="rounded-xl border bg-white p-5">
        <h3 class="text-lg font-medium mb-2">Daily distribution (heatmap)</h3>
        <div id="chart_heatmap"></div>
        <p class="text-xs text-gray-600 mt-2">Darker = more periods. Helps spot overloaded days.</p>
      </div>
    </section>
  </main>

  <script>
    const BAR_CATEGORIES = @json($barCategories);
    const BAR_DATA       = @json($barData);
    const HEATMAP_SERIES = @json($heatmapSeries);

    new ApexCharts(document.querySelector("#chart_total"), {
      chart: { type: 'bar', height: 420 },
      series: [{ name: 'Periods', data: BAR_DATA }],
      xaxis: { categories: BAR_CATEGORIES, title: { text: 'Teachers' } },
      dataLabels: { enabled: true },
      plotOptions: { bar: { horizontal: true, borderRadius: 6, barHeight: '70%' } },
      tooltip: { y: { formatter: (val) => `${val} period${val===1?'':'s'}` } }
    }).render();

    new ApexCharts(document.querySelector("#chart_heatmap"), {
      chart: { type: 'heatmap', height: 420 },
      series: HEATMAP_SERIES,
      dataLabels: { enabled: true, style: { colors: ['#000'] } },
      xaxis: { title: { text: 'Day' } },
      yaxis: { title: { text: 'Teacher' } },
      colors: ["#e5e7eb"],
      plotOptions: {
        heatmap: {
          shadeIntensity: 0.5,
          colorScale: {
            ranges: [
              { from: 0, to: 0, name: '0', color: '#f3f4f6' },
              { from: 1, to: 1, name: '1', color: '#d1fae5' },
              { from: 2, to: 2, name: '2', color: '#a7f3d0' },
              { from: 3, to: 3, name: '3', color: '#6ee7b7' },
              { from: 4, to: 4, name: '4', color: '#34d399' },
              { from: 5, to: 99, name: '5+', color: '#10b981' },
            ]
          }
        }
      },
      tooltip: { y: { formatter: (val) => `${val} period${val===1?'':'s'}` } }
    }).render();
  </script>
</body>
</html>
