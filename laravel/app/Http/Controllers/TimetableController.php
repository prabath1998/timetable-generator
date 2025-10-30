<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class TimetableController extends Controller
{
    public function generate(Request $request)
    {
        $solverUrl = rtrim(config('app.solver_url', env('SOLVER_URL', 'http://solver:8080')), '/');

        $groups = DB::table('groups')->get();
        $timeslots = DB::table('timeslots')->orderBy('day_of_week')->orderBy('slot_index')->get();
        $assignments = DB::table('group_teacher')
            ->join('teachers', 'teachers.id', '=', 'group_teacher.teacher_id')
            ->join('subjects', 'subjects.id', '=', 'group_teacher.subject_id')
            ->select(
                'group_teacher.group_id',
                'group_teacher.teacher_id',
                'group_teacher.subject_id',
                'teachers.name as teacher_name',
                'subjects.name as subject_name',
                'subjects.weekly_slots'
            )
            ->get();

        $payload = [
            'timeslots'   => $timeslots->map(fn ($t) => [
                'id' => $t->id,'day' => $t->day_of_week,'slot' => $t->slot_index
            ])->all(),
            'groups'      => $groups->map(fn ($g) => ['id' => $g->id])->all(),
            'teaching'    => $assignments->map(fn ($a) => [
                'group_id' => $a->group_id,
                'teacher_id' => $a->teacher_id,
                'subject_id' => $a->subject_id,
                'weekly_slots' => $a->weekly_slots
            ])->all(),
            'soft'        => $request->input('soft_constraints', []),
        ];

        $reqId = DB::table('timetable_requests')->insertGetId([
            'constraints' => json_encode($payload['soft']),
            'status' => 'pending',
            'created_at' => now(),'updated_at' => now()
        ]);

        $res = Http::timeout(120)->post($solverUrl.'/solve', $payload);

        if (!$res->ok()) {
            DB::table('timetable_requests')->where('id', $reqId)->update([
                'status' => 'failed','error' => $res->body(),'updated_at' => now()
            ]);
            return response()->json(['message' => 'Solver error','detail' => $res->body()], 422);
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

        return response()->json(['request_id' => $reqId,'status' => 'solved'], 201);
    }

    public function show($requestId)
    {
        $rows = DB::table('timetable_entries as e')
            ->join('groups as g', 'g.id', '=', 'e.group_id')
            ->join('teachers as t', 't.id', '=', 'e.teacher_id')
            ->join('subjects as s', 's.id', '=', 'e.subject_id')
            ->join('timeslots as ts', 'ts.id', '=', 'e.timeslot_id')
            ->where('e.timetable_request_id', $requestId)
            ->orderBy('ts.day_of_week')->orderBy('ts.slot_index')
            ->selectRaw('g.name as group_name, t.name as teacher, s.name as subject, ts.day_of_week as day, ts.slot_index as slot')
            ->get();

        return response()->json($rows);
    }

    // app/Http/Controllers/TimetableController.php
    public function status($id)
    {
        $r = DB::table('timetable_requests')->find($id);
        if (!$r) {
            return response()->json(['status' => 'failed','error' => 'Request not found'], 404);
        }
        return response()->json([
            'status' => $r->status,      // 'pending' | 'solved' | 'failed'
            'error'  => $r->error,
        ]);
    }

}
