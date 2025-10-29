<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('timeslots', function (Blueprint $t) {
            $t->id();
            $t->unsignedTinyInteger('day_of_week'); // 1=Mon..5=Fri (or 7)
            $t->unsignedTinyInteger('slot_index');  // 1..N (e.g., period number)
            $t->time('start_time');
            $t->time('end_time');
            $t->timestamps();
            $t->unique(['day_of_week','slot_index']);
        });

        Schema::create('timetable_requests', function (Blueprint $t) {
            $t->id();
            $t->json('constraints')->nullable(); // optional custom constraints
            $t->string('status')->default('pending'); // pending|solved|failed
            $t->text('error')->nullable();
            $t->timestamps();
        });

        Schema::create('timetable_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('timetable_request_id')->constrained()->cascadeOnDelete();
            $t->foreignId('group_id')->constrained()->cascadeOnDelete();
            $t->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $t->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $t->foreignId('timeslot_id')->constrained()->cascadeOnDelete();
            $t->timestamps();

            // was: $t->unique(['timetable_request_id','group_id','timeslot_id']);
            $t->unique(
                ['timetable_request_id','group_id','timeslot_id'],
                'tt_entries_req_grp_ts_unique' // <= keep under 64 chars
            );

            // (optional) helpful indexes:
            $t->index(['teacher_id','timeslot_id'], 'tt_entries_teacher_ts_idx');
            $t->index(['group_id','timeslot_id'], 'tt_entries_group_ts_idx');
        });

    }

};
