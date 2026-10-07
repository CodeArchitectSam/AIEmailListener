<?php

use App\Http\Controllers\Api\EmailController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/tasks', [TaskController::class, 'index']);
Route::get('/tasks/{task}', [TaskController::class, 'show']);
Route::patch('/tasks/{task}', [TaskController::class, 'update']);
Route::post('/emails/{email}/reprocess', [EmailController::class, 'reprocess']);