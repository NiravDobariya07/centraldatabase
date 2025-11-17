<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ListFlexController;
use App\Http\Middleware\RequestResponseLoggerMiddleware;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::match(['get', 'post'], '/listflex-webhook', [ListFlexController::class, 'getListflexData'])->middleware([RequestResponseLoggerMiddleware::class]);
