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
    $affected = DB::table('setting_portofolio_ppip_admin')
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
      $setting_ppip = DB::table('setting_portofolio_personal_admin')->select('*')
      ->leftjoin('setting_komposisi_investasi_lifecycle_fund_admin', 'setting_portofolio_personal_admin.id', '=', 'setting_komposisi_investasi_lifecycle_fund_admin.id_setting_portofolio_personal_admin')
      ->groupBy('setting_portofolio_personal_admin.id')->where('setting_portofolio_personal_admin.id', $id)
      ->get();
  
      return response()->json([
          "status" =>true,
          "message"=>"Lists Setting Personal Keuangan!",
          "opsi" => $opsi,
          "data" => $setting_ppip
      ],200);
    } else {
      $setting_ppip = DB::table('setting_portofolio_personal_admin')
        ->select('*')
        ->selectRaw('(SELECT id as id_komposisi_investasi, nama as nama_komposisi_investasi, saham_t1,saham_t2,saham_t3,
        pendapatan_tetap_t1,pendapatan_tetap_t2,pendapatan_tetap_t3,
        deposito_t1,deposito_t2,deposito_t3,
        reksadana_saham_t1,reksadana_saham_t2,reksadana_saham_t1,
        reksadana_pendapatan_tetap_t1,
        reksadana_pendapatan_tetap_t2,
        reksadana_pendapatan_tetap_t3,
        reksadana_pasar_uang_t1,reksadana_pasar_uang_t2,reksadana_pasar_uang_t3, 
        reksadana_campuran_t1, reksadana_campuran_t2,reksadana_campuran_t3,
        return_portofolio_personal_t1, return_portofolio_personal_t2,return_portofolio_personal_t3,
        resiko_pasar_portofolio_personal_t1, resiko_pasar_portofolio_personal_t2, resiko_pasar_portofolio_personal_t3, flag as flag_komposisi_investasi
        FROM setting_komposisi_investasi_lifecycle_fund_admin WHERE setting_portofolio_personal_admin.id = setting_komposisi_investasi_lifecycle_fund_admin.id_setting_portofolio_personal_admin) AS komposisi_investasi')
        ->groupBy('setting_portofolio_personal_admin.id')
        ->where('setting_portofolio_personal_admin.id', $id)
        ->get();
  
        return response()->json([
            "status" =>true,
            "message"=>"Lists Setting Personal Keuangan!",
            "opsi" => $opsi,
            "data" => $setting_ppip,
        ],200);
    }
  }
  // public function setting_personal_lifecycle_add(Request $request){
  //   DB::table('setting_portofolio_ppip_admin')->insert([
  //       'id' => (string) Str::uuid(),
  //       'nama_portofolio' => $request->nama_portofolio,
  //       'return_saham' => $request->return_saham,
  //       'return_pendapatan_tetap' => $request->return_pendapatan_tetap,
  //       'return_deposito' => $request->return_deposito,
  //       'resiko_saham' => $request->resiko_saham,
  //       'resiko_pendapatan_tetap' => $request->resiko_pendapatan_tetap,
  //       'resiko_deposito' => $request->resiko_deposito,
  //       'korelasi_saham_pendapatan_tetap' => $request->korelasi_saham_pendapatan_tetap,
  //       'korelasi_saham_deposito' => $request->korelasi_saham_deposito,
  //       'korelasi_pendapatan_tetap_deposito' => $request->korelasi_pendapatan_tetap_deposito,
  //       'tranche_investasi_saham' => $request->tranche_investasi_saham,
  //       'tranche_investasi_pendapatan_tetap' => $request->tranche_investasi_pendapatan_tetap,
  //       'tranche_investasi_deposito' => $request->tranche_investasi_deposito,
  //       'tranche_likuiditas_saham' => $request->tranche_likuiditas_saham,
  //       'tranche_likuiditas_pendapatan_tetap' => $request->tranche_likuiditas_pendapatan_tetap,
  //       'tranche_likuiditas_deposito' => $request->tranche_likuiditas_deposito,
  //       'flag' => 1,
  //   ]);
    
  //   return response()->json([
  //     "status" =>true,
  //     "message"=>"Setting PPIP Berhasil Ditambahkan!",
  //   ],200);
  // }
}