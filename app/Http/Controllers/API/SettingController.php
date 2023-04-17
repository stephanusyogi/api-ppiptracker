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

  public function setting_ppip(){
    $setting_ppip = DB::table('setting_portofolio_ppip_admin')
    ->select('*')->get();

    $opsi = DB::table('setting_portofolio_ppip_admin')
    ->select('id','nama_portofolio')->get();

    return response()->json([
        "status" =>true,
        "message"=>"Lists Setting PPIP!",
        "opsi" => $opsi,
        "data" => $setting_ppip
    ],200);
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
        'korelasi_pendapatan_tetap_deposito	' => $request->korelasi_pendapatan_tetap_deposito,
        'tranche_investasi_saham	' => $request->tranche_investasi_saham,
        'tranche_investasi_pendapatan_tetap		' => $request->tranche_investasi_pendapatan_tetap	,
        'tranche_investasi_deposito	' => $request->tranche_investasi_deposito,
        'tranche_likuiditas_saham	' => $request->tranche_likuiditas_saham,
        'tranche_likuiditas_pendapatan_tetap		' => $request->tranche_likuiditas_pendapatan_tetap,
        'tranche_likuiditas_deposito		' => $request->tranche_likuiditas_deposito,
        'return_portofolio_tranche_investasi		' => $request->return_portofolio_tranche_investasi,
        'return_portofolio_tranche_likuiditas		' => $request->return_portofolio_tranche_likuiditas,
        'flag		' => 1,
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
        'korelasi_pendapatan_tetap_deposito	' => $request->korelasi_pendapatan_tetap_deposito,
        'tranche_investasi_saham	' => $request->tranche_investasi_saham,
        'tranche_investasi_pendapatan_tetap		' => $request->tranche_investasi_pendapatan_tetap	,
        'tranche_investasi_deposito	' => $request->tranche_investasi_deposito,
        'tranche_likuiditas_saham	' => $request->tranche_likuiditas_saham,
        'tranche_likuiditas_pendapatan_tetap		' => $request->tranche_likuiditas_pendapatan_tetap,
        'tranche_likuiditas_deposito		' => $request->tranche_likuiditas_deposito,
        'return_portofolio_tranche_investasi		' => $request->return_portofolio_tranche_investasi,
        'return_portofolio_tranche_likuiditas		' => $request->return_portofolio_tranche_likuiditas,
        'flag		' => $request->flag,
      ]);
    return response()->json([
        "status" =>true,
        "message"=>"Setting Nilai Asumsi Diperbarui!",
    ],200);
  }
}