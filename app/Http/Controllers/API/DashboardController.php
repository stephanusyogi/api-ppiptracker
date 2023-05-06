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
            if($year==2023 && $month==1){  
              $usia_tahun=$usia_tahun["2023_1"]; //read usia tahun saat januari 2023
              $usia_bulan=$usia_bulan["2023_1"]; //read usia bulan saat januari 2023
  
              $sisa_kerja_tahun_hitung = $tahun_pensiun - $usia_tahun;
              $sisa_kerja_bulan_hitung = $bulan_pensiun - $usia_bulan;
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
              if($sisa_kerja_bulan_hitung<=0){
                  $sisa_kerja_tahun_hitung=$sisa_kerja_tahun_hitung-1;
                  $sisa_kerja_bulan_hitung=11;
              }
              $sisa_kerja_bulan_hitung=$sisa_kerja_bulan_hitung-1;
            }
            
            $key_tahun = $year . "_" . $month;
            $sisa_kerja_tahun[$key_tahun] = $sisa_kerja_tahun_hitung;

            $key_bulan = $year . "_" . $month;
            $sisa_kerja_bulan[$key_bulan] = $sisa_kerja_bulan_hitung;
          }
      }

      // -----------------------------------------------------------------------
      //C.4. Flag Pensiun/belum pensiun 

      $flag_pensiun = array();
      
      for($year=2023; $year<=2100; $year++){
        for($month=1; $month<=12; $month++){
          $key = $year . "_" . $month;
          $flag_sisa_kerja_tahun=$sisa_kerja_tahun[$key];//Read sisa masa kerja tahun
          $flag_sisa_kerja_bulan=$sisa_kerja_bulan[$key];//Read sisa masa kerja bulan
          
          if($flag_sisa_kerja_tahun<0){
            $flag=1;//sudah pensiun
          } else {
            $flag=0;//belum pensiun
          }
          $flag_pensiun[$key] = $flag;
        }
      }
      
      // -----------------------------------------------------------------------
    //D. Hitung Montecarlo PPIP
    //Input: Read sisa masa kerja tahun saat awal tahun, portofolio investasi PPIP yang dipilih peserta, return dan risk portofolio ppip, tabel normal inverse;
    $setting_ppip_user = DB::table('setting_portofolio_ppip')->select('*')
      ->where('id_user', $id_user)
      ->where('flag', 1)
      ->get()[0];
     
    // Tabel Norm Inverse
    $tabel_norminv = DB::table('distribusi_normal')->select('norm_inv')
      ->get()->toArray();
    for ($i=1;$i<count($tabel_norminv);$i++){ //$i adalah primary key dari tabel normal inverse yang ada di database
        $norminv[$i]=$tabel_norminv[$i]->norm_inv;//Read tabel normal inverse
    }
    for($year=2023; $year<=2100; $year++){

    }
    echo json_encode($norminv, true);
    die();

      return response()->json([
        "status" =>true,
        "message"=>"Testing Hitung Awal!",
      ],200);
    }
}
