<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Validasi Data
        $validator = Validator::make($request->all(),[
            'nama' => 'required|string',
            'email' => 'required|email|string|unique:users',
            'password' => 'required|string',
            'no_hp' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        
        $user = User::create([
            'nama' => $request->nama,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'no_hp' => $request->no_hp,
            'tgl_registrasi' => $date = date('Y-m-d'),
            'inactive' => 0,
        ]);

        $token = $user->createToken('token-auth')->plainTextToken;

        return response()->json([
            'data' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer'
        ]);
    }

    public function sendVerificationEmail(Request $request)
    {
        if($request->user()->hasVerifiedEmail()){
            return[
                'status' => false,
                'message' => 'Already Verified'
            ];
        }else{
            $request->user()->sendEmailVerificationNotification();
            
            return[
                'status' => true,
                'message' => 'Sending Verification Email'
            ];
        }
    }

    public function checkVerifiedEmail(Request $request)
    {
        // if($request->user()->hasVerifiedEmail()){
        //     return[
        //         'status' => true,
        //         'message' => 'Already Verified'
        //     ];
        // }else{
        //     return[
        //         'status' => false,
        //         'message' => 'Not Verified'
        //     ];
        // }
            return[
                'user' =>  $request->user()
            ];
        
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email|string',
            'password' => 'required|string',
        ]);

        if (!Auth::guard('web')->attempt(
            $request->only('email', 'password')
        )) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ]);
        }
        
        // if(auth('web')->check()){
        //     Auth::guard('web')->user()->tokens()->delete();
        // }
        Auth::guard('api')->user()->tokens()->delete();

        $user = User::where('email', $request->email)->firstOrFail();

        $token = $user->createToken('token-auth')->plainTextToken;
        
        return response()->json([
            'status' => true,
            'data' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer'
        ]);
    }


    public function logout(Request $request)
    {
        Auth::guard('api')->user()->tokens()->delete();
        return response()->json([
            'status' => true,
            'message' => 'Logout Success'
        ],200);
    }
}

