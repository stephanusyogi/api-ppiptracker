<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use DateTime;
use DB;

class MontecarloController extends Controller
{

    public function montecarlo_count($id){
        // Data User
        $users = User::where('id',$id)->first();
        $gaji = $users->gaji;
        $saldo = $users->saldo_ppip;
        $tgl_lahir = $users->tgl_lahir;
        $umur_pensiun = $users->usia_pensiun;
        $jenis_mp = $users->jenis_pensiun;
        // Array Umur Terkini
        list($umurkini[1],$umurkini[2],$umurkini[3])=$this->umur_kini($tgl_lahir);


        // Sisa Masa Dinas
        $sisa_dinas=$umur_pensiun - $umurkini[1];

        $tahunini= date('Y');

        // Tabel Tahun
        $tabel_tahun[1] = $tahunini;
        for($i=2; $i<=$sisa_dinas; $i++){
            $tabel_tahun[$i] = $tabel_tahun[$i-1] + 1;
        }

        // Tabel Usia
        for ($i=1; $i<=$sisa_dinas; $i++){
            $tabel_usia[$i] = $umurkini[1] + $i-1;
        }

        // Tabel Sisa Masa Dinas
        for ($i=0; $i < $sisa_dinas; $i++){
            $tabel_sisa[$i+1] = $sisa_dinas-$i;
        }
        
        // Tabel Cluster
        if($sisa_dinas == 1){

            $cluster[$sisa_dinas] = 7;

        } else if($sisa_dinas == 2){

            $cluster[$sisa_dinas] = 7;
            $cluster[$sisa_dinas-1] = 7;

        } else if($sisa_dinas<=5){

            $cluster[$sisa_dinas] = 7;
            $cluster[$sisa_dinas-1] = 7;

            for($i=$sisa_dinas-2 ;$i>=1; $i--){
                $cluster[$i] = 6;
            }

        } else {

            $cluster[$sisa_dinas] = 7;
            $cluster[$sisa_dinas-1] = 7;

            for($i=$sisa_dinas-2; $i>$sisa_dinas-5; $i--){
                $cluster[$i] = 6;
            }

            $j = 5;
            $jml = 1;

            for ($i=$sisa_dinas-5; $i>=1; $i--){	

                if($jml>5 and $j>1){

                    $j--;
                    $jml = 1;

                }

                $cluster[$i] = $j;
                $jml++;
            }

        }


        // Tabel Proyeksi Gaji
        $kenaikan_gaji = DB::table('asumsi')->where('nama_asumsi', 'kenaikan_gaji')->value('nilai_asumsi');
        for ($i=1; $i<=$sisa_dinas; $i++){
            
            if($i==1){

                $tabel_gaji[$i]=round($gaji,2);

            } else{

                $tabel_gaji[$i]=round($tabel_gaji[$i-1] * (1 + $kenaikan_gaji),2);

            }
        }

        // Asumsi Iuran
        if ($jenis_mp == 'ppip_murni') {
            $iuran = DB::table('asumsi')->where('nama_asumsi', 'iuran_ppip_murni')->value('nilai_asumsi');
        } else {
            $iuran = DB::table('asumsi')->where('nama_asumsi', 'iuran_ppip_hybrid')->value('nilai_asumsi');
        }

        // --------------------------------------------------------------------

        // MonteCarlo
        $jml_iterasi_mc=10000;

        // Nilai Return
        for($i=1; $i<=7; $i++){
            $return_cluster[$i] = DB::table('users')->where("id", $id)->value("return_cluster$i");
        }

        // Risk
        for($i=1; $i<=7; $i++){
            $risk_cluster[$i] = DB::table('mvo')->where("mvo_return", $return_cluster[$i])->value("mvo_risk");
        }

        // Data Normal Inverse
        $res_norm_inv = DB::table('distribusi_normal')->get();
        $id_distribusi = 1;
        foreach ($res_norm_inv as $key) {
            $norm_inv[$id_distribusi] = $key->norm_inv; 
            $id_distribusi++;
        }
        
        // Bulan & Tanggal Sekarang
        $bulan_ini=intval(date("m"));
  		$hari_ini=intval(date("d"));

		for ($i=0; $i<=$sisa_dinas; $i++){

			for($j=1; $j<=$jml_iterasi_mc; $j++){

				if($i==0){

					$nab[$i][$j] = 100; // NAB Awal
					$return_mc[$i][$j] = 0; // Return MonteCarlo Awal
					$saldo_mc[$i][$j] = 0; // Saldo MonteCarlo Dummy

                // Kondisi Tahun Ini
				}else if($i==1){ 
					
					//generate random variable
					$acak=mt_rand(0,10000);

  					//Hitung NAB
					$nab[$i][$j] = round($nab[$i-1][$j] * (1 + ($return_cluster[$cluster[$i]] / 100) + (($risk_cluster[$cluster[$i]] / 100) * $norm_inv[$acak+1]) ),2);

					//Hitung Return
					$return_mc[$i][$j] = round(($nab[$i][$j] - $nab[$i-1][$j]) / $nab[$i-1][$j],2);

					//Hitung Saldo Awal Real
					$saldo_mc[$i][$j] = $saldo;

					//Hitung Tambahan Saldo Tahun Ini Sesuai Kondisi Bulan
                    // Jika bulan ini Desember, maka kalau belum tanggal gajian saldo ditambah 1x iuran dan nilai kenaikan investasi diabaikan
					if($bulan_ini==12){ 

                        // Jika hari ini sebelum tanggal 25 atau sebelum tanggal gajian
						if($hari_ini<25){

							$saldo_mc[$i][$j]=$saldo_mc[$i][$j] + $tabel_gaji[$i] * $iuran;

						}

                    // Jika bulan ini Juli - November, maka hanya ada tambahan iuran dan nilai kenaikan investasi diabaikan
					} elseif ($bulan_ini>6) {

                        //Setelah tanggal gajian (Setelah / saat tanggal 25)
						if($hari_ini>=25){ 

                            //Tambahan iuran
							$tambahan_iuran = $tabel_gaji[$i] * $iuran * (12 - $bulan_ini);
							$tambahan_pengembangan=0;
							$saldo_mc[$i][$j]=$saldo_mc[$i][$j] + $tambahan_iuran + $tambahan_pengembangan;

                        //Sebelum tanggal gajian, maka nilai iuran ditambah 1 
						} else{ 

                            //Tambahan iuran
							$tambahan_iuran=$tabel_gaji[$i] * $iuran * (12 - $bulan_ini-1); 
							$tambahan_pengembangan=0;
							$saldo_mc[$i][$j]=$saldo_mc[$i][$j] + $tambahan_iuran + $tambahan_pengembangan;
					
                        }
                        
					// Jika bulan ini Januari - Juni, maka hanya ada tambahan iuran dan nilai kenaikan investasi diabaikan
					} else{

                        //Counter iuran yang sudah dibayar
						$zz=0;

                        //Setelah tanggal gajian
						if($hari_ini>=25){ 

                            // Kalibrasi Setengah Tahun
							for($y=$bulan_ini; $y<=6; $y++){

								$saldo_mc[$i][$j] = $saldo_mc[$i][$j] + $tabel_gaji[$i] * $iuran;
								$zz++;

							}

                            //Tambahan iuran yang telah dikalibrasi selama setengah tahun
							$tambahan_iuran = $tabel_gaji[$i] * $iuran * (12 - $bulan_ini + $zz);
							$tambahan_pengembangan = $saldo_mc[$i][$j] * ($return_mc[$i][$j] / 2);
							$saldo_mc[$i][$j] = $saldo_mc[$i][$j] + $tambahan_iuran + $tambahan_pengembangan;

                        //Sebelum tanggal gajian (iuran ditambah 1)
						} else{ 

                            //Ditambah 1 iterasi karena belum gajian
                            // Kalibrasi Setengah Tahun
							for($y=$bulan_ini-1;$y<=6;$y++){ 
								$saldo_mc[$i][$j] = $saldo_mc[$i][$j] + $tabel_gaji[$i] * $iuran;
								$zz++;
							}

                            //Tambahan iuran yang sudah dikalibrasi selama setengah tahun
							$tambahan_iuran = $tabel_gaji[$i] * $iuran * (12 - $bulan_ini + $zz);
							$tambahan_pengembangan = $saldo_mc[$i][$j] * ($return_mc[$i][$j] / 2);
							$saldo_mc[$i][$j] = $saldo_mc[$i][$j] + $tambahan_iuran + $tambahan_pengembangan;
						}
					}

                // Kondisi Tahun Setelahnya (Mendatang)
				} else { 

					//generate random variable
					$acak=mt_rand(0,10000);

  					//Hitung NAB
					$nab[$i][$j] = round($nab[$i-1][$j] * (1 + ($return_cluster[$cluster[$i]] / 100)+(($risk_cluster[$cluster[$i]] / 100) * $norm_inv[$acak+1])),2);

					//Hitung Return
					$return_mc[$i][$j] = round(($nab[$i][$j] - $nab[$i-1][$j]) / $nab[$i-1][$j],2);

					//Saldo Montecarlo real tahun selanjutnya
					$saldo_mc[$i][$j] = ($saldo_mc[$i-1][$j] * (1+$return_mc[$i][$j])) + (($tabel_gaji[$i] * $iuran * 12) * (1 + $return_mc[$i][$j] / 2));
					
				}

				$saldo_mc[$i][$j] = round($saldo_mc[$i][$j],2);
			}

		}

        // Percentile Saldo
        $final_result_mc = array();

        $id = $sisa_dinas; 
        for($i=$sisa_dinas; $i>0; $i--){

			$k=0;
			for($j=1; $j<=$jml_iterasi_mc; $j++){

                // Temporary Saldo Array (Belum Urut)
				$percentile_temp1[$k]=$saldo_mc[$i][$j];
				$k++;

			}

			sort($percentile_temp1);

            //Setelah disort, array dimulai dari nol. sehingga perlu dikembalikan lagi urutan arraynya
			$m=0;
			for($n=1;$n<=$jml_iterasi_mc;$n++){ //jumlahnya sesuai $jml_iterasi_mc
                
                // Temporary Saldo Array (Sudah Urut)
				$percentile_temp2[$n]=$percentile_temp1[$m];
				$m++;
                
			}
			
			$percentile_95[$i] = $percentile_temp2[round(0.95 * $jml_iterasi_mc)];
			$percentile_50[$i] = $percentile_temp2[round(0.5 * $jml_iterasi_mc)];
			$percentile_05[$i] = $percentile_temp2[round(0.05 * $jml_iterasi_mc)];

            $final_result_mc[] = array(
                "id" => $id,
                "sisa_dinas" => $tabel_sisa[$i],
                "usia_pensiun" => $tabel_usia[$i],
                "Percentile95" => $percentile_95[$i],
                "Percentile50" => $percentile_50[$i],
                "Percentile05" => $percentile_05[$i]
            );
            
            $id--;
		}

        $res = array_reverse($final_result_mc);

        return response()->json($res);
    }

    function umur_kini($tgl_lahir){
        $lahir = new DateTime($tgl_lahir);
        $hari_ini = new DateTime();
        $diff = $hari_ini->diff($lahir);
        return array($diff->y,$diff->m,$diff->d);
    }
}
