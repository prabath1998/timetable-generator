<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('sections', function (Blueprint $t) {
        $t->id(); $t->string('name'); $t->timestamps();
    });

    Schema::create('grades', function (Blueprint $t) {
        $t->id(); $t->foreignId('section_id')->constrained()->cascadeOnDelete();
        $t->string('name'); $t->timestamps();
    });

    Schema::create('groups', function (Blueprint $t) {
        $t->id(); $t->foreignId('grade_id')->constrained()->cascadeOnDelete();
        $t->string('name'); // e.g., "7A"
        $t->timestamps();
    });

    Schema::create('subjects', function (Blueprint $t) {
        $t->id(); $t->string('name'); $t->unsignedTinyInteger('weekly_slots'); // hours/week
        $t->timestamps();
    });

    Schema::create('teachers', function (Blueprint $t) {
        $t->id(); $t->string('name'); $t->timestamps();
    });

    Schema::create('grade_subject', function (Blueprint $t) {
        $t->id();
        $t->foreignId('grade_id')->constrained()->cascadeOnDelete();
        $t->foreignId('subject_id')->constrained()->cascadeOnDelete();
        $t->timestamps();
        $t->unique(['grade_id','subject_id']);
    });

    Schema::create('group_teacher', function (Blueprint $t) {
        $t->id();
        $t->foreignId('group_id')->constrained()->cascadeOnDelete();
        $t->foreignId('teacher_id')->constrained()->cascadeOnDelete();
        $t->foreignId('subject_id')->constrained()->cascadeOnDelete();
        $t->timestamps();
        $t->unique(['group_id','teacher_id','subject_id']);
    });
}

};
