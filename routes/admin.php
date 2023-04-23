<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\QuestionController;
use App\Http\Controllers\API\SettingController;
use App\Http\Controllers\API\PengumumanController;

Route::post('/login-administrator', [AdminController::class, 'login']);

Route::group(['middleware' => ['auth:admin,api-admin']], function(){
    Route::apiResources(['/admin' => AdminController::class]);

    Route::apiResource('/question', QuestionController::class);

    Route::post('/admin/change-password', [AdminController::class, 'changePassword']);

    Route::get('/setting-nilai-asumsi/admin', [SettingController::class, 'setting_nilai_asumsi']);
    Route::post('/setting-nilai-asumsi/admin/update', [SettingController::class, 'setting_nilai_asumsi_update']);
    
    Route::get('/setting-ppip/admin', [SettingController::class, 'setting_ppip']);
    Route::get('/setting-ppip/admin/hitung-nilai', [SettingController::class, 'setting_ppip_hitung_nilai']);
    Route::post('/setting-ppip/admin/add', [SettingController::class, 'setting_ppip_add']);
    Route::post('/setting-ppip/admin/update', [SettingController::class, 'setting_ppip_update']);
    
    Route::get('/setting-personal-lifecycle/admin', [SettingController::class, 'setting_personal_lifecycle']);
    Route::post('/setting-personal-lifecycle/admin/add', [SettingController::class, 'setting_personal_lifecycle_add']);
    Route::post('/setting-personal-lifecycle/admin/update', [SettingController::class, 'setting_personal_lifecycle_update']);
    Route::post('/setting-personal-lifecycle/admin/buka-tutup-aset', [SettingController::class, 'setting_personal_lifecycle_bukatutup_aset']);

    Route::post('/pengumuman/add', [PengumumanController::class, 'store']);
    Route::post('/pengumuman/update', [PengumumanController::class, 'update']);
    Route::post('/pengumuman/delete', [PengumumanController::class, 'delete']);

    Route::get('/logout-administrator', [AdminController::class, 'logout']);
    Route::get('/check-token', [AdminController::class, 'checktoken']);
});

