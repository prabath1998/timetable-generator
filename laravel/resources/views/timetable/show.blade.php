<!doctype html>
<html class="h-full">

<head>
    <meta charset="utf-8">
    <title>Timetable #{{ $id }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full bg-gradient-to-br from-slate-50 via-indigo-50/30 to-slate-50 text-gray-900 antialiased">
    @php
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
        $dayNames = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
    @endphp

    <main class="min-h-screen p-4 md:p-8">
        <div class="max-w-[1600px] mx-auto space-y-6">

            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <a href="{{ route('tt.index') }}"
                        class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Back to All Timetables
                    </a>
                </div>

                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="space-y-2">
                        <h1 class="text-3xl md:text-4xl font-bold text-slate-900">Timetable #{{ $id }}</h1>
                        <div class="flex items-center gap-3 text-sm text-slate-600">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full font-medium
              @if($req->status === 'solved') bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200
              @elseif($req->status === 'failed') bg-red-100 text-red-700 ring-1 ring-red-200
              @else bg-amber-100 text-amber-700 ring-1 ring-amber-200 @endif">
                                <span class="w-1.5 h-1.5 rounded-full
                @if($req->status === 'solved') bg-emerald-500
                @elseif($req->status === 'failed') bg-red-500
                @else bg-amber-500 @endif"></span>
                                {{ ucfirst($req->status) }}
                            </span>
                            <span class="text-slate-500">•</span>
                            <span>Created
                                {{ \Illuminate\Support\Carbon::parse($req->created_at)->format('M d, Y H:i') }}</span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <a href="{{ route('tt.workload', $id) }}"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-300 text-slate-700 rounded-xl hover:bg-slate-50 hover:border-slate-400 transition-all shadow-sm font-medium text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            Teacher Workload
                        </a>
                        <a href="{{ route('tt.subjectUsage', ['id' => $id]) }}"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-300 text-slate-700 rounded-xl hover:bg-slate-50 hover:border-slate-400 transition-all shadow-sm font-medium text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 17v-6a2 2 0 012-2h2a2 2 0 012 2v6m4 4H5a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v14a2 2 0 01-2 2z" />
                            </svg>
                            Subject Usage
                        </a>
                        <button onclick="window.print()"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-300 text-slate-700 rounded-xl hover:bg-slate-50 hover:border-slate-400 transition-all shadow-sm font-medium text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Print
                        </button>
                    </div>
                </div>
            </div>

            <div
                class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-sm border border-slate-200/60 p-4 sticky top-4 z-20">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <button id="tab-full"
                            class="px-4 py-2 rounded-xl font-medium text-sm transition-all duration-200"
                            onclick="switchTab('full')">
                            Full Schedule
                        </button>
                        <button id="tab-group"
                            class="px-4 py-2 rounded-xl font-medium text-sm transition-all duration-200"
                            onclick="switchTab('group')">
                            By Group
                        </button>
                        <button id="tab-teacher"
                            class="px-4 py-2 rounded-xl font-medium text-sm transition-all duration-200"
                            onclick="switchTab('teacher')">
                            By Teacher
                        </button>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div id="groupFilter" class="hidden items-center gap-2">
                            <label class="text-sm font-medium text-slate-700">Group:</label>
                            <select id="groupSel" onchange="showOnlyGroup()"
                                class="px-3 py-2 rounded-xl border-slate-300 bg-white text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                                @foreach($groups as $g)
                                    <option value="{{ $g->id }}">{{ $g->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="teacherFilter" class="hidden items-center gap-2">
                            <label class="text-sm font-medium text-slate-700">Teacher:</label>
                            <select id="teacherSel" onchange="showOnlyTeacher()"
                                class="px-3 py-2 rounded-xl border-slate-300 bg-white text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                                @foreach($teachers as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <section id="panel-full" class="hidden space-y-6">
                @if(empty($byGroup))
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/60 p-12 text-center">
                        <svg class="w-16 h-16 mx-auto text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <p class="mt-4 text-slate-600">No timetable entries found.</p>
                    </div>
                @else
                    @foreach($groups as $g)
                        @php $m = $byGroup[$g->id] ?? []; @endphp
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden">
                            <div
                                class="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-transparent">
                                <h3 class="text-lg font-semibold text-slate-900">{{ $g->name }}</h3>
                                <span
                                    class="text-xs font-medium text-slate-500 bg-slate-100 px-3 py-1 rounded-full">Group</span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-[13px] sm:text-sm">
                                    <thead class="bg-slate-50 border-b border-slate-200">
                                        <tr class="text-slate-700">
                                            <th class="text-left py-3 px-4 font-semibold w-28">Period \ Day</th>
                                            @foreach($days as $d)
                                                <th class="text-left py-3 px-3 font-semibold">{{ $dayNames[$d] ?? 'Day ' . $d }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach($slots as $s)
                                            <tr class="hover:bg-slate-50/50 transition-colors">
                                                <th
                                                    class="text-left py-3 px-4 font-semibold text-slate-700 bg-slate-50/50 sticky left-0">
                                                    P{{ $s }}</th>
                                                @foreach($days as $d)
                                                    @php
                                                        $cell = $m[$d][$s] ?? null;
                                                        $tsId = $timeslotIdMap[$d][$s] ?? '';
                                                        $entryId = $cell['entry_id'] ?? null;
                                                        $chip = $cell ? $chipFor($cell['subject']) : '';
                                                    @endphp
                                                    <td class="align-top min-h-[52px] py-2 px-3" data-timeslot-id="{{ $tsId }}"
                                                        ondragover="onDragOver(event)" ondragleave="onDragLeave(event)"
                                                        ondrop="onDrop(event, {{ $id }})">
                                                        @if($cell && $entryId)
                                                            <div class="rounded-xl border border-slate-200 bg-gradient-to-br from-white to-slate-50/50 px-3 py-2 cursor-grab shadow-sm hover:shadow-md hover:border-indigo-300 transition-all duration-200"
                                                                draggable="true" ondragstart="onDragStart(event)"
                                                                data-entry-id="{{ $entryId }}"
                                                                title="{{ $cell['subject'] }} • {{ $cell['teacher'] }}">
                                                                <div class="flex flex-col gap-1">
                                                                    <span
                                                                        class="inline-flex items-center rounded-lg px-2 py-0.5 text-[11px] font-medium ring-1 {{ $chip }} w-fit">{{ $cell['subject'] }}</span>
                                                                    <span
                                                                        class="text-slate-600 text-xs truncate">{{ $cell['teacher'] }}</span>
                                                                </div>
                                                            </div>
                                                        @else
                                                            <div
                                                                class="inline-flex items-center px-3 py-1.5 rounded-lg text-[11px] border border-slate-200 text-slate-500 bg-slate-50/50">
                                                                Free</div>
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

            <section id="panel-group" class="hidden">
                @php $firstGroup = $groups->first(); @endphp
                <div id="groupPanelOne"
                    class="bg-white rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden">
                    <div
                        class="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-transparent">
                        <h3 id="groupPanelTitle" class="text-lg font-semibold text-slate-900">Group:
                            {{ $firstGroup?->name ?? '—' }}</h3>
                        <span class="text-xs font-medium text-slate-500 bg-slate-100 px-3 py-1 rounded-full">Group
                            View</span>
                    </div>
                    <div id="groupPanelBody" class="overflow-x-auto">
                        @if($firstGroup)
                            @php $m = $byGroup[$firstGroup->id] ?? []; @endphp
                            <table class="w-full text-[13px] sm:text-sm">
                                <thead class="bg-slate-50 border-b border-slate-200">
                                    <tr class="text-slate-700">
                                        <th class="text-left py-3 px-4 font-semibold w-28">Period \ Day</th>
                                        @foreach($days as $d)
                                            <th class="text-left py-3 px-3 font-semibold">{{ $dayNames[$d] ?? 'Day ' . $d }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($slots as $s)
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <th
                                                class="text-left py-3 px-4 font-semibold text-slate-700 bg-slate-50/50 sticky left-0">
                                                P{{ $s }}</th>
                                            @foreach($days as $d)
                                                @php
                                                    $cell = $m[$d][$s] ?? null;
                                                    $tsId = $timeslotIdMap[$d][$s] ?? '';
                                                    $entryId = $cell['entry_id'] ?? null;
                                                    $chip = $cell ? $chipFor($cell['subject']) : '';
                                                  @endphp
                                                <td class="align-top min-h-[52px] py-2 px-3" data-timeslot-id="{{ $tsId }}"
                                                    ondragover="onDragOver(event)" ondragleave="onDragLeave(event)"
                                                    ondrop="onDrop(event, {{ $id }})">
                                                    @if($cell && $entryId)
                                                        <div class="rounded-xl border border-slate-200 bg-gradient-to-br from-white to-slate-50/50 px-3 py-2 cursor-grab shadow-sm hover:shadow-md hover:border-indigo-300 transition-all duration-200"
                                                            draggable="true" ondragstart="onDragStart(event)"
                                                            data-entry-id="{{ $entryId }}"
                                                            title="{{ $cell['subject'] }} • {{ $cell['teacher'] }}">
                                                            <div class="flex flex-col gap-1">
                                                                <span
                                                                    class="inline-flex items-center rounded-lg px-2 py-0.5 text-[11px] font-medium ring-1 {{ $chip }} w-fit">{{ $cell['subject'] }}</span>
                                                                <span
                                                                    class="text-slate-600 text-xs truncate">{{ $cell['teacher'] }}</span>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div
                                                            class="inline-flex items-center px-3 py-1.5 rounded-lg text-[11px] border border-slate-200 text-slate-500 bg-slate-50/50">
                                                            Free</div>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="p-12 text-center text-slate-600">No groups found.</div>
                        @endif
                    </div>
                </div>
            </section>

            <!-- PER TEACHER (single selected) -->
            <section id="panel-teacher" class="hidden">
                @php $firstTeacher = $teachers->first(); @endphp
                <div id="teacherPanelOne"
                    class="bg-white rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden">
                    <div
                        class="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-transparent">
                        <h3 id="teacherPanelTitle" class="text-lg font-semibold text-slate-900">Teacher:
                            {{ $firstTeacher?->name ?? '—' }}</h3>
                        <span class="text-xs font-medium text-slate-500 bg-slate-100 px-3 py-1 rounded-full">Teacher
                            View</span>
                    </div>
                    <div id="teacherPanelBody" class="overflow-x-auto">
                        @if($firstTeacher)
                            @php $m = $byTeacher[$firstTeacher->id] ?? []; @endphp
                            <table class="w-full text-[13px] sm:text-sm">
                                <thead class="bg-slate-50 border-b border-slate-200">
                                    <tr class="text-slate-700">
                                        <th class="text-left py-3 px-4 font-semibold w-28">Period \ Day</th>
                                        @foreach($days as $d)
                                            <th class="text-left py-3 px-3 font-semibold">{{ $dayNames[$d] ?? 'Day ' . $d }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($slots as $s)
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <th
                                                class="text-left py-3 px-4 font-semibold text-slate-700 bg-slate-50/50 sticky left-0">
                                                P{{ $s }}</th>
                                            @foreach($days as $d)
                                                @php
                                                    $cell = $m[$d][$s] ?? null;
                                                    $tsId = $timeslotIdMap[$d][$s] ?? '';
                                                    $entryId = $cell['entry_id'] ?? null;
                                                    $chip = $cell ? $chipFor($cell['subject']) : '';
                                                  @endphp
                                                <td class="align-top min-h-[52px] py-2 px-3" data-timeslot-id="{{ $tsId }}"
                                                    ondragover="onDragOver(event)" ondragleave="onDragLeave(event)"
                                                    ondrop="onDrop(event, {{ $id }})">
                                                    @if($cell && $entryId)
                                                        <div class="rounded-xl border border-slate-200 bg-gradient-to-br from-white to-slate-50/50 px-3 py-2 cursor-grab shadow-sm hover:shadow-md hover:border-indigo-300 transition-all duration-200"
                                                            draggable="true" ondragstart="onDragStart(event)"
                                                            data-entry-id="{{ $entryId }}"
                                                            title="{{ $cell['subject'] }} • {{ $cell['group'] }}">
                                                            <div class="flex flex-col gap-1">
                                                                <span
                                                                    class="inline-flex items-center rounded-lg px-2 py-0.5 text-[11px] font-medium ring-1 {{ $chip }} w-fit">{{ $cell['subject'] }}</span>
                                                                <span
                                                                    class="text-slate-600 text-xs truncate">{{ $cell['group'] }}</span>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div
                                                            class="inline-flex items-center px-3 py-1.5 rounded-lg text-[11px] border border-slate-200 text-slate-500 bg-slate-50/50">
                                                            Free</div>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="p-12 text-center text-slate-600">No teachers found.</div>
                        @endif
                    </div>
                </div>
            </section>
        </div>
    </main>

    <script>
        const REQ_ID = {{ (int) $id }};
        const BY_GROUP = @json($byGroup ?? []);
        const BY_TEACHER = @json($byTeacher ?? []);
        const DAYS = @json($days ?? []);
        const SLOTS = @json($slots ?? []);
        const TS_MAP = @json($timeslotIdMap ?? []);
        const DAY_NAMES = { 1: 'Mon', 2: 'Tue', 3: 'Wed', 4: 'Thu', 5: 'Fri', 6: 'Sat', 7: 'Sun' };

        function activateTab(tab) {
            document.querySelectorAll('button[id^="tab-"]').forEach(b => {
                b.classList.remove('bg-indigo-600', 'text-white', 'shadow-lg', 'shadow-indigo-200');
                b.classList.add('text-slate-600', 'hover:bg-slate-100');
            });
            const active = document.getElementById('tab-' + tab);
            active?.classList.remove('text-slate-600', 'hover:bg-slate-100');
            active?.classList.add('bg-indigo-600', 'text-white', 'shadow-lg', 'shadow-indigo-200');

            document.getElementById('groupFilter')?.classList.toggle('hidden', tab !== 'group');
            document.getElementById('teacherFilter')?.classList.toggle('hidden', tab !== 'teacher');

            document.querySelectorAll('section[id^="panel-"]').forEach(p => p.classList.add('hidden'));
            document.getElementById('panel-' + tab)?.classList.remove('hidden');
        }
        function switchTab(tab) { activateTab(tab); }
        window.switchTab = switchTab;

        function subjectChip(subject) {
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
            let h = 0; for (let i = 0; i < subject.length; i++) { h = (h * 33 + subject.charCodeAt(i)) >>> 0; }
            return palette[h % palette.length];
        }

        const csrf = () => document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let dragEntryId = null;

        function onDragStart(ev) {
            const el = ev.target.closest('[data-entry-id]');
            dragEntryId = el?.dataset.entryId;
            ev.dataTransfer.setData('text/plain', dragEntryId || '');
            ev.dataTransfer.effectAllowed = 'move';
            el?.classList.add('opacity-50');
        }
        function onDragOver(ev) {
            ev.preventDefault();
            ev.currentTarget.classList.add('ring-2', 'ring-indigo-400', 'ring-offset-2', 'bg-indigo-50/30');
        }
        function onDragLeave(ev) {
            ev.currentTarget.classList.remove('ring-2', 'ring-indigo-400', 'ring-offset-2', 'bg-indigo-50/30');
        }
        async function onDrop(ev, reqId) {
            ev.preventDefault();
            const cell = ev.currentTarget;
            cell.classList.remove('ring-2', 'ring-indigo-400', 'ring-offset-2', 'bg-indigo-50/30');

            const toTsId = cell.dataset.timeslotId;
            if (!dragEntryId || !toTsId) return;

            const occupant = cell.querySelector('[data-entry-id]');
            let body = { entry_id: dragEntryId, to_timeslot_id: toTsId };
            if (occupant) body.swap_with_entry_id = occupant.dataset.entryId;

            try {
                const res = await fetch(`${location.origin}/timetable/${reqId}/move`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
                    body: JSON.stringify(body)
                });
                const data = await res.json();
                if (!res.ok) {
                    if (data?.needs_swap) {
                        if (confirm('Destination occupied. Swap lessons?')) {
                            body.swap_with_entry_id = data.swap_candidate_id;
                            const res2 = await fetch(`${location.origin}/timetable/${reqId}/move`, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
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
        window.onDragStart = onDragStart;
        window.onDragOver = onDragOver;
        window.onDragLeave = onDragLeave;
        window.onDrop = onDrop;

        function buildCell(d, s, contentHtml) {
            const tsId = (TS_MAP[d] && TS_MAP[d][s]) ? TS_MAP[d][s] : '';
            return `<td class="align-top min-h-[52px] py-2 px-3"
              data-timeslot-id="${tsId}"
              ondragover="onDragOver(event)"
              ondragleave="onDragLeave(event)"
              ondrop="onDrop(event, ${REQ_ID})">${contentHtml}</td>`;
        }

        function renderGroup(gid) {
            const m = BY_GROUP[gid] || {};
            let thead = `<thead class="bg-slate-50 border-b border-slate-200"><tr class="text-slate-700">
      <th class="text-left py-3 px-4 font-semibold w-28">Period \\ Day</th>`;
            DAYS.forEach(d => thead += `<th class="text-left py-3 px-3 font-semibold">${DAY_NAMES[d] || ('Day ' + d)}</th>`);
            thead += `</tr></thead>`;

            let tbody = `<tbody class="divide-y divide-slate-100">`;
            SLOTS.forEach(s => {
                tbody += `<tr class="hover:bg-slate-50/50 transition-colors"><th class="text-left py-3 px-4 font-semibold text-slate-700 bg-slate-50/50 sticky left-0">P${s}</th>`;
                DAYS.forEach(d => {
                    const cell = (m[d] && m[d][s]) ? m[d][s] : null;
                    if (cell) {
                        const chip = subjectChip(cell.subject);
                        const entryId = cell.entry_id;
                        tbody += buildCell(d, s,
                            `<div class="rounded-xl border border-slate-200 bg-gradient-to-br from-white to-slate-50/50 px-3 py-2 cursor-grab shadow-sm hover:shadow-md hover:border-indigo-300 transition-all duration-200"
                  draggable="true" ondragstart="onDragStart(event)" data-entry-id="${entryId}"
                  title="${cell.subject} • ${cell.teacher}">
               <div class="flex flex-col gap-1">
                 <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-[11px] font-medium ring-1 ${chip} w-fit">${cell.subject}</span>
                 <span class="text-slate-600 text-xs truncate">${cell.teacher}</span>
               </div>
             </div>`);
                    } else {
                        tbody += buildCell(d, s, `<div class="inline-flex items-center px-3 py-1.5 rounded-lg text-[11px] border border-slate-200 text-slate-500 bg-slate-50/50">Free</div>`);
                    }
                });
                tbody += `</tr>`;
            });
            tbody += `</tbody>`;
            return `<table class="w-full text-[13px] sm:text-sm">${thead}${tbody}</table>`;
        }

        function renderTeacher(tid) {
            const m = BY_TEACHER[tid] || {};
            let thead = `<thead class="bg-slate-50 border-b border-slate-200"><tr class="text-slate-700">
      <th class="text-left py-3 px-4 font-semibold w-28">Period \\ Day</th>`;
            DAYS.forEach(d => thead += `<th class="text-left py-3 px-3 font-semibold">${DAY_NAMES[d] || ('Day ' + d)}</th>`);
            thead += `</tr></thead>`;

            let tbody = `<tbody class="divide-y divide-slate-100">`;
            SLOTS.forEach(s => {
                tbody += `<tr class="hover:bg-slate-50/50 transition-colors"><th class="text-left py-3 px-4 font-semibold text-slate-700 bg-slate-50/50 sticky left-0">P${s}</th>`;
                DAYS.forEach(d => {
                    const cell = (m[d] && m[d][s]) ? m[d][s] : null;
                    if (cell) {
                        const chip = subjectChip(cell.subject);
                        const entryId = cell.entry_id;
                        tbody += buildCell(d, s,
                            `<div class="rounded-xl border border-slate-200 bg-gradient-to-br from-white to-slate-50/50 px-3 py-2 cursor-grab shadow-sm hover:shadow-md hover:border-indigo-300 transition-all duration-200"
                  draggable="true" ondragstart="onDragStart(event)" data-entry-id="${entryId}"
                  title="${cell.subject} • ${cell.group}">
               <div class="flex flex-col gap-1">
                 <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-[11px] font-medium ring-1 ${chip} w-fit">${cell.subject}</span>
                 <span class="text-slate-600 text-xs truncate">${cell.group}</span>
               </div>
             </div>`);
                    } else {
                        tbody += buildCell(d, s, `<div class="inline-flex items-center px-3 py-1.5 rounded-lg text-[11px] border border-slate-200 text-slate-500 bg-slate-50/50">Free</div>`);
                    }
                });
                tbody += `</tr>`;
            });
            tbody += `</tbody>`;
            return `<table class="w-full text-[13px] sm:text-sm">${thead}${tbody}</table>`;
        }

        function showOnlyGroup() {
            const gid = document.getElementById('groupSel').value;
            document.getElementById('groupPanelTitle').textContent =
                `Group: ${document.querySelector(`#groupSel option[value="${gid}"]`)?.textContent || '—'}`;
            document.getElementById('groupPanelBody').innerHTML = renderGroup(gid);
        }
        function showOnlyTeacher() {
            const tid = document.getElementById('teacherSel').value;
            document.getElementById('teacherPanelTitle').textContent =
                `Teacher: ${document.querySelector(`#teacherSel option[value="${tid}"]`)?.textContent || '—'}`;
            document.getElementById('teacherPanelBody').innerHTML = renderTeacher(tid);
        }
        window.showOnlyGroup = showOnlyGroup;
        window.showOnlyTeacher = showOnlyTeacher;

        // ----- Init -----
        window.addEventListener('DOMContentLoaded', () => {
            activateTab('full'); // default
        });
    </script>
</body>

</html>
