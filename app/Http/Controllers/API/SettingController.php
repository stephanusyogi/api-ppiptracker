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
  public function setting_nilai_asumsi_add(Request $request){
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
      "data" => $affected
  ],200);
  }
}