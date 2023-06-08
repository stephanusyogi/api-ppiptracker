<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        DB::table('activity_update_biodata')->insert([
            'id' => (string) Str::uuid(),
            'id_user' => $request->id_user,
            'browser' => $request->browser,
            'sistem_operasi' => $request->sistem_operasi,
            'ip_address' => $request->ip_address,
        ]);

        if (password_get_info($request->password)['algoName'] !== 'unknown') {
            $newPassword = $request->password;
        }else{
            $newPassword = Hash::make($request->password);
        }

        $user->update([
            'nama' => $request->nama,
            'email' => $request->email,
            'password' => $newPassword,
            'no_hp' => $request->no_hp,
            'nip' => $request->nip,
            'satker' => $request->satker,
            'tgl_lahir' => $request->tgl_lahir,
            'tgl_diangkat_pegawai' => $request->tgl_diangkat_pegawai,
            'usia_diangkat_tahun' => $request->usia_diangkat_tahun,
            'usia_diangkat_bulan' => $request->usia_diangkat_bulan,
            'usia_pensiun' => $request->usia_pensiun,
            'tgl_registrasi' => $request->tgl_registrasi,
            'layer_ppmp' => $request->layer_ppmp,
            'layer_ppip' => $request->layer_ppip,
            'layer_personal' => $request->layer_personal,
            'terdapat_investasi_pensiun' => $request->terdapat_investasi_pensiun,
            'jumlah_investasi_keuangan' => $request->jumlah_investasi_keuangan,
            'jumlah_investasi_properti' => $request->jumlah_investasi_properti,
            'sewa_properti' => $request->sewa_properti,
            'kenaikan_properti' => $request->kenaikan_properti,
            'kenaikan_sewa' => $request->kenaikan_sewa,
            'rencana_penambahan_saldo_bulan_ini' => $request->rencana_penambahan_saldo_bulan_ini,
            'penambahan_saldo_tentative_personal_keuangan' => $request->penambahan_saldo_tentative_personal_keuangan,
            'penambahan_saldo_tentative_personal_properti' => $request->penambahan_saldo_tentative_personal_properti,
            'saldo_ppip' => $request->saldo_ppip,
        ]);

        //return response
        return new UserResource(true, 'Data User Berhasil Diubah!', $user);
    }

    // Ubah Password
    public function changePassword(Request $request){
        
        DB::table('activity_ubah_password')->insert([
            'id' => (string) Str::uuid(),
            'id_user' => $request->id_user,
            'browser' => $request->browser,
            'sistem_operasi' => $request->sistem_operasi,
            'ip_address' => $request->ip_address,
        ]);

        User::where('id', $request->id_user)->update(['password' => Hash::make($request->new_password)]);

        return response()->json([
            'status' => true,
            'message' => 'Passsword Diperbarui!'
        ]);
    }

    // Check Data Biodata Empty
    public function checkDataEmpty($id){
        $user = User::find($id);
        $empty_status = false;
        
        $data_check = array(
            'nip' => $user['nip'],
            'satker' => $user['satker'],
            'tgl_lahir' => $user['tgl_lahir'],
            'tgl_diangkat_pegawai' => $user['tgl_diangkat_pegawai'],
            'usia_diangkat_tahun' => $user['usia_diangkat_tahun'],
            'usia_diangkat_bulan' => $user['usia_diangkat_bulan'],
            'usia_pensiun' => $user['usia_pensiun'],
            'tgl_registrasi' => $user['tgl_registrasi'],
            'layer_ppmp' => $user['layer_ppmp'],
            'layer_ppip' => $user['layer_ppip'],
            'layer_personal' => $user['layer_personal'],
            'rencana_penambahan_saldo_bulan_ini' => $user['rencana_penambahan_saldo_bulan_ini'],
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
    
    // Check Data Kuisioner Empty
    public function checkKuisionerEmpty($id){
        $check = DB::table('variabel_kuisioner_target_rr_answer')
                ->where([
                    ['id_user', '=', $id],
                ])
                ->get()->toArray();
     
        if (count($check) > 0) {
            return response()->json([
                'status' => true,
                'message' => 'Kuisioner Terisi Lengkap'
            ]);
        }else{
            return response()->json([
                'status' => false,
                'message' => 'Kuisioner Tidak Lengkap'
            ]);
            
        }
    }

    // Check Token Availability
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

    // Tracking Data
    public function tracking_data(Request $request){
        $id_user = $request->input('id_user');
        $tracking_data = DB::table('users_update_tracking_data')
        ->where('id_user', $id_user)
        ->where('flag', 1)
        ->select('*')->get();
        
        return response()->json([
            "status" =>true,
            "message"=>"Tracking Data User!",
            "data" => $tracking_data
        ],200);    
    }
    public function update_tracking_data(Request $request){
        $id_user = $request->input('id_user');
        
        if($request->saldo_ppip !== ""){
            DB::table('users')
                ->where('id_user', $id_user)  // find your user by their email
                ->update(array('saldo_ppip' => $request->saldo_ppip));  // update the record in the DB. 
        }

        // Add Activity
        DB::table('activity_update_tracking_data')->insert([
            'id' => (string) Str::uuid(),
            'id_user' => $id_user,
            'browser' => $request->browser,
            'sistem_operasi' => $request->sistem_operasi,
            'ip_address' => $request->ip_address,
        ]);

        // Ubah Flag Data Terbaru ke => 0
        DB::table('users_update_tracking_data')
        ->where('id_user', $id_user)
        ->where('flag', 1)
        ->update([
            'flag' => 0,
        ]);

        // Tambahkan Data Baru
        DB::table('users_update_tracking_data')->insert([
            'id' => (string) Str::uuid(),
            'id_user' => $id_user,
            'saldo_ppip' => $request->saldo_ppip,
            'saldo_personal_pasar_keuangan' => $request->saldo_personal_pasar_keuangan,
            'saldo_personal_properti' => $request->saldo_personal_properti,
            'penambahan_saldo_ppip' => $request->penambahan_saldo_ppip,
            'penambahan_saldo_personal_keuangan' => $request->penambahan_saldo_personal_keuangan,
            'penambahan_saldo_personal_properti' => $request->penambahan_saldo_personal_properti,
            'flag' => 1,
        ]);
        
        return response()->json([
            "status" =>true,
            "message"=>"Tracking Data User Updated!",
        ],200);    
    }

    // Setting Nilai Asumsi
    public function setting_nilai_asumsi(Request $request){
        $id_user = $request->input('id_user');

        if ($id_user) {
            $setting_nilai_asumsi_user = DB::table('nilai_asumsi_user')
            ->where('id_user', $id_user)
            ->where('flag', 1)
            ->select('*')->get();
            
            return response()->json([
                "status" =>true,
                "message"=>"Setting Nilai Asumsi User!",
                "data" => $setting_nilai_asumsi_user
            ],200);    
        }else{
            $setting_nilai_asumsi = DB::table('nilai_asumsi_admin')
            ->select('*')->get();
            
            return response()->json([
                "status" =>true,
                "message"=>"Lists Setting Nilai Asumsi!",
                "data" => $setting_nilai_asumsi
            ],200);
        }

    }
    public function setting_nilai_asumsi_add(Request $request){
        $id_user = $request->input('id_user');
        
        // Add Activity
        DB::table('activity_setting_nilai_asumsi')->insert([
            'id' => (string) Str::uuid(),
            'id_user' => $id_user,
            'browser' => $request->browser,
            'sistem_operasi' => $request->sistem_operasi,
            'ip_address' => $request->ip_address,
        ]);

        // Ubah Flag Data Terbaru ke => 0
        DB::table('nilai_asumsi_user')
        ->where('id_user', $id_user)
        ->where('flag', 1)
        ->update([
            'flag' => 0,
        ]);

        // Tambahkan Data Baru
        DB::table('nilai_asumsi_user')->insert([
            'id' => (string) Str::uuid(),
            'id_user' => $id_user,
            'kenaikan_gaji' => $request->kenaikan_gaji,
            'kenaikan_phdp' => $request->kenaikan_phdp,
            'iuran_ppip' => $request->iuran_ppip,
            'tambahan_iuran' => $request->tambahan_iuran,
            'dasar_pembayaran_iuran_personal' => $request->dasar_pembayaran_iuran_personal,
            'jumlah_pembayaran_iuran_personal' => $request->jumlah_pembayaran_iuran_personal,
            'kenaikan_iuran_personal' => $request->kenaikan_iuran_personal,
            'inflasi_jangka_panjang' => $request->inflasi_jangka_panjang,
            'flag' => 1,
        ]);
        
        return response()->json([
            "status" =>true,
            "message"=>"Setting Nilai Asumsi User Updated!",
        ],200);    
    }

    // Setting PPIP
    public function setting_ppip(Request $request){
        $id_user = $request->input('id_user');
        $id_investasi = $request->input('id_investasi');

        if ($id_user) {
            $opsi = DB::table('setting_portofolio_ppip_admin')
            ->select('id','nama_portofolio')->where('flag', 1)->get();

            $setting_ppip_user = DB::table('setting_portofolio_ppip')->select('*')
            ->where('id_user', $id_user)
            ->where('flag', 1)
            ->get();
        
            return response()->json([
                "status" =>true,
                "message"=>"Setting PPIP User!",
                "opsi" => $opsi,
                "data" => $setting_ppip_user
            ],200);
        } elseif ($id_investasi) {
            $opsi = DB::table('setting_portofolio_ppip_admin')
            ->select('id','nama_portofolio')->get();

            $setting_ppip = DB::table('setting_portofolio_ppip_admin')
            ->select('*')->where('id', $id_investasi)->get();
        
            return response()->json([
                "status" =>true,
                "message"=>"Lists Setting PPIP!",
                "opsi" => $opsi,
                "data" => $setting_ppip
            ],200);
        } else {
            $opsi = DB::table('setting_portofolio_ppip_admin')
            ->select('id','nama_portofolio')->get();
            $setting_ppip = DB::table('setting_portofolio_ppip_admin')
            ->select('*')->get();
        
            return response()->json([
                "status" =>true,
                "message"=>"Lists Setting PPIP!",
                "opsi" => $opsi,
                "data" => $setting_ppip
            ],200);
        }   
    }
    public function setting_ppip_add(Request $request){
        $id_user = $request->input('id_user');
        $id_investasi = $request->input('id_investasi');

        // Add Activity
        DB::table('activity_setting_ppip')->insert([
            'id' => (string) Str::uuid(),
            'id_user' => $id_user,
            'browser' => $request->browser,
            'sistem_operasi' => $request->sistem_operasi,
            'ip_address' => $request->ip_address,
        ]);
        
        // Ubah Flag Data Terbaru ke => 0
        DB::table('setting_portofolio_ppip')
        ->where('id_user', $id_user)
        ->where('flag', 1)
        ->update([
            'flag' => 0,
        ]);

        // Tambahkan Data Baru
        DB::table('setting_portofolio_ppip')->insert([
            'id' => (string) Str::uuid(),
            'id_user' => $id_user,
            'id_setting_portofolio_ppip_admin' => $id_investasi,
            'nama_pilihan' => $request->nama_pilihan,
            'return_saham' => $request->return_saham,
            'return_pendapatan_tetap' => $request->return_pendapatan_tetap,
            'return_deposito' => $request->return_deposito,
            'resiko_saham' => $request->resiko_saham,
            'resiko_pendapatan_tetap' => $request->resiko_pendapatan_tetap,
            'resiko_deposito' => $request->resiko_deposito,
            'korelasi_saham_pendapatan_tetap' => $request->korelasi_saham_pendapatan_tetap,
            'korelasi_saham_deposito' => $request->korelasi_saham_deposito,
            'korelasi_pendapatan_tetap_deposito' => $request->korelasi_pendapatan_tetap_deposito,
            'tranche_investasi_saham' => $request->tranche_investasi_saham,
            'tranche_investasi_pendapatan_tetap' => $request->tranche_investasi_pendapatan_tetap,
            'tranche_investasi_deposito' => $request->tranche_investasi_deposito,
            'tranche_likuiditas_saham' => $request->tranche_likuiditas_saham,
            'tranche_likuiditas_pendapatan_tetap' => $request->tranche_likuiditas_pendapatan_tetap,
            'tranche_likuiditas_deposito' => $request->tranche_likuiditas_deposito,
            'return_portofolio_tranche_investasi'=> $request->return_portofolio_tranche_investasi,
            'return_portofolio_tranche_likuiditas'=> $request->return_portofolio_tranche_likuiditas,
            'resiko_portofolio_tranche_investasi'=> $request->resiko_portofolio_tranche_investasi,
            'resiko_portofolio_tranche_likuiditas'=> $request->resiko_portofolio_tranche_likuiditas,
            'flag' => 1,
        ]);
        
        return response()->json([
            "status" =>true,
            "message"=>"Setting PPIP User Updated!",
        ],200);    
    }

    // Setting Personal LifeCycle
    public function setting_personal_lifecycle(Request $request){
        $id_user = $request->input('id_user');
        $id_investasi = $request->input('id_investasi');

        if ($id_user) {
            $opsi = DB::table('setting_portofolio_personal_admin')
            ->select('id','nama')->where('flag', 1)->get();

            $setting_personal_lifecycle = array();
            // Personal Keuangan
            $setting_personal_user = DB::table('setting_portofolio_personal')->select('*')
            ->where('id_user', $id_user)
            ->where('flag', 1)
            ->get();

            // Lifecycle
            $setting_lifecycle_user = DB::table('setting_komposisi_investasi_lifecycle_fund')->select('*')
            ->where('id_user', $id_user)
            ->where('flag', 1)
            ->get();

            $setting_personal_lifecycle["personal_keuangan"] = $setting_personal_user;
            $setting_personal_lifecycle["komposisi_investasi"] = $setting_lifecycle_user;
        
            return response()->json([
                "status" =>true,
                "message"=>"Setting Personal LifeCyecle User!",
                "opsi" => $opsi,
                "data" => $setting_personal_lifecycle
            ],200);
        } elseif ($id_investasi) {
            $opsi = DB::table('setting_portofolio_personal_admin')
            ->select('id','nama')->get();

            $setting_personal_lifecycle = array();
            // Personal Keuangan
            $setting_personal = DB::table('setting_portofolio_personal_admin')
            ->select('*')->where('id', $id_investasi)->get();
            
            // Lifecycle
            $setting_lifecycle = DB::table('setting_komposisi_investasi_lifecycle_fund_admin')
            ->select('*')->where('id_setting_portofolio_personal_admin', $id_investasi)->get();

            $setting_personal_lifecycle["personal_keuangan"] = $setting_personal;
            $setting_personal_lifecycle["komposisi_investasi"] = $setting_lifecycle;
        
            return response()->json([
                "status" =>true,
                "message"=>"Lists Setting Personal LifeCycle!",
                "opsi" => $opsi,
                "data" => $setting_personal_lifecycle
            ],200);
        } else {
            $opsi = DB::table('setting_portofolio_personal_admin')
            ->select('id','nama')->get();

            $setting_personal_lifecycle = array();
            // Personal Keuangan
            $setting_personal = DB::table('setting_portofolio_personal_admin')
            ->select('*')->get();

            // LifeCycle
            $setting_lifecycle = DB::table('setting_komposisi_investasi_lifecycle_fund_admin')
            ->select('*')->get();

            $setting_personal_lifecycle["personal_keuangan"] = $setting_personal;
            $setting_personal_lifecycle["komposisi_investasi"] = $setting_lifecycle;
        
            return response()->json([
                "status" =>true,
                "message"=>"Lists Setting Personal LifeCycle!",
                "opsi" => $opsi,
                "data" => $setting_personal_lifecycle
            ],200);
        }  
    }
    public function setting_personal_lifecycle_add(Request $request){
        $id_user = $request->input('id_user');
        $id_investasi = $request->input('id_investasi');

        // Add Activity
        DB::table('activity_setting_personal_keuangan')->insert([
            'id' => (string) Str::uuid(),
            'id_user' => $id_user,
            'browser' => $request->browser,
            'sistem_operasi' => $request->sistem_operasi,
            'ip_address' => $request->ip_address,
        ]);
        
        // Ubah Flag Data Terbaru ke => 0
        DB::table('setting_portofolio_personal')
        ->where('id_user', $id_user)
        ->where('flag', 1)
        ->update([
            'flag' => 0,
        ]);
        DB::table('setting_komposisi_investasi_lifecycle_fund')
        ->where('id_user', $id_user)
        ->where('flag', 1)
        ->update([
            'flag' => 0,
        ]);

        // Tambahkan Data Baru
        // Personal Keuangan
        DB::table('setting_portofolio_personal')->insert([
            'id' => (string) Str::uuid(),
            'id_user' => $id_user,
            'id_setting_portofolio_personal_admin' => $id_investasi,
            'nama' => $request->nama,
            'return_s_tranche1' => $request->return_s_tranche1,
            'return_s_tranche2' => $request->return_s_tranche2,
            'return_s_tranche3' => $request->return_s_tranche3,
            'return_pt_tranche1' => $request->return_pt_tranche1,
            'return_pt_tranche2' => $request->return_pt_tranche2,
            'return_pt_tranche3' => $request->return_pt_tranche3,
            'return_d_tranche1' => $request->return_d_tranche1,
            'return_d_tranche2' => $request->return_d_tranche2,
            'return_d_tranche3' => $request->return_d_tranche3,
            'return_r_s_tranche1' => $request->return_r_s_tranche1,
            'return_r_s_tranche2' => $request->return_r_s_tranche2,
            'return_r_s_tranche3' => $request->return_r_s_tranche3,
            'return_r_pt_tranche1' => $request->return_r_pt_tranche1,
            'return_r_pt_tranche2' => $request->return_r_pt_tranche2,
            'return_r_pt_tranche3' => $request->return_r_pt_tranche3,
            'return_r_pu_tranche1' => $request->return_r_pu_tranche1,
            'return_r_pu_tranche2' => $request->return_r_pu_tranche2,
            'return_r_pu_tranche3' => $request->return_r_pu_tranche3,
            'return_r_c_tranche1' => $request->return_r_c_tranche1,
            'return_r_c_tranche2' => $request->return_r_c_tranche2,
            'return_r_c_tranche3' => $request->return_r_c_tranche3,
            'resiko_s_tranche1' => $request->resiko_s_tranche1,
            'resiko_s_tranche2' => $request->resiko_s_tranche2,
            'resiko_s_tranche3' => $request->resiko_s_tranche3,
            'resiko_pt_tranche1' => $request->resiko_pt_tranche1,
            'resiko_pt_tranche2' => $request->resiko_pt_tranche2,
            'resiko_pt_tranche3' => $request->resiko_pt_tranche3,
            'resiko_d_tranche1' => $request->resiko_d_tranche1,
            'resiko_d_tranche2' => $request->resiko_d_tranche2,
            'resiko_d_tranche3' => $request->resiko_d_tranche3,
            'resiko_r_s_tranche1' => $request->resiko_r_s_tranche1,
            'resiko_r_s_tranche2' => $request->resiko_r_s_tranche2,
            'resiko_r_s_tranche3' => $request->resiko_r_s_tranche3,
            'resiko_r_pt_tranche1' => $request->resiko_r_pt_tranche1,
            'resiko_r_pt_tranche2' => $request->resiko_r_pt_tranche2,
            'resiko_r_pt_tranche3' => $request->resiko_r_pt_tranche3,
            'resiko_r_pu_tranche1' => $request->resiko_r_pu_tranche1,
            'resiko_r_pu_tranche2' => $request->resiko_r_pu_tranche2,
            'resiko_r_pu_tranche3' => $request->resiko_r_pu_tranche3,
            'resiko_r_c_tranche1' => $request->resiko_r_c_tranche1,
            'resiko_r_c_tranche2' => $request->resiko_r_c_tranche2,
            'resiko_r_c_tranche3' => $request->resiko_r_c_tranche3,
            'korelasi_s_pt_tranche1' => $request->korelasi_s_pt_tranche1,
            'korelasi_s_pt_tranche2' => $request->korelasi_s_pt_tranche2,
            'korelasi_s_pt_tranche3' => $request->korelasi_s_pt_tranche3,
            'korelasi_s_d_tranche1' => $request->korelasi_s_d_tranche1,
            'korelasi_s_d_tranche2' => $request->korelasi_s_d_tranche2,
            'korelasi_s_d_tranche3' => $request->korelasi_s_d_tranche3,
            'korelasi_s_r_s_tranche1' => $request->korelasi_s_r_s_tranche1,
            'korelasi_s_r_s_tranche2' => $request->korelasi_s_r_s_tranche2,
            'korelasi_s_r_s_tranche3' => $request->korelasi_s_r_s_tranche3,
            'korelasi_s_r_pt_tranche1' => $request->korelasi_s_r_pt_tranche1,
            'korelasi_s_r_pt_tranche2' => $request->korelasi_s_r_pt_tranche2,
            'korelasi_s_r_pt_tranche3' => $request->korelasi_s_r_pt_tranche3,
            'korelasi_s_r_pu_tranche1' => $request->korelasi_s_r_pu_tranche1,
            'korelasi_s_r_pu_tranche2' => $request->korelasi_s_r_pu_tranche2,
            'korelasi_s_r_pu_tranche3' => $request->korelasi_s_r_pu_tranche3,
            'korelasi_s_r_c_tranche1' => $request->korelasi_s_r_c_tranche1,
            'korelasi_s_r_c_tranche2' => $request->korelasi_s_r_c_tranche2,
            'korelasi_s_r_c_tranche3' => $request->korelasi_s_r_c_tranche3,
            'korelasi_pt_d_tranche1' => $request->korelasi_pt_d_tranche1,
            'korelasi_pt_d_tranche2' => $request->korelasi_pt_d_tranche2,
            'korelasi_pt_d_tranche3' => $request->korelasi_pt_d_tranche3,
            'korelasi_pt_r_s_tranche1' => $request->korelasi_pt_r_s_tranche1,
            'korelasi_pt_r_s_tranche2' => $request->korelasi_pt_r_s_tranche2,
            'korelasi_pt_r_s_tranche3' => $request->korelasi_pt_r_s_tranche3,
            'korelasi_pt_r_pt_tranche1' => $request->korelasi_pt_r_pt_tranche1,
            'korelasi_pt_r_pt_tranche2' => $request->korelasi_pt_r_pt_tranche2,
            'korelasi_pt_r_pt_tranche3' => $request->korelasi_pt_r_pt_tranche3,
            'korelasi_pt_r_pu_tranche1' => $request->korelasi_pt_r_pu_tranche1,
            'korelasi_pt_r_pu_tranche2' => $request->korelasi_pt_r_pu_tranche2,
            'korelasi_pt_r_pu_tranche3' => $request->korelasi_pt_r_pu_tranche3,
            'korelasi_pt_r_c_tranche1' => $request->korelasi_pt_r_c_tranche1,
            'korelasi_pt_r_c_tranche2' => $request->korelasi_pt_r_c_tranche2,
            'korelasi_pt_r_c_tranche3' => $request->korelasi_pt_r_c_tranche3,
            'korelasi_d_r_s_tranche1' => $request->korelasi_d_r_s_tranche1,
            'korelasi_d_r_s_tranche2' => $request->korelasi_d_r_s_tranche2,
            'korelasi_d_r_s_tranche3' => $request->korelasi_d_r_s_tranche3,
            'korelasi_d_r_pt_tranche1' => $request->korelasi_d_r_pt_tranche1,
            'korelasi_d_r_pt_tranche2' => $request->korelasi_d_r_pt_tranche2,
            'korelasi_d_r_pt_tranche3' => $request->korelasi_d_r_pt_tranche3,
            'korelasi_d_r_pu_tranche1' => $request->korelasi_d_r_pu_tranche1,
            'korelasi_d_r_pu_tranche2' => $request->korelasi_d_r_pu_tranche2,
            'korelasi_d_r_pu_tranche3' => $request->korelasi_d_r_pu_tranche3,
            'korelasi_d_r_c_tranche1' => $request->korelasi_d_r_c_tranche1,
            'korelasi_d_r_c_tranche2' => $request->korelasi_d_r_c_tranche2,
            'korelasi_d_r_c_tranche3' => $request->korelasi_d_r_c_tranche3,
            'korelasi_r_s_r_pt_tranche1' => $request->korelasi_r_s_r_pt_tranche1,
            'korelasi_r_s_r_pt_tranche2' => $request->korelasi_r_s_r_pt_tranche2,
            'korelasi_r_s_r_pt_tranche3' => $request->korelasi_r_s_r_pt_tranche3,
            'korelasi_r_s_r_pu_tranche1' => $request->korelasi_r_s_r_pu_tranche1,
            'korelasi_r_s_r_pu_tranche2' => $request->korelasi_r_s_r_pu_tranche2,
            'korelasi_r_s_r_pu_tranche3' => $request->korelasi_r_s_r_pu_tranche3,
            'korelasi_r_s_r_c_tranche1' => $request->korelasi_r_s_r_c_tranche1,
            'korelasi_r_s_r_c_tranche2' => $request->korelasi_r_s_r_c_tranche2,
            'korelasi_r_s_r_c_tranche3' => $request->korelasi_r_s_r_c_tranche3,
            'korelasi_r_pt_r_pu_tranche1' => $request->korelasi_r_pt_r_pu_tranche1,
            'korelasi_r_pt_r_pu_tranche2' => $request->korelasi_r_pt_r_pu_tranche2,
            'korelasi_r_pt_r_pu_tranche3' => $request->korelasi_r_pt_r_pu_tranche3,
            'korelasi_r_pt_r_c_tranche1' => $request->korelasi_r_pt_r_c_tranche1,
            'korelasi_r_pt_r_c_tranche2' => $request->korelasi_r_pt_r_c_tranche2,
            'korelasi_r_pt_r_c_tranche3' => $request->korelasi_r_pt_r_c_tranche3,
            'korelasi_r_pu_r_c_tranche1' => $request->korelasi_r_pu_r_c_tranche1,
            'korelasi_r_pu_r_c_tranche2' => $request->korelasi_r_pu_r_c_tranche2,
            'korelasi_r_pu_r_c_tranche3' => $request->korelasi_r_pu_r_c_tranche3,
            'flag' => 1,
        ]);

        // Lifecycle
        DB::table('setting_komposisi_investasi_lifecycle_fund')->insert([
            'id' => (string) Str::uuid(),
            'id_user' => $id_user,
            'id_setting_portofolio_personal_admin' => $id_investasi,
            'nama' => $request->nama,
            'saham_t1' => $request->saham_t1,
            'saham_t2' => $request->saham_t2,
            'saham_t3' => $request->saham_t3,
            'pendapatan_tetap_t1' => $request->pendapatan_tetap_t1,
            'pendapatan_tetap_t2' => $request->pendapatan_tetap_t2,
            'pendapatan_tetap_t3' => $request->pendapatan_tetap_t3,
            'deposito_t1' => $request->deposito_t1,
            'deposito_t2' => $request->deposito_t2,
            'deposito_t3' => $request->deposito_t3,
            'reksadana_saham_t1' => $request->reksadana_saham_t1,
            'reksadana_saham_t2' => $request->reksadana_saham_t2,
            'reksadana_saham_t3' => $request->reksadana_saham_t3,
            'reksadana_pendapatan_tetap_t1' => $request->reksadana_pendapatan_tetap_t1,
            'reksadana_pendapatan_tetap_t2' => $request->reksadana_pendapatan_tetap_t2,
            'reksadana_pendapatan_tetap_t3' => $request->reksadana_pendapatan_tetap_t3,
            'reksadana_pasar_uang_t1' => $request->reksadana_pasar_uang_t1,
            'reksadana_pasar_uang_t2' => $request->reksadana_pasar_uang_t2,
            'reksadana_pasar_uang_t3' => $request->reksadana_pasar_uang_t3,
            'reksadana_campuran_t1' => $request->reksadana_campuran_t1,
            'reksadana_campuran_t2' => $request->reksadana_campuran_t2,
            'reksadana_campuran_t3' => $request->reksadana_campuran_t3,
            'return_portofolio_personal_t1' => $request->return_portofolio_personal_t1,
            'return_portofolio_personal_t2' => $request->return_portofolio_personal_t2,
            'return_portofolio_personal_t3' => $request->return_portofolio_personal_t3,
            'resiko_pasar_portofolio_personal_t1' => $request->resiko_pasar_portofolio_personal_t1,
            'resiko_pasar_portofolio_personal_t2' => $request->resiko_pasar_portofolio_personal_t2,
            'resiko_pasar_portofolio_personal_t3' => $request->resiko_pasar_portofolio_personal_t3,
            'flag' => 1,
        ]);
        
        return response()->json([
            "status" =>true,
            "message"=>"Setting Personal LifeCycle User Updated!",
        ],200);    
    }

    // Setting Treatment Pembayaran
    public function setting_treatment(Request $request){
        $id_user = $request->input('id_user');
        $setting_treatment = DB::table('setting_treatment_pembayaran_setelah_pensiun')
        ->where('id_user', $id_user)
        ->where('flag', 1)
        ->select('*')->get();
        
        return response()->json([
            "status" =>true,
            "message"=>"Setting Treatment Pembayaran Setelah Pensiun User!",
            "data" => $setting_treatment
        ],200);    
    }
    public function setting_treatment_add(Request $request){
        $id_user = $request->input('id_user');
        
        // Add Activity
        DB::table('activity_setting_treatment_pembayaran')->insert([
            'id' => (string) Str::uuid(),
            'id_user' => $id_user,
            'browser' => $request->browser,
            'sistem_operasi' => $request->sistem_operasi,
            'ip_address' => $request->ip_address,
        ]);

        // Ubah Flag Data Terbaru ke => 0
        DB::table('setting_treatment_pembayaran_setelah_pensiun')
        ->where('id_user', $id_user)
        ->where('flag', 1)
        ->update([
            'flag' => 0,
        ]);

        // Tambahkan Data Baru
        DB::table('setting_treatment_pembayaran_setelah_pensiun')->insert([
            'id' => (string) Str::uuid(),
            'id_user' => $id_user,
            'ppmp' => $request->ppmp,
            'personal_properti' => $request->personal_properti,
            'ppip' => $request->ppip,
            'harga_anuitas_ppip' => $request->harga_anuitas_ppip,
            'bunga_ppip' => $request->bunga_ppip,
            'pajak_ppip' => $request->pajak_ppip,
            'personal_pasar_keuangan' => $request->personal_pasar_keuangan,
            'harga_anuitas_personal_pasar_keuangan' => $request->harga_anuitas_personal_pasar_keuangan,
            'bunga_personal_pasar_keuangan' => $request->bunga_personal_pasar_keuangan,
            'pajak_personal_pasar_keuangan' => $request->pajak_personal_pasar_keuangan,
            'flag' => 1,
        ]);
        
        return response()->json([
            "status" =>true,
            "message"=>"Setting Treatment Pembayaran Setelah Pensiun User Updated!",
        ],200);    
    }   
}


            

