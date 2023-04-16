<?php

namespace App\Http\Controllers\API;

use App\Models\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\AdminResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    protected function guard()
    {
        return Auth::guard('admin');
    }
    public function index(Request $request)
    {
        $query = Admin::query();
        $res = Admin::all();
        $sort_field = $request->input('sort_field');
        $sort_order = $request->input('sort_order');
        $search = $request->input('search');
        $per_page = $request->input('per_page');

        if ($sort_field && $sort_order) {
            $query->orderBy($sort_field, $sort_order);
        }

        if($search){
            $query->where('name','LIKE','%'.$search.'%')
            ->orWhere('username','LIKE','%'.$search.'%')
            ->orWhere('nip','LIKE','%'.$search.'%');
        }

        $admins = $query->latest()->paginate($per_page ? $per_page : 2);

        return new AdminResource(true, 'List Data Admins!', $res);
    }

    public function checktoken(Request $request){
        if(Auth::guard('api-admin')->check()){
            return response()->json([
                "status" =>true,
                "message"=>"Authenticated"
            ],200);
        }else{
            return response()->json([
                "status" =>false,
                "message"=>"Unauthenticated"
            ],200);
        }
    }

    public function destroy(Admin $admin)
    {
        //delete post
        $admin->delete();

        //return response
        return new AdminResource(true, 'Data Admin Berhasil Dihapus!', null);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'string',
            'username'     => 'required|string|unique:admins',
            'nip'   => 'required',
            'password'   => 'required'
        ]);


        //check if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $admin = Admin::create([
            'name'     => $request->name,
            'username'     => $request->username,
            'nip'   => $request->nip,
            'password'   =>  Hash::make($request->password),
        ]);
        
        //return response
        return new AdminResource(true, 'Data Admin Berhasil Ditambahkan!', $admin);
    }
    
    public function show(Admin $admin)
    {
        return new AdminResource(true, 'Data Admin Ditemukan!', $admin);
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required',
        ]);
        if (!Auth::guard('admin')->attempt($request->only('username', 'password'))) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ]);
        }
        
        if(Auth::guard('admin')->check()){
            Auth::guard('admin')->user()->tokens()->delete();
        }

        $admin = Admin::where('username', $request->username)->firstOrFail();

        $token = $admin->createToken('token-auth')->plainTextToken;
        
        return response()->json([
            'status' => true,
            'data' => $admin,
            'access_token' => $token,
            'token_type' => 'Bearer'
        ]);
    }
    
    public function logout(Request $request)
    {
        Auth::guard('api-admin')->user()->tokens()->delete();
        return response()->json([
            'status' => true,
            'message' => 'Logout Success'
        ],200);
    }
}
