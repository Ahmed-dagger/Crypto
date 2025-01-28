<?php

use App\Http\Controllers\API\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::group(['name' => 'App\Http\Controllers\Api' ] , function() {

    Route::apiResource('users' , UserController::class);
    Route::post('auth',[UserController::class , 'auth']);
});

