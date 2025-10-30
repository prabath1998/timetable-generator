<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoSeederLarge extends Seeder
{
    // target weekly lessons per GROUP (keep < 40; 15 keeps solver comfy)
    private const PER_GROUP_WEEKLY = 15;

    public function run(): void
    {
        $this->command?->info('>>> USING SEEDER FILE: '.__FILE__.' (8 periods/day, 40/week)');
        mt_srand(20251030);

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
        ] as $t) {
            if (DB::getSchemaBuilder()->hasTable($t)) DB::table($t)->truncate();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ----------------------------
        // Sections / Grades / Groups (50 groups)
        // ----------------------------
        $secPrimary   = DB::table('sections')->insertGetId(['name'=>'Primary','created_at'=>now(),'updated_at'=>now()]);
        $secSecondary = DB::table('sections')->insertGetId(['name'=>'Secondary','created_at'=>now(),'updated_at'=>now()]);

        $gradeIds = [];  // 1..10
        $groupIds = [];  // [grade => [A..E => id]]
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
        // Subjects (15 total) + weekly slots (Math/Eng are 3; others 1)
        // ----------------------------
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
        $sidByName = [];
        foreach ($subjects as $s) {
            $sid = DB::table('subjects')->insertGetId([
                'name'         => $s['name'],
                'weekly_slots' => $s['weekly_slots'],
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
            $sidByName[$s['name']] = $sid;
        }

        // ----------------------------
        // grade_subject subsets that sum to EXACTLY 15 lessons/week per group
        // ----------------------------
        // 3 + 3 + (9 × 1) = 15
        $primarySubset = [
            'Mathematics','English',
            'Science','Second Language','ICT','History','Geography','Physical Education','Aesthetics','Civics','Economics'
        ];
        $secondarySubset = [
            'Mathematics','English',
            'Physics','Chemistry','Biology','Economics','Business Studies','Civics','ICT','Second Language','History'
        ];

        foreach ($gradeIds as $gNum => $gid) {
            $subset = $gNum <= 5 ? $primarySubset : $secondarySubset;
            // sanity: make sure it sums to PER_GROUP_WEEKLY
            $sum = 0;
            foreach ($subset as $name) $sum += (int) DB::table('subjects')->where('id',$sidByName[$name])->value('weekly_slots');
            if ($sum !== self::PER_GROUP_WEEKLY) {
                throw new \RuntimeException("Subset for Grade {$gNum} sums to {$sum}, expected ".self::PER_GROUP_WEEKLY);
            }
            foreach ($subset as $name) {
                DB::table('grade_subject')->insert([
                    'grade_id'   => $gid,
                    'subject_id' => $sidByName[$name],
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

        // teacher buckets (6–10 per family)
        $buckets = [];
        foreach (array_unique(array_values($familyBySubject)) as $fam) {
            $pool = $teacherIds;
            shuffle($pool);
            $buckets[$fam] = array_slice($pool, 0, mt_rand(6,10));
        }

        // balance load; try not to exceed 40 per teacher
        $teacherLoad = array_fill_keys($teacherIds, 0);

        $pickTeacher = function (string $subject, int $need) use (&$buckets, &$teacherLoad, $familyBySubject, $teacherIds) {
            $fam  = $familyBySubject[$subject] ?? 'Gen';
            $pool = $buckets[$fam] ?? $teacherIds;
            usort($pool, function($a,$b) use ($teacherLoad){
                // prefer lower load; slight bias against people already >= 40
                $la = $teacherLoad[$a] + ($teacherLoad[$a] >= 40 ? 1000 : 0);
                $lb = $teacherLoad[$b] + ($teacherLoad[$b] >= 40 ? 1000 : 0);
                return $la <=> $lb;
            });
            $chosen = $pool[0];
            $teacherLoad[$chosen] += $need;
            return $chosen;
        };

        // group_teacher assignments
        foreach ($groupIds as $gNum => $letters) {
            $subset = $gNum <= 5 ? $primarySubset : $secondarySubset;
            foreach ($letters as $groupId) {
                foreach ($subset as $subjName) {
                    $sid   = $sidByName[$subjName];
                    $need  = (int) DB::table('subjects')->where('id',$sid)->value('weekly_slots');
                    $tid   = $pickTeacher($subjName, $need);
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
        // Timeslots: **8 per day** × 5 days = **40/week**
        // ----------------------------
        $startHour = 8; $startMin = 15; $blockMin = 40; $gapMin = 5;
        for ($d = 1; $d <= 5; $d++) {
            $h = $startHour; $m = $startMin;
            for ($p = 1; $p <= 8; $p++) {   // <= 8 (was 7)
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

                // next slot (small gap)
                $m = $m2 + $gapMin;
                $h += intdiv($m, 60);
                $m = $m % 60;
            }
        }

        // Seed one pending request so UI can run immediately
        DB::table('timetable_requests')->insert([
            'constraints' => json_encode([
                'seed' => '20T-50G-15S-8x5',
                'notes' => '8 periods/day; 15 lessons/group/week',
            ]),
            'status'      => 'pending',
            'error'       => null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // Stats
        $groupsCount = array_reduce($groupIds, fn($c,$r)=>$c+count($r), 0);
        $this->command?->info("Seeded: teachers=20, groups={$groupsCount}, subjects=15, days=5, periods/day=8 (40/wk)");
        asort($teacherLoad);
        $top = array_slice($teacherLoad, -5, 5, true);
        $this->command?->info('Top teacher loads (should be < 40):');
        foreach ($top as $tid=>$v) {
            $name = DB::table('teachers')->where('id',$tid)->value('name');
            $this->command?->info(" - {$name}: {$v}");
        }
    }
}
