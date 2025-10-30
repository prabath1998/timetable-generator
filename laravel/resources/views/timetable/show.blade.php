<!doctype html>
<html class="h-full">
<head>
  <meta charset="utf-8">
  <title>Timetable #{{ $id }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="h-full bg-gray-50 text-gray-900 antialiased">
@php
  // Deterministic Tailwind chip classes for subjects
  $palette = [
    'bg-rose-100 text-rose-800 ring-rose-200',
    'bg-sky-100 text-sky-800 ring-sky-200',
    'bg-amber-100 text-amber-900 ring-amber-200',
    'bg-emerald-100 text-emerald-800 ring-emerald-200',
    'bg-indigo-100 text-indigo-800 ring-indigo-200',
    'bg-fuchsia-100 text-fuchsia-800 ring-fuchsia-200',
    'bg-cyan-100 text-cyan-800 ring-cyan-200',
    'bg-lime-100 text-lime-800 ring-lime-200',
    'bg-violet-100 text-violet-800 ring-violet-200',
    'bg-orange-100 text-orange-900 ring-orange-200',
    'bg-teal-100 text-teal-800 ring-teal-200',
    'bg-pink-100 text-pink-800 ring-pink-200',
  ];
  $chipFor = function (string $subject) use ($palette) {
    $idx = abs(crc32($subject)) % count($palette);
    return $palette[$idx];
  };
  $dayNames=[1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',7=>'Sun'];
@endphp

<main class="w-full max-w-none px-6 py-6">
  <!-- Header -->
  <div class="flex items-start justify-between gap-4">
    <div>
      <a href="{{ route('tt.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back</a>
      <h1 class="text-2xl font-semibold mt-2">Timetable <span class="text-gray-500">#{{ $id }}</span></h1>
      <p class="text-sm text-gray-600">
        Status:
        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs
          @if($req->status==='solved') border-emerald-300 bg-emerald-50 text-emerald-700
          @elseif($req->status==='failed') border-red-300 bg-red-50 text-red-700
          @else border-amber-300 bg-amber-50 text-amber-700 @endif">
          {{ $req->status }}
        </span>
        • Requested: {{ \Illuminate\Support\Carbon::parse($req->created_at)->format('Y-m-d H:i') }}
      </p>
    </div>
    <div class="flex items-center gap-2">
      <a href="{{ route('tt.workload', $id) }}"
         class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm hover:border-gray-400">
        📊 Teacher workload
      </a>
      <button onclick="window.print()"
        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm hover:border-gray-400">
        🖨️ Print
      </button>
    </div>
  </div>

  <!-- Tabs / Filters -->
  <div class="sticky top-0 z-10 mt-5 -mx-6 px-6 bg-gray-50/80 backdrop-blur supports-[backdrop-filter]:bg-gray-50/60 border-y border-gray-200">
    <div class="py-3">
      <div class="flex flex-wrap items-center gap-2">
        <button id="tab-full" class="px-3 py-1.5 rounded-full border text-sm hover:bg-gray-100" onclick="switchTab('full')">Full (by Group)</button>
        <button id="tab-group" class="px-3 py-1.5 rounded-full border text-sm hover:bg-gray-100" onclick="switchTab('group')">Per Group</button>
        <button id="tab-teacher" class="px-3 py-1.5 rounded-full border text-sm hover:bg-gray-100" onclick="switchTab('teacher')">Per Teacher</button>

        <div class="ml-auto flex items-center gap-3">
          <div id="groupFilter" class="hidden items-center gap-2">
            <label class="text-sm text-gray-700">Group:</label>
            <select id="groupSel" onchange="showOnlyGroup()"
                    class="rounded-lg border-gray-300 bg-white text-sm focus:border-gray-900 focus:ring-gray-900">
              @foreach($groups as $g)
                <option value="{{ $g->id }}">{{ $g->name }}</option>
              @endforeach
            </select>
          </div>
          <div id="teacherFilter" class="hidden items-center gap-2">
            <label class="text-sm text-gray-700">Teacher:</label>
            <select id="teacherSel" onchange="showOnlyTeacher()"
                    class="rounded-lg border-gray-300 bg-white text-sm focus:border-gray-900 focus:ring-gray-900">
              @foreach($teachers as $t)
                <option value="{{ $t->id }}">{{ $t->name }}</option>
              @endforeach
            </select>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- FULL (by group) — flipped axes: Periods (rows) × Days (cols) -->
  <section id="panel-full" class="mt-6 hidden">
    @if(empty($byGroup))
      <div class="rounded-xl border bg-white p-6 text-gray-600">No entries found.</div>
    @else
      @foreach($groups as $g)
        @php $m = $byGroup[$g->id] ?? []; @endphp
        <div class="rounded-xl border bg-white mt-6 overflow-hidden">
          <div class="flex items-center justify-between px-5 py-3 border-b">
            <h3 class="text-lg font-medium">Group: {{ $g->name }}</h3>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-[13px] sm:text-sm">
              <thead class="bg-gray-100">
                <tr class="text-gray-700">
                  <th class="text-left py-2 px-3 w-28">Period \ Day</th>
                  @foreach($days as $d)
                    <th class="text-left py-2 px-2 sm:px-3">{{ $dayNames[$d] ?? 'Day '.$d }}</th>
                  @endforeach
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                @foreach($slots as $s)
                  <tr class="bg-white">
                    <th class="text-left py-2 px-3 font-medium text-gray-700 sticky left-0 bg-white">P{{ $s }}</th>
                    @foreach($days as $d)
                      @php
                        $cell    = $m[$d][$s] ?? null;
                        $tsId    = $timeslotIdMap[$d][$s] ?? '';
                        $entryId = $cell['entry_id'] ?? null;
                        $chip    = $cell ? $chipFor($cell['subject']) : '';
                      @endphp
                      <td class="align-top min-h-[48px] py-1.5 px-2 sm:px-3"
                          data-timeslot-id="{{ $tsId }}"
                          ondragover="onDragOver(event)"
                          ondragleave="onDragLeave(event)"
                          ondrop="onDrop(event, {{ $id }})">
                        @if($cell && $entryId)
                          <div class="rounded-lg border border-gray-200 bg-white px-2 py-1.5 cursor-grab shadow-sm hover:shadow transition"
                               draggable="true"
                               ondragstart="onDragStart(event)"
                               data-entry-id="{{ $entryId }}"
                               title="{{ $cell['subject'] }} • {{ $cell['teacher'] }}">
                            <span class="inline-flex items-center gap-2">
                              <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] ring-1 {{ $chip }}">{{ $cell['subject'] }}</span>
                              <span class="text-gray-600 truncate max-w-[12rem]">— {{ $cell['teacher'] }}</span>
                            </span>
                          </div>
                        @else
                          <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] border text-gray-600">Free</span>
                        @endif
                      </td>
                    @endforeach
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      @endforeach
    @endif
  </section>

  <!-- PER GROUP (single selected) — flipped axes -->
  <section id="panel-group" class="mt-6 hidden">
    @php $firstGroup = $groups->first(); @endphp
    <div id="groupPanelOne" class="rounded-xl border bg-white overflow-hidden">
      <div class="flex items-center justify-between px-5 py-3 border-b">
        <h3 id="groupPanelTitle" class="text-lg font-medium">Group: {{ $firstGroup?->name ?? '—' }}</h3>
      </div>
      <div id="groupPanelBody" class="overflow-x-auto">
        @if($firstGroup)
          @php $m = $byGroup[$firstGroup->id] ?? []; @endphp
          <table class="w-full text-[13px] sm:text-sm">
            <thead class="bg-gray-100">
              <tr class="text-gray-700">
                <th class="text-left py-2 px-3 w-28">Period \ Day</th>
                @foreach($days as $d)
                  <th class="text-left py-2 px-2 sm:px-3">{{ $dayNames[$d] ?? 'Day '.$d }}</th>
                @endforeach
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              @foreach($slots as $s)
                <tr class="bg-white">
                  <th class="text-left py-2 px-3 font-medium text-gray-700 sticky left-0 bg-white">P{{ $s }}</th>
                  @foreach($days as $d)
                    @php
                      $cell    = $m[$d][$s] ?? null;
                      $tsId    = $timeslotIdMap[$d][$s] ?? '';
                      $entryId = $cell['entry_id'] ?? null;
                      $chip    = $cell ? $chipFor($cell['subject']) : '';
                    @endphp
                    <td class="align-top min-h-[48px] py-1.5 px-2 sm:px-3"
                        data-timeslot-id="{{ $tsId }}"
                        ondragover="onDragOver(event)"
                        ondragleave="onDragLeave(event)"
                        ondrop="onDrop(event, {{ $id }})">
                      @if($cell && $entryId)
                        <div class="rounded-lg border border-gray-200 bg-white px-2 py-1.5 cursor-grab shadow-sm hover:shadow transition"
                             draggable="true"
                             ondragstart="onDragStart(event)"
                             data-entry-id="{{ $entryId }}"
                             title="{{ $cell['subject'] }} • {{ $cell['teacher'] }}">
                          <span class="inline-flex items-center gap-2">
                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] ring-1 {{ $chip }}">{{ $cell['subject'] }}</span>
                            <span class="text-gray-600 truncate max-w-[12rem]">— {{ $cell['teacher'] }}</span>
                          </span>
                        </div>
                      @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] border text-gray-600">Free</span>
                      @endif
                    </td>
                  @endforeach
                </tr>
              @endforeach
            </tbody>
          </table>
        @else
          <div class="p-6 text-gray-600">No groups found.</div>
        @endif
      </div>
    </div>
  </section>

  <!-- PER TEACHER (single selected) — flipped axes -->
  <section id="panel-teacher" class="mt-6 hidden">
    @php $firstTeacher = $teachers->first(); @endphp
    <div id="teacherPanelOne" class="rounded-xl border bg-white overflow-hidden">
      <div class="flex items-center justify-between px-5 py-3 border-b">
        <h3 id="teacherPanelTitle" class="text-lg font-medium">Teacher: {{ $firstTeacher?->name ?? '—' }}</h3>
      </div>
      <div id="teacherPanelBody" class="overflow-x-auto">
        @if($firstTeacher)
          @php $m = $byTeacher[$firstTeacher->id] ?? []; @endphp
          <table class="w-full text-[13px] sm:text-sm">
            <thead class="bg-gray-100">
              <tr class="text-gray-700">
                <th class="text-left py-2 px-3 w-28">Period \ Day</th>
                @foreach($days as $d)
                  <th class="text-left py-2 px-2 sm:px-3">{{ $dayNames[$d] ?? 'Day '.$d }}</th>
                @endforeach
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              @foreach($slots as $s)
                <tr class="bg-white">
                  <th class="text-left py-2 px-3 font-medium text-gray-700 sticky left-0 bg-white">P{{ $s }}</th>
                  @foreach($days as $d)
                    @php
                      $cell    = $m[$d][$s] ?? null;
                      $tsId    = $timeslotIdMap[$d][$s] ?? '';
                      $entryId = $cell['entry_id'] ?? null;
                      $chip    = $cell ? $chipFor($cell['subject']) : '';
                    @endphp
                    <td class="align-top min-h-[48px] py-1.5 px-2 sm:px-3"
                        data-timeslot-id="{{ $tsId }}"
                        ondragover="onDragOver(event)"
                        ondragleave="onDragLeave(event)"
                        ondrop="onDrop(event, {{ $id }})">
                      @if($cell && $entryId)
                        <div class="rounded-lg border border-gray-200 bg-white px-2 py-1.5 cursor-grab shadow-sm hover:shadow transition"
                             draggable="true"
                             ondragstart="onDragStart(event)"
                             data-entry-id="{{ $entryId }}"
                             title="{{ $cell['subject'] }} • {{ $cell['group'] }}">
                          <span class="inline-flex items-center gap-2">
                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] ring-1 {{ $chip }}">{{ $cell['subject'] }}</span>
                            <span class="text-gray-600 truncate max-w-[12rem]">— {{ $cell['group'] }}</span>
                          </span>
                        </div>
                      @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] border text-gray-600">Free</span>
                      @endif
                    </td>
                  @endforeach
                </tr>
              @endforeach
            </tbody>
          </table>
        @else
          <div class="p-6 text-gray-600">No teachers found.</div>
        @endif
      </div>
    </div>
  </section>
