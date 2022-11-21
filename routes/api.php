<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

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

Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);

Route::group(['middleware' => ['auth:sanctum', 'verified']], function(){
    Route::get('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/user/{id?}', [UserController::class, 'getUserById']);

});


// Verifikasi Email
Route::get('/email/verify', [AuthController::class, 'sendVerificationEmail'])->middleware('auth:sanctum');

Route::get('/email/checkverified', [AuthController::class, 'checkVerifiedEmail'])->middleware('auth:sanctum');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
 
    return response()->json([
        'status' => true,
        'message' => 'Verified'
    ], 200);
})->middleware(['auth:sanctum', 'signed'])->name('verification.verify');

