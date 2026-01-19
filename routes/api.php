<?php

use App\Http\Controllers\Api\CenterController;
use App\Http\Controllers\Api\ProvinceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Province API Routes
Route::prefix('provinces')->group(function () {
    Route::get('/', [ProvinceController::class, 'index']);
    Route::get('/{id}/centers', [ProvinceController::class, 'centers']);
});

// Center API Routes
Route::prefix('centers')->group(function () {
    Route::get('/', [CenterController::class, 'index']);
    Route::get('/by-province/{provinceId}', [CenterController::class, 'byProvince']);
});
