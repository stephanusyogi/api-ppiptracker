<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\QuestionController;


Route::post('/login-administrator', [AdminController::class, 'login']);

Route::group(['middleware' => ['auth:admin,api-admin']], function(){
    Route::apiResources(['/admin' => AdminController::class]);
    Route::apiResource('/question', QuestionController::class);

    Route::get('/logout-administrator', [AdminController::class, 'logout']);
    Route::get('/check-token', [AdminController::class, 'checktoken']);
});

