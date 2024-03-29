<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use DateTime;
use DB;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index(Request $request){
      $id_user = $request->input('id_user');
      
      $dashboard = DB::table('dashboard')
      ->where([
          ['id_user','=',$id_user],
          ['flag','=',1]
      ])->get()->toArray();
      
      return response()->json([
        "status" =>true,
        "data"=> $dashboard,
      ],200);
    }

    public function generate_data(Request $request){
      $id_user = $request->input('id_user');

      // Validasi kelengkapan data
      // Validasi Kuisioner
      $validasi_kuisioner = DB::table('variabel_kuisioner_target_rr_answer')
      ->select("answer")
      ->where([
          ['id_user','=',$id_user],
          ['flag','=',1],
          ['kode_kuisioner','=',"TARGET_RR"],
      ])
      ->get();
      if (count($validasi_kuisioner) === 0) {
        return response()->json([
          "status" =>false,
          "message"=>"Kuisioner Kosong",
        ],200);
      }

      // Validasi Setting Portofolio PPIP
      $validasi_setting_ppip = DB::table('setting_portofolio_ppip')->select('*')
      ->where('id_user', $id_user)
      ->where('flag', 1)
      ->get();
      if (count($validasi_setting_ppip) === 0) {
        return response()->json([
          "status" =>false,
          "message"=>"Setting PPIP User Kosong",
        ],200);
      }

      // Validasi Setting Portofolio Personal
      $validasi_setting_personal_user = DB::table('setting_portofolio_personal')->select('*')
      ->where('id_user', $id_user)
      ->where('flag', 1)
      ->get();
      if (count($validasi_setting_personal_user) === 0) {
        return response()->json([
          "status" =>false,
          "message"=>"Setting Personal User Kosong",
        ],200);
      }

      // Validasi Setting Lifecycle
      $validasi_setting_lifecycle_user = DB::table('setting_komposisi_investasi_lifecycle_fund')->select('*')
      ->where('id_user', $id_user)
      ->where('flag', 1)
      ->get();
      if (count($validasi_setting_lifecycle_user) === 0) {
        return response()->json([
          "status" =>false,
          "message"=>"Setting Lifecycle User Kosong",
        ],200);
      }

      // Validasi Setting Nilai Asumsi User
      $validasi_setting_nilai_asumsi_user = DB::table('nilai_asumsi_user')
      ->where('id_user', $id_user)
      ->where('flag', 1)
      ->select('*')->get();
      if (count($validasi_setting_nilai_asumsi_user) === 0) {
        return response()->json([
          "status" =>false,
          "message"=>"Setting Nilai Asumsi User Kosong",
        ],200);
      }
      // Validasi Setting Treatment Pembayaran
      $validasi_setting_treatment_user = DB::table('setting_treatment_pembayaran_setelah_pensiun')
            ->where('id_user', $id_user)
            ->where('flag', 1)
            ->select('*')->get();
      if (count($validasi_setting_treatment_user) === 0) {
        return response()->json([
          "status" =>false,
          "message"=>"Setting Treament Pembayaran User Kosong",
        ],200);
      }


      DB::table('activity_dashboard')->insert([
          'id' => (string) Str::uuid(),
          'id_user' => $request->id_user,
          'browser' => $request->browser,
          'sistem_operasi' => $request->sistem_operasi,
          'ip_address' => $request->ip_address,
      ]);

      // Get Input Form Data
      $tgl_update_gaji_phdp = $request->tgl_update_gaji_phdp;
      $gaji = $request->gaji;
      $phdp = $request->phdp;
      
      //untuk validasi perhitungan, karena data gaji dan phdp tidak disimpan dalam database, maka perlu ditembak data gaji dan phdp disini saat cek perhitungan menggunakan postman.
      //hasil tembak gaji, akan dihilangkan ketika data dashboard frontend sudah nyambung dengan backend
       //$gaji = 47700000;
       //$phdp = 19000000;  
      
      //echo json_encode($request, true);
      //die();

      //A.1 Hitung Target Replacement Ratio
      $res = DB::table('variabel_kuisioner_target_rr_answer')
        ->select("answer")
        ->where([
            ['id_user','=',$id_user],
            ['flag','=',1],
            ['kode_kuisioner','=',"TARGET_RR"],
        ])
        ->get();
      $target_replacement_ratio = round($res[0]->answer,2);
      $res = DB::table('variabel_kuisioner_target_rr_answer')
        ->select("answer")
        ->where([
            ['id_user','=',$id_user],
            ['flag','=',1],
            ['kode_kuisioner','=',"BEKERJA_TOTAL_PENGELUARAN"],
        ])
        ->get();
      $target_pengeluaran = round($res[0]->answer,2);
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

      //Output: Create $tahun dan $bulan ke masing-masing tahun dan bulan di database usia 
      $usia_tahun = array();
      $usia_bulan = array();
      
      for($year=2023; $year<=2100; $year++){
          for($month=1; $month<=12; $month++){
              if($year==2023 && $month==1){
                $tahun=(int)$diff->format('%y');
                $bulan=(int)$diff->format('%m'); 
                  
                $key_tahun = $year . "_" . $month;
                $usia_tahun[$key_tahun] = $tahun;
                $key_bulan = $year . "_" . $month;
                $usia_bulan[$key_bulan] = $bulan;
                
                $bulan = $bulan +1;
                  
              } else {
                  if($bulan >=12){
                    $bulan = 1;
                    $tahun = $tahun+1;
                  }
                $key_tahun = $year . "_" . $month;
                $usia_tahun[$key_tahun] = $tahun;
                $key_bulan = $year . "_" . $month;
                $usia_bulan[$key_bulan] = $bulan;
                
                $bulan = $bulan +1;
              }
              
          }
      }

      $this->uploadToDatabase("profil_usia_bulan", $id_user, $usia_bulan);

      $this->uploadToDatabase("profil_usia_tahun", $id_user, $usia_tahun);
        
      // -----------------------------------------------------------------------
      //C.2. Simulasi Basic - hitung Masa Dinas (masa dinas diisi dari januari 2023 s.d. desember 2100)
      $date1=date_create($data_user->tgl_diangkat_pegawai); //Read tanggal diangkat
      $date2=date_create("2023-01-31"); //januari 2023
      $diff=date_diff($date1,$date2);

      //Output: Create $masa_dinas_tahun[$i] dan $masa_dinas_bulan[$i] ke masing-masing tahun dan bulan di database masa dinas
      $masa_dinas_tahun = array();
      $masa_dinas_bulan = array();
      
      for($year=2023; $year<=2100; $year++){
          for($month=1; $month<=12; $month++){
              if($year==2023 && $month==1){
                $tahun=(int)$diff->format('%y');
                $bulan=(int)$diff->format('%m');
                  
                $key_tahun = $year . "_" . $month;
                $masa_dinas_tahun[$key_tahun] = $tahun;
                $key_bulan = $year . "_" . $month;
                $masa_dinas_bulan[$key_bulan] = $bulan; 
                  
                $bulan = $bulan +1;
                  
              } else {
                if($bulan >=12){
                  $bulan = 0;
                  $tahun = $tahun+1;
                }
                
                $key_tahun = $year . "_" . $month;
                $masa_dinas_tahun[$key_tahun] = $tahun;
                $key_bulan = $year . "_" . $month;
                $masa_dinas_bulan[$key_bulan] = $bulan;
                
                $bulan = $bulan +1;
              }             
          }
      }

      $this->uploadToDatabase("profil_masa_kerja_tahun", $id_user, $masa_dinas_tahun);

      $this->uploadToDatabase("profil_masa_kerja_bulan", $id_user, $masa_dinas_bulan);
       
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
                 
                 //keluarkan output
                 $key_tahun = $year . "_" . $month;
                 $sisa_kerja_tahun[$key_tahun] = $sisa_kerja_tahun_hitung;
                 $key_bulan = $year . "_" . $month;
                 $sisa_kerja_bulan[$key_bulan] = $sisa_kerja_bulan_hitung;
                                  
                 //menurunkan bulan
                 if($sisa_kerja_bulan_hitung<=0){
                   $sisa_kerja_tahun_hitung=$sisa_kerja_tahun_hitung-1;
                   $sisa_kerja_bulan_hitung=11;
                 } else{
                   $sisa_kerja_bulan_hitung=$sisa_kerja_bulan_hitung-1;
                 }
                 
             } else {
               if($sisa_kerja_bulan_hitung<0){
                   $sisa_kerja_tahun_hitung=$sisa_kerja_tahun_hitung-1;
                   $sisa_kerja_bulan_hitung=11;
               }
               
               //keluarkan output
               $key_tahun = $year . "_" . $month;
               $sisa_kerja_tahun[$key_tahun] = $sisa_kerja_tahun_hitung;
               $key_bulan = $year . "_" . $month;
               $sisa_kerja_bulan[$key_bulan] = $sisa_kerja_bulan_hitung;
                 
               $sisa_kerja_bulan_hitung=$sisa_kerja_bulan_hitung-1;
               
             }             
           }
       }
       
       $this->uploadToDatabase("profil_sisa_masa_kerja_tahun", $id_user, $sisa_kerja_tahun);

       $this->uploadToDatabase("profil_sisa_masa_kerja_bulan", $id_user, $sisa_kerja_bulan);
        
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
       $montecarlo_personal_keuangan = $this->montecarlo_personal($id_user, $sisa_kerja_tahun, $flag_pensiun, $norminv);
       
       //---------------------------------------------------------
       //F. Perhitungan Simulasi
       //F.1. Simulasi Gaji dan PhDP
       $return_simulasi_gaji_phdp = $this->simulasi_gaji_phdp($tgl_update_gaji_phdp, $gaji, $phdp, $id_user);
       //F.2. Simulasi PPMP
       $return_simulasi_ppmp = $this->simulasi_ppmp($data_user, $id_user, $masa_dinas_tahun, $masa_dinas_bulan, $flag_pensiun, $return_simulasi_gaji_phdp);
       //F.3. Simulasi PPIP
       $return_simulasi_ppip = $this->simulasi_ppip($data_user, $id_user, $return_simulasi_ppmp, $flag_pensiun, $return_simulasi_gaji_phdp, $montecarlo_ppip);
       //F.4. Simulasi Personal Properti
       $return_simulasi_personal_properti = $this->simulasi_personal_properti($data_user,$id_user, $return_simulasi_gaji_phdp, $return_simulasi_gaji_phdp);
       //F.5. Simulasi PERSONAL_KEUANGAN
       $return_simulasi_personal_keuangan = $this->simulasi_personal_keuangan($data_user, $id_user, $return_simulasi_gaji_phdp, $flag_pensiun, $montecarlo_personal_keuangan, $return_simulasi_ppmp);
        //echo json_encode($return_simulasi_personal_keuangan, true);
        //die();
       //----------------------------------------------------------------------------
       //G.1. Hitung indikator dashboard - lokasi pensiun4
      $return_dashboard = $this->indikator_dashboard($data_user, $id_user, $flag_pensiun, $sisa_kerja_tahun, $sisa_kerja_bulan, $return_simulasi_ppip, $return_simulasi_personal_properti, $return_simulasi_personal_keuangan, $return_simulasi_ppmp);
