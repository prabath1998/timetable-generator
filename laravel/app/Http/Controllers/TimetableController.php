<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\FacadesDB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class TimetableController extends Controller
{
    public function index()
    {
        $requests = DB::table('timetable_requests')
            ->orderByDesc('id')->limit(10)->get();

        return view('timetable.index', compact('requests'));
    }

    public function generate(Request $request)
    {
        $groups = DB::table('groups')->get();
        $timeslots = DB::table('timeslots')->orderBy('day_of_week')->orderBy('slot_index')->get();
        $assignments = DB::table('group_teacher')
            ->join('teachers', 'teachers.id', '=', 'group_teacher.teacher_id')
            ->join('subjects', 'subjects.id', '=', 'group_teacher.subject_id')
            ->select(
                'group_teacher.group_id',
                'group_teacher.teacher_id',
                'group_teacher.subject_id',
                'subjects.weekly_slots'
            )
            ->get();

        $soft = [
            'avoid_last_period_weight' => (int) $request->input('avoid_last_period_weight', 0),
        ];

        $payload = [
            'timeslots'   => $timeslots->map(fn ($t) => [
                'id' => $t->id, 'day' => $t->day_of_week, 'slot' => $t->slot_index
            ])->all(),
            'groups'      => $groups->map(fn ($g) => ['id' => $g->id])->all(),
            'teaching'    => $assignments->map(fn ($a) => [
                'group_id' => $a->group_id,
                'teacher_id' => $a->teacher_id,
                'subject_id' => $a->subject_id,
                'weekly_slots' => $a->weekly_slots
            ])->all(),
            'soft'        => $soft,
        ];

        $reqId = DB::table('timetable_requests')->insertGetId([
            'constraints' => json_encode($soft),
            'status' => 'pending',
            'created_at' => now(),'updated_at' => now()
        ]);

        try {

            $solverUrl = rtrim(config('app.solver_url', env('SOLVER_URL')), '/');
            $res = Http::timeout(120)->post($solverUrl.'/solve', $payload);

            if (!$res->ok()) {
                DB::table('timetable_requests')->where('id', $reqId)->update([
                    'status' => 'failed','error' => $res->body(),'updated_at' => now()
                ]);
                return redirect()->route('tt.index')->with('error', 'Solver error: '.$res->body());
            }

            $data = $res->json();

            DB::transaction(function () use ($reqId, $data) {
                foreach ($data['assignments'] as $a) {
                    DB::table('timetable_entries')->insert([
                        'timetable_request_id' => $reqId,
                        'group_id' => $a['group_id'],
                        'teacher_id' => $a['teacher_id'],
                        'subject_id' => $a['subject_id'],
                        'timeslot_id' => $a['timeslot_id'],
                        'created_at' => now(),'updated_at' => now()
                    ]);
                }
                DB::table('timetable_requests')->where('id', $reqId)->update([
                    'status' => 'solved','updated_at' => now()
                ]);
            });

            return redirect()->route('tt.show', $reqId)->with('success', 'Timetable generated.');
        } catch (\Throwable $e) {
            DB::table('timetable_requests')->where('id', $reqId)->update([
                'status' => 'failed','error' => $e->getMessage(),'updated_at' => now()
            ]);
            return redirect()->route('tt.index')->with('error', 'Error: '.$e->getMessage());
        }
    }

    public function show($id)
    {
        $days  = range(1, 5);
        $slots = range(1, 8);

        // timeslot map once
        $timeslots = DB::table('timeslots')->get(['id','day_of_week','slot_index']);
        $timeslotIdMap = [];
        foreach ($timeslots as $t) {
            $timeslotIdMap[$t->day_of_week][$t->slot_index] = $t->id;
        }

        $rows = DB::table('timetable_entries as e')
            ->join('timeslots as ts', 'ts.id', '=', 'e.timeslot_id')
            ->join('subjects as s', 's.id', '=', 'e.subject_id')
            ->join('teachers as t', 't.id', '=', 'e.teacher_id')
            ->join('groups as g', 'g.id', '=', 'e.group_id')
            ->where('e.timetable_request_id', $id)
            ->get([
                'e.id as entry_id','e.group_id','e.teacher_id',
                'ts.day_of_week','ts.slot_index',
                's.name as subject','t.name as teacher','g.name as group',
            ]);

        $byGroup = [];
        $byTeacher = [];
        foreach ($rows as $r) {
            $byGroup[$r->group_id][$r->day_of_week][$r->slot_index] = [
                'subject' => $r->subject,'teacher' => $r->teacher,'entry_id' => $r->entry_id,
            ];
            $byTeacher[$r->teacher_id][$r->day_of_week][$r->slot_index] = [
                'subject' => $r->subject,'group' => $r->group,'entry_id' => $r->entry_id,
            ];
        }

        $req      = DB::table('timetable_requests')->find($id);
        $groups   = DB::table('groups')->orderBy('name')->get();
        $teachers = DB::table('teachers')->orderBy('name')->get();

        return view('timetable.show', compact(
            'id',
            'req',
            'groups',
            'teachers',
            'days',
            'slots',
            'byGroup',
            'byTeacher',
            'timeslotIdMap'
        ));
    }


    public function move(Request $request, $id)
    {
        $entryId = (int) $request->input('entry_id');
        $toTsId  = (int) $request->input('to_timeslot_id');
        $swapWith = $request->input('swap_with_entry_id') ? (int) $request->input('swap_with_entry_id') : null;

        $entry = DB::table('timetable_entries')->where('id', $entryId)
            ->where('timetable_request_id', $id)->first();
        if (!$entry) {
            return response()->json(['ok' => false,'message' => 'Entry not found'], 404);
        }

        $hasGroupClash = DB::table('timetable_entries')
            ->where('timetable_request_id', $id)
            ->where('group_id', $entry->group_id)
            ->where('timeslot_id', $toTsId)
            ->where('id', '!=', $entryId)
            ->exists();

        $hasTeacherClash = DB::table('timetable_entries')
            ->where('timetable_request_id', $id)
            ->where('teacher_id', $entry->teacher_id)
            ->where('timeslot_id', $toTsId)
            ->where('id', '!=', $entryId)
            ->exists();

        $destEntry = DB::table('timetable_entries')
            ->where('timetable_request_id', $id)
            ->where('timeslot_id', $toTsId)
            ->first();

        if ($destEntry && !$swapWith) {
            return response()->json([
                'ok' => false,
                'needs_swap' => true,
                'swap_candidate_id' => $destEntry->id,
                'message' => 'Destination occupied. Do you want to swap?'
            ], 409);
        }

        if ($swapWith) {
            $a = $entry;
            $b = DB::table('timetable_entries')
                ->where('id', $swapWith)
                ->where('timetable_request_id', $id)->first();
            if (!$b) {
                return response()->json(['ok' => false,'message' => 'Swap target not found'], 404);
            }

            $a_group_clash = DB::table('timetable_entries')
                ->where('timetable_request_id', $id)
                ->where('group_id', $a->group_id)
                ->where('timeslot_id', $b->timeslot_id)
                ->whereNotIn('id', [$a->id, $b->id])
                ->exists();
            $a_teacher_clash = DB::table('timetable_entries')
                ->where('timetable_request_id', $id)
                ->where('teacher_id', $a->teacher_id)
                ->where('timeslot_id', $b->timeslot_id)
                ->whereNotIn('id', [$a->id, $b->id])
                ->exists();

            $b_group_clash = DB::table('timetable_entries')
                ->where('timetable_request_id', $id)
                ->where('group_id', $b->group_id)
                ->where('timeslot_id', $a->timeslot_id)
                ->whereNotIn('id', [$a->id, $b->id])
                ->exists();
            $b_teacher_clash = DB::table('timetable_entries')
                ->where('timetable_request_id', $id)
                ->where('teacher_id', $b->teacher_id)
                ->where('timeslot_id', $a->timeslot_id)
                ->whereNotIn('id', [$a->id, $b->id])
                ->exists();

            if ($a_group_clash || $a_teacher_clash || $b_group_clash || $b_teacher_clash) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Swap would violate constraints (group/teacher clash).'
                ], 422);
            }

            DB::transaction(function () use ($a, $b) {
                DB::table('timetable_entries')->where('id', $a->id)->update(['timeslot_id' => $b->timeslot_id, 'updated_at' => now()]);
                DB::table('timetable_entries')->where('id', $b->id)->update(['timeslot_id' => $a->timeslot_id, 'updated_at' => now()]);
            });

            return response()->json(['ok' => true,'swapped' => true]);
        }

        if ($hasGroupClash || $hasTeacherClash) {
            return response()->json([
                'ok' => false,
                'message' => 'Move would violate constraints (group or teacher already busy).'
            ], 422);
        }

        DB::table('timetable_entries')->where('id', $entryId)->update([
            'timeslot_id' => $toTsId, 'updated_at' => now()
        ]);

        return response()->json(['ok' => true]);
    }

    public function validateNow(Request $request, $id)
    {
        $solverUrl = rtrim(config('app.solver_url', env('SOLVER_URL')), '/');

        $timeslots = DB::table('timeslots')->orderBy('day_of_week')->orderBy('slot_index')->get();
        $groups    = DB::table('groups')->get();
        $assigns   = DB::table('group_teacher')
            ->join('subjects', 'subjects.id', '=', 'group_teacher.subject_id')
            ->select('group_teacher.group_id', 'group_teacher.teacher_id', 'group_teacher.subject_id', 'subjects.weekly_slots')
            ->get();
        $locked    = DB::table('timetable_entries')->where('timetable_request_id', $id)
            ->select('group_id', 'teacher_id', 'subject_id', 'timeslot_id')->get();

        $payload = [
            'timeslots' => $timeslots->map(fn ($t) => ['id' => $t->id,'day' => $t->day_of_week,'slot' => $t->slot_index])->all(),
            'groups'    => $groups->map(fn ($g) => ['id' => $g->id])->all(),
            'teaching'  => $assigns->map(fn ($a) => [
                'group_id' => $a->group_id,'teacher_id' => $a->teacher_id,'subject_id' => $a->subject_id,'weekly_slots' => $a->weekly_slots
            ])->all(),
            'locked'    => $locked->toArray()
        ];

        $res = Http::timeout(60)->post($solverUrl.'/validate', $payload);
        if (!$res->ok()) {
            return response()->json(['ok' => false,'message' => 'Solver validation failed','detail' => $res->body()], 422);
        }
        return response()->json($res->json());
    }

    public function getTeacherWorkload($id)
    {
        $teachers = DB::table('teachers')->select('id', 'name')->orderBy('name')->get();
        $teacherIndex = $teachers->pluck('name', 'id'); // [id => name]

        $totals = DB::table('timetable_entries as e')
            ->where('e.timetable_request_id', $id)
            ->join('teachers as t', 't.id', '=', 'e.teacher_id')
            ->select('e.teacher_id', DB::raw('COUNT(*) as periods'))
            ->groupBy('e.teacher_id')
            ->get();

        $perDay = DB::table('timetable_entries as e')
            ->where('e.timetable_request_id', $id)
            ->join('timeslots as ts', 'ts.id', '=', 'e.timeslot_id')
            ->select('e.teacher_id', 'ts.day_of_week', DB::raw('COUNT(*) as periods'))
            ->groupBy('e.teacher_id', 'ts.day_of_week')
            ->get();

        $barCategories = [];
        $barData = [];

        foreach ($teachers as $t) {
            $barCategories[] = $t->name;
            $count = $totals->firstWhere('teacher_id', $t->id)->periods ?? 0;
            $barData[] = (int) $count;
        }

        $dayNames = [1 => 'Mon',2 => 'Tue',3 => 'Wed',4 => 'Thu',5 => 'Fri',6 => 'Sat',7 => 'Sun'];
        $heatmapSeries = [];
        foreach ($teachers as $t) {
            $row = [];
            for ($d = 1; $d <= 7; $d++) {
                $row[$d] = 0;
            }
            foreach ($perDay->where('teacher_id', $t->id) as $rec) {
                $row[$rec->day_of_week] = (int) $rec->periods;
            }
            $heatmapSeries[] = [
                'name' => $t->name,
                'data' => array_map(function ($dayIdx) use ($row, $dayNames) {
                    return ['x' => $dayNames[$dayIdx] ?? "Day $dayIdx", 'y' => $row[$dayIdx]];
                }, array_keys($row))
            ];
        }

        $req = DB::table('timetable_requests')->where('id', $id)->first();

        $weeklySlots = DB::table('timeslots')->count();
        return view('timetable.workload', [
          'id' => $id,
          'req' => $req,
          'barCategories' => $barCategories,
          'barData' => $barData,
          'heatmapSeries' => $heatmapSeries,
          'weeklySlots' => $weeklySlots,
        ]);

    }

    public function getStatus($id)
    {
        $r = DB::table('timetable_requests')->find($id);
        if (!$r) {
            return response()->json(['status' => 'failed','error' => 'Request not found'], 404);
        }
        return response()->json([
            'status' => $r->status,
            'error'  => $r->error,
        ]);
    }

    public function getSubjectUsage($id)
    {
        $groups   = DB::table('groups')->select('id', 'name')->orderBy('name')->get();
        $subjects = DB::table('subjects')->select('id', 'name')->orderBy('name')->get();

        $groupId   = request('group_id', $groups->first()->id ?? null);
        $subjectId = request('subject_id', $subjects->first()->id ?? null);

        if (!$groupId || !$subjectId) {
            return view('timetable.subject_usage', [
                'id' => $id, 'groups' => $groups, 'subjects' => $subjects,
                'groupId' => $groupId, 'subjectId' => $subjectId,
                'weeklySlots' => DB::table('timeslots')->count(),
                'total' => 0, 'perDaySeries' => [], 'rows' => collect(),
            ]);
        }

        $perDayRaw = DB::table('timetable_entries as e')
            ->join('timeslots as ts', 'ts.id', '=', 'e.timeslot_id')
            ->where('e.timetable_request_id', $id)
            ->where('e.group_id', $groupId)
            ->where('e.subject_id', $subjectId)
            ->select('ts.day_of_week', DB::raw('count(*) as c'))
            ->groupBy('ts.day_of_week')
            ->pluck('c', 'ts.day_of_week')
            ->all();

        $dayNames = [1 => 'Mon',2 => 'Tue',3 => 'Wed',4 => 'Thu',5 => 'Fri',6 => 'Sat',7 => 'Sun'];
        $days     = array_keys($dayNames);
        $perDaySeries = [
            'categories' => array_values($dayNames),
            'data'       => array_map(fn ($d) => (int)($perDayRaw[$d] ?? 0), $days),
        ];
        $total = array_sum($perDaySeries['data']);

        $required = null;
        $gradeId  = DB::table('groups')->where('id', $groupId)->value('grade_id');
        if (Schema::hasTable('grade_subject_requirements')) {
            $required = DB::table('grade_subject_requirements')
                ->where('grade_id', $gradeId)->where('subject_id', $subjectId)
                ->value('periods_per_week');
        }

        $rows = DB::table('timetable_entries as e')
            ->join('timeslots as ts', 'ts.id', '=', 'e.timeslot_id')
            ->leftJoin('teachers as t', 't.id', '=', 'e.teacher_id')
            ->where('e.timetable_request_id', $id)
            ->where('e.group_id', $groupId)
            ->where('e.subject_id', $subjectId)
            ->orderBy('ts.day_of_week')->orderBy('ts.slot_index')
            ->get([
                'ts.day_of_week','ts.slot_index','ts.start_time','ts.end_time',
                't.name as teacher_name'
            ]);

        return view('timetable.subject_usage', [
            'id' => $id,
            'groups' => $groups,
            'subjects' => $subjects,
            'groupId' => $groupId,
            'subjectId' => $subjectId,
            'groupName' => $groups->firstWhere('id', $groupId)->name ?? '',
            'subjectName' => $subjects->firstWhere('id', $subjectId)->name ?? '',
            'weeklySlots' => DB::table('timeslots')->count(), // e.g. 40
            'total' => $total,
            'required' => $required,
            'perDaySeries' => $perDaySeries,
            'rows' => $rows,
        ]);
    }



}
