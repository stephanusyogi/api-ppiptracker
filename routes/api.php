<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\MontecarloController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\API\QuestionController;
use App\Http\Controllers\API\PengumumanController;

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

Route::get('/clear', function() {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('config:cache');
 
    return "Cleared!";
 });

// Authentication User
Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);

Route::get('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::group(['middleware' => ['auth:sanctum','verified']], function(){

    Route::apiResources([
        '/user' => UserController::class,
    ]);

    Route::post('/change-password', [UserController::class, 'changePassword']);

    Route::get('/check-data-empty/{id}', [UserController::class, 'checkDataEmpty']);

    Route::get('/check-kuisioner-empty/{id}', [UserController::class, 'checkKuisionerEmpty']);
    
    Route::get('/check-token-user', [UserController::class, 'checktoken']);

    Route::get('/montecarlo/{id}', [MontecarloController::class, 'montecarlo_count']);
    
    Route::post('/answer-question', [QuestionController::class, 'answer_question']);

    Route::get('/user-answer-question/{id}', [QuestionController::class, 'user_questions']);

    Route::get('/kuisioner', [QuestionController::class, 'get_kuisioner']);

    Route::post('/kuisioner-activity', [QuestionController::class, 'add_activity']);

    Route::get('/pengumuman', [PengumumanController::class, 'index']);

    // Setting
    Route::get('/setting-nilai-asumsi/user', [UserController::class, 'setting_nilai_asumsi']);
    Route::post('/setting-nilai-asumsi/user/add', [UserController::class, 'setting_nilai_asumsi_add']);
    
    Route::get('/setting-ppip/user', [UserController::class, 'setting_ppip']);
    Route::post('/setting-ppip/user/add', [UserController::class, 'setting_ppip_add']);
    
    Route::get('/setting-personal-lifecycle/user', [UserController::class, 'setting_personal_lifecycle']);
    Route::post('/setting-personal-lifecycle/user/add', [UserController::class, 'setting_personal_lifecycle_add']);
    
    Route::get('/setting-treatment/user', [UserController::class, 'setting_treatment']);
    Route::post('/setting-treatment/user/add', [UserController::class, 'setting_treatment_add']);
});

// Verifikasi Email
Route::post('/email/verify', [AuthController::class, 'sendVerificationEmail'])->middleware('auth:web,api');

Route::get('/email/checkverified', [AuthController::class, 'checkVerifiedEmail'])->middleware('auth:web,api');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
 
    return response()->json([
        'status' => true,
        'message' => 'Verified'
    ], 200);
})->middleware(['auth:web,api'])->name('verification.verify');

require __DIR__ . '/admin.php';