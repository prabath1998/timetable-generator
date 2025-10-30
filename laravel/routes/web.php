<?php

use App\Http\Controllers\TimetableUIController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/timetable', [TimetableUIController::class, 'index'])->name('tt.index');
Route::post('/timetable/generate', [TimetableUIController::class, 'generate'])->name('tt.generate');
Route::get('/timetable/{id}', [TimetableUIController::class, 'show'])->name('tt.show');

// NEW: DnD endpoints
Route::post('/timetable/{id}/move', [TimetableUIController::class, 'move'])->name('tt.move');
Route::post('/timetable/{id}/validate', [TimetableUIController::class, 'validateNow'])->name('tt.validate');
Route::get('/timetable/{id}/workload', [TimetableUIController::class, 'workload'])->name('tt.workload');
// routes/web.php
Route::get('/timetable/{id}/status', [\App\Http\Controllers\TimetableController::class,'status'])
    ->name('tt.status');



