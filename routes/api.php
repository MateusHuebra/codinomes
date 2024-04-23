<?php

use App\Http\Controllers\PacksController;
use Illuminate\Http\Request;
use App\Http\Controllers\CodinomesController;
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

Route::post('/bot/listen', [CodinomesController::class, 'listen']);

Route::get('/packs/get', [PacksController::class, 'get']);
Route::get('/packs/getall', [PacksController::class, 'getAll']);
Route::delete('/packs/delete/{id}', [PacksController::class, 'delete']);
Route::post('/packs/save', [PacksController::class, 'save']);

/*
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
*/