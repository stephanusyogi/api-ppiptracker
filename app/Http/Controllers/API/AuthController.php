<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|string|unique:users',
            'password' => 'required|string',
            // 'nip' => 'required|string',
            // 'tgl_lahir' => 'required|date',
            // 'usia_pensiun' => 'required|integer',
            // 'jenis_pensiun' => 'required|string',
            // 'no_hp' => 'required|string',
            // 'return_cluster1' => 'required|string',
            // 'return_cluster2' => 'required|string',
            // 'return_cluster3' => 'required|string',
            // 'return_cluster4' => 'required|string',
            // 'return_cluster5' => 'required|string',
            // 'return_cluster6' => 'required|string',
            // 'return_cluster7' => 'required|string',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            // 'nip' => $request->nip,
            // 'tgl_lahir' => $request->tgl_lahir,
            // 'usia_pensiun' => $request->usia_pensiun,
            // 'jenis_pensiun' => $request->jenis_pensiun,
            // 'no_hp' => $request->no_hp,
            // 'return_cluster1' => $request->return_cluster1,
            // 'return_cluster2' => $request->return_cluster2,
            // 'return_cluster3' => $request->return_cluster3,
            // 'return_cluster4' => $request->return_cluster4,
            // 'return_cluster5' => $request->return_cluster5,
            // 'return_cluster6' => $request->return_cluster6,
            // 'return_cluster7' => $request->return_cluster7,
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
        }

        $request->user()->sendEmailVerificationNotification();
        
        return[
            'status' => true,
            'message' => 'Sending Verification Email'
        ];
    }

    public function checkVerifiedEmail(Request $request)
    {
        if($request->user()->hasVerifiedEmail()){
            return[
                'status' => true,
                'message' => 'Already Verified'
            ];
        }else{
            return[
                'status' => false,
                'message' => 'Not Verified'
            ];
        }
        
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email|string',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt(
            $request->only('email', 'password')
        )) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ]);
        }

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
        Auth::user()->tokens()->delete();
        return response()->json([
            'message' => 'Logout Success'
        ],200);
    }
}

