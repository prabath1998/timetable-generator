<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
{
    $sec = DB::table('sections')->insertGetId(['name'=>'Secondary','created_at'=>now(),'updated_at'=>now()]);
    $g7  = DB::table('grades')->insertGetId(['name'=>'Grade 7','section_id'=>$sec,'created_at'=>now(),'updated_at'=>now()]);
    $a   = DB::table('groups')->insertGetId(['name'=>'7A','grade_id'=>$g7,'created_at'=>now(),'updated_at'=>now()]);
    $b   = DB::table('groups')->insertGetId(['name'=>'7B','grade_id'=>$g7,'created_at'=>now(),'updated_at'=>now()]);

    $math = DB::table('subjects')->insertGetId(['name'=>'Math','weekly_slots'=>5,'created_at'=>now(),'updated_at'=>now()]);
    $eng  = DB::table('subjects')->insertGetId(['name'=>'English','weekly_slots'=>5,'created_at'=>now(),'updated_at'=>now()]);
    $sci  = DB::table('subjects')->insertGetId(['name'=>'Science','weekly_slots'=>4,'created_at'=>now(),'updated_at'=>now()]);

    DB::table('grade_subject')->insert([
        ['grade_id'=>$g7,'subject_id'=>$math,'created_at'=>now(),'updated_at'=>now()],
        ['grade_id'=>$g7,'subject_id'=>$eng,'created_at'=>now(),'updated_at'=>now()],
        ['grade_id'=>$g7,'subject_id'=>$sci,'created_at'=>now(),'updated_at'=>now()],
    ]);

    $t1 = DB::table('teachers')->insertGetId(['name'=>'Alice','created_at'=>now(),'updated_at'=>now()]);
    $t2 = DB::table('teachers')->insertGetId(['name'=>'Bob','created_at'=>now(),'updated_at'=>now()]);
    $t3 = DB::table('teachers')->insertGetId(['name'=>'Carol','created_at'=>now(),'updated_at'=>now()]);

    DB::table('group_teacher')->insert([
        ['group_id'=>$a,'teacher_id'=>$t1,'subject_id'=>$math],
        ['group_id'=>$a,'teacher_id'=>$t2,'subject_id'=>$eng],
        ['group_id'=>$a,'teacher_id'=>$t3,'subject_id'=>$sci],
        ['group_id'=>$b,'teacher_id'=>$t2,'subject_id'=>$math],
        ['group_id'=>$b,'teacher_id'=>$t3,'subject_id'=>$eng],
        ['group_id'=>$b,'teacher_id'=>$t1,'subject_id'=>$sci],
    ]);

    // 5 days x 6 periods
    for ($d=1;$d<=5;$d++){
        for ($p=1;$p<=6;$p++){
            DB::table('timeslots')->insert([
                'day_of_week'=>$d,
                'slot_index'=>$p,
                'start_time'=>sprintf('%02d:00:00', 7+$p), // demo times
                'end_time'  =>sprintf('%02d:45:00', 7+$p),
                'created_at'=>now(),'updated_at'=>now()
            ]);
        }
    }
}

}
