<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoSeederLarge extends Seeder
{
    public function run(): void
    {
        // Marker to ensure THIS file is running
        $this->command?->info('>>> USING SEEDER FILE: '.__FILE__);

        mt_srand(20251030);

        // Clean slate
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ([
            'timetable_entries',
            'timetable_requests',
            'group_teacher',
            'grade_subject',
            'teachers',
            'subjects',
            'groups',
            'grades',
            'sections',
            'timeslots',
        ] as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                DB::table($table)->truncate();
            }
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ----------------------------
        // Sections / Grades / Groups (50 groups)
        // ----------------------------
        $secPrimary   = DB::table('sections')->insertGetId(['name'=>'Primary','created_at'=>now(),'updated_at'=>now()]);
        $secSecondary = DB::table('sections')->insertGetId(['name'=>'Secondary','created_at'=>now(),'updated_at'=>now()]);

        $gradeIds = [];   // Grades 1..10
        $groupIds = [];   // 5 groups per grade => 50
        foreach (range(1,10) as $gNum) {
            $gid = DB::table('grades')->insertGetId([
                'section_id' => $gNum <= 5 ? $secPrimary : $secSecondary,
                'name'       => "Grade {$gNum}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $gradeIds[$gNum] = $gid;

            foreach (['A','B','C','D','E'] as $letter) {
                $ggid = DB::table('groups')->insertGetId([
                    'grade_id'   => $gid,
                    'name'       => "{$gNum}{$letter}",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $groupIds[$gNum][$letter] = $ggid;
            }
        }

        // ----------------------------
        // Subjects (15 total)
        // ----------------------------
        // Keep 15 subjects globally; we’ll include a subset per grade that sums to EXACTLY 12.
        $subjects = [
            ['name'=>'Mathematics',        'weekly_slots'=>3],
            ['name'=>'English',            'weekly_slots'=>3],
            ['name'=>'Science',            'weekly_slots'=>1],
            ['name'=>'Physics',            'weekly_slots'=>1],
            ['name'=>'Chemistry',          'weekly_slots'=>1],
            ['name'=>'Biology',            'weekly_slots'=>1],
            ['name'=>'History',            'weekly_slots'=>1],
            ['name'=>'Geography',          'weekly_slots'=>1],
            ['name'=>'Civics',             'weekly_slots'=>1],
            ['name'=>'Second Language',    'weekly_slots'=>1],
            ['name'=>'ICT',                'weekly_slots'=>1],
            ['name'=>'Aesthetics',         'weekly_slots'=>1],
            ['name'=>'Physical Education', 'weekly_slots'=>1],
            ['name'=>'Economics',          'weekly_slots'=>1],
            ['name'=>'Business Studies',   'weekly_slots'=>1],
        ];
        $subjectIdByName = [];
        foreach ($subjects as $s) {
            $sid = DB::table('subjects')->insertGetId([
                'name'         => $s['name'],
                'weekly_slots' => $s['weekly_slots'],
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
            $subjectIdByName[$s['name']] = $sid;
        }

        // ----------------------------
        // grade_subject subsets (sum = 12 per grade)
        // ----------------------------
        // Primary (grades 1–5): 2 core (3+3) + 6 one-slot = 12
        $primarySubset = [
            'Mathematics','English', // 6
            'Science','Second Language','ICT','History','Geography','Physical Education' // +6 = 12
        ];

        // Secondary (grades 6–10): 2 core (3+3) + 6 one-slot = 12
        // Slightly different mix to vary teacher subjects
        $secondarySubset = [
            'Mathematics','English', // 6
            'Physics','Chemistry','Biology','Economics','Business Studies','Civics' // +6 = 12
        ];

        foreach ($gradeIds as $gNum => $gid) {
            $subset = $gNum <= 5 ? $primarySubset : $secondarySubset;
            foreach ($subset as $name) {
                DB::table('grade_subject')->insert([
                    'grade_id'   => $gid,
                    'subject_id' => $subjectIdByName[$name],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // ----------------------------
        // 20 Teachers
        // ----------------------------
        $teacherNames = [
            'Alice Perera','Bob Fernando','Carol Jayasinghe','David Silva','Eva de Alwis',
            'Farhan Ismail','Grace Wijesinghe','Henry Dias','Ishara Senanayake','Jude Ranasinghe',
            'Kamal Peris','Lakmini Dissanayake','Manjula Peiris','Nadeesha Peiris','Oshini Abeywickrama',
            'Pradeep Gunasekara','Rashmi Wickramasinghe','Sunil Samarasinghe','Thilina Jayasuriya','Udari Ekanayake',
        ];
        $teacherIds = [];
        foreach ($teacherNames as $name) {
            $teacherIds[] = DB::table('teachers')->insertGetId([
                'name'       => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Subject families → buckets to diversify assignment
        $familyBySubject = [
            'Mathematics'        => 'Math',
            'English'            => 'Lang',
            'Science'            => 'Sci',
            'Physics'            => 'Sci',
            'Chemistry'          => 'Sci',
            'Biology'            => 'Sci',
            'History'            => 'Hum',
            'Geography'          => 'Hum',
            'Civics'             => 'Hum',
            'Second Language'    => 'Lang',
            'ICT'                => 'ICT',
            'Aesthetics'         => 'Aes',
            'Physical Education' => 'PE',
            'Economics'          => 'Biz',
            'Business Studies'   => 'Biz',
        ];

        // Build medium-size buckets (6–10 teachers) per family
        $buckets = [];
        foreach (array_unique(array_values($familyBySubject)) as $fam) {
            $pool = $teacherIds;
            shuffle($pool);
            $buckets[$fam] = array_slice($pool, 0, mt_rand(6,10));
        }

        // Track teacher load and prefer the least-loaded in each bucket
        $teacherLoad = array_fill_keys($teacherIds, 0);

        $pickTeacher = function (string $subject) use (&$buckets, &$teacherLoad, $familyBySubject, $teacherIds) {
            $fam  = $familyBySubject[$subject] ?? 'Gen';
            $pool = $buckets[$fam] ?? $teacherIds;
            usort($pool, fn($a,$b) => $teacherLoad[$a] <=> $teacherLoad[$b]);
            return $pool[0];
        };

        // -----------------------------------
        // group_teacher: per group, assign subset subjects
        // Total demand = 50 × 12 = 600 (slack vs capacity 700)
        // -----------------------------------
        foreach ($groupIds as $gNum => $letters) {
            $subset = $gNum <= 5 ? $primarySubset : $secondarySubset;
            foreach ($letters as $groupId) {
                foreach ($subset as $subjName) {
                    $sid  = $subjectIdByName[$subjName];
                    $need = (int) DB::table('subjects')->where('id',$sid)->value('weekly_slots');

                    $tid = $pickTeacher($subjName);
                    $teacherLoad[$tid] += $need;

                    DB::table('group_teacher')->insert([
                        'group_id'   => $groupId,
                        'teacher_id' => $tid,
                        'subject_id' => $sid,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // ----------------------------
        // Timeslots: 5 days × 7 periods = 35/week
        // ----------------------------
        $startHour = 8; $startMin = 15; $blockMin = 40; $gapMin = 5;
        for ($d = 1; $d <= 5; $d++) {
            $h = $startHour; $m = $startMin;
            for ($p = 1; $p <= 7; $p++) {
                $start = sprintf('%02d:%02d:00', $h, $m);
                $m2 = $m + $blockMin;
                $h2 = $h + intdiv($m2, 60);
                $m2 = $m2 % 60;
                $end = sprintf('%02d:%02d:00', $h2, $m2);

                DB::table('timeslots')->insert([
                    'day_of_week' => $d,
                    'slot_index'  => $p,
                    'start_time'  => $start,
                    'end_time'    => $end,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                $m = $m2 + $gapMin;
                $h += intdiv($m, 60);
                $m = $m % 60;
            }
        }

        // Request row so UI shows a run
        DB::table('timetable_requests')->insert([
            'constraints' => json_encode(['seed' => '20T-50G-15S-12pw']),
            'status'      => 'pending',
            'error'       => null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // Stats
        $this->command?->info('Seeded: teachers=20, groups=50, subjects=15, group_load=12, timeslots=35');
        asort($teacherLoad);
        $top = array_slice($teacherLoad, -5, 5, true);
        $this->command?->info('Top teacher loads (should be well < 35 now):');
        foreach ($top as $tid=>$v) {
            $name = DB::table('teachers')->where('id',$tid)->value('name');
            $this->command?->info(" - {$name}: {$v}");
        }
    }
}
