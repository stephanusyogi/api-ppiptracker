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
      $data_user = User::select('*')->where('id',$id_user)->get()[0];
      $date1 = date_create($data_user->tgl_lahir); //Read tanggal lahir
      $date2 = date_create($data_user->tgl_diangkat_pegawai); //Read tanggal diangkat

      $diff = date_diff($date1,$date2);

      $tahun = $diff->format('%y');
      $bulan = $diff->format('%m');

      // -----------------------------------------------------------------------
      //C.1. Simulasi Basic - hitung usia (usia diisi dari januari 2023 s.d. desember 2100)
      $jml=936; // jumlah bulan dari januari 2023 s.d. desember 2100
      $date1=date_create($data_user->tgl_lahir); //Read tanggal lahir
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
      $date1=date_create($data_user->tgl_diangkat_pegawai); //Read tanggal diangkat
      $date2=date_create("2023-01-01"); //januari 2023
      $diff=date_diff($date1,$date2);

      //Output: Create $masa_dinas_tahun[$i] dan $masa_dinas_bulan[$i] ke masing-masing tahun dan bulan di database masa dinas
      $sisa_masa_dinas_tahun = array();
      $sisa_masa_dinas_bulan = array();
      
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
              $sisa_masa_dinas_tahun[$key_tahun] = $tahun;

              $key_bulan = $year . "_" . $month;
              $sisa_masa_dinas_bulan[$key_bulan] = $bulan;
          }
      }

      
      // -----------------------------------------------------------------------
      //C.3. Simulasi Basic - sisa masa kerja (sisa masa kerja diisi dari januari 2023 s.d. desember 2100)
      $usia_pensiun=$data_user->usia_pensiun; //read usia pensiun
      $tahun_pensiun=$usia_pensiun - 1;
      $bulan_pensiun=12;
      
      $jml=936; // jumlah bulan dari januari 2023 s.d. desember 2100
      
      $sisa_kerja_tahun = array();
      $sisa_kerja_bulan = array();
     
      for($year=2023; $year<=2100; $year++){
          for($month=1; $month<=12; $month++){
            $usia_tahun=$usia_tahun["$year.'_'.$month"]; //read usia tahun saat januari 2023
            $usia_bulan=$usia_bulan["$year.'_'.$month"]; //read usia bulan saat januari 2023

            $sisa_kerja_tahun_hitung = $tahun_pensiun - $usia_tahun;
            $sisa_kerja_bulan_hitung = $bulan_pensiun - $usia_bulan;

            if($year==2023 && $month==1){  
                //konversi bulan dari posisi dari 1-12 ke 0-11
                if($sisa_kerja_bulan_hitung == 12){
                  $sisa_kerja_tahun_hitung = $sisa_kerja_tahun_hitung + 1;
                  $sisa_kerja_bulan_hitung = 0;
                }  
              
                //menurunkan bulan
                if($sisa_kerja_bulan_hitung<=0){
                  $sisa_kerja_tahun_hitung=$sisa_kerja_tahun_hitung-1;
                  $sisa_kerja_bulan_hitung=11;
                } else{
                  $sisa_kerja_bulan_hitung=$sisa_kerja_bulan_hitung-1;
                }
            
            } else {
              if($sisa_kerja_bulan<=0){
                  $sisa_kerja_tahun=$sisa_kerja_tahun-1;
                  $sisa_kerja_bulan=11;
              }
              $sisa_kerja_bulan=$sisa_kerja_bulan-1;
            }
            
            $key_tahun = $year . "_" . $month;
            $sisa_kerja_tahun[$key_tahun] = $tahun;

            $key_bulan = $year . "_" . $month;
            $sisa_kerja_bulan[$key_bulan] = $bulan;
          }
      }
      echo json_encode(array("sisa_masa_kerja_tahun"=>$sisa_kerja_tahun, "sisa_masa_kerja_bulan"=>$sisa_kerja_bulan));
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
