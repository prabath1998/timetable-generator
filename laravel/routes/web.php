<?php

use App\Http\Controllers\TimetableController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('timetable')->name('tt.')->group(function () {

    Route::get('/', [TimetableController::class, 'index'])->name('index');
    Route::post('generate', [TimetableController::class, 'generate'])->name('generate');

    Route::prefix('{id}')->group(function () {
        Route::get('/', [TimetableController::class, 'show'])->name('show');
        Route::post('move', [TimetableController::class, 'move'])->name('move');
        Route::post('validate', [TimetableController::class, 'validateNow'])->name('validate');
        Route::get('workload', [TimetableController::class, 'workload'])->name('workload');
        Route::get('status', [TimetableController::class, 'status'])->name('status');
    });
});



