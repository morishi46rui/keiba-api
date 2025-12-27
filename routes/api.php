<?php

use App\Http\Controllers\Api\SampleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// サンプルエンドポイント
Route::get('/sample', [SampleController::class, 'index']);
Route::post('/sample', [SampleController::class, 'store']);
