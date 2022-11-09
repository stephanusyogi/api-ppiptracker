<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    //Get By Id
    public function getUserById($id)
    {
        $user = User::whereId($id)->first();

        if ($user) {
            return response()->json([
                'success' => true,
                'message' => 'Data User Ditemukan!',
                'data'    => $user
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'User Tidak Ditemukan!',
                'data'    => ''
            ], 401);
        }
    }
}
