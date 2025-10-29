<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Teacher Workload – Timetable #{{ $id }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial; margin:24px; color:#111}
    .muted{color:#6b7280}
    .wrap{display:grid; gap:20px}
    .cards{display:grid; grid-template-columns:1fr; gap:20px}
    @media (min-width: 1000px){ .cards{grid-template-columns: 1fr 1fr;} }
    .card{border:1px solid #e5e7eb; border-radius:12px; padding:16px}
    h1{margin:8px 0 4px}
    a.btn{display:inline-block; padding:8px 12px; border-radius:10px; border:1px solid #111; text-decoration:none; color:#111}
    .legend{font-size:13px; margin-top:10px}
  </style>
  <!-- ApexCharts CDN -->
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body>
  <a href="{{ route('tt.show', $id) }}" class="btn">&larr; Back to timetable</a>
  <h1>Teacher Workload</h1>
  <p class="muted">Timetable #{{ $id }} • Created {{ \Illuminate\Support\Carbon::parse($req->created_at)->format('Y-m-d H:i') }}</p>

  <div class="wrap">
    <div class="cards">
      <div class="card">
        <h3 style="margin:0 0 10px;">Weekly periods per teacher</h3>
        <div id="chart_total"></div>
        <div class="legend muted">Shows total number of scheduled periods in the selected timetable.</div>
      </div>

      <div class="card">
        <h3 style="margin:0 0 10px;">Daily distribution (heatmap)</h3>
        <div id="chart_heatmap"></div>
        <div class="legend muted">Darker = more periods. Helps spot overloaded days.</div>
      </div>
    </div>
  </div>

  <script>
    // Data injected from controller
    const BAR_CATEGORIES = @json($barCategories);
    const BAR_DATA       = @json($barData);
    const HEATMAP_SERIES = @json($heatmapSeries);

    // 1) Horizontal bar – total periods per teacher
    const optsBar = {
      chart: { type: 'bar', height: 420 },
      series: [{ name: 'Periods', data: BAR_DATA }],
      xaxis: { categories: BAR_CATEGORIES, title: { text: 'Teachers' } },
      dataLabels: { enabled: true },
      plotOptions: {
        bar: {
          horizontal: true,
          borderRadius: 6,
          barHeight: '70%'
        }
      },
      tooltip: {
        y: { formatter: (val) => `${val} period${val===1?'':'s'}` }
      }
    };
    new ApexCharts(document.querySelector("#chart_total"), optsBar).render();

    // 2) Heatmap – per teacher per day
    const optsHeat = {
      chart: { type: 'heatmap', height: 420 },
      series: HEATMAP_SERIES,
      dataLabels: { enabled: true, style: { colors: ['#000'] } },
      xaxis: { title: { text: 'Day' } },
      yaxis: { title: { text: 'Teacher' } },
      colors: ["#e5e7eb"], // base; Apex will generate gradient
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
      tooltip: {
        y: { formatter: (val) => `${val} period${val===1?'':'s'}` }
      }
    };
    new ApexCharts(document.querySelector("#chart_heatmap"), optsHeat).render();
  </script>
</body>
</html>
