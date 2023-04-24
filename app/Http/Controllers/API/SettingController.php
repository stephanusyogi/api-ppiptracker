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
        'flag' => 0,
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
  public function setting_ppip_hitung_nilai(Request $request){
    $port_ppip = DB::table('setting_portofolio_ppip_admin')
    ->select('*')->get()->toArray();
    foreach ($port_ppip as $obj) {
      $return_saham_ppip=round($obj->return_saham ,2); //Read return saham
      $return_fi_ppip=round($obj->return_pendapatan_tetap ,2); //Read return pendapatan tetap
      $return_depo_ppip=round($obj->return_deposito ,2); //Read return deposito
      
      $risk_saham_ppip=round($obj->resiko_saham ,2); //Read risk saham
      $risk_fi_ppip=round($obj->resiko_pendapatan_tetap ,2); //Read risk pendapatan tetap
      $risk_depo_ppip=round($obj->resiko_deposito ,2); //Read risk deposito
      
      $korel_saham_fi_ppip=round($obj->korelasi_saham_pendapatan_tetap ,2); //Read korelasi saham - pendapatan tetap
      $korel_saham_depo_ppip=round($obj->korelasi_saham_deposito ,2); //Read korelasi saham - deposito
      $korel_fi_depo_ppip=round($obj->korelasi_pendapatan_tetap_deposito ,2); //Read korelasi pendapatan tetap - deposito
      
      $komposisi_inv_saham_ppip=round($obj->tranche_investasi_saham ,2); //Read komposisi saham tranche investasi
      $komposisi_inv_fi_ppip=round($obj->tranche_investasi_pendapatan_tetap ,2); //Read komposisi pendapatan tetap tranche investasi
      $komposisi_inv_depo_ppip=round($obj->tranche_investasi_deposito ,2); //Read komposisi deposito tranche investasi
      
      $komposisi_likuid_saham_ppip=round($obj->tranche_likuiditas_saham ,2); //Read komposisi saham tranche likuiditas
      $komposisi_likuid_fi_ppip=round($obj->tranche_likuiditas_pendapatan_tetap ,2); //Read komposisi pendapatan tetap tranche likuiditas
      $komposisi_likuid_depo_ppip=round($obj->tranche_likuiditas_deposito ,2); //Read komposisi deposito tranche likuiditas

      // perhitungan return portofolio tranche investasi
      $return_ppip_inv = round(($komposisi_inv_saham_ppip*$return_saham_ppip + $komposisi_inv_fi_ppip*$return_fi_ppip + $komposisi_inv_depo_ppip*$return_depo_ppip) / 100, 2); 
      // perhitungan return portofolio tranche likuiditas
      $return_ppip_likuid = round(($komposisi_likuid_saham_ppip*$return_saham_ppip + $komposisi_likuid_fi_ppip*$return_fi_ppip + $komposisi_likuid_depo_ppip*$return_depo_ppip) / 100, 2); 

      // perhitungan resiko portofolio tranche investasi
      $risk_ppip_inv_step1 = $komposisi_inv_saham_ppip * $komposisi_inv_saham_ppip * $risk_saham_ppip * $risk_saham_ppip + $komposisi_inv_fi_ppip * $komposisi_inv_fi_ppip * $risk_fi_ppip * $risk_fi_ppip + $komposisi_inv_depo_ppip * $komposisi_inv_depo_ppip * $risk_depo_ppip * $risk_depo_ppip;
      $risk_ppip_inv_step2 = 2 * $komposisi_inv_saham_ppip * $komposisi_inv_fi_ppip * $risk_saham_ppip * $risk_fi_ppip * $korel_saham_fi_ppip + 2 * $komposisi_inv_saham_ppip * $komposisi_inv_depo_ppip * $risk_saham_ppip * $risk_depo_ppip * $korel_saham_depo_ppip + 2 * $komposisi_inv_fi_ppip * $komposisi_inv_depo_ppip * $risk_fi_ppip * $risk_depo_ppip * $korel_fi_depo_ppip;
      $risk_ppip_inv = round(sqrt($risk_ppip_inv_step1+$risk_ppip_inv_step2) / 100, 2);
      
      // perhitungan resiko portofolio tranche likuiditas
      $risk_ppip_likuid_step1 = $komposisi_likuid_saham_ppip*$komposisi_likuid_saham_ppip*$risk_saham_ppip*$risk_saham_ppip + $komposisi_likuid_fi_ppip*$komposisi_likuid_fi_ppip*$risk_fi_ppip*$risk_fi_ppip + $komposisi_likuid_depo_ppip*$komposisi_likuid_depo_ppip*$risk_depo_ppip*$risk_depo_ppip;
      $risk_ppip_likuid_step2 = 2*$komposisi_likuid_saham_ppip*$komposisi_likuid_fi_ppip*$risk_saham_ppip*$risk_fi_ppip*$korel_saham_fi_ppip+ 2*$komposisi_likuid_saham_ppip*$komposisi_likuid_depo_ppip*$risk_saham_ppip*$risk_depo_ppip*$korel_saham_depo_ppip+2*$komposisi_likuid_fi_ppip*$komposisi_likuid_depo_ppip*$risk_fi_ppip*$risk_depo_ppip*$korel_fi_depo_ppip;
      $risk_ppip_likuid = round(sqrt($risk_ppip_likuid_step1+$risk_ppip_likuid_step2) / 100, 2);

      // Update Tabel
      DB::table('setting_portofolio_ppip_admin')
        ->where('id', $obj->id)
        ->update([
          'return_portofolio_tranche_investasi' => $return_ppip_inv,
          'return_portofolio_tranche_likuiditas' => $return_ppip_likuid,
          'resiko_portofolio_tranche_investasi' => $risk_ppip_inv,
          'resiko_portofolio_tranche_likuiditas' => $risk_ppip_likuid,
          'flag' => 1,
        ]);
    }
    
    return response()->json([
      "status" =>true,
      "message"=>"Setting Updated!",
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
    DB::table('setting_portofolio_personal_admin')->insertGetId([
        'id' => (string) Str::uuid(),
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
        'flag' => 0,
    ]);
    
    $response = DB::table('setting_portofolio_personal_admin')
    ->select('id')
    ->where('nama', $request->nama)
    ->get();
    $id_setting_portofolio_personal_admin = $response->first()->id;

    DB::table('setting_komposisi_investasi_lifecycle_fund_admin')->insert([
      'id' => (string) Str::uuid(),
      'id_setting_portofolio_personal_admin' => $id_setting_portofolio_personal_admin,
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
      'flag' => 0,
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
  public function setting_personal_lifecycle_hitung_nilai(Request $request){
    $setting_personal = DB::table('setting_portofolio_personal_admin')->select('*')->get();
    $response = array();
    foreach ($setting_personal as $row) {
        $komposisi_investasi = DB::table('setting_komposisi_investasi_lifecycle_fund_admin')
                                ->where('id_setting_portofolio_personal_admin', $row->id)
                                ->get();
        $row->komposisi_investasi = $komposisi_investasi;
        $response[] = $row;
    }

    foreach ($response as $obj) {
      $tranche1 = $this->setting_personal_lifecycle_hitung_nilai_tranche1($obj);
      echo json_encode($tranche1, true);
      die();
    }
    
    return response()->json([
      "status" =>true,
      "message"=>"Setting Updated!",
    ],200); 
  }

  public function setting_personal_lifecycle_hitung_nilai_tranche1($data)
    {
      // Personal Keuangan
      $return_saham_personal_tranche1 = $data->return_s_tranche1; //Read return saham
      $return_fi_personal_tranche1 = $data->return_pt_tranche1; //Read return pendapatan tetap
      $return_depo_personal_tranche1 = $data->return_d_tranche1; //Read return deposito
      $return_rdsaham_personal_tranche1 = $data->return_r_s_tranche1; //Read return reksadana saham
      $return_rdfi_personal_tranche1 = $data->return_r_pt_tranche1; //Read return reksadana pendapatan tetap
      $return_rdpu_personal_tranche1 = $data->return_r_pu_tranche1; //Read return reksadana pasar uang
      $return_rdcampuran_personal_tranche1 = $data->return_r_c_tranche1; //Read return reksadana campuran
      
      $risk_saham_personal_tranche1 = $data->resiko_s_tranche1; //Read risk saham
      $risk_fi_personal_tranche1 = $data->resiko_pt_tranche1; //Read risk pendapatan tetap
      $risk_depo_personal_tranche1 = $data->resiko_d_tranche1; //Read risk deposito
      $risk_rdsaham_personal_tranche1 = $data->resiko_r_s_tranche1; //Read risk reksadana saham
      $risk_rdfi_personal_tranche1 = $data->resiko_r_pt_tranche1; //Read risk reksadanapendapatan tetap
      $risk_rdpu_personal_tranche1 = $data->resiko_r_pu_tranche1; //Read risk reksadana pasar uang
      $risk_rdcampuran_personal_tranche1 = $data->resiko_r_c_tranche1; //Read risk reksadana campuran
      
      $korel_saham_fi_personal_tranche1 = $data->korelasi_s_pt_tranche1; //Read korelasi saham - pendapatan tetap
      $korel_saham_depo_personal_tranche1 = $data->korelasi_s_d_tranche1; //Read korelasi saham - deposito
      $korel_saham_rdsaham_personal_tranche1 = $data->korelasi_s_r_s_tranche1; //Read korelasi saham - rdsaham
      $korel_saham_rdfi_personal_tranche1 = $data->korealsi_s_r_pt_tranche1; //Read korelasi saham - rdfi
      $korel_saham_rdpu_personal_tranche1 = $data->korelasi_s_r_pu_tranche1; //Read korelasi saham - rdpu
      $korel_saham_rdcampuran_personal_tranche1 = $data->korelasi_s_r_c_tranche1; //Read korelasi saham - rdsaham
      
      $korel_fi_depo_personal_tranche1 = $data->korelasi_pt_d_tranche1; //Read korelasi fi - deposito
      $korel_fi_rdsaham_personal_tranche1 = $data->korealsi_pt_r_s_tranche1; //Read korelasi fi - rdsaham
      $korel_fi_rdfi_personal_tranche1 = $data->korelasi_pt_r_pt_tranche1; //Read korelasi fi - rdfi
      $korel_fi_rdpu_personal_tranche1 = $data->korelasi_pt_r_pu_tranche1; //Read korelasi fi - rdpu
      $korel_fi_rdcampuran_personal_tranche1 = $data->korelasi_pt_r_c_tranche1; //Read korelasi fi - rdsaham
      
      $korel_depo_rdsaham_personal_tranche1 = $data->korelasi_d_r_s_tranche1; //Read korelasi depo - rdsaham
      $korel_depo_rdfi_personal_tranche1 = $data->korelasi_d_r_pt_tranche1; //Read korelasi depo - rdfi
      $korel_depo_rdpu_personal_tranche1 = $data->korelasi_d_r_pu_tranche1; //Read korelasi depo - rdpu
      $korel_depo_rdcampuran_personal_tranche1 = $data->korelasi_d_r_c_tranche1; //Read korelasi depo - rdsaham
      
      $korel_rdsaham_rdfi_personal_tranche1 = $data->korelasi_r_s_r_pt_tranche1; //Read korelasi rdsaham - rdfi
      $korel_rdsaham_rdpu_personal_tranche1 = $data->korelasi_r_s_r_pu_tranche1; //Read korelasi rdsaham - rdpu
      $korel_rdsaham_rdcampuran_personal_tranche1 = $data->korelasi_r_s_r_c_tranche1; //Read korelasi rdsaham - rdsaham

      $korel_rdfi_rdpu_personal_tranche1 = $data->korelasi_r_pt_r_pu_tranche1; //Read korelasi rdfi - rdpu
      $korel_rdfi_rdcampuran_personal_tranche1 = $data->korelasi_r_pt_r_c_tranche1; //Read korelasi rdfi - rdsaham
      
      $korel_rdpu_rdcampuran_personal_tranche1 = $data->korelasi_r_pu_r_c_tranche1; //Read korelasi rdpu - rdsaham
        
      // LifeCycle
      $komposisi_investasi = $data->komposisi_investasi[0];

      $komposisi_tranche1_saham_personal = $komposisi_investasi->saham_t1; //Read komposisi saham tranche 1
      $komposisi_tranche1_fi_personal = $komposisi_investasi->pendapatan_tetap_t1; //Read komposisi pendapatan tetap tranche 1
      $komposisi_tranche1_depo_personal = $komposisi_investasi->deposito_t1; //Read komposisi deposito tranche 1
      $komposisi_tranche1_rdsaham_personal = $komposisi_investasi->reksadana_saham_t1; //Read komposisi reksadana saham tranche 1
      $komposisi_tranche1_rdfi_personal = $komposisi_investasi->reksadana_pendapatan_tetap_t1; //Read komposisi reksadana pendapatan tetap tranche 1
      $komposisi_tranche1_rdpu_personal = $komposisi_investasi->reksadana_pasar_uang_t1; //Read komposisi reksadana pasar uang tranche 1
      $komposisi_tranche1_rdcampuran_personal = $komposisi_investasi->reksadana_campuran_t1; //Read komposisi reksadana campuran tranche 1
      
      $return_personal_tranche1 = round(($komposisi_tranche1_saham_personal*$return_saham_personal_tranche1 + $komposisi_tranche1_fi_personal*$return_fi_personal_tranche1 + $komposisi_tranche1_depo_personal*$return_depo_personal_tranche1+ $komposisi_tranche1_rdsaham_personal*$return_rdsaham_personal_tranche1+ $komposisi_tranche1_rdfi_personal*$return_rdfi_personal_tranche1+ $komposisi_tranche1_rdpu_personal*$return_rdpu_personal_tranche1+ $komposisi_tranche1_rdcampuran_personal*$return_rdcampuran_personal_tranche1)/100, 2); // perhitungan return portofolio tranche 1
      
      $risk_personal_tranche1_step1= $komposisi_tranche1_saham_personal*$risk_saham_personal_tranche1* $komposisi_tranche1_saham_personal*$risk_saham_personal_tranche1 + $komposisi_tranche1_fi_personal*$risk_fi_personal_tranche1* $komposisi_tranche1_fi_personal*$risk_fi_personal_tranche1 + $komposisi_tranche1_depo_personal*$risk_depo_personal_tranche1* $komposisi_tranche1_depo_personal*$risk_depo_personal_tranche1+ $komposisi_tranche1_rdsaham_personal*$risk_rdsaham_personal_tranche1* $komposisi_tranche1_rdsaham_personal*$risk_rdsaham_personal_tranche1+ $komposisi_tranche1_rdfi_personal*$risk_rdfi_personal_tranche1* $komposisi_tranche1_rdfi_personal*$risk_rdfi_personal_tranche1+ $komposisi_tranche1_rdpu_personal*$risk_rdpu_personal_tranche1* $komposisi_tranche1_rdpu_personal*$risk_rdpu_personal_tranche1+ $komposisi_tranche1_rdcampuran_personal*$risk_rdcampuran_personal_tranche1* $komposisi_tranche1_rdcampuran_personal*$risk_rdcampuran_personal_tranche1; // perhitungan risk portofolio tranche 1 step 2
      $risk_personal_tranche1_step2= 2*$komposisi_tranche1_saham_personal*$risk_saham_personal_tranche1* $komposisi_tranche1_fi_personal*$risk_fi_personal_tranche1* $korel_saham_fi_personal_tranche1 + 2*$komposisi_tranche1_saham_personal*$risk_saham_personal_tranche1* $komposisi_tranche1_depo_personal*$risk_depo_personal_tranche1* $korel_saham_depo_personal_tranche1 + 2*$komposisi_tranche1_saham_personal*$risk_saham_personal_tranche1* $komposisi_tranche1_rdsaham_personal*$risk_rdsaham_personal_tranche1* $korel_saham_rdsaham_personal_tranche1 + 2*$komposisi_tranche1_saham_personal*$risk_saham_personal_tranche1* $komposisi_tranche1_rdfi_personal*$risk_rdfi_personal_tranche1* $korel_saham_rdfi_personal_tranche1 + 2*$komposisi_tranche1_saham_personal*$risk_saham_personal_tranche1* $komposisi_tranche1_rdpu_personal*$risk_rdpu_personal_tranche1* $korel_saham_rdpu_personal_tranche1 + 2*$komposisi_tranche1_saham_personal*$risk_saham_personal_tranche1* $komposisi_tranche1_rdcampuran_personal*$risk_rdcampuran_personal_tranche1* $korel_saham_rdcampuran_personal_tranche1; // perhitungan risk portofolio tranche 1 step 2
      $risk_personal_tranche1_step3= 2*$komposisi_tranche1_fi_personal*$risk_fi_personal_tranche1* $komposisi_tranche1_depo_personal*$risk_depo_personal_tranche1* $korel_fi_depo_personal_tranche1 + 2*$komposisi_tranche1_fi_personal*$risk_fi_personal_tranche1* $komposisi_tranche1_rdsaham_personal*$risk_rdsaham_personal_tranche1* $korel_fi_rdsaham_personal_tranche1 + 2*$komposisi_tranche1_fi_personal*$risk_fi_personal_tranche1* $komposisi_tranche1_rdfi_personal*$risk_rdfi_personal_tranche1* $korel_fi_rdfi_personal_tranche1 + 2*$komposisi_tranche1_fi_personal*$risk_fi_personal_tranche1* $komposisi_tranche1_rdpu_personal*$risk_rdpu_personal_tranche1* $korel_fi_rdpu_personal_tranche1 + 2*$komposisi_tranche1_fi_personal*$risk_fi_personal_tranche1* $komposisi_tranche1_rdcampuran_personal*$risk_rdcampuran_personal_tranche1* $korel_fi_rdcampuran_personal_tranche1; // perhitungan risk portofolio tranche 1 step 3
      $risk_personal_tranche1_step4= 2*$komposisi_tranche1_depo_personal*$risk_depo_personal_tranche1* $komposisi_tranche1_rdsaham_personal*$risk_rdsaham_personal_tranche1* $korel_depo_rdsaham_personal_tranche1 + 2*$komposisi_tranche1_depo_personal*$risk_depo_personal_tranche1* $komposisi_tranche1_rdfi_personal*$risk_rdfi_personal_tranche1* $korel_depo_rdfi_personal_tranche1 + 2*$komposisi_tranche1_depo_personal*$risk_depo_personal_tranche1* $komposisi_tranche1_rdpu_personal*$risk_rdpu_personal_tranche1* $korel_depo_rdpu_personal_tranche1 + 2*$komposisi_tranche1_depo_personal*$risk_depo_personal_tranche1* $komposisi_tranche1_rdcampuran_personal*$risk_rdcampuran_personal_tranche1* $korel_depo_rdcampuran_personal_tranche1; // perhitungan risk portofolio tranche 1 step 4
      $risk_personal_tranche1_step5= 2*$komposisi_tranche1_rdsaham_personal*$risk_rdsaham_personal_tranche1* $komposisi_tranche1_rdfi_personal*$risk_rdfi_personal_tranche1* $korel_rdsaham_rdfi_personal_tranche1 + 2*$komposisi_tranche1_rdsaham_personal*$risk_rdsaham_personal_tranche1* $komposisi_tranche1_rdpu_personal*$risk_rdpu_personal_tranche1* $korel_rdsaham_rdpu_personal_tranche1 + 2*$komposisi_tranche1_rdsaham_personal*$risk_rdsaham_personal_tranche1* $komposisi_tranche1_rdcampuran_personal*$risk_rdcampuran_personal_tranche1* $korel_rdsaham_rdcampuran_personal_tranche1; // perhitungan risk portofolio tranche 1 step 5
      $risk_personal_tranche1_step6= 2*$komposisi_tranche1_rdfi_personal*$risk_rdfi_personal_tranche1* $komposisi_tranche1_rdpu_personal*$risk_rdpu_personal_tranche1* $korel_rdfi_rdpu_personal_tranche1 + 2*$komposisi_tranche1_rdfi_personal*$risk_rdfi_personal_tranche1* $komposisi_tranche1_rdcampuran_personal*$risk_rdcampuran_personal_tranche1* $korel_rdfi_rdcampuran_personal_tranche1; // perhitungan risk portofolio tranche 1 step 6
      $risk_personal_tranche1_step7= 2*$komposisi_tranche1_rdpu_personal*$risk_rdpu_personal_tranche1* $komposisi_tranche1_rdcampuran_personal*$risk_rdcampuran_personal_tranche1* $korel_rdpu_rdcampuran_personal_tranche1; // perhitungan risk portofolio tranche 1 step 7
      $risk_personal_tranche1= round((sqrt($risk_personal_tranche1_step1+$risk_personal_tranche1_step2+$risk_personal_tranche1_step3+$risk_personal_tranche1_step4+$risk_personal_tranche1_step5+$risk_personal_tranche1_step6+$risk_personal_tranche1_step7))/100, 2);// risiko all

      return array(
        "return_personal_tranche1" => $return_personal_tranche1,
        "risk_personal_tranche1" => $risk_personal_tranche1,
      );
    }
}