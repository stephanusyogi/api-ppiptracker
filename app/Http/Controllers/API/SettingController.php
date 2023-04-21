<?php

namespace App\Http\Controllers\API;

use App\Models\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\AdminResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SettingController extends Controller
{
  public function setting_nilai_asumsi(){
    $setting_nilai_asumsi = DB::table('nilai_asumsi_admin')
    ->select('*')->get();
    
    return response()->json([
        "status" =>true,
        "message"=>"Lists Setting Nilai Asumsi!",
        "data" => $setting_nilai_asumsi
    ],200);
  }
  public function setting_nilai_asumsi_update(Request $request)
  {
    $affected = DB::table('nilai_asumsi_admin')
      ->where('id', $request->id)
      ->update([
        'kenaikan_gaji' => $request->kenaikan_gaji,
        'kenaikan_phdp' => $request->kenaikan_phdp,
        'iuran_ppip' => $request->iuran_ppip,
        'dasar_pembayaran_iuran_personal' => $request->dasar_pembayaran_iuran_personal,
        'inflasi_jangka_panjang' => $request->inflasi_jangka_panjang,
      ]);
    return response()->json([
        "status" =>true,
        "message"=>"Setting Nilai Asumsi Updated!",
    ],200);
  }

  public function setting_ppip(Request $request){
    $opsi = DB::table('setting_portofolio_ppip_admin')
    ->select('id','nama_portofolio')->get();

    $id = $request->input('id');
    if ($id) {
      $setting_ppip = DB::table('setting_portofolio_ppip_admin')
      ->select('*')->where('id', $id)->get();
  
      return response()->json([
          "status" =>true,
          "message"=>"Lists Setting PPIP!",
          "opsi" => $opsi,
          "data" => $setting_ppip
      ],200);
    } else {
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
    DB::table('setting_portofolio_ppip_admin')->insert([
        'id' => (string) Str::uuid(),
        'nama_portofolio' => $request->nama_portofolio,
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
        'flag' => 1,
    ]);
    
    return response()->json([
      "status" =>true,
      "message"=>"Setting PPIP Berhasil Ditambahkan!",
    ],200);
  }
  public function setting_ppip_update(Request $request)
  {
    DB::table('setting_portofolio_ppip_admin')
      ->where('id', $request->id)
      ->update([
        'nama_portofolio' => $request->nama_portofolio,
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
        'flag' => $request->flag,
      ]);
    return response()->json([
        "status" =>true,
        "message"=>"Setting Nilai Asumsi Diperbarui!",
    ],200);
  }
  
  public function setting_personal_lifecycle(Request $request){
    $opsi = DB::table('setting_portofolio_personal_admin')
    ->select('id','nama')->get();

    $id = $request->input('id');
    if ($id) {
      $setting_personal = DB::table('setting_portofolio_personal_admin')
      ->select('*')
      ->where('id', $id)
      ->get();
      $response = array();
      foreach ($setting_personal as $row) {
          $komposisi_investasi = DB::table('setting_komposisi_investasi_lifecycle_fund_admin')
                                  ->where('id_setting_portofolio_personal_admin', $row->id)
                                  ->get();
          $row->komposisi_investasi = $komposisi_investasi;
          $response[] = $row;
      }
      return response()->json([
          "status" =>true,
          "message"=>"Lists Setting Personal Keuangan!",
          "opsi" => $opsi,
          "data" => $response,
      ],200);
    } else {
      $setting_personal = DB::table('setting_portofolio_personal_admin')->select('*')->get();
      $response = array();
      foreach ($setting_personal as $row) {
          $komposisi_investasi = DB::table('setting_komposisi_investasi_lifecycle_fund_admin')
                                  ->where('id_setting_portofolio_personal_admin', $row->id)
                                  ->get();
          $row->komposisi_investasi = $komposisi_investasi;
          $response[] = $row;
      }
      return response()->json([
          "status" =>true,
          "message"=>"Lists Setting Personal Keuangan!",
          "opsi" => $opsi,
          "data" => $response,
      ],200);
    }
  }
  public function setting_personal_lifecycle_add(Request $request){
    // DB::table('setting_portofolio_personal_admin')->insertGetId([
    //     'id' => (string) Str::uuid(),
    //     'nama' => $request->nama,
    //     'return_s_tranche1' => $request->return_s_tranche1,
    //     'return_s_tranche2' => $request->return_s_tranche2,
    //     'return_s_tranche3' => $request->return_s_tranche3,
    //     'return_pt_tranche1' => $request->return_pt_tranche1,
    //     'return_pt_tranche2' => $request->return_pt_tranche2,
    //     'return_pt_tranche3' => $request->return_pt_tranche3,
    //     'return_d_tranche1' => $request->return_d_tranche1,
    //     'return_d_tranche2' => $request->return_d_tranche2,
    //     'return_d_tranche3' => $request->return_d_tranche3,
    //     'return_r_s_tranche1' => $request->return_r_s_tranche1,
    //     'return_r_s_tranche2' => $request->return_r_s_tranche2,
    //     'return_r_s_tranche3' => $request->return_r_s_tranche3,
    //     'return_r_pt_tranche1' => $request->return_r_pt_tranche1,
    //     'return_r_pt_tranche2' => $request->return_r_pt_tranche2,
    //     'return_r_pt_tranche3' => $request->return_r_pt_tranche3,
    //     'return_r_pu_tranche1' => $request->return_r_pu_tranche1,
    //     'return_r_pu_tranche2' => $request->return_r_pu_tranche2,
    //     'return_r_pu_tranche3' => $request->return_r_pu_tranche3,
    //     'return_r_c_tranche1' => $request->return_r_c_tranche1,
    //     'return_r_c_tranche2' => $request->return_r_c_tranche2,
    //     'return_r_c_tranche3' => $request->return_r_c_tranche3,
    //     'resiko_s_tranche1' => $request->resiko_s_tranche1,
    //     'resiko_s_tranche2' => $request->resiko_s_tranche2,
    //     'resiko_s_tranche3' => $request->resiko_s_tranche3,
    //     'resiko_pt_tranche1' => $request->resiko_pt_tranche1,
    //     'resiko_pt_tranche2' => $request->resiko_pt_tranche2,
    //     'resiko_pt_tranche3' => $request->resiko_pt_tranche3,
    //     'resiko_d_tranche1' => $request->resiko_d_tranche1,
    //     'resiko_d_tranche2' => $request->resiko_d_tranche2,
    //     'resiko_d_tranche3' => $request->resiko_d_tranche3,
    //     'resiko_r_s_tranche1' => $request->resiko_r_s_tranche1,
    //     'resiko_r_s_tranche2' => $request->resiko_r_s_tranche2,
    //     'resiko_r_s_tranche3' => $request->resiko_r_s_tranche3,
    //     'resiko_r_pt_tranche1' => $request->resiko_r_pt_tranche1,
    //     'resiko_r_pt_tranche2' => $request->resiko_r_pt_tranche2,
    //     'resiko_r_pt_tranche3' => $request->resiko_r_pt_tranche3,
    //     'resiko_r_pu_tranche1' => $request->resiko_r_pu_tranche1,
    //     'resiko_r_pu_tranche2' => $request->resiko_r_pu_tranche2,
    //     'resiko_r_pu_tranche3' => $request->resiko_r_pu_tranche3,
    //     'resiko_r_c_tranche1' => $request->resiko_r_c_tranche1,
    //     'resiko_r_c_tranche2' => $request->resiko_r_c_tranche2,
    //     'resiko_r_c_tranche3' => $request->resiko_r_c_tranche3,
    //     'korelasi_s_pt_tranche1' => $request->korelasi_s_pt_tranche1,
    //     'korelasi_s_pt_tranche2' => $request->korelasi_s_pt_tranche2,
    //     'korelasi_s_pt_tranche3' => $request->korelasi_s_pt_tranche3,
    //     'korelasi_s_d_tranche1' => $request->korelasi_s_d_tranche1,
    //     'korelasi_s_d_tranche2' => $request->korelasi_s_d_tranche2,
    //     'korelasi_s_d_tranche3' => $request->korelasi_s_d_tranche3,
    //     'korelasi_s_r_s_tranche1' => $request->korelasi_s_r_s_tranche1,
    //     'korelasi_s_r_s_tranche2' => $request->korelasi_s_r_s_tranche2,
    //     'korelasi_s_r_s_tranche3' => $request->korelasi_s_r_s_tranche3,
    //     'korealsi_s_r_pt_tranche1' => $request->korealsi_s_r_pt_tranche1,
    //     'korealsi_s_r_pt_tranche2' => $request->korealsi_s_r_pt_tranche2,
    //     'korealsi_s_r_pt_tranche3' => $request->korealsi_s_r_pt_tranche3,
    //     'korelasi_s_r_pu_tranche1' => $request->korelasi_s_r_pu_tranche1,
    //     'korelasi_s_r_pu_tranche2' => $request->korelasi_s_r_pu_tranche2,
    //     'korelasi_s_r_pu_tranche3' => $request->korelasi_s_r_pu_tranche3,
    //     'korelasi_s_r_c_tranche1' => $request->korelasi_s_r_c_tranche1,
    //     'korelasi_s_r_c_tranche2' => $request->korelasi_s_r_c_tranche2,
    //     'korelasi_s_r_c_tranche3' => $request->korelasi_s_r_c_tranche3,
    //     'korelasi_pt_d_tranche1' => $request->korelasi_pt_d_tranche1,
    //     'korelasi_pt_d_tranche2' => $request->korelasi_pt_d_tranche2,
    //     'korelasi_pt_d_tranche3' => $request->korelasi_pt_d_tranche3,
    //     'korealsi_pt_r_s_tranche1' => $request->korealsi_pt_r_s_tranche1,
    //     'korealsi_pt_r_s_tranche2' => $request->korealsi_pt_r_s_tranche2,
    //     'korealsi_pt_r_s_tranche3' => $request->korealsi_pt_r_s_tranche3,
    //     'korelasi_pt_r_pt_tranche1' => $request->korelasi_pt_r_pt_tranche1,
    //     'korelasi_pt_r_pt_tranche2' => $request->korelasi_pt_r_pt_tranche2,
    //     'korelasi_pt_r_pt_tranche3' => $request->korelasi_pt_r_pt_tranche3,
    //     'korelasi_pt_r_pu_tranche1' => $request->korelasi_pt_r_pu_tranche1,
    //     'korelasi_pt_r_pu_tranche2' => $request->korelasi_pt_r_pu_tranche2,
    //     'korelasi_pt_r_pu_tranche3' => $request->korelasi_pt_r_pu_tranche3,
    //     'korelasi_pt_r_c_tranche1' => $request->korelasi_pt_r_c_tranche1,
    //     'korelasi_pt_r_c_tranche2' => $request->korelasi_pt_r_c_tranche2,
    //     'korelasi_pt_r_c_tranche3' => $request->korelasi_pt_r_c_tranche3,
    //     'korelasi_d_r_s_tranche1' => $request->korelasi_d_r_s_tranche1,
    //     'korelasi_d_r_s_tranche2' => $request->korelasi_d_r_s_tranche2,
    //     'korelasi_d_r_s_tranche3' => $request->korelasi_d_r_s_tranche3,
    //     'korelasi_d_r_pt_tranche1' => $request->korelasi_d_r_pt_tranche1,
    //     'korelasi_d_r_pt_tranche2' => $request->korelasi_d_r_pt_tranche2,
    //     'korelasi_d_r_pt_tranche3' => $request->korelasi_d_r_pt_tranche3,
    //     'korelasi_d_r_pu_tranche1' => $request->korelasi_d_r_pu_tranche1,
    //     'korelasi_d_r_pu_tranche2' => $request->korelasi_d_r_pu_tranche2,
    //     'korelasi_d_r_pu_tranche3' => $request->korelasi_d_r_pu_tranche3,
    //     'korelasi_d_r_c_tranche1' => $request->korelasi_d_r_c_tranche1,
    //     'korelasi_d_r_c_tranche2' => $request->korelasi_d_r_c_tranche2,
    //     'korelasi_d_r_c_tranche3' => $request->korelasi_d_r_c_tranche3,
    //     'korelasi_r_s_r_pt_tranche1' => $request->korelasi_r_s_r_pt_tranche1,
    //     'korelasi_r_s_r_pt_tranche2' => $request->korelasi_r_s_r_pt_tranche2,
    //     'korelasi_r_s_r_pt_tranche3' => $request->korelasi_r_s_r_pt_tranche3,
    //     'korelasi_r_s_r_pu_tranche1' => $request->korelasi_r_s_r_pu_tranche1,
    //     'korelasi_r_s_r_pu_tranche2' => $request->korelasi_r_s_r_pu_tranche2,
    //     'korelasi_r_s_r_pu_tranche3' => $request->korelasi_r_s_r_pu_tranche3,
    //     'korelasi_r_s_r_c_tranche1' => $request->korelasi_r_s_r_c_tranche1,
    //     'korelasi_r_s_r_c_tranche2' => $request->korelasi_r_s_r_c_tranche2,
    //     'korelasi_r_s_r_c_tranche3' => $request->korelasi_r_s_r_c_tranche3,
    //     'korelasi_r_pt_r_pu_tranche1' => $request->korelasi_r_pt_r_pu_tranche1,
    //     'korelasi_r_pt_r_pu_tranche2' => $request->korelasi_r_pt_r_pu_tranche2,
    //     'korelasi_r_pt_r_pu_tranche3' => $request->korelasi_r_pt_r_pu_tranche3,
    //     'korelasi_r_pt_r_c_tranche1' => $request->korelasi_r_pt_r_c_tranche1,
    //     'korelasi_r_pt_r_c_tranche2' => $request->korelasi_r_pt_r_c_tranche2,
    //     'korelasi_r_pt_r_c_tranche3' => $request->korelasi_r_pt_r_c_tranche3,
    //     'korelasi_r_pu_r_c_tranche1' => $request->korelasi_r_pu_r_c_tranche1,
    //     'korelasi_r_pu_r_c_tranche2' => $request->korelasi_r_pu_r_c_tranche2,
    //     'korelasi_r_pu_r_c_tranche3' => $request->korelasi_r_pu_r_c_tranche3,
    //     'flag' => 1,
    // ]);
    
    $response = DB::table('setting_portofolio_personal_admin')
    ->select('id')
    ->where('nama', $request->nama)
    ->get()->toArray();
    // $array = get_object_vars($response);
    var_dump($response);
    die();

    DB::table('setting_komposisi_investasi_lifecycle_fund_admin')->insert([
      'id' => (string) Str::uuid(),
      'id_setting_portofolio_personal_admin' => $response->id,
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
      "message"=>"Setting Personal Berhasil Ditambahkan!",
    ],200);
  }
  public function setting_personal_lifecycle_update(Request $request)
  {
    DB::table('setting_portofolio_personal_admin')
      ->where('id', $request->id)
      ->update([
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
        'korealsi_s_r_pt_tranche1' => $request->korealsi_s_r_pt_tranche1,
        'korealsi_s_r_pt_tranche2' => $request->korealsi_s_r_pt_tranche2,
        'korealsi_s_r_pt_tranche3' => $request->korealsi_s_r_pt_tranche3,
        'korelasi_s_r_pu_tranche1' => $request->korelasi_s_r_pu_tranche1,
        'korelasi_s_r_pu_tranche2' => $request->korelasi_s_r_pu_tranche2,
        'korelasi_s_r_pu_tranche3' => $request->korelasi_s_r_pu_tranche3,
        'korelasi_s_r_c_tranche1' => $request->korelasi_s_r_c_tranche1,
        'korelasi_s_r_c_tranche2' => $request->korelasi_s_r_c_tranche2,
        'korelasi_s_r_c_tranche3' => $request->korelasi_s_r_c_tranche3,
        'korelasi_pt_d_tranche1' => $request->korelasi_pt_d_tranche1,
        'korelasi_pt_d_tranche2' => $request->korelasi_pt_d_tranche2,
        'korelasi_pt_d_tranche3' => $request->korelasi_pt_d_tranche3,
        'korealsi_pt_r_s_tranche1' => $request->korealsi_pt_r_s_tranche1,
        'korealsi_pt_r_s_tranche2' => $request->korealsi_pt_r_s_tranche2,
        'korealsi_pt_r_s_tranche3' => $request->korealsi_pt_r_s_tranche3,
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
        'flag' => $request->flag,
      ]);
    
      
    DB::table('setting_komposisi_investasi_lifecycle_fund_admin')
    ->where('id_setting_portofolio_personal_admin', $request->id)
    ->update([
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
      'flag' => $request->flag,
    ]);
    return response()->json([
        "status" =>true,
        "message"=>"Setting Personal Diperbarui!",
    ],200);
  }

  public function setting_personal_lifecycle_bukatutup_aset(Request $request){
    $id = $request->input('id');
    if ($id) {
      DB::table('setting_portofolio_personal_aset_dibuka')
        ->where('id', $id)
        ->update([
          'dibuka' => $request->dibuka,
        ]);

      return response()->json([
          "status" =>true,
          "message"=>"Setting Aset Updated!",
      ],200);
    } else {
      $setting_aset = DB::table('setting_portofolio_personal_aset_dibuka')
      ->select('*')->get();
  
      return response()->json([
          "status" =>true,
          "message"=>"Lists Setting Aset!",
          "data" => $setting_aset
      ],200);
    }
  }
}