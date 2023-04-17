<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\QuestionController;
use App\Http\Controllers\API\SettingController;


Route::post('/login-administrator', [AdminController::class, 'login']);

Route::group(['middleware' => ['auth:admin,api-admin']], function(){
    Route::apiResources(['/admin' => AdminController::class]);
    Route::apiResource('/question', QuestionController::class);

    Route::get('/setting-nilai-asumsi/admin', [SettingController::class, 'setting_nilai_asumsi']);
    Route::post('/setting-nilai-asumsi/admin/update', [SettingController::class, 'setting_nilai_asumsi_update']);
    
    Route::get('/setting-ppip/admin', [SettingController::class, 'setting_ppip']);
    Route::post('/setting-ppip/admin/add', [SettingController::class, 'setting_ppip_add']);
    Route::post('/setting-ppip/admin/update', [SettingController::class, 'setting_ppip_update']);

    Route::get('/logout-administrator', [AdminController::class, 'logout']);
    Route::get('/check-token', [AdminController::class, 'checktoken']);
});

