<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\AttendanceController;
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
