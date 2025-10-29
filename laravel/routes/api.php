<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/timetable/generate', [\App\Http\Controllers\TimetableController::class,'generate']);
Route::get('/timetable/{id}', [\App\Http\Controllers\TimetableController::class,'show']);
