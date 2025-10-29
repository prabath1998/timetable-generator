<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Timetable #{{ $id }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <style>
    body{font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial; margin:24px; color:#111}
    .tabs{display:flex; gap:8px; margin-bottom:16px}
    .tab{padding:8px 12px; border:1px solid #d1d5db; border-radius:999px; text-decoration:none; color:#111; background:#fff; cursor:pointer}
    .tab.active{background:#111; color:#fff; border-color:#111}
    .bar{display:flex; gap:12px; align-items:center; margin-bottom:12px}
    select{padding:8px 10px; border:1px solid #d1d5db; border-radius:8px}
    table{width:100%; border-collapse:collapse; table-layout:fixed}
    th,td{border:1px solid #e5e7eb; padding:10px; vertical-align:top}
    th{background:#f9fafb}
    .card{border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin-bottom:16px}
    .muted{color:#6b7280}
    .pill{display:inline-block; padding:2px 8px; border:1px solid #e5e7eb; border-radius:999px; font-size:12px}
    .gridwrap{overflow:auto}
    /* DnD */
    .cardlet{background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:6px 8px; cursor:grab}
    .tt-cell{position:relative; min-height:58px}
    .drop-ok{outline:2px dashed #10b981;}
    .drop-bad{outline:2px dashed #ef4444;}
    .toast{position:fixed; top:16px; right:16px; background:#111; color:#fff; padding:10px 12px; border-radius:8px}
  </style>

  <script>
    function switchTab(tab){
      document.querySelectorAll('.tab').forEach(el=>el.classList.remove('active'));
      document.getElementById('tab-'+tab).classList.add('active');
      document.querySelectorAll('.panel').forEach(el=>el.style.display='none');
      document.getElementById('panel-'+tab).style.display='block';
    }
    function choose(selId, panelClass){
      const val = document.getElementById(selId).value;
      document.querySelectorAll('.'+panelClass).forEach(el=>el.style.display='none');
      const el = document.getElementById(panelClass+'-'+val);
      if(el) el.style.display='block';
    }
    window.addEventListener('DOMContentLoaded', ()=>{
      switchTab('full');
      const gs = document.getElementById('groupSel'); if(gs) choose('groupSel','groupPanel');
      const ts = document.getElementById('teacherSel'); if(ts) choose('teacherSel','teacherPanel');
    });

    const csrf = () => document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    function showToast(msg, ok=true){
      const t=document.createElement('div');
      t.className='toast'; t.textContent=msg;
      t.style.background = ok ? '#111' : '#b91c1c';
      document.body.appendChild(t);
      setTimeout(()=>{ t.remove(); }, 2200);
    }

    // DnD handlers
    let dragEntryId = null;
    let dragOriginCell = null;

    function onDragStart(ev){
      const el = ev.target.closest('.cardlet');
      dragEntryId = el?.dataset.entryId;
      dragOriginCell = el?.closest('.tt-cell');
      ev.dataTransfer.setData('text/plain', dragEntryId);
      ev.dataTransfer.effectAllowed = 'move';
    }

    function onDragOver(ev){
      ev.preventDefault(); // allow drop
      const cell = ev.currentTarget;
      cell.classList.add('drop-ok');
    }

    function onDragLeave(ev){
      ev.currentTarget.classList.remove('drop-ok','drop-bad');
    }

    async function onDrop(ev, reqId){
      ev.preventDefault();
      const targetCell = ev.currentTarget;
      targetCell.classList.remove('drop-ok','drop-bad');
      const toTsId = targetCell.dataset.timeslotId;
      const entryId = dragEntryId;

      if (!entryId || !toTsId) return;

      // Check if target has another entry
      const targetEntryEl = targetCell.querySelector('.cardlet');
      let body = { entry_id: entryId, to_timeslot_id: toTsId };

      if (targetEntryEl) {
        // Attempt swap
        body.swap_with_entry_id = targetEntryEl.dataset.entryId;
      }

      try{
        const res = await fetch(`${location.origin}/timetable/${reqId}/move`, {
          method:'POST',
          headers: {
            'Content-Type':'application/json',
            'X-CSRF-TOKEN': csrf()
          },
          body: JSON.stringify(body)
        });
        const data = await res.json();

        if (!res.ok) {
          if (data.needs_swap) {
            if (confirm('Destination occupied. Swap lessons?')) {
              body.swap_with_entry_id = data.swap_candidate_id;
              const res2 = await fetch(`${location.origin}/timetable/${reqId}/move`, {
                method:'POST',
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify(body)
              });
              const data2 = await res2.json();
              if (res2.ok && data2.ok) {
                location.reload();
              } else {
                showToast(data2.message || 'Swap failed', false);
              }
            }
          } else {
            showToast(data.message || 'Move not allowed', false);
          }
          return;
        }

        if (data.ok) {
          location.reload();
        } else {
          showToast(data.message || 'Move failed', false);
        }
      } catch (e){
        showToast('Network error', false);
      } finally {
        dragEntryId = null;
        dragOriginCell = null;
      }
    }
  </script>
</head>
<body>
  <a href="{{ route('tt.index') }}" style="text-decoration:none">&larr; Back</a>
  <h1 style="margin:8px 0 4px;">Timetable #{{ $id }}</h1>
  <p class="muted" style="margin-top:0;">
    Status: {{ $req->status }} • Requested at {{ \Illuminate\Support\Carbon::parse($req->created_at)->format('Y-m-d H:i') }}
  </p>

  <div class="tabs">
    <button id="tab-full" class="tab" onclick="switchTab('full')">Full (by Group)</button>
    <button id="tab-group" class="tab" onclick="switchTab('group')">Per Group</button>
    <button id="tab-teacher" class="tab" onclick="switchTab('teacher')">Per Teacher</button>
  </div>

  <a href="{{ route('tt.workload', $id) }}" class="tab" style="margin-bottom: 5px">View teacher workload</a>

  {{-- FULL GRID (all groups, one below another) --}}
  <div id="panel-full" class="panel" style="display:none">
    @if(empty($byGroup))
      <div class="card">No entries found.</div>
    @else
      @foreach($groups as $g)
        @php $m = $byGroup[$g->id] ?? []; @endphp
        <div class="card">
          <h3 style="margin:0 0 8px;">Group: {{ $g->name }}</h3>
          <div class="gridwrap">
            <table>
              <thead>
                <tr>
                  <th style="width:120px;">Day \ Slot</th>
                  @foreach($slots as $s) <th>Period {{ $s }}</th> @endforeach
                </tr>
              </thead>
              <tbody>
                @foreach($days as $d)
                  <tr>
                    <th>
                      @php $dayNames=[1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',7=>'Sun']; @endphp
                      {{ $dayNames[$d] ?? 'Day '.$d }}
                    </th>
                    @foreach($slots as $s)
                      @php
                        $cell = $m[$d][$s] ?? null;
                        $tsId = \DB::table('timeslots')->where(['day_of_week'=>$d,'slot_index'=>$s])->value('id');
                        $entryId = null;
                        if ($cell) {
                          $entryId = \DB::table('timetable_entries as e')
                            ->join('timeslots as ts','ts.id','=','e.timeslot_id')
                            ->where('e.timetable_request_id',$id)
                            ->where('e.group_id', $g->id)
                            ->where('ts.day_of_week',$d)->where('ts.slot_index',$s)
                            ->value('e.id');
                        }
                      @endphp
                      <td class="tt-cell"
                          data-timeslot-id="{{ $tsId }}"
                          ondragover="onDragOver(event)"
                          ondragleave="onDragLeave(event)"
                          ondrop="onDrop(event, {{ $id }})">
                        @if($cell && $entryId)
                          <div class="cardlet"
                               draggable="true"
                               ondragstart="onDragStart(event)"
                               data-entry-id="{{ $entryId }}">
                            <div><strong>{{ $cell['subject'] }}</strong></div>
                            <div class="muted">{{ $cell['teacher'] }}</div>
                          </div>
                        @else
                          <span class="pill muted">Free</span>
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
  </div>

  {{-- PER GROUP (choose one) --}}
  <div id="panel-group" class="panel" style="display:none">
    <div class="bar">
      <label>Group:&nbsp;
        <select id="groupSel" onchange="choose('groupSel','groupPanel')">
          @foreach($groups as $g)
            <option value="{{ $g->id }}">{{ $g->name }}</option>
          @endforeach
        </select>
      </label>
    </div>

    @foreach($groups as $g)
      @php $m = $byGroup[$g->id] ?? []; @endphp
      <div id="groupPanel-{{ $g->id }}" class="groupPanel card" style="display:none">
        <h3 style="margin:0 0 8px;">Group: {{ $g->name }}</h3>
        <div class="gridwrap">
          <table>
            <thead>
              <tr>
                <th style="width:120px;">Day \ Slot</th>
                @foreach($slots as $s) <th>Period {{ $s }}</th> @endforeach
              </tr>
            </thead>
            <tbody>
              @foreach($days as $d)
                <tr>
                  <th>
                    @php $dayNames=[1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',7=>'Sun']; @endphp
                    {{ $dayNames[$d] ?? 'Day '.$d }}
                  </th>
                  @foreach($slots as $s)
                    @php
                      $cell = $m[$d][$s] ?? null;
                      $tsId = \DB::table('timeslots')->where(['day_of_week'=>$d,'slot_index'=>$s])->value('id');
                      $entryId = null;
                      if ($cell) {
                        $entryId = \DB::table('timetable_entries as e')
                          ->join('timeslots as ts','ts.id','=','e.timeslot_id')
                          ->where('e.timetable_request_id',$id)
                          ->where('e.group_id', $g->id)
                          ->where('ts.day_of_week',$d)->where('ts.slot_index',$s)
                          ->value('e.id');
                      }
                    @endphp
                    <td class="tt-cell"
                        data-timeslot-id="{{ $tsId }}"
                        ondragover="onDragOver(event)"
                        ondragleave="onDragLeave(event)"
                        ondrop="onDrop(event, {{ $id }})">
                      @if($cell && $entryId)
                        <div class="cardlet"
                             draggable="true"
                             ondragstart="onDragStart(event)"
                             data-entry-id="{{ $entryId }}">
                          <div><strong>{{ $cell['subject'] }}</strong></div>
                          <div class="muted">{{ $cell['teacher'] }}</div>
                        </div>
                      @else
                        <span class="pill muted">Free</span>
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
  </div>

  {{-- PER TEACHER (choose one) --}}
  <div id="panel-teacher" class="panel" style="display:none">
    <div class="bar">
      <label>Teacher:&nbsp;
        <select id="teacherSel" onchange="choose('teacherSel','teacherPanel')">
          @foreach($teachers as $t)
            <option value="{{ $t->id }}">{{ $t->name }}</option>
          @endforeach
        </select>
      </label>
    </div>

    @foreach($teachers as $t)
      @php $m = $byTeacher[$t->id] ?? []; @endphp
      <div id="teacherPanel-{{ $t->id }}" class="teacherPanel card" style="display:none">
        <h3 style="margin:0 0 8px;">Teacher: {{ $t->name }}</h3>
        <div class="gridwrap">
          <table>
            <thead>
              <tr>
                <th style="width:120px;">Day \ Slot</th>
                @foreach($slots as $s) <th>Period {{ $s }}</th> @endforeach
              </tr>
            </thead>
            <tbody>
              @foreach($days as $d)
                <tr>
                  <th>
                    @php $dayNames=[1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',7=>'Sun']; @endphp
                    {{ $dayNames[$d] ?? 'Day '.$d }}
                  </th>
                  @foreach($slots as $s)
                    @php
                      $cell = $m[$d][$s] ?? null;
                      $tsId = \DB::table('timeslots')->where(['day_of_week'=>$d,'slot_index'=>$s])->value('id');
                      $entryId = null;
                      if ($cell) {
                        // For teacher panel, resolve by teacher_id + timeslot
                        $entryId = \DB::table('timetable_entries as e')
                          ->join('timeslots as ts','ts.id','=','e.timeslot_id')
                          ->where('e.timetable_request_id',$id)
                          ->where('e.teacher_id', $t->id)
                          ->where('ts.day_of_week',$d)->where('ts.slot_index',$s)
                          ->value('e.id');
                      }
                    @endphp
                    <td class="tt-cell"
                        data-timeslot-id="{{ $tsId }}"
                        ondragover="onDragOver(event)"
                        ondragleave="onDragLeave(event)"
                        ondrop="onDrop(event, {{ $id }})">
                      @if($cell && $entryId)
                        <div class="cardlet"
                             draggable="true"
                             ondragstart="onDragStart(event)"
                             data-entry-id="{{ $entryId }}">
                          <div><strong>{{ $cell['subject'] }}</strong></div>
                          <div class="muted">{{ $cell['group'] }}</div>
                        </div>
                      @else
                        <span class="pill muted">Free</span>
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
  </div>
</body>
</html>
