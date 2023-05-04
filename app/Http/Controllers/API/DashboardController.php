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

      //Output: Create $tahun dan $bulan ke masing-masing tahun dan bulan di database usia 
      $usia_tahun = array();
      $usia_bulan = array();
      
      for($year=2023; $year<=2100; $year++){
          for($month=1; $month<=12; $month++){
            
              if($year==2023 && $month==1){
                $tahun=(int)$diff->format('%y');

                $bulan=(int)$diff->format('%m');
                $bulan = $bulan +1;
              } else {
                if($bulan >=12){
                  $tahun = $tahun+1;

                  $bulan = 1;
                }
                $bulan = $bulan +1;
              }

              $key_tahun = $year . "_" . $month;
              $usia_tahun[$key_tahun] = $tahun;

              $key_bulan = $year . "_" . $month;
              $usia_bulan[$key_bulan] = $bulan;
          }
      }
      
      // -----------------------------------------------------------------------
      //C.2. Simulasi Basic - hitung Masa Dinas (masa dinas diisi dari januari 2023 s.d. desember 2100)
      $jml=936; // jumlah bulan dari januari 2023 s.d. desember 2100
      $date1=date_create($res->tgl_diangkat_pegawai); //Read tanggal diangkat
      $date2=date_create("2023-01-01"); //januari 2023
      $diff=date_diff($date1,$date2);

      //Output: Create $masa_dinas_tahun[$i] dan $masa_dinas_bulan[$i] ke masing-masing tahun dan bulan di database masa dinas
      $sisa_masa_kerja_tahun = array();
      $sisa_masa_kerja_bulan = array();
      
      for($year=2023; $year<=2100; $year++){
          for($month=1; $month<=12; $month++){
            
              if($year==2023 && $month==1){
                $tahun=(int)$diff->format('%y');
                $bulan=(int)$diff->format('%m');
                $bulan = $bulan +1;
              } else {
                if($bulan >=12){
                  $bulan = 1;
                  $tahun = $tahun+1;
                }
                $bulan = $bulan +1;
              }

              $key_tahun = $year . "_" . $month;
              $sisa_masa_kerja_tahun[$key_tahun] = $tahun;

              $key_bulan = $year . "_" . $month;
              $sisa_masa_kerja_bulan[$key_bulan] = $bulan;
          }
      }

      echo json_encode(array("sisa_masa_kerja_tahun"=>$sisa_masa_kerja_tahun, "sisa_masa_kerja_bulan"=>$sisa_masa_kerja_bulan));
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
