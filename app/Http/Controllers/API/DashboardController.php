<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use DateTime;
use DB;

class DashboardController extends Controller
{
    public function index(Request $request){
      $id_user = $request->input('id_user');

      //A.1 Hitung Target Replacement Ratio
      $res = DB::table('variabel_kuisioner_target_rr_answer')
        ->select("answer")
        ->where([
            ['id_user','=',$id_user],
            ['flag','=',1],
            ['kode_kuisioner','=',"TARGET_RR"],
        ])
        ->get()[0];
      $target_replacement_ratio = round($res->answer,2);

      // -----------------------------------------------------------------------

      //B.1 Hitung usia diangkat
      $res = User::select('tgl_lahir','tgl_diangkat_pegawai')->where('id',$id_user)->get()[0];
      $date1 = date_create($res->tgl_lahir); //Read tanggal lahir
      $date2 = date_create($res->tgl_diangkat_pegawai); //Read tanggal diangkat

      $diff = date_diff($date1,$date2);

      $tahun = $diff->format('%y');
      $bulan = $diff->format('%m');

      // -----------------------------------------------------------------------
      
      //C.1. Simulasi Basic - hitung usia (usia diisi dari januari 2023 s.d. desember 2100)
      $jml=936; // jumlah bulan dari januari 2023 s.d. desember 2100
      $date1=date_create($res->tgl_lahir); //Read tanggal lahir
      $date2=date_create("2023-01-01"); //januari 2023
      $diff=date_diff($date1,$date2);

      $tahun_bulan = array();
      
      for ($i=1;$i<=$jml;$i++){  
        if($i==1){
          $tahun=$diff->format('%y');
          $bulan=$diff->format('%m');
          //Output: Create $tahun dan $bulan ke masing-masing tahun dan bulan di database usia
          $bulan = $bulan +1;

          $tahun_bulan[] = array(
            "tahun" => $tahun,
            "bulan" => $bulan,
          );
        } else {
          if($bulan >=12){
            $bulan = 1;
            $tahun = $tahun+1;
          }
          //Output: Create $tahun dan $bulan ke masing-masing tahun dan bulan di database usia 
          $bulan = $bulan +1;
          
          $tahun_bulan[] = array(
            "tahun" => $tahun,
            "bulan" => $bulan,
          );
        } 
      }
      
      echo json_encode($tahun_bulan, true);
      die();
      return response()->json([
        "status" =>true,
        "message"=>"Testing Hitung Awal!",
        "data_testing" => array(
          "tahun" => $tahun,
          "bulan" => $bulan,
        )
      ],200);
    }
}
