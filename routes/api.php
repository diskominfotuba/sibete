<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IzinBelajarController;

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

Route::post('/login', API\Auth\LoginController::class);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/status/user', [Api\TestController::class, 'index']);


Route::prefix('v1')->group(function () {
    Route::apiResource('pemohonan', IzinBelajarController::class);
});


Route::get('/test', function () {
    return response()->json([
        'status' => true,
        'message' => 'Test API',
    ]);
});
