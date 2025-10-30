<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoSeederLarge extends Seeder
{
    // ----- knobs you can change quickly -----
    private const PERIODS_PER_DAY    = 8;   // 8 per day
    private const DAYS_PER_WEEK      = 5;   // Mon–Fri
    private const WEEKLY_SLOTS       = 40;  // 8 × 5
    private const GROUPS_PER_GRADE   = 5;   // A..E  →  10 grades × 5 = 50 groups
    private const TEACHER_COUNT      = 32;  // capacity = 32 × 40 = 1280
    private const PER_GROUP_WEEKLY   = 20;  // target lessons per group/week (<= 40)

    public function run(): void
    {
        $this->command?->info('>>> DemoSeederLarge (8×5, 40/wk) starting …');

        mt_srand(20251030);

        // ----------------------------
        // Truncate core tables
        // ----------------------------
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
            // optional:
            'group_blackouts',
        ] as $t) {
            if (Schema::hasTable($t)) DB::table($t)->truncate();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ----------------------------
        // Sections / Grades / Groups
        // ----------------------------
        $secPrimary   = DB::table('sections')->insertGetId(['name'=>'Primary','created_at'=>now(),'updated_at'=>now()]);
        $secSecondary = DB::table('sections')->insertGetId(['name'=>'Secondary','created_at'=>now(),'updated_at'=>now()]);

        $gradeIds = [];   // [1..10] => id
        $groupIds = [];   // [gradeNum => [letter => group_id]]

        foreach (range(1, 10) as $gNum) {
            $gid = DB::table('grades')->insertGetId([
                'section_id' => $gNum <= 5 ? $secPrimary : $secSecondary,
                'name'       => "Grade {$gNum}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $gradeIds[$gNum] = $gid;

            foreach (array_slice(range('A','Z'), 0, self::GROUPS_PER_GRADE) as $letter) {
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
        // Subjects
        // ----------------------------
        // 2 majors with 3/wk each, rest 1/wk
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
            // add a few extra 1-slot subjects for variety
            ['name'=>'Literature',         'weekly_slots'=>1],
            ['name'=>'Drama',              'weekly_slots'=>1],
            ['name'=>'Music',              'weekly_slots'=>1],
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
        // grade_subject: ensure EXACTLY 20 lessons per group/week
        // 3 + 3 + (14 × 1) = 20
        // choose 14 one-slot subjects per grade band
        // ----------------------------
        $majors = ['Mathematics','English'];

        $oneSlotPoolPrimary = [
            'Science','Second Language','ICT','History','Geography','Physical Education','Aesthetics',
            'Civics','Economics','Business Studies','Biology','Chemistry','Physics','Literature','Drama','Music'
        ];
        $oneSlotPoolSecondary = [
            'Physics','Chemistry','Biology','Economics','Business Studies','Civics','ICT','Second Language',
            'History','Geography','Physical Education','Aesthetics','Science','Literature','Drama','Music'
        ];

        foreach ($gradeIds as $gNum => $gid) {
            $pool = $gNum <= 5 ? $oneSlotPoolPrimary : $oneSlotPoolSecondary;

            // pick first 14 from pool (deterministic); you can shuffle($pool) if you want randomness
            $oneSlots = array_slice($pool, 0, 14);

            $subset = array_merge($majors, $oneSlots); // 2 + 14 = 16 subjects, totals 20 slots
            // sanity check
            $sum = 0;
            foreach ($subset as $name) {
                $sum += (int) DB::table('subjects')->where('id', $sidByName[$name])->value('weekly_slots');
            }
            if ($sum !== self::PER_GROUP_WEEKLY) {
                throw new \RuntimeException("Grade {$gNum} weekly sum = {$sum}, expected ".self::PER_GROUP_WEEKLY);
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
        // Teachers (32)
        // ----------------------------
        $teacherNames = [
            'Alice Perera','Bob Fernando','Carol Jayasinghe','David Silva','Eva de Alwis',
            'Farhan Ismail','Grace Wijesinghe','Henry Dias','Ishara Senanayake','Jude Ranasinghe',
            'Kamal Peris','Lakmini Dissanayake','Manjula Peiris','Nadeesha Peiris','Oshini Abeywickrama',
            'Pradeep Gunasekara','Rashmi Wickramasinghe','Sunil Samarasinghe','Thilina Jayasuriya','Udari Ekanayake',
            'Alice Gunawardena','Bhanuka Peiris','Chatura Ranatunga','Dilini Silva','Eshan Perera',
            'Fathima Nizar','Gihan Fonseka','Haritha Senanayake','Iresha Perera','Janith Fernando',
            'Kasuni Jayawardena','Lahiru Wickramasinghe',
        ];
        // ensure at least TEACHER_COUNT
        while (count($teacherNames) < self::TEACHER_COUNT) {
            $teacherNames[] = 'Teacher '.(count($teacherNames)+1);
        }
        $teacherNames = array_slice($teacherNames, 0, self::TEACHER_COUNT);

        $teacherIds = [];
        foreach ($teacherNames as $name) {
            $teacherIds[] = DB::table('teachers')->insertGetId([
                'name'       => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // subject family buckets (to pick sensible teachers)
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
            'Literature'         => 'Lang',
            'Drama'              => 'Aes',
            'Music'              => 'Aes',
        ];
        $families = array_unique(array_values($familyBySubject));

        $buckets = [];
        foreach ($families as $fam) {
            $pool = $teacherIds;
            shuffle($pool);
            // give each family a decent-sized pool
            $buckets[$fam] = array_slice($pool, 0, 10);
        }

        // balance: try to keep load near PER_TEACHER_MAX (= 40) without hard caps
        $teacherLoad = array_fill_keys($teacherIds, 0);

        $pickTeacher = function (string $subject, int $need) use (&$buckets, &$teacherLoad, $familyBySubject, $teacherIds) {
            $fam  = $familyBySubject[$subject] ?? 'Gen';
            $pool = $buckets[$fam] ?? $teacherIds;
            usort($pool, fn($a,$b) => ($teacherLoad[$a] <=> $teacherLoad[$b]));
            $chosen = $pool[0];
            $teacherLoad[$chosen] += $need;
            return $chosen;
        };

        // ----------------------------
        // group_teacher assignments (primary teacher per subject per group)
        // ----------------------------
        foreach ($groupIds as $gNum => $letters) {
            // determine subset again (same as grade_subject)
            $pool = $gNum <= 5 ? $oneSlotPoolPrimary : $oneSlotPoolSecondary;
            $subset = array_merge($majors, array_slice($pool, 0, 14));

            foreach ($letters as $groupId) {
                foreach ($subset as $subjName) {
                    $sid  = $sidByName[$subjName];
                    $need = (int) DB::table('subjects')->where('id', $sid)->value('weekly_slots');
                    $tid  = $pickTeacher($subjName, $need);
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
        // Timeslots: 8 per day × 5 days = 40
        // ----------------------------
        $startHour = 8; $startMin = 15; $blockMin = 40; $gapMin = 5;
        for ($d = 1; $d <= self::DAYS_PER_WEEK; $d++) {
            $h = $startHour; $m = $startMin;
            for ($p = 1; $p <= self::PERIODS_PER_DAY; $p++) {
                $start = sprintf('%02d:%02d:00', $h, $m);
                $m2 = $m + $blockMin; $h2 = $h + intdiv($m2, 60); $m2 = $m2 % 60;
                $end = sprintf('%02d:%02d:00', $h2, $m2);

                DB::table('timeslots')->insert([
                    'day_of_week' => $d,
                    'slot_index'  => $p,
                    'start_time'  => $start,
                    'end_time'    => $end,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                // move to next slot start (+gap)
                $m = $m2 + $gapMin; $h += intdiv($m, 60); $m = $m % 60;
            }
        }

        // ----------------------------
        // Optional: compress window — blackout P7 & P8
        // ----------------------------
        if (Schema::hasTable('group_blackouts')) {
            $this->command?->info('Seeding group_blackouts (P7–P8 off) …');
            $allGroupIds = [];
            foreach ($groupIds as $letters) { foreach ($letters as $gid) $allGroupIds[] = $gid; }

            foreach ($allGroupIds as $gid) {
                for ($d = 1; $d <= self::DAYS_PER_WEEK; $d++) {
                    foreach ([7,8] as $s) {
                        DB::table('group_blackouts')->insert([
                            'group_id'    => $gid,
                            'day_of_week' => $d,
                            'slot_index'  => $s,
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]);
                    }
                }
            }
        }

        // ----------------------------
        // One pending request so UI can run immediately
        // ----------------------------
        DB::table('timetable_requests')->insert([
            'constraints' => json_encode([
                'seed'   => '32T-50G-20perGroup-8x5',
                'notes'  => '8 periods/day; 20 lessons/group; P7–P8 blacked out if table exists',
            ]),
            'status'      => 'pending',
            'error'       => null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // ----------------------------
        // Stats
        // ----------------------------
        $groupsCount = array_reduce($groupIds, fn($c,$r)=>$c+count($r), 0);
        $capacity    = self::TEACHER_COUNT * self::WEEKLY_SLOTS;
        $demand      = $groupsCount * self::PER_GROUP_WEEKLY;

        $this->command?->info("Seeded: teachers=".self::TEACHER_COUNT.", groups={$groupsCount}, subjects=".count($subjects));
        $this->command?->info("Timeslots: ".self::PERIODS_PER_DAY."×".self::DAYS_PER_WEEK." = ".self::WEEKLY_SLOTS."/wk");
        $this->command?->info("Weekly demand ≈ {$demand}, capacity ≈ {$capacity} (headroom ".($capacity-$demand).")");

        asort($teacherLoad);
        $peek = array_slice($teacherLoad, -8, 8, true);
        $this->command?->info('Teacher load (top 8 periods/week):');
        foreach ($peek as $tid => $v) {
            $name = DB::table('teachers')->where('id',$tid)->value('name');
            $this->command?->info(" - {$name}: {$v}");
        }
    }
}
