<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();
        $sort_field = $request->input('sort_field');
        $sort_order = $request->input('sort_order');
        $search = $request->input('search');
        $per_page = $request->input('per_page');

        if ($sort_field && $sort_order) {
            $query->orderBy($sort_field, $sort_order);
        }

        if($search){
            $query->where('name','LIKE','%'.$search.'%')
            ->orWhere('email','LIKE','%'.$search.'%')
            ->orWhere('nip','LIKE','%'.$search.'%')
            ->orWhere('tgl_lahir','LIKE','%'.$search.'%')
            ->orWhere('usia_pensiun','LIKE','%'.$search.'%');
        }

        $users = $query->latest()->paginate($per_page ? $per_page : 2);

        return new UserResource(true, 'List Data Users!', $users);
    }

    public function destroy(User $user)
    {
        //delete post
        $user->delete();

        //return response
        return new UserResource(true, 'Data User Berhasil Dihapus!', null);
    }

    //Get By Id
    public function show(User $user)
    {
        return new UserResource(true, 'Data User Ditemukan!', $user);
    }

    // Add User
    public function store(Request $request)
    {
        // Validasi Data
        $validator = Validator::make($request->all(),[
            'name' => 'required|string',
            'email' => 'required|email|string|unique:users',
            'password' => 'required|string',
            'nip' => 'required|string',
            'tgl_lahir' => 'required|string',
            'usia_pensiun' => 'required|string',
            'jenis_pensiun' => 'required|string',
            'no_hp' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'nip' => $request->nip,
            'tgl_lahir' => $request->tgl_lahir,
            'usia_pensiun' => $request->usia_pensiun,
            'jenis_pensiun' => $request->jenis_pensiun,
            'no_hp' => $request->no_hp,
            'return_cluster1' => 10,
            'return_cluster2' => 10,
            'return_cluster3' => 10,
            'return_cluster4' => 10,
            'return_cluster5' => 10,
            'return_cluster6' => 9,
            'return_cluster7' => 7,
        ]);
        
        $token = $user->createToken('token-auth')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Data pengguna baru berhasil ditambahkan',
            'data' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer'
        ]);
    }

    // Update User
    public function update(Request $request, User $user)
    {
        //define validation rules
        $validator = Validator::make($request->all(),[
            'name' => 'required|string',
            'email' => 'required|email|string',
            'nip' => 'required|string',
            'tgl_lahir' => 'required|date',
            'usia_pensiun' => 'required|integer',
            'jenis_pensiun' => 'required|string',
            'no_hp' => 'required|string',
            'return_cluster1' => 'required|string',
            'return_cluster2' => 'required|string',
            'return_cluster3' => 'required|string',
            'return_cluster4' => 'required|string',
            'return_cluster5' => 'required|string',
            'return_cluster6' => 'required|string',
            'return_cluster7' => 'required|string',
        ]);

        //check if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (password_get_info($request->password)['algoName'] !== 'unknown') {
            $newPassword = $request->password;
        }else{
            $newPassword = Hash::make($request->password);
        }

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $newPassword,
            'nip' => $request->nip,
            'tgl_lahir' => $request->tgl_lahir,
            'usia_pensiun' => $request->usia_pensiun,
            'jenis_pensiun' => $request->jenis_pensiun,
            'no_hp' => $request->no_hp,
            'gaji' => $request->gaji,
            'saldo_ppip' => $request->saldo_ppip,
            'return_cluster1' => $request->return_cluster1,
            'return_cluster2' => $request->return_cluster2,
            'return_cluster3' => $request->return_cluster3,
            'return_cluster4' => $request->return_cluster4,
            'return_cluster5' => $request->return_cluster5,
            'return_cluster6' => $request->return_cluster6,
            'return_cluster7' => $request->return_cluster7,
        ]);


        //return response
        return new UserResource(true, 'Data User Berhasil Diubah!', $user);
    }

    public function checkDataEmpty($id){
        $user = User::find($id);
        $empty_status = false;
        
        $data_check = array(
            'tgl_lahir' => $user['tgl_lahir'],
            'tgl_diangkat_pegawai' => $user['tgl_diangkat_pegawai'],
            'usia_diangkat_tahun' => $user['usia_diangkat_tahun'],
            'usia_diangkat_bulan' => $user['usia_diangkat_bulan'],
            'usia_pensiun' => $user['usia_pensiun'],
            'tgl_registrasi' => $user['tgl_registrasi'],
            'layer_ppmp' => $user['layer_ppmp'],
            'layer_ppip' => $user['layer_ppip'],
            'layer_personal' => $user['layer_personal'],
            'terdapat_investasi_pensiun' => $user['terdapat_investasi_pensiun'],
            'jumlah_investasi_keuangan' => $user['jumlah_investasi_keuangan'],
            'jumlah_investasi_properti' => $user['jumlah_investasi_properti'],
            'sewa_properti' => $user['sewa_properti'],
            'kenaikan_properti' => $user['kenaikan_properti'],
            'kenaikan_sewa' => $user['kenaikan_sewa'],
            'rencana_penambahan_saldo_bulan_ini' => $user['rencana_penambahan_saldo_bulan_ini'],
            'penambahan_saldo_tentative_personal_keuangan' => $user['penambahan_saldo_tentative_personal_keuangan'],
            'penambahan_saldo_tentative_personal_properti' => $user['penambahan_saldo_tentative_personal_properti'],
            'saldo_ppip' => $user['saldo_ppip'],
        );
        
        foreach ($data_check as $key => $value) {
            if ($value === null) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data Tidak Lengkap'
                ]);
            }
        }
        
        return response()->json([
            'status' => true,
            'message' => 'Data Lengkap'
        ]);

    }
    
    public function checkKuisionerEmpty($id){
        $check = DB::table('variabel_kuisioner_target_rr_answer')
                ->where('id_user', '=', $id)
                ->get()->toArray();
        die(var_dump($kuisioner));
    }

    public function checktoken(Request $request){
        if(Auth::guard('api')->check()){
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
}


            

