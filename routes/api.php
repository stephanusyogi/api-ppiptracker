<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\MontecarloController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\API\QuestionController;

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