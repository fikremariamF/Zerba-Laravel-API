<?php

use App\Http\Controllers\SprintController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\FoamController;
use App\Http\Controllers\CherkController;
use App\Http\Controllers\MyCostController;
use App\Http\Controllers\TsCostController;
use App\Http\Controllers\TotalController;
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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post("register", [ApiController::class, "register"]);
Route::post("login", [ApiController::class, "login"]);

Route::group([
    "middleware" => ["auth:api"]
], function () {

    Route::get("profile", [ApiController::class, "profile"]);
    Route::get("refresh", [ApiController::class, "refreshToken"]);
    Route::get("logout", [ApiController::class, "logout"]);

    Route::post("sprint", [SprintController::class, "store"]);
    Route::put("sprint/deactivate/{id}", [SprintController::class, "deactivate"]);
    Route::get("sprint", [SprintController::class, 'getSprints']);
    Route::get('/inactive-sprints', [SprintController::class, 'getInactiveSprints']);
    Route::get('/sprint-report/{sprintId}', [SprintController::class, 'getSprintData']);

    Route::get('/foams', [FoamController::class, 'index']);
    Route::put('/foams/{id}', [FoamController::class, 'update']);

    Route::get('/cherks', [CherkController::class, 'index']);
    Route::put('/cherks/{id}', [CherkController::class, 'update']);

    Route::get('/totals', [TotalController::class, 'index']);
    Route::put('/totals/{id}', [TotalController::class, 'update']);

    Route::get('/my-costs', [MyCostController::class, 'index']);
    Route::put('/my-costs/{id}', [MyCostController::class, 'update']);

    Route::get('/ts-costs', [TsCostController::class, 'index']);
    Route::put('/ts-costs/{id}', [TsCostController::class, 'update']);
});

