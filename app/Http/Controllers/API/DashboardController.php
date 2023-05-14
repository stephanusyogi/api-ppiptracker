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

      // Get Input Form Data
      $tgl_update_gaji_phdp = $request->tgl_update_gaji_phdp;
      $gaji = $request->gaji;
      $phdp = $request->phdp;

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
      $date1=date_create($data_user->tgl_lahir); //Read tanggal lahir
      $date2=date_create("2023-01-31"); //januari 2023
      $diff=date_diff($date1,$date2);
      
      /* cek tahun dan bulan
      $tahun=(int)$diff->format('%y');
      $bulan=(int)$diff->format('%m');
      echo json_encode($tahun, true);
      echo json_encode($bulan, true);
      die();
      */

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
       //echo json_encode($usia_tahun, true);
       //die();
      // -----------------------------------------------------------------------
      //C.2. Simulasi Basic - hitung Masa Dinas (masa dinas diisi dari januari 2023 s.d. desember 2100)
      $date1=date_create($data_user->tgl_diangkat_pegawai); //Read tanggal diangkat
      $date2=date_create("2023-01-31"); //januari 2023
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
                } else {
                $bulan = $bulan +1;
                }
              }

              $key_tahun = $year . "_" . $month;
              $sisa_masa_dinas_tahun[$key_tahun] = $tahun;
              $key_bulan = $year . "_" . $month;
              $sisa_masa_dinas_bulan[$key_bulan] = $bulan;
          }
      }
       echo json_encode($sisa_masa_dinas_bulan, true);
       echo json_encode($sisa_masa_dinas_tahun, true);
       die();
      // -----------------------------------------------------------------------
      //C.3. Simulasi Basic - sisa masa kerja (sisa masa kerja diisi dari januari 2023 s.d. desember 2100)
      $usia_pensiun=$data_user->usia_pensiun; //read usia pensiun
      $tahun_pensiun=$usia_pensiun - 1;
      $bulan_pensiun=12;

      //Output: Create $tahun dan $bulan ke masing-masing tahun dan bulan di database usia 
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
      
      // Tabel Norm Inverse
      $tabel_norminv = DB::table('distribusi_normal')->select('norm_inv')
        ->get()->toArray();
      for ($i=1;$i<count($tabel_norminv);$i++){ //$i adalah primary key dari tabel normal inverse yang ada di database
          $norminv[$i]=$tabel_norminv[$i]->norm_inv;//Read tabel normal inverse
      }
      
      // -----------------------------------------------------------------------
      //D. Hitung Montecarlo PPIP
      $montecarlo_ppip = $this->montecarlo_ppip($id_user, $sisa_kerja_tahun, $flag_pensiun, $norminv);

      // -----------------------------------------------------------------------
      //E. Hitung Montecarlo Personal Keuangan
      $this->montecarlo_personal($id_user, $sisa_kerja_tahun, $flag_pensiun, $norminv);
      
      //---------------------------------------------------------
      //F. Perhitungan Simulasi
      //F.1. Simulasi Gaji dan PhDP
      $return_simulasi_gaji_phdp = $this->simulasi_gaji_phdp($tgl_update_gaji_phdp, $gaji, $phdp, $id_user);
      //F.2. Simulasi PPMP
      $return_simulasi_ppmp = $this->simulasi_ppmp($data_user, $id_user, $sisa_masa_dinas_tahun, $sisa_masa_dinas_bulan, $flag_pensiun, $return_simulasi_gaji_phdp);
      //F.3. Simulasi PPIP
      $this->simulasi_ppip($data_user, $id_user, $return_simulasi_ppmp, $flag_pensiun, $return_simulasi_gaji_phdp, $montecarlo_ppip);

      return response()->json([
        "status" =>true,
        "message"=>"Testing Hitung Awal!",
      ],200);
    }

    public function montecarlo_ppip($id_user, $sisa_kerja_tahun, $flag_pensiun, $norminv){
      //Input: Read sisa masa kerja tahun saat awal tahun, portofolio investasi PPIP yang dipilih peserta, return dan risk portofolio ppip, tabel normal inverse;
      $setting_ppip_user = DB::table('setting_portofolio_ppip')->select('*')
      ->where('id_user', $id_user)
      ->where('flag', 1)
      ->get()[0];


      //mulai perhitungan
      $tranche_ppip = array();
      $return_ppip = array();
      $risk_ppip = array();

      $nab_ppip = array();

      $percentile_95_nab_ppip = array();
      $percentile_50_nab_ppip = array();
      $percentile_05_nab_ppip = array();

      $z=1; //untuk konversi $flag_pensiun[$i] dari bulanan ke tahunan
      for($year=2023; $year<=2100; $year++){
        $key_loop = $year;
        $key_tahun = $year . "_1";
        $sisa_kerja_tahun_hitung = $sisa_kerja_tahun[$key_tahun];//Read sisa masa kerja tahun setiap bulan januari
        $flag_pensiun_hitung = $flag_pensiun[$key_tahun];//Read flag pensiun setiap bulan januari
        $z=$z+12;

        //+++++++++++++++++++++++++++++++++
        //D.1., D.2., dan D.3. Hitung Montecarlo PPIP - hitung tranche, return, dan risk
        if($sisa_kerja_tahun_hitung>=2){
          $tranche_ppip_hitung = "investasi";//untuk sisa masa kerja lebih dari atau sama dengan 2 tahun , masuk ke tranche investasi
          $return_ppip_hitung = $setting_ppip_user->return_portofolio_tranche_investasi;//read return portofolio dari PPIP dengan $pilihan_ppip dan tranche investasi
          $risk_ppip_hitung = $setting_ppip_user->resiko_portofolio_tranche_investasi;//read risk portofolio dari PPIP dengan $pilihan_ppip dan tranche investasi
        } else if ($sisa_kerja_tahun_hitung<2 && $flag_pensiun_hitung == 0 ){ //flag pensiun =0 menandakan belum pensiun
          $tranche_ppip_hitung = "likuiditas";//untuk sisa masa kerja kurang dari 2 tahun , masuk ke tranche likuiditas
          $return_ppip_hitung = $setting_ppip_user->return_portofolio_tranche_likuiditas;//read return portofolio dari PPIP dengan $pilihan_ppip dan tranche likuiditas
          $risk_ppip_hitung = $setting_ppip_user->resiko_portofolio_tranche_likuiditas;//read risk portofolio dari PPIP dengan $pilihan_ppip dan tranche likuiditas
        } else {
          $tranche_ppip_hitung = "null";//sudah pensiun
          $return_ppip_hitung = "null";//sudah pensiun
          $risk_ppip_hitung = "null";//sudah pensiun
        }
        //Output: Create $tranche_ppip[$i], $return_ppip[$i], $risk_ppip[$i]
        $tranche_ppip[$key_loop] = $tranche_ppip_hitung;
        $return_ppip[$key_loop] = $return_ppip_hitung;
        $risk_ppip[$key_loop] = $risk_ppip_hitung;

        //+++++++++++++++++++++++++++++++++
        //D.4. Hitung Montecarlo PPIP - hitung NAB
        if($tranche_ppip_hitung != "null"){ //jika masih belum pensiun
          $previous_nab = null;
          for($j=1;$j<=10000;$j++){      //monte carlo 10.000 iterasi
              if($j==1){ // untuk perhitungan awal (karena angka sebelumnya indeks dari NAB adalah 100)
                  $acak = mt_rand(1,10000); //generate angka acak dari 1 s.d. 10.000. (angka acak sesuai dengan primary key dari tabel normal inverse dalam database)
                  $nab_ppip_hitung = round(100 * (1 + ($return_ppip_hitung / 100) + (($risk_ppip_hitung / 100) * $norminv[$acak]) ),2);
              } else{
                  $acak = mt_rand(1,10000); //generate angka acak dari 1 s.d. 10.000. (angka acak sesuai dengan primary key dari tabel normal inverse dalam database)
                  $nab_ppip_hitung = round($previous_nab * (1 + ($return_ppip_hitung / 100) + (($risk_ppip_hitung / 100) * $norminv[$acak]) ),2);
              }
              $nab_ppip[$key_loop] = $nab_ppip_hitung;
              $previous_nab = $nab_ppip[$key_loop];
          }
        } else{ //jika sudah pensiun
          for($j=1;$j<=10000;$j++){ //monte carlo 10.000 iterasi
              $nab_ppip_hitung=0;
              $nab_ppip[$key_loop] = $nab_ppip_hitung;
          }
        }

        //+++++++++++++++++++++++++++++++++
        //D.5., D.6., dan D.7. Hitung Montecarlo PPIP - hitung percentile 95, 50, dan 5 dari NAB
        //Input: NAB yang telah dihitung sebelumnya
        if($tranche_ppip_hitung != "null"){ //jika masih belum pensiun
            $k=0;
            for ($j=1;$j<=10000;$j++){
              $percentile_temp1[$k]=$nab_ppip_hitung; //loading sementara isi dari NAB untuk kemudian di shorting
              $k++;
            }
            
            sort($percentile_temp1); //shorting array
            
            $k=0;
            for ($j=1;$j<=10000;$j++){
              $percentile_temp2[$j]=$percentile_temp1[$k]; //mengembalikan lagi ke urutan array yang telah disortir
              $k++;
            }
            
            $percentile_95_nab_ppip_hitung = $percentile_temp2[round(0.95 * 10000)]; //mengambil nilai percentile 95
            $percentile_50_nab_ppip_hitung = $percentile_temp2[round(0.5 * 10000)]; //mengambil nilai percentile 50
            $percentile_05_nab_ppip_hitung = $percentile_temp2[round(0.05 * 10000)]; //mengambil nilai percentile 5
          
        } else {
          $percentile_95_nab_ppip_hitung = 0; // nilai percentile 95 saat sudah pensiun
          $percentile_50_nab_ppip_hitung = 0; // nilai percentile 50 saat sudah pensiun
          $percentile_05_nab_ppip_hitung = 0; // nilai percentile 5 saat sudah pensiun
        }

        //Output: Create $percentile_95_nab_ppip[$i], $percentile_50_nab_ppip[$i], dan $percentile_05_nab_ppip[$i]
        $percentile_95_nab_ppip[$key_loop] = $percentile_95_nab_ppip_hitung;
        $percentile_50_nab_ppip[$key_loop] = $percentile_50_nab_ppip_hitung;
        $percentile_05_nab_ppip[$key_loop] = $percentile_05_nab_ppip_hitung;
      }

      // -----------------------------------------------------------------------
      //D.8., D.9., dan D.10. Hitung Montecarlo PPIP - hitung return dari Percentile NAB
      //termasuk dengan convert monthly di D.11., D.12., dan D.13. Hitung Montecarlo PPIP - hitung return dari Percentile NAB - convert monthly
      $percentile_95_return_ppip=array();
      $percentile_50_return_ppip=array();
      $percentile_05_return_ppip=array();

      $percentile_95_return_monthly_ppip=array();
      $percentile_50_return_monthly_ppip=array();
      $percentile_05_return_monthly_ppip=array();

      $previous_percentile_95_nab_ppip = null;
      $previous_percentile_50_nab_ppip = null;
      $previous_percentile_05_nab_ppip = null;
      for($year=2023; $year<=2100; $year++){
        if ($tranche_ppip[$year] != "null"){ //jika masih belum pensiun
          if ($year==2023){
            //tahunan
            $percentile_95_return_ppip_hitung = ($percentile_95_nab_ppip[$year]/100)-1;
            $percentile_50_return_ppip_hitung = ($percentile_50_nab_ppip[$year]/100)-1;
            $percentile_05_return_ppip_hitung = ($percentile_05_nab_ppip[$year]/100)-1;
            
            //convert monthly
            $percentile_95_return_monthly_ppip_hitung = ((1+$percentile_95_return_ppip_hitung)^(1/12))-1;
            $percentile_50_return_monthly_ppip_hitung = ((1+$percentile_50_return_ppip_hitung)^(1/12))-1;
            $percentile_05_return_monthly_ppip_hitung = ((1+$percentile_05_return_ppip_hitung)^(1/12))-1;
          } else {
            //tahunan
            $percentile_95_return_ppip_hitung = ($percentile_95_nab_ppip[$year]/$previous_percentile_95_nab_ppip)-1;
            $percentile_50_return_ppip_hitung = ($percentile_50_nab_ppip[$year]/$previous_percentile_50_nab_ppip)-1;
            $percentile_05_return_ppip_hitung = ($percentile_05_nab_ppip[$year]/$previous_percentile_05_nab_ppip)-1;
            
            //convert monthly
            $percentile_95_return_monthly_ppip_hitung = ((1+$percentile_95_return_ppip_hitung)^(1/12))-1;
            $percentile_50_return_monthly_ppip_hitung = ((1+$percentile_50_return_ppip_hitung)^(1/12))-1;
            $percentile_05_return_monthly_ppip_hitung = ((1+$percentile_05_return_ppip_hitung)^(1/12))-1;
          }
        } else {
            $percentile_95_return_ppip_hitung = 0;
            $percentile_50_return_ppip_hitung = 0;
            $percentile_05_return_ppip_hitung = 0;
          
            $percentile_95_return_monthly_ppip_hitung = 0;
            $percentile_50_return_monthly_ppip_hitung = 0;
            $percentile_05_return_monthly_ppip_hitung = 0;	
        }

        //Output: Create $percentile_95_return_ppip[$i], $percentile_50_return_ppip[$i], $percentile_05_return_ppip[$i], $percentile_95_return_monthly_ppip[$i], $percentile_50_return_monthly_ppip[$i], dan $percentile_05_return_monthly_ppip[$i]
        $percentile_95_return_ppip[$year]=$percentile_95_return_ppip_hitung;
        $percentile_50_return_ppip[$year]=$percentile_50_return_ppip_hitung;
        $percentile_05_return_ppip[$year]=$percentile_05_return_ppip_hitung;

        $previous_percentile_95_nab_ppip = $percentile_95_nab_ppip[$year];
        $previous_percentile_50_nab_ppip = $percentile_50_nab_ppip[$year];
        $previous_percentile_05_nab_ppip = $percentile_05_nab_ppip[$year];

        $percentile_95_return_monthly_ppip[$year]=$percentile_95_return_monthly_ppip_hitung;
        $percentile_50_return_monthly_ppip[$year]=$percentile_50_return_monthly_ppip_hitung;
        $percentile_05_return_monthly_ppip[$year]=$percentile_05_return_monthly_ppip_hitung;
      }
      return array(
        "tranche_ppip" => $tranche_ppip,
        "return_ppip" => $return_ppip,
        "risk_ppip" => $risk_ppip,
        "nab_ppip" => $nab_ppip,
        "percentile_95_nab_ppip" => $percentile_95_nab_ppip,
        "percentile_50_nab_ppip" => $percentile_50_nab_ppip,
        "percentile_05_nab_ppip" => $percentile_05_nab_ppip,
        "percentile_95_return_ppip" => $percentile_95_return_ppip,
        "percentile_50_return_ppip" => $percentile_50_return_ppip,
        "percentile_05_return_ppip" => $percentile_05_return_ppip,
        "percentile_95_return_monthly_ppip" => $percentile_95_return_monthly_ppip,
        "percentile_50_return_monthly_ppip" => $percentile_50_return_monthly_ppip,
        "percentile_05_return_monthly_ppip" => $percentile_05_return_monthly_ppip,
      );
    }

    public function montecarlo_personal($id_user, $sisa_kerja_tahun, $flag_pensiun, $norminv){
      //Input: Read sisa masa kerja tahun saat awal tahun, portofolio investasi Personal yang dipilih peserta, return dan risk portofolio Personal, tabel normal inverse;
      $setting_personal_lifecycle_user = array();
      // Personal Keuangan
      $setting_personal_user = DB::table('setting_portofolio_personal')->select('*')
      ->where('id_user', $id_user)
      ->where('flag', 1)
      ->get()[0];

      // Lifecycle
      $setting_lifecycle_user = DB::table('setting_komposisi_investasi_lifecycle_fund')->select('*')
      ->where('id_user', $id_user)
      ->where('flag', 1)
      ->get()[0];
      $setting_personal_lifecycle_user["personal_keuangan"] = $setting_personal_user;
      $setting_personal_lifecycle_user["komposisi_investasi"] = $setting_lifecycle_user;
      
      $tranche_personal = array();
      $return_personal = array();
      $risk_personal = array();

      $nab_personal = array();
      
      $percentile_95_nab_personal = array();
      $percentile_50_nab_personal = array();
      $percentile_05_nab_personal = array();

      for($year=2023; $year<=2100; $year++){
        $key_tahun = $year . "_1";
        $sisa_kerja_tahun_hitung = $sisa_kerja_tahun[$key_tahun];//Read sisa masa kerja tahun setiap bulan januari
        $flag_pensiun_hitung = $flag_pensiun[$key_tahun];//Read flag pensiun setiap bulan januari

        //+++++++++++++++++++++++++++++++++
        //E.1., E.2., dan E.3. Hitung Montecarlo Personal - hitung tranche, return, dan risk
        if($sisa_kerja_tahun_hitung>=7){
          $tranche_personal_hitung = "tranche 1";//untuk sisa masa kerja lebih dari atau sama dengan 7 tahun , masuk ke tranche 1
          $return_personal_hitung = $setting_personal_lifecycle_user["komposisi_investasi"]->return_portofolio_personal_t1;//read return portofolio personal dengan $pilihan_personal dan tranche 1
          $risk_personal_hitung = $setting_personal_lifecycle_user["komposisi_investasi"]->resiko_pasar_portofolio_personal_t1;//read risk portofolio personal dengan $pilihan_personal dan tranche 1
        } else if($sisa_kerja_tahun_hitung>=2){
          $tranche_personal_hitung = "tranche 2";//untuk sisa masa kerja kurang dari 7 tahun sampai dengan 2 tahun , masuk ke tranche 2
          $return_personal_hitung = $setting_personal_lifecycle_user["komposisi_investasi"]->return_portofolio_personal_t2;//read return portofolio personal dengan $pilihan_personal dan tranche 2
          $risk_personal_hitung = $setting_personal_lifecycle_user["komposisi_investasi"]->resiko_pasar_portofolio_personal_t2;//read risk portofolio personal dengan $pilihan_personal dan tranche 2
        } else if ($sisa_kerja_tahun_hitung<2 && $flag_pensiun_hitung == 0 ){ //flag pensiun =0 menandakan belum pensiun
          $tranche_personal_hitung = "tranche 3";//untuk sisa masa kerja kurang dari 2 tahun , masuk ke tranche 3
          $return_personal_hitung = $setting_personal_lifecycle_user["komposisi_investasi"]->return_portofolio_personal_t3;//read return portofolio personal dengan $pilihan_personal dan tranche 3
          $risk_personal_hitung = $setting_personal_lifecycle_user["komposisi_investasi"]->resiko_pasar_portofolio_personal_t3;//read risk portofolio personal dengan $pilihan_personal dan tranche 3
        } else {
          $tranche_personal_hitung = "null";//sudah pensiun
          $return_personal_hitung = "null";//sudah pensiun
          $risk_personal_hitung = "null";//sudah pensiun
        }
        //Output: Create $tranche_personal[$i], $return_personal[$i], $risk_personal[$i]
        $tranche_personal[$year] = $tranche_personal_hitung;
        $return_personal[$year] = $return_personal_hitung;
        $risk_personal[$year] = $risk_personal_hitung;

        //+++++++++++++++++++++++++++++++++
        //E.4. Hitung Montecarlo personal - hitung NAB
        if($tranche_personal_hitung != "null"){ //jika masih belum pensiun
          $previous_nab_personal = null;
          for($l=1;$l<=10000;$l++){      //monte carlo 10.000 iterasi
            if($l==1){ // untuk perhitungan awal (karena angka sebelumnya indeks dari NAB adalah 100)
                $acak = mt_rand(1,10000); //generate angka acak dari 1 s.d. 10.000. (angka acak sesuai dengan primary key dari tabel normal inverse dalam database)
                $nab_personal_hitung = round(100 * (1 + ($return_personal_hitung / 100) + (($risk_personal_hitung / 100) * $norminv[$acak]) ),2);
            } else{
                $acak = mt_rand(1,10000); //generate angka acak dari 1 s.d. 10.000. (angka acak sesuai dengan primary key dari tabel normal inverse dalam database)
                $nab_personal_hitung = round($previous_nab_personal * (1 + ($return_personal_hitung / 100) + (($risk_personal_hitung / 100) * $norminv[$acak]) ),2);
            }
            $nab_personal[$year] = round($nab_personal_hitung, 2);
            $previous_nab_personal = $nab_personal[$year];
          }
        } else{ //jika sudah pensiun
          for($l=1;$l<=10000;$l++){ //monte carlo 10.000 iterasi
              $nab_personal_hitung = 0;
              $nab_personal[$year] = round($nab_personal_hitung, 2);
          }
        }

        //+++++++++++++++++++++++++++++++++
        //E.5., E.6., dan E.7. Hitung Montecarlo PERSONAL - hitung percentile 95, 50, dan 5 dari NAB
        //Input: NAB yang telah dihitung sebelumnya
        if($tranche_personal_hitung != "null"){ //jika masih belum pensiun
          $k=0;
          for ($j=1;$j<=10000;$j++){
            $percentile_temp1[$k]=$nab_personal_hitung; //loading sementara isi dari NAB untuk kemudian di shorting
            $k++;
          }
          
          sort($percentile_temp1); //shorting array
          
          $k=0;
          for ($j=1;$j<=10000;$j++){
            $percentile_temp2[$j]=$percentile_temp1[$k]; //mengembalikan lagi ke urutan array yang telah disortir
            $k++;
          }
          
          $percentile_95_nab_personal_hitung=$percentile_temp2[round(0.95 * 10000)]; //mengambil nilai percentile 95
          $percentile_50_nab_personal_hitung=$percentile_temp2[round(0.5 * 10000)]; //mengambil nilai percentile 50
          $percentile_05_nab_personal_hitung=$percentile_temp2[round(0.05 * 10000)]; //mengambil nilai percentile 5
        } else {
          $percentile_95_nab_personal_hitung=0; // nilai percentile 95 saat sudah pensiun
          $percentile_50_nab_personal_hitung=0; // nilai percentile 50 saat sudah pensiun
          $percentile_05_nab_personal_hitung=0; // nilai percentile 5 saat sudah pensiun
        }
        //Output: Create $percentile_95_nab_personal[$i], $percentile_50_nab_personal[$i], dan $percentile_05_nab_personal[$i]
        $percentile_95_nab_personal[$year] = $percentile_95_nab_personal_hitung;
        $percentile_50_nab_personal[$year] = $percentile_50_nab_personal_hitung;
        $percentile_05_nab_personal[$year] = $percentile_05_nab_personal_hitung;
      }

      //--------------------------------------------------------
      //E.8., E.9., dan E.10. Hitung Montecarlo PERSONAL - hitung return dari Percentile NAB
      //termasuk dengan convert monthly di E.11., E.12., dan E.13. Hitung Montecarlo PERSONAL - hitung return dari Percentile NAB - convert monthly
      $percentile_95_return_personal=array();
      $percentile_50_return_personal=array();
      $percentile_05_return_personal=array();

      $percentile_95_return_monthly_personal=array();
      $percentile_50_return_monthly_personal=array();
      $percentile_05_return_monthly_personal=array();

      $previous_percentile_95_nab_personal = null;
      $previous_percentile_50_nab_personal = null;
      $previous_percentile_05_nab_personal = null;

      for($year=2023; $year<=2100; $year++){
        $key_tahun = $year . "_1";
        if ($tranche_personal[$year] != "null"){ //jika masih belum pensiun
          if ($year==2023){
            
            //tahunan
            $percentile_95_return_personal_hitung=($percentile_95_nab_personal[$year]/100)-1;
            $percentile_50_return_personal_hitung=($percentile_50_nab_personal[$year]/100)-1;
            $percentile_05_return_personal_hitung=($percentile_05_nab_personal[$year]/100)-1;
            
            //convert monthly
            $percentile_95_return_monthly_personal_hitung=((1+$percentile_95_return_personal_hitung)^(1/12))-1;
            $percentile_50_return_monthly_personal_hitung=((1+$percentile_50_return_personal_hitung)^(1/12))-1;
            $percentile_05_return_monthly_personal_hitung=((1+$percentile_05_return_personal_hitung)^(1/12))-1;
          } else {
            
            //tahunan
            $percentile_95_return_personal_hitung=($percentile_95_nab_personal[$year]/$previous_percentile_95_nab_personal)-1;
            $percentile_50_return_personal_hitung=($percentile_50_nab_personal[$year]/$previous_percentile_50_nab_personal)-1;
            $percentile_05_return_personal_hitung=($percentile_05_nab_personal[$year]/$previous_percentile_05_nab_personal)-1;
            
            //convert monthly
            $percentile_95_return_monthly_personal_hitung=((1+$percentile_95_return_personal_hitung)^(1/12))-1;
            $percentile_50_return_monthly_personal_hitung=((1+$percentile_50_return_personal_hitung)^(1/12))-1;
            $percentile_05_return_monthly_personal_hitung=((1+$percentile_05_return_personal_hitung)^(1/12))-1;
          }
        } else {
            $percentile_95_return_personal_hitung=0;
            $percentile_50_return_personal_hitung=0;
            $percentile_05_return_personal_hitung=0;
          
            $percentile_95_return_monthly_personal_hitung=0;
            $percentile_50_return_monthly_personal_hitung=0;
            $percentile_05_return_monthly_personal_hitung=0;	
        }
        //Output: Create $percentile_95_return_personal[$i], $percentile_50_return_personal[$i], $percentile_05_return_personal[$i], $percentile_95_return_monthly_personal[$i], $percentile_50_return_monthly_personal[$i], dan $percentile_05_return_monthly_personal[$i]
        $percentile_95_return_personal[$year]=$percentile_95_return_personal_hitung;
        $percentile_50_return_personal[$year]=$percentile_50_return_personal_hitung;
        $percentile_05_return_personal[$year]=$percentile_05_return_personal_hitung;

        $previous_percentile_95_nab_personal = $percentile_95_return_personal[$year];
        $previous_percentile_50_nab_personal = $percentile_50_return_personal[$year];
        $previous_percentile_05_nab_personal = $percentile_05_return_personal[$year];

        $percentile_95_return_monthly_personal[$year]=$percentile_95_return_monthly_personal_hitung;
        $percentile_50_return_monthly_personal[$year]=$percentile_50_return_monthly_personal_hitung;
        $percentile_05_return_monthly_personal[$year]=$percentile_05_return_monthly_personal_hitung;
      }
    }

    public function simulasi_gaji_phdp($tgl_update_gaji_phdp, $gaji_form, $phdp_form,  $id_user){
      //Input: Read inputan user tentang gaji dan PhDP, tanggal input
      $timestamp = strtotime($tgl_update_gaji_phdp);
      $bulan=date('n', $timestamp);//Read bulan input
      $tahun=date('Y', $timestamp);// Read tahun input
      $kode_input=($tahun*100)+$bulan; //untuk koding input
      
      $gaji_input=(int)$gaji_form; //Read gaji yang diinput
      $phdp_input=(int)$phdp_form; //Read phdp yang diinput

      /*
      $saldo_ppip_input=0; //numpang untuk mengisi saldo ppip, Read saldo ppip yang diinput
      $saldo_personal_keuangan_input=0;//numpang untuk mengisi saldo personal keuangan, Read saldo keuangan keuangan yang diinput
      $saldo_personal_properti_input=0;//numpang untuk mengisi saldo personal properti, Read saldo properti keuangan yang diinput
      */

      //counter letak saldo ppip dan personal
      $counter_saldo_ppip="2023_1";
      $counter_saldo_personal_keuangan="2023_1";
      $counter_saldo_personal_properti="2023_1";
      
      $setting_nilai_asumsi_user = DB::table('nilai_asumsi_user')
            ->where('id_user', $id_user)
            ->where('flag', 1)
            ->select('*')->get()[0];

      $gaji_naik = $setting_nilai_asumsi_user->kenaikan_gaji;//Read kenaikan gaji di admin
      $phdp_naik = $setting_nilai_asumsi_user->kenaikan_phdp;//Read kenaikan phdp di admin

      $year = 2023; //tahun awal di database
      $k=1;
      $kode = ($year*100)+$k; //untuk perbandingan kode input

      $gaji = array();
      $phdp = array();

      $previous_gaji = null;
      $previous_phdp = null;
      for($year=2023; $year<=2100; $year++){
        for($month=1; $month<=12; $month++){
          $key = $year . "_" . $month;
          if($kode < $kode_input){
            if($k==12){
              $gaji_hitung = 0;
              $phdp_hitung = 0;
              /*
              $saldo_ppip_sementara=0; //numpang untuk mengisi saldo ppip
              $saldo_personal_keuangan[$i]=0;//numpang untuk mengisi saldo personal keuangan
              $saldo_personal_properti[$i]=0;//numpang untuk mengisi saldo personal properti
              */
              $j = $year+1;
              $k=1;
              $kode = ($j*100)+$k;
            } else{
              $gaji_hitung = 0;
              $phdp_hitung = 0;
              /*
              $saldo_ppip[$i]=0; //numpang untuk mengisi saldo ppip
              $saldo_personal_keuangan[$i]=0;//numpang untuk mengisi saldo personal keuangan
              $saldo_personal_properti[$i]=0;//numpang untuk mengisi saldo personal properti
              */
              $k=$k+1;
              $kode=($year*100)+$k;
            }
          } else if ($kode == $kode_input){
            if($k==12){
              $gaji_hitung = $gaji_input;
              $phdp_hitung = $phdp_input;
              /*
              $saldo_ppip[$i]=$saldo_ppip_input; //numpang untuk mengisi saldo ppip
              $saldo_personal_keuangan[$i]=$saldo_personal_keuangan_input;//numpang untuk mengisi saldo personal keuangan
              $saldo_personal_properti[$i]=$saldo_personal_properti_input;//numpang untuk mengisi saldo personal properti
              */
              $counter_saldo_ppip_hitung = $key; //numpang kode counter, untuk menandai mulai isi saldo di bulan ke berapa
              $counter_saldo_personal_keuangan = $key;//numpang kode counter, untuk menandai mulai isi saldo di bulan ke berapa
              $counter_saldo_personal_properti = $key;//numpang kode counter, untuk menandai mulai isi saldo di bulan ke berapa
              $j = $year+1;
              $k=1;
              
              $kode=($j*100)+$k;
            } else{
              $gaji_hitung = $gaji_input;
              $phdp_hitung = $phdp_input;
              /*
              $saldo_ppip[$i]=$saldo_ppip_input; //numpang untuk mengisi saldo ppip
              $saldo_personal_keuangan[$i]=$saldo_personal_keuangan_input;//numpang untuk mengisi saldo personal keuangan
              $saldo_personal_properti[$i]=$saldo_personal_properti_input;//numpang untuk mengisi saldo personal properti
              */
              $k=$k+1;
              $kode=($year*100)+$k;
            }
          } else {
            if($k==12){
              $gaji_hitung = $previous_gaji*(1+$gaji_naik);
              $phdp_hitung = $previous_phdp*(1+$phdp_naik);
              /*
              $saldo_ppip[$i]=0; //numpang untuk mengisi saldo ppip
              $saldo_personal_keuangan[$i]=0;//numpang untuk mengisi saldo personal keuangan
              $saldo_personal_properti[$i]=0;//numpang untuk mengisi saldo personal properti
              */
              $j=$year+1;
              $k=1;
              $kode=($j*100)+$k;
            } else{
              $gaji_hitung = $previous_gaji;
              $phdp_hitung = $previous_phdp;
              /*
              $saldo_ppip[$i]=0; //numpang untuk mengisi saldo ppip
              $saldo_personal_keuangan[$i]=0;//numpang untuk mengisi saldo personal keuangan
              $saldo_personal_properti[$i]=0;//numpang untuk mengisi saldo personal properti
              */
              $k=$k+1;
              $kode=($year*100)+$k;
            }
          }
          // echo $key."<br/>";
          $gaji[$key] = $gaji_hitung;
          $previous_gaji = $gaji[$key];

          $phdp[$key] = $phdp_hitung;
          $previous_phdp = $phdp[$key];

        }
      }

      return array(
        "gaji" => $gaji,
        "phdp" => $phdp,
        "counter_saldo_ppip" => $counter_saldo_ppip,
        "counter_saldo_personal_properti" => $counter_saldo_personal_properti,
        "counter_saldo_personal_keuangan" => $counter_saldo_personal_keuangan,
      );
    }

    public function simulasi_ppmp($data_user, $id_user, $sisa_masa_dinas_tahun, $sisa_masa_dinas_bulan, $flag_pensiun, $return_simulasi_gaji_phdp){
      //Input: variabel $phdp[$i] yang ada di memory, Read masa dinas tahun dan bulan, dan flag pensiun
      $date1 = date_create($data_user->tgl_diangkat_pegawai); //Read tanggal diangkat
      $date2 = date_create("2015-01-01"); //tanggal cutoff pensiun hybrid. yang diangkat setelah 1 januari 2015 ppip murni, kalau sebelumnya hybrid ppmp dan ppip
      $diff = date_diff($date1,$date2);
      
      $hari = $diff->format('%R%a');

      $gaji = $return_simulasi_gaji_phdp['gaji'];
      $phdp = $return_simulasi_gaji_phdp['phdp'];

      $jumlah_ppmp = array();
      $rr_ppmp = array();
      $status_mp = array();
      for($year=2023; $year<=2100; $year++){
        for($month=1; $month<=12; $month++){
          $key = $year . "_" . $month;
          if ($hari > 0){ //hybrid ppmp ppip
            $status_mp_hitung = 1;//untuk hybrid ppmp ppip
            if ($flag_pensiun[$key]==0){ //belum pensiun
              $masa_dinas_sementara = $sisa_masa_dinas_tahun[$key]+($sisa_masa_dinas_bulan[$key] / 12);
              $masa_dinas = min($masa_dinas_sementara,32); //maksimum masa dinas yang bisa diabsorb oleh ppmp adalah 32 tahun
              $jumlah_ppmp_hitung = 0.025 * $masa_dinas * $phdp[$key]; //rumus besar MP dalam PPMP
              $rr_ppmp_hitung = $jumlah_ppmp_hitung / $gaji[$key]; //rumus mencari replacement ratio dalam ppmp
              //Output: create $jumlah_ppmp[$i] dan $rr_ppmp[$i]
            } else { //sudah pensiun
              $jumlah_ppmp_hitung = "null";
              $rr_ppmp_hitung = "null";
              //Output: create $jumlah_ppmp[$i] dan $rr_ppmp[$i]
            }
          } else { //ppip murni
            $status_mp_hitung = 2;//untuk ppip murni
            $jumlah_ppmp_hitung = "null";
            $rr_ppmp_hitung = "null";		
            //Output: create $jumlah_ppmp[$i] dan $rr_ppmp[$i]
          }

          $jumlah_ppmp[$year] = $jumlah_ppmp_hitung;
          $rr_ppmp[$year] = $rr_ppmp_hitung;
          $status_mp[$year] = $status_mp_hitung;
        }
      }

      return array(
        "jumlah_ppmp"=>$jumlah_ppmp,
        "rr_ppmp"=>$rr_ppmp,
        "status_mp"=>$status_mp,
      );
    }

    public function simulasi_ppip($data_user, $id_user, $return_simulasi_ppmp, $flag_pensiun, $return_simulasi_gaji_phdp, $montecarlo_ppip){
      //Input: variabel $gaji{$i] yang ada di memory serta flag pensiun, status mp yang sudah dihitung sebelumnya, Read tambahan iuran ppip, Read Saldo PPIP, Read pilihan pembayaran PPIP di profil user
      
      $status_mp = $return_simulasi_ppmp['status_mp'];
      
      $gaji = $return_simulasi_gaji_phdp['gaji'];
      $phdp = $return_simulasi_gaji_phdp['phdp'];
      $counter_saldo_ppip = explode("_", $return_simulasi_gaji_phdp['counter_saldo_ppip']);
      $counter_saldo_ppip_year = $counter_saldo_ppip[0]; 
      $counter_saldo_ppip_month = $counter_saldo_ppip[1];
      
      $percentile_95_return_monthly_ppip = $montecarlo_ppip["percentile_95_return_monthly_ppip"];
      $percentile_50_return_monthly_ppip = $montecarlo_ppip["percentile_50_return_monthly_ppip"];
      $percentile_05_return_monthly_ppip = $montecarlo_ppip["percentile_05_return_monthly_ppip"];
      
      $setting_nilai_asumsi_user = DB::table('nilai_asumsi_user')
            ->where('id_user', $id_user)
            ->where('flag', 1)
            ->select('*')->get()[0];

      //F.3.1. Simulasi PPIP - Hitung iuran
      //menentukan besar iuran
      if ($status_mp==1){ //hybrid ppmp ppip
        $persentase_iuran_ppip = 0.09; //iuran ppip sebesar 9% untuk hybrid ppmp ppip
      } else {
        $persentase_iuran_ppip = 0.2; //iuran ppip sebesar 20% untuk ppip murni
      }

      $persentase_tambahan_iuran_ppip=$setting_nilai_asumsi_user->tambahan_iuran;// Read tambahan iuran ppip di profil user
      $saldo_ppip_input=$data_user->saldo_ppip;// Read saldo ppip yang diinput (saldo diasumsikan diinput di awal bulan)

      //nilai default pilihan pembayaran PPIP
      //Input: Read pilihan pembayaran PPIP, Read kupon SBN/SBSN dan beserta pajak dari profil user, Read Harga anuitas dari profil user
      //pembayaran PPIP jika 1=anuitas; 2=kupon SBN/SBSN
      
      $setting_treatment_user = DB::table('setting_treatment_pembayaran_setelah_pensiun')
            ->where('id_user', $id_user)
            ->where('flag', 1)
            ->select('*')->get()[0];
            
      $pembayaran_ppip = ($setting_treatment_user->ppip === 'Beli Anuitas') ? 1 : 2;//Read pilihan pembayaran PPIP (pembayaran PPIP jika 1=anuitas; 2=kupon SBN/SBSN)
      
      if($pembayaran_ppip==1){
        $harga_anuitas_ppip = $setting_treatment_user->harga_anuitas_ppip;//Read harga anuitas masing-masing user
        
        $kupon_sbn_ppip =0.06125;//default
        $pajak_sbn_ppip =0.01;//default
      } else {
        $harga_anuitas_ppip = 136;//default
        
        $kupon_sbn_ppip =$setting_treatment_user->bunga_ppip;//Read kupon SBN/SBSN dari profil user
        $pajak_sbn_ppip =$setting_treatment_user->pajak_ppip;//Read pajak SBN/SBSN dari profil user
      }

      //Output: Create $iuran[$i], $tambahan_iuran_ppip[$i], $percentile_95_return_ppip_bulanan[$i], $percentile_50_return_ppip_bulanan[$i], $percentile_05_return_ppip_bulanan[$i]
      $iuran = array();
      $tambahan_iuran_ppip = array();
      $percentile_95_return_ppip_bulanan = array();
      $percentile_50_return_ppip_bulanan = array();
      $percentile_05_return_ppip_bulanan = array();

      //output: Create $saldo_ppip_awal_p95[$i], $pengembangan_ppip_p95[$i], $saldo_ppip_akhir_p95[$i], $saldo_ppip_awal_p50[$i], $pengembangan_ppip_p50[$i], $saldo_ppip_akhir_p50[$i], $saldo_ppip_awal_p05[$i], $pengembangan_ppip_p05[$i], $saldo_ppip_akhir_p05[$i]
      $saldo_ppip_awal_p95 = array();
      $pengembangan_ppip_p95 = array();
      $saldo_ppip_akhir_p95 = array();
      $saldo_ppip_awal_p50 = array();
      $pengembangan_ppip_p50 = array();
      $saldo_ppip_akhir_p50 = array();
      $saldo_ppip_awal_p05 = array();
      $pengembangan_ppip_p05 = array();
      $saldo_ppip_akhir_p05 = array();

      $previous_saldo_ppip_akhir_p95 = null;
      $previous_saldo_ppip_akhir_p50 = null;
      $previous_saldo_ppip_akhir_p05 = null;

      //Output: Create $anuitas_ppip_p95[$i], $anuitas_ppip_p50[$i], $anuitas_ppip_p05[$i], $kupon_sbn_ppip_p95[$i], $kupon_sbn_ppip_p50[$i], $kupon_sbn_ppip_p05[$i]
      $anuitas_ppip_p95 = array();
      $anuitas_ppip_p50 = array();
      $anuitas_ppip_p05 = array();
      $kupon_sbn_ppip_p95 = array();
      $kupon_sbn_ppip_p50 = array();
      $kupon_sbn_ppip_p05 = array();

      //Output: Create $rr_ppip_anuitas_p95[$i], $rr_ppip_anuitas_p50[$i], $rr_ppip_anuitas_p05[$i], $rr_ppip_kupon_sbn_p95[$i], $rr_ppip_kupon_sbn_p50[$i], $rr_ppip_kupon_sbn_p05[$i]
      $rr_ppip_anuitas_p95 = array();
      $rr_ppip_anuitas_p50 = array();
      $rr_ppip_anuitas_p05 = array();
      $rr_ppip_kupon_sbn_p95 = array();
      $rr_ppip_kupon_sbn_p50 = array();
      $rr_ppip_kupon_sbn_p05 = array();

      $j=1; //counter hasil investasi percentile monthly (konversi dari tahunan ke bulanan)
      for($year=2023; $year<=2100; $year++){
        for($month=1; $month<=12; $month++){
          $key = $year . "_" . $month;
          $iuran_hitung = $gaji[$key] * $persentase_iuran_ppip; //hitung besar iuran

          // //+++++++++++++++++++++++++++++++++++++
          // //F.3.2., F.3.3., dan F.3.4. Simulasi PPIP - tentukan hasil investasi percentile 95, 50, dan 05
          $percentile_95_return_ppip_bulanan_hitung = $percentile_95_return_monthly_ppip[$year]; //menentukan percentile secara bulanan dari yang sebelumnya tahunan di monte carlo PPIP
          $percentile_50_return_ppip_bulanan_hitung = $percentile_50_return_monthly_ppip[$year]; //menentukan percentile secara bulanan dari yang sebelumnya tahunan di monte carlo PPIP
          $percentile_05_return_ppip_bulanan_hitung = $percentile_05_return_monthly_ppip[$year]; //menentukan percentile secara bulanan dari yang sebelumnya tahunan di monte carlo PPIP
          
          // Tidak Dipakai Karena Menggunakan Looping Year Month
          // if (fmod($i,12)==0){ //jika sudah bulan desember maka selanjutnya tahunnya bergeser
          //   $j = $j+1;
          // }
          
          //+++++++++++++++++++++++++++++++++++++
          //F.3.5. Simulasi PPIP - tambahan iuran mandiri ppip
          $tambahan_iuran_ppip_hitung = $persentase_tambahan_iuran_ppip * $gaji[$key];
          
          
          //+++++++++++++++++++++++++++++++++++++
          //F.3.6., F.3.7., F.3.8., F.3.9., F.3.10., F.3.11., F.3.12., F.3.13., dan F.3.14. Simulasi PPIP - hitung percentile 95,50,05 untuk saldo awal, hasil pengembangan, dan saldo akhir
          if($year==$counter_saldo_ppip_year && $month==$counter_saldo_ppip_month){ //tahun pertama ada saldonya
            // //percentile 95
            $saldo_ppip_awal_p95_hitung = $saldo_ppip_input;
            $pengembangan_ppip_p95_hitung= ($saldo_ppip_awal_p95_hitung + $tambahan_iuran_ppip_hitung + $iuran_hitung ) * $percentile_95_return_ppip_bulanan_hitung;
            $saldo_ppip_akhir_p95_hitung = $saldo_ppip_awal_p95_hitung + $tambahan_iuran_ppip_hitung + $iuran_hitung + $pengembangan_ppip_p95_hitung; //saldo merupakan saldo akhir bulan
            
            // //percentile 50
            $saldo_ppip_awal_p50_hitung = $saldo_ppip_input;
            $pengembangan_ppip_p50_hitung= ($saldo_ppip_awal_p50_hitung + $tambahan_iuran_ppip_hitung + $iuran_hitung )* $percentile_50_return_ppip_bulanan_hitung;
            $saldo_ppip_akhir_p50_hitung = $saldo_ppip_awal_p50_hitung + $tambahan_iuran_ppip_hitung + $iuran_hitung + $pengembangan_ppip_p50_hitung; //saldo merupakan saldo akhir bulan
            
            // //percentile 05
            $saldo_ppip_awal_p05_hitung = $saldo_ppip_input;
            $pengembangan_ppip_p05_hitung= ($saldo_ppip_awal_p05_hitung + $tambahan_iuran_ppip_hitung + $iuran_hitung )* $percentile_05_return_ppip_bulanan_hitung;
            $saldo_ppip_akhir_p05_hitung = $saldo_ppip_awal_p05_hitung + $tambahan_iuran_ppip_hitung + $iuran_hitung + $pengembangan_ppip_p05_hitung; //saldo merupakan saldo akhir bulan
            
          } else if ($year>$counter_saldo_ppip_year || $month>$counter_saldo_ppip_month) {
            //percentile 95
            $saldo_ppip_awal_p95_hitung = $previous_saldo_ppip_akhir_p95;
            $pengembangan_ppip_p95_hitung= ($saldo_ppip_awal_p95_hitung + $tambahan_iuran_ppip_hitung + $iuran_hitung )* $percentile_95_return_ppip_bulanan_hitung;
            $saldo_ppip_akhir_p95_hitung = $saldo_ppip_awal_p95_hitung + $tambahan_iuran_ppip_hitung + $iuran_hitung + $pengembangan_ppip_p95_hitung; //saldo merupakan saldo akhir bulan
            
            // //percentile 50
            $saldo_ppip_awal_p50_hitung = $previous_saldo_ppip_akhir_p50;
            $pengembangan_ppip_p50_hitung= ($saldo_ppip_awal_p50_hitung + $tambahan_iuran_ppip_hitung + $iuran_hitung )* $percentile_50_return_ppip_bulanan_hitung;
            $saldo_ppip_akhir_p50_hitung = $saldo_ppip_awal_p50_hitung + $tambahan_iuran_ppip_hitung + $iuran_hitung + $pengembangan_ppip_p50_hitung; //saldo merupakan saldo akhir bulan
            
            // //percentile 05
            $saldo_ppip_awal_p05_hitung = $previous_saldo_ppip_akhir_p05;
            $pengembangan_ppip_p05_hitung= ($saldo_ppip_awal_p05_hitung + $tambahan_iuran_ppip_hitung + $iuran_hitung )* $percentile_05_return_ppip_bulanan_hitung;
            $saldo_ppip_akhir_p05_hitung = $saldo_ppip_awal_p05_hitung + $tambahan_iuran_ppip_hitung + $iuran_hitung + $pengembangan_ppip_p05_hitung; //saldo merupakan saldo akhir bulan
            
          } else{
            // //percentile 95
            $saldo_ppip_awal_p95_hitung = 0;
            $pengembangan_ppip_p95_hitung= 0;
            $saldo_ppip_akhir_p95_hitung = 0;
            
            // //percentile 50
            $saldo_ppip_awal_p50_hitung = 0;
            $pengembangan_ppip_p50_hitung= 0;
            $saldo_ppip_akhir_p50_hitung = 0;
            
            // //percentile 05
            $saldo_ppip_awal_p05_hitung = 0;
            $pengembangan_ppip_p05_hitung= 0;
            $saldo_ppip_akhir_p05_hitung = 0;
            
          }
          
          //++++++++++++++++++++++++++++++++++++++++
          //F.3.15., F.3.16., dan F.3.17. Simulasi PPIP - Hitung anuitas bulanan untuk percentile 95, 50, dan 05 (hitung MP Bulanan bila dihitung menggunakan anuitas seumur hidup)
          $anuitas_ppip_p95_hitung = $saldo_ppip_akhir_p95_hitung / $harga_anuitas_ppip;
          $anuitas_ppip_p50_hitung = $saldo_ppip_akhir_p50_hitung / $harga_anuitas_ppip;
          $anuitas_ppip_p05_hitung = $saldo_ppip_akhir_p05_hitung / $harga_anuitas_ppip;
          
          //++++++++++++++++++++++++++++++++++++++++
          //F.3.18., F.3.19., dan F.3.20. Simulasi PPIP - Hitung kupon SBN/SBSN bulanan untuk percentile 95, 50, dan 05 (hitung MP Bulanan bila dihitung menggunakan kupon SBN/SBSN)
          $kupon_sbn_ppip_p95_hitung = ( $saldo_ppip_akhir_p95_hitung * $kupon_sbn_ppip *(1-$pajak_sbn_ppip))/12; //pembayaran bulanan dari kupon SBN/SBSN percentile 95
          $kupon_sbn_ppip_p50_hitung = ( $saldo_ppip_akhir_p50_hitung * $kupon_sbn_ppip *(1-$pajak_sbn_ppip))/12; //pembayaran bulanan dari kupon SBN/SBSN percentile 50
          $kupon_sbn_ppip_p05_hitung = ( $saldo_ppip_akhir_p05_hitung * $kupon_sbn_ppip *(1-$pajak_sbn_ppip))/12; //pembayaran bulanan dari kupon SBN/SBSN percentile 05
          
          //++++++++++++++++++++++++++++++++++++++++
          //F.3.21., F.3.22., F.3.23., F.3.24., F.3.25., dan F.3.26., Hitung RR untuk anuitas dan kupon SBN/SBSN pada percentile 95, 50, dan 05
          if ($gaji[$key]>0){
            //untuk anuitas
            $rr_ppip_anuitas_p95_hitung = $anuitas_ppip_p95_hitung / $gaji[$key];
            $rr_ppip_anuitas_p50_hitung = $anuitas_ppip_p50_hitung / $gaji[$key];
            $rr_ppip_anuitas_p05_hitung = $anuitas_ppip_p05_hitung / $gaji[$key];
            
            //untuk kupon SBN/SBSN
            $rr_ppip_kupon_sbn_p95_hitung = $kupon_sbn_ppip_p95_hitung / $gaji[$key];
            $rr_ppip_kupon_sbn_p50_hitung = $kupon_sbn_ppip_p50_hitung / $gaji[$key];
            $rr_ppip_kupon_sbn_p05_hitung = $kupon_sbn_ppip_p05_hitung / $gaji[$key];
            
          } else{
            //untuk anuitas
            $rr_ppip_anuitas_p95_hitung = 0;
            $rr_ppip_anuitas_p50_hitung = 0;
            $rr_ppip_anuitas_p05_hitung = 0;
            
            //untuk kupon SBN/SBSN
            $rr_ppip_kupon_sbn_p95_hitung = 0;
            $rr_ppip_kupon_sbn_p50_hitung = 0;
            $rr_ppip_kupon_sbn_p05_hitung = 0;
          }

          //Output: Create $iuran[$i], $tambahan_iuran_ppip[$i], $percentile_95_return_ppip_bulanan[$i], $percentile_50_return_ppip_bulanan[$i], $percentile_05_return_ppip_bulanan[$i]
          $iuran[$key] = $iuran_hitung;
          $tambahan_iuran_ppip[$key] = $tambahan_iuran_ppip_hitung;
          $percentile_95_return_ppip_bulanan[$key] = $percentile_95_return_ppip_bulanan_hitung;
          $percentile_50_return_ppip_bulanan[$key] = $percentile_50_return_ppip_bulanan_hitung;
          $percentile_05_return_ppip_bulanan[$key] = $percentile_05_return_ppip_bulanan_hitung;

          //output: Create $saldo_ppip_awal_p95[$i], $pengembangan_ppip_p95[$i], $saldo_ppip_akhir_p95[$i], $saldo_ppip_awal_p50[$i], $pengembangan_ppip_p50[$i], $saldo_ppip_akhir_p50[$i], $saldo_ppip_awal_p05[$i], $pengembangan_ppip_p05[$i], $saldo_ppip_akhir_p05[$i]
          $saldo_ppip_awal_p95[$key] = $saldo_ppip_awal_p95_hitung;
          $pengembangan_ppip_p95[$key] = $pengembangan_ppip_p95_hitung;
          $saldo_ppip_akhir_p95[$key] = $saldo_ppip_akhir_p95_hitung;
          $saldo_ppip_awal_p50[$key] = $saldo_ppip_awal_p50_hitung;
          $pengembangan_ppip_p50[$key] = $pengembangan_ppip_p50_hitung;
          $saldo_ppip_akhir_p50[$key] = $saldo_ppip_akhir_p50_hitung;
          $saldo_ppip_awal_p05[$key] = $saldo_ppip_awal_p05_hitung;
          $pengembangan_ppip_p05[$key] = $pengembangan_ppip_p05_hitung;
          $saldo_ppip_akhir_p05[$key] = $saldo_ppip_akhir_p05_hitung;

          $previous_saldo_ppip_akhir_p95 = $saldo_ppip_akhir_p95[$key];
          $previous_saldo_ppip_akhir_p50 = $saldo_ppip_akhir_p50[$key];
          $previous_saldo_ppip_akhir_p05 = $saldo_ppip_akhir_p05[$key];

          //Output: Create $anuitas_ppip_p95[$i], $anuitas_ppip_p50[$i], $anuitas_ppip_p05[$i], $kupon_sbn_ppip_p95[$i], $kupon_sbn_ppip_p50[$i], $kupon_sbn_ppip_p05[$i]
          $anuitas_ppip_p95 = $anuitas_ppip_p95_hitung;
          $anuitas_ppip_p50 = $anuitas_ppip_p50_hitung;
          $anuitas_ppip_p05 = $anuitas_ppip_p05_hitung;
          $kupon_sbn_ppip_p95 = $kupon_sbn_ppip_p95_hitung;
          $kupon_sbn_ppip_p50 = $kupon_sbn_ppip_p50_hitung;
          $kupon_sbn_ppip_p05 = $kupon_sbn_ppip_p05_hitung;

          //Output: Create $rr_ppip_anuitas_p95[$i], $rr_ppip_anuitas_p50[$i], $rr_ppip_anuitas_p05[$i], $rr_ppip_kupon_sbn_p95[$i], $rr_ppip_kupon_sbn_p50[$i], $rr_ppip_kupon_sbn_p05[$i]
          $rr_ppip_anuitas_p95 = $rr_ppip_anuitas_p95_hitung;
          $rr_ppip_anuitas_p50 = $rr_ppip_anuitas_p50_hitung;
          $rr_ppip_anuitas_p05 = $rr_ppip_anuitas_p05_hitung;
          $rr_ppip_kupon_sbn_p95 = $rr_ppip_kupon_sbn_p95_hitung;
          $rr_ppip_kupon_sbn_p50 = $rr_ppip_kupon_sbn_p50_hitung;
          $rr_ppip_kupon_sbn_p05 = $rr_ppip_kupon_sbn_p05_hitung;
        }
      }
    }

    public function simulasi_personal_properti($data_user, $return_simulasi_gaji_phdp){
      //F.4.1. dan F.4.2. Simulasi Properti - Hitung harga dan sewa properti
      //Input: Read harga properti, sewa tahunan, kenaikan harga properti, dan kenaikan harga sewa di profil user
      $saldo_personal_properti_input=$data_user->jumlah_investasi_properti;// Read harga properti keuangan yang diinput di profil user
      $sewa_personal_properti_input=$data_user->sewa_properti;// Read harga properti keuangan yang diinput di profil user

      $naik_harga_properti=$data_user->kenaikan_properti; // Read kenaikan harga properti keuangan yang diinput di profil user
      $naik_sewa_properti=$data_user->kenaikan_sewa; // Read kenaikan sewa properti keuangan yang diinput di profil user

      $gaji = $return_simulasi_gaji_phdp['gaji'];
      $counter_saldo_personal_properti = explode("_", $return_simulasi_gaji_phdp['counter_saldo_personal_properti']);
      $counter_saldo_personal_properti_year = $counter_saldo_personal_properti[0]; 
      $counter_saldo_personal_properti_month = $counter_saldo_personal_properti[1];

      $harga_properti = array();
      $sewa_properti = array();
      $rr_personal_properti = array();

      $previous_harga_properti = null;
      $previous_sewa_properti = null;

      $jml=936; // jumlah bulan dari januari 2023 s.d. desember 2100

      for($year=2023; $year<=2100; $year++){
        for($month=1; $month<=12; $month++){
          $key = $year . "_" . $month;
          if($year==$counter_saldo_personal_properti_year && $month==$counter_saldo_personal_properti_month){
            $harga_properti_hitung = $saldo_personal_properti_input;
            $sewa_properti_hitung = $sewa_personal_properti_input;
          } else if ($year>$counter_saldo_personal_properti_year || $month>$counter_saldo_personal_properti_month) {
            if ($month==1){ //jika sudah bulan januari maka harga rumah dan sewa naik
              $harga_properti_hitung = $previous_harga_properti * (1+$naik_harga_properti);
              $sewa_properti_hitung = $previous_sewa_properti * (1+$naik_sewa_properti);
            } else {
              $harga_properti_hitung = $previous_harga_properti;
              $sewa_properti_hitung = $previous_sewa_properti;
            }
          } else {
            $harga_properti_hitung = 0;
            $sewa_properti_hitung = 0;
          }
          
          //+++++++++++++++++++++++++++++++++++
          //F.4.3. Simulasi Properti - Hitung RR Properti
          if ($gaji[$key]>0){
            if ($sewa_properti_hitung != 0) {
              $rr_personal_properti_hitung = ($sewa_properti_hitung / 12) / $gaji[$key];
            } else {
              $rr_personal_properti_hitung = 0;
            }
          } else {
            $rr_personal_properti_hitung = 0;
          }

          // Output
          $harga_properti[$key] = $harga_properti_hitung;
          $sewa_properti[$key] = $sewa_properti_hitung;
          $rr_personal_properti[$key] = $rr_personal_properti_hitung;
        }
      }
      return array(
        "harga_properti" => $harga_properti,
        "sewa_properti" => $sewa_properti,
        "rr_personal_properti" => $rr_personal_properti,
      );
    }

    public function simulasi_personal_keuangan($data_user, $id_user, $return_simulasi_gaji_phdp, $flag_pensiun){
      //Input: variabel $gaji{$i] yang ada di memory serta flag pensiun, Read tambahan iuran personal_keuangan, Read Saldo PERSONAL_KEUANGAN

      //F.5.1. Simulasi PERSONAL_KEUANGAN - Hitung iuran
      $persentase_iuran_personal_keuangan=0.05; //Read besar iuran personal keuangan di profil user
      $saldo_personal_keuangan_input=0; // Read saldo personal_keuangan yang diinput (saldo diasumsikan diinput di awal bulan)

      //nilai default pilihan pembayaran personal keuangan
      //Input: Read pilihan pembayaran personal keuangan, Read kupon SBN/SBSN dan beserta pajak dari profil user, Read Harga anuitas dari profil user
      //pembayaran personal_keuangan jika 1=anuitas; 2=kupon SBN/SBSN

      $setting_treatment_user = DB::table('setting_treatment_pembayaran_setelah_pensiun')
            ->where('id_user', $id_user)
            ->where('flag', 1)
            ->select('*')->get()[0];
      echo json_encode($setting_treatment_user, true);
      die();

      $pembayaran_personal_keuangan=($setting_treatment_user->ppip === 'Beli Anuitas') ? 1 : 2;//Read pilihan pembayaran personal_keuangan (pembayaran personal_keuangan jika 1=anuitas; 2=kupon SBN/SBSN)
      if($pembayaran_personal_keuangan==1){
        $harga_anuitas_personal_keuangan =136;//Read harga anuitas masing-masing user
        
        $kupon_sbn_personal_keuangan =0.06125;//default
        $pajak_sbn_personal_keuangan =0.01;//default
      } else {
        $harga_anuitas_personal_keuangan =136;//default
        
        $kupon_sbn_personal_keuangan =0.06125;//Read kupon SBN/SBSN dari profil user
        $pajak_sbn_personal_keuangan =0.01;//Read pajak SBN/SBSN dari profil user
      }

      $j=1; //counter hasil investasi percentile monthly (konversi dari tahunan ke bulanan)
      for($year=2023; $year<=2100; $year++){
        for($month=1; $month<=12; $month++){
          $key = $year . "_" . $month;

        }
      }
      // for ($i=1;$i<=$jml;$i++){
        
      //   $iuran_personal_keuangan[$i]=$gaji[$i]*$persentase_iuran_personal_keuangan; //hitung besar iuran
        
      //   //+++++++++++++++++++++++++++++++++++++
      //   //F.5.2., F.5.3., dan F.5.4. Simulasi PERSONAL_KEUANGAN - tentukan hasil investasi percentile 95, 50, dan 05
      //   $percentile_95_return_personal_bulanan[$i]=$percentile_95_return_monthly_personal[$j]; //menentukan percentile secara bulanan dari yang sebelumnya tahunan di monte carlo PERSONAL_KEUANGAN
      //   $percentile_50_return_personal_bulanan[$i]=$percentile_50_return_monthly_personal[$j]; //menentukan percentile secara bulanan dari yang sebelumnya tahunan di monte carlo PERSONAL_KEUANGAN
      //   $percentile_05_return_personal_bulanan[$i]=$percentile_05_return_monthly_personal[$j]; //menentukan percentile secara bulanan dari yang sebelumnya tahunan di monte carlo PERSONAL_KEUANGAN
        
      //   if (fmod($i,12)==0){ //jika sudah bulan desember maka selanjutnya tahunnya bergeser
      //     $j=$j+1;
      //   }
        
          
      //   //+++++++++++++++++++++++++++++++++++++
      //   //F.5.5., F.5.6., F.5.7., F.5.8., F.5.9., F.5.10., F.5.11., F.5.12., dan F.5.13. Simulasi PERSONAL_KEUANGAN - hitung percentile 95,50,05 untuk saldo awal, hasil pengembangan, dan saldo akhir
      //   if($i==$counter_saldo_personal_keuangan){ //tahun pertama ada saldonya
          
      //     //percentile 95
      //     $saldo_personal_keuangan_awal_p95[$i]=$saldo_personal_keuangan_input;
      //     $pengembangan_personal_keuangan_p95[$i]= ($saldo_personal_keuangan_awal_p95[$i] + $iuran_personal_keuangan[$i] )* $percentile_95_return_personal_keuangan_bulanan[$i];
      //     $saldo_personal_keuangan_akhir_p95[$i] = $saldo_personal_keuangan_awal_p95[$i] + $iuran_personal_keuangan[$i] + $pengembangan_personal_keuangan_p95[$i]; //saldo merupakan saldo akhir bulan
          
      //     //percentile 50
      //     $saldo_personal_keuangan_awal_p50[$i]=$saldo_personal_keuangan_input;
      //     $pengembangan_personal_keuangan_p50[$i]= ($saldo_personal_keuangan_awal_p50[$i] + $iuran_personal_keuangan[$i] )* $percentile_50_return_personal_keuangan_bulanan[$i];
      //     $saldo_personal_keuangan_akhir_p50[$i] = $saldo_personal_keuangan_awal_p50[$i] + $iuran_personal_keuangan[$i] + $pengembangan_personal_keuangan_p50[$i]; //saldo merupakan saldo akhir bulan
          
      //     //percentile 05
      //     $saldo_personal_keuangan_awal_p05[$i]=$saldo_personal_keuangan_input;
      //     $pengembangan_personal_keuangan_p05[$i]= ($saldo_personal_keuangan_awal_p05[$i] + $iuran_personal_keuangan[$i] )* $percentile_05_return_personal_keuangan_bulanan[$i];
      //     $saldo_personal_keuangan_akhir_p05[$i] = $saldo_personal_keuangan_awal_p05[$i] + $iuran_personal_keuangan[$i] + $pengembangan_personal_keuangan_p05[$i]; //saldo merupakan saldo akhir bulan
          
      //   } else if ($i>$counter_saldo_personal_keuangan) {
      //     //percentile 95
      //     $saldo_personal_keuangan_awal_p95[$i]=$saldo_personal_keuangan_akhir_p95[$i-1];
      //     $pengembangan_personal_keuangan_p95[$i]= ($saldo_personal_keuangan_awal_p95[$i] + $iuran_personal_keuangan[$i] )* $percentile_95_return_personal_keuangan_bulanan[$i];
      //     $saldo_personal_keuangan_akhir_p95[$i] = $saldo_personal_keuangan_awal_p95[$i] + $iuran_personal_keuangan[$i] + $pengembangan_personal_keuangan_p95[$i]; //saldo merupakan saldo akhir bulan
          
      //     //percentile 50
      //     $saldo_personal_keuangan_awal_p50[$i]=$saldo_personal_keuangan_akhir_p50[$i-1];
      //     $pengembangan_personal_keuangan_p50[$i]= ($saldo_personal_keuangan_awal_p50[$i] + $iuran_personal_keuangan[$i] )* $percentile_50_return_personal_keuangan_bulanan[$i];
      //     $saldo_personal_keuangan_akhir_p50[$i] = $saldo_personal_keuangan_awal_p50[$i] + $iuran_personal_keuangan[$i] + $pengembangan_personal_keuangan_p50[$i]; //saldo merupakan saldo akhir bulan
          
      //     //percentile 05
      //     $saldo_personal_keuangan_awal_p05[$i]=$saldo_personal_keuangan_akhir_p05[$i-1];
      //     $pengembangan_personal_keuangan_p05[$i]= ($saldo_personal_keuangan_awal_p05[$i] + $iuran_personal_keuangan[$i] )* $percentile_05_return_personal_keuangan_bulanan[$i];
      //     $saldo_personal_keuangan_akhir_p05[$i] = $saldo_personal_keuangan_awal_p05[$i] + $iuran_personal_keuangan[$i] + $pengembangan_personal_keuangan_p05[$i]; //saldo merupakan saldo akhir bulan
          
      //   } else{
      //     //percentile 95
      //     $saldo_personal_keuangan_awal_p95[$i]=0;
      //     $pengembangan_personal_keuangan_p95[$i]= 0;
      //     $saldo_personal_keuangan_akhir_p95[$i] = 0;
          
      //     //percentile 50
      //     $saldo_personal_keuangan_awal_p50[$i]=0;
      //     $pengembangan_personal_keuangan_p50[$i]= 0;
      //     $saldo_personal_keuangan_akhir_p50[$i] = 0;
          
      //     //percentile 05
      //     $saldo_personal_keuangan_awal_p05[$i]=0;
      //     $pengembangan_personal_keuangan_p05[$i]= 0;
      //     $saldo_personal_keuangan_akhir_p05[$i] = 0;
          
      //   }
        
      //   //++++++++++++++++++++++++++++++++++++++++
      //   //F.5.14., F.5.15., dan F.5.16. Simulasi PERSONAL_KEUANGAN - Hitung anuitas bulanan untuk percentile 95, 50, dan 05 (hitung MP Bulanan bila dihitung menggunakan anuitas seumur hidup)
          
      //   $anuitas_personal_keuangan_p95[$i]=$saldo_personal_keuangan_akhir_p95[$i] / $harga_anuitas_personal_keuangan;
      //   $anuitas_personal_keuangan_p50[$i]=$saldo_personal_keuangan_akhir_p50[$i] / $harga_anuitas_personal_keuangan;
      //   $anuitas_personal_keuangan_p05[$i]=$saldo_personal_keuangan_akhir_p05[$i] / $harga_anuitas_personal_keuangan;
        
        
      //   //++++++++++++++++++++++++++++++++++++++++
      //   //F.5.17., F.5.18., dan F.5.19. Simulasi PERSONAL_KEUANGAN - Hitung kupon SBN/SBSN bulanan untuk percentile 95, 50, dan 05 (hitung MP Bulanan bila dihitung menggunakan kupon SBN/SBSN)
          
      //   $kupon_sbn_personal_keuangan_p95[$i]=( $saldo_personal_keuangan_akhir_p95[$i] * $kupon_sbn_personal_keuangan *(1-$pajak_sbn_personal_keuangan))/12; //pembayaran bulanan dari kupon SBN/SBSN percentile 95
      //   $kupon_sbn_personal_keuangan_p50[$i]=( $saldo_personal_keuangan_akhir_p50[$i] * $kupon_sbn_personal_keuangan *(1-$pajak_sbn_personal_keuangan))/12; //pembayaran bulanan dari kupon SBN/SBSN percentile 50
      //   $kupon_sbn_personal_keuangan_p05[$i]=( $saldo_personal_keuangan_akhir_p05[$i] * $kupon_sbn_personal_keuangan *(1-$pajak_sbn_personal_keuangan))/12; //pembayaran bulanan dari kupon SBN/SBSN percentile 05
        
      //   //++++++++++++++++++++++++++++++++++++++++
      //   //F.5.20., F.5.21., F.5.22., F.5.23., F.5.24., dan F.5.25., Hitung RR untuk anuitas dan kupon SBN/SBSN pada percentile 95, 50, dan 05
        
      //   if ($gaji[$i]>0){
          
      //     //untuk anuitas
      //     $rr_personal_keuangan_anuitas_p95[$i] = $anuitas_personal_keuangan_p95[$i] / $gaji[$i];
      //     $rr_personal_keuangan_anuitas_p50[$i] = $anuitas_personal_keuangan_p50[$i] / $gaji[$i];
      //     $rr_personal_keuangan_anuitas_p05[$i] = $anuitas_personal_keuangan_p05[$i] / $gaji[$i];
          
      //     //untuk kupon SBN/SBSN
      //     $rr_personal_keuangan_kupon_sbn_p95[$i] = $kupon_sbn_personal_keuangan_p95[$i] / $gaji[$i];
      //     $rr_personal_keuangan_kupon_sbn_p50[$i] = $kupon_sbn_personal_keuangan_p50[$i] / $gaji[$i];
      //     $rr_personal_keuangan_kupon_sbn_p05[$i] = $kupon_sbn_personal_keuangan_p05[$i] / $gaji[$i];
          
      //   } else{
      //     //untuk anuitas
      //     $rr_personal_keuangan_anuitas_p95[$i] = 0;
      //     $rr_personal_keuangan_anuitas_p50[$i] = 0;
      //     $rr_personal_keuangan_anuitas_p05[$i] = 0;
          
      //     //untuk kupon SBN/SBSN
      //     $rr_personal_keuangan_kupon_sbn_p95[$i] = 0;
      //     $rr_personal_keuangan_kupon_sbn_p50[$i] = 0;
      //     $rr_personal_keuangan_kupon_sbn_p05[$i] = 0;
      //   }
          
      //   //Output: Create $iuran_personal_keuangan[$i], $percentile_95_return_personal_keuangan_bulanan[$i], $percentile_50_return_personal_keuangan_bulanan[$i], $percentile_05_return_personal_keuangan_bulanan[$i]
      //   //output: Create $saldo_personal_keuangan_awal_p95[$i], $pengembangan_personal_keuangan_p95[$i], $saldo_personal_keuangan_akhir_p95[$i], $saldo_personal_keuangan_awal_p50[$i], $pengembangan_personal_keuangan_p50[$i], $saldo_personal_keuangan_akhir_p50[$i], $saldo_personal_keuangan_awal_p05[$i], $pengembangan_personal_keuangan_p05[$i], $saldo_personal_keuangan_akhir_p05[$i]
      //   //Output: Create $anuitas_personal_keuangan_p95[$i], $anuitas_personal_keuangan_p50[$i], $anuitas_personal_keuangan_p05[$i], $kupon_sbn_personal_keuangan_p95[$i], $kupon_sbn_personal_keuangan_p50[$i], $kupon_sbn_personal_keuangan_p05[$i]
      //   //Output: Create $rr_personal_keuangan_anuitas_p95[$i], $rr_personal_keuangan_anuitas_p50[$i], $rr_personal_keuangan_anuitas_p05[$i], $rr_personal_keuangan_kupon_sbn_p95[$i], $rr_personal_keuangan_kupon_sbn_p50[$i], $rr_personal_keuangan_kupon_sbn_p05[$i]
        
      // }

    }

    // Section Development - Yogi
    public function index_yogi(Request $request){
      $id_user = $request->input('id_user');

      // Get Input Form Data
      $tgl_update_gaji_phdp = $request->tgl_update_gaji_phdp;
      $gaji = $request->gaji;
      $phdp = $request->phdp;

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
      $date1=date_create($data_user->tgl_diangkat_pegawai); //Read tanggal diangkat
      $date2=date_create("2023-01-31"); //januari 2023
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

      //Output: Create $tahun dan $bulan ke masing-masing tahun dan bulan di database usia 
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
      
      // Tabel Norm Inverse
      $tabel_norminv = DB::table('distribusi_normal')->select('norm_inv')
        ->get()->toArray();
      for ($i=1;$i<count($tabel_norminv);$i++){ //$i adalah primary key dari tabel normal inverse yang ada di database
          $norminv[$i]=$tabel_norminv[$i]->norm_inv;//Read tabel normal inverse
      }
      
      // -----------------------------------------------------------------------
      //D. Hitung Montecarlo PPIP
      $montecarlo_ppip = $this->montecarlo_ppip($id_user, $sisa_kerja_tahun, $flag_pensiun, $norminv);

      // -----------------------------------------------------------------------
      //E. Hitung Montecarlo Personal Keuangan
      $this->montecarlo_personal($id_user, $sisa_kerja_tahun, $flag_pensiun, $norminv);
      
      //---------------------------------------------------------
      //F. Perhitungan Simulasi
      //F.1. Simulasi Gaji dan PhDP
      $return_simulasi_gaji_phdp = $this->simulasi_gaji_phdp($tgl_update_gaji_phdp, $gaji, $phdp, $id_user);
      //F.2. Simulasi PPMP
      $return_simulasi_ppmp = $this->simulasi_ppmp($data_user, $id_user, $sisa_masa_dinas_tahun, $sisa_masa_dinas_bulan, $flag_pensiun, $return_simulasi_gaji_phdp);
      //F.3. Simulasi PPIP
      $return_simulasi_ppip = $this->simulasi_ppip($data_user, $id_user, $return_simulasi_ppmp, $flag_pensiun, $return_simulasi_gaji_phdp, $montecarlo_ppip);
      //F.4. Simulasi Personal Properti
      $return_simulasi_personal_properti = $this->simulasi_personal_properti($data_user, $return_simulasi_gaji_phdp, $return_simulasi_gaji_phdp);
      //F.5. Simulasi PERSONAL_KEUANGAN
      $return_simulasi_personal_keuangan = $this->simulasi_personal_keuangan($data_user, $id_user, $return_simulasi_gaji_phdp, $flag_pensiun);

      return response()->json([
        "status" =>true,
        "message"=>"Testing Hitung Awal!",
      ],200);
    }
}