</main>

<!-- Flipped-layout JS + DnD (global) -->
<script>
  // ----- Blade → JS data -----
  const REQ_ID     = {{ (int) $id }};
  const BY_GROUP   = @json($byGroup ?? []);
  const BY_TEACHER = @json($byTeacher ?? []);
  const DAYS       = @json($days ?? []);
  const SLOTS      = @json($slots ?? []);
  const TS_MAP     = @json($timeslotIdMap ?? []);
  const DAY_NAMES  = {1:'Mon',2:'Tue',3:'Wed',4:'Thu',5:'Fri',6:'Sat',7:'Sun'};

  // ----- Tabs / Filters -----
  function activateTab(tab){
    document.querySelectorAll('button[id^="tab-"]').forEach(b=>b.classList.remove('bg-gray-900','text-white','border-gray-900'));
    document.getElementById('tab-'+tab)?.classList.add('bg-gray-900','text-white','border-gray-900');

    document.getElementById('groupFilter')?.classList.toggle('hidden', tab!=='group');
    document.getElementById('teacherFilter')?.classList.toggle('hidden', tab!=='teacher');

    document.querySelectorAll('section[id^="panel-"]').forEach(p=>p.classList.add('hidden'));
    document.getElementById('panel-'+tab)?.classList.remove('hidden');
  }
  function switchTab(tab){ activateTab(tab); }
  window.switchTab = switchTab;

  // ----- Subject chip (match PHP palette) -----
  function subjectChip(subject){
    const palette = [
      'bg-rose-100 text-rose-800 ring-rose-200',
      'bg-sky-100 text-sky-800 ring-sky-200',
      'bg-amber-100 text-amber-900 ring-amber-200',
      'bg-emerald-100 text-emerald-800 ring-emerald-200',
      'bg-indigo-100 text-indigo-800 ring-indigo-200',
      'bg-fuchsia-100 text-fuchsia-800 ring-fuchsia-200',
      'bg-cyan-100 text-cyan-800 ring-cyan-200',
      'bg-lime-100 text-lime-800 ring-lime-200',
      'bg-violet-100 text-violet-800 ring-violet-200',
      'bg-orange-100 text-orange-900 ring-orange-200',
      'bg-teal-100 text-teal-800 ring-teal-200',
      'bg-pink-100 text-pink-800 ring-pink-200',
    ];
    let h=0; for(let i=0;i<subject.length;i++){ h=(h*33+subject.charCodeAt(i))>>>0; }
    return palette[h % palette.length];
  }

  // ----- Drag & Drop -----
  const csrf = () => document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  let dragEntryId = null;

  function onDragStart(ev){
    const el = ev.target.closest('[data-entry-id]');
    dragEntryId = el?.dataset.entryId;
    ev.dataTransfer.setData('text/plain', dragEntryId || '');
    ev.dataTransfer.effectAllowed = 'move';
  }
  function onDragOver(ev){
    ev.preventDefault();
    ev.currentTarget.classList.add('ring','ring-emerald-400','ring-offset-2','ring-offset-white');
  }
  function onDragLeave(ev){
    ev.currentTarget.classList.remove('ring','ring-emerald-400','ring-offset-2','ring-offset-white');
  }
  async function onDrop(ev, reqId){
    ev.preventDefault();
    const cell = ev.currentTarget;
    cell.classList.remove('ring','ring-emerald-400','ring-offset-2','ring-offset-white');

    const toTsId = cell.dataset.timeslotId;
    if (!dragEntryId || !toTsId) return;

    const occupant = cell.querySelector('[data-entry-id]');
    let body = { entry_id: dragEntryId, to_timeslot_id: toTsId };
    if (occupant) body.swap_with_entry_id = occupant.dataset.entryId;

    try{
      const res = await fetch(`${location.origin}/timetable/${reqId}/move`, {
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN': csrf()},
        body: JSON.stringify(body)
      });
      const data = await res.json();
      if (!res.ok) {
        if (data?.needs_swap) {
          if (confirm('Destination occupied. Swap lessons?')) {
            body.swap_with_entry_id = data.swap_candidate_id;
            const res2 = await fetch(`${location.origin}/timetable/${reqId}/move`, {
              method:'POST',
              headers:{'Content-Type':'application/json','X-CSRF-TOKEN': csrf()},
              body: JSON.stringify(body)
            });
            const data2 = await res2.json();
            if (res2.ok && data2.ok) location.reload(); else alert(data2.message || 'Swap failed');
          }
        } else {
          alert(data?.message || 'Move not allowed');
        }
        return;
      }
      if (data.ok) location.reload(); else alert(data.message || 'Move failed');
    } catch {
      alert('Network error');
    }
  }
  // expose for inline handlers
  window.onDragStart = onDragStart;
  window.onDragOver  = onDragOver;
  window.onDragLeave = onDragLeave;
  window.onDrop      = onDrop;

  // ----- Builders (flipped axes) -----
  function buildCell(d,s,contentHtml){
    const tsId = (TS_MAP[d] && TS_MAP[d][s]) ? TS_MAP[d][s] : '';
    return `<td class="align-top min-h-[48px] py-1.5 px-2 sm:px-3"
              data-timeslot-id="${tsId}"
              ondragover="onDragOver(event)"
              ondragleave="onDragLeave(event)"
              ondrop="onDrop(event, ${REQ_ID})">${contentHtml}</td>`;
  }

  function renderGroup(gid){
    const m = BY_GROUP[gid] || {};
    let thead = `<thead class="bg-gray-100"><tr class="text-gray-700">
      <th class="text-left py-2 px-3 w-28">Period \\ Day</th>`;
    DAYS.forEach(d => thead += `<th class="text-left py-2 px-2 sm:px-3">${DAY_NAMES[d] || ('Day '+d)}</th>`);
    thead += `</tr></thead>`;

    let tbody = `<tbody class="divide-y divide-gray-100">`;
    SLOTS.forEach(s => {
      tbody += `<tr class="bg-white"><th class="text-left py-2 px-3 font-medium text-gray-700 sticky left-0 bg-white">P${s}</th>`;
      DAYS.forEach(d => {
        const cell = (m[d] && m[d][s]) ? m[d][s] : null;
        if (cell) {
          const chip = subjectChip(cell.subject);
          const entryId = cell.entry_id;
          tbody += buildCell(d,s,
            `<div class="rounded-lg border border-gray-200 bg-white px-2 py-1.5 cursor-grab shadow-sm hover:shadow transition"
                  draggable="true" ondragstart="onDragStart(event)" data-entry-id="${entryId}"
                  title="${cell.subject} • ${cell.teacher}">
               <span class="inline-flex items-center gap-2">
                 <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] ring-1 ${chip}">${cell.subject}</span>
                 <span class="text-gray-600 truncate max-w-[12rem]">— ${cell.teacher}</span>
               </span>
             </div>`);
        } else {
          tbody += buildCell(d,s, `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] border text-gray-600">Free</span>`);
        }
      });
      tbody += `</tr>`;
    });
    tbody += `</tbody>`;
    return `<table class="w-full text-[13px] sm:text-sm">${thead}${tbody}</table>`;
  }

  function renderTeacher(tid){
    const m = BY_TEACHER[tid] || {};
    let thead = `<thead class="bg-gray-100"><tr class="text-gray-700">
      <th class="text-left py-2 px-3 w-28">Period \\ Day</th>`;
    DAYS.forEach(d => thead += `<th class="text-left py-2 px-2 sm:px-3">${DAY_NAMES[d] || ('Day '+d)}</th>`);
    thead += `</tr></thead>`;

    let tbody = `<tbody class="divide-y divide-gray-100">`;
    SLOTS.forEach(s => {
      tbody += `<tr class="bg-white"><th class="text-left py-2 px-3 font-medium text-gray-700 sticky left-0 bg-white">P${s}</th>`;
      DAYS.forEach(d => {
        const cell = (m[d] && m[d][s]) ? m[d][s] : null;
        if (cell) {
          const chip = subjectChip(cell.subject);
          const entryId = cell.entry_id;
          tbody += buildCell(d,s,
            `<div class="rounded-lg border border-gray-200 bg-white px-2 py-1.5 cursor-grab shadow-sm hover:shadow transition"
                  draggable="true" ondragstart="onDragStart(event)" data-entry-id="${entryId}"
                  title="${cell.subject} • ${cell.group}">
               <span class="inline-flex items-center gap-2">
                 <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] ring-1 ${chip}">${cell.subject}</span>
                 <span class="text-gray-600 truncate max-w-[12rem]">— ${cell.group}</span>
               </span>
             </div>`);
        } else {
          tbody += buildCell(d,s, `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] border text-gray-600">Free</span>`);
        }
      });
      tbody += `</tr>`;
    });
    tbody += `</tbody>`;
    return `<table class="w-full text-[13px] sm:text-sm">${thead}${tbody}</table>`;
  }

  // ----- Public APIs for selects -----
  function showOnlyGroup(){
    const gid = document.getElementById('groupSel').value;
    document.getElementById('groupPanelTitle').textContent =
      `Group: ${document.querySelector(`#groupSel option[value="${gid}"]`)?.textContent || '—'}`;
    document.getElementById('groupPanelBody').innerHTML = renderGroup(gid);
  }
  function showOnlyTeacher(){
    const tid = document.getElementById('teacherSel').value;
    document.getElementById('teacherPanelTitle').textContent =
      `Teacher: ${document.querySelector(`#teacherSel option[value="${tid}"]`)?.textContent || '—'}`;
    document.getElementById('teacherPanelBody').innerHTML = renderTeacher(tid);
  }
  window.showOnlyGroup = showOnlyGroup;
  window.showOnlyTeacher = showOnlyTeacher;

  // ----- Init -----
  window.addEventListener('DOMContentLoaded', ()=>{
    activateTab('full'); // default
    // You can pre-render the selected group/teacher if desired:
    // if (document.getElementById('groupSel')) showOnlyGroup();
    // if (document.getElementById('teacherSel')) showOnlyTeacher();
  });
</script>
</body>
</html>
