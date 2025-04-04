<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::middleware('auth.token')->group(function () {
    Route::get('user-profile', [UserController::class, 'profile']);
});

Route::apiResource('users', UserController::class);
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');


Route::get('/attendances', [AttendanceController::class, 'index']);
Route::get('/attendances/user/{id}', [AttendanceController::class, 'getAttendanceByUserId']);
Route::post('/attendance', [AttendanceController::class, 'store']);
Route::get('attendances/{id}', [AttendanceController::class, 'show']);


Route::get('/notification', [NotificationController::class, 'index']);
Route::get('/notification/user/{user_id}', [NotificationController::class, 'getByUserId']);




Route::get('/check-timezone', function () {
    return [
        'Laravel Timezone' => config('app.timezone'),
        'Server Timezone' => date_default_timezone_get(),
        'Current Time' => now()->toDateTimeString(),
    ];
});