//echo json_encode($return_dashboard, true);
      //die();
      //  $this->uploadToDatabase("dashboard", $id_user, $return_dashboard);
       
       //--------------------------------------------------------
       //H.1. dan H.2. Hitung selisih target dan kekurangan iuran personal keuangan
        //input 
      $total_rr = $return_dashboard["rr_total_minimal"];
        
      $setting_nilai_asumsi_user = DB::table('nilai_asumsi_user')
          ->where('id_user', $id_user)
          ->where('flag', 1)
          ->select('*')->get()[0];
        
        $iuran_kini=$setting_nilai_asumsi_user->jumlah_pembayaran_iuran_personal;
        $iuran_hitung=$iuran_kini/100;
        $pisah="pisah";
        /*
        echo json_encode($target_replacement_ratio, true);
        echo json_encode($pisah, true);
        echo json_encode($total_rr, true);
        echo json_encode($pisah, true);
        echo json_encode($iuran_hitung, true);
        echo json_encode($pisah, true);
        //die();
        */
        if ($total_rr<$target_replacement_ratio){
            //simulasi lagi personal keuangan dengan iuran dinaikkan
            for ($j=1; $j<=10000; $j++){
                $iuran_hitung = $iuran_hitung + 0.01;
                $return_simulasi_personal_keuangan_solver1 = $this->simulasi_personal_keuangan_solver1($data_user, $id_user, $return_simulasi_gaji_phdp, $flag_pensiun, $montecarlo_personal_keuangan, $return_simulasi_ppmp, $iuran_hitung);
                //echo json_encode($return_simulasi_personal_keuangan_solver, true);
                //die();
                
                $rr_kini = $this->cari_iuran1($data_user, $id_user, $flag_pensiun, $sisa_kerja_tahun, $sisa_kerja_bulan, $return_simulasi_ppip, $return_simulasi_personal_properti, $return_simulasi_personal_keuangan_solver1, $return_simulasi_ppmp);
                $rr_baru = $rr_kini["rr_total_minimal"];
                //echo json_encode($rr_kini, true);
                //echo json_encode($pisah, true);
                //die();
                if ($j==10000 && $rr_baru<$target_replacement_ratio){
                    //kesimpulannya, iurannya melebihi $iuran_hitung
                    $kesimpulan = "Replacement Ratio Anda diperkirakan kurang dari target";
                    $rekomendasi1 = "silahkan menambahkan iuran personal keuangan lebih dari ";
                    $iuran_hitung = $iuran_hitung*100;
                    $rekomendasi2 = $rekomendasi1 . $iuran_hitung;
                    $rekomendasi = $rekomendasi2 . "% dari gaji Anda dan running kembali hasilnya";
                } elseif ($j<10000 && $rr_baru>=$target_replacement_ratio){
                    //target rr dapat dipenuhi dengan $iuran hitung
                    $kesimpulan = "Replacement Ratio Anda diperkirakan kurang dari target";
                    $rekomendasi1 = "silahkan menambahkan iuran personal keuangan menjadi sebesar ";
                    $iuran_hitung = $iuran_hitung*100;
                    $rekomendasi2 = $rekomendasi1 . $iuran_hitung;
                    $rekomendasi = $rekomendasi2 . "% dari gaji Anda";
                    $j=10001;
                } else {
                }
                /*
                $nn="iuran";
                $mm="RR baru";
                echo json_encode($nn, true);
                echo json_encode($iuran_hitung, true);
                echo json_encode($mm, true);
                echo json_encode($rr_baru, true);
                echo json_encode($pisah, true);
                */
            } 
        } else {
            //iuran sudah cukup
             $kesimpulan = "Selamat. Pensiun Anda telah sesuai target Replacement Ratio";
             $rekomendasi = "pantau terus kinerja portofolio Anda";
        }

      //echo json_encode($iuran_hitung, true);
        //echo json_encode($pisah, true);
        //die();
        
      unset($return_dashboard['pensiun']);
      $return_dashboard["target_rr"] = $target_replacement_ratio;
      $return_dashboard["target_pengeluaran"] = $target_pengeluaran;
      $return_dashboard["kesimpulan"] = $kesimpulan;
      $return_dashboard["rekomendasi"] = $rekomendasi;
       
      $this->uploadToDatabase("dashboard", $id_user, $return_dashboard);
        
      echo json_encode($return_simulasi_gaji_phdp, true);
      die(); 
        
      return response()->json([
        "status" =>true,
        "message"=>"Hitung Berhasil!",
      ],200);
    }

    public function montecarlo_ppip($id_user, $sisa_kerja_tahun, $flag_pensiun, $norminv){
      // Sheet 5
      //Input: Read sisa masa kerja tahun saat awal tahun, portofolio investasi PPIP yang dipilih peserta, return dan risk portofolio ppip, tabel normal inverse;
      $setting_ppip_user = DB::table('setting_portofolio_ppip')->select('*')
      ->where('id_user', $id_user)
      ->where('flag', 1)
      ->get()[0];

      //D.1., D.2., dan D.3. Hitung Montecarlo PPIP - hitung tranche, return, dan risk
      //mulai perhitungan
      $tranche_ppip = array(); // Sheet 5
      $return_ppip = array(); // Sheet 5
      $risk_ppip = array(); // Sheet 5

      //D.4. Hitung Montecarlo PPIP - hitung NAB
      $nab_ppip = array(); // Sheet 5

      $percentile_95_nab_ppip = array();
      $percentile_50_nab_ppip = array();
      $percentile_05_nab_ppip = array();
      $previous_nab = array();
      $nab_ppip_hitung = array();

      $z=1; //untuk konversi $flag_pensiun[$i] dari bulanan ke tahunan
      $iter_mc = 10000;
    
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
        //D.5., D.6., dan D.7. Hitung Montecarlo PPIP - hitung percentile 95, 50, dan 5 dari NAB
        if($tranche_ppip_hitung != "null"){ //jika masih belum pensiun
                  
          //$previous_nab = null;
          for($j=1;$j<=$iter_mc;$j++){      //monte carlo 10.000 iterasi
              if($year==2023){ // untuk perhitungan awal (karena angka sebelumnya indeks dari NAB adalah 100)
                  $acak = mt_rand(1,10000); //generate angka acak dari 1 s.d. 10.000. (angka acak sesuai dengan primary key dari tabel normal inverse dalam database)
                  $nab_ppip_hitung[$j] = round(100 * (1 + ($return_ppip_hitung / 100) + (($risk_ppip_hitung / 100) * $norminv[$acak]) ),2);
                  $previous_nab[$j] = $nab_ppip_hitung[$j];
              } else{
                  $acak = mt_rand(1,10000); //generate angka acak dari 1 s.d. 10.000. (angka acak sesuai dengan primary key dari tabel normal inverse dalam database)
                  $nab_ppip_hitung[$j] = round($previous_nab[$j] * (1 + ($return_ppip_hitung / 100) + (($risk_ppip_hitung / 100) * $norminv[$acak]) ),2);
                  $previous_nab[$j] = $nab_ppip_hitung[$j];
              }
              
              
              //$nab_ppip[$key_loop] = $nab_ppip_hitung;
              //$previous_nab = $nab_ppip[$key_loop];
          }
            //echo json_encode($nab_ppip_hitung, true);
            //die();
        } else{ //jika sudah pensiun
          for($j=1;$j<=$iter_mc;$j++){ //monte carlo 10.000 iterasi
              $nab_ppip_hitung[$j]=0;
              //$nab_ppip[$key_loop] = $nab_ppip_hitung;
          }
        }

        //+++++++++++++++++++++++++++++++++
        //D.5., D.6., dan D.7. Hitung Montecarlo PPIP - hitung percentile 95, 50, dan 5 dari NAB
        //Input: NAB yang telah dihitung sebelumnya
        if($tranche_ppip_hitung != "null"){ //jika masih belum pensiun
            $k=0;//index waktu sorting mulai dari nol
            for ($j=1;$j<=$iter_mc;$j++){
              $percentile_temp1[$k]=$nab_ppip_hitung[$j]; //loading sementara isi dari NAB untuk kemudian di shorting
              $k++;
            }
            
            //$n= $percentile_temp1[1];
            //echo json_encode($percentile_temp1, true);
            //echo json_encode($n, true);
            
            sort($percentile_temp1); //shorting array
            
            //echo json_encode($percentile_temp1, true);
            //$n= $percentile_temp1[0];
            //echo json_encode($n, true);
            //die();
            
            $k=0; //index waktu sorting mulai dari nol
            for ($j=1;$j<=$iter_mc;$j++){
              $percentile_temp2[$j]=$percentile_temp1[$k]; //mengembalikan lagi ke urutan array yang telah disortir
              $k++;
            }
            
            $percentile_95_nab_ppip_hitung = $percentile_temp2[round(0.95 * $iter_mc)]; //mengambil nilai percentile 95
            $percentile_50_nab_ppip_hitung = $percentile_temp2[round(0.5 * $iter_mc)]; //mengambil nilai percentile 50
            $percentile_05_nab_ppip_hitung = $percentile_temp2[round(0.05 * $iter_mc)]; //mengambil nilai percentile 5
          
        } else {
          $percentile_95_nab_ppip_hitung = 0; // nilai percentile 95 saat sudah pensiun
          $percentile_50_nab_ppip_hitung = 0; // nilai percentile 50 saat sudah pensiun
          $percentile_05_nab_ppip_hitung = 0; // nilai percentile 5 saat sudah pensiun
        }

        //Output: Create $percentile_95_nab_ppip[$i], $percentile_50_nab_ppip[$i], dan $percentile_05_nab_ppip[$i]
        $percentile_95_nab_ppip[$key_loop] = $percentile_95_nab_ppip_hitung;
        $percentile_50_nab_ppip[$key_loop] = $percentile_50_nab_ppip_hitung;
        $percentile_05_nab_ppip[$key_loop] = $percentile_05_nab_ppip_hitung;
              
      }  // end dari for 2023 s.d. 2100

      $this->uploadToDatabase("ppip_tahun_tranche", $id_user, $tranche_ppip);
      $this->uploadToDatabase("ppip_tahun_return_portofolio", $id_user, $return_ppip);
      $this->uploadToDatabase("ppip_tahun_risk_portofolio", $id_user, $risk_ppip);

      $this->uploadToDatabase("ppip_nab_p95", $id_user, $percentile_95_nab_ppip);
      $this->uploadToDatabase("ppip_nab_p50", $id_user, $percentile_50_nab_ppip);
      $this->uploadToDatabase("ppip_nab_p5", $id_user, $percentile_05_nab_ppip);
        
      // -----------------------------------------------------------------------
      //D.8., D.9., dan D.10. Hitung Montecarlo PPIP - hitung return dari Percentile NAB
      //termasuk dengan convert monthly di D.11., D.12., dan D.13. Hitung Montecarlo PPIP - hitung return dari Percentile NAB - convert monthly
      $percentile_95_return_ppip=array();
      $percentile_50_return_ppip=array();
      $percentile_05_return_ppip=array();

      $percentile_95_return_monthly_ppip=array();
      $percentile_50_return_monthly_ppip=array();
      $percentile_05_return_monthly_ppip=array();

      //$previous_percentile_95_nab_ppip = null;
      //$previous_percentile_50_nab_ppip = null;
      //$previous_percentile_05_nab_ppip = null;
      for($year=2023; $year<=2100; $year++){
        if ($tranche_ppip[$year] != "null"){ //jika masih belum pensiun
          if ($year==2023){
            //tahunan
            $percentile_95_return_ppip_hitung = ($percentile_95_nab_ppip[$year]/100)-1;
            $percentile_50_return_ppip_hitung = ($percentile_50_nab_ppip[$year]/100)-1;
            $percentile_05_return_ppip_hitung = ($percentile_05_nab_ppip[$year]/100)-1;
              
            //echo json_encode($percentile_95_return_ppip_hitung, true);
            //die();
              
            //$previous_percentile_95_nab_ppip = $percentile_95_return_ppip_hitung;
            //$previous_percentile_50_nab_ppip = $percentile_50_return_ppip_hitung;
            //$previous_percentile_05_nab_ppip = $percentile_05_return_ppip_hitung;
            
            //convert monthly
            $percentile_95_return_monthly_ppip_hitung = pow((1+$percentile_95_return_ppip_hitung),(1/12))-1;
            $percentile_50_return_monthly_ppip_hitung = pow((1+$percentile_50_return_ppip_hitung),(1/12))-1;
            $percentile_05_return_monthly_ppip_hitung = pow((1+$percentile_05_return_ppip_hitung),(1/12))-1;
              
            //echo json_encode($percentile_95_return_monthly_ppip_hitung, true);
            //echo json_encode($percentile_50_return_monthly_ppip_hitung, true);
            //echo json_encode($percentile_05_return_monthly_ppip_hitung, true);
            //die();
          } else {
            //tahunan
            $percentile_95_return_ppip_hitung = ($percentile_95_nab_ppip[$year]/$percentile_95_nab_ppip[$year-1])-1;
            $percentile_50_return_ppip_hitung = ($percentile_50_nab_ppip[$year]/$percentile_50_nab_ppip[$year-1])-1;
            $percentile_05_return_ppip_hitung = ($percentile_05_nab_ppip[$year]/$percentile_05_nab_ppip[$year-1])-1;
              
            //$previous_percentile_95_nab_ppip = $percentile_95_return_ppip_hitung;
            //$previous_percentile_50_nab_ppip = $percentile_50_return_ppip_hitung;
            //$previous_percentile_05_nab_ppip = $percentile_05_return_ppip_hitung;
            
            //convert monthly
            $percentile_95_return_monthly_ppip_hitung = pow((1+$percentile_95_return_ppip_hitung),(1/12))-1;
            $percentile_50_return_monthly_ppip_hitung = pow((1+$percentile_50_return_ppip_hitung),(1/12))-1;
            $percentile_05_return_monthly_ppip_hitung = pow((1+$percentile_05_return_ppip_hitung),(1/12))-1;
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

        $percentile_95_return_monthly_ppip[$year]=$percentile_95_return_monthly_ppip_hitung;
        $percentile_50_return_monthly_ppip[$year]=$percentile_50_return_monthly_ppip_hitung;
        $percentile_05_return_monthly_ppip[$year]=$percentile_05_return_monthly_ppip_hitung;
          
        //echo json_encode($percentile_95_return_monthly_ppip_hitung, true);
        //echo json_encode($percentile_50_return_monthly_ppip_hitung, true);
        //echo json_encode($percentile_05_return_monthly_ppip_hitung, true);
        //die();
        
      }

      $this->uploadToDatabase("ppip_return_nab_p95", $id_user, $percentile_95_return_ppip);
      $this->uploadToDatabase("ppip_return_nab_p50", $id_user, $percentile_50_return_ppip);
      $this->uploadToDatabase("ppip_return_nab_p5", $id_user, $percentile_05_return_ppip);

      $this->uploadToDatabase("ppip_return_nab_month_p95", $id_user, $percentile_95_return_monthly_ppip);
      $this->uploadToDatabase("ppip_return_nab_month_p50", $id_user, $percentile_50_return_monthly_ppip);
      $this->uploadToDatabase("ppip_return_nab_month_p5", $id_user, $percentile_05_return_monthly_ppip);

      return array(
        "tranche_ppip" => $tranche_ppip,
        "return_ppip" => $return_ppip,
        "risk_ppip" => $risk_ppip,
        "nab_ppip" => $nab_ppip,//tidak dipakai karena array nya hanya 1 dimensi. 
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
      // Sheet 6
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
      $nab_personal_hitung = array();
      $previous_nab_personal = array();
      
      $percentile_95_nab_personal = array();
      $percentile_50_nab_personal = array();
      $percentile_05_nab_personal = array();
      $iterasi_mc=10000;
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
          //$previous_nab_personal = null;
          for($l=1;$l<=$iterasi_mc;$l++){      //monte carlo 10.000 iterasi
            if($year==2023){ // untuk perhitungan awal (karena angka sebelumnya indeks dari NAB adalah 100)
                $acak = mt_rand(1,10000); //generate angka acak dari 1 s.d. 10.000. (angka acak sesuai dengan primary key dari tabel normal inverse dalam database)
                $nab_personal_hitung[$l] = round(100 * (1 + ($return_personal_hitung / 100) + (($risk_personal_hitung / 100) * $norminv[$acak]) ),2);
                $previous_nab_personal[$l] = $nab_personal_hitung[$l];
            } else{
                $acak = mt_rand(1,10000); //generate angka acak dari 1 s.d. 10.000. (angka acak sesuai dengan primary key dari tabel normal inverse dalam database)
                $nab_personal_hitung[$l] = round($previous_nab_personal[$l] * (1 + ($return_personal_hitung / 100) + (($risk_personal_hitung / 100) * $norminv[$acak]) ),2);
                $previous_nab_personal[$l] = $nab_personal_hitung[$l];
            }
            //$nab_personal[$year] = round($nab_personal_hitung, 2);
            //$previous_nab_personal = $nab_personal[$year];
          }
        } else{ //jika sudah pensiun
          for($l=1;$l<=$iterasi_mc;$l++){ //monte carlo 10.000 iterasi
              $nab_personal_hitung[$l] = 0;
              //$nab_personal[$year] = round($nab_personal_hitung, 2);
          }
        }

        //+++++++++++++++++++++++++++++++++
        //E.5., E.6., dan E.7. Hitung Montecarlo PERSONAL - hitung percentile 95, 50, dan 5 dari NAB
        //Input: NAB yang telah dihitung sebelumnya
        if($tranche_personal_hitung != "null"){ //jika masih belum pensiun
          $k=0; //index waktu sorting mulai dari nol
          for ($j=1;$j<=$iterasi_mc;$j++){
            $percentile_temp1[$k]=$nab_personal_hitung[$j]; //loading sementara isi dari NAB untuk kemudian di shorting
            $k++;
          }
          
          sort($percentile_temp1); //shorting array

          $k=0; //index waktu sorting mulai dari nol
          for ($j=1;$j<=$iterasi_mc;$j++){
            $percentile_temp2[$j]=$percentile_temp1[$k]; //mengembalikan lagi ke urutan array yang telah disortir
            $k++;
          }
          
          $percentile_95_nab_personal_hitung=$percentile_temp2[round(0.95 * $iterasi_mc)]; //mengambil nilai percentile 95
          $percentile_50_nab_personal_hitung=$percentile_temp2[round(0.5 * $iterasi_mc)]; //mengambil nilai percentile 50
          $percentile_05_nab_personal_hitung=$percentile_temp2[round(0.05 * $iterasi_mc)]; //mengambil nilai percentile 5
        } else {
          $percentile_95_nab_personal_hitung=0; // nilai percentile 95 saat sudah pensiun
          $percentile_50_nab_personal_hitung=0; // nilai percentile 50 saat sudah pensiun
          $percentile_05_nab_personal_hitung=0; // nilai percentile 5 saat sudah pensiun
        }
        //Output: Create $percentile_95_nab_personal[$i], $percentile_50_nab_personal[$i], dan $percentile_05_nab_personal[$i]
        $percentile_95_nab_personal[$year] = $percentile_95_nab_personal_hitung;
        $percentile_50_nab_personal[$year] = $percentile_50_nab_personal_hitung;
        $percentile_05_nab_personal[$year] = $percentile_05_nab_personal_hitung;
      } // end dari for 2023 s.d. 2100

      $this->uploadToDatabase("personal_keuangan_tahun_tranche", $id_user, $tranche_personal);
      $this->uploadToDatabase("personal_keuangan_tahun_return_portofolio", $id_user, $return_personal);
      $this->uploadToDatabase("personal_keuangan_tahun_risk_portofolio", $id_user, $risk_personal);
      
      $this->uploadToDatabase("personal_keuangan_nab_p95", $id_user, $percentile_95_nab_personal);
      $this->uploadToDatabase("personal_keuangan_nab_p50", $id_user, $percentile_50_nab_personal);
      $this->uploadToDatabase("personal_keuangan_nab_p5", $id_user, $percentile_05_nab_personal);
      
      //--------------------------------------------------------
      //E.8., E.9., dan E.10. Hitung Montecarlo PERSONAL - hitung return dari Percentile NAB
      //termasuk dengan convert monthly di E.11., E.12., dan E.13. Hitung Montecarlo PERSONAL - hitung return dari Percentile NAB - convert monthly
      $percentile_95_return_personal=array();
      $percentile_50_return_personal=array();
      $percentile_05_return_personal=array();

      $percentile_95_return_monthly_personal=array();
      $percentile_50_return_monthly_personal=array();
      $percentile_05_return_monthly_personal=array();

      //$previous_percentile_95_nab_personal = null;
      //$previous_percentile_50_nab_personal = null;
      //$previous_percentile_05_nab_personal = null;

      for($year=2023; $year<=2100; $year++){
        //$key_tahun = $year . "_1";
        if ($tranche_personal[$year] != "null"){ //jika masih belum pensiun
          if ($year==2023){
            
            //tahunan
            $percentile_95_return_personal_hitung=($percentile_95_nab_personal[$year]/100)-1;
            $percentile_50_return_personal_hitung=($percentile_50_nab_personal[$year]/100)-1;
            $percentile_05_return_personal_hitung=($percentile_05_nab_personal[$year]/100)-1;
            
            //convert monthly
            $percentile_95_return_monthly_personal_hitung = pow((1+$percentile_95_return_personal_hitung),(1/12))-1;
            $percentile_50_return_monthly_personal_hitung = pow((1+$percentile_50_return_personal_hitung),(1/12))-1;
            $percentile_05_return_monthly_personal_hitung = pow((1+$percentile_05_return_personal_hitung),(1/12))-1;
          } else {
            
            //tahunan
            $percentile_95_return_personal_hitung=($percentile_95_nab_personal[$year]/$percentile_95_nab_personal[$year-1])-1;
            $percentile_50_return_personal_hitung=($percentile_50_nab_personal[$year]/$percentile_50_nab_personal[$year-1])-1;
            $percentile_05_return_personal_hitung=($percentile_05_nab_personal[$year]/$percentile_05_nab_personal[$year-1])-1;
            
            //convert monthly
            $percentile_95_return_monthly_personal_hitung = pow((1+$percentile_95_return_personal_hitung),(1/12))-1;
            $percentile_50_return_monthly_personal_hitung = pow((1+$percentile_50_return_personal_hitung),(1/12))-1;
            $percentile_05_return_monthly_personal_hitung = pow((1+$percentile_05_return_personal_hitung),(1/12))-1;
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

        //$previous_percentile_95_nab_personal = $percentile_95_return_personal[$year];
        //$previous_percentile_50_nab_personal = $percentile_50_return_personal[$year];
        //$previous_percentile_05_nab_personal = $percentile_05_return_personal[$year];

        $percentile_95_return_monthly_personal[$year]=$percentile_95_return_monthly_personal_hitung;
        $percentile_50_return_monthly_personal[$year]=$percentile_50_return_monthly_personal_hitung;
        $percentile_05_return_monthly_personal[$year]=$percentile_05_return_monthly_personal_hitung;
      }
      
      $this->uploadToDatabase("personal_keuangan_return_nab_p95", $id_user, $percentile_95_return_personal);
      $this->uploadToDatabase("personal_keuangan_return_nab_p50", $id_user, $percentile_50_return_personal);
      $this->uploadToDatabase("personal_keuangan_return_nab_p95", $id_user, $percentile_05_return_personal);
      
      $this->uploadToDatabase("personal_keuangan_return_nab_month_p95", $id_user, $percentile_95_return_monthly_personal);
      $this->uploadToDatabase("personal_keuangan_return_nab_month_p50", $id_user, $percentile_50_return_monthly_personal);
      $this->uploadToDatabase("personal_keuangan_return_nab_month_p5", $id_user, $percentile_05_return_monthly_personal);

      return array(
        "tranche_personal" => $tranche_personal,
        "return_personal" => $return_personal,
        "risk_personal" => $risk_personal,
        "nab_personal" => $nab_personal, //tidak digunakan karena array nab hanya 1 dimensi
        "percentile_95_nab_personal" => $percentile_95_nab_personal,
        "percentile_50_nab_personal" => $percentile_50_nab_personal,
        "percentile_05_nab_personal" => $percentile_05_nab_personal,
        "percentile_95_return_personal" => $percentile_95_return_personal,
        "percentile_50_return_personal" => $percentile_50_return_personal,
        "percentile_05_return_personal" => $percentile_05_return_personal,
        "percentile_95_return_monthly_personal" => $percentile_95_return_monthly_personal,
        "percentile_50_return_monthly_personal" => $percentile_50_return_monthly_personal,
        "percentile_05_return_monthly_personal" => $percentile_05_return_monthly_personal,
      );
    }

    public function simulasi_gaji_phdp($tgl_update_gaji_phdp, $gaji_form, $phdp_form,  $id_user){
      // Tidak disimpan ke DB
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

      //$gaji_naik = $setting_nilai_asumsi_user->kenaikan_gaji;//Read kenaikan gaji di admin
      //$phdp_naik = $setting_nilai_asumsi_user->kenaikan_phdp;//Read kenaikan phdp di admin
      $gaji_naik =7.5; //diinjek disini karena kode di atas masih salah baca setting di user, harusnya setting di admin
      $phdp_naik =5; //diinjek disini karena kode di atas masih salah baca setting di user, harusnya setting di admin
        
      //echo json_encode($phdp_naik, true);
      //die();

      $year = 2023; //tahun awal di database
      $k=1;
      $kode = ($year*100)+$k; //untuk perbandingan kode input

      $gaji = array();
      $phdp = array();

      $previous_gaji = null;
      $previous_phdp = null;
      $tanda = 0; //untuk menandai awal mula pengisian
      for($year=2023; $year<=2100; $year++){
        for($month=1; $month<=12; $month++){
          $key = $year . "_" . $month;
          $kode = ($year*100)+$month;
            
            
          if($kode < $kode_input){
              $gaji_hitung = $gaji_input; // Sementara diganti dengan gaji input sebelumnya adalah 0
              $phdp_hitung = $phdp_input; // Sementara diganti dengan phdp input sebelumnya adalah 0
              
              $previous_gaji = $gaji_hitung;
              $previous_phdp = $phdp_hitung;
              
          } else if ($kode == $kode_input){
             $gaji_hitung = $gaji_input;
             $phdp_hitung = $phdp_input;
            
             $previous_gaji = $gaji_hitung;
             $previous_phdp = $phdp_hitung;
            
             $counter_saldo_ppip_hitung = $key; //numpang kode counter, untuk menandai mulai isi saldo di bulan ke berapa
             $counter_saldo_personal_keuangan = $key;//numpang kode counter, untuk menandai mulai isi saldo di bulan ke berapa
             $counter_saldo_personal_properti = $key;//numpang kode counter, untuk menandai mulai isi saldo di bulan ke berapa
                       
          } else {
            if($month==1){
              $gaji_hitung = $previous_gaji*(1+$gaji_naik/100);
              //$phdp_hitung = $previous_phdp*(1+$phdp_naik/100);
              $phdp_hitung = $previous_phdp+$gaji_hitung*0.45*($phdp_naik/100);
                
              $previous_gaji = $gaji_hitung;
              $previous_phdp = $phdp_hitung;
              
            } else{
              $gaji_hitung = $previous_gaji;
              $phdp_hitung = $previous_phdp;
              
              $previous_gaji = $gaji_hitung;
              $previous_phdp = $phdp_hitung;
            }
          }
          
          $gaji[$key] = $gaji_hitung;
          $phdp[$key] = $phdp_hitung;
          
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

    public function simulasi_ppmp($data_user, $id_user, $masa_dinas_tahun, $masa_dinas_bulan, $flag_pensiun, $return_simulasi_gaji_phdp){
      // Sheet 4 Baris 29
      //Input: variabel $phdp[$i] yang ada di memory, Read masa dinas tahun dan bulan, dan flag pensiun
      $date1 = date_create($data_user->tgl_diangkat_pegawai); //Read tanggal diangkat
      $date2 = date_create("2015-01-01"); //tanggal cutoff pensiun hybrid. yang diangkat setelah 1 januari 2015 ppip murni, kalau sebelumnya hybrid ppmp dan ppip
      $diff = date_diff($date1,$date2);
      
      $hari = $diff->format('%R%a');
      //echo json_encode($hari, true);
      //die();  

      $gaji = $return_simulasi_gaji_phdp['gaji'];
      $phdp = $return_simulasi_gaji_phdp['phdp'];

      $jumlah_ppmp = array();
      $jumlah_ppmp_year_month = array();
      $rr_ppmp = array();
      $rr_ppmp_year_month = array();
      $status_mp = array();
      for($year=2023; $year<=2100; $year++){
        for($month=1; $month<=12; $month++){
          $key = $year . "_" . $month;
          if ($hari > 0){ //hybrid ppmp ppip
            $status_mp_hitung = 1;//untuk hybrid ppmp ppip
            if ($flag_pensiun[$key]==0){ //belum pensiun
              $masa_dinas_sementara = $masa_dinas_tahun[$key]+($masa_dinas_bulan[$key] / 12);
              $masa_dinas = min($masa_dinas_sementara,32); //maksimum masa dinas yang bisa diabsorb oleh ppmp adalah 32 tahun
              $jumlah_ppmp_hitung = 0.025 * $masa_dinas * $phdp[$key]; //rumus besar MP dalam PPMP
                if($gaji[$key] == 0){
                  $rr_ppmp_hitung = "null"; //rumus mencari replacement ratio dalam ppmp                
                }else{
                  $rr_ppmp_hitung = $jumlah_ppmp_hitung / $gaji[$key]; //rumus mencari replacement ratio dalam ppmp
                }
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
          $jumlah_ppmp_year_month[$key] = $jumlah_ppmp_hitung;
          $rr_ppmp[$year] = $rr_ppmp_hitung;
          $rr_ppmp_year_month[$key] = $rr_ppmp_hitung;
          $status_mp[$year] = $status_mp_hitung;
        }
      }
      //echo json_encode($rr_ppmp_year_month, true);
      //die();  
      $this->uploadToDatabase("profil_ppmp_besar", $id_user, $jumlah_ppmp_year_month);
      $this->uploadToDatabase("profil_ppmp_rr", $id_user, $rr_ppmp_year_month);

      return array(
        "jumlah_ppmp"=>$jumlah_ppmp_year_month,
        "rr_ppmp"=>$rr_ppmp_year_month,
        "status_mp"=>$status_mp,
      );
    }

    public function simulasi_ppip($data_user, $id_user, $return_simulasi_ppmp, $flag_pensiun, $return_simulasi_gaji_phdp, $montecarlo_ppip){
      // Sheet 4 Baris 32
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
      //menentukan besar iuran - dipindah ke bawah
      //if ($status_mp==1){ //hybrid ppmp ppip
        //$persentase_iuran_ppip = 0.09; //iuran ppip sebesar 9% untuk hybrid ppmp ppip
      //} else {
        //$persentase_iuran_ppip = 0.2; //iuran ppip sebesar 20% untuk ppip murni
      //}

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
        
        $kupon_sbn_ppip =$kupon_sbn_ppip / 100;
        $pajak_sbn_ppip =$pajak_sbn_ppip / 100;
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
            //menentukan besar iuran
            if ($status_mp[$year]==1){ //hybrid ppmp ppip
                    $persentase_iuran_ppip = 0.09; //iuran ppip sebesar 9% untuk hybrid ppmp ppip
            } else {
                    $persentase_iuran_ppip = 0.2; //iuran ppip sebesar 20% untuk ppip murni
            }
        for($month=1; $month<=12; $month++){
          $key = $year . "_" . $month;
          $iuran_hitung = $gaji[$key] * $persentase_iuran_ppip; //hitung besar iuran
            //echo json_encode($gaji[$key], true);
            //die();  

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
          $anuitas_ppip_p95[$key] = $anuitas_ppip_p95_hitung;
          $anuitas_ppip_p50[$key] = $anuitas_ppip_p50_hitung;
          $anuitas_ppip_p05[$key] = $anuitas_ppip_p05_hitung;
          $kupon_sbn_ppip_p95[$key] = $kupon_sbn_ppip_p95_hitung;
          $kupon_sbn_ppip_p50[$key] = $kupon_sbn_ppip_p50_hitung;
          $kupon_sbn_ppip_p05[$key] = $kupon_sbn_ppip_p05_hitung;

          //Output: Create $rr_ppip_anuitas_p95[$i], $rr_ppip_anuitas_p50[$i], $rr_ppip_anuitas_p05[$i], $rr_ppip_kupon_sbn_p95[$i], $rr_ppip_kupon_sbn_p50[$i], $rr_ppip_kupon_sbn_p05[$i]
          $rr_ppip_anuitas_p95[$key] = $rr_ppip_anuitas_p95_hitung;
          $rr_ppip_anuitas_p50[$key] = $rr_ppip_anuitas_p50_hitung;
          $rr_ppip_anuitas_p05[$key] = $rr_ppip_anuitas_p05_hitung;
          $rr_ppip_kupon_sbn_p95[$key] = $rr_ppip_kupon_sbn_p95_hitung;
          $rr_ppip_kupon_sbn_p50[$key] = $rr_ppip_kupon_sbn_p50_hitung;
          $rr_ppip_kupon_sbn_p05[$key] = $rr_ppip_kupon_sbn_p05_hitung;
        }
      }

      $this->uploadToDatabase("profil_ppip_besar_iuran", $id_user, $iuran);

      $this->uploadToDatabase("profil_ppip_investasi_p95", $id_user, $percentile_95_return_ppip_bulanan);
      $this->uploadToDatabase("profil_ppip_investasi_p50", $id_user, $percentile_50_return_ppip_bulanan);
      $this->uploadToDatabase("profil_ppip_investasi_p5", $id_user, $percentile_05_return_ppip_bulanan);
      
      $this->uploadToDatabase("profil_ppip_p95_saldo_awal", $id_user, $saldo_ppip_awal_p95);
      $this->uploadToDatabase("profil_ppip_p50_saldo_awal", $id_user, $saldo_ppip_awal_p50);
      $this->uploadToDatabase("profil_ppip_p5_saldo_awal", $id_user, $saldo_ppip_awal_p05);
      
      $this->uploadToDatabase("profil_ppip_p95_saldo_akhir", $id_user, $saldo_ppip_akhir_p95);
      $this->uploadToDatabase("profil_ppip_p50_saldo_akhir", $id_user, $saldo_ppip_akhir_p50);
      $this->uploadToDatabase("profil_ppip_p5_saldo_akhir", $id_user, $saldo_ppip_akhir_p05);
      
      $this->uploadToDatabase("profil_ppip_p95_pengembangan", $id_user, $pengembangan_ppip_p95);
      $this->uploadToDatabase("profil_ppip_p50_pengembangan", $id_user, $pengembangan_ppip_p50);
      $this->uploadToDatabase("profil_ppip_p5_pengembangan", $id_user, $pengembangan_ppip_p05);
      
      $this->uploadToDatabase("profil_ppip_anuitas_p95", $id_user, $anuitas_ppip_p95);
      $this->uploadToDatabase("profil_ppip_anuitas_p50", $id_user, $anuitas_ppip_p50);
      $this->uploadToDatabase("profil_ppip_anuitas_p5", $id_user, $anuitas_ppip_p05);
      
      $this->uploadToDatabase("profil_ppip_bunga_deposito_p95", $id_user, $kupon_sbn_ppip_p95);
      $this->uploadToDatabase("profil_ppip_bunga_deposito_p50", $id_user, $kupon_sbn_ppip_p50);
      $this->uploadToDatabase("profil_ppip_bunga_deposito_p5", $id_user, $kupon_sbn_ppip_p05);
      
      $this->uploadToDatabase("profil_ppip_rr_anuitas_p95", $id_user, $rr_ppip_anuitas_p95);
      $this->uploadToDatabase("profil_ppip_rr_anuitas_p50", $id_user, $rr_ppip_anuitas_p50);
      $this->uploadToDatabase("profil_ppip_rr_anuitas_p5", $id_user, $rr_ppip_anuitas_p05);
      
      $this->uploadToDatabase("profil_ppip_rr_bunga_deposito_p95", $id_user, $rr_ppip_kupon_sbn_p95);
      $this->uploadToDatabase("profil_ppip_rr_bunga_deposito_p50", $id_user, $rr_ppip_kupon_sbn_p50);
      $this->uploadToDatabase("profil_ppip_rr_bunga_deposito_p5", $id_user, $rr_ppip_kupon_sbn_p05);

      return array(
        "iuran" => $iuran,
        "tambahan_iuran_ppip" => $tambahan_iuran_ppip,
        "percentile_95_return_ppip_bulanan" => $percentile_95_return_ppip_bulanan,
        "percentile_50_return_ppip_bulanan" => $percentile_50_return_ppip_bulanan,
        "percentile_05_return_ppip_bulanan" => $percentile_05_return_ppip_bulanan,
        "saldo_ppip_awal_p95" => $saldo_ppip_awal_p95,
        "pengembangan_ppip_p95" => $pengembangan_ppip_p95,
        "saldo_ppip_akhir_p95" => $saldo_ppip_akhir_p95, // Data FE Diagram
        "saldo_ppip_awal_p50" => $saldo_ppip_awal_p50,
        "pengembangan_ppip_p50" => $pengembangan_ppip_p50,
        "saldo_ppip_akhir_p50" => $saldo_ppip_akhir_p50, // Data FE Diagram
        "saldo_ppip_awal_p05" => $saldo_ppip_awal_p05,
        "pengembangan_ppip_p05" => $pengembangan_ppip_p05,
        "saldo_ppip_akhir_p05" => $saldo_ppip_akhir_p05, // Data FE Diagram
        "anuitas_ppip_p95" => $anuitas_ppip_p95,
        "anuitas_ppip_p50" => $anuitas_ppip_p50,
        "anuitas_ppip_p05" => $anuitas_ppip_p05,
        "kupon_sbn_ppip_p95" => $kupon_sbn_ppip_p95,
        "kupon_sbn_ppip_p50" => $kupon_sbn_ppip_p50,
        "kupon_sbn_ppip_p05" => $kupon_sbn_ppip_p05,
        "rr_ppip_anuitas_p95" => $rr_ppip_anuitas_p95,
        "rr_ppip_anuitas_p50" => $rr_ppip_anuitas_p50,
        "rr_ppip_anuitas_p05" => $rr_ppip_anuitas_p05,
        "rr_ppip_kupon_sbn_p95" => $rr_ppip_kupon_sbn_p95,
        "rr_ppip_kupon_sbn_p50" => $rr_ppip_kupon_sbn_p50,
        "rr_ppip_kupon_sbn_p05" => $rr_ppip_kupon_sbn_p05,
      );
    }

    public function simulasi_personal_properti($data_user,$id_user, $return_simulasi_gaji_phdp){
      // Sheet 4 Baris 69
      //F.4.1. dan F.4.2. Simulasi Properti - Hitung harga dan sewa properti
      //Input: Read harga properti, sewa tahunan, kenaikan harga properti, dan kenaikan harga sewa di profil user
      $saldo_personal_properti_input=$data_user->jumlah_investasi_properti;// Read harga properti keuangan yang diinput di profil user
      $sewa_personal_properti_input=$data_user->sewa_properti;// Read harga properti keuangan yang diinput di profil user

      $naik_harga_properti=$data_user->kenaikan_properti; // Read kenaikan harga properti keuangan yang diinput di profil user
      $naik_sewa_properti=$data_user->kenaikan_sewa; // Read kenaikan sewa properti keuangan yang diinput di profil user

      $gaji = $return_simulasi_gaji_phdp['gaji'];
        
      //echo json_encode($naik_harga_properti, true);
      //echo json_encode($naik_sewa_properti, true);
      //die();
      $counter_saldo_personal_properti = explode("_", $return_simulasi_gaji_phdp['counter_saldo_personal_properti']);
      $counter_saldo_personal_properti_year = $counter_saldo_personal_properti[0]; 
      $counter_saldo_personal_properti_month = $counter_saldo_personal_properti[1];
        
      //echo json_encode($counter_saldo_personal_properti_month, true);
      //die();

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
            
            $previous_harga_properti = $harga_properti_hitung;
            $previous_sewa_properti = $sewa_properti_hitung;
              
          } else if ($year>$counter_saldo_personal_properti_year || $month>$counter_saldo_personal_properti_month) {
            if ($month==1){ //jika sudah bulan januari maka harga rumah dan sewa naik
              $harga_properti_hitung = $previous_harga_properti * (1+$naik_harga_properti/100);
              $sewa_properti_hitung = $previous_sewa_properti * (1+$naik_sewa_properti/100);
              
              $previous_harga_properti = $harga_properti_hitung;
              $previous_sewa_properti = $sewa_properti_hitung;
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

      $this->uploadToDatabase("profil_personal_properti_harga", $id_user, $harga_properti);
      $this->uploadToDatabase("profil_personal_properti_sewa", $id_user, $sewa_properti);
      $this->uploadToDatabase("profil_personal_properti_rr", $id_user, $rr_personal_properti);

      return array(
        "harga_properti" => $harga_properti,
        "sewa_properti" => $sewa_properti,
        "rr_personal_properti" => $rr_personal_properti,
      );
    }

    public function simulasi_personal_keuangan($data_user, $id_user, $return_simulasi_gaji_phdp, $flag_pensiun, $montecarlo_personal_keuangan, $return_simulasi_ppmp){
      // Sheet 4 Baris 73
      //Input: variabel $gaji{$i] yang ada di memory serta flag pensiun, Read tambahan iuran personal_keuangan, Read Saldo PERSONAL_KEUANGAN
      $gaji = $return_simulasi_gaji_phdp['gaji'];
      $counter_saldo_personal_keuangan = explode("_", $return_simulasi_gaji_phdp['counter_saldo_personal_keuangan']);
      $counter_saldo_personal_keuangan_year = $counter_saldo_personal_keuangan[0]; 
      $counter_saldo_personal_keuangan_month = $counter_saldo_personal_keuangan[1];

      //F.5.1. Simulasi PERSONAL_KEUANGAN - Hitung iuran
      $setting_nilai_asumsi_user = DB::table('nilai_asumsi_user')
            ->where('id_user', $id_user)
            ->where('flag', 1)
            ->select('*')->get()[0];
      $persentase_iuran_personal_keuangan=$setting_nilai_asumsi_user->jumlah_pembayaran_iuran_personal; //Read besar iuran personal keuangan di profil user
      $saldo_personal_keuangan_input=$data_user->jumlah_investasi_keuangan; // Read saldo personal_keuangan yang diinput (saldo diasumsikan diinput di awal bulan)

      //nilai default pilihan pembayaran personal keuangan
      //Input: Read pilihan pembayaran personal keuangan, Read kupon SBN/SBSN dan beserta pajak dari profil user, Read Harga anuitas dari profil user
      //pembayaran personal_keuangan jika 1=anuitas; 2=kupon SBN/SBSN

      $setting_treatment_user = DB::table('setting_treatment_pembayaran_setelah_pensiun')
            ->where('id_user', $id_user)
            ->where('flag', 1)
            ->select('*')->get()[0];

      $pembayaran_personal_keuangan=($setting_treatment_user->personal_pasar_keuangan === 'Beli Anuitas') ? 1 : 2;//Read pilihan pembayaran personal_keuangan (pembayaran personal_keuangan jika 1=anuitas; 2=kupon SBN/SBSN)
      if($pembayaran_personal_keuangan==1){
        $harga_anuitas_personal_keuangan =$setting_treatment_user->harga_anuitas_personal_pasar_keuangan;//Read harga anuitas masing-masing user
        
        $kupon_sbn_personal_keuangan =0.06125;//default
        $pajak_sbn_personal_keuangan =0.01;//default
      } else {
        $harga_anuitas_personal_keuangan =136;//default
        
        $kupon_sbn_personal_keuangan =$setting_treatment_user->bunga_personal_pasar_keuangan;//Read kupon SBN/SBSN dari profil user
        $pajak_sbn_personal_keuangan =$setting_treatment_user->pajak_personal_pasar_keuangan;//Read pajak SBN/SBSN dari profil user
        
        $kupon_sbn_personal_keuangan =$kupon_sbn_personal_keuangan / 100;
        $pajak_sbn_personal_keuangan =$pajak_sbn_personal_keuangan / 100;
      }
     //echo json_encode($kupon_sbn_personal_keuangan, true);
       //die();
      $percentile_95_return_monthly_personal = $montecarlo_personal_keuangan["percentile_95_return_monthly_personal"];
      $percentile_50_return_monthly_personal = $montecarlo_personal_keuangan["percentile_50_return_monthly_personal"];
      $percentile_05_return_monthly_personal = $montecarlo_personal_keuangan["percentile_05_return_monthly_personal"];

      $iuran_personal_keuangan = array();
      $percentile_95_return_personal_keuangan_bulanan = array();
      $percentile_50_return_personal_keuangan_bulanan = array();
      $percentile_05_return_personal_keuangan_bulanan = array();

      $saldo_personal_keuangan_awal_p95 = array();
      $pengembangan_personal_keuangan_p95 = array();
      $saldo_personal_keuangan_akhir_p95 = array();
      
      $saldo_personal_keuangan_awal_p50 = array();
      $pengembangan_personal_keuangan_p50 = array();
      $saldo_personal_keuangan_akhir_p50 = array();
      
      $saldo_personal_keuangan_awal_p05 = array();
      $pengembangan_personal_keuangan_p05 = array();
      $saldo_personal_keuangan_akhir_p05 = array();

      $previous_saldo_personal_keuangan_akhir_p95 = null;
      $previous_saldo_personal_keuangan_akhir_p50 = null;
      $previous_saldo_personal_keuangan_akhir_p05 = null;
      
      $anuitas_personal_keuangan_p95 = array();
      $anuitas_personal_keuangan_p50 = array();
      $anuitas_personal_keuangan_p05 = array();
      $kupon_sbn_personal_keuangan_p95 = array();
      $kupon_sbn_personal_keuangan_p50 = array();
      $kupon_sbn_personal_keuangan_p05 = array();
      
      $rr_personal_keuangan_anuitas_p95 = array();
      $rr_personal_keuangan_anuitas_p50 = array();
      $rr_personal_keuangan_anuitas_p05 = array();
      $rr_personal_keuangan_kupon_sbn_p95 = array();
      $rr_personal_keuangan_kupon_sbn_p50 = array();
      $rr_personal_keuangan_kupon_sbn_p05 = array();

      $j=1; //counter hasil investasi percentile monthly (konversi dari tahunan ke bulanan)
      for($year=2023; $year<=2100; $year++){
        for($month=1; $month<=12; $month++){
          $key = $year . "_" . $month;

          $iuran_personal_keuangan_hitung = $gaji[$key] * $persentase_iuran_personal_keuangan/100; //hitung besar iuran
          
          // //+++++++++++++++++++++++++++++++++++++
          // //F.5.2., F.5.3., dan F.5.4. Simulasi PERSONAL_KEUANGAN - tentukan hasil investasi percentile 95, 50, dan 05
          $percentile_95_return_personal_bulanan_hitung = $percentile_95_return_monthly_personal[$year]; //menentukan percentile secara bulanan dari yang sebelumnya tahunan di monte carlo PERSONAL_KEUANGAN
          $percentile_50_return_personal_bulanan_hitung = $percentile_50_return_monthly_personal[$year]; //menentukan percentile secara bulanan dari yang sebelumnya tahunan di monte carlo PERSONAL_KEUANGAN
          $percentile_05_return_personal_bulanan_hitung = $percentile_05_return_monthly_personal[$year]; //menentukan percentile secara bulanan dari yang sebelumnya tahunan di monte carlo PERSONAL_KEUANGAN

          //Output: Create $iuran_personal_keuangan[$i], $percentile_95_return_personal_keuangan_bulanan[$i], $percentile_50_return_personal_keuangan_bulanan[$i], $percentile_05_return_personal_keuangan_bulanan[$i]
          $iuran_personal_keuangan[$key] = $iuran_personal_keuangan_hitung;
          $percentile_95_return_personal_keuangan_bulanan[$key] = $percentile_95_return_personal_bulanan_hitung;
          $percentile_50_return_personal_keuangan_bulanan[$key] = $percentile_50_return_personal_bulanan_hitung;
          $percentile_05_return_personal_keuangan_bulanan[$key] = $percentile_05_return_personal_bulanan_hitung;
            
          //echo json_encode($percentile_95_return_personal_bulanan_hitung, true);
          //die();

          
          // +++++++++++++++++++++++++++++++++++++
          // F.5.5., F.5.6., F.5.7., F.5.8., F.5.9., F.5.10., F.5.11., F.5.12., dan F.5.13. Simulasi PERSONAL_KEUANGAN - hitung percentile 95,50,05 untuk saldo awal, hasil pengembangan, dan saldo akhir
          if($year==$counter_saldo_personal_keuangan_year && $month==$counter_saldo_personal_keuangan_month){ //tahun pertama ada saldonya
            //percentile 95
            $saldo_personal_keuangan_awal_p95_hitung = $saldo_personal_keuangan_input;
            $pengembangan_personal_keuangan_p95_hitung = ($saldo_personal_keuangan_awal_p95_hitung + $iuran_personal_keuangan_hitung )* $percentile_95_return_personal_bulanan_hitung;
            $saldo_personal_keuangan_akhir_p95_hitung = $saldo_personal_keuangan_awal_p95_hitung + $iuran_personal_keuangan_hitung + $pengembangan_personal_keuangan_p95_hitung; //saldo merupakan saldo akhir bulan
            $previous_saldo_personal_keuangan_akhir_p95 = $saldo_personal_keuangan_akhir_p95_hitung;
            
           //echo json_encode($pengembangan_personal_keuangan_p95_hitung, true);
           //echo json_encode($saldo_personal_keuangan_awal_p95_hitung, true);
           //echo json_encode($iuran_personal_keuangan_hitung, true);
           //echo json_encode($percentile_95_return_personal_bulanan_hitung, true);
           //die();
              
            //percentile 50
            $saldo_personal_keuangan_awal_p50_hitung = $saldo_personal_keuangan_input;
            $pengembangan_personal_keuangan_p50_hitung = ($saldo_personal_keuangan_awal_p50_hitung + $iuran_personal_keuangan_hitung )* $percentile_50_return_personal_bulanan_hitung;
            $saldo_personal_keuangan_akhir_p50_hitung = $saldo_personal_keuangan_awal_p50_hitung + $iuran_personal_keuangan_hitung + $pengembangan_personal_keuangan_p50_hitung; //saldo merupakan saldo akhir bulan
            $previous_saldo_personal_keuangan_akhir_p50 = $saldo_personal_keuangan_akhir_p50_hitung;
              
            //percentile 05
            $saldo_personal_keuangan_awal_p05_hitung = $saldo_personal_keuangan_input;
            $pengembangan_personal_keuangan_p05_hitung = ($saldo_personal_keuangan_awal_p05_hitung + $iuran_personal_keuangan_hitung )* $percentile_05_return_personal_bulanan_hitung;
            $saldo_personal_keuangan_akhir_p05_hitung = $saldo_personal_keuangan_awal_p05_hitung + $iuran_personal_keuangan_hitung + $pengembangan_personal_keuangan_p05_hitung; //saldo merupakan saldo akhir bulan
            $previous_saldo_personal_keuangan_akhir_p05 = $saldo_personal_keuangan_akhir_p05_hitung;
              
          } else if ($year>$counter_saldo_personal_keuangan_year || $month>$counter_saldo_personal_keuangan_month) {
            //percentile 95
            $saldo_personal_keuangan_awal_p95_hitung = $previous_saldo_personal_keuangan_akhir_p95;
            $pengembangan_personal_keuangan_p95_hitung = ($saldo_personal_keuangan_awal_p95_hitung + $iuran_personal_keuangan_hitung )* $percentile_95_return_personal_bulanan_hitung;
            $saldo_personal_keuangan_akhir_p95_hitung = $saldo_personal_keuangan_awal_p95_hitung + $iuran_personal_keuangan_hitung + $pengembangan_personal_keuangan_p95_hitung; //saldo merupakan saldo akhir bulan
            $previous_saldo_personal_keuangan_akhir_p95 = $saldo_personal_keuangan_akhir_p95_hitung;
              
            //percentile 50
            $saldo_personal_keuangan_awal_p50_hitung = $previous_saldo_personal_keuangan_akhir_p50;
            $pengembangan_personal_keuangan_p50_hitung = ($saldo_personal_keuangan_awal_p50_hitung + $iuran_personal_keuangan_hitung )* $percentile_50_return_personal_bulanan_hitung;
            $saldo_personal_keuangan_akhir_p50_hitung = $saldo_personal_keuangan_awal_p50_hitung + $iuran_personal_keuangan_hitung + $pengembangan_personal_keuangan_p50_hitung; //saldo merupakan saldo akhir bulan
            $previous_saldo_personal_keuangan_akhir_p50 = $saldo_personal_keuangan_akhir_p50_hitung;
              
            //percentile 05
            $saldo_personal_keuangan_awal_p05_hitung = $previous_saldo_personal_keuangan_akhir_p05;
            $pengembangan_personal_keuangan_p05_hitung = ($saldo_personal_keuangan_awal_p05_hitung + $iuran_personal_keuangan_hitung )* $percentile_05_return_personal_bulanan_hitung;
            $saldo_personal_keuangan_akhir_p05_hitung = $saldo_personal_keuangan_awal_p05_hitung + $iuran_personal_keuangan_hitung + $pengembangan_personal_keuangan_p05_hitung; //saldo merupakan saldo akhir bulan
            $previous_saldo_personal_keuangan_akhir_p05 = $saldo_personal_keuangan_akhir_p05_hitung;  
              
          } else{
            //percentile 95
            $saldo_personal_keuangan_awal_p95_hitung = 0;
            $pengembangan_personal_keuangan_p95_hitung = 0;
            $saldo_personal_keuangan_akhir_p95_hitung = 0;
            
            //percentile 50
            $saldo_personal_keuangan_awal_p50_hitung = 0;
            $pengembangan_personal_keuangan_p50_hitung = 0;
            $saldo_personal_keuangan_akhir_p50_hitung = 0;
            
            //percentile 05
            $saldo_personal_keuangan_awal_p05_hitung = 0;
            $pengembangan_personal_keuangan_p05_hitung = 0;
            $saldo_personal_keuangan_akhir_p05_hitung = 0;
          }

          //output: Create $saldo_personal_keuangan_awal_p95[$i], $pengembangan_personal_keuangan_p95[$i], $saldo_personal_keuangan_akhir_p95[$i], $saldo_personal_keuangan_awal_p50[$i], $pengembangan_personal_keuangan_p50[$i], $saldo_personal_keuangan_akhir_p50[$i], $saldo_personal_keuangan_awal_p05[$i], $pengembangan_personal_keuangan_p05[$i], $saldo_personal_keuangan_akhir_p05[$i]
          $saldo_personal_keuangan_awal_p95[$key] = $saldo_personal_keuangan_awal_p95_hitung;
          $pengembangan_personal_keuangan_p95[$key] = $pengembangan_personal_keuangan_p95_hitung;
          $saldo_personal_keuangan_akhir_p95[$key] = $saldo_personal_keuangan_akhir_p95_hitung;
          
          $saldo_personal_keuangan_awal_p50[$key] = $saldo_personal_keuangan_awal_p50_hitung;
          $pengembangan_personal_keuangan_p50[$key] = $pengembangan_personal_keuangan_p50_hitung;
          $saldo_personal_keuangan_akhir_p50[$key] = $saldo_personal_keuangan_akhir_p50_hitung;
          
          $saldo_personal_keuangan_awal_p05[$key] = $saldo_personal_keuangan_awal_p05_hitung;
          $pengembangan_personal_keuangan_p05[$key] = $pengembangan_personal_keuangan_p05_hitung;
          $saldo_personal_keuangan_akhir_p05[$key] = $saldo_personal_keuangan_akhir_p05_hitung;

          //$previous_saldo_personal_keuangan_akhir_p95 = $saldo_personal_keuangan_akhir_p95[$key];
          //$previous_saldo_personal_keuangan_akhir_p50 = $saldo_personal_keuangan_akhir_p50[$key];
          //$previous_saldo_personal_keuangan_akhir_p05 = $saldo_personal_keuangan_akhir_p05[$key];
          
          //++++++++++++++++++++++++++++++++++++++++
          //F.5.14., F.5.15., dan F.5.16. Simulasi PERSONAL_KEUANGAN - Hitung anuitas bulanan untuk percentile 95, 50, dan 05 (hitung MP Bulanan bila dihitung menggunakan anuitas seumur hidup)
          $anuitas_personal_keuangan_p95_hitung = $saldo_personal_keuangan_akhir_p95_hitung / $harga_anuitas_personal_keuangan;
          $anuitas_personal_keuangan_p50_hitung = $saldo_personal_keuangan_akhir_p50_hitung / $harga_anuitas_personal_keuangan;
          $anuitas_personal_keuangan_p05_hitung = $saldo_personal_keuangan_akhir_p05_hitung / $harga_anuitas_personal_keuangan;
          
          //++++++++++++++++++++++++++++++++++++++++
          //F.5.17., F.5.18., dan F.5.19. Simulasi PERSONAL_KEUANGAN - Hitung kupon SBN/SBSN bulanan untuk percentile 95, 50, dan 05 (hitung MP Bulanan bila dihitung menggunakan kupon SBN/SBSN)
          $kupon_sbn_personal_keuangan_p95_hitung = ( $saldo_personal_keuangan_akhir_p95_hitung * $kupon_sbn_personal_keuangan *(1-$pajak_sbn_personal_keuangan))/12; //pembayaran bulanan dari kupon SBN/SBSN percentile 95
          $kupon_sbn_personal_keuangan_p50_hitung = ( $saldo_personal_keuangan_akhir_p50_hitung * $kupon_sbn_personal_keuangan *(1-$pajak_sbn_personal_keuangan))/12; //pembayaran bulanan dari kupon SBN/SBSN percentile 50
          $kupon_sbn_personal_keuangan_p05_hitung = ( $saldo_personal_keuangan_akhir_p05_hitung * $kupon_sbn_personal_keuangan *(1-$pajak_sbn_personal_keuangan))/12; //pembayaran bulanan dari kupon SBN/SBSN percentile 05

          //Output: Create $anuitas_personal_keuangan_p95[$i], $anuitas_personal_keuangan_p50[$i], $anuitas_personal_keuangan_p05[$i], $kupon_sbn_personal_keuangan_p95[$i], $kupon_sbn_personal_keuangan_p50[$i], $kupon_sbn_personal_keuangan_p05[$i]
          $anuitas_personal_keuangan_p95[$key] = $anuitas_personal_keuangan_p95_hitung;
          $anuitas_personal_keuangan_p50[$key] = $anuitas_personal_keuangan_p50_hitung;
          $anuitas_personal_keuangan_p05[$key] = $anuitas_personal_keuangan_p05_hitung;
          $kupon_sbn_personal_keuangan_p95[$key] = $kupon_sbn_personal_keuangan_p95_hitung;
          $kupon_sbn_personal_keuangan_p50[$key] = $kupon_sbn_personal_keuangan_p50_hitung;
          $kupon_sbn_personal_keuangan_p05[$key] = $kupon_sbn_personal_keuangan_p05_hitung;

          
          //++++++++++++++++++++++++++++++++++++++++
          //F.5.20., F.5.21., F.5.22., F.5.23., F.5.24., dan F.5.25., Hitung RR untuk anuitas dan kupon SBN/SBSN pada percentile 95, 50, dan 05
          if ($gaji[$key]>0){
            
            //untuk anuitas
            $rr_personal_keuangan_anuitas_p95_hitung = $anuitas_personal_keuangan_p95_hitung / $gaji[$key];
            $rr_personal_keuangan_anuitas_p50_hitung = $anuitas_personal_keuangan_p50_hitung / $gaji[$key];
            $rr_personal_keuangan_anuitas_p05_hitung = $anuitas_personal_keuangan_p05_hitung / $gaji[$key];
            
            //untuk kupon SBN/SBSN
            $rr_personal_keuangan_kupon_sbn_p95_hitung = $kupon_sbn_personal_keuangan_p95_hitung / $gaji[$key];
            $rr_personal_keuangan_kupon_sbn_p50_hitung = $kupon_sbn_personal_keuangan_p50_hitung / $gaji[$key];
            $rr_personal_keuangan_kupon_sbn_p05_hitung = $kupon_sbn_personal_keuangan_p05_hitung / $gaji[$key];
            
          } else{
            //untuk anuitas
            $rr_personal_keuangan_anuitas_p95_hitung = 0;
            $rr_personal_keuangan_anuitas_p50_hitung = 0;
            $rr_personal_keuangan_anuitas_p05_hitung = 0;
            
            //untuk kupon SBN/SBSN
            $rr_personal_keuangan_kupon_sbn_p95_hitung = 0;
            $rr_personal_keuangan_kupon_sbn_p50_hitung = 0;
            $rr_personal_keuangan_kupon_sbn_p05_hitung = 0;
          }

          //Output: Create $rr_personal_keuangan_anuitas_p95[$i], $rr_personal_keuangan_anuitas_p50[$i], $rr_personal_keuangan_anuitas_p05[$i], $rr_personal_keuangan_kupon_sbn_p95[$i], $rr_personal_keuangan_kupon_sbn_p50[$i], $rr_personal_keuangan_kupon_sbn_p05[$i]
          $rr_personal_keuangan_anuitas_p95[$key] = $rr_personal_keuangan_anuitas_p95_hitung;
          $rr_personal_keuangan_anuitas_p50[$key] = $rr_personal_keuangan_anuitas_p50_hitung;
          $rr_personal_keuangan_anuitas_p05[$key] = $rr_personal_keuangan_anuitas_p05_hitung;
          $rr_personal_keuangan_kupon_sbn_p95[$key] = $rr_personal_keuangan_kupon_sbn_p95_hitung;
          $rr_personal_keuangan_kupon_sbn_p50[$key] = $rr_personal_keuangan_kupon_sbn_p50_hitung;
          $rr_personal_keuangan_kupon_sbn_p05[$key] = $rr_personal_keuangan_kupon_sbn_p05_hitung;
        }
      }

      $this->uploadToDatabase("profil_personal_iuran", $id_user, $iuran_personal_keuangan);
      //echo json_encode($iuran_personal_keuangan, true);
      //die();
      
      $this->uploadToDatabase("profil_personal_investasi_p95", $id_user, $percentile_95_return_personal_keuangan_bulanan);
      $this->uploadToDatabase("profil_personal_investasi_p50", $id_user, $percentile_50_return_personal_keuangan_bulanan);
      $this->uploadToDatabase("profil_personal_investasi_p5", $id_user, $percentile_05_return_personal_keuangan_bulanan);
      
      $this->uploadToDatabase("profil_personal_p95_saldo_awal", $id_user, $saldo_personal_keuangan_awal_p95);
      $this->uploadToDatabase("profil_personal_p50_saldo_awal", $id_user, $saldo_personal_keuangan_awal_p50);
      $this->uploadToDatabase("profil_personal_p5_saldo_awal", $id_user, $saldo_personal_keuangan_awal_p05);
      
      $this->uploadToDatabase("profil_personal_p95_saldo_akhir", $id_user, $saldo_personal_keuangan_akhir_p95);
      $this->uploadToDatabase("profil_personal_p50_saldo_akhir", $id_user, $saldo_personal_keuangan_akhir_p50);
      $this->uploadToDatabase("profil_personal_p5_saldo_akhir", $id_user, $saldo_personal_keuangan_akhir_p05);
      
      $this->uploadToDatabase("profil_personal_p95_pengembangan", $id_user, $pengembangan_personal_keuangan_p95);
      $this->uploadToDatabase("profil_personal_p50_pengembangan", $id_user, $pengembangan_personal_keuangan_p50);
      $this->uploadToDatabase("profil_personal_p5_pengembangan", $id_user, $pengembangan_personal_keuangan_p05);
      
      $this->uploadToDatabase("profil_personal_anuitas_p95", $id_user, $anuitas_personal_keuangan_p95);
      $this->uploadToDatabase("profil_personal_anuitas_p50", $id_user, $anuitas_personal_keuangan_p50);
      $this->uploadToDatabase("profil_personal_anuitas_p5", $id_user, $anuitas_personal_keuangan_p05);
      
      $this->uploadToDatabase("profil_personal_bunga_deposito_p95", $id_user, $kupon_sbn_personal_keuangan_p95);
      $this->uploadToDatabase("profil_personal_bunga_deposito_p50", $id_user, $kupon_sbn_personal_keuangan_p50);
      $this->uploadToDatabase("profil_personal_bunga_deposito_p5", $id_user, $kupon_sbn_personal_keuangan_p05);
      
      $this->uploadToDatabase("profil_personal_rr_anuitas_p95", $id_user, $rr_personal_keuangan_anuitas_p95);
      $this->uploadToDatabase("profil_personal_rr_anuitas_p50", $id_user, $rr_personal_keuangan_anuitas_p50);
      $this->uploadToDatabase("profil_personal_rr_anuitas_p5", $id_user, $rr_personal_keuangan_anuitas_p05);
      
      $this->uploadToDatabase("profil_personal_rr_bunga_deposito_p95", $id_user, $rr_personal_keuangan_kupon_sbn_p95);
      $this->uploadToDatabase("profil_personal_rr_bunga_deposito_p50", $id_user, $rr_personal_keuangan_kupon_sbn_p50);
      $this->uploadToDatabase("profil_personal_rr_bunga_deposito_p5", $id_user, $rr_personal_keuangan_kupon_sbn_p05);

      return array(
        "iuran_personal_keuangan" => $iuran_personal_keuangan,
        "percentile_95_return_personal_keuangan_bulanan" => $percentile_95_return_personal_keuangan_bulanan,
        "percentile_50_return_personal_keuangan_bulanan" => $percentile_50_return_personal_keuangan_bulanan,
        "percentile_05_return_personal_keuangan_bulanan" => $percentile_05_return_personal_keuangan_bulanan,
        "saldo_personal_keuangan_awal_p95" => $saldo_personal_keuangan_awal_p95,
        "pengembangan_personal_keuangan_p95" => $pengembangan_personal_keuangan_p95,
        "saldo_personal_keuangan_akhir_p95" => $saldo_personal_keuangan_akhir_p95, // Data FE Diagram
        "saldo_personal_keuangan_awal_p50" => $saldo_personal_keuangan_awal_p50,
        "pengembangan_personal_keuangan_p50" => $pengembangan_personal_keuangan_p50,
        "saldo_personal_keuangan_akhir_p50" => $saldo_personal_keuangan_akhir_p50, // Data FE Diagram
        "saldo_personal_keuangan_awal_p05" => $saldo_personal_keuangan_awal_p05,
        "pengembangan_personal_keuangan_p05" => $pengembangan_personal_keuangan_p05,
        "saldo_personal_keuangan_akhir_p05" => $saldo_personal_keuangan_akhir_p05, // Data FE Diagram
        "previous_saldo_personal_keuangan_akhir_p95" => $previous_saldo_personal_keuangan_akhir_p95,
        "previous_saldo_personal_keuangan_akhir_p50" => $previous_saldo_personal_keuangan_akhir_p50,
        "previous_saldo_personal_keuangan_akhir_p05" => $previous_saldo_personal_keuangan_akhir_p05,
        "anuitas_personal_keuangan_p95" => $anuitas_personal_keuangan_p95,
        "anuitas_personal_keuangan_p50" => $anuitas_personal_keuangan_p50,
        "anuitas_personal_keuangan_p05" => $anuitas_personal_keuangan_p05,
        "kupon_sbn_personal_keuangan_p95" => $kupon_sbn_personal_keuangan_p95,
        "kupon_sbn_personal_keuangan_p50" => $kupon_sbn_personal_keuangan_p50,
        "kupon_sbn_personal_keuangan_p05" => $kupon_sbn_personal_keuangan_p05,
        "rr_personal_keuangan_anuitas_p95" => $rr_personal_keuangan_anuitas_p95,
        "rr_personal_keuangan_anuitas_p50" => $rr_personal_keuangan_anuitas_p50,
        "rr_personal_keuangan_anuitas_p05" => $rr_personal_keuangan_anuitas_p05,
        "rr_personal_keuangan_kupon_sbn_p95" => $rr_personal_keuangan_kupon_sbn_p95,
        "rr_personal_keuangan_kupon_sbn_p50" => $rr_personal_keuangan_kupon_sbn_p50,
        "rr_personal_keuangan_kupon_sbn_p05" => $rr_personal_keuangan_kupon_sbn_p05,
      );
    }

    public function indikator_dashboard($data_user, $id_user, $flag_pensiun, $sisa_kerja_tahun, $sisa_kerja_bulan, $return_simulasi_ppip, $return_simulasi_personal_properti, $return_simulasi_personal_keuangan, $return_simulasi_ppmp){
      // Sheet 1
      //Input: Read flag pensiun
      $counter_pensiun=""; //counter posisi pensiun
      $previous_flag_pensiun = null;
      $tahun_pensiun = null;
      
      for($year=2023; $year<=2100; $year++){
        for($month=1; $month<=12; $month++){
          $key = $year . "_" . $month;
          if ($year==2023 && $month==1){
            if ($flag_pensiun[$key]==1){
              $counter_pensiun = $key;// pada saat bulan ini sudah pensiun. jadi saldo yang ditampilkan adalah saldo awal
                $tahun_pensiun = $year;
            }
          } else {
            if ($flag_pensiun[$key]==1 && $previous_flag_pensiun==0){
              $counter_pensiun = $key; // pada saat bulan ini sudah pensiun. jadi saldo yang ditampilkan adalah saldo akhir untuk bulan sebelumnya.
               $tahun_pensiun = $year; 
            }
          }
          $previous_flag_pensiun = $flag_pensiun[$key];
        }
      }

      // Note: Bungkus loop year month untuk total rr

      $parts_counter_pensiun = explode("_", $counter_pensiun);
      $counter_pensiun_year = intval($parts_counter_pensiun[0]);
      $counter_pensiun_month = intval($parts_counter_pensiun[1]);
      // Mengurangi satu bulan
      if ($counter_pensiun_month == 1) {
          $counter_pensiun_year -= 1;
          $counter_pensiun_month = 12;
      } else {
          $counter_pensiun_month -= 1;
      }
      $counter_pensiun_minus_one_month = sprintf("%d_%d", $counter_pensiun_year, $counter_pensiun_month);
      //echo json_encode($counter_pensiun_minus_one_month, true);
      //die();

      //----------------------------------------------------------------------------
      //G.2. Hitung indikator dashboard - posisi saat pensiun
      $rr_ppip_anuitas_p05 = $return_simulasi_ppip["rr_ppip_anuitas_p05"];
      $rr_ppip_anuitas_p50 = $return_simulasi_ppip["rr_ppip_anuitas_p50"];
      $rr_ppip_anuitas_p95 = $return_simulasi_ppip["rr_ppip_anuitas_p95"];

      $rr_ppip_kupon_sbn_p05 = $return_simulasi_ppip["rr_ppip_kupon_sbn_p05"];
      $rr_ppip_kupon_sbn_p50 = $return_simulasi_ppip["rr_ppip_kupon_sbn_p50"];
      $rr_ppip_kupon_sbn_p95 = $return_simulasi_ppip["rr_ppip_kupon_sbn_p95"];
      
      $rr_personal_keuangan_anuitas_p05 = $return_simulasi_personal_keuangan["rr_personal_keuangan_anuitas_p05"];
      $rr_personal_keuangan_anuitas_p50 = $return_simulasi_personal_keuangan["rr_personal_keuangan_anuitas_p50"];
      $rr_personal_keuangan_anuitas_p95 = $return_simulasi_personal_keuangan["rr_personal_keuangan_anuitas_p95"];

      $rr_personal_keuangan_kupon_sbn_p05 = $return_simulasi_personal_keuangan["rr_personal_keuangan_kupon_sbn_p05"];
      $rr_personal_keuangan_kupon_sbn_p50 = $return_simulasi_personal_keuangan["rr_personal_keuangan_kupon_sbn_p50"];
      $rr_personal_keuangan_kupon_sbn_p95 = $return_simulasi_personal_keuangan["rr_personal_keuangan_kupon_sbn_p95"];

      $rr_personal_properti = $return_simulasi_personal_properti["rr_personal_properti"];
      //++++++++++++++++++++++++++++++++
      //G.2.1. RR pada dashboard
      //pembayaran PPIP jika 1=anuitas; 2=kupon SBN/SBSN
      $setting_treatment_user = DB::table('setting_treatment_pembayaran_setelah_pensiun')
      ->where('id_user', $id_user)
      ->where('flag', 1)
      ->select('*')->get()[0];
      $pembayaran_ppip = ($setting_treatment_user->ppip === 'Beli Anuitas') ? 1 : 2;//Read pilihan pembayaran PPIP (pembayaran PPIP jika 1=anuitas; 2=kupon SBN/SBSN)
      if($pembayaran_ppip==1){
        $dashboard_rr_ppip_min = $rr_ppip_anuitas_p05[$counter_pensiun_minus_one_month];
        $dashboard_rr_ppip_med = $rr_ppip_anuitas_p50[$counter_pensiun_minus_one_month];
        $dashboard_rr_ppip_max = $rr_ppip_anuitas_p95[$counter_pensiun_minus_one_month];  
      } else {
        $dashboard_rr_ppip_min = $rr_ppip_kupon_sbn_p05[$counter_pensiun_minus_one_month];
        $dashboard_rr_ppip_med = $rr_ppip_kupon_sbn_p50[$counter_pensiun_minus_one_month];
        $dashboard_rr_ppip_max = $rr_ppip_kupon_sbn_p95[$counter_pensiun_minus_one_month];
      }

      //pembayaran personal keuangan jika 1=anuitas; 2=kupon SBN/SBSN
      $setting_treatment_user = DB::table('setting_treatment_pembayaran_setelah_pensiun')
      ->where('id_user', $id_user)
      ->where('flag', 1)
      ->select('*')->get()[0];

      $pembayaran_personal_keuangan=($setting_treatment_user->personal_pasar_keuangan === 'Beli Anuitas') ? 1 : 2;//Read pilihan pembayaran personal_keuangan (pembayaran personal_keuangan jika 1=anuitas; 2=kupon SBN/SBSN)
      if($pembayaran_personal_keuangan==1){
        $dashboard_rr_personal_keuangan_min = $rr_personal_keuangan_anuitas_p05[$counter_pensiun_minus_one_month];
        $dashboard_rr_personal_keuangan_med = $rr_personal_keuangan_anuitas_p50[$counter_pensiun_minus_one_month];
        $dashboard_rr_personal_keuangan_max = $rr_personal_keuangan_anuitas_p95[$counter_pensiun_minus_one_month];
      } else { 
        $dashboard_rr_personal_keuangan_min = $rr_personal_keuangan_kupon_sbn_p05[$counter_pensiun_minus_one_month];
        $dashboard_rr_personal_keuangan_med = $rr_personal_keuangan_kupon_sbn_p50[$counter_pensiun_minus_one_month];
        $dashboard_rr_personal_keuangan_max = $rr_personal_keuangan_kupon_sbn_p95[$counter_pensiun_minus_one_month];
      }
      $dashboard_rr_personal_properti = $rr_personal_properti[$counter_pensiun_minus_one_month];
      
      //echo json_encode($dashboard_rr_personal_keuangan_min, true);
      //die();

      //total rr
      $status_mp = $return_simulasi_ppmp['status_mp'];
      $rr_ppmp = $return_simulasi_ppmp['rr_ppmp'];
      
      //echo json_encode($rr_ppmp, true);
      //die();
        
      //$status_mp=1 untuk hybrid ppmp ppip dan $status_mp=2 untuk ppip murni
      if ($status_mp[$tahun_pensiun]==1){
        $dashboard_rr_ppmp = $rr_ppmp[$counter_pensiun_minus_one_month];
        
        $dashboard_rr_total_min = $dashboard_rr_ppmp +  $dashboard_rr_ppip_min + $dashboard_rr_personal_keuangan_min + $dashboard_rr_personal_properti;
        $dashboard_rr_total_med = $dashboard_rr_ppmp +  $dashboard_rr_ppip_med + $dashboard_rr_personal_keuangan_med + $dashboard_rr_personal_properti;
        $dashboard_rr_total_max = $dashboard_rr_ppmp +  $dashboard_rr_ppip_max + $dashboard_rr_personal_keuangan_max + $dashboard_rr_personal_properti;

      } else {
        $dashboard_rr_ppmp = null;

        $dashboard_rr_total_min = $dashboard_rr_ppip_min + $dashboard_rr_personal_keuangan_min + $dashboard_rr_personal_properti;
        $dashboard_rr_total_med = $dashboard_rr_ppip_med + $dashboard_rr_personal_keuangan_med + $dashboard_rr_personal_properti;
        $dashboard_rr_total_max = $dashboard_rr_ppip_max + $dashboard_rr_personal_keuangan_max + $dashboard_rr_personal_properti;
      }

      //++++++++++++++++++++++++++++++++
      //G.2.2. Penghasilan Bulanan pada dashboard
      $anuitas_ppip_p05 = $return_simulasi_ppip["anuitas_ppip_p05"];
      $anuitas_ppip_p50 = $return_simulasi_ppip["anuitas_ppip_p50"];
      $anuitas_ppip_p95 = $return_simulasi_ppip["anuitas_ppip_p95"];
      
      $kupon_sbn_ppip_p05 = $return_simulasi_ppip["kupon_sbn_ppip_p05"];
      $kupon_sbn_ppip_p50 = $return_simulasi_ppip["kupon_sbn_ppip_p50"];
      $kupon_sbn_ppip_p95 = $return_simulasi_ppip["kupon_sbn_ppip_p95"];

      $anuitas_personal_keuangan_p05 = $return_simulasi_personal_keuangan["anuitas_personal_keuangan_p05"];
      $anuitas_personal_keuangan_p50 = $return_simulasi_personal_keuangan["anuitas_personal_keuangan_p50"];
      $anuitas_personal_keuangan_p95 = $return_simulasi_personal_keuangan["anuitas_personal_keuangan_p95"];

      $kupon_sbn_personal_keuangan_p05 = $return_simulasi_personal_keuangan["kupon_sbn_personal_keuangan_p05"];
      $kupon_sbn_personal_keuangan_p50 = $return_simulasi_personal_keuangan["kupon_sbn_personal_keuangan_p50"];
      $kupon_sbn_personal_keuangan_p95 = $return_simulasi_personal_keuangan["kupon_sbn_personal_keuangan_p95"];

      $sewa_properti = $return_simulasi_personal_properti["sewa_properti"];
      //pembayaran PPIP jika 1=anuitas; 2=kupon SBN/SBSN
      if($pembayaran_ppip==1){
        $dashboard_penghasilan_bulanan_ppip_min = $anuitas_ppip_p05[$counter_pensiun_minus_one_month];
        $dashboard_penghasilan_bulanan_ppip_med = $anuitas_ppip_p50[$counter_pensiun_minus_one_month];
        $dashboard_penghasilan_bulanan_ppip_max = $anuitas_ppip_p95[$counter_pensiun_minus_one_month];
      } else {
        $dashboard_penghasilan_bulanan_ppip_min = $kupon_sbn_ppip_p05[$counter_pensiun_minus_one_month];
        $dashboard_penghasilan_bulanan_ppip_med = $kupon_sbn_ppip_p50[$counter_pensiun_minus_one_month];
        $dashboard_penghasilan_bulanan_ppip_max = $kupon_sbn_ppip_p95[$counter_pensiun_minus_one_month];
      }

      //pembayaran personal keuangan jika 1=anuitas; 2=kupon SBN/SBSN
      if($pembayaran_personal_keuangan==1){
        $dashboard_penghasilan_bulanan_personal_keuangan_min = $anuitas_personal_keuangan_p05[$counter_pensiun_minus_one_month];
        $dashboard_penghasilan_bulanan_personal_keuangan_med = $anuitas_personal_keuangan_p50[$counter_pensiun_minus_one_month];
        $dashboard_penghasilan_bulanan_personal_keuangan_max = $anuitas_personal_keuangan_p95[$counter_pensiun_minus_one_month];
      } else { 
        $dashboard_penghasilan_bulanan_personal_keuangan_min = $kupon_sbn_personal_keuangan_p05[$counter_pensiun_minus_one_month];
        $dashboard_penghasilan_bulanan_personal_keuangan_med = $kupon_sbn_personal_keuangan_p50[$counter_pensiun_minus_one_month];
        $dashboard_penghasilan_bulanan_personal_keuangan_max = $kupon_sbn_personal_keuangan_p95[$counter_pensiun_minus_one_month];
      }
      $dashboard_penghasilan_bulanan_personal_properti = $sewa_properti[$counter_pensiun_minus_one_month] / 12;

      //total penghasilan bulanan
      $jumlah_ppmp = $return_simulasi_ppmp['jumlah_ppmp'];
      //$status_mp=1 untuk hybrid ppmp ppip dan $status_mp=2 untuk ppip murni
      if ($status_mp[$tahun_pensiun]==1){
        $dashboard_penghasilan_bulanan_ppmp = $jumlah_ppmp[$counter_pensiun_minus_one_month];
        
        $dashboard_penghasilan_bulanan_total_min = $dashboard_penghasilan_bulanan_ppmp +  $dashboard_penghasilan_bulanan_ppip_min + $dashboard_penghasilan_bulanan_personal_keuangan_min + $dashboard_penghasilan_bulanan_personal_properti;
        $dashboard_penghasilan_bulanan_total_med = $dashboard_penghasilan_bulanan_ppmp +  $dashboard_penghasilan_bulanan_ppip_med + $dashboard_penghasilan_bulanan_personal_keuangan_med + $dashboard_penghasilan_bulanan_personal_properti;
        $dashboard_penghasilan_bulanan_total_max = $dashboard_penghasilan_bulanan_ppmp +  $dashboard_penghasilan_bulanan_ppip_max + $dashboard_penghasilan_bulanan_personal_keuangan_max + $dashboard_penghasilan_bulanan_personal_properti;
      } else {
        $dashboard_penghasilan_bulanan_ppmp = null;
        
        $dashboard_penghasilan_bulanan_total_min = $dashboard_penghasilan_bulanan_ppip_min + $dashboard_penghasilan_bulanan_personal_keuangan_min + $dashboard_penghasilan_bulanan_personal_properti;
        $dashboard_penghasilan_bulanan_total_med = $dashboard_penghasilan_bulanan_ppip_med + $dashboard_penghasilan_bulanan_personal_keuangan_med + $dashboard_penghasilan_bulanan_personal_properti;
        $dashboard_penghasilan_bulanan_total_max = $dashboard_penghasilan_bulanan_ppip_max + $dashboard_penghasilan_bulanan_personal_keuangan_max + $dashboard_penghasilan_bulanan_personal_properti;
      }

      // +++++++++++++++++++++++++++++++
      //G.2.3. present value Penghasilan Bulanan pada dashboard
      //Input: Read sisa masa kerja saat membuka
      $tahun_ini=date('Y');//Read current date untuk tahun
      $bulan_ini=date('n');////Read current date untuk bulan
      $tahun_bulan_ini = $tahun_ini."_".$bulan_ini;
      $inflasi=0.04;//Read asumsi inflasi yang di admin

      $tahun_sisa_kerja = $sisa_kerja_tahun[$tahun_bulan_ini];//Read sisa masa kerja tahun untuk current date
      $bulan_sisa_kerja = $sisa_kerja_bulan[$tahun_bulan_ini];//Read sisa masa kerja bulan untuk current date
      
      //echo json_encode($bulan_sisa_kerja, true);
      //die();

      //$dashboard_penghasilan_bulanan_ppip_min_pv = $dashboard_penghasilan_bulanan_ppip_min / ((1+$inflasi)^($tahun_sisa_kerja+($bulan_sisa_kerja/12)));
      $dashboard_penghasilan_bulanan_ppip_min_pv = $dashboard_penghasilan_bulanan_ppip_min / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));
      $dashboard_penghasilan_bulanan_ppip_med_pv = $dashboard_penghasilan_bulanan_ppip_med / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));
      $dashboard_penghasilan_bulanan_ppip_max_pv = $dashboard_penghasilan_bulanan_ppip_max / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));
      
      //echo json_encode($dashboard_penghasilan_bulanan_ppip_min, true);
      //echo json_encode($dashboard_penghasilan_bulanan_ppip_min_pv, true);
      //die();

      $dashboard_penghasilan_bulanan_personal_keuangan_min_pv = $dashboard_penghasilan_bulanan_personal_keuangan_min / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));
      $dashboard_penghasilan_bulanan_personal_keuangan_med_pv = $dashboard_penghasilan_bulanan_personal_keuangan_med / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));
      $dashboard_penghasilan_bulanan_personal_keuangan_max_pv = $dashboard_penghasilan_bulanan_personal_keuangan_max / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));

      $dashboard_penghasilan_bulanan_personal_properti_pv = $dashboard_penghasilan_bulanan_personal_properti / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));

      //total penghasilan bulanan
      //$status_mp=1 untuk hybrid ppmp ppip dan $status_mp=2 untuk ppip murni
      if ($status_mp[$tahun_pensiun]==1){
        $dashboard_penghasilan_bulanan_ppmp_pv = $dashboard_penghasilan_bulanan_ppmp / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));
        
        $dashboard_penghasilan_bulanan_total_min_pv = $dashboard_penghasilan_bulanan_ppmp_pv +  $dashboard_penghasilan_bulanan_ppip_min_pv + $dashboard_penghasilan_bulanan_personal_keuangan_min_pv + $dashboard_penghasilan_bulanan_personal_properti_pv;
        $dashboard_penghasilan_bulanan_total_med_pv = $dashboard_penghasilan_bulanan_ppmp_pv +  $dashboard_penghasilan_bulanan_ppip_med_pv + $dashboard_penghasilan_bulanan_personal_keuangan_med_pv + $dashboard_penghasilan_bulanan_personal_properti_pv;
        $dashboard_penghasilan_bulanan_total_max_pv = $dashboard_penghasilan_bulanan_ppmp_pv +  $dashboard_penghasilan_bulanan_ppip_max_pv + $dashboard_penghasilan_bulanan_personal_keuangan_max_pv + $dashboard_penghasilan_bulanan_personal_properti_pv;

      } else {
        $dashboard_penghasilan_bulanan_ppmp_pv = null;
        
        $dashboard_penghasilan_bulanan_total_min_pv = $dashboard_penghasilan_bulanan_ppip_min_pv + $dashboard_penghasilan_bulanan_personal_keuangan_min_pv + $dashboard_penghasilan_bulanan_personal_properti_pv;
        $dashboard_penghasilan_bulanan_total_med_pv = $dashboard_penghasilan_bulanan_ppip_med_pv + $dashboard_penghasilan_bulanan_personal_keuangan_med_pv + $dashboard_penghasilan_bulanan_personal_properti_pv;
        $dashboard_penghasilan_bulanan_total_max_pv = $dashboard_penghasilan_bulanan_ppip_max_pv + $dashboard_penghasilan_bulanan_personal_keuangan_max_pv + $dashboard_penghasilan_bulanan_personal_properti_pv;

      }
        
        //++++++++++++++++++++++++++++++++
        //G.2.4. kekayaan pada dashboard
        
        $saldo_ppip_p05 = $return_simulasi_ppip["saldo_ppip_akhir_p05"];
        $saldo_ppip_p50 = $return_simulasi_ppip["saldo_ppip_akhir_p50"];
        $saldo_ppip_p95 = $return_simulasi_ppip["saldo_ppip_akhir_p95"];
            
        $saldo_personal_keuangan_p05 = $return_simulasi_personal_keuangan["saldo_personal_keuangan_akhir_p05"];
        $saldo_personal_keuangan_p50 = $return_simulasi_personal_keuangan["saldo_personal_keuangan_akhir_p50"];
        $saldo_personal_keuangan_p95 = $return_simulasi_personal_keuangan["saldo_personal_keuangan_akhir_p95"];
        
        $harga_properti = $return_simulasi_personal_properti["harga_properti"];

        //mengambil angka waktu pensiun
        $dashboard_kekayaan_ppip_min = $saldo_ppip_p05[$counter_pensiun_minus_one_month];
        $dashboard_kekayaan_ppip_med = $saldo_ppip_p50[$counter_pensiun_minus_one_month];
        $dashboard_kekayaan_ppip_max = $saldo_ppip_p95[$counter_pensiun_minus_one_month];

        $dashboard_kekayaan_personal_keuangan_min = $saldo_personal_keuangan_p05[$counter_pensiun_minus_one_month];
        $dashboard_kekayaan_personal_keuangan_med = $saldo_personal_keuangan_p50[$counter_pensiun_minus_one_month];
        $dashboard_kekayaan_personal_keuangan_max = $saldo_personal_keuangan_p95[$counter_pensiun_minus_one_month];

        $dashboard_kekayaan_personal_properti = $harga_properti[$counter_pensiun_minus_one_month];
        
        $dashboard_kekayaan_ppmp = null;
         
       
        //total kekayaan
        //ppmp tidak dihitung kekayaan karena merupakan manfaat pasti yang diberikan secara bulanan
        
         $dashboard_kekayaan_total_min = $dashboard_kekayaan_ppip_min + $dashboard_kekayaan_personal_keuangan_min + $dashboard_kekayaan_personal_properti;
         $dashboard_kekayaan_total_med = $dashboard_kekayaan_ppip_med + $dashboard_kekayaan_personal_keuangan_med + $dashboard_kekayaan_personal_properti;
         $dashboard_kekayaan_total_max = $dashboard_kekayaan_ppip_max + $dashboard_kekayaan_personal_keuangan_max + $dashboard_kekayaan_personal_properti;
        
        //++++++++++++++++++++++++++++++++
        //G.2.5. preset value kekayaan pada dashboard
        //Input: Read sisa masa kerja saat membuka
        $tahun_ini=date('Y');//Read current date untuk tahun
        $bulan_ini=date('n');////Read current date untuk bulan
        $tahun_bulan_ini = $tahun_ini."_".$bulan_ini;
        $inflasi=0.04;//Read asumsi inflasi yang di admin

        $tahun_sisa_kerja = $sisa_kerja_tahun[$tahun_bulan_ini];//Read sisa masa kerja tahun untuk current date
        $bulan_sisa_kerja = $sisa_kerja_bulan[$tahun_bulan_ini];//Read sisa masa kerja bulan untuk current date
              
        $dashboard_kekayaan_ppip_min_pv = $dashboard_kekayaan_ppip_min / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));
        $dashboard_kekayaan_ppip_med_pv = $dashboard_kekayaan_ppip_med / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));
        $dashboard_kekayaan_ppip_max_pv = $dashboard_kekayaan_ppip_max / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));

        $dashboard_kekayaan_personal_keuangan_min_pv = $dashboard_kekayaan_personal_keuangan_min / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));
        $dashboard_kekayaan_personal_keuangan_med_pv = $dashboard_kekayaan_personal_keuangan_med / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));
        $dashboard_kekayaan_personal_keuangan_max_pv = $dashboard_kekayaan_personal_keuangan_max / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));

        $dashboard_kekayaan_personal_properti_pv = $dashboard_kekayaan_personal_properti / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));

        $dashboard_kekayaan_ppmp_pv = null;

        $dashboard_kekayaan_total_min_pv = $dashboard_kekayaan_total_min / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));
        $dashboard_kekayaan_total_med_pv = $dashboard_kekayaan_total_med / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));
        $dashboard_kekayaan_total_max_pv = $dashboard_kekayaan_total_max / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));
        
      return array(
          "pensiun" => $counter_pensiun,
          //"status_mp" => $status_mp,
          
          //RR
          "rr_ppip_minimal" => $dashboard_rr_ppip_min,
          "rr_ppip_median" => $dashboard_rr_ppip_med,
          "rr_ppip_maksimal" =>  $dashboard_rr_ppip_max,
            
          "rr_ppmp" => $dashboard_rr_ppmp,
          
          "rr_personal_properti" => $dashboard_rr_personal_properti,
          
          "rr_personal_keuangan_minimal" => $dashboard_rr_personal_keuangan_min,
          "rr_personal_keuangan_median" => $dashboard_rr_personal_keuangan_med,
          "rr_personal_keuangan_maksimal" =>  $dashboard_rr_personal_keuangan_max,
              
          "rr_total_minimal" => $dashboard_rr_total_min,
          "rr_total_median" => $dashboard_rr_total_med,
          "rr_total_maksimal" => $dashboard_rr_total_max,
            
          //Penghasilan Bulanan
          "penghasilan_ppip_minimal" => $dashboard_penghasilan_bulanan_ppip_min,
          "penghasilan_ppip_median" => $dashboard_penghasilan_bulanan_ppip_med,
          "penghasilan_ppip_maksimal" =>  $dashboard_penghasilan_bulanan_ppip_max,
                      
          "penghasilan_ppmp" => $dashboard_penghasilan_bulanan_ppmp,
          
          "penghasilan_personal_properti" => $dashboard_penghasilan_bulanan_personal_properti,
          
          "penghasilan_personal_keuangan_minimal" => $dashboard_penghasilan_bulanan_personal_keuangan_min,
          "penghasilan_personal_keuangan_median" => $dashboard_penghasilan_bulanan_personal_keuangan_med,
          "penghasilan_personal_keuangan_maksimal" =>  $dashboard_penghasilan_bulanan_personal_keuangan_max,
                      
          "penghasilan_total_minimal" => $dashboard_penghasilan_bulanan_total_min,
          "penghasilan_total_median" => $dashboard_penghasilan_bulanan_total_med,
          "penghasilan_total_maksimal" => $dashboard_penghasilan_bulanan_total_max,
          
          //Penghasilan Bulanan - present value
          "pv_penghasilan_ppip_minimal" => $dashboard_penghasilan_bulanan_ppip_min_pv,
          "pv_penghasilan_ppip_median" => $dashboard_penghasilan_bulanan_ppip_med_pv,
          "pv_penghasilan_ppip_maksimal" =>  $dashboard_penghasilan_bulanan_ppip_max_pv,
                      
          "pv_penghasilan_ppmp" => $dashboard_penghasilan_bulanan_ppmp_pv,
          
          "pv_penghasilan_personal_properti" => $dashboard_penghasilan_bulanan_personal_properti_pv,
          
          "pv_penghasilan_personal_keuangan_minimal" => $dashboard_penghasilan_bulanan_personal_keuangan_min_pv,
          "pv_penghasilan_personal_keuangan_median" => $dashboard_penghasilan_bulanan_personal_keuangan_med_pv,
          "pv_penghasilan_personal_keuangan_maksimal" =>  $dashboard_penghasilan_bulanan_personal_keuangan_max_pv,
                      
          "pv_penghasilan_total_minimal" => $dashboard_penghasilan_bulanan_total_min_pv,
          "pv_penghasilan_total_median" => $dashboard_penghasilan_bulanan_total_med_pv,
          "pv_penghasilan_total_maksimal" => $dashboard_penghasilan_bulanan_total_max_pv,
          
              //kekayaan
              //Penghasilan Bulanan
          "kekayaan_ppip_minimal" => $dashboard_kekayaan_ppip_min,
          "kekayaan_ppip_median" => $dashboard_kekayaan_ppip_med,
          "kekayaan_ppip_maksimal" =>  $dashboard_kekayaan_ppip_max,
                      
          "kekayaan_ppmp" => $dashboard_kekayaan_ppmp,
          
          "kekayaan_personal_properti" => $dashboard_kekayaan_personal_properti,
          
          "kekayaan_personal_keuangan_minimal" => $dashboard_kekayaan_personal_keuangan_min,
          "kekayaan_personal_keuangan_median" => $dashboard_kekayaan_personal_keuangan_med,
          "kekayaan_personal_keuangan_maksimal" =>  $dashboard_kekayaan_personal_keuangan_max,
                      
          "kekayaan_total_minimal" => $dashboard_kekayaan_total_min,
          "kekayaan_total_median" => $dashboard_kekayaan_total_med,
          "kekayaan_total_maksimal" => $dashboard_kekayaan_total_max,
          
          //kekayaan - present value
          "pv_kekayaan_ppip_minimal" => $dashboard_kekayaan_ppip_min_pv,
          "pv_kekayaan_ppip_median" => $dashboard_kekayaan_ppip_med_pv,
          "pv_kekayaan_ppip_maksimal" =>  $dashboard_kekayaan_ppip_max_pv,
                      
          "pv_kekayaan_ppmp" => $dashboard_kekayaan_ppmp_pv,
          
          "pv_kekayaan_personal_properti" => $dashboard_kekayaan_personal_properti_pv,
          
          "pv_kekayaan_personal_keuangan_minimal" => $dashboard_kekayaan_personal_keuangan_min_pv,
          "pv_kekayaan_personal_keuangan_median" => $dashboard_kekayaan_personal_keuangan_med_pv,
          "pv_kekayaan_personal_keuangan_maksimal" =>  $dashboard_kekayaan_personal_keuangan_max_pv,
                      
          "pv_kekayaan_total_minimal" => $dashboard_kekayaan_total_min_pv,
          "pv_kekayaan_total_median" => $dashboard_kekayaan_total_med_pv,
          "pv_kekayaan_total_maksimal" => $dashboard_kekayaan_total_max_pv,
      );
    }
    
    public function simulasi_personal_keuangan_solver($data_user, $id_user, $return_simulasi_gaji_phdp, $flag_pensiun, $montecarlo_personal_keuangan, $return_simulasi_ppmp, $iuran_hitung){
      // Sheet 4 Baris 73
      //Input: variabel $gaji{$i] yang ada di memory serta flag pensiun, Read tambahan iuran personal_keuangan, Read Saldo PERSONAL_KEUANGAN
      $gaji = $return_simulasi_gaji_phdp['gaji'];
      $counter_saldo_personal_keuangan = explode("_", $return_simulasi_gaji_phdp['counter_saldo_personal_keuangan']);
      $counter_saldo_personal_keuangan_year = $counter_saldo_personal_keuangan[0]; 
      $counter_saldo_personal_keuangan_month = $counter_saldo_personal_keuangan[1];

      //F.5.1. Simulasi PERSONAL_KEUANGAN - Hitung iuran
      $setting_nilai_asumsi_user = DB::table('nilai_asumsi_user')
            ->where('id_user', $id_user)
            ->where('flag', 1)
            ->select('*')->get()[0];
      //$persentase_iuran_personal_keuangan=$setting_nilai_asumsi_user->jumlah_pembayaran_iuran_personal; //Read besar iuran personal keuangan di profil user
      $persentase_iuran_personal_keuangan = $iuran_hitung*100;
      //echo json_encode($persentase_iuran_personal_keuangan, true);
      //die();
      $saldo_personal_keuangan_input=$data_user->jumlah_investasi_keuangan; // Read saldo personal_keuangan yang diinput (saldo diasumsikan diinput di awal bulan)

      //nilai default pilihan pembayaran personal keuangan
      //Input: Read pilihan pembayaran personal keuangan, Read kupon SBN/SBSN dan beserta pajak dari profil user, Read Harga anuitas dari profil user
      //pembayaran personal_keuangan jika 1=anuitas; 2=kupon SBN/SBSN

      $setting_treatment_user = DB::table('setting_treatment_pembayaran_setelah_pensiun')
            ->where('id_user', $id_user)
            ->where('flag', 1)
            ->select('*')->get()[0];

      $pembayaran_personal_keuangan=($setting_treatment_user->personal_pasar_keuangan === 'Beli Anuitas') ? 1 : 2;//Read pilihan pembayaran personal_keuangan (pembayaran personal_keuangan jika 1=anuitas; 2=kupon SBN/SBSN)
      if($pembayaran_personal_keuangan==1){
        $harga_anuitas_personal_keuangan =$setting_treatment_user->harga_anuitas_personal_pasar_keuangan;//Read harga anuitas masing-masing user
        
        $kupon_sbn_personal_keuangan =0.06125;//default
        $pajak_sbn_personal_keuangan =0.01;//default
      } else {
        $harga_anuitas_personal_keuangan =136;//default
        
        $kupon_sbn_personal_keuangan =$setting_treatment_user->bunga_personal_pasar_keuangan;//Read kupon SBN/SBSN dari profil user
        $pajak_sbn_personal_keuangan =$setting_treatment_user->pajak_personal_pasar_keuangan;//Read pajak SBN/SBSN dari profil user
        
        $kupon_sbn_personal_keuangan =$kupon_sbn_personal_keuangan / 100;
        $pajak_sbn_personal_keuangan =$pajak_sbn_personal_keuangan / 100;
      }
     //echo json_encode($kupon_sbn_personal_keuangan, true);
       //die();
      $percentile_95_return_monthly_personal = $montecarlo_personal_keuangan["percentile_95_return_monthly_personal"];
      $percentile_50_return_monthly_personal = $montecarlo_personal_keuangan["percentile_50_return_monthly_personal"];
      $percentile_05_return_monthly_personal = $montecarlo_personal_keuangan["percentile_05_return_monthly_personal"];

      $iuran_personal_keuangan = array();
      $percentile_95_return_personal_keuangan_bulanan = array();
      $percentile_50_return_personal_keuangan_bulanan = array();
      $percentile_05_return_personal_keuangan_bulanan = array();

      $saldo_personal_keuangan_awal_p95 = array();
      $pengembangan_personal_keuangan_p95 = array();
      $saldo_personal_keuangan_akhir_p95 = array();
      
      $saldo_personal_keuangan_awal_p50 = array();
      $pengembangan_personal_keuangan_p50 = array();
      $saldo_personal_keuangan_akhir_p50 = array();
      
      $saldo_personal_keuangan_awal_p05 = array();
      $pengembangan_personal_keuangan_p05 = array();
      $saldo_personal_keuangan_akhir_p05 = array();

      $previous_saldo_personal_keuangan_akhir_p95 = null;
      $previous_saldo_personal_keuangan_akhir_p50 = null;
      $previous_saldo_personal_keuangan_akhir_p05 = null;
      
      $anuitas_personal_keuangan_p95 = array();
      $anuitas_personal_keuangan_p50 = array();
      $anuitas_personal_keuangan_p05 = array();
      $kupon_sbn_personal_keuangan_p95 = array();
      $kupon_sbn_personal_keuangan_p50 = array();
      $kupon_sbn_personal_keuangan_p05 = array();
      
      $rr_personal_keuangan_anuitas_p95 = array();
      $rr_personal_keuangan_anuitas_p50 = array();
      $rr_personal_keuangan_anuitas_p05 = array();
      $rr_personal_keuangan_kupon_sbn_p95 = array();
      $rr_personal_keuangan_kupon_sbn_p50 = array();
      $rr_personal_keuangan_kupon_sbn_p05 = array();

      $j=1; //counter hasil investasi percentile monthly (konversi dari tahunan ke bulanan)
      for($year=2023; $year<=2100; $year++){
        for($month=1; $month<=12; $month++){
          $key = $year . "_" . $month;

          $iuran_personal_keuangan_hitung = $gaji[$key] * $persentase_iuran_personal_keuangan/100; //hitung besar iuran
          
          // //+++++++++++++++++++++++++++++++++++++
          // //F.5.2., F.5.3., dan F.5.4. Simulasi PERSONAL_KEUANGAN - tentukan hasil investasi percentile 95, 50, dan 05
          $percentile_95_return_personal_bulanan_hitung = $percentile_95_return_monthly_personal[$year]; //menentukan percentile secara bulanan dari yang sebelumnya tahunan di monte carlo PERSONAL_KEUANGAN
          $percentile_50_return_personal_bulanan_hitung = $percentile_50_return_monthly_personal[$year]; //menentukan percentile secara bulanan dari yang sebelumnya tahunan di monte carlo PERSONAL_KEUANGAN
          $percentile_05_return_personal_bulanan_hitung = $percentile_05_return_monthly_personal[$year]; //menentukan percentile secara bulanan dari yang sebelumnya tahunan di monte carlo PERSONAL_KEUANGAN

          //Output: Create $iuran_personal_keuangan[$i], $percentile_95_return_personal_keuangan_bulanan[$i], $percentile_50_return_personal_keuangan_bulanan[$i], $percentile_05_return_personal_keuangan_bulanan[$i]
          $iuran_personal_keuangan[$key] = $iuran_personal_keuangan_hitung;
          $percentile_95_return_personal_keuangan_bulanan[$key] = $percentile_95_return_personal_bulanan_hitung;
          $percentile_50_return_personal_keuangan_bulanan[$key] = $percentile_50_return_personal_bulanan_hitung;
          $percentile_05_return_personal_keuangan_bulanan[$key] = $percentile_05_return_personal_bulanan_hitung;
            
          //echo json_encode($percentile_95_return_personal_bulanan_hitung, true);
          //die();

          
          // +++++++++++++++++++++++++++++++++++++
          // F.5.5., F.5.6., F.5.7., F.5.8., F.5.9., F.5.10., F.5.11., F.5.12., dan F.5.13. Simulasi PERSONAL_KEUANGAN - hitung percentile 95,50,05 untuk saldo awal, hasil pengembangan, dan saldo akhir
          if($year==$counter_saldo_personal_keuangan_year && $month==$counter_saldo_personal_keuangan_month){ //tahun pertama ada saldonya
            //percentile 95
            $saldo_personal_keuangan_awal_p95_hitung = $saldo_personal_keuangan_input;
            $pengembangan_personal_keuangan_p95_hitung = ($saldo_personal_keuangan_awal_p95_hitung + $iuran_personal_keuangan_hitung )* $percentile_95_return_personal_bulanan_hitung;
            $saldo_personal_keuangan_akhir_p95_hitung = $saldo_personal_keuangan_awal_p95_hitung + $iuran_personal_keuangan_hitung + $pengembangan_personal_keuangan_p95_hitung; //saldo merupakan saldo akhir bulan
            $previous_saldo_personal_keuangan_akhir_p95 = $saldo_personal_keuangan_akhir_p95_hitung;
            
           //echo json_encode($pengembangan_personal_keuangan_p95_hitung, true);
           //echo json_encode($saldo_personal_keuangan_awal_p95_hitung, true);
           //echo json_encode($iuran_personal_keuangan_hitung, true);
           //echo json_encode($percentile_95_return_personal_bulanan_hitung, true);
           //die();
              
            //percentile 50
            $saldo_personal_keuangan_awal_p50_hitung = $saldo_personal_keuangan_input;
            $pengembangan_personal_keuangan_p50_hitung = ($saldo_personal_keuangan_awal_p50_hitung + $iuran_personal_keuangan_hitung )* $percentile_50_return_personal_bulanan_hitung;
            $saldo_personal_keuangan_akhir_p50_hitung = $saldo_personal_keuangan_awal_p50_hitung + $iuran_personal_keuangan_hitung + $pengembangan_personal_keuangan_p50_hitung; //saldo merupakan saldo akhir bulan
            $previous_saldo_personal_keuangan_akhir_p50 = $saldo_personal_keuangan_akhir_p50_hitung;
              
            //percentile 05
            $saldo_personal_keuangan_awal_p05_hitung = $saldo_personal_keuangan_input;
            $pengembangan_personal_keuangan_p05_hitung = ($saldo_personal_keuangan_awal_p05_hitung + $iuran_personal_keuangan_hitung )* $percentile_05_return_personal_bulanan_hitung;
            $saldo_personal_keuangan_akhir_p05_hitung = $saldo_personal_keuangan_awal_p05_hitung + $iuran_personal_keuangan_hitung + $pengembangan_personal_keuangan_p05_hitung; //saldo merupakan saldo akhir bulan
            $previous_saldo_personal_keuangan_akhir_p05 = $saldo_personal_keuangan_akhir_p05_hitung;
              
          } else if ($year>$counter_saldo_personal_keuangan_year || $month>$counter_saldo_personal_keuangan_month) {
            //percentile 95
            $saldo_personal_keuangan_awal_p95_hitung = $previous_saldo_personal_keuangan_akhir_p95;
            $pengembangan_personal_keuangan_p95_hitung = ($saldo_personal_keuangan_awal_p95_hitung + $iuran_personal_keuangan_hitung )* $percentile_95_return_personal_bulanan_hitung;
            $saldo_personal_keuangan_akhir_p95_hitung = $saldo_personal_keuangan_awal_p95_hitung + $iuran_personal_keuangan_hitung + $pengembangan_personal_keuangan_p95_hitung; //saldo merupakan saldo akhir bulan
            $previous_saldo_personal_keuangan_akhir_p95 = $saldo_personal_keuangan_akhir_p95_hitung;
              
            //percentile 50
            $saldo_personal_keuangan_awal_p50_hitung = $previous_saldo_personal_keuangan_akhir_p50;
            $pengembangan_personal_keuangan_p50_hitung = ($saldo_personal_keuangan_awal_p50_hitung + $iuran_personal_keuangan_hitung )* $percentile_50_return_personal_bulanan_hitung;
            $saldo_personal_keuangan_akhir_p50_hitung = $saldo_personal_keuangan_awal_p50_hitung + $iuran_personal_keuangan_hitung + $pengembangan_personal_keuangan_p50_hitung; //saldo merupakan saldo akhir bulan
            $previous_saldo_personal_keuangan_akhir_p50 = $saldo_personal_keuangan_akhir_p50_hitung;
              
            //percentile 05
            $saldo_personal_keuangan_awal_p05_hitung = $previous_saldo_personal_keuangan_akhir_p05;
            $pengembangan_personal_keuangan_p05_hitung = ($saldo_personal_keuangan_awal_p05_hitung + $iuran_personal_keuangan_hitung )* $percentile_05_return_personal_bulanan_hitung;
            $saldo_personal_keuangan_akhir_p05_hitung = $saldo_personal_keuangan_awal_p05_hitung + $iuran_personal_keuangan_hitung + $pengembangan_personal_keuangan_p05_hitung; //saldo merupakan saldo akhir bulan
            $previous_saldo_personal_keuangan_akhir_p05 = $saldo_personal_keuangan_akhir_p05_hitung;  
              
          } else{
            //percentile 95
            $saldo_personal_keuangan_awal_p95_hitung = 0;
            $pengembangan_personal_keuangan_p95_hitung = 0;
            $saldo_personal_keuangan_akhir_p95_hitung = 0;
            
            //percentile 50
            $saldo_personal_keuangan_awal_p50_hitung = 0;
            $pengembangan_personal_keuangan_p50_hitung = 0;
            $saldo_personal_keuangan_akhir_p50_hitung = 0;
            
            //percentile 05
            $saldo_personal_keuangan_awal_p05_hitung = 0;
            $pengembangan_personal_keuangan_p05_hitung = 0;
            $saldo_personal_keuangan_akhir_p05_hitung = 0;
          }

          //output: Create $saldo_personal_keuangan_awal_p95[$i], $pengembangan_personal_keuangan_p95[$i], $saldo_personal_keuangan_akhir_p95[$i], $saldo_personal_keuangan_awal_p50[$i], $pengembangan_personal_keuangan_p50[$i], $saldo_personal_keuangan_akhir_p50[$i], $saldo_personal_keuangan_awal_p05[$i], $pengembangan_personal_keuangan_p05[$i], $saldo_personal_keuangan_akhir_p05[$i]
          $saldo_personal_keuangan_awal_p95[$key] = $saldo_personal_keuangan_awal_p95_hitung;
          $pengembangan_personal_keuangan_p95[$key] = $pengembangan_personal_keuangan_p95_hitung;
          $saldo_personal_keuangan_akhir_p95[$key] = $saldo_personal_keuangan_akhir_p95_hitung;
          
          $saldo_personal_keuangan_awal_p50[$key] = $saldo_personal_keuangan_awal_p50_hitung;
          $pengembangan_personal_keuangan_p50[$key] = $pengembangan_personal_keuangan_p50_hitung;
          $saldo_personal_keuangan_akhir_p50[$key] = $saldo_personal_keuangan_akhir_p50_hitung;
          
          $saldo_personal_keuangan_awal_p05[$key] = $saldo_personal_keuangan_awal_p05_hitung;
          $pengembangan_personal_keuangan_p05[$key] = $pengembangan_personal_keuangan_p05_hitung;
          $saldo_personal_keuangan_akhir_p05[$key] = $saldo_personal_keuangan_akhir_p05_hitung;

          //$previous_saldo_personal_keuangan_akhir_p95 = $saldo_personal_keuangan_akhir_p95[$key];
          //$previous_saldo_personal_keuangan_akhir_p50 = $saldo_personal_keuangan_akhir_p50[$key];
          //$previous_saldo_personal_keuangan_akhir_p05 = $saldo_personal_keuangan_akhir_p05[$key];
          
          //++++++++++++++++++++++++++++++++++++++++
          //F.5.14., F.5.15., dan F.5.16. Simulasi PERSONAL_KEUANGAN - Hitung anuitas bulanan untuk percentile 95, 50, dan 05 (hitung MP Bulanan bila dihitung menggunakan anuitas seumur hidup)
          $anuitas_personal_keuangan_p95_hitung = $saldo_personal_keuangan_akhir_p95_hitung / $harga_anuitas_personal_keuangan;
          $anuitas_personal_keuangan_p50_hitung = $saldo_personal_keuangan_akhir_p50_hitung / $harga_anuitas_personal_keuangan;
          $anuitas_personal_keuangan_p05_hitung = $saldo_personal_keuangan_akhir_p05_hitung / $harga_anuitas_personal_keuangan;
          
          //++++++++++++++++++++++++++++++++++++++++
          //F.5.17., F.5.18., dan F.5.19. Simulasi PERSONAL_KEUANGAN - Hitung kupon SBN/SBSN bulanan untuk percentile 95, 50, dan 05 (hitung MP Bulanan bila dihitung menggunakan kupon SBN/SBSN)
          $kupon_sbn_personal_keuangan_p95_hitung = ( $saldo_personal_keuangan_akhir_p95_hitung * $kupon_sbn_personal_keuangan *(1-$pajak_sbn_personal_keuangan))/12; //pembayaran bulanan dari kupon SBN/SBSN percentile 95
          $kupon_sbn_personal_keuangan_p50_hitung = ( $saldo_personal_keuangan_akhir_p50_hitung * $kupon_sbn_personal_keuangan *(1-$pajak_sbn_personal_keuangan))/12; //pembayaran bulanan dari kupon SBN/SBSN percentile 50
          $kupon_sbn_personal_keuangan_p05_hitung = ( $saldo_personal_keuangan_akhir_p05_hitung * $kupon_sbn_personal_keuangan *(1-$pajak_sbn_personal_keuangan))/12; //pembayaran bulanan dari kupon SBN/SBSN percentile 05

          //Output: Create $anuitas_personal_keuangan_p95[$i], $anuitas_personal_keuangan_p50[$i], $anuitas_personal_keuangan_p05[$i], $kupon_sbn_personal_keuangan_p95[$i], $kupon_sbn_personal_keuangan_p50[$i], $kupon_sbn_personal_keuangan_p05[$i]
          $anuitas_personal_keuangan_p95[$key] = $anuitas_personal_keuangan_p95_hitung;
          $anuitas_personal_keuangan_p50[$key] = $anuitas_personal_keuangan_p50_hitung;
          $anuitas_personal_keuangan_p05[$key] = $anuitas_personal_keuangan_p05_hitung;
          $kupon_sbn_personal_keuangan_p95[$key] = $kupon_sbn_personal_keuangan_p95_hitung;
          $kupon_sbn_personal_keuangan_p50[$key] = $kupon_sbn_personal_keuangan_p50_hitung;
          $kupon_sbn_personal_keuangan_p05[$key] = $kupon_sbn_personal_keuangan_p05_hitung;

          
          //++++++++++++++++++++++++++++++++++++++++
          //F.5.20., F.5.21., F.5.22., F.5.23., F.5.24., dan F.5.25., Hitung RR untuk anuitas dan kupon SBN/SBSN pada percentile 95, 50, dan 05
          if ($gaji[$key]>0){
            
            //untuk anuitas
            $rr_personal_keuangan_anuitas_p95_hitung = $anuitas_personal_keuangan_p95_hitung / $gaji[$key];
            $rr_personal_keuangan_anuitas_p50_hitung = $anuitas_personal_keuangan_p50_hitung / $gaji[$key];
            $rr_personal_keuangan_anuitas_p05_hitung = $anuitas_personal_keuangan_p05_hitung / $gaji[$key];
            
            //untuk kupon SBN/SBSN
            $rr_personal_keuangan_kupon_sbn_p95_hitung = $kupon_sbn_personal_keuangan_p95_hitung / $gaji[$key];
            $rr_personal_keuangan_kupon_sbn_p50_hitung = $kupon_sbn_personal_keuangan_p50_hitung / $gaji[$key];
            $rr_personal_keuangan_kupon_sbn_p05_hitung = $kupon_sbn_personal_keuangan_p05_hitung / $gaji[$key];
            
          } else{
            //untuk anuitas
            $rr_personal_keuangan_anuitas_p95_hitung = 0;
            $rr_personal_keuangan_anuitas_p50_hitung = 0;
            $rr_personal_keuangan_anuitas_p05_hitung = 0;
            
            //untuk kupon SBN/SBSN
            $rr_personal_keuangan_kupon_sbn_p95_hitung = 0;
            $rr_personal_keuangan_kupon_sbn_p50_hitung = 0;
            $rr_personal_keuangan_kupon_sbn_p05_hitung = 0;
          }

          //Output: Create $rr_personal_keuangan_anuitas_p95[$i], $rr_personal_keuangan_anuitas_p50[$i], $rr_personal_keuangan_anuitas_p05[$i], $rr_personal_keuangan_kupon_sbn_p95[$i], $rr_personal_keuangan_kupon_sbn_p50[$i], $rr_personal_keuangan_kupon_sbn_p05[$i]
          $rr_personal_keuangan_anuitas_p95[$key] = $rr_personal_keuangan_anuitas_p95_hitung;
          $rr_personal_keuangan_anuitas_p50[$key] = $rr_personal_keuangan_anuitas_p50_hitung;
          $rr_personal_keuangan_anuitas_p05[$key] = $rr_personal_keuangan_anuitas_p05_hitung;
          $rr_personal_keuangan_kupon_sbn_p95[$key] = $rr_personal_keuangan_kupon_sbn_p95_hitung;
          $rr_personal_keuangan_kupon_sbn_p50[$key] = $rr_personal_keuangan_kupon_sbn_p50_hitung;
          $rr_personal_keuangan_kupon_sbn_p05[$key] = $rr_personal_keuangan_kupon_sbn_p05_hitung;
        }
      }

      /*
      //tidak perlu ada return ke database
      $this->uploadToDatabase("profil_personal_iuran", $id_user, $iuran_personal_keuangan);
      
      $this->uploadToDatabase("profil_personal_investasi_p95", $id_user, $percentile_95_return_personal_keuangan_bulanan);
      $this->uploadToDatabase("profil_personal_investasi_p50", $id_user, $percentile_50_return_personal_keuangan_bulanan);
      $this->uploadToDatabase("profil_personal_investasi_p5", $id_user, $percentile_05_return_personal_keuangan_bulanan);
      
      $this->uploadToDatabase("profil_personal_p95_saldo_awal", $id_user, $saldo_personal_keuangan_awal_p95);
      $this->uploadToDatabase("profil_personal_p50_saldo_awal", $id_user, $saldo_personal_keuangan_awal_p50);
      $this->uploadToDatabase("profil_personal_p5_saldo_awal", $id_user, $saldo_personal_keuangan_awal_p05);
      
      $this->uploadToDatabase("profil_personal_p95_saldo_akhir", $id_user, $saldo_personal_keuangan_akhir_p95);
      $this->uploadToDatabase("profil_personal_p50_saldo_akhir", $id_user, $saldo_personal_keuangan_akhir_p50);
      $this->uploadToDatabase("profil_personal_p5_saldo_akhir", $id_user, $saldo_personal_keuangan_akhir_p05);
      
      $this->uploadToDatabase("profil_personal_p95_pengembangan", $id_user, $pengembangan_personal_keuangan_p95);
      $this->uploadToDatabase("profil_personal_p50_pengembangan", $id_user, $pengembangan_personal_keuangan_p50);
      $this->uploadToDatabase("profil_personal_p5_pengembangan", $id_user, $pengembangan_personal_keuangan_p05);
      
      $this->uploadToDatabase("profil_personal_anuitas_p95", $id_user, $anuitas_personal_keuangan_p95);
      $this->uploadToDatabase("profil_personal_anuitas_p50", $id_user, $anuitas_personal_keuangan_p50);
      $this->uploadToDatabase("profil_personal_anuitas_p5", $id_user, $anuitas_personal_keuangan_p05);
      
      $this->uploadToDatabase("profil_personal_bunga_deposito_p95", $id_user, $kupon_sbn_personal_keuangan_p95);
      $this->uploadToDatabase("profil_personal_bunga_deposito_p50", $id_user, $kupon_sbn_personal_keuangan_p50);
      $this->uploadToDatabase("profil_personal_bunga_deposito_p5", $id_user, $kupon_sbn_personal_keuangan_p05);
      
      $this->uploadToDatabase("profil_personal_rr_anuitas_p95", $id_user, $rr_personal_keuangan_anuitas_p95);
      $this->uploadToDatabase("profil_personal_rr_anuitas_p50", $id_user, $rr_personal_keuangan_anuitas_p50);
      $this->uploadToDatabase("profil_personal_rr_anuitas_p5", $id_user, $rr_personal_keuangan_anuitas_p05);
      
      $this->uploadToDatabase("profil_personal_rr_bunga_deposito_p95", $id_user, $rr_personal_keuangan_kupon_sbn_p95);
      $this->uploadToDatabase("profil_personal_rr_bunga_deposito_p50", $id_user, $rr_personal_keuangan_kupon_sbn_p50);
      $this->uploadToDatabase("profil_personal_rr_bunga_deposito_p5", $id_user, $rr_personal_keuangan_kupon_sbn_p05);
      */  
      
      return array(
        "iuran_personal_keuangan" => $iuran_personal_keuangan,
        "percentile_95_return_personal_keuangan_bulanan" => $percentile_95_return_personal_keuangan_bulanan,
        "percentile_50_return_personal_keuangan_bulanan" => $percentile_50_return_personal_keuangan_bulanan,
        "percentile_05_return_personal_keuangan_bulanan" => $percentile_05_return_personal_keuangan_bulanan,
        "saldo_personal_keuangan_awal_p95" => $saldo_personal_keuangan_awal_p95,
        "pengembangan_personal_keuangan_p95" => $pengembangan_personal_keuangan_p95,
        "saldo_personal_keuangan_akhir_p95" => $saldo_personal_keuangan_akhir_p95, // Data FE Diagram
        "saldo_personal_keuangan_awal_p50" => $saldo_personal_keuangan_awal_p50,
        "pengembangan_personal_keuangan_p50" => $pengembangan_personal_keuangan_p50,
        "saldo_personal_keuangan_akhir_p50" => $saldo_personal_keuangan_akhir_p50, // Data FE Diagram
        "saldo_personal_keuangan_awal_p05" => $saldo_personal_keuangan_awal_p05,
        "pengembangan_personal_keuangan_p05" => $pengembangan_personal_keuangan_p05,
        "saldo_personal_keuangan_akhir_p05" => $saldo_personal_keuangan_akhir_p05, // Data FE Diagram
        "previous_saldo_personal_keuangan_akhir_p95" => $previous_saldo_personal_keuangan_akhir_p95,
        "previous_saldo_personal_keuangan_akhir_p50" => $previous_saldo_personal_keuangan_akhir_p50,
        "previous_saldo_personal_keuangan_akhir_p05" => $previous_saldo_personal_keuangan_akhir_p05,
        "anuitas_personal_keuangan_p95" => $anuitas_personal_keuangan_p95,
        "anuitas_personal_keuangan_p50" => $anuitas_personal_keuangan_p50,
        "anuitas_personal_keuangan_p05" => $anuitas_personal_keuangan_p05,
        "kupon_sbn_personal_keuangan_p95" => $kupon_sbn_personal_keuangan_p95,
        "kupon_sbn_personal_keuangan_p50" => $kupon_sbn_personal_keuangan_p50,
        "kupon_sbn_personal_keuangan_p05" => $kupon_sbn_personal_keuangan_p05,
        "rr_personal_keuangan_anuitas_p95" => $rr_personal_keuangan_anuitas_p95,
        "rr_personal_keuangan_anuitas_p50" => $rr_personal_keuangan_anuitas_p50,
        "rr_personal_keuangan_anuitas_p05" => $rr_personal_keuangan_anuitas_p05,
        "rr_personal_keuangan_kupon_sbn_p95" => $rr_personal_keuangan_kupon_sbn_p95,
        "rr_personal_keuangan_kupon_sbn_p50" => $rr_personal_keuangan_kupon_sbn_p50,
        "rr_personal_keuangan_kupon_sbn_p05" => $rr_personal_keuangan_kupon_sbn_p05,
      );
    }

    public function simulasi_personal_keuangan_solver1($data_user, $id_user, $return_simulasi_gaji_phdp, $flag_pensiun, $montecarlo_personal_keuangan, $return_simulasi_ppmp, $iuran_hitung){
      // Sheet 4 Baris 73
      //Input: variabel $gaji{$i] yang ada di memory serta flag pensiun, Read tambahan iuran personal_keuangan, Read Saldo PERSONAL_KEUANGAN
      $gaji = $return_simulasi_gaji_phdp['gaji'];
      $counter_saldo_personal_keuangan = explode("_", $return_simulasi_gaji_phdp['counter_saldo_personal_keuangan']);
      $counter_saldo_personal_keuangan_year = $counter_saldo_personal_keuangan[0]; 
      $counter_saldo_personal_keuangan_month = $counter_saldo_personal_keuangan[1];

      //F.5.1. Simulasi PERSONAL_KEUANGAN - Hitung iuran
      $setting_nilai_asumsi_user = DB::table('nilai_asumsi_user')
            ->where('id_user', $id_user)
            ->where('flag', 1)
            ->select('*')->get()[0];
      //$persentase_iuran_personal_keuangan=$setting_nilai_asumsi_user->jumlah_pembayaran_iuran_personal; //Read besar iuran personal keuangan di profil user
      $persentase_iuran_personal_keuangan = $iuran_hitung*100;
        
        $saldo_personal_keuangan_input=$data_user->jumlah_investasi_keuangan; // Read saldo personal_keuangan yang diinput (saldo diasumsikan diinput di awal bulan)
        
      //nilai default pilihan pembayaran personal keuangan
      //Input: Read pilihan pembayaran personal keuangan, Read kupon SBN/SBSN dan beserta pajak dari profil user, Read Harga anuitas dari profil user
      //pembayaran personal_keuangan jika 1=anuitas; 2=kupon SBN/SBSN

      $setting_treatment_user = DB::table('setting_treatment_pembayaran_setelah_pensiun')
            ->where('id_user', $id_user)
            ->where('flag', 1)
            ->select('*')->get()[0];

      $pembayaran_personal_keuangan=($setting_treatment_user->personal_pasar_keuangan === 'Beli Anuitas') ? 1 : 2;//Read pilihan pembayaran personal_keuangan (pembayaran personal_keuangan jika 1=anuitas; 2=kupon SBN/SBSN)
      if($pembayaran_personal_keuangan==1){
        $harga_anuitas_personal_keuangan =$setting_treatment_user->harga_anuitas_personal_pasar_keuangan;//Read harga anuitas masing-masing user
        
        $kupon_sbn_personal_keuangan =0.06125;//default
        $pajak_sbn_personal_keuangan =0.01;//default
      } else {
        $harga_anuitas_personal_keuangan =136;//default
        
        $kupon_sbn_personal_keuangan =$setting_treatment_user->bunga_personal_pasar_keuangan;//Read kupon SBN/SBSN dari profil user
        $pajak_sbn_personal_keuangan =$setting_treatment_user->pajak_personal_pasar_keuangan;//Read pajak SBN/SBSN dari profil user
        
        $kupon_sbn_personal_keuangan =$kupon_sbn_personal_keuangan / 100;
        $pajak_sbn_personal_keuangan =$pajak_sbn_personal_keuangan / 100;
      }
     //echo json_encode($kupon_sbn_personal_keuangan, true);
       //die();
      $percentile_95_return_monthly_personal = $montecarlo_personal_keuangan["percentile_95_return_monthly_personal"];
      $percentile_50_return_monthly_personal = $montecarlo_personal_keuangan["percentile_50_return_monthly_personal"];
      $percentile_05_return_monthly_personal = $montecarlo_personal_keuangan["percentile_05_return_monthly_personal"];

      $iuran_personal_keuangan = array();
      $percentile_95_return_personal_keuangan_bulanan = array();
      $percentile_50_return_personal_keuangan_bulanan = array();
      $percentile_05_return_personal_keuangan_bulanan = array();

      $saldo_personal_keuangan_awal_p95 = array();
      $pengembangan_personal_keuangan_p95 = array();
      $saldo_personal_keuangan_akhir_p95 = array();
      
      $saldo_personal_keuangan_awal_p50 = array();
      $pengembangan_personal_keuangan_p50 = array();
      $saldo_personal_keuangan_akhir_p50 = array();
      
      $saldo_personal_keuangan_awal_p05 = array();
      $pengembangan_personal_keuangan_p05 = array();
      $saldo_personal_keuangan_akhir_p05 = array();

      $previous_saldo_personal_keuangan_akhir_p95 = null;
      $previous_saldo_personal_keuangan_akhir_p50 = null;
      $previous_saldo_personal_keuangan_akhir_p05 = null;
      
      $anuitas_personal_keuangan_p95 = array();
      $anuitas_personal_keuangan_p50 = array();
      $anuitas_personal_keuangan_p05 = array();
      $kupon_sbn_personal_keuangan_p95 = array();
      $kupon_sbn_personal_keuangan_p50 = array();
      $kupon_sbn_personal_keuangan_p05 = array();
      
      $rr_personal_keuangan_anuitas_p95 = array();
      $rr_personal_keuangan_anuitas_p50 = array();
      $rr_personal_keuangan_anuitas_p05 = array();
      $rr_personal_keuangan_kupon_sbn_p95 = array();
      $rr_personal_keuangan_kupon_sbn_p50 = array();
      $rr_personal_keuangan_kupon_sbn_p05 = array();

      $j=1; //counter hasil investasi percentile monthly (konversi dari tahunan ke bulanan)
      for($year=2023; $year<=2100; $year++){
        for($month=1; $month<=12; $month++){
          $key = $year . "_" . $month;

          $iuran_personal_keuangan_hitung = $gaji[$key] * $persentase_iuran_personal_keuangan/100; //hitung besar iuran
          
          // //+++++++++++++++++++++++++++++++++++++
          // //F.5.2., F.5.3., dan F.5.4. Simulasi PERSONAL_KEUANGAN - tentukan hasil investasi percentile 95, 50, dan 05
          $percentile_95_return_personal_bulanan_hitung = $percentile_95_return_monthly_personal[$year]; //menentukan percentile secara bulanan dari yang sebelumnya tahunan di monte carlo PERSONAL_KEUANGAN
          $percentile_50_return_personal_bulanan_hitung = $percentile_50_return_monthly_personal[$year]; //menentukan percentile secara bulanan dari yang sebelumnya tahunan di monte carlo PERSONAL_KEUANGAN
          $percentile_05_return_personal_bulanan_hitung = $percentile_05_return_monthly_personal[$year]; //menentukan percentile secara bulanan dari yang sebelumnya tahunan di monte carlo PERSONAL_KEUANGAN

          //Output: Create $iuran_personal_keuangan[$i], $percentile_95_return_personal_keuangan_bulanan[$i], $percentile_50_return_personal_keuangan_bulanan[$i], $percentile_05_return_personal_keuangan_bulanan[$i]
          $iuran_personal_keuangan[$key] = $iuran_personal_keuangan_hitung;
          $percentile_95_return_personal_keuangan_bulanan[$key] = $percentile_95_return_personal_bulanan_hitung;
          $percentile_50_return_personal_keuangan_bulanan[$key] = $percentile_50_return_personal_bulanan_hitung;
          $percentile_05_return_personal_keuangan_bulanan[$key] = $percentile_05_return_personal_bulanan_hitung;
            
          //echo json_encode($percentile_95_return_personal_bulanan_hitung, true);
          //die();

          
          // +++++++++++++++++++++++++++++++++++++
          // F.5.5., F.5.6., F.5.7., F.5.8., F.5.9., F.5.10., F.5.11., F.5.12., dan F.5.13. Simulasi PERSONAL_KEUANGAN - hitung percentile 95,50,05 untuk saldo awal, hasil pengembangan, dan saldo akhir
          if($year==$counter_saldo_personal_keuangan_year && $month==$counter_saldo_personal_keuangan_month){ //tahun pertama ada saldonya
            //percentile 95
            $saldo_personal_keuangan_awal_p95_hitung = $saldo_personal_keuangan_input;
            $pengembangan_personal_keuangan_p95_hitung = ($saldo_personal_keuangan_awal_p95_hitung + $iuran_personal_keuangan_hitung )* $percentile_95_return_personal_bulanan_hitung;
            $saldo_personal_keuangan_akhir_p95_hitung = $saldo_personal_keuangan_awal_p95_hitung + $iuran_personal_keuangan_hitung + $pengembangan_personal_keuangan_p95_hitung; //saldo merupakan saldo akhir bulan
            $previous_saldo_personal_keuangan_akhir_p95 = $saldo_personal_keuangan_akhir_p95_hitung;
            
           //echo json_encode($pengembangan_personal_keuangan_p95_hitung, true);
           //echo json_encode($saldo_personal_keuangan_awal_p95_hitung, true);
           //echo json_encode($iuran_personal_keuangan_hitung, true);
           //echo json_encode($percentile_95_return_personal_bulanan_hitung, true);
           //die();
              
            //percentile 50
            $saldo_personal_keuangan_awal_p50_hitung = $saldo_personal_keuangan_input;
            $pengembangan_personal_keuangan_p50_hitung = ($saldo_personal_keuangan_awal_p50_hitung + $iuran_personal_keuangan_hitung )* $percentile_50_return_personal_bulanan_hitung;
            $saldo_personal_keuangan_akhir_p50_hitung = $saldo_personal_keuangan_awal_p50_hitung + $iuran_personal_keuangan_hitung + $pengembangan_personal_keuangan_p50_hitung; //saldo merupakan saldo akhir bulan
            $previous_saldo_personal_keuangan_akhir_p50 = $saldo_personal_keuangan_akhir_p50_hitung;
              
            //percentile 05
            $saldo_personal_keuangan_awal_p05_hitung = $saldo_personal_keuangan_input;
            $pengembangan_personal_keuangan_p05_hitung = ($saldo_personal_keuangan_awal_p05_hitung + $iuran_personal_keuangan_hitung )* $percentile_05_return_personal_bulanan_hitung;
            $saldo_personal_keuangan_akhir_p05_hitung = $saldo_personal_keuangan_awal_p05_hitung + $iuran_personal_keuangan_hitung + $pengembangan_personal_keuangan_p05_hitung; //saldo merupakan saldo akhir bulan
            $previous_saldo_personal_keuangan_akhir_p05 = $saldo_personal_keuangan_akhir_p05_hitung;
              
          } else if ($year>$counter_saldo_personal_keuangan_year || $month>$counter_saldo_personal_keuangan_month) {
            //percentile 95
            $saldo_personal_keuangan_awal_p95_hitung = $previous_saldo_personal_keuangan_akhir_p95;
            $pengembangan_personal_keuangan_p95_hitung = ($saldo_personal_keuangan_awal_p95_hitung + $iuran_personal_keuangan_hitung )* $percentile_95_return_personal_bulanan_hitung;
            $saldo_personal_keuangan_akhir_p95_hitung = $saldo_personal_keuangan_awal_p95_hitung + $iuran_personal_keuangan_hitung + $pengembangan_personal_keuangan_p95_hitung; //saldo merupakan saldo akhir bulan
            $previous_saldo_personal_keuangan_akhir_p95 = $saldo_personal_keuangan_akhir_p95_hitung;
              
            //percentile 50
            $saldo_personal_keuangan_awal_p50_hitung = $previous_saldo_personal_keuangan_akhir_p50;
            $pengembangan_personal_keuangan_p50_hitung = ($saldo_personal_keuangan_awal_p50_hitung + $iuran_personal_keuangan_hitung )* $percentile_50_return_personal_bulanan_hitung;
            $saldo_personal_keuangan_akhir_p50_hitung = $saldo_personal_keuangan_awal_p50_hitung + $iuran_personal_keuangan_hitung + $pengembangan_personal_keuangan_p50_hitung; //saldo merupakan saldo akhir bulan
            $previous_saldo_personal_keuangan_akhir_p50 = $saldo_personal_keuangan_akhir_p50_hitung;
              
            //percentile 05
            $saldo_personal_keuangan_awal_p05_hitung = $previous_saldo_personal_keuangan_akhir_p05;
            $pengembangan_personal_keuangan_p05_hitung = ($saldo_personal_keuangan_awal_p05_hitung + $iuran_personal_keuangan_hitung )* $percentile_05_return_personal_bulanan_hitung;
            $saldo_personal_keuangan_akhir_p05_hitung = $saldo_personal_keuangan_awal_p05_hitung + $iuran_personal_keuangan_hitung + $pengembangan_personal_keuangan_p05_hitung; //saldo merupakan saldo akhir bulan
            $previous_saldo_personal_keuangan_akhir_p05 = $saldo_personal_keuangan_akhir_p05_hitung;  
              
          } else{
            //percentile 95
            $saldo_personal_keuangan_awal_p95_hitung = 0;
            $pengembangan_personal_keuangan_p95_hitung = 0;
            $saldo_personal_keuangan_akhir_p95_hitung = 0;
            
            //percentile 50
            $saldo_personal_keuangan_awal_p50_hitung = 0;
            $pengembangan_personal_keuangan_p50_hitung = 0;
            $saldo_personal_keuangan_akhir_p50_hitung = 0;
            
            //percentile 05
            $saldo_personal_keuangan_awal_p05_hitung = 0;
            $pengembangan_personal_keuangan_p05_hitung = 0;
            $saldo_personal_keuangan_akhir_p05_hitung = 0;
          }

          //output: Create $saldo_personal_keuangan_awal_p95[$i], $pengembangan_personal_keuangan_p95[$i], $saldo_personal_keuangan_akhir_p95[$i], $saldo_personal_keuangan_awal_p50[$i], $pengembangan_personal_keuangan_p50[$i], $saldo_personal_keuangan_akhir_p50[$i], $saldo_personal_keuangan_awal_p05[$i], $pengembangan_personal_keuangan_p05[$i], $saldo_personal_keuangan_akhir_p05[$i]
          $saldo_personal_keuangan_awal_p95[$key] = $saldo_personal_keuangan_awal_p95_hitung;
          $pengembangan_personal_keuangan_p95[$key] = $pengembangan_personal_keuangan_p95_hitung;
          $saldo_personal_keuangan_akhir_p95[$key] = $saldo_personal_keuangan_akhir_p95_hitung;
          
          $saldo_personal_keuangan_awal_p50[$key] = $saldo_personal_keuangan_awal_p50_hitung;
          $pengembangan_personal_keuangan_p50[$key] = $pengembangan_personal_keuangan_p50_hitung;
          $saldo_personal_keuangan_akhir_p50[$key] = $saldo_personal_keuangan_akhir_p50_hitung;
          
          $saldo_personal_keuangan_awal_p05[$key] = $saldo_personal_keuangan_awal_p05_hitung;
          $pengembangan_personal_keuangan_p05[$key] = $pengembangan_personal_keuangan_p05_hitung;
          $saldo_personal_keuangan_akhir_p05[$key] = $saldo_personal_keuangan_akhir_p05_hitung;

          //$previous_saldo_personal_keuangan_akhir_p95 = $saldo_personal_keuangan_akhir_p95[$key];
          //$previous_saldo_personal_keuangan_akhir_p50 = $saldo_personal_keuangan_akhir_p50[$key];
          //$previous_saldo_personal_keuangan_akhir_p05 = $saldo_personal_keuangan_akhir_p05[$key];
          
          //++++++++++++++++++++++++++++++++++++++++
          //F.5.14., F.5.15., dan F.5.16. Simulasi PERSONAL_KEUANGAN - Hitung anuitas bulanan untuk percentile 95, 50, dan 05 (hitung MP Bulanan bila dihitung menggunakan anuitas seumur hidup)
          $anuitas_personal_keuangan_p95_hitung = $saldo_personal_keuangan_akhir_p95_hitung / $harga_anuitas_personal_keuangan;
          $anuitas_personal_keuangan_p50_hitung = $saldo_personal_keuangan_akhir_p50_hitung / $harga_anuitas_personal_keuangan;
          $anuitas_personal_keuangan_p05_hitung = $saldo_personal_keuangan_akhir_p05_hitung / $harga_anuitas_personal_keuangan;
          
          //++++++++++++++++++++++++++++++++++++++++
          //F.5.17., F.5.18., dan F.5.19. Simulasi PERSONAL_KEUANGAN - Hitung kupon SBN/SBSN bulanan untuk percentile 95, 50, dan 05 (hitung MP Bulanan bila dihitung menggunakan kupon SBN/SBSN)
          $kupon_sbn_personal_keuangan_p95_hitung = ( $saldo_personal_keuangan_akhir_p95_hitung * $kupon_sbn_personal_keuangan *(1-$pajak_sbn_personal_keuangan))/12; //pembayaran bulanan dari kupon SBN/SBSN percentile 95
          $kupon_sbn_personal_keuangan_p50_hitung = ( $saldo_personal_keuangan_akhir_p50_hitung * $kupon_sbn_personal_keuangan *(1-$pajak_sbn_personal_keuangan))/12; //pembayaran bulanan dari kupon SBN/SBSN percentile 50
          $kupon_sbn_personal_keuangan_p05_hitung = ( $saldo_personal_keuangan_akhir_p05_hitung * $kupon_sbn_personal_keuangan *(1-$pajak_sbn_personal_keuangan))/12; //pembayaran bulanan dari kupon SBN/SBSN percentile 05

          //Output: Create $anuitas_personal_keuangan_p95[$i], $anuitas_personal_keuangan_p50[$i], $anuitas_personal_keuangan_p05[$i], $kupon_sbn_personal_keuangan_p95[$i], $kupon_sbn_personal_keuangan_p50[$i], $kupon_sbn_personal_keuangan_p05[$i]
          $anuitas_personal_keuangan_p95[$key] = $anuitas_personal_keuangan_p95_hitung;
          $anuitas_personal_keuangan_p50[$key] = $anuitas_personal_keuangan_p50_hitung;
          $anuitas_personal_keuangan_p05[$key] = $anuitas_personal_keuangan_p05_hitung;
          $kupon_sbn_personal_keuangan_p95[$key] = $kupon_sbn_personal_keuangan_p95_hitung;
          $kupon_sbn_personal_keuangan_p50[$key] = $kupon_sbn_personal_keuangan_p50_hitung;
          $kupon_sbn_personal_keuangan_p05[$key] = $kupon_sbn_personal_keuangan_p05_hitung;

          
          //++++++++++++++++++++++++++++++++++++++++
          //F.5.20., F.5.21., F.5.22., F.5.23., F.5.24., dan F.5.25., Hitung RR untuk anuitas dan kupon SBN/SBSN pada percentile 95, 50, dan 05
          if ($gaji[$key]>0){
            
            //untuk anuitas
            $rr_personal_keuangan_anuitas_p95_hitung = $anuitas_personal_keuangan_p95_hitung / $gaji[$key];
            $rr_personal_keuangan_anuitas_p50_hitung = $anuitas_personal_keuangan_p50_hitung / $gaji[$key];
            $rr_personal_keuangan_anuitas_p05_hitung = $anuitas_personal_keuangan_p05_hitung / $gaji[$key];
            
            //untuk kupon SBN/SBSN
            $rr_personal_keuangan_kupon_sbn_p95_hitung = $kupon_sbn_personal_keuangan_p95_hitung / $gaji[$key];
            $rr_personal_keuangan_kupon_sbn_p50_hitung = $kupon_sbn_personal_keuangan_p50_hitung / $gaji[$key];
            $rr_personal_keuangan_kupon_sbn_p05_hitung = $kupon_sbn_personal_keuangan_p05_hitung / $gaji[$key];
            
          } else{
            //untuk anuitas
            $rr_personal_keuangan_anuitas_p95_hitung = 0;
            $rr_personal_keuangan_anuitas_p50_hitung = 0;
            $rr_personal_keuangan_anuitas_p05_hitung = 0;
            
            //untuk kupon SBN/SBSN
            $rr_personal_keuangan_kupon_sbn_p95_hitung = 0;
            $rr_personal_keuangan_kupon_sbn_p50_hitung = 0;
            $rr_personal_keuangan_kupon_sbn_p05_hitung = 0;
          }

          //Output: Create $rr_personal_keuangan_anuitas_p95[$i], $rr_personal_keuangan_anuitas_p50[$i], $rr_personal_keuangan_anuitas_p05[$i], $rr_personal_keuangan_kupon_sbn_p95[$i], $rr_personal_keuangan_kupon_sbn_p50[$i], $rr_personal_keuangan_kupon_sbn_p05[$i]
          $rr_personal_keuangan_anuitas_p95[$key] = $rr_personal_keuangan_anuitas_p95_hitung;
          $rr_personal_keuangan_anuitas_p50[$key] = $rr_personal_keuangan_anuitas_p50_hitung;
          $rr_personal_keuangan_anuitas_p05[$key] = $rr_personal_keuangan_anuitas_p05_hitung;
          $rr_personal_keuangan_kupon_sbn_p95[$key] = $rr_personal_keuangan_kupon_sbn_p95_hitung;
          $rr_personal_keuangan_kupon_sbn_p50[$key] = $rr_personal_keuangan_kupon_sbn_p50_hitung;
          $rr_personal_keuangan_kupon_sbn_p05[$key] = $rr_personal_keuangan_kupon_sbn_p05_hitung;
        }
      }

        /*
            tidak perlu diupload ke database
      $this->uploadToDatabase("profil_personal_iuran", $id_user, $iuran_personal_keuangan);
      //echo json_encode($iuran_personal_keuangan, true);
      //die();
      
      $this->uploadToDatabase("profil_personal_investasi_p95", $id_user, $percentile_95_return_personal_keuangan_bulanan);
      $this->uploadToDatabase("profil_personal_investasi_p50", $id_user, $percentile_50_return_personal_keuangan_bulanan);
      $this->uploadToDatabase("profil_personal_investasi_p5", $id_user, $percentile_05_return_personal_keuangan_bulanan);
      
      $this->uploadToDatabase("profil_personal_p95_saldo_awal", $id_user, $saldo_personal_keuangan_awal_p95);
      $this->uploadToDatabase("profil_personal_p50_saldo_awal", $id_user, $saldo_personal_keuangan_awal_p50);
      $this->uploadToDatabase("profil_personal_p5_saldo_awal", $id_user, $saldo_personal_keuangan_awal_p05);
      
      $this->uploadToDatabase("profil_personal_p95_saldo_akhir", $id_user, $saldo_personal_keuangan_akhir_p95);
      $this->uploadToDatabase("profil_personal_p50_saldo_akhir", $id_user, $saldo_personal_keuangan_akhir_p50);
      $this->uploadToDatabase("profil_personal_p5_saldo_akhir", $id_user, $saldo_personal_keuangan_akhir_p05);
      
      $this->uploadToDatabase("profil_personal_p95_pengembangan", $id_user, $pengembangan_personal_keuangan_p95);
      $this->uploadToDatabase("profil_personal_p50_pengembangan", $id_user, $pengembangan_personal_keuangan_p50);
      $this->uploadToDatabase("profil_personal_p5_pengembangan", $id_user, $pengembangan_personal_keuangan_p05);
      
      $this->uploadToDatabase("profil_personal_anuitas_p95", $id_user, $anuitas_personal_keuangan_p95);
      $this->uploadToDatabase("profil_personal_anuitas_p50", $id_user, $anuitas_personal_keuangan_p50);
      $this->uploadToDatabase("profil_personal_anuitas_p5", $id_user, $anuitas_personal_keuangan_p05);
      
      $this->uploadToDatabase("profil_personal_bunga_deposito_p95", $id_user, $kupon_sbn_personal_keuangan_p95);
      $this->uploadToDatabase("profil_personal_bunga_deposito_p50", $id_user, $kupon_sbn_personal_keuangan_p50);
      $this->uploadToDatabase("profil_personal_bunga_deposito_p5", $id_user, $kupon_sbn_personal_keuangan_p05);
      
      $this->uploadToDatabase("profil_personal_rr_anuitas_p95", $id_user, $rr_personal_keuangan_anuitas_p95);
      $this->uploadToDatabase("profil_personal_rr_anuitas_p50", $id_user, $rr_personal_keuangan_anuitas_p50);
      $this->uploadToDatabase("profil_personal_rr_anuitas_p5", $id_user, $rr_personal_keuangan_anuitas_p05);
      
      $this->uploadToDatabase("profil_personal_rr_bunga_deposito_p95", $id_user, $rr_personal_keuangan_kupon_sbn_p95);
      $this->uploadToDatabase("profil_personal_rr_bunga_deposito_p50", $id_user, $rr_personal_keuangan_kupon_sbn_p50);
      $this->uploadToDatabase("profil_personal_rr_bunga_deposito_p5", $id_user, $rr_personal_keuangan_kupon_sbn_p05);
        */
      return array(
        "iuran_personal_keuangan" => $iuran_personal_keuangan,
        "percentile_95_return_personal_keuangan_bulanan" => $percentile_95_return_personal_keuangan_bulanan,
        "percentile_50_return_personal_keuangan_bulanan" => $percentile_50_return_personal_keuangan_bulanan,
        "percentile_05_return_personal_keuangan_bulanan" => $percentile_05_return_personal_keuangan_bulanan,
        "saldo_personal_keuangan_awal_p95" => $saldo_personal_keuangan_awal_p95,
        "pengembangan_personal_keuangan_p95" => $pengembangan_personal_keuangan_p95,
        "saldo_personal_keuangan_akhir_p95" => $saldo_personal_keuangan_akhir_p95, // Data FE Diagram
        "saldo_personal_keuangan_awal_p50" => $saldo_personal_keuangan_awal_p50,
        "pengembangan_personal_keuangan_p50" => $pengembangan_personal_keuangan_p50,
        "saldo_personal_keuangan_akhir_p50" => $saldo_personal_keuangan_akhir_p50, // Data FE Diagram
        "saldo_personal_keuangan_awal_p05" => $saldo_personal_keuangan_awal_p05,
        "pengembangan_personal_keuangan_p05" => $pengembangan_personal_keuangan_p05,
        "saldo_personal_keuangan_akhir_p05" => $saldo_personal_keuangan_akhir_p05, // Data FE Diagram
        "previous_saldo_personal_keuangan_akhir_p95" => $previous_saldo_personal_keuangan_akhir_p95,
        "previous_saldo_personal_keuangan_akhir_p50" => $previous_saldo_personal_keuangan_akhir_p50,
        "previous_saldo_personal_keuangan_akhir_p05" => $previous_saldo_personal_keuangan_akhir_p05,
        "anuitas_personal_keuangan_p95" => $anuitas_personal_keuangan_p95,
        "anuitas_personal_keuangan_p50" => $anuitas_personal_keuangan_p50,
        "anuitas_personal_keuangan_p05" => $anuitas_personal_keuangan_p05,
        "kupon_sbn_personal_keuangan_p95" => $kupon_sbn_personal_keuangan_p95,
        "kupon_sbn_personal_keuangan_p50" => $kupon_sbn_personal_keuangan_p50,
        "kupon_sbn_personal_keuangan_p05" => $kupon_sbn_personal_keuangan_p05,
        "rr_personal_keuangan_anuitas_p95" => $rr_personal_keuangan_anuitas_p95,
        "rr_personal_keuangan_anuitas_p50" => $rr_personal_keuangan_anuitas_p50,
        "rr_personal_keuangan_anuitas_p05" => $rr_personal_keuangan_anuitas_p05,
        "rr_personal_keuangan_kupon_sbn_p95" => $rr_personal_keuangan_kupon_sbn_p95,
        "rr_personal_keuangan_kupon_sbn_p50" => $rr_personal_keuangan_kupon_sbn_p50,
        "rr_personal_keuangan_kupon_sbn_p05" => $rr_personal_keuangan_kupon_sbn_p05,
      );
    }

    
    public function cari_iuran($data_user, $id_user, $flag_pensiun, $sisa_kerja_tahun, $sisa_kerja_bulan, $return_simulasi_ppip, $return_simulasi_personal_properti, $return_simulasi_personal_keuangan_solver1, $return_simulasi_ppmp){
      // Sheet 1
      //Input: Read flag pensiun
      $counter_pensiun=""; //counter posisi pensiun
      $previous_flag_pensiun = null;
      
      for($year=2023; $year<=2100; $year++){
        for($month=1; $month<=12; $month++){
          $key = $year . "_" . $month;
          if ($year==2023 && $month==1){
            if ($flag_pensiun[$key]==1){
              $counter_pensiun = $key;// pada saat bulan ini sudah pensiun. jadi saldo yang ditampilkan adalah saldo awal
            }
          } else {
            if ($flag_pensiun[$key]==1 && $previous_flag_pensiun==0){
              $counter_pensiun = $key; // pada saat bulan ini sudah pensiun. jadi saldo yang ditampilkan adalah saldo akhir untuk bulan sebelumnya.
            }
          }
          $previous_flag_pensiun = $flag_pensiun[$key];
        }
      }

      // Note: Bungkus loop year month untuk total rr

      $parts_counter_pensiun = explode("_", $counter_pensiun);
      $counter_pensiun_year = intval($parts_counter_pensiun[0]);
      $counter_pensiun_month = intval($parts_counter_pensiun[1]);
      // Mengurangi satu bulan
      if ($counter_pensiun_month == 1) {
          $counter_pensiun_year -= 1;
          $counter_pensiun_month = 12;
      } else {
          $counter_pensiun_month -= 1;
      }
      $counter_pensiun_minus_one_month = sprintf("%d_%d", $counter_pensiun_year, $counter_pensiun_month);

      //----------------------------------------------------------------------------
      //G.2. Hitung indikator dashboard - posisi saat pensiun
      $rr_ppip_anuitas_p05 = $return_simulasi_ppip["rr_ppip_anuitas_p05"];
      $rr_ppip_anuitas_p50 = $return_simulasi_ppip["rr_ppip_anuitas_p50"];
      $rr_ppip_anuitas_p95 = $return_simulasi_ppip["rr_ppip_anuitas_p95"];

      $rr_ppip_kupon_sbn_p05 = $return_simulasi_ppip["rr_ppip_kupon_sbn_p05"];
      $rr_ppip_kupon_sbn_p50 = $return_simulasi_ppip["rr_ppip_kupon_sbn_p50"];
      $rr_ppip_kupon_sbn_p95 = $return_simulasi_ppip["rr_ppip_kupon_sbn_p95"];
      
      $rr_personal_keuangan_anuitas_p05 = $return_simulasi_personal_keuangan_solver1["rr_personal_keuangan_anuitas_p05"];
      $rr_personal_keuangan_anuitas_p50 = $return_simulasi_personal_keuangan_solver1["rr_personal_keuangan_anuitas_p50"];
      $rr_personal_keuangan_anuitas_p95 = $return_simulasi_personal_keuangan_solver1["rr_personal_keuangan_anuitas_p95"];

      $rr_personal_keuangan_kupon_sbn_p05 = $return_simulasi_personal_keuangan_solver1["rr_personal_keuangan_kupon_sbn_p05"];
      $rr_personal_keuangan_kupon_sbn_p50 = $return_simulasi_personal_keuangan_solver1["rr_personal_keuangan_kupon_sbn_p50"];
      $rr_personal_keuangan_kupon_sbn_p95 = $return_simulasi_personal_keuangan_solver1["rr_personal_keuangan_kupon_sbn_p95"];

      $rr_personal_properti = $return_simulasi_personal_properti["rr_personal_properti"];
      //++++++++++++++++++++++++++++++++
      //G.2.1. RR pada dashboard
      //pembayaran PPIP jika 1=anuitas; 2=kupon SBN/SBSN
      $setting_treatment_user = DB::table('setting_treatment_pembayaran_setelah_pensiun')
      ->where('id_user', $id_user)
      ->where('flag', 1)
      ->select('*')->get()[0];
      $pembayaran_ppip = ($setting_treatment_user->ppip === 'Beli Anuitas') ? 1 : 2;//Read pilihan pembayaran PPIP (pembayaran PPIP jika 1=anuitas; 2=kupon SBN/SBSN)
      if($pembayaran_ppip==1){
        $dashboard_rr_ppip_min = $rr_ppip_anuitas_p05[$counter_pensiun_minus_one_month];
        $dashboard_rr_ppip_med = $rr_ppip_anuitas_p50[$counter_pensiun_minus_one_month];
        $dashboard_rr_ppip_max = $rr_ppip_anuitas_p95[$counter_pensiun_minus_one_month];  
      } else {
        $dashboard_rr_ppip_min = $rr_ppip_kupon_sbn_p05[$counter_pensiun_minus_one_month];
        $dashboard_rr_ppip_med = $rr_ppip_kupon_sbn_p50[$counter_pensiun_minus_one_month];
        $dashboard_rr_ppip_max = $rr_ppip_kupon_sbn_p95[$counter_pensiun_minus_one_month];
      }

      //pembayaran personal keuangan jika 1=anuitas; 2=kupon SBN/SBSN
      $setting_treatment_user = DB::table('setting_treatment_pembayaran_setelah_pensiun')
      ->where('id_user', $id_user)
      ->where('flag', 1)
      ->select('*')->get()[0];

      $pembayaran_personal_keuangan=($setting_treatment_user->personal_pasar_keuangan === 'Beli Anuitas') ? 1 : 2;//Read pilihan pembayaran personal_keuangan (pembayaran personal_keuangan jika 1=anuitas; 2=kupon SBN/SBSN)
      if($pembayaran_personal_keuangan==1){
        $dashboard_rr_personal_keuangan_min = $rr_personal_keuangan_anuitas_p05[$counter_pensiun_minus_one_month];
        $dashboard_rr_personal_keuangan_med = $rr_personal_keuangan_anuitas_p50[$counter_pensiun_minus_one_month];
        $dashboard_rr_personal_keuangan_max = $rr_personal_keuangan_anuitas_p95[$counter_pensiun_minus_one_month];
      } else { 
        $dashboard_rr_personal_keuangan_min = $rr_personal_keuangan_kupon_sbn_p05[$counter_pensiun_minus_one_month];
        $dashboard_rr_personal_keuangan_med = $rr_personal_keuangan_kupon_sbn_p50[$counter_pensiun_minus_one_month];
        $dashboard_rr_personal_keuangan_max = $rr_personal_keuangan_kupon_sbn_p95[$counter_pensiun_minus_one_month];
      }
      $dashboard_rr_personal_properti = $rr_personal_properti[$counter_pensiun_minus_one_month];
      
      //echo json_encode($dashboard_rr_personal_keuangan_min, true);
      //die();

      //total rr
      $status_mp = $return_simulasi_ppmp['status_mp'];
      $rr_ppmp = $return_simulasi_ppmp['rr_ppmp'];
      //$status_mp=1 untuk hybrid ppmp ppip dan $status_mp=2 untuk ppip murni
      if ($status_mp==1){
        $dashboard_rr_ppmp = $rr_ppmp[$counter_pensiun_minus_one_month];
        
        $dashboard_rr_total_min = $dashboard_rr_ppmp +  $dashboard_rr_ppip_min + $dashboard_rr_personal_keuangan_min + $dashboard_rr_personal_properti;
        $dashboard_rr_total_med = $dashboard_rr_ppmp +  $dashboard_rr_ppip_med + $dashboard_rr_personal_keuangan_med + $dashboard_rr_personal_properti;
        $dashboard_rr_total_max = $dashboard_rr_ppmp +  $dashboard_rr_ppip_max + $dashboard_rr_personal_keuangan_max + $dashboard_rr_personal_properti;

      } else {
        $dashboard_rr_ppmp = null;

        $dashboard_rr_total_min = $dashboard_rr_ppip_min + $dashboard_rr_personal_keuangan_min + $dashboard_rr_personal_properti;
        $dashboard_rr_total_med = $dashboard_rr_ppip_med + $dashboard_rr_personal_keuangan_med + $dashboard_rr_personal_properti;
        $dashboard_rr_total_max = $dashboard_rr_ppip_max + $dashboard_rr_personal_keuangan_max + $dashboard_rr_personal_properti;
      }

      //++++++++++++++++++++++++++++++++
      //G.2.2. Penghasilan Bulanan pada dashboard
      $anuitas_ppip_p05 = $return_simulasi_ppip["anuitas_ppip_p05"];
      $anuitas_ppip_p50 = $return_simulasi_ppip["anuitas_ppip_p50"];
      $anuitas_ppip_p95 = $return_simulasi_ppip["anuitas_ppip_p95"];
      
      $kupon_sbn_ppip_p05 = $return_simulasi_ppip["kupon_sbn_ppip_p05"];
      $kupon_sbn_ppip_p50 = $return_simulasi_ppip["kupon_sbn_ppip_p50"];
      $kupon_sbn_ppip_p95 = $return_simulasi_ppip["kupon_sbn_ppip_p95"];

      $anuitas_personal_keuangan_p05 = $return_simulasi_personal_keuangan_solver1["anuitas_personal_keuangan_p05"];
      $anuitas_personal_keuangan_p50 = $return_simulasi_personal_keuangan_solver1["anuitas_personal_keuangan_p50"];
      $anuitas_personal_keuangan_p95 = $return_simulasi_personal_keuangan_solver1["anuitas_personal_keuangan_p95"];

      $kupon_sbn_personal_keuangan_p05 = $return_simulasi_personal_keuangan_solver1["kupon_sbn_personal_keuangan_p05"];
      $kupon_sbn_personal_keuangan_p50 = $return_simulasi_personal_keuangan_solver1["kupon_sbn_personal_keuangan_p50"];
      $kupon_sbn_personal_keuangan_p95 = $return_simulasi_personal_keuangan_solver1["kupon_sbn_personal_keuangan_p95"];

      $sewa_properti = $return_simulasi_personal_properti["sewa_properti"];
      //pembayaran PPIP jika 1=anuitas; 2=kupon SBN/SBSN
      if($pembayaran_ppip==1){
        $dashboard_penghasilan_bulanan_ppip_min = $anuitas_ppip_p05[$counter_pensiun_minus_one_month];
        $dashboard_penghasilan_bulanan_ppip_med = $anuitas_ppip_p50[$counter_pensiun_minus_one_month];
        $dashboard_penghasilan_bulanan_ppip_max = $anuitas_ppip_p95[$counter_pensiun_minus_one_month];
      } else {
        $dashboard_penghasilan_bulanan_ppip_min = $kupon_sbn_ppip_p05[$counter_pensiun_minus_one_month];
        $dashboard_penghasilan_bulanan_ppip_med = $kupon_sbn_ppip_p50[$counter_pensiun_minus_one_month];
        $dashboard_penghasilan_bulanan_ppip_max = $kupon_sbn_ppip_p95[$counter_pensiun_minus_one_month];
      }

      //pembayaran personal keuangan jika 1=anuitas; 2=kupon SBN/SBSN
      if($pembayaran_personal_keuangan==1){
        $dashboard_penghasilan_bulanan_personal_keuangan_min = $anuitas_personal_keuangan_p05[$counter_pensiun_minus_one_month];
        $dashboard_penghasilan_bulanan_personal_keuangan_med = $anuitas_personal_keuangan_p50[$counter_pensiun_minus_one_month];
        $dashboard_penghasilan_bulanan_personal_keuangan_max = $anuitas_personal_keuangan_p95[$counter_pensiun_minus_one_month];
      } else { 
        $dashboard_penghasilan_bulanan_personal_keuangan_min = $kupon_sbn_personal_keuangan_p05[$counter_pensiun_minus_one_month];
        $dashboard_penghasilan_bulanan_personal_keuangan_med = $kupon_sbn_personal_keuangan_p50[$counter_pensiun_minus_one_month];
        $dashboard_penghasilan_bulanan_personal_keuangan_max = $kupon_sbn_personal_keuangan_p95[$counter_pensiun_minus_one_month];
      }
      $dashboard_penghasilan_bulanan_personal_properti = $sewa_properti[$counter_pensiun_minus_one_month] / 12;

      //total penghasilan bulanan
      $jumlah_ppmp = $return_simulasi_ppmp['jumlah_ppmp'];
      //$status_mp=1 untuk hybrid ppmp ppip dan $status_mp=2 untuk ppip murni
      if ($status_mp==1){
        $dashboard_penghasilan_bulanan_ppmp = $jumlah_ppmp[$counter_pensiun_minus_one_month];
        
        $dashboard_penghasilan_bulanan_total_min = $dashboard_penghasilan_bulanan_ppmp +  $dashboard_penghasilan_bulanan_ppip_min + $dashboard_penghasilan_bulanan_personal_keuangan_min + $dashboard_penghasilan_bulanan_personal_properti;
        $dashboard_penghasilan_bulanan_total_med = $dashboard_penghasilan_bulanan_ppmp +  $dashboard_penghasilan_bulanan_ppip_med + $dashboard_penghasilan_bulanan_personal_keuangan_med + $dashboard_penghasilan_bulanan_personal_properti;
        $dashboard_penghasilan_bulanan_total_max = $dashboard_penghasilan_bulanan_ppmp +  $dashboard_penghasilan_bulanan_ppip_max + $dashboard_penghasilan_bulanan_personal_keuangan_max + $dashboard_penghasilan_bulanan_personal_properti;
      } else {
        $dashboard_penghasilan_bulanan_ppmp = null;
        
        $dashboard_penghasilan_bulanan_total_min = $dashboard_penghasilan_bulanan_ppip_min + $dashboard_penghasilan_bulanan_personal_keuangan_min + $dashboard_penghasilan_bulanan_personal_properti;
        $dashboard_penghasilan_bulanan_total_med = $dashboard_penghasilan_bulanan_ppip_med + $dashboard_penghasilan_bulanan_personal_keuangan_med + $dashboard_penghasilan_bulanan_personal_properti;
        $dashboard_penghasilan_bulanan_total_max = $dashboard_penghasilan_bulanan_ppip_max + $dashboard_penghasilan_bulanan_personal_keuangan_max + $dashboard_penghasilan_bulanan_personal_properti;
      }

      // +++++++++++++++++++++++++++++++
      //G.2.3. present value Penghasilan Bulanan pada dashboard
      //Input: Read sisa masa kerja saat membuka
      $tahun_ini=date('Y');//Read current date untuk tahun
      $bulan_ini=date('n');////Read current date untuk bulan
      $tahun_bulan_ini = $tahun_ini."_".$bulan_ini;
      $inflasi=0.04;//Read asumsi inflasi yang di admin

      $tahun_sisa_kerja = $sisa_kerja_tahun[$tahun_bulan_ini];//Read sisa masa kerja tahun untuk current date
      $bulan_sisa_kerja = $sisa_kerja_bulan[$tahun_bulan_ini];//Read sisa masa kerja bulan untuk current date
      
      //echo json_encode($bulan_sisa_kerja, true);
      //die();

      //$dashboard_penghasilan_bulanan_ppip_min_pv = $dashboard_penghasilan_bulanan_ppip_min / ((1+$inflasi)^($tahun_sisa_kerja+($bulan_sisa_kerja/12)));
      $dashboard_penghasilan_bulanan_ppip_min_pv = $dashboard_penghasilan_bulanan_ppip_min / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));
      $dashboard_penghasilan_bulanan_ppip_med_pv = $dashboard_penghasilan_bulanan_ppip_med / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));
      $dashboard_penghasilan_bulanan_ppip_max_pv = $dashboard_penghasilan_bulanan_ppip_max / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));
      
      //echo json_encode($dashboard_penghasilan_bulanan_ppip_min, true);
      //echo json_encode($dashboard_penghasilan_bulanan_ppip_min_pv, true);
      //die();

      $dashboard_penghasilan_bulanan_personal_keuangan_min_pv = $dashboard_penghasilan_bulanan_personal_keuangan_min / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));
      $dashboard_penghasilan_bulanan_personal_keuangan_med_pv = $dashboard_penghasilan_bulanan_personal_keuangan_med / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));
      $dashboard_penghasilan_bulanan_personal_keuangan_max_pv = $dashboard_penghasilan_bulanan_personal_keuangan_max / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));

      $dashboard_penghasilan_bulanan_personal_properti_pv = $dashboard_penghasilan_bulanan_personal_properti / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));

      //total penghasilan bulanan
      //$status_mp=1 untuk hybrid ppmp ppip dan $status_mp=2 untuk ppip murni
      if ($status_mp==1){
        $dashboard_penghasilan_bulanan_ppmp_pv = $dashboard_penghasilan_bulanan_ppmp / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));
        
        $dashboard_penghasilan_bulanan_total_min_pv = $dashboard_penghasilan_bulanan_ppmp_pv +  $dashboard_penghasilan_bulanan_ppip_min_pv + $dashboard_penghasilan_bulanan_personal_keuangan_min_pv + $dashboard_penghasilan_bulanan_personal_properti_pv;
        $dashboard_penghasilan_bulanan_total_med_pv = $dashboard_penghasilan_bulanan_ppmp_pv +  $dashboard_penghasilan_bulanan_ppip_med_pv + $dashboard_penghasilan_bulanan_personal_keuangan_med_pv + $dashboard_penghasilan_bulanan_personal_properti_pv;
        $dashboard_penghasilan_bulanan_total_max_pv = $dashboard_penghasilan_bulanan_ppmp_pv +  $dashboard_penghasilan_bulanan_ppip_max_pv + $dashboard_penghasilan_bulanan_personal_keuangan_max_pv + $dashboard_penghasilan_bulanan_personal_properti_pv;

      } else {
        $dashboard_penghasilan_bulanan_ppmp_pv = null;
        
        $dashboard_penghasilan_bulanan_total_min_pv = $dashboard_penghasilan_bulanan_ppip_min_pv + $dashboard_penghasilan_bulanan_personal_keuangan_min_pv + $dashboard_penghasilan_bulanan_personal_properti_pv;
        $dashboard_penghasilan_bulanan_total_med_pv = $dashboard_penghasilan_bulanan_ppip_med_pv + $dashboard_penghasilan_bulanan_personal_keuangan_med_pv + $dashboard_penghasilan_bulanan_personal_properti_pv;
        $dashboard_penghasilan_bulanan_total_max_pv = $dashboard_penghasilan_bulanan_ppip_max_pv + $dashboard_penghasilan_bulanan_personal_keuangan_max_pv + $dashboard_penghasilan_bulanan_personal_properti_pv;

      }
        
        //++++++++++++++++++++++++++++++++
        //G.2.4. kekayaan pada dashboard
        //under construction
        $dashboard_kekayaan_ppip_min = null;
        $dashboard_kekayaan_ppip_med = null;
        $dashboard_kekayaan_ppip_max = null;
                            
        $dashboard_kekayaan_ppmp = null;
         
        $dashboard_kekayaan_personal_properti = null;
        
        $dashboard_kekayaan_personal_keuangan_min = null;
        $dashboard_kekayaan_personal_keuangan_med = null;
        $dashboard_kekayaan_personal_keuangan_max = null;
                     
         $dashboard_kekayaan_total_min = null;
         $dashboard_kekayaan_total_med = null;
         $dashboard_kekayaan_total_max = null;
        
        //++++++++++++++++++++++++++++++++
        //G.2.5. preset value kekayaan pada dashboard
        //under construction
        $dashboard_kekayaan_ppip_min_pv = null;
        $dashboard_kekayaan_ppip_med_pv = null;
        $dashboard_kekayaan_ppip_max_pv = null;
                            
        $dashboard_kekayaan_ppmp_pv = null;
         
        $dashboard_kekayaan_personal_properti_pv = null;
        
        $dashboard_kekayaan_personal_keuangan_min_pv = null;
        $dashboard_kekayaan_personal_keuangan_med_pv = null;
        $dashboard_kekayaan_personal_keuangan_max_pv = null;
                     
         $dashboard_kekayaan_total_min_pv = null;
         $dashboard_kekayaan_total_med_pv = null;
         $dashboard_kekayaan_total_max_pv = null;
        
        //tidak perlu upload ke database
        //$this->uploadToDatabase("profil_personal_iuran", $id_user, $iuran_personal_keuangan); 
        
        
        
        
        
        return array(
          "pensiun" => $counter_pensiun,
          //"status_mp" => $status_mp,
          
          //RR
          "rr_ppip_minimal" => $dashboard_rr_ppip_min,
          "rr_ppip_median" => $dashboard_rr_ppip_med,
          "rr_ppip_maksimal" =>  $dashboard_rr_ppip_max,
            
          "rr_ppmp" => $dashboard_rr_ppmp,
          
          "rr_personal_properti" => $dashboard_rr_personal_properti,
          
          "rr_personal_keuangan_minimal" => $dashboard_rr_personal_keuangan_min,
          "rr_personal_keuangan_median" => $dashboard_rr_personal_keuangan_med,
          "rr_personal_keuangan_maksimal" =>  $dashboard_rr_personal_keuangan_max,
              
          "rr_total_minimal" => $dashboard_rr_total_min,
          "rr_total_median" => $dashboard_rr_total_med,
          "rr_total_maksimal" => $dashboard_rr_total_max,
            
          //Penghasilan Bulanan
          "penghasilan_ppip_minimal" => $dashboard_penghasilan_bulanan_ppip_min,
          "penghasilan_ppip_median" => $dashboard_penghasilan_bulanan_ppip_med,
          "penghasilan_ppip_maksimal" =>  $dashboard_penghasilan_bulanan_ppip_max,
                      
          "penghasilan_ppmp" => $dashboard_penghasilan_bulanan_ppmp,
          
          "penghasilan_personal_properti" => $dashboard_penghasilan_bulanan_personal_properti,
          
          "penghasilan_personal_keuangan_minimal" => $dashboard_penghasilan_bulanan_personal_keuangan_min,
          "penghasilan_personal_keuangan_median" => $dashboard_penghasilan_bulanan_personal_keuangan_med,
          "penghasilan_personal_keuangan_maksimal" =>  $dashboard_penghasilan_bulanan_personal_keuangan_max,
                      
          "penghasilan_total_minimal" => $dashboard_penghasilan_bulanan_total_min,
          "penghasilan_total_median" => $dashboard_penghasilan_bulanan_total_med,
          "penghasilan_total_maksimal" => $dashboard_penghasilan_bulanan_total_max,
          
          //Penghasilan Bulanan - present value
          "pv_penghasilan_ppip_minimal" => $dashboard_penghasilan_bulanan_ppip_min_pv,
          "pv_penghasilan_ppip_median" => $dashboard_penghasilan_bulanan_ppip_med_pv,
          "pv_penghasilan_ppip_maksimal" =>  $dashboard_penghasilan_bulanan_ppip_max_pv,
                      
          "pv_penghasilan_ppmp" => $dashboard_penghasilan_bulanan_ppmp_pv,
          
          "pv_penghasilan_personal_properti" => $dashboard_penghasilan_bulanan_personal_properti_pv,
          
          "pv_penghasilan_personal_keuangan_minimal" => $dashboard_penghasilan_bulanan_personal_keuangan_min_pv,
          "pv_penghasilan_personal_keuangan_median" => $dashboard_penghasilan_bulanan_personal_keuangan_med_pv,
          "pv_penghasilan_personal_keuangan_maksimal" =>  $dashboard_penghasilan_bulanan_personal_keuangan_max_pv,
                      
          "pv_penghasilan_total_minimal" => $dashboard_penghasilan_bulanan_total_min_pv,
          "pv_penghasilan_total_median" => $dashboard_penghasilan_bulanan_total_med_pv,
          "pv_penghasilan_total_maksimal" => $dashboard_penghasilan_bulanan_total_max_pv,
          
              //kekayaan
              //Penghasilan Bulanan
          "kekayaan_ppip_minimal" => $dashboard_kekayaan_ppip_min,
          "kekayaan_ppip_median" => $dashboard_kekayaan_ppip_med,
          "kekayaan_ppip_maksimal" =>  $dashboard_kekayaan_ppip_max,
                      
          "kekayaan_ppmp" => $dashboard_kekayaan_ppmp,
          
          "kekayaan_personal_properti" => $dashboard_kekayaan_personal_properti,
          
          "kekayaan_personal_keuangan_minimal" => $dashboard_kekayaan_personal_keuangan_min,
          "kekayaan_personal_keuangan_median" => $dashboard_kekayaan_personal_keuangan_med,
          "kekayaan_personal_keuangan_maksimal" =>  $dashboard_kekayaan_personal_keuangan_max,
                      
          "kekayaan_total_minimal" => $dashboard_kekayaan_total_min,
          "kekayaan_total_median" => $dashboard_kekayaan_total_med,
          "kekayaan_total_maksimal" => $dashboard_kekayaan_total_max,
          
          //kekayaan - present value
          "pv_kekayaan_ppip_minimal" => $dashboard_kekayaan_ppip_min_pv,
          "pv_kekayaan_ppip_median" => $dashboard_kekayaan_ppip_med_pv,
          "pv_kekayaan_ppip_maksimal" =>  $dashboard_kekayaan_ppip_max_pv,
                      
          "pv_kekayaan_ppmp" => $dashboard_kekayaan_ppmp_pv,
          
          "pv_kekayaan_personal_properti" => $dashboard_kekayaan_personal_properti_pv,
          
          "pv_kekayaan_personal_keuangan_minimal" => $dashboard_kekayaan_personal_keuangan_min_pv,
          "pv_kekayaan_personal_keuangan_median" => $dashboard_kekayaan_personal_keuangan_med_pv,
          "pv_kekayaan_personal_keuangan_maksimal" =>  $dashboard_kekayaan_personal_keuangan_max_pv,
                      
          "pv_kekayaan_total_minimal" => $dashboard_kekayaan_total_min_pv,
          "pv_kekayaan_total_median" => $dashboard_kekayaan_total_med_pv,
          "pv_kekayaan_total_maksimal" => $dashboard_kekayaan_total_max_pv,
      );
    }

    public function cari_iuran1($data_user, $id_user, $flag_pensiun, $sisa_kerja_tahun, $sisa_kerja_bulan, $return_simulasi_ppip, $return_simulasi_personal_properti, $return_simulasi_personal_keuangan_solver1, $return_simulasi_ppmp){
      // Sheet 1
      //Input: Read flag pensiun
      $counter_pensiun=""; //counter posisi pensiun
      $previous_flag_pensiun = null;
      $tahun_pensiun = null;
      
      for($year=2023; $year<=2100; $year++){
        for($month=1; $month<=12; $month++){
          $key = $year . "_" . $month;
          if ($year==2023 && $month==1){
            if ($flag_pensiun[$key]==1){
              $counter_pensiun = $key;// pada saat bulan ini sudah pensiun. jadi saldo yang ditampilkan adalah saldo awal
                $tahun_pensiun = $year;
            }
          } else {
            if ($flag_pensiun[$key]==1 && $previous_flag_pensiun==0){
              $counter_pensiun = $key; // pada saat bulan ini sudah pensiun. jadi saldo yang ditampilkan adalah saldo akhir untuk bulan sebelumnya.
               $tahun_pensiun = $year; 
            }
          }
          $previous_flag_pensiun = $flag_pensiun[$key];
        }
      }

      // Note: Bungkus loop year month untuk total rr

      $parts_counter_pensiun = explode("_", $counter_pensiun);
      $counter_pensiun_year = intval($parts_counter_pensiun[0]);
      $counter_pensiun_month = intval($parts_counter_pensiun[1]);
      // Mengurangi satu bulan
      if ($counter_pensiun_month == 1) {
          $counter_pensiun_year -= 1;
          $counter_pensiun_month = 12;
      } else {
          $counter_pensiun_month -= 1;
      }
      $counter_pensiun_minus_one_month = sprintf("%d_%d", $counter_pensiun_year, $counter_pensiun_month);
      //echo json_encode($counter_pensiun_minus_one_month, true);
      //die();

      //----------------------------------------------------------------------------
      //G.2. Hitung indikator dashboard - posisi saat pensiun
      $rr_ppip_anuitas_p05 = $return_simulasi_ppip["rr_ppip_anuitas_p05"];
      $rr_ppip_anuitas_p50 = $return_simulasi_ppip["rr_ppip_anuitas_p50"];
      $rr_ppip_anuitas_p95 = $return_simulasi_ppip["rr_ppip_anuitas_p95"];

      $rr_ppip_kupon_sbn_p05 = $return_simulasi_ppip["rr_ppip_kupon_sbn_p05"];
      $rr_ppip_kupon_sbn_p50 = $return_simulasi_ppip["rr_ppip_kupon_sbn_p50"];
      $rr_ppip_kupon_sbn_p95 = $return_simulasi_ppip["rr_ppip_kupon_sbn_p95"];
      
      $rr_personal_keuangan_anuitas_p05 = $return_simulasi_personal_keuangan_solver1["rr_personal_keuangan_anuitas_p05"];
      $rr_personal_keuangan_anuitas_p50 = $return_simulasi_personal_keuangan_solver1["rr_personal_keuangan_anuitas_p50"];
      $rr_personal_keuangan_anuitas_p95 = $return_simulasi_personal_keuangan_solver1["rr_personal_keuangan_anuitas_p95"];

      $rr_personal_keuangan_kupon_sbn_p05 = $return_simulasi_personal_keuangan_solver1["rr_personal_keuangan_kupon_sbn_p05"];
      $rr_personal_keuangan_kupon_sbn_p50 = $return_simulasi_personal_keuangan_solver1["rr_personal_keuangan_kupon_sbn_p50"];
      $rr_personal_keuangan_kupon_sbn_p95 = $return_simulasi_personal_keuangan_solver1["rr_personal_keuangan_kupon_sbn_p95"];

      $rr_personal_properti = $return_simulasi_personal_properti["rr_personal_properti"];
      //++++++++++++++++++++++++++++++++
      //G.2.1. RR pada dashboard
      //pembayaran PPIP jika 1=anuitas; 2=kupon SBN/SBSN
      $setting_treatment_user = DB::table('setting_treatment_pembayaran_setelah_pensiun')
      ->where('id_user', $id_user)
      ->where('flag', 1)
      ->select('*')->get()[0];
      $pembayaran_ppip = ($setting_treatment_user->ppip === 'Beli Anuitas') ? 1 : 2;//Read pilihan pembayaran PPIP (pembayaran PPIP jika 1=anuitas; 2=kupon SBN/SBSN)
      if($pembayaran_ppip==1){
        $dashboard_rr_ppip_min = $rr_ppip_anuitas_p05[$counter_pensiun_minus_one_month];
        $dashboard_rr_ppip_med = $rr_ppip_anuitas_p50[$counter_pensiun_minus_one_month];
        $dashboard_rr_ppip_max = $rr_ppip_anuitas_p95[$counter_pensiun_minus_one_month];  
      } else {
        $dashboard_rr_ppip_min = $rr_ppip_kupon_sbn_p05[$counter_pensiun_minus_one_month];
        $dashboard_rr_ppip_med = $rr_ppip_kupon_sbn_p50[$counter_pensiun_minus_one_month];
        $dashboard_rr_ppip_max = $rr_ppip_kupon_sbn_p95[$counter_pensiun_minus_one_month];
      }

      //pembayaran personal keuangan jika 1=anuitas; 2=kupon SBN/SBSN
      $setting_treatment_user = DB::table('setting_treatment_pembayaran_setelah_pensiun')
      ->where('id_user', $id_user)
      ->where('flag', 1)
      ->select('*')->get()[0];

      $pembayaran_personal_keuangan=($setting_treatment_user->personal_pasar_keuangan === 'Beli Anuitas') ? 1 : 2;//Read pilihan pembayaran personal_keuangan (pembayaran personal_keuangan jika 1=anuitas; 2=kupon SBN/SBSN)
      if($pembayaran_personal_keuangan==1){
        $dashboard_rr_personal_keuangan_min = $rr_personal_keuangan_anuitas_p05[$counter_pensiun_minus_one_month];
        $dashboard_rr_personal_keuangan_med = $rr_personal_keuangan_anuitas_p50[$counter_pensiun_minus_one_month];
        $dashboard_rr_personal_keuangan_max = $rr_personal_keuangan_anuitas_p95[$counter_pensiun_minus_one_month];
      } else { 
        $dashboard_rr_personal_keuangan_min = $rr_personal_keuangan_kupon_sbn_p05[$counter_pensiun_minus_one_month];
        $dashboard_rr_personal_keuangan_med = $rr_personal_keuangan_kupon_sbn_p50[$counter_pensiun_minus_one_month];
        $dashboard_rr_personal_keuangan_max = $rr_personal_keuangan_kupon_sbn_p95[$counter_pensiun_minus_one_month];
      }
      $dashboard_rr_personal_properti = $rr_personal_properti[$counter_pensiun_minus_one_month];
      
      //echo json_encode($dashboard_rr_personal_keuangan_min, true);
      //die();

      //total rr
      $status_mp = $return_simulasi_ppmp['status_mp'];
      $rr_ppmp = $return_simulasi_ppmp['rr_ppmp'];
      
      //echo json_encode($rr_ppmp, true);
      //die();
        
      //$status_mp=1 untuk hybrid ppmp ppip dan $status_mp=2 untuk ppip murni
      if ($status_mp[$tahun_pensiun]==1){
        $dashboard_rr_ppmp = $rr_ppmp[$counter_pensiun_minus_one_month];
        
        $dashboard_rr_total_min = $dashboard_rr_ppmp +  $dashboard_rr_ppip_min + $dashboard_rr_personal_keuangan_min + $dashboard_rr_personal_properti;
        $dashboard_rr_total_med = $dashboard_rr_ppmp +  $dashboard_rr_ppip_med + $dashboard_rr_personal_keuangan_med + $dashboard_rr_personal_properti;
        $dashboard_rr_total_max = $dashboard_rr_ppmp +  $dashboard_rr_ppip_max + $dashboard_rr_personal_keuangan_max + $dashboard_rr_personal_properti;

      } else {
        $dashboard_rr_ppmp = null;

        $dashboard_rr_total_min = $dashboard_rr_ppip_min + $dashboard_rr_personal_keuangan_min + $dashboard_rr_personal_properti;
        $dashboard_rr_total_med = $dashboard_rr_ppip_med + $dashboard_rr_personal_keuangan_med + $dashboard_rr_personal_properti;
        $dashboard_rr_total_max = $dashboard_rr_ppip_max + $dashboard_rr_personal_keuangan_max + $dashboard_rr_personal_properti;
      }

      //++++++++++++++++++++++++++++++++
      //G.2.2. Penghasilan Bulanan pada dashboard
      $anuitas_ppip_p05 = $return_simulasi_ppip["anuitas_ppip_p05"];
      $anuitas_ppip_p50 = $return_simulasi_ppip["anuitas_ppip_p50"];
      $anuitas_ppip_p95 = $return_simulasi_ppip["anuitas_ppip_p95"];
      
      $kupon_sbn_ppip_p05 = $return_simulasi_ppip["kupon_sbn_ppip_p05"];
      $kupon_sbn_ppip_p50 = $return_simulasi_ppip["kupon_sbn_ppip_p50"];
      $kupon_sbn_ppip_p95 = $return_simulasi_ppip["kupon_sbn_ppip_p95"];

      $anuitas_personal_keuangan_p05 = $return_simulasi_personal_keuangan_solver1["anuitas_personal_keuangan_p05"];
      $anuitas_personal_keuangan_p50 = $return_simulasi_personal_keuangan_solver1["anuitas_personal_keuangan_p50"];
      $anuitas_personal_keuangan_p95 = $return_simulasi_personal_keuangan_solver1["anuitas_personal_keuangan_p95"];

      $kupon_sbn_personal_keuangan_p05 = $return_simulasi_personal_keuangan_solver1["kupon_sbn_personal_keuangan_p05"];
      $kupon_sbn_personal_keuangan_p50 = $return_simulasi_personal_keuangan_solver1["kupon_sbn_personal_keuangan_p50"];
      $kupon_sbn_personal_keuangan_p95 = $return_simulasi_personal_keuangan_solver1["kupon_sbn_personal_keuangan_p95"];

      $sewa_properti = $return_simulasi_personal_properti["sewa_properti"];
      //pembayaran PPIP jika 1=anuitas; 2=kupon SBN/SBSN
      if($pembayaran_ppip==1){
        $dashboard_penghasilan_bulanan_ppip_min = $anuitas_ppip_p05[$counter_pensiun_minus_one_month];
        $dashboard_penghasilan_bulanan_ppip_med = $anuitas_ppip_p50[$counter_pensiun_minus_one_month];
        $dashboard_penghasilan_bulanan_ppip_max = $anuitas_ppip_p95[$counter_pensiun_minus_one_month];
      } else {
        $dashboard_penghasilan_bulanan_ppip_min = $kupon_sbn_ppip_p05[$counter_pensiun_minus_one_month];
        $dashboard_penghasilan_bulanan_ppip_med = $kupon_sbn_ppip_p50[$counter_pensiun_minus_one_month];
        $dashboard_penghasilan_bulanan_ppip_max = $kupon_sbn_ppip_p95[$counter_pensiun_minus_one_month];
      }

      //pembayaran personal keuangan jika 1=anuitas; 2=kupon SBN/SBSN
      if($pembayaran_personal_keuangan==1){
        $dashboard_penghasilan_bulanan_personal_keuangan_min = $anuitas_personal_keuangan_p05[$counter_pensiun_minus_one_month];
        $dashboard_penghasilan_bulanan_personal_keuangan_med = $anuitas_personal_keuangan_p50[$counter_pensiun_minus_one_month];
        $dashboard_penghasilan_bulanan_personal_keuangan_max = $anuitas_personal_keuangan_p95[$counter_pensiun_minus_one_month];
      } else { 
        $dashboard_penghasilan_bulanan_personal_keuangan_min = $kupon_sbn_personal_keuangan_p05[$counter_pensiun_minus_one_month];
        $dashboard_penghasilan_bulanan_personal_keuangan_med = $kupon_sbn_personal_keuangan_p50[$counter_pensiun_minus_one_month];
        $dashboard_penghasilan_bulanan_personal_keuangan_max = $kupon_sbn_personal_keuangan_p95[$counter_pensiun_minus_one_month];
      }
      $dashboard_penghasilan_bulanan_personal_properti = $sewa_properti[$counter_pensiun_minus_one_month] / 12;

      //total penghasilan bulanan
      $jumlah_ppmp = $return_simulasi_ppmp['jumlah_ppmp'];
      //$status_mp=1 untuk hybrid ppmp ppip dan $status_mp=2 untuk ppip murni
      if ($status_mp[$tahun_pensiun]==1){
        $dashboard_penghasilan_bulanan_ppmp = $jumlah_ppmp[$counter_pensiun_minus_one_month];
        
        $dashboard_penghasilan_bulanan_total_min = $dashboard_penghasilan_bulanan_ppmp +  $dashboard_penghasilan_bulanan_ppip_min + $dashboard_penghasilan_bulanan_personal_keuangan_min + $dashboard_penghasilan_bulanan_personal_properti;
        $dashboard_penghasilan_bulanan_total_med = $dashboard_penghasilan_bulanan_ppmp +  $dashboard_penghasilan_bulanan_ppip_med + $dashboard_penghasilan_bulanan_personal_keuangan_med + $dashboard_penghasilan_bulanan_personal_properti;
        $dashboard_penghasilan_bulanan_total_max = $dashboard_penghasilan_bulanan_ppmp +  $dashboard_penghasilan_bulanan_ppip_max + $dashboard_penghasilan_bulanan_personal_keuangan_max + $dashboard_penghasilan_bulanan_personal_properti;
      } else {
        $dashboard_penghasilan_bulanan_ppmp = null;
        
        $dashboard_penghasilan_bulanan_total_min = $dashboard_penghasilan_bulanan_ppip_min + $dashboard_penghasilan_bulanan_personal_keuangan_min + $dashboard_penghasilan_bulanan_personal_properti;
        $dashboard_penghasilan_bulanan_total_med = $dashboard_penghasilan_bulanan_ppip_med + $dashboard_penghasilan_bulanan_personal_keuangan_med + $dashboard_penghasilan_bulanan_personal_properti;
        $dashboard_penghasilan_bulanan_total_max = $dashboard_penghasilan_bulanan_ppip_max + $dashboard_penghasilan_bulanan_personal_keuangan_max + $dashboard_penghasilan_bulanan_personal_properti;
      }

      // +++++++++++++++++++++++++++++++
      //G.2.3. present value Penghasilan Bulanan pada dashboard
      //Input: Read sisa masa kerja saat membuka
      $tahun_ini=date('Y');//Read current date untuk tahun
      $bulan_ini=date('n');////Read current date untuk bulan
      $tahun_bulan_ini = $tahun_ini."_".$bulan_ini;
      $inflasi=0.04;//Read asumsi inflasi yang di admin

      $tahun_sisa_kerja = $sisa_kerja_tahun[$tahun_bulan_ini];//Read sisa masa kerja tahun untuk current date
      $bulan_sisa_kerja = $sisa_kerja_bulan[$tahun_bulan_ini];//Read sisa masa kerja bulan untuk current date
      
      //echo json_encode($bulan_sisa_kerja, true);
      //die();

      //$dashboard_penghasilan_bulanan_ppip_min_pv = $dashboard_penghasilan_bulanan_ppip_min / ((1+$inflasi)^($tahun_sisa_kerja+($bulan_sisa_kerja/12)));
      $dashboard_penghasilan_bulanan_ppip_min_pv = $dashboard_penghasilan_bulanan_ppip_min / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));
      $dashboard_penghasilan_bulanan_ppip_med_pv = $dashboard_penghasilan_bulanan_ppip_med / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));
      $dashboard_penghasilan_bulanan_ppip_max_pv = $dashboard_penghasilan_bulanan_ppip_max / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));
      
      //echo json_encode($dashboard_penghasilan_bulanan_ppip_min, true);
      //echo json_encode($dashboard_penghasilan_bulanan_ppip_min_pv, true);
      //die();

      $dashboard_penghasilan_bulanan_personal_keuangan_min_pv = $dashboard_penghasilan_bulanan_personal_keuangan_min / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));
      $dashboard_penghasilan_bulanan_personal_keuangan_med_pv = $dashboard_penghasilan_bulanan_personal_keuangan_med / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));
      $dashboard_penghasilan_bulanan_personal_keuangan_max_pv = $dashboard_penghasilan_bulanan_personal_keuangan_max / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));

      $dashboard_penghasilan_bulanan_personal_properti_pv = $dashboard_penghasilan_bulanan_personal_properti / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));

      //total penghasilan bulanan
      //$status_mp=1 untuk hybrid ppmp ppip dan $status_mp=2 untuk ppip murni
      if ($status_mp[$tahun_pensiun]==1){
        $dashboard_penghasilan_bulanan_ppmp_pv = $dashboard_penghasilan_bulanan_ppmp / pow((1+$inflasi),($tahun_sisa_kerja+($bulan_sisa_kerja/12)));
        
        $dashboard_penghasilan_bulanan_total_min_pv = $dashboard_penghasilan_bulanan_ppmp_pv +  $dashboard_penghasilan_bulanan_ppip_min_pv + $dashboard_penghasilan_bulanan_personal_keuangan_min_pv + $dashboard_penghasilan_bulanan_personal_properti_pv;
        $dashboard_penghasilan_bulanan_total_med_pv = $dashboard_penghasilan_bulanan_ppmp_pv +  $dashboard_penghasilan_bulanan_ppip_med_pv + $dashboard_penghasilan_bulanan_personal_keuangan_med_pv + $dashboard_penghasilan_bulanan_personal_properti_pv;
        $dashboard_penghasilan_bulanan_total_max_pv = $dashboard_penghasilan_bulanan_ppmp_pv +  $dashboard_penghasilan_bulanan_ppip_max_pv + $dashboard_penghasilan_bulanan_personal_keuangan_max_pv + $dashboard_penghasilan_bulanan_personal_properti_pv;

      } else {
        $dashboard_penghasilan_bulanan_ppmp_pv = null;
        
        $dashboard_penghasilan_bulanan_total_min_pv = $dashboard_penghasilan_bulanan_ppip_min_pv + $dashboard_penghasilan_bulanan_personal_keuangan_min_pv + $dashboard_penghasilan_bulanan_personal_properti_pv;
        $dashboard_penghasilan_bulanan_total_med_pv = $dashboard_penghasilan_bulanan_ppip_med_pv + $dashboard_penghasilan_bulanan_personal_keuangan_med_pv + $dashboard_penghasilan_bulanan_personal_properti_pv;
        $dashboard_penghasilan_bulanan_total_max_pv = $dashboard_penghasilan_bulanan_ppip_max_pv + $dashboard_penghasilan_bulanan_personal_keuangan_max_pv + $dashboard_penghasilan_bulanan_personal_properti_pv;

      }
        
        //++++++++++++++++++++++++++++++++
        //G.2.4. kekayaan pada dashboard
        //under construction
        $dashboard_kekayaan_ppip_min = null;
        $dashboard_kekayaan_ppip_med = null;
        $dashboard_kekayaan_ppip_max = null;
                            
        $dashboard_kekayaan_ppmp = null;
         
        $dashboard_kekayaan_personal_properti = null;
        
        $dashboard_kekayaan_personal_keuangan_min = null;
        $dashboard_kekayaan_personal_keuangan_med = null;
        $dashboard_kekayaan_personal_keuangan_max = null;
                     
         $dashboard_kekayaan_total_min = null;
         $dashboard_kekayaan_total_med = null;
         $dashboard_kekayaan_total_max = null;
        
        //++++++++++++++++++++++++++++++++
        //G.2.5. preset value kekayaan pada dashboard
        //under construction
        $dashboard_kekayaan_ppip_min_pv = null;
        $dashboard_kekayaan_ppip_med_pv = null;
        $dashboard_kekayaan_ppip_max_pv = null;
                            
        $dashboard_kekayaan_ppmp_pv = null;
         
        $dashboard_kekayaan_personal_properti_pv = null;
        
        $dashboard_kekayaan_personal_keuangan_min_pv = null;
        $dashboard_kekayaan_personal_keuangan_med_pv = null;
        $dashboard_kekayaan_personal_keuangan_max_pv = null;
                     
         $dashboard_kekayaan_total_min_pv = null;
         $dashboard_kekayaan_total_med_pv = null;
         $dashboard_kekayaan_total_max_pv = null;
        
        
       // $rr_total_rr=
        //$this->uploadToDatabase("profil_personal_iuran", $id_user, $iuran_personal_keuangan); 
        
        
        
        
        
      return array(
          "pensiun" => $counter_pensiun,
          //"status_mp" => $status_mp,
          
          //RR
          "rr_ppip_minimal" => $dashboard_rr_ppip_min,
          "rr_ppip_median" => $dashboard_rr_ppip_med,
          "rr_ppip_maksimal" =>  $dashboard_rr_ppip_max,
            
          "rr_ppmp" => $dashboard_rr_ppmp,
          
          "rr_personal_properti" => $dashboard_rr_personal_properti,
          
          "rr_personal_keuangan_minimal" => $dashboard_rr_personal_keuangan_min,
          "rr_personal_keuangan_median" => $dashboard_rr_personal_keuangan_med,
          "rr_personal_keuangan_maksimal" =>  $dashboard_rr_personal_keuangan_max,
              
          "rr_total_minimal" => $dashboard_rr_total_min,
          "rr_total_median" => $dashboard_rr_total_med,
          "rr_total_maksimal" => $dashboard_rr_total_max,
            
          //Penghasilan Bulanan
          "penghasilan_ppip_minimal" => $dashboard_penghasilan_bulanan_ppip_min,
          "penghasilan_ppip_median" => $dashboard_penghasilan_bulanan_ppip_med,
          "penghasilan_ppip_maksimal" =>  $dashboard_penghasilan_bulanan_ppip_max,
                      
          "penghasilan_ppmp" => $dashboard_penghasilan_bulanan_ppmp,
          
          "penghasilan_personal_properti" => $dashboard_penghasilan_bulanan_personal_properti,
          
          "penghasilan_personal_keuangan_minimal" => $dashboard_penghasilan_bulanan_personal_keuangan_min,
          "penghasilan_personal_keuangan_median" => $dashboard_penghasilan_bulanan_personal_keuangan_med,
          "penghasilan_personal_keuangan_maksimal" =>  $dashboard_penghasilan_bulanan_personal_keuangan_max,
                      
          "penghasilan_total_minimal" => $dashboard_penghasilan_bulanan_total_min,
          "penghasilan_total_median" => $dashboard_penghasilan_bulanan_total_med,
          "penghasilan_total_maksimal" => $dashboard_penghasilan_bulanan_total_max,
          
          //Penghasilan Bulanan - present value
          "pv_penghasilan_ppip_minimal" => $dashboard_penghasilan_bulanan_ppip_min_pv,
          "pv_penghasilan_ppip_median" => $dashboard_penghasilan_bulanan_ppip_med_pv,
          "pv_penghasilan_ppip_maksimal" =>  $dashboard_penghasilan_bulanan_ppip_max_pv,
                      
          "pv_penghasilan_ppmp" => $dashboard_penghasilan_bulanan_ppmp_pv,
          
          "pv_penghasilan_personal_properti" => $dashboard_penghasilan_bulanan_personal_properti_pv,
          
          "pv_penghasilan_personal_keuangan_minimal" => $dashboard_penghasilan_bulanan_personal_keuangan_min_pv,
          "pv_penghasilan_personal_keuangan_median" => $dashboard_penghasilan_bulanan_personal_keuangan_med_pv,
          "pv_penghasilan_personal_keuangan_maksimal" =>  $dashboard_penghasilan_bulanan_personal_keuangan_max_pv,
                      
          "pv_penghasilan_total_minimal" => $dashboard_penghasilan_bulanan_total_min_pv,
          "pv_penghasilan_total_median" => $dashboard_penghasilan_bulanan_total_med_pv,
          "pv_penghasilan_total_maksimal" => $dashboard_penghasilan_bulanan_total_max_pv,
          
              //kekayaan
              //Penghasilan Bulanan
          "kekayaan_ppip_minimal" => $dashboard_kekayaan_ppip_min,
          "kekayaan_ppip_median" => $dashboard_kekayaan_ppip_med,
          "kekayaan_ppip_maksimal" =>  $dashboard_kekayaan_ppip_max,
                      
          "kekayaan_ppmp" => $dashboard_kekayaan_ppmp,
          
          "kekayaan_personal_properti" => $dashboard_kekayaan_personal_properti,
          
          "kekayaan_personal_keuangan_minimal" => $dashboard_kekayaan_personal_keuangan_min,
          "kekayaan_personal_keuangan_median" => $dashboard_kekayaan_personal_keuangan_med,
          "kekayaan_personal_keuangan_maksimal" =>  $dashboard_kekayaan_personal_keuangan_max,
                      
          "kekayaan_total_minimal" => $dashboard_kekayaan_total_min,
          "kekayaan_total_median" => $dashboard_kekayaan_total_med,
          "kekayaan_total_maksimal" => $dashboard_kekayaan_total_max,
          
          //kekayaan - present value
          "pv_kekayaan_ppip_minimal" => $dashboard_kekayaan_ppip_min_pv,
          "pv_kekayaan_ppip_median" => $dashboard_kekayaan_ppip_med_pv,
          "pv_kekayaan_ppip_maksimal" =>  $dashboard_kekayaan_ppip_max_pv,
                      
          "pv_kekayaan_ppmp" => $dashboard_kekayaan_ppmp_pv,
          
          "pv_kekayaan_personal_properti" => $dashboard_kekayaan_personal_properti_pv,
          
          "pv_kekayaan_personal_keuangan_minimal" => $dashboard_kekayaan_personal_keuangan_min_pv,
          "pv_kekayaan_personal_keuangan_median" => $dashboard_kekayaan_personal_keuangan_med_pv,
          "pv_kekayaan_personal_keuangan_maksimal" =>  $dashboard_kekayaan_personal_keuangan_max_pv,
                      
          "pv_kekayaan_total_minimal" => $dashboard_kekayaan_total_min_pv,
          "pv_kekayaan_total_median" => $dashboard_kekayaan_total_med_pv,
          "pv_kekayaan_total_maksimal" => $dashboard_kekayaan_total_max_pv,
      );
    }
    public function uploadToDatabase($table, $id_user, $data){
      $check_table = DB::table($table)
      ->where([
          ['id_user', '=', $id_user]
        ])
      ->get()->toArray();

      $data_table = array(
        'id'=> (string) Str::uuid(),
        'id_user' => $id_user,
        'flag' => 1,
      );
      
      if (count($check_table) > 0) {
        DB::table($table)
        ->where([['id_user', '=', $id_user]])->update([
            'flag' => 0,
        ]);

        DB::table($table)->insert($data_table+$data);
      } else {;
        DB::table($table)->insert($data_table+$data);
      }
    }
}


